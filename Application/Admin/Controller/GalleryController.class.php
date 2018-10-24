<?php
/**
 * 图库列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-7
 */
use Think\Tool\Tool;
class GalleryController extends CommonController {
    public $GalleryClass = [];
    /**
    *构造函数
    *
    * @return  #     
    */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
        $GalleryClass = M('GalleryClass')->where("status=1")->select();
        $this->GalleryClass = $GalleryClass;
        //引用Tree类
        $Class = Tool::getTree($GalleryClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('GalleryClass', $Class);
    }
    public function index(){
        $map = $this->_search('Gallery');
        $list = $this->_list(CM('Gallery'),$map);
        foreach ($list as $k => $v) {
            $img_array = json_decode($v['img_array'],true);
            $list[$k]['img']  = setImgThumb($img_array[1],'240');
            $list[$k]['href'] = galleryUrl($v['id'], $v['path'],$v['add_time']);
            unset($list[$k]['img_array']);
        }
        $this->assign('list', $list);
        $this->display();
    }
    public function edit() {
        $id = I('id');
        $vo = M("Gallery")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        //获取图片数组
        $img_array = M('Gallery')->where(['id'=>$id])->getField('img_array');
        $imgArr = json_decode($img_array,true);
        for($i=1;$i<31;$i++){
            if ($imgArr && !empty($imgArr[$i])){
                //存在
                $vo['img'][$i]['url']   = Tool::imagesReplace($imgArr[$i]);
            } else {
                $vo['img'][$i]['url'] = "";
            }
        }
        $vo['describe'] = json_decode($vo['describe'],true);
        $this->assign('vo', $vo);
        $this->display("add");
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $model = D('Gallery');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        $model->describe = json_encode(I("describe"));
        $model->editor   =$_SESSION['authId'];
        $model->title    = htmlspecialchars_decode($_POST['title']);
        $model->short_title   = htmlspecialchars_decode($_POST['short_title']);
        if(!empty($_POST['add_time'])){
            $add_time = strtotime($_POST['add_time']);
            if($add_time > time()){
                $this->error('发布时间不能大于当前时间!');
            }
            $model->add_time = $add_time;
        }else{
            $model->add_time = time();
        }
        if (empty($id)) {
            //为新增
            $rs = $model->add();
            //上传图片
            for ($i=1; $i <31 ; $i++) { 
                if (!empty($_FILES['fileInput_'.$i]['tmp_name'])) {
                    $thumb = $i <=3 ? "[[240,240,\"$i_240\"]]" : NULL;
                    $return = D('Uploads')->uploadImg('fileInput_'.$i,"gallery",$i,$rs,$thumb);
                    if($i == 1){
                        $size = getimagesize($_FILES['fileInput_'.$i]['tmp_name']);
                        $return['url'] .= '&size='.$size[0].'X'.$size[1]; 
                    }
                    $pathArr[$i] = $return['url'];
                }
            }
            M("Gallery")->where(['id'=>$rs])->save(['img_array'=>json_encode($pathArr)]);
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            for ($i=1; $i <31 ; $i++) { 
                if (!empty($_FILES['fileInput_'.$i]['tmp_name'])) {
                    //先删除原来图片
                    $fileArr = array(
                        "/gallery/{$id}/{$i}.jpg",
                        "/gallery/{$id}/{$i}.gif",
                        "/gallery/{$id}/{$i}.png",
                        "/gallery/{$id}/{$i}.swf"
                    );
                    D('Uploads')->deleteFile($fileArr);
                    $thumb = $i <= 3 ? "[[240,240,\"$i_240\"]]" : NULL;
                    $return = D('Uploads')->uploadImg('fileInput_'.$i,"gallery",$i,$id,$thumb);
                    if($i == 1){
                        $size = getimagesize($_FILES['fileInput_'.$i]['tmp_name']);
                        $return['url'] .= '&size='.$size[0].'X'.$size[1]; 
                    }
                    $pathArr[$i] = $return['url'];
                }
            }
            if(!empty($pathArr)){
                $img_array = M('Gallery')->where(['id'=>$id])->getField('img_array');
                $imgArr = json_decode($img_array,true);
                if(!empty($imgArr)){
                    foreach ($imgArr as $k => $v) {
                        foreach ($pathArr as $key => $value) {
                            if($k != $key){
                                $imgArr[$key] = $value;
                            }
                        }
                    }
                    ksort($imgArr);
                    M("Gallery")->where(['id'=>$id])->save(['img_array'=>json_encode($imgArr)]);
                }else{
                    //为空直接修改
                    M("Gallery")->where(['id'=>$id])->save(['img_array'=>json_encode($pathArr)]);
                }
            }
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'),'',true);
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
    //异步删除图片
    public function delPic(){
        $id = I('id');
        $number = I('number');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/gallery/{$id}/{$number}.jpg",
            "/gallery/{$id}/{$number}.gif",
            "/gallery/{$id}/{$number}.png",
            "/gallery/{$id}/{$number}.swf"
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            $img_array = M('Gallery')->where(['id'=>$id])->getField('img_array');
            $imgArr = json_decode($img_array,true);
            foreach ($imgArr as $k => $v) {
                if($k == $number){
                    $imgArr[$k] = '';
                }
            }
            //修改路径
            M("Gallery")->where(['id'=>$id])->save(['img_array'=>json_encode($imgArr)]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        } 
    }

    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("Gallery");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/gallery/{$id}",
                    );
                    //执行删除
                    $return = D('Uploads')->deleteFile($fileArr);
                    $this->success('删除成功！');
                } else {
                    $this->error('删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }


    /**
    +----------------------------------------------------------
    * 添加删除操作  (多个删除)
    +----------------------------------------------------------
    * @access public
    +----------------------------------------------------------
    * @return string
    +----------------------------------------------------------
    * @throws ThinkExecption
    +----------------------------------------------------------
    */

    public function delAll(){
        //删除指定记录
        $model = M("Gallery");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($idsArr as $k => $v) {
                        $fileArr = array(
                            "/gallery/{$v}",
                        );
                        //执行删除
                        $return = D('Uploads')->deleteFile($fileArr);
                    }
                    $this->success('批量删除成功！');
                } else {
                    $this->error('批量删除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }
}