<?php
/**
 * 数据实时数据缓存
 * @author dengwj <406516482@qq.com>
 * @since  2018-8-29
 */
use Think\Controller;

class DataCacheController extends Controller
{
    /**
     * 缓存足球比分mongo数据，crontab定时3秒执行缓存一次
     */
    public function cachefbtodayListData()
    {
        set_time_limit(2);

        $mongo = mongoService();

        //获取今日赛事列表
        $dataService = new \Common\Services\DataService();
        $gameIdArr = $dataService->getGameTodayGids(1);
        $baseRes = $mongo->select('fb_game',['game_id'=>['$in'=>$gameIdArr]],
            [
                'game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id',
                'away_team_id','union_id','start_time','game_starttime','game_start_timestamp','game_half_datetime',
                'red_card','yellow_card','corner','game_state','score','half_score','explain','remark','is_go','is_flash',
                'is_sporttery','spottery_num','union_color','field_weather','goal_data','odds_data'
            ]
        );
        //保存赛程redis缓存
        S('cache_fbtodayList_game',$baseRes,10);
        
        echo '缓存时间:'.date('Y-m-d H:i:s');
        exit();
    }

    /**
     * 缓存篮球比分mongo数据，crontab定时3秒执行缓存一次
     */
    public function cachebktodayListData()
    {
        set_time_limit(2);

        $mongo = mongoService();

        //获取今日赛事列表
        $dataService = new \Common\Services\DataService();
        $gameIdArr = $dataService->getGameTodayGids(2);
        $baseRes = $mongo->select('bk_game_schedule',['game_id'=>['$in'=>$gameIdArr]],
            [
                'game_id','union_name','home_team_name','away_team_name','home_team_rank','away_team_rank','home_team_id',
                'away_team_id','union_id','game_timestamp','is_go','game_status','quarter_time','union_color','game_info','instant_index'
            ]
        );
        //保存赛程redis缓存
        S('cache_bktodayList_game',$baseRes,10);
        
        echo '缓存时间:'.date('Y-m-d H:i:s');
        exit();
    }

    //定时缓存足球比分其他相关数据
    public function cachefbtodayListOther()
    {
        set_time_limit(10);
        $mongo     = mongoService();
        $dataService = new \Common\Services\DataService();
        $gameIdArr = $dataService->getGameTodayGids(1);

        //获取mysql业务数据
        $GameFbinfo = M('GameFbinfo')
            ->field("game_id,gtime,score,half_score,game_state,is_gamble,is_show,status,app_video,is_video")
            ->where(['game_id'=>['in',$gameIdArr]])
            ->select();

        foreach ($GameFbinfo as $k => $v) {
            $gameArr[$v['game_id']] = $v;
        }
        S('cache_fbtodayList_mysqlGame',$gameArr,120);

        //是否有主播直播
        $game_lives = M('liveLog')
            ->alias('Lg')
            ->field('Lg.user_id,Lg.game_id')
            ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
            ->where(['Lg.status' => 1, 'LU.status' => 1, 'Lg.live_status' => 1,'Lg.game_id' => ['neq', '']])
            ->getField('game_id', true);
        S('cache_fbtodayList_lives',$game_lives,120);

        //从缓存获取今日赛事列表
        $baseRes = S('cache_fbtodayList_game');

        if(!empty($baseRes))
        {
            $updateData = $gameData = [];
            foreach ($baseRes as $k => $v) {
                //比赛时间
                $mongoGtime = TellRealTime($v['start_time'],$v['game_start_timestamp'], $v['game_starttime'],$v['game_state']);
                //判断game_id是否存在和比赛状态是否改变
                if(isset($gameArr[$v['game_id']])){
                    $game = $gameArr[$v['game_id']];
                    if($game['game_state'] != $v['game_state'] 
                        || $game['score'] != $v['score'] 
                        || $game['half_score'] != $v['half_score']
                        || $game['gtime'] != $mongoGtime){
                        //修改
                        $updateData[] = "({$v['game_id']},{$mongoGtime},{$v['game_state']},'{$v['score']}','{$v['half_score']}')";
                    }
                }else{
                    //新增
                    $gameData[] = ['game_id'=>$v['game_id'],'gtime'=>$mongoGtime,'game_state'=>$v['game_state'],'score'=>(string)$v['score'],'half_score'=>(string)$v['half_score']];
                }
            }
            if(count($updateData) > 0){
                //执行修改比赛信息
                $updateSql = replaceAllSql('qc_game_fbinfo',['game_id','gtime','game_state','score','half_score'],$updateData);
                $rs = M()->execute($updateSql);
            }
            if(count($gameData) > 0){
                //执行添加比赛(game_id,game_state)
                $rs = M('GameFbinfo')->addAll($gameData);
            }
            
            //获取联盟数据
            if(!$unionArr = S('cache_fbtodayList_union')){
                $unionIdArr = array_column($baseRes,'union_id');
                $union = $mongo->select(
                    'fb_union',
                    ['union_id'=>['$in'=>$unionIdArr]],
                    ['union_id','union_name','country_id','level','union_or_cup','union_color']
                );
                foreach ($union as $k => $v) {
                    $unionArr[$v['union_id']] = $v;
                }
                //保存联盟redis缓存
                S('cache_fbtodayList_union', $unionArr, 299);
            }

            //路珠判断
            if(!$lzMaps = S('cache_fbtodayList_luzhu')){
                foreach ($baseRes as $k => $v) {
                    $uData  = $unionArr[$v['union_id']];
                    $uLevel = isset($uData['level']) ? $uData['level'] : '3';
                    if(in_array($uLevel, [0, 1, 2])){
                        $lzTeamIds[] = $v['home_team_id'];
                        $lzTeamIds[] = $v['away_team_id'];
                    }
                }
                $appfbService = new \Home\Services\AppfbService();
                $lzMaps = $appfbService->checkGoodRule($mongo, $lzTeamIds);
                S('cache_fbtodayList_luzhu', $lzMaps, 599);
            }
        }

        echo '缓存时间:'.date('Y-m-d H:i:s');
    }

    //定时缓存篮球比分其他相关数据
    public function cachebktodayListOther()
    {
        set_time_limit(10);
        $mongo     = mongoService();
        $dataService = new \Common\Services\DataService();
        $gameIdArr = $dataService->getGameTodayGids(2);

        //获取mysql业务数据
        $GameBkinfo = M('GameBkinfo')
            ->field("game_id,gtime,score,half_score,game_state,is_gamble,is_show,status,app_video,is_video")
            ->where(['game_id'=>['in',$gameIdArr]])
            ->select();

        foreach ($GameBkinfo as $k => $v) {
            $gameArr[$v['game_id']] = $v;
        }
        S('cache_bktodayList_mysqlGame',$gameArr,120);

        //从缓存获取今日赛事列表
        $baseRes = S('cache_bktodayList_game');

        if(!empty($baseRes))
        {
            $updateData = $gameData = [];
            foreach ($baseRes as $k => $v) {
                //比赛时间
                $mongoGtime = $v['game_timestamp'];
                //比赛状态
                $v['game_state'] = $v['game_status'];
                //比分
                $game_info  = $v['game_info'];
                $v['score']      = $game_info[3].'-'.$game_info[4];
                $v['half_score'] = ($game_info[5] + $game_info[7]) .'-'. ($game_info[6] + $game_info[8]);
                //判断game_id是否存在和比赛状态是否改变
                if(isset($gameArr[$v['game_id']])){
                    $game = $gameArr[$v['game_id']];
                    if($game['game_state'] != $v['game_state'] 
                        || $game['score'] != $v['score'] 
                        || $game['half_score'] != $v['half_score']
                        || $game['gtime'] != $mongoGtime){
                        //修改
                        $updateData[] = "({$v['game_id']},{$mongoGtime},{$v['game_state']},'{$v['score']}','{$v['half_score']}')";
                    }
                }else{
                    //新增
                    $gameData[] = ['game_id'=>$v['game_id'],'gtime'=>$mongoGtime,'game_state'=>$v['game_state'],'score'=>(string)$v['score'],'half_score'=>(string)$v['half_score']];
                }
            }
            if(count($updateData) > 0){
                //执行修改比赛信息
                $updateSql = replaceAllSql('qc_game_bkinfo',['game_id','gtime','game_state','score','half_score'],$updateData);
                $rs = M()->execute($updateSql);
            }
            if(count($gameData) > 0){
                //执行添加比赛(game_id,game_state)
                $rs = M('GameBkinfo')->addAll($gameData);
            }
            
            //获取联盟数据
            if(!$unionArr = S('cache_bktodayList_union')){
                $unionIdArr = array_column($baseRes,'union_id');
                $union = $mongo->select(
                    'bk_union',
                    ['union_id'=>['$in'=>$unionIdArr]],
                    ['union_id','union_name','grade','union_color']
                );
                foreach ($union as $k => $v) {
                    $unionArr[$v['union_id']] = $v;
                }
                S('cache_bktodayList_union', $unionArr, 299);
            }
        }

        echo '缓存时间:'.date('Y-m-d H:i:s');
    }
}