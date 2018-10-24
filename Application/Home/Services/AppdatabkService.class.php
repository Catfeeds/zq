<?php
/**
  +------------------------------------------------------------------------------
 * AppdataService   App服务类————篮球
  +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqw.cn All rights reserved.
  +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>
  +------------------------------------------------------------------------------
 */
namespace Home\Services;

class AppdatabkService {

    protected $data;

    public function __construct()
    {
        $this->getDataList();
        import('phpQuery');
    }

    /**
     * 篮球当日赛事 (mongo数据，部分业务数据来自mysql)
     * @return array  当日赛事数据
     */
    public function bktodayList($unionId, $subId = '',$platform = '')
    {
        $mongo = mongoService();

        //从缓存获取今日赛事列表
        if(!$baseRes = S('cache_bktodayList_game'))
        {
            $dataService = new \Common\Services\DataService();
            $gameIdArr = $dataService->getGameTodayGids(2);
            $baseRes = $mongo->select('bk_game_schedule',['game_id'=>['$in'=>$gameIdArr]],
                [
                    'game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id',
                    'away_team_id','union_id','game_timestamp','is_go','game_status','quarter_time','union_color','game_info','instant_index'
                ]
            );
            S('cache_bktodayList_game',$baseRes,3);
        }

        $rData = [];
        if (!empty($baseRes))
        {
            $gids = $unionIdArr = $teamIds = [];
            foreach ($baseRes as $k => $v)
            {
                $gids[] = (int)$v['game_id'];
                $unionIdArr[] = (int)$v['union_id'];
                $teamIds[$v['home_team_id']] = $v['home_team_id'];
                $teamIds[$v['away_team_id']] = $v['away_team_id'];
            }

            if($platform == 'robot')
            {
                //机器人需求
                $teamArr = [];
                $tRes = M('GameTeambk')->field('team_id,short_team_name')->where(['team_id'=>['in',implode(',',$teamIds)]])->select();
                if(!empty($tRes))
                {
                    foreach($tRes as $k=> $v)
                    {
                        $teamArr[$v['team_id']] = $v['short_team_name'];
                    }
                }
            }

            if(!$gameArr = S('cache_bktodayList_mysqlGame')){
                //获取mysql业务数据
                $GameBkinfo = M('GameBkinfo')
                    ->field("game_id,gtime,score,half_score,game_state,is_gamble,is_show,status,app_video,is_video")
                    ->where(['game_id'=>['in',$gameIdArr]])
                    ->select();

                foreach ($GameBkinfo as $k => $v) {
                    $gameArr[$v['game_id']] = $v;
                }
                S('cache_bktodayList_mysqlGame',$gameArr,120);
            }

            //获取联盟数据
            if(!$unionArr = S('cache_bktodayList_union')){
                $union = $mongo->select(
                    'bk_union',
                    ['union_id'=>['$in'=>$unionIdArr]],
                    ['union_id','union_name','grade','union_color']
                );
                foreach ($union as $k => $v) {
                    $unionArr[$v['union_id']] = $v;
                }
                S('cache_bktodayList_union', $unionArr, 300);
            }

            //获取篮球动画关联
            $mdArr = [];
            $blRes = M('BkLinkbet')->field('game_id,is_link,flash_id,md_id')->where(implode($gids))->select();
            if(!empty($blRes))
            {
                foreach($blRes as $k=>$v)
                {
                    $blData[$v['game_id']] = $v['is_link'];
                    if(!empty($v['flash_id']) && !empty($v['md_id']))
                    {
                        $mdArr[$v['game_id']] = $v['md_id'];
                    }
                }
            }

            foreach ($baseRes as $k => $v)
            {
                $v['game_state'] = $v['game_status'];
                $v['gtime']      = $v['game_timestamp'];
                //屏蔽待定和推迟 ————-2:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消,-5取消
                if ($v['game_state'] == -14 || $v['game_state'] == -10 || $v['game_state'] == -2 || $v['game_state'] == -12 || $v['game_state'] == -13 || $v['game_state'] == -5) continue;          
                //过了开场时间未开始
                if ($v['gtime'] + 1800 < time() && $v['game_state'] == 0) continue;          
                //60*4分钟还没结束
                if ($v['gtime'] + 3600*4 < time() && array_search($v['game_state'], [1, 2, 3, 4,5,6,7]) !== false) continue;      

                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(isset($mysqlGame['status']) && $mysqlGame['status'] != 1) continue;
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];

                $val = [];
                $val[0] = (string)$v['game_id'];
                $val[1] = (string)$v['union_id'];
                $val[2] = $v['union_name'];
                $val[3] = !empty($v['union_color']) ? $v['union_color'] : '#aeba4b';
                $val[4] = !empty($unionData['grade']) ? $unionData['grade'] : '';
                $val[5] = $v['game_state'];
                $gameTime = explode('-', date('Ymd-H:i', $v['gtime']));
                $val[6] = $gameTime[0];
                $val[7] = $gameTime[1];
                $val[8] = $v['quarter_time'];
                $val[9] = $v['home_team_name'];
                $val[10] = $v['away_team_name'];
                $home_rank = !empty($v['home_team_rank']) ? pregUnionRank($v['home_team_rank']) : '';
                $away_rank = !empty($v['away_team_rank']) ? pregUnionRank($v['away_team_rank']) : '';
                $val[11] = $home_rank !== false ? $home_rank : '';
                $val[12] = $away_rank !== false ? $away_rank : '';
                //比分
                $game_info = $v['game_info'];
                $val[13] = $game_info[3];
                $val[14] = $game_info[4];
                #小节比分
                $val[15] = $game_info[5];
                $val[16] = $game_info[6];
                $val[17] = $game_info[7];
                $val[18] = $game_info[8];
                $val[19] = $game_info[9];
                $val[20] = $game_info[10];
                $val[21] = $game_info[11];
                $val[22] = $game_info[12];
                //加时赛数据(加时赛比分格式（加时为一节：7；两节：7-8；三节：7-8-9）)
                $val[23] = $game_info[13];
                $home_js = $game_info[14];
                if($game_info[16] != '') $home_js .= '-'.$game_info[16];
                if($game_info[18] != '') $home_js .= '-'.$game_info[18];
                $away_js = $game_info[15];
                if($game_info[17] != '') $away_js .= '-'.$game_info[17];
                if($game_info[19] != '') $away_js .= '-'.$game_info[19];
                $val[24] = $home_js;
                $val[25] = $away_js;
                //初盘
                $instant_index = $v['instant_index'];
                $val[26] = $instant_index['letGoal'][3][1] ? : '';   //主队亚盘初盘赔率
                $val[27] = $instant_index['letGoal'][3][0] ? : '';   //亚盘初盘盘口
                $val[28] = $instant_index['letGoal'][3][2] ? : '';   //客队亚盘初盘赔率
                $val[29] = $instant_index['bigSmall'][3][1] ? : '';   //主队大小初盘赔率
                $val[30] = $instant_index['bigSmall'][3][0] ? : '';   //大小初盘盘口
                $val[31] = $instant_index['bigSmall'][3][2] ? : '';   //客队大小初盘赔率
                $ouzhi = explode(',', $game_info[20]);
                $val[32] = $ouzhi[0] ? : '';   //欧赔
                $val[33] = $ouzhi[1] ? : '';   //欧赔
                //即时盘
                $oddsArr = $this->getBkInstantOdds($instant_index,$v['game_state']);
                $val[34] = $oddsArr[1];   //主队亚盘即时赔率
                $val[35] = $oddsArr[0];   //亚盘即时盘口
                $val[36] = $oddsArr[2];   //客队亚盘即时赔率
                $val[37] = $oddsArr[4];   //主队大小即时赔率
                $val[38] = $oddsArr[3];   //大小即时盘口
                $val[39] = $oddsArr[5];   //客队大小即时赔率
                $val[40] = $ouzhi[0] ? : '';   //欧赔
                $val[41] = $ouzhi[1] ? : '';   //欧赔

                #是否有视频直播
                $app_video = $mysqlGame['app_video'];
                if($v['game_state'] !=-1 && !empty($app_video) && $mysqlGame['is_video'] == 1)
                {
                    #video
                    if(!empty(json_decode($app_video)))
                        $val[42] = '1';
                    else
                        $val[42] = '0';
                }
                else
                {
                    $val[42] = '0';
                }

                //是否有动画直播
                if(isset($blData[$v['game_id']]))
                {
                    if(in_array($v['game_state'],[0,1,2,3,4,5,6,7,50]))
                    {
                        if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                            $val[43] = '1';
                        else
                            $val[43] = '0';
                    }
                    else
                    {
                        $val[43] = '0';
                    }
                }
                else
                {
                    $val[43] = '0';
                }

                if(!in_array(MODULE_NAME,['Api','Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400']))
                {
                    $val[44] = (string)$mysqlGame['is_video'];
                    $val[45] = $val[43];
                    if($platform == 'robot')
                    {
                        if(isset($teamArr[$v['home_team_id']]))
                            $val[46] = $teamArr[$v['home_team_id']] !== null?$teamArr[$v['home_team_id']]:'';
                        else
                            $val[46] = '';
                        if(isset($teamArr[$v['away_team_id']]))
                            $val[47] = $teamArr[$v['away_team_id']] !== null?$teamArr[$v['away_team_id']]:'';
                        else
                            $val[47] = '';
                    }
                }
                $sort1[] = $v['game_state'];
                $sort2[] = $v['gtime'];
                $sort3[] = $v['game_id'];
                $rData[] = $val;
            }
            //排序
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$sort3,SORT_ASC,$rData);
        }
        return $rData;
    }

    //获取篮球实时赔率(完场不使用滚球盘)
    public function getBkInstantOdds($instant_index,$game_state=0){
        //篮球推荐赔率公司
        $whole = $instant_index['letGoal'][3];
        $size  = $instant_index['bigSmall'][3];
        $odds = ['','','','','',''];
        if( ($whole[6] !='' || $whole[7] !='' || $whole[8] !='') && $game_state != -1)
        {
            //全场滚球
            $odds[0] = $whole[6];
            $odds[1] = $whole[7];
            $odds[2] = $whole[8];
        }
        elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
        {
            //全场即时
            $odds[0] = $whole[3];
            $odds[1] = $whole[4];
            $odds[2] = $whole[5];
        }
        elseif ($whole[0] !='' || $whole[1] !='' || $whole[2]!='')
        {
            //全场初盘
            $odds[0] = $whole[0];
            $odds[1] = $whole[1];
            $odds[2] = $whole[2];
        }

        if( ($size[6] !='' || $size[7] !='' || $size[8] !='') && $game_state != -1)
        {
            //大小滚球
            $odds[3]= $size[6];
            $odds[4]= $size[7];
            $odds[5]= $size[8];
        }
        elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
        {
            //大小即时
            $odds[3] = $size[3];
            $odds[4] = $size[4];
            $odds[5] = $size[5];
        }
        elseif ($size[0] !='' || $size[1] !='' || $size[2] !='')
        {
            //大小初盘
            $odds[3] = $size[0];
            $odds[4] = $size[1];
            $odds[5] = $size[2];
        }
        return $odds;
    }

    public function bktodayList1($unionId, $subId = '',$platform = '')
    {
        $GameBkinfo = M('GameBkinfo');
        $sTime = strtotime('15:30:00');
        if ($sTime < time()) {
            $startTime = $sTime;
            $endTime = $sTime + 3600 * 24;
        } else {
            $startTime = $sTime - 3600 * 24;
            $endTime = $sTime;
        }
        //$map['gtime'] = array(array('gt', $startTime), array('lt', $endTime));

        $map['a.status'] = 1;
        if (!empty($unionId))
            $map['a.union_id'] = array('in', $unionId);
        if (!empty($subId))
            $map['is_sub'] = array('in', $subId);

        $map['_string'] = '(gtime>'.$startTime.' and gtime <'.$endTime.') or (gtime>'.($startTime-3600*3).' and gtime<'.$startTime.' and game_state in (1,2,3,4,5,6,7))';

        $baseRes = $GameBkinfo->table('qc_game_bkinfo a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_state,home_team_name,away_team_name,home_team_id,away_team_id,score,list_score,is_ot,ot_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_total,fsw_total_home,fsw_total_away,home_team_rank,away_team_rank,union_color,is_sub,grade,is_video,app_video,is_flash,u.union_name as u_name')->join('LEFT JOIN qc_bk_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,is_sub,a.id')->select();

        $rData = [];
        if (!empty($baseRes))
        {
            $gids = $teamIds = [];
            foreach ($baseRes as $k => $v)
            {
                $gids[] = $v['game_id'];
                $teamIds[$v['home_team_id']] = $v['home_team_id'];
                $teamIds[$v['away_team_id']] = $v['away_team_id'];
            }

            $cData = [];
            $cRes = M('BkChange')->field('id,game_id,change_str')->where(implode($gids))->select();
            if(!empty($cRes))
            {
                foreach($cRes as $k=>$v)
                {
                    $temp = explode('^',$v['change_str']);
                    $cData[$v['game_id']] = $temp[2];
                }
            }

            if($platform == 'robot')
            {
                $teamArr = [];
                $tRes = M('GameTeambk')->field('team_id,short_team_name')->where(['team_id'=>['in',implode(',',$teamIds)]])->select();
                if(!empty($tRes))
                {
                    foreach($tRes as $k=> $v)
                    {
                        $teamArr[$v['team_id']] = $v['short_team_name'];
                    }
                }
            }

            $mdArr = [];
            $blRes = M('BkLinkbet')->field('game_id,is_link,flash_id,md_id')->where(implode($gids))->select();
            if(!empty($blRes))
            {
                foreach($blRes as $k=>$v)
                {
                    $blData[$v['game_id']] = $v['is_link'];
                    if(!empty($v['flash_id']) && !empty($v['md_id']))
                    {
                        $mdArr[$v['game_id']] = $v['md_id'];
                    }
                }
            }

            $oddsArr = $this->bkOdds($gids,3);

            foreach ($baseRes as $k => $v)
            {
                /*if ($v['is_sub'] === null || $v['is_sub'] > 3) {
                    unset($baseRes[$k]);
                    continue;
                }*/
                if ($v['game_state'] == -14 || $v['game_state'] == -10 || $v['game_state'] == -2 || $v['game_state'] == -12 || $v['game_state'] == -13 || $v['game_state'] == -5) continue;          //屏蔽待定和推迟 ————-2:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消,-5取消
                if ($v['gtime'] + 1800 < time() && $v['game_state'] == 0) continue;          //过了开场时间未开始
                if ($v['gtime'] + 3600*4 < time() && array_search($v['game_state'], [1, 2, 3, 4,5,6,7]) !== false) continue;      //60*4分钟还没结束

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if (!empty($v['u_name']))
                    $val[2] = explode(',', $v['u_name']);
                else
                    $val[2] = explode(',', $v['union_name']);
                $val[3] = !empty($v['union_color']) ? $v['union_color'] : '#aeba4b';
                $val[4] = $v['grade'] !==null ? $v['grade'] : '';
                $val[5] = $v['game_state'];
                $gameTime = explode('-', date('Ymd-H:i', $v['gtime']));
                $val[6] = $gameTime[0];
                $val[7] = $gameTime[1];
                $val[8] = isset($cData[$v['game_id']]) ? $cData[$v['game_id']] : '';
                $val[9] = explode(',', $v['home_team_name']);
                $val[10] = explode(',', $v['away_team_name']);
                $home_rank = !empty($v['home_team_rank']) ? pregUnionRank($v['home_team_rank']) : '';
                $away_rank = !empty($v['away_team_rank']) ? pregUnionRank($v['away_team_rank']) : '';
                $val[11] = $home_rank !== false ? $home_rank : '';
                $val[12] = $away_rank !== false ? $away_rank : '';
                $score = explode('-', $v['score']);
                $val[13] = $score[0];
                $val[14] = isset($score[1]) ? $score[1] : '';
                #小节比分
                $list_score = explode(',', $v['list_score']);
                if (!empty($list_score[0])) {
                    $list_score1 = explode('-', $list_score[0]);
                    $val[15] = $list_score1[0];
                    $val[16] = isset($list_score1[1]) ? $list_score1[1] : '';
                } else {
                    $val[15] = '';
                    $val[16] = '';
                }
                if (!empty($list_score[1])) {
                    $list_score2 = explode('-', $list_score[1]);
                    $val[17] = $list_score2[0];
                    $val[18] = isset($list_score2[1]) ? $list_score2[1] : '';
                } else {
                    $val[17] = '';
                    $val[18] = '';
                }
                if (!empty($list_score[2])) {
                    $list_score3 = explode('-', $list_score[2]);
                    $val[19] = $list_score3[0];
                    $val[20] = isset($list_score3[1]) ? $list_score3[1] : '';
                } else {
                    $val[19] = '';
                    $val[20] = '';
                }
                if (!empty($list_score[3])) {
                    $list_score4 = explode('-', $list_score[3]);
                    $val[21] = $list_score4[0];
                    $val[22] = isset($list_score4[1]) ? $list_score4[1] : '';
                } else {
                    $val[21] = '';
                    $val[22] = '';
                }
                if($v['is_ot'] !== 0 || !empty($v['is_ot']))
                {
                    $val[23] = (string)$v['is_ot'];
                    $oScore1 = $oScore2 = '';
                    $list = explode(',',$v['ot_score']);
                    foreach($list as $k2=>$v2)
                    {
                        $tScore = explode('-',$v2);
                        if($oScore1 == '')
                        {
                            $oScore1 .= $tScore[0];
                            $oScore2 .= $tScore[1];
                        }
                        else
                        {
                            $oScore1 .= '-'.$tScore[0];
                            $oScore2 .= '-'.$tScore[1];
                        }
                    }
                    $val[24] = (string)$oScore1;
                    $val[25] = (string)$oScore2;
                    /*if(in_array($v['game_state'],[5,6,7]))
                    {
                        $oScore1 = $oScore2 = '';
                        $list = explode(',',$v['ot_score']);
                        foreach($list as $k2=>$v2)
                        {
                            $tScore = explode('-',$v2);
                            if($oScore1 == '')
                            {
                                $oScore1 .= $tScore[0];
                                $oScore2 .= $tScore[1];
                            }
                            else
                            {
                                $oScore1 .= '-'.$tScore[0];
                                $oScore2 .= '-'.$tScore[1];
                            }
                        }
                        $val[24] = (string)$oScore1;
                        $val[25] = (string)$oScore2;
                    }
                    else
                    {
                        $val[24] = '';
                        $val[25] = '';
                    }*/
                }
                else
                {
                    $val[23] ='0';
                    $val[24] = '';
                    $val[25] = '';
                }

                /*$val[23] = isset($v['is_ot']) ? $v['is_ot'] : '0';
                $ot_score = explode('-', $v['ot_score']);
                $val[24] = $ot_score[0];
                $val[25] = isset($ot_score[1]) ? $ot_score[1] : '';
*/
                #全场让分大小初盘赔率
               /* $val[26] = !empty($v['fsw_exp_home']) ? $v['fsw_exp_home'] : '';
                $val[27] = !empty($v['fsw_exp']) ? $v['fsw_exp'] : '';
                $val[28] = !empty($v['fsw_exp_away']) ? $v['fsw_exp_away'] : '';
                $val[29] = !empty($v['fsw_total_home']) ? $v['fsw_total_home'] : '';
                $val[30] = !empty($v['fsw_total']) ? $v['fsw_total'] : '';
                $val[31] = !empty($v['fsw_total_away']) ? $v['fsw_total_away'] : '';
                $val[32] = !empty($v['fsw_europe_home']) ? $v['fsw_europe_home'] : '';
                $val[33] = !empty($v['fsw_europe_away']) ? $v['fsw_europe_away'] : '';*/

                if (isset($oddsArr[$v['game_id']])) {
                    $val[26] = $oddsArr[$v['game_id']][17];  //主队亚盘初盘赔率
                    $val[27] = $oddsArr[$v['game_id']][18];   //亚盘初盘盘口
                    $val[28] = $oddsArr[$v['game_id']][19];   //客队亚盘初盘赔率
                    $val[29] = $oddsArr[$v['game_id']][20];  //主队大小初盘赔率
                    $val[30] = $oddsArr[$v['game_id']][21];   //大小初盘盘口
                    $val[31] = $oddsArr[$v['game_id']][22];   //客队大小初盘赔率
                    $val[32] = $oddsArr[$v['game_id']][23];   //欧赔主队初盘
                    $val[33] = $oddsArr[$v['game_id']][24];   //欧赔客队初盘
                } else {
                    $val[26] = '';
                    $val[27] = '';
                    $val[28] = '';
                    $val[29] = '';
                    $val[30] = '';
                    $val[31] = '';
                    $val[32] = '';
                    $val[33] = '';
                }
                if (isset($oddsArr[$v['game_id']])) {
                    $val[34] = $oddsArr[$v['game_id']][0];  //主队亚盘即时赔率
                    $val[35] = $oddsArr[$v['game_id']][1];   //亚盘即时盘口
                    $val[36] = $oddsArr[$v['game_id']][2];   //客队亚盘即时赔率
                    $val[37] = $oddsArr[$v['game_id']][3];  //主队大小即时赔率
                    $val[38] = $oddsArr[$v['game_id']][4];   //大小即时盘口
                    $val[39] = $oddsArr[$v['game_id']][5];   //客队大小即时赔率
                    $val[40] = $oddsArr[$v['game_id']][6];   //欧赔主队即时赔率
                    $val[41] = $oddsArr[$v['game_id']][7];   //欧赔客队即时赔率
                } else {
                    $val[34] = '';
                    $val[35] = '';
                    $val[36] = '';
                    $val[37] = '';
                    $val[38] = '';
                    $val[39] = '';
                    $val[40] = '';
                    $val[41] = '';
                }

                if($v['game_state'] != -1)
                {
                    //是否有直播
                    if(!empty($v['app_video']))
                    {
                        #video
                        if(!empty(json_decode($v['app_video'])))
                            $val[42] = '1';
                        else
                            $val[42] = '0';
                    }
                    else
                    {
                        $val[42] = '0';
                    }

                    if(isset($blData[$v['game_id']]))
                    {
                        if(in_array($v['game_state'],[1,2,3,4,5,6,7,50]))
                        {
                            if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                                $val[43] = '1';
                            else
                                $val[43] = '0';
                        }
                        else
                        {
                            $val[43] = '1';
                        }
                    }
                    else
                    {
                        $val[43] = '0';
                    }
                }
                else
                {
                    $val[42] = '0';
                    $val[43] = '0';
                }
                if(!in_array(MODULE_NAME,['Api','Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400']))
                {
                    $val[44] = (string)$v['is_video'];
                    if(isset($blData[$v['game_id']]))
                    {
                        if(in_array($v['game_state'],[1,2,3,4,5,6,7,50]))
                        {
                            if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                                $val[45] = '1';
                            else
                                $val[45] = '0';
                        }
                        else
                        {
                            $val[45] = '1';
                        }
                    }
                    else
                    {
                        $val[45] = '0';
                    }
                    if($platform == 'robot')
                    {
                        if(isset($teamArr[$v['home_team_id']]))
                            $val[46] = $teamArr[$v['home_team_id']] !== null?$teamArr[$v['home_team_id']]:'';
                        else
                            $val[46] = '';
                        if(isset($teamArr[$v['away_team_id']]))
                            $val[47] = $teamArr[$v['away_team_id']] !== null?$teamArr[$v['away_team_id']]:'';
                        else
                            $val[47] = '';
                    }
                }

                $rData[] = $val;
            }
        }
        return $rData;
    }

    /**
     * 篮球完场赛事
     * @return array  篮球完场赛事
     */
    public function bkOverList($date,$plat = 1) {
        $GameBkinfo = M('GameBkinfo');
        $map['a.status'] = 1;
        if (!empty($date)) {
            $startTime = strtotime($date . ' 15:30:00');
            $endTime = $startTime + 3600 * 24;
        } else {
            $sTime = strtotime('15:30:00');
            if ($sTime < time()) {
                $startTime = $sTime;
                $endTime = $sTime + 3600 * 24;
            } else {
                $startTime = $sTime - 3600 * 24;
                $endTime = $sTime;
            }
        }
        $map['gtime'] = array('between', "$startTime,$endTime");
        $GameList = $GameBkinfo->alias('a')->field('game_id,a.union_id,a.union_name,gtime,game_state,home_team_name,away_team_name,score,list_score,is_ot,ot_score,u.union_name u_name,union_color,fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away,grade,is_video,is_flash')
                ->join('LEFT JOIN qc_bk_union u ON a.union_id=u.union_id')->where($map)->order('gtime,is_sub,a.id')->select();

        $rData = [];
        foreach ($GameList as $k => $v)
        {
            if ($v['game_state'] == -14 || $v['game_state'] == -10 || $v['game_state'] == -2 || $v['game_state'] == -12 || $v['game_state'] == -13 || $v['game_state'] == -5) continue;          //屏蔽待定和推迟 ————-2:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场,-10:取消,-5:未知
            if ($v['gtime'] + 60 < time() && $v['game_state'] == 0 ) continue;          //过了开场时间未开始
            if ($v['gtime'] + 3600*4 < time() && array_search($v['game_state'], [1, 2, 3, 4,5,6,7]) !== false) continue;      //60*4分钟还没结束

            $val = [];
            $list_score=  explode(',', $v['list_score']);
            foreach ($list_score as &$vo){
                $vo=  explode('-', $vo);
            }
            $val[0] = $v['game_id'];
            $val[1] = $v['union_id'];
            if (!empty($v['u_name']))
                $val[2] = explode(',', $v['u_name']);
            else
                $val[2] = explode(',', $v['union_name']);
            $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
            $val[4] = $v['game_state'];
            $gameTime = explode('-', date('Ymd-H:i', $v['gtime']));
            $val[5] = $gameTime[0];
            $val[6] = $gameTime[1];
            $val[7] = explode(',', $v['home_team_name']);
            $val[8] = explode(',', $v['away_team_name']);
            $score = explode('-', $v['score']);
            $val[9] = $score[0];
            $val[10] = $score[1] !== null ? $score[1] : '';
            $val[11] = isset($list_score[0][0])?$list_score[0][0]:'';
            $val[12] = isset($list_score[0][1])?$list_score[0][1]:'';
            $val[13] = isset($list_score[1][0])?$list_score[1][0]:'';
            $val[14] = isset($list_score[1][1])?$list_score[1][1]:'';
            $val[15] = isset($list_score[2][0])?$list_score[2][0]:'';
            $val[16] = isset($list_score[2][1])?$list_score[2][1]:'';
            $val[17] = isset($list_score[3][0])?$list_score[3][0]:'';
            $val[18] = isset($list_score[3][1])?$list_score[3][1]:'';
            $val[19] = !empty($v['is_ot'])?$v['is_ot']:'0';
            if($v['ot_score']){
                $home_ot='';
                $away_ot='';
                $ot_score=  explode(',', $v['ot_score']);
                foreach($ot_score as $va){
                    $ot=  explode('-', $va);
                    if($plat == 3 && in_array(MODULE_NAME,['Api203']))
                    {
                        $home_ot.=$ot[0].'_';
                        $away_ot.=$ot[1].'_';
                    }
                    else
                    {
                        $home_ot.=$ot[0].'-';
                        $away_ot.=$ot[1].'-';
                    }
                }
                if($home_ot){
                    $val[20] = substr($home_ot, 0,-1);
                }else{
                    $val[20]='';
                }
                if($away_ot){
                    $val[21] = substr($away_ot, 0,-1);
                }else{
                    $val[21]='';
                }
            }else{
                $val[20] = '';
                $val[21] = '';
            }
            $val[22] = $v['fsw_exp_home'] == null?'':$v['fsw_exp_home'];
            $val[23] = $v['fsw_exp'] == null?'':$v['fsw_exp'];
            $val[24] = $v['fsw_exp_away'] == null?'':$v['fsw_exp_away'];
            $val[25] = $v['fsw_total_home'] == null?'':$v['fsw_total_home'];
            $val[26] = $v['fsw_total'] == null?'':$v['fsw_total'];
            $val[27] = $v['fsw_total_away'] == null?'':$v['fsw_total_away'];
            $val[28] = $v['grade'] == null?'':$v['grade'];

            if($v['game_state'] != -1)
            {
                $val[29] = (string)$v['is_video'];      //是否有直播
                $val[30] = isset($blData[$v['game_id']])?(string)$blData[$v['game_id']]:'0';    //是否有动画
            }
            else
            {
                $val[29] = '0';
                $val[30] = '0';
            }
            $rData[] = $val;
        }
        return $rData;
    }

    /**
     * 篮球赛程
     * @return array  篮球赛程
     */
    public function bkFutureList($date)
    {
        $GameBkinfo = M('GameBkinfo');
        $map['a.status'] = 1;
        if (!empty($date)) {
            $startTime = strtotime($date . ' 15:30:00');
            $endTime = $startTime + 3600 * 24;
        } else {
            $sTime = strtotime('15:30:00');
            if ($sTime < time()) {
                $startTime = $sTime;
                $endTime = $sTime + 3600 * 24;
            } else {
                $startTime = $sTime - 3600 * 24;
                $endTime = $sTime;
            }
        }
        $map['gtime'] = array('between', "$startTime,$endTime");
        $GameList = $GameBkinfo->alias('a')->field('game_id,a.union_id,a.union_name,gtime,game_state,home_team_name,away_team_name,fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away,u.union_name u_name,union_color,grade,is_video,app_video,is_flash')->join('LEFT JOIN qc_bk_union u ON a.union_id=u.union_id')->where($map)->order('gtime,is_sub,a.id')->select();
        $rData = [];
       // echo M()->getlastsql();exit;
        if(!empty($GameList))
        {
            $gids = [];
            foreach ($GameList as $k => $v)
            {
                $gids[] = $v['game_id'];
            }
            $oddsArr = $this->bkOdds($gids,3);

            $blData = $mdArr = [];
            $blRes = M('BkLinkbet')->field('game_id,is_link,flash_id,md_id')->where(implode($gids))->select();
            if(!empty($blRes))
            {
                foreach($blRes as $k=>$v)
                {
                    $blData[$v['game_id']] = $v['is_link'];
                    if(!empty($v['flash_id']) && !empty($v['md_id']))
                    {
                        $mdArr[$v['game_id']] = $v['md_id'];
                    }
                }
            }

            foreach ($GameList as $k => $v)
            {
                if ($v['game_state'] == -14 || $v['game_state'] == -10 || $v['game_state'] == -2 || $v['game_state'] == -12 || $v['game_state'] == -13 || $v['game_state'] == -5) continue;          //屏蔽待定和推迟 ————-2:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场,-10:取消,-5:未知
                if ($v['gtime'] + 60 < time() && $v['game_state'] == 0 ) continue;          //过了开场时间未开始
                if ($v['gtime'] + 3600*4 < time() && array_search($v['game_state'], [1, 2, 3, 4,5,6,7]) !== false) continue;      //60*4分钟还没结束


                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if (!empty($v['u_name']))
                    $val[2] = explode(',', $v['u_name']);
                else
                    $val[2] = explode(',', $v['union_name']);
                $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[4] = $v['game_state'];
                $gameTime = explode('-', date('Ymd-H:i', $v['gtime']));
                $val[5] = $gameTime[0];
                $val[6] = $gameTime[1];
                $val[7] = explode(',', $v['home_team_name']);
                $val[8] = explode(',', $v['away_team_name']);
                #全场让分大小初盘赔率
                $val[9] = !empty($v['fsw_exp_home']) ? $v['fsw_exp_home'] : '';
                $val[10] = !empty($v['fsw_exp']) ? $v['fsw_exp'] : '';
                $val[11] = !empty($v['fsw_exp_away']) ? $v['fsw_exp_away'] : '';
                $val[12] = !empty($v['fsw_total_home']) ? $v['fsw_total_home'] : '';
                $val[13] = !empty($v['fsw_total']) ? $v['fsw_total'] : '';
                $val[14] = !empty($v['fsw_total_away']) ? $v['fsw_total_away'] : '';
                $val[15] = !empty($v['fsw_europe_home']) ? $v['fsw_europe_home'] : '';
                $val[16] = !empty($v['fsw_europe_away']) ? $v['fsw_europe_away'] : '';

                if (isset($oddsArr[$v['game_id']])) {
                    $val[17] = $oddsArr[$v['game_id']][0];  //主队亚盘即时赔率
                    $val[18] = $oddsArr[$v['game_id']][1];   //亚盘即时盘口
                    $val[19] = $oddsArr[$v['game_id']][2];   //客队亚盘即时赔率
                    $val[20] = $oddsArr[$v['game_id']][3];  //主队大小即时赔率
                    $val[21] = $oddsArr[$v['game_id']][4];   //大小即时盘口
                    $val[22] = $oddsArr[$v['game_id']][5];   //客队大小即时赔率
                    $val[23] = $oddsArr[$v['game_id']][6];   //大小即时盘口
                    $val[24] = $oddsArr[$v['game_id']][7];   //客队大小即时赔率
                } else {
                    $val[17] = '';
                    $val[18] = '';
                    $val[19] = '';
                    $val[20] = '';
                    $val[21] = '';
                    $val[22] = '';
                    $val[23] = '';
                    $val[24] = '';
                }
                $val[25] = $v['grade'] == null?'':$v['grade'];

                if($v['game_state'] != -1)
                {
                    //是否有直播
                    if(!empty($v['app_video']))
                    {
                        #video
                        if(!empty(json_decode($v['app_video'])))
                            $val[26] = '1';
                        else
                            $val[26] = '0';
                    }
                    else
                    {
                        $val[26] = '0';
                    }

                    if(isset($blData[$v['game_id']]))
                    {
                        if(in_array($v['game_state'],[1,2,3,4,5,6,7,50]))
                        {
                            if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                                $val[27] = '1';
                            else
                                $val[27] = '0';
                        }
                        else
                        {
                            $val[27] = '1';
                        }
                    }
                    else
                    {
                        $val[27] = '0';
                    }
                }
                else
                {
                    $val[26] = '0';
                    $val[27] = '0';
                }

                $rData[] = $val;
            }
        }
        return $rData;
    }

    /**
     * 获取篮球change数据
     * @return array
     */
    public function getbkChange()
    {
        $rData = [];
        if(S('cache_bk_change'))
        {
            $rData = S('cache_bk_change');
            unset($rData['cache']);
        }
        else
        {
            $res = M()->query('select game_id,game_id_new,change_str,update_time from qc_bk_change where update_time = (select update_time as utime from qc_bk_change order by update_time desc limit 1) order by id');

            if (!empty($res))
            {
                if ($res[0]['update_time'] + 40 > time())
                {
                    foreach ($res as $k => $v)
                    {
                        $arr = explode('^', $v['change_str']);
                        $aTemp[0] = $arr[0];     //赛事ID
                        $aTemp[1] = $arr[1];  //进行赛事节数
                        $aTemp[2] = $arr[2];  //比赛小节时间
                        $aTemp[3] = $arr[3];  //主队总得分
                        $aTemp[4] = $arr[4];  //客队总得分
                        $aTemp[5] = $arr[5];  //第一节主队得分
                        $aTemp[6] = $arr[6];  //第一节主队得分
                        $aTemp[7] = $arr[7];  //第二节主队得分
                        $aTemp[8] = $arr[8];  //第二节主队得分
                        $aTemp[9] = $arr[9];  //第三节主队得分
                        $aTemp[10] = $arr[10];   //第三节主队得分
                        $aTemp[11] = $arr[11];  //第四节主队得分
                        $aTemp[12] = $arr[12];  //第四节主队得分
                        $aTemp[13] = $arr[13];  //加时节数
                        $aTemp[14] = $arr[16];  //加时第一节主队得分
                        $aTemp[15] = $arr[17];   //加时第一节客队得分
                        $aTemp[16] = $arr[18];  //加时第二节主队得分
                        $aTemp[17] = $arr[19];  //加时第二节客队得分
                        $aTemp[18] = $arr[20];  //加时第三节主队得分
                        $aTemp[19] = $arr[21];  //加时第三节客队得分
                        $rData[$arr[0]] = $aTemp;
                    }
                }
            }
            $rData['cache'] = 'true';
            S('cache_bk_change',$rData,1);
            unset($rData['cache']);
        }
        return $rData;
    }

    /**
     * 获取全场指数变化数据(数据库)
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     */
    public function getbkodds($companyID = 3 )
    {
        if(empty($companyID)) return false;

        //$sql = 'select max(update_time) as utime from qc_bk_goal where company_id='.$companyID;
        $sql = 'select update_time as utime from qc_bk_goal where company_id='.$companyID.' order by update_time desc limit 1 ';
        $res = M()->query($sql);

        $rData = [];
        if (!empty($res) && $res[0]['utime'] >time()-40)
        {
            $sql = 'select * from qc_bk_goal where update_time ='.$res[0]['utime'].' and company_id='.$companyID;
            $res = M()->query($sql);

            foreach($res as $k=>$v)
            {
                $temp = [];
                $odds1 = explode('^',$v['exp_value']);
                #让分赔率
                $aOdds = explode(',',$odds1[0]);
                if(!empty($aOdds[6]) || !empty($aOdds[7]) || !empty($aOdds[8]))
                {
                    $temp[0] = formatExp($aOdds[6]);
                    $temp[1] = formatExp($aOdds[7]);
                    $temp[2] = formatExp($aOdds[8]);
                }
                else if(!empty($aOdds[3]) || !empty($aOdds[4]) || !empty($aOdds[5]))
                {
                    $temp[0] = formatExp($aOdds[3]);
                    $temp[1] = formatExp($aOdds[4]);
                    $temp[2] = formatExp($aOdds[5]);
                }
                else
                {
                    $temp[0] = '';
                    $temp[1] = '';
                    $temp[2] = '';
                }
                #大小赔率
                $bOdds = explode(',',$odds1[1]);
                if(!empty($bOdds[6]) || !empty($bOdds[7]) || !empty($bOdds[8]))
                {
                    $temp[3] = formatExp($bOdds[6]);
                    $temp[4] = formatExp($bOdds[7]);
                    $temp[5] = formatExp($bOdds[8]);
                }
                else if(!empty($bOdds[3]) || !empty($bOdds[4]) || !empty($bOdds[5]))
                {
                    $temp[3] = formatExp($bOdds[3]);
                    $temp[4] = formatExp($bOdds[4]);
                    $temp[5] = formatExp($bOdds[5]);
                }
                else
                {
                    $temp[3] = '';
                    $temp[4] = '';
                    $temp[5] = '';
                }
                #欧赔
                $oOdds = explode(',',$odds1[2]);
                if(!empty($oOdds[4]) || !empty($oOdds[5]))
                {
                    $temp[6] = formatExp($oOdds[4]);
                    $temp[7] = formatExp($oOdds[5]);
                }
                else if(!empty($oOdds[2]) || !empty($oOdds[3]))
                {
                    $temp[6] = formatExp($oOdds[2]);
                    $temp[7] = formatExp($oOdds[3]);
                }
                else
                {
                    $temp[6] = '';
                    $temp[7] = '';
                }
                $rData[$v['game_id']] = $temp;
            }
        }
        return $rData;
    }

    /**
     * 获取篮球赔率数据
     * @return json
     */
    public function getbkoddsB($gameId = '')
    {
        $item = $this->data['nbaodds'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath . $item['savePath'] . $item['name'] . $ext;

        if (is_file($fileName)) {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $aBkodds = $disposeData->bkodds($content);
        }
        if (!empty($gameId))
            $aGameId = explode(',', $gameId);

        $rData = [];
        if (!empty($aBkodds)) {
            foreach ($aBkodds as $k => $v) {
                $aTemp[0] = $v[0];     //赛事ID
                $aTemp[1] = $v[1];  //主队让分赔率
                $aTemp[2] = $v[2];  //让分盘口
                $aTemp[3] = $v[3];  //客队让分赔率
                $aTemp[4] = $v[4];  //主队总分赔率
                $aTemp[5] = $v[5];  //总分赔率
                $aTemp[6] = $v[6];  //客队总分赔率
                $aTemp[7] = $v[7];  //主队欧赔赔率
                $aTemp[8] = $v[8];  //客队欧赔赔率
                if (!empty($aGameId) && array_search($v[0], $aGameId) === false)
                    continue;
                $rData[$v[0]] = $aTemp;
            }
        }
        return $rData;
    }

    /**
     * 获取篮球亚欧赔界面数据
     * @return array
     */
    public function getbkMatchOdds($gameId, $type)
    {
        $fieldArr = array(
            '1' => 'aodds',
            '2' => 'oodds',
            '3' => 'bodds',
        );
        $field = $fieldArr[$type];
        $res = M()->query('select ' . $field . ' FROM qc_bk_matchodds where  game_id = ' . $gameId . ' order by update_time desc limit 1');

        $rData = [];
        if (!empty($res[0][$field]))
        {
            $bk_company = C('DB_BK_COMPANY_ODDS');
            $companyArr = explode('!', $res[0][$field]);

            #add kwight
            $cids = [];
            foreach ($companyArr as $k => $v)
            {
                $temp = explode('^',$v);
                $cids[] = $temp[1];
            }

            $map['jb_id'] = array('in', implode(',',$cids));
            $cRes = M('BkCompany')->field('id,jb_id')->where($map)->select();

            $comIds = [];
            foreach ($cRes as $k => $v)
            {
                $comIds[$v['jb_id']] = $v['id'];
            }
            #add kwight end

            $oddsGj = ['h'=>0,'a'=>0];
            $oddsGje = ['h'=>['rise'=>0,'equal'=>0,'drop'=>0],'a'=>['rise'=>0,'equal'=>0,'drop'=>0]];
            foreach ($companyArr as $k => $v)
            {
                $arr = explode('^', $v);

                if ($field == 'oodds')
                {
                    //$company_id = M('BkCompany')->where(['jb_id' => $arr[1]])->getField('id');
                    $company_id = $comIds[$arr[1]];
                }
                else
                {
                    $arr[0] = ucfirst($arr[0]);
                    $company_id = array_search($arr[0], $bk_company);
                    if ($company_id == false) {
                        continue;
                    }
                }
                if($type == 1 || $type == 3)
                {
                    $tj = $this->abTrend($arr[3],$arr[6],$arr[2],$arr[5],$endfswOdds[4],$endfswOdds[7]);
                    $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                    $oddsGj['a'] = $oddsGj['a'] + $tj['a'];
                }
                else
                {
                    $tj = $this->eurTrend($arr[2],$arr[4]);
                    $oddsGje['h']['rise'] = $oddsGje['h']['rise'] + $tj['h']*2;
                    $oddsGje['h']['equal'] = $oddsGje['h']['equal'] + $tj['d']*2;
                    $oddsGje['h']['drop'] = $oddsGje['h']['drop'] + $tj['a']*2;
                    $tj = $this->eurTrend($arr[3],$arr[5]);
                    $oddsGje['a']['rise'] = $oddsGje['a']['rise'] + $tj['h']*2;
                    $oddsGje['a']['equal'] = $oddsGje['a']['equal'] + $tj['d']*2;
                    $oddsGje['a']['drop'] = $oddsGje['a']['drop'] + $tj['a']*2;
                }

                $aTemp[0] = $arr[0];     //公司名称
                $aTemp[1] = (string) $company_id;  //公司id
                $aTemp[2] = $arr[2];  //
                $aTemp[3] = $field == 'oodds' ? '' : $arr[3];  //
                $aTemp[4] = $field == 'oodds' ? $arr[3] : $arr[4];  //
                $aTemp[5] = $field == 'oodds' ? $arr[4] : $arr[5];  //
                $aTemp[6] = $field == 'oodds' ? '' : $arr[6];  //
                $aTemp[7] = $field == 'oodds' ? $arr[5] : $arr[7];  //
                $rData[$k] = $aTemp;
            }
        }
        /*if($type == 1 || $type == 3)
            $rData['aobTrend'] = $oddsGj;
        else
            $rData['aobTrend'] = $oddsGje;*/
        return $rData;
    }

    /**
     * 获取篮球历史赔率数据
     * @return array
     */
    public function getbkOddsHistory($gameId, $companyId, $type)
    {
        $fieldArr = array(
            '1' => 'ahistory',
            '2' => 'bhistory',
            '3' => 'ohistory',
        );
        $field = $fieldArr[$type];
        $res = M()->query('select ' . $field . ' FROM qc_bk_oddshis where  game_id = ' . $gameId . ' AND company_id= ' . $companyId . ' order by update_time desc limit 1');

        $rData = [];
        if (!empty($res[0][$field]))
        {
            $companyArr = explode('!', $res[0][$field]);
            foreach ($companyArr as $k => $v)
            {
                $arr = explode('^', $v);
                $aTemp[0] = $arr[0];     //
                $aTemp[1] = $field == 'bhistory' ? '' : $arr[1];
                $aTemp[2] = $field == 'bhistory' ? $arr[1] : $arr[2];
                $tempTime = $field == 'bhistory' ? $arr[2] : $arr[3];
                $aTemp[3] = date('Y-m-d H:i',strtotime($tempTime));
                $rData[$k] = $aTemp;
            }
        }
        return $rData;
    }

    /**
     * 根据公司ID获取数据分析界面数据(不可用)
     * @param  int   $gameId  赛事ID
     * @return array  赔率数据
     */
    public function getAnalysis($gameId,$lang = 1)
    {
        if(empty($gameId)) return false;
        $GameBkinfo = M('GameBkinfo');
        $baseRes = $GameBkinfo->field('*')->where('game_id = '.$gameId)->find();
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

        $bkService = new \Common\Services\BkdataService();
        #对战历史
        $res = $bkService->getMatchFight($baseRes['home_team_id'],$baseRes['away_team_id'],$baseRes['gtime'] ,$lang);
        if(!empty($res))
        {
            $rData[] = ['name'=>'match_fight','content'=>$res];
        }
        #近期交战历史
        $res = $bkService->getRecentFight($baseRes['home_team_id'] ,$baseRes['gtime'],$lang);
        $recentTemp = [];
        if(!empty($res))
        {
            $recentTemp[] = ['name'=>'recent_fight1','content'=>$res];
        }
        $res = $bkService->getRecentFight($baseRes['away_team_id'] ,$baseRes['gtime'],$lang);
        if(!empty($res))
        {
            $recentTemp[] = ['name'=>'recent_fight2','content'=>$res];
        }
        if(!empty($recentTemp)) $rData[] = ['name'=>'recent_fight','content'=>$recentTemp];

        #联赛积分
        $res = $bkService->getMatchInt($gameId);
        $rankRes = $bkService->teamRank($baseRes['home_team_id'],$baseRes['away_team_id'],$baseRes['union_id'],$baseRes['gtime'],$baseRes['years']);
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

        #盘路
        $res1 = $bkService->getPanlu($baseRes['home_team_id'] ,$baseRes['union_id'],$baseRes['gtime']);
        $res2 = $bkService->getPanlu($baseRes['away_team_id'] ,$baseRes['union_id'],$baseRes['gtime']);
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
                    $str1 .= !empty($str1)?'贏':' 贏';
                }
                else if ($v == -1)
                {
                    $str1 .= !empty($str1)?'输':' 输';
                }
                else
                {
                    $str1 .= !empty($str1)?'走':' 走';
                }
            }
            $str2= '';
            foreach($res1[4] as $k=>$v)
            {
                if($v == 1)
                    $str2 .= !empty($str2)?'大':' 大';
                else
                    $str2 .= !empty($str2)?'小':' 小';
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
                    $str1 .= !empty($str1)?'贏':' 贏';
                }
                else if ($v == -1)
                {
                    $str1 .= !empty($str1)?'输':' 输';
                }
                else
                {
                    $str1 .= !empty($str1)?'走':' 走';
                }
            }
            $str2= '';
            foreach($res2[4] as $k=>$v)
            {
                if($v == 1)
                {
                    $str2 .= !empty($str2)?'大':' 大';
                }
                else
                {
                    $str2 .= !empty($str2)?'小':' 小';
                }
            }
            $sTemp = round($ratio[1]/$ratio[0],2)*100;
            $paluTemp[7] = [0=> $atRank.$aTeamName,1=> '近6场',2=> (string)$ratio[0], 3=> $str1,4=> $sTemp.'%',5=> '查看',6=> $str2,7=> '',8=> '', 9=> '' ,10=> ''];
        }
        if(!empty($paluTemp)) $rData[] = ['name'=>'match_panlu','content'=>$paluTemp];
        #未来三场
        $res1 = $bkService->getFutureThree($baseRes['home_team_id'] ,$baseRes['gtime'],$lang);
        $res2 = $bkService->getFutureThree($baseRes['away_team_id'] ,$baseRes['gtime'],$lang);
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
     * 根据赛事ID获取分析界面数据
     * @param  int   $gameId  赛事ID
     * @param  int   $lang  语言ID
     * @return array  数据
     */
    public function getAnaForFile($gameId,$lang = 1)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $rData = [];
        $item = $this->data['nbaanalysis'];
        $ext = getFileExt($item['mimeType']);

        $GameBkinfo = D('GameBkinfo');
        $map['game_id'] = $gameId;
        $baseRes = $GameBkinfo->field('*')->where($map)->find();

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

        if($lang == 1)
        {
            $fileName1 = DataPath.$item['savePath'].$date.'/'.$gameId.'cn'.$ext;
            if(is_file($fileName1))
            {
                $fileName = $fileName1;
            }
            else
                $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;
        }
        else
        {
            $fileName2 = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;
            if(is_file($fileName2))
                $fileName = $fileName2;
            else
                $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.'cn'.$ext;
        }

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();

            $content = str_replace(array("\t","\n","\r"),"",$content);
            $content = str_replace("TABLE","table",$content);
            $content = preg_replace('/>\s+</is','><',$content);
            $content = preg_replace('/>\s+<\//is','><\/',$content);
            $rData = [];
            $rData[] = ['name'=>'game_info','content'=>[0=> $baseRes['union_id']!==null?$baseRes['union_id']:'',1=>$baseRes['home_team_id'],2=>$baseRes['away_team_id'],3=> $htn,4=>$atn,5=>$utn]];

            $doc = \phpQuery::newDocumentHTML($content);

            foreach(pq('tr') as $k=>$tr)
            {
                #积 分 排 名
                if(strpos(pq($tr)->html(),'積 分 排 名')!== false || strpos(pq($tr)->html(),'积 分 排 名')!== false)
                {
                    $tHtml = pq('tr:eq('.($k+1).')')->html();
                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 >5) continue;
                        $temp = [];
                        $temp[0] = $hTeamName;
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            if($k1 == 5 && $k2 == 3) $temp[$k2-1] = (string)($temp[$k2]+pq($td)->text());        //近十，改为实际场次
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;
                    }

                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(1)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 >5) continue;
                        $temp = [];
                        $temp[0] = $aTeamName;
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            if($k1 == 5 && $k2 == 3) $temp[$k2-1] = (string)($temp[$k2]+pq($td)->text());        //近十，改为实际场次
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;
                    }
                    if(!empty($aTemp)) $match_integral = ['name'=>'match_integral','content'=> $aTemp];
                    if(!empty($match_integral)) $rData[] = $match_integral;
                }
                #近20场
                if(strpos(pq($tr)->html(),'近 20 場') !== false || strpos(pq($tr)->html(),'近 20 场') !== false)
                {
                    $RecentFight = [];
                    $RecentFight['name'] ='recent_fight';
                    $RecentFight['content'] =array();

                    $aTemp = [];
                    $RecentFight1 = [];
                    $RecentFight1['name'] ='recent_fight1';
                    $RecentFight1['content'] =array();
                    $name1 = '';
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0)
                        {
                            $name1 = \Think\Tool\Tool::html2text(pq($tr)->text());
                        }
                        if($k1 == 0 ||$k1 == 1 || $k1 >21) continue;
                        $temp = [];
                        $temp[0] = $name1;
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;
                    }

                    if(!empty($aTemp)) $RecentFight1['content'] = $aTemp;
                    if(!empty($aTemp)) $RecentFight['content'][] = $RecentFight1;

                    $aTemp = [];
                    $RecentFight2 = [];
                    $RecentFight2['name'] ='recent_fight2';
                    $RecentFight2['content'] =array();

                    if(preg_match_all('/<h3>近 20 場<\/h3><\/td>(.*?)<\/tr>(.*?)<tr class="title_h3">/is',$content,$rfArr))
                    {
                        preg_match_all('/<table(.*?)>(.*?)<\/table>/is',$rfArr[0][0],$awrfArr);
                        preg_match_all('/<tr(.*?)>(.*?)<\/tr>/is',$awrfArr[2][1],$trArr);
                        //var_dump($trArr);exit;
                        $name2 = '';
                        foreach($trArr[2] as $k3=>$v3)
                        {
                            if($k3 == 0)
                            {
                                $name2 = \Think\Tool\Tool::html2text($v3);
                            }
                            if($k3 <2) continue;
                            preg_match_all('/<td(.*?)>(.*?)<\/td>/is',$v3,$tdArr);

                            $temp = [];
                            $temp[] = $name2;
                            foreach($tdArr[2] as $k4=>$v4)
                            {
                                $temp[] = \Think\Tool\Tool::html2text($v4);
                            }
                            $aTemp[] = $temp;
                        }
                        if(!empty($aTemp)) $RecentFight2['content'] = $aTemp;
                        if(!empty($aTemp)) $RecentFight['content'][] = $RecentFight2;
                    }
                    if(!empty($RecentFight['content'])) $rData[] = $RecentFight;
                }

                #对战历史
                if(strpos(pq($tr)->html(),'對 戰 往 績') !== false || strpos(pq($tr)->html(),'对 战 往 绩') !== false)
                {
                    $tHtml = pq('tr:eq('.($k+1).')')->html();
                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 >11) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }
                    if(!empty($aTemp)) $match_fight = ['name'=>'match_fight','content'=> $aTemp];
                    if(!empty($match_integral)) $rData[] = $match_fight;
                }
                #技术统计
                if(strpos(pq($tr)->html(),'技術統計') !== false || strpos(pq($tr)->html(),'技术统计') !== false)
                {

                    $tHtml = pq('tr:eq('.($k+1).')')->html();
                    $statistics['name'] ='statistics';
                    $statistics['content'] =array();

                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }
                    if(!empty($aTemp)) $statistics['content'][0] = $aTemp;

                    $aTemp = [];

                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(1)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }

                    if(!empty($aTemp)) $statistics['content'][1] = $aTemp;

                    if(!empty($statistics['content'])) $rData[] = $statistics;
                }
                #近 三 场 比 赛
                if(strpos(pq($tr)->html(),'近 三 場 比 賽') !== false || strpos(pq($tr)->html(),'近 三 场 比 赛')!== false )
                {

                    $tHtml = pq('tr:eq('.($k+1).')')->html();
                    $match_three['name'] ='match_three';
                    $match_three['content'] =array();

                    $aTemp = [];
                    $name1 = '';
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0)
                        {
                            $name1 = \Think\Tool\Tool::html2text(pq($tr)->text());
                        }
                        if($k1 == 0 || $k1 == 1) continue;
                        $temp = [];
                        $temp[] = $name1;
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }
                    if(!empty($aTemp)) $match_three['content'][0] = $aTemp;

                    $aTemp = [];
                    $name2 = '';
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(1)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0)
                        {
                            $name2 = \Think\Tool\Tool::html2text(pq($tr)->text());
                        }
                        if($k1 == 0 || $k1 == 1) continue;
                        $temp = [];
                        $temp[] = $name2;
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }

                    if(!empty($aTemp)) $match_three['content'][1] = $aTemp;
                    if(!empty($match_three['content'])) $rData[] = $match_three;
                }

                #让分盘盘路统计
                if(strpos(pq($tr)->html(),'讓分盤盤路統計') !== false || strpos(pq($tr)->html(),'让分盘盘路统计') !== false)
                {
                    $tHtml = pq('tr:eq('.($k+1).')')->html();
                    $let_panlu['name'] ='let_panlu';
                    $let_panlu['content'] =array();

                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 > 5) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }
                    if(!empty($aTemp)) $let_panlu['content'][0] = $aTemp;

                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(1)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 > 5) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }
                    if(!empty($aTemp)) $let_panlu['content'][1] = $aTemp;
                    if(!empty($let_panlu['content'])) $rData[] = $let_panlu;
                }

                #总分盘盘路统计
                if(strpos(pq($tr)->html(),'總分盤盤路統計') !== false || strpos(pq($tr)->html(),'总分盘盘路统计') !== false)
                {

                    $tHtml = pq('tr:eq('.($k+1).')')->html();
                    $total_panlu['name'] ='total_panlu';
                    $total_panlu['content'] =array();

                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(0)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 > 5) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }
                    if(!empty($aTemp)) $total_panlu['content'][0] = $aTemp;

                    $aTemp = [];
                    foreach(pq('tr:eq('.($k+1).')')->find('table:eq(1)')->find('tr') as $k1=>$tr)
                    {
                        if($k1 == 0 || $k1 == 1 || $k1 > 5) continue;
                        $temp = [];
                        foreach(pq($tr)->find('td') as $k2=>$td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;

                    }

                    if(!empty($aTemp)) $total_panlu['content'][1] = $aTemp;
                    if(!empty($total_panlu['content'])) $rData[] = $total_panlu;
                }
            }
        }
        return $rData;
    }

    /**
     * 根据赛事ID获取分析界面数据
     * @param  int   $gameId  赛事ID
     * @param  int   $lang  语言ID
     * @return array  数据
     */
    public function getAnaForAppNs($gameId,$lang = 1)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $sPath = DataPath.'/basketball/nbaanalysis_cache/';

        $sPath = $sPath.substr($gameId,0,1).'/'.substr($gameId,0,2);
        createDir($sPath);

        $fPath = $sPath.'/'.$gameId.'.txt';
        if(is_file($fPath))
        {
            $str = file_get_contents($fPath);
            return json_decode($str,true);
        }
        else
        {
            $rData = [];
            $item = $this->data['nbaanalysis'];

            $GameBkinfo = D('GameBkinfo');
            $map['game_id'] = $gameId;
            $baseRes = $GameBkinfo->field('*')->where($map)->find();

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

            if($lang == 1)
            {
                $fileName1 = DataPath.$item['savePath'].$date.'/'.$gameId.'cn.txt';
                if(is_file($fileName1))
                {
                    $fileName = $fileName1;
                }
                else
                    $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.'.txt';
            }
            else
            {
                $fileName2 = DataPath.$item['savePath'].$date.'/'.$gameId.'.txt';
                if(is_file($fileName2))
                    $fileName = $fileName2;
                else
                    $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.'cn.txt';
            }

            if(is_file($fileName))
            {
                $content = file_get_contents($fileName);
                $arr = explode('$$',$content);

                #赛事基本信息
                $rData[] = ['name'=>'game_info','content'=>[0=> $baseRes['union_id']!==null?$baseRes['union_id']:'',1=>$baseRes['home_team_id'],2=>$baseRes['away_team_id'],3=> $htn,4=>$atn,5=>$utn]];

                #联赛积分
                if(strlen($arr[0]) >3 || !empty($arr[1]))
                {
                    $cnArr = ['T'=>[0=>'总',1=>'總'],'H'=>[0=>'主场',1=>'主場'],'A'=>[0=>'客场',1=>'客場'],'N'=>[0=>'近10场',1=>'近10場']];
                    $match_integral = ['name'=>'match_integral','content'=> [0=>[],1=>[]]];

                    if(strlen($arr[0]) >3)
                    {
                        $mi1 = [];
                        $miTemp1 = explode('!',$arr[0]);
                        if(!empty($miTemp1))
                        {
                            foreach($miTemp1 as $k2=>$v2)
                            {
                                $temp = explode('^',$v2);
                                if($k2 == 0)
                                    $temp[0] = $cnArr['T'][$lang];
                                else
                                    $temp[0] = $cnArr[trim($temp[0])][$lang];
                                $mi1[] = $temp;
                            }
                        }
                        $match_integral['content'][0] = $mi1;
                    }
                    if(!empty($arr[1]))
                    {
                        $mi2 = [];
                        $miTemp2 = explode('!',$arr[1]);
                        if(!empty($miTemp2))
                        {
                            foreach($miTemp2 as $k2=>$v2)
                            {
                                $temp = explode('^',$v2);
                                if($k2 == 0)
                                    $temp[0] = $cnArr['T'][$lang];
                                else
                                    $temp[0] = $cnArr[trim($temp[0])][$lang];
                                $mi2[] = $temp;
                            }
                        }
                        $match_integral['content'][1] = $mi2;
                    }
                    $rData[] = $match_integral;
                }

                #对战往绩
                if(!empty($arr[2]))
                {
                    $match_fight = ['name'=>'match_fight','content'=> []];
                    $mf = [];
                    $mfTemp = explode('!',$arr[2]);
                    foreach($mfTemp as $k2=>$v2)
                    {
                        $temp = explode('^',$v2);
                        $tTemp = [
                            0 => $temp[1],
                            1 => date('Y/m/d',strtotime($temp[0])),
                            2 => $temp[2],
                            3 => $temp[3],
                            4 => $temp[4],
                            5 => '',
                            6 => '',
                            7 => $temp[5],
                            8 => '',
                            9 => '',
                            10 => '',
                            11 => '',
                            12 => $temp[6],
                            13 => $temp[7],
                        ];
                        $map = ['gtime'=>strtotime($temp[0]),'home_team_id'=>$temp[6],'away_team_id'=>$temp[7]];
                        $tRes = $GameBkinfo->field('game_id')->where($map)->find();
                        $tTemp[14] = !empty($tRes['game_id'])?$tRes['game_id']:'';
                        $mf[] = $tTemp;
                    }
                    $match_fight['content'] = $mf;
                    $rData[] = $match_fight;
                }
                #近期对战
                if(!empty($arr[3]) || !empty($arr[4]))
                {
                    $RecentFight = ['name'=>'recent_fight','content'=> [0=>[],1=>[]]];
                    if(!empty($arr[3]))
                    {
                        $rf1 = [];
                        $rfTemp1 = explode('!',$arr[3]);
                        foreach($rfTemp1 as $k2=>$v2)
                        {
                            $temp = explode('^',$v2);
                            $tTemp = [
                                0 => $htn[0],
                                1 => $temp[1],
                                2 => date('Y/m/d',strtotime($temp[0])),
                                3 => $temp[2],
                                4 => $temp[3],
                                5 => $temp[4],
                                6 => '',
                                7 => '',
                                8 => $temp[5],
                                9 => '',
                                10 => '',
                                11 => '',
                                12 => '',
                                13 => $temp[6],
                                14 => $temp[7],
                            ];
                            $map = ['gtime'=>strtotime($temp[0]),'home_team_id'=>$temp[6],'away_team_id'=>$temp[7]];
                            $tRes = $GameBkinfo->field('game_id')->where($map)->find();
                            $tTemp[15] = !empty($tRes['game_id'])?$tRes['game_id']:'';
                            $rf1[] = $tTemp;
                            //$rf1[] = explode('^',$v2);
                        }
                        $RecentFight['content'][0] = $rf1;
                    }
                    if(!empty($arr[4]))
                    {
                        $rf2 = [];
                        $rfTemp1 = explode('!',$arr[4]);
                        foreach($rfTemp1 as $k2=>$v2)
                        {
                            $temp = explode('^',$v2);
                            $tTemp = [
                                0 => $atn[1],
                                1 => $temp[1],
                                2 => date('Y/m/d',strtotime($temp[0])),
                                3 => $temp[2],
                                4 => $temp[3],
                                5 => $temp[4],
                                6 => '',
                                7 => '',
                                8 => $temp[5],
                                9 => '',
                                10 => '',
                                11 => '',
                                12 => '',
                                13 => $temp[6],
                                14 => $temp[7],
                            ];
                            $map = ['gtime'=>strtotime($temp[0]),'home_team_id'=>$temp[6],'away_team_id'=>$temp[7]];
                            $tRes = $GameBkinfo->field('game_id')->where($map)->find();
                            $tTemp[15] = !empty($tRes['game_id'])?$tRes['game_id']:'';
                            $rf2[] = $tTemp;
                            //$rf2[] = explode('^',$v2);
                        }
                        $RecentFight['content'][1] = $rf2;
                    }
                    $rData[] = $RecentFight;
                }
                #联赛盘路
                if(!empty($arr[6]) || !empty($arr[7]))
                {
                    $cnArr = ['﻿T'=>[0=>'总',1=>'總'],'H'=>[0=>'主场',1=>'主場'],'A'=>[0=>'客场',1=>'客場'],'N'=>[0=>'近10场',1=>'近10場']];
                    $match_panlu = ['name'=>'match_panlu','content'=> [0=>[],1=>[]]];
                    $let_panlu = ['name'=>'let_panlu','content'=> [0=>[],1=>[]]];
                    $total_panlu = ['name'=>'total_panlu','content'=> [0=>[],1=>[]]];
                    if(!empty($arr[6]))
                    {
                        $mp1 = $tp1 = $lp1 = [];
                        $mpTemp1 = explode('!',$arr[6]);
                        foreach($mpTemp1 as $k2=>$v2)
                        {
                            $temp = explode('^',$v2);

                            if($k2 == 3)
                            {
                                $tTemp1 = [
                                    0 => '',
                                    1 => $temp[0],
                                    2 => $temp[1],
                                    3 => $temp[2],
                                    4 => $temp[3],
                                    5 => $temp[4],
                                    6 => $temp[5],
                                ];
                            }
                            else
                            {
                               $tTemp1 = [
                                    0 => '',
                                    1 => '',
                                    2 => $temp[0],
                                    3 => $temp[1],
                                    4 => $temp[2],
                                    5 => $temp[3],
                                    6 => '',
                                ];
                            }
                            if($k2 == 3)
                            {
                                $tTemp2 = [
                                    0 => '',
                                    1 => $temp[6],
                                    2 => $temp[7],
                                    3 => $temp[8],
                                    4 => $temp[9],
                                    5 => $temp[10],
                                    6 => $temp[11],
                                ];
                            }
                            else
                            {
                               $tTemp2 = [
                                    0 => '',
                                    1 => '',
                                    2 => $temp[4],
                                    3 => $temp[5],
                                    4 => $temp[6],
                                    5 => $temp[7],
                                    6 => '',
                                ];
                            }

                            switch ($k2) {
                                case 0:
                                    $tTemp1[0] = $tTemp2[0] = '总';
                                    break;
                                case 1:
                                    $tTemp1[0] = $tTemp2[0] = '主';
                                    break;
                                case 2:
                                    $tTemp1[0] = $tTemp2[0] = '客';
                                    break;
                                case 3:
                                    $tTemp1[0] = $tTemp2[0] = '近6';
                                    break;
                            }
                            $lp1[] = $tTemp1;
                            $tp1[] = $tTemp2;
                            //$mp1[] = explode('^',$v2);
                        }
                        $let_panlu['content'][0] = $lp1;
                        $total_panlu['content'][0] = $tp1;
                        //$match_panlu['content'][0] = $mp1;
                    }
                    if(!empty($arr[7]))
                    {
                        $mp2 = $tp2 = $lp2 =  [];
                        $mpTemp1 = explode('!',$arr[7]);
                        foreach($mpTemp1 as $k2=>$v2)
                        {
                            $temp = explode('^',$v2);
                            if($k2 == 3)
                            {
                                $tTemp1 = [
                                    0 => '',
                                    1 => $temp[0],
                                    2 => $temp[1],
                                    3 => $temp[2],
                                    4 => $temp[3],
                                    5 => $temp[4],
                                    6 => $temp[5],
                                ];
                            }
                            else
                            {
                               $tTemp1 = [
                                    0 => '',
                                    1 => '',
                                    2 => $temp[0],
                                    3 => $temp[1],
                                    4 => $temp[2],
                                    5 => $temp[3],
                                    6 => '',
                                ];
                            }
                            if($k2 == 3)
                            {
                                $tTemp2 = [
                                    0 => '',
                                    1 => $temp[6],
                                    2 => $temp[7],
                                    3 => $temp[8],
                                    4 => $temp[9],
                                    5 => $temp[10],
                                    6 => $temp[11],
                                ];
                            }
                            else
                            {
                               $tTemp2 = [
                                    0 => '',
                                    1 => '',
                                    2 => $temp[4],
                                    3 => $temp[5],
                                    4 => $temp[6],
                                    5 => $temp[7],
                                    6 => '',
                                ];
                            }
                            switch ($k2) {
                                case 0:
                                    $tTemp1[0] = $tTemp2[0] = '总';
                                    break;
                                case 1:
                                    $tTemp1[0] = $tTemp2[0] = '主';
                                    break;
                                case 2:
                                    $tTemp1[0] = $tTemp2[0] = '客';
                                    break;
                                case 3:
                                    $tTemp1[0] = $tTemp2[0] = '近6';
                                    break;
                            }
                            $lp2[] = $tTemp1;
                            $tp2[] = $tTemp2;
                            //$mp2[] = explode('^',$v2);
                        }
                        $let_panlu['content'][1] = $lp2;
                        $total_panlu['content'][1] = $tp2;
                        //$match_panlu['content'][1] = $mp2;
                    }
                    $rData[] = $let_panlu;
                    $rData[] = $total_panlu;
                    //$rData[] = $match_panlu;
                }
                #相同历史盘口
                if(!empty($arr[8]) || !empty($arr[9]))
                {
                    $cnArr = ['﻿T'=>[0=>'总',1=>'總'],'H'=>[0=>'主场',1=>'主場'],'A'=>[0=>'客场',1=>'客場'],'N'=>[0=>'近10场',1=>'近10場']];
                    $same_panlu = ['name'=>'same_panlu','content'=> [0=>[],1=>[]]];
                    if(!empty($arr[8]))
                    {
                        $sp1 = [];
                        $spTemp1 = explode('!',$arr[8]);
                        foreach($spTemp1 as $k2=>$v2)
                        {
                            $sp1[] = explode('^',$v2);
                        }
                        $same_panlu['content'][0] = $sp1;
                    }
                    if(!empty($arr[9]))
                    {
                        $sp2 = [];
                        $spTemp1 = explode('!',$arr[9]);
                        foreach($spTemp1 as $k2=>$v2)
                        {
                            $sp2[] = explode('^',$v2);
                        }
                        $same_panlu['content'][1] = $sp2;
                    }
                    $rData[] = $same_panlu;
                }
                #未来三场
                if(!empty($arr[10]) || !empty($arr[11]))
                {
                    $cnArr = ['﻿T'=>[0=>'总',1=>'總'],'H'=>[0=>'主场',1=>'主場'],'A'=>[0=>'客场',1=>'客場'],'N'=>[0=>'近10场',1=>'近10場']];
                    $match_three = ['name'=>'match_three','content'=> [0=>[],1=>[]]];
                    if(!empty($arr[10]))
                    {
                        $sp1 = [];
                        $spTemp1 = explode('!',$arr[10]);
                        foreach($spTemp1 as $k2=>$v2)
                        {
                            $temp = explode('^',$v2);
                            $tTemp = [
                                0 => $temp[1],
                                1 => date('Y/m/d',strtotime($temp[0])),
                                2 => $temp[2],
                                3 => $temp[3],
                                4 => $temp[4],
                                5 => '',
                                6 => '',
                            ];
                            $sp1[] = $tTemp;
                            //$sp1[] = explode('^',$v2);
                        }
                        $match_three['content'][0] = $sp1;
                    }
                    if(!empty($arr[11]))
                    {
                        $sp2 = [];
                        $spTemp1 = explode('!',$arr[11]);
                        foreach($spTemp1 as $k2=>$v2)
                        {
                            $temp = explode('^',$v2);
                            $tTemp = [
                                0 => $temp[1],
                                1 => date('Y/m/d',strtotime($temp[0])),
                                2 => $temp[2],
                                3 => $temp[3],
                                4 => $temp[4],
                                5 => $temp[5],
                                6 => $temp[6],
                            ];
                            $sp2[] = $tTemp;
                            //$sp2[] = explode('^',$v2);
                        }
                        $match_three['content'][1] = $sp2;
                    }
                    $rData[] = $match_three;
                }
            }
            if(!empty($rData)) file_put_contents($fPath,json_encode($rData));
            return $rData;
        }
    }


    /*
     *  篮球赛况界面数据
     */
    public function bkSituationList($gameId)
    {
        if(empty($gameId)) return false;
        $data=M('BkLive')->where('game_id='.$gameId)->getField('txt_live');//var_dump($data);exit;
        $rData=[];
        if($data){
            $dataArr=  explode('!', $data);
            foreach ($dataArr as $k => &$v) {
                if($v==''){
                    unset($dataArr[$k]);
                }
                $v =  explode('^', $v);
            }
            $rData['live']=$dataArr;
        }
        $GbData=M('GameBkinfo')->field('list_score,ot_score')->where('game_id='.$gameId)->find();

        $GbData['score']=  explode(',', $GbData['list_score']);
        for($i=0;$i<4;$i++){
            if(!isset($GbData['score'][$i])){
                $GbData['score'][$i]='-';
            }
        }

        if($GbData['ot_score']!=NULL || $GbData['ot_score']!=''){
            $homeOt=$awayOt=0;
            $otScore=  explode(',', $GbData['ot_score']);
            foreach ($otScore as $key => $val) {
                $arr=  explode('-', $val);
                //$homeOt+=$arr[0];
                //$awayOt+=$arr[1];
                $GbData['score'][] = $arr[0].'-'.$arr[1];
            }
            //$GbData['score'][4]=$homeOt.'-'.$awayOt;
        }
        foreach ($GbData['score'] as &$kk){
            $kk=  explode('-', $kk);
        }
        $rData['score']=$GbData['score'];
        return $rData;
    }

    /*
     * 篮球技术界面数据
     */
    public function bkTechList($gameId)
    {
        if(empty($gameId)) return false;
        $data=M('BkLive')->where('game_id='.$gameId)->getField('tech');
        $rData=[];
        if($data)
        {
            $dataArr=  explode('$', $data);
            unset($dataArr[0]);
            $dataArr=array_merge($dataArr);
            foreach ($dataArr as &$v){
                $v=  explode('!', $v);
            }

            foreach ($dataArr as $k=>&$vv){
                unset($dataArr[$k][count($vv)-1]);
                foreach ($vv as &$val){
                    $val=  explode('^', $val);
                }
                $rData['total'][$k]=$dataArr[$k][count($vv)-1];
                unset($dataArr[$k][count($vv)-1]);
            }
            if(!empty($dataArr)) $rData['content'] = $dataArr;
        }
        return $rData;
    }

    /*
     * 篮球阵容界面数据
     */
    public function bkSquadList($gameId)
    {
        if(empty($gameId)) return false;
        $data=M('BkLive')->where('game_id='.$gameId)->getField('tech');
        $rData=[];

        if($data)
        {
            $defaultHomeImg = staticDomain('/Public/Home/images/common/bk_ht.png');
            $defaultAwayImg = staticDomain('/Public/Home/images/common/bk_at.png');

            $dataArr=  explode('$', $data);
            unset($dataArr[0]);
            $dataArr=array_merge($dataArr);
            foreach ($dataArr as &$v){
                $v=  explode('!', $v);
            }
            foreach ($dataArr as $k=>&$vv){
                unset($dataArr[$k][count($vv)-1]);
                unset($dataArr[$k][count($vv)-1]);
                foreach ($vv as $key => &$val){
                    $val=  explode('^', $val);
                    $vo=[$val[1],$val[2],$val[3],$val[5]];
                    if($val[5] =='' || $val[5] ==' ')
                    {
                        if($k == 0)
                            $vo[] = $defaultHomeImg;
                        else
                            $vo[] = $defaultAwayImg;
                        $rData[1][$k][]=$vo;
                    }
                    else
                    {
                        if($k == 0)
                            $vo[] = $defaultHomeImg;
                        else
                            $vo[] = $defaultAwayImg;
                        $rData[0][$k][]=$vo;
                    }
                }
            }
        }
        return $rData;
    }

    /*
     *  篮球指数界面数据
     */
    public function bkChoddsList()
    {
        $startTime = time();
        $endTime = $startTime+3600*24;
        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
        $map['b.status'] = 1;
        $map['game_state'] = 0;
        $rsl=M('GameBkinfo')->alias('b')->field('b.union_name,u.union_name u_name,u.union_color,b.union_id,b.game_state,b.game_id,b.gtime,b.home_team_name,b.away_team_name,fsw_exp_home,fsw_exp,fsw_exp_away,fsw_total_home,fsw_total,fsw_total_away,grade')
                ->join('LEFT JOIN qc_bk_union u ON u.union_id=b.union_id')
                ->where($map)->order('gtime')->select();
        //var_dump($rsl);exit;
        $rData=[];
        $gameIds=[];
        if(!empty($rsl))
        {
            foreach ($rsl as $k=>$v)
            {
                if($v['fsw_exp_home']==NULL || $v['fsw_exp']==NULL || $v['fsw_exp_away']==NULL || $v['fsw_total_home']==NULL || $v['fsw_total']==NULL || $v['fsw_total_away']==NULL ) continue;
                $gameIds[]=$v['game_id'];
            }
            $oddsArr = $this->bkOdds($gameIds,3);
            //var_dump($oddsArr);exit;
            foreach ($rsl as $k=>$v)
            {
                if($v['fsw_exp_home']==NULL || $v['fsw_exp']==NULL || $v['fsw_exp_away']==NULL || $v['fsw_total_home']==NULL || $v['fsw_total']==NULL || $v['fsw_total_away']==NULL ){
                    unset($rsl[$k]);
                }
                else
                {
                    $aData[0]=$v['game_id'];
                    $aData[1]=$v['union_id'];
                    if (!empty($v['u_name']))
                        $aData[2] = explode(',', $v['u_name']);
                    else
                        $aData[2] = explode(',', $v['union_name']);
                    $aData[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                    $aData[4] = $v['game_state'];
                    $gameTime = explode('-', date('Ymd-H:i', $v['gtime']));
                    $aData[5] = $gameTime[0];
                    $aData[6] = $gameTime[1];
                    $aData[7] = explode(',',$v['home_team_name']);
                    $aData[8] = explode(',',$v['away_team_name']);
                    $aData[9] = $v['fsw_exp_home'] != null?$v['fsw_exp_home']:'';
                    $aData[10] = $v['fsw_exp'] != null?$v['fsw_exp']:'';
                    $aData[11] = $v['fsw_exp_away'] != null?$v['fsw_exp_away']:'';
                    $aData[12] = $v['fsw_total_home'] != null?$v['fsw_total_home']:'';
                    $aData[13] = $v['fsw_total'] != null?$v['fsw_total']:'';
                    $aData[14] = $v['fsw_total_away'] != null?$v['fsw_total_away']:'';
                    #初盘
                    /*if (isset($oddsArr[$v['game_id']]))
                    {
                        if(empty($oddsArr[$v['game_id']][17]) && empty($oddsArr[$v['game_id']][18]) && empty($oddsArr[$v['game_id']][19]) && empty($oddsArr[$v['game_id']][20])
                            && empty($oddsArr[$v['game_id']][21]) && empty($oddsArr[$v['game_id']][22]))
                        {
                            continue;
                        }
                        $aData[9] = formatExp($oddsArr[$v['game_id']][17]);  //主队亚盘即时赔率
                        $aData[10] = formatExp($oddsArr[$v['game_id']][18]);   //亚盘即时盘口
                        $aData[11] = formatExp($oddsArr[$v['game_id']][19]);   //客队亚盘即时赔率
                        $aData[12] = formatExp($oddsArr[$v['game_id']][20]);  //主队大小即时赔率
                        $aData[13] = formatExp($oddsArr[$v['game_id']][21]);   //大小即时盘口
                        $aData[14] = formatExp($oddsArr[$v['game_id']][22]);   //客队大小即时赔率
                    }
                    else
                    {
                        $aData[9] = '';
                        $aData[10] = '';
                        $aData[11] = '';
                        $aData[12] = '';
                        $aData[13] = '';
                        $aData[14] = '';
                    }*/
                    #即时
                    if (isset($oddsArr[$v['game_id']]))
                    {
                        $aData[15] = formatExp($oddsArr[$v['game_id']][0]);  //主队亚盘即时赔率
                        $aData[16] = formatExp($oddsArr[$v['game_id']][1]);   //亚盘即时盘口
                        $aData[17] = formatExp($oddsArr[$v['game_id']][2]);   //客队亚盘即时赔率
                        $aData[18] = formatExp($oddsArr[$v['game_id']][3]);  //主队大小即时赔率
                        $aData[19] = formatExp($oddsArr[$v['game_id']][4]);   //大小即时盘口
                        $aData[20] = formatExp($oddsArr[$v['game_id']][5]);   //客队大小即时赔率
                    }
                    else
                    {
                        $aData[15] = '';
                        $aData[16] = '';
                        $aData[17] = '';
                        $aData[18] = '';
                        $aData[19] = '';
                        $aData[20] = '';
                    }
                    $aData[21] = $v['grade'] !== null?$v['grade']:'';
                    $rData[]=$aData;
                }
            }
        }
        //exit;
        return $rData;
    }

    /**
     * 获取球队logo地址
     * @param  string $gameId 赛事ID
     * @return array          球队logo地址
     */
    public function getTeamLogo($gameId,$is_show=false)
    {
        if(empty($gameId)) return false;
        $mongo = mongoService();

        //mongo赛事数据
        $baseRes = $mongo->fetchRow('bk_game_schedule',['game_id'=>(int)$gameId],['game_id','game_status','game_info','union_id','union_name','home_team_name','away_team_name','home_team_id','away_team_id','game_timestamp']);
        $baseRes['gtime'] = $baseRes['game_timestamp'];
        
        //mysql业务字段
        $GameBkinfo = M('GameBkinfo')->field('id,game_id,is_video')->where(['game_id'=>$gameId])->find();

        $aData = [];
        if(!empty($baseRes))
        {
            $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
            $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

            if (iosCheck()) //ios审核设定为默认球队logo
            {
                $homeTeamImg = $defaultHomeImg;
                $awayTeamImg = $defaultAwayImg;
            }
            else
            {
                $homeTeamImg = getLogoTeam($baseRes['home_team_id'],1,2);
                $awayTeamImg = getLogoTeam($baseRes['away_team_id'],2,2);
            }

            $aData[0] = $homeTeamImg;
            $aData[1] = $awayTeamImg;
            $aData[2] = (string) $baseRes['game_status'];
            $game_info = $baseRes['game_info'];
            $score = (!empty($game_info[3]) || !empty($game_info[4])) ? $game_info[3] . '-' .$game_info[4] : '';
            $aData[3] = (string) $score;
            $aData[4] = date('Ymd',$baseRes['gtime']);
            $aData[5] = date('H:i',$baseRes['gtime']);
            $aData[6] = $baseRes['union_name'];
            $aData[7] = $baseRes['home_team_name'];
            $aData[8] = $baseRes['away_team_name'];
            if($baseRes['game_status'] == -1)
            {
                $aData[9] = '0';
            }
            else
            {
                $aData[9] = !empty($GameBkinfo['is_video']) ? (string)$GameBkinfo['is_video'] : '0';
            }

            #是否flash
            $bmap['game_id']  = $gameId;
            $bmap['flash_id']  = array('exp',' is not NULL');
            $betRes = M('BkLinkbet')->field('game_id,is_link,flash_id,md_id')->where($bmap)->order('update_time desc')->select();

            if(empty($betRes) || $baseRes['game_status'] == -1)
            {
                $aData[10] = '0';
            }
            else
            {
                if(in_array($baseRes['game_status'],[1,2,3,4,5,6,7,50]))
                {
                    if(!empty($betRes[0]['md_id']))
                        $aData[10] = '1';
                    else
                        $aData[10] = '0';
                }
                else
                {
                    $aData[10] = '1';
                }
            }

            $aData[11] = (string)$baseRes['gtime'];

            //关注(userToken,设备标识deviceID，赛事ID gameId)
            $redis  = connRedis();
            $uinfo  = getUserToken(I('userToken'),true);
            if(isset($uinfo['userid'])){
                $key = $uinfo['userid'];
            }elseif(I('deviceID') !=''){
                $key = I('deviceID');
            }elseif(I('pushID') != ''){
                $key = I('pushID');
            }

            $_preKey = 'push_bk_user_follow_' . I('gameType') . ':';
            $is_follow = $redis->sIsMember($_preKey . $key, I('gameId'));

            $_preKey2 = 'push_bk_user_follow:';
            $is_follow2 = $redis->sIsMember($_preKey2 . $key, I('gameId'));

            $_preKey3 = I('gameType') == 1 ? 'push_apns_user_fb_follow:' . $key : 'push_apns_user_bk_follow:' . $key;
            $is_follow3 = $redis->sIsMember($_preKey3, I('gameId'));
            $aData[12] = $is_follow === true || $is_follow2 === true || $is_follow3 == true ? '1': '0';//1关注，0未关注

            $aData[13] = (string)$baseRes['union_id'];
            $aData[14] = (string)$baseRes['home_team_id'];
            $aData[15] = (string)$baseRes['away_team_id'];
            //4小节比分
            $score1 = (!empty($game_info[5])  || !empty($game_info[6]))  ? $game_info[5]  . '-' .$game_info[6]  : '';
            $score2 = (!empty($game_info[7])  || !empty($game_info[8]))  ? ','.$game_info[7]  . '-' .$game_info[8]  : '';
            $score3 = (!empty($game_info[9])  || !empty($game_info[10])) ? ','.$game_info[9]  . '-' .$game_info[10] : '';
            $score4 = (!empty($game_info[11]) || !empty($game_info[12])) ? ','.$game_info[11] . '-' .$game_info[12] : '';
            $aData[16] = $score1.$score2.$score3.$score4;

            //api500以上数据
            if(explode('Api', MODULE_NAME)[1] >= 500){
                //是否有情报
                $articleList = D('Home')->getGameArticleList($gameId,2);
                $aData[17] = empty($articleList['articleList']) && empty($articleList['preInfo']) ? '0' : '1';
                //是否有推荐
                $ypOdds = D('GambleHall')->getGambleOddsBk($gameId);
                $aData[18] = $ypOdds['data']['odds_check'] == 1 ? '1' : '0';
            }
        }

        return $aData;
    }

    /**
     * 获取足球Goal数据
     * @return json
     */
    public function getGoal($companyID) {
        $fileName = DataPath . 'other/goal' . $companyID . '.txt';

        $content = '';

        if (is_file($fileName)) {
            $content = file_get_contents($fileName);
        }
        return $content;
    }

    /**
     +------------------------------------------------------------------------------
     * 以下开始为app篮球3.0
     +------------------------------------------------------------------------------
    */

     /**
     * 根据赛事ID获取动画 qc_bk_cartoonbet表
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
        $res1 = M('GameBkinfo')->field('*,qc_game_bkinfo.union_name as uname,b.img_url as home_img_url,c.img_url as away_img_url,u.union_color')->join('
        LEFT JOIN qc_game_teambk b ON qc_game_bkinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_teambk c ON qc_game_bkinfo.away_team_id = c.team_id
        LEFT JOIN qc_bk_union u ON qc_game_bkinfo.union_id = u.union_id')->where($map)->find();

        if(empty($res1)) return null;
        //var_dump($res1);exit;
        $res2 = M('BkLinkbet') ->field('*')->where($map)->find();
        //if(empty($res2)) return null;

        $rData = [];

        $httpUrl = C('IMG_SERVER');
        $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
        $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

        $homeTeamImg = !empty($res1['home_img_url']) ? $httpUrl.$res1['home_img_url'] : $defaultHomeImg;
        $awayTeamImg = !empty($res1['away_img_url']) ? $httpUrl.$res1['away_img_url'] : $defaultAwayImg;

        $htn = explode(',',$res1['home_team_name']);
        $atn = explode(',',$res1['away_team_name']);
        $utn = explode(',',$res1['uname']);

        $score = explode('-',$res1['score']);
        $game_detail = [
            'game_id' => $res1['game_id'],
            'gtime' => $res1['gtime'],
            'game_half_time' =>  $res1['game_half_time'],
            'game_date' => date('Ymd H:i:s',$res1['gtime']),
            'home_score' => $score[0],
            'away_score' => isset($score[1])?$score[1]:'',
            'is_ot' => $res1['is_ot'],
            'union_name' => $utn[$langs],
            'home_team_name' => $htn[$langs],
            'away_team_name' => $atn[$langs],
            'home_img_url' => $homeTeamImg,
            'away_img_url' => $awayTeamImg,
            'game_state' => $res1['game_state'],
            'status' => $res2['status'],
            'data_flag' => '1',
            'union_color' => !empty($res1['union_color'])?$res1['union_color']:'#aeba4b',
        ];

        #小节比分
        $list_score = explode(',', $res1['list_score']);
        if (!empty($list_score[0])) {
            $list_score1 = explode('-', $list_score[0]);
            $game_detail['h_score1'] = $list_score1[0];
            $game_detail['a_score1'] = isset($list_score1[1]) ? $list_score1[1] : '';
        } else {
            $game_detail['h_score1'] = '';
            $game_detail['a_score1'] = '';
        }
        if (!empty($list_score[1])) {
            $list_score2 = explode('-', $list_score[1]);
            $game_detail['h_score2'] = $list_score2[0];
            $game_detail['a_score2'] = isset($list_score2[1]) ? $list_score2[1] : '';
        } else {
            $game_detail['h_score2'] = '';
            $game_detail['a_score2'] = '';
        }
        if (!empty($list_score[2])) {
            $list_score3 = explode('-', $list_score[2]);
            $game_detail['h_score3'] = $list_score3[0];
            $game_detail['a_score3'] = isset($list_score3[1]) ? $list_score3[1] : '';
        } else {
            $game_detail['h_score3'] = '';
            $game_detail['a_score3'] = '';
        }
        if (!empty($list_score[3])) {
            $list_score4 = explode('-', $list_score[3]);
            $game_detail['h_score4'] = $list_score4[0];
            $game_detail['a_score4'] = isset($list_score4[1]) ? $list_score4[1] : '';
        } else {
            $game_detail['h_score4'] = '';
            $game_detail['h_score4'] = '';
        }
        if($v['is_ot'] !== 0 || !empty($v['is_ot']))
        {
            $oScore1 = $oScore2 = '';
            $list = explode(',',$res1['ot_score']);

            foreach($list as $k2=>$v2)
            {
                $tScore = explode('-',$v2);
                if($oScore1 == '')
                {
                    $oScore1 .= $tScore[0];
                    $oScore2 .= $tScore[1];
                }
                else
                {
                    $oScore1 .= '-'.$tScore[0];
                    $oScore2 .= '-'.$tScore[1];
                }
            }
            $game_detail['h_otscore'] = (string)$oScore1;
            $game_detail['a_otscore'] = (string)$oScore2;
        }
        else
        {
            $game_detail['h_otscore'] = '';
            $game_detail['a_otscore'] = '';
        }

        $rData['game_detail'] = $game_detail;
        $runtime_detail = [];

        if($res2['status'] == 'end' || $res1['game_state'] == -1)
        {
            $rData['runtime_detail'] = [];
            return $rData;
        }

        $uptimeRes = M('BkCartoonbet')->field('update_time')->where(['flash_id'=>$res2['flash_id']])->order('id desc')->limit(1)->find();
        //echo M()->getlastsql();
        //var_dump($uptimeRes);exit;
        if(strlen($last_game_time) >10) $last_game_time = substr($last_game_time,0,10);

        #数据2min不更新
        if(!empty($uptimeRes) && $uptimeRes['update_time'] < (time()-120) && $game_detail['game_state'] !=50)
        {
            $rData['game_detail']['data_flag'] = '0';
            $rData['runtime_detail'] = [];
            return $rData;
        }

        if(!empty($uptimeRes) && $uptimeRes['update_time'] > $last_game_time)
        {
            $map2['flash_id'] = $res2['flash_id'];
            $map2['update_time'] = array('EGT',$uptimeRes['update_time']);
            $rtRes = M('BkCartoonbet') ->field('update_time,status_code,xy,pg,game_desc,is_home,other')->where($map2)->select();

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
        $res1 = M('GameBkinfo')->field('*,qc_game_bkinfo.union_name as uname,b.img_url as home_img_url,c.img_url as away_img_url,u.union_color')->join('
        LEFT JOIN qc_game_teambk b ON qc_game_bkinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_teambk c ON qc_game_bkinfo.away_team_id = c.team_id
        LEFT JOIN qc_bk_union u ON qc_game_bkinfo.union_id = u.union_id')->where($map)->find();
        if(empty($res1)) return null;
        //var_dump($res1);exit;
        $res2 = M('BkLinkbet') ->field('*')->where($map)->find();
        if(empty($res2)) return null;

        $rData = [];

        $httpUrl = C('IMG_SERVER');
        $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
        $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

        $homeTeamImg = !empty($res1['home_img_url']) ? $httpUrl.$res1['home_img_url'] : $defaultHomeImg;
        $awayTeamImg = !empty($res1['away_img_url']) ? $httpUrl.$res1['away_img_url'] : $defaultAwayImg;

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
            'union_color' => !empty($res1['union_color'])?$res1['union_color']:'#aeba4b',
        ];

        #小节比分
        $list_score = explode(',', $res1['list_score']);
        if (!empty($list_score[0])) {
            $list_score1 = explode('-', $list_score[0]);
            $game_detail['h_score1'] = $list_score1[0];
            $game_detail['a_score1'] = isset($list_score1[1]) ? $list_score1[1] : '';
        } else {
            $game_detail['h_score1'] = '';
            $game_detail['a_score1'] = '';
        }
        if (!empty($list_score[1])) {
            $list_score2 = explode('-', $list_score[1]);
            $game_detail['h_score2'] = $list_score2[0];
            $game_detail['a_score2'] = isset($list_score2[1]) ? $list_score2[1] : '';
        } else {
            $game_detail['h_score2'] = '';
            $game_detail['a_score2'] = '';
        }
        if (!empty($list_score[2])) {
            $list_score3 = explode('-', $list_score[2]);
            $game_detail['h_score3'] = $list_score3[0];
            $game_detail['a_score3'] = isset($list_score3[1]) ? $list_score3[1] : '';
        } else {
            $game_detail['h_score3'] = '';
            $game_detail['a_score3'] = '';
        }
        if (!empty($list_score[3])) {
            $list_score4 = explode('-', $list_score[3]);
            $game_detail['h_score4'] = $list_score4[0];
            $game_detail['a_score4'] = isset($list_score4[1]) ? $list_score4[1] : '';
        } else {
            $game_detail['h_score4'] = '';
            $game_detail['h_score4'] = '';
        }
        if($v['is_ot'] !== 0 || !empty($v['is_ot']))
        {

            $oScore1 = $oScore2 = '';
            $list = explode(',',$v['ot_score']);
            foreach($list as $k2=>$v2)
            {
                $tScore = explode('-',$v2);
                if($oScore1 == '')
                {
                    $oScore1 .= $tScore[0];
                    $oScore2 .= $tScore[1];
                }
                else
                {
                    $oScore1 .= '-'.$tScore[0];
                    $oScore2 .= '-'.$tScore[1];
                }
            }
            $game_detail['h_otscore'] = (string)$oScore1;
            $game_detail['a_otscore'] = (string)$oScore2;
        }
        else
        {
            $game_detail['h_otscore'] = '';
            $game_detail['a_otscore'] = '';
        }

        $runtime_detail = [];

        if($res2['status'] == 'end' || $res1['game_state'] == -1)
        {
            //var_dump($res2);exit;
            $allRdRes = M('BkCartoonbet') ->field('update_time,status_code,xy,pg,game_desc,is_home,other')->where(['flash_id'=>$res2['flash_id']])->select();
            //var_dump($allRdRes);exit;
            if(!empty($allRdRes))
            {
                $cArr =   [];
                $overFlag = false;
                foreach($allRdRes as $k=>$v)
                {
                    unset($v['str_txt']);
                    $v['update_time'] = $v['update_time'].'000';
                    //if(array_search($v['status_code'],$cArr) === false) continue;
                    $runtime_detail[] = $v;
                    $rData[] = $v;
                }
                $aSort = [];
                foreach($rData as $k=>$v)
                {
                    $aSort[$k] = $v['update_time'];
                }
                array_multisort($aSort, SORT_ASC, $rData);

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
     * 根据球队ID获取球队信息
     * @param  int   $teamId  球队ID
     * @return array  球队数据
     */
    public function getTeamData($teamId,$unionId)
    {
        if(empty($teamId)) return false;

        $map['union_id'] = $unionId;
        $map['status'] = 1;
        //$map['gtime'] = array('gt',strtotime(date('Y'),time()));
        $map['_string'] = 'home_team_id ='.$teamId.' OR away_team_id ='.$teamId;

        $res = M('GameBkinfo')->field('game_id,union_name,home_team_id,home_team_name,away_team_id,away_team_name,score,list_score,gtime,game_state,fsw_exp,fsw_total,home_team_rank,away_team_rank')->where($map)->order('gtime desc')->limit(50)->select();

        $rData = [];
        $tInfo = $this->getTeamInfo($teamId);
        $rData['team_info'] = $tInfo;

        if(!empty($res))
        {
            $FbdataService = new \Common\Services\BkdataService();
            $gameInfo = [];
            $time = time();
            foreach ($res as $k => $v)
            {
                if($v['gtime'] < ($time -3600*24) && $v['game_state'] == 0) continue;
                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['game_state'];
                $val[2] = !empty($v['gtime']) ? date('Ymd-H:i', $v['gtime']) : '';
                $val[3] = $unionId;
                $val[4] = explode(',', $v['union_name']);
                $val[5] = explode(',', $v['home_team_name']);
                $val[6] = explode(',', $v['away_team_name']);
                $home_rank = !empty($v['home_team_rank']) ? pregUnionRank($v['home_team_rank']) : '';
                $away_rank = !empty($v['away_team_rank']) ? pregUnionRank($v['away_team_rank']) : '';
                $val[7] = $home_rank !== false ? $home_rank : '';
                $val[8] = $away_rank !== false ? $away_rank : '';
                $val[9] = ($v['score'] == '-' || empty($v['score'])) ? '' : $v['score'];
                $val[10] = ($v['list_score'] == '-' || empty($v['list_score'])) ? '' : $v['list_score'];
                $val[11] = $v['fsw_exp'] == null ? '' : $v['fsw_exp'];
                $val[12] = $v['fsw_total'] == null ? '' : $v['fsw_total'];
                if ($v['game_state'] == -1) {
                    $win = $ePanlu = $bPanlu = '';
                    if ($v['home_team_id'] == $teamId) {
                        $win = $FbdataService->winLost($v['score'], 1);
                        $ePanlu = $FbdataService->panluWin($v['fsw_exp'], $v['score'], 1);
                    } else {
                        $win = $FbdataService->winLost($v['score'], 2);
                        $ePanlu = $FbdataService->panluWin($v['fsw_exp'], $v['score'], 2);
                    }
                    if ($v['fsw_total'] !== null && $v['fsw_total'] !== '') {
                        $score = explode('-', $v['score']);
                        if ($score[0] + $score[1] > $v['fsw_total']) {
                            $bPanlu = 1;
                        } else if ($score[0] + $score[1] < $v['fsw_total']) {
                            $bPanlu = -1;
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
    public function getTeamInfo($teamId)
    {
        if(empty($teamId)) return false;
        $rData = M('GameTeambk')->field('team_id,team_name,country_id,country,stadium_name,people,formed,url,img_url,team_intro,union_id,union_name,champion')->where(['team_id'=>$teamId,'status'=>1])->find();

        if(empty($rData)) return null;
        $rData['team_name'] = explode(',',$rData['team_name']);
        $rData['stadium_name'] = explode(',',$rData['stadium_name']);
        $rData['union_name'] = explode(',',$rData['union_name']);
        $rData['country_id'] = $rData['country_id']==null?'':$rData['country_id'];
        $rData['country'] = empty($rData['country'])?array():explode(',',$rData['country']);
        $rData['people'] = $rData['people']==null?'-':$rData['people'];
        $rData['formed'] = $rData['formed']==null?'-':$rData['formed'];
        $rData['champion'] = $rData['champion']==null?'-':$rData['champion'];
        $rData['url'] = $rData['url']==null?'-':$rData['url'];
        $httpUrl = C('IMG_SERVER');
        $defaultImg = staticDomain('/Public/Home/images/common/team_def.png');
        $teamImg = !empty($rData['img_url']) ? $httpUrl.$rData['img_url'] : $defaultImg;
        $rData['img_url'] = $teamImg;
        $rData['team_intro'] = $rData['team_intro']==null?'':$rData['team_intro'];
        return $rData;
    }



    /**
      +------------------------------------------------------------------------------
     * 以下为功能函数
      +------------------------------------------------------------------------------
     */

    /**
     * 根据赛事ID获取最新赔率数据 qc_bk_goal表
     * @param  array   $gameIds  赛事ID
     * @param  int     $companyID  公司ID
     * @return array  当日即时赛事数据
     */
    public function bkOdds($gameIds, $companyID = 3)
    {
        if (empty($gameIds) || !is_array($gameIds)) return false;

        $map['game_id'] = array('in', implode(',', $gameIds));
        $map['company_id'] = $companyID;

        $obj = M('BkGoal');
        $res = $obj->field('game_id,exp_value')->where($map)->select();

        $rData = [];
        if (!empty($res))
        {
            foreach ($res as $k => $v)
            {
                $temp = [];
                $oTemp = oddsChArrBk($v['exp_value']);
                #让分
                if (!empty($oTemp[0][6]) || !empty($oTemp[0][7]) || !empty($oTemp[0][8])) {
                    $temp[0] = formatExp($oTemp[0][6]);
                    $temp[1] = formatExp($oTemp[0][7]);
                    $temp[2] = formatExp($oTemp[0][8]);
                } else if (!empty($oTemp[0][3]) || !empty($oTemp[0][4]) || !empty($oTemp[0][5])) {
                    $temp[0] = formatExp($oTemp[0][3]);
                    $temp[1] = formatExp($oTemp[0][4]);
                    $temp[2] = formatExp($oTemp[0][5]);
                } else {
                    $temp[0] = formatExp($oTemp[0][0]);
                    $temp[1] = formatExp($oTemp[0][1]);
                    $temp[2] = formatExp($oTemp[0][2]);
                }
                #总分
                if (!empty($oTemp[1][6]) || !empty($oTemp[1][7]) || !empty($oTemp[1][8])) {
                    $temp[3] = formatExp($oTemp[1][6]);
                    $temp[4] = formatExp($oTemp[1][7]);
                    $temp[5] = formatExp($oTemp[1][8]);
                } else if (!empty($oTemp[1][3]) || !empty($oTemp[1][4]) || !empty($oTemp[1][5])) {
                    $temp[3] = formatExp($oTemp[1][3]);
                    $temp[4] = formatExp($oTemp[1][4]);
                    $temp[5] = formatExp($oTemp[1][5]);
                } else {
                    $temp[3] = formatExp($oTemp[1][0]);
                    $temp[4] = formatExp($oTemp[1][1]);
                    $temp[5] = formatExp($oTemp[1][2]);
                }
                #欧赔
                if (!empty($oTemp[2][4]) || !empty($oTemp[2][5])) {
                    $temp[6] = formatExp($oTemp[2][4]);
                    $temp[7] = formatExp($oTemp[2][5]);
                } else if (!empty($oTemp[2][2]) || !empty($oTemp[2][3])) {
                    $temp[6] = formatExp($oTemp[2][2]);
                    $temp[7] = formatExp($oTemp[2][3]);
                } else {
                    $temp[6] = formatExp($oTemp[2][0]);
                    $temp[7] = formatExp($oTemp[2][1]);
                }

                #半场
                if (!empty($oTemp[3][6]) || !empty($oTemp[3][7]) || !empty($oTemp[3][8])) {
                    $temp[9] = formatExp($oTemp[3][6]);
                    $temp[10] = formatExp($oTemp[3][7]);
                    $temp[11] = formatExp($oTemp[3][8]);
                } else if (!empty($oTemp[3][3]) || !empty($oTemp[3][4]) || !empty($oTemp[3][5])) {
                    $temp[9] = formatExp($oTemp[3][3]);
                    $temp[10] = formatExp($oTemp[3][4]);
                    $temp[11] = formatExp($oTemp[3][5]);
                } else {
                    $temp[9] = formatExp($oTemp[3][0]);
                    $temp[10] = formatExp($oTemp[3][1]);
                    $temp[11] = formatExp($oTemp[3][2]);
                }

                if (!empty($oTemp[4][6]) || !empty($oTemp[4][7]) || !empty($oTemp[4][8])) {
                    $temp[12] = formatExp($oTemp[4][6]);
                    $temp[13] = formatExp($oTemp[4][7]);
                    $temp[14] = formatExp($oTemp[4][8]);
                } else if (!empty($oTemp[4][3]) || !empty($oTemp[4][4]) || !empty($oTemp[4][5])) {
                    $temp[12] = formatExp($oTemp[4][3]);
                    $temp[13] = formatExp($oTemp[4][4]);
                    $temp[14] = formatExp($oTemp[4][5]);
                } else {
                    $temp[12] = formatExp($oTemp[4][0]);
                    $temp[13] = formatExp($oTemp[4][1]);
                    $temp[14] = formatExp($oTemp[4][2]);
                }

                if (!empty($oTemp[5][4]) || !empty($oTemp[5][5])) {
                    $temp[15] = formatExp($oTemp[5][4]);
                    $temp[16] = formatExp($oTemp[5][5]);
                } else if (!empty($oTemp[5][2]) || !empty($oTemp[5][3])) {
                    $temp[15] = formatExp($oTemp[5][2]);
                    $temp[16] = formatExp($oTemp[5][3]);
                } else {
                    $temp[15] = formatExp($oTemp[5][0]);
                    $temp[16] = formatExp($oTemp[5][1]);
                }

                #全场初盘
                if (!empty($oTemp[0][0]) || !empty($oTemp[0][1]) || !empty($oTemp[0][2])) {
                    $temp[17] = formatExp($oTemp[0][0]);
                    $temp[18] = formatExp($oTemp[0][1]);
                    $temp[19] = formatExp($oTemp[0][2]);
                }  else {
                    $temp[17] = '';
                    $temp[18] = '';
                    $temp[19] = '';
                }
                #总分
                if (!empty($oTemp[1][0]) || !empty($oTemp[1][1]) || !empty($oTemp[1][2])) {
                    $temp[20] = formatExp($oTemp[1][0]);
                    $temp[21] = formatExp($oTemp[1][1]);
                    $temp[22] = formatExp($oTemp[1][2]);
                }  else {
                    $temp[20] = '';
                    $temp[21] = '';
                    $temp[22] = '';
                }
                #欧赔
                if (!empty($oTemp[2][0]) || !empty($oTemp[2][1])) {
                    $temp[23] = formatExp($oTemp[2][0]);
                    $temp[24] = formatExp($oTemp[2][1]);
                }  else {
                    $temp[23] = '';
                    $temp[24] = '';
                }

                #半场初盘
                if (!empty($oTemp[3][0]) || !empty($oTemp[3][1]) || !empty($oTemp[3][2])) {
                    $temp[25] = formatExp($oTemp[3][0]);
                    $temp[26] = formatExp($oTemp[3][1]);
                    $temp[27] = formatExp($oTemp[3][2]);
                }  else {
                    $temp[25] = '';
                    $temp[26] = '';
                    $temp[27] = '';
                }
                #总分
                if (!empty($oTemp[4][0]) || !empty($oTemp[4][1]) || !empty($oTemp[4][2])) {
                    $temp[28] = formatExp($oTemp[4][0]);
                    $temp[29] = formatExp($oTemp[4][1]);
                    $temp[30] = formatExp($oTemp[4][2]);
                }  else {
                    $temp[28] = '';
                    $temp[29] = '';
                    $temp[30] = '';
                }
                #欧赔
                if (!empty($oTemp[5][0]) || !empty($oTemp[5][1])) {
                    $temp[31] = formatExp($oTemp[5][0]);
                    $temp[32] = formatExp($oTemp[5][1]);
                }  else {
                    $temp[31] = '';
                    $temp[32] = '';
                }
                $rData[$v['game_id']] = $temp;
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
    public function bkOddsIns($gameIds)
    {
        if(empty($gameIds) || !is_array($gameIds)) return false;

        $map['game_id'] = array('in',implode(',',$gameIds));

        $obj = M('BkGoal');
        $res = $obj->field('game_id,company_id,exp_value')->where($map)->select();

        $rData = [];
        $oddsCompany = C('DB_BK_COMPANY_ODDS');
        if(!empty($res))
        {
            $aOdds = [];
            $oOdds = [];
            $dOdds = [];
            foreach($res as $k=>$v)
            {
                $oddsTemp = oddsChArrBk($v['exp_value']);
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
     * [getDataList 获取接口数据]
     * @return void
     */
    public function getDataList() {
        $this->data = include 'interfaceArr.php';
    }

}
