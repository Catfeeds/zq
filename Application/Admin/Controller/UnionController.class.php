<?php
/**
 * 足球联盟管理
 * @author huangjiezhen <418832673@qq.com> 2015.12.28
 */
use Think\Tool\Tool;
class UnionController extends CommonController
{
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('Union');
        //手动获取列表
        $list = $this->_list(CM("Union"), $map);
        $this->country = M('Country')->field(['country_id','country_name'])->select();
        foreach ($list as $k => $v) {
            $img = Tool::imagesReplace($v['img']);
            $list[$k]['img'] = $img ? $img : '';
        }
        $this->assign('list', $list);
        $this->display();
    }
    public function add(){
    	$this->display('edit');
    }

    public function edit() {
        $id = I('id');
        $vo = M("Union")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $img = Tool::imagesReplace($vo['img']);
        $vo['img'] = $img ? $img : '';
        $this->assign('vo', $vo);
        $this->display();
    }
    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $model = D('Union');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        /*if (!empty($_FILES['fileInput']['tmp_name'])) {
            $filetype = pathinfo($_FILES["fileInput"]["name"], PATHINFO_EXTENSION);//获取后缀
        }*/
        if (empty($id)) {
            //为新增
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "union", $rs);
                //修改路径
                if($return['status'] == 1){
                    M("Union")->where(['id'=>$rs])->save(['img'=>$return['url']]);
                }
            }
        }else{
            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/union/{$id}.jpg",
                    "/union/{$id}.gif",
                    "/union/{$id}.png",
                    "/union/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "union", $id);
                //修改路径
                if($return['status'] == 1){
                    M("Union")->where(['id'=>$id])->save(['img'=>$return['url']]);
                }
            }
        }
        if (false !== $rs) {
            //成功提示
            $this->success('保存成功!');
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
            "/union/{$id}.jpg",
            "/union/{$id}.gif",
            "/union/{$id}.png",
            "/union/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("union")->where(['id'=>$id])->save(['img'=>NULL])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }
    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("union");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
                    //删除图片
                    $fileArr = array(
                        "/union/{$id}.jpg",
                        "/union/{$id}.gif",
                        "/union/{$id}.png",
                        "/union/{$id}.swf",
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