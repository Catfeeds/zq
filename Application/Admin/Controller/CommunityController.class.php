<?php
/**
 * 圈子分类控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-6-30
 */
use Think\Tool\Tool;
class CommunityController extends CommonController
{
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //手动指定显示条数
        $_REQUEST ['numPerPage'] = 1000;
    }
    /**
     * 分类列表
     * @return string
    */
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search ("Community");

        //获取列表
        $list = $this->_list ( CM('Community'), $map);
        foreach ($list as $k => $v) {
            $list[$k]['titleimg']   = Tool::imagesReplace($v['head_img']);
            $list[$k]['background'] = Tool::imagesReplace($v['background']);
        }

        if($map['status']==NULL && $map['name']==NULL){
            $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        }
        $this->assign ('list', $list);
        $this->display();
    }

    /**
     * 编辑指定记录
     *
     * @return string
     *
    */
    function edit() {

        $id = Tool::request("id");
        $vo = M('Community')->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        //获取所有记录
        $list = M('Community')->where(['id'=>['neq',$vo['id']]])->select();
        //引用Tree类
        $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('list', $list);
        $vo['img'] = Tool::imagesReplace($vo['head_img']);
        $vo['background'] = Tool::imagesReplace($vo['background']);
        $this->assign ('vo', $vo);
        $this->display();
    }
    /**
     * 添加记录
     *
     * @return string
     *
    */
    function add() {
        //获取所有记录
        $list = M('Community')->select();
        //引用Tree类
        $list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('list', $list);
        $this->display('edit');
    }

    /**
     * 添加/编辑分类表数据
     * @return #
    */
    public function save()
    {
        //是否为修改标志
        $id = I('id');
        //检验数据
        $model = D('community');
        $validate = $model->create();
        if($validate['level'] > 3){
            $this->error('只能添加3层分类哦!');
        }
        //判断数据对象是否通过
        if( !$validate ){
            //返回错误提示
            $this->error($model->getError());
        }
        if (empty($id)) {
            //判断圈子名称是否存在
            $nameRes = M('Community')->where(['name'=>I('name')])->find();
            if($nameRes) $this->error('此圈子名称已存在');
            if (empty($_FILES['fileInput2']['tmp_name'])) $this->error('请上传背景他图片');
            //为新增
            $rs = $model->add();
            //上传图片
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput", "community", $rs);
                if($return['status'] == 1)
                    M("community")->where(['id'=>$rs])->save(['head_img'=>$return['url']]);
            }
            
            //上传背景图片
            if (!empty($_FILES['fileInput2']['tmp_name'])) {
                $return = D('Uploads')->uploadImg("fileInput2", "community_back", $rs);
                if($return['status'] == 1)
                    M("community")->where(['id'=>$rs])->save(['background'=>$return['url']]);
            }
            else
            {
                $this->error('请上传背景他图片');
            }
        }else{
            //判断圈子名称是否存在
            $nameRes = M('Community')->where(['name'=>I('name'),'id'=>['neq',$id]])->find();
            if ($nameRes) $this->error('此圈子名称已存在');
            if (empty($_FILES['fileInput2']['tmp_name']))
            {
                $bg_img = M('community')->where(['id'=>$id])->getField('background');
                if (empty($bg_img)) $this->error('请上传背景他图片');
            }


            //为修改
            $rs = $model->save();
            //是否有上传
            if (!empty($_FILES['fileInput']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/community/{$id}.jpg",
                    "/community/{$id}.gif",
                    "/community/{$id}.png",
                    "/community/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput", "community", $id);
                //修改路径
                if($return['status'] == 1)
                    M("community")->where(['id'=>$id])->save(['head_img'=>$return['url']]);
            }
            //是否有上传背景图片
            if (!empty($_FILES['fileInput2']['tmp_name'])) {
                //先删除原来图片
                $fileArr = array(
                    "/community_back/{$id}.jpg",
                    "/community_back/{$id}.gif",
                    "/community_back/{$id}.png",
                    "/community_back/{$id}.swf",
                );
                D('Uploads')->deleteFile($fileArr);
                //上传图片
                $return = D('Uploads')->uploadImg("fileInput2", "community_back", $id);
                //修改路径
                if($return['status'] == 1)
                    M("community")->where(['id'=>$id])->save(['background'=>$return['url']]);
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

    /**
     * 删除指定记录
     *
     * @return string
     *
    */
    public function del() {
        //只允许单个删除
        $id = $_REQUEST['id'];
        if (isset ( $id )) {
            $rs = M('community')->where(['pid'=>$id])->find();
            if ($rs){
                $this->error ( '请先删除下级分类' );
            } else {
                if (M('community')->where(['id'=>$id])->delete()){
                    $fileArr = array(
                        "/community/{$id}.jpg",
                        "/community/{$id}.gif",
                        "/community/{$id}.png",
                        "/community/{$id}.swf",
                        "/community_back/{$id}.jpg",
                        "/community_back/{$id}.gif",
                        "/community_back/{$id}.png",
                        "/community_back/{$id}.swf",
                    );
                    //执行删除
                    $return = D('Uploads')->deleteFile($fileArr);
                    $this->success ( '删除成功' );
                } else {
                    $this->error ( '删除失败' );
                }
            }
        } else {
            $this->error ( '非法操作' );
        }
    }

    //异步删除图片
    public function delPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/community/{$id}.jpg",
            "/community/{$id}.gif",
            "/community/{$id}.png",
            "/community/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("community")->where(['id'=>$id])->save(['head_img'=>NULL])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }

    //异步删除背景图片
    public function delBackPic(){
        $id = I('id');
        if(empty($id)){
            $this->error('参数错误!');
        }
        $fileArr = array(
            "/community_back/{$id}.jpg",
            "/community_back/{$id}.gif",
            "/community_back/{$id}.png",
            "/community_back/{$id}.swf",
        );
        //执行删除
        $return = D('Uploads')->deleteFile($fileArr);
        if($return['status'] == 1){
            //删除路径
            if(M("community")->where(['id'=>$id])->save(['background'=>NULL])){
                $this->success('删除成功！');
            }else{
                $this->error('删除失败！');
            }
        }else{
            $this->error('删除失败！');
        }
    }

}



?>