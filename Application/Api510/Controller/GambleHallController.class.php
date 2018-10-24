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
        $gameType = $this->param['gameType'] ?: '1';//1足球、2篮球
        $playType = $this->param['playType'] ?: 1;

        if ($gameType == '1') {
            list($game, $union) = D('GambleHall')->matchList($playType);
        } else if ($gameType == '2') {
            list($game, $union) = D('GambleHall')->basketballList();
//            unset($game[0]);

            foreach($game as $k => $v){
                $game[$k]['union_name']     = is_array($v['union_name'])?:explode(',',$v['union_name']);
                $game[$k]['home_team_name'] = is_array($v['home_team_name'])?:explode(',',$v['home_team_name']);
                $game[$k]['away_team_name'] = is_array($v['away_team_name'])?:explode(',',$v['away_team_name']);
                unset($game[$k]['fsw_exp_away']);
                unset($game[$k]['fsw_exp_home']);
                unset($game[$k]['fsw_exp']);
                unset($game[$k]['fsw_total_home']);
                unset($game[$k]['fsw_total']);
                unset($game[$k]['fsw_total_away']);
                unset($game[$k]['psw_exp']);
                unset($game[$k]['psw_exp_home']);
                unset($game[$k]['psw_exp_away']);
                unset($game[$k]['psw_total_home']);
                unset($game[$k]['psw_total_away']);
                unset($game[$k]['psw_total']);
            }

            foreach($union as $k2 => $v2){
                $union[$k2]['union_name']     = is_array($v2['union_name'])?:explode(',',$v2['union_name']);
            }
            
        } else {
            $this->ajaxReturn(2001);
        }

        //广告、分享
        $adver = @Think\Tool\Tool::getAdList($classId = 20, 20, $this->param['platform']) ?: [];
        $adver = D('Home')->getBannerShare($adver);

        foreach ($adver as $k => $v) {
//            unset($adver[$k]['id']);
            unset($adver[$k]['img']);
        }

        $this->ajaxReturn(['matchList' => $game, 'union' => $union, 'adver' => $adver]);
    }

    /**
     * 点击赛程，进入推荐详情页 (hzl)
     */
    public function gamblePage ()
    {
        //ios审核不能推荐
//        if(iosCheck()){
//            $data['gstate_check'] = 0;
//            $this->ajaxReturn($data);
//        }

        $userToken  = getUserToken($this->param['userToken']);
        $gameId     = $this->param['gameId'];
        $gameType   = $this->param['gameType'] ?: 1;//1足球 2篮球
        $type       = $this->param['playType'] ?: 1;//1亚盘 2竞彩
        $game       = [];
        $blockTime  = getBlockTime($gameType);

        if($gameType ==  '1'){
            $data['ypOdds'] = $data['jcOdds'] = '';

            $fields1 = ['g.id', 'g.gtime', 'is_betting','g.fsw_exp_home', 'g.fsw_exp', 'g.fsw_exp_away', 'g.fsw_ball_home', 'g.fsw_ball', 'g.fsw_ball_away','g.game_state'];
            $fields2 = ['g.id', 'g.gtime', 'bet.bet_code', 'bet.home_odds', 'bet.draw_odds', 'bet.away_odds', 'bet.let_exp', 'bet.home_letodds', 'bet.draw_letodds', 'bet.away_letodds','g.game_state'];

            //亚盘数据：盘口、赔率、统计
            switch($type){
                case 1:
                    $game = M('GameFbinfo g')->field($fields1)->where(['g.game_id' => $gameId])->find();
                    $res = (new \Home\Services\PcdataService())->getOddsById($gameId, 2)[$gameId];

                    $data['ypOdds'] = [
                        'fsw_exp_home'  => $res[18] != '' ? $res[18] : ( $res[9] != '' ?  $res[9] : ($res[0] != '' ? $res[0] : $game['fsw_exp_home'])),  //让球主队的赔率
                        'fsw_exp'       => $res[19] != '' ? $res[19] : ($res[10] != '' ? $res[10] : ($res[1] != '' ? $res[1] : $game['fsw_exp'])),      //让球盘口
                        'fsw_exp_away'  => $res[20] != '' ? $res[20] : ($res[11] != '' ? $res[11] : ($res[2] != '' ? $res[2] : $game['fsw_exp_away'])), //让球客队赔率
                        'fsw_ball_home' => $res[21] != '' ? $res[21] : ($res[12] != '' ? $res[12] : ($res[3] != '' ? $res[3] : $game['fsw_ball_home'])),//大小球 大的赔率
                        'fsw_ball'      => $res[22] != '' ? $res[22] : ($res[13] != '' ? $res[13] : ($res[4] != '' ? $res[4] : $game['fsw_ball'])),     //大小球盘口
                        'fsw_ball_away' => $res[23] != '' ? $res[23] : ($res[14] != '' ? $res[14] : ($res[5] != '' ? $res[5] : $game['fsw_ball_away'])),//大小球 小的赔率
                    ];

                    $playWhere = [-1, 1];
                    $data['ypOdds']['fsw_exp']  = changeExp( $data['ypOdds']['fsw_exp']);
                    $data['ypOdds']['fsw_ball'] = changeExp( $data['ypOdds']['fsw_ball']);
                    $data['ypOdds']['odds_check']    = !$game || count(array_filter($data['ypOdds'])) <= count($data['ypOdds']) - 2 ? '0' : '1';
                    break;

                case 2:
                    $game = M('GameFbinfo g')->field($fields2)->join('LEFT JOIN qc_fb_betodds bet ON bet.game_id = g.game_id')->where(['g.game_id' => $gameId])->find();

                    $data['jcOdds'] = [
                        'home_odds'     => (string)$game['home_odds'],      //不让球主胜赔率
                        'draw_odds'     => (string)$game['draw_odds'],      //不让球平赔率
                        'away_odds'     => (string)$game['away_odds'],      //不让球客胜赔率
                        'let_exp'       => (string)$game['let_exp'],        //让球
                        'home_letodds'  => (string)$game['home_letodds'],   //让球主胜赔率
                        'draw_letodds'  => (string)$game['draw_letodds'],   //让球平赔率
                        'away_letodds'  => (string)$game['away_letodds'],   //让球客胜赔率
                    ];

                    $playWhere = [-2, 2];
                    $data['jcOdds']['odds_check']    = !$game || count(array_filter($data['jcOdds'])) <= count($data['jcOdds']) - 2 ? '0' : '1';
                    break;

                default:
                    $this->ajaxReturn(101);
                    break;
            }


            $userGamble = (array)M('Gamble')->field(['play_type', 'chose_side'])->where(['game_id' => $gameId, 'user_id' => $userToken['userid'], 'play_type' => ['in', $playWhere]])->select();

        }
        elseif($gameType == '2')
        {
            $game = M('gameBkinfo')->where(['game_id'=>$gameId])->field("gtime,fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away,game_state")->find();

            //获取即时赔率数据
            $bk_goal = M('bkGoal')->field('exp_value')->where(['company_id'=>2,'game_id'=>$gameId])->find();

            if($bk_goal)
            {
                $bk_odds = explode("^", $bk_goal['exp_value']);

                $whole   = explode(',', $bk_odds[0]);  //全场
                if($whole[6] !='' || $whole[7] !='' || $whole[8] !='')
                {

                    //全场滚球
                    $game['fsw_exp_home'] = substr($whole[6],0,4);
                    $game['fsw_exp']      = $whole[7];
                    $game['fsw_exp_away'] = substr($whole[8],0,4);
                }
                elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
                {
                    //全场即时
                    $game['fsw_exp_home'] = substr($whole[3],0,4);
                    $game['fsw_exp']      = $whole[4];
                    $game['fsw_exp_away'] = substr($whole[5],0,4);
                }

                $size    = explode(',', $bk_odds[1]);  //大小
                if($size[6] !='' || $size[7] !='' || $size[8] !='')
                {
                    //大小滚球
                    $game['fsw_total_home'] = substr($size[6],0,4);
                    $game['fsw_total']      = $size[7];
                    $game['fsw_total_away'] = substr($size[8],0,4);
                }

                elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
                {
                    //大小即时
                    $game['fsw_total_home'] = substr($size[3],0,4);
                    $game['fsw_total']      = $size[4];
                    $game['fsw_total_away'] = substr($size[5],0,4);
                }
            }

            $data['ypOdds'] = $game ? : [];
            $data['ypOdds']['odds_check']  = !$game || (count(array_filter($game)) <= count($game) - 2) ? '0' : '1';

            $userGamble = (array)M('Gamblebk')->field(['play_type', 'chose_side'])->where(['game_id' => $gameId, 'user_id' => $userToken['userid'], 'play_type' => ['in', [-1, 1]]])->select();
        }


        $data['gambleTotal'] = $this->getTenMaster($this->param['userToken'], $gameId, $gameType, $type, 0,true);

        $gstate_check = '0';//不在竞猜时间内
        if($game['game_state'] || ($game['gtime'] && $game['gtime'] < $blockTime['beginTime'])){//已完赛或者完赛
            $gstate_check = '-1';
        }elseif($game['gtime'] && $game['gtime'] >= $blockTime['beginTime'] && $game['gtime'] <= $blockTime['endTime']){//在竞猜时间内
            $gstate_check = '1';
        }

        if(isset($data['ypOdds']['game_state']))
            unset($data['ypOdds']['game_state']);

        if(isset($data['jcOdds']['game_state']))
            unset($data['jcOdds']['game_state']);

        $data['userGamble'] = $userGamble;
        $data['gstate_check'] = $gstate_check;

        //今日剩余推荐场数，历史的推荐就不返回了
        if(is_array($userToken)){
            $userGamble = D('GambleHall')->gambleLeftTimes($userToken['userid'], $gameType, $type);
            $data['leftTimes'] = "今日剩余{$userGamble[0]}场.";
        }else{
            switch ($gameType) {
                case '1':
                    $sign = $type == 1 ? 'fbConfig' : 'betConfig';
                    break;

                case '2': $sign = 'bkConfig';
                    break;
            }
            $gameConf = getWebConfig($sign);
            $blockTime   = getBlockTime($gameType,$gamble=true);
            if (in_array(date('N',$blockTime['beginTime']),[1,2,3,4])) //周1-4
            {
                $normTimes = $gameConf['weekday_norm_times'];
            }
            else
            {
                $normTimes = $gameConf['weekend_norm_times'];
            }
            $data['leftTimes'] = "今日剩余{$normTimes}场";
        }

        //该场赛事竞猜统计
        //$cacheKey = MODULE_NAME . '_gamblePage_gambleCount_' . $gameId . $gameType . $type;

        $gambleCount = $this->getTenMaster($this->param['userToken'], $gameId, $gameType, $type);

        $data['gambleCount'] = $gambleCount;
        $this->ajaxReturn($data);
    }

    /**
     * 赛事推荐统计 (hzl)
     * @param $userToken
     * @param $gameId
     * @param int $gameType
     * @param int $gambleType
     * @param int $playType
     * @param bool $getTotal
     * @return array
     */
    public function getTenMaster($userToken, $gameId, $gameType = 1, $gambleType = 1, $playType = 0,$getTotal = false)
    {
        //根据亚盘、竞彩玩法组装条件
        $jWhere = $wh = $userWeekGamble = $userids = $pageList = $userGamble = [];
        $time   = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;
        if($gameType == 1){
            $gambleModel = M('Gamble');
            if($gambleType == 1){
                $lvField            = 'f.lv lv';
                $wh['result']       = ['IN',['1', '0.5', '2', '-1', '-0.5']];
                $wh['play_type']    = ['IN', [-1, 1]];
                $jWhere['play_type']= ['IN', [-1, 1]];
            }else{
                $lvField            = 'f.lv_bet lv';
                $wh['result']       = ['IN', [1, -1]];
                $wh['play_type']    = ['IN', [2, -2]];
                $jWhere['play_type']= ['IN', [2, -2]];
            }

            if($playType)
                $wh['play_type'] = (int)$playType;
        }else{
            $gambleModel = M('Gamblebk');
            $lvField            = 'f.lv_bk lv';
            $wh['result']       = ['IN',['1', '0.5', '2', '-1', '-0.5']];
            $wh['play_type']    = ['IN', [-1, 1]];
            $jWhere['play_type']= ['IN', [-1, 1]];
        }

        //获取参与该场赛事竞猜的用户
        $fields = ['g.id gamble_id','g.user_id', 'g.play_type','g.chose_side','g.handcp','g.odds', 'g.is_impt',
            'g.result', 'g.voice','g.is_voice', 'g.voice_time', 'g.tradeCoin','g.desc', 'g.create_time','f.head face', 'f.nick_name', $lvField,'(g.quiz_number + g.extra_number) as quiz_number'];

        $where4 = ['EGT', 0];

        if($getTotal === true){
            $list = $gambleModel
                ->field('DISTINCT user_id')
                ->where(['game_id' => $gameId, 'play_type' => $wh['play_type'], 'tradeCoin' =>$where4])
                ->select();
            return (string)count($list);
        }else{
            $list = $gambleModel->alias("g")
                ->join("left join qc_front_user f on f.id = g.user_id")
                ->field($fields)
                ->where(['game_id' => $gameId, 'play_type' => $wh['play_type'], 'tradeCoin' =>$where4])
                ->group('g.user_id')
                ->order('lv desc')
                ->limit(100)
                ->select();
        }

        if($list){
            list($wBegin,$wEnd) = getRankBlockDate($gameType,1);//周
            list($mBegin,$mEnd) = getRankBlockDate($gameType,2);//月
            list($jBegin,$jEnd) = getRankBlockDate($gameType,3);//季

            $wBeginTime = strtotime($wBegin) + $time;
            $wEndTime   = strtotime($wEnd) + 86400 + $time;

            $mBeginTime = strtotime($mBegin) + $time;
            $mEndTime   = strtotime($mEnd) + 86400 + $time;

            $jBeginTime = strtotime($jBegin) + $time;
            $jEndTime   = strtotime($jEnd) + 86400 + $time;

            foreach($list as $vv){
                $userids[] = $vv['user_id'];
            }

            $wWhere['user_id']      = ['IN',$userids];
            $wWhere['result']       = ['IN', ['1', '0.5', '-1', '-0.5']];
            $wWhere['play_type']    = $jWhere['play_type'];
            $wWhere['create_time']  = ["between", [$wBeginTime, $wEndTime]];

            $userGamble = $gambleModel
                ->field('user_id, GROUP_CONCAT(result) as result')
                ->where($wWhere)
                ->group('user_id')
                ->select();

            //是否查看过本赛程
            $userInfo  = getUserToken($userToken);
            if (isset($userInfo['userid'])){
                $gambleId = (array)M('QuizLog')->where(['user_id' => $userInfo['userid'], 'game_id' => $gameId, 'game_type' => $gameType])->getField('gamble_id',true);
            }

            //周竞猜
            $userWeekGamble = array_column($userGamble, 'result', 'user_id');
            $lv = $weekSort = $monthSort = $seasonSort = $tenGamble = $sortTime = [];

            //月竞猜
            $jWhere['result']       = ["IN", ['1', '0.5', '-1', '-0.5']];
            $jWhere['create_time']  = ["between", [$jBeginTime, $jEndTime ]];

            foreach ($list as $k => $v)
            {
                //用户信息
                $list[$k]['face']       = frontUserFace($v['face']);
                $list[$k]['is_trade']   = in_array($v['gamble_id'], $gambleId) ? '1' : '0';
                $list[$k]['desc']       = (string)$v['desc'];

                //周胜率计算
                $wWin = $wHalf = $wTransport = $wDonate = 0;
                $resultArr = explode(',', $userWeekGamble[$v['user_id']]);

                foreach($resultArr as $resultV){
                    if($resultV == '1')     $wWin++;
                    if($resultV == '0.5')   $wHalf++;
                    if($resultV == '-1')    $wTransport++;
                    if($resultV == '-0.5')  $wDonate++;
                }
                $list[$k]['weekPercnet']    = (string)getGambleWinrate($wWin, $wHalf, $wTransport, $wDonate);


                //月、季胜率计算
                $jWhere['user_id'] = $v['user_id'];
                $jWin = $mWin = $jHalf = $mHalf = $jTransport = $mTransport = $jDonate = $mDonate = 0;
                $seasonGamble = $gambleModel->field(['result','earn_point','create_time'])->where($jWhere)->select();
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

                $list[$k]['monthPercnet']  = (string)getGambleWinrate($mWin, $mHalf, $mTransport, $mDonate);
                $list[$k]['seasonPercnet'] = (string)getGambleWinrate($jWin, $jHalf, $jTransport, $jDonate);

                //近十场胜负、胜平负
                $wh['user_id']    = $v['user_id'];
                $tenGamble  = $gambleModel->where($wh)->order("id desc")->limit(10)->getField('result',true);
//                $list[$k]['tenGamble'] = $tenGamble;
                $list[$k]['tenGambleRate'] = countTenGambleRate($tenGamble);;

                $_TenGambleSort = 0;
                foreach($tenGamble as $gamble_v){
                    if($gamble_v == 1 || $gamble_v == 0.5){
                        $_TenGambleSort++;
                    }
                }

                //过滤近十中5一下
                if($_TenGambleSort<5){
                    unset($list[$k]);
                    continue;
                }else{
                    $list[$k]['ten_rate'] = $_TenGambleSort;
                }

                unset($list[$k]['create_time']);

                //排序数组
                $lv[]           = $v['lv'];
                $tenGambleSort[]= $_TenGambleSort;
                $weekSort[]     = $list[$k]['weekPercnet'];

                $monthSort[]    = $list[$k]['monthPercnet'];
                $seasonSort[]   = $list[$k]['seasonPercnet'];
                $sortTime[]     = $v['create_time'];
                unset($list[$k]['lv_bet']);

                if($v['voice'] != '' && $v['is_voice'] == '1' ){
                    $list[$k]['voice'] = C('IMG_SERVER') . $v['voice'];
                }else{
                    $list[$k]['voice'] = '';
                }
                unset($list[$k]['create_time']);
                unset($list[$k]['is_voice']);

                $list[$k]['quiz_number'] = D('Common')->getQuizNumber($v['quiz_number']);
            }

            //排序：近十中几》周胜》等级》月》季》发布时间
            array_values($list);
            array_multisort($tenGambleSort, SORT_DESC, $weekSort,SORT_DESC, $lv, SORT_DESC, $monthSort, SORT_DESC, $seasonSort,SORT_DESC,$list);
        }
        return array_slice($list,0,10)?:[];
    }

    /**
     * hzl 竞猜统计
     */
    public function gambleCount()
    {
        $pageNum    = 15;
        $page       = $this->param['page'] ?: 1;
        $playType   = $this->param['play_type']?:0;  //1：让分，-1大小，2：竞彩
        $gambleType = $this->param['gamble_type']?:1; //1亚盘 2竞彩
        $gameType   = $this->param['game_type']?:1;//1篮球2足球
        $gameId     = $this->param['game_id'];
        $userToken  = $this->param['userToken'];

        //根据亚盘、竞彩玩法组装条件
        $jWhere = $wh = $userWeekGamble = $userids = $pageList = $userGamble = [];
        $time = $gameType == 1 ? (10 * 60 + 32) * 60 : (12 * 60) * 60;

        if($gameType == 1){
            $gModel = M('gameFbinfo');
            $gambleModel = M('Gamble');
            $gamble_time = C('fb_gamble_time');
            if($gambleType == 1){
                $lvField            = 'f.lv lv';
                $wh['play_type']    = ['IN', [-1, 1]];
                $jWhere['play_type']= ['IN', [-1, 1]];
            }else{
                $lvField            = 'f.lv_bet lv';
                $wh['result']       = ['IN', [1, -1]];
                $wh['play_type']    = ['IN', [2, -2]];
                $jWhere['play_type']= ['IN', [2, -2]];
            }

            if($playType)
                $wh['play_type'] = (int)$playType;
        }else{
            $gModel = M('gameBkinfo');
            $gambleModel = M('Gamblebk');
            $gamble_time = C('bk_gamble_time');
            $lvField            = 'f.lv_bk lv';
            $wh['play_type']    = ['IN', [-1, 1]];
            $jWhere['play_type']= ['IN', [-1, 1]];
        }
        $gtime = $gModel->where(['game_id'=> $gameId])->getField('gtime');
        $b = strtotime($gamble_time, $gtime);

        if($gtime < $b) $b = strtotime($gamble_time, $gtime - 86400);

        //竞猜该场赛事的用户
        $fields = ['g.id gamble_id','g.user_id', 'g.play_type','g.chose_side','g.handcp','g.odds', 'g.is_impt',
            'g.result', 'g.tradeCoin','g.desc', 'g.create_time','f.head face', 'f.nick_name', $lvField, '(g.quiz_number + g.extra_number) as quiz_number','g.voice','g.is_voice','g.voice_time'];

        $list = $gambleModel->alias("g")
            ->join("left join qc_front_user f on f.id = g.user_id")
            ->field($fields)
            ->where(['game_id' => $gameId, 'play_type' => $wh['play_type'],['create_time' => ['BETWEEN',[$b, $b + 86400]]]])
            ->group('g.user_id')
            ->select();

        if($list){
            foreach($list as $kk => $vv){
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
            $chunkUsers = array_chunk($userids, 200);
            foreach($chunkUsers as $cKey => $cVal){
                $wWhere = [
                    'user_id'       => ['in',$cVal],
                    'result'        => ['in', ['1', '0.5', '-1', '-0.5']],
                    'play_type'     => $jWhere['play_type'],
                    'create_time'   => ["between", [$wBeginTime, $wEndTime]],
                ];
                $cRes = $gambleModel->field('user_id, GROUP_CONCAT(result) as result')
                    ->where($wWhere)
                    ->group('user_id')
                    ->select();

                $userGamble = array_merge($userGamble, $cRes);
            }

            //每个用户的周竞猜结果
            $userWeekGamble = array_column($userGamble, 'result', 'user_id');

            $lv = $weekSort = $tradeCoin = $tradeCount = $sortTime = [];

            foreach ($list as $k1 => $v1)
            {
                $wWin = $wHalf = $wTransport = $wDonate = 0;
                //周胜率计算
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

            $userInfo  = getUserToken($userToken);

            //是否查看过本赛程
            if ($userInfo && $userInfo != -1){
                $gambleId = (array)M('QuizLog')->where(['user_id' => $userInfo['userid'], 'game_id' => $gameId, 'game_type' => $gameType])->getField('gamble_id',true);
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
                $jWhere['user_id'] = $v['user_id'];
                $jWin = $mWin = $jHalf = $mHalf = $jTransport = $mTransport = $jDonate = $mDonate = 0;
                $seasonGamble = $gambleModel->field(['result','earn_point','create_time'])->where($jWhere)->select();
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
                $tenArr  = $gambleModel->where($wh)->order("id desc")->limit(10)->getField('result',true);
                $pageList[$k]['tenGambleRate'] = (string)(countTenGambleRate($tenArr, $gambleType));

                if($v['voice'] != '' && $v['is_voice'] == '1' ){
                    $pageList[$k]['voice'] = C('IMG_SERVER') . $v['voice'];
                }else{
                    $pageList[$k]['voice'] = '';
                }
                unset($pageList[$k]['create_time']);
                unset($pageList[$k]['is_voice']);
                $pageList[$k]['quiz_number'] = D('Common')->getQuizNumber($v['quiz_number']);
            }
        }
        $this->ajaxReturn(['gambleList' => $pageList?:[]]);
    }

    /**
     * 个人中心-她的主页(hzl)
     */
    public function userHomePage()
    {
        $page       = $this->param['page'] ?: 1;
        $userToken  = getUserToken($this->param['userToken']);
        $playType   = $this->param['play_type'] ?: 0;   //默认0，全部，让分：1，大小：-1；竞彩：2
        $gameType   = $this->param['game_type'] ?: 1;   //足球：1 篮球：2
        $gambleType = $this->param['gamble_type'];      //默认0，1亚盘 2竞彩
        $gamble_id  = isset($this->param['gamble_id']) ? (int)$this->param['gamble_id'] : 0;
        $user_id    = ($this->param['user_id'] && $userToken['userid'] != $this->param['user_id']) ? $this->param['user_id'] : $userToken['userid'];
        D('Common')->setFrontSeeNum($user_id,'app');
        if($page == 1){
            $userInfo                = M('FrontUser')->field(['nick_name','lv','lv_bet','lv_bk','descript','head face','is_expert'])->where(['id'=>$user_id])->find();
            $userInfo['fansNum']     = M('FollowUser')->where(['follow_id'=>$user_id])->count();
            $userInfo['face']        = frontUserFace($userInfo['face']);
            if(iosCheck()){
                $userInfo['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $userInfo['nick_name']);
                $userInfo['descript'] = str_replace(C('filterNickname'), C('replaceWord'), $userInfo['descript']);
            }

            //亚盘统计
            $returnArr['yp']                     = D('GambleHall')->getWinning($user_id, $gameType, 0, 1, 0);//查总的

            $returnArr['yp']['lv']               = $gameType == 2 ? $userInfo['lv_bk'] : $userInfo['lv'];
            $returnArr['yp']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($user_id, $gameType, 1);
            $returnArr['yp']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($user_id, $gameType, 2);
            $returnArr['yp']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($user_id, $gameType, 3);
            $returnArr['yp']['total_times']      = (string)$userInfo['yp']['total_times'];

            if($gameType == 1){//足球时返回竞彩数据
                //竞彩统计
                $returnArr['jc']                     = D('GambleHall')->getWinning($user_id, $gameType, 0, 2, 0);
                $returnArr['jc']['lv']               = $userInfo['lv_bet'];
                $returnArr['jc']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($user_id, $gameType, 1, false, false, 0, 2);
                $returnArr['jc']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($user_id, $gameType, 2, false, false, 0, 2);
                $returnArr['jc']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($user_id, $gameType, 3, false, false, 0, 2);

                $returnArr['jc']['total_times']      = (string)$userInfo['jc']['total_times'];
                unset($userInfo['gamble']['level']);
            }

            if ($userToken['userid']) {
                $isFollow = M('FollowUser')->where(['user_id'=>$userToken['userid'],'follow_id'=>$this->param['user_id']])->find();
            }

            $userInfo['isFollow'] = $isFollow ? '1' : '0';
            $userInfo['sub'] = $isFollow['sub'] ? '1' : '0';
            unset($userInfo['lv']);
            unset($userInfo['lv_bet']);
            unset($userInfo['lv_bk']);
            $returnArr['userInfo'] = $userInfo;

        }

        $gambleList = D('GambleHall')->getGambleList($user_id, $playType, $page, $gamble_id, $gambleType, $gameType);

        foreach ($gambleList as $k => $v)
        {
            if ($userToken['userid']){
                $isTrade = D('Common')->getTradeLog($v['gamble_id'], $userToken['userid'], $gameType);
            }
            $gambleList[$k]['is_trade'] = $isTrade ? '1' : '0';
            $gambleList[$k]['game_type'] =   $gameType;
        }
        $returnArr['gambleList'] = $gambleList;

        $returnArr['total_times'] = D('GambleHall')->getGambleList($user_id, $playType, $page, $gamble_id, $gambleType, $gameType,true);

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
                $isTrade = M('QuizLog')->where(['user_id'=>$userToken['userid'],'gamble_id'=>$v['gamble_id']])->getField('id');
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
     * 排行榜接口
     * page 页码
     * gambleType 1亚盘 2竞彩
     * rankType 1胜率榜 2盈利榜
     * gameType 1足球 2篮球
     * dateType 1周榜 2月榜 3季榜 4日榜
     */
    public function rank(){
        $page       = $this->param['page'] ?: 1;
        $rankType   = $this->param['rank_type'] ?: 1;
        $gameType   = $this->param['game_type'] ?: 1;
        $gambleType = $this->param['gamble_type'] ?: 1;
        $dateType   = $this->param['date_type'] ?: 4;
        $todayGamble= $this->param['today_gamble'] ?:0;
        $pageNum    = 20;
        $userToken  = getUserToken($this->param['userToken']);
        $gambleTb   = $gameType == 2 ? '__GAMBLEBK__' : '__GAMBLE__';
        $gambleType2 = $gameType == 2 ? '1' : $gambleType;

        if($gambleType2 == '1'){
            if($rankType == '1'){
                $rank = $this->winRateRank($userToken['userid'],$gambleTb,$gameType,$dateType,$page,$pageNum,$todayGamble);
            }else{
                $rank = $this->profitRank($userToken['userid'],$gambleTb,$gameType,$dateType,$page,$pageNum,$todayGamble);
            }
        }else{
            if($rankType == '1'){
                $rank = $this->betWinRateRank($userToken['userid'],$gambleTb,$gameType,$dateType,$page,$pageNum,$todayGamble);
            }else{
                $rank = $this->betProfitRank($userToken['userid'],$gambleTb,$gameType,$dateType,$page,$pageNum,$todayGamble);
            }
        }

        if(iosCheck()){
            foreach($rank['rankList'] as $k => &$v){
                $v['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $v['nick_name']);
            }
        }

        $this->ajaxReturn($rank);
    }

    /**
     * 亚盘榜
     * @param
     * $userid
     * @param $gambleTb
     * @param int $gameType
     * @param int $dateType
     * @param int $page
     * @param int $pageNum
     * @param int $todayGamble
     * @return array
     */
    public function winRateRank($userid, $gambleTb, $gameType=1, $dateType=4, $page=1, $pageNum=20, $todayGamble=0){
        $blockTime  = getBlockTime($gameType, true);
        $myRankKey  = MODULE_NAME . '_my_winrate_rank_' . $gameType . $dateType . $userid;
        $expire = 300;
        if($dateType == 4){
            $listDate   = date('Ymd', strtotime("-1 day"));
            $exist = M('RedList')->where(['list_date' => $listDate, 'game_type' => $gameType])->field('id')->find();

            if (!$exist)
                $listDate = date('Ymd', strtotime("-2 day"));

            $field = ['r.user_id', 'r.ranking', 'r.gameCount', 'r.win', 'r.half', 'r.`level`', 'r.transport', 'r.donate', 'r.winrate', 'r.pointCount'];
            $where = ['r.list_date' => $listDate, 'r.game_type' => $gameType];
            $cacheKey = MODULE_NAME . '_winrate_rank_' . $gameType . $listDate . $todayGamble . $page . $pageNum;

            if (true || !$rank = S($cacheKey)) {//读取缓存

                if ($todayGamble) {
//                    $where['g.play_type'] = ['IN', [1, -1]];
//                    $rank = (array)M('RedList r')
//                        ->field($field)
//                        ->join('left join ' . $gambleTb . ' g on g.user_id = r.user_id')
//
//                        ->where(array_merge($where,['g.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]]))
//                        ->group('r.user_id')
//                        ->order('r.ranking')
//                        ->page($page.','.$pageNum)
//                        ->select();
                    $tempRank = (array)M('RedList r')
                        ->field($field)
                        ->where($where)
                        ->order('r.ranking')
//                        ->limit(($page - 1) * 5, $pageNum*25)
                        ->select();

                    foreach($tempRank as $k=>$v){
                        $userids[] = $v['user_id'];
                    }

                    $gambleTb2   = $gameType == 2 ? 'Gamblebk' : 'Gamble';
                    $gm = M($gambleTb2)->field('user_id')->where(['user_id' => ['IN', $userids], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],'play_type' => ['IN', [1, -1]]])->getField('user_id', true);
                    $gambleUsers = array_unique($gm);

                    foreach($tempRank as $k2=>$v2){
                        if(in_array($v2['user_id'], $gambleUsers)){
                            $rank1[] = $v2;
                        }
                    }
                    $rank = array_slice($rank1, ($page - 1) * $pageNum, $pageNum);

                } else {
                    $rank = M('RedList r')
                        ->field($field)
                        ->cache($cacheKey, $expire, 'Redis')
                        ->where($where)
                        ->page($page . ',' . $pageNum)
                        ->select();
                }
            }

            //我的排名
            if ($userid) {
                if (!$myRank = S($myRankKey)) {
                    $where['r.user_id'] = $userid;
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
                        'curr_victs'=> D('GambleHall')->getWinning($userid, $gameType, 0, 1)['curr_victs'] ?: '0'
                    ];

                    if ($myRank)
                        S($myRankKey, $myRank, $expire - 3);
                }
            }
        }else{
            if ($userid) {
                if (!$myRank = S($myRankKey)) {
                    $rankData = (array)D('GambleHall')->getRankingData($gameType, $dateType, $userid);

                    if ($rankData) {
                        $myRank = $rankData[0];
                        $myRank['ranking'] .= '名';
                    } else {
                        $myRank = D('GambleHall')->CountWinrate($userid, $gameType, $dateType, true);
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
                        'curr_victs'=> D('GambleHall')->getWinning($userid, 1, 0, 1, 100)['curr_victs'] ?: '0'
                    ];

                    if ($myRank)
                        S($myRankKey, $myRank, $expire);
                }
            }

            //周、月、季 排行数据
            $rank = (array)D('GambleHall')->getRankingData($gameType, $dateType, null, false, $page, $pageNum, $todayGamble);
        }

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
            $m =  $gameType == 2 ? M('Gamblebk') : M('Gamble');
            $rank[$k]['today_gamble']   = $m->where($today_gamle_where)->getField('id') ? '1' : '0';

            //是否已经关注
            if ($userid)
                $rank[$k]['isFollow']   = M('FollowUser')->where(['user_id' => $userid, 'follow_id' => $v['user_id']])->find() ? '1' : '0';

            unset($rank[$k]['head']);
            unset($rank[$k]['donate']);
            unset($rank[$k]['half']);
            unset($rank[$k]['lv']);
            unset($rank[$k]['lv_bk']);
        }

        return ['myRank' => $myRank?:'', 'rankList' => $rank ?: []];
    }

    /**
     * （排行榜）足球、篮球亚盘盈利榜 hzl
     */
    public function profitRank($userid, $gambleTb, $gameType=1, $dateType=4,$page=1, $pageNum=20, $todayGamble=0)
    {
        $expire     = 5*60;
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $cacheKey   = MODULE_NAME . '_profit_rank_' . $gameType . $todayGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . '_my_profit_rank_' . $gameType . $dateType . $userid;

        switch (intval($dateType)) {
            case 1: //周
            case 2: //月
            case 3: //季
                $endDate    = getRankDate($dateType)[1];
                $topEndDate = getTopRankDate($dateType)[1];

                $where      = ['r.listDate' => ["EQ", $endDate], 'r.dateType' => $dateType, 'gameType' => $gameType];
                $topWhere   = ['r.listDate' => ["EQ", $topEndDate], 'r.dateType' => $dateType, 'gameType' => $gameType];
                break;

            case 4: //日
                $where      = ['r.listDate' => date('Ymd', strtotime("-1 day")), 'r.dateType' => $dateType, 'gameType' => $gameType];
                $topWhere   = ['r.listDate' => date('Ymd', strtotime("-2 day")), 'r.dateType' => $dateType, 'gameType' => $gameType];
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

            if ($todayGamble) {
                //筛选今日推荐
//                $where['g.play_type']   = ['IN', [1, -1]];
//                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
//                $rankLists = M('earnPointList r')
//                    ->field($field)
//                    ->join('left join '.$gambleTb.' g on g.user_id = r.user_id')
//                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
//                    ->where($where)
//                    ->group('r.user_id')
//                    ->order('r.ranking ASC')
//                    ->page($page . ',' . $pageNum)
//                    ->select();

                //优化
                $tempRank = (array)M('earnPointList r')
                    ->field($field)
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->where($where)
                    ->order('r.ranking')
//                    ->limit(($page - 1) * $pageNum, $pageNum*25)
                    ->select();

                foreach($tempRank as $k=>$v){
                    $userids[] = $v['user_id'];
                }

                $gambleTb2   = $gameType == 2 ? 'Gamblebk' : 'Gamble';
                $w = ['user_id' => ['IN', $userids], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],'play_type' => ['IN', [1, -1]]];
                $gm = M($gambleTb2)->field('user_id')->where($w)->getField('user_id', true);
                $gambleUsers = array_unique($gm);

                foreach($tempRank as $k2=>$v2){
                    if(in_array($v2['user_id'], $gambleUsers)){
                        $rank1[] = $v2;
                    }
                }

                $rankLists = array_slice($rank1, ($page - 1) * $pageNum, $pageNum);
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
                $rankLists[$k]['curr_victs']    = D('GambleHall')->getWinning($v['user_id'], $gameType, 0, 1, 100)['curr_victs'] ?: '0';

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
        if ($userid) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('earnPointList r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userid;
                $fields = ['r.user_id', 'r.win', 'r.half', 'r.transport', 'r.donate', 'r.pointCount', 'r.winrate','r.ranking'];
                $rankData = M('earnPointList r')->field($fields)->where($where)->select();
                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank = D('GambleHall')->CountWinrate($userid, $gameType, $dateType, true);
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
                    'curr_victs'=> D('GambleHall')->getWinning($userid, $gameType, 0, 1, 100)['curr_victs'] ?: '0'
                ];

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        //排行用户信息（提出来不加入缓存）
        foreach ($rankLists as $k => $v) {
            $today_gamle_where              = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [1,-1]]];
            $m =  $gameType == 2 ? M('Gamblebk') : M('Gamble');
            $rankLists[$k]['today_gamble']  = $m->where($today_gamle_where)->getField('id') ? '1' : '0';

            if ($userid)
                $rankLists[$k]['isFollow']  = M('FollowUser')->where(['user_id' => $userid, 'follow_id' => $v['user_id']])->find() ? '1' : '0';
        }

        return ['myRank' => $myRank ?:'', 'rankList' => $rankLists ?: []];
    }

    /**
     * 排行榜：足球竞彩胜率榜
     * @param $userid
     * @param $gambleTb
     * @param $gameType
     * @param $dateType
     * @param $page
     * @param $pageNum
     * @param $todayGamble
     * @return array
     */
    public function betWinRateRank($userid,$gambleTb,$gameType,$dateType,$page,$pageNum,$todayGamble)
    {
        $expire     = 5*60;
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $cacheKey   = MODULE_NAME . '_bet_winrate_rank_' . $gameType . $todayGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . '_my_bet_winrate_rank_' . $gameType . $dateType . $userid;

        switch (intval($dateType)) {
            case 1: //周
            case 2: //月
            case 3: //季
                $endDate    = getRankDate($dateType)[1];
                $topEndDate = getTopRankDate($dateType)[1];

                $where      = ['r.listDate' => ["EQ", $endDate], 'r.dateType' => $dateType, 'gameType' => $gameType];
                $topWhere   = ['r.listDate' => ["EQ", $topEndDate], 'r.dateType' => $dateType, 'gameType' => $gameType];
                break;

            case 4: //日
                $where      = ['r.listDate' => date('Ymd', strtotime("-1 day")), 'r.dateType' => $dateType, 'gameType' => $gameType];
                $topWhere   = ['r.listDate' => date('Ymd', strtotime("-2 day")), 'r.dateType' => $dateType, 'gameType' => $gameType];
                break;

            default:
                $this->ajaxReturn(['myRank' => '', 'rankList' => '']);
        }

        if (!$rankLists = S($cacheKey)) {
            $count = M('rankBetting r')->where($where)->count();

            $field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.win', 'r.transport', 'r.winrate','r.gameCount', 'r.pointCount'];

            if (!$count)
                $where = $topWhere;

            if ($todayGamble) {
                //筛选今日推荐
//                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
//                $where['g.play_type']   = ['IN', [2, -2]];
//                $rankLists = M('rankBetting r')
//                    ->field($field)
//                    ->join('left join '.$gambleTb.' g on g.user_id = r.user_id')
//                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
//                    ->where($where)
//                    ->group('r.user_id')
//                    ->order('r.ranking ASC')
//                    ->page($page . ',' . $pageNum)
//                    ->select();

                //优化
                $tempRank = (array)M('rankBetting r')
                    ->field($field)
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->where($where)
                    ->order('r.ranking')
//                    ->limit(($page - 1) * $pageNum, $pageNum * 25)
                    ->select();

                foreach($tempRank as $k=>$v){
                    $userids[] = $v['user_id'];
                }

                $gambleTb2   = $gameType == 2 ? 'Gamblebk' : 'Gamble';
                $w = ['user_id' => ['IN', $userids], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],'play_type' => ['IN', [2, -2]]];
                $gm = M($gambleTb2)->field('user_id')->where($w)->getField('user_id', true);
                $gambleUsers = array_unique($gm);

                foreach($tempRank as $k2=>$v2){
                    if(in_array($v2['user_id'], $gambleUsers)){
                        $rank1[] = $v2;
                    }
                }

                $rankLists = array_slice($rank1, ($page - 1) * $pageNum, $pageNum);

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
                $rankLists[$k]['curr_victs']    = D('GambleHall')->getWinning($v['user_id'], $gameType, 0, 2, 100)['curr_victs'] ?: '0';

                //头像、昵称
                $rankLists[$k]['face']          = frontUserFace($v['face']);
                $rankLists[$k]['nick_name']     = $v['nick_name'] ?: '';
            }

            if ($rankLists)
                S($cacheKey, $rankLists, $expire);
        }

        //我的排名
        if ($userid) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('rankBetting r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userid;
                $rankData = M('rankBetting r')->field(['r.user_id', 'r.pointCount', 'r.ranking', 'r.win', 'r.transport', 'r.winrate'])->where($where)->select();

                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank = D('GambleHall')->CountWinrate($userid, 1, $dateType, true, false, 0, 2);
                    $myRank['ranking']      = '未上榜';
                }

                //构造排名数据
                $myRank = [
                    'ranking'   => $myRank['ranking'] ? (string)$myRank['ranking'] : '0',
                    'pointCount'=> $myRank['pointCount'] ? (string)$myRank['pointCount'] : '0',
                    'win'       => (string)($myRank['win'] + $myRank['half']),
                    'transport' => (string)($myRank['donate'] + $myRank['transport']),
                    'winrate'   => $myRank['winrate'] ? (string)$myRank['winrate'] : '0',
                    'curr_victs'=> D('GambleHall')->getWinning($userid, $gameType, 0, 2, 100)['curr_victs'] ?: '0'
                ];

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        //排行榜用户信息（不加入缓存）
        foreach ($rankLists as $k => $v) {
            $today_gamle_where              = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [2,-2]]];
            $m =  $gameType == 2 ? M('Gamblebk') : M('Gamble');
            $rankLists[$k]['today_gamble']  = $m->where($today_gamle_where)->getField('id') ? '1' : '0';

            $isFollow = 0;
            if ($userid)
                $isFollow = M('FollowUser')->where(['user_id' => $userid, 'follow_id' => $v['user_id']])->find();

            $rankLists[$k]['isFollow'] = $isFollow ? '1' : '0';
        }
        return ['myRank' => $myRank ?: "", 'rankList' => $rankLists ?: []];
    }

    /**
     * 排行榜：足球竞彩盈利榜榜
     * @param $userid
     * @param $gambleTb
     * @param $gameType
     * @param $dateType
     * @param $page
     * @param $pageNum
     * @param $todayGamble
     */
    public function betProfitRank($userid,$gambleTb,$gameType,$dateType,$page,$pageNum,$todayGamble)
    {
        $expire     = 5*60;
        $blockTime  = getBlockTime(1, true);//今日足球推荐时间段
        $cacheKey   = MODULE_NAME . '_bet_profit_rank_' . $gameType . $todayGamble . $dateType . $page . $pageNum;
        $myCacheKey = MODULE_NAME . '_my_bet_profit_rank_' . $gameType . $dateType . $userid;

        switch (intval($dateType)) {
            case 1: //周
            case 2: //月
            case 3: //季
                $endDate    = getRankDate($dateType)[1];
                $topEndDate = getTopRankDate($dateType)[1];

                $where      = ['r.listDate' => ["EQ", $endDate], 'r.dateType' => $dateType, 'gameType' => $gameType];
                $topWhere   = ['r.listDate' => ["EQ", $topEndDate], 'r.dateType' => $dateType, 'gameType' => $gameType];
                break;

            case 4: //日
                $where      = ['r.listDate' => date('Ymd', strtotime("-1 day")), 'r.dateType' => $dateType, 'gameType' => $gameType];
                $topWhere   = ['r.listDate' => date('Ymd', strtotime("-2 day")), 'r.dateType' => $dateType, 'gameType' => $gameType];
                break;

            default:
                $this->ajaxReturn(['myRank' => '', 'rankList' => []]);
        }

        if (!$rankLists = S($cacheKey)) {
            $count = M('rankBetprofit r')->where($where)->count();

            $field = ['r.user_id', 'f.nick_name', 'r.ranking', 'f.head' => 'face', 'r.gameCount', 'r.ranking', 'r.win', 'r.transport', 'r.winrate', 'r.pointCount'];

            if (!$count)
                $where = $topWhere;

            if ($todayGamble) {
//                //筛选今日推荐
//                $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];
//                $where['g.play_type']   = ['IN', [2, -2]];
//                $rankLists = M('rankBetprofit r')
//                    ->field($field)
//                    ->join('left join '.$gambleTb.' g on g.user_id = r.user_id')
//                    ->join('left join __FRONT_USER__ f on f.id = r.user_id')
//                    ->where($where)
//                    ->group('r.user_id')
//                    ->order('r.ranking ASC')
//                    ->page($page . ',' . $pageNum)
//                    ->select();

                //优化
                $tempRank = (array)M('rankBetprofit r')
                    ->field($field)
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->where($where)
                    ->order('r.ranking')
//                    ->limit(($page - 1) * $pageNum, $pageNum * 25)
                    ->select();

                foreach($tempRank as $k=>$v){
                    $userids[] = $v['user_id'];
                }

                $gambleTb2   = $gameType == 2 ? 'Gamblebk' : 'Gamble';
                $w = ['user_id' => ['IN', $userids], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],'play_type' => ['IN', [2, -2]]];
                $gm = M($gambleTb2)->field('user_id')->where($w)->getField('user_id', true);
                $gambleUsers = array_unique($gm);

                foreach($tempRank as $k2=>$v2){
                    if(in_array($v2['user_id'], $gambleUsers)){
                        $rank1[] = $v2;
                    }
                }
                $rankLists = array_slice($rank1, ($page - 1) * $pageNum, $pageNum);

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
                $rankLists[$k]['curr_victs']    = D('GambleHall')->getWinning($v['user_id'], $gameType, 0, 2, 100)['curr_victs'] ?: '0';

                //头像、昵称
                $rankLists[$k]['face']          = frontUserFace($v['face']);
                $rankLists[$k]['nick_name']     = $v['nick_name'] ?: '';
            }

            if ($rankLists)
                S($cacheKey, $rankLists, $expire);
        }

        //我的排名
        if ($userid) {
            if (!$myRank = S($myCacheKey)) {
                $count = M('rankBetprofit r')->where($where)->count();

                if (!$count)
                    $where = $topWhere;

                $where['r.user_id'] = $userid;
                $rankData = M('rankBetprofit r')->field(['r.user_id', 'r.pointCount', 'r.ranking', 'r.win', 'r.transport', 'r.winrate'])->where($where)->select();

                if ($rankData) {
                    $myRank = $rankData[0];
                    $myRank['ranking'] .= '名';
                } else {
                    $myRank = D('GambleHall')->CountWinrate($userid, 1, $dateType, true, false, 0, 2);
                    $myRank['ranking']      = '未上榜';
                }

                //构造排名数据
                $myRank = [
                    'ranking'   => $myRank['ranking'] ? (string)$myRank['ranking'] : '0',
                    'pointCount'=> $myRank['pointCount'] ? (string)$myRank['pointCount'] : '0',
                    'win'       => (string)($myRank['win'] + $myRank['half']),
                    'transport' => (string)($myRank['donate'] + $myRank['transport']),
                    'winrate'   => $myRank['winrate'] ? (string)$myRank['winrate'] : '0',
                    'curr_victs'=> D('GambleHall')->getWinning($userid, $gameType, 0, 2, 100)['curr_victs'] ?: '0'
                ];

                if ($myRank)
                    S($myCacheKey, $myRank, $expire);
            }
        }

        //是否关注、今日是否有推荐（不加入缓存）
        foreach ($rankLists as $k => $v) {
            $today_gamle_where              = ['user_id' => $v['user_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]], 'play_type' => ['IN', [2,-2]]];
            $m =  $gameType == 2 ? M('Gamblebk') : M('Gamble');
            $rankLists[$k]['today_gamble']  =$m->where($today_gamle_where)->getField('id') ? '1' : '0';

            $isFollow = 0;
            if ($userid)
                $isFollow = M('FollowUser')->where(['user_id' => $userid, 'follow_id' => $v['user_id']])->find();

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

        //兑换中心的优惠券配置信息
        $sign = $this->param['platform'] == 2 ? 'ticketIos' : 'ticket';
        $ticketConfig = getWebConfig($sign);

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

        $this->ajaxReturn(['bannerList'=>$Recommend, 'ticketConfig' => $ticketConfig ? (array)$ticketConfig : []]);
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
                    $jingcaiArr[$k2]['is_trade'] = M('QuizLog')->where(['gamble_id'=>$v2['gamble_id'], 'user_id'=>$userToken['userid']])->getField('id') ? 1 : 0;//是否已查看购买过
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
     *  V5.1大咖广场
     */
    public function masterGamble(){
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $userToken = getUserToken($this->param['userToken']);
        $page      = $this->param['page'] ? (int)$this->param['page'] : 1;//页码大于1表示翻页
        $pageNum   = 10;
        $pageSize  = ($page-1)*$pageNum;
        $platform  = $this->param['platform'] ?: 0;

        //参数类型
        $playType  = $this->param['playType']  ? (int)$this->param['playType']  : 1;//默认亚盘
        $sortType  = $this->param['sortType']  ? (int)$this->param['sortType']  : 0;//默认综合
        $lvType    = isset($this->param['lvType']) && $this->param['lvType'] != '' ? (int)$this->param['lvType'] : '';//默认等级，不选
        $priceType = $sortType == 0 ? 1 : 0;//综合默认价格高，其他不选；1：价格高；2：价格低
        $unionType = $this->param['unionType'] ? (string)trim($this->param['unionType'], ',') : '';//默认全部
        $timestamp = $this->param['timestamp'] ? (int)$this->param['timestamp'] : 0;//默认0,我的关注时间戳

        $list = D('Home')->getMasterGamble($userToken, $playType, $sortType, $lvType, $priceType, $unionType, $pageSize, $pageNum, $timestamp, $game_type, $platform);

        $this->ajaxReturn(['list' => (array)$list]);
    }

    /**
     * 热门高手 (hzl)
     * 足球取大咖广场的亚盘前四、竞彩前四
     * 足球取大咖广场的前8
     */
    public function hotMaster()
    {
        $game_type = $this->param['game_type'] ?: 1;//默认1足球，2篮球
        $cacheKey = MODULE_NAME . 'hotMaster' . $game_type;

        if(!$list = S($cacheKey)){
            $userToken = getUserToken($this->param['userToken']);
            if($game_type == 2){
                $list2 = D('Home')->getMasterGamble($userToken, 1, 2, '', 0, '', 0, 8, 0, 2);
                $list = [];
                foreach($list2 as $k1=>$vl){
                    $list[$k1]['user_id']        = $vl['user_id'];
                    $list[$k1]['face']           = $vl['face'];
                    $list[$k1]['nick_name']      = $vl['nick_name'];
                    $list[$k1]['todayGamble']    = '1';
                    $list[$k1]['gambleType']     = '1';
                    $list[$k1]['tenGambleRate']  = (string)$vl['tenGambleRate'];
                }
            }else{
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
            }
        }

        if($list)
            S($cacheKey, $list, 500);

        $this->ajaxReturn(['lists' => $list?:[]]);
    }

    /**
     * 昵称搜索hzl
     **/
    public function search()
    {
        $limit  = 15;
        $page   = $this->param['page'] ? $this->param['page'] : 1;
        $gameType   = $this->param['game_type'] ? $this->param['game_type'] : '1';
        $start  = ($page - 1) * $limit;

        //默认昵称搜索
        if(isset($this->param['keyword'])){
            $FrontUser = M('FrontUser')
                ->field(['id user_id', 'nick_name', 'head face', 'lv', 'lv_bet', 'lv_bk',"LOCATE('" . $this->param['keyword'] . "',nick_name ) AS pos",])
                ->where(['_string' => "LOCATE('" . $this->param['keyword'] . "', nick_name) > 0"])
                ->select();

            foreach ($FrontUser as $k => $v) {
                //排序数组
                $matchArr[] = $v['pos']; //比配度（靠前）
                $lvArr[]    =  isset($this->param['lv'])?$v['lv']:$v['lv_bk'];  //等级
            }

            array_multisort($matchArr, SORT_ASC, $lvArr, SORT_DESC, $FrontUser);
            $data = array_slice($FrontUser, $start, $limit);

            foreach($data as $k2 => $v2){
                //拼接返回结果
                $data[$k2]['face']      = (string)frontUserFace($v2['face']);
                $data[$k2]['lv']        = (string)$v2['lv'];
                $data[$k2]['lv_bet']    = (string)$v2['lv_bet'];
                $data[$k2]['lv_bk']    = (string)$v2['lv_bk'];
                //最新竞猜
                $new_gamble = D('GambleHall')->getGambleList($v2['user_id'], $playType = 0, $page = 1, $gamble_id = 0, $gambleType = 0,$gameType );
                $data[$k2]['gamble']       = $new_gamble[0] ? [$new_gamble[0]] : [];
                unset($data[$k2]['pos']);
            }

        }else{
            $model   = $this->param['game_type'] ? M('Gamble') : M('Gamblebk');
            $lv = isset($this->param['lv'])?'lv'.$this->param['lv']:(isset($this->param['lv_bet'])?'lv_bet'.$this->param['lv_bet']:'lv_bk'.$this->param['lv_bk']);
            $cache_key = MODE_NAME . 'lv_query_' . $lv . '_' .$this->param['page'];
            if(!$data = S($cache_key)){
                $wh = $FrontUser = $userWeekGamble = $userids = $userGamble = $victsSort = [];
                $time = (10 * 60 + 32) * 60;
                if(isset($this->param['lv'])){//亚盘等级搜索
                    $gambleType = '1';
                    $FrontUser = M('FrontUser')->field(['id user_id', 'nick_name', 'head face', 'lv'])->where(['lv' => $this->param['lv']])->select();
                    $wh['result']       = ['IN',['1', '0.5', '2', '-1', '-0.5']];
                    $wh['play_type']    = ['IN', ['-1', '1']];

                }elseif(isset($this->param['lv_bet'])){
                    $gambleType = '2';
                    $FrontUser = M('FrontUser')->field(['id user_id', 'nick_name', 'head face', 'lv_bet'])->where(['lv_bet' => $this->param['lv_bet']])->select();
                    $wh['result']       = ['IN', ['1', '-1']];
                    $wh['play_type']    = ['IN', ['2', '-2']];
                }elseif(isset($this->param['lv_bk'])){
                    $gambleType = '1';
                    $FrontUser = M('FrontUser')->field(['id user_id', 'nick_name', 'head face', 'lv_bk'])->where(['lv_bk' => $this->param['lv_bk']])->select();
                    $wh['result']       = ['IN', ['1', '-1']];
                    $wh['play_type']    = ['IN', ['1', '-1']];
                }
                if($FrontUser){
                    foreach($FrontUser as $kk => $vv){
                        $userids[] = $vv['user_id'];
                    }

                    list($wBegin,$wEnd) = getRankBlockDate($gameType, 1);//周

                    $wBeginTime = strtotime($wBegin) + $time;
                    $wEndTime   = strtotime($wEnd) + 86400 + $time;

                    //每个用户的周竞猜
                    $chunkUsers = array_chunk($userids, 200);
                    foreach($chunkUsers as $cKey => $cVal){
                        $wWhere = [
                            'user_id'       => ['in',$cVal],
                            'result'        => ['in', ['1', '0.5', '-1', '-0.5']],
                            'play_type'     => $wh['play_type'],
                            'create_time'   => ["between", [$wBeginTime, $wEndTime]],
                        ];
                        $cRes = $model->field('user_id, GROUP_CONCAT(result) as result')->where($wWhere)->group('user_id')->select();
                        $userGamble = array_merge($userGamble, $cRes);
                    }
                    $userWeekGamble = array_column($userGamble, 'result', 'user_id');

                    //周胜率排序
                    foreach ($FrontUser as $k1 => $v1)
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
                        $weekSort[]     = $weekPercnet;
                    }

                    array_multisort( $weekSort, SORT_DESC , $FrontUser);
                    $data = array_slice($FrontUser, $start, $limit);

                    foreach($data as $k3 => $v3){
                        $wh['user_id']  = $v3['user_id'];
                        $new_gamble = D('GambleHall')->getGambleList($v3['user_id'], $wh['play_type'], $page = 1, $gamble_id = 0, $gambleType, $gameType);
                        $data[$k3]['face']         = frontUserFace($v3['face']);
                        $data[$k3]['gamble']       = $new_gamble[0] ? [$new_gamble[0]] : [];
                    }
                }
                S($cache_key, $data, 600);
            }
        }

        $this->ajaxReturn(['lists' => $data?:[]]);
    }

    /**
     * 聊天室点赞
     */
    public function chatLike(){
        $game_type  = $this->param['game_type']?:1;
        $game_id    = $this->param['game_id'];
        $choice     = $this->param['choice'];
        $model = $game_type == 1?M('GameFbinfo'):M('GameBkinfo');
        if(!$game_id)
            $this->ajaxReturn(101);

        $field      = $choice == '-1'?'away_up':'home_up';
        $userInfo   = getUserToken($this->param['userToken']);
        $key        = 'user_chat_like'.$game_type.$game_id.$userInfo['userid'];
        $hisLike    = S($key);

        $likes = $model->field('home_up,away_up')->where(['game_id' => $game_id])->find();
        if($likes['home_up'] < 2000 || $likes['away_up'] < 2000 ){
            $likes['home_up'] = (string)rand(2000,3000);
            $likes['away_up'] = (string)rand(2000,3000);
            $model->where(['game_id' => $game_id])->save(['home_up'=>$likes['home_up'],'away_up' => $likes['away_up']]);
        }

        $likes['choice'] = !$hisLike ? '0' : ($hisLike == 'away_up'?'-1':'1');

        if($choice){//点赞
            if(!isset($userInfo['userid']))
                $this->ajaxReturn(1001);

            if($hisLike)
                $this->ajaxReturn(3013);

            $res = $model->field('home_up,away_up')->where(['game_id' => $game_id])->setInc($field);
            if(!$res)
                $this->ajaxReturn(3014);

            $likes[$field] = (string)($likes[$field]+1);
            S($key,$field,24*3600*7);
            $likes['choice'] = $choice;
        }


        $this->ajaxReturn($likes?:[]);
    }
}

 ?>