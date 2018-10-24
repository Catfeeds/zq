<?php
/**
 * 欧洲杯赛事管理控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-5-4
 */
class EuropeanCupController extends CommonController {
    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search("gameFbinfo");
        //手动获取列表
        $map['years'] = '2014-2016';
        $map['union_id'] = '67';
        //获取类型
        $runno = M('gameFbinfo g')->join("LEFT JOIN qc_run_fb r ON g.runno = r.id")->where(['g.years'=>'2014-2016','g.union_id'=>67])->field('g.runno,r.run_name')->group('runno')->select();
        $this->assign('runno', $runno);
        $list = $this->_list(D("gameFbinfo"), $map ,'game_state desc,gtime asc',NULL);
        $list = HandleGamble($list);

        $this->assign('list', $list);
        $this->display();
    }

    //修改是否竞猜
    public function saveIsGamble(){
        $where['id'] = $_REQUEST['id'];
        unset($_REQUEST['id']);
        $rs = M('gameFbinfo')->where($where)->save($_REQUEST);
        if($rs !== false){
            $this->success('保存成功');
        }else{
            $this->error('保存失败');
        }
    }

    public function EuroScorer(){
        //列表过滤器，生成查询Map对象
        $map = $this->_search("EuroScorer");
        $list = $this->_list(CM("EuroScorer"), $map);
        $this->assign('list', $list);
        $this->display("scorer_index");
    }

    public function EuroIntegral(){
        //列表过滤器，生成查询Map对象
        $map = $this->_search("EuroIntegral");
        $list = $this->_list(CM("EuroIntegral"), $map);
        $this->assign('list', $list);
        $this->display("integral_index");
    }

    public function euro_edit(){
        $id = I('id');
        $type = I('type');
        if($type == 1){
            $vo = M('EuroScorer')->find($id);
            $this->assign('vo', $vo);
            $this->display("scorer_edit");
        }else{
            $vo = M('EuroIntegral')->find($id);
            $this->assign('vo', $vo);
            $this->display("integral_edit");
        }
    }

    public function euro_save(){
        $id = I('id');
        $type = I('type');
        $model = $type == 1 ? CM("EuroScorer") : CM("EuroIntegral");
        if (false === $model->create()) {
            $this->error($model->getError());
        }
        if(empty($id)){
            //新增
            $rs = $model->add();
        }else{
            //修改
            $rs = $model->save();
        }
        if($rs){
            $this->success('保存成功！');
        }else{
            $this->error('保存失败！');
        }
    }

    public function euro_del(){
        $id = $_REQUEST['id'];
        $type = $_REQUEST['type'];
        $model = $type == 1 ? CM("EuroScorer") : CM("EuroIntegral");
        if (false !== $model->where(['id'=>$id])->delete()) {
            $this->success('删除成功！');
        } else {
            $this->error('删除失败！');
        }
    }
}