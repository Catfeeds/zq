<?php
/**
 * 摇一摇活动
 */
use Think\Tool\Tool;

class ShakeController extends \Think\Controller
{
    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_AUTHORIZE_URL = '/authorize?';
    const API_BASE_URL_PREFIX = 'https://api.weixin.qq.com';
    const OAUTH_TOKEN_URL = '/sns/oauth2/access_token?';
    const OAUTH_USERINFO_URL = '/sns/userinfo?';

    private $url = '';
    private $wxConfig;
    private $authType = 'snsapi_userinfo';
    private $domain   = 'https://m.qqty.com/';
    private $userInfo;

    public function _initialize()
    {
        $wxconfig = C('wxpay.wxpay_config');
        $this->wxConfig = $wxconfig;
        $this->domain = 'https://m.'.DOMAIN;
    }

    /**
     * 活动详情页
     */
    public function details()
    {
//        header('Location:http://m.qqty.com/Guess/new_put.html');exit;
        $urlToState = $this->urlToState($this->domain . $this->url());
        $callback = U('Shake/callback');

        $unionid = session('shake_'.$this->wxConfig['appid'] . '_uid');

        if(!$unionid) {
            if (!I('code') && !I('state')) {
                $sUrl = $this->getOauthRedirect($callback, $urlToState);
                redirect($sUrl);
                exit;
            }
        }


        //网站登录注册
        $user_id = M("FrontUser")->where(['weixin_unionid' => $unionid])->getField('id');

        if($user_id){
            D('FrontUser')->autoLogin($user_id);
        }else{
            //昵称
            $wxUserInfo = S('shake_' . $unionid . '_userinfo');
            $nickname = $wxUserInfo['nickname'];
            $length = $length = mb_strlen($nickname, 'utf-8');

            //昵称长度小于2或者大于10，则生成随机昵称
            if ($length < 2 || $length > 8){
                $nickname = 'wx_'.GetRandStr(6);
            }

            if(M('FrontUser')->where(['nick_name' => $nickname])->getField('nick_name')){
                $nickname = $nickname . GetRandStr(2);
            }

            $addData['nick_name']   = $nickname;

            $addData['platform']    = '4';
            $addData['channel_code']= 'm';
            $addData['reg_time']    = time();
            $addData['reg_ip']      = get_client_ip();
            $addData['weixin_unionid'] = $unionid;

            $user_id = M('frontUser')->add($addData);
            if ($user_id !== false) {
                D('FrontUser')->autoLogin($user_id);
            }
        }

        $shake = M('Config')->where(['sign' => 'shake'])->find();
        $vo = json_decode($shake['config'], true);

        //更新领取人数
        $nums = time() - $vo['create_time']  + 3000;
        $vo['nums'] = $nums;
        M('Config')->where(['sign' => 'shake'])->save(['config' => json_encode($vo)]);

        $vo['sign'] = $shake['sign'];
        $vo['bg_logo'] = Tool::imagesReplace($vo['bg_logo']);

        $this->assign('vo', $vo);
        $this->assign('daka_url', U('Guess/new_put'));
        $this->display();
    }

    /**
     * 活动详情页
     */
    public function details_test()
    {
        $urlToState = $this->urlToState($this->domain . $this->url());
        $callback = U('Shake/callback');

        $unionid = session('shake_'.$this->wxConfig['appid'] . '_uid');

        if(!$unionid) {
            if (!I('code') && !I('state')) {
                $sUrl = $this->getOauthRedirect($callback, $urlToState);
                redirect($sUrl);
                exit;
            }
        }

        //网站登录注册
        $user_id = M("FrontUser")->where(['weixin_unionid' => $unionid])->getField('id');

        if($user_id){
            D('FrontUser')->autoLogin($user_id);
        }else{
            //昵称
            $wxUserInfo = S('shake_' . $unionid . '_userinfo');
            $nickname = $wxUserInfo['nickname'];
            $length = $length = mb_strlen($nickname, 'utf-8');

            //昵称长度小于2或者大于10，则生成随机昵称
            if ($length < 2 || $length > 8){
                $nickname = 'wx_'.GetRandStr(6);
            }

            if(M('FrontUser')->where(['nick_name' => $nickname])->getField('nick_name')){
                $nickname = $nickname . GetRandStr(2);
            }

            $addData['nick_name']   = $nickname;
            $addData['platform']    = '4';
            $addData['channel_code']= 'm';
            $addData['reg_time']    = time();
            $addData['reg_ip']      = get_client_ip();
            $addData['weixin_unionid'] = $unionid;

            $user_id = M('frontUser')->add($addData);
            if ($user_id !== false) {
                D('FrontUser')->autoLogin($user_id);
            }
        }

        $shake = M('Config')->where(['sign' => 'shake'])->find();
        $vo = json_decode($shake['config'], true);

        //更新领取人数
        $nums = time() - $vo['create_time']  + 3000;
        $vo['nums'] = $nums;
        M('Config')->where(['sign' => 'shake'])->save(['config' => json_encode($vo)]);

        $vo['sign'] = $shake['sign'];
        $vo['bg_logo'] = Tool::imagesReplace($vo['bg_logo']);

        $this->assign('vo', $vo);
        $this->assign('daka_url', U('Guess/new_put'));
        $this->display('details');
    }

    public function sendCode(){
        $ret = ['code' => '200', 'data' => '', 'msg' => ''];
        try{
            if(!IS_AJAX && !IS_POST){
                throw new \Think\Exception('非法访问！', '1001');
            }

            if(!I('mobile')){
                throw new \Think\Exception('手机号码为空！', '1002');
            }

            if (!fn_is_mobile(I('mobile'))) {
                throw new \Think\Exception('请输入正确的手机号!', '1002');
            }
            $_POST['platform'] = 4;
            $result = sendCode(I('mobile'),'active');

            if ($result == '-1') {
                throw new \Think\Exception('您已经发送过验证码,请等待' . C('reSendCodeTime') . '秒后重试!', '1003');
            }

            if ($result) {
                cookie('verifyCode', $result['token'], C('verifyCodeTime'));  //存返回值
                if($result['mobileSMS']==3){
                    $ret['msg'] = '验证码将以电话语音形式通知您,请注意接听！';
                }else{
                    $ret['msg'] = '验证码将以短信形式通知您,请在' . (C('verifyCodeTime') / 60) . '分钟内完成验证注册！';
                }
                $ret['data'] = ['code' => S(cookie('verifyCode')['rank'])];
            }

        }catch (\Think\Exception $e){
            $ret['code'] = $e->getCode();
            $ret['msg'] = $e->getMessage();
        }

        $this->ajaxReturn($ret);
    }

    /**
     * 获取红包操作
     */
    public function getRedPackage(){
        $ret = ['code' => 200, 'data' => '', 'msg' => '领取成功！'];
        try{
            if(!IS_AJAX && !IS_POST){
                throw new \Think\Exception('非法访问！', '1001');
            }
            if(!session('shake_'.$this->wxConfig['appid'] . '_uid')){
                throw new \Think\Exception('请重新授权登录！', '1001');
            }

            $verifyNum = I('code');
            $mobile = I('mobile');
            $sign = I('sign');

            if(!$mobile)
                throw new \Think\Exception('手机号码为空！', '1002');

            if(!$verifyNum)
                throw new \Think\Exception('验证码为空！', '1003');

            if(!$sign)
                throw new \Think\Exception('体验卷无效！', '1004');

            $verify = S(cookie('verifyCode'));

            if(!$verify || $verify['rank'] != $verifyNum || $verify['mobile'] != $mobile)
                throw new \Think\Exception('验证码错误！', '1002');

            $unionid = session('shake_'.$this->wxConfig['appid'] . '_uid');
            $frontUser = M("FrontUser")->where(['weixin_unionid' => $unionid])->find();

            //未绑定手机号、先绑定
            if ($frontUser['username'] == ''){
                $mobile_is_bind = M("FrontUser")->where(['username' => $mobile])->find();
                if(!$mobile_is_bind){
                    M("FrontUser")->where(['id' => $frontUser['id']])->save(['username' => $mobile]);
                }
            }

            //领取优惠券
            $shake = M('Config')->where(['sign' => 'shake'])->find();

            if($shake){
                $vo = json_decode($shake['config'], true);
                //判断有没有之前购买(修改)

                $ticketLog = M('TicketLog')->where(['get_type' => '6', 'user_id' => $frontUser['id']])->find();
                if($ticketLog){
                    $is_get_price = $ticketLog['price'];
                    throw new \Think\Exception('你已经领取过了', '1005');
                }

                $data = [];
                $data['name']        = $vo['price'].'金币体验卷';
                $data['user_id']     = $frontUser['id'];
                $data['type']        = 1;
                $data['price']       = $vo['price'];
                $data['get_time']    = NOW_TIME;
                $data['over_time']   = $vo['over_time'];
                $data['plat_form']   = '4';
                $data['get_type']    = '6';
                $data['remark']      = '推荐体验券-摇一摇活动赠送';

                //添加记录
                $res3 = M('TicketLog')->add($data);

                if($res3 === false)
                    throw new \Think\Exception('购买失败！', '1006');

            }else{
                throw new \Think\Exception('体验卷无效！', '1007');
            }

        }catch (\Think\Exception $e){
            $ret = ['code' => $e->getCode(), 'data' => ['is_get_price' => $is_get_price], 'msg' => $e->getMessage()];
        }
        $this->ajaxReturn($ret);
    }

    /**
     * oauth 授权跳转接口
     * @param $callback
     * @param string $state
     * @param string $scope
     * @return string
     */
    public function getOauthRedirect($callback, $state = '', $scope = 'snsapi_userinfo')
    {
        return self::OAUTH_PREFIX . self::OAUTH_AUTHORIZE_URL . 'appid=' . $this->wxConfig['appid'] . '&redirect_uri=' . urlencode($callback) . '&response_type=code&scope=' . $scope . '&state=' . $state . '#wechat_redirect';
    }

    /**
     * 授权回调页
     */
    public function callback()
    {
        if (I('code') && I('state')) {
            $aJson = $this->getOauthAccessToken();

            if ($this->authType == 'snsapi_userinfo') {
                $res = $this->getOauthUserinfo($aJson['access_token'], $aJson['openid']);
                if(!$res)
                    exit('获取用户信息失败');

                $sUrl = $this->stateToUrl(I('state'));
                header('Location:' . $sUrl);exit;
            }else{
                exit('授权类型错误');
            }
        }
        exit('code or state null');
    }

    /**
     * 测试
     */
    public function test(){
        echo $code = I('code');
    }

    /**
     * 通过code获取Access Token
     * @return bool|mixed {access_token,expires_in,refresh_token,openid,scope}
     */
    public function getOauthAccessToken()
    {
        $result = file_get_contents(self::API_BASE_URL_PREFIX . self::OAUTH_TOKEN_URL . 'appid=' . $this->wxConfig['appid'] . '&secret=' . $this->wxConfig['appsecret'] . '&code=' . I('code') . '&grant_type=authorization_code');

        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                exit(json_encode($json));
            }

            if (!isset($json['openid'])) {
                echo '获取openid失败!';
                exit;
            }

            return $json;
        }
        return false;
    }

    /**
     * 获取微信用户信息
     * @param $access_token
     * @param $openid
     * @return bool|mixed|string {openid,nickname,sex,province,city,country,headimgurl,privilege,[unionid]}
     */
    public function getOauthUserinfo($access_token, $openid)
    {
        $result = $this->http_get(self::API_BASE_URL_PREFIX . self::OAUTH_USERINFO_URL . 'access_token=' . $access_token . '&openid=' . $openid);

        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode']) || !$json['unionid']) {
                var_dump($json);exit;
            }
            //保存用户信息
            $this->userInfo = $json;
            session('shake_'.$this->wxConfig['appid'] . '_uid', $json['unionid']);
            $cacheUserInfoKey = 'shake_' . $json['unionid'] . '_userinfo';
            S($cacheUserInfoKey, ['unionid' => $json['unionid'], 'nickname' => $json['nickname']],3600 * 24);

            return $this->userInfo;
        }
        return false;
    }

    /**
     * 将当前链接转换成state
     * @return string
     */
    private function urlToState($url)
    {
        if (strlen($url) > 100) {//长链接转短链接
            $sTinyUrlCode = '__' . md5($url);
            S($sTinyUrlCode, $url, 60);

            return urlencode($sTinyUrlCode);
        } else {
            return urlencode($url);
        }
    }

    /**
     * 将state 转换成 url
     * @param $sState
     * @return mixed|string
     */
    private function stateToUrl($sState)
    {

        $sState = urldecode($sState);
        if (substr($sState, 0, 2) === '__') {//短链接转长链接
            return S($sState);
        } else {
            return $sState;
        }
    }

    /**
     * 设置或获取当前完整URL 包括QUERY_STRING
     * @access public
     * @return string
     */
    public function url()
    {
        if (IS_CLI) {
            $this->url = isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : '';
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $this->url = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'])) {
            $this->url = $_SERVER['REQUEST_URI'];
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $this->url = $_SERVER['ORIG_PATH_INFO'] . (!empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '');
        } else {
            $this->url = '';
        }
        return $this->url;
    }

    /**
     * GET 请求
     * @param string $url
     */
    private function http_get($url){
        $oCurl = curl_init();
        if(stripos($url,"https://")!==FALSE){
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1 );
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if(intval($aStatus["http_code"])==200){
            return $sContent;
        }else{
            return false;
        }
    }
}