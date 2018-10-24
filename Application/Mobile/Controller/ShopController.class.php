<?php

/**
 * 新闻
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;
use Vendor\ThinkSDK\ThinkOauth;

class ShopController extends CommonController {

    private $secretKey = 'quancaiappppa';
    public $param = null;

    protected function _initialize() {
        $user = session('user_auth');
		if (!empty($user))
		{
			$user = session('user_auth');
		}
		
    }

    //校验签名
    public function verifySignature()
    {
        //验证请求的时间
        if (!$this->param['t'] ||$this->param['t'] < time() - 300 || $this->param['t'] > time() + 60)
            $this->ajaxReturn(array('status'=>0,'ErrorCode'=>103,'data'=>null));

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
   
	/**
     *Liangzk 《Liangzk@qc.com》
     * DateTime 2017-02-15
     *  第三方登录页面注册
     */
    public function tpperfect()
    {
        cookie('sdk_sign', null, array('domain' => '.' . DOMAIN));
        $token = cookie('loginToken');
        if (empty($token) || !in_array($token['type'],['qq','weixin','sina']))
        {
            //请求超时，请重试！
            $this->shop_err();
            exit;
        }
        cookie('loginToken',null);
        
        switch ($token['type']) {
            case 'qq':
                $UserArray['qq_unionid'] = $token['openid'];
                $FrontUserId = M("FrontUser")->where(['qq_unionid'=>$token['openid']])->getField('id');

                break;
            case 'weixin':
                $UserArray['weixin_unionid'] = $token['unionid'];
                $FrontUserId = M("FrontUser")->where(['weixin_unionid'=>$token['unionid']])->getField('id');
                break;
            case 'sina':
                $UserArray['sina_unionid'] = $token['openid'];
                $FrontUserId = M("FrontUser")->where(['sina_unionid'=>$token['openid']])->getField('id');
                break;
        }
        
        //判断是否有认证过
        if (!empty($FrontUserId))
        {
            //自动登录
            D('FrontUser')->autoLogin($FrontUserId);
            if (!M('FrontUser')->where(['id'=>$FrontUserId])->getField('nick_name'))
            {
                $this->shop_log($FrontUserId);
                exit;
            }
            ///已经绑定过
            $this->shop_log($FrontUserId);
            exit;
        }
        
        $ip = get_client_ip();
        $channel_code = cookie('login_code');
        $UserArray['reg_time']  = time();
        $UserArray['reg_ip']    = $ip;
        $UserArray['platform']  = 4;
        $UserArray['channel_code'] = $channel_code == '' ? 'm' : $channel_code;
        
        $FrontUserId = M('frontUser')->add($UserArray);
        if ($FrontUserId !== false) {
            //登录
            D('FrontUser')->autoLogin($FrontUserId);
            //注册成功，请修改昵称
            $this->shop_log($FrontUserId);
            exit;
        } else {
            ///注册失败,请重试
            $this->shop_err();
            exit;
        }
        
    }


    public function wechat_login() {
        $wxpay_config = C('wxpay.wxpay_config');
        if (!isset($_GET['code'])) {
            redirect("https://open.weixin.qq.com/connect/oauth2/authorize?appid=" . $wxpay_config['appid'] . "&redirect_uri=http://m.qqty.com/Shop/wechat_login.html&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect");
        } else {
            //获取code码，以获取openid
            $code = $_GET['code'];
            //获取token
            $tokenInfo = $this->do_curl('https://api.weixin.qq.com/sns/oauth2/access_token', 'appid=' . $wxpay_config['appid'] . '&secret=' . $wxpay_config['appsecret'] . '&code=' . $code . '&grant_type=authorization_code');
            $tokenInfo = json_decode($tokenInfo, true);
            $userInfo = $this->do_curl('https://api.weixin.qq.com/sns/userinfo', 'access_token=' . $tokenInfo['access_token'] . '&openid=' . $tokenInfo['openid'] . '&lang=zh_CN', array(), 'GET', 'wx');
            $userInfo = json_decode($userInfo, true);
            session('m_openid', $userInfo['openid']);
            $uid=is_login();
            if (!$uid) {
                $user_id = M('FrontUser')->where(array('weixin_unionid' => $tokenInfo['unionid']))->getField('id');
                if ($user_id) {
                    D('FrontUser')->autoLogin($user_id);
                    $this->shop_log($user_id);
                } else {
                    $token['type'] = 'weixin';
                    $token['unionid'] = $tokenInfo['unionid'];
                    cookie('loginToken',$token,array('expire'=>C('loginTokenTime'),'domain'=>'.'.DOMAIN));
                    redirect(U('Shop/tpperfect'));
                }
            } else {
                $is_bind = M("frontUser")->where(['id' => $uid])->getField('weixin_unionid');
                $is_byBind = M("frontUser")->where(['weixin_unionid' => $tokenInfo['unionid']])->getField('id');
                if(empty($is_bind) && empty($is_byBind)){
                    M("frontUser")->where(['id' => $uid])->save(['weixin_unionid'=>$tokenInfo['unionid']]);
                    $this->shop_log($uid);
                    exit;
                }
                $this->shop_log($uid);
                
            }
        }
    }

    public function wx_s(){
        redirect(U('Shop/wechat_login'));
    }

    public function shop_log($id){
        $id = $this->encrypt($id);
        // setcookie("w_u", $id, 3600, "/", DOMAIN);
        // cookie('sdk_sign', 1, array('domain' => '.' . DOMAIN));
        // var_dump(cookie('sdk_sign'));EXIT;
        header("Location: https://shop.qqty.com/Mobile/Index?w_u=".$id);
    }

    public function shop_err()
    {
        header("Location: https://shop.qqty.com/Mobile/Index");
    }

    /**
     * 加密函数
     * @param string $txt 需要加密的字符串
     * @param string $key 密钥
     * @return string 返回加密结果
     */
    public function encrypt($txt, $key = ''){
        if (empty($txt)) return $txt;
        if (empty($key)) $key = md5(MD5_KEY);
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
        $ikey ="-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
        $nh1 = rand(0,64);
        $nh2 = rand(0,64);
        $nh3 = rand(0,64);
        $ch1 = $chars{$nh1};
        $ch2 = $chars{$nh2};
        $ch3 = $chars{$nh3};
        $nhnum = $nh1 + $nh2 + $nh3;
        $knum = 0;$i = 0;
        while(isset($key{$i})) $knum +=ord($key{$i++});
        $mdKey = substr(md5(md5(md5($key.$ch1).$ch2.$ikey).$ch3),$nhnum%8,$knum%8 + 16);
        $txt = base64_encode(time().'_'.$txt);
        $txt = str_replace(array('+','/','='),array('-','_','.'),$txt);
        $tmp = '';
        $j=0;$k = 0;
        $tlen = strlen($txt);
        $klen = strlen($mdKey);
        for ($i=0; $i<$tlen; $i++) {
            $k = $k == $klen ? 0 : $k;
            $j = ($nhnum+strpos($chars,$txt{$i})+ord($mdKey{$k++}))%64;
            $tmp .= $chars{$j};
        }
        $tmplen = strlen($tmp);
        $tmp = substr_replace($tmp,$ch3,$nh2 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch2,$nh1 % ++$tmplen,0);
        $tmp = substr_replace($tmp,$ch1,$knum % ++$tmplen,0);
        return $tmp;
    }


    //userToken获取用户id返回给商城
    public function getuser()
    {
        $this->param = getParam(); //获取传入的参数
        if($this->param['nosign'] != C('nosignStr') && ACTION_NAME != 'animate' && ACTION_NAME != 'aniOver' && ACTION_NAME != 'animateId')
        {
            $this->verifySignature();  //校验签名
        }
        $token = I('userToken');
        $userInfo = getUserToken($token, true);
        $this->ajaxreturn($userInfo);
    }

    //易宝支付
    public function shop_appYeepay()
    {
        vendor('Payment.yeepay.yeepayMPay');
        $yeeconf = C('appyeepay');
        $conf    = $yeeconf['yeepay_config'];

        $yeepay = new yeepayMPay($conf['merchantaccount'],$conf['merchantPublicKey'],$conf['merchantPrivateKey'],$conf['yeepayPublicKey']);

        $order_id        = I('order_sn');
        $transtime       = NOW_TIME;
        $amount          = intval(I('order_amount')) * 100;
        $currency        = 156;
        $product_catalog = '53';
        $product_name    = '全球体育：'.I('order_amount').'元充值';
        $identity_type   = 2;
        $identity_id     = I('user_id');
        $user_ip         = get_client_ip();
        $terminaltype    = 3;
        $terminalid      = I('user_id');
        $callbackurl     = $yeeconf['notify_url'];
        $fcallbackurl    = $yeeconf['return_url'];

        $url = $yeepay->webPay($order_id, $transtime, $amount, $cardno = '', $idcardtype = '', $idcard = '', $owner = '', $product_catalog, $identity_id, $identity_type, $user_ip, $user_ua = '', $callbackurl, $fcallbackurl, $currency, $product_name, $product_desc = '', $terminaltype, $terminalid, $orderexp_date = null, $paytypes = '', $version = '');

        if (array_key_exists('error_code', $url))
            $this->ajaxReturn(5004);


        $this->ajaxReturn(['trade_no'=>$order_id,'url'=>$url]);
    }

}