<?php
/**
 * 第三方用户登陆
 * @author huangjiezhen <418832673@qq.com> 2016.04.27
 */

class SdkLoginController extends PublicController
{
    protected $AppKey    = '';
    protected $AppSecret = '';
    protected $TokenUri  = '';
    protected $Type      = '';

    //获取对应第三方平台的配置和类型
    public function _initialize()
    {
        parent::_initialize();
        $config = C("APP_THINK_SDK_".strtoupper($this->param['type']));

        if (!$config)
            $this->ajaxReturn(1054);

        $this->AppKey    = $config['APP_KEY'];
        $this->AppSecret = $config['APP_SECRET'];
        $this->TokenUri  = $config['TokenUri'];
        $this->Type      = $this->param['type'];
    }

    //登陆拼装请求的参数
    public function login()
    {
        switch ($this->Type) {
            case 'weixin':
                $params = array(
                    'appid'      => $this->AppKey,
                    'secret'     => $this->AppSecret,
                    'grant_type' => 'authorization_code',
                    'code'       => $this->param['code'],
                );

                $this->doLogin($params,'POST');
                break;

            case 'qq':
                $params = array(
                    'access_token' => $this->param['code'],
                );

                $this->doLogin($params,'GET');
                break;

            case 'sina':
                $this->checkBindSdk($this->param['code']);
                break;

            default:
                $this->ajaxReturn(1053);
                break;
        }
    }

    //执行登陆从第三方平台获取unionid
    public function doLogin($params,$method='POST')
    {
        $data = $this->http($this->TokenUri, $params, $method);

        if ($this->Type == 'qq')
            $data = json_decode(trim(substr($data, 9), " );\n"), true);
        else
            $data = json_decode($data, true);

        switch ($this->Type)
        {
            case 'weixin':  $openKey = 'unionid'; break;
            case 'qq':      $openKey = 'openid'; break;
        }

        if (!isset($data[$openKey]))
            $this->ajaxReturn(1055);

        $this->checkBindSdk($data[$openKey]);
    }

    //检查是否已经绑定账户
    public function checkBindSdk($unionid)
    {
        $sql = "
            SELECT
                `id`,
                `username`,
                `nick_name`,
                `login_time`,
                `point`,
                `status`
            FROM
                `qc_front_user`
            WHERE
                (
                    ".$this->Type.'_unionid'." = '".$unionid."'
                )
            LIMIT 1
        ";
        $userInfo = M()->query($sql)[0];
        //$userInfo = M('FrontUser')->field(['id','username'])->where([$this->Type.'_unionid'=>$unionid])->find();

        if (!$userInfo)
        {
            //设置第三方登陆的token
            $loginToken = md5('login'.time().mt_rand(10000,99999));
            S('loginToken:'.$loginToken,[$this->Type.'_unionid'=>$unionid],C('loginTokenTime'));
            $this->ajaxReturn(['loginToken'=>$loginToken]);
        }

        if ($userInfo['status'] != 1)
            $this->ajaxReturn(1005);

        $msgCode   = $userInfo['nick_name'] ? '' : 1010;
        $loginData = D('FrontUser')->loginData($userInfo['id'],$userInfo['login_time'],$userInfo['point'],$userInfo['username'],$this->param['platform']);
        $this->ajaxReturn($loginData,$msgCode);
    }

    /**
     * 发送HTTP请求方法，目前只支持CURL发送请求
     * @param  string $url 请求URL
     * @param  array $params 请求参数
     * @param  string $method 请求方法GET/POST
     * @return array  $data   响应数据
     */
    protected function http($url, $params, $method = 'GET', $header = array(), $multi = false)
    {
        $opts = array(
            CURLOPT_TIMEOUT => 30,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_HTTPHEADER => $header
        );

        /* 根据请求类型设置特定参数 */
        switch (strtoupper($method)) {
            case 'GET':
                $opts[CURLOPT_URL] = $url . '?' . http_build_query($params);
                break;
            case 'POST':
                //判断是否传输文件
                $params = $multi ? $params : http_build_query($params);
                $opts[CURLOPT_URL] = $url;
                $opts[CURLOPT_POST] = 1;
                $opts[CURLOPT_POSTFIELDS] = $params;
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
}