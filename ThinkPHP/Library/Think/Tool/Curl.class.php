<?php
/**
 * 用于curl上传的相关方法 只支持单个文件上传 多个请分批次处理
 * 
 * @author Ansion <1572309495@qq.com>
 * @since  2015-6-29
 */
/**
 * 用于curl上传的相关方法 只支持单个文件上传 多个请分批次处理
 * 
 * @author Ansion <1572309495@qq.com>
 */
namespace Think\Tool;

class Curl
{
    // 是否自动检查附件 对网站前台开启 后台关闭
    public $autoCheck = true;
    
    //上传文件的最大值
    public $maxSize = -1;
    
    //允许上传的文件后缀
    //留空不作后缀检查
    public $allowExts = array();
    
    //允许上传的文件类型
    //留空不做检查
    public $allowTypes = array();
    
    //上传文件保存路径 以Uploads为相对路径 目录以英文分割符,隔开
    public $savePath = '';
    
    //需要传输的文件(完整路径)
    public $transferFile = ''; //指定传输文件时 不需要formFileName
    
    public $formFileName = ''; //指定fromFileName 不需要$transferFile
    
    // 错误信息
    private $error = '';
    
    //是否自动创建以年月命名的二级文件夹
    public $isAutoCreateYMDir = false;
    
    // 上传文件命名规则
    // 例如可以是 time uniqid com_create_guid 等
    // 必须是一个无需任何参数的函数名 可以使用自定义函数
    public $saveRule = '';
    
    //图片裁剪信息(precent,p_x,p_y,p_w,p_h)
    public $cutInfo = array();
    //自定义文件名(不含扩展名)
    public $customName;
    //自定义扩展名
    public $customExt;
    //需要删除文件的数组列表
    public $unlinks = array();
    
    // 使用对上传图片进行缩略图处理
    public $thumb   =  false;
    // 缩略图最大宽度
    public $thumbMaxWidth;
    // 缩略图最大高度
    public $thumbMaxHeight;
	
	// 需要裁剪的缩略图json字符串
    public $thumbJson = '';

    // 是否添加水印
    public $water = false;
    
    //图片服务器host
    public $serverHost;

    //客户端与服务器通信的安全密钥
    public $accessAuthKey;
    
    //调试模式
    public $debug = false;//默认是关闭调试模式
    public $debugSendClientData = array(); //发送请求的数据
    public $debugAcceptServerData = array(); //接受服务器返回的数据
    
    
    /**
    * Short description：构造函数，定义curl连接信息
    *
    * @param string  $maxSize           #单个文件的最大值
    * @param array   $allowExts         #允许的文件后缀
    * @param array   $allowTypes        #允许的文件mime类型
    * @param string  $savePath          #文件的保存路径
    * @param string  $saveRule          #保存文件名的命名规则
    * @param string  $transferFile      #上传本地服务器图片 另外一种传输方式
    * @param string  $formFileName      #html中的上传文件的name值
    * @param boolean $isAutoCreateYMDir #是否自动创建以年月命名的二级文件夹
    */
    public function __construct($maxSize = '', $allowExts = '', $allowTypes = '', $savePath = '', $saveRule = '', $transferFile = '', $formFileName = '', $isAutoCreateYMDir = '')
    {
        //图片服务器host和port
        $this->serverHost = C('IMG_SERVER');
        //客户端与服务器通信的安全密钥
        $this->accessAuthKey = '95f24f4c82da92e69cccc16a71068b45';
        
        //侦测系统运行环境
        $this->_DetectSystemEnv();
        
        //单个文件最大值
        if (!empty($maxSize) && is_numeric($maxSize)) {
            $this->maxSize = $maxSize;
        }
        
        //允许的文件扩展
        if (!empty($allowExts)) {
            if (is_array($allowExts)) {
                $this->allowExts = array_map('strtolower', $allowExts);
            } else {
                $this->allowExts = explode(',', strtolower($allowExts));
            }
        }
        
        //检查文件Mime类型
        if (!empty($allowTypes)) {
            if (is_array($allowTypes)) {
                $this->allowTypes = array_map('strtolower', $allowTypes);
            } else {
                $this->allowTypes = explode(',', strtolower($allowTypes));
            }
        }
        
        if (!empty($saveRule)) {
            $this->saveRule = $saveRule;
        }
        //文件保存路径
        $this->savePath = $savePath;
        
        //需要上传的文件
        $this->transferFile = $transferFile;
        
        //表单文件
        $this->formFileName = $formFileName;
        
        //是否自动传建以年月命名的二级文件夹
        $this->isAutoCreateYMDir = $isAutoCreateYMDir;
        
    }
    
    /**
     * Short description：侦测系统运行环境
     *
     * @return #
     */
    private function _DetectSystemEnv()
    {
        //检测图片服务器的host
        if (empty($this->serverHost)) {
            exit("please set imageserver host and port");
        }
        
        $extArr   = get_loaded_extensions();
        //curlFlag扩展标识
        $curlFlag = false;
        foreach ($extArr as $value) {
            $value = strtolower(trim($value));
            if ($value == 'curl') {
                $curlFlag = true;
                break;
            }
        }
        if ($curlFlag == false) {
            exit('please install php-curl extensions');
        }
    }
    
    /**
     * 上传文件
     *
     * @param str $method 方法名
     *
     * @return array
     */
    public function upload($method='index')
    {
        //为裁剪时,取消检测传输文件
        if (!$this->cutInfo['source']) {
            //检测传输文件类型
            $this->_checkTransferType();
        }
        //检查上传路径
        $this->_checkSavePath();
        
        //自动检查附件
        if ($this->autoCheck) {
            if (!$this->check()) {
                $data = array(
                    'status' => '0',
                    'info' => $this->error,
                    'data' => ''
                );
                return $data;
            }
        }
        
        //组装需要远程调用的数据
        $sendData    = $this->getSendData();
        //发送数据 并获取服务器的响应结果
        $receiveData = $this->_getReceiveData($sendData, $method);
        return $receiveData;
    }
    
    /**
     * 发送数据 并获取服务器的响应结果
     *
     * @param array  $sendData #客户端发送给服务端的数据
     * @param string $method   #图片服务器中的方法名
     *
     * @return array
     */
    private function _getReceiveData($sendData = array(), $method='index')
    {
        //客户端与服务器通信的安全密钥
        $sendData['accessAuthKey'] 	= $this->accessAuthKey;
		
		//裁剪的json字符串
		$sendData['thumbJson'] 		= $this->thumbJson;
        $sendData['water']          = $this->water;
		
        $method = trim($method);
        $ch             = curl_init();
        $tmp_user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
		$url = $this->serverHost . '/?m=Home&c=FileService&a='.$method;
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, $tmp_user_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $sendData);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:'));
        $data = curl_exec($ch);
        //判断是否开启调试模式
        if ($this->debug == true) {
			if(curl_errno($ch)){
				//error
				dump(curl_error($ch));
			}
			dump($data);
			dump($sendData);
			$reslut = curl_getinfo($ch);
			dump($reslut);
            $this->debugSendClientData = $sendData;
            $this->debugAcceptServerData = $data;
        }
		curl_close($ch);
		$info = array();
        if($data){
            $info = json_decode($data, true);
        }
        return $info;
    }
    
    /**
    * 打印调试数据
    *
    * @return #
    */
    public function debug()
    {
        if ($this->debug !=true) {
            echo "<p>please set member debug true, call debug function</p>";
            exit;
        }
        echo "<p style='color:red;'>客户端发送给服务器端的数据如下：</p>";
        echo "<pre>";
        print_r($this->debugSendClientData);
        echo "</pre>";
        echo "<p style='color:blue;'>客户端接受服务器端的数据如下：</p>";
        echo "<pre>";
        print_r($this->debugAcceptServerData);
        echo "</pre>";
    }
    
    /**
     * 解码json数据
     *
     * @param string $json #json
     *
     * @return array
     */
    public function jsondecode($json)
    {
        $array = array();
        if (empty($json) || !($array = json_decode($json, true)) ) {
            $array['error'] = 0;
            $array['data']  = 0;
            $array['info']  = 'Server internal error!';
            return $array;
        }
        //$array['info'] = iconv('utf-8', 'gbk', $array['info']);
        return $array;
    }
    
    /**
     * 检测传输文件类型的合法性
     *
     * @return string
     */
    protected function _checkTransferType()
    {
        if (!empty($this->transferFile) && !empty($this->formFileName)) {
            exit("please set member field either transferFile or formfileName. only one");
        }
        if (empty($this->transferFile) && empty($this->formFileName)) {
            exit("please set member field transferFile or formfileName. only one");
        }
    }
    
    /**
    * 检查文件保存路径
    *
    * @return boolean
    */
    private function _checkSavePath()
    {
        if (empty($this->savePath)) {
            exit("please set file upload dir");
        }
    }
    
    /**
     * 检查上传的文件
     *
     * @return boolean
     */
    protected function check()
    {
        /**********************本地服务器的上传图片检测开始****************************/
        if (!empty($this->transferFile)) {
            //检测本地服务器图片是否存在
            if (!is_file($this->transferFile) || !file_exists($this->transferFile)) {
                $this->error = '上传的图片不存在';
                return false;
            }
            //检测文件大小
            $fileSize = filesize($this->transferFile);
            if (!$this->checkSize($fileSize)) {
                $this->error = '上传文件大小不符！';
                return false;
            }
            //检查文件类型
            $fileExt = pathinfo($this->transferFile, PATHINFO_EXTENSION);
            if (!$this->checkExt($fileExt)) {
                $this->error = '上传文件类型不允许';
                return false;
            }
            return true;
        }
        /**********************本地服务器的上传图片检测结尾****************************/
        
        /**********************用户浏览器上传图片检测开始****************************/
        if (empty($this->formFileName)) {
            exit('please set member field formFileName');
        }
        if ($_FILES[$this->formFileName]['error']) {
            //文件上传失败
            //捕获错误代码
            $this->error($_FILES[$this->formFileName]['error']);
            return false;
        }
        //文件上传成功，进行自定义规则检查
        //检查文件大小
        if (!$this->checkSize($_FILES[$this->formFileName]['size'])) {
            $this->error = '上传文件大小不符！';
            return false;
        }
        
        //检查文件Mime类型
        if (!$this->checkType($_FILES[$this->formFileName]['type'])) {
            $this->error = '上传文件MIME类型不允许！';
            return false;
        }
        //检查文件类型
        if (!$this->checkExt($this->getExt($_FILES[$this->formFileName]['name']))) {
            $this->error = '上传文件类型不允许';
            return false;
        }
        /**********************用户浏览器上传图片检测结尾****************************/
        
        return true;
    }
    
    /**
    * 取得上传文件的后缀
    *
    * @param string $filename 文件名
    *
    * @return boolean
    */
    private function getExt($filename)
    {
        //自定义扩展名时,启用自定义扩展名
        if (!empty($this->customExt)) {
            return $this->customExt;
        }
        $pathinfo = pathinfo($filename);
        return strtolower($pathinfo['extension']);
    }
    
    /**
     * 获取错误代码信息
     *
     * @param string $errorNo #错误号码
     *
     * @return void
     */
    protected function error($errorNo)
    {
        switch ($errorNo) {
        case 1:
            $this->error = '上传的文件超过了 php.ini 中 upload_max_filesize 选项限制的值';
            break;
        case 2:
            $this->error = '上传文件的大小超过了 HTML 表单中 MAX_FILE_SIZE 选项指定的值';
            break;
        case 3:
            $this->error = '文件只有部分被上传';
            break;
        case 4:
            $this->error = '没有文件被上传';
            break;
        case 6:
            $this->error = '找不到临时文件夹';
            break;
        case 7:
            $this->error = '文件写入失败';
            break;
        default:
            $this->error = '未知上传错误！';
        }
        return;
    }
    
    /**
    * 检查文件大小是否合法
    *
    * @param integer $size 数据
    *
    * @return boolean
    */
    private function checkSize($size)
    {
        return !($size > $this->maxSize) || (-1 == $this->maxSize);
    }
    
    /**
    * 检查上传的文件类型是否合法
    *
    * @param string $type 数据
    *
    * @return boolean
    *
    */
    private function checkType($type)
    {
        if (!empty($this->allowTypes)) {
            return in_array(strtolower($type), $this->allowTypes);
        }
        return true;
    }
    
    /**
    * 检查上传的文件后缀是否合法
    *
    * @param string $ext 后缀名
    *
    * @return boolean
    */
    private function checkExt($ext)
    {
        if (!empty($this->allowExts)) {
            return in_array(strtolower($ext), $this->allowExts, true);
        }
        return true;
    }
    
    
    /**
     * 获取要发送的数据
     *
     * @return array
     */
    protected function getSendData()
    {
        //传输方式一 字段成员变量$transferFile存在时
        $sendData             = array();
        //传输方式一与传输方式二共用的字段
        $sendData['filePath'] = $this->savePath;
        //判断是否是裁剪图片
        if ($this->cutInfo['source']) {
            //裁取位置信息array(precent,p_x,p_y,p_w,p_h);
            $sendData['posInfo']   = json_encode($this->cutInfo['posInfo']);
            //扩展名兼容server生成文件名
            $sendData['fileExt']   = $this->cutInfo['customExt'] ? $this->cutInfo['customExt'] : 'jpg';
            //保存文件名
            $sendData['saveName']  = $this->cutInfo['customName'];
            //原图片文件名,直接拿远程服务器图片,oldpath配合使用            
            $sendData['source']    = $this->cutInfo['source'];
            //裁取尺寸(width,height)
            $sendData['thumbSize'] = json_encode($this->cutInfo['thumbSize']);
            //源图片路径            
            $sendData['oldpath']   = $this->cutInfo['oldpath'];
        } else {
            if (!empty($this->transferFile)) {
                //兼容php 5.6
                if(class_exists('\CURLFile')){
                    $sendData['transferFile'] = new \CURLFile(realpath($this->transferFile));
                }else{
                    $sendData['transferFile'] = '@' . $this->transferFile;
                }
                $fileExt                  = $this->getExt($this->transferFile);
                if (in_array($fileExt, array('jpg', 'jpeg', 'png', 'gif'))) {
                    $sendData['fileExt']  = $fileExt;
                } else {
                    $sendData['fileExt'] = 'jpg';
                }
            } else {
                //兼容php 5.6
                if(class_exists('\CURLFile')){
                    $sendData['transferFile'] = new \CURLFile(realpath($_FILES[$this->formFileName]['tmp_name']));
                }else{
                    $sendData['transferFile'] = '@' . $_FILES[$this->formFileName]['tmp_name'];
                }
                $sendData['fileExt']      = $this->getExt($_FILES[$this->formFileName]['name']);
            }
            $sendData['isAutoCreateYMDir'] = $this->isAutoCreateYMDir;
            //指定的文件名
            $sendData['saveName']          = $this->getSaveName();
			//用于判断如果是指定文件名则不需要加随机数
            if (!empty($this->customName)) {
				$sendData['customName'] = 1;
			} else {
				$sendData['customName'] = 0;
			}
            //是否生成缩略图
            if ($this->thumb == true) {
                $sendData['thumbMaxWidth'] = $this->thumbMaxWidth;
                $sendData['thumbMaxHeight'] = $this->thumbMaxHeight;
            }
        }
        return $sendData;
    }
    
    
    /**
    * 根据上传文件命名规则取得保存文件名
    *
    * @return string
    */
    private function getSaveName()
    {
        //自定义名文件名时启用自定义文件名
        if (!empty($this->customName)) {
            return $this->customName;
        }
        $rule = $this->saveRule;
        if (empty($rule)) { //没有定义命名规则，则以YmdHis的命令规则
            $saveName = date("YmdHis");
        } else {
            if (function_exists($rule)) {
                //使用函数生成一个唯一文件标识号
                $saveName = $rule();
            } else {
                //使用给定的文件名作为标识号 指定的文件名含有中文的 就以YmdHis的命名规则
                if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $rule)) {
                    $saveName = "zh" . date("YmdHis");
                } else {
                    $rule = strtolower($rule);
                    $pos  = strrpos($rule, '.');
                    if ($pos >= 1) {
                        $rule = mb_substr($rule, 0, $pos);
                    }
                    $saveName = $rule;
                }
            }
        }
        return $saveName;
    }
    
    /**
    * 删除文件
    *
    * @return array
    */
    public function unlink()
    {
        if (empty($this->unlinks)) {
            $data = array(
                'status' => '0',
                'info' => '请指定要删除的文件列表',
                'data' => ''
            );
            return $data;
        }
        $sendData = $this->getUnLinksData();
        $receiveData = $this->_getReceiveData($sendData, 'unlinks');
        return $receiveData;
    }
    
    /**
    * 获取需要删除数据的关联数组
    *
    * @return 数组
    */
    public function getUnLinksData()
    {
        $unlinks = $this->unlinks;
        foreach ($unlinks as $key=>$value) {
            //对于删除文件的版本号的处理
            $value = trim($value);
            $pos = strpos($value, '?v');
            if ($pos >1) {
                $value = mb_substr($value, 0, $pos, 'gbk');
            }
            $value = trim($value);  
            $unlinks["unlinks".($key+1)] = trim($value);
            //去掉相应的索引数组
            unset($unlinks[$key]);
        }
        return $unlinks;
    }
    
    /**
     * 获取图片文件大小
     *
     * @param str $file 文件路径
     *
     * @return arr
     */
    public function getImgSize($file)
    {
        if (empty($file)) {
            $data = array(
                'status' => '0',
                'info' => '文件为空',
                'data' => ''
            );
            return $data['data'];
        }
        $sendData = array('imgpath'=>$file);
        $receiveData = $this->_getReceiveData($sendData, 'getimgsize');
        return $receiveData['data'];
    }
}
?>