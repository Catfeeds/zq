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
    public function matchList($map='')
    {
        $show_date = time() >= strtotime('10:30') ? date('Ymd') : date("Ymd",strtotime("-1 day"));

        $sql = "
            select
                distinct g.game_id, g.union_id, g.union_name, u.union_color, g.game_date, g.game_time, g.game_half_time, g.game_state,
                g.home_team_name, g.home_team_id, g.score, g.half_score, g.away_team_name, g.away_team_id,
                g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away, g.fsw_ball_home, g.fsw_ball, g.fsw_ball_away
            from __PREFIX__game_fbinfo g
            left join __PREFIX__union u ON g.union_id = u.union_id
            where
                g.status = 1 ".$map."
                and g.fsw_exp != ''
                and g.fsw_ball != ''
                and g.fsw_exp_home != ''
                and g.fsw_exp_away != ''
                and g.fsw_ball_home != ''
                and g.fsw_ball_away != ''
                and (
                    (u.is_sub < 3 and g.is_show = 1)
                    or g.is_gamble = 1
                )
                and (
                    g.show_date = ".$show_date."
                    or (
                        g.show_date in (".date("Ymd",strtotime("$show_date -1 day")).','.date("Ymd",strtotime("$show_date +1 day")).")
                        and g.game_state in (1,2,3,4)
                    )
                )
            order by g.game_state desc,g.game_date asc,g.game_time asc
        ";

        $game = M()->query($sql);

        foreach ($game as $k => $v)
        {
            $game[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1);
            $game[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2);
        }

        $usql = "
            select u.union_id,u.union_name,count(u.union_id) union_num
            from __PREFIX__game_fbinfo g,__PREFIX__union u
            where
                g.show_date = ".$show_date."
            and g.fsw_exp != ''
            and g.fsw_ball != ''
            and g.union_id = u.union_id
            and (
                (u.is_sub < 3 and g.is_show = 1)
                or g.is_gamble = 1
            )
            group by union_id
            order by u.is_sub asc,u.id asc
        ";

        $union = M()->query($usql);

        if (stristr(MODULE_NAME,'Api'))
        {
            foreach ($game as $k => $v)
            {
                $game[$k]['union_name']     = explode(',', $v['union_name']);
                $game[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $game[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $halfTime = explode(',', $v['game_half_time']);
                $halfTime[1] = str_pad($halfTime[1]+1, 2, '0', STR_PAD_LEFT);
                $game[$k]['game_half_time'] = implode('', $halfTime);
            }

            foreach ($union as $k => $v)
            {
                $union[$k]['union_name'] = explode(',', $v['union_name']);
            }
        }

        return [$game,$union];
    }

    //篮球竞猜大厅
    public function basketballList($map='')
    {
        $game_date = getGameDate()['game_date'];
        $tomorrow  = getGameDate()['tomorrow'];
        $date = date("Ymd",strtotime("-1 day")).",".date('Ymd');
        $game = D("gameBkinfo")->table('qc_game_bkinfo g')
                             ->join('LEFT JOIN qc_bk_union b ON g.union_id = b.union_id')
                             ->field('g.game_id,g.show_date, g.union_id, g.union_name, b.union_color, g.game_date, g.game_time, g.game_half_time, g.game_state,g.total,g.home_team_name, g.home_team_id, g.score, g.half_score, g.away_team_name, g.away_team_id,
                g.fsw_exp_home, g.fsw_exp, g.fsw_exp_away, g.fsw_total_home, g.fsw_total, g.fsw_total_away,g.psw_exp_home,g.psw_exp,g.psw_exp_away,g.psw_total_home,g.psw_total,g.psw_total_away')
                             ->where("(((g.game_date = {$game_date} and g.game_time >= '12:00') or (g.game_date = {$tomorrow} and g.game_time <= '12:00')) or (g.game_date in ({$date}) and g.game_state in (1,2,50,3,4,5,6,7))) and status = 1 and ((b.is_sub<3 and g.is_show=1) or g.is_gamble=1) and (g.fsw_exp!='' or g.fsw_total!='')".$map)
                             ->group('g.game_id')
                             ->order('g.game_state desc,g.game_date asc,g.game_time asc')
                             ->select();
        foreach ($game as $k => $v)
        {
            $game[$k]['homeTeamLogo'] = getLogoTeam($v['home_team_id'],1,2);
            $game[$k]['awayTeamLogo'] = getLogoTeam($v['away_team_id'],2,2);
        }
        $union = D("bkUnion")->table('qc_bk_union b')
                             ->join('LEFT JOIN qc_game_bkinfo g ON b.union_id = g.union_id')
                             ->field('b.union_id,b.union_name,count(b.union_id) union_num')
                             ->where("(((g.game_date = {$game_date} and g.game_time >= '12:00') or (g.game_date = {$tomorrow} and g.game_time <= '12:00')) or (g.game_date in ({$date}) and g.game_state in (1,2,50,3,4,5,6,7))) and ((b.is_sub<3 and g.is_show=1) or g.is_gamble=1) and (g.fsw_exp!='' or g.fsw_total!='')")
                             ->group("b.union_id")
                             ->order('b.is_sub asc,b.id asc')
                             ->select();
        return [$game,$union];
    }

    //足球/篮球竞猜
    public function gamble($userid,$param,$platform,$gameType=1)
    {
        //获取盘口和赔率
        switch ($gameType) {
            case '1':
                self::getHandcpAndOdds($param);
                break;
            case '2':
                self::getHandcpAndOddsBk($param);
                break;
        }
        $gameModel   = $gameType == 1 ? M('GameFbinfo') : M('GameBkinfo');
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        $gameConf    = $gameType == 1 ? C('gamble') : C('gamblebk');
        if (
            $param['game_id']       == null
            || $param['play_type']  == null
            || $param['chose_side'] == null
            || $param['odds']       == null
            || $param['handcp']     == null
            || $param['is_impt']    == null
            || $platform            == null
            || $param['odds'] > 2
        )
        {
            return 201;
        }

        $field = ['union_id','union_name','game_id','game_date','game_time','home_team_name','away_team_name','show_date'];
        $gameInfo = $gameModel->field($field)->where(['game_id'=>$param['game_id']])->find();

        //判断竞猜时间
        if (time() > strtotime($gameInfo['game_date'].$gameInfo['game_time']))
            return 2002;

        //判断竞猜的类型，不可重复、冲突竞猜
        $condition['user_id'] = $userid;
        $condition['game_id'] = $param['game_id'];
        // if($gameType == 1){
        //     $condition['show_date'] = getShowDate();
        // }elseif ($gameType == 2) {
        //     //添加查询条件函数,位于function.php
        //     addSearchBk($condition);
        // }
        $gamble = (array)$GambleModel->field(['play_type','chose_side'])->where($condition)->select();

        foreach ($gamble as $v)
        {
            if ($v['play_type'] === $param['play_type'])
                return 2003;
        }

        //判断竞猜的次数是否已达上限
        $blockTime = getBlockTime($gameType,true);
        $where['user_id'] = $userid;
        $where['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];

        // if($gameType == 1)
        // {

        //     $show_date = getShowDate();
        //     $where['show_date'] = $show_date;
        // }
        // elseif ($gameType == 2)
        // {
        //     $game_date = getGameDate()['game_date'];
        //     //添加查询条件函数,位于function.php
        //     addSearchBk($where);
        // }
        $gambleList = $GambleModel->master(true)->field(['is_impt','count(*) vote_times'])->where($where)->group('is_impt')->select();
        // $date = $gameType == 1 ? $show_date : $game_date;

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
                $normVoteTimes = $v['vote_times'];

            if ($v['is_impt'] == 1)
                $imptVoteTimes = $v['vote_times'];
        }

        $normLeftTimes = $normTimes - $normVoteTimes;
        $imptLeftTimes = $imptTimes - $imptVoteTimes;

        if ($normLeftTimes <= 0 && $imptLeftTimes <= 0)
            return 2004;

        if ($normLeftTimes <= 0 && $param['is_impt'] == 0)
            return 2005;

        if ($imptLeftTimes <= 0 && $param['is_impt'] == 1)
            return 2006;

        $param['is_impt'] == 0 ? $normLeftTimes-- : $imptLeftTimes--;

        //增加竞猜记录
        $param['user_id']     = $userid;
        $param['game_type']   = '1';
        $param['vote_point']  = $param['is_impt'] ? C('gamble')['impt_point'] : C('gamble')['norm_point'];
        $param['create_time'] = time();
        $param['platform']    = $platform;

        $insertId = $GambleModel->add(array_merge($gameInfo,$param));

        if (!$insertId)
            return 2007;

        return ['normLeftTimes'=>$normLeftTimes,'imptLeftTimes'=>$imptLeftTimes];
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
    public function CountWinrate($id,$gameType=1,$dateType=1,$more=false,$isCount=false){
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
    * @return  array
    */
    public function getWinning($id,$gameType=1){
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        $gamble = $GambleModel->where(['user_id'=>$id,'result'=>['neq',0]])->order("id desc")->getField('result',true);

        $curr_victs = 0; //当前连胜
        foreach ($gamble as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $curr_victs++;
            }
            if($v == '-1' || $v == '-0.5'){
                break;
            }
        }

        $temp = $max_victs = 0;
        foreach ($gamble as $v)
        {
            if ($v == 1 || $v == 0.5)
            {
                $temp++;
                if ($temp > $max_victs)
                    $max_victs = $temp;
            }
            else if ($v == -1 || $v == -0.5)
                $temp = 0;
            else
                continue;  //需考虑推迟、取消的赛程结果值为-14,-13等
        }
        return ['curr_victs'=>(string)$curr_victs,'max_victs'=>(string)$max_victs];
    }


    /**
     * 用户查看竞猜
     * @param  int $userid    用户id
     * @param  int $coverid   被查看用户id
     * @param  int $unableCoin    用户不可提球币
     * @param  int $leftCoin      用户可提球币
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

        $FrontUser = M('FrontUser');
        $platformSetting = getWebConfig('platformSetting');
        $tradeCoin = ceil($coin*$platformSetting['userSales1']/100);            //销售分成

        //添加交易查看记录
        $rs3 = M('QuizLog')->add([
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

        //查看者添加球币交易记录
        $userCoin = $FrontUser->master(true)->where(array('id'=>$userid))->field("coin,unable_coin")->find();
        $rs1 = $FrontUser->where(array('id'=>$userid))->save(['unable_coin'=>$unableCoin,'coin'=>$leftCoin]); //查看者减少球币
        $rs4 = M('AccountLog')->add([
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
        $rs5 = M('AccountLog')->add([
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
        $rs6 = M('msg')->add([
            'title'      =>'销售收入通知',
            'content'    =>"恭喜您！您发布的竞猜被查看，获得{$tradeCoin}金币收入，详情请查看账户明细。",
            'send_time'  =>time(),
            'front_user_id'=>$coverid
        ]);

        //添加查看数量
        $GambleModel = $game_type == 1 ? M('Gamble') : M('Gamblebk');
        $rs7 = $GambleModel->where(['id'=>$gambleId])->setInc('quiz_number',1);

        if ($rs1 !== false &&  $rs2 !== false && $rs3 && $rs4 && $rs5 && $rs6 && $rs7)
            return M()->commit(); //提交事务
        else
            M()->rollback(); //回滚事务
    }

    /**
     * 获取用户足球的竞猜记录
     * @param  mixed  $userid     用户id
     * @param  int    $dateType   记录类型 1：今天，2：历史
     * @param  int    $page       页数
     * @return mixed              记录列表
     */
    public function getGambleList($userid,$dateType=1,$page=null)
    {
        $field = [
            'id gamble_id',
            'user_id',
            "SUBSTRING_INDEX(SUBSTRING_INDEX(`union_name`,',',2),',',-1) union_name",
            'game_date',
            'game_time',
            "SUBSTRING_INDEX(SUBSTRING_INDEX(`home_team_name`,',',2),',',-1) home_team_name",
            "SUBSTRING_INDEX(SUBSTRING_INDEX(`away_team_name`,',',2),',',-1) away_team_name",
            'score',
            'half_score',
            'is_impt',
            'play_type',
            'chose_side',
            'handcp',
            'odds',
            'result',
            'earn_point'
        ];

        $pageNum = 20;
        $blockTime = getBlockTime(1,true);

        $where = [
            'user_id'     => is_array($userid) ? ['in',$userid] : $userid,
            'create_time' => $dateType == 1 ? ['between',[$blockTime['beginTime'],$blockTime['endTime']]] : ['not between',[$blockTime['beginTime'],$blockTime['endTime']]]
        ];

        // $order = $dateType == 1 ? 'game_date,game_time' : 'create_time desc';
        $order = 'id desc';

        if ($page)
            $list = M('Gamble')->field($field)->where($where)->page($page.','.$pageNum)->order($order)->select();
        else
            $list = M('Gamble')->field($field)->where($where)->order($order)->select();

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
            'id gamble_id',
            'user_id',
            "SUBSTRING_INDEX(SUBSTRING_INDEX(`union_name`,',',2),',',-1) union_name",
            'game_date',
            'game_time',
            "SUBSTRING_INDEX(SUBSTRING_INDEX(`home_team_name`,',',2),',',-1) home_team_name",
            "SUBSTRING_INDEX(SUBSTRING_INDEX(`away_team_name`,',',2),',',-1) away_team_name",
            'score',
            'half_score',
            'is_impt',
            'play_type',
            'chose_side',
            'handcp',
            'odds',
            'result'
        ];

        return M('Gamble')->field($field)->where(['id'=>$gambleId])->find();
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
        if(!isset($param['handcp']) || !isset($param['odds'])){
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
        $Tool = new \Think\Tool\Tool();
        $gamble = $Tool->getHttpContent('http://'.DOMAIN.'/collect/bfdata/football/goals/goals3.xml');
        $xml    = $Tool->simplest_xml_to_array($gamble);
        foreach ($xml->match->m as $key => $value) {
            $array = explode(',', $value);
            if($array[0] == $param['game_id']){
                switch ($param['play_type']) {
                    case '1':
                        $param['handcp'] = $array[2];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $array[3];
                                break;
                            case '-1':
                                $param['odds'] = $array[4];
                                break;
                        }
                        break;
                    case '-1':
                        $param['handcp'] = $array[10];
                        switch ($param['chose_side']) {
                            case '1':
                                $param['odds'] = $array[11];
                                break;
                            case '-1':
                                $param['odds'] = $array[12];
                                break;
                        }
                        break;
                }
            }
        }
        if(!isset($param['handcp']) || !isset($param['odds'])){
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