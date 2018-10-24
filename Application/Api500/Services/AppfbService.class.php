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
namespace Api500\Services;

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
	 * SB公司赔率历史数据————mongo数据
	 * @param  int    $gameId  赛事ID
	 * @param  int    $type    类别：1亚，2欧，3大小
	 * @return array  SB公司赔率历史数据
	 */
	public function getSBhisOdds($gameId,$type=1)
	{
		if(empty($gameId)) return false;
		$mongodb = mongoService();
		$game = $mongodb->select('fb_game',['game_id'=>(int)$gameId],['odds_history.3', 'game_start_timestamp']);
		
		$data = [];
		$year = date("Y", $game[0]['game_start_timestamp']);
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
					if(is_numeric($v[0])) $v[0] .= "'";
					$temp = [
						0 => $v[2],
						1 => changeSnExpTwo($v[3]),
						2 => $v[4],
						3 => $year.'-'.$v[5],
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
					if(is_numeric($v[0])) $v[0] .= "'";
					$temp = [
						0 => $v[2],
						1 => $v[3],
						2 => $v[4],
						3 => $year.'-'.$v[5],
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
                    if(is_numeric($v[0])) $v[0] .= "'";
					$temp = [
						0 => $v[2],
						1 => changeSnExpTwo($v[3]),
						2 => $v[4],
						3 => $year.'-'.$v[5],
						4 => $v[1],
						5 => $v[0]
					];
					$data[] = $temp;
				}
				break;
		}
		return array_reverse($data);
	}
 

}


?>