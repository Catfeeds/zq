<?php

use Think\Model;

/**
 * 会员模型
 */
class FrontUserModel extends Model {
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
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function login($username, $password) {
        $map = array();
        /* 获取用户数据 */
        $map['username'] = $username;
        $user = $this->where($map)->find();
        if ($user) {
            if ($user['status'] == 0) {
                return -3; //账户已被禁用
            }
            /* 验证用户密码 */
            if (md5($password) === $user['password']) {
                $day_first=0;
                $this->where(['id'=>$user['id']])->save([
                    'login_count' => ['exp', 'login_count+1'],
                    'login_time' => time(),
                    'last_ip' => get_client_ip(),
                    'last_login_ver' => 'M站',
                    'session_id' => session_id()
                ]);
//                if (($user['login_time'] < strtotime(date('Ymd')))) {
//                    $this->where($map)->setInc('point',100);
//                    M('PointLog')->add(array(
//                        'user_id'=>$user['id'],
//                        'log_time'=>time(),
//                        'log_type'=>11,
//                        'change_num'=>100,
//                        'total_point'=>$user['point']+100,
//                        'desc'=>'登陆赠送',
//                    ));
//                    $day_first=100;
//                }
                $user['head'] = frontUserFace($user['head']);
                session('user_auth', array('id' => $user['id'], 'username' => $user['username'], 'nick_name' => $user['nick_name'],'password'=>$user['password'],'head'=>$user['head']));
                return $user['id']; //登录成功，返回用户ID
            } else {
                return -2; //密码错误
            }
        } else {
            return -1; //用户不存在
        }
    }
    /**
     * 忘记密码
     * @param  string  $username 用户名
     * @param  string  $password 用户密码
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function findPwd($username, $password) {
        $map = array();
        /* 获取用户数据 */
        $map['username'] = $username;
        $user = $this->where($map)->find();
        if ($user) {
            if ($user['status'] == 0) {
                return -3; //账户已被禁用
            }
            if (md5($password) === $user['password']) {
                return -4;//与原密码相同
            }
            /* 验证用户密码 */
            $rsl=$this->where($map)->setField('password',md5($password));
            if($rsl){
                return $rsl;
            }else{
                return -2;
            }
        } else {
            return -1; //用户不存在
        }
    }
    /**
     * 修改密码
     * @param  string  $id 用户id
     * @param  string  $oldpwd 用户旧密码
     * @param  string  $password 用户新密码
     * @return integer           登录成功-用户ID，登录失败-错误编号
     */
    public function updatePwd($id, $oldpwd, $password,$type) {
        $map = array();
        /* 获取用户数据 */
        $map['id'] = $id;
        $user = $this->where($map)->find();
        if ($user) {
            //0:修改登录密码,1:修改提款密码
            if(!$type){
                if (md5($oldpwd) !== $user['password']) {
                    return -5; //原密码不正确
                }
                if ($user['status'] == 0) {
                    return -3; //账户已被禁用
                }
                if (md5($password) === $user['password']) {
                    return -4;//与原密码相同
                }
                /* 验证用户密码 */
                $rsl=$this->where($map)->setField('password',md5($password));
                if($rsl){
                    return $rsl;
                }else{
                    return -2;
                }
            }else{
                if (md5($oldpwd) !== $user['bank_extract_pwd']) {
                    return -5; //原密码不正确
                }
                if ($user['status'] == 0) {
                    return -3; //账户已被禁用
                }
                if (md5($password) === $user['bank_extract_pwd']) {
                    return -4;//与原密码相同
                }
                /* 验证用户密码 */
                $rsl=$this->where($map)->setField('bank_extract_pwd',md5($password));
                if($rsl){
                    return $rsl;
                }else{
                    return -2;
                }
            }
        } else {
            return -1; //用户不存在
        }
    }

    /**
     * 注销当前用户
     * @return void
     */
    public function logout() {
        session('user_auth', null);
        session_destroy();
        cookie('u_p', null);
    }

    /**
     * 自动登录用户
     * @param  integer $user 用户信息数组
     */
    public function autoLogin($id) {
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

        session('user_auth', $auth);

        //修改最后登录时间 登录次数加1 记录最后登录ip
        $rs = $this->where(array('id' => $id))->save(['login_count' => ['exp', 'login_count+1'], 'login_time' => time(), 'last_ip' => get_client_ip(), 'session_id' => session_id()]);
        if ($rs)
            return true;
    }

    /**
     * 注册或者活动赠送大礼包
     */
    public function giftBag($userid, $platform, $type, $get_type, $gift_id=0){
        //检验是否在活动时间内
        if($type == 3)
            $where['id'] = $gift_id;

        $where['type']       = $type;
        $where['start_time'] = ['lt', NOW_TIME];
        $where['end_time']   = ['gt', NOW_TIME];
        $where['status']     = 1;

        if(M('GiftsConf')->where($where)->count() == 0)
            return false;

        //不能重复领取
        if($gift_id){
            if(M('TicketLog')->master(true)->where(['gift_id' => $gift_id, 'user_id' => $userid])->count())
                return false;
        }

        $where['over_time'] = ['gt', NOW_TIME];//券的有效期
        unset($where['id'], $where['start_time'], $where['end_time']);

        $res = M('GiftsConf')->field('id, game_ticket, coin_ticket, over_time, start_time, end_time, remark ')->where($where)->order(' id desc ')->find();

        //推荐和充值体验券
        $data = $cdata = [];
        $game = json_decode($res['game_ticket'], true);

        // 3:注册赠送  4:活动赠送
        $msg1 = $get_type == 3 ? '推荐体验券-注册赠送' : '推荐体验券-活动赠送';
        foreach($game as $k => $v){
            for($i=0; $i < $v; $i++){
                $data[$i]['name']        = $k.C('giftPrice');
                $data[$i]['user_id']     = $userid;
                $data[$i]['type']        = 1;
                $data[$i]['price']       = $k;
                $data[$i]['get_time']    = NOW_TIME;
                $data[$i]['over_time']   = $res['over_time'];
                $data[$i]['plat_form']   = $platform;
                $data[$i]['get_type']    = $get_type;
                $data[$i]['remark']      = $msg1;
                $data[$i]['gift_id']     = $res['id'];
            }

            M('TicketLog')->addAll($data);
            unset($data);
        }

        $coin = json_decode($res['coin_ticket'], true);
        $msg2 = $get_type == 3 ? '充值体验券-注册赠送' : '充值体验券-活动赠送';
        foreach($coin as $k => $v){
            $kk = explode('_', $k);
            for($i=0; $i < $v; $i++){
                $cdata[$i]['name']        = '满'.$kk[0].'送'.$kk[1].'金币';
                $cdata[$i]['user_id']     = $userid;
                $cdata[$i]['type']        = 2;
                $cdata[$i]['price']       = $kk[0];
                $cdata[$i]['give_coin']   = $kk[1];
                $cdata[$i]['get_time']    = NOW_TIME;
                $cdata[$i]['over_time']   = $res['over_time'];
                $cdata[$i]['plat_form']   = $platform;
                $cdata[$i]['get_type']    = $get_type;
                $cdata[$i]['remark']      = $msg2;
                $cdata[$i]['gift_id']     = $res['id'];
            }

            M('TicketLog')->addAll($cdata);
            unset($cdata);
        }

        return true;
    }

    //检查注册ip：相同IP一个小时注册不能超过20个，注册不限制时间间隔
    public function checkReg($mac_addr)
    {
        $mac_addr = $mac_addr?$mac_addr:I('deviceID');
        $ip = get_client_ip();
        $loginConf = getWebConfig('loginGift');
        $regList = M('FrontUser')->field('reg_time')->where(['reg_ip'=>$ip,'reg_time'=>['between', [NOW_TIME-60*60, NOW_TIME]],'mac_addr'=>$mac_addr])->order('reg_time desc')->select();
        if ((isset($regList[0]['reg_time']) && (NOW_TIME - $regList[0]['reg_time'] <= $loginConf['reg_limit_time']*60)) || count($regList) > $loginConf['reg_limit_count'])
            return false;
        return $ip;
    }

}
