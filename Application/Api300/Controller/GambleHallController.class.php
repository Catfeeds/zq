<?php
/**
 * 推荐大厅
 * @author huangjiezhen <418832673@qq.com> 2015.12.02
 */

class GambleHallController extends PublicController
{
    /**
     * 推荐大厅 (hzl)
     */
    public function index()
    {
        $info = $this->param['info'] ?: 'football';
        $type = $this->param['type'] ?: 1;

        if ($info == 'football') {
            list($game, $union) = D('GambleHall')->matchList($type);
            $adver = @Think\Tool\Tool::getAdList($classId = 20, 20, $this->param['platform']) ?: [];
            $adver = D('Home')->getBannerShare($adver);//添加分享图片和标题

            foreach ($adver as $k => $v) {
                unset($adver[$k]['id']);
                unset($adver[$k]['img']);
            }

            $this->ajaxReturn(['matchList' => $game, 'union' => $union, 'adver' => $adver]);
        } else if ($info == 'basketball') {
            $this->ajaxReturn([]);
        } else {
            $this->ajaxReturn(2001);
        }
    }

    /**
     * 点击赛程，进入推荐详情页 (hzl)
     */
    public function gamblePage ()
    {
        $blockTime  = getBlockTime(1);
        $userToken  = getUserToken($this->param['userToken']);
        $gameId     = $this->param['gameId'];
        $gameType   = $this->param['gameType'] ?: 1;
        $type       = $this->param['playType'] ?: 1;
        $has        = 0;

        $data['ypOdds'] = $data['jcOdds'] = '';
        $data['userGamble'] = [];

        $fields1 = ['g.id', 'g.gtime', 'is_betting','g.fsw_exp_home', 'g.fsw_exp', 'g.fsw_exp_away', 'g.fsw_ball_home', 'g.fsw_ball', 'g.fsw_ball_away'];
        $fields2 = ['g.id', 'g.gtime', 'bet.bet_code', 'bet.home_odds', 'bet.draw_odds', 'bet.away_odds', 'bet.let_exp', 'bet.home_letodds', 'bet.draw_letodds', 'bet.away_letodds'];

        //亚盘数据：盘口、赔率、统计
        switch($type){
            case 1:
                $game = M('GameFbinfo g')->field($fields1)->where(['g.game_id' => $gameId])->select()[0];
                $has = $game && $game['gtime'] >= $blockTime['beginTime'] && $game['gtime'] <= $blockTime['endTime'] ? 1 : 0;

                $res = (new \Home\Services\PcdataService())->getOddsById($gameId, 2)[$gameId];

                $data['ypOdds'] = [
                    'fsw_exp_home'  => $res[18] != '' ? $res[18] : ( $res[9] != '' ?  $res[9] : ($res[0] != '' ? $res[0] : $game[0]['fsw_exp_home'])),  //让球主队的赔率
                    'fsw_exp'       => $res[19] != '' ? $res[19] : ($res[10] != '' ? $res[10] : ($res[1] != '' ? $res[1] : $game[0]['fsw_exp'])),      //让球盘口
                    'fsw_exp_away'  => $res[20] != '' ? $res[20] : ($res[11] != '' ? $res[11] : ($res[2] != '' ? $res[2] : $game[0]['fsw_exp_away'])), //让球客队赔率
                    'fsw_ball_home' => $res[21] != '' ? $res[21] : ($res[12] != '' ? $res[12] : ($res[3] != '' ? $res[3] : $game[0]['fsw_ball_home'])),//大小球 大的赔率
                    'fsw_ball'      => $res[22] != '' ? $res[22] : ($res[13] != '' ? $res[13] : ($res[4] != '' ? $res[4] : $game[0]['fsw_ball'])),     //大小球盘口
                    'fsw_ball_away' => $res[23] != '' ? $res[23] : ($res[14] != '' ? $res[14] : ($res[5] != '' ? $res[5] : $game[0]['fsw_ball_away'])),//大小球 小的赔率
                ];

                $data['ypOdds']['fsw_exp']  = changeExp( $data['ypOdds']['fsw_exp']);
                $data['ypOdds']['fsw_ball'] = changeExp( $data['ypOdds']['fsw_ball']);
                $data['ypOdds']['valid']    = count(array_filter($data['ypOdds'])) <= count($data['ypOdds']) - 2 ? '0' : '1';
                break;

            case 2:
                $game = M('GameFbinfo g')->field($fields2)->join('LEFT JOIN qc_fb_betodds bet ON bet.game_id = g.game_id')->where(['g.game_id' => $gameId])->select()[0];
                $has = $game && $game['gtime'] >= $blockTime['beginTime'] && $game['gtime'] <= $blockTime['endTime'] ? 1 : 0;

                $data['jcOdds'] = [
                    'home_odds'     => $game['home_odds'],      //不让球主胜赔率
                    'draw_odds'     => $game['draw_odds'],      //不让球平赔率
                    'away_odds'     => $game['away_odds'],      //不让球客胜赔率
                    'let_exp'       => $game['let_exp'],        //让球
                    'home_letodds'  => $game['home_letodds'],   //让球主胜赔率
                    'draw_letodds'  => $game['draw_letodds'],   //让球平赔率
                    'away_letodds'  => $game['away_letodds'],   //让球客胜赔率
                ];

                $data['jcOdds']['valid']    = count(array_filter($data['jcOdds'])) <= count($data['jcOdds']) - 2 ? '0' : '1';
                break;

            default:
                $this->ajaxReturn(101);
                break;
        }

        //play_type  玩法  让分：1，大小：-1；不让球2，让球-2
        //chose_side 亚盘：选择 主队/大：1，客队/小：-1；竞彩：胜1，平0，负-1
        $gamble = (array)M('Gamble')->field(['user_id', 'play_type', 'chose_side'])->where(['game_id' => $gameId])->select();

        $rfTotalNum     = $rfHomeNum    = $rfAwayNum   = 0;
        $dxTotalNum     = $dxBigNum     = $dxSmallNum  = 0;
        $rqTotalNum     = $rqHomeNum    = $rqAwayNum   = $rqDrawNum     = 0;
        $brqTotalNum    = $brqHomeNum   = $brqAwayNum  = $brqDrawNum    = 0;

        foreach ($gamble as $v)
        {
            switch($v['play_type']){
                case 1://让分
                    $rfTotalNum ++;
                    if($v['chose_side'] == 1)   $rfHomeNum ++;
                    if($v['chose_side'] == -1)  $rfAwayNum ++;
                    break;

                case -1://大小
                    $dxTotalNum ++;
                    if($v['chose_side'] == 1)   $dxBigNum ++;
                    if($v['chose_side'] == -1)  $dxSmallNum ++;
                    break;

                case -2://让球
                    $rqTotalNum ++;
                    if($v['chose_side'] == 1)   $rqHomeNum ++;
                    if($v['chose_side'] == -1)  $rqAwayNum ++;
                    if($v['chose_side'] == 0)   $rqDrawNum ++;
                    break;

                case 2://不让球
                    $brqTotalNum ++;
                    if($v['chose_side'] == 1)   $brqHomeNum ++;
                    if($v['chose_side'] == -1)  $brqAwayNum ++;
                    if($v['chose_side'] == 0)   $brqDrawNum ++;
                    break;
            }

            //获取用户对该赛事的推荐记录
            if($userToken['userid'] == $v['user_id']){
                $play_type  = $type == 2 ? [2, -2] : [1, -1];
                if(in_array($v['play_type'], $play_type)){
                    $data['userGamble'][]    = ['play_type' => $v['play_type'], 'chose_side' => $v['chose_side']];
                }
            }
        }
    /*
        $data['percent'] = [
            'rfTotalNum'    => (string)$rfTotalNum,
            'rfHomeNum'     => (string)$rfHomeNum,
            'rfAwayNum'     => (string)$rfAwayNum,
            'dxTotalNum'    => (string)$dxTotalNum,
            'dxBigNum'      => (string)$dxBigNum,
            'dxSmallNum'    => (string)$dxSmallNum,
            'rqTotalNum'    => (string)$rqTotalNum,
            'rqHomeNum'     => (string)$rqHomeNum,
            'rqDrawNum'     => (string)$rqDrawNum,
            'rqAwayNum'     => (string)$rqAwayNum,
            'brqTotalNum'   => (string)$brqTotalNum,
            'brqHomeNum'    => (string)$brqHomeNum,
            'brqDrawNum'    => (string)$brqDrawNum,
            'brqAwayNum'    => (string)$brqAwayNum
        ];
    */
        $data['percent'] = [
            'rfTotalNum'    => 0,
            'rfHomeNum'     => 0,
            'rfAwayNum'     => 0,
            'dxTotalNum'    => 0,
            'dxBigNum'      => 0,
            'dxSmallNum'    => 0,
            'rqTotalNum'    => 0,
            'rqHomeNum'     => 0,
            'rqDrawNum'     => 0,
            'rqAwayNum'     => 0,
            'brqTotalNum'   => 0,
            'brqHomeNum'    => 0,
            'brqDrawNum'    => 0,
            'brqAwayNum'    => 0
        ];

        $data['has'] = (string) $has;

        //今日剩余推荐场数，历史的推荐就不返回了
        if(is_array($userToken) && $has == 1){
            $userGamble = D('GambleHall')->gambleLeftTimes($userToken['userid'], $gameType, $type);
            $data['leftTimes'] = "免费推荐,猜错不扣分,猜对送{$userGamble[2]['norm_point']}*赔率积分,剩余{$userGamble[0]}场.";
        }else{
            switch ($gameType) {
                case '1':
                    $sign = $type == 1 ? 'fbConfig' : 'betConfig';
                    break;
                case '2': $sign = 'bkConfig';  break;
            }
            $gameConf = getWebConfig($sign);
            $data['leftTimes'] = "免费推荐,猜错不扣分,猜对送{$gameConf['norm_point']}*赔率积分.";
        }

        $this->ajaxReturn($data);
    }

    /**
     * 赛事推荐统计 (hzl) #20161216优化
     */
    public function gambleCount()
    {
        $pageNum    = 20;
        $gameType   = 1;
        $page       = $this->param['page'] ?: 1;
        $playType   = $this->param['play_type'] ?:1; //1：让分，-1大小，2：竞彩
        $gameId     = $this->param['game_id'];
        $time       = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
        $userids    = $userGamble = $jWhere = [];
        $weekSort   = $lv = $tradeCoin = $tradeCount = $sortTime = [];

        switch($playType){
            case 1:
            case -1:
                $lvField            = 'f.lv lv';
                $wh['result']       = ['in',[1, 0.5, 2, -1, -0.5]];
                $wh['play_type']    = $playType;
                $jWhere['play_type']= ['in', [-1, 1]];
                break;

            case 2:
            case -2:
                $lvField            = 'f.lv_bet lv';
                $wh['result']       = ['in', [1, -1]];
                $wh['play_type']    = ['in', [2, -2]];
                $jWhere['play_type']= ['in', [2, -2]];
                break;
        }

        $fields = ['g.id gamble_id','g.user_id', 'g.play_type','g.chose_side','g.handcp','g.odds', 'g.is_impt',
            'g.result','g.tradeCoin','g.desc', 'g.create_time','f.head face', 'f.nick_name', $lvField];

        //竞猜该场赛事的用户
        $list = M('Gamble')->alias("g")
            ->join("left join qc_front_user f on f.id = g.user_id")
            ->field($fields)
            ->where(['game_id'=>$gameId, 'play_type'=> $playType != 2 ? $playType : ['IN', [2, -2]]])
            ->group('g.user_id')
            ->select();

        if($list){
            foreach($list as $vv){
                $userids[] = $vv['user_id'];
            }

            list($wBegin,$wEnd) = getRankBlockDate($gameType,1);//周
            list($mBegin,$mEnd) = getRankBlockDate($gameType,2);//月
            list($jBegin,$jEnd) = getRankBlockDate($gameType,3);//季

            $wBeginTime = strtotime($wBegin) + $time;
            $wEndTime   = strtotime($wEnd) + 86400 + $time;

            $mBeginTime = strtotime($mBegin) + $time;
            $mEndTime   = strtotime($mEnd) + 86400 + $time;

            $jBeginTime = strtotime($jBegin) + $time;
            $jEndTime   = strtotime($jEnd) + 86400 + $time;

            //每个用户的周竞猜记录（用户太多分批查询）
            $chunkUsers = array_chunk(array_unique($userids), 200);

            foreach($chunkUsers as $cKey => $cVal){
                $wWhere = [
                    'user_id'       => ['in',$cVal],
                    'result'        => ['in', ['1', '0.5', '-1', '-0.5']],
                    'play_type'     => $jWhere['play_type'],
                    'create_time'   => ["between", [$wBeginTime, $wEndTime]],
                ];
                $cRes = M('Gamble')->field('user_id, GROUP_CONCAT(result) as result')
                    ->where($wWhere)
                    ->group('user_id')
                    ->select();
                $userGamble = array_merge($userGamble, $cRes);
            }

            //二维转一维
            $userWeekGamble = array_column($userGamble, 'result', 'user_id');

            foreach ($list as $k1 => $v1)
            {
                //周胜率计算
                $wWin = $wHalf = $wTransport = $wDonate = 0;
                $resultArr = explode(',', $userWeekGamble[$v1['user_id']]);

                foreach($resultArr as $resultV){
                    if($resultV == '1')     $wWin ++;
                    if($resultV == '0.5')   $wHalf ++;
                    if($resultV == '-1')    $wTransport ++;
                    if($resultV == '-0.5')  $wDonate ++;
                }
                $weekPercnet    = (string)getGambleWinrate($wWin, $wHalf, $wTransport, $wDonate);

                //排序数组
                $weekSort[]     = $weekPercnet;
                $lv[]           = $v1['lv'];
                $sortTime[]     = $v1['create_time'];
                $tradeCoin[]    = $v1['tradeCoin'];

                $list[$k1]['weekPercnet'] = $weekPercnet;
                unset($list[$k1]['lv_bet']);
            }

            //排序：等级》周胜率》金币》该场销量》发布时间
            array_multisort($lv, SORT_DESC, $weekSort, SORT_DESC, $tradeCoin, SORT_DESC, $list);
            $pageList = array_slice($list, ($page-1) * $pageNum, $pageNum);

            $userToken  = getUserToken($this->param['userToken']);

            //是否查看过本赛程
            if ($userToken && $userToken != -1){
                $gambleId = (array)M('QuizLog')->where(['game_type' => 1, 'user_id'=>$userToken['userid'],'game_id'=>$this->param['game_id']])->getField('gamble_id',true);
            }

            $jWhere['result']       = ["IN", ['1', '0.5', '-1', '-0.5']];
            $jWhere['create_time']  = ["between", [$jBeginTime, $jEndTime ]];

            foreach ($pageList as $k => $v)
            {
                //用户信息
                $pageList[$k]['face']       = frontUserFace($v['face']);
                $pageList[$k]['is_trade']   = in_array($v['gamble_id'], $gambleId) ? '1' : '0';
                $pageList[$k]['desc']       = (string)$pageList[$k]['desc'];

                //查询月、季胜率(数据库只查季胜率)
                $jWin = $mWin = $jHalf = $mHalf = $jTransport = $mTransport = $jDonate = $mDonate = 0;
                $jWhere['user_id'] = $v['user_id'];

                $seasonGamble = M('gamble')->field(['result','earn_point','create_time'])->where($jWhere)->select();
                foreach($seasonGamble as $key => $val){
                    switch($val['result']){
                        case '1':
                            $jWin ++;
                            if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mWin ++;
                            break;

                        case '0.5':
                            $jHalf ++;
                            if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mHalf ++;
                            break;

                        case '-1':
                            $jTransport ++;
                            if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mTransport++;
                            break;

                        case '-0.5':
                            $jDonate++;
                            if($val['create_time'] >= $mBeginTime && $val['create_time'] <= $mEndTime) $mDonate++;
                            break;
                    }
                }

                $pageList[$k]['monthPercnet']  = (string)getGambleWinrate($mWin, $mHalf, $mTransport, $mDonate);
                $pageList[$k]['seasonPercnet'] = (string)getGambleWinrate($jWin, $jHalf, $jTransport, $jDonate);

                //近十场胜负、胜平负
                $wh['user_id']    = $v['user_id'];
                $pageList[$k]['tenGamble']  = M('gamble')->where($wh)->order("id desc")->limit(10)->getField('result',true);

                unset($pageList[$k]['create_time']);
            }
        }

        $this->ajaxReturn(['gambleList' => $pageList ?: []]);
    }

    //个人中心
    public function user()
    {
        $page        = $this->param['page'] ?: 1;
        $userToken   = getUserToken($this->param['userToken']);
        //id相同则是看自己的主页
        $user_id     = ($userToken['userid'] == $this->param['user_id']) ? $userToken['userid'] : $this->param['user_id'];
        D('Common')->setFrontSeeNum($user_id,'app');
        if($page == 1){
            $userInfo                = M('FrontUser')->field(['nick_name','lv','lv_bet','descript','head face'])->where(['id'=>$user_id])->find();
            $userInfo['fansNum']     = M('FollowUser')->where(['follow_id'=>$user_id])->count();
            $userInfo['face']        = frontUserFace($userInfo['face']);
            //亚盘
            $gamble                  = D('GambleHall')->getWinning($user_id, $gameType=1, 0, 1, 0); //推荐统计信息
            $gamble['weekPercnet']   = (string)D('GambleHall')->CountWinrate($user_id, 1, 1);
            $gamble['monthPercnet']  = (string)D('GambleHall')->CountWinrate($user_id, 1, 2);
            $gamble['seasonPercnet'] = (string)D('GambleHall')->CountWinrate($user_id, 1, 3);
            $gamble['tenGambleRate'] = $gamble['tenGambleRate']; //近十场的胜率
            $gamble['lv']            = $userInfo['lv'];

            //竞彩
            $betting                  = D('GambleHall')->getWinning($user_id, $gameType=1, 0, 2, 0); //推荐统计信息
            $betting['weekPercnet']   = (string)D('GambleHall')->CountWinrate($user_id, 1, 1, false, false, 0, 2);
            $betting['monthPercnet']  = (string)D('GambleHall')->CountWinrate($user_id, 1, 2, false, false, 0, 2);
            $betting['seasonPercnet'] = (string)D('GambleHall')->CountWinrate($user_id, 1, 3, false, false, 0, 2);
            $betting['tenGambleRate'] = $betting['tenGambleRate']; //近十场的胜率
            $betting['lv']            = $userInfo['lv_bet'];

            unset($userInfo['lv'], $userInfo['lv_bet']);

            if ($userToken)
            {
                $isFollow = M('FollowUser')->where(['user_id'=>$userToken['userid'],'follow_id'=>$this->param['user_id']])->find(); //是否已经关注
                $userInfo['isFollow'] = $isFollow ? '1' : '0';
                $userInfo['sub'] = $isFollow ? $isFollow['sub'] : '0';
            }
            else
            {
                $userInfo['sub'] = '0';
                $userInfo['isFollow'] = '0';
            }
        }

        $gamble_id   = isset($this->param['gamble_id']) ? (int)$this->param['gamble_id'] : 0;
        $playType    = $this->param['play_type'] ?: 0;//玩法类型，1：让球；2：大小；
        $gamebleType = $this->param['gameble_type'] ?: 0;//推荐类型(1:亚盘;2:竞彩 默认为亚盘1)

        $gambleList = D('GambleHall')->getGambleList($this->param['user_id'], $playType, $page , $gamble_id, $gamebleType);

        $where['user_id'] = $user_id;
        if($gamebleType)
            $where['play_type'] = $gamebleType == 1 ? ['IN',[1, -1]] : ['IN',[2, -2]];

        if($playType)
            $where['play_type'] = $playType;

        $total_times = M('Gamble')->where($where)->count();

        foreach ($gambleList as $k => $v)
        {
            if ($userToken) //如已经登陆
            {
                $isTrade = M('QuizLog')->master(true)->where(['game_type' => 1, 'user_id'=>$userToken['userid'],'gamble_id'=>$v['gamble_id']])->getField('id');
                $gambleList[$k]['is_trade'] = $isTrade ? 1 : 0;
            }
            else
            {
                $gambleList[$k]['is_trade'] = 0;
            }
        }

        if($page > 1){
            $returnArr = ['gambleList'=>$gambleList];
        }else{
            $returnArr = ['userInfo'=>$userInfo, 'gamble' => $gamble, 'betting' => $betting, 'gambleList'=>$gambleList, 'total_times' => $total_times];
        }

        $this->ajaxReturn($returnArr);
    }

    /**
     * 用户推荐记录
     */
    public function userGamble()
    {
        $gamble_id  = $this->param['gamble_id'];
        $playType   = $this->param['play_type']?:0;
        $page       = $this->param['page'] ?: 1;
        $userToken  = getUserToken($this->param['userToken']);
        $gambleType = $playType == 2 ? 2 : 0;

        $gambleList = D('GambleHall')->getGambleList($this->param['user_id'], $playType, $page, $gamble_id, $gambleType);

        foreach ($gambleList as $k => $v)
        {
            if ($userToken) //如已经登陆
            {
                $isTrade = M('QuizLog')->where(['game_type' => 1, 'user_id'=>$userToken['userid'],'gamble_id'=>$v['gamble_id']])->getField('id');
                $gambleList[$k]['is_trade'] = $isTrade ? 1 : 0;
            }
            else
            {
                $gambleList[$k]['is_trade'] = 0;
            }
        }

        $this->ajaxReturn(['gambleList'=>$gambleList]);
    }

    /**
     * 排行榜：足球、篮球亚盘胜率榜
     */
    public function rank()
    {
        $myRank     = '';
        $pageNum    = 20;
        $page       = $this->param['page'] ?: 1;
        $gameType   = $this->param['gameType'] ?: 1;
        $dateType   = $this->param['dateType'] ?: 4;
        $blockTime  = getBlockTime($gameType, true);
        $userToken  = getUserToken($this->param['userToken']);
        $myRankKey  = MODULE_NAME . 'ranking_myRank:' . $gameType . $dateType . $userToken['userid'];
        $expire     = 5*60;

        $gambleTbs  = $gameType == 2 ? '__GAMBLEBK__' : '__GAMBLE__';

        if ($dateType == 4) {//日榜
            $listDate = date('Ymd', strtotime("-1 day"));
            $exist = M('RedList')->where(['list_date' => $listDate, 'game_type' => $gameType])->field('id')->find();

            if (!$exist)
                $listDate = date('Ymd', strtotime("-2 day"));

            $cacheKey = MODULE_NAME . '_ranking_game_rank:' . $listDate . $this->param['todayGamble'] . $page . $pageNum;

            $field = ['r.user_id', 'r.ranking', 'r.gameCount', 'r.win', 'r.half', 'r.`level`', 'r.transport', 'r.donate', 'r.winrate', 'r.pointCount'];
            $where = ['r.list_date' => $listDate, 'r.game_type' => $gameType];

            if (!$rank = S($cacheKey)) {//读取缓存

                if ($this->param['todayGamble']) {
                    $where['g.play_type']   = ['IN', [1, -1]];
                    $rank = (array)M('RedList r')
                        ->cache($cacheKey, $expire, 'Redis')
                        ->field($field)
                        ->join('left join '. $gambleTbs .' g on g.user_id = r.user_id')
                        ->where(array_merge($where, ['g.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]]))
                        ->group('r.user_id')
                        ->order('r.ranking')
                        ->page($page . ',' . $pageNum)
                        ->select();

                } else {
                    $rank = M('RedList r')
                        ->cache($cacheKey, $expire, 'Redis')
                        ->field($field)
                        ->where($where)
                        ->page($page . ',' . $pageNum)
                        ->select();
                }
            }

            //我的排名
            if ($userToken['userid']) {
                if (!$myRank = S($myRankKey)) {
                    $where['r.user_id'] = $userToken['userid'];
                    $rankData = (array)M('RedList r')->field($field)->where($where)->select();

                    if ($rankData) {
                        $myRank = $rankData[0];
                        $myRank['ranking'] .= '名';
                    } else {
                        $myRank['ranking'] = '未上榜';
                    }

                    //我的排名数据重构
                    $myRank = [
                        'ranking'   => $myRank['ranking'] ? (string)$myRank['ranking'] : '0',
                        'win'       => (string)($myRank['win'] + $myRank['half']),
                        'level'     => $myRank['level']?(string)$myRank['level'] :'0',
                        'pointCount'=> $myRank['pointCount']?(string)$myRank['pointCount'] :'0',
                        'transport' => (string)($myRank['donate'] + $myRank['transport']),
                        'winrate'   => $myRank['winrate']?(string)$myRank['winrate'] :'0',
                        'curr_victs'=> D('GambleHall')->getWinning($userToken['userid'], 1, 0, 1, 100)['curr_victs'] ?: '0'
                    ];

                    if ($myRank)
                        S($myRankKey, $myRank, $expire - 3);
                }
            }

        } else {//周、月、季
            //我的排名
            if ($userToken['userid']) {
                if (!$myRank = S($myRankKey)) {
                    $rankData = (array)D('GambleHall')->getRankingData($gameType, $this->param['dateType'], $userToken['userid']);

                    if ($rankData) {
                        $myRank = $rankData[0];
                        $myRank['ranking'] .= '名';
                    } else {
                        $myRank = D('GambleHall')->CountWinrate($userToken['userid'], $gameType, $dateType, true);
                        $myRank['ranking'] = '未上榜';
                    }

                    //我的排名数据重构
                    $myRank = [
                        'ranking'   => $myRank['ranking']?(string)$myRank['ranking']:'0',
                        'win'       => (string)($myRank['win'] + $myRank['half']),
                        'level'     => $myRank['level']?(string)$myRank['level']:'0',
                        'pointCount'=> $myRank['pointCount']?(string)$myRank['pointCount']:'0',
                        'transport' => (string)($myRank['donate'] + $myRank['transport']),
                        'winrate'   => $myRank['winrate']?(string)$myRank['winrate']:'0',
                        'curr_victs'=> D('GambleHall')->getWinning($userToken['userid'], 1, 0, 1, 100)['curr_victs'] ?: '0'
                    ];

                    if ($myRank)
                        S($myRankKey, $myRank, $expire);
                }
            }

            //周、月、季 排行数据
            $rank = (array)D('GambleHall')->getRankingData($gameType, $dateType, null, false, $page, $pageNum, $this->param['todayGamble']);
        }

        //（可以优化加到缓存）
        foreach ($rank as $k => $v) {

            //胜、平、负、连胜
            $rank[$k]['win']            = (string)($rank[$k]['win'] + $rank[$k]['half']);
            $rank[$k]['transport']      = (string)($rank[$k]['transport'] + $rank[$k]['donate']);
            $rank[$k]['curr_victs']     = D('GambleHall')->getWinning($v['user_id'], 1, 0, 1, 100)['curr_victs'] ?: '0';

            //头像、昵称
            $userInfo                   = M('FrontUser')->where(['id' => $v['user_id']])->field('nick_name,head')->find();
            $rank[$k]['nick_name']      = $userInfo['nick_name'];
            $rank[$k]['face']           = frontUserFace($userInfo['head']);
            $today_gamle_where          = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [1,-1]]];
            $rank[$k]['today_gamble']   = M('Gamble')->where($today_gamle_where)->getField('id') ? '1' : '0';

            //是否已经关注
            if ($userToken['userid'])
                $rank[$k]['isFollow']   = M('FollowUser')->where(['user_id' => $userToken['userid'], 'follow_id' => $v['user_id']])->find() ? '1' : '0';

            unset($rank[$k]['head']);
            unset($rank[$k]['donate']);
            unset($rank[$k]['half']);
            unset($rank[$k]['lv']);
            unset($rank[$k]['lv_bk']);
        }

        $this->ajaxReturn(['myRank' => $myRank, 'rankList' => $rank ?: []]);
    }

    /**
     * （排行榜）足球、篮球亚盘盈利榜 hzl
     */
    public function profitRank()
    {
        $pageNum    = 20;
        $expire     = 5*60;
        $page       = $this->param['page'] ?: 1;
        $dateType   = $this->param['dateType'] ?: 4;
        $gameType   = $this->param['gameType'] ?: 1;
        $tdGamble   = $this->param['todayGamble'] ?: 0;
        $userToken  = getUserToken($this->param['userToken']);
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $cacheKey   = MODULE_NAME . '_ranking_profit_rank_' . $tdGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . 'api_ranking_my_profit_rank_' . $dateType . $userToken['userid'];

        switch (intval($dateType)) {
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
                $this->ajaxReturn(['myRank' => '' , 'rankList' => '']);
        }

        //获取排行
        if (!$rankLists = S($cacheKey)) {
            $count = M('earnPointList r')->where($where)->count();

            $field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.gameCount', 'r.win', 'r.half', 'r.level', 'r.transport', 'r.donate', 'r.winrate', 'r.pointCount'];

            if (!$count)
                $where = $topWhere;

            if ($tdGamble) {
                //筛选今日推荐
                $where['g.play_type']   = ['IN', [1, -1]];
                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
                $rankLists = M('earnPointList r')
                    ->field($field)
                    ->join('left join __GAMBLE__ g on g.user_id = r.user_id')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->where($where)
                    ->group('r.user_id')
                    ->order('r.ranking ASC')
                    ->page($page . ',' . $pageNum)
                    ->select();

            } else {
                $rankLists = M('earnPointList r')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->field($field)
                    ->where($where)
                    ->order("r.ranking ASC")
                    ->page($page . ',' . $pageNum)
                    ->select();
            }

            //排行用户信息
            foreach ($rankLists as $k => $v) {
                //胜、平、负、连胜
                $rankLists[$k]['win']           = (string)($rankLists[$k]['win'] + $rankLists[$k]['half']);
                $rankLists[$k]['transport']     = (string)($rankLists[$k]['transport'] + $rankLists[$k]['donate']);
                $rankLists[$k]['curr_victs']    = D('GambleHall')->getWinning($v['user_id'], 1, 0, 1, 100)['curr_victs'] ?: '0';

                //头像昵称
                $rankLists[$k]['nick_name']     = $v['nick_name'];
                $rankLists[$k]['face']          = frontUserFace($v['face']);

                unset($rankLists[$k]['donate']);
                unset($rankLists[$k]['half']);
            }

            if($rankLists)
                S($cacheKey, $rankLists, $expire);
        }

        //我的排名
        if (is_array($userToken)) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('earnPointList r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userToken['userid'];
                $fields = ['r.user_id', 'r.win', 'r.half', 'r.transport', 'r.donate', 'r.pointCount', 'r.winrate','r.ranking'];
                $rankData = M('earnPointList r')->field($fields)->where($where)->select();
                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank = D('GambleHall')->CountWinrate($userToken['userid'], $gameType, $dateType, true);
                    $myRank['ranking'] = '未上榜';
                }

                //构造我的排名数据
                $myRank = [
                    'ranking'   => $myRank['ranking'] ? (string)$myRank['ranking'] : '0',
                    'win'       => (string)($myRank['win'] + $myRank['half']),
                    'level'     => $myRank['level'] ? (string)$myRank['level'] : '0',
                    'pointCount'=> $myRank['pointCount'] ? (string)$myRank['pointCount'] : '0',
                    'transport' => (string)($myRank['donate'] + $myRank['transport']),
                    'winrate'   => $myRank['winrate'] ? (string)$myRank['winrate'] : '0',
                    'curr_victs'=> D('GambleHall')->getWinning($userToken['userid'], 1, 0, 1, 100)['curr_victs'] ?: '0'
                ];

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        //排行用户信息（提出来不加入缓存）
        foreach ($rankLists as $k => $v) {
            $today_gamle_where              = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [1,-1]]];
            $rankLists[$k]['today_gamble']  = M('Gamble')->where($today_gamle_where)->getField('id') ? '1' : '0';

            if ($userToken['userid'])
                $rankLists[$k]['isFollow']  = M('FollowUser')->where(['user_id' => $userToken['userid'], 'follow_id' => $v['user_id']])->find() ? '1' : '0';
        }

        $this->ajaxReturn(['myRank' => $myRank ?: '', 'rankList' => $rankLists ?: []]);
    }

    /**
     * 排行榜：足球竞彩胜率榜
     */
    public function betRank()
    {
        $pageNum    = 20;
        $expire     = 5*60;
        $page       = $this->param['page'] ?: 1;
        $dateType   = $this->param['dateType'] ?: 4;
        $tdGamble   = $this->param['todayGamble'] ?: 0;
        $userToken  = getUserToken($this->param['userToken']);
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $cacheKey   = MODULE_NAME . '_ranking_bet_rank_' . $tdGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . '_ranking_my_bet_rank_' . $dateType . $userToken['userid'];

        switch (intval($dateType)) {
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
                $this->ajaxReturn(['myRank' => '', 'rankList' => '']);
        }

        if (!$rankLists = S($cacheKey)) {
            $count = M('rankBetting r')->where($where)->count();

            $field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.win', 'r.transport', 'r.winrate','r.gameCount', 'r.pointCount'];

            if (!$count)
                $where = $topWhere;

            if ($tdGamble) {
                //筛选今日推荐
                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
                $where['g.play_type']   = ['IN', [2, -2]];
                $rankLists = M('rankBetting r')
                    ->field($field)
                    ->join('left join __GAMBLE__ g on g.user_id = r.user_id')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->where($where)
                    ->group('r.user_id')
                    ->order('r.ranking ASC')
                    ->page($page . ',' . $pageNum)
                    ->select();
            } else {
                $rankLists = M('rankBetting r')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->field($field)
                    ->where($where)
                    ->order("r.ranking ASC")
                    ->page($page . ',' . $pageNum)
                    ->select();
            }

            //排行榜用户信息
            foreach ($rankLists as $k => $v) {
                //胜、负、连胜
                $rankLists[$k]['win']           = (string)($rankLists[$k]['win'] + $rankLists[$k]['half']);
                $rankLists[$k]['transport']     = (string)($rankLists[$k]['transport'] + $rankLists[$k]['donate']);
                $rankLists[$k]['curr_victs']    = D('GambleHall')->getWinning($v['user_id'], 1, 0, 2, 100)['curr_victs'] ?: '0';

                //头像、昵称
                $rankLists[$k]['face']          = frontUserFace($v['face']);
                $rankLists[$k]['nick_name']     = $v['nick_name'] ?: '';
            }

            if ($rankLists)
                S($cacheKey, $rankLists, $expire);
        }

        //我的排名
        if (is_array($userToken)) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('rankBetting r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userToken['userid'];
                $rankData = M('rankBetting r')->field(['r.user_id', 'r.pointCount', 'r.ranking', 'r.win', 'r.transport', 'r.winrate'])->where($where)->select();

                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank = D('GambleHall')->CountWinrate($userToken['userid'], 1, $dateType, true, false, 0, 2);
                    $myRank['ranking']      = '未上榜';
                }

                //构造排名数据
                $myRank = [
                    'ranking'   => $myRank['ranking'] ? (string)$myRank['ranking'] : '0',
                    'pointCount'=> $myRank['pointCount'] ? (string)$myRank['pointCount'] : '0',
                    'win'       => (string)($myRank['win'] + $myRank['half']),
                    'transport' => (string)($myRank['donate'] + $myRank['transport']),
                    'winrate'   => $myRank['winrate'] ? (string)$myRank['winrate'] : '0',
                    'curr_victs'=> D('GambleHall')->getWinning($userToken['userid'], 1, 0, 2, 100)['curr_victs'] ?: '0'
                ];

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        //排行榜用户信息（不加入缓存）
        foreach ($rankLists as $k => $v) {
            $today_gamle_where              = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [2,-2]]];
            $rankLists[$k]['today_gamble']  = M('Gamble')->where($today_gamle_where)->getField('id') ? '1' : '0';

            $isFollow = 0;
            if (is_array($userToken))
                $isFollow = M('FollowUser')->where(['user_id' => $userToken['userid'], 'follow_id' => $v['user_id']])->find();

            $rankLists[$k]['isFollow'] = $isFollow ? '1' : '0';
        }

        $this->ajaxReturn(['myRank' => $myRank ?: "", 'rankList' => $rankLists ?: []]);
    }

    /**
     * 排行榜：足球竞彩盈利榜榜
     */
    public function betProfitRank()
    {
        $pageNum    = 20;
        $expire     = 5*60;
        $page       = $this->param['page'] ?: 1;
        $dateType   = $this->param['dateType'] ?: 4;
        $tdGamble   = $this->param['todayGamble'] ?: 0;
        $userToken  = getUserToken($this->param['userToken']);
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $cacheKey   = MODULE_NAME . '_ranking_betProfit_rank_' . $tdGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . 'api_ranking_my_betProfit_rank_' . $dateType . $userToken['userid'];

        switch (intval($dateType)) {
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
                $this->ajaxReturn(['myRank' => '', 'rankList' => []]);
        }

        if (!$rankLists = S($cacheKey)) {
            $count = M('rankBetprofit r')->where($where)->count();

            $field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.gameCount', 'r.ranking', 'r.win', 'r.transport', 'r.winrate', 'r.pointCount'];

            if (!$count)
                $where = $topWhere;

            if ($tdGamble) {
                //筛选今日推荐
                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
                $where['g.play_type']   = ['IN', [2, -2]];
                $rankLists = M('rankBetprofit r')
                    ->field($field)
                    ->join('left join __GAMBLE__ g on g.user_id = r.user_id')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->where($where)
                    ->group('r.user_id')
                    ->order('r.ranking ASC')
                    ->page($page . ',' . $pageNum)
                    ->select();

            } else {
                $rankLists = M('rankBetprofit r')
                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
                    ->field($field)
                    ->where($where)
                    ->order("r.ranking ASC")
                    ->page($page . ',' . $pageNum)
                    ->select();
            }

            //排行榜用户信息
            foreach ($rankLists as $k => $v) {
                //胜、负、连胜
                $rankLists[$k]['win']           = (string)($rankLists[$k]['win'] + $rankLists[$k]['half']);
                $rankLists[$k]['transport']     = (string)($rankLists[$k]['transport'] + $rankLists[$k]['donate']);
                $rankLists[$k]['curr_victs']    = D('GambleHall')->getWinning($v['user_id'], 1, 0, 2, 100)['curr_victs'] ?: '0';

                //头像、昵称
                $rankLists[$k]['face']          = frontUserFace($v['face']);
                $rankLists[$k]['nick_name']     = $v['nick_name'] ?: '';
            }

            if ($rankLists)
                S($cacheKey, $rankLists, $expire);
        }

        //我的排名
        if (is_array($userToken)) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('rankBetprofit r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userToken['userid'];
                $rankData = M('rankBetprofit r')->field(['r.user_id', 'r.pointCount', 'r.ranking', 'r.win', 'r.transport', 'r.winrate'])->where($where)->select();

                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank = D('GambleHall')->CountWinrate($userToken['userid'], 1, $dateType, true, false, 0, 2);
                    $myRank['ranking']      = '未上榜';
                }

                //构造排名数据
                $myRank = [
                    'ranking'   => $myRank['ranking'] ? (string)$myRank['ranking'] : '0',
                    'pointCount'=> $myRank['pointCount'] ? (string)$myRank['pointCount'] : '0',
                    'win'       => (string)($myRank['win'] + $myRank['half']),
                    'transport' => (string)($myRank['donate'] + $myRank['transport']),
                    'winrate'   => $myRank['winrate'] ? (string)$myRank['winrate'] : '0',
                    'curr_victs'=> D('GambleHall')->getWinning($userToken['userid'], 1, 0, 2, 100)['curr_victs'] ?: '0'
                ];

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        //是否关注、今日是否有推荐（不加入缓存）
        foreach ($rankLists as $k => $v) {
            $today_gamle_where              = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [2,-2]]];
            $rankLists[$k]['today_gamble']  = M('Gamble')->where($today_gamle_where)->getField('id') ? '1' : '0';

            $isFollow = 0;
            if (is_array($userToken))
                $isFollow = M('FollowUser')->where(['user_id' => $userToken['userid'], 'follow_id' => $v['user_id']])->find();

            $rankLists[$k]['isFollow'] = $isFollow ? '1' : '0';
        }

        $this->ajaxReturn(['myRank' => $myRank ?: "", 'rankList' => $rankLists ?: []]);
    }

    //兑换中心
    public function exchange()
    {
        //banner图
        $class_id = M('recommendClass')->where(['sign'=>'exchange'])->getField('id');
        $Recommend = (array)M("Recommend")
                    ->field(['id','title','type','url','img'])
                    ->where(['class_id'=>$class_id,'status'=>1])
                    ->order("sort desc")
                    ->select();

        foreach( $Recommend as $k => $v )
        {
            $Recommend[$k]['img']  = Think\Tool\Tool::imagesReplace( $v['img'] ) ?: '';
            unset($Recommend[$k]['id']);
        }

        //礼品兑换列表
        $prizeList = (array)M('Prize')->field(['name','coin','point','url','img','valid'])->where(['status'=>1])->order('sort')->select();

        foreach ($prizeList as $k => $v)
        {
            $prizeList[$k]['img']  = Think\Tool\Tool::imagesReplace( $v['img'] ) ?: '';
        }

        if (iosCheck()) //ios审核不显示礼品兑换
        {
            $prizeList = [];
        }

        $this->ajaxReturn(['bannerList'=>$Recommend,'prizeList'=>$prizeList]);
    }

    /**
     * 最新发布信息接口
     */
    public function hotPush(){
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页,只返回推荐数据
        $blockTime = getBlockTime(1, $gamble = true);//获取推荐分割日期的区间时间

        if($page == 1){
            //图片地址
            $img = M('recommendClass')->alias("rc")->join("left join qc_recommend re on rc.id = re.class_id")->where(['rc.sign' => 'appHotPush'])->field('re.img')->find();
            $img  = @Think\Tool\Tool::imagesReplace($img['img']);

            //先查缓存
            $dakaCahe = S('dakaCahe'.MODULE_NAME);
            if(empty($dakaCahe)) {
                //热门大咖,热门大咖，取值周榜连胜50名中取7个
                $paramType = 1;//取周榜
                $rankDate = getRankDate($paramType);//获取上周的日期

                $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
                $count = M()->query($sql1);
                if (!$count[0]['num']){
                    $rankDate = getTopRankDate($paramType);//获取上上周的数据
                }

                $sql = " SELECT r.user_id, u.nick_name, u.head
                    FROM qc_ranking_list AS r
                    LEFT JOIN qc_front_user AS u ON r.user_id = u.id
                    WHERE r.dateType = {$paramType}
                    AND r.gameType = 1
                    AND r.begin_date >= {$rankDate[0]} AND r.end_date <= {$rankDate[1]} AND r.id > 0
                    ORDER BY  r.ranking ASC LIMIT 50 ";

                $arr = M()->query($sql);
                $currArr = array();//排序数组

                foreach ($arr as $k => $v) {
                    $arr[$k]['face'] = frontUserFace($v['head']);
                    $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType = 1); //连胜记录
                    $arr[$k]['curr_victs_ftball'] = $currArr[] = $winnig['curr_victs'];//当前连胜场数

                    unset($arr[$k]['head']);
                }
                array_multisort($currArr, SORT_DESC, $arr);

                $data1 =  array_slice($arr, 0, 7);//取前七

                //缓存3个小时
                S('dakaCahe'.MODULE_NAME, json_encode($data1), 60 * 5);
                $daka = $data1;
                unset($data1);
            }else{//返回缓存
                $daka = $dakaCahe;
            }
        }else{
            $daka = array();
            $img = '';
        }

        //最新推荐
        $pageNum = 10;
        $pageSize = ($page-1)*$pageNum;
        $playType = $this->param['playType'] ? (int)$this->param['playType'] : 0;//0:全部;1:让分;-1:大小
        $orderType = $this->param['orderType'] ? (int)$this->param['orderType'] : 0;//0:默认按时间倒序;1:价格低到高;2:价格高到低;3:销量高到低

        $where = " g.create_time between {$blockTime['beginTime']} AND {$blockTime['endTime']} AND g.result = 0 AND g.id > 0 ";//推荐赛程期间内，且未出结果的
        if($playType){
            $where .=  " AND g.play_type = {$playType} " ;
        }

        $order = ' g.create_time DESC ';
        if($orderType == 1 || $orderType == 2){//价格
            $d = ($orderType == 1) ? 'ASC' : 'DESC';
            $order =  " g.tradeCoin {$d}, g.create_time DESC ";
        }else if($orderType == 3){//销量,只出现金币不为0的，先按销量排，再按推荐时间排
            $where .= ' AND g.tradeCoin > 0 ';
            $order = ' g.quiz_number DESC, g.create_time DESC ';
        }

        $userToken = getUserToken($this->param['userToken']);
        $sql4 = " SELECT g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.away_team_name,
                  g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, g.create_time, u.nick_name, u.head, u.lv, qu.union_color
                  FROM qc_gamble AS g
                  LEFT JOIN qc_front_user AS u ON g.user_id = u.id
                  LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id
                  WHERE {$where}
                  ORDER BY  {$order} limit {$pageSize}, {$pageNum} ";
        $jingcaiArr = (array)M()->query($sql4);

        if(!empty($jingcaiArr)){
            foreach($jingcaiArr as $k2 => $v2){
                $jingcaiArr[$k2]['face'] = frontUserFace($v2['head']);
                $jingcaiArr[$k2]['union_name']     = explode(',', $v2['union_name']);
                $jingcaiArr[$k2]['home_team_name'] = explode(',', $v2['home_team_name']);
                $jingcaiArr[$k2]['away_team_name'] = explode(',', $v2['away_team_name']);
                $jingcaiArr[$k2]['desc'] = (string)$v2['desc'];

                if ($userToken) {//如已经登陆
                    //判断当前用户是否有购买当前信息
                    $jingcaiArr[$k2]['is_trade'] = D('Common')->getTradeLog($v2['gamble_id'], $userToken['userid']);//是否已查看购买过
                }else{
                    //无登录则全部没有购买
                    $jingcaiArr[$k2]['is_trade'] = 0;
                }
                unset($jingcaiArr[$k2]['head']);
            }
        }


        if($page == 1){
            $this->ajaxReturn(['daka' => $daka, 'dakaImg' => $img,  'jingcai' => $jingcaiArr]);
        }else{
            $this->ajaxReturn(['jingcai' => $jingcaiArr]);
        }
    }


    /**
     * 热门大咖--更多信息
     */
    public function bigShotInfo(){
        $page = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum = 10;
        $pageSize = ($page-1)*$pageNum;
        $playType = $this->param['playType'] ? (int)$this->param['playType'] : 0;//0:全部;1:让分;-1:大小
        $recordType = $this->param['recordType'] ? (int)$this->param['recordType'] : 1;//1:默认周胜率;2:月胜率;3:人气;4:等级；5：当前连胜；6：命中

        if($page >= 5){//不能大于等于5，只取前50名
            $this->ajaxReturn(['bigShotInfo' => array()]);
        }
        if(!in_array($playType, array(-1,0,1)) || !in_array($recordType, array(1,2,3,4,5,6))){
            $this->ajaxReturn(101);
        }

        if(in_array($recordType, array(1,2,4,5,6))){//周胜率，月胜率
            $paramType = in_array($recordType, array(1,4,5,6)) ? 1 : 2;//4,5,6取周榜
            $rankDate = getRankDate($paramType);//获取上周的日期

            $sql1 = " SELECT count(*) AS num from qc_ranking_list WHERE dateType = {$paramType} AND gameType = 1 AND begin_date >= {$rankDate[0]} AND end_date <= {$rankDate[1]} ";
            $count = M()->query($sql1);
            if (!$count[0]['num']){
                $rankDate = getTopRankDate($paramType);//获取上上周的数据
            }

            $sql = " SELECT r.user_id, r.winrate, u.nick_name,u.head,u.lv,r.ranking
                    FROM qc_ranking_list AS r
                    LEFT JOIN qc_front_user AS u ON r.user_id = u.id
                    WHERE r.dateType = {$paramType}
                    AND r.gameType = 1
                    AND r.begin_date >= {$rankDate[0]} AND r.end_date <= {$rankDate[1]} AND r.id > 0
                    ORDER BY  r.ranking ASC LIMIT 50 ";
        }else if($recordType == 3){//人气
            $listDate = date('Ymd', strtotime("-1 day"));
            $exist = M('RedList')->where(['list_date' => $listDate, 'game_type' => 1])->field('id')->find();

            if (!$exist) {//昨天不存在就找前天
                $listDate = date('Ymd', strtotime("-2 day"));
            }
            $sql = " SELECT l.user_id, l.winrate, u.nick_name, u.head, u.lv,l.ranking
                    FROM qc_red_list as l
                    LEFT JOIN qc_front_user as u on l.user_id = u.id
                    WHERE l.list_date = {$listDate} AND l.game_type = 1 AND l.id > 0
                    ORDER BY l.ranking ASC LIMIT 50 ";
        }
        $arr = M()->query($sql);
        $blockTime = getBlockTime(1, $gamble = true);//获取赛程分割日期的区间时间
        $rankSort = $lvArr = $currArr = $tenGambleRateArr = $createTimeSort = $weekSort = $monthSort = $redList = $playTypeArr = $backupArr = $backupTimeArr = array();//排序数组

        foreach ($arr as $k => $v) {
            $arr[$k]['face'] = frontUserFace($v['head']);
            $arr[$k]['tenGamble'] = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $arr[$k]['tenGambleRate'] = $tenGambleRateArr[] = countTenGambleRate($arr[$k]['tenGamble']);//近十场的胜率
            $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType=1); //连胜记录
            $arr[$k]['curr_victs_ftball'] = $currArr[] = $winnig['curr_victs'];//连胜场数
            $arr[$k]['win']  = $winnig['win'];//胜数
            $rankSort[] = $redList[] = $v['ranking'];//排名
            $lvArr[] = $v['lv'];//等级

            if(in_array($recordType, array(1, 4, 5, 6)) && $playType == 0){//周胜率
                $arr[$k]['weekPercnet'] = $weekSort[] = $arr[$k]['winrate'];//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2);//月胜率
            }else if($recordType == 2 && $playType == 0){//月胜率
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = $arr[$k]['winrate'];//月胜率
            }else if(in_array($recordType, array(1, 2, 4, 5, 6)) && in_array($playType, array(-1, 1))){
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1, false, false, $playType);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2, false, false, $playType);//月胜率
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType=1, $playType); //连胜记录
                $arr[$k]['win']  = $winnig['win'];//胜数
                $arr[$k]['winrate'] = (in_array($recordType, array(1, 4, 5, 6))) ? $arr[$k]['weekPercnet'] : $arr[$k]['monthPercnet'];
            }else if($recordType == 3 && in_array($playType, array(-1, 1))){//人气
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1, false, false, $playType);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2, false, false, $playType);//月胜率
                $winnig = D('GambleHall')->getWinning($v['user_id'], $gameType=1, $playType); //连胜记录
                $arr[$k]['win']  = $winnig['win'];//胜数
                //计算昨天的胜率
                $where['user_id']   = $v['user_id'];
                $where['create_time'] = ['between',[$blockTime['beginTime']-86400, $blockTime['endTime']-86400]];
                $where['result'] = ['NEQ', 0];
                $where['play_type'] = $playType;
                $gameArray = M('gamble')->where($where)->select();
                //计算昨日胜率
                $win = $half = $transport = $donate = 0;
                foreach ($gameArray as $vv)
                {
                    if($vv['result'] == '1')     $win++;
                    if($vv['result'] == '0.5')   $half++;
                    if($vv['result'] == '-1')    $transport++;
                    if($vv['result'] == '-0.5')  $donate++;
                }

                $winTotal    = $win + $half*0.5;
                $gambleTotal = $winTotal + $transport + $donate*0.5;
                $arr[$k]['winrate'] = $redList[] = $gambleTotal ? round(($winTotal/$gambleTotal)*100) : 0;
            }else if($recordType == 3 && $playType == 0) {
                $arr[$k]['weekPercnet'] = $weekSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周胜率
                $arr[$k]['monthPercnet'] = $monthSort[] = D('GambleHall')->CountWinrate($v['user_id'], 1, 2);//月胜率
            }

            $playWhere = ($playType != 0) ? $playType : ['in', [-1,1]];
            $arr[$k]['todayNum'] = M('Gamble')->where(['user_id' => $v['user_id'], 'play_type'=>$playWhere, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();//当天推荐场数
            $one = M('Gamble')->where(['user_id' => $v['user_id'], 'play_type'=>$playWhere, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->order('id desc')->find();

            if($one){//若当天推荐存在
                $arr[$k]['todayHomeName'] = explode(',', $one['home_team_name']);//当天推荐主队名称
                $arr[$k]['todayAwayName'] = explode(',', $one['away_team_name']);//当天推荐客队名称
                $arr[$k]['createTime'] = $createTimeSort[] = $one['create_time'];

                if(in_array($playType, array(-1, 1))){
                    $playTypeArr[] = $arr[$k];
                }
            }else{
                $arr[$k]['todayHomeName'] = '';//当天推荐主队名称
                $arr[$k]['todayAwayName'] = '';//当天推荐客队名称
                $arr[$k]['createTime'] = $createTimeSort[] = 0;
            }
            unset($one);
        }

        if(in_array($playType, array(-1, 1))){//如果选玩法，则只出现玩法的内容
            $arr = $playTypeArr;
            foreach ($arr as $k => $v) {
                if($recordType == 4){
                    $backupArr[] = $v['lv'];
                }else if($recordType == 5){
                    $backupArr[] = $v['curr_victs_ftball'];
                }else if($recordType == 6){
                    $backupArr[] = $v['tenGambleRate'];
                }else{
                    $backupArr[] = ($recordType == 1) ? $v['weekPercnet'] : (($recordType == 2) ? $v['monthPercnet'] : $v['winrate']);
                }
                $backupTimeArr[] = $v['createTime'];
            }
            array_multisort($backupArr, SORT_DESC, $backupTimeArr, SORT_DESC, $arr);
        }else{
            if($recordType == 4){
                array_multisort($lvArr, SORT_DESC, $createTimeSort, SORT_DESC, $arr);
            }else if($recordType == 5){
                array_multisort($currArr, SORT_DESC, $createTimeSort, SORT_DESC, $arr);
            }else if($recordType == 6){
                array_multisort($tenGambleRateArr, SORT_DESC, $createTimeSort, SORT_DESC, $arr);
            }else{//全部情况下，都是按照排名排序
                array_multisort($rankSort, SORT_ASC, $createTimeSort, SORT_DESC, $arr);
            }
        }

        //释放无用的数据
        foreach ($arr as $k => $v) {
            unset($arr[$k]['head'], $arr[$k]['winrate'], $arr[$k]['createTime'], $arr[$k]['ranking'], $arr[$k]['tenGambleRate']);
        }
        unset($rankSort, $createTimeSort, $weekSort, $monthSort, $redList, $backupArr, $backupTimeArr, $lvArr, $currArr, $tenGambleRateArr);
        $arr = array_slice($arr, $pageSize, $pageNum);

        $this->ajaxReturn(['bigShotInfo' => $arr]);
    }

    /**
     *  V3.0大咖广场
     */
    public function masterGamble(){
        $userToken = getUserToken($this->param['userToken']);
        $page      = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum   = 10;
        $pageSize  = ($page-1)*$pageNum;

        //参数类型
        $playType  = $this->param['playType']  ? (int)$this->param['playType']  : 1;//默认亚盘
        $sortType  = $this->param['sortType']  ? (int)$this->param['sortType']  : 0;//默认综合
        $lvType    = isset($this->param['lvType']) && $this->param['lvType'] != '' ? (int)$this->param['lvType'] : '';//默认等级，不选
        $priceType = $this->param['priceType'] ? (int)$this->param['priceType'] : 0;//默认价格
        $unionType = $this->param['unionType'] ? (string)trim($this->param['unionType'], ',') : '';//默认全部
        $timestamp = $this->param['timestamp'] ? (int)$this->param['timestamp'] : 0;//默认0,我的关注时间戳

        $list = D('Home')->getMasterGamble($userToken, $playType, $sortType, $lvType, $priceType, $unionType, $pageSize, $pageNum, $timestamp);

        $this->ajaxReturn(['list' => (array)$list]);
    }

    /**
     * 热门高手,去大咖广场的亚盘前四、竞彩前四 (hzl)
     */
    public function hotMaster()
    {
        $cacheKey = MODULE_NAME . 'hotMaster';

        if(!$list = S($cacheKey)){
            $userToken = getUserToken($this->param['userToken']);

            //亚盘高手命中前四
            $ypMaster = D('Home')->getMasterGamble($userToken, $playType = 1, $sortType = 2, $lvType='', $priceType=0, $unionType='', $pageSize = 0, $pageNum=4, 0);

            foreach($ypMaster as $k1=>$vl){
                $ypList[$k1]['user_id']        = $vl['user_id'];
                $ypList[$k1]['face']           = $vl['face'];
                $ypList[$k1]['nick_name']      = $vl['nick_name'];
                $ypList[$k1]['todayGamble']    = '1';
                $ypList[$k1]['gambleType']     = '1';
                $ypList[$k1]['tenGambleRate']  = (string)$vl['tenGambleRate'];
            }

            //竞彩高手命中前四
            $jcMaster = D('Home')->getMasterGamble($userToken, $playType = 2, $sortType = 2, $lvType='', $priceType=0, $unionType='', $pageSize = 0, $pageNum=4, 0);

            foreach($jcMaster as $k2=>$v2){
                $jcList[$k2]['user_id']        = $v2['user_id'];
                $jcList[$k2]['face']           = $v2['face'];
                $jcList[$k2]['nick_name']      = $v2['nick_name'];
                $jcList[$k2]['todayGamble']    = '1';
                $jcList[$k2]['gambleType']     = '2';
                $jcList[$k2]['tenGambleRate']  = (string)$v2['tenGambleRate'];
            }

            $list = array_merge($ypList ?:[], $jcList ?:[]);

            if($list)
                S($cacheKey, $list, 500);
        }

        $this->ajaxReturn(['lists' => $list]);
    }

    /**
     * 昵称搜索（待优化）hzl
     **/
    public function queryByNick()
    {
        if (!$this->param['keyword'])
            $this->ajaxReturn(2015);

        $limit = 20;
        $page = $this->param['page'] ? $this->param['page'] : 1;
        $startRow = ($page - 1) * $limit;

        //模糊匹配
        $FrontUser = M('FrontUser')
            ->field(['id user_id', 'nick_name', 'head face', 'lv', 'lv_bet', 'LOCATE(\'' . $this->param['keyword'] . '\',nick_name ) AS pos',])
            ->where(['_string' => 'LOCATE(\'' . $this->param['keyword'] . '\', nick_name) > 0'])
            ->select();

        foreach ($FrontUser as $k => $v) {
            $game = D('GambleHall')->getGambleList($v['user_id'], $playType = 0, $page = 1, $gamble_id = 0, $gambleType = 0);

            //排序数组
            $matchArr[] = $v['pos']; //比配度（靠前）
            $lvArr[]    = $v['lv'];  //等级

            //拼接返回结果
            $FrontUser[$k]['face']      = (string)frontUserFace($v['face']);
            $FrontUser[$k]['lv']        = (string)$v['lv'];
            $FrontUser[$k]['lv_bet']    = (string)$v['lv_bet'];
            $FrontUser[$k]['gamble']    = $game[0]?[$game[0]]:[];
        }

        array_multisort($matchArr, SORT_ASC, $lvArr, SORT_DESC, $FrontUser);
        $lists = $FrontUser ? array_slice($FrontUser, $startRow, $limit) : [];

        $this->ajaxReturn(['lists' => $lists]);
    }

}

 ?>