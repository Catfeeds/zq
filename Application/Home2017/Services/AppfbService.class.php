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
     * 当日即时赛事
     * @param  string   $content  源数据
     * @return array  当日即时赛事数据
     */
    public function fbtodayList($unionId,$subId ='',$platform = '')
    {
        $GameFbinfo = M('GameFbinfo');

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
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);
        if(!empty($subId)) $map['is_sub'] = array('in',$subId);

        $baseRes = $GameFbinfo->alias('a')->field('a.id,game_id,a.union_id,a.union_name as union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,home_team_id,away_team_id,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,is_video,is_flash,is_betting,bet_code,u.union_name as u_name')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,bet_code,is_sub,a.id')->select();

        $rData = $fbFlash = [];
        if(!empty($baseRes))
        {
            $gids = $teamIds = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
                $teamIds[$v['home_team_id']] = $v['home_team_id'];
                $teamIds[$v['away_team_id']] = $v['away_team_id'];
            }
            $oddsArr = $this->fbOdds($gids);

            if($platform == 'robot')
            {
                $teamArr = [];
                $tRes = M('GameTeam')->field('team_id,short_team_name')->where(['team_id'=>['in',implode(',',$teamIds)]])->select();
                if(!empty($tRes))
                {
                    foreach($tRes as $k=> $v)
                    {
                        $teamArr[$v['team_id']] = $v['short_team_name'];
                    }
                }
            }

            $map2['game_id'] = array('in',implode(',',$gids));
            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where($map2)->select();

            $linksArr = $mdArr = [];
            if(!empty($betRes))
            {
                foreach($betRes as $k=> $v)
                {
                    $linksArr[$v['game_id']] = $v['is_link'];
                    if(!empty($v['flash_id']) && !empty($v['md_id']))
                    {
                        $mdArr[$v['game_id']] = $v['md_id'];
                    }
                }
            }

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
                if($v['gtime'] + 60 < time() && $v['game_state'] == 0) continue;          //过了开场时间未开始
                if($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) continue;      //140分钟还没结束

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']))
                    $val[2] = explode(',',$v['union_name']);
                else
                    $val[2] = explode(',',$v['u_name']);
                $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[4] = $v['is_sub'] !== null?$v['is_sub']:'';
                $val[5] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[6] = $gameTime[0];
                $val[7] = $gameTime[1];
                $tempTime = explode(',',$v['game_half_time']);
                $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                $val[8] = implode('',$tempTime);
                $val[9] = explode(',',$v['home_team_name']);
                $val[10] = explode(',',$v['away_team_name']);
                $home_rank = !empty($v['home_team_rank'])?pregUnionRank($v['home_team_rank']):'';
                $away_rank = !empty($v['away_team_rank'])?pregUnionRank($v['away_team_rank']):'';
                $val[11] = $home_rank !== false?$home_rank:'';
                $val[12] = $away_rank !== false?$away_rank:'';
                $score = explode('-',$v['score']);
                $val[13] = $score[0];
                $val[14] = isset($score[1])?$score[1]:'';
                $half_score = explode('-',$v['half_score']);
                $val[15] = $half_score[0];
                $val[16] = isset($half_score[1])?$half_score[1]:'';
                #全场亚盘大小即时赔率
                if(isset($oddsArr[$v['game_id']]))
                {
                    $val[17] = $oddsArr[$v['game_id']][0];  //主队亚盘即时赔率
                    $val[18] = changeExp($oddsArr[$v['game_id']][1]);   //亚盘即时盘口
                    $val[19] = $oddsArr[$v['game_id']][2];   //客队亚盘即时赔率
                    $val[20] = $oddsArr[$v['game_id']][6];  //主队大小即时赔率
                    $val[21] = changeExp($oddsArr[$v['game_id']][7]);   //大小即时盘口
                    $val[22] = $oddsArr[$v['game_id']][8];   //客队大小即时赔率
                }
                else
                {
                    $val[17] = '';
                    $val[18] = '';
                    $val[19] = '';
                    $val[20] = '';
                    $val[21] = '';
                    $val[22] = '';
                }

                #红黄牌
                if(!empty($v['red_card']))
                {
                    $red = explode('-',$v['red_card']);
                    $val[23] = $red[0];
                    $val[24] = $red[1];
                }
                else
                {
                    $val[23] = '0';
                    $val[24] = '0';
                }
                if(!empty($v['yellow_card']))
                {
                    $yellow = explode('-',$v['yellow_card']);
                    $val[25] = $yellow[0];
                    $val[26] = $yellow[1];
                }
                else
                {
                    $val[25] = '0';
                    $val[26] = '0';
                }
                #角球
                if(!empty($v['corner']))
                {
                    $corner = explode('-',$v['corner']);
                    $val[27] = $corner[0];
                    $val[28] = $corner[1];
                }
                else
                {
                    $val[27] = '0';
                    $val[28] = '0';
                }
                if($v['game_state'] !=-1)
                    $val[29] = $v['is_video'];
                else
                    $val[29] = '';
                if(!in_array(MODULE_NAME,['Api200','Api201','Api202']))
                {
                    #初盘
                    $val[30] = $v['fsw_exp_home']===null?'':$v['fsw_exp_home'];  //主队亚盘初盘赔率
                    $val[31] = changeExp($v['fsw_exp']);   //亚盘初盘盘口
                    $val[32] = $v['fsw_exp_away'] === null?'':$v['fsw_exp_away'];   //客队亚盘初盘赔率
                    $val[33] = $v['fsw_ball_home']===null?'':$v['fsw_ball_home'];  //主队大小初盘赔率
                    $val[34] = changeExp($v['fsw_ball']);   //大小初盘盘口
                    $val[35] = $v['fsw_ball_away']===null?'':$v['fsw_ball_away'];   //客队大小初盘赔率
                    if(isset($linksArr[$v['game_id']]) && $v['game_state'] !=-1)
                    {
                        if(in_array($v['game_state'],[1,2,3,4]))
                        {
                            if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                                $val[36] = '1';
                            else
                                $val[36] = '0';
                        }
                        else
                        {
                            $val[36] = '1';
                        }
                    }
                    else
                    {
                        $val[36] = '0';
                    }
                    //$val[36] = $v['is_flash'];
                    $val[37] = $v['is_betting'];
                    $val[38] = empty($v['bet_code'])?'':$v['bet_code'];
                }

                if(!in_array(MODULE_NAME,['Api','Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400']))
                {
                    $val[39] = $v['is_video'];
                    if(isset($linksArr[$v['game_id']]))
                    {
                        if(in_array($v['game_state'],[1,2,3,4,-1]))
                        {
                            if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                                $val[40] = '1';
                            else
                                $val[40] = '0';
                        }
                        else
                        {
                            $val[40] = '1';
                        }
                    }
                    else
                    {
                        $val[40] = '0';
                    }
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
                    }

                }
                $rData[] = $val;
            }
        }
        return $rData;
    }

    /**
     * 当日滚球赛事
     * @return array 滚球赛事数组
     */
    public function fbRollList($unionId,$subId='')
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

        $GameFbinfo = M('GameFbinfo');

        $baseRes = $GameFbinfo->alias('a')->field('game_id,a.id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,show_date,union_color,is_sub,u.union_name as u_name,is_video,is_flash')->join('qc_union u ON a.union_id = u.union_id','LEFT')->where($map)->order('game_state desc,gtime,is_sub,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
            }
            $oddsArr = $this->fbOdds($gids);

            $map2['game_id'] = array('in',implode(',',$gids));
            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id')->where($map2)->select();

            $linksArr = [];
            if(!empty($betRes))
            {
                foreach($betRes as $k=> $v)
                {
                    $linksArr[$v['game_id']] = $v['is_link'];
                }
            }


            foreach($baseRes as $k=> $v)
            {
                if(stripos($v['home_team_name'],'测试') !== false || strpos($v['away_team_name'],'测试') !== false || strpos($v['home_team_name'],'test') !== false || strpos($v['away_team_name'],'test') !== false)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['is_sub'] === null || $v['is_sub'] === '' || $v['is_sub'] > 3)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['game_state'] == -14) continue;   //推迟的比赛不显示
                if($v['gtime'] + 60 < time() && $v['game_state'] == 0) continue;          //过了开场时间未开始
                if($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) continue;      //140分钟还没结束

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']))
                    $val[2] = explode(',',$v['union_name']);
                else
                    $val[2] = explode(',',$v['u_name']);
                $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[4] = $v['is_sub']!== null?$v['is_sub']:'';
                $val[5] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[6] = $gameTime[0];
                $val[7] = $gameTime[1];
                $tempTime = explode(',',$v['game_half_time']);
                $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                $val[8] = implode('',$tempTime);
                $val[9] = explode(',',$v['home_team_name']);
                $val[10] = explode(',',$v['away_team_name']);
                 #全场亚盘滚球赔率
                if($oddsArr[$v['game_id']])
                {
                    #全场亚盘滚球
                    $val[11] = $oddsArr[$v['game_id']][0];
                    $val[12] = changeExp($oddsArr[$v['game_id']][1]);
                    $val[13] = $oddsArr[$v['game_id']][2];
                    #全场大小滚球
                    $val[14] = $oddsArr[$v['game_id']][6];
                    $val[15] = changeExp($oddsArr[$v['game_id']][7]);
                    $val[16] = $oddsArr[$v['game_id']][8];
                    #全场欧盘滚球
                    $val[17] = $oddsArr[$v['game_id']][3];
                    $val[18] = $oddsArr[$v['game_id']][4];
                    $val[19] = $oddsArr[$v['game_id']][5];

                    if($v['game_state'] == 1)
                    {
                        #半场场亚盘滚球
                        $val[20] = $oddsArr[$v['game_id']][9];
                        $val[21] = changeExp($oddsArr[$v['game_id']][10]);
                        $val[22] = $oddsArr[$v['game_id']][11];
                        #半场大小滚球
                        $val[23] = $oddsArr[$v['game_id']][15];
                        $val[24] = changeExp($oddsArr[$v['game_id']][16]);
                        $val[25] = $oddsArr[$v['game_id']][17];
                        #半场欧盘滚球
                        $val[26] = $oddsArr[$v['game_id']][12];
                        $val[27] = $oddsArr[$v['game_id']][13];
                        $val[28] = $oddsArr[$v['game_id']][14];
                    }
                    else
                    {
                        #半场场亚盘滚球
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
                }
                else
                {
                    #全场亚盘滚球
                    $val[11] = ($v['fsw_exp_home'] == null && $v['fsw_exp_home'] == '')?'':$v['fsw_exp_home'];
                    $val[12] = ($v['fsw_exp']== null && $v['fsw_exp'] == '')?'':changeExp($v['fsw_exp']);
                    $val[13] = ($v['fsw_exp_away']== null && $v['fsw_exp_away'] == '')?'':$v['fsw_exp_away'];
                    #全场大小滚球
                    $val[14] = ($v['fsw_ball_home']== null && $v['fsw_ball_home'] == '')?'':$v['fsw_ball_home'];
                    $val[15] = ($v['fsw_ball']== null && $v['fsw_ball'] == '')?'':changeExp($v['fsw_ball']);
                    $val[16] = ($v['fsw_ball_away']== null && $v['fsw_ball_away'] == '')?'':$v['fsw_ball_away'];
                    #全场欧盘滚球
                    $val[17] = '';
                    $val[18] = '';
                    $val[19] = '';

                    #半场亚盘滚球
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

                $score = explode('-',$v['score']);
                $val[29] = $score[0];
                $val[30] = $score[1];
                $half_score = explode('-',$v['half_score']);
                $val[31] = $half_score[0];
                $val[32] = $half_score[1];
                if($v['game_state'] != -1 )
                    $val[33] = $v['is_video'];
                else
                    $val[33] = '0';
                //$val[34] = $v['is_flash'];
                if(isset($linksArr[$v['game_id']]) && $v['game_state'] != -1 )
                    $val[34] = '1';
                else
                    $val[34] = '0';
                $rData[] = $val;
            }
        }
        return $rData;
    }

     /**
     * 当日完场赛事
     * @return array 完场赛事数组
     */
    public function fbOverList($date,$unionId,$subId)
    {
        $GameFbinfo = M('GameFbinfo');
        $map['a.status'] = 1;
        //$map['game_state'] = -1;
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

        $baseRes = $GameFbinfo->alias('a')->field('game_id,a.union_id,a.union_name,gtime,game_state,home_team_name,away_team_name,score,half_score,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,u.union_name u_name,is_video,is_flash,is_betting,bet_code')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('gtime,is_sub,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
            }
            $oddsArr = $this->fbOdds($gids);

            $map2['game_id'] = array('in',implode(',',$gids));
            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id')->where($map2)->select();

            $linksArr = [];
            if(!empty($betRes))
            {
                foreach($betRes as $k=> $v)
                {
                    $linksArr[$v['game_id']] = $v['is_link'];
                }
            }

            foreach($baseRes as $k =>$v)
            {
                if(stripos($v['home_team_name'],'测试') !== false || strpos($v['away_team_name'],'测试') !== false || strpos($v['home_team_name'],'test') !== false || strpos($v['away_team_name'],'test') !== false)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['is_sub'] === null || $v['is_sub'] === '' || $v['is_sub']>3)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if(strtotime($date) < strtotime('00:00:00'))
                {
                    if($v['game_state'] == 0) continue;     //完场未开始的赛事过滤掉
                }
                if(in_array($v['game_state'],[1,2,3,4])) continue;

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']))
                    $val[2] = explode(',',$v['union_name']);
                else
                    $val[2] = explode(',',$v['u_name']);
                $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[4] =$v['is_sub'] !== null?$v['is_sub']:'';
                $val[5] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[6] = $gameTime[0];
                $val[7] = $gameTime[1];
                $tempTime = explode(',',$v['game_half_time']);
                $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                $val[8] = implode('',$tempTime);
                $val[9] = explode(',',$v['home_team_name']);
                $val[10] = explode(',',$v['away_team_name']);
                $val[11] = !empty($v['home_team_rank'])?pregUnionRank($v['home_team_rank']):'';
                $val[12] = !empty($v['away_team_rank'])?pregUnionRank($v['away_team_rank']):'';
                $score = explode('-',$v['score']);
                $val[13] = $score[0];
                $val[14] = $score[1] !==null? $score[1]:'';
                $half_score = explode('-',$v['half_score']);
                $val[15] = $half_score[0];
                $val[16] = $half_score[1]!==null? $half_score[1]:'';

                 #红黄牌
                if(!empty($v['red_card']))
                {
                    $red = explode('-',$v['red_card']);
                    $val[17] = $red[0];
                    $val[18] = $red[1];
                }
                else
                {
                    $val[17] = '0';
                    $val[18] = '0';
                }
                if(!empty($v['yellow_card']))
                {
                    $red = explode('-',$v['yellow_card']);
                    $val[19] = $red[0];
                    $val[20] = $red[1];
                }
                else
                {
                    $val[19] = '0';
                    $val[20] = '0';
                }
                 #角球
                if(!empty($v['corner']))
                {
                    $corner = explode('-',$v['corner']);
                    $val[21] = $corner[0];
                    $val[22] = $corner[1];
                }
                else
                {
                    $val[21] = '0';
                    $val[22] = '0';
                }
                #全场亚盘滚球
                $val[23] = $v['fsw_exp_home'] == null?'':$v['fsw_exp_home'];
                $val[24] = $v['fsw_exp']== null?'':changeExp($v['fsw_exp']);
                $val[25] = $v['fsw_exp_away']== null?'':$v['fsw_exp_away'];
                #全场大小滚球
                $val[26] = $v['fsw_ball_home']== null?'':$v['fsw_ball_home'];
                $val[27] = $v['fsw_ball']== null?'':changeExp($v['fsw_ball']);
                $val[28] = $v['fsw_ball_away']== null?'':$v['fsw_ball_away'];

                /*if(isset($oddsArr[$v['game_id']]))
                {
                    #全场亚盘滚球
                    $val[23] = $oddsArr[$v['game_id']][0] ;
                    $val[24] = changeExp($oddsArr[$v['game_id']][1]);
                    $val[25] = $oddsArr[$v['game_id']][2];
                    #全场大小滚球
                    $val[26] = $oddsArr[$v['game_id']][6];
                    $val[27] = changeExp($oddsArr[$v['game_id']][7]);
                    $val[28] = $oddsArr[$v['game_id']][8];
                }
                else
                {
                    #全场亚盘滚球
                    $val[23] = $v['fsw_exp_home'] == null?'':$v['fsw_exp_home'];
                    $val[24] = $v['fsw_exp']== null?'':changeExp($v['fsw_exp']);
                    $val[25] = $v['fsw_exp_away']== null?'':$v['fsw_exp_away'];
                    #全场大小滚球
                    $val[26] = $v['fsw_ball_home']== null?'':$v['fsw_ball_home'];
                    $val[27] = $v['fsw_ball']== null?'':changeExp($v['fsw_ball']);
                    $val[28] = $v['fsw_ball_away']== null?'':$v['fsw_ball_away'];
                }*/
                if($v['game_state'] != -1)
                    $val[29] = $v['is_video'];
                else
                    $val[29] = '0';
                //$val[30] = $v['is_flash'];
                if(isset($linksArr[$v['game_id']]) && $v['game_state'] != -1)
                    $val[30] = '1';
                else
                    $val[30] = '0';
                $val[31] = $v['is_betting'];
                $val[32] = !empty($v['bet_code'])?$v['bet_code']:'';
                $rData[] = $val;
            }
        }
        return $rData;
    }

    /**
     * 赛程列表
     * @param  int $date       日期
     * @param  string $unionId 赛事ID,多个以‘,’隔开
     * @return array           赛程数据
     */
    public function fbFixtureList($date,$unionId,$subId='')
    {
        $GameFbinfo = M('GameFbinfo');
        $map['a.status'] = 1;
        if(!empty($subId)) $map['is_sub'] =array('in',$subId);
        if(!empty($unionId)) $map['union_id'] = array('in',$unionId);

        if(!empty($date))
        {
            $startTime = strtotime($date.' 10:32:00');
            $endTime = $startTime+3600*24;
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

        $baseRes = $GameFbinfo->alias('a')->field('game_id,a.union_id,a.union_name,gtime,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,u.union_name u_name,is_video,is_flash,is_betting,bet_code')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,is_sub,a.id')->select();

        $rData = [];
        if(!empty($baseRes))
        {
            $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
            }
            $oddsArr = $this->fbOdds($gids);

            $map2['game_id'] = array('in',implode(',',$gids));
            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id')->where($map2)->select();

            $linksArr = [];
            if(!empty($betRes))
            {
                foreach($betRes as $k=> $v)
                {
                    $linksArr[$v['game_id']] = $v['is_link'];
                }
            }

            $map2['game_id'] = array('in',implode(',',$gids));
            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where($map2)->select();

            $linksArr = $mdArr = [];
            if(!empty($betRes))
            {
                foreach($betRes as $k=> $v)
                {
                    $linksArr[$v['game_id']] = $v['is_link'];
                    if(!empty($v['flash_id']) && !empty($v['md_id']))
                    {
                        $mdArr[$v['game_id']] = $v['md_id'];
                    }
                }
            }


            foreach($baseRes as $k=>$v)
            {
                if(stripos($v['home_team_name'],'测试') !== false || strpos($v['away_team_name'],'测试') !== false || strpos($v['home_team_name'],'test') !== false || strpos($v['away_team_name'],'test') !== false)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['is_sub'] === null || $v['is_sub'] === '' || $v['is_sub']>3)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                if(!empty($v['union_name']))
                    $val[2] = explode(',',$v['union_name']);
                else
                    $val[2] = explode(',',$v['u_name']);
                $val[3] = !empty($v['union_color'])?$v['union_color']:'#aeba4b';
                $val[4] = $v['is_sub'] !== null?$v['is_sub']:'';
                $val[5] = $v['game_state'];
                $gameTime = explode('-',date('Ymd-H:i',$v['gtime']));
                $val[6] = $gameTime[0];
                $val[7] = $gameTime[1];
                $val[8] = explode(',',$v['home_team_name']);
                $val[9] = explode(',',$v['away_team_name']);
                $val[10] = !empty($v['home_team_rank'])?pregUnionRank($v['home_team_rank']):'';
                $val[11] = !empty($v['away_team_rank'])?pregUnionRank($v['away_team_rank']):'';
                if(isset($oddsArr[$v['game_id']]))
                {
                    #全场亚盘滚球
                    $val[12] = $oddsArr[$v['game_id']][0] ;
                    $val[13] = changeExp($oddsArr[$v['game_id']][1]);
                    $val[14] = $oddsArr[$v['game_id']][2];
                    #全场大小滚球
                    $val[15] = $oddsArr[$v['game_id']][6];
                    $val[16] = changeExp($oddsArr[$v['game_id']][7]);
                    $val[17] = $oddsArr[$v['game_id']][8];
                }
                else
                {
                    $val[12] = $v['fsw_exp_home'] == null?'':$v['fsw_exp_home'];
                    $val[13] = $v['fsw_exp'] == null?'':changeExp($v['fsw_exp']);
                    $val[14] = $v['fsw_exp_away'] == null?'':$v['fsw_exp_away'];
                    $val[15] = $v['fsw_ball_home'] == null?'':$v['fsw_ball_home'];
                    $val[16] = $v['fsw_ball'] == null?'':changeExp($v['fsw_ball']);
                    $val[17] = $v['fsw_ball_away'] == null?'':$v['fsw_ball_away'];
                }
                if($v['game_state'] != -1)
                    $val[18] = $v['is_video'];
                else
                    $val[18] = '0';
                //$val[30] = $v['is_flash'];
                if(isset($linksArr[$v['game_id']]) && $v['game_state'] != -1)
                    $val[19] = '1';
                else
                    $val[19] = '0';
                $val[20] = $v['is_betting'];
                $val[21] = !empty($v['bet_code'])?$v['bet_code']:'';
                $rData[] = $val;
            }
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
        $GameFbinfo = M('GameFbinfo');

        $startTime = time();
        $endTime = $startTime+3600*24;
        $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));

        $map['game_state'] = 0;
        $map['a.status'] = 1;
        if(!empty($unionId)) $map['a.union_id'] = array('in',$unionId);
        if(!empty($subId))
            $map['is_sub'] = array('in',$subId);
        else
            $map['is_sub'] = array('in','0,1,2');

        $baseRes = $GameFbinfo->alias('a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,is_video,u.union_name as u_name')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,a.id')->select();

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
     * SB公司赔率历史数据————数据库数据
     * @param  int    $gameId  赛事ID
     * @param  int    $type     类别：1亚，2欧，3大小
     * @return array  SB公司赔率历史数据
     */
    public function getSBhisOdds($gameId ,$type = 1)
    {
        if(empty($gameId)) return false;

        $fbHisodds = M('fbOddshis');

        $map['game_id'] = (int) $gameId;
        $map['company_id'] = 3;

        $baseRes = $fbHisodds->field('sb_ahistory,sb_ohistory,sb_bhistory')->where($map)->find();
        $rData = [];
        $hisOdds = [];

        if(!empty($baseRes))
        {
            $gRes = M('GameFbinfo')->field('gtime')->where(['game_id'=>(int) $gameId])->find();
            $gameTime = date('YmdHis',$gRes['gtime']);

            switch($type)
            {
                case 1:
                    if(empty($baseRes['sb_ahistory'])) break;
                    $hisOdds = json_decode($baseRes['sb_ahistory'],true);
                    break;
                case 2:
                    if(empty($baseRes['sb_ohistory'])) break;
                    $hisOdds = json_decode($baseRes['sb_ohistory'],true);
                    break;
                case 3:
                    if(empty($baseRes['sb_bhistory'])) break;
                    $hisOdds = json_decode($baseRes['sb_bhistory'],true);
                    break;
            }

            if(!empty($hisOdds))
            {
                $aSort = [];
                foreach($hisOdds as $k=>$v)
                {

                    if($v['Score'] == '即' || $v['Score'] == '早') continue;
                    /*if($v['IsClosed'] =='封')
                    {
                        $temp = [
                            0 => $v['HomeOdds'],
                            1 => '100',
                            2 => $v['AwayOdds'],
                            3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
                            4 => $v['Score'],
                        ];
                    }
                    else
                    {
                        $temp = [
                            0 => $v['HomeOdds'],
                            1 => changeExp($v['PanKou']),
                            2 => $v['AwayOdds'],
                            3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
                            4 => $v['Score'],
                        ];
                    }*/
                    if($v['IsClosed'] =='封')
                    {
                        $temp = [
                            0 => $v['HomeOdds'],
                            1 => '100',
                            2 => $v['AwayOdds'],
                            3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
                            4 => $v['Score'],
                            //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
                            5=> $v['HappenTime'],
                        ];
                    }
                    else
                    {
                        $temp = [
                            0 => $v['HomeOdds'],
                            1 => changeExp($v['PanKou']),
                            2 => $v['AwayOdds'],
                            3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
                            4 => $v['Score'],
                            //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
                            5=> $v['HappenTime'],
                        ];
                    }

                    $temp[5] = str_pad($temp[5],2,"0",STR_PAD_LEFT);

                    $aSort[] = $temp[3];
                    $aSort2[] = $temp[5];
                    $rData[] = $temp;
                }
                array_multisort($aSort, SORT_ASC, $rData,SORT_DESC,$aSort2);
            }
        }
        return $rData;
    }

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
     * @param  int   $gameId  赛事ID
     * @param  int   $lang  语言ID(1是简体，2是繁体)
     * @return array  数据
     */
    public function getAnaForFile($gameId,$lang = 1)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $rData = [];
        $item = $this->data['analysis'];
        $ext = getFileExt($item['mimeType']);

        $GameFbinfo = D('GameFbinfo');
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
                        2 => [$oArr[2][0],changeExp($oArr[2][1]),$oArr[2][2],$oArr[2][3],changeExp($oArr[2][4]),$oArr[2][5]],
                    ];
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

                /*if(!empty($PreMatchInfo))
                {
                    $aaTemp = [
                        'name'     => 'PreMatchInfo',
                        'content'  => $PreMatchInfo,
                    ];
                    $rData[] = $aaTemp;
                }*/
            }
        }
        return $rData;
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
                $mService = new \Common\Services\MongodbService();
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
        $defaultHomeImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/home_def.png';
        $defaultAwayImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/away_def.png';

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
        $defaultHomeImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/home_def.png';
        $defaultAwayImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/away_def.png';

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
        $defaultHomeImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/home_def.png';
        $defaultAwayImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/away_def.png';

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
        $defaultHomeImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/home_def.png';
        $defaultAwayImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/away_def.png';

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

        $map['union_id'] = $unionId;
        $map['status'] = 1;
        //$map['gtime'] = array('gt',strtotime(date('Y'),time()));
        $map['_string'] = 'home_team_id ='.$teamId.' OR away_team_id ='.$teamId;

        $res = M('GameFbinfo')->field('game_id,union_name,home_team_id,home_team_name,away_team_id,away_team_name,score,half_score,gtime,game_state,fsw_exp,fsw_ball,home_team_rank,away_team_rank')->where($map)->order('gtime desc')->limit(50)->select();

        $rData = [];
        $tInfo = $this->getTeamInfo($teamId);
        $rData['team_info'] = $tInfo;

        if(!empty($res))
        {
            $FbdataService = new \Common\Services\FbdataService();
            $gameInfo = [];
            foreach ($res as $k => $v)
            {
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
    public function getTeamInfo($teamId)
    {
        if(empty($teamId)) return false;
        $rData = M('GameTeam')->field('team_id,team_name,country_id,country,stadium_name,people,formed,url,img_url,team_intro,union_id,union_name')->where(['team_id'=>$teamId,'status'=>1])->find();

        if(empty($rData)) return null;
        $rData['team_name'] = explode(',',$rData['team_name']);
        $rData['stadium_name'] = explode(',',$rData['stadium_name']);
        $rData['union_name'] = explode(',',$rData['union_name']);
        $rData['country_id'] = $rData['country_id']==null?'':$rData['country_id'];
        $rData['country'] = empty($rData['country'])?array():explode(',',$rData['country']);
        $rData['people'] = $rData['people']==null?'-':$rData['people'];
        $rData['formed'] = $rData['formed']==null?'-':$rData['formed'];
        $rData['url'] = $rData['url']==null?'-':$rData['url'];
        $httpUrl = C('IMG_SERVER');
        $defaultImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/team_def.png';
        $teamImg = !empty($rData['img_url']) ? $httpUrl.$rData['img_url'] : $defaultImg;
        $rData['img_url'] = $teamImg;
        $rData['team_intro'] = $rData['team_intro']==null?'':$rData['team_intro'];
        return $rData;
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
        $res = M('FbMatchinfo')->field('home_pre_match_info,away_pre_match_info')->where(['game_id'=>$gameId,'from_web'=>$from])->find();
        $res2 = M('FbMatchinfo')->field('home_pre_match_info,away_pre_match_info')->where(['game_id'=>$gameId,'from_web'=>0])->find();

        $rData = [];
        if(!empty($res['home_pre_match_info']) || !empty($res['away_pre_match_info']))
        {
            $rData = [];
            if(!empty($res['home_pre_match_info']))
            {
                $rData['HpmInfo'] = json_decode($res['home_pre_match_info'],true);
                if(!empty($res2['home_pre_match_info'])) array_unshift($rData['HpmInfo'],$res2['home_pre_match_info']);
            }
            if(!empty($res['away_pre_match_info']))
            {
                $rData['ApmInfo'] = json_decode($res['away_pre_match_info'],true);
                if(!empty($res2['away_pre_match_info'])) array_unshift($rData['ApmInfo'],$res2['away_pre_match_info']);
            }
        }

        #mongo赛事情报数据
        $mService = new \Common\Services\MongodbService(C('Mongodb2')['DB_HOST'],C('Mongodb2')['DB_NAME']);
        $mRes = $mService->select('fb_163gameevent',['game_id'=>(int)$gameId]);
        if(!empty($mRes))
        {
            if(isset($mRes[0]['game_event']) && !empty($mRes[0]['game_event']))
            {
                $upStr = '【有利】';
                $downStr = '【无利】';
                foreach($mRes[0]['game_event'] as $key=>$val)
                {
                    if($val['HomeAway'] == 0)
                    {
                        if($val['UpDown'] == 0)
                            $rData['HpmInfo'][] =  $upStr.$val['Title'];
                        else
                            $rData['HpmInfo'][] =  $downStr.$val['Title'];
                    }
                    else
                    {
                        if($val['UpDown'] == 0)
                            $rData['ApmInfo'][] =  $upStr.$val['Title'];
                        else
                            $rData['ApmInfo'][] =  $downStr.$val['Title'];
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

        $res = M('StatisticsFb')->field('game_id,s_type,home_value,away_value')->where(['game_id'=>$gameId])->select();

        $rData = [];
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

        $corData = [];
        if(!empty($res))
        {
            $tRes = M('GameFbinfo')->field('game_id,home_team_name,home_team_id,away_team_name,away_team_id')->where(['game_id'=>$gameId])->find();
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
        return $rData;
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
     * 根据赛事ID获取最新赔率数据 qc_fb_odds表
     * @param  array   $gameIds  赛事ID
     * @param  int     $companyID  公司ID
     * @return array  当日即时赛事数据
     */
    public function fbOdds($gameIds,$companyID = 3)
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
                #半场
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