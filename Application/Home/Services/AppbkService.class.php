<?php
/**
 +------------------------------------------------------------------------------
 * AppfbService   App服务类（1.2）
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqty.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author huangmg <huangmg@qc.mail>
 +------------------------------------------------------------------------------
*/
namespace Home\Services;

class AppbkService
{
    protected $data;

    public function __construct()
    {
        $this->getDataList();
    }

     /**
     * 根据公司ID获取数据分析界面数据
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