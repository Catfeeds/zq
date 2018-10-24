<?php
/**
 * 第三方用户登陆
 */

class SdkLoginController extends PublicController
{
    protected $AppKey       = '';
    protected $AppSecret    = '';
    protected $TokenUri     = '';
    protected $Type         = '';
    protected $Config;

    /**
     * 获取对应第三方平台的配置和类型
     */
    public function _initialize()
    {
        parent::_initialize();

        $config = C("WEIXIN_THINK_SDK_WEIXIN");

        if (!$config)
            $this->ajaxReturn(1054);

        $this->Config       = $config;
        $this->AppKey       = $config['APP_KEY'];
        $this->AppSecret    = $config['APP_SECRET'];
        $this->TokenUri     = $config['TokenUri'];
        $this->GrantType    = 'authorization_code';
        $this->Type         = 'WEIXIN';

    }

    public function wxLogin() {
        if (empty($this->param['code']) || empty($this->param['rawData']) || empty($this->param['signature']) || empty($this->param['encryptedData']) || empty($this->param['iv']))
            $this->ajaxReturn(1054);

//        logRecord(date('Y-m-d H:i:s').'_小程序参数：'.json_encode($this->param), 'log_wx.txt');

        /**
         * 3.小程序调用server获取token接口, 传入code, rawData, signature, encryptData.
         */
        $code          = $this->param['code'] ? htmlspecialchars_decode($this->param['code']) : '';
        $rawData       = $this->param['rawData'] ? htmlspecialchars_decode($this->param['rawData']) : '';
        $signature     = $this->param['signature'] ? htmlspecialchars_decode($this->param['signature']) : '';
        $encryptedData = $this->param['encryptedData'] ? htmlspecialchars_decode($this->param['encryptedData']) : '';
        $iv            = $this->param['iv'] ? htmlspecialchars_decode($this->param['iv']) : '';

        /**
         * 4.server调用微信提供的jsoncode2session接口获取openid, session_key, 调用失败应给予客户端反馈
         * , 微信侧返回错误则可判断为恶意请求, 可以不返回. 微信文档链接
         * 这是一个 HTTP 接口，开发者服务器使用登录凭证 code 获取 session_key 和 openid。其中 session_key 是对用户数据进行加密签名的密钥。
         * 为了自身应用安全，session_key 不应该在网络上传输。
         * 接口地址："https://api.weixin.qq.com/sns/jscode2session?appid=APPID&secret=SECRET&js_code=JSCODE&grant_type=authorization_code"
         */
        $params = [
            'appid'      => $this->AppKey,
            'secret'     => $this->AppSecret,
            'js_code'    => $code,
            'grant_type' => $this->GrantType
        ];

        $res = $this->getAccessToken($params);

        if ($res['errcode'])
            $this->ajaxReturn($res, 'requestTokenFailed');

        if (!isset($res['session_key']))
            $this->ajaxReturn($res, 'session_keyFailed');

        $sessionKey = $res['session_key'];

        /**
         * 5.server计算signature, 并与小程序传入的signature比较, 校验signature的合法性, 不匹配则返回signature不匹配的错误. 不匹配的场景可判断为恶意请求, 可以不返回.
         * 通过调用接口（如 wx.getUserInfo）获取敏感数据时，接口会同时返回 rawData、signature，其中 signature = sha1( rawData + session_key )
         *
         * 将 signature、rawData、以及用户登录态发送给开发者服务器，开发者在数据库中找到该用户对应的 session-key
         * ，使用相同的算法计算出签名 signature2 ，比对 signature 与 signature2 即可校验数据的可信度。
         */
        $signature2 = sha1($rawData.$sessionKey);

        if ($signature2 !== $signature)
            $this->ajaxReturn('signNotMatch');

        /**
         *
         * 6.使用第4步返回的session_key解密encryptData, 将解得的信息与rawData中信息进行比较, 需要完全匹配,
         * 解得的信息中也包括openid, 也需要与第4步返回的openid匹配. 解密失败或不匹配应该返回客户相应错误.
         * （使用官方提供的方法即可）
         */
        Vendor('wxdecode.wxBizDataCrypt');
        $pc = new WXBizDataCrypt($this->AppKey, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);

        if ($errCode !== 0)
            $this->ajaxReturn($errCode, 'encryptDataNotMatch');

        /**
         * 7.生成第三方3rd_session，用于第三方服务器和小程序之间做登录态校验。为了保证安全性，3rd_session应该满足：
         * a.长度足够长。建议有2^128种组合，即长度为16B
         * b.避免使用srand（当前时间）然后rand()的方法，而是采用操作系统提供的真正随机数机制，比如Linux下面读取/dev/urandom设备
         * c.设置一定有效时间，对于过期的3rd_session视为不合法
         *
         * 以 $session3rd 为key，sessionKey+openId为value，写入memcached
         */
        $data = json_decode($data, true);
        $session3rd = $this->randomFromDev(16);

        $data['session3rd'] = $session3rd;
        S($session3rd, $data['openId'].$sessionKey, C('loginLifeTime'));

        return $data;
    }

    /**
     * 登录、绑定登录
     */
    public function login()
    {
//        $this->param['rawData'] = '{"nickName":"Band","gender":1,"language":"zh_CN","city":"Guangzhou","province":"Guangdong","country":"CN","avatarUrl":"http://wx.qlogo.cn/mmopen/vi_32/1vZvI39NWFQ9XM4LtQpFrQJ1xlgZxx3w7bQxKARol6503Iuswjjn6nIGBiaycAjAtpujxyzYsrztuuICqIM5ibXQ/0"}';
//        if (empty($this->param['code']) || empty($this->param['rawData']))
//            $this->ajaxReturn(1054);

        $token = $this->wxLogin();

        if (!$token['unionId'])
            $this->ajaxReturn(1062, '登录失败!', $token);

        $sdk_array  = ['weixin_unionid' => $token['unionId']];//用户微信唯一

        $userInfo = M("FrontUser")->master(true)->field('id, username, head, nick_name, status, point')->where($sdk_array)->find();

        if (isset($userInfo['status']) && $userInfo['status'] != 1)
            $this->ajaxReturn(1005);

        $msgCode = '';
        $sdkInfo = json_decode($this->param['rawData'], true);//用户信息

        if ($userInfo) {//已登录过
            $sdk_array['login_time'] = NOW_TIME;
            $loginData = D('FrontUser')->loginData($userInfo['id'], $userInfo['login_time'], $userInfo['point'], $userInfo['username'], 5);
/*
            foreach ($loginData[0] as $key => $value) {
                $loginData[0][$key] = (string)$value;
            }
            $loginData[0]['sdk_nickname'] = (string)$sdkInfo['nickName'];
            $loginData[0]['first_login'] = '0';
*/
        } elseif (!$userInfo) {//未执行过登录，注册：
            if (!$ip = D('FrontUser')->checkReg())
                $this->ajaxReturn(1075);

            $sdk_array['nick_name'] = $sdkInfo['nickName'];
            $sdk_array['platform']  = 5;
            $sdk_array['reg_time']  = NOW_TIME;
            $sdk_array['reg_ip']    = get_client_ip();

            $userid = M('FrontUser')->add($sdk_array);

            if (!$userid)
                $this->ajaxReturn(1062);

            $loginData = D('FrontUser')->loginData($userid, 0, 0, '', 5);
/*
            foreach ($loginData[0] as $key => $value) {
                $loginData[0][$key] = (string)$value;
            }

            $loginData[0]['face'] = $this->saveHeadImg($sdkInfo['avatarUrl'], $userid);
            $loginData[0]['sdk_nickname'] = (string)utf8_filter($sdkInfo['nickName']);

            $loginData[0]['first_login'] = '1';
*/
            $this->saveHeadImg($sdkInfo['avatarUrl'], $userid);
        }

        echo $loginData;exit;
    }

    /**
     * 根据客户端传的code对应调用api
     * @param $params
     * @param string $method
     * @return mixed
     * @throws Exception
     */
    public function getAccessToken($params, $method = 'POST')
    {
        $data = $this->http($this->TokenUri, $params, $method);
        $data = json_decode($data, true);

        return $data;
    }

    /**
     * 保存用户第三方头像
     * @param $image_file
     * @param $userid
     * @return mixed
     */
    public function saveHeadImg($image_file = '', $userid)
    {
        if ($image_file) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $image_file);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//以数据流的方式返回数据,当为false是直接显示出来
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $res = curl_exec($ch);
            $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            curl_close($ch);

            $base64_image_content = "data:{$content_type};base64," . chunk_split(base64_encode($res));
            $result = D('Uploads')->uploadFileBase64($base64_image_content, "user", "face", $userid, $userid);

            M("frontUser")->where(['id' => $userid])->save(['head' => $result['url']]);
        } else {
            $result['url'] = '';
        }

        return frontUserFace($result['url']);
    }

    /**
     * 保存用户的第三方昵称
     * @param $userid
     * @param $sdkNick
     * @return string
     */
    public function saveNickName($sdkNick = '')
    {
        $nickPre = '';
        switch ($this->Type) {
            case 'weixin':
                $nickPre = 'wx';
                break;
        }

        //过滤掉昵称中的特殊字符
        if (preg_match_all("/([{\x{4e00}-\x{9fa5}]|[0-9a-zA-Z\.\_\-\=\!\^\&\*\(\)\~])+/u", $sdkNick, $matchs)) {
            $nickStr = implode('', $matchs[0]);
            if (mb_strlen($nickStr, 'utf-8') > 10) {
                $nickStr = mb_substr($nickStr, 0, 10, 'utf-8');
            }
        } else {
            $nickStr = $nickPre . '_' . GetRandStr(7);
        }

        return $nickStr;
    }


    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param $url 请求URL
     * @param $params 请求参数
     * @param string $method 请求参数
     * @param array $header 请求方法GET/POST
     * @param bool|false $multi
     * @return mixed 响应数据
     * @throws Exception
     */
    protected function http($url, $params, $method = 'GET', $header = array(), $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT         => 30,
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_SSL_VERIFYPEER  => false,
            CURLOPT_SSL_VERIFYHOST  => false,
            CURLOPT_HTTPHEADER      => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL]          = $url;
                $opts[CURLOPT_POST]         = 1;
                $opts[CURLOPT_POSTFIELDS]   = $params;
                break;
            default:
                throw new Exception('不支持的请求方式！');
        }

        /* 初始化并执行curl请求 */
        $ch = curl_init();
        curl_setopt_array($ch, $opts);
        $data = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        if ($error)
            throw new Exception('请求发生错误：' . $error);
        return $data;
    }

    /**
     * 读取/dev/urandom获取随机数
     * @param $len
     * @return mixed|string
     */
    function randomFromDev($len) {
        $fp = @fopen('/dev/urandom','rb');
        $result = '';
        if ($fp !== FALSE) {
            $result .= @fread($fp, $len);
            @fclose($fp);
        }
        else
        {
            trigger_error('Can not open /dev/urandom.');
        }
        // convert from binary to string
        $result = base64_encode($result);
        // remove none url chars
        $result = strtr($result, '+/', '-_');

        return substr($result, 0, $len);
    }

}