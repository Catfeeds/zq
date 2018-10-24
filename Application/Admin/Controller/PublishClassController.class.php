<?php
/**
 * 资讯分类控制器
 *
 * @author dengweijun <406516482@qq.com>
 *
 * @since  2015-11-27
 */
use Think\Tool\Tool;
class PublishClassController extends CommonController {
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
		$map = $this->_search ("PublishClass");
		//获取列表
		$list = $this->_list ( CM('PublishClass'), $map);
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
		$vo = M('PublishClass')->find($id);
		if (!$vo){
			$this->error('参数错误');
		}
		//获取所有记录
		$list = M('PublishClass')->where(['id'=>['neq',$vo['id']]])->select();
        $this->selectData($id,1);
		//引用Tree类
		$list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
		$this->assign ('list', $list);
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
		$list = M('PublishClass')->select();
		//引用Tree类
		$list = Tool::getTree($list, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
		$this->assign ('list', $list);
		$this->assign ('add', $add);
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
		$model = D('PublishClass');
		$validate = $model->create();
		if($validate['level'] > 3){
			$this->error('只能添加3层分类哦!');
		}
		$class_id = $this->getClassId();
        $model->pid = $class_id;
        $class = getPublishClass(0);
        if($class_id === 0)
        {
            $model->level = 1;
        }else if($class[$class_id]['level'] == 1){
            $model->level = 2;
        }else{
            $model->level = 3;
        }
		//判断数据对象是否通过
		if( !$validate ){
			//返回错误提示
			$this->error($model->getError());
		}
		if (!empty($id)){
			//为修改
			$rs = $model->save();
			if (!is_bool($rs)){
				$rs = true;
			}
		} else {
			//新增
			$rs = $model->add();
		}
		if (false !== $rs) {
			S('cache_publish_class',null);
            //成功提示
            $this->success('保存成功!',cookie('_currentUrl_'));
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
			$rs = M('PublishClass')->where(['pid'=>$id])->find();
			if ($rs){
				$this->error ( '请先删除下级分类' );
			} else {
				if (M('PublishClass')->where(['id'=>$id])->delete()){
					$this->success ( '删除成功' );
				} else {
					$this->error ( '删除失败' );
				}
			}
		} else {
			$this->error ( '非法操作' );
		}
	}
}