<?php
/**
 * 微信授权控制器
 */
use Think\Tool\Tool;

class WxOauthController extends \Think\Controller
{
    const OAUTH_PREFIX = 'https://open.weixin.qq.com/connect/oauth2';
    const OAUTH_AUTHORIZE_URL = '/authorize?';
    const API_BASE_URL_PREFIX = 'https://api.weixin.qq.com';
    const OAUTH_TOKEN_URL = '/sns/oauth2/access_token?';
    const OAUTH_USERINFO_URL = '/sns/userinfo?';

    private $url = '';
    private $wxConfig;
    private $authType = 'snsapi_userinfo';
    private $domain = 'https://m.qqty.com/';
    private $userInfo;

    public function _initialize()
    {
        $wxconfig = C('wxpay.wxpay_config');
        $this->wxConfig = $wxconfig;
        $this->domain = 'https://m.' . DOMAIN;
    }

    public function test(){
        $this->oauth(2);
    }

    /**
     * 授权调用
     * @param int $mode
     */
    public function oauth($mode = 0)
    {
        if($mode){
            $urlToState = $this->urlToState('http://m.' . DOMAIN . $this->url());
            $callback = 'https://m.qqty.com/wxOauth/callback/mode/2.html';
        }else{
            $urlToState = $this->urlToState('https://m.' . DOMAIN . $this->url());
            $callback = U('wxOauth/callback');
        }

        $sUrl = $this->getOauthRedirect($callback, $urlToState);
        redirect($sUrl);
    }

    /**
     * 微信授权登录
     */
    public function wxLogin()
    {
        $unionid = session('wx_oauth_' . $this->wxConfig['appid'] . '_uid');

        //网站登录注册
        $user_id = M("FrontUser")->where(['weixin_unionid' => $unionid])->getField('id');
        if ($user_id) {
            D('FrontUser')->autoLogin($user_id);
        } else {
            //自动注册
            $wxUserInfo = S('wx_oauth_' . $unionid . '_userinfo');//昵称
            $nickname = $wxUserInfo['nickname'];
            $length = $length = mb_strlen($nickname, 'utf-8');

            //昵称长度小于2或者大于10，则生成随机昵称
            if ($length < 2 || $length > 8) {
                $nickname = 'wx_' . GetRandStr(6);
            }

            if (M('FrontUser')->where(['nick_name' => $nickname])->getField('nick_name')) {
                $nickname = $nickname . GetRandStr(2);
            }

            $addData['nick_name'] = $nickname;

            $addData['platform'] = '4';
            $addData['channel_code'] = 'm';
            $addData['reg_time'] = time();
            $addData['reg_ip'] = get_client_ip();
            $addData['weixin_unionid'] = $unionid;

            $user_id = M('frontUser')->add($addData);
            if ($user_id !== false) {
                D('FrontUser')->autoLogin($user_id);
            }
        }
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
            if(I('mode')){
                $sUrl = 'http://m.bobobong.com' . $this->url();
                $new_url = str_replace('mode', 'mode_', $sUrl);
                header('Location:' . $new_url);
                exit;
            }else{
                $aJson = $this->getOauthAccessToken();
                if ($this->authType == 'snsapi_userinfo') {
                    $res = $this->getOauthUserinfo($aJson['access_token'], $aJson['openid']);


                    if (!$res)
                        exit('获取用户信息失败');

                    //执行微信登录
                    $this->wxLogin();

                    $sUrl = $this->stateToUrl(I('state'));
                    header('Location:' . $sUrl);
                    exit;
                } else {
                    exit('授权类型错误');
                }
            }

        }
        exit('code or state null');
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
                var_dump($json);
                exit;
            }
            //保存用户信息
            $this->userInfo = $json;
            session('wx_oauth_' . $this->wxConfig['appid'] . '_uid', $json['unionid']);
            $cacheUserInfoKey = 'wx_oauth_' . $json['unionid'] . '_userinfo';
            S($cacheUserInfoKey, ['unionid' => $json['unionid'], 'nickname' => $json['nickname']], 3600 * 24);

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
     *  GET 请求
     * @param $url
     * @return bool|mixed
     */
    private function http_get($url)
    {
        $oCurl = curl_init();
        if (stripos($url, "https://") !== FALSE) {
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($oCurl, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($oCurl, CURLOPT_SSLVERSION, 1); //CURL_SSLVERSION_TLSv1
        }
        curl_setopt($oCurl, CURLOPT_URL, $url);
        curl_setopt($oCurl, CURLOPT_RETURNTRANSFER, 1);
        $sContent = curl_exec($oCurl);
        $aStatus = curl_getinfo($oCurl);
        curl_close($oCurl);
        if (intval($aStatus["http_code"]) == 200) {
            return $sContent;
        } else {
            return false;
        }
    }
}