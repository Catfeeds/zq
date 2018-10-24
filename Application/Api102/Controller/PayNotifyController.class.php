<?php
/**
 * app支付回调接口类
 * @author Hmg<huangmg@qc.mail> 2016.03.25
 */

use Think\Controller;

class PayNotifyController extends Controller
{
     /**
     * app支付宝回调接口
     * @param  string    success/fail
     */
    public function notifyAlipay()
    {
        vendor('Payment.Alipay.lib.Corefunction');
        vendor('Payment.Alipay.lib.Rsafunction');
        vendor('Payment.Alipay.lib.Notify');

        $alipay_config = C('appalipay')['alipay_config'];
        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if($verify_result)
        {//验证成功
            $trade_status = $_POST['trade_status'];      //订单状态
            $out_trade_no = $_POST['out_trade_no'];      //商户网站唯一订单号
            $alipay_no = $_POST['trade_no'];             //支付宝交易号
            $buyer_id = $_POST['buyer_id'];              //买家支付宝账号对应的支付宝唯一用户号。以2088开头的纯16位数字
            $buyer_email = $_POST['buyer_email'];        //买家支付宝账号，可以是Email或手机号码。
            $seller_id = $_POST['seller_id'];            //卖家支付宝账号对应的支付宝唯一用户号。以2088开头的纯16位数字
            $seller_email = $_POST['seller_email'];      //买家支付宝账号，可以是Email或手机号码。
            $total_fee = $_POST['total_fee'];            //交易金额
            $gmt_create = strtotime($_POST['gmt_create']);             //交易创建时间
            $gmt_payment = strtotime($_POST['gmt_payment']);           //交易付款时间
            $where = array('trade_no'=>$out_trade_no);
            $trade = M('TradeRecord');
            switch($trade_status)
            {
                case 'TRADE_SUCCESS':
                    #支付成功————交易状态TRADE_SUCCESS的通知触发条件是商户签约的产品支持退款功能的前提下，买家付款成功
                    $data = [
                        'trade_no'                  => $out_trade_no,
                        'alipay_trade_no'           => $alipay_no,
                        'seller_email'              => $seller_email,
                        'buyer_id'                  => $buyer_id,
                        'buyer_email'               => $buyer_email,
                        'pay_fee'                   => $total_fee,
                        'trade_state'               => 1,
                        'ctime'                     => $gmt_create,
                        'etime'                     => $gmt_payment,
                    ];
                    $res = $trade->field('*')->where($where)->find();
                    if(!empty($res))
                    {
                        if($res['trade_state'] == 1)
                        {
                            $flag = ExecutiveRecharge($out_trade_no);
                            if($flag === false)
                                echo 'fail';
                            else
                                echo "success";
                            exit;
                        }
                        if($res['seller_email'] != $seller_email || $res['total_fee'] != $total_fee) $data['status'] = 0 ;
                        $trade->where($where)->save($data);
                    }
                    else
                    {
                        $data['user_id'] = 0 ;
                        $data['total_fee'] = $total_fee;
                        $data['title'] = $_POST['subject'];
                        $data['description'] = $_POST['body'];
                        $data['platform'] = 0;
                        $data['status'] = 0;
                        $trade->add($data);
                    }
                    $flag = ExecutiveRecharge($out_trade_no);
                    if($flag === false)
                    {
                        logResult("金币添加不成功：".$out_trade_no);
                        echo 'fail';
                        exit;
                    }
                    logResult("交易成功：".json_encode($_POST));
                    break;
                case 'WAIT_BUYER_PAY':
                    #交易创建
                    $res = $trade->field('*')->where($where)->find();
                    $data = [
                        'trade_no'                   => $out_trade_no,
                        'alipay_trade_no'            => $alipay_no,
                        'seller_email'               => $seller_email,
                        'buyer_id'                   => $buyer_id,
                        'buyer_email'                => $buyer_email,
                        'pay_fee'                    => $total_fee,
                        'title'                      => $_POST['subject'],
                        'description'                => $_POST['body'],
                        'trade_state'                => 0,
                        'ctime'                      => $gmt_create,
                    ];
                    if(!empty($res))
                    {
                        $trade->where($where)->save($data);
                    }
                    else
                    {
                        $data['total_fee'] = $total_fee;
                        $data['platform'] = 0;
                        $data['status'] = 0;
                        $trade->add($data);
                    }
                    break;
                case 'RADE_FINISHED':
                    #交易成功————交易状态TRADE_FINISHED的通知触发条件是商户签约的产品不支持退款功能的前提下，买家付款成功；或者，商户签约的产品支持退款功能的前提下，交易已经成功并且已经超过可退款期限；
                    $res = $trade->field('*')->where($where)->find();
                    $data = [
                        'trade_no'               => $out_trade_no,
                        'alipay_trade_no'        => $alipay_no,
                        'seller_email'           => $seller_email,
                        'buyer_id'               => $buyer_id,
                        'buyer_email'            => $buyer_email,
                        'pay_fee'                => $total_fee,
                        'trade_state'            => 2,
                        'ctime'                  => $gmt_create,
                        'etime'                  => $gmt_payment,
                    ];
                    if(!empty($res))
                    {
                        if($res['trade_state'] == 2)
                        {
                            $flag = ExecutiveRecharge($out_trade_no);
                            if($flag === false)
                                echo 'fail';
                            else
                                echo "success";
                            exit;
                        }
                        if($res['seller_email'] != $seller_email || $res['total_fee'] != $total_fee) $data['status'] = 0 ;
                        $trade->where($where)->save($data);
                    }
                    else
                    {
                        $data['user_id'] = 0 ;
                        $data['total_fee'] = $total_fee;
                        $data['title'] = $_POST['subject'];
                        $data['description'] = $_POST['body'];
                        $data['platform'] = 0;
                        $data['status'] = 0;
                        $trade->add($data);
                    }
                    $flag = ExecutiveRecharge($out_trade_no);
                    if($flag === false)
                    {
                        logResult("金币添加不成功：".$out_trade_no);
                        echo 'fail';
                        exit;
                    }
                    logResult("交易成功：".json_encode($_POST));
                    break;
                default:
                    break;
            }
            echo "success";     //请不要修改或删除
        }
        else {
            //验证失败
            echo "fail";
            $logs = json_encode($_POST);
            //调试用，写文本函数记录程序运行情况是否正常
            logResult("验证失败：".$logs);
        }
    }

    /**
     * app微信回调接口
     * @param  string    success/fail
     */
    public function notifyWxpay()
    {
        $xmlStr = file_get_contents('php://input');

        vendor('Payment.Alipay.lib.Corefunction');
        vendor('Payment.Wxpay.lib.WxpayService');
        $WxService = new WxpayService();

        $xmlArr = $WxService->xmlToArray($xmlStr);   //xml转换数组
        $sXml = '<xml>
                  <return_code><![CDATA[SUCCESS]]></return_code>
                  <return_msg><![CDATA[OK]]></return_msg>
                </xml>';
        $fXml = '<xml>
                  <return_code><![CDATA[FAIL]]></return_code>
                  <return_msg><![CDATA[FAIL]]></return_msg>
                </xml>';

        if(isset($xmlArr['return_code']) && $xmlArr['return_code'] == 'SUCCESS')
        {
            vendor('Payment.Wxpay.lib.WxPayApi');
            vendor('Payment.Wxpay.lib.WxPayData');
            vendor('Payment.Wxpay.lib.WxPayConfig');
            vendor('Payment.Wxpay.lib.WxPayException');
            vendor('Payment.Wxpay.lib.WxPayNotify');
            $WxPayApi = new WxPayApi();

            if($WxPayApi->notify($this->backCall()) === false)
            {
                $xml = '<xml>
                          <return_code><![CDATA[FAIL]]></return_code>
                          <return_msg><![CDATA[sign no ok]]></return_msg>
                        </xml>';
                echo $xml;
                //调试用，写文本函数记录程序运行情况是否正常
                logResult("签名验证失败：".json_encode($xmlArr),'logWx.txt');
                exit;
            }

            $mch_id = $xmlArr['mch_id'];      //商户网站唯一订单号
            $total_fee = $xmlArr['total_fee']/100;            //交易金额
            $where = array('trade_no'=>$xmlArr['out_trade_no']);

            $trade = M('TradeRecord');
            switch($xmlArr['result_code'])
            {
                case 'SUCCESS':
                    #交易成功
                    $res = $trade->field('*')->where($where)->find();
                    $data = [
                        'trade_no'               => $xmlArr['out_trade_no'],
                        'alipay_trade_no'        => $xmlArr['transaction_id'],
                        'seller_email'           => $xmlArr['mch_id'],
                        'buyer_id'               => $xmlArr['openid'],
                        'pay_fee'                => $total_fee,
                        'trade_state'            => 1,
                        'etime'                  => strtotime($xmlArr['time_end']),
                    ];

                    if(!empty($res))
                    {
                        if($res['trade_state'] == 1)
                        {
                            $flag = ExecutiveRecharge($xmlArr['out_trade_no']);
                            if($flag === false)
                                $xml = $fXml;
                            else
                                $xml = $sXml;
                            echo $xml;
                            exit;
                        }
                        if($res['seller_email'] != $mch_id  || $res['total_fee'] != $total_fee) $data['status'] = 0 ;
                        $trade->where($where)->save($data);
                    }
                    else
                    {
                        $data['user_id'] = 0 ;
                        $data['total_fee'] = $total_fee;
                        $data['platform'] = 0;
                        $data['status'] = 0;
                        $trade->add($data);
                    }
                    #添加金币
                    $flag = ExecutiveRecharge($xmlArr['out_trade_no']);
                    if($flag === false)
                    {
                        logResult("金币添加不成功：".$xmlArr['out_trade_no'],'logWx.txt');
                        echo $fXml;
                        exit;
                    }

                    echo $sXml;
                    logResult("交易成功：".json_encode($xmlArr).$xmlStr,'logWx.txt');
                    break;
                case 'FAIL':
                    #交易失败
                    echo $fXml;
                    //调试用，写文本函数记录程序运行情况是否正常
                    logResult("交易失败：".json_encode($xmlArr).$xmlStr,'logWx.txt');
                    break;
                default:
                    break;
            }
        }
        else
        {
            //通信失败
            echo $fXml;
            //调试用，写文本函数记录程序运行情况是否正常
            logResult("通信失败：".json_encode($xmlArr).$xmlStr,'logWx.txt');
        }

    }

    function backCall()
    {
        return true;
    }

    /**
     * test
     * @param  array/int    $data       要返回的数据
     * @param  int          $msgCode    指定提示信息的状态码
     * @param  string       $type       返回数据的格式 json xml...
     */
    function test()
    {
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        //$xml = file_get_contents('php://input');
        echo $xml;exit;
    }


}
 ?>