<?php
/**
 +------------------------------------------------------------------------------
 * AppfbService   App足球服务类（5.0）
 +------------------------------------------------------------------------------
 * Copyright (c) 2017 http://www.qqty.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>  2017-08-29
 +------------------------------------------------------------------------------
*/
namespace Api520\Services;

class AppfbService
{
    /**
     * 根据公司ID获取赔率各公司初盘指数、即时指数
     * @param  int   $gameId  赛事ID
     * @param  int   $type  1,亚赔；2,欧赔；3,大小
     * @return array  赔率数据
     */
    public function getAllOdds($gameId ,$type = 1)
    {
        if(empty($gameId)) return false;

        $oddsCompany = C('AOB_COMPANY_ID');
        $fbOddshis = M('fbOddshis');

        $map['game_id'] = (int) $gameId;

        $baseRes = $fbOddshis->field('company_id,ahistory,bhistory,ohistory')->where($map)->select();

        $rData = $retData = [];
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
                    $eurComp = C('DB_FB_EUR_COMPANY');
                    $fbEuroodds = M('fbEuroodds');
                    $map['game_id'] = (int) $gameId;

                    $baseRes2 = $fbEuroodds->field('game_id,europe_cname,company_id,from_oddsid,odds_val')->where($map)->select();

                    $oddsGj = ['h'=>['rise'=>0,'equal'=>0,'drop'=>0],'d'=>['rise'=>0,'equal'=>0,'drop'=>0],'a'=>['rise'=>0,'equal'=>0,'drop'=>0]];
                    $sbData = $jcData = $ooData = [];
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
                                    1 => isset($eurComp[$companyID])?$eurComp[$companyID]:$v['europe_cname'],
                                    2 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                                    3 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                                    4 => $endfswOdds[2] == null?'':sprintf("%.2f",$endfswOdds[2]),
                                    5 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                                    6 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                                    7 => $endfswOdds[2] == null?'':sprintf("%.2f",$endfswOdds[2]),
                                    8 => !empty($companyID)?(string)$companyID:'',
                                ];
                                if(isset($eurComp[$companyID]))
                                {
                                    $oddsGj['h']['equal'] = $oddsGj['h']['equal']+2;
                                    $oddsGj['d']['equal'] = $oddsGj['d']['equal']+2;
                                    $oddsGj['a']['equal'] = $oddsGj['a']['equal']+2;
                                }
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
                                    1 => isset($eurComp[$companyID])?$eurComp[$companyID]:$v['europe_cname'],
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
                            if(isset($eurComp[$companyID]))
                            {
                                array_unshift($ooData,$temp);
                                continue;
                            }

                            $rData[] = $temp;
                        }

                    }
                    if(!empty($ooData))
                    {
                        foreach($ooData as $kk=>$vv)
                        {
                            array_unshift($rData,$vv);
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
                            $tj = $this->bTrend($endfswOdds[1],$endfswOdds[1],$endfswOdds[0],$endfswOdds[0],$endfswOdds[2],$endfswOdds[2]);
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
                            $tj = $this->bTrend($startfswOdds[1],$endfswOdds[1],$startfswOdds[0],$endfswOdds[0],$startfswOdds[2],$endfswOdds[2]);
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
            $retData = ['detailOdds' =>$rData, 'aobTrend' =>$oddsGj];
        }
        return $retData;
    }

     /**
     * 根据日期获取亚赔对抗数据
     * @param  int   $date  日期
     * @return array
     */
    public function getAsianCompete($date,$type)
    {
        $time = strtotime(C('fb_bigdata_time'));
        $tDate = date('Y-m-d');
        $rData = [];
        if((time()>$time && $date == $tDate) || (time() < $time && $date == date('Y-m-d',$time-3600*24)))
        {
            $time = strtotime($date." ".C('fb_bigdata_time'));
            $startTime = $time;
            $endTime = $time+3600*24;

            $map['status'] = 1;
            $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
            $res = M('GameFbinfo')->table('qc_game_fbinfo fb')->field('fb.game_id as gid,union_id,union_name,gtime,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,fbs.*')->join('LEFT JOIN qc_fb_strength fbs ON fb.game_id=fbs.game_id')->where($map)->select();

            $rData = [];
            if(!empty($res))
            {
                $gameIds = $insIds = $upIds = [];

                foreach($res as $key=>$val)
                {
                    $gameIds[] = $val['gid'];
                    if(empty($val['game_id']))
                        $insIds[$val['gid']] = $val['gid'];
                    else
                        $upIds[$val['gid']] = $val['gid'];
                }

                $map['game_id'] = array('in',implode(',',$gameIds));
                $res2 = M('FbOddshis')->field('game_id,company_id,ahistory,bhistory')->where($map)->select();
                $oddsData = [];
                if(!empty($res2))
                {
                    foreach($res2 as $key=>$val)
                    {
                        if(!empty($val['ahistory']))
                        {
                            if($type == 1)
                                $oddsData[$val['game_id']][$val['company_id']] = $val['ahistory'];
                            else
                                $oddsData[$val['game_id']][$val['company_id']] = $val['bhistory'];
                        }
                    }
                }
                $abRes = $this->asianBallTj($oddsData,$type);
                $uArr = $iArr = [];
                foreach($abRes as $key=>$val)
                {
                    if(isset($insIds[$val['game_id']]))
                        $iArr[] = $val;
                    else
                        $uArr[] = $val;
                }

                if(!empty($uArr))
                {
                    $sql = $this->upBatchSql('qc_fb_strength','game_id',$uArr);
                    M()->execute($sql);
                }

                if(!empty($iArr))
                {
                    $sql = $this->inBatchSql('qc_fb_strength',$iArr);
                    M()->execute($sql);
                }

                $tSort = $gSort = [];
                foreach($res as $key=>$val)
                {
                    if($val['game_state'] == -1)
                    {
                        $scoreArr = explode('-',$val['score']);
                        $hScore = $scoreArr[0];
                        $aScore = $scoreArr[1];
                    }
                    else
                    {
                        $hScore = '';
                        $aScore = '';
                    }
                    if($type == 1)
                    {
                        if(!isset($abRes[$val['gid']]) || ($abRes[$val['gid']]['asian_trend']<5) ) continue;
                        if($val['fsw_exp'] == '-' || $val['fsw_exp'] == '' || $val['fsw_exp'] == null) continue;
                    }
                    else
                    {
                        if(!isset($abRes[$val['gid']]) || ($abRes[$val['gid']]['ball_trend']<5) ) continue;
                        if($val['fsw_ball'] == '-' || $val['fsw_ball'] == '' || $val['fsw_ball'] == null) continue;
                    }

                    if($type == 1)
                        $val['fsw_exp'] = $this->expToCn($val['fsw_exp']);
                    else
                        $val['fsw_ball'] = $this->expToCn($val['fsw_ball']);

                    $temp = [
                        'gameId'         => (string)$val['gid'],
                        'unionName'      => $val['union_name'],
                        'homeTeamName'   => $val['home_team_name'],
                        'awayTeamName'   => $val['away_team_name'],
                        'homeScore'      => (string)$hScore,
                        'awayScore'      => (string)$aScore,
                        'gtime'          => date('H:i',$val['gtime']),
                    ];
                    if($type == 1)
                    {
                        $temp['handcp'] = $val['fsw_exp'] == null?'':(string)$val['fsw_exp'];
                        $temp['homeTrend'] = isset($abRes[$val['gid']])?(string)$abRes[$val['gid']]['home_asian_trend']:'';
                        $temp['awayTrend'] = isset($abRes[$val['gid']])?(string)$abRes[$val['gid']]['away_asian_trend']:'';
                        $temp['trendDif'] = isset($abRes[$val['gid']])?(string)$abRes[$val['gid']]['asian_trend']:'';
                        $tSort[]  = $abRes[$val['gid']]['asian_trend'];
                    }
                    else
                    {
                        $temp['handcp'] = $val['fsw_ball'] == null?'':(string)$val['fsw_ball'];
                        $temp['homeTrend'] = isset($abRes[$val['gid']])?(string)$abRes[$val['gid']]['home_ball_trend']:'';
                        $temp['awayTrend'] = isset($abRes[$val['gid']])?(string)$abRes[$val['gid']]['away_ball_trend']:'';
                        $temp['trendDif'] = isset($abRes[$val['gid']])?(string)$abRes[$val['gid']]['ball_trend']:'';
                        $tSort[]  = $abRes[$val['gid']]['ball_trend'];
                    }

                    $gSort[]  = $temp['gtime'];
                    $rData[] = $temp;
                }
                array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);
            }
        }
        else
        {
            if($tDate<=$date) return [];
            $time = strtotime($date." ".C('fb_bigdata_time'));
            $startTime = $time;
            $endTime = $time+3600*24;

            $map['status'] = 1;
            $map['game_state'] = -1;
            $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
            $res = M('GameFbinfo')->table('qc_game_fbinfo fb')->field('fb.game_id as gid,union_id,union_name,gtime,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,fbs.*')->join('LEFT JOIN qc_fb_strength fbs ON fb.game_id=fbs.game_id')->where($map)->select();

            if(!empty($res))
            {
                $tSort = $gSort = [];
                foreach($res as $key=>$val)
                {
                    if($val['game_state'] == -1)
                    {
                        $scoreArr = explode('-',$val['score']);
                        $hScore = $scoreArr[0];
                        $aScore = $scoreArr[1];
                    }
                    else
                    {
                        $hScore = '';
                        $aScore = '';
                    }
                    if($type == 1)
                    {
                        if($val['asian_trend']<5) continue;
                        if($val['fsw_exp'] == '-' || $val['fsw_exp'] == '' || $val['fsw_exp'] == null) continue;
                    }
                    else
                    {
                        if($val['ball_trend']<5) continue;
                        if($val['fsw_ball'] == '-' || $val['fsw_ball'] == '' || $val['fsw_ball'] == null) continue;
                    }

                    if($type == 1)
                        $val['fsw_exp'] = $this->expToCn($val['fsw_exp']);
                    else
                        $val['fsw_ball'] = $this->expToCn($val['fsw_ball']);
                    $temp = [
                        'gameId'         => (string)$val['gid'],
                        'unionName'      => $val['union_name'],
                        'homeTeamName'   => $val['home_team_name'],
                        'awayTeamName'   => $val['away_team_name'],
                        'homeScore'      => (string)$hScore,
                        'awayScore'      => (string)$aScore,
                        'gtime'          => date('H:i',$val['gtime']),
                    ];

                    if($type == 1)
                    {
                        $temp['handcp'] = $val['fsw_exp'] == null?'':(string)$val['fsw_exp'];
                        $temp['homeTrend'] = $val['home_asian_trend'] == null?'':(string)$val['home_asian_trend'];
                        $temp['awayTrend'] = $val['away_asian_trend'] == null?'':(string)$val['away_asian_trend'];
                        $temp['trendDif'] = $val['asian_trend'] == null?'':(string)$val['asian_trend'];
                        $tSort[]  = $abRes[$val['gid']]['asian_trend'];
                    }
                    else
                    {
                        $temp['handcp'] = $val['fsw_ball'] == null?'':(string)$val['fsw_ball'];
                        $temp['homeTrend'] = $val['home_ball_trend'] == null?'':(string)$val['home_ball_trend'];
                        $temp['awayTrend'] = $val['away_ball_trend'] == null?'':(string)$val['away_ball_trend'];
                        $temp['trendDif'] = $val['ball_trend'] == null?'':(string)$val['ball_trend'];
                        $tSort[]  = $abRes[$val['gid']]['ball_trend'];
                    }
                    $gSort[]  = $temp['gtime'];
                    $rData[] = $temp;
                }
            }
        }
        return $rData;
    }

    /**
     * 根据日期获取亚赔对抗数据
     * @param  int   $date  日期
     * @return array
     */
    public function getStrengthList($date)
    {
        $time = strtotime(C('fb_bigdata_time'));
        $tDate = date('Y-m-d');
        $rData = [];
        if((time()>$time && $date == $tDate) || (time() < $time && $date == date('Y-m-d',$time-3600*24)))
        {
            $time = strtotime($date." ".C('fb_bigdata_time'));
            $startTime = $time;
            $endTime = $time+3600*24;

            $map['status'] = 1;
            $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
            $res = M('GameFbinfo')->table('qc_game_fbinfo fb')->field('fb.game_id as gid,union_id,union_name,gtime,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,fbs.*')->join('LEFT JOIN qc_fb_strength fbs ON fb.game_id=fbs.game_id')->where($map)->select();

            if(!empty($res))
            {
                $gameIds = $insIds = $upIds = [];

                foreach($res as $key=>$val)
                {
                    $gameIds[] = $val['gid'];
                    if(empty($val['game_id']))
                        $insIds[$val['gid']] = $val['gid'];
                    else
                        $upIds[$val['gid']] = $val['gid'];
                }

                $map2['game_id'] = array('in',implode(',',$gameIds));;
                $map2['company_id'] = 3;
                $oddsRes = M('FbOdds')->field('game_id,exp_value')->where($map2)->select();

                $fswData = [];
                if(!empty($oddsRes))
                {
                    foreach($oddsRes as $key=>$val)
                    {
                        $temp = [];
                        $arr1 = explode('^',$val['exp_value']);
                        $oArr = explode(',',$arr1[0]);
                        if($oArr[4] !='')
                            $fswData[$val['game_id']] = [0=>$oArr[3],1=>$oArr[4],2=>$oArr[5]];
                    }
                }

                $sVal = [];
                $uArr = $iArr = [];
                foreach($res as $key=>$val)
                {
                    $strengTemp = [];
                    if(empty($val['home_strength_fixval']) || empty($val['away_strength_fixval']))
                    {
                        $fixval = $this->getStrengthFixval($val['gid']);
                        $fixval['game_id'] = $val['gid'];
                        $strengTemp = $fixval;
                        $hInt = $fixval['home_strength_fixval'];
                        $aInt = $fixval['away_strength_fixval'];
                    }
                    else
                    {
                        $hInt = $val['home_strength_fixval'];
                        $aInt = $val['away_strength_fixval'];
                        $strengTemp['home_strength_fixval'] = $val['home_strength_fixval'];
                        $strengTemp['away_strength_fixval'] = $val['away_strength_fixval'];
                        $strengTemp['game_id'] = $val['gid'];
                    }

                    if(isset($fswData[$val['game_id']]))
                        $exp = $fswData[$val['game_id']][1];
                    else
                        $exp = $val['fsw_exp'];

                    if($exp != '')
                    {
                        $expTrend = $this->calExpTrend($exp,20);
                        $hInt = $hInt + $expTrend['h'];
                        $aInt = $aInt + $expTrend['a'];
                    }
                    $h = round($hInt/($hInt+$aInt),2)*100;
                    $a = round($aInt/($hInt+$aInt),2)*100;
                    $strengTemp['home_strength'] = $h;
                    $strengTemp['away_strength'] = $a;
                    $strengTemp['strength'] = abs($h-$a);

                    if(isset($insIds[$val['gid']]))
                        $iArr[] = $strengTemp;
                    else
                        $uArr[] = $strengTemp;

                    if($val['game_state'] == -1)
                    {
                        $scoreArr = explode('-',$val['score']);
                        $hScore = $scoreArr[0];
                        $aScore = $scoreArr[1];
                    }
                    else
                    {
                        $hScore = '';
                        $aScore = '';
                    }

                    $temp = [
                        'gameId'         => (string)$val['gid'],
                        'unionName'      => $val['union_name'],
                        'homeTeamName'   => $val['home_team_name'],
                        'awayTeamName'   => $val['away_team_name'],
                        'homeScore'      => (string)$hScore,
                        'awayScore'      => (string)$aScore,
                        'gtime'          => date('H:i',$val['gtime']),
                        'handcp'         => $val['fsw_exp'] == null?'':(string)$val['fsw_exp'],
                        'homeTrend'      => (string)$h,
                        'awayTrend'      => (string)$a,
                        'trendDif'       => (string)$strengTemp['strength'],
                    ];
                    if($temp['trendDif']<10) continue;
                    if($val['fsw_exp'] == '-' || $val['fsw_exp'] == '' || $val['fsw_exp'] == null) continue;

                    $tSort[] = $temp['trendDif'];
                    $gSort[] = $temp['gtime'];
                    $rData[] = $temp;
                }
                array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);

                if(!empty($uArr))
                {
                    $sql = $this->upBatchSql('qc_fb_strength','game_id',$uArr);
                    M()->execute($sql);
                }

                if(!empty($iArr))
                {
                    $sql = $this->inBatchSql('qc_fb_strength',$iArr);
                    M()->execute($sql);
                }

            }

        }
        else
        {
            if($tDate<=$date) return [];
            $time = strtotime($date." ".C('fb_bigdata_time'));
            $startTime = $time;
            $endTime = $time+3600*24;

            $map['status'] = 1;
            $map['game_state'] = -1;
            $map['gtime'] = array(array('gt',$startTime),array('lt',$endTime));
            $res = M('GameFbinfo')->table('qc_game_fbinfo fb')->field('fb.game_id as gid,union_id,union_name,gtime,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,fbs.*')->join('LEFT JOIN qc_fb_strength fbs ON fb.game_id=fbs.game_id')->where($map)->select();

            if(!empty($res))
            {
                $tSort = $gSort = [];
                foreach($res as $key=>$val)
                {
                    $scoreArr = explode('-',$val['score']);
                    $hScore = $scoreArr[0];
                    $aScore = $scoreArr[1];

                    if($val['trendDif']<10) continue;
                    if($val['fsw_exp'] == '-' || $val['fsw_exp'] == '' || $val['fsw_exp'] == null) continue;

                    $val['fsw_exp'] = $this->expToCn($val['fsw_exp']);

                    $temp = [
                        'gameId'         => (string)$val['gid'],
                        'unionName'      => $val['union_name'],
                        'homeTeamName'   => $val['home_team_name'],
                        'awayTeamName'   => $val['away_team_name'],
                        'homeScore'      => (string)$hScore,
                        'awayScore'      => (string)$aScore,
                        'gtime'          => date('H:i',$val['gtime']),
                        'handcp'         => (string)$val['fsw_exp'],
                        'homeTrend'      => (string)$val['home_strength'],
                        'awayTrend'      => (string)$val['away_strength'],
                        'trendDif'       => (string)$strengTemp['strength'],
                    ];
                    $tSort[] = $temp['trendDif'];
                    $gSort[]  = $temp['gtime'];
                    $rData[] = $temp;
                }
                array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);
            }
        }

        return $rData;
    }

     /**
     * 根据赛事ID获取综合实力
     * @param  array   $gameIds  赛事ID
     * @return array  当日即时赛事数据
     */
    public function getStrengthFixval($gameId)
    {
        if(empty($gameId)) return false;

        $rData = [];


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

            return ['home_strength_fixval'=>round($hInt,2),'away_strength_fixval'=>round($aInt,2)];
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

        }

        return $rData;
    }


    /**
     * 亚盘、大小对抗
     * @return array
     */
    public function getAsianCompeteMon($date,$type)
    {
        $time = strtotime(C('fb_bigdata_time'));
        $tDate = date('Y-m-d');

        $mService = mongoService();

        $rData = [];
        //if((time()>$time && $date == $tDate) || (time() < $time && $date == date('Y-m-d',$time-3600*24)))
        //{
        if(S('cache_fb_abcompete_'.$type.':'.$date))
        {
            $rData = S('cache_fb_abcompete_'.$type.':'.$date);
        }
        else
        {
            $time = strtotime($date." ".C('fb_bigdata_time'));
            $startTime = $time;
            $endTime = $time+3600*24;

            $mRes = $mService->select('fb_game',['game_start_timestamp'=>[$mService->cmd('<')=>$endTime,$mService->cmd('>')=>$startTime]],['game_id','union_id','union_name','home_team_name','away_team_name','game_state','score','game_start_timestamp']);

            if(!empty($mRes))
            {
                /*$mRes2 = $mService->select('fb_game',['game_start_timestamp'=>[$mService->cmd('<')=>$endTime,$mService->cmd('>')=>$startTime]],['game_id','match_odds']);
                $odds = [];
                $gameIds = $unionIds = [];
                foreach($mRes2 as $key=>$val)
                {
                    $gameIds[] = $val['game_id'];
                    $unionIds[] = $val['union_id'];
                    if(isset($val['match_odds']))
                    {
                        foreach($val['match_odds'] as $key2=>$val2)
                        {
                            if($type == 1 && $val2[1] == ' ') continue;
                            if($type == 2 && $val2[13] == ' ') continue;
                            $oddsTemp = [
                                0 => $val2[0],
                                1 => changeSnExp($val2[1]),
                                2 => $val2[2],
                                3 => $val2[3],
                                4 => changeSnExp($val2[4]),
                                5 => $val2[5],
                                6 => '',
                                7 => '',
                                8 => '',
                                9 => '',
                                10=> '',
                                11 => '',
                                12 => '',
                                13 => '',
                                14 => '',
                                15 => '',
                                16 => '',
                                17 => '',
                                18 => $val2[12],
                                19 => $val2[13],
                                20 => $val2[14],
                                21 => $val2[15],
                                22 => $val2[16],
                                23 => $val2[17],
                                24 => '',
                                25 => '',
                                26 => '',
                            ];
                            $odds[$val['game_id']][$key2] = $oddsTemp;
                        }
                    }
                }

                if($type == 1)
                    $abRes = $this->asianBallTjMon($odds,1);
                else
                    $abRes = $this->asianBallTjMon($odds,2);

                foreach($mRes as $key=>$val)
                {

                    if($val['game_state'] == -1)
                    {
                        $scoreArr = explode('-',$val['score']);
                        $hScore = $scoreArr[0];
                        $aScore = $scoreArr[1];
                    }
                    else
                    {
                        $hScore = '';
                        $aScore = '';
                    }
                    $temp = [
                        'gameId'         => (string)$val['game_id'],
                        'unionName'      => is_array($val['union_name'])?implode(',',$val['union_name']):$val['union_name'],
                        'homeTeamName'   => implode(',',$val['home_team_name']),
                        'awayTeamName'   => implode(',',$val['away_team_name']),
                        'score'          => $val['game_state'] == -1?$val['score']:'',
                        'gtime'          => date('H:i',$val['game_start_timestamp']),
                    ];

                    if($type == 1)
                    {
                        if(!isset($abRes[$val['game_id']]) || $abRes[$val['game_id']]['asian_trend'] == '') continue;
                        $temp['handcp'] = isset($odds[$val['game_id']][3])?(!empty($odds[$val['game_id']][3][4])?(string)$odds[$val['game_id']][3][4]:(string)$odds[$val['game_id']][3][1]):'';
                        //$temp['handcp'] = $this->expToCn(floatval($temp['handcp']));
                        $temp['handcp'] = (string)changeExpT((string)floatval($temp['handcp']));
                        if($temp['handcp'] != '') $temp['handcp'] = '亚盘:'.$temp['handcp'];
                        $temp['homeTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['home_asian_trend']:'';
                        $temp['awayTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['away_asian_trend']:'';
                        $temp['trendDif'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['asian_trend']:'';
                        if($temp['trendDif']<5) continue;
                        $tSort[]  = $abRes[$val['game_id']]['asian_trend'];
                    }
                    else
                    {
                        if(!isset($abRes[$val['game_id']]) || $abRes[$val['game_id']]['ball_trend'] == '') continue;
                        $temp['handcp'] = isset($odds[$val['game_id']][3])?(!empty($odds[$val['game_id']][3][22])?(string)$odds[$val['game_id']][3][22]:(string)$odds[$val['game_id']][3][19]):'';
                        //$temp['handcp'] = str_replace('让', '', $this->expToCn(floatval($temp['handcp'])));
                        $temp['handcp'] = (string)changeExpT((string)floatval($temp['handcp']));
                        if($temp['handcp'] != '') $temp['handcp'] = '大小:'.$temp['handcp'];
                        $temp['homeTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['home_ball_trend']:'';
                        $temp['awayTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['away_ball_trend']:'';
                        $temp['trendDif'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['ball_trend']:'';
                        if($temp['trendDif']<5) continue;
                        $tSort[]  = $abRes[$val['game_id']]['ball_trend'];
                    }

                    $gSort[] = $temp['gtime'];
                    $rData[] = $temp;
                }
                array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);*/

                $gameIds = $unionIds = [];
                foreach($mRes as $key=>$val)
                {
                    $gameIds[] = $val['game_id'];
                    $unionIds[] = $val['union_id'];
                }

                //$gmRes = $mService->select('fb_odds',['game_id'=>[$mService->cmd('in')=>$gameIds],'is_half'=>0]);
                $gmRes = $mService->select('fb_oddshisWin007',['game_id'=>[$mService->cmd('in')=>$gameIds],'is_half'=>0,'odds_type'=>(int)$type],['game_id','company_id','odds']);

                if(!empty($gmRes))
                {
                    $odds = [];
                    // foreach($gmRes as $key=>$val)
                    // {
                    //     $odds[$val['game_id']][$val['company_id']] = $val['odds'];
                    // }
                    foreach($gmRes as $key=>$val)
                    {
                        $n = count($val['odds']);
                        $oddsTemp = [ 0 => '', 1 => '',2 => '',3 => '',4 => '',5 => '',6 => '',7 => '',8 => '',9 => '',10 => '',11 => '',12 => '',13 => '',14 => '',15 => '',16 => '',17 => '',18 => '',19 => '',20 => '',21 => '',22 => '',23 => '',24 => '',25 => '',26 => '',
                            ];
                        if($type == 1)
                        {
                            if($n==1)
                            {
                                $startOdds = $val['odds'][$n-1];
                                $oddsTemp[0] = $startOdds[0];
                                $oddsTemp[1] = $startOdds[1];
                                $oddsTemp[2] = $startOdds[2];
                            }
                            else
                            {
                                $endOdds = $val['odds'][0];
                                $startOdds = $val['odds'][$n-1];
                                $oddsTemp[0] = $startOdds[0];
                                $oddsTemp[1] = $startOdds[1];
                                $oddsTemp[2] = $startOdds[2];
                                $oddsTemp[3] = $endOdds[0];
                                $oddsTemp[4] = $endOdds[1];
                                $oddsTemp[5] = $endOdds[2];
                            }
                        }
                        else
                        {
                            if($n==1)
                            {
                                $startOdds = $val['odds'][$n-1];
                                $oddsTemp[18] = $startOdds[0];
                                $oddsTemp[19] = $startOdds[1];
                                $oddsTemp[20] = $startOdds[2];
                            }
                            else
                            {
                                $endOdds = $val['odds'][0];
                                $startOdds = $val['odds'][$n-1];
                                $oddsTemp[18] = $startOdds[0];
                                $oddsTemp[19] = $startOdds[1];
                                $oddsTemp[20] = $startOdds[2];
                                $oddsTemp[21] = $endOdds[0];
                                $oddsTemp[22] = $endOdds[1];
                                $oddsTemp[23] = $endOdds[2];
                            }
                        }
                        $odds[$val['game_id']][$val['company_id']] = $oddsTemp;
                    }

                    if($type == 1)
                        $abRes = $this->asianBallTjMon($odds,1);
                    else
                        $abRes = $this->asianBallTjMon($odds,2);

                    foreach($mRes as $key=>$val)
                    {

                        if($val['game_state'] == -1)
                        {
                            $scoreArr = explode('-',$val['score']);
                            $hScore = $scoreArr[0];
                            $aScore = $scoreArr[1];
                        }
                        else
                        {
                            $hScore = '';
                            $aScore = '';
                        }
                        $temp = [
                            'gameId'         => (string)$val['game_id'],
                            'unionName'      => is_array($val['union_name'])?implode(',',$val['union_name']):$val['union_name'],
                            'homeTeamName'   => implode(',',$val['home_team_name']),
                            'awayTeamName'   => implode(',',$val['away_team_name']),
                            'score'          => $val['game_state'] == -1?$val['score']:'',
                            'gtime'          => date('H:i',$val['game_start_timestamp']),
                        ];

                        if($type == 1)
                        {
                            if(!isset($abRes[$val['game_id']]) || $abRes[$val['game_id']]['asian_trend'] == '') continue;
                            $temp['handcp'] = isset($odds[$val['game_id']][3])?(!empty($odds[$val['game_id']][3][4])?(string)$odds[$val['game_id']][3][4]:(string)$odds[$val['game_id']][3][1]):'';
                            //$temp['handcp'] = $this->expToCn(floatval($temp['handcp']));
                            $temp['handcp'] = (string)changeExpT((string)floatval($temp['handcp']));
                            if($temp['handcp'] != '') $temp['handcp'] = '亚盘:'.$temp['handcp'];
                            $temp['homeTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['home_asian_trend']:'';
                            $temp['awayTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['away_asian_trend']:'';
                            $temp['trendDif'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['asian_trend']:'';
                            if($temp['trendDif']<5) continue;
                            $tSort[]  = $abRes[$val['game_id']]['asian_trend'];
                        }
                        else
                        {
                            if(!isset($abRes[$val['game_id']]) || $abRes[$val['game_id']]['ball_trend'] == '') continue;
                            $temp['handcp'] = isset($odds[$val['game_id']][3])?(!empty($odds[$val['game_id']][3][22])?(string)$odds[$val['game_id']][3][22]:(string)$odds[$val['game_id']][3][19]):'';
                            //$temp['handcp'] = str_replace('让', '', $this->expToCn(floatval($temp['handcp'])));
                            $temp['handcp'] = (string)changeExpT((string)floatval($temp['handcp']));
                            if($temp['handcp'] != '') $temp['handcp'] = '大小:'.$temp['handcp'];
                            $temp['homeTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['home_ball_trend']:'';
                            $temp['awayTrend'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['away_ball_trend']:'';
                            $temp['trendDif'] = isset($abRes[$val['game_id']])?(string)$abRes[$val['game_id']]['ball_trend']:'';
                            if($temp['trendDif']<5) continue;
                            $tSort[]  = $abRes[$val['game_id']]['ball_trend'];
                        }

                        $gSort[] = $temp['gtime'];
                        $rData[] = $temp;
                    }
                    array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);
                }
            }
            S('cache_fb_abcompete_'.$type.':'.$date,$rData,120);
        //}
        }
        return $rData;
    }

    /**
     * 亚盘、大小对抗
     * @return array
     */
    public function getAsianCompeteMonT($date,$type)
    {
        $time = strtotime(C('fb_bigdata_time'));
        $tDate = date('Y-m-d');

        $mService = mongoService();

        $rData = [];
        //if((time()>$time && $date == $tDate) || (time() < $time && $date == date('Y-m-d',$time-3600*24)))
        //{
        if(S('cache_fb_abcompete_'.$type.':'.$date))
        {
            $rData = S('cache_fb_abcompete_'.$type.':'.$date);
        }
        else
        {
            $time = strtotime($date." ".C('fb_bigdata_time'));
            $startTime = $time;
            $endTime = $time+3600*24;

            $mRes = $mService->select('fb_game',['game_start_timestamp'=>[$mService->cmd('<')=>$endTime,$mService->cmd('>')=>$startTime]],['game_id','union_id','union_name','home_team_name','away_team_name','game_state','score','game_start_timestamp','aob_trend','match_odds.3', 'match_odds_m_asia', 'match_odds_m_bigsmall']);

            if(!empty($mRes))
            {
                foreach($mRes as $key=>$val)
                {
                    if($val['game_state'] == -1)
                    {
                        $scoreArr = explode('-',$val['score']);
                        $hScore = $scoreArr[0];
                        $aScore = $scoreArr[1];
                    }
                    else
                    {
                        $hScore = '';
                        $aScore = '';
                    }
                    if($type == 1)
                        $exp = changeSnExpTwo(str_replace(' ','',$val['match_odds_m_asia'][3][4]));
                    else
                        $exp = str_replace(' ','',$val['match_odds_m_bigsmall'][3][4]);

                    if($exp == '') continue;

                    $temp = [
                        'gameId'         => (string)$val['game_id'],
                        'unionName'      => is_array($val['union_name'])?implode(',',$val['union_name']):$val['union_name'],
                        'homeTeamName'   => implode(',',$val['home_team_name']),
                        'awayTeamName'   => implode(',',$val['away_team_name']),
                        'score'          => $val['game_state'] == -1?$val['score']:'',
                        'gtime'          => date('H:i',$val['game_start_timestamp']),
                        'handcp'         => $exp,
                    ];

                    if($type == 1)
                    {
                        if($temp['handcp'] != '') $temp['handcp'] = '亚盘:'.$temp['handcp'];
                        $temp['homeTrend'] = isset($val['aob_trend'])?(string)$val['aob_trend']['a']['home_trend']:'';
                        $temp['awayTrend'] = isset($val['aob_trend'])?(string)$val['aob_trend']['a']['away_trend']:'';
                        $temp['trendDif'] = isset($val['aob_trend'])?(string)$val['aob_trend']['a']['trend_dif']:'';
                        if(($temp['trendDif']<5) || $temp['trendDif'] == '') continue;
                        $tSort[]  = $temp['trendDif'];
                    }
                    else
                    {
                        if($temp['handcp'] != '') $temp['handcp'] = '大小:'.$temp['handcp'];
                        $temp['homeTrend'] = isset($val['aob_trend'])?(string)$val['aob_trend']['b']['home_trend']:'';
                        $temp['awayTrend'] = isset($val['aob_trend'])?(string)$val['aob_trend']['b']['away_trend']:'';
                        $temp['trendDif'] = isset($val['aob_trend'])?(string)$val['aob_trend']['b']['trend_dif']:'';
                        if(($temp['trendDif']<5) || $temp['trendDif'] == '') continue;
                        $tSort[]  = $temp['trendDif'];
                    }

                    $gSort[] = $temp['gtime'];
                    $rData[] = $temp;
                }
                array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);
            }
            S('cache_fb_abcompete_'.$type.':'.$date,$rData,20);
        }
        //}
        return $rData;
    }
	
	/**
	 * 根据日期获取亚赔对抗数据
	 * @param  int   $date  日期
	 * @return array
	 */
	public function getStrengthListMon($date)
	{
		$mService = mongoService();
		
		$time = strtotime(C('fb_bigdata_time'));
		$tDate = date('Y-m-d');
		$rData = [];
		
		if(S('cache_fb_strlist:'.$date))
		{
			$rData = S('cache_fb_strlist:'.$date);
		}
		else
		{
			$time = strtotime($date." ".C('fb_bigdata_time'));
			$startTime = $time;
			$endTime = $time+3600*24;
			
			$mRes = $mService->select('fb_game',['game_start_timestamp'=>[$mService->cmd('<')=>$endTime,$mService->cmd('>')=>$startTime]],['game_id','union_id','union_name','home_team_name','away_team_name','game_state','score', 'game_starttime','game_start_timestamp','statistics','home_team_id','away_team_id','let_goal']);
			
			if(!empty($mRes))
			{
				$gameIds = [];
				
				foreach($mRes as $key=>$val)
				{
					$gameIds[] = $val['game_id'];
				}
				
				$omRes = $mService->select('fb_odds',['game_id'=>[$mService->cmd('in')=>$gameIds],'company_id'=>3,'is_half'=>0],['game_id','odds']);
				$fswData = [];
				if(!empty($omRes))
				{
					foreach($omRes as $key=>$val)
					{
						if($val['odds'][4] !='')
							$fswData[$val['game_id']] = [0=>$val['odds'][3],1=>$val['odds'][4],2=>$val['odds'][5]];
						else if($val['odds'][1] !='')
							$fswData[$val['game_id']] = [0=>$val['odds'][0],1=>$val['odds'][1],2=>$val['odds'][2]];
					}
				}
				
				foreach($mRes as $key=>$val)
				{
					$start_time = $val['game_start_timestamp'] ? : $val['game_starttime']->sec;
					
					//盘口
					if(isset($fswData[$val['game_id']]))
						$exp = $fswData[$val['game_id']][1];
					else
						$exp = $val['let_goal'];
					
					if(isset($val['statistics']))
					{
                        //数据库有计算数据
                        $statistics = $val['statistics'];
                        $hInt = $statistics['home_strength_fixval'];
                        $aInt = $statistics['away_strength_fixval'];
                        $h    = $statistics['home_strength'];
                        $a    = $statistics['away_strength'];
                        $strengthDif = $statistics['strength'];
					}else{
                        //没有数据进行计算存表
                        $fixval = $this->getStrengthFixvalMon($val['home_team_id'],$val['away_team_id'], $val['union_id'],$start_time, $val['game_id']);
                        $hInt = $fixval['home_strength_fixval'] === null?0:$fixval['home_strength_fixval'];
                        $aInt = $fixval['away_strength_fixval'] === null?0:$fixval['away_strength_fixval'];
                        $h = round($hInt/($hInt+$aInt),2)*100;
                        $a = round($aInt/($hInt+$aInt),2)*100;
                        $strengthDif = abs($h-$a);
                        $mService->update('fb_game', array('statistics'=>['home_strength_fixval'=>$hInt,'away_strength_fixval'=>$aInt,'home_strength'=>$h,'away_strength'=>$a,'strength'=>$strengthDif]), array('game_id'=>$val['game_id']),'set');
                    }
					
					$temp = [
						'gameId'         => (string)$val['game_id'],
						'unionName'      => is_array($val['union_name'])?implode(',',$val['union_name']):$val['union_name'],
						'homeTeamName'   => implode(',',$val['home_team_name']),
						'awayTeamName'   => implode(',',$val['away_team_name']),
						'score'          => $val['game_state'] == -1?$val['score']:'',
						'gtime'          => date('H:i',$val['game_start_timestamp']),
						'handcp'         => $exp === null?'':(string)$exp,
						'homeTrend'      => (string)$h,
						'awayTrend'      => (string)$a,
						'trendDif'       => (string)$strengthDif,
					];

					if($temp['handcp'] != '')
					{
						$temp['handcp'] = '亚盘:'.changeExpT((string)floatval($temp['handcp']));
					}
					
					if(($temp['trendDif']<10) || $temp['trendDif'] == 100) continue;
					if($val['let_goal'] == '-' || $val['let_goal'] === '' || $val['let_goal'] === null) continue;
					
					$tSort[] = $temp['trendDif'];
					$gSort[] = $temp['gtime'];
					$rData[] = $temp;
				}
				array_multisort ($tSort ,SORT_DESC ,$gSort ,SORT_ASC , $rData);
				S('cache_fb_strlist:'.$date,$rData,60);
			}
		}
		return $rData;
	}

    /**
     * 根据赛事ID获取综合实力
     * @param  array   $gameIds  赛事ID
     * @return array  当日即时赛事数据
     */
    public function getStrengthFixvalMon($htId, $atId, $union_id, $start_time,$game_id = null)
    {
	    $mService = mongoService();
	    $hInt1 = $aInt1 = 0;
	    # 取比赛时间 如果没有 那么取当天时间
	    $start_time = $start_time ? new \MongoDate($start_time) :  new \MongoDate(strtotime(date("Ymd")));
	    
	    #近期战力
	    $renRes1 = $mService->select('fb_game',[
		    $mService->cmd('or') => [['home_team_id'=>$htId], ['away_team_id'=>$htId]],
		    "game_state" => -1,
		    "game_id" => [$mService->cmd("notin") => [(int) $game_id]],
		    "game_starttime" => [$mService->cmd('<') => $start_time],
		    'union_id' => (int) $union_id,
	    ],['game_id', 'home_team_name','home_team_id','away_team_id', 'union_name', 'away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
	    $renRes1 = $this->cleanData($renRes1);
	
	    if(!empty($renRes1))
	    {
		    $hIntTemp1 = $this->TestcalRecentGame($htId,$renRes1,20);
		    $hInt1 = $hInt1 + $hIntTemp1;
	    }
	
	    $renRes2 = $mService->select('fb_game',[
		    $mService->cmd('or') => [['home_team_id'=>$atId], ['away_team_id'=>$atId]],
		    "game_state" => -1,
		    "game_id" => [$mService->cmd("notin") => [(int) $game_id]],
		    "game_starttime" => [$mService->cmd('<') => $start_time],
		    'union_id' => (int) $union_id,
	    ],['game_id', 'home_team_name','home_team_id','away_team_id', 'union_name','away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
	    $renRes2 = $this->cleanData($renRes2);
	    
	    if(!empty($renRes2))
	    {
		    $aIntTemp1 = $this->TestcalRecentGame($atId,$renRes2,20);
		    $aInt1 = $aInt1 + $aIntTemp1;
	    }

	    #主客战绩
	    $res = $mService->select('fb_game',[
		    'home_team_id'=>$htId,
		    "game_state" => -1,
		    "game_id" => [$mService->cmd("notin") => [(int) $game_id]],
		    "game_starttime" => [$mService->cmd('<') => $start_time],
		    'union_id' => (int) $union_id,
	    ],['game_id','home_team_id', 'home_team_name','away_team_id', 'union_name','away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
	    $res = $this->cleanData($res);
	
	    if(!empty($res))
	    {
		    $hIntTemp1 = $this->TestcalRecentGame($htId,$res,10);
		    $hInt1 = $hInt1 + $hIntTemp1;
	    }
	
	    $res = $mService->select('fb_game',[
		    'away_team_id'=>$atId,
		    "game_state" => -1,
		    "game_id" => [$mService->cmd("notin") => [(int) $game_id]],
		    "game_starttime" => [$mService->cmd('<') => $start_time],
		    'union_id' => (int) $union_id,
	    ],['game_id','home_team_id', 'home_team_name','away_team_id', 'union_name','away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
	    $res = $this->cleanData($res);
	
	
	    if(!empty($res))
	    {
		    $aIntTemp1 = $this->TestcalRecentGame($atId,$res,10);
		    $aInt1 = $aInt1 + $aIntTemp1;
	    }
	
	    #历史交战
	    $hisRes = $mService->select('fb_game',[
		    $mService->cmd('or') => [['home_team_id'=>$htId,'away_team_id'=>$atId], ['away_team_id'=>$htId,'home_team_id'=>$atId]],
		    "game_state" => -1,
		    "game_id" => [$mService->cmd("notin") => [(int) $game_id]],
		    "game_starttime" => [$mService->cmd('<') => $start_time],
	    ],['game_id','home_team_id', 'home_team_name','away_team_id' , 'away_team_name','score', 'union_name'],
		    $sort=['game_start_timestamp'=>-1],10);
	    $hisRes = $this->cleanData($hisRes);
	
	    if (!empty($hisRes)) {
		    $hIntTemp = $aIntTemp = 0;
		    foreach($hisRes as $k=>$v)
		    {
			    $score = explode('-',$v['score']);
			    if($v['home_team_id'] == $htId)
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
		    $rate = 0.2;
		    $hIntTemp1=  round($hIntTemp*$rate,2);
		    $aIntTemp1 =  round($aIntTemp*$rate,2);
		    $hInt1 = $hInt1 + $hIntTemp1;
		    $aInt1 = $aInt1 + $aIntTemp1;
	    }
	
	    $homeAttDef = $this->attDefNum($htId, $renRes1);
	    $hInt1 = $hInt1 + $homeAttDef['att'] + $homeAttDef['def'];
	
	    $awayAttDef = $this->attDefNum($atId, $renRes2);
	    $aInt1 = $aInt1 + $awayAttDef['att'] + $awayAttDef['def'];
	    
	    return ['home_strength_fixval'=>round($hInt1,2),'away_strength_fixval'=>round($aInt1,2)];
    }
	
	
	
	/**
	 * 根据赛事ID获取综合实力 测试接口
	 */
	public function getStrengthFixvalMonTest($htId, $atId, $union_id, $start_time,$game_id = null)
	{
		$mService = mongoService();
		$hInt = $aInt = 0;
		$hInt1 = $aInt1 = 0;
		
		echo "主队战力开始 : ".$hInt;
		echo "客队战力开始 : ".$aInt;
		$start_time = $start_time ? new \MongoDate($start_time) :  new \MongoDate(strtotime(date("Ymd")));
		#近期战力
		$renRes1 = $mService->select('fb_game',[
			$mService->cmd('or') => [['home_team_id'=>$htId], ['away_team_id'=>$htId]],
			"game_state" => -1,
			"game_id" => [$mService->cmd("notin") => [(int) $game_id]],
			"game_starttime" => [$mService->cmd('<') => $start_time],
			'union_id' => (int) $union_id,
		],['game_id', 'home_team_name','home_team_id','away_team_id', 'union_name', 'away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
		$renRes1 = $this->cleanData($renRes1);
		
		echo "<br>";
		foreach ($renRes1 as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]." ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}

		if(!empty($renRes1))
		{
			$hIntTemp1 = $this->TestcalRecentGame($htId,$renRes1,20);
			$hInt1 = $hInt1 + $hIntTemp1;
		}
		
		echo "<br>";
		echo "计算主队近期战力 增加值".$hIntTemp1." 增加后 : ".$hInt1;
		
		
		echo "<br>";
		echo "<br>";
		
		$renRes2 = $mService->select('fb_game',[
			$mService->cmd('or') => [['home_team_id'=>$atId], ['away_team_id'=>$atId]],
			"game_state" => -1,
			"game_id" => [$mService->cmd("notin") => [(int) $game_id]],
			"game_starttime" => [$mService->cmd('<') => $start_time],
			'union_id' => (int) $union_id,
		],['game_id', 'home_team_name','home_team_id','away_team_id', 'union_name','away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
		$renRes2 = $this->cleanData($renRes2);
		
		echo "<br>";
		foreach ($renRes2 as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]."  ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}
		
		if(!empty($renRes2))
		{
			$aIntTemp1 = $this->TestcalRecentGame($atId,$renRes2,20);
			$aInt1 = $aInt1 + $aIntTemp1;
		}
		
		echo "<br>";
		echo "计算客队近期战力 增加值".$aIntTemp1." 增加后 : ".$aInt1;
		
		echo "<br>";
		echo "<br>";
		echo "<br>";
		echo "<br>";
		#主客战绩
		$res = $mService->select('fb_game',[
			'home_team_id'=>$htId,
			"game_state" => -1,
			"game_id" => [$mService->cmd("notin") => [(int) $game_id]],
			"game_starttime" => [$mService->cmd('<') => $start_time],
			'union_id' => (int) $union_id,
		],['game_id','home_team_id', 'home_team_name','away_team_id', 'union_name','away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
		$res = $this->cleanData($res);
		
		
		echo "<br>";
		foreach ($res as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]."  ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}
		
		if(!empty($res))
		{
			$hIntTemp1 = $this->TestcalRecentGame($htId,$res,10);
			$hInt1 = $hInt1 + $hIntTemp1;
		}
		echo "<br>";
		echo "计算主队主客战绩 增加值".$hIntTemp1." 增加后 : ".$hInt1;
		
		echo "<br>";
		echo "<br>";
		
		$res = $mService->select('fb_game',[
			'away_team_id'=>$atId,
			"game_state" => -1,
			"game_id" => [$mService->cmd("notin") => [(int) $game_id]],
			"game_starttime" => [$mService->cmd('<') => $start_time],
			'union_id' => (int) $union_id,
		],['game_id','home_team_id', 'home_team_name','away_team_id', 'union_name','away_team_name','score'],$sort=['game_start_timestamp'=>-1],10);
		$res = $this->cleanData($res);
		
		echo "<br>";
		foreach ($res as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]."  ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}

		if(!empty($res))
		{
			$aIntTemp1 = $this->TestcalRecentGame($atId,$res,10);
			$aInt1 = $aInt1 + $aIntTemp1;
		}
		
		echo "<br>";
		echo "计算客队主客战绩 增加值".$aIntTemp1." 增加后 : ".$aInt1;
		
		echo "<br>";
		echo "<br>";
		echo "<br>";
		echo "<br>";
		
		#历史交战
		$hisRes = $mService->select('fb_game',[
			$mService->cmd('or') => [['home_team_id'=>$htId,'away_team_id'=>$atId], ['away_team_id'=>$htId,'home_team_id'=>$atId]],
			"game_state" => -1,
			"game_id" => [$mService->cmd("notin") => [(int) $game_id]],
			"game_starttime" => [$mService->cmd('<') => $start_time],
		],['game_id','home_team_id', 'home_team_name','away_team_id' , 'away_team_name','score', 'union_name'],
			$sort=['game_start_timestamp'=>-1],10);
		$hisRes = $this->cleanData($hisRes);
		
		echo "<br>";
		foreach ($hisRes as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]."  ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}
		echo "<br>";
		echo "<br>";
		
		if (!empty($hisRes)) {
			$hIntTemp = $aIntTemp = 0;
			foreach($hisRes as $k=>$v)
			{
				$score = explode('-',$v['score']);
				if($v['home_team_id'] == $htId)
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
			$rate = 0.2;
			$hIntTemp1=  round($hIntTemp*$rate,2);
			$aIntTemp1 =  round($aIntTemp*$rate,2);
			$hInt1 = $hInt1 + $hIntTemp1;
			$aInt1 = $aInt1 + $aIntTemp1;
			echo "计算主队历史战绩 增加值".$hIntTemp1." 增加后 : ".$hInt1;
			echo "<br>";
			echo "计算客队历史战绩 增加值".$aIntTemp1." 增加后 : ".$aInt1;
		}
		
		echo "<br>";
		echo "<br>";
		foreach ($renRes1 as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]."  ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}
		echo "<br>";
		
		$homeAttDef = $this->attDefNum($htId, $renRes1);
		$hInt1 = $hInt1 + $homeAttDef['att'] + $homeAttDef['def'];
		$tempAtt =  $homeAttDef['att'] * 4;
		$tempDef =  $homeAttDef['def'] * 4;
		
		echo "<br>";
		echo "计算主队 攻击力分数 : ".$tempAtt."分 攻击力 攻击力增加值 ".$homeAttDef['att']." 防御力分数 : ".$tempDef."分"."  防守力增加值 ".$homeAttDef['def']."  增加后 : ".$hInt1;
		echo "<br>";
		
		echo "<br>";
		echo "<br>";
		foreach ($renRes2 as $key => $value) {
			echo "比赛 id 为 : ".$value['game_id']."  "."联赛名称为 : ".$value['union_name'][0]."  ".$value['home_team_name'][0]." ".$value['score']." ".$value['away_team_name'][0];
			echo "<br>";
		}
		echo "<br>";

		$awayAttDef = $this->attDefNum($atId, $renRes2);
		$aInt1 = $aInt1 + $awayAttDef['att'] + $awayAttDef['def'];
		$tempaAtt =  $awayAttDef['att'] * 4;
		$tempaDef =  $awayAttDef['def'] * 4;
		echo "<br>";
		echo "计算主队 攻击力分数 : ".$tempaAtt."分 攻击力 攻击力增加值 ".$awayAttDef['att']." 防御力分数 : ".$tempaDef."分"."  防守力增加值 ".$awayAttDef['def']."  增加后 : ".$aInt1;
		echo "<br>";
		
		return ['home_strength_fixval'=>round($hInt1,2),'away_strength_fixval'=>round($aInt1,2)];
	}
    
    
    


    /**
     * 根据game_id获取3v1初盘即时赔率
     * @return array
     */
    public function getAbByIdMon($gameId,$type)
    {
        if(empty($gameId) || empty($type)) return false;

        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId,'is_half'=>0,'odds_type' =>(int)$type];
        $gmRes = $mService->select('fb_oddshisWin007',$map);

        $rData = $retData = [];
        if(!empty($gmRes ))
        {
            $sbData = [];
            $oddsGj = ['h'=>0,'a'=>0];

            foreach($gmRes as $key=>$val)
            {
                if(empty($val['odds']) || $val['company_id'] == 18) continue;
                $n = count($val['odds']);
                if($n == 1)
                {
                    $endfswOdds = $val['odds'][0];
                    $temp = [
                        0 => $val['company_name'],
                        1 => $endfswOdds[0]== null?'':$endfswOdds[0],
                        2 => changeExp($endfswOdds[1]),
                        3 => $endfswOdds[2]== null?'':$endfswOdds[2],
                        4 => $endfswOdds[0] == null?'':$endfswOdds[0],
                        5 => changeExp($endfswOdds[1]),
                        6 => $endfswOdds[2]== null?'':$endfswOdds[2],
                        7 => (string)$val['company_id'],
                    ];
                    if($type == 1 )
                        $tj = $this->abTrend($endfswOdds[1],$endfswOdds[1],$endfswOdds[0],$endfswOdds[0],$endfswOdds[2],$endfswOdds[2]);
                    else
                        $tj = $this->bTrend($endfswOdds[1],$endfswOdds[1],$endfswOdds[0],$endfswOdds[0],$endfswOdds[2],$endfswOdds[2]);
                }
                else
                {
                    $startfswOdds = $val['odds'][$n-1];
                    $endfswOdds = $val['odds'][0];
                    $temp = [
                        0 => $val['company_name'],
                        1 => $startfswOdds[0]== null?'':$startfswOdds[0],
                        2 => changeExp($startfswOdds[1]),
                        3 => $startfswOdds[2]== null?'':$startfswOdds[2],
                        4 => $endfswOdds[0] == null?'':$endfswOdds[0],
                        5 => changeExp($endfswOdds[1]),
                        6 => $endfswOdds[2]== null?'':$endfswOdds[2],
                        7 => (string)$val['company_id'],
                    ];
                    if($type == 1 )
                        $tj = $this->abTrend($startfswOdds[1],$endfswOdds[1],$startfswOdds[0],$endfswOdds[0],$startfswOdds[2],$endfswOdds[2]);
                    else
                        $tj = $this->bTrend($startfswOdds[1],$endfswOdds[1],$startfswOdds[0],$endfswOdds[0],$startfswOdds[2],$endfswOdds[2]);
                }
                $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                $oddsGj['a'] = $oddsGj['a'] + $tj['a'];

                if($val['company_id'] == 3)
                    $sbData = $temp;
                else
                    $rData[] = $temp;
            }
            if(!empty($sbData)) array_unshift($rData,$sbData);
            if(!empty($rData)) $retData = ['detailOdds' =>$rData, 'aobTrend' =>$oddsGj];
        }
        return $retData;
    }

    /**
     * 根据game_id获取3v1初盘即时赔率
     * @param gameId 赛程id
     * @param type   类型 1:亚指 2:大小
     * @return array
     */
    public function getAbByIdMonT($gameId,$type)
    {
        if(empty($gameId) || empty($type)) return false;

        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId];

        //根据类型设置历史同赔类型
        switch ($type) {
            case '1':
                $same_name = 'same_asia';
                break;
            case '2':
                $same_name = 'same_bigsmall';
                break;
        }

        $gmRes = $mService->select('fb_game',$map,['game_id','match_odds','aob_trend', 'match_odds_m_asia', 'match_odds_m_bigsmall','same_odds.'.$same_name.'.all']);

        $same_odds = $gmRes[0]['same_odds'][$same_name]['all']; //历史同赔数据
        
        /*
        if(!empty($gmRes) && !empty($gmRes[0]['match_odds']) && !empty($gmRes[0]['odds_history']))
        {
            $tsw = $gmRes[0]['odds_history'];
            foreach ($tsw as $key => &$val) {
                foreach ($val as $k => &$v) {
                    foreach ($v as $zk => $zv) {
                        if ($zv[6] == "滚") {
                            unset($v[$zk]);
                        }
                    }
                }
            }

            foreach ($tsw as $k => $v) {
                $only_odds[$k][0] = current($v[0]);
                $only_odds[$k][1] = current($v[1]);
                $only_odds[$k][2] = current($v[2]);
            }

            foreach ($only_odds as $ko => $vo) {
                //如果亚赔 即时盘存在  替换match_odds里面的亚赔即时盘
                if ($only_odds[$ko][0]) {
                    $gmRes[0]['match_odds'][$ko][3] = $only_odds[$ko][0][2];
                    $gmRes[0]['match_odds'][$ko][4] = $only_odds[$ko][0][3];
                    $gmRes[0]['match_odds'][$ko][5] = $only_odds[$ko][0][4];
                }
                //如果大小球即时盘存在 替换match_odds里面的大小球即时盘
                if ($only_odds[$ko][1]) {
                    $gmRes[0]['match_odds'][$ko][15] = $only_odds[$ko][1][2];
                    $gmRes[0]['match_odds'][$ko][16] = $only_odds[$ko][1][3];
                    $gmRes[0]['match_odds'][$ko][17] = $only_odds[$ko][1][4];
                }
            }
        }

        */

        //使用捷报m站即时盘
        if(!empty($gmRes) && !empty($gmRes[0]['match_odds']) && !empty($gmRes[0]['match_odds_m_asia']) && !empty($gmRes[0]['match_odds_m_bigsmall'])) {
            foreach ($gmRes[0]['match_odds_m_asia'] as $k => $v) {
                if ($gmRes[0]['match_odds_m_asia'][$k][0]) {
                    $gmRes[0]['match_odds'][$k][3] = $gmRes[0]['match_odds_m_asia'][$k][3];
                    $gmRes[0]['match_odds'][$k][4] = $gmRes[0]['match_odds_m_asia'][$k][4];
                    $gmRes[0]['match_odds'][$k][5] = $gmRes[0]['match_odds_m_asia'][$k][5];
                }
            }

            foreach ($gmRes[0]['match_odds_m_bigsmall'] as $k => $v) {
                if ($gmRes[0]['match_odds_m_bigsmall'][$k][0]) {
                    $gmRes[0]['match_odds'][$k][15] = $gmRes[0]['match_odds_m_bigsmall'][$k][3];
                    $gmRes[0]['match_odds'][$k][16] = $gmRes[0]['match_odds_m_bigsmall'][$k][4];
                    $gmRes[0]['match_odds'][$k][17] = $gmRes[0]['match_odds_m_bigsmall'][$k][5];
                }
            }
        }

        $rData = $retData = [];
        if(!empty($gmRes) && !empty($gmRes[0]['match_odds']))
        {
            $oddsCompany = C('AOB_COMPANY_ID');

            $sbData = [];
            $oddsGj = ['h'=>0,'a'=>0];
            $match_odds = $gmRes[0]['match_odds'];
            //根据配置重新排序
            $match_odds_all = [];
            foreach ($oddsCompany as $k => $v) {
                if(isset($match_odds[$k])){
                    $match_odds_all[$k] = $match_odds[$k];
                }
            }
            foreach($match_odds_all as $key=>$val)
            {
                if($type == 1)
                {
                    if($val[1] == ' ') continue;
                    $temp = [
                        0 => $oddsCompany[$key],
                        1 => $val[0]== null?'':str_replace(' ','',$val[0]),
                        2 => changeSnExpTwo(str_replace(' ','',$val[1])),
                        3 => $val[2]== null?'':str_replace(' ','',$val[2]),
                        4 => $val[3] == null?'':str_replace(' ','',$val[3]),
                        5 => changeSnExpTwo(str_replace(' ','',$val[4])),
                        6 => $val[5]== null?'':str_replace(' ','',$val[5]),
                        7 => (string)$key,
                    ];

                    $tj = $this->abTrend(changeExpStrToNum($temp[2]),changeExpStrToNum($temp[5]),$temp[1],$temp[4],$temp[3],$temp[6]);
                    $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                    $oddsGj['a'] = $oddsGj['a'] + $tj['a'];

                    if($val['company_id'] == 3)
                        $sbData = $temp;
                    else
                        $rData[] = $temp;
                }
                else
                {
                    if($val[13] == ' ') continue;
                    $temp = [
                        0 => $oddsCompany[$key],
                        1 => $val[12]== null?'':str_replace(' ','',$val[12]),
                        2 => $val[13]== null?'':changeSnExpTwo(str_replace(' ','',$val[13])),
                        3 => $val[14]== null?'':str_replace(' ','',$val[14]),
                        4 => $val[15]== null?'':str_replace(' ','',$val[15]),
                        5 => $val[16]== null?'':changeSnExpTwo(str_replace(' ','',$val[16])),
                        6 => $val[17]== null?'':str_replace(' ','',$val[17]),
                        7 => (string)$key,
                    ];
                    $tj = $this->bTrend(changeExpStrToNum($temp[2]),changeExpStrToNum($temp[5]),$temp[1],$temp[4],$temp[3],$temp[6]);
                    $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                    $oddsGj['a'] = $oddsGj['a'] + $tj['a'];
                    if($val['company_id'] == 3)
                        $sbData = $temp;
                    else
                        $rData[] = $temp;
                }

            }
            if(!empty($sbData)) array_unshift($rData,$sbData);
            if(!empty($rData)) $retData = ['detailOdds' =>$rData, 'aobTrend' =>$oddsGj];
            if($type == 1)
                $abKey = 'a';
            else
                $abKey = 'b';

            if(!isset($gmRes[0]['aob_trend']))
            {
                $AobTrend = ['a'=>['home_trend'=>0,'away_trend'=>0,'trend_dif'=>0],'o'=>['home_trend'=>0,'away_trend'=>0,'trend_dif'=>0],'b'=>['home_trend'=>0,'away_trend'=>0,'trend_dif'=>0]];
                $AobTrend[$abKey] = [
                    'home_trend' => $oddsGj['h'],
                    'away_trend' => $oddsGj['a'],
                    'trend_dif' => abs($oddsGj['h']-$oddsGj['a']),
                ];
                $upFlag = true;
            }
            else
            {
                $AobTrend = $gmRes[0]['aob_trend'];
                if($AobTrend[$abKey]['home_trend'] != $oddsGj['h'] || $AobTrend[$abKey]['away_trend'] != $oddsGj['a'] || $AobTrend[$abKey]['trend_dif'] != abs($oddsGj['h']-$oddsGj['a'])) $upFlag = true;
                $AobTrend[$abKey] = [
                    'home_trend' => $oddsGj['h'],
                    'away_trend' => $oddsGj['a'],
                    'trend_dif' => abs($oddsGj['h']-$oddsGj['a']),
                ];
            }
            if($upFlag == true)
            {
                $upRes = $mService->update('fb_game', ['aob_trend'=>$AobTrend], array('game_id'=>(int)$gameId),'set');
            }
        }
        if(!empty($same_odds)){
            //历史同赔数据
            $sameData['h'] = $same_odds[0];
            $sameData['f'] = $same_odds[1];
            $sameData['a'] = $same_odds[2];
            $sameData['n'] = count($same_odds[3]).'场'.C('alikeHistoryCompany')['companyName'];
            $retData['same_odds'] = $sameData;
        }
        return $retData;
    }


    /**
     * 根据game_id获取亚赔大小历史赔率记录
     * @return array
     */
    public function getAbhisByIdMon($gameId,$companyID,$type)
    {
        if($type == 2)
            $type = 3;
        else if($type == 3)
            $type = 2;

        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId,'is_half'=>0,'odds_type' =>(int)$type,'company_id' =>(int)$companyID];
        $gmRes = $mService->select('fb_oddshisWin007',$map);

        $rData = $aData =[];
        if(!empty($gmRes) && !empty($gmRes[0]['odds']))
        {
            foreach($gmRes[0]['odds'] as $key=>$val)
            {
                $val[1] = changeExp($val[1]);
                $temp  = [
                    0 => $val[0] == null?'':$val[0],
                    1 => $val[1] == null?'':changeExp($val[1]),
                    2 => $val[2] == null?'':$val[2],
                    3 => $val[3] == null?'':date('Y-m-d H:i',strtotime($val[3])),
                ];

                $aData[] = $temp;
            }
            $rData = array_reverse($aData);
        }
        return $rData;
    }

    /**
     * 根据game_id获取亚赔大小历史赔率记录
     * @return array
     */
    public function getAbhisByIdMonT($gameId,$companyID,$type)
    {
        if($type == 1)
            $type = 0;
        else if($type == 3)
            $type = 1;

        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId];
        $gmRes = $mService->select('fb_game',$map,['game_id','odds_history']);

        $rData = [];
        if(!empty($gmRes) && !empty($gmRes[0]['odds_history']))
        {
            $date = date('Y');
            $time = time();
            $oddsRes = $gmRes[0]['odds_history'];
            $aData = [];
            if(isset($oddsRes[$companyID]) && isset($oddsRes[$companyID][$type]) && !empty($oddsRes[$companyID][$type]))
            {
                foreach($oddsRes[$companyID][$type] as $key=>$val)
                {
                    $only_time = $val[3];
                    if($type == 0) $val[3] = changeSnExpTwo($val[3]);
                    if((strtotime(date('Y').'-'.$val[5]) - $time) > 3600*24*30)
                        $tTime = ($date-1).'-'.$val[5];
                    else
                        $tTime = $date.'-'.$val[5];
                    $temp  = [
                        0 => $val[2] == null?'':$val[2],
                        1 => $val[3] == null?'':$val[3],
                        2 => $val[4] == null?'':$val[4],
                        3 => $val[5] == null?'':$tTime,
                        4 => $val[6] == null?'':$val[6]
                    ];
                    if ($temp[4] == "滚"){
                        continue;
                    }
                    unset($temp[4]);

                    //添加特殊筛选 42:"18Bet" 45:"ManbetX"  两家公司亚赔 和 大小清洗
                    if ($companyID == 42 || $companyID == 45) {
                        $temp  = [
                            0 => $val[0] == null?'':$val[0],
                            1 => $val[1] == null?'':$val[1],
                            2 => $val[2] == null?'':$val[2],
                            3 => $only_time == null?'':date('Y-m-d H:i', strtotime($only_time))
                        ];
                    }
                    $aData[] = $temp;
                }
            }

            if(!empty($aData))
                $rData = array_reverse($aData);
            else
                $rData = $aData;
        }
        return $rData;
    }

    /**
     * 根据game_id获取3v1初盘即时赔率(百家欧赔)
     * @return array
     */
    public function getEuroByIdMon($gameId)
    {
        if(empty($gameId)) return false;
        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId];
        $gmRes = $mService->select('fb_euroodds',$map,['game_id','euroodds']);

        $rData = [];
        if(!empty($gmRes[0]['euroodds']))
        {
            $oddsGj = ['h'=>['rise'=>0,'equal'=>0,'drop'=>0],'d'=>['rise'=>0,'equal'=>0,'drop'=>0],'a'=>['rise'=>0,'equal'=>0,'drop'=>0]];
            foreach($gmRes[0]['euroodds'] as $key=>$val)
            {
                $temp1 = $temp2 = [];
                if(empty($val['odds_history'])) continue;

                $nowOdds = $val['now_odds'];
                $cName = preg_replace("/\((.*?)\)/i","",$val['company_name']);   //过滤括号里的国家等字
                $cName = str_replace(" ", "", $cName);
                $numFlag = 0;
                if(preg_match("/(\d+)/i",$cName,$data)) $numFlag = 1;

                $n = count($val['odds_history']);
                if($n == 1)
                {
                    $endfswOdds = $val['odds_history'][0];
                    // 取消changeExp欧赔的公式转换
                    $temp = [
                        0 => $nowOdds[2],
                        1 => $cName,
                        2 => $endfswOdds[0]== null?'':$endfswOdds[0],
//                        3 => changeExp($endfswOdds[1]),
                        3 => $endfswOdds[1]== null?'':$endfswOdds[1],
                        4 => $endfswOdds[2]== null?'':$endfswOdds[2],
                        5 => $endfswOdds[0] == null?'':$endfswOdds[0],
//                        6 => changeExp($endfswOdds[1]),
                        6 => $endfswOdds[1]==null?'':$endfswOdds[1],
                        7 => $endfswOdds[2]== null?'':$endfswOdds[2],
                        8 => (string)$nowOdds[0],
                        9 => (string)$nowOdds[22],
                        10 => (string)$nowOdds[23],
                        11 => (string)$numFlag,
                    ];
                    if($temp[9] == 1)
                    {
                        $oddsGj['h']['equal'] = $oddsGj['h']['equal']+2;
                        $oddsGj['d']['equal'] = $oddsGj['d']['equal']+2;
                        $oddsGj['a']['equal'] = $oddsGj['a']['equal']+2;
                    }
                }
                else
                {
                    $startfswOdds = $val['odds_history'][$n-1];
                    $endfswOdds = $val['odds_history'][0];

                    $temp = [
                        0 => $nowOdds[2],
                        1 => $cName,
                        2 => $startfswOdds[0]== null?'':$startfswOdds[0],
//                        3 => changeExp($startfswOdds[1]),
                        3 => $startfswOdds[1]== null?'':$startfswOdds[1],
                        4 => $startfswOdds[2]== null?'':$startfswOdds[2],
                        5 => $endfswOdds[0] == null?'':$endfswOdds[0],
//                        6 => changeExp($endfswOdds[1]),
                        6 => $endfswOdds[1]== null?'':$endfswOdds[1],
                        7 => $endfswOdds[2]== null?'':$endfswOdds[2],
                        8 => (string)$nowOdds[0],
                        9 => (string)$nowOdds[22],
                        10 => (string)$nowOdds[23],
                        11 => (string)$numFlag,
                    ];
                    if($temp[9] == 1)
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

                if($val['company_name'] == "竞彩官方"){
                    $temp[0] = $temp[1] = '竞彩官方';
                    $jcData = $temp;
                    continue;
                }

                if($nowOdds[0] == 3)
                    $sbData = $temp;
                else
                    $rData[] = $temp;

            }

            if(!empty($sbData)) array_unshift($rData,$sbData);
            if(!empty($jcData)) array_unshift($rData,$jcData);
            if(!empty($rData)) $retData = ['detailOdds' =>$rData, 'aobTrend' =>$oddsGj];

            $fb_game = $mService->select('fb_game',$map,['same_odds.same_euro.all']);
            $same_odds = $fb_game[0]['same_odds']['same_euro']['all'];
            if(!empty($same_odds)){
                //历史同赔数据
                $sameData['h'] = $same_odds[0];
                $sameData['f'] = $same_odds[1];
                $sameData['a'] = $same_odds[2];
                $sameData['n'] = count($same_odds[3]).'场'.C('alikeHistoryCompany')['companyName'];
                $retData['same_odds'] = $sameData;
            }
            return $retData;
        }
        else
        {
            return $rData;
        }

    }

    /**
     * 根据game_id获取亚赔大小历史赔率记录
     * @return array
     */
    public function getEurohisByIdMon($gameId,$company)
    {
        if(empty($gameId) || empty($company)) return false;

        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId];
        $gmRes = $mService->select('fb_euroodds',$map,['game_id','euroodds']);

        $rData = $aData =[];
        if(!empty($gmRes) && !empty($gmRes[0]['euroodds']))
        {
            $date = date('Y');
            $tTime = time();
            foreach($gmRes[0]['euroodds'] as $key=>$val)
            {
                $cName = preg_replace("/\((.*?)\)/i","",$val['company_name']);   //过滤括号里的国家等字
                $cName = str_replace(" ", "", $cName);
                if($company != $cName) continue;

                foreach($val['odds_history'] as $k2=>$v2)
                {
                    $v2[1] = changeExp($v2[1]);
                    $temp  = [
                        0 => $v2[0] == null?'':$v2[0],
                        1 => $v2[1] == null?'':changeExp($v2[1]),
                        2 => $v2[2] == null?'':$v2[2],
                        //3 => $v2[3] == null?'':date('Y-m-d H:i',strtotime($v2[3])),
                    ];
                    $tempTime = $date.'-'.$v2[3];
                    if($tTime < strtotime($tempTime))
                        $temp[3] = ($date-1).'-'.$v2[3];
                    else
                        $temp[3] = $tempTime;

                    $aData[] = $temp;
                }
            }
            //$rData = array_reverse($aData);
            $rData = $aData;
        }
        return $rData;
    }

    /**
     * 根据game_id获取实力对抗
     * @return array
     */
    public function getStrengthMon($gameId)
    {
        $mService = mongoService();
	    $map = ['game_id'=>(int)$gameId];
	    $start_time = null;
	    $upFlag = false;
	    $gmRes = $mService->select('fb_game',$map,['statistics', 'home_team_id', 'away_team_id', 'union_id', 'game_starttime']);
	    if ($gmRes[0]['game_starttime'] !== null) {
		    $start_time = $gmRes[0]['game_starttime']->sec;
	    }
		
	    $hInt = S('cache_strength_home_'.$gameId);
	    $aInt = S('cache_strength_away_'.$gameId);
		
	    if (!$hInt && !$aInt) {
		    $fixval = $this->getStrengthFixvalMon($gmRes[0]['home_team_id'],$gmRes[0]['away_team_id'],$gmRes[0]['union_id'], $start_time,$gameId);
		    $hInt = $fixval['home_strength_fixval'];
		    $aInt = $fixval['away_strength_fixval'];
		    S('cache_strength_home_'.$gameId, $hInt, 60 * 60);
		    S('cache_strength_away_'.$gameId, $aInt, 60 * 60);
		    $upFlag = true;
	    }
        $h = round($hInt/($hInt+$aInt),2);
        $a = round($aInt/($hInt+$aInt),2);
        $strengthDif = abs($h-$a);
        if($upFlag === true){
//	        $mService->update('fb_game', array('statistics'=>['home_strength_fixval'=>$fixval['home_strength_fixval'], 'away_strength_fixval'=>$fixval['away_strength_fixval'], 'home_strength'=>$h, 'away_strength'=>$a, 'strength'=>$strengthDif]), $map,'set');
        }
        if ($h == 0.29) $h = $h + 0.001;
        if ($a == 0.29) $a = $a + 0.001;
        $rData['home'] = $h;
        $rData['away'] = $a;
        return $rData;
    }
	
	/**
	 * 战斗力数据计算日志接口
	 * @param $gameId
	 */
    public function getStrengthMontest($gameId)
    {
	    $mService = mongoService();
	    $map = ['game_id'=>(int)$gameId];
		$start_time = null;
	    $gmRes = $mService->select('fb_game',$map,['statistics', 'home_team_id', 'away_team_id', 'union_id', 'game_starttime']);
	    if ($gmRes[0]['game_starttime'] !== null) {
	    	$start_time = $gmRes[0]['game_starttime']->sec;
	    }
		$fixval = $this->getStrengthFixvalMonTest($gmRes[0]['home_team_id'],$gmRes[0]['away_team_id'],$gmRes[0]['union_id'], $start_time,$gameId);
		$hInt = $fixval['home_strength_fixval'];
		$aInt = $fixval['away_strength_fixval'];
		
		echo "<br>";
		echo "主队计算后数值".$hInt;
		echo "<br>";
		echo "客队计算后数值".$aInt;
	
	    $h = round($hInt/($hInt+$aInt),2);
	    $a = round($aInt/($hInt+$aInt),2);
	
	    echo "<br>";
	    echo "主队百分比计算后 : ".$h;
	    echo "<br>";
	    echo "客队百分比计算后 : ".$a;
	
	    if ($h == 0.29) $h = $h + 0.001;
	    if ($a == 0.29) $a = $a + 0.001;
	    echo "<br>";
	    echo "<br>";
	    echo "<br>";
	    
	    echo "<br>";
	    echo "主队结果  : ".$h;
	    echo "<br>";
	    echo "客队结果 : ".$a;
	    $rData['home'] = $h;
	    $rData['away'] = $a;
    }
    
    
    

    /**
     * 根据game_id必发数据
     * @return array
     */
    public function getBifaValue($gameId)
    {
        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId];
        $gmRes = $mService->select('fb_bifaindex310win',$map,['odds','bifadatastandard','bifadatabigsmall']);
        //var_dump($gmRes);exit;
        $rData = [];
        if(!empty($gmRes))
        {
            $odds = $aData = [];
            if(isset($gmRes[0]['odds']))
            {
                foreach($gmRes[0]['odds'] as $key=>$val)
                {
                    if($val[0] == 0)
                    {
                        $odds = $val;
                    }
                }
            }
            //var_dump($odds);exit;
            $one = [0=>'',1=>'',2=>'',3=>'',4=>'',5=>'',6=>''];
            if(isset($gmRes[0]['bifadatastandard']))
            {
                $aNumber = str_replace(',','',$gmRes[0]['bifadatastandard'][0][3])+ str_replace(',','',$gmRes[0]['bifadatastandard'][1][2]) + str_replace(',','',$gmRes[0]['bifadatastandard'][2][2]);
                $one[0] = (string)$aNumber;
                $one[1] = isset($gmRes[0]['bifadatastandard'][0][1])?$gmRes[0]['bifadatastandard'][0][1]:'';
                $one[2] = isset($gmRes[0]['bifadatastandard'][1][1])?$gmRes[0]['bifadatastandard'][1][1]:'';
                $one[3] = isset($gmRes[0]['bifadatastandard'][2][1])?$gmRes[0]['bifadatastandard'][2][1]:'';

                foreach($gmRes[0]['bifadatastandard'] as $key=>$val)
                {
                    if(count($val) < 8)
                    {
                        $temp = [0=>'',1=>'',2=>'',3=>'',4=>'',5=>'',6=>''];
                        $aData[] = $temp;
                        continue;
                    }
                    if($key == 0)
                    {
                        $temp = [
                            '0' => isset($odds[1])?$odds[1]:'',
                            '1' => (isset($val[0]) && !empty($val[0]))?$val[0]:'',
                            '2' => (isset($val[1]) && !empty($val[1]))?$val[1]:'',
                            '3' => (isset($val[3]) && !empty($val[3]))?$val[3]:'',
                            '4' => (isset($val[4]) && !empty($val[4]))?$val[4]:'',
                            '5' => (isset($val[6]) && !empty($val[6]))?$val[6]:'',
                            '6' => (isset($val[7]) && !empty($val[7]))?$val[7]:'',
                        ];
                    }
                    else if($key == 1)
                    {
                        $temp = [
                            '0' => isset($odds[2])?$odds[2]:'',
                            '1' => (isset($val[0]) && !empty($val[0]))?$val[0]:'',
                            '2' => (isset($val[1]) && !empty($val[1]))?$val[1]:'',
                            '3' => (isset($val[2]) && !empty($val[2]))?$val[2]:'',
                            '4' => (isset($val[3]) && !empty($val[3]))?$val[3]:'',
                            '5' => (isset($val[5]) && !empty($val[5]))?$val[5]:'',
                            '6' => (isset($val[6]) && !empty($val[6]))?$val[6]:'',
                        ];
                    }
                    else
                    {
                        $temp = [
                            '0' => isset($odds[3])?$odds[3]:'',
                            '1' => (isset($val[0]) && !empty($val[0]))?$val[0]:'',
                            '2' => (isset($val[1]) && !empty($val[1]))?$val[1]:'',
                            '3' => (isset($val[2]) && !empty($val[2]))?$val[2]:'',
                            '4' => (isset($val[3]) && !empty($val[3]))?$val[3]:'',
                            '5' => (isset($val[5]) && !empty($val[5]))?$val[5]:'',
                            '6' => (isset($val[6]) && !empty($val[6]))?$val[6]:'',
                        ];
                    }

                    $aData[] = $temp;
                }
            }

            if(isset($gmRes[0]['bifadatabigsmall']))
            {
                $aNumber = str_replace(',','',$gmRes[0]['bifadatabigsmall'][0][5])+ str_replace(',','',$gmRes[0]['bifadatabigsmall'][1][5]);
                $one[4] = (string)$aNumber;
                $one[5] = isset($gmRes[0]['bifadatabigsmall'][0][7])?$gmRes[0]['bifadatabigsmall'][0][7]:'';
                $one[6] = isset($gmRes[0]['bifadatabigsmall'][1][7])?$gmRes[0]['bifadatabigsmall'][1][7]:'';

                foreach($gmRes[0]['bifadatabigsmall'] as $key=>$val)
                {
                    if(count($val) < 12)
                    {
                        $temp = [0=>'',1=>'',2=>'',3=>'',4=>'',5=>'',6=>''];
                        $aData[] = $temp;
                        continue;
                    }
                    $temp = [
                        '0' => (isset($val[2]) && !empty($val[2]))?$val[2]:'',
                        '1' => (isset($val[6]) && !empty($val[6]))?$val[6]:'',
                        '2' => '-',
                        '3' => (isset($val[5]) && !empty($val[5]))?$val[5]:'',
                        '4' => (isset($val[7]) && !empty($val[7]))?$val[7]:'',
                        '5' => (isset($val[8]) && !empty($val[8]))?$val[8]:'',
                        '6' => (isset($val[11]) && !empty($val[11]))?$val[11]:'',
                    ];
                    $aData[] = $temp;
                }
            }

            if($one[0] == 0 || ($one[1] == '0.00%' && $one[2] == '0.00%' && $one[3] == '0.00%')) return $rData = [];
            $rData[] = $one;
            $rData[] = $aData;
        }
        return $rData;
    }

     /**
     * 根据game_id必发数据
     * @return array
     */
    public function getBifaTrade($gameId)
    {
        $mService = mongoService();
        $map = ['game_id'=>(int)$gameId];
        $gmRes = $mService->select('fb_bifaindex310win',$map,['hostdetail','awaydetail','drawdetail']);

        $rData = [];
        if(!empty($gmRes))
        {
            if(isset($gmRes[0]['hostdetail']))
            {
                $num = '';
                $oddsArr = array_reverse($gmRes[0]['hostdetail']);
                foreach($oddsArr as $key=>$val)
                {
                    if(empty($val[4]) || $val[4] == '冲') continue;

                    if($num != '')
                    {
                        $nums = str_replace(',','',$val[2]);
                        $nTemp = abs (round((($nums-$num)/$num * 100),2)).'%';
                    }
                    else
                    {
                        $nTemp = '0%';
                    }
                    $num = str_replace(',','',$val[2]);
                    $temp = [
                        '0' => '主',
                        '1' => $val[4],
                        '2' => $val[2],
                        '3' => $val[1],
                        '4' => $val[0],
                        '5' => $nTemp,
                    ];
                    $sort[] = $val[0];
                    $rData[] = $temp;
                }
            }
            if(isset($gmRes[0]['awaydetail']))
            {
                $num = '';
                $oddsArr = array_reverse($gmRes[0]['awaydetail']);
                foreach($oddsArr as $key=>$val)
                {
                    if(empty($val[4]) || $val[4] == '冲') continue;

                    if($num != '')
                    {
                        $nums = str_replace(',','',$val[2]);
                        $nTemp = abs (round((($nums-$num)/$num * 100),2)).'%';
                    }
                    else
                    {
                        $nTemp = '0%';
                    }
                    $num = str_replace(',','',$val[2]);
                    $temp = [
                        '0' => '客',
                        '1' => $val[4],
                        '2' => $val[2],
                        '3' => $val[1],
                        '4' => $val[0],
                        '5' => $nTemp,
                    ];
                    $sort[] = $val[0];
                    $rData[] = $temp;
                }
            }
            if(isset($gmRes[0]['drawdetail']))
            {
                $num = '';
                $oddsArr = array_reverse($gmRes[0]['drawdetail']);
                foreach($oddsArr as $key=>$val)
                {
                    if(empty($val[4]) || $val[4] == '冲') continue;

                    if($num != '')
                    {
                        $nums = str_replace(',','',$val[2]);
                        $nTemp = abs (round((($nums-$num)/$num * 100),2)).'%';
                    }
                    else
                    {
                        $nTemp = '0%';
                    }
                    $num = str_replace(',','',$val[2]);
                    $temp = [
                        '0' => '平',
                        '1' => $val[4],
                        '2' => $val[2],
                        '3' => $val[1],
                        '4' => $val[0],
                        '5' => $nTemp,
                    ];
                    $sort[] = $val[0];
                    $rData[] = $temp;
                }
            }
            array_multisort($sort ,SORT_DESC ,$rData);
        }
        return $rData;
    }

    /**
     * SB公司赔率历史数据————mongo数据
     * @param  int    $gameId  赛事ID
     * @return array  SB公司赔率历史数据
     */
    public function getSBhisOdds($gameId)
    {
        if(empty($gameId)) return false;
        if(!$game = S('cache_api520_SBhisOdds:'.$gameId)){
            $mongodb = mongoService();
            $game = $mongodb->select('fb_game',['game_id'=>$gameId],['corner_oddshistory','odds_history.3']);
            S('cache_api520_SBhisOdds:'.$gameId,$game,60);
        }
        
        $yapan   = $game[0]['odds_history'][3][0];   //亚盘
        $ouzhi   = $game[0]['odds_history'][3][2];   //欧指
        $daxiao  = $game[0]['odds_history'][3][1];   //大小
        $jiaoqiu = $game[0]['corner_oddshistory'][1];//角球

        $score_cn = C('score_cn');
        $ya = $ou = $da = $jiao = [];
        //亚盘数据处理
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
            if(is_numeric($v[0])) $v[0] .= "'";
            unset($v[5],$v[6]);
            $ya[] = $v;
        }
        //欧指数据处理
        foreach ($ouzhi as $k => $v) {
            if($v[6] != '滚') continue;
            if(is_numeric($v[0])) $v[0] .= "'";
            unset($v[5],$v[6]);
            $ou[] = $v;
        }
        //大小数据处理
        foreach ($daxiao as $k => $v) {
            if($v[6] != '滚') continue;
            if(is_numeric($v[0])) $v[0] .= "'";
            unset($v[5],$v[6]);
            $da[] = $v;
        }
        //角球数据处理
        foreach ($jiaoqiu as $k => $v) {
            if($v[6] == '滚' || $v[4] == '滚'){
                if($v[2] == '封'){
                    //为封时数据处理
                    $v[2] = '';
                    $v[3] = '封';
                    $v[4] = '';
                }
                if(is_numeric($v[0])) $v[0] .= "'";
                unset($v[5],$v[6]);
                $jiao[] = $v;
            }
        }
        $data['yazhi']   = $ya; 
        $data['ouzhi']   = $ou; 
        $data['daxiao']  = $da; 
        $data['jiaoqiu'] = $jiao; 
        return $data;
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
    //                         //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
    //                         5=> $v['HappenTime'] == '中场'?$v['HappenTime']:$v['HappenTime']."'",
    //                     ];
    //                 }
    //                 else
    //                 {
    //                     if($type == 2)
    //                     {
    //                         $temp = [
    //                             0 => $v['HomeOdds'],
    //                             1 => $v['PanKou'],
    //                             2 => $v['AwayOdds'],
    //                             3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
    //                             4 => $v['Score'],
    //                             //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
    //                             5=> $v['HappenTime'] == '中场'?$v['HappenTime']:$v['HappenTime']."'",
    //                         ];
    //                     }
    //                     else
    //                     {
    //                        $temp = [
    //                             0 => $v['HomeOdds'],
    //                             1 => changeExp($v['PanKou']),
    //                             2 => $v['AwayOdds'],
    //                             3 => date('Y-m-d H:i',strtotime($v['ModifyTime'])),
    //                             4 => $v['Score'],
    //                             //5 => (($v['ModifyTime']-$gameTime)<0)?'00':round(($v['ModifyTime']-$gameTime)/100),
    //                             5=> $v['HappenTime'] == '中场'?$v['HappenTime']:$v['HappenTime']."'",
    //                         ];
    //                     }
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
     +------------------------------------------------------------------------------
     * 华丽分割线
     +------------------------------------------------------------------------------
    */

    /**
     * 根据盘口、初盘、即时盘计算倾向
     * @return array  计算结果
     */
    public function abTrend($cExp,$jExp,$hCodds,$hJodds,$aCodds,$aJodds)
    {
        $arr = ['h'=>0,'a'=>0];
        if($cExp > $jExp && $cExp >= 0 && $jExp >= 0)
        {
            $arr['a']++;
        }
        else if($cExp < $jExp && $cExp >= 0 && $jExp >= 0)
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
        else if($cExp >= 0 && $jExp < 0)
        {
            //echo "eee";
            $arr['a']++;
        }
        else if($cExp < 0 && $jExp >= 0)
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
     * 根据盘口、初盘、即时盘计算倾向(大小)
     * @return array  计算结果
     */
    public function bTrend($cExp,$jExp,$hCodds,$hJodds,$aCodds,$aJodds)
    {
        $arr = ['h'=>0,'a'=>0];

        if($cExp == $jExp)
        {
            if($hCodds == $hJodds && $aCodds == $aJodds)
            {
                if($hCodds < $aCodds)
                {
                    $arr['h']++;
                }
                else if($hCodds > $aCodds)
                {
                    $arr['a']++;
                }
            }
            else if($hCodds != $hJodds && $aCodds == $aJodds)
            {
                if($hCodds > $hJodds)
                {
                    $arr['a']++;
                }
                else
                {
                    $arr['h']++;
                }
            }
            else if($hCodds == $hJodds && $aCodds != $aJodds)
            {
                if($aCodds > $aJodds)
                {
                    $arr['h']++;
                }
                else
                {
                    $arr['a']++;
                }
            }
            else if($hCodds != $hJodds && $aCodds != $aJodds)
            {
                if($hCodds > $hJodds && $aCodds < $aJodds)
                {
                    $arr['h']++;
                }
                else if($hCodds < $hJodds && $aCodds > $aJodds)
                {
                    $arr['a']++;
                }
                else if(($hCodds > $hJodds && $aCodds > $aJodds)||($hCodds < $hJodds && $aCodds < $aJodds))
                {
                    if($hJodds < $aJodds)
                    {
                        $arr['h']++;
                    }
                    else if($hJodds > $aJodds)
                    {
                        $arr['a']++;
                    }
                }

            }

        }
        else if($cExp > $jExp)
        {
            $arr['a']++;
        }
        else
        {
            $arr['h']++;
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
            $arr['a']++;
        }
        else if($hCodds < $hJodds)
        {
            $arr['h']++;
        }
        else
        {
            $arr['d']++;
        }
        return $arr;
    }

    /**
     * 亚盘大小对抗计算
     * @param  araay     $data  初盘赔率
     * @param  int     $type  即时赔率
     * @return array   计算结果
     */
    public function asianBallTj($data,$type)
    {
        if(empty($data)) return;

        $rData = [];
        foreach($data as $key=>$val)
        {
            $oddsGj = ['h'=>0,'a'=>0];
            foreach($val as $k=>$v)
            {
                $oddsArr = explode('!',$v);
                $number = count($oddsArr);
                if($number == 1)
                {
                    $endOdds = $oddsArr[0];
                    $endfswOdds = explode('^',$endOdds);
                    $tj = $this->abTrend($endfswOdds[1],$endfswOdds[1],$endfswOdds[0],$endfswOdds[0],$endfswOdds[2],$endfswOdds[2]);
                }
                else
                {
                    $endOdds = $oddsArr[0];
                    $endfswOdds = explode('^',$endOdds);
                    $startOdds = $oddsArr[$number-1];
                    $startfswOdds = explode('^',$startOdds);
                    $tj = $this->abTrend($startfswOdds[1],$endfswOdds[1],$startfswOdds[0],$endfswOdds[0],$startfswOdds[2],$endfswOdds[2]);
                }
                $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                $oddsGj['a'] = $oddsGj['a'] + $tj['a'];
            }
            if($type == 1)
            {
               $temp = [
                    'game_id' => $key,
                    'home_asian_trend' =>$oddsGj['h'],
                    'away_asian_trend' =>$oddsGj['a'],
                    'asian_trend' => abs($oddsGj['h']-$oddsGj['a']),
                ];
            }
            else
            {
                $temp = [
                    'game_id' => $key,
                    'home_ball_trend' =>$oddsGj['h'],
                    'away_ball_trend' =>$oddsGj['a'],
                    'ball_trend' => abs($oddsGj['h']-$oddsGj['a']),
                ];
            }

           $rData[$key] = $temp;
        }
        return $rData;
    }

    /**
     * 亚盘大小对抗计算
     * @param  araay     $data  初盘赔率
     * @param  int     $type  即时赔率
     * @return array   计算结果
     */
    public function asianBallTjMon($data,$type)
    {
        if(empty($data)) return;

        $rData = [];
        foreach($data as $key=>$val)
        {
            $oddsGj = ['h'=>0,'a'=>0];
            foreach($val as $k=>$v)
            {
                $oddsArr = $v;

                if($type == 1)
                {
                    if($oddsArr[1] == '' && $oddsArr[4] == '') continue;
                    if($oddsArr[4] == '')
                    {
                        $tj = $this->abTrend($oddsArr[1],$oddsArr[1],$oddsArr[0],$oddsArr[0],$oddsArr[2],$oddsArr[2]);
                    }
                    else
                    {
                        $tj = $this->abTrend($oddsArr[1],$oddsArr[4],$oddsArr[0],$oddsArr[3],$oddsArr[2],$oddsArr[5]);
                    }
                }
                else
                {
                    if($oddsArr[19] == '' && $oddsArr[22] == '') continue;
                    if($oddsArr[22] == '')
                    {
                        $tj = $this->bTrend($oddsArr[19],$oddsArr[1],$oddsArr[18],$oddsArr[18],$oddsArr[23],$oddsArr[23]);
                    }
                    else
                    {
                        $tj = $this->bTrend($oddsArr[19],$oddsArr[22],$oddsArr[18],$oddsArr[21],$oddsArr[20],$oddsArr[23]);
                    }
                }
                $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                $oddsGj['a'] = $oddsGj['a'] + $tj['a'];
            }
            if($type == 1)
            {
               $temp = [
                    'game_id' => $key,
                    'home_asian_trend' =>$oddsGj['h'],
                    'away_asian_trend' =>$oddsGj['a'],
                    'asian_trend' => abs($oddsGj['h']-$oddsGj['a']),
                ];
            }
            else
            {
                $temp = [
                    'game_id' => $key,
                    'home_ball_trend' =>$oddsGj['h'],
                    'away_ball_trend' =>$oddsGj['a'],
                    'ball_trend' => abs($oddsGj['h']-$oddsGj['a']),
                ];
            }

            $rData[$key] = $temp;
        }
        return $rData;
    }

    //拼装推荐记录数据sql
    public function inBatchSql($table,$data)
    {
        $sqlStr = 'INSERT into '.$table.' ';
        $keys = array_keys($data[0]);
        foreach($keys as $k=>$v)
        {
            $sqlArr[$v] = $v;
        }
        $sqlStr .= '('.implode(',',$sqlArr).') VALUES';

        foreach($data as $k=>$v)
        {
            $sqlStr .= '('.implode(',',$v).'),';
        }

        $sqlStr = rtrim($sqlStr,',');
        return $sqlStr;
    }

    //拼装推荐记录数据sql
    public function upBatchSql($table,$caseField,$data)
    {
        $sqlStr = 'UPDATE '.$table.' SET';
        $keys = array_keys($data[0]);

        foreach($keys as $k=>$v)
        {
            if($k != $caseField) $sqlArr[$v] = '';
        }
        $pField = $sqlArr = [];
        foreach($data as $k=>$v)
        {
            $sqlTmep = '';
            $cfVal = $v[$caseField];
            $pField[] = $cfVal;

            foreach($v as $k2=>$v2)
            {
                if($k2 == $caseField) continue;
                $sqlArr[$k2] .= 'WHEN '.$cfVal.' THEN '.$v2.' ';
            }
        }

        foreach($sqlArr as $k=>$v)
        {

            $sqlStr .=' '.$k.' = CASE '.$caseField.' '.$v.' END,';
        }
        $sqlStr = rtrim($sqlStr,',');
        $sqlStr .= ' WHERE game_id IN ('.implode(',',$pField).')';

        return $sqlStr;
    }

    //盘口格式转换0.25=>平半
    public function expToCn($exp)
    {
        if($exp == 0) return '平手';
        $scoreCn = C('score');
        if(strpos($exp,'-') !== false)
        {
            $exp = ltrim($exp,'-');
            $res = '受让'.$scoreCn[(string)$exp];
        }
        else
        {
            $res = '让'.$scoreCn[(string)$exp];
        }
        return $res;
    }


     /**
     * 根据球队计算近期赛事战力积分
     * @param  float     $teamId
     * @param  float     $teamId
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
	 * 根据近期比赛计算战力
	 * @param $teamId
	 * @param $arr
	 * @param $snum
	 * @return bool|float
	 */
    public function TestcalRecentGame($teamId, $arr, $snum)
    {
	    if(empty($teamId) || empty($arr)) return false;
	
	    $rate = $snum/100;
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
	    return round($int*$rate,2);
    }
    
    
    
    function attDefNum($team_id, $res)
    {
    	$inSide = $lostGoal = 0;
	    $rate = 25 / 100;
        foreach ($res as $key => $value) {
        	if ($value['home_team_id'] == $team_id) {
        	    $inSide += explode('-', $value['score'])[0];
        	    $lostGoal += explode('-', $value['score'])[1];
	        } else {
		        $inSide += explode('-', $value['score'])[1];
		        $lostGoal += explode('-', $value['score'])[0];
	        }
        }
        switch ($inSide) {
	        case $inSide >= 0 && $inSide <= 7:
	            $att = 2;
	            break;
	        case $inSide >= 8 && $inSide <= 15:
	        	$att = 4;
	        	break;
	        case $inSide >= 16 && $inSide <= 23:
	        	$att = 6;
	        	break;
	        case $inSide >=24 && $inSide <= 31:
	        	$att = 8;
	        	break;
	        case $inSide >= 32:
	        	$att = 10;
	        	break;
        }
        
        switch ($lostGoal) {
	        case $lostGoal <= 8 && $lostGoal >=0:
	        	$def = 10;
	        	break;
	        case $lostGoal <= 15 && $lostGoal >=9:
	        	$def = 8;
	        	break;
	        case $lostGoal <= 20 && $lostGoal >= 16:
	        	$def = 6;
	        	break;
	        case $lostGoal >= 21 && $lostGoal <= 29:
	        	$def = 4;
	        	break;
	        case $lostGoal >= 30:
	        	$def = 2;
	        	break;
        }
	    return ['att' => round($att*$rate,2), 'def' => round($def*$rate,2)];
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
    
    public function testCalExpTrend($exp)
    {
	    $arr = ['h'=>0,'a'=>0];
	    $rate = 0.2;
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
    
    


    public function cleanData(&$data)
    {
        #通过mongo筛选历史数据 无法进行game_state判断所以进行反筛选 进行清除
        foreach ($data as $k => $v) {
            if ($v['score'] == '') {
                unset($data[$k]);
            }
        }
        return (array_slice($data,0, 10));
    }


    /**
     * 获取指定时间赛事 滚球预警基础数据
     * @param $date "Y-m-d H:i" 默认当前时间
     * @return array
     */
    public function getNowGameData($date) {
        $mongodb = mongoService();
        $gameIds = [];
        $gameList = $mongodb->select('fb_gamelist',['date'=>$date],['game_list'])[0]['game_list'];
        if(empty($gameList)) {
            return [];
        }
        $statusIds = array_column($gameList, 'game_id');
        $_map = ['$or' => [
            ['jbh_id' => ['$in' => $statusIds]],
            ['jb_id' => ['$in' => $statusIds]],
        ]];
	    $statusList = $mongodb->select('fb_game_365'.C('TableSuffix'),$_map,['status', 'jb_id', 'jbh_id', 'is_icon']);
	    $mongodb->close();
	    $gameStatusList = [];
        foreach ($statusList as $key => $value) {
            if ($value['status'] > 0 &&  $value['status'] < 5 && $value['is_icon'] === 1) {
	            $gameStatusList[] = $value['jbh_id']?:$value['jb_id'];
            }
        }
        return $this->rollingBallWarning($gameStatusList);
    }


    /**
     * 指定ids 获取滚球预警数据
     * @param $gameIds  array 赛事id数组
     * @return array
     */
    public function rollingBallWarning($gameIds)
    {
        if(empty($gameIds))
            return [];
        $data = [];
        $mongodb = mongoService();
        //获取赛事数据
        $gameInfoArr = $mongodb->select('fb_game',['game_id'=>[$mongodb->cmd('in')=>$gameIds]],['game_id', 'union_name','home_team_name','away_team_name','game_state','game_start_timestamp','gtime']);
        $gameRollBallArr = $mongodb->select('fb_goal',['game_id'=>[$mongodb->cmd('in')=>$gameIds]],['game_id', 'odds']);
        $_map = ['$or' => [
            ['jbh_id' => ['$in' => $gameIds]],
            ['jb_id' => ['$in' => $gameIds]],
        ]];
        $rollGameArr = $mongodb->select('fb_game_365'.C('TableSuffix'),$_map,['jb_id', 'jbh_id', 'game_time', 'tech','events_statistics','status']);
	    $mongodb->close();
        
        // 如果数据有空 返回空
        if(!$rollGameArr || !$gameInfoArr) return [];
        
        // 如果365数据字段为空 移除此场比赛
        foreach ($rollGameArr as $key => $value) {
            if (empty($rollGameArr[$key]['events_statistics'])) {
                unset($rollGameArr[$key]);
            }
        }
        foreach ($gameIds as $value) {
            $tempData = [];
            foreach ($gameInfoArr as $infoKey => $infoValue) {
                if ($infoValue['game_id'] == $value) {
                    $tempData['game_id'] = $infoValue['game_id'];
                    $tempData['union_name'] = implode(',', $infoValue['union_name']);
                    $tempData['home_team_name'] = implode(',', $infoValue['home_team_name']);
                    $tempData['away_team_name'] = implode(',', $infoValue['away_team_name']);
                    $tempData['game_start_timestamp'] = $infoValue['game_start_timestamp'];
                    $tempData['ctime'] = date('H:i',$infoValue['game_start_timestamp']);
                }
            }
			
            //获取即时滚球赔率
	        if ($gameRollBallArr) {
		        foreach ($gameRollBallArr as $ballKey => $ballValue) {
			        if ($ballValue['game_id'] == $value) {
				        if ($ballValue['odds']['a'][3] == 1) {
					        $tempData['asia_odds'] = '-^封^-';
				        } else {
					        $tempData['asia_odds'] = $this->oddsInit($ballValue['odds']['a'][1]).'^'.$this->oddsInit($ballValue['odds']['a'][0]).'^'.$this->oddsInit($ballValue['odds']['a'][2]);
				        }
				        // mongo 中 fb_goal集合 欧赔和 大小亚赔数据不一致
				        if ($ballValue['odds']['e'][3] == 1) {
					        $tempData['europe_odds'] = '-^封^-';
				        } else {
					        $tempData['europe_odds'] = $this->oddsInit($ballValue['odds']['e'][0]).'^'.$this->oddsInit($ballValue['odds']['e'][1]).'^'.$this->oddsInit($ballValue['odds']['e'][2]);
				        }
				        if ($ballValue['odds']['b'][3] == 1) {
					        $tempData['bigsmall_odds'] = '-^封^-';
				        } else {
					        $tempData['bigsmall_odds'] = $this->oddsInit($ballValue['odds']['b'][1]).'^'.$this->oddsInit($ballValue['odds']['b'][0]).'^'.$this->oddsInit($ballValue['odds']['b'][2]);
				        }
				        break;
			        }
			        $tempData['asia_odds'] = $tempData['europe_odds'] = $tempData['bigsmall_odds'] = "-^-^-";
		        }
	        }else {
		        $tempData['asia_odds'] = $tempData['europe_odds'] = $tempData['bigsmall_odds'] = "-^-^-";
	        }


            // 根据365数据获取滚球预警信息
            foreach ($rollGameArr as $rollKey => $rollValue) {
                $addKey = true;
                $jb_id = $rollValue['jbh_id']?:$rollValue['jb_id'];
                if ($jb_id == $value) {
	                $tempData['play_time'] =$game_time = $rollValue['game_time'];
                    $tempData['corner'] = $rollValue['tech']['home_team']['corner']."^".$rollValue['tech']['away_team']['corner'];
                    $tempData['game_score'] = $rollValue['events_statistics'][$game_time]['game_score'];
                    if (empty($rollValue['events_statistics'])) {
                        $addKey = false;
                    }
                    $progress = $this->progress($jb_id, $tempData['play_time'], $rollValue['events_statistics'], $rollValue['status']);
                    $tempData['progress'] = $progress;
	                // 如果状态为中场时间显示中场
	                if ($rollValue['status'] == 2) {
		                $tempData['play_time'] = '中场';
	                }
                }
                // 如果时间不为正整数 或 365数据不存在 则不加入此场比赛
                $addKey ? $tempImport[] = $jb_id : '';
            }
            $sort[] = $tempData['game_start_timestamp'];
            unset($tempData['game_start_timestamp']);
            if (in_array($value, $tempImport, true)) {
                $data[] = $tempData;
            }
        }
        array_multisort($sort,SORT_ASC,$data);
        return $data;
    }
	
	
	/**
	 * 计算进度条总数据
	 * @param $time
	 * @param $data
	 * @return array
	 */
    public function progress($game_id,$time, $data, $status) {
	    $fifteenMinutesAgo = $time -15 > 1 ? $time-15 : 1;
        $gameScore = $data[$time]['game_score'];
        $isGoal = -1;
        for ($i = $time; $i > $fifteenMinutesAgo; $i --) {
            if ($data[$i]['game_score'] != $gameScore) {
            	$isGoal = $i + 1;
            	break;
            }
        }
		return $this->goalChange($game_id, $isGoal, $fifteenMinutesAgo, $time, $data, $status);
    }
	
	
	/**
	 * 计算进度变化条件变化
	 * @param $game_id
	 * @param $isGoal
	 * @param $fifteenMinutesAgo
	 * @param $time
	 * @param $data
	 * @param $status
	 * @return array
	 */
    public function goalChange($game_id, $isGoal, $fifteenMinutesAgo, $time, $data, $status)
    {
	    if ($isGoal != -1) {
		    $shootGoalNum = $data[$isGoal]['home_team']['shootInside'] + $data[$isGoal]['home_team']['shootOutside'] +
			    $data[$isGoal]['away_team']['shootInside'] + $data[$isGoal]['away_team']['shootOutside'];
		    $shootNowNum = $data[$time]['home_team']['shootInside'] + $data[$time]['home_team']['shootOutside'] +
			    $data[$time]['away_team']['shootInside'] + $data[$time]['away_team']['shootOutside'];
		    $shootNum = $shootNowNum - $shootGoalNum;
	    } else {
		    $shootBeforeNum = $data[$fifteenMinutesAgo]['home_team']['shootInside'] + $data[$fifteenMinutesAgo]['home_team']['shootOutside'] +
			    $data[$fifteenMinutesAgo]['away_team']['shootInside'] + $data[$fifteenMinutesAgo]['away_team']['shootOutside'];
		    $shootNowNum = $data[$time]['home_team']['shootInside'] + $data[$time]['home_team']['shootOutside'] +
			    $data[$time]['away_team']['shootInside'] + $data[$time]['away_team']['shootOutside'];
		    $shootNum = $shootNowNum - $shootBeforeNum;
	    }
	    return $this->processData($game_id, $shootNum, $fifteenMinutesAgo, $time,$data, $status);
    }
	
	
	/**
	 * 计算进度变化数据
	 * @param $game_id
	 * @param $shootNum
	 * @param $fifteenMinutesAgo
	 * @param $time
	 * @param $data
	 * @param $status
	 * @return array
	 */
    public function processData($game_id, $shootNum, $fifteenMinutesAgo, $time, $data, $status)
    {
	    $rollBallConfig = M("config")->where(['sign' => 'rollBallWarning'])->getField('config');
	    $config = json_decode($rollBallConfig, true);
	    $tempData = [];
	    if ($shootNum < 3) {
		    $tempData = $this->determineAttr($game_id, $data[$fifteenMinutesAgo], $data[$time], $status);
	    } else if ($shootNum >= 3 && $shootNum < 5 ) {
	    	$message = $this->MessageFilter($config, $shootNum, $game_id, $status);
		    $tempData['color'] = FALSE;
		    $tempData['message'] = $message;
		    $tempData['progress'] = 20 + $shootNum;
	    } else {
		    $message = $this->MessageFilter($config, $shootNum, $game_id, $status);
		    $tempData['color'] = TRUE;
		    $tempData['message']= $message;
		    if ((20 + $shootNum) > 30) {
			    $tempData['progress'] = 30;
		    } else {
			    $tempData['progress'] = 20 + $shootNum;
		    }
	    }
	    return $tempData;
    }
	
	
	/**
	 * 危险进攻计算方法
	 * @param $game_id
	 * @param $beforeData
	 * @param $afterData
	 * @param $status
	 * @return array
	 */
    public function determineAttr($game_id, $beforeData, $afterData, $status)
    {
        $homeAttr = $afterData['home_team']['dangerAttack'] - $beforeData['home_team']['dangerAttack'];
        $awayAttr = $afterData['away_team']['dangerAttack'] - $beforeData['away_team']['dangerAttack'];
        if ($homeAttr < $awayAttr) {
	        return $this->teamProcess("客队", $awayAttr, $status, $game_id);
        }
	    return $this->teamProcess("主队", $homeAttr, $status, $game_id);
    }
	
	/**
	 * 危险进攻进度条
	 * @param $team
	 * @param $attr
	 * @param $status
	 * @param $game_id
	 * @return array
	 */
    public function teamProcess($team, $attr, $status, $game_id)
    {
	    $rollBallConfig = M("config")->where(['sign' => 'rollBallWarning'])->getField('config');
	    $config = json_decode($rollBallConfig, true);
	    $warAttr =  [];
	    $message = '发起危险进攻';
	    $tempBlock = '';
	    foreach ($config as $key => $value) {
	    	if ($value['type'] == 1) {
	    		$warAttr[] = $value;
		    }
	    }
	    $messageArr = $tempBlockArr = $shootArr =[];
	    foreach ($warAttr as $key => $value) {
	    	if ($attr < $value['condition_end'] && $attr >= $value['condition_start']) {
			    $messageArr[] = $value['string'];
			    $tempBlockArr[] = $value['team_block'];
			    $shootArr[] = $value['condition_start'].'-'.$value['condition_end'];;
		    }
	    }
	    if (!empty($messageArr)) {
		    $max = sizeof($messageArr) -1;
		    $num = mt_rand(0, $max);
		    $message = $messageArr[$num];
		    $tempBlock = $tempBlockArr[$num];
		    $temp = explode('-', $shootArr[$num]);
		    $interval['s'] = $temp[0];
		    $interval['e'] = $temp[1];
	    }
	
	    // 如果推送中所在区间在之前区间内 后台message信息不变
	    $beforeInterval= S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_attr');
	    $beforeMessage = S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_attr_message');
	    if (!$beforeInterval) {
		    S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_attr', $interval['s'].'-'.$interval['e'], 300);
		    S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_attr_message', $message, 300);
	    } else {
		    $list = explode('-', $beforeInterval);
		    $shootS = $list[0];
		    $shootE = $list[1];
		    if ($attr < $shootE && $attr >= $shootS) {
			    $message = $beforeMessage;
		    } else {
			    S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_attr', null);
			    S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_attr_message', null);
		    }
	    }
	    
	    
    	$temp = [];
	    $temp['color'] = FALSE;
	    if ($tempBlock != 1) {
	    	$team = '';
	    }
	    $temp['message'] = $team.$message;
	
	    //如果比赛在中场那么信息为空
	    if($status == 2) {
	    	$temp['message'] = '';
	    }
	    
	    if (floor($attr/2) >= 20) {
		    $temp['progress'] = 20;
	    } else {
		    $temp['progress'] = floor($attr/2);
	    }
	    return $temp;
    }
	
	/**
	 * 当赔率为空时 显示 -
	 * @param $odds
	 * @return string
	 */
	public function oddsInit($odds) {
		return $odds == "" ? "-" : $odds;
	}
	
	
	public function MessageFilter($config, $shootNum, $game_id, $status) {
		$warAttr =  [];
		$message = '发起射门';
		foreach ($config as $key => $value) {
			if ($value['type'] == 2) {
				$warAttr[] = $value;
			}
		}
		//后台配置
		$messageArr = $shootArr = [];
		foreach ($warAttr as $key => $value) {
			if ($shootNum < $value['condition_end'] && $shootNum >= $value['condition_start']) {
				$messageArr[] = $value['string'];
				$shootArr[] = $value['condition_start'].'-'.$value['condition_end'];;
			}
		}
		
		if (!empty($messageArr)) {
			$max = sizeof($messageArr) -1;
			$num = mt_rand(0, $max);
			$message = $messageArr[$num];
			$temp = explode('-', $shootArr[$num]);
			$interval['s'] = $temp[0];
			$interval['e'] = $temp[1];
		}
		
		// 如果推送中所在区间在之前区间内 后台信息不变
		$beforeInterval= S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_shoot');
		$beforeMessage = S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_shoot_message');
		if (!$beforeInterval) {
			S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_shoot', $interval['s'].'-'.$interval['e'], 300);
			S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_shoot_message', $message, 300);
		} else {
			$list = explode('-', $beforeInterval);
			$shootS = $list[0];
			$shootE = $list[1];
			if ($shootNum < $shootE && $shootNum >= $shootS) {
				$message = $beforeMessage;
			} else {
				S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_shoot', null);
				S($game_id.'_'.$interval['s'].'-'.$interval['e'].'_shoot_message', null);
			}
		}
		
		//如果比赛在中场那么信息为空
		if ($status == 2) {
			$message = '';
		}
		return $message;
	}

}


?>