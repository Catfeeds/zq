<?PHP
/**
 * DataService   数据处理类-----------------------------------------
*/
namespace Common\Services;
class DataService
{
    /**
     * [__construct 构造函数]
     */
    public function __construct()
    {
        #code
    }

    /**
     * 获取聊天室礼物
     * @param  int  $type 类型 1赛事聊天室  2主播聊天室  默认1
     * @return array   
     */
    public function getChatGift($type=1)
    {
        $map['type']        = $type;
        $map['status']      = 1;
        $map['online_time'] = ['LT', NOW_TIME];
        $map['end_time']    = ['GT', NOW_TIME];

        $list = M('ChatGift')
            ->field('id,name,img,zip_file,price,update_time')
            ->where($map)
            ->order('sort asc')->select();

        return $list;
    }

    /**
     * 获取聊天室屏蔽状态
     * @param $userId
     * @return int|string}
     */
    public function chatForbidStatus($userId){
        $errCode = '';
        $forbid = M('ChatForbid')
            ->where(['user_id' => $userId, 'status' => ['IN', [1, 3]]])
            ->order('id DESC')
            ->find();

        if ($forbid) {
            if ($forbid['type'] == 1) {
                $errCode = 3018;
            } else if ($forbid['type'] == 3) {
                if (NOW_TIME < $forbid['operate_time'] + 600) {
                    $errCode = 3019;
                }
            } else if ($forbid['type'] == 2) {
                if ($forbid['status'] == 1) {
                    $errCode = 3018;
                } else {
                    if (NOW_TIME < $forbid['operate_time'] + 600) {
                        $errCode = 3019;
                    }
                }
            }
        }
        return $errCode;
    }

    /**
     * 获取当日赛事game_id数组
     * @param gameType 赛事类型  1足球 2篮球  默认足球
     * @return array
     */
    public function getGameTodayGids($gameType=1)
    {
        $mongo = mongoService();
        if($gameType == 1){
            //足球
            if(!$gids = S('cache_fbtodayList_gameIdArr')){
                $game_list = 'gamelist_array';
                $nowGame = $mongo->select('fb_gamelist',[],['date',$game_list],array('date'=>-1),1);
                $gids = $nowGame[0][$game_list];
                S('cache_fbtodayList_gameIdArr',$gids,60);
            }
        }else{
            //篮球
            if(!$gids = S('cache_bktodayList_gameIdArr')){
                $nowGame = $mongo->select('bk_today_game_list',['game_date'=>'today'],['game_date','today_game_id_list']);
                $gids = $nowGame[0]['today_game_id_list'];
                S('cache_bktodayList_gameIdArr',$gids,60);
            }
        }
    
        if(empty($gids)) return [];
        return $gids;
    }

    /**
     * 聊天历史记录
     * @param string $suffix_key (篮球聊天室2_23423432),（足球聊天室1_23423423）,(直播聊天室live_23545465475)
     * @return array
     */
    public function chatRecord($suffix_key = ''){
        if(!$suffix_key)
            return [];

        //获取聊天记录
        $redis = connRedis();

        $temp_log = $redis->lRange('qqty_chat_' . $suffix_key, 0, 80);
        $forbid_users = $redis->sMembers('qqty_chat_forbid_userids');
        $chat_log = [];
        foreach ($temp_log as $k => $v) {
            $lg = json_decode($v, true);

            if (in_array($lg['user_id'], $forbid_users)) {
                unset($temp_log[$k]);
            } else {
                if (strpos($suffix_key, 'l_') !== false) {
                    $lv = $lg['lv'] > $lg['lv_bet'] ? $lg['lv'] : $lg['lv_bet'];
                    $lg['lv'] = $lv >= 4 ? $lv : '';
                }else if(strpos($suffix_key, 'live_') !== false){
                    $lg['lv'] = $lg['lv'] > $lg['lv_bet'] ? $lg['lv'] : $lg['lv_bet'];
                } else {
                    $lv = $lg['lv_bk'];
                    $lg['lv'] = $lv >= 4 ? $lv : '';
                }

                unset($lg['lv_bet'], $lg['lv_bk']);

                $chat_log[] = $lg;
            }
        }

        return array_reverse($chat_log);
    }

    /**
     * 从mongo获取赛程信息
     * @param $gids 赛程id数组
     * @param $type 类型  1足球  2篮球
     * @return array
     */
    public function getMongoGameData($gids,$type=1){
        if(empty($gids)) return [];

        $gidsArr = [];
        if(is_array($gids)){
            foreach ($gids as $k => $v) {
                $gidsArr[] = (int)$v;
            }
        }else{
            $gidsArr = [(int)$gids];
        }
            
        $mongo = mongoService();
        if($type == 1){
            //足球
            $game = $mongo->select('fb_game',['game_id'=>['$in'=>$gidsArr]],['union_id','game_id','union_name','union_color','home_team_name','away_team_name','home_team_id','away_team_id','home_team_rank','away_team_rank','game_start_timestamp','gtime','game_starttime','score','half_score','is_go','game_state']);
        }else{
            //篮球
            $game = $mongo->select('bk_game_schedule',['game_id'=>['$in'=>$gidsArr]],['union_id','game_id','union_name','union_color','home_team_name','away_team_name','home_team_id','away_team_id','home_team_rank','away_team_rank','game_timestamp','game_info','game_status']);
        }
        
        $gameArr = [];
        foreach ($game as $k => $v) {
            if($type == 1){
                //足球数据处理
                $v['gtime']      = TimeISTrue($v['game_start_timestamp'], $v['gtime'], $v['game_starttime']);
            }else{
                //篮球数据处理
                $game_info  = $v['game_info'];
                $v['score']      = $game_info[3].'-'.$game_info[4];
                $v['half_score'] = ($game_info[5] + $game_info[7]) .'-'. ($game_info[6] + $game_info[8]);
                $v['game_state'] = $v['game_status'];
                $v['gtime']      = $v['game_timestamp'];
            }
            $gameArr[$v['game_id']] = $v;
        }
        return $gameArr;
    }
}