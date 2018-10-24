<?php
/**
 * 奥运赛程控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-7-18
 */
class OlympicsController extends CommonController {
    /**
     * Index页显示
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('Olympics');

        //手动获取列表
        $list = $this->_list(CM("Olympics"), $map);
        $this->assign('list', $list);
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("Olympics")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
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
        $model = D('Olympics');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if (empty($id)) {
            $model->add_time = time();
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

    //修改是否直播
    public function saveisVideo(){
        $where['id'] = $_REQUEST['id'];
        unset($_REQUEST['id']);
        $rs = M('Olympics')->where($where)->save($_REQUEST);
        if($rs !== false){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

}