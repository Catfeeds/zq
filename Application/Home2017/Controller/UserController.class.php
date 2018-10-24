<?php
/**
 * 用户注册登录程序
 *
 * @author dengweijun <406516482@qq.com>
 * @since  2015-11-27
 */
use Vendor\ThinkSDK\ThinkOauth;
/**
 * 用户控制器
 * 包括用户登录及注册
 */
class UserController extends CommonController {

	protected function _initialize()
	{
	    C('HTTP_CACHE_CONTROL','no-cache,no-store');
	}

	/* 注册页面第一步 */
	public function register(){
		$token = md5(uniqid(rand(), TRUE));
  		session('token',$token);
  		$this->assign('token',$token);
		if(IS_POST){
			/* 检测验证码 */
			$isTrue = self::checkMobileVerify(I('captcha'),I('mobile'));
			if(!$isTrue){
				$this->error('验证码错误或已超时');
				exit;
			}else{
				$this->success('验证成功！');
			}
		} else { //显示注册表单
			
			$this->assign('position','欢迎注册');
			$this->display();
		}
	}
	/* 注册页面第二步 */
	public function infor(){
        $verify = S(cookie('verifyCode'));
        $verifySign = cookie('verifySign');
        if(!$verify || !$verifySign || $verify['mobile'] != $verifySign['mobile'] || $verify['rank'] != $verifySign['rank']){
            $this->error('注册信息已经过时，请重新验证手机注册！',U('User/register'));
        }
		if(IS_POST){
			$nickname = I('nick_name');
			$isNickname = M('frontUser')->where(array('nick_name'=>$nickname))->find();
			if($isNickname){
			    $this->error('该用户名已经存在');
			}
			$length = mb_strlen($nickname, 'utf-8');
			if ($length < 2 || $length > 10) {
			    $this->error('用户昵称必须大于2位,小于10位');
			}
			$isTrue = matchFilterWords('nickFilter', $nickname);
			if (!$isTrue) {
			    $this->error('您的名字不合法,请重新输入!');
			}
			/* 检测数据 */
			$UserArray=array(
					'username'  =>  $verify['mobile'],
					'nick_name' =>  $nickname,
					'password'  =>  md5(I('password')),
					'reg_time'  =>  time(),
					'reg_ip'    =>  get_client_ip(),
					'platform'  =>  1, //注册的平台
					'channel_code' => 'web',
				);
			$FrontUserId = M('frontUser')->add($UserArray);
			if($FrontUserId){
				D('FrontUser')->autoLogin($FrontUserId);
				$this->success('注册成功！');
			}else{
				$this->error('注册失败！');
			}
		} else { //显示注册表单
			$this->assign('position','欢迎注册');
			$this->display();
		}
	}
	/* 注册页面第三步 */
	public function complete(){
        $verify = S(cookie('verifyCode'));
        $verifySign = cookie('verifySign');
        if(!$verify || !$verifySign || $verify['mobile'] != $verifySign['mobile'] || $verify['rank'] != $verifySign['rank']){
            $this->error('注册信息已经过时，请重新验证手机注册！',U('User/register'));
        }
        //成功后删除cookie里的验证码
		S(cookie('verifyCode'),null);
        cookie('verifyCode', null);
        cookie('verifySign', null);
		$this->assign('position','欢迎注册');
		$this->display();
	}
	/* 找回登录密码找回提款密码第一步 */
	public function re_phone()
	{
		$token = md5(uniqid(rand(), TRUE));
  		session('token',$token);
  		$this->assign('token',$token);
		$user_id = is_login();
		$actionStatus = I('operation') == 'backDrawPass' ? '找回提款密码':'找回密码';
		if(IS_POST){
			//找回提款密码信息检验
			if (I('operation') == 'backDrawPass')
			{
				$true_name = I('true_name','','htmlspecialchars');
				$identfy = I('identfy','','htmlspecialchars');
                
				if (empty($user_id))
				    $this->error('请先登录！',U('User/login'));
                
				$userData = M('FrontUser')->where(['id'=>$user_id])->Field('true_name,identfy,bank_card_id,alipay_id')->find();
                
				if (empty($userData) || empty($userData['true_name']) || empty($userData['identfy']))
					$this->error('请完善身份认证！',U('UserInfo/identity'));
                
                if (empty($userData['bank_card_id']) && empty($userData['alipay_id']))
                    $this->error('请绑定银行卡号或支付宝账号！');
                
				if ($userData['true_name'] === $true_name && $userData['identfy'] === $identfy)
				{
					$data = [
						'true_name'=> $true_name,
						'identfy'=> $identfy,
						'operation'=> 'backDrawPass',
					];
					S('IdentityInfo:'.$user_id,$data,600);//缓存真实姓名、身份证号
				}
				else
				{
					$this->error('真实姓名或身份证不正确！');
				}

			}
			/* 检测验证码 */
			$isTrue = self::checkMobileVerify(I('captcha'),I('mobile'));
			if(!$isTrue)
			{
				$this->error('验证码错误或已超时');
				exit;
			}else
			{
				$this->success('验证成功！');
			}
		}
		else
		{   //显示填写表单
			if (I('operation') == 'backDrawPass')
			{
				if (empty($user_id)) $this->error('请先登录!',U('User/login'));
				$userData = M('FrontUser')->where(['id'=>$user_id])->Field('true_name,identfy')->find();
				if (empty($userData) || empty($userData['true_name']) || empty($userData['identfy']))
					$this->error('请完善身份认证!',U('UserInfo/identity'));
			}
			$this->assign('position',$actionStatus);
			if (I('operation') == 'backDrawPass') $this->assign('operation','backDrawPass');
			$this->display('User/modpassword/re_phone');
		}
	}
	/* 找回登录密码找回提款密码第二步 */
	public function new_password()
	{
        $user_id = is_login();
		$IdentityInfo = S('IdentityInfo:'.$user_id);//真实姓名、身份证号
		$actionStatus = $IdentityInfo['operation'] == 'backDrawPass' ? '找回提款密码':'找回密码';
		$jointUrl = $IdentityInfo['operation'] == 'backDrawPass' ? array('operation'=>'backDrawPass') : array();
        $verify = S(cookie('verifyCode'));
        $verifySign = cookie('verifySign');
        if(!$verify || !$verifySign || $verify['mobile'] != $verifySign['mobile'] || $verify['rank'] != $verifySign['rank'])
		{
			S('IdentityInfo:'.$user_id,null);//删除缓存（真实姓名、身份证号）
        	$this->error('验证码信息已经过时，请重新验证手机！',U('User/re_phone',$jointUrl));
        }
		if(IS_POST)
		{
			$password = I('password','','htmlspecialchars');
			/* 检测数据 */
			if ($IdentityInfo['operation'] == 'backDrawPass')//找回提款密码
			{
				if (! empty($user_id))
				{
				    $dataUser = M('FrontUser')->where(['id'=>$user_id])->Field('username,true_name,identfy')->find();
                    if (! $IdentityInfo || $IdentityInfo['operation'] != 'backDrawPass' || $verify['mobile'] != $dataUser['username'])
                        $this->error('请重新填写信息！',U('User/re_phone',$jointUrl));
					if ($IdentityInfo['identfy'] != $dataUser['identfy'] || $IdentityInfo['true_name'] != $dataUser['true_name'])
                        $this->error('请重新填写信息！',U('User/re_phone',$jointUrl));
					if (! preg_match('/^\d{6}$/',$password))
						$this->error('输入格式不正确！');
					$rs = M('FrontUser')->where(['id'=>$user_id])->save(['bank_extract_pwd'=>md5($password)]);
				}
				else
				{
					$this->error('请先登录!',U('User/login'));
				}
			}
			else//找回登录密码
			{
				if (! preg_match('/^[a-z\d]{6,15}$/i',$password)) $this->error('输入格式不正确！');
				$rs = M('FrontUser')->where(['username'=>$verify['mobile']])->save(['password'=>md5($password)]);
			}
			if(! is_bool($rs)) $rs = true;
			$rs ? $this->success('修改成功！') : $this->error('修改失败！');


		}
		else
		{ //显示表单
			$this->assign('position',$actionStatus);
			if ($IdentityInfo['operation'] == 'backDrawPass') $this->assign('operation','backDrawPass');
			$this->display('User/modpassword/new_password');
		}
	}
	/* 找回登录密码找回提款密码第三步 */
	public function recovered()
	{
		$IdentityInfo = S('IdentityInfo:'.is_login());//真实姓名、身份证号
		$actionStatus = $IdentityInfo['operation'] == 'backDrawPass' ? '找回提款密码':'找回密码';
		$jointUrl = $IdentityInfo['operation'] == 'editDrawPass' ? array('operation'=>'backDrawPass') : array();
        $verify = S(cookie('verifyCode'));
        $verifySign = cookie('verifySign');
        if(!$verify || !$verifySign || $verify['mobile'] != $verifySign['mobile'] || $verify['rank'] != $verifySign['rank']){
			S('IdentityInfo',null);//删除缓存（真实姓名、身份证号）
            $this->error('验证码信息已经过时，请重新验证手机！',U('User/re_phone',$jointUrl));
        }
        //成功后删除验证码
        S(cookie('verifyCode'),null);
        cookie('verifyCode', null);
        cookie('verifySign', null);
		S('IdentityInfo',null);//删除缓存（真实姓名、身份证号）
        $this->assign('position',$actionStatus);
		if ($IdentityInfo['operation'] == 'backDrawPass') $this->assign('operation','backDrawPass');
		$this->display('User/modpassword/recovered');
	}
	/**
    * 校验手机验证码
	* @param string $verifyNum 	#待验证的验证码
    * @param string $verifyNum  #待验证的手机号
    * @return  #
    */
    public function checkMobileVerify($verifyNum,$mobile)
    {
		//获取验证码
        $verify = S(cookie('verifyCode'));
		if (empty($verify)){
            //验证码超时
            return false;
        } elseif($verify['rank'] != $verifyNum || $verify['mobile'] != $mobile){
            //验证码错误
            return false;
		} else {
			//验证通过
            cookie('verifySign',['mobile'=>$mobile,'rank'=>$verifyNum],C('verifyCodeTime'));
			return true;
		}
	}

	/*发送手机验证码*/
	public function sendMobileMsg()
	{
		$token   = I('token');
		if($token != session('token') || !IS_AJAX){
			$this->error('发送失败，请稍后重试！');
		}
		if(checkShieldIp()){
			$this->error('注册失败，请联系管理员');
		}
		$mobile  = I('mobile');
		$msgType = I('msgType');
		$isMobile = M('FrontUser')->where(['username'=>$mobile])->find();
		if ($msgType == 'registe')//注册
		{
			//是否已注册
			if($isMobile){
				$this->error('该手机号码已经注册，不能再注册！');
				exit;
			}
		}
		elseif ($msgType == 'editPwd')//修改密码(找回密码)
		{
			//是否未注册
			if(!$isMobile){
				$this->error('该手机号码未注册！');
				exit;
			}
		}
        elseif ($msgType == 'verifyPhone')//验证手机号
        {
            //是否未注册
            if(!$isMobile){
                $this->error('该手机号码未注册！');
                exit;
            }
        }
		elseif ($msgType == 'bindPhone')//绑定手机
		{
			$user_id = is_login();
			if (! empty($user_id))
			{
				$username = M('FrontUser')->where(['id'=>$user_id])->getField('username');
				if($username) $this->error('已经绑定过手机！');
			}
			else
			{
				$this->error('请先登录!',U('User/login'));
			}

		}
		elseif ($msgType == 'editExtractPwd' || $msgType == 'backDrawPass')//修改提款密码或找回提款密码
		{
			$user_id = is_login();
			if (! empty($user_id))
			{
				$userData = M('FrontUser')
							->where(['username'=>$mobile,'id'=>$user_id])
							->field('username,alipay_id,true_name,identfy,bank_card_id,bank_extract_pwd')
							->find();
				if(empty($userData['username'])) $this->error('请绑定手机号！');
				if(empty($userData['true_name']) || empty($userData['identfy'])
					|| (empty($userData['bank_card_id']) && empty($userData['alipay_id']))
                    || empty($userData['bank_extract_pwd']))
				{
					$this->error('操作失败！');
				}
                
			}
			else
			{
				$this->error('请先登录!',U('User/login'));
			}
		}
		else
		{
			$this->error('无法操作');
		}
		$_POST['platform'] = 1;
		$result = sendCode($mobile,$msgType);
		
		if($result == '-1'){
		    //已经发送过,需等待60秒
		    $this->error('您已经发送过验证码,请等待'.C('reSendCodeTime').'秒后重试!');
		    exit;
		}
		if($result){
            cookie('verifyCode',$result['token'],C('verifyCodeTime'));  //存返回值
            $msg = $result['mobileSMS'] == 3 ? '请留意稍后的电话语音通知' : '请留意下发的短信通知';
			//发送成功
			$this->success('发送成功，'.$msg.'，验证码'.(C('verifyCodeTime')/60).'分钟内有效，请尽快完成验证！');
		}else{
			//发送失败
			$this->error('您发送太频繁了，请稍后重试！');
		}
	}
	/*验证手机是否注册,已经注册返回false*/
	public function checkMobile(){
		$mobile = I('mobile');
		$isMobile = M('frontUser')->where(array('username'=>$mobile))->find();
		if(!$isMobile){
			echo "true";
		}else{
			echo "false";
		}
	}
	/*验证手机是否注册,未注册返回false*/
	public function checkMobileThere(){
		$mobile = I('mobile');
		$isMobile = M('frontUser')->where(array('username'=>$mobile))->find();
		if($isMobile){
			echo "true";
		}else{
			echo "false";
		}
	}
	/*验证用户昵称是否存在*/
	public function checkNickname(){
		$nickname = I('nick_name');
		$isNickname = M('frontUser')->where(array('nick_name'=>$nickname))->find();
        //关键字检查
        $isTrue = matchFilterWords('nickFilter',$nickname);
		if($isNickname || !$isTrue){
			echo "false";
		}else{
			echo "true";
		}
	}

	/* 退出登录 */
	public function logout(){
		if(is_login()){
			D('FrontUser')->logout();
			header("location:".U('/'));
		} else {
			$this->redirect('User/login');
		}
	}

	//登录地址
    public function sdk_login($type = null)
    {
        empty($type) && $this->error('参数错误');
        if(checkShieldIp()){
        	$this->error('第三方登录失败，请联系管理员','/');
        }
        //加载ThinkOauth类并实例化一个对象
        $sns = ThinkOauth::getInstance($type);
        /*dump($type);
        dump($sns->getRequestCodeURL());
        exit;*/
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }

    //授权回调地址
    public function callback($type = null, $code = null)
    {
        (empty($type) || empty($code)) && $this->redirect('User/login');

        //加载ThinkOauth类并实例化一个对象
        $sns = ThinkOauth::getInstance($type);

        //腾讯微博需传递的额外参数
        $extend = null;
        if ($type == 'tencent') {
            $extend = array('openid' => $this->_get('openid'), 'openkey' => $this->_get('openkey'));
        }
        //请妥善保管这里获取到的Token信息，方便以后API调用
        //调用方法，实例化SDK对象的时候直接作为构造函数的第二个参数传入
        //如： $qq = ThinkOauth::getInstance('qq', $token);
        $token = $sns->getAccessToken($code, $extend);

        //获取当前登录用户信息
        if (is_array($token)) {
            if(!is_null(session('user_auth')) && session('user_auth')['id'] > 0){
                //已登录绑定第三方登录帐号
                switch ($type) {
                    case 'qq':
                        $sdk_unionid = 'qq_unionid';
                        $sdk_array   = ['qq_unionid'=>$token['openid']];
                        break;
                    case 'weixin':
                        $sdk_unionid = 'weixin_unionid';
                        $sdk_array   = ['weixin_unionid'=>$token['unionid']];
                        break;
                    case 'sina':
                        $sdk_unionid = 'sina_unionid';
                        $sdk_array   = ['sina_unionid'=>$token['openid']];
                        break;
                }
                //查看是否已绑定其他第三方登录id
                $FrontUserId = M("FrontUser")->where($sdk_array)->where(['id'=>['neq',session('user_auth')['id']]])->getField('id');
                if(!is_null($FrontUserId) && $FrontUserId > 0){
                    echo "<script>alert('绑定失败，该帐号已被其他用户绑定！');window.close();</script>";
                    exit;
                }
                //绑定第三方登录帐号
                if(!M("FrontUser")->where(['id'=>session('user_auth')['id']])->save($sdk_array)){
                	session_destroy();
                    echo "<script>alert('绑定失败，请稍后再试！');window.close();</script>";
                    exit;
                }else{
                    if(cookie('sdk_sign')=='m'){
                        $this->redirect('User/basic@m');
                    }else{
                        echo "<script>window.opener.location.href = '/UserInfo/index.html';window.close();</script>";
                        exit;
                    }
                }
            }else{
                self::OauthLogin($token,$type);
            }
        }else{
			$this->error('登录失败，请重试！',U('User/sdk_login',['type'=>$type]));
		}
    }
    /*
	* $token 第三方登录参数
	* $type  第三方登录类型（'qq','weixin','sina'）
    */
    public function OauthLogin($token,$type)
    {
    	//查询是否已绑定
    	switch ($type) {
    		case 'qq':
    			$FrontUserId = M("FrontUser")->where(['qq_unionid'=>$token['openid']])->getField('id');
    			break;
    		case 'weixin':
    			$FrontUserId = M("FrontUser")->where(['weixin_unionid'=>$token['unionid']])->getField('id');
    			break;
    		case 'sina':
    			$FrontUserId = M("FrontUser")->where(['sina_unionid'=>$token['openid']])->getField('id');
    			break;
    	}
    	if(!is_null($FrontUserId) && $FrontUserId > 0){
    		//已绑定
    		D('FrontUser')->autoLogin($FrontUserId);
                if(cookie('sdk_sign')=='m'){
                    header("location:".U('User/index@m'));
                }else{
                    $this->redirect('/');
                }
    	}else{
    		//转跳用户操作页面
    		$token['type'] = $type;
            S('loginToken:'.session_id(),$token,C('loginTokenTime'));
            if(cookie('sdk_sign')=='m'){
                cookie('loginToken',$token,array('expire'=>C('loginTokenTime'),'domain'=>'.'.DOMAIN));
                $this->redirect('User/tpperfect@m');
            }else{
                $this->redirect('User/tpperfect');
            }
    	}
    }
    
    /* 第三方登录页面注册 */
    public function tpperfect(){
    	$token = S('loginToken:'.session_id());
    	if(!$token){
    		$this->error('请求超时，请重试！',U('User/login'));
    	}
        //发送模版区分第三方登录类型
        $this->assign('sdk_type',$token['type']);
    	if(IS_POST){
    		//区分注册新用户还是绑定已有用户 0：新增  1：绑定
    		$is_has = I('is_has');
    		if($is_has == '0'){
    			$UserArray['nick_name'] = I('nick_name');
    			$UserArray['reg_time']  = time();
    			$UserArray['reg_ip']    = get_client_ip();
    			$UserArray['platform']  = 1; //注册的平台
    			$username = I('mobile');
    			$password = I('password');
    			if(!empty($username) || !empty($password)){
    				if(empty($username)) $this->error('请输入手机号码！');
    				if(empty($password)) $this->error('请输入登录密码！');
    				$UserArray['username']  = $username;
    				$UserArray['password']  = md5($password);
    			}
    			/* 新增用户 */
    			switch ($token['type']) {
    				case 'qq':
    					$UserArray['qq_unionid'] = $token['openid'];
    					break;
    				case 'weixin':
    					$UserArray['weixin_unionid'] = $token['unionid'];
    					break;
    				case 'sina':
    					$UserArray['sina_unionid'] = $token['openid'];
    					break;
    			}
    			$FrontUserId = M('frontUser')->add($UserArray);
    			
    			if($FrontUserId){
    				//保存头像
    				$head = I('head');
    				$img  = Think\Tool\Tool::url_get_contents($head);
    				$return = D('Uploads')->uploadFileBase64(base64_encode($img), "user", "face", "200", $FrontUserId);
    				if($return['status'] == 1){
    					M("frontUser")->where(['id'=>$FrontUserId])->save(['head'=>$return['url']]);
    				}
    				//登录
    				D('FrontUser')->autoLogin($FrontUserId);
                    S('loginToken:'.session_id(),NULL);
    				$this->success('注册成功！');
    			}else{
    				$this->error('注册失败,请重试！');
    			}
    		}elseif ($is_has == '1') {
    			/* 绑定用户 */
    			$username = I('has_mobile');
    			$password = I('has_password');
    			//检查帐号密码
    			$user_id = D('FrontUser')->login($username, $password);
    			if(0 < $user_id)//验证成功
    			{
    				//根据第三放登录类型组装对应信息
    				switch ($token['type']) {
	    				case 'qq':
	    					$sdk_unionid = 'qq_unionid';
	    					$sdk_array   = ['qq_unionid'=>$token['openid']];
	    					break;
	    				case 'weixin':
	    					$sdk_unionid = 'weixin_unionid';
	    					$sdk_array   = ['weixin_unionid'=>$token['unionid']];
	    					break;
	    				case 'sina':
	    					$sdk_unionid = 'sina_unionid';
	    					$sdk_array   = ['sina_unionid'=>$token['openid']];
	    					break;
	    			}
	    			//查看是否已绑定其他第三方登录id
    				$is_bind = M("frontUser")->where(['id'=>$user_id])->getField($sdk_unionid);
    				if($is_bind){
    					$this->error("绑定失败，该帐号已被绑定！");
    				}
    				//绑定第三方登录id
    				if(!M("FrontUser")->where(['id'=>$user_id])->save($sdk_array)){
    					$this->error("绑定失败，请稍后再试！");
    				}
    			    /* 登录用户 */
    			    if(D('FrontUser')->autoLogin($user_id))//登录用户
    			    {
                        S('loginToken:'.session_id(),NULL);
    			        $this->success('绑定成功！');
    			    } else {
    			        $this->error("绑定失败，请稍后再试！");
    			    }
    			} else {
    			    //登录失败
    			    switch($user_id) {
    			        case -1: $error = '用户不存在或被禁用'; break; //系统级别禁用
    			        case -2: $error = '账户名与密码不匹配，请重新输入'; break;
    			        default: $error = '未知错误'; break; // 0-接口参数错误（调试阶段使用）
    			    }
    			    $this->error($error);
    			}
    		}
    	} else {
    		//获取第三方用户信息
    		switch ($token['type']) {
    			case 'qq':
    				$user_info = self::qq($token);
    				break;
    			case 'weixin':
    				$user_info = self::weixin($token);
    				break;
    			case 'sina':
    				$user_info = self::sina($token);
    				break;
    		}

            $name = $token['type'] == 'weixin' ? 'nickname' : 'name';
            $randName = $token['type'].'_'.$user_info[$name].rand(0,99);
            if(mb_strlen($randName,'utf-8') > 10){
                $randName = mb_substr($randName, 0,10,'utf-8');
            }
            $user_info['randName'] = $randName;
    		$this->assign('user_info',$user_info);
    		$this->display();
    	}
    }

    //登录成功，获取腾讯QQ用户信息
    public function qq($token)
    {
        $qq = ThinkOauth::getInstance('qq', $token);
        $data = $qq->call('user/get_user_info');

        if ($data['ret'] == 0) {
            $userInfo['type'] = 'QQ';
            $userInfo['name'] = $data['nickname'];
            $userInfo['nick'] = $data['nickname'];
            $userInfo['head'] = $data['figureurl_2'];
            return $userInfo;
        } else {
            throw_exception("获取腾讯QQ用户信息失败：{$data['msg']}");
        }
    }

    //登录成功，获取微信用户信息
    public function weixin($token)
    {
        $Weixin = ThinkOauth::getInstance('Weixin', $token);
        $userInfo = $Weixin->call('sns/userinfo');
        if ($userInfo) {
            return $userInfo;
        } else {
            $this->error("获取微信用户信息失败！");
        }
    }

    //登录成功，获取新浪微博用户信息
    public function sina($token)
    {
        $sina = ThinkOauth::getInstance('sina', $token);
        $data = $sina->call('users/show', "uid={$sina->openid()}");

        if ($data['error_code'] == 0) {
            $userInfo['type'] = 'SINA';
            $userInfo['name'] = $data['name'];
            $userInfo['nick'] = $data['screen_name'];
            $userInfo['head'] = $data['avatar_large'];
            return $userInfo;
        } else {
            throw_exception("获取新浪微博用户信息失败：{$data['error']}");
        }
    }

    /**
     * 第三方登录完善昵称填写（n昵称为空时才操作）
     * @author liangzk <liangzk@qc.com>
     * DataTime 2016-08-04
     */
    public function complete_nick()
    {
        $user_id = is_login();
        $userData = M('FrontUser')->where(['id'=>$user_id])->field('nick_name,head')->find();
        if (IS_POST)
        {
            if ($userData['nick_name']) $this->error('无法操作',U('UserInfo/index'));
            if (M('FrontUser')->where(['id'=>$user_id])->save(['nick_name'=>I('nick_name')]))
            {
                $this->success('操作成功');
            }
            else
            {
                 $this->success('操作失败');
            }

        }
        else
        {
            $this->assign('head_img',frontUserFace($userData['head']));
            $this->assign('is_complete','is_complete');
            $this->display();
        }
    }
}