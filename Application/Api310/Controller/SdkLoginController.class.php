<?php
/**
 * 第三方用户登陆
 * @author huangzl <496331832@qq.com> 2016.07.25
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

        if ($this->param['platform'] == 2)
            $pkg = $this->param['pkg'] != '' && $this->param['pkg'] != 'company' ? strtoupper($this->param['pkg']) . '_' : '';
        else
            $pkg = '';

        $config = C($pkg . "APP_THINK_SDK_" . strtoupper($this->param['type']));

        if (!$config)
            $this->ajaxReturn(1054);

        $this->Config       = $config;
        $this->AppKey       = $config['APP_KEY'];
        $this->AppSecret    = $config['APP_SECRET'];
        $this->TokenUri     = $config['TokenUri'];
        $this->Type         = $this->param['type'];

    }

    /**
     * 第三方授权登录、绑定登录
     */
    public function login()
    {
        //判断IP黑名单
        if(checkShieldIp()) $this->ajaxReturn(401);

        if (empty($this->Type) || empty($this->param['code']))
            $this->ajaxReturn(1054);

        $sdk_array = $token = $binds = [];

        switch ($this->Type) {
            case 'qq':
                $params['access_token'] = $this->param['code'];//access_token
                $token = array_merge($params, $this->getAccessToken($params, 'GET'));

                if ($token['error'])
                    $this->ajaxReturn(1062, '第三方登录失败', $token['error_description']);

                $sdk_array  = ['qq_unionid' => $token['openid']];
                break;

            case 'weixin':
                $params = array(
                    'appid'     => $this->AppKey,
                    'secret'    => $this->AppSecret,
                    'grant_type'=> 'authorization_code',
                    'code'      => $this->param['code'],//code
                );
                $token = $this->getAccessToken($params);

                if ($token['errcode'])
                    $this->ajaxReturn(1062, '第三方登录失败', $token['errmsg']);

                $sdk_array  = ['weixin_unionid' => $token['unionid']];
                break;

            case 'sina':
                $params['access_token'] = $this->param['code'];//access_token
                $token = array_merge($params, $this->getAccessToken($params));

                if ($token['error_code'])
                    $this->ajaxReturn(1062, '第三方登录失败', $token['error']);

                $sdk_array  = ['sina_unionid' => $token['uid']];
                break;

            case 'mm':
                $sdk_array  = ['mm_unionid' => $this->param['code']];
                break;
        }

        //绑定第三方账户
        if ($this->param['userToken'])
            $this->bindSdkUser($sdk_array);

        $userInfo = M("FrontUser")->master(true)->field('id, username, head, nick_name, status, point')->where($sdk_array)->find();

        if (isset($userInfo['status']) && $userInfo['status'] != 1)
            $this->ajaxReturn(1005);

        $msgCode = $userInfo['nick_name'] ? '' : 1010;

        if ($userInfo) {//已登录过
            $sdk_array['login_time'] = NOW_TIME;
            $loginData = D('FrontUser')->loginData($userInfo['id'], $userInfo['login_time'], $userInfo['point'], $userInfo['username'], $this->param['platform']);
            foreach ($loginData['userInfo'] as $key => $value) {
                $loginData['userInfo'][$key] = (string)$value;
            }
            $loginData['userInfo']['sdk_nickname'] = '';
            $loginData['userInfo']['first_login'] = '0';

            $this->ajaxReturn($loginData, $msgCode);

        } elseif (!$userInfo) {//未执行过第三方平台登录
            if (!$ip = D('FrontUser')->checkReg())
                $this->ajaxReturn(1075);

            if (empty($this->param['channel_code']))
                $this->ajaxReturn(101);

            $sdkInfo = $this->getSdkUserInfo($token);

            $sdk_array['platform']      = $this->param['platform'];
            $sdk_array['reg_time']      = NOW_TIME;
            $sdk_array['reg_ip']        = get_client_ip();
            $sdk_array['channel_code']  = $this->param['channel_code'];

            $userid = M('FrontUser')->add($sdk_array);

            if (!$userid)
                $this->ajaxReturn(1062);

            //增加注册赠送金币
            D('FrontUser')->loginGift($userid,$this->param['platform']);

            $loginData = D('FrontUser')->loginData($userid, 0, 0, $userInfo['username'], $this->param['platform']);

            foreach ($loginData['userInfo'] as $key => $value) {
                $loginData['userInfo'][$key] = (string)$value;
            }

            $loginData['userInfo']['face'] = $this->saveHeadImg($sdkInfo['head'], $userid);
            $loginData['userInfo']['sdk_nickname'] = (string)utf8_filter($sdkInfo['nick']);

            $loginData['userInfo']['first_login'] = '1';

            $this->ajaxReturn($loginData, $msgCode);
        }

    }

    /**
     * 获取第三方平台的头像昵称
     * @param
     * @param string $token
     * @return array
     */
    public function getSdkUserInfo($token = '')
    {
        switch ($this->Type) {

            case 'qq':
                $apiUrl = 'https://graph.qq.com/user/get_user_info';
                $params = [ 'format' => 'json', 'openid' => $token['openid'], 'access_token' => $token['access_token'], 'oauth_consumer_key'=> $token['client_id']];

                $data = json_decode($this->http($apiUrl, $params, 'GET'), true);
                if ($data['ret'] == 0) {
                    $userInfo['type'] = 'QQ';
                    $userInfo['name'] = $data['nickname'];
                    $userInfo['nick'] = $data['nickname'];
                    $userInfo['head'] = $data['figureurl_qq_2'];
                } else {
                    $this->ajaxReturn(1062, '第三方登录失败', $data['msg']);
                }
                break;

            case 'weixin':
                $apiUrl = 'https://api.weixin.qq.com/sns/userinfo';
                $data = json_decode($this->http($apiUrl, $token, 'GET'), true);

                if ($data['errcode'])
                    $this->ajaxReturn(1062, '第三方登录失败', $data['errmsg']);

                $userInfo['type']   = 'WEIXIN';
                $userInfo['name']   = $data['nickname'];
                $userInfo['nick']   = $data['nickname'];
                $userInfo['head']   = $data['headimgurl'];
                $userInfo['unionid']= $data['unionid'];
                break;

            case 'sina':

                $apiUrl = 'https://api.weibo.com/2/users/show.json';
                $data = json_decode($this->http($apiUrl, $token, 'GET'), true);

                if ($data['error_code'] == 0) {
                    $userInfo['type'] = 'SINA';
                    $userInfo['name'] = $data['name'];
                    $userInfo['nick'] = $data['screen_name'];
                    $userInfo['head'] = $data['avatar_large'];
                } else {
                    $this->ajaxReturn(1062, '第三方登录失败', $data['error']);
                }
                break;

            default;
        }
        return $userInfo;
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
        if ($this->Type == 'qq')
            $data = json_decode(trim(substr($data, 9), " );\n"), true);//返回的是client_id和openid
        else
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
            case 'qq':
                $nickPre = 'qq';
                break;
            case 'sina':
                $nickPre = 'sn';
                break;
            case 'mm':
                $nickPre = 'mm';
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
     * 第三方账号绑定
     * @param $sdk_array
     */
    public function bindSdkUser($sdk_array)
    {
        $userToken = getUserToken($this->param['userToken']);
        if (!$userToken['userid']) $this->ajaxReturn(1001);

        $userModel = M('FrontUser');
        $sdkInfo1 = $userModel->where($sdk_array)->getField('id');
        $sdkInfo2 = $userModel->where(['id' => $userToken['userid']])->getField(key($sdk_array));

        if (($sdkInfo1 && $sdkInfo1 != $userToken['userid']))
            $this->ajaxReturn(1057);

        if ($sdkInfo2)
            $this->ajaxReturn(1063);

        if ($userModel->where(['id' => $userToken['userid']])->save($sdk_array) === false)
            $this->ajaxReturn(1058);

        $this->ajaxReturn(['bindResult' => 1]);
    }

}