<?php
/**
 +------------------------------------------------------------------------------
 * AppbkService   App篮球服务类（5.0）
 +------------------------------------------------------------------------------
 * Copyright (c) 2017 http://www.qqty.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>  2017-08-29
 +------------------------------------------------------------------------------
*/
namespace Api510\Services;

class AppbkService
{
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

        $rData = $oData = [];
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

                //var_dump($arr);
                if ($field == 'oodds')
                {
                    //$company_id = M('BkCompany')->where(['jb_id' => $arr[1]])->getField('id');
                    $companyId = $comIds[$arr[1]];
                }
                else
                {
                    $arr[0] = ucfirst($arr[0]);
                    $companyId = array_search($arr[0], $bk_company);
                    if ($companyId == false) {
                        continue;
                    }
                }

                if($type == 1)
                {
                    $tj = $this->aTrend($arr[3],$arr[6],$arr[2],$arr[5],$arr[4],$arr[7]);
                    $oddsGj['h'] = $oddsGj['h'] + $tj['h'];
                    $oddsGj['a'] = $oddsGj['a'] + $tj['a'];
                }
                else if($type == 3)
                {
                    $tj = $this->bTrend($arr[3],$arr[6],$arr[2],$arr[5],$arr[4],$arr[7]);
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
                $aTemp[1] = (string) $companyId;  //公司id
                $aTemp[2] = $arr[2];  //
                $aTemp[3] = $field == 'oodds' ? '' : $arr[3];  //
                $aTemp[4] = $field == 'oodds' ? $arr[3] : $arr[4];  //
                $aTemp[5] = $field == 'oodds' ? $arr[4] : $arr[5];  //
                $aTemp[6] = $field == 'oodds' ? '' : $arr[6];  //
                $aTemp[7] = $field == 'oodds' ? $arr[5] : $arr[7];  //
                $oData[$k] = $aTemp;
            }

            $rData['detailOdds'] = $oData;

            if($type == 1 || $type == 3)
                $rData['aobTrend'] = $oddsGj;
            else
                $rData['aobTrend'] = $oddsGje;
        }
        return $rData;
    }

    /**
     * 获取篮球亚欧赔界面数据
     * @return array
     */
    public function getbkEurroOdds($gameId)
    {
        if(empty($gameId)) return false;

        $map['game_id'] = (int) $gameId;
        $res = M('BkEuroodds')->field('game_id,europe_cname,company_id,from_oddsid,odds_val')->where($map)->select();
        //var_dump($res);exit;
        $eurComp = C('DB_BK_EUR_COMPANY');

        $oddsGj = ['h'=>['rise'=>0,'equal'=>0,'drop'=>0],'a'=>['rise'=>0,'equal'=>0,'drop'=>0]];
        $rData = $sbData = $jcData = $oData = $ooData =[];

        if(!empty($res))
        {
            foreach($res as $k =>$v)
            {
                $oddsArr = $startOdds = $endOdds = [];
                $companyID = $v['company_id'];
                $oddsArr = explode('!',$v['odds_val']);

                if(count($oddsArr) == 1)
                {
                    $endOdds = $oddsArr[0];
                    $endfswOdds = explode('^',$endOdds);
                    //客户端使用公司名称字段是1
                    $temp = [
                        0 => $v['europe_cname'],
                        1 => $v['from_oddsid'],
                        2 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                        3 => '',
                        4 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                        5 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                        6 => '',
                        7 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                        8 => !empty($companyID)?(string)$companyID:'',
                    ];
                    if(isset($eurComp[$companyID]))
                    {
                        $oddsGj['h']['equal'] = $oddsGj['h']['equal']+2;
                        $oddsGj['a']['equal'] = $oddsGj['a']['equal']+2;
                    }
                }
                else
                {
                    $endOdds = $oddsArr[0];
                    $endfswOdds = explode('^',$endOdds);
                    $startOdds = array_pop($oddsArr);
                    $startfswOdds = explode('^',$startOdds);
                    if(count($startfswOdds) < 2 && count($oddsArr) != 1)
                    {
                        $startOdds = array_pop($oddsArr);
                        $startfswOdds = explode('^',$startOdds);
                    }

                    $temp = [
                        0 => $v['europe_cname'],
                        1 => $v['from_oddsid'],
                        2 => $startfswOdds[0] == null?'':sprintf("%.2f",$startfswOdds[0]),
                        3 => '',
                        4 => $startfswOdds[1] == null?'':sprintf("%.2f",$startfswOdds[1]),
                        5 => $endfswOdds[0] == null?'':sprintf("%.2f",$endfswOdds[0]),
                        6 => '',
                        7 => $endfswOdds[1] == null?'':sprintf("%.2f",$endfswOdds[1]),
                        8 => !empty($companyID)?(string)$companyID:'',
                    ];
                    if(isset($eurComp[$companyID]))
                    {
                        $tj = $this->eurTrend($startfswOdds[0],$endfswOdds[0]);
                        $oddsGj['h']['rise'] = $oddsGj['h']['rise'] + $tj['h']*2;
                        $oddsGj['h']['equal'] = $oddsGj['h']['equal'] + $tj['d']*2;
                        $oddsGj['h']['drop'] = $oddsGj['h']['drop'] + $tj['a']*2;

                        $tj = $this->eurTrend($startfswOdds[1],$endfswOdds[1]);
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
                    $ooData[] = $temp;
                    continue;
                }
                $oData[] = $temp;
            }
            if(!empty($ooData))
            {
                foreach($ooData as $kk=>$vv)
                {
                    array_unshift($oData,$vv);
                }
            }
            if(!empty($sbData)) array_unshift($oData,$sbData);
            if(!empty($jcData)) array_unshift($oData,$jcData);

            $rData['detailOdds'] = $oData;
            $rData['aobTrend'] = $oddsGj;

        }
        return $rData;
    }

    /**
     * 获取篮球欧赔历史数据
     * @return array
     */
    public function getbkEuroOddsHis($gameId,$companyId)
    {
        if(empty($gameId)) return false;

        $map['game_id'] = (int) $gameId;
        $map['from_oddsid'] = (int) $companyId;

        $res = M('BkEuroodds')->field('game_id,europe_cname,company_id,from_oddsid,odds_val')->where($map)->find();

        $rData = [];
        if(!empty($res))
        {
            $odds = explode('!',$res['odds_val']);

            foreach($odds as $k=>$v)
            {
                $aTemp = explode('^',$v);
                $temp = [
                    0 => $aTemp[0],
                    1 => '',
                    2 => $aTemp[1],
                    3 => date('Y-m-d H:i',strtotime($aTemp[2])),
                ];
                $rData[] = $temp;
            }
        }
        return $rData;
    }

    /**
     * 根据盘口、初盘、即时盘计算倾向
     * @return array  计算结果
     */
    public function aTrend($cExp,$jExp,$hCodds,$hJodds,$aCodds,$aJodds)
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
            $defaultHomeImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/bk_ht.png';
            $defaultAwayImg = SITE_URL.$_SERVER['HTTP_HOST'].'/Public/Home/images/common/bk_at.png';

            $dataArr=  explode('$', $data);
            unset($dataArr[0]);
            $dataArr=array_merge($dataArr);
            foreach ($dataArr as $k=>$v){
                $te=  explode('!', $v);
                $dataArr[ $k] = $te;
            }

            $playerIds = [];
            foreach($dataArr as $k => $v)
            {
                $num = count($v);
                foreach($v as $k2 => $v2)
                {
                    if(($num-2) <= $k2) continue;
                    $temp = explode('^',$v2);
                    $playerIds[] = $temp[0];
                }
            }

            $pIds = [];
            $position = ['后卫' =>'G','前锋' =>'F','中锋' =>'C'];
            if(!empty($playerIds))
            {
                $map['player_id'] = array('in',implode(',',$playerIds));
                $res = M('BkPlayer')->field('player_id,player_number,player_position')->where($map)->select();
                if(!empty($res))
                {
                    foreach($res as $k => $v)
                    {
                        $pIds[$v['player_id']] = [
                            'player_number'    =>  $v['player_number'],
                            'player_position'  =>  $position[$v['player_position']],
                        ];
                    }
                }
            }
            $rData = [];
            foreach($dataArr as $k =>$v)
            {
                $num = count($v);
                foreach($v as $k2 => $v2)
                {
                    if(($num-2) <= $k2) continue;
                    $temp = explode('^',$v2);
                    if($temp[5] =='' || $temp[5] ==' ')
                    {
                        $val = [
                            0 =>$temp[1],
                            1 =>$temp[2],
                            2 =>$temp[3],
                            3 =>isset($pIds[$temp[0]])?$pIds[$temp[0]]['player_position']:$temp[5],
                            4 =>isset($pIds[$temp[0]])?$pIds[$temp[0]]['player_number']:'',
                            5 =>$temp[0],
                        ];
                        if($val[3] === null ) $val[3] = '';

                        if($k == 0)
                            $val[] = $defaultHomeImg;
                        else
                            $val[] = $defaultAwayImg;

                        $rData[1][$k][]=$val;
                    }
                    else
                    {
                        $val = [
                            0 =>$temp[1],
                            1 =>$temp[2],
                            2 =>$temp[3],
                            3 =>$temp[5],
                            4 =>isset($pIds[$temp[0]])?$pIds[$temp[0]]['player_number']:'',
                            5 =>$temp[0],
                        ];
                        if($val[3] === null ) $val[3] = '';

                        if($k == 0)
                            $val[] = $defaultHomeImg;
                        else
                            $val[] = $defaultAwayImg;

                        $rData[0][$k][]=$val;
                    }
                }
            }
        }
        return $rData;
    }



}

?>