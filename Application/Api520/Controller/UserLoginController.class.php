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
        $res = D('FrontUser')->login($this->param);

        if(is_array($res)){
            $this->ajaxReturn($res['loginData'], $res['$msgCode']);
        }else{
            $this->ajaxReturn($res);
        }
    }

    //发送注册验证码
    public function sendRegisteCode()
    {
        $type = $this->param['area_code'] ? (string)$this->param['area_code'] : 'registe';
        if(!in_array($type, array('registe', '852', '853', '886')) || empty($this->param['deviceID']))
            $this->ajaxReturn(101);

        if ($type == 'registe' && !Think\Tool\Tool::checkMobile($this->param['mobile'])){
            $this->ajaxReturn(1002);
        }else if($type == '852' || $type == '853'){
            if(!preg_match("/^\d{8}$/", $this->param['mobile']))
                $this->ajaxReturn(1002);
        }else if($type == '886'){
            if(!preg_match("/^(09|9)\d{8}$/", $this->param['mobile']))
                $this->ajaxReturn(1002);
        }

        //传userToken说明是完善资料，判断除自己外有没有注册
        $userToken = getUserToken($this->param['userToken']);
        if($userToken && $userToken['username'] == $this->param['mobile']){
            $isRegister = M('FrontUser')->where(['username' => $this->param['mobile'], 'id' => ['NEQ', $userToken['userid']]])->count();
        }else{
            $isRegister = M('FrontUser')->where(array('username' => $this->param['mobile']))->count();
        }

        if($isRegister)
            $this->ajaxReturn(1003);

        $sendResult = sendCode($this->param['mobile'], $type, $this->param['deviceID']);
        if ($sendResult === -1)
            $this->ajaxReturn(1059);

        if ($sendResult === -2)
            $this->ajaxReturn(4019);

        if (!$sendResult)
            $this->ajaxReturn(1004);

        $this->ajaxReturn(['registe' => 1, 'type' => ($sendResult['mobileSMS'] == 3) ? '2' : '1']);
    }

    //注册用户
    public function registe()
    {
        $res = D('FrontUser')->register($this->param);
        $this->ajaxReturn($res);
    }

    //发送重置密码的验证码
    public function sendResetCode()
    {
        if(strlen(trim($this->param['mobile'])) == 11){
            if (!Think\Tool\Tool::checkMobile($this->param['mobile']))
                $this->ajaxReturn(1002);
        }else if(in_array(strlen(trim($this->param['mobile'])), [9, 10])){
            if (!preg_match("/^(09|9)\d{8}$/", $this->param['mobile']))
                $this->ajaxReturn(1002);
        }else if(strlen(trim($this->param['mobile'])) == 8){
            if (!preg_match("/^\d{8}$/", $this->param['mobile']))
                $this->ajaxReturn(1002);
        }else{
            $this->ajaxReturn(1002);
        }

        if (!M('FrontUser')->where(array('username' => $this->param['mobile']))->find())
            $this->ajaxReturn(1013);

        $token = sendCode($this->param['mobile'], 'resetPwd', $this->param['deviceID']);
        if ($token === -1)
            $this->ajaxReturn(1059);

        if ($token === -2)
            $this->ajaxReturn(4019);

        if (!$token)
            $this->ajaxReturn(1004);

        $this->ajaxReturn(['resetToken' => $token['token'], 'type' => ($token['mobileSMS'] == 3) ? '2' : '1']);
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

    /**
     * APP记录登录数据
     */
    public function log(){
        if (!$userToken = getUserToken($this->param['userToken']))
            $this->ajaxReturn(1001);

        $login_time = M('FrontUser')->where(['id' => $userToken['userid']])->getField('login_time');

        //判断一个小时内重复不算登录
        if(NOW_TIME - $login_time < 60*60)
            $this->ajaxReturn(['result' => '0']);

        $rs = M('FrontUser')->where(['id'=>$userToken['userid']])->save([
            'login_count'    => ['exp', 'login_count+1'],
            'login_time'     => NOW_TIME,
            'last_ip'        => get_client_ip(),
            'mobile_model'   => (string)$this->param['mobile_model'],
        ]);

        if($rs === false)
            $this->ajaxReturn(['result' => '2']);

        //检查登录情况是否达到邀请好友的有效条件
        $res = M('InviteLoginInfo')->where(['user_id' => $userToken['userid']])->find();
        if(!$res)//没有入库的才入库
            D('FrontUser')->checkLoginData($userToken['userid']);

        //判断邀请注册时获得金币是否已给，有邀请金币且没有给的才进入方法
        $getInfo = M('InviteInfo')->field('register_coin, is_get')->where(['user_id' => $userToken['userid']])->find();
        if($getInfo['is_get'] == 0 && $getInfo['register_coin'] > 0)
            D('FrontUser')->checkRegisterCoin($userToken['userid']);

        $this->ajaxReturn(['result' => '1']);
    }

    /**
     * 同步之前的邀请好友金币到有效账户
     */
    public function updateCoins(){
        if($this->param['begin'] != 'go')
            $this->ajaxReturn(101);

        $data = M('InviteInfo')->group('id')->order('id')->getField('user_id, total_coin');

        $ids = implode(',', array_keys($data));
        $sql = " UPDATE qc_invite_info SET valid_coin = CASE user_id ";

        foreach ($data as $id => $v) {
            $sql .= sprintf(" WHEN %d THEN %d ", $id, $v);
        }

        $sql .= " END WHERE user_id IN ($ids) ";

        M()->startTrans();
        $res = M()->execute($sql);

        if($res){
            M()->commit();
        }else{
            M()->rollback();
        }

        var_dump($res);die;
    }

    /**
     * 专家发布的资讯
     */
    public function expertList(){
        $userToken = getUserToken($this->param['userToken']);
        //没有被查看的user_id，就是看自己的列表
        $user_id = intval($this->param['user_id'] ?: $userToken['userid']);
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        if($user_id == 0)
            $this->ajaxReturn(['articleList' => []]);

        $list = D('Home')->getArticleList($page, $pageNum, '', 0, $user_id);
        //个人的要去掉置顶
        foreach($list as $k => $v){
            $list[$k]['app_recommend'] = '0';
        }

        $this->ajaxReturn(['articleList' => (array)$list]);
    }

    /**
     * APP文字验证码
     */
    public function verify(){
        $Verify = new \Think\Verify();
        // 设置验证码字符为纯数字
        $Verify->codeSet = '0123456789';
        $Verify->length   = 4;
        $Verify->entry();
        $value = $_SESSION['sess_']['d2d977c58444271d9c780187e93f80e5']['verify_code'];
        $deviceID = I('deviceID');
        $redis = connRedis();
        $redis->set($deviceID, $value, 60);
    }

    //app记录
    public function appLogs(){
        $param = $this->param;
        $rs    = addAppLogs($param);
        $this->ajaxReturn(['status' => $rs ? 1 : 0]);
    }

}


?>