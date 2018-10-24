<?php

/**
 * 新闻
 *
 * @author chenzj <443629770@qq.com>
 *
 * @since  2016-04-19
 */
class GuessController extends CommonController {
    protected function _initialize() {
        parent::_initialize();
        $user=session('user_auth');
        if($user){
            $this->assign('user_auth',$user);
        }
        if(ACTION_NAME != 'new_put' && !IS_AJAX){
            cookie('playtype',null);
            cookie('ordertype',null);
        }
        if(ACTION_NAME != 'square' && !IS_AJAX){
            cookie('playtype2',null);
            cookie('wintype',null);
        }
         if(cookie('pageUrl')==null || cookie('pageUrl')==''){
             cookie('pageUrl','/Guess/new_put.html');
         }
         if(cookie('redirectUrl')==null || cookie('pageUrl')==''){
             cookie('redirectUrl','/Guess/new_put.html');
         }
    }
    public function index() {
        $type=I('get.type',2,'intval');
        $today = [date('m/d'),getWeek(date("w"))];
		$gambleType = I('gambleType',1,'int');//1：亚盘，2：竞彩
        list($game, $this->union) = D('GambleHall')->matchList($gambleType);
//        $date=strtotime('today');
//        foreach($game as &$val){
//            $poor=  (strtotime($val['game_date'])-$date)/3600/24;
//            switch ($poor) {
//                case 0:
//                    $val['poor']='今天';
//                    break;
//                case 1:
//                    $val['poor']='明天';
//                    break;
//                case -1:
//                    $val['poor']='昨天';
//                    break;
//                default:
//                    $val['poor']=date('m-d',  strtotime($val['game_date']));
//                    break;
//            }
//        }
        if($type==2){
            foreach($game as $k=>$v){
                $game_state[$k]=$v['game_state'];
                $gtime[$k]=$v['gtime'];
            }
            array_multisort($game_state, SORT_DESC, $gtime, SORT_ASC, $game);
            foreach ($game as $j=>$l){
                if(in_array($l['game_state'],array(1,2,3,4))){
                    array_push($game,$l);
                    unset($game[$j]);
                }
            }
        }
        $adver = @Think\Tool\Tool::getAdList($classId=20,20,4) ?: [];
        $this->assign('adver',$adver);
        $this->game=$game;
        $this->assign('date',date('Ymd'));
        $this->assign('today',$today);
        $this->assign('type',$type);
        $this->assign('gambleType',$gambleType);
        cookie('userUrl', __SELF__);
        cookie('redirectUrl', __SELF__);
		cookie('detailsUrl',null);
        $this->display();
    }
	
    //
	public function new_put()//如下全部为足球
	{
		
		cookie('userUrl', __SELF__);
		cookie('redirectUrl', __SELF__);
		cookie('pageUrl', __SELF__);
		$gambleType = I('gambleType',1,'int');//0:不分亚盘、竞彩 1；亚盘 2：竞彩
		$victoryType = I('victoryType',0,'int');//0:不筛选胜率 1；周胜率 2：高命中 3：连胜多
		$priceType = I('priceType',1,'int');//0:不筛选价格 1；价格从高到低 2：价格从低到高 3：，免费
		$lvType = I('lvType',0,'int');//0:不筛选价格 1；等级1 2：等级2 3：，等级3.....
		
		$login_user_id = is_login();//获取登录的用户id
		//当筛选我的关注
		if ($victoryType == 5 && !$login_user_id)
		{
			$this->ajaxReturn(['status'=>1111]);
		}
		
		if (! in_array($gambleType,[0,1,2]) || ! in_array($victoryType,[0,1,2,3,4,5]) || ! in_array($priceType,[0,1,2]) || $lvType > 9)
		{
			$this->ajaxReturn(['status'=>0]);
		}
		$limit = 10;//每页10条
		$page = I('page',1,'int');//页码
//		$gemeTime = getBlockTime(1,true);//获取赛事分割日期的区间时间
//
//		$gameFbArr = M('GameFbinfo')
//			->field('union_id,union_name')
//			->where(['gtime'=>['between',[$gemeTime['beginTime'],$gemeTime['endTime']]],'game_state'=>['in',[0,1,2,3,4]]])
//			->group('union_id')
//			->select();
		$gameFbArr = D('common')->getMatchList()[$gambleType];
		
		//获取赛事名称
		foreach ($gameFbArr as $key => $value)
		{
			$unionNameArr[$value['union_id']] = substr($value['union_name'],0,stripos($value['union_name'],','));
		}
		
		//获取筛选赛事的cookie,并更新或更换赛事筛选---当用户没筛选时默认选中全部
		$unionIdString = cookie('newPut_unionId');
	
		if ($unionIdString != '-1')
		{
			$unionIdArr = explode(',',$unionIdString);
			foreach ($gameFbArr as $key => $value)
			{
				foreach ($unionIdArr as $k => $v)
				{
					if ((int)$value['union_id'] === (int)$v)
					{
						$newUnionId[$key] = $value['union_id'];
					}
				}
			}
		}
		
		if (empty($newUnionId))
		{
			$newUnionId = get_arr_column($gameFbArr,'union_id');
		}
		
		cookie('newPut_unionId',implode(',',$newUnionId));//把赛事ID保存到cookie
		
		$screenList = $this->getScreeningList($gambleType,$victoryType,$lvType,$login_user_id);
		$userIdArr = $screenList['userIdArr'];//筛选后的用户ID
		$rankList = $screenList['rankList'];//周榜等列表
		
		$blockTime = getBlockTime(1, true);//获取推荐分割日期的区间时间
		if ($login_user_id > 0)
		{
			$fieldName = 'g1.id,g1.user_id,g1.game_id,g1.union_id,g1.union_name,g1.home_team_name,g1.away_team_name,g1.game_date,g1.game_time,
						g1.play_type,g1.chose_side,g1.odds,g1.score,g1.handcp,g1.tradeCoin,g1.create_time,g1.desc';
		}
		else
		{
			$fieldName = 'g1.id,g1.user_id,g1.game_id,g1.union_id,g1.union_name,g1.home_team_name,g1.away_team_name,
						g1.game_date,g1.game_time,g1.play_type,g1.tradeCoin,g1.score,g1.create_time';
		}
		$gambleTypeWhere = array();//默认不分亚盘、竞彩
		switch ($gambleType)
		{
			case 1:
				$gambleTypeWhere = ['g1.play_type'=>['IN',['1','-1']]];
//				$querySqlWhere = ['play_type'=>['IN',['1','-1']]];
				break;//亚盘足球
			case 2:
				$gambleTypeWhere = ['g1.play_type'=>['IN',['2','-2']]];
//				$querySqlWhere = ['play_type'=>['IN',['2','-2']]];
				break;//竞彩足球
		}
		
		//获取竞彩列表
//		$querySql = M('Gamble')
//				->where(['user_id'=>['IN',$userIdArr],
//						 'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
//						 'result'=>0])
//				->where($querySqlWhere)
//				->where(['union_id'=>['IN',$newUnionId]])
//				->field('user_id,MAX(create_time) AS create_time')
//				->group('user_id')
//				->buildSql();
		if ($victoryType === 0 && $priceType === 0)//判断是否对对胜率、价格筛选
		{
			//当不对胜率、价格筛选时，只按发布时间排序
			$gambleList = M('Gamble g1')
				->master(true)
//				->join('INNER JOIN '.$querySql.' g2 ON g1.user_id = g2.user_id and g1.create_time = g2.create_time')
				->where(['g1.user_id'=>['in',$userIdArr],
						 'g1.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
						 'g1.result'=>0])
				->where($gambleTypeWhere)
				->where(['g1.union_id'=>['IN',$newUnionId]])
				->field($fieldName)
				->order('g1.id desc')
				->page($page . ',' . $limit)
				->select();
		}
		else
		{
			
			$gambleList = M('Gamble g1')
						->master(true)
//						->join('INNER JOIN '.$querySql.' g2 ON g1.user_id = g2.user_id and g1.create_time = g2.create_time')
						->where(['g1.user_id'=>['in',$userIdArr],
								 'g1.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
								 'g1.result'=>0])
						->where($gambleTypeWhere)
						->where(['g1.union_id'=>['IN',$newUnionId]])
						->field($fieldName)
						->select();
		}
		
		if (!empty($gambleList)) $gambleList = HandleGamble($gambleList);
		if ($login_user_id > 0)
		{
			$gambleIdArr = get_arr_column($gambleList,'id');
			$userQuizLog = M('QuizLog')
				->master(true)
				->where(['user_id'=>$login_user_id, 'gamble_id'=>['in',$gambleIdArr],'game_type'=>1])
				->field('cover_id')
				->select();
			foreach ($gambleList as $key => $value)
			{
				foreach ($userQuizLog as $k => $v)
				{
					if ($value['user_id'] === $v['cover_id'])
					{
						$gambleList[$key]['is_quiz'] = 1;
						break;
					}
				}
				if ($login_user_id == $value['user_id'])
				{
					$gambleList[$key]['is_quiz'] = 1;
				}
				
				
			}
		}
		
		//获取赛事颜色
		$unionIdArr = get_arr_column($gambleList,'union_id');
		$unionColor = M('Union')->master(true)->field('union_id,union_color')->where(['union_id'=>['IN',$unionIdArr]])->select();
		foreach ($gambleList AS $key => $value)
		{
			if ($value['is_quiz'] !== 1)
			{
				$gambleList[$key]['chose_side'] = null;
				$gambleList[$key]['odds'] = null;
				$gambleList[$key]['handcp'] = null;
				$gambleList[$key]['Answer'] = null;
				$gambleList[$key]['desc'] = null;
			}
			//添加用户信息
			foreach ($rankList as $k => $v)
			{
				//亚盘
				if ($v['user_id'] === $value['user_id'] && ( $value['play_type'] === '-1' || $value['play_type'] === '1')) {
					$gambleList[ $key ]['curr_victs'] = $v['curr_victs'];
					$gambleList[ $key ]['face'] = $v['face'];
					$gambleList[ $key ]['lv'] = $v['lv'];
					$gambleList[ $key ]['nick_name'] = msubstr($v['nick_name'], 0, 6);
					$gambleList[ $key ]['tenGambleRate'] = $v['tenGambleRate'];
					$gambleList[ $key ]['win'] = $v['win'];
					$gambleList[ $key ]['winrate'] = $v['winrate'];
					$gambleList[ $key ]['quizCount'] = $v['quizCount'];
					
				}
				//竞彩
				if ($v['user_id'] === $value['user_id'] && ( $value['play_type'] === '-2' || $value['play_type'] === '2'))
				{
					$gambleList[$key]['curr_victs'] = $v['curr_victs'];
					$gambleList[$key]['face'] = $v['face'];
					$gambleList[$key]['lv'] = $v['lv_bet'];
					$gambleList[$key]['nick_name'] = msubstr($v['nick_name'],0,6);
					$gambleList[$key]['tenGambleRate'] = $v['tenGambleRate'];
					$gambleList[$key]['win'] = $v['win'];
					$gambleList[$key]['winrate'] = $v['winrate'];
					$gambleList[$key]['quizCount'] = $v['quizCount'];
					$gambleList[$key]['quizCount'] = $v['quizCount'];
					
				}
				
				$gambleList[$key]['gDate'] = date('m/d',strtotime($value['game_date'])).' '.$value['game_time'];//比赛时间
				
			}
			//添加赛事颜色
			foreach ($unionColor as $k => $v)
			{
				if ($value['union_id'] === $v['union_id'])
				{
					$gambleList[$key]['union_color'] = $v['union_color'];
					break;
				}
			}
			//转换比赛日期格式
			$gambleList[$key]['game_date'] = date('m/d',strtotime($value['game_date']));
			$gambleList[$key]['create_time'] = date('m/d H:i',$value['create_time']);
			
		}
		
		//排序
		$priceArr = $quizCountArr = $winrateArr = $tenGambleArr = $currVictsArr = $createTimeArr = $userArr = $lvArr = $lvBetArr = array();
		foreach ($gambleList as $key => $value)
		{
			$winrateArr[] 		= $value['winrate'];
			$tenGambleArr[] 	= $value['tenGambleRate'];
			$currVictsArr[] 	= $value['curr_victs'];
			$createTimeArr[] 	= $value['create_time'];
			$userArr[] 			= $value['user_id'];
			$priceArr[]			= $value['tradeCoin'];
			$lvArr[]			= $value['lv'];
			$lvBetArr[]			= $value['lv_bet'];
			$quizCountArr[]			= $value['quizCount'];
		}
		
		//价格的排序
		$priceOrder = $priceType === 1 ? SORT_DESC : SORT_ASC ;
		
		if ($victoryType > 0)//对胜率排序
		{
			
			switch ($victoryType)
			{
				//按周胜率＞等级高低
				case 1:
					if ($priceType > 0)
					{
						array_multisort(
							$priceArr,$priceOrder,
							$gambleList);
					}
					else
					{
						array_multisort($winrateArr,SORT_DESC,
							$gambleType === 1 ? $lvArr : $lvBetArr,SORT_DESC,
							$gambleList);
					}
					break;
				//按10中几＞周胜率＞等级
				case 2:
					if ($priceType > 0)
					{
						array_multisort($priceArr,$priceOrder, $gambleList);
					}
					else
					{
						array_multisort($tenGambleArr,SORT_DESC,
									$winrateArr,SORT_DESC,
									$gambleType === 1 ? $lvArr : $lvBetArr,SORT_DESC,
									$gambleList);
					}
					break;
					
					//按最大连胜＞月胜率高低＞等级高低
				case 3:
					if ($priceType > 0)
					{
						array_multisort($priceArr,$priceOrder, $gambleList);
					}
					else
					{
						array_multisort($currVictsArr,SORT_DESC,
								$winrateArr,SORT_DESC,
								$gambleType === 1 ? $lvArr : $lvBetArr,SORT_DESC,
								$gambleList);
					}
					break;
				case 4 :
					if ($priceType > 0)
					{
						array_multisort($priceArr,$priceOrder, $gambleList);
					}
					else
					{
						array_multisort($quizCountArr,SORT_DESC,
							$winrateArr,SORT_DESC,
							$gambleType === 1 ? $lvArr : $lvBetArr,SORT_DESC,
							$gambleList);
					}
					break;
				case 5:
					if ($priceType > 0)
					{
						array_multisort($priceArr,$priceOrder, $gambleList);
					}
					else
					{
						array_multisort($createTimeArr,SORT_DESC,$gambleList);
					}
					break;
			}
		}
		elseif ($priceType > 0)//对价格排序
		{
			//价格低规则：按价格从低到高的顺序排序 价格高规则：按价格从高到低的顺序排序
			array_multisort($priceArr,$priceOrder, $gambleList);
		}
		
		if ($victoryType > 0 || $priceType > 0)//当筛选了胜率或价格时
		{
			$gambleList = array_chunk($gambleList,$limit)[$page-1];
		}
		
		if ($gambleType === 2)
		{
			//获取竞彩的标志码
			$gameIdArr = get_arr_column($gambleList,'game_id');
			$betCodeArr = M('GameFbinfo')->master(true)->where(['game_id'=>['IN',$gameIdArr]])->field('game_id,bet_code')->select();
			
			foreach ($gambleList as $key => $value)
			{
				foreach ($betCodeArr as $k => $v)
				{
					if ($value['game_id'] == $v['game_id'])
					{
						$gambleList[$key]['bet_code'] = $v['bet_code'];
						break;
					}
				}
				
			}
		}
		
		$this->assign('gambleType',$gambleType);
		$this->assign('victoryType',$victoryType);
		$this->assign('priceType',$priceType);
		$this->assign('unionNameArr',$unionNameArr);
		$this->assign('unionIdArr',$newUnionId);
		
		if (IS_AJAX)
		{
			$this->ajaxReturn(['status'=>1,
							   'list'=>$gambleList,
							   'login_user_id'=>$login_user_id,
							   'unionNameArr'=>$unionNameArr,
							   'unionIdArr'=>$newUnionId,
							   'date'=>$gambleType]);
		}
		
		$this->display();
	}
	
	/**
	 * Liangzk <Liangzk@qc.com>
	 * @DateTime 2017-05-8
	 * 用于大咖广场
	 */
	public function getScreeningList($gambleType,$victoryType,$lvType,$login_user_id)
	{
		if ($victoryType === 3)//当筛选了连胜多时
		{
			switch ($gambleType)//获取排行榜
			{
				case 1: $rankList = D('Base')->getRankUserHundred(2,1,200,200); break;//亚盘足球月榜前200名；---方法内已经统一缓存
				case 2: $rankList = D('Base')->getRankUserRaceHundred(2,1,200,200); break;//竞彩足球月榜前200名；---方法内已经统一缓存
			}
			
		}
		else
		{
			switch ($gambleType)//获取排行榜
			{
				case 1: $rankList = D('Base')->getRankUserHundred(1,1,100,100); break;//亚盘足球周榜前100名；---方法内已经统一缓存
				case 2: $rankList = D('Base')->getRankUserRaceHundred(1,1,100,100); break;//竞彩足球周榜前100名；---方法内已经统一缓存
			}
			
		}
		
		//等级筛选
		if ($lvType > 0)
		{
			foreach ($rankList as $key => $value)
			{
				if ($gambleType == 1)
				{
					if ($lvType != $value['lv']) unset($rankList[$key]);
				}
				else
				{
					if ($lvType != $value['lv_bet']) unset($rankList[$key]);
				}
			}
		}
		
		//根据筛选条件获取用户ID
		$userIdArr = array();
		if ($victoryType === 2)//当筛选了高命中时
		{
			foreach ($rankList as $key => $value)
			{
				if ($value['tenGambleRate'] > 5)
				{
					$userIdArr[] = $value['user_id'];//获取十中6及以上的用户ID
				}
			}
		}
		elseif ($victoryType === 3)//当筛选了连胜多时
		{
			foreach ($rankList as $key => $value)
			{
				if ($value['curr_victs'] > 1)
				{
					$userIdArr[] = $value['user_id'];//获取连胜的用户ID
				}
			}
		}
		elseif ($victoryType == 4)//人气旺--前三天有销售量（不免费）的用户
		{
			$userIdArr = get_arr_column($rankList,'user_id');
			
			$quizRes = M('QuizLog')
				->where([
					'cover_id'=>['IN',$userIdArr],
					'log_time'=>['between',[strtotime(date('Y-m-d',strtotime('-3 Day'))),strtotime(date('Y-m-d'))]],
					'coin'=>['GT',0]
				])
				->field('cover_id,count(id) as quizCount')
				->group('cover_id')
				->select();
			
			unset($userIdArr);
			
			$userIdArr = array();
			foreach ($quizRes as $key => $value)
			{
				$userIdArr[] = $value['cover_id'];
				foreach ($rankList as $k => $v)
				{
					if ($value['cover_id'] == $v['user_id'])
					{
						$rankList[$k]['quizCount'] = $value['quizCount'];//获取前三天销售量
						break;
					}
				}
			}
			
		}
		elseif ($victoryType == 5)//我的关注
		{
			//获取登录用户关注的用户ID
			$followUserArr = M('FollowUser')->where(['user_id'=>$login_user_id])->getField('follow_id',true);
			if (!empty($followUserArr))
			{
				foreach ($rankList as $key => $value)
				{
					if (in_array($value['user_id'],$followUserArr))
					{
						$userIdArr[] = $value['user_id'];//获取连胜的用户ID
					}
				}
			}
			
		}
		else
		{
			$userIdArr = get_arr_column($rankList,'user_id');
		}
		
		return ['rankList'=>$rankList,'userIdArr'=>empty($userIdArr) ? array() : $userIdArr];
	}
	
	
    /**
     *  高手推荐模型
     */
    public function getMasterGamble($user_id, $sortType, $pageSize, $pageNum){

        switch ($sortType)
        {
            case 'highHit':           $result = $this->rankGambleList($sortType, 1, 100, $user_id, $pageSize, $pageNum);      break;
            case 'winMore':           $result = $this->rankGambleList($sortType, 2, 200, $user_id, $pageSize, $pageNum);      break;
            case 'popularityHigh':    $result = $this->rankGambleList($sortType, 1, 100, $user_id, $pageSize, $pageNum);      break;
            case 'levelHigh':         $result = $this->getList($sortType, $user_id, 1, $pageSize, $pageNum);      break;
            case 'weekRate':          $result = $this->getList($sortType, $user_id, 1, $pageSize, $pageNum);      break;
            case 'priceHigh':         $result = $this->getList($sortType, $user_id, 1, $pageSize, $pageNum);      break;
            case 'priceLow':          $result = $this->getList($sortType, $user_id, 1, $pageSize, $pageNum);      break;
            default: $result = array();
        }

        return $result;
    }

    /**
     * 获取排行榜的数据
     * @param $sortType string 排序类型
     * @param $dateType int 榜类型 1：周， 2：月， 3：季
     * @param $num int 查询数量
     * @param $user_id string 用户口令
     * @param $pageSize int
     * @param $pageNum int
     * @return array
     */
    public function rankGambleList($sortType, $dateType, $num, $user_id, $pageSize, $pageNum){
        $rankDate = getRankDate($dateType);//获取上个周期的日期
        $countNum = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->count();

        if (!$countNum){
            $rankDate = getTopRankDate($dateType);//获取上个周期的数据
        }

        $arr = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->order('ranking ASC')->limit($num)->getField('user_id, winrate', true);
        $userArr = array_keys($arr);
        $rateArr = array();

        foreach($userArr as $k => $v){
            if($sortType == 'highHit'){
                $tenGamble = D('GambleHall')->getTenGamble($v, 1);
                $tenGambleRate = countTenGambleRate($tenGamble);//近十场的胜率;
                //要10中6的或以上
                if($tenGambleRate < 60){
                    unset($userArr[$k], $arr[$v]);
                    continue;
                }
                $rateArr[$v] = $tenGambleRate;
            }else if($sortType == 'winMore'){
                $winnig = D('GambleHall')->getWinning($v, $gameType=1); //连胜记录
                //连胜2以上
                if($winnig['curr_victs'] < 2){
                    unset($userArr[$k], $arr[$v]);
                    continue;
                }
                $rateArr[$v] = $winnig['curr_victs'];//连胜场数
            }else if($sortType == 'popularityHigh'){
                $rateArr[$v] = M('QuizLog')->where(['cover_id' => $v, 'game_type' => 1, 'log_time' => ['between', [strtotime(date('Y-m-d 00:00:00', strtotime('-3 day'))), strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')))]]])->count();
            }
        }

        array_multisort(array_values($rateArr), SORT_ASC, array_values($arr), SORT_ASC, $userArr);
        $order = ' field(g.user_id, '.implode(',', $userArr).') DESC, u.lv DESC, g.tradeCoin DESC, sortTime ASC ';
        $blockTime = getBlockTime(1, $gamble = true);//获取推荐分割日期的区间时间

        //推荐赛程期间内，且未出结果的
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where['g.result'] = 0;
        $where['g.id'] = ['gt', 0];
        $where['g.user_id'] = ['in', $userArr];
        $fields = ' CONCAT(g.game_date, g.game_time) as sortTime, g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head as face, u.lv, qu.union_color ';

        $res = M('Gamble g')->field($fields)
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where)->group('gamble_id')->order($order)
                ->page($pageSize, $pageNum)->select();

        if(!empty($res)) {
            foreach ($res as $k => $v) {
                if ($sortType == 'highHit') {
                    $res[$k]['tenGambleRate'] = $rateArr[$v['user_id']];
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                    $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                    unset($winnig);
                } else if ($sortType == 'winMore') {
                    if(!$rateArr[$v['user_id']]){
                        $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                        $rateArr[$v['user_id']] = (string)$winnig['curr_victs'];//连胜场数
                    }
                    $res[$k]['curr_victs'] = $rateArr[$v['user_id']];
                    $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                    $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);//近十场的胜率;
                    unset($tenGamble);
                } else {
                    $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                    $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);//近十场的胜率;
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                    $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                    unset($tenGamble, $winnig);
                }

                $res[$k]['face'] = frontUserFace($v['face']);
                $res[$k]['weekPercnet'] = $arr[$v['user_id']] ?: (string)D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周榜
                $res[$k]['desc'] = (string)$v['desc'];

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($user_id) {
                    $res[$k]['is_trade'] = M('QuizLog')->where(['gamble_id' => $v['gamble_id'], 'user_id' => $user_id])->getField('id') ? 1 : 0;//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = 0;
                }
                unset($res[$k]['sortTime']);
            }
        }
        return $res;
    }

    /**
     * 数据列表：等级，周胜率，价格
     * @param $sortType string 排序类型
     * @param $user_id string 用户口令
     * @param $dateType int 榜类型 1：周， 2：月， 3：季
     * @param $pageSize
     * @param $pageNum
     * @return array
     */
    public function getList($sortType, $user_id, $dateType=1, $pageSize, $pageNum){
        $rankDate = getRankDate($dateType);//获取上个周期的日期
        $countNum = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->count();

        if (!$countNum){
            $rankDate = getTopRankDate($dateType);//获取上个周期的数据
        }

        $blockTime = getBlockTime(1, $gamble = true);//获取推荐分割日期的区间时间
        $where['l.dateType'] = $dateType;
        $where['l.gameType'] = 1;
        $where['l.begin_date'] = ['between', [$rankDate[0], $rankDate[1]]];
        $where['l.ranking'] = ['lt', 101];

        //推荐赛程期间内，且未出结果的
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where['g.result'] = 0;
        $where['g.id'] = ['gt', 0];
        $fields = ' CONCAT(g.game_date, g.game_time) as sortTime, g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head as face, u.lv, qu.union_color, l.winrate as weekPercnet ';

        switch ($sortType)
        {
            case 'levelHigh':   $order = ' u.lv DESC, l.winrate DESC, sortTime ASC ';  break;
            case 'weekRate':    $order = ' l.winrate DESC, sortTime ASC ';             break;
            case 'priceHigh':   $order = ' g.tradeCoin DESC, l.winrate DESC, u.lv DESC, sortTime ASC '; break;
            case 'priceLow':    $order = ' g.tradeCoin ASC, l.winrate DESC, u.lv DESC, sortTime ASC '; break;
            default:            $order = ' l.ranking ASC, sortTime ASC ';  break;
        }

        $res = M('Gamble g')->field($fields)
                ->join(' LEFT JOIN qc_ranking_list AS l ON l.user_id = g.user_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where)->order($order)->page($pageSize, $pageNum)->select();

        if(!empty($res)) {
            foreach ($res as $k => $v) {
                $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                $res[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);//近十场的胜率;
                $res[$k]['face'] = frontUserFace($v['face']);
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                $res[$k]['desc'] = (string)$v['desc'];

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($user_id) {
                    $res[$k]['is_trade'] = M('QuizLog')->where(['gamble_id' => $v['gamble_id'], 'user_id' => $user_id])->getField('id') ? 1 : 0;//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = 0;
                }

                unset($tenGamble, $winnig, $res[$k]['sortTime'], $res[$k]['winrate']);
            }
        }

        return $res;
    }

    //排行榜
    public function rank()
	{
        $dateType = I('dateType',4,'intval');
        $m_quiz = cookie('m_quiz');
        $page=I('page',1,'intval');
        $limit=100;//每次获取30条
		//亚盘、竞彩的标识
		$gambleType = I('gambleType',1,'int');//1:亚盘，2：竞彩
		
		if (! in_array($gambleType,[1,2]))
		{
			$this->error('参数出错!');
		}
        $is_login = is_login();
		if ($gambleType === 1)
		{
			//获取排行榜
			$myRank=[];
			if ($dateType == 4) //亚盘日榜
			{
				list($myRank,$ranking) = $this->getRedList(1,$page,$limit);
				if (empty($myRank['ranking']))
				{
					$myRank['ranking'] = '未上榜';
				}
				else
				{
					$myRank['ranking'] = $myRank['ranking'] > 1000 || empty($myRank['ranking']) ? '未上榜' : intval($myRank['ranking']).'名';
				}
				
			}
			else
			{
				$ranking = D('GambleHall')->getRankingData(1,$dateType,null,false,$page,$limit);
				//当加载第二页就不用在获取个人排排名
				if ($page < 2)
				{
					if($is_login > 0)
					{
						//登录用户的排名
						$myData = D('GambleHall')->getRankingData(1,$dateType,$is_login,false,$page,$limit);
						$myRank['ranking'] = $myData[0]['ranking'] > 1000 || empty($myData[0]['ranking']) ? '未上榜' : intval($myData[0]['ranking']).'名';
						$myRank['win']=$myData[0]['win']+$myData[0]['half'];//赢的场数
						$myRank['level']=$myData[0]['level'];//
						$myRank['transport']=$myData[0]['transport']+$myData[0]['donate'];
						$myRank['winrate']=$myData[0]['winrate'];
						$myRank['pointCount']=$myData[0]['pointCount'];
					}
				}
				
			}
			
			
		}
		elseif ($gambleType === 2)//竞彩--足球
		{
			if($dateType == 4)//日榜
			{
				$where['listDate']   = date('Ymd', strtotime("-1 day"));
			}
			else
			{
				list($begin,$end)  = getRankDate($dateType);
				$where['listDate']   = $end;//周、月、季
			}
			$where['r.gameType']    = 1; //1；足球 2：篮球
			$where['r.dateType']    = $dateType;//日、周、月、季
			//查看是否有上周/月/季的数据
			$rankBetCount = M('RankBetting r')->where($where)->count();
			
			if ($rankBetCount < 1)//判断是否有数据，否则扩大时间范围
			{
				if($dateType == 4)
				{
					$where['listDate']   = date('Ymd', strtotime("-2 day"));
				}
				else
				{
					list($begin,$end)  = getTopRankDate($dateType);
					$where['listDate']   = $end;
				}
			}
			//足球竞彩胜率排行榜
			$ranking = M('RankBetting r')
						->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
						->field('r.id,r.user_id,r.ranking,r.win,r.gameCount,r.transport,r.winrate,r.pointCount,f.nick_name,f.head')
						->where($where)
						->order('r.ranking asc')
						->page($page . ',' . $limit)
						->select();
			
			//当加载第二页就不用再获取个人排排名
			if ($page < 2)
			{
				if($is_login > 0)
				{
					//登录用户的排名
					$myRank = M('RankBetting r')
							->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
							->field('r.id,r.user_id,r.ranking,r.win,r.gameCount,r.transport,r.pointCount,r.winrate,f.nick_name,f.head')
							->where($where)
							->where(['r.user_id'=>$is_login])
							->find();
					
					if(! empty($myRank))
					{
						$myRank['ranking'] = $myRank['ranking'] > 1000 ? '未上榜' : $myRank['ranking'].'名';
					}
					else
					{
						$myRank['ranking'] = '未上榜';
					}
				}
			}
		}
     	
        $blockTime   = getBlockTime(1,true);
		$userIdArr = get_arr_column($ranking,'user_id');
		$gambleQuiz = M('Gamble')
					->where(['user_id'=>['IN',$userIdArr],
							 'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]])
					->field('id,user_id')
					->select();
		//获取登录用户连胜
		$myRank['curr_victs'] = D('GambleHall')->getWinning($is_login,1,0,$gambleType)['curr_victs'];
		
        foreach ($ranking as $k => $v)
        {
        	//获取连胜
			$ranking[$k]['curr_victs'] = D('GambleHall')->getWinning($v['user_id'],1,0,$gambleType)['curr_victs'];
				
            $ranking[$k]['face'] = frontUserFace($v['head']);
            //今天是否有最近推荐
			foreach ($gambleQuiz as $key => $value)
			{
				if ($v['user_id'] === $value['user_id'])
				{
					$ranking[$k]['is_quiz'] = $value['id'];
				}
			}
        }
        
//        if($is_login > 0)
//        {
//            $followIdArr = M("FollowUser")->where(array('user_id'=>$is_login))->field("follow_id")->select();
//            foreach ($followIdArr as $value)
//            {
//                $followIds[] = $value['follow_id'];
//            }
//            array_unique($followIds);
//        }
		$this->assign('nav','rank');
		
		if (IS_POST && IS_AJAX)
		{
			$this->ajaxReturn(['status'=>1, 'list' => $ranking,
							   'my_rank'=>$myRank,
							   'm_quiz'=>$m_quiz,
								'is_login'=>$is_login
							  ]);
		}

        cookie('userUrl', __SELF__);
        cookie('pageUrl', __SELF__);
        cookie('redirectUrl', __SELF__);
		
        $this->display();
    }
    
    //关注
    public function focus(){
        $type=I('post.type','');
        $id=I('post.id','');
        if($type === '' || $id === ''){
           $this->ajaxReturn(['status'=>0,'info'=>'参数有误!']); 
        }
        $login=is_login();
        if(!$login){
            $this->ajaxReturn(['status'=>-1,'info'=>'请先登录!','url'=>U('User/login')]);
        }
        if($type==1){
            if($id == $login){
                $this->ajaxReturn(['status'=>0,'info'=>'您不能关注自己噢!']);
            }
            $rs = M('followUser')->add(array('user_id'=>is_login(),'follow_id'=>$id,"follow_time"=>time()));
        }else{
            $rs = M('followUser')->where(array('follow_id'=>$id,'user_id'=>is_login()))->delete();
        }
        if($rs){
            $this->ajaxReturn(['status'=>1,'info'=>'操作成功!']);
            $this->success('操作成功!');
        }else{
            $this->ajaxReturn(['status'=>1,'info'=>'操作失败!']);
        }
    }


    //兑换中心
    public function exchange() {
        if (IS_AJAX){
            parent::exchange();
            return;
        }
		cookie('redirectUrl', __SELF__);
        $userid = is_login();
        $userInfo = M('FrontUser')->where(['id'=>$userid])->field('nick_name,coin,unable_coin,point,head')->find();
        $this->point=$userInfo['point'];
        if($userInfo){
            $userInfo['count_coin']=$userInfo['coin']+$userInfo['unable_coin'];
            $userInfo['head'] = frontUserFace($userInfo['head']);
            $this->assign('userInfo',$userInfo);
        }
        $banner=$this->get_recommend('exchange',4);
        $prize=M('Prize')->field('name,coin,valid,img')->where(array('status'=>1))->order('sort')->select();
        foreach ($prize as &$v){
            $v['img']=@Think\Tool\Tool::imagesReplace($v['img']);
        }
        $this->assign('prize',$prize);
        $this->assign('banner',$banner);
        $config = getWebConfig(['platformSetting', 'ticket']);
        $this->assign('config',$config['platformSetting']);

		//兑换中心的优惠券配置信息
		$ticketConfig = $config['ticket'] ?: '';

		if($ticketConfig){
			$sort = [];
			foreach($ticketConfig as $k => $v){
				//排除已禁用
				if($v['status'] == 0){
					unset($ticketConfig[$k]);
					continue;
				}

				$sort[] = $v['sort'];
				$ticketConfig[$k]['url'] = (string)Think\Tool\Tool::imagesReplace($v['url']);
				unset($ticketConfig[$k]['status'], $ticketConfig[$k]['sort']);
			}

			array_multisort($sort, SORT_ASC, $ticketConfig);
			unset($sort);
		}

		$this->assign('ticketConfig', $ticketConfig);
        cookie('userUrl', __SELF__);
        $this->display();
    }
    
    //显示推荐层
    public function show_guess(){
        if (!IS_AJAX)
            return;
        $user_id=is_login();
        if(!$user_id){
            $this->error('请先登录!',U('User/login'));
        }
        $this->success('success');
    }
    //提交推荐
    public function do_guess()
	{
        if (!IS_AJAX)
            return;
        $user_id=is_login();
        if(!$user_id){
            $this->error('请先登录！');
        }
        
        $play_type = I('play_type','','string');
        $chose_side = I('chose_side','','string');
        $handcp = I('handcp','','string');
        $game_id = I('game_id',0,'int');
		$desc = trim(I('desc','','string'));
		
		if ($play_type === '' || $chose_side === '' || $handcp === '' || $game_id === 0 )
			$this->error('参数错误！');
		
		$tradeCoin = I('tradeCoin',0,'int');
		$tradeCoinConf = D('GambleHall')->gambleLeftTimes($user_id,1,$play_type)[2]['userLv'];//获取配置的可推荐的金额
		if (!in_array($tradeCoin,get_arr_column($tradeCoinConf,'letCoin')))//判断是否为合法金额
		{
			$this->error('参数错误！');
		}
		
        $res = D('GambleHall')->gamble($user_id,['play_type'=>$play_type,
												 'chose_side'=>$chose_side,
												 'game_id'=>$game_id,
												 'desc'=>$desc,
												 'tradeCoin'=>$tradeCoin,
												 'confirm'=>I('confirm',0,'int'),//为1时就不用判断盘口是否发生改变
												 'handcp'=>I('handcp','','string'),
												],4,1);
		if ($res === 2018)//盘口是否已经改变
		{
			$param =['play_type'=>$play_type,
					 'chose_side'=>$chose_side,
					 'game_id'=>$game_id,
			];
			//获取新盘口、赔率
			D('GambleHall')->getHandcpAndOdds($param);
			//亚盘盘口格式转换
			$newhHandcp = $play_type === '1' || $play_type === '-1' ? changeExp($param['handcp']) : $param['handcp'];
			
			if ($play_type === '1')//亚盘让球
			{
				if ($param['handcp'] < 0)
					$homeHandcp = changeExp(substr($param['handcp'],1));
				$this->success(['ststusCode'=>2018,'oddHandcp'=>$param['handcp'],'homeHandcp'=>$homeHandcp,
								'newhHandcp'=>$newhHandcp,'odds'=>$param['odds'],'odds_other'=>$param['odds_other']]);
			}
			elseif ($play_type === '-1')//亚盘大小
			{
				$this->success(['ststusCode'=>2018,'newhHandcp'=>$newhHandcp,'odds'=>$param['odds'],'odds_other'=>$param['odds_other']]);
			}
			elseif ($play_type === '-2')//竞彩让球
			{
				$jcGame =M('fbBetodds')
						->where(['game_id'=>$game_id])
						->field('let_exp,home_letodds,draw_letodds,away_letodds')
						->find();
				$this->success(['ststusCode'=>2018,'newhHandcp'=>$jcGame['let_exp'],
								'home_letodds'=>$jcGame['home_letodds'],'draw_letodds'=>$jcGame['draw_letodds'],
								'away_letodds'=>$jcGame['away_letodds']
								]);
			}
		}
		
		if ($res === 2004)
		{
			$this->success(['ststusCode'=>2004]);
		}
        if (is_numeric($res))
            $this->error(getErrorMsg($res));

        $this->success(['normLeftTimes'=>$res['normLeftTimes'],'imptLeftTimes'=>$res['imptLeftTimes'],'data'=>D('Base')->getGambleRatio($game_id)]);
    }
    
    //查看推荐（交易）
    public function trade()
    {
        $user_auth=  session('user_auth');
        if(!$user_auth) $this->error('请先登录!');

        $gamble_id = I('gamble_id');
        $isTicket = I('isTicket',0,'int');

        //执行交易
        $tradeRes = D('Common')->trade(
            $user_auth['id'],
            $gamble_id,
            4,
            1,
			$isTicket
        );
	
        if ($tradeRes['code'] != 'success' && $tradeRes['data'] == ''){
        	if ($tradeRes['code'] == 2008)
			{
				$this->error('2008');
			}
            $this->error(C('errorCode')[$tradeRes['code']]);
        }
	
		//修改体验券的状态
		if($isTicket && $tradeRes['ticket_id'])
		{
			M('TicketLog')->where(['id' => $tradeRes['ticket_id']])->save(['is_use' => 1, 'use_time' => NOW_TIME]);
		}
		
        //推荐记录信息
        $this->success($tradeRes['data']);
    }
    
    //ta的主页
    public function other_page()
    {
        $user_id  = I('user_id');
        D('Common')->setFrontSeeNum($user_id,'m');
		cookie('redirectUrl', __SELF__);
        $this->assign('user_auth',session('user_auth'));
        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 1; 
        $sort = I('type');
        $where['g.user_id'] = $user_id;
		$gambleType = in_array($sort,[1,-1]) || empty($sort)? 1 : 2;//1：亚盘  2：竞彩
		$this->assign('gambleType',$gambleType);
        if(!empty($sort))
        {
            if ($sort == '2' || $sort == '-2')
            {
                $where['g.play_type'] = ['IN',['2','-2 ']];
            }
            else
            {
                $where['g.play_type'] = $sort;
            }
        }
        //最新10条推荐
        $gamble = M('gamble g')
                ->join("LEFT JOIN qc_union u on u.union_id=g.union_id")
                ->field("g.id,g.user_id,g.game_id,g.union_name,g.game_date,g.game_time,g.home_team_name,g.score,g.away_team_name,g.handcp,g.odds,g.play_type,g.chose_side,g.result,g.tradeCoin,g.create_time,g.desc,u.union_color")
                ->where($where)->order("g.id desc")->page($p,10)->select();

        $gamble = HandleGamble($gamble,0,true);
     	$login_user_id = is_login();
        if($login_user_id)
        {
        	$gambleIdArr = $gameIdArr = array();
			foreach ($gamble as $key => $value)
			{
				$gameIdArr[] = $value['game_id'];
				$gambleIdArr[] = $value['id'];
				
			}
			//获取登陆用户查看记录
			$quizLogArr = M('quizLog')->master(true)->where(['user_id'=>is_login(),'gamble_id'=>['in',$gambleIdArr],'game_type'=>1])->field('gamble_id')->select();
            
			//获取推荐标志码
			if ($gambleType === 2)
			{
				$betCodeArr = M('GameFbinfo')->where(['game_id'=>['IN',$gameIdArr]])->field('game_id,bet_code')->select();
			}
			else
			{
				unset($gameIdArr);
			}
			
			foreach ($gamble as $k => $v)
            {
				//获取登陆用户查看记录
                if($v['user_id'] != $login_user_id && $v['result'] == 0) //是否已被查看
                {
                	foreach ($quizLogArr as $key => $value)
					{
						if ($v['id'] == $value['gamble_id'])
						{
							$gamble[$k]['is_trade'] = 1;
							break;
						}
					}
                }
                if ($v['user_id'] == $login_user_id)
				{
					$gamble[$k]['is_trade'] = 1;
				}
				//获取推荐标志码
				foreach ($betCodeArr as $key => $value)
				{
					if ($v['game_id'] == $value['game_id'])
					{
						$gamble[$k]['bet_code'] = $value['bet_code'];
						break;
					}
				}
            }
        }
        
        if(IS_AJAX)
        {
            //组装html
            $lis = '';
            foreach ($gamble as $k => $v) {
//                $playtype = $v['play_type'] == '1' ? '让球' : '大小球';
                switch ($v['play_type'])
                {
                    case '1':$playtype = '让球';break;
                    case '-1':$playtype = '大小球';break;
                    default:$playtype = '竞彩';
                }
                $score = $v['result'] == 0 ? 'VS' : "<strong style=\"color: red\">".str_replace('-','：',$v['score'])."</strong>";
                $li  =  "<li class=\"list\">".
                        "<div class=\"p_1\">".
                            "<div class=\"t_vs\"><em style=\"color: {$v['union_color']}\">{$v['union_name']}</em> {$v['home_team_name']} ".$score." {$v['away_team_name']}</div>".
                        "<div class=\"etip\">";
                        if($v['desc'] != ''){
                            $li .=  "<span><img src=\"/Public/Mobile/images/guess/fenxi.png\" alt=\"分析\"></span>";
                        }
                        if(($v['is_trade'] == 1 && $v['tradeCoin'] > 0) || ($v['result'] != 0 && $v['tradeCoin'] > 0)){
                            $li .=  "<span class=\"coins\">{$v['tradeCoin']}</span>";
                        }
                        if(($v['is_trade'] == 1 && $v['tradeCoin'] == 0) || ($v['result'] != 0 && $v['tradeCoin'] == 0)){
                            $li .=  "<span><img src=\"/Public/Mobile/images/guess/free.png\" alt=\"免费\"></span>";
                        }
                $li .=  "</div>".
                        "</div>".
                        "<p class=\"p_2\">".date('m/d',strtotime($v['game_date']))."  {$v['game_time']}</p>".
                        "<p class=\"p_3\">类型：<span>".$playtype."</span></p>";
                        if($v['is_trade'] == 1 || $v['user_id'] == is_login() || $v['result'] != 0)
                        {
                            switch ($v['result']) {
                                case '1'   : $class = "win";      break;
                                case '0.5' : $class = "win_half"; break;
                                case '2'   : $class = "ping";     break;
                                case '-1'  : $class = "lose";     break;
                                case '-0.5': $class = "lose_half";break;
                            }
                            $li .= "<div class=\"".$class."\"></div>";
                            $desc = $v['desc'] != '' ? $v['desc'] : '暂无分析';
							if ($v['play_type'] == '1' || $v['play_type'] == '-1')
							{
								$li .= "<p class=\"p_4\">推荐：<span>{$v['Answer']} {$v['handcp']}</span><em>（{$v['odds']}）</em></p>".
									"<p class=\"p_5 q-two\">分析：<span>".$desc."</span></p>";
							}
							else
							{
								$li .= "<p class=\"p_4\">推荐：<span>{$v['home_team_name']} ({$v['handcp']}) {$v['Answer']} </span><em>（{$v['odds']}）</em></p>".
									"<p class=\"p_5 q-two\">分析：<span>".$desc."</span></p>";
								
							}
                            
                        }
                        else
                        {
                            $li .= "<a href=\"javascript:;\" onclick=\"payment(this,{$v['id']},{$v['tradeCoin']}),4444\">";
                            if($v['tradeCoin'] == 0)
                            {
                                $li .= "<div class=\"gold2 bg_green\">免费</div>";
                            }
                            else
                            {
                                $li .= "<div class=\"gold2\">{$v['tradeCoin']}金币</div>";
                            }
                            $li .= "</a>";
                        }
                        $li .= "</li>";
                $lis .= $li;
            }
            $this->success($lis);
            exit;
        }
        //用户信息
        $userInfo = M('FrontUser f')
                    ->join("LEFT JOIN qc_follow_user o on o.follow_id=f.id")
                    ->where(['f.id'=>$user_id])
                    ->field('f.id,f.lv,f.lv_bet,f.nick_name,f.descript,f.head,count(o.id) follow')
                    ->find();
        $userInfo['head'] = frontUserFace($userInfo['head']);
//        $userInfo['WinNum'] = D('Common')->getWinNum($user_id);//十中几
        $this->assign('userInfo', $userInfo);
        //连胜---足球亚盘
        $winnig  = D('GambleHall')->getWinning($user_id); //推荐统计信息
        //Ta的推荐统计
        if (!empty($sort))
        {
            if ($sort == '2' || $sort == '-2')
            {
                $winnigCount  = D('GambleHall')->getWinning($user_id,1,0,2); //推荐统计信息
            }
            else
            {
                $winnigCount  = D('GambleHall')->getWinning($user_id,1,$sort,2); //推荐统计信息
            }
            $winnig['total_times'] = $winnigCount['total_times'];
        }
        $this->assign('winnig', $winnig);
        //连胜---足球竞彩
        $winnigRace  = D('GambleHall')->getWinning($user_id,1,0,2); //推荐统计信息
        $this->assign('winnigRace', $winnigRace);
        
        
        
        
        if (is_login())
        {
            //是否已关注
            $isFollow = M('FollowUser')->where(array('user_id'=>is_login(),'follow_id'=>$user_id))->find();
            $this->assign('isFollow',$isFollow);
        }
        //胜率----足球亚盘
        
        $win['week']   = D('GambleHall')->CountWinrate($user_id,1,1);  //周
        $win['month']  = D('GambleHall')->CountWinrate($user_id,1,2);  //月
        $win['season'] = D('GambleHall')->CountWinrate($user_id,1,3);  //季
        //胜率---足球竞彩
        $win['weekRace']   = D('GambleHall')->CountWinrate($user_id,1,1,false,false,0,2);  //周
        $win['monthRace']  = D('GambleHall')->CountWinrate($user_id,1,2,false,false,0,2);  //月
        $win['seasonRace'] = D('GambleHall')->CountWinrate($user_id,1,3,false,false,0,2);  //季
       
        
        $this->assign('type', $sort);
        $this->assign('user_id', $user_id);
        $this->assign('win', $win);
        $this->assign('gamble', $gamble);
        $this->display();
    }

    //大咖广场
    public function square(){
        //获取cookie排序数据
        $playtype = cookie('playtype2');  //玩法   1   -1
        $wintype  = cookie('wintype');    //榜类型 
        if($wintype == 3)
        {
            //红人榜
            $HostUser = D('Common')->getRedList(1,50);
        }
        else
        {
            //排行榜
            $dateType = $wintype == 6 ? 2 : 1; //默认周榜
            $HostUser = D('Common')->getRankingData(1,$dateType,null,50);
        }
        $blockTime = getBlockTime(1, $gamble = true);
        foreach ($HostUser as $k => $v) 
        {
        	$Winning = D('GambleHall')->getWinning($v['user_id']); //连胜
            $HostUser[$k]['WinCount'] = $Winning['win'];
            $HostUser[$k]['curr_victs'] = $Winning['curr_victs'];
            $WinNum  = D('Common')->getWinNum($v['user_id']);  //近10中几
            $HostUser[$k]['tennum'] = $WinNum['num'];
            $HostUser[$k]['tenwin'] = $WinNum['win'];
            $HostUser[$k]['weekWin']  = D('GambleHall')->CountWinrate($v['user_id'], 1, 1,false,false,$playtype);//周胜率
            $HostUser[$k]['monthWin'] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2,false,false,$playtype);//月胜率
            //当天最新一条
            $where['user_id']     = $v['user_id'];
            $where['create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
            if($playtype) $where['play_type'] = cookie('playtype2');  //是否分类筛选
            $HostUser[$k]['gamble'] = M('Gamble')->where($where)->order('id desc')->field("id,home_team_name,away_team_name")->find();
        }

        if($playtype) //筛选了类型没有最新推荐unset掉
        {
        	foreach ($HostUser as $k => $v) {
        		if(!$v['gamble'])
        			unset($HostUser[$k]);
        	}
        }
        
        if($playtype || in_array($wintype, [2,3,4,5]))   //根据筛选排序  
        {
            $sort = SORT_DESC;
            foreach ($HostUser as $k => $v) 
            {
                if($wintype == 3)
                {
                    if($playtype){
                        //红人榜排序
                        $lastData = D('Common')->YestWinrate($v['user_id'],1,$playtype); //昨日胜率
                        $HostUser[$k]['lastWin'] = $sort_win[] = $lastData['winrate'];
                    }else{
                        $sort_win[] = $v['ranking'];
                        $sort = SORT_ASC;
                    }
                }
                else
                {
                    //排行榜排序
                    switch ($wintype) {
                    	case '2': $sort_win[] = $v['lv'];         break;
                    	case '4': $sort_win[] = $v['curr_victs']; break;
                    	case '5': $sort_win[] = $v['tenwin'];     break;
                    	default:  $sort_win[] = $dateType == 1 ? $v['weekWin'] : $v['monthWin']; break;
                    }
                }
                $HostUser[$k]['gamble_id'] = $sort_time[] = $v['gamble']['id'];
            }
            //按胜率->最新发布倒序
            array_multisort($sort_win,$sort,$sort_time,SORT_DESC,$HostUser);
            unset($HostUser[$k]['gamble_id']);
            unset($HostUser[$k]['lastWin']);
        }

        $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
        $pageNum = 10;
        $HostUser = array_slice($HostUser, $p*$pageNum ,$pageNum);
        if(IS_AJAX)
        {
            //组装html
            $lis = '';
            foreach ($HostUser as $k => $v) 
            {
                $li  =  "<li class=\"list\">".
                        "<a href=\"".U('Guess/other_page@m',['user_id'=>$v['user_id']])."\">".
                        "<div class=\"rg_top\">".
                        "<div class=\"rgt_left\"><img src=\"{$v['face']}\"></div>".
                        "<div class=\"rgt_right\">".
                        "<p><span>{$v['nick_name']}</span><em class=\"lv lv{$v['lv']}\"></em> <em class=\"shengc\">胜场：{$v['WinCount']}</em></p>".
                        "<p>周胜率：{$v['weekWin']}%   月胜率：{$v['monthWin']}%</p>".
                        "<p>".
                        "<span>近{$v['tennum']}中{$v['tenwin']}</span>";
                if($v['curr_victs'] > 0)
                {
                    $li .=  "<em><img src=\"/Public/Mobile/images/xing.png\">{$v['curr_victs']}连胜</em>";
                }
                    $li .=  "</p>".
                            "</div>".
                            "</div>".
                            "<div class=\"rg_bottom\">";
                if(!empty($v['gamble']))
                {
                    $li .=  "最新推荐：".switchName(0,$v['gamble']['home_team_name'])." <span>VS</span> ".switchName(0,$v['gamble']['away_team_name']);
                }
                else
                {
                    $li .=  "<span>即将发布</span>";
                }
                    $li .=  "</div>".
                            "</a>".
                            "</li>";
                $lis .= $li;
            }
            $this->success($lis);
            exit;
        }
        $this->assign('HostUser', $HostUser);
        $this->assign('title', '大咖广场');
        $this->display();
    }

    //更多推荐
    public function more_quiz()
    {
        $user_id = I('user_id');
        //默认让球
        $playtype = I('playtype') ? I('playtype') : 1;
        $num = 20; //每页记录数
        if(IS_AJAX)
        {
            $p = isset($_POST['k']) ? intval(trim($_POST['k'])) : 0;
            $total = M('gamble')->where(['user_id'=>$user_id,'play_type'=>$playtype])->count(); //数据记录总数
            $totalpage = ceil($total / $num); //总计页数
            $limitpage = ($p - 1) * $num; //每次查询取记录
            if ($p > $totalpage) {
                //超过最大页数，退出
                $this->error("已经全部加载完毕");
            }
        }
        else
        {
            $limitpage = 0;
        }
        
        $gamble = M('gamble g')
                ->join("LEFT JOIN qc_union u on u.union_id=g.union_id")
                ->field("g.id,g.user_id,g.game_id,g.union_name,g.game_date,g.game_time,g.home_team_name,g.score,g.away_team_name,g.handcp,g.odds,g.play_type,g.chose_side,g.result,g.tradeCoin,g.create_time,g.desc,u.union_color")
                ->where(['g.user_id'=>$user_id,'g.play_type'=>$playtype])->order("g.id desc")->limit($limitpage,$num)->select();
        $gamble = HandleGamble($gamble);
        foreach ($gamble as &$v){
            if($v['play_type']==1){
                $v['handcp']=$v['chose_side']*-1*$v['handcp'];
                if($v['handcp']>0){
                    $v['handcp']='+'.$v['handcp'];
                }
            }
        }
        if(is_login())
        {
            foreach ($gamble as $k => $v) 
            {
                if($v['user_id'] != is_login() && $v['result'] == 0) //是否已被查看
                {
                    $gamble[$k]['is_check'] = M('quizLog')->where(['user_id'=>is_login(),'gamble_id'=>$v['id'],'game_type'=>1])->getField('id');
                }
            }
            $this->assign('user_auth',session('user_auth'));
        }
        if(IS_AJAX)
        {
            //组装html
            $lis = '';
            foreach ($gamble as $k => $v) {
                $playtype = $v['play_type'] == '1' ? '让球' : '大小球';
                $score = $v['result'] == 0 ? 'VS' : "<strong style=\"color: red\">".str_replace('-','：',$v['score'])."</strong>";
                $li  =  "<li class=\"list\">";
                if($v['tradeCoin']==0){
                    $li .= "<div class=\"free\"></div>";
                }
                
                        $li.="<p class=\"p_1\"><em style=\"color: {$v['union_color']}\">{$v['union_name']}</em> {$v['home_team_name']} ".$score." {$v['away_team_name']}</p>".
                        "<p class=\"p_2\">".date('m/d',strtotime($v['game_date']))."  {$v['game_time']}</p>".
                        "<p class=\"p_3\">类型：<span>".$playtype."</span></p>";
                        if($v['tradeCoin'] == 0) 
                            $li .= "<div class=\"free\"></div>";
                        if($v['is_check'] != '' || $v['user_id'] == is_login() || $v['result'] != 0)
                        {
                            switch ($v['result']) {
                                case '1'   : $class = "win";      break;
                                case '0.5' : $class = "win_half"; break;
                                case '2'   : $class = "ping";     break;
                                case '-1'  : $class = "lose";     break;
                                case '-0.5': $class = "lose_half";break;
                            }
                            $li .= "<div class=\"".$class."\"></div>";
                            $desc = $v['desc'] != '' ? $v['desc'] : '暂无分析';
                            $li .= "<p class=\"p_4\">推荐：<span>{$v['handcp']} {$v['Answer']}（{$v['odds']}）</span></p>".
                                   "<p class=\"p_5 q-two\">分析：<span>".$desc."</span></p>";
                        }
                        else
                        {
                            $li .= "<a href=\"javascript:;\" onclick=\"payment(this,{$v['id']},{$v['tradeCoin']})\">";
                            if($v['tradeCoin'] == 0)
                            {
                                $li .= "<div class=\"gold2 bg_green\">免费</div>";
                            }
                            else
                            {
                                $li .= "<div class=\"gold2\">{$v['tradeCoin']}金币</div>";
                            }
                            $li .= "</a>";
                        }
                        $li .= "</li>";
                $lis .= $li;
            }
            $this->success($lis);
            exit;
        }
        $this->assign('gamble', $gamble);
        $this->display();
    }
    //规则
    public function rules(){
        $this->display();
    }
    /**
     * 日、周、月、季积分盈利榜 --亚盘
     */
    public function profit()
    {
        $dateType = I('dateType',4,'intval');
		$m_quiz = cookie('m_quiz');
        $page=I('post.page',1,'intval');
        $pageNum    = 100;
        //$tdGamble   = $is_quiz ?$is_quiz: 0;
	
		//亚盘、竞彩的标识
		$gambleType = I('gambleType',1,'int');//1:亚盘，2：竞彩
		if (! in_array($gambleType,[1,2]))
		{
			$this->error('参数出错!');
		}
		
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $user_id=  is_login();
        switch (intval($dateType))
		{
            case 1: //周
            case 2: //月
            case 3: //季
                $endDate    = getRankDate($dateType)[1];
                $topEndDate = getTopRankDate($dateType)[1];
				
                $where      = ['r.listDate' => ["EQ", $endDate], 'r.dateType' => $dateType];
                $topWhere   = ['r.listDate' => ["EQ", $topEndDate], 'r.dateType' => $dateType];
                break;

            case 4: //日
                $where      = ['r.listDate' => date('Ymd', strtotime("-1 day")), 'r.dateType' => $dateType];
                $topWhere   = ['r.listDate' => date('Ymd', strtotime("-2 day")), 'r.dateType' => $dateType];
                break;

            default:
                $this->ajaxReturn(['status'=>0,'myRank' => [] , 'rankList' => []]);

        }
        
		
        if ($gambleType === 1)//亚盘盈利榜
		{
			$field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.gameCount',
				'r.pointCount','r.win','r.half','r.level','r.transport','r.donate','r.winrate'];
			$count = M('earnPointList r')->where($where)->count();
			
			if (!$count) $where = $topWhere;//当没有数据是2，扩大时间范围
			
			$rankLists = M('earnPointList r')
							->join('left join __FRONT_USER__ f on f.id = r.user_id')
							->field($field)
							->where($where)
							->order("r.ranking ASC")
							->page($page . ',' . $pageNum)
							->select();
			
			$myRank=[];
			if ($page < 2)//当加载第二页就不用在获取个人排排名---亚盘
			{
				if($user_id > 0)
				{
					
					$myRank = D('Common')->getProfitData(1,$dateType,$user_id); //我的排名
					$myRank=$myRank[0];
					$myRank['ranking'] = $myRank['ranking'] > 1000 || empty($myRank['ranking'])? '未上榜' : intval($myRank['ranking']).'名';
					$myRank['pointCount'] =  empty($myRank['pointCount'])? 0 : $myRank['pointCount'];
					$myRank['level'] =  empty($myRank['level'])? 0 : $myRank['level'];
					$myRank['winrate'] =  empty($myRank['winrate'])? 0 : $myRank['winrate'];
					$myRank['win'] =  empty($myRank['win'])? 0 : $myRank['win'];
					$myRank['transport'] =  empty($myRank['transport'])? 0 : $myRank['transport'];
					$myRank['half'] =  empty($myRank['half'])? 0 : $myRank['half'];
					$myRank['donate'] =  empty($myRank['donate'])? 0 : $myRank['donate'];
					
				}
			}
		}
		elseif ($gambleType === 2)//竞彩盈利榜
		{
			$field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.gameCount',
				'r.pointCount','r.win','r.transport','r.winrate'];
			
			$count = M('RankBetprofit')->where($where)->count('id');
			
			if (!$count) $where = $topWhere;//当没有数据是2，扩大时间范围
			
			$rankLists = M('RankBetprofit r')
						->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
						->field($field)
						->where($where)
						->order("r.ranking ASC")
						->page($page . ',' . $pageNum)
						->select();
			if ($page < 2)//当加载第二页就不用在获取个人排排名---亚盘
			{
				if($user_id > 0)
				{
					$myRank = M('RankBetprofit r')
						->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
						->where($where)
						->where(['r.user_id'=>$user_id])
						->field($field)
						->find();
					
					$myRank['ranking'] = $myRank['ranking'] > 1000 || empty($myRank['ranking']) ? '未上榜' : intval($myRank['ranking']).'名';
					$myRank['pointCount'] = empty($myRank['pointCount']) ? 0 : $myRank['pointCount'];
					$myRank['winrate'] = empty($myRank['winrate']) ? 0 : $myRank['winrate'];
					$myRank['win'] = empty($myRank['win']) ? 0 : $myRank['win'];
					$myRank['transport'] = empty($myRank['transport']) ? 0 : $myRank['transport'];
				}
			}
		}
		
			
		$userIdArr = get_arr_column($rankLists,'user_id');//获取用户ID
		//获取今天有推荐的用户
		$gambleIdArr = M('Gamble')
			->where(['user_id' => ['IN',$userIdArr], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])
			->field('user_id')->select();
		//获取连胜
		if ($user_id > 0)
		{
			$myRank['curr_victs'] = D('GambleHall')->getWinning($user_id,1,0,$gambleType)['curr_victs'];
		}
		
		//获取用户信息
		foreach ($rankLists as $k => $v)
		{
			//获取连胜
			$rankLists[$k]['curr_victs'] = D('GambleHall')->getWinning($v['user_id'],1,0,$gambleType)['curr_victs'];
			
			$rankLists[$k]['face']          = frontUserFace($v['face']);
			$rankLists[$k]['nick_name']     = $v['nick_name'] ?: '';
			//标志今天有推荐的用户
			foreach ($gambleIdArr as $key => $value)
			{
				if ($v['user_id'] === $value['user_id'])
				{
					$rankLists[$k]['today_gamble'] = 1;
				}
			}
			
		}
		
		
		if(IS_POST && IS_AJAX)
		{
			$this->ajaxReturn(['status'=>1,'list'=>$rankLists,'myRank'=>$myRank,'dateType'=>$dateType,'m_quiz'=>$m_quiz,'is_login'=>$user_id]);
		}
		cookie('pageUrl', __SELF__);
		$this->display();
    }
    public function search()
	{
		$blockTime = getBlockTime(1,true);
		
		//亚盘
		$ypRankList = D('Base')->getRankUserHundred(1,1,100,100);//亚盘足球周榜前100名；---方法内已经统一缓存
		
		$tenGambleArrYP = get_arr_column($ypRankList,'tenGambleRate');
		array_multisort($tenGambleArrYP,SORT_DESC,$ypRankList);//按十中几排序
		
		//获取十中几前四名
		$ypRankList = array_slice($ypRankList,0,4);
		//获取用户Id
		$userIdYP = array();
		foreach ($ypRankList as $k => $v)
		{
			$ypRankList[$k]['gambleType'] = 1;//亚盘标志
			$userIdYP[] = $v['user_id'];
			$ypRankList[$k]['nick_name'] = msubstr($v['nick_name'],0,4);
		}
		
		//判断是否有推荐---亚盘的
		$gambleYP = M('Gamble')
					->where(['user_id'=>['IN',$userIdYP],
							 'result'=>0,
							 'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
							 'play_type'=>['IN',[1,-1]]])//亚盘的
					->getField('user_id',true);
		if (! empty($gambleYP))
		{
			foreach ($ypRankList as $key => $value)
			{
				foreach ($gambleYP as $k => $v)
				{
					if ($value['user_id'] == $v)
					{
						$ypRankList[$key]['is_gamble'] = 1;
						break;
					}
				}
			}
		}
		
		//
		$jcRankList = D('Base')->getRankUserRaceHundred(1,1,100,100);//竞彩足球周榜前100名；---方法内已经统一缓存
		//按十中几排序
		$tenGambleArrJC = get_arr_column($jcRankList,'tenGambleRate');
		array_multisort($tenGambleArrJC,SORT_DESC,$jcRankList);
		//获取前四名
		$jcRankList = array_slice($jcRankList,0,4);
		//获取用户Id
		$userIdJC = array();
		foreach ($jcRankList as $k => $v)
		{
			$jcRankList[$k]['gambleType'] = 2;//亚盘标志
			$userIdJC[] = $v['user_id'];
			$jcRankList[$k]['nick_name'] = msubstr($v['nick_name'],0,4);
		}
		//判断是否有推荐---竞彩的
		$gambleJC = M('Gamble')
			->where(['user_id'=>['IN',$userIdJC],
					 'result'=>0,
					 'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
					 'play_type'=>['IN',[2,-2]]])//竞彩的
			->getField('user_id',true);
		if (! empty($gambleJC))
		{
			foreach ($jcRankList as $key => $value)
			{
				foreach ($gambleJC as $k => $v)
				{
					if ($value['user_id'] == $v)
					{
						$jcRankList[$key]['is_gamble'] = 1;
						break;
					}
				}
			}
		}
		
		$rankList = array_merge($ypRankList,$jcRankList);
		
		
		//获取历史搜索记录
		$searchHistory=cookie('searchHistory');
		$searchHisArr = explode(',',$searchHistory);
		array_shift($searchHisArr);//删除第一个
		rsort($searchHisArr);
		
        //搜索历史
        cookie('redirectUrl', __SELF__);
        cookie('pageUrl', __SELF__);
        cookie('userUrl', __SELF__);
        $this->assign('nav','search');
        $this->assign('history',$searchHisArr);
        $this->assign('hot',$rankList);
        $this->display();
    }
    
    
     /**
     * 昵称搜索
     **/
    public function search_results()
    {
        $keyword=I('param.keyword','','string');
        $keyword=urldecode($keyword);
        if($keyword === ''){
	
			redirect(U('Guess/search'));
            exit;
        }
        //保存搜索记录---有过相同的就不保存了
		$searchHistory=cookie('searchHistory');
		$searchHisArr = explode(',',$searchHistory);
		$is_history = false;
		foreach ($searchHisArr as $k => $v)
		{
			if ($v == $keyword)
			{
				$is_history = true;
				break;
			}
		}
		if (! $is_history)
		{
			cookie('searchHistory',$searchHistory.','.$keyword);
		}
		
		
        $limit = 20;
        $page = I('param.page',1,'intval');
        $startRow = ($page - 1) * $limit;

        //模糊匹配
		$userInfo = M('FrontUser')
					->where(['id'=>['GT',0],'nick_name'=>['Like','%'.$keyword.'%']])
					->field('id,nick_name,head,lv,lv_bet')
					->select();
   
        foreach ($userInfo as $k => $v) {

            //排序数组
            $lvArr[]            = $v['lv'];                                             //等级
            //拼接返回结果
			$userInfo[$k]['nick_name']=preg_replace("/($keyword)/i","<i>\\1</i>",$userInfo[$k]['nick_name'],1);
			$userInfo[$k]['head']  =  frontUserFace($v['head']);

        }
        array_multisort($lvArr,SORT_DESC,$userInfo);
        $lists =  $userInfo ? array_slice($userInfo, $startRow, $limit) : [];
	
		//获取用户最新推荐
		$userIdArr = get_arr_column($lists,'id');
		$blockTime = getBlockTime(1,true);
		$querySql = M('Gamble')
			->where(['user_id'=>['IN',$userIdArr],
					 'create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
					 'result'=>0])
			->field('user_id,MAX(create_time) AS create_time')
			->group('user_id')
			->buildSql();
		
		$gambleList = M('Gamble g1')
				->join('INNER JOIN '.$querySql.' g2 ON g1.user_id = g2.user_id and g1.create_time = g2.create_time')
				->where(['g1.user_id'=>['IN',$userIdArr],
						 'g1.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]],
						 'g1.result'=>0])
				->field('g1.user_id,g1.home_team_name,g1.away_team_name')
				->select();
		foreach ($lists as $key => $value)
		{
			foreach ($gambleList as $k => $v)
			{
				if ($value['id'] == $v['user_id'])
				{
					$lists[$key]['home_team_name'] = substr($v['home_team_name'],0,stripos($v['home_team_name'],','));
					$lists[$key]['away_team_name'] = substr($v['away_team_name'],0,stripos($v['away_team_name'],','));
					break;
				}
			}
		}
	
        if(IS_AJAX)
        {
            $this->ajaxReturn(['status'=>1,'list'=>empty($lists) ? null : $lists]);
        }
		cookie('userUrl', __SELF__);
		cookie('pageUrl', __SELF__);
        $this->assign('keyword',$keyword);
        $this->assign('nav','search');
        $this->assign('list',$lists);
        $this->display();

    }
    
    //篮球
    public function bk_index(){
        $this->display();
    }
}
