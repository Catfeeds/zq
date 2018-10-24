<?php
/**
 +------------------------------------------------------------------------------
 * PcdataService   Web接口服务类
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>
 +------------------------------------------------------------------------------
*/
namespace Home\Services;

class PcdataService
{
    protected $data;

    public function __construct()
    {
        $this->getDataList();
    }

    /**
     * 获取足球change数据
     * @return json
     */
    public function getChange()
    {
        $fileName = DataPath.'other/fbchange.txt';

        $content = '';

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
        }
        return $content;
    }

    /**
     * 当日赛事变化数据解析（数据库数据）
     * @return array 赛事变化数据
     */
    public function getChangeB()
    {
        $rData = [];
        //$res = M()->query('select game_id,game_id_new,change_str,update_time from qc_change_fb where update_time = (select max(update_time) as utime from qc_change_fb) order by id');
        if(S('cache_fb_change2'))
        {
            $rData = S('cache_fb_change2');
            unset($rData['cache']);
        }
        else
        {
            $res = M()->query('select game_id,game_id_new,change_str,update_time from qc_change_fb where update_time = (select update_time as utime from qc_change_fb order by update_time desc limit 1) order by id');
            $rData = [];
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
                        /*$aTime = explode(',',$arr[9]);
                        $aTime[1] = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                        $aTime[2] = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                        $aTemp[11] = implode('',$aTime);   //半场时间*/
                        $aTemp[11] = $arr[9];

                        $aTemp[12] = $arr[16] == null?'':$arr[16];   //主队角球
                        $aTemp[13] = $arr[17] == null?'':$arr[17];  //客队角球


                        $rData[$v['game_id']] = $aTemp;
                    }
                }
            }
            //file_put_contents('testlog.log', 'pc getChangeB+:'.date("Y-m-d H:i:s")."\n",FILE_APPEND );
            $rData['cache'] = 'true';
            S('cache_fb_change2',$rData,1);
            unset($rData['cache']);
        }
        return $rData;
    }

	/**
     * 获取足球Goal数据
     * @return json
     */
    public function getGoal($companyID)
    {
        $fileName = DataPath.'other/goal'.$companyID.'.txt';

        $content = '';

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
        }
        return $content;
    }

    /**
     * 获取足球Goal数据
     * @return json
     */
    public function getGoalB($companyID)
    {
        if(empty($companyId)) $companyID = 3;

        $sql = 'select max(update_time) as utime from qc_fb_goal where company_id='.$companyID;
        $res = M()->query($sql);
        $rData = [];

        if (!empty($res) && $res[0]['utime'] >time()-20)
        {
            $sql = 'select * from qc_fb_goal where update_time ='.$res[0]['utime'].' and company_id='.$companyID;
            $res = M()->query($sql);
            #0,game_id;1-3,让分盘主客；4-6,欧赔主平客；7-9,大小盘主客
            foreach($res as $k=>$v)
            {
                $temp = [];
                $odds1 = explode('^',$v['exp_value']);
                $aOdds = explode(',',$odds1[0]);
                $temp[0] = $v['game_id'];
                if(!empty($aOdds[6]) || !empty($aOdds[7]) || !empty($aOdds[8]))
                {
                    $temp[1] = changeExp($aOdds[7]);
                    $temp[2] = $aOdds[6];
                    $temp[3] = $aOdds[8];
                }
                else if(!empty($aOdds[3]) || !empty($aOdds[4]) || !empty($aOdds[5]))
                {
                    $temp[1] = changeExp($aOdds[4]);
                    $temp[2] = $aOdds[3];
                    $temp[3] = $aOdds[5];
                }
                else
                {
                    $temp[1] = '';
                    $temp[2] = '';
                    $temp[3] = '';
                }

                $oOdds = explode(',',$odds1[1]);
                if(!empty($oOdds[6]) || !empty($oOdds[7]) || !empty($oOdds[8]))
                {
                    $temp[4] = $oOdds[6];
                    $temp[5] = $oOdds[7];
                    $temp[6] = $oOdds[8];
                }
                else if(!empty($oOdds[3]) || !empty($oOdds[4]) || !empty($oOdds[5]))
                {
                    $temp[4] = $oOdds[3];
                    $temp[5] = $oOdds[4];
                    $temp[6] = $oOdds[5];
                }
                else
                {
                    $temp[4] = '';
                    $temp[5] = '';
                    $temp[6] = '';
                }

                $bOdds = explode(',',$odds1[2]);
                if(!empty($bOdds[6]) || !empty($bOdds[7]) || !empty($bOdds[8]))
                {
                    $temp[7] = changeExp($bOdds[7]);
                    $temp[8] = $bOdds[6];
                    $temp[9] = $bOdds[8];
                }
                else if(!empty($bOdds[3]) || !empty($bOdds[4]) || !empty($bOdds[5]))
                {
                    $temp[7] = changeExp($bOdds[4]);
                    $temp[8] = $bOdds[3];
                    $temp[9] = $bOdds[5];
                }
                else
                {
                    $temp[7] = '';
                    $temp[8] = '';
                    $temp[9] = '';
                }
                $rData[] = $temp;
                //$rData[$v['game_id']] = $temp;
            }
        }
        return $rData;
    }

     /**
     * 根据赛事ID获取足球Goal数据
     * @return json
     */
    public function getGoalById($gameId ,$companyID,$is_change = true)
    {
        if(empty($companyID) || empty($gameId)) return false;

        $where['game_id'] = array('in',$gameId);
        $where['company_id'] = $companyID;

        $fbGoal = M('FbGoal');

        $baseRes = $fbGoal->field('id,game_id,company_id,exp_value')->where($where)->select();

        $rData = [];
        if(!empty($baseRes))
        {
            foreach($baseRes as $k=>$v)
            {
                $temp = [];
                $odds1 = explode('^',$v['exp_value']);
                $aOdds = explode(',',$odds1[0]);
                $temp[0] = $v['game_id'];
                if(!empty($aOdds[6]) || !empty($aOdds[7]) || !empty($aOdds[8]))
                {
                    if($is_change == true)
                        $temp[1] = changeExp($aOdds[7]);
                    else
                        $temp[1] = $aOdds[7];
                    $temp[2] = $aOdds[6];
                    $temp[3] = $aOdds[8];
                }
                else if(!empty($aOdds[3]) || !empty($aOdds[4]) || !empty($aOdds[5]))
                {
                    if($is_change == true)
                        $temp[1] = changeExp($aOdds[4]);
                    else
                        $temp[1] = $aOdds[4];
                    $temp[2] = $aOdds[3];
                    $temp[3] = $aOdds[5];
                }
                else
                {
                    $temp[1] = '';
                    $temp[2] = '';
                    $temp[3] = '';
                }

                $oOdds = explode(',',$odds1[1]);
                if(!empty($oOdds[6]) || !empty($oOdds[7]) || !empty($oOdds[8]))
                {
                    $temp[4] = $oOdds[6];
                    $temp[5] = $oOdds[7];
                    $temp[6] = $oOdds[8];
                }
                else if(!empty($oOdds[3]) || !empty($oOdds[4]) || !empty($oOdds[5]))
                {
                    $temp[4] = $oOdds[3];
                    $temp[5] = $oOdds[4];
                    $temp[6] = $oOdds[5];
                }
                else
                {
                    $temp[4] = '';
                    $temp[5] = '';
                    $temp[6] = '';
                }

                $bOdds = explode(',',$odds1[2]);
                if(!empty($bOdds[6]) || !empty($bOdds[7]) || !empty($bOdds[8]))
                {
                    if($is_change == true)
                        $temp[7] = changeExp($bOdds[7]);
                    else
                        $temp[7] = $bOdds[7];
                    $temp[8] = $bOdds[6];
                    $temp[9] = $bOdds[8];
                }
                else if(!empty($bOdds[3]) || !empty($bOdds[4]) || !empty($bOdds[5]))
                {
                    if($is_change == true)
                        $temp[7] = changeExp($bOdds[4]);
                    else
                        $temp[7] = $bOdds[4];
                    $temp[8] = $bOdds[3];
                    $temp[9] = $bOdds[5];
                }
                else
                {
                    $temp[7] = '';
                    $temp[8] = '';
                    $temp[9] = '';
                }
                if($temp[1] != '' || $temp[2] != '' || $temp[3] != '' || $temp[4] != '' || $temp[5] != '' || $temp[6] != '' || $temp[7] != '' || $temp[8] != '' || $temp[9] != '' )
                {
                    $rData[$v['game_id']] = $temp;
                }
            }
        }
        return $rData;
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
                    1 => $oddsTemp[0][1],              //让球盘口
                    2 => $oddsTemp[0][2],              //客队让球赔率
                    3 => $oddsTemp[2][0],              //主队大小赔率
                    4 => $oddsTemp[2][1],              //让球盘口
                    5 => $oddsTemp[2][2],              //客队大小赔率
                    6 => $oddsTemp[1][0],              //主队欧赔赔率
                    7 => $oddsTemp[1][1],              //平赔率
                    8 => $oddsTemp[1][2],               //客队欧赔赔率
                    9 => $oddsTemp[0][3],
                    10 => $oddsTemp[0][4],
                    11 => $oddsTemp[0][5],
                    12 => $oddsTemp[2][3],
                    13 => $oddsTemp[2][4],
                    14 => $oddsTemp[2][5],
                    15 => $oddsTemp[1][3],
                    16 => $oddsTemp[1][4],
                    17 => $oddsTemp[1][5],
                    18 => $oddsTemp[0][6],
                    19 => $oddsTemp[0][7],
                    20 => $oddsTemp[0][8],
                    21 => $oddsTemp[2][6],
                    22 => $oddsTemp[2][7],
                    23 => $oddsTemp[2][8],
                    24 => $oddsTemp[1][6],
                    25 => $oddsTemp[1][7],
                    26 => $oddsTemp[1][8]
                ];
                $fData[$v['game_id']] = $fTemp;
                #半场
                $pTemp = [
                    0 => $oddsTemp[3][0],              //主队让球赔率
                    1 => $oddsTemp[3][1],              //让球盘口
                    2 => $oddsTemp[3][2],              //客队让球赔率
                    3 => $oddsTemp[5][0],              //主队大小赔率
                    4 => $oddsTemp[5][1],              //让球盘口
                    5 => $oddsTemp[5][2],              //客队大小赔率
                    6 => $oddsTemp[4][0],              //主队欧赔赔率
                    7 => $oddsTemp[4][1],              //平赔率
                    8 => $oddsTemp[4][2],               //客队欧赔赔率
                    9 => $oddsTemp[3][3],
                    10 => $oddsTemp[3][4],
                    11 => $oddsTemp[3][5],
                    12 => $oddsTemp[5][3],
                    13 => $oddsTemp[5][4],
                    14 => $oddsTemp[5][5],
                    15 => $oddsTemp[4][3],
                    16 => $oddsTemp[4][4],
                    17 => $oddsTemp[4][5],
                    18 => $oddsTemp[3][6],
                    19 => $oddsTemp[3][7],
                    20 => $oddsTemp[3][8],
                    21 => $oddsTemp[5][6],
                    22 => $oddsTemp[5][7],
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
     +------------------------------------------------------------------------------
     * 以下开始为app篮球接口
     +------------------------------------------------------------------------------
    */

    /**
     * 获取足球change数据
     * @return json
     */
    public function getNbachange()
    {
        $item = $this->data['nbachange'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;

        $content = '';

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
        }
        $xmlData = [];
        $content = iconv('GBK','utf-8//IGNORE',$content);
        if(preg_match_all('/<h>(.*?)<\/h>/i',$content,$data))
        {
            foreach($data[1] as $k=>$v)
            {
                if(strpos($v,'CDATA')!==false)
                {
                    preg_match_all('/\[(.*?)\]\]>/i',$v,$vData);
                    $str = str_ireplace('CDATA[','',$vData[1][0]);
                }
                else
                {
                    $str = $v;
                }
                $arr = explode('^',$str);
                $xmlData[] =$arr;
            }
        }
        return $xmlData;
    }

    /**
     * 获取蓝球change数据
     * @return json
     */
    public function getBkchange()
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
                        $arr = explode('^',$v['change_str']);
                        $rData[] = $arr;
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
     * 获取篮球赔率变化数据
     * @return json
     */
    public function getBkodds($companyID = 2 )
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
     * [getDataList 获取接口数据]
     * @return void
     */
    public function getDataList()
    {
        $this->data = include 'interfaceArr.php';
    }
}