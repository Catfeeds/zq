<?php
set_time_limit(0);//0表示不限时
//@ini_set('implicit_flush',1);
//ob_implicit_flush(1);
//@ob_end_clean();
/**
 * 结算程序
 *
 * @author dengwj <406516482@qq.com>
 *
 * @since  2017-10-31
 */
use Think\Controller;
class SettleController extends CommonController {

    //足球竞猜结算
    public function runFbResult()
    {
        $create_time = NOW_TIME - 86400*3;
        $gamble = M('Gamble g')
                ->master(true)
                ->join("LEFT JOIN qc_game_fbinfo gf on gf.game_id = g.game_id")
                ->field("g.id,g.home_team_name,g.away_team_name,g.user_id,g.game_id,g.play_type,g.chose_side,g.odds,g.handcp,g.vote_point,g.result,g.quiz_number,g.income,g.tradeCoin,g.platform,gf.game_state,gf.game_id")
                ->where("g.result in(0,-10,-11,-12,-13,-14) AND g.create_time > {$create_time} AND gf.game_state in(-1,4,5)")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        // echo M('Gamble g')->_sql();
        // dump($gamble);
        // die;
        $num = $countnum = 0;
        if($gamble)
        {
            //从mongo获取赛事信息
            $gameIdArr = array_unique(array_column($gamble,'game_id'));
            $DataService = new \Common\Services\DataService();
            $mongoGameArr = $DataService->getMongoGameData($gameIdArr);

            foreach ($gamble as $v)
            {
                //mongo赛事信息
                $mongoGame = $mongoGameArr[$v['game_id']];
                if(empty($mongoGame)){
                    continue;
                }
                //比赛状态判断
                $game_state = $mongoGame['game_state'];
                if($game_state == 4 || $game_state == 5){
                    //加时和点球算完场
                    $game_state = -1;
                }
                if ($game_state != -1)
                {
                    if($v['result'] != $game_state)
                    {
                        M('Gamble')->where(['id'=>$v['id']])->save(['result'=>$game_state]);
                        $num++;
                    }
                    continue;
                }
                $score      = $mongoGame['score'];
                $half_score = $mongoGame['half_score'];
                $result = getTheWin($score,$v['play_type'],$v['handcp'],$v['chose_side']);
   
                //这里读取主数据库
                $userInfo  = M('FrontUser')->master(true)->field(['coin','unable_coin','point','gamble_num','fb_ten_gamble','bet_num','fb_ten_bet','fb_gamble_win','fb_bet_win'])->where(['id'=>$v['user_id']])->find();
                $point     = $userInfo['point'];

                if ($result == '1' || $result == '0.5')
                {
                    switch ($v['play_type'])
                    {
                        case '1':
                        case '-1':
                                $earnPoint = ceil($v['odds'] * $v['vote_point']);
                            break;
                        case '2':
                        case '-2':
                                $earnPoint = ceil($v['odds'] * $v['vote_point']) - 100;
                            break;
                    }
                }
                else
                {
                    $earnPoint = 0;
                }
                //推荐结算数据
                M('gamble')->where(['id'=>$v['id']])->save([
                    'score'      => $score,
                    'half_score' => $half_score,
                    'result'     => $result,
                    'earn_point' => $earnPoint
                ]);

                $home_name = explode(',',$v['home_team_name'])[0];
                $away_name = explode(',',$v['away_team_name'])[0];
                $saveArray = array();
                if ($earnPoint > 0)
                {
                    switch ($v['play_type']) {
                        case  '1':
                        case '-1': $gamebleType = '亚盘' ;break;
                        case  '2':
                        case '-2': $gamebleType = '竞彩' ;break;
                    }
                    $pointDesc = '足球'. $gamebleType . '推荐'."【{$home_name}VS{$away_name}】";
                    //增加积分记录数据
                    $UserLog[$v['user_id']]['point'] += $earnPoint;
                    $addPointArray[] = [
                        'user_id'     => $v['user_id'],
                        'log_time'    => time(),
                        'log_type'    => 1,
                        'change_num'  => $earnPoint,
                        'total_point' => $point + $UserLog[$v['user_id']]['point'],
                        'gamble_id'   => $v['id'],
                        'desc'        => $pointDesc,
                    ];
                    //更新用户积分
                    $saveArray['point'] = $earnPoint;
                }

                $gambleResult = $this->getFiveGamble($v);

                //更新用户近5中几,有5条才更新
                if($gambleResult['num'] >= 5)
                {
                    $saveArray[$gambleResult['five_gamble']] = $gambleResult['win'];
                }
                //更新用户近10中几,有10条才更新
                if($gambleResult['num'] >= 10)
                {
                    $saveArray[$gambleResult['ten_gamble']] = $gambleResult['tenWin'];
                }
                //更新用户当前连胜
                if($gambleResult['num'] > 0)
                {
                    $saveArray[$gambleResult['now_gamble']] = $gambleResult['nowWin'];
                }

                //待结算金币转为可提款金币
                if($v['quiz_number'] > 0 && $v['tradeCoin'] > 0 && $v['income'] > 0)
                {
                    //编辑描述
                    $AccDesc = '';
                    $AccDesc .= '您推荐的【';
                    $AccDesc .= in_array($v['play_type'], ['2', '-2']) ? '竞彩-':'亚盘-';
                    $AccDesc .= C('fb_play_type')[$v['play_type']];
                    $AccDesc .= " {$home_name}VS{$away_name}】";
                    $AccDesc .= "被{$v['quiz_number']}人查看";
                    $saveArray['coin'] = $v['income'];
                    //添加金币明细
                    $UserLog[$v['user_id']]['coin'] += $v['income'];
                    $addAccountArray[] = [
                        'user_id'    =>  $v['user_id'],
                        'log_time'   =>  time(),
                        'log_type'   =>  4,
                        'game_type'  =>  1,
                        'log_status' =>  1,
                        'change_num' =>  $v['income'],
                        'total_coin' =>  $userInfo['coin']+$userInfo['unable_coin']+$UserLog[$v['user_id']]['coin'],
                        'gamble_id'  =>  $v['id'],
                        'desc'       =>  $AccDesc,
                        'platform'   =>  $v['platform'],
                        'operation_time' => time()
                    ];
                }
                if(!empty($saveArray))
                {
                    //用户信息修改数据
                    $saveUserArray[$v['user_id']][] = $saveArray;
                }
                $num++;
            }
            //开始事务
            $model = M();
            $model->startTrans();
            if(!empty($addPointArray)){
                $rs2 = M('pointLog')->addAll($addPointArray);
            }else{
                $rs2 = true;
            }
            if(!empty($addAccountArray)){
                $rs3 = M('AccountLog')->addAll($addAccountArray);
            }else{
                $rs3 = true;
            }
            if(!empty($saveUserArray)){
                $UserSql = $this->saveUserSql($saveUserArray);
                $rs4 = $model->execute($UserSql);
            }else{
                $rs4 = true;
            }
            if($rs2 && $rs3 && $rs4){
                $model->commit();
            }else{
                $model->rollback();
            }
        }
        $num2 = $this->runGambleReset() ? :0;
        echo '更新了 <b style="color:red;font-size:15px;">'.$num.'</b> 条竞猜数据，<b style="color:red;font-size:15px;">'.$num2.'</b> 条重置竞猜数据<br/>';
    }

    //结算重置数据表竞猜
    public function runGambleReset()
    {
        $create_time = NOW_TIME - 86400*3;
        $gtime       = NOW_TIME - 86400;

        $GambleReset = M('GambleReset g')
                ->join("LEFT JOIN qc_game_fbinfo gf on gf.game_id = g.game_id")
                ->field("g.id,gf.game_state,g.user_id,g.game_id,g.play_type,g.chose_side,g.odds,g.handcp,g.vote_point,g.result,gf.score,gf.half_score")
                ->where("g.result in(0,-10,-11,-12,-13,-14) AND g.create_time > ".$create_time." AND gf.gtime > ".$time." AND gf.game_state < 0")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        $num = 0;
        if($GambleReset){
            foreach ($GambleReset as $v)
            {
                if ($v['game_state'] != -1)
                {
                    if($v['result'] != $v['game_state'])
                    {
                        M('GambleReset')->where(['id'=>$v['id']])->save(['result'=>$v['game_state']]);
                        $num++;
                    }
                    continue;
                }

                $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);

                //更新竞猜记录表的比分、状态、赢取的积分
                $saveGambleArray[] = '('.implode(',', [
                    $v['id'],
                    "'".$v['score']."'",
                    "'".$v['half_score']."'",
                    "'".$result."'"
                ]).')';

                $num++;
            }
            $GambleSql = $this->replaceSql('qc_gamble_reset',['id','score','half_score','result'],$saveGambleArray);
            $rs = M()->execute($GambleSql);
        }
        return $num;
    }

    //足球竞猜结果修复
    public function repairFbResult()
    {
        $gtime  = NOW_TIME - 86400;
        $num = 0;
        $gamble = M('Gamble g')
                ->join("LEFT JOIN qc_game_fbinfo gf on gf.game_id = g.game_id")
                ->field("g.id,g.home_team_name,g.away_team_name,g.user_id,g.game_id,g.play_type,g.chose_side,g.odds,g.handcp,g.vote_point,g.earn_point,g.result,gf.game_state,gf.score,gf.half_score")
                ->where("g.result in(1,-1,2,0.5,-0.5) AND gf.gtime > ".$gtime." AND gf.gtime < ".NOW_TIME." AND gf.game_state = -1 AND g.score <> gf.score")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        // echo M('Gamble g')->_sql();
        // dump($gamble);
        // die;
        if($gamble){
            foreach ($gamble as $k => $v)
            {
                //实际结果
                $result = getTheWin($v['score'],$v['play_type'],$v['handcp'],$v['chose_side']);
                if ($result == '1' || $result == '0.5')
                {
                    switch ($v['play_type'])
                    {
                        case '1':
                        case '-1':
                                $earnPoint = ceil($v['odds'] * $v['vote_point']);
                            break;
                        case '2':
                        case '-2':
                                $earnPoint = ceil($v['odds'] * $v['vote_point']) - 100;
                            break;
                    }
                }
                else
                {
                    $earnPoint = 0;
                }
                //更新竞猜记录表的比分、状态、赢取的积分
                $saveGambleArray[] = '('.implode(',', [
                    $v['id'],
                    "'".$v['score']."'",
                    "'".$v['half_score']."'",
                    "'".$result."'",
                    $earnPoint
                ]).')';

                //结果不一样添加或扣除积分
                if($v['result'] != $result)
                {
                    //这里读取主数据库获取现有积分
                    $point  = M('FrontUser')->master(true)->where(['id'=>$v['user_id']])->getField('point');
                    switch ($v['play_type']) {
                        case  '1':
                        case '-1': $gamebleType = '亚盘' ;break;
                        case  '2':
                        case '-2': $gamebleType = '竞彩' ;break;
                    }
                    $home_name  = explode(',',$v['home_team_name'])[0];
                    $away_name  = explode(',',$v['away_team_name'])[0];
                    $pointDesc = '足球'. $gamebleType . '推荐'."【{$home_name}VS{$away_name}】";
                    if(in_array($v['result'], ['-1','-0.5','2']) && in_array($result, ['1','0.5'])) 
                    {
                        //输平 变 赢,更新用户积分
                        //增加积分记录
                        $UserLog[$v['user_id']]['point'] += $earnPoint;
                        $addPointArray[] = [
                            'user_id'     => $v['user_id'],
                            'log_time'    => time(),
                            'log_type'    => 18,
                            'change_num'  => $earnPoint,
                            'total_point' => $point + $UserLog[$v['user_id']]['point'],
                            'gamble_id'   => $v['id'],
                            'desc'        => $pointDesc
                        ];
                    }
                    elseif (in_array($v['result'], ['1','0.5']) && in_array($result, ['-1','-0.5','2'])) 
                    {
                        //赢 变 输平,减去原来赢的积分
                        $fellPoint = $v['earn_point'];
                        //增加积分记录
                        $UserLog[$v['user_id']]['point'] -= $fellPoint;
                        $addPointArray[] = [
                            'user_id'     => $v['user_id'],
                            'log_time'    => time(),
                            'log_type'    => 19,
                            'change_num'  => $fellPoint,
                            'total_point' => $point + $UserLog[$v['user_id']]['point'],
                            'gamble_id'   => $v['id'],
                            'desc'        => $pointDesc
                        ];
                    }
                }
                $num++;
            }

            //开始事务
            $model = M();
            $model->startTrans();
            if(!empty($saveGambleArray)){
                $GambleSql = $this->replaceSql('qc_gamble',['id','score','half_score','result','earn_point'],$saveGambleArray);
                $rs1 = $model->execute($GambleSql);
            }else{
                $rs1 = true;
            }
            if(!empty($addPointArray)){
                $rs2 = M('pointLog')->addAll($addPointArray);
            }else{
                $rs2 = true;
            }
            if(!empty($UserLog)){
                $pointSql = "UPDATE qc_front_user SET `point` = CASE id ";
                foreach ($UserLog as $k => $v) 
                {
                    if($v['point'] != 0)
                        $pointSql  .= sprintf("WHEN %d THEN %s ", $k, $v['point'] > 0 ? 'point+'.$v['point'] : 'point'.$v['point']);
                    else
                        unset($UserLog[$k]);
                }
                $ids = implode(',', array_keys($UserLog));
                if(!empty($ids)){
                    $pointSql .= "END WHERE id IN ($ids)";
                    $rs3 = $model->execute($pointSql);
                }else{
                    $rs3 = true;
                }
            }else{
                $rs3 = true;
            }
            if($rs1 && $rs2 && $rs3){
                $model->commit();
            }else{
                $model->rollback();
            }
        }
        echo '修复了 <b style="color:red;font-size:15px;">'.$num.'</b> 条足球竞猜数据<br/>';
    }

    //非正常结算竞猜被查看返还与扣除金币
    public function returnQuizCoin($gameType=1)
    {
        $create_time = NOW_TIME - 86400*3;
        $gtime  = NOW_TIME - 86400;
        $gambleModel = $gameType == 1 ? M('gamble') : M('gamblebk');
        if($gameType == 1) //足球
        {
            $gamble = M('Gamble g')
                ->join("LEFT JOIN qc_game_fbinfo gf on gf.game_id = g.game_id")
                ->field("g.id,g.user_id,g.home_team_name,g.away_team_name,g.tradeCoin,g.quiz_number,gf.game_state")
                ->where("g.result not in(1,-1,2,0.5,-0.5) AND g.is_back = 0 AND gf.gtime > {$create_time} AND gf.gtime < {$gtime}")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        }
        elseif ($gameType == 2) //篮球
        {
            $gamble = M('Gamblebk g')
                ->join("LEFT JOIN qc_game_bkinfo gf on gf.game_id = g.game_id")
                ->field("g.id,g.user_id,g.home_team_name,g.away_team_name,g.tradeCoin,g.quiz_number,gf.game_state")
                ->where("g.result not in(1,-1,2,0.5,-0.5) AND g.is_back = 0 AND gf.gtime > {$create_time} AND gf.gtime < {$gtime}")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        }
        // dump($gamble);
        // die;
        if(!$gamble){
            echo "没有非正常结算的竞猜<br/>";
            return;
        }

        foreach ($gamble as $k => $v)
        {
            if($v['tradeCoin'] > 0 && $v['quiz_number'] > 0){
                //需要退回金币的推荐
                $gambleIdArr[] = $v['id'];
            }
            $qx_gamble_id[] = $v['id'];
        }
        $num = 0;
        if($gambleIdArr){
            //返回金币,找出查看记录
            $quizLog = M('quizLog')->where(['gamble_id'=>['in',$gambleIdArr],'game_type'=>$gameType])->select();
            foreach ($quizLog as $k => $v) {
                foreach ($gamble as $kk => $vv) {
                    //获取球队名状态
                    if($v['gamble_id'] == $vv['id']){
                        $home_team_name = $vv['home_team_name'];
                        $away_team_name = $vv['away_team_name'];
                        $game_state     = $vv['game_state'];
                    }
                }
                $game_state = C('game_state')[$game_state]; //比赛状态

                //查看者现有金币
                $userInfo  = M('FrontUser')->master(true)->field("id,coin+unable_coin as coin")->where(['id'=>$v['user_id']])->find();

                if($v['ticket_id'] == 0)
                {
                    //退回查看者金币
                    $UserLog[$v['user_id']]['coin'] += $v['coin'];
                    //添加返还查看金币账户明细
                    $addAccountArray[] = [
                                'user_id'        => $v['user_id'],
                                'log_type'       => 11,
                                'log_status'     => 1,
                                'log_time'       => NOW_TIME,
                                'change_num'     => $v['coin'],
                                'total_coin'     => $userInfo['coin'] + $UserLog[$v['user_id']]['coin'],
                                'desc'           => "您查看的推荐【".switchName(0,$home_team_name)."VS".switchName(0,$away_team_name)."】赛事被".$game_state."，返还{$v['coin']}金币",
                                'platform'       => 1,
                                'gamble_id'      => $v['gamble_id'],
                                'operation_time' => time()
                            ];

                    $msgTitle = '金币返还通知';
                    $msgLast  = "返还{$v['coin']}金币，详情请查看账户明细。";
                }else{
                    //退回体验券
                    $rs = M('ticketLog')->where(['id'=>$v['ticket_id']])->save(['is_use'=>0,'use_time'=>0]);
                    $rs2 = true;
                    $msgTitle = '体验券返还通知';
                    $msgLast  = "返还{$v['coin']}金币体验券一张，详情请查看我的优惠券。";
                }

                //发送金币返还通知
                sendMsg($v['user_id'], $msgTitle, "您查看的推荐【".switchName(0,$home_team_name)." VS ".switchName(0,$away_team_name)."】赛事被".$game_state."，".$msgLast);

                //发送金币扣除通知
                sendMsg($v['user_id'],'金币扣除通知',"您推荐的比赛【".switchName(0,$home_team_name)." VS ".switchName(0,$away_team_name)."】赛事被".$game_state."，进入待结算的{$v['income']}金币收入将返还至查看人账户，感谢你的参与。");

                $num++;
            }
            $model = M();
            $model->startTrans(); //开启事务

            $coinSql = "UPDATE qc_front_user SET `unable_coin` = CASE id ";
            foreach ($UserLog as $k => $v) 
            {
                $coinSql  .= sprintf("WHEN %d THEN %s ", $k, 'unable_coin+'.$v['coin']);
            }
            $ids = implode(',', array_keys($UserLog));
            $coinSql .= "END WHERE id IN ($ids)";
            $rs1 = $model->execute($coinSql);

            $rs2 = M('AccountLog')->addAll($addAccountArray);

            if($rs1 && $rs2){
                $model->commit();
            }else{
                $model->rollback();
            }
        }
        //改为取消，已返还
        $result = $gambleModel->where(['id'=>['in',$qx_gamble_id]])->save(['result'=> '-10','is_back'=>1]);
        echo '成功处理了 <b style="color:red;font-size:15px;">'.count($gamble).'</b> 条推荐数据,返还了 <b style="color:red;font-size:15px;">'.$num.'</b> 条数据查看';
        die;
    }

    //竞猜结果修复 与 非正常结算返还与扣除金币
    public function totalRepair()
    {
        $this->repairFbResult();
        $this->repairBkResult();
        $this->returnQuizCoin(1);
        $this->returnQuizCoin(2);
        exit("执行成功！");
    }

    //篮球竞猜结算
    public function runBkResult()
    {
        $create_time = NOW_TIME - 86400*3;
        $gamblebk = M('Gamblebk g')
                ->master(true)
                ->join("LEFT JOIN qc_game_bkinfo gb on gb.game_id = g.game_id")
                ->field("g.id,g.home_team_name,g.away_team_name,g.user_id,g.game_id,g.play_type,g.chose_side,g.odds,g.handcp,g.platform,g.vote_point,g.result,g.quiz_number,g.tradeCoin,g.income,gb.game_state,gb.score,gb.half_score")
                ->where("g.result in(0,-10,-2,-12,-13,-14,-5) AND g.create_time > {$create_time} AND gb.game_state = -1")
                ->group("g.id")
                ->order("g.id asc")
                ->select();
        // dump($gamblebk);
        // die;
        $num = 0;
        if($gamblebk){

            //从mongo获取赛事信息
            $gameIdArr = array_unique(array_column($gamblebk,'game_id'));
            $DataService = new \Common\Services\DataService();
            $mongoGameArr = $DataService->getMongoGameData($gameIdArr,2);

            foreach ($gamblebk as $v)
            {
                //mongo赛事信息
                $mongoGame = $mongoGameArr[$v['game_id']];
                if(empty($mongoGame)){
                    continue;
                }
                $game_state = $mongoGame['game_state'];
                if ($game_state != -1)
                {
                    if($v['result'] != $game_state)
                    {
                        M('Gamblebk')->where(['id'=>$v['id']])->save(['result'=>$game_state]);
                        $num++;
                    }
                    continue;
                }
                $score      = $mongoGame['score'];
                $half_score = $mongoGame['half_score'];
                $result = getTheWinBk($score,$half_score,$v['play_type'],$v['handcp'],$v['chose_side']);

                //这里读取主数据库
                $userInfo  = M('FrontUser')->master(true)->field(['coin','unable_coin','point','bk_gamble_num','bk_ten_gamble'])->where(['id'=>$v['user_id']])->find();
                $point     = $userInfo['point'];

                if ($result == '1')
                {
                    $earnPoint = ceil($v['odds'] * $v['vote_point']);
                }
                else
                {
                    $earnPoint = 0;
                }
                
                //推荐结算数据
                M('Gamblebk')->where(['id'=>$v['id']])->save([
                    'score'      => $score,
                    'half_score' => $half_score,
                    'result'     => $result,
                    'earn_point' => $earnPoint
                ]);
                $saveArray = array();
                if ($earnPoint > 0)
                {
                    switch ($v['play_type']) {
                        case '1' : $playType = '全场让球'; break;
                        case '-1': $playType = '全场大小'; break;
                        case '2' : $playType = '半场让球'; break;
                        case '-2': $playType = '半场大小'; break;
                    }

                    $home_name  = explode(',',$v['home_team_name'])[0];
                    $away_name  = explode(',',$v['away_team_name'])[0];
                    $pointDesc = '篮球'. $playType . '推荐'."【{$home_name}VS{$away_name}】";
                    //增加积分记录
                    $UserLog[$v['user_id']]['point'] += $earnPoint;
                    $addPointArray[] = [
                        'user_id'     => $v['user_id'],
                        'log_time'    => time(),
                        'log_type'    => 1,
                        'change_num'  => $earnPoint,
                        'total_point' => $point + $UserLog[$v['user_id']]['point'],
                        'gamble_id'   => $v['id'],
                        'desc'        => $pointDesc,
                    ];
                    //更新用户积分
                    $saveArray['point'] = $earnPoint;
                }
                
                $gambleResult = $this->getFiveGamblebk($v);

                //更新用户近5中几,有5条才更新
                if($gambleResult['num'] >= 5)
                {
                    $saveArray['bk_gamble_num'] = $gambleResult['win'];
                }
                //更新用户近10中几,有10条才更新
                if($gambleResult['num'] >= 10)
                {
                    $saveArray['bk_ten_gamble'] = $gambleResult['tenWin'];
                }
                //更新用户当前连胜
                if($gambleResult['num'] > 0)
                {
                    $saveArray['bk_gamble_win'] = $gambleResult['nowWin'];
                }

                //待结算金币转为可提款金币
                if($v['quiz_number'] > 0 && $v['tradeCoin'] > 0 && $v['income'] > 0)
                {
                    //编辑描述
                    $AccDesc = '';
                    $AccDesc .= '您推荐的篮球【'.C('bk_play_type')[$v['play_type']];
                    $AccDesc .= " - {$home_name}VS{$away_name}】";
                    $AccDesc .= "被{$v['quiz_number']}人查看";
                    $saveArray['coin'] = $v['income'];
                    //添加金币明细
                    $UserLog[$v['user_id']]['coin'] += $v['income'];
                    $addAccountArray[] = [
                        'user_id'    =>  $v['user_id'],
                        'log_time'   =>  time(),
                        'log_type'   =>  4,
                        'game_type'  =>  2,
                        'log_status' =>  1,
                        'change_num' =>  $v['income'],
                        'total_coin' =>  $userInfo['coin']+$userInfo['unable_coin']+$UserLog[$v['user_id']]['coin'],
                        'gamble_id'  =>  $v['id'],
                        'desc'       =>  $AccDesc,
                        'platform'   =>  $v['platform'],
                        'operation_time' => time()
                    ];
                }
                if(!empty($saveArray))
                {
                    //用户信息修改数据
                    $saveUserArray[$v['user_id']][] = $saveArray;
                }
                $num++;
            }
            //开始事务
            $model = M();
            $model->startTrans();
            if(!empty($addPointArray)){
                $rs2 = M('pointLog')->addAll($addPointArray);
            }else{
                $rs2 = true;
            }
            if(!empty($addAccountArray)){
                $rs3 = M('AccountLog')->addAll($addAccountArray);
            }else{
                $rs3 = true;
            }
            if(!empty($saveUserArray)){
                $UserSql = $this->saveUserSql($saveUserArray,2);
                $rs4 = $model->execute($UserSql);
            }else{
                $rs4 = true;
            }
            if($rs2 && $rs3 && $rs4){
                $model->commit();
            }else{
                $model->rollback();
            }
        }
        
        echo '更新了 <b style="color:red;font-size:15px;">'.$num.'</b> 条竞猜数据<br/>';
    }

    //蓝球竞猜结果修复
    public function repairBkResult()
    {
        $gtime  = NOW_TIME - 86400;
        $num = 0;
        $gamble = M('Gamblebk g')
                ->join("LEFT JOIN qc_game_bkinfo gf on gf.game_id = g.game_id")
                ->field("g.id,g.home_team_name,g.away_team_name,g.user_id,g.game_id,g.play_type,g.chose_side,g.odds,g.handcp,g.vote_point,g.earn_point,g.result,gf.game_state,gf.score,gf.half_score")
                ->where("g.result in(1,-1,2) AND gf.gtime > ".$gtime." AND gf.gtime < ".NOW_TIME." AND gf.game_state = -1 AND g.score <> gf.score")
                ->group("g.id")
                ->order("g.id asc")
                ->select();

        // dump($gamble);
        // echo M('Gamblebk g')->_sql();
        // die;
        $num = 0;

        if($gamble){
            foreach ($gamble as $k => $v)
            {
                //实际结果
                $result = getTheWinBk($v['score'],$v['half_score'],$v['play_type'],$v['handcp'],$v['chose_side']);

                $earnPoint = $result == '1' ? ceil($v['odds'] * $v['vote_point']) : 0;

                //更新竞猜记录表的比分、状态、赢取的积分
                $saveGambleArray[] = '('.implode(',', [
                    $v['id'],
                    "'".$v['score']."'",
                    "'".$v['half_score']."'",
                    "'".$result."'",
                    $earnPoint
                ]).')';
                
                //结果不一样添加或扣除积分
                if($v['result'] != $result)
                {
                    switch ($v['play_type']) {
                        case '1' : $playType = '全场让球'; break;
                        case '-1': $playType = '全场大小'; break;
                        case '2' : $playType = '半场让球'; break;
                        case '-2': $playType = '半场大小'; break;
                    }

                    //这里读取主数据库
                    $point  = M('FrontUser')->master(true)->where(['id'=>$v['user_id']])->getField('point');

                    $home_name  = explode(',',$v['home_team_name'])[0];
                    $away_name  = explode(',',$v['away_team_name'])[0];
                    $pointDesc = '篮球'. $playType . '推荐'."【{$home_name}VS{$away_name}】";

                    if(in_array($v['result'], ['-1','2']) && $result == '1') //输平 变 赢
                    {
                        //更新用户积分
                        $UserLog[$v['user_id']]['point'] += $earnPoint;

                        //增加积分记 录
                        $addPointArray[] = [
                            'user_id'     => $v['user_id'],
                            'log_time'    => time(),
                            'log_type'    => 18,
                            'change_num'  => $earnPoint,
                            'total_point' => $point + $UserLog[$v['user_id']]['point'],
                            'gamble_id'   => $v['id'],
                            'desc'        => $pointDesc
                        ];
                    }
                    elseif ($v['result'] == '1' && in_array($result, ['-1','2'])) //赢 变 输平
                    {
                        //减去原来赢的积分
                        $fellPoint = $v['earn_point'];
                        $UserLog[$v['user_id']]['point'] -= $fellPoint;

                        //增加积分记录
                        $addPointArray[] = [
                            'user_id'     => $v['user_id'],
                            'log_time'    => time(),
                            'log_type'    => 19,
                            'change_num'  => $fellPoint,
                            'total_point' => $point + $UserLog[$v['user_id']]['point'],
                            'gamble_id'   => $v['id'],
                            'desc'        => $pointDesc
                        ];
                    }
                }
                $num++;
            }

            //开始事务
            $model = M();
            $model->startTrans();
            if(!empty($saveGambleArray)){
                $GambleSql = $this->replaceSql('qc_gamblebk',['id','score','half_score','result','earn_point'],$saveGambleArray);
                $rs1 = $model->execute($GambleSql);
            }else{
                $rs1 = true;
            }
            if(!empty($addPointArray)){
                $rs2 = M('pointLog')->addAll($addPointArray);
            }else{
                $rs2 = true;
            }
            if(!empty($UserLog)){
                $pointSql = "UPDATE qc_front_user SET `point` = CASE id ";
                foreach ($UserLog as $k => $v) 
                {
                    if($v['point'] != 0)
                        $pointSql  .= sprintf("WHEN %d THEN %s ", $k, $v['point'] > 0 ? 'point+'.$v['point'] : 'point'.$v['point']);
                    else
                        unset($UserLog[$k]);
                }
                $ids = implode(',', array_keys($UserLog));
                if(!empty($ids)){
                    $pointSql .= "END WHERE id IN ($ids)";
                    $rs3 = $model->execute($pointSql);
                }else{
                    $rs3 = true;
                }
            }else{
                $rs3 = true;
            }
            if($rs1 && $rs2 && $rs3){
                $model->commit();
            }else{
                $model->rollback();
            }
        }
        echo '修复了 <b style="color:red;font-size:15px;">'.$num.'</b> 条蓝球竞猜数据<br/>';
    }

    //拼装推荐记录数据sql
    public function replaceSql($table,$fieldArr,$data){
        $value = implode(',', $data);
        $field = implode(',', $fieldArr);
        foreach ($fieldArr as $k => $v) {
            $fieldStrArr[] = "{$v}=VALUES({$v})";
        }
        $fieldStr = implode(',', $fieldStrArr);
        $sql = "INSERT INTO {$table} ({$field})
                VALUES {$value}
                ON DUPLICATE KEY UPDATE {$fieldStr}";
        return $sql;
    }

    /**
     * 拼装用户修改数据sql
     */
    public function saveUserSql($saveUserArray,$game_type=1){
        $ids = implode(',', array_keys($saveUserArray));
        $sql = "UPDATE qc_front_user SET ";
        foreach ($saveUserArray as $k => $v) 
        {
            $pointCount = $coinCount = 0;
            foreach ($v as $kk => $vv) 
            {
                $pointCount += $vv['point'];
                $coinCount  += $vv['coin'];
                if($vv['gamble_num'])    $gamble_num = $vv['gamble_num'];
                if($vv['fb_ten_gamble']) $fb_ten_gamble = $vv['fb_ten_gamble'];
                if($vv['bet_num'])       $bet_num = $vv['bet_num'];
                if($vv['fb_ten_bet'])    $fb_ten_bet = $vv['fb_ten_bet'];
                if($vv['bk_gamble_num']) $bk_gamble_num = $vv['bk_gamble_num'];
                if($vv['bk_ten_gamble']) $bk_ten_gamble = $vv['bk_ten_gamble'];
                if($vv['fb_gamble_win']) $fb_gamble_win = $vv['fb_gamble_win'];
                if($vv['fb_bet_win'])    $fb_bet_win = $vv['fb_bet_win'];
                if($vv['bk_gamble_win']) $bk_gamble_win = $vv['bk_gamble_win'];
            }
            $pointSql       .= sprintf("WHEN %d THEN %s ", $k, $pointCount > 0 ? 'point+'.$pointCount : 'point');//积分
            $coinSql        .= sprintf("WHEN %d THEN %s ", $k, $coinCount > 0 ? 'coin+'.$coinCount : 'coin');    //金币
            $gambleSql      .= sprintf("WHEN %d THEN %s ", $k, $gamble_num ? : 'gamble_num');                    //足球亚盘5中几
            $tenGambleSql   .= sprintf("WHEN %d THEN %s ", $k, $fb_ten_gamble ? : 'fb_ten_gamble');              //足球亚盘10中几
            $betSql         .= sprintf("WHEN %d THEN %s ", $k, $bet_num ? : 'bet_num');                          //足球竞彩5中几
            $tenBetSql      .= sprintf("WHEN %d THEN %s ", $k, $fb_ten_bet ? : 'fb_ten_bet');                    //足球竞彩10中几
            $gamblebkSql    .= sprintf("WHEN %d THEN %s ", $k, $bk_gamble_num ? : 'bk_gamble_num');              //篮球5中几
            $tenGamblebkSql .= sprintf("WHEN %d THEN %s ", $k, $bk_ten_gamble ? : 'bk_ten_gamble');              //篮球10中几
            $nowGambleSql   .= sprintf("WHEN %d THEN %s ", $k, $fb_gamble_win ? : 'fb_gamble_win');              //足球亚盘当前连胜
            $nowBetSql      .= sprintf("WHEN %d THEN %s ", $k, $fb_bet_win ? : 'fb_bet_win');                    //足球竞彩当前连胜
            $nowGamblebkSql .= sprintf("WHEN %d THEN %s ", $k, $bk_gamble_win ? : 'bk_gamble_win');              //篮球当前连胜
        }

        $sql .= "`point` = CASE id ".$pointSql.'END,';
        $sql .= "`coin` = CASE id ".$coinSql.'END,';
        if($game_type == 1){
            $sql .= "`gamble_num` = CASE id ".$gambleSql.'END,';
            $sql .= "`fb_ten_gamble` = CASE id ".$tenGambleSql.'END,';
            $sql .= "`bet_num` = CASE id ".$betSql.'END,';
            $sql .= "`fb_ten_bet` = CASE id ".$tenBetSql.'END,';
            $sql .= "`fb_gamble_win` = CASE id ".$nowGambleSql.'END,';
            $sql .= "`fb_bet_win` = CASE id ".$nowBetSql;
        }else{
            $sql .= "`bk_gamble_num` = CASE id ".$gamblebkSql.'END,';
            $sql .= "`bk_ten_gamble` = CASE id ".$tenGamblebkSql.'END,';
            $sql .= "`bk_gamble_win` = CASE id ".$nowGamblebkSql;
        }
        
        $sql .= "END WHERE id IN ($ids)";
        return $sql;
    }

    //足球用户推荐数据结算（修复程序）
    public function userFbWin($type=1){
        $user = M('Gamble')->group("user_id")->getField('user_id',true);
        if($type == 1){
            $five_gamble  = 'gamble_num';
            $ten_gamble   = 'fb_ten_gamble';
            $now_gamble   = 'fb_gamble_win';
            $where['play_type'] = ['in',[1,-1]];
        }else{
            $five_gamble  = 'bet_num';
            $ten_gamble   = 'fb_ten_bet';
            $now_gamble   = 'fb_bet_win';
            $where['play_type'] = ['in',[2,-2]];
        }
        //过滤掉未出结果的
        $where['result']     = array('in',[1,0.5,-1,-0.5]);
        foreach ($user as $k => $v) {
            //近20场比赛结果
            $where['user_id'] = $v;
            $gambleArray = M('gamble')->master(true)->where($where)->order("id desc")->limit(20)->getField('result',true);
            $num = $tenWin = $nowWin = 0;
            //近5中几
            $fiveArray = array_slice($gambleArray, 0,5);
            foreach ($fiveArray as $kk => $vv) {
                if($vv == '1' || $vv == '0.5'){
                    $num++;
                }
            }
            //近10中几
            $tenArray  = array_slice($gambleArray, 0,10);
            foreach ($tenArray as $kkk => $vvv) {
                if($vvv == '1' || $vvv == '0.5'){
                    $tenWin++;
                }
            }
            //当前连胜
            foreach ($gambleArray as $kkkk => $vvvv) {
                if($vvvv == '1' || $vvvv == '0.5'){
                    $nowWin++;
                }
                if($vvvv == '-1' || $vvvv == '-0.5'){
                    break;
                }
            }
            $rs = M('FrontUser')->where(['id'=>$v])->save([
                $five_gamble => $num,
                $ten_gamble  => $tenWin,
                $now_gamble  => $nowWin
            ]);
            dump($rs);
            dump(M('FrontUser')->_sql());
            //die;
        }
    }

    //篮球用户推荐数据结算（修复程序）
    public function userBkWin($type=1){
        $user = M('gamblebk')->group("user_id")->getField('user_id',true);
        //过滤掉未出结果的
        $where['result'] = array('in',[1,-1]);
        foreach ($user as $k => $v) {
            $where['user_id']    = $v;
            //近5场比赛结果
            $gambleArray = M('gamblebk')->master(true)->where($where)->order("id desc")->limit(20)->getField('result',true);

            $fiveArray = array_slice($gambleArray, 0,5);
            $tenArray  = array_slice($gambleArray, 0,10);
            $num = $tenWin = $nowWin = 0;
            //近5中几
            $fiveArray = array_slice($gambleArray, 0,5);
            foreach ($fiveArray as $kk => $vv) {
                if($vv == '1' || $vv == '0.5'){
                    $num++;
                }
            }
            //近10中几
            $tenArray  = array_slice($gambleArray, 0,10);
            foreach ($tenArray as $kkk => $vvv) {
                if($vvv == '1' || $vvv == '0.5'){
                    $tenWin++;
                }
            }
            //当前连胜
            foreach ($gambleArray as $kkkk => $vvvv) {
                if($vvvv == '1' || $vvvv == '0.5'){
                    $nowWin++;
                }
                if($vvvv == '-1' || $vvvv == '-0.5'){
                    break;
                }
            }
            $rs = M('FrontUser')->where(['id'=>$v])->save([
                'bk_gamble_num'  => $num,
                'bk_ten_gamble'  => $tenWin,
                'bk_gamble_win'  => $nowWin
            ]);
            dump($rs);
            dump(M('FrontUser')->_sql());
            //die;
        }
    }


    //获取足球亚盘或竞彩近5中几/10中几 / 当前连胜
    public function getFiveGamble($gamble){
        $where['user_id']    = $gamble['user_id'];
        //过滤掉未出结果的
        $where['result']     = array('in',[1,0.5,-1,-0.5]);
        switch ($gamble['play_type'])
        {
            case '1':
            case '-1':
                    $where['play_type'] = ['in',[1,-1]];
                    $five_gamble  = 'gamble_num';
                    $ten_gamble   = 'fb_ten_gamble';
                    $now_gamble   = 'fb_gamble_win';
                break;
            case '2':
            case '-2':
                    $where['play_type'] = ['in',[2,-2]];
                    $five_gamble  = 'bet_num';
                    $ten_gamble   = 'fb_ten_bet';
                    $now_gamble   = 'fb_bet_win';
                break;
        }
        //近20场比赛结果
        $gambleArray = M('gamble')->master(true)->where($where)->order("id desc")->limit(20)->getField('result',true);
        $num = $tenWin = $nowWin = 0;
        //近5中几
        $fiveArray = array_slice($gambleArray, 0,5);
        foreach ($fiveArray as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $num++;
            }
        }
        //近10中几
        $tenArray  = array_slice($gambleArray, 0,10);
        foreach ($tenArray as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $tenWin++;
            }
        }
        //当前连胜
        foreach ($gambleArray as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $nowWin++;
            }
            if($v == '-1' || $v == '-0.5'){
                break;
            }
        }
        return [
            'num'         => count($gambleArray),//查询数量
            'win'         => $num,               //近5中几
            'tenWin'      => $tenWin,            //近10中几
            'nowWin'      => $nowWin,            //当前连胜
            'five_gamble' => $five_gamble,       //近5中几字段名
            'ten_gamble'  => $ten_gamble,        //近10中几字段名
            'now_gamble'  => $now_gamble,        //当前连胜字段名
        ];
    }

    //获取篮球近5中几/10中几/当前连胜
    public function getFiveGamblebk($gamble){
        $where['user_id']    = $gamble['user_id'];
        //过滤掉未出结果的
        $where['result']     = array('in',[1,-1]);
        //近5场比赛结果
        $gambleArray = M('gamblebk')->master(true)->where($where)->order("id desc")->limit(20)->getField('result',true);

        $fiveArray = array_slice($gambleArray, 0,5);
        $tenArray  = array_slice($gambleArray, 0,10);
        $num = $tenWin = $nowWin = 0;
        //近5中几
        $fiveArray = array_slice($gambleArray, 0,5);
        foreach ($fiveArray as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $num++;
            }
        }
        //近10中几
        $tenArray  = array_slice($gambleArray, 0,10);
        foreach ($tenArray as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $tenWin++;
            }
        }
        //当前连胜
        foreach ($gambleArray as $k => $v) {
            if($v == '1' || $v == '0.5'){
                $nowWin++;
            }
            if($v == '-1' || $v == '-0.5'){
                break;
            }
        }
        return ['num'=>count($gambleArray),'win'=>$num,'tenWin'=>$tenWin,'nowWin'=>$nowWin];
    }
}