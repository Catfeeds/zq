<?php
/**1
 * app支付接口类
 * @author Hmg<huangmg@qc.mail> 2016.03.25
 */

use Think\Controller;

class PayController extends CommonController
{
    /**
     * app支付接口数据
     * @param  string       $ordtotal_fee       金额
     * @param  string       $userToken          用户token
     * @param  json         $data               返回数据
     */
    function appAlipay()
    {
        if(!isset($_POST['ordtotal_fee']) && empty($_POST['ordtotal_fee']) && !isset($_POST['userToken']) && empty($_POST['userToken']))
        {
            $this->ajaxReturn(5001);
        }
        vendor('Payment.Alipay.lib.Corefunction');
        vendor('Payment.Alipay.lib.Rsafunction');

        $alipay_config = C('appalipay')['alipay_config'];

        $payment_type      = "1"; //支付类型 //必填，不能修改
        $notify_url        = C('appalipay.notify_url');       //服务器异步通知页面路径
        $seller_id         = $alipay_config['seller_email'];  //卖家支付宝帐户必填
        $user_id           = $this->userInfo['userid'];       //用户ID
        $out_trade_no      = getTradeNo($user_id);        //商户订单号 通过支付页面的表单进行传递，注意要唯一！
        $total_fee         = $_POST['ordtotal_fee'];       //付款金额  //必填 通过支付页面的表单进行传递
        $subject           = "全球体育网";        //订单名称 //必填 通过支付页面的表单进行传递
        $body              = getTradeBody($total_fee);         //订单描述 通过支付页面的表单进行传递
        $platform          = $this->userInfo['platform'];      //平台来源
        $sign              = "";                               //签名串

        $parameter = array(
            "notify_url"        => $notify_url,
            "service"           => "mobile.securitypay.pay",
            "partner"           => trim($alipay_config['partner']),
            "_input_charset"    => trim(strtolower($alipay_config['input_charset'])),
            "sign_type"         => trim(strtolower($alipay_config['sign_type'])),
            "sign"              => $sign,
            "payment_type"      => $payment_type,
            "seller_id"         => $seller_id,
            "out_trade_no"      => $out_trade_no,
            "subject"           => $subject,
            "body"              => $body,
            "total_fee"         => $total_fee,
            "it_b_pay"          => "30m",
        );

        $parameter = paraFilter($parameter);    //除去数组中的空值和签名参数
        $str = createLinkstringTwo($parameter) ;
        $sign = rsaSign($str, $alipay_config['private_key_path']);
        $sign = urlencode($sign);
        $sStr = $str.'&sign="'.$sign.'"&sign_type="RSA"';

        #充值记录入库
        $trade = M('TradeRecord');
        $data = [
            'trade_no'          => $out_trade_no,
            'user_id'           => $user_id ,
            'total_fee'         => $total_fee,
            'title'             => $subject,
            'description'       => $body,
            'platform'          => $platform,
            'ctime'             => time(),
            'seller_email'      => $alipay_config['seller_email'],
            'pay_type'          => 1,
            'pkg'               => $this->param['pkg']
        ];
        $res = $trade->add($data);

        if($res)
        {
            $rData = ['trade_no'=>$out_trade_no,'sign'=>$sStr];
            $this->ajaxReturn($rData);
        }
        else
        {
            $this->ajaxReturn(5002);
        }
    }
    /**
     * app微信接口数据
     * @param  array/int    $data       要返回的数据
     * @param  int          $msgCode    指定提示信息的状态码
     * @param  string       $type       返回数据的格式 json xml...
     */
    function appWxpay()
    {
        if(!isset($_POST['ordtotal_fee']) && empty($_POST['ordtotal_fee']) && !isset($_POST['userToken']) && empty($_POST['userToken']))
        {
            $this->ajaxReturn(5001);
        }
        vendor('Payment.Alipay.lib.Corefunction');

        if ($this->param['platform'] == 2)
            $pkg = $this->param['pkg'] != '' && $this->param['pkg'] != 'company' ? $this->param['pkg'].'_' : '';
        else
            $pkg = '';

        $wxconfig = C($pkg."appwxpay");

        $wxpay_config      = $wxconfig['wxpay_config'];
        $notify_url        = $wxconfig['notify_url']; //服务器异步通知页面路径
        $appid             = $wxpay_config['appid'];  //微信开放平台审核通过的应用APPID
        $mch_id            = $wxpay_config['mch_id']; //微信支付分配的商户号
        $nonce_str         = (string) mt_rand();  //随机字符串，不长于32位。推荐随机数生成算法
        $user_id           = $this->userInfo['userid'];       //用户ID
        $platform          = $this->userInfo['platform'];
        $sign              = ''; //签名串
        $body              = "全球体育网";  //商品或支付单简要描述
        $total_fee         = $_POST['ordtotal_fee'];       //注意：前方有坑！！！最小单位是分，跟支付宝不一样。1表示1分钱。只能是整形。
        $detail            = getTradeBody($total_fee); //商品名称明细列表,非必须
        $out_trade_no      = getTradeNo($user_id);        //商户订单号 通过支付页面的表单进行传递，注意要唯一！
        $spbill_create_ip  = get_client_ip();       //客户端的IP地址
        $key               = $wxpay_config['key'];
        $trade_type        = 'APP'; //支付类型


        //STEP 1. 构造一个订单。
        $order=array(
            "appid"            => $appid,
            "mch_id"           => $mch_id,
            "nonce_str"        => $nonce_str,
            "body"             => $body,
            "out_trade_no"     => $out_trade_no,
            "total_fee"        => intval($_POST['ordtotal_fee'] * 100),
            "spbill_create_ip" => $spbill_create_ip,
            "notify_url"       => $notify_url,//注意：前方有坑！！！最小单位是分，跟支付宝不一样。1表示1分钱。只能是整形。
            "trade_type"       => "APP"
        );
        ksort($order);     //排序
        $order = paraFilter($order);    //除去数组中的空值和签名参数
        $str = createLinkstring($order) ;
        $stringSignTemp = $str."&key=".$key;

        vendor('Payment.Wxpay.lib.WxpayService');
        $WxService = new WxpayService();

        $sign = $WxService->signMd5($stringSignTemp);
        $order['sign'] = $sign;
        $xml = $WxService->arrayToXml($order);    //数组转换xml
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $res = $WxService->postXmlCurl($xml,$url);     //请求微信服务端
        $xmlArr = $WxService->xmlToArray($res);   //xml转换数组

        if(isset($xmlArr['result_code']) && $xmlArr['result_code'] == 'SUCCESS')
        {
            #充值记录入库
            $trade = M('TradeRecord');
            $data = [
                'trade_no'          => $out_trade_no,
                'user_id'           => $user_id ,
                'total_fee'         => $total_fee,
                'title'             => $body,
                'description'       => $detail,
                'platform'          => $platform,
                'ctime'             => time(),
                'seller_email'      => $wxpay_config['mch_id'],
                'pay_type'          => 2,
                'pkg'               => $this->param['pkg']
            ];

            $rs = $trade->add($data);
            if(!$rs) $this->ajaxReturn(5002);
            
            $rData = [
                'appid'        => $appid,
                'partnerid'    => $mch_id,
                'prepayid'     => $xmlArr['prepay_id'],
                'package'      => 'Sign=WXPay',
                'noncestr'     => $nonce_str,
                'timestamp'    => time(),
            ];

            ksort($rData);     //排序
            $order = paraFilter($rData);    //除去数组中的空值和签名参数
            $str = createLinkstring($rData) ;
            $stringSignTemp = $str."&key=".$key;
            $sign = $WxService->signMd5($stringSignTemp);
            $rData['sign'] = $sign;
            $this->ajaxReturn($rData);
        }
        else
        {
            $this->ajaxReturn(5003);
        }
    }

    //易宝支付
    public function appYeepay()
    {
        vendor('Payment.yeepay.yeepayMPay');
        $yeeconf = C('appyeepay');
        $conf    = $yeeconf['yeepay_config'];

        $yeepay = new yeepayMPay($conf['merchantaccount'],$conf['merchantPublicKey'],$conf['merchantPrivateKey'],$conf['yeepayPublicKey']);

        $order_id        = getTradeNo($this->userInfo['userid']);
        $transtime       = NOW_TIME;
        $amount          = intval($this->param['ordtotal_fee']) * 100;
        $currency        = 156;
        $product_catalog = '53';
        $product_name    = '全球体育：'.$this->param['ordtotal_fee'].'元充值';
        $identity_type   = 2;
        $identity_id     = $this->userInfo['userid'];
        $user_ip         = get_client_ip();
        $terminaltype    = 3;
        $terminalid      = $this->userInfo['userid'];
        $callbackurl     = $yeeconf['notify_url'];
        $fcallbackurl    = $yeeconf['return_url'];

        $url = $yeepay->webPay($order_id, $transtime, $amount, $cardno = '', $idcardtype = '', $idcard = '', $owner = '', $product_catalog, $identity_id, $identity_type, $user_ip, $user_ua = '', $callbackurl, $fcallbackurl, $currency, $product_name, $product_desc = '', $terminaltype, $terminalid, $orderexp_date = null, $paytypes = '', $version = '');

        if (array_key_exists('error_code', $url))
            $this->ajaxReturn(5004);

        #充值记录入库
        $data = [
            'trade_no'     => $order_id,
            'user_id'      => $this->userInfo['userid'],
            'total_fee'    => $this->param['ordtotal_fee'],
            'title'        => $product_name,
            'description'  => '',
            'platform'     => $this->userInfo['platform'],
            'ctime'        => NOW_TIME,
            'seller_email' => $conf['merchantaccount'],
            'pay_type'     => 3,
            'pkg'          => $this->param['pkg']
        ];

        if (!M('TradeRecord')->add($data))
            $this->ajaxReturn(5002);

        $this->ajaxReturn(['trade_no'=>$order_id,'url'=>$url]);
    }

    //移动网页订购
    public function appWabpPay()
    {
        $conf = C('appwabppay');
        $sin = $conf['content']["{$this->param['ordtotal_fee']}"]; //订购的业务标识

        if (!$sin || !$this->param['phone'])
            $this->ajaxReturn(5001);

        $order_id = getTradeNo($this->userInfo['userid']);

        //支付的信息
        $info = [
            'apco'   => $this->param['ordtotal_fee'],
            'aptid'  => $this->userInfo['userid'],
            'aptrid' => $order_id,
            'ch'     => $conf['ch'],
            'ex'     => $conf['ex'],
            'sin'    => $sin,
            'bu'     => base64_encode($conf['return_url']),
            'xid'    => '',
            'mid'    => $this->param['phone'],
        ];

        //生成支付的地址
        vendor('Payment.wabp.WABPPay');
        $wabp = new WABPPay($conf['private_key_path'],$conf['wabp_public_key_path']);
        $url = $wabp->getPayUrl($info);

        #充值记录入库
        $data = [
            'trade_no'     => $order_id,
            'user_id'      => $this->userInfo['userid'],
            'total_fee'    => $this->param['ordtotal_fee'],
            'title'        => '移动网页充值',
            'description'  => $this->param['ordtotal_fee'].'元充值 sin:'.$sin,
            'platform'     => $this->userInfo['platform'],
            'ctime'        => NOW_TIME,
            'seller_email' => $conf['ch'].'/'.$conf['ex'],
            'pay_type'     => 4,
            'pkg'          => $this->param['pkg']
        ];

        if (!M('TradeRecord')->add($data))
            $this->ajaxReturn(5002);

        $this->ajaxReturn(['trade_no'=>$info['aptrid'],'url'=>$url]);
    }

}
 ?>