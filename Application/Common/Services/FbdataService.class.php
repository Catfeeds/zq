<?PHP
/**
 +------------------------------------------------------------------------------
 * FbdataService   足球赛事数据处理类
 +------------------------------------------------------------------------------
 * Copyright (c) 2016 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <huangmg@qc.mail>
 +------------------------------------------------------------------------------
*/
namespace Common\Services;
class FbdataService
{
    public $data = [];

    /**
     * [__construct 构造函数]
     */
    public function __construct()
    {
        #code
    }

    /**
     * [getMatchInt 计算联赛积分]
     * @param  int  $gameId 接口参数
     * @return string         源数据
     */
    public function getMatchInt($gameId)
    {
        if(empty($gameId)) return false;

        $map['game_id'] = (int) $gameId;

        $Fbinfo = M('GameFbinfo');

        $res = $Fbinfo->table('qc_game_fbinfo a')->field('a.union_id,home_team_id,away_team_id,years,year_list,gtime')->join('LEFT JOIN qc_union u ON a.union_id=u.union_id')->where($map)->find();

        $years = array_shift(explode(',',$res['year_list']));

        $homeArr = $this->getTeamMatchInt($res['home_team_id'] , $res['union_id'] , $res['gtime'], $years);
        $awayArr = $this->getTeamMatchInt($res['away_team_id'] , $res['union_id'] , $res['gtime'], $years);
        return [$homeArr,$awayArr];
    }


    /**
     * [getTeamMatchInt 计算球队联赛积分]
     * @param  int  $gameId 接口参数
     * @return string         源数据
     */
    public function getTeamMatchInt($teamId , $unionId , $gtime, $year)
    {
        if(empty($teamId) || empty($unionId) || empty($year)) return false;

        $sql = sprintf('select * from qc_game_fbinfo where game_state = -1 and status = 1 and union_id=%d and years =%d and (home_team_id=%d or away_team_id=%d) and gtime<%d order by gtime desc' , $unionId ,$year, $teamId ,$teamId , $gtime);
        $res = M()->query($sql);
        if(empty($res)) return null;

        $fswHome = $fswAway = $pswHome = $pswAway = $fswRecent= $pswRecent = [
            'total' => 0,
            'win' => 0,
            'draw' => 0,
            'lose' => 0,
            'get' => 0,
            'miss' => 0,
            'int' => 0,
            'rank' => 0,
        ];
        $rData = [];
        foreach($res as $k=>$v)
        {
            if($v['home_team_id'] == $teamId)
            { //主
                $score = explode('-',$v['score']);
                $fswHome['total']++;
                $fswHome['get'] += $score[0];
                $fswHome['miss'] += $score[1];
                if($score[0] > $score[1])
                {
                    $fswHome['win']++;
                    $fswHome['int'] += 3;
                }
                else if($score[0] < $score[1])
                {
                    $fswHome['lose']++;
                }
                else
                {
                    $fswHome['draw']++;
                    $fswHome['int'] += 1;
                }

                $halfScore = explode('-',$v['half_score']);
                $pswHome['total']++;
                $pswHome['get'] += $halfScore[0];
                $pswHome['miss'] += $halfScore[1];
                if($halfScore[0] > $halfScore[1])
                {
                    $pswHome['win']++;
                    $pswHome['int'] += 3;
                }
                else if($halfScore[0] < $halfScore[1])
                {
                    $pswHome['lose']++;
                }
                else
                {
                    $pswHome['draw']++;
                    $pswHome['int'] += 1;
                }
            }
            else
            { //客
                $score = explode('-',$v['score']);
                $fswAway['total']++;
                $fswAway['get'] += $score[1];
                $fswAway['miss'] += $score[0];
                if($score[1] > $score[0])
                {
                    $fswAway['win']++;
                    $fswAway['int'] += 3;
                }
                else if($score[1] < $score[0])
                {
                    $fswAway['lose']++;
                }
                else
                {
                    $fswAway['draw']++;
                    $fswAway['int'] += 1;
                }

                $halfScore = explode('-',$v['half_score']);
                $pswAway['total']++;
                $pswAway['get'] += $halfScore[1];
                $pswAway['miss'] += $halfScore[0];
                if($halfScore[1] > $halfScore[0])
                {
                    $pswAway['win']++;
                    $pswAway['int'] += 3;
                }
                else if($halfScore[1] < $halfScore[0])
                {
                    $pswAway['lose']++;
                }
                else
                {
                    $pswAway['draw']++;
                    $pswAway['int'] += 1;
                }
            }

            if($k == 5)
            {
                #全近6
                $fswRecent = [
                    'total' => 6,
                    'win' => $fswHome['win'] + $fswAway['win'],
                    'draw' => $fswHome['draw'] + $fswAway['draw'],
                    'lose' => $fswHome['lose'] + $fswAway['lose'],
                    'get' => $fswHome['get'] + $fswAway['get'],
                    'miss' => $fswHome['miss'] + $fswAway['miss'],
                    'int' => $fswHome['int'] + $fswAway['int'],
                    'rank' => 0,
                ];
                #半近6
                $pswRecent = [
                    'total' => 6,
                    'win' => $pswHome['win'] + $pswAway['win'],
                    'draw' => $pswHome['draw'] + $pswAway['draw'],
                    'lose' => $pswHome['lose'] + $pswAway['lose'],
                    'get' => $pswHome['get'] + $pswAway['get'],
                    'miss' => $pswHome['miss'] + $pswAway['miss'],
                    'int' => $pswHome['int'] + $pswAway['int'],
                    'rank' => 0,
                ];
            }
            if(count($res) <6 && count($res) == $k+1)
            {
                #全近6
                $fswRecent = [
                    'total' => $fswHome['total'] + $fswAway['total'],
                    'win' => $fswHome['win'] + $fswAway['win'],
                    'draw' => $fswHome['draw'] + $fswAway['draw'],
                    'lose' => $fswHome['lose'] + $fswAway['lose'],
                    'get' => $fswHome['get'] + $fswAway['get'],
                    'miss' => $fswHome['miss'] + $fswAway['miss'],
                    'int' => $fswHome['int'] + $fswAway['int'],
                    'rank' => 0,
                ];
                #半近6
                $pswRecent = [
                    'total' => $pswHome['total'] + $pswAway['total'],
                    'win' => $pswHome['win'] + $pswAway['win'],
                    'draw' => $pswHome['draw'] + $pswAway['draw'],
                    'lose' => $pswHome['lose'] + $pswAway['lose'],
                    'get' => $pswHome['get'] + $pswAway['get'],
                    'miss' => $pswHome['miss'] + $pswAway['miss'],
                    'int' => $pswHome['int'] + $pswAway['int'],
                    'rank' => 0,
                ];
            }
        }
        #总计
        $total = [
            'total' => count($res),
            'win' => $fswHome['win'] + $fswAway['win'],
            'draw' => $fswHome['draw'] + $fswAway['draw'],
            'lose' => $fswHome['lose'] + $fswAway['lose'],
            'get' => $fswHome['get'] + $fswAway['get'],
            'miss' => $fswHome['miss'] + $fswAway['miss'],
            'int' => $fswHome['int'] + $fswAway['int'],
            'rank' => 0,
        ];
        $pswTotal = [
            'total' => count($res),
            'win' => $pswHome['win'] + $pswAway['win'],
            'draw' => $pswHome['draw'] + $pswAway['draw'],
            'lose' => $pswHome['lose'] + $pswAway['lose'],
            'get' => $pswHome['get'] + $pswAway['get'],
            'miss' => $pswHome['miss'] + $pswAway['miss'],
            'int' => $pswHome['int'] + $pswAway['int'],
            'rank' => 0,
        ];
        //var_dump($fswHome , $fswAway , $pswHome , $pswAway,$fswRecent, $pswRecent ,$total );exit;
        $rData = [
            'total'=>$total,
            'fswHome'=>$fswHome,
            'fswAway'=>$fswAway,
            'fswRecent'=>$fswRecent,
            'pswTotal'=>$pswTotal,
            'pswHome'=>$pswHome,
            'pswAway'=>$pswAway,
            'pswRecent'=>$pswRecent,
        ];
        return $rData;
    }

    /**
     * [getMatchFight 两队对战历史数据]
     * @param  int  $hTeamId 主队
     * @param  int  $aTeamId 客队
     * @param  int  $gtime   时间点
     * @return array
     */
    public function getMatchFight($hTeamId , $aTeamId ,$gtime, $lang = 1)
    {
        if(empty($hTeamId) || empty($aTeamId) || empty($gtime)) return false;

        $sql = sprintf('select a.* ,u.union_color, t.team_name as home_team_name2, t2.team_name as away_team_name2 from qc_game_fbinfo a left join qc_union u on a.union_id = u.union_id  left join qc_game_team as t on a.home_team_id = t.team_id  left join qc_game_team as t2 on a.away_team_id = t2.team_id where a.game_state = -1 and a.status = 1 and ((a.home_team_id=%d and a.away_team_id=%d) or (a.home_team_id=%d and a.away_team_id=%d) ) and a.gtime<%d order by a.gtime desc limit 20' ,$hTeamId , $aTeamId, $aTeamId , $hTeamId , $gtime);

        $res = M()->query($sql);
        if(empty($res)) return null;

        if($lang == 1)
            $langKey = 0;
        else
            $langKey = 1;

        $rData = [];

        foreach ($res as $k => $v) {
            $res[$k]['home_team_name'] = $res[$k]['home_team_name2'];
            $res[$k]['away_team_name'] = $res[$k]['away_team_name2'];
        }

        foreach($res as $k=>$v)
        {
            $htn = explode(',' ,$v['home_team_name']);
            $hTeamName = $htn[$langKey];
            $atn = explode(',' ,$v['away_team_name']);
            $aTeamName =  $atn[$langKey];
            $utn = explode(',' ,$v['union_name']);
            $unionName = $utn[$langKey];
            $score = explode('-',$v['score']);
            $halfScore = explode('-',$v['half_score']);
            #胜平负
            if($v['home_team_id'] == $hTeamId)
                $winLose = $this->winLost($v['score']);
            else
                $winLose = $this->winLost($v['score'] , 2);

            if($winLose === false) $winLose = '';
            #盘路
            $panlu = '';
            if($v['fsw_exp'] == 0 || !empty($v['fsw_exp']))
            {
                if($v['home_team_id'] == $hTeamId)
                    $panlu = $this->panluWin($v['fsw_exp'] , $v['score']);
                else
                    $panlu = $this->panluWin($v['fsw_exp'] , $v['score'], 2);
            }
            if($score[0]+$score[1] >= 3)
                $ball = 1 ;
            else
                $ball = -1 ;

            $temp = [
                0 => date('Y-m-d',$v['gtime']),
                1 => $v['game_id'],
                2 => $unionName,
                3 => !empty($v['union_color'])?$v['union_color']:'',
                4 => $v['home_team_id'],
                5 => $hTeamName,
                6 => $v['away_team_id'],
                7 => $aTeamName,
                8 => $score[0],
                9 => $score[1],
                10 => $halfScore[0],
                11 => $halfScore[1],
                12 => $v['fsw_exp'] != null?$v['fsw_exp']:'',
                13 => (string)$winLose,
                14 => (string)$panlu,
                15 => (string)$ball,
                16 => $v['union_id'],
            ];
            $rData[] = $temp;
        }
        return $rData;
    }

     /**
     * [getRecentFight 球队近期对战历史数据]
     * @param  int  $hTeamId  球队ID
     * @param  int  $hTeamId  时间
     * @return string         源数据
     */
    public function getRecentFight($hTeamId ,$gtime,$lang = 1)
    {
        if(empty($hTeamId) || empty($gtime)) return false;

        $sql = sprintf('select a.* ,u.union_color, t.team_name as home_team_name2, t2.team_name as away_team_name2 from qc_game_fbinfo a  left join qc_union u on a.union_id = u.union_id  left join qc_game_team as t on a.home_team_id = t.team_id  left join qc_game_team as t2 on a.away_team_id = t2.team_id  where a.game_state = -1 and a.status = 1 and (a.home_team_id=%d or a.away_team_id=%d) and a.gtime<%d order by a.gtime desc limit 20' ,$hTeamId , $hTeamId ,$gtime);
        $res = M()->query($sql);
        if(empty($res)) return null;

        if($lang == 1)
            $langKey = 0;
        else
            $langKey = 1;

        $rData = [];

        foreach ($res as $k => $v) {
            $res[$k]['home_team_name'] = $res[$k]['home_team_name2'];
            $res[$k]['away_team_name'] = $res[$k]['away_team_name2'];
        }

        foreach($res as $k=>$v)
        {
            $htn = explode(',' ,$v['home_team_name']);
            $hTeamName = $htn[$langKey];
            $atn = explode(',' ,$v['away_team_name']);
            $aTeamName =  $atn[$langKey];
            $utn = explode(',' ,$v['union_name']);
            $unionName = $utn[$langKey];
            $score = explode('-',$v['score']);
            $halfScore = explode('-',$v['half_score']);
            #胜平负
            if($v['home_team_id'] == $hTeamId)
                $winLose = $this->winLost($v['score']);
            else
                $winLose = $this->winLost($v['score'] , 2);

            if($winLose === false) $winLose = '';
            #盘路
            $panlu = '';
            if($v['fsw_exp'] == 0 || !empty($v['fsw_exp']))
            {
                if($v['home_team_id'] == $hTeamId)
                    $panlu = $this->panluWin($v['fsw_exp'] , $v['score']);
                else
                    $panlu = $this->panluWin($v['fsw_exp'] , $v['score'], 2);
            }

            if($score[0]+$score[1] >= 3)
                $ball = 1 ;
            else
                $ball = -1 ;

            $temp = [
                0 => date('Y-m-d',$v['gtime']),
                1 => $v['game_id'],
                2 => $unionName,
                3 => !empty($v['union_color'])?$v['union_color']:'',
                4 => $v['home_team_id'],
                5 => $hTeamName,
                6 => $v['away_team_id'],
                7 => $aTeamName,
                8 => $score[0],
                9 => $score[1],
                10 => $halfScore[0],
                11 => $halfScore[1],
                12 => $v['fsw_exp'] !== null?$v['fsw_exp']:'',
                13 => (string)$winLose,
                14 => (string)$panlu,
                15 => (string)$ball,
                16 => $v['union_id'],
            ];
            $rData[] = $temp;
        }
        return $rData;
    }

     /**
     * [getPanlu 球队联赛盘路走势]
     * @param  int  $hTeamId  球队ID
     * @param  int  $unionId  联赛ID
     * @param  int  $gtime    时间
     * @return string         源数据
     */
    public function getPanlu($hTeamId ,$unionId ,$gtime)
    {
        if(empty($hTeamId) || empty($gtime) || empty($unionId)) return false;

        $sql = sprintf('select * from qc_game_fbinfo a left join qc_union u on a.union_id = u.union_id where game_state = -1 and status = 1 and (home_team_id=%d or away_team_id=%d) and gtime<%d and a.union_id=%d and fsw_exp is not null order by gtime desc limit 6' ,$hTeamId , $hTeamId ,$gtime,$unionId);

        $res = M()->query($sql);
        if(empty($res)) return null;

        if($lang == 1)
            $langKey = 0;
        else
            $langKey = 1;

        $rData = [];
        $hArr = $aArr = [0=>0 ,1=>0 ,2=>0 ,3=>0 ,4=>0 ,5=>0 ,6=>0 ,7=>0 ,8=>0];
        $aPanlu = $bPanlu = [];

        foreach($res as $k=>$v)
        {
            if(empty($v['fsw_exp'])) continue;
            $score = explode('-',$v['score']);
            #盘路
            $panlu = '';
            if(!empty($v['fsw_exp']))
            {
                if($v['home_team_id'] == $hTeamId)
                {
                    $panlu = $this->panluWin($v['fsw_exp'] , $v['score']);
                    $hArr[0] ++;
                    if($panlu == 1)
                    {
                        $hArr[1] ++;
                    }
                    else if($panlu == 0)
                    {
                        $hArr[2] ++;
                    }
                    else
                    {
                        $hArr[3] ++;
                    }

                    if($score[0]+$score[1] >= 3)
                        $hArr[5] ++;
                    else
                        $hArr[7] ++;
                }
                else
                {
                    $panlu = $this->panluWin($v['fsw_exp'] , $v['score'], 2);
                    $aArr[0] ++;
                    if($panlu == 1)
                    {
                        $aArr[1] ++;
                    }
                    else if($panlu == 0)
                    {
                        $aArr[2] ++;
                    }
                    else
                    {
                        $aArr[3] ++;
                    }

                    if($score[0]+$score[1] >= 3)
                        $aArr[5] ++;
                    else
                        $aArr[7] ++;
                }

                if($score[0]+$score[1] >= 3)
                    $ball = 1 ;
                else
                    $ball = -1 ;
                $aPanlu[] = $panlu;
                $bPanlu[] = $ball;
            }
        }
        $hArr[4] = round($hArr[1]/$hArr[0],2);
        $hArr[6] = round($hArr[5]/$hArr[0],2);
        $hArr[8] = round($hArr[7]/$hArr[0],2);

        $aArr[4] = round($aArr[1]/$aArr[0],2);
        $aArr[6] = round($aArr[5]/$aArr[0],2);
        $aArr[8] = round($aArr[7]/$aArr[0],2);

        $total = [
            0 => $hArr[0]+$aArr[0],
            1 => $hArr[1]+$aArr[1],
            2 => $hArr[2]+$aArr[2],
            3 => $hArr[3]+$aArr[3],
            4 => round(($hArr[1]+$aArr[1])/($hArr[0]+$aArr[0]),2),
            5 => $hArr[5]+$aArr[5],
            6 => round(($hArr[5]+$aArr[5])/($hArr[0]+$aArr[0]),2),
            7 => $hArr[7]+$aArr[7],
            8 => round(($hArr[7]+$aArr[7])/($hArr[0]+$aArr[0]),2),
        ];

        $rData = [0=>$total,1=>$hArr,2=>$aArr,3=>$aPanlu,4=>$bPanlu];
        if($total[0] == 0) return null;
        return $rData;
    }

    /**
     * [getFutureThree 球队未来三场]
     * @param  int  $hTeamId  球队ID
     * @param  int  $gtime    时间
     * @param  int  $lang     语言
     * @return string         源数据
     */
    public function getFutureThree($hTeamId ,$gtime,$lang =1)
    {
        if(empty($hTeamId) || empty($gtime)) return false;

        $sql = sprintf('select * from qc_game_fbinfo a left join qc_union u on a.union_id = u.union_id where status = 1 and (home_team_id=%d or away_team_id=%d) and gtime>%d order by gtime limit 3' ,$hTeamId , $hTeamId ,$gtime);

        $res = M()->query($sql);
        if(empty($res)) return null;

        if($lang == 1)
            $langKey = 0;
        else
            $langKey = 1;
        $rData = [];
        foreach($res as $k=>$v)
        {
            $htn = explode(',' ,$v['home_team_name']);
            $hTeamName = $htn[$langKey];
            $atn = explode(',' ,$v['away_team_name']);
            $aTeamName =  $atn[$langKey];
            $utn = explode(',' ,$v['union_name']);
            $unionName = $utn[$langKey];
            $day = (string) floor(($v['gtime']-$gtime)/86400);

            if($v['home_team_id'] == $hTeamId)
            {
                $temp = [
                    0 => $hTeamName,
                    1 => $unionName,
                    2 => date('Y-m-d',$v['gtime']),
                    3 => '1',
                    4 => $aTeamName,
                    5 => $day,
                ];
            }
            else
            {
                $temp = [
                    0 => $aTeamName,
                    1 => $unionName,
                    2 => date('Y-m-d',$v['gtime']),
                    3 => '-1',
                    4 => $hTeamName,
                    5 => $day,
                ];
            }
            $rData[] = $temp;
        }
        return $rData;
    }


     /**
     * 根据分数，主客计算胜负平
     * @return json
     */
    public function teamRank($hTeamId , $aTeamId, $unionId, $gtime, $years)
    {
        if(empty($hTeamId) || empty($aTeamId) || empty($unionId) || empty($gtime) || empty($years)) return false;

        $GameFbinfo = M('GameFbinfo');
        $map['union_id'] = $unionId;
        $map['years'] = $years;
        $map['gtime'] = array('lt',$gtime);

        $baseRes = $GameFbinfo->field('home_team_id,home_team_name,away_team_id,away_team_name,score')->where($map)->select();

        $rData = [];
        if(empty($baseRes)) return $rData;

        $intRankHome = $intRankAway = [];
        foreach($baseRes as $k=>$v)
        {
            $score = explode('-',$v['score']);
            if(!isset($intRankHome[$v['home_team_id']])) $intRankHome[$v['home_team_id']] = [0=>0,1=>0,2=>0,3=>$v['home_team_id']];
            if(!isset($intRankAway[$v['away_team_id']])) $intRankAway[$v['away_team_id']] = [0=>0,1=>0,2=>0,3=>$v['away_team_id']];

            if($score[0] > $score[1])
            {
                $intRankHome[$v['home_team_id']][0] += 3;
                $intRankHome[$v['home_team_id']][1] += 1;
                $intRankHome[$v['home_team_id']][2] += $score[0];
                $intRankAway[$v['away_team_id']][2] += $score[1];
            }
            else if($score[0] < $score[1])
            {
                $intRankAway[$v['away_team_id']][0] += 3;
                $intRankAway[$v['away_team_id']][1] += 1;
                $intRankHome[$v['home_team_id']][2] += $score[0];
                $intRankAway[$v['away_team_id']][2] += $score[1];
            }
            else
            {
                $intRankHome[$v['home_team_id']][0] += 1;
                $intRankAway[$v['away_team_id']][0] += 1;

                $intRankHome[$v['home_team_id']][2] += $score[0];
                $intRankAway[$v['away_team_id']][2] += $score[1];
            }
        }

        $sort1 = $sort2 = $sort3 = [];
        $intRankTotal = [];
        foreach($intRankHome as $k=>$v)
        {
            $sort1[] = $v[0];
            $sort3[] = $intRankHome[$k][0] + $intRankAway[$k][0];;
            $intRankTotal[$k][0] = $intRankHome[$k][0] + $intRankAway[$k][0];
            $intRankTotal[$k][1] = $intRankHome[$k][1] + $intRankAway[$k][1];
            $intRankTotal[$k][2] = $intRankHome[$k][2] + $intRankAway[$k][2];
            $intRankTotal[$k][3] = $k;
        }
        foreach($intRankAway as $k=>$v)
        {
            $sort2[] = $v[0];
        }

        array_multisort($intRankHome,SORT_DESC ,$sort1);
        array_multisort($intRankAway,SORT_DESC ,$sort2);
        array_multisort($intRankTotal,SORT_DESC ,$sort3);

        $hRank = $aRank = $totalRank = [];
        foreach($intRankTotal as $k=>$v)
        {
            if($v[3] == $hTeamId) $hRank['total'] = $k+1;
            if($v[3] == $aTeamId) $aRank['total'] = $k+1;
        }

        foreach($intRankHome as $k=>$v)
        {
            if($v[3] == $hTeamId) $hRank['home'] = $k+1;
            if($v[3] == $aTeamId) $aRank['home'] = $k+1;
        }

        foreach($intRankAway as $k=>$v)
        {
            if($v[3] == $hTeamId) $hRank['away'] = $k+1;
            if($v[3] == $aTeamId) $aRank['away'] = $k+1;
        }
        $rData = [$hRank,$aRank];
        return $rData;
    }

    /**
     * [getSkilldata 计统率]
     * @param  int  $teamId  球队ID
     * @param  int  $gtime    时间
     * @return string         源数据
     */
    public function getSkilldata($teamId ,$gtime)
    {
        if(empty($teamId)) return false;

        $sql = sprintf('select game_date,game_id,home_team_id,away_team_id,score from qc_game_fbinfo where status = 1 and game_state = -1 and (home_team_id=%d or away_team_id=%d) and gtime<%d order by gtime desc limit 10' ,$teamId , $teamId ,$gtime);
        $res = M()->query($sql);
        if(empty($res)) return null;

        $games = $id = [];
        foreach($res as $k=>$v)
        {
            $games[$v['game_id']] = $v;
            $id[] = $v['game_id'];
        }
        $totalNum = count($res);

        $sql = 'select game_id,s_type,home_value,away_value from qc_statistics_fb where 1 and s_type in(6,11,3,5,14) and game_id in ('.implode(',',$id).') order by s_id';
        $res2 = M()->query($sql);
        if(empty($res2)) return null;

        $sgId = [];
        foreach($res2 as $k=>$v)
        {
            switch($v['s_type'])
            {
                case '6':
                    $games[$v['game_id']]['corner'] = $games[$v['game_id']]['home_team_id'] == $teamId?$v['home_value']:$v['away_value'];
                    break;
                case '11':
                    $games[$v['game_id']]['yellow'] = $games[$v['game_id']]['home_team_id'] == $teamId?$v['home_value']:$v['away_value'];
                    break;
                case '3':
                    $games[$v['game_id']]['shoot'] = $games[$v['game_id']]['home_team_id'] == $teamId?$v['home_value']:$v['away_value'];
                    break;
                case '5':
                    $games[$v['game_id']]['foul'] = $games[$v['game_id']]['home_team_id'] == $teamId?$v['home_value']:$v['away_value'];
                    break;
                case '14':
                    $games[$v['game_id']]['ball_control'] = $games[$v['game_id']]['home_team_id'] == $teamId?$v['home_value']:$v['away_value'];
                    break;
            }
            $sgId[$v['game_id']] = '';
        }

        if($totalNum > 3  && count($sgId) >3)
        {
            $num1 = 3;
            $num2 = count($sgId);
        }
        else
        {
            $num1 = $num2 = count($sgId);
        }

        $rData = [];
        $threeGet = $threeLose = $threeYellow = $threeCorner = $threeShoot = $threeFoul = $threeControl = 0;
        $tenGet = $tenLose = $tenYellow = $tenCorner = $tenShoot = $tenFoul = $tenControl = 0;
        $i = 0;
        foreach($games as $k=>$v)
        {
            $scoreTemp = explode('-',$v['score']);
            if($i<3)
            {
                if($v['home_team_id'] == $teamId)
                {
                    $threeGet += $scoreTemp[0];
                    $threeLose += $scoreTemp[1];
                }
                else
                {
                    $threeGet += $scoreTemp[1];
                    $threeLose += $scoreTemp[0];
                }
                $threeYellow += $v['yellow'];
                $threeCorner += $v['corner'];
                $threeShoot += $v['shoot'];
                $threeFoul += $v['foul'];
                $threeControl += $v['ball_control'];
                $i++;
            }

            if($v['home_team_id'] == $teamId)
            {
                $tenGet += $scoreTemp[0];
                $tenLose += $scoreTemp[1];
            }
            else
            {
                $tenGet += $scoreTemp[1];
                $tenLose += $scoreTemp[0];
            }
            $tenYellow += $v['yellow'];
            $tenCorner += $v['corner'];
            $tenShoot += $v['shoot'];
            $tenFoul += $v['foul'];
            $tenControl += $v['ball_control'];
        }

        $rData['get'] = round($threeGet/$num1, 1) .'/' .round($tenGet/$num2, 1);
        $rData['lose'] = round($threeLose/$num1, 1) .'/' .round($tenLose/$num2, 1);
        $rData['corner'] = round($threeCorner/$num1, 1) .'/' .round($tenCorner/$num2, 1);
        $rData['yellow'] = round($threeYellow/$num1, 1) .'/' .round($tenYellow/$num2, 1);
        //$rData['shoot'] = round($threeShoot/$num1, 1) .'/' .round($tenShoot/$num2, 1);
        $rData['foul'] = round($threeFoul/$num1, 1) .'/' .round($tenFoul/$num2, 1);
        $rData['ball_control'] = round($threeControl/$num1, 1) .'%/' .round($tenControl/$num2, 1).'%';

        return $rData;
    }

     /**
     * [getSkilldataTwo 计统率————数据库取数据]
     * @param  int  $gameId  球队ID
     * @return string         源数据
     */
    public function getSkilldataTwo($gameId)
    {
        if(empty($gameId)) return false;
        $sql = "select game_id,jt_value from qc_fb_matchodds where 1 and game_id ='".$gameId."'";
        $res = M()->query($sql);

        if(empty($res[0]['jt_value'])) return null;

        $arr = json_decode($res[0]['jt_value']);

        $rData = [
            0 =>[
                'name'=>'skill_data1',
                'content'=>[
                    0 => isset($arr->Goals_h3)?$arr->Goals_h3.'/'.$arr->Goals_h10:'',
                    1 => isset($arr->LossGoals_h3)?$arr->LossGoals_h3.'/'.$arr->LossGoals_h10:'',
                    2 => isset($arr->Corner_h3)?$arr->Corner_h3.'/'.$arr->Corner_h10:'',
                    3 => isset($arr->Yellow_h3)?$arr->Yellow_h3.'/'.$arr->Yellow_h10 : '',
                    4 => isset($arr->Fouls_h3)?$arr->Fouls_h3.'/'.$arr->Fouls_h10 : '',
                    5 => isset($arr->ControlPrecent_h3)?$arr->ControlPrecent_h3.'%/'.$arr->ControlPrecent_h10.'%' : '',
                ],
            ],
            1 =>[
                'name'=>'skill_data2',
                'content'=>[
                   0 => isset($arr->Goals_g3)?$arr->Goals_g3.'/'.$arr->Goals_g10:'',
                    1 => isset($arr->LossGoals_g3)?$arr->LossGoals_g3.'/'.$arr->LossGoals_g10:'',
                    2 => isset($arr->Corner_g3)?$arr->Corner_g3.'/'.$arr->Corner_g10:'',
                    3 => isset($arr->Yellow_g3)?$arr->Yellow_g3.'/'.$arr->Yellow_g10 : '',
                    4 => isset($arr->Fouls_g3)?$arr->Fouls_g3.'/'.$arr->Fouls_g10 : '',
                    5 => isset($arr->ControlPrecent_g3)?$arr->ControlPrecent_g3.'%/'.$arr->ControlPrecent_g10.'%' : '',
                ],
            ],
        ];

        return $rData;
    }


    /**
     +------------------------------------------------------------------------------
     * 功能函数
     +------------------------------------------------------------------------------
    */

     /**
     * 根据分数，主客计算胜负平
     * @return json
     */
    public function winLost($scores , $type = 1)
    {
        if(empty($scores)) return false;
        $score = explode('-',$scores);
        if($type == 1)
        {
            if($score[0] > $score[1])
            {
                $flag = 1 ;
            }
            else if($score[0] < $score[1])
            {
                $flag = -1 ;
            }
            else
            {
                $flag = 0 ;
            }
        }
        else
        {
            if($score[0] > $score[1])
            {
                $flag = -1 ;
            }
            else if($score[0] < $score[1])
            {
                $flag = 1 ;
            }
            else
            {
                $flag = 0 ;
            }
        }
        return $flag;
    }

     /**
     * 根据分数、盘口，主客计算盘路
     * @return json
     */
    public function panluWin($exp = null, $scores ,$type = 1)
    {
        if(empty($scores) || $exp === null || $exp === '') return false;
        $score = explode('-',$scores);
        if($type == 1)
        {
            if($score[0] - $exp > $score[1])
            {
                $panlu = 1 ;
            }
            else if($score[0] - $exp < $score[1])
            {
                $panlu = -1 ;
            }
            else
            {
                $panlu = 0 ;
            }
        }
        else
        {
            if($score[0] - $exp < $score[1])
            {
                $panlu = 1 ;
            }
            else if($score[0] - $exp > $score[1])
            {
                $panlu = -1 ;
            }
            else
            {
                $panlu = 0 ;
            }
        }
        return $panlu;
    }

}