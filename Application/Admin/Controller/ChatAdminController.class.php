<?php
use Think\Tool\Tool;
class ChatAdminController extends CommonController {

    public function index(){
        //列表过滤器，生成查询Map对象
        $map = $this->_search('ChatAdmin');
        //手动获取列表
        $list = $this->_list(CM("ChatAdmin"), $map, 'create_time', false);

        $this->assign('list', $list);
        $this->display();
    }

    public function add(){
        $this->display();
    }

    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save(){
        $id = I('id', 'int');
        $_POST['create_time'] = time();
        $frontUser = M('FrontUser')->where(['username' => ['EQ',$_POST['username']]])->find();
        if(!$frontUser){
            $this->error('会员不存在');
            exit;
        }

        $_POST['user_id'] = $frontUser['id'];
        $_POST['nick_name'] = $frontUser['nick_name'];

        $model = D('ChatAdmin');
        if($model->where(['username' => ['EQ', $_POST['username']]])->find()){
            $this->error('管理员已经存在');
            exit;
        }
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

    public function edit() {
        $id = I('id');
        $vo = M("ChatAdmin")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign('vo', $vo);
        $this->display("add");
    }

    //删除单个
    public function delete() {
        //删除指定记录
        $model = M("ChatAdmin");
        if (!empty($model)) {
            $id = $_REQUEST ['id'];
            if (isset($id)) {
                $condition = array('id' => $id);
                if (false !== $model->where($condition)->delete()) {
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
        $model = M("ChatAdmin");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $idsArr = explode(',', $ids);
                $condition = array ("id" => array ('in',$idsArr));
                if (false !== $model->where($condition)->delete()) {
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