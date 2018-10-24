<?php
/**
 * 环信、友盟、APNS定时推送后台自定义消息
 */
use Think\Controller;

class PushController extends Controller
{
    private $Easemob = null;
    private $Umeng = null;

    public function _initialize()
    {
        if ($_REQUEST['nosign'] != 'haha')
            die('no auth!');

        //环信
        import('Vendor.Easemob.Easemob');
        $this->Easemob = new \Easemob(C('Easemob'), true);

        //友盟
        import('Vendor.umeng.Umeng');
        $config = C('umeng');

        $this->Umeng = new Umeng($config['AppKey'], $config['AppMasterSecret']);
    }

    /**
     * 定时跑方法, 调用推送工作脚本
     */
    public function crontab()
    {
        if ($_REQUEST['runType'] == '')
            die('error runType!');

        if (!isset($_SERVER['HTTP_HOST']))
            die('error HTTP_HOST!');

        switch ($_REQUEST['runType']) {
            case '1'://环信，非广播
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=Daemon/Push/emPush/nosign/haha";
                break;

            case '2'://友盟，非广播
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=Daemon/Push/umPush/nosign/haha";
                break;

            case '3'://友盟，广播
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=Daemon/Push/umengBroadCast/nosign/haha";
                break;

            case '4'://APNS
                $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=Daemon/Push/apns/nosign/haha";
                break;
        }

        //检测脚本运行
        $checkRun = S('checkRunKey_' . $_REQUEST['runType']);
        $time_out = time() - $checkRun['runtime'];
        if ($checkRun['isRun'] == 1) {
            if ($time_out > 120) {
                // TODO:工作脚本最近运行时间超时,需要检查或杀掉重启(重发)
                S('checkRunKey_' . $_REQUEST['runType'], ['isRun' => 0, 'runtime' => time()]);
//                $this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 脚本运行超时(' . $time_out . 's) ' . $url);

                //真正执行工作脚本
                $this->doCurl($url);
            }
            exit;
        }

        //真正执行工作脚本
        $this->doCurl($url);
    }

    /**
     * 友盟广播后台消息
     */
    public function umengBroadCast()
    {
        //未推送的记录
        $lists = M('EasemobUsermsg')->where(['status' => ['EQ', '0'], 'broad_cast' => 3])->find();

        //获取推送详情
        $map['id'] = $lists['msg_id'];
        $map['task_time'] = ['lt',NOW_TIME];
        $pushInfo = M('EasemobMsg')->where($map)->find();

        if (!$pushInfo || !$lists)
            die('no msg push!');

        $custom = [
            'um_module' => [
                'module' => $pushInfo['module'],
                'value' => $pushInfo['url'],
                'url' => $pushInfo['url'],
                'alias_type' => '',
                'alias' => [],
                'msg_id' => $lists['msg_id']
            ],
            'module' => $pushInfo['module'],
            'url' => $pushInfo['url']
        ];

        $payloadBody = [
            'ticker' => $pushInfo['content'],
            'title' => $pushInfo['content'],
            'text' => $pushInfo['content'],
            'play_vibrate' => "true",
            'play_lights' => "true",
            'play_sound' => "true",
            'after_open' => 'go_custom',
            'custom' => json_encode($custom),
        ];

        $umconfig = C('umeng');
        $exprie_time = $umconfig['expire_time'] ? $umconfig['expire_time'] : 3600 * 12;

        $body['type'] = "broadcast";
        $body['production_mode'] = "true";
        $body['payload']["display_type"] = "notification";
        $body['payload']["body"] = $payloadBody;
        $body['policy']['expire_time'] = date('Y-m-d H:i:s', time() + $exprie_time);

        $post_data = [
            'platform' => $umconfig['platform'],
            'payload' => json_encode($body)
        ];

        httpPost(C('push_adress'), $post_data);
        S('checkRunKey_3', ['isRun' => 0, 'runtime' => time()]);

        $msgData['is_push'] = 1;
        $msgData['push_time'] = NOW_TIME;

        //广播失败了，直接后台重发就好，不必程序来重发
        M('EasemobMsg')->where(['id' => $pushInfo['id']])->save($msgData);
        M('EasemobUsermsg')->where(['id' => $lists['id']])->save(['status' => 1]);

    }

    /**
     * 环信推送
     */
    public function emPush()
    {
        $size = 20;                //每次处理条数
        $count = $_GET['count'];    //一次定时任务里，要处理的推送条数（不定）
        $times = ($_GET['times'] ?: 0) + 1;   //一次定时任务里，要处理的批次
        $where = ['status' => ['EQ', '0'], 'user_type' => 1, 'platform' => 2];

        if (!$count)
            $count = $lists = M('EasemobUsermsg')->master(true)->where($where)->count();

        $total_times = ceil($count / $size); //总批次
        if ($total_times > 200) {
            $total_times = 200;
        }

        echo "run $times / $total_times / $count\n";

        //读出本批次要发送所有记录,一批里面可能有不同的消息id
        $pushArrs = M('EasemobUsermsg')->master(true)->where($where)->limit($size)->select();

        //判断执行的次数，执行完发送批次时，停止本次任务，更新检测脚本
        if ($times > $total_times || !$pushArrs) {
            echo 'end';
            S('checkRunKey_1', ['isRun' => 0, 'runtime' => time()]);
            //$this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 环信推送完成');//日志
            exit;
        }

        //按msgid分组
        $msgList = [];
        foreach ($pushArrs as $k => $v) {
            $msgList[$v['msg_id']]['push_id'][] = $v['id'];
            $msgList[$v['msg_id']]['push_user'][] = $v['push_user'];
        }

        //按msgid和所对应的user_id推送
        foreach ($msgList as $msg_id => $v2) {
            //获取推送详情
            $pushInfo = M('EasemobMsg')->where(['id' => $msg_id])->find();

            if ($pushInfo) {
                echo "push msgid : $msg_id \r\n";
                $ext = ['em_apns_ext' =>
                    [
                        'em_push_title' => $pushInfo['content'],
                        'em_module' => ['module' => $pushInfo['module'], 'value' => $pushInfo['url'], 'url' => $pushInfo['url']],
                        'show_type' => 1,
                        'msg_id' => $msg_id
                    ]
                ];

                //只要请求得到服务器，一般都返回成功
                $res = $this->Easemob->sendText($from = "admin", $target_type = 'users', $target = $v2['push_user'], $pushInfo['content'], $ext);

                if (isset($res['httpCode']) && $res['httpCode'] == '0') {
                    //TODO:重发，不更新状态, 获取发送不成功原因
                    //$this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 环信推送 消息id:' . $msg_id . ' error:' .  $res['msg']);//日志
                } else {
                    $sendOK = M('EasemobUsermsg')->where(['id' => ['IN', $v2['push_id']]])->save(['status' => 1]);

                    if ($sendOK) {
                        //更新脚本，正在执行下一批次推送
                        S('checkRunKey_1', ['isRun' => 1, 'runtime' => time()]);
                        $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=Daemon/Push/emPush/nosign/haha/times/" . $times . "/count/" . $count;//真正执行工作脚本
//                        echo "curl $url";
                        $curlRes = $this->doCurl($url);//处理下一批次

                        if ($curlRes) {
                            echo 'curl true';
                        } else {
                            $log = date('Y-m-d H:i:s', NOW_TIME) . ' 环信推送 消息id:' . $msg_id . ' error:curl return false';
                            //$this->log($log);//日志
                            echo "curl log : $log";
                            S('checkRunKey_1', ['isRun' => 0, 'runtime' => time()]);
                        }
                    } else {
                        S('checkRunKey_1', ['isRun' => 0, 'runtime' => time()]);
                    }

                    if (!isset($res['error']) && isset($res['data'])) {
                        //TODO:推送失败...
                    } else {
                        //$this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 环信推送 消息id:' . $msg_id . ' error:' .  $res['error']);//日志
                    }
                }
            }
        }
    }

    /**
     * 友盟推送
     */
    public function umPush()
    {
        $size = 20;                 //每次处理条数
        $count = intval($_GET['count']);     //要处理的推送条数
        $times = ($_GET['times'] ?: 0) + 1;     //要处理的批次
        $where = ['status' => ['EQ', '0'], 'user_type' => 2, 'platform' => 3, 'broad_cast' => 0];

        if (!$count)
            $count = $lists = M('EasemobUsermsg')->master(true)->where($where)->count();

        if ($count <= 0) {
            S('checkRunKey_2', ['isRun' => 0, 'runtime' => time()]);
            //$this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 友盟无推送');//日志
            exit;
        }

        $total_times = ceil($count / $size); //总批次

        if ($times > $total_times) {
            S('checkRunKey_2', ['isRun' => 0, 'runtime' => time()]);
            //$this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 友盟推送完成');//日志
            exit;
        }

        //读出本批次要发送所有记录,一批里面可能有不同的消息id
        $pushArrs = M('EasemobUsermsg')->master(true)->where($where)->limit($size)->select();

        //按msgid分组
        $msgList = [];
        foreach ($pushArrs as $k => $v) {
            $msgList[$v['msg_id']]['push_id'][] = $v['id'];
            $msgList[$v['msg_id']]['push_user'][] = $v['push_user'];
        }

        foreach ($msgList as $msg_id => $v2) {
            //获取推送详情
            $map = [];
            $map['id'] = $msg_id;
            $map['task_time'] = ['lt',NOW_TIME];
            $pushInfo = M('EasemobMsg')->master(true)->where($map)->find();

            if ($pushInfo) {
                $custom = [
                    'um_module' => [
                        'module' => $pushInfo['module'],
                        'value' => $pushInfo['url'],
                        'alias_type' => 'QQTY',
                        'alias' => $v2['push_user'],
                        'msg_id' => $msg_id
                    ],
                    'module' => $pushInfo['module'],
                    'url' => $pushInfo['url']
                ];

                $payloadBody = [
                    'ticker' => $pushInfo['content'],
                    'title' => $pushInfo['content'],
                    'text' => $pushInfo['content'],
                    'play_vibrate' => "true",
                    'play_lights' => "true",
                    'play_sound' => "true",
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
                $body['alias'] = implode(',', $v2['push_user']);
                $body['policy']['expire_time'] = date('Y-m-d H:i:s', time() + $exprie_time);

                //用户对应的clientId
                $eUsercIDs = M('EasemobUser')
                    ->field('client_id')
                    ->where(['username' => ['IN', $v2['push_user']]])
                    ->order('login_time DESC')
                    ->getField('client_id', true);

                $post_data = [
                    'platform' => $umconfig['platform'],
                    'payload' => json_encode($body),
                    'clientId' => implode(',', array_unique($eUsercIDs)),
                ];

                $res = httpPost(C('push_adress'), $post_data);
                //推送成功
                M('EasemobUsermsg')->where(['id' => ['IN', $v2['push_id']]])->save(['status' => 1]);
            }
        }

        //处理下一批次
        S('checkRunKey_2', ['isRun' => 1, 'runtime' => time()]);

        //真正执行工作脚本
        $url = "http://" . $_SERVER['HTTP_HOST'] . "/index.php?s=Daemon/Push/umPush/nosign/haha/times/" . $times . "/count/" . $count;
        //处理下一批次
        $curlRes = $this->doCurl($url);

        if ($curlRes === false) {
            S('checkRunKey_2', ['isRun' => 0, 'runtime' => time()]);
            //$this->log(date('Y-m-d H:i:s', NOW_TIME) . ' 友盟推送 消息id:' . $msg_id . ' error:curl return false');//日志
        }
    }

    /**
     * apns 推送
     */
    public function apns()
    {
        set_time_limit(120);
        S('checkRunKey_4', ['isRun' => 1, 'runtime' => time()]);

        if (!$total_count = (int)$_GET['count']) {
            $total_count = M('ApnsQueue')->where(['status' => 0])->count();//当前定时要发的消息总数
        }

        $send_count = 150;//每次脚本发送一百条
        $batches_num = ceil($total_count / $send_count);//一次定时分几批执行脚本
        $cur_batches = (int)$_GET['cur_batches'];

        $batches_num = $batches_num > 200 ? 200 : $batches_num;

        if ($cur_batches >= $batches_num) {
            S('checkRunKey_4', ['isRun' => 0, 'runtime' => time()]);
            return;
        }

        $msg = M('ApnsQueue')
            ->field('id,device_token,cert_no,msg_id')
            ->where(['status' => 0])
            ->limit($send_count)
            ->order('id DESC')
            ->select();

        $logStr = '[' . date('Y-m-d H:i:s', time()) . "] 第" . ($cur_batches + 1) . "批 开始推送";
        if ($msg) {
            //根据证书和消息ID分组
            $msgGroup = $msgIds = [];
            foreach ($msg as $k => $v) {
                $msgGroup[$v['cert_no'] . '|' . $v['msg_id']][] = $v['device_token'];
                $msgIdGroup['msg_id' . $v['cert_no'] . '|' . $v['msg_id']][] = $v['id'];
            }

            //拼接分组消息的payload
            foreach ($msgGroup as $k1 => $v2) {

                $cert_no = explode('|', $k1)[0];
                $msgIds = explode('|', $k1)[1];
                $device_tokens = implode(',', $v2);
                $map = [];
                $map['id'] = $msgIds;
                $map['task_time'] = ['lt',NOW_TIME];
                $msgInfo = M('EasemobMsg')->master(true)->where($map)->find();//消息内容
                if($msgInfo)
                {


                    $payload = ['aps' => [
                        'alert' => ["body" => $msgInfo['content']]],
                        'e' => [
                            'em_module' => ['module' => $msgInfo['module'], 'value' => $msgInfo['url'], 'url' => $msgInfo['url']],
                            'show_type' => 1
                        ]
                    ];

                    $post_data = [
                        'token' => $device_tokens,
                        'platform' => $cert_no,
                        'payload' => json_encode($payload)
                    ];

                    $res = httpPost(C('push_adress'), $post_data);

                    M('ApnsQueue')->where(['id' => ['IN', $msgIdGroup['msg_id' . $k1]]])->save(['status' => 1]);

                    $logStr .= "\r\n队列 {$k1}：" . count($v2) . "\r\n" . $msgInfo['content'] . " \r\n{$msg['cert_no']}， 状态：{$res['http_code']}\r\n";
                }
            }

            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 推送完成\r\n";
            $url = "http://" .
                $_SERVER['HTTP_HOST'] .
                "/index.php?s=Daemon/Push/apns/nosign/haha/cur_batches/" .
                ($cur_batches + 1) . "/count/" . $total_count;

            $logStr .= "\r\n下一批次请求：" . $url . "\r\n";
//            $this->log($logStr, 'Public/log/push_apns_consumer.txt');

            //执行下一批次
            $this->doCurl($url);
        } else {
            $logStr .= '[' . date('Y-m-d H:i:s', time()) . "] 无推送数据\r\n";
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);
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
            $fp = fopen("pushLog.txt", "a");
        else
            $fp = fopen($file, "a");
        flock($fp, LOCK_EX);
        fwrite($fp, $word . "\r\n");
        flock($fp, LOCK_UN);
        fclose($fp);
    }

}