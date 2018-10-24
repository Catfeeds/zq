<?php
/**
 * 奥运竞猜记录管理
 * 
 * @author dengweijun <406516482@qq.com>
 * @since  2016-7-5
 */

class OlympicRecordController extends CommonController{
	/**
     * 竞猜列表
     * @return string     
    */
    public function index()
	{
		//列表过滤器，生成查询Map对象
		$map = $this->_search ("OlympicRecordView");

		//时间查询
		if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
		    if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
		        $startTime = strtotime($_REQUEST ['startTime']);
		        $endTime   = strtotime($_REQUEST ['endTime'])+86400;
		        $map['create_time'] = array('BETWEEN',array($startTime,$endTime));
		    } elseif (!empty($_REQUEST['startTime'])) {
		        $strtotime = strtotime($_REQUEST ['startTime']);
		        $map['create_time'] = array('EGT',$strtotime);
		    } elseif (!empty($_REQUEST['endTime'])) {
		        $endTime = strtotime($_REQUEST['endTime'])+86400;
		        $map['create_time'] = array('ELT',$endTime);
		    }
		}
		if(!empty($_POST['title']))
		{
			$map['title'] = ['like','%'.$_POST['title'].'%'];
		}
		if(!empty($_POST['union_name']))
		{
			$map['union_name'] = ['like','%'.$_POST['union_name'].'%'];
		}

        //是否机器人竞猜记录筛选
        $is_robot = I('is_robot');
        if($is_robot != ''){
            if($is_robot == 1){
                $map['is_robot'] = 1;
            }else{
                $map['is_robot'] = ['neq',1];
            }
        }
		//获取列表
		$list = $this->_list ( CM('OlympicRecordView'), $map);
		foreach ($list as $k => $v) {
			//获取选项
			$list[$k]['question'] = M("OlympicQuiz")->where(['pid'=>$v['quiz_id']])->select();
            if($v['answer'] > 0) //结果预览
            {
                $result = $v['answer_id'] == $v['answer'] ? '1' : '-1';
                $list[$k]['show_result'] = $result;
            }
		}
        //正常用户参与人数
        $map['is_robot'] = 0;
        $userCount = D('OlympicRecordView')
                ->where($map)
                ->group("o.user_id")->select();
        $this->assign('userCount',count($userCount));
		$this->assign('list',$list);
        $this->display();
    }

    //修改答案
    public function saveAnswer(){
        if(IS_POST){
            $id = I('id');
            $answer_id = I('answer_id');
            $rs = M('OlympicRecord')->where(['id'=>$id])->save(['answer_id'=>$answer_id]);
            if($rs){
                $this->success("修改成功");
            }else{
                $this->error("你没有修改哦！");
            }
        }else{
            $id = I('id');
            $vo = D('OlympicRecordView')->find($id);
            if(!$vo){
                $this->error("参数错误");
            }
            $vo['question'] = M("OlympicQuiz")->where(['pid'=>$vo['quiz_id']])->select();
            $this->assign('vo',$vo);
            $this->display();
        }
    }

    //排行列表
    public function rank()
    {
    	//列表过滤器，生成查询Map对象
    	$map = $this->_search ("OlympicRankView");

    	//用户查找
    	if(!empty($_POST['FrontUser_id'])) {
    	    $userIdArr = explode(',', $_POST['FrontUser_id']);
    	    $map['user_id'] = ['IN',$userIdArr];
    	}
        
        //是否机器人竞猜记录筛选
        $is_robot = I('is_robot');
        if($is_robot != ''){
            if($is_robot == 1){
                $map['is_robot'] = 1;
            }else{
                $map['is_robot'] = ['neq',1];
            }
        }
    	//获取列表
    	$list = $this->_list ( CM('OlympicRankView'), $map,'year_date desc,ranking asc',NULL);

    	$this->assign('list',$list);
    	$this->display();
    }

    //奥运排行结算
    public function breakRanking()
    {
    	//找出所有用户
    	$FrontUser = M("FrontUser")->where(array('status'=>1))->field('id as user_id')->order("id asc")->select();
    	foreach ($FrontUser as $k => $v) {
    	    $gameArray = M('OlympicRecord')->where("user_id = {$v['user_id']} and result <> 0 and FROM_UNIXTIME(create_time,'%Y') =".date('Y'))->select();
    	    //去掉没有参与竞猜的用户
    	    if(!$gameArray){
    	    	unset($FrontUser[$k]);
    	    }else{
    	    	$FrontUser[$k]['gameCount'] = count($gameArray);
    	    	$FrontUser[$k]['gameArray'] = $gameArray;
    	    }
    	}
    	if(empty($FrontUser)){
    	    $this->error('没有用户上榜');
    	}
    	//获取胜率和详细记录
    	foreach ($FrontUser as $k => $v) {
    	    $winning = $this->dealWinning($v['gameArray']);
    	    $FrontUser[$k]['winrate']    = $winning['winrate'];
    	    $FrontUser[$k]['win']        = $winning['win'];
    	    $FrontUser[$k]['transport']  = $winning['transport'];
    	    $FrontUser[$k]['pointCount'] = $winning['pointCount'];
    	    $FrontUser[$k]['year_date']  = date("Y");
    	    unset($FrontUser[$k]['gameArray']);
    	}
    	$pointCount = array();
    	$winrate    = array();
    	//对数组进行排序,先按积分,再按胜率
    	foreach ($FrontUser as $v) {
    	    $pointCount[] = $v['pointCount'];
    	    $winrate[]    = $v['winrate'];
    	}
    	array_multisort($pointCount, SORT_DESC,$winrate, SORT_DESC, $FrontUser);
    	foreach ($FrontUser as $k => $v) {
    	    //名次
    	    $FrontUser[$k]['ranking'] = $k+1;
    	}
    	//删除奥运排行，再添加。
    	M('OlympicRank')->where(['year_date'=>date('Y')])->delete();
    	if(M('OlympicRank')->addAll($FrontUser))
    		$this->success('刷新成功！');
    }

    //计算胜率
    public function dealWinning($array)
    {
        $win        = 0;
        $transport  = 0;
        $pointCount = 0;
        foreach ($array as $k => $v) 
        {
            if( $v['result'] == '1' ) $win++;
            if( $v['result'] == '-1') $transport++;
            if( $v['earn_point'] > 0) $pointCount += $v['earn_point'];
        }
        //胜率赢除以总场数
        $winrate = round($win/count($array)*100);

        return array(
                "winrate"    =>  $winrate,
                'win'        =>  $win,
                'transport'  =>  $transport,
                'pointCount' =>  $pointCount,
            );
    }

    public function runQuiz()
    {
        //获取前30个机器人
        $robot = M('FrontUser')->where(['is_robot'=>1,'status'=>1])->field('id')->order('id asc')->limit(30)->select();
        foreach ($robot as $k => $v) {
            $robotId[] = $v['id'];
        }
        //获取未结算的机器人竞猜
        $quiz = M('OlympicRecord r')
                ->join("LEFT JOIN qc_olympic_quiz q on r.quiz_id = q.id")
                ->field("r.id,r.answer_id,q.answer")
                ->where(['r.result'=>0,'r.user_id'=>['in',$robotId],'q.answer'=>['gt',0]])
                ->select();
        //执行修改
        $num = 0;
        foreach ($quiz as $k => $v) {
            if($v['answer'] != $v['answer_id']){
                $num ++ ;
                M('OlympicRecord')->where(['id'=>$v['id']])->save(['answer_id'=>$v['answer']]);
            }
        }
        echo '修改了 <span style="color:red;font-size:15px;">'.$num.'</span> 条竞猜数据';
    }
    
    //奥运竞猜结算
	public function runResult()
	{
		//获取可结算的竞猜
	    $game = M('OlympicRecord r')
	    		->field("r.id,r.user_id,r.answer_id,r.odds,r.vote_point,q.answer")
	    		->join("LEFT JOIN qc_olympic_quiz q on q.id = r.quiz_id")
	    		->where("r.result = 0 and q.answer > 0 and q.status = 1")
	    		->order("r.id asc")
	    		->select();

	    if(!$game) $this->error("没有可结算的竞猜噢！");

	    $num = 0;
	    foreach ($game as $v)
	    {
	    	//判断输赢
	    	$result = $v['answer_id'] == $v['answer'] ? '1' : '-1';

	        //根据输赢计算获得积分
	        $earn_point = $result == '1' ? ceil($v['odds'] * $v['vote_point'] * $result) : 0;

	        M()->startTrans(); //开始事务

	        //更新竞猜记录状态、赢取的积分
	        $rs  = M('OlympicRecord')->where(['id'=>$v['id']])->save(['result'=>$result,'earn_point' => $earn_point]);

	        if ( $earn_point > 0 )
	        {
	        	//获取原有积分
	        	$point  = M('FrontUser')->master(true)->where(['id'=>$v['user_id']])->getField('point');

	        	//给用户增加积分
	        	$rs2 = M('FrontUser')->where(['id'=>$v['user_id']])->setInc('point',$earn_point);

	            //增加积分记录
	            $rs3 = M('PointLog')->add([
	                'user_id'     => $v['user_id'],
	                'log_time'    => time(),
	                'log_type'    => 1,
	                'change_num'  => $earn_point,
	                'total_point' => $point + $earn_point,
	                'desc'        => '奥运竞猜'
	            ]);
	        }
	        else
	        {
	            $rs2 = $rs3 = true;
	        }

	        if ( $rs && $rs2 && $rs3 ) {
	        	$num ++;
	        	M()->commit();
	        }
	        else
	        {
	        	M()->rollback();
	        }
	    }
	    echo '结算了 <span style="color:red;font-size:15px;">'.$num.'</span> 条竞猜数据';
	}

}
?>