<?php
/**
 * 接口共用类
 * @author huangjiezhen <418832673@qq.com> 2015.12.15
 */

use Think\Controller;

class PublicController extends Controller
{
    private $secretKey = 'quancaiappppa';
    public $param = null;


    public function _initialize()
    {
        // $this->ajaxReturn(405);    //停止接口访问、提示更新到最新的版本

        $this->param = getParam(); //获取传入的参数

        $uri = CONTROLLER_NAME.'/'.ACTION_NAME;

        if ($this->param['nosign'] != C('nosignStr') && !in_array(CONTROLLER_NAME,C('nosignUri')) && !in_array($uri, C('nosignUri')) && $this->param['from_pay'] != 'from_pay')
            $this->verifySignature();  //校验签名
    }

    /**
     * 返回接口数据
     * @param  array/int    $data       array:要返回的数据, int:错误状态码
     * @param  int/string   $msgCode    int:指定提示信息的状态码, string:直接显示错误信息
     * @param  int          $debug      指定提示信息的状态码
     * @param  string       $type       返回数据的格式 json xml...
     */
    function ajaxReturn($data,$msgCode='',$debug='',$type='')
    {
        if (is_array($data))
        {
            $code = 200;
        }
        else
        {
            $code = $data;
            $data = '';
        }

        $msgCode    = $msgCode ?: $code;
        $msgContent = is_int($msgCode) ? C('errorCode')[$msgCode] : $msgCode;

        //充值限制
        if($msgCode == 5005){
            $rechargeLimit = getWebConfig('common')['rechargeLimit'];
            $msgContent    = str_replace('*', $rechargeLimit, $msgContent);
        }

        $res = [
            'code'  => $code,
            'time'  => time(),
            'msg'   => $msgContent,
            'debug' => $debug,
            'data'  => $data
        ];

        parent::ajaxReturn($res,$type);
    }

    //获取用户的信息
    public function getInfo()
    {
        //是否登陆
        if (!$info = getUserToken($this->param['userToken']))
            $this->ajaxReturn(1001);

        //已经被其他终端登陆
        if ($info == -1)
            $this->ajaxReturn(1051);

        $user = M('FrontUser')->master(true)->field(['nick_name','coin','unable_coin','head','status','password'])->find($info['userid']);
        //状态是否被禁用
        if ($user['status'] != 1)
            $this->ajaxReturn(1005);

        //是否修改了密码
        if ($user['password'] != $info['password'])
            $this->ajaxReturn(1050);

        $info['nick_name'] = $user['nick_name'];
        $info['balance']   = $user['coin'] + $user['unable_coin'];
        $info['head']      = $user['head'];
        return $info;
    }

    //校验签名
    public function verifySignature()
    {
        //验证参数和请求的时间
        if (!$this->param['t'] || !$this->param['sign'] || $this->param['t'] < time() - 300 || $this->param['t'] > time() + 60)
            $this->ajaxReturn(101);

        //验证签名
        import('Vendor.Signature.SignatureHelper');
        $signObj = new \SignatureHelper();

        $params = array();

        foreach ($this->param as $key => $value)
        {
            if($key != 'sign' && strpos($key, '/') === false && $value !== '' && $value !== false)
            {
                $params[$key] = $signObj->urlDecode($value);
            }
        }

        if(!$signObj->verifySignature($params, $this->param['sign'], $this->secretKey))
            $this->ajaxReturn(403);
    }

    /**
     * 获取天气信息，默认根据ip获取
     */
    public function  getIpWeather(){
        $city     = I('city','');
        $citycode = I('citycode','');
        $cityid   = I('cityid','');
        $ip       = I('ip','');
        $location = I('location','');
        //默认以ip获取
        if(empty($city) && empty($citycode) && empty($cityid) && empty($ip)){
            $ip = get_client_ip();
            $expIp = explode('.', $ip);
            if ($ip == '127.0.0.1' || ($expIp[0].'.'.$expIp[1] == '192.168')) {
                $ip = '14.147.145.12'; //广州
            }
        }
        if(I('show')){
            dump($ip);
            die;
        }
        $param = [];
        if($city)     $param['city'] = $city;
        if($citycode) $param['citycode'] = $citycode;
        if($cityid)   $param['cityid'] = $cityid;
        if($ip)       $param['ip'] = $ip;
        if($location) $param['location'] = $location;

        $querys = http_build_query($param);
        //获取天气
        $info = getWeather($querys);
        $this->ajaxReturn($info);
    }
    /* 空操作，用于输出404页面 */
    // public function _empty(){
    //     $this->ajaxReturn(404);
    // }
}


 ?>