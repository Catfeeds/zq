<?php
/**
 * 竞猜大厅模型类
 * @author huangjiezhen <418832673@qq.com> 2015.12.16
 */

use Think\Model;

class GambleHallModel extends Model
{
    protected $tableName = 'game_fbinfo';

    //足球竞猜大厅
    public function matchList()
    {
        $blockTime = getBlockTime(1);

        $sql = "
            SELECT DISTINCT
                g.game_id, g.union_id, u.union_name, u.union_color, u.is_sub, u.sort ,g.gtime, g.game_half_time, g.game_state,
                g.home_team_name, g.home_team_id, g.score, g.half_score, g.away_team_name, g.away_team_id,
                g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away, g.fsw_ball_home, g.fsw_ball, g.fsw_ball_away
            FROM __PREFIX__game_fbinfo g
            LEFT JOIN __PREFIX__union u ON g.union_id = u.union_id
            WHERE
                g.status = 1
            AND gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']}
            AND ((g.is_show = 1 AND u.is_sub < 3) or g.is_gamble = 1)
            AND g.fsw_exp       != ''
            AND g.fsw_ball      != ''
            AND g.fsw_exp_home  != ''
            AND g.fsw_exp_away  != ''
            AND g.fsw_ball_home != ''
            AND g.fsw_ball_away != ''
        ";

        $game  = M()->query($sql);

        $union = [];
        $sort_game_state = [];
        $sort_gtime      = [];
        $sort_union      = [];
        $sort_union2     = [];

        foreach ($game as $k => $v)
        {
            if(
                   ($v['gtime'] + 60 < time() && $v['game_state'] == 0)  //过了开场时间未开始
                || ($v['game_state'] == -14 || $v['game_state'] == -11)  //屏蔽待定和推迟
                || ($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) //140分钟还没结束
              )
            {
                unset($game[$k]);
                continue;
            }

            //获取球队logo
            //$game[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1);
            //$game[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2);

            //分解时间
            $game[$k]['game_date'] = date('Ymd',$v['gtime']);
            $game[$k]['game_time'] = date('H:i',$v['gtime']);

            //增加排序的条件
            $sort_gtime[]      = $v['gtime'];

            if (stristr(MODULE_NAME,'Api'))
            {
                if ($v['game_state'] < 0)
                {
                    $v['game_state'] = abs($v['game_state']);
                }
                else if ($v['game_state'] > 0)
                {
                    $v['game_state'] += 15; //纯粹为了排序 -.-!!!
                }

                $sort_game_state[] = $v['game_state'];
            }
            else
            {
                $sort_game_state[] = $v['game_state'];
            }

            //获取联盟中球队数量
            if (array_key_exists($v['union_id'],$union))
            {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num']+1);
            }
            else
            {
                $union[$v['union_id']] = ['union_id'=>$v['union_id'],'union_name'=>$v['union_name'],'union_num'=>'1','union_color'=>$v['union_color']];
                $sort_union[] = $v['is_sub'];
                $sort_union2[] = $v['sort'];
            }

            unset($game[$k]['is_sub']);
        }

        //获取球队logo
        setTeamLogo($game,1);
        
        $union = array_values($union);
        array_multisort($sort_union,SORT_ASC,$sort_union2,SORT_ASC,$union);

        if (stristr(MODULE_NAME,'Api'))
        {
            foreach ($game as $k => $v)
            {
                $game[$k]['union_name']     = explode(',', $v['union_name']);
                $game[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $game[$k]['away_team_name'] = explode(',', $v['away_team_name']);

                if ($v['game_half_time']) //半场时间转换
                {
                    $halfTime    = explode(',', $v['game_half_time']);
                    $halfTime[1] = str_pad($halfTime[1]+1, 2, '0', STR_PAD_LEFT); //js的月份+1为正常月份
                    $game[$k]['game_half_time'] = implode('', $halfTime);
                }

                //接口删除盘口赔率等不需要返回的字段
                unset($game[$k]['home_team_id']);
                unset($game[$k]['away_team_id']);
                unset($game[$k]['fsw_exp']);
                unset($game[$k]['fsw_ball']);
                unset($game[$k]['fsw_exp_home']);
                unset($game[$k]['fsw_exp_away']);
                unset($game[$k]['fsw_ball_home']);
                unset($game[$k]['fsw_ball_away']);
            }

            foreach ($union as $k => $v)
            {
                $union[$k]['union_name'] = explode(',', $v['union_name']);
            }

            array_multisort($sort_game_state,SORT_ASC, $sort_gtime,SORT_ASC, $game);
        }
        else
        {
            array_multisort($sort_game_state,SORT_DESC, $sort_gtime,SORT_ASC, $game);
        }
        //获取即时数据
        if(!empty($game))
        {
            $this->getFbGoal($game);
        }
        return [$game,$union];
    }

    public function getFbGoal(&$game)
    {
        foreach ($game as $k => $v) {
            $gameId[] = $v['game_id'];
        }
        //获取数据
        $fb_goal = M('fb_goal')->field('game_id,exp_value')->where(['company_id'=>3,'game_id'=>['in',$gameId]])->select();
        foreach ($game as $k => $v)
        {
            foreach ($fb_goal as $kk => $vv)
            {
                //组装对应数据
                if($v['game_id']  == $vv['game_id'])
                {
                    $game[$k]['exp_value'] = $vv['exp_value'];
                }
            }
        }
        //获取即时数据
        foreach ($game as $k => $v)
        {
            if(!empty($v['exp_value']))
            {
                $odds = explode('^', $v['exp_value']);

                $whole      = explode(',', $odds[0]);  //全场
                if($whole[6] !='' || $whole[7] !='' || $whole[8] !='')
                {
                    //全场滚球
                    if($whole[6] == 100 || $whole[7] == 100 || $whole[7] == 100)
                    {
                        $game[$k]['fsw_exp_home'] = '';
                        $game[$k]['fsw_exp']      = '封';
                        $game[$k]['fsw_exp_away'] = '';
                    }else{
                        $game[$k]['fsw_exp_home'] = $whole[6];
                        $game[$k]['fsw_exp']      = $whole[7];
                        $game[$k]['fsw_exp_away'] = $whole[8];
                    }
                }
                elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
                {
                    //全场即时
                    if($whole[3] == 100 || $whole[4] == 100 || $whole[5] == 100)
                    {
                        $game[$k]['fsw_exp_home'] = '';
                        $game[$k]['fsw_exp']      = '封';
                        $game[$k]['fsw_exp_away'] = '';
                    }else{
                        $game[$k]['fsw_exp_home'] = $whole[3];
                        $game[$k]['fsw_exp']      = $whole[4];
                        $game[$k]['fsw_exp_away'] = $whole[5];
                    }
                }

                $size       = explode(',', $odds[2]);  //大小
                if($size[6] !='' || $size[7] !='' || $size[8] !='')
                {
                    //大小滚球
                    if($size[6] == 100 || $size[7] == 100 || $size[8] == 100)
                    {
                        $game[$k]['fsw_ball_home'] = '';
                        $game[$k]['fsw_ball']      = '封';
                        $game[$k]['fsw_ball_away'] = '';
                    }else{
                        $game[$k]['fsw_ball_home'] = $size[6];
                        $game[$k]['fsw_ball']      = $size[7];
                        $game[$k]['fsw_ball_away'] = $size[8];
                    }
                }
                elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
                {
                    //大小即时
                    if($size[3] == 100 || $size[4] == 100 || $size[5] == 100)
                    {
                        $game[$k]['fsw_ball_home'] = '';
                        $game[$k]['fsw_ball']      = '封';
                        $game[$k]['fsw_ball_away'] = '';
                    }else{
                        $game[$k]['fsw_ball_home'] = $size[3];
                        $game[$k]['fsw_ball']      = $size[4];
                        $game[$k]['fsw_ball_away'] = $size[5];
                    }
                }
            }
        }
    }

    //篮球竞猜大厅
    public function basketballList($map='')
    {
        $blockTime = getBlockTime(2);
        $date = date("Ymd",strtotime("-1 day")).",".date('Ymd');
        $game = D("gameBkinfo")->table('qc_game_bkinfo g')
                             ->join('LEFT JOIN qc_bk_union b ON g.union_id = b.union_id')
                             ->field('g.game_id,g.gtime,g.show_date, g.union_id, g.union_name, g.game_date, g.game_time,g.game_half_time,
                                      g.game_state,g.total,g.home_team_name, g.home_team_id, g.score, g.half_score,g.away_team_name,
                                      g.away_team_id,g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away, g.fsw_total_home,g.fsw_total,
                                      g.fsw_total_away,g.psw_exp_home,g.psw_exp,g.psw_exp_away,g.psw_total_home,g.psw_total,g.psw_total_away,
                                      b.union_id,b.union_name,b.union_color,b.is_sub,b.sort')
                             ->where("(
                                        (g.gtime between {$blockTime['beginTime']} AND {$blockTime['endTime']})
                                        or (g.game_date in ({$date}) and g.game_state in (1,2,50,3,4,5,6,7))
                                      )
                                        and g.status = 1
                                        and ( (b.is_sub<3 and g.is_show=1) or g.is_gamble=1 )
                                        and ( g.fsw_exp!='' or g.fsw_total!='' or g.psw_exp!='' or g.psw_total!='' )")
                             ->group('g.game_id')
                             ->order('g.game_state desc,g.game_date asc,g.game_time asc')
                             ->select();

        $union = array();
        foreach ($game as $k => $v)
        {
            if(
                   ($v['gtime'] + 60 < time() && $v['game_state'] == 0)  //过了开场时间未开始
                || ($v['game_state'] == -14 || $v['game_state'] == -2)   //屏蔽待定和推迟
                || ($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,50,4,5,6]) !== false) //140分钟还没结束
              )
            {
                unset($game[$k]);
                continue;
            }
//            $game[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1,2);
//            $game[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2,2);
            //获取联盟中球队数量
            if (array_key_exists($v['union_id'],$union))
            {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num']+1);
            }
            else
            {
                $union[$v['union_id']] = ['union_id'=>$v['union_id'],'union_name'=>$v['union_name'],'union_num'=>'1'];
                $sort_union[]  = $v['is_sub'];
                $sort_union2[] = $v['sort'];
            }
        }

        //获取球队logo
        setTeamLogo($game, 2);

        //排序
        array_multisort($sort_union,SORT_ASC,$sort_union2,SORT_ASC,$union);
        $union = array_values($union);
        //选中某个联盟
        if ($map != '')
        {
            foreach ($game as $k => $v)
            {
                if ($v['union_id'] != $map)
                    unset($game[$k]);
            }
        }
        return [$game,$union];
    }


    //足球/篮球竞猜
    public function gamble($userid,$param,$platform,$gameType=1)
    {
        unset($param['odds'],$param['handcp']);
        //获取盘口和赔率
        switch ($gameType)
        {
            case '1': self::getHandcpAndOdds($param);   break;
            case '2': self::getHandcpAndOddsBk($param); break;
        }

        $gameModel   = $gameType == 1 ? M('GameFbinfo') : M('GameBkinfo');
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');

        //竞猜字段不能为空
        if (
            $param['game_id']       == null
            || $param['play_type']  == null
            || $param['chose_side'] == null
            || $param['is_impt']    == null
            || $param['odds']       == null
            || $param['handcp']     == null
            || $platform            == null
        )
        {
            return 201;
        }

        //获取剩余竞猜次数，竞猜配置
        list($normLeftTimes,$imptLeftTimes,$gameConf) = $this->gambleLeftTimes($userid,$gameType);

        //判断竞猜的次数是否已达上限
        if ($normLeftTimes <= 0 && $imptLeftTimes <= 0)
            return 2004;

        if ($normLeftTimes <= 0 && $param['is_impt'] == 0)
            return 2005;

        if ($imptLeftTimes <= 0 && $param['is_impt'] == 1)
            return 2006;

        //如果有推荐分析、需要大于10字小于50字
        if ($param['desc'])
        {
            $descLenth = Think\Tool\Tool::utf8_strlen($param['desc']);

            if ($descLenth < 10 || $descLenth > 400)
                return 2011;
        }

        //是否有推荐购买和竞猜分析
        $Lv = $gameType == 1 ? 'lv' : 'lv_bk';
        if($param['tradeCoin'] > 0 || !empty($param['desc']))
        {
            $userInfo = M('FrontUser')->field([$Lv,'point'])->where(['id'=>$userid])->find();
        }
        if ($param['tradeCoin'])
        {
            //如果设置推荐购买、判断是否符合用户等级
            $maxCoin = $gameConf['userLv'][$userInfo[$Lv]]['letCoin'];
            if ($param['tradeCoin'] > $maxCoin)
                return 2012;
        }

        $gameInfo = $gameModel->field(['union_id','union_name','game_id','gtime','home_team_name','away_team_name'])
                    ->where(['game_id'=>$param['game_id']])->find();

        $gameInfo['game_date'] = date('Ymd',$gameInfo['gtime']);
        $gameInfo['game_time'] = date('H:i',$gameInfo['gtime']);

        //判断是否是未来竞猜
        $blockTime  = getBlockTime($gameType);
        if($gameInfo['gtime'] < $blockTime['beginTime'] || $gameInfo['gtime'] > $blockTime['endTime'])
            return 2019;

        //判断竞猜时间
        if (time() > $gameInfo['gtime'])
            return 2002;

        //判断竞猜的类型，不可重复、冲突竞猜
        $gamble = (array)$GambleModel->master(true)->field(['play_type','chose_side'])->where(['user_id'=>$userid,'game_id'=>$param['game_id']])->select();

        foreach ($gamble as $v)
        {
            if ($v['play_type'] === $param['play_type'])
                return 2003;
        }

        $param['is_impt'] == 0 ? $normLeftTimes-- : $imptLeftTimes--;

        //增加竞猜记录
        $param['user_id']     = $userid;
        $param['game_type']   = '1';
        $param['vote_point']  = $param['is_impt'] ? $gameConf['impt_point'] : $gameConf['norm_point'];
        $param['create_time'] = time();
        $param['platform']    = $platform;
        $param['tradeCoin']   = (int)$param['tradeCoin'];

        //获取另外队的赔率
        $param2 = $param;
        $param2['chose_side'] = $param2['chose_side'] == 1 ? -1 : 1;
        switch ($gameType)
        {
            case '1': self::getHandcpAndOdds($param2);   break;
            case '2': self::getHandcpAndOddsBk($param2); break;
        }

        $param['odds_other']   = $param2['odds'];
        unset($param2);

        $insertId = $GambleModel->add(array_merge($gameInfo,$param));

        if (!$insertId)
            return 2007;

        //增加竞猜分析的积分记录,0积分跳过
        if (!empty($param['desc']) && $gameConf['gamble_desc'] != 0)
        {
            $changePoint = $gameConf['gamble_desc'];
            $totalPoint = $userInfo['point'] + $changePoint;

            M('FrontUser')->where(['id'=>$userid])->setInc('point',$changePoint);

            $descType = $gameType == 1 ? '足球' : '篮球';
            $descPlay = $param['play_type'] == 1 ? '让球' : '大小';

            M('PointLog')->add([
                'user_id'     => $userid,
                'log_time'    => NOW_TIME,
                'log_type'    => 12,
                'gamble_id'   => $param['game_id'],
                'change_num'  => $changePoint,
                'total_point' => $totalPoint,
                'desc'        => $descType.$descPlay.'竞猜分析'
            ]);
        }

        $redis = connRedis();
        $redis->lpush(C('em') . 'user_gameball_push_list', $userid); //竞猜的用户入列
        $redis->lpush(C('um') . 'user_gameball_push_list', $userid); //竞猜的用户入列,友盟

        return ['normLeftTimes'=>$normLeftTimes,'imptLeftTimes'=>$imptLeftTimes];
    }

    /**
     * 计算用户剩余竞猜的场次
     * @param  int $userid   用户id
     * @param  int $gameType 赛程类型 1：足球，2：篮球
     * @return arr           普通、重点剩余的次数
     */
    public function gambleLeftTimes($userid,$gameType=1)
    {
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        $sign = $gameType == 1 ? 'fbConfig' : 'bkConfig';
        $gameConf = getWebConfig($sign);

        $blockTime   = getBlockTime($gameType,$gamble=true);

        $where['user_id']     = $userid;
        $where['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];

        $gambleList = $GambleModel->field(['game_id','is_impt'])->where($where)->select();

        if (in_array(date('N',$blockTime['beginTime']),[1,2,3,4])) //周1-4
        {
            $normTimes = $gameConf['weekday_norm_times'];
            $imptTimes = $gameConf['weekday_impt_times'];
        }
        else
        {
            $normTimes = $gameConf['weekend_norm_times'];
            $imptTimes = $gameConf['weekend_impt_times'];
        }

        $normVoteTimes = 0;
        $imptVoteTimes = 0;

        foreach ($gambleList as $v)
        {
            if ($v['is_impt'] == 0)
                $normVoteTimes ++;

            if ($v['is_impt'] == 1)
                $imptVoteTimes ++;
        }

        $normLeftTimes = $normTimes - $normVoteTimes;
        $imptLeftTimes = $imptTimes - $imptVoteTimes;

        return [$normLeftTimes,$imptLeftTimes,$gameConf,$gambleList];
    }

    /**
     * 计算竞猜胜率或更多详情
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $dateType  时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
     * @param bool $more      更多详情记录(flase:否 true:是 默认为否)
     * @param bool $isCount   是否只计算竞猜场数(flase:否 true:是 默认为否)
     * @param int  $playType  玩法(1:让分;-1:大小 默认为0，不分玩法)
     * @return int or array  #
    */
    public function CountWinrate($id,$gameType=1,$dateType=1,$more=false,$isCount=false,$playType=0){
        //日期筛选
        list($begin,$end) = getRankBlockDate($gameType,$dateType);

        $gameModel = $gameType == 1 ? M('gamble') : M('gamblebk');

        //查询竞猜数据
        $where['user_id']    = $id;
        $where['result']     = array("IN",array('1','0.5','2','-1','-0.5'));

        //加上对应时间
        $time = $gameType == 1 ? (10*60+32)*60 : (12*60)*60;

        $where['create_time']  = array( "between",array( strtotime($begin) + $time, strtotime($end) + 86400 + $time ) );

        if($playType){
            $where['play_type'] = (int)$playType;
        }

        if($isCount){
            return $gameModel->where($where)->field("create_time")->select(); //只计算竞猜场数
        }

        $gambleArr = $gameModel->field(['result','earn_point'])->where($where)->select();

        //计算胜率
        $win        = 0;
        $half       = 0;
        $level      = 0;
        $transport  = 0;
        $donate     = 0;
        $pointCount = 0;

        foreach ($gambleArr as $k => $v)
        {
            if($v['result'] == '1'){
                $win++;
            }
            if($v['result'] == '0.5'){
                $half++;
            }
            if($v['result'] == '2'){
                $level++;
            }
            if($v['result'] == '-1'){
                $transport++;
            }
            if($v['result'] == '-0.5'){
                $donate++;
            }
            if($v['earn_point'] > 0){
                $pointCount += $v['earn_point'];
            }
        }
        if($gameType == 1)
        {
            $winTotal    = $win + $half*0.5;
            $gambleTotal = $winTotal + $transport + $donate*0.5;
            $winrate     = $gambleTotal ? round(($winTotal/$gambleTotal)*100) : 0;
        }
        else
        {
            $gambleTotal = $win + $transport;
            $winrate     = $gambleTotal ? round(($win/$gambleTotal)*100) : 0;
        }

        //获取详细竞猜记录
        if ($more)
        {
            $count = count($gambleArr);
            return array(
                "winrate"    =>  $winrate,
                'count'      =>  $count,
                'win'        =>  $win,
                'half'       =>  $half,
                'level'      =>  $level,
                'transport'  =>  $transport,
                'donate'     =>  $donate,
                'pointCount' =>  $pointCount,
                'begin_date' =>  $begin,
                'end_date'   =>  $end,
                'user_id'    =>  $id,
                'gameCount'  =>  $count,
            );
        }

        return $winrate;
    }

    /**
     * 获取近十场竞猜结果
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @return  array
    */
    public function getTenGamble($id,$gameType=1){
        $where['user_id']    = $id;
        $where['result']     =['in',[1,0.5,2,-1,-0.5]];
        //赛事类型
        $Model = $gameType == 1 ? M('gamble') : M('gamblebk');
        $tenArray = $Model->where($where)->order("id desc")->limit(10)->getField('result',true);
        return $tenArray;
    }

   /**
    * 获取排行榜(读取数据表)
    * @param int        $gameType     赛事类型(1:足球   2:篮球   默认为1)
    * @param int        $dateType     时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
    * @param int        $user_id      是否查找指定用户,默认为否
    * @param bool       $more         获取最近10场、连胜记录,默认为否
    * @param string     $page         页数
    * @param string     $pageNum      每页条数
    * @param boolean    $todayGamble  是否筛选今日有竞猜
    */
    public function getRankingData($gameType=1,$dateType=1,$user_id=null,$more=false,$page='',$pageNum='',$todayGamble=false)
    {
        $cacheKey = MODULE_NAME.'_game_rank:'.implode('',func_get_args());

        if($Ranking = S($cacheKey))
            return $Ranking;

        list($begin,$end)      = getRankDate($dateType);
        $where['r.gameType']   = $gameType;
        $where['r.dateType']   = $dateType;
        $where['r.begin_date'] = ["between",array($begin,$end)];
        $where['r.end_date']   = ["between",array($begin,$end)];

        //查看是否有上周/月/季的数据
        $count = M('rankingList r')->where($where)->count();

        if (!$count)
        {
            list($begin,$end) = getTopRankDate($dateType);  //获取上上周的数据
            $where['r.begin_date'] = ["between",array($begin,$end)];
            $where['r.end_date']   = ["between",array($begin,$end)];
        }

        if($user_id !== null)
            $where['r.user_id']  = $user_id;


        //从数据表获取排行榜
        $field = ['r.user_id','r.ranking','r.gameCount','r.win','r.half','r.`level`','r.transport','r.donate','r.winrate','r.pointCount','f.nick_name','f.head','f.lv','f.lv_bk'];

        if ($page && $pageNum) //是否分页
        {
            if ($todayGamble)  //这里只判断排行榜的是否筛选今日有竞猜的用户
            {
                $blockTime = getBlockTime(1,$gamble=true);

                $Ranking = M('rankingList r')
                            ->field($field)
                            ->join('left join __GAMBLE__ g on g.user_id = r.user_id')
                            ->join('left join qc_front_user f on f.id = r.user_id')
                            ->where(array_merge($where,['g.create_time'=>['between',[$blockTime['beginTime'],$blockTime['endTime']]]]))
                            ->group('r.user_id')
                            ->order('r.ranking')
                            ->page($page.','.$pageNum)
                            ->select();
            }
            else
            {
                $Ranking = M('rankingList r')
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->field($field)
                    ->where($where)
                    ->order("ranking asc")
                    ->page($page.','.$pageNum)
                    ->select();
            }
        }
        else
        {
            $Ranking = M('rankingList r')
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->field($field)
                ->where($where)
                ->order("ranking asc")
                ->select();
        }

        if ($more)
        {
            foreach ($Ranking as $k => $v) {
                $Ranking[$k]['nick_name']   = M('FrontUser')->where(array('id'=>$v['user_id']))->getField('nick_name');
                $Ranking[$k]['tenArray']    = $this->getTenGamble($v['user_id'],$dateType);
                $Ranking[$k]['Winning']     = $this->getWinning($v['user_id'],$gameType);
            }
        }

        //缓存
        S($cacheKey, $Ranking, 600);

        return $Ranking;
    }

    /**
    * 获取连胜记录 当前连胜和最大连胜
    * @param int  $id        会员id
    * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
    * @param int  $playType  玩法(1:让分;-1:大小 默认为0，不分玩法 )
    * @return  array
    */
    public function getWinning($id,$gameType=1,$playType=0)
    {
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        if($playType){
            $gamble = (array)$GambleModel->where(['user_id'=>$id,'result'=>['neq',0],'play_type'=>$playType])->order("id desc")->getField('result',true);
        }else{
            $gamble = (array)$GambleModel->where(['user_id'=>$id,'result'=>['neq',0]])->order("id desc")->getField('result',true);
        }

        $curr_victs = 0; //当前连胜

        foreach ($gamble as $v)
        {
            if($v == 1 || $v == 0.5)
                $curr_victs++;

            if($v == -1 || $v == -0.5)
                break;
        }

        $temp = $max_victs = $win = $level = $transport = 0;

        foreach ($gamble as $v)
        {
            if ($v == 1 || $v == 0.5) //赢、赢半
            {
                $temp++;
                if ($temp > $max_victs)
                    $max_victs = $temp;

                $win++;
            }
            else if ($v == -1 || $v == -0.5) //输、输半
            {
                $temp = 0;
                $transport++;
            }
            else if ($v == 2) //平
            {
                $level++;
            }
            else //其他推迟取消的
            {
                continue;  //需考虑推迟、取消的赛程结果值为-14,-13等
            }
        }

        return [
            'curr_victs'  => (string)$curr_victs,
            'max_victs'   => (string)$max_victs,
            'total_times' => (int)$GambleModel->where(['user_id'=>$id])->count(), //竞猜总场次包括未结算的
            'win'         => $win,
            'level'       => $level,
            'transport'   => $transport
        ];
    }


    /**
     * 用户查看竞猜
     * @param  int $userid    用户id
     * @param  int $coverid   被查看用户id
     * @param  int $unableCoin    用户剩余不可提球币
     * @param  int $leftCoin      用户剩余可提球币
     * @param  int $coin      消耗球币数量
     * @param  int $gameId    赛程id
     * @param  int $gambleId  竞猜记录id
     * @param  int $platform  平台 1：web  2：IOS  3：ANDRIOD
     * @param  int $game_type 赛事类型 1：足球  2：篮球
     * @return bool           是否交易成功
     */
    public function trade($userid,$coverid,$unableCoin,$leftCoin,$coin,$gameId,$gambleId,$platform=1,$game_type=1)
    {
        M()->startTrans(); //开启事务
        //获取竞猜配置
        $sign = $game_type == 1 ? 'fbConfig' : 'bkConfig';
        $gameConf = getWebConfig($sign);
        //获取被查看用户等级
        $userLv = M('FrontUser')->where(['id'=>$coverid])->getField('lv');
        //对应登录销售分成百分比
        $split = $gameConf['userLv'][$userLv]['split'];
        $FrontUser = M('FrontUser');
        $tradeCoin = ceil($coin*($split/100));            //销售分成

        if ($coin != 0 && $tradeCoin != 0)
        {
            //查看者添加球币交易记录
            $userCoin = $FrontUser->master(true)->where(array('id'=>$userid))->field("coin,unable_coin")->find();
            $rs1 = $FrontUser->where(array('id'=>$userid))->save(['unable_coin'=>$unableCoin,'coin'=>$leftCoin]); //查看者减少球币
            $rs3 = M('AccountLog')->add([
                'user_id'    =>  $userid,
                'log_time'   =>  time(),
                'log_type'   =>  3,
                'game_type'  =>  $game_type,
                'log_status' =>  1,
                'change_num' =>  $coin,
                'total_coin' =>  $userCoin['coin']+$userCoin['unable_coin']-$coin,
                'gamble_id'  =>  $gambleId,
                'desc'       =>  "查看竞猜记录",
                'platform'   =>  $platform,
                'operation_time' => time()
            ]);

            //被查看者添加球币交易记录
            $coverCoin = $FrontUser->master(true)->where(array('id'=>$coverid))->field("coin,unable_coin")->find();
            $rs2 = $FrontUser->where(array('id'=>$coverid))->setInc('coin',$tradeCoin); //被查看者增加分成
            $rs4 = M('AccountLog')->add([
                'user_id'    =>  $coverid,
                'log_time'   =>  time(),
                'log_type'   =>  4,
                'game_type'  =>  $game_type,
                'log_status' =>  1,
                'change_num' =>  $tradeCoin,
                'total_coin' =>  $coverCoin['coin']+$coverCoin['unable_coin']+$tradeCoin,
                'gamble_id'  =>  $gambleId,
                'desc'       =>  "被查看竞猜记录",
                'platform'   =>  $platform,
                'operation_time' => time()
            ]);

            //发送被查看通知
            $rs5 = M('msg')->add([
                'title'      =>'销售收入通知',
                'content'    =>"恭喜您！您发布的竞猜被查看，获得{$tradeCoin}金币收入，详情请查看账户明细。",
                'send_time'  =>time(),
                'front_user_id'=>$coverid
            ]);
        }

        //添加交易查看记录
        $rs6 = M('QuizLog')->add([
            'game_type' => $game_type,
            'user_id'   => $userid,
            'cover_id'  => $coverid,
            'game_id'   => $gameId,
            'gamble_id' => $gambleId,
            'log_time'  => time(),
            'platform'  => $platform,
            'coin'      => $coin,
            'cover_coin'=> $tradeCoin,
        ]);

        //添加查看数量
        $GambleModel = $game_type == 1 ? M('Gamble') : M('Gamblebk');
        $rs7 = $GambleModel->where(['id'=>$gambleId])->setInc('quiz_number',1);

        if (isset($rs1))
        {
            if ($rs1 !== false &&  $rs2 !== false && $rs3 && $rs4 && $rs5 && $rs6 && $rs7)
                return M()->commit();
            else
                M()->rollback();
        }
        else
        {
            if ($rs6)
                return M()->commit();
            else
                M()->rollback();
        }
    }

    /**
     * 获取用户足球的竞猜记录
     * @param  mixed  $userid     用户id
     * @param  mixed  $playType   竞猜玩法
     * @param  int    $page       页数
     * @return mixed              记录列表
     */
    public function getGambleList($userid,$playType=null,$page=null,$gamble_id=0)
    {
        $field = [
            'g.id gamble_id',
            'g.user_id',
            'g.game_id',
            'g.union_name',
            'g.home_team_name',
            'g.away_team_name',
            'g.game_date',
            'g.game_time',
            'gf.score gf_score',
            'gf.half_score gf_half_score',
            'g.score',
            'g.half_score',
            'g.play_type',
            'g.chose_side',
            'g.handcp',
            'g.odds',
            'g.result',
            'g.tradeCoin',
            'g.vote_point',
            'g.earn_point',
            'g.create_time',
            'g.desc',
            'qu.union_color',
            'gf.game_state'
        ];

        $pageNum = 10;

        $where['g.user_id']   = is_array($userid) ? ['in',$userid] : $userid;

        $where['g.play_type'] = ['in', [1,-1]];
        if (in_array(intval($playType),[1,-1]))
            $where['g.play_type'] = $playType;

        $joinUnion    = "LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id";
        $joinGameInfo = "LEFT JOIN qc_game_fbinfo AS gf ON g.game_id = gf.game_id";
        $orderBy      = 'g.id desc';

        if ($page)
        {
            if($gamble_id){
                $where['g.id'] = ['lt', (int)$gamble_id];
                $list = M('Gamble g')->join($joinUnion)->join($joinGameInfo)->field($field)->where($where)->limit($pageNum)->order($orderBy)->select();
            }else{
                $list = M('Gamble g')->join($joinUnion)->join($joinGameInfo)->field($field)->where($where)->page($page.','.$pageNum)->order($orderBy)->select();
            }
        }
        else
        {
            $list = M('Gamble g')->join($joinUnion)->join($joinGameInfo)->field($field)->where($where)->order($orderBy)->limit(10)->select();
        }

        foreach ($list as $k => $v)
        {
            if ($list[$k]['game_state'] == '-1')
            {
                $list[$k]['score']      = $list[$k]['gf_score'];
                $list[$k]['half_score'] = $list[$k]['gf_half_score'];
            }

            unset($list[$k]['gf_score']);
            unset($list[$k]['gf_half_score']);

            $list[$k]['union_name']     = explode(',', $v['union_name']);
            $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);
            $list[$k]['desc'] = (string)$v['desc'];
        }

        return empty($list) ? array() : $list;
    }

    /**
     * 获取单场竞猜的信息
     * @param  int $gambleId 竞猜记录id
     * @return arr
     */
    public function getGambleInfo($gambleId)
    {
        $field = [
            'g.id gamble_id',
            'g.user_id',
            'g.union_name',
            'g.home_team_name',
            'g.away_team_name',
            'g.game_date',
            'g.game_time',
            'g.score',
            'g.half_score',
            'g.play_type',
            'g.chose_side',
            'g.handcp',
            'g.odds',
            'g.tradeCoin',
            'g.desc',
            'g.result',
            'qu.union_color'
        ];

        $info = M('Gamble')->alias('g')->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                ->field($field)->where(['g.id'=>$gambleId])->find();

        //旧表没有就去查新表
        if(!$info){
            $info = M('GambleReset')->alias('g')->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                    ->field($field)->where(['g.id'=>$gambleId])->find();
        }

        $info['union_name']     = explode(',', $info['union_name']);
        $info['home_team_name'] = explode(',', $info['home_team_name']);
        $info['away_team_name'] = explode(',', $info['away_team_name']);

        return $info;
    }

    /**
     * 获取篮球盘口和赔率
     * @param  $param 竞猜数据
     * @return 传值引用
     */
    public function getHandcpAndOddsBk(&$param){
        $Tool = new \Think\Tool\Tool();
        $date = time() < strtotime('16:00') ? date('Ymd',strtotime('-1 day')) : date('Ymd');
        $gamble = $Tool->getHttpContent('http://'.DOMAIN.'/Home/Gdata/nbaodds/date/'.$date);
        $xml    = $Tool->simplest_xml_to_array($gamble);
        foreach ($xml->o as $key => $value) {
            $array = explode(',', $value);
            if($array[0] == $param['game_id']){
                switch ($param['play_type']) {
                    case '1':
                        $param['handcp'] = $array[2];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $array[1];
                                break;
                            case '-1':
                                $param['odds'] = $array[3];
                                break;
                        }
                        break;
                    case '-1':
                        $param['handcp'] = $array[5];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $array[4];
                                break;
                            case '-1':
                                $param['odds'] = $array[6];
                                break;
                        }
                        break;
                    case '2':
                        $array = M('gameBkinfo')->where(['game_id'=>$param['game_id']])->field("psw_exp_home,psw_exp,psw_exp_away")->find();
                        $param['handcp'] = $array['psw_exp'];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $array['psw_exp_home'];
                                break;
                            case '-1':
                                $param['odds'] = $array['psw_exp_away'];
                                break;
                        }
                        break;
                    case '-2':
                        $array = M('gameBkinfo')->where(['game_id'=>$param['game_id']])->field("psw_total_home,psw_total,psw_total_away")->find();
                        $param['handcp'] = $array['psw_total'];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $array['psw_total_home'];
                                break;
                            case '-1':
                                $param['odds'] = $array['psw_total_away'];
                                break;
                        }
                        break;
                }
            }
        }
        if($param['handcp'] == '' || $param['odds'] == ''){
            //接口没有数据读取数据库
            $array = M('gameBkinfo')->where(['game_id'=>$param['game_id']])->field("fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away,psw_exp_home,psw_exp,psw_exp_away,psw_total_home,psw_total,psw_total_away")->find();
            switch ($param['play_type']) {
                case '1':
                    $param['handcp'] = $array['fsw_exp'];
                    switch ($param['chose_side']) {
                        case '1':
                            $param['odds'] = $array['fsw_exp_home'];
                            break;
                        case '-1':
                            $param['odds'] = $array['fsw_exp_away'];
                            break;
                    }
                    break;
                case '-1':
                    $param['handcp'] = $array['fsw_total'];
                    switch ($param['chose_side']) {
                        case '1':
                            $param['odds'] = $array['fsw_total_home'];
                            break;
                        case '-1':
                            $param['odds'] = $array['fsw_total_away'];
                            break;
                    }
                    break;
                case '2':
                    $param['handcp'] = $array['psw_exp'];
                    switch ($param['chose_side']) {
                        case '1':
                            $param['odds'] = $array['psw_exp_home'];
                            break;
                        case '-1':
                            $param['odds'] = $array['psw_exp_away'];
                            break;
                    }
                    break;
                case '-2':
                    $param['handcp'] = $array['psw_total'];
                    switch ($param['chose_side']) {
                        case '1':
                            $param['odds'] = $array['psw_total_home'];
                            break;
                        case '-1':
                            $param['odds'] = $array['psw_total_away'];
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * 获取足球盘口和赔率
     * @param  $param 竞猜数据
     * @return 传值引用
     */
    public function getHandcpAndOdds(&$param){
        $pcData = new \Home\Services\PcdataService();
        $res = $pcData->getOddsById($param['game_id'],2);
        foreach ($res as $k => $v) {
            switch ($param['play_type']) {
                case '1':
                    //让分
                    if($v[18] != '' && $v[19] != '' && $v[20] != '')
                    {
                        //滚球
                        $param['handcp'] = $v[19];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $v[18]; //主
                                break;
                            case '-1':
                                $param['odds'] = $v[20]; //客
                                break;
                        }
                    }
                    else if($v[9] != '' && $v[10] != '' && $v[11] != '')
                    {
                        //即时
                        $param['handcp'] = $v[10];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $v[9]; //主
                                break;
                            case '-1':
                                $param['odds'] = $v[11]; //客
                                break;
                        }
                    }
                    else if($v[0] != '' && $v[1] != '' && $v[2] != '')
                    {
                        //初盘
                        $param['handcp'] = $v[1];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $v[0]; //主
                                break;
                            case '-1':
                                $param['odds'] = $v[2]; //客
                                break;
                        }
                    }
                    break;
                case '-1':
                    //大小
                    if($v[21] != '' && $v[22] != '' && $v[23] != '')
                    {
                        //滚球
                        $param['handcp'] = $v[22];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $v[21]; //主
                                break;
                            case '-1':
                                $param['odds'] = $v[23]; //客
                                break;
                        }
                    }
                    else if($v[12] != '' && $v[13] != '' && $v[14] != '')
                    {
                        //即时
                        $param['handcp'] = $v[13];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $v[12]; //主
                                break;
                            case '-1':
                                $param['odds'] = $v[14]; //客
                                break;
                        }
                    }
                    else if($v[3] != '' && $v[4] != '' && $v[5] != '')
                    {
                        //初盘
                        $param['handcp'] = $v[4];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $v[3]; //主
                                break;
                            case '-1':
                                $param['odds'] = $v[5]; //客
                                break;
                        }
                    }
                    break;
            }
        }
        if($param['handcp'] == '' || $param['odds'] == ''){
            //接口没有数据读取数据库
            $array = M('gameFbinfo')->where(['game_id'=>$param['game_id']])->field("fsw_exp_home,fsw_exp,fsw_exp_away,fsw_ball_home,fsw_ball,fsw_ball_away")->find();
            switch ($param['play_type']) {
                case '1':
                    $param['handcp'] = $array['fsw_exp'];
                    switch ($param['chose_side']) {
                        case '1':
                            $param['odds'] = $array['fsw_exp_home'];
                            break;
                        case '-1':
                            $param['odds'] = $array['fsw_exp_away'];
                            break;
                    }
                    break;
                case '-1':
                    $param['handcp'] = $array['fsw_ball'];
                    switch ($param['chose_side']) {
                        case '1':
                            $param['odds'] = $array['fsw_ball_home'];
                            break;
                        case '-1':
                            $param['odds'] = $array['fsw_ball_away'];
                            break;
                    }
                    break;
            }
        }
    }

    /**
     * v2.2首页——“筛选命中率高”、“连胜数多”的用户
     */
    public function getIndexUser(){
        $blockTime = getBlockTime(1, $gamble = true);//获取赛程分割日期的区间时间
        $dateType  = 1;
        $rankDate  = getRankDate($dateType);//获取上个周期的日期
        $countNum  = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->count();

        if (!$countNum) {
            $rankDate = getTopRankDate($dateType);//获取上个周期的数据
        }

        $arr = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->order('ranking ASC')->limit(50)->getField('user_id, winrate', true);
        $userArr = $victsUser = array_keys($arr);
        $tenGambleRateArr = $victsArr = $winArr = array();

        foreach ($userArr as $k => $v) {
            //命中率高
            $tenGamble = D('GambleHall')->getTenGamble($v, 1);
            $tenGambleRate = countTenGambleRate($tenGamble);//近十场的胜率;
            $tenGambleRateArr[$v] = $tenGambleRate;

            //连胜数多
            $winnig = D('GambleHall')->getWinning($v, $gameType = 1); //连胜记录
            $victsArr[$v] = $winnig['curr_victs'];//连胜场数
            $winArr[$v] = $winnig['win'];//胜场数

            unset($tenGamble, $winnig);
        }

        array_multisort(array_values($tenGambleRateArr), SORT_DESC, array_values($arr), SORT_DESC, $userArr);
        array_multisort(array_values($victsArr), SORT_DESC, array_values($arr), SORT_DESC, $victsUser);

        $userArr = array_slice($userArr, 0, 10);//命中率高
        $victsUser = array_slice($victsUser, 0, 10);//连胜数多

        $lastUserArr = S('lastUserArr' . MODULE_NAME);
        $lastUser = $lastUserArr[0]['user_id'];//上一个高命中用户

        //命中率高
        if ($lastUser) {
            $res1 = array_rand(array_diff($userArr, array($lastUser)));
        } else {//第一次请求接口
            $res1 = array_rand($userArr);
        }

        //连胜数多
        $res2 = array_rand(array_diff($victsUser, array($userArr[$res1])));

        $res = M('FrontUser')->field('id as user_id, nick_name, head as face, lv')->where(['id' => ['in', array($userArr[$res1], $victsUser[$res2])]])
            ->order('field(id, ' . $victsUser[$res2] . ',' . $userArr[$res1] . ') DESC')->select();

        foreach ($res as $k => $v) {
            $res[$k]['face']          = frontUserFace($v['face']);
            $res[$k]['weekPercnet']   = $arr[$v['user_id']];//取周榜的
            $res[$k]['monthPercnet']  = (string)D('GambleHall')->CountWinrate($v['user_id'], 1, 2);//月胜率
            $res[$k]['tenGambleRate'] = $tenGambleRateArr[$v['user_id']];//命中率
            $res[$k]['curr_victs']    = $victsArr[$v['user_id']];//连胜
            $res[$k]['win']           = (string)$winArr[$v['user_id']];//总胜数

            $one = M('Gamble')->where(['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->order('id desc')->find();

            if ($one) {//若当天竞猜存在
                $res[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                $res[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
            } else {
                $res[$k]['todayHomeName'] = '';//当天推荐主队名称
                $res[$k]['todayAwayName'] = '';//当天推荐客队名称
            }
            unset($one);
        }

        return $res;
    }

    /**
     * V2..2首页新版——高手竞猜
     */
    public function masterGamble($userToken){
        $blockTime = getBlockTime(1, $gamble = true);//获取赛程分割日期的区间时间

        $field = ' g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head as face, u.lv, qu.union_color ';

        $dateType = 1;
        $rankDate = getRankDate($dateType);//获取上个周期的日期
        $countNum = M('RankingList')->where(['dateType' => $dateType, 'gameType' => 1, 'begin_date' => ['between', [$rankDate[0], $rankDate[1]]]])->count();

        if (!$countNum) {
            $rankDate = getTopRankDate($dateType);//获取上个周期的数据
        }

        $where['l.dateType']    = 1;
        $where['l.gameType']    = 1;
        $where['l.begin_date']  = ['between', [$rankDate[0], $rankDate[1]]];
        $where['l.ranking']     = ['lt', 101];
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
        $where['g.tradeCoin']   = ['gt', 0];
        $where['g.play_type']   = ['in', [-1,1]];

        //先取gamble_id,前100名，只要最新的前30条
        $gambleList = (array)M('Gamble g')->join(' LEFT JOIN qc_ranking_list AS l ON g.user_id = l.user_id ')
                      ->where($where)->group('g.user_id')->order('l.ranking ASC')
                      ->limit(30)->getField('max(g.id)', true);

        //再取内容
        if ($gambleList) {
            $where1['g.id'] = ['in', $gambleList];
            $res = M('Gamble g')->field($field)
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where1)->order('field(g.id, ' . implode(',', array_reverse($gambleList)) . ') DESC')
                ->select();

            foreach ($res as $k => $v) {
                $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
                $res[$k]['tenGambleRate']  = countTenGambleRate($tenGamble);//近十场的胜率;
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                $res[$k]['curr_victs']     = $winnig['curr_victs'];//连胜场数

                $res[$k]['face']           = frontUserFace($v['face']);
                $res[$k]['weekPercnet']    = (string)D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周胜率
                $res[$k]['union_name']     = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc']           = (string)$v['desc'];

                //如已经登陆,判断当前用户是否有购买当前信息
                if ($userToken) {
                    $res[$k]['is_trade'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id' => $v['gamble_id'], 'user_id' => $userToken['userid']])->getField('id') ? 1 : 0;//是否已查看购买过
                } else {//无登录则全部没有购买
                    $res[$k]['is_trade'] = 0;
                }
                unset($tenGamble, $winnig);
            }
        }

        return $gambleList ? $res : array();
    }

    /**
     * 数据重置
     * @param $userid   int 用户id
     * @param $platform int 平台
     * @return int
     */
    public function resetGambleData($userid, $platform)
    {
        //先判断金币数量
        $userInfo = M('FrontUser')->field(['coin','unable_coin','(coin+unable_coin) as totalCion'])->where(['id'=>$userid])->find();

        $coin = 5;
        if ($userInfo['unable_coin'] < $coin) {
            $userInfo['coin'] = $userInfo['coin'] - ($coin - $userInfo['unable_coin']);
            $userInfo['unable_coin'] = 0;

            if ($userInfo['coin'] < 0) return 1072;

        } else {
            $userInfo['unable_coin'] -= $coin;
        }

        M()->startTrans();

        //转移数据
        $data = M('Gamble')->field('is_reset', true)->where(['user_id' => $userid])->select();
        //无数据则不重置，提示
        if(empty($data))
            return 1074;

        $e1 = M('GambleReset')->addAll($data);
        $e2 = M('Gamble')->where(['user_id' => $userid])->delete();

        //修改相关表
        $res1 = M('FrontUser')->where(['id'=>$userid])->save(['unable_coin'=>$userInfo['unable_coin'],'coin'=>$userInfo['coin']]);

        $res2 = M('AccountLog')->add([
                'user_id'    =>  $userid,
                'log_time'   =>  NOW_TIME,
                'log_type'   =>  14,
                'log_status' =>  1,
                'change_num' =>  $coin,
                'total_coin' =>  $userInfo['totalCion']-$coin,
                'desc'       =>  "重置数据",
                'platform'   =>  $platform,
                'operation_time' => NOW_TIME
        ]);

        if (!$e1 || !$e2 || !$res1 || !$res2) {
            M()->rollback();
            return 1073;
        }

        M()->commit();

        return 1;
    }

}