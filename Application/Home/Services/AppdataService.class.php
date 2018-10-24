<?php
/*****
 +------------------------------------------------------------------------------
 * AppdataService   App服务类
 +------------------------------------------------------------------------------
 * Copyright (c) 2015 http://www.qqw.com All rights reserved.
 +------------------------------------------------------------------------------
 * @author Knight <39383198@qq.com>
 +------------------------------------------------------------------------------
*/
namespace Home\Services;

use Common\Mongo\GambleHallMongo;
class AppdataService
{
    protected $data;

    public function __construct()
    {
        $this->getDataList();
    }

    /**2
     * 当日即时赛事
     * @param  string   $content  源数据
     * @return array  当日即时赛事数据
     */
    public function fbtodayList($unionId,$subId ='',$key = 'no')
    {
        $GameFbinfo = M('GameFbinfo');
        $time1 = strtotime('10:32:00');

        $where = "(";
        if($time1 < time())
            $where .= "show_date = ".(int) date('Ymd',time());
        else
            $where .= "show_date = ".(int) date('Ymd',strtotime('-1 day',time()));
        $where .= "  OR (game_state IN ('1','2','3','4') AND show_date in (".(int) date('Ymd',strtotime('-1 day',time())).",".(int) date('Ymd',strtotime('+1 day',time())).",".(int) date('Ymd',time())."))";

        $where .= ")";
        $where .= " AND qc_game_fbinfo.status = 1";
        if(!empty($subId))
            $where .= " AND is_sub in(".$subId.")";
        /*else
            $where .= " AND is_sub in(0,1,2) ";*/

        if(!empty($unionId)) $where .= " AND qc_game_fbinfo.union_id in(".$unionId.")";

        $baseRes = $GameFbinfo->field('qc_game_fbinfo.id,game_id,qc_game_fbinfo.union_id,qc_game_fbinfo.union_name,game_date,game_time,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,red_card,yellow_card,is_video,is_flash')->join('
        LEFT JOIN qc_union ON qc_game_fbinfo.union_id=qc_union.union_id')->where($where)->order('game_state desc,game_date,game_time,is_sub,qc_game_fbinfo.id')->select();

        $aDetail = $this->getDetail();
        $aChange = $this->getChange();
        $aOdds = $this->getOddsXml();
        $file = C(TMPL_PARSE_STRING)['__DOWNFILE__'];
        $httpUrl = C('STATIC_SERVER');
        $aData = [];
        if($key == 'yes')
        {
            foreach($baseRes as $k=> $v)
            {
               /* if(empty($subId))
                {
                    if($v['fsw_exp'] == null || $v['fsw_ball'] == null)
                    {
                        unset($baseRes[$k]);
                        continue;
                    }
                }*/
                if($v['is_sub'] === null)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                $score = explode('-',$v['score']);
                $v['home_score'] = $score[0];
                $v['away_score'] = $score[1];
                $half_score = explode('-',$v['half_score']);
                $v['home_half_score'] = $half_score[0];
                $v['away_half_score'] = $half_score[1];
                $v['union_name'] = explode(',',$v['union_name']);
                $v['home_team_name'] = explode(',',$v['home_team_name']);
                $v['away_team_name'] = explode(',',$v['away_team_name']);
                $tempTime = explode(',',$v['game_half_time']);
                $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                $v['game_half_time'] = implode('',$tempTime);

                #全场亚盘即时赔率
                if($aOdds[$v['game_id']])
                {
                    $v['fsw_instant_asian_home'] = $aOdds[$v['game_id']]['fsw_instant_asian_home'];
                    $v['fsw_instant_asian'] = $aOdds[$v['game_id']]['fsw_instant_asian'];
                    $v['fsw_instant_asian_away'] = $aOdds[$v['game_id']]['fsw_instant_asian_away'];
                    $v['fsw_instant_ball_home'] = $aOdds[$v['game_id']]['fsw_instant_ball_home'];
                    $v['fsw_instant_ball'] = $aOdds[$v['game_id']]['fsw_instant_ball'];
                    $v['fsw_instant_ball_away'] = $aOdds[$v['game_id']]['fsw_instant_ball_away'];
                }
                #红黄牌
                if(!empty($v['red_card']))
                {
                    $red = explode('-',$v['red_card']);
                    $v['home_red_card'] = $red[0];
                    $v['away_red_card'] = $red[1];
                }
                else
                {
                    $v['home_red_card'] = '0';
                    $v['away_red_card'] = '0';
                }
                if(!empty($v['yellow_card']))
                {
                    $red = explode('-',$v['yellow_card']);
                    $v['home_yellow_card'] = $red[0];
                    $v['away_yellow_card'] = $red[1];
                }
                else
                {
                    $v['home_yellow_card'] = '0';
                    $v['away_yellow_card'] = '0';
                }

                #角球
                if(isset($aDetail[$v['game_id']]) && isset($aDetail[$v['game_id']]['tc']))
                {
                    $aTemp = explode(';',$aDetail[$v['game_id']]['tc'][1]);
                    foreach($aTemp as $k2 =>$v2)
                    {
                        $temp = explode(',',$v2);
                        if($temp[0] == 6)
                        {
                            $v['home_corner'] = $temp[1];
                            $v['away_corner'] = $temp[2];
                        }
                    }
                }
                else
                {
                    $v['home_corner'] = "0";
                    $v['away_corner'] = "0";
                }
                $aData[] = $v;
            }
        }
        else
        {
            foreach($baseRes as $k=> $v)
            {
                /*if(empty($subId))
                {
                    if($v['fsw_exp'] == null || $v['fsw_exp_home'] == null || $v['fsw_exp_away'] == null || $v['fsw_ball'] == null || $v['fsw_ball_home'] == null || $v['fsw_ball_away'] == null)
                    {
                        unset($baseRes[$k]);
                        continue;
                    }
                }*/
                if($v['is_sub'] === null)
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['game_state'] == -14) continue;
                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                $val[2] = explode(',',$v['union_name']);;
                $val[3] = isset($v['union_color'])?$v['union_color']:'';
                $val[4] = $v['is_sub']!==null?$v['is_sub']:'';
                $val[5] = $v['game_state'];
                $val[6] = $v['game_date'];
                $val[7] = $v['game_time'];
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
                if($aOdds[$v['game_id']])
                {
                    $val[17] = $aOdds[$v['game_id']]['fsw_instant_asian_home'];  //主队亚盘即时赔率
                    $val[18] = changeExp($aOdds[$v['game_id']]['fsw_instant_asian']);   //亚盘即时盘口
                    $val[19] = $aOdds[$v['game_id']]['fsw_instant_asian_away'];   //客队亚盘即时赔率
                    $val[20] = $aOdds[$v['game_id']]['fsw_instant_ball_home'];  //主队大小即时赔率
                    $val[21] = changeExp($aOdds[$v['game_id']]['fsw_instant_ball']);   //大小即时盘口
                    $val[22] = $aOdds[$v['game_id']]['fsw_instant_ball_away'];   //客队大小即时赔率
                }
                else
                {
                    $val[17] = ($v['fsw_exp_home'] !==null && $v['fsw_exp_home'] !=='')?$v['fsw_exp_home']:'';
                    $val[18] = ($v['fsw_exp'] !==null && $v['fsw_exp'] !=='')?changeExp($v['fsw_exp']):'';
                    $val[19] = ($v['fsw_exp_away'] && $v['fsw_exp_away'] !=='')?$v['fsw_exp_away']:'';
                    $val[20] = ($v['fsw_ball_home'] && $v['fsw_ball_home'] !=='')?$v['fsw_ball_home']:'';
                    $val[21] = ($v['fsw_ball'] !==null && $v['fsw_ball'] !=='')?changeExp($v['fsw_ball']):'';
                    $val[22] = ($v['fsw_ball_away'] && $v['fsw_ball_away'] !=='')?$v['fsw_ball_away']:'';
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
                    $red = explode('-',$v['yellow_card']);
                    $val[25] = $red[0];
                    $val[26] = $red[1];
                }
                else
                {
                    $val[25] = '0';
                    $val[26] = '0';
                }

                #角球
                if(isset($aDetail[$v['game_id']]) && isset($aDetail[$v['game_id']]['tc']))
                {
                    $val[27] = '0';
                    $val[28] = '0';
                    $aTemp = explode(';',$aDetail[$v['game_id']]['tc'][1]);
                    foreach($aTemp as $k2 =>$v2)
                    {
                        $temp = explode(',',$v2);
                        if($temp[0] == 6)
                        {
                            $val[27] = $temp[1];
                            $val[28] = $temp[2];
                        }
                    }
                }
                else
                {
                    $val[27] = '0';
                    $val[28] = '0';
                }
                $val[29] = $v['is_video'];
                $val[30] = $v['is_flash'];
                $aData[] = $val;
            }
        }
        return $aData;
    }

    /**
     * 当日滚球赛事
     * @return array 滚球赛事数组
     */
    public function fbRollList($unionId,$subId='',$key = 'yes')
    {
        $GameFbinfo = M('GameFbinfo');

        /*$y = date('Y',time());
        $m = date('m',time());
        $d = date('d',time());
        $time1 = mktime ( 10 ,  32 , 00 ,  $m ,  $d ,  $y );*/
        $time1 = strtotime('10:32:00');
        $where = "(";
        if($time1 < time())
            $where .= "(show_date = ".(int) date('Ymd',time())." AND game_state in(1,2,3,4))";
        else
            $where .= "(show_date = ".(int) date('Ymd',strtotime('-1 day',time()))." AND game_state in(1,2,3,4))";
        $where .= "  OR (game_state IN ('1','2','3','4') AND show_date in (".(int) date('Ymd',strtotime('-1 day',time())).",".(int) date('Ymd',strtotime('+1 day',time())).",".(int) date('Ymd',time())."))";
        $where .= ")";
        $where .= " AND qc_game_fbinfo.status = 1 AND is_go=1";
        if(!empty($subId)) $where .= " AND is_sub in(".$subId.")";
        if(!empty($unionId)) $where .= " AND qc_game_fbinfo.union_id in(".$unionId.")";

        $baseRes = $GameFbinfo->field('game_id,qc_game_fbinfo.id,qc_game_fbinfo.union_id,qc_game_fbinfo.union_name,game_date,game_time,game_half_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,show_date,union_color,is_sub')->join('qc_union ON qc_game_fbinfo.union_id = qc_union.union_id','LEFT')->where($where)->order('game_state desc,game_date,game_time,is_sub,qc_game_fbinfo.id')->select();

        $aOdds = $this->getOddsXml();
        $aData = [];
        if($key == 'yes')
        {
            foreach($baseRes as $k=> $v)
            {
                $score = explode('-',$v['score']);
                $v['home_score'] = $score[0];
                $v['away_score'] = $score[1];
                $half_score = explode('-',$v['half_score']);
                $v['home_half_score'] = $half_score[0];
                $v['away_half_score'] = $half_score[1];
                $v['union_name'] = explode(',',$v['union_name']);
                $v['home_team_name'] = explode(',',$v['home_team_name']);
                $v['away_team_name'] = explode(',',$v['away_team_name']);

                $tempTime = explode(',',$v['game_half_time']);
                $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                $v['game_half_time'] = implode('',$tempTime);

                #全场亚盘滚球赔率
                if($aOdds[$v['game_id']])
                {
                    #全场亚盘滚球
                    $v['fsw_roll_asian_home'] = $aOdds[$v['game_id']]['fsw_roll_asian_home'];
                    $v['fsw_roll_asian'] = $aOdds[$v['game_id']]['fsw_roll_asian'];
                    $v['fsw_roll_asian_away'] = $aOdds[$v['game_id']]['fsw_roll_asian_away'];
                    #全场大小滚球
                    $v['fsw_roll_ball_home'] = $aOdds[$v['game_id']]['fsw_roll_ball_home'];
                    $v['fsw_roll_ball'] = $aOdds[$v['game_id']]['fsw_roll_ball'];
                    $v['fsw_roll_ball_away'] = $aOdds[$v['game_id']]['fsw_roll_ball_away'];
                    #全场欧盘滚球
                    $v['fsw_roll_europe_home'] = $aOdds[$v['game_id']]['fsw_roll_europe_home'];
                    $v['fsw_roll_europe'] = $aOdds[$v['game_id']]['fsw_roll_europe'];
                    $v['fsw_roll_europe_away'] = $aOdds[$v['game_id']]['fsw_roll_europe_away'];

                    #半场场亚盘滚球
                    $v['psw_roll_asian_home'] = $aOdds[$v['game_id']]['psw_roll_asian_home'];
                    $v['psw_roll_asian'] = $aOdds[$v['game_id']]['psw_roll_asian'];
                    $v['psw_roll_asian_away'] = $aOdds[$v['game_id']]['psw_roll_asian_away'];
                    #半场大小滚球
                    $v['psw_roll_ball_home'] = $aOdds[$v['game_id']]['psw_roll_ball_home'];
                    $v['psw_roll_ball'] = $aOdds[$v['game_id']]['psw_roll_ball'];
                    $v['psw_roll_ball_away'] = $aOdds[$v['game_id']]['psw_roll_ball_away'];
                    #半场欧盘滚球
                    $v['psw_roll_europe_home'] = $aOdds[$v['game_id']]['psw_roll_europe_home'];
                    $v['psw_roll_europe'] = $aOdds[$v['game_id']]['psw_roll_europe'];
                    $v['psw_roll_europe_away'] = $aOdds[$v['game_id']]['psw_roll_europe_away'];
                }
                $aData[] = $v;
            }
        }
        else
        {
            foreach($baseRes as $k=> $v)
            {
                if($v['is_sub'] === null || $v['is_sub'] === '')
                {
                    unset($baseRes[$k]);
                    continue;
                }
                if($v['game_state'] == -14) continue;   //推迟的比赛不显示

                $val = [];
                $val[0] = $v['game_id'];
                $val[1] = $v['union_id'];
                $val[2] = explode(',',$v['union_name']);;
                $val[3] = isset($v['union_color'])?$v['union_color']:'';
                $val[4] = isset($v['is_sub'])?$v['is_sub']:'';
                $val[5] = $v['game_state'];
                $val[6] = $v['game_date'];
                $val[7] = $v['game_time'];
                $tempTime = explode(',',$v['game_half_time']);
                $tempTime[1] = str_pad($tempTime[1]+1,2,0,STR_PAD_LEFT);
                $tempTime[2] = str_pad($tempTime[2],2,0,STR_PAD_LEFT);
                $val[8] = implode('',$tempTime);
                $val[9] = explode(',',$v['home_team_name']);
                $val[10] = explode(',',$v['away_team_name']);
                 #全场亚盘滚球赔率
                if($aOdds[$v['game_id']])
                {
                    #全场亚盘滚球
                    $val[11] = $aOdds[$v['game_id']]['fsw_roll_asian_home'];
                    $val[12] = changeExp($aOdds[$v['game_id']]['fsw_roll_asian']);
                    $val[13] = $aOdds[$v['game_id']]['fsw_roll_asian_away'];
                    #全场大小滚球
                    $val[14] = $aOdds[$v['game_id']]['fsw_roll_ball_home'];
                    $val[15] = changeExp($aOdds[$v['game_id']]['fsw_roll_ball']);
                    $val[16] = $aOdds[$v['game_id']]['fsw_roll_ball_away'];
                    #全场欧盘滚球
                    $val[17] = $aOdds[$v['game_id']]['fsw_roll_europe_home'];
                    $val[18] = $aOdds[$v['game_id']]['fsw_roll_europe'];
                    $val[19] = $aOdds[$v['game_id']]['fsw_roll_europe_away'];

                    if($v['game_state'] == 1)
                    {
                        #半场场亚盘滚球
                        $val[20] = $aOdds[$v['game_id']]['psw_roll_asian_home'];
                        $val[21] = changeExp($aOdds[$v['game_id']]['psw_roll_asian']);
                        $val[22] = $aOdds[$v['game_id']]['psw_roll_asian_away'];
                        #半场大小滚球
                        $val[23] = $aOdds[$v['game_id']]['psw_roll_ball_home'];
                        $val[24] = changeExp($aOdds[$v['game_id']]['psw_roll_ball']);
                        $val[25] = $aOdds[$v['game_id']]['psw_roll_ball_away'];
                        #半场欧盘滚球
                        $val[26] = $aOdds[$v['game_id']]['psw_roll_europe_home'];
                        $val[27] = $aOdds[$v['game_id']]['psw_roll_europe'];
                        $val[28] = $aOdds[$v['game_id']]['psw_roll_europe_away'];
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
                    $val[16] = ($v['fsw_ball_away']== null && $v['fsw_ball_away'] == '')?'':$v['fsw_ball_home'];
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
                $aData[] = $val;
            }
        }
        return $aData;
    }

     /**
     * 当日完场赛事
     * @return array 完场赛事数组
     */
    public function fbOverList($date,$unionId,$subId,$key)
    {
        $disposeData = new \Common\Services\DisposeService();
        $detail = $this->data['detail_js'];
        //var_dump($date,$unionId,$subId,$key);exit;
        $ext = $this->getFileExt($detail['mimeType']);
        $fileName = DataPath.$detail['savePath'].$detail['name'].$ext;
        $dTxt = file_get_contents($fileName);

        $aRes = $disposeData->detail($dTxt);

        //待定
        $GameFbinfo = M('GameFbinfo');
        $where['qc_game_fbinfo.status'] = 1;
        $where['game_state'] = -1;
        if(!empty($subId))
            $where['is_sub'] =array('in',$subId);

        if(!empty($unionId)) $where['union_id'] = array('in',$unionId);

        if(!empty($date))
        {
            $where['show_date'] = $date;
        }
        else
        {
            /*$y = date('Y',time());
            $m = date('m',time());
            $d = date('d',time());
            $time1 = mktime ( 10 ,  32 , 00 ,  $m ,  $d ,  $y );*/
            $time1 = strtotime('10:32:00');
            if($time1 < time())
                $where['show_date'] =(int) date('Ymd',time());
            else
                $where['show_date'] =(int) date('Ymd',strtotime('-1 day',time()));
        }

        $baseRes = $GameFbinfo->field('game_id,qc_game_fbinfo.union_id,qc_game_fbinfo.union_name,game_date,game_time,game_state,home_team_name,away_team_name,score,half_score,home_team_rank,away_team_rank,union_color,is_sub,b.img_url as home_img_url,c.img_url as away_img_url,red_card,yellow_card,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away')->join('
        LEFT JOIN qc_union ON qc_game_fbinfo.union_id=qc_union.union_id
        LEFT JOIN qc_game_team b ON qc_game_fbinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_team c ON qc_game_fbinfo.away_team_id = c.team_id')->where($where)->order('game_state desc,game_date,game_time')->select();

        $file = C(TMPL_PARSE_STRING)['__DOWNFILE__'];
        $httpUrl = C('STATIC_SERVER');
        $aChange = $this->getChange();
        $aData = [];
        if(!empty($baseRes))
        {
            if($key == 'yes')
            {
                foreach($baseRes as $k =>$v)
                {
                    $v['union_name'] = explode(',',$v['union_name']);
                    $v['home_team_name'] = explode(',',$v['home_team_name']);
                    $v['away_team_name'] = explode(',',$v['away_team_name']);
                    $v['home_img_url'] = file_exists($file.$v['home_img_url']) && !empty($v['home_img_url'])? $httpUrl.ltrim($file,'.').$v['home_img_url'] : $httpUrl.'/Public/Home/images/common/home_def.png';
                    $v['away_img_url'] = file_exists($file.$v['away_img_url']) && !empty($v['away_img_url'])? $httpUrl.ltrim($file,'.').$v['away_img_url'] : $httpUrl.'/Public/Home/images/common/away_def.png';

                    $score = explode('-',$v['score']);
                    $v['home_score'] = $score[0];
                    $v['away_score'] = $score[1];
                    $half_score = explode('-',$v['half_score']);
                    $v['home_half_score'] = $half_score[0];
                    $v['away_half_score'] = $half_score[1];

                    #红黄牌
                    if(!empty($v['red_card']))
                    {
                        $red = explode('-',$v['red_card']);
                        $v['home_red_card'] = $red[0];
                        $v['away_red_card'] = $red[1];
                    }
                    else
                    {
                        $v['home_red_card'] = '0';
                        $v['away_red_card'] = '0';
                    }
                    if(!empty($v['yellow_card']))
                    {
                        $red = explode('-',$v['yellow_card']);
                        $v['home_yellow_card'] = $red[0];
                        $v['away_yellow_card'] = $red[1];
                    }
                    else
                    {
                        $v['home_yellow_card'] = '0';
                        $v['away_yellow_card'] = '0';
                    }

                    #角球
                    if(isset($aRes[$v['game_id']]) && isset($aRes[$v['game_id']]['tc']))
                    {
                        $aTemp = explode(';',$aRes[$v['game_id']]['tc'][1]);
                        foreach($aTemp as $k2 =>$v2)
                        {
                            $temp = explode(',',$v2);
                            if($temp[0] == 6)
                            {
                                $v['home_corner'] = $temp[1];
                                $v['away_corner'] = $temp[2];
                            }
                        }
                    }
                    //$baseRes[$k] = $v;
                    $aData[] = $v;
                }
            }
            else
            {
                foreach($baseRes as $k =>$v)
                {
                    if($v['is_sub'] === null || $v['is_sub'] === '')
                    {
                        unset($baseRes[$k]);
                        continue;
                    }
                    $val = [];
                    $val[0] = $v['game_id'];
                    $val[1] = $v['union_id'];
                    $val[2] = explode(',',$v['union_name']);;
                    $val[3] = isset($v['union_color'])?$v['union_color']:'';
                    $val[4] = isset($v['is_sub'])?$v['is_sub']:'';
                    $val[5] = $v['game_state'];
                    $val[6] = $v['game_date'];
                    $val[7] = $v['game_time'];
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
                    $val[14] = $score[1];
                    $half_score = explode('-',$v['half_score']);
                    $val[15] = $half_score[0];
                    $val[16] = $half_score[1];

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
                    if(isset($aRes[$v['game_id']]) && isset($aRes[$v['game_id']]['tc']))
                    {
                        $val[21] = '0';
                        $val[22] = '0';
                        $aTemp = explode(';',$aRes[$v['game_id']]['tc'][1]);
                        foreach($aTemp as $k2 =>$v2)
                        {
                            $temp = explode(',',$v2);
                            if($temp[0] == 6)
                            {
                                $val[21] = $temp[1];
                                $val[22] = $temp[2];
                            }
                        }
                    }
                    else
                    {
                        $val[21] = '0';
                        $val[22] = '0';
                    }
                    #全场亚盘滚球
                    $val[23] = ($v['fsw_exp_home'] == null && $v['fsw_exp_home'] == '')?'':$v['fsw_exp_home'];
                    $val[24] = ($v['fsw_exp']== null && $v['fsw_exp'] == '')?'':changeExp($v['fsw_exp']);
                    $val[25] = ($v['fsw_exp_away']== null && $v['fsw_exp_away'] == '')?'':$v['fsw_exp_away'];
                    #全场大小滚球
                    $val[26] = ($v['fsw_ball_home']== null && $v['fsw_ball_home'] == '')?'':$v['fsw_ball_home'];
                    $val[27] = ($v['fsw_ball']== null && $v['fsw_ball'] == '')?'':changeExp($v['fsw_ball']);
                    $val[28] = ($v['fsw_ball_away']== null && $v['fsw_ball_away'] == '')?'':$v['fsw_ball_home'];
                    /*$val[23] = file_exists(ltrim($file,"/").$v['home_img_url']) && !empty($v['home_img_url'])? $httpUrl.ltrim($file,'.').$v['home_img_url'] : $httpUrl.'/Public/Home/images/common/home_def.png';
                    $val[24] = file_exists(ltrim($file,"/").$v['away_img_url']) && !empty($v['away_img_url'])? $httpUrl.ltrim($file,'.').$v['away_img_url'] : $httpUrl.'/Public/Home/images/common/away_def.png';*/
                    $aData[] = $val;

                }
            }
        }
        return $aData;
    }

    /**
     * 赛程列表
     * @param  int $date       日期
     * @param  string $unionId 赛事ID,多个以‘,’隔开
     * @return array           赛程数据
     */
    public function fbFixtureList($date,$unionId,$subId='',$key ='yes')
    {
        if(empty($date))
            $date = date('Ymd',time());
        else
            if(strpos($date,'-')) $date = str_replace('-', '', $date);

        $where['qc_game_fbinfo.status'] = 1;
        $where['show_date'] = (int) $date;
        if(!empty($unionId))  $where['union_id'] = array('in',$unionId);
        if(!empty($subId))  $where['is_sub'] =array('in',$subId);

        $GameFbinfo = M('GameFbinfo');
        /*$baseRes = $GameFbinfo->field('game_id,qc_game_fbinfo.union_id,qc_game_fbinfo.union_name,game_date,game_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color')->join('qc_union ON qc_game_fbinfo.union_id = qc_union.union_id','LEFT')->where($where)->order('game_state desc,game_date,game_time')->select();*/
        $baseRes = $GameFbinfo->field('game_id,qc_game_fbinfo.union_id,qc_game_fbinfo.union_name,game_date,game_time,game_state,home_team_name,away_team_name,score,half_score,fsw_exp,fsw_exp_home,fsw_exp_away,fsw_ball,fsw_ball_home,fsw_ball_away,home_team_rank,away_team_rank,union_color,is_sub,b.img_url as home_img_url,c.img_url as away_img_url')->join('
        LEFT JOIN qc_union ON qc_game_fbinfo.union_id=qc_union.union_id
        LEFT JOIN qc_game_team b ON qc_game_fbinfo.home_team_id = b.team_id
        LEFT JOIN qc_game_team c ON qc_game_fbinfo.away_team_id = c.team_id')->where($where)->order('game_state desc,game_date,game_time')->select();

        $file = C(TMPL_PARSE_STRING)['__DOWNFILE__'];
        $httpUrl = C('STATIC_SERVER');
        $aData = [];
        if(!empty($baseRes))
        {
            if($key == 'yes')
            {
                foreach($baseRes as $k=>$v)
                {
                    $score = explode('-',$v['score']);
                    $v['home_score'] = $score[0];
                    $v['away_score'] = $score[1];
                    $half_score = explode('-',$v['half_score']);
                    $v['home_half_score'] = $half_score[0];
                    $v['away_half_score'] = $half_score[1];
                    $v['union_name'] = explode(',',$v['union_name']);
                    $v['home_team_name'] = explode(',',$v['home_team_name']);
                    $v['away_team_name'] = explode(',',$v['away_team_name']);
                    $v['home_img_url'] = file_exists($file.$v['home_img_url']) && !empty($v['home_img_url'])? $httpUrl.ltrim($file,'.').$v['home_img_url'] : $httpUrl.'/Public/Home/images/common/home_def.png';
                    $v['away_img_url'] = file_exists($file.$v['away_img_url']) && !empty($v['away_img_url'])? $httpUrl.ltrim($file,'.').$v['away_img_url'] : $httpUrl.'/Public/Home/images/common/away_def.png';
                    $aData[] = $v;
                }
            }
            else
            {
                foreach($baseRes as $k=>$v)
                {
                    if($v['is_sub'] === null || $v['is_sub'] === '')
                    {
                        unset($baseRes[$k]);
                        continue;
                    }
                    $val = [];
                    $val[0] = $v['game_id'];
                    $val[1] = $v['union_id'];
                    $val[2] = explode(',',$v['union_name']);;
                    $val[3] = isset($v['union_color'])?$v['union_color']:'';
                    $val[4] = isset($v['is_sub'])?$v['is_sub']:'';
                    $val[5] = $v['game_state'];
                    $val[6] = $v['game_date'];
                    $val[7] = $v['game_time'];
                    $val[8] = explode(',',$v['home_team_name']);
                    $val[9] = explode(',',$v['away_team_name']);
                    $val[10] = !empty($v['home_team_rank'])?pregUnionRank($v['home_team_rank']):'';
                    $val[11] = !empty($v['away_team_rank'])?pregUnionRank($v['away_team_rank']):'';
                    $val[12] = $v['fsw_exp_home'] == null?'':$v['fsw_exp_home'];
                    $val[13] = $v['fsw_exp'] == null?'':changeExp($v['fsw_exp']);
                    $val[14] = $v['fsw_exp_away'] == null?'':$v['fsw_exp_away'];
                    $val[15] = $v['fsw_ball_home'] == null?'':$v['fsw_ball_home'];
                    $val[16] = $v['fsw_ball'] == null?'':changeExp($v['fsw_ball']);
                    $val[17] = $v['fsw_ball_away'] == null?'':$v['fsw_ball_away'];
                    /*$val[18] = file_exists(ltrim($file,"/").$v['home_img_url']) && !empty($v['home_img_url'])? $httpUrl.ltrim($file,'.').$v['home_img_url'] : $httpUrl.'/Public/Home/images/common/home_def.png';
                    $val[19] = file_exists(ltrim($file,"/").$v['away_img_url']) && !empty($v['away_img_url'])? $httpUrl.ltrim($file,'.').$v['away_img_url'] : $httpUrl.'/Public/Home/images/common/away_def.png';*/
                    $aData[] = $val;
                }
            }
        }
        return $aData;
    }

    /**
     * app即时指数界面
     * @param  int $unionId 赛事ID，多个以‘,’隔开
     * @param  int $subId   级别ID，多个以‘,’隔开
     * @return array 即时赛事数组
     */
    public function fbInstant($unionId,$subId='',$key ='yes')
    {
        $item = $this->data['xmlodds'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;

        $xmlodds = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $xmlodds = $disposeData->xmlodds($content);
        }
        $rData = [];
        $odds_company = C('DB_COMPANY_ODDS');

        if(!empty($xmlodds))
        {
            /*$y = date('Y',time());
            $m = date('m',time());
            $d = date('d',time());
            $time1 = mktime ( 10 ,  32 , 00 ,  $m ,  $d ,  $y );*/
            $time1 = strtotime('10:32:00');
            if($time1 < time())
            {
                $show_date1 = $time1;     //开始时间戳
                $show_date2 = strtotime('+1 day',$time1); //结束时间戳
            }
            else
            {
                $show_date1 = date('Ymd',strtotime('-1 day',$time1));
                $show_date2 = $time1;
            }
            //var_dump(date('Y-m-d H:i:s',$show_date1),date('Y-m-d H:i:s',$show_date2));exit;
            $union = M('Union');
            $uRes = $union->field('union_id,union_color,is_sub')->select();
            $uData = [];
            $uSubData = [];
            foreach($uRes as $k=>$v)
            {
                $uData[$v['union_id']] = $v['union_color'] ;
                $uSubData[$v['union_id']] = $v['is_sub'] ;
            }
            if(!empty($unionId)) $unionArr = explode(',',$unionId);
            if(!empty($subId))
                $subArr = explode(',',$subId);
            else
                $subArr = array(0,1,2);
            $aSort = [];
            if($key == 'yes')
            {
                foreach($xmlodds['game'] as $k=>$v)
                {
                    if($v[14] != 0) continue;
                    $tempTime = substr($v[2], 0, -3);
                    if($tempTime < $show_date1 || $tempTime > $show_date2) continue;    //判断时间

                    if(!empty($unionId) && array_search($v[1], $unionArr)===false) continue;
                    if(!isset($uSubData[$v[1]])) continue;
                    if(array_key_exists($uSubData[$v[1]], $subArr)===false) continue;

                    $temp = [];
                    $temp['game_id'] = $v[0];
                    $temp['union_id'] = $v[1];
                    $temp['union_name'] = $xmlodds['union'][$v[1]][3];
                    $temp['union_color'] = $uData[$v[1]];
                    $temp['home_team_name'] = $v[5];
                    $temp['away_team_name'] = $v[10];
                    $temp['game_time'] = date('YmdHis',substr($v[2], 0, -3));
                    $temp['is_sub'] = $uSubData[$v[1]];
                    $aSort[$k] = $temp['game_time'];
                    $temp['home_team_img'] = getLogoTeam($v[4],$side=1);
                    $temp['away_team_img'] = getLogoTeam($v[9],$side=2);

                    $asianTemp =[];
                    foreach($xmlodds['asian'][$k] as $k2=>$v2)
                    {
                        if(!isset($odds_company[$v2[1]])) continue;
                        $aTemp = [];
                        $aTemp['companyID'] = $v2[1];
                        $aTemp['company_name'] = $odds_company[$v2[1]];
                        $aTemp['fsw_home'] = $v2[3];
                        $aTemp['fsw'] = $v2[2];
                        $aTemp['fsw_away'] = $v2[4];
                        $aTemp['fsw_instant_home'] = $v2[6];
                        $aTemp['fsw_instant'] = $v2[5];
                        $aTemp['fsw_instant_away'] = $v2[7];
                        $asianTemp[$v2[1]] = $aTemp;
                    }
                    $temp['instant_asian'] = $asianTemp;

                    $europeTemp =[];
                    foreach($xmlodds['europe'][$k] as $k2=>$v2)
                    {
                        if(!isset($odds_company[$v2[1]])) continue;
                        $aTemp = [];
                        $aTemp['companyID'] = $v2[1];
                        $aTemp['company_name'] = $odds_company[$v2[1]];
                        $aTemp['fsw_home'] = $v2[3];
                        $aTemp['fsw'] = $v2[2];
                        $aTemp['fsw_away'] = $v2[4];
                        $aTemp['fsw_instant_home'] = $v2[6];
                        $aTemp['fsw_instant'] = $v2[5];
                        $aTemp['fsw_instant_away'] = $v2[7];
                        $europeTemp[$v2[1]] = $aTemp;
                    }
                    $temp['instant_europe'] = $europeTemp;

                    $ballTemp =[];
                    foreach($xmlodds['ball'][$k] as $k2=>$v2)
                    {
                        if(!isset($odds_company[$v2[1]])) continue;
                        $aTemp = [];
                        $aTemp['companyID'] = $v2[1];
                        $aTemp['company_name'] = $odds_company[$v2[1]];
                        $aTemp['fsw_home'] = $v2[3];
                        $aTemp['fsw'] = $v2[2];
                        $aTemp['fsw_away'] = $v2[4];
                        $aTemp['fsw_instant_home'] = $v2[6];
                        $aTemp['fsw_instant'] = $v2[5];
                        $aTemp['fsw_instant_away'] = $v2[7];
                        $ballTemp[$v2[1]] = $aTemp;
                    }
                    $temp['instant_ball'] = $ballTemp;
                    $rData[$k] = $temp;
                }
            }
            else
            {
                foreach($xmlodds['game'] as $k=>$v)
                {
                    if($v[14] != 0) continue;    //判断比赛状态
                    $tempTime = substr($v[2], 0, -3);
                    if($tempTime < $show_date1 || $tempTime > $show_date2) continue;   //判断比赛时间

                    if(!empty($unionId) && array_search($v[1], $unionArr)===false) continue;  //判断联赛参数
                    if(!isset($uSubData[$v[1]])) continue;   //判断联赛级别
                    if(array_key_exists($uSubData[$v[1]], $subArr)===false) continue;  //判断联赛级别

                    $val = [];
                    $val[0] = $v[0];    //赛事ID
                    $val[1] = $v[1];     //联赛ID
                    $val[2] = isset($xmlodds['union'][$v[1]][3])?$xmlodds['union'][$v[1]][3]:'';   //联赛名称
                    $val[3] = isset($uData[$v[1]])?$uData[$v[1]]:'';  //联赛背景颜色
                    $val[4] = $v[5];   //主队球队名称
                    $val[5] = $v[10];  //客队球队名称
                    $val[6] = date('YmdHis',substr($v[2], 0, -3));   //比赛时间
                    $val[7] = isset($uSubData[$v[1]])?$uSubData[$v[1]]:'';    //联赛级别
                    $aSort[$k] = $val[6];
                    //$val[8] = getLogoTeam($v[4],$side=1);
                    //$val[9] = getLogoTeam($v[9],$side=2);

                    #亚赔
                    $asianTemp =[];
                    foreach($xmlodds['asian'][$k] as $k2=>$v2)
                    {
                        if(!isset($odds_company[$v2[1]])) continue;
                        $aTemp = [];
                        $aTemp[0] = $v2[1];    //公司ID
                        $aTemp[1] = $odds_company[$v2[1]];    //公司名称
                        $aTemp[2] = $v2[3];   //全场主队初盘赔率
                        $aTemp[3] = changeExp($v2[2]);   //全场初盘盘口
                        $aTemp[4] = $v2[4];   //全场客队初盘赔率
                        $aTemp[5] = $v2[6];   //全场主队即时赔率
                        $aTemp[6] = changeExp($v2[5]);   //全场即时盘口
                        $aTemp[7] = $v2[7];   //全场客队即时赔率
                        $asianTemp[$v2[1]] = $aTemp;
                    }
                    $val[8] = $asianTemp;

                    #欧赔
                    $europeTemp =[];
                    foreach($xmlodds['europe'][$k] as $k2=>$v2)
                    {
                        if(!isset($odds_company[$v2[1]])) continue;
                        $aTemp = [];
                        $aTemp[0] = $v2[1];
                        $aTemp[1] = $odds_company[$v2[1]];
                        $aTemp[2] = $v2[3];
                        $aTemp[3] = $v2[2];
                        $aTemp[4] = $v2[4];
                        $aTemp[5] = $v2[6];
                        $aTemp[6] = $v2[5];
                        $aTemp[7] = $v2[7];
                        $europeTemp[$v2[1]] = $aTemp;
                    }
                    $val[9] = $europeTemp;

                    #大小
                    $ballTemp =[];
                    foreach($xmlodds['ball'][$k] as $k2=>$v2)
                    {
                        if(!isset($odds_company[$v2[1]])) continue;
                        $aTemp = [];
                        $aTemp[0] = $v2[1];
                        $aTemp[1] = $odds_company[$v2[1]];
                        $aTemp[2] = $v2[3];
                        $aTemp[3] = changeExp($v2[2]);
                        $aTemp[4] = $v2[4];
                        $aTemp[5] = $v2[6];
                        $aTemp[6] = changeExp($v2[5]);
                        $aTemp[7] = $v2[7];
                        $ballTemp[$v2[1]] = $aTemp;
                    }
                    $val[10] = $ballTemp;
                    //$val[13] = $v2[14];
                    $rData[$k] = $val;
                }
                #当指数数据为空时，多加一个判断
                if(empty($rData))
                {
                    $show_date1 = $time1;     //开始时间戳
                    $show_date2 = strtotime('+1 day',$time1); //结束时间戳
                    foreach($xmlodds['game'] as $k=>$v)
                    {
                        if($v[14] != 0) continue;
                        $tempTime = substr($v[2], 0, -3);
                        if($tempTime < $show_date1 || $tempTime > $show_date2) continue;

                        if(!empty($unionId) && array_search($v[1], $unionArr)===false) continue;
                        if(!isset($uSubData[$v[1]])) continue;
                        if(array_key_exists($uSubData[$v[1]], $subArr)===false) continue;

                        $val = [];
                        $val[0] = $v[0];    //赛事ID
                        $val[1] = $v[1];     //联赛ID
                        $val[2] = isset($xmlodds['union'][$v[1]][3])?$xmlodds['union'][$v[1]][3]:'';   //联赛名称
                        $val[3] = isset($uData[$v[1]])?$uData[$v[1]]:'';  //联赛背景颜色
                        $val[4] = $v[5];   //主队球队名称
                        $val[5] = $v[10];  //客队球队名称
                        $val[6] = date('YmdHis',substr($v[2], 0, -3));   //比赛时间
                        $val[7] = isset($uSubData[$v[1]])?$uSubData[$v[1]]:'';    //联赛级别
                        $aSort[$k] = $val[6];
                        //$val[8] = getLogoTeam($v[4],$side=1);
                        //$val[9] = getLogoTeam($v[9],$side=2);

                        #亚赔
                        $asianTemp =[];
                        foreach($xmlodds['asian'][$k] as $k2=>$v2)
                        {
                            if(!isset($odds_company[$v2[1]])) continue;
                            $aTemp = [];
                            $aTemp[0] = $v2[1];    //公司ID
                            $aTemp[1] = $odds_company[$v2[1]];    //公司名称
                            $aTemp[2] = $v2[3];   //全场主队初盘赔率
                            $aTemp[3] = changeExp($v2[2]);   //全场初盘盘口
                            $aTemp[4] = $v2[4];   //全场客队初盘赔率
                            $aTemp[5] = $v2[6];   //全场主队即时赔率
                            $aTemp[6] = changeExp($v2[5]);   //全场即时盘口
                            $aTemp[7] = $v2[7];   //全场客队即时赔率
                            $asianTemp[$v2[1]] = $aTemp;
                        }
                        $val[8] = $asianTemp;

                        #欧赔
                        $europeTemp =[];
                        foreach($xmlodds['europe'][$k] as $k2=>$v2)
                        {
                            if(!isset($odds_company[$v2[1]])) continue;
                            $aTemp = [];
                            $aTemp[0] = $v2[1];
                            $aTemp[1] = $odds_company[$v2[1]];
                            $aTemp[2] = $v2[3];
                            $aTemp[3] = $v2[2];
                            $aTemp[4] = $v2[4];
                            $aTemp[5] = $v2[6];
                            $aTemp[6] = $v2[5];
                            $aTemp[7] = $v2[7];
                            $europeTemp[$v2[1]] = $aTemp;
                        }
                        $val[9] = $europeTemp;

                        #大小
                        $ballTemp =[];
                        foreach($xmlodds['ball'][$k] as $k2=>$v2)
                        {
                            if(!isset($odds_company[$v2[1]])) continue;
                            $aTemp = [];
                            $aTemp[0] = $v2[1];
                            $aTemp[1] = $odds_company[$v2[1]];
                            $aTemp[2] = $v2[3];
                            $aTemp[3] = changeExp($v2[2]);
                            $aTemp[4] = $v2[4];
                            $aTemp[5] = $v2[6];
                            $aTemp[6] = changeExp($v2[5]);
                            $aTemp[7] = $v2[7];
                            $ballTemp[$v2[1]] = $aTemp;
                        }
                        $val[10] = $ballTemp;
                        $rData[$k] = $val;
                    }
                }
            }
        }
        array_multisort($aSort, SORT_ASC, $rData);
        return $rData;
    }

     /**
     * 即时赔率数据(多公司,指数比较界面数据源)
     * @return array 全场即时赔率数据
     */
    public function getChodds($gameId = '',$key = 'yes')
    {

        if(!empty($gameId)) $gData = explode(',',$gameId);
        $item = $this->data['ch_odds'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$companyID.$ext;

        $chodds = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $chodds = $disposeData->chodds($content);
        }
        $rData = [];

        if(!empty($chodds))
        {
            foreach($chodds['fsw'] as $k=>$v)
            {
                if(empty($gData))
                {
                    $temp = array('name'=>$k,'content'=>$v);
                    $rData[] = $temp;
                }
                else
                {

                    $aTemp = [];
                    foreach($v as $k2=>$v2)
                    {
                        if(array_search($k2, $gData)!==false) $aTemp[$k2] =$v2;
                    }
                    $temp = array('name'=>$k,'content'=>$aTemp);
                    $rData[] = $temp;
                }

            }
        }
        return $rData;
    }

    /**
     * 当日赛事变化数据解析
     * @return array 赛事变化数据
     */
     public function getChange($key)
    {
        $item = $this->data['change'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;

        $aChange = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $content = iconv('gb2312','utf-8//IGNORE',$content);

            if(preg_match_all('/<h>(.*?)<\/h>/i',$content,$data))
            {
                foreach($data[1] as $k=>$v)
                {
                    if(strpos($v,'CDATA')!==false)
                        $str = str_replace(array('<![CDATA[',']]>'),'',$v);
                    else
                        $str = $v;
                    $arr = explode('^',$str);
                    $aChange[] =$arr;
                }
            }
        }

        $rData = [];
        if(!empty($aChange))
        {
            if($key == 'yes')
            {
                foreach($aChange as $k =>$v)
                {
                    $aTemp['game_id'] = $v[0];     //赛事ID
                    $aTemp['game_state'] = $v[1];  //赛事状态
                    $aTemp['home_score'] = $v[2];  //主队得分
                    $aTemp['away_score'] = $v[3];  //客队得分
                    $aTemp['home_half_score'] = $v[4];  //半场主队得分
                    $aTemp['away_half_score'] = $v[5];  //半场客队得分
                    $aTemp['home_red_card'] = $v[6];  //主队红牌
                    $aTemp['away_red_card'] = $v[7];  //客队红牌
                    $aTemp['home_yellow_card'] = $v[12];  //主队黄牌
                    $aTemp['away_yellow_card'] = $v[13];  //客队黄牌
                    $aTemp['game_time'] = $v[8];   //比赛时间
                    $aTime = explode(',',$v[9]);
                    $aTime[1] = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                    $aTime[2] = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                    $aTemp['game_half_time'] = implode('',$aTime);   //半场时间
                    $rData[$v[0]] = $aTemp;
                }
            }
            else
            {
                foreach($aChange as $k =>$v)
                {
                    $aTemp[0] = $v[0];     //赛事ID
                    $aTemp[1] = $v[1];  //赛事状态
                    $aTemp[2] = $v[2];  //主队得分
                    $aTemp[3] = $v[3];  //客队得分
                    $aTemp[4] = $v[4];  //半场主队得分
                    $aTemp[5] = $v[5];  //半场客队得分
                    $aTemp[6] = $v[6];  //主队红牌
                    $aTemp[7] = $v[7];  //客队红牌
                    $aTemp[8] = $v[12];  //主队黄牌
                    $aTemp[9] = $v[13];  //客队黄牌
                    $aTemp[10] = $v[8];   //比赛时间
                    $aTime = explode(',',$v[9]);
                    $aTime[1] = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                    $aTime[2] = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                    $aTemp[11] = implode('',$aTime);   //半场时间
                    $rData[$v[0]] = $aTemp;
                }
            }
        }
        return $rData;
    }

    /**
     * 当日赛事变化数据解析（数据库数据）
     * @return array 赛事变化数据
     */
    public function getChangeB()
    {
        $rData = [];

        if(!S('cache_fb_change_flag')) usleep(1000);

        if(S('cache_fb_change'))
        {
            $rData = S('cache_fb_change');
            unset($rData['cache']);
            //file_put_contents('testlog.log', 'app getChangeB cache:'.date("Y-m-d H:i:s")."\n",FILE_APPEND );
        }
        else
        {
            S('cache_fb_change_flag',false);
            $res = M()->query('select game_id,game_id_new,change_str,update_time from qc_change_fb where update_time = (select update_time as utime from qc_change_fb order by update_time desc limit 1) order by id');
            //$rData = [];
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
                        $aTime = explode(',',$arr[9]);
                        $aTime[1] = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                        $aTime[2] = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                        $aTemp[11] = implode('',$aTime);   //半场时间
                        $aTemp[12] = $arr[16] == null?'':$arr[16];   //主队角球
                        $aTemp[13] = $arr[17] == null?'':$arr[17];  //主队角球
                        $rData[$v['game_id']] = $aTemp;
                    }
                }
            }
            //file_put_contents('testlog.log', 'app getChangeB:      '.date("Y-m-d H:i:s")."\n",FILE_APPEND );
            $rData['cache'] = 'true';
            S('cache_fb_change',$rData,1);
            unset($rData['cache']);
            S('cache_fb_change_flag',true);
        }
        return $rData;
    }

    /**
     * 获取全场指数变化数据
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     */
    public function getGoal($companyID,$key = 'yes')
    {
        if(empty($companyID)) return false;
        $item = $this->data['goal'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$companyID.$ext;
        $aGoal = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $aGoal = $disposeData->goals($content);
        }

        $rData = [];
        if(!empty($aGoal))
        {
            if($key == 'yes')
            {
                foreach($aGoal as $k =>$v)
                {
                    #亚盘滚球全场
                    $aTemp['fsw_roll_asian'] = $v[2];
                    $aTemp['fsw_roll_asian_home'] = $v[3];
                    $aTemp['fsw_roll_asian_away'] = $v[4];
                    #欧盘即时全场
                    $aTemp['fsw_instant_europe_home'] = $v[6];
                    $aTemp['fsw_instant_europe'] = $v[7];
                    $aTemp['fsw_instant_europe_away'] = $v[8];
                    #大小即时全场
                    $aTemp['fsw_roll_ball'] = $v[10];
                    $aTemp['fsw_roll_ball_home'] = $v[11];
                    $aTemp['fsw_roll_ball_away'] = $v[12];

                    $aTemp['game_state'] = $v[13];
                    $rData[$v[0]] = $aTemp;
                }
            }
            else
            {
                 foreach($aGoal as $k =>$v)
                {
                    #亚盘滚球全场
                    $aTemp[0] = changeExp($v[2]);    //亚盘盘口
                    $aTemp[1] = $v[3];    //主队亚盘赔率
                    $aTemp[2] = $v[4];    //客队亚盘赔率
                    #欧盘即时全场
                    $aTemp[3] = $v[6];    //主队欧赔赔率
                    $aTemp[4] = $v[7];    //欧赔盘口
                    $aTemp[5] = $v[8];    //客队欧赔赔率
                    #大小即时全场
                    $aTemp[6] = changeExp($v[10]);   //大小盘口
                    $aTemp[7] = $v[11];   //主队大小赔率
                    $aTemp[8] = $v[12];   //客队大小赔率

                    $aTemp[9] = $v[13];   //比赛状态
                    $rData[$v[0]] = $aTemp;
                }
            }

        }
        return $rData;
    }

    /**
     * 获取全场指数变化数据(数据库)
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     */
    public function getGoalB($companyID)
    {
        if(empty($companyID)) return false;

        $rData = [];
        if(S('cache_fb_goal'))
        {
            $rData = S('cache_fb_goal');
            unset($rData['cache']);
        }
        else
        {
            $sql = 'select update_time as utime from qc_fb_goal where company_id='.$companyID.' ORDER BY update_time desc limit 1';
            $res = M()->query($sql);
            $rData = [];

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
                        $temp[0] = changeExp($aOdds[7]);
                        $temp[1] = $aOdds[6];
                        $temp[2] = $aOdds[8];
                    }
                    else if(!empty($aOdds[3]) || !empty($aOdds[4]) || !empty($aOdds[5]))
                    {
                        $temp[0] = changeExp($aOdds[4]);
                        $temp[1] = $aOdds[3];
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
                        $temp[6] = changeExp($bOdds[7]);
                        $temp[7] = $bOdds[6];
                        $temp[8] = $bOdds[8];
                    }
                    else if(!empty($bOdds[3]) || !empty($bOdds[4]) || !empty($bOdds[5]))
                    {
                        $temp[6] = changeExp($bOdds[4]);
                        $temp[7] = $bOdds[3];
                        $temp[8] = $bOdds[5];
                    }
                    else
                    {
                        $temp[6] = '';
                        $temp[7] = '';
                        $temp[8] = '';
                    }

                    $rData[$v['game_id']] = $temp;
                }
            }
            $rData['cache'] = 'true';
            S('cache_fb_goal',$rData,1);
            unset($rData['cache']);
        }
        return $rData;
    }




    /**
     * 获取滚球goal赔率历史变化
     * @param  int   $gameId    赛事ID
     * @param  int   $companyID 公司ID
     * @param  int   $type      1,让球；2,大小；3欧赔
     * @return array          全场指数变化数据
     */
    public function getGoalHistroy($gameId ,$companyID ,$type = 1)
    {
        if(empty($gameId) || empty($companyID)) return false;

        $goalObj = M('goalfbNs');

        $where = ' game_id ='.$gameId;
        $where .= ' and company_id ='.$companyID;

        switch($type)
        {
            case 1:
                $where .= ' and odds_type in(1,2,3)';
                break;
            case 2:
                $where .= ' and odds_type in(4,5,6)';
                break;
            case 3:
                $where .= ' and odds_type in(7,8,9)';
                break;
            default:
                $where .= ' and odds_type in(1,2,3)';
                break;
        }

        $res = $goalObj->field('id,home_exp,exp,away_exp,o_time,score,odds_type')->where($where)->order('o_time,odds_type asc,id')->select();

        $rData = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                $temp =[
                    0 => $v['home_exp'],
                    1 => changeExp($v['exp']),
                    2 => $v['away_exp'],
                    3 => date('m-d H:i',$v['o_time']),
                    //3 => ($v['odds_type'] ==3 || $v['odds_type']==6 || $v['odds_type']==9)?date('i',$v['o_time']):'0',
                    //4 => $v['score'],
                ];
                $rData[] = $temp;
            }
        }
        return $rData;
    }

    /**
     * 获取滚球goal赔率历史变化
     * @param  int   $gameId    赛事ID
     * @param  int   $type      1,让球；2,欧赔；3,大小
     * @return array          全场指数变化数据
     */
    public function getGoalRoll($gameId, $type = 1)
    {
        if(empty($gameId)) return false;

        $goalObj = M('goalfbNs');
        $comArr = [3,8,24,23];
        $where['is_used'] = 1;
        $where['game_id'] = (int) $gameId;

        switch($type)
        {
            case 1:
                $where['odds_type']  = array('in','1,2,3');
                break;
            case 2:
                $where['odds_type']  = array('in','4,5,6');
                break;
            case 3:
                $where['odds_type']  = array('in','7,8,9');
                break;
            default:
                $where['odds_type']  = array('in','1,2,3');
                break;
        }

        $rData = [];
        foreach($comArr as $k=>$v)
        {
            $where['company_id'] = (int) $v;
            $res = $goalObj->field('id,home_exp,exp,away_exp,o_time,score,odds_type')->where($where)->order('o_time,odds_type asc,id')->select();

            if(!empty($res))
            {
                foreach($res as $k=>$v)
                {
                    if(in_array($v['odds_type'],[1,2,4,5,7,8])) continue;
                    $temp =[
                        0 => $v['home_exp'],
                        1 => changeExp($v['exp']),
                        2 => $v['away_exp'],
                        3 => date('Y-m-d H:i',$v['o_time']),
                        //3 => ($v['odds_type'] ==3 || $v['odds_type']==6 || $v['odds_type']==9)?date('i',$v['o_time']):'0',
                        4 => $v['score'],
                    ];
                    $rData[] = $temp;
                }
                break;
            }
        }
        return $rData;
    }



    /**
     * 获取全场指数变化数据
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     */
    public function getGoals($companyID, $key = 'yes')
    {
        if(empty($companyID)) return false;
        $companyID = (int) $companyID;
        $aGoal = $this->getGoalsXml($companyID);
        //var_dump($res);exit;
        $rData = [];
        if(!empty($aGoal))
        {
            if($key == 'yes')
            {
                foreach($aGoal as $k =>$v)
                {
                    #亚盘滚球全场
                   $aTemp['fsw_roll_asian'] = $v['fsw_roll_asian'];
                    $aTemp['fsw_roll_asian_home'] = $v['fsw_roll_asian_home'];
                    $aTemp['fsw_roll_asian_away'] = $v['fsw_roll_asian_away'];
                    #欧盘即时全场
                    $aTemp['fsw_instant_europe_home'] = $v['fsw_instant_europe_home'];
                    $aTemp['fsw_instant_europe'] = $v['fsw_instant_europe'];
                    $aTemp['fsw_instant_europe_away'] = $v['fsw_instant_europe_away'];
                    #大小即时全场
                    $aTemp['fsw_roll_ball'] = $v['fsw_roll_ball'];
                    $aTemp['fsw_roll_ball_home'] = $v['fsw_roll_ball_home'];
                    $aTemp['fsw_roll_ball_away'] = $v['fsw_roll_ball_away'];

                    $aTemp['game_state'] = $v['game_state'];
                    $rData[$k] = $aTemp;
                }
            }
            else
            {
                 foreach($aGoal as $k =>$v)
                {
                    #亚盘滚球全场
                    $aTemp[0] = $v['fsw_roll_asian'];    //亚盘盘口
                    $aTemp[1] = $v['fsw_roll_asian_home'];    //主队亚盘赔率
                    $aTemp[2] = $v['fsw_roll_asian_away'];    //客队亚盘赔率
                    #欧盘即时全场
                    $aTemp[3] = $v['fsw_instant_europe_home'];    //主队欧赔赔率
                    $aTemp[4] = $v['fsw_instant_europe'];    //欧赔盘口
                    $aTemp[5] = $v['fsw_instant_europe_away'];    //客队欧赔赔率
                    #大小即时全场
                    $aTemp[6] = $v['fsw_roll_ball'];   //大小盘口
                    $aTemp[7] = $v['fsw_roll_ball_home'];   //主队大小赔率
                    $aTemp[8] = $v['fsw_roll_ball_away'];   //客队大小赔率

                    $aTemp[9] = $v['game_state'];   //比赛状态
                    $rData[$k] = $aTemp;
                }
            }
        }
        return $rData;
    }

    /**
     * 获取全场指数变化数据
     * @param  int $companyID 公司ID
     * @return array          全场指数变化数据
     */
    public function getGoalsXml($companyID)
    {
        $item = $this->data['goals'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$companyID.$ext;
        $aGoals = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $aGoals = $disposeData->goals($content);
        }

        $rData = [];
        if(!empty($aGoals))
        {
            foreach($aGoals as $k =>$v)
            {
                #亚盘滚球全场
                $aTemp['fsw_roll_asian'] = changeExp($v[2]);
                $aTemp['fsw_roll_asian_home'] = $v[3];
                $aTemp['fsw_roll_asian_away'] = $v[4];
                #欧盘即时全场
                $aTemp['fsw_instant_europe_home'] = $v[6];
                $aTemp['fsw_instant_europe'] = $v[7];
                $aTemp['fsw_instant_europe_away'] = $v[8];
                #大小即时全场
                $aTemp['fsw_roll_ball'] = changeExp($v[10]);
                $aTemp['fsw_roll_ball_home'] = $v[11];
                $aTemp['fsw_roll_ball_away'] = $v[12];

                $aTemp['game_state'] = $v[13];
                $rData[$v[0]] = $aTemp;
            }
        }
        return $rData;
    }

    /**
     *  赔率数据
     * @return array 最新赔率数据
     */
    public function getOdds($gameId,$type = '1' ,$key = 'yes')
    {
        $item = $this->data['OddsDataDiv'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;
        $aOdds = [];

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $dd = new \Common\Services\DisposeService();
            $aOdds = $dd->oddsDataDiv($content);
        }

        $aGameId = [];
        if(!empty($gameId)) $aGameId = explode(',',$gameId);

        $rData = [];
        $fData = [];
        $pData = [];
        if(!empty($aOdds))
        {
            if($key == 'yes')
            {
                foreach($aOdds as $k =>$v)
                {
                    #全场亚盘初盘
                    $fsw_exp_asian = explode(',',$v);
                    $aTemp['fsw_exp_asian_home'] = $v['fsw'][0];
                    $aTemp['fsw_exp_asian'] = $v['fsw'][1];
                    $aTemp['fsw_exp_asian_away'] = $v['fsw'][2];
                    #全场大小初盘
                    $aTemp['fsw_ball_home'] =$v['fsw'][5];
                    $aTemp['fsw_ball'] = $v['fsw'][6];
                    $aTemp['fsw_ball_awqy'] = $v['fsw'][7];
                    #全场欧盘初盘
                    $aTemp['fsw_exp_europe_home'] = $v['fsw'][10];
                    $aTemp['fsw_exp_europe'] = $v['fsw'][11];
                    $aTemp['fsw_exp_europe_away'] = $v['fsw'][12];
                    #全场亚盘即时
                    $aTemp['fsw_instant_asian_home'] = $v['fsw'][15];
                    $aTemp['fsw_instant_asian'] = $v['fsw'][16];
                    $aTemp['fsw_instant_asian_away'] = $v['fsw'][17];
                    #全场大小即时
                    $aTemp['fsw_instant_ball_home'] = $v['fsw'][20];
                    $aTemp['fsw_instant_ball'] = $v['fsw'][21];
                    $aTemp['fsw_instant_ball_away'] = $v['fsw'][22];
                    #全场欧盘即时
                    $aTemp['fsw_exp_europe_home'] = $v['fsw'][25];
                    $aTemp['fsw_exp_europe'] = $v['fsw'][26];
                    $aTemp['fsw_exp_europe_away'] = $v['fsw'][27];
                    #全场亚盘滚球
                    $aTemp['fsw_roll_asian_home'] = $v['fsw'][30];
                    $aTemp['fsw_roll_asian'] = $v['fsw'][31];
                    $aTemp['fsw_roll_asian_away'] = $v['fsw'][32];
                    #全场大小滚球
                    $aTemp['fsw_roll_ball_home'] = $v['fsw'][35];
                    $aTemp['fsw_roll_ball'] = $v['fsw'][36];
                    $aTemp['fsw_roll_ball_away'] = $v['fsw'][37];
                    #全场欧盘滚球
                    $aTemp['fsw_roll_europe_home'] = $v['fsw'][40];
                    $aTemp['fsw_roll_europe'] = $v['fsw'][41];
                    $aTemp['fsw_roll_europe_away'] = $v['fsw'][42];
                    if(empty($aGameId))
                    {
                        $fData[$k] = $aTemp;
                    }
                    else
                    {
                        $k = (string) $k;
                        if(array_search($k, $aGameId) !== false)
                        {
                            $fData[$k] = $aTemp;
                        }
                    }

                    #半场亚盘初盘
                    $aTemp2['psw_exp_asian_home'] = $v['psw'][0];
                    $aTemp2['psw_exp_asian'] = $v['psw'][1];
                    $aTemp2['psw_exp_asian_away'] = $v['psw'][2];
                    #半场大小初盘
                    $aTemp2['psw_ball_home'] = $v['psw'][5];
                    $aTemp2['psw_ball'] =$v['psw'][6];
                    $aTemp2['psw_ball_awqy'] = $v['psw'][7];
                    #半场欧盘初盘
                    $aTemp2['psw_exp_europe_home'] = $v['psw'][10];
                    $aTemp2['psw_exp_europe'] = $v['psw'][11];
                    $aTemp2['psw_exp_europe_away'] = $v['psw'][12];
                    #半场亚盘即时
                    $aTemp2['psw_instant_asian_home'] = $v['psw'][15];
                    $aTemp2['psw_instant_asian'] = $v['psw'][16];
                    $aTemp2['psw_instant_asian_away'] = $v['psw'][17];
                    #半场大小即时
                    $aTemp2['psw_instant_ball_home'] = $v['psw'][20];
                    $aTemp2['psw_instant_ball'] = $v['psw'][21];
                    $aTemp2['psw_instant_ball_away'] = $v['psw'][22];
                    #半场欧盘即时
                    $aTemp2['psw_exp_europe_home'] = $v['psw'][25];
                    $aTemp2['psw_exp_europe'] = $v['psw'][26];
                    $aTemp2['psw_exp_europe_away'] = $v['psw'][27];
                    #半场亚盘滚球
                    $aTemp2['psw_roll_asian_home'] = $v['psw'][30];
                    $aTemp2['psw_roll_asian'] = $v['psw'][31];
                    $aTemp2['psw_roll_asian_away'] = $v['psw'][32];
                    #半场大小滚球
                    $aTemp2['psw_roll_ball_home'] = $v['psw'][35];
                    $aTemp2['psw_roll_ball'] = $v['psw'][36];
                    $aTemp2['psw_roll_ball_away'] = $v['psw'][37];
                    #半场欧盘滚球
                    $aTemp2['psw_roll_europe_home'] = $v['psw'][40];
                    $aTemp2['psw_roll_europe'] = $v['psw'][41];
                    $aTemp2['psw_roll_europe_away'] = $v['psw'][42];

                    if(empty($aGameId))
                    {
                        $pData[$k] = $aTemp2;
                    }
                    else
                    {
                        $k = (string) $k;
                        if(array_search($k, $aGameId) !== false)
                        {
                            $pData[$k] = $aTemp2;
                        }
                    }
                }
                $rData[] = array('fsw'=>$fData,'psw'=>$pData);
            }
            else
            {
                foreach($aOdds as $k =>$v)
                {
                    $aTemp = [];
                    #全场亚盘初盘
                    $fsw_exp_asian = explode(',',$v);
                    $aTemp[0] = $v['fsw'][0];    //主
                    $aTemp[1] = changeExp($v['fsw'][1]);    //盘口
                    $aTemp[2] = $v['fsw'][2];    //客
                    #全场大小初盘
                    $aTemp[3] = $v['fsw'][5];   //主
                    $aTemp[4] = changeExp($v['fsw'][6]);   //盘口
                    $aTemp[5] = $v['fsw'][7];   //客
                    #全场欧盘初盘
                    $aTemp[6] = $v['fsw'][10];
                    $aTemp[7] = $v['fsw'][11];
                    $aTemp[8] = $v['fsw'][12];
                    #全场亚盘即时
                    $aTemp[9] = $v['fsw'][15];
                    $aTemp[10] = changeExp($v['fsw'][16]);
                    $aTemp[11] = $v['fsw'][17];
                    #全场大小即时
                    $aTemp[12] = $v['fsw'][20];
                    $aTemp[13] = changeExp($v['fsw'][21]);
                    $aTemp[14] = $v['fsw'][22];
                    #全场欧盘即时
                    $aTemp[15] = $v['fsw'][25];
                    $aTemp[16] = $v['fsw'][26];
                    $aTemp[17] = $v['fsw'][27];
                    #全场亚盘滚球
                    $aTemp[18] = $v['fsw'][30];
                    $aTemp[19] = changeExp($v['fsw'][31]);
                    $aTemp[20] = $v['fsw'][32];
                    #全场大小滚球
                    $aTemp[21] = $v['fsw'][35];
                    $aTemp[22] = changeExp($v['fsw'][36]);
                    $aTemp[23] = $v['fsw'][37];
                    #全场欧盘滚球
                    $aTemp[24] = $v['fsw'][40];
                    $aTemp[25] = $v['fsw'][41];
                    $aTemp[26] = $v['fsw'][42];
                    if(empty($aGameId))
                    {
                        $fData[$k] = $aTemp;
                    }
                    else
                    {
                        $k = (string) $k;
                        if(array_search($k, $aGameId) !== false)
                        {
                            $fData[$k] = $aTemp;
                        }
                    }
                    $aTemp2 = [];
                    #半场亚盘初盘
                    $aTemp2[0] = $v['psw'][0];
                    $aTemp2[1] = changeExp($v['psw'][1]);
                    $aTemp2[2] = $v['psw'][2];
                    #半场大小初盘
                    $aTemp2[3] = $v['psw'][5];
                    $aTemp2[4] = changeExp($v['psw'][6]);
                    $aTemp2[5] = $v['psw'][7];
                    #半场欧盘初盘
                    $aTemp2[6] = $v['psw'][10];
                    $aTemp2[7] = $v['psw'][11];
                    $aTemp2[8] = $v['psw'][12];
                    #半场亚盘即时
                    $aTemp2[9] = $v['psw'][15];
                    $aTemp2[10] = changeExp($v['psw'][16]);
                    $aTemp2[11] = $v['psw'][17];
                    #半场大小即时
                    $aTemp2[12] = $v['psw'][20];
                    $aTemp2[13] = changeExp($v['psw'][21]);
                    $aTemp2[14] = $v['psw'][22];
                    #半场欧盘即时
                    $aTemp2[15] = $v['psw'][25];
                    $aTemp2[16] = $v['psw'][26];
                    $aTemp2[17] = $v['psw'][27];
                    #半场亚盘滚球
                    $aTemp2[18] = $v['psw'][30];
                    $aTemp2[19] = changeExp($v['psw'][31]);
                    $aTemp2[20] = $v['psw'][32];
                    #半场大小滚球
                    $aTemp2[21] = $v['psw'][35];
                    $aTemp2[22] = changeExp($v['psw'][36]);
                    $aTemp2[23] = $v['psw'][37];
                    #半场欧盘滚球
                    $aTemp2[24] = $v['psw'][40];
                    $aTemp2[25] = $v['psw'][41];
                    $aTemp2[26] = $v['psw'][42];
                    if(empty($aGameId))
                    {
                        $pData[$k] = $aTemp2;
                    }
                    else
                    {
                        $k = (string) $k;
                        if(array_search($k, $aGameId) !== false)
                        {
                            $pData[$k] = $aTemp2;
                        }
                    }
                }
                $rData[] = array('fsw'=>$fData,'psw'=>$pData);
            }
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
     *  赔率数据
     * @return array 最新赔率数据
     */
    public function getOddsRoll($gameId,$type = '1')
    {
        $item = $this->data['OddsDataDiv'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;
        $aOdds = [];

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $dd = new \Common\Services\DisposeService();
            $aOdds = $dd->oddsDataDiv($content);
        }

        $aGameId = [];
        if(!empty($gameId)) $aGameId = explode(',',$gameId);

        $GameFbinfo = M('GameFbinfo');

        $y = date('Y',time());
        $m = date('m',time());
        $d = date('d',time());
        $time1 = mktime ( 10 ,  32 , 00 ,  $m ,  $d ,  $y );
        $where = "(";
        if($time1 < time())
            $where .= "(show_date = ".(int) date('Ymd',time())." AND game_state in(1,2,3,4))";
        else
            $where .= "(show_date = ".(int) date('Ymd',strtotime('-1 day',time()))." AND game_state in(1,2,3,4))";
        $where .= "  OR (game_state IN ('1','2','3','4') AND show_date in (".(int) date('Ymd',strtotime('-1 day',time())).",".(int) date('Ymd',strtotime('+1 day',time())).",".(int) date('Ymd',time())."))";
        $where .= ")";
        $where .= " AND qc_game_fbinfo.status = 1 AND is_go=1";
        if(!empty($subId)) $where .= " AND is_sub in(".$subId.")";
        if(!empty($unionId)) $where .= " AND qc_game_fbinfo.union_id in(".$unionId.")";

        $baseRes = $GameFbinfo->field('game_id,game_state')->where($where)->order('game_state desc,game_date,game_time')->select();

        $idArr = [];
        if(!empty($baseRes))
        {
            foreach($baseRes as $k=>$v)
            {
                $idArr[$v['game_id']] = $v['game_state'];
            }
        }
        else
        {
            return null;
        }

        $rData = [];
        $fData = [];
        $pData = [];
        if(!empty($aOdds))
        {
            foreach($aOdds as $k =>$v)
            {
                if(!isset($idArr[$k])) continue;
                #全场赔率
                $aTemp = [];
                #全场亚盘初盘
                $fsw_exp_asian = explode(',',$v);
                #全场亚盘滚球
                $aTemp[0] = $v['fsw'][30];
                $aTemp[1] = changeExp($v['fsw'][31]);
                $aTemp[2] = $v['fsw'][32];
                #全场大小滚球
                $aTemp[3] = $v['fsw'][35];
                $aTemp[4] = changeExp($v['fsw'][36]);
                $aTemp[5] = $v['fsw'][37];
                #全场欧盘滚球
                $aTemp[6] = $v['fsw'][40];
                $aTemp[7] = $v['fsw'][41];
                $aTemp[8] = $v['fsw'][42];
                if(empty($aGameId))
                {
                    $fData[$k] = $aTemp;
                }
                else
                {
                    $k = (string) $k;
                    if(array_search($k, $aGameId) !== false)
                    {
                        $fData[$k] = $aTemp;
                    }
                }
                #半场赔率
                $aTemp2 = [];
                if($idArr[$k] != 1) continue;
                #半场亚盘滚球
                $aTemp2[0] = $v['psw'][30];
                $aTemp2[1] = changeExp($v['psw'][31]);
                $aTemp2[2] = $v['psw'][32];
                #半场大小滚球
                $aTemp2[3] = $v['psw'][35];
                $aTemp2[4] = changeExp($v['psw'][36]);
                $aTemp2[5] = $v['psw'][37];
                #半场欧盘滚球
                $aTemp2[6] = $v['psw'][40];
                $aTemp2[7] = $v['psw'][41];
                $aTemp2[8] = $v['psw'][42];
                if(empty($aGameId))
                {
                    $pData[$k] = $aTemp2;
                }
                else
                {
                    $k = (string) $k;
                    if(array_search($k, $aGameId) !== false)
                    {
                        $pData[$k] = $aTemp2;
                    }
                }
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
     *  赔率数据
     * @return array 最新赔率数据
     */
    public function getOddsXml($gameId)
    {
        $item = $this->data['OddsDataDiv'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;
        $aOdds = [];

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
           // $content = str_replace('^', '', $content);
            $dd = new \Common\Services\DisposeService();
            $aOdds = $dd->oddsDataDiv($content);
        }

        $aGameId = [];
        if(!empty($gameId))
        {
            $aGameId = explode(',',$gameId);
        }

        $rData = [];
        if(!empty($aOdds))
        {
            foreach($aOdds as $k =>$v)
            {
                #全场亚盘初盘
                $fsw_exp_asian = explode(',',$v);
                $aTemp['fsw_exp_asian_home'] = $v['fsw'][0];
                $aTemp['fsw_exp_asian'] = changeExp($v['fsw'][1]);
                $aTemp['fsw_exp_asian_away'] = $v['fsw'][2];
                #全场大小初盘
                $aTemp['fsw_ball_home'] =$v['fsw'][5];
                $aTemp['fsw_ball'] = changeExp($v['fsw'][6]);
                $aTemp['fsw_ball_awqy'] = $v['fsw'][7];
                #全场欧盘初盘
                $aTemp['fsw_exp_europe_home'] = $v['fsw'][10];
                $aTemp['fsw_exp_europe'] = $v['fsw'][11];
                $aTemp['fsw_exp_europe_away'] = $v['fsw'][12];
                #全场亚盘即时
                $aTemp['fsw_instant_asian_home'] = $v['fsw'][15];
                $aTemp['fsw_instant_asian'] = changeExp($v['fsw'][16]);
                $aTemp['fsw_instant_asian_away'] = $v['fsw'][17];
                #全场大小即时
                $aTemp['fsw_instant_ball_home'] = $v['fsw'][20];
                $aTemp['fsw_instant_ball'] = changeExp($v['fsw'][21]);
                $aTemp['fsw_instant_ball_away'] = $v['fsw'][22];
                #全场欧盘即时
                $aTemp['fsw_exp_europe_home'] = $v['fsw'][25];
                $aTemp['fsw_exp_europe'] = $v['fsw'][26];
                $aTemp['fsw_exp_europe_away'] = $v['fsw'][27];
                #全场亚盘滚球
                $aTemp['fsw_roll_asian_home'] = $v['fsw'][30];
                $aTemp['fsw_roll_asian'] = changeExp($v['fsw'][31]);
                $aTemp['fsw_roll_asian_away'] = $v['fsw'][32];
                #全场大小滚球
                $aTemp['fsw_roll_ball_home'] = $v['fsw'][35];
                $aTemp['fsw_roll_ball'] = changeExp($v['fsw'][36]);
                $aTemp['fsw_roll_ball_away'] = $v['fsw'][37];
                #全场欧盘滚球
                $aTemp['fsw_roll_europe_home'] = $v['fsw'][40];
                $aTemp['fsw_roll_europe'] = $v['fsw'][41];
                $aTemp['fsw_roll_europe_away'] = $v['fsw'][42];

                #半场亚盘初盘
                $aTemp['psw_exp_asian_home'] = $v['psw'][0];
                $aTemp['psw_exp_asian'] = changeExp($v['psw'][1]);
                $aTemp['psw_exp_asian_away'] = $v['psw'][2];
                #半场大小初盘
                $aTemp['psw_ball_home'] = $v['psw'][5];
                $aTemp['psw_ball'] =changeExp($v['psw'][6]);
                $aTemp['psw_ball_awqy'] = $v['psw'][7];
                #半场欧盘初盘
                $aTemp['psw_exp_europe_home'] = $v['psw'][10];
                $aTemp['psw_exp_europe'] = $v['psw'][11];
                $aTemp['psw_exp_europe_away'] = $v['psw'][12];
                #半场亚盘即时
                $aTemp['psw_instant_asian_home'] = $v['psw'][15];
                $aTemp['psw_instant_asian'] = changeExp($v['psw'][16]);
                $aTemp['psw_instant_asian_away'] = $v['psw'][17];
                #半场大小即时
                $aTemp['psw_instant_ball_home'] = $v['psw'][20];
                $aTemp['psw_instant_ball'] = changeExp($v['psw'][21]);
                $aTemp['psw_instant_ball_away'] = $v['psw'][22];
                #半场欧盘即时
                $aTemp['psw_exp_europe_home'] = $v['psw'][25];
                $aTemp['psw_exp_europe'] = $v['psw'][26];
                $aTemp['psw_exp_europe_away'] = $v['psw'][27];
                #半场亚盘滚球
                $aTemp['psw_roll_asian_home'] = $v['psw'][30];
                $aTemp['psw_roll_asian'] = changeExp($v['psw'][31]);
                $aTemp['psw_roll_asian_away'] = $v['psw'][32];
                #半场大小滚球
                $aTemp['psw_roll_ball_home'] = $v['psw'][35];
                $aTemp['psw_roll_ball'] = changeExp($v['psw'][36]);
                $aTemp['psw_roll_ball_away'] = $v['psw'][37];
                #半场欧盘滚球
                $aTemp['psw_roll_europe_home'] = $v['psw'][40];
                $aTemp['psw_roll_europe'] = $v['psw'][41];
                $aTemp['psw_roll_europe_away'] = $v['psw'][42];

                if(empty($aGameId))
                {
                    $rData[$k] = $aTemp;
                }
                else
                {
                    $k = (string) $k;
                    if(array_search($k, $aGameId) !== false)
                    {
                        $rData[$k] = $aTemp;
                    }
                }
            }
        }
        return $rData;
    }

    /**
     *  赔率数据(即时、滚球)
     * @return array 最新赔率数据
     */
    public function getOddsDynamic($gameId)
    {
        $item = $this->data['OddsDataDiv'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;
        $aOdds = [];

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $content = str_replace('^', '', $content);
            $dd = new \Common\Services\DisposeService();
            $aOdds = $dd->oddsDataDiv($content);
        }

        $aGameId = [];
        if(!empty($gameId))
        {
            $aGameId = explode(',',$gameId);
        }

        $rData = [];
        if(!empty($aOdds))
        {

            foreach($aOdds as $k =>$v)
            {
                #全场亚盘即时
                $aTemp['fsw_instant_asian_home'] = $v['fsw'][12];
                $aTemp['fsw_instant_asian'] = $v['fsw'][13];
                $aTemp['fsw_instant_asian_away'] = $v['fsw'][14];
                #全场大小即时
                $aTemp['fsw_instant_ball_home'] = $v['fsw'][16];
                $aTemp['fsw_instant_ball'] = $v['fsw'][17];
                $aTemp['fsw_instant_ball_away'] = $v['fsw'][18];
                #全场欧盘即时
                $aTemp['fsw_exp_europe_home'] = $v['fsw'][20];
                $aTemp['fsw_exp_europe'] = $v['fsw'][21];
                $aTemp['fsw_exp_europe_away'] = $v['fsw'][22];
                #全场亚盘滚球
                $aTemp['fsw_roll_asian_home'] = $v['fsw'][24];
                $aTemp['fsw_roll_asian'] = $v['fsw'][25];
                $aTemp['fsw_roll_asian_away'] = $v['fsw'][26];
                #全场大小滚球
                $aTemp['fsw_roll_ball_home'] = $v['fsw'][28];
                $aTemp['fsw_roll_ball'] = $v['fsw'][29];
                $aTemp['fsw_roll_ball_away'] = $v['fsw'][30];
                #全场欧盘滚球
                $aTemp['fsw_roll_europe_home'] = $v['fsw'][32];
                $aTemp['fsw_roll_europe'] = $v['fsw'][33];
                $aTemp['fsw_roll_europe_away'] = $v['fsw'][34];

                #半场亚盘即时
                $aTemp['psw_instant_asian_home'] = $v['psw'][12];
                $aTemp['psw_instant_asian'] = $v['psw'][13];
                $aTemp['psw_instant_asian_away'] = $v['psw'][14];
                #半场大小即时
                $aTemp['psw_instant_ball_home'] = $v['psw'][16];
                $aTemp['psw_instant_ball'] = $v['psw'][17];
                $aTemp['psw_instant_ball_away'] = $v['psw'][18];
                #半场欧盘即时
                $aTemp['psw_exp_europe_home'] = $v['psw'][20];
                $aTemp['psw_exp_europe'] = $v['psw'][21];
                $aTemp['psw_exp_europe_away'] = $v['psw'][22];
                #半场亚盘滚球
                $aTemp['psw_roll_asian_home'] = $v['psw'][24];
                $aTemp['psw_roll_asian'] = $v['psw'][25];
                $aTemp['psw_roll_asian_away'] = $v['psw'][26];
                #半场大小滚球
                $aTemp['psw_roll_ball_home'] = $v['psw'][28];
                $aTemp['psw_roll_ball'] = $v['psw'][29];
                $aTemp['psw_roll_ball_away'] = $v['psw'][30];
                #半场欧盘滚球
                $aTemp['psw_roll_europe_home'] = $v['psw'][32];
                $aTemp['psw_roll_europe'] = $v['psw'][33];
                $aTemp['psw_roll_europe_away'] = $v['psw'][34];

                if(empty($aGameId))
                {
                    $rData[$k] = $aTemp;
                }
                else
                {
                    $k = (string) $k;
                    if(array_search($k, $aGameId) !== false)
                    {
                        $rData[$k] = $aTemp;
                    }
                }
            }
        }
        return $rData;
    }

     /**
     *  赔率数据(初盘)
     * @return array 最新赔率数据
     */
    public function getOddsStatic($gameId)
    {
        $item = $this->data['OddsDataDiv'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;
        $aOdds = [];

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $content = str_replace('^', '', $content);
            $dd = new \Common\Services\DisposeService();
            $aOdds = $dd->oddsDataDiv($content);
        }

        $aGameId = [];
        if(!empty($gameId))
        {
            $aGameId = explode(',',$gameId);
        }

        $rData = [];
        if(!empty($aOdds))
        {
            foreach($aOdds as $k =>$v)
            {
                 #全场亚盘初盘
                $aTemp['fsw_exp_asian_home'] = $v['fsw'][0];
                $aTemp['fsw_exp_asian'] = $v['fsw'][1];
                $aTemp['fsw_exp_asian_away'] = $v['fsw'][2];
                #全场大小初盘
                $aTemp['fsw_ball_home'] =$v['fsw'][4];
                $aTemp['fsw_ball'] = $v['fsw'][5];
                $aTemp['fsw_ball_awqy'] = $v['fsw'][6];
                #全场欧盘初盘
                $aTemp['fsw_exp_europe_home'] = $v['fsw'][8];
                $aTemp['fsw_exp_europe'] = $v['fsw'][9];
                $aTemp['fsw_exp_europe_away'] = $v['fsw'][10];

                #半场亚盘初盘
                $aTemp['psw_exp_asian_home'] = $v['psw'][0];
                $aTemp['psw_exp_asian'] = $v['psw'][1];
                $aTemp['psw_exp_asian_away'] = $v['psw'][2];
                #半场大小初盘
                $aTemp['psw_ball_home'] = $v['psw'][4];
                $aTemp['psw_ball'] =$v['psw'][5];
                $aTemp['psw_ball_awqy'] = $v['psw'][6];
                #半场欧盘初盘
                $aTemp['psw_exp_europe_home'] = $v['psw'][8];
                $aTemp['psw_exp_europe'] = $v['psw'][9];
                $aTemp['psw_exp_europe_away'] = $v['psw'][10];

                if(empty($aGameId))
                {
                    $rData[$k] = $aTemp;
                }
                else
                {
                    $k = (string) $k;
                    if(array_search($k, $aGameId) !== false)
                    {
                        $rData[$k] = $aTemp;
                    }
                }
            }
        }
        $res['data'] = $rData;
        return $res;
    }

    /**
     * 赛事事件变化数据
     * @return array
     */
    public function getDetail()
    {
        $item = $this->data['detail_js'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$item['name'].$ext;

        $aDetail = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $aDetail = $disposeData->detail($content);
        }
        $res['data'] = $aDetail;
        return $res;
    }


    /**
     * 赛事事件变化数据(app)
     * @return array
     */
    public function getDetailApp($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $aRes = $this->getDetail();
        $aData =$aRes['data'];
        if(!isset($aData[$gameId]['rq'])) return null;

        $rData = [];
        $rData = $aData[$gameId]['rq'];
        return $rData;
    }
    /**
     * 赛事事件变化数据(app数据库)
     * @return array
     */
    public function getDetailAppB($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;
	    $mongodb = mongoService();
        $fb_detail = $mongodb->select('fb_detail',['game_id'=> $gameId],['game_id', 'detail'])[0];
		$game_id = (string) $fb_detail['game_id'];
		$res= $fb_detail['detail'];
        $rData = [];
        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
            	// mongo 结构 [0] 事件队伍 [1] 技术类别 [2] 比赛事件  [4]  球员简体名 [5] 球员id [6] 球员繁体名
                $temp = [
                    0 => $game_id,
                    1 => $v[0],
                    2 => $v[1],
                    3 => $v[2],
                    4 => $v[3] == null? '':$v[3] ,
                    5 => $v[4] == null? '':$v[4] ,
                    6 => $v[5] == null? '':$v[5]
                ];
                $rData[] = $temp;
            }
        }
        return $rData;
    }

    /**
     * 赛事事件变化数据(app)
     * @return array
     */
    public function getSkillApp($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $aRes = $this->getDetail();
        $aData =$aRes['data'];

        if(!isset($aData[$gameId]['tc'])) return null;

        $rData = [];
        $data = $aData[$gameId]['tc'];
        $arr = explode(';',$data);
        $numArr = array(14,3,4,8,19,6,9,5,11,13);
        foreach($arr as $k=>$v)
        {
            $temp = explode(',',$v);
            if(array_search($temp[0],$numArr) !== false)
            {
                $rData[] = $temp;
            }
        }
        return $rData;
    }

     /**
     * 赛事事件变化数据(app 数据库)
     * @return array
     */
    public function getSkillAppB($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $res = M()->query('select * from qc_statistics_fb where game_id='.$gameId);
        $rData = [];
        $numArr = array(14,3,4,8,19,6,9,5,11,13);

        if(!empty($res))
        {
            foreach($res as $k=>$v)
            {
                if(array_search($v['s_type'],$numArr) !== false)
                {
                    $temp = [
                        0 => $v['s_type'] ,
                        1 => $v['home_value'] == null? '':$v['home_value'] ,
                        2 => $v['away_value'] == null? '':$v['away_value']
                    ];
                    $rData[] = $temp;
                }
            }
        }
        return $rData;
    }

    /**
     * 赛事球队阵容(app)
     * @param  int $gameId 赛事ID
     * @return array
     */
    public function getLineup($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;
	    $mongodb = mongoService();
	    $baseRes = $mongodb->select('fb_game',['game_id'=> $gameId], ['apk_lineup'])[0]['apk_lineup'];
	    $data = [];
	    $homeData = [];
	    $awayData = [];
	    // mongo 数据问题 如果主队首发第一位的球号不存在
	    if (!empty($baseRes[0][0][0])) {
		    $defaultHomeImg = staticDomain('/Public/Home/images/common/fb_ht.png');
		    $defaultAwayImg = staticDomain('/Public/Home/images/common/fb_at.png');
		    $homeStart = $this->lineup($baseRes[0], $defaultHomeImg, TRUE);
		    $homeSub = $this->lineup($baseRes[1], $defaultHomeImg, FALSE);
		    $awayStart = $this->lineup($baseRes[2], $defaultAwayImg, TRUE);
		    $awaySub = $this->lineup($baseRes[3], $defaultAwayImg, FALSE);
		    $homeData = array_merge($homeStart, $homeSub);
		    $awayData = array_merge($awayStart, $awaySub);
		    $data = array(0=>$homeData,1=>$awayData);
	    } else {
		    $data = array(0=>$homeData,1=>$awayData);
	    }
	    return $data;
        
        /*
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
            $defaultHomeImg = staticDomain('/Public/Home/images/common/fb_ht.png');
            $defaultAwayImg = staticDomain('/Public/Home/images/common/fb_at.png');

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
                        4 => $v['player_type'] != 0?$v['player_type']:'',        //球员位置
                        5 => $tidArr['home_team_id'] == $v['team_id']?$defaultHomeImg:$defaultAwayImg,                       //球员球衣图片地址
                    ];
                }
                else
                {
                    $temp = [
                        0 => '',          //球员ID
                        1 => $v['pname'],        //球员名字
                        2 => $v['pno'],      //球员号码
                        3 => $v['is_first'],           //是否首发
                        4 => $v['player_type'] != 0?$v['player_type']:'',        //球员位置
                        5 => $tidArr['home_team_id'] == $v['team_id']?$defaultHomeImg:$defaultAwayImg,                       //球员球衣图片地址
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

        $rData = array(0=>$homeArr,1=>$awayArr);
        return $rData;
        */
    }

    /**
     * 赛事数据分析统计数据
     * @param  int $gameId 赛事ID
     * @return array
     */
    public function getAnalysis($gameId,$key ='yes')
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $item = $this->data['analysis'];
        $ext = $this->getFileExt($item['mimeType']);

        $GameFbinfo = D('GameFbinfo');
        $where['game_id'] = $gameId;
        $baseRes = $GameFbinfo->field('id,show_date')->where($where)->select();
        if(!empty($baseRes))
            $date = substr($baseRes[0]['show_date'],0,4);
        else
            return null;

        $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;

        $res = [];
        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            if($key == 'yes')
                $aData = $disposeData->analysisApp($content);
            else
                $aData = $disposeData->analysisAppNokey($content);

            if($aData !== false)  $res = $aData;
        }
        return $res;
    }

    /**
     * 亚赔数据（让球初盘、即时赔率数据）
     * @param  int $gameId 赛事ID
     * @return array       赔率
     */
    public function getAsianOdds($gameId,$key = 'no')
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $GameFbinfo = D('GameFbinfo');
        $where['game_id'] = $gameId;
        $baseRes = $GameFbinfo->field('id,show_date')->where($where)->select();
        if(!empty($baseRes))
            $date = substr($baseRes[0]['show_date'],0,4);
        else
            return null;

        $aData = [];
        $cData = [];
        $score = C('score_cn');
        //var_dump($score);exit;

        $item = $this->data['match'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $cData = $disposeData->asianOdds($content);
            if(!empty($cData))
            {
                if($key == 'yes')
                {
                    foreach($cData as $k =>$v)
                    {
                        if(empty($v[1])) continue;
                        $aTemp = [];
                        $aTemp['company_name'] = $v[0];
                        #全场亚盘初盘
                        $aTemp['fsw_exp_asian_home'] = $v[1];
                        $aTemp['fsw_exp_asian'] = $v[2];
                        $aTemp['fsw_exp_asian_away'] = $v[3];
                        #全场亚盘即时
                        $aTemp['fsw_instant_asian_home'] = $v[4];
                        $aTemp['fsw_instant_asian'] = $v[5];
                        $aTemp['fsw_instant_asian_away'] = $v[6];
                        $aData[] = $aTemp;
                    }
                }
                else
                {
                    $oddsCompany = array_flip(C('AOB_COMPANY_ID'));
                    $impArr = [];
                    foreach($cData as $k =>$v)
                    {
                        if(empty($v[1])) continue;
                        $aTemp = [];
                        $aTemp[0] = $v[0];   //公司名称
                        #全场亚盘初盘
                        $aTemp[1] = $v[1];   //主
                        if(preg_match('/受/i',$v[2],$data))
                        {
                            $str = str_replace('受','',$v[2]);
                            if(isset($score[$str]))
                                $aTemp[2] = '-'.$score[$str];
                            else
                                $aTemp[2] = '';
                        }
                        else
                        {
                            if(isset($score[$v[2]]))
                                $aTemp[2] = $score[$v[2]];
                            else
                                $aTemp[2] = '';
                        }
                        $aTemp[3] = $v[3];   //客
                        #全场亚盘即时
                        $aTemp[4] = $v[4];   //主
                        if(preg_match('/受/i',$v[5],$data))
                        {
                            $str = str_replace('受','',$v[5]);
                            if(isset($score[$str]))
                                $aTemp[5] = '-'.$score[$str];
                            else
                                $aTemp[5] = '';
                        }
                        else
                        {
                            //盘口
                            if(isset($score[$v[5]]))
                                $aTemp[5] = $score[$v[5]];
                            else
                                $aTemp[5] = '';
                        }
                        $aTemp[6] = $v[6];   //客
                        $aTemp[7] = isset($oddsCompany[$v[0]])?(string)$oddsCompany[$v[0]]:'';
                        if($v[0] == 'ＳＢ' || $v[0] == 'Bet365' || $v[0] == '澳彩')
                        {
                            $impArr[$v[0]] = $aTemp;
                        }
                        else
                        {
                            $aData[] = $aTemp;
                        }

                        //$aData[] = $aTemp;
                    }
                    if(isset($impArr['澳彩'])) array_unshift($aData,$impArr['澳彩']);
                    if(isset($impArr['Bet365'])) array_unshift($aData,$impArr['Bet365']);
                    if(isset($impArr['ＳＢ'])) array_unshift($aData,$impArr['ＳＢ']);
                }
            }
        }
        return $aData;
    }

     /**
     * 大小数据（大小初盘、即时赔率数据）
     * @param  int $gameId 赛事ID
     * @return array       赔率
     */
    public function getBallOdds($gameId,$key ='yes')
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $item = $this->data['goals'];
        $ext = getFileExt($item['mimeType']);

        $GameFbinfo = D('GameFbinfo');
        $where['game_id'] = $gameId;
        $baseRes = $GameFbinfo->field('id,show_date')->where($where)->select();
        if(!empty($baseRes))
            $date = substr($baseRes[0]['show_date'],0,4);
        else
            return null;

        $aData = [];
        $cData = [];
        $score_cn = C('score');

        $item = $this->data['match'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $cData = $disposeData->asianOdds($content);

            if(!empty($cData))
            {
                if($key == 'yes')
                {
                    foreach($cData as $k =>$v)
                    {
                        if(empty($v[13])) continue;
                        $aTemp = [];
                        $aTemp['company_name'] = $v[0];
                        #全场大小初盘
                        $aTemp['fsw_ball_asian_home'] = $v[13];
                        $aTemp['fsw_ball_asian'] = $v[14];
                        $aTemp['fsw_ball_asian_away'] = $v[15];
                        #全场大小即时盘
                        $aTemp['fsw_instant_ball_home'] = $v[16];
                        $aTemp['fsw_instant_ball'] = $v[17];
                        $aTemp['fsw_instant_ball_away'] = $v[18];
                        $aData[] = $aTemp;
                    }
                }
                else
                {
                    $oddsCompany = array_flip(C('AOB_COMPANY_ID'));
                    $impArr = [];
                    foreach($cData as $k =>$v)
                    {
                        if(empty($v[1])) continue;
                        $aTemp = [];
                        $aTemp[0] = $v[0];    //公司名称
                        #全场大小初盘
                        $aTemp[1] = $v[13];   //主
                        $aTemp[2] = $v[14];   //盘口
                        $aTemp[3] = $v[15];   //客
                        #全场大小即时盘
                        $aTemp[4] = $v[16];   //主
                        $aTemp[5] = $v[17];   //盘口
                        $aTemp[6] = $v[18];   //客
                        $aTemp[7] = isset($oddsCompany[$v[0]])?(string)$oddsCompany[$v[0]]:'';
                        if($v[0] == 'ＳＢ' || $v[0] == 'Bet365' || $v[0] == '澳彩')
                            $impArr[$v[0]] = $aTemp;
                        else
                            $aData[] = $aTemp;

                    }
                    if(isset($impArr['澳彩'])) array_unshift($aData,$impArr['澳彩']);
                    if(isset($impArr['Bet365'])) array_unshift($aData,$impArr['Bet365']);
                    if(isset($impArr['ＳＢ'])) array_unshift($aData,$impArr['ＳＢ']);
                }
            }

        }
        return $aData;
    }

    /**
     * 欧赔数据（各公司让球初盘、即时赔率数据）
     * @return array 赔率数据
     */
    public function getEuropeOdds($gameId,$key='no')
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;

        $GameFbinfo = D('GameFbinfo');
        $where['game_id'] = $gameId;
        $baseRes = $GameFbinfo->field('id,gtime')->where($where)->find();
        if(!empty($baseRes))
            $date = date('Y',$baseRes['gtime']);
        else
            return null;

        $aData = [];
        $cData = [];
        $item = $this->data['1x2'];
        $ext = getFileExt($item['mimeType']);
        $fileName = DataPath.$item['savePath'].$date.'/'.$gameId.$ext;

        if(is_file($fileName))
        {
            $content = file_get_contents($fileName);
            $disposeData = new \Common\Services\DisposeService();
            $cData = $disposeData->europeOdds($content);

            if(!empty($cData))
            {
                if($key == 'yes')
                {
                    foreach($cData as $k =>$v)
                    {
                        if($k>49) break;
                        $aTemp = [];
                        $aTemp['company_name'] = $v[2];
                        $aTemp['company_name_cn'] = $v[21];
                        #全场欧赔初盘
                        $aTemp['fsw_exp_europe_home'] = $v[3];
                        $aTemp['fsw_exp_europe'] = $v[4];
                        $aTemp['fsw_exp_europe_away'] = $v[5];
                        #全场欧赔即时盘
                        $aTemp['fsw_instant_europe_home'] = $v[10];
                        $aTemp['fsw_instant_europe'] = $v[11];
                        $aTemp['fsw_instant_europe_away'] = $v[12];
                        $aData[] = $aTemp;
                    }
                }
                else
                {
                    $oddsCompany = array_flip(C('AOB_COMPANY_ID'));
                    $impArr = [];
                    foreach($cData as $k =>$v)
                    {
                        //if(!isset($oddsCompany[$v[21]])) continue;    //过滤欧赔无历史赔率公司
                        if($k>49) break;
                        $aTemp = [];
                        $aTemp[0] = $v[2];         //公司名
                        $cName = preg_replace("/\((.*?)\)/i","",$v[21]);   //过滤括号里的国家等字
                        $cName = strtolower($cName);
                        $aTemp[1] = $cName;     //公司中文名
                        #全场欧赔初盘
                        $aTemp[2] = $v[3];   //主
                        $aTemp[3] = $v[4];        //盘口
                        $aTemp[4] = $v[5];   //客
                        #全场欧赔即时盘
                        $aTemp[5] = $v[10];  //主
                        $aTemp[6] = $v[11];   //盘口
                        $aTemp[7] = $v[12];  //客
                        if($v[21] == 'SB')
                            $aTemp[8] = '3';
                        else
                            $aTemp[8] = isset($oddsCompany[$v[21]])?(string)$oddsCompany[$v[21]]:'';
                        if($v[2] == 'Crown' || $v[2] == 'Bet 365' || $v[2] == 'Macauslot')
                            $impArr[$v[2]] = $aTemp;
                        else
                            $aData[] = $aTemp;
                    }
                    if(isset($impArr['Macauslot'])) array_unshift($aData,$impArr['Macauslot']);
                    if(isset($impArr['Bet 365'])) array_unshift($aData,$impArr['Bet 365']);
                    if(isset($impArr['Crown'])) array_unshift($aData,$impArr['Crown']);
                }
            }
        }
        return $aData;
    }

    /**
     * 某赛事各公司亚洲即时指数
     * @param  int $gameId 赛事ID
     * @return array       各公司亚洲即时指数
     */
    public function getAsianInstantOdds($gameId)
    {
        $gameId = (int) $gameId;
        if(empty($gameId)) return false;
        $oddsCompany = C('DB_COMPANY_INFO');
        $aData = [];
        foreach($oddsCompany as $k=>$v)
        {
           $res = $this->getGoalsXml($k);
           if(!empty($res) && isset($res[$gameId]))
           {
                $aData[$k]['fsw_instant_europe_home'] = $res[$gameId]['fsw_instant_europe_home'];
                $aData[$k]['fsw_instant_europe'] = $res[$gameId]['fsw_instant_europe'];
                $aData[$k]['fsw_instant_europe_away'] = $res[$gameId]['fsw_instant_europe_away'];
           }
           else
           {
                $aData[$k] = [];
           }
        }
        return $aData;
    }

    /**
     * 洲数据
     * @return array 洲数据
     */
    public function getContinent()
    {
	    $mService = mongoService();
	    $res = $mService->select('fb_continent', [], ['continent_id', 'continent_name']);
        if(!empty($res))
        {
            $aData = [];
            foreach($res as $k=>$v)
            {
                $temp = array((string) $v['continent_id'],$v['continent_name'][0]);
                $aData[] = $temp;
            }
            return $aData;
        }
        else
        {
            return null;
        }
    }

    /**
     * 国家数据
     * @return array 国家数据
     */
    public function getCountry($continentId,$lang = 1)
    {
	    $filter = [];
	    $mongodb  = mongoService();
	    $filter['country_id'] = [$mongodb->cmd('type') => "int"];
        if($continentId == 0 || $continentId != null)
        {
//            $where['continent_id'] = array('in',$continentId);
	        $filter['continent_id'] = [$mongodb->cmd('in') => [$continentId]];
        }
        $countryRes = $mongodb->select("fb_country", $filter, ['s_name', 't_name', 'country_id']);
	    $unionRes = $mongodb->select("fb_union", ["level" => [$mongodb->cmd("in") => [0,1,2]]], ['level', 'union_id', 'country_id']);
	
	    $mongoRes= filterArray($countryRes, $unionRes, "country_id");
	    $sort = array_column($mongoRes, "country_id");
	    array_multisort($sort, SORT_ASC, $mongoRes);
	    
        if (!empty($mongoRes)) {
            $data = [];
            foreach ($mongoRes as $key => $value) {
            	$temp = [];
	            $temp[0] = (string) $value['country_id'];
            	if ($lang == 1) {
            		$temp[1] = $value['s_name'];
	            } elseif ($lang == 2) {
            		$temp[1] = $value['t_name'];
	            }
	            $data[] = $temp;
            }
            return $data;
        }
        return null;
	    
	    /*
	     mysql 源
        $where['is_sub'] = array('lt',3);
        $country = M('Country');
        $res = $country->field('DISTINCT qc_country.country_id,qc_country.*')->join('LEFT JOIN qc_union ON qc_country.country_id=qc_union.country_id')->where($where)->select();
        //$res = $country->field('country_name,country_id')->where($where)->select();

        if(!empty($res))
        {
            $aData = [];
            foreach($res as $k=>$v)
            {
                $name = explode(',',$v['country_name']);
                $temp = [];
                if($lang == 1)
                {
                    $temp = array($v['country_id'],$name[0]);
                }
                else
                {
                    $temp[0] = $v['country_id'];
                    $temp[1] = !empty($name[1])?$name[1]:$name[0];
                }

                //$temp = array($v['country_id'],$v['country_name']);
                $aData[] = $temp;
            }
            return $aData;
        }
        else
        {
            return null;
        }
        */
    }

    /**
     * 联赛数据
     * @return array 联赛数据
     */
    public function getUnion($unionId,$countryId)
    {
	    /*
	    $mongodb  = mongoService();
    	$unionFilter["level"] = [$mongodb->cmd("in") => [0,1,2]];
	    if(!empty($countryId)){
		    $Ids = explode(',', $countryId);
		    $newIdsc = [];
		    foreach ($Ids as $key => $value) {
		    	$newIdsc[$key] = intval($value);
		    }
		    $unionFilter['country_id'] = [$mongodb->cmd("in") => $newIdsc];
	    }
	    if(!empty($unionId)) {
		    $Ids = explode(',', $unionId);
		    $newIds = [];
		    foreach ($Ids as $key => $value) {
		    	$newIds[$key] = intval($value);
		    }
	    	$unionFilter['union_id'] = [$mongodb->cmd("in") => $newIds];
	    }
	    $unionRes = $mongodb->select("fb_union", $unionFilter,
		    ['level', 'union_id', 'country_id','union_name_long','union_color', 'is_league','season']);
	    $sort = array_column($unionRes, "union_id");
	    array_multisort($sort, SORT_ASC, $unionRes);
	    if (!empty($unionRes)) {
	    	$data = [];
	    	foreach ($unionRes as $key => $value) {
	    	    $temp = [];
	    	    $temp[] = (string) $value['union_id'];
	    	    $temp[] = $value['union_name_long'];
	    	    $temp[] = (string) $value['is_league'];
	    	    $temp[] = (string) $value['level'];
	    	    $temp[] = $value['union_color'];
	    	    $temp[] = $value['season'][0];
	    	    $data[] = $temp;
		    }
		    return $data;
	    }
	    return null;


	    mysql 源
        */
		$where = [];
        $where['is_sub'] = array('in','0,1,2');
        if(!empty($countryId)) $where['country_id'] = array('in',$countryId);
        if(!empty($unionId)) $where['union_id'] = array('in',$unionId);

        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,union_color,year_list')->where($where)->select();

        if(!empty($res))
        {
            $aData = [];
            foreach($res as $k=>$v)
            {
                $temp = [];
                $temp[0] = $v['union_id'];
                $temp[1] = explode(',',$v['union_name']);
                $temp[2] = $v['is_union'];
                $temp[3] = $v['is_sub'];
                $temp[4] = isset($v['union_color'])?$v['union_color']:'';
                if(!empty($v['year_list']))
                {
                    $lTemp = explode(',',$v['year_list']);
                    $temp[5] = $lTemp[0];
                }
                else
                {
                    $temp[5] = '';
                }
                $aData[] = $temp;
            }
            return $aData;
        }
        else
        {
            return null;
        }
    }

    /**
     * 联赛总积分
     * @param  int $unionId 联赛ID
     * @return bool | array
     */
    public function getLeagueIntegral($unionId,$key='yes')
    {
        if(empty($unionId)) return false;
	    $mongodb  = mongoService();
	    $season = $mongodb->select("fb_union", ['union_id' => (int) $unionId], ['season'])[0];
	    $baseRes = $mongodb->select("fb_union", ['union_id' => (int) $unionId],
		    ['statistics.'.$season['season'][0].'.matchResult']
	    )[0];
	    $matchResult = $baseRes['statistics'][$season['season'][0]]['matchResult'];
	    $statistics = $matchResult['total_score'];
	    if ($statistics === null) {
	    	$temp = $matchResult['arrSubLeague'];
		    foreach ($temp as $k => $v) {
		    	if ($v[4] === 1) {
		    		$statistics = $matchResult[$v[0]]['total_score'];
			    }
	    	}
	    }
	    $teamId = array_column($statistics, 2);
	    $statisticsTeam = $mongodb->select("fb_team", ['team_id' => [$mongodb->cmd("in") => $teamId]],['team_id', 'team_name']);
        $newTeam = [];
	    foreach ($statisticsTeam as $k => $v) {
		    $newTeam[$v['team_id']] = $v['team_name'];
        }
        $data = [];
	    if($key == 'yes')
	    {
		    foreach ($statistics as $k => $v) {
			    $temp =[] ;
			    $temp['rank'] = (string) $v[1];
			    $temp['team_name'] = $newTeam[$v[2]][0];
			    $temp['total'] = (string) $v[4];
			    $temp['win'] = (string) $v[5];
			    $temp['draw'] = (string) $v[6];
			    $temp['lose'] = (string) $v[7];
			    $temp['integral'] = (string) $v[16];
			    $temp['updown'] = (string) $v[0];
			    $data[] = $temp;
		    }
	    } else {
	    	foreach ($statistics as $k => $v) {
	    		$temp = [];
			    $temp[] = (string) $v[1];
			    $temp[] = $newTeam[$v[2]][0];
			    $temp[] = (string) $v[4];
			    $temp[] = (string) $v[5];
			    $temp[] = (string) $v[6];
			    $temp[] = (string) $v[7];
			    $temp[] = (string) $v[16];
			    $temp[] = (string) $v[0];
			    $data[] = $temp;
		    }
	    }
        return $data;


        /*
        mysql 源
        $where['union_id'] = $unionId;
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        if($res['is_union'] == 2 || empty($res['year_list'])) return array();

        $temp = explode(',',$res['year_list']);
        $indexPath = DataPath.'football/League/matchSeason='.$temp[0].'&SclassID='.$res['union_id'].'.html';

        if(is_file($indexPath))
        {
            $body = file_get_contents($indexPath);
            if(preg_match_all('/var SubSclassID =(.*?);/i',$body,$data))
                $subId = trim($data[1][0]);
            else
                $subId = 0;
        }
        else
        {
            return null;
        }

        if($subId == 0)
            $name ='s'.$res['union_id'].'.js';
        else
            $name ='s'.$res['union_id'].'_'.$subId.'.js';
        $filePath = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/'.$name;
        if(!is_file($filePath)) return null;
        $content = file_get_contents($filePath);

        #默认只显示联赛（子级）积分，附加赛等不显示
        if(preg_match_all('/var arrSubLeague = \[\[(.*?)\]\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            $stemp = explode('],[',$txt);
            $arrSub = explode(',',$stemp[0]);
            $subId2 = $arrSub[0];

            if($subId2 !== $subId)
            {
                $filePath2 = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/s'.$res['union_id'].'_'.$subId2.'.js';
                $content = file_get_contents($filePath2);
            }
        }

        $disposeData = new \Common\Services\DisposeService();
        $res = $disposeData->matchResultOne($content);

        if($res === false)
        {
            return false;
        }
        else
        {
            $rData = [];
            if($key == 'yes')
            {
                foreach($res['totalScore'] as $k=>$v)
                {
                    $aTemp =[] ;
                    $aTemp['rank'] = $v[1];
                    $aTemp['team_name'] = $res['team_name'][$v[2]][1];
                    $aTemp['total'] = $v[4];
                    $aTemp['win'] = $v[5];
                    $aTemp['draw'] = $v[6];
                    $aTemp['lose'] = $v[7];
                    $aTemp['integral'] = $v[16];
                    $aTemp['updown'] = $v[0];
                    $rData[] = $aTemp;
                }
            }
            else
            {
                foreach($res['totalScore'] as $k=>$v)
                {
                    $aTemp =[] ;
                    $aTemp[0] = $v[1] !== null?$v[1]:'';                       //排名
                    $aTemp[1] = $res['team_name'][$v[2]][1]; //球队名
                    $aTemp[2] = $v[4] !== null?$v[4]:'';                       //共
                    $aTemp[3] = $v[5] !== null?$v[5]:'';                       //胜
                    $aTemp[4] = $v[6] !== null?$v[6]:'';                       //平
                    $aTemp[5] = $v[7] !== null?$v[7]:'';                       //负
                    $aTemp[6] = $v[16] !== null?$v[16]:'';                      //积分
                    $aTemp[7] = $v[0] !== null?$v[0]:'';                      //积分
                    $rData[] = $aTemp;
                }
            }
            return $rData;
        }
        */
    }

    /**
     * 联赛总积分
     * @param  int $unionId 联赛ID
     * @return array          积分数据
     */
    public function getUpdown($unionId)
    {
        if(empty($unionId)) return false;
        $where['union_id'] = $unionId;

	    $mongodb  = mongoService();
	    $season = $mongodb->select("fb_union", ['union_id' => (int) $unionId], ['season'])[0];
	    $baseRes = $mongodb->select("fb_union", ['union_id' => (int) $unionId],
		    ['statistics.'.$season['season'][0].'.matchResult']
	    )[0];
	    $matchResult = $baseRes['statistics'][$season['season'][0]]['matchResult'];
	    $statistics = $matchResult['score_color'];
	    if ($statistics === null) {
		    $temp = $matchResult['arrSubLeague'];
		    foreach ($temp as $k => $v) {
			    if ($v[4] === 1) {
				    $statistics = $matchResult[$v[0]]['score_color'];
			    }
		    }
	    }
	    $data = [];
        foreach ($statistics as $key => $value) {
            $temp = explode('|', $value);
            $data[] = $temp;
        }
        return array('updown' => $data);

        /*
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        if($res['is_union'] == 2 || empty($res['year_list'])) return array();

        $temp = explode(',',$res['year_list']);
        $indexPath = DataPath.'football/League/matchSeason='.$temp[0].'&SclassID='.$res['union_id'].'.html';

        if(is_file($indexPath))
        {
            $body = file_get_contents($indexPath);
            if(preg_match_all('/var SubSclassID =(.*?);/i',$body,$data))
                $subId = trim($data[1][0]);
            else
                $subId = 0;
        }
        else
        {
            return null;
        }

        if($subId == 0)
            $name ='s'.$res['union_id'].'.js';
        else
            $name ='s'.$res['union_id'].'_'.$subId.'.js';
        $filePath = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/'.$name;
        if(!is_file($filePath)) return null;
        $content = file_get_contents($filePath);

        #默认只显示联赛（子级）积分，附加赛等不显示
        if(preg_match_all('/var arrSubLeague = \[\[(.*?)\]\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            $stemp = explode('],[',$txt);
            $arrSub = explode(',',$stemp[0]);
            $subId2 = $arrSub[0];

            if($subId2 !== $subId)
            {
                $filePath2 = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/s'.$res['union_id'].'_'.$subId2.'.js';
                $content = file_get_contents($filePath2);
            }
        }

        $disposeData = new \Common\Services\DisposeService();
        $res = $disposeData->matchResultOne($content);

        if($res === false)
        {
            return false;
        }
        else
        {
            if(!empty($res['updown']))
                $rData['updown'] = $res['updown'];
            else
                $rData = [];
            return $rData;
        }
        */

    }

    /**
     * 联赛总积分
     * @param  int $unionId 联赛ID
     * @param  int $runId   轮次ID
     * @return array          积分数据
     */
    public function getLeagueMatch($unionId, $runId, $key = 'yes')
    {
        if(empty($unionId)) return false;
        $unionId = (int) $unionId;

        $where['union_id'] = $unionId;
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        if($res['is_union'] == 2 || empty($res['year_list'])) return array();
        $is_sub = $res['is_sub'] ;

        $temp = explode(',',$res['year_list']);
        $indexPath = DataPath.'football/League/matchSeason='.$temp[0].'&SclassID='.$res['union_id'].'.html';

        $subId = 0;
        if(is_file($indexPath))
        {
            $body = file_get_contents($indexPath);
            if(preg_match_all('/var SubSclassID =(.*?);/i',$body,$data))  $subId = trim($data[1][0]);
        }
        else
        {
            return null;
        }

        if($subId == 0)
            $name ='s'.$res['union_id'].'.js';
        else
            $name ='s'.$res['union_id'].'_'.$subId.'.js';

        $filePath = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/'.$name;
        if(!is_file($filePath)) return null;
        $content = file_get_contents($filePath);

        #默认只显示联赛（子级）积分，附加赛等不显示
        if(preg_match_all('/var arrSubLeague = \[\[(.*?)\]\];/i',$content,$teamData))
        {
            $txt = str_replace("'",'',$teamData[1][0]);
            $stemp = explode('],[',$txt);
            $arrSub = explode(',',$stemp[0]);
            $subId2 = $arrSub[0];

            if($subId2 !== $subId)
            {
                $filePath2 = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/s'.$res['union_id'].'_'.$subId2.'.js';
                $content = file_get_contents($filePath2);
            }
        }

        $disposeData = new \Common\Services\DisposeService();
        if($key == 'yes')
            $res = $disposeData->matchResultTwoKey($content);
        else
            $res = $disposeData->matchResultTwo($content);

        if($res === false)
        {
            return false;
        }
        else
        {
            if(!empty($runId))
            {
                $aData = [];
                foreach($res['content'] as $k=>$v)
                {
                    if($v['runNo'] == $runId)
                    {
                        $aData = $v;
                        break;
                    }
                }
                return array('run'=>$res['run'],'content'=>$aData);
            }
            else
            {
                $aData = [];
                foreach($res['content'] as $k=>$v)
                {
                    if($v['show'] == 1)
                    {
                        unset($v['show']);
                        $aData = $v;
                        break;
                    }
                }
                return array('run'=>$res['run'],'content'=>$aData);
            }
        }
    }

    /**
     * 资料库联赛让球盘路榜数据
     * @return [type] [description]
     */
    public function getLetGoal($unionId,$key = 'yes')
    {
        if(empty($unionId)) return false;
        $unionFilter['union_id']= (int) $unionId;

	    $mongodb  = mongoService();
	    $season = $mongodb->select("fb_union", $unionFilter, ['season'])[0];
	    $statistics = $mongodb->select("fb_union", $unionFilter,
		    ['statistics.'.$season['season'][0].'.letGoal.total_pan_lou']
	    )[0]['statistics'][$season['season'][0]]['letGoal']['total_pan_lou'];
	    foreach ($statistics as $k => $v) {
	    	$id = $v[1];
	    	$teamName = $mongodb->select("fb_team", ['team_id' => $id], ['team_name']);
	    	$statistics[$k][] = $teamName[0]['team_name'][0];
	    }

        if ($statistics === false) {
        	return false;
        }else {
        	$data = [];
        	if ($key == 'yes') {
        	    foreach ($statistics as $k => $v) {
        	    	$temp = [];
		            $temp['rank'] = (string) $v[0];
		            $temp['team_name'] = (string) $v[13];
		            $temp['total'] = (string) $v[2];
		            $temp['up'] = (string) $v[3];
		            $temp['draw'] = (string) $v[4];
		            $temp['down'] = (string) $v[5];
		            $temp['win'] = (string) $v[6];
		            $temp['walk'] = (string) $v[7];
		            $temp['lose'] = (string) $v[8];
		            $temp['winning_number'] = (string) $v[9];
		            $data[] = $temp;
	            }
	        } else {
		        foreach ($statistics as $k => $v) {
			        $temp =[] ;
			        $temp[0] = (string) $v[0];
			        $temp[1] = (string) $v[13];
			        $temp[2] = (string) $v[2];
			        $temp[3] = (string) $v[3];
			        $temp[4] = (string) $v[4];
			        $temp[5] = (string) $v[5];
			        $temp[6] = (string) $v[6];
			        $temp[7] = (string) $v[7];
			        $temp[8] = (string) $v[8];
			        $temp[9] = (string) $v[9];
			        $data[] = $temp;
		        }
	        }
	        return $data;
        }

        /*
         * // mysql 数据源
        $where['union_id'] = $unionId;
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        if($res['is_union'] == 2 || empty($res['year_list'])) return array();
        $temp = explode(',',$res['year_list']);
        $filePath = DataPath.'football/letGoal/'.$res['union_id'].'/l'.$res['union_id'].'_'.$temp[0].'.js';//echo $filePath;exit;
        if(!is_file($filePath)) return null;

        $content = file_get_contents($filePath);
        $disposeData = new \Common\Services\DisposeService();
        $res = $disposeData->letGoal($content);

        if($res === false)
        {
            return false;
        }
        else
        {
            $rData = [];
            if($key == 'yes')
            {
                foreach($res['totalPanlu'] as $k=>$v)
                {
                    if(!isset($res['team_name'][$v[1]])) continue;
                    $aTemp =[] ;
                    $aTemp['rank'] = $v[0];
                    $aTemp['team_name'] = $res['team_name'][$v[1]][1];
                    $aTemp['total'] = $v[2];
                    $aTemp['up'] = $v[3];
                    $aTemp['draw'] = $v[4];
                    $aTemp['down'] = $v[5];
                    $aTemp['win'] = $v[6];
                    $aTemp['walk'] = $v[7];
                    $aTemp['lose'] = $v[8];
                    $aTemp['winning_number'] = $v[9];
                    $rData[] = $aTemp;
                }
            }
            else
            {
                foreach($res['totalPanlu'] as $k=>$v)
                {
                    if(!isset($res['team_name'][$v[1]])) continue;
                    $aTemp =[] ;
                    $aTemp[0] = $v[0];                        //排名
                    $aTemp[1] = $res['team_name'][$v[1]][1];  //队名
                    $aTemp[2] = $v[2];                        //共
                    $aTemp[3] = $v[3];                        //上
                    $aTemp[4] = $v[4];                        //平
                    $aTemp[5] = $v[5];                        //下
                    $aTemp[6] = $v[6];                        //胜
                    $aTemp[7] = $v[7];                        //走
                    $aTemp[8] = $v[8];                        //失
                    $aTemp[9] = $v[9];                        //尽胜
                    $rData[] = $aTemp;
                }
            }
            return $rData;
        }
        */
    }

    /**
     * 资料库联赛大小盘路榜数据
     * @return [type] [description]
     */
    public function getBigSmall($unionId,$key = 'yes')
    {
        if(empty($unionId)) return false;

	    $unionFilter['union_id']= (int) $unionId;

	    $mongodb  = mongoService();
	    $season = $mongodb->select("fb_union", $unionFilter, ['season'])[0];
	    $statistics = $mongodb->select("fb_union", $unionFilter,
		    ['statistics.'.$season['season'][0].'.bigSmall.TotalBs']
	    )[0]['statistics'][$season['season'][0]]['bigSmall']['TotalBs'];
	    foreach ($statistics as $k => $v) {
		    $id = $v[1];
		    $teamName = $mongodb->select("fb_team", ['team_id' => $id], ['team_name']);
		    $statistics[$k][] = $teamName[0]['team_name'][0];
	    }

	    if ($statistics === false) {
		    return false;
	    }else {
		    $data = [];
		    if ($key == 'yes') {
			    foreach ($statistics as $k => $v) {
				    $temp = [];
				    $temp['rank'] = (string) $v[0];
				    $temp['team_name'] = (string) $v[9];
				    $temp['total'] = (string) $v[2];
				    $temp['big'] = (string) $v[3];
				    $temp['walk'] = (string)$v[4];
				    $temp['small'] = (string) $v[5];
				    $temp['big_rate'] = (string) $v[6];
				    $temp['walk_rate'] = (string) $v[7];
				    $temp['small_rate'] = (string) $v[8];
				    $data[] = $temp;
			    }
		    } else {
			    foreach ($statistics as $k => $v) {
				    $temp =[] ;
				    $temp[0] = (string) $v[0];
				    $temp[1] = (string) $v[9];
				    $temp[2] = (string) $v[2];
				    $temp[3] = (string) $v[3];
				    $temp[4] = (string) $v[4];
				    $temp[5] = (string) $v[5];
				    $temp[6] = (string) $v[6];
				    $temp[7] = (string) $v[7];
				    $temp[8] = (string)$v[8];
				    $data[] = $temp;
			    }
		    }
		    return $data;
	    }



        /*
        mysql 源
        $where['union_id'] = $unionId;
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        if($res['is_union'] == 2 || empty($res['year_list'])) return array();
        $temp = explode(',',$res['year_list']);
        $filePath = DataPath.'football/bigSmall/'.$res['union_id'].'/bs'.$res['union_id'].'_'.$temp[0].'.js';

        if(!is_file($filePath)) return null;

        $content = file_get_contents($filePath);
        $disposeData = new \Common\Services\DisposeService();
        $res = $disposeData->bigSmall($content);
        if($res === false)
        {
            return false;
        }
        else
        {
            $rData = [];
            if($key == 'yes')
            {
                foreach($res['totalBs'] as $k=>$v)
                {
                    if(!isset($res['team_name'][$v[1]])) continue;
                    $aTemp =[] ;
                    $aTemp['rank'] = $v[0];
                    $aTemp['team_name'] = $res['team_name'][$v[1]][1];
                    $aTemp['total'] = $v[2];
                    $aTemp['big'] = $v[3];
                    $aTemp['walk'] = $v[4];
                    $aTemp['small'] = $v[5];
                    $aTemp['big_rate'] = $v[6];
                    $aTemp['walk_rate'] = $v[7];
                    $aTemp['small_rate'] = $v[8];
                    $rData[] = $aTemp;
                }
            }
            else
            {
                foreach($res['totalBs'] as $k=>$v)
                {
                    if(!isset($res['team_name'][$v[1]])) continue;
                    $aTemp =[] ;
                    $aTemp[0] = $v[0];   //排名
                    $aTemp[1] = $res['team_name'][$v[1]][1]; //球队名
                    $aTemp[2] = $v[2];   //共
                    $aTemp[3] = $v[3];   // 大球
                    $aTemp[4] = $v[4];   //走
                    $aTemp[5] = $v[5];  //小球
                    $aTemp[6] = $v[6];   //大球率
                    $aTemp[7] = $v[7];   //走率
                    $aTemp[8] = $v[8];   //小球率
                    $rData[] = $aTemp;
                }
            }

            return $rData;
        }
        */
    }

    /**
     * 资料库联赛射手榜数据
     * @return [type] [description]
     */
    /*public function getArcher($unionId,$key = 'yes')
    {
        if(empty($unionId)) return false;
        $where['union_id'] = $unionId;
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        if($res['is_union'] == 2 || empty($res['year_list'])) return array();
        $temp = explode(',',$res['year_list']);
        $filePath = DataPath.'football/archer/'.$res['union_id'].'/a'.$res['union_id'].'_'.$temp[0].'.html';

        if(!is_file($filePath)) return null;

        $content = file_get_contents($filePath);
        $disposeData = new \Common\Services\DisposeService();
        $res = $disposeData->archer($content);
        if($res === false)
        {
            return false;
        }
        else
        {
            $rData = [];
            if(!empty($res))
            {
                if($key == 'yes')
                {
                    foreach($res['arrTotal'] as $k=>$v)
                    {
                        $aTemp =[] ;
                        $aTemp['rank'] = (string)$v[0];
                        $aTemp['player'] = (string)$v[2];
                        $aTemp['team_name'] = $res['team_name'][$v[8]][1];
                        $aTemp['country'] = (string)$v[5];
                        $aTemp['total'] = (string)$v[9];
                        $aTemp['home_ball'] = (string)$v[10];
                        $aTemp['away_ball'] = (string)$v[11];
                        $rData[] = $aTemp;
                    }
                }
                else
                {
                    foreach($res['arrTotal'] as $k=>$v)
                    {
                        $aTemp =[] ;
                        $aTemp[0] = (string)$v[0];  //排名
                        $aTemp[1] = trim($v[2],"'");  //球员
                        $aTemp[2] = trim($res['team_name'][$v[8]][1],"'"); //球队名
                        $aTemp[3] =trim($v[5],"'");  //国家
                        $aTemp[4] = (string)$v[9];  //共进球
                        $aTemp[5] = (string)$v[10];  //主进球
                        $aTemp[6] = (string)$v[11];  //可进球
                        $rData[] = $aTemp;
                    }
                }

            }
            return $rData;
        }
    }*/
    public function getArcher($unionId,$key = 'yes')
    {
        if(empty($unionId)) return false;
	    $mongodb  = mongoService();
	    $unionFilter['union_id']= (int) $unionId;
	    $season = $mongodb->select("fb_union", $unionFilter, ['season', 'is_league'])[0];
	    $statistics = $mongodb->select("fb_union", $unionFilter,  ['statistics.'.$season['season'][0].'.player_tech', 'statistics.'.$season['season'][0].'.Archer']);
	    $Archer = $statistics[0]['statistics'][$season['season'][0]]['Archer'];
	    $data = $statistics[0]['statistics'][$season['season'][0]]['player_tech'];
	    // 射手数据源　数据统计过于复杂只能这样筛选
	    if ((empty($data['Total']['value']) && !empty($Archer['total_data'])) || !empty($Archer['total_data'])) {
		    $data = $this->ArcherData($Archer['total_data'], 'Archer', $key);
	    }else if ((!empty($data['Total']['value']) && empty($Archer['total_data'])) || !empty($data['Total']['value'])) {
		    $Total = sortToColumn($data['Total']['value'], 40);
		    $temp = columnToArray($Total, $data['Home']['value'], 0, 0, [40]);
		    $dataAllData = columnToArray($temp, $data['guest']['value'], 0, 0, [40]);
		    $pidAndTid = $this->playerTechTidToPid($data['Pid'], $data['Tid']);
		    $withPidData = $this->playerTechPidToPlus($dataAllData, $pidAndTid);
		    $playerSlice= array_column($withPidData, 0);
		    $player_filter = ['player_id' => [$mongodb->cmd('in') => $playerSlice]];
		    $player_data = $mongodb->select('fb_player', $player_filter, ['player_id', 'country']);
		    $player = columnToArray($withPidData, $player_data, 0, 'player_id', ['country']);
		    $data = $this->ArcherData($player, 'player_tech', $key);
	    } else {
		    return [];
	    }
	    return $data;


	    /*
	    mysql 源
        $where['union_id'] = $unionId;
        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();
        //if($res['is_union'] == 2 || empty($res['year_list'])) return array();
        if(empty($res['year_list'])) return array();
        $temp = explode(',',$res['year_list']);
        $filePath = DataPath.'football/archer/'.$res['union_id'].'/a'.$res['union_id'].'_'.$temp[0].'.html';

        if(!is_file($filePath)) return null;

        $content = file_get_contents($filePath);
        //$disposeData = new \Common\Services\DisposeService();

        $rData = [];
        if(empty($content) || !is_string($content))
        {
            return $rData;
        }
        if($res['is_union'] == 1)
        {

            $pidArr = $tidArr = $dArr = [];

            if(preg_match('/\"Pid\":(.*?),\"Tid\"/is',$content,$data))
            {
                $pidArr = json_decode($data[1],true);
            }

            if(preg_match('/\"Tid\":(.*?),\"Total\"/is',$content,$data))
            {
                $tidArr = json_decode($data[1],true);

            }

            if(empty($pidArr) || empty($tidArr)) return $rData;

            $total = $home = $away = $pIds = [];

            if(preg_match_all('/value\":\[(.*?)\]\}/is',$content,$data))
            {
                $tStr = "[".$data[1][0]."]";
                $tArr = json_decode($tStr,true);
                foreach($tArr as $k=>$v)
                {
                    $pIds[] = $v[0];
                }
                if(empty($pIds)) return $rData;

                $hStr = "[".$data[1][1]."]";
                $hArr = json_decode($hStr,true);
                foreach($hArr as $k=>$v)
                {
                    $home[$v[0]] = $v;
                }

                $aStr = "[".$data[1][2]."]";
                $aArr = json_decode($aStr,true);
                foreach($aArr as $k=>$v)
                {
                    $away[$v[0]] = $v;
                }
                $map['player_id'] = ['in',implode(',',$pIds)];
                $pRes = M('playerfb') ->field('player_id,country')->where($map)->select();

                $pData = [];
                foreach($pRes as $k2=>$v2)
                {
                    $temp = explode(',',$v2['country']);
                    $pData[$v2['player_id']] = $temp[0];
                }

                $trData = $aSort = $bSort = [];
                foreach($tArr as $k=>$v)
                {
                    $temp = [
                        0 => (string)$k,
                        1 => $pidArr[$v[0]][0][0],
                        2 => $tidArr[$pidArr[$v[0]][1]][0],
                        3 => isset($pData[$v[0]])?$pData[$v[0]]:'',
                        4 => (string)$v[40],
                        5 => isset($home[$v[0]])?(string)$home[$v[0]][40]:'',
                        6 => isset($away[$v[0]])?(string)$away[$v[0]][40]:'',
                    ];
                    $trData[] = $temp;
                    $aSort[] = $temp[4];
                    $bSort[] = $k;
                }
                array_multisort($aSort, SORT_DESC, $bSort, SORT_ASC, $trData);
                foreach($trData as $k=>$v)
                {
                    $temp = $v;
                    $temp[0] = (string)($k+1);
                    $rData[] = $temp;
                }

            }
        }
        else
        {
            $disposeData = new \Common\Services\DisposeService();
            $res = $disposeData->archer($content);
            if($res === false) return false;

            if(!empty($res))
            {
                if($key == 'yes')
                {
                    foreach($res['arrTotal'] as $k=>$v)
                    {
                        $aTemp =[] ;
                        $aTemp['rank'] = (string)$v[0];
                        $aTemp['player'] = (string)$v[2];
                        $aTemp['team_name'] = $res['team_name'][$v[8]][1];
                        $aTemp['country'] = (string)$v[5];
                        $aTemp['total'] = (string)$v[9];
                        $aTemp['home_ball'] = (string)$v[10];
                        $aTemp['away_ball'] = (string)$v[11];
                        $rData[] = $aTemp;
                    }
                }
                else
                {
                    foreach($res['arrTotal'] as $k=>$v)
                    {
                        $aTemp =[] ;
                        $aTemp[0] = (string)$v[0];  //排名
                        $aTemp[1] = trim($v[2],"'");  //球员
                        $aTemp[2] = trim($res['team_name'][$v[8]][1],"'"); //球队名
                        $aTemp[3] =trim($v[5],"'");  //国家
                        $aTemp[4] = (string)$v[9];  //共进球
                        $aTemp[5] = (string)$v[10];  //主进球
                        $aTemp[6] = (string)$v[11];  //可进球
                        $rData[] = $aTemp;
                    }
                }
            }
        }

        return $rData;
        */
    }

    /**
     * 资料库杯赛积分
     * @param  int $unionId 杯赛ID
     * @return array        杯赛数据
     */
    public function getCupGroupIntegral($unionId,$key = 'yes')
    {
        if(empty($unionId)) return false;
        $unionId = (int) $unionId;
        $where['union_id'] = $unionId;

        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();

        if($res['is_sub'] >=3 || $res['is_union']==1 ||empty($res['year_list'])) return array();
        $temp = explode(',',$res['year_list']);
        $filePath = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/s'.$res['union_id'].'.js';

        if(!is_file($filePath)) return null;
        $content = file_get_contents($filePath);

        $disposeData = new \Common\Services\DisposeService();
        if($key == 'yes')
            $res = $disposeData->matchResultCupKey($content);
        else
            $res = $disposeData->matchResultCup($content);
        if($res === false)
        {
            return false;
        }
        else
        {
            return $res;
        }
    }

    /**
     * 按轮次取资料库杯赛积分
     * @param  int $unionId 杯赛ID
     * @param  int $runId   轮次ID
     * @return array        杯赛数据
     */
    public function getCupMatch($unionId,$runId = '')
    {
        if(empty($unionId)) return false;
	    $mongodb  = mongoService();
	    $unionFilter['union_id']= (int) $unionId;
	    $season = $mongodb->select("fb_union", $unionFilter, ['season'])[0];
	    $matchResult = $mongodb->select("fb_union", $unionFilter,  ['union_name','statistics.'.$season['season'][0].'.matchResult', 'statistics.'.$season['season'][0].'.team_tech']);
	    $seasonData = $matchResult[0]['statistics'][$season['season'][0]];
	    $matchResultData = $seasonData['matchResult'];
	    $arrCupKind = $matchResultData['arrCupKind'];

	    $group = [];
	    $runNo = [];
	    foreach ($arrCupKind as $key => $value) {
	    	$group[$key][] = $value[0];
	    	//是否是分组赛
		    $group[$key][] = $value[1];
		    $group[$key][] = $value[2];
		    $group[$key][] = $value[4];
		    //出線球隊數
		    $group[$key][] = $value[7];
		    if (empty($runId)) {
			    if ($value[6] == 1) {
				    $runNo = $group[$key];
			    }
		    } else {
		        if ($runId == $value[0]) {
		        	$runNo = $group[$key];
		        }
		    }
	    }

	    $TeamData = $seasonData['team_tech']['Tid'];
	    if ($runNo[1] == 1){
	        $groupData= $matchResultData['Groups'];
		    $integral = $this->splitGroupData($groupData, $TeamData);
	    }

	    $info[] = $matchResult[0]['union_name'];
	    $info[] = $season['season'][0];

	    $gameList = [];
	    $match = $matchResultData[$runNo[3].'_matchs'];
	    foreach ($match as $key => $value) {
	    	if ($runNo[4] == 0) {
	    		$gameList = array_merge([$value], $gameList);
		    } elseif ($runNo[4] == 1) {
	    		// 出现球队情况不一样 赛事id 记录在第五第六个字段
	    	    $gameList[] = $value[4];
	    	    $gameList[] = $value[5];
		    }
	    }

	    $gameResult = $mongodb->select("fb_game", ["game_id" => [$mongodb->cmd("in") => $gameList]],
		    ['game_id', 'union_id', 'game_state', 'gtime', 'home_team_id', 'home_team_name', 'home_team_rank', 'away_team_id',
			    'away_team_name', 'away_team_rank', 'score', 'half_score', 'let_goal', 'half_let_goal', 'big_small', 'half_big_small']);

	    $this->splitGameResult($gameResult);






        $where['union_id'] = $unionId;

        $union = M('Union');
        $res = $union ->field('union_name,union_id,is_union,is_sub,year_list')->where($where)->find();

        if(empty($res)) return null;
        if($res['is_sub'] >=3 || $res['is_union']==1 || empty($res['year_list']))
        {
            $info = [0=>explode(',',$res['union_name']),1=>''];
            return ['info'=>$info];
        }
        $temp = explode(',',$res['year_list']);
        $info = [0=>explode(',',$res['union_name']),1=>$temp[0]];

        $filePath = DataPath.'football/matchResult/'.$res['union_id'].'/'.$temp[0].'/s'.$res['union_id'].'.js';

        if(!is_file($filePath)) return null;
        $content = file_get_contents($filePath);

        $disposeData = new \Common\Services\DisposeService();
        $res = $disposeData->matchResultCupRun($content);

        $rData = [];
        if($res === false)
        {
            return false;
        }
        else if(empty($res))
        {
            return ['info'=>$info];
        }
        else
        {
            if(empty($runId))
            {
                $groupTemp = $res['group'];
                $last = array_pop($groupTemp);
                if (!empty($res['tempStatus'])){
                	$index = $res['tempStatus'];
                	foreach ($groupTemp as $key =>  $value) {
                		if ($value[0] == $index) {
                			$last = $groupTemp[$key];
		                }
	                }
                } else {
                	$index = $last[0];
                }
                $rData = array('info'=>$info,'group'=>$res['group'], 'content'=>array('runNo' =>$last, 'content' =>$res['intContent'][$index]));
            }
            else
            {
                $runNo = [];
                foreach($res['group'] as $k =>$v)
                {
                    if($v[0] == $runId)
                    {
                        $runNo = $v;
                        break;
                    }
                }
                if(!empty($runNo))
                    $rData = array('info'=>$info,'group'=>$res['group'], 'content'=>array('runNo' =>$runNo,'content'=>$res['intContent'][$runId]));
                else
                    $rData = array('info'=>$info,'group'=>$res['group'], 'content'=>null);
            }
            return $rData;
        }
    }

    /**
     * 获取球队logo地址
     * @param  string $gameId 赛事ID
     * @return array          球队logo地址
     */
    public function getTeamLogo($gameId,$is_show=false)
    {
        if(empty($gameId))
            return false;

        $gameId = (int) $gameId;
	    $mongodb = mongoService();
	    $baseRes2 = $mongodb->select('fb_game',['game_id'=> $gameId],
		    ['game_start_timestamp', 'game_starttime','home_team_name','home_team_rank', 'home_team_id','game_start_datetime', 'gtime',
			    'union_id', 'union_name', 'away_team_name', 'away_team_rank', 'away_team_id', 'union_color', 'is_go','game_half_datetime',
			    'game_state','start_time', 'field_weather', 'is_sporttery', 'score', 'gtime'])[0];

	    // 获取联赛相关数据
	    $union = $mongodb->select('fb_union',['union_id'=> $baseRes2['union_id']],['union_id','union_name','country_id','level','union_or_cup'])[0];

	    $baseRes = M('GameFbinfo f')
		    ->field('f.game_id,f.gtime,f.game_state, f.is_gamble, f.is_show, f.status, f.score,f.union_name,f.home_team_name,f.away_team_name,b.img_url as home_img_url,c.img_url as away_img_url,
		    is_video,app_video,is_flash,gtime,f.home_team_id,f.away_team_id,f.union_id,is_betting,u.is_union,f.is_go,f.weather,f.game_half_time')
		    ->join('LEFT JOIN qc_game_team b ON f.home_team_id = b.team_id LEFT JOIN qc_game_team c ON f.away_team_id = c.team_id LEFT JOIN qc_union u ON f.union_id = u.union_id')
		    ->where(['game_id'=>$gameId])
		    ->find();

	    $aData = [];

        if(!empty($baseRes2))
        {
            $httpUrl = C('IMG_SERVER');
            $defaultHomeImg = staticDomain('/Public/Home/images/common/home_def.png');
            $defaultAwayImg = staticDomain('/Public/Home/images/common/away_def.png');

            if ((iosCheck()) && I('platform') == '2') //ios审核设定为默认球队logo
            {
                $homeTeamImg = $defaultHomeImg;
                $awayTeamImg = $defaultAwayImg;
            }
            else
            {
                $homeTeamImg = !empty($baseRes['home_img_url']) ? $httpUrl.$baseRes['home_img_url'] : $defaultHomeImg;
                $awayTeamImg = !empty($baseRes['away_img_url']) ? $httpUrl.$baseRes['away_img_url'] : $defaultAwayImg;
            }

            $aData[0] = $homeTeamImg;
            $aData[1] = $awayTeamImg;
            // 0 未开 1 上半场 2 中场 3 下半场 4 加时 5点球 -10 取消 -11 待定 -12 腰斩  -13 终端 -14推迟 -1 完场
	        if ($baseRes2['game_state'] == null) {
	        	$aData[2] = '0';
	        } else {
		        $aData[2] = in_array($baseRes2['game_state'], [0, 1, 2, 3, 4, 5, -10,-11, -12, -13, -14, -1]) ? (string) $baseRes2['game_state'] : '-1';
	        }
            $aData[3] = ($baseRes2['score'] == '-') ? "" : $baseRes2['score'] ;
            $gtime = TimeISTrue($baseRes2['game_start_timestamp'], $baseRes2['gtime'], $baseRes2['game_starttime']);
            $aData[4] = date('Ymd',$gtime);
            $aData[5] = date('H:i',$gtime);
            $aData[6] = $baseRes2['union_name'];
            $aData[7] = $baseRes2['home_team_name'];
            $aData[8] = $baseRes2['away_team_name'];

            if($baseRes['game_state'] !=-1 && !empty($baseRes['app_video']))
            {
                #video
                if(!empty(json_decode($baseRes['app_video'])))
                    $aData[9] = '1';
                else
                    $aData[9] = '0';
            }
            else
            {
                $aData[9] = '0';
            }

            #是否flash
//            $bmap['game_id']  = $gameId;
//            $bmap['flash_id']  = array('exp',' is not NULL');
//            $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where($bmap)->order('update_time desc')->select();
//            if(empty($betRes) || $baseRes['game_state'] == -1)
//            {
//                $aData[10] = '0';
//            }
//            else
//            {
//                if(in_array($baseRes['game_state'],[1,2,3,4]))
//                {
//                    if(!empty($betRes[0]['md_id']))
//                        $aData[10] = '1';
//                    else
//                        $aData[10] = '0';
//                }
//                else
//                {
//                    $aData[10] = '1';
//                }
//            }

//             $has = D('GambleHall')->getFbLinkbet($gameId);
            $has = (new GambleHallMongo())->getFbLinkbet($gameId);
            $aData[10] = $has && in_array($baseRes2['game_state'],[0,1,2,3,4])? '1' : '0';

            //$aData[10] = $baseRes['is_flash'];
            $aData[11] = (string) $gtime;
            $aData[12] = (string) $baseRes2['home_team_id'];
            $aData[13] = (string) $baseRes2['away_team_id'];
            $aData[14] = (string) $baseRes2['union_id'];
            $aData[15] = $baseRes2['is_sporttery'] && $baseRes2['is_sporttery'] == 1 ? '1' : '0';
            $aData[16] = $union['union_or_cup'] === null?'': (string) $union['union_or_cup'];

            //关注(userToken,设备标识deviceID，赛事类型gameType，赛事ID gameId)
            $redis  = connRedis();
            $uinfo  = getUserToken(I('userToken'),true);
            if(isset($uinfo['userid'])){
                $key = $uinfo['userid'];
            }elseif(I('deviceID') !=''){
                $key = I('deviceID');
            }elseif(I('pushID') != ''){
                $key = I('pushID');
            }

            $_preKey = 'push_fb_user_follow_' . I('gameType') . ':';
            $is_follow = $redis->sIsMember($_preKey . $key, I('gameId'));

            $_preKey2 = 'push_fb_user_follow:';
            $is_follow2 = $redis->sIsMember($_preKey2 . $key, I('gameId'));

            $_preKey3 = I('gameType') == 1 ? 'push_apns_user_fb_follow:' . $key : 'push_apns_user_bk_follow:' . $key;
            $is_follow3 = $redis->sIsMember($_preKey3, I('gameId'));

            $aData[17] = $is_follow === true || $is_follow2 === true || $is_follow3 == true ? '1': '0';//1关注，0未关注
            $aData[18] = empty($baseRes2['is_go']) ?'0': (string) $baseRes2['is_go'];
            
            //天气与温度
            $tWeather = explode('^' ,$baseRes2['field_weather']);
            $weatherStr = !empty($tWeather[1]) ? $tWeather[1] : '';
            $weatherNum = !empty($tWeather[2]) ? $tWeather[2] : '';
            $aData[19] = $weatherStr.$weatherNum;

            if(!empty($baseRes2['game_half_datetime']))
            {
               $aData[20]  = date("YmdHis", strtotime($baseRes2['game_half_datetime']));
            }
            else
            {
                $aData[20] = '';
            }
            //api500以上数据
            if(explode('Api', MODULE_NAME)[1] >= 500){
                //是否有情报
                $articleList = D('Home')->getGameArticleList($gameId,1);
                $aData[21] = empty($articleList['articleList']) && empty($articleList['preInfo']) ? '0' : '1';
                //是否有推荐
                $ypOdds = D('GambleHall')->getGambleOdds($gameId,1);
                $jcOdds = D('GambleHall')->getGambleOdds($gameId,2);
                $aData[22] = $ypOdds['data']['odds_check'] == 0 && $jcOdds['data']['odds_check'] == '0' ? '0' : '1';
                $unionLevel = isset($union['level']) ? $union['level'] : '3';
                //是否显示路珠/好路
                $aData[23] = ['0', '0'];

                if(in_array($unionLevel, [0, 1, 2])){
                    $lzTeams = $mongodb->select('fb_team',
                        ['team_id' => ['$in' => [$baseRes2['home_team_id'], $baseRes2['away_team_id']]]],
                        ['team_id', 'team_luzhu']);


                    foreach($lzTeams as $lzk => $lzv){
                        $teamLuzhu = $lzv['team_luzhu'];
                        if($teamLuzhu){

                            $teamHaolu = array_sum(array_values($teamLuzhu['haolu']));
                            if($lzv['team_id'] == $baseRes2['home_team_id']){
                                $aData[23][0] = $teamHaolu >= 1 ? '2' : '1';
                            }

                            if($lzv['team_id'] == $baseRes2['away_team_id']){
                                $aData[23][1] = $teamHaolu >= 1 ? '2' : '1';
                            }
                        }
                    }
                }
            }
        }
        return $aData;
    }
     /**
     * 获取必发数据
     * @param  string $gameId 赛事ID
     * @return array          数据
     */
    public function BifaValue($gameId){
        if(empty($gameId)) return false;
        $map['bf.game_id']=$gameId;
        $rsl=M('FbBingfa')->alias('bf')->field('bet_code,bf_value_win')
                //->join('__GAME_FBINFO__ gf ON bf.game_id=gf.game_id')
                ->where($map)
                ->find();
        $aData = [];
        if($rsl && $rsl['bf_value_win']){
            $data=json_decode($rsl['bf_value_win']);
            //交易总量
            $win=str_replace(',','',$data[0][4]);
            $flat=str_replace(',','',$data[1][4]);
            $lose=str_replace(',','',$data[2][4]);
            $rslCount=$win+$flat+$lose;
            $WinRate=round($win/$rslCount*100,1).'%';
            $FlatRate=round($flat/$rslCount*100,1).'%';
            $LoseRate=round($lose/$rslCount*100,1).'%';

            $big=str_replace(',','',$data[3][4]);
            $small=str_replace(',','',$data[4][4]);
            $BigsCount=$big+$small;
            $BigRate=round($big/$BigsCount*100,1).'%';
            $SmallRate=round($small/$BigsCount*100,1).'%';
            $rslCount=number_format($rslCount,0);
            $BigsCount=number_format($BigsCount,0);
            $aData[0][]=$rslCount;
            $aData[0][]=$WinRate;
            $aData[0][]=$FlatRate;
            $aData[0][]=$LoseRate;
            $aData[0][]=$BigsCount;
            $aData[0][]=$BigRate;
            $aData[0][]=$SmallRate;
            foreach($data as $k=>$v){
                $aData[1][$k][0]=$v[1];
                $aData[1][$k][1]=$v[0];
                $aData[1][$k][2]=$v[3];
                $aData[1][$k][3]=$v[2];
                $aData[1][$k][4]=$v[4];
                $aData[1][$k][5]=$v[7];
                $aData[1][$k][6]=$v[8];
            }
        }
        return $aData;
    }

     /**
     * 获取必发数据
     * @param  string $gameId 赛事ID
     * @return array          数据
     */
    public function BifaTrade($gameId, $limit) {
        if (empty($gameId))
            return false;
	    $mongodb = mongoService();
	    $data = $mongodb->select('fb_bifaindex_win007', ['game_id'=> (int) $gameId], ['betfair'])[0]['betfair'];
	    if (!empty($data)) {
			$sort = array_column($data, 0);
			array_multisort($sort, SORT_DESC, $data);
			$shiled_data= [];
			foreach ($data as $key => $value) {
				$home = $away = $draw = [];
				$home[0] = '主';
				$home[1] = NullString($value[4]);
				$home[2] = NullString(number_format($value[2]));
				$home[3] = NullString($value[1]);
				$home[4] = NullString(date("Y-m-d H:i", strtotime($value[0])));
				$home[5] = $this->orderNum($value[3], $value[2]);
				$draw[0] = '平';
				$draw[1] = NullString($value[8]);
				$draw[2] = NullString(number_format($value[6]));
				$draw[3] = NullString($value[5]);
				$draw[4] = NullString(date("Y-m-d H:i", strtotime($value[0])));
				$draw[5] = $this->orderNum($value[7], $value[6]);
				$away[0] = '客';
				$away[1] = NullString($value[12]);
				$away[2] = NullString(number_format($value[10]));
				$away[3] = NullString($value[9]);
				$away[4] = NullString(date("Y-m-d H:i", strtotime($value[0])));
				$away[5] = $this->orderNum($value[11], $value[10]);
				$shiled_data[] = $home;
				$shiled_data[] = $away;
				$shiled_data[] = $draw;
			}
			return $shiled_data;
	    }
	    
	    /*
        $map['bf.game_id'] = $gameId;
        $rsl = M('FbBingfa')->alias('bf')->field('bet_code,bf_trade_win')
                //->join('__GAME_FBINFO__ gf ON bf.game_id=gf.game_id')
                ->where($map)
                ->find();
        $aData = [];
        if ($rsl && $rsl['bf_trade_win']) {
            $list = json_decode($rsl['bf_trade_win']);
            foreach ($list as $v) {
                foreach ($v as $k=>$vv) {
                     if(isset(array_keys($aData)[$limit-1]) && $limit){
                         break;
                     }
                     $vv[4]=date('Y-m-d H:i',strtotime($vv[4]));
                    $aData[] = $vv;
                }
            }
        }
        return $aData;
        */
    }

    /*
     * 视频直播数据
     */
    public function getlivedata(){

//        $date= strtotime(date('Y-m-d'),"+2 day");
        $start=time()-3600*6;
        $end= strtotime('today')+86400*7;
        $map['gtime']=['between',[$start,$end]];
        $map['game_state']=['egt',0];
        $map['is_video']=1;
        $data=M('GameFbinfo')->alias('f')->field('f.union_name,f.game_id,f.gtime,f.home_team_name,away_team_name,home_team_id,away_team_id,u.union_name u_name,web_video,app_video')
                ->join('LEFT JOIN qc_union u ON f.union_id=u.union_id')
                ->where($map)->select();
        $aData=[];
        if($data){
            setTeamLogo($data,1,false);

            foreach ($data as $k=>&$v){
                if($v['u_name']){
                    $aData[$k]['union_name']=  explode(',', $v['u_name'])[0];
                }else{
                    $aData[$k]['union_name']=  explode(',', $v['union_name'])[0];
                }
                $aData[$k]['game_id']=  $v['game_id'];
                $aData[$k]['gtime']=  $v['gtime'];
                $aData[$k]['home_team_name']=  explode(',', $v['home_team_name'])[0];
                $aData[$k]['away_team_name']=  explode(',', $v['away_team_name'])[0];
                $aData[$k]['home_logo']=  $v['homeTeamLogo'];
                $aData[$k]['away_logo']=  $v['awayTeamLogo'];

                if($v['web_video']){
                    $web=  json_decode($v['web_video'],true);
                    foreach($web as &$wval){
                        if($wval['web_ischain']=='1'){
                            $wval['webname']='';
                            $wval['weburl']='';
                            $wval['web_ischain']='0';
                        }
                    }
                    $v['web_video']=  json_encode($web);
                }
                $aData[$k]['web_video']=  $v['web_video']?$v['web_video']:'';

                if($v['app_video']){
                    $li=  json_decode($v['app_video'],true);
                    foreach($li as &$va){
                        if($va['app_ischain']=='1'){
                            $va['appname']='';
                            $va['appurl']='';
                            $va['app_ischain']='0';
                        }
                    }
                    $v['app_video']=  json_encode($li);
                }
                $aData[$k]['app_video']=  $v['app_video']?$v['app_video']:'';
            }
        }

        $bData=[];
        $bkdata=M('GameBkinfo')->alias('f')->field('f.union_name,f.game_id,f.gtime,f.home_team_name,away_team_name,home_team_id,away_team_id,u.union_name u_name,web_video,app_video')
                ->join('LEFT JOIN qc_bk_union u ON f.union_id=u.union_id')
                ->where($map)->select();
        if($bkdata){
            setTeamLogo($bkdata,2,false);
            foreach ($bkdata as $key=>&$val){
                if($v['u_name']){
                    $bData[$key]['union_name']=  explode(',', $val['u_name'])[0];
                }else{
                    $bData[$key]['union_name']=  explode(',', $val['union_name'])[0];
                }
                $bData[$key]['game_id']=  $val['game_id'];
                $bData[$key]['gtime']=  $val['gtime'];
                $bData[$key]['home_team_name']=  explode(',', $val['home_team_name'])[0];
                $bData[$key]['away_team_name']=  explode(',', $val['away_team_name'])[0];
                $bData[$key]['home_logo']=  $val['homeTeamLogo'];
                $bData[$key]['away_logo']=  $val['awayTeamLogo'];

                if($val['web_video']){
                    $bkweb=  json_decode($val['web_video'],true);
                    foreach($bkweb as &$bwval){
                        if($bwval['web_ischain']=='1'){
                            $bwval['webname']='';
                            $bwval['weburl']='';
                            $bwval['web_ischain']='0';
                        }
                    }
                    $val['web_video']=  json_encode($bkweb);
                }
                $bData[$key]['web_video']=  $val['web_video']?$val['web_video']:'';

                if($val['app_video']){
                    $bkli=  json_decode($val['app_video'],true);
                    foreach($bkli as &$bv){
                        if($bv['app_ischain']=='1'){
                            $bv['appname']='';
                            $bv['appurl']='';
                            $bv['app_ischain']='0';
                        }
                    }
                    $val['app_video']=  json_encode($bkli);
                }
                $bData[$key]['app_video']=  $val['app_video']?$val['app_video']:'';
            }
        }
        return [$aData,$bData];
    }

    /**
     +------------------------------------------------------------------------------
     * 以下开始为app足球v1.1
     +------------------------------------------------------------------------------
    */


     /**
     * [getDataList 获取接口数据]
     * @return void
     */
    public function getTest()
    {
        $GameFbinfo = M('GameFbinfo');
        echo "ok";



    }

















     /**
     +------------------------------------------------------------------------------
     * 以上开始为app足球v1.1
     +------------------------------------------------------------------------------
    */


    /**
     * [getFileExt 获取文件后缀]
     * @param  string $type 文件类别
     * @return string       文件后缀
     */
    public function getFileExt($type)
    {
        switch($type)
        {
            case 'application/x-javascript':
                $ext = '.js';
                break;
            case 'text/xml':
                $ext = '.xml';
                break;
            case 'text/html':
                $ext = '.html';
                break;
            case 'text/plain':
                $ext = '.txt';
                break;
            default:
                $ext = '.htm';
                break;
        }
        return $ext;
    }

    /**
     * [getDataList 获取接口数据]
     * @return void
     */
    public function getDataList()
    {
        $this->data = include 'interfaceArr.php';
    }

	/**
	 * mongo player_tech 对应pid数据源
	 * @param $arrayS
	 * @param $arrayT
	 * @return mixed
	 */
	public function playerTechPidToPlus($arrayS, $arrayT) {
		foreach ($arrayS as $key => $value) {
			$arrayS[$key][] = $arrayT[$value[0]][1];
			$arrayS[$key][] = $arrayT[$value[0]][0];
			$arrayS[$key][] = $arrayT[$value[0]][2];
		}
		return $arrayS;
	}


	/**
	 * @param $Pid player_tech pid数据
	 * @param $Tid player_tech tid 数据
	 * @return mixed
	 */
	public function playerTechTidToPid($Pid, $Tid) {
		foreach ($Pid as $key => $value) {
			foreach ($Tid as $k => $v) {
				if ($value[1] == $k) {
					$Pid[$key][] = $v;
				}
			}
		}
		return $Pid;
	}


	public function splitGroupData($groupArray, $teamNameArray, $lang=0) {
		foreach ($groupArray as $key => $value) {
			foreach ($value as $teamKey => $teamValue){
				unset($groupArray[$key][$teamKey][10]);
				array_splice($groupArray[$key][$teamKey], 2, 0, $teamNameArray[$teamValue[1]][$lang]);
			}
		}
		return $groupArray;
	}


	public function splitGameResult($gameResult, $lang = 0) {
		$gameData = [];
		foreach($gameResult as $key => $value) {
			$gameData[$value['game_id']][] = $value['game_id'];
			$gameData[$value['game_id']][] = $value['union_id'];
			$gameData[$value['game_id']][] = $value['game_state'];
			$gameData[$value['game_id']][] = $value['gtime'];
			$gameData[$value['game_id']][] = $value['home_team_id'];
			$gameData[$value['game_id']][] = $value['home_team_name'][$lang];
			$gameData[$value['game_id']][] = $value['home_team_rank'];
			$gameData[$value['game_id']][] = $value['away_team_id'];
			$gameData[$value['game_id']][] = $value['away_team_name'][$lang];
			$gameData[$value['game_id']][] = $value['away_team_rank'];
			$gameData[$value['game_id']][] = changeSnExpTwo($value['let_goal']);
			$gameData[$value['game_id']][] = changeSnExpTwo($value['half_let_goal']);
			$gameData[$value['game_id']][] = changeSnExpTwo($value['big_small']);
			$gameData[$value['game_id']][] = changeSnExpTwo($value['half_big_small']);
		}
		return $gameData;
	}




	/**
	 * 解析mongo 射手榜数据
	 * @param $playerData 射手榜数据源
	 * @return array 射手榜
	 */
	public function ArcherData($playerData, $dataSource, $keyString) {
		$data = [];
		if ($keyString == 'yes') {
			if ($dataSource == 'player_tech') {
				foreach ($playerData as $key => $value) {
					//主键 通过进球数进行排序
					$data[$key]['rank'] = (string) ($key + 1);
					// 球员名
					$data[$key]['player'] = $value[sizeof($value) - 3][0];
					// 球员所在球队名
					$data[$key]['team_name'] = $value[sizeof($value) - 2][0];
					// 球员所在国家名
					$data[$key]['country'] = $value[sizeof($value) - 1][0];
					//当前赛季总进球数
					$data[$key]['total'] = (string) $value[40];
					//当前赛季主场进球数
					$data[$key]['home_ball']=  (string) $value[45];
					//当前赛季客场进球数
					$data[$key]['away_ball']=  (string) $value[46];
				}
			} elseif ($dataSource == "Archer") {
				foreach ($playerData as $key => $value) {
					$data[$key]['rank'] = (string) $value[0];
					$data[$key]['player'] = $value[2];
					$data[$key]['team_name'] = $value[5];
					$data[$key]['country'] = $value[6];
					$data[$key]['total'] = (string) $value[9];
					$data[$key]['home_ball'] = (string) $value[10];
					$data[$key]['away_ball'] = (string) $value[11];
				}
			}
		} else {
			if ($dataSource == 'player_tech') {
				foreach ($playerData as $key => $value) {
					//主键 通过进球数进行排序
					$data[$key][] = (string) ($key + 1);
					// 球员名
					$data[$key][] = $value[sizeof($value) - 3][0];
					// 球员所在球队名
					$data[$key][] = $value[sizeof($value) - 2][0];
					// 球员所在国家名
					$data[$key][] = $value[sizeof($value) - 1][0];
					//当前赛季总进球数
					$data[$key][] = (string) $value[40];
					//当前赛季主场进球数
					$data[$key][]=  (string) $value[45];
					//当前赛季客场进球数
					$data[$key][]=  (string) $value[46];
				}
			} elseif ($dataSource == "Archer") {
				foreach ($playerData as $key => $value) {
					$data[$key][] = (string) $value[0];
					$data[$key][] = $value[2];
					$data[$key][] = $value[5];
					$data[$key][] = $value[6];
					$data[$key][] = (string) $value[9];
					$data[$key][] = (string) $value[10];
					$data[$key][] = (string) $value[11];
				}
			}
		}
		return $data;
	}
	
	
	public function orderNum($persent, $all) {
		if (($persent !== null || trim($persent) !== '') && ($all !== null || trim($all) !== '')) {
			return (round($persent / $all, 4) * 100).'%';
		}
		return '';
	}
	


	public function lineup($array, $img, $bool) {
		//是否首发
		$isStart = $bool ? '1' : '0';
		$data = [];
		foreach ($array as $key => $value) {
			$temp = [];
			// 球员id 暂时为空
			$temp[] = "";
			// 球员名称
			$temp[] = (string) $value[sizeof($value) -1];
			// 球员秋衣
			$temp[] = (string) $value[0];
			// 是否首发
			$temp[] = $isStart;
			//球员位置 暂时为空
			$temp[] = "";
			$temp[] = $img;
			$data[] = $temp;
		}
		return $data;
	}



}