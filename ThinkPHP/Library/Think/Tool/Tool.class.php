<?php
/**
 * 常用工具类
 */
namespace Think\Tool;

class Tool
{
    /**
     * tool类构造函数
     */
    public function _initialize()
    {
    }
	/**
     * 获取指定数组打乱
     *
     * @param array  $array 要处理的数组
     * @param string $num   返回数量
     *
     * @return array 处理后的数组
     */
    public static function getRandArray($array, $num)
    {
        $arrcount = count($array);
        if (!$arrcount) {
            return '';
        }
        if ($arrcount < $num) {
            $num = $arrcount;
        }

        $keyarray = array_keys($array);
        shuffle($keyarray);

        for ($i = 0; $i < $num; $i ++) {
            if ($num == 1) {
                $newarray [$i] = $array [$keyarray[0]];
            } else {
                $newarray [$i] = $array [$keyarray [$i]];
            }

        }

        return $newarray;
    }
	/**
     * 异步获取数据
     *
     * @param string $url 数据源链接
     *
     * @return string html代码
     */
    public static function ajaxProcess($url)
    {
        echo '<script type="text/javascript" src="http://js1.fuyuesoft.com/js/jquery-1.8.3.min.js"></script>';
        echo '<script type="text/javascript">';
        echo '$.ajax({
                url:"'.$url.'",
                success : function(data){/*alert(data)*/}
            });
        ';
        echo '</script>';
    }
	/**
     * 进行json编码
     *
     * @param array $array 要编码的数组
     *
     * @return json 编码后的数据
     */
    public static function jsonEncode($array)
    {
        return json_encode($array);
    }

    /**
     * 解码json字符串
     *
     * @param json $json 要解码的json数据
     *
     * @return array 解码后的数据
     */
    public static function jsonDecode($json)
    {
        return json_decode($json, true);
    }

    /**
     * 调试工具方法
     *
     * @return string 输出调试数据
     */
    public static function debug()
    {
        static $start_time = null;
        static $start_code_line = 0;

        $call_info = array_shift(debug_backtrace());
        $code_line = $call_info['line'];
        $file = array_pop(explode('/', $call_info['file']));

        if ($start_time === null) {
            print "debug ".$file."> initialize<br/>\n";
            $start_time = time() + microtime();
            $start_code_line = $code_line;
            return 0;
        }

        printf(
            "debug %s> code-lines: %d-%d time: %.4f mem: %d KB<br/>\n",
            $file,
            $start_code_line,
            $code_line,
            (time() + microtime() - $start_time),
            ceil(memory_get_usage()/1024)
        );
        $start_time = time() + microtime();
        $start_code_line = $code_line;
    }

	/**
     * 去掉html标签
     *
     * @param string $str 要处理内容
     *
     * @return string 处理后内容	备注：用PHP自带方法string strip_tags(string str);也可去掉
     */
    public static function html2text($str)
    {
        $str = strip_tags($str);
        $str = trim($str);
        $str = preg_replace("/<sty(.*)\\/style>|<scr(.*)\\/script>|<!--(.*)-->/isU", "", $str);
        $alltext = "";
        $start = 1;
        for ($i=0; $i<strlen($str); $i++) {
            if ($start==0 && $str[$i]==">") {
                $start = 1;
            } elseif ($start==1) {
                if ($str[$i]=="<") {
                    $start = 0;
                    $alltext .= " ";
                } elseif (ord($str[$i])>31) {
                    $alltext .= $str[$i];
                }
            }
        }
        $alltext = str_replace(" ", "", $alltext);
        $alltext = preg_replace("/&([^;&]*)(;|&)/", "", $alltext);
        $alltext = preg_replace("/[ ]+/s", " ", $alltext);
        return $alltext;
    }
    /**
     * 最简单的XML转数组
     * @param string $xmlstring XML字符串
     * @return array XML数组
     */
    function simplest_xml_to_array($xmlstring) {
        return json_decode(json_encode((array) simplexml_load_string($xmlstring)));
    }
	/**
     * 去掉html中的a标签
     *
     * @param string $str 要处理内容
     *
     * @return string 处理后内容
     */
    public static function delLink($str)
    {
        return preg_replace('/\>\><a.+?>*<\/a>/','',$str);
    }

	/**
    * 代替file_get_contents
    *
    * @param string $strUrl 获取的url内容
    *
    * @return string 所指向链接的内容
    */
    public static function url_get_contents($strUrl,$header='')
    {
        $curlobj = curl_init();
        if(!empty($header)){
            curl_setopt($curlobj, CURLOPT_HTTPHEADER, $header); 
        }
        curl_setopt($curlobj, CURLOPT_URL, $strUrl);
        curl_setopt($curlobj, CURLOPT_HEADER, 0);
        curl_setopt($curlobj, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curlobj, CURLOPT_ENCODING, "");
        curl_setopt($curlobj, CURLOPT_SSL_VERIFYPEER, false);  
        curl_setopt($curlobj, CURLOPT_VERBOSE, true);  
        //curl_setopt($curlobj, CURLOPT_PROXY, '125.106.224.214:6666');
        $response = curl_exec($curlobj);
        if (curl_errno($curlobj) != 0) {
            echo 'false';
        }
        curl_close($curlobj);
        return $response;
        // $strUrl = self::httpBuildUrl($strUrl);
        // $ch = curl_init($strUrl);
        // curl_setopt($ch, CURLOPT_HEADER, 0);
        // curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        // curl_setopt($ch, CURLOPT_HTTPGET, true);
        // curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
        // curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        // curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        // //curl_setopt($ch, CURLOPT_PROXY, "192.168.11.45:80");218调试 改为自己的ip即可
        // curl_setopt($ch, CURLOPT_PROXY, '125.106.224.214:6666');
        // $response = curl_exec($ch);
        // if (curl_errno($ch) != 0) {
        //     return false;
        // }
        // curl_close($ch);
        // return $response;
    }

	/**
    * 对于请求的url添加一个cookies的参数
    *
    * @param string $strUrl 获取的url内容
    *
    * @return string 所指向链接的内容
    */
    public static function httpBuildUrl($strUrl)
    {
        $parseUrlArr   = parse_url($strUrl);
        //检测域名
        if (strpos($parseUrlArr['host'], "fuyuesoft.com") == false) {
            return $strUrl;
        }
        $queryArr      = explode("&", $parseUrlArr['query']);
        $queryBuildArr = array();
        foreach ($queryArr as $key => $value) {
            $valueArr                          = explode("=", $value);
            $queryBuildArr[trim($valueArr[0])] = trim($valueArr[1]);
        }
        $queryBuildArr['userSelectCity'] = $_COOKIE["userSelectCity"];
        $queryBuildArr['errorUrl'] = $_SERVER['REQUEST_URI'];
        $queryBuildStr             = http_build_query($queryBuildArr);
        $strUrl = $parseUrlArr['scheme']."://".$parseUrlArr['host'].$parseUrlArr['path']."?".$queryBuildStr;
        return $strUrl;
    }

	/**
     * 判断空值(数字0不为空)
     *
     * @param string $str 字符串
     *
     * @return string
     */
    public static function isEmpty ($str)
    {
        if (!empty($str)) {
            return false;
        } else {
            if ($str===0) {
                return false;
            } else {
                return true;
            }
        }
    }

	/**
     * 获取当前页面url
     *
     * @param boolean $urlEncode 是否需要进行url编码
     *
     * @return string
     */
    public static function getCurrentURL($urlEncode = false)
    {
        $currentURL = '';
        $currentURL = (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? 'https' : 'http';
        $currentURL .= '://';

        if ($_SERVER["SERVER_PORT"] != "80") {
            $currentURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $currentURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }
        if ($urlEncode) {
            $currentURL = urlencode($currentURL);
        }
        return $currentURL;
    }

	/**
     * 年周数,格式：201312
     *
     * @param string $time 时间
     *
     * @return string
     */
    public static function getWeekNum($time)
    {
        $weekNum = strftime("%Y%V", $time);
        return $weekNum;
    }

	 /**
     * 处理时间
     * 规则说明：<60分钟显示分钟,<24小时显示小时,<7天显示天,其他显示月日
     *
     * @param int $time unixstamp时间
     * @param int $type 类型,控制>7天的显示格式
     *
     * @return string
     */
    public static function processTime($time, $type=0)
    {
        $timeString = time()-$time;

        if ($timeString>60*60) {
            if ($timeString>24*60*60) {
                if ($timeString>7*24*60*60) {
                    switch ($type) {
                    case 0:
                        return date('Y-m-d H:i', $time);
                        break;
                    case 1:
                        //咨询首页使用
                        return date('m-d H:i', $time);
                        break;
                    }
                } else {
                    return ceil($timeString/(24*60*60)).'天前';
                }
            } else {
                return ceil($timeString/(60*60)).'小时前';
            }

        } else {
            if ($timeString/60 < 1) {
                return "1分钟前";
            } else {
                return ceil($timeString/60).'分钟前';
            }
        }
    }

	/**
     * 集成信息数组并加密为cookie
     *
     * @param string $name      cookie名称
     * @param array  $params    信息数组
     * @param int    $cacheTime 缓存时间
     *
     * @return #
     */
    public static function setArrayCookie($name, $params, $cacheTime=86400)
    {
        $params 	= self::jsonEncode($params);
		$cookieStr 	= self::authcode($params, 'ENCODE', 'fe01356c504a07d4');
        $path = C( 'COOKIE_PATH' );
        setcookie($name, $cookieStr, time()+$cacheTime, $path );
    }

	/**
     * 解密集成信息数组cookie
     *
     * @param string $name cookie名称
     *
     * @return array
     */
    public static function getArrayCookie($name)
    {
        if (!empty($_COOKIE[$name])) {
            $info = self::authcode($_COOKIE[$name], 'DECODE', 'fe01356c504a07d4');
            if (!empty($info)) {
                $arrayCookie = self::jsonDecode($info);
                return $arrayCookie;
            }
            return '';
        } else {
            return false;
        }
    }

	/**
     * 集成信息数组并加密为session
     *
     * @param string $name      session名称
     * @param array  $params    信息数组
     *
     * @return #
     */
    public static function setArraySession($name, $params)
    {
        $params = self::jsonEncode($params);
		$_SESSION[$name] = $params;
    }

	/**
     * 解密集成信息数组session
     *
     * @param string $name session名称
     *
     * @return array
     */
    public static function getArraySession($name)
    {
		$tempVar = $_SESSION[$name];
        if (!empty($tempVar)) {
                $arraySession = self::jsonDecode($tempVar);
                return $arraySession;
        } else {
            return false;
        }
    }
	/**
     * 加密与解码函数
     *
     * @param string $string    加密的字符
     * @param string $operation 加密或解密
     * @param string $key       key值
     *
     * @return 加密或解密数据
     */
    public static function authcode($string, $operation, $key = '')
    {
        $key = md5($key ? $key : md5('fe01356c504a07d4'.$_SERVER['HTTP_USER_AGENT']));
        $key_length = strlen($key);

        $string = $operation == 'DECODE' ? base64_decode($string) : substr(md5($string.$key), 0, 8).$string;
        $string_length = strlen($string);

        $rndkey = $box = array();
        $result = '';

        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8).$key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }
	/**
     * 自动检测是否UTF-8编码,是则转换成对象编码,不是则直接返回
     *
     * @param string $string      字符串
     * @param string $outEncoding 转换目标编码
     *
     * @return string
     */
    public static function turnEncoding($string, $outEncoding='utf8')
    {
        $is_utf8 =  preg_match('%^(?:[\x09\x0A\x0D\x20-\x7E]| [\xC2-\xDF][\x80-\xBF]|  \xE0[\xA0-\xBF][\x80-\xBF] | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}    |  \xED[\x80-\x9F][\x80-\xBF] |  \xF0[\x90-\xBF][\x80-\xBF]{2}  | [\xF1-\xF3][\x80-\xBF]{3}  |  \xF4[\x80-\x8F][\x80-\xBF]{2} )*$%xs', $string);

		//对于utf8编码 还需要进行检测
        ///gbk中的汉字，但是满足utf-8的编码规范，对于这些汉字当做gbk字符处理 - 由于存在问题，暂不启用
        /*if ($is_utf8) {
            $charstr = '陇搂篓掳卤路脳脿谩猫茅锚矛铆貌贸梅霉煤眉脓臎墨艅艌艒奴菐菒菕菙菛菢菤菧蓱伞藟藠藡藱螒螔螕螖螘螙螚螛螜螝螠螡萤螣螤巍危韦违桅围唯惟伪尾纬未蔚味畏胃喂魏位渭谓尉慰蟺蟽蟿蠀蠁蠂蠄蝇衼袗袘袙袚袛袝袟袠袡袣袥袦袧袨袩袪小孝校肖啸笑效楔些歇蝎鞋协挟携邪斜胁谐写械卸蟹懈泄泻谢屑薪芯锌褉褋褌褍褎褏褑褔褕褖褗褘褜褝褞褟褢';
            $charsetArr = str_split($charstr, 2);
            for ($i = 0; $i < count($charsetArr); $i++) {
                if (strpos($string, $charsetArr[$i]) !== false) {
                    $is_utf8 = false;
                    break;
                }
            }
        }*/
        if ($is_utf8) {
            if ($outEncoding=='utf8') {
                return $string;
            } else {
                //先用mb函数进行转换，若成功转换后编码检测与目标编码相同则输出，不同则用iconv重新转
                $tmp = mb_convert_encoding($string, $outEncoding, "UTF-8");
                if ($outEncoding == mb_detect_encoding($tmp, array('GB2312','GBK','UTF-8'), true)) {
                    return $tmp;
                } else {
                    return iconv('UTF-8', $outEncoding, $string)?iconv('UTF-8', $outEncoding, $string):$string;
                }
            }
        } else {
            //源编码不UTF-8的，默认为GBK
            if ($outEncoding=='gbk') {
				return iconv('GBK', 'UTF-8', $string)?iconv('GBK', 'UTF-8', $string):$string;
            } elseif ($outEncoding=='utf8') {
				return $string;
            } else {
                return iconv('GBK', $outEncoding, $string)?iconv('GBK', $outEncoding, $string):$string;
            }
        }
    }
	/**
    * 检测浏览器的cookies是否禁用
    *
    * @return #
    */
    public function checkCookies()
    {
        setcookie('checkCookies', '1', time()+30, '/', '.fuyuesoft.com');
        if (!isset($_COOKIE['checkcookies'])) {
            $vars = base64_encode("tip=系统检测到浏览器禁用了cookies 请手动开启浏览器的cookies功能");
            header("Location:http://web.fuyuesoft.com/?a=error&vars={$vars}");
            exit;
        }
        unset($_COOKIE['checkCookies']);
    }

    /**
    * 使用curl的post方式获取数据
    *
    * @param String  $url     请求地址
    * @param Array   $data    请求参数
    * @param Integer $timeout 超时时间（秒）
    *
    * @return mix
    */
    public static function file_post_contents($url, $data = array(), $timeout = 20)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);  //设置访问路径
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);  // 将结果缓冲，不立刻输出
        curl_setopt($ch, CURLOPT_POST, 1);   //是否为post方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data); //	post 数据

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;

    }
	/**
    * 使用curl获取数据
    *
    * @param String  	$url     	请求地址
	* @param boolean    $isHttps 	是否https请求
	* @param String  	$method  	请求类型
    * @param Array   	$data    	请求参数
    * @param Integer 	$timeout 	超时时间（秒）
    *
    * @return mix
    */
	public static function getHttpContent($url,$isHttps = false,$method = 'GET', $postData = array(),$timeout = 30)
    {
        if (!empty($url)) {
            try {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url); //设置访问路径
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // 将结果缓冲，不立刻输出
                curl_setopt($ch, CURLOPT_TIMEOUT, $timeout); //30秒超时
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
                //如果是https请求，不验证证书和
                if($isHttps){
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);// https请求 不验证证书和hosts
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
                }
                //如果是post请求
                if (strtoupper($method) == 'POST') {
                    $curlPost = is_array($postData) ? http_build_query($postData) : $postData;
                    curl_setopt($ch, CURLOPT_POST, 1);  //是否为post方式
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);  //post 数据
                }
                $data = curl_exec($ch);
                curl_close($ch);
            } catch (Exception $e) {
                $data = null;
            }
        }
        return $data;
    }
    
    /**
     * 获取GET、POST的参数，并可选择是否进行过滤
     *
     * @param string $variable 变量名
     * @param array  $filter   指定过滤规则:int,css,js,tag,text,mysql,char,trim（注意顺序）
     * @param string $default  指定为空时的默认值
     * @param string $type     指定选择GET/POST,空时优先选择POST
     *
     * @return #
     */
    static public function request($variable, $filter=array(), $default='', $type='')
    {
        if ($type=='POST') {
            $value = isset($_POST[$variable])?$_POST[$variable]:null;
        } elseif ($type=='GET') {
            $value = isset($_GET[$variable])?$_GET[$variable]:null;
        } elseif ($type=='') {
            $value = isset($_POST[$variable])?$_POST[$variable]:(isset($_GET[$variable])?$_GET[$variable]:null);
        }
        //默认值处理,字符串0和数字0都转为数字0
        if ($value==='0'||$value===0) {
            $value = 0;
        } elseif (empty($value)) {
            if ($default==='0'||$default===0) {
                $value = 0;
            } elseif ($default===null) {
                $value = null;
            } elseif ($default===array()) {
                $value = array();
            } else {
                $value = $default;
            }
        }
		//如果是ASCII编码不用过滤直接返回
		if (mb_detect_encoding($value)=="ASCII"){
			return $value;
		}
		//过滤数据并返回
        $value = Filter::clean($value, $filter);
		return $value;
    }


    /**
    * 获取内容里的图片
    *
    * @param string  $text        #内容
    * @param boolean $isStripHost #是否去掉图片链接里的域名
    *
    * @return array #数组
    */
    public static function getTextImgUrl($text, $isStripHost = true)
    {
        $text = stripslashes($text);//去掉反斜杠

        $matches = array();
        preg_match_all("/<img.*\>/isU", $text, $matches);
        $matches = $matches[0];

        $imglist = array();
        if (is_array($matches) && !empty($matches)) {
            $pattern2 = "#src=('|\")(.*)('|\")#isU";//正则表达式
            foreach ($matches as $key=>$value) {
                $imgarr = array();
                preg_match_all($pattern2, $value, $imgarr);

                $url = $imgarr[2][0];
                if (isset($url) && !empty($url)) {
                    $urlArr = array();
                    if ($isStripHost === true) {
                        $url = str_replace("\\", "/", $url);
                        $urlArr = split("/", $url);
                        unset($urlArr[0], $urlArr[1], $urlArr[2]);
                        $url = implode("/", $urlArr);
                    }
                    $imglist[] = $url;
                }

            }
            $imglist = array_values($imglist);
        }
        return $imglist;
    }

	/**
    * 获取客户端ip
    *
    * @return #
    */
	public static function getip()
    {
        $ip = false;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = false;
            }

            for ($i = 0; $i<count($ips); $i++) {
                if (!eregi("^(10|172\.16|127\.0|192\.168)\.", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
    }

    /**
     * 此函数完成带汉字的字符串取串
     *
     * @param string $inputstr  输入的字符串
     *
     * @param string $mylen 长度
	 *
	 * @param string $laterStr 如果没有超出总长度，结尾附加字符串，默认为…(省略号)
     *
     * @return #
     */
	public static function substr_cn($inputstr,$mylen, $laterStr='…'){
		$len=strlen($inputstr);
		$content='';
		$count=0;
		for($i=0;$i<$len;$i++){
		   if(ord(substr($inputstr,$i,1))>127){
			$content.=substr($inputstr,$i,2);
			$i++;
		   }else{
			$content.=substr($inputstr,$i,1);
		   }
		   if(++$count==$mylen){
			break;
		   }
		}
		if ($len > $mylen) {
			return $content.$laterStr;
		}
		return $content;
	}

	/**
     * 根据ip获取ip归属地区信息
     *
     * @param string $ip  输入的ip
	 *
     * @return #
     */
	public static function ip2Area($ip='58.63.88.59'){
		$ip2 = self::getip();
		if ($ip2=='127.0.0.1') {
			//$ip = '58.63.88.59';
            $ip = '14.147.145.12'; //广州
		} else {
			$ip = $ip2;
		}
		$url = "http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
		$data = file_get_contents($url);
		$obj = json_decode($data);
		$code = $obj->code;
		if ($code != 0) {
			//没有查到默认返回广州地区的信息
			return array(
				'id' => '440100',
				'province' => '广东',
				'city' => '广州',
				'area' => '华南',
			);
		}
		$obj2 = $obj->data;
		$province = $obj2->region;
		$city = $obj2->city;
		$country = $obj2->county;
		$area = $obj2->area;
		$isp = $obj2->isp;

		$county_id = $obj2->county_id;
		$city_id = $obj2->city_id;
		$province_id = $obj2->province_id;
		if ($county_id>0) {
			$areaid = $county_id;
		} elseif($city_id>0) {
			$areaid = $city_id;
		} else {
			$areaid = $province_id;
		}
		$province = str_replace('省', "", $province);
		$province = str_replace('市', "", $province);
		$city = str_replace("市", "", $city);
		$country = str_replace("县", "", $country);
		$country = str_replace("区", "", $country);

		$areaInfo['id'] 		= $areaid;
		$areaInfo['province'] 	= $province;
		$areaInfo['city'] 		= $city;
		$areaInfo['country'] 	= $country;
		$areaInfo['area'] 		= $area;
		$areaInfo['ip'] 		= $ip;
		$areaInfo['isp'] 		= $isp;

		return $areaInfo;
	}
	/**
     * 是否是wap移动设备访问
     *
     * @param #
	 *
     * @return boolean
     */
	public static function isWap(){
		if(isset($_SERVER['HTTP_VIA'])) return TRUE;
		if(isset($_SERVER['HTTP_X_NOKIA_CONNECTION_MODE'])) return TRUE;
		if(isset($_SERVER['HTTP_X_UP_CALLING_LINE_ID'])) return TRUE;
		if(strpos(strtoupper($_SERVER['HTTP_ACCEPT']), 'VND.WAP.WML') > 0) return TRUE;
		$http_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? trim($_SERVER['HTTP_USER_AGENT']) : '';
		if($http_user_agent == '') return TRUE;
		$mobile_os = array('Google Wireless Transcoder', 'Windows CE', 'WindowsCE', 'Symbian', 'Android', 'armv6l', 'armv5', 'Mobile', 'CentOS', 'mowser', 'AvantGo', 'Opera Mobi', 'J2ME/MIDP', 'Smartphone', 'Go.Web', 'Palm', 'iPAQ');
		$mobile_token = array('Profile/MIDP', 'Configuration/CLDC-', '160×160', '176×220', '240×240', '240×320', '320×240', 'UP.Browser', 'UP.Link', 'SymbianOS', 'PalmOS', 'PocketPC', 'SonyEricsson', 'Nokia', 'BlackBerry', 'Vodafone', 'BenQ', 'Novarra-Vision', 'Iris', 'NetFront', 'HTC_', 'Xda_', 'SAMSUNG-SGH', 'Wapaka', 'DoCoMo', 'iPhone', 'iPod');
		$flag_os = $flag_token = FALSE;
		foreach($mobile_os as $val){
			if(strpos($http_user_agent, $val) > 0){ $flag_os = TRUE; break; }
		}
		foreach($mobile_token as $val){
			if(strpos($http_user_agent, $val) > 0){ $flag_token = TRUE; break; }
		}
		if($flag_os || $flag_token) return TRUE;
		return FALSE;
	}

	/**
    * 获取客户端天气预报信息
    *
    * @return #
    */
	public static function getWeather()
    {
		//获取城市
		$areaInfo = self::ip2Area();
		//调用第三方天气API（百度）
        $url  = "http://api.map.baidu.com/telematics/v3/weather?location={$areaInfo[city]}&output=json&ak=A72e372de05e63c8740b2622d0ed8ab1";
		$data = self::file_get_contents_for_array($url);
		//转码
		$data = self::array_iconv("gbk", "utf-8", $data);
        return $data['results'][0]['weather_data'];
    }
	/**
    * 改写file_get_contents使其真正抓到内容，解决超时抓不到内容的问题
	*
	* @param string $url   #请求的URL
	*
	* @param string $limit 尝试次数
    *
    * @return string #
    */
	public static function file_get_contents_for_array($url, $limit = 3){
		$str = @file_get_contents($url);
		$data = json_decode($str, true);

		if (empty($data)){
			for ($i=$limit-1;$i>0;$i--){
				self::file_get_contents_for_array($url,$i);
			}
		}
		$data = self::array_iconv('utf-8','gbk',$data);
		return $data;
	}
	/**
     * 数组批量转码
     *
     * @param string $in_charset  输入的编码
	 *
	 * @param string $out_charset 输出的编码
	 *
	 * @param string $arr         数组
	 *
     * @return array #
     */
	public static function array_iconv($in_charset,$out_charset,$arr){
        return eval('return '.iconv($in_charset,$out_charset,var_export($arr,true).';'));
	}

	/**
     * 判断是否来访并验证域名授权
     *
     * @return string
     */
    public static function verRefererURL()
    {
        $url = $_SERVER['HTTP_REFERER'];	//获取完整的来路URL
		if (empty($url)) {
			return 0;
		}
		$str   = str_replace("http://","",$url);  	//去掉http://
		$str   = str_replace("https://","",$url);  	//去掉https://
		$strdomain = explode("/",$str);           	// 以“/”分开成数组
		$domain    = $strdomain[0];              	//取第一个“/”以前的字符
		return $domain;
    }
	/**
    * 字符串中提取数字
	*
	* @param string $str   #字符串
    *
    * @return string #
    */
	public static function findNum($str=''){
		$str=trim($str);
		if(empty($str)){return '';}
		$result='';
		for($i=0;$i<strlen($str);$i++){
			if(is_numeric($str[$i])){
				$result.=$str[$i];
			}
		}
		return $result;
	}
	/**
    * 截取字符串指定范围内容
	*
	* @param string $str   		#字符串
    *
	* @param string $start_str  #开始字符串
    *
	* @param string $end_str   	#结束字符串
    *
    * @return string #
    */
	public static function getStr($str, $start_str, $end_str){
		$start_pos = strpos($str,$start_str)+strlen($start_str);
		$end_pos = strpos($str,$end_str);
		$c_str_l = $end_pos - $start_pos;
		$content = substr($str,$start_pos,$c_str_l);
		return $content;
	}

	/**
	 * 计算两个坐标之间的距离(米)
	 * @param float $fP1Lat 起点(纬度)
	 * @param float $fP1Lon 起点(经度)
	 * @param float $fP2Lat 终点(纬度)
	 * @param float $fP2Lon 终点(经度)
	 * @return int
	*/
	public static function distanceBetween($fP1Lat, $fP1Lon, $fP2Lat, $fP2Lon){
		$fEARTH_RADIUS = 6378137;
		//角度换算成弧度
		$fRadLon1 = deg2rad($fP1Lon);
		$fRadLon2 = deg2rad($fP2Lon);
		$fRadLat1 = deg2rad($fP1Lat);
		$fRadLat2 = deg2rad($fP2Lat);
		//计算经纬度的差值
		$fD1 = abs($fRadLat1 - $fRadLat2);
		$fD2 = abs($fRadLon1 - $fRadLon2);
		//距离计算
		$fP = pow(sin($fD1/2), 2) +
			  cos($fRadLat1) * cos($fRadLat2) * pow(sin($fD2/2), 2);
		return intval($fEARTH_RADIUS * 2 * asin(sqrt($fP)) + 0.5);
	}
	/**
	 * 百度坐标系转换成标准GPS坐系
	 *
	 * @param float  $longitude   		#经度
	 *
	 * @param float  $latitude  		#纬度
	 *
	 * @return string #转换后的标准GPS值
	*/
	public static function BD09LLtoWGS84($longitude, $latitude){
		$x = $longitude;
		$y = $latitude;
		$Baidu_Server = "http://api.map.baidu.com/ag/coord/convert?from=0&to=4&x={$x}&y={$y}";
		$result = @file_get_contents($Baidu_Server);
		$json = json_decode($result);
		if($json->error == 0){
			$bx = base64_decode($json->x);
			$by = base64_decode($json->y);
			$GPS_x = 2 * $x - $bx;
			$GPS_y = 2 * $y - $by;
			return $GPS_x.','.$GPS_y;//经度,纬度
		} else {
			return $longitude.','.$latitude;
		}
	}
	/**
	 * 根据经纬度获取地址--请求接口参考：http://developer.baidu.com/map/index.php?title=webapi/guide/webservice-geocoding
	 *
	 * @param float  $longitude   		#经度
	 *
	 * @param float  $latitude  		#纬度
	 *
	 * @return string 转换后的标准GPS值:
	*/
	public static function reGeocoding($longitude, $latitude){
		$url = "http://api.map.baidu.com/geocoder/v2/?ak=pCVWSzGCBcUtHRW3tbHYSrcI&location={$latitude},{$longitude}&output=json&pois=0";
		$result = @file_get_contents($url);
		$data = json_decode($result, true);
		if ($data){
			return $data;
		} else {
			return false;
		}
	}

	/**
    * 二维数组去重
	*
	* @param array $arr   		#需要去重的二位数组
    *
	* @param string $key  		#指定需要去重的键
    *
    * @return array
    */
	public static function getUniqueArray($arr, $key)
	{
		$rAr=array();
		for($i=0;$i<count($arr);$i++)
		{
			if(!isset($rAr[$arr[$i][$key]]))
			{
				$rAr[$arr[$i][$key]]=$arr[$i];
			}
		}
		return array_values($rAr);
	}
	/**
    * 将地址编码转成地址数组
	*
	* @param string $areaCode	#地区编码
    *
	* @param string $type		#返回类型，1为字符串，2为数组
    *
    * @return array
    */
	public static function areaCode2address($areaCode=440100, $type=1)
	{
		if (empty($areaCode)) {
			return false;
		}
		$areaInfo = M("Area")->find($areaCode);
		if ($type==2) {
			return $areaInfo;
		} else {
			return trim($areaInfo['province']." ".$areaInfo['city']." ".$areaInfo['country']);
		}

	}
	/**
    * 列出所有文件夹所有文件
	*
	* @param string $dir		#目录
    *
	* @param array $suffixArr	#要显示的文件后缀数组
    *
    * @return array
    */
	public static function showFileList($dir, $suffixArr)
	{
		$array = array();
		foreach($suffixArr as $k=>$v)
		{
			$pattern = $dir.'*.'.$v;
			$all = glob($pattern);
			$array = array_merge($array,$all);
		}
		return $array;
		/*
		输出格式-------------------------------------------------------------------------------
		array(1) {
		  [0] => string(61) "/opt/webroot/booogo.com/files.booogo.com/dyimages/default.png"
		}
		---------------------------------------------------------------------------------------
		*/
	}
	/**
    * 列出远程目录所有文件夹所有文件
	*
	* @param string $dir		#目录
    *
    * @return array
    */
	public static function showFileListOrder($dir){
		//文件服务器host
        $CURL_HOST = C('IMG_SERVER');
        $accessAuthKey = '95f24f4c82da92e69cccc16a71068b45';
        //初始化要传的参数
        $sendData = array();
        //客户端与服务器通信的安全密钥
        $sendData['accessAuthKey']  = $accessAuthKey;
        $sendData['dir']            = $dir;
        //接收端调用方法
        $method = "showFileListOrder";
        $ch             = curl_init();
        $tmp_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $url = $CURL_HOST . '/?m=Home&c=FileService&a='.$method;
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $tmp_user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sendData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $data = curl_exec($ch);
        //调试模式
        /*if (1==1) {
            if(curl_errno($ch)){
                //error
                dump(curl_error($ch));
            }
            dump($data);
            dump($sendData);
            $reslut = curl_getinfo($ch);
            dump($reslut);
        }*/
        curl_close($ch);
        $info = array();
        if($data){
            $info = json_decode($data, true);
        }
        return $info;
	}

    /**
     * 动态或静态图片域名匹配
     *
     * @param string $url 图片路径格式规范，看文档
     *
     * @return string 带域名规范的图片路径
     */
    public static function imagesReplace($url)
    {
        if(!empty($url)){
            if(strpos($url ,  'http') !== false){
                return $url;
            }
            $IMG_SERVER = C('IMG_SERVER');
            return $IMG_SERVER.$url;
        }
        /*$host_url = '';
        $static_host_url = array(
            0 => 'http://img1.boooog.com',
            1 => 'http://img2.boooog.com',
            2 => 'http://img3.boooog.com'
        );
        $dyn_host_url = array(
            0 => 'http://dy1.boooog.net',
            1 => 'http://dy2.boooog.net',
            2 => 'http://dy3.boooog.net'
        );
        $file_host_url = array(
            0 => 'http://file1.boooog.cn',
            1 => 'http://file2.boooog.cn',
            2 => 'http://file3.boooog.cn'
        );
        //去掉前后的空格
        $url=trim($url);
        $begin = explode('/', $url);
        switch ($begin[1]) {
        case 'images':
            $host_url = $static_host_url;
            break;
        case 'dyimages':
            $host_url = $dyn_host_url;
            break;
        case 'dyfiles':
            //self::filesReplace($url);
            $host_url = $file_host_url;
            break;
        }

        if ($host_url) {
            $name = explode('?', $url);
            $pos = (abs(crc32(trim($name[0])))%3);
            $host_url = $host_url[$pos].$url;
            return $host_url;
        } else {
            return '';
        }*/

    }
    /**
     * 上传的非图片类文件域名匹配
     *
     * @param string $url 文件路径格式规范
     *
     * @return string 带域名规范的文件路径
     */
    public static function filesReplace($url)
    {
        $host_url = '';
        $dyn_host_url = array(
            0 => 'http://file1.boooog.cn',
            1 => 'http://file2.boooog.cn',
            2 => 'http://file3.boooog.cn'
        );
        //去掉前后的空格
        $url=trim($url);
        $begin = explode('/', $url);
        switch ($begin[1]) {
        case 'dyfiles':
            $host_url = $dyn_host_url;
            break;
        }
        if ($host_url) {
            $name = explode('?', $url);
            $pos = (abs(crc32(trim($name[0])))%3);
            $host_url = $host_url[$pos].$url;
            return $host_url;
        } else {
            return '';
        }

    }

    /**
    * 远程上传base64数据流文件
    *
    * @param string     $imgStr     #base64字符串
    * @param string     $imgDir     #存放图片的路径 多级目录以逗号隔开
    * @param string     $imgName    #文件名含后缀
    * @param json       $thumbJson  #储存了要裁剪的json数组
    *
    * @return array
    */
    public static function uploadBase64($imgStr, $imgDir, $imgName, $thumbJson=NULL)
    {
        //文件服务器host
        $CURL_HOST = C('IMG_SERVER');
        $accessAuthKey = '95f24f4c82da92e69cccc16a71068b45';
        //初始化要传的参数
        $sendData = array();
        //客户端与服务器通信的安全密钥
        $sendData['accessAuthKey']  = $accessAuthKey;
        $sendData['imgStr']         = $imgStr;
        $sendData['imgDir']         = $imgDir;
        $sendData['imgName']        = $imgName;
        $sendData['thumbJson']      = $thumbJson;
        //接收端调用方法
        $method = "uploadBase64";
        $ch             = curl_init();
        $tmp_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $url = $CURL_HOST . '/?m=Home&c=FileService&a='.$method;
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $tmp_user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sendData);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $data = curl_exec($ch);
        //调试模式
        /*if (1==1) {
            if(curl_errno($ch)){
                //error
                dump(curl_error($ch));
            }
            dump($data);
            dump($sendData);
            $reslut = curl_getinfo($ch);
            dump($reslut);
        }*/
        curl_close($ch);
        $info = array();
        if($data){
            $info = json_decode($data, true);
        }
        return $info;
    }

	/**
    * 随机生成指定长度字符串函数
	*
	* @param int $length	#长度
	* @param int $type		#生成类型，1为文本，2为密码
    *
    * @return string
    */
	public static function randStr($length=6, $type=1)
	{
		if ($type==1){
			//文本
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		} else {
			//密码
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
		}
		$str = '';
		for ( $i = 0; $i < $length; $i++ )
		{
			// 这里提供两种字符获取方式
			// 第一种是使用substr 截取$chars中的任意一位字符；
			// 第二种是取字符数组$chars 的任意元素
			// $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
			$str .= $chars[ mt_rand(0, strlen($chars) - 1) ];
		}
		return $str;
	}
	/**
    * 判断是echo还是return数据
	* @param type 			$type 		#数据类型，1为bool，2为json，3为字符串
	* @param string/array 	$value 		#要操作的数据值
	* @param bool 			$isRetrun 	#是否强制为return,默认要判断
    *
    * @return  #
    */
    public static function judgmentReturnOrEcho($type=1, $value='false', $isRetrun=false)
    {
		if ($type==1){
			//处理bool类型
			if (IS_AJAX){
				if ($isRetrun){
					if ($value=="false") {
						$value = "";
					}
					return (bool)$value;
				}
				echo (string)$value;
				exit;
			} else {
				if ($value=="false") {
					$value = "";
				}
				return (bool)$value;
			}
		} elseif ($type==2){
			//处理json类型
			if (IS_AJAX){
				if ($isRetrun){
					return $value;
				}
				echo json_encode($value);
				exit;
			} else {
				return $value;
			}
		} else {
			//处理string类型
			if (IS_AJAX){
				//对强制返回的直接return
				if ($isRetrun){
					return $value;
				}
				//特殊处理字符串非 true或false的情况
				if (!in_array((string)$value, array('true', 'false'))){
					if (intval($value) > 0){
						$value = "true";
					} else {
						$value = "false";
					}
				}
				echo $value;
				exit;
			} else {
				return $value;
			}
		}
	}

	/**
	 +----------------------------------------------------------
	 * 功能：计算两个日期相差 年 月 日 时
	 +----------------------------------------------------------
	 * @param date   $startDate  #起始日期    时间戳
	 * @param date   $endDate    #截止日期日期 时间戳
	 +----------------------------------------------------------
	 * @return array(年，月，日，时,分)
	 +----------------------------------------------------------
	 */
	public static function getDateTime($startDate, $endDate)
	{
		if($startDate > $endDate)
		{
			$differ    =  $startDate;
			$startDate =  $endDate;
			$endDate   =  $differ;
		}

		$common  = $endDate-$startDate;                  //相差值
		$year    = floor($common/86400/360);               //整数年
		if (!empty($year)) {
			$common = $common - (86400*360)*$year;
		}
		$month   = floor($common/86400/30);			     //整数月
		if (!empty($month)) {
			$common = $common - (86400*30)*$month;
		}
		$day     = floor($common/86400);                   //总的天数
		if (!empty($day)) {
			$common = $common - 86400*$day;
		}
		$time    = floor($common/3600);                    //总的时
		if (!empty($day)) {
			$common = $common - 3600*$time;
		}
		$minute  = floor($common/60);                      //总的分钟

		return array('y'=>$year,'m'=>$month,'d'=>$day,'h'=>$time,'i'=>$minute);
	}

	/**
     * 随机生成主键id函数，用于需要上传图片的表，防止ID冲突
	 *
	 * @param string $table	#操作的表名，不含前缀
     *
     * @return array  #
    */
	public function randPrimaryKeyId($table) {
		$id = mt_rand(1,99999999999);
		if (M($table)->find($id)) {
			$this->randId($table);
		} else {
			return $id;
		}
	}
    /**
     * 无限级分类中获取一个分类下的所有分类的ID,包括查找的父ID
     *
     * @param  Array   $categoryArray 原始分类数据
     * @param  int     $id            分类父ID
     * @param  int     $id            查找深度,默认为5层载入所有子分类，当然可以控制查找几层，找不到数据就不会查找
     * @param  int     $level         父ID的分类级数，默认为空，自动从数据中查找
     * @return array                  父分类下的所有子分类的ID
     */
    public static function getAllSubCategoriesID( $categoryArray, $id, $depth = 5, $level = null ){
        //查找LEVEL
        if( ! $level ){
            foreach( $categoryArray as $v ){
                if( $id == $v['id'] ){
                    $level = $v['level'];
                }
            }
        }
        //没找到LEVEL？数据有问题
        if( ! $level ){
        	return false;
        }
        //开始查找
        $result = array( $id );
        $lookup = array( $id );
        for( $i = $level; $i < $depth ; $i ++ ){
            $r  =  self::getAllSubCategoriesIDFind( $categoryArray, $lookup, $i + 1 );
            if( $r ){
                //找到数据就合并
                $result = array_merge( $result, $r );
            }else{
                //没有数据退出
                return $result;
            }
        }
        return $result;
    }
    /**
     * 查找分类下某一深度的分类ID--属于上面的获取分类ID函数
     *
     * @param  Array  &$categoryArray 原始分类数据
     * @param  array  &$lookup        查找的ID数组
     * @param  int    $level          深度
     * @return array                  查找到的数组
     */
    public static function getAllSubCategoriesIDFind( &$categoryArray, &$lookup, $level ){
        $result = array();
        foreach( $categoryArray as $k => $v ){
            if( $level == $v['level']){
                if( in_array( $v['pid'], $lookup ) ){
                    $result[] = $v['id'];
                }
                //删除循环过的不在需要的数据，减少下次循环查询次数
                unset( $categoryArray[$k] );
            }
        }
        $lookup = $result;
        return $result;
    }
    /**
     * 截取中文字符串
     *
     * @param  string $str          要截取的中文字符串
     * @param  int $len             要截取的中文字符串长度
     * @param  int $startpos        起始位置
     * @param  string $after        截取后添加的内容，没有截取不添加
     * @param  string $encoding     字符串编码
     * @return string               截取后的字符串
     */
    public static function mbSubstr( $str, $len, $startpos = 0 , $after = '...', $encoding = 'utf-8' ){
        $newStr = mb_substr( $str, $startpos, $len, $encoding );
        if( $newStr !== $str ){
            return $newStr . $after;
        }else{
            return $newStr;
        }
    }
    /**
     * 计算地址代码等级
     *
     * @param  int $areaCode 6位地址代码
     * @return int           返回区域等级
     */
    public static  function getAreaCodeRank( $areaCode ){
        if( substr( $areaCode, -4 ) == '0000' ){
            return 'PROVINCE_AREA';
        }else if( substr( $areaCode, -2 ) == '00' ){
            return 'CITY_AREA';
        }else{
            return 'COUNTRY_AREA';
        }
    }
	/**
     * 获取二维码
     * @param   $data  二维码包含的文字内容
	 * @param   $path  保存二维码的位置，默认不保存false
	 * @param   $level 二维码编码纠错级别：L、M、Q、H
	 * @param   $size  二维码点的大小
     * @return int     返回二维码
     */
	public static function getQrCode($data,$path=false,$level='L',$size=4){
		Vendor('phpQrCode.phpqrcode');
		$QRcode = new \QRcode ();
		$fileName = $path;
		if($path){
            // 生成的文件名
            $fileName = $path.$size.'.png';
		}
		$QRcode->png($data, $fileName, $level, $size);
	}

	/**
	 *
	 * 参数数组转换为url参数
	 *
	 * @param array $urlData
	 */
	public function ToUrlParams($urlData)
	{
		$url = "";
		foreach ($urlData as $k => $v)
		{
			$url .= $k . "=" . $v . "&";
		}
		$url = trim($url, "&");
		return $url;
	}

    /**
     * 系统非常规MD5加密方法
     * @param  string $str 要加密的字符串
     * @return string
     */
    function think_ucenter_md5($str, $key = 'ThinkUCenter'){
        return '' === $str ? '' : md5(sha1($str) . $key);
    }

    /**
     * 系统加密方法
     * @param string $data 要加密的字符串
     * @param string $key  加密密钥
     * @param int $expire  过期时间 (单位:秒)
     * @return string
     */
    function think_ucenter_encrypt($data, $key, $expire = 0) {
        $key  = md5($key);
        $data = base64_encode($data);
        $x    = 0;
        $len  = strlen($data);
        $l    = strlen($key);
        $char =  '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x=0;
            $char  .= substr($key, $x, 1);
            $x++;
        }
        $str = sprintf('%010d', $expire ? $expire + time() : 0);
        for ($i = 0; $i < $len; $i++) {
            $str .= chr(ord(substr($data,$i,1)) + (ord(substr($char,$i,1)))%256);
        }
        return str_replace('=', '', base64_encode($str));
    }

    /**
     * 系统解密方法
     * @param string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
     * @param string $key  加密密钥
     * @return string
     */
    function think_ucenter_decrypt($data, $key){
        $key    = md5($key);
        $x      = 0;
        $data   = base64_decode($data);
        $expire = substr($data, 0, 10);
        $data   = substr($data, 10);
        if($expire > 0 && $expire < time()) {
            return '';
        }
        $len  = strlen($data);
        $l    = strlen($key);
        $char = $str = '';
        for ($i = 0; $i < $len; $i++) {
            if ($x == $l) $x = 0;
            $char  .= substr($key, $x, 1);
            $x++;
        }
        for ($i = 0; $i < $len; $i++) {
            if (ord(substr($data, $i, 1)) < ord(substr($char, $i, 1))) {
                $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
            }else{
                $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
            }
        }
        return base64_decode($str);
    }

    static public function findChild(&$data, $pid = 0, $col_pid = 'parent') {
        $rootList = array();
        foreach ($data as $key => $val) {
            if ($val[$col_pid] == $pid) {
                $rootList[]   = $val;
                unset($data[$key]);
            }
        }
        return $rootList;
    }
    /**
     * 无限分级
     * @access  public
     * @param   array     &$data      数据库里取得的结果集 地址引用
     * @param   integer   $pid        父级id的值
     * @param   string    $col_id     自增id字段名（对应&$data里的字段名）
     * @param   string    $col_pid    父级字段名（对应&$data里的字段名）
     * @param   string    $col_cid    是否存在子级字段名（对应&$data里的字段名）
     * @return  array     $childs     返回整理好的数组
     */
    static public function getTree(&$data, $pid = 0, $col_id = 'id', $col_pid = 'parent', $col_cid = 'haschild') {
        $childs = self::findChild($data, $pid, $col_pid);
        if (empty($childs)) {
            return null;
        }
        foreach ($childs as $key => $val) {
            if ($val[$col_cid]) {
                $treeList = self::getTree($data, $val[$col_id], $col_id, $col_pid, $col_cid);
                if ($treeList !== null) {
                    $childs[$key]['childs'] = $treeList;
                }
            }
        }
        return $childs;
    }
    
    /**
     * 公用通过路径获取文件夹下的指定文件路径
     *
     * @param string    $path       相对于/Uploads/的路径
     * @param int       $type       返回类型，1为文件列表，2为单个文件全路径，3仅返回单个文件名，默认为2
     * @param array     $suffixArr  需要读取的文件后缀（二维数组），默认读取图片
     * @param string    $filterStr  需要过滤掉文件名含有某字符串的文件，默认不需要
     *
     * @return array
     */
    public static function getFileList($path, $type=2, $suffixArr=array('gif','png','jpg','swf','GIF','PNG','JPG','SWF'), $filterStr=NULL)
    {
        if (empty($path)){
            return false;
        }
        if (empty($suffixArr)) {
            $suffixArr=array('gif','png','jpg','swf','GIF','PNG','JPG','SWF');
        }
        if (empty($type)) {
            $type = 2;
        }
        $imgArr = self::showFileListOrder($path, $suffixArr);
        if (empty($imgArr)){
            //没有文件
            return false;
        }
        //排序
        sort($imgArr,SORT_NUMERIC);
        if (!empty($filterStr)) {
            //需要过滤
            //造一个最末键值对，否则无法验证最后一个
            $index = count($imgArr)+1;
            $imgArr[$index] = 0;
            for($i=0;$i<count($imgArr);$i++){
                if (strpos($imgArr[$i], $filterStr) !== false){
                    //存在过滤掉
                    unset($imgArr[$i]);
                }
            }
            //销毁拟造键值对
            unset($imgArr[$index]);
            //重装游标
            $imgArr = array_values($imgArr);
        }
        if ($type==1) {
            $imgArr2 = array();
            foreach ($imgArr as $key => $value) {
                $imgArr2[] = "/Uploads{$path}".$value;
            }
            return $imgArr2;
        } elseif($type==2) {
            //组装单个路径返回
            return "/Uploads{$path}".$imgArr[0];
        } else {
            //仅返回单个文件名
            return $imgArr;
        }
    }
    /**
     * 根据广告位置获取对应广告数据
     *
     * @param  int  $class_id   广告位置id
     * @param  int  $limit      读取数量
     * @param  $imgType string  720P  1080P  2K
     * @return array            #
     */
    public function getAdList( $class_id, $limit='5',$platform='1', $imgType='')
    {
        if( empty($class_id) || empty($limit) || !is_numeric($class_id) || !is_numeric($limit)){
            return false;
        }

        $where = [
            'class_id'    => $class_id,
            'status'      => 1,
            'platform'    => in_array($platform, [2,3]) ? ['in',[$platform,5,6]] : ['in',[$platform,6]], 
            'online_time' => array("elt",time()),
            'end_time'    => array("egt",time())
        ];

        $adList = M("AdverList")->where($where)->field(['id','title','module','url','img','app_isbrowser','remark','sort'])->order("sort asc")->limit($limit)->select();

        if(!$adList){
            if(in_array($class_id, [108,77,75,73,71,69,64,63,62,57,45])){
                //默认横幅广告
                $adList = M("AdverList")->where(['class_id'=>114])->field(['id','title','module','url','img','app_isbrowser','remark'])->order("sort asc")->limit(1)->select();
            }
            if(in_array($class_id, [109,107,106,104,103,102,94,90,88,86,85,82,80,78,76,74,72,70,68,56,55,54,48,47,44,28,22,13,1])){
                //默认图片轮播
                $adList = M("AdverList")->where(['class_id'=>115])->field(['id','title','module','url','img','app_isbrowser','remark'])->order("sort asc")->limit(1)->select();
            }
        }

        if (empty($adList)) {
            return false;
        }

        foreach( $adList as $k => $v )
        {
            $img = $v['img'];
            //安卓启动页图片获取处理
            if($platform == 3 && $imgType != ''){
                switch ($imgType) {
                    case '720P' :$img = str_replace('1440P','720',$img);break;
                    case '768P' :$img = str_replace('1440P','768',$img);break;
                    case '1080P':$img = str_replace('1440P','1080',$img);break;
                }
            }
            //获取广告图片
            $adList[$k]['img'] = Tool::imagesReplace( $img );
            $adList[$k]['value'] = $v['url'];

            //web与M站平台解析地址
            if (in_array($platform, [1,4]))
            {
                if($platform == 1) $adList[$k]['url'] = U('/adver/'.$v['id'].'@www');
                if($platform == 4) $adList[$k]['url'] = U('/adver/'.$v['id'].'@m');
            }
        }

        return $adList;
    }

    /**
     * 根据标识获取对应推荐位图片
     *
     * @param  int  $sign       推荐位标识
     * @param  int  $limit      读取数量
     * @return array            #
     */
    public function getRecommend( $sign, $limit='5', $platform='1' ){
        if( empty($sign) || empty($limit) ){
            return false;
        }
        $class_id = M('recommendClass')->where(['sign'=>$sign])->getField('id');
        $where              = array();
        $where['class_id']  = $class_id;
        $where['status']    = 1;
        $Recommend          = M("Recommend")->where($where)->field(['id','class_id','title','remark','type','url','img'])->order("sort asc")->limit($limit)->select();
        if (empty($Recommend)) {
            return false;
        }
        foreach( $Recommend as $k => $v ){
            //获取推荐位图片
            $Recommend[$k]['img'] = Tool::imagesReplace( $v['img'] );
            //平台解析地址
            switch ($v['type'])
            {
                case 1:  
                    //资讯
                    $classArr = getPublishClass(0);
                    $news = M('PublishList')->field('id,add_time,class_id')->where(['id'=>$v['url']])->find();
                    $url  = $platform == 1 
                        ? newsUrl($news['id'],$news['add_time'],$news['class_id'],$classArr) 
                        : mNewsUrl($news['id'],$news['class_id'],$classArr);

                break;
                case 2: 
                    //图库
                    if($platform == 1){
                        $classArr = getGalleryClass(0);
                        $gallery = M('Gallery')->field('id,add_time,class_id')->where(['id'=>$v['url']])->find();
                        $url = galleryUrl($gallery['id'],$classArr[$gallery['class_id']]['path'],$gallery['add_time']);
                    }else{
                        $url  = U('/photo/'.$v['url'].'@m');
                    }
                break;
                case 9:  
                    $url = $v['url']; break; //外链
                default:
                    $Recommend[$k]['url'] = 'javascript:void(0);'; break; //空链接
                    break;
            }
            
            $Recommend[$k]['url'] = $url;
        }
        return $Recommend;
    }

    //验证手机号码
    public static function checkMobile($mobile)
    {
        return preg_match("/1[3456789]{1}\d{9}$/",$mobile);
    }

    //验证密码长度
    public function checkPassword($pwd)
    {
        return preg_match("/^[0-9A-Za-z]{6,15}$/",$pwd);
    }

    //验证邮箱格式
    public function checkEmail($email){
        $pattern = '/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i';
        return preg_match($pattern,$email);
    }

    /**
     * 获取字符的长度
     * @param  str $string
     * @return int
     */
    function utf8_strlen($string=null)
    {
        preg_match_all("/./us", $string, $match);
        return count($match[0]);
    }

    ####################################################################################
    #php 验证身份证有效性,根据国家标准GB 11643-1999 15位和18位通用
    //验证身份证是否有效
    public static function validateIDCard($IDCard) {
        if (strlen($IDCard) == 18) {
            return self::check18IDCard($IDCard);
        } elseif ((strlen($IDCard) == 15)) {
            $IDCard = self::convertIDCard15to18($IDCard);
            return self::check18IDCard($IDCard);
        } else {
            return false;
        }
    }

    //计算身份证的最后一位验证码,根据国家标准GB 11643-1999
    public function calcIDCardCode($IDCardBody) {
        if (strlen($IDCardBody) != 17) {
            return false;
        }

        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $code = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;

        for ($i = 0; $i < strlen($IDCardBody); $i++) {
            $checksum += substr($IDCardBody, $i, 1) * $factor[$i];
        }

        return $code[$checksum % 11];
    }

    // 将15位身份证升级到18位
    public function convertIDCard15to18($IDCard) {
        if (strlen($IDCard) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($IDCard, 12, 3), array('996', '997', '998', '999')) !== false) {
                $IDCard = substr($IDCard, 0, 6) . '18' . substr($IDCard, 6, 9);
            } else {
                $IDCard = substr($IDCard, 0, 6) . '19' . substr($IDCard, 6, 9);
            }
        }
        $IDCard = $IDCard . self::calcIDCardCode($IDCard);
        return $IDCard;
    }

    // 18位身份证校验码有效性检查
    public function check18IDCard($IDCard) {
        if (strlen($IDCard) != 18) {
            return false;
        }

        $IDCardBody = substr($IDCard, 0, 17); //身份证主体
        $IDCardCode = strtoupper(substr($IDCard, 17, 1)); //身份证最后一位的验证码

        if (self::calcIDCardCode($IDCardBody) != $IDCardCode) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * GrabImage  通过url把图片下载本地
     * @param   string  $url   图片Url地址
     * @param   string  $name  图片存储文件名
     * @param   boolean $download 是否下载
     * @return  string  $filename
     */
    function GrabImage($url, $name="", $download=false)
    {
        if($url==""){
            return false;
        }
        if($name==""){
            $TempStr = strrchr($url,"/");
            $TempStr = trim($TempStr,"//");
            $filename = $TempStr;
        }else{
            $filename = $name;
        }

        ob_start();
        readfile($url);
        $img = ob_get_contents();
        ob_end_clean();

        if($download){
            $size = strlen($img);
            file_put_contents($filename, $img);
            return $filename;
        }else{
            return $img;
        }

        /* $fp2=@fopen($filename, "a");
        fwrite($fp2,$img);
        fclose($fp2); */

    }
    ####################################################################################
}
?>