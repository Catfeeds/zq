<?php
/**
 * 所有版本接口共用类
 * @author dengwj <406516482@qq.com> 2018.09.06
 */
namespace Common\Controller;
use Think\Controller;

class ApiController extends Controller
{
    private $secretKey = 'quancaiappppa';
    public $param = null;
    public $requestTime = null;

    public function _initialize()
    {
        // $this->ajaxReturn(405);    //停止接口访问、提示更新到最新的版本
        $this->requestTime = time(); //请求的时间
        $this->param = getParam(); //获取传入的参数
        $uri = CONTROLLER_NAME.'/'.ACTION_NAME;

        if ($this->param['nosign'] != C('nosignStr') && !in_array(CONTROLLER_NAME,C('nosignUri')) && !in_array($uri, C('nosignUri'))){
            if(!in_array(MODULE_NAME, ['Api','Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400','Api500'])){
                $this->verifyJwtToken();
            }else{
                $this->verifySignature();
            }
        }

        //如果为ios平台，渠道号为pkg
        if($this->param['channel_code'] && $this->param['platform'] == 2){
            $this->param['channel_code'] = $this->param['pkg'];
        }
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
        if (is_array($data) || is_object($data))
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

        $this->requestLog($res); //记录请求log
        parent::ajaxReturn($res,$type);
    }

    /**
     * app记录请求日志
     * @param  array $data 返回的数据
     */
    public function requestLog($data)
    {
        $confg = getWebConfig('appRequest');

        if ($confg['appLogOn'] && in_array(MODULE_NAME,explode(',',$confg['appLogList'])))
        {
            M('ApiRequest')->add([
                'request'       => json_encode(I()),
                'response'      => json_encode($data),
                'request_time'  => $this->requestTime,
                'response_time' => time(),
                'module'        => MODULE_NAME,
                'controller'    => CONTROLLER_NAME,
                'action'        => ACTION_NAME,
            ]);
        }
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

        $user = M('FrontUser')->master(true)->field(['id as userid','username','nick_name','vip_time','predictive_model_vip','coin','unable_coin','head','status','password','platform'])->find($info['userid']);
        //状态是否被禁用
        if ($user['status'] != 1)
            $this->ajaxReturn(1005);

        //是否修改了密码
        if ($user['password'] != $info['password'])
            $this->ajaxReturn(1050);

        $user['user_nick'] = $user['nick_name'];
        $user['balance']   = $user['coin'] + $user['unable_coin'];

        //判断是否ios vip
        $user['is_vip'] = checkVip($user['vip_time']);

        //判断是否预测模型 vip
        $user['is_model_vip'] = checkVip($user['predictive_model_vip']);
        unset($user['status'],$user['password']);
        return $user;
    }

    //校验签名
    public function verifySignature()
    {
        //验证参数和请求的时间
        if (!$this->param['t'] || !$this->param['sign'] || $this->param['t'] < time() - 3600 || $this->param['t'] > time() + 3600)
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
    /* 空操作，用于输出404页面 */
    // public function _empty(){
    //     $this->ajaxReturn(404);
    // }

    /**
     * 校验签名
     */
    public function verifyJwtToken()
    {
        $authorization = explode(" ", $_SERVER['HTTP_AUTHORIZATION']);
        if (!$authorization[0] == "Bearer")
            $this->ajaxReturn(427, "Authorization认证token_type错误");

        $verifyStatus = D("Jwt")->verifyJwt($authorization[1]);
        if ($verifyStatus["statusCode"] == 427 || $verifyStatus["statusCode"] == 500) {
            $this->ajaxReturn(427, $verifyStatus["codeMessage"]);
        }
    }
}


 ?>