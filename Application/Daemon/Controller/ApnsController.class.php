<?php
/**
 * APNS 定时任务相关
 */
use Think\Controller;

class ApnsController extends Controller
{
    /**
     * 获取无效的token，只返回一次之前推送无效的token，再次请求有可能为空
     */
    public function feedback()
    {
        //apns
        import('Vendor.apns.ApnsPush');
        $apns = new ApnsPush(C('apns_env'), 'qqty888');
        $apns->apns_urls = ['ssl://feedback.push.apple.com:2196', 'ssl://feedback.sandbox.push.apple.com:2196'];

        $apns->connect();
        $lists = $apns->feedback();
        $apns->close();
        $redis = connRedis();
        foreach ($lists as $k => $v) {
            //将无效的token保存，并且将用户绑定的改token移除
            $redis->sAdd('apns_invalid_token_lists_' . C('apns_env'), $v['deviceToken']);
            M('ApnsUsers')->where(['device_token' => $v['deviceToken']])->save(['device_token' => null]);
        }
        $redis->set('apns_invalid_token_lists_tmp_log', json_encode($lists));
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

    public function test()
    {
        $ApnsUsers = M('ApnsUsers')->where(['user_id' => I('user_id')])->find();

        echo '----------用户信息----------<br/>';
        var_dump($ApnsUsers);

        $con = I('content') ?: '全球体育';
        $module = I('module');
        $module_value = I('module_value');
        $show_type = I('show_type') ?: 1;
        $device_token = $ApnsUsers['device_token'];
        $cert_no = $ApnsUsers['cert_no'];

        $payload = ['aps' => [
            'alert' => ["body" => $con]],
            'e' => [
                'em_module' => ['module' => $module, 'value' => $module_value, 'url' => $module_value],
                'show_type' => $show_type,
            ]
        ];

        $post_data = [
            'token' => $device_token,
            'platform' => $cert_no,
            'payload' => json_encode($payload)
        ];

        echo '----------Payload----------<br/>';
        var_dump($post_data);

        $res = httpPost(C('push_adress'), $post_data);
        echo '----------post结果----------<br/>';
        var_dump($res);
        echo '----------推送地址----------<br/>';
        echo C('push_adress');
    }

    public function catMsg()
    {
        $redis = connRedis();
        //订阅
        $task = $redis->get('push_apns_gameball');
        echo 'push_apns_gameball:' . $task . "\r\n";
        $size = $redis->lSize('apns_user_gameball_push_queue');
        echo 'apns_user_gameball_push_queue:' . $size;
        //比赛
        $task2 = $redis->get('push_apns_push_gamechange');
        echo 'push_apns_push_gamechange:' . $task2 . "\r\n";
        $size2 = $redis->lSize('push_apns_msg_queue');
        echo 'push_apns_push_gamechange:' . $size2 . "\r\n";
        //球王
        $task3 = $redis->get('push_apns_message_queue');
        echo 'push_apns_message_queue:' . $task3 . "\r\n";
        $size3 = $redis->lSize('message_queue_2');
        echo 'message_queue_2:' . $size3 . "\r\n";

    }
}