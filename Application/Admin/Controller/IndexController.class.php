<?php

class IndexController extends CommonController {

    // 框架首页
    public function index() {
        if (isset ( $_SESSION [C ( 'USER_AUTH_KEY' )] )) {
            //囚鸟先生 start
            $volist=M("GroupClass")->where(array('status'=>1))->order("sort asc")->select();
            
            //囚鸟先生 end
            //显示菜单项
            $map['level'] = 2;
            if($_SESSION['authId'] != 1)
            {
                $role_id = M('RoleUser')->where('user_id='.$_SESSION['authId'])->getField('role_id',true);
                $role_id = array_unique($role_id);
                $map['role_id'] = array('in',implode(",",$role_id));
                $node_id = M('Access')->where($map)->getField('node_id',true);
                unset($map['role_id']);
                $map['id'] = array('in',implode(",",array_unique($node_id)));
            }
            $map['status']=1;
            $node = M('Node')->where($map)->field('id,name,group_id,title')->order('sort asc')->select();
            foreach ($node as $key=>$val)
            {
                $val['access'] =   1;
                $menu[$val['group_id']][$key]  = $val;
            }
            //缓存菜单访问
            //$_SESSION['menu'.$_SESSION[C('USER_AUTH_KEY')]] =   $menu;

            if (! empty ( $_GET ['tag'] )) {
                $this->assign ( 'menuTag', $_GET ['tag'] );
            }
            //luz start
            $groups=M("Group")->where(array('group_menu'=>"{$volist[0]['menu']}",'status'=>"1"))->order("sort asc")->select();
            $this->assign("groups",$groups);
            $group = array();
            foreach ($volist as $val)
            {
                $group[$val['menu']] = $groups=M("Group")->where(array('group_menu'=>"{$val['menu']}",'status'=>"1"))->order("sort asc")->select();
            }
            //luz end
            $this->assign ( 'menu', $menu );
            $PublishClass = M('PublishClass')->where(['status'=>1])->field("id,pid,name,level")->select();
            $PublishClass = Think\Tool\Tool::getTree($PublishClass, $pid = 0, $col_id = 'id', $col_pid = 'pid', $col_cid = 'level');
            $this->assign('PublishClass',$PublishClass);
        }

        // C ( 'SHOW_RUN_TIME', false ); // 运行时间显示
        // C ( 'SHOW_PAGE_TRACE', false );
        if(getUserPower()['is_show_index'] == 1){
            $this->getIndexShow();
        }
        //去掉没有任何列表权限的分组
        foreach ($group as $k => $v) {
        	foreach ($v as $kk => $vv) {
        		foreach ($menu as $m => $n) {
        			if($vv['id'] == $m){
        				$group[$k][$kk]['num'][] = $n;
        			}
        		}
        		if($group[$k][$kk]['num'] == NULL){
        			unset($group[$k][$kk]);
        		}
        	}
        }
        //去掉没有任何分组权限的导航
        foreach ($volist as $k => $v) {
        	foreach ($group as $kk => $vv) {
        		if($group[$v['menu']] == NULL){
        			unset($volist[$k]);
        		}
        	}
        }
        $this->volist=$volist;
        $this->assign("group",$group);
        $this->display ();
    }

	public function getIndexShow()
	{
		//近七天日期
		$date = [date('Y-m-d', strtotime('-6 days')), date('Y-m-d', strtotime('-5 days')), date('Y-m-d', strtotime('-4 days')), date('Y-m-d', strtotime('-3 days')), date('Y-m-d', strtotime('-2 days')), date('Y-m-d', strtotime('-1 days')), date('Y-m-d'), ];
		$days = [date('m-d', strtotime('-6 days')), date('m-d', strtotime('-5 days')), date('m-d', strtotime('-4 days')), date('m-d', strtotime('-3 days')), date('m-d', strtotime('-2 days')), date('m-d', strtotime('-1 days')), date('m-d'), ];
		if(!$sevenData = S('admin_sevenData'))
		{
			foreach ($date as $k => $v) 
			{
				$start = strtotime($v);
				$end   = strtotime($v) + 86399;
				//获取近七天注册数
				$register[] = M('FrontUser')->where(['user_type'=>1,'is_robot'=>0,'reg_time'=>['BETWEEN',[$start,$end]]])->count();

				//获取近七天充值数
				$accountLog = M('accountLog')->field("sum(change_num) coin")->where("log_type in(7,8) AND (log_time BETWEEN {$start} AND {$end})")->find();

				$recharge[] = $accountLog['coin'] ? : 0;
			}
			$sevenData['register'] = json_encode($register);
			$sevenData['recharge'] = json_encode($recharge);
			S('admin_sevenData',$sevenData,60);
		}
		$this->assign('register',$sevenData['register']);
		$this->assign('recharge',$sevenData['recharge']);
		$this->assign('days',json_encode($days));

		//当天真实用户竞猜数
		$blockTime = getBlockTime(1);
		if(!$gambleNum = S('admin_gambleNum'))
		{
			$gambleNum = M('FrontUser')->alias('f')->field('f.id')->join("LEFT JOIN qc_gamble g on f.id = g.user_id")->where("f.user_type = 1 AND f.is_robot = 0 AND g.create_time between {$blockTime['beginTime']} AND {$blockTime['endTime']}")->group('f.id')->select();

			$gambleNum = count($gambleNum);
			S('admin_gambleNum',$gambleNum,120);
		}
		$this->assign('gambleNum',$gambleNum);

		//当前可竞猜赛事场数   亚盘
	    if(!$gameData = S('admin_gameData'))
	    {
	    	$game = D('GambleHall')->matchList(1);
	    	$betting = D('GambleHall')->matchList(2);
	    	$gameData['gameNum'] = count($game[0]);
	    	$gameData['bettingNum'] = count($betting[0]);
	    	S('admin_gameData',$gameData,70);
	    }
		
		$this->assign('gameNum',$gameData['gameNum']);
		$this->assign('bettingNum',$gameData['bettingNum']);
		
		if(!$statistics = S('admin_statistics'))
		{
			//当前总余额、总可提金币
			$coinSumRes = M('FrontUser')->where(['is_robot'=>0,'user_type'=>1])->Field('SUM(coin+unable_coin) as balanceSum,SUM(coin) as coinSum')->find();
			$coinMsg = '当前正常用户总余额为： '.$coinSumRes['balanceSum'].' 金币，可提款余额为 '.$coinSumRes['coinSum'].' 金币。';
			$statistics['coinMsg'] = $coinMsg;

			//当前推荐被购买数
			$trueQuizSum = M('quizLog')->where("coin > 0")->Field('count(id) as quizSum,SUM(coin) as quizRradeCoin')->find();
			$statistics['trueQuizSum'] = $trueQuizSum;

			//当前充值用户数
			$userNum = M('accountLog')->where("log_type in(7,8)")->group('user_id')->select();
			//当前充值金额
			$userCoinNum = M('accountLog')->field("sum(change_num) userCoinNum")->where("log_type in(7,8)")->find();
			$UsercoinMsg = '当前充值总用户数为： '.count($userNum).' ，充值总金额 '.$userCoinNum['userCoinNum'].' 金币。';
			$statistics['UsercoinMsg'] = $UsercoinMsg;

			//当前消费用户数
			$conuserNum = M('accountLog')->where("log_type in(3,14)")->group('user_id')->select();
			//当前消费金额
			$conuserCoinNum = M('accountLog')->field("sum(change_num) userCoinNum")->where("log_type in(3,14)")->find();
			$conUsercoinMsg = '当前消费总用户数为： '.count($conuserNum).' ，消费总金额 '.$conuserCoinNum['userCoinNum'].' 金币。';
			$statistics['conUsercoinMsg'] = $conUsercoinMsg;
			S('admin_statistics',$statistics,80);
		}
		$this->assign('coinMsg',$statistics['coinMsg']);
		$this->assign('trueQuizSum',$statistics['trueQuizSum']);
		$this->assign('UsercoinMsg',$statistics['UsercoinMsg']);
		$this->assign('conUsercoinMsg',$statistics['conUsercoinMsg']);

		// if(!$gambleMsg = S('admin_gambleMsg'))
		// {
		// 	//当前正常用户赢平输场次数
		// 	$gamble = M('gamble')->alias('g')->join("LEFT JOIN qc_front_user f on f.id = g.user_id")
		// 			->field("result,count(1) as resultNum")
		// 			->where("f.is_robot = 0 and f.user_type = 1")
		// 			->group("result")
		// 			->select();
		// 	echo M('gamble')->_sql();
		// 	$win  = 0;
		// 	$draw = 0;
		// 	$lose = 0;
		// 	foreach ($gamble as $k => $v) {
		// 		switch ($v['result']) {
		// 			case   '1':
		// 			case '0.5': $win += $v['resultNum']; break;
		// 			case   '2': $draw += $v['resultNum']; break;
		// 			case  '-1':
		// 			case '-0.5': $lose += $v['resultNum']; break;
		// 		}
		// 	}
		// 	$gambleMsg = "当前正常用户竞猜：【 赢 {$win} 】；【 平 {$draw} 】；【 输 {$lose} 】场；";
		// 	S('admin_gambleMsg',$gambleMsg,100);
		// }
		// $this->assign('gambleMsg',$gambleMsg);

		if(!$accountLogMsg = S('admin_accountLogMsg'))
		{
			//当前的用户提款申请中，待汇款，成功，冻结，不通过申请数、   金额
			$accountLog = M('accountLog')
					->field("log_status,count(1) as statusNum,SUM(change_num) as num")
					->where("log_type = 2")
					->group("log_status")
					->select();
			$accountLogMsg = "当前用户提款：";
			foreach ($accountLog as $k => $v) {
				switch ($v['log_status']) {
					case '0': $accountLogMsg .= "【申请中数 {$v['statusNum']} ，金额 {$v['num']}】"; break;
					case '1': $accountLogMsg .= "【成功数   {$v['statusNum']} ，金额 {$v['num']}】";   break;
					case '2': $accountLogMsg .= "【不通过数 {$v['statusNum']} ，金额 {$v['num']}】"; break;
					case '3': $accountLogMsg .= "【待汇款数 {$v['statusNum']} ，金额 {$v['num']}】"; break;
					case '4': $accountLogMsg .= "【驳回数   {$v['statusNum']} ，金额 {$v['num']}】";   break;
					case '5': $accountLogMsg .= "【冻结数   {$v['statusNum']} ，金额 {$v['num']}】";   break;
				}
			}
			S('admin_accountLogMsg',$accountLogMsg,30);
		}
		$this->assign('accountLogMsg',$accountLogMsg);

		//当日文章数
		$today = strtotime(date(Ymd));
		if(!$newsMsg = S('admin_newsMsg')){
			$news = M('PublishList p')
				->join("LEFT JOIN qc_comment c on c.publish_id = p.id")
				->field("count(p.id) as newsNum,SUM(p.click_number) clickNum,count(c.id) as cNum")->where("p.status=1 AND p.update_time between ".$today." AND ".array_sum([$today,86399])."")->find();
				//echo  M('PublishList p')->_sql();
			$newsMsg = "当日最新的文章数：{$news['newsNum']}， 阅读数：{$news['clickNum']}，评论数：{$news['cNum']}";
			S('admin_newsMsg',$newsMsg,60);
		}
		$this->assign('newsMsg',$newsMsg);
	}

}