<?php
use Think\Controller;

class MqttController extends Controller
{
    /**
     * 聊天室发言过滤接口
     */
    public function sayFilter()
    {
        header("Access-Control-Allow-Origin: *");
        try {
            $postdata = file_get_contents('php://input');

            $unpack = unpack('a*', $postdata);
            $input = json_decode($unpack[1] ?: $postdata, true);
            if (!is_array($input))
                throw new Exception($unpack[1] ?: $postdata);

            if ($input['action'] == 'say' && $input['dataType'] == 'text' && $input['data'] && !$input['isR']) {
                $user_id = $input['data']['user_id'];
                $room_type = $input['data']['room_type'] == 2 ? 2 : 1;

                //用户是否被屏蔽、禁言
                $err_code = $this->checkUserStatus($input);
                if ($err_code)
                    throw new \Exception(json_encode($input), $err_code);

                //发言频率限制 N秒内只能发M条消息
                $chat_num = 0;
                $chatlog = M('Chatlog')->master(true)->where(['user_id' => $user_id])->order('chat_time desc')->limit(10)->select();

                if ($room_type == 1) {
                    $limitDec = 5;
                    $limitNum = 1;
                } else {
                    $limitDec = 5;
                    $limitNum = 1;
                }

                foreach ($chatlog as $ck => $cv) {
                    if ((time() - $limitDec) < $cv['chat_time']) {
                        $chat_num++;
                    }
                }

                if ($limitNum && $chat_num >= $limitNum) {
                    $input['action'] = 'timeLimit';
                    $input['data']['notice_str'] = '您发消息太频繁了，休息一下吧~~~';
                    throw new \Exception(json_encode($input), 1003);
                }

                $redis = connRedis();

                //是否触发广告刷屏
                $err_code = $this->checkAdBrush($input, $room_type);
                if ($err_code) {
                    $redis->sAdd('qqty_chat_forbid_userids', $user_id);
                    throw new \Exception(json_encode($input), $err_code);
                }

                //敏感词检测
                $err_code = $this->checkWord($input, $room_type);
                if ($err_code) {
                    $redis->sAdd('qqty_chat_forbid_userids', $user_id);
                    throw new \Exception(json_encode($input), $err_code);
                }

            } elseif ($input['action'] == 'say' && $input['dataType'] == 'gift') {
                $payload = md5(json_encode($input));
                if (S($payload)) {
                    $input['action'] = 'filter';
                    $input['data']['notice_str'] = 'filter';
                    throw new Exception(json_encode($input), 0);
                }

                S($payload, time(), 3);
            }

        } catch (Exception $e) {
            echo pack('a*', $e->getMessage());
            exit;
        }

        echo pack('a*', json_encode($input));
    }

    /**
     * 检测用户状态
     * @param $input
     * @return int|string
     */
    public function checkUserStatus(&$input)
    {
        $forbid = M('ChatForbid')->master(true)
            ->where(['user_id' => $input['data']['user_id'], 'status' => ['IN', [1, 3]]])
            ->order('id DESC')
            ->find();

        //被屏蔽
        if ($forbid && $forbid['type'] == 1) {
            $input['action'] = 'forbid';
            $input['data']['notice_str'] = C('errorCode')[3018];
            return 3018;
        }

        //被踢出
        if ($forbid && $forbid['type'] == 3 && NOW_TIME < $forbid['operate_time'] + 600) {
            $input['action'] = 'kickout';
            $input['data']['notice_str'] = C('errorCode')[3019];
            return 3019;
        }

        //被举报、屏蔽
        if ($forbid && $forbid['type'] == 2 && $forbid['status'] == 1) {
            $input['action'] = 'forbid';
            $input['data']['notice_str'] = C('errorCode')[3018];
            return 3018;
        }

        //被举报、踢出
        if ($forbid && $forbid['type'] == 2 && NOW_TIME < $forbid['operate_time'] + 600) {
            $input['action'] = 'forbid';
            $input['data']['notice_str'] = C('errorCode')[3019];
            return 3019;
        }

        //用户状态与ip是否可用
        $user = M('FrontUser')->where(['id' => $input['data']['user_id']])->find();
        //添加vip标记
        $input['data']['is_vip'] = checkVip($user['vip_time']);
        if ($user && $user['status'] == 0) {
            $input['action'] = 'forbid';
            $input['data']['notice_str'] = C('errorCode')[3018];
            return 3018;
        }

        if (checkShieldIp()) {
            $input['action'] = 'forbid';
            $input['data']['notice_str'] = C('errorCode')[401];
            return 401;
        }

        //返回等级
        $input['data']['lv'] = $user['lv'];
        $input['data']['lv_bet'] = $user['lv_bet'];
        $input['data']['lv_bk'] = $user['lv_bk'];

        return '';
    }


    /**
     * 广告刷频 自动屏蔽
     * @param $input
     * @param int $room_type
     * @return int|string
     */
    public function checkAdBrush(&$input, $room_type = 1)
    {
        $user_id = $input['data']['user_id'];
        if (mb_strlen($input['data']['content']) > 10) {
            $inFilterStr = '';
            $mbstring = 0;
            preg_replace_callback('/[\x{4e00}-\x{9fa5}]|[A-Za-z0-9]/u', function ($match) use (&$inFilterStr, &$mbstring) {
                $inFilterStr .= $match[0];
                $mbstring++;
            }, strtolower($input['data']['content']));

              //查询是否存在历史广告词
            if($inFilterStr != ''){
                $r = M('ChatBanword')->where(['status' => 1, 'content' => ['LIKE', "%" . strtolower($inFilterStr) . "%"]])->find();
                if ($r) {
                    $d = [
                        'user_id' => $user_id,
                        'type' => 1,
                        'content' => $input['data']['content'],
                        'status' => 1,
                        'room_type' => $room_type,
                        'room_id' => $input['data']['room_id']?:'',
                        'create_time' => NOW_TIME,
                        'operate_time' => NOW_TIME,
                        'operator' => 1,//（系统）广告过滤程序
                        'operate_type' => 1,//系统

                    ];
                    M('ChatForbid')->add($d);

                    $input['action'] = 'forbid';
                    $input['data']['notice_str'] = C('errorCode')[3018];
                    return 3018;
                }
            }

            //1分钟发送同样的3条消息，则自动屏蔽
            $chat_list = M('Chatlog')
                ->master(true)
                ->field('content')
                ->where(['user_id' => $user_id, 'room_type' => $room_type, 'chat_time' => ['GT', NOW_TIME - 120]])
                ->order('chat_time desc')
                ->select();

            $s_num = 0;
            if (count($chat_list) >= 2) {
                foreach ($chat_list as $chK => $chV) {
                    $hisAdFilterStr = '';
                    preg_replace_callback('/[\x{4e00}-\x{9fa5}]|[A-Za-z0-9]/u', function ($match) use (&$hisAdFilterStr) {
                        $hisAdFilterStr .= $match[0];
                    }, htmlspecialchars_decode(strtolower($chV['content'])));

                    if (strstr($hisAdFilterStr, $inFilterStr)) {
                        $s_num += 1;
                        $saveFilter = $inFilterStr;
                    } elseif (strstr($inFilterStr, $hisAdFilterStr)) {
                        $s_num += 1;
                        $saveFilter = $hisAdFilterStr;
                    }
                }
            }

            if ($s_num >= 2) {
                $forbidAdd1 = [
                    'user_id' => $user_id,
                    'type' => 1,
                    'room_type' => $room_type,
                    'room_id' => $input['data']['room_id']?:'',
                    'content' => $input['data']['content'],
                    'status' => 1,
                    'create_time' => NOW_TIME,
                    'operate_time' => NOW_TIME,
                    'operator' => 2,//刷屏禁言程序
                    'operate_type' => 1,
                ];
                M('ChatForbid')->add($forbidAdd1);

                $forbidAdd2 = [
                    'user_id' => $user_id,
                    'content' => $saveFilter,
                    'room_type' => $room_type,
                    'room_id' => $input['data']['room_id']?:'',
                    'nick_name' => $input['data']['nick_name'],
                    'add_time' => NOW_TIME,
                    'status' => 1,
                ];
                M('ChatBanword')->add($forbidAdd2);

                $input['action'] = 'forbid';
                $input['data']['notice_str'] = C('errorCode')[3018];
                return 3018;
            }
        }
        return '';
    }


    /**
     * 敏感词检测 自动屏蔽
     * @param $input
     * @param $room_type
     * @return int|string
     */
    public function checkWord(&$input, $room_type = 1)
    {
        $keyArrs = getWebConfig('FilterWords');
        if (preg_match('/(' . implode('|', array_filter($keyArrs)) . ')/', htmlspecialchars($input['data']['content']))) {
            $input['data']['scontent'] = htmlspecialchars($input['data']['content']);
            $input['data']['content'] = '***';

            //屏蔽用户
            if ($input['data']['user_id']) {
                $forbidAdd = [
                    'user_id' => $input['data']['user_id'],
                    'type' => 1,
                    'content' => $input['data']['scontent'],
                    'room_id' => $input['data']['room_id']?:'',
                    'room_type' => $room_type,
                    'chat_time' => $input['data']['chat_time'],
                    'status' => 1,
                    'create_time' => NOW_TIME,
                    'operate_time' => NOW_TIME,
                    'operator' => 3,//敏感词
                    'operate_type' => 1,

                ];

                M('ChatForbid')->add($forbidAdd);
            }

            $input['action'] = 'forbid';
            $input['data']['notice_str'] = C('errorCode')[3018];
            return 3018;

        } else {
            $input['data']['content'] = htmlspecialchars($input['data']['content']);
        }

        return '';
    }

    /**
     * @param $text
     * @param $pattern
     * @return mixed
     */
    public function filter($text, $pattern)
    {
        $res = preg_replace_callback($pattern, function ($m) {
            return isset($m[1]) ? '***' : $m[0];
        }, $text);

        return $res ? $res : $text;
    }

    public function test()
    {
        $text = htmlspecialchars($_REQUEST['text']);
        $keyArrs = getWebConfig('FilterWords');
        $temp_content = $this->filter($text, '/(' . implode('|', $keyArrs) . ')/');
        var_dump($temp_content);
    }
}


