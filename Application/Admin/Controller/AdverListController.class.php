<?php
/**
 * 广告列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-12-3
 */
use Think\Tool\Tool;
class AdverListController extends CommonController {
        /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
        $AdverClass = M('AdverClass')->where(array('status'=>1))->select();
        $this->assign('AdverClass', $AdverClass);
    }
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('AdverList');
        //手动获取列表
        $list = $this->_list(CM("AdverList"), $map);
        foreach ($list as $k => $v) {
            $list[$k]['titleimg'] = Tool::imagesReplace($v['img']);
            if($v['module'] != 9) $list[$k]['url'] = explode('_',$v['url'])[0];
        }
        $this->assign('list', $list);
        $this->display();
    }
    public function add() {

        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("AdverList")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['titleimg'] = Tool::imagesReplace($vo['img']);
        $url = $vo['module'] == 9 ? $vo['url'] : explode('_',$vo['url'])[0];
        $this->assign('url', $url);
        $this->assign('vo', $vo);
        $this->display("add");
    }

    public function intro() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('AdverList');
        $map['class_id'] = 31;
        //手动获取列表
        $list = $this->_list(CM("AdverList"), $map);
        $this->assign('list', $list);
        $this->display();
    }

    public function addIntro() {

        $this->display();
    }

    public function editIntro() {
        $id = I('id');
        $vo = M("AdverList")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign('vo', $vo);
        $this->display("addIntro");
    }

    public function saveIntro(){
        $id = I('id', 'int');
        $_POST['online_time'] = strtotime(I("online_time"));//转化上架设定开始时间
        $_POST['end_time'] = strtotime(I("end_time"));//转化上架到期时间
        if($_POST['online_time'] > $_POST['end_time']){
            $this->error('开始时间必须小于结束时间!');
            exit;
        }

        $model = D('AdverList');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (empty($id)) {
            //为新增
            $rs = $model->add();
        }else{
            //为修改
            $rs = $model->save();
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $_POST['online_time'] = strtotime(I("online_time"));//转化上架设定开始时间
        $_POST['end_time'] = strtotime(I("end_time"));//转化上架到期时间
        if($_POST['online_time'] > $_POST['end_time']){
            $this->error('开始时间必须小于结束时间!');
            exit;
        }

        if($_POST['module'] == 10){
            $_POST['url'] =  explode('_',$_POST['url'])[0] . $_POST['grzx'];
        }

        $model = D('AdverList');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        $imgName = $thump = '';
        //是否有上传
        if (!empty($_FILES['fileInput']['tmp_name'])) {
            //判断是否安卓启动页
            if($model->class_id == 16 && $model->platform == 3){
                $imgsize = getimagesize($_FILES['fileInput']['tmp_name']);
                if($imgsize[0] != 1440){
                    $this->error('安卓启动页图片需上传1440尺寸图片!');
                    exit;
                }
                //启动页图片自动裁剪
                $thump = "[[1080,1920,1080],[720,1280,720],[768,1280,768]]";
                $imgName = "1440P";
            }
            if($model->class_id == 1){
                $imgsize = getimagesize($_FILES['fileInput']['tmp_name']);
                if($imgsize[0] != 600){
                    $this->error('需上传600尺寸图片!');
                    exit;
                }
                //启动页图片自动裁剪
                $thump = "[[600,246,\"246P\",4]]";
                $imgName = "321P";
            }
        }
        if (empty($id)) {
            if (empty($_FILES['fileInput']['tmp_name'])) {
                $this->error('请上传图片!');
                exit;
            }
            //为新增
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "adver", $imgName ,$rs,$thump);
                if($return['status'] == 1)
                    M('AdverList')->where(['id'=>$rs])->save(['img'=>$return['url']]);
            }
        }else{
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来广告图
                $fileArr = array("/adver/{$id}");
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "adver", $imgName ,$id,$thump);
                if($return['status'] == 1)
                    M('AdverList')->where(['id'=>$id])->save(['img'=>$return['url']]);
            }
            //为修改
            $rs = $model->save();
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }
    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array("/adver/{$id}");
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            M("AdverList")->where(['id'=>$id])->save(['img'=>NULL]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("AdverList");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/adver/{$id}",
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
        $model = M("AdverList");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($idsArr as $k => $v) {
                        $fileArr = array(
                            "/adver/{$v}",
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