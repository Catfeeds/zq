<?php
/**
 * 上传模型类
 * @author dengweijun <406516482@qq.com> 2016.4.15
 */

use Think\Model;
use Think\Tool\Tool;
use Think\Tool\Curl;
class UploadsModel extends Model
{
	/**
     * 上传图片文件
     * 
     * @param string $formName 		#表单名称
	 * @param string $beforDir 		#上传目录-以id为目录分割的前半部分目录，用英文逗号隔开
	 * @param string $fileName 		#不含后缀的文件名
	 * @param int 	 $id 			#操作的记录id
	 * @param json 	 $thumbJson 	#储存了要裁剪的json数组
	 * @param bool 	 $water 	    #是否添加水印
     * 
     * @return string
     */
    public function uploadImg($formName = "filedata", $beforDir=NULL, $fileName=NULL, $id=NULL, $thumbJson=NULL,$water=false)
    {
		$this->curl = new Curl();
		//判断表单域名称
		if ($formName=='filedata'){
			$formNameTemp = I('formName');
			if (!empty($formNameTemp)){
				$formName = $formNameTemp;
			}
		}
		//判断目录
		if (empty($beforDir)){
			$beforDir = I('beforDir');
		}
		//判断文件名来源
		if (empty($fileName)){
			$fileName = I('fileName');
		}
		if (empty($fileName)){
			$fileName = date('YmdHis', time());
		}
		//判断是否存在裁剪json
		if (empty($thumbJson)){
			$thumbJson = I('thumbJson');
		}
		

        $this->curl->maxSize    = '3072000'; //允许单张图片大小的最大值(1m)
        $this->curl->allowTypes = array( //允许上传的文件的mime类型
            'image/jpeg',
            'image/pjpeg',
            'image/png',
            'image/x-png',
            'image/gif',
            'application/x-shockwave-flash'
        );
        $this->curl->allowExts  = array( //允许上传图片的后缀
            'jpg',
            'jpeg',
            'gif',
            'png',
            'swf',
        );
        //$input = file_get_contents('php://input');
        $this->curl->savePath          = "{$beforDir},{$id}"; //保存图片的路径
        $this->curl->isAutoCreateYMDir = false; //是否在指定的文件夹内创建以年月命名的二级文件夹 true是创建 false就不创建
        $this->curl->saveRule          = ""; //图片文件名的保存规则 留空则以YmdHis命名 可以传uniqid time这样不带参数的函数
        $this->curl->formFileName      = $formName; //html中的上传文件的name值
		if (!empty($fileName)) {
			$this->curl->customName	   = $fileName;//自定义文件名，不含文件扩展名
		}
		$this->curl->thumbJson         = $thumbJson;
		$this->curl->water             = $water;
        $imageArr                      = $this->curl->upload(); //返回的是数组 array('status'=>1, data=>'', 'info'=>'')
		
		/*header("content-Type: text/html; charset=utf-8"); 
		dump($imageArr);*/

		//返回
		if ($imageArr['status'] == 1) {
			$data['status'] = 1;
			$data['info']   = "上传成功";	
			$imgUrl = $imageArr['data']['imgDir'];
			$data['url'] = $imgUrl.'?'.date('is');
		} else {
			$data['status'] = 0;
			$data['info']   = "上传失败";	
		}

		return $data;
    }
	/**
     * 上传非图片文件
     * 
     * @param string $formName 		#表单名称
	 * @param string $beforDir 		#上传目录-以id为目录分割的前半部分目录，用英文逗号隔开
	 * @param string $fileName 		#不含后缀的文件名
	 * @param int 	 $id 			#操作的记录id
     * 
     * @return string
     */
    public function uploadFile($formName = "filedata", $beforDir=NULL, $fileName=NULL, $id=NULL)
    {
		$this->curl = new Curl();
		//判断表单域名称
		if ($formName=='filedata'){
			$formNameTemp = I('formName');
			if (!empty($formNameTemp)){
				$formName = $formNameTemp;
			}
		}
		//判断目录
		if (empty($beforDir)){
			$beforDir = I('beforDir');
		}
		//判断文件名来源
		if (empty($fileName)){
			$fileName = I('fileName');
		}
		if (empty($fileName)){
			$fileName = date('YmdHis', time());
		}
		//判断是否存在裁剪json
		if (empty($thumbJson)){
			$thumbJson = I('thumbJson');
		}
		

        $this->curl->maxSize    = '3072000'; //允许单张图片大小的最大值(1m)
        
		/*$this->curl->allowTypes = array( //允许上传的文件的mime类型
            'application/pdf',
            'text/plain',
            'application/msword',
            'video/x-msvideo',
            'application/zip',
			'application/x-shockwave-flash',
			'application/vnd.android.package-archive'
        );
        $this->curl->allowExts  = array( //允许上传图片的后缀
            'pdf',
            'txt',
            'word',
            'avi',
			'zip',
			'rar',
			'swf',
			'apk',
        );*/
        //$input = file_get_contents('php://input');
        $this->curl->savePath          = "{$beforDir},{$id}"; //保存图片的路径
        $this->curl->isAutoCreateYMDir = false; //是否在指定的文件夹内创建以年月命名的二级文件夹 true是创建 false就不创建
        $this->curl->saveRule          = ""; //图片文件名的保存规则 留空则以YmdHis命名 可以传uniqid time这样不带参数的函数
        $this->curl->formFileName      = $formName; //html中的上传文件的name值
		if (!empty($fileName)) {
			$this->curl->customName	   = $fileName;//自定义文件名，不含文件扩展名
		}

        $imageArr                      = $this->curl->upload(); //返回的是数组 array('status'=>1, data=>'', 'info'=>'')
		
		/*header("content-Type: text/html; charset=utf-8"); 
		dump($imageArr);*/

		//返回
		if ($imageArr['status'] == 1) {
			$data['status'] = 1;
			$data['info']   = "上传成功";	
			$imgUrl = $imageArr['data']['imgDir'];
			$data['url'] = $imgUrl.'?'.date('is');
		} else {
			$data['status'] = 0;
			$data['info']   = "上传失败";	
		}

		return $data;
    }
	/**
     * 上传Base64位数据流
     * 
	 * @param string $imgData 		#Base64数据流
	 * @param string $beforDir 		#上传目录-以id为目录分割的前半部分目录，用英文逗号隔开
	 * @param string $afterDir 		#上传目录-以id为目录分割的后半部分目录
	 * @param string $fileName 		#不含后缀的文件名
	 * @param int 	 $id 			#操作的记录id
	 * @param json 	 $thumbJson 	#储存了要裁剪的json数组
	 * @param bool 	 $isRetrun 		#是否直接返回
	 *
     * @return json
    */
    public function uploadFileBase64($imgData=NULL, $beforDir=NULL, $afterDir=NULL, $fileName=NULL, $id=NULL, $thumbJson=NULL, $isRetrun=false, $isVoice=false)
    {
		//判断id来源
		if (empty($id)){
			$id = I('id');
		}
		//判断前置目录
		if (empty($beforDir)){
			$beforDir = I('beforDir');
		}
		//判断后置目录
		if (empty($afterDir)){
			$afterDir = I('afterDir');
		}
		//判断流来源
		if (empty($imgData)){
			$imgData = I('imgData');
		}
		//判断文件名来源
		if (empty($fileName)){
			$fileName = I('fileName');
		}
		if (empty($fileName)){
			$fileName = date('YmdHis', time());
		}
		//判断是否存在裁剪json
		if (empty($thumbJson)){
			$thumbJson = I('thumbJson');
		}
		//将base64解析成图片
		$tempArr = explode('base64,', $imgData);

		if($isVoice){//音频都是mp3
			$suffix = "mp3";
			$imgStr = $imgData;
		}else {
			if (count($tempArr) > 1) {
				//说明需要验证格式
				if (!in_array($tempArr[0], array('data:image/jpg;','data:image/jpeg;', 'data:image/png;', 'data:image/pjpeg;', 'data:image/x-png;', 'data:image/gif;'))) {
					$data['status'] = 0;
					$data['info'] = '格式不正确';
					return $data;
				}
				if (strpos("hd" . $tempArr[0], "png")) {
					$suffix = "png";
				} elseif (strpos("hd" . $tempArr[0], "jpg")) {
					$suffix = "jpg";
				} elseif (strpos("hd" . $tempArr[0], "jpeg")) {
					$suffix = "jpeg";
				} elseif (strpos("hd" . $tempArr[0], "gif")) {
					$suffix = "gif";
				}
				//必须把空格替换掉(imgStr:Base64数据)
				$imgStr = str_replace(' ', '', $tempArr[1]);
			} else {
				//说明不需要验证格式，默认格式为jpg
				$suffix = "jpg";
				$imgStr = $imgData;
			}
		}
		//保存文件的路径 多个以英文逗号隔开
		$imgArr = [$beforDir,$id,$afterDir];
		$imgDir = implode(',', array_filter($imgArr));

		//组装文件名
		$imgName = $fileName.".".$suffix;

		$result = Tool::uploadBase64($imgStr, $imgDir, $imgName, $thumbJson);
		if ($result['status']==1){
			$data['status'] = 1;
			$data['info']   = $result["info"];
			$data['url']    = '/Uploads/'.$result['data']['imgName'].'?'.date('is');		
		} else {
			$data['status'] = 0;
			$data['info']   = $result["info"];
		}
		//处理判断返回
		return $data;
    }
	/**
     * 删除远程文件
     * 
     * @param string $fileOrDirArray #要删除的文件或目录二维数组，格式："/dyimages/goods/$id",相对于files.booogo.com目录
     * 
     * @return string
     */
    public function deleteFile($fileOrDirArray)
    {
		if (empty($fileOrDirArray)){
			return "参数错误";
		}
		$this->curl = new Curl();
		$this->curl->unlinks = $fileOrDirArray;
		$result = $this->curl->unlink();
		if ($result['status']==1){
			$data['status'] = 1;
			$data['info']   = "删除成功";	
		} else {
			$data['status'] = 0;
			$data['info']   = "删除失败";
		}
		return $data;
	}
}