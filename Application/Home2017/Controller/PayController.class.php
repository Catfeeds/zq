<?php
/**
 * 支付
 * @author Hmg<huangmg@qc.mail> 2016.04.07
 */
use Think\Controller;

class PayController extends CommonController
{
    /**
     * 获取支付地址
     * @author:junguo
     * @email:1423844263@qq.com
     * @支付流程:2016/4/8
     */
    public function getPayUrl()
    {
        $total_fee = $_POST['ordtotal_fee'];
        $subject   = '全球体育';
        $body      = '用户中心充值';
        $show_url  = $_POST['ordshow_url'];
        $user_id   = is_login();
        if (   empty($total_fee) 
            || empty($show_url) 
            || empty($user_id)
            || !is_numeric($total_fee)
            || is_int($total_fee) )
        {
            echo "<script>alert('参数错误！')</script>";
            die;
        }

		if(!$_GET['t']){
            if($total_fee<10) {
                echo "<script>alert('充值金额最少10元！')</script>"; 
                die;
            }
            if($total_fee>10000) { 
                echo "<script>alert('充值金额最多10000元！')</script>"; 
                die; 
            } 
        } 
        switch (I('payType')) {
            case '1':
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
                    "service" => "create_direct_pay_by_user",
                    "partner" => $alipay_config['partner'],
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
                    'trade_no' => $out_trade_no,
                    'user_id' => $user_id,
                    'goods_type' => 3,
                    'total_fee' => $total_fee,
                    'title' => $subject,
                    'description' => $body,
                    'platform' => 1,
                    'ctime' => time(),
                    'seller_email' => $alipay_config['seller_email'],
                    'pay_type' => I('payType'),
                ];
                $rsl=$trade->add($data);
                if($rsl){
                    $html_text = $alipaySubmit->buildRequestForm($parameter, "post", "确认");
                    echo $html_text;
                    break;
                }else{
                    echo "<script>alert('支付失败,请重试!');location.href='//www.qqty.com/UserAccount/charge.html'</script>";
                    die;
                }
            case '2':
                vendor('Payment.Wxpay.lib.WxPayApi');
                vendor('Payment.Wxpay.lib.WxPayConfig');
                vendor('Payment.Wxpay.lib.WxPayData');
                vendor('Payment.Wxpay.lib.WxPayException');
                vendor('Payment.Wxpay.lib.WxPayNotify');
                $wxpay_config = C('wxpay.wxpay_config');
                $out_trade_no = getTradeNo($user_id);
                $trade = M('TradeRecord');
                $data = [
                    'trade_no' => $out_trade_no,
                    'user_id' => $user_id,
                    'goods_type' => 3,
                    'total_fee' => $total_fee,
                    'title' => $subject,
                    'description' => $body,
                    'platform' => 1,
                    'ctime' => time(),
                    'seller_email' => $wxpay_config['seller_email'],
                    'pay_type' => I('payType'),
                ];
                $rsl=$trade->add($data);
                if($rsl){
                    $input = new WxPayUnifiedOrder();
                    $input->SetBody($body);
                    $input->SetAttach($out_trade_no);
                    $input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));
                    $input->SetTotal_fee($total_fee * 100);
                    $input->SetTime_start(date("YmdHis"));
                    $input->SetTime_expire(date("YmdHis", time() + 600));
                    $input->SetGoods_tag($subject);
                    $input->SetNotify_url(C('wxpay.notify_url') . '/');
                    $input->SetTrade_type("NATIVE");
                    $input->SetProduct_id($out_trade_no);
                    $result = WxPayApi::unifiedOrder($input);
                    $url2 = $result["code_url"];
                    $da['tm'] = date("Y-m-d H:i:s");
                    $da['url'] = $url2;
                    $da['total_fee'] = $total_fee;
                    $da['no'] = $out_trade_no;
                    $this->assign('da', $da);
                    $this->display('wxpay');
                    break;
                }else{
                    echo "<script>alert('支付失败,请重试!');location.href='//www.qqty.com/UserAccount/charge.html'</script>";
                    die;
                }
            default:
                echo "<script>alert('支付失败,请重试!');location.href='//www.qqty.com/UserAccount/charge.html'</script>";
                break;
        }

    }

    public function ajaxpay()
    {
        $no = I('no');
        if ($no) {
            $res = M('trade_record')->where(['trade_no' => $no])->find();
            if ($res['trade_state'] == 1 || $res['trade_state'] == 2) {
                echo 'ok';
            }
        }
    }

    /**
     * 供商城发起微信支付请求,返回二维码等数据供使用
     * @return [json] [description]
     */
    public function shop_code()
    {
        vendor('Payment.Wxpay.lib.WxPayApi');
        vendor('Payment.Wxpay.lib.WxPayConfig');
        vendor('Payment.Wxpay.lib.WxPayData');
        vendor('Payment.Wxpay.lib.WxPayException');
        vendor('Payment.Wxpay.lib.WxPayNotify');
        $input = new WxPayUnifiedOrder();
        $input->SetBody(I('Body'));
        $input->SetAttach('shop'.I('Product_id'));
        //$input->SetOut_trade_no(I('Out_trade_no'));
        $input->SetOut_trade_no(WxPayConfig::MCHID . date("YmdHis"));
        $input->SetTotal_fee(I('Total_fee'));
        $input->SetNotify_url(C('wxpay.notify_url') . '/');
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetTrade_type("NATIVE");
        $input->SetGoods_tag('全球体育商城');
        $input->SetProduct_id(I('Product_id'));
        $result = WxPayApi::unifiedOrder($input);


        $this->ajaxReturn($result);
    }
}
       