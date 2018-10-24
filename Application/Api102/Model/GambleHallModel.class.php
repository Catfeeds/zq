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
            if ($v['gtime'] + 60 < time() && $v['game_state'] == 0)  //过了开场时间未开始
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

            if ((MODULE_NAME == 'Api' || MODULE_NAME == 'Api102') && $v['game_state'] < 0)
                $sort_game_state[] = $v['game_state'] == '-1' ? 1000 : abs($v['game_state']);
            else
                $sort_game_state[] = $v['game_state'];

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

        if (MODULE_NAME == 'Api' || MODULE_NAME == 'Api102')
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
                             ->field('g.game_id,g.show_date, g.union_id, g.union_name, g.game_date, g.game_time,g.game_half_time,
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

            if ($descLenth < 10 || $descLenth > 50)
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

        //增加竞猜分析的积分记录
        if (!empty($param['desc']) && $gameConf['gamble_desc'] != 0)
        {
            $changePoint = $gameConf['gamble_desc'];
            $totalPoint = $userInfo['point'] + $changePoint;

            M('FrontUser')->where(['id'=>$userid])->setInc('point',$changePoint);
            M('PointLog')->add([
                'user_id'     => $userid,
                'log_time'    => NOW_TIME,
                'log_type'    => 12,
                'gamble_id'   => $param['game_id'],
                'change_num'  => $changePoint,
                'total_point' => $totalPoint,
                'desc'        => '竞猜分析'
            ]);
        }

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
        $startTime = strtotime($begin)+$time;
        $endTime   = strtotime($end)+86400+$time;
        $where['create_time']  = array("between",array($startTime,$endTime));

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
            return array(
                "winrate"    =>  $winrate,
                'count'      =>  count($gambleArr),
                'win'        =>  $win,
                'half'       =>  $half,
                'level'      =>  $level,
                'transport'  =>  $transport,
                'donate'     =>  $donate,
                'pointCount' =>  $pointCount,
                'begin_date' =>  $begin,
                'end_date'   =>  $end,
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
    * @param int   $gameType     赛事类型(1:足球   2:篮球   默认为1)
    * @param int   $dateType     时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
    * @param int   $user_id      是否查找指定用户,默认为否
    * @param bool  $more         获取最近10场、连胜记录,默认为否
    * @return  mixed
    */
    public function getRankingData($gameType=1,$dateType=1,$user_id=null,$more=false,$page='',$pageNum='')
    {
        list($begin,$end)    = getRankDate($dateType);
        $where['gameType']   = $gameType;
        $where['dateType']   = $dateType;
        $where['begin_date'] = array("between",array($begin,$end));
        $where['end_date']   = array("between",array($begin,$end));

        if($user_id !== null){
           $where['user_id']  = $user_id;
        }

        //查看是否有上周/月/季的数据
        $count = M('rankingList')->where($where)->count();

        if (!$count)
        {
            list($begin,$end) = getTopRankDate($dateType);  //获取上上周的数据
            $where['begin_date'] = array("between",array($begin,$end));
            $where['end_date']   = array("between",array($begin,$end));
        }

        //从数据表获取排行榜
        $field = ['user_id','ranking','gameCount','win','half','level','transport','donate','winrate','pointCount'];

        if ($page && $pageNum) //是否分页
            $Ranking = M('rankingList')->field($field)->where($where)->order("ranking asc")->page($page.','.$pageNum)->select();
        else
            $Ranking = M('rankingList')->field($field)->where($where)->order("ranking asc")->select();

        if ($more)
        {
            foreach ($Ranking as $k => $v) {
                $Ranking[$k]['nick_name'] = M('FrontUser')->where(array('id'=>$v['user_id']))->getField('nick_name');
                $Ranking[$k]['tenArray'] = $this->getTenGamble($v['user_id'],$dateType);
                $Ranking[$k]['Winning'] = $this->getWinning($v['user_id'],$gameType);
            }
        }

        return $Ranking;
    }

    /**
     * 获取用户的排行榜
     * @param int   $gameType     赛事类型(1:足球   2:篮球   默认为1)
     * @param int   $user_id      用户id
     * @return  mixed
     */
    public function getUserRank($gameType=1,$user_id)
    {
        for ($i=1; $i <= 3; $i++)
        {
            $rank = (int)$this->getRankingData($gameType,$i,$user_id)[0]['ranking'];

            if ($rank && $rank <= 100)
                return $rank;
        }

        return 999999;
    }

    /**
    * 获取连胜记录 当前连胜和最大连胜
    * @param int  $id        会员id
    * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
    * @param int  $playType  玩法(1:让分;-1:大小 默认为0，不分玩法)
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
            'g.result',
            'g.tradeCoin',
            'g.vote_point',
            'g.earn_point',
            'g.create_time',
            'g.desc',
            'qu.union_color'
        ];

        $pageNum = 20;

        $where['g.user_id']   = is_array($userid) ? ['in',$userid] : $userid;
        if ($playType)
            $where['g.play_type'] = $playType;

        if ($page)
            if($gamble_id){
                $where['g.id'] = ['lt', (int)$gamble_id];
                $list = M('Gamble')->alias('g')->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                        ->field($field)->where($where)->limit($pageNum)->order('g.id desc')->select();
            }else{
                $list = M('Gamble')->alias('g')->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                        ->field($field)->where($where)->page($page.','.$pageNum)->order('g.id desc')->select();
            }
        else
            $list = M('Gamble')->alias('g')->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                    ->field($field)->where($where)->order('g.id desc')->limit(10)->select();

        foreach ($list as $k => $v)
        {
            $list[$k]['union_name']     = explode(',', $v['union_name']);
            $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);
        }

        return $list;
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
}