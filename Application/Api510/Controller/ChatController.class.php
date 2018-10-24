<?php
use Think\Tool\Tool;

class ChatController extends PublicController
{
    /**
     * 获取聊天室信息
     */
    public function getInfo()
    {
        $game_type = $this->param['game_type'];
        $game_id = $this->param['game_id'];
        if ($game_type == '' || $game_id == '')
            $this->ajaxReturn(101);

        //公告
        $notice = Tool::getAdList(42, 5, $this->param['platform']) ?: [];

        //判断是否是管理员
        $userInfo = getUserToken($this->param['userToken']);

        $ad = M('ChatAdmin')->where(['username' => $userInfo['username']])->find();
        $ret = $ad ? '1' : '0';

        //获取聊天记录
        $room_id = 'qqty_chat_' . $game_type . '_' . $game_id;

        $redis = connRedis();
        $temp_log = $redis->lRange($room_id, 0, 100);
        $members = $redis->sMembers('qqty_chat_forbid_userids');
        $chat_log = [];

        //聊天记录处理
        foreach ($temp_log as $k => $v) {
            $log = json_decode($v, true);
            if (in_array($log['user_id'], $members)) {
                unset($temp_log[$k]);
            } else {
                $userids[] = $log['user_id'];
                $chat_log[] = json_decode($v, true);;
            }
        }

        //查询用户是否是专家
        if($userids){
            $expert = M('FrontUser')
                ->where(['id' => ['IN', $userids], 'is_expert' => '1'])
                ->getField('id', true);
        }

        foreach ($chat_log as $k2 => $v2) {
            if (in_array($v2['user_id'], $expert)) {
                $chat_log[$k2]['is_expert'] = '1';
            } else {
                $chat_log[$k2]['is_expert'] = '0';
            }
        }

        //开赛时间
        $statusDesc = '聊天室可用';
        $gModel = $game_type == 1 ? M("GameFbinfo") : M('GameBkinfo');
        $ginfo = $gModel
            ->field('gtime,game_state,update_time')
            ->where(['game_id' => $game_id])
            ->find();

        $status = '1';
        if ($ginfo['gtime'] - 3600 >= time()) {
            $status = '-1';
            $statusDesc = '聊天室将在比赛前1小时开启';
        } elseif ($ginfo['game_state'] == '-1' && ($ginfo['update_time'] + 3600 * 3 <= time() || $ginfo['gtime'] + 5 * 3600 < time())) {
            $status = '-2';
            $statusDesc = '聊天室已关闭';
        }

        //用户信息
        $uinfo = M('FrontUser')
            ->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,is_expert')
            ->where(['id' => $userInfo['userid']])
            ->find();
        if ($uinfo) {
            $uinfo['head'] = frontUserFace($uinfo['head']);
            $uinfo['is_expert'] = (string)$uinfo['is_expert'];
        }

        $errCode = '';
        $forbid = M('ChatForbid')
            ->where(['user_id' => $userInfo['userid'], 'status' => ['IN', [1, 3]]])
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

        if ($errCode) {
            $uinfo['is_forbid'] = '1';
            $uinfo['forbid_msg'] = C('errorCode')[$errCode];
        } else {
            $uinfo['is_forbid'] = '0';
            $uinfo['forbid_msg'] = '';
        }

        //后台聊天室开关
        $config = getWebConfig('common');
        if ((int)$config['chatroom'] == 0) {
            $status = '0';
            $statusDesc = '聊天室正在升级维护中';
        }

        $this->ajaxReturn([
            'status' => $status,
            'statusDesc' => $statusDesc,
            'notice' => $notice,
            'isAdmin' => $ret,
            'userInfo' => $uinfo ?: '',
            'chatLog' => array_reverse($chat_log),
            'gameInfo' => $ginfo ?: '',
        ]);
    }

    /**
     * 加入聊天室
     */
    public function joinRoom()
    {
        $game_type = $this->param['game_type'];
        $game_id = $this->param['game_id'];
        if ($game_type == '' || $game_id == '')
            $this->ajaxReturn(101);

        $userInfo = getUserToken($this->param['userToken']);

        //在线人数
        $this->setDefault($game_id, $game_type);
        $identity = isset($userInfo['userid']) ? 'normal' : 'robot';
        $chat = D('Robot')->onOffLine($userInfo['userid'], $game_type, $game_id, 1, $identity);
        $onlineNum = $chat['normalNum'] + $chat['robotNum'] + $chat['default_num'];

        //发送欢迎语
        if (isset($userInfo['userid'])) {
            $key = 'qqty_chat_send_hello:' . $userInfo['userid'] . '_' . $game_type . '_' . $game_id;
            //用户信息
            $uinfo = M('FrontUser')
                ->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,is_expert')
                ->where(['id' => $userInfo['userid']])
                ->find();

            if ($uinfo) {
                $uinfo['head'] = frontUserFace($uinfo['head']);
                $uinfo['is_expert'] = (string)$uinfo['is_expert'];
            }

            //屏蔽状态
            $errCode = '';
            $forbid = M('ChatForbid')->where(['user_id' => $userInfo['userid'], 'status' => ['IN', [1, 3]]])->order('id DESC')->find();
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

            //第一次进入聊天室、账号正常则发送欢迎语
            if (!S($key) && $errCode == '') {
                $msg_id = md5(time() . $userInfo['userid'] . $game_type . $this->param['game_id'] . rand(0, 9999));
                $data = array_merge($uinfo, ['content' => "Hi,很高兴和大家一起来聊球。", 'msg_id' => $msg_id, 'chat_time' => time()]);
                $payload = [
                    'action' => 'sayHello',
                    'data' => $data,
                    'dataType' => 'text',
                    'platform' => $this->param['platform'],
                    'status' => '1'
                ];

                $opt = [
                    'topic' => 'qqty/' . $game_type . '_' . $this->param['game_id'] . '/chat',
                    'payload' => $payload,
                    'clientid' => md5(time() . $userInfo['userid']),
                ];

                mqttPub($opt);

                S($key, time(), 3600 * 24);
            }
        }

        $this->ajaxReturn(['result' => '1', 'debug' => $opt, 'onlineNum' => $onlineNum]);
    }

    /**
     * 礼物列表
     */
    public function gift()
    {
        $dataService = new \Common\Services\DataService();
        $list = $dataService->getChatGift(1);

        foreach ($list as $k => $v) {
            $list[$k]['img']      = imagesReplace($v['img']);
            $list[$k]['zip_file'] = imagesReplace($v['zip_file']);
            $uptime[] = $v['update_time'];
            unset($list[$k]['update_time']);
        }

        $this->ajaxReturn(['lists' => $list ?: [], 'update_time' => max($uptime) ?: 0]);
    }


    /**
     * 表情包购买
     */
    public function buygift()
    {
        $game_type = $this->param['game_type'] ? $this->param['game_type'] : 1;
        $support = $this->param['support'] ? $this->param['support'] : 1;
        if ($this->param['giftId'] == '' || $this->param['game_id'] == '')
            $this->ajaxReturn(101);

        $userInfo = parent::getInfo();

        $gift = M('ChatGift')
            ->field('id,name,img,zip_file,price')
            ->where(['id' => $this->param['giftId']])
            ->find();

        if (!$gift)
            $this->ajaxReturn(101);

        if($gift['price'] !=0 ){
            //个人金币
            $frontUser = M('FrontUser')
                ->master(true)
                ->field('coin, username, unable_coin')
                ->where(['id' => $userInfo['userid']])
                ->find();

            //金币是否足够,先使用不可提金币
            $total_coin = $frontUser['coin'] + $frontUser['unable_coin'];

            if ($total_coin <= 0 || $total_coin < $gift['price'])
                $this->ajaxReturn(8009);

            if ($frontUser['unable_coin'] < $gift['price']) {
                $remain_coin = $frontUser['coin'] - ($gift['price'] - $frontUser['unable_coin']);
                $remain_unable_coin = 0;
            } else {
                $remain_coin = $frontUser['coin'];
                $remain_unable_coin = $frontUser['unable_coin'] - $gift['price'];
            }

            M()->startTrans();
            //金币更新
            $coin = $frontUser['coin'] - $gift['price'];
            $update1 = M('FrontUser')
                ->where(['id' => $userInfo['userid']])
                ->save(['coin' => $remain_coin, 'unable_coin' => $remain_unable_coin]);

            //账户明细
            $insertId = M('AccountLog')->add([
                'user_id' => $userInfo['userid'],
                'log_time' => NOW_TIME,
                'log_type' => '19',
                'log_status' => '1',
                'change_num' => $gift['price'],
                'total_coin' => $frontUser['unable_coin'] + $coin,
                'desc' => "赠送礼品消耗" . $gift['price'] . '金币',
                'platform' => $this->param['platform'],
                'operation_time' => NOW_TIME
            ]);

            if ($update1 === false || $insertId === false) {
                M()->rollback();
                $this->ajaxReturn(8010);
            } else {
                M()->commit();
            }
        }

        //发送
        $say['data'] = [
            'user_id' => $userInfo['userid'],
            'nick_name' => $userInfo['nick_name'],
            'head' => frontUserFace($userInfo['head']),
            'chat_time' => NOW_TIME,
            'support' => $support,
            'gift' => ''
        ];

        $fbInfo = M('GameFbinfo')
            ->field('home_team_name,away_team_name')
            ->where(['game_id' => $this->param['game_id']])
            ->find();

        if ($support == 1) {
            $str = $say['data']['nick_name'] . '为 ' . explode(',', $fbInfo['home_team_name'])[0];
        } else {
            $str = $say['data']['nick_name'] . '为 ' . explode(',', $fbInfo['away_team_name'])[0];
        }

        $str .= ' 送出 ' . $gift['name'];
        $say['data']['desc'] = $str;
        $gift['img'] = Think\Tool\Tool::imagesReplace($gift['img']);
        $gift['zip_file'] = Think\Tool\Tool::imagesReplace($gift['zip_file']);
        $say['data']['gift'] = $gift ?: '';

        //mqtt
        $say['action'] = 'say';
        $say['dataType'] = 'gift';
        $say['status'] = 1;

        if($gift['price'] == 0 ){
            $gift_key = 'qqty_chat_gift:' . $userInfo['userid'];
            if(S($gift_key))
                $this->ajaxReturn(3022);

            S($gift_key, time(), 10);

        }

        $opt = [
            'topic' => 'qqty/' . $game_type . '_' . $this->param['game_id'] . '/chat',
            'payload' => $say,
            'clientid' => md5(time() . $userInfo['userid']),
        ];

        mqttPub($opt);//mqtt推送

        $say['data']['coin'] = (string)$remain_coin;
        $say['data']['unable_coin'] = (string)$remain_unable_coin;

        $this->ajaxReturn($say['data']);
    }

    /**
     * 聊天室发言
     */
    public function say()
    {
        if ($this->param['content'] == '' || $this->param['game_id'] == '')
            $this->ajaxReturn(101);

        $game_type = $this->param['game_type'] ? $this->param['game_type'] : 1;
        $userInfo = parent::getInfo();

        $say['data'] = [
            'userId' => $userInfo['userid'],
            'nickName' => $userInfo['nick_name'],
            'head' => frontUserFace($userInfo['head']),
            'content' => $this->param['content'],
            'contentType' => 1,
            'time' => NOW_TIME,
            'gift' => '',
            'desc' => ''
        ];

        //判断是否被屏蔽、踢出
        $forbid = M('ChatForbid')->where(['user_id' => $userInfo['userid'], 'status' => ['IN', [1, 3]]])->order('id DESC')->find();
        if ($forbid) {
            $errCode = '';
            if ($forbid['type'] == 1) {
                $errCode = 3018;
            } else {
                if (NOW_TIME < $forbid['operate_time'] + 600) {
                    $errCode = 3019;
                }
            }
            if ($errCode) {
                $this->ajaxReturn($errCode);
            }
        }

        //如果没有被屏蔽，则从屏蔽旧的记录集合里删除用户
        $redis = connRedis();
        $redis->sRem('qqty_chat_forbid_userids', $userInfo['userid']);

        //显示等级
        $uinfo = M('FrontUser')->master(true)->field('lv,lv_bet,lv_bk,is_expert')->where(['id' => $userInfo['userid']])->find();
        $lv = $game_type == 1 ? max($uinfo['lv'], $uinfo['lv_bet']) : $uinfo['bk'];
        $say['data']['lv'] = $lv >= 4 ? $lv : '';
        $say['data']['is_expert'] = (string)$uinfo['is_expert'];

        //敏感词检测
        if (!matchFilterWords('FilterWords', $this->param['content']))
            $this->ajaxReturn(1061);

        //发言
        $redis = connRedis();
        $msgid = $redis->incr('chat_esr_msg_id');
        $say['type'] = 2002;
        $say['status'] = 1;
        $say['data']['msg_id'] = $msgid;
        $channel = 'esr_chat_' . $game_type . ':' . $this->param['game_id'];

        $redis->lPush($channel, json_encode($say['data']));
        $redis->expire($channel, 86400);

        //mqtt
        $opt = [
            'topic' => 'qqty/' . $game_type . '_' . $this->param['game_id'] . '/chat/say',
            'payload' => $say,
            'clientid' => md5($channel . $userInfo['userid']),
        ];
        mqttPub($opt);

        $this->ajaxReturn($say['data']);
    }

    /**
     * 屏蔽用户聊天
     * type 1:屏蔽，2：举报，3：踢出
     * report_type 1：打广告，2：不文明发言，3：恶意刷屏，4：其他
     */
    public function forbid()
    {
        $report_type = $this->param['report_type'];
        $type = $this->param['type'];
        $game_id = $this->param['game_id'];
        $game_type = $this->param['game_type'];
        $content = $this->param['content'];
        $forbid_id = $this->param['user_id'];
        $msg_id = $this->param['msg_id'];
        $chat_time = $this->param['chat_time'];

        $userInfo = parent::getInfo();

        if (!$type || !$game_id || !$game_type || !$content || !$forbid_id || !$msg_id)
            $this->ajaxReturn(101);

        $room_id = $game_type . '_' . $game_id;

        $forbid = [
            'user_id' => $forbid_id,
            'type' => $type,
            'report_type' => $report_type,
            'content' => json_encode($content),
            'room_id' => $room_id,
            'msg_id' => $msg_id,
            'chat_time' => $chat_time,
            'create_time' => NOW_TIME
        ];

        if ($type == 1 || $type == 3) {//屏蔽、踢出
            //记录被禁用户
            $redis = connRedis();
            $redis->sAdd('qqty_chat_forbid_userids', $forbid['user_id']);

            $ad = M('ChatAdmin')->where(['username' => $userInfo['username']])->find();
            if (!$ad)
                $this->ajaxReturn(3016);

            $forbid['status'] = $type;
            $forbid['operate_time'] = NOW_TIME;
            $forbid['operator'] =  $userInfo['userid'];
            $forbid['operate_type'] = 3;

            $add = M('ChatForbid')->add($forbid);
            if (!$add)
                $this->ajaxReturn(3017);

            if ($type == 1) {
                $action = 'forbid';
                $notice_str = '您的聊天内容已经严重违反了全球体育平台规则，您将被永久屏蔽帐号';
            } else {
                $action = 'kickout';
                $notice_str = '您的聊天内容影响到其他用户，你将被禁言十分钟';
            }

            //通知客户端
            $redis->sAdd('qqty_chat_forbid_userids', $forbid_id);
            $pubData = [
                'data' => ['user_id' => $forbid_id, 'notice_str' => $notice_str, 'msg_id' => $msg_id],
                'action' => $action,
                'dataType' => 'text',
                'status' => 1
            ];

            //mqtt
            $opt = [
                'topic' => 'qqty/' . $room_id . '/chat',
                'payload' => $pubData,
                'clientid' => md5(time() . $userInfo['userid']),
            ];

            mqttPub($opt);

            //过滤被屏蔽的用户消息
            $chat_log = $redis->lRange('qqty_chat_' . $room_id, 0, -1);
            $members = $redis->sMembers('qqty_chat_forbid_userids');
            foreach ($chat_log as $k => $v) {
                $log = json_decode($v, true);
                if (in_array($log['user_id'], $members)) {
                    $redis->lRem('qqty_chat_' . $room_id, $v, 1);
                }
            }
        } elseif ($this->param['type'] == 2) {//举报
            $forbid['from_id'] = $userInfo['userid'];
            $add = M('ChatForbid')->add($forbid);
            if (!$add)
                $this->ajaxReturn(3017);
        }
        $this->ajaxReturn(['result' => '1']);

    }

    /**
     * 机器人列表
     */
    public function getRobot()
    {
        $page = (int)$this->param['page'] ?: 1;
        $data = $this->getRobotList($page);
        $this->ajaxReturn($data);
    }

    /**
     * 机器人上下线
     */
    public function  robot()
    {
        $type = $this->param['type'] ?: 0;
        $data = [];
        if($type == 1 || $type == -1 || $type == 0 ){
            $gameId   = (int)$this->param['gameId'];
            $gameType = (int)$this->param['gameType'];
            $userArr = $this->param['robotIds'] ? explode(',', $this->param['robotIds']) : '';
            $comstorm_key  ='qqty_chat_comstorm_online:' .  $gameType . '_' . $gameId;
            if($this->param['num'] > 0){
                $redis  = connRedis();
                $comstorm_num = (int) $redis->get($comstorm_key);
                if($type ==  1 ){
                    $set_num = $this->param['num'] + $comstorm_num;
                }else{
                    $set_num = $comstorm_num - $this->param['num'] <=0 ? 0 : $comstorm_num - $this->param['num'];
                }

                $redis->set($comstorm_key, $set_num);
                $redis->expire($comstorm_key, 3600 * 24 * 3);
            }

            $data = D('Robot')->onOffLine($userArr, $gameType, $gameId, $type, 'robot');
        }

        $this->ajaxReturn($data);
    }



    /**
     * 获取机器人列表
     * @param int $page
     * @return mixed
     */
    public function getRobotList($page = 1)
    {
        $pageNum = 500;
        $total = M('FrontUser')->where(['is_robot' => 1])->count();
        $totalPage = ceil($total / $pageNum);
        $mod = $total % $pageNum;

        $pre = DOMAIN == 'qw.com' ? 'http://' : 'https://';
        $url = $pre . $_SERVER['HTTP_HOST'] . '/' . MODULE_NAME . '/chat/getRobot?page=';

        $prev = $page - 1;
        $next = $page + 1;

        if ($page > $totalPage) {
            $page = $totalPage;
        }

        if ($totalPage == $page) {
            $next = 0;
            if ($mod < ($pageNum / 2)) {
                $page = 1;
                $prev = 1;
            }
        }

        $list = M('FrontUser')
            ->field(' id, username, nick_name as nickname, head as avatar, lv, lv_bet, lv_bk ')
            ->where(['is_robot' => 1])
            ->page($page . ',' . $pageNum)
            ->order('id asc')
            ->select();

        if ($list) {
            foreach ($list as $k => &$v) {
                $v['avatar'] = frontUserFace($v['avatar']);
            }
        }

        $data['count'] = count($list);
        $data['next'] = $url . $next;
        $data['previous'] = $url . $prev;
        $data['results'] = $list;

        //设置聊天室默认值
        $gameId = $this->param['gameId'];
        $gameType = $this->param['gameType'];
        $this->setDefault($gameId, $gameType);

        return $data;
    }

    /**
     * 设置聊天室默认人数
     * @param $gameId
     * @param $gameType
     */
    public function setDefault($gameId, $gameType){
        //设置默认人数
        $redis  = connRedis();
        $dk = 'api_chatDefaultNum_' . $gameType . '_' . $gameId;

        if($gameId && $redis->get($dk) === false){
            $robotConfig = getWebConfig(['fbGameRobot', 'bkGameRobot']);
            if($gameType == 2){
                $config = $robotConfig['bkGameRobot'];
                $model = M('GameBkinfo g');
            }else{
                $config = $robotConfig['fbGameRobot'];
                $model = M('GameFbinfo g');
            }

            $info = $model
                ->field('g.gtime, g.game_state, u.is_sub, g.is_video, g.app_video, l.is_link, l.md_id')
                ->join('left join qc_union u on g.union_id = u.union_id')
                ->join('left join qc_fb_linkbet l on g.game_id = l.game_id')
                ->where(['g.game_id' => $gameId])
                ->find();

            if (in_array($info['is_sub'], [0, 1])) {
                $defaultNum = $config['defaultNum'][1];
            } else if ($info['is_sub'] == 2) {
                $defaultNum = $config['defaultNum'][2];
            } else {//普通
                $defaultNum = $config['defaultNum'][3];
            }

            $defRand = rand($defaultNum['start'], $defaultNum['end']);
            $redis->set($dk, $defRand);
            $redis->expire($dk, 3600 * 24 * 2);
        }
    }

    /**
     * 获取机器人聊天室后台配置
     */
    public function robotConfig(){
        $gameId   = (int)$this->param['gameId'];
        $gameType = (int)$this->param['gameType'];
        $robotConfig = getWebConfig(['fbGameRobot', 'bkGameRobot']);
        if($gameType == 2){
            $config = $robotConfig['bkGameRobot'];
            $model = M('GameBkinfo g');
        }else{
            $config = $robotConfig['fbGameRobot'];
            $model = M('GameFbinfo g');
        }
        $info = $model
            ->field('g.gtime, g.game_state, u.is_sub, g.is_video, g.app_video, l.is_link, l.md_id')
            ->join('left join qc_union u on g.union_id = u.union_id')
            ->join('left join qc_fb_linkbet l on g.game_id = l.game_id')
            ->where(['g.game_id' => $gameId])
            ->find();

        //分赛事级别0,1是1级
        $retConf = in_array($info['is_sub'], [0, 1, 2]) ? $config['rank1'] : $config['rank0'];

        //当前聊天室真实人数
        $redis  = connRedis();
        $key_suffix = $gameId . '_' . $gameType;
        $normal_key ='qqty_chat_normal_online:' . $key_suffix;
        $normalNum  = (int) $redis->sCard($normal_key);
        $retConf['normal_num'] = $normalNum;
        $retConf['middle'] = array_reverse($retConf['middle']);
        $retConf['unionLevel'] = (int)$info['is_sub'];
        $this->ajaxReturn($retConf);
    }
}


