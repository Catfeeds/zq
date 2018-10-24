<?php
/**
 +------------------------------------------------------------------------------
 * AppfbService   App服务类（1.2）
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqty.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>
 +------------------------------------------------------------------------------
*/
namespace Home\Services;

class AppfbService
{
    protected $data;

    public function __construct()
    {
        $this->getDataList();
    }

     /**
     * 当日即时赛事  (mongo数据，部分业务数据来自mysql)
     * @param  string   $content  源数据
     * @return array  当日即时赛事数据
     */
    public function fbtodayList($unionId,$subId ='',$platform = '')
    {
        $mongo = mongoService();

        //从缓存获取今日赛事列表
        if(!$baseRes = S('cache_fbtodayList_game'))
        {
            $dataService = new \Common\Services\DataService();
            $gameIdArr = $dataService->getGameTodayGids(1);
            $baseRes = $mongo->select('fb_game',['game_id'=>['$in'=>$gameIdArr]],
                [
                    'game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id',
                    'away_team_id','union_id','start_time','game_starttime','game_start_timestamp','game_half_datetime',
                    'red_card','yellow_card','corner','game_state','score','half_score','explain','remark','is_go','is_flash',
                    'is_sporttery','spottery_num','union_color','field_weather','goal_data','odds_data'
                ]
            );
            S('cache_fbtodayList_game',$baseRes,3);
        }
        
        $rData = [];
        if(!empty($baseRes))
        {
            $teamIds = $unionIdArr = $gameIdArr = [];
            foreach($baseRes as $k=> $v)
            {
                $teamIds[$v['home_team_id']] = $v['home_team_id'];
                $teamIds[$v['away_team_id']] = $v['away_team_id'];
                $gameIdArr[]  = (int)$v['game_id'];
                $unionIdArr[] = (int)$v['union_id'];
            }

            if($platform == 'robot')
            {
                $teamArr = [];
                /*
                 * todo
                 * 耦合度过高
                    $mongodb = mongoService();
                    $team_ids = array_keys($teamIds);
                    $ntRes = $mongodb->select('fb_team',['team_id'=>[$mongodb->cmd('in')=>$team_ids]],['team_id', 'team_name']);
                */
                $tRes = M('GameTeam')->field('team_id,short_team_name')->where(['team_id'=>['in',implode(',',$teamIds)]])->select();
                if(!empty($tRes))
                {
                    foreach($tRes as $k=> $v)
                    {
                        $teamArr[$v['team_id']] = $v['short_team_name'];
                    }
                }
            }

            if(!$gameArr = S('cache_fbtodayList_mysqlGame')){
                //获取mysql业务数据
                $GameFbinfo = M('GameFbinfo')
                    ->field("game_id,gtime,score,half_score,game_state,is_gamble,is_show,status,app_video,is_video")
                    ->where(['game_id'=>['in',$gameIdArr]])
                    ->select();

                foreach ($GameFbinfo as $k => $v) {
                    $gameArr[$v['game_id']] = $v;
                }
                S('cache_fbtodayList_mysqlGame',$gameArr,120);
            }

            //获取联盟数据
            if(!$unionArr = S('cache_fbtodayList_union')){
                $union = $mongo->select(
                    'fb_union',
                    ['union_id'=>['$in'=>$unionIdArr]],
                    ['union_id','union_name','country_id','level','union_or_cup','union_color']
                );
                foreach ($union as $k => $v) {
                    $unionArr[$v['union_id']] = $v;
                }
                S('cache_fbtodayList_union', $unionArr, 300);
            }
            
            //路珠判断
            if(!$lzMaps = S('cache_fbtodayList_luzhu')){
                foreach ($baseRes as $k => $v) {
                    $uData  = $unionArr[$v['union_id']];
                    $uLevel = isset($uData['level']) ? $uData['level'] : '3';
                    if(in_array($uLevel, [0, 1, 2])){
                        $lzTeamIds[] = $v['home_team_id'];
                        $lzTeamIds[] = $v['away_team_id'];
                    }
                }
                $lzMaps = $this->checkGoodRule($mongo, $lzTeamIds);
                S('cache_fbtodayList_luzhu', $lzMaps, 600);
            }

            if(!$game_lives = S('cache_fbtodayList_lives')){
                //是否有主播直播
                $game_lives = M('liveLog')
                    ->alias('Lg')
                    ->field('Lg.user_id,Lg.game_id')
                    ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
                    ->where(['Lg.status' => 1, 'LU.status' => 1, 'Lg.live_status' => 1,'Lg.game_id' => ['neq', '']])
                    ->getField('game_id', true);
                S('cache_fbtodayList_lives',$game_lives,60);
            }

            //判断Api版本
            $version = explode('Api', MODULE_NAME)[1];
            foreach($baseRes as $k=> $v)
            {
                if(stripos($v['home_team_name'][0],'测试') !== false ||
                    stripos($v['away_team_name'][0],'测试') !== false ||
                    stripos($v['home_team_name'][0],'test') !== false ||
                    stripos($v['away_team_name'][0],'test') !== false){
                    continue;
                }
                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(isset($mysqlGame['status']) && $mysqlGame['status'] != 1) continue;
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];
                $unionLevel = isset($unionData['level']) ? $unionData['level'] : '3';

                $val = [];
                $val[0] = (string)$v['game_id'];
                $val[1] = (string)$v['union_id'];
                $val[2] = $v['union_name'];
                $val[3] = !empty($unionData['union_color'])?$unionData['union_color']:'#aeba4b';
                $val[4] = (string)$unionLevel;
                //api520版本以下点球大战转为完场
                if($v['game_state'] == '5' && $version < 520){
                    $v['game_state'] = '-1';
                }
                $val[5] = (string)$v['game_state'];
                $game_start_timestamp = TellRealTime($v['start_time'],$v['game_start_timestamp'],$v['game_starttime'],$v['game_state']);
                $gameTime = explode('-',date('Ymd-H:i',$game_start_timestamp));
                $val[6] = $gameTime[0];
                $val[7] = $v['start_time'] ? :$gameTime[1];
                $val[8]  = isset($v['game_half_datetime']) ? date('YmdHis',strtotime($v['game_half_datetime'])) : '';
                $val[9]  = $v['home_team_name'];
                $val[10] = $v['away_team_name'];
                $val[11] = $v['home_team_rank'] ? pregUnionRank($v['home_team_rank']) :'';
                $val[12] = $v['away_team_rank'] ? pregUnionRank($v['away_team_rank']) :'';
                $score = explode('-',$v['score']);
                $val[13] = $v['game_state'] != 0 ? $score[0] : '';
                $val[14] = $v['game_state'] != 0 ? $score[1] : '';
                $half_score = explode('-',$v['half_score']);
                $val[15] = $v['game_state'] != 0 ? $half_score[0] : '';
                $val[16] = $v['game_state'] != 0 ? $half_score[1] : '';

                //赔率
                $fbOdds = $this->fbOdds(null,3,$v['odds_data']);
                $odds_data = isset($fbOdds[$v['game_id']]) ? $fbOdds[$v['game_id']] : '';
                $goalsArr  = D('GambleHall')->doFswOdds($odds_data,$v['game_state'],$v['goal_data']?:'');

                #全场亚盘大小即时赔率
                $val[17] = $goalsArr[0];   //主队亚盘即时赔率
                $val[18] = changeExp($goalsArr[1]);   //亚盘即时盘口
                $val[19] = $goalsArr[2];   //客队亚盘即时赔率
                $val[20] = $goalsArr[6];   //主队大小即时赔率
                $val[21] = changeExp($goalsArr[7]);   //大小即时盘口
                $val[22] = $goalsArr[8];   //客队大小即时赔率

                #红牌
                $red = explode('-',$v['red_card']);
                $val[23] = $v['game_state'] != 0 ? $red[0] : '0';
                $val[24] = $v['game_state'] != 0 ? $red[1] : '0';
                #黄牌
                $yellow  = explode('-',$v['yellow_card']);
                $val[25] = $v['game_state'] != 0 ? $yellow[0] : '0';
                $val[26] = $v['game_state'] != 0 ? $yellow[1] : '0';
                #角球
                $corner = explode('-',$v['corner']);
                $val[27] = $v['game_state'] != 0 ? $corner[0] : '0';
                $val[28] = $v['game_state'] != 0 ? $corner[1] : '0';
                #是否有视频直播
                $app_video = $mysqlGame['app_video'];
                if($v['game_state'] !=-1 && !empty($app_video) && $mysqlGame['is_video'] == 1)
                {
                    #video
                    if(!empty(json_decode($app_video)))
                        $val[29] = '1';
                    else
                        $val[29] = '0';
                }
                else
                {
                    $val[29] = '0';
                }
                if(!in_array(MODULE_NAME,['Api200','Api201','Api202']))
                {
                    #初盘
                    $chupan  = !empty($odds_data) ? explode("^", $odds_data[18]) : '';
                    $val[30] = !empty($chupan[0]) ? $chupan[0] : '';   //主队亚盘初盘赔率
                    $val[31] = !empty($chupan[1]) ? changeExp($chupan[1]) : '';   //亚盘初盘盘口
                    $val[32] = !empty($chupan[2]) ? $chupan[2] : '';   //客队亚盘初盘赔率
                    $val[33] = !empty($chupan[6]) ? $chupan[6] : '';   //主队大小初盘赔率
                    $val[34] = !empty($chupan[7]) ? changeExp($chupan[7]) : '';   //大小初盘盘口
                    $val[35] = !empty($chupan[8]) ? $chupan[8] : '';   //客队大小初盘赔率

                    //是否有动画直播
                    if($v['is_flash'] == 1 && in_array($v['game_state'],[0, 1, 2, 3, 4, 5]))
                    {
                        $val[36] = '1';
                    } else {
                        $val[36] = '0';
                    }
                    $val[37] = !empty($v['is_sporttery']) ? (string)$v['is_sporttery'] : '0';
                    $val[38] = !empty($v['spottery_num']) ? $v['spottery_num'] : '';
                }

                if(!in_array(MODULE_NAME,['Api','Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400']))
                {
                    //是否有视频直播，1是0否(机器人需求)
                    $val[39] = $mysqlGame['is_video'] ? : '0';
                    //是否有动画直播，1是0否(机器人需求)
                    if($v['is_flash'] == 1 && in_array($v['game_state'],[0,1,2,3,4,5]))
                    {
                        $val[40] = '1';
                    }else{
                        $val[40] = '0';
                    }
                }
                //机器人过滤队名,5.0开始用
                if($platform == 'robot')
                {
                    if(isset($teamArr[$v['home_team_id']]))
                        $val[41] = $teamArr[$v['home_team_id']] !== null?$teamArr[$v['home_team_id']]:'';
                    else
                        $val[41] = '';
                    if(isset($teamArr[$v['away_team_id']]))
                        $val[42] = $teamArr[$v['away_team_id']] !== null?$teamArr[$v['away_team_id']]:'';
                    else
                        $val[42] = '';
                }else{
                    $val[41] = '';
                    $val[42] = '';
                }
                //赛事状态文字数据

                $val[43] = isset($v['explain']) && $v['explain'] != '' ? $v['explain'] : '';
                //好路、路珠
                $val[44] = '0';
                if($lzMaps[$v['away_team_id']] == 2 || $lzMaps[$v['home_team_id']] == 2){
                    $val[44] = '2';
                }else{
                    $val[44] = $lzMaps[$v['away_team_id']] == 1 || $lzMaps[$v['home_team_id']] == 1 ? '1' : '0';
                }

                $val[45] = $game_lives && in_array($v['game_id'], $game_lives) ? '1' : '0';
                $sort1[] = $v['game_state'];
                $sort2[] = $game_start_timestamp;
                $sort3[] = $v['game_id'];
                $rData[] = $val;
            }
            //排序
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$sort3,SORT_ASC,$rData);
        }
        return $rData;
    }

    /**
     * 当日滚球赛事 (mongo数据，部分业务数据来自mysql)
     * @return array 滚球赛事数组
     */
    public function fbRollList($unionId,$subId='')
    {
        $mongo = mongoService();

        //从缓存获取今日赛事列表
        if(!$baseRes = S('cache_fbtodayList_game'))
        {
            $dataService = new \Common\Services\DataService();
            $gameIdArr = $dataService->getGameTodayGids(1);
            $baseRes = $mongo->select('fb_game',['game_id'=>['$in'=>$gameIdArr]],
                [
                    'game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id',
                    'away_team_id','union_id','start_time','game_starttime','game_start_timestamp','game_half_datetime',
                    'red_card','yellow_card','corner','game_state','score','half_score','explain','remark','is_go','is_flash',
                    'is_sporttery','spottery_num','union_color','field_weather','goal_data','odds_data'
                ]
            );
            S('cache_fbtodayList_game',$baseRes,3);
        }

        $rData = [];
        if(!empty($baseRes))
        {
            $unionIdArr = $gameIdArr = [];
            foreach($baseRes as $k=> $v)
            {
                $gameIdArr[]  = (int)$v['game_id'];
                $unionIdArr[] = (int)$v['union_id'];
            }

            if(!$gameArr = S('cache_fbtodayList_mysqlGame')){
                //获取mysql业务数据
                $GameFbinfo = M('GameFbinfo')
                    ->field("game_id,is_gamble,is_show,status,app_video,is_video")
                    ->where(['game_id'=>['in',$gameIdArr]])
                    ->select();

                foreach ($GameFbinfo as $k => $v) {
                    $gameArr[$v['game_id']] = $v;
                }
                S('cache_fbtodayList_mysqlGame',$gameArr,120);
            }

            //获取联盟数据
            if(!$unionArr = S('cache_fbtodayList_union')){
                $union = $mongo->select(
                    'fb_union',
                    ['union_id'=>['$in'=>$unionIdArr]],
                    ['union_id','union_name','country_id','level','union_or_cup','union_color']
                );
                foreach ($union as $k => $v) {
                    $unionArr[$v['union_id']] = $v;
                }
                S('cache_fbtodayList_union', $unionArr, 300);
            }

            //判断Api版本
            $version = explode('Api', MODULE_NAME)[1];
            foreach($baseRes as $k=> $v)
            {
                //赛事需要为比赛进行中和滚球为1
                if(!in_array($v['game_state'], [1,2,3,4,5]) || $v['is_go'] != 1){
                    continue;
                }
                if(stripos($v['home_team_name'][0],'测试') !== false || stripos($v['away_team_name'][0],'测试') !== false || stripos($v['home_team_name'][0],'test') !== false || stripos($v['away_team_name'][0],'test') !== false){
                    continue;
                }
                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(isset($mysqlGame['status']) && $mysqlGame['status'] != 1) continue;
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];
                $unionLevel = isset($unionData['level']) ? $unionData['level'] : '3';

                $val = [];
                $val[0] = (string)$v['game_id'];
                $val[1] = (string)$v['union_id'];
                $val[2] = $v['union_name'];
                $val[3] = !empty($unionData['union_color'])?$unionData['union_color']:'#aeba4b';
                $val[4] = (string)$unionLevel;
                //api520版本以下点球大战转为完场
                if($v['game_state'] == '5' && $version < 520){
                    $v['game_state'] = '-1';
                }
                $val[5] = (string)$v['game_state'];
                $game_start_timestamp = TellRealTime($v['start_time'],$v['game_start_timestamp'],$v['game_starttime'],$v['game_state']);
                $gameTime = explode('-',date('Ymd-H:i',$game_start_timestamp));
                $val[6] = $gameTime[0];
                $val[7] = $v['start_time'] ? :$gameTime[1];
                $val[8] = isset($v['game_half_datetime']) ? date('YmdHis',strtotime($v['game_half_datetime'])) : '';
                $val[9]  = $v['home_team_name'];
                $val[10] = $v['away_team_name'];

                //赔率
                $fbOdds = $this->fbOdds(null,3,$v['odds_data']);
                $odds_data = isset($fbOdds[$v['game_id']]) ? $fbOdds[$v['game_id']] : '';
                $goalsArr  = D('GambleHall')->doFswOdds($odds_data,$v['game_state'],$v['goal_data']?:'');

                #全场亚盘大小即时赔率
                $val[11] = $goalsArr[0];   //主队亚盘即时赔率
                $val[12] = changeExp($goalsArr[1]);   //亚盘即时盘口
                $val[13] = $goalsArr[2];   //客队亚盘即时赔率
                $val[14] = $goalsArr[6];   //主队大小即时赔率
                $val[15] = changeExp($goalsArr[7]);   //大小即时盘口
                $val[16] = $goalsArr[8];   //客队大小即时赔率
                $val[17] = $goalsArr[3];
                $val[18] = $goalsArr[4];
                $val[19] = $goalsArr[5];
                if($v['game_state'] == 1){
                    #半场让球滚球
                    $val[20] = $goalsArr[9];
                    $val[21] = changeExp($goalsArr[10]);
                    $val[22] = $goalsArr[11];
                    #半场大小滚球
                    $val[23] = $goalsArr[15];
                    $val[24] = changeExp($goalsArr[16]);
                    $val[25] = $goalsArr[17];
                    #半场欧盘滚球
                    $val[26] = $goalsArr[12];
                    $val[27] = $goalsArr[13];
                    $val[28] = $goalsArr[14];
                }else{
                    #半场让球滚球
                    $val[20] = '';
                    $val[21] = '';
                    $val[22] = '';
                    #半场大小滚球
                    $val[23] = '';
                    $val[24] = '';
                    $val[25] = '';
                    #半场欧盘滚球
                    $val[26] = '';
                    $val[27] = '';
                    $val[28] = '';
                }

                $score   = explode('-',$v['score']);
                $val[29] = $v['game_state'] != 0 ? $score[0] : '';
                $val[30] = $v['game_state'] != 0 ? $score[1] : '';
                $half_score = explode('-',$v['half_score']);
                $val[31] = $v['game_state'] != 0 ? $half_score[0] : '';
                $val[32] = $v['game_state'] != 0 ? $half_score[1] : '';

                #是否有视频直播
                $app_video = $mysqlGame['app_video'];
                if(in_array($v['game_state'],[0, 1, 2, 3, 4, 5]) && !empty($app_video) && $mysqlGame['is_video'] == 1)
                {
                    #video
                    if(!empty(json_decode($app_video)))
                        $val[33] = '1';
                    else
                        $val[33] = '0';
                }
                else
                {
                    $val[33] = '0';
                }

                //是否有动画直播
                if($v['is_flash'] == 1 && in_array($v['game_state'],[0, 1, 2, 3, 4, 5]))
                {
                    $val[34] = '1';
                } else {
                    $val[34] = '0';
                }

                $val[35] = !empty($v['is_sporttery']) ? (string)$v['is_sporttery'] : '0';
                $sort1[] = $v['game_state'];
                $sort2[] = $game_start_timestamp;
                $sort3[] = $v['game_id'];
                $rData[] = $val;
            }
            //排序
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$sort3,SORT_ASC,$rData);
        }
        return $rData;
    }

    /**
     * 当日完场赛事 (mongo数据，部分业务数据来自mysql)
     * @return array 完场赛事数组
     */
    public function fbOverList($date,$unionId,$subId)
    {
        $mongo     = mongoService();
        if(empty($date)){
            $date = date('Ymd',strtotime('-1 day'));
        }
        //MongoDate时间格式
        $startTime = new \MongoDate(strtotime($date.' 10:32:00'));
        $endTime   = new \MongoDate(strtotime($date.' 10:32:00') + 86400);
        $map['game_starttime'] = [
            '$gt' => $startTime,
            '$lt' => $endTime,
        ];
        if(!empty($unionId)){
            $map['union_id'] = ['$in'=>$unionId];
        }
        //dump($map);
        //die;
        // if(!empty($subId)) $map['is_sub'] = array('in',$subId);
        $baseRes = $mongo->select('fb_game',$map,['game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id','away_team_id','union_id','start_time','game_starttime','game_start_timestamp','game_half_datetime','red_card','yellow_card','corner','game_state','score','half_score','explain','remark','is_go','is_flash','is_sporttery','spottery_num','union_color','field_weather','match_odds.3', 'level']);
        // dump($baseRes);
        // die;
        $rData = [];
        if(!empty($baseRes))
        {
            $unionIdArr = $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[]       = (int)$v['game_id'];
                $unionIdArr[] = (int)$v['union_id'];
            }

            //获取联盟数据
            $union = $mongo->select('fb_union',['union_id'=>['$in'=>$unionIdArr]],['union_id','union_name','country_id','level','union_or_cup','union_color','is_league']);
            foreach ($union as $k => $v) {
                $unionArr[$v['union_id']] = $v;
            }

            //路珠判断
            foreach ($baseRes as $k => $v) {
                $uData = $unionArr[$v['union_id']];
                $uLevel = isset($uData['level']) ? $uData['level'] : '3';
                if(in_array($uLevel, [0, 1, 2])){
                    $lzTeamIds[] = $v['home_team_id'];
                    $lzTeamIds[] = $v['away_team_id'];
                }
            }
            $lzMaps = $this->checkGoodRule($mongo, $lzTeamIds);

            //获取mysql业务数据
            $GameFbinfo = M('GameFbinfo')->field("game_id,is_gamble,is_show,status,app_video,is_video")->where(['game_id'=>['in',$gids]])->select();
            foreach ($GameFbinfo as $k => $v) {
                $gameArr[$v['game_id']] = $v;
            }

            foreach($baseRes as $k =>$v)
            {
                if(stripos($v['home_team_name'][0],'测试') !== false || stripos($v['away_team_name'][0],'测试') !== false || stripos($v['home_team_name'][0],'test') !== false || stripos($v['away_team_name'][0],'test') !== false){
                    continue;
                }
                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(isset($mysqlGame['status']) && $mysqlGame['status'] != 1) continue;
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];
                $unionLevel = isset($unionData['level']) ? $unionData['level'] : '3';

                //过了开场时间未开始
                if($v['game_start_timestamp'] + 120 < time() && $v['game_state'] == 0) continue;
                //180分钟还没结束
                if($v['game_start_timestamp'] + 10800 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) continue;
                if(in_array($v['game_state'],[1,2,3,4])) continue;

                $val = [];
                $val[0] = (string)$v['game_id'];
                $val[1] = (string)$v['union_id'];
                $val[2] = $v['union_name'];
                $val[3] = !empty($unionData['union_color'])?$unionData['union_color']:'#aeba4b';
                $val[4] = (string)$unionLevel;
                $val[5] = (string)$v['game_state'];
                $game_start_timestamp = $v['game_start_timestamp'] ? : $v['game_starttime']->sec;
                $gameTime = explode('-',date('Ymd-H:i',$game_start_timestamp));
                $val[6] = $gameTime[0];
                $val[7] = $v['start_time'] ? :$gameTime[1];
                $val[8] = isset($v['game_half_datetime']) ? date('YmdHis',strtotime($v['game_half_datetime'])) : '';
                $val[9]  = $v['home_team_name'];
                $val[10] = $v['away_team_name'];
                $val[11] = $v['home_team_rank'] ? pregUnionRank($v['home_team_rank']) :'';
                $val[12] = $v['away_team_rank'] ? pregUnionRank($v['away_team_rank']) :'';
                $score = explode('-',$v['score']);
                $val[13] = $v['game_state'] != 0 ? $score[0] : '';
                $val[14] = $v['game_state'] != 0 ? $score[1] : '';
                $half_score = explode('-',$v['half_score']);
                $val[15] = $v['game_state'] != 0 ? $half_score[0] : '';
                $val[16] = $v['game_state'] != 0 ? $half_score[1] : '';

                #红牌
                $red = explode('-',$v['red_card']);
                $val[17] = $v['game_state'] != 0 ? $red[0] ?:'0' : '0';
                $val[18] = $v['game_state'] != 0 ? $red[1] ?:'0' : '0';
                #黄牌
                $yellow  = explode('-',$v['yellow_card']);
                $val[19] = $v['game_state'] != 0 ? $yellow[0] : '0';
                $val[20] = $v['game_state'] != 0 ? $yellow[1] : '0';
                #角球
                $corner = explode('-',$v['corner']);
                $val[21] = $v['game_state'] != 0 ? $corner[0] : '0';
                $val[22] = $v['game_state'] != 0 ? $corner[1] : '0';

                #全场亚盘大小取初盘
                $odds    = $v['match_odds'][3];
                $val[23] = str_replace(' ', '', $odds[0]);    //主队亚盘初盘赔率
                $val[24] = changeSnExpTwo(str_replace(' ', '', $odds[1]));    //亚盘初盘盘口
                $val[25] = str_replace(' ', '', $odds[2]);    //客队亚盘初盘赔率
                $val[26] = str_replace(' ', '', $odds[12]);   //主队大小初盘赔率
                $val[27] = str_replace(' ', '', $odds[13]);   //大小初盘盘口
                $val[28] = str_replace(' ', '', $odds[14]);   //客队大小初盘赔率

                #是否有视频直播
                $app_video = $mysqlGame['app_video'];
                if($v['game_state'] !=-1 && !empty($app_video) && $mysqlGame['is_video'] == 1)
                {
                    #video
                    $val[29] = !empty(json_decode($app_video)) ? '1': '0';
                }
                else
                {
                    $val[29] = '0';
                }

                //是否有动画直播
                $val[30] = $v['is_flash'] == 1 && in_array($v['game_state'],[0, 1, 2, 3, 4]) ? '1' : '0';
                $val[31] = !empty($v['is_sporttery']) ? (string)$v['is_sporttery'] : '0';
                $val[32] = !empty($v['spottery_num']) ? $v['spottery_num'] : '';
                //赛事状态文字数据
                $val[33] = isset($v['explain']) && $v['explain'] != '' ? $v['explain'] : '';
                $val[34] = isset($v['remark']) && $v['remark'] != '' ? $v['remark'] : '';
                $val[35] = (string)$v['home_team_id'];
                $val[36] = (string)$v['away_team_id'];

                //好路、路珠
                $val[37] = '0';
                if($lzMaps[$v['away_team_id']] == 2 || $lzMaps[$v['home_team_id']] == 2){
                    $val[37] = '2';
                }else{
                    $val[37] = $lzMaps[$v['away_team_id']] || $lzMaps[$v['home_team_id']] ? '1' : '0';
                }
                $is_league = $unionArr[$v['union_id']]['is_league']?:$unionArr[$v['union_id']]['union_or_cup'];
                $val[38] = (string)$is_league;
                $rData[] = $val;
                $sort1[] = $v['game_state'];
                $sort2[] = $game_start_timestamp;
            }
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$rData);
        }
        return $rData;
    }

    /**
     * 赛程列表 (mongo数据，部分业务数据来自mysql)
     * @param  int $date       日期
     * @param  string $unionId 赛事ID,多个以‘,’隔开
     * @return array           赛程数据
     */
    public function fbFixtureList($date,$unionId,$subId='')
    {
        $mongo     = mongoService();
        if(empty($date)){
            $date = date('Ymd');
        }
        //MongoDate时间格式
        $startTime = new \MongoDate(strtotime($date.' 10:32:00'));
        $endTime   = new \MongoDate(strtotime($date.' 10:32:00') + 86400);
        $map['game_starttime'] = [
            '$gt' => $startTime,
            '$lt' => $endTime,
        ];
        if(!empty($unionId)){
            $map['union_id'] = ['$in'=>$unionId];
        }
        // if(!empty($subId)) $map['is_sub'] = array('in',$subId);
        $baseRes = $mongo->select('fb_game',$map,['game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id','away_team_id','union_id','game_starttime','game_start_timestamp','game_half_datetime','red_card','yellow_card','let_goal','corner','game_state','score','half_score','is_go','is_flash','is_sporttery','spottery_num','union_color','field_weather','match_odds.3', 'level']);
        // dump($baseRes);
        // die;
        $rData = [];
        if(!empty($baseRes))
        {
            $unionIdArr = $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[]       = (int)$v['game_id'];
                $unionIdArr[] = (int)$v['union_id'];
            }


            //获取即时盘口赔率
            $oddsArr = $this->fbOdds($gids);

            //获取联盟数据
            $union = $mongo->select('fb_union',['union_id'=>['$in'=>$unionIdArr]],['union_id','union_name','country_id','level','union_or_cup','union_color','is_league']);
            foreach ($union as $k => $v) {
                $unionArr[$v['union_id']] = $v;
            }

            //路珠判断
            foreach ($baseRes as $k => $v) {
                $uData = $unionArr[$v['union_id']];
                $uLevel = isset($uData['level']) ? $uData['level'] : '3';
                if(in_array($uLevel, [0, 1, 2])){
                    $lzTeamIds[] = $v['home_team_id'];
                    $lzTeamIds[] = $v['away_team_id'];
                }
            }
            $lzMaps = $this->checkGoodRule($mongo, $lzTeamIds);

            //获取mysql业务数据
            $GameFbinfo = M('GameFbinfo')->field("game_id,is_gamble,is_show,status,app_video,is_video")->where(['game_id'=>['in',$gids]])->select();
            foreach ($GameFbinfo as $k => $v) {
                $gameArr[$v['game_id']] = $v;
            }

            foreach($baseRes as $k=>$v)
            {
                if(stripos($v['home_team_name'][0],'测试') !== false || stripos($v['away_team_name'][0],'测试') !== false || stripos($v['home_team_name'][0],'test') !== false || stripos($v['away_team_name'][0],'test') !== false){
                    continue;
                }
                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(isset($mysqlGame['status']) && $mysqlGame['status'] != 1) continue;
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];
                $unionLevel = isset($unionData['level']) ? $unionData['level'] : '3';

                if(isset($v['game_start_timestamp'])){
                    //过了开场时间未开始
                    if($v['game_start_timestamp'] + 120 < time() && $v['game_state'] == 0) continue;
                    //180分钟还没结束
                    if($v['game_start_timestamp'] + 10800 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) continue;
                }
                if(in_array($v['game_state'],[-1,1,2,3,4])) continue;
                $val = [];
                $val[0] = (string)$v['game_id'];
                $val[1] = (string)$v['union_id'];
                $val[2] = $v['union_name'] ? : $unionData['union_name'];
                $val[3] = !empty($unionData['union_color'])?$unionData['union_color']:'#aeba4b';
                $val[4] = (string)$unionLevel;
                $val[5] = (string)$v['game_state'] ? : '0';
                $game_start_timestamp = $v['game_start_timestamp'] ? : $v['game_starttime']->sec;
                $gameTime = explode('-',date('Ymd-H:i',$game_start_timestamp));
                $val[6]  = $gameTime[0];
                $val[7]  = $gameTime[1];
                $val[8]  = $v['home_team_name'];
                $val[9]  = $v['away_team_name'];
                $val[10] = $v['home_team_rank'] ? pregUnionRank($v['home_team_rank']) :'';
                $val[11] = $v['away_team_rank'] ? pregUnionRank($v['away_team_rank']) :'';

                #全场亚盘大小即时赔率
                if(isset($oddsArr[$v['game_id']]))
                {
                    $fb_odds = $oddsArr[$v['game_id']];
                    $val[12] = $fb_odds[0];   //主队亚盘即时赔率
                    $val[13] = changeExp($fb_odds[1]);   //亚盘即时盘口
                    $val[14] = $fb_odds[2];   //客队亚盘即时赔率
                    $val[15] = $fb_odds[6];   //主队大小即时赔率
                    $val[16] = changeExp($fb_odds[7]);   //大小即时盘口
                    $val[17] = $fb_odds[8];   //客队大小即时赔率
                }
                else
                {
                    //取初盘
                    $odds    = $v['match_odds'][3];
                    $val[12] = str_replace(' ', '', $odds[0]);    //主队亚盘初盘赔率
                    $val[13] = changeSnExpTwo(str_replace(' ', '', $odds[1]));    //亚盘初盘盘口
                    $val[14] = str_replace(' ', '', $odds[2]);    //客队亚盘初盘赔率
                    $val[15] = str_replace(' ', '', $odds[12]);   //主队大小初盘赔率
                    $val[16] = str_replace(' ', '', $odds[13]);   //大小初盘盘口
                    $val[17] = str_replace(' ', '', $odds[14]);   //客队大小初盘赔率
                }

                #是否有视频直播
                $app_video = $mysqlGame['app_video'];
                if($v['game_state'] !=-1 && !empty($app_video) && $mysqlGame['is_video'] == 1)
                {
                    #video
                    if(!empty(json_decode($app_video)))
                        $val[18] = '1';
                    else
                        $val[18] = '0';
                }
                else
                {
                    $val[18] = '0';
                }

                //是否有动画直播
                if($v['is_flash'] == 1 && in_array($v['game_state'],[0, 1, 2, 3, 4]))
                {
                    $val[19] = '1';
                } else {
                    $val[19] = '0';
                }

                $val[20] = !empty($v['is_sporttery']) ? (string)$v['is_sporttery'] : '0';
                $val[21] = !empty($v['spottery_num']) ? $v['spottery_num'] : '';
                $val[22] = (string)$v['home_team_id'];
                $val[23] = (string)$v['away_team_id'];
                $val[24] = $v['let_goal'] === null?'':(string)$v['let_goal'];

                //好路、路珠
                $val[25] = '0';
                if($lzMaps[$v['away_team_id']] == 2 || $lzMaps[$v['home_team_id']] == 2){
                    $val[25] = '2';
                }else{
                    $val[25] = $lzMaps[$v['away_team_id']] || $lzMaps[$v['home_team_id']] ? '1' : '0';
                }
                $is_league = $unionArr[$v['union_id']]['is_league']?:$unionArr[$v['union_id']]['union_or_cup'];
                $val[38] = (string)$is_league;

                $rData[] = $val;
                $sort1[] = $v['game_state'];
                $sort2[] = $game_start_timestamp;
            }
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$rData);
        }
        return $rData;
    }

    /**
     * app即时指数界面
     * @param  int $unionId 赛事ID，多个以‘,’隔开
     * @param  int $subId   级别ID，多个以‘,’隔开
     * @return array 即时赛事数组
     */
    public function fbInstant($unionId,$subId='')
    {
	    $mongodb  = mongoService();
	    // TODO 少一个 ['status'] = 1 判断   '赛程状态，0：在即时比分、竞猜大厅都不显示、1：显示', mongo 中无此业务字段
	    $game_filter = ['game_id' => [$mongodb->cmd('in') => getTodayGameList()], 'game_state' => 0];
	    $game_time_filter = ['game_start_timestamp' => [$mongodb->cmd('gt') => time(), $mongodb->cmd('lt') => time() + 86400], 'game_state' => 0];
	    if(!empty($unionId)) {
		    $union_id = arrayStringToInt(explode(',', $unionId));
		    $game_filter['union_id'] = [$mongodb->cmd('in') => $union_id];
	    }
	    // $game_field = ['game_id', 'union_id', 'union_name', 'game_start_timestamp', 'game_half_datetime', 'game_state', 'home_team_name','away_team_name', 'score', 'half_score', 'match_odds','match_odds_m_asia', 'match_odds_m_bigsmall','union_color', 'union_name'];
	    $game_field = ['game_id', 'union_id', 'union_name', 'game_start_timestamp', 'game_half_datetime', 'game_state', 'home_team_name','away_team_name', 'score', 'half_score', 'union_color', 'union_name'];
        //获取今日列表赛事
	    $gameRes= $mongodb->select('fb_game',$game_filter,$game_field);
        // dump($gameRes);
        // die;
	    // 获取明日和后日赛事列表 防止无数据
	    $gameTimeRes = $mongodb->select("fb_game", $game_time_filter, $game_field);
	    //去重
	    foreach ($gameTimeRes as $key => $value) {
	    	foreach($gameRes as $k => $v) {
                $gameArr[] = (int)$v['game_id'];
	    	    if ($value['game_id'] == $v['game_id']) {
	    	    	unset($gameTimeRes[$key]);
		        }
		    }
	    }
	    $gameRes = array_merge($gameTimeRes, $gameRes);
	    $game_union = array_merge(array_unique(array_column($gameRes, 'union_id')), []);
	    $union_filter['union_id'] = [$mongodb->cmd('in') => $game_union];
	    if(!empty($subId)) {
		    $sub_id = arrayStringToInt(explode(',', $subId));
		    $union_filter['level'] = [$mongodb->cmd('in') => $sub_id];
	    } else {
		    $union_filter['level'] = [$mongodb->cmd('in') => [0, 1, 2]];
	    }
	    $union_field = ['level', 'union_id'];
	    $unionRes = $mongodb->select('fb_union', $union_filter,$union_field);
	    $res = filterArray($gameRes, $unionRes, 'union_id');

	    //将联赛级别添加到结果集中
	    foreach ($res as $key => $value) {
	    	foreach ($unionRes as $uk => $uv) {
	    		if ($value['union_id'] == $uv['union_id']) {
	    			$res[$key]['level'] = $uv['level'];
			    }
		    }
	    }

	    $data = $oddsInit = [];
//	    $odds = $this->filterMongoOdds($res);

        //获取即时指数
        $gameArr = array_column($gameRes,'game_id');
        $oddsTmp = $mongodb->select('fb_index',['game_id'=>['$in'=>$gameArr]],['game_id','odds_init']);
        foreach($oddsTmp as $val)
        {
            $tmp = [];
            $tmp['game_id'] = $val['game_id'];
            foreach($val['odds_init'] as $k=>$v)
            {
                if($v['a']) $tmp['a'][$k] = $this->oddsInit($v['a'],$k,1);
                if($v['o']) $tmp['o'][$k] = $this->oddsInit($v['o'],$k);
                if($v['d']) $tmp['d'][$k] = $this->oddsInit($v['d'],$k,1);
            }
            $odds_init[$val['game_id']] = $tmp;
        }
	    foreach ($res as $key => $value)
	    {
	        $gameData = [];
	        $gameData[0] = (string) $value['game_id'];
	        $gameData[1] = (string) $value['union_id'];
	        $gameData[2] = $value['union_name'];
		    $gameData[3] = NullString($value['union_color']);
		    $gameData[4] = $value['home_team_name'];
		    $gameData[5] = $value['away_team_name'];
		    $gameData[6] = date('YmdHis', $value['game_start_timestamp']);
		    $gameData[7] = (string) $value['level'];
            $oddsRes = $odds_init[$value['game_id']];
			$gameData[8] = emptyReturnArray($oddsRes['a']);
		    $gameData[9] = emptyReturnArray($oddsRes['o']);
		    $gameData[10] = emptyReturnArray($oddsRes['d']);
		    $sort[] = $value['game_start_timestamp'];
		    $data[] = $gameData;
	    }
	    array_multisort($sort, SORT_ASC, $data);
	    return $data;


	    /*
	    mysql 源
	    $startTime = time();
	    $endTime = $startTime+3600*24;
        $GameFbinfo = M('GameFbinfo');
        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
        $map['game_state'] = 0;
        $map['a.status'] = 1;
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);
        if(!empty($subId))
            $map['is_sub'] = array('in',$subId);
        else
            $map['is_sub'] = array('in','0,1,2');

        $baseRes = $GameFbinfo->alias('a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,
        home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,is_video,u.union_name as u_name')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
            }

            $oddsArr = $this->fbOddsIns($gids);

            foreach($baseRes as $k=>$v)
            {
                if($v['is_sub'] === null || $v['is_sub'] === '')
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if(!isset($oddsArr['asia'][$v['game_id']]) && !isset($oddsArr['euro'][$v['game_id']]) && !isset($oddsArr['ball'][$v['game_id']]))
                {
                    unset($baseRes[$k]);
                    continue;
                }
                $val = [];
                $val[0] = $v['game_id'];    //赛事ID
                $val[1] = $v['union_id'];     //联赛ID
                if(!empty($v['union_name']))
                    $val[2] = explode(',',$v['union_name']);
                else
                    $val[2] = explode(',',$v['u_name']);
                $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';  //联赛背景颜色
                $val[4] = explode(',',$v['home_team_name']);
                $val[5] = explode(',',$v['away_team_name']);
                $val[6] = date('YmdHis',$v['gtime']);   //比赛时间
                $val[7] = $v['is_sub'];    //联赛级别
                #亚赔
                $asianTemp =[];
                if(isset($oddsArr['asia'][$v['game_id']])) $asianTemp = $oddsArr['asia'][$v['game_id']];
                $val[8] = $asianTemp;

                #欧赔
                $europeTemp =[];
                if(isset($oddsArr['euro'][$v['game_id']])) $europeTemp = $oddsArr['euro'][$v['game_id']];
                $val[9] = $europeTemp;

                #大小
                $ballTemp =[];
                if(isset($oddsArr['ball'][$v['game_id']])) $ballTemp = $oddsArr['ball'][$v['game_id']];
                $val[10] = $ballTemp;

                $rData[] = $val;
            }
        }
        return $rData;
	    */
    }

    //处理指数数据结构
    public function oddsInit($data,$k,$type = 0)
    {
        $compant = C('DB_COMPANY_ODDS');//获取公司名称
        $sprit = C('score_sprit');//获取公司名称
        $res = [];
        $res[] = (string)$k;
        $res[] = (string)$compant[$k];
        if($type)
        {
            $pan1 = $pan2 = $str = '';
            if(strpos($data[0],'-') !== false)
            {
                $data[0] = ltrim($data[0],'-');
                $str = '-';
            }
            $pan1 = $sprit[$data[0]]?$str.$sprit[$data[0]]:$str.$data[0];
            $str = '';
            if(strpos($data[3],'-') !== false)
            {
                $data[3] = ltrim($data[3],'-');
                $str = '-';
            }
            $pan2 = $sprit[$data[3]]?$str.$sprit[$data[3]]:$str.$data[3];
            $res[] = (string)$data[1];
            $res[] = (string)$pan1;
            $res[] = (string)$data[2];
            $res[] = (string)$data[4];
            $res[] = (string)$pan2;
            $res[] = (string)$data[5];
        }else{
            $res = array_merge($res,$data);
        }
        return $res;


    }

     /**
     * 即时赔率数据(多公司,指数比较界面数据源)
     * @param  array   $gameIds  赛事ID
     * @return array 全场即时赔率数据
     */
    public function getChoddsB($gameId = '')
    {
        if(!empty($gameId)) $gData = explode(',',$gameId);

        $sql = 'select update_time as utime from qc_fb_odds order by update_time desc limit 1';
        $res = M()->query($sql);
        $rData = [];

        //$oddsArr = array(9,14,3,4,24,1,19,12,18,7,8,17,23,31,35,22);
        //$oddsArr = array(3,4,24,1,12);
        if (!empty($res))
        {
            $sql = 'select id,game_id,company_id,exp_value from qc_fb_odds where update_time ='.$res[0]['utime'];
            $res = M()->query($sql);

            $aisan = [];
            $euro =[];
            $ball = [];

            foreach($res as $k=>$v)
            {
                //if(array_search($v['company_id'],$oddsArr) === false) continue;
                $oddsTemp = oddsChArr($v['exp_value']);
                if($oddsTemp[0][6] == '' && $oddsTemp[0][7] == '' && $oddsTemp[0][8] == '')
                {
                    $aOdds = [
                        0 => $v['game_id'],
                        1 => $v['company_id'],
                        2 => $oddsTemp[0][4],
                        3 => $oddsTemp[0][3],
                        4 => $oddsTemp[0][5],
                    ];
                }
                else
                {
                    $aOdds = [
                        0 => $v['game_id'],
                        1 => $v['company_id'],
                        2 => $oddsTemp[0][7],
                        3 => $oddsTemp[0][6],
                        4 => $oddsTemp[0][8],
                    ];

                }
                $aisan[$v['game_id']][$v['company_id']] = $aOdds;

                if($oddsTemp[1][6] == '' && $oddsTemp[1][7] == '' && $oddsTemp[1][8] == '')
                {
                    $eOdds = [
                        0 => $v['game_id'],
                        1 => $v['company_id'],
                        2 => $oddsTemp[1][3],
                        3 => $oddsTemp[1][4],
                        4 => $oddsTemp[1][5],
                    ];
                }
                else
                {
                    $eOdds = [
                        0 => $v['game_id'],
                        1 => $v['company_id'],
                        2 => $oddsTemp[1][6],
                        3 => $oddsTemp[1][7],
                        4 => $oddsTemp[1][8],
                    ];

                }
                $euro[$v['game_id']][$v['company_id']] = $eOdds;

                if($oddsTemp[2][6] == '' && $oddsTemp[2][7] == '' && $oddsTemp[2][8] == '')
                {
                    $bOdds = [
                        0 => $v['game_id'],
                        1 => $v['company_id'],
                        2 => $oddsTemp[2][4],
                        3 => $oddsTemp[2][3],
                        4 => $oddsTemp[2][5],
                    ];
                }
                else
                {
                    $bOdds = [
                        0 => $v['game_id'],
                        1 => $v['company_id'],
                        2 => $oddsTemp[2][7],
                        3 => $oddsTemp[2][6],
                        4 => $oddsTemp[2][8],
                    ];

                }
                $ball[$v['game_id']][$v['company_id']] = $bOdds;
            }

            $rData[] = ['name'=>'asian','content'=>$aisan];
            $rData[] = ['name'=>'europe','content'=>$euro];
            $rData[] = ['name'=>'ball','content'=>$ball];
        }
        return $rData;
    }

    /**
     *  滚球赔率变化数据
     * @param  string   $gameId  赛事ID，多个以‘,’隔开
     * @param  int     $type      返回数据类别：1全场半场；2全场；3半场
     * @return array 最新赔率数据
     */
    public function getOddsRoll($gameId,$type = '1')
    {

        if(strtotime('10:32:00') < time())
        {
            $startTime = strtotime('8:00:00');
            $endTime = strtotime('10:32:00')+3600*24;
        }
        else
        {
            $startTime =strtotime('8:00:00')-3600*24;
            $endTime = strtotime('10:32:00');
        }

        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
        $map['a.status'] = 1;
        $map['is_go'] = 1;
        $map['game_state'] = array('in','1,2,3,4');
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);
        if(!empty($subId)) $map['is_sub'] = array('in',$subId);
        if(!empty($gameId)) $map['game_id'] = array('in',$gameId);

        $GameFbinfo = M('GameFbinfo');

        $baseRes = $GameFbinfo->table('qc_game_fbinfo a')->field('a.id,game_id,game_state')->join('qc_union u ON a.union_id = u.union_id','LEFT')->where($map)->order('game_state desc,gtime,is_sub,a.id')->select();

        $idArr = [];
        if(!empty($baseRes))
        {
            foreach($baseRes as $k=>$v)
            {
                $idArr[] = $v['game_id'];
            }
        }
        else
        {
            return null;
        }

        $sql = sprintf('select * from qc_fb_odds where game_id in (%s) and company_id = 3',implode(',',$idArr));
        $res = M()->query($sql);

        $fData = [];
        $pData = [];

        if(!empty($res))
        {
            foreach($res as $k =>$v)
            {
                $oddsTemp = oddsChArr($v['exp_value']);
                $fTemp = [
                    0 => $oddsTemp[0][6],
                    1 => changeExp($oddsTemp[0][7]),
                    2 => $oddsTemp[0][8],
                    3 => $oddsTemp[2][6],
                    4 => changeExp($oddsTemp[2][7]),
                    5 => $oddsTemp[2][8],
                    6 => isset($oddsTemp[1][6])?$oddsTemp[1][6]:'',
                    7 => isset($oddsTemp[1][7])?$oddsTemp[1][7]:'',
                    8 => isset($oddsTemp[1][8])?$oddsTemp[1][8]:''
                ];
                $fData[$v['game_id']] = $fTemp;
                $pTemp = [
                    0 => $oddsTemp[3][6],
                    1 => changeExp($oddsTemp[3][7]),
                    2 => $oddsTemp[3][8],
                    3 => $oddsTemp[5][6],
                    4 => changeExp($oddsTemp[5][7]),
                    5 => $oddsTemp[5][8],
                    6 => $oddsTemp[4][6],
                    7 => $oddsTemp[4][7],
                    8 => $oddsTemp[4][8]
                ];
                $pData[$v['game_id']] = $pTemp;
            }
            $rData[] = array('fsw'=>$fData,'psw'=>$pData);
        }
        switch($type)
        {
            case '1':
                return $rData;
                break;
            case '2':
                return $fData;
                break;
            case '3':
                return $pData;
                break;
        }
    }

    /**
     *  根据赛事ID获取最新赔率数据
     * @param  string   $gameIds  赛事ID，多个以‘,’隔开
     * @param  int     $type      返回数据类别：1全场半场；2全场；3半场
     * @param  int     $companyID  公司ID
     * @return array  最新赔率数据
     */
    public function getOddsById($gameIds, $type = 2 ,$companyId = 3)
    {
        if(empty($gameIds) || is_array($gameIds)) return false;

        $sql = sprintf('select * from qc_fb_odds where game_id in (%s) and company_id = %d',$gameIds,$companyId);
        $res = M()->query($sql);

        $fData = [];
        $pData = [];

        if(!empty($res))
        {
            foreach($res as $k =>$v)
            {
                $oddsTemp = oddsChArr($v['exp_value']);
                #全场
                $fTemp = [
                    0 => $oddsTemp[0][0],              //主队让球赔率
                    1 => changeExp($oddsTemp[0][1]),   //让球盘口
                    2 => $oddsTemp[0][2],              //客队让球赔率
                    3 => $oddsTemp[2][0],              //主队大小赔率
                    4 => changeExp($oddsTemp[2][1]),   //让球盘口
                    5 => $oddsTemp[2][3],              //客队大小赔率
                    6 => $oddsTemp[1][0],              //主队欧赔赔率
                    7 => $oddsTemp[1][1],              //平赔率
                    8 => $oddsTemp[1][2],               //客队欧赔赔率
                    9 => $oddsTemp[0][3],
                    10 => changeExp($oddsTemp[0][4]),
                    11 => $oddsTemp[0][5],
                    12 => $oddsTemp[2][3],
                    13 => changeExp($oddsTemp[2][4]),
                    14 => $oddsTemp[2][5],
                    15 => $oddsTemp[1][3],
                    16 => $oddsTemp[1][4],
                    17 => $oddsTemp[1][5],
                    18 => $oddsTemp[0][6],
                    19 => changeExp($oddsTemp[0][7]),
                    20 => $oddsTemp[0][8],
                    21 => $oddsTemp[2][6],
                    22 => changeExp($oddsTemp[2][7]),
                    23 => $oddsTemp[2][8],
                    24 => $oddsTemp[1][6],
                    25 => $oddsTemp[1][7],
                    26 => $oddsTemp[1][8]
                ];
                $fData[$v['game_id']] = $fTemp;
                #半场
                $pTemp = [
                    0 => $oddsTemp[3][0],              //主队让球赔率
                    1 => changeExp($oddsTemp[3][1]),   //让球盘口
                    2 => $oddsTemp[3][2],              //客队让球赔率
                    3 => $oddsTemp[5][0],              //主队大小赔率
                    4 => changeExp($oddsTemp[5][1]),   //让球盘口
                    5 => $oddsTemp[5][3],              //客队大小赔率
                    6 => $oddsTemp[4][0],              //主队欧赔赔率
                    7 => $oddsTemp[4][1],              //平赔率
                    8 => $oddsTemp[4][2],               //客队欧赔赔率
                    9 => $oddsTemp[3][3],
                    10 => changeExp($oddsTemp[3][4]),
                    11 => $oddsTemp[3][5],
                    12 => $oddsTemp[5][3],
                    13 => changeExp($oddsTemp[5][4]),
                    14 => $oddsTemp[5][5],
                    15 => $oddsTemp[4][3],
                    16 => $oddsTemp[4][4],
                    17 => $oddsTemp[4][5],
                    18 => $oddsTemp[3][6],
                    19 => changeExp($oddsTemp[3][7]),
                    20 => $oddsTemp[3][8],
                    21 => $oddsTemp[5][6],
                    22 => changeExp($oddsTemp[5][7]),
                    23 => $oddsTemp[5][8],
                    24 => $oddsTemp[4][6],
                    25 => $oddsTemp[4][7],
                    26 => $oddsTemp[4][8],
                ];
                $pData[$v['game_id']] = $pTemp;
            }
            $rData[] = array('fsw'=>$fData,'psw'=>$pData);
        }
        switch($type)
        {
            case '1':
                return $rData;
                break;
            case '2':
                return $fData;
                break;
            case '3':
                return $pData;
                break;
        }
    }

    /**
     * SB公司赔率历史数据————mongo数据
     * @param  int    $gameId  赛事ID
     * @param  int    $type    类别：1亚，2欧，3大小
     * @return array  SB公司赔率历史数据
     */
    public function getSBhisOdds($gameId,$type=1)
    {
        if(empty($gameId)) return false;
        $mongodb = mongoService();
        $game = $mongodb->select('fb_game',['game_id'=>(int)$gameId],['odds_history.3']);

        $data = [];
        switch($type)
        {
            case 1:
                //亚盘数据处理
                $yapan   = $game[0]['odds_history'][3][0];   //亚盘
                $score_cn = C('score_cn');
                foreach ($yapan as $k => $v) {
                    if($v[6] != '滚') continue;
                    $handcp = $v[3];
                    $exp = '';
                    //受让时转换
                    if(stripos($handcp, '受让') !== false){
                        $handcp = str_replace('受让', '', $handcp);
                        $exp = '-';
                    }
                    //盘口格式转换
                    if(isset($score_cn[$handcp])){
                        $v[3] = $exp.$score_cn[$handcp];
                    }
                    $temp = [
                        0 => $v[2],
                        1 => $v[3],
                        2 => $v[4],
                        3 => $v[5],
                        4 => $v[1],
                        5 => $v[0]
                    ];
                    $data[] = $temp;
                }
                break;
            case 2:
                //欧指数据处理
                $ouzhi   = $game[0]['odds_history'][3][2];   //欧指
                foreach ($ouzhi as $k => $v) {
                    if($v[6] != '滚') continue;
                    $temp = [
                        0 => $v[2],
                        1 => $v[3],
                        2 => $v[4],
                        3 => $v[5],
                        4 => $v[1],
                        5 => $v[0]
                    ];
                    $data[] = $temp;
                }
                break;
            case 3:
                //大小数据处理
                $daxiao  = $game[0]['odds_history'][3][1];   //大小
                foreach ($daxiao as $k => $v) {
                    if($v[6] != '滚') continue;
                    $temp = [
                        0 => $v[2],
                        1 => $v[3],
                        2 => $v[4],
                        3 => $v[5],
                        4 => $v[1],
                        5 => $v[0]
                    ];
                    $data[] = $temp;
                }
                break;
        }
        return array_reverse($data);
    }

    /**
     * SB公司赔率历史数据————数据库数据
     * @param  int    $gameId  赛事ID
     * @param  int    $type     类别：1亚，2欧，3大小
     * @return array  SB公司赔率历史数据
     */
    // public function getSBhisOdds($gameId ,$type = 1)
    // {
    //     if(empty($gameId)) return false;

    //     $fbHisodds = M('fbOddshis');

    //     $map['game_id'] = (int) $gameId;
    //     $map['company_id'] = 3;

    //     $baseRes = $fbHisodds->field('sb_ahistory,sb_ohistory,sb_bhistory')->where($map)->find();
    //     $rData = [];
    //     $hisOdds = [];

    //     if(!empty($baseRes))
    //     {
    //         $gRes = M('GameFbinfo')->field('gtime')->where(['game_id'=>(int) $gameId])->find();
    //         $gameTime = date('YmdHis',$gRes['gtime']);

    //         switch($type)
    //         {
    //             case 1:
    //                 if(empty($baseRes['sb_ahistory'])) break;
    //                 $hisOdds = json_decode($baseRes['sb_ahistory'],true);
    //                 break;
    //             case 2:
    //                 if(empty($baseRes['sb_ohistory'])) break;
    //                 $hisOdds = json_decode($baseRes['sb_ohistory'],true);
    //                 break;
    //             case 3:
    //                 if(empty($baseRes['sb_bhistory'])) break;
    //                 $hisOdds = json_decode($baseRes['sb_bhistory'],true);
    //                 break;
    //         }

    //         if(!empty($hisOdds))
    //         {
    //             $aSort = [];
    //             foreach($hisOdds as $k=>$v)
    //             {

    //                 if($v['Score'] == '即' || $v['Score'] == '早') continue;
    //                 if($v['IsClosed'] =='封')
    //                 {
    //                     $temp = [
    //                         0 => $v['HomeOdds'],
    //                         1 => '100',
    //                         2 => $v['AwayOdds'],
    //                         3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
    //                         4 => $v['Score'],
    //                     ];
    //                 }
    //                 else
    //                 {
    //                     $temp = [
    //                         0 => $v['HomeOdds'],
    //                         1 => changeExp($v['PanKou']),
    //                         2 => $v['AwayOdds'],
    //                         3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
    //                         4 => $v['Score'],
    //                     ];
    //                 }
    //                 if($v['IsClosed'] =='封')
    //                 {
    //                     $temp = [
    //                         0 => $v['HomeOdds'],
    //                         1 => '100',
    //                         2 => $v['AwayOdds'],
    //                         3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
    //                         4 => $v['Score'],
    //                         //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
    //                         5=> $v['HappenTime'],
    //                     ];
    //                 }
    //                 else
    //                 {
    //                     $temp = [
    //                         0 => $v['HomeOdds'],
    //                         1 => changeExp($v['PanKou']),
    //                         2 => $v['AwayOdds'],
    //                         3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
    //                         4 => $v['Score'],
    //                         //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
    //                         5=> $v['HappenTime'],
    //                     ];
    //                 }

    //                 $temp[5] = str_pad($temp[5],2,"0",STR_PAD_LEFT);

    //                 $aSort[] = $temp[3];
    //                 $aSort2[] = $temp[5];
    //                 $rData[] = $temp;
    //             }
    //             array_multisort($aSort, SORT_ASC, $rData,SORT_DESC,$aSort2);
    //         }
    //     }
    //     return $rData;
    // }

     /**
     * 根据公司ID各公司历史赔率数据————数据库数据
     * @param  int   $gameId  赛事ID
     * @param  int   $companyID  公司ID
     * @param  int   $type     类别：1亚，2欧，3大小
     * @return array 历史赔率数据
     */
    public function getHisOdds($gameId ,$companyID =3 ,$type = 1)
    {
        if(empty($gameId)) return false;

        $fbHisodds = M('fbOddshis');
        $map['game_id'] = (int) $gameId;
        $map['company_id'] = $companyID;

        $baseRes = $fbHisodds->field('ahistory,ohistory,bhistory')->where($map)->find();

        $rData = [];
        $hisOdds = '';
        if(!empty($baseRes))
        {
            switch($type)
            {
                case 1:
                    if(!empty($baseRes['ahistory'])) $hisOdds = $baseRes['ahistory'];
                    break;
                case 2:
                    if(!empty($baseRes['ohistory'])) $hisOdds = $baseRes['ohistory'];
                    break;
                case 3:
                    if(!empty($baseRes['bhistory'])) $hisOdds = $baseRes['bhistory'];
                    break;
            }

            if(!empty($hisOdds))
            {
                //$aSort = [];
                $aData = [];
                $oddsArr = explode('!',$hisOdds);
                foreach($oddsArr as $k=>$v)
                {
                    $arr = explode('^',$v);
                    if($type == 2)
                    {
                        $temp = [
                            0 => $arr[0],
                            1 => $arr[1],
                            2 => $arr[2],
                            3 => date('Y-m-d H:i',strtotime($arr[3])),
                        ];
                    }
                    else
                    {
                        $temp = [
                            0 => $arr[0],
                            1 => changeExp($arr[1]),
                            2 => $arr[2],
                            3 => date('Y-m-d H:i',strtotime($arr[3])),
                        ];
                    }

                    //$aSort[] = $temp[3];
                    $aData[] = $temp;
                }
                $rData = array_reverse($aData);
                //array_multisort($aSort, SORT_ASC, $rData);
            }
        }
        return $rData;
    }

    /**
     * 根据公司ID获取赔率各公司初盘指数、即时指数
     * @param  int   $gameId  赛事ID
     * @return array  赔率数据
     */
    public function getAllOdds($gameId ,$type = 1)
    {
        if(empty($gameId)) return false;

        $oddsCompany = array_flip(C('AOB_COMPANY_ID'));
        $fbMatchodds = M('fbMatchodds');
        $map['game_id'] = (int) $gameId;

        $baseRes = $fbMatchodds->field('aodds,oodds,bodds')->where($map)->find();

        $rData = [];
        $hisOdds = '';
        if(!empty($baseRes))
        {
            switch($type)
            {
                case 1:
                    if(!empty($baseRes['aodds']))
                    {
                        $hisOdds = $baseRes['aodds'];
                        $oddsArr = explode('!',$hisOdds);

                        foreach($oddsArr as $k=>$v)
                        {
                            $arr = explode('^',$v);
                            $temp = [
                                0 => $arr[0],
                                1 => $arr[2],
                                2 => changeExp($arr[3]),
                                3 => $arr[4],
                                4 => $arr[5],
                                5 => changeExp($arr[6]),
                                6 => $arr[7],
                                7 => isset($oddsCompany[$arr[0]])?(string)$oddsCompany[$arr[0]]:'',
                            ];
                            if($arr[0] == '18Bet') $temp[7] = '42';
                            $rData[] = $temp;
                        }
                    }
                    break;
                case 2:
                    if(!empty($baseRes['oodds']))
                    {
                        $hisOdds = $baseRes['oodds'];
                        $oddsArr = explode('!',$hisOdds);
                        $tData = [];
                        foreach($oddsArr as $k=>$v)
                        {
                            $arr = explode('^',$v);
                            //if(!isset($oddsCompany[$arr[0]])) continue;    //过滤欧赔无历史赔率公司
                            $temp = [
                                0 => $arr[0],
                                1 => $arr[0],
                                2 => $arr[2],
                                3 => $arr[3],
                                4 => $arr[4],
                                5 => $arr[5],
                                6 => $arr[6],
                                7 => $arr[7],
                            ];
                            if($arr[0] == 'SB')
                                $temp[8] = '3';
                            else
                                $temp[8] = isset($oddsCompany[$arr[0]])?(string)$oddsCompany[$arr[0]]:'';

                            if(!isset($oddsCompany[$arr[0]]))
                                $tData[] = $temp;
                            else
                                $rData[] = $temp;
                            //$rData[] = $temp;
                        }
                        if(!empty($tData))
                        {
                            foreach($tData as $k2=>$v2)
                            {
                                $rData[] = $v2;
                            }
                        }
                    }
                    break;
                case 3:
                    if(!empty($baseRes['bodds']))
                    {
                        $hisOdds = $baseRes['bodds'];
                        $oddsArr = explode('!',$hisOdds);
                        foreach($oddsArr as $k=>$v)
                        {
                            $arr = explode('^',$v);
                            $temp = [
                                0 => $arr[0],
                                1 => $arr[2],
                                2 => changeExp($arr[3]),
                                3 => $arr[4],
                                4 => $arr[5],
                                5 => changeExp($arr[6]),
                                6 => $arr[7],
                                7 => isset($oddsCompany[$arr[0]])?(string)$oddsCompany[$arr[0]]:'',
                            ];
                            if($arr[0] == '18Bet') $temp[7] = '23';
                            $rData[] = $temp;
                        }
                    }
                    break;
            }
        }
        return $rData;
    }

    /**
     * 根据公司ID获取赔率各公司初盘指数、即时指数
     * @param  int   $gameId  赛事ID
     * @param  int   $type  1,亚赔；2,欧赔；3,大小
     * @return array  赔率数据
     */
    public function getAllOddsNew($gameId ,$type = 1)
    {
        if(empty($gameId)) return false;

        $oddsCompany = C('AOB_COMPANY_ID');
        $fbOddshis = M('fbOddshis');

        $map['game_id'] = (int) $gameId;

        $baseRes = $fbOddshis->field('company_id,ahistory,bhistory,ohistory')->where($map)->select();

        $rData = [];
        $hisOdds = '';
        if(!empty($baseRes))
        {
            $sbData = [];
            switch($type)
            {
                case 1:
                    $oddsGj = ['h'=>0,'a'=>0];
                    foreach($baseRes as $k=>$v)
                    {
                        if(empty($v['ahistory'])) continue;
                        $companyID = $v['company_id'];
                        $oddsArr = explode('!',$v['ahistory']);

                        if(count($oddsArr) == 1)
                        {
                            $endOdds = $oddsArr[0];
                            $endfswOdds = explode('^',$endOdds);
                            $temp = [
                                0 => isset($oddsCompany[$companyID])?(string)$oddsCompany[$companyID]:'',
                                1 => $endfswOdds[0]== null?'':$endfswOdds[0],
                                2 => changeExp($endfswOdds[1]),
                                3 => $endfswOdds[2]== null?'':$endfswOdds[2],
                                4 => $endfswOdds[0] == null?'':$endfswOdds[0],
                                5 => changeExp($endfswOdds[1]),
                                6 => $endfswOdds[2]== null?'':$endfswOdds[2],
                                7 => !empty($companyID)?(string)$companyID:'',
                            ];
                            $tj = $this->abTrend($endfswOdds[1],$endfswOdds[1],$endfswOdds[0],$endfswOdds[0],$endfswOdds[2],$endfswOdds[2]);
                        }
                        else
                        {
                            $endOdds = $oddsArr[0];
                            $endfswOdds = explode('^',$endOdds);
                            $startOdds = end($oddsArr);
                            $startfswOdds = explode('^',$startOdds);

                            if($startOdds == '')
                            {
                                $startOdds = array_pop($oddsArr);
                                $startfswOdds = explode('^',$startOdds);
                            }
                            if(count($startfswOdds) < 4 && count($oddsArr) !=1)
                            {
                                $startOdds = array_pop($oddsArr);
                                $startfswOdds = explode('^',$startOdds);
                            }

                            $temp = [
                                0 => isset($oddsCompany[$companyID])?(string)$oddsCompany[$companyID]:'',
                                1 => $startfswOdds[0]== null?'':$startfswOdds[0],
                                2 => changeExp($startfswOdds[1]),
                                3 => $startfswOdds[2]== null?'':$startfswOdds[2],
                                4 => $endfswOdds[0] == null?'':$endfswOdds[0],
                                5 => changeExp($endfswOdds[1]),
                                6 => $endfswOdds[2]== null?'':$endfswOdds[2],
                                7 => !empty($companyID)?(string)$companyID:'',
                            ];
                            $tj = $this->abTrend($startfswOdds[1],$endfswOdds[1],$startfswOdds[0],$endfswOdds[0],$startfswOdds[2],$endfswOdds[2]);
                        }
                        $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                        $oddsGj['a'] = $oddsGj['a'] + $tj['a'];

                        if($companyID == 3)
                            $sbData = $temp;
                        else
                            $rData[] = $temp;
                    }

                    if(!empty($sbData)) array_unshift($rData,$sbData);
                    //$rData['aobTrend'] = $oddsGj;
                    break;
                case 2:
                    $eurComp = $oddsCompany = C('DB_FB_EUR_COMPANY');
                    $fbEuroodds = M('fbEuroodds');
                    $map['game_id'] = (int) $gameId;

                    $baseRes2 = $fbEuroodds->field('game_id,europe_cname,company_id,from_oddsid,odds_val')->where($map)->select();

                    $oddsGj = ['h'=>['rise'=>0,'equal'=>0,'drop'=>0],'d'=>['rise'=>0,'equal'=>0,'drop'=>0],'a'=>['rise'=>0,'equal'=>0,'drop'=>0]];
                    $sbData = $jcData = [];
                    if(!empty($baseRes2))
                    {
                        foreach($baseRes2 as $k =>$v)
                        {
                            $oddsArr = $startOdds = $endOdds = [];
                            //$oddsArr = explode('!',$hisOdds);
                            $companyID = $v['company_id'];
                            $oddsArr = explode('!',$v['odds_val']);

                            if(count($oddsArr) == 1)
                            {
                                $endOdds = $oddsArr[0];
                                $endfswOdds = explode('^',$endOdds);
                                //客户端使用公司名称字段是1
                                $temp = [
                                    0 => $v['europe_cname'],
                                    1 => $v['europe_cname'],
                                    2 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                                    3 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                                    4 => $endfswOdds[2] == null?'':sprintf("%.2f",$endfswOdds[2]),
                                    5 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                                    6 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                                    7 => $endfswOdds[2] == null?'':sprintf("%.2f",$endfswOdds[2]),
                                    8 => !empty($companyID)?(string)$companyID:'',
                                ];
                                $oddsGj['h']['equal'] = $oddsGj['h']['equal']+2;
                                $oddsGj['d']['equal'] = $oddsGj['d']['equal']+2;
                                $oddsGj['a']['equal'] = $oddsGj['a']['equal']+2;
                            }
                            else
                            {
                                $endOdds = $oddsArr[0];
                                $endfswOdds = explode('^',$endOdds);
                                $startOdds = array_pop($oddsArr);
                                $startfswOdds = explode('^',$startOdds);
                                if(count($startfswOdds) < 4 && count($oddsArr) != 1)
                                {
                                    $startOdds = array_pop($oddsArr);
                                    $startfswOdds = explode('^',$startOdds);
                                }

                                $temp = [
                                    0 => $v['europe_cname'],
                                    1 => $v['europe_cname'],
                                    2 => $startfswOdds[0] == null?'':sprintf("%.2f",$startfswOdds[0]),
                                    3 => $startfswOdds[1] == null?'':sprintf("%.2f",$startfswOdds[1]),
                                    4 => $startfswOdds[2] == null?'':sprintf("%.2f",$startfswOdds[2]),
                                    5 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                                    6 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                                    7 => $endfswOdds[2] == null?'':sprintf("%.2f",$endfswOdds[2]),
                                    8 => !empty($companyID)?(string)$companyID:'',
                                ];
                                if(isset($eurComp[$companyID]))
                                {
                                    $tj = $this->eurTrend($startfswOdds[0],$endfswOdds[0]);
                                    $oddsGj['h']['rise'] = $oddsGj['h']['rise'] + $tj['h']*2;
                                    $oddsGj['h']['equal'] = $oddsGj['h']['equal'] + $tj['d']*2;
                                    $oddsGj['h']['drop'] = $oddsGj['h']['drop'] + $tj['a']*2;
                                    $tj = $this->eurTrend($startfswOdds[1],$endfswOdds[1]);
                                    $oddsGj['d']['rise'] = $oddsGj['d']['rise'] + $tj['h']*2;
                                    $oddsGj['d']['equal'] = $oddsGj['d']['equal'] + $tj['d']*2;
                                    $oddsGj['d']['drop'] = $oddsGj['d']['drop'] + $tj['a']*2;
                                    $tj = $this->eurTrend($startfswOdds[2],$endfswOdds[2]);
                                    $oddsGj['a']['rise'] = $oddsGj['a']['rise'] + $tj['h']*2;
                                    $oddsGj['a']['equal'] = $oddsGj['a']['equal'] + $tj['d']*2;
                                    $oddsGj['a']['drop'] = $oddsGj['a']['drop'] + $tj['a']*2;
                                }

                            }
                            if($v['europe_cname'] == "Lottery Official"){
                                $temp[0] = $temp[1] = '竞彩官方';
                                $jcData = $temp;
                                continue;
                            }

                            if($v['europe_cname'] == "SB")
                            {
                                $sbData = $temp;
                                continue;
                            }

                            $rData[] = $temp;
                        }

                    }
                    if(!empty($sbData)) array_unshift($rData,$sbData);
                    if(!empty($jcData)) array_unshift($rData,$jcData);
                    //$rData['aobTrend'] = $oddsGj;
                    break;
                case 3:
                    $oddsGj = ['h'=>0,'a'=>0];
                    foreach($baseRes as $k=>$v)
                    {
                        if(empty($v['bhistory'])) continue;
                        $companyID = $v['company_id'];
                        $oddsArr = explode('!',$v['bhistory']);

                        if(count($oddsArr) == 1)
                        {
                            $endOdds = $oddsArr[0];
                            $endfswOdds = explode('^',$endOdds);
                            $temp = [
                                0 => isset($oddsCompany[$companyID])?(string)$oddsCompany[$companyID]:'',
                                1 => $endfswOdds[0]== null?'':$endfswOdds[0],
                                2 => changeExp($endfswOdds[1]),
                                3 => $endfswOdds[2]== null?'':$endfswOdds[2],
                                4 => $endfswOdds[0] == null?'':$endfswOdds[0],
                                5 => changeExp($endfswOdds[1]),
                                6 => $endfswOdds[2]== null?'':$endfswOdds[2],
                                7 => !empty($companyID)?(string)$companyID:'',
                            ];
                            $tj = $this->abTrend($endfswOdds[1],$endfswOdds[1],$endfswOdds[0],$endfswOdds[0],$endfswOdds[2],$endfswOdds[2]);
                        }
                        else
                        {
                            $endOdds = $oddsArr[0];
                            $endfswOdds = explode('^',$endOdds);
                            $startOdds = array_pop($oddsArr);
                            $startfswOdds = explode('^',$startOdds);
                            if(count($startfswOdds)< 4 && count($oddsArr) != 1)
                            {
                                $startOdds = array_pop($oddsArr);
                                $startfswOdds = explode('^',$startOdds);
                            }
                            $temp = [
                                0 => isset($oddsCompany[$companyID])?(string)$oddsCompany[$companyID]:'',
                                1 => $startfswOdds[0]== null?'':$startfswOdds[0],
                                2 => changeExp($startfswOdds[1]),
                                3 => $startfswOdds[2]== null?'':$startfswOdds[2],
                                4 => $endfswOdds[0] == null?'':$endfswOdds[0],
                                5 => changeExp($endfswOdds[1]),
                                6 => $endfswOdds[2]== null?'':$endfswOdds[2],
                                7 => !empty($companyID)?(string)$companyID:'',
                            ];
                            $tj = $this->abTrend($startfswOdds[1],$endfswOdds[1],$startfswOdds[0],$endfswOdds[0],$startfswOdds[2],$endfswOdds[2]);
                        }
                        $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                        $oddsGj['a'] = $oddsGj['a'] + $tj['a'];

                        if($companyID == 3)
                            $sbData = $temp;
                        else
                            $rData[] = $temp;
                    }
                    if(!empty($sbData)) array_unshift($rData,$sbData);
                    //$rData['aobTrend'] = $oddsGj;
                    break;
                default:
                    break;
            }
        }
        return $rData;
    }

     /**
     * 根据公司ID获取赔率各公司欧盘历史赔率数据初盘，即时赔率（文件数据源）
     * @param  int   $gameId  赛事ID
     * @param  int   $company  公司名称
     * @return array  赔率数据
     */
    public function getEuroHistory($gameId,$company)
    {
        if(empty($gameId)) return false;

        $rData = [];

        $fbEuroodds = M('fbEuroodds');
        $map['game_id'] = (int) $gameId;
        $map['europe_cname'] = $company;

        $oddsRes = $fbEuroodds->field('game_id,europe_cname,company_id,from_oddsid,odds_val')->where($map)->find();

        if(!empty($oddsRes))
        {
            $oddsArr = explode('!',$oddsRes['odds_val']);
            foreach($oddsArr as $k =>$v)
            {
                $oddsArr = explode('^',$v);
                $otime = substr($oddsArr[3],0,4).'-'.substr($oddsArr[3],4,2).'-'.substr($oddsArr[3],6,2).' '.substr($oddsArr[3],8,2).':'.substr($oddsArr[3],10,2);
                $temp = [
                    0 => sprintf("%.2f",$oddsArr[0]),
                    1 => sprintf("%.2f",$oddsArr[1]),
                    2 => sprintf("%.2f",$oddsArr[2]),
                    3 => $otime,
                ];
                $rData[] = $temp;
            }
            return $rData;
        }

        if(empty($rData))
        {
            $GameFbinfo = D('GameFbinfo');
            $where['game_id'] = $gameId;
            $baseRes = $GameFbinfo->field('id,gtime')->where($where)->find();

            if(!empty($baseRes))
                $date = date('Y',$baseRes['gtime']);
            else
                return null;

            $rData = [];
            $item = $this->data['1x2'];
            $ext = getFileExt($item['mimeType']);
            $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;

            if(is_file($fileName))
            {
                $content = file_get_contents($fileName);
                $data=  explode('var ', $content);
                $oddsHis = array_pop($data);
                $gamelist = cutstr($content,'game=Array("','");');
                $oddslist = cutstr($oddsHis,'("','");');

                $gamelist= explode('","', $gamelist);
                $key='';
                foreach ($gamelist as &$v)
                {
                    $v=  explode('|', $v);
                    $cName = preg_replace("/\((.*?)\)/i","",$v[21]);   //过滤括号里的国家等字
                    $cName = strtolower($cName);

                    if(strtolower($v[2]) == strtolower($company) || strtolower($cName) == strtolower($company) )
                    //if(stripos($v[2] ,$company) !== false || stripos($v[21] ,$company) !== false)
                    {
                        $key=$v[1];
                        break;
                    }
                }

                if(!empty($key))
                {
                    $detail=  explode(';","', $oddslist);

                    $tData = [];
                    foreach ($detail as &$val)
                    {
                        $val=  explode('^', $val);
                        if($key==$val[0]) $tData=  explode(';', $val[1]);
                    }
                    $date = date('Y');
                    $tTime = time();
                    foreach ($tData as $k2=>$v2)
                    {
                        if($v2 == '') continue;
                        $temp = explode('|', $v2);
                        //if($temp != '') $rData[] = explode('|', $v2);

                        $tempTime = $date.'-'.$temp[3];
                        if($tTime < strtotime($tempTime))
                        {
                            $temp[3] = ($date-1).'-'.$temp[3];
                        }
                        else
                        {
                            $temp[3] = $tempTime;
                        }
                        $rData[] = $temp;

                    }
                }
                return $rData;
            }
        }
        return $rData;
    }


    /**
     * 根据赛事ID获取欧赔各公司初盘指数、即时指数
     * @param  int   $gameId  赛事ID
     * @return array  赔率数据
     */
    public function getEuroOddsTwo($gameId)
    {
        if(empty($gameId)) return false;

        $fbMatchodds = M('fbMatchodds');
        $map['game_id'] = (int) $gameId;
        $map['company_id'] = $companyID;

        $baseRes = $fbMatchodds->field('oodds')->where($map)->find();

        $rData = [];
        $hisOdds = '';
        if(!empty($baseRes) && !empty($baseRes['oodds']))
        {
            $hisOdds = $baseRes['oodds'];
            $oddsArr = explode('!',$hisOdds);
            foreach($oddsArr as $k=>$v)
            {
                $arr = explode('^',$v);
                $temp = [
                    0 => $arr[0],
                    1 => $arr[0],
                    2 => $arr[2],
                    3 => $arr[3],
                    4 => $arr[4],
                    5 => $arr[5],
                    6 => $arr[6],
                    7 => $arr[7],
                ];
                $rData[] = $temp;
            }
        }
        return $rData;
    }

     /**
     * 根据公司ID获取数据分析界面数据
     * @param  int   $gameId  赛事ID
     * @return array  赔率数据
     */
    public function getAnalysis($gameId,$lang = 1)
    {
        if(empty($gameId)) return false;
        $GameFbinfo = M('GameFbinfo');
        $baseRes = $GameFbinfo->field('*')->where('game_id = '.$gameId)->find();

        $rData = [];
        if(empty($baseRes)) return $rData;

        if($lang == 1)
            $langKey = 0;
        else
            $langKey = 1;

        $htn = explode(',' ,$baseRes['home_team_name']);
        $hTeamName = $htn[$langKey];
        $atn = explode(',' ,$baseRes['away_team_name']);
        $aTeamName =  $atn[$langKey];
        $utn = explode(',' ,$baseRes['union_name']);
        $unionName = $utn[$langKey];
        $htRank = !empty($baseRes['home_team_rank'])?'['.$baseRes['home_team_rank'].']':'';
        $atRank = !empty($baseRes['away_team_rank'])?'['.$baseRes['away_team_rank'].']':'';

        $fbService = new \Common\Services\FbdataService();

        #赛事基本信息
        $rData[] = ['name'=>'game_info','content'=>[0=> $baseRes['union_id'],1=>$baseRes['home_team_id'],2=>$baseRes['away_team_id']]];

        #联赛积分
        $res = $fbService->getMatchInt($gameId);
        $rankRes = $fbService->teamRank($baseRes['home_team_id'],$baseRes['away_team_id'],$baseRes['union_id'],$baseRes['gtime'],$baseRes['years']);
        $intTemp = [];
        if(!empty($res[0]))
        {
            $intTemp[0] = [0=> $htRank.$hTeamName,1=> '总',2=> (string)$res[0]['total']['total'], 3=> (string)$res[0]['total']['win'],4=> (string)$res[0]['total']['draw'],5=> (string)$res[0]['total']['lose'],6=> (string)$res[0]['total']['get'],7=> (string)$res[0]['total']['miss'],8=> (string)$res[0]['total']['int'], 9=> (string)$rankRes[0]['total'] ];
            $intTemp[1] = [0=> $htRank.$hTeamName,1=> '主',2=> (string)$res[0]['fswHome']['total'], 3=> (string)$res[0]['fswHome']['win'],4=> (string)$res[0]['fswHome']['draw'],5=> (string)$res[0]['fswHome']['lose'],6=> (string)$res[0]['fswHome']['get'],7=> (string)$res[0]['fswHome']['miss'],8=> (string)$res[0]['fswHome']['int'], 9=>(string)$rankRes[0]['home'] ];
            $intTemp[2] = [0=> $htRank.$hTeamName,1=> '客',2=> (string)$res[0]['fswAway']['total'], 3=> (string)$res[0]['fswAway']['win'],4=> (string)$res[0]['fswAway']['draw'],5=> (string)$res[0]['fswAway']['lose'],6=> (string)$res[0]['fswAway']['get'],7=> (string)$res[0]['fswAway']['miss'],8=> (string)$res[0]['fswAway']['int'], 9=>(string)$rankRes[0]['away'] ];
            $intTemp[3] = [0=> $htRank.$hTeamName,1=> '近',2=> (string)$res[0]['fswRecent']['total'], 3=> (string)$res[0]['fswRecent']['win'],4=> (string)$res[0]['fswRecent']['draw'],5=> (string)$res[0]['fswRecent']['lose'],6=> (string)$res[0]['fswRecent']['get'],7=> (string)$res[0]['fswRecent']['miss'],8=> (string)$res[0]['fswRecent']['int'], 9=>''];
            $intTemp[4] = [0=> $atRank.$aTeamName,1=> '总',2=> (string)$res[1]['total']['total'], 3=> (string)$res[1]['total']['win'],4=> (string)$res[1]['total']['draw'],5=> (string)$res[1]['total']['lose'],6=> (string)$res[1]['total']['get'],7=> (string)$res[1]['total']['miss'],8=> (string)$res[1]['total']['int'], 9=>(string)$rankRes[1]['total'] ];
            $intTemp[5] = [0=> $atRank.$aTeamName,1=> '主',2=> (string)$res[1]['fswHome']['total'], 3=> (string)$res[1]['fswHome']['win'],4=> (string)$res[1]['fswHome']['draw'],5=> (string)$res[1]['fswHome']['lose'],6=> (string)$res[1]['fswHome']['get'],7=> (string)$res[1]['fswHome']['miss'],8=> (string)$res[1]['fswHome']['int'], 9=>(string)$rankRes[1]['home'] ];
            $intTemp[6] = [0=> $atRank.$aTeamName,1=> '客',2=> (string)$res[1]['fswAway']['total'], 3=> (string)$res[1]['fswAway']['win'],4=> (string)$res[1]['fswAway']['draw'],5=> (string)$res[1]['fswAway']['lose'],6=>(string) $res[1]['fswAway']['get'],7=> (string)$res[1]['fswAway']['miss'],8=>(string) $res[1]['fswAway']['int'], 9=>(string)$rankRes[1]['away'] ];
            $intTemp[7] = [0=> $atRank.$aTeamName,1=> '近',2=> (string)$res[1]['fswRecent']['total'], 3=> (string)$res[1]['fswRecent']['win'],4=> (string)$res[1]['fswRecent']['draw'],5=> (string)$res[1]['fswRecent']['lose'],6=>(string) $res[1]['fswRecent']['get'],7=> (string)$res[1]['fswRecent']['miss'],8=> (string)$res[1]['fswRecent']['int'], 9=>''];
        }
        if(!empty($res[1]))
        {
             $intTemp[4] = [0=> $atRank.$aTeamName,1=> '总',2=> (string)$res[1]['total']['total'], 3=> (string)$res[1]['total']['win'],4=> (string)$res[1]['total']['draw'],5=> (string)$res[1]['total']['lose'],6=> (string)$res[1]['total']['get'],7=> (string)$res[1]['total']['miss'],8=> (string)$res[1]['total']['int'], 9=>(string)$rankRes[1]['total'] ];
            $intTemp[5] = [0=> $atRank.$aTeamName,1=> '主',2=> (string)$res[1]['fswHome']['total'], 3=> (string)$res[1]['fswHome']['win'],4=> (string)$res[1]['fswHome']['draw'],5=> (string)$res[1]['fswHome']['lose'],6=> (string)$res[1]['fswHome']['get'],7=> (string)$res[1]['fswHome']['miss'],8=> (string)$res[1]['fswHome']['int'], 9=>(string)$rankRes[1]['home'] ];
            $intTemp[6] = [0=> $atRank.$aTeamName,1=> '客',2=> (string)$res[1]['fswAway']['total'], 3=> (string)$res[1]['fswAway']['win'],4=> (string)$res[1]['fswAway']['draw'],5=> (string)$res[1]['fswAway']['lose'],6=>(string) $res[1]['fswAway']['get'],7=> (string)$res[1]['fswAway']['miss'],8=>(string) $res[1]['fswAway']['int'], 9=>(string)$rankRes[1]['away'] ];
            $intTemp[7] = [0=> $atRank.$aTeamName,1=> '近',2=> (string)$res[1]['fswRecent']['total'], 3=> (string)$res[1]['fswRecent']['win'],4=> (string)$res[1]['fswRecent']['draw'],5=> (string)$res[1]['fswRecent']['lose'],6=>(string) $res[1]['fswRecent']['get'],7=> (string)$res[1]['fswRecent']['miss'],8=> (string)$res[1]['fswRecent']['int'], 9=>''];
        }
        if(!empty($intTemp)) $rData[] = ['name'=>'match_integral','content'=>$intTemp];

        #对战历史
        $res = $fbService->getMatchFight($baseRes['home_team_id'],$baseRes['away_team_id'],$baseRes['gtime'] ,$lang);
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                if($k > 9) break;
                $v[1] = $v[16];
                unset($v[16]);
                $res[$k] = $v;
            }
            $rData[] = ['name'=>'match_fight','content'=>$res];
        }

        #近期交战历史
        $res = $fbService->getRecentFight($baseRes['home_team_id'] ,$baseRes['gtime'],$lang);
        $recentTemp = [];
        if(!empty($res))
        {
            $homeTemp = [];
            foreach($res as $k=>$v)
            {
                if($k > 9) break;
                $v[1] = $v[16];
                unset($v[16]);
                array_unshift ($v , $hTeamName);
                $homeTemp[$k] = $v;
            }
            $recentTemp[] = ['name'=>'recent_fight1','content'=>$homeTemp];
        }

        $res = $fbService->getRecentFight($baseRes['away_team_id'] ,$baseRes['gtime'],$lang);
        if(!empty($res))
        {
            $awayTemp = [];
            foreach($res as $k=>$v)
            {
                if($k > 9) break;
                $v[1] = $v[16];
                unset($v[16]);
                array_unshift ($v , $aTeamName);
                $awayTemp[$k] = $v;
            }
            $recentTemp[] = ['name'=>'recent_fight2','content'=>$awayTemp];
        }
        if(!empty($recentTemp)) $rData[] = ['name'=>'recent_fight','content'=>$recentTemp];

        #计统率
        $sdTemp = $fbService->getSkilldataTwo($gameId);
        if(empty($sdTemp))
        {
            $res1 = $fbService->getSkilldata($baseRes['home_team_id'] ,$baseRes['gtime']);
            $res2 = $fbService->getSkilldata($baseRes['away_team_id'] ,$baseRes['gtime']);
            $sdTemp = [];
            if(!empty($res1))
            {
                $res = [];
                foreach($res1 as $k=>$v)
                {
                    $res[] = $v;
                }
                $sdTemp[] = ['name'=>'skill_data1','content'=>$res];
            }
            if(!empty($res2))
            {
                $res = [];
                foreach($res2 as $k=>$v)
                {
                    $res[] = $v;
                }
                $sdTemp[] = ['name'=>'skill_data2','content'=>$res];
            }
        }
        if(!empty($sdTemp)) $rData[] = ['name'=>'skill_data','content'=>$sdTemp];

        #盘路
        $res1 = $fbService->getPanlu($baseRes['home_team_id'] ,$baseRes['union_id'],$baseRes['gtime']);
        $res2 = $fbService->getPanlu($baseRes['away_team_id'] ,$baseRes['union_id'],$baseRes['gtime']);
        $paluTemp = [];
        if(!empty($res1))
        {
            $paluTemp[0] = [0=> $htRank.$hTeamName,1=> '总',2=> (string)$res1[0][0], 3=> (string)$res1[0][1],4=> (string)$res1[0][2],5=> (string)$res1[0][3],6=> ($res1[0][4]*100).'%',7=> (string)$res1[0][5],8=> ($res1[0][6]*100).'%', 9=> (string)$res1[0][7] ,10=> ($res1[0][8]*100).'%'];
            $paluTemp[1] = [0=> $htRank.$hTeamName,1=> '主场',2=> (string)$res1[1][0], 3=> (string)$res1[1][1],4=> (string)$res1[1][2],5=> (string)$res1[1][3],6=> ($res1[1][4]*100).'%',7=> (string)$res1[1][5],8=> ($res1[1][6]*100).'%', 9=> (string)$res1[1][7] ,10=> ($res1[1][8]*100).'%'];
            $paluTemp[2] = [0=> $htRank.$hTeamName,1=> '客场',2=> (string)$res1[2][0], 3=> (string)$res1[2][1],4=> (string)$res1[2][2],5=> (string)$res1[2][3],6=> ($res1[2][4]*100).'%',7=> (string)$res1[2][5],8=> ($res1[2][6]*100).'%', 9=> (string)$res1[2][7] ,10=> ($res1[2][8]*100).'%'];
            $str1 = '';
            $ratio = [0=>0,1=>0];
            foreach($res1[3] as $k=>$v)
            {
                $ratio[0]++;
                if($v == 1)
                {
                    $ratio[1]++;
                    $str1 .= !empty($str1)?' 贏':' 贏';
                }
                else if ($v == -1)
                {
                    $str1 .= !empty($str1)?' 输':' 输';
                }
                else
                {
                    $str1 .= !empty($str1)?' 走':' 走';
                }
            }
            $str2= '';
            foreach($res1[4] as $k=>$v)
            {
                if($v == 1)
                    $str2 .= !empty($str2)?' 大':' 大';
                else
                    $str2 .= !empty($str2)?' 小':' 小';
            }
            $sTemp = round($ratio[1]/$ratio[0],2)*100;
            $paluTemp[3] = [0=> $htRank.$hTeamName,1=> '近6场',2=> (string)$ratio[0], 3=> $str1,4=> $sTemp.'%',5=> '查看',6=> $str2,7=> '',8=> '', 9=> '' ,10=> ''];
        }
        if(!empty($res2))
        {
            $paluTemp[4] = [0=> $atRank.$aTeamName,1=> '总',2=> (string)$res2[0][0], 3=> (string)$res2[0][1],4=> (string)$res2[0][2],5=> (string)$res2[0][3],6=> ($res2[0][4]*100).'%',7=> (string)$res2[0][5],8=> ($res2[0][6]*100).'%', 9=> (string)$res2[0][7] ,10=> ($res2[0][8]*100).'%'];
            $paluTemp[5] = [0=> $atRank.$aTeamName,1=> '主场',2=> (string)$res2[1][0], 3=> (string)$res2[1][1],4=> (string)$res2[1][2],5=> (string)$res2[1][3],6=> ($res2[1][4]*100).'%',7=> (string)$res2[1][5],8=> ($res2[1][6]*100).'%', 9=> (string)$res2[1][7] ,10=> ($res2[1][8]*100).'%'];
            $paluTemp[6] = [0=> $atRank.$aTeamName,1=> '客场',2=> (string)$res2[2][0], 3=> (string)$res2[2][1],4=> (string)$res2[2][2],5=> (string)$res2[2][3],6=> ($res2[2][4]*100).'%',7=> (string)$res2[2][5],8=> ($res2[2][6]*100).'%', 9=> (string)$res2[2][7] ,10=> ($res2[2][8]*100).'%'];
            $str1 = '';
            $ratio = [0=>0,1=>0];
            foreach($res2[3] as $k=>$v)
            {
                $ratio[0]++;
                if($v == 1)
                {
                    $ratio[1]++;
                    $str1 .= !empty($str1)?' 贏':' 贏';
                }
                else if ($v == -1)
                {
                    $str1 .= !empty($str1)?' 输':' 输';
                }
                else
                {
                    $str1 .= !empty($str1)?' 走':' 走';
                }
            }
            $str2= '';
            foreach($res2[4] as $k=>$v)
            {
                if($v == 1)
                {
                    $str2 .= !empty($str2)?' 大':' 大';
                }
                else
                {
                    $str2 .= !empty($str2)?' 小':' 小';
                }
            }
            $sTemp = round($ratio[1]/$ratio[0],2)*100;
            $paluTemp[7] = [0=> $atRank.$aTeamName,1=> '近6场',2=> (string)$ratio[0], 3=> $str1,4=> $sTemp.'%',5=> '查看',6=> $str2,7=> '',8=> '', 9=> '' ,10=> ''];
        }
        if(!empty($paluTemp)) $rData[] = ['name'=>'match_panlu','content'=>$paluTemp];

        #未来三场
        $res1 = $fbService->getFutureThree($baseRes['home_team_id'] ,$baseRes['gtime'],$lang);
        $res2 = $fbService->getFutureThree($baseRes['away_team_id'] ,$baseRes['gtime'],$lang);
        $three = [];
        if(!empty($res1))
        {
            foreach($res1 as $k=>$v)
            {
                $three[] = $v;
            }
        }
        if(!empty($res2))
        {
            foreach($res2 as $k=>$v)
            {
                $three[] = $v;
            }
        }
        if(!empty($three)) $rData[] = ['name'=>'match_three','content'=>$three];

        return $rData;
    }

	/**
	 * 根据公司ID获取数据分析界面数据
	 * @param  int $gameId 赛事ID
	 * @param  int $lang 语言ID(1是简体，2是繁体)
	 * @param int $is_corner 是否包含角球数据
	 * @return array  数据
	 */
    public function getAnaForFile($gameId,$lang = 1,$is_corner = 0)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $allData= [];
	    ($lang == 1) ? $langKey = 0 : $langKey = 1;

	    $mongodb = mongoService();
	    $baseRes = $mongodb->select('fb_game',['game_id'=> $gameId],
		    ['game_start_timestamp',  'home_team_name','home_team_rank', 'home_team_id',
			    'union_id', 'away_team_name', 'away_team_rank', 'away_team_id', 'game_analysis_mobi', 'game_analysis_web_qt.past_match_data',
			    'match_odds_m_bigsmall', 'match_odds_m_asia', 'match_odds', 'union_color','game_analysis_web_qt.game_layoff','game_analysis_web_qt.recent_match',
			    'corner_sb'])[0];

	    if(!empty($baseRes))
            $date = date('Y',$baseRes['game_start_timestamp']);
        else
            return $allData;

        // 技术统计数据
	    $statistics = $baseRes['game_analysis_mobi'];
	    $match_data = $baseRes['game_analysis_web_qt']['past_match_data'];
        $game_layoff = $baseRes['game_analysis_web_qt']['game_layoff'];
	    $recent_match_data = $baseRes['game_analysis_mobi']['recent_match'];
	    $recent_cp_match_data = $baseRes['game_analysis_web_qt']['recent_match'];
	    $homeTeamName = $baseRes['home_team_name'][$langKey];
	    $awayTeamName = $baseRes['away_team_name'][$langKey];
	    $homeTeamId = $baseRes['home_team_id'];
	    $awayTeamId = $baseRes['away_team_id'];
	    $union_color = $baseRes['union_color'];


        //赛事信息
	    $game_info['name'] = 'game_info';
	    $game_info['content'][] = (string) $baseRes['union_id'];
	    $game_info['content'][] = (string) $baseRes['home_team_id'];
	    $game_info['content'][] = (string) $baseRes['away_team_id'];
	    $allData[] = $game_info;


        //SB赔率
	    $sb_asia_temp = $baseRes['match_odds_m_asia'][3];
	    $sb_bigsmall_temp = $baseRes['match_odds_m_bigsmall'][3];
	    $sb_europ_temp = $baseRes['match_odds'][3];

	    //读取亚赔
	    $sb_asia[] = NullString($sb_asia_temp[0]);
	    $sb_asia[] = NullString(changeSnExpTwo($sb_asia_temp[1]));
	    $sb_asia[] = NullString($sb_asia_temp[2]);
	    $sb_asia[] = NullString($sb_asia_temp[3]);
	    $sb_asia[] = NullString(changeSnExpTwo($sb_asia_temp[4]));
	    $sb_asia[] = NullString($sb_asia_temp[5]);

	    // 读取欧赔
	    $sb_europ[] = NullString($sb_europ_temp[6]);
	    $sb_europ[] = NullString($sb_europ_temp[7]);
	    $sb_europ[] = NullString($sb_europ_temp[8]);
	    $sb_europ[] = NullString($sb_europ_temp[9]);
	    $sb_europ[] = NullString($sb_europ_temp[10]);
	    $sb_europ[] = NullString($sb_europ_temp[11]);

	    //读取大小
	    $sb_bigsmall[] = NullString($sb_bigsmall_temp[0]);
	    $sb_bigsmall[] = NullString(changeSnExpTwo($sb_bigsmall_temp[1]));
	    $sb_bigsmall[] = NullString($sb_bigsmall_temp[2]);
	    $sb_bigsmall[] = NullString($sb_bigsmall_temp[3]);
	    $sb_bigsmall[] = NullString(changeSnExpTwo($sb_bigsmall_temp[4]));
	    $sb_bigsmall[] = NullString($sb_bigsmall_temp[5]);

	    $sb_odds['name'] = 'sbOdds';
	    $sb_odds['content'][] = $sb_asia;
	    $sb_odds['content'][] = $sb_europ;
	    $sb_odds['content'][] = $sb_bigsmall;

	    if($is_corner){
		    //5.2版本以上添加角球赔率
		    $cornerOdds = $baseRes['corner_sb'][1][0];
		    if (!empty($cornerOdds)) {
			    $sb_odds['content'][] = $cornerOdds;
		    } else {
		    	// 如果corner_sb 不存在 那么存放空数组
		    	$sb_odds['content'][] = ['', '', '', '', '', ''];
		    }
	    }
	    if (!(nullArrayToBool($sb_asia) && nullArrayToBool($sb_europ) && nullArrayToBool($sb_bigsmall))) {
	        $allData[] = $sb_odds;
	    }

	    // 联赛积分排名
	    $match_integral_temp = $statistics['league_rank'];
	    $home_match_integral= $this->getLeagueRank($match_integral_temp['home_team'], $homeTeamName);
	    $away_match_integral= $this->getLeagueRank($match_integral_temp['away_team'], $awayTeamName);
	    $match_integral['name'] = 'match_integral';
	    if (!empty($home_match_integral)) {
		    $match_integral['content'] = array_merge($home_match_integral, $away_match_integral);
		    $allData[] = $match_integral;
	    }


	    //历史交战
	    $recent_combat = $statistics['recent_combat'];
	    $match_fight['name'] = 'match_fight';
	    if (!empty($match_data)) {
		    $match_fight_temp = $this->getMatchFight($recent_combat, $match_data, $homeTeamId);
		    $match_fight['content'] = $match_fight_temp;
		    if (!empty($match_fight_temp)) {
			    $allData[] = $match_fight;
		    }
	    }


	    //近期交战
	    $recent_fight =[];
	    $recent_fight1_temp = $this->getWebRecentCombat($recent_match_data['home_team'], $recent_cp_match_data['home_team'],$homeTeamName, $union_color);
	    $recent_fight2_temp = $this->getWebRecentCombat($recent_match_data['away_team'], $recent_cp_match_data['guest_team'],$awayTeamName, $union_color);
	    if (!empty($recent_fight1_temp)) {
		    $recent_fight1['name'] = 'recent_fight1';
		    $recent_fight1['content'] = $recent_fight1_temp;
		    $recent_fight['content'][] = $recent_fight1;
	    }
	    if (!empty($recent_fight2_temp)) {
		    $recent_fight2['name'] = 'recent_fight2';
		    $recent_fight2['content'] = $recent_fight2_temp;
		    $recent_fight['content'][] = $recent_fight2;
	    }
	    if (!empty($recent_fight1_temp)) {
		    $recent_fight['name'] = 'recent_fight';
		    $allData[] = $recent_fight;
	    }


	    //伤停情况
	    $layoff_temp = $statistics['layoff'];
	    $layoff = [];
	    $Home_S = $this->getSt($layoff_temp['home_team']);
	    $Away_S = $this->getSt($layoff_temp['away_team']);
        if (!empty($Home_S)) {
	        $layoff['content']['Home_S'] =  $Home_S;
        }
        if (!empty($Away_S)) {
	        $layoff['content']['Away_S'] = $Away_S;
        }
        if (!empty($layoff)) {
	        $layoff['name'] = "St";
	        $allData[] = $layoff;
        }
        //帶球員id的傷停數據
	    if (!empty($layoff)) {
		    $layoff = [];
		    $layoff['content']['Home_S'] = $game_layoff['home_team']['layoff'];
		    $layoff['content']['Away_S'] = $game_layoff['away_team']['layoff'];
		    $layoff['name'] = "StHaveId";
		    $allData[] = $layoff;
	    }

        //数据对比
	    $data_compare = $statistics['data_compare'];
	    $home_compare_temp = $data_compare['listHome'];
	    $away_compare_temp  = $data_compare['away_team'];
	    $home_compare = $this->getCompareData($home_compare_temp);
	    $away_compare = $this->getCompareData($away_compare_temp);
	    $Compare['name'] = "Compare";
	    if (!(empty($home_compare) && empty($away_compare))) {
		    $Compare['content'][] = $home_compare;
		    $Compare['content'][] = $away_compare;
		    $allData[] = $Compare;
	    }


	    //裁判统计
	    $referee_info_temp = $statistics['referee_info'];
	    if (!empty($referee_info_temp['RefereeNameCn'])) {
		    $data[] = (string) $referee_info_temp['RefereeWin_h'];
		    $data[] = (string) $referee_info_temp['RefereeDraw_h'];
		    $data[] = (string) $referee_info_temp['RefereeLoss_h'];
		    $data[] = (string) $referee_info_temp['RefereeWin_g'];
		    $data[] = (string) $referee_info_temp['RefereeDraw_g'];
		    $data[] = (string) $referee_info_temp['RefereeLoss_g'];
		    $data[] = (string) $referee_info_temp['RefereeNameCn'];
		    $data[] = (string) $referee_info_temp['RefereeNameBig'];
		    $data[] = (string) $referee_info_temp['RefereeNameEn'];
		    $data[] = (string) $referee_info_temp['WinPanPrecent'];
		    $data[] = (string) $referee_info_temp['YellowAvg'];
		    $Referee['name'] = 'Referee';
		    $Referee['content'] = $data;
		    $allData[] = $Referee;
	    }

	    //相同历史盘口
	    $same_odd_data = $statistics['same_odd'];
	    $same_odd_home = $same_odd_data['home_team'];
	    $same_odd_away = $same_odd_data['away_team'];
	    $sameExp['name'] = 'sameExp';
	    $same_odd_home_data = $this->getSameOdd($same_odd_home);
	    $same_odd_away_data = $this->getSameOdd($same_odd_away);
	    if(!(empty($same_odd_away_data) && empty($same_odd_away_data))) {
		    $sameExp['content'][] = $same_odd_home_data;
		    $sameExp['content'][] = $same_odd_away_data;
		    $allData[] = $sameExp;
	    }


	    //澳彩推荐
	    $confidence_index_temp = $statistics['confidence_index'];
	    if (!empty($confidence_index_temp)) {
		    $data = explode('^', $confidence_index_temp);
		    $home_trend[] = (string) $data[0];
		    $home_trend[] = (string) $data[1];
		    $home_trend[] = (string) $data[2];
		    $away_trend[] = (string) $data[3];
		    $away_trend[] = (string) $data[4];
		    $away_trend[] = (string) $data[5];
		    $viewpoint = (string) $data[6];
		    $match_Recommend['name'] = 'match_Recommend';
		    $match_Recommend['content']['trend'][] = $home_trend;
		    $match_Recommend['content']['trend'][] = $away_trend;
		    $match_Recommend['content']['viewpoint'] = explode("，", $viewpoint, 2);
		    $allData[] = $match_Recommend;
	    }


        //技术统计 对应mongo数据的变量命名
	    $referee_info = $statistics['referee_info'];
	    if (!empty($referee_info)) {
		    $skill_data = [];
		    $skill_data['name'] = "skill_data";
		    //主队技术统计
		    $referee_data_h['name'] = 'skill_data1';
		    $referee_data_h['content'][] = $this->getStatistics($referee_info, 'Goals', 'h');
		    $referee_data_h['content'][] = $this->getStatistics($referee_info, 'LossGoals', 'h');
		    $referee_data_h['content'][] = $this->getStatistics($referee_info, 'Corner', 'h');
		    $referee_data_h['content'][] = $this->getStatistics($referee_info, 'Yellow', 'h');
		    $referee_data_h['content'][] = $this->getStatistics($referee_info, 'Fouls', 'h');
		    $referee_data_h['content'][] = $this->getStatistics($referee_info, 'ControlPrecent', 'h');
		    //客队技术统计
		    $referee_data_g['name'] = 'skill_data2';
		    $referee_data_g['content'][] = $this->getStatistics($referee_info, 'Goals', 'g');
		    $referee_data_g['content'][] = $this->getStatistics($referee_info, 'LossGoals', 'g');
		    $referee_data_g['content'][] = $this->getStatistics($referee_info, 'Corner', 'g');
		    $referee_data_g['content'][] = $this->getStatistics($referee_info, 'Yellow', 'g');
		    $referee_data_g['content'][] = $this->getStatistics($referee_info, 'Fouls', 'g');
		    $referee_data_g['content'][] = $this->getStatistics($referee_info, 'ControlPrecent', 'g');
		    $skill_data['content'][] = $referee_data_h;
		    $skill_data['content'][] = $referee_data_g;
		    $allData[] = $skill_data;
	    }



	    //联赛盘路
	    $referee_info_temp = $statistics['odd_trend'];
	    $home_info = $this->getReferee($referee_info_temp['home_team'], $homeTeamName);
	    $away_info = $this->getReferee($referee_info_temp['away_team'], $awayTeamName);
	    $referee_odd_trend_a =  $referee_odd_trend_h = [];
	    if (!empty($home_info)) {
		    $referee_odd_trend_h = $home_info;
	    }
	    if (!empty($away_info)) {
		    $referee_odd_trend_a = $away_info;
	    }
	    // 当数据队伍不全时隐藏
	    if (!empty($referee_odd_trend_h)) {
		    $referee_odd_trend['name'] = 'match_panlu';
		    $referee_odd_trend['content'] = array_merge($referee_odd_trend_h, $referee_odd_trend_a);
		    $allData[] = $referee_odd_trend;
	    }


	    //最近三场
	    $referee_info = $statistics['future_three'];
	    $home_data = $this->future_three_data($referee_info['home_team'], $homeTeamId, $homeTeamName);
	    $away_data = $this->future_three_data($referee_info['away_team'], $awayTeamId, $awayTeamName);
	    $match_three['content'] = array_merge($home_data, $away_data);
	    // 当最近三场不全时隐藏
	    if (!empty($home_data)) {
		    $match_three['name'] = 'match_three';
		    $allData[] = $match_three;
	    }

	    return $allData;


        /*
         *
         mysql 源
        $item = $this->data['analysis'];
        $ext = getFileExt($item['mimeType']);
        $fbService = new \Common\Services\FbdataService();
        $langFlag = false;
        if($lang == 1)
        {
            $fileName1 = DataPath.$item['savePath'].$date.'/'.$gameId.'cn'.$ext;
            if(is_file($fileName1))
            {
                $fileName = $fileName1;
            }
            else
            {
                $langFlag = true;
                $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;
            }
        }
        else
        {
            $fileName2 = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;
            if(is_file($fileName2))
            {
                $fileName = $fileName2;
            }
            else
            {
                $langFlag = true;
                $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.'cn'.$ext;
            }
        }

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();

            if($lang == 1 && $langFlag !== true)
            {
                $aData = $disposeData->analysisAppCn($content);
            }
            else
            {
                $aData = $disposeData->analysisAppNokey($content);
            }

            if($aData !== false)
            {
                vendor('chinese_conversion.convert');

                #赛事基本信息
                $rData[] = ['name'=>'game_info','content'=>[0=> (string) $baseRes['union_id'],1=> (string) $baseRes['home_team_id'],2=> (string)$baseRes['away_team_id']]];

                #计统率
                $sdTemp = $fbService->getSkilldataTwo($gameId);
                if(empty($sdTemp))
                {
                    $res1 = $fbService->getSkilldata($baseRes['home_team_id'] ,$baseRes['game_start_timestamp']);
                    $res2 = $fbService->getSkilldata($baseRes['away_team_id'] ,$baseRes['game_start_timestamp']);
                    $sdTemp = [];
                    if(!empty($res1))
                    {
                        $res = [];
                        foreach($res1 as $k=>$v)
                        {
                            $res[] = $v;
                        }
                        $sdTemp[] = ['name'=>'skill_data1','content'=>$res];
                    }
                    if(!empty($res2))
                    {
                        $res = [];
                        foreach($res2 as $k=>$v)
                        {
                            $res[] = $v;
                        }
                        $sdTemp[] = ['name'=>'skill_data2','content'=>$res];
                    }
                }
                if(!empty($sdTemp)) $skill_data = ['name'=>'skill_data','content'=>$sdTemp];

				# TODO http://61.143.224.154:8071/phone/analysis/1/48/big/1488956.htm?an=iosQiuTan&av=5.7.1&from=2&r=1527587286
	            # 来自球探app的数据
                #伤停情况、数据对比、裁判统计、赛前情报、相同历史盘口
                $filePath = DataPath."football/analysis_qtapp/";
                $fN = $filePath.substr($gameId,0,1).'/'.substr($gameId,1,2).'/'.$gameId.'_app.txt';

                if(is_file($fN))
                {
                    $txt = file_get_contents($fN);
                    $tArr = explode('$$',$txt);

                    $sameExp = $PreMatchInfo = $Compare = $St = $Referee = [];
                    foreach($tArr as $tk => $tv)
                    {
                        switch($tk)
                        {
                            case 12:
                                #相同历史盘口（主）
                                $temp = explode('!',$tv);
                                if(!empty($temp[0]))
                                {
                                    $aTemp = [];
                                    foreach($temp as $tk2 => $tv2)
                                    {
                                        $temp2 = explode('^',$tv2);
                                        $temp2[4] = changeExp($temp2[4]);
                                        $aTemp[] = $temp2;
                                    }
                                    $sameExp[0] = $aTemp;
                                }
                                else
                                {
                                    $sameExp[0] = [];
                                }
                                break;
                            case 13:
                                #相同历史盘口（客）
                                $temp = explode('!',$tv);
                                if(!empty($temp[0]))
                                {
                                    $aTemp = [];
                                    foreach($temp as $tk2 => $tv2)
                                    {
                                        $temp2 = explode('^',$tv2);
                                        $temp2[4] = changeExp($temp2[4]);
                                        $aTemp[] = $temp2;
                                    }
                                    $sameExp[1] = $aTemp;
                                }
                                else
                                {
                                    $sameExp[1] = [];
                                }
                                break;
                            case 16:
                                #数据对比
                                $temp = json_decode($tv,true);
                                if(!empty($temp))
                                {
                                    $aTemp = [];
                                    foreach($temp['listHome'] as $tk2 => $tv2)
                                    {
                                        $temp2 = [
                                            0  => (string)$tv2['AvgObtain'],    //
                                            1  => (string)$tv2['DrawScale'],    //
                                            2  => (string)$tv2['Lose'],    //
                                            3  => (string)$tv2['LoseScale'],    //
                                            4  => (string)$tv2['Net'],    //
                                            5  => (string)$tv2['Obtain'],    //
                                            6  => (string)$tv2['TeamID'],    //
                                            7  => (string)$tv2['TotalMatch'],    //
                                            8  => (string)$tv2['WinScale'],    //
                                        ];
                                        $aTemp[] = $temp2;
                                    }
                                    $Compare[0] = $aTemp;
                                    $aTemp = [];
                                    foreach($temp['listAway'] as $tk2 => $tv2)
                                    {
                                        $temp2 = [
                                            0  => (string)$tv2['AvgObtain'],    //
                                            1  => (string)$tv2['DrawScale'],    //
                                            2  => (string)$tv2['Lose'],    //
                                            3  => (string)$tv2['LoseScale'],    //
                                            4  => (string)$tv2['Net'],    //
                                            5  => (string)$tv2['Obtain'],    //
                                            6  => (string)$tv2['TeamID'],    //
                                            7  => (string)$tv2['TotalMatch'],    //
                                            8  => (string)$tv2['WinScale'],    //
                                        ];
                                        $aTemp[] = $temp2;
                                    }
                                    $Compare[1] = $aTemp;
                                }
                                break;
                            case 18:
                                #伤停情况 "Home_S":主伤员,"Guest_S":客伤员,"Home_T":主停员,"Guest_T":客停员
                                $temp = json_decode($tv,true);
                                if(!empty($temp['Home_S']))
                                {
                                    $aTemp = [];
                                    foreach($temp['Home_S'] as $tk2 => $tv2)
                                    {
                                        $temp2 = [
                                            0  => $tv2['Num'],    //
                                            1  => $tv2['Name'],    //
                                            2  => $tv2['NameJ'],    //
                                            3  => $tv2['NameF'],    //
                                            4  => $tv2['NameSB'],    //
                                        ];
                                        $aTemp[] = $temp2;
                                    }
                                    $St['Home_S'] = $aTemp;
                                }
                                if(!empty($temp['Guest_S']))
                                {
                                    $aTemp = [];
                                    foreach($temp['Guest_S'] as $tk2 => $tv2)
                                    {
                                        $temp2 = [
                                            0  => $tv2['Num'],    //
                                            1  => $tv2['Name'],    //
                                            2  => $tv2['NameJ'],    //
                                            3  => $tv2['NameF'],    //
                                            4  => $tv2['NameSB'],    //
                                        ];
                                        $aTemp[] = $temp2;
                                    }
                                    $St['Away_S'] = $aTemp;
                                }
                                if(!empty($temp['Home_T']))
                                {
                                    $aTemp = [];
                                    foreach($temp['Home_T'] as $tk2 => $tv2)
                                    {
                                        $temp2 = [
                                            0  => $tv2['Num'],    //
                                            1  => $tv2['Name'],    //
                                            2  => $tv2['NameJ'],    //
                                            3  => $tv2['NameF'],    //
                                            4  => $tv2['NameSB'],    //
                                        ];
                                        $aTemp[] = $temp2;
                                    }
                                    $St['Home_T'] = $aTemp;
                                }
                                if(!empty($temp['Guest_T']))
                                {
                                    $aTemp = [];
                                    foreach($temp['Guest_T'] as $tk2 => $tv2)
                                    {
                                        $temp2 = [
                                            0  => $tv2['Num'],    //
                                            1  => $tv2['Name'],    //
                                            2  => $tv2['NameJ'],    //
                                            3  => $tv2['NameF'],    //
                                            4  => $tv2['NameSB'],    //
                                        ];
                                        $aTemp[] = $temp2;
                                    }
                                    $St['Away_T'] = $aTemp;
                                }
                                break;
                            case 21:
                                $temp = json_decode($tv,true);
                                if(!empty($temp))
                                {
                                    if($temp['RefereeWin_h'] == null && $temp['RefereeDraw_h'] == null && $temp['RefereeLoss_h'] == null && $temp['RefereeWin_g'] == null && $temp['RefereeDraw_g'] == null && $temp['RefereeLoss_g'] == null && $temp['RefereeNameCn'] == null && $temp['RefereeNameBig'] == null && $temp['RefereeNameEn'] == null && $temp['WinPanPrecent'] == null && $temp['YellowAvg'] == null)
                                    {
                                        $Referee = [];
                                    }
                                    else
                                    {
                                        $Referee = [
                                            0  => $temp['RefereeWin_h'] === null?'':(string)$temp['RefereeWin_h'],    //
                                            1  => $temp['RefereeDraw_h']=== null?'':(string)$temp['RefereeDraw_h'],    //
                                            2  => $temp['RefereeLoss_h']=== null?'':(string)$temp['RefereeLoss_h'],    //
                                            3  => $temp['RefereeWin_g']=== null?'':(string)$temp['RefereeWin_g'],    //
                                            4  => $temp['RefereeDraw_g']=== null?'':(string)$temp['RefereeDraw_g'],    //
                                            5  => $temp['RefereeLoss_g']=== null?'':(string)$temp['RefereeLoss_g'],    //
                                            6  => $temp['RefereeNameCn']=== null?'':$temp['RefereeNameCn'],    //
                                            7  => $temp['RefereeNameBig']=== null?'':$temp['RefereeNameBig'],    //
                                            8  => $temp['RefereeNameEn']=== null?'':$temp['RefereeNameEn'],    //
                                            9  => $temp['WinPanPrecent']=== null?'':(string)$temp['WinPanPrecent'],    //
                                            10  => $temp['YellowAvg']=== null?'':(string)$temp['YellowAvg'],    //
                                        ];
                                    }
                                }
                                break;
                            case 22:
                                $temp = json_decode($tv,true);
                                $PreMatchInfo = $temp['PreMatchInfo'];
                                break;
                        }
                    }

                }

                $match_integral = $match_fight = $recent_fight = $match_panlu = $match_three = $match_Recommend = [];
                foreach($aData as $k=>&$v)
                {
                    if($langFlag)
                    {
                        if($v['name'] == 'match_integral')
                        {
                            foreach($v['content'] as $k2=>&$v2)
                            {
                                foreach($v2 as &$v3)
                                {
                                    if($lang == 1)
                                        $v3 = zhconversion_hans($v3);
                                    else
                                        $v3 = zhconversion_hant($v3);
                                }
                            }
                            $match_integral = $v;
                        }
                        if($v['name'] == 'match_fight')
                        {
                            foreach($v['content'] as $k2=>&$v2)
                            {
                                foreach($v2 as &$v3)
                                {
                                    if($lang == 1)
                                        $v3 = zhconversion_hans($v3);
                                    else
                                        $v3 = zhconversion_hant($v3);
                                }
                            }
                            $match_fight = $v;
                        }
                        if($v['name'] == 'recent_fight')
                        {
                            foreach($v['content'] as $k2=>&$v2)
                            {
                                foreach($v2['content'] as &$v3)
                                {
                                    foreach($v3 as &$v4)
                                    {
                                        if ($lang == 1)
                                            $v4 = zhconversion_hans($v4);
                                        else
                                            $v4 = zhconversion_hant($v4);
                                    }
                                }
                            }
                            $recent_fight = $v;
                        }
                        if($v['name'] == 'match_panlu')
                        {
                            foreach($v['content'] as $k2=>&$v2)
                            {
                                foreach($v2 as &$v3)
                                {
                                    if($lang == 1)
                                        $v3 = zhconversion_hans($v3);
                                    else
                                        $v3 = zhconversion_hant($v3);
                                }
                            }
                            $match_panlu = $v;
                        }
                        if($v['name'] == 'match_three') {
                            foreach ($v['content'] as $k2 => &$v2) {
                                foreach ($v2 as &$v3) {
                                    if ($lang == 1)
                                        $v3 = zhconversion_hans($v3);
                                    else
                                        $v3 = zhconversion_hant($v3);
                                }
                            }
                            $match_three = $v;
                        }
                        if($v['name'] == 'match_Recommend')
                        {
                            foreach($v['content']['trend'] as $k2=>&$v2)
                            {
                                foreach($v2 as &$v3)
                                {
                                    if($lang == 1)
                                        $v3 = zhconversion_hans($v3);
                                    else
                                        $v3 = zhconversion_hant($v3);
                                }
                            }
                            foreach($v['content']['viewpoint'] as $k2=>&$v2)
                            {
                                if($lang == 1)
                                    $v2 = zhconversion_hans($v2);
                                else
                                    $v2 = zhconversion_hant($v2);
                            }
                            $match_Recommend = $v;
                        }
                    }
                    else
                    {
                        if($v['name'] == 'match_integral') $match_integral = $v;
                        if($v['name'] == 'match_fight') $match_fight = $v;
                        if($v['name'] == 'recent_fight') $recent_fight = $v;
                        if($v['name'] == 'match_panlu') $match_panlu = $v;
                        if($v['name'] == 'match_three') $match_three = $v;
                        if($v['name'] == 'match_Recommend') $match_Recommend = $v;

                        if(!empty($sameExp))
                        {

                            foreach($sameExp as $k2=>&$v2)
                            {
                                foreach($v2 as $k3 => &$v3)
                                {
                                    foreach($v3 as $k4 =>&$v4)
                                    {
                                        if($lang == 1)
                                            $v4 = zhconversion_hans($v4);
                                        else
                                            $v4 = zhconversion_hant($v4);
                                    }
                                }
                            }
                        }

                        if(!empty($PreMatchInfo))
                        {
                            foreach($PreMatchInfo['AwayInfo']['ContentList'] as $k2=>&$v2)
                            {
                                if($lang == 1)
                                    $v2 = zhconversion_hans($v2);
                                else
                                    $v2 = zhconversion_hant($v2);
                            }
                            foreach($PreMatchInfo['HomeInfo']['ContentList'] as $k2=>&$v2)
                            {
                                if($lang == 1)
                                    $v2 = zhconversion_hans($v2);
                                else
                                    $v2 = zhconversion_hant($v2);
                            }
                        }
                    }
                }
                $sbRes = M('FbOdds')->field('exp_value')->where(['game_id'=>$gameId,'company_id'=>3])->find();

                if(!empty($sbRes))
                {
                    $oArr = oddsChArr($sbRes['exp_value']);
                    $sbOdds['name'] = 'sbOdds';
                    $sbOdds['content'] = [
                        0 => [$oArr[0][0],changeExp($oArr[0][1]),$oArr[0][2],$oArr[0][3],changeExp($oArr[0][4]),$oArr[0][5]],
                        1 => [$oArr[1][0],$oArr[1][1],$oArr[1][2],$oArr[1][3],$oArr[1][4],$oArr[1][5]],
                        2 => [$oArr[2][0],changeExp($oArr[2][1]),$oArr[2][2],$oArr[2][3],changeExp($oArr[2][4]),$oArr[2][5]]
                    ];
                    // xxxxxx 新增 功能
                    if($is_corner){
                        //5.2版本以上添加角球赔率
                        $mongodb = mongoService();
                        $cornerArr = $mongodb->select('fb_game',['game_id'=>$gameId],['corner_sb']);
                        $cornerOdds = $cornerArr[0]['corner_sb'][1][0];
                        $sbOdds['content'][] = $cornerOdds ? : [];
                    }
                    $rData[] = $sbOdds;
                }

                #对阵详情-分析页面的分类排序顺序分别为：联赛积分排名、历史交战、近期交战、伤停情况、数据对比、裁判统计、相同历史盘口、独家解盘、技统率、联赛盘路、未来三场
                #5.0 分析数据顺序 1、SB赔率  2、积分排名  3、历史交战  4、近期交战  5、伤停情况  6、数据对比  7、裁判统计  8、相同历史盘口  9、澳彩推荐  10、技术统计  11、联赛盘路  12、未来三场[]
                if(!empty($match_integral)) $rData[] = $match_integral;
                if(!empty($match_fight)) $rData[] = $match_fight;
                if(!empty($recent_fight)) $rData[] = $recent_fight;
                //伤停
                if(!empty($St))
                {
                    $aaTemp = [
                        'name'     => 'St',
                        'content'  => $St,
                    ];
                    $rData[] = $aaTemp;
                }
                //数据对比
                if(!empty($Compare))
                {
                    $aaTemp = [
                        'name'     => 'Compare',
                        'content'  => $Compare,
                    ];
                    $rData[] = $aaTemp;
                }
                //裁判统计
                if(!empty($Referee)){
                    $aaTemp = [
                        'name'     => 'Referee',
                        'content'  => $Referee,
                    ];
                    $rData[] = $aaTemp;
                }
                //相同历史盘口
                if(!empty($sameExp[0]) || !empty($sameExp[1]))
                {
                    $aaTemp = [
                        'name'     => 'sameExp',
                        'content'  => $sameExp,
                    ];
                    $rData[] = $aaTemp;
                }
                if(!empty($match_Recommend)) $rData[] = $match_Recommend;
                if(!empty($skill_data)) $rData[] = $skill_data;
                if(!empty($match_panlu)) $rData[] = $match_panlu;
                if(!empty($match_three)) $rData[] = $match_three;

//                if(!empty($PreMatchInfo))
//                {
//                    $aaTemp = [
//                        'name'     => 'PreMatchInfo',
//                        'content'  => $PreMatchInfo,
//                    ];
//                    $rData[] = $aaTemp;
//                }
            }
        }
        return $rData;
        */
    }

    /**
     * 根据赛事ID获取文字直播内容 qc_fb_textliving表
     * @param  array   $gameIds  赛事ID
     * @param  int     $web      来源网站ID
     * @return array  当日即时赛事数据
     */
    public function getTextliving($gameId,$web = 2,$lang = 0)
    {
        //return array();exit;   //暂时关闭文字直播
        if(empty($gameId)) return false;
        $web = 2;
        $map['game_id'] = $gameId;
        $map['from_web'] = $web;
        $res = M('FbTextliving') ->field('*')->where($map)->find();
        $res2 = M('GameFbinfo') ->field('id,home_team_name,away_team_name')->where(['game_id'=>$gameId])->find();
        $htName = explode(',',$res2['home_team_name']);
        $atName = explode(',',$res2['away_team_name']);

        $rData = [];
        if(!empty($res) && !empty($res['json_str']))
        {
            $hTname = explode(',',$res2['home_team_name']);
            $aTname = explode(',',$res2['away_team_name']);
            if($web == 2)
            {
                $changeArr = [0=>0,2=>1,3=>9,4=>3,5=>4,7=>2];
                $arr = json_decode($res['json_str'],true);
                //var_dump($arr);exit;
                $nTemp = $tempArr = [];
                foreach($arr as $k=>$v)
                {
                    if($v['type'] == 0) continue;
                    if(strpos($v['data'],'test')!==false || strpos($v['data'],'bet365')!==false || strpos($v['data'],'测试')!==false) continue;

                    $aTemp = [
                        'time'      => (string)$v['time'],
                        //'type'      => (string)$v['type'],
                        'type'      => isset($changeArr[$v['type']])?(string)$changeArr[$v['type']] : (string)$v['type'],
                        'position'  => (string)$v['position'],
                        'data'      => (string)$v['data'],
                    ];
                    if($v['position'] == 1)
                    {
                        $aTemp['data'] .= ' - '.$hTname[$lang];
                    }
                    else if($v['position'] == 2)
                    {
                        $aTemp['data'] .= ' - '.$aTname[$lang];
                    }
                    /*if($v['time'] == "90+1'")
                    {
                        $nTemp[] = $aTemp;
                        continue;
                    }*/
                    $tempArr[] = $aTemp;
                }
                if(!empty($nTemp))
                {
                    foreach($nTemp as $k=>$v)
                    {
                        $tempArr[] = $v;
                    }
                }
                $rData = array_reverse($tempArr);
            }
            else
            {
                $htName = explode(',',$res2['home_team_name']);
                $atName = explode(',',$res2['away_team_name']);

                $arr = json_decode($res['json_str'],true);
                //var_dump($arr);exit;
                $nTemp = [];
                foreach($arr as $k=>$v)
                {
                    $str = $this->testLiving($v,$htName[0],$atName[0]);
                    if($str === false)
                        continue;
                    else
                        $v['data'] = $str;

                    if(strpos($v['data'],'角球数')!==false || strpos($v['data'],'进球数')!==false || strpos($v['data'],'大家好')!==false || strpos($v['data'],'上半场比赛开始')!==false || strpos($v['data'],'罚牌')!==false || strpos($v['data'],'球门球')!==false || strpos($v['data'],'任意球')!==false || strpos($v['data'],'界外球')!==false) continue;

                    $aTemp = [
                        'time'      => (string)$v['time'],
                        'type'      => (string)$v['type'],
                        'position'  => (string)$v['position'],
                        'data'      => (string)$v['data'],
                    ];
                    if($v['time'] == "90+1'")
                    {
                        $nTemp[] = $aTemp;
                        continue;
                    }
                    $rData[] = $aTemp;
                }
                if(!empty($nTemp))
                {
                    foreach($nTemp as $k=>$v)
                    {
                        $rData[] = $v;
                    }
                }
            }
        }

        #mongo数据
        if(empty($res) && $web ==2 )
        {
            $aMap = ['game_id'=>$gameId];
            $res = M('FbLinkbet') ->field('game_id,md_id')->where($map)->find();

            if(!empty($res['md_id']))
            {
                $mService = mongoService();
                $_id =  $mService->_objectId($res['md_id']);
                $mRes = $mService->select('gameEvent',["gameId"=>$_id]);

                if(!empty($mRes))
                {
                    $teamTemp = explode(' v ',$mRes[0]['gameName']);
                    $hTeam = trim($teamTemp[0]);
                    $aTeam = trim($teamTemp[1]);

                    $aSort = [];
                    $aSort45 = $aSort90= [];
                    foreach($mRes as $key => $val)
                    {
                        if($val['eventType'] != 0) continue;
                        if(strpos($val['event'],'角球数')!==false || strpos($val['event'],'进球数')!==false || strpos($val['event'],'大家好')!==false || strpos($val['event'],'上半场比赛开始')!==false || strpos($val['event'],'罚牌')!==false || strpos($val['event'],'球门球')!==false || strpos($val['event'],'任意球')!==false || strpos($val['event'],'界外球')!==false) continue;

                        $eventTemp = explode('-',$val['event']);
                        $teamName = trim($eventTemp[2]);
                        $str = trim($eventTemp[1]);
                        $tTime = trim($eventTemp[0]);
                        $temp = [];
                        $temp['time'] = trim($tTime,"'");
                        $temp['data'] = $str.' - '.$teamName;
                        if(strpos($teamName,$hTeam) !==false)
                        {
                            $temp['position'] = '1';
                            $temp['data'] = str_replace($hTeam,$htName[0],$temp['data']);
                        }
                        else if(strpos($teamName,$aTeam) !==false)
                        {
                            $temp['position'] = '2';
                            $temp['data'] = str_replace($aTeam,$atName[0],$temp['data']);
                        }
                        else
                        {
                            $temp['position'] = '0';
                        }
                        //1进球，2角球，3黄牌，4红牌，9换人，0普通描述
                        if(strpos($str,'進球') !==false)
                        {
                            $temp['type'] = '1';
                        }
                        else if(strpos($str,'角球') !==false)
                        {
                            $temp['type'] = '2';
                        }
                        else if(strpos($str,'黃牌') !==false)
                        {
                            $temp['type'] = '3';
                        }
                        else if(strpos($str,'紅牌') !==false)
                        {
                            $temp['type'] = '4';
                        }
                        else
                        {
                            $temp['type'] = '0';
                        }
                        #时间排序问题
                        if(strpos($temp['time'],'45+'))
                        {
                            $tStr = $temp['time'];
                            $ttime = eval("return $tStr;");
                            $aSort45[$ttime] = $temp;
                        }
                        else if(strpos($temp['time'],'90+'))
                        {
                            $tStr = $temp['time'];
                            $ttime = eval("return $tStr;");
                            $aSort90[$ttime] = $temp;
                        }
                        else
                        {
                            $aSort[] = $temp['time'];
                            $rData[] = $temp;
                        }
                    }
                    array_multisort($aSort, SORT_ASC, $rData);
                    if(!empty($aSort45))
                    {
                        $tData = $rData;
                        foreach($tData as $kk => $vv)
                        {
                            if($vv['time'] <= 45 && $rData[$kk+1]['time'] > 45)
                            {
                                array_splice($rData,$kk+1,0,$aSort45);
                            }
                        }

                    }
                    if(!empty($aSort90))
                    {
                        foreach($aSort90 as $k3 => $v3)
                        {
                            $rData[] = $v3;
                        }
                    }

                }
            }
        }
        return $rData;
    }

    public function testLiving($data,$hName,$aName)
    {
        if(empty($data)) return false;
        $arr = [1,3,4,2];
        if(!in_array($data['type'],$arr)) return false;

        $str = '';
        switch($data['type'])
        {
            case 1:
                if(preg_match('/第\d+个进球/is',$data['data'],$tData))
                {
                    if($data['position'] == 1)
                        $str = "Goal！（".$hName."）打进".$tData[0];
                    else
                        $str = "Goal！（".$aName."）打进".$tData[0];
                }
                break;
            case 2:
                if(preg_match('/\d+个角球/is',$data['data'],$tData)) {
                    if ($data['position'] == 1)
                        $str = "（" . $hName . "）斩获第" . $tData[0];
                    else
                        $str = "（" . $aName . "）斩获第" . $tData[0];
                }
                break;
            case 3:
                if(preg_match('/第\d+张黄牌/is',$data['data'],$tData))
                {
                    if($data['position'] == 1)
                        $str = "（".$hName."）获得".$tData[0];
                    else
                        $str = "（".$aName."）获得".$tData[0];
                }
                break;
            case 4:
                if(preg_match('/第\d+红牌/is',$data['data'],$tData))
                {
                    if($data['position'] == 1)
                        $str = "Goal！（".$hName."）获得".$tData[0];
                    else
                        $str = "Goal！（".$aName."）获得".$tData[0];
                }
                else
                {
                    if($data['position'] == 1)
                        $str = "（".$hName."）获得红牌";
                    else
                        $str = "（".$aName."）获得红牌";
                }
                break;
        }
        return $str;
    }

     /**
     * 根据赛事ID获取动画 qc_fb_cartoonbet表
     * @param  array   $gameIds  赛事ID
     * @param  array   $last_game_time  时间
     * @return array  当日即时赛事数据
     */
    public function getAnimate($gameId , $last_game_time = 0,$lang = 1)
    {
        if(empty($gameId)) return false;

        if($lang == 1)
            $langs = 0;
        else
            $langs = 1;

        $map['game_id'] = $gameId;
        //$map['from_web'] = $web;
        $res1 = M('GameFbinfo') ->field('*,b.img_url as home_img_url,c.img_url as away_img_url')->join('
        LEFT JOIN qc_game_team b ON qc_game_fbinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_team c ON qc_game_fbinfo.away_team_id = c.team_id')->where($map)->find();
        if(empty($res1)) return null;

        $res2 = M('FbLinkbet') ->field('*')->where($map)->find();
        if(empty($res2)) return null;

        $rData = [];

        $httpUrl = C('IMG_SERVER');
        $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
        $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

        //$homeTeamImg = !empty($res1['home_img_url']) ? $httpUrl.$res1['home_img_url'] : $defaultHomeImg;
        //$awayTeamImg = !empty($res1['away_img_url']) ? $httpUrl.$res1['away_img_url'] : $defaultAwayImg;

        $homeTeamImg = $defaultHomeImg;   //写死主客默认logo
        $awayTeamImg = $defaultAwayImg;

        $htn = explode(',',$res1['home_team_name']);
        $atn = explode(',',$res1['away_team_name']);
        $utn = explode(',',$res1['union_name']);

        $tempTime = explode(',',$res1['game_half_time']);
        $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
        $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
        $halftime = implode('',$tempTime);

        $score = explode('-',$res1['score']);
        $game_detail = [
            'game_id' => $res1['game_id'],
            'gtime' => $res1['gtime'].'000',
            'game_half_time' => strtotime($halftime).'000',
            'game_date' => date('Ymd H:i:s',$res1['gtime']),
            'score1' => $score[0],
            'score2' => isset($score[1])?$score[1]:'',
            'union_name' => $utn[$langs],
            'home_team_name' => $htn[$langs],
            'away_team_name' => $atn[$langs],
            'home_img_url' => $homeTeamImg,
            'away_img_url' => $awayTeamImg,
            'game_state' => $res1['game_state'],
            'status' => $res2['status'],
            'data_flag' => '1',
        ];
        $rData['game_detail'] = $game_detail;
        //var_dump($game_detail);exit;
        $runtime_detail = [];

        /*if($res2['status'] == 'end' || $res1['game_state'] == -1)
        {
            $allRdRes = M('FbCartoonbet') ->field('update_time,status_code,xy,pg,game_desc,is_home,other')->where(['flash_id'=>$res2['flash_id']])->select();

            if(!empty($allRdRes))
            {
                $cArr = [11003,11005,11006,10008,11012,11004,11013,1025,11234,11236,11239,11008,11009,21003,21005,21006,20008,21012,21004,21013,21025,21234,21236,21239,21008,21009];
                foreach($allRdRes as $k=>$v)
                {
                    unset($v['str_txt']);
                    $v['update_time'] = $v['update_time'].'000';
                    if(array_search($v['status_code'],$cArr) === false) continue;
                    $runtime_detail[] = $v;
                }
                return array('game_detail'=>$game_detail,'runtime_detail'=>$runtime_detail);
            }
        }*/
        if($res1['game_state'] == -1)
        {
            $rData['runtime_detail'] = [];
            return $rData;
        }

        $uptimeRes = M('FbCartoonbet')->field('update_time')->where(['flash_id'=>$res2['flash_id']])->order('id desc')->limit(1)->find();

        if(strlen($last_game_time) >10) $last_game_time = substr($last_game_time,0,10);

        #数据2min不更新
        if(!empty($uptimeRes) && $uptimeRes['update_time'] < (time()-120) && $game_detail['game_state'] !=2)
        {
            $rData['game_detail']['data_flag'] = '0';
            $rData['runtime_detail'] = [];
            return $rData;
        }

        if(!empty($uptimeRes) && $uptimeRes['update_time'] > $last_game_time)
        {
            $map2['flash_id'] = $res2['flash_id'];
            $map2['update_time'] = array('EGT',$uptimeRes['update_time']);
            $rtRes = M('FbCartoonbet') ->field('update_time,status_code,xy,pg,game_desc,is_home,other')->where($map2)->select();

            if(!empty($rtRes))
            {
                foreach($rtRes as $k=>$v)
                {
                    unset($v['str_txt']);
                    $v['update_time'] = $v['update_time'].'000';
                    if($v['update_time'])
                    $runtime_detail[$k] = $v;
                }
                //$rData = ['runtime_detail'=>$runtime_detail];
                $rData['runtime_detail'] = $runtime_detail;
            }
        }
        else
        {
            $rData['runtime_detail'] = [];
        }

        return $rData;
    }

     /**
     * 根据赛事ID获取动画 qc_fb_cartoonbet/qc_fb_detail表
     * @param  array   $gameIds  赛事ID
     * @param  array   $last_game_time  时间
     * @return array  当日即时赛事数据
     */
    public function getFlashOver($gameId , $last_game_time ,$lang = 1)
    {
        if(empty($gameId)) return false;
        if($lang == 1)
            $langs = 0;
        else
            $langs = 1;

        $map['game_id'] = $gameId;
        //$map['from_web'] = $web;
        $res1 = M('GameFbinfo') ->field('*,b.img_url as home_img_url,c.img_url as away_img_url')->join('
        LEFT JOIN qc_game_team b ON qc_game_fbinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_team c ON qc_game_fbinfo.away_team_id = c.team_id')->where($map)->find();
        if(empty($res1)) return null;

        $res2 = M('FbLinkbet') ->field('*')->where($map)->find();
        if(empty($res2)) return null;

        $rData = [];

        $httpUrl = C('IMG_SERVER');
        $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
        $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

        //$homeTeamImg = !empty($res1['home_img_url']) ? $httpUrl.$res1['home_img_url'] : $defaultHomeImg;
        //$awayTeamImg = !empty($res1['away_img_url']) ? $httpUrl.$res1['away_img_url'] : $defaultAwayImg;

        $homeTeamImg = $defaultHomeImg;   //写死主客默认logo
        $awayTeamImg = $defaultAwayImg;

        $htn = explode(',',$res1['home_team_name']);
        $atn = explode(',',$res1['away_team_name']);
        $utn = explode(',',$res1['union_name']);

        $tempTime = explode(',',$res1['game_half_time']);
        $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
        $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
        $halftime = implode('',$tempTime);

        $score = explode('-',$res1['score']);
        $game_detail = [
            'game_id' => $res1['game_id'],
            'gtime' => $res1['gtime'].'000',
            'game_half_time' => strtotime($halftime).'000',
            'game_date' => date('Ymd H:i:s',$res1['gtime']),
            'score1' => $score[0],
            'score2' => isset($score[1])?$score[1]:'',
            'union_name' => $utn[$langs],
            'home_team_name' => $htn[$langs],
            'away_team_name' => $atn[$langs],
            'home_img_url' => $homeTeamImg,
            'away_img_url' => $awayTeamImg,
            'game_state' => $res1['game_state'],
            'status' => $res2['status'],
        ];

        $runtime_detail = [];

        if($res2['status'] == 'end' || $res1['game_state'] == -1)
        {
            $res3=M('DetailFb')->where($map)->order('gtime')->select();

            $scores = $game_detail['score1'] + $game_detail['score2'];
            foreach($res3 as $k=>$v)
            {
                if($v['detail_type'] == 1 || $v['detail_type'] == 8) $scores--;
            }

            foreach($res3 as $k=>$v)
            {
                $code = $this->typeTotype($v['detail_type']);
                if(!$code) continue;

                if($scores > 0 && $v['detail_type'] == 7)
                {
                    if($v['gtime'] > 45)
                        $t = ($v['gtime']+15) * 60;
                    else
                        $t = $v['gtime'] * 60;
                    $newcode = $v['is_home'] == 1? '1'.$code:'2'.$code;
                    $temp = [
                        'update_time' => (string)($res1['gtime']+$t).'000',
                        //'time' => (string)date('Y-m-d H:i:s',($res1['gtime']+$t)),
                        //'gtime' => (string)$v['gtime'],
                        'status_code' => $newcode,
                        'xy' => '',
                        'pg' => '',
                        'game_desc' => '',
                        'is_home' => $v['is_home'] == 1?'1':'2',
                        'other' => '',
                    ];
                    $rData[] = $temp;

                    if($v['gtime'] > 45)
                        $t = ($v['gtime']+15) * 60+60;
                    else
                        $t = $v['gtime'] * 60+60;

                    $newcode = $v['is_home'] == 1? '10008':'20008';
                    $temp = [
                        'update_time' => (string)($res1['gtime']+$t).'000',
                        //'time' => (string)date('Y-m-d H:i:s',($res1['gtime']+$t)),
                        //'gtime' => (string)$v['gtime'],
                        'status_code' => $newcode,
                        'xy' => '',
                        'pg' => '',
                        'game_desc' => '',
                        'is_home' => $v['is_home'] == 1?'1':'2',
                        'other' => '',
                    ];
                    $scores--;
                    $rData[] = $temp;
                    continue;
                }

                if($scores < 1 && $v['detail_type'] == 7) continue;
                if($code)
                {
                    if($v['gtime'] > 45)
                        $t = ($v['gtime']+15) * 60;
                    else
                        $t = $v['gtime'] * 60;
                    $newcode = $v['is_home'] == 1? '1'.$code:'2'.$code;
                    $temp = [
                        'update_time' => (string)($res1['gtime']+$t).'000',
                        //'time' => (string)date('Y-m-d H:i:s',($res1['gtime']+$t)),
                        //'gtime' => (string)$v['gtime'],
                        'status_code' => $newcode,
                        'xy' => '',
                        'pg' => '',
                        'game_desc' => '',
                        'is_home' => $v['is_home'] == 1?'1':'2',
                        'other' => '',
                    ];
                    if($scores > 0 && $v['detail_type'] == 7) $scores--;
                    $rData[] = $temp;
                }
            }

            $allRdRes = M('FbCartoonbet') ->field('update_time,status_code,xy,pg,game_desc,is_home,other')->where(['flash_id'=>$res2['flash_id']])->select();

            if(!empty($allRdRes))
            {
               /*$cArr = [11003,11005,11006,10008,11012,11004,11013,1025,11234,11236,11239,11008,11009,21003,21005,21006,20008,21012,21004,21013,21025,21234,21236,21239,21008,21009];*/
               $cArr =   [11004,21004,11009,21009,11236,21236,11012,21012,11234,21234,11239,21239,1025];
               $overFlag = false;
               foreach($allRdRes as $k=>$v)
               {
                   unset($v['str_txt']);
                   $v['update_time'] = $v['update_time'].'000';
                   if(array_search($v['status_code'],$cArr) === false) continue;
                   /*if($v['status_code'] == 1017)
                   {
                        $overFlag = true;
                        array_pop($cArr);
                   }*/
                   $runtime_detail[] = $v;
                   $rData[] = $v;
               }
               $aSort = [];
               foreach($rData as $k=>$v)
               {
                   $aSort[$k] = $v['update_time'];
               }
               array_multisort($aSort, SORT_ASC, $rData);

               $lastkey = count($rData)-1;
               if($rData[$lastkey]['status_code'] != 1017 && $overFlag == false)
               {
                    $temp = [
                        'update_time' => (string)($rData[$lastkey]['update_time']+1000),
                        'status_code' => '1017',
                        'xy' => '',
                        'pg' => '',
                        'game_desc' => 'Full Time',
                        'is_home' => '',
                        'other' => '',
                    ];
                    $rData[] = $temp;
               }

               /*foreach($rData as $k=>$v)
               {
                   $v['game_time'] = date('Y-m-d H:i:s',$v['update_time']);
                   $rData[$k] = $v;
               }*/

               return array('game_detail'=>$game_detail,'runtime_detail'=>$rData);
           }
        }

        return array('game_detail'=>$game_detail,'runtime_detail'=>$rData);
    }

    /**
     * 根据赛事ID获取动画mongodb id
     * @param  string   $gameId  赛事ID
     * @param  string   $type  1,足球；2，篮球
     * @return string  mongodb id
     */
    public function getAnimateId($gameId,$type)
    {
        if(empty($gameId)) return '';

        $httpUrl = C('IMG_SERVER');
        $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
        $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

        $rData = [];
        if($type == 1)
        {
            $res = M('FbLinkbet') ->field('md_id')->where(['game_id'=>$gameId])->find();
            if(!empty($res))
                $rData['_id'] = $res['md_id'] == null?'':$res['md_id'];
            else
                $rData['_id'] = '';
            //$res = M('GameFbinfo') ->field('home_team_name,away_team_name')->where(['game_id'=>$gameId])->find();

            $baseRes = M('GameFbinfo f')
            ->field('f.game_id,f.gtime,f.game_state,f.score,f.union_name,f.home_team_name,f.away_team_name,b.img_url as home_img_url,c.img_url as away_img_url,is_video,is_flash,gtime,f.home_team_id,f.away_team_id,f.union_id,is_betting,u.is_union,f.is_go')
            ->join('LEFT JOIN qc_game_team b ON f.home_team_id = b.team_id LEFT JOIN qc_game_team c ON f.away_team_id = c.team_id LEFT JOIN qc_union u ON f.union_id = u.union_id')
            ->where(['game_id'=>$gameId])
            ->find();

            if(!empty($baseRes))
            {
                $hname = explode(',',$baseRes['home_team_name']);
                $rData['h_team'] = $hname[0];
                $aname = explode(',',$baseRes['away_team_name']);
                $rData['a_team'] = $aname[0];

                if (iosCheck() && I('platform') == '2') //ios审核设定为默认球队logo
                {
                    $homeTeamImg = $defaultHomeImg;
                    $awayTeamImg = $defaultAwayImg;
                }
                else
                {
                    $homeTeamImg = !empty($baseRes['home_img_url']) ? $httpUrl.$baseRes['home_img_url'] : $defaultHomeImg;
                    $awayTeamImg = !empty($baseRes['away_img_url']) ? $httpUrl.$baseRes['away_img_url'] : $defaultAwayImg;
                }

                $rData['h_team_img'] = $homeTeamImg;
                $rData['a_team_img'] = $awayTeamImg;
            }
        }
        else if($type == 2)
        {
            $res = M('BkLinkbet') ->field('md_id')->where(['game_id'=>$gameId])->find();
            if(!empty($res))
                $rData['_id'] = $res['md_id'] == null?'':$res['md_id'];
             else
                $rData['_id'] = '';
            //$res = M('GameBkinfo') ->field('home_team_name,away_team_name')->where(['game_id'=>$gameId])->find();

            $baseRes = M('GameBkinfo f')
            ->field('f.game_id,f.gtime,f.game_state,f.score,f.union_name,f.home_team_name,f.away_team_name,b.img_url as home_img_url,c.img_url as away_img_url,is_video,is_flash,f.home_team_id,f.away_team_id,f.union_id')
            ->join('LEFT JOIN qc_game_teambk b ON f.home_team_id = b.team_id LEFT JOIN qc_game_teambk c ON f.away_team_id = c.team_id LEFT JOIN qc_bk_union u ON f.union_id = u.union_id')
            ->where(['game_id'=>$gameId])
            ->find();
            if(!empty($baseRes))
            {
                $hname = explode(',',$baseRes['home_team_name']);
                $rData['h_team'] = $hname[0];
                $aname = explode(',',$baseRes['away_team_name']);
                $rData['a_team'] = $aname[0];

                if (iosCheck() && I('platform') == '2') //ios审核设定为默认球队logo
                {
                    $homeTeamImg = $defaultHomeImg;
                    $awayTeamImg = $defaultAwayImg;
                }
                else
                {
                    $homeTeamImg = !empty($baseRes['home_img_url']) ? $httpUrl.$baseRes['home_img_url'] : $defaultHomeImg;
                    $awayTeamImg = !empty($baseRes['away_img_url']) ? $httpUrl.$baseRes['away_img_url'] : $defaultAwayImg;
                }

                $rData['h_team_img'] = $homeTeamImg;
                $rData['a_team_img'] = $awayTeamImg;
            }
        }
        return $rData;
    }

    /**
     * 根据赛事ID获取动画 qc_fb_cartoonbet/qc_fb_detail表
     * @param  array   $gameIds  赛事ID
     * @param  array   $last_game_time  时间
     * @return array  当日即时赛事数据
     */
    public function getFlashOverTest($gameId , $last_game_time ,$lang = 1)
    {
        if(empty($gameId)) return false;
        if($lang == 1)
            $langs = 0;
        else
            $langs = 1;

        $map['game_id'] = $gameId;

        $res1 = M('GameFbinfo') ->field('*,b.img_url as home_img_url,c.img_url as away_img_url')->join('
        LEFT JOIN qc_game_team b ON qc_game_fbinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_team c ON qc_game_fbinfo.away_team_id = c.team_id')->where($map)->find();
        if(empty($res1)) return null;

        $res2 = M('FbLinkbet') ->field('*')->where($map)->find();
        if(empty($res2)) return null;

        $rData = [];

        $httpUrl = C('IMG_SERVER');
        $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
        $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

        //$homeTeamImg = !empty($res1['home_img_url']) ? $httpUrl.$res1['home_img_url'] : $defaultHomeImg;
        //$awayTeamImg = !empty($res1['away_img_url']) ? $httpUrl.$res1['away_img_url'] : $defaultAwayImg;

        $homeTeamImg = $defaultHomeImg;   //写死主客默认logo
        $awayTeamImg = $defaultAwayImg;

        $htn = explode(',',$res1['home_team_name']);
        $atn = explode(',',$res1['away_team_name']);
        $utn = explode(',',$res1['union_name']);

        $tempTime = explode(',',$res1['game_half_time']);
        $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
        $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
        $halftime = implode('',$tempTime);

        $score = explode('-',$res1['score']);
        $game_detail = [
            'game_id' => $res1['game_id'],
            'gtime' => $res1['gtime'].'000',
            'game_half_time' => strtotime($halftime).'000',
            'game_date' => date('Ymd H:i:s',$res1['gtime']),
            'score1' => $score[0],
            'score2' => isset($score[1])?$score[1]:'',
            'union_name' => $utn[$langs],
            'home_team_name' => $htn[$langs],
            'away_team_name' => $atn[$langs],
            'home_img_url' => $homeTeamImg,
            'away_img_url' => $awayTeamImg,
            'game_state' => $res1['game_state'],
            'status' => $res2['status'],
        ];

        $runtime_detail = [];

        if($res2['status'] == 'end' || $res1['game_state'] == -1)
        {
            $allRdRes = M('FbCartoonbet') ->field('update_time,status_code,xy,pg,game_desc,is_home,other')->where(['flash_id'=>$res2['flash_id']])->select();

            if(!empty($allRdRes))
            {

               $overFlag = false;
               foreach($allRdRes as $k=>$v)
               {
                   unset($v['str_txt']);
                   $v['update_time'] = $v['update_time'].'000';
                   $runtime_detail[] = $v;
                   $rData[] = $v;
               }
               $aSort = [];
               foreach($rData as $k=>$v)
               {
                   $aSort[$k] = $v['update_time'];
               }
               array_multisort($aSort, SORT_ASC, $rData);

               $lastkey = count($rData)-1;
               if($rData[$lastkey]['status_code'] != 1017 && $overFlag == false)
               {
                    $temp = [
                        'update_time' => (string)($rData[$lastkey]['update_time']+1000),
                        'status_code' => '1017',
                        'xy' => '',
                        'pg' => '',
                        'game_desc' => 'Full Time',
                        'is_home' => '',
                        'other' => '',
                    ];
                    $rData[] = $temp;
               }
               return array('game_detail'=>$game_detail,'runtime_detail'=>$rData);
           }
        }

        return array('game_detail'=>$game_detail,'runtime_detail'=>$rData);
    }

     /**
     * 根据球队ID获取球队信息
     * @param  int   $teamId  球队ID
     * @return array  球队数据
     */
    public function getTeamData($teamId,$unionId)
    {
        if(empty($teamId)) return false;
	    $mongo = mongoService();
	    $where = ['$or' => [['home_team_id' => (int) $teamId], ['away_team_id' => (int) $teamId]], 'union_id' => (int) $unionId];

	    $team_info = $mongo->select('fb_game', $where,
		    ['game_id', 'union_name', 'home_team_id', 'home_team_name', 'away_team_id', 'away_team_name', 'score', 'half_score', 'gtime', 'game_state', 'let_goal', 'big_small', 'home_team_rank', 'away_team_rank']);

	    if (!empty($team_info)) {
	    	$gtime_sort = [];
	        foreach ($team_info as $key => $value) {
	        	$gtime_sort[$key] = $value['gtime'];
	        }
	        //根据gtime 排序
	        array_multisort($gtime_sort, SORT_DESC,$team_info);
	        // 如果大于50 只显示50
	        if (sizeof($team_info) > 50) {
	        	$team_info = array_slice($team_info, 0, 50);
	        }
	        // 数据格式转换为原格式
	        $temp_data = [];
	        foreach ($team_info as $key => $value) {
	            $temp = [];
	            $temp['game_id'] = (string) $value['game_id'];
	            $temp['union_name'] = $value['union_name'];
	            $temp['home_team_id'] = (string) $value['home_team_id'];
	            $temp['home_team_name'] = $value['home_team_name'];
	            $temp['away_team_id'] = (string) $value['away_team_id'];
	            $temp['away_team_name'] = $value['away_team_name'];
	            $temp['score'] = $this->NullChange($value['score']);
	            $temp['half_score'] = $this->NullChange($value['half_score']);
	            $temp['gtime'] = (string) strtotime($value['gtime']);
	            $temp['game_state'] = (string) $value['game_state'];
	            $temp['fsw_exp'] = (string) $value['let_goal'];
	            $temp['fsw_ball'] = (string) $value['big_small'];
	            $temp['home_team_rank'] = $value['home_team_rank'];
	            $temp['away_team_rank'] = $value['away_team_rank'];
	            if ($temp['game_state'] !== '') {
		            $temp_data[$key] = $temp;
	            }
	        }
	    }

        $rData = [];
        $tInfo = $this->getTeamInfo($teamId, $unionId);
        $rData['team_info'] = $tInfo;

        if(!empty($temp_data))
        {
            $FbdataService = new \Common\Services\FbdataService();
            $gameInfo = [];
            foreach ($temp_data as $k => $v)
            {
                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['game_state'];
                $val[2] = !empty($v['gtime']) ? date('Ymd-H:i', $v['gtime']) : '';
                $val[3] = $unionId;
                $val[4] = $tInfo['union_name'];
                $val[5] = $v['home_team_name'];
                $val[6] = $v['away_team_name'];
                $home_rank = !empty($v['home_team_rank']) ? pregUnionRank($v['home_team_rank']) : '';
                $away_rank = !empty($v['away_team_rank']) ? pregUnionRank($v['away_team_rank']) : '';
                $val[7] = $home_rank !== false ? $home_rank : '';
                $val[8] = $away_rank !== false ? $away_rank : '';
                $val[9] = ($v['score'] == '-' || empty($v['score'])) ? '' : $v['score'];
                $val[10] = ($v['half_score'] == '-' || empty($v['half_score'])) ? '' : $v['half_score'];
                $val[11] = $v['fsw_exp'] == null ? '' : $v['fsw_exp'];
                $val[12] = $v['fsw_ball'] == null ? '' : $v['fsw_ball'];
                if ($v['game_state'] == -1) {
                    $win = $ePanlu = $bPanlu = '';
                    if ($v['home_team_id'] == $teamId) {
                        $win = $FbdataService->winLost($v['score'], 1);
                        $ePanlu = $FbdataService->panluWin($v['fsw_exp'], $v['score'], 1);
                    } else {
                        $win = $FbdataService->winLost($v['score'], 2);
                        $ePanlu = $FbdataService->panluWin($v['fsw_exp'], $v['score'], 2);
                    }
                    if ($v['fsw_ball'] !== null && $v['fsw_ball'] !== '') {
                        $score = explode('-', $v['score']);
                        if ($score[0] + $score[1] > $v['fsw_ball']) {
                            $bPanlu = 1;
                        } else if ($score[0] + $score[1] < $v['fsw_ball']) {
                            $bPanlu = -1;
                        } else {
                            $bPanlu = 0;
                        }
                    }
                    $val[13] = (string)$win;
                    $val[14] = (string)$ePanlu;
                    $val[15] = (string)$bPanlu;
                } else {
                    $val[13] = '';
                    $val[14] = '';
                    $val[15] = '';
                }
                $gameInfo[] = $val;
            }
            $rData['match_info'] = $gameInfo;
        }
        return $rData;
    }


    /**
     * 根据球队ID获取球队基本信息 qc_game_team表
     * @param  int   $teamId  球队ID
     * @return array  球队数据
     */
    public function getTeamInfo($teamId, $unionId)
    {
        if(empty($teamId)) return false;
        $data = [];
	    $mongo = mongoService();
	    $team_info = $mongo->select('fb_team', ['team_id' => (int) $teamId], ['team_id', 'team_name', 'country_id', 'country', 'stadium_name', 'people','formed', 'url', 'img_url', 'team_intro', 'union_name'])[0];
	    $union_data = $mongo->select('fb_union', ['union_id' => (int) $unionId], ['country_id', 'union_name'])[0];

		if (empty($team_info)) {
			return null;
		}
		$data['team_id'] = (string) $teamId;
		$data['team_name'] = $team_info['team_name'];
		$data['stadium_name'] = $team_info['stadium_name'];
		$data['country_id'] = (string) $union_data['country_id'];
		$data['country'] = empty($team_info['country']) ? '' : $team_info['country'];
		$data['people'] = $team_info['people'] === null ? '-' : $team_info['people'];
		$data['formed'] = $team_info['formed']  === null ? '-' : $team_info['formed'];
		$data['url'] = $team_info['url'] === null ? '-' : $team_info['url'];
	    $httpUrl = C('IMG_SERVER');
	    $img = staticDomain('/Public/Home/images/common/team_def.png');
	    $data['img_url'] = empty($team_info['img_url']) ? $img : $httpUrl.$team_info['img_url'];
		$data['team_intro'] = $team_info['team_intro'] === null ? '' : $team_info['team_intro'];
		$data['union_id'] = $unionId;
		$data['union_name'] = $union_data['union_name'];
        return $data;
    }

    /**
     * 根据赛事ID获取概率界面数据
     * @param  int   $gameId 赛事ID
     * @return array  数据
     */
    public function getProbability($gameId)
    {
        if(empty($gameId)) return false;
        $res = M('FbMatchodds')->field('gl_value')->where(['game_id'=>$gameId])->find();

        $rData = [];
        if(!empty($res['gl_value']))
        {
            $arr = explode('$$',$res['gl_value']);
            foreach($arr as $k =>$v)
            {
                $temp = [];
                if($k == 0 || $k == 2 || $k == 4)
                {
                    $rData[] = explode('^',$v);
                }
                else
                {
                    $aTemp = [];
                    $temp2 = explode('!',$v);
                    foreach($temp2 as $k2 =>$v2)
                    {
                        $aTemp[] =  explode('^',$v2);
                    }
                    $rData[] =$aTemp;
                }
            }
        }
        return $rData;
    }

    /**
     * 根据赛事ID获取赛事前瞻
     * @param  int   $gameId 赛事ID
     * @return array  数据
     */
    public function getPreMatchinfo($gameId,$from = 1)
    {
        if(empty($gameId)) return false;

        $rData = [];

        #mongo赛事情报数据
        $mService = mongoService();
        $mRes = $mService->select('fb_game',['game_id'=>(int)$gameId],['news_gunqiu', '163_game_id']);
        $game_id_163 = $mRes[0]['163_game_id'];

        if(!empty($mRes))
        {
            if(isset($mRes[0]['news_gunqiu']) && !empty($mRes[0]['news_gunqiu']))
            {

                foreach($mRes[0]['news_gunqiu']['home'] as $key=>$val)
                {
                    $rData['HpmInfo'][] =  '【'.$val[0].'】'.$val[2];
                }
                foreach($mRes[0]['news_gunqiu']['guest'] as $key=>$val)
                {
                    $rData['ApmInfo'][] =  '【'.$val[0].'】'.$val[2];
                }
            }
        }

	    $res = $mService->select('fb_game',['game_id'=>(int)$gameId],['game_analysis_web_qt.game_information'])[0]['game_analysis_web_qt']['game_information'];


        if(!empty($res['home']) || !empty($res['guest']))
        {
            if(!empty($res['home']))
            {
                if ($res['home'][0] != "") {
	                foreach ($res['home'] as $key => $val)
	                {
						$rData['HpmInfo'][] = $val;
	                }
                }
            }
            if(!empty($res['guest']))
            {
	            if ($res['guest'][0] != "") {
		            foreach ($res['guest'] as $key => $val)
		            {
			            $rData['ApmInfo'][] = $val;
		            }
	            }

            }
        }

        #mongo赛事情报数据
	    if (!empty($game_id_163)) {
		    $mRes = $mService->select('fb_163gameevent',['game_info_id'=>(int)$game_id_163]);
		    if(!empty($mRes))
		    {
			    if(isset($mRes[0]['game_event']) && !empty($mRes[0]['game_event']))
			    {
				    $upStr = '【有利】';
				    $downStr = '【无利】';
				    $hupArr = $hdownArr = $aupArr = $adownArr = [];
				    foreach($mRes[0]['game_event']['homeEvent'] as $key=>$val)
				    {

						if($val['upDown'] == 0)
						{
							$hupArr[] =  $upStr.$val['title'];
						}
						else
						{
							$hdownArr[] =  $downStr.$val['title'];
						}
				    }

				    foreach($mRes[0]['game_event']['guestEvent'] as $key=>$val)
				    {

					    if($val['upDown'] == 0)
					    {
						    $aupArr[] =  $upStr.$val['title'];
					    }
					    else
					    {
						    $adownArr[] =  $downStr.$val['title'];
					    }
				    }

				    if(!empty($hupArr))
				    {
					    foreach($hupArr as $key=>$val)
					    {
						    $rData['HpmInfo'][] = $val;
					    }
				    }
				    if(!empty($hdownArr))
				    {
					    foreach($hdownArr as $key=>$val)
					    {
						    $rData['HpmInfo'][] = $val;
					    }
				    }
				    if(!empty($aupArr))
				    {
					    foreach($aupArr as $key=>$val)
					    {
						    $rData['ApmInfo'][] = $val;
					    }

				    }
				    if(!empty($adownArr))
				    {
					    foreach($adownArr as $key=>$val)
					    {
						    $rData['ApmInfo'][] = $val;
					    }
				    }
			    }
		    }
	    }
        return $rData;
    }

    /**
     +------------------------------------------------------------------------------
     * 以下为app足球5.0新增 start
     +------------------------------------------------------------------------------
    */

      /**
     * 根据赛事ID获取赛事赛况（事件、角球、技术统计）
     * @param  int   $gameId 赛事ID
     * @return array  数据
     */
    public function textSkill($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

	    $mongodb   = mongoService();
	    $textSkill = $mongodb->fetchRow("fb_game", ['game_id' => $gameId], ['home_team_id', 'away_team_id','tc', 'corner_sb', 'detail']);
        $tc = $textSkill['tc'];

        $corner = $textSkill['corner_sb'][3];
        $detail = $textSkill['detail'];
        $home_id = $textSkill['home_team_id'];
        $away_id = $textSkill['away_team_id'];
	    $data = [];
	    $data['St'] = [];
	    $data['det'] = [];
        if (!empty($tc) || !empty($detail)) {
	        // 获取技术统计
	        $st = $this->getSkill($tc);

	        //获取赛事事件
	        $det = $this->getDetail($corner, $detail, $gameId, $home_id, $away_id);
	        $data['St'] = $st;
	        $data['det'] = $det;
        }

        return$data;


		/*
        $rData = [];
        if(S('cache_fb_textSkill:'.$gameId))
        {
            $rData = S('cache_fb_textSkill:'.$gameId);
        }
        else
        {
            $res = M('StatisticsFb')->field('game_id,s_type,home_value,away_value')->where(['game_id'=>$gameId])->select();
            if($res === false)
            {
                echo "StatisticsFb error";exit;
            }

            //3:射门,4:射中,5:犯规,6:角球,8:角球,9:越位,11:黄牌,13:红牌,14:控球率,43:进攻,44:危险进攻
            $numArr = array(14,3,4,8,19,6,9,5,11,13,44);

            if(!empty($res))
            {
                $stData = [];
                foreach($res as $k=>$v)
                {
                    if(array_search($v['s_type'],$numArr) !== false)
                    {
                        $temp = [
                            0 => $v['s_type'] ,
                            1 => $v['home_value'] == null? '':$v['home_value'] ,
                            2 => $v['away_value'] == null? '':$v['away_value']
                        ];
                        $stData[] = $temp;
                    }
                }
                $rData['St'] = $stData;
            }

            $res = M('DetailFb')->field('game_id,is_home,detail_type,gtime,s_player,player_id,c_player')->where(['game_id'=>$gameId])->order('gtime')->select();
            if($res === false)
            {
                echo "DetailFb error";exit;
            }
            $DetData = [];
            if(!empty($res))
            {
                foreach($res as $k=>$v)
                {
                    $temp = [
                        0 => $v['game_id'],
                        1 => $v['is_home'],
                        2 => $v['detail_type'],
                        3 => $v['gtime'],
                        4 => $v['s_player'] == null? '':$v['s_player'] ,
                        5 => $v['player_id'] == null? '':$v['player_id'] ,
                        6 => $v['c_player'] == null? '':$v['c_player']
                    ];
                    $DetData[] = $temp;
                }
            }

            $res = M('FbCorner')->field('game_id,corner_str')->where(['game_id'=>$gameId,'company_id'=>3])->find();
            if($res === false)
            {
                echo "FbCorner error";exit;
            }

            $corData = [];
            if(!empty($res))
            {
                $tRes = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id')->where(['game_id'=>$gameId])->find();
                if($res === false)
                {
                    echo "FbCorner error";exit;
                }
                $cor1 = explode('^',$res['corner_str']);
                if(!empty($cor1[3]))
                {
                    $cor2 = explode(';',$cor1[3]);
                    $corTemp = ['h'=>0,'a'=>0];
                    foreach($cor2 as $key => $val)
                    {
                        $aTemp = explode(',',$val);
                        $temp = [
                            0 => (string)$gameId,
                            1 => $aTemp[0] == $tRes['home_team_id']?'1':'0',
                            2 => '99',
                            3 => (string)$aTemp[1],
                            4 => '第'.($key+1).'个角球' ,
                            5 => '',
                            6 => '第'.($key+1).'个角球'
                        ];
                        if($temp[1] == 1)
                        {
                            $corTemp['h'] ++;
                            $temp[4] = '第'.($corTemp['h']).'个角球';
                            $temp[6] = '第'.($corTemp['h']).'个角球';
                        }
                        else
                        {
                            $corTemp['a'] ++;
                            $temp[4] = '第'.($corTemp['a']).'个角球';
                            $temp[6] = '第'.($corTemp['a']).'个角球';
                        }
                        $corData[] = $temp;
                    }
                }
            }

            $dData = array_merge($DetData,$corData);
            if(!empty($dData))
            {
                $time = [];
                foreach($dData as $key => $val)
                {
                    $time[$key] = $val[3];
                }
                array_multisort($time,SORT_ASC,$dData);
                $rData['det'] = $dData;
            }
            else
            {
                $rData['det'] = [];
            }
            S('cache_fb_textSkill:'.$gameId,$rData,1);
        }
        return $rData;
		*/
    }

     /**
     * 根据赛事ID获取综合实力
     * @param  array   $gameIds  赛事ID
     * @return array  当日即时赛事数据
     */
    public function getStrength($gameId)
    {
        if(empty($gameId)) return false;

        $rData = [];

        if(S('cache_fb_strength_'.$gameId))
        {
            $rData = S('cache_fb_strength_'.$gameId);
        }
        else
        {
            $map['game_id'] = $gameId;
            $gRes = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id')->where($map)->find();

            $hInt = $aInt = 0;
            if(!empty($gRes))
            {
                #近期战力
                $sMap = 'status = 1 and game_state = -1 and (home_team_id = '.$gRes['home_team_id'].' or away_team_id = '.$gRes['home_team_id'].')';
                $renRes1 = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();

                if(!empty($renRes1))
                {
                    $hIntTemp = $this->calRecentGame($gRes['home_team_id'],$renRes1,20);
                    $hInt = $hInt + $hIntTemp;
                }

                $sMap = 'status = 1 and game_state = -1 and (home_team_id = '.$gRes['away_team_id'].' or away_team_id = '.$gRes['away_team_id'].')';
                $renRes2 = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();

                if(!empty($renRes2))
                {
                    $aIntTemp = $this->calRecentGame($gRes['away_team_id'],$renRes2,40);
                    $aInt = $aInt + $aIntTemp;
                }

                #主客战绩
                $sMap = 'status = 1 and game_state = -1 and home_team_id = '.$gRes['home_team_id'];
                $res = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                if(!empty($res))
                {
                    $hIntTemp = $this->calRecentGame($gRes['home_team_id'],$res,20);
                    $hInt = $hInt + $hIntTemp;
                }
                $sMap = 'status = 1 and game_state = -1 and away_team_id = '.$gRes['away_team_id'];
                $res = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                if(!empty($res))
                {
                    $aIntTemp = $this->calRecentGame($gRes['away_team_id'],$res,20);
                    $aInt = $aInt + $aIntTemp;
                }

                #历史交战
                $sMap = 'status = 1 and game_state = -1 and ((home_team_id = '.$gRes['home_team_id'].' and away_team_id = '.$gRes['away_team_id'].') or (home_team_id ='.$gRes['away_team_id'].' and away_team_id ='.$gRes['home_team_id'].'))';

                $res = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();

                if(!empty($res))
                {
                    $hIntTemp = $aIntTemp = 0;
                    foreach($res as $k=>$v)
                    {
                        $score = explode('-',$v['score']);
                        if($v['home_team_id'] == $gRes['home_team_id'])
                        {
                            if($score[0] > $score[1])
                                $hIntTemp = $hIntTemp + 3;
                            else if($score[0] < $score[1])
                                $aIntTemp = $aIntTemp + 3;
                        }
                        else
                        {
                            if($score[0] > $score[1])
                                $aIntTemp = $aIntTemp + 3;
                            else if($score[0] < $score[1])
                                $hIntTemp = $hIntTemp + 3;
                        }
                    }
                    $count = count($res);
                    $n = 30/($count*3);
                    $rate = 20/30;
                    $hIntTemp = $hIntTemp*$n*$rate;
                    $aIntTemp = $aIntTemp*$n*$rate;

                    $hInt = $hInt + $aIntTemp;
                    $aInt = $aInt + $aIntTemp;
                }

                $hIntTemp = $aIntTemp = $hIntTemp2 = $aIntTemp2 = 0;
                $hGoal = $hLost = $aGoal = $aLost = 0;
                #攻击力、防守力
                if(!empty($renRes1))
                {
                    foreach($renRes1 as $k=>$v)
                    {
                        $score = explode('-',$v['score']);
                        if($v['home_team_id'] == $gRes['home_team_id'])
                        {
                            $hGoal = $hGoal + $score[0];
                            $hLost = $hLost + $score[1];
                        }
                        else
                        {
                            $hGoal = $hGoal + $score[1];
                            $hLost = $hLost + $score[0];
                        }
                    }

                    if(count($renRes1) != 10)
                    {
                        $rateG = (10/count($renRes1))*2;
                        $hGoal = $hGoal+$rateG;
                        $hLost = $hLost+$rateG;
                    }

                    if(7 >= $hGoal && $hGoal >= 0)
                        $hIntTemp = 2;
                    else if(15 >= $hGoal && $hGoal >7)
                        $hIntTemp = 4;
                    else if(23 >= $hGoal && $hGoal >15)
                        $hIntTemp = 6;
                    else if(31>=$hGoal && $hGoal >23)
                        $hIntTemp = 8;
                    else if($hGoal>31)
                        $hIntTemp = 10;

                    if(8>=$hLost && $hLost >=0)
                        $hIntTemp2 = 10;
                    else if(15>$hLost && $hLost >9)
                        $hIntTemp2 = 8;
                    else if(20>=$hLost && $hLost>15)
                        $hIntTemp2 = 6;
                    else if(29>=$hLost && $hLost >21)
                        $hIntTemp2 = 4;
                    else if($hLost>30)
                        $hIntTemp2 = 2;

                    $hInt = $hInt + $hIntTemp + $hIntTemp2;
                }
                if(!empty($renRes2))
                {
                    foreach($renRes2 as $k=>$v)
                    {
                        $score = explode('-',$v['score']);
                        if($v['home_team_id'] == $gRes['away_team_id'])
                        {
                            $aGoal = $aGoal + $score[0];
                            $aLost = $aLost + $score[1];
                        }
                        else
                        {
                            $aGoal = $aGoal + $score[1];
                            $aLost = $aLost + $score[0];
                        }
                    }

                    if(count($renRes2) != 10)
                    {
                        $rateG = (10/count($renRes2))*2;
                        $aGoal = $aGoal+$rateG;
                        $aLost = $aLost+$rateG;
                    }

                    if(7 >= $aGoal && $aGoal >= 0)
                        $aIntTemp = 2;
                    else if(15 >= $aGoal && $aGoal >7)
                        $aIntTemp = 4;
                    else if(23 >= $aGoal && $aGoal >15)
                        $aIntTemp = 6;
                    else if(31 >= $aGoal && $aGoal >23)
                        $aIntTemp = 8;
                    else if($aGoal>31)
                        $aIntTemp = 10;

                    if(8>=$aLost && $aLost >=0)
                        $aIntTemp2 = 10;
                    else if(15 >= $aLost && $aLost >9)
                        $aIntTemp2 = 8;
                    else if(20 >= $aLost && $aLost>15)
                        $aIntTemp2 = 6;
                    else if(29 >= $aLost && $aLost >21)
                        $aIntTemp2 = 4;
                    else if($aLost>30)
                        $aIntTemp2 = 2;

                    $aInt = $aInt + $aIntTemp + $aIntTemp2;
                }

                #盘口
                //$odds = $this->fbOdds([0=>$gameId]);
                $map2['game_id'] = $gameId;
                $map2['company_id'] = 3;
                $oddsRes = M('FbOdds')->field('exp_value')->where($map2)->find();
                if(!empty($oddsRes))
                {
                    $oArr = oddsChArr($oddsRes['exp_value']);
                    if($oArr[0][4] != '')
                        $exp = $oArr[0][4];
                    else
                        $exp = $oArr[0][1];

                    if($exp != '')
                    {
                        $expTrend = $this->calExpTrend($exp,20);
                        $hInt = $hInt + $expTrend['h'];
                        $aInt = $aInt + $expTrend['a'];
                    }
                }

                $h = round($hInt/($hInt+$aInt),2);
                $a = round($aInt/($hInt+$aInt),2);
                $rData = ['home'=>$h,'away'=>$a];
                S('cache_fb_strength_'.$gameId,$rData,1800);
            }
        }
        return $rData;
    }


    /**
     * 根据赛事ID获取综合实力
     * @param  array   $gameIds  赛事ID
     * @return array  当日即时赛事数据
     */
    public function getStrengthTest($gameId)
    {
        if(empty($gameId)) return false;

        $rData = [];

       /* if(S('cache_fb_strength_'.$gameId))
        {
            $rData = S('cache_fb_strength_'.$gameId);
        }
        else
        {*/
            $map['game_id'] = $gameId;
            $gRes = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id')->where($map)->find();

            $hInt = $aInt = 0;
            if(!empty($gRes))
            {
                #近期战力
                $sMap = 'status = 1 and game_state = -1 and (home_team_id = '.$gRes['home_team_id'].' or away_team_id = '.$gRes['home_team_id'].')';
                $renRes1 = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                var_dump('近期战力',$renRes1);
                if(!empty($renRes1))
                {
                    $hIntTemp = $this->calRecentGame($gRes['home_team_id'],$renRes1,20);
                    $hInt = $hInt + $hIntTemp;
                }

                $sMap = 'status = 1 and game_state = -1 and (home_team_id = '.$gRes['away_team_id'].' or away_team_id = '.$gRes['away_team_id'].')';
                $renRes2 = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                var_dump($renRes2);
                if(!empty($renRes2))
                {
                    $aIntTemp = $this->calRecentGame($gRes['away_team_id'],$renRes2,40);
                    $aInt = $aInt + $aIntTemp;
                }

                #主客战绩
                $sMap = 'status = 1 and game_state = -1 and home_team_id = '.$gRes['home_team_id'];
                $res = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                var_dump('主客战绩',$res);
                if(!empty($res))
                {
                    $hIntTemp = $this->calRecentGame($gRes['home_team_id'],$res,20);
                    $hInt = $hInt + $hIntTemp;
                }
                $sMap = 'status = 1 and game_state = -1 and away_team_id = '.$gRes['away_team_id'];
                $res = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                var_dump($res);
                if(!empty($res))
                {
                    $aIntTemp = $this->calRecentGame($gRes['away_team_id'],$res,20);
                    $aInt = $aInt + $aIntTemp;
                }

                #历史交战
                $sMap = 'status = 1 and game_state = -1 and ((home_team_id = '.$gRes['home_team_id'].' and away_team_id = '.$gRes['away_team_id'].') or (home_team_id ='.$gRes['away_team_id'].' and away_team_id ='.$gRes['home_team_id'].'))';

                $res = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id,score')->where($sMap)->order('gtime desc')->limit(10)->select();
                var_dump('历史交战',$res);
                if(!empty($res))
                {
                    $hIntTemp = $aIntTemp = 0;
                    foreach($res as $k=>$v)
                    {
                        $score = explode('-',$v['score']);
                        if($v['home_team_id'] == $gRes['home_team_id'])
                        {
                            if($score[0] > $score[1])
                                $hIntTemp = $hIntTemp + 3;
                            else if($score[0] < $score[1])
                                $aIntTemp = $aIntTemp + 3;
                        }
                        else
                        {
                            if($score[0] > $score[1])
                                $aIntTemp = $aIntTemp + 3;
                            else if($score[0] < $score[1])
                                $hIntTemp = $hIntTemp + 3;
                        }
                    }
                    $count = count($res);
                    $n = 30/($count*3);
                    $rate = 20/30;
                    $hIntTemp = $hIntTemp*$n*$rate;
                    $aIntTemp = $aIntTemp*$n*$rate;

                    $hInt = $hInt + $aIntTemp;
                    $aInt = $aInt + $aIntTemp;
                }

                $hIntTemp = $aIntTemp = $hIntTemp2 = $aIntTemp2 = 0;
                $hGoal = $hLost = $aGoal = $aLost = 0;
                #攻击力、防守力
                var_dump('攻击力、防守力',$renRes1,$renRes2);
                if(!empty($renRes1))
                {
                    foreach($renRes1 as $k=>$v)
                    {
                        $score = explode('-',$v['score']);
                        if($v['home_team_id'] == $gRes['home_team_id'])
                        {
                            $hGoal = $hGoal + $score[0];
                            $hLost = $hLost + $score[1];
                        }
                        else
                        {
                            $hGoal = $hGoal + $score[1];
                            $hLost = $hLost + $score[0];
                        }
                    }

                    if(count($renRes1) != 10)
                    {
                        $rateG = (10/count($renRes1))*2;
                        $hGoal = $hGoal+$rateG;
                        $hLost = $hLost+$rateG;
                    }

                    if(7 >= $hGoal && $hGoal >= 0)
                        $hIntTemp = 2;
                    else if(15 >= $hGoal && $hGoal >7)
                        $hIntTemp = 4;
                    else if(23 >= $hGoal && $hGoal >15)
                        $hIntTemp = 6;
                    else if(31>=$hGoal && $hGoal >23)
                        $hIntTemp = 8;
                    else if($hGoal>31)
                        $hIntTemp = 10;

                    if(8>=$hLost && $hLost >=0)
                        $hIntTemp2 = 10;
                    else if(15>$hLost && $hLost >9)
                        $hIntTemp2 = 8;
                    else if(20>=$hLost && $hLost>15)
                        $hIntTemp2 = 6;
                    else if(29>=$hLost && $hLost >21)
                        $hIntTemp2 = 4;
                    else if($hLost>30)
                        $hIntTemp2 = 2;

                    $hInt = $hInt + $hIntTemp + $hIntTemp2;
                }
                if(!empty($renRes2))
                {
                    foreach($renRes2 as $k=>$v)
                    {
                        $score = explode('-',$v['score']);
                        if($v['home_team_id'] == $gRes['away_team_id'])
                        {
                            $aGoal = $aGoal + $score[0];
                            $aLost = $aLost + $score[1];
                        }
                        else
                        {
                            $aGoal = $aGoal + $score[1];
                            $aLost = $aLost + $score[0];
                        }
                    }

                    if(count($renRes2) != 10)
                    {
                        $rateG = (10/count($renRes2))*2;
                        $aGoal = $aGoal+$rateG;
                        $aLost = $aLost+$rateG;
                    }

                    if(7 >= $aGoal && $aGoal >= 0)
                        $aIntTemp = 2;
                    else if(15 >= $aGoal && $aGoal >7)
                        $aIntTemp = 4;
                    else if(23 >= $aGoal && $aGoal >15)
                        $aIntTemp = 6;
                    else if(31 >= $aGoal && $aGoal >23)
                        $aIntTemp = 8;
                    else if($aGoal>31)
                        $aIntTemp = 10;

                    if(8>=$aLost && $aLost >=0)
                        $aIntTemp2 = 10;
                    else if(15 >= $aLost && $aLost >9)
                        $aIntTemp2 = 8;
                    else if(20 >= $aLost && $aLost>15)
                        $aIntTemp2 = 6;
                    else if(29 >= $aLost && $aLost >21)
                        $aIntTemp2 = 4;
                    else if($aLost>30)
                        $aIntTemp2 = 2;

                    $aInt = $aInt + $aIntTemp + $aIntTemp2;
                }

                #盘口
                //$odds = $this->fbOdds([0=>$gameId]);
                $map2['game_id'] = $gameId;
                $map2['company_id'] = 3;
                $oddsRes = M('FbOdds')->field('exp_value')->where($map2)->find();
                var_dump('盘口',$oddsRes);
                if(!empty($oddsRes))
                {
                    $oArr = oddsChArr($oddsRes['exp_value']);
                    if($oArr[0][4] != '')
                        $exp = $oArr[0][4];
                    else
                        $exp = $oArr[0][1];

                    if($exp != '')
                    {
                        $expTrend = $this->calExpTrend($exp,20);
                        $hInt = $hInt + $expTrend['h'];
                        $aInt = $aInt + $expTrend['a'];
                    }
                }

                $h = round($hInt/($hInt+$aInt),2);
                $a = round($aInt/($hInt+$aInt),2);
                $rData = ['home'=>$h,'away'=>$a];
                //S('cache_fb_strength_'.$gameId,$rData,1800);
            }
       // }
        var_dump($rData);exit;
        return $rData;
    }

   /**
     +------------------------------------------------------------------------------
     * 以上为app足球5.0新增 end
     +------------------------------------------------------------------------------
    */




    /**
     +------------------------------------------------------------------------------
     * 以下为功能函数
     +------------------------------------------------------------------------------
    */

      /**
     * 根据赛事ID获取最新赔率数据 mongo fb_odds表
     * @param  array   $gameIds  赛事ID
     * @param  int     $companyID  公司ID
     * @return array  当日即时赛事数据
     */
    public function fbOdds($gameIds,$companyID = 3,$odds_data = [])
    {
        if($gameIds != null){
            if(empty($gameIds)){
                $dataService = new \Common\Services\DataService();
                $gameIds = $dataService->getGameTodayGids(1);
            }
            //使用mongo赔率数据
            $mongodb  = mongoService();
            if(!is_array($gameIds)){
                $map['game_id'] = $gameIds;
            }else{
                if(empty($gameIds)) return [];
                $map['game_id'] = [$mongodb->cmd('in')=>$gameIds];
            }
            $map['company_id'] = (int)$companyID;

            $fb_odds = $mongodb->select('fb_odds',$map,['game_id','odds','is_half']);
        }else{
            $fb_odds = $odds_data[$companyID];
        }
        if(empty($fb_odds)){
            return [];
        }
        $oddsData = [];
        foreach ($fb_odds as $k => $v) {
            $odds = array_chunk($v['odds'], 9);
            $data = [];
            switch ($v['is_half']) {
                case '0':
                    //全场
                    $data['game_id'] = $v['game_id'];
                    $data['odds'] = implode(',', $odds[0]).'^'.implode(',', $odds[1]).'^'.implode(',', $odds[2]);
                    $oddsData[$v['game_id']]['all_odds'] = $data;
                    break;
                case '1':
                    //半场
                    $data['game_id'] = $v['game_id'];
                    $data['odds'] = implode(',', $odds[0]).'^'.implode(',', $odds[1]).'^'.implode(',', $odds[2]);
                    $oddsData[$v['game_id']]['half_odds'] = $data;
                    break;
            }
        }
        //组装成之前数据，方便后续处理
        $res = [];
        foreach ($oddsData as $k => $v) {
            $data = [];
            $data['game_id']   = $k;
            $data['exp_value'] = $v['all_odds']['odds'].'^'.$v['half_odds']['odds'];
            $res[] = $data;
        }
        $rData = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $temp = [];
                $oTemp = oddsChArr($v['exp_value']);

                //全场亚
                if(!empty($oTemp[0][6]) || !empty($oTemp[0][7]) || !empty($oTemp[0][8]))
                {
                    $temp[0] = $oTemp[0][6];
                    $temp[1] = $oTemp[0][7];
                    $temp[2] = $oTemp[0][8];
                }
                else if (!empty($oTemp[0][3]) || !empty($oTemp[0][4]) || !empty($oTemp[0][5]))
                {
                    $temp[0] = $oTemp[0][3];
                    $temp[1] = $oTemp[0][4];
                    $temp[2] = $oTemp[0][5];
                }
                else
                {
                    $temp[0] = $oTemp[0][0];
                    $temp[1] = $oTemp[0][1];
                    $temp[2] = $oTemp[0][2];
                }
                //全场欧
                if(!empty($oTemp[1][6]) || !empty($oTemp[1][7]) || !empty($oTemp[1][8]))
                {
                    $temp[3] = $oTemp[1][6];
                    $temp[4] = $oTemp[1][7];
                    $temp[5] = $oTemp[1][8];
                }
                else if (!empty($oTemp[1][3]) || !empty($oTemp[1][4]) || !empty($oTemp[1][5]))
                {
                    $temp[3] = $oTemp[1][3];
                    $temp[4] = $oTemp[1][4];
                    $temp[5] = $oTemp[1][5];
                }
                else
                {
                    $temp[3] = $oTemp[1][0];
                    $temp[4] = $oTemp[1][1];
                    $temp[5] = $oTemp[1][2];
                }
                //全场大
                if(!empty($oTemp[2][6]) || !empty($oTemp[2][7]) || !empty($oTemp[2][8]))
                {
                    $temp[6] = $oTemp[2][6];
                    $temp[7] = $oTemp[2][7];
                    $temp[8] = $oTemp[2][8];
                }
                else if (!empty($oTemp[2][3]) || !empty($oTemp[2][4]) || !empty($oTemp[2][5]))
                {
                    $temp[6] = $oTemp[2][3];
                    $temp[7] = $oTemp[2][4];
                    $temp[8] = $oTemp[2][5];
                }
                else
                {
                    $temp[6] = $oTemp[2][0];
                    $temp[7] = $oTemp[2][1];
                    $temp[8] = $oTemp[2][2];
                }
                //半场亚
                if(!empty($oTemp[3][6]) || !empty($oTemp[3][7]) || !empty($oTemp[3][8]))
                {
                    $temp[9] = $oTemp[3][6];
                    $temp[10] = $oTemp[3][7];
                    $temp[11] = $oTemp[3][8];
                }
                else if (!empty($oTemp[3][3]) || !empty($oTemp[3][4]) || !empty($oTemp[3][5]))
                {
                    $temp[9] = $oTemp[3][3];
                    $temp[10] = $oTemp[3][4];
                    $temp[11] = $oTemp[3][5];
                }
                else
                {
                    $temp[9] = $oTemp[3][0];
                    $temp[10] = $oTemp[3][1];
                    $temp[11] = $oTemp[3][2];
                }
                //半场欧
                if(!empty($oTemp[4][6]) || !empty($oTemp[4][7]) || !empty($oTemp[4][8]))
                {
                    $temp[12] = $oTemp[4][6];
                    $temp[13] = $oTemp[4][7];
                    $temp[14] = $oTemp[4][8];
                }
                else if (!empty($oTemp[4][3]) || !empty($oTemp[4][4]) || !empty($oTemp[4][5]))
                {
                    $temp[12] = $oTemp[4][3];
                    $temp[13] = $oTemp[4][4];
                    $temp[14] = $oTemp[4][5];
                }
                else
                {
                    $temp[12] = $oTemp[4][0];
                    $temp[13] = $oTemp[4][1];
                    $temp[14] = $oTemp[4][2];
                }
                //半场大
                if(!empty($oTemp[5][6]) || !empty($oTemp[5][7]) || !empty($oTemp[5][8]))
                {
                    $temp[15] = $oTemp[5][6];
                    $temp[16] = $oTemp[5][7];
                    $temp[17] = $oTemp[5][8];
                }
                else if (!empty($oTemp[5][3]) || !empty($oTemp[5][4]) || !empty($oTemp[5][5]))
                {
                    $temp[15] = $oTemp[5][3];
                    $temp[16] = $oTemp[5][4];
                    $temp[17] = $oTemp[5][5];
                }
                else
                {
                    $temp[15] = $oTemp[5][0];
                    $temp[16] = $oTemp[5][1];
                    $temp[17] = $oTemp[5][2];
                }
                //记录全场初盘数据
                $temp[18] = $oTemp[0][0].'^'.$oTemp[0][1].'^'.$oTemp[0][2].'^'.$oTemp[1][0].'^'.$oTemp[1][1].'^'.$oTemp[1][2].'^'.$oTemp[2][0].'^'.$oTemp[2][1].'^'.$oTemp[2][2].'^'.$oTemp[3][0].'^'.$oTemp[3][1].'^'.$oTemp[3][2].'^'.$oTemp[4][0].'^'.$oTemp[4][1].'^'.$oTemp[4][2].'^'.$oTemp[5][0].'^'.$oTemp[5][1].'^'.$oTemp[5][2];
                $rData[$v['game_id']] = $temp;
            }
        }
        return $rData;
    }

     /**
     * 根据赛事ID获取初盘赔率数据 qc_fb_odds表
     * @param  array   $gameIds  赛事ID
     * @param  int     $companyID  公司ID
     * @return array  当日即时赛事数据
     */
    public function fbFirstOdds($gameIds,$companyID = 3)
    {
        if(empty($gameIds) || !is_array($gameIds)) return false;

        $map['game_id'] = array('in',implode(',',$gameIds));
        $map['company_id'] = $companyID;

        $obj = M('FbOdds');
        $res = $obj->field('game_id,exp_value')->where($map)->select();
        $rData = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $temp = [];
                $oTemp = oddsChArr($v['exp_value']);

                $temp[0] = $oTemp[0][0];
                $temp[1] = $oTemp[0][1];
                $temp[2] = $oTemp[0][2];

                $temp[3] = $oTemp[1][0];
                $temp[4] = $oTemp[1][1];
                $temp[5] = $oTemp[1][2];

                $temp[6] = $oTemp[2][0];
                $temp[7] = $oTemp[2][1];
                $temp[8] = $oTemp[2][2];

                $temp[9] = $oTemp[3][0];
                $temp[10] = $oTemp[3][1];
                $temp[11] = $oTemp[3][2];

                $temp[12] = $oTemp[4][0];
                $temp[13] = $oTemp[4][1];
                $temp[14] = $oTemp[4][2];

                $temp[15] = $oTemp[5][0];
                $temp[16] = $oTemp[5][1];
                $temp[17] = $oTemp[5][2];

                $rData[$v['game_id']] = $temp;
            }
        }
        return $rData;
    }

	/**
	 * 从mongo库中取出的数据源 数组 筛选初盘和即时盘进行返回
	 * @param $array mongo数组源
	 * @return array|bool 赔率数组
	 */
    public function filterMongoOdds($array)
    {
	    if(empty($array) || !is_array($array)) return false;
	    $oddsCompany = C('AOB_COMPANY_ID');
	    $oddsData = [];
	    foreach ($array as $key => $value)
	    {
	    	foreach($value['match_odds_m_asia'] as $ak => $av) {
	    	    $oddsData[$value['game_id']]['asia'][$ak][] = trim((string) $ak);
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim($oddsCompany[$ak]);
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim($av[0]);
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim(changeSnExpTwo($av[1]));
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim($av[2]);
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim($av[3]);
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim(changeSnExpTwo($av[4]));
			    $oddsData[$value['game_id']]['asia'][$ak][] = trim($av[5]);
		    }

		    foreach($value['match_odds_m_bigsmall'] as $bk => $bv) {
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim((string) $bk);
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim($oddsCompany[$bk]);
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim($bv[0]);
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim(changeSnExpTwo($bv[1]));
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim($bv[2]);
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim($bv[3]);
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim(changeSnExpTwo($bv[4]));
			    $oddsData[$value['game_id']]['bigsmall'][$bk][] = trim($bv[5]);
		    }

		    foreach($value['match_odds'] as $ek => $ev) {
	    		if (trim($ev[6]) == "" && trim($ev[7]) == "" && trim($ev[8]) == "" && trim($ev[9]) == "" && trim($ev[10]) == "" && trim($ev[11]) == "") {
	    			$oddsData[$value['game_id']]['europ'] = [];
	    			continue;
			    }
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim((string) $ek);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($oddsCompany[$ek]);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($ev[6]);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($ev[7]);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($ev[8]);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($ev[9]);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($ev[10]);
			    $oddsData[$value['game_id']]['europ'][$ek][] = trim($ev[11]);
		    }
	    }
	    return $oddsData;
    }


    /**
     * 根据赛事ID获取初盘、最新赔率数据 qc_fb_odds表
     * @param  array   $gameIds  赛事ID
     * @param  int     $companyID  公司ID
     * @return array  当日即时赛事数据
     */
    public function fbOddsIns($gameIds)
    {
        if(empty($gameIds) || !is_array($gameIds)) return false;

        $map['game_id'] = array('in',implode(',',$gameIds));

        $obj = M('FbOdds');
        $res = $obj->field('game_id,company_id,exp_value')->where($map)->select();

        $rData = [];
        $oddsCompany = C('DB_COMPANY_ODDS');
        if(!empty($res))
        {
            $aOdds = [];
            $oOdds = [];
            $dOdds = [];
            foreach($res as $k=>$v)
            {
                $oddsTemp = oddsChArr($v['exp_value']);
                if($oddsTemp[0][0] != '' && $oddsTemp[0][1] != '' && $oddsTemp[0][2] != '' && $oddsTemp[0][3] != '' && $oddsTemp[0][4] != '' && $oddsTemp[0][5] != '')
                {
                    $aTemp = [
                        0 => $v['company_id'],
                        1 => $oddsCompany[$v['company_id']],
                        2 => $oddsTemp[0][0],
                        3 => changeExp($oddsTemp[0][1]),
                        4 => $oddsTemp[0][2],
                        5 => $oddsTemp[0][3],
                        6 => changeExp($oddsTemp[0][4]),
                        7 => $oddsTemp[0][5],
                    ];
                    $aOdds[$v['game_id']][$v['company_id']] = $aTemp;
                }

                if($oddsTemp[1][0] != '' && $oddsTemp[1][1] != '' && $oddsTemp[1][2] != '' && $oddsTemp[1][3] != '' && $oddsTemp[1][4] != '' && $oddsTemp[1][5] != '')
                {
                    $oTemp = [
                        0 => $v['company_id'],
                        1 => $oddsCompany[$v['company_id']],
                        2 => $oddsTemp[1][0],
                        3 => $oddsTemp[1][1],
                        4 => $oddsTemp[1][2],
                        5 => $oddsTemp[1][3],
                        6 => $oddsTemp[1][4],
                        7 => $oddsTemp[1][5],
                    ];
                    $oOdds[$v['game_id']][$v['company_id']] = $oTemp;
                }

                if($oddsTemp[2][0] != '' && $oddsTemp[2][1] != '' && $oddsTemp[2][2] != '' && $oddsTemp[2][3] != '' && $oddsTemp[2][4] != '' && $oddsTemp[2][5] != '')
                {
                    $dTemp = [
                        0 => $v['company_id'],
                        1 => $oddsCompany[$v['company_id']],
                        2 => $oddsTemp[2][0],
                        3 => changeExp($oddsTemp[2][1]),
                        4 => $oddsTemp[2][2],
                        5 => $oddsTemp[2][3],
                        6 => changeExp($oddsTemp[2][4]),
                        7 => $oddsTemp[2][5],
                    ];
                    $dOdds[$v['game_id']][$v['company_id']] = $dTemp;
                }
            }
            $rData = [
                'asia' => $aOdds,
                'euro' => $oOdds,
                'ball' => $dOdds
            ];
        }
        return $rData;
    }

    /*足球技术类型兑换动画类型*/
    public function typeTotype($type)
    {
        $arr=[
            '1'=>'0008',
            '2'=>'1006',
            '3'=>'1005',
            //'7'=>'0008',
            '7'=>'1008',         //点球
            '8'=>'0008',
            '11'=>'1013'
        ];
        return $arr[$type];
    }

    /**
     * 根据盘口、初盘、即时盘计算倾向
     * @return array  计算结果
     */
    public function abTrend($cExp,$jExp,$hCodds,$hJodds,$aCodds,$aJodds)
    {
        $arr = ['h'=>0,'a'=>0];
        if($cExp > $jExp && $cExp > 0 && $jExp > 0)
        {
            $arr['a']++;
        }
        else if($cExp < $jExp && $cExp > 0 && $jExp > 0)
        {
            $arr['h']++;
        }
        else if($cExp > $jExp && $cExp < 0 && $jExp < 0)
        {
            $arr['a']++;
        }
        else if($cExp < $jExp && $cExp < 0 && $jExp < 0)
        {
            $arr['h']++;
        }
        else if($cExp > 0 && $jExp < 0)
        {
            $arr['a']++;
        }
        else if($cExp < 0 && $jExp > 0)
        {
            $arr['h']++;
        }
        else if($cExp == $jExp)
        {
            if($hCodds == $hJodds && $aCodds == $aJodds)
            { //盘口、赔率都不变，赔率低倾向胜
                if($cExp == 0)
                {
                    if($hJodds > $aJodds)
                        $arr['a']++;
                    else
                        $arr['h']++;
                }
                else if($cExp > 0)
                {
                    $arr['h']++;
                }
                else
                {
                    $arr['a']++;
                }
            }
            else if($hCodds == $hJodds && $aCodds != $aJodds)
            {
                if($aCodds > $aJodds)
                    $arr['a']++;
                else
                    $arr['h']++;

            }
            else if($hCodds != $hJodds && $aCodds == $aJodds)
            {
                if($hCodds > $hJodds)
                    $arr['h']++;
                else
                    $arr['a']++;
            }
            else
            {
                if($hCodds > $hJodds && $aCodds < $aJodds)
                {
                    $arr['h']++;
                }
                else if($hCodds < $hJodds && $aCodds > $aJodds)
                {
                    $arr['a']++;
                }
                else
                {
                    if($hJodds > $aJodds)
                    {
                        $arr['a']++;
                    }
                    else if($hJodds < $aJodds)
                    {
                        $arr['h']++;
                    }
                    else
                    {
                        if($hCodds > $aCodds)
                            $arr['a']++;
                        else
                            $arr['h']++;
                    }
                }
            }

        }
        return $arr;
    }

    /**
     * 根据欧赔赔率即时公司倾向
     * @param  float     $hCodds  初盘赔率
     * @param  float     $hJodds  即时赔率
     * @return array   计算结果
     */
    public function eurTrend($hCodds,$hJodds)
    {
        if($hCodds == '' || $hCodds === null || $hJodds == '' || $hJodds === null) return false;

        $arr = ['h'=>0,'d'=> 0,'a'=>0];
        if($hCodds > $hJodds)
        {
            $arr['h']++;
        }
        else if($hCodds < $hJodds)
        {
            $arr['a']++;
        }
        else
        {
            $arr['d']++;
        }
        return $arr;
    }

     /**
     * 根据球队计算近期赛事战力积分
     * @param  float     $hCodds  初盘赔率
     * @param  float     $hJodds  即时赔率
     * @return array   计算结果
     */
    public function calRecentGame($teamId,$arr,$snum)
    {
        if(empty($teamId) || empty($arr)) return false;

        $count = count($arr);
        $n = 30/($count*3);
        $rate = $snum/30;
        $int = 0;
        foreach($arr as $k=>$v)
        {
            if($v['home_team_id'] == $teamId)
            {
                $score = explode('-',$v['score']);
                if($score[0] > $score[1])
                {
                    $int = $int+3;
                }
                else if($score[0] == $score[1])
                {
                    $int = $int+1;
                }
            }
            else
            {
                $score = explode('-',$v['score']);
                if($score[0] < $score[1])
                {
                    $int = $int+3;
                }
                else if($score[0] == $score[1])
                {
                    $int = $int+1;
                }
            }
        }
        return round($int*$n*$rate,2);
    }

    /**
     * 根据球队盘口计算战力
     * @param  float     $hCodds  初盘赔率
     * @param  float     $hJodds  即时赔率
     * @return array   计算结果
     */
    public function calExpTrend($exp,$snum = 20)
    {
        $arr = ['h'=>0,'a'=>0];
        $rate = $snum/10;

        if($exp == 0 || $exp == '-0') return $arr;

        $val = 0;
        $ha = true;
        if(strpos($exp,'-') !== false)
        {
            $exp = str_replace('-','',$exp);
            $ha = false;
        }

        if($exp <= 0.25){
            $val = 2;
        }else if($exp > 0.25 && $exp < 1){
            $val = 4;
        }else if($exp >= 1 && $exp <= 1.25){
            $val = 6;
        }else if($exp > 1.25 && $exp <= 1.75){
            $val = 8;
        }else if($exp > 1.75 && $exp <= 1.75){
            $val = 10;
        }

        if($ha == true)
            $arr['h'] = $val * $rate;
        else
            $arr['a'] = $val * $rate;

        return $arr;
    }


	/**
	 * @param $array 数组
	 * @param $name 获取对象名称
	 * @param $team 获取对象队伍
	 * @return string 返回组合数据后的字符串
	 */
	public function getStatistics($array ,$name, $team)
	{
		if (!empty($array)) {
			if ($team == 'h') {
				if ($name == 'ControlPrecent') {
					return $array[$name.'_'.$team.'3'].'%/'.$array[$name.'_'.$team.'10'].'%';
				} else {
					return $array[$name.'_'.$team.'3'].'/'.$array[$name.'_'.$team.'10'];
				}
			} elseif ($team == 'g') {
				if ($name == 'ControlPrecent') {
					return $array[$name.'_'.$team.'3'].'%/'.$array[$name.'_'.$team.'10'].'%';
				} else {
					return $array[$name.'_'.$team.'3'].'/'.$array[$name.'_'.$team.'10'];
				}
			}
		} else {
			return "";
		}
	}


	/**
	 * 获取联赛统计数据
	 * @param $array 数据源
	 * @param $teamName 队伍名称
	 * @return array 返回清洗后数据
	 */
	public function getLeagueRank($array, $teamName)
	{
		$data = [];
		foreach ($array as $key => $value) {
			$data[$key][] = $teamName;
			$data[$key][] = $value['titie'];
			$data[$key][] = $value['toal'];
			$data[$key][] = $value['win'];
			$data[$key][] = $value['draw'];
			$data[$key][] = $value['lose'];
			$data[$key][] = $value['goal'];
			$data[$key][] = $value['goal_loss'];
			$data[$key][] = $value['score'];
			$data[$key][] = $value['rank'];
		}
		return $data;
	}


	/**
	 * 近期交战数据解析
	 * @param $array
	 * @param $teamName
	 * @param $union_color
	 * @param $homeTeamId
	 * @return array
	 */
	public function getWebRecentCombat($array, $cpArray,  $teamName, $union_color)
	{
		$data = [];
		foreach ($array as $key => $value) {
			if ($key == 10) { break; }
			$homeScore = $value['HomeScore'];
			$awayScore = $value['GuestScore'];
			$data[$key][] = $teamName;
			$data[$key][] = date("y/m/d", strtotime($value['MatchTimeStr']));
			$data[$key][] = (string) $value['SclassID'];
			$data[$key][] = $value['SclassName'];
			$data[$key][] = $union_color;
			$data[$key][] = (string) $value['HomeTeamID'];
			$data[$key][] = strip_tags($value['HomeTeam']);
			$data[$key][] = (string) $value['GuestTeamID'];
			$data[$key][] = strip_tags($value['GuestTeam']);
			$data[$key][] = (string) $homeScore;
			$data[$key][] = (string) $awayScore;
			$data[$key][] = (string) $value['HomeHalfScore'];
			$data[$key][] = (string) $value['GuestHalfScore'];
			$data[$key][] = changeSnExpTwo($value['Letgoal']);
			$data[$key][] = (string) $cpArray[$key][12];
			$data[$key][] = (string) $cpArray[$key][13];
			$data[$key][] = (string) $cpArray[$key][14];
			$data[$key][] = (string) $value['ScheduleID'];
		}
		return $data;
	}


	/**
	 * 历史交战
	 * @param $recentCombat
	 * @param $match_data
	 * @param $homeTeamId
	 * @return array
	 */
	public function getMatchFight($recentCombat, $match_data, $homeTeamId)
	{
		$data = [];
		foreach ($recentCombat as $key => $value) {
			$home_score = $value['home_team_score'];
			$away_score = $value['away_team_score'];
			$data[$key][] = date("Y/m/d", strtotime($value['game_time']));
			$data[$key][] = $value['union_id'];
			$data[$key][] = (string) $value['league_name'];
			$data[$key][] = (string) $match_data[$key][3];
			$data[$key][] = $value['home_team_id'];
			$data[$key][] = $value['home_team_name'];
			$data[$key][] = $value['away_team_id'];
			$data[$key][] = $value['away_team_name'];
			$data[$key][] = $home_score;
			$data[$key][] = $away_score;
			$data[$key][] = $value['home_team_half_score'];
			$data[$key][] = $value['away_taem_half_score'];
			$data[$key][] = $value['odds'][1];
			$data[$key][] = (string) $match_data[$key][12];
			$data[$key][] = (string) $this->splitGameResult(getHandcpWin($home_score.'-'.$away_score,
				$value['odds'][1], 1, ($value['home_team_id'] == $homeTeamId) ? 1 : 0));
			$data[$key][] = (string) $match_data[$key][14];
			$data[$key][] = (string) $match_data[$key][15];
		}
		return $data;
	}




	/**
	 * 获取队伍名称 简繁英 根据参数变化
	 * @param $teamId
	 * @param $lang
	 * @return mixed
	 */
	public function getTeamNameFromId($teamId, $lang)
	{
		$mongodb = mongoService();
		$teamName = $mongodb->select('fb_team',['team_id'=>$teamId],['team_id', 'team_name'])[0];
		if ($lang == 1) {$index = 0;} elseif ($lang == 2) {$index = 1;} elseif ($lang == 3) {$index =2;} else{ $index = 1;}
		return $teamName['team_name'][$index];
	}

	/**
	 * 过滤参数字 返回指定数字
	 * @param $result
	 * @return string
	 */
	public function splitGameResult($result)
	{
		if ($result == "赢") { return "1"; } else if ($result == "走") { return "0"; } else if ($result == "输") { return "-1";} else { return $result; }
	}


	/**
	 * 获取伤停数据
	 * @param $stData
	 * @return array
	 */
	public function getSt($stData)
	{
		$data = [];
		foreach ($stData as $key => $value) {
			$data[$key][] = $value['number'];
			$data[$key][] = $value['name'][0];
			$data[$key][] = $value['name'][1];
			$data[$key][] = $value['name'][2];
			$data[$key][] = $value['name'][3];
		}
		return $data;
	}

	/**
	 * 获取数据对比数据
	 * @param $compare
	 * @return array
	 */
	public function getCompareData($compare)
	{
		$data = [];
		foreach ($compare as $key => $value) {
			$data[$key][] = (string) $value['avg_obtain'];
			$data[$key][] = (string) $value['draw_scale'];
			$data[$key][] = (string) $value['lose'];
			$data[$key][] = (string) $value['lose_scale'];
			$data[$key][] = (string) $value['net'];
			$data[$key][] = (string) $value['obtain'];
			$data[$key][] = (string) $value['team_id'];
			$data[$key][] = (string) $value['total_match'];
			$data[$key][] = (string) $value['win_scale'];
		}
		return $data;
	}


	/**
	 * 解析相同历史盘口的数据
	 * @param $same_odd
	 * @return array
	 */
	public function getSameOdd($same_odd)
	{
		$data = [];
		foreach ($same_odd as $key => $value) {
			$data[$key][] = (string) $value['league_name'];
			$data[$key][] = (string) $value['game_time'];
			$data[$key][] = (string) $value['home_team_name'];
			$data[$key][] = (string) $value['home_team_id'];
			$data[$key][] = changeSnExpTwo($value['first_odd']);
			$data[$key][] = (string) $value['away_team'];
			$data[$key][] = (string) $value['away_team_id'];
			$data[$key][] = (string) explode("-", $value['score'])[0];
			$data[$key][] = (string) explode("-", $value['score'])[1];
			$data[$key][] = (string) $value['panlu'];
		}
		return $data;
	}


	/**
	 * 联赛盘路走势
	 * @param $array
	 * @param $teamName
	 * @return array
	 */
	public function getReferee($array, $teamName)
	{
		$data = [];
		foreach ($array as $key => $value) {
			$temp = [];
			if ($key != 'near_six') {
				$temp[] = $teamName;
				$temp[] = $this->returnName($key);
				$temp[] = (string) ($value['win'] + $value['draw'] + $value['lose']);
				$temp[] = $value['win'];
				$temp[] = $value['draw'];
				$temp[] = $value['lose'];
				$temp[] = $this->persentNum($value['win_rate']);
				$temp[] = $value['big'];
				$temp[] = $this->persentNum($value['big_rate']);
				$temp[] = $value['small'];
				$temp[] = $this->persentNum($value['small_rate']);
			} else {
				$temp[] = $teamName;
				$temp[] = "近6场";
				$temp[] = "6";
				$temp[] = implode(" ", $value['letGoal']);
				$temp[] = (round($this->changeWin($value['letGoal']), 3) * 100).'%';
				$temp[] = "查看";
				$temp[] = implode(" ", $value['bigSmall']);
				$temp[] = "";
				$temp[] = "";
				$temp[] = "";
				$temp[] = "";
			}
			$data[] = $temp;
		}
		return $data;
	}


	/**
	 * 解析未来三天
	 * @param $array
	 * @param $team_id
	 * @return array
	 */
	public function future_three_data($array, $team_id, $team_name)
	{
		$data = [];
		foreach ($array as $key => $value)
		{
			$data[$key][] = $team_name;
			$data[$key][] = $value['league_name'];
			$data[$key][] = date("Y/m/d",strtotime($value['game_time']));
			$site = $this->changeMain($team_id, $value['home_team_id'], $value['away_team_id']);
			$data[$key][] = $site;
			$data[$key][] =$this->splitTeamName($site, $value['home_team_name'], $value['away_team_name']);
			$data[$key][] = $value['day_left'].' 天';
		}
		return $data;
	}


	/**
	 * 获取技术统计
	 * @param $skill 所有技术统计值
	 * @return array
	 */
	public function getSkill($skill)
	{
		//赛事统计技术key值
		//3:射门,4:射中,5:犯规,6:角球,8:角球,9:越位,11:黄牌,13:红牌,14:控球率,43:进攻,44:危险进攻
		$dataKey = [3, 4, 5,  6, 8, 9, 11, 13, 14,19, 44];
		$data = [];
		foreach ($dataKey as $key => $value) {
			$temp = [];
			$temp[] = (string) $value;
			// 有时控球率会出现无百分号时保险设计
			$val0 = isset($skill[$value][0]) ? $skill[$value][0] : '';
			$val1 = isset($skill[$value][1]) ? $skill[$value][1] : '';

			if ($value== 14) {

				$temp[] = (string) $this->returnHundred($val0);
				$temp[] = (string) $this->returnHundred($val1);
			} else {
				$temp[] = (string) $val0;
				$temp[] = (string) $val1;
			}
			if (isset($skill[$value][0]) && isset($skill[$value][1])) {
				$data[] = $temp;
			}
		}
		return $data;
	}

	/**
	 * 获取比赛事件
	 * @param $corner 角球数据
	 * @param $detail 事件数据
	 * @param $game_id
	 * @param $home_id
	 * @param $away_id
	 * @return array
	 */
	public function getDetail($corner, $detail, $game_id, $home_id, $away_id)
	{
		$detData = [];
		$awayCorner = $homeCorner = 1;
		//遍历角球事件区分主客队
		if (!empty($corner[0])) {
			foreach ($corner as $key => $value) {
				$temp =[];
				$temp[] = (string) $game_id;
				if ($value[0] == $home_id) {
					$temp[] = '1';
					// 技术类型
					$temp[] = '99';
					$temp[] = $value[1];
					$temp[] = "第".$homeCorner."个角球";
					$temp[] = '';
					$temp[] = "第".$homeCorner."个角球";
					$homeCorner++;
				} elseif ($value[0] == $away_id) {
					$temp[] = '0';
					$temp[] = '99';
					$temp[] = $value[1];
					$temp[] = "第".$awayCorner."个角球";
					$temp[] = '';
					$temp[] = "第".$awayCorner."个角球";
					$awayCorner++;
				}
				$detData[] = $temp;
			}
		}
		$detailData = [];
		// 遍历事件插入比赛id
		foreach ($detail as $key => $value) {
		    if(!is_array($value)){
		        $value = (array)$value;
		    }
			array_splice($value, 0,0, (string) $game_id);
			$detailData[] = $value;
		}
		$all = array_merge($detailData, $detData);
		// 获取排序字段
		foreach ($all as $key => $value) {
			$sort[] = $value[3];
		}
		array_multisort($sort, SORT_ASC, SORT_NUMERIC, $all);
		return $all;
	}


	//判断主客场
	public function changeMain($team_id, $home_id, $away_id)
	{
		if ($team_id == $home_id) {
			return "主";
		} elseif ($team_id == $away_id) {
			return "客";
		} else {
			return "主";
		}
	}

	//判断位置分析主客场
	public function splitTeamName($site, $home, $away)
	{
		if ($site == "主") {
			return $away;
		} elseif ($site == "客") {
			return $home;
		}
	}

	//判断胜利率
	public function changeWin($array)
	{
		$num = 0;
		foreach ($array as $v) {
			if ($v == "赢"){
				$num++;
			}
		}
		return $num/6;
	}

	//返回数据所在数据名称
	public function returnName($key)
	{
		if ($key == 'total') { return "总"; } elseif ($key == "home") { return "主场"; } elseif ($key == "away") { return "客场"; }
	}

	//返回百分比保险数字
	public function returnHundred($string)
	{
		if (strpos($string, '%')) {
			return $string;
		} else {
			return $string.'%';
		}
	}

	// 修复字符串变化
	public function changeString($string)
	{
		$temp = "";
		foreach ($string as $key => $value) {
			if (!($value === '-')) {
				$temp .= $value." ";
			}
		}
		return $temp;
	}

	public function persentNum($string)
	{
		if ($string === "") {
			return '0%';
		}
		return $string;
	}

	// 判断字符中包含null 或 - 返回空字符串
	public function NullChange($string)
	{
		if ($string === null || trim($string) === '-') {
			return '';
		}
		return $string;
	}

    //查询球队是否有路珠走势
	public function checkGoodRule($mongo, $lzTeamIds){
        if($lzTeamIds){
            //获取球队路珠
            $lzTeams = $mongo->select('fb_team',
                ['team_id' => ['$in' => $lzTeamIds]],
                ['team_id', 'team_luzhu.haolu']
            );

            foreach($lzTeams as $lzk => $lzv){
                $teamLuzhu = $lzv['team_luzhu'];
                if($teamLuzhu){
                    $lzMaps[$lzv['team_id']] = 1;
                    $teamHaolu = array_sum(array_values($teamLuzhu['haolu']));
                    if($teamHaolu >= 1){
                        $lzMaps[$lzv['team_id']] = 2;//有好路
                    }
                }
            }
        }
        return $lzMaps;
    }



    /**
     +------------------------------------------------------------------------------
     * 以上为功能函数
     +------------------------------------------------------------------------------
    */

    /**
     * [getDataList 获取接口数据]
     * @return void
     */
    public function getDataList()
    {
        $this->data = include 'interfaceArr.php';
    }
}