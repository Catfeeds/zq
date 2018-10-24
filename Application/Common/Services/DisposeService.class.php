<?PHP
/**
 +------------------------------------------------------------------------------
 * DisposeService   源数据处理服务类
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <Knight@163.com>
 +------------------------------------------------------------------------------
*/
namespace Common\Services;

class DisposeService
{
    public $data = [];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->getDataList();
        import('phpQuery');
    }

    /**
     * 各公司当日即时赔率解析
     * @param  string $content 待处理源文本
     * @return array          处理后数据数组
     */
    public function goals($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $doc = \phpQuery::newDocumentHTML($content);
        $aData = [];
        foreach(pq('match')->find('m') as $v)
        {
            $sTemp = pq($v)->text();
            $aData[] = explode(',',$sTemp);
        }
        return $aData;
    }

    /**
     * 赔率数据处理
     * @param  string   $content  源数据
     * @return array              处理后数据
     */
    public function oddsDataDiv($content)
    {
        if(preg_match_all('/[a-z]{3,5}\[(.*?)\]=\"(.*?)\"/is' ,$content, $dataTxt))
        {
            $aData =[];
            foreach($dataTxt[0] as $k=>$v)
            {
                //$aTemp =[];
                if(!isset($aData[$dataTxt[1][$k]])) $aData[$dataTxt[1][$k]]= array();
                $temp = [];
                $a = [];
                if(strpos($v,'psw[') !==false)
                {
                    $dataTxt[2][$k] = str_replace('-0,', '0,', $dataTxt[2][$k]);
                    $temp = explode('^',$dataTxt[2][$k]);
                    $a0 = explode(',',$temp[0]);
                    $a1 = explode(',',$temp[1]);
                    $a2 = explode(',',$temp[2]);
                    $a3 = explode(',',$temp[3]);
                    $a4 = explode(',',$temp[4]);
                    $a5 = explode(',',$temp[5]);
                    $a6 = explode(',',$temp[6]);
                    $a7 = explode(',',$temp[7]);
                    $a8 = explode(',',$temp[8]);
                    $a = array_merge($a0,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8);
                    $aData[$dataTxt[1][$k]]['psw'] = $a;

                    //$aData[$dataTxt[1][$k]]['psw'] =explode(',',$dataTxt[2][$k]);
                    continue;
                }

                if(strpos($v,'fsw[') !==false)
                {
                    $dataTxt[2][$k] = str_replace('-0,', '0,', $dataTxt[2][$k]);
                    $temp = explode('^',$dataTxt[2][$k]);
                    $a0 = explode(',',$temp[0]);
                    $a1 = explode(',',$temp[1]);
                    $a2 = explode(',',$temp[2]);
                    $a3 = explode(',',$temp[3]);
                    $a4 = explode(',',$temp[4]);
                    $a5 = explode(',',$temp[5]);
                    $a6 = explode(',',$temp[6]);
                    $a7 = explode(',',$temp[7]);
                    $a8 = explode(',',$temp[8]);
                    $a = array_merge($a0,$a1,$a2,$a3,$a4,$a5,$a6,$a7,$a8);
                    $aData[$dataTxt[1][$k]]['fsw'] = $a;
                    //$aData[$dataTxt[1][$k]]['fsw'] =explode(',',$dataTxt[2][$k]);
                    continue;
                }
                if(strpos($v,'lasw[') !==false)
                {
                    $temp = explode('^',$dataTxt[2][$k]);
                    $aData[$dataTxt[1][$k]]['lasw'] = $temp;
                    //$aData[$dataTxt[1][$k]]['lasw'] =explode(',',$dataTxt[2][$k]);
                    continue;
                }

                if(strpos($v,'losw[') !==false)
                {
                    $temp = explode('^',$dataTxt[2][$k]);
                    $aData[$dataTxt[1][$k]]['losw'] = $temp;
                   // $aData[$dataTxt[1][$k]]['losw'] =explode(',',$dataTxt[2][$k]);
                    continue;
                }
                if(strpos($v,'lesw[') !==false)
                {
                    $temp = explode('^',$dataTxt[2][$k]);
                    $aData[$dataTxt[1][$k]]['lesw'] = $temp;
                    //$aData[$dataTxt[1][$k]]['lesw'] =explode(',',$dataTxt[2][$k]);
                    continue;
                }

                if(strpos($v,'compa[') !==false)
                {
                    $temp = explode('^',$dataTxt[2][$k]);
                    $aData[$dataTxt[1][$k]]['compa'] = $temp;
                    //$aData[$dataTxt[1][$k]]['compa'] =explode(',',$dataTxt[2][$k]);
                    continue;
                }
            }
           return $aData;
        }
        else
        {
            return null;
        }
    }


    /**
     * 赛事事件数据解析
     * @param  string $content 源数据
     * @return array           处理后数据数组
     */
    public function detail($content)
    {
        if(preg_match_all('/[a-z]{1,3}\[(.*?)\]=\"(.*?)\"/is' ,$content, $dataTxt))
        {
            $aData =[];
            foreach($dataTxt[0] as $k=>$v)
            {
                $s = str_replace('"', '', $dataTxt[2][$k]);
                if(strpos($v,'rq[') !==false)
                {
                    $temp =explode('^',$dataTxt[2][$k]);
                    if(!isset($aData[$temp[0]])) $aData[$temp[0]]= array();
                    $aData[$temp[0]]['rq'][] =$temp;
                    continue;
                }

                if(strpos($v,'tc[') !==false)
                {
                    $temp =explode('^',$dataTxt[2][$k]);
                    if(!isset($aData[$temp[0]])) $aData[$temp[0]]= array();
                    $aData[$temp[0]]['tc'] =$temp[1];
                    continue;
                }
            }
            return $aData;
        }
    }

    /**
     * app赛事分析数据解析
     * @param  string $content 待处理源数据文本
     * @return array           处理后数据
     */
    public function analysisApp($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $content = str_replace(array("\t","\n","\r"),"",$content);
        $content = str_replace("TABLE","table",$content);
        $content = preg_replace('/>\s+</is','><',$content);
        $content = preg_replace('/>\s+<\//is','><\/',$content);

        $aData = [];
        $home_name ='';
        $away_name ='';

        if(preg_match_all('/var hometeam="(.*?)";/i',$content,$ndata)) $home_name =$ndata[1][0];
        if(preg_match_all('/var guestteam="(.*?)";/i',$content,$ndata)) $away_name =$ndata[1][0];

        #对战历史
        $MatchFight = [];
        $MatchFight['name'] ='match_fight';
        $MatchFight['content'] =array();
        if(preg_match_all('/var v_data=\[(.*?)var h_data/is',$content,$VSdata))
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
                    $temp['game_date'] = $v2[0];
                    $temp['union_id'] = $v2[1];
                    $temp['union_name'] = $v2[2];
                    $temp['union_color'] = $v2[3];
                    $temp['home_team_id'] = $v2[4];
                    $temp['home_team_name'] = $v2[5];
                    $temp['away_team_id'] = $v2[6];
                    $temp['away_team_name'] = $v2[7];
                    $temp['home_score'] = $v2[8];
                    $temp['away_score'] = $v2[9];
                    $s = explode('-',$v2[10]);
                    $temp['home_half_score'] = $s[0];
                    $temp['away_half_score'] = $s[1];
                    $temp['fsw_exp'] = $v2[15];
                    $temp['winning_losing'] = $v2[17];
                    $temp['panlu'] = $v2[18];
                    $temp['ball'] = $v2[19];
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
        if(preg_match_all('/var h_data=\[(.*?)var a_data/is',$content,$VSdata))
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
                    $temp['team_name'] = $home_name;
                    $temp['game_date'] = $v2[0];
                    $temp['union_id'] = $v2[1];
                    $temp['union_name'] = $v2[2];
                    $temp['union_color'] = $v2[3];
                    $temp['home_team_id'] = $v2[4];
                    $temp['home_team_name'] = $v2[5];
                    $temp['away_team_id'] = $v2[6];
                    $temp['away_team_name'] = $v2[7];
                    $temp['home_score'] = $v2[8];
                    $temp['away_score'] = $v2[9];
                    $s = explode('-',$v2[10]);
                    $temp['home_half_score'] = $s[0];
                    $temp['away_half_score'] = $s[1];
                    $temp['fsw_exp'] = $v2[15];
                    $temp['win'] = $v2[17];
                    $temp['panlu'] = $v2[18];
                    $temp['ball'] = $v2[19];
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
        if(preg_match_all('/var a_data=\[(.*?)var h2_data/is',$content,$VSdata))
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
                    $temp['team_name'] = $away_name;
                    $temp['game_date'] = $v2[0];
                    $temp['union_id'] = $v2[1];
                    $temp['union_name'] = $v2[2];
                    $temp['union_color'] = $v2[3];
                    $temp['home_team_id'] = $v2[4];
                    $temp['home_team_name'] = $v2[5];
                    $temp['away_team_id'] = $v2[6];
                    $temp['away_team_name'] = $v2[7];
                    $temp['home_score'] = $v2[8];
                    $temp['away_score'] = $v2[9];
                    $s = explode('-',$v2[10]);
                    $temp['home_half_score'] = $s[0];
                    $temp['away_half_score'] = $s[1];
                    $temp['fsw_exp'] = $v2[15];
                    $temp['win'] = $v2[17];
                    $temp['panlu'] = $v2[18];
                    $temp['ball'] = $v2[19];
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight2['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight2;
        unset($RecentFight2);

        $RecentFight3 = [];
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
                    $temp['team_name'] = $home_name;
                    $temp['game_date'] = $v2[0];
                    $temp['union_id'] = $v2[1];
                    $temp['union_name'] = $v2[2];
                    $temp['union_color'] = $v2[3];
                    $temp['home_team_id'] = $v2[4];
                    $temp['home_team_name'] = $v2[5];
                    $temp['away_team_id'] = $v2[6];
                    $temp['away_team_name'] = $v2[7];
                    $temp['home_score'] = $v2[8];
                    $temp['away_score'] = $v2[9];
                    $s = explode('-',$v2[10]);
                    $temp['home_half_score'] = $s[0];
                    $temp['away_half_score'] = $s[1];
                    $temp['fsw_exp'] = $v2[15];
                    $temp['win'] = $v2[17];
                    $temp['panlu'] = $v2[18];
                    $temp['ball'] = $v2[19];
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
                    $temp['team_name'] = $away_name;
                    $temp['game_date'] = $v2[0];
                    $temp['union_id'] = $v2[1];
                    $temp['union_name'] = $v2[2];
                    $temp['union_color'] = $v2[3];
                    $temp['home_team_id'] = $v2[4];
                    $temp['home_team_name'] = $v2[5];
                    $temp['away_team_id'] = $v2[6];
                    $temp['away_team_name'] = $v2[7];
                    $temp['home_score'] = $v2[8];
                    $temp['away_score'] = $v2[9];
                    $s = explode('-',$v2[10]);
                    $temp['home_half_score'] = $s[0];
                    $temp['away_half_score'] = $s[1];
                    $temp['fsw_exp'] = $v2[15];
                    $temp['win'] = $v2[17];
                    $temp['panlu'] = $v2[18];
                    $temp['ball'] = $v2[19];
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight4['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight4;
        if(!empty($RecentFight['content'])) $aData[] = $RecentFight;
        unset($RecentFight4);

        #聯賽積分
        $MatchIntegral = [];
        $MatchIntegral['name'] ='match_integral';
        $MatchIntegral['content'] =array();
        if(preg_match_all('/<h3>聯賽積分<\/h3>(.*?)<\/table><\/div><div style="display:none">/is',$content,$MIdata))
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
            $name1 = '';
            $name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );
                if(empty($str)) continue;
                $temp = [];
                $temp['team_name'] = $name1;
                $temp['fsw'] = $v2[0];
                $temp['all'] = $v2[1];
                $temp['winning'] = $v2[2];
                $temp['draw'] = $v2[3];
                $temp['losing'] = $v2[4];
                $temp['get'] = $v2[5];
                $temp['lose'] = $v2[6];
                $temp['integral'] = $v2[7];
                $temp['rank'] = $v2[8];
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
                    if(empty($str)) continue;
                    $temp = [];
                    $temp['team_name'] = $name2;
                    $temp['fsw'] = $v2[0];
                    $temp['all'] = $v2[1];
                    $temp['winning'] = $v2[2];
                    $temp['draw'] = $v2[3];
                    $temp['losing'] = $v2[4];
                    $temp['get'] = $v2[5];
                    $temp['lose'] = $v2[6];
                    $temp['integral'] = $v2[7];
                    $temp['rank'] = $v2[8];
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
        if(preg_match_all('/<h3>聯賽盤路走勢<\/h3>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940" align="center" border="0">/is',$content,$Mdata))
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
            $name1 ='';
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
                $temp['team_name'] = $name1;
                $temp['fsw'] = $v2[0];
                $temp['all'] = $v2[1];
                $temp['winning'] = $v2[2];
                $temp['draw'] = $v2[3];
                $temp['losing'] = $v2[4];
                $temp['win_rate'] = $v2[5];
                $temp['big_ball'] = $v2[7];
                $temp['big_ball_rate'] = $v2[8];
                $temp['small_ball'] = $v2[9];
                $temp['small_ball_rate'] = $v2[10];
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
            $name2 ='';
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
                    $temp['team_name'] = $name2;
                    $temp['fsw'] = $v2[0];
                    $temp['all'] = $v2[1];
                    $temp['winning'] = $v2[2];
                    $temp['draw'] = $v2[3];
                    $temp['losing'] = $v2[4];
                    $temp['win_rate'] = $v2[5];
                    $temp['big_ball'] = $v2[7];
                    $temp['big_ball_rate'] = $v2[8];
                    $temp['small_ball'] = $v2[9];
                    $temp['small_ball_rate'] = $v2[10];
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
        if(preg_match_all('/<h3>近三場賽程<\/h3><\/td>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940" align="center" border="0">/is',$content,$Threedata))
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
            $name1 ='';
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
                $temp['team_name'] = $name1;
                $temp['union_name'] = $v2[0];
                $temp['game_date'] = $v2[1];
                $temp['home_away'] = $v2[2];
                $temp['rival'] = $v2[3];
                $temp['interval'] = $v2[4];
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
            $name2 ='';
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
                    $temp['team_name'] = $name2;
                    $temp['union_name'] = $v2[0];
                    $temp['game_date'] = $v2[1];
                    $temp['home_away'] = $v2[2];
                    $temp['rival'] = $v2[3];
                    $temp['interval'] = $v2[4];
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
        if(preg_match_all('/<h3>心水推介<\/h3><\/td>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940"/i',$content,$Recommenddata))
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
            $viewpoint1 = $aTemp[2][0];
            $viewpoint2 = $aTemp[3][0];
            array_pop($aTemp);
            array_pop($aTemp);
            $sTemp = [];

            if(!empty($aTemp))
            {
                foreach($aTemp as $k2 => $v2)
                {
                    $temp = [];
                    $temp['team_name'] = $name;
                    $temp['recent'] = $v2[1];
                    $temp['panlu'] = $v2[2];
                    $sTemp['trend'][] = $temp;
                }
                $sTemp['viewpoint']['win'] = $viewpoint1;
                $sTemp['viewpoint']['lose'] = $viewpoint2;
                $MatchRecommend['content'] =$sTemp;
            }
        }
        if(!empty($sTemp)) $aData[] = $MatchRecommend;
        unset($MatchThree);
        return $aData;
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

        #add
        $aOddsData = [];
        if(preg_match('/var Vs_hOdds=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1] = strip_tags($VSdata[1]);
            $VSdata[1] = str_replace("'","",$VSdata[1]);
            $VSdata[1] = rtrim($VSdata[1],']');
            $VSdata[1] = ltrim($VSdata[1],'[');
            $vsTemp = explode('],[',$VSdata[1]);
            if(!empty($vsTemp))
            {
                foreach($vsTemp as $k=>$v)
                {
                    $ttemp = explode(',',$v);
                    if($ttemp[1] == 3)
                    {
                        $aOddsData[$ttemp[0]] = [
                            'game_id' => $ttemp[0],
                            'fsw_exp_home' => $ttemp[2],
                            'fsw_exp'      => $ttemp[3],
                            'fsw_exp_away' => $ttemp[4],
                        ];
                    }
                }
            }
        }
        #add end

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
                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $v2[0] !== null?$v2[0]:'';     //比赛状态
                    $temp[0] = str_replace("-","/",$temp[0]);
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
                    /*if(!empty($v2[15]))
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
                    }*/

                    $temp[12] = (isset($aOddsData[$v2[20]]) && isset($aOddsData[$v2[20]]['fsw_exp']))?$aOddsData[$v2[20]]['fsw_exp']:'';
                    $temp[13] = $v2[17] !== null?$v2[17]:'';    //胜负
                    $temp[14] = $v2[18] !== null?$v2[18]:'';    //盘路
                    $temp[15] = $v2[19] !== null?$v2[19]:'';     //大小
                    $temp[16] = $v2[20] !== null?$v2[20]:'';     //赛事ID
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
                    $temp[1] = $v[0] !==null?$v2[0]:'';        //比赛时间
                    $temp[1] = str_replace("-","/",$temp[1]);
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
                    //$temp[17] = isset($v2[21])?$v2[21]:'';      //主角球
                    //$temp[18] = isset($v2[22])?$v2[22]:'';      //客角球
                    $temp[17] = $v2[20] !== null?$v2[20]:'';     //赛事ID
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
                    $temp[1] = str_replace("-","/",$temp[1]);
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
                    //$temp[17] = isset($v2[21])?$v2[21]:'';      //主角球
                    //$temp[18] = isset($v2[22])?$v2[22]:'';      //客角球
                    $temp[17] = $v2[20] !== null?$v2[20]:'';     //赛事ID
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight2['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight2;
        unset($RecentFight2);

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
            $name1 = preg_replace('/\[(.*?)\]/','',$aTemp[0][0]);
            //$name1 = $aTemp[0][0];
            array_shift($aTemp);
            array_shift($aTemp);
            $sTemp = [];
            foreach($aTemp as $k2 => $v2)
            {
                $str = iconv('gb2312','utf-8//IGNORE', $v2[2]);
                $str  =  preg_replace ( '/\s\s+/' ,  ' ' ,  $str );

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
            $name2 = preg_replace('/\[(.*?)\]/','',$aTemp[0][0]);
            //$name2 = $aTemp[0][0];
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
                /*if($k2 == 4)
                {
                    $a1 = explode(' ',$temp[2]);
                    $v2[2] = implode(' ',$a1);
                    $a2 = explode(' ',$temp[5]);
                    $v2[5] = implode(' ',$a2);
                }*/
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
                $temp[2] = str_replace("-","/",$temp[2]);
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

        #add
        $aOddsData = [];
        if(preg_match('/var Vs_hOdds=\[(.*?)\];/is',$content,$VSdata))
        {
            $VSdata[1] = strip_tags($VSdata[1]);
            $VSdata[1] = str_replace("'","",$VSdata[1]);
            $VSdata[1] = rtrim($VSdata[1],']');
            $VSdata[1] = ltrim($VSdata[1],'[');
            $vsTemp = explode('],[',$VSdata[1]);
            if(!empty($vsTemp))
            {
                foreach($vsTemp as $k=>$v)
                {
                    $ttemp = explode(',',$v);
                    if($ttemp[1] == 3)
                    {
                        $aOddsData[$ttemp[0]] = [
                            'game_id' => $ttemp[0],
                            'fsw_exp_home' => $ttemp[2],
                            'fsw_exp'      => $ttemp[3],
                            'fsw_exp_away' => $ttemp[4],
                        ];
                    }
                }
            }
        }
        #add end

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
                foreach($vs as $k2 => $v2)
                {
                    $temp = [];
                    $temp[0] = $v2[0] !== null?$v2[0]:'';     //比赛状态
                    $temp[0] = str_replace("-","/",$temp[0]);
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
                    /*if(!empty($v2[15]))
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
                    }*/

                    $temp[12] = (isset($aOddsData[$v2[20]]) && isset($aOddsData[$v2[20]]['fsw_exp']))?$aOddsData[$v2[20]]['fsw_exp']:'';
                    $temp[13] = $v2[17] !== null?$v2[17]:'';    //胜负
                    $temp[14] = $v2[18] !== null?$v2[18]:'';    //盘路
                    $temp[15] = $v2[19] !== null?$v2[19]:'';     //大小
                    $temp[16] = $v2[20] !== null?$v2[20]:'';     //赛事ID
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
                    $temp[1] = $v[0] !==null?$v2[0]:'';        //比赛时间
                    $temp[1] = str_replace("-","/",$temp[1]);
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
                    //$temp[17] = isset($v2[21])?$v2[21]:'';           //主角球
                    //$temp[18] = isset($v2[22])??$v2[22]:'';            //客角球
                    $temp[17] = $v2[20] !== null?$v2[20]:'';     //赛事ID
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
                    $temp[1] = str_replace("-","/",$temp[1]);
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
                    //$temp[17] = isset($v2[21])?$v2[21]:'';           //主角球
                    //$temp[18] = isset($v2[22])??$v2[22]:'';            //客角球
                    $temp[17] = $v2[20] !== null?$v2[20]:'';     //赛事ID
                    $vsTemp[] = $temp;
                }
            }
            $RecentFight2['content'] = $vsTemp;
        }
        if(!empty($vsTemp)) $RecentFight['content'][] = $RecentFight2;
        unset($RecentFight2);

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

            $name1 = preg_replace('/\[(.*?)\]/','',$aTemp[0][0]);
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
            $name2 = preg_replace('/\[(.*?)\]/','',$aTemp[0][0]);

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
                /*if($k2 == 4)
                {
                    $a1 = explode(' ',$temp[2]);

                    $v2[2] = implode(' ',$a1);
                    $a2 = explode(' ',$temp[5]);
                    $v2[5] = implode(' ',$a2);
                    var_dump($a1,$a2);
                }*/
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
                $temp[2] = str_replace("-","/",$temp[2]);
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
                    $temp[2] = str_replace("-","/",$temp[2]);
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
     * [analysis description]
     * @return [type] [description]
     */
    public function analysis($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        $content = str_replace(array("\t","\n","\r"),"",$content);
        $content = str_replace("TABLE","table",$content);
        $content = preg_replace('/>\s+</is','><',$content);
        $content = preg_replace('/>\s+<\//is','><\/',$content);
       // import('phpQuery');

        $aData = [];
        #对战历史
        if(preg_match_all('/var v_data=\[(.*?)var h_data/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                $aData['match_fight'] = $vs;
            }
        }

        #近期战史
        if(preg_match_all('/var h_data=\[(.*?)var a_data/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                $aData['recent_fight1'] = $vs;
            }
        }

        if(preg_match_all('/var a_data=\[(.*?)var h2_data/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                $aData['recent_fight2'] = $vs;
            }
        }

        if(preg_match_all('/var h2_data=\[(.*?)var a2_data/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                $aData['recent_fight3'] = $vs;
            }
        }

        if(preg_match_all('/var a2_data=\[(.*?)var ScoreAll/is',$content,$VSdata))
        {
            $VSdata[1][0] = strip_tags($VSdata[1][0]);
            $VSdata[1][0] = str_replace("'","",$VSdata[1][0]);
            if(preg_match_all('/\[(.*?)\]/is',$VSdata[1][0],$VSdata2))
            {
                $vs = [];
                foreach($VSdata2[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $vs[] =$temp;
                }
                $aData['recent_fight4'] = $vs;
            }
        }

        #聯賽積分
        if(preg_match_all('/<h3>聯賽積分<\/h3>(.*?)<\/table><\/div><div style="display:none">/is',$content,$MIdata))
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
            $aData['match_integral'][] = $aTemp;
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
            $aData['match_integral'][] = $aTemp;

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
            $aData['match_integral'][] = $aTemp;
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
            $aData['match_integral'][] = $aTemp;
        }

        #聯賽盤路走勢
        if(preg_match_all('/<h3>聯賽盤路走勢<\/h3>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940" align="center" border="0">/is',$content,$Mdata))
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
            $aData['match_panlu'][] = $aTemp;
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
            $aData['match_panlu'][] = $aTemp;
        }

        #近三場賽程
        if(preg_match_all('/<h3>近三場賽程<\/h3><\/td>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940" align="center" border="0">/is',$content,$Threedata))
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
            $aData['match_three'][] = $aTemp;
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
            $aData['match_three'][] = $aTemp;

            /*if(preg_match_all('/<table width="100%" border="0" cellpadding="3" cellspacing="1"(.*?)<\/table>/is',$Threedata[1][0],$Three2data))
            {
                foreach($Three2data[0] as $k=>$v)
                {
                    $aTemp = [];
                    $doc = phpQuery::newDocumentHTML($v);
                    foreach(pq('tr') as $tr)
                    {
                        $temp = [];
                        foreach(pq($tr)->find('td') as $td)
                        {
                            $temp[] = pq($td)->text();
                        }
                        $aTemp[] = $temp;
                    }

                    $aData['match_three'][] = $aTemp;
                }
            }*/
        }

        #心水推介
        if(preg_match_all('/<h3>心水推介<\/h3><\/td>(.*?)<\/table><table cellspacing="0" cellpadding="0" width="940"/is',$content,$Recommenddata))
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
            $aData['match_Recommend'][] = $aTemp;
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
            $aData['match_Recommend'][] = $aTemp;
            /*if(preg_match_all('/<table cellspacing="0" cellpadding="4" width="99%" border="0">(.*?)<\/table>/is',$Recommenddata[1][0],$Recommend2data))
            {
                $aTemp = [];
                $doc = phpQuery::newDocumentHTML($Recommend2data[0][0]);
                foreach(pq('tr') as $tr)
                {
                    $temp = [];
                    foreach(pq($tr)->find('td') as $td)
                    {
                        $temp[] = pq($td)->text();
                    }
                    $aTemp[] = $temp;
                }
                $aData['match_Recommend'] = $aTemp;
            }*/
        }
        return $aData;
    }

    /**
     * 赔率三合一界面数据处理（亚赔）
     * @return array 赔率
     */
    public function asianOdds($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        $doc = \phpQuery::newDocumentHTML($content);
        $aData = [];   //xml h源数据
        foreach(pq('table')->find('tr') as $v)
        {
            $temp = [];
            foreach(pq($v)->find('td') as $v2)
            {
                $sTemp = trim(pq($v2)->text());
                if(strpos($sTemp,'走地'))
                {
                    $sTemp = str_replace('走地','',$sTemp);
                    $sTemp = trim($sTemp);
                }
                $temp[] = $sTemp;
            }
            $aData[] = $temp;
        }
        array_shift($aData);
        array_shift($aData);
        array_pop($aData);
        array_pop($aData);

        return $aData;
    }

    /**
     * 百家欧赔界面数据处理（亚赔）
     * @return array 百家赔率
     */
    public function europeOdds($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        $aData = [];
        if(preg_match_all('/var game=Array\((.*?)\);/i',$content,$data))
        {
            $aEurope = explode('",',$data[1][0]);
            foreach($aEurope as $k =>$v)
            {
                $v = trim($v,'"');
                $aTemp = explode('|',$v);
                $aData[] = $aTemp;
            }
        }
        return $aData;
    }

    /**
     *  资料库联赛积分界面数据处理app
     * @return array 处理后数据()
     */
    public function matchResultOne($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        //$aData = [];
        $aData = $team = $total = $run = $updownColor = [];

        #球队数据
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }
        #总积分数据
        if(preg_match_all('/var totalScore = \[(.*?)\];/i',$content,$totalData))
        {
            $txt = str_replace("'",'',$totalData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$iData))
            {
                foreach($iData[1] as $k=>$v)
                {
                    $sTemp = explode(',',$v);
                    $total[] = $sTemp;
                    /*$aTemp =[] ;
                    $aTemp['rank'] = $sTemp[1];
                    $aTemp['team_name'] = $team[$sTemp[2]][1];
                    $aTemp['total'] = $sTemp[4];
                    $aTemp['win'] = $sTemp[5];
                    $aTemp['draw'] = $sTemp[6];
                    $aTemp['lose'] = $sTemp[7];
                    $aTemp['integral'] = $sTemp[16];
                    $aData[] = $aTemp;*/
                }
            }
        }
        #赛程数据
        if(preg_match_all('/jh\["R_(.*?)"\] = \[(.*?)\];/i',$content,$runData))
        {
            foreach($runData[2] as $k => $v)
            {
                $txt = str_replace("'",'',$v);
                if(preg_match_all('/\[(.*?)\]/i',$txt,$runData2))
                {
                    $rArr = [];
                    foreach($runData2[1] as $k2=>$v2)
                    {
                        $rArr[] = explode(',',$v2);
                    }
                    $run[$runData[1][$k]] = $rArr;
                }
            }
        }

        #升降级颜色
        if(preg_match_all('/var scoreColor = \[(.*?)\];/i',$content,$udData))
        {
            $colorArr = ['#ccccff'=>'#f3655f','#ffff00'=>'#e9af33'];
            $txt = str_replace("'",'',$udData[1][0]);
            $updownColor = [];
            if(!empty($txt))
            {
                $upArr = explode(',',$txt);
                foreach($upArr as $k=>$v)
                {
                    $sTemp = explode('|',$v);
                    $colorStr = strtolower($sTemp[0]);
                    if(isset($colorArr[$colorStr]))
                    {
                        $sTemp[0] = $colorArr[$colorStr];
                    }
                    $updownColor[] = $sTemp;
                }
            }

        }
        $aData = array('totalScore'=>$total,'team_name'=>$team,'run'=>$run,'updown'=>$updownColor);
        return $aData;
    }

    /**
     *  资料库联赛积分界面数据处理app
     * @return array 处理后数据(赛程数据)
     */
    public function matchResultTwoKey($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        $aData = [];
        $runArr = [];
        $team =[];
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        if(preg_match_all('/jh\["R_(.*?)"\] = \[(.*?)\];/i',$content,$runData))
        {
            foreach($runData[2] as $k => $v)
            {
                $txt = str_replace("'",'',$v);
                if(preg_match_all('/\[(.*?)\]/i',$txt,$runData2))
                {
                    $rArr = [];
                    foreach($runData2[1] as $k2=>$v2)
                    {
                        $sTemp = explode(',',$v2);
                        $aTemp =[] ;
                        $aTemp['game_id'] = $sTemp[0];
                        $aTemp['union_id'] = $sTemp[1];
                        $aTemp['game_state'] = $sTemp[2];
                        $aTemp['game_date'] = $sTemp[3];
                        $aTemp['home_team_name'] = $team[$sTemp[4]][1];
                        $aTemp['away_team_name'] = $team[$sTemp[5]][1];
                        $score = explode('-',$sTemp[6]);
                        $aTemp['home_score'] = $score[0];
                        $aTemp['away_score'] = $score[1];
                        $half_score = explode('-',$sTemp[7]);
                        $aTemp['home_half_score'] = $half_score[0];
                        $aTemp['away_half_score'] = $half_score[1];
                        $aTemp['home_team_rank'] = $sTemp[8];
                        $aTemp['away_team_rank'] = $sTemp[9];
                        $aTemp['fsw_exp'] = $sTemp[10];
                        $aTemp['psw_exp'] = $sTemp[11];
                        $aTemp['fsw_ball'] = $sTemp[12];
                        $aTemp['psw_ball'] = $sTemp[13];
                        $rArr[] = $aTemp;
                    }
                    $arr['runNo'] = $runData[1][$k];
                    $arr['content'] = $rArr;
                    $runArr[] = $runData[1][$k];
                    $aData[] = $arr;
                    //$aData[$runData[1][$k]] = $rArr;
                }
            }
        }
        return array('run'=>$runArr,'content'=>$aData);
    }

    /**
     *  资料库联赛积分界面数据处理app
     * @return array 处理后数据(赛程数据)
     */
    public function matchResultTwo($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        $aData = [];
        $runArr = [];
        $team =[];
        $showRun = '';
        #球队
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }
        #处理默认显示轮次
        if(preg_match_all('/var arrSubLeague = \[\[(.*?)\]\];/i',$content,$leagueData))
        {
            $txt = str_replace("'",'',$leagueData[1][0]);
            $tData = explode('],[',$txt);
            $leagueArr = explode(',',$tData[0]);
            $showRun = $leagueArr[6];
            /*if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    if($temp[4] == 1) $showRun = $temp[6];
                }
            }*/
        }
        else
        {
            if(preg_match_all('/var arrLeague = \[(.*?)\];/i',$content,$leagueData))
            {
                $txt = str_replace("'",'',$leagueData[1][0]);
                $arrLeague = explode(',',$txt);
                $showRun = $arrLeague[8];
            }
        }

        #赛程
        if(preg_match_all('/jh\["R_(.*?)"\] = \[(.*?)\];/i',$content,$runData))
        {
            $sflag = false;
            foreach($runData[2] as $k => $v)
            {
                $txt = str_replace("'",'',$v);
                if(preg_match_all('/\[(.*?)\]/i',$txt,$runData2))
                {
                    $rArr = [];
                    $show = 0;
                    foreach($runData2[1] as $k2=>$v2)
                    {
                        if(strpos($v2,"[") !== false) $v2 = substr($v2,strpos($v2,"[")+1);
                        $sTemp = explode(',',$v2);
                        $aTemp =[] ;
                        $aTemp[0] = !empty($sTemp[0])?$sTemp[0]:'';          //公司ID
                        $aTemp[1] = !empty($sTemp[1])?$sTemp[1]:'';          //联赛ID
                        $aTemp[2] = $sTemp[2] !==null?$sTemp[2]:'';          //比赛状态
                        $aTemp[3] = !empty($sTemp[3])?$sTemp[3]:'';          //比赛日期
                        $aTemp[4] = isset($team[$sTemp[4]][1])?$team[$sTemp[4]][1]:''; //主队名称
                        $aTemp[5] = isset($team[$sTemp[5]][1])?$team[$sTemp[5]][1]:''; //客队名称
                        if(!empty($sTemp[6]) && strpos($sTemp[6],'-') !== false)
                        {
                            $score = explode('-',$sTemp[6]);
                            $aTemp[6] = $score[0];          //主队得分
                            $aTemp[7] = $score[1];          //客队得分
                        }
                        else
                        {
                            $aTemp[6] = '';          //主队得分
                            $aTemp[7] = '';          //客队得分
                        }
                        if(!empty($sTemp[7]) && strpos($sTemp[7],'-') !== false)
                        {
                            $half_score = explode('-',$sTemp[7]);
                            $aTemp[8] = $half_score[0];     //半场主队得分
                            $aTemp[9] = $half_score[1];    //半场客队得分
                        }
                        else
                        {
                            $aTemp[8] = '';     //半场主队得分
                            $aTemp[9] = '';    //半场客队得分
                        }
                        $aTemp[10] = !empty($sTemp[8])?$sTemp[8]:'';    //主队排名
                        $aTemp[11] = !empty($sTemp[9])?$sTemp[9]:'';   //客队排名
                        $aTemp[12] = $sTemp[10] !==null?$sTemp[10]:'';  //全场亚盘盘口
                        $aTemp[13] = $sTemp[11] !==null?$sTemp[11]:'';  //半场亚盘盘口
                        $aTemp[14] = $sTemp[12] !==null?$sTemp[12]:'';  //全场大小盘口
                        $aTemp[15] = $sTemp[13] !==null?$sTemp[13]:'';  //半场大小盘口
                        $rArr[] = $aTemp;
                    }
                    $arr['runNo'] = $runData[1][$k];
                    if($runData[1][$k] == $showRun)
                        $arr['show'] = 1;
                    else
                        $arr['show'] = 0;
                    $arr['content'] = $rArr;
                    $runArr[] = $runData[1][$k];
                    $aData[] = $arr;
                }
            }
        }
        return array('run'=>$runArr,'content'=>$aData);
    }

     /**
     *  资料库杯赛积分界面数据处理app
     * @return array 处理后数据(赛程数据)
     */
    public function matchResultCupKey($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        $aData = [];
        $team =[];
        #球队
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        $ack = [];
        $cupData = [];
        #小组赛
        if(preg_match_all('/var arrCupKind = \[(.*?)\];/i',$content,$ackData))
        {

            $txt = str_replace("'",'',$ackData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $aTemp['run_id'] = $temp[0];
                    $aTemp['is_group'] = $temp[1];
                    $aTemp['run_name'] = $temp[2];
                    $ack[] = $aTemp;
                }

                $arr = $ack;
                $last = array_pop($arr);
                //$last = array_shift($ack);
                $intData = [];
                $matchData = [];
                if($last['is_group'] == 1)
                {//分组赛（循环积分小组出线赛）
                    if(preg_match_all('/jh\["S'.$last['run_id'].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                    {
                        foreach($sData[2] as $k2=>$v2)
                        {
                            $txt = str_replace("'",'',$v2);
                            if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                            {
                                $tempArr = [];
                                foreach($runData[1] as $k3=>$v3)
                                {
                                    $temp = explode(',',$v3);
                                    $aTemp['rank'] = $temp[0];
                                    $aTemp['team_id'] = $temp[1];
                                    $aTemp['team_name'] = $team[$temp[1]][1];
                                    $aTemp['total'] = $temp[2];
                                    $aTemp['winning'] = $temp[3];
                                    $aTemp['drawing'] = $team[4];
                                    $aTemp['losing'] = $temp[5];
                                    $aTemp['get_int'] = $temp[6];
                                    $aTemp['lose_int'] = $temp[7];
                                    $aTemp['net_int'] = $temp[8];
                                    $aTemp['integral'] = $temp[9];
                                    $tempArr[] = $aTemp;
                                }
                                $intData[$sData[1][$k2]] = $tempArr;
                            }
                        }
                    }

                    if(preg_match_all('/jh\["G'.$last['run_id'].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                    {
                        foreach($sData[2] as $k2=>$v2)
                        {
                            $txt = str_replace("'",'',$v2);
                            if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                            {
                                $tempArr = [];
                                foreach($runData[1] as $k3=>$v3)
                                {
                                    $temp = explode(',',$v3);
                                    $aTemp['game_id'] = $temp[0];
                                    $aTemp['union_id'] = $temp[1];
                                    $aTemp['game_state'] = $temp[2];
                                    $aTemp['game_time'] = $temp[3];
                                    $aTemp['home_team_id'] = $temp[4];
                                    $aTemp['home_team_name'] = $team[$temp[4]][1];
                                    $aTemp['home_team_rank'] = $temp[8];
                                    $aTemp['away_team_id'] = $temp[5];
                                    $aTemp['away_team_name'] = $team[$temp[5]][1];
                                    $aTemp['away_team_rank'] = $temp[9];
                                    $aTemp['score'] = $temp[6];
                                    $aTemp['half_score'] = $temp[7];
                                    $aTemp['fsw_exp'] = $temp[10];
                                    $aTemp['psw_exp'] = $temp[11];
                                    $aTemp['fsw_ball'] = $temp[12];
                                    $aTemp['psw_ball'] = $temp[13];
                                    $tempArr[] = $aTemp;
                                }
                                $matchData[$sData[1][$k2]] = $tempArr;
                            }
                        }
                    }
                    $cupData = array('integral'=>$intData,'match'=>$matchData);
                }
                else
                {//淘汰赛
                    if(preg_match_all('/jh\["G'.$last['run_id'].'"\] = \[(.*?)\];/i',$content,$sData))
                    {
                        //var_dump($sData);//exit;
                        $txt = str_replace("'",'',$sData[1][0]);
                        if(preg_match_all('/\[(.*?)\]\]/i',$txt,$runData1))
                        {
                            foreach($runData1[1] as $k2=>$v2)
                            {
                                $arr = preg_replace('/(.*?)\[/i', '', $v2).'<br>';
                                $temp = explode(',',$arr);
                                $aTemp['game_id'] = $temp[0];
                                $aTemp['union_id'] = $temp[1];
                                $aTemp['game_state'] = $temp[2];
                                $aTemp['game_time'] = $temp[3];
                                $aTemp['home_team_id'] = $temp[4];
                                $aTemp['home_team_name'] = $team[$temp[4]][1];
                                $aTemp['home_team_rank'] = $temp[8];
                                $aTemp['away_team_id'] = $temp[5];
                                $aTemp['away_team_name'] = $team[$temp[5]][1];
                                $aTemp['away_team_rank'] = $temp[9];
                                $aTemp['score'] = $temp[6];
                                $aTemp['half_score'] = $temp[7];
                                $aTemp['fsw_exp'] = $temp[10];
                                $aTemp['psw_exp'] = $temp[11];
                                $aTemp['fsw_ball'] = $temp[12];
                                $aTemp['psw_ball'] = $temp[13];
                                $matchData[] = $aTemp;
                            }

                        }
                        else
                        {
                            if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                            {
                                foreach($runData[1] as $k2=>$v2)
                                {
                                    $temp = explode(',',$v2);
                                    $aTemp['game_id'] = $temp[0];
                                    $aTemp['union_id'] = $temp[1];
                                    $aTemp['game_state'] = $temp[2];
                                    $aTemp['game_time'] = $temp[3];
                                    $aTemp['home_team_id'] = $temp[4];
                                    $aTemp['home_team_name'] = $team[$temp[4]][1];
                                    $aTemp['home_team_rank'] = $temp[8];
                                    $aTemp['away_team_id'] = $temp[5];
                                    $aTemp['away_team_name'] = $team[$temp[5]][1];
                                    $aTemp['away_team_rank'] = $temp[9];
                                    $aTemp['score'] = $temp[6];
                                    $aTemp['half_score'] = $temp[7];
                                    $aTemp['fsw_exp'] = $temp[10];
                                    $aTemp['psw_exp'] = $temp[11];
                                    $aTemp['fsw_ball'] = $temp[12];
                                    $aTemp['psw_ball'] = $temp[13];
                                    $matchData[] = $aTemp;
                                }
                            }
                        }
                    }
                    $cupData = array('match'=>$matchData);
                }
            }
            $aData = array('group'=>$ack,'intContent'=>$cupData);
        }
        return $aData;
    }

    /**
     *  资料库杯赛积分界面数据处理app
     * @return array 处理后数据(赛程数据)
     */
    public function matchResultCup($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        $aData = [];
        $team =[];
        #球队
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        $ack = [];
        $cupData = [];
        #小组赛
        if(preg_match_all('/var arrCupKind = \[(.*?)\];/i',$content,$ackData))
        {

            $txt = str_replace("'",'',$ackData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $aTemp[0] = $temp[0];  //轮次
                    $aTemp[1] = $temp[1];  //是否小组赛，1是，0否
                    $aTemp[2] = $temp[2];  //轮次名称
                    $ack[] = $aTemp;
                }

                $arr = $ack;
                $last = array_pop($arr);

                //$last = array_shift($ack);
                $intData = [];
                $matchData = [];
                if($last['is_group'] == 1)
                {//分组赛（循环积分小组出线赛）
                    if(preg_match_all('/jh\["S'.$last[0].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                    {
                        foreach($sData[2] as $k2=>$v2)
                        {
                            $txt = str_replace("'",'',$v2);
                            if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                            {
                                $tempArr = [];
                                foreach($runData[1] as $k3=>$v3)
                                {
                                    $temp = explode(',',$v3);
                                    $aTemp[0] = $temp[0];  //排名
                                    $aTemp[1] = $temp[1];  //球队ID
                                    $aTemp[2] = $team[$temp[1]][1];  //球队名称
                                    $aTemp[3] = $temp[2];  //共
                                    $aTemp[4] = $temp[3];  //胜
                                    $aTemp[5] = $team[4];  // 走
                                    $aTemp[6] = $temp[5];  //负
                                    $aTemp[7] = $temp[6];  //得
                                    $aTemp[8] = $temp[7];  //失
                                    $aTemp[9] = $temp[8];  //净积分
                                    $aTemp[10] = $temp[9];  //积分
                                    $tempArr[] = $aTemp;
                                }
                                $intData[$sData[1][$k2]] = $tempArr;
                            }
                        }
                    }

                    if(preg_match_all('/jh\["G'.$last[0].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                    {
                        foreach($sData[2] as $k2=>$v2)
                        {
                            $txt = str_replace("'",'',$v2);
                            if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                            {
                                $tempArr = [];
                                foreach($runData[1] as $k3=>$v3)
                                {
                                    $temp = explode(',',$v3);
                                    $aTemp[0] = $temp[0];   //赛事ID
                                    $aTemp[1] = $temp[1];  //联赛ID
                                    $aTemp[2] = $temp[2];  //比赛状态
                                    $aTemp[3] = $temp[3];   //比赛时间
                                    $aTemp[4] = $temp[4];  //主队ID
                                    $aTemp[5] = $team[$temp[4]][1];  //主队名称
                                    $aTemp[6] = $temp[8];   //主队排名
                                    $aTemp[7] = $temp[5];  //客队ID
                                    $aTemp[8] = $team[$temp[5]][1];  //客队球队名称
                                    $aTemp[9] = $temp[9];   //客队球队排名
                                    $aTemp[10] = $temp[6];  //全场比分
                                    $aTemp[11] = $temp[7];  //半场比分
                                    $aTemp[12] = $temp[10];  //全场盘口
                                    $aTemp[13] = $temp[11];  //半场盘口
                                    $aTemp[14] = $temp[12]; //全场大小盘口
                                    $aTemp[15] = $temp[13];  //半场大小盘口
                                    $tempArr[] = $aTemp;
                                }
                                $matchData[$sData[1][$k2]] = $tempArr;
                            }
                        }
                    }
                    $cupData = array('integral'=>$intData,'match'=>$matchData);
                }
                else
                {//淘汰赛
                    if(preg_match_all('/jh\["G'.$last[0].'"\] = \[(.*?)\];/i',$content,$sData))
                    {
                        $txt = str_replace("'",'',$sData[1][0]);
                        if(preg_match_all('/\[(.*?)\]\]/i',$txt,$runData1))
                        {
                            foreach($runData1[1] as $k2=>$v2)
                            {
                                $arr = preg_replace('/(.*?)\[/i', '', $v2).'<br>';
                                $temp = explode(',',$arr);
                                $aTemp[0] = $temp[0];   //赛事ID
                                $aTemp[1] = $temp[1];  //联赛ID
                                $aTemp[2] = $temp[2];  //比赛状态
                                $aTemp[3] = $temp[3];   //比赛时间
                                $aTemp[4] = $temp[4];  //主队ID
                                $aTemp[5] = $team[$temp[4]][1];  //主队名称
                                $aTemp[6] = $temp[8];   //主队排名
                                $aTemp[7] = $temp[5];  //客队ID
                                $aTemp[8] = $team[$temp[5]][1];  //客队球队名称
                                $aTemp[9] = $temp[9];   //客队球队排名
                                $aTemp[10] = $temp[6];  //全场比分
                                $aTemp[11] = $temp[7];  //半场比分
                                $aTemp[12] = $temp[10];  //全场盘口
                                $aTemp[13] = $temp[11];  //半场盘口
                                $aTemp[14] = $temp[12]; //全场大小盘口
                                $aTemp[15] = $temp[13];  //半场大小盘口
                                $matchData[] = $aTemp;
                            }
                        }
                        else
                        {
                            if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                            {
                                foreach($runData[1] as $k2=>$v2)
                                {
                                    $temp = explode(',',$v2);
                                    $aTemp[0] = $temp[0];   //赛事ID
                                    $aTemp[1] = $temp[1];  //联赛ID
                                    $aTemp[2] = $temp[2];  //比赛状态
                                    $aTemp[3] = $temp[3];   //比赛时间
                                    $aTemp[4] = $temp[4];  //主队ID
                                    $aTemp[5] = $team[$temp[4]][1];  //主队名称
                                    $aTemp[6] = $temp[8];   //主队排名
                                    $aTemp[7] = $temp[5];  //客队ID
                                    $aTemp[8] = $team[$temp[5]][1];  //客队球队名称
                                    $aTemp[9] = $temp[9];   //客队球队排名
                                    $aTemp[10] = $temp[6];  //全场比分
                                    $aTemp[11] = $temp[7];  //半场比分
                                    $aTemp[12] = $temp[10];  //全场盘口
                                    $aTemp[13] = $temp[11];  //半场盘口
                                    $aTemp[14] = $temp[12]; //全场大小盘口
                                    $aTemp[15] = $temp[13];  //半场大小盘口
                                    $matchData[] = $aTemp;
                                }
                            }
                        }
                    }
                    $cupData = array('match'=>$matchData);
                }
            }
            $aData = array('group'=>$ack,'intContent'=>$cupData);
        }
        return $aData;
    }

    /**
     * 资料库杯赛积分界面数据处理
     * @param  string $content 待处理源数据文本
     * @return array          处理后数据
     */
    public function matchResultCupRun($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        $team =[];
        #球队
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        $ack = [];
        $cupData = [];
        #小组赛
        if(preg_match_all('/var arrCupKind = \[(.*?)\];/i',$content,$ackData))
        {
            $txt = str_replace("'",'',$ackData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $aTemp = [];
                    $aTemp[0] = $temp[0];   //轮次ID
                    $aTemp[1] = $temp[1];  //1是小组赛，0是淘汰赛
                    $aTemp[2] = $temp[2];  //轮次名称
                    //$ack[$temp[0]] = $aTemp;
	                if ($temp[6] == 1) {
	                	$tempStatus = $temp[0];
	                }
                    $ack[] = $aTemp;
                }
            }
        }
        if(empty($ack)) return null;
        foreach($ack as $k=>$v)
        {
            $intData = [];
            $matchData = [];
            $aTemp = [];
            if($v[1] == 1)
            {//分组赛（循环积分小组出线赛）
                #积分
                if(preg_match_all('/jh\["S'.$v[0].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                {
                    foreach($sData[2] as $k2=>$v2)
                    {
                        $txt = str_replace("'",'',$v2);
                        if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                        {
                            $tempArr = [];
                            foreach($runData[1] as $k3=>$v3)
                            {
                                $temp = explode(',',$v3);
                                if(count($temp) <10) continue;
                                $aTemp[0] = $temp[0] !== null?$temp[0]:'';   //排名
                                $aTemp[1] = $temp[1] !== null?$temp[1]:'';  //球队ID
                                $aTemp[2] = !empty($team[$temp[1]][1])?$team[$temp[1]][1]:'';  //球队名称
                                $aTemp[3] = $temp[2] !== null?$temp[2]:'';   //总场次
                                $aTemp[4] = $temp[3] !== null?$temp[3]:'';  //胜
                                $aTemp[5] = $temp[4] !== null?$temp[4]:'';  //走
                                $aTemp[6] = $temp[5] !== null?$temp[5]:'';  //负
                                $aTemp[7] = $temp[6] !== null?$temp[6]:'';  //得
                                $aTemp[8] = $temp[7] !== null?$temp[7]:'';  //失
                                $aTemp[9] = $temp[8] !== null?$temp[8]:'';  //净
                                $aTemp[10] = $temp[9] !== null?$temp[9]:''; //积分
                                $tempArr[] = $aTemp;
                            }
                            $intData[$sData[1][$k2]] = $tempArr;
                        }
                    }
                }

                #赛程
                if(preg_match_all('/jh\["G'.$v[0].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                {
                    foreach($sData[2] as $k2=>$v2)
                    {
                        $txt = str_replace("'",'',$v2);
                        if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                        {
                            $tempArr = [];
                            foreach($runData[1] as $k3=>$v3)
                            {
                                $temp = explode(',',$v3);
                                if(count($temp) <10) continue;
                                $aTemp[0] = $temp[0] !== null?$temp[0]:'';   //赛事ID
                                $aTemp[1] = $temp[1] !== null?$temp[1]:'';  //联赛ID
                                $aTemp[2] = $temp[2] !== null?$temp[2]:'';  //比赛状态
                                $aTemp[3] = $temp[3] !== null?$temp[3]:'';   //比赛时间
                                $aTemp[4] = $temp[4] !== null?$temp[4]:'';  //主队ID
                                $aTemp[5] = !empty($team[$temp[4]][1])?$team[$temp[4]][1]:'';  //主队名称
                                $aTemp[6] = $temp[8] !== null?$temp[8]:'';   //主队排名
                                $aTemp[7] = $temp[5] !== null?$temp[5]:'';  //客队ID
                                $aTemp[8] = !empty($team[$temp[5]][1])?$team[$temp[5]][1]:'';  //客队球队名称
                                $aTemp[9] = $temp[9] !== null?$temp[9]:'';   //客队球队排名
                                if(!empty($temp[6]))
                                {
                                    $score = explode('-',$temp[6]);
                                    $aTemp[10] = $score[0];  //全场比分
                                    $aTemp[11] = $score[1];  //全场比分
                                }
                                else
                                {
                                    $aTemp[10] = '';  //全场比分
                                    $aTemp[11] = '';  //全场比分
                                }
                                if(!empty($temp[7]))
                                {
                                    $half_score = explode('-',$temp[7]);
                                    $aTemp[12] = $half_score[0];  //全场比分
                                    $aTemp[13] = $half_score[1];  //全场比分
                                }
                                else
                                {
                                    $aTemp[12] = '';  //全场比分
                                    $aTemp[13] = '';  //全场比分
                                }

                                $aTemp[14] = ($temp[10] !== null && $temp[10] !== '')?changeExp($temp[10]):'';  //全场盘口
                                $aTemp[15] = ($temp[11] !== null && $temp[11] !== '')?changeExp($temp[11]):'';  //半场盘口
                                $aTemp[16] = ($temp[12] !== null && $temp[12] !== '')?changeExp($temp[12]):''; //全场大小盘口
                                $aTemp[17] = ($temp[13] !== null && $temp[13] !== '')?changeExp($temp[13]):'';  //半场大小盘口
                                $tempArr[] = $aTemp;
                            }
                            $matchData[$sData[1][$k2]] = $tempArr;
                        }
                    }
                }
                $cupData[$v[0]] = array('integral'=>$intData,'match'=>$matchData);
            }
            else
            {//淘汰赛
                if(preg_match_all('/jh\["G'.$v[0].'"\] = \[\[(.*?)\]\];/i',$content,$sData))
                {
                    $runData = explode('],[',$sData[1][0]);
                    foreach($runData as $k2=>$v2)
                    {
                        $v2 = str_replace("'",'',$v2);
                        if(strpos($v2,'[') !==false && strpos($v2,'[')<18)
                        {
                            //[58,25,0,0,[1200975,84,0,'2016-01-06 04:00',58,25,'','','英超10','英超7',-0.25,-0.25,'2/2.5','1',1,1,1,1,0,0,'','','','0','ENG PR-10','ENG PR-7']
                            //[254757,349,-1,'2009-01-10 03:00',4675,4381,'1-1','1-1','','',,,'','',1,1,0,0,0,0,'90分钟[1-1],120分钟[1-1],点球[7-6],土耳其球迷队胜出','','']
                            $v2 = substr($v2,strpos($v2,'[')+1);
                        }
                        $temp = explode(',',$v2);
                        $aTemp[0] = $temp[0] !== null?$temp[0]:'';   //赛事ID
                        $aTemp[1] = $temp[1] !== null?$temp[1]:'';  //联赛ID
                        $aTemp[2] = $temp[2] !== null?$temp[2]:'';  //比赛状态
                        $aTemp[3] = $temp[3] !== null?$temp[3]:'';   //比赛时间
                        $aTemp[4] = $temp[4] !== null?$temp[4]:'';  //主队ID
                        $aTemp[5] = !empty($team[$temp[4]][1])?$team[$temp[4]][1]:'';  //主队名称
                        $aTemp[6] = $temp[8] !== null?$temp[8]:'';   //主队排名
                        $aTemp[7] = $temp[5] !== null?$temp[5]:'';  //客队ID
                        $aTemp[8] = !empty($team[$temp[5]][1])?$team[$temp[5]][1]:'';  //客队球队名称
                        $aTemp[9] = $temp[9] !== null?$temp[9]:'';   //客队球队排名
                        if(!empty($temp[6]))
                        {
                            $score = explode('-',$temp[6]);
                            $aTemp[10] = $score[0];  //全场比分
                            $aTemp[11] = $score[1];  //全场比分
                        }
                        else
                        {
                            $aTemp[10] = '';  //全场比分
                            $aTemp[11] = '';  //全场比分
                        }
                        if(!empty($temp[7]))
                        {
                            $half_score = explode('-',$temp[7]);
                            $aTemp[12] = $half_score[0];  //全场比分
                            $aTemp[13] = $half_score[1];  //全场比分
                        }
                        else
                        {
                            $aTemp[12] = '';  //全场比分
                            $aTemp[13] = '';  //全场比分
                        }
                        $aTemp[14] = ($temp[10] !== null && $temp[10] !== '')?changeExp($temp[10]):'';  //全场盘口
                        $aTemp[15] = ($temp[11] !== null && $temp[11] !== '')?changeExp($temp[11]):'';  //半场盘口
                        $aTemp[16] = ($temp[12] !== null && $temp[12] !== '')?changeExp($temp[12]):''; //全场大小盘口
                        $aTemp[17] = ($temp[13] !== null && $temp[13] !== '')?changeExp($temp[13]):'';  //半场大小盘口
                        $matchData[] = $aTemp;
                    }
                }
                $cupData[$v[0]] = array('match'=>$matchData);
            }
        }
        return array('group'=>$ack,'intContent'=>$cupData, 'tempStatus'=>$tempStatus);
    }

    /**
     * 资料库杯赛积分界面数据处理
     * @param  string $content 待处理源数据文本
     * @return array          处理后数据
     */
    public function matchResultCupRunKey($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }
        //$content = iconv('gb2312','utf-8//IGNORE',$content);
        $team =[];
        #球队
        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        $ack = [];
        $cupData = [];
        #小组赛
        if(preg_match_all('/var arrCupKind = \[(.*?)\];/i',$content,$ackData))
        {
            $txt = str_replace("'",'',$ackData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $aTemp = [];
                    $aTemp['run_id'] = $temp[0];   //轮次ID
                    $aTemp['is_group'] = $temp[1];  //1是小组赛，0是淘汰赛
                    $aTemp['run_name'] = $temp[2];  //轮次名称
                    $ack[$temp[0]] = $aTemp;
                }
            }
        }
        if(empty($ack)) return null;
        foreach($ack as $k=>$v)
        {
            $intData = [];
            $matchData = [];
            $aTemp = [];
            if($v['is_group'] == 1)
            {//分组赛（循环积分小组出线赛）
                if(preg_match_all('/jh\["S'.$v['run_id'].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                {
                    foreach($sData[2] as $k2=>$v2)
                    {
                        $txt = str_replace("'",'',$v2);
                        if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                        {
                            $tempArr = [];
                            foreach($runData[1] as $k3=>$v3)
                            {
                                $temp = explode(',',$v3);
                                $aTemp['rank'] = $temp[0];
                                $aTemp['team_id'] = $temp[1];
                                $aTemp['team_name'] = $team[$temp[1]][1];
                                $aTemp['total'] = $temp[2];
                                $aTemp['winning'] = $temp[3];
                                $aTemp['drawing'] = $team[4];
                                $aTemp['losing'] = $temp[5];
                                $aTemp['get_int'] = $temp[6];
                                $aTemp['lose_int'] = $temp[7];
                                $aTemp['net_int'] = $temp[8];
                                $aTemp['integral'] = $temp[9];
                                $tempArr[] = $aTemp;
                            }
                            $intData[$sData[1][$k2]] = $tempArr;
                        }
                    }
                }

                if(preg_match_all('/jh\["G'.$v['run_id'].'(.*?)"\] = \[(.*?)\];/i',$content,$sData))
                {
                    foreach($sData[2] as $k2=>$v2)
                    {
                        $txt = str_replace("'",'',$v2);
                        if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                        {
                            $tempArr = [];
                            foreach($runData[1] as $k3=>$v3)
                            {
                                $temp = explode(',',$v3);
                                $aTemp['game_id'] = $temp[0];
                                $aTemp['union_id'] = $temp[1];
                                $aTemp['game_state'] = $temp[2];
                                $aTemp['game_time'] = $temp[3];
                                $aTemp['home_team_id'] = $temp[4];
                                $aTemp['home_team_name'] = $team[$temp[4]][1];
                                $aTemp['home_team_rank'] = $temp[8];
                                $aTemp['away_team_id'] = $temp[5];
                                $aTemp['away_team_name'] = $team[$temp[5]][1];
                                $aTemp['away_team_rank'] = $temp[9];
                                $aTemp['score'] = $temp[6];
                                $aTemp['half_score'] = $temp[7];
                                $aTemp['fsw_exp'] = $temp[10];
                                $aTemp['psw_exp'] = $temp[11];
                                $aTemp['fsw_ball'] = $temp[12];
                                $aTemp['psw_ball'] = $temp[13];
                                $tempArr[] = $aTemp;
                            }
                            $matchData[$sData[1][$k2]] = $tempArr;
                        }
                    }
                }
                $cupData[$k] = array('integral'=>$intData,'match'=>$matchData);
            }
            else
            {//淘汰赛
                if(preg_match_all('/jh\["G'.$v['run_id'].'"\] = \[(.*?)\];/i',$content,$sData))
                {
                    $txt = str_replace("'",'',$sData[1][0]);
                    if(preg_match_all('/\[(.*?)\]/i',$txt,$runData))
                    {
                        foreach($runData[1] as $k2=>$v2)
                        {
                            $temp = explode(',',$v2);
                            $aTemp['game_id'] = $temp[0];
                            $aTemp['union_id'] = $temp[1];
                            $aTemp['game_state'] = $temp[2];
                            $aTemp['game_time'] = $temp[3];
                            $aTemp['home_team_id'] = $temp[4];
                            $aTemp['home_team_name'] = $team[$temp[4]][1];
                            $aTemp['home_team_rank'] = $temp[8];
                            $aTemp['away_team_id'] = $temp[5];
                            $aTemp['away_team_name'] = $team[$temp[5]][1];
                            $aTemp['away_team_rank'] = $temp[9];
                            $aTemp['score'] = $temp[6];
                            $aTemp['half_score'] = $temp[7];
                            $aTemp['fsw_exp'] = $temp[10];
                            $aTemp['psw_exp'] = $temp[11];
                            $aTemp['fsw_ball'] = $temp[12];
                            $aTemp['psw_ball'] = $temp[13];
                            $matchData[] = $aTemp;
                        }
                    }
                }
                $cupData[$k] = array('match'=>$matchData);
            }
        }
        return array('group'=>$ack,'intContent'=>$cupData);
    }


    /**
     * 资料库联赛让球盘路榜数据处理
     * @param  string $content 待处理文本
     * @return array          处理后数据
     */
    public function letGoal($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $aData = [];
        $team = [];
        $totalPanLu =[];

        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {

            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        if(preg_match_all('/var TotalPanLu = \[(.*?)\];/i',$content,$tpData))
        {
            if(preg_match_all('/\[(.*?)\]/i',$tpData[1][0],$tpData2))
            {
                foreach($tpData2[1] as $k=>$v)
                {
                    $sTemp = explode(',',$v);
                    $totalPanLu[] = $sTemp;
                }
            }
        }

        $aData =array('team_name'=>$team,'totalPanlu'=>$totalPanLu);
        return $aData;
    }

    /**
     * 资料库联赛大小盘路榜数据处理
     * @param  string $content 待处理文本
     * @return array          处理后数据
     */
    public function bigSmall($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $aData = [];
        $team = [];
        $totalBs =[];

        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }
        }

        if(preg_match_all('/var TotalBs = \[(.*?)\];/i',$content,$tbData))
        {
            if(preg_match_all('/\[(.*?)\]/i',$tbData[1][0],$tbData2))
            {
                foreach($tbData2[1] as $k=>$v)
                {
                    $sTemp = explode(',',$v);
                    $totalBs[] = $sTemp;
                }
            }
        }

        $aData =array('team_name'=>$team,'totalBs'=>$totalBs);
        return $aData;
    }

    /**
     * 资料库联赛射手榜数据处理
     * @param  string $content 待处理文本
     * @return array          处理后数据
     */
    public function archer($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $aData = [];
        $team = [];
        $arrTotal =[];

        if(preg_match_all('/var arrTeam = \[(.*?)\];/i',$content,$teamData))
        {
            $strTemp = '['.$teamData[1][0].']';
            $strTemp = str_replace("'","\"",$strTemp);
            $arrTeam = json_decode($strTemp);
            foreach($arrTeam as $k=>$v)
            {
                $team[$v[0]] = $v;
            }

           /* $txt = str_replace("'",'',$teamData[1][0]);
            if(preg_match_all('/\[(.*?)\]/i',$txt,$tData))
            {
                foreach($tData[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    $team[$temp[0]] = explode(',',$v);
                }
            }*/
        }

        if(preg_match_all('/var arrTotalData = \[(.*?)\];/i',$content,$atData))
        {
            $strTemp = '['.$atData[1][0].']';
            $strTemp = str_replace("'","\"",$strTemp);
            $arrTotal = json_decode($strTemp);

            /*if(preg_match_all('/\[(.*?)\]/i',$atData[1][0],$atData2))
            {
                foreach($atData2[1] as $k=>$v)
                {
                    if(preg_match('/[a-zA-z]+,[a-zA-z]+/is' , $v , $data))
                    {
                        $v = preg_replace('/([a-zA-z]+)(,)([a-zA-z]+)/is','${1}.$3' ,$v);
                    }
                    $sTemp = explode(',',$v);
                    $arrTotal[] = $sTemp;
                }
            }*/
        }
        else
        {
            return null;
        }

        $aData =array('team_name'=>$team,'arrTotal'=>$arrTotal);
        return $aData;
    }

    /**
     * 赔率数据接口
     * @param  string $content 待处理文本
     * @return array          处理后数据
     */
    public function xmlodds($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $aData = [];
        $arr = explode('$',$content);

        $aUnion = [];
        $aGame = [];
        $aAsian = [];
        $aEurope = [];
        $aBall = [];

        #联赛
        $uArr = explode(';',$arr[0]);
        foreach($uArr as $k=>$v)
        {
            $temp = explode(',',$v);
            $aUnion[$temp[0]] = $temp;
        }
        #比赛
        $gArr = explode(';',$arr[1]);
        foreach($gArr as $k=>$v)
        {
            $temp = explode(',',$v);
            $aGame[$temp[0]] = $temp;
        }

        #亚赔
        $aaArr = explode(';',$arr[2]);
        foreach($aaArr as $k=>$v)
        {
            $temp = explode(',',$v);
            $aAsian[$temp[0]][] = $temp;
        }

        #欧赔
        $eArr = explode(';',$arr[3]);
        foreach($eArr as $k=>$v)
        {
            $temp = explode(',',$v);
            $aEurope[$temp[0]][] = $temp;
        }

        #大小
        $bArr = explode(';',$arr[4]);
        foreach($bArr as $k=>$v)
        {
            $temp = explode(',',$v);
            $aBall[$temp[0]][] = $temp;
        }
        $aData = array('union'=>$aUnion,'game'=>$aGame,'asian'=>$aAsian,'europe'=>$aEurope,'ball'=>$aBall);
        return $aData;
    }

    /**
     * 即时指数各公司赔率即时变化处理
     * @param  string $content 待处理文本
     * @return array          处理后数据
     */
    public function chodds($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $aData = [];
        $fswData = [];
        $pswData = [];
        #亚赔
        if(preg_match_all('/<a>(.*?)<\/a>/i',$content,$a))
        {
            #全场
            $asian = [];
            if(preg_match_all('/<h>(.*?)<\/h>/i',$a[1][0],$h))
            {
                foreach($h[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    //$aTemp = array('game_id'=>$temp[0],'companyID'=>$temp[1],'fsw_asian_instant'=>$temp[2],'fsw_asian_instant_home'=>$temp[3],'fsw_asian_instant_away'=>$temp[4]);
                    $aTemp = array(0=>$temp[0],1=>$temp[1],2=>changeExp($temp[2]),3=>$temp[3],4=>$temp[4]);
                    $asian[$temp[0]][$temp[1]] = $aTemp;
                }
            }
            $fswData['asian'] = $asian;
            #半场
            $asian = [];
            if(isset($a[1][1]))
            {
                if(preg_match_all('/<h>(.*?)<\/h>/i',$a[1][1],$h))
                {
                    foreach($h[1] as $k=>$v)
                    {
                        $temp = explode(',',$v);
                        //$aTemp = array('game_id'=>$temp[0],'companyID'=>$temp[1],'psw_asian_instant'=>$temp[2],'psw_asian_instant_home'=>$temp[3],'psw_asian_instant_away'=>$temp[4]);
                        $aTemp = array(0=>$temp[0],1=>$temp[1],2=>changeExp($temp[2]),3=>$temp[3],4=>$temp[4]);
                        $asian[$temp[0]][$temp[1]] = $aTemp;
                    }
                }
            }
            $pswData['asian'] = $asian;
        }
        #欧赔
        if(preg_match_all('/<o>(.*?)<\/o>/i',$content,$o))
        {
            #全场
            $europe = [];
            if(preg_match_all('/<h>(.*?)<\/h>/i',$o[1][0],$h))
            {
                foreach($h[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    //$aTemp = array('game_id'=>$temp[0],'companyID'=>$temp[1],'fsw_europe_instant'=>$temp[2],'fsw_europe_instant_home'=>$temp[3],'fsw_europe_instant_away'=>$temp[4]);
                    $aTemp = array(0=>$temp[0],1=>$temp[1],2=>$temp[2],3=>$temp[3],4=>$temp[4]);
                    $europe[$temp[0]][$temp[1]] = $aTemp;
                }
            }
            $fswData['europe'] = $europe;
            #半场
            $europe = [];
            if(isset($o[1][1]))
            {
                if(preg_match_all('/<h>(.*?)<\/h>/i',$o[1][1],$h))
                {
                    foreach($h[1] as $k=>$v)
                    {
                        $temp = explode(',',$v);
                        //$aTemp = array('game_id'=>$temp[0],'companyID'=>$temp[1],'fsw_europe_instant'=>$temp[2],'fsw_europe_instant_home'=>$temp[3],'fsw_europe_instant_away'=>$temp[4]);
                        $aTemp = array(0=>$temp[0],1=>$temp[1],2=>$temp[2],3=>$temp[3],4=>$temp[4]);
                        $europe[$temp[0]][$temp[1]] = $aTemp;
                    }
                }
            }
            $pswData['europe'] = $europe;
        }

        #大小
        if(preg_match_all('/<d>(.*?)<\/d>/i',$content,$d))
        {
            #全场
            $ball = [];
            if(preg_match_all('/<h>(.*?)<\/h>/i',$d[1][0],$h))
            {
                foreach($h[1] as $k=>$v)
                {
                    $temp = explode(',',$v);
                    //$aTemp = array('game_id'=>$temp[0],'companyID'=>$temp[1],'fsw_ball_instant'=>$temp[2],'fsw_ball_instant_home'=>$temp[3],'fsw_ball_instant_away'=>$temp[4]);
                    $aTemp = array(0=>$temp[0],1=>$temp[1],2=>changeExp($temp[2]),3=>$temp[3],4=>$temp[4]);
                    $ball[$temp[0]][$temp[1]] = $aTemp;
                }
            }
            $fswData['ball'] = $ball;

            #半场
            $ball = [];
            if(isset($a[1][1]))
            {
                if(preg_match_all('/<h>(.*?)<\/h>/i',$d[1][1],$h))
                {
                    foreach($h[1] as $k=>$v)
                    {
                        $temp = explode(',',$v);
                        //$aTemp = array('game_id'=>$temp[0],'companyID'=>$temp[1],'fsw_ball_instant'=>$temp[2],'fsw_ball_instant_home'=>$temp[3],'fsw_ball_instant_away'=>$temp[4]);
                        $aTemp = array(0=>$temp[0],1=>$temp[1],2=>changeExp($temp[2]),3=>$temp[3],4=>$temp[4]);
                        $ball[$temp[0]][$temp[1]] = $aTemp;
                    }
                }
            }
            $pswData['ball'] = $ball;
        }
        $aData = array('fsw'=>$fswData,'psw'=>$pswData);
        return $aData;
    }

     /**
     +------------------------------------------------------------------------------
     * 以下开始为app篮球源数据
     +------------------------------------------------------------------------------
    */

    /**
     * 篮球赔率
     * @param  string $content 待处理源文本
     * @return array          处理后数据数组
     */
    public function bkodds($content)
    {
        if(empty($content) || !is_string($content))
        {
            return false;
        }

        $doc = \phpQuery::newDocumentHTML($content);
        $aData = [];
        foreach(pq('c')->find('o') as $v)
        {
            $sTemp = pq($v)->text();
            $aData[] = explode(',',$sTemp);
        }
        return $aData;
    }

    /**
     * 获取接口数据
     * @return void
     */
    private function getDataList()
    {
        //$this->data=unserialize(file_get_contents("datadata.txt"));    //数据源数据
        $this->data = include 'interfaceArr.php';
        //$this->data = C('PCinterface');
    }
}