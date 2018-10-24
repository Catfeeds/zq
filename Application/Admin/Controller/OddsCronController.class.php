 <?php
/**
 * 定时任务
 *
 * @author Hmg
 *
 * @since  2018-01-20
 */
use Think\Controller;

class OddsCronController extends Controller
{
    /**
    *构造函数
    *
    * @return  #
    */
    public function _initialize()
    {

    }

    /**
    * 亚盘大小对抗定时任务
    *
    * @return  #
    */
    public function cronAbCompeteMon()
    {
        set_time_limit(0);
        $pFlag = false;

        if($pFlag == true) monitoring('start');

        $mService = mongoService();
        $time = strtotime($date." ".C('fb_bigdata_time'));
        $startTime = $time-3600*3.5;
        $endTime = $time+3600*24;

        $mRes = $mService->select('fb_game',['game_start_timestamp'=>[$mService->cmd('<')=>$endTime,$mService->cmd('>')=>$startTime]],['game_id','match_odds','aob_trend']);

        if(!empty($mRes))
        {
            $odds = [];
            $gameIds = $unionIds = [];
            foreach($mRes as $key=>$val)
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
                            0 => str_replace(' ','',$val2[0]),
                            1 => changeSnExp(str_replace(' ','',$val2[1])),
                            2 => str_replace(' ','',$val2[2]),
                            3 => str_replace(' ','',$val2[3]),
                            4 => changeSnExp(str_replace(' ','',$val2[4])),
                            5 => str_replace(' ','',$val2[5]),
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
                            18 => str_replace(' ','',$val2[12]),
                            19 => str_replace(' ','',$val2[13]),
                            20 => str_replace(' ','',$val2[14]),
                            21 => str_replace(' ','',$val2[15]),
                            22 => str_replace(' ','',$val2[16]),
                            23 => str_replace(' ','',$val2[17]),
                            24 => '',
                            25 => '',
                            26 => '',
                        ];
                        $odds[$val['game_id']][$key2] = $oddsTemp;
                    }
                }
            }

            $aRes = $this->asianBallTjMon($odds,1);
            $bRes = $this->asianBallTjMon($odds,2);

            foreach($mRes as $key=>$val)
            {
                $temp = [];
                $upFlag = false;
                if(!isset($val['aob_trend']))
                {
                    $val['aob_trend'] = ['a'=>['home_trend'=>0,'away_trend'=>0,'trend_dif'=>0],'o'=>['home_trend'=>0,'away_trend'=>0,'trend_dif'=>0],'b'=>['home_trend'=>0,'away_trend'=>0,'trend_dif'=>0]];
                }
                if(isset($aRes[$val['game_id']]))
                {
                    if($val['aob_trend']['a']['home_trend'] != $aRes[$val['game_id']]['home_asian_trend'] || $val['aob_trend']['a']['away_trend'] != $aRes[$val['game_id']]['away_asian_trend'] || $val['aob_trend']['a']['trend_dif'] != $aRes[$val['game_id']]['asian_trend']) $upFlag = true;
                    $val['aob_trend']['a'] = [
                        'home_trend' => $aRes[$val['game_id']]['home_asian_trend'],
                        'away_trend' => $aRes[$val['game_id']]['away_asian_trend'],
                        'trend_dif' => $aRes[$val['game_id']]['asian_trend'],
                    ];
                }
                if(isset($bRes[$val['game_id']]))
                {
                    if($val['aob_trend']['b']['home_trend'] != $bRes[$val['game_id']]['home_ball_trend'] || $val['aob_trend']['b']['away_trend'] != $bRes[$val['game_id']]['away_ball_trend'] || $val['aob_trend']['b']['trend_dif'] != $bRes[$val['game_id']]['ball_trend']) $upFlag = true;
                    $val['aob_trend']['b'] = [
                        'home_trend' => $bRes[$val['game_id']]['home_ball_trend'],
                        'away_trend' => $bRes[$val['game_id']]['away_ball_trend'],
                        'trend_dif' => $bRes[$val['game_id']]['ball_trend'],
                    ];
                }

                if($upFlag === true) $mRes = $mService->update('fb_game', ['aob_trend'=>$val['aob_trend']], array('game_id'=>$val['game_id']),'set');
            }

        }
        if($pFlag == true) monitoring('end');

    }



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
}