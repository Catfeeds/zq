<?php
/**
 * 首页
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
use Think\Tool\Tool;
class IndexController extends CommonController {
    
    /**
     * 首页
     * @User liangzk <liangzk@qc.com>
     * $dataTime 2016-11-2
     */
    public function index()
    {
        cookie('redirectUrl',__SELF__);
		
        //滚动的banner
		if (!$banner = S('IndexBanner_m'.MODULE_NAME))
		{
			$banner = Tool::getAdList(1,4,4) ?: '';
			S('IndexBanner_m'.MODULE_NAME,$banner,1*60);
		}

        $last_executeTime = S('IndexLastExecute_m'.MODULE_NAME);//z最后更新时间
        $rankUserArr = S('IndexRankUserArr_m'.MODULE_NAME);//根据十中几排序好的用户--足球亚盘
        //每天结算一次，就排序一次(即这里缓存一天)---，足球亚盘
        if (empty($rankUserArr) || (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < strtotime('10:36'))
            || (strtotime(date('H:i')) > strtotime('12:36') && $last_executeTime < strtotime('12:36')))
        {
            $rankUserArr = D('Base')->getDelicateList(1,1,50,48);//获取用户周榜前五十，足球亚盘，返回24条---十中几的统计
        
            S('IndexLastExecute_m'.MODULE_NAME,time(),24*60*60);
            S('IndexRankUserArr_m'.MODULE_NAME,$rankUserArr,24*60*60);
        }
        
        //每天结算一次，就排序一次----足球竞彩
        $last_raceTime = S('IndexLastExecuteRace_m'.MODULE_NAME);//z最后更新时间
        $delicateRaceList = S('IndexDelicateRaceList_m'.MODULE_NAME);//根据十中几排序好的用户--足球竞彩
        if (empty($delicateRaceList) || (strtotime(date('H:i')) > strtotime('10:36') && $last_raceTime < strtotime('10:36'))
            || (strtotime(date('H:i')) > strtotime('12:36') && $last_raceTime < strtotime('12:36')))
        {
            $delicateRaceList = D('Base')->getDelicateRaceList(1,1,50,48);//获取用户周榜前五十，足球竞彩，返回24条---十中几的统计

            S('IndexLastExecuteRace_m'.MODULE_NAME,time(),24*60*60);
            S('IndexDelicateRaceList_m'.MODULE_NAME,$delicateRaceList,24*60*60);
        }


        $blockTime = getBlockTime(1,true);
//
        //每日精选(足球亚盘)---缓存5分钟
		$selectRaceList = S('IndexSelectRaceList_m'.MODULE_NAME);//竞彩的每日精选
        if(!$selectList = S('IndexSelectList_m'.MODULE_NAME))
        {
            //每日精选--亚盘和竞彩出现用户相同只显示一个，根据十中几高的显示，相等就优先显示亚盘的
            foreach ($rankUserArr as $key => $value)
            {
                if (count($selectList) >= 24)//只获取12个
                    break;
                $equal = false;
                foreach ($selectRaceList as $k => $v)
                {
                    if ($value['user_id'] === $v['user_id'])
                    {
                        if ($value['tenGambleRate'] >= $v['tenGambleRate'])
                        {
                            $selectList[$key] = $value;
                        }
                        $equal = true;
                        break;
                    }
                }
                if (! $equal)
                {
                    $selectList[$key] = $value;
                }
            }
			$selectUserArr = get_arr_column($selectList,'user_id');
            $userArr = M('Gamble')
						->master(true)
                        ->where(['user_id'=>['IN',$selectUserArr],
								 'result'=>0,
                                 'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                        ->field('user_id')
                        ->group('user_id')
                        ->getField('user_id',true);
            foreach ($selectList as $key => $value)
            {
            	if(in_array($value['user_id'],$userArr))
				{
					$selectList[$key]['today_gamble'] = 1;//判断是否有推荐
				}
            }

            S('IndexSelectList_m'.MODULE_NAME, $selectList, 60*6);
        }

        //每日精选(足球竞彩)---缓存6分钟
        if (!$selectRaceList = S('IndexSelectRaceList_m'.MODULE_NAME))
        {
            //每日精选--亚盘和竞彩出现用户相同只显示一个，根据十中几高的显示，相等就优先显示亚盘的
            foreach ($delicateRaceList as $key => $value)
            {
                if (count($selectRaceList) >= 24)//只获取12个
                    break;
                $equal = false;
                foreach ($selectList as $k => $v)
                {
                    if ($value['user_id'] === $v['user_id'])
                    {
                        if ($value['tenGambleRate'] > $v['tenGambleRate'])
                        {
                            $selectRaceList[$key] = $value;
                        }
                        $equal = true;
                        break;
                    }
                }
                if (! $equal)
                {
                    $selectRaceList[$key] = $value;
                }
            }

            $userArr = M('Gamble')
				->master(true)
                ->where(['result'=>0,
						'user_id'=>['IN',get_arr_column($selectRaceList,'user_id')],
                         'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
                ->field('user_id')
                ->group('user_id')
                ->getField('user_id',true);
            foreach ($selectRaceList as $key => $value)
            {
            	if (in_array($value['user_id'],$userArr))
				{
					$selectRaceList[$key]['today_gamble'] = 1;//判断是否有推荐
				}
            }

            S('IndexSelectRaceList_m'.MODULE_NAME, $selectRaceList, 60*6);
        }

        //命中率高
		if (!$winningHigh = S('IndexWinningHighList_m'.MODULE_NAME))
		{
			$highListShow = S('IndexHighListShow_m'.MODULE_NAME) ? S('IndexHighListShow_m'.MODULE_NAME) : 1;//默认显示足球亚盘
			
			$winningList = $winningHigh['winningList'];
			if ($highListShow == 1)//足球亚盘
			{
				while (true)//同一个用户不可以和高命中同时出现
				{
					//足球亚盘
					if (! $highKeyArr = S('IndexHighKeyArr_m'.MODULE_NAME))//从命中率高的开始显示---15个用户循环显示
					{
						$highKeyArr = array_slice($rankUserArr,12,12);
					}
					$highList = $highKeyArr[0];//获取一名用户
					array_shift($highKeyArr);
					S('IndexHighKeyArr_m'.MODULE_NAME,$highKeyArr);
					if (empty($winningList['user_id']) || empty($highList['user_id']) || $winningList['user_id'] != $highList['user_id'])//同一个用户不可以和高命中同时出现
					{
						break;
					}
				}
				S('IndexHighListShow_m'.MODULE_NAME,2);
			}
			else
			{
				while (true)//同一个用户不可以和高命中同时出现
				{
					//足球竞彩
					if (! $highRaceKeyArr = S('IndexHighRaceKeyArr_m'.MODULE_NAME))//从命中率高的开始显示---15个用户循环显示
					{
						$highRaceKeyArr = array_slice($delicateRaceList,0,15);
					}
					$highList = $highRaceKeyArr[0];//获取一名用户
					array_shift($highRaceKeyArr);
					S('IndexHighRaceKeyArr_m'.MODULE_NAME,$highRaceKeyArr);
					if (empty($winningList['user_id']) || empty($highList['user_id']) || $winningList['user_id'] != $highList['user_id'])//同一个用户不可以和高命中同时出现
					{
						break;
					}
				}
				S('IndexHighListShow_m'.MODULE_NAME,1);
			}
	
			//获取用户的月胜率
			$highList['monthWinrate'] = D('GambleHall')->CountWinrate($highList['user_id'],1,2,false,false,0,$highListShow);//分足球亚盘、竞彩
			$winnig = D('GambleHall')->getWinning($highList['user_id'], 1,0,$highListShow); //连胜记录---分足球亚盘、竞彩
			$highList['curr_victs'] = $winnig['curr_victs'];//连胜场数--分足球亚盘、竞彩
			$highList['win'] = $winnig['win'];//胜场数--分足球亚盘、竞彩
	
	
			$newGamble = M('Gamble')
					->where(['user_id'=>$highList['user_id'],
							 'result'=>0,
							 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])
					->where(['play_type'=>$highListShow == 1 ? ['IN',['1','-1']] : ['IN',['2','-2']]])
					->field('home_team_name,away_team_name')
					->order('id desc')
					->find();
			$highList['home_team_name'] = substr($newGamble['home_team_name'],0,strpos($newGamble['home_team_name'],','));
			$highList['away_team_name'] = substr($newGamble['away_team_name'],0,strpos($newGamble['away_team_name'],','));
	
			$this->assign('highListShow',$highListShow);//用来标记显示的是竞彩、还是亚盘 1：亚盘 2：竞彩---但对连胜多来说 2：亚盘 1：竞彩
			
			$winningHigh['highList'] = $highList;
		
		   
			
			//连胜数多
			$highListShow = S('IndexHighListShow_m'.MODULE_NAME);
			if ($highListShow == 1)//显示竞彩的
			{
				$last_win_betTime = S('IndexLastWinBet_m'.MODULE_NAME);//最后更新时间
				$rankBettingWinArr = S('IndexRankBettingWinArr_m'.MODULE_NAME);//根据连胜数多排序好的用户
				//每天结算一次，就排序一次(即这里缓存一天)
				if (empty($rankBettingWinArr) || (strtotime(date('H:i')) > strtotime('10:36') && $last_win_betTime < strtotime('10:36'))
					|| (strtotime(date('H:i')) > strtotime('12:36') && $last_win_betTime < strtotime('12:36')))
				{
					$rankBettingWinArr = D('Base')->getWinnigRaceList(1,1,50,15);//获取用户周榜前五十，足球亚盘，返回15条--连胜统计
	
					S('IndexLastWinBet_m'.MODULE_NAME,time(),24*60*60);
					S('IndexRankBettingWinArr_m'.MODULE_NAME,$rankBettingWinArr,24*60*60);
				}
				while (true)//同一个用户不可以和高命中同时出现
				{
	
					if (! $winningBetKeyArr = S('IndexWinningBetKeyArr_m'.MODULE_NAME))//从连胜高的开始显示---15个用户循环显示
					{
						$winningBetKeyArr = array_slice($rankBettingWinArr,0,15);
					}
					$winningList = $winningBetKeyArr[0];//获取一名用户
					array_shift($winningBetKeyArr);
					S('IndexWinningBetKeyArr_m'.MODULE_NAME,$winningBetKeyArr);
					if (empty($winningList['user_id']) || empty($highList['user_id']) || $winningList['user_id'] != $highList['user_id'] )//同一个用户不可以和高命中同时出现
					{
						break;
					}
				}
	
			}
			else//显示亚盘
			{
				$last_win_executeTime = S('IndexLastWinExecute_m'.MODULE_NAME);//最后更新时间
				$rankUserWinArr = S('IndexRankUserWinArr_m'.MODULE_NAME);//根据连胜数多排序好的用户
				//每天结算一次，就排序一次(即这里缓存一天)
				if (empty($rankUserWinArr) || (strtotime(date('H:i')) > strtotime('10:35') && $last_win_executeTime < mktime(10,35,0,date('m'),date('d'),date('Y')))
					|| (strtotime(date('H:i')) > strtotime('12:35') && $last_win_executeTime < mktime(12,35,0,date('m'),date('d'),date('Y'))))
				{
					$rankUserWinArr = D('Base')->getWinnigList(1,1,50,12);//获取用户周榜前五十，足球亚盘，返回15条--连胜统计
	
					S('IndexLastWinExecute_m'.MODULE_NAME,time());
					S('IndexRankUserWinArr_m'.MODULE_NAME,$rankUserWinArr);
				}
	
				while (true)//同一个用户不可以和高命中同时出现
				{
					if (! $winningKeyArr = S('IndexWinningKeyArr_m'.MODULE_NAME))//从连胜高的开始显示---15个用户循环显示
					{
						$winningKeyArr = array_slice($rankUserWinArr,0,12);
					}
					$winningList = $winningKeyArr[0];//获取一名用户
					array_shift($winningKeyArr);
					S('IndexWinningKeyArr_m'.MODULE_NAME,$winningKeyArr);
					if (empty($winningList['user_id']) || empty($highList['user_id']) || $winningList['user_id'] != $highList['user_id'])//同一个用户不可以和高命中同时出现
					{
						break;
					}
				}
				
			}
			//获取用户的月胜率
			$winningList['monthWinrate'] = D('GambleHall')
									->CountWinrate($winningList['user_id'],1,2,false,false,0,$highListShow == 1 ? 2 : 1);//分足球亚盘、竞彩
			$tenGambleRes = M('Gamble')
							->where(['user_id'=>$winningList['user_id'],
									 'result'=>['in',[1,0.5,2,-1,-0.5]]])
							->where(['play_type'=>$highListShow == 1 ? ['IN',['2','-2']] : ['IN',['1','-1']]])
							->order("id desc")
							->limit(10)
							->getField('result',true);
			$winningList['lv_bet'] = M('FrontUser')->where(['id'=>$winningList['user_id']])->getField('lv_bet');
			$num = 0;
			foreach ($tenGambleRes as $k => $v)
			{
				if ($v['result'] === '1' || $v['result'] === '0.5')
				{
					$num++;
				}
			}
			$winningList['tenGambleRate'] = $num;//十中几
	
			$newGamble = M('Gamble')
						->where(['user_id'=>$winningList['user_id'],
								 'result'=>0,
								 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])
						->where(['play_type'=>$highListShow == 1 ? ['IN',['2','-2']] : ['IN',['1','-1']]])
						->field('home_team_name,away_team_name')
						->order('id desc')
						->find();
			$winningList['home_team_name'] = substr($newGamble['home_team_name'],0,strpos($newGamble['home_team_name'],','));
			$winningList['away_team_name'] = substr($newGamble['away_team_name'],0,strpos($newGamble['away_team_name'],','));
			
			$winningHigh['winningList'] = $winningList;
			
			S('IndexWinningHighList_m'.MODULE_NAME,$winningHigh,60*5);
		}
        
        
        $this->assign('banner',$banner);//首页广告位
		
        $this->assign('selectList',$selectList);//今日精选--足球亚盘
        $this->assign('selectRaceList',$selectRaceList);//今日精选--足球竞彩
        
        $this->assign('highList',$winningHigh['highList']);//高命中
        $this->assign('winningList',$winningHigh['winningList']);//多连胜
		
        $this->assign('recommendList',$this->getHightList());//高手推荐
        cookie('userUrl', __SELF__);
        cookie('pageUrl', __SELF__);
        $this->display();
    }
	//清楚首页redis缓存
    public function delCache(){
    	S('IndexBanner_m'.MODULE_NAME,null);
    	S('IndexLastExecute_m'.MODULE_NAME,null);
        S('IndexRankUserArr_m'.MODULE_NAME,null);
        S('IndexLastExecuteRace_m'.MODULE_NAME,null);
        S('IndexDelicateRaceList_m'.MODULE_NAME,null);
		S('IndexSelectRaceList_m'.MODULE_NAME,null);
        S('IndexSelectList_m'.MODULE_NAME,null);
        S('IndexHighListShow_m'.MODULE_NAME,null);
		S('IndexLastWinBet_m'.MODULE_NAME,null);
		S('IndexRankBettingWinArr_m'.MODULE_NAME,null);
		S('IndexWinningBetKeyArr_m'.MODULE_NAME,null);
		S('IndexLastWinExecute_m'.MODULE_NAME,null);
		S('IndexRankUserWinArr_m'.MODULE_NAME,null);
		S('IndexWinningKeyArr_m'.MODULE_NAME,null);
		S('IndexWinningHighList_m'.MODULE_NAME,null);
    }
    
    /**
     * 首页的大咖推荐列表--滑动加载
     */
    public function getHightList()
	{
		
		if (!$masterGamble = S('IndexMasterGamble'.MODULE_NAME))
		{
			$masterGamble = D('GambleHall')->masterGamble();
			S('IndexMasterGamble'.MODULE_NAME,$masterGamble,5*60);
		}
		
		//获取赛事id
		$gambleIdArr = $quizGameArr = array();
		foreach ($masterGamble as $k => $v)
		{
			$gambleIdArr[] = $v['gamble_id'];
			
			$masterGamble[$k]['union_name'] = $v['union_name'][0];
			$masterGamble[$k]['home_team_name'] = $v['home_team_name'][0];
			$masterGamble[$k]['away_team_name'] = $v['away_team_name'][0];
			$masterGamble[$k]['gDate'] = date('m/d',strtotime($v['game_date'])).' '.$v['game_time'];//比赛时间
		}
		if (!empty($masterGamble))  $masterGamble = HandleGamble($masterGamble,0,true);
		
		$userId = is_login();
		//获取用户查看的足球推荐
		if ($userId)
		{
			$quizGameArr = M('QuizLog')->where(['gamble_id'=>['IN',$gambleIdArr],'user_id'=>$userId,'game_type'=>1])->getField('gamble_id',true);
			if (empty($quizGameArr)) $quizGameArr = array();
		}
		
		foreach ($masterGamble as $key => $value)
		{
			//判断是否查看---1：查看或已结算或当前用户发布 0：还没查看
			if (in_array($value['gamble_id'],$quizGameArr) || $value['result'] != 0 || $value['user_id'] == $userId)
			{
				$masterGamble[$key]['is_trade'] = 1;
			}
			else
			{
				$masterGamble[$key]['chose_side'] = null;
				$masterGamble[$key]['handcp'] = null;
				$masterGamble[$key]['odds'] = null;
				$masterGamble[$key]['Answer'] = null;
				$masterGamble[$key]['desc'] = null;
				$masterGamble[$key]['is_trade'] = 0;
			}
		}

		return $masterGamble;
	}
	/**
	 * 超值高手
	 */
	public function superMaster()
	{
		if (!$totalArr = S('IndexSuperMaster'.MODULE_NAME))
		{
			$blockTime  = getBlockTime(1, $gamble = true);//获取赛程分割日期的区间时间
			$gambleData = D('GambleHall')->superMasterData(1, 15, $blockTime['beginTime'], $blockTime['endTime']);//亚盘
			$betData    = D('GambleHall')->superMasterData(2, 15, $blockTime['beginTime'], $blockTime['endTime']);//竞彩
			
			//不够就选前一天
			if(count($gambleData) < 15){
				$lastUser1 = [];
				foreach($gambleData as $uk => $uv){
					$lastUser1[] = $uv['user_id'];
				}
				
				$num = 15 - count($gambleData);
				$gambleData1 = D('GambleHall')->superMasterData(1, $num, strtotime('-1 day', $blockTime['beginTime']), strtotime('-1 day', $blockTime['endTime']), $lastUser1);//亚盘
				$gambleData  = array_merge($gambleData, $gambleData1);
			}
			
			if(count($betData) < 15){
				$lastUser2 = [];
				foreach($betData as $bk => $bv){
					$lastUser12[] = $bv['user_id'];
				}
				
				$num = 15 - count($betData);
				$betData1 = D('GambleHall')->superMasterData(2, $num, strtotime('-1 day', $blockTime['beginTime']), strtotime('-1 day', $blockTime['endTime']), $lastUser2);//亚盘
				$betData  = array_merge($betData, $betData1);
			}
			
			//重新排序
			$totalArr  = array_merge($gambleData, $betData);
			
			if ($totalArr) {
				$today  = $curr_victs1 = $tenGambleRate1 = $weekPercnet1 = $tradeCoin1 = $timeSort1 = array();
				$before = $curr_victs2 = $tenGambleRate2 = $weekPercnet2 = $tradeCoin2 = $timeSort2 = array();
				
				foreach ($totalArr as $k => $v) {
					//当天的分组，未结算
					if($v['result'] == 0){
						$curr_victs1[]    = $v['curr_victs'];
						$tenGambleRate1[] = $v['tenGambleRate'];
						$weekPercnet1[]   = $v['weekPercnet'];
						$tradeCoin1[]     = $v['tradeCoin'];
						$timeSort1[]      = $v['create_time'];
						$today[]          = $v;
					}else{
						$curr_victs2[]    = $v['curr_victs'];
						$tenGambleRate2[] = $v['tenGambleRate'];
						$weekPercnet2[]   = $v['weekPercnet'];
						$tradeCoin2[]     = $v['tradeCoin'];
						$timeSort2[]      = $v['create_time'];
						$before[]         = $v;
					}
					
					unset($totalArr[$k]['tenGambleRate']);
				}
				
				//排序，分组排序，当天时间优先，按连胜 > 10中几 > 周胜率 > 价格 > 发布时间
				array_multisort($curr_victs1, SORT_DESC, $tenGambleRate1, SORT_DESC, $weekPercnet1, SORT_DESC, $tradeCoin1, SORT_DESC, $timeSort1, SORT_DESC, $today);
				array_multisort($curr_victs2, SORT_DESC, $tenGambleRate2, SORT_DESC, $weekPercnet2, SORT_DESC, $tradeCoin2, SORT_DESC, $timeSort2, SORT_DESC, $before);
				
				//合并
				$totalArr = array_merge($today, $before);
				unset($curr_victs1, $tenGambleRate1, $weekPercnet1, $tradeCoin1, $timeSort1, $today);
				unset($curr_victs2, $tenGambleRate2, $weekPercnet2, $tradeCoin2, $timeSort2, $before);
			}
			
			S('IndexSuperMaster'.MODULE_NAME, $totalArr, 60*5);
		}
		
		$userId = is_login();
		if($totalArr)
		{
			//获取赛事id
			$gambleIdArr = $quizGameArr = array();
			foreach ($totalArr as $k => $v)
			{
				$gambleIdArr[] = $v['gamble_id'];
				$totalArr[$k]['home_team_name'] = $v['home_team_name'][0];
				$totalArr[$k]['away_team_name'] = $v['away_team_name'][0];
				$totalArr[$k]['union_name'] = $v['union_name'][0];
				
				$totalArr[$k]['gDate'] = date('m/d',strtotime($v['game_date'])).' '.$v['game_time'];//比赛时间
				
				$totalArr[$k]['score'] = $v['result'] != 0 ? str_replace('-','：',$v['score']) : ' VS ';
			}
			
			$totalArr = HandleGamble($totalArr,0,true);
			//获取用户查看的足球推荐
			if ($userId)
			{
				$quizGameArr = M('QuizLog')->where(['gamble_id'=>['IN',$gambleIdArr],'user_id'=>$userId,'game_type'=>1])->getField('gamble_id',true);
				if (empty($quizGameArr)) $quizGameArr = array();
			}
			
			foreach ($totalArr as $key => $value)
			{
				//判断是否查看---1：查看 2：还没查看
				if (in_array($value['gamble_id'],$quizGameArr) || $value['user_id'] == $userId || $value['result'] != 0)
				{
					$totalArr[$key]['is_trade'] = 1;
				}
				else
				{
					$totalArr[$key]['chose_side'] = null;
					$totalArr[$key]['handcp'] = null;
					$totalArr[$key]['odds'] = null;
					$totalArr[$key]['Answer'] = null;
					$totalArr[$key]['desc'] = null;
					$totalArr[$key]['is_trade'] = 0;
				}
			}
		}

		$this->ajaxReturn(['status'=>1,'list' => empty($totalArr) ? null : $totalArr]);
	}
    //
    public function getInfoToIndex()
    {
        $publishModel = M('PublishList');

        $list = $publishModel->alias('pl')->field('pl.id,pl.img,pl.title,pl.source,c.name')
        	->join('LEFT JOIN qc_publish_class c ON c.id = pl.class_id')
            ->where(['pl.class_id' => ['IN',[6,54,55]], 'pl.status' => 1])
            ->where(['pl.app_time' => ['lt', strtotime('+1 day', strtotime('7:15'))]])
            ->group('pl.id')
            ->order('pl.app_time DESC, pl.update_time desc')
            ->limit(30)
            ->select();
        
        foreach ($list as &$v)
        {
            $v['img'] = @Think\Tool\Tool::imagesReplace($v['img']);
        }

        $this->success($list);
    }
    
    //足球比分
    public function fbscore()
    {
        //获取即时页面数据
        $data=$this->get_curl(C('API_URL')."/fb",'key=no',C('CURL_DOMAIN'));
        //置顶
        if($data['status']===1){
            array_unique($data['data']);
            $league=array();
            $_Now=cookie('M_Now');
            $_Now_arr=array();
            if ($_Now != null){
                $_Now_arr=explode(',', $_Now);
            }
            foreach ($data['data'] as $k=>&$v){
                //获取联赛,不要第四级以下
                if(!empty($v[2][0]) && $v[4]<=3){
                    if($v[4]==0){
                        $league[1][$v[1]]=$v[2][0];
                    }else{
                        $league[$v[4]][$v[1]]=$v[2][0];
                    }
                }
                //移除没有选的联赛
                if ($_Now == null){
                    if($v[4]>2){
                        unset($data['data'][$k]);
                    }
                }else{
                    if(!in_array($v[1], $_Now_arr)){
                        unset($data['data'][$k]);
                    }
                }
            }
            //级别筛选
            ksort($league);
            if($_Now==null){
                $sche='';
                foreach ($league as $k=>$v){
                    if($k<3){
                        $va= array_flip($v);
                        $sche.= implode(',', $va);
                        $sche.=',';
                        }
                }
                $sche=  substr($sche, 0,-1);
                cookie('M_Now',$sche);
            }
            $this->assign('league',$league);
            $this->assign('list',$data['data']);
        }
        //广告图片
//        $adver = @Think\Tool\Tool::getAdList(17,5,2);
//        foreach ($adver as $k => $v)
//        {
//            unset($adver[$k]['id']);
//        }
//        $this->assign('adver_list',$adver);
        $this->assign('language',cookie('language'));
        $this->assign('chioce',cookie('M_Now'));
        cookie('userUrl', __SELF__);
		cookie('detailsUrl', __SELF__);
        $this->assign('title','足球比分');
        $this->display();
    }
    
    //篮球即时比分
    public function instantBk(){
        $this->assign('title','篮球比分');
        //获取即时页面数据
        $data=$this->get_curl(C('API_BKURL')."/bk",'key=no',C('CURL_DOMAIN'));
        if($data['status']===1){
            $league=[];
            $_Now=cookie('NowBk');
            if ($_Now != null){
                $_Now_arr=explode(',', $_Now);
            }
            array_unique($data['data']);
            //获取联赛
            foreach ($data['data'] as $k=>&$v){
                if(!empty($v[2][0] )){
                    $league[$v[1]]=$v[2][0];
                }
                //移除没有选的联赛
                if ($_Now != null){
                    if(!in_array($v[1], $_Now_arr)){
                        unset($data['data'][$k]);
                    }
                }
                
                $opHomeScore = explode('-',$v[24]);//主队加时比分
                $opAwayScore = explode('-',$v[25]);//客队加时比分
                if (count($opHomeScore) > count($opAwayScore))
                {
                    array_push($opAwayScore,0);
                }
                elseif (count($opHomeScore) < count($opAwayScore))
                {
                    array_push($opHomeScore,0);
                }
                elseif (empty($opHomeScore) && empty($opAwayScore) && $v[23] > 0)
                {
                    $opHomeScore = [0];
                    $opAwayScore = [0];
                }
                $v[24] = $opHomeScore;//主队加时比分
                $v[25] = $opAwayScore;//客队加时比分
          
            }
            if($_Now==null){
                $sche='';
                foreach ($league as $k=>$v){
                        $sche.= $k.',';
                }
                $sche=  substr($sche, 0,-1);
                cookie('NowBk',$sche);
            }
            //级别筛选
            ksort($league);
            $this->assign('league',$league);
        }
 
        $this->assign('chioce',cookie('NowBk'));
        $this->assign('language',cookie('language'));
        $this->assign('list',$data['data']);
        cookie('userUrl', __SELF__);
        cookie('detailsUrl', __SELF__);
        $this->display();
    }
    
    public function ScoreInstant(){
        $data=$this->get_curl(C('API_URL')."/change",'key=no',C('CURL_DOMAIN'));
        
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }
    public function goal(){
        $data=$this->get_curl(C('API_URL')."/goal",'id=3&key=no',C('CURL_DOMAIN'));
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }
    
    public function BkScoreChange(){
        //获取即时页面数据
        $data=$this->get_curl(C('API_BKURL')."/bkchange",'key=no',C('CURL_DOMAIN'));
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        $this->success($data['data']);
    }
    /**
     * 篮球今日赔率变化数据
     * @User liangzk <liangzk@qc.com>
     * $DateTime 2016-10-281:56
     * @return json ----具体查看全球体育网接口文档_赛事数据相关_篮球
     */
    public function bkGoalChange()
    {
        $data=$this->get_curl(C('API_BKURL')."/bkodds",'companyId=3&key=no',C('CURL_DOMAIN'));//取皇冠的数据
        if($data['status']==0 || empty($data['data'])){
            $this->error("没有数据！");
        }
        foreach ($data['data'] as $k => $v)
        {
            $v[0] = number_format($v[0],2);
            $v[2] = number_format($v[2],2);
            $v[3] = number_format($v[5],2);
            $v[5] = number_format($v[5],2);
        }
        $this->success($data['data']);
    }
}