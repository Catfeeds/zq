<?php

/**
 * 新闻
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;
use Vendor\ThinkSDK\ThinkOauth;

class UserController extends CommonController {

    protected function _initialize() {
        parent::_initialize();
        $user = session('user_auth');
/*
		if (!empty($user))
		{
			//判断昵称是否为空
            if (!M('FrontUser')->master(true)->where(['id'=>$user['id']])->getField('nick_name') && ACTION_NAME != 'nickname')
			{
				$this->redirect('User/nickname');
				exit;
			}
			if (empty($user['nick_name']))
			{
				D('FrontUser')->autoLogin($user['id']);
			}
			$user = session('user_auth');
			$this->assign('user_auth', $user);
		}

        if(ACTION_NAME != 'nickname') {
            //注册赠送礼包
            $gift1 = M('GiftsConf')->field('id, name, before_img, after_img')
                ->where(['type' => 1, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                ->order(' id desc ')->limit(1)->find();
            if ($gift1) {
                $gift1['after_img'] = (string)Tool::imagesReplace($gift1['after_img']);
                $gift1['open'] = (string)S("gift1_open_{$user['id']}_{$gift1['id']}");//弹框标志
                $gift1['close'] = (string)S("gift1_close_{$user['id']}_{$gift1['id']}");
            } else {
                $gift1['after_img'] = '';
                $gift1['open'] = '';
                $gift1['close'] = '';
            }
            $this->assign('gift1', $gift1);

            //活动赠送礼包
            $gift2 = M('GiftsConf')->field('id, name, before_img, after_img')
                ->where(['type' => 3, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                ->order(' id desc ')->limit(1)->find();

            if ($gift2) {
                $gift2['before_img'] = (string)Tool::imagesReplace($gift2['before_img']);
                $gift2['after_img'] = (string)Tool::imagesReplace($gift2['after_img']);
                $gift2['close'] = (string)S("gift2_close_{$user['id']}_{$gift2['id']}");
            } else {
                $gift2['before_img'] = '';
                $gift2['after_img'] = '';
                $gift2['close'] = '';
            }

            $this->assign('gift2', $gift2);

            //注册后要弹活动的框
            $gift2_frame = I('gift2_frame', '', 'strval');
            $this->assign('gift2_frame', $gift2_frame);
        }
*/
        C('HTTP_CACHE_CONTROL','no-cache,no-store');
    }

    public function index() {
        $user = session('user_auth');
        if ($user) {
            $info = M('FrontUser')->field('point,coin,coin+unable_coin count_coin,lv')->where('id=' . $user['id'])->find();
            $info['follow'] = M('FollowUser')->where('follow_id=' . $user['id'])->count();
            $new_message = $this->getNewMsg();
            $this->assign('new_message', $new_message);
            $this->assign('info', $info);
            $this->assign('user_auth', $user);


            $where1['user_id']   = $user['id'];
            $where1['status']    = 1;
            $where1['is_use']    = 0;
            $where1['over_time'] = ['gt', NOW_TIME];
            $num1  = M('TicketLog')->where($where1)->count();//可用总数
            $this->assign('available_num', $num1);
        }
        $url=cookie('userUrl');
        if(!$url){
            $url=U('Index/index');
        }
        $this->assign('userUrl', $url);
        cookie('pageUrl',__SELF__);
        cookie('redirectUrl', __SELF__);
        $this->display();
    }

    public function login() {
        if (session('user_auth')) {
			redirect(U('User/index'));
        }
        $code=I('get.channel_code','');
        if($code){
            cookie('login_code',$code);
        }
        $this->assign('title', '登录');
        $this->display();
    }

    public function logout() {
        session('user_auth', null);
        session_destroy();
        redirect(U('User/index'));
    }

    public function register() {
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
        if (IS_POST) {
            //注册防刷
            if(!D('FrontUser')->checkReg(I('deviceID'))) $this->error('该设备注册次数过多!');
            $mobile = I('post.mobile', '');
            $pwd = I('post.pwd', '');
            $code = I('post.code', '');
            if (!fn_is_mobile($mobile)) {
                $this->error('请输入正确的手机号码!');
            }
            if (!fn_is_pwd($pwd)) {
                $this->error('密码为6-15位数字或者字母');
            }
            if (empty($code)) {
                $this->error('请输入验证码!');
            }
            $isTrue = self::checkMobileVerify($code, $mobile);
            if (!$isTrue) {
                $this->error('验证码错误或已超时');
            }
            /* 图文验证码 */
            $ifVerify = checkVerify(I('post.verify', ''));
            if(!$ifVerify){
                $this->error('图文验证码错误或已超时');
                exit;
            }
            $UserArray = array(
                'username' => $mobile,
                'password' => md5($pwd),
            );
            session('reg_user', $UserArray, C('verifyCodeTime')/60);
            $this->success('', U('User/infor'));
        } else {
            $this->assign('title', '注册账号');
            $this->display();
        }
    }

    public function infor() {
        $verify = S(cookie('verifyCode'));
        $verifySign = cookie('verifySign');
        if (!$verify || !$verifySign || $verify['mobile'] != $verifySign['mobile'] || $verify['rank'] != $verifySign['rank']) {
            $this->error('验证码信息已经过时，请重新验证手机！', redirect('register'));
        }
        $user = session('reg_user');
        if (!$user) {
            $this->error('您已超时,请重新注册!', U('User/register'));
        }
        if (IS_POST) {
            if (!$user) {
                $this->error('您已超时,请重新注册!', U('User/register'));
            }
            //注册防刷
            if(!D('FrontUser')->checkReg(I('deviceID'))) $this->error('该设备注册次数过多!');
            $username = I('post.username', '');
            $isNickname = M('frontUser')->master(true)->where(array('nick_name'=>$username))->find();
            if($isNickname){
                $this->error('该用户名已经存在');
            }
            $length = mb_strlen($username, 'utf-8');
            if ($length < 2 || $length > 10) {
                $this->error('用户昵称必须大于2位,小于10位');
            }
            $isTrue = matchFilterWords('nickFilter', $username);
            if (!$isTrue) {
                $this->error('您的名字不合法,请重新输入!');
            }
            $code=cookie('login_code');
            if($code==''){
                $code='m';
            }

            $ip = get_client_ip();
            $user['nick_name'] = $username;
            $user['reg_time']  = time();
            $user['reg_ip']    = $ip;
            $user['platform']  = 4;
            $user['channel_code'] = $code;
            $user['mac_addr'] = I('deviceID');//注册mac地址
            $FrontUserId = M('FrontUser')->add($user);
            if ($FrontUserId) {
                $user['head'] = frontUserFace('');
                /* 记录登录SESSION和COOKIES */
                $auth = array(
                    'id' => $FrontUserId,
                    'username' => $user['username'],
                    'nick_name' => $user['nick_name'],
                    'head'=>$user['head'],
                );
                session('user_auth', $auth);
                 M('FrontUser')->where(array('id' => $FrontUserId))->save(['login_count' => ['exp', 'login_count+1'], 'login_time' => time(), 'last_ip' => $ip, 'session_id' => session_id()]);
        
                //D('FrontUser')->autoLogin($FrontUserId);
                //成功后删除验证码
                S(cookie('verifyCode'), null);
                cookie('verifyCode', null);
                cookie('verifySign', null);
                cookie('login_code', null);
                $url = cookie('redirectUrl');
                if (!$url) {
                    $url = U('User/index');
                }
				cookie('redirectUrl',null);

                //注册赠送大礼包，活动时间内
                D('FrontUser')->giftBag($FrontUserId, 4, 1, 3);

                //保存注册赠送标志
                $gift1 = M('GiftsConf')->where(['type' => 1, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                         ->order(' id desc ')->limit(1)->getField('id');

                if($gift1 && !S("gift1_open_{$FrontUserId}_{$gift1}")){
                    S("gift1_open_{$FrontUserId}_{$gift1}", 1, time()+3600*24*30);//弹框标志
                }

                $this->success('注册成功！',$url);
            } else {
                $this->error('注册失败,请重试!', U('User/login'));
            }
        } else {
            $this->display();
        }
    }

    public function find_pwd() {
        $token = md5(uniqid(rand(), TRUE));
        session('token',$token);
        $this->assign('token',$token);
        if (IS_POST) {
            $mobile = I('post.mobile', '');
            $code = I('post.code', '');
            /* 检测验证码 */
            $isTrue = self::checkMobileVerify($code, $mobile);
            if ($isTrue) {
                $this->success('验证成功！', U('User/set_pwd'));
            } else {
                $this->error('验证码错误或已超时');
            }
        } else {
            $this->assign('title', '忘记密码');
            $this->display();
        }
    }

    public function set_pwd() {
        $verify = S(cookie('verifyCode'));
        $verifySign = cookie('verifySign');
        if (!$verify || !$verifySign || $verify['mobile'] != $verifySign['mobile'] || $verify['rank'] != $verifySign['rank']) {
            $this->error('验证码信息已经过时，请重新验证手机！', redirect(U('User/find_pwd')));
        }
        if (IS_POST) {
            /* 检测数据 */
            $pwd = I('pwd', '');
            if (!fn_is_pwd($pwd)) {
                $this->error('密码为6-15位数字或者字母!');
            }
            $UserArray = array(
                'password' => md5($pwd),
            );
            $rs = M('frontUser')->where(array('username' => $verifySign['mobile']))->save($UserArray);
            if (!is_bool($rs)) {
                $rs = true;
            }
            if ($rs) {
                //成功后删除验证码
                S(cookie('verifyCode'), null);
                cookie('verifyCode', null);
                cookie('verifySign', null);
                $this->success('修改成功！', U('User/login'));
            } else {
                $this->error('修改失败！');
            }
        } else {
            $this->assign('title', '修改密码');
            $this->display();
        }
    }

    public function update_pwd() {
        $user=session('user_auth');
        if (!$user) {
            redirect(U('login'));
        }
        $rsl=M('FrontUser')->field('alipay_id,bank_card_id,bank_extract_pwd')->where('id='.$user['id'])->find();
        $pwd=0;
        if($rsl['bank_extract_pwd'] && ($rsl['alipay_id'] || $rsl['bank_card_id'])){
            $pwd=1;
        }
        $this->assign('extrpwd', $pwd);
        $this->assign('mobile', $user['username']);
        $this->assign('title', '修改密码');
        $this->display();
    }

    /* 登录 */

    public function dologin() {
        $mobile = I("post.mobile", "");
        $pwd = I("post.pwd", "");
        if (empty($mobile) || empty($pwd)) {
            $this->error("请检查并填写必填项");
        }
        if(checkShieldIp()){
            $this->error('登录失败，请联系管理员');
        }
        $rsl = D('FrontUser')->login($mobile, $pwd);
        switch ($rsl) {
            case -1:
                $this->error('用户不存在!');
                break;
            case -2:
                $this->error('密码错误!');
                break;
            case -3:
                $this->error('用户已被禁用!');
                break;
        }
		$url = cookie('redirectUrl');
		if (!$url)
		{
			$url = U("User/index");
		}
		cookie('redirectUrl',null);
        $this->success('登录成功!', $url);
    }

    public function doforget() {
        $mobile = I('post.mobile', '');
        $pwd = I('post.pwd', '');
        $code = I('post.code', '');
        if (!fn_is_mobile($mobile)) {
            $this->error('请输入正确的手机号码!');
        }
        if (!fn_is_pwd($mobile)) {
            $this->error('密码为6-15位数字或者字母');
        }
        if (empty($code)) {
            $this->error('请输入验证码!');
        }
        $isTrue = self::checkMobileVerify($code, $mobile);
        if (!$isTrue) {
            $this->error('验证码错误或已超时');
        }
        $rsl = D('FrontUser')->findPwd($mobile, $pwd);
        switch ($rsl) {
            case -1:
                $this->error('用户不存在!');
                break;
            case -3:
                $this->error('用户已被禁用!');
                break;
            case -4:
                $this->error('修改的密码不能与原密码相同!');
                break;
            case -2:
                $this->error('修改失败!');
                break;
        }
        $this->success('修改成功!', U('User/login'));
    }

    /* 修改密码 */

    public function dopwd() {
        $user = session('user_auth');
        if (!$user) {
            $this->error('请先登录', U('User/login'));
        }
        if (!$user['username']) {
            $this->error('请完善手机号码信息', U('User/basic'));
        }
        $old_pwd = I('post.old_pwd', '');
        $pwd = I('post.pwd', '');
        $type = I('post.type', 0,'intval');
        $code = I('post.code','');
		
        if ($type === 0)
		{
			if (empty($old_pwd) || empty($pwd) || !fn_is_pwd($old_pwd) || !fn_is_pwd($pwd)) {
				$this->error('密码为6-15位数字或者字母!');
			}
		}
		else
		{
			if (empty($old_pwd) || empty($pwd) || ! preg_match('/^\d{6}$/',$old_pwd) || ! preg_match('/^\d{6}$/',$pwd)) {
				$this->error('请输入6位数字密码!');
			}
		}
        $isTrue = self::checkMobileVerify($code, $user['username']);
        if (!$isTrue) {
            $this->error('验证码错误或已超时');
        }
        $rsl = D('FrontUser')->updatePwd($user['id'], $old_pwd, $pwd,$type);
        switch ($rsl) {
            case -5:
                $this->error('原密码不正确!');
                break;
            case -1:
                $this->error('用户不存在!');
                break;
            case -3:
                $this->error('用户已被禁用!');
                break;
            case -4:
                $this->error('修改的密码不能与原密码相同!');
                break;
            case -2:
                $this->error('修改失败!');
                break;
        }
        if($type){
            $this->success('修改成功!',U('User/basic'));
        }else{
            session('user_auth', null);
            $this->success('修改成功,请重新登录!', U('User/login'));
        }
    }

    //登录地址
    public function sdk_login($type = null) {
        empty($type) && $this->error('参数错误');
        if(checkShieldIp()){
            $this->error('第三方登录失败，请联系管理员','/');
        }
        //加载ThinkOauth类并实例化一个对象
        $sns = ThinkOauth::getInstance($type);
        /* dump($type);
          dump($sns->getRequestCodeURL());
          exit; */
        cookie('sdk_sign', 'm', array('domain' => '.' . DOMAIN));
        //跳转到授权页面
        redirect($sns->getRequestCodeURL());
    }

   
	/**
	 *Liangzk 《Liangzk@qc.com》
	 * DateTime 2017-02-15
	 *  第三方登录页面注册
	 */
    public function tpperfect()
	{
		cookie('sdk_sign', null, array('domain' => '.' . DOMAIN));
		$token = cookie('loginToken');
		if (empty($token) || !in_array($token['type'],['qq','weixin','sina']))
		{
			//请求超时，请重试！
			$this->redirect('User/login');
			exit;
		}
		cookie('loginToken',null);
		
		switch ($token['type']) {
			case 'qq':
				$UserArray['qq_unionid'] = $token['openid'];
				$FrontUserId = M("FrontUser")->where(['qq_unionid'=>$token['openid']])->getField('id');

				break;
			case 'weixin':
				$UserArray['weixin_unionid'] = $token['unionid'];
				$FrontUserId = M("FrontUser")->where(['weixin_unionid'=>$token['unionid']])->getField('id');
				break;
			case 'sina':
				$UserArray['sina_unionid'] = $token['openid'];
				$FrontUserId = M("FrontUser")->where(['sina_unionid'=>$token['openid']])->getField('id');
				break;
		}
		
		//判断是否有认证过
		if (!empty($FrontUserId))
		{
			//自动登录
			D('FrontUser')->autoLogin($FrontUserId);
			if (!M('FrontUser')->where(['id'=>$FrontUserId])->getField('nick_name'))
			{
				$this->redirect('User/nickname');
				exit;
			}
			///已经绑定过
			$url = cookie('redirectUrl');
			if (!$url)
			{
				$url = U('User/index');
			}
			cookie('redirectUrl',null);
			$this->redirect($url);
			exit;
		}
		
		$ip = get_client_ip();
		$channel_code = cookie('login_code');
		$UserArray['reg_time']  = time();
		$UserArray['reg_ip']    = $ip;
		$UserArray['platform']  = 4;
		$UserArray['channel_code'] = $channel_code == '' ? 'm' : $channel_code;
		
		$FrontUserId = M('frontUser')->add($UserArray);
		if ($FrontUserId !== false) {
			//登录
			D('FrontUser')->autoLogin($FrontUserId);
			//注册成功，请修改昵称
			$this->redirect('User/nickname');
			exit;
		} else {
			///注册失败,请重试
			$this->redirect('User/login');
			exit;
		}
	}

	/**
	 *Liangzk 《Liangzk@qc.com》
	 * DateTime 2017-02-15
	 * 绑定手机号码
	 */
    public function bindingPhone()
	{
		if (IS_POST)
		{
			$userId = is_login();
			if(empty($userId))
				$this->error('请登录再操作！');
			
			if (M('FrontUser')->where(['id'=>$userId])->getField('username'))
				$this->error('操作失败，不能绑定！');
			
			$mobile = I('mobile', '');
			$pwd = I('pwd', '');
			$code = I('code', '','string');
			
			if (!fn_is_mobile($mobile))
				$this->error('请输入正确的手机号码！');

			if (M('FrontUser')->where(['username'=>$mobile])->getField('id'))
				$this->error('该手机号已被绑定！');
			
			if ( empty($pwd) || !fn_is_pwd($pwd))
				$this->error('密码为6-15位数字或者字母！');
			
			if ($code === '')
				$this->error('请输入验证码！');
			
			$isTrue = self::checkMobileVerify($code, $mobile);
			if (!$isTrue) {
				$this->error('验证码错误或已超时！');
			}
			
			
			$res = M('FrontUser')->where(['id'=>$userId])->save(['username'=>$mobile,'password'=>md5($pwd)]);
			
			if ($res === false)
			{
				$this->error('绑定失败，请重新操作！');
			}
			//自动登录
			D('FrontUser')->autoLogin($userId);
			$url = cookie('redirectUrl');
			if (!$url)
			{
				$url = U('User/index');
			}
			cookie('redirectUrl',null);
			$this->success('绑定成功！', $url);
			
		}
		$this->assign('title', '绑定账号');
		$this->display();
	}
	/**
	 *Liangzk 《Liangzk@qc.com》
	 * DateTime 2017-02-15
	 * 修改昵称
	 */
    public function nickname()
	{
		if (IS_POST)
		{
			$userId = is_login();
			if (empty($userId))
			{
				$this->ajaxReturn(['start'=>0,'msg'=>'请登录！']);
			}
			
			$nickname = I('nickname','','string');
			if ($nickname === '')
			{
				$this->ajaxReturn(['start'=>0,'msg'=>'用户昵称必须大于2位,小于10位！']);
			}
			
			$length = mb_strlen($nickname, 'utf-8');
			if ($length < 2 || $length > 10)
			{
				$this->ajaxReturn(['start'=>0,'msg'=>'用户昵称必须大于2位,小于10位！']);
			}
			
			if (!matchFilterWords('nickFilter', $nickname)) {
				$this->ajaxReturn(['start'=>0,'msg'=>'您的名字不合法,请重新输入！']);
			}
			
			if (M("FrontUser")->where(['id'=>$userId])->getField('nick_name'))
			{
				$this->ajaxReturn(['start'=>0,'msg'=>'不能修改昵称！']);
			}
			
			if(M('FrontUser')->where(['nick_name'=>$nickname])->getField('nick_name'))
			{
				$this->ajaxReturn(['start'=>0,'msg'=>'该用户名已经存在！']);
			}
			
			$res = M('FrontUser')->where(['id'=>$userId])->save(['nick_name'=>$nickname]);
			if ($res === false)
			{
				$this->ajaxReturn(['start'=>0,'msg'=>'修改失败，请重新操作！']);
			}
			
			$url = cookie('redirectUrl');
			if (!$url)
			{
				$url = U('User/index');
			}
			cookie('redirectUrl',null);
			$this->success('修改成功！', $url);
		}
		$this->assign('title','修改昵称');
		$this->display();
	}

    /* 发送手机验证码 */

    public function sendMobileMsg() {
        $token   = I('token');
        if($token != session('token') || !IS_AJAX){
            $this->error('发送失败，请稍后重试！');
        }
        if(checkShieldIp()){
            $this->error('注册失败，请联系管理员' );
        }
        $mobile = I('mobile');
        if (!fn_is_mobile($mobile)) {
            $this->error('请输入正确的手机号码!');
        }
        $isChangePass = I('change');
        $isMobile = M('frontUser')->master(true)->where(array('username' => $mobile))->find();
        //判断注册还是修改密码
        if (empty($isChangePass)) {
            $type = 'registe';
            //是否已注册
            if (!empty($isMobile)) {
                $this->error('该手机号码已经注册，不能再注册！');
                exit;
            }
        } else if ($isChangePass == 'bindPhone') {
        	//绑定手机号
			//已注册
			if (!empty($isMobile)) {
				$this->error('该手机号码已经被绑定！');
				exit;
			}
			$type = 'bindPhone';
			
		} else {
			$type = 'editPwd';
            //是否未注册
            if (empty($isMobile)) {
                $this->error('该手机号码未注册！');
                exit;
            }
            if ($isMobile['status'] != 1) {
                $this->error('该手机号码已被禁用！');
                exit;
            }
        }
        $_POST['platform'] = 4;
        $result = sendCode($mobile, $type);
        if ($result == '-1') {
            //已经发送过,需等待60秒
            $this->error('您已经发送过验证码,请等待' . C('reSendCodeTime') . '秒后重试!');
            exit;
        }
        if ($result) {
            cookie('verifyCode', $result['token'], C('verifyCodeTime'));  //存返回值
            if($result['mobileSMS']==3){
                $msg='验证码将以电话语音形式通知您,请注意接听！';
            }else{
                $msg='验证码将以短信形式通知您,请在' . (C('verifyCodeTime') / 60) . '分钟内完成验证注册！';
            }
            //发送成功
            $this->success($msg);
        } else {
            //发送失败
            $this->error('你发送太频繁了，请稍后重试！');
        }
    }

    /**
     * 校验手机验证码
     *
     * @param string $verifyNum 	#待验证的验证码
     *
     * @return  #
     */
    public function checkMobileVerify($verifyNum, $mobile) {
        //获取验证码
        $verify = S(cookie('verifyCode'));
        if (empty($verify)) {
            //验证码超时
            return false;
        } elseif ($verify['rank'] != $verifyNum || $verify['mobile'] != $mobile) {
            //验证码错误
            return false;
        } else {
            //验证通过
            cookie('verifySign', ['mobile' => $mobile, 'rank' => $verifyNum]);
            return true;
        }
    }

    //我的关注-动态
    public function fcous_dt() {

        $user_id = is_login();
        if ($user_id < 1) {
            redirect(U('login'));
			exit;
        }
        cookie('pageUrl', __SELF__);
        cookie('redirectUrl', __SELF__);

        $page = I('page', 1, 'intval');//页码
		
        $list = $this->getFcousList($user_id,$page);

		if (IS_AJAX && IS_POST)
		{
			$this->ajaxReturn(['status'=>1,'list'=>$list,'is_login'=>$user_id]);
		}
		
		
        $this->assign('list', $list);
        $this->assign('title', '我的关注');
        $this->display();
    }

    //获取关注用户推荐
    public function getFcousList($user_id,$page){
        $followId = M('FollowUser')->where(['user_id' => $user_id])->getField('follow_id', true); //关注用户的id数组
        if(empty($followId)) return [];
        $list = D('GambleHall')->getGambleList($followId, 0, $page); //关注用户的推荐记录
        if(empty($list)) return [];

        //获取关注的用户ID
        $userIdArr = array_unique(get_arr_column($list,'user_id'));
        $userInfo = M('FrontUser')->where(['id' => ['IN',$userIdArr]])->field('id,nick_name,lv,lv_bet,head')->select();
    
        //是否已被查看
        $gambleIdArr  = get_arr_column($list,'gamble_id');
        $quizLogRes = M('QuizLog')
                    ->where(['cover_id' => ['IN',$userIdArr],'gamble_id' => ['IN',$gambleIdArr],'game_type' => 1])
                    ->field('gamble_id')
                    ->select();
        
        foreach ($list as $k => &$v)
        {
            
            //合并用户信息
            foreach ($userInfo as $key => $value)
            {
                if ($value['id'] === $v['user_id'])
                {
                    $list[$k]['nick_name']    = $value['nick_name'];
                    $list[$k]['lv']           = $value['lv'];
                    $list[$k]['lv_bet']           = $value['lv_bet'];
                    $list[$k]['head']         = frontUserFace($value['head']);
                }
            }
            
            $list[$k]['gDate']        = date('m/d',strtotime($v['game_date'])).' '.$v['game_time'];
            $list[$k]['create_time']  = date('m-d H:i', $v['create_time']);
            $list[$k]['union_name']  = $v['union_name'][0];
            $list[$k]['home_team_name']  = $v['home_team_name'][0];
            $list[$k]['away_team_name']  = $v['away_team_name'][0];
    
            $gambleType = in_array($v['play_type'],[1,-1]) ? 1 : 2;//区分竞彩还是亚盘---1：亚盘 2：竞彩
            $list[$k]['gambleType'] = $gambleType;
            
            //周胜率---分亚盘、竞彩
            $list[$k]['weekPercnet']  = D('GambleHall')->CountWinrate($v['user_id'],1,1,false,false,0,$gambleType);
            //连胜---分亚盘、竞彩
            $Winning = D('GambleHall')->getWinning($v['user_id'],1,0,$gambleType);
            $list[$k]['curr_victs']   = $Winning['curr_victs'];
            $list[$k]['tenGambleRate']= $Winning['tenGambleRate'];        //近十中几
            //是否已经购买查看过
            foreach ($quizLogRes as $key => $value)
            {
                if ($v['gamble_id'] == $value['gamble_id'])
                {
                    $list[$k]['is_trade'] = 1;
                    break;
                }
            }
          
            if ($v['result'] != 0) $list[$k]['is_trade'] = 1;
			
            //获取赛事ID
            if (in_array($v['play_type'],[2,-2]))
            {
                $gameIdArr[$k] = $v['game_id'];
            }
        }
        $betCodeArr = M('GameFbinfo')->where(['game_id'=>['IN',$gameIdArr]])->field('game_id,bet_code')->select();
        foreach ($list as $key => $value)
        {
            foreach ($betCodeArr as $k => $v)
            {
                if ($v['game_id'] == $value['game_id'])
                {
                    $list[$key]['bet_code'] = $v['bet_code'];
                    break;
                }
            }
        }
        if (!empty($list)) $list = HandleGamble($list);//格式化结果
        //当没有查看推荐时筛选掉盘口、玩法等
        foreach ($list as $k => $v)
        {
            if ($v['is_trade'] != 1 && $v['result'] == 0)
            {
				$list[$k]['Answer'] = null;
				$list[$k]['handcp'] = null;
				$list[$k]['chose_side'] = null;
				$list[$k]['odds'] = null;
				$list[$k]['desc'] = null;
            }
        }
        return $list;
    }
    //我的关注-用户
    public function fcous_user() {
        $user = session('user_auth');
        if ($user) {
            $page = I('param.page', 1, 'intval');
            $pageNum = 10;
            $list = M('FollowUser f')->field('f.follow_id user_id,u.nick_name,u.lv,u.lv_bet,u.head face,u.descript')
                    ->join('__FRONT_USER__ u ON u.id = f.follow_id')
                    ->where(['f.user_id' => $user['id']])
                    ->page($page . ',' . $pageNum)
                    ->order('f.id desc')
                    ->select();
            foreach ($list as $k => $v) {
                $list[$k]['face'] = frontUserFace($v['face']);
            }
            if (IS_AJAX) {
                $this->success($list);
            } else {
                $this->assign('list', $list);
            }
        }
        cookie('pageUrl','/User/index.html');
		cookie('redirectUrl','/User/index.html');
        $this->assign('title', '我的关注');
        $this->display();
    }

    public function cancel_fcous() {
        $user = session('user_auth');
        if (!$user) {
            $this->error('请先登录!');
        }
        $id = I('post.id', 0, 'intval');
        if ($id < 1) {
            $this->error('参数有误!');
        }
        $res = M('FollowUser')->where(array('user_id' => $user['id'], 'follow_id' => $id))->delete();
        if ($res) {
            $this->success('取消成功!');
        } else {
            $this->error('取消失败,请稍后重试!');
        }
    }

    //我的推荐
    public function myquiz() {
        $user = session('user_auth');
        if (!$user) {
            redirect(U('login'));
			exit;
        }
        $type = I('param.type', 0, 'intval');
		
        $userid = $user['id'];
        $page = I('param.page', 1, 'intval');
	
		
		//竞彩
		$jcUserInfo  = D('GambleHall')->getWinning($userid,1,0,2,0); //推荐统计信息
		//亚盘--分玩法
		$userInfo = D('GambleHall')->getWinning($userid, 1,0,1,0 ); //推荐统计信息
		
		//j竞彩
		$jcUserInfo['jcWeekPercnet'] = D('GambleHall')->CountWinrate($userid, 1, 1,false,false,0,2); //周
		$jcUserInfo['jcMonthPercnet'] = D('GambleHall')->CountWinrate($userid, 1, 2,false,false,0,2);//月
		$jcUserInfo['jcSeasonPercnet'] = D('GambleHall')->CountWinrate($userid, 1, 3,false,false,0,2);//季
		
		//亚盘
        $userInfo['weekPercnet'] = D('GambleHall')->CountWinrate($userid, 1, 1);
        $userInfo['monthPercnet'] = D('GambleHall')->CountWinrate($userid, 1, 2);
        $userInfo['seasonPercnet'] = D('GambleHall')->CountWinrate($userid, 1, 3);
		$lvArr = M('FrontUser')->where(['id'=>$userid])->field('lv,lv_bet')->find();
		if ($type === 2 || $type === -2)//竞彩
		{
			$gambleList = D('GambleHall')->getGambleList($userid, 0, $page,0,2); //推荐记录
		}
		else if($type === 1 || $type === -1)//亚盘--分玩法
		{
			$gambleList = D('GambleHall')->getGambleList($userid, $type, $page,0,1); //推荐记录
		}
		else
		{
			$gambleList = D('GambleHall')->getGambleList($userid, 0, $page,0,0); //推荐记录
		}
        
		//获取推荐ID
		$gambleIdArr = get_arr_column($gambleList,'gamble_id');
		//获取推荐记录
		$tradeCountArr = M('QuizLog')
			->where(['gamble_id' => ['IN',$gambleIdArr]])
			->field('gamble_id,count(id) as tradeCount')
			->group('gamble_id')
			->select();
		
		//获取竞彩标志码
		$gameIdArr = get_arr_column($gambleList,'game_id');
		$betCodeArr = M('GameFbinfo')->where(['game_id'=>['IN',$gameIdArr]])->field('game_id,bet_code')->select();
		
        foreach ($gambleList as $k => &$v) {
            $gambleList[$k]['tradeCount'] = 0;
			foreach ($tradeCountArr as $key => $value)
			{
				if ($v['gamble_id'] == $value['gamble_id'])
				{
					$gambleList[$k]['tradeCount'] = $value['tradeCount'];//推荐记录
					break;
				}
			}
            $gambleList[$k]['day'] = date('m-d', strtotime($v['game_date']));
            if ($v['play_type'] == 1) {
                $v['handcp'] = $v['chose_side'] * -1 * $v['handcp'];
                if ($v['handcp'] > 0) {
                    $v['handcp'] = '+' . $v['handcp'];
                }
            }
            
            //获取竞彩标志码
			foreach ($betCodeArr as $key => $value)
			{
				if ($v['game_id'] == $value['game_id'])
				{
					$v['bet_code'] = $value['bet_code'];
					break;
				}
			}
        }
        
        if (IS_AJAX) {
            $this->success($gambleList);
        } else {
            $user = A("Home/Video")->author_info($userid);
            $this->assign('user', $user);
            $this->assign('lvArr', $lvArr);
            $this->assign('userInfo', $userInfo);
            $this->assign('jcUserInfo', $jcUserInfo);
            $this->assign('list', $gambleList);
            $this->assign('type', $type);
            $this->assign('title', '我的推荐');
            $this->display();
        }
    }

    //我的购买
    public function mybuy() {
        $user = session('user_auth');
        if (!$user) {
            redirect(U('login'));
        }
        $page = I('page',1,'int');
        $pageNum = 10;
        $type = I('type',1,'int');
		$gambleType = $type === 2 || $type === -2 ? 2 : 1;//1：亚盘 2：竞彩
		
		$this->assign('gambleType',$gambleType);
		$palySql = $gambleType === 2 ? "play_type in ('2','-2')" : "play_type = ".$type;
        //旧表和新表的联表
        $subQuery = M('Gamble')->field('id')
                    ->union('SELECT id from qc_gamble_reset WHERE  '.$palySql, true)
                    ->where(['_string'=>$palySql])->buildSql();

        $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id'])
                ->join('RIGHT JOIN  '.$subQuery.' gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$user['id']])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();
		//获取用户ID
		$userIdArr = get_arr_column($list,'user_id');
		//获取用户信息
		$userInfoArr = M('FrontUser')
					->where(['id'=>['IN',$userIdArr]])
					->field('id,lv,lv_bet,nick_name,head')
					->select();
		
        foreach ($list as $k => $v)
        {
            foreach ($userInfoArr as $key => $value)
			{
				if ($v['user_id'] === $value['id'])
				{
					$list[$k]['nick_name']    = $value['nick_name'];
					$list[$k]['lv']           = $value['lv'];
					$list[$k]['lv_bet']           = $value['lv_bet'];
					$list[$k]['face']         = frontUserFace($value['head']);
					break;
				}
			}
            
			$Winning = D('GambleHall')->getWinning($v['user_id'],1,0,$gambleType);        //连胜
            $list[$k]['curr_victs']   = $Winning['curr_victs'];
            $list[$k]['tenGambleRate']= $Winning['tenGambleRate'];        //近十中几

            $gamble = D('GambleHall')->getGambleInfo($v['gamble_id']);
            $list[$k]['play_type']      = $gamble['play_type'];
            $list[$k]['chose_side']     = $gamble['chose_side'];
            $list[$k]['handcp']         = $gamble['handcp'];
            $list[$k]['odds']           = $gamble['odds'];
            $list[$k]['result']         = $gamble['result'];
            $list[$k]['tradeCoin']      = $gamble['tradeCoin'];
            $list[$k]['desc']           = $gamble['desc'];
            $list[$k]['union_name']     = implode(',', $gamble['union_name']);
            $list[$k]['home_team_name'] = implode(',', $gamble['home_team_name']);
            $list[$k]['away_team_name'] = implode(',', $gamble['away_team_name']);
            $list[$k]['score']          = $gamble['score'];
            $list[$k]['union_color']    = $gamble['union_color'];

            $list[$k]['day']          = date('Y-m-d', strtotime($gamble['game_date'])).' '.$gamble['game_time'];
        }
		//获取竞彩的标志码
		if ($gambleType === 2)
		{
			$gambleIdArr = get_arr_column($list,'gamble_id');
			
			$betCodeArr = M('GameFbinfo gf')
					->join('INNER JOIN qc_gamble g ON gf.game_id = g.game_id')
					->where(['g.id'=>['in',$gambleIdArr],'gf.is_betting'=>1])
					->field('g.id,gf.bet_code')
					->select();
			
		}
		foreach ($list as $key => $value)
		{
			if ($gambleType === 2)
			{
				foreach ($betCodeArr as $k => $v)
				{
					if ($value['gamble_id'] == $v['id'])
					{
						$list[$key]['bet_code'] = $v['bet_code'];
					}
				}
			}
			else
			{
				$list[$key]['bet_code'] = null;
			}
		}
        $list = HandleGamble($list);
		
        if (IS_AJAX) {
            $this->success($list);
            die;
        }
	    switch(I('type'))
        {
            case -1:
                $url = '/Guess/new_put';
                break;
            case 2:
                $url = '/Guess/new_put';
                break;
            default:
                $url = '/Guess/new_put';
        }
        $this->assign('url',$url);
		cookie('pageUrl', __SELF__);
		$this->assign('type', $type);
		$this->assign('list', $list);
		$this->assign('title', '我的购买');
		$this->display();
    }

    public function set() {
        $this->assign('title', '功能设置');
        $this->display();
    }

    public function drawal_idcard() {
        $user_id = is_login();
        $type=I('get.type','');
        if (IS_AJAX) {
            if (!$user_id)
                $this->error('请先登录!');
            $id_card = I('post.id_card', '');
            $true_name = I('post.true_name', '');
            if (empty($id_card) || empty($true_name)) {
                $this->error('请填写完整信息!');
            }
            if (!preg_match("/^[\x{4e00}-\x{9fa5}]{2,7}$/u", $true_name)) {
                $this->error('请输入真实的姓名!');
            }
            if (!preg_match("/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/", $id_card)) {
                $this->error('请输入正确的身份证号码!');
            }
            $isIdentfy = M('frontUser')->where(array('identfy' => $id_card))->find();
            if ($isIdentfy) {
                $this->error('此身份证号已存在!');
            }
            $rs = M('frontUser')->where(array('id' => $user_id))->save(array(
                'true_name' => $true_name,
                'identfy' => $id_card,
            ));
            if($type=='id'){
                $url=U('User/basic');
            }else{
                $url=U('User/drawal_bank');
            }
            if ($rs) {
                $this->success('保存成功', $url);
            } else {
                $this->error('提交失败!', $user_id);
            }
        } else {
            if (!$user_id)
                redirect(U('login'));

            $user = M('FrontUser')->field('identfy,true_name')->where('id=' . $user_id)->find();
            if ($user['true_name'] && $user['identfy']) {
                $user['true_name'] = substr_replace($user['true_name'], '**', 3, 6);
                $user['identfy'] = substr_replace($user['identfy'], '**********', 4, 10);
                $this->assign('is_user', $user);
            }
            $this->display();
        }
    }

    public function drawal_bank() {
        $user_id = is_login();
        //是否ajax请求绑定银行卡
        if (IS_AJAX) {
            if (!$user_id)
                $this->error('请先登录!');
            $post = I('post.');
            if (empty($post['password']) || empty($post['true_name'])) {
                $this->error('请输入写姓名和密码!');
            }
            if($post['type']==1){
                if($post['alipay_id']==''){
                    $this->error('请输入支付宝账号!');
                }
                if(!fn_is_email($post['alipay_id']) && !fn_is_mobile($post['alipay_id'])){
                    $this->error('请输入正确的支付宝账号!');
                }
            }else{
                if(empty($post['bank']) || empty($post['bank_card']) || empty($post['province']) || empty($post['city'])){
                    $this->error('请填写银行信息!');
                }
                if (!preg_match("/^[0-9]{16,20}$/", $post['bank_card'])) {
                    $this->error('请输入正确的银行卡号码!');
                }
            }
            if (!preg_match("/^[\x{4e00}-\x{9fa5}]{2,7}$/u", $post['true_name'])) {
                $this->error('请输入真实的姓名!');
            }
            if (!preg_match("/^\d{6}$/", $post['password'])) {
                $this->error('请输入6位数字的提款密码!');
            }
            $user = M('FrontUser')->field('true_name')->find($user_id);
            if ($user['true_name'] != $post['true_name'])
                $this->error('真实姓名不一致');
            
            if($post['type']==1){
                $data['alipay_id']=$post['alipay_id'];
            }else{
                $data['bank_name']=$post['bank'];
                $data['bank_card_id']=$post['bank_card'];
                $data['bank_region']=$post['province'] . ' ' . $post['city'];
            }
            $data['bank_extract_pwd']=md5($post['password']);

            $update = M('FrontUser')->where(['id' => $user_id])->save($data);

            if ($update!=false)
                $this->success('绑定成功', U('User/drawal_extract'));
            else
                $this->error('绑定失败');
        }
        if (!$user_id)
            redirect(U('login'));

        $user = M('FrontUser')->field('true_name,identfy,bank_name,bank_card_id,bank_region,bank_extract_pwd,alipay_id')->where('id=' . $user_id)->find();

        //是否已经认证身份
        if (!$user['true_name'] || !$user['identfy'])
            redirect(U('User/drawal_idcard'));

        //是否已经绑定银行卡
        if ((($user['bank_name'] && $user['bank_card_id']) || $user['alipay_id']) && $user['bank_extract_pwd'] && $user['identfy'] && $user['true_name']) {
            redirect(U('User/drawal_extract'));
        }

        $this->bank = M('Bank')->field('bank_name')->select();
        $this->province = M('Area')->field('id,region_name')->where(['parent_id' => 1])->select();

        $this->display();
    }

    public function drawal_extract() {
        $minMoney = getWebConfig('common')['iosExtractMoney'];
        $user_id = is_login();
        if (IS_POST) {
            if (!$user_id)
                $this->error('请先登录!');
            $user = M('FrontUser')->field('true_name,identfy,bank_name,bank_card_id,bank_region,coin,bank_extract_pwd,alipay_id')->where('id=' . $user_id)->find();
            //是否已经认证身份
            if (!$user['true_name'] || !$user['bank_extract_pwd'] || !$user['identfy'] || ((!$user['bank_name'] || !$user['bank_card_id']) && !$user['alipay_id']))
                $this->error('请完善身份资料!');

            //是否已经绑定银行卡
//            if (!$user['bank_name'] || !$user['bank_card_id']) {
//                $this->error('请完善银行卡资料!');
//            }
            $coin = I('post.rmb', 0, 'intval');
            $pwd = I('post.pwd', '');
            if (empty($pwd)) {
                $this->error('密码不能为空!');
            }
            if ($coin < $minMoney || $coin > 10000) {
                $this->error('提款金额必须在'.$minMoney.'到10000元之间!');
            }
            if (md5($pwd) !== $user['bank_extract_pwd']) {
                $this->error("提款密码错误！");
            }
            if ($coin > $user['coin']) {
                $this->error("可提现金额为{$user['coin']}元");
            }
            //每天只能申请提款一次
            $begin = strtotime("today");
            $end = strtotime("today") + 86400;
            $where['user_id'] = $user_id;
            $where['log_type'] = 2;
            $where['log_time'] = array('BETWEEN', array($begin, $end));
            $is_true = M("accountLog")->where($where)->select();
            if ($is_true) {
                $this->error("亲，每天只能提款一次哦，明天再来吧！");
            }
            M()->startTrans();
            $rs = M("accountLog")->add(
                    array(
                        'user_id' => $user_id,
                        'log_time' => time(),
                        'log_type' => 2,
                        'change_num' => $coin,
                        'total_coin' => ($user['coin'] + $user['unable_coin']) - $coin,
                        'desc' => "提款申请",
                        'platform' => 4,
                    )
            );
            if ($rs) {
                //减去金额
                $rs2 = M("FrontUser")->where(array('id' => is_login()))->setDec('coin', $coin);
                //添加到冻结提款金额
                $rs3 = M("FrontUser")->where(array('id' => is_login()))->setInc('frozen_coin', $coin);
            }
            if ($rs && $rs2 && $rs3) {
                M()->commit();
                $this->success("申请提款成功，请等待审核！", U('User/index'));
            } else {
                M()->rollback();
                $this->error("申请提款失败！");
            }
        } else {
            if (!$user_id)
                redirect(U('login'));

            $user = M('FrontUser')->field('true_name,identfy,bank_name,bank_card_id,bank_region,coin,bank_extract_pwd,alipay_id')->where('id=' . $user_id)->find();
            //是否已经认证身份
            if (!$user['true_name'] || !$user['identfy'])
                redirect(U('User/drawal_idcard'));

            //是否已经绑定银行卡
            if (!$user['bank_extract_pwd']  || ((!$user['bank_name'] || !$user['bank_card_id']) && !$user['alipay_id'])){
                redirect(U('User/drawal_bank'));
            }
            if($user['alipay_id']){
                $user['type']='alipay';
                if(fn_is_mobile($user['alipay_id'])){
                    $user['alipay_id'] = substr_replace($user['alipay_id'], '****', 3, 4);
                }else{
                    $num=strpos($user['alipay_id'],'@');
                    $user['alipay_id'] = substr_replace($user['alipay_id'], '****', 3,$num-3);
                }
            }else if($user['bank_card_id']){
                $user['type']='bank';
                $user['bank_card_id'] = substr_replace($user['bank_card_id'], '********', 4, 4);
            }
            $this->assign('user', $user);
            $this->assign('minMoney',$minMoney);
            $this->display();
        }
    }

    //个人基本信息
    public function basic() {
        $user_id = is_login();
        if (!$user_id) {
            redirect(U('login'));
        }
        $user = M('FrontUser')->field('head,nick_name,username,true_name,identfy,descript,reg_time,qq_unionid,weixin_unionid,sina_unionid')->find($user_id);
		$this->assign('is_binding',empty($user['username']) ? 0 : 1);//是否绑定手机  1：绑定 0：未绑定
		$user['username'] = substr_replace($user['username'], '****', 3, 4);
        $user['head'] = frontUserFace($user['head']);
        $this->assign('user', $user);
        $this->assign('title', '基本资料');
        $this->display();
    }

    // 账户明细（全部、1支出、2收入、3待结算）
    //支出 2：提款 3：交易(查看) 9：系统扣除 12：扣除分成金币  14：重置数据
    //收入 1：营销支出 4：分成 5：系统赠送 6：积分兑换 7：充值收入 8：自动充值 10：提款失败退回 11：返还购买金币  13：邀请好友
    public function gold_deta() {
        $page = I('page', 1, 'intval');
        $type = I('type', 0, 'intval');
        $pageNum = 20;
        $userid = is_login();
        if (!$userid) {
            redirect(U('login'));
        }
        $where = ['user_id' => $userid];
		$exType = ['2', '3', '9', '12', '14','15','17'];//支出的类型

        //获取记录
        if($type == 3){
            $fb_list = M('quizLog q')
                    ->join("RIGHT JOIN qc_gamble g on g.id = q.gamble_id")
                    ->field('g.home_team_name,g.away_team_name,g.play_type,g.chose_side,g.quiz_number,g.income,q.log_time,q.gamble_id,q.game_type')
                    ->where("q.game_type=1 and g.result = 0 AND g.tradeCoin > 0 AND g.quiz_number > 0 AND g.is_back = 0 AND g.user_id = ".$userid)
                    ->group("g.id")
                    ->order('q.log_time desc')
                    ->select();
            $bk_list = M('quizLog q')
                    ->join("RIGHT JOIN qc_gamblebk g on g.id = q.gamble_id")
                    ->field('g.home_team_name,g.away_team_name,g.play_type,g.chose_side,g.quiz_number,g.income,q.log_time,q.gamble_id,q.game_type')
                    ->where("q.game_type=2 and g.result = 0 AND g.tradeCoin > 0 AND g.quiz_number > 0 AND g.is_back = 0 AND g.user_id = ".$userid)
                    ->group("g.id")
                    ->order('q.log_time desc')
                    ->select();

            $list = array_merge($fb_list,$bk_list);
            foreach($list as $k => $v){
                $log_time[] = $v['log_time'];
            }
            array_multisort($log_time,SORT_DESC,$list);

            $list = array_slice($list,($page-1) * $pageNum,$pageNum);

            foreach($list as $k => $v)
            {
                //编辑描述
                $desc = '';
                $home_name  = explode(',',$v['home_team_name'])[0];
                $away_name  = explode(',',$v['away_team_name'])[0];

                $desc .= '您推荐的【';
                if($v['game_type'] == 2){
                    $desc .= '篮球-';
                    $desc .= C('bk_play_type')[$v['play_type']];
                }else{
                    $desc .= in_array($v['play_type'], ['2', '-2']) ? '竞彩-':'亚盘-';
                    $desc .= C('fb_play_type')[$v['play_type']];
                }
                $desc .= " {$home_name}VS{$away_name}】";
                $desc .= "被{$v['quiz_number']}人查看";

                $list[$k]['change_num'] = $v['income'];
                $list[$k]['desc'] = $desc ? $desc : $v['desc'];
                $list[$k]['log_type'] = 4;

                unset($list[$k]['gamble_id']);
                unset($list[$k]['game_type']);
                unset($list[$k]['income']);
            }

        }
        else
        {
            if($type){
                $where['log_type'] = $type == '1' ? ['IN', $exType] : ['NOT IN', $exType];
            }
            $list = M('AccountLog')->field(['log_type','log_status','log_time','change_num','desc','gamble_id','game_type','ticket_id'])
                    ->where( $where )
                    ->page($page.','.$pageNum)
                    ->order('id desc')
                    ->select();
        }
        foreach ($list as $k2 => $v2)
        {
            $accountStatus = C('accountStatus');
            $list[$k2]['status_desc'] = $accountStatus[$list[$k2]['log_status']];

            //标记是收入还是支出
            if (in_array($v2['log_type'], $exType)){
                $list[$k2]['type'] = '1';
            }else{
                $list[$k2]['type'] = '2';
            }

            if($v2['log_type'] == '1'){
                $list[$k2]['log_type'] = '4';
            }
            $list[$k2]['log_type_name'] = C('accountType')[$v2['log_type']];
            $list[$k2]['log_date']  = date('Y-m-d',$v2['log_time']);
            $list[$k2]['log_times'] = date('H:i:s',$v2['log_time']);
            unset($list[$k2]['ticket_id']);
        }
        if ($page != 1) {
            $this->success($list);
        }

        $exNum  = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['IN',$exType]])->sum('change_num');//支出
        $inNum  = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['NOT IN',$exType]])->sum('change_num');//收入
        $wjsNum1 = (int)M('gamble')->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)->sum('income'); //足球待结算
        $wjsNum2 = (int)M('gamblebk')->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)->sum('income'); //篮球待结算
        $totalNum   = $inNum - $exNum;//余额

        $this->assign('totalNum', $totalNum);
        $this->assign('exNum', $exNum);
        $this->assign('inNum', $inNum);
        $this->assign('wjsNum', $wjsNum1 + $wjsNum2);
        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->assign('title', '金币明细');
        $this->display();
    }

    //积分详情
    public function integral_deta() {
        $page = I('param.page', 1, 'intval');
        $type = I('param.type', 0, 'intval');
        $pageNum = 20;

        $userid = is_login();
        if (!$userid) {
            redirect(U('login'));
        }
        $where = ['user_id' => $userid];
        $exType = [2, 6]; //支出的类型

        switch ($type) {
            case '1': $where['log_type'] = ['in', $exType];
                break;
            case '2': $where['log_type'] = ['not in', $exType];
                break;
        }
        $list = M('PointLog')->field(['log_type', 'log_time', 'change_num', 'desc'])
                ->where($where)
                ->page($page . ',' . $pageNum)
                ->order('id desc')
                ->select();

        foreach ($list as $k => &$v) {
            $v['log_date'] = date('Y-m-d', $v['log_time']);
            $v['log_times'] = date('H:i:s', $v['log_time']);
            if (in_array($v['log_type'], $exType))
                $list[$k]['type'] = 1;
            else
                $list[$k]['type'] = 2;
        }
        if ($page != 1)
            $this->success($list);

        $exNum = (int) M('PointLog')->where(['user_id' => $userid, 'log_type' => ['in', $exType]])->sum('change_num');
        $inNum = (int) M('PointLog')->where(['user_id' => $userid, 'log_type' => ['not in', $exType]])->sum('change_num');
        $totalNum = $inNum - $exNum;
        $this->assign('totalNum', $totalNum);
        $this->assign('exNum', $exNum);
        $this->assign('inNum', $inNum);
        $this->assign('list', $list);
        $this->assign('type', $type);
        $this->assign('title', '积分明细');
        $this->display();
    }

    //通知
    public function user_inform() {
        $page = I('param.page', 1, 'intval');
        $userid = is_login();
        if (!$userid) {
            redirect(U('login'));
        }
        $pageNum = 20;
        $list = M('Msg')->field(['title', 'content', 'is_read', 'send_time'])
                ->where(['front_user_id' => $userid])
                ->page($page . ',' . $pageNum)
                ->order('id desc')
                ->select();
        foreach ($list as &$v) {
            if (date('Ymd') == date('Ymd', $v['send_time'])) {
                $v['times'] = date('H:i:s', $v['send_time']);
            } else {
                $v['times'] = date('Y-m-d', $v['send_time']);
            }
        }
        if (IS_AJAX) {
            $this->success($list);
        }
        $this->assign('list', $list);
        M('Msg')->where(['front_user_id' => $userid])->save(['is_read' => 1]);
        $this->assign('title', '通知');
        $this->display();
    }

    //是否有新的通知
    public function getNewMsg() {
        $userid = is_login();
        if ($userid) {
            $num = M('Msg')->where(['front_user_id' => $userid, 'is_read' => 0])->count();
            return $num;
        }
    }

    //我的粉丝
    public function myFans() {
        $page = I('param.page', 1, 'intval');
        $pageNum = 20;
        $userid = is_login();
        if (!$userid) {
            redirect(U('login'));
        }
        $list = M('FollowUser f')->field('f.user_id,u.nick_name,u.lv,u.head face,u.descript')
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.user_id')
                ->where(['f.follow_id' => $userid])
                ->page($page . ',' . $pageNum)
                ->order('f.id desc')
                ->select();

        foreach ($list as $k => $v) {
            $list[$k]['face'] = frontUserFace($v['face']);
            $list[$k]['isFollow'] = M('FollowUser')->where(['user_id' => $userid, 'follow_id' => $v['user_id']])->find() ? 1 : 0; //是否已经关注
        }
        if (IS_AJAX) {
            $this->success($list);
        }
        $this->assign('list', $list);
        $this->assign('title', '我的粉丝');
        $this->display();
    }

    public function wechat_login() {
        $wxpay_config = C('wxpay.wxpay_config');
        if (!isset($_GET['code'])) {
            redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $wxpay_config['appid'] . "&redirect_uri=http://m.qqty.com/User/wechat_login.html&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect");
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            //获取token
            $tokenInfo = $this->do_curl('https://api.weixin.qq.com/sns/oauth2/access_token', 'appid=' . $wxpay_config['appid'] . '&secret=' . $wxpay_config['appsecret'] . '&code=' . $code . '&grant_type=authorization_code');
            $tokenInfo = json_decode($tokenInfo, true);
            $userInfo = $this->do_curl('https://api.weixin.qq.com/sns/userinfo', 'access_token=' . $tokenInfo['access_token'] . '&openid=' . $tokenInfo['openid'] . '&lang=zh_CN', array(), 'GET', 'wx');
            $userInfo = json_decode($userInfo, true);
            session('m_openid', $userInfo['openid']);
            $uid=is_login();
            if (!$uid) {
                $user_id = M('FrontUser')->where(array('weixin_unionid' => $tokenInfo['unionid']))->getField('id');
                if ($user_id) {
                    D('FrontUser')->autoLogin($user_id);
					$url = cookie('redirectUrl');
					if (!$url)
					{
						$url = U('User/index');
					}
					cookie('redirectUrl',null);
                    redirect($url);
                } else {
                    $token['type'] = 'weixin';
                    $token['unionid'] = $tokenInfo['unionid'];
                    cookie('loginToken',$token,array('expire'=>C('loginTokenTime'),'domain'=>'.'.DOMAIN));
                    redirect(U('User/tpperfect'));
                }
            } else {
                $is_bind = M("frontUser")->where(['id' => $uid])->getField('weixin_unionid');
				$is_byBind = M("frontUser")->where(['weixin_unionid' => $tokenInfo['unionid']])->getField('id');
                if(empty($is_bind) && empty($is_byBind)){
                    M("frontUser")->where(['id' => $uid])->save(['weixin_unionid'=>$tokenInfo['unionid']]);
					redirect(U('User/basic'));
					exit;
                }
				redirect(U('User/basic',['status'=>'no']));
                
            }
        }
    }

    //服务协议
    public function agreement() {
        $this->display();
    }

    //个人简介
    public function profile(){
        $userid = is_login();
        if (!$userid) {
            redirect(U('login'));
        }
        if (IS_AJAX) {
            $content = I('param.content', '');
            $content=  trim($content);
            if($content=='' || mb_strlen($content, 'UTF-8')>20){
                $this->error('内容限制20个字！');
            }
            if(!matchFilterWords('FilterWords',$content))
                    $this->error('您输入的内容包含敏感词，请重新填写！');
            $rsl=M('FrontUser')->where('id='.$userid)->save(['descript'=>$content]);
            if($rsl===false){
                $this->error('保存失败！');
            }else{
                $this->success('保存成功！',U('User/basic'));
            }
        }
        $data=M('FrontUser')->where('id='.$userid)->getField('descript');
        $this->assign('data',$data);
        $this->display();
    }

    public function startAPP(){
        $pkg = I('pkg');
        switch ($pkg) {
            case 'company':
                $appName = 'qqtywcom://cz';
                break;
            case 'personal':
                $appName = 'hhnScoreApp://cz';
                break;
            case 'worldCup':
                $appName = 'qqtyWorldCup://cz';
                break;
            case 'valuableBook':
                $appName = 'com.chentao.scoreBook://cz';
                break;
            case 'zuzu':
                $appName = 'com.zuzu.sportsApp://cz';
                break;
        }
        
        echo "<script>window.location.href='".$appName."'</script>";
        die;
    }

    //专家个人主页
    public function expUser(){
        //获取专家信息
        $user_id = I('user_id');
        if($user_id == is_login()) $this->assign('is_me',1);
        $user = A("Home/Video")->author_info($user_id);
//        if($user['is_expert'] != 1) redirect(U('Guess/other_page',['user_id'=>$user['id'],'type'=>1]));
        cookie('redirectUrl',U('/expUser/'.$user_id));
        $user['followNum'] = M('FollowUser')->where(['follow_id'=>$user_id])->count();
//        $newsNum = M('PublishList')->field('sum(click_number) as num')->where(['user_id'=>$user_id])->find();//资讯点击量
//        $videoNum = M('Highlights')->field('sum(click_num) as num')->where(['user_id'=>$user_id])->find();//视频点击量
//        $user['click_num'] = (int)$newsNum['num'] + (int)$videoNum['num'];
        $this->assign('titleHead','TA的主页');

        //获取关注数
        $user['number'] = M('FollowUser')->where(['follow_id'=>$user_id])->count();//获取粉丝条数
        //判断需要进入什么模块
        $type = I('type',1,'int');
        if($user['is_expert'] != 1) {
            $type = 1;
            $navData = 0;
        }else{
            //针对导航栏显示控制
            $show = $this->showNav($user_id);
            $navData = $show;
            if(!in_array($type,$navData)) $type = reset($navData);
        }
        $this->assign('navData',$navData);
        //推荐模块数据
        switch($type)
        {
            case 3:
                $this->assign('listType',1);
                break;
            case 4:
                $this->assign('listType',2);
                break;
            default:
                $this->assign('listType',0);
                $this->getGuess($user_id);
        }

        if (is_login())
        {
            //是否已关注
            $isFollow = M('FollowUser')->where(array('user_id'=>is_login(),'follow_id'=>$user_id))->find();
            $this->assign('isFollow',$isFollow);
        }
        $this->assign('user',$user);
        $this->display();
    }

    public function showNav($user_id){
        $where['g.user_id'] = $user_id;
        //最新10条推荐
        $gamble = M('gamble g')
            ->join("LEFT JOIN qc_union u on u.union_id=g.union_id")->field("g.id")->where($where)->order("g.id desc")->find();
        if($gamble) $show['gamble'] = 1;
        $map['user_id'] = $user_id;
        $map['status'] = 1;
        $new = M('PublishList')->where($map)->find();
        if($new) $show['new'] = 3;
        $map['m_url'] = ['NEQ',''];
        $video = M('Highlights')->where($map)->find();
        if($video) $show['video'] = 4;
        return $show;
    }

    //专家个人主页推荐模块数据
    public function getGuess()
    {
        $user_id  = I('user_id');
        //连胜---足球亚盘
        $winnig  = D('GambleHall')->getWinning($user_id); //推荐统计信息
        //Ta的推荐统计
        if (!empty($sort))
        {
            if ($sort == '2' || $sort == '-2')
            {
                $winnigCount  = D('GambleHall')->getWinning($user_id,1,0,2); //推荐统计信息
            }
            else
            {
                $winnigCount  = D('GambleHall')->getWinning($user_id,1,$sort,2); //推荐统计信息
            }
            $winnig['total_times'] = $winnigCount['total_times'];
        }
        $this->assign('winnig', $winnig);
        //连胜---足球竞彩
        $winnigRace  = D('GambleHall')->getWinning($user_id,1,0,2); //推荐统计信息
        $this->assign('winnigRace', $winnigRace);

        $sort = I('type');
        switch($sort)
        {
            case 1:
            case -1:
            case 2:
                $sort = $sort;
                break;
            default:
                $sort = NULL;
        }
        $this->assign('gambleType',$sort);


        if (is_login())
        {
            //是否已关注
            $isFollow = M('FollowUser')->where(array('user_id'=>is_login(),'follow_id'=>$user_id))->find();
            $this->assign('isFollow',$isFollow);
        }
        //胜率----足球亚盘

        $win['week']   = D('GambleHall')->CountWinrate($user_id,1,1);  //周
        $win['month']  = D('GambleHall')->CountWinrate($user_id,1,2);  //月
        $win['season'] = D('GambleHall')->CountWinrate($user_id,1,3);  //季
        //胜率---足球竞彩
        $win['weekRace']   = D('GambleHall')->CountWinrate($user_id,1,1,false,false,0,2);  //周
        $win['monthRace']  = D('GambleHall')->CountWinrate($user_id,1,2,false,false,0,2);  //月
        $win['seasonRace'] = D('GambleHall')->CountWinrate($user_id,1,3,false,false,0,2);  //季


        $this->assign('type', $sort);
        $this->assign('user_id', $user_id);
        $this->assign('win', $win);
    }



    //专家个人主页ajax查询列表数据
    public function expUserList()
    {
        $page = I('page')-1;
        $time = I('time')?I('time'):time();
        $num = 10;
        $where['user_id'] = I('id');
        $limit_page = $page * $num;
        if(I('type') == 1)
        {
            $where['p.status'] = 1;
            $where['p.add_time'] = ['lt',$time];
            $list = M('PublishList p')
                ->join("LEFT JOIN qc_publish_class c on c.id = p.class_id")
                ->field('p.id,p.title,p.img,p.add_time,p.click_number,p.top_recommend,p.web_recommend,c.domain,c.pid,c.name,p.class_id')
                ->where($where)
                ->order('p.add_time desc')
                ->limit($limit_page.','.$num)
                ->select();
        }else{
            $where['status'] = 1;
            $where['add_time'] = ['lt',$time];
            $where['m_url'] = ['NEQ',''];
            $list = M('Highlights')->field('id,title,img,add_time,click_num as click_number,m_ischain,m_url')->where($where)->order('add_time desc')->limit($limit_page.','.$num)->select();
        }
        $classArr  = getPublishClass(0);
        foreach($list as $key=>$value)
        {
            $list[$key]['img']  = newsImgReplace($value);
            $list[$key]['add_time'] = date('Y-m-d',$value['add_time']);
            $list[$key]['click_number'] = addClickConfig(1, $value['class_id'],$value['click_number'], $value['id']);
            if($value['top_recommend'] == 1 && $value['web_recommend'] == 1) $list[$key]['is_hot'] = 1;
            if(!$value['domain']) $list[$key]['domain'] = $classArr[$value['pid']]['domain'];
            if($value['pid'] != '0') $list[$key]['name'] = $classArr[$value['pid']]['name'];
            if(!$value['domain']) $list[$key]['domain'] = 'general';
            //组装url
            if(I('type') == 1)
            {
                $list[$key]['url'] = mNewsUrl($value['id'],$value['class_id'],$classArr);
            }else{
                $list[$key]['url'] = $value['m_ischain'] == 1?$value['m_url']:U('/video/'.$value['id'].'@m');
            }
            unset($list[$key]['pid']);
        }
        if($list)
            $data = ['code'=>200,'data'=>$list];
        else
            $data = ['code'=>201,'msg'=>'暂无数据!!'];
        $this->ajaxReturn($data);

    }
}
