<?php
/**
 * 欧冠专题管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-4-11
 */
class SpecialController extends CommonController {
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search("Special");
        $game_state = I('game_state');
        if($game_state != ''){
            $map['game_state'] = $game_state;
        }
        //手动获取列表
        $list = $this->_list(D("SpecialView"), $map );
        $list = HandleGamble($list);
        $this->assign('list', $list);
        $this->display();
    }

    function edit() {
        $id = I("id");
        $vo = M('Special')->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign ('vo', $vo);
        $this->display("add");
    }
    /**
     * 保存/修改记录
     *
     * @return #
    */
    public function save(){
        $id = I('id', 'int');
        $model = D('special');
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        $model->add_time = time();
        if (empty($id)) {
            $game = M('Special')->where(['game_id'=>I('game_id'),'type'=>I('type')])->find();
            if($game){
                $this->error('该赛程id与类型已存在！');
            }
            //为新增
            $rs = $model->add();
        }else{
            $game = M('Special')->where(['game_id'=>I('game_id'),'type'=>I('type'),'id'=>['neq',$id]])->find();
            if($game){
                $this->error('该赛程id与类型已存在！');
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
}