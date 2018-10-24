<?php
/**
 * Mqtt 服务端 监听
 * @author huangzl <496331832@qq.com>
 * @since  2016-9-22
 */
use Think\Controller;

class MqttController extends Controller
{
    private $options = null;
    public $redis = null;


    public function _initialize()
    {
        import('Vendor.mqtt.Mqtt');
        $this->options = C('MQTT');
        $this->redis = connRedis();
        echo json_encode( $this->options)."\r\n";
    }

    /**
     * 聊天室监听、保存记录
     */
    public function onChat()
    {
        set_time_limit(0);
        $client = new Mosquitto\Client(md5('qqty_onChat_' . time()));
        $client->onConnect(function ($code, $message) use ($client) {
            var_dump($code, $message);
            $client->subscribe('qqty/+/chat/#', 1);
        });

        $client->onMessage(function ($message) {
            echo $message->topic, "\n", $message->payload, "\r\n";

            $topic = $message->topic;
            $payload = $message->payload;

            $data = json_decode($payload, true);
            if ($data) {
                if ($data['action'] == 'say' && $data['dataType'] == 'text') {
                    $topics = explode('/', $topic);
                    $key = 'qqty_chat_' . $topics[1];
                    $room_type = strpos($topics[1], 'live_') !== false ? 2 : 1;
                    //保存发言 不保存机器人到数据库
                    if(!isset($data['isR'])){
                        DM('Chatlog')->add([
                            'user_id' => $data['data']['user_id'],
                            'nick_name' => $data['data']['nick_name'],
                            'content' => $data['data']['scontent'] ? $data['data']['scontent'] : $data['data']['content'],
                            'avatar' => $data['data']['head'],
                            'msg_id' => $data['data']['msg_id'],
                            'room_id' => $topics[1],
                            'chat_time' => $data['data']['chat_time'],
                            'room_type' => $room_type,
                            'user_type' => 1
                        ]);
                    }

//                    DM('Chatlog')->close();

                    //保存发言 redis
                    $this->redis->lPush($key, json_encode($data['data']));
                    $size = $this->redis->lSize($key);
                    if (!$size) {
                        $this->redis->expire($key, 86400);
                    }elseif ($size >= 200){
                        $this->redis->rpop($key);
                    }
                }
            }
        });
        $client->setCredentials('mqtt_appclient_nologin', 'mqtt_appclient_nologin');
        $client->connect($this->options['host'], 1883);
        $client->loopForever();
    }

}