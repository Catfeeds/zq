<?php
/**
 * 公共Model.
 * User: liangzk <liangzk@qc.com>
 * Date: 2016/10/21
 * Time: 16:44
 */
use Think\Model;
class BaseModel extends Model
{
    /**
     * 获取亚盘排行榜的十中几的用户
     * @user liangzk <liangzk@qc.com>
     * @datetime  2016-10-25
     * @param int $dateType 1：周，2：月，3:季
     * @param int $gameType 1：足球 2：篮球
     * @param int $num 排行榜前多少名
     * @param int $resultNum 返回多少行数据 默认12行
     * @return array
     * @version 2.3
     */
    public function getDelicateList($dateType = 1,$gameType = 1,$num = 50,$resultNum = 12)
    {
        $tableName = $gameType === 1 ? 'Gamble' : 'Gamblebk' ;//判断足球还是篮球 1:为足球
        
        
        $rankDate = getRankDate($dateType);//获取上周的日期
        $rankCount = M('RankingList')
            ->where(['dateType' => $dateType, 'gameType' => $gameType,'begin_date'=>['between',[$rankDate[0],$rankDate[1]]]])
            ->count('id');
        
        if (empty($rankCount)) //判断是否数据，防止周一没数据的情况
        {
            $rankDate = getTopRankDate($dateType);//获取上上周的数据
        }

        //获取周榜前$num用户,默认50
        $rankUserArr = M('RankingList r')
                    ->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
                    ->field('r.user_id,r.winrate, f.nick_name, f.head,f.lv,f.lv_bet')
                    ->where(['dateType' => $dateType,
                             'gameType' => $gameType,
                             'ranking'=>['elt',$num],
                             'begin_date'=>['between',[$rankDate[0],$rankDate[1]]]])
                    ->select();
        
        if ($gameType === 1)//足球亚盘条件
            $where['play_type'] = ['IN',[1,-1]];
        
        //推荐结果条件（分足球、篮球）
        $where['result'] = $gameType === 1 ? ['IN',['1','0.5','2','-1','-0.5']] : ['IN',['1','2','-1']];
        
        //获取周榜前$num用户的前十条推荐结果-------根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
        $gambleResultArr = $numCount = null;
        foreach ($rankUserArr as $key => $value)
        {
    
            $rankUserArr[$key]['face'] = frontUserFace($value['head']);
            unset($rankUserArr[$key]['head']);
            
            $gambleResultArr = M($tableName.' g')
                            ->where(['user_id'=>$value['user_id'],
                                     'create_time'=>['EGT',strtotime($rankDate[0])]])//根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
                            ->where($where)
                            ->field('result')
                            ->order('id desc')
                            ->limit(10)
                            ->select();
            $numCount = 0;
            foreach ($gambleResultArr as $k => $v)
            {
                if ($gameType === 1 ? $v['result'] === '1' || $v['result'] === '0.5' : $v['result'] === '1' )
                {
                    $numCount++;
                }
            }
            $rankUserArr[$key]['tenGambleRate'] = $numCount;//十中几
            unset($gambleResultArr);
        }
        //根据十场中的多排序
        array_multisort(get_arr_column($rankUserArr,'tenGambleRate'),SORT_DESC,$rankUserArr);
        
        return array_slice($rankUserArr, 0, $resultNum);//返回$resultNum数据
    }
    
    /**
     * 获取竞彩排行榜的十中几排行的用户
     * @user liangzk <liangzk@qc.com>
     * @datetime  2016-10-28
     * @param int $dateType 1：周，2：月，3:季
     * @param int $gameType 1：足球 2：篮球
     * @param int $num 排行榜前多少名
     * @param int $resultNum 返回多少行数据 默认12行
     * @return array
     * @version 2.3
     */
    public function getDelicateRaceList($dateType = 1,$gameType = 1,$num = 50,$resultNum = 12)
    {
        $tableName = $gameType === 1 ? 'Gamble' : 'Gamblebk' ;//判断足球还是篮球 1:为足球
        $rankDate = getTopRankDate($dateType);//获取上上周、月、季的数据的日期---加大日期范围，
        
        $rankBettingArr = D('Common')->getRankBetting($gameType,$dateType,null,$num);//获取竞彩的排行榜
    
        if ($gameType === 1)//足球竞彩条件
            $where['play_type'] = ['IN',[2,-2]];
        //推荐结果条件（分足球、篮球）
        $where['result'] = $gameType === 1 ? ['IN',['1','0.5','2','-1','-0.5']] : ['IN',['1','2','-1']];

        //获取周榜前$num用户的前十条推荐结果-------根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
        $gambleResultArr = $numCount = null;
        foreach ($rankBettingArr as $key => $value)
        {
        
            $gambleResultArr = M($tableName.' g')
                ->where(['user_id'=>$value['user_id'],
                         'create_time'=>['EGT',strtotime($rankDate[0])]])//根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
                ->where($where)
                ->field('result')
                ->order('id desc')
                ->limit(10)
                ->select();

            $numCount = 0;
            foreach ($gambleResultArr as $k => $v)
            {
                if ($gameType === 1 ? $v['result'] === '1' || $v['result'] === '0.5' : $v['result'] === '1' )
                {
                    $numCount++;
                }
            }
            $rankBettingArr[$key]['tenGambleRate'] = $numCount;//十中几
            unset($gambleResultArr);
        }
        //根据十场中的多排序
        array_multisort(get_arr_column($rankBettingArr,'tenGambleRate'),SORT_DESC,$rankBettingArr);
    
        return array_slice($rankBettingArr, 0, $resultNum);//返回$resultNum数据
        
    }
    
    /**
     * 获取亚盘排行榜的连胜排行的用户
     * @user liangzk <liangzk@qc.com>
     * @datetime  2016-10-25
     * @param int $dateType 1：周，2：月，3:季
     * @param int $gameType 1：足球 2：篮球
     * @param int $num 排行榜前多少名
     * @param int $resultNum 返回多少行数据 默认12行
     * @return array
     * @version 2.3
     */
    public function getWinnigList($dateType = 1,$gameType = 1,$num = 50,$resultNum = 12)
    {
        $rankDate = getRankDate($dateType);//获取上周的日期
        $rankCount = M('RankingList')
            ->where(['dateType' => $dateType, 'gameType' => $gameType,'begin_date'=>['between',[$rankDate[0],$rankDate[1]]]])
            ->count('id');
        
        if (empty($rankCount)) //判断是否有数据，防止周一没数据的情况
        {
            $rankDate = getTopRankDate($dateType);//获取上上周的数据
        }
        
        //获取周榜前$num用户,默认50
        $rankUserArr = M('RankingList r')
            ->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
            ->field('r.user_id,r.winrate, f.nick_name, f.head,f.lv')
            ->where(['dateType' => $dateType,
                     'gameType' => $gameType,
                     'ranking'=>['elt',$num],
                     'begin_date'=>['between',[$rankDate[0],$rankDate[1]]]])
            ->select();
        
        //获取周榜前$num用户的前十条推荐结果-------根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
        foreach ($rankUserArr as $key => $value)
        {
            $rankUserArr[$key]['face'] = frontUserFace($value['head']);
            unset($rankUserArr[$key]['head']);
            
            //连胜数多
            $winnig = D('GambleHall')->getWinning($value['user_id'], $gameType); //连胜记录
            if ($winnig['curr_victs'] >1)
            {
                $rankUserArr[$key]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                $rankUserArr[$key]['win'] = $winnig['win'];//胜场数
            }
            else
            {
                unset($rankUserArr[$key]);//筛选掉小于2的
            }
        }
        //根据连胜场数排序
        array_multisort(get_arr_column($rankUserArr,'curr_victs'),SORT_DESC,$rankUserArr);
        
        return array_slice($rankUserArr, 0, $resultNum);//返回$resultNum条数据
    }
    
    /**
     * * 获取竞彩排行榜的连胜多排行的用户
     * @user liangzk <liangzk@qc.com>
     * @datetime  2016-10-28
     * @param int $dateType 1：周，2：月，3:季
     * @param int $gameType 1：足球 2：篮球
     * @param int $num 排行榜前多少名
     * @param int $resultNum 返回多少行数据 默认12行
     * @return array
     * @version 2.3
     */
    public function getWinnigRaceList($dateType = 1,$gameType = 1,$num = 50,$resultNum = 12)
    {
        $rankBettingArr = D('Common')->getRankBetting($gameType,$dateType,null,$num);//获取竞彩的排行榜
        
        //获取周榜前$num用户的前十条推荐结果-------根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
        foreach ($rankBettingArr as $key => $value)
        {
    
            //连胜数多
            $winnig = D('GambleHall')->getWinning($value['user_id'], $gameType,0,2); //连胜记录
            if ($winnig['curr_victs'] >1)
            {
                $rankBettingArr[$key]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                $rankBettingArr[$key]['win'] = $winnig['win'];//胜场数
            }
            else
            {
                unset($rankBettingArr[$key]);//筛选掉小于2的
            }
        }
    
        //根据连胜场数排序
        array_multisort(get_arr_column($rankBettingArr,'curr_victs'),SORT_DESC,$rankBettingArr);
    
        return array_slice($rankBettingArr, 0, $resultNum);//返回$resultNum条数据
        
    }
    
    /**
     * 获取亚盘排行榜的用户的连胜、十中等的信息
     * @user liangzk <liangzk@qc.com>
     * @datetime  2016-10-25
     * @param int $dateType 1：周，2：月，3:季
     * @param int $gameType 1：足球 2：篮球
     * @param int $num 排行榜前多少名
     * @param int $resultNum 返回多少行数据 默认12行
     * @return array
     * @version 2.3
     */
    
    public function getRankUserRecord ($dateType = 1,$gameType = 1,$num = 50,$resultNum = 12)
    {
        $tableName = $gameType === 1 ? 'Gamble' : 'Gamblebk' ;//判断足球还是篮球 1:为足球
        
        $rankDate = getRankDate($dateType);//获取上周的日期
        $rankCount = M('RankingList')
            ->where(['dateType' => $dateType, 'gameType' => $gameType,'begin_date'=>['between',[$rankDate[0],$rankDate[1]]]])
            ->count('id');
    
        if (empty($rankCount)) //判断是否有数据，防止周一没数据的情况
        {
            $rankDate = getTopRankDate($dateType);//获取上上周的数据
        }
    
        //获取周榜前$num用户,默认50
        $rankUserArr = M('RankingList r')
            ->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
            ->field('r.user_id,r.winrate, f.nick_name, f.head,f.lv,f.lv_bet')
            ->where(['r.dateType' => $dateType,
                     'r.gameType' => $gameType,
                     'r.ranking'=>['elt',$num],
                     'r.begin_date'=>['between',[$rankDate[0],$rankDate[1]]]])
			->order('r.ranking asc')
            ->select();
    
        if ($gameType === 1)//足球亚盘条件
            $where['play_type'] = ['IN',[1,-1]];
    
        //推荐结果条件（分足球、篮球）
        $where['result'] = $gameType === 1 ? ['IN',['1','0.5','2','-1','-0.5']] : ['IN',['1','2','-1']];
    
        //获取周榜前$num用户的前十条推荐结果-------根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
        foreach ($rankUserArr as $key => $value)
        {
            $rankUserArr[$key]['face'] = frontUserFace($value['head']);
            unset($rankUserArr[$key]['head']);
            
            //十中几
            $gambleResultArr = M($tableName.' g')
                        ->where(['user_id'=>$value['user_id'],
                                 'create_time'=>['EGT',strtotime($rankDate[0])]])//根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
                        ->where($where)
                        ->field('result')
                        ->order('id desc')
                        ->limit(10)
                        ->select();
            $numCount = 0;
            foreach ($gambleResultArr as $k => $v)
            {
                if ($gameType === 1 ? $v['result'] === '1' || $v['result'] === '0.5' : $v['result'] === '1' )
                {
                    $numCount++;
                }
            }
            $rankUserArr[$key]['tenGambleRate'] = $numCount;//十中几
            
            //连胜数多
            $winnig = D('GambleHall')->getWinning($value['user_id'], $gameType); //连胜记录
            $rankUserArr[$key]['curr_victs'] = $winnig['curr_victs'];//连胜场数
            $rankUserArr[$key]['win'] = $winnig['win'];//胜场数
        }
        
        return array_slice($rankUserArr, 0, $resultNum);//返回$resultNum条数据
    }
	
	/**
	 *根据排行榜更新和推荐结算时间调整---这里比排行榜更新时间延时一分钟 和 比结算时间延时四分钟
	 *统一返回已经缓存的亚盘排行榜前一百名的用户信息（用户id、昵称、周胜率、等级、十中几、连胜、胜场数等信息）---亚盘
	 * @User Liangzk <Liangzk@qc.com>
	 * @DateTime 2016-11-24 10:57
	 * @param int $dateType 1：周，2：月，3:季
	 * @param int $gameType 1：足球 2：篮球
	 * @param int $num 排行榜前多少名
	 * @param int $resultNum 返回多少行数据
	 * @param bool $is_reload 是否清除缓存重新加载
	 * @return bool|mixed|string|void
	 *
	 * 注意----注意----注意
	 *
	 * 1、这里已经缓存了-----亚盘周榜前一百名的用户信息---getRankUserHundred（1,1,100,100）
	 * 2、这里已经缓存了-----亚盘月榜前二百名的用户信息---getRankUserHundred（2,1,200,200）
	 *
	 *---- 每新增一个缓存要在这添加缓存数据类型，-----每新增一个缓存要在这添加缓存数据类型----
	 */
	public function getRankUserHundred($dateType = 1,$gameType = 1,$num = 100,$resultNum = 100,$is_reload = false)
	{
	
		if (! in_array($dateType,[1,2,3]))
			return null;
		
		$last_executeTime = S('BaseRankUserHundredLastExecute'.MODULE_NAME.$dateType.$gameType.$num.$resultNum);//最后更新时间
		$rankUserArr = S('BaseRankUserHundredRankUserArr'.MODULE_NAME.$dateType.$gameType.$num.$resultNum);//根据足球亚盘
		
		switch ($dateType)
		{
			//周
			case 1:$conditions = empty($rankUserArr)
				|| (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < mktime(10,36,0,date('m'),date('d'),date('Y')))
				|| (strtotime(date('H:i')) > strtotime('12:36') && $last_executeTime < mktime(12,36,0,date('m'),date('d'),date('Y')));
				break;
			//月
			case 2:$conditions = empty($rankUserArr)
				|| (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < mktime(10,36,0,date('m'),date('d'),date('Y')))
				|| (strtotime(date('H:i')) > strtotime('12:39') && $last_executeTime < mktime(12,39,0,date('m'),date('d'),date('Y')));
				break;
			//季
			default:$conditions = empty($rankUserArr)
				|| (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < mktime(10,36,0,date('m'),date('d'),date('Y')))
				|| (strtotime(date('H:i')) > strtotime('12:42') && $last_executeTime < mktime(12,42,0,date('m'),date('d'),date('Y')));
			
		}
		//每天推荐结算一次或排行榜排序一次，就更新一次(这里缓存一天)---亚盘
		if ($conditions || $is_reload)
		{
			$rankUserArr = $this->getRankUserRecord($dateType,$gameType,$num,$resultNum);//获取用户排行榜
			
			S('BaseRankUserHundredLastExecute'.MODULE_NAME.$dateType.$gameType.$num.$resultNum,time(),24*60*60);
			S('BaseRankUserHundredRankUserArr'.MODULE_NAME.$dateType.$gameType.$num.$resultNum,$rankUserArr,24*60*60);
		}
		return $rankUserArr;
	}
    /**
     * 获取竞彩排行榜的用户的连胜、十中等的信息
     * @user liangzk <liangzk@qc.com>
     * @datetime  2016-10-28
     * @param int $dateType 1：周，2：月，3:季
     * @param int $gameType 1：足球 2：篮球
     * @param int $num 排行榜前多少名
     * @param int $resultNum 返回多少行数据 默认12行
     * @return array
     * @version 2.3
     */
    public function getRankUserRace ($dateType = 1,$gameType = 1,$num = 50,$resultNum = 12)
    {
        $tableName = $gameType === 1 ? 'Gamble' : 'Gamblebk' ;//判断足球还是篮球 1:为足球
        
        $rankDate = getTopRankDate($dateType);//获取上上周、月、季的数据的日期---加大日期范围，
    
        $rankBettingArr = D('Common')->getRankBetting($gameType,$dateType,null,$num);//获取竞彩的排行榜
        
        if ($gameType === 1)//足球亚盘条件
            $where['play_type'] = ['IN',[2,-2]];
        
        //推荐结果条件（分足球、篮球）
        $where['result'] = $gameType === 1 ? ['IN',['1','0.5','2','-1','-0.5']] : ['IN',['1','2','-1']];
        
        //获取周榜前$num用户的前十条推荐结果-------根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
        foreach ($rankBettingArr as $key => $value)
        {
            //十中几
            $gambleResultArr = M($tableName.' g')
                ->where(['user_id'=>$value['user_id'],
                         'create_time'=>['EGT',strtotime($rankDate[0])]])//根据规则，周榜连续3天没推荐或推荐不足15场的用户则剔除排行榜
                ->where($where)
                ->field('result')
                ->order('id desc')
                ->limit(10)
                ->select();
            $numCount = 0;
            foreach ($gambleResultArr as $k => $v)
            {
                if ($gameType === 1 ? $v['result'] === '1' || $v['result'] === '0.5' : $v['result'] === '1' )
                {
                    $numCount++;
                }
            }
            $rankBettingArr[$key]['tenGambleRate'] = $numCount;//十中几
            
            //连胜数多
            $winnig = D('GambleHall')->getWinning($value['user_id'], $gameType,0,2); //连胜记录
            $rankBettingArr[$key]['curr_victs'] = $winnig['curr_victs'];//连胜场数
            $rankBettingArr[$key]['win'] = $winnig['win'];//胜场数
        }
        
        return array_slice($rankBettingArr, 0, $resultNum);//返回$resultNum条数据
    }
	
	/**
	 * 根据排行榜更新和推荐结算时间调整---这里比排行榜更新时间延时一分钟 和 比结算时间延时四分钟
	 *	统一返回已经缓存的竞彩排行榜前一百名的用户信息（用户id、昵称、周胜率、等级、十中几、连胜、胜场数等信息）---竞彩
	 * @User Liangzk <Liangzk@qc.com>
	 * @DateTime 2016-11-24 10:57
	 * @param int $dateType 1：周，2：月，3:季
	 * @param int $gameType 1：足球 2：篮球
	 * @param int $num 排行榜前多少名
	 * @param int $resultNum 返回多少行数据
	 * @param bool $is_reload 是否清除缓存重新加载
	 * @return bool|mixed|string|void
	 *
	 *
	 * 注意----注意----注意
	 *
	 * 1、这里已经缓存了-----竞彩周榜前一百名的用户信息---getRankUserRaceHundred（1,1,100,100）
	 * 2、这里已经缓存了-----竞彩月榜前二百名的用户信息---getRankUserRaceHundred（2,1,200,200）
	 *
	 *---- 每新增一个缓存要在这添加缓存数据类型，-----每新增一个缓存要在这添加缓存数据类型----
	 */
	public function getRankUserRaceHundred($dateType = 1,$gameType = 1,$num = 100,$resultNum = 100,$is_reload = false)
	{
		if (! in_array($dateType,[1,2,3]))
			return null;
		
		$last_executeTime = S('BaseRankUserRaceHundredLastExecute'.MODULE_NAME.$dateType.$gameType.$num.$resultNum);//最后更新时间
		$rankUserArr = S('BaseRankUserRaceHundredRankUserArr'.MODULE_NAME.$dateType.$gameType.$num.$resultNum);//根据足球亚盘
		
		switch ($dateType)
		{
			//周
			case 1:$conditions = empty($rankUserArr)
				|| (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < mktime(10,36,0,date('m'),date('d'),date('Y')))
				|| (strtotime(date('H:i')) > strtotime('12:36') && $last_executeTime < mktime(12,36,0,date('m'),date('d'),date('Y')));
				break;
			//月
			case 2:$conditions = empty($rankUserArr)
				|| (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < mktime(10,36,0,date('m'),date('d'),date('Y')))
				|| (strtotime(date('H:i')) > strtotime('12:39') && $last_executeTime < mktime(12,39,0,date('m'),date('d'),date('Y')));
				break;
			//季
			default:$conditions = empty($rankUserArr)
				|| (strtotime(date('H:i')) > strtotime('10:36') && $last_executeTime < mktime(10,36,0,date('m'),date('d'),date('Y')))
				|| (strtotime(date('H:i')) > strtotime('12:42') && $last_executeTime < mktime(12,42,0,date('m'),date('d'),date('Y')));
			
		}
		//每天推荐结算一次或排行榜排序一次，就更新一次(这里缓存一天)---，竞彩
		if ($conditions || $is_reload)
		{
			$rankUserArr = $this->getRankUserRace($dateType,$gameType,$num,$resultNum);//获取用户排行榜

			S('BaseRankUserRaceHundredLastExecute'.MODULE_NAME.$dateType.$gameType.$num.$resultNum,time(),24*60*60);
			S('BaseRankUserRaceHundredRankUserArr'.MODULE_NAME.$dateType.$gameType.$num.$resultNum,$rankUserArr,24*60*60);
		}
		
		return $rankUserArr;
	}
	
	/**
	 * 获取足球赛事针对各玩法推荐场数的比率
	 * @User liangzk <liangzk@qc.com>
	 * @DataTime 2016-11-17 15:19
	 * @param $game_id 足球赛事id
	 * @return array
	 */
    public function getGambleRatio($game_id)
	{
		//推荐统计---分玩法
		$gamble = M('Gamble')
			->field('count(id) as gambleCount,play_type,chose_side')
			->where(['game_id'=>$game_id])
			->group('play_type,chose_side')
			->select();
		
		//计算推荐玩法百分比
		$data = $gambleCount = array();
		foreach ($gamble as $k => $v)
		{
			if ($v['play_type'] === '1' && $v['chose_side'] === '1')
			{
				$gambleCount['spreadHome'] = $v['gambleCount'];//亚盘让球（主）推荐数
			}
			elseif ($v['play_type'] === '1' && $v['chose_side'] === '-1')
			{
				$gambleCount['spreadAway'] = $v['gambleCount'];//亚盘让球（客）推荐数
			}
			elseif ($v['play_type'] === '-1' && $v['chose_side'] === '1')
			{
				$gambleCount['totalBig'] = $v['gambleCount'];//亚盘大小（大）推荐数
			}
			elseif ($v['play_type'] === '-1' && $v['chose_side'] === '-1')
			{
				$gambleCount['totalMall'] = $v['gambleCount'];//亚盘大小（小）推荐数
			}
			elseif ($v['play_type'] === '2' && $v['chose_side'] === '1')
			{
				$gambleCount['raceNotLetWin'] = $v['gambleCount'];//竞彩不让球（胜）推荐数
			}
			elseif ($v['play_type'] === '2' && $v['chose_side'] === '0')
			{
				$gambleCount['raceNotLetFlat'] = $v['gambleCount'];//竞彩不让球（平）推荐数
			}
			elseif ($v['play_type'] === '2' && $v['chose_side'] === '-1')
			{
				$gambleCount['raceNotLetLose'] = $v['gambleCount'];//竞彩不让球（负）推荐数
			}
			elseif ($v['play_type'] === '-2' && $v['chose_side'] === '1')
			{
				$gambleCount['raceLetWin'] = $v['gambleCount'];//竞彩让球（胜）推荐数
			}
			elseif ($v['play_type'] === '-2' && $v['chose_side'] === '0')
			{
				$gambleCount['raceLetFlat'] = $v['gambleCount'];//竞彩让球（平）推荐数
			}
			elseif ($v['play_type'] === '-2' && $v['chose_side'] === '-1')
			{
				$gambleCount['raceLetLose'] = $v['gambleCount'];//竞彩让球（负）推荐数
			}
			
		}
		
		$data['spreadNum']  = $gambleCount['spreadHome'] + $gambleCount['spreadAway'];//亚盘让球推荐数
		$spreadHome = round($gambleCount['spreadHome']/($gambleCount['spreadHome']+$gambleCount['spreadAway'])*100);
		$spreadAway = round($gambleCount['spreadAway']/($gambleCount['spreadHome']+$gambleCount['spreadAway'])*100);
		if ($spreadHome == 0 && $spreadAway == 0)
		{
			$data['spreadHome'] = 50;//亚盘让球（主）推荐占比
			$data['spreadAway'] = 50;//亚盘让球（客）推荐占比
		}
		else
		{
			$data['spreadHome'] = $spreadHome;//亚盘让球（主）推荐占比
			$data['spreadAway'] = 100 - $data['spreadHome'];//亚盘让球（客）推荐占比
		}
		
		
		$data['totalNum'] = $gambleCount['totalBig'] + $gambleCount['totalMall'];
		$totalBig = round($gambleCount['totalBig']/$data['totalNum']*100);
		$totalMall = round($gambleCount['totalMall']/$data['totalNum']*100);
		if ($totalBig == 0 && $totalMall == 0)
		{
			$data['totalBig']   = 50;//亚盘大小（大）推荐占比
			$data['totalMall'] = 50;//亚盘大小（小）推荐占比
		}
		else
		{
			$data['totalBig']   = $totalBig;//亚盘大小（大）推荐占比
			$data['totalMall'] = 100 - $data['totalBig'];//亚盘大小（小）推荐占比
		}
		
		$data['raceNotLetNum'] = $gambleCount['raceNotLetWin'] + $gambleCount['raceNotLetFlat'] + $gambleCount['raceNotLetLose'];//竞彩不让球推荐数
		$data['raceNotLetWin']   = round($gambleCount['raceNotLetWin']/$data['raceNotLetNum']*100);//竞彩不让球（胜）推荐占比
		$data['raceNotLetFlat']   = round($gambleCount['raceNotLetFlat']/$data['raceNotLetNum']*100);//竞彩不让球（平）推荐占比
		$data['raceNotLetLose']   = 100 - $data['raceNotLetWin'] - $data['raceNotLetFlat'];//竞彩不让球（负）推荐占比
		
		$data['raceLetNum'] = $gambleCount['raceLetWin'] + $gambleCount['raceLetFlat'] + $gambleCount['raceLetLose'];//竞彩不让球推荐数
		$data['raceLetWin']   = round($gambleCount['raceLetWin']/$data['raceLetNum']*100);//竞彩让球（胜）推荐占比
		$data['raceLetFlat']   = round($gambleCount['raceLetFlat']/$data['raceLetNum']*100);//竞彩让球（平）推荐占比
		$data['raceLetLose']   = 100 - $data['raceLetWin'] - $data['raceLetFlat'];//竞彩让球（负）推荐占比
		
		return $data;
	}
	
	/**
	 * 获取亚盘、竞彩排行榜
	 * @User  liangzk 《Liangzk@qc.com》
	 * @DateTime 2016-11-23 11:23
	 * @param int $gameType 1：足球 2：篮球
	 * @param int $playType 0:获取亚盘和竞彩 1：只获取亚盘  2：只获取竞彩
	 * @param int $ypDateType 亚盘的 1：周 2：月 3：季 4：日
	 * @param int $ypRankNum  获取前几名
	 * @param int $jcDateType 竞彩的 1：周 2：月 3：季 4：日
	 * @param int $jcRankNum  获取前几名
	 * @return array
	 */
	public function getRanking($gameType = 1,$playType = 0,$ypDateType = 1,$ypRankNum = 100,$jcDateType = 1,$jcRankNum = 100)
	{
		
		$ypRankList = $jcRankList = array();
		if ($playType === 0 || $playType === 1)//亚盘和竞彩
		{
			$ypRankDate = getRankDate($ypDateType);//获取上个周或月或季期的日期
			$ypRankCount = M('RankingList')
				->where(['dateType' => $ypDateType, 'gameType' => $gameType, 'begin_date' => ['between', [$ypRankDate[0], $ypRankDate[1]]]])
				->count('id');
			
			if ($ypRankCount < 1)//当没有数据时，扩大时间范围
				$ypRankDate = getTopRankDate($ypDateType);
			
			$ypRankList = M('RankingList r')
						->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
						->field('r.user_id,r.winrate, f.nick_name, f.head,f.lv,f.lv_bet')
						->where(['r.dateType' => $ypDateType,
								 'r.gameType' => $gameType,
								 'r.ranking'=>['elt',$ypRankNum],
								 'r.begin_date'=>['between',[$ypRankDate[0],$ypRankDate[1]]]])
						->order('ranking asc')
						->select();
			
				
					
					
		}
		
		if ($playType === 0 || $playType === 2)//竞彩
		{
			$jcRankDate = getRankDate($jcDateType);//获取上个周或月或季期的日期
			$jcRankCount = M('RankBetting')
				->where(['dateType' => $ypDateType, 'gameType' => $gameType, 'listDate' => $jcRankDate[1]])
				->count('id');
			if ($jcRankCount < 1)//当没有数据时，扩大时间范围
				$jcRankDate = getTopRankDate($jcDateType);
			
			$jcRankList = M('RankBetting r')
						->join('INNER JOIN qc_front_user f ON r.user_id = f.id')
						->field('r.user_id,r.winrate, f.nick_name, f.head,f.lv,f.lv_bet')
						->where(['r.dateType' => $jcDateType,
								 'r.gameType' => $gameType,
								 'r.ranking'=>['elt',$jcRankNum],
								 'r.listDate' => $jcRankDate[1]])
						->order('ranking asc')
						->select();
		}
		
		return array_merge($ypRankList,$jcRankList);
	}
	
	/**
	 * @User  liangzk 《Liangzk@qc.com》
	 * @DateTime 2016-12-22 15:14
	 * 获取用户足球、篮球的亚盘或竞彩的连胜----获取用户连胜---获取用户连胜---分亚盘、竞彩
	 * @param int   $gameType 1:足球 2：篮球
	 * @param int   $playType 1: 亚盘 2：竞彩
	 * @param array $userIdArr 用户ID
	 * @return null | array()
	 */
	public function getUserWinning($gameType = 1,$gambleType = 1,$userIdArr = array())
	{
		$tableName = $gameType == 1 ? 'Gamble' : 'Gamblebk';
		
		if (empty($userIdArr) || !in_array($gambleType,[1,2]) || !in_array($gameType,[1,2]))
			return null;
		
		//亚盘、竞彩
		$map['play_type'] = $gambleType === 1 ? ['IN',['1','-1']] : ['IN',['2','-2']];
		
		//获取最近输那场的时间
		$querySql = M($tableName)
			->where(['user_id'=>['IN',$userIdArr], 'result'=>['IN',['-1','-0.5']]])
			->where($map)
			->field('user_id,MAX(create_time) AS create_time')
			->group('user_id')
			->buildSql();
		
		
		$userWinRes = M($tableName.' g1')
				->join('INNER JOIN '.$querySql.' g2 ON g1.user_id = g2.user_id and g1.create_time >= g2.create_time')
				->where(['g1.user_id'=>['IN',$userIdArr],'g1.result'=>['IN',['1','0.5']]])
				->where($map)
				->field('g1.user_id,count(g1.id) AS winCount')
				->group('g1.user_id')
				->select();

		return empty($userWinRes) ? null : $userWinRes;
		
	}
	
	
	/**
	 * @User liangzk <liangzk@qc.com>
	 * @dateTime 2017-01-05 16:30
	 * @param int   $gameType 1:足球 2：篮球
	 * @param int   $gambleType 1：亚盘 2：竞彩
	 * @param array $userIdArr 用户id--一维数组
	 * @param array $create_time 某个时间段
	 * @param int   $sub_num  比赛结果所拼接的字符串的长度（注意：根据返回长度设置，这里默认设置十中几的）
	 * @param int   $limit  默认十中几   比如 A中B  limit的值为A
	 * @param boolean   $is_result  是否返回的是推荐结果  默认不是-即默认返回中几
	 * @return array user_id和 A中B的B
	 */
	public function getUserWinFew($gameType = 1,$gambleType = 1,$userIdArr = array(),$is_result = false,$create_time = array(0,0),$sub_num = 45,$limit = 10)
	{
		$tableName = $gameType == 1 ? 'Gamble' : 'Gamblebk';
		
		if (!is_array($userIdArr) || empty($userIdArr) || !in_array($gambleType,[1,2]) || !in_array($gameType,[1,2]))
			return null;
		
		if($gameType == 1)
		{
			//亚盘、竞彩--足球
			$map['play_type'] = $gambleType === 1 ? ['IN',['1','-1']] : ['IN',['2','-2']];
			$map['result'] = $gambleType === 1 ? ['IN',['-1','-0.5','1','0.5','2']] : ['IN',['-1','-0.5','1','0.5']];
		}
		else
		{
			//亚盘--篮球
			$map['play_type'] = ['IN',['2','-2','1','-1']];
			$map['result'] = ['IN',['1','-1']];
		}
		if ($create_time[1] > 0)
		{
			$map['create_time'] = ['between',[$create_time[0],$create_time[1]]];
		}
		
		$fieldName = ' user_id,LEFT(GROUP_CONCAT(result ORDER BY id DESC),'.$sub_num.') as winStr';
		//获取每个用户的前几条记录的id
		$winFewArr = M($tableName)
			->where(['user_id'=>['IN',$userIdArr]])
			->where($map)
			->field($fieldName)
			->group('user_id')
			->select();
		//统计中几
		foreach ($winFewArr as $key => $value)
		{
			$resultArr = array_slice(explode(',',$value['winStr']),0,$limit);
			unset($winFewArr[$key]['winStr']);
			if ($is_result === false)
			{
				$winNum = 0;
				foreach ($resultArr as $k => $v)
				{
					if ($v == '1' || $v == '0.5')
					{
						$winNum ++;
					}
				}
				$winFewArr[$key]['tenGambleRate'] = $winNum;//中几
			}
			else
			{
				$winFewArr[$key]['gambleResult'] = $resultArr;//推荐结果
			}
			
		}
		
		return $winFewArr;
	}
	
	/**
	 * @param array $userIdArr 用户id--一维数组
	 * @param int   $gameType 1:足球 2：篮球
	 * @param int   $gambleType 1：亚盘 2：竞彩
	 * @param int  $playType  玩法(1:让分;-1:大小 默认为0，不分玩法)
	 * @param int  $dateType  时间类型(1:周胜率 2:月胜率 3:季胜率 4:日胜率 默认为1)
	 * @return array user_id和 胜率
	 */
	public function getWinrate($useridArr = array(),$gameType=1,$gambleType=1,$playType=0,$dateType=1)
	{
		if (!is_array($useridArr) || !in_array($gameType,[1,2]) || !in_array($gambleType,[1,2]) || !in_array($dateType,[1,2,3,4]))
		{
			return null;
		}
		
		//日期筛选
		list($begin,$end) = getRankBlockDate($gameType,$dateType);
		
		$gameModel = $gameType == 1 ? 'gamble' : 'gamblebk';
		
		//查询推荐数据
		$where['user_id']    = ['IN',$useridArr];
		$where['result']     = $gameType == 1 ? ['IN',['1','0.5','2','-1','-0.5']] : ['IN',['1','-1','2']];
		
		//加上对应时间
		$time = $gameType == 1 ? (10*60+32)*60 : (12*60)*60;
		$where['create_time']  = [ 'between',[ strtotime($begin) + $time, strtotime($end) + 86400 + $time ] ];
		
		if($dateType == 4) //日榜时间条件
		{
			$blockTime  = getBlockTime($gameType,$gamble=true);
			$end        = date('Ymd', $blockTime['beginTime'] - 86400);
			$where['create_time'] = ['between',[$blockTime['beginTime']-86400,$blockTime['endTime']-86400]];
		}
		//足球竞彩
		if($gameType == 1)
		{
			$where['play_type'] = ($gambleType == 1) ? ['IN', ['-1','1']] : ['in', ['-2','2']];
		}
		
		if($playType !== 0)
		{
			$where['play_type'] = (int)$playType;
		}
		
		$fieldName  = $gameType == 1 ?
					'user_id,count(id) AS totalCount,COUNT(CASE WHEN result = 1 OR result = 0.5 THEN 1 ELSE NULL END) AS winCount'
					:
					'user_id,count(id) AS totalCount,COUNT(CASE WHEN result = 1 THEN 1 ELSE NULL END) AS winCount';
		//获取用户的赢的场数和推荐场数
		$gambleArr = M($gameModel)->field($fieldName)->where($where)->group('user_id')->select();
		
		foreach ($gambleArr as $key => $value)
		{
			$gambleArr[$key]['winrate'] = round(($value['winCount']/$value['totalCount'])*100);
			unset($gambleArr[$key]['winCount'],$gambleArr[$key]['totalCount']);
		}
		return $gambleArr;
		
	}
	
	
}

?>
 
 