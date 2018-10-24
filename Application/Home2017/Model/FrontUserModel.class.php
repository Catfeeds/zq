<?php
use Think\Model;
/**
 * 会员模型
 */
class FrontUserModel extends Model{
    //商城与qqty共用cookie
    public $u_info = array();
	/* 用户模型自动验证 */
	protected $_validate = array(
		/* 验证用户名 */
	);

	/* 用户模型自动完成 */
	protected $_auto = array(

	);

	/**
	 * 用户登录认证
	 * @param  string  $username 用户名
	 * @param  string  $password 用户密码
	 * @param  integer $type     用户名类型 （1-用户名，2-邮箱，3-手机，4-UID）
	 * @return integer           登录成功-用户ID，登录失败-错误编号
	 */
	public function login($username, $password){
		$map = array();
		/* 获取用户数据 */
		$map['status'] = 1;
		$map['username'] = $username;
		$user = $this->where($map)->find();
		if(is_array($user) && $user['status']){
			/* 验证用户密码 */
			if(md5($password) === $user['password']){
				return $user['id']; //登录成功，返回用户ID
			} else {
				return -2; //密码错误
			}
		} else {
			return -1; //用户不存在或被禁用
		}
	}

	/**
     * 注销当前用户
     * @return void
     */
    public function logout(){

        $data['u_k'] = '';
        $data['q_log_status'] = 0;//0为注销
        $data['s_log_status'] = 1;
        $key = $this->encrypt(json_encode($data));
        setcookie("u_k", $key, 0, "/", DOMAIN);
        // $_SESSION['sess_']=array();
        // $oldSess = $_SESSION;
        // session_regenerate_id();
        // session_start();
        // $_SESSION = $oldSess;
        session('user_auth',null);
        session_destroy();
        cookie('u_p', null);
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    public function autoLogin($id){
    	$user = $this->find($id);
        $user['head'] = frontUserFace($user['head']);
        /* 记录登录SESSION和COOKIES */
        $auth = array(
            'id'        => $user['id'],
            'username'  => $user['username'],
            'nick_name' => $user['nick_name'],
            'password'  => $user['password'],
            'head'=>$user['head'],
        );
        $last_login_ver = 'Web端';
        if(cookie('sdk_sign')=='m'){
            $last_login_ver = 'm站';
        }
        session('user_auth', $auth);
        setcookie('userLoginInfoEsr',$user['id'].';'.$user['nick_name'].';'.$user['head'],0,'/',DOMAIN);//esr用到
        $data['u_k'] = $id;
        $data['q_log_status'] = 1;//1为已登入
        $data['s_log_status'] = 2;//2为未登入,两个参数适用
        $key = $this->encrypt(json_encode($data));
        setcookie("u_k", $key, 0, "/", DOMAIN);
        //修改最后登录时间 登录次数加1 记录最后登录ip 唯一session_id
        $saveArr = array(
        	'login_count' => ['exp','login_count+1'],
        	'login_time'  => time(),
        	'last_ip'     => get_client_ip(),
            'last_login_ver' => $last_login_ver,
        	'session_id'  => session_id()
        );
        //每天首次登陆赠送积分
        // if ($user['login_time'] < strtotime(date('Ymd')))
        // {
        //     $changeNum = C('givePoint')['login'];
        //     //添加积分明细记录
        //     M('PointLog')->add([
        //         'user_id'     => $id,
        //         'log_time'    => NOW_TIME,
        //         'log_type'    => 11,
        //         'change_num'  => $changeNum,
        //         'total_point' => $user['point']+$changeNum,
        //         'desc'        => '登陆赠送'
        //     ]);
        //     //添加积分
        //     $saveArr['point'] = ['exp','point+'.$changeNum];
        //     //发送系统消息通知
        //     sendMsg($id,'积分赠送通知','您好！今日首次登录赠送'.$changeNum.'积分，详情请查看积分明细。');
        // }
		$rs = $this->where(array('id'=>$id))->save($saveArr);
		if($rs)
        return true;
    }


    /*
     * 对公用cookie进行数据保存处理
     */
    public function set_u_k($key,$val)
    {
        $this->u_info = json_decode($this->decrypt($_COOKIE['u_k']),true);
        $this->u_info[$key] = $val;
        $key = $this->encrypt(json_encode($this->u_info));
        setcookie("u_k", $key, 0, "/", DOMAIN);
    }

    /**
     * 加密函数
     * @param string $txt 需要加密的字符串
     * @param string $key 密钥
     * @return string 返回加密结果
     */
    public function encrypt($txt, $key = ''){
        if (empty($txt)) return $txt;
        if (empty($key)) $key = md5(MD5_KEY);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $nh1 = rand(0,64);
        $nh2 = rand(0,64);
        $nh3 = rand(0,64);
        $ch1 = $chars{$nh1};
        $ch2 = $chars{$nh2};
        $ch3 = $chars{$nh3};
        $nhnum = $nh1 + $nh2 + $nh3;
        $knum = 0;$i = 0;
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum%8,$knum%8 + 16);
        $txt = base64_encode(time().'_'.$txt);
        $txt = str_replace(array('+','/','='),array('-','_','.'),$txt);
        $tmp = '';
        $j=0;$k = 0;
        $tlen = strlen($txt);
        $klen = strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = ($nhnum+strpos($chars,$txt{$i})+ord($mdKey{$k++}))%64;
            $tmp .= $chars{$j};
        }
        $tmplen = strlen($tmp);
        $tmp = substr_replace($tmp,$ch3,$nh2 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch2,$nh1 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch1,$knum % ++$tmplen,0);
        return $tmp;
    }

    /**
     * 解密函数
     * @param string $txt 需要解密的字符串
     * @param string $key 密匙
     * @return string 字符串类型的返回结果
     */
    public function decrypt($txt, $key = '', $ttl = 0){
        if (empty($txt)) return $txt;
        if (empty($key)) $key = md5(MD5_KEY);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $knum = 0;$i = 0;
        $tlen = @strlen($txt);
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $ch1 = @$txt{$knum % $tlen};
        $nh1 = strpos($chars,$ch1);
        $txt = @substr_replace($txt,'',$knum % $tlen--,1);
        $ch2 = @$txt{$nh1 % $tlen};
        $nh2 = @strpos($chars,$ch2);
        $txt = @substr_replace($txt,'',$nh1 % $tlen--,1);
        $ch3 = @$txt{$nh2 % $tlen};
        $nh3 = @strpos($chars,$ch3);
        $txt = @substr_replace($txt,'',$nh2 % $tlen--,1);
        $nhnum = $nh1 + $nh2 + $nh3;
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum % 8,$knum % 8 + 16);
        $tmp = '';
        $j=0; $k = 0;
        $tlen = @strlen($txt);
        $klen = @strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = strpos($chars,$txt{$i})-$nhnum - ord($mdKey{$k++});
            while ($j<0) $j+=64;
            $tmp .= $chars{$j};
        }
        $tmp = str_replace(array('-','_','.'),array('+','/','='),$tmp);
        $tmp = trim(base64_decode($tmp));
        if (preg_match("/\d{10}_/s",substr($tmp,0,11))){
            if ($ttl > 0 && (time() - substr($tmp,0,11) > $ttl)){
                $tmp = null;
            }else{
                $tmp = substr($tmp,11);
            }
        }
        return $tmp;
    }


}