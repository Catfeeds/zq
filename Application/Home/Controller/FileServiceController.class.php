<?php
/**
 * Curl统一上传文件处理程序
 * 
 * @author Ansion <406516482@qq.com>
 * @since  2015-5-29
 */
/**
 * Curl统一上传文件处理程序
 * 
 * @author Ansion <406516482@qq.com>
 */
//引用图片类
use Think\Controller;
use Think\Image;
class FileServiceController extends Controller
{
    public $baseDir = null;
    //用户指定的目录
    public $userDir = null;
    //系统自动创建的目录
    public $autoDir = null;
    public $tempDir;
    /**
     * Short description：构造函数
     *
     */
    public function _initialize()
    {
        //初始化函数
        $this->_init();
        $this->baseDir = './Uploads/';
        $this->tempDir = './Uploads/';
    }

    /**
    * Short description：初始化函数
    *
    * @return #
    */
    private function _init() 
    {
        //客户端与服务器通信的安全密钥
        $accessAuthKey = trim($_POST['accessAuthKey']);
        if ($accessAuthKey != '95f24f4c82da92e69cccc16a71068b45') {
            $data = array(
                'status' => '0',
                'info' => '客户端与服务器通信的安全密钥校对失败',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
    }
    /**
     * Short   description：自动创建文件夹
     *
     * @param string  $imgDir  #存放图片的路径 多级目录以逗号隔开
     * @param boolean $autoSub #是否自动创建以年和月命名的二级子文件夹
     *
     * @return array  #如果上传成功返回相对目录路径 否则返回false
     */
    public function createDir($imgDir = '', $autoSub = false)
    {
        //分割路径
        $imgDir    = trim($imgDir, ",");
        $imgDirArr = explode(",", $imgDir);
        $tmpDir    = '';
        foreach ($imgDirArr as $k => $v) {
            $tmpDir .= trim($v) . '/';
            if (!file_exists($this->baseDir . $tmpDir)) {
                if (!@mkdir($this->baseDir . $tmpDir)) {
                    //文件夹创建失败，请检查相应文件夹是否有足够权限
                    return false;
                }
                @chmod($this->baseDir . $tmpDir, 0767);
            }
        }
        
        //用户自定义的文件夹
        $this->userDir = $tmpDir;
        
        //是否自动创建以年月命名的目录
        if (!empty($autoSub)) {
            $Y = date("Y");
            $m = date("m");
            //年
            if (!file_exists($this->baseDir . $tmpDir . $Y . "/")) {
                if (!@mkdir($this->baseDir . $tmpDir . $Y . "/")) {
                    //文件夹创建失败，请检查相应文件夹是否有足够权限
                    return false;
                }
                @chmod($this->baseDir . $tmpDir . $Y . "/", 0767);
            }
            //月
            if (!file_exists($this->baseDir . $tmpDir . $Y . "/" . $m . "/")) {
                if (!@mkdir($this->baseDir . $tmpDir . $Y . "/" . $m . "/")) {
                    //文件夹创建失败，请检查相应文件夹是否有足够权限
                    return false;
                }
                @chmod($this->baseDir . $tmpDir . $Y . "/" . $m . "/", 0767);
            }
            //系统自动创建的文件夹
            $this->autoDir = $Y . "/" . $m . "/";
            return $tmpDir . $Y . "/" . $m . "/";
        } else {
            return $tmpDir;
        }
    }
    
    /**
    * Short description：上传文件
    *
    * @return string
    */
    public function index()
    {
        if (empty($_FILES['transferFile'])) {
            $data = array(
                'status' => '0',
                'info' => '没有文件上传',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
        $childDir = $this->createDir($_POST['filePath'], $_POST['isAutoCreateYMDir']);
        if (!$childDir) {
            $data = array(
                'status' => '0',
                'info' => 'Folder creation failed, please check the appropriate folder if there is sufficient authority',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
		//是否为自定义文件名，如果是则不加随机数
        if (!empty($_POST['customName'])){
			$tmpFilename = $_POST['saveName'] . "." . $_POST['fileExt'];
		} else {
			$tmpFilename = $_POST['saveName'] . mt_rand(1, 1000) . "." . $_POST['fileExt'];
		}
        $imgPath = $this->tempDir . $childDir . $tmpFilename;
        if (!move_uploaded_file($_FILES['transferFile']['tmp_name'], $imgPath)) {
            $data = array(
                'status' => '0',
                'info' => 'File move failed, please check the appropriate folder is sufficient authority',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
        
        $imgArr            = array();
        $imgArr['imgDir']  = ltrim($imgPath,'.');
        $imgArr['imgName'] = $this->autoDir . $tmpFilename;
        $info = "文件上传成功";
        
		/**************************判断是否需要生成缩略图-开始-**********************/
		$thumbJson 	= trim($_POST['thumbJson']);
		$thumbArray = json_decode($thumbJson, true);

        if (!empty($thumbArray)) {
			//初始化图片操作类
			$image = new \Think\Image();
			
			$thumbSuccess = true;
			for($i=0;$i<count($thumbArray);$i++){
				$thumbWidth  	= $thumbArray[$i][0];
				$thumbHeight 	= $thumbArray[$i][1];
				$thumbImgName	= $thumbArray[$i][2];
                $thumbType      = $thumbArray[$i][3] ? : 1;
				$oldFile = $this->tempDir . $childDir . $tmpFilename;
				$tempArr = explode(".", $tmpFilename);
				if (empty($thumbImgName)){
					$newFile = $this->tempDir . $childDir .$tempArr[0]."_".$thumbWidth.".".$tempArr[1];
				} else {
					//指定了新名称
					$newFile = $this->tempDir . $childDir .$thumbImgName.".".$tempArr[1];
				}
				$image->open($oldFile);
				
				/*
				IMAGE_THUMB_SCALE     =   1 ; //等比例缩放类型
				IMAGE_THUMB_FILLED    =   2 ; //缩放后填充类型
				IMAGE_THUMB_CENTER    =   3 ; //居中裁剪类型
				IMAGE_THUMB_NORTHWEST =   4 ; //左上角裁剪类型
				IMAGE_THUMB_SOUTHEAST =   5 ; //右下角裁剪类型
				IMAGE_THUMB_FIXED     =   6 ; //固定尺寸缩放类型
				*/
				$thumbName = $image->thumb($thumbWidth, $thumbHeight, $thumbType)->save($newFile);
				if ($thumbName) {
					//$imgArr['thumbName'] = $this->autoDir.basename($thumbName);
				} else {
					$thumbSuccess = false;
					//$imgArr['thumbName'] = '';
				}
			}
			if (!$thumbSuccess){
				$info = $info. " 但是缩略图生成失败";
			}
        }
		
        /**************************判断是否需要生成缩略图结尾**********************/
		/**************************判断是否需要生成缩略图-开始-屏蔽此方式**********************/
        /*if ( !empty($_POST['thumbMaxWidth']) && !empty($_POST['thumbMaxHeight']) ) {
            $thumbName=@Image::thumb(
                $this->tempDir . $childDir.$tmpFilename,
                $this->tempDir . $childDir."pre_".$tmpFilename,
                $_POST['fileExt'],
                $_POST['thumbMaxWidth'],
                $_POST['thumbMaxHeight'],
                true,
                true
            );
            if ($thumbName!=false) {
                $imgArr['thumbName'] = $this->autoDir.basename($thumbName);
            } else {
                $imgArr['thumbName'] = '';
                $info = $info. " 但是缩略图生成失败";
            }
        }
        /**************************判断是否需要生成缩略图结尾**********************/
        /**************************判断是否需要加水印**********************/
        $water  = trim($_POST['water']);
        if($water == true)
        {
            $image = new \Think\Image();
            $image->open($imgPath)->water('./Public/Images/logo.png',9)->save($imgPath);
        }
        /**************************判断是否需要加水印结束**********************/
        $data = array(
            'status' => '1',
            'info' => $info,
            'data' => $imgArr
        );
        echo json_encode($data);
        exit;
    }
    
    /**
    * Short description：删除文件
    *
    * @return string
    */
    public function unlinks()
    {
		//获取要删除的数据
		$delDirArray = $_POST;
		//删除多余的
		unset($delDirArray['accessAuthKey']);
		unset($delDirArray['thumbJson']);
        if (empty($delDirArray)) {
            $data = array(
                'status' =>'0',
                'info' => 'There is no need to delete the file',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
        $succFlag = false;
        foreach ($delDirArray as $k=>$v) {
			if (empty($v)) {
				//过滤掉为空的数组，不然会导致删除全部，非常恐怖
				continue;
			}
			//过滤掉 两斜杆//
			$path = str_replace('//', '/', $this->tempDir.$v);
            //如果是文件夹
			if (is_dir($path)){
				$succFlag = $this->delDirAndFile($path);
				if (!$succFlag){
					$info = "delete failed. Please check the appropriate folder for sufficient permissions.";
				}
			} 
			//如果是文件 并且尝试删除 只有有一次删除成功 总体就算删除成功
			elseif (is_file($path) && file_exists($path)) {
                if (unlink($path) == true && ($succFlag == false) ) {
                    $succFlag = true;
                } elseif ($succFlag == false) {
                    $info = "File delete failed. Please check the appropriate folder for sufficient permissions.";
                }
            }
        }
        
        if ($succFlag == true) {
            $status = "1";
            $info = "File deletion success";
        } else {
            $status = "0";
            $info = !empty($info)?$info:"File delete failed";
        }
        $data = array(
            'status' =>$status,
            'info' => $info,
            'data' => ''
        );
        echo json_encode($data);
        exit;
    }

    /**
     * 压缩图片 绘制新图片
     *
     * @param string $thumb      压缩后的路径 绝对
     * @param string $image      压缩前的路径 绝对
     * @param string $percent    压缩比率 0.5 = 50%
     * @param int    $dis_width  画布宽
     * @param int    $dis_height 画布高
     * @param int    $p_x        裁剪坐标  X
     * @param int    $p_y        裁剪坐标  Y
     * @param int    $p_w        裁剪宽度  W
     * @param int    $p_h        裁剪高度  H
     * 
     * @return string 压缩后的路径
     */
    public function resizeThumbnailImageCaiJian($thumb, $image, $percent, $dis_width, $dis_height, $p_x, $p_y, $p_w, $p_h)
    {
        list($imagewidth, $imageheight, $imageType) = @getimagesize($image);
        $imageType = image_type_to_mime_type($imageType);
        $newImageWidth  = $dis_width;
        $newImageHeight = $dis_height;
        $newImage = imagecreatetruecolor($newImageWidth, $newImageHeight);
        switch ($imageType) {
        case "image/gif":
            $source = imagecreatefromgif($image);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            $source = imagecreatefromjpeg($image);
            break;
        case "image/png":
        case "image/x-png":
            $source = imagecreatefrompng($image);
            break;
        }
        
        imagecopyresampled($newImage, $source, 0, 0, $percent * $p_x, $percent * $p_y, $newImageWidth, $newImageHeight, $p_w * $percent, $p_h * $percent);
        switch ($imageType) {
        case "image/gif":
            imagegif($newImage, $thumb);
            break;
        case "image/pjpeg":
        case "image/jpeg":
        case "image/jpg":
            imagejpeg($newImage, $thumb, 100);
            break;
        case "image/png":
        case "image/x-png":
            imagepng($newImage, $thumb);
            break;
        }
        imagedestroy($newImage);
    }

    /**
     * 处理裁剪图片函数用LtCurl.upload(method)调用
     * 变量说明:被裁剪的原文件名$oldfile,压缩比$percent,裁剪尺宽$cai_w,裁剪尺高$cai_h,裁剪位置信息$p_arr
     * 
     * @return string json
     */
    public function handle_image()
    {
        $p_arr     = json_decode($_POST['posInfo'], true);
        $thumbSize = json_decode($_POST['thumbSize'], true);
        $oldfile   = $_POST['source'];
        $cai_w     = $thumbSize[0];
        $cai_h     = $thumbSize[1];
        //源图片,被裁取远程服务器本地图片
        $oldDir = $this->baseDir . $_POST['oldpath'];
        //完整的文件目录
        $childDir = $this->createDir($_POST['filePath'], $_POST['isAutoCreateYMDir']);
        if (!$childDir) {
            $data = array(
                'status' => '0',
                'info' => '文件夹创建失败，请检查相应文件夹是否有足够权限',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
        //保存文件名
        $tmpFilename = $_POST['saveName'] . "." . $_POST['fileExt'];
        //源图片物理地址
        $save_path_old = trim($oldDir.$oldfile);
        //新图片保存地址
        $newfile = $this->tempDir . $childDir . $tmpFilename;
        list($percent, $p_x, $p_y, $p_w, $p_h) = $p_arr;
        $path = $this->resizeThumbnailImageCaiJian(trim($newfile), $save_path_old, $percent, $cai_w, $cai_h, $p_x, $p_y, $p_w, $p_h);
        $newfilepath     = $tmpFilename.'?'.rand(1, 1000);
        
        $data = array(
            'status' => '1',
            'info'   => '截剪成功',
            'data'   => $newfilepath
        );
        echo json_encode($data);
        exit;
    }

    /**
     * 获取文件size
     *
     * @return void
     */
    public function getimgsize()
    {
        $file = $this->tempDir . trim($_POST['imgpath']);
        if (is_file($file) && file_exists($file)) {
            echo json_encode(array('status' => '1', 'data'   => @getimagesize($file), 'info'=>$file));
            exit;
        } else {
            echo json_encode(array('status' => '0', 'info'=>$file, 'isfile'=>$bool1, 'exists'=>$bool2, 'size'   => @getimagesize($file)));
            exit;
        }
    }
	/**
     * 循环删除目录和文件函数
	 *
	 * @param $dirName	#目录名称
     *
     * @return void
     */
	public function delDirAndFile($dirName)
	{
		$isSucess = false;
		if ( $handle = opendir($dirName) ) {
			while ( false !== ( $item = readdir( $handle ) ) ) {
				if ( $item != "." && $item != ".." ) {
					if (is_dir("$dirName/$item")) {
						$this->delDirAndFile("$dirName/$item");
					} else {
						if( unlink("$dirName/$item") ) $isSucess=true;
					}
				}
			}
			closedir( $handle );
			if( rmdir( $dirName ) ) $isSucess = true;
		}
		return $isSucess;
	}
	/**
    * Short description：接收base64位图片或文件
    *
    * @return string
    */
    public function uploadBase64()
    {
		$imgStr 	= trim($_POST['imgStr']);
		$imgStr 	= str_replace(' ','', $imgStr);
		$imgDir 	= trim($_POST['imgDir']);
		$imgName 	= trim($_POST['imgName']);
		$thumbJson 	= trim($_POST['thumbJson']);
        if (empty($imgStr)) {
            $data = array(
                'status' => '0',
                'info' => '没有文件上传',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
        $childDir = $this->createDir($imgDir, false);
        if (!$childDir) {
            $data = array(
                'status' => '0',
                'info' => 'Folder creation failed, please check the appropriate folder if there is sufficient authority',
                'data' => ''
            );
            echo json_encode($data);
            exit;
        }
		//指定文件名，含后缀
        $tmpFilename = $imgName;
        
        $img = base64_decode($imgStr);
		$allPath = $this->tempDir . $childDir.$tmpFilename;
		$result = file_put_contents($allPath, $img);//返回的是字节数
        if (!$result){
			 $data = array(
                'status' => '0',
                'info' => 'write faild!',
                'data' => ''
            );
            echo json_encode($data);
            exit;
		}
        $imgArr            = array();
        $imgArr['imgName'] = $this->userDir . $tmpFilename;
        $info = "文件上传成功";
        
        /**************************判断是否需要生成缩略图-开始-**********************/
		$thumbArray = json_decode($thumbJson, true);
        if (!empty($thumbArray)) {
			//初始化图片操作类
			$image = new \Think\Image();
			
			$thumbSuccess = true;
			for($i=0;$i<count($thumbArray);$i++){
				$thumbWidth  	= $thumbArray[$i][0];
				$thumbHeight 	= $thumbArray[$i][1];
				$thumbImgName	= $thumbArray[$i][2];
                $thumbType      = $thumbArray[$i][3] ? : 1;
				$oldFile = $this->tempDir . $childDir.$tmpFilename;
				$tempArr = explode(".", $tmpFilename);
				if (empty($thumbImgName)){
					$newFile = $this->tempDir . $childDir.$tempArr[0]."_".$thumbWidth.".".$tempArr[1];
				} else {
					//指定了新名称
					$newFile = $this->tempDir . $childDir.$thumbImgName.".".$tempArr[1];
				}
				$image->open($oldFile);
				
				/*
				IMAGE_THUMB_SCALE     =   1 ; //等比例缩放类型
				IMAGE_THUMB_FILLED    =   2 ; //缩放后填充类型
				IMAGE_THUMB_CENTER    =   3 ; //居中裁剪类型
				IMAGE_THUMB_NORTHWEST =   4 ; //左上角裁剪类型
				IMAGE_THUMB_SOUTHEAST =   5 ; //右下角裁剪类型
				IMAGE_THUMB_FIXED     =   6 ; //固定尺寸缩放类型
				*/
				//获取旧文件的宽度和高度
				$oldWidth 	= $image->width();
				$oldHeight 	= $image->height();
				if ($thumbWidth>$oldWidth && $thumbHeight>$oldWidth) {
					//如果要裁剪的宽度和高度比旧文件还大，跳出循环
					$thumbSuccess = false;
					break 2;
				}
				
				$thumbName = $image->thumb($thumbWidth, $thumbHeight, $thumbType)->save($newFile);
				if ($thumbName) {
					//$imgArr['thumbName'] = $this->autoDir.basename($thumbName);
				} else {
					$thumbSuccess = false;
					//$imgArr['thumbName'] = '';
				}
			}
			if (!$thumbSuccess){
				$info = $info. " 但是缩略图生成失败";
			}
        }
        /**************************判断是否需要生成缩略图结尾**********************/
         
        $data = array(
            'status' => '1',
            'info' => $info,
            'data' => $imgArr
        );
        echo json_encode($data);
        exit;
    }

    /**
    * 列出所有文件夹所有文件-可按照大小/时间/名称排序
    *
    * @return array
    */
    public function showFileListOrder(){
        $dir       = trim($_POST['dir']);
        if (is_dir($this->baseDir.$dir)) {  
           if ($dh = opendir($this->baseDir.$dir)) {  

                while (($file = readdir($dh)) !== false) {  
                if ($file!="." && $file!="..") {  
                        $path[] = $file;    
                    }  
                }  
                closedir($dh);  
                echo json_encode($path);
           }  
        }  
    }
    //百度编辑器图片上传
    public function ueditorUpFile(){
        if (empty($_FILES['transferFile'])) {
            echo json_encode(['status'=>'-1']);
            exit;
        }
        $filePath = self::getFilePath($_POST['fullName']);
        $dirname  = dirname($filePath);

        //创建目录
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            echo json_encode(['status'=>'-2']);
            exit;
        } else if (!is_writeable($dirname)) {
            echo json_encode(['status'=>'-3']);
            exit;
        }

        //移动文件
        if (!(move_uploaded_file($_FILES['transferFile']['tmp_name'], $filePath) && file_exists($filePath))) { //移动失败
            echo json_encode(['status'=>'-4']);
        } else { //移动成功
            //添加水印
            $image = new \Think\Image();
            $image->open($filePath)->water('./Public/Images/logo.png',9)->save($filePath);
            echo json_encode(['status'=>'8']);
        }
        
    }
    
    //百度编辑器上传Base64
    public function ueditorUpBase64(){
        $imgStr     = trim($_POST['imgStr']);
        if (empty($imgStr)) {
            echo json_encode(['status'=>'-1']);
            exit;
        }
        $filePath = self::getFilePath($_POST['fullName']);
        $dirname  = dirname($filePath);

        //创建目录
        if (!file_exists($dirname) && !mkdir($dirname, 0777, true)) {
            echo json_encode(['status'=>'-2']);
            exit;
        } else if (!is_writeable($dirname)) {
            echo json_encode(['status'=>'-3']);
            exit;
        }

        //移动文件
        if (!(file_put_contents($filePath, $imgStr) && file_exists($filePath))) { //移动失败
            echo json_encode(['status'=>'-4']);
        } else { //移动成功
            //添加水印
            $image = new \Think\Image();
            $image->open($filePath)->water('./Public/Images/logo.png',9)->save($filePath);
            echo json_encode(['status'=>'8']);
        }
        
    }

    /**
     * 获取文件完整路径
     * @return string
     */
    private function getFilePath($fullName)
    {
        $rootPath = $_SERVER['DOCUMENT_ROOT'];

        if (substr($fullName, 0, 1) != '/') {
            $fullName = '/' . $fullName;
        }

        return $rootPath . $fullName;
    }
}
?>