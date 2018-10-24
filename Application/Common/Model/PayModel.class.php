<?php
/**
 * 支付模型类
 * @author zhangw 2017.1.10
 */

use Think\Model;
class PayModel extends Model
{

	/**
	 * app支付接口数据
	 * @param  array      $_POST       传过来数据
	 * @param  array      $userInfo       用户信息
	 * @return  json      $data           返回数据
	 */
	public function appAlipay($userInfo, $param){
		if(!isset($_POST['ordtotal_fee']) && empty($_POST['ordtotal_fee']) && !isset($_POST['userToken']) && empty($_POST['userToken']))
			return 5001;

		//充值限制
		if(D('Common')->checkRechargeNum($_POST['ordtotal_fee'], $userInfo['userid']))
			return 5005;

		//使用体验券判断用户是否填写手机号
		if(isset($_POST['give_coin']) &&  $_POST['give_coin'] > 0){
			if(!$userInfo['username'])
				return 8008;
		}

		vendor('Payment.Alipay.lib.Corefunction');
		vendor('Payment.Alipay.lib.Rsafunction');

		$alipay_config = C('appalipay')['alipay_config'];

		$payment_type      = "1"; //支付类型 //必填，不能修改
		$notify_url        = C('appalipay.notify_url');       //服务器异步通知页面路径
		$seller_id         = $alipay_config['seller_email'];  //卖家支付宝帐户必填
		$user_id           = $userInfo['userid'];       //用户ID
		$out_trade_no      = getTradeNo($user_id);        //商户订单号 通过支付页面的表单进行传递，注意要唯一！
		$total_fee         = $_POST['ordtotal_fee'];       //付款金额  //必填 通过支付页面的表单进行传递
		$subject           = "全球体育网";        //订单名称 //必填 通过支付页面的表单进行传递
		$body              = getTradeBody($total_fee);         //订单描述 通过支付页面的表单进行传递
		$platform          = $userInfo['platform'];      //平台来源
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
				'pkg'               => $param['pkg'],
				'give_coin'         => isset($_POST['give_coin']) ? (int)$_POST['give_coin'] : 0,
				'telBind'           => isset($_POST['telBind']) ? (int)$_POST['telBind'] : 0,
		];
		$res = $trade->add($data);

		if($res)
		{
			$rData = ['trade_no'=>$out_trade_no,'sign'=>$sStr];
			return $rData;
		}
		else
		{
			return 5002;
		}
	}

	/**
	 * app微信接口数据
	 * @param  array/int    $data       要返回的数据
	 * @param  int          $msgCode    指定提示信息的状态码
	 * @return  string       $type       返回数据的格式 json xml...
	 */
	public function appWxpay($userInfo, $param)
	{
		if(!isset($_POST['ordtotal_fee']) && empty($_POST['ordtotal_fee']) && !isset($_POST['userToken']) && empty($_POST['userToken']))
			return 5001;

		//充值限制
		if(D('Common')->checkRechargeNum($_POST['ordtotal_fee'], $userInfo['userid']))
			return 5005;

		//使用体验券判断用户是否填写手机号
		if(isset($_POST['give_coin']) &&  $_POST['give_coin'] > 0){
			if(!$userInfo['username'])
				return 8008;
		}

		vendor('Payment.Alipay.lib.Corefunction');

		if ($param['platform'] == 2)
			//个人就用竞猜版，和分析大师版，其他用公司版，公司版没有配置前缀
			$pkg = ($param['pkg'] != '' && in_array($param['pkg'], ['personal', 'master'])) ? $param['pkg'].'_' : '';
		else
			$pkg = '';

		$wxconfig = C($pkg."appwxpay");

		$wxpay_config      = $wxconfig['wxpay_config'];
		$notify_url        = $wxconfig['notify_url']; //服务器异步通知页面路径
		$appid             = $wxpay_config['appid'];  //微信开放平台审核通过的应用APPID
		$mch_id            = $wxpay_config['mch_id']; //微信支付分配的商户号
		$nonce_str         = (string) mt_rand();  //随机字符串，不长于32位。推荐随机数生成算法
		$user_id           = $userInfo['userid'];       //用户ID
		$platform          = $userInfo['platform'];
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
					'pkg'               => $param['pkg'],
					'give_coin'         => isset($_POST['give_coin']) ? (int)$_POST['give_coin'] : 0,
					'telBind'           => isset($_POST['telBind']) ? (int)$_POST['telBind'] : 0,
			];

			$rs = $trade->add($data);
			if(!$rs) return 5002;

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
			$rData['trade_no'] = $out_trade_no;

			return $rData;
		}
		else
		{
			return 5003;
		}
	}

	/**
	 * 易宝支付
	 * @param bool|false $isJump
	 */
	public function appYeepay($userInfo, $param, $isJump=false)
	{
		//充值限制
		if(D('Common')->checkRechargeNum($param['ordtotal_fee'], $userInfo['userid']))
			return 5005;

		//使用体验券判断用户是否填写手机号
		if(isset($_POST['give_coin']) &&  $_POST['give_coin'] > 0){
			if(!$userInfo['username'])
				return 8008;
		}

		vendor('Payment.yeepay.yeepayMPay');
		$yeeconf = C('appyeepay');
		$conf    = $yeeconf['yeepay_config'];

		$yeepay = new yeepayMPay($conf['merchantaccount'],$conf['merchantPublicKey'],$conf['merchantPrivateKey'],$conf['yeepayPublicKey']);

		$order_id        = getTradeNo($userInfo['userid']);
		$transtime       = NOW_TIME;
		$amount          = intval($param['ordtotal_fee']) * 100;
		$currency        = 156;
		$product_catalog = '53';
		$product_name    = '全球体育：'.$param['ordtotal_fee'].'元充值';
		$identity_type   = 2;
		$identity_id     = $userInfo['userid'];
		$user_ip         = get_client_ip();
		$terminaltype    = 3;
		$terminalid      = $userInfo['userid'];
		$callbackurl     = $yeeconf['notify_url'];
		$fcallbackurl    = $yeeconf['return_url'];

		$url = $yeepay->webPay($order_id, $transtime, $amount, $cardno = '', $idcardtype = '', $idcard = '', $owner = '', $product_catalog, $identity_id, $identity_type, $user_ip, $user_ua = '', $callbackurl, $fcallbackurl, $currency, $product_name, $product_desc = '', $terminaltype, $terminalid, $orderexp_date = null, $paytypes = '', $version = '');

		if (array_key_exists('error_code', $url))
			return 5004;

		#充值记录入库
		$data = [
				'trade_no'     => $order_id,
				'user_id'      => $userInfo['userid'],
				'total_fee'    => $param['ordtotal_fee'],
				'title'        => $product_name,
				'description'  => '',
				'platform'     => $userInfo['platform'],
				'ctime'        => NOW_TIME,
				'seller_email' => $conf['merchantaccount'],
				'pay_type'     => 3,
				'pkg'          => $param['pkg'],
				'give_coin'    => isset($_POST['give_coin']) ? (int)$_POST['give_coin'] : 0,
				'telBind'      => isset($_POST['telBind']) ? (int)$_POST['telBind'] : 0,
		];

		if (!M('TradeRecord')->add($data))
			return 5002;

		if($isJump == false){
			return ['trade_no'=>$order_id, 'url'=>$url];
		}else{
			return $url;
		}
	}

	//获取16位唯一订单号
	function getTradeNo()
	{
	    $letter = '';
	    for ($i = 1; $i <= 3; $i++)
	    {
	        $letter .= chr(rand(65, 90));
	    }
	    list($msec, $sec) = explode(' ', microtime());
	    $msectime =  (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
	    $orderNo = $letter.$msectime;

	    $where['trade_no'] = $orderNo;
	    $res = M('TradeRecord')->master(true)->field('id')->where($where)->find();
	    if(!empty($res))
	        return $this->getTradeNo();
	    else
	        return $orderNo;
	}

	/**
	 * 移动网页订购
	 * @param bool|false $isJump
	 */
	public function appWabpPay($userInfo, $param, $isJump=false)
	{
		//充值限制
		if(D('Common')->checkRechargeNum($param['ordtotal_fee'], $userInfo['userid']))
			return 5005;

		//使用体验券判断用户是否填写手机号
		if(isset($_POST['give_coin']) &&  $_POST['give_coin'] > 0){
			if(!$userInfo['username'])
				return 8008;
		}

		$conf = C('appwabppay');
		$sin = $conf['content']["{$param['ordtotal_fee']}"]; //订购的业务标识

		if (!$sin || !$param['phone'])
			return 5001;

		if($param['is_sdk'] == 1){
			//16位唯一订单号
			$order_id = $this->getTradeNo();
		}else{
			$order_id = getTradeNo($userInfo['userid']);
		}
		
		//支付的信息
		$info = [
				'apco'   => $param['ordtotal_fee'],
				'aptid'  => $userInfo['userid'],
				'aptrid' => $order_id,
				'ch'     => $conf['ch'],
				'ex'     => $conf['ex'],
				'sin'    => $sin,
				'bu'     => base64_encode($conf['return_url']),
				'xid'    => '',
				'mid'    => $param['phone'],
		];

		//生成支付的地址
		vendor('Payment.wabp.WABPPay');
		$wabp = new WABPPay($conf['private_key_path'],$conf['wabp_public_key_path']);
		$url = $wabp->getPayUrl($info);

		#充值记录入库
		$data = [
				'trade_no'     => $order_id,
				'user_id'      => $userInfo['userid'],
				'total_fee'    => $param['ordtotal_fee'],
				'title'        => '移动网页充值',
				'description'  => $param['ordtotal_fee'].'元充值 sin:'.$sin,
				'platform'     => $userInfo['platform'],
				'ctime'        => NOW_TIME,
				'seller_email' => $conf['ch'].'/'.$conf['ex'],
				'pay_type'     => 4,
				'pkg'          => $param['pkg'],
				'give_coin'    => isset($_POST['give_coin']) ? (int)$_POST['give_coin'] : 0,
				'telBind'      => isset($_POST['telBind']) ? (int)$_POST['telBind'] : 0,
		];

		if (!M('TradeRecord')->add($data))
			return 5002;

		if($isJump == false){
			return ['trade_no'=>$info['aptrid'], 'url'=>$url];
		}else{
			return '<html>
                    <head>
                        <meta http-equiv="refresh" content="0;url='.$url.'">
                    </head>
                    <body>
                    </body>
                </html>';
		}
	}

	/**
	 * 安卓移动+接口数据
	 * @param  string       $ordtotal_fee       金额
	 * @param  string       $userToken          用户token
	 * @return json         $data               返回数据
	 */
	public function androidMovePay($userInfo, $param)
	{
		if(!isset($_POST['ordtotal_fee']) && empty($_POST['ordtotal_fee']) && !isset($_POST['userToken']) && empty($_POST['userToken']))
			return 5001;

		//充值限制
		if(D('Common')->checkRechargeNum($_POST['ordtotal_fee'], $userInfo['userid']))
			return 5005;

		//使用体验券判断用户是否填写手机号
		if(isset($_POST['give_coin']) &&  $_POST['give_coin'] > 0){
			if(!$userInfo['username'])
				return 8008;
		}

		$moveconfig  = C("androidmovepay");
		$user_id     = $userInfo['userid'];       //用户ID
		$total_fee   = $_POST['ordtotal_fee'];
		$appTradeId  = getTradeNo($user_id);
		$productName = "全球体育网";
		$productDesc = getTradeBody($total_fee);

		#充值记录入库
		$trade = M('TradeRecord');
		$data = [
				'trade_no'          => $appTradeId,              //商户订单号
				'user_id'           => $user_id ,                //充值用户id
				'total_fee'         => $total_fee,               //充值金额
				'title'             => $productName,             //商品或支付单简要描述
				'description'       => $productDesc,             //商品名称明细列表,非必须,
				'platform'          => $userInfo['platform'],
				'ctime'             => time(),
				'seller_email'      => $moveconfig['appid'],
				'pay_type'          => 4,
				'pkg'               => $param['pkg'],
				'give_coin'         => isset($_POST['give_coin']) ? (int)$_POST['give_coin'] : 0,
				'telBind'           => isset($_POST['telBind']) ? (int)$_POST['telBind'] : 0,
		];

		$rs = $trade->add($data);
		if(!$rs) return 5002;

		$rData = [
				'appId'        => $moveconfig['appid'],
				'appkey'       => $moveconfig['appkey'],
				'appTradeId'   => $appTradeId,
				'productName'  => $productName,
				'productPrice' => $total_fee,
				'payTime'      => date('YmdHis'),
				'productDesc'  => $productDesc,
		];
		return $rData;
	}

}