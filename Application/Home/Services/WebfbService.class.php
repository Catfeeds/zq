<?php
/**
 +------------------------------------------------------------------------------
 * WebfbService   Webfb服务类（1.2）
 +------------------------------------------------------------------------------
 * Copyright (c) 2016 http://www.qqty.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author huangmg <huangmg@qc.mail>
 +------------------------------------------------------------------------------
*/
namespace Home\Services;

use Common\Mongo\GambleHallMongo;
class WebfbService
{
    protected $data;

    public function __construct()
    {
        $this->getDataList();
    }

    /**
     * 判断赛事是否能推荐
     */
    public function checkGamble($v)
    {
        if($v['is_gamble'] == null){
            //mysql没数据使用默认数值
            $v['is_gamble'] = 1;
            $v['is_show']   = 0;
        }
        if ($v['is_gamble'] != 1 || ($v['is_sub'] > 2 && $v['is_show'] != 1)) {
            return 0;
        }
        if ($v['fsw_exp'] == '' || $v['fsw_exp_home'] == '' || $v['fsw_exp_away'] == '' || $v['fsw_ball'] == '' || $v['fsw_ball_home'] == '' || $v['fsw_ball_away'] == '') {
            return 0;
        }
        return 1;
    }

    public function fbtodayList($unionId,$subId ='')
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

        if(empty($baseRes)) return [];

        $rData = $unionIdArr = $gameIdArr = [];
        foreach($baseRes as $k=> $v)
        {
            $gameIdArr[]  = (int)$v['game_id'];
            $unionIdArr[] = (int)$v['union_id'];
        }

        if(!$gameArr = S('cache_fbtodayList_mysqlGame')){
            //获取mysql业务数据
            $GameFbinfo = M('GameFbinfo')
                ->field("game_id,is_gamble,is_show,status,web_video,app_video,is_video")
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
                ['union_id','union_name','country_id','level','union_or_cup','union_color','is_league']
            );
            foreach ($union as $k => $v) {
                $unionArr[$v['union_id']] = $v;
            }
            S('cache_fbtodayList_union', $unionArr, 300);
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

        if(!$newsArr = S('web_fb_newsArr')){
            //获取是否有情报
            $newsArr = M('PublishList')->where(['game_id'=>['in',$gameIdArr],'class_id'=>['in',C('informationIdArr')]])->group('game_id')->getField('game_id',true);
            S('web_fb_newsArr',$newsArr,120);
        }
        
        //获取赔率
        $appfbService = new \Home\Services\AppfbService();

        $unionInfo = $gameInfo = [];
        foreach($baseRes as $k=> $v)
        {
            if(stripos($v['home_team_name'][0],'测试') !== false || stripos($v['away_team_name'][0],'测试') !== false || stripos($v['home_team_name'][0],'test') !== false || stripos($v['away_team_name'][0],'test') !== false){
                continue;
            }
            //mysql赛事显示控制
            $mysqlGame = $gameArr[$v['game_id']];
            if(isset($mysqlGame['status']) && $mysqlGame['status'] != 1) continue;
            //联盟表数据
            $unionData = $unionArr[$v['union_id']];
            $unionLevel = isset($unionData['level']) ? $unionData['level'] : 3;

            $val = [];
            $val[0] = $v['game_id'];
            $val[1] = $v['union_id'];
            $unionTemp = $v['union_name'];

            if(is_array($unionTemp)){
                $val[2] = isset($unionTemp[0])?$unionTemp[0]:'';
                $val[3] = isset($unionTemp[1])?$unionTemp[1]:'';
                $val[4] = isset($unionTemp[2])?$unionTemp[2]:'';
            }else{
                $unionTemp = $unionData['union_name'];
                $val[2] = isset($unionTemp[0])?$unionTemp[0]:'';
                $val[3] = isset($unionTemp[1])?$unionTemp[1]:'';
                $val[4] = isset($unionTemp[2])?$unionTemp[2]:'';
            }
            
            $val[5] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
            $val[6] = $unionLevel;
            $val[7] = $v['game_state'];
            $game_start_timestamp = TellRealTime($v['start_time'],$v['game_start_timestamp'],$v['game_starttime'],$v['game_state']);
            $gameTime = explode('-',date('Ymd-H:i',$game_start_timestamp));
            $val[8]  = $gameTime[0];
            $val[9]  = $v['start_time'] ? :$gameTime[1];
            $val[10] = isset($v['game_half_datetime']) ? date('YmdHis',strtotime($v['game_half_datetime'])) : '';
            $val[11] = !empty($v['home_team_id'])?$v['home_team_id']:'';
            $val[12] = !empty($v['away_team_id'])?$v['away_team_id']:'';
            $htName  = $v['home_team_name'];
            $atName  = $v['away_team_name'];
            $val[13] = isset($htName[0])?$htName[0]:'';
            $val[14] = isset($htName[1])?$htName[1]:'';
            $val[15] = isset($htName[2])?$htName[2]:'';
            $val[16] = isset($atName[0])?$atName[0]:'';
            $val[17] = isset($atName[1])?$atName[1]:'';
            $val[18] = isset($atName[2])?$atName[2]:'';
            $val[19] = $v['home_team_rank'] ?:'';
            $val[20] = $v['away_team_rank'] ?:'';
            $score   = explode('-',$v['score']);
            $val[21] = $v['game_state'] != 0 ? $score[0] : '';
            $val[22] = $v['game_state'] != 0 ? $score[1] : '';
            $half_score = explode('-',$v['half_score']);
            $val[23] = $v['game_state'] != 0 ? $half_score[0] : '';
            $val[24] = $v['game_state'] != 0 ? $half_score[1] : '';
            #红黄牌
            $red     = explode('-',$v['red_card']);
            $val[25] = $v['game_state'] != 0 ? $red[0] : 0;
            $val[26] = $v['game_state'] != 0 ? $red[1] : 0;
            $yellow  = explode('-',$v['yellow_card']);
            $val[27] = $v['game_state'] != 0 ? $yellow[0] : 0;
            $val[28] = $v['game_state'] != 0 ? $yellow[1] : 0;
            #角球
            $corner  = explode('-',$v['corner']);
            $val[29] = $v['game_state'] != 0 ? $corner[0] : 0;
            $val[30] = $v['game_state'] != 0 ? $corner[1] : 0;
            #半角留位
            $val[31] = '';
            $val[32] = '';
            #初盘赔率
            $fbOdds    = $appfbService->fbOdds(null,3,$v['odds_data']);
            $odds_data = isset($fbOdds[$v['game_id']]) ? $fbOdds[$v['game_id']] : '';
            $chupan  = !empty($odds_data) ? explode("^", $odds_data[18]) : '';
            $val[33] = !empty($chupan[0]) ? $chupan[0] : '';   //主队亚盘初盘赔率
            $val[34] = !empty($chupan[1]) ? handCpSpread($chupan[1]) : '';   //亚盘初盘盘口
            $val[35] = !empty($chupan[2]) ? $chupan[2] : '';   //客队亚盘初盘赔率
            $val[36] = !empty($chupan[6]) ? $chupan[6] : '';   //主队大小初盘赔率
            $val[37] = !empty($chupan[7]) ? handCpSpread($chupan[7]) : '';   //大小初盘盘口
            $val[38] = !empty($chupan[8]) ? $chupan[8] : '';   //客队大小初盘赔率

            $val[39] = $v['is_go'];  //是否滚球
            //是否有视频直播
            $val[40] = 0;
            $web_video = $mysqlGame['web_video'];
            if(in_array($v['game_state'],[0, 1, 2, 3, 4, 5]) && !empty($web_video) && $mysqlGame['is_video'] == 1){
                $videoNum = 0;
                $web_video = json_decode($web_video,true);
                //判断是否有直播链接
                foreach ($web_video as $web => $url) {
                    if($url['weburl'] != ''){
                        $videoNum++;
                    }
                }
                $val[40] =  $videoNum > 0 ? 1 : 0;
            }

            //是否有动画直播
            if($v['is_flash'] == 1 && in_array($v['game_state'],[0, 1, 2, 3, 4, 5]))
            {
                $val[41] = 1;
            } else {
                $val[41] = 0;
            }

            $val[42] = $v['is_sporttery'] ? : 0;
            $val[43] = $v['spottery_num'] ? : '';
            //是否能推荐
            $val[44] = (string)$this->checkGamble([
                'game_id'       => $v['game_id'],
                'is_gamble'     => $mysqlGame['is_gamble'],
                'is_show'       => $mysqlGame['is_show'],
                'is_sub'        => $unionLevel,
                'fsw_exp'       => $val[33],
                'fsw_exp_home'  => $val[34],
                'fsw_exp_away'  => $val[35],
                'fsw_ball'      => $val[36],
                'fsw_ball_home' => $val[37],
                'fsw_ball_away' => $val[38],
            ]);
            //天气处理
            $val[45] = '';
            $weather_class = '';
            if ($v['field_weather'] != '') {
                $weatherArr = explode('^', $v[field_weather]);
                $weatherStr = $weatherArr[1];
                if(!empty($weatherStr)){
                    switch ($weatherStr) {
                        case '晴天':
                        case '天晴':
                        case '大致天晴':
                            $weather_class = 'weather-qt';
                            $weatherStr = '晴天';
                            break;
                        case '阴天':
                            $weather_class = 'weather-yt';
                            break;
                        case '多云':
                            $weather_class = 'weather-dy';
                            break;
                        case '少云':
                        case '间中有云':
                            $weather_class = 'weather-sy';
                            $weatherStr = '少云';
                            break;
                        case '阵雨':
                            $weather_class = 'weather-zy';
                            $weatherStr = '阵雨';
                            break;
                        case '烟雾':
                            $weather_class = 'weather-yw';
                            break;
                        case '霾'  :
                            $weather_class = 'weather-l';
                            break;
                        case '雾'  :
                        case '有雾':
                            $weather_class = 'weather-w';
                            $weatherStr = '雾';
                            break;
                        case '毛毛雨' :
                        case '微雨':
                            $weather_class = 'weather-mmy';
                            $weatherStr = '小雨';
                            break;
                        case '局部多云':
                            $weather_class = 'weather-jbdy';
                            break;
                        case '零散雷雨':
                        case '雷暴':
                        case '雷陣雨':
                            $weather_class = 'weather-lsly';
                            $weatherStr = '雷阵雨';
                            break;
                        case '大雪':
                            $weather_class = 'weather-dx';
                            break;
                        case '小雪':
                        case '雪':
                            $weather_class = 'weather-xx';
                            $weatherStr = '小雪';
                            break;
                    }
                    $val[45] = $weatherArr[1] . '<br/>' . $weatherArr[2];
                }
            }
            $val[46] = $weather_class; 
            //即时赔率
            $val[47] = D('GambleHall')->doFswOdds($odds_data,$v['game_state'],$v['goal_data']?:'',1);
            //是否有情报
            $val[48] = in_array($v['game_id'], $newsArr) ? 1 : 0;
            //赛事数据
            $val[49] = isset($v['explain']) && $v['explain'] != '' ? $v['explain'] : '';
            $val[50] = isset($v['remark']) && $v['remark'] != '' ? $v['remark'] : '';
            //判断联赛或者杯赛
            $is_league = $unionArr[$v['union_id']]['is_league'];
            $union_or_cup = $unionArr[$v['union_id']]['union_or_cup'];
            $val[51] = $is_league ? $is_league : $union_or_cup;
            $val[52] = $game_lives && in_array($v['game_id'], $game_lives) ? '1' : '0';
            $sort1[] = $v['game_state'];
            $sort2[] = $game_start_timestamp;
            $sort3[] = $v['game_id'];
            $gameInfo[] = $val;

            if($unionData)
            {
                $uVal = [];
                $uVal[0] = $v['union_id'];             //联赛id
                $uVal[1] = isset($unionTemp[0])?$unionTemp[0]:''; //联赛名称（简体）
                $uVal[2] = isset($unionTemp[1])?$unionTemp[1]:''; //联赛名称（繁体）
                $uVal[3] = isset($unionTemp[2])?$unionTemp[2]:''; //联赛名称（英文）
                $uVal[4] = !empty($v['union_color'])?$v['union_color']:'#aeba4b'; //联赛背景
                $uVal[5] = $unionData['union_or_cup']; //联赛杯赛，1是联赛 2是杯赛
                $uVal[6] = $unionLevel;                //联赛级别
                $uVal[7] = 1;                          //是否有资料库，1是0否
                $uVal[8] = $unionData['country_id'];   //国家ID
                $uVal[9] = 0;                          //后台自定义排序
                $unionInfo[$v['union_id']] = $uVal;
            }
        }

        //赛事排序
        array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$sort3,SORT_ASC,$gameInfo);

        $unionLevel = $unionSort = array();
        //统计联赛的赛事数
        foreach ($unionInfo as $key => $value) {
            $gameCount = 0;
            foreach ($gameInfo as $k => $v) {
                if ($value[0] == $v[1])//判断是否为同一联赛
                {
                    $gameCount++;
                }
            }
            $unionInfo[$key][10] = $gameCount;
            //级别
            $unionLevel[] = $value[6];
            $unionSort[]  = $value[9];
        }
        //级别排序--升序
        array_multisort($unionSort, SORT_ASC, $unionLevel, SORT_ASC, $unionInfo);
        $rData = ['info'=>$gameInfo,'union' =>$unionInfo];
        
        return $rData;
    }

    /**
     * 完场赛事
     * @param  string   $date  比赛日期
     * @param  string   $unionId  联赛ID
     * @param  string   $subId  联赛级别
     * @return array 完场赛事数组
     * @author huangmg 2016-12-27
     */
    public function fbOverList($date,$unionId,$subId ='')
    {
        $sDate = date('Ymd');
        if($date > $sDate) return array();

        $GameFbinfo = M('GameFbinfo');
        $map['a.status'] = 1;
        $map['game_state'] = -1;

        if(!empty($subId)) $map['is_sub'] =array('in',$subId);
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);

        if(!empty($date))
        {
            $startTime =strtotime($date.' 10:32:00');
            $endTime = $startTime + 3600*24;
        }
        else
        {
           if(strtotime('10:32:00') < time())
            {
                $startTime = strtotime('10:32:00');
                $endTime = strtotime('10:32:00')+3600*24;
            }
            else
            {
                $startTime =strtotime('10:32:00')-3600*24;
                $endTime = strtotime('10:32:00');
            }
        }
        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));

        $baseRes = $GameFbinfo->alias('a')->field('a.id,game_id,a.union_id,a.union_name as union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,home_team_id,away_team_id,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,is_video,is_flash,is_betting,bet_code,is_go,is_gamble,is_show,u.union_name as u_name,u.is_union,u.is_lib,country_id')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,bet_code,is_sub,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $unionArr = $gameinfo = [];
            foreach($baseRes as $k=> $v)
            {
                if(stripos($v['home_team_name'],'测试') !== false || strpos($v['away_team_name'],'测试') !== false || strpos($v['home_team_name'],'test') !== false || strpos($v['away_team_name'],'test') !== false)
                {

                    unset($baseRes[$k]);
                    continue;
                }
                if($v['is_sub'] === null || $v['is_sub'] >3)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['game_state'] == -14 || $v['game_state'] == -11) continue;          //屏蔽待定和推迟
                if($v['gtime'] + 120 < time() && $v['game_state'] == 0) continue;          //过了开场时间未开始
                if($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) continue;      //140分钟还没结束

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']) && $v['union_name'] !=',,')
                    $unionTemp = explode(',',$v['union_name']);
                else
                    $unionTemp = explode(',',$v['u_name']);
                $val[2] = isset($unionTemp[0])?$unionTemp[0]:'';
                $val[3] = isset($unionTemp[1])?$unionTemp[1]:'';
                $val[4] = isset($unionTemp[2])?$unionTemp[2]:'';
                $val[5] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[6] = $v['is_sub'] !== null?$v['is_sub']:'';
                $val[7] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[8] = $gameTime[0];
                $val[9] = $gameTime[1];
                //$tempTime = explode(',',$v['game_half_time']);
                //$tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                //$tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                //$val[10] = implode('',$tempTime);
                $val[10] = $v['game_half_time'];
                $val[11] = !empty($v['home_team_id'])?$v['home_team_id']:'';
                $val[12] = !empty($v['away_team_id'])?$v['away_team_id']:'';
                $htName = explode(',',$v['home_team_name']);
                $atName = explode(',',$v['away_team_name']);
                $val[13] = isset($htName[0])?$htName[0]:'';
                $val[14] = isset($htName[1])?$htName[1]:'';
                $val[15] = isset($htName[2])?$htName[2]:'';
                $val[16] = isset($atName[0])?$atName[0]:'';
                $val[17] = isset($atName[1])?$atName[1]:'';
                $val[18] = isset($atName[2])?$atName[2]:'';
                $home_rank = $v['home_team_rank'];
                $away_rank = $v['away_team_rank'];
                $val[19] = $home_rank !== false?$home_rank:'';
                $val[20] = $away_rank !== false?$away_rank:'';
                $score = explode('-',$v['score']);
                $val[21] = $score[0];
                $val[22] = isset($score[1])?$score[1]:'';
                $half_score = explode('-',$v['half_score']);
                $val[23] = $half_score[0];
                $val[24] = isset($half_score[1])?$half_score[1]:'';
                #红黄牌
                if(!empty($v['red_card']))
                {
                    $red = explode('-',$v['red_card']);
                    $val[25] = $red[0];
                    $val[26] = $red[1];
                }
                else
                {
                    $val[25] = '0';
                    $val[26] = '0';
                }
                if(!empty($v['yellow_card']))
                {
                    $yellow = explode('-',$v['yellow_card']);
                    $val[27] = $yellow[0];
                    $val[28] = $yellow[1];
                }
                else
                {
                    $val[27] = '0';
                    $val[28] = '0';
                }
                #角球
                if(!empty($v['corner']))
                {
                    $corner = explode('-',$v['corner']);
                    $val[29] = $corner[0];
                    $val[30] = $corner[1];
                }
                else
                {
                    $val[29] = '0';
                    $val[30] = '0';
                }
                #半角留位
                $val[31] = '';
                $val[32] = '';

                $val[33] = !empty($v['fsw_exp_home'])?$v['fsw_exp_home']:'';  //主队亚盘初盘赔率
                if($v['fsw_exp'] == '-0') $v['fsw_exp'] = '0';
                $val[34] = $v['fsw_exp'] !== null?$v['fsw_exp']:'';   //亚盘初盘盘口
                $val[35] = !empty($v['fsw_exp_away'])?$v['fsw_exp_away']:'';   //客队亚盘初盘赔率
                $val[36] = !empty($v['fsw_ball_home'])?$v['fsw_ball_home']:'';  //主队大小初盘赔率
                if($v['fsw_ball'] == '-0') $v['fsw_ball'] = '0';
                $val[37] = $v['fsw_ball']!== null?$v['fsw_ball']:'';   //大小初盘盘口
                $val[38] = !empty($v['fsw_ball_away'])?$v['fsw_ball_away']:'';   //客队大小初盘赔率
                $val[39] = $v['is_go'];
                $val[40] = $v['is_video'];
                $val[41] = $v['is_flash'];
                $val[42] = $v['is_betting'];
                $val[43] = empty($v['bet_code'])?'':$v['bet_code'];
                $val[44] = getExpWinFb($v['score'],$v['fsw_exp']);
                $val[45] = getBallWinFb($v['score'],$v['fsw_ball']);
                $val[46] = ($score[0] + $score[1])%2 == 1?'1':'2';
                $val[47] = getScoreWinFb($v['half_score']) !== false?getScoreWinFb($v['half_score']):'';
                $val[48] = getScoreWinFb($v['score']) !== false?getScoreWinFb($v['score']):'';
                $val[49] = $this->checkGamble($v);
                $gameinfo[] = $val;

                if(!isset($unionArr[$v['union_id']]))
                {
                    $uVal = [];
                    $uVal[0] = $v['union_id'];
                    $uVal[1] = isset($unionTemp[0])?$unionTemp[0]:'';
                    $uVal[2] = isset($unionTemp[1])?$unionTemp[1]:'';
                    $uVal[3] = isset($unionTemp[2])?$unionTemp[2]:'';
                    $uVal[4] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                    $uVal[5] = !empty($v['is_union'])?$v['is_union']:'';
                    $uVal[6] = $v['is_sub'] !== null?$v['is_sub']:'';
                    $uVal[7] = $v['is_lib'];
                    $uVal[8] = $v['country_id'];
                    $unionArr[$v['union_id']] = $uVal;
                }
            }
            sort($unionArr);
            $rData = ['info'=>$gameinfo,'union' =>$unionArr];
        }
        return $rData;
    }

    /**
     * 近日赛程
     * @param  string   $date  比赛日期
     * @param  string   $unionId  联赛ID
     * @param  string   $subId  联赛级别
     * @return array 近日赛程数组
     * @author huangmg 2016-12-28
     */
    public function fbFixtureList($date,$unionId,$subId ='')
    {
        $sDate = date('Ymd');
        if($date < $sDate) return array();

        $GameFbinfo = M('GameFbinfo');
        $map['a.status'] = 1;
        $map['game_state'] = 0;

        if(!empty($subId)) $map['is_sub'] =array('in',$subId);
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);

        if(!empty($date))
        {
            $startTime =strtotime($date.' 10:32:00');
            $endTime = $startTime + 3600*24;
        }
        else
        {
           if(strtotime('10:32:00') < time())
            {
                $startTime = strtotime('10:32:00');
                $endTime = strtotime('10:32:00')+3600*24;
            }
            else
            {
                $startTime =strtotime('10:32:00')-3600*24;
                $endTime = strtotime('10:32:00');
            }
        }
        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));

        $baseRes = $GameFbinfo->alias('a')->field('a.id,game_id,a.union_id,a.union_name as union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,home_team_id,away_team_id,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,is_video,is_flash,is_betting,bet_code,is_go,is_gamble,is_show,u.union_name as u_name,u.is_union,u.is_lib,country_id,is_sub')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,bet_code,is_sub,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $unionArr = $gameinfo = [];
            foreach($baseRes as $k=> $v)
            {
                if(stripos($v['home_team_name'],'测试') !== false || strpos($v['away_team_name'],'测试') !== false || strpos($v['home_team_name'],'test') !== false || strpos($v['away_team_name'],'test') !== false)
                {

                    unset($baseRes[$k]);
                    continue;
                }
                if($v['is_sub'] === null || $v['is_sub'] >3)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['game_state'] == -14 || $v['game_state'] == -11) continue;          //屏蔽待定和推迟
                //if($v['gtime'] + 120 < time() && $v['game_state'] == 0) continue;          //过了开场时间未开始
                //if($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) continue;      //140分钟还没结束

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']) && $v['union_name'] !=',,')
                    $unionTemp = explode(',',$v['union_name']);
                else
                    $unionTemp = explode(',',$v['u_name']);
                $val[2] = isset($unionTemp[0])?$unionTemp[0]:'';
                $val[3] = isset($unionTemp[1])?$unionTemp[1]:'';
                $val[4] = isset($unionTemp[2])?$unionTemp[2]:'';
                $val[5] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[6] = $v['is_sub'] !== null?$v['is_sub']:'';
                $val[7] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[8] = $gameTime[0];
                $val[9] = $gameTime[1];
                $val[10] = $v['game_half_time'];
                $val[11] = !empty($v['home_team_id'])?$v['home_team_id']:'';
                $val[12] = !empty($v['away_team_id'])?$v['away_team_id']:'';
                $htName = explode(',',$v['home_team_name']);
                $atName = explode(',',$v['away_team_name']);
                $val[13] = isset($htName[0])?$htName[0]:'';
                $val[14] = isset($htName[1])?$htName[1]:'';
                $val[15] = isset($htName[2])?$htName[2]:'';
                $val[16] = isset($atName[0])?$atName[0]:'';
                $val[17] = isset($atName[1])?$atName[1]:'';
                $val[18] = isset($atName[2])?$atName[2]:'';
                $home_rank = $v['home_team_rank'];
                $away_rank = $v['away_team_rank'];
                $val[19] = $home_rank !== false?$home_rank:'';
                $val[20] = $away_rank !== false?$away_rank:'';

                $val[21] = !empty($v['fsw_exp_home'])?$v['fsw_exp_home']:'';  //主队亚盘初盘赔率
                if($v['fsw_exp'] == '-0') $v['fsw_exp'] = '0';
                $val[22] = $v['fsw_exp'] !== null?$v['fsw_exp']:'';   //亚盘初盘盘口
                $val[23] = !empty($v['fsw_exp_away'])?$v['fsw_exp_away']:'';   //客队亚盘初盘赔率
                $val[24] = !empty($v['fsw_ball_home'])?$v['fsw_ball_home']:'';  //主队大小初盘赔率
                if($v['fsw_ball'] == '-0') $v['fsw_ball'] = '0';
                $val[25] = $v['fsw_ball'] !== null?$v['fsw_ball']:'';   //亚盘初盘盘口
                $val[26] = !empty($v['fsw_ball_away'])?$v['fsw_ball_away']:'';   //客队大小初盘赔率
                $val[27] = $v['is_go'];
                $val[28] = $v['is_video'];
                $val[29] = $v['is_flash'];
                $val[30] = $v['is_betting'];
                $val[31] = empty($v['bet_code'])?'':$v['bet_code'];
                $val[32] = $this->checkGamble($v);
                $gameinfo[] = $val;

                if(!isset($unionArr[$v['union_id']]))
                {
                    $uVal = [];
                    $uVal[0] = $v['union_id'];
                    $uVal[1] = isset($unionTemp[0])?$unionTemp[0]:'';
                    $uVal[2] = isset($unionTemp[1])?$unionTemp[1]:'';
                    $uVal[3] = isset($unionTemp[2])?$unionTemp[2]:'';
                    $uVal[4] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                    $uVal[5] = !empty($v['is_union'])?$v['is_union']:'';
                    $uVal[6] = $v['is_sub'] !== null?$v['is_sub']:'';
                    $uVal[7] = $v['is_lib'];
                    $uVal[8] = $v['country_id'];
                    $unionArr[$v['union_id']] = $uVal;
                }
            }
            sort($unionArr);
            $rData = ['info'=>$gameinfo,'union' =>$unionArr];
        }
        return $rData;
    }

    /**
     * app即时指数界面
     * @param  int $unionId 赛事ID，多个以‘,’隔开
     * @param  int $subId   级别ID，多个以‘,’隔开
     * @return array 即时赛事数组
     * @author huangmg 2016-12-28
     */
    public function fbInstant($unionId,$subId='')
    {
        $GameFbinfo = M('GameFbinfo');

        $startTime = time();
        $endTime = $startTime+3600*24;
        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));

        $map['game_state'] = 0;
        $map['a.status'] = 1;
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);
        if(!empty($subId))
            $map['is_sub'] = array('in',$subId);
        //else
            //$map['is_sub'] = array('in','0,1,2');

        $baseRes = $GameFbinfo->alias('a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,is_video,is_gamble,is_show,u.union_name as u_name,u.is_union,u.is_lib,country_id')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
            }

            //$AppfbService = new \Home\Services\AppfbService();
            $oddsArr = $this->fbOddsIns($gids);

            $unionArr = $gameinfo = [];
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
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']) && $v['union_name'] !=',,')
                    $unionTemp = explode(',',$v['union_name']);
                else
                    $unionTemp = explode(',',$v['u_name']);
                $val[2] = isset($unionTemp[0])?$unionTemp[0]:'';
                $val[3] = isset($unionTemp[1])?$unionTemp[1]:'';
                $val[4] = isset($unionTemp[2])?$unionTemp[2]:'';
                $val[5] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[6] = $v['is_sub'] !== null?$v['is_sub']:'';
                $val[7] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[8] = $gameTime[0];
                $val[9] = $gameTime[1];
                $val[10] = $v['game_half_time'];
                $val[11] = !empty($v['home_team_id'])?$v['home_team_id']:'';
                $val[12] = !empty($v['away_team_id'])?$v['away_team_id']:'';
                $htName = explode(',',$v['home_team_name']);
                $atName = explode(',',$v['away_team_name']);
                $val[13] = isset($htName[0])?$htName[0]:'';
                $val[14] = isset($htName[1])?$htName[1]:'';
                $val[15] = isset($htName[2])?$htName[2]:'';
                $val[16] = isset($atName[0])?$atName[0]:'';
                $val[17] = isset($atName[1])?$atName[1]:'';
                $val[18] = isset($atName[2])?$atName[2]:'';
                $home_rank = $v['home_team_rank'];
                $away_rank = $v['away_team_rank'];
                $val[19] = $home_rank !== false?$home_rank:'';
                $val[20] = $away_rank !== false?$away_rank:'';

                #亚赔
                $asianTemp =[];
                if(isset($oddsArr['asia'][$v['game_id']])) $asianTemp = $oddsArr['asia'][$v['game_id']];
                $val[21] = $asianTemp;

                #欧赔
                $europeTemp =[];
                if(isset($oddsArr['euro'][$v['game_id']])) $europeTemp = $oddsArr['euro'][$v['game_id']];
                $val[22] = $europeTemp;

                #大小
                $ballTemp =[];
                if(isset($oddsArr['ball'][$v['game_id']])) $ballTemp = $oddsArr['ball'][$v['game_id']];
                $val[23] = $ballTemp;
                $val[24] = $this->checkGamble($v);
                $gameinfo[] = $val;

                if(!isset($unionArr[$v['union_id']]))
                {
                    $uVal = [];
                    $uVal[0] = $v['union_id'];
                    $uVal[1] = isset($unionTemp[0])?$unionTemp[0]:'';
                    $uVal[2] = isset($unionTemp[1])?$unionTemp[1]:'';
                    $uVal[3] = isset($unionTemp[2])?$unionTemp[2]:'';
                    $uVal[4] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                    $uVal[5] = !empty($v['is_union'])?$v['is_union']:'';
                    $uVal[6] = $v['is_sub'] !== null?$v['is_sub']:'';
                    $uVal[7] = $v['is_lib'];
                    $uVal[8] = $v['country_id'];
                    $unionArr[$v['union_id']] = $uVal;
                }
            }
            sort($unionArr);
            $rData = ['info'=>$gameinfo,'union' =>$unionArr];
        }
        return $rData;
    }

    /**
     * 当日赛事变化数据解析（数据库数据）
     * @return array 赛事变化数据
     * @author huangmg 2016-12-28
     */
    public function getChange()
    {
        $rData = [];
        if(!S('cache_fb_change_flag2')) usleep(1000);
        if(S('cache_fb_change3'))
        {
            $rData = S('cache_fb_change3');
            unset($rData['cache']);
        }
        else
        {
            S('cache_fb_change_flag2',false);
            $res = M()->query('select game_id,game_id_new,change_str,update_time from qc_change_fb where update_time = (select update_time as utime from qc_change_fb order by update_time desc limit 1) order by id');

            if(!empty($res))
            {
                if($res[0]['update_time'] +20 > time())
                {
                    foreach($res as $k=>$v)
                    {
                        $arr = explode('^',$v['change_str']);
                        $aTemp[0] = $arr[0];     //赛事ID
                        $aTemp[1] = $arr[1];  //赛事状态
                        $aTemp[2] = $arr[2] == null?'':$arr[2];  //主队得分
                        $aTemp[3] = $arr[3] == null?'':$arr[3];  //客队得分
                        $aTemp[4] = $arr[4] == null?'':$arr[4];  //半场主队得分
                        $aTemp[5] = $arr[5] == null?'':$arr[5];  //半场客队得分
                        $aTemp[6] = $arr[6] == null?'':$arr[6];  //主队红牌
                        $aTemp[7] = $arr[7] == null?'':$arr[7];  //客队红牌
                        $aTemp[8] = $arr[12] == null?'':$arr[12];  //主队黄牌
                        $aTemp[9] = $arr[13] == null?'':$arr[13];  //客队黄牌
                        $aTemp[10] = $arr[8];   //比赛时间
                        //$aTime = explode(',',$arr[9]);
                        //$aTime[1] = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                        //$aTime[2] = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                        //$aTemp[11] = implode('',$aTime);   //半场时间
                        $aTemp[11] = $arr[9] == null?'':$arr[9];   //半场时间
                        $aTemp[12] = $arr[16] == null?'':$arr[16];   //主队角球
                        $aTemp[13] = $arr[17] == null?'':$arr[17];  //主队角球
                        $rData[$v['game_id']] = $aTemp;
                    }
                }
            }
            //file_put_contents('testlog.log', 'web getChangeB+:'.date("Y-m-d H:i:s")."\n",FILE_APPEND );
            $rData['cache'] = 'true';
            S('cache_fb_change3',$rData,1);
            unset($rData['cache']);
            S('cache_fb_change_flag2',true);
        }
        return $rData;
    }

    /**
     * 当日赛事变化数据解析（数据库数据）——补充接口，20秒请求一次
     * @return array 赛事变化数据
     * @author huangmg 2016-12-28
     */
    public function getChangeTwo()
    {
        $changeFb = M('changeFb');
        $res1 =  $changeFb->field('update_time')->where($map)->order('update_time desc')->find();

        $map['update_time'] = array(array('gt',$res1['update_time']-30));
        $res2 =  $changeFb->field('game_id,game_id_new,change_str,update_time')->where($map)->order('id')->select();

        $rData = [];
        if(!empty($res2))
        {
            foreach($res2 as $k=>$v)
            {
                $arr = explode('^',$v['change_str']);
                $aTemp[0] = $arr[0];     //赛事ID
                $aTemp[1] = $arr[1];  //赛事状态
                $aTemp[2] = $arr[2] == null?'':$arr[2];  //主队得分
                $aTemp[3] = $arr[3] == null?'':$arr[3];  //客队得分
                $aTemp[4] = $arr[4] == null?'':$arr[4];  //半场主队得分
                $aTemp[5] = $arr[5] == null?'':$arr[5];  //半场客队得分
                $aTemp[6] = $arr[6] == null?'':$arr[6];  //主队红牌
                $aTemp[7] = $arr[7] == null?'':$arr[7];  //客队红牌
                $aTemp[8] = $arr[12] == null?'':$arr[12];  //主队黄牌
                $aTemp[9] = $arr[13] == null?'':$arr[13];  //客队黄牌
                $aTemp[10] = $arr[8];   //比赛时间
                //$aTime = explode(',',$arr[9]);
                //$aTime[1] = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                //$aTime[2] = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                //$aTemp[11] = implode('',$aTime);   //半场时间
                $aTemp[11] = $arr[9] == null?'':$arr[9];   //半场时间
                $aTemp[12] = $arr[16] == null?'':$arr[16];   //主队角球
                $aTemp[13] = $arr[17] == null?'':$arr[17];  //主队角球
                $rData[$v['game_id']] = $aTemp;
            }

        }
        return $rData;
    }

    /**
     * 获取全场指数变化数据(数据库)
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     * @author huangmg 2016-12-27
     */
    public function getGoal($companyID)
    {
        if(empty($companyID)) return false;

        $sql = 'select update_time as utime from qc_fb_goal where company_id='.$companyID.' ORDER BY update_time desc limit 1';
        $res = M()->query($sql);
        $rData = [];

        #全场赔率变化
        if (!empty($res) && $res[0]['utime'] >time()-20)
        {
            $sql = 'select * from qc_fb_goal where update_time ='.$res[0]['utime'].' and company_id='.$companyID;
            $res = M()->query($sql);

            foreach($res as $k=>$v)
            {
                $temp = [];
                $odds1 = explode('^',$v['exp_value']);
                $aOdds = explode(',',$odds1[0]);
                if(!empty($aOdds[6]) || !empty($aOdds[7]) || !empty($aOdds[8]))
                {
                    $temp[0] = $aOdds[6];
                    $temp[1] = $aOdds[7];
                    $temp[2] = $aOdds[8];
                }
                else if(!empty($aOdds[3]) || !empty($aOdds[4]) || !empty($aOdds[5]))
                {
                    $temp[0] = $aOdds[3];
                    $temp[1] = $aOdds[4];
                    $temp[2] = $aOdds[5];
                }
                else
                {
                    $temp[0] = '';
                    $temp[1] = '';
                    $temp[2] = '';
                }

                $oOdds = explode(',',$odds1[1]);
                if(!empty($oOdds[6]) || !empty($oOdds[7]) || !empty($oOdds[8]))
                {
                    $temp[3] = $oOdds[6];
                    $temp[4] = $oOdds[7];
                    $temp[5] = $oOdds[8];
                }
                else if(!empty($oOdds[3]) || !empty($oOdds[4]) || !empty($oOdds[5]))
                {
                    $temp[3] = $oOdds[3];
                    $temp[4] = $oOdds[4];
                    $temp[5] = $oOdds[5];
                }
                else
                {
                    $temp[3] = '';
                    $temp[4] = '';
                    $temp[5] = '';
                }

                $bOdds = explode(',',$odds1[2]);
                if(!empty($bOdds[6]) || !empty($bOdds[7]) || !empty($bOdds[8]))
                {
                    $temp[6] = $bOdds[6];
                    $temp[7] = $bOdds[7];
                    $temp[8] = $bOdds[8];
                }
                else if(!empty($bOdds[3]) || !empty($bOdds[4]) || !empty($bOdds[5]))
                {
                    $temp[6] = $bOdds[3];
                    $temp[7] = $bOdds[4];
                    $temp[8] = $bOdds[5];
                }
                else
                {
                    $temp[6] = '';
                    $temp[7] = '';
                    $temp[8] = '';
                }
                /*$temp[9] = '';
                $temp[10] = '';
                $temp[11] = '';
                $temp[12] = '';
                $temp[13] = '';
                $temp[14] = '';
                $temp[15] = '';
                $temp[16] = '';
                $temp[17] = '';*/

                $rData[$v['game_id']] = $temp;
            }
        }

        return $rData;
    }

    /**
     * 获取全场指数变化数据(数据库)
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     * @author huangmg 2016-12-27
     */
    public function getPswGoal($companyID)
    {
        if(empty($companyID)) return false;

        $time1 = strtotime('10:32:00');
        $time2 = strtotime('8:00:00');
        if($time1 < time())
        {
            $startTime = $time2;
            $endTime = $time1+3600*24;
        }
        else
        {
            $startTime = $time2-3600*24;
            $endTime = $time1;
        }
        $GameFbinfo = M('GameFbinfo');

        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
        $map['status'] = 1;
        $map['game_state'] = array('neq',-1);;

        $baseRes = $GameFbinfo->field('id,game_id')->where($map)->order('game_state desc,gtime,bet_code,id')->select();

        $rData = $gTemp = [];
        if(!empty($baseRes))
        {
            foreach($baseRes as $k=> $v)
            {
                $gTemp[] = $v['game_id'];
            }
        }
        if(empty($gTemp)) return [];
        $pswGids = implode(',',$gTemp);

        $map2['game_id'] = array('in',$pswGids);
        $map2['company_id'] = $companyID;

        $obj = M('FbOdds');
        $res = $obj->field('game_id,exp_value')->where($map2)->select();
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $oTemp = oddsChArr($v['exp_value']);
                $arr = [0=>'',1=>'',2=>'',3=>'',4=>'',5=>'',6=>'',7=>'',8=>''];
                #半场让球
                if($oTemp[3][6] != '' || $oTemp[3][7] != '' || $oTemp[3][8] != '')
                {
                    $arr[0] = $oTemp[3][6];
                    $arr[1] = $oTemp[3][7];
                    $arr[2] = $oTemp[3][8];
                }
                else if($oTemp[3][3] != '' || $oTemp[3][4] != '' || $oTemp[3][5] != '')
                {
                    $arr[0] = $oTemp[3][3];
                    $arr[1] = $oTemp[3][4];
                    $arr[2] = $oTemp[3][5];
                }
                #半场欧赔
                if(($oTemp[4][6] != '' || $oTemp[4][7] != '' || $oTemp[4][8] != '') && ($oTemp[4][6] != $halfOddsCache[$kk][4][6] || $oTemp[4][7] != $halfOddsCache[$kk][4][7] || $oTemp[4][8] != $halfOddsCache[$kk][4][8]))
                {
                    $arr[3] = $oTemp[4][6];
                    $arr[4] = $oTemp[4][7];
                    $arr[5] = $oTemp[4][8];
                }
                else if(($oTemp[4][3] != '' || $oTemp[4][4] != '' || $oTemp[4][5] != '') && ($oTemp[4][3] != $halfOddsCache[$kk][4][3] || $oTemp[4][4] != $halfOddsCache[$kk][4][4] || $oTemp[4][5] != $halfOddsCache[$kk][4][5]))
                {
                    $arr[3] = $oTemp[4][3];
                    $arr[4] = $oTemp[4][4];
                    $arr[5] = $oTemp[4][5];
                }
                #半场大小
                if(($oTemp[5][6] != '' || $oTemp[5][7] != '' || $oTemp[5][8] != '') && ($oTemp[5][6] != $halfOddsCache[$kk][5][6] || $oTemp[5][7] != $halfOddsCache[$kk][5][7] || $oTemp[5][8] != $halfOddsCache[$kk][5][8]))
                {
                    $arr[6] = $oTemp[5][6];
                    $arr[7] = $oTemp[5][7];
                    $arr[8] = $oTemp[5][8];
                }
                else if(($oTemp[5][3] != '' || $oTemp[5][4] != '' || $oTemp[5][5] != '') && ($oTemp[5][3] != $halfOddsCache[$kk][5][3] || $oTemp[5][4] != $halfOddsCache[$kk][5][4] || $oTemp[5][5] != $halfOddsCache[$kk][5][5]))
                {
                    $arr[6] = $oTemp[5][3];
                    $arr[7] = $oTemp[5][4];
                    $arr[8] = $oTemp[5][5];
                }

                if($arr[0] == '' && $arr[1] == '' && $arr[2] == '' && $arr[3] == '' && $arr[4] == '' && $arr[5] == '' && $arr[6] == '' && $arr[7] == '' && $arr[8] == '') continue;

                if(S('services_pswgoalpub_'.$v['game_id']))
                {
                    $oldArr = S('services_pswgoalpub_'.$v['game_id']);

                    if(json_encode($oldArr) == json_encode($arr))
                        continue;
                    else
                        S('services_pswgoalpub_'.$v['game_id'],json_encode($arr),600);
                }
                else
                {
                    S('services_pswgoalpub_'.$v['game_id'],json_encode($arr),600);
                }
                $rData[$v['game_id']] = $arr;
            }
        }
        return $rData;
    }

    /**
     * 获取当日赛事赔率弹出框赔率数据 (mongo数据)
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     * @author dengwi 2018-6-29
     */
    public function getOddsData($companyID = 3,$gameId = 0)
    {
        //使用mongo赔率数据
        $mongodb  = mongoService();
        if($gameId > 0){
            $map['game_id'] = $gameId;
        }else{
            $dataService = new \Common\Services\DataService();
            $gids = $dataService->getGameTodayGids(1);
            if(empty($gids)) return [];
            $map['game_id'] = [$mongodb->cmd('in')=>$gids];
        }
        $map['company_id'] = (int)$companyID;

        // $map2['game_id'] = array('in',$gids);
        // $map2['company_id'] = $companyID;
        // $obj = M('FbOdds');
        // $res = $obj->field('game_id,exp_value')->where($map2)->select();
        // dump($res);
        // die;

        $fb_odds = $mongodb->select('fb_odds',$map,['game_id','odds','is_half']);
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
                $rData[$v['game_id']] = $oTemp;
            }
        }

        if($gameId > 0){
            //各公司赔率（使用mongo数据）
            $where['game_id'] = $gameId;
            $where['match_odds_m_asia'] = ['$ne'=>NULL];
            $res = $mongodb->select('fb_game',$where,['game_id','match_odds_m_asia']);
            $compArr = [];
            $sortArr = array_keys(C('DB_COMPANY_INFO'));
            unset($sortArr[1]);
            foreach ($res as $k => $v) {
                $match_odds_m_asia = $v['match_odds_m_asia'];
                $tempArr = [];
                foreach ($match_odds_m_asia as $kk => $vv) {
                    if(in_array($kk, $sortArr)){
                        $temp = [];
                        $temp[0]   = $vv[3] ? $vv[3] : $vv[0];
                        $temp[1]   = $vv[4] ? $vv[4] : $vv[1];
                        $temp[2]   = $vv[5] ? $vv[5] : $vv[2];
                        $tempArr[$kk] = $temp;
                    }
                }
                if(!empty($tempArr)){
                    $rData[$v['game_id']][6] = $tempArr;
                } 
            }
        }
        return $rData;
        
        // $map3['game_id'] = array('in',$gids);
        // $obj2 = M('FbGoal');
        // $res = $obj2->field('game_id,company_id,exp_value')->where($map3)->select();
        // $compArr = [];
        // foreach($res as $k=>$v)
        // {
        //     $oTemp = oddsChArr($v['exp_value']);
        //     $temp = [];
        //     if($oTemp[0][0] =='' && $oTemp[0][1] =='' && $oTemp[0][2] =='' && $oTemp[0][3] =='' && $oTemp[0][4] =='' && $oTemp[0][5] ==''&& $oTemp[0][6] =='' && $oTemp[0][7] =='' && $oTemp[0][8] =='') continue;
        //     if($oTemp[0][6] !='' || $oTemp[0][7] !='' || $oTemp[0][8] !='')
        //     {
        //         $temp[] = round($oTemp[0][6],2);
        //         $temp[] = changeExp($oTemp[0][7]);
        //         $temp[] = round($oTemp[0][8],2);
        //     }
        //     else if($oTemp[0][3] !='' || $oTemp[0][4] !='' || $oTemp[0][5] !='')
        //     {
        //         $temp[] = round($oTemp[0][3],2);
        //         $temp[] = changeExp($oTemp[0][4]);
        //         $temp[] = round($oTemp[0][5],2);
        //     }
        //     else
        //     {
        //         $temp[] = round($oTemp[0][0],2);
        //         $temp[] = changeExp($oTemp[0][1]);
        //         $temp[] = round($oTemp[0][2],2);
        //     }

        //     $compArr[$v['game_id']][$v['company_id']] = $temp;
        // }

        // $sortArr = [1,12,8,4,23,17,24,31];
        // foreach($rData as $k=>$v)
        // {
        //     if(isset($compArr[$k]))
        //     {
        //         $temp = $oldTemp = [];
        //         $oldTemp = $compArr[$k];
        //         if(isset($oldTemp[$sortArr[0]])) $temp[$sortArr[0]] = $oldTemp[$sortArr[0]];
        //         if(isset($oldTemp[$sortArr[1]])) $temp[$sortArr[1]] = $oldTemp[$sortArr[1]];
        //         if(isset($oldTemp[$sortArr[2]])) $temp[$sortArr[2]] = $oldTemp[$sortArr[2]];
        //         if(isset($oldTemp[$sortArr[3]])) $temp[$sortArr[3]] = $oldTemp[$sortArr[3]];
        //         if(isset($oldTemp[$sortArr[4]])) $temp[$sortArr[4]] = $oldTemp[$sortArr[4]];
        //         if(isset($oldTemp[$sortArr[5]])) $temp[$sortArr[5]] = $oldTemp[$sortArr[5]];
        //         if(isset($oldTemp[$sortArr[6]])) $temp[$sortArr[6]] = $oldTemp[$sortArr[6]];
        //         if(isset($oldTemp[$sortArr[7]])) $temp[$sortArr[7]] = $oldTemp[$sortArr[7]];
        //         $rData[$k][6] = $temp;
        //     }
        // }
    }


    /**
     * 即时赔率数据(多公司,指数比较界面数据源)
     * @param  array   $gameIds  赛事ID
     * @return array 全场即时赔率数据
     * @author huangmg 2017-01-04
     */
    public function getChodds($gameId = '')
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
     * 赛事事件变化数据(数据库)
     * @return array
     * @author huangmg 2016-12-29
     */
    public function getDetailWeb($gameId)
    {
        if($rData = S("web_fb_getDetailWeb".$gameId)){
            return $rData;
        }

        $gids = '';
        if(!empty($gameId)) $gids = $gameId;

        if(empty($gids))
        {
            $dataService = new \Common\Services\DataService();
            $gids = $dataService->getGameTodayGids(1);
        }

        if(empty($gids)) return [];

        $mongodb  = mongoService();
        if(is_array($gids)){
            $detailWhere = ['game_id'=>[$mongodb->cmd('in')=>$gids],'detail'=>['$exists'=>1]];
        }else{
            $detailWhere = ['game_id'=>$gids];
        }
        //$res = M()->query('select * from qc_detail_fb where game_id in('.$gids.') order by gtime');
        //使用mongo数据
        $res = $mongodb->select('fb_game',$detailWhere,['game_id','detail']);
        $t = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $game_id = $v['game_id'];
                $detail  = $v['detail'];
                foreach ($detail as $kk => $vv) {
                    $temp = [
                        0 => $game_id,
                        1 => $vv[0],
                        2 => $vv[1],
                        3 => $vv[2],
                        4 => $vv[3],
                        5 => $vv[4],
                        6 => $vv[5],
                    ];
                    $t[$v['game_id']][] = $temp;
                }
                // $temp = [
                //     0 => $v['game_id'],
                //     1 => $v['is_home'],
                //     2 => $v['detail_type'],
                //     3 => $v['gtime'],
                //     4 => $v['s_player'] == null? '':$v['s_player'] ,
                //     5 => $v['player_id'] == null? '':$v['player_id'] ,
                //     6 => $v['c_player'] == null? '':$v['c_player']
                // ];
                
            }
        }
        // dump($t);
        // die;
        if(is_array($gids)){
            $gids = implode(',', $gids);
        }
        $res = M()->query('select * from qc_statistics_fb where game_id in('.$gids.') order by s_id');
        $s = [];
        if(!empty($res))
        {
            $passArr = array(14,3,4,8,19,6,9,5,11,13);
            foreach($res as $k=>$v)
            {
                if(!in_array($v['s_type'],$passArr)) continue;
                $temp = [
                    0 => $v['game_id'],
                    1 => $v['s_type'],
                    2 => $v['home_value'],
                    3 => $v['away_value'],
                ];
                $s[$v['game_id']][] = $temp;
            }
        }
        $rData = [];
        if(!empty($t) || !empty($s))
        {
            if(!empty($t)) $rData['t'] = $t;
            if(!empty($s)) $rData['s'] = $s;
        }
        S("web_fb_getDetailWeb".$gameId,$rData,30);
        return $rData;
    }

    /**
     * 赛事事件变化数据(mongo)
     * @return array
     * @author dengwj 2018-6-26
     */
    public function getPanluWeb($num = '10',$game_id)
    {
        if($fb_panlu_cache = S('fb_panlu_cache'.$game_id))
        {
            return $fb_panlu_cache;
        }

        $mongodb = mongoService();
        $baseRes = $mongodb->select('fb_game',['game_id'=>$game_id],['game_id','gtime','union_id','home_team_id','home_team_name','away_team_id','away_team_name','union_color','game_analysis_web_qt.past_match_data']);

        if(empty($baseRes)) return [];
        $past_match_data = $baseRes[0]['game_analysis_web_qt']['past_match_data'];
        
        foreach ($past_match_data as $k => $v) {
            //过滤其他联盟
            if($v[1] != $baseRes[0]['union_id']){
                unset($past_match_data[$k]);
                continue;
            }
            $gameIdArr[] = $v[15];
        }

        if(empty($past_match_data)) return [];

        //获取赛事数据
        $game = $mongodb->select('fb_game',['game_id'=>['$in'=>$gameIdArr]],['game_id','union_id','union_name','home_team_name','away_team_name','match_odds_m_asia.3','match_odds_m_bigsmall.3']);

        foreach ($game as $k => $v) {
            $gameArr[$v['game_id']] = $v;
        }

        $rData = [];
        $past_match_data = array_slice($past_match_data, 0,10);
        foreach ($baseRes as $k => $v) {
            $gData = [];
            foreach($past_match_data as $kk => $vv)
            {
                $temp = [];
                $gameInfo = $gameArr[$vv[15]];
                $temp[0] = $vv[15]; //赛事id
                $temp[1] = date('y-m-d',strtotime($vv[0]));  //日期 Y-m-d
                $temp[2] = implode(",", $gameInfo['union_name']);
                $temp[3] = $vv[3];  //联盟颜色
                $temp[4] = implode(",", $gameInfo['home_team_name']);
                $temp[5] = implode(",", $gameInfo['away_team_name']);
                $score   = $vv[8].'-'.$vv[9];
                $temp[6] = $score;
                $temp[7] = $vv[10];
                //亚盘初盘盘口
                $match_odds_m_asia = str_replace(' ', '', $gameInfo['match_odds_m_asia'][3][1]);
                if($match_odds_m_asia == ''){
                    //过滤盘口为空数据
                    continue;
                }
                $temp[8]  = changeExpP($match_odds_m_asia);
                //判断主队基准
                $is_home  = $vv[4] == $baseRes[0]['home_team_id'] ? 1 : 0;
                //盘路
                $temp[9]  = getHandcpWin($score,$temp[8],1,$is_home);
                //胜负
                $temp[10] = getScoreWinFb($score,$is_home);
                //大小
                $match_odds_m_bigsmall = $gameInfo['match_odds_m_bigsmall'][3][1];
                $temp[11] = getBallWinFb($score,$match_odds_m_bigsmall);
                //单双
                $temp[12] = ($vv[8] + $vv[9]) % 2 == 0 ? '2' : '1';
                //主队基准数值
                $temp[13] = $is_home;
                $gData[] = $temp;
            }
            $rData[$v['game_id']] = $gData;
        }
        
        S('fb_panlu_cache'.$game_id,$rData,3600);
        return $rData;
    }

     /**
     * 赛事事件变化数据(数据库)
     * @return array
     * @author huangmg 2016-12-29
     */
    public function getCornerWeb($gameId,$companyID = 3)
    {
        $gids = '';
        if(!empty($gameId)) $gids = $gameId;

        if(empty($gids))
        {
            $dataService = new \Common\Services\DataService();
            $gids = $dataService->getGameTodayGids(1);
        }

        if(empty($gids)) return [];

        $rData = [];
        if(is_array($gids)){
            $gids = implode(',', $gids);
        }
        $res = M()->query('select * from qc_fb_corner where game_id in('.$gids.') and company_id='.$companyID.' order by id');

        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $temp = [];
                $arr1 = explode('^',$v['corner_str']);
                $cArr1 = explode(',',$arr1[2]);
                if(empty($cArr1[0]) && empty($cArr1[1])) continue;
                $temp[0] = $cArr1;
                $cArr2 = explode(';',$arr1[3]);
                $temp[1] = $cArr2;
                $rData[$v['game_id']] = $temp;
            }
        }
        return $rData;
    }
	
	/**
	 * 根据id 获取赔率
	 * @param $gameId string|int 比赛id
	 * @param int $type 1 亚赔 3大小
	 * @return array|bool
	 */
	public function getNewAllOddsAndHistoryOdds($gameId, $type = 1)
    {
	    if(empty($gameId)) return false;
	    $mongodb = mongoService();
	    $allData = $mongodb->select('fb_game',['game_id'=>(int) $gameId],['match_odds_m_asia','match_odds', 'match_odds_m_bigsmall', 'odds_history'])[0];
	    // 赔率公司名称
	    ($type == 1) ? $map = 0 : $map = 1;
	    $oddsCompany = C('AOB_COMPANY_ID');
	    $temp = [];
	    if (!empty($allData)) {
			$tempData = [];
			$tempTime = '';
			foreach ($allData['odds_history'] as $key => $value) {
				// 获取最后即时盘的时间
				foreach ($value[$map] as $k => $v) {
					if ($v[6] === "即") {
						$tempTime = $v[5];
						break 2;
					}
				}
				// 获取最后早盘的时间
				if ($tempTime == '') {
					foreach ($value[$map] as $k => $v) {
						if ($v[6] === "早") {
							$tempTime = $v[5];
							break 2;
						}
					}
				}
			}
			// 如果不为空
			if ($tempTime != '') {
				$tempTime = date("Y").'-'.$tempTime;
			}
			foreach ($allData['odds_history'] as $key => $value) {
				foreach ($value[$map] as $k => $v) {
					$tempData[$key][$k][] = $v[2];
					$tempData[$key][$k][] = changeSnExpTwo($v[3]);
					$tempData[$key][$k][] = $v[4];
					$tempData[$key][$k][] = $v[5];
				}
			}
			
			if ($map == 0) {
				$data = $allData['match_odds_m_asia'];
			} else {
				$data = $allData['match_odds_m_bigsmall'];
			}
			foreach ($data as $key => $value) {
				$temp[$key][] = (string) $oddsCompany[$key];
				$temp[$key][] = (string) $value[0];
				$temp[$key][] = changeSnExpTwo($value[1]);
				$temp[$key][] = (string) $value[2];
				$temp[$key][] = (string) $value[3];
				$temp[$key][] = changeSnExpTwo($value[4]);
				$temp[$key][] = (string) $value[5];
				$temp[$key][] = (string) $key;
				$temp[$key][] = (string) date('YmdHis',strtotime($tempTime));
				$temp[$key]['day'] =  date('m-d', strtotime($tempTime));
				$temp[$key]['hour'] = date("H:i", strtotime($tempTime));
				$temp[$key]['aohis'] = $tempData[$key];
			}
	    }
	    return $temp;
    }
	
	/**
	 * 根据id获取百家欧赔数据
	 * @param $game_id int | string 比赛id
	 * @return array|bool 返回数数据
	 */
    public function getNewEurOdds($game_id)
    {
	    if(empty($game_id)) return false;
	    $mongodb = mongoService();
	    $allData = $mongodb->select('fb_euroodds',['game_id'=>(int) $game_id], ['euroodds'])[0]['euroodds'];
	    $newData = [];
	    $eurComp = C('DB_FB_EUR_COMPANY');
	    foreach ($allData as $key => $value)
	    {
	    	$temp = [
	    		0 => isset($eurComp[$key]) ? $eurComp[$key] : $value['now_odds'][2],
	    	    1 => $value['now_odds'][2],
			    2 => $value['now_odds'][3],
			    3 => $value['now_odds'][4],
			    4 => $value['now_odds'][5],
			    5 => $this->secondChange($value['now_odds'][10], $value['now_odds'][3]),
			    6 => $this->secondChange($value['now_odds'][11], $value['now_odds'][4]),
			    7 => $this->secondChange($value['now_odds'][12], $value['now_odds'][5]),
			    8 => $value['now_odds'][0]
		    ];
	    	
	    	$tempHis = [];
	    	foreach ($value['odds_history'] as $k => $v)
		    {
		    	$tempString = implode('^', $v);
		    	$tempHis[] = $tempString;
		    }
	    	$newData['oo'][$key] = $temp;
	    	$newData['oohis'][$key] = $tempHis;
	    }
	    return $newData;
    }
    
    

    /**
     * 根据公司ID获取赔率各公司初盘指数、即时指数
     * @param  int   $gameId  赛事ID
     * @param  int   $type  1,亚赔；2,欧赔；3,大小
     * @return array  赔率数据
     * @author huangmg 2016-12-30
     */
    public function getAllOdds($gameId ,$type = 1)
    {
        if(empty($gameId)) return false;

        $oddsCompany = C('AOB_COMPANY_ID');
        $fbOddshis = M('fbOddshis');

        $map['game_id'] = (int) $gameId;

        $baseRes = $fbOddshis->field('company_id,ahistory,bhistory,ohistory')->where($map)->select();

        $rData = $oddsA = $oddsB = [];
        $hisOdds = '';
        if(!empty($baseRes))
        {
            switch($type)
            {
                case 1:
                    foreach($baseRes as $k=>$v)
                    {
                        if(empty($v['ahistory'])) continue;
                        $companyID = $v['company_id'];
                        $oddsArr = explode('!',$v['ahistory']);
                        $rData['aohis'][$v['company_id']] = $oddsArr;
                        $endOdds = $oddsArr[0];
                        $endfswOdds = explode('^',$endOdds);
                        $startOdds = array_pop($oddsArr);
                        $startfswOdds = explode('^',$startOdds);

                        $temp = [
                            0 => isset($oddsCompany[$companyID])?(string)$oddsCompany[$companyID]:'',
                            1 => $startfswOdds[0],
                            2 => changeExp($startfswOdds[1]),
                            3 => $startfswOdds[2],
                            4 => $endfswOdds[0],
                            5 => changeExp($endfswOdds[1]),
                            6 => $endfswOdds[2],
                            7 => !empty($companyID)?(string)$companyID:'',
                            8 => $endfswOdds[3],
                        ];
                        $rData['ao'][$v['company_id']] = $temp;
                    }
                    break;
                case 2:
                    $eurComp = C('DB_FB_EUR_COMPANY');
                    $fbEuroodds = M('fbEuroodds');
                    $map['game_id'] = (int) $gameId;

                    $baseRes2 = $fbEuroodds->field('game_id,europe_cname,company_id,from_oddsid,odds_val')->where($map)->select();

                    if(!empty($baseRes2))
                    {
                        foreach( $baseRes2 as $k =>$v)
                        {
                            $oddsArr = explode('!',$hisOdds);
                            $companyID = $v['company_id'];
                            $oddsArr = explode('!',$v['odds_val']);
                            $rData['oohis'][$v['company_id']] = $oddsArr;
                            $endOdds = $oddsArr[0];
                            $endfswOdds = explode('^',$endOdds);
                            $startOdds = array_pop($oddsArr);
                            $startfswOdds = explode('^',$startOdds);

                            $temp = [
                                0 => isset($eurComp[$companyID])?$eurComp[$companyID]:$v['europe_cname'],
                                1 => $v['europe_cname'],
                                2 => $startfswOdds[0],
                                3 => $startfswOdds[1],
                                4 => $startfswOdds[2],
                                5 => $endfswOdds[0],
                                6 => $endfswOdds[1],
                                7 => $endfswOdds[2],
                                8 => !empty($companyID)?(string)$companyID:'',
                            ];
                            $rData['oo'][$v['company_id']] = $temp;
                        }
                    }
                    break;
                case 3:
                    foreach($baseRes as $k=>$v)
                    {
                        if(empty($v['bhistory'])) continue;
                        $companyID = $v['company_id'];
                        $oddsArr = explode('!',$v['bhistory']);
                        $rData['bohis'][$v['company_id']] = $oddsArr;
                        $endOdds = $oddsArr[0];
                        $endfswOdds = explode('^',$endOdds);
                        $startOdds = array_pop($oddsArr);
                        $startfswOdds = explode('^',$startOdds);
                        //var_dump($startfswOdds,$endfswOdds);exit;
                        $temp = [
                            0 => isset($oddsCompany[$companyID])?(string)$oddsCompany[$companyID]:'',
                            1 => $startfswOdds[0],
                            2 => changeExp($startfswOdds[1]),
                            3 => $startfswOdds[2],
                            4 => $endfswOdds[0],
                            5 => changeExp($endfswOdds[1]),
                            6 => $endfswOdds[2],
                            7 => !empty($companyID)?(string)$companyID:'',
                            8 => $endfswOdds[3],
                        ];
                        $rData['bo'][$v['company_id']] = $temp;
                    }
                    break;
            }
        }
        return $rData;
    }

    /**
     * 赛事球队阵容(app)
     * @param  int $gameId 赛事ID
     * @return array
     * @author huangmg 2016-12-30
     */
    public function getLineup($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $gameFbInfo = M('GameFbinfo');
        $tidArr = $gameFbInfo->field('home_team_id,away_team_id')->where('game_id='.$gameId)->find();
        if(empty($tidArr)) return null;

        $gameLineup = M('GameLineupFb');
        $where['game_id'] = $gameId;
        $res = $gameLineup->field('qc_playerfb.*,qc_game_lineup_fb.*')->join('qc_playerfb ON qc_game_lineup_fb.player_id = qc_playerfb.player_id','LEFT')->where($where)->order('qc_game_lineup_fb.id,qc_game_lineup_fb.is_first')->select();
        $rData = [];
        $homeArr = [];
        $awayArr = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $temp = [];
                if($v['is_sys'] == 1)
                {
                    $temp = [
                        0 => $v['player_id'],          //球员ID
                        1 => $v['player_name'],        //球员名字
                        2 => $v['player_number'] !=null?$v['player_number']:'',      //球员号码
                        3 => $v['is_first'],           //是否首发
                        4 => $v['player_type'],        //球员位置
                    ];
                }
                else
                {
                    $temp = [
                        0 => '',          //球员ID
                        1 => $v['pname'],        //球员名字
                        2 => $v['pno'],      //球员号码
                        3 => $v['is_first'],           //是否首发
                        4 => $v['player_type'],        //球员位置
                    ];
                }
                if($tidArr['home_team_id'] == $v['team_id'])
                {
                    $homeArr[] = $temp;
                }
                else
                {
                    $awayArr[] = $temp;
                }
            }
        }
        if(!empty($homeArr) || !empty($homeArr))
            $rData = array('home'=>$homeArr,'away'=>$awayArr);
        return $rData;
    }

     /**
     * 根据公司ID获取数据分析界面数据
     * @param  int   $gameId  赛事ID
     * @param  int   $lang  语言ID(1是简体-文件带cn的,2是繁体-文件不带cn的)
     * @return array  数据
     * @author huangmg 2017-01-03
     */
    public function getAnaForFile($gameId,$lang = 1)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $rData = [];
        $item = $this->data['analysis'];
        $ext = getFileExt($item['mimeType']);

        $GameFbinfo = M('GameFbinfo');
        $map['game_id'] = $gameId;
        $baseRes = $GameFbinfo->field('*')->where($map)->find();
        if(!empty($baseRes))
            $date = date('Y',$baseRes['gtime']);
        else
            return $rData;

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
                //简体
                $aData = $this->analysisAppCn($content);
            }
            else
            {
                //繁体
                $aData = $this->analysisAppNokey($content);
            }

            if($aData !== false)
            {
                vendor('chinese_conversion.convert');

                #赛事基本信息
                $rData[] = ['name'=>'game_info','content'=>[0=> $baseRes['union_id'],1=>$baseRes['home_team_id'],2=>$baseRes['away_team_id']]];

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
                if(!empty($sdTemp)) $skill_data = ['name'=>'skill_data','content'=>$sdTemp];

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
                            $rgId = [];
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
                        if($v['name'] == 'cupmatch_integral')
                        {
                            foreach ($v['content'] as $k2 => &$v2) {
                                foreach ($v2 as &$v3) {
                                    if ($lang == 1)
                                        $v3 = zhconversion_hans($v3);
                                    else
                                        $v3 = zhconversion_hant($v3);
                                }
                            }
                            $cupmatch_integral = $v;
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
                        if($v['name'] == 'cupmatch_integral') $cupmatch_integral = $v;
                    }
                }

                if(!empty($match_integral)) $rData[] = $match_integral;
                if(!empty($match_fight)) $rData[] = $match_fight;
                if(!empty($recent_fight)) $rData[] = $recent_fight;
                if(!empty($skill_data)) $rData[] = $skill_data;
                if(!empty($match_panlu)) $rData[] = $match_panlu;
                if(!empty($match_three)) $rData[] = $match_three;
                if(!empty($match_Recommend)) $rData[] = $match_Recommend;
                if(!empty($cupmatch_integral)) $rData[] = $cupmatch_integral;
            }
        }
        return $rData;
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
                        3 => $oddsTemp[0][1],
                        4 => $oddsTemp[0][2],
                        5 => $oddsTemp[0][3],
                        6 => $oddsTemp[0][4],
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
                        3 => $oddsTemp[2][1],
                        4 => $oddsTemp[2][2],
                        5 => $oddsTemp[2][3],
                        6 => $oddsTemp[2][4],
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

    /**
     * app赛事分析数据解析
     * @param  string $content 待处理源数据文本
     * @return array           处理后数据
     */
    public function analysisAppNokey($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $content = str_replace(array("\t","\n","\r"),"",$content);
        $content = str_replace("TABLE","table",$content);
        $content = preg_replace('/>\s+</is','><',$content);
        $content = preg_replace('/>\s+<\//is','><\/',$content);

        $score_cn = C('score_cn');

        $aData = [];
        $home_name ='';
        $away_name ='';

        if(preg_match_all('/var hometeam="(.*?)";/i',$content,$ndata)) $home_name =$ndata[1][0];
        if(preg_match_all('/var guestteam="(.*?)";/i',$content,$ndata)) $away_name =$ndata[1][0];

        #对战历史
        $MatchFight = [];
        $MatchFight['name'] ='match_fight';
        $MatchFight['content'] =array();
        if(preg_match_all('/var v_data=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                //var_dump($v2);exit;
                foreach($vs as $k2 => $v2)
                {
                    //var_dump($v2);exit;
                    $temp = [];
                    $temp[0] = $v2[0] !== null?$v2[0]:'';     //比赛状态
                    $temp[1] = $v2[1] !== null?$v2[1]:'';      //联赛ID
                    $temp[2] = $v2[2] !== null?$v2[2]:'';    //联赛名称
                    $temp[3] = $v2[3] !== null?$v2[3]:'';   //联赛颜色
                    $temp[4] = $v2[4] !== null?$v2[4]:'';  //主队球队ID
                    $temp[5] = $v2[5] !== null?$v2[5]:'';   //主队球队名
                    $temp[6] = $v2[6] !== null?$v2[6]:'';    //客队球队ID
                    $temp[7] = $v2[7] !== null?$v2[7]:'';  //客队球队名
                    $temp[8] = $v2[8] !== null?$v2[8]:'';    //主队得分
                    $temp[9] = $v2[9] !== null?$v2[9]:'';    //客队得分
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[10] = $s[0];    //半场主队得分
                        $temp[11] = $s[1];    //半场客队得分
                    }
                    else
                    {
                        $temp[10] = '';
                        $temp[11] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[12] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[12] = $score_cn[$v2[15]];
                        }
                        if(!isset($temp[12])) $temp[12] = '';
                    }
                    else
                    {
                        $temp[12] = $v2[15];
                    }

                    $temp[13] = $v2[17] !== null?$v2[17]:'';    //胜负
                    $temp[14] = $v2[18] !== null?$v2[18]:'';    //盘路
                    $temp[15] = $v2[19] !== null?$v2[19]:'';     //大小
                    $temp[16] = $v2[11] !== null?$v2[11]:'';    //欧盘主胜
                    $temp[17] = $v2[12] !== null?$v2[12]:'';     //欧盘平
                    $temp[18] = $v2[13] !== null?$v2[13]:'';     //欧盘客胜
                    $temp[19] = $v2[14] !== null?$v2[14]:'';    //让球主赔率
                    $temp[20] = $v2[16] !== null?$v2[16]:'';     //让球客赔率
                    $temp[21] = isset($v2[21])?$v2[21]:'';    //主角球
                    $temp[22] = isset($v2[21])?$v2[22]:'';     //客角球
                    $vsTemp[] = $temp;
                }

            }
            $MatchFight['content'] =$vsTemp;
        }

        if(!empty($vsTemp)) $aData[] = $MatchFight;
        unset($MatchFight);
        unset($vsTemp);

        #近期战史
        $RecentFight = [];
        $RecentFight['name'] ='recent_fight';
        $RecentFight['content'] =array();

        $RecentFight1 = [];
        $RecentFight1['name'] ='recent_fight1';
        $RecentFight1['content'] =array();
        if(preg_match_all('/var h_data=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                foreach($vs as $k2 => $v2)
                {
                    if($k2>9) break;  //只显示10场
                    $temp = [];
                    $temp[0] = $home_name;    //球队名-表头
                    $temp[1] = $v[0] !==null?$v2[0]:'';        //比赛状态
                    $temp[2] = $v[1] !==null?$v2[1]:'';         //联赛ID
                    $temp[3] = $v[2] !==null?$v2[2]:'';       //联赛名称
                    $temp[4] = $v[3] !==null?$v2[3]:'';      //联赛颜色
                    $temp[5] = $v[4] !==null?$v2[4]:'';      //主队球队ID
                    $temp[6] = $v[5] !==null?$v2[5]:'';      //主队球队名
                    $temp[7] = $v[6] !==null?$v2[6]:'';      //客队球队ID
                    $temp[8] = $v[7] !==null?$v2[7]:'';      //客队球队名
                    $temp[9] = $v[8] !==null?$v2[8]:'';       //主队得分
                    $temp[10] = $v[9] !==null?$v2[9]:'';       //客队得分
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];    //半场主队得分
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    //$temp[13] = $v2[15];         //初盘赔率
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = $score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17] !==null?$v2[17]:'';             //胜
                    $temp[15] = $v2[18] !==null?$v2[18]:'';           //盘路
                    $temp[16] = $v2[19] !==null?$v2[19]:'';            //大小
                    $temp[17] = $v2[11] !== null?$v2[11]:'';    //欧盘主胜
                    $temp[18] = $v2[12] !== null?$v2[12]:'';     //欧盘平
                    $temp[19] = $v2[13] !== null?$v2[13]:'';     //欧盘客胜
                    $temp[20] = $v2[14] !== null?$v2[14]:'';    //让球主赔率
                    $temp[21] = $v2[16] !== null?$v2[16]:'';     //让球客赔率
                    $temp[22] = isset($v2[21])?$v2[21]:'';      //主角球
                    $temp[23] = isset($v2[22])?$v2[22]:'';      //客角球
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight1['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight1;
        unset($RecentFight1);

        $RecentFight2 = [];
        $RecentFight2['name'] ='recent_fight2';
        $RecentFight2['content'] =array();
        if(preg_match_all('/var a_data=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }

                foreach($vs as $k2 => $v2)
                {
                    if($k2>9) break;    //只显示10场
                    $temp = [];
                    $temp[0] = $away_name;
                    $temp[1] = $v2[0]!==null?$v2[0]:'';
                    $temp[2] = $v2[1]!==null?$v2[1]:'';
                    $temp[3] = $v2[2]!==null?$v2[2]:'';
                    $temp[4] = $v2[3]!==null?$v2[3]:'';
                    $temp[5] = $v2[4]!==null?$v2[4]:'';
                    $temp[6] = $v2[5]!==null?$v2[5]:'';
                    $temp[7] = $v2[6]!==null?$v2[6]:'';
                    $temp[8] = $v2[7]!==null?$v2[7]:'';
                    $temp[9] = $v2[8]!==null?$v2[8]:'';
                    $temp[10] = $v2[9]!==null?$v2[9]:'';
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = $score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17]!==null?$v2[17]:'';
                    $temp[15] = $v2[18]!==null?$v2[18]:'';
                    $temp[16] = $v2[19]!==null?$v2[19]:'';
                    $temp[17] = $v2[11] !== null?$v2[11]:'';    //欧盘主胜
                    $temp[18] = $v2[12] !== null?$v2[12]:'';     //欧盘平
                    $temp[19] = $v2[13] !== null?$v2[13]:'';     //欧盘客胜
                    $temp[20] = $v2[14] !== null?$v2[14]:'';    //让球主赔率
                    $temp[21] = $v2[16] !== null?$v2[16]:'';     //让球客赔率
                    $temp[22] = isset($v2[21])?$v2[21]:'';      //主角球
                    $temp[23] = isset($v2[22])?$v2[22]:'';      //客角球
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight2['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight2;
        unset($RecentFight2);

        //主场、客场数据不显示了
        /*$RecentFight3 = [];
        $RecentFight3['name'] ='recent_fight3';
        $RecentFight3['content'] =array();
        if(preg_match_all('/var h2_data=\[(.*?)var a2_data/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }

                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $home_name;
                    $temp[1] = $v2[0]!==null?$v2[0]:'';
                    $temp[2] = $v2[1]!==null?$v2[1]:'';
                    $temp[3] = $v2[2]!==null?$v2[2]:'';
                    $temp[4] = $v2[3]!==null?$v2[3]:'';
                    $temp[5] = $v2[4]!==null?$v2[4]:'';
                    $temp[6] = $v2[5]!==null?$v2[5]:'';
                    $temp[7] = $v2[6]!==null?$v2[6]:'';
                    $temp[8] = $v2[7]!==null?$v2[7]:'';
                    $temp[9] = $v2[8]!==null?$v2[8]:'';
                    $temp[10] = $v2[9]!==null?$v2[9]:'';
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = '-'.$score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17]!==null?$v2[17]:'';
                    $temp[15] = $v2[18]!==null?$v2[18]:'';
                    $temp[16] = $v2[19]!==null?$v2[19]:'';
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight3['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight3;
        unset($RecentFight3);

        $RecentFight4 = [];
        $RecentFight4['name'] ='recent_fight4';
        $RecentFight4['content'] =array();
        if(preg_match_all('/var a2_data=\[(.*?)var ScoreAll/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }

                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $home_name;
                    $temp[1] = $v2[0]!==null?$v2[0]:'';
                    $temp[2] = $v2[1]!==null?$v2[1]:'';
                    $temp[3] = $v2[2]!==null?$v2[2]:'';
                    $temp[4] = $v2[3]!==null?$v2[3]:'';
                    $temp[5] = $v2[4]!==null?$v2[4]:'';
                    $temp[6] = $v2[5]!==null?$v2[5]:'';
                    $temp[7] = $v2[6]!==null?$v2[6]:'';
                    $temp[8] = $v2[7]!==null?$v2[7]:'';
                    $temp[9] = $v2[8]!==null?$v2[8]:'';
                    $temp[10] = $v2[9]!==null?$v2[9]:'';
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = '-'.$score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17]!==null?$v2[17]:'';
                    $temp[15] = $v2[18]!==null?$v2[18]:'';
                    $temp[16] = $v2[19]!==null?$v2[19]:'';
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight4['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight4;
        unset($RecentFight4);*/
        if(!empty($RecentFight['content'])) $aData[] = $RecentFight;

        #聯賽積分
        $MatchIntegral = [];
        $MatchIntegral['name'] ='match_integral';
        $MatchIntegral['content'] =array();
        if(preg_match_all('/>聯賽積分<\/h3>(.*?)<\/table><\/div><div/is',$content,$MIdata))
        {
            $doc = \phpQuery::newDocumentHTML($MIdata[0][0]);
            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                //if(empty($str)) continue;
                if($k2 == 0 && ord($v2[1]) === 194) break;
                if($k2 == 0 && empty($v2[1])) continue;
                $temp = [];
                $temp[0] = $name1;          //球队名称
                $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                $sTemp[] = $temp;
            }
            //$aData['match_integral'][] = $aTemp;
            $aTemp = [];
            foreach(pq('table:eq(2)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            $name2 = '';
            $name2 = $aTemp[0][0];
            if($name1 != $name2)
            {
                array_shift($aTemp);
                array_shift($aTemp);
                //$sTemp = [];
                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    //if(empty($str)) continue;
                    if($k2 == 0 && ord($v2[1]) === 194) break;
                    if($k2 == 0 && empty($v2[1])) continue;
                    $temp = [];
                    $temp[0] = $name2;    //球队名称
                    $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                    $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                    $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                    $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                    $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                    $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                    $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                    $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                    $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                    $sTemp[] = $temp;
                }
            }

            #半场
            $aTemp = [];
            foreach(pq('table:eq(3)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }

            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                //if(empty($str)) continue;
                if($k2 == 0 && ord($v2[1]) === 194) break;
                if($k2 == 0 && empty($v2[1])) continue;

                $temp = [];
                $temp[0] = $name1;          //球队名称
                $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                $sTemp[] = $temp;
            }
            //$aData['match_integral'][] = $aTemp;
            $aTemp = [];
            foreach(pq('table:eq(4)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }

            $name2 = '';
            $name2 = $aTemp[0][0];

            if($name2 == '半场')
            {
                array_shift($aTemp);
                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    //if(empty($str)) continue;
                    if($k2 == 0 && ord($v2[1]) === 194) break;
                    if($k2 == 0 && empty($v2[1])) continue;
                    $temp = [];
                    $temp[0] = $name2;    //球队名称
                    $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                    $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                    $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                    $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                    $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                    $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                    $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                    $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                    $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                    $sTemp[] = $temp;
                }
            }
            $MatchIntegral['content'] =$sTemp;
        }
        if(!empty($sTemp)) $aData[] = $MatchIntegral;
        unset($MatchIntegral);

        #聯賽盤路走勢
        $MatchPanlu = [];
        $MatchPanlu['name'] ='match_panlu';
        $MatchPanlu['content'] =array();
        if(preg_match_all('/>聯賽盤路走勢<\/h3>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940" align="center" border="0"/is',$content,$Mdata))
        {
            $doc = \phpQuery::newDocumentHTML($Mdata[0][0]);

            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_panlu'][] = $aTemp;
            $name1 = '';
            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                if(empty($str)) continue;
                if(ord($str) == 231) continue;
                $temp = [];
                $temp[0] = $name1;      //球队名称
                $temp[1] = $v2[0]!==null?$v2[0]:'';     //全场
                $temp[2] = $v2[1]!==null?$v2[1]:'';     //共
                $temp[3] = $v2[2]!==null?$v2[2]:'';     //s胜
                $temp[4] = $v2[3]!==null?$v2[3]:'';     //平
                $temp[5] = $v2[4]!==null?$v2[4]:'';     //负
                $temp[6] = $v2[5]!==null?$v2[5]:'';     //胜率
                $temp[7] = $v2[7]!==null?$v2[7]:'';     //大球
                $temp[8] = $v2[8]!==null?$v2[8]:'';     //大球率
                $temp[9] = $v2[9]!==null?$v2[9]:'';     //小球
                $temp[10] = $v2[10]!==null?$v2[10]:'';   //小球率
                $sTemp[] = $temp;
            }

            $aTemp = [];
            foreach(pq('table:eq(2)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_panlu'][] = $aTemp;
            $name2 = '';
            $name2 = $aTemp[0][0];
            if($name1 != $name2)
            {
                array_shift($aTemp);
                array_shift($aTemp);
                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    if(empty($str)) continue;
                     if(ord($str) == 231) continue;
                    $temp = [];
                    $temp[0] = $name2;      //球队名称
                    $temp[1] = $v2[0]!==null?$v2[0]:'';     //全场
                    $temp[2] = $v2[1]!==null?$v2[1]:'';     //共
                    $temp[3] = $v2[2]!==null?$v2[2]:'';     //s胜
                    $temp[4] = $v2[3]!==null?$v2[3]:'';     //平
                    $temp[5] = $v2[4]!==null?$v2[4]:'';     //负
                    $temp[6] = $v2[5]!==null?$v2[5]:'';     //胜率
                    $temp[7] = $v2[7]!==null?$v2[7]:'';     //大球
                    $temp[8] = $v2[8]!==null?$v2[8]:'';     //大球率
                    $temp[9] = $v2[9]!==null?$v2[9]:'';     //小球
                    $temp[10] = $v2[10]!==null?$v2[10]:'';   //小球率
                    $sTemp[] = $temp;
                }
            }
            $MatchPanlu['content'] = $sTemp;
        }
        if(!empty($sTemp)) $aData[] = $MatchPanlu;
        unset($MatchPanlu);

        #近三場賽程
        $MatchThree = [];
        $MatchThree['name'] ='match_three';
        $MatchThree['content'] =array();
        if(preg_match_all('/>近三場賽程<\/h3><\/td>(.*?)<\/table><div class=/is',$content,$Threedata))
        {
            $doc = \phpQuery::newDocumentHTML($Threedata[0][0]);

            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_three'][] = $aTemp;
            $name1 = '';
            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                if(empty($str)) continue;
                $temp = [];
                $temp[0] = $name1;   //球队名称
                $temp[1] = $v2[0]!==null?$v2[0]:'';  //联赛名称
                $temp[2] = $v2[1]!==null?$v2[1]:'';  //比赛日期
                $temp[3] = $v2[2]!==null?$v2[2]:'';  //主客队
                $temp[4] = $v2[3]!==null?$v2[3]:'';  //对手
                $temp[5] = $v2[4]!==null?$v2[4]:'';  //积分
                $sTemp[] = $temp;
            }

            $aTemp = [];
            foreach(pq('table:eq(2)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            $name2 = '';
            $name2 = $aTemp[0][0];
            if($name1 != $name2)
            {
                array_shift($aTemp);
                array_shift($aTemp);

                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    if(empty($str)) continue;
                    $temp = [];
                    $temp[0] = $name2;   //球队名称
                    $temp[1] = $v2[0]!==null?$v2[0]:'';  //联赛名称
                    $temp[2] = $v2[1]!==null?$v2[1]:'';  //比赛日期
                    $temp[3] = $v2[2]!==null?$v2[2]:'';  //主客队
                    $temp[4] = $v2[3]!==null?$v2[3]:'';  //对手
                    $temp[5] = $v2[4]!==null?$v2[4]:'';  //积分
                    $sTemp[] = $temp;
                }
            }
            $MatchThree['content'] = $sTemp;
        }
        if(!empty($sTemp)) $aData[] = $MatchThree;
        unset($sTemp);
        unset($MatchThree);

        #心水推介
        $MatchRecommend = [];
        $MatchRecommend['name'] ='match_Recommend';
        $MatchRecommend['content'] =array();
        if(preg_match_all('/>心水推介<\/h3><\/td>(.*?)<\/table><div class=/i',$content,$Recommenddata))
        {

            $doc = \phpQuery::newDocumentHTML($Recommenddata[0][0]);

            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_Recommend'][] = $aTemp;
            $viewpoint1 = $aTemp[2][0]!==null?$aTemp[2][0]:'';
            $viewpoint2 = $aTemp[3][0]!==null?$aTemp[3][0]:'';
            array_pop($aTemp);
            array_pop($aTemp);
            $sTemp = [];

            if(!empty($aTemp))
            {
                foreach($aTemp as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $v2[0] !==null?$v2[0]:'';   //球队名称
                    $temp[1] = $v2[1] !==null?$v2[1]:'';     //近期
                    $temp[2] = $v2[2] !==null?$v2[2]:'';      //盘路
                    $sTemp['trend'][] = $temp;    //趋势
                }
                $sTemp['viewpoint'][0] = $viewpoint1;  //预期胜者
                $sTemp['viewpoint'][1] = $viewpoint2; //预期负者
                $MatchRecommend['content'] =$sTemp;
            }
        }
        if(!empty($sTemp)) $aData[] = $MatchRecommend;
        unset($MatchThree);

        #杯赛积分
        $cupMatch = [];
        $cupMatch['name'] ='cupmatch_integral';
        $cupMatch['content'] =array();

        if(preg_match_all('/>杯賽積分<\/h3>(.*?)<\/table>/is',$content,$Mdata))
        {
            $doc = \phpQuery::newDocumentHTML($Mdata[0][0]);
            $sTemp = [];
            foreach(pq('table:eq(0)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $sTemp[] = $temp;
            }
            $cupMatch['content'] = $sTemp;
        }
        if(!empty($sTemp)) $aData[] = $cupMatch;
        unset($cupMatch);

        return $aData;
    }

    /**
     * app赛事分析数据解析
     * @param  string $content 待处理源数据文本
     * @return array           处理后数据
     */
    public function analysisAppCn($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $content = str_replace(array("\t","\n","\r"),"",$content);
        $content = str_replace("TABLE","table",$content);
        $content = preg_replace('/>\s+</is','><',$content);
        $content = preg_replace('/>\s+<\//is','><\/',$content);

        $score_cn = C('score_cn');

        $aData = [];
        $home_name ='';
        $away_name ='';

        if(preg_match_all('/var hometeam="(.*?)";/i',$content,$ndata)) $home_name =$ndata[1][0];
        if(preg_match_all('/var guestteam="(.*?)";/i',$content,$ndata)) $away_name =$ndata[1][0];

        #对战历史
        $MatchFight = [];
        $MatchFight['name'] ='match_fight';
        $MatchFight['content'] =array();
        if(preg_match_all('/var v_data=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                //var_dump($v2);exit;
                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $v2[0] !== null?$v2[0]:'';     //比赛状态
                    $temp[1] = $v2[1] !== null?$v2[1]:'';      //联赛ID
                    $temp[2] = $v2[2] !== null?$v2[2]:'';    //联赛名称
                    $temp[3] = $v2[3] !== null?$v2[3]:'';   //联赛颜色
                    $temp[4] = $v2[4] !== null?$v2[4]:'';  //主队球队ID
                    $temp[5] = $v2[5] !== null?$v2[5]:'';   //主队球队名
                    $temp[6] = $v2[6] !== null?$v2[6]:'';    //客队球队ID
                    $temp[7] = $v2[7] !== null?$v2[7]:'';  //客队球队名
                    $temp[8] = $v2[8] !== null?$v2[8]:'';    //主队得分
                    $temp[9] = $v2[9] !== null?$v2[9]:'';    //客队得分
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[10] = $s[0];    //半场主队得分
                        $temp[11] = $s[1];    //半场客队得分
                    }
                    else
                    {
                        $temp[10] = '';
                        $temp[11] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[12] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[12] = $score_cn[$v2[15]];
                        }
                        if(!isset($temp[12])) $temp[12] = '';
                    }
                    else
                    {
                        $temp[12] = $v2[15];
                    }

                    $temp[13] = $v2[17] !== null?$v2[17]:'';    //胜负
                    $temp[14] = $v2[18] !== null?$v2[18]:'';    //盘路
                    $temp[15] = $v2[19] !== null?$v2[19]:'';     //大小
                    $temp[16] = $v2[11] !== null?$v2[11]:'';    //欧盘主胜
                    $temp[17] = $v2[12] !== null?$v2[12]:'';     //欧盘平
                    $temp[18] = $v2[13] !== null?$v2[13]:'';     //欧盘客胜
                    $temp[19] = $v2[14] !== null?$v2[14]:'';    //让球主赔率
                    $temp[20] = $v2[16] !== null?$v2[16]:'';     //让球客赔率
                    $temp[21] = isset($v2[21])?$v2[21]:'';    //主角球
                    $temp[22] = isset($v2[21])?$v2[22]:'';     //客角球
                    $temp[23] = $v2[20] !== null?$v2[20]:'';     //赛事ID
                    $vsTemp[] = $temp;
                }

            }
            $MatchFight['content'] =$vsTemp;
        }
        // var_dump($MatchFight);exit;
        if(!empty($vsTemp)) $aData[] = $MatchFight;
        unset($MatchFight);
        unset($vsTemp);

        #近期战史
        $RecentFight = [];
        $RecentFight['name'] ='recent_fight';
        $RecentFight['content'] =array();

        $RecentFight1 = [];
        $RecentFight1['name'] ='recent_fight1';
        $RecentFight1['content'] =array();
        if(preg_match_all('/var h_data=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = $vs = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                foreach($vs as $k2 => $v2)
                {
                    //if($k2>9) break;  //只显示10场
                    $temp = [];
                    $temp[0] = $home_name;    //球队名-表头
                    $temp[1] = $v[0] !==null?$v2[0]:'';        //比赛状态
                    $temp[2] = $v[1] !==null?$v2[1]:'';         //联赛ID
                    $temp[3] = $v[2] !==null?$v2[2]:'';       //联赛名称
                    $temp[4] = $v[3] !==null?$v2[3]:'';      //联赛颜色
                    $temp[5] = $v[4] !==null?$v2[4]:'';      //主队球队ID
                    $temp[6] = $v[5] !==null?$v2[5]:'';      //主队球队名
                    $temp[7] = $v[6] !==null?$v2[6]:'';      //客队球队ID
                    $temp[8] = $v[7] !==null?$v2[7]:'';      //客队球队名
                    $temp[9] = $v[8] !==null?$v2[8]:'';       //主队得分
                    $temp[10] = $v[9] !==null?$v2[9]:'';       //客队得分
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];    //半场主队得分
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    //$temp[13] = $v2[15];         //初盘赔率
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = $score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17] !==null?$v2[17]:'';             //胜
                    $temp[15] = $v2[18] !==null?$v2[18]:'';           //盘路
                    $temp[16] = $v2[19] !==null?$v2[19]:'';            //大小
                    $temp[17] = $v2[11] !== null?$v2[11]:'';    //欧盘主胜
                    $temp[18] = $v2[12] !== null?$v2[12]:'';     //欧盘平
                    $temp[19] = $v2[13] !== null?$v2[13]:'';     //欧盘客胜
                    $temp[20] = $v2[14] !== null?$v2[14]:'';    //让球主赔率
                    $temp[21] = $v2[16] !== null?$v2[16]:'';     //让球客赔率
                    $temp[22] = isset($v2[21])?$v2[21]:'';      //主角球
                    $temp[23] = isset($v2[22])?$v2[22]:'';      //客角球
                    $temp[24] = $v2[20] !== null?$v2[20]:'';     //赛事ID
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight1['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight1;
        unset($RecentFight1);

        $RecentFight2 = [];
        $RecentFight2['name'] ='recent_fight2';
        $RecentFight2['content'] =array();
        if(preg_match_all('/var a_data=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = $vs = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }

                foreach($vs as $k2 => $v2)
                {
                    //if($k2>9) break;    //只显示10场
                    $temp = [];
                    $temp[0] = $away_name;
                    $temp[1] = $v2[0]!==null?$v2[0]:'';
                    $temp[2] = $v2[1]!==null?$v2[1]:'';
                    $temp[3] = $v2[2]!==null?$v2[2]:'';
                    $temp[4] = $v2[3]!==null?$v2[3]:'';
                    $temp[5] = $v2[4]!==null?$v2[4]:'';
                    $temp[6] = $v2[5]!==null?$v2[5]:'';
                    $temp[7] = $v2[6]!==null?$v2[6]:'';
                    $temp[8] = $v2[7]!==null?$v2[7]:'';
                    $temp[9] = $v2[8]!==null?$v2[8]:'';
                    $temp[10] = $v2[9]!==null?$v2[9]:'';
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = $score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17]!==null?$v2[17]:'';
                    $temp[15] = $v2[18]!==null?$v2[18]:'';
                    $temp[16] = $v2[19]!==null?$v2[19]:'';
                    $temp[17] = $v2[11] !== null?$v2[11]:'';    //欧盘主胜
                    $temp[18] = $v2[12] !== null?$v2[12]:'';     //欧盘平
                    $temp[19] = $v2[13] !== null?$v2[13]:'';     //欧盘客胜
                    $temp[20] = $v2[14] !== null?$v2[14]:'';    //让球主赔率
                    $temp[21] = $v2[16] !== null?$v2[16]:'';     //让球客赔率
                    $temp[22] = isset($v2[21])?$v2[21]:'';      //主角球
                    $temp[23] = isset($v2[22])?$v2[22]:'';      //客角球
                    $temp[24] = $v2[20] !== null?$v2[20]:'';     //赛事ID
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight2['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight2;
        unset($RecentFight2);

        //主场、客场数据不显示了
        /*$RecentFight3 = [];
        $RecentFight3['name'] ='recent_fight3';
        $RecentFight3['content'] =array();
        if(preg_match_all('/var h2_data=\[(.*?)var a2_data/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }

                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $home_name;
                    $temp[1] = $v2[0]!==null?$v2[0]:'';
                    $temp[2] = $v2[1]!==null?$v2[1]:'';
                    $temp[3] = $v2[2]!==null?$v2[2]:'';
                    $temp[4] = $v2[3]!==null?$v2[3]:'';
                    $temp[5] = $v2[4]!==null?$v2[4]:'';
                    $temp[6] = $v2[5]!==null?$v2[5]:'';
                    $temp[7] = $v2[6]!==null?$v2[6]:'';
                    $temp[8] = $v2[7]!==null?$v2[7]:'';
                    $temp[9] = $v2[8]!==null?$v2[8]:'';
                    $temp[10] = $v2[9]!==null?$v2[9]:'';
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = '-'.$score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17]!==null?$v2[17]:'';
                    $temp[15] = $v2[18]!==null?$v2[18]:'';
                    $temp[16] = $v2[19]!==null?$v2[19]:'';
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight3['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight3;
        unset($RecentFight3);

        $RecentFight4 = [];
        $RecentFight4['name'] ='recent_fight4';
        $RecentFight4['content'] =array();
        if(preg_match_all('/var a2_data=\[(.*?)var ScoreAll/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            $vsTemp = [];
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }

                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $home_name;
                    $temp[1] = $v2[0]!==null?$v2[0]:'';
                    $temp[2] = $v2[1]!==null?$v2[1]:'';
                    $temp[3] = $v2[2]!==null?$v2[2]:'';
                    $temp[4] = $v2[3]!==null?$v2[3]:'';
                    $temp[5] = $v2[4]!==null?$v2[4]:'';
                    $temp[6] = $v2[5]!==null?$v2[5]:'';
                    $temp[7] = $v2[6]!==null?$v2[6]:'';
                    $temp[8] = $v2[7]!==null?$v2[7]:'';
                    $temp[9] = $v2[8]!==null?$v2[8]:'';
                    $temp[10] = $v2[9]!==null?$v2[9]:'';
                    if(!empty($v2[10]))
                    {
                        $s = explode('-',$v2[10]);
                        $temp[11] = $s[0];
                        $temp[12] = $s[1];
                    }
                    else
                    {
                        $temp[11] = '';
                        $temp[12] = '';
                    }
                    if(!empty($v2[15]))
                    {
                        if(preg_match('/受/i',$v2[15],$data))
                        {
                            $str = str_replace('受','',$v2[15]);
                            if(isset($score_cn[$str]) !== false) $temp[13] = '-'.$score_cn[$str];
                        }
                        else
                        {
                            if(isset($score_cn[$v2[15]]) !== false) $temp[13] = '-'.$score_cn[$v2[15]];
                        }
                        if(!isset($temp[13])) $temp[13] = '';
                    }
                    else
                    {
                        $temp[13] = $v2[15];
                    }

                    $temp[14] = $v2[17]!==null?$v2[17]:'';
                    $temp[15] = $v2[18]!==null?$v2[18]:'';
                    $temp[16] = $v2[19]!==null?$v2[19]:'';
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight4['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight4;
        unset($RecentFight4);*/
        if(!empty($RecentFight['content'])) $aData[] = $RecentFight;

        #联赛积分
        $MatchIntegral = [];
        $MatchIntegral['name'] ='match_integral';
        $MatchIntegral['content'] =array();
        if(preg_match_all('/>联赛积分<\/h3>(.*?)<\/table><\/div><div/is',$content,$MIdata))
        {
            $doc = \phpQuery::newDocumentHTML($MIdata[0][0]);
            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }

            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                //if(empty($str)) continue;
                if($k2 == 0 && ord($v2[1]) === 194) break;
                if($k2 == 0 && empty($v2[1])) continue;

                $temp = [];
                $temp[0] = $name1;          //球队名称
                $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                $sTemp[] = $temp;
            }
            //$aData['match_integral'][] = $aTemp;
            $aTemp = [];
            foreach(pq('table:eq(2)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            $name2 = '';
            $name2 = $aTemp[0][0];
            if($name1 != $name2)
            {
                array_shift($aTemp);
                array_shift($aTemp);
                //$sTemp = [];
                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    //if(empty($str)) continue;
                    if($k2 == 0 && ord($v2[1]) === 194) break;
                    if($k2 == 0 && empty($v2[1])) continue;
                    $temp = [];
                    $temp[0] = $name2;    //球队名称
                    $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                    $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                    $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                    $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                    $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                    $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                    $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                    $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                    $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                    $sTemp[] = $temp;
                }
            }

            #半场
            $aTemp = [];
            foreach(pq('table:eq(3)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }

            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                //if(empty($str)) continue;
                if($k2 == 0 && ord($v2[1]) === 194) break;
                if($k2 == 0 && empty($v2[1])) continue;

                $temp = [];
                $temp[0] = $name1;          //球队名称
                $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                $sTemp[] = $temp;
            }
            //$aData['match_integral'][] = $aTemp;
            $aTemp = [];
            foreach(pq('table:eq(4)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }

            $name2 = '';
            $name2 = $aTemp[0][0];

            if($name2 == '半场')
            {
                array_shift($aTemp);
                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    //if(empty($str)) continue;
                    if($k2 == 0 && ord($v2[1]) === 194) break;
                    if($k2 == 0 && empty($v2[1])) continue;
                    $temp = [];
                    $temp[0] = $name2;    //球队名称
                    $temp[1] = ($v2[0]!==null && ord($v2[0]) !== 194)?trim($v2[0]):'';         //全场
                    $temp[2] = ($v2[1]!==null && ord($v2[1]) !== 194)?trim($v2[1]):'';         //全
                    $temp[3] = ($v2[2]!==null && ord($v2[2]) !== 194)?trim($v2[2]):'';         //胜
                    $temp[4] = ($v2[3]!==null && ord($v2[3]) !== 194)?trim($v2[3]):'';         //平
                    $temp[5] = ($v2[4]!==null && ord($v2[4]) !== 194)?trim($v2[4]):'';         //输
                    $temp[6] = ($v2[5]!==null && ord($v2[5]) !== 194)?trim($v2[5]):'';         //得
                    $temp[7] = ($v2[6]!==null && ord($v2[6]) !== 194)?trim($v2[6]):'';         //失
                    $temp[8] = ($v2[8]!==null && ord($v2[8]) !== 194)?trim($v2[8]):'';         //积分
                    $temp[9] = ($v2[9]!==null && ord($v2[9]) !== 194)?trim($v2[9]):'';         //排名
                    $sTemp[] = $temp;
                }
            }
            $MatchIntegral['content'] =$sTemp;
        }
        if(!empty($sTemp)) $aData[] = $MatchIntegral;
        unset($MatchIntegral);

        #联赛盘路走势
        $MatchPanlu = [];
        $MatchPanlu['name'] ='match_panlu';
        $MatchPanlu['content'] =array();
        if(preg_match_all('/>联赛盘路走势<\/h3>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940" align="center" border="0"/is',$content,$Mdata))
        {
            $doc = \phpQuery::newDocumentHTML($Mdata[0][0]);

            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_panlu'][] = $aTemp;
            $name1 = '';
            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                if(empty($str)) continue;
                if(ord($str) == 231) continue;
                $temp = [];
                $temp[0] = $name1;      //球队名称
                $temp[1] = $v2[0]!==null?$v2[0]:'';     //全场
                $temp[2] = $v2[1]!==null?$v2[1]:'';     //共
                $temp[3] = $v2[2]!==null?$v2[2]:'';     //s胜
                $temp[4] = $v2[3]!==null?$v2[3]:'';     //平
                $temp[5] = $v2[4]!==null?$v2[4]:'';     //负
                $temp[6] = $v2[5]!==null?$v2[5]:'';     //胜率
                $temp[7] = $v2[7]!==null?$v2[7]:'';     //大球
                $temp[8] = $v2[8]!==null?$v2[8]:'';     //大球率
                $temp[9] = $v2[9]!==null?$v2[9]:'';     //小球
                $temp[10] = $v2[10]!==null?$v2[10]:'';   //小球率
                $sTemp[] = $temp;
            }

            $aTemp = [];
            foreach(pq('table:eq(2)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_panlu'][] = $aTemp;
            $name2 = '';
            $name2 = $aTemp[0][0];
            if($name1 != $name2)
            {
                array_shift($aTemp);
                array_shift($aTemp);
                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    if(empty($str)) continue;
                    if(ord($str) == 231) continue;

                    $temp = [];
                    $temp[0] = $name2;      //球队名称
                    $temp[1] = $v2[0]!==null?$v2[0]:'';     //全场
                    $temp[2] = $v2[1]!==null?$v2[1]:'';     //共
                    $temp[3] = $v2[2]!==null?$v2[2]:'';     //s胜
                    $temp[4] = $v2[3]!==null?$v2[3]:'';     //平
                    $temp[5] = $v2[4]!==null?$v2[4]:'';     //负
                    $temp[6] = $v2[5]!==null?$v2[5]:'';     //胜率
                    $temp[7] = $v2[7]!==null?$v2[7]:'';     //大球
                    $temp[8] = $v2[8]!==null?$v2[8]:'';     //大球率
                    $temp[9] = $v2[9]!==null?$v2[9]:'';     //小球
                    $temp[10] = $v2[10]!==null?$v2[10]:'';   //小球率
                    $sTemp[] = $temp;
                }
            }
            $MatchPanlu['content'] = $sTemp;
        }
        if(!empty($sTemp)) $aData[] = $MatchPanlu;
        unset($MatchPanlu);

        #近三場賽程
        $MatchThree = [];
        $MatchThree['name'] ='match_three';
        $MatchThree['content'] =array();
        if(preg_match_all('/>近三场赛程<\/h3><\/td>(.*?)<\/table><div class=/is',$content,$Threedata))
        {
            $doc = \phpQuery::newDocumentHTML($Threedata[0][0]);

            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_three'][] = $aTemp;
            $name1 = '';
            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                if(empty($str)) continue;
                $temp = [];
                $temp[0] = $name1;   //球队名称
                $temp[1] = $v2[0]!==null?$v2[0]:'';  //联赛名称
                $temp[2] = $v2[1]!==null?$v2[1]:'';  //比赛日期
                $temp[3] = $v2[2]!==null?$v2[2]:'';  //主客队
                $temp[4] = $v2[3]!==null?$v2[3]:'';  //对手
                $temp[5] = $v2[4]!==null?$v2[4]:'';  //积分
                $sTemp[] = $temp;
            }

            $aTemp = [];
            foreach(pq('table:eq(2)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            $name2 = '';
            $name2 = $aTemp[0][0];
            if($name1 != $name2)
            {
                array_shift($aTemp);
                array_shift($aTemp);

                foreach($aTemp as $k2 => $v2)
                {
                    $str = iconv('gb2312','utf-8//IGNORE', $v2[1]);
                    $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                    if(empty($str)) continue;
                    $temp = [];
                    $temp[0] = $name2;   //球队名称
                    $temp[1] = $v2[0]!==null?$v2[0]:'';  //联赛名称
                    $temp[2] = $v2[1]!==null?$v2[1]:'';  //比赛日期
                    $temp[3] = $v2[2]!==null?$v2[2]:'';  //主客队
                    $temp[4] = $v2[3]!==null?$v2[3]:'';  //对手
                    $temp[5] = $v2[4]!==null?$v2[4]:'';  //积分
                    $sTemp[] = $temp;
                }
            }
            $MatchThree['content'] = $sTemp;
        }
        if(!empty($sTemp)) $aData[] = $MatchThree;
        unset($sTemp);
        unset($MatchThree);

        #心水推介
        $MatchRecommend = [];
        $MatchRecommend['name'] ='match_Recommend';
        $MatchRecommend['content'] =array();
        if(preg_match_all('/>心水推介<\/h3><\/td>(.*?)<\/table><div class=/i',$content,$Recommenddata))
        {
            $doc = \phpQuery::newDocumentHTML($Recommenddata[0][0]);

            $aTemp = [];
            foreach(pq('table:eq(1)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            //$aData['match_Recommend'][] = $aTemp;
            $viewpoint1 = $aTemp[2][0]!==null?$aTemp[2][0]:'';
            $viewpoint2 = $aTemp[3][0]!==null?$aTemp[3][0]:'';
            array_pop($aTemp);
            array_pop($aTemp);
            $sTemp = [];

            if(!empty($aTemp))
            {
                foreach($aTemp as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $v2[0] !==null?$v2[0]:'';   //球队名称
                    $temp[1] = $v2[1] !==null?$v2[1]:'';     //近期
                    $temp[2] = $v2[2] !==null?$v2[2]:'';      //盘路
                    $sTemp['trend'][] = $temp;    //趋势
                }
                $sTemp['viewpoint'][0] = $viewpoint1;  //预期胜者
                $sTemp['viewpoint'][1] = $viewpoint2; //预期负者
                $MatchRecommend['content'] =$sTemp;
            }
        }
        if(!empty($sTemp)) $aData[] = $MatchRecommend;
        unset($MatchThree);

        #杯赛积分
        $cupMatch = [];
        $cupMatch['name'] ='cupmatch_integral';
        $cupMatch['content'] =array();

        if(preg_match_all('/>杯赛积分<\/h3>(.*?)<\/table>/is',$content,$Mdata))
        {
            $doc = \phpQuery::newDocumentHTML($Mdata[0][0]);
            $aTemp = [];
            foreach(pq('table:eq(0)')->find('tr') as $tr)
            {
                $temp = [];
                foreach(pq($tr)->find('td') as $td)
                {
                    $temp[] = pq($td)->text();
                }
                $aTemp[] = $temp;
            }
            $cupMatch['content'] = $aTemp;
        }
        if(!empty($sTemp)) $aData[] = $cupMatch;
        unset($cupMatch);

        return $aData;
    }



    /**
     * 获取接口数据
     * @param  string $flashId 动画ID
     * @param  string $content 待处理源数据文本
     * @return void
     */
    public function dealforAnimate($flashId, $content,$status)
    {
        $flash_id = $flashId;
        $txt = $content;

        $response_length = strlen($txt);
        $game_time = get_game_time($txt);

        $arr = explode(";|", $txt);
        $codeCache = $xyCache = '';

        $fbCart = M('FbCartoonbet');
        $time = time();

        foreach($arr as $kk=>$vv)
        {
            $ms = 0;
            $item = $vv.';';
            $temp = [];

            if(false !== strpos($item, "VC="))
            {
                $temp['status_code'] = cutstr($item, "VC=", ";");
                $len = strlen($temp['status_code']);
                if(5 == $len)
                    $ms = substr($temp['status_code'], 0, 1);
                else if(4 == $len)
                    $ms = -1;
            }
            else
            {
                $temp['status_code'] = '';
            }
            if(false !== strpos($item, "XY="))
            {
                $temp['xy'] = cutstr($item, "XY=", ";");
            }
            else
            {
                $temp['xy'] = '';
            }
            if(false !== strpos($item, "PG="))
            {
                $temp['pg'] = cutstr($item, "PG=", ";");
            }
            if(empty($temp['status_code']) && empty($temp['xy'])) continue;


            if(empty($temp['status_code']) && !empty($temp['xy']))
            {
                $temp['status_code'] = 0;
            }
            if(false !== strpos($item, $v['gameKey'])) continue;

            if($temp['status_code'] != $codeCache || $temp['xy'] != $xyCache)
            {
                $temp['flash_id'] = $flash_id;
                $temp['game_time'] = $game_time;
                $temp['is_home'] = $ms;
                $temp['update_time'] = $time;

                if($temp['status_code'] == 1026)
                {
                    $TA = cutstr($item, "TA=", ";");
                    $temp['other'] = "TA=".$TA;
                }

                if($temp['status_code'] == 1015)
                {
                    $map1['flash_id'] = $flash_id;
                    $res = $fbCart->field(['id','status_code'])->where($map1)->order('id desc')->limit(1)->select();

                    if($res[0]['status_code'] != 1015)
                    {
                        $res = $fbCart->add($temp);
                    }
                }
                else
                {
                    $res = $fbCart->add($temp);
                }
            }
            $codeCache = $temp['status_code'];
            $xyCache = $temp['xy'];

        }
        $end_flag = is_game_end($flash_id, $txt);
        if($end_flag)
        {
            if(empty($gId))
            {
                $res = $fbCart->where("flash_id = '".$flash_id."'")->save(['status'=>'end']);
            }
        }
        $map2['flash_id'] = $flash_id;
        if($status == 'end')
            $res2 = M('FbLinkbet')->where($map2)->save(['status'=>'end']);
        else
            $res2 = M('FbLinkbet')->where($map2)->save(['status'=>'ing']);
        return $res;
    }

    /**
     * 根据赛事ID获取公司ID获取赔率详情
     * mongo数据库取数据
     * @param  int     $gameId       赛事ID
     * @param  int     $company      公司ID
     * @param  int     $half         0:半场 1:全场
     * @return array  赔率详情
     */
    public function getOddsInfo($gameId,$company,$half = 1,$odds_type,$type = 0,$game=[])
    {
        $mService = mongoService();
        if($half)
        {
//            $mRes = $mService->select('fb_oddshis',["game_id"=>$gameId,'company_id'=>$company,'odds_type'=>$odds_type,'is_half'=>$half])[0]['odds'];
            $half = 'odds_history';
        }else{
            $half = 'odds_history_half';
        }
        if($type == 3)
        {
            $tmp = $mService->select('fb_game',["game_id"=>$gameId])[0][$half][$company];
            foreach ($tmp as $key=>$val){
                $tmp1 = $this->makeOddsInfo($val,$key,$game);
                $res[] = $this->oddsData($tmp1)['data'];
            }
            $mRes['data'] = $res;
        }else{
            $mRes = $mService->select('fb_game',["game_id"=>$gameId])[0][$half][$company][$type];
            $mRes = $this->makeOddsInfo($mRes,$type,$game);
            $mRes = $this->oddsData($mRes);
        }

        return $mRes;
    }

    //處理賠率詳情數據
    public function makeOddsInfo($mRes,$type,$game){
        $_score = C('score');
        foreach($mRes as $key=>$val)
        {
            if(count($val) < 7)
            {
                $tmp[0] = $tmp[1] = '';
                $tmp[2] = $val[0];
                if($type === 0)
                    $tmp[3] = handCpSpread($val[1]);
                else
                    $tmp[3] = $val[1];
                $tmp[4] = $val[2];
                $time = strtotime($val[3]);
                $tmp[5] = date('m-d H:i',$time);
                if($time < $game['game_time'])
                    $tmp[6] = '即';
                else
                    $tmp[6] = '滚';
                $mRes[$key] = $val = $tmp;
            }
            $mRes[$key][] = explode(' ',$val[5])[0];
            $mRes[$key][] = explode(' ',$val[5])[1];
            $mRes[$key][] = $val[6];
            unset($mRes[$key][0],$mRes[$key][5],$mRes[$key][6]);
            $mRes[$key] = array_values($mRes[$key]);
        }
        return $mRes;
    }

    /**
     * 根据赛事ID获取公司ID获取赔率详情
     * mysql数据库取数据
     * @param  int     $gameId       赛事ID
     * @param  int     $company      公司ID
     * @param  int     $half         0:半场 1:全场
     * @return array  赔率详情
     */
    public function getOddsInfoM($gameId,$company,$half = 0,$odds_type)
    {
//        var_Dump($gameId,$company,$half,$odds_type);
        if($half == 0) return null;
        $gtime = M("GameFbinfo")->where(['game_id'=>$gameId])->getField('gtime');
        $odd_type = 1;
        $data = M("FbOddshis")->where(['game_id'=>$gameId,'company_id'=>$company])->find();
        switch($odds_type)
        {
            case '亚':
                $odd_type = 1;
                $tmp = explode('!',$data['ahistory']);
                $tmp2 = json_decode($data['sb_ahistory'],true);
                $config = C('score');
                break;
            case '欧':
                $odd_type = 2;
                $tmp = explode('!',$data['ohistory']);
                $tmp2 = json_decode($data['sb_ohistory'],true);
                break;
            case '大':
                $odd_type = 3;
                $tmp = explode('!',$data['bhistory']);
                $tmp2 = json_decode($data['sb_bhistory'],true);
                $config = C("score_sprit");
                break;

        }
        foreach($tmp as $k=>$v)
        {
            $tmp[$k] = explode('^',$v);
            $kk = $tmp[$k][1];
            if($odd_type == 1)
            {
                $_t = '';
                if(substr($kk,0,1) == '-')
                {
                    $_t = '受';
                    $kk = ltrim($kk,'-');
                }
                $_pan = $_t.$config[$kk];
            }elseif($odd_type == 3){
                $_pan = $config[$kk]?$config[$kk]:$kk;
            }else{
                $_pan = $tmp[$k][1];
            }
            $tmp[$k][1] = $_pan;
            //该条数据日期
            $mon = substr($tmp[$k][3],4,2);//月
            $day = substr($tmp[$k][3],6,2);//日
            $hour = substr($tmp[$k][3],8,2);//时
            $minute = substr($tmp[$k][3],10,2);//分
            $tmp[$k][4] = $mon.'-'.$day;
            $tmp[$k][5] = $hour.':'.$minute;
            //判断是早盘或者未即时
            $tmp[$k][] = $this->forthwith($gtime,$mon,$day,$hour,$minute);
            unset($tmp[$k][3]);
            $_tmp = $tmp[$k];
            array_unshift($_tmp,'-');
            $tmp[$k] = $_tmp;
        }
        $_data = $tmp;
        if($company == 3)
        {
            $gun = array();
            foreach($tmp2 as $k=>$v)
            {
                if($v['Score'] == '即' || $v['Score'] == '早'){
                    unset($tmp2[$k]);
                    continue;
                }
                $mon = substr($v['ModifyTime'],4,2);//月
                $day = substr($v['ModifyTime'],6,2);//日
                $hour = substr($v['ModifyTime'],8,2);//时
                $minute = substr($v['ModifyTime'],10,2);//分
                $gun[$k][0] = $v['HappenTime'];
                $gun[$k][1] = $v['Score'];
                if($v['IsClosed'] == '')
                {
                    $kk = $v['PanKou'];
                    if($odd_type == 1)
                    {
                        $kk = rtrim($kk,'0');
                        $kk = rtrim($kk,'.');
                        if($kk == '0.0') $kk = 0;
                        $_t = '';
                        if(substr($kk,0,1) == '-')
                        {
                            $_t = '受';
                            $kk = ltrim($kk,'-');
                        }
                        $pankou = $_t.$config[$kk];
                    }elseif($odd_type == 3){
                        $pankou = $config[$kk];
                    }else{
                        $kk = rtrim($kk,'0');
                        $kk = rtrim($kk,'.');
                        $pankou = $kk;
                    }
                    $v['HomeOdds'] = rtrim(rtrim($v['HomeOdds'],'0'),'.');
                    $v['AwayOdds'] = rtrim(rtrim($v['AwayOdds'],'0'),'.');
                    $gun[$k][] = $v['HomeOdds'];
                    $gun[$k][] = $pankou;
                    $gun[$k][] = $v['AwayOdds'];
                }else{
                    $gun[$k][] = $v['IsClosed'];
                }
                $gun[$k][] = $mon.'-'.$day;
                $gun[$k][] = $hour.':'.$minute;
                $gun[$k][] = '滚';
            }
            $_data = array_merge((array)$gun,(array)$_data);
        }
        return $this->oddsData($_data);

    }
    /**
     * 根据开赛时间判断是否为早盘或者初盘
     * mysql数据库取数据
     * @param  int     $gtime       开赛时间
     * @param  int     $mon         数据月
     * @param  int     $day         数据日
     * @param  int     $hour        数据小时
     * @param  int     $minute      数据分
     * @return array  赔率详情
     */
    public function forthwith($gtime,$mon,$day,$hour,$minute)
    {
        $_year = (int)date('Y',$gtime);
        $data_time = mktime($hour,$minute,0,$mon,$day,$_year);
        if(($gtime + 60*60*24*30*10) < $data_time)
        {
            $data_time = mktime($hour,$minute,0,$mon,$day,$_year-1);
        }
        $_hour=date("G",$gtime);
        if($_hour < 12)
        {
            $gtime = $gtime - 60*60*24;
        }
        $_gtime = strtotime(date('Y-m-d',$gtime).' 12:00:00');
        if($data_time < $_gtime)
        {
            $tmp = '早';
        }else{
            $tmp = '即';
        }
        return $tmp;
    }
    /*
     *赔率详情数据处理,供mongo的数据与mysql数据使用
     */
    public function oddsData($mRes)
    {
        $tmp_h = array();
        $tmp_a = array();
        $tmp_p = array();
        foreach($mRes as $key => $val)
        {
            if(count($val) == 7){
                array_unshift($mRes[$key],'');
                $tmp_h[] = $val[1];
                $tmp_p[] = $val[2];
                $tmp_a[] = $val[3];
            }elseif(count($val) == 8){
                $tmp_h[] = $val[2];
                $tmp_p[] = $val[3];
                $tmp_a[] = $val[4];
            }elseif(count($val) == 5){
                array_unshift($mRes[$key],'');
            }
        }
        $pos_h = array_search(max($tmp_h), $tmp_h);
        $pos_p = array_search(max($tmp_p), $tmp_p);
        $pos_a = array_search(max($tmp_a), $tmp_a);
        $data['home_max'] = $tmp_h[$pos_h];
        $data['pin_max'] = $tmp_p[$pos_p];
        $data['away_max'] = $tmp_a[$pos_a];
        $data['data'] = $mRes;
        return $data;
    }

    /**
     * 根据赛事ID获取数据分析页面必发指数数据
     * mongo数据库取数据
     * @param  int     $gameId       赛事ID
     * @return array  赔率详情
     */
    public function getFenxiBifa($gameId)
    {
        $mService = mongoService();
        $mRes = $mService->select('fb_bifaindex310win',["game_id"=>(int)$gameId],['odds','bifadatastandard','bifadatabigsmall']);
        if(empty($mRes)) return [];
        //各公司赔率
        $mRes = $mRes[0];
        $company = C('DB_COMPANY_ODDS');
        $data = $odds_temp = $bigsmall_temp = [];
        foreach($mRes['odds'] as $val)
        {
            if(count(array_unique($val)) < 3) continue;
            $tmp = [];
            if($val[0] == '0') {
                $tmp['company_name'] = '平均欧赔';
                $tmp['company_id'] = '0';
            } else {
                $tmp['company_name']  = $company[$val[0]];
                $tmp['company_id']  = $val[0];
            }
            unset($val[0]);
            $tmp['company_val'] = implode(',',$val);
            $odds_temp[] = $tmp;

        }
        $data['odds'] = $odds_temp;
        $data['standard'] = $mRes['bifadatastandard'];
        $data['bigsmall'] = $mRes['bifadatabigsmall'];
        return $data;
    }


    /**
     * 根据赛事ID获取数据分析页面必发指数数据
     * mongo数据库取数据
     * @param  int     $gameId       赛事ID
     * @param  string  $field        查询数据的字段
     * @param  int     $limit        每页查询条数
     * @param  int     $page         查询页数
     * @return array  赔率详情
     */
    public function getDetTrade($gameId,$field,$limit,$page)
    {
        $mService = mongoService();
        $mRes = $mService->select('fb_bifaindex310win',["game_id"=>(int)$gameId],[$field]);
        if(empty($mRes)) return [];
        $data = $mRes[0][$field];
        $buy = $buy_num = $sell = $sell_num = 0;
        foreach ($data as $k=>$v) {
            if($v[4] == '买')
            {
                $buy++;
            }elseif($v[4] == '卖')
            {
                $sell++;
            }
            $data[$k]['gtime'] = strtotime($v[0]);
            $gtime[] = $data[$k]['gtime'];
            $num[] = $v[2];
        }
        array_multisort($gtime, SORT_DESC,$num, SORT_DESC,$data);
        foreach ($data as $v) {
            if($v[4] == '买')
            {
                $buy_num = $v[2];
            }elseif($v[4] == '卖')
            {
                $sell_num = $v[2];
            }
            if($sell_num != 0 && $buy_num != 0) break;
        }
        $count = count($data);
        $data = array_slice($data,$page*$limit,$limit);
        return ['count'=>$count,'data'=>$data,'buy'=>$buy,'buy_num'=>$buy_num,'sell'=>$sell,'sell_num'=>$sell_num];
    }

    /**
     +------------------------------------------------------------------------------
     * WEB改版
     +------------------------------------------------------------------------------
    */

    /**
     * 根据足球联赛ID获取积分排名
     * mongo数据库取数据
     * @param  int     $unionId      联赛ID（英超-36/西甲-31/中超-60/德甲-8/意甲-34/欧冠-103/亚冠-192/世界杯-75）
     * @param  int     $num          获取数据条数
     * @return array
     */
    public function getFbUnionRank($unionId,$num=10,$group='A')
    {
        if(empty($unionId)) return [];

        if($rData = S('cache_web_fb_unionRank:'.$unionId.'_'.$num.'_'.$group))
        {
            return $rData;
        }
        else
        {
            $mService = mongoService();

            $map = ['union_id'=>(int)$unionId];
            $season = $mService->select('fb_union',$map,['union_id','season']);

            if(empty($season)) return [];

            $year = $season[0]['season'][0];

            $field = ['union_id','statistics.'.$year];
            $gmRes = $mService->select('fb_union',$map,$field);
            $statistics = $gmRes[0]['statistics'][$year];

            //判断积分榜是否为空，为空取上一年
            if(empty($statistics['matchResult']['total_score']) && empty($statistics['matchResult']['Groups'])){
                $year = $season[0]['season'][1];
                $field = ['union_id','statistics.'.$year];
                $gmRes = $mService->select('fb_union',$map,$field);
                $statistics = $gmRes[0]['statistics'][$year];
            }
            
            $rData = [];
            if(!empty($gmRes) && !empty($statistics))
            {
                $httpUrl = C('IMG_SERVER');
                if($statistics['matchResult']['total_score']) {
                    $teamIds = [];
                    foreach ($statistics['matchResult']['total_score'] as $key => $val) {
                        if ($key > ($num - 1)) break;
                        $temp = [
                            'rank' => (string)$val[1],
                            'team_id' => (string)$val[2],
                            'team_name' => '',
                            'team_logo' => '',
                            'win' => (string)$val[5],
                            'draw' => (string)$val[6],
                            'lose' => (string)$val[7],
                            'int' => (string)$val[16],
                        ];
                        $teamIds[] = (int)$val[2];
                        $rData[] = $temp;
                    }

                    $gmRes = $mService->select('fb_team', ['team_id' => [$mService->cmd('in') => $teamIds]], ['team_id', 'team_name', 'img_url']);
                    $teamArr = [];
                    foreach ($gmRes as $key => $val) {
                        $teamArr[$val['team_id']] = $val;
                    }
                    foreach ($rData as $key => $val) {
                        if (isset($teamArr[$val['team_id']])) {
                            $val['team_name'] = isset($teamArr[$val['team_id']]['team_name'][0]) ? $teamArr[$val['team_id']]['team_name'][0] : '';
                            $val['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']]['img_url']);
                        }
                        $rData[$key] = $val;
                    }
                    S('cache_web_fb_unionRank:' . $unionId . '_' . $num, $rData, 3600 * 12);
                }
                else if($statistics['matchResult']['Groups'])
                {
                    //分组数据
                    $teamIds = $rData = [];
                    foreach($statistics['matchResult']['Groups'][$group] as $k => &$v){
                        $teamIds[] = $v[1];
                        $temp = [
                            'rank'      => (string)$v[0],
                            'team_id'   => (string)$v[1],
                            'team_logo' => '',
                            'count'     => (string)$v[2],
                            'win'       => (string)$v[3],
                            'draw'      => (string)$v[4],
                            'lose'      => (string)$v[5],
                            'int'       => (string)$v[9],
                        ];
                        $rData[$k] = $temp;
                    }

                    //查询球队logo
                    $gmRes = $mService->select('fb_team', ['team_id' => [$mService->cmd('in') => $teamIds]], ['team_id', 'team_name' ,'img_url']);
                    $teamArr = [];
                    foreach ($gmRes as $key => $val) {
                        $teamArr[$val['team_id']] = $val;
                    }
                    //球队名称
                    foreach($rData as $k => $v){
                        $rData[$k]['team_logo'] = replaceTeamLogo($teamArr[$v['team_id']]['img_url']);
                        $rData[$k]['team_name'] = $teamArr[$v['team_id']]['team_name'][0];
                    }

                    S('cache_web_fb_unionRank:'.$unionId.'_'.$num.'_'.$group, $rData, 600);
                }
            }
        }
        unset($gmRes);
        return $rData;
    }

    /**
     * 根据足球联赛ID获取射手榜排名
     * mongo数据库取数据
     * @param  int     $unionId      联赛ID（英超-36/西甲-31/中超-60/德甲-8/意甲-34/欧冠-103/亚冠-192/世界杯-75）
     * @param  int     $num          获取数据条数
     * @return array
     */
    public function getFbUnionArcher($unionId,$num=10)
    {
        if(empty($unionId)) return [];

        if(S('cache_web_fb_unionArcher:'.$unionId.'_'.$num))
        {
            $rData = S('cache_web_fb_unionArcher:'.$unionId.'_'.$num);
        }
        else
        {
            $mService = mongoService();
            $map = ['union_id'=>(int)$unionId];

            $season = $mService->select('fb_union',$map,['union_id','season']);

            if(empty($season)) return [];

            $year = $season[0]['season'][0];

            $field = ['union_id','statistics.'.$year.'.player_tech','statistics.'.$year.'.Archer'];
            $gmRes = $mService->select('fb_union',$map,$field);
            //判断射手榜是否为空，为空取上一年
            if(!isset($gmRes[0]['statistics'][$year]['Archer']) && empty($gmRes[0]['statistics'][$year]['player_tech']['Total']['value'])){
                $year = $season[0]['season'][1];
                $field = ['union_id','statistics.'.$year.'.player_tech','statistics.'.$year.'.Archer'];
                $gmRes = $mService->select('fb_union',$map,$field);
            }

            $rData = $aData = [];
            if(!empty($gmRes) && !empty($gmRes[0]['statistics']))
            {
                $httpUrl = C('IMG_SERVER');
                $defaultTeamImg = staticDomain('/Public/Home/images/common/web_player.png');
                $archer = $gmRes[0]['statistics'][$year];

                $pIds = $shoot = $teamIds = [];
                //射手资料，球队名
                if(isset($archer['Archer']) && $archer['Archer']['team_data'] && $archer['Archer']['total_data'])
                {
                    foreach($archer['Archer']['total_data'] as $key=>$val)
                    {
                        if($key > ($num-1)) break;
                        $temp = [
                            'rank'          => (string)$val[0],
                            'player_id'     => (string)$val[1],
                            'player_name'   => !empty($val[2])?$val[2]:'',
                            'player_logo'   => $defaultTeamImg,
                            'team_id'       => $val[8],
                            'val'           => (string)$val[9],//不要点球
//                            'val'           => (string)$val[9].'('.$val[12].')',
                        ];

                        $pIds[] = (int)$val[0];
                        $shoot[$key] = $val[9];
                        $dianqiu[$key] = $val[12];
                        $rData[] = $temp;
                        $teamIds[] = $val[8];
                    }
                }
                else if(isset($archer['player_tech']))//球员技术统计
                {
                    foreach($archer['player_tech']['Total']['value'] as $key=>$val)
                    {
                        $temp = [
                            //'rank'        => (string)$val[1],
                            'player_id'     => (string)$val[0],
                            'player_name'   => $archer['player_tech']['Pid'][$val[0]][0][0],
                            'player_logo'   => $defaultTeamImg,
                            'team_id'       => $archer['player_tech']['Pid'][$val[0]][1],
                            'val'           => (string)$val[40],
//                            'val'           => (string)$val[40].'('.$val[5].')',
                        ];

                        $pIds[] = (int)$val[0];
                        $shoot[$key] = $val[40];
                        $dianqiu[$key] = $val[5];
                        $aData[] = $temp;
                    }

                    array_multisort($shoot,SORT_DESC,$dianqiu,SORT_ASC,$aData);
                    $aData = array_slice($aData, 0, $num);

                    foreach($aData as $key=>$val)
                    {
                        $val['rank'] = (string) ($key+1);
                        $rData[$key] = $val;
                        $teamIds[] = $val['team_id'];
                    }
                }
                //获取球队logo
                $gmRes = $mService->select('fb_team',['team_id'=>[$mService->cmd('in')=>$teamIds]],['team_id','team_name','img_url']);
                $teamArr = [];
                foreach($gmRes as $key=>$val)
                {
                    $teamArr[$val['team_id']] = $val;
                }
                foreach($rData as $key=>$val)
                {
                    $rData[$key]['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']]['img_url']);
                    $rData[$key]['team_name'] = isset($teamArr[$val['team_id']]['team_name'][0]) ? $teamArr[$val['team_id']]['team_name'][0] : '';
                }
                S('cache_web_fb_unionArcher:'.$unionId.'_'.$num,$rData,600);
            }
        }
        unset($gmRes);
        return $rData;
    }



     /**
     * 根据篮球联赛ID获取积分排名等数据
     * mongo数据库取数据
     * @param  int     $unionId      联赛ID（NBA-1，CBA-5）
     * @param  int     $type         类型：1、东部联赛积分；2、西部联赛积分；3，得分榜；4，助攻榜；5、篮板榜 PS：CBA没有东西部分开，联赛积分请求1
     * @param  int     $num          获取数据条数
     * @return array
     */
    public function getBkUnionRank($unionId,$type,$num=10)
    {
        if(empty($unionId)) return [];
        //当前年份
        $year = date('Y',strtotime('-1 year')) .'-'.date('Y');

        $mService = mongoService();
        $map = ['union_id'=>(int)$unionId];
        if($type == 1 || $type == 2)
            $filed = ['union_id','statistics.'.$year.'.union_rank'];
        else
            $filed = ['union_id','statistics.'.$year.'.player_tech'];
        $gmRes = $mService->select('bk_union',$map,$filed);
        //dump($gmRes);

        $rData = [];
        if(!empty($gmRes))
        {
            $httpUrl = C('IMG_SERVER');
            $defaultPlayerImg = staticDomain('/Public/Home/images/common/web_player.png');

            $aData = $teamIds = [];
            switch ($type) {
                case 1:
                    $union_rank = $gmRes[0]['statistics'][$year]['union_rank'];
                    if($union_rank)
                    {
                        if($unionId == 1)
                            $rankData = $union_rank[0];
                        else
                            $rankData = $union_rank;

                        foreach($rankData as $key=>$val)
                        {
                            if($key > ($num-1)) continue;
                            $temp = [
                                'rank' => (string)($key+1),
                                'team_id' => (string)$val[0],
                                'team_name' => '',
                                'win' => (string)$val[1],
                                'lose' => (string)$val[2],
                                'win_ratio' => (string)round($val[3]),
                                'team_logo' => '',
                            ];
                            $teamIds[] = $val[0];
                            $aData[] = $temp;
                        }

                        $mapT['team_id'] = ['in',$teamIds];
                        $res = M('gameTeambk')->field('team_id,team_name,img_url')->where($mapT)->select();

                        $teamArr = [];
                        if(!empty($res))
                        {
                            foreach($res as $k2=>$v2)
                            {
                                $tName = explode(',',$v2['team_name']);
                                $teamArr[$v2['team_id']] = [
                                    0 => $tName[0],
                                    1 => !empty($v2['img_url']) ? $httpUrl.$v2['img_url'] : '',
                                ];
                            }
                        }

                        foreach($aData as $key=>$val)
                        {
                            if(isset($teamArr[$val['team_id']]))
                            {
                                $val['team_name'] = $teamArr[$val['team_id']][0];
                                $val['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']][1]);
                            }
                            $aData[$key] = $val;
                        }
                        $rData = $aData;
                    }
                    break;
                case 2:
                    $union_rank = $gmRes[0]['statistics'][$year]['union_rank'];
                    if($union_rank)
                    {
                        if($unionId == 1)
                            $rankData = $union_rank[1];
                        else
                            $rankData = $union_rank;

                        foreach($rankData as $key=>$val)
                        {
                            if($key > ($num-1)) continue;
                            $temp = [
                                'rank' => (string)($key+1),
                                'team_id' => (string)$val[0],
                                'team_name' => '',
                                'win' => (string)$val[1],
                                'lose' => (string)$val[2],
                                'win_ratio' => (string)round($val[3]),
                                'team_logo' => '',
                            ];
                            $teamIds[] = $val[0];
                            $aData[] = $temp;
                        }

                        $mapT['team_id'] = ['in',$teamIds];
                        $res = M('gameTeambk')->field('team_id,team_name,img_url')->where($mapT)->select();

                        $teamArr = [];
                        if(!empty($res))
                        {
                            foreach($res as $k2=>$v2)
                            {
                                $tName = explode(',',$v2['team_name']);
                                $teamArr[$v2['team_id']] = [
                                    0 => $tName[0],
                                    1 => !empty($v2['img_url']) ? $httpUrl.$v2['img_url'] : '',
                                ];
                            }
                        }

                        foreach($aData as $key=>$val)
                        {
                            if(isset($teamArr[$val['team_id']]))
                            {
                                $val['team_name'] = $teamArr[$val['team_id']][0];
                                $val['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']][1]);
                            }
                            $aData[$key] = $val;
                        }
                        $rData = $aData;
                    }
                    break;
                case 3:
                    $player_tech = $gmRes[0]['statistics'][$year]['player_tech'];
                    if($player_tech)
                    {
                        if(!empty($player_tech[2]))
                            $matchKind = 2;
                        else if(!empty($player_tech[1]))
                            $matchKind = 1;
                        else
                            $matchKind = 3;
                        foreach($player_tech[$matchKind]['score'] as $key=>$val)
                        {
                            if($key > ($num-1)) continue;
                            $temp = [
                                'rank' => (string)($key+1),
                                'player_id' => (string)$val[0],
                                'player_name' => (string)$val[1],
                                'player_logo' => $defaultPlayerImg,
                                'team_id' => (string)$val[4],
                                'team_name' => '',
                                'team_logo' => '',
                                'val' => (string)$val[9],
                            ];
                            $teamIds[] = $val[4];
                            $aData[] = $temp;
                        }
                        $mapT['team_id'] = ['in',$teamIds];
                        $res = M('gameTeambk')->field('team_id,team_name,img_url')->where($mapT)->select();

                        $teamArr = [];
                        if(!empty($res))
                        {
                            foreach($res as $k2=>$v2)
                            {
                                $tName = explode(',',$v2['team_name']);
                                $teamArr[$v2['team_id']] = [
                                    0 => $tName[0],
                                    1 => !empty($v2['img_url']) ? $httpUrl.$v2['img_url'] : '',
                                ];
                            }
                        }

                        foreach($aData as $key=>$val)
                        {
                            if(isset($teamArr[$val['team_id']]))
                            {
                                $val['team_name'] = $teamArr[$val['team_id']][0];
                                $val['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']][1]);
                            }
                            unset($val['team_id']);
                            $aData[$key] = $val;
                        }
                        $rData = $aData;
                    }
                    break;
                case 4:
                    $player_tech = $gmRes[0]['statistics'][$year]['player_tech'];
                    if(isset($player_tech))
                    {
                        if(!empty($player_tech[2]))
                            $matchKind = 2;
                        else if(!empty($player_tech[1]))
                            $matchKind = 1;
                        else
                            $matchKind = 3;
                        foreach($player_tech[$matchKind]['helpAttack'] as $key=>$val)
                        {
                            if($key > ($num-1)) continue;
                            $temp = [
                                'rank' => (string)($key+1),
                                'player_id' => (string)$val[0],
                                'player_name' => (string)$val[1],
                                'player_logo' => $defaultPlayerImg,
                                'team_id' => (string)$val[4],
                                'team_name' => '',
                                'team_logo' => '',
                                'val' => (string)$val[7],
                            ];
                            $teamIds[] = $val[4];
                            $aData[] = $temp;
                        }
                        $mapT['team_id'] = ['in',$teamIds];
                        $res = M('gameTeambk')->field('team_id,team_name,img_url')->where($mapT)->select();

                        $teamArr = [];
                        if(!empty($res))
                        {
                            foreach($res as $k2=>$v2)
                            {
                                $tName = explode(',',$v2['team_name']);
                                $teamArr[$v2['team_id']] = [
                                    0 => $tName[0],
                                    1 => !empty($v2['img_url']) ? $httpUrl.$v2['img_url'] : '',
                                ];
                            }
                        }

                        foreach($aData as $key=>$val)
                        {
                            if(isset($teamArr[$val['team_id']]))
                            {
                                $val['team_name'] = $teamArr[$val['team_id']][0];
                                $val['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']][1]);
                            }
                            unset($val['team_id']);
                            $aData[$key] = $val;
                        }
                        $rData = $aData;
                    }
                    break;
                case 5:
                    $player_tech = $gmRes[0]['statistics'][$year]['player_tech'];
                    if(isset($player_tech))
                    {
                        if(!empty($player_tech[2]))
                            $matchKind = 2;
                        else if(!empty($player_tech[1]))
                            $matchKind = 1;
                        else
                            $matchKind = 3;
                        foreach($player_tech[$matchKind]['board'] as $key=>$val)
                        {
                            if($key > ($num-1)) continue;
                            $temp = [
                                'rank' => (string)($key+1),
                                'player_id' => (string)$val[0],
                                'player_name' => (string)$val[1],
                                'player_logo' => $defaultPlayerImg,
                                'team_id' => (string)$val[4],
                                'team_name' => '',
                                'team_logo' => '',
                                'val' => (string)$val[9],
                            ];
                            $teamIds[] = $val[4];
                            $aData[] = $temp;
                        }
                        $mapT['team_id'] = ['in',$teamIds];
                        $res = M('gameTeambk')->field('team_id,team_name,img_url')->where($mapT)->select();

                        $teamArr = [];
                        if(!empty($res))
                        {
                            foreach($res as $k2=>$v2)
                            {
                                $tName = explode(',',$v2['team_name']);
                                $teamArr[$v2['team_id']] = [
                                    0 => $tName[0],
                                    1 => !empty($v2['img_url']) ? $httpUrl.$v2['img_url'] : '',
                                ];
                            }
                        }

                        foreach($aData as $key=>$val)
                        {
                            if(isset($teamArr[$val['team_id']]))
                            {
                                $val['team_name'] = $teamArr[$val['team_id']][0];
                                $val['team_logo'] = replaceTeamLogo($teamArr[$val['team_id']][1]);
                            }
                            unset($val['team_id']);
                            $aData[$key] = $val;
                        }
                        $rData = $aData;
                    }
                    break;
                default:
                    # code...
                    break;
            }

        }
        return $rData;
    }
	
	
	/**
	 * 根据指定game_id 和 公司id 获取赔率
	 * @param $game_id int 游戏id
	 * @param $compare_id int 公司id
	 * @return array
	 */
    public function getNewAllOdds($game_id, $compare_id)
    {
	    $mongodb = mongoService();
	    $allOdds = $mongodb->select('fb_game',['game_id'=> (int) $game_id],
		    ['match_odds_m_bigsmall', 'match_odds_m_asia', 'match_odds'])[0];
	    $compare_id = (int) $compare_id;
	    // 从数据中解析对应数据
	    for ($i = 0; $i < 6; $i++) {
	    	if ($i === 1 || $i === 4) {
			    $asiaOdds[] = changeSnExpTwo(NullString($allOdds['match_odds_m_asia'][$compare_id][$i]));
			    $bigsmallOdds[] = changeSnExpTwo(NullString($allOdds['match_odds_m_bigsmall'][$compare_id][$i]));
		    } else {
			    $asiaOdds[] = NullString($allOdds['match_odds_m_asia'][$compare_id][$i]);
			    $bigsmallOdds[] = NullString($allOdds['match_odds_m_bigsmall'][$compare_id][$i]);
		    }
		    $europOdds[] = NullString($allOdds['match_odds'][$compare_id][$i+6]);
	    }
	    $data = ['asia' => $asiaOdds, 'europ' => $europOdds, 'bigsmall' => $bigsmallOdds];
	    return $data;
    }
	
	/**
	 * 获取对往战绩
	 * @param $game_id int 比赛id
	 * @param int $lang  所选语言 1 简 2繁 3英
	 * @param bool $is_analysis 是否是比赛详情所需数据
	 * @return array
	 */
    public function getPastMatchData($game_id, $lang = 1, $is_analysis = FALSE)
    {
	    return $this->getMatchData($game_id, 'past_match_data', $lang, $is_analysis);
    }
	
	/**
	 * 获取近期交战
	 * @param $game_id int 比赛id
	 * @param $childrenCollection string 子集合
	 * @param int $lang 所选语言 1 简 2繁 3英
	 * @param bool $is_analysis 是否是比赛详情所需数据
	 * @return array
	 */
    public function getRecentMatchData($game_id, $childrenCollection,$lang = 1, $is_analysis = FALSE)
    {
        return $this->getMatchData($game_id, 'recent_match', $lang, $is_analysis, $childrenCollection);
    }
	
	
	/**
	 * 获取近期交战和对往战绩数据
	 * @param $game_id int 比赛id
	 * @param $collectionName string 集合name
	 * @param int $lang 所选语言 1 简 2繁 3英
	 * @param bool $is_analysis 是否是比赛详情所需数据
	 * @param $childrenCollection
	 * @return array
	 */
    public function getMatchData($game_id, $collectionName, $lang, $is_analysis, $childrenCollection = null)
    {
	    $mongodb = mongoService();
	    $allData = $mongodb->select('fb_game', ['game_id'=> (int) $game_id],
		    ['home_team_id', 'away_team_id' ,'game_analysis_web_qt.'.$collectionName])[0];
	    $data = $allGame= $teamDetail= [];
	    if ($childrenCollection === 'home_team' || $childrenCollection === null) {
		    $team_id = $allData['home_team_id'];
	    } else {
		    $team_id = $allData['away_team_id'];
	    }
	
	    // 获取对往战绩基本数据
	    $pastMatchData =  $allData['game_analysis_web_qt'][$collectionName];
	    if ($collectionName === 'recent_match'){
		    $pastMatchData =  $allData['game_analysis_web_qt'][$collectionName][$childrenCollection];
	    }
	    foreach ($pastMatchData as $key => $value) {
		    $allGame[] = $value[20]?:$value[15];
	    }
	    //获取对往战绩 额外数据
	    $allTeamData = $mongodb->select('fb_game', ['game_id' => [$mongodb->cmd('in') => $allGame]],
		    ['game_id', 'union_name','home_team_name', 'away_team_name', 'corner', 'match_odds_m_asia.3', 'match_odds.3']);
        $lang = (int)$lang -1;
        foreach ($allTeamData as $key => $value) {
		    $teamDetail[$value['game_id']]['union_name'] = $value['union_name'][$lang];
		    $teamDetail[$value['game_id']]['home_team_name'] = $value['home_team_name'][$lang];
		    $teamDetail[$value['game_id']]['away_team_name'] = $value['away_team_name'][$lang];
		    $teamDetail[$value['game_id']]['home_corner'] = explode('-', $value['corner'])[0];
		    $teamDetail[$value['game_id']]['away_corner'] = explode('-', $value['corner'])[1];
		    $teamDetail[$value['game_id']]['asia_odds_h_s'] = $value['match_odds_m_asia'][3][0];
		    $teamDetail[$value['game_id']]['asia_odds_d_s'] = $value['match_odds_m_asia'][3][1];
		    $teamDetail[$value['game_id']]['asia_odds_g_s'] = $value['match_odds_m_asia'][3][2];
		    $teamDetail[$value['game_id']]['europ_odds_h_s'] = $value['match_odds'][3][6];
		    $teamDetail[$value['game_id']]['europ_odds_d_s'] = $value['match_odds'][3][7];
		    $teamDetail[$value['game_id']]['europ_odds_g_s'] = $value['match_odds'][3][8];
	    }
	    //将额外数据添加到基本数据中
	    foreach ($pastMatchData as $key => $value) {
		    $temp =[];
		    if ($collectionName === 'recent_match') {
			    $temp[] = date('Y-m-d', strtotime($value[0]));
		    } else {
			    $temp[] = $value[0];
		    }
            $kk = $value[20]?:$value[15];
		    $temp[] = (string) $kk;
		    $temp[] = $value[2];
		    $temp[] = $value[3];
		    $temp[] = (string) $value[4];
		    $temp[] = strip_tags($value[5]);
		    $temp[] = (string) $value[6];
		    $temp[] = strip_tags($value[7]);
		    $temp[] = (string) $value[8];
		    $temp[] = (string) $value[9];
		    $temp[] = explode('-', $value[10])[0];
		    $temp[] = explode('-', $value[10])[1];
		    $temp[] = changeSnExpTwo($teamDetail[$kk]['asia_odds_d_s']);
		    $temp[] = (string) $value[12];
		    $temp[] = (string) $this->splitGameResult(getHandcpWin((string) $value[8].'-'.(string) $value[9],
			    $teamDetail[$kk]['asia_odds_d_s'], 1, ($value[4] == $team_id) ? 1 : 0));
		    $temp[] = (string) $value[14];
		    if ($is_analysis) {
			    $temp[] = $teamDetail[$kk]['europ_odds_h_s'];
			    $temp[] = $teamDetail[$kk]['europ_odds_d_s'];
			    $temp[] = $teamDetail[$kk]['europ_odds_g_s'];
			    $temp[] = $teamDetail[$kk]['asia_odds_h_s'];
			    $temp[] = $teamDetail[$kk]['asia_odds_g_s'];
			    $temp[] = (string) $value[16];
			    $temp[] = (string) $value[17];
		    }else {
			    $temp[] = (string) $value[1];
		    }
		    if ($collectionName === 'recent_match'){
			    $temp[] = (string) $value[16];
			    $temp[] = (string) $value[17];
		    }
		    $data[] = $temp;
	    }
	    return $data;
    }
	
	/**
	 * 获取web端赛事事件和技术统计
	 * @param $game_id
	 * @return array
	 */
    public function getWebGameDetailTc($game_id)
    {
	    $mongodb = mongoService();
	    $allData = $mongodb->select('fb_game', ['game_id'=> (int) $game_id], ['tc', 'detail'])[0];
	    $data = $detail = $tc = [];
	    foreach ($allData['detail'] as $key => $value) {
		    array_splice($value, 0,0, (string) $game_id);
		    $detail[$game_id][] = $value;
	    }
	    $tcArray = [14, 6, 3, 4, 8, 9, 11];
	    foreach ($tcArray as $key => $value) {
		    $temp = [];
		    $temp[] = (string) $game_id;
		    $temp[] = (string) $value;
		    if ($value == 14) {
			    $temp[] = $this->returnHundred((string) $allData['tc'][$value][0]);
			    $temp[] = $this->returnHundred((string) $allData['tc'][$value][1]);
		    } else {
			    $temp[] = (string) $allData['tc'][$value][0];
			    $temp[] = (string) $allData['tc'][$value][1];
		    }
		    $tc[$game_id][] = $temp;
	    }
	    $data['tc'] = $tc;
	    $data['detail'] = $detail;
		return $data;
    }
	
	
	/**
	 * 获取web端阵容信息
	 * @param $gameId
	 * @return array|bool
	 */
    public function getWebLineUp($gameId)
    {
	    $gameId = (int) $gameId;
	    if(empty($gameId)) return false;
	    $mongodb = mongoService();
	    $baseRes = $mongodb->select('fb_game',['game_id'=> $gameId], ['apk_lineup'])[0]['apk_lineup'];
	    $data = [];
	    $homeData = [];
	    $awayData = [];
	    // mongo 数据问题 如果主队首发第一位的球号不存在
	    if (!empty($baseRes[0][0][0])) {
		    $homeStart = $this->lineup($baseRes[0], TRUE);
		    $homeSub = $this->lineup($baseRes[1],FALSE);
		    $awayStart = $this->lineup($baseRes[2], TRUE);
		    $awaySub = $this->lineup($baseRes[3], FALSE);
		    $homeData = array_merge($homeStart, $homeSub);
		    $awayData = array_merge($awayStart, $awaySub);
		    $data = array('home'=>$homeData,'away'=>$awayData);
	    } else {
		    $data = array('home'=>$homeData,'away'=>$awayData);
	    }
	    return $data;
    }
	
	
	public function lineup($array, $bool) {
		//是否首发
		$isStart = $bool ? '1' : '0';
		$data = [];
		foreach ($array as $key => $value) {
			$temp = [];
			// 球员id 暂时为空
			$temp[] = "";
			// 球员名称
			$temp[] = (string) $value[sizeof($value) -1];
			// 球员秋衣
			$temp[] = (string) $value[0];
			// 是否首发
			$temp[] = $isStart;
			//球员位置 暂时为0
			$temp[] = "0";
			$data[] = $temp;
		}
		return $data;
	}
    
    
    public function nullString($array)
    {
    	foreach ($array as $k => $v) {
    		if (!($v === null || trim($v) === '')) {
    		    return FALSE;
		    }
	    }
	    return TRUE;
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
	
	//返回百分比保险数字
	public function returnHundred($string)
	{
		if (strpos($string, '%')) {
			return $string;
		} else {
			if ($string == null || trim($string) == '') {
				return '0%';
			} else {
				return $string.'%';
			}
		}
	}
	
	// 如果第一个数据为空或不存在
	public function secondChange($source, $change)
	{
		if ($source === null || trim($source) === '') {
			return $change;
		}
		return $source;
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