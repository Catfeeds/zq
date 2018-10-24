<?php
/**
 * 推荐大厅模型类
 * @author huangjiezhen <418832673@qq.com> 2015.12.16
 */

use Think\Model;
use Think\Tool\Tool;
class GambleHallModel extends Model
{
    protected $tableName = 'game_fbinfo';

    //足球推荐大厅
    public function matchList($type=1, $return = '',$exp = 0)
    {
        $game = $this->getGameFbinfo($type,$exp,$return);
        $union = $sort_game_state = $sort_gtime = $sort_union = $sort_union2 = [];
        foreach ($game as $k => $v)
        {
            if(
                   ($v['gtime'] + 60 < time() && $v['game_state'] == 0)  //过了开场时间未开始
                || ($v['game_state'] == -14 || $v['game_state'] == -11)  //屏蔽待定和推迟
                || ($v['gtime'] + 8400 < time() && array_search($v['game_state'],[1,2,3,4]) !== false) //140分钟还没结束
              )
            {
                unset($game[$k]);
                continue;
            }

            //分解时间
            $game[$k]['game_date'] = date('Ymd',$v['gtime']);
            $game[$k]['game_time'] = date('H:i',$v['gtime']);

            //增加排序的条件
            $sort_gtime[]      = $v['gtime'];

            if (stristr(MODULE_NAME,'Api'))
            {
                if ($v['game_state'] < 0)
                {
                    $v['game_state'] = abs($v['game_state']);
                }
                else if ($v['game_state'] > 0)
                {
                    $v['game_state'] += 15; //纯粹为了排序 -.-!!!
                }
                $sort_game_state[] = $v['game_state'];
            }
            else
            {
                $sort_game_state[] = $v['game_state'];
            }

            //获取联盟中球队数量
            if (array_key_exists($v['union_id'],$union))
            {
                $union[$v['union_id']]['union_num'] = (string)($union[$v['union_id']]['union_num']+1);
            }
            else
            {
                $union[$v['union_id']] = ['union_id'=>$v['union_id'],'union_name'=>$v['union_name'],'union_num'=>'1','union_color'=>$v['union_color']];
                $sort_union[] = $v['is_sub'];
                $sort_union2[] = $v['sort'];
            }
            unset($game[$k]['is_sub']);
        }

        $union = array_values($union);
        array_multisort($sort_union,SORT_ASC,$sort_union2,SORT_ASC,$union);
        
        if (stristr(MODULE_NAME,'Api'))
        {
            foreach ($union as $k => $v)
            {
                $union[$k]['union_name'] = explode(',', $v['union_name']);
            }
            if($return == 'union'){
				//只返回今日联盟
                return $union;
            }
            foreach ($game as $k => $v)
            {
                $game[$k]['union_name']     = explode(',', $v['union_name']);
                $game[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $game[$k]['away_team_name'] = explode(',', $v['away_team_name']);

                if ($v['game_half_time']) //半场时间转换
                {
                    $halfTime    = explode(',', $v['game_half_time']);
                    $halfTime[1] = str_pad($halfTime[1]+1, 2, '0', STR_PAD_LEFT); //js的月份+1为正常月份
                    $game[$k]['game_half_time'] = implode('', $halfTime);
                }
                //接口删除盘口赔率等不需要返回的字段
                unset($game[$k]['home_team_id']);
                unset($game[$k]['away_team_id']);
                unset($game[$k]['fsw_exp']);
                unset($game[$k]['fsw_ball']);
                unset($game[$k]['fsw_exp_home']);
                unset($game[$k]['fsw_exp_away']);
                unset($game[$k]['fsw_ball_home']);
                unset($game[$k]['fsw_ball_away']);
            }
            array_multisort($sort_game_state,SORT_ASC, $sort_gtime,SORT_ASC, $game);
        }
        //获取球队logo
        setTeamLogo($game,1);
        return [$game,$union];
    }

    //获取足球竞猜即时盘口赔率
    public function getFbGoal(&$game)
    {
        foreach ($game as $k => $v) {
            $gameId[] = $v['game_id'];
        }
        //获取数据
        $fb_goal = M('fb_odds')->field('game_id,exp_value')->where(['company_id'=>3,'game_id'=>['in',$gameId]])->select();
        foreach ($game as $k => $v)
        {
            foreach ($fb_goal as $kk => $vv)
            {
                //组装对应数据
                if($v['game_id']  == $vv['game_id'])
                {
                    $game[$k]['exp_value'] = $vv['exp_value'];
                }
            }
        }
        //获取即时数据
        foreach ($game as $k => $v)
        {
            if(!empty($v['exp_value']))
            {
                $odds = explode('^', $v['exp_value']);

                $whole      = explode(',', $odds[0]);  //全场
                if($whole[6] !='' || $whole[7] !='' || $whole[8] !='')
                {
                    //全场滚球
                    if($whole[6] == 100 || $whole[7] == 100 || $whole[7] == 100)
                    {
                        $game[$k]['fsw_exp_home'] = '';
                        $game[$k]['fsw_exp']      = '封';
                        $game[$k]['fsw_exp_away'] = '';
                    }else{
                        $game[$k]['fsw_exp_home'] = $whole[6];
                        $game[$k]['fsw_exp']      = $whole[7];
                        $game[$k]['fsw_exp_away'] = $whole[8];
                    }
                }
                elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
                {
                    //全场即时
                    if($whole[3] == 100 || $whole[4] == 100 || $whole[5] == 100)
                    {
                        $game[$k]['fsw_exp_home'] = '';
                        $game[$k]['fsw_exp']      = '封';
                        $game[$k]['fsw_exp_away'] = '';
                    }else{
                        $game[$k]['fsw_exp_home'] = $whole[3];
                        $game[$k]['fsw_exp']      = $whole[4];
                        $game[$k]['fsw_exp_away'] = $whole[5];
                    }
                }

                $size       = explode(',', $odds[2]);  //大小
                if($size[6] !='' || $size[7] !='' || $size[8] !='')
                {
                    //大小滚球
                    if($size[6] == 100 || $size[7] == 100 || $size[8] == 100)
                    {
                        $game[$k]['fsw_ball_home'] = '';
                        $game[$k]['fsw_ball']      = '封';
                        $game[$k]['fsw_ball_away'] = '';
                    }else{
                        $game[$k]['fsw_ball_home'] = $size[6];
                        $game[$k]['fsw_ball']      = $size[7];
                        $game[$k]['fsw_ball_away'] = $size[8];
                    }
                }
                elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
                {
                    //大小即时
                    if($size[3] == 100 || $size[4] == 100 || $size[5] == 100)
                    {
                        $game[$k]['fsw_ball_home'] = '';
                        $game[$k]['fsw_ball']      = '封';
                        $game[$k]['fsw_ball_away'] = '';
                    }else{
                        $game[$k]['fsw_ball_home'] = $size[3];
                        $game[$k]['fsw_ball']      = $size[4];
                        $game[$k]['fsw_ball_away'] = $size[5];
                    }
                }
            }
        }
    }

    /**
     * 获取亚盘或竞彩今日可推荐赛事
     * @param  int $type    1:亚盘  2:竞彩
     * @return array
     */
    public function getGameFbinfo($type,$exp=0,$return = '')
    {
        if($exp == 0){
            //获取今日赛事列表
            $DataService = new \Common\Services\DataService();
            $gameIdArr = $DataService->getGameTodayGids(1);
            $map['game_id'] = ['$in'=>$gameIdArr];
        }else{
            //专家推荐，多获取后两天赛事日期
            $blockTime = getBlockTime(1,true);
            //MongoDate时间格式
            $startTime = new \MongoDate($blockTime['beginTime']);
            $endTime   = new \MongoDate($blockTime['endTime'] + 86400);
            $map['game_starttime'] = [
                '$gt' => $startTime,
                '$lt' => $endTime,
            ];
        }
        //获取赛事
        $mongo = mongoService();
        if($return == 'union'){
            if(!$baseRes = S('app_baseRes')){
                $baseRes = $mongo->select('fb_game',$map,['game_id','union_name','union_id','start_time','game_starttime','game_start_timestamp','game_state']);
                S('app_baseRes', $baseRes, 600);
            }


        }else{
            $baseRes = $mongo->select('fb_game',$map,['game_id','union_name','home_team_name','away_team_name','home_team_id','away_team_id','union_id','start_time','game_starttime','game_start_timestamp','game_half_datetime','game_state','score','half_score','is_sporttery','spottery_num','union_color','match_odds']);

        }

        $unionIdArr = $gameIdArr = [];
        foreach($baseRes as $k=> $v)
        {
            $unionIdArr[] = (int)$v['union_id'];
            $gameIdArr[] = (int)$v['game_id'];
        }

        //获取联盟数据
        $union = $mongo->select('fb_union',['union_id'=>['$in'=>$unionIdArr]],['union_id','union_name','country_id','level','union_or_cup','union_color']);
        foreach ($union as $k => $v) {
            $unionArr[$v['union_id']] = $v;
        }

        //获取mysql业务数据
        $GameFbinfo = M('GameFbinfo')->field("game_id,is_gamble,is_color,is_show,status")->where(['game_id'=>['in',$gameIdArr]])->select();
        foreach ($GameFbinfo as $k => $v) {
            $gameArr[$v['game_id']] = $v;
        }

        if($type == 1)
        {
            //获取赔率
            $appfbService = new \Home\Services\AppfbService();
            $oddsArr = $appfbService->fbOdds($gameIdArr);

            $game = [];
            foreach ($baseRes as $k => $v) {
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];
                //没赔率或联盟信息过滤
                if(empty($unionData)){
                    continue;
                }
                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(!empty($mysqlGame)){
                    if($mysqlGame['is_gamble'] != 1 || $mysqlGame['status'] != 1){
                        continue;
                    }
                }
                $unionLevel = isset($unionData['level']) ? $unionData['level'] : '3';
                //联盟小于3级过滤
                if( $unionLevel > 2 && $mysqlGame['is_show'] != 1 ){
                    continue;
                }
                $arr = [];
                $arr['game_id']        = (string)$v['game_id'];
                $arr['union_id']       = (string)$v['union_id'];
                $arr['union_name']     = implode(',', $v['union_name']);
                $arr['union_color']    = $v['union_color'];
                $arr['sort']           = '999';
                $game_start_timestamp  = TellRealTime($v['start_time'],$v['game_start_timestamp'],$v['game_starttime'],$v['game_state']);
                $arr['gtime']          = (string)$game_start_timestamp;
                $arr['game_half_time'] = isset($v['game_half_datetime']) ? date('YmdHi',strtotime($v['game_half_datetime'])) : '';
                $arr['game_state']     = (string)$v['game_state'];
                $arr['home_team_name'] = implode(',', $v['home_team_name']);
                $arr['score']          = $v['score'];
                $arr['half_score']     = $v['half_score'];
                $arr['away_team_name'] = implode(',', $v['away_team_name']);
                $arr['is_sub']         = $unionLevel;
                $arr['home_team_id']   = $v['home_team_id'];
                $arr['away_team_id']   = $v['away_team_id'];
                //赔率
                if(isset($oddsArr[$v['game_id']]))
                {
                    $fb_odds = $oddsArr[$v['game_id']];
                    $arr['fsw_exp_home']   = $fb_odds[0];   //主队亚盘即时赔率
                    $arr['fsw_exp']        = changeExp($fb_odds[1]);   //亚盘即时盘口
                    $arr['fsw_exp_away']   = $fb_odds[2];   //客队亚盘即时赔率
                    $arr['fsw_ball_home']  = $fb_odds[6];   //主队大小即时赔率
                    $arr['fsw_ball']       = changeExp($fb_odds[7]);   //大小即时盘口
                    $arr['fsw_ball_away']  = $fb_odds[8];   //客队大小即时赔率
                }
                else
                {
                    //取初盘
                    $odds    = $v['match_odds'][3];
                    $arr['fsw_exp_home']   = str_replace(' ', '', $odds[0]);    //主队亚盘初盘赔率
                    $arr['fsw_exp']        = changeSnExpTwo(str_replace(' ', '', $odds[1]));    //亚盘初盘盘口
                    $arr['fsw_exp_away']   = str_replace(' ', '', $odds[2]);    //客队亚盘初盘赔率
                    $arr['fsw_ball_home']  = str_replace(' ', '', $odds[12]);   //主队大小初盘赔率
                    $arr['fsw_ball']       = str_replace(' ', '', $odds[13]);   //大小初盘盘口
                    $arr['fsw_ball_away']  = str_replace(' ', '', $odds[14]);   //客队大小初盘赔率
                }
                if($arr['fsw_exp_home'] == '' || $arr['fsw_exp'] == '' || $arr['fsw_exp_away'] == '' || $arr['fsw_ball_home'] == '' || $arr['fsw_ball'] == '' || $arr['fsw_ball_away'] == '' ){
                    continue;
                }
                $game[] = $arr;
                $sort1[] = $v['game_state'];
                $sort2[] = $game_start_timestamp;
            }
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$game);
        }
        else if($type == 2)
        {
            //获取即时竞彩赔率
            $goals = D('GambleHall')->getSportteryGoal($gameIdArr);
            $game = [];
            foreach ($baseRes as $k => $v) {
                //即时赔率数据
                $odds      = $goals[$v['game_id']];
                //联盟表数据
                $unionData = $unionArr[$v['union_id']];
                //没赔率或联盟信息或不是竞彩过滤
                if(empty($odds) || empty($unionData) || $v['is_sporttery'] != 1){
                    continue;
                }
                //mysql赛事显示控制
                $mysqlGame = $gameArr[$v['game_id']];
                if(!empty($mysqlGame)){
                    if($mysqlGame['is_color'] != 1 || $mysqlGame['status'] != 1){
                        continue;
                    }
                }
                $unionLevel = isset($unionData['level']) ? $unionData['level'] : '3';
                $arr = [];
                $arr['game_id']        = (string)$v['game_id'];
                $arr['union_id']       = (string)$v['union_id'];
                $arr['union_name']     = implode(',', $v['union_name']);
                $arr['union_color']    = $v['union_color'];
                $arr['sort']           = '999';
                $game_start_timestamp  = TellRealTime($v['start_time'],$v['game_start_timestamp'],$v['game_starttime'],$v['game_state']);
                $arr['gtime']          = (string)$game_start_timestamp;
                $arr['game_half_time'] = isset($v['game_half_datetime']) ? date('YmdHi',strtotime($v['game_half_datetime'])) : '';
                $arr['game_state']     = (string)$v['game_state'];
                $arr['home_team_name'] = implode(',', $v['home_team_name']);
                $arr['score']          = $v['score'];
                $arr['half_score']     = $v['half_score'];
                $arr['away_team_name'] = implode(',', $v['away_team_name']);
                $arr['is_sub']         = $unionLevel;
                $arr['home_team_id']   = $v['home_team_id'];
                $arr['away_team_id']   = $v['away_team_id'];
                $arr['bet_code']       = $v['spottery_num'];
                $arr['home_odds']      = $odds[0];
                $arr['draw_odds']      = $odds[1];
                $arr['away_odds']      = $odds[2];
                $arr['home_letodds']   = $odds[3];
                $arr['draw_letodds']   = $odds[4];
                $arr['away_letodds']   = $odds[5];
                $arr['let_exp']        = $odds[6];
                $arr['is_reverse']     = $odds[7]; //是否反转过标志
                $game[] = $arr;
                $sort1[] = $v['game_state'];
                $sort2[] = $game_start_timestamp;
                $sort3[] = $v['spottery_num'];
            }
            array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$sort3,SORT_ASC,$game);
        }
        return $game;
    }

    //篮球推荐大厅
    public function basketballList()
    {
        //获取今日篮球赛程id
        $dataService = new \Common\Services\DataService();
        $gameIdArr = $dataService->getGameTodayGids(2);
        if(empty($gameIdArr)){
            return [];
        }

        $mongo = mongoService();
        $baseRes = $mongo->select('bk_game_schedule',['game_id'=>['$in'=>$gameIdArr]],['game_id','union_id','game_status','union_name','home_team_name','away_team_name','union_color','game_time','game_timestamp','quarter_time','home_team_id','away_team_id','game_info','instant_index']);

        $unionIdArr = [];
        foreach($baseRes as $k=> $v)
        {
            $unionIdArr[] = (int)$v['union_id'];
        }

        //获取联盟数据
        $union = $mongo->select('bk_union',['union_id'=>['$in'=>$unionIdArr]],['union_id','union_name','grade','union_color']);
        foreach ($union as $k => $v) {
            $unionArr[$v['union_id']] = $v;
        }

        //获取mysql业务数据
        $GameBkinfo = M('GameBkinfo')->field("game_id,is_gamble,is_show,status")->where(['game_id'=>['in',$gameIdArr]])->select();
        foreach ($GameBkinfo as $k => $v) {
            $gameArr[$v['game_id']] = $v;
        }

        $game = $union = [];
        foreach ($baseRes as $k => $v) {
            //联盟表数据
            $unionData = $unionArr[$v['union_id']];
            //赔率获取
            $odds = $this->getBkOdds($v['instant_index']);
            //没赔率或联盟信息过滤
            if(empty($odds)){
                continue;
            }
            //mysql赛事显示控制
            $mysqlGame = $gameArr[$v['game_id']];
            if(!empty($mysqlGame)){
                if($mysqlGame['is_gamble'] != 1 || $mysqlGame['status'] != 1){
                    continue;
                }
            }
            $arr = [];
            $arr['game_id']   = (string)$v['game_id'];
            $arr['gtime']     = (string)$v['game_timestamp'];
            $arr['show_date'] = date('Ymd',$v['game_timestamp']);
            $arr['union_id']  = (string)$v['union_id'];
            $arr['union_name']= implode(',', $v['union_name']);
            $arr['game_date'] = date('Ymd',$v['game_timestamp']);
            $arr['game_time'] = date('H:i',$v['game_timestamp']);
            $arr['game_half_time'] = '';
            $arr['game_state']= (string)$v['game_status'];
            $arr['total']     = '4';
            $arr['home_team_name'] = implode(',', $v['home_team_name']);
            $arr['home_team_id']   = (string)$v['home_team_id'];
            if(isset($v['game_info'])){
                $score = $v['game_info'][3].'-'.$v['game_info'][4];
                $half_score = ($v['game_info'][5] + $v['game_info'][7]) .'-'. ($v['game_info'][6] + $v['game_info'][8]);
            }else{
                $score = '';
                $half_score = '';
            }
            $arr['score']     = $score;
            $arr['half_score']= $half_score;
            $arr['away_team_name'] = implode(',', $v['away_team_name']);
            $arr['away_team_id']   = (string)$v['away_team_id'];
            $arr['union_color']    = $v['union_color'];
            $arr['is_sub']         = $unionData['grade'] ? (string)$unionData['grade'] : '3';
            $arr['sort']           = '999';
            $arr['fsw_exp']        = $odds[0];
            $arr['fsw_exp_home']   = $odds[1];
            $arr['fsw_exp_away']   = $odds[2];
            $arr['fsw_total']      = $odds[3];
            $arr['fsw_total_home'] = $odds[4];
            $arr['fsw_total_away'] = $odds[5];
            $game[] = $arr;
            $sort1[] = $v['game_status'];
            $sort2[] = $v['game_timestamp'];
            //获取联盟中球队数量
            if (array_key_exists($arr['union_id'],$union))
            {
                $union[$arr['union_id']]['union_num'] = (string)($union[$arr['union_id']]['union_num']+1);
            }
            else
            {
                $union[$arr['union_id']] = ['union_id'=>$arr['union_id'],'union_name'=>$arr['union_name'],'union_num'=>'1'];
                $sort_union[]  = $arr['is_sub'];
                $sort_union2[] = $arr['sort'];
            }
        }
        //排序
        array_multisort($sort1,SORT_DESC,$sort2,SORT_ASC,$game);
        array_multisort($sort_union,SORT_ASC,$sort_union2,SORT_ASC,$union);
        $game  = array_values($game);
        $union = array_values($union);

        //获取球队logo
        setTeamLogo($game,2);

        return [$game,$union];
    }

    //获取篮球实时赔率
    public function getBkOdds($instant_index,$gameId = ''){
        if($gameId != ''){
            $mongo = mongoService();
            $game = $mongo->fetchRow('bk_game_schedule',['game_id'=>(int)$gameId],['game_id','instant_index']);
            $instant_index = $game['instant_index'];
        }
        //篮球推荐赔率公司配置
        $bk_company_id = C('bk_company_id');
        $whole = $instant_index['letGoal'][$bk_company_id];
        $size  = $instant_index['bigSmall'][$bk_company_id];
        if(empty($whole) || empty($size)){
            return [];
        }
        $odds = ['','','','','',''];
        if($whole[6] !='' || $whole[7] !='' || $whole[8] !='')
        {
            //全场滚球
            $odds[0] = $whole[6];
            $odds[1] = $whole[7];
            $odds[2] = $whole[8];
        }
        elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
        {
            //全场即时
            $odds[0] = $whole[3];
            $odds[1] = $whole[4];
            $odds[2] = $whole[5];
        }
        elseif ($whole[0] !='' || $whole[1] !='' || $whole[2]!='')
        {
            //全场初盘
            $odds[0] = $whole[0];
            $odds[1] = $whole[1];
            $odds[2] = $whole[2];
        }

        if($size[6] !='' || $size[7] !='' || $size[8] !='')
        {
            //大小滚球
            $odds[3]= $size[6];
            $odds[4]= $size[7];
            $odds[5]= $size[8];
        }
        elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
        {
            //大小即时
            $odds[3] = $size[3];
            $odds[4] = $size[4];
            $odds[5] = $size[5];
        }
        elseif ($size[0] !='' || $size[1] !='' || $size[2] !='')
        {
            //大小初盘
            $odds[3] = $size[0];
            $odds[4] = $size[1];
            $odds[5] = $size[2];
        }
        return $odds;
    }

    //获取篮球竞猜即时盘口赔率（兼容以前代码）
    public function getBkGoal(&$game)
    {
        foreach ($game as $k => $v) {
            $gameId[] = (int)$v['game_id'];
        }
        //获取mongo数据
        $mongo = mongoService();
        $bk_goal = $mongo->select('bk_game_schedule',['game_id'=>['$in'=>$gameId]],['game_id','instant_index']);
        //篮球推荐赔率公司配置
        $bk_company_id = C('bk_company_id');
        foreach ($bk_goal as $k => $v)
        {
            $letGoal  = $v['instant_index']['letGoal'][$bk_company_id];
            $bigSmall = $v['instant_index']['bigSmall'][$bk_company_id];
            $goalArr[$v['game_id']] = ['letGoal'=>$letGoal,'bigSmall'=>$bigSmall];
        }

        //获取即时数据
        foreach ($game as $k => $v)
        {
            $odds = $goalArr[$v['game_id']];
            if(!empty($odds))
            {
                $whole = $odds['letGoal'];  //全场
                if($whole[6] !='' || $whole[7] !='' || $whole[8] !='')
                {
                    //全场滚球
                    $game[$k]['fsw_exp']      = $whole[6];
                    $game[$k]['fsw_exp_home'] = $whole[7];
                    $game[$k]['fsw_exp_away'] = $whole[8];
                }
                elseif ($whole[3] !='' || $whole[4] !='' || $whole[5]!='')
                {
                    //全场即时
                    $game[$k]['fsw_exp']      = $whole[3];
                    $game[$k]['fsw_exp_home'] = $whole[4];
                    $game[$k]['fsw_exp_away'] = $whole[5];
                }
                elseif ($whole[0] !='' || $whole[1] !='' || $whole[2]!='')
                {
                    //全场初盘
                    $game[$k]['fsw_exp']      = $whole[0];
                    $game[$k]['fsw_exp_home'] = $whole[1];
                    $game[$k]['fsw_exp_away'] = $whole[2];
                }

                $size = $odds['bigSmall'];  //大小
                if($size[6] !='' || $size[7] !='' || $size[8] !='')
                {
                    //大小滚球
                    $game[$k]['fsw_total']      = $size[6];
                    $game[$k]['fsw_total_home'] = $size[7];
                    $game[$k]['fsw_total_away'] = $size[8];
                }
                elseif ($size[3] !='' || $size[4] !='' || $size[5] !='')
                {
                    //大小即时
                    $game[$k]['fsw_total']      = $size[3];
                    $game[$k]['fsw_total_home'] = $size[4];
                    $game[$k]['fsw_total_away'] = $size[5];
                }
                elseif ($size[0] !='' || $size[1] !='' || $size[2] !='')
                {
                    //大小初盘
                    $game[$k]['fsw_total']      = $size[0];
                    $game[$k]['fsw_total_home'] = $size[1];
                    $game[$k]['fsw_total_away'] = $size[2];
                }
            }
        }
    }

    /**
     * 足球/篮球推荐
     * @param  int   $userid   用户id
     * @param  array $param    推荐参数
     * @param  int   $platform 平台
     * @param  int   $gameType 类型 1：足球 2：篮球 默认1
     * @return array         剩余的次数
     */
    public function gamble($userid,$param,$platform,$gameType=1)
    {
        //判断是否是在今天可推荐赛程列表
        $DataService = new \Common\Services\DataService();
        $gids = $DataService->getGameTodayGids($gameType);  

        if(!in_array($param['game_id'], $gids)){
            return 2019;
        }
        
        $client_handcp  = $param['handcp'];
        unset($param['odds'],$param['handcp']);
        //获取盘口和赔率
        switch ($gameType)
        {
            case '1': 
                switch ($param['play_type']) 
                {
                    case '1':
                    case '-1':
                        $Lv         = 'lv';
                        $playType   = 1;
                        $min_odds   = 0.6;
                        $error_code = 2016;
                        self::getHandcpAndOdds($param);
                        break;

                    case '2':
                    case '-2':
                        $Lv         = 'lv_bet';
                        $playType   = 2;
                        $min_odds   = 1.4;
                        $error_code = 2017;
                        self::getHandcpAndOddsBet($param);
                        break;
                }
                break;

            case '2': 
                $Lv         = 'lv_bk';
                $min_odds   = 0.6;
                $error_code = 2016;
                self::getHandcpAndOddsBk($param); 
                break;
        }

        //亚盘不能低于0.6，竞彩不能低于1.4
        if($param['odds'] < $min_odds)
            return $error_code;

        //判断盘口
        if((!isset($param['confirm']) || $param['confirm'] != 1) && $platform != 1){
            $handcp = $playType == 1 ? changeExp($param['handcp']) : $param['handcp'];
            if($client_handcp != $handcp)
                return 2018;
        }

        $gameModel   = $gameType == 1 ? M('GameFbinfo') : M('GameBkinfo');
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');

        //推荐字段不能为空
        if (
            $param['game_id']       == null
            || $param['play_type']  == null
            || $param['chose_side'] == null
            || $param['odds']       == null
            || !isset($param['handcp'])
            || $platform            == null
        )
        {
            return 201;
        }

        //获取剩余推荐次数，推荐配置
        list($normLeftTimes,$imptLeftTimes,$gameConf,$gambleList) = $this->gambleLeftTimes($userid,$gameType,$playType);

        //判断推荐的次数是否已达上限
        if ($normLeftTimes <= 0)
            return 2004;

        //判断推荐的类型，不可重复、冲突推荐
        foreach ($gambleList as $v)
        {
            if ($v['play_type'] == $param['play_type'] && $v['game_id'] == $param['game_id'])
                return 2003;
        }

        //如果有推荐分析、需要大于10字小于50字
        if ($param['desc'])
        {
            $descLenth = Think\Tool\Tool::utf8_strlen($param['desc']);

            if ($descLenth < 10 || $descLenth > 400)
                return 2011;
        }

        //是否有推荐购买和推荐分析
        $userInfo = M('FrontUser')->master(true)->field(['id',$Lv,'point','nick_name'])->where(['id'=>$userid])->find();

        if ($param['tradeCoin'])
        {
            //检查金币是否合法
            $tradeCoinArr = [];
            foreach ($gameConf['userLv'] as $k => $v) {
                $tradeCoinArr[] = $v['letCoin'];
            }
            if(!in_array($param['tradeCoin'], $tradeCoinArr)){
                return $platform == 1 ? 201 : 2022;
            }
            //如果设置推荐购买、判断是否符合用户等级
            $maxCoin = $gameConf['userLv'][$userInfo[$Lv]]['letCoin'];
            if ($param['tradeCoin'] > $maxCoin)
                return $platform == 1 ? 2012 : 2022;
        }

        //音频判断，6级用户才能发
        $file = $param['uploadVoice'] ?: '';
        if($file){
            if($userInfo[$Lv] < 6)
                return 1082;
        }

        $mongo = mongoService();
        if($gameType == 1) 
        {
            //足球
            $gameInfo = $mongo->fetchRow('fb_game',['game_id'=>(int)$param['game_id']],['game_id','union_id','union_name','home_team_name','away_team_name','start_time','game_starttime','game_start_timestamp']);
            //判断比赛准确开赛时间
            $gameInfo['gtime']  = TellRealTime($gameInfo['start_time'],$gameInfo['game_start_timestamp'],$gameInfo['game_starttime'],$gameInfo['game_state']);
        }
        else 
        {
            //篮球
            $gameInfo = $mongo->fetchRow('bk_game_schedule',['game_id'=>(int)$param['game_id']],['game_id','union_id','union_name','home_team_name','away_team_name','game_timestamp']);
            $gameInfo['gtime'] = $gameInfo['game_timestamp'];

            //推荐数量检查
            // $gamblebk_number = M('gamblebkNumber')->field("all_home_num,all_away_num ,all_big_num,all_small_num,half_home_num,half_away_num,half_big_num,half_small_num")->where(['game_id'=>$param['game_id']])->find();
            // if($gamblebk_number['all_home_num'] + $gamblebk_number['all_away_num'] < 10) 
            // {
            //     //全场让球小于10时添加推荐
            //     D('Robot')->dogamble($gameInfo,1,null,$gameType);
            // }
            // if($gamblebk_number['all_big_num'] + $gamblebk_number['all_small_num'] < 10) 
            // {
            //     //全场大小小于10时添加推荐
            //     D('Robot')->dogamble($gameInfo,-1,null,$gameType);
            // }
        }

        //判断推荐时间
        if (time() > $gameInfo['gtime'])
            return 2002;

        //增加推荐记录
        $param['user_id']        = $userid;
        $param['vote_point']     = $gameConf['norm_point'];
        $param['create_time']    = time();
        $param['platform']       = $platform;
        $param['tradeCoin']      = (int)$param['tradeCoin'];
        $param['union_id']       = $gameInfo['union_id'];
        $param['union_name']     = $gameInfo['union_name'][0];
        $param['home_team_name'] = $gameInfo['home_team_name'][0];
        $param['away_team_name'] = $gameInfo['away_team_name'][0];
        $param['game_id']        = $gameInfo['game_id'];
        $param['game_date']      = date('Ymd',$gameInfo['gtime']);
        $param['game_time']      = date('H:i',$gameInfo['gtime']);
        $param['sign']           = $userid.'^'.$param['game_id'].'^'.$param['play_type'];
        $param['odds']           = getFloatNumber($param['odds']);//保留到小数点后2位

        $insertId = $GambleModel->add($param);

        if (!$insertId)
            return 2007;

        //保存音频
        if($file){
            $uploadRes = D('Uploads')->uploadFileBase64($file, "gamble", date('Ymd'), $insertId, $gameType, NULL, false, true);
            if($uploadRes['status'] == 1)
                $GambleModel->where(['id'=>$insertId])->save(['voice'=>$uploadRes['url'], 'voice_time' => (int)$param['voiceTime']]);
        }

        //添加推荐数量
        $this->setGambleNumber($param,$gameType);
        
        //增加推荐分析的积分记录,0积分跳过
        $descFlag = false;
        if (!empty($param['desc']) && $gameConf['gamble_desc'] != 0)
        {
            $changePoint = $gameConf['gamble_desc'];
            $totalPoint = $userInfo['point'] + $changePoint;

            M('FrontUser')->where(['id'=>$userid])->setInc('point',$changePoint);
            switch ($gameType) {
                case '1': 
                    $descType = '足球'; 
                    switch ($param['play_type']) {
                        case  '1':
                        case '-1': $descPlay = '亚盘';  break;
                        case  '2': 
                        case '-2': $descPlay = '竞彩';  break;
                    }
                    break;
                case '2': 
                    $descType = '篮球'; 
                    $descPlay = '亚盘';
                    break;
            }

            $home_team_name = $gameInfo['home_team_name'][0];
            $away_team_name = $gameInfo['away_team_name'][0];
            $desc = "您已发布{$descType}{$descPlay}推荐分析[{$home_team_name}VS$away_team_name]";
            M('PointLog')->add([
                'user_id'     => $userid,
                'log_time'    => NOW_TIME,
                'log_type'    => 12,
                'gamble_id'   => $param['game_id'],
                'change_num'  => $changePoint,
                'total_point' => $totalPoint,
                'desc'        => $desc
            ]);

            $descFlag = true;
        }

        //音频赠送积分，分析和音频同时存在，只赠送分析
        if (!empty($file) && $gameConf['gamble_voice'] != 0 && $descFlag == false){
            $changePoint = $gameConf['gamble_voice'];
            $totalPoint  = $userInfo['point'] + $changePoint;

            M('FrontUser')->where(['id'=>$userid])->setInc('point',$changePoint);
            switch ($gameType) {
                case '1':
                    $descType = '足球';
                    switch ($param['play_type']) {
                        case  '1':
                        case '-1': $descPlay = '亚盘';  break;
                        case  '2':
                        case '-2': $descPlay = '竞彩';  break;
                    }
                    break;
                case '2':
                    $descType = '篮球';
                    $descPlay = '亚盘';
                    break;
            }

            $home_team_name = $gameInfo['home_team_name'][0];
            $away_team_name = $gameInfo['away_team_name'][0];
            $desc = "您已发布{$descType}{$descPlay}音频分析[{$home_team_name}VS$away_team_name]";

            M('PointLog')->add([
                'user_id'     => $userid,
                'log_time'    => NOW_TIME,
                'log_type'    => 20,
                'gamble_id'   => $param['game_id'],
                'change_num'  => $changePoint,
                'total_point' => $totalPoint,
                'desc'        => $desc
            ]);
        }

        //-------推送相关----
        $this->gamblePush($userInfo,$gameInfo);

        $normLeftTimes--;
        return ['normLeftTimes'=>$normLeftTimes,'imptLeftTimes'=>$imptLeftTimes,'voice'=> $file ? explode('?', explode('/', $uploadRes['url'])[5])[0] : ''];
    }

    //-------推送相关----
    public function gamblePush($userInfo,$gameInfo,$param)
    {
        $userid = $userInfo['id'];
        $redis  = connRedis();
        $union_name = $gameInfo['union_name'][0];
        $home_team_name = $gameInfo['home_team_name'][0];
        $away_team_name = $gameInfo['away_team_name'][0];

        if($union_name != '' && $away_team_name != '' && $away_team_name != ''){
            $content = "您关注的用户 {$userInfo['nick_name']} 发布推荐啦，{$union_name} {$home_team_name} VS {$away_team_name}, 马上查看";

            //友盟
            $alias = DM('FollowUser')->field('user_id')->where(['follow_id' => $userid]) ->getField('user_id',true);

            foreach ($alias as $k => $v) {
                //mqtt推送红点提示
                $opt = [
                    'topic'    => 'qqty/' . $v . '/userNotify',
                    'payload'  => [
                        'status'  => 1,
                        'data'    => ['userId' => $v, 'type' => 1], 
                        'randKey' => $k.microtime(true).rand(0, 1000)
                    ],
                    'clientid' => md5(time() . $v),
                    'qos'      => 1
                ];
                $data = json_encode($opt);
                $redis->lPush('mqtt_common_push_queue', $data);
                //Mqtt($opt);
                //保存redis记录红点24小时
                $redis->set('qqty/' . $v . '/userNotify/1',$v,86400);
            }

            $chunk_alias = array_chunk($alias, 20);
            if($chunk_alias){
                foreach($chunk_alias as $ck => $cv){
                    $custom = [
                        'um_module' => [
                            'module'    => '10',
                            'value'     => $userid,
                            'show_type' => 1,
                            'alias'     => $cv,
                            'alias_type'=>'QQTY'
                        ]
                    ];

                    $payloadBody = [
                        'ticker'        => $content,
                        'title'         => $content,
                        'text'          => $content,
                        'alias'         => implode(',', $cv),
                        'play_vibrate'  => "true",
                        'play_lights'   => "true",
                        'play_sound'    => "true",
                        'after_open'    => 'go_custom',
                        'custom'        => json_encode($custom),
                    ];
                    $redis->rpush('umeng_user_gameball_push_queue', json_encode($payloadBody));
                }
            }

            //APNS
            $sub_users = M('FollowUser')->where(['follow_id' => $userid, 'sub' => 1]) ->getField('user_id',true);
            foreach ($sub_users as $k => $sub_id) {
                $ApnsUser = M('ApnsUsers')->where(['user_id' => $sub_id])->find();
                if($ApnsUser && $ApnsUser['cert_no']){
                    $pushMsg = json_encode([
                        'device_token' => $ApnsUser['device_token'],
                        'content' => $content,
                        'pub_id' => $userid,
                        'cert_no' => $ApnsUser['cert_no']
                    ]);

                    $redis->rpush('apns_user_gameball_push_queue', $pushMsg);
                }
            }
        }
    }

    /**
     * 计算用户剩余推荐的场次
     * @param  int $userid    用户id
     * @param  int $gameType  赛程类型 1：足球，2：篮球  默认1
     * @param  int $playType  玩法 1：亚盘，2：竞彩
     * @return array          剩余的次数
     */
    public function gambleLeftTimes($userid,$gameType=1,$playType=1)
    {
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');

        $blockTime   = getBlockTime($gameType,$gamble=true);

        $where['user_id']     = $userid;
        $where['create_time'] = ['between',[$blockTime['beginTime'],$blockTime['endTime']]];

        if($playType == 1 && $gameType == 1) //亚盘
        {
            $where['play_type'] = ['in',[1,-1]];
        }
        elseif($playType == 2 && $gameType == 1) //竞彩
        {
            $where['play_type'] = ['in',[2,-2]];
        }

        $gambleList = $GambleModel->master(true)->field(['game_id,play_type,chose_side'])->where($where)->select();

        switch ($gameType) {
            case '1': 
                $sign = $playType == 1 ? 'fbConfig' : 'betConfig';  
                break;
            case '2': $sign = 'bkConfig';  break;
        }
        $gameConf    = getWebConfig($sign);
        if (in_array(date('N',$blockTime['beginTime']),[1,2,3,4,5])) //周1-5
        {
            $normTimes = $gameConf['weekday_norm_times'];
            //$imptTimes = $gameConf['weekday_impt_times'];
        }
        else
        {
            $normTimes = $gameConf['weekend_norm_times'];
            //$imptTimes = $gameConf['weekend_impt_times'];
        }

        $normVoteTimes = count($gambleList);
        $imptVoteTimes = 0;
        // foreach ($gambleList as $v)
        // {
        //     if ($v['is_impt'] == 0)
        //         $normVoteTimes ++;

        //     if ($v['is_impt'] == 1)
        //         $imptVoteTimes ++;
        // }
        $normLeftTimes = $normTimes - $normVoteTimes;
        return [$normLeftTimes,$imptVoteTimes,$gameConf,$gambleList];
    }

    /**
     * 计算推荐胜率或更多详情
     *
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $dateType  时间类型(1:周胜率 2:月胜率 3:季胜率 4:日胜率 默认为1)
     * @param bool $more      更多详情记录(flase:否 true:是 默认为否)
     * @param bool $isCount   是否只计算推荐场数(flase:否 true:是 默认为否)
     * @param int  $playType  玩法(1:让分;-1:大小 默认为0，不分玩法)
     * @param int  $gambleType  推荐玩法(1:亚盘;2:竞彩 默认为亚盘1)
     * @return int or array  #
    */
    public function CountWinrate($id,$gameType=1,$dateType=1,$more=false,$isCount=false,$playType=0,$gambleType=1)
    {
        //日期筛选
        list($begin,$end) = getRankBlockDate($gameType,$dateType);
        
        $gameModel = $gameType == 1 ? M('gamble') : M('gamblebk');

        //查询推荐数据
        $where['user_id']    = $id;
        $where['result']     = array("IN",array('1','0.5','2','-1','-0.5'));

        //加上对应时间
        $time = $gameType == 1 ? (10*60+32)*60 : (12*60)*60;

        $where['create_time']  = array( "between",array( strtotime($begin) + $time, strtotime($end) + 86400 + $time ) );
        if($dateType == 4) //日榜时间条件
        {
            $blockTime  = getBlockTime($gameType,$gamble=true);
            $end        = date('Ymd', $blockTime['beginTime'] - 86400);
            $where['create_time'] = ['between',[$blockTime['beginTime']-86400,$blockTime['endTime']-86400]];
        }

        //竞彩
        if($gameType == 1){
            $where['play_type'] = ($gambleType == 1) ? ['in', [-1,1]] : ['in', [-2,2]];
        }

        if($playType){
            $where['play_type'] = (int)$playType;
        }

        if($isCount){
            return $gameModel->where($where)->field("create_time")->select(); //只计算推荐场数
        }

        $gambleArr = $gameModel->field(['result','earn_point'])->where($where)->order('id desc')->select();

        //计算胜率
        $win        = 0;
        $half       = 0;
        $level      = 0;
        $transport  = 0;
        $donate     = 0;
        $pointCount = 0;

        foreach ($gambleArr as $k => $v)
        {
            if($v['result'] == '1'){
                $win++;
            }
            if($v['result'] == '0.5'){
                $half++;
            }
            if($v['result'] == '2'){
                $level++;
            }
            if($v['result'] == '-1'){
                $transport++;
            }
            if($v['result'] == '-0.5'){
                $donate++;
            }
            if($v['earn_point'] > 0){
                $pointCount += $v['earn_point'];
            }
        }

        $curr_victs = 0; //当前连胜

        foreach ($gambleArr as $v)
        {
            if($v['result'] == 1 || $v['result'] == 0.5)
                $curr_victs++;

            if($v['result'] == -1 || $v['result'] == -0.5)
                break;
        }

        $winrate = getGambleWinrate($win,$half,$transport,$donate);

        //获取详细推荐记录
        if ($more)
        {
            $count = count($gambleArr);
            return array(
                "winrate"    =>  $winrate,
                'count'      =>  $count,
                'win'        =>  $win,
                'half'       =>  $half,
                'level'      =>  $level,
                'transport'  =>  $transport,
                'donate'     =>  $donate,
                'pointCount' =>  $pointCount,
                'begin_date' =>  $begin,
                'end_date'   =>  $end,
                'user_id'    =>  $id,
                'gameCount'  =>  $count,
                'curr_victs' =>  $curr_victs,
            );
        }

        return $winrate;
    }

    /**
     * 获取近十场推荐结果
     * @param int  $id        会员id
     * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
     * @param int  $playType  玩法，1：亚盘；2：竞彩，默认亚盘
     * @return  array
    */
    public function getTenGamble($id,$gameType=1,$playType=1){
        $where['user_id']    = $id;

        if($playType == 1){//亚盘
            $where['result']    = ['in',[1,0.5,2,-1,-0.5]];
            $where['play_type'] = ['in', ($gameType == 1) ? [1,-1] : [1,2,-1,-2]];//篮球再细分
        }else if($playType == 2 && $gameType == 1){//足球竞彩
            $where['result']    = ['in',[1,-1]];
            $where['play_type'] = ['in',[2,-2]];
        }

        //赛事类型
        $Model = $gameType == 1 ? M('gamble') : M('gamblebk');
        $tenArray = (array)$Model->where($where)->order("id desc")->limit(10)->getField('result',true);

        return $tenArray;
    }

    /**
     * @param int $gameType             赛事类型(1:足球   2:篮球   默认为1)
     * @param int $dateType             时间类型(1:周胜率 2:月胜率 3:季胜率 默认为1)
     * @param null $user_id             是否查找指定用户,默认为否
     * @param bool|false $more          获取最近10场、连胜记录,默认为否
     * @param string $page              页数
     * @param string $pageNum           每页条数
     * @param bool|false $todayGamble   是否筛选今日有推荐
     * @return bool|mixed|string|void
     */
    public function getRankingData($gameType = 1, $dateType = 1, $user_id = null, $more = false, $page = '', $pageNum = '', $todayGamble = false)
    {
        $cacheKey = 'api_ranking_game_rank:' . implode('', func_get_args());
        if($Ranking = S($cacheKey))
            return $Ranking;

        list($begin, $end) = getRankDate($dateType);

        $where['r.gameType']    = $gameType;
        $where['r.dateType']    = $dateType;
        $where['r.begin_date']  = $begin;
        $where['r.end_date']    = $end;

        //查看是否有上周/月/季的数据
        $count = M('rankingList r')->where($where)->count();

        if (!$count) {
            list($begin, $end)      = getTopRankDate($dateType);  //获取上上周的数据
            $where['r.begin_date']  = $begin;
            $where['r.end_date']    = $end;
        }

        if ($user_id)
            $where['r.user_id'] = $user_id;

        $field = [
            'r.user_id', 'r.ranking', 'r.gameCount', 'r.win', 'r.half', 'r.`level`','r.transport',
            'r.donate', 'r.winrate', 'r.pointCount', 'f.nick_name', 'f.head', 'f.lv', 'f.lv_bk'
        ];

        $gambleModel = $gameType == 2 ? '__GAMBLEBK__' : '__GAMBLE__';

        if ($page && $pageNum) //是否分页
        {
            if ($todayGamble)  //这里只判断排行榜的是否筛选今日有推荐的用户
            {
                $blockTime = getBlockTime($gameType, $gamble = true);
//                $where['g.play_type']   = ['IN', [1, -1]];
//                $Ranking = M('rankingList r')
//                    ->field($field)
//                    ->join('left join '. $gambleModel .' g on g.user_id = r.user_id')
//                    ->join('left join qc_front_user f on f.id = r.user_id')
//                    ->where(array_merge($where, ['g.create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]]))
//                    ->group('r.user_id')
//                    ->order('r.ranking')
//                    ->page($page . ',' . $pageNum)
//                    ->select();

                $tempRank = (array)M('rankingList r')
                    ->field($field)
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->where($where)
                    ->order('r.ranking')
//                    ->limit(($page - 1) * $pageNum, $pageNum*25)
                    ->select();

                foreach($tempRank as $k=>$v){
                    $userids[] = $v['user_id'];
                }

                $gambleTb2   = $gameType == 2 ? 'Gamblebk' : 'Gamble';
                $w = ['user_id' => ['IN', $userids], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]],'play_type' => ['IN', [1, -1]]];
                $gm = M($gambleTb2)->field('user_id')->where($w)->getField('user_id', true);
                $gambleUsers = array_unique($gm);

                foreach($tempRank as $k2=>$v2){
                    if(in_array($v2['user_id'], $gambleUsers)){
                        $rank1[] = $v2;
                    }
                }

                $Ranking = array_slice($rank1, ($page - 1) * $pageNum, $pageNum);
            } else {
                $Ranking = M('rankingList r')
                    ->join('left join qc_front_user f on f.id = r.user_id')
                    ->field($field)
                    ->where($where)
                    ->order("ranking asc")
                    ->page($page . ',' . $pageNum)
                    ->select();
            }
        } else {
            $Ranking = M('rankingList r')
                ->join('left join qc_front_user f on f.id = r.user_id')
                ->field($field)
                ->where($where)
                ->order("ranking asc")
                ->select();
        }

        if ($more) {
            foreach ($Ranking as $k => $v) {
                $Ranking[$k]['nick_name']   = M('FrontUser')->where(['id' => $v['user_id']])->getField('nick_name');
                $Ranking[$k]['tenArray']    = $this->getTenGamble($v['user_id'], $dateType);
                $Ranking[$k]['Winning']     = $this->getWinning($v['user_id'], $gameType);
            }
        }

        //缓存
        S($cacheKey, $Ranking, 298);

        return $Ranking;
    }

    /**
    * 获取连胜记录 当前连胜和最大连胜
    * @param int  $id        会员id
    * @param int  $gameType  赛事类型(1:足球   2:篮球   默认为1)
    * @param int  $playType  玩法(1:让分;-1:大小 默认为0，不分玩法 )
    * @param int  $gambleType  推荐玩法(1:亚盘;2:竞彩 默认为亚盘1)
    * @param int  $limit
    * @return  array
    */
    public function getWinning($id,$gameType=1,$playType=0,$gambleType=1,$limit = 30)
    {
        $GambleModel = $gameType == 1 ? M('Gamble') : M('Gamblebk');
        $where['user_id'] = $id;

        //竞彩足球
        if($gameType == 1){
            $where['play_type'] = ($gambleType == 1) ? ['in', [-1,1]] : ['in', [-2,2]];
        }

        if($playType){
            $where['play_type'] = (int)$playType;
        }

        if($limit){
            $where['result']  = ['neq',0];
            $gamble = (array)$GambleModel->where($where)->order("id desc")->limit(30)->getField('result',true);
        }else{
            $gamble = (array)$GambleModel->where($where)->order("id desc")->getField('result',true);
        }

        $tenGamble = [];

        foreach ($gamble as $k => $v) {
            if(in_array($v,array('1','0.5','2','-1','-0.5'))){
                $tenGamble[] = $v;
            }
            if(count($tenGamble) >= 10) break;
        }

        $tenGambleRate  = countTenGambleRate($tenGamble, $gambleType)/10;//近十场的胜率;

        $curr_victs = 0; //当前连胜

        foreach ($gamble as $v)
        {
            if($v == 1 || $v == 0.5)
                $curr_victs++;

            if($v == -1 || $v == -0.5)
                break;
        }

        $temp = $max_victs = $win = $level = $transport = 0;

        foreach ($gamble as $v)
        {
            if ($v == 1 || $v == 0.5) //赢、赢半
            {
                $temp++;
                if ($temp > $max_victs)
                    $max_victs = $temp;

                $win++;
            }
            else if ($v == -1 || $v == -0.5) //输、输半
            {
                $temp = 0;
                $transport++;
            }
            else if ($v == 2) //平
            {
                $level++;
            }
            else //其他推迟取消的
            {
                continue;  //需考虑推迟、取消的赛程结果值为-14,-13等
            }
        }

        unset($where['result']);
        return [
            'curr_victs'  => (string)$curr_victs,
            'max_victs'   => (string)$max_victs,
            'total_times' => count($gamble),//推荐总场次包括未结算的
            'win'         => (string)$win,
            'level'       => (string)$level,
            'transport'   => (string)$transport,
            'tenGambleRate' => (string)$tenGambleRate,
            'tenGambleArr'  => $tenGamble,
        ];
    }

    /**
     * 获取用户足球推荐记录
     * @param  mixed  $userid     用户id
     * @param  mixed  $playType   玩法(1:让分;-1:大小;2大球;-2小球 默认为0，)
     * @param  int    $page       页数
     * @param  int    $gamble_id  默认0
     * @param   int   $gambleType 推荐玩法(1:亚盘;2:竞彩 默认为亚盘1)
     * @param   int   $gameType  推荐玩法(1:足球;2:篮球 默认为足球1)
     * @param   bool   $getTotals  获取总记录数
     * @return mixed              记录列表
     */
    public function getGambleList($userid, $playType = 0, $page = 1, $gamble_id = 0, $gambleType = 0, $gameType=1,$getTotals = false)
    {
        $pageNum  = 10;
        $page     = $page.','.$pageNum;
        $field = [
            'g.id gamble_id', 'g.user_id', 'g.game_id', 'g.union_name', 'g.home_team_name', 'g.away_team_name',
            'g.game_date', 'g.game_time', 'gf.score gf_score', 'gf.half_score gf_half_score', 'g.score', 'g.half_score',
            'g.play_type', 'g.chose_side', 'g.handcp', 'g.odds', 'g.result', 'g.tradeCoin', 'g.vote_point', 'g.earn_point',
            'g.create_time', 'g.`desc`', 'g.`desc_check`','qu.union_color', 'gf.game_state','(g.quiz_number + g.extra_number) as quiz_number','g.voice','g.is_voice','g.voice_time'
        ];

        if($gameType == 1){
            $GambleModel = M('Gamble g');
            $infoTbs    = 'qc_game_fbinfo';
            $unionTbs   = 'qc_union';
            $field[]    = 'gf.bet_code';
            if($gambleType){
                $where['play_type'] = $gambleType == 1 ? ['in', [-1,1]] : ['in', [-2,2]];
            }
        }else{
            $GambleModel = M('Gamblebk g');
            $infoTbs = 'qc_game_bkinfo';
            $unionTbs = 'qc_bk_union';
        }

        if($playType){
            $where['play_type'] = $playType;
        }

        if($gamble_id){
            $where['g.id'] = ['lt', (int)$gamble_id];
            $page = '1, 10';//LIMIT 10
        }
        $where['user_id'] = is_array($userid) ? ['IN',$userid] : $userid;

        if($getTotals === true){
            return (string)$GambleModel->where($where)->count();
        }

        $list = $GambleModel->field($field)
            ->join("LEFT JOIN ".$unionTbs." AS qu ON g.union_id = qu.union_id")
            ->join("LEFT JOIN ".$infoTbs." AS gf ON g.game_id = gf.game_id")
            ->where($where)
            ->page($page)
            ->order('gamble_id desc')
            ->group('gamble_id')
            ->select();

        foreach ($list as $k => $v)
        {
            if($v['voice'] != '' && $v['is_voice'] == '1' ){
                $list[$k]['voice'] = C('IMG_SERVER') . $v['voice'];
            }else{
                $list[$k]['voice'] = '';
            }

            if($v['desc_check'] != '' && $v['desc_check'] == '1' ){
                $list[$k]['desc'] = $v['desc'];
            }else{
                $list[$k]['desc'] = '';
            }

            if (in_array($list[$k]['game_state'], [-1,4,5]))
            {
                $list[$k]['score']      = $list[$k]['gf_score'] ?:'';
                $list[$k]['half_score'] = $list[$k]['gf_half_score'] ?:'';
            }

            unset($list[$k]['gf_score']);
            unset($list[$k]['gf_half_score']);
            unset($list[$k]['is_voice']);

            $list[$k]['union_name']     = explode(',', $v['union_name']);
            $list[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $list[$k]['away_team_name'] = explode(',', $v['away_team_name']);

            $list[$k]['bet_code']       = $v['bet_code'] ?:'';
            $list[$k]['union_color']    = (string)$v['union_color'];
            $list[$k]['score']          = (string)$v['score'];
            $list[$k]['half_score']     = (string)$v['half_score'];
            $list[$k]['earn_point']     = (string)$v['earn_point'];

            //判断比赛异常情况，根据实时情况，未完场都是未结算
            if(in_array($v['game_state'], array(1,2,3))){
                $list[$k]['result'] = 0;
            }

            //购买人数
            $list[$k]['quiz_number'] = D('Common')->getQuizNumber($v['quiz_number']);;
        }


        return empty($list) ? array() : $list;
    }

    /**
     * 获取单场推荐的信息
     * @param  int $gambleId 推荐记录id
     * @param  int $game_type 默认1：足球；2篮球
     * @return arr
     */
    public function getGambleInfo($gambleId, $game_type=1)
    {
        $field = [
            'g.id gamble_id',
            'g.user_id',
            'g.union_name',
            'g.home_team_name',
            'g.away_team_name',
            'g.game_date',
            'g.game_time',
            'g.score',
            'g.half_score',
            'g.play_type',
            'g.chose_side',
            'g.handcp',
            'g.odds',
            'g.tradeCoin',
            'g.desc',
            'g.desc_check',
            'g.result',
            'IF(is_voice=1, voice, \'\') as voice',
            'voice_time',
            'qu.union_color'
        ];

        $model = $game_type == 1 ? M('Gamble'): M('Gamblebk');
        $union = $game_type == 1 ? 'qc_union' : 'qc_bk_union';

        $info = $model->alias('g')->join("LEFT JOIN {$union} AS qu ON g.union_id = qu.union_id")
                ->field($field)->where(['g.id'=>$gambleId])->find();

        //旧表没有就去查新表
        if(!$info && $game_type == 1){
            $info = M('GambleReset')->alias('g')->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                    ->field($field)->where(['g.id'=>$gambleId])->find();
        }

        $info['union_name']     = explode(',', $info['union_name']);
        $info['home_team_name'] = explode(',', $info['home_team_name']);
        $info['away_team_name'] = explode(',', $info['away_team_name']);
        $info['voice']          = $info['voice'] ? Tool::imagesReplace($info['voice']) : '';
        $info['desc']           = $info['desc_check'] != '1' ? '' : $info['desc'];

        return $info;
    }

    /**
     * 获取足球竞彩盘口和赔率
     * @param  $param 推荐数据
     * @return 传值引用
     */
    public function getHandcpAndOddsBet(&$param){
        $fbBetodds = $this->getSportteryGoal($param['game_id']);
        switch ($param['play_type']) {
            case '2':
                $param['handcp'] = 0;
                switch ($param['chose_side']) {
                    case '1':  $param['odds'] = $fbBetodds[0]; break;
                    case '0':  $param['odds'] = $fbBetodds[1]; break;
                    case '-1': $param['odds'] = $fbBetodds[2]; break;
                }
                break;
            case '-2':
                $param['handcp'] = $fbBetodds[6];
                switch ($param['chose_side']) {
                    case '1':  $param['odds'] = $fbBetodds[3]; break;
                    case '0':  $param['odds'] = $fbBetodds[4]; break;
                    case '-1': $param['odds'] = $fbBetodds[5]; break;
                }
                break;
        }
        //记录推荐时赔率
        $param['odds_other'] = json_encode([
            'home_odds'    => $fbBetodds[0],
            'draw_odds'    => $fbBetodds[1],
            'away_odds'    => $fbBetodds[2],
            'home_letodds' => $fbBetodds[3],
            'draw_letodds' => $fbBetodds[4],
            'away_letodds' => $fbBetodds[5],
        ]);
    }

    /**
     * 获取篮球盘口和赔率
     * @param  $param 推荐数据
     * @return 传值引用
     */
    public function getHandcpAndOddsBk(&$param,$company=3){
        //获取mongo数据
        $odds = $this->getBkOdds('',$param['game_id']);
        if(!empty($odds))
        {
            switch ($param['play_type']) {
                case '1':
                    //让球
                    $handcp    = $odds[0];
                    $home_odds = $odds[1];
                    $away_odds = $odds[2];
                    break;
                case '-1':
                    //大小
                    $handcp    = $odds[3];
                    $home_odds = $odds[4];
                    $away_odds = $odds[5];
                    break;
            }
            $param['handcp'] = $handcp;
            switch ($param['chose_side']) {
                case '1':
                    $param['odds'] = $home_odds;
                    $param['odds_other'] = $away_odds;
                    break;
                case '-1':
                    $param['odds'] = $away_odds;
                    $param['odds_other'] = $home_odds;
                    break;
            }
        }
    }

    /**
     * 获取足球盘口和赔率
     * @param  $param 推荐数据
     * @return 传值引用
     */
    public function getHandcpAndOdds(&$param){
        //获取实时赔率
        $appfbService = new \Home\Services\AppfbService();
        $oddsArr = $appfbService->fbOdds((int)$param['game_id']);
        $odds = $oddsArr[$param['game_id']];

        if(empty($odds)){
            //获取初盘
            $mongo = mongoService();
            $game  = $mongo->fetchRow('fb_game',['game_id'=>(int)$param['game_id']],['match_odds']);
            //取初盘
            $match_odds = $game['match_odds'][3];
            if(!empty($match_odds)){
                $odds[0]   = str_replace(' ', '', $match_odds[0]);    //主队亚盘初盘赔率
                $odds[1]   = changeExpStrToNum(changeSnExpTwo(str_replace(' ', '', $match_odds[1])));    //亚盘初盘盘口
                $odds[2]   = str_replace(' ', '', $match_odds[2]);    //客队亚盘初盘赔率
                $odds[6]   = str_replace(' ', '', $match_odds[12]);   //主队大小初盘赔率
                $odds[7]   = changeExpStrToNum(str_replace(' ', '', $match_odds[13]));   //大小初盘盘口
                $odds[8]   = str_replace(' ', '', $match_odds[14]);   //客队大小初盘赔率
            }
        }

        if(!empty($odds)){
            //玩法play_type 1:让球  -1:大小
            switch ($param['play_type']) {
                case '1':
                    //让球
                    $home_odds = $odds[0];
                    $handcp    = $odds[1];
                    $away_odds = $odds[2];
                    break;
                case '-1':
                    //大小
                    $home_odds = $odds[6];
                    $handcp    = $odds[7];
                    $away_odds = $odds[8];
                    break;
            }
            $param['handcp'] = $handcp;
            //选择主客对应赔率
            switch ($param['chose_side']) {
                case '1':
                    $param['odds']       = $home_odds; //主
                    $param['odds_other'] = $away_odds; 
                    break;
                case '-1':
                    $param['odds']       = $away_odds; //客
                    $param['odds_other'] = $home_odds; 
                    break;
            }
        }
    }

    /**
     * V3.0首页——“筛选命中率高”、“连胜数多”的用户，分亚盘和竞彩
     */
    public function getIndexUser($game_type, $user1=0, $gamble_type1=0){
        if(!S('lastUserGamble'.MODULE_NAME.$game_type)) {
            $dateType = 1;
            if ($game_type == 1) {
                $userGamble  = $this->getIndexUserData($dateType, 0, 1, $game_type);//亚盘
                $userBetting = $this->getIndexUserData($dateType, 0, 2, $game_type);//竞彩

                //缓存15分钟
                S('lastUserGamble' . MODULE_NAME . $game_type, json_encode($userGamble), 60 * 15);
                S('lastUserBetting' . MODULE_NAME . $game_type, json_encode($userBetting), 60 * 15);
            } else {
                $userGamble = $this->getIndexUserData($dateType, 0, 1, $game_type);//亚盘
                S('lastUserGamble' . MODULE_NAME . $game_type, json_encode($userGamble), 60 * 15);
            }
        }else{//有缓存
            if($game_type == 1){
                $userGamble  = S('lastUserGamble'.MODULE_NAME.$game_type);
                $userBetting = S('lastUserBetting'.MODULE_NAME.$game_type);
            }else{
                $userGamble  = S('lastUserGamble'.MODULE_NAME.$game_type);
            }
        }

        //换一批
        if($user1 && $gamble_type1){
            $lastUserArr[0]['user_id']     = $user1;
            $lastUserArr[0]['gamble_type'] = $gamble_type1;
        }else{
            $lastUserArr = S('lastUserArr'.MODULE_NAME.$game_type) ?: '';
        }

        $lastUser = $lastUserArr[0]['user_id'] ?: 0;//上一个高命中用户
        $lastType = $lastUserArr[0]['gamble_type'] ?: 0;//上一个高命中的推荐类型，1：亚盘；2：竞彩

        if($game_type == 1){
            if($lastType == 0 || $lastType == 2){//首次和上次是竞彩都是亚盘
                $userArr   = $userGamble[0];
                $victsUser = $userBetting[1];
            }else if($lastType == 1){//上次亚盘，这次竞彩
                $userArr   = $userBetting[0];
                $victsUser = $userGamble[1];
            }
        }else{//篮球每次都是亚盘
            $userArr   = $userGamble[0];
            $victsUser = $userGamble[1];
        }

        //命中率高
        if ($lastUser) {
            $res1 = array_rand(array_diff($userArr, array($lastUser)));
        } else {//第一次请求接口，默认先亚盘高命中，竞彩连胜多
            $res1 = array_rand($userArr);
        }

        //连胜数多
        $res2 = array_rand(array_diff($victsUser, array($userArr[$res1])));

        $res = M('FrontUser')->field('id as user_id, nick_name, head as face, lv, lv_bk, lv_bet')->where(['id' => ['in', array((int)$userArr[$res1], (int)$victsUser[$res2])]])
                ->order('field(id, ' . (int)$victsUser[$res2] . ',' . (int)$userArr[$res1] . ') DESC')->select();

        if($game_type == 1){
            if($lastType == 1){
                $res[0]['gamble_type'] = 2;
                $res[1]['gamble_type'] = 1;
            }else {
                $res[0]['gamble_type'] = 1;
                $res[1]['gamble_type'] = 2;
            }
        }else{
            $res[0]['gamble_type'] = $res[1]['gamble_type'] = 1;
        }

        foreach ($res as $k => $v) {
            $res[$k]['game_type'] = $game_type;
            $res[$k]['face']      = frontUserFace($v['face']);
            if($game_type == 1){
                $res[$k]['weekPercnet']   = $v['gamble_type'] == 1 ? $userGamble[2][$v['user_id']] : $userBetting[2][$v['user_id']];//取周榜的
                $res[$k]['monthPercnet']  = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 2, false, false, 0, $v['gamble_type']);//月胜率
                $res[$k]['tenGambleRate'] = $v['gamble_type'] == 1 ? (string)($userGamble[3][$v['user_id']]) : (string)($userBetting[3][$v['user_id']]);//命中率
                $res[$k]['curr_victs']    = $v['gamble_type'] == 1 ? $userGamble[4][$v['user_id']] : $userBetting[4][$v['user_id']];//连胜
                $res[$k]['win']           = $v['gamble_type'] == 1 ? (string)$userGamble[5][$v['user_id']] : (string)$userBetting[5][$v['user_id']];//总胜数
            }else{
                $res[$k]['weekPercnet']   = $userGamble[2][$v['user_id']];//取周榜的
                $res[$k]['monthPercnet']  = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 2, false, false, 0, $v['gamble_type']);//月胜率
                $res[$k]['tenGambleRate'] = (string)($userGamble[3][$v['user_id']]);//命中率
                $res[$k]['curr_victs']    = $userGamble[4][$v['user_id']];//连胜
                $res[$k]['win']           = (string)$userGamble[5][$v['user_id']];//总胜数
            }
        }

        S('lastUserArr'.MODULE_NAME.$game_type, json_encode($res), 60 * 15);

        return $res;
    }

    /**
     * V3.0首页高命中和连胜多的数据获取
     */
    public function getIndexUserData($dateType, $rankDate=0, $playType, $game_type=1){
        $blockTime = getBlockTime($game_type, $gamble = true);//获取赛程分割日期的区间时间

        $where['r.dateType'] = $dateType;
        $where['r.gameType'] = $game_type;
        if($playType == 1){
            $table = M('RankingList r');
            $sort_field = 'end_date';
        }else if($playType == 2){
            $table = M('RankBetting r');
            $sort_field = 'listDate';
        }
        $where['r.id'] = ['gt', 0];
        $rankDate = $table->where($where)->order('id desc')->limit(1)->getField($sort_field);
        $where['r.'.$sort_field] = $rankDate;
        $where['f.status'] = 1;
        $arr = $table->join("LEFT JOIN qc_front_user f on f.id = r.user_id")->where($where)->order('ranking ASC')->limit(50)->getField('user_id, winrate', true);

        $userArr = $victsUser = array_keys($arr);
        $tenGambleRateArr = $victsArr = $winArr = $todayGambleSort = array();

        $model = $game_type == 1 ? M('Gamble') : M('Gamblebk');
        foreach ($userArr as $k => $v) {
            //连胜数多
            $winnig = D('GambleHall')->getWinning($v, $game_type, 0, $playType, 30); //连胜记录
            $victsArr[$v] = $winnig['curr_victs'];//连胜场数
            $winArr[$v]   = $winnig['win'];//胜场数

            //命中率高
            $tenGambleRateArr[$v] = $winnig['tenGambleRate'];//近十场的胜率;

            //今天是否有推荐
            $todayGambleSort[] = $model->where(['user_id' => $v, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->getField('id') ? 1 : 0;

            unset($tenGamble, $winnig);
        }

        array_multisort($todayGambleSort, SORT_DESC, array_values($tenGambleRateArr), SORT_DESC, array_values($arr), SORT_DESC, $userArr);
        array_multisort($todayGambleSort, SORT_DESC, array_values($victsArr), SORT_DESC, array_values($arr), SORT_DESC, $victsUser);

        $userArr   = array_slice($userArr, 0, 10);//命中率高
        $victsUser = array_slice($victsUser, 0, 10);//连胜数多

        return [$userArr, $victsUser, $arr, $tenGambleRateArr, $victsArr, $winArr];
    }

    /**
     * V5.1首页新版——高手推荐
     */
    public function masterGamble($game_type=1){
        $dateType = 1;
        $rankDate = 0;
        //10:32分都是昨天的
        if($game_type == 1){
            $userGamble  = $this->masterGambleData($dateType, $rankDate, 1, 5);//亚盘
            $userBetting = $this->masterGambleData($dateType, $rankDate, 2, 5);//竞彩
        }else{
            //篮球
            $userGamble = $this->masterGambleData($dateType, $rankDate, 1, 10, '', $game_type);//亚盘
        }

        //重新排序，按发布时间
        $totalArr = ($game_type == 1) ? array_merge($userGamble, $userBetting) : $userGamble;
        $today    = $curr_victs1 = $tenGambleRate1 = $weekPercnet1 = $tradeCoin1 = $timeSort1 = array();
        $before   = $curr_victs2 = $tenGambleRate2 = $weekPercnet2 = $tradeCoin2 = $timeSort2 = array();

        foreach($totalArr as $k => $v){
            $gambleType= in_array($v['play_type'], [-1,1]) ? 1 : 2;//推荐类型
            // $winnig    = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $gambleType, 30); //连胜记录
            // $totalArr[$k]['tenGambleRate']  = $winnig['tenGambleRate'];//近十场的胜率;
            // $totalArr[$k]['curr_victs']     = $winnig['curr_victs'];//连胜场数
            $totalArr[$k]['face']           = frontUserFace($v['face']);
            $totalArr[$k]['weekPercnet']    = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0, $gambleType);//周胜率
            $totalArr[$k]['union_name']     = explode(',', $v['union_name']);
            $totalArr[$k]['home_team_name'] = explode(',', $v['home_team_name']);
            $totalArr[$k]['away_team_name'] = explode(',', $v['away_team_name']);
            $totalArr[$k]['desc']           = (string)$v['desc'];
            $totalArr[$k]['score']          = (string)$v['score'];
            $totalArr[$k]['voice']          = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
            $totalArr[$k]['game_type']      = $game_type;
//            $totalArr[$k]['quiz_number']    = D('Common')->getQuizNumber($v['quiz_number']);
            if(iosCheck()) $totalArr[$k]['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $totalArr[$k]['nick_name']);

            unset($winnig);

            //当天的分组
            if($v['result'] == 0){
                $curr_victs1[]    = $totalArr[$k]['curr_victs'];
                $tenGambleRate1[] = $totalArr[$k]['tenGambleRate'];
                $weekPercnet1[]   = $totalArr[$k]['weekPercnet'];
                $tradeCoin1[]     = $v['tradeCoin'];
                $timeSort1[]      = $v['create_time'];
                $today[]          = $totalArr[$k];
            }else{
                $curr_victs2[]    = $totalArr[$k]['curr_victs'];
                $tenGambleRate2[] = $totalArr[$k]['tenGambleRate'];
                $weekPercnet2[]   = $totalArr[$k]['weekPercnet'];
                $tradeCoin2[]     = $v['tradeCoin'];
                $timeSort2[]      = $v['create_time'];
                $before[]         = $totalArr[$k];
            }
        }

        //排序，分组排序，当天时间优先，10中几 > 按连胜 > 周胜率 > 价格 > 发布时间
        array_multisort($tenGambleRate1, SORT_DESC, $curr_victs1, SORT_DESC, $weekPercnet1, SORT_DESC, $tradeCoin1, SORT_DESC, $timeSort1, SORT_DESC, $today);
        array_multisort($tenGambleRate2, SORT_DESC, $curr_victs2, SORT_DESC, $weekPercnet2, SORT_DESC, $tradeCoin2, SORT_DESC, $timeSort2, SORT_DESC, $before);

        //合并
        $totalArr = array_merge($today, $before);
        unset($curr_victs1, $tenGambleRate1, $weekPercnet1, $tradeCoin1, $timeSort1, $today);
        unset($curr_victs2, $tenGambleRate2, $weekPercnet2, $tradeCoin2, $timeSort2, $before);

        return $totalArr;
    }

    /**
     * 获取高手竞彩不同类型的数据
     */
    public function masterGambleData($dateType, $rankDate=0, $playType, $limit, $lastUser='', $game_type=1){
        $iosCheck = 0;
        if ($playType == 1) {
            //亚盘
            $rankTable = M('rankingList');
            $sort_field = 'end_date';
            $where['g.play_type'] = ['in', ($game_type == 1) ? [1,-1] : [1,2,-1,-2]];//篮球再细分
        } else if ($playType == 2) {
            //竞彩
            $rankTable = M('rankBetting');
            $sort_field = 'listDate';
            $where['g.play_type'] = ['in', [-2, 2]];
        }

        $where['g.tradeCoin'] = $iosCheck? 0 : ['gt', 0];
        $where['g.result']    = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）
        //$where['g.create_time'] = ['gt', strtotime('-2 day', strtotime('10:32'))];
        $blockTime = getBlockTime($game_type, $gamble = true);//获取竞猜分割日期的区间时间
        $where['g.create_time'] = ['between', [$blockTime['beginTime'], $blockTime['endTime']]];

        //排除第一次查的用户
        if($lastUser)
            $where['g.user_id'] = ['not in', $lastUser];

        if(!$rankUser = S('rankUser:'.$dateType.$playType.$game_type)){
            //排行榜前100名
            $rankWhere['dateType']    = $dateType;
            $rankWhere['gameType']    = $game_type;
            $rankDate = $rankTable->where($rankWhere)->order("$sort_field desc")->limit(1)->getField($sort_field);
            $rankWhere[$sort_field]   = $rankDate;
            $rankWhere['f.status']    = 1;
            $limit    = $iosCheck ? 300 : 100;
            $rankUser = $rankTable->alias('r')->join("LEFT JOIN qc_front_user f on f.id = r.user_id")->where($rankWhere)->limit($limit)->order('ranking asc')->getField('ranking,user_id',true);
            S('rankUser:'.$dateType.$playType.$game_type,$rankUser,600);
        }

        $where['g.user_id'] = ['in',array_values($rankUser)];

        //当天竞猜最新id
        $model = $game_type == 1 ? M('Gamble g') : M('Gamblebk g');

        //再取内容
        $field = " max(g.id) as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.score, g.away_team_name,
                 g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face,u.fb_ten_gamble, u.fb_ten_bet, u.fb_gamble_win,u.fb_bet_win,u.bk_ten_gamble ,u.bk_gamble_win ";

        if($playType == 1){
            $field .= $game_type == 1 ? ' , u.lv, qu.union_color ' : ' , u.lv_bk as lv, qu.union_color ';
            $union  = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
            $gambleList = $model->field($field)
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
                ->where($where)->order('g.create_time DESC')->group('g.user_id')
                ->select();
        }else{//竞彩需要赛事序号
            $field .= ' , u.lv_bet as lv, qu.union_color, b.bet_code';
            $gambleList = M('Gamble g')->field($field)
                ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where)->order('g.create_time DESC')->group('g.user_id')
                ->select();
        }
        if (empty($gambleList))
            return array();

        //对应排名
        foreach ($gambleList as $k => $v) {
            foreach ($rankUser as $kk => $vv) {
                if($v['user_id'] == $vv){
                    $gambleList[$k]['ranking'] = $kk;
                    $sortRank[] = $kk;
                }
            }
            //近十场的胜率;
            if($game_type == 1){
                $gambleList[$k]['tenGambleRate'] = $playType == 1 ? $v['fb_ten_gamble'] : $v['fb_ten_bet'];
                $gambleList[$k]['curr_victs']    = $playType == 1 ? $v['fb_gamble_win'] : $v['fb_bet_win'];
            }else{
                $gambleList[$k]['tenGambleRate'] = $v['bk_ten_gamble'];
                $gambleList[$k]['curr_victs']    = $v['bk_gamble_win'];
            }
            unset($res[$k]['winrate'],$gambleList[$k]['fb_gamble_win'],$gambleList[$k]['fb_bet_win'],$gambleList[$k]['fb_ten_gamble'],$gambleList[$k]['fb_ten_bet'],$gambleList[$k]['bk_ten_gamble'],$gambleList[$k]['bk_gamble_win']);
        } 
        //根据周报排名排序
        array_multisort($sortRank,SORT_ASC,$gambleList);
        //前5条
        $gambleArr = array_slice($gambleList, 0,5);
        return $gambleArr ? : [];
    }


    /**
     * 数据重置
     * @param $userid   int 用户id
     * @param $platform int 平台
     * @param $gambleType int 推荐玩法(默认为亚盘1:亚盘;2:竞彩 )
     * @return int
     */
    public function resetGambleData($userid, $platform, $gambleType=1)
    {
        //先判断金币数量
        $userInfo = M('FrontUser')->field(['coin','unable_coin','(coin+unable_coin) as totalCion'])->where(['id'=>$userid])->find();

        $coin = 5;
        if ($userInfo['unable_coin'] < $coin) {
            $userInfo['coin'] = $userInfo['coin'] - ($coin - $userInfo['unable_coin']);
            $userInfo['unable_coin'] = 0;

            if ($userInfo['coin'] < 0) return 1072;

        } else {
            $userInfo['unable_coin'] -= $coin;
        }

        $where['user_id']   = $userid;
        $where['play_type'] = ($gambleType == 1) ? ['in', [1,-1]] : ['in', [2,-2]];

        //转移数据
        $data = M('Gamble')->field('is_reset', true)->where($where)->select();

        //无数据则不重置，提示
        if(empty($data))
            return 1074;

        //捕抓错误，开启事务
        try{
            M()->startTrans();

            $e1 = M('GambleReset')->addAll($data);
            $e2 = M('Gamble')->where($where)->delete();

            if($e1=== false || $e2=== false){
                throw new Exception();
            }else{
                //修改相关表
                $res1 = M('FrontUser')->where(['id'=>$userid])->save(['unable_coin'=>$userInfo['unable_coin'],'coin'=>$userInfo['coin']]);

                $res2 = M('AccountLog')->add([
                    'user_id'    =>  $userid,
                    'log_time'   =>  NOW_TIME,
                    'log_type'   =>  14,
                    'log_status' =>  1,
                    'change_num' =>  $coin,
                    'total_coin' =>  $userInfo['totalCion']-$coin,
                    'desc'       =>  "重置数据",
                    'platform'   =>  $platform,
                    'operation_time' => NOW_TIME
                ]);

                if ($res1=== false || $res2=== false) {
                    throw new Exception();
                }
            }

            M()->commit();
            return 1;
        }catch(Exception $e) {
            M()->rollback();
            return 1073;
        }

    }

    /**
     * 添加推荐记录数量
     * @param $param    array 推荐数据
     * @param $gameType int   1足球  2篮球
     * @return int
     */
    public function setGambleNumber($param,$gameType)
    {
        $Model = $gameType == 1 ? M('gambleNumber') : M('gamblebkNumber');
        if ($gameType == 1) //足球
        {
            switch ($param['play_type']) 
            {
                case '1':
                    //亚盘让球
                    $gambleStr = $param['chose_side'] == 1 ? 'let_home_num' : 'let_away_num';
                    break;
                case '-1':
                    //亚盘让球
                    $gambleStr = $param['chose_side'] == 1 ? 'size_big_num' : 'size_small_num';
                    break;
                case '2':
                    //竞彩不让球
                    switch ($param['chose_side']) {
                        case  '1': $gambleStr = 'not_win_num';  break;
                        case  '0': $gambleStr = 'not_draw_num'; break;
                        case '-1': $gambleStr = 'not_lose_num'; break;
                    }
                    break;
                case '-2':
                    //竞彩让球
                    switch ($param['chose_side']) {
                        case  '1': $gambleStr = 'let_win_num';  break;
                        case  '0': $gambleStr = 'let_draw_num'; break;
                        case '-1': $gambleStr = 'let_lose_num'; break;
                    }
                    break;
            }
        }
        elseif($gameType == 2) //篮球
        {
            switch ($param['play_type']) 
            {
                case '1':
                    //全场让球
                    $gambleStr = $param['chose_side'] == 1 ? 'all_home_num' : 'all_away_num';
                    break;
                case '-1':
                    //全场大小
                    $gambleStr = $param['chose_side'] == 1 ? 'all_big_num' : 'all_small_num';
                    break;
                case '2':
                    //半场让球
                    $gambleStr = $param['chose_side'] == 1 ? 'half_home_num' : 'half_away_num';
                    break;
                case '-2':
                    //半场大小
                    $gambleStr = $param['chose_side'] == 1 ? 'half_big_num' : 'half_small_num';
                    break;
            }
        }
        //查询是否已有记录
        $is_has = $Model->master(true)->where(['game_id'=>$param['game_id']])->getField('id');
        if($is_has) //更新数量
        {
            $rs = $Model->where(['game_id'=>$param['game_id']])->setInc($gambleStr);
        }
        else //添加新记录
        {
            $rs = $Model->add(['game_id'=>$param['game_id'],$gambleStr=>1]);
        }
    }

    /**
     * 4.0首页——超值高手数据
     */
    public function superMasterData($playType, $limit, $beginTime, $endTime, $lastUser='', $game_type=1){
//        $where['u.is_robot']  = 0;//真实用户
        if($playType == 1){//亚盘
            if($game_type == 1){
                $where['u.gamble_num'] = ['gt', 3];
            }else{
                $where['u.bk_gamble_num'] = ['gt', 3];
            }

            $where['g.play_type']  = $game_type == 1 ? ['in', [-1,1]] : ['in', [-1,1,-2,2]];
        }else if($playType == 2){//竞彩
            $where['u.bet_num']    = ['gt', 3];
            $where['g.play_type']  = ['in', [-2,2]];
        }

        $where['g.tradeCoin']   = ['between', [1, 8]];
        $where['g.create_time'] = ['between', [$beginTime, $endTime]];
        $where['g.result']      = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）
        $where['gf.game_state'] = $game_type == 1 ? ['in', [-1,0,1,2,3,4]] : ['in', [-1,0,1,2,3,4,5,6,7]];
        $where['u.status']      = 1;
        //排除第一次查的用户
        if($lastUser)
            $where['g.user_id'] = ['not in', $lastUser];

        $model = $game_type == 1 ? M('Gamble g') : M('Gamblebk g');
        $info  = $game_type == 1 ? 'qc_game_fbinfo' : 'qc_game_bkinfo';
        //显示5中4以上的真实用户，只要最新的前15条，今天和昨天，时间倒序
        $gambleList = $model->join(' INNER JOIN qc_front_user AS u ON g.user_id = u.id ')
                    ->join(' LEFT JOIN '.$info.' AS gf ON g.game_id = gf.game_id ')
                    ->join(' LEFT JOIN qc_front_user u ON g.user_id = u.id ')
                    ->where($where)->group('g.user_id')->order('g.create_time DESC')
                    ->limit($limit)->getField('max(g.id)', true);

        if($gambleList){
            $field = " g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.score, g.away_team_name,
                     g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face, u.gamble_num, u.bet_num, u.bk_gamble_num ";

            $where1['g.id'] = ['in', $gambleList];

            if($playType == 1){
                $field .= $game_type == 1 ? ' , u.lv, qu.union_color ' : ' , u.lv_bk as lv, qu.union_color ';
                $union  = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
                $res = $model->field($field)
                    ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                    ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
                    ->where($where1)->order('g.create_time DESC')
                    ->select();
            }else{//竞彩需要赛事序号
                $field .= ' , u.lv_bet as lv, qu.union_color, b.bet_code';
                $res = M('Gamble g')->field($field)
                    ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
                    ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                    ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                    ->where($where1)->order('g.create_time DESC')
                    ->select();
            }

            foreach ($res as $k => $v) {
                $winnig = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $playType); //连胜记录
                $res[$k]['curr_victs']     = $winnig['curr_victs'];//连胜场数
                $res[$k]['tenGambleRate']  = $winnig['tenGambleRate'];;//近十场的胜率;
                $res[$k]['face']           = frontUserFace($v['face']);
                $res[$k]['weekPercnet']    = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0,$playType);//周胜率
                $res[$k]['union_name']     = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc']           = (string)$v['desc'];
                $res[$k]['fiveGambleRate'] = ($playType == 1) ? ($game_type == 1 ? $v['gamble_num'] : $v['bk_gamble_num']) : $v['bet_num'];
                $res[$k]['score']          = (string)$v['score'];
                $res[$k]['voice']          = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
                $res[$k]['game_type']      = $game_type;
                $res[$k]['quiz_number']    = D('Common')->getQuizNumber($v['quiz_number']);

                if(iosCheck()) $res[$k]['nick_name'] = str_replace(C('filterNickname'), C('replaceWord'), $res[$k]['nick_name']);

                unset($winnig, $res[$k]['gamble_num'], $res[$k]['bet_num'], $res[$k]['bk_gamble_num']);
            }
        }

        return $gambleList ? $res : array();
    }

    /**
     * 理财产品订购、抢购：高并发下可能会出现超卖的情况，可优化（基于redis乐观锁、mysql事务+redis原子性...）
     * @param $userid           //用户ID
     * @param $productId        //产品ID
     * @param int $platform     //平台
     * @return array            //返回结构
     */
    public function introOrder($userid, $productId, $platform = 3)
    {
        try{
            $blockTime = getBlockTime(1, true);

            if (!$userid || !$productId)
                throw new \Think\Exception(101);

            $products = M('introProducts')->master(true)->field('id,name,total_num,pay_num,sale,create_time')->where(['id' => $productId])->find();
            if(!$products)
                throw new \Think\Exception(8011);

            //金币是否足够
            $frontUser = M('FrontUser')->master(true)->field('coin, username, unable_coin')->where(['id' => $userid])->find();
            $total_coin = $frontUser['coin'] + $frontUser['unable_coin'];
            if ($total_coin <= 0 || $total_coin < $products['sale'])
                throw new \Think\Exception(8009);

            //先使用不可提金币
            if ($frontUser['unable_coin'] < $products['sale']) {
                $save_coin = $frontUser['coin'] - ($products['sale'] - $frontUser['unable_coin']);
                $save_unable_coin = 0;
            } else {
                $save_coin = $frontUser['coin'];
                $save_unable_coin = $frontUser['unable_coin'] - $products['sale'];
            }

            //是否已经发了推介
            $wh = ['status' => 1, 'product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]];
            $intro = M('IntroLists')->master(true)->where($wh)->find();
            if ($intro) {
                //是否达到订购上限
                if($intro['remain_num'] < 1)
                    throw new \Think\Exception(8005);

                //是否已购买
                $is_order = M('IntroBuy')->master(true)->where(['product_id' => $productId, 'user_id' => $userid, 'list_id' => $intro['id']])->find();
                if($is_order)
                    throw new \Think\Exception(8006);

            }else{
                //是否已购买
                $buy_log = M('IntroBuy')->master(true)->where(['product_id' => $productId, 'user_id' => $userid])->order('id DESC')->find();

                if($buy_log && !$buy_log['list_id']){
                    throw new \Think\Exception(8007);
                }

                //是否达到订购上限
                $num = M('IntroBuy')->master(true)->where(['product_id' => $productId, 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();
                if(($num + $products['pay_num']) >= $products['total_num'])
                    throw new \Think\Exception(8005);
            }

            //事务开始
            M()->startTrans();

            //金币更新
            $update1 = M('FrontUser')->where(['id' => $userid])->save(['unable_coin' => $save_unable_coin,'coin' => $save_coin]);

            //生成推介购买记录
            $insertId1 = M('IntroBuy')->add([
                'user_id'       =>  $userid,
                'product_id'    =>  $products['id'],
                'list_id'       =>  $intro['id']?:'',
                'price'         =>  $products['sale'],
                'platform'      =>  $platform,
                'create_time'   => NOW_TIME
            ]);
            M('IntroProducts')->where(['id' => $products['id']])->setInc('total_pay');

            //生成账户明细记录
            $insertId2 = M('AccountLog')->add([
                'user_id'       =>  $userid,
                'intro_buy_id'  =>  $insertId1,
                'log_time'      =>  NOW_TIME,
                'log_type'      =>  '17',
                'log_status'    =>  '1',
                'change_num'    =>  $products['sale'],
                'total_coin'    =>  $save_coin + $save_unable_coin,
                'desc'          =>  "您已成功购买【{$products['name']}】的服务",
                'platform'      =>  $platform,
                'operation_time'=> NOW_TIME
            ]);

            //发推介时,限购数减一
            if($intro['id']){
                $update2 = M('IntroLists')->where(['id' => $intro['id']])->setDec('remain_num', 1);
            }

            if($insertId1 === false || $update1 === false || $insertId2 === false || $update2 === false){
                M()->rollback();
                throw new \Think\Exception(8010);
            }else{
                M()->commit();
            }

            //生成推送订购消息
            if($intro['id'] && $intro['pub_time'] > NOW_TIME){
                $gamble = M('IntroGamble')->master(true)->where(['list_id' => $intro['id']])->select();
                $msg = $products['name'];

                foreach($gamble as $gkey => $gval) {
                    $union_name     = explode(',', $gval['union_name'])[0];
                    $home_team_name = explode(',', $gval['home_team_name'])[0];
                    $away_team_name = explode(',', $gval['away_team_name'])[0];

                    if ($gamble['play_type'] == 1) {
                        $select = $gamble['play_type'] == 1 ? $home_team_name : $away_team_name;
                    } else {
                        $select = $gamble['play_type'] == 1 ? '大球' : '小球';
                    }

                    $gdata = date('d-m H:i', $gval['gtime']);
                    $str = '';
                    switch($gkey){
                        case 0: $str = "推介一：";break;
                        case 1: $str = "推介二：";break;
                        case 2: $str = "推介三：";break;
                    }
                    $msg .= $str . "{$gdata} {$union_name}【{$home_team_name} VS {$away_team_name}】{$select} {$gval['handcp']}(" . $gval['odds'] . ");";
                }

                M('MobileMsg')->add([
                    'user_id'       => $userid,
                    'list_id'       => $intro['id'],
                    'content'       => $msg,
                    'send_type'     => '2',
                    'module'        => '16',
                    'module_value'  => $products['id'],
                    'state'         => 0,
                    'send_time'     => $intro['pub_time']
                ]);
            }
            $ret_struct = ['status' => true, 'code' => '', 'msg' => '注意：请留意接收短信和App推送通知，如没收到信息，请及时联系在线客服。'];

        }catch (\Think\Exception $m){
            $c = $m->getMessage();
            $ret_struct = ['status' => false, 'code' => $c, 'msg' => C('errorCode')[$c]];
        }

        return $ret_struct;
    }

    /**
     * V5.0首页IOS审核——高手推荐
     * 获取免费的竞猜
     */
    public function getGamble($game_type=1){
        $model = $game_type == 1 ? M('Gamble g') : M('Gamblebk g');

        $field = " g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.score, g.away_team_name,
                 g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face ";

        $where['g.tradeCoin'] = 0;
        $where['g.result']      = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）
        $where['g.create_time'] = ['gt', strtotime('-7 day', strtotime('10:32'))];

//        if($game_type == 1){
            $field .= $game_type == 1 ? ' , u.lv, qu.union_color ' : ' , u.lv_bk as lv, qu.union_color ';
            $union  = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
            $where['u.status'] = 1;
            $totalArr = $model->field($field)
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
                ->where($where)->group('g.user_id')->limit(30)->order('g.id DESC')
                ->select();
//        }else{//竞彩需要赛事序号
//            $field .= ' , u.lv_bet as lv, qu.union_color, b.bet_code';
//            $totalArr = M('Gamble g')->field($field)
//                ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
//                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
//                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
//                ->where($where)->group('g.user_id')->limit(30)->order('g.id DESC')
//                ->select();
//        }

        if($totalArr){
            foreach($totalArr as $k => $v){
                $gambleType= in_array($v['play_type'], [-1,1]) ? 1 : 2;//推荐类型
                $winnig    = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $gambleType,30); //连胜记录
                $totalArr[$k]['tenGambleRate']  = $winnig['tenGambleRate'];//近十场的胜率;
                $totalArr[$k]['curr_victs']     = $winnig['curr_victs'];//连胜场数
                $totalArr[$k]['face']           = frontUserFace($v['face']);
                $totalArr[$k]['weekPercnet']    = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0, $gambleType);//周胜率
                $totalArr[$k]['union_name']     = explode(',', $v['union_name']);
                $totalArr[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $totalArr[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $totalArr[$k]['desc']           = (string)$v['desc'];
                $totalArr[$k]['score']          = (string)$v['score'];
                $totalArr[$k]['voice']          = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
                $totalArr[$k]['game_type']      = $game_type;

                unset($winnig);
            }
        }

        return $totalArr ?: [];
    }

    /**
     * V5.0首页IOS审核——超值高手
     */
    public function superGamble($game_type){
        if($game_type == 1){
            $where['u.gamble_num'] = ['gt', 3];
        }else{
            $where['u.bk_gamble_num'] = ['gt', 3];
        }

        $where['g.play_type'] = ['in', [-1,1,-2,2]];
        $where['g.tradeCoin']   = 0;
        $where['g.create_time'] = ['gt', strtotime('-7 day', strtotime('10:32'))];
        $where['g.result']      = ['in', ['0', '1', '0.5', '2' ,'-0.5' ,'-1']];//0:未出结果，1：赢，0.5:赢半，2：平，-1：输，-0.5：输半）

        $model = $game_type == 1 ? M('Gamble g') : M('Gamblebk g');
        $field = " g.id as gamble_id, g.user_id, g.union_name, g.game_date, g.game_time, g.home_team_name, g.score, g.away_team_name,
                 g.play_type, g.chose_side, g.tradeCoin, g.handcp, g.odds, g.result, g.`desc`, (g.quiz_number + g.extra_number) as quiz_number, g.create_time, IF(is_voice=1, voice, '') as voice, g.voice_time, u.nick_name, u.head as face, u.gamble_num, u.bet_num, u.bk_gamble_num ";
/*
        if($game_type == 1){
            $field .= $game_type == 1 ? ' , u.lv, qu.union_color ' : ' , u.lv_bk as lv, qu.union_color ';
            $union  = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
            $res = $model->field($field)
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
                ->where($where)->group('g.user_id')->order('g.id DESC')
                ->limit(30)
                ->select();
        }else{//竞彩需要赛事序号
            $field .= ' , u.lv_bet as lv, qu.union_color, b.bet_code';
            $res = M('Gamble g')->field($field)
                ->join(' LEFT JOIN qc_fb_betodds AS b ON g.game_id = b.game_id ')
                ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
                ->join(' LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id ')
                ->where($where)->group('g.user_id')->order('g.id DESC')
                ->limit(30)
                ->select();
        }
*/
        $where['u.status'] = 1;
        $field .= $game_type == 1 ? ' , u.lv, qu.union_color ' : ' , u.lv_bk as lv, qu.union_color ';
        $union  = $game_type == 1 ? 'qc_union' : 'qc_bk_union';
        $res = $model->field($field)
            ->join(' LEFT JOIN qc_front_user AS u ON g.user_id = u.id ')
            ->join(' LEFT JOIN '.$union.' AS qu ON g.union_id = qu.union_id ')
            ->where($where)->group('g.user_id')->order('g.id DESC')
            ->limit(30)
            ->select();

        if($res) {
            foreach ($res as $k => $v) {
                $winnig = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $v['play_type']); //连胜记录
                $res[$k]['curr_victs'] = $winnig['curr_victs'];//连胜场数
                $res[$k]['tenGambleRate'] = $winnig['tenGambleRate'];;//近十场的胜率;
                $res[$k]['face'] = frontUserFace($v['face']);
                $res[$k]['weekPercnet'] = (string)D('GambleHall')->CountWinrate($v['user_id'], $game_type, 1, false, false, 0, $v['play_type']);//周胜率
                $res[$k]['union_name'] = explode(',', $v['union_name']);
                $res[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                $res[$k]['away_team_name'] = explode(',', $v['away_team_name']);
                $res[$k]['desc'] = (string)$v['desc'];
                $res[$k]['fiveGambleRate'] = ($v['play_type'] == 1) ? ($game_type == 1 ? $v['gamble_num'] : $v['bk_gamble_num']) : $v['bet_num'];
                $res[$k]['score'] = (string)$v['score'];
                $res[$k]['voice'] = $v['voice'] ? Tool::imagesReplace($v['voice']) : '';
                $res[$k]['game_type'] = $game_type;
                $res[$k]['quiz_number'] = D('Common')->getQuizNumber($v['quiz_number']);

                unset($winnig, $res[$k]['gamble_num'], $res[$k]['bet_num'], $res[$k]['bk_gamble_num']);
            }
        }

        return $res ?: [];
    }

    /**
     * 统计十中几
     */
    public function countTeneGamble($userArr=''){
        if($userArr){
            $where['id'] =  ['in', $userArr];
        } else {
            $where =  '1=1';
        }

        $userArr = M('FrontUser')->master(true)->field('id, fb_ten_gamble, fb_ten_bet, bk_ten_gamble')->where($where)->select();
        if(empty($userArr)) return false;

        $newArr  = array_chunk($userArr, 100);

        M()->startTrans();
        $num1 = $num2 = $num3 = 0;
        foreach($newArr as $nk => $nv) {
            $gambleUser = $betUser = $bkgambleUser = [];
            foreach ($nv as $uk => $uv) {
                //足球亚盘
                $fbyapan     = D('GambleHall')->getTenGamble($uv['id'], 1, 1);
                $fbyapanRate = countTenGambleRate($fbyapan, 1, 2);//近十场的胜率

                //足球竞彩
                $fbjingcai     = D('GambleHall')->getTenGamble($uv['id'], 1, 1);
                $fbjingcaiRate = countTenGambleRate($fbjingcai, 2, 2);//近十场的胜率

                //篮球亚盘
                $bkyapan     = D('GambleHall')->getTenGamble($uv['id'], 2, 1);
                $bkyapanRate = countTenGambleRate($bkyapan, 1, 2);//近十场的胜率

                //要满足10条竞猜，统计的结果不等于当前的结果
                if ($fbyapanRate != $uv['fb_ten_gamble']) $gambleUser[$uv['id']] = $fbyapanRate;

                if ($fbjingcaiRate != $uv['fb_ten_bet']) $betUser[$uv['id']] = $fbjingcaiRate;

                if ($bkyapanRate != $uv['bk_ten_gamble']) $bkgambleUser[$uv['id']] = $bkyapanRate;
            }

            $sql1 = D('Home')->assembleSql('qc_front_user', 'fb_ten_gamble', $gambleUser);
            $sql2 = D('Home')->assembleSql('qc_front_user', 'fb_ten_bet', $betUser);
            $sql3 = D('Home')->assembleSql('qc_front_user', 'bk_ten_gamble', $bkgambleUser);

            $res1 = M()->execute($sql1);
            $res2 = M()->execute($sql2);
            $res3 = M()->execute($sql3);

            //两种修改互不干扰
            if ($res1 === false && $res2 === false && $res3 === false) {
                M()->rollback();
            } else {
                M()->commit();
                $num1 += $res1;
                $num2 += $res2;
                $num3 += $res3;
            }
        }

        return [$num1, $num2, $num3];
    }

    //获取足球推荐赛事赔率
    public function getGambleOdds($gameId,$type=1){
        $data = [];
        $mongo = mongoService();
        $game = $mongo->fetchRow('fb_game',['game_id'=>(int)$gameId],['game_id','game_state','start_time','game_start_timestamp','game_starttime']);
        $game['gtime'] = TellRealTime($game['start_time'],$game['game_start_timestamp'],$game['game_starttime'],$game['game_state']);

        $DataService = new \Common\Services\DataService();
        $gids = $DataService->getGameTodayGids(1);

        if($game['game_state'] == 0 && in_array($gameId, $gids)){
            //在竞猜时间内
            $gstate_check = '1';
        }else{
            //不可推荐
            $gstate_check = '-1';
        }

        if($type == 1){
            //获取实时赔率
            $appfbService = new \Home\Services\AppfbService();
            $oddsArr = $appfbService->fbOdds((int)$gameId);
            $odds = $oddsArr[$gameId];

            $data = [
                'fsw_exp_home'  => !empty($odds[0]) ? $odds[0] : '',  //让球主队的赔率
                'fsw_exp'       => !empty($odds[1]) ? changeExp($odds[1]) : '',  //让球盘口
                'fsw_exp_away'  => !empty($odds[2]) ? $odds[2] : '',  //让球客队赔率
                'fsw_ball_home' => !empty($odds[6]) ? $odds[6] : '',  //大小球 大的赔率
                'fsw_ball'      => !empty($odds[7]) ? changeExp($odds[7]) : '',  //大小球盘口
                'fsw_ball_away' => !empty($odds[8]) ? $odds[8] : '',  //大小球 小的赔率
            ];
            $data['odds_check'] = !$game || count(array_filter($data)) <= count($data) - 2 ? '0' : '1';
        }else{
            //获取实时赔率
            $oddsArr = D('GambleHall')->getSportteryGoal((int)$gameId);

            $data = [
                'home_odds'     => (string)$oddsArr[0],      //不让球主胜赔率
                'draw_odds'     => (string)$oddsArr[1],      //不让球平赔率
                'away_odds'     => (string)$oddsArr[2],      //不让球客胜赔率
                'home_letodds'  => (string)$oddsArr[3],   //让球主胜赔率
                'draw_letodds'  => (string)$oddsArr[4],   //让球平赔率
                'away_letodds'  => (string)$oddsArr[5],   //让球客胜赔率
                'let_exp'       => (string)$oddsArr[6],   //让球
            ];

            $data['odds_check'] = !$game || count(array_filter($data)) <= count($data) - 2 ? '0' : '1';
        }
        
        return ['data'=>$data,'gstate_check'=>$gstate_check];
    }

    //获取篮球推荐赛事赔率
    public function getGambleOddsBk($gameId){
        $mongo = mongoService();
        $game = $mongo->fetchRow('bk_game_schedule',['game_id'=>(int)$gameId],['game_id','game_status','game_timestamp','instant_index']);
        $game['gtime'] = $game['game_timestamp'];

        $DataService = new \Common\Services\DataService();
        $gids = $DataService->getGameTodayGids(2);  
        if($game['game_status'] == 0 && in_array($gameId, $gids)){
            //在竞猜时间内
            $gstate_check = '1';
        }else{
            //不可推荐
            $gstate_check = '-1';
        }
        //获取即时赔率数据
        $odds = $this->getBkOdds($game['instant_index']);
        $data = [];
        $data['fsw_exp']        = !empty($odds[0]) ? $odds[0] : '';
        $data['fsw_exp_home']   = !empty($odds[1]) ? $odds[1] : '';
        $data['fsw_exp_away']   = !empty($odds[2]) ? $odds[2] : '';
        $data['fsw_total']      = !empty($odds[3]) ? $odds[3] : '';
        $data['fsw_total_home'] = !empty($odds[4]) ? $odds[4] : '';
        $data['fsw_total_away'] = !empty($odds[5]) ? $odds[5] : '';
        $data['odds_check']  = !$game || (count(array_filter($data)) <= count($data) - 2) ? '0' : '1';

        return ['data'=>$data,'gstate_check'=>$gstate_check];
    }

    //判断动画关联
//     public function getFbLinkbet($gids){
//         $mongo = mongoService();
//         if(!is_array($gids)){
//             $_map = ['$or' => [
//                 ['jbh_id' => (int)$gids],
//                 ['jb_id'  => (int)$gids],
//             ]];
//             $betRes = $mongo->select('fb_game_365'.C('TableSuffix'), $_map,['jbh_id','jb_id','is_icon']);
//             return $betRes ? $betRes[0]['is_icon'] : 0;
//         }
//         $_map = ['$or' => [
//             ['jbh_id' => ['$in' => $gids]],
//             ['jb_id'  => ['$in' => $gids]],
//         ]];
//         $betRes = $mongo->select('fb_game_365'.C('TableSuffix'), $_map,['jbh_id','jb_id','is_icon']);
//         $linksArr = [];
//         if(!empty($betRes))
//         {
//             foreach($betRes as $k=> $v)
//             {
//                 $gid = $v['jbh_id'] ? $v['jbh_id'] : $v['jb_id'];
//                 if($v['is_icon'] == 1) $linksArr[$gid] = 1;
//             }
//         }
//         // if(!is_array($gids)){
//         //     $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where(['game_id'=>$gids])->find();
//         //     return $betRes ? 1 : 0;
//         // }
//         // $map['game_id'] = array('in',$gids);
//         // $betRes = M('FbLinkbet')->field('game_id,is_link,flash_id,md_id')->where($map)->select();

//         // $linksArr = [];
//         // if(!empty($betRes))
//         // {
//         //     foreach($betRes as $k=> $v)
//         //     {
//         //         $linksArr[$v['game_id']] = $v['is_link'];
//         //     }
//         // }
//         return $linksArr;
//     }

    /**
     * 获取赛事实时最新赔率
     * @param int  $gids 赛事id
     * @return  array
    */
    public function getGambleGoal($gids){
        $mongo = mongoService();
        if(is_array($gids)){
            $goal = $mongo->select('fb_goal',['game_id'=>['$in'=>$gids]],['game_id','odds']);
            foreach ($goal as $k => $v) {
                $goalData[$v['game_id']] = $v['odds'];
            }
            return $goalData;
        }else{
            $goal = $mongo->fetchRow('fb_goal',['game_id'=>(int)$gids],['game_id','odds']);
            return $goal['odds'];
        }
    }

    /**
     * 获取竞彩赛事实时最新赔率
     * @param int  $gids 赛事id
     * @return  array
    */
    public function getSportteryGoal($gids){
        if(empty($gids)) return [];

        $mongo = mongoService();
        if(is_array($gids)){
            $goal = $mongo->select('fb_sporttery',['game_id'=>['$in'=>$gids]],['game_id','had','hhad','is_reverse']);
            foreach ($goal as $k => $v) {
                //不让球赔率
                $had  = $v['had'];
                //让球赔率
                $hhad = $v['hhad'];

                $odds = [
                    0 => $had['h'],
                    1 => $had['d'],
                    2 => $had['a'],
                    3 => $hhad['h'],
                    4 => $hhad['d'],
                    5 => $hhad['a'],
                    6 => $hhad['fixedodds'],
                    7 => 0,
                ];

                if(isset($v['is_reverse']) && $v['is_reverse'] == 1){
                    //赔率盘口反转
                    $odds = [
                        0 => $had['a'],
                        1 => $had['d'],
                        2 => $had['h'],
                        3 => $hhad['a'],
                        4 => $hhad['d'],
                        5 => $hhad['h'],
                        6 => plusMinusChange($hhad['fixedodds']),
                        7 => 1,
                    ];
                }
                
                $goalData[$v['game_id']] = $odds;
            }
            return $goalData;
        }else{
            $goal = $mongo->fetchRow('fb_sporttery',['game_id'=>(int)$gids],['game_id','had','hhad','is_reverse']);
            //不让球赔率
            $had  = $goal['had'];
            //让球赔率
            $hhad = $goal['hhad'];
            
            $odds = [
                0 => $had['h'],
                1 => $had['d'],
                2 => $had['a'],
                3 => $hhad['h'],
                4 => $hhad['d'],
                5 => $hhad['a'],
                6 => $hhad['fixedodds'],
                7 => 0,
            ];

            if(isset($goal['is_reverse']) && $goal['is_reverse'] == 1){
                //赔率盘口反转
                $odds = [
                    0 => $had['a'],
                    1 => $had['d'],
                    2 => $had['h'],
                    3 => $hhad['a'],
                    4 => $hhad['d'],
                    5 => $hhad['h'],
                    6 => plusMinusChange($hhad['fixedodds']),
                    7 => 1,
                ];
            }
            
            return $odds;
        }
    }

    /**
     * 赛事实时最新赔率处理
     * @param array  $fsw_odds 赔率数组
     * @param int  $game_state 赛事状态
     * @return  array
    */
    public function doFswOdds($fsw_odds='',$game_state,$dogame='',$is_nbsp=0){
        if(empty($fsw_odds)){
            return ['','','','','','','','','','','','','','','','','',''];
        }

        //赛事进行中实时赔率，是否封盘处理
        if(!empty($dogame) && in_array($game_state, [1,2,3,4,5])){
            $fsw_exp_home    = $dogame['a'][1] != '' ? $dogame['a'][1] : '';
            $fsw_exp         = $dogame['a'][0] != '' ? $dogame['a'][0] : '';
            $fsw_exp_away    = $dogame['a'][2] != '' ? $dogame['a'][2] : '';
            $fsw_ball_home   = $dogame['b'][1] != '' ? $dogame['b'][1] : '';
            $fsw_ball        = $dogame['b'][0] != '' ? $dogame['b'][0] : '';
            $fsw_ball_away   = $dogame['b'][2] != '' ? $dogame['b'][2] : '';
            $fsw_europe_home = $dogame['e'][0] != '' ? $dogame['e'][0] : '';
            $fsw_europe      = $dogame['e'][1] != '' ? $dogame['e'][1] : '';
            $fsw_europe_away = $dogame['e'][2] != '' ? $dogame['e'][2] : '';
            $nbsp = $is_nbsp == 1 ? '&nbsp;' : '';
            if($dogame['a'][3] != 0){
                $fsw_exp_home    = $nbsp;
                $fsw_exp         = '封';
                $fsw_exp_away    = $nbsp;
            }
            if($dogame['b'][3] != 0){
                $fsw_ball_home    = $nbsp;
                $fsw_ball         = '封';
                $fsw_ball_away    = $nbsp;
            }
            if($dogame['e'][3] != 0){
                $fsw_europe_home    = $nbsp;
                $fsw_europe         = '封';
                $fsw_europe_away    = $nbsp;
            }
            $fsw_odds[0] = $fsw_exp_home;
            $fsw_odds[1] = $fsw_exp;
            $fsw_odds[2] = $fsw_exp_away;
            $fsw_odds[3] = $fsw_europe_home;
            $fsw_odds[4] = $fsw_europe;
            $fsw_odds[5] = $fsw_europe_away;
            $fsw_odds[6] = $fsw_ball_home;
            $fsw_odds[7] = $fsw_ball;
            $fsw_odds[8] = $fsw_ball_away;
        }
        if($game_state == -1){
            //完场使用初盘
            $fsw_odds = explode('^', $fsw_odds[18]);
        }
        unset($fsw_odds[18]);
        return $fsw_odds;
    }
}