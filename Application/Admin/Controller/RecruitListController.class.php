<?php
/**
 * 招聘列表控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-2-17
 */
class RecruitListController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
		$RecruitClass = M('RecruitClass')->where(array('status'=>1))->select();
		$this->assign('RecruitClass', $RecruitClass);
    }
    public function edit(){
        $id = I('id');
        $vo = M("RecruitList")->find($id);
        if (!$vo){
            $this->error('参数错误');
        }
        $this->assign('vo', $vo);
        $this->display('add');
    }
    /**
     * 保存/修改记录
	 *
     * @return #
    */
    public function save(){
		$id = I('id', 'int');
		$model = D('RecruitList');
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
            $this->success('保存成功!',cookie('_currentUrl_'),'',true);
        } else {
            //错误提示
            $this->error('编辑失败!');
        }
	}
}