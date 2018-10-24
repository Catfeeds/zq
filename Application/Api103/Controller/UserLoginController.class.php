<?php

/**
 * 用户注册登陆
 * @author huangjiezhen <418832673@qq.com> 2015.12.15
 */
class UserLoginController extends PublicController
{
    //用户登陆 自动登录
    public function login()
    {
        $user = array();
        $field = ['id', 'nick_name', 'status', 'login_time', 'point'];
        if ($this->param['mobile'] && $this->param['password']) {//普通登录

            $user = M('FrontUser')->field($field)->where(['username' => $this->param['mobile'], ['password' => md5($this->param['password'])]])->find();

        }

        if (!$user)
            $this->ajaxReturn(1009);

        if ($user['status'] != 1)
            $this->ajaxReturn(1005);

        $msgCode = $user['nick_name'] ? '' : 1010;
        $loginData = D('FrontUser')->loginData($user['id'], $user['login_time'], $user['point'], $this->param['mobile'], $this->param['platform']);

        $this->ajaxReturn($loginData, $msgCode);
    }

    //发送注册验证码
    public function sendRegisteCode()
    {
        if (!Think\Tool\Tool::checkMobile($this->param['mobile']))
            $this->ajaxReturn(1002);

        if (M('FrontUser')->field('id')->where(array('username' => $this->param['mobile']))->find())
            $this->ajaxReturn(1003);

        $sendResult = sendCode($this->param['mobile']);

        if ($sendResult === -1)
            $this->ajaxReturn(1059);

        if (!$sendResult)
            $this->ajaxReturn(1004);

        $this->ajaxReturn(['registe' => 1]);
    }

    //注册用户
    public function registe()
    {
        if (M('FrontUser')->where(array('username' => $this->param['mobile']))->find())
            $this->ajaxReturn(1003);

        if (!Think\Tool\Tool::checkPassword($this->param['password']))
            $this->ajaxReturn(1006);

        if ($this->param['smsCode'] == null || S(md5(C('smsPrefix') . $this->param['mobile']))['rank'] != $this->param['smsCode'])
            $this->ajaxReturn(1007);

        //判断同一个ip不能多次注册
        $ip = get_client_ip();
        $isReg = M('FrontUser')->field('reg_time')->where(['reg_ip'=>$ip,'reg_time'=>['gt',strtotime(date('Y-m-d'))]])->order('reg_time desc')->select();

        if ((isset($isReg[0]['reg_time']) && (NOW_TIME - $isReg[0]['reg_time'] <= 60*5)) || count($isReg) > 3)
            $this->ajaxReturn(1071);

        $data = [
            'username'     => $this->param['mobile'],
            'password'     => md5($this->param['password']),
            'reg_time'     => NOW_TIME,
            'reg_ip'       => $ip,
            'platform'     => $this->param['platform'],
            'channel_code' => $this->param['channel_code'],
        ];

        $userid = M('FrontUser')->add($data);

        if (!$userid)
            $this->ajaxReturn(1008);

        //绑定第三方帐号
//        $this->bindSdkUser($userid);

        //是否有活动注册赠送金币
        $config = M('Config')->where(['sign' => 'loginGift'])->getField('config');
        $config = json_decode($config, true);

        if ($config['is_on'] && $config['giftCoin'] > 0 && NOW_TIME >= $config['begin'] && NOW_TIME <= $config['end']) {
            $coinField = $config['coinType'] == 0 ? 'unable_coin' : 'coin';

            if (M('FrontUser')->where(['id' => $userid])->save([$coinField => $config['giftCoin']]) !== false) //赠送金币
            {
                M('AccountLog')->add([
                    'user_id' => $userid,
                    'log_time' => NOW_TIME,
                    'log_type' => 5,
                    'log_status' => 1,
                    'change_num' => $config['giftCoin'],
                    'total_coin' => $config['giftCoin'],
                    'desc' => "注册赠送",
                    'platform' => $this->param['platform'],
                ]); //添加账户明细记录
            }
        }

        $loginData = D('FrontUser')->loginData($userid, $lastLoginTime = 0, $point = 0, $this->param['mobile'], $this->param['platform']);
        $this->ajaxReturn($loginData);
    }

    //发送重置密码的验证码
    public function sendResetCode()
    {
        if (!Think\Tool\Tool::checkMobile($this->param['mobile']))
            $this->ajaxReturn(1002);

        if (!M('FrontUser')->where(array('username' => $this->param['mobile']))->find())
            $this->ajaxReturn(1013);

        if (!$token = sendCode($this->param['mobile'], 'resetPwd'))
            $this->ajaxReturn(1004);

        $this->ajaxReturn(['resetToken' => $token['token']]);
    }

    //确认重置密码的验证码
    public function confirmResetCode()
    {
        if ($this->param['smsCode'] == null || S($this->param['resetToken'])['rank'] != $this->param['smsCode'])
            $this->ajaxReturn(1007);

        $this->ajaxReturn(['resetToken' => $this->param['resetToken']]);
    }

    //输入新密码
    public function resetPassword()
    {
        if (!Think\Tool\Tool::checkPassword($this->param['newPassword']))
            $this->ajaxReturn(1006);

        if (!$mobile = S($this->param['resetToken'])['mobile'])
            $this->ajaxReturn(1014);

        if (M('FrontUser')->where(['username' => $mobile])->save(['password' => md5($this->param['newPassword'])]) === false)
            $this->ajaxReturn(1015);

        $user = M('FrontUser')->field(['id', 'login_time', 'point'])->where(['username' => $mobile])->find();

        $loginData = D('FrontUser')->loginData($user['id'], $user['login_time'], $user['point'], $mobile, $this->param['platform']);
        $this->ajaxReturn($loginData);
    }

    //绑定第三方帐号
//    public function bindSdkUser($userid)
//    {
//        return;
//        if (!$this->param['loginToken'])
//            return;
//
//        if (!$sdkInfo = S('loginToken:' . $this->param['loginToken']))
//            $this->ajaxReturn(1056);
//
//        if (M('FrontUser')->where(['id' => $userid])->getField(key($sdkInfo)))  //是否已绑定同平台的其他第三方帐号
//            $this->ajaxReturn(1057);
//
//        if (M('frontUser')->where(['id' => $userid])->save($sdkInfo) === false)
//            $this->ajaxReturn(1058);
//
//        S('loginToken:' . $this->param['loginToken'], null);
//    }
}


?>