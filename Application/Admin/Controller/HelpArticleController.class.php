<?php
/**
 * 帮助中心文章控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2016-1-20
 */
use Think\Tool\Tool;
class HelpArticleController extends CommonController {
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {
        parent::_initialize();
        //获取分类
		$HelpClass = M('HelpClass')->where("status=1")->select();
        //引用Tree类
        $HelpClass = Tool::getTree($HelpClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
        $this->assign ('HelpClass', $HelpClass);
    }

    /**
     * Index页显示
     *
     */
    public function index() {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('HelpArticle');
        $class_id = I('class_id');
        if(!empty($class_id)){
            //获取问题分类
            $HelpClass = M('HelpClass')->where("status=1")->select();
            //无限级分类中获取一个分类下的所有分类的ID,包括查找的父ID
            $HelpClassIds = Tool::getAllSubCategoriesID( $HelpClass, $class_id );
            $map['class_id'] = array( 'in', $HelpClassIds );
        }
        //手动获取列表
        $list = $this->_list(CM("HelpArticle"), $map);
        foreach ($list as $k => $v) {
            $list[$k]['class_name'] = M('HelpClass')->where(['id'=>$v['class_id']])->getField('name');
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function add() {
        $this->display();
    }

    public function edit() {
        $id = I('id');
        $vo = M("HelpArticle")->find($id);
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
		$model = D('HelpArticle');
		if (false === $model->create()) {
        	$this->error($model->getError());
        }
		if (empty($id)) {
			//为新增
            $model->add_time = time();
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