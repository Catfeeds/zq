<?php
/**
 * 奥运竞猜设置管理
 * 
 * @author dengweijun <406516482@qq.com>
 * @since  2016-7-4
 */

class OlympicQuizController extends CommonController{
	/**
     * 竞猜列表
     * @return string     
    */
    public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ("OlympicQuiz");
		$map['pid'] = 0;
		$answer = I('answer');
		//赛选是否已经出答案
		if($answer == 1)
		{
			$map['answer'] = ['gt',0];
		}elseif($answer == 2){
			$map['answer'] = ['eq',0];
		}
		//获取列表
		$list = $this->_list ( CM('OlympicQuiz'), $map);
		foreach ($list as $k => $v) {
			//获取选项
			$list[$k]['question'] = M("OlympicQuiz")->where(['pid'=>$v['id']])->select();
		}

		$this->assign('list',$list);
        $this->display();
    }
    //运行机器人竞猜
    public function robot_quiz()
    {
    	//获取前30个机器人
    	$robot = M('FrontUser')->where(['is_robot'=>1,'status'=>1])->field('id')->order('id asc')->limit(30)->select();

		foreach ($robot as $k => $v) {
			$quiz = $this->getQuiz($v['id']);
			M('OlympicRecord')->addAll($quiz);
		}
		$this->success("发布成功！");
    }
    //获取随机竞猜
    public function getQuiz($user_id)
    {
    	//可竞猜的
    	$game = M('OlympicQuiz')->where(['status'=>1,'pid'=>0,'answer'=>0])->order("id desc")->select();
    	foreach ($game as $k => $v) {
    		$question = M("OlympicQuiz")->where(['pid'=>$v['id']])->field('id,odds')->select();
    		$randQuiz = $question[array_rand($question)];
    		$quiz['quiz_id']     = $v['id'];
    		$quiz['answer_id']   = $randQuiz['id'];
    		$quiz['create_time'] = time();
    		$quiz['odds']        = $randQuiz['odds'];
    		$quiz['vote_point']  = $v['point'];
    		$quiz['user_id']     = $user_id;
    		$quizArr[] = $quiz;
    	}
    	return $quizArr;
    }

    /**
    * 批量禁用启用
    * @access 
    * @return string
    */
    public function saveAll(){
        //删除指定记录
        $ids = isset($_POST['id']) ? $_POST['id'] : null;
        if ($ids) {
        	$status = $_REQUEST['status'];
            $idsArr = explode(',', $ids);
            $condition = array ("id" => array ('in',$idsArr));
            $rs = M('OlympicQuiz')->where($condition)->save(['status'=>$status]);
            if($rs !== false){
                $this->success('设置成功');
            }else{
                $this->error('设置失败');
            }
        } else {
            $this->error('非法操作');
        }
    }


    /**
	 *
     * 编辑指定记录
	 *
     * @return string
	 *
    */
	function edit() {	
		$id = I('id');
		//获取课程内容信息
		$vo = M('OlympicQuiz')->find($id);
		if (!$vo){
			$this->error('参数错误');
		}	
		//查出所有选项
		$question = M('OlympicQuiz')->where(array('pid'=>$vo['id']))->select();
		$this->assign ('question',$question);
		$this->assign ('vo', $vo);
		$this->display ('add');
	}
	/**
     * 保存记录
	 *
     * @return #
    */
    public function save()
	{
		$id     = I('id');
		$content= I('content');
		$odds   = I('odds');
		$answer = I('answer');
		$ids    = I('ids');
		//检验数据
		$model = D('OlympicQuiz');
		//判断数据对象是否通过
		if( !$model->create() ){
			$this->error($model->getError());
		}
		$model->game_time = strtotime($_POST['game_time']);
		M()->startTrans();
		if (!empty($id)){
			//为修改
			$rs = $model->save();
			if (!is_bool($rs)){
				$rs = $id;
			}
		} else {
			//新增
			$model->add_time = time();
			$rs = $model->add();
		}
		if($rs){
			$isTrue = '';
			foreach ($content as $k => $v) {
				//组装选项数据
				$data['pid']   = $rs;
				$data['title'] = $v;
				$data['odds']  = $odds[$k];
				if (empty($ids[$k])){
					//新增
					$pid = $model->add($data);
				} else {
					//修改
					$model->where("id={$ids[$k]}")->save($data);
					$pid = $ids[$k];
				}
				if($pid && $answer != ''){
					//答案选项ID
					if($k == $answer){
						$isTrue = $pid;
					}
				}
			}
			if($isTrue != ''){
				//更新绑定答案
				$datas['id'] = $rs;
				$datas['answer'] = $isTrue;
				$rs2 = $model->save($datas);
			}else{
				$rs2 = true;
			}
			if($rs && $rs2){
				M()->commit();
				$this->success("保存成功");
			} else {
				M()->rollback();
				$this->error('保存失败');
			}
		}
	}
	/**
     * 删除指定记录
	 *
     * @return string
	 *
    */
	public function del() {
		$id = $_REQUEST['id'];
		if (isset ( $id )) {
			//根据id查出对应的选项id
			$pid = M('OlympicQuiz')->where(array('pid'=>$id))->field('id')->select();
			//拼接要删除的所有id
			foreach ($pid as $key => $value) {
				$id.=",".$value['id'];
			}
			$condition = array ("id" => array ('in', explode ( ',', $id ) ) );
			if (false !== M('OlympicQuiz')->where ( $condition )->delete ()) {
				$this->success ( '删除成功' );
			} else {
				$this->error ( '删除失败' );
			}	
		} else {
			$this->error ( '非法操作' );
		}
	}

	/**
     * ajax删除选项
     * @return string
	 *
    */
	public function delOption() {
		$id = I('id');
		$pid = I('pid');
		if (empty($id) || empty($pid)){
			$this->error("参数错误！");
		}
		$answer = M('OlympicQuiz')->where(['id'=>$pid])->getField('answer');
		if($answer == $id)
		{
			//选项被删，修改答案
			M('OlympicQuiz')->where(['id'=>$pid])->save(['answer'=>0]);
		}
		//执行删除
		$rs = M('OlympicQuiz')->delete($id);
		if ($rs){
			echo 1;
		} else {
			echo 0;
		}
	}

}
?>