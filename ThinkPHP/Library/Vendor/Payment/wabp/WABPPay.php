<?php
/**
 * 移动网页计费类
 */
class WABPPay
{
    //private $payUrl         = 'http://dev.10086.cn/wabp/wap/pay_h5.action'; 
    private $payUrl         = 'https://dev.10086.cn/wabps/wap/purchase_h5.action'; //wabp订购页 URL

    //private $payUrl         =  'https://dev.10086.cn/wabps/www/subscribe.action'; //pc订购页URL
    private $dsaPriKeyPath  = '';  //商户私钥路径
    private $wabpPubKeyPath = '';  //wabp公钥路径

    public function __construct($dsaPriKeyPath='',$wabpPubKeyPath='')
    {
        $this->dsaPriKeyPath  = $dsaPriKeyPath;
        $this->wabpPubKeyPath = $wabpPubKeyPath;
    }

	/**
	 * 生成支付订购地址
	 */
	public function getPayUrl($info)
	{
        $info['mid']  = $this->codePhone($info['mid']);
        $info['sign'] = $this->createSign($this->buildUrl($info));
        return $this->payUrl.'?'.$this->buildUrl($info,$null=false,$justUrl=true);
	}

    /**
     * 对手机号码加密
     */
    public function codePhone($phone)
    {
        $num2alph = [0 => 'R', 1 => 'I', 2 => 'Z', 3 => 'B', 4 => 'H', 5 => 'G', 6 => 'E', 7 => 'C', 8 => 'F', 9 => 'O'];
        $code = '';

        for ($i=0; $i < strlen($phone); $i++){
            $code .= $num2alph[$phone[$i]];
            if ($i == 4)
                $code .= 'KAF';
        }
        return $code;
    }

    /**
     * 将参数转换为url形式
     * @param  array   $data      参数数组
     * @param  boolean $null      false转为value=&value2=    true转为value=null&value2=null
     * @param  boolean $justUrl   只生成url地址
     */
	function buildUrl($data,$null=false,$justUrl=false)
    {
        if (!$justUrl)
            ksort($data);

        $url = '';
		foreach($data as $k=>$v){
            $url.=$k."=".($null && !$v && $v !== '0' ? 'null' : $v)."&";
		}
		$url = trim($url,"&");

        if ($justUrl)
            return $url;

        // $url = $this->bstr2bin($url);
        // $url = iconv('UTF-8', 'GB2312', $url);
        return $url;
	}

    /**
     * 字符串转换为二进制
     */
    function bstr2bin($input)
    {
        if (!is_string($input)) return null;
        $value = unpack('H*', $input);
        $value = str_split($value[1], 1);
        $bin = '';
        foreach ($value as $v){
            $b = str_pad(base_convert($v, 16, 2), 4, '0', STR_PAD_LEFT);
            $bin .= $b;
        }
        return $bin;
    }

	/**
     * 生成dsa签名
     */
	function createSign($data)
    {
	    $pri = file_get_contents($this->dsaPriKeyPath);
	    $res = openssl_pkey_get_private($pri);
		if (openssl_sign($data, $out, $res, OPENSSL_ALGO_DSS1)){
			return  base64_encode($out);
		}
    }

    /**
     * 校验dsa签名
     */
    function verifySign($data)
    {
        $sign = $data['sign'];
        unset($data['signMethod'],$data['sign']);
        $url   = $this->buildUrl($data,$null=true,$justUrl=false);
        $pub   = file_get_contents($this->wabpPubKeyPath);
        $pid   = openssl_get_publickey($pub);
        $valid = openssl_verify($url, base64_decode($sign), $pid, OPENSSL_ALGO_DSS1);
        return $valid === 1 ? true : false;
    }

    /**
     * XML编码
     * @param mixed $data 数据
     * @param string $encoding 数据编码
     * @param string $root 根节点名
     * @return string
     */
    public function XmlEncode($data, $encoding='utf-8', $root='mmarket')
    {
        $xml    = '<?xml version="1.0" encoding="' . $encoding . '"?>';
        $xml   .= '<'.$root.'>';
        $xml   .= $this->DataToXml($data);
        $xml   .= '</'. $root .'>';
        return $xml;
    }

    /**
     * 数据XML编码
     * @param mixed $data 数据
     * @return string
     */
    public function DataToXml($data)
    {
        $xml = '';
        foreach ($data as $key => $val) {
            is_numeric($key) && $key = "item id=\"$key\"";
            $xml    .=  "<$key>";
            $xml    .=  ( is_array($val) || is_object($val)) ? $this->DataToXml($val) : $val;
            list($key, ) = explode(' ', $key);
            $xml    .=  "</$key>";
        }
        return $xml;
    }
}