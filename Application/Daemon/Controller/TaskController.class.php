<?php
/**
 * 公用定时任务，业务处理控制器
 * 1、购买优惠券，在到期还有5、3天时，发推送
 * 2、订阅理财产品，有推介发布时，发推送
 * 3、成功订购产品，到发布时间时，发推送
 */
use Think\Controller;

class TaskController extends Controller
{
    /**
     * apns 推送 （每1分钟）
     */
    public function apnsPush()
    {
        set_time_limit(120);
        $redis = connRedis();

        $is_log = $redis->get('push_apns_queue_open_log');
        $msg_list = $redis->lRange('apns_push_queue', 0, 100);

        $logStr = '';
        $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] ";
        if (!empty($msg_list)) {
            //链接
            import('Vendor.apns.ApnsPush');
            $apns = new ApnsPush(C('apns_env'), 'qqty888');
            $apns->connect();
            //发送
            foreach ($msg_list as $k => $msg) {
                $redis->lPop('apns_push_queue');
                $msg = json_decode($msg, true);

                //无效的token不推送
                if ($redis->sIsMember('apns_invalid_token_lists_' . C('apns_env'), $msg['device_token']))
                    continue;

                $payload = ['aps' => [
                    'alert' => ["body" => $msg['content']]],
                    'e' => ['show_type' => 1]
                ];

                if (!$apns->fp) {
                    $apns->connect();
                }
                $apns->setBody($payload);
                $res = $apns->send($msg['device_token'], $k);

                if (!$res) {
                    $logStr .= '队列 ' . $msg['content'] . " error:未发出\r\n";
                } else {

                    $logStr .= '队列 ' . $msg['content'] . " 已发出 \r\n";
                }
            }

            sleep(1);
            $err = $apns->readErrMsg();
            $apns->close();
            if (is_array($err) && isset($err['identifier'])) {
                $err_queue_id = $err['identifier'];
                $logStr .= "apns 返回的错误 error:{$err['statusMessage']} queue_id:{$err_queue_id}\r\n";
                //将无效token记录
                $ms = json_decode($msg_list[$err_queue_id], true);
                $redis->sAdd('apns_invalid_token_lists_' . C('apns_env'), $ms['device_token']);

                //获取错误开始的消息新生成到队列
                foreach ($msg_list as $k2 => $msg2) {
                    if ($k2 > $err['identifier']) {
                        $redis->rpush('apns_push_queue', $msg2);
                        $logStr .= "重新生成队列 $msg2 \r\n";
                    }
                }
            }

            $logStr .= "APNS 推送完成\r\n";
        } else {
            $logStr = '[' . date('Y-m-d H:i:s', time()) . "] APNS无推送数据\r\n";
        }
        echo $logStr;

        if ($is_log == '1')
            logRecord($logStr, 'apns_push_queue.log');
    }


    /**
     * 友盟推送（每分钟）
     */
    public function umPush()
    {
        set_time_limit(60);

        $redis = connRedis();
        $msg_list = $redis->lRange(C('um') . '_push_queue', 0, 200);

        foreach ($msg_list as $k => $v) {
            $redis->lPop(C('um') . '_push_queue');
            $msg = json_decode($v, true);
            $custom = [
                'um_module' => [
                    'show_type' => 1,
                    'alias' => $msg['user'],
                    'alias_type' => 'QQTY']
            ];

            $payloadBody = [
                'ticker' => $msg['content'],
                'title' => $msg['content'],
                'text' => $msg['content'],
                'alias' => $msg['user'],
                'alias_type' => 'QQTY',
                'after_open' => 'go_custom',
                'custom' => json_encode($custom),
            ];
            $umconfig = C('umeng');
            $exprie_time = $umconfig['expire_time'] ? $umconfig['expire_time'] : 3600 * 12;
            $body['type'] = "customizedcast";
            $body['production_mode'] = "true";
            $body['payload']["display_type"] = "notification";
            $body['payload']["body"] = $payloadBody;
            $body['alias_type'] = "QQTY";
            $body['alias'] = implode(',', $msg['user']);
            $body['policy']['expire_time'] = date('Y-m-d H:i:s', time() + $exprie_time);

            $eUsercIDs = M('EasemobUser')
                ->field('client_id')
                ->where(['username' => ['IN', $msg['user']]])
                ->order('login_time DESC')
                ->getField('client_id', true);

            $post_data = [
                'platform' => $umconfig['platform'],
                'payload' => json_encode($body),
                'clientId' => implode(',', array_unique($eUsercIDs)),
            ];

            httpPost(C('push_adress'), $post_data);
        }
    }

    /**
     * 推送通过addMessageToQueue的消息 每分钟
     */
    public function apnsConsumer()
    {
        set_time_limit(120);
        $redis = connRedis();
        $task = $redis->get('push_apns_message_queue');

        if ($task)
            exit('任务未结束：' . $task);

        $redis->set('push_apns_message_queue', 1, 60);

        $queues = $redis->lRange('message_queue_2', 0, 50);

        $logStr = '[' . date('Y-m-d H:i:s', time()) . "] 开始推送";
        if (!empty($queues)) {
            //获取订阅用户id
            foreach ($queues as $k => $v) {
                $redis->lPop('message_queue_2');
                $msg = json_decode($v, true);

                $payload = ['aps' => [
                    'alert' => ["body" => $msg['message']]],
                    'e' => [
                        'em_module' => ['module' => $msg['module'], 'value' => $msg['module_value'], 'url' => $msg['module_value']],
                        'show_type' => $msg['show_type'],
                    ]
                ];

                $post_data = [
                    'token' => $msg['device_token'],
                    'platform' => $msg['cert_no'],
                    'payload' => json_encode($payload)
                ];

                $res = httpPost(C('push_adress'), $post_data);
                $logStr .= "\r\n队列 {$k}：" . $msg['device_token'] . "\r\n" . $msg['message'] . " \r\n{$msg['cert_no']}， 状态：{$res['http_code']}\r\n";

            }

            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 推送完成\r\n";
            $this->log($logStr, 'Public/log/apns_message_queue.txt');
        } else {
            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 无推送数据\r\n";
        }

        $redis->set('push_apns_message_queue', null);
    }


    /**
     * 友盟 推送通过addMessageToQueue的消息 每分钟
     */
    public function umConsumer()
    {
        set_time_limit(60);
        $redis = connRedis();
        $msg_list = $redis->lRange('message_queue_3', 0, 200);

        foreach ($msg_list as $k => $v) {
            $redis->lPop('message_queue_3');
            $msg = json_decode($v, true);

            $custom = [
                'um_module' => [
                    'module' => $msg['module'],
                    'value' => $msg['module_value'],
                    'url' => $msg['module_value'],
                    'show_type' => 1,
                    'alias' => $msg['alias'],
                    'alias_type' => 'QQTY']
            ];

            $payloadBody = [
                'ticker' => $msg['message'],
                'title' => $msg['message'],
                'text' => $msg['message'],
                'play_vibrate' => "true",
                'play_lights' => "true",
                'play_sound' => "true",
                'alias_type' => 'QQTY',
                'after_open' => 'go_custom',
                'custom' => json_encode($custom),
            ];

            $umconfig = C('umeng');
            $exprie_time = $umconfig['expire_time'] ? $umconfig['expire_time'] : 3600 * 12;
            $body['type'] = "customizedcast";
            $body['production_mode'] = "true";
            $body['payload']["display_type"] = "notification";
            $body['payload']["body"] = $payloadBody;
            $body['alias_type'] = "QQTY";
            $body['alias'] = implode(',', $msg['alias']);
            $body['policy']['expire_time'] = date('Y-m-d H:i:s', time() + $exprie_time);


            $eUsercIDs = M('EasemobUser')
                ->field('client_id')
                ->where(['username' => ['IN', $msg['alias']]])
                ->order('login_time DESC')
                ->getField('client_id', true);

            $post_data = [
                'platform' => $umconfig['platform'],
                'payload' => json_encode($body),
                'clientId' => implode(',', array_unique($eUsercIDs)),
            ];
            $res = httpPost(C('push_adress'), $post_data);

//            logRecord(' 队列' . $msg['alias'] . ' ' .$msg['message'], 'intro_um_log.txt');
        }
    }

    /**
     * 环信推送任务
     */
    public function emPush()
    {
        import('Vendor.Easemob.Easemob');

        set_time_limit(60);
        ini_set('default_socket_timeout', -1);

        $redis = connRedis();
        $easemob = new Easemob(C('Easemob'));

        $msg_list = $redis->lRange('message_queue_4', 0, 200);

        $sendTimes = 0;
        foreach ($msg_list as $k => $v) {
            $redis->lPop('message_queue_4');
            $msg = json_decode($v, true);

            $ext = [
                'em_apns_ext' => [
                    'em_push_title' => $msg['message'],
                    'em_module' => ['module' => $msg['module'], 'value' => $msg['module_value'], 'url' => $msg['module_value']],
                    'show_type' => 1
                ]
            ];
            $res = $easemob->sendText($from = "admin", $target_type = 'users', $target = [$msg['users']], $msg['message'], $ext);

            echo json_encode($res['data']) . "\r\n";

            $sendTimes++;
            if ($sendTimes >= 25 && $sendTimes % 25 == 0) {
                sleep(1);
            }

            $sendTimes++;
        }
    }

    /**
     * mqtt推送方法
     */
    public function mqttPushQueue()
    {
        $redis = connRedis();
        $queue = $redis->lRange('mqtt_common_push_queue', 0, -1);
        foreach ($queue as $k => $v) {
            $opt = json_decode($v, true);
            Mqtt($opt);
            $redis->lPop('mqtt_common_push_queue');
        }
    }

    /**
     * 红包预告、红包雨触发
     */
    public function pushRedPackEvent()
    {
        echo "初始内存: " . memory_get_usage(), PHP_EOL;
        set_time_limit(0);
        $mqOption = C('MQTT');
        $redConfig= C('RED_PACKET');
        $mqClient = new Mosquitto\Client();

        $mqClient->setCredentials('mqtt_appclient_nologin', 'mqtt_appclient_nologin');
        $mqClient->connect($mqOption['host'], $mqOption['port']);

        $mqClient->onConnect(function ($code, $message) {
            echo 'Mqtt connect OK', PHP_EOL;
        });

        $redis = connRedis(['persistent' => true]);

        while (true) {
            echo "memory_get_usage: " . memory_get_usage(), PHP_EOL;
            $mqClient->loop();
            //红包查询
            $end = time() + ($redConfig['advanced'] ? $redConfig['advanced'] : 12);
            $events = $redis->zRangeByScore('redPackEvent', 0, $end, ['withscores' => TRUE]);
            foreach ($events as $k => $v) {
                $time = time();
                $keys = explode('@', $k);
                $topic = 'qqty/' . $keys[1] . '/redPacket';
                $mod = ($v - $time) % 2;
                $pubData = [];
                if ($time >= $v) {
                    $redis->zDelete('redPackEvent', $k);
                    $arrs = DM('redpkgLog')->where(['pid' => $keys[0]])->getField('unique_id', true);

                    if($arrs){
                        $pubData['data'] = [
                            'data' => $arrs,
                            'notice_str' => '触发红包',
                            'event_id' => $keys[0],
                            'timestamp' => $time,
                            'falltime' => isset($redConfig['fall_time']) ? $redConfig['fall_time'] : rand(8, 10),
                            'url' => isset($redConfig['url']) ? $redConfig['url'] : U('Api530/Chat/drawRedPacket'),
                        ];
                        $pubData['action'] = 'redPackRainingEvent';
                    }
                } elseif ($time < $v && $mod == 0) {
                    $pubData['data'] = [
                        'countdown' => $v - $time,
                        'notice_str' => '红包预告',
                        'timestamp' => $time,
                    ];
                    $pubData['action'] = 'redPackAdvanceNoticeEvent';
                }

                if ($pubData) {
                    $pubData['dataType'] = 'text';
                    $pubData['status'] = '1';
                    $mqClient->publish($topic, json_encode($pubData), 1);
                    $mqClient->loop();
                }
            }
            sleep(2);
        }
    }

    public function test()
    {
        $redis = connRedis();
        $redis->zAdd('redPackEvent', time() + 60, '175@590');
    }

    //持久化红包获取记录，账户明细
    public function updateRedpkgLog()
    {
        $redis = connRedis();
        $list = $redis->lRange('RedPacketHashLog', 0, 100);
        $evenIds = $userIds = [];

        //更新红包领取记录
        foreach ($list as $k => $v) {
            $log = json_decode($v, true);

            $redis->lpop('RedPacketHashLog');
            $data = ['user_id' => $log['user_id'], 'get_time' => $log['time'], 'get_status' => 1];
            M('RedpkgLog')->where(['unique_id' => $log['id']])->save($data);

            $evenIds[$log['event_id']][] = $log['user_id'];
            $userIds[] = $log['user_id'];
        }

        //账号流水
        if($evenIds){
            foreach($evenIds as $event_id => $uIds){
                $uIds = array_unique($uIds);
                foreach($uIds as $uk => $user_id){
                    $get_coin = (int)$redis->get('RedPacketGetLog_' . $event_id . '_' . $user_id);
                    if($get_coin > 0){
                        //更新用户不可提金币
                        M('FrontUser')->where(['id' => $user_id])->setInc('unable_coin', $get_coin);

                        //是否存在记录
                        $account_log = M('AccountLog')->master(true)->where(['user_id' => $user_id, 'link_id' => $event_id])->find();

                        //用户总金币
                        $frontUsers = M('FrontUser')->master(true)->where(['id' => $user_id])->find();
                        $total_coin = (int)$frontUsers['unable_coin'] + (int)$frontUsers['coin'];

                        //记录流水
                        if(!$account_log){
                            $save = [
                                'user_id' => $user_id,
                                'log_time' => NOW_TIME,
                                'log_type' => '23',
                                'log_status' => '1',
                                'change_num' => $get_coin,
                                'total_coin' => $total_coin,
                                'desc' => "直播间抢红包，增加" . $get_coin . '金币',
                                'link_id' => $event_id,
                                'operation_time' => NOW_TIME
                            ];
                            M('AccountLog')->add($save);
                        }else{
                            $save = [
                                'change_num' => $get_coin,
                                'total_coin' => $total_coin,
                                'desc' => "直播间抢红包，增加" . $get_coin . '金币',
                                'operation_time' => NOW_TIME
                            ];
                            M('AccountLog')->where(['id' => $account_log['id']])->save($save);
                        }
                    }
                }
            }
        }

    }

    /**
     * @param $url
     * @return mixed
     */
    public function doCurl($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 8);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
        @curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $r = curl_exec($ch);
        curl_close($ch);
        return $r;
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
}
