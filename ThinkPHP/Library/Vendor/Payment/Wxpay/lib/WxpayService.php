<?PHP
/**
 +------------------------------------------------------------------------------
 * WxPayService   微信支付服务类
 +------------------------------------------------------------------------------
 * Copyright (c) 2016 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <huangmg@qc.mail>
 +------------------------------------------------------------------------------
*/
class WxpayService
{
     /**
     * 数组转成xml字符串
     * @param array $arr
     * @return string
     */
    public function arrayToXml($arr)
    {
        $xml = '<xml>';
        foreach($arr as $key => $value) {
            $xml .= "<{$key}>";
            //$xml .= "<![CDATA[{$value}]]>";
            $xml .= $value;
            $xml .= "</{$key}>";
        }
        $xml .= '</xml>';
        return $xml;
    }

    /*
     * xml 转换成数组
     * @param string $xml
     * @return array
     */
    public function xmlToArray($xml)
    {
    	if(empty($xml)) return false;
        $xmlObj = simplexml_load_string(
                $xml,
                'SimpleXMLIterator',   //可迭代对象
                LIBXML_NOCDATA
        );

        $arr = [];
        $xmlObj->rewind(); //指针指向第一个元素
        while (1) {
            if( ! is_object($xmlObj->current()) )
            {
                break;
            }
            $arr[$xmlObj->key()] = $xmlObj->current()->__toString();
            $xmlObj->next(); //指向下一个元素
        }

        return $arr;
    }

    /**
     * MD5签名
     *
     * @param string $str 待签名字符串
     * @return string 生成的签名
     */
    public function signMd5($str)
    {
        return strtoupper(md5($str));
    }

    /**
     * https请求，CURLOPT_POSTFIELDS xml格式
     * @param  string $xml    发送的xml内容
     * @param  string $url    请求地址
     * @param  string $second  超时时间
     * @return string         响应文本
     */
	public function postXmlCurl($xml,$url,$second=30)
	{
		$ch = curl_init();
        curl_setopt($ch,CURLOPT_TIMEOUT,$second);  //超时时间
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);  //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $data = curl_exec($ch);
        if($data === false)
        {
            $error = curl_errno($ch);
            echo "curl出错，错误码:$error"."<br>";
            echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a><br />";
            curl_close($ch);
            return false;
        }
        else
        {
            curl_close($ch);
            return $data;
        }
	}

}