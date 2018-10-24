<?php
/**
 * 接口共用类
 * @author knight <39383198@qq.com> 2016.12.15
 */

use Think\Controller;

class AppverifyController extends Controller
{
    private $secretKey = 'quancaiappppa';
    public $param = null;


    public function _initialize()
    {
        $this->param = getParam(); //获取传入的参数

        if ($this->param['nosign'] != C('nosignStr'))
        {
            $this->verifySignature();  //校验签名
        }
    }

    /**
     * 返回接口数据
     * @param  array/int    $data       要返回的数据
     * @param  int          $msgCode    指定提示信息的状态码
     * @param  string       $type       返回数据的格式 json xml...
     */
    function ajaxReturn($data,$msgCode='',$type='')
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

        $msgCode = $msgCode ?: $code;
		parent::ajaxReturn($data,$type);
       /*  parent::ajaxReturn([
            'code' => $msgCode,
            'time' => time(),
            'msg'  => C('errorCode')[$msgCode],
            'data' => $data
        ],$type); */
    }

    //校验签名
    public function verifySignature()
    {
        //验证参数和请求的时间
        if (!$this->param['t'] || !$this->param['sign'] || $this->param['t'] < time() - 300 || $this->param['t'] > time() + 60)
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>101,'data'=>null));

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
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>403,'data'=>null));
    }
}


 ?>