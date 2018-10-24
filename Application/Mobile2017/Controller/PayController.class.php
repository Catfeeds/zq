<?php

/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class PayController extends CommonController {

//    protected function _initialize() {
//
//    }
    public function index() {
        if (!is_login()) {
			redirect(U('User/login'));
        }
        //是否微信浏览器
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            //请求code跳到up.html
            $wxpay_config = C('wxpay.wxpay_config');
            redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $wxpay_config['appid'] . "&redirect_uri=https://m.qqty.com/Pay/up.html&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect");
        }else{
            redirect(U('up'));
        }
    }

    //m站h5支付
    public function up() {

        if(!is_login()) {
            redirect(U('User/login'));
        }

        $code = I('get.code', '');
        if ($code) {
            $wxpay_config = C('wxpay.wxpay_config');
            //获取token
            $tokenInfo = $this->do_curl('https://api.weixin.qq.com/sns/oauth2/access_token', 'appid=' . $wxpay_config['appid'] . '&secret=' . $wxpay_config['appsecret'] . '&code=' . $code . '&grant_type=authorization_code');
            $tokenInfo = json_decode($tokenInfo, true);
            $userInfo = $this->do_curl('https://api.weixin.qq.com/sns/userinfo', 'access_token=' . $tokenInfo['access_token'] . '&openid=' . $tokenInfo['openid'] . '&lang=zh_CN', array(), 'GET', 'wx');
            $userInfo = json_decode($userInfo, true);
            session('m_openid', $userInfo['openid']);
        }
        //充值配置
        $rechargeConfig = getWebConfig('recharge')['recharge'] ?: '';

        //判断有无空
        if($rechargeConfig){
            foreach($rechargeConfig as $rk => $rv){
                if($rv['account'] == ''){
                    unset($rechargeConfig[$rk]);
                }
            }
        }

        $user_auth = session('user_auth');
        $coin = M('FrontUser')->where('id=' . $user_auth['id'])->getField('coin+unable_coin as total_coin');

        $this->assign('rechargeConfig', $rechargeConfig);
        $this->assign('redirectUrl', $_SERVER['HTTP_REFERER']);
        $this->assign('title', '充值');
        $this->assign('coin', $coin);
//        $this->display('index');
        $this->display('pay');
    }

    /**
     * 获取支付地址
     * @author:chenzj
     * @email:443629770@qq.com
     * @支付流程:2016/4/8
     */
    public function getPayUrl() {
        if(check_form_token() != 1){
            echo "<script>alert('请求失败，请稍后再试！');location.href='https://m.qqty.com/Pay/index.html'</script>";
            die;
        }

        $total_fee = I('money',0,'intval');
        $payType   = I('payType',0,'intval');
        $agree     = I('agree');
        $subject   = '全球体育';
        $body      = '用户中心充值';
        $show_url  = U('User/index');
        $user_id   = is_login();
        $give_coin = I('give_coin', 0, 'intval');//使用充值优惠券

        if($agree!='on'){
            echo "<script>alert('请已阅读并接受《全球体育服务协议》');location.href='https://m.qqty.com/Pay/index.html'</script>";
            die;
        }
        if (empty($total_fee) || empty($subject) || empty($body) || empty($show_url) || !$user_id ||  !is_numeric($total_fee)  ) {
            echo "<script>alert('参数有误!');location.href='https://m.qqty.com/Pay/index.html'</script>";
            die;
        }

        if ($total_fee < 10) {
            echo "<script>alert('充值金额最少10元！');location.href='https://m.qqty.com/Pay/index.html'</script>";
            die;
        }
        if ($total_fee > 10000) {
            echo "<script>alert('充值金额最多10000元！');location.href='https://m.qqty.com/Pay/index.html'</script>";
            die;
        }
        if($give_coin){
            $user_auth=  session('user_auth');
            //判断用户是否填写手机号
            if(!$user_auth['username']){
                echo "<script>alert('请前往“个人中心”绑定手机号码！');location.href='https://m.qqty.com/Pay/index.html'</script>";
                die;
            }
        }

        switch ($payType) {
            case 1:
                vendor('Payment.Alipay.lib.Corefunction');
                vendor('Payment.Alipay.lib.Md5function');
                vendor('Payment.Alipay.lib.Notify');
                vendor('Payment.Alipay.lib.Submit');

                $alipay_config = C('alipay.alipay_config');
                $alipaySubmit = new \AlipaySubmit($alipay_config);

                $alipay_config = C('alipay.alipay_config');
                $payment_type = "1";
                $notify_url = C('alipay.notify_url') . '/';
                $return_url = C('alipay.return_url');
                $seller_email = $alipay_config['seller_email'];
                $out_trade_no = getTradeNo($user_id);
                $anti_phishing_key = $alipaySubmit->query_timestamp;
                $exter_invoke_ip = get_client_ip();
                $parameter = [
                    "service" => "alipay.wap.create.direct.pay.by.user",
                    "partner" => $alipay_config['partner'],
                    "seller_id" => $alipay_config['partner'],
                    "payment_type" => $payment_type,
                    "notify_url" => $notify_url,
                    "return_url" => $return_url,
                    "seller_email" => $seller_email,
                    "out_trade_no" => $out_trade_no,
                    "subject" => $subject,
                    "total_fee" => $total_fee,
                    "body" => $body,
                    "show_url" => $show_url,
                    "anti_phishing_key" => $anti_phishing_key,
                    "exter_invoke_ip" => $exter_invoke_ip,
                    "_input_charset" => trim(strtolower($alipay_config['input_charset']))
                ];
                $trade = M('TradeRecord');
                $data = [
                    'trade_no'   => $out_trade_no,
                    'user_id'    => $user_id,
                    'goods_type' => 3,
                    'total_fee'  => $total_fee,
                    'title'      => $subject,
                    'description'=> $body,
                    'platform'   => 4,
                    'ctime'      => time(),
                    'seller_email' => $alipay_config['seller_email'],
                    'pay_type'   => $payType,
                    'give_coin'  => $give_coin,
                ];
                $rsl=$trade->add($data);
                if($rsl){
                    $html_text = $alipaySubmit->buildRequestForm($parameter, "get", "确认");
                    echo $html_text;
                    break;
                }else{
                    echo "<script>alert('支付失败,请重试!');location.href='https://m.qqty.com/Pay/index.html'</script>";
                    die;
                }

            case 2:
                //是否微信浏览器
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                    $wxpay  = C('wxpay');
                    $wxpay_config = $wxpay['wxpay_config'];
                    vendor('Payment.Wxpay.lib.WxPayConfig');
                    vendor('Payment.Wxpay.lib.WxPayJssApiPay');
                    vendor('Payment.Wxpay.lib.WxPayApi');
                    vendor('Payment.Wxpay.lib.WxPayData');
                    vendor('Payment.Wxpay.lib.WxPayException');
                    vendor('Payment.Wxpay.lib.WxPayNotify');
                    $tools = new WxPayJssApiPay();
                    $openId = session('m_openid');
                    $out_trade_no = getTradeNo($user_id);
                    $trade = M('TradeRecord');
                    $data = [
                        'trade_no'   => $out_trade_no,
                        'user_id'    => $user_id,
                        'goods_type' => 3,
                        'total_fee'  => $total_fee,
                        'title'      => $subject,
                        'description' => $body,
                        'platform'   => 4,
                        'ctime'      => time(),
                        'seller_email' => $wxpay_config['seller_email'],
                        'pay_type'   => $payType,
                        'give_coin'  => $give_coin,
                    ];
                    $rsl=$trade->add($data);
                    if($rsl){
                        $input = new WxPayUnifiedOrder();
                        $input->SetBody($body);
                        $input->SetAttach($out_trade_no);
                        $input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
                        $input->SetTotal_fee($total_fee * 100);
                        $input->SetTime_start(date("YmdHis"));
                        $input->SetTime_expire(date("YmdHis", time() + 600));
                        $input->SetGoods_tag($subject);
                        $input->SetNotify_url(C('wxpay.notify_url'));
                        $input->SetTrade_type("JSAPI");
                        $input->SetOpenid($openId);
                        $order = WxPayApi::unifiedOrder($input);
                        $jsApiParameters = $tools->GetJsApiParameters($order);
                        $da['tm'] = date("Y-m-d H:i:s");
                        $da['total_fee'] = $total_fee;
                        $da['no'] = $out_trade_no;
                        $this->assign('order',$order);
                        $this->assign('jsApiParameters',$jsApiParameters);
                        $this->assign('da',$da);
                        $this->display('wxpay');
                        break;
                    }else{
                        echo "<script>alert('支付失败,请重试!');location.href='https://m.qqty.com/Pay/index.html'</script>";
                        die;
                    }
                }else{
                    vendor('Payment.Alipay.lib.Corefunction');
                    vendor('Payment.Wxpay.lib.WxpayService');
                    $WxService = new WxpayService();
                    $wxpay  = C('appwxpay');
                    $wxpay_config = $wxpay['wxpay_config'];
                    $notify_url        = $wxpay['notify_url']; //服务器异步通知页面路径
                    $appid             = $wxpay_config['appid'];  //微信开放平台审核通过的应用APPID
                    $mch_id            = $wxpay_config['mch_id']; //微信支付分配的商户号
                    $nonce_str         = (string) mt_rand();  //随机字符串，不长于32位。推荐随机数生成算法
                    $platform          = 4;
                    $body              = "全球体育网";  //商品或支付单简要描述
                    $total_fee         = $total_fee;       
                    //注意：前方有坑！！！最小单位是分，跟支付宝不一样。1表示1分钱。只能是整形。
                    $detail            = getTradeBody($total_fee); //商品名称明细列表,非必须
                    $out_trade_no      = getTradeNo($user_id);        //商户订单号 通过支付页面的表单进行传递，注意要唯一！
                    $spbill_create_ip  = get_client_ip();       //客户端的IP地址
                    $key               = $wxpay_config['key'];
                    $trade_type        = 'MWEB'; //支付类型

                    //STEP 1. 构造一个订单。
                    $order=array(
                            "appid"            => $appid,
                            'attach'           => '支付测试',
                            "body"             => $body,
                            "mch_id"           => $mch_id,
                            "nonce_str"        => $nonce_str,
                            "notify_url"       => $notify_url,
                            "out_trade_no"     => $out_trade_no,
                            "spbill_create_ip" => $spbill_create_ip,
                            "total_fee"        => intval($total_fee * 100),
                            //注意：前方有坑！！！最小单位是分，跟支付宝不一样。1表示1分钱。只能是整形。
                            "trade_type"       => "MWEB",
                            'scene_info'       => '{"h5_info": {"type":"Wap","wap_url": "https://m.qqty.com","wap_name": "全球体育充值"}} ',
                    );
                    ksort($order);
                    $order = paraFilter($order);    //除去数组中的空值和签名参数
                    $str = createLinkstring($order) ;
                    $stringSignTemp = $str."&key=".$key;
                    // dump($stringSignTemp);
                    // die;
                    $sign = $WxService->signMd5($stringSignTemp);//签名串
                    $order['sign'] = $sign;
                    
                    $xml = $WxService->arrayToXml($order);    //数组转换xml
                    // header("Content-type: text/xml");
                    // echo $xml;
                    // die;
                    $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
                    $res = $WxService->postXmlCurl($xml,$url);     //请求微信服务端
                    $xmlArr = $WxService->xmlToArray($res);   //xml转换数组
                    // dump($xmlArr);
                    // die;
                    if($xmlArr['return_code'] == 'SUCCESS'){
                        $trade = M('TradeRecord');
                        $data = [
                            'trade_no'   => $out_trade_no,
                            'user_id'    => $user_id,
                            'goods_type' => 3,
                            'total_fee'  => $total_fee,
                            'title'      => $subject,
                            'description' => $body,
                            'platform'   => 4,
                            'ctime'      => time(),
                            'seller_email' => $wxpay_config['seller_email'],
                            'pay_type'   => $payType,
                            'give_coin'  => $give_coin,
                        ];
                        $rsl=$trade->add($data);
                        if($rsl){
                            $redirect_url = $xmlArr['mweb_url'].'&redirect_url='.urlencode('https://m.qqty.com/User/index.html');
                            redirect($redirect_url);
                        }else{
                            echo "<script>alert('创建订单失败，请稍后再试!');location.href='https://m.qqty.com/Pay/index.html'</script>";
                        }
                    }else{
                        echo "<script>alert('请求失败，请稍后再试!');location.href='https://m.qqty.com/Pay/index.html'</script>";
                    }
                    die;
                }
            default:
                echo "<script>alert('支付失败,请重试!');location.href='https://m.qqty.com/Pay/index.html'</script>";
                break;
        }
    }

    //移动话费充值
    public function getBillUrl() {
        $bill_phone = I('bill_phone');
        $bill_price = I('bill_price');
        $userInfo = ['userid'=>is_login(),'platform'=>4];
        $param = ['ordtotal_fee'=>$bill_price,'phone'=>$bill_phone,'pkg'=>'M'];
        $url = D('Pay')->appWabpPay($userInfo,$param,true);
        echo $url;
    }

}
