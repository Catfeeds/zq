<?php
set_time_limit(0);//0表示不限时
/**
 * 高手列表
 *
 * @author liuweitao <cytusc@foxmail.com>
 * @since  2016-12-14
 */

class MasterListController extends CommonController {
    /**
    *构造函数
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
    }
    public function index(){
        $map = $this->_search('MasterList');
            //点击渠道查询中的昵称所传过来的user_id,进行筛选
            $list = $this->_list(CM('MasterList'),$map);
            $this->assign('list',$list);
            $this->display();

    }

    public function edit() {
        $id = I('id');
        $vo = M('MasterList')->where(['id'=>$id])->find();
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['face'] = frontUserFace($vo['head']);
        $this->assign('vo', $vo);
        $this->display("add");

    }
    //增加修改用户信息
    public function save(){
        $id = I('id');
        $model = M('MasterList');
        $_POST['add_time'] = time();
        if (empty($id)) {
            //为新增
            $rs = $model->add($_POST);
            if($rs){
                //上传图片
                if (!empty($_FILES['fileInput']['tmp_name'])) {
                    $fileInput = $_FILES['fileInput'];
                    $return = D('Uploads')->uploadImg("fileInput", "master,{$rs}", '200' ,'face',"[[200,200,200]]");
                    if($return['status'] == 1){
                        M("MasterList")->where(['id'=>$rs])->save(['head'=>$return['url']]);
                    }
                }
            }
        }else{
            //为修改
            $rs = $model->save($_POST);
            if(!is_bool($rs)){
                $rs = true;
            }
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来头像
                $fileArr = array("/master/{$id}/face");
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $fileInput = $_FILES['fileInput'];
                $return = D('Uploads')->uploadImg("fileInput", "master,{$id}", '200' ,'face',"[[200,200,200]]");
                if($return['status'] == 1){
                    M("MasterList")->where(['id'=>$id])->save(['head'=>$return['url']]);
                }
            }
        }
        if ($rs) {
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
        } else {
            //错误提示
            $this->error('保存失败!');
        }
    }

    /**
     * 批量禁用启用
     * @access
     * @return string
     */
    public function saveAll(){
        //删除指定记录
        $ids = isset($_POST['id']) ? $_POST['id'] : null;
        if ($ids) {
            $status = $_REQUEST['status'];
            $idsArr = explode(',', $ids);
            $condition = array ("id" => array ('in',$idsArr));
            $rs = M('MasterList')->where($condition)->save(['status'=>$status]);
            if($rs !== false){
                $this->success('设置成功');
            }else{
                $this->error('设置失败');
            }
        } else {
            $this->error('非法操作');
        }
    }

    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array("/master/{$id}/face");
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            M("FrontUser")->where(['id'=>$id])->save(['head'=>NULL]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }

    /**
     * 添加删除操作  (多个删除)
     * @access
     * @return string
     */
    public function delAll(){
        //删除指定记录
        $model = M("MasterList");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    foreach ($idsArr as $k => $v) {
                        $fileArr = array(
                            "/master/{$v}.jpg",
                            "/master/{$v}.gif",
                            "/master/{$v}.png",
                            "/master/{$v}.swf",
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