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
        if ($v['is_gamble'] != 1 || ($v['is_sub'] > 2 && $v['is_show'] != 1)) {
            return 0;
        }
        if ($v['fsw_exp'] == '' || $v['fsw_exp_home'] == '' || $v['fsw_exp_away'] == '' || $v['fsw_ball'] == '' || $v['fsw_ball_home'] == '' || $v['fsw_ball_away'] == '') {
            return 0;
        }
        return 1;
    }

     /**
     * 当日即时赛事
     * @param  string   $unionId  联赛ID
     * @param  string   $subId  联赛级别
     * @return array  当日即时赛事数据
     * @author huangmg 2016-12-27
     */
    public function fbtodayList($unionId,$subId ='')
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

        $baseRes = $GameFbinfo->table('qc_game_fbinfo a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,is_video,is_flash,is_betting,bet_code,is_go,is_gamble,is_show,u.union_name as u_name,u.is_union,u.sort,u.is_lib,country_id,weather')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,bet_code,is_sub,a.id')->select();
        $rData = [];
        if(!empty($baseRes))
        {
            $gids = [];
            foreach($baseRes as $k=> $v)
            {
                $gids[] = $v['game_id'];
            }
            if(!$arr = S('services_todaygames')) S('services_todaygames',implode(',',$gids),60 );

            $map2['game_id'] = array('in',implode(',',$gids));
            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where($map2)->select();

            $linksArr = $mdArr = [];
            if(!empty($betRes))
            {
                foreach($betRes as $k=> $v)
                {
                    $linksArr[$v['game_id']] = $v['is_link'];
                    $mdArr[$v['game_id']] = $v['md_id'];
                }
            }

            //$oddsArr = $this->fbOdds($gids);
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
                #初盘赔率
                $val[33] = !empty($v['fsw_exp_home'])?$v['fsw_exp_home']:'';  //主队亚盘初盘赔率
                if($v['fsw_exp'] == '-0') $v['fsw_exp'] = '0';
                $val[34] = $v['fsw_exp'] !== null?$v['fsw_exp']:'';   //亚盘初盘盘口
                $val[35] = !empty($v['fsw_exp_away'])?$v['fsw_exp_away']:'';   //客队亚盘初盘赔率
                $val[36] = !empty($v['fsw_ball_home'])?$v['fsw_ball_home']:'';  //主队大小初盘赔率
                if($v['fsw_ball'] == '-0') $v['fsw_ball'] = '0';
                $val[37] = $v['fsw_ball'] !== null?$v['fsw_ball']:'';   //亚盘初盘盘口
                $val[38] = !empty($v['fsw_ball_away'])?$v['fsw_ball_away']:'';   //客队大小初盘赔率
                $val[39] = $v['is_go'];
                $val[40] = $v['is_video'];
                //$val[41] = $v['is_flash'];
                if(isset($linksArr[$v['game_id']]) && $v['game_state'] !=-1)
                {
                    if(in_array($v['game_state'],[1,2,3,4]))
                    {
                        if(isset($mdArr[$v['game_id']]) && !empty($mdArr[$v['game_id']]))
                            $val[41] = '1';
                        else
                            $val[41] = '0';
                    }
                    else
                    {
                        $val[41] = '1';
                    }
                }
                else
                {
                    $val[41] = '0';
                }

                $val[42] = $v['is_betting'];
                $val[43] = empty($v['bet_code'])?'':$v['bet_code'];
                $val[44] = $this->checkGamble($v);
                $val[45] = $v['weather'] ? : '';
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
                    $uVal[9] = $v['sort'];
                    $unionArr[$v['union_id']] = $uVal;
                }
            }
            sort($unionArr);
            $rData = ['info'=>$gameinfo,'union' =>$unionArr];
        }
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

        $baseRes = $GameFbinfo->table('qc_game_fbinfo a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,is_video,is_flash,is_betting,bet_code,is_go,is_gamble,is_show,u.union_name as u_name,u.is_union,u.is_lib,country_id')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,bet_code,is_sub,a.id')->select();

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

        $baseRes = $GameFbinfo->table('qc_game_fbinfo a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,corner,is_video,is_flash,is_betting,bet_code,is_go,is_gamble,is_show,u.union_name as u_name,u.is_union,u.is_lib,country_id,is_sub')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,bet_code,is_sub,a.id')->select();

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

        $baseRes = $GameFbinfo->table('qc_game_fbinfo a')->field('a.id,game_id,a.union_id,a.union_name,gtime,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,is_video,is_gamble,is_show,u.union_name as u_name,u.is_union,u.is_lib,country_id')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->order('game_state desc,gtime,a.id')->select();

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

        #半场赔率变化
        /*$gids = '';
        if(!$gids = S('services_todaygames'))
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
            $map['status'] = 1;

            $baseRes = $GameFbinfo->field('id,game_id')->where($map)->order('game_state desc,gtime,bet_code,id')->select();

            if(!empty($baseRes))
            {
                $gTemp = [];
                foreach($baseRes as $k=> $v)
                {
                    $gTemp[] = $v['game_id'];
                }
            }
            $gids = implode(',',$gTemp);
        }

        if(empty($gids)) return [];

        if($companyID == 3)
        {
            $map2['game_id'] = array('in',$gids);
            $map2['company_id'] = $companyID;

            $obj = M('FbOdds');
            $res = $obj->field('game_id,exp_value')->where($map2)->select();

            $halfOdds = [];
            if(!empty($res))
            {
                foreach($res as $k=>$v)
                {
                    $temp = [];
                    $oTemp = oddsChArr($v['exp_value']);
                    $halfOdds[$v['game_id']] = $oTemp;
                }
            }
            $halfOddsCache = S('oddsdata_half');
            //var_dump(S('oddsdata_half'),$halfOdds);//exit;

            if($halfOddsCache = S('oddsdata_half'))
            {
                if(json_encode($halfOdds) != json_encode($halfOddsCache))
                {
                    foreach($halfOdds as $kk=>$vv)
                    {
                        if(isset($halfOddsCache[$kk]))
                        {
                            $arr = [9=>'',10=>'',11=>'',12=>'',13=>'',14=>'',15=>'',16=>'',17=>''];
                            $aTemp = implode(',',$vv[3]);
                            $aTempCache = implode(',',$halfOddsCache[$kk][3]);

                            if($aTemp != $aTempCache)
                            {
                                if(($vv[3][6] != '' || $vv[3][7] != '' || $vv[3][8] != '') && ($vv[3][6] != $halfOddsCache[$kk][3][6] || $vv[3][7] != $halfOddsCache[$kk][3][7] || $vv[3][8] != $halfOddsCache[$kk][3][8]))
                                {
                                    $arr[9] = $vv[3][6];
                                    $arr[10] = $vv[3][7];
                                    $arr[11] = $vv[3][8];
                                }
                                else if(($vv[3][3] != '' || $vv[3][4] != '' || $vv[3][5] != '') && ($vv[3][3] != $halfOddsCache[$kk][3][3] || $vv[3][4] != $halfOddsCache[$kk][3][4] || $vv[3][5] != $halfOddsCache[$kk][3][5]))
                                {
                                    $arr[9] = $vv[3][3];
                                    $arr[10] = $vv[3][4];
                                    $arr[11] = $vv[3][5];
                                }
                                if(($vv[4][6] != '' || $vv[4][7] != '' || $vv[4][8] != '') && ($vv[4][6] != $halfOddsCache[$kk][4][6] || $vv[4][7] != $halfOddsCache[$kk][4][7] || $vv[4][8] != $halfOddsCache[$kk][4][8]))
                                {
                                    $arr[12] = $vv[4][6];
                                    $arr[13] = $vv[4][7];
                                    $arr[14] = $vv[4][8];
                                }
                                else if(($vv[4][3] != '' || $vv[4][4] != '' || $vv[4][5] != '') && ($vv[4][3] != $halfOddsCache[$kk][4][3] || $vv[4][4] != $halfOddsCache[$kk][4][4] || $vv[4][5] != $halfOddsCache[$kk][4][5]))
                                {
                                    $arr[12] = $vv[4][3];
                                    $arr[13] = $vv[4][4];
                                    $arr[14] = $vv[4][5];
                                }
                                if(($vv[5][6] != '' || $vv[5][7] != '' || $vv[5][8] != '') && ($vv[5][6] != $halfOddsCache[$kk][5][6] || $vv[5][7] != $halfOddsCache[$kk][5][7] || $vv[5][8] != $halfOddsCache[$kk][5][8]))
                                {
                                    $arr[15] = $vv[5][6];
                                    $arr[16] = $vv[5][7];
                                    $arr[17] = $vv[5][8];
                                }
                                else if(($vv[5][3] != '' || $vv[5][4] != '' || $vv[5][5] != '') && ($vv[5][3] != $halfOddsCache[$kk][5][3] || $vv[5][4] != $halfOddsCache[$kk][5][4] || $vv[5][5] != $halfOddsCache[$kk][5][5]))
                                {
                                    $arr[15] = $vv[5][3];
                                    $arr[16] = $vv[5][4];
                                    $arr[17] = $vv[5][5];
                                }
                            }
                            if(isset($rData[$kk]))
                            {
                                $cTemp = array_merge($rData[$kk],$arr);
                                $rData[$kk] = $cTemp;
                            }
                            else
                            {
                                if($arr[0] == '' && $arr[1] == '' && $arr[2] == '' && $arr[3] == '' && $arr[4] == '' && $arr[5] == '' && $arr[6] == '' && $arr[7] == '' && $arr[8] == '') continue;
                                $arr2 = [0=>'',1=>'',2=>'',3=>'',4=>'',5=>'',6=>'',7=>'',8=>''];
                                $cTemp = array_merge($arr2,$arr);
                                $rData[$kk] = $cTemp;
                            }
                        }
                    }

                    S('oddsdata_half',json_encode($halfOdds));
                }

            }
            else
            {

                S('oddsdata_half',json_encode($halfOdds));
            }
        }*/

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
     * 获取当日赛事赔率弹出框赔率数据
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     * @author huangmg 2016-12-27
     */
    public function getOddsData($companyID = 3)
    {
        if(S('services_oddsdiv'))
        {
            return S('services_oddsdiv');
        }
        $gids = '';
        if(!$gids = S('services_todaygames'))
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
            $map['status'] = 1;

            $baseRes = $GameFbinfo->field('id,game_id')->where($map)->order('game_state desc,gtime,bet_code,id')->select();

            if(!empty($baseRes))
            {
                $gTemp = [];
                foreach($baseRes as $k=> $v)
                {
                    $gTemp[] = $v['game_id'];
                }
            }
            $gids = implode(',',$gTemp);
        }

        if(empty($gids)) return [];

        $map2['game_id'] = array('in',$gids);
        $map2['company_id'] = $companyID;

        $obj = M('FbOdds');
        $res = $obj->field('game_id,exp_value')->where($map2)->select();

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

        $map3['game_id'] = array('in',$gids);
        $obj2 = M('FbGoal');
        $res = $obj2->field('game_id,company_id,exp_value')->where($map3)->select();

        $compArr = [];
        foreach($res as $k=>$v)
        {
            $oTemp = oddsChArr($v['exp_value']);
            $temp = [];
            if($oTemp[0][0] =='' && $oTemp[0][1] =='' && $oTemp[0][2] =='' && $oTemp[0][3] =='' && $oTemp[0][4] =='' && $oTemp[0][5] ==''&& $oTemp[0][6] =='' && $oTemp[0][7] =='' && $oTemp[0][8] =='') continue;
            if($oTemp[0][6] !='' || $oTemp[0][7] !='' || $oTemp[0][8] !='')
            {
                $temp[] = round($oTemp[0][6],2);
                $temp[] = changeExp($oTemp[0][7]);
                $temp[] = round($oTemp[0][8],2);
            }
            else if($oTemp[0][3] !='' || $oTemp[0][4] !='' || $oTemp[0][5] !='')
            {
                $temp[] = round($oTemp[0][3],2);
                $temp[] = changeExp($oTemp[0][4]);
                $temp[] = round($oTemp[0][5],2);
            }
            else
            {
                $temp[] = round($oTemp[0][0],2);
                $temp[] = changeExp($oTemp[0][1]);
                $temp[] = round($oTemp[0][2],2);
            }

            $compArr[$v['game_id']][$v['company_id']] = $temp;
        }

        $sortArr = [1,12,8,4,23,17,24,31];
        foreach($rData as $k=>$v)
        {
            if(isset($compArr[$k]))
            {
                $temp = $oldTemp = [];
                $oldTemp = $compArr[$k];
                if(isset($oldTemp[$sortArr[0]])) $temp[$sortArr[0]] = $oldTemp[$sortArr[0]];
                if(isset($oldTemp[$sortArr[1]])) $temp[$sortArr[1]] = $oldTemp[$sortArr[1]];
                if(isset($oldTemp[$sortArr[2]])) $temp[$sortArr[2]] = $oldTemp[$sortArr[2]];
                if(isset($oldTemp[$sortArr[3]])) $temp[$sortArr[3]] = $oldTemp[$sortArr[3]];
                if(isset($oldTemp[$sortArr[4]])) $temp[$sortArr[4]] = $oldTemp[$sortArr[4]];
                if(isset($oldTemp[$sortArr[5]])) $temp[$sortArr[5]] = $oldTemp[$sortArr[5]];
                if(isset($oldTemp[$sortArr[6]])) $temp[$sortArr[6]] = $oldTemp[$sortArr[6]];
                if(isset($oldTemp[$sortArr[7]])) $temp[$sortArr[7]] = $oldTemp[$sortArr[7]];
                $rData[$k][6] = $temp;
            }
        }
        S('services_oddsdiv',json_encode($rData),2);
        return $rData;
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
        $gids = '';
        if(!empty($gameId)) $gids = $gameId;

        if(empty($gids))
        {
            if(!$gids = S('services_todaygames'))
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
                $map['status'] = 1;

                $baseRes = $GameFbinfo->field('id,game_id')->where($map)->order('game_state desc,gtime,bet_code,id')->select();

                if(!empty($baseRes))
                {
                    $gTemp = [];
                    foreach($baseRes as $k=> $v)
                    {
                        $gTemp[] = $v['game_id'];
                    }
                }
                $gids = implode(',',$gTemp);
            }
        }

        if(empty($gids)) return [];

        $rData = [];
        $res = M()->query('select * from qc_detail_fb where game_id in('.$gids.') order by gtime');

        $t = [];
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
                $t[$v['game_id']][] = $temp;
            }
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
        if(!empty($t) || !empty($s))
        {
            if(!empty($t)) $rData['t'] = $t;
            if(!empty($s)) $rData['s'] = $s;
        }
        return $rData;
    }

    /**
     * 赛事事件变化数据(数据库)
     * @return array
     * @author huangmg 2016-12-29
     */
    public function getPanluWeb($num = '10',$game_id)
    {
        if($fb_panlu_cache = S('fb_panlu_cache'.$game_id))
        {
            return $fb_panlu_cache;
        }

        $GameFbinfo = M('GameFbinfo');
        // if(strtotime('10:32:00') < time())
        // {
        //     $startTime = strtotime('8:00:00');
        //     $endTime = strtotime('10:32:00')+3600*24;
        // }
        // else
        // {
        //     $startTime =strtotime('8:00:00')-3600*24;
        //     $endTime = strtotime('10:32:00');
        // }
        // $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
        $map['status'] = 1;
        $map['game_id'] = $game_id;

        $baseRes = $GameFbinfo->field('id,game_id,gtime,home_team_id,home_team_name,away_team_id,away_team_name')->where($map)->order('game_state desc,gtime,bet_code,id')->select();

        if(empty($baseRes)) return [];

        foreach($baseRes as $k=> $v)
        {
            $tSql = 'select a.id,game_id,gtime,a.game_state,score,half_score,a.union_name,home_team_id,home_team_name,away_team_id,away_team_name,fsw_exp,fsw_ball,union_color from qc_game_fbinfo a left join qc_union b on a.union_id = b.union_id where 1 and a.status=1 and a.game_state = -1 and gtime <'.$v['gtime'].' and ((home_team_id = '.$v['home_team_id'].' and away_team_id = '.$v['away_team_id'].') or (home_team_id ='.$v['away_team_id'].' and away_team_id = '.$v['home_team_id'].')) order by id desc limit '.$num;

            $tRes = M()->query($tSql);

            if(empty($tRes)) continue;
            $gData = [];
            foreach($tRes as $kk =>$vv)
            {
                $temp = [];
                $temp[0] = $vv['game_id'];
                $temp[1] = $vv['gtime'];
                $temp[2] = $vv['union_name'];
                $temp[3] = $vv['union_color'];
                $temp[4] = $vv['home_team_name'];
                $temp[5] = $vv['away_team_name'];
                $temp[6] = $vv['score'];
                $temp[7] = $vv['half_score'];
                $temp[8] = $vv['fsw_exp'] !== null?$vv['fsw_exp']:'';
                $temp[9] = getExpWinFb($vv['score'],$vv['fsw_exp']);
                $temp[10] = getScoreWinFb($vv['score']);
                $temp[11] = getBallWinFb($vv['score'],$vv['fsw_ball']);
                $score = explode(',',$vv['score']);
                $temp[12] = ($score[0] + $score[1])%2 == 1?'1':'2';

                $gData[] = $temp;
            }

            $rData[$v['game_id']] = $gData;
        }

        // $time1 = strtotime('8:00:00');
        // $time2 = strtotime('10:32:00');
        // if($time2 < time())
        // {
        //     $startTime = $time1;
        //     $endTime = $time2+3600*24;
        // }
        // else
        // {
        //     $startTime = $time1-3600*24;
        //     $endTime = $time2;
        // }
        // S('panlu_cache',json_encode($rData),$endTime - time());
        /*if(!$panlucache = S('panlu_cache'))
        {
            S('panlu_cache',json_encode($rData),$endTime - time());
        }*/
        S('fb_panlu_cache'.$game_id,$rData,10*3600);
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
            if(!$gids = S('services_todaygames'))
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
                $map['status'] = 1;

                $baseRes = $GameFbinfo->field('id,game_id')->where($map)->order('game_state desc,gtime,bet_code,id')->select();

                if(!empty($baseRes))
                {
                    $gTemp = [];
                    foreach($baseRes as $k=> $v)
                    {
                        $gTemp[] = $v['game_id'];
                    }
                }
                $gids = implode(',',$gTemp);
            }
        }

        if(empty($gids)) return [];

        $rData = [];
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
    public function getOddsInfo($gameId,$company,$half = 0,$odds_type)
    {
        $config = C('FbMongodb');
        $mService = new \Common\Services\MongodbService($config['DB_HOST'],$config['DB_NAME']);
        $mRes = $mService->select('fb_oddshis',["game_id"=>$gameId,'company_id'=>$company,'odds_type'=>$odds_type,'is_half'=>$half])[0]['odds'];
        return $this->oddsData($mRes);
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
        $config = C('FbMongodb');
        $mService = new \Common\Services\MongodbService($config['DB_HOST'],$config['DB_NAME']);
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
//        return [];
        $config = C('FbMongodb');
        $mService = new \Common\Services\MongodbService($config['DB_HOST'],$config['DB_NAME']);
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