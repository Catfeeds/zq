<?php
/**
 * 机器人竞猜模型类
 * @author dengweijun <406516482@qq.com> 2016.11.4
 */

use Think\Model;
class RobotModel extends Model
{
	/**
	* 根据指定赛程随机发布15到20条竞猜
	* @param $game        赛程 
	* @param $play_type   玩法
	* @param $Lv          等级
	* @param $RobotIdArr  机器人数组
	* @param $gameType    赛事类型
	*/
	public function dogamble($game,$play_type,$RobotIdArr,$gameType,$betting=1)
	{  
		if(!$RobotIdArr) {
			$RobotIdArr = D('Robot')->getRobot($gameType,$betting);
		}
	    //只取大于1级的机器人
	    foreach ($RobotIdArr as $k => $v) {
	        if($v['lv'] <= 1) unset($RobotIdArr[$k]);
	    }
	    //转为二维数组
	    $gameArr[] = $game;
	    $number = rand(15,20);  //随机15-20条
	    $randGame = array();
	    $p = 0;
	    foreach ($RobotIdArr as $k => $v) {
	        $randGamble = $this->getRandGamble($v,$gameType,$gameArr,$betting,$play_type);
	        if(!empty($randGamble)){
	            $randGame[] = $randGamble;
	            $p ++;
	            //大于等于配置数量退出循环
	            if($p >= $number){
	                break;
	            }
	        }else{
	            continue;
	        }
	    }
	    if(!empty($randGame)) //添加竞猜记录与数量
	    {
	        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
	        $GambleModel->addAll($randGame);
	        $home_num = 0;
	        $draw_num = 0;
	        $away_num = 0;
	        foreach ($randGame as $k => $v) 
	        {
	            if($v['chose_side'] == 1){
	                $home_num++;
	            }
	            if($v['chose_side'] == 0){
	                $draw_num++;
	            }
	            if($v['chose_side'] == -1){
	                $away_num++;
	            }
	        }
	        //添加竞猜记录数量
	        $Model = $gameType == 1 ? M('gambleNumber') : M('gamblebkNumber');
	        $is_has = $Model->master(true)->where(['game_id'=>$game['game_id']])->getField('id');
	        if($gameType == 1)  //足球
	        {
	            switch ($play_type) 
	            {
	                case '1':
	                    //亚盘让球
	                    if($is_has) //更新数量
	                    {
	                        $rs = $Model->where(['game_id'=>$game['game_id']])->save([
	                                                'let_home_num'=>['exp','let_home_num+'.$home_num],
	                                                'let_away_num'=>['exp','let_away_num+'.$away_num],
	                                         ]);
	                    }
	                    else //添加新记录
	                    {
	                        $rs = $Model->add(['game_id'=>$game['game_id'],'let_home_num'=>$home_num,'let_away_num'=>$away_num]);
	                    }
	                    break;
	                case '-1':
	                    //亚盘大小
	                    if($is_has) //更新数量
	                    {
	                        $rs = $Model->where(['game_id'=>$game['game_id']])->save([
	                                                'size_big_num'=>['exp','size_big_num+'.$home_num],
	                                                'size_small_num'=>['exp','size_small_num+'.$away_num],
	                                         ]);
	                    }
	                    else //添加新记录
	                    {
	                        $rs = $Model->add(['game_id'=>$game['game_id'],'size_big_num'=>$home_num,'size_small_num'=>$away_num]);
	                    }
	                    break;
	                case '2':
	                    //竞彩不让球
	                	if($is_has) //更新数量
	                	{
	                	    $rs = $Model->where(['game_id'=>$game['game_id']])->save([
	                	                            'not_win_num'=>['exp','not_win_num+'.$home_num],
	                	                            'not_draw_num'=>['exp','not_draw_num+'.$draw_num],
	                	                            'not_lose_num'=>['exp','not_lose_num+'.$away_num],
	                	                     ]);
	                	}
	                	else //添加新记录
	                	{
	                	    $rs = $Model->add(['game_id'=>$game['game_id'],'not_win_num'=>$home_num,'not_draw_num'=>$draw_num,'not_lose_num'=>$away_num]);
	                	}
	                    break;
	                case '-2':
	                    //竞彩让球
	                	if($is_has) //更新数量
	                	{
	                	    $rs = $Model->where(['game_id'=>$game['game_id']])->save([
	                	                            'let_win_num'=>['exp','let_win_num+'.$home_num],
	                	                            'let_draw_num'=>['exp','let_draw_num+'.$draw_num],
	                	                            'let_lose_num'=>['exp','let_lose_num+'.$away_num],
	                	                     ]);
	                	}
	                	else //添加新记录
	                	{
	                	    $rs = $Model->add(['game_id'=>$game['game_id'],'let_win_num'=>$home_num,'let_draw_num'=>$draw_num,'let_lose_num'=>$away_num]);
	                	}
	                    break;
	            }
	        }
	        else //篮球
	        {
	            switch ($play_type) 
	            {
	                case '1':
	                    //全场让球
	                    if($is_has) //更新数量
	                    {
	                        $rs = $Model->where(['game_id'=>$game['game_id']])->save([
	                                                'all_home_num'=>['exp','all_home_num+'.$home_num],
	                                                'all_away_num'=>['exp','all_away_num+'.$away_num],
	                                         ]);
	                    }
	                    else //添加新记录
	                    {
	                        $rs = $Model->add(['game_id'=>$game['game_id'],'all_home_num'=>$home_num,'all_away_num'=>$away_num]);
	                    }
	                    break;
	                case '-1':
	                    //全场大小
	                    if($is_has) //更新数量
	                    {
	                        $rs = $Model->where(['game_id'=>$game['game_id']])->save([
	                                                'all_big_num'=>['exp','all_big_num+'.$home_num],
	                                                'all_small_num'=>['exp','all_small_num+'.$away_num],
	                                         ]);
	                    }
	                    else //添加新记录
	                    {
	                        $rs = $Model->add(['game_id'=>$game['game_id'],'all_big_num'=>$home_num,'all_small_num'=>$away_num]);
	                    }
	                    break;
	                // case '2':
	                //     //半场让球
	                //     $gambleStr = $param['chose_side'] == 1 ? 'half_home_num' : 'half_away_num';
	                //     break;
	                // case '-2':
	                //     //半场大小
	                //     $gambleStr = $param['chose_side'] == 1 ? 'half_big_num' : 'half_small_num';
	                //     break;
	            }
	        }
	    }
	    return $p;
	}

	//获取随机足球/篮球竞猜
	public function getRandGamble($userInfo,$gameType,$game,$betting=1,$play_type=0){
	    $number = $userInfo['QuizNumber'];
	    $user_id = $userInfo['id'];

	    //剩余可竞猜数量
	    list($normLeftTimes,$imptLeftTimes,$gameConf,$gambleList) = D('GambleHall')->gambleLeftTimes($user_id,$gameType,$betting);

	    if(($normLeftTimes + $imptLeftTimes) <= 0) return array();

	    //根据今日设置发布数量随机上下浮动一条
	    $RandNumber = rand($number-1,$number+1);
	    if(count($gambleList) >= $RandNumber) return array();
	   
	    $game_id = array();
	    //已竞猜赛事
	    foreach ($gambleList as $k => $v) {
	        $game_id[] = $v['game_id'];
	    }
	    $game_id = array_unique($game_id);
	    //去掉已经竞猜的
	    if(!empty($game_id)){
	        foreach ($game as $k => $v) {
	            if(in_array($v['game_id'], $game_id)){
	                unset($game[$k]);
	            }
	        }
	    }
	    //每次只发一条普通竞猜
	    $RandQuiz  = $this->getRandQuiz($game,$user_id,$userInfo['lv'],$gameType,$gameConf,$betting,$play_type);
	    if(!empty($RandQuiz)){
	    	//推送
	    	D('GambleHall')->gamblePush($userInfo,$RandQuiz);
	    }
	    return $RandQuiz;
	}

	//获取随机答案
	public function getRandQuiz($game,$user_id,$Lv,$gameType,$gameConf,$betting,$play_type)
	{
	    //随机获取1条比赛
	    $randGamble = shuffle($game);
	    $newArray   = array_slice($game, 0,1);
	    //$newArray = $this->getIsSubGame($game,$QuizNumber);

	    $number = ['1','-1','2','-2'];
	    $gamble = [];
	    foreach ($newArray as $k => $v) {
	        $gamble['union_id']       = $v['union_id'];
	        $gamble['union_name']     = $v['union_name'];
	        $gamble['game_id']        = $v['game_id'];
	        $gamble['home_team_name'] = $v['home_team_name'];
	        $gamble['away_team_name'] = $v['away_team_name'];
	        $gamble['chose_side']     = $betting == 1 ? $number[rand(0,1)] : rand(-1,1);
	        if($gameType == 1) //足球
	        {
	        	if($betting == 1) //亚盘
	        	{
	        		//是否指定玩法
	        		$gamble['play_type'] = $play_type ? : $number[rand(0,1)];
	        		switch ($gamble['play_type']) {
	        		    case '1':
	        		        if($gamble['chose_side'] == 1){
	        		            $gamble['odds']       = $v['fsw_exp_home'];
	        		            $gamble['odds_other'] = $v['fsw_exp_away'];
	        		        }else{
	        		            $gamble['odds']       = $v['fsw_exp_away'];
	        		            $gamble['odds_other'] = $v['fsw_exp_home'];
	        		        }
	        		        $gamble['handcp'] = $v['fsw_exp'];
	        		        break;
	        		    case '-1':
	        		        if($gamble['chose_side'] == 1){
	        		            $gamble['odds']       = $v['fsw_ball_home'];
	        		            $gamble['odds_other'] = $v['fsw_ball_away'];
	        		        }else{
	        		            $gamble['odds']       = $v['fsw_ball_away'];
	        		            $gamble['odds_other'] = $v['fsw_ball_home'];
	        		        }
	        		        $gamble['handcp'] = $v['fsw_ball'];
	        		        break;
	        		}
	        	}
	            elseif ($betting == 2)  //竞彩
	            {
	            	//是否指定玩法
	        		$gamble['play_type'] = $play_type ? : $number[rand(2,3)];
	        		switch ($gamble['play_type']) {
	        		    case '2':
	        		    	switch ($gamble['chose_side']) {
	        		    		case  '1': $gamble['odds'] = $v['home_odds']; break;
	        		    		case  '0': $gamble['odds'] = $v['draw_odds']; break;
	        		    		case '-1': $gamble['odds'] = $v['away_odds']; break;
	        		    	}
	        		        $gamble['handcp'] = 0;
	        		        break;
	        		    case '-2':
	        		        switch ($gamble['chose_side']) {
	        		    		case  '1': $gamble['odds'] = $v['home_letodds']; break;
	        		    		case  '0': $gamble['odds'] = $v['draw_letodds']; break;
	        		    		case '-1': $gamble['odds'] = $v['away_letodds']; break;
	        		    	}
	        		        $gamble['handcp'] = $v['let_exp'];
	        		        break;
	        		}
	        		$gamble['odds_other'] = json_encode([
	        									'home_odds'    => $v['home_odds'],
	        									'draw_odds'    => $v['draw_odds'],
	        									'away_odds'    => $v['away_odds'],
	        									'home_letodds' => $v['home_letodds'],
	        									'draw_letodds' => $v['draw_letodds'],
	        									'away_letodds' => $v['away_letodds'],
	        								]);
	            }
	        }
	        else //篮球
	        {
	            $gamble['play_type'] = $play_type ? : $number[rand(0,1)];
	            switch ($gamble['play_type']) {
	                case '1':
	                    if($gamble['chose_side'] == 1){
	                        $gamble['odds']       = $v['fsw_exp_home'];
	                        $gamble['odds_other'] = $v['fsw_exp_away'];
	                    }else{
	                        $gamble['odds']       = $v['fsw_exp_away'];
	                        $gamble['odds_other'] = $v['fsw_exp_home'];
	                    }
	                    $gamble['handcp'] = $v['fsw_exp'];
	                    break;
	                case '-1':
	                    if($gamble['chose_side'] == 1){
	                        $gamble['odds']       = $v['fsw_total_home'];
	                        $gamble['odds_other'] = $v['fsw_total_away'];
	                    }else{
	                        $gamble['odds']       = $v['fsw_total_away'];
	                        $gamble['odds_other'] = $v['fsw_total_home'];
	                    }
	                    $gamble['handcp'] = $v['fsw_total'];
	                    break;
	                case '2':
	                    if(empty($v['psw_exp']) || empty($v['psw_exp_home']) || empty($v['psw_exp_away'])){
	                        //盘口赔率为空重新获取玩法，只获取全场
	                        $gamble['play_type'] = $number[rand(0,1)];
	                        switch ($gamble['play_type']) {
	                            case '1':
	                                if($gamble['chose_side'] == 1){
	                                    $gamble['odds']       = $v['fsw_exp_home'];
	                                    $gamble['odds_other'] = $v['fsw_exp_away'];
	                                }else{
	                                    $gamble['odds']       = $v['fsw_exp_away'];
	                                    $gamble['odds_other'] = $v['fsw_exp_home'];
	                                }
	                                $gamble['handcp'] = $v['fsw_exp'];
	                                break;
	                            case '-1':
	                                if($gamble['chose_side'] == 1){
	                                    $gamble['odds']       = $v['fsw_total_home'];
	                                    $gamble['odds_other'] = $v['fsw_total_away'];
	                                }else{
	                                    $gamble['odds']       = $v['fsw_total_away'];
	                                    $gamble['odds_other'] = $v['fsw_total_home'];
	                                }
	                                $gamble['handcp'] = $v['fsw_total'];
	                                break;
	                        }
	                        break;
	                    }
	                    if($gamble['chose_side'] == 1){
	                        $gamble['odds']       = $v['psw_exp_home'];
	                        $gamble['odds_other'] = $v['psw_exp_away'];
	                    }else{
	                        $gamble['odds']       = $v['psw_exp_away'];
	                        $gamble['odds_other'] = $v['psw_exp_home'];
	                    }
	                    $gamble['handcp'] = $v['psw_exp'];
	                    break;
	                case '-2':
	                    if(empty($v['psw_total']) || empty($v['psw_total_home']) || empty($v['psw_total_away'])){
	                        //盘口赔率为空重新获取玩法，只获取全场
	                        $gamble['play_type'] = $number[rand(0,1)];
	                        switch ($gamble['play_type']) {
	                            case '1':
	                                if($gamble['chose_side'] == 1){
	                                    $gamble['odds']       = $v['fsw_exp_home'];
	                                    $gamble['odds_other'] = $v['fsw_exp_away'];
	                                }else{
	                                    $gamble['odds']       = $v['fsw_exp_away'];
	                                    $gamble['odds_other'] = $v['fsw_exp_home'];
	                                }
	                                $gamble['handcp'] = $v['fsw_exp'];
	                                break;
	                            case '-1':
	                                if($gamble['chose_side'] == 1){
	                                    $gamble['odds']       = $v['fsw_total_home'];
	                                    $gamble['odds_other'] = $v['fsw_total_away'];
	                                }else{
	                                    $gamble['odds']       = $v['fsw_total_away'];
	                                    $gamble['odds_other'] = $v['fsw_total_home'];
	                                }
	                                $gamble['handcp'] = $v['fsw_total'];
	                                break;
	                        }
	                        break;
	                    }
	                    if($gamble['chose_side'] == 1){
	                        $gamble['odds']       = $v['psw_total_home'];
	                        $gamble['odds_other'] = $v['psw_total_away'];
	                    }else{
	                        $gamble['odds']       = $v['psw_total_away'];
	                        $gamble['odds_other'] = $v['psw_total_home'];
	                    }
	                    $gamble['handcp'] = $v['psw_total'];
	                    break;
	            }
	        }
	        //盘口格式转换(数字格式)
	        $gamble['handcp'] = changeExpStrToNum($gamble['handcp']);
	        //竞猜时间随机后5分钟时间
	        $gamble['create_time'] = rand(time()+300,time());
	        $gamble['platform'] = rand(1,3);
	        $gamble['user_id'] = $user_id;
	        //竞猜积分
	        $gamble['vote_point'] = $gameConf['norm_point'];
	        //分解时间
	        $gamble['game_date'] = date('Ymd',$v['gtime']);
	        $gamble['game_time'] = date('H:i',$v['gtime']);
	        $gamble['game_id'] = $v['game_id'];
	        if($Lv > 0){
	            $robotCoin = getWebConfig('robotCoin');
	            $coinArr = $robotCoin['robot_coin'][$Lv];
	            foreach ($coinArr as $kk => $vv) {
	                if($vv != '' && $kk <= 128){
	                    $randCoin[] = $kk;
	                }
	            }
	            $tradeCoin = $randCoin[array_rand($randCoin)];
	        }else{
	            $tradeCoin = 0;
	        }

	        $gamble['tradeCoin'] = $tradeCoin;
	        $gamble['sign']      = $user_id.'^'.$v['game_id'].'^'.$gamble['play_type'];
	    }
	    $min_odds = $betting == 1 ? '0.6' : '1.4';
	    if($gamble['odds'] == "" || $gamble['odds'] < $min_odds) return array();
	    return $gamble;
	}

	public function getIsSubGame($game,$QuizNumber){
	    //根据赛事等级分开
	    foreach ($game as $k => $v) {
	        switch ($v['is_sub']) {
	            case '0': $game_one[]   = $v; break;
	            case '1': $game_two[]   = $v; break;
	            case '2': $game_three[] = $v; break;
	        }
	    }
	    //先发0级->1级->2级
	    if(!empty($game_one)){
	        shuffle($game_one);
	        $newArray = array_slice($game_one, 0, $QuizNumber);
	        return $newArray;
	    }
	    if(!empty($game_two)){
	        shuffle($game_two);
	        $newArray = array_slice($game_two, 0, $QuizNumber);
	        return $newArray;
	    }
	    if(!empty($game_three)){
	        shuffle($game_three);
	        $newArray = array_slice($game_three, 0, $QuizNumber);
	        return $newArray;
	    }
	}

	public function getRobot($gameType,$betting=1){
		switch ($gameType) {
		    case '1': 
		    	$Lv   = $betting  == 1 ? 'lv' : 'lv_bet';
		        $sign = $playType == 1 ? 'fbConfig' : 'betConfig';  
		        $gameWhere['play_type'] = $betting  == 1 ? ['in',[1,-1]] : ['in',[2,-2]];
		        $rob_config = $betting == 1 ? 'fb_config' : 'fbet_config';
		        $GambleModel = 'qc_gamble';
		        break;
		    case '2': 
		    	$Lv   = 'lv_bk';
		    	$sign = 'bkConfig';  
		    	$rob_config = 'bk_config';
		    	$GambleModel = 'qc_gamblebk';
		    	break;
		}
		$gameConf    = getWebConfig($sign);
		if (in_array(date('N',$blockTime['beginTime']),[1,2,3,4])) //周1-4
		{
		    $normTimes = $gameConf['weekday_norm_times'];
		}
		else
		{
		    $normTimes = $gameConf['weekend_norm_times'];
		}
		$where['status']   = 1;
		$where['is_robot'] = 1;
		if(date('G') == 10) //10点钟时只取大于2级的机器人
		{
		    $where[$Lv] = ['gt',2];
		}

		$gameTime = getBlockTime($gameType,true);
		$gameWhere['create_time'] = ['between',[$gameTime['beginTime'],$gameTime['endTime']]];

		//获取机器人
		$notRobor = M('FrontUser f')->master(true)
		->join("LEFT JOIN ".$GambleModel." g on g.user_id = f.id")
		->field(['f.id','count(g.id) num'])
		->where($where)->where($gameWhere)->group('f.id')->having('num = '.$normTimes)->select();

		$notRoborArrId = array_map("array_shift", $notRobor);
		if(!empty($notRoborArrId)) $where['id'] = ['not in',$notRoborArrId];

		$RobotIdArr = M('FrontUser')->master(true)->field(['id',$Lv.' as lv','nick_name','robot_conf'])->where($where)->select();

		foreach ($RobotIdArr as $k => $v) {
		    $robot_conf = json_decode($v['robot_conf'],true)[$rob_config];
		    //该用户今天可发布数
		    $segmTime = $gameType == 1 ? strtotime(C('fb_gamble_time')) : strtotime(C('bk_gamble_time'));
		    $QuizNumber = time() > $segmTime ? $robot_conf[date('N')] : $robot_conf[date('N',strtotime('-1 day'))];
		    $RobotIdArr[$k]['QuizNumber'] = $QuizNumber;
		    unset($RobotIdArr[$k]['robot_conf']);
		    //是否竞猜 0：竞猜  1：不竞猜
		    if($QuizNumber <= 0 || $robot_conf['is_quiz'] == 1){
		    	unset($RobotIdArr[$k]);
		    }
		}
		//打乱顺序
		shuffle($RobotIdArr);
		return $RobotIdArr;
	}

	/**
	 * 聊天室机器人列表
	 * @param $page 页码
	 */
	public function getRobotList($page){
		$pageNum = 500;

		$total = M('FrontUser')->where(['is_robot'=>1])->count(); //数据记录总数
		$totalPage = ceil($total / $pageNum); //总计页数

		if($page <= $totalPage){
			//判断最后一页数据，如果少于一半，则直接返回第一页数据
			if($page == $totalPage){
				$restNum = M('FrontUser')->where(['is_robot'=>1])->page($page.','.$pageNum)->count();
				if($restNum < $pageNum/2) $page = 1;
			}
			$list = M('FrontUser')->field(' id, username, nick_name as nickname, head as avatar, lv, lv_bet, lv_bk ')
					->where(['is_robot'=>1])
					->page($page.','.$pageNum)
					->order('id asc')
					->select();

			if($totalPage == $page){
				$prev = $page-1;
				$next = 0;
			}else{
				$prev = $page-1;
				$next = $page+1;
			}
		}else{
			$list = [];
			$prev = $page-1;
			$next = 0;
		}

		if($list){
			foreach($list as $k => &$v){
				$v['avatar'] = frontUserFace($v['avatar']);
			}
		}

		$url = SITE_URL.$_SERVER['HTTP_HOST'].'/'.MODULE_NAME.'/index/robots?page=';

		$data['count']    = count($list);
		$data['next']     = $url.$next;
		$data['previous'] = $url.$prev;
		$data['results']  = $list;

		return $data;
	}

	/**
	 * 5.1新版机器人列表
	 * @param  $gameType int
	 * @param  $gameId int
	 * @param  $firstTime int 是否第一次请求
	 * @return array
	 */
	public function getNewRobotList($gameType, $gameId, $firstTime=0){
		if($gameId == 0 || $gameType == 0) return false;

		$robotConfig = getWebConfig(['fbGameRobot', 'bkGameRobot']);
		$redis = connRedis();
		//获取在线用户集合（在线用户id）
		$key_suffix = $gameType . '_' . $gameId;
		$normalMembers = $redis->sMembers('qqty_chat_normal_online:' .$key_suffix);
		$robotMembers  = $redis->sMembers('qqty_chat_robot_online:' . $key_suffix);
//		$normal_key ='qqty_chat_normal_online:' . $key_suffix;
//		$robot_key  ='qqty_chat_robot_online:' . $key_suffix;
//		$normalNum  = (int) $redis->sCard($normal_key);
//		$robotNum   = (int) $redis->sCard($robot_key);
		//上线推送人数，下线的时候，客户端推送
//		$default_num = $redis->get('api_chatDefaultNum_' . $gameType . '_' . $gameId);

		$totalMembers = array_merge($normalMembers, $robotMembers);

		//真实线上用户
		$onlineUser = '';

		if($gameType == 1){
			$data = $this->chatFbProcess($robotConfig['fbGameRobot'], $totalMembers, $onlineUser, $gameId, $firstTime);
		}else if($gameType == 2){
			$data = $this->chatBkProcess($robotConfig['bkGameRobot'], $totalMembers, $onlineUser, $gameId, $firstTime);
		}else {
			return false;
		}

		if($data === false) return false;

		$addType  = $data['addType'];
		$robotNum = $data['robotNum'];
		$lastTime = $data['lastTime'];

		//增加人数
		if($addType == 1){
			$list = M('FrontUser')->field(' id, username, nick_name as nickname, head as avatar, lv, lv_bet, lv_bk ')
					->where(['is_robot'=>1])
					->order('RAND()')->limit($robotNum)
					->select();

			$userArr = [];
			foreach($list as $k => &$v){
				$v['avatar'] = frontUserFace($v['avatar']);
				$userArr[] = $v['id'];
			}
		}else{//减少人数
			$userArr = $robotNum;
			$list = [];
		}

		//拿总数给聊天室加上，判断比赛中则增加，结束则减少；增加的返回客户端，减少就不需要了
		$this->onOffLine($userArr, $gameType, $gameId, $addType, 'robot', $lastTime);

		return ['list' => $list, 'num' => $addType == 1 ? 0 : count($userArr)];
	}

	/**
	 * 聊天室足球配置处理过程
	 */
	public function chatFbProcess($config, $totalMembers, $onlineUser, $gameId, $firstTime){
		$info = M('GameFbinfo g')->join('left join qc_union u on g.union_id = u.union_id')
				->join('left join qc_fb_linkbet l on g.game_id = l.game_id')
				->where(['g.game_id' => $gameId])->field('g.gtime, g.game_state, u.is_sub, g.is_video, g.app_video, l.is_link, l.md_id')->find();
//		$info['game_state'] = -1;

		if(empty($info)) return false;

		//分赛事级别0,1是1级
		if(in_array($info['is_sub'], [0, 1, 2])){
			$c = $config['rank1'];
		}else{//普通
			$c = $config['rank0'];
		}

		//默认有视频或者动画
		$isGame = 1;
		$isVideo = ($info['is_video'] && $info['app_video'] && !empty(json_decode($info['app_video'], true))) ? 1: 0;
		$isFlash = ($info['is_link'] && $info['md_id']) ? 1: 0;
		if($isVideo == 0 && $isFlash == 0) $isGame = 0;

		//设置默认人数，
		if(!S('chatDefaultNum_1_'.$gameId)){
			if($isGame) {
				if (in_array($info['is_sub'], [0, 1])) {
					$defaultNum = $config['defaultNum'][1];
				} else if ($info['is_sub'] == 2) {
					$defaultNum = $config['defaultNum'][2];
				} else {//普通
					$defaultNum = $config['defaultNum'][3];
				}
			}else{//判断是否无视频，无动画
				$defaultNum['start'] = 1;
				$defaultNum['end'] = 20;
			}

			S('chatDefaultNum_1_'.$gameId, mt_rand($defaultNum['start'], $defaultNum['end']), 60*60*2);//保存2小时
		}

		//现在时间
		$nowTime = floor((time() - $info['gtime'])/60);

		//默认增加人数
		$addType = 1;

		//是否最后一次清零
		$lastTime = 0;

		//正常状态只返回一次
		if(in_array($info['game_state'], [0, 1, 2, 3, 4]) && $isGame == 0){
			if(!S('noGameDefaultNum_1_'.$gameId)){
				S('noGameDefaultNum_1_'.$gameId, 1, 60*60*3);
				return ['addType' => $addType, 'robotNum' => S('chatDefaultNum_1_'.$gameId), 'lastTime' => $lastTime];
			}else{
				return false;
			}
		}

		//赛前：比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
		if($info['game_state'] == 0 && $isGame){
			if(abs($nowTime) <= $c['before']['time']){
				$robotNum = ($onlineUser + $c['before']['addNum'])* $c['before']['multiNum'];
			}else{//未开超过一个小时以上，返回10个
				$robotNum = 100;
			}
		}else if(in_array($info['game_state'], [1, 2, 3, 4]) && $isGame){//比赛中
			if($firstTime){
				$robotNum = S('chatDefaultNum_1_'.$gameId);
			}else {
				if ($nowTime >= $c['middle'][1]['time1'] && $nowTime <= $c['middle'][1]['time2']) {
					$robotNum = ($onlineUser + $c['middle'][1]['addNum']) * $c['middle'][1]['multiNum'];
				} else if ($nowTime >= $c['middle'][2]['time1'] && $nowTime <= $c['middle'][2]['time2']) {
					$robotNum = ($onlineUser + $c['middle'][2]['addNum']) * $c['middle'][2]['multiNum'];
				} else if ($nowTime >= $c['middle'][3]['time1'] && $nowTime <= $c['middle'][3]['time2']) {
					$robotNum = ($onlineUser + $c['middle'][3]['addNum']) * $c['middle'][3]['multiNum'];
				} else if ($nowTime >= $c['middle'][4]['time1'] && $nowTime <= $c['middle'][4]['time2']) {
					$robotNum = ($onlineUser + $c['middle'][4]['addNum']) * $c['middle'][4]['multiNum'];
				}
			}
		}else if($info['game_state'] == -1){//比赛结束，减人数
			//完场时第一时间保存最终清除时间
			if(!$endTime = S('chatEndTime_1_'.$gameId)){
				$endTime = time() + $c['after']['time']*60;
				S('chatEndTime_1_'.$gameId, $endTime, 60*30);
			}

			//清除的间隔，1分钟
			if(S('chatClearInterval_1_'.$gameId)){
				return false;
			}else{
				S('chatClearInterval_1_'.$gameId, 1, 60);
			}

			if(time() <= $endTime){
				$robotNum = floor(count($totalMembers) / $c['after']['minusNum']);
				$robotNum = array_slice($totalMembers, 0, $robotNum);//截取前面的人数
			}else if(time() > $endTime){//最后时刻全部人数消失
				$robotNum = $totalMembers;
				$lastTime = 1;
				S('chatDefaultNum_1_'.$gameId, null);//完场清零
			}
			$addType = -1;
		}else{
			return false;
		}

		return ['addType' => $addType, 'robotNum' => $robotNum, 'lastTime' => $lastTime];
	}

	/**
	 * 聊天室篮球配置处理过程
	 */
	public function chatBkProcess($config, $totalMembers, $onlineUser, $gameId, $firstTime){
		$info = M('GameBkinfo g')->join('left join qc_bk_union u on g.union_id = u.union_id')
				->join('left join qc_bk_linkbet l on g.game_id = l.game_id')
				->where(['g.game_id' => $gameId])->field('g.gtime, g.game_state, u.is_sub, g.is_video, g.app_video, l.is_link, l.md_id')->find();
//		$info['game_state'] = -1;

		if(empty($info)) return false;

		//分赛事级别0,1是1级
		if(in_array($info['is_sub'], [0, 1, 2])){
			$c = $config['rank1'];
		}else{//普通
			$c = $config['rank0'];
		}

		//默认有视频或者动画
		$isGame = 1;
		$isVideo = ($info['is_video'] && $info['app_video'] && !empty(json_decode($info['app_video'], true))) ? 1: 0;
		$isFlash = ($info['is_link'] && $info['md_id']) ? 1: 0;
		if($isVideo == 0 && $isFlash == 0) $isGame = 0;

		//设置默认人数
		if(!S('chatDefaultNum_2_'.$gameId)){
			if($isGame) {
				if (in_array($info['is_sub'], [0, 1])) {
					$defaultNum = $config['defaultNum'][1];
				} else if ($info['is_sub'] == 2) {
					$defaultNum = $config['defaultNum'][2];
				} else {//普通
					$defaultNum = $config['defaultNum'][3];
				}
			}else{//判断是否无视频，无动画
				$defaultNum['start'] = 1;
				$defaultNum['end'] = 20;
			}
			S('chatDefaultNum_2_'.$gameId, mt_rand($defaultNum['start'], $defaultNum['end']), 60*60*3);//保存3小时
		}

		//现在时间
		$nowTime = floor((time() - $info['gtime'])/60);

		//默认增加人数
		$addType = 1;

		//是否最后一次清零
		$lastTime = 0;

		//正常状态只返回一次
		if(in_array($info['game_state'], [0, 1, 2, 3, 4]) && $isGame == 0){
			if(!S('noGameDefaultNum_2_'.$gameId)){
				S('noGameDefaultNum_2_'.$gameId, 1, 60*60*3);
				return ['addType' => $addType, 'robotNum' => S('chatDefaultNum_2_'.$gameId), 'lastTime' => $lastTime];
			}else{
				return false;
			}
		}

		//比赛状态：比赛状态 0:未开,1:第一节,2:第二节,50:中场,3:第三节,4:第四节,5:第五节,6:第六节,-2:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消,-5异常（undefined）
		if($info['game_state'] == 0){//未开
			if(abs($nowTime) <= $c['before']['time'] && $isGame){
				$robotNum = ($onlineUser + $c['before']['addNum'])* $c['before']['multiNum'];
			}else{//未开超过一个小时以上，返回10个
				$robotNum = 10;
			}
		}else if(in_array($info['game_state'], [1,2,3,4,5,6,50]) && $isGame){//进行中
			if($firstTime){
				$robotNum = S('chatDefaultNum_2_'.$gameId);
			}else {
				if ($info['game_state'] == 1) {
					$robotNum = ($onlineUser + $c['middle'][1]['addNum']) * $c['middle'][1]['multiNum'];
				} else if ($info['game_state'] == 2) {
					$robotNum = ($onlineUser + $c['middle'][2]['addNum']) * $c['middle'][2]['multiNum'];
				} else if ($info['game_state'] == 3) {
					$robotNum = ($onlineUser + $c['middle'][3]['addNum']) * $c['middle'][3]['multiNum'];
				} else if ($info['game_state'] == 4) {
					$robotNum = ($onlineUser + $c['middle'][4]['addNum']) * $c['middle'][4]['multiNum'];
				} else if (in_array($info['game_state'], [5, 6])) {//加时
					$robotNum = ($onlineUser + $c['middle'][5]['addNum']) * $c['middle'][5]['multiNum'];
				}
			}
		}else if($info['game_state'] == -1){//结束后
			//完场时第一时间保存最终清除时间
			if(!$endTime = S('chatEndTime_2_'.$gameId)){
				$endTime = time() + $c['after']['time']*60;
				S('chatEndTime_2_'.$gameId, $endTime, 60*30);
			}

			//清除的间隔，1分钟
			if(S('chatClearInterval_2_'.$gameId)){
				return false;
			}else{
				S('chatClearInterval_2_'.$gameId, 1, 60);
			}

			if(time() <= $endTime){
				$robotNum = floor(count($totalMembers) / $c['after']['minusNum']);
				$robotNum = array_slice($totalMembers, 0, $robotNum);//截取前面的人数
			}else if(time() > $endTime){//最后时刻全部人数消失
				$robotNum = $totalMembers;
				$lastTime = 1;
				S('chatDefaultNum_2_'.$gameId, null);//完场清零
			}
			$addType = -1;
		}else{
			return false;
		}

		return ['addType' => $addType, 'robotNum' => $robotNum, 'lastTime' => $lastTime];
	}

    /**
     *  聊天室上下线
     * @param string|array $userids 用户id，单个值或者数组
     * @param int $game_type 赛事类型 1足球 2篮球
     * @param string $game_id 赛事id
     * @param int $type 上线下线 1上线 -1 下线
     * @param string $identity normal 正常用户 robot 机器人
	 * @param int $lastTime 是否最后一次清零，否：1；是：1
     * @return array 返回在线人数
     */
	public function onOffLine($userids, $game_type = 1, $game_id = '', $type = 1, $identity = 'normal', $lastTime=0){
        $redis  = connRedis();
        $key_suffix = $game_type . '_' . $game_id;
	    if($userids){
            $ids    = is_array($userids) ? $userids : [$userids];
            $action = $type ==  -1 ? 'sRem' : 'sadd';
            //在线列表中加入或者踢出用户
            foreach($ids as $k => $v){
                $redis->$action('qqty_chat_' . $identity . '_online:' . $key_suffix, $v);
            }
        }

        if($lastTime){
			$redis->del('qqty_chat_normal_online:' . $key_suffix);
			$redis->del('qqty_chat_robot_online:' . $key_suffix);
        }

        //清空默认人数和机器人
        if($type == 0){
            $redis->del('qqty_chat_robot_online:' . $key_suffix);
            $redis->set('api_chatDefaultNum_' . $game_type . '_' . $game_id, 0);
        }

        //获取在线成员数
        $normal_key ='qqty_chat_normal_online:' . $key_suffix;
        $robot_key  ='qqty_chat_robot_online:' . $key_suffix;
        $comstorm_key  ='qqty_chat_comstorm_online:' . $key_suffix;
        $normalNum  = (int) $redis->sCard($normal_key);
        $robotNum   = (int) $redis->sCard($robot_key);
        $comstormNum   = (int) $redis->get($comstorm_key);

        //设置过期时间3天
        if(!$normalNum)
            $redis->expire($normal_key, 3600 * 24 * 3);

        if(!$robotNum)
            $redis->expire($robot_key, 3600 * 24 * 3);

        if(!$comstormNum)
            $redis->expire($comstorm_key, 3600 * 24 * 3);

        //上线推送人数，下线的时候，客户端推送
        $default_num = (int)$redis->get('api_chatDefaultNum_' . $game_type . '_' . $game_id);
        $payload = [
            'action' => 'roomInfo',
            'data' => ['onlineNum' => $normalNum + $comstormNum + $default_num, 'time' => microtime(true), 'matchId' => $game_id, 'matchType' => $game_type, 'fromId' => $identity, 'rid' => rand(10000,99999)],
            'dataType' => 'text',
            'status' => '1'
        ];

        $opt = [
            'topic' => 'qqty/' . $key_suffix . '/chat',
            'payload' => $payload,
            'clientid' => md5(time() . $key_suffix),
        ];

        mqttPub($opt);

        return [
            'normalNum' => $normalNum,
            'robotNum' => $comstormNum,
            'default_num' => $default_num,
            'comstorm_num' => $comstormNum];
    }

	/**
	 * 定时清除比赛结束的机器人
	 */
	public function clearRobot(){
		//统计当前完赛的比赛，近4小时
		//足球
		$fbInfo = M('GameFbinfo')->where(['game_state' => '-1', 'gtime' => ['between', [time()-60*60*8, time()]]])->getField('game_id', true);
		if($fbInfo){
			foreach($fbInfo as $fk => $fv){
				$this->getNewRobotList(1, $fv, 0);
			}
			unset($fbInfo);
		}

		//篮球
		$bkInfo = M('GameBkinfo')->where(['game_state' => '-1', 'gtime' => ['between', [time()-60*60*8, time()]]])->getField('game_id', true);
		if($bkInfo){
			foreach($bkInfo as $bk => $bv){
				$this->getNewRobotList(2, $bv, 0);
			}
			unset($bkInfo);
		}

		return true;
	}

}