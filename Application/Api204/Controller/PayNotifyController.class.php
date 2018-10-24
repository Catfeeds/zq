<?php
/**1
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

    //易宝支付回调
    public function notifyYeepay()
    {
        if (!$_REQUEST['data'] || !$_REQUEST['encryptkey'])
            return;

        vendor('Payment.yeepay.yeepayMPay');
        vendor('Payment.Alipay.lib.Corefunction');
        $conf = C('appyeepay')['yeepay_config'];

        $yeepay = new yeepayMPay($conf['merchantaccount'],$conf['merchantPublicKey'],$conf['merchantPrivateKey'],$conf['yeepayPublicKey']);
        $return = $yeepay->callback($_REQUEST['data'], $_REQUEST['encryptkey']); //解密易宝支付回调结果

        file_put_contents('a.txt', json_encode(getParam())."\r\n\r\n",FILE_APPEND);
        file_put_contents('a.txt', json_encode($return)."\r\n\r\n\r\n\r\n",FILE_APPEND);

        if (!$return)
            return;

        $order = M('TradeRecord')->field(['total_fee'])->where(['trade_no'=>$return['orderid']])->find();
        $payFee = $return['amount'] / 100; //实际支付的金额

        $data = [
            'alipay_trade_no' => floatval($return['yborderid']),
            'buyer_id'        => $return['lastno'],
            'buyer_email'     => $return['bank'].' '.$return['bankcode'],
            'pay_fee'         => $payFee,
            'trade_state'     => $return['status'] == 1 ? 2 : 3, //交易成功或失败
            'etime'           => time(),
            'status'          => $payFee == $order['total_fee'] ? 1 : 0, //交易金额是否异常
        ];

        M('TradeRecord')->where(['trade_no'=>$return['orderid']])->save($data);

        if (ExecutiveRecharge($return['orderid']))
        {
            $successStr = $_REQUEST['type'] == 'return' ? "<script>window.location.href = 'yeepay:SUCCESS'; </script>" : 'SUCCESS';
            exit($successStr);
        }
    }

    //wabp网页计费用户状态确认、异步通知
    public function notifyWabpPay()
    {
        //返回合作方的url
        if ($_REQUEST['type'] == 'return')
        {
            switch ($_REQUEST['wabp_result'])
            {
                case '000': $result = 'SUCCESS';    break;
                case '001': $result = 'FAILED';     break;
                case '002': $result = 'ERRORPHONE'; break;
                default :   $result = 'UNKNOWN';    break;
            }
            exit("<script>window.location.href = 'wabpPay:".$result."'; </script>");
        }

        $xmlInput = file_get_contents('php://input');
        $xmlStr   = mb_convert_encoding($xmlInput, "GBK", "UTF-8");
        $xmlData  = (array)simplexml_load_string($xmlStr);

        file_put_contents('wabp.txt', $xmlInput."\r\n\r\n",FILE_APPEND);
        file_put_contents('wabp.txt', iconv('GB2312', 'UTF-8', $xmlStr)."\r\n\r\n",FILE_APPEND);
        file_put_contents('wabp.txt', json_encode($xmlData)."\r\n\r\n\r\n\r\n",FILE_APPEND);

        //签名校验
        vendor('Payment.wabp.WABPPay');
        $conf = C('appwabppay');
        $wabp = new WABPPay($conf['private_key_path'],$conf['wabp_public_key_path']);

        if (!$wabp->verifySign($xmlData))
        {
            file_put_contents('wabp.txt', $xmlData['APTransactionID'].' '.$_REQUEST['type'].' 签名校验失败'."\r\n\r\n",FILE_APPEND);
            exit('签名校验失败');
        }

        //确认用户状态
        if ($_REQUEST['type'] == 'confirm')
        {
            //校验订单
            $order = M('TradeRecord')->field(['total_fee'])->where(['trade_no'=>$xmlData['APTransactionID']])->find();

            if ($conf['verify_content'][$conf['content'][intval($order['total_fee'])]] != $xmlData['ServiceId'])
            {
                file_put_contents('wabp.txt', $xmlData['APTransactionID'].' sin无效'."\r\n\r\n",FILE_APPEND);
                exit('sin无效');
            }

            $retData = [
                'APTransactionID' => $xmlData['APTransactionID'],
                'ResultCode'      => '000',
                'ResultMSG'       => 'ok',
                'RspTime'         => time(),
            ];

            $retXml = $wabp->XmlEncode($retData, $encoding='GBK', $root='VertifyUserState2APRsp');
            file_put_contents('wabp.txt', $xmlData['APTransactionID'].' 确认用户状态返回：'.$retXml."\r\n\r\n",FILE_APPEND);
            exit($retXml);
        }

        //订购关系同步
        if ($_REQUEST['type'] == 'notify')
        {
            $order = M('TradeRecord')->field(['total_fee'])->where(['trade_no'=>$xmlData['APTransactionID']])->find();

            $data = [
                'alipay_trade_no' => $xmlData['APId'],
                'buyer_id'        => $xmlData['Msisdn'],
                'buyer_email'     => $xmlData['Province'],
                'pay_fee'         => $order['total_fee'],
                'trade_state'     => $xmlData['ServiceAction'] == "0" ? 2 : 3, //交易成功或失败
                'etime'           => time(),
                'status'          => 1,
            ];

            $save = M('TradeRecord')->where(['trade_no'=>$xmlData['APTransactionID']])->save($data);
            $excu = ExecutiveRecharge($xmlData['APTransactionID']);
            file_put_contents('wabp.txt', '执行订单结果：'.json_encode($excu)."\r\n\r\n",FILE_APPEND);

            if ($save !== false && $excu)
            {
                $retData = [
                    'APTransactionID' => $xmlData['APTransactionID'],
                    'ResultCode'      => '000',
                    'ResultMSG'       => 'ok',
                    'RspTime'         => time(),
                ];

                $retXml = $wabp->XmlEncode($retData, $encoding='GBK', $root='ServiceWebTransfer2APRsp');
                file_put_contents('wabp.txt', '返回同步订单结果：'.$retXml."\r\n\r\n",FILE_APPEND);
                exit($retXml);
            }
        }
    }


    function backCall()
    {
        return true;
    }


    public function test()
    {
/*        $xmlStr = <<<XML
<?xml version="1.0" encoding="GBK"?>
<ServiceWebTransfer2APReq>
    <APTransactionID>交易流水号</APTransactionID>
    <APId>企业代码</APId>
    <ServiceId>业务代码</ServiceId>
    <ServiceType>业务类型</ServiceType>
    <ChannelId>渠道代码</ChannelId>
    <APContentId>AP内容代码</APContentId >
    <APUserId>合作方用户id</APUserId >
    <Msisdn>手机号伪码</Msisdn>
    <Province>省份</Province>
    <OrderType>订购类型</OrderType>
    <Backup1>计费平台交购中枢订单号</Backup1>
    <Backup2>备用字段2</Backup2>
    <Actiontime>交易发起时间</Actiontime>
    <ServiceAction>业务处理方式</ServiceAction>
    <method>合作方处理方法</method>
    <signMethod>签名方法</signMethod>
    <sign>签名结果</sign>
</ServiceWebTransfer2APReq>
XML;*/
        $xmlStr = <<<XML
<?xml version="1.0" encoding="gbk"?>
<VertifyUserState2APReq>
    <APTransactionID>LKR2016071810042310564</APTransactionID>
    <APId>10959</APId>
    <ServiceId>25248</ServiceId>
    <ServiceType>31</ServiceType>
    <ChannelId>10960</ChannelId>
    <APContentId>1</APContentId>
    <APUserId>10</APUserId>
    <OrderType>0</OrderType>
    <Actiontime>2015-11-23 04:22:37</Actiontime>
    <method />
    <signMethod>DSA</signMethod>
    <sign>MC0CFQCCDlj1XU76uOJFUumZ4ldZFH5NDgIUU75A3CvuvY9LkLkyrPs8Ryl/mII=</sign>
    <Msisdn>28020657452</Msisdn>
    <Province>20</Province>
    <Backup1 />
    <Backup2 />
</VertifyUserState2APReq>
XML;
        $xmlStr  = mb_convert_encoding($xmlStr, "GBK", "UTF-8");
        $xmlData = (array)simplexml_load_string($xmlStr);
        echo json_encode($xmlData);
        dump($xmlData);
    }
}
 ?>