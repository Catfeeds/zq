<?php
class WxPayConf_pub
{
	const APPID = 'wx4e27335fb7cfba88';
	const MCHID = '1301380001';
	const KEY = '5ccfe05101bda2ebfddda9131558e7c2';
	const APPSECRET = '5618405b6273235c665667ab9add0008';
	const JS_API_CALL_URL = 'http://mb.junguo.com/mobile/weixinpay/index.php';
	const SSLCERT_PATH = '../cert/cacert/apiclient_cert.pem';
	const SSLKEY_PATH = '../cert/cacert/apiclient_key.pem';
	const NOTIFY_URL = 'http://mb.junguo.com/mobile/weixinpay/return/notify_url.php';
	const CURL_TIMEOUT = 30;
}
	
?>