<?php
/**
 * 奖品列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-6-30
 */
use Think\Tool\Tool;
class PrizeController extends CommonController {
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('Prize');
        //手动获取列表
        $list = $this->_list(CM("Prize"), $map);
        foreach ($list as $k => $v) {
            $list[$k]['titleimg'] = Tool::imagesReplace($v['img']);
        }
        $this->assign('list', $list);
        $this->display();
    }
    public function add() {

        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("Prize")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $vo['titleimg'] = Tool::imagesReplace($vo['img']);
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
        $model = D('prize');
        if (false === $model->create()) {
            $this->error($model->getError());
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
                $return = D('Uploads')->uploadImg("fileInput", "prize", $rs);
                if($return['status'] == 1)
                    M('prize')->where(['id'=>$rs])->save(['img'=>$return['url']]);
            }
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图
                $fileArr = array(
                    "/prize/{$id}.jpg",
                    "/prize/{$id}.gif",
                    "/prize/{$id}.png",
                    "/prize/{$id}.swf",
                );
                //执行删除
                $return = D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "prize",$id);
                if($return['status'] == 1)
                    M('prize')->where(['id'=>$id])->save(['img'=>$return['url']]);
            }
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
        $fileArr = array(
            "/prize/{$id}.jpg",
            "/prize/{$id}.gif",
            "/prize/{$id}.png",
            "/prize/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            M("prize")->where(['id'=>$id])->save(['img'=>NULL]);
            $this->success('删除成功！');
        }else{
            $this->error('删除失败！');
        }
    }
    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("prize");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/prize/{$id}.jpg",
                        "/prize/{$id}.gif",
                        "/prize/{$id}.png",
                        "/prize/{$id}.swf",
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