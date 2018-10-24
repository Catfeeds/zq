<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 */
include_once 'Util/Autoloader.php';

$demo = new Demo();
$demo->doGet();

class Demo
{
	private static $appKey = "23643994";
    private static $appSecret = "a5e90b94abe5e85c3cfe6308b06f1c1f";
    private static $host = "http://ali-weather.showapi.com";

	/**
	*method=GET请求示例
	*/
    public function doGet() {
		//域名后、query前的部分
		$path = "/hour24";
		$request = new HttpRequest($this::$host, $path, HttpMethod::GET, $this::$appKey, $this::$appSecret);

        //设定Content-Type，根据服务器端接受的值来设置
		$request->setHeader(HttpHeader::HTTP_HEADER_CONTENT_TYPE, ContentType::CONTENT_TYPE_TEXT);
		
        //设定Accept，根据服务器端接受的值来设置
		$request->setHeader(HttpHeader::HTTP_HEADER_ACCEPT, ContentType::CONTENT_TYPE_TEXT);

        //注意：业务query部分，如果没有则无此行；请不要、不要、不要做UrlEncode处理
		$area = $this->getCity();
		$request->setQuery("area", $area);

		$response = HttpClient::execute($request);
		
		print_r(json_decode($response->getBody(), true));
	}

	public function getCity(){
	    $ip = $this->real_ip();
	    $city_info = $this->get_city($ip);
	    
	    if(is_array($city_info) && array_key_exists('city', $city_info)){
	        $city = $city_info['city'];
	        $city = rtrim($city, '市');
	        return $city;
	    }else{
	        return '';
	    }
	}
	
	
	public function real_ip()
	{
	    static $realip = NULL;
	
	    if ($realip !== NULL)
	    {
	        return $realip;
	    }
	
	    if (isset($_SERVER))
	    {
	        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']))
	        {
	            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
	
	            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
	            foreach ($arr AS $ip)
	            {
	                $ip = trim($ip);
	
	                if ($ip != 'unknown')
	                {
	                    $realip = $ip;
	
	                    break;
	                }
	            }
	        }
	        elseif (isset($_SERVER['HTTP_CLIENT_IP']))
	        {
	            $realip = $_SERVER['HTTP_CLIENT_IP'];
	        }
	        else
	        {
	            if (isset($_SERVER['REMOTE_ADDR']))
	            {
	                $realip = $_SERVER['REMOTE_ADDR'];
	            }
	            else
	            {
	                $realip = '0.0.0.0';
	            }
	        }
	    }
	    else
	    {
	        if (getenv('HTTP_X_FORWARDED_FOR'))
	        {
	            $realip = getenv('HTTP_X_FORWARDED_FOR');
	        }
	        elseif (getenv('HTTP_CLIENT_IP'))
	        {
	            $realip = getenv('HTTP_CLIENT_IP');
	        }
	        else
	        {
	            $realip = getenv('REMOTE_ADDR');
	        }
	    }
	
	    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
	    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
	
	    return $realip;
	}
	
	
	public function get_city($ip)
	{
	    $url="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
	
	    $ip=json_decode($this->curl_file_get_contents($url));
	
	    if(!is_object($ip)){
	        return false;
	    }
	
	    if((string)$ip->code=='1'){
	        return false;
	    }
	    $data = (array)$ip->data;
	
	    return $data;
	}
	
	public function curl_file_get_contents($url,$data=array()){
	    //对空格进行转义
	    $url = str_replace(' ','+',$url);
	    $ch = curl_init();
	    //设置选项，包括URL
	    curl_setopt($ch, CURLOPT_URL, "$url");
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_HEADER, 0);
	    curl_setopt($ch,CURLOPT_TIMEOUT,3);  //定义超时3秒钟
	    // POST数据
	    curl_setopt($ch, CURLOPT_POST, 1);
	    // 把post的变量加上
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	
	    //执行并获取url地址的内容
	    $output = curl_exec($ch);
	    $errorCode = curl_errno($ch);
	    //释放curl句柄
	    curl_close($ch);
	    if(0 !== $errorCode) {
	        return false;
	    }
	    return $output;
	}
}