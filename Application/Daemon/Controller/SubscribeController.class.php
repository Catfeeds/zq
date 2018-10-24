<?php
/**
 * 环信、友盟、APNS用户订阅推送
 * @author huangzl <496331832@qq.com>
 * @since  2016-9-22
 */
use Think\Controller;

class SubscribeController extends Controller
{
    /**
     * 查询竞猜用户，向订阅者推送消息---友盟
     */
    public function umengSubPush()
    {
        import('Vendor.umeng.Umeng');

        set_time_limit(0);
        ini_set('default_socket_timeout', -1);

        $redis = connRedis();

        while (1) {
            $msgid = $redis->rPop(C('um') . 'user_gameball_push_list');
            echo 'msgid:' . $msgid . "\r\n";
            if ($msgid) {
                $config = C('umeng');
                $Umeng = new Umeng($config['AppKey'], $config['AppMasterSecret']);

                $msg = $redis->hmget(C('um') . 'user_gameball_push_msg:' . $msgid, ['user', 'content', 'show_type']);
                $subUser = DM('FollowUser')->field('user_id')->where(['follow_id' => $msg['user'], 'sub' => 1])->select();

                echo 'follow_count:' . count($subUser) . "\r\n";

                DM('FollowUser')->close();
                DM('FrontUser')->close();

                $msg = $redis->hmget(C('um') . 'user_gameball_push_msg:' . $msgid, ['user', 'content', 'show_type']);

                foreach ($subUser as $k => $v) {
                    $content = is_array($msg['content']) ? json_encode($msg['content']) : $msg['content'];
                    $custom = [
                        'um_module' => [
                            'module' => '10',
                            'value' => $msg['user'],
                            'show_type' => 1,
                            'alias' => [$v['user_id']],
                            'alias_type' => 'QQTY'
                        ]
                    ];

                    $options = [
                        'ticker' => $content,
                        'title' => $content,
                        'text' => $content,
                        'alias' => $v['user_id'],
                        'alias_type' => 'QQTY',
                        'after_open' => 'go_custom',
                        'custom' => json_encode($custom),
                        'production_mode' => 'true'
                    ];

                    $res = $Umeng->sendAndroidCustomizedcast($options);
                    echo $res . "\r\n";
                }
                $redis->del(C('um') . 'user_gameball_push_msg:' . $msgid); //删除已经发送的消息
            } else {
                sleep(5);
            }
        }
    }

    /**
     * 查询竞猜用户，向订阅者推送消息---环信
     */
    public function emSubPush()
    {
        import('Vendor.Easemob.Easemob');
        set_time_limit(0);
        ini_set('default_socket_timeout', -1);

        $redis = connRedis();
        $easemob = new Easemob(C('Easemob'));

        while (1) {
            $logStr = '';
            $msgid = $redis->rPop(C('em') . 'user_gameball_push_list');
            unset($emUser);
            unset($users);
            unset($v);

            $logStr .= "\r\n" . date('Y-m-d H:i:s', time()) . "\r\n" . '推送消息ID:' . $msgid . "\r\n";
            if ($msgid) {
                $msg = $redis->hmget(C('em') . 'user_gameball_push_msg:' . $msgid, ['user', 'content', 'show_type']);

                $subUser = DM('FollowUser')->field('user_id')->where(['follow_id' => $msg['user'], 'sub' => 1])->select();

                $logStr .= '关注人数:' . count($subUser) . "\r\n";

                foreach ($subUser as $key => $val) {
                    $ApnsUser = DM('ApnsUsers')->where(['user_id' => $val['user_id']])->find();
                    if (!$ApnsUser || ($ApnsUser['cert_no'] && $ApnsUser['cert_no'] != 'APNS_distribution')) {
                        $emUser[] = $val['user_id'];
                    }
                }
                $logStr .= '将要向谁发出：' . json_encode($emUser) . "\r\n";
                if ($emUser) {
                    $users = array_chunk($emUser, 20); //分割20个用户为一批来群发
                    $sendTimes = 0;
                    foreach ($users as $v) {
                        $content = $msg['content'];
                        $ext = [
                            'em_apns_ext' => [
                                'em_push_title' => $content,
                                'em_module' => ['module' => '10', 'value' => $msg['user'], 'url' => $msg['user']],
                                'show_type' => 1
                            ]
                        ];

                        $res = $easemob->sendText($from = "admin", $target_type = 'users', $target = $v, $content, $ext);
                        $logStr .= '已发出：' . json_encode($res['data']) . "\r\n";

                        $sendTimes++;
                        if ($sendTimes >= 25 && $sendTimes % 25 == 0) //每推送25次休息一秒
                            sleep(1);
                    }
                }

                $redis->del(C('em') . 'user_gameball_push_msg:' . $msgid); //删除已经发送的消息
                $logStr .= ' mysql：' . (DM()->getDbError() ?: 'OK ');
                echo $logStr;
                DM('FollowUser')->close();
                DM('FrontUser')->close();
            } else {
                sleep(5);
            }
        }
    }

    /**
     * apns 订阅推送
     */
    public function _apnsPush()
    {
        set_time_limit(120);
        $redis = connRedis();
        $task = $redis->get('push_apns_gameball');

        if ($task)
            return;

        $redis->set('push_apns_gameball', 1, 60);

        $_queues = $redis->lRange('apns_user_gameball_push_queue', 0, 10);
        $logStr = '';
        $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] ";
        if (!empty($_queues)) {
            //链接
            import('Vendor.apns.ApnsPush');
            $apns = new ApnsPush(C('apns_env'), 'qqty888');
            $apns->connect();
            $queues = $redis->lRange('apns_user_gameball_push_queue', 0, 10);

            //获取订阅用户id
            foreach ($queues as $k => $v) {
                $redis->lPop('apns_user_gameball_push_queue');
                $msg = json_decode($v, true);

                //不推无效的设备
                if ($redis->sIsMember('apns_invalid_token_lists_' . C('apns_env'), $msg['device_token']))
                    continue;

                //推送
                $payload = ['aps' => [
                    'alert' => ["body" => $msg['content']]],
                    'e' => [
                        'em_module' => ['module' => '10', 'value' => $msg['pub_id'], 'url' => $msg['pub_id']],
                        'show_type' => 1
                    ]
                ];
                if (!$apns->fp) {
                    $apns->connect();
                }
                $apns->setBody($payload);
                $res = $apns->send($msg['device_token'], $k);

                if (!$res) {
                    $logStr .= "\r\n队列 " . $msg['content'] . " error:未发出\r\n";
                } else {

                    $logStr .= "\r\n队列 " . $msg['device_token'] . $msg['content'] . " 已发出 \r\n";
                }
            }

            sleep(1);
            $err = $apns->readErrMsg();
            $apns->close();
            if (is_array($err) && isset($err['identifier'])) {
                $err_queue_id = $err['identifier'];

                //记录无效的token，下次不再发送
                $err_q = $queues[$err_queue_id];
                $err_m = json_decode($err_q, true);
                $redis->sAdd('apns_invalid_token_lists_' . C('apns_env'), $err_m['device_token']);
                $logStr .= "apns 返回的错误 error:{$err['statusMessage']} queue_id:{$err_queue_id}\r\n";

                //获取错误开始的消息新生成到队列
                foreach ($queues as $k2 => $msg2) {
                    if ($k2 > $err['identifier']) {
                        $redis->rpush('apns_user_gameball_push_queue', $msg2);
                        $logStr .= "重新生成队列 $msg2 \r\n";
                    }
                }
            }

            $logStr .= "APNS 推送完成\r\n";
        } else {
            $logStr = '[' . date('Y-m-d H:i:s', time()) . "] APNS无推送数据\r\n";
        }
        $redis->set('push_apns_gameball', null);

    }

    /**
     * apns 订阅推送
     */
    public function apnsPush()
    {
        set_time_limit(120);

        $redis = connRedis();
        $task = $redis->get('push_apns_gameball');

        if ($task)
            return;

        $redis->set('push_apns_gameball', 1, 60);

        $queues = $redis->lRange('apns_user_gameball_push_queue', 0, 50);

        $logStr = '[' . date('Y-m-d H:i:s', time()) . "] 开始推送";
        if (!empty($queues)) {
            //获取订阅用户id
            foreach ($queues as $k => $v) {
                $redis->lPop('apns_user_gameball_push_queue');
                $msg = json_decode($v, true);

                //推送
                $payload = ['aps' => [
                    'alert' => ["body" => $msg['content']]],
                    'e' => [
                        'em_module' => ['module' => '10', 'value' => $msg['pub_id'], 'url' => $msg['pub_id']],
                        'show_type' => 1
                    ]
                ];

                $post_data = [
                    'token' => $msg['device_token'],
                    'platform' => $msg['cert_no'],
                    'payload' => json_encode($payload)
                ];
                $res = httpPost(C('push_adress'), $post_data);
                $logStr .= "\r\n队列 {$k}：" . $msg['device_token'] . "\r\n" . $msg['content'] . " \r\n{$msg['cert_no']}， 状态：{$res['http_code']}\r\n";

            }

            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 推送完成\r\n";
            $this->log($logStr);
        } else {
            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 无推送数据\r\n";
        }


        $redis->set('push_apns_gameball', null);

    }

    /**
     * 友盟推送队列
     */
    public function umengQueuePush()
    {
        set_time_limit(120);

        $redis = connRedis();
        $task = $redis->get('push_umeng_gameball2');
        if ($task)
            return;

        $redis->set('push_umeng_gameball2',1, 60);
        $queues = $redis->lRange('umeng_user_gameball_push_queue', 0, 10);


        $logStr = '[' . date('Y-m-d H:i:s', time()) . "] 开始推送";

        if (!empty($queues)) {
            //获取订阅用户id
            foreach ($queues as $k => $v) {
                $redis->lPop('umeng_user_gameball_push_queue');
                $payloadBody = json_decode($v, true);
                $alias = $payloadBody['alias'];

                unset($payloadBody['alias']);
                $umconfig = C('umeng');
                $exprie_time = $umconfig['expire_time'] ? $umconfig['expire_time'] : 3600 * 12;
                $body['type'] = "customizedcast";
                $body['production_mode'] = "true";
                $body['payload']["display_type"] = "notification";
                $body['payload']["body"] = $payloadBody;
                $body['alias_type'] = "QQTY";
                $body['alias'] = $alias;
                $body['policy']['expire_time'] = date('Y-m-d H:i:s', time() + $exprie_time);;

                $alias_user = explode(',', $alias);
                $eUsercIDs = M('EasemobUser')
                    ->field('client_id')
                    ->where(['username' => ['IN', $alias_user]])
                    ->order('login_time DESC')
                    ->getField('client_id', true);

                $post_data = [
                    'platform' => $umconfig['platform'],
                    'payload' => json_encode($body),
                    'clientId' => implode(',', array_unique($eUsercIDs)),
                ];

                $res = httpPost(C('push_adress'), $post_data);

                $logStr .= "\r\n队列 {$k}, 状态：{$res['http_code']}\r\n";
            }

            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 推送完成\r\n";
            $this->log($logStr);
        } else {
            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 无推送数据\r\n";
        }

        echo $logStr;
        $redis->set('push_umeng_gameball2', null);
    }

    /**
     * 日志写
     * @param string $word
     * @param string $file
     */
    public function log($word = '', $file = '')
    {
        if (empty($file))
            $fp = fopen("Public/log/sub_apns_log.txt", "a");
        else
            $fp = fopen($file, "a");
        //flock($fp, LOCK_EX);
        fwrite($fp, $word . "\r\n");
        //flock($fp, LOCK_UN);
        fclose($fp);
    }


    public function pushTest()
    {
        $content = "全球体育足球动画直播, 马上查看";
        $userid = 590;
        $redis = connRedis();

        //友盟
        $alias = DM('FollowUser')->field('user_id')->where(['follow_id' => $userid])->getField('user_id', true);

        $chunk_alias = array_chunk($alias, 20);
        if ($chunk_alias) {
            foreach ($chunk_alias as $ck => $cv) {
                $custom = [
                    'um_module' => [
                        'module' => '10',
                        'value' => $userid,
                        'show_type' => 1,
                        'alias' => $cv,
                        'alias_type' => 'QQTY'
                    ]
                ];

                $payloadBody = [
                    'ticker' => $content,
                    'title' => $content,
                    'text' => $content,
                    'alias' => implode(',', $cv),
                    'play_vibrate' => "true",
                    'play_lights' => "true",
                    'play_sound' => "true",
                    'after_open' => 'go_custom',
                    'custom' => json_encode($custom),
                ];
                $redis->rpush('umeng_user_gameball_push_queue', json_encode($payloadBody));
            }
        }
    }
}