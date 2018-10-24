<?php
/**
 * 推荐产品分类列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2017-3-8
 */
use Think\Tool\Tool;
class IntroClassController extends CommonController {
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('IntroClass');
        //手动获取列表
        $list = $this->_list(CM("IntroClass"), $map);
        foreach ($list as $k => $v) {
            $list[$k]['logo'] = Tool::imagesReplace($v['logo']);
            $list[$k]['background'] = Tool::imagesReplace($v['background']);
        }
        $this->assign('list', $list);
        $this->display();
    }
    public function add() {

        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("IntroClass")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['logo'] = Tool::imagesReplace($vo['logo']);
        $vo['background'] = Tool::imagesReplace($vo['background']);
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

        $model = D('IntroClass');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (empty($id)) {
            if (empty($_FILES['fileInput']['tmp_name'])) {
                $this->error('请上传分类图标!');
                exit;
            }
            if (empty($_FILES['fileInput2']['tmp_name'])) {
                $this->error('请上传分类背景图!');
                exit;
            }
            $model->create_time = time();
            //为新增
            $rs = $model->add();
            //上传图片
            $logoRs = D('Uploads')->uploadImg("fileInput", "introclass" ,$rs.'_logo');
            if($logoRs['status'] == 1){
                $imgSrc['logo'] = $logoRs['url'];
            }
            $backRs = D('Uploads')->uploadImg("fileInput2", "introclass" ,$rs.'_back');
            if($backRs['status'] == 1){
                $imgSrc['background'] = $backRs['url'];
            }
            M('IntroClass')->where(['id'=>$rs])->save($imgSrc);
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来广告图
                $fileArr = array(
                    "/introclass/{$id}_logo.jpg",
                    "/introclass/{$id}_logo.gif",
                    "/introclass/{$id}_logo.png",
                    "/introclass/{$id}_logo.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $logoRs = D('Uploads')->uploadImg("fileInput", "introclass" ,$id.'_logo');
                if($logoRs['status'] == 1){
                    $imgSrc['logo'] = $logoRs['url'];
                }  
            }
            if (!empty($_FILES['fileInput2']['tmp_name'])) {
                //先删除原来广告图
                $fileArr = array(
                    "/introclass/{$id}_back.jpg",
                    "/introclass/{$id}_back.gif",
                    "/introclass/{$id}_back.png",
                    "/introclass/{$id}_back.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $backRs = D('Uploads')->uploadImg("fileInput2", "introclass" ,$id.'_back');
                if($backRs['status'] == 1){
                    $imgSrc['background'] = $backRs['url'];
                }  
            }
            if($imgSrc) M('IntroClass')->where(['id'=>$id])->save($imgSrc);
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
        $type = I('type');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/introclass/{$id}".$type.".jpg",
            "/introclass/{$id}".$type.".gif",
            "/introclass/{$id}".$type.".png",
            "/introclass/{$id}".$type.".swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            $field = $type == '_logo' ? 'logo' : 'background';
            M("IntroClass")->where(['id'=>$id])->save([$field=>'']);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("IntroClass");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/introclass/{$id}_logo.jpg",
                        "/introclass/{$id}_logo.gif",
                        "/introclass/{$id}_logo.png",
                        "/introclass/{$id}_logo.swf",
                        "/introclass/{$id}_back.jpg",
                        "/introclass/{$id}_back.gif",
                        "/introclass/{$id}_back.png",
                        "/introclass/{$id}_back.swf",
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
}