<?php
use Think\Tool\Tool;

class ChatController extends PublicController
{
    /**
     * 获取聊天室
     * 1、赛事直播
     * 2、有主播 不关联赛事
     * 3、主播关联赛事
     */
    public function getInfo()
    {
        $room_id = $this->param['room_id'];

        $game_type = $this->param['game_type'];
        $game_id = $this->param['game_id'];

        if ($room_id == '' && ($game_type == '' || $game_id == '')){
            $this->ajaxReturn(101);
        }

        $status = '1';
        $statusDesc = '当前聊天室正常使用';
        $ginfo = [];

        //有赛事 则获取赛事详情
        if($game_id && $game_type){
            $DataService = new \Common\Services\DataService();
            $ginfo   = $DataService->getMongoGameData([$game_id], 1);
        }

        if($room_id == ''){//1、赛事直播
            $room_log_id = $game_type . '_' . $game_id;
            $topic = 'qqty/' . $game_type . '_' . $game_id . '/chat';

            if($ginfo){
                if ($ginfo['game_start_timestamp'] - 3600 >= time()) {
                    $status = '-1';
                    $statusDesc = '聊天室将在比赛前1小时开启';
                } elseif ($ginfo['game_state'] == '-1' && ($ginfo['update_time'] + 3600 * 3 <= time() || $ginfo['game_start_timestamp'] + 5 * 3600 < time())) {
                    $status = '-2';
                    $statusDesc = '聊天室已关闭';
                }
                $retData['gameInfo'] = $ginfo ?: '';
            }

            $notice = Tool::getAdList(42, 5, $this->param['platform']) ?: [];
            $retData['notice'] = $notice;

        }elseif($room_id){//主播聊天室
            //获取主播房间消息
            $live = M('liveLog')
                ->alias('Lg')
                ->field('Lg.id, Lg.user_id, Lg.room_id, Lg.title, Lg.start_time, Lg.live_status, U.nick_name, Lu.unique_id, U.head')
                ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
                ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
                ->where(['Lg.room_id' => $room_id])
                ->find();


            if(!$live)
                $this->ajaxReturn(3023);

            $room_log_id = 'live_' . $live['room_id'];
            $notice = Tool::getAdList(118, 5, $this->param['platform']) ?: [];
            $retData['notice'] = $notice;

            //正在直播中，有没有红包活动
            $redPacket = M('Redpkg')
                ->field('id, title, count, value, start_time')
                ->where(['livelog_id' => $live['id'], 'start_time' => ['gt', time()], 'status' => 1])
                ->order('start_time DESC')
                ->find();

            $topic = 'qqty/live_' . $live['room_id'] . '/chat';
            $live['head'] =  frontUserFace($live['head']);
            $live['share_qrcode'] = 'https://static.qqty.com/Public/Home/images/common/ewm.png';
            $live['share_url'] = U('/LiveRoom/index@m',['roomId' => $live['room_id']]);
            $retData['liveInfo']  = $live;
            $retData['welcome']   = "欢迎来到{$live['nick_name']}的直播间。全球体育提倡积极健康的直播环境，直播内容24小时巡查，发现任何违法、违规、低俗等不良信息，将做封号处理";

            //如果关联赛事
            if($ginfo){
                $retData['gameInfo'] = $ginfo ?: '';
            }

            if($redPacket){
                $redPacket['countdown'] = $redPacket['start_time'] - time();
                $retData['redPacket'] = $redPacket;
            }

            $retData['mqtt_room_redPacket_topic'] = 'qqty/' . $live['user_id'] . '/redPacket';
        }

        //后台聊天室开关
        $config = getWebConfig('common');
        if ((int)$config['chatroom'] == 0) {
            $status = '0';
            $statusDesc = '聊天室正在升级维护中';
        }

        //判断是否是管理员
        $userInfo = getUserToken($this->param['userToken']);
        $chatAdmin = M('ChatAdmin')->where(['username' => $userInfo['username']])->find();

        //获取聊天记录
        $dataService = new \Common\Services\DataService();

        $chat_log = $dataService->chatRecord($room_log_id);
        $userids = array_column($chat_log, 'user_id');

        //查询用户是否是专家
        if($userids){
            $user = M('FrontUser')->where(['id' => ['IN', $userids]])->getField('id,is_expert,vip_time', true);
        }

        foreach ($chat_log as $k => $v) {
            if(isset($user[$v['user_id']])){
                $chat_log[$k]['is_expert'] = $user[$v['user_id']]['is_expert'];
                $chat_log[$k]['is_vip']    = checkVip($user[$v['user_id']]['vip_time']);
            }else{
                $chat_log[$k]['is_expert'] = '0';
                $chat_log[$k]['is_vip']    = '0';
            }
        }

        //用户信息
        if($userInfo['userid']){
            $uinfo = M('FrontUser')
                ->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,is_expert')
                ->where(['id' => $userInfo['userid']])
                ->find();

            if ($uinfo) {
                $uinfo['head'] = frontUserFace($uinfo['head']);
                $uinfo['is_expert'] = (string)$uinfo['is_expert'];
            }

            //获取屏蔽状态
            $dataService = new \Common\Services\DataService();
            $errCode = $dataService->chatForbidStatus($userInfo['userid']);

            if ($errCode) {
                $uinfo['is_forbid'] = '1';
                $uinfo['forbid_msg'] = C('errorCode')[$errCode];
            } else {
                $uinfo['is_forbid'] = '0';
                $uinfo['forbid_msg'] = '';
            }
        }

        $uinfo['isAdmin'] = $chatAdmin ? '1' : '0';;

        $retData['status'] = $status;
        $retData['statusDesc'] = $statusDesc;
        $retData['mqtt_room_topic'] = $topic;
        $retData['userInfo'] = $uinfo ?: '';
        $retData['chatLog'] = $chat_log?:[];

        $this->ajaxReturn($retData);
    }

    /**
     * 加入聊天室
     */
    public function joinRoom()
    {
        $room_id = $this->param['room_id'];
        $game_type = $this->param['game_type'] ? $this->param['game_type'] : 1;
        $game_id = $this->param['game_id'];

        if ($room_id == '' && ($game_type == '' || $game_id == '')){
            $this->ajaxReturn(101);
        }

        $userInfo = getUserToken($this->param['userToken']);

        //用户信息
        $errCode = '';
        if(isset($userInfo['userid'])){
            $FrontUser = M('FrontUser')
                ->field('id as user_id,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,is_expert')
                ->where(['id' => $userInfo['userid']])
                ->find();

            $FrontUser['head'] = frontUserFace($FrontUser['head']);
            $FrontUser['is_expert'] = (string)$FrontUser['is_expert'];

            //获取屏蔽状态
            $dataService = new \Common\Services\DataService();
            $errCode = $dataService->chatForbidStatus($userInfo['userid']);
        }

        //发送欢迎语
        if ($room_id == '') {//赛事
            $key = 'qqty_chat_send_hello:' . $userInfo['userid'] . '_' . $game_type . '_' . $game_id;
            $topic = 'qqty/' . $game_type . '_' . $game_id . '/chat';
            $content = "进入房间";
            $msg_id = md5(time() . $userInfo['userid'] . rand(0, 9999));
        }elseif($room_id != ''){//直播
            $live = M('liveLog')
                ->alias('Lg')
                ->field('Lg.id, Lg.user_id, Lg.room_id, Lg.title, Lg.start_time, Lg.live_status, U.nick_name')
                ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
                ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
                ->where(['Lg.room_id' => $room_id])
                ->find();

            if(!$live)
                $this->ajaxReturn(3023);

            $key= 'qqty_chat_send_hello:' . $userInfo['userid'] . '_' . $room_id;
            $msg_id = md5(time() . $userInfo['userid'] . $live['room_id'] . rand(0, 9999));
            $content = "进入直播间";
            $topic = 'qqty/live_' . $live['room_id'] . '/chat';

            //进入用户+1
            M('liveUser')->where(['user_id' => $live['user_id']])->setInc('user_num', 1);
            M('liveLog')->where(['id' => $live['id']])->setInc('user_num', 1);
        }

        if($FrontUser){
            $data = array_merge($FrontUser, ['content' => $content, 'msg_id' => $msg_id, 'chat_time' => time()]);
            $payload = [
                'action' => 'sayHello',
                'data' => $data,
                'dataType' => 'text',
                'platform' => $this->param['platform'],
                'status' => '1'
            ];

            $opt = [
                'topic' => $topic,
                'payload' => $payload,
                'clientid' => md5(time() . $userInfo['userid']),
            ];

            if($errCode == ''){
                $st = $room_id == '' ? 3600 * 24 : 3600;
                if (!S($key)) {//第一次进入聊天室、账号正常则发送欢迎语
                    S($key, time(), $st);
                    mqttPub($opt);
                }
            }
        }

        //在线人数
        $onlineNum = $this->setDefault($game_id, $game_type, $room_id);
        if($room_id == ''){
            $identity = isset($userInfo['userid']) ? 'normal' : 'robot';
            $chat = D('Robot')->onOffLine($userInfo['userid'], $game_type, $game_id, 1, $identity);
            $onlineNum = $chat['normalNum'] + $chat['robotNum'] + $chat['default_num'];
        }

        $this->ajaxReturn(['result' => '1', 'debug' => $opt ?:'', 'onlineNum' => (int)$onlineNum]);
    }


    /**
     * 礼物列表
     */
    public function gift()
    {
        $type = $this->param['type'] == 2 ? 2: 1;
        $dataService = new \Common\Services\DataService();
        $list = $dataService->getChatGift($type);

        foreach ($list as $k => $v) {
            $list[$k]['img']      = imagesReplace($v['img']);
            $list[$k]['zip_file'] = imagesReplace($v['zip_file']);
            $uptime[] = $v['update_time'];
            unset($list[$k]['update_time']);
        }

        $this->ajaxReturn(['lists' => $list ?: [], 'update_time' => max($uptime) ?: 0]);
    }

    /**
     * 表情包/礼物购买
     */
    public function sendGift()
    {
        $room_id = $this->param['room_id'];
        $game_type = $this->param['game_type'] ? $this->param['game_type'] : 1;
        $support = $this->param['support'] ? $this->param['support'] : 1;
        $sayData = [];

        if ($this->param['giftId'] == '')
            $this->ajaxReturn(101);

        //获取用户信息
        $getInfo = parent::getInfo();

        $userInfo = M('FrontUser')->where(['id' => $getInfo['userid']])->find();

        //主播信息
        if($room_id){
            $live = M('liveLog')
                ->alias('Lg')
                ->field('Lg.id, Lg.user_id, Lg.room_id, Lg.title, Lg.start_time, Lg.live_status, U.nick_name')
                ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
                ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
                ->where(['Lg.room_id' => $room_id])
                ->find();

            if(!$live)
                $this->ajaxReturn(3023);
        }

        //获取礼物
        $gift = M('ChatGift')
            ->field('id,name,img,zip_file,price,vip_price')
            ->where(['id' => $this->param['giftId']])
            ->find();

        if (!$gift)
            $this->ajaxReturn(101);

        //防止礼物刷屏
        if($gift['price'] == 0 ){
            $gift_key = 'qqty_chat_gift:' . $userInfo['id'];
            if(S($gift_key))
                $this->ajaxReturn(3022);

            S($gift_key, time(), 10);
        }

        $gift['img']         = Think\Tool\Tool::imagesReplace($gift['img']);
        $gift['zip_file']    = Think\Tool\Tool::imagesReplace($gift['zip_file']);

        //金币购买
        $price = $gift['price'];
        if($userInfo['is_vip'] == 1 && $this->param['platform'] == 2 && !empty($gift['vip_price'])){//是否vip, 并且ios平台,使用vip价格
            $price = $gift['vip_price'];
        }

        if($price > 0){
            //金币是否足够,先使用不可提金币
            $total_coin = $userInfo['coin'] + $userInfo['unable_coin'];

            //金币是否足够
            if ($total_coin <= 0 || $total_coin < $price)
                $this->ajaxReturn(8009);

            if ($userInfo['unable_coin'] >= $price) {
                //不可提金币足够优先扣除
                $saveArray['unable_coin'] = ['exp','unable_coin-'.$price];
                $remain_coin = $userInfo['coin'];
                $remain_unable_coin = $userInfo['unable_coin'] - $price;
            } else {
                //扣完不可提后从可提里扣
                $saveArray['unable_coin'] = 0;
                $saveArray['coin'] = ['exp','coin-'.($price - $userInfo['unable_coin'])];
                $remain_coin = $userInfo['coin'] - ($price - $userInfo['unable_coin']);
                $remain_unable_coin = 0;
            }

            M()->startTrans();
            //金币更新
            $update1 = M('FrontUser')->where(['id' => $userInfo['id']])->save($saveArray);

            //购买用户账户明细
            $insertId1 = M('AccountLog')->add([
                'user_id'    => $userInfo['id'],
                'log_time'   => NOW_TIME,
                'log_type'   => '19',
                'log_status' => '1',
                'change_num' => $price,
                'total_coin' => $total_coin - $price,
                'desc'       => "赠送礼品消耗" . $price . '金币',
                'platform'   => $this->param['platform'],
                'operation_time' => NOW_TIME
            ]);

            //主播收入明细
            if($room_id){
                $user = M('FrontUser')->where(['id' => $live['user_id']])->find();
                $update2 = M('FrontUser')->where(['id' => $live['user_id']])->setInc('coin', $price);
                $update3 = M('LiveUser')->where(['user_id' => $live['user_id']])->setInc('coin', $price);
                $update4 = M('LiveLog')->where(['room_id' => $room_id])->setInc('coin', $price);

                $insertId2 = M('AccountLog')->add([
                    'user_id'    => $live['user_id'],
                    'log_time'   => NOW_TIME,
                    'log_type'   => '22',
                    'log_status' => '1',
                    'change_num' => $price,
                    'total_coin' => $user['unable_coin'] + $user['coin'] + $price,
                    'desc'       => "恭喜你收到{$gift['name']}，价值{$price}金币已到账",
                    'platform'   => $this->param['platform'],
                    'operation_time' => NOW_TIME
                ]);

                $insertId3 = M('LiveAccount')->add([
                    'user_id'   => $userInfo['id'],
                    'cover_id'  => $live['user_id'],
                    'log_id'    => $live['id'],
                    'gift_id'   => $gift['id'],
                    'gift_name' => $gift['name'],
                    'gift_img'  => $gift['img'],
                    'coin'      => $price,
                    'add_time'  => NOW_TIME,
                ]);
            }

            if ($update1 === false || $insertId1 === false
                || $update2 === false || $insertId2 === false
                || $insertId3 === false || $update3 === false || $update4 === false) {
                M()->rollback();

                $this->ajaxReturn(8010);
            } else {
                M()->commit();
            }
        }

        $sayData['user_id'] =  $userInfo['id'];
        $sayData['nick_name'] =  $userInfo['nick_name'];
        $sayData['lv'] =  $userInfo['lv'];
        $sayData['lv_bk'] =  $userInfo['lv'];
        $sayData['lv_bet'] =  $userInfo['lv_bk'];
        $sayData['head'] =  frontUserFace($userInfo['head']);
        $sayData['chat_time'] =  NOW_TIME;
        $sayData['gift'] = $gift;

        if($room_id == ''){//赛事
            $topic = 'qqty/' . $game_type . '_' . $this->param['game_id'] . '/chat';
            $sayData['support'] = $support;

            //比赛信息
            $fbInfo = M('GameFbinfo')
                ->field('home_team_name,away_team_name')
                ->where(['game_id' => $this->param['game_id']])
                ->find();

            if ($support == 1) {
                $str = $sayData['nick_name'] . '为 ' . explode(',', $fbInfo['home_team_name'])[0];
            } else {
                $str = $sayData['nick_name'] . '为 ' . explode(',', $fbInfo['away_team_name'])[0];
            }

            $str .= ' 送出 ' . $gift['name'];
            $sayData['desc'] = $str;
        }else{//主播
            $topic = 'qqty/live_' . $room_id . '/chat';

            $str = $sayData['nick_name'] . ' 送出了 ' . $gift['name'];
            $sayData['desc'] = $str;
        }

        //mqtt
        $say['action'] = 'say';
        $say['dataType'] = 'gift';
        $say['data'] = $sayData;
        $say['status'] = 1;

        $options = [
            'topic' => $topic,
            'payload' => $say,
            'clientid' => md5(time() . $userInfo['id']),
        ];

        mqttPub($options);//mqtt推送

        $sayData['coin'] = (string)$remain_coin;
        $sayData['unable_coin'] = (string)$remain_unable_coin;

        $this->ajaxReturn($sayData);
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
        $room_id = $this->param['room_id'];
        $game_id = $this->param['game_id'];
        $game_type = $this->param['game_type'];
        $content = $this->param['content'];
        $forbid_id = $this->param['user_id'];
        $msg_id = $this->param['msg_id'];
        $chat_time = $this->param['chat_time'];

        $userInfo = parent::getInfo();

        if (!$type || !$content || !$forbid_id || !$msg_id || (!$game_id && !$game_type && !$room_id))
            $this->ajaxReturn(101);

        $room_key = $game_type . '_' . $game_id;
        $room_type = 1;
        if($room_id){
            $live = M('liveLog')
                ->alias('Lg')
                ->field('Lg.id, Lg.user_id, Lg.room_id, Lg.title, Lg.start_time, Lg.live_status, U.nick_name')
                ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
                ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
                ->where(['Lg.room_id' => $room_id])
                ->find();

            $room_key = 'live_' . $live['room_id'];
            $room_type = 2;
        }

        $forbid = [
            'room_type' => $room_type,
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
            $chat_log = $redis->lRange('qqty_chat_' . $room_key, 0, -1);
            $members = $redis->sMembers('qqty_chat_forbid_userids');
            foreach ($chat_log as $k => $v) {
                $log = json_decode($v, true);
                if (in_array($log['user_id'], $members)) {
                    $redis->lRem('qqty_chat_' . $room_key, $v, 1);
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
    public function setDefault($gameId = '', $gameType = '', $roomId = ''){
        //设置默认人数
        $redis  = connRedis();
        if($roomId == ''){
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
            }
        }else{
            $dk = $dk = 'api_chatDefaultNum_' . $roomId;
            $defRand = rand(300, 700);

        }

        if($redis->get($dk) === false){
            $redis->set($dk, $defRand);
            $redis->expire($dk, 3600 * 24 * 2);
        }

        //主播房间，人数推送
        if($roomId){
            $default_num = (int)$redis->get('api_chatDefaultNum_' . $roomId);
            $randArr = [1, 2, 3, 4, 5, 6, 0, -1, -2, -3];
            $addNum = $randArr[rand(0, 9)];
            $default_num = $addNum + $default_num;
            $payload = [
                'action' => 'roomInfo',
                'data' => ['onlineNum' => $default_num, 'time' => microtime(true), 'rid' => rand(10000,99999)],
                'dataType' => 'text',
                'status' => '1'
            ];

            $opt = [
                'topic' => 'qqty/live_' . $roomId . '/chat',
                'payload' => $payload,
                'clientid' => md5(time() . $roomId),
            ];

            mqttPub($opt);
        }

        return (int) $default_num;
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

    /**
     * 抢红包
     * @return array
     */
    public function drawRedPacket(){
        $id = $this->param['id'];
        $eventId = $this->param['event_id'];
        $user = parent::getInfo();

        if(!$id || !$eventId)
            $this->ajaxReturn(101);

        $key = 'RedPacketRepertory:' . $id;
        $get_set = 'RedPacketHashLog';
        $user_get_key = 'RedPacketGetLog_' . $eventId . '_' . $user['userid'];

        $redis = connRedis();
        $repertory = $redis->rPop($key);//出列
        if($repertory){
            //成功领取记录，后台定时将其持久化到mysql
            $redis-> rPush($get_set, json_encode(['id' => $id, 'user_id' => $user['userid'], 'value' => $repertory,'time' => time()]));
            $redis->incrBy($user_get_key, $repertory);
            $redis->expire($user_get_key, 24 * 3600);
            $result = ['result' => '1', 'value' => $repertory, 'id' => $id, 'desc' => '恭喜抢到红包！'];
        }else{
            $result = ['result' => '-1', 'value' => 0, 'desc' => '很遗憾，没抢到！'];
        }

        $this->ajaxReturn($result);
    }

    /**
     * 每次红包活动结束后，返回总金额
     */
    public function getRedPacketLog(){
        $eventId = $this->param['event_id'];
        $user = parent::getInfo();

        $user_get_key = 'RedPacketGetLog_' . $eventId . '_' . $user['userid'];

        if(!$eventId)
            $this->ajaxReturn(101);

        $redis = connRedis();
        $get_coin = (int)$redis->get($user_get_key);

        $this->ajaxReturn(['coin' => $get_coin, 'notice_str' => "恭喜抢到{$get_coin}Q币，金币会稍后到账！"]);
    }
}


