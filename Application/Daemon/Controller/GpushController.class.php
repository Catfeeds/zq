<?php
/**
 * 根据每日足球、篮球赛事变化生成推送消息队列；
 * 环信、友盟、APNS推送赛事变化
 */
use Think\Controller;

class GpushController extends Controller
{
    /**
     * 获取今日足球赛程变化
     */
    public function getFbChange()
    {
        set_time_limit(120);
        $redis = connRedis();
        $data = json_decode($redis->get('fb_game_change__sec_key'), true);

        if($data){
            foreach ($data as $v) {
                $logStr= $msgid = $minutesStr = '';
                //是否有人关注了这个赛程
                $users = $redis->smembers('push_fb_game_follow:' . $v[0]);
                if ($users) {
                    unset($notic);
                    unset($notic1);
                    unset($notic2);
                    unset($change);
                    $logStr .= '['.date('Y-m-d H:i:s', time()) . "] 足球变化 game_id:". $v[0] . " user_count:" . count($users);
                    $change = $redis->hmget('push_fb_game_change:' . $v[0], ['state', 'home_score', 'away_score', 'home_red', 'away_red']);
                    //更新赛程变化
                    $up1 = $redis->hmset('push_fb_game_change:' . $v[0], ['state' => $v[1], 'home_score' => $v[2], 'away_score' => $v[3], 'home_red' => $v[6], 'away_red' => $v[7]]);
                    $up2 = $redis->expire('push_fb_game_change:' . $v[0], 3600 * 24);

                    if($up1 && $up2){
                        //比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
                        //当球队进球时，弹出推送提示，如：“印尼锦 上35’ 斯里维加亚（进球）1-1巴日托”
                        //当球队出现红牌时，弹出推送提示，如：“乌兹甲附 下88’ 曼干纳弗巴霍（中）1-1纳伦（红牌）”
                        if(in_array($v[1], ['1', '3', '4'])){
                            if ($change['home_score'] != $v[2] && $change['home_score'] < $v[2]){
                                $notic1 = "%s %s %s（进球）" . $v[2] . '-' . $v[3] . " %s";
                            }elseif($change['away_score'] != $v[3] && $change['away_score'] < $v[3]){
                                $notic1 = "%s %s %s " . $v[2] . '-' . $v[3] . " %s（进球）";
                            }elseif($change['home_red'] != $v[6] && $change['home_red'] < $v[6]){
                                $notic1 = "%s %s %s （红牌） " . $v[2] . '-' . $v[3] . " %s";
                            }elseif($change['away_red'] != $v[7] && $change['away_red'] < $v[7]){
                                $notic1 = "%s %s %s " . $v[2] . '-' . $v[3] . " %s（红牌）";
                            }
                        }
                        //echo 'gameid:'.$v[0].' 主进球：' . $v[2] .' redis 值：' . $change['home_score'] .' 客进球:' .  $v[3] . ' redis 值：'.$v[3] . $notic1 .'<br/>';

                        //当对阵比赛完场时，弹出推送提示，如: “乌兹甲附 完场 曼干纳弗巴霍（中）3-1纳伦（半场:2-1）”
                        //当对阵比赛中场时，弹出推送提示，如：“印尼锦 中场 斯里维加1-1巴日托”
                        //当对阵比赛待定时，弹出推送提示，如：“印精英 待定/推迟/中断 孟买海关VS印度中央银行（11-28 17:30）”；推迟、中断、腰斩同理
                        if (isset($change['state']) && $change['state'] != $v[1]){
                            switch($v[1]){
                                case '-1'://完场 比分:
                                    $notic2 = "%s 完场 %s " . $v[2] . '-' . $v[3] . " %s （半场：".(int)$v['4']. "-". (int)$v[5] ."）"; break;
                                case '2':
                                    $notic2 = "%s 中场 %s " . $v[2] . '-' . $v[3] . " %s";break;
                                case '-10':
                                    $notic2 = "%s 取消 %s VS %s （" . date('m-d H:i', time() - 5) . "）";break;
                                case '-11':
                                    $notic2 = "%s 待定 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                                case '-12':
                                    $notic2 = "%s 腰斩 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                                case '-13':
                                    $notic2 = "%s 中断 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                                case '-14':
                                    $notic2 = "%s 推迟 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                            }
                        }

                        if ($notic2 != '' || $notic1 !='') {
                            $game = M('GameFbinfo')->field(['union_name', 'home_team_name', 'away_team_name'])->where(['game_id' => $v[0]])->find();
                            $logStr .= ' mysql：' . (DM()->getDbError()?:'OK ');
                            $union_name     = explode(',', $game['union_name'])[0];
                            $home_team_name = explode(',', $game['home_team_name'])[0];
                            $away_team_name = explode(',', $game['away_team_name'])[0];

                            if($notic1 != ''){
                                //半场时间计算
                                $time = ($v['update_time'] + 5) - strtotime($v[11]);
                                $minutes = floor($time/60);

                                if($v[1] == '3')
                                    $minutes += 45;

                                if($minutes >= 90){
                                    $minutesStr = "90'";
                                }elseif($minutes < 1){
                                    $minutesStr = "1'";
                                }else{
                                    $minutesStr = $minutes . "'";
                                }
                                $notic = sprintf($notic1, $union_name, $minutesStr, $home_team_name, $away_team_name);
                            }

                            if($notic2 != '')
                                $notic = sprintf($notic2, $union_name, $home_team_name, $away_team_name);

                            if ($union_name && $home_team_name && $away_team_name && $v[2] !== false && $v[3] !== false) {
                                foreach ($users as $kk => $vv) {
                                    $is_push = $redis->hget('push_user:' . $vv, 'is_push');
                                    if ($is_push !== false && $is_push == 0) //默认推送，当设置了不推送是过滤掉
                                        unset($users[$kk]);
                                }

                                $msgid = $redis->incr('push_msgid');
                                $redis->lpush(C('um') . 'push_lists', $msgid); //保存消息id到友盟队列
                                $redis->lpush(C('em') . 'push_lists', $msgid); //保存消息id到环信队列
                                $redis->hmset(C('um') . 'push_msg:' . $msgid, ['user' => json_encode($users), 'content' => $notic, 'show_type' => 1]); //生成友盟消息
                                $redis->hmset(C('em') . 'push_msg:' . $msgid, ['user' => json_encode($users), 'content' => $notic, 'show_type' => 1]); //生成环信消息
                                $logStr .= ' create msg_id:' . $msgid . ' msg: ' . $notic . ' users:'.implode(',', $users);
                            }

                            //DM('GameFbinfo')->close();
                            $notic  = '';

                            $this->log($logStr, 'create_push_msg.log');

                        }
                    }
                }

                $echoStr = $logStr . " game_id:" . $v[0]  . ' msg:' . $notic . 'msgid:' . $msgid . ' users:'.implode(',', $users) . "\r\n";
                echo $echoStr;
            }
            unset($data);
        }else{
            $lStr = '['.date('Y-m-d H:i:s', time()) . "] 足球无赛事变化\r\n";
            //echo $lStr;

            $this->log($lStr, 'create_push_msg.log');

        }
    }

    /**
     * 获取今日足球赛程变化
     */
    public function getFbChangeApns()
    {
        set_time_limit(120);
        $redis = connRedis();
        $data = json_decode($redis->get('fb_game_change__sec_key'), true);
        if($data){
            foreach ($data as $v) {
                $logStr= $msgid = $minutesStr = '';
                //是否有人关注了这个赛程
                $users = $redis->smembers('push_apns_game_fb_follow:' . $v[0]);
                if ($users) {
                    unset($notic);
                    unset($notic1);
                    unset($notic2);
                    unset($change);
                    $logStr .= '['.date('Y-m-d H:i:s', time()) . "] 足球变化 game_id:". $v[0] . " user_count:" . count($users);
                    $change = $redis->hmget('push_apns_fb_game_change:' . $v[0], ['state', 'home_score', 'away_score', 'home_red', 'away_red']);
                    //更新赛程变化
                    $up1 = $redis->hmset('push_apns_fb_game_change:' . $v[0], ['state' => $v[1], 'home_score' => $v[2], 'away_score' => $v[3], 'home_red' => $v[6], 'away_red' => $v[7]]);
                    $up2 = $redis->expire('push_apns_fb_game_change:' . $v[0], 3600 * 24);

                    if($up1 && $up2){
                        //比赛状态 0:未开,1:上半场,2:中场,3:下半场,4,加时，-11:待定,-12:腰斩,-13:中断,-14:推迟,-1:完场，-10取消
                        //当球队进球时，弹出推送提示，如：“印尼锦 上35’ 斯里维加亚（进球）1-1巴日托”
                        //当球队出现红牌时，弹出推送提示，如：“乌兹甲附 下88’ 曼干纳弗巴霍（中）1-1纳伦（红牌）”
                        if(in_array($v[1], ['1', '3', '4'])){
                            if ($change['home_score'] != $v[2] && $change['home_score'] < $v[2]){
                                $notic1 = "%s %s %s（进球）" . $v[2] . '-' . $v[3] . " %s";
                            }elseif($change['away_score'] != $v[3] && $change['away_score'] < $v[3]){
                                $notic1 = "%s %s %s " . $v[2] . '-' . $v[3] . " %s（进球）";
                            }elseif($change['home_red'] != $v[6] && $change['home_red'] < $v[6]){
                                $notic1 = "%s %s %s （红牌） " . $v[2] . '-' . $v[3] . " %s";
                            }elseif($change['away_red'] != $v[7] && $change['away_red'] < $v[7]){
                                $notic1 = "%s %s %s " . $v[2] . '-' . $v[3] . " %s（红牌）";
                            }
                        }
                        //echo 'gameid:'.$v[0].' 主进球：' . $v[2] .' redis 值：' . $change['home_score'] .' 客进球:' .  $v[3] . ' redis 值：'.$v[3] . $notic1 .'<br/>';

                        //当对阵比赛完场时，弹出推送提示，如: “乌兹甲附 完场 曼干纳弗巴霍（中）3-1纳伦（半场:2-1）”
                        //当对阵比赛中场时，弹出推送提示，如：“印尼锦 中场 斯里维加1-1巴日托”
                        //当对阵比赛待定时，弹出推送提示，如：“印精英 待定/推迟/中断 孟买海关VS印度中央银行（11-28 17:30）”；推迟、中断、腰斩同理
                        if (isset($change['state']) && $change['state'] != $v[1]){
                            switch($v[1]){
                                case '-1'://完场 比分:
                                    $notic2 = "%s 完场 %s " . $v[2] . '-' . $v[3] . " %s （半场：".(int)$v['4']. "-". (int)$v[5] ."）"; break;
                                case '2':
                                    $notic2 = "%s 中场 %s " . $v[2] . '-' . $v[3] . " %s";break;
                                case '-10':
                                    $notic2 = "%s 取消 %s VS %s （" . date('m-d H:i', time() - 5) . "）";break;
                                case '-11':
                                    $notic2 = "%s 待定 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                                case '-12':
                                    $notic2 = "%s 腰斩 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                                case '-13':
                                    $notic2 = "%s 中断 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                                case '-14':
                                    $notic2 = "%s 推迟 %s VS %s （" . date('m-d H:i', time() - 5). "）";break;
                            }
                        }

                        if ($notic2 != '' || $notic1 !='') {
                            $game = DM('GameFbinfo')->field(['union_name', 'home_team_name', 'away_team_name'])->where(['game_id' => $v[0]])->find();
                            $logStr .= ' mysql：' . (DM()->getDbError()?:'OK ');
                            $union_name     = explode(',', $game['union_name'])[0];
                            $home_team_name = explode(',', $game['home_team_name'])[0];
                            $away_team_name = explode(',', $game['away_team_name'])[0];

                            if($notic1 != ''){
                                //半场时间计算
                                $time = ($v['update_time'] + 5) - strtotime($v[11]);
                                $minutes = floor($time/60);

                                if($v[1] == '3')
                                    $minutes += 45;

                                if($minutes >= 90){
                                    $minutesStr = "90'";
                                }elseif($minutes < 1){
                                    $minutesStr = "1'";
                                }else{
                                    $minutesStr = $minutes . "'";
                                }
                                $notic = sprintf($notic1, $union_name, $minutesStr, $home_team_name, $away_team_name);
                            }

                            if($notic2 != '')
                                $notic = sprintf($notic2, $union_name, $home_team_name, $away_team_name);

                            if ($union_name && $home_team_name && $away_team_name && $v[2] !== false && $v[3] !== false) {
                                foreach ($users as $kk => $vv) {
                                    $is_push = $redis->hget('push_user:' . $vv, 'is_push');

                                    if ($is_push !== false && $is_push == 0){
                                        unset($users[$kk]);
                                    }else{
                                        //无效的token不推送
                                        if(!$redis->sIsMember('apns_invalid_token_lists_' . C('apns_env'), $vv)){
                                            $queue_id = $redis->rpush('push_apns_msg_queue', json_encode(['device_token' => $vv, 'content' => $notic,'fb_game_id' => $v[0]]));
                                            $logStr .= ' create msg_id:' . $queue_id . ' msg: ' . $notic . ' users:'.implode(',', $users);
                                        }
                                    }
                                }


                            }

                            DM('GameFbinfo')->close();
                            $notic  = '';

                           $this->log($logStr, 'apns_create_push_msg.log');
                        }
                    }
                }

                $echoStr = $logStr . " game_id:" . $v[0]  . ' msg:' . $notic . 'msgid:' . $queue_id . ' users:'.implode(',', $users) . "\r\n";
                echo $echoStr;
            }
            unset($data);
        }else{
            $lStr = '['.date('Y-m-d H:i:s', time()) . "] 足球无赛事变化\r\n";
//            echo $lStr;
        }
    }

    /**
     * 通用，获取今日赛程变化
     */
    public function getBkChange()
    {
        set_time_limit(120);
        $redis = connRedis();
        $data = json_decode($redis->get('bk_game_change__sec_key'), true);
        if ($data) {
            foreach ($data as $v) {
                $logStr     = $msgid  = '';
                $users = $redis->smembers('push_bk_game_follow:' . $v[0]);
                if ($users) {
                    unset($notic);
                    unset($change);
                    $logStr .= "[".date('Y-m-d H:i:s', time()) . "] 篮球变化 game_id:". $v[0] . " user_count:" . count($users);
                    $change = $redis->hmget('push_bk_game_change:' . $v[0], ['state', 'home_score', 'away_score']);

                    // 联盟 每节变化（完场中场） 主队 0-0 客队 （中场：0-0）
                    if(isset($change['state']) && $change['state'] != $v[1]){
                        switch($v[1]){
                            case '-1':
                                $notic = "%s 完场 %s " . $v[3] . '-' . $v[4] . " %s （中场：" . ($v[5] + $v[7]) . '-' . ($v[6] + $v[8]) . "）"; break;
                            case '2':
                                $notic = "%s 第一节 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '50':
                                $notic = "%s 中场 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '4':
                                $notic = "%s 第三节 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '5':

                                $notic = "%s 第四节 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '6':
                                $notic = "%s 加时1 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '7':
                                $notic = "%s 加时2 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '8':
                                $notic = "%s 加时2 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                        }

                        if ($notic != '') {
                            $game = DM('GameBkinfo')->field(['union_name', 'home_team_name', 'away_team_name'])->where(['game_id' => $v[0]])->find();
                            $logStr .= ' mysql：' . (DM()->getDbError()?:'OK ');
                            $union_name     = explode(',', $game['union_name'])[0];
                            $home_team_name = explode(',', $game['home_team_name'])[0];
                            $away_team_name = explode(',', $game['away_team_name'])[0];

                            if ($union_name && $home_team_name && $away_team_name) {
                                $content = sprintf($notic, $union_name, $home_team_name, $away_team_name);

                                foreach ($users as $kk => $vv) {
                                    $is_push = $redis->hget('push_user:' . $vv, 'is_push');
                                    if ($is_push !== false && $is_push == 0) //默认推送，当设置了不推送是过滤掉
                                        unset($users[$kk]);
                                }

                                $msgid = $redis->incr('push_msgid');
                                $redis->lpush(C('um') . 'push_lists', $msgid); //保存消息id到友盟队列
                                $redis->lpush(C('em') . 'push_lists', $msgid); //保存消息id到环信队列
                                $redis->hmset(C('um') . 'push_msg:' . $msgid, ['user' => json_encode($users), 'content' => $content, 'show_type' => 1]); //生成友盟消息
                                $redis->hmset(C('em') . 'push_msg:' . $msgid, ['user' => json_encode($users), 'content' => $content, 'show_type' => 1]); //生成环信消息
                                $logStr .= ' create msg_id:' . $msgid . ' msg：' . $content  . ' users:'.implode(',', $users);
                            }

                            DM('GameBkinfo')->close();
                            $notic  = '';

                            $this->log($logStr, 'create_push_msg.log');

                        }
                    }
                }
                //更新赛程变化
                $redis->hmset('push_bk_game_change:' . $v[0], ['state' => $v[1], 'home_score' => $v[3], 'away_score' => $v[4]]);

                //设置赛程变化信息的有效期
                $redis->expire('push_bk_game_change:' . $v[0], 3600 * 24);

                $echoStr = $logStr . " game_id:" . $v[0] . ' msg:' . $notic . 'msgid:' . $msgid . ' users:' . implode(',', $users)  . "\r\n";
                echo $echoStr;
            }
        }else{
            $lStr = '['.date('Y-m-d H:i:s', time()) . "] 篮球无赛事变化\r\n";
//            echo $lStr;
        }
    }

    /**
     * 通用，获取今日赛程变化
     */
    public function getBkChangeApns()
    {
        set_time_limit(120);
        $redis = connRedis();
        $data = json_decode($redis->get('bk_game_change__sec_key'), true);
        if ($data) {
            foreach ($data as $v) {
                $logStr     = $msgid  = '';
                $users = $redis->smembers('push_apns_game_bk_follow:' . $v[0]);

                if ($users) {
                    unset($notic);
                    unset($change);
                    $logStr .= "[".date('Y-m-d H:i:s', time()) . "] 篮球变化 game_id:". $v[0] . " user_count:" . count($users);
                    $change = $redis->hmget('push_apns_bk_game_change:' . $v[0], ['state', 'home_score', 'away_score']);

                    // 联盟 每节变化（完场中场） 主队 0-0 客队 （中场：0-0）
                    if(isset($change['state']) && $change['state'] != $v[1]){
                        switch($v[1]){
                            case '-1':
                                $notic = "%s 完场 %s " . $v[3] . '-' . $v[4] . " %s （中场：" . ($v[5] + $v[7]) . '-' . ($v[6] + $v[8]) . "）"; break;
                            case '2':
                                $notic = "%s 第一节 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '50':
                                $notic = "%s 中场 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '4':
                                $notic = "%s 第三节 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '5':
                                $notic = "%s 第四节 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '6':
                                $notic = "%s 加时1 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '7':
                                $notic = "%s 加时2 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                            case '8':
                                $notic = "%s 加时2 %s " . $v[3] . '-' . $v[4] . " %s"; break;
                        }

                        if ($notic != '') {
                            $game = DM('GameBkinfo')->field(['union_name', 'home_team_name', 'away_team_name'])->where(['game_id' => $v[0]])->find();
                            $logStr .= ' mysql：' . (DM()->getDbError()?:'OK ');
                            $union_name     = explode(',', $game['union_name'])[0];
                            $home_team_name = explode(',', $game['home_team_name'])[0];
                            $away_team_name = explode(',', $game['away_team_name'])[0];

                            if ($union_name && $home_team_name && $away_team_name) {
                                $content = sprintf($notic, $union_name, $home_team_name, $away_team_name);

                                foreach ($users as $kk => $vv) {
                                    $is_push = $redis->hget('push_user:' . $vv, 'is_push');
                                    if ($is_push !== false && $is_push == 0){
                                        unset($users[$kk]);
                                    }else{
                                        if(!$redis->sIsMember('apns_invalid_token_lists_' . C('apns_env'), $vv)){
                                            $queue_id = $redis->rpush('push_apns_msg_queue', json_encode(['device_token' => $vv, 'content' => $content,'bk_game_id' => $v[0]]));
                                        }
                                    }
                                }

                                $logStr .= ' create msg_id:' . $queue_id . ' msg：' . $content  . ' users:'.implode(',', $users);
                            }

                            DM('GameBkinfo')->close();
                            $notic  = '';

                            $this->log($logStr, 'apns_create_push_msg.log');

                        }
                    }
                }
                //更新赛程变化
                $redis->hmset('push_apns_bk_game_change:' . $v[0], ['state' => $v[1], 'home_score' => $v[3], 'away_score' => $v[4]]);

                //设置赛程变化信息的有效期
                $redis->expire('push_apns_bk_game_change:' . $v[0], 3600 * 24);

                $echoStr = $logStr . " game_id:" . $v[0] . ' msg:' . $notic . 'msgid:' . $msgid . ' users:' . implode(',', $users)  . "\r\n";
                echo $echoStr;
            }
        }else{
            $lStr = '['.date('Y-m-d H:i:s', time()) . "] 篮球无赛事变化\r\n";
//            echo $lStr;
        }
    }

    /**
     * apns 订阅推送
     */
    public function apnsPush(){
        set_time_limit(120);

        $redis = connRedis();
        $task = $redis->get('push_apns_push_gamechange');
        if($task)
            return;

        $redis->set('push_apns_push_gamechange',1, 60);
        $queues = $redis->lRange('push_apns_msg_queue', 0, 30);

        $logStr = '['.date('Y-m-d H:i:s', time()) . "] 开始推送";
        if (!empty($queues)){
            //token对应的证书
            $token = [];
            foreach ($queues as $k1 => $v1) {
                $msg1 = json_decode($v1, true);
                $token[] = $msg1['device_token'];
            }

            $cert_no = M('ApnsUsers')->master(true)->where(['device_token' => ['IN', $token]])->getField('device_token, cert_no');

            //推送
            foreach ($queues as $k => $v) {
                $redis->lPop('push_apns_msg_queue');
                $msg = json_decode($v, true);

                //推送,此处应该制定cert_no制定证书名
                $payload = ['aps' => [
                    'alert' => ["body" => $msg['content']]],
                    'e' =>['show_type' => 1]
                ];

                $post_data = [
                    'token'     => $msg['device_token'],
                    'platform'  => $cert_no[$msg['device_token']],
                    'payload'   => json_encode($payload)
                ];
                $res = httpPost(C('push_adress'), $post_data);
                $logStr .= "\r\n队列 {$k}：" . $msg['device_token'] . "\r\n" . $msg['content'] . " \r\n{$cert_no[$msg['device_token']]}， 状态：{$res['http_code']}\r\n";

            }

            $logStr .= '['.date('Y-m-d H:i:s', time()) . "] 推送完成\r\n";
            $this->log($logStr, 'Public/log/apns_push_game_change.txt');
        }else{
            $logStr .= '['.date('Y-m-d H:i:s', time()) . "] 无推送数据\r\n";
        }

        $redis->set('push_apns_push_gamechange', null);

    }

    /**
     * 友盟，定时推送赛事变化，注意：有些数据还是写死测试推送
     */
    public function umPush()
    {
        set_time_limit(120);

        $redis = connRedis();
        $msgid = $redis->rpop(C('um') . 'push_lists');
        $logStr = '';
        if ($msgid) {
            $msg = $redis->hmget(C('um') . 'push_msg:' . $msgid, ['user', 'content', 'show_type']);

            $users = json_decode($msg['user'], true);
            $logStr .= "[".date('Y-m-d H:i:s', time()) . '] 友盟推送 msg_id:' . $msgid . ' user_count:' . count($users) . ' msg:' . $msg['content'];
            if ($msg['user'] && $msg['content']) {
                $users = array_chunk($users, 20); //分割100个用户为一批来群发
                $sendTimes = 0;

                foreach ($users as $v) {
                    $custom = [
                        'um_module' => [
                            'show_type' => 1,
                            'alias'     => $v,
                            'alias_type'=>'QQTY'
                        ]
                    ];

                    $payloadBody = [
                        'ticker'        => $msg['content'],
                        'title'         => $msg['content'],
                        'text'          => $msg['content'],
                        'play_vibrate'  => "true",
                        'play_lights'   => "true",
                        'play_sound'    => "true",
                        'after_open'    => 'go_custom',
                        'custom'        => json_encode($custom),
                    ];

                    $umconfig = C('umeng');
                    $exprie_time = $umconfig['expire_time'] ? $umconfig['expire_time'] : 3600 * 12;
                    $body['type'] = "customizedcast";
                    $body['production_mode'] = "true";
                    $body['payload']["display_type"] = "notification";
                    $body['payload']["body"] = $payloadBody;
                    $body['alias_type'] = "QQTY";
                    $body['policy']['expire_time'] = date('Y-m-d H:i:s', time() + $exprie_time);;

                    $body['alias'] = implode(',', $v);

                    //用户对应的clientId
                    $eUsercIDs = M('EasemobUser')
                        ->field('client_id')
                        ->where(['username' => ['IN', $v]])
                        ->order('login_time DESC')
                        ->getField('client_id', true);

                    $post_data = [
                        'platform' => $umconfig['platform'],
                        'payload' => json_encode($body),
                        'clientId' => implode(',', array_unique($eUsercIDs)),
                    ];
                    httpPost(C('push_adress'), $post_data);

                    $sendTimes++;

                    if ($sendTimes >= 25 && $sendTimes % 25 == 0){
                        sleep(1);
                    }
                }

                $redis->del(C('um') . 'push_msg:' . $msgid); //删除已经发送的消息
                $logStr .= ' users:' . $msg['user'];

                //$this->log($logStr, 'push_game_change.log');

                echo $logStr . "\r\n";
            }

        }else{
            $lStr = '['.date('Y-m-d H:i:s', time()) . "] 友盟无推送数据\r\n";
            //echo $lStr;
        }
    }

    /**
     * 环信，定时推送赛事变化,注意：有些数据还是写死测试推送
     */
    public function emPush()
    {
        set_time_limit(120);
        import('Vendor.Easemob.Easemob');
        $redis = connRedis();
        $is_log = 0;
        $msgid = $redis->rpop(C('em') . 'push_lists');

        if ($msgid) {
            $logStr = '';
            $msg    = $redis->hmget(C('em') . 'push_msg:' . $msgid, ['user', 'content', 'show_type']);
            $users = json_decode($msg['user'], true);
            $logStr .= "[".date('Y-m-d H:i:s', time()) . '] 环信推送 msg_id:' . $msgid . ' user_count:' . count($users) . ' msg:' . $msg['content'];
            if ($msg['user'] && $msg['content']) {
                $chunk_users    = array_chunk($users, 20); //分割20个用户为一批来群发
                $sendTimes      = 0;

                $easemob = new \Easemob(C('Easemob'), true);
                foreach ($chunk_users as $v) {
                    $ext = [
                        'em_apns_ext' => [
                            'em_push_title' => $msg['content'],
                            'show_type' => $msg['show_type']
                        ]
                    ];

                    $res = $easemob->sendText($from = "admin", $target_type = 'users', $target = $v, $msg['content'], $ext);

                    if (isset($res['httpCode']) && $res['httpCode'] == '0') {
                        //打log，将发送不成功的用户消息放回队列里
                        $_msgid = $redis->incr('push_msgid');
                        $redis->lpush(C('em') . 'push_lists', $_msgid);
                        $redis->hmset(C('em') . 'push_msg:' . $_msgid, ['user' => json_encode($v), 'content' => $msg['content'], 'show_type' => 1]);
                        $logStr .= ' send fail, rebulid msg_id ' . $_msgid;
                    }

                    $sendTimes++;
                    if ($sendTimes >= 25 && $sendTimes % 25 == 0) { //每推送25次休息一秒
                        sleep(1);
                    }
                }

                $redis->del(C('em') . 'push_msg:' . $msgid); //删除已经发送的消息
                $logStr .= ' users:' . $msg['user'];
            }

//            if($is_log == '1')
//                $this->log($logStr, 'push_game_change.log');
        }else{
            $lStr = '['.date('Y-m-d H:i:s', time()) . "] 环信无推送数据\r\n";
            //echo $lStr;

//            if($is_log == '1')
//                $this->log($lStr, 'push_game_change.log');
        }

        //echo $logStr . "\r\n";
    }



    /**
     * 当日赛事变化数据解析（数据库数据）
     * @return array 赛事变化数据
     */
    public function fb()
    {
        $rData = $arr = [];
        $time = NOW_TIME - 40;
        $res = DM()->query('select game_id,game_id_new,change_str,update_time from qc_change_fb where update_time = (select update_time as utime from qc_change_fb where update_time > '.$time.' order by update_time desc limit 1) order by id');

        if(!empty($res))
        {
            if($res[0]['update_time'] + 20 > time())
            {
                foreach($res as $k=>$v)
                {
                    $arr = explode('^',$v['change_str']);
                    $aTemp[0] = $arr[0];                        //赛事ID
                    $aTemp[1] = $arr[1];                        //赛事状态
                    $aTemp[2] = $arr[2] == null?'':$arr[2];     //主队得分
                    $aTemp[3] = $arr[3] == null?'':$arr[3];     //客队得分
                    $aTemp[4] = $arr[4] == null?'':$arr[4];     //半场主队得分
                    $aTemp[5] = $arr[5] == null?'':$arr[5];     //半场客队得分
                    $aTemp[6] = $arr[6] == null?'':$arr[6];     //主队红牌
                    $aTemp[7] = $arr[7] == null?'':$arr[7];     //客队红牌
                    $aTemp[8] = $arr[12] == null?'':$arr[12];   //主队黄牌
                    $aTemp[9] = $arr[13] == null?'':$arr[13];   //客队黄牌
                    $aTemp[10] = $arr[8];                       //比赛时间

                    $aTime      = explode(',',$arr[9]);
                    $aTime[1]   = str_pad($aTime[1]+1,2,0,STR_PAD_LEFT);
                    $aTime[2]   = str_pad($aTime[2],2,0,STR_PAD_LEFT);
                    $aTemp[11]  = implode('',$aTime);            //半场时间

                    $aTemp[12] = $arr[16] == null?'':$arr[16];   //主队角球
                    $aTemp[13] = $arr[17] == null?'':$arr[17];   //主队角球
                    $aTemp['update_time'] = $v['update_time'];
                    $rData[$v['game_id']] = $aTemp;
                }
            }
        }

        $e = DM()->getDbError();
        if($e)
            $this->log($logStr = "[".date('Y-m-d H:i:s', time()) . "] 获取变化 mysql:" . $e, 'create_push_msg.txt');

        return $rData;
    }

    /**
     * 获取篮球change数据
     * @return array
     */
    public function bk()
    {
        $rData = $arr = [];
        $time = NOW_TIME - 40;

        $res = DM()->query('select game_id,game_id_new,change_str,update_time from qc_bk_change where update_time = (select update_time as utime from qc_bk_change where update_time > ' . $time . ' order by update_time desc limit 1) order by id');

        if (!empty($res))
        {
            if ($res[0]['update_time'] + 40 > time())
            {
                foreach ($res as $k => $v)
                {
                    $arr = explode('^', $v['change_str']);
                    $aTemp[0] = $arr[0];    //赛事ID
                    $aTemp[1] = $arr[1];    //进行赛事节数
                    $aTemp[2] = $arr[2];    //比赛小节时间
                    $aTemp[3] = $arr[3];    //主队总得分
                    $aTemp[4] = $arr[4];    //客队总得分
                    $aTemp[5] = $arr[5];    //第一节主队得分
                    $aTemp[6] = $arr[6];    //第一节主队得分
                    $aTemp[7] = $arr[7];    //第二节主队得分
                    $aTemp[8] = $arr[8];    //第二节主队得分
                    $aTemp[9] = $arr[9];    //第三节主队得分
                    $aTemp[10] = $arr[10];  //第三节主队得分
                    $aTemp[11] = $arr[11];  //第四节主队得分
                    $aTemp[12] = $arr[12];  //第四节主队得分
                    $aTemp[13] = $arr[13];  //加时节数
                    $aTemp[14] = $arr[16];  //加时第一节主队得分
                    $aTemp[15] = $arr[17];   //加时第一节客队得分
                    $aTemp[16] = $arr[18];  //加时第二节主队得分
                    $aTemp[17] = $arr[19];  //加时第二节客队得分
                    $aTemp[18] = $arr[20];  //加时第三节主队得分
                    $aTemp[19] = $arr[21];  //加时第三节客队得分
                    $rData[$arr[0]] = $aTemp;
                }
            }
        }
        $e = DM()->getDbError();
        if($e)
            $this->log($logStr = "[".date('Y-m-d H:i:s', time()) . "] 获取变化 mysql:" . $e, 'create_push_msg.txt');

        return $rData;
    }

    public function cacheChange(){
        $redis = connRedis();
        //足球数据
        $fbData = $this->fb();
        if($fbData){
            $redis->set('fb_game_change__sec_key', json_encode($fbData), 5);
        }

        //篮球数据
        $bkData = $this->bk();
        if($bkData){
            $redis->set('bk_game_change__sec_key', json_encode($bkData), 5);
        }

        var_dump($fbData,$bkData);
    }


    /**
     * 日志写
     * @param string $word
     * @param string $file
     */
    public function log($word = '', $file = '')
    {
        if (empty($file))
            $fp = fopen("pushLog.txt", "a");
        else
            $fp = fopen($file, "a");
        //flock($fp, LOCK_EX);
        fwrite($fp, $word . "\r\n");
        //flock($fp, LOCK_UN);
        fclose($fp);
    }

    //测试
    public function gfbfollowuser(){
        $redis = connRedis();
        $users = $redis->smembers('push_fb_game_follow:' . I('game_id'));
        print_r($users);
    }

    //测试
    public function gbkfollowuser(){
        $redis = connRedis();
        $users = $redis->smembers('push_bk_game_follow:' . I('game_id'));
        print_r($users);
    }

    /**
     * 操作日志
     */
    public function opend_log(){
        $redis = connRedis();
        $is_log = $redis->get('push_open_log');
        echo '当前日志开启状态：' . ($is_log == '1' ? '开启' : '关闭') . '\\r\\n';
        if(I('status') != ''){
            $res = $redis->set('push_open_log', I('status'));
            if($res){
                echo ' 操作成功';
            }else{
                echo ' 操作失败';
            }
        }
    }
}

