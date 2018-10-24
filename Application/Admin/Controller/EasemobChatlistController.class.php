<?php

/**
 * mqtt聊天室消息
 */
class EasemobChatlistController extends CommonController
{
        public function index(){
            $map = $this->_search('ChatForbid');

            if($_REQUEST['type_room'] || $_REQUEST['room_id']){
                if(!$_REQUEST['type_room']){
                    $map['room_id'] = ['like', '%' . trim($_REQUEST['type_room']) . '_' . trim($_REQUEST['room_id'])."%"];
                }else{
                    $map['room_id'] = ['like', trim($_REQUEST['type_room']) . '_' . trim($_REQUEST['room_id'])."%"];
                }

                $this->assign('type_room', trim($_REQUEST['type_room']));
                $this->assign('room_id', $_REQUEST['room_id']);
            }

            if($_REQUEST['game_type'] && $_REQUEST['game_id']){
                $map['room_id'] = $_REQUEST['game_type'] . '_' . $_REQUEST['game_id'];
                $this->assign('game_type', $_REQUEST['game_type']);
                $this->assign('game_id', $_REQUEST['game_id']);
            }

            if($_REQUEST['nick_name']){
                $nickname = trim($_REQUEST['nick_name']);
                $map['nick_name'] = $nickname;
                $this->assign('nick_name', $nickname);
            }

            if($_REQUEST['content']){
                $content = trim($_REQUEST['content']);
                $map['content'] = ['like', "%{$content}%"];
                $this->assign('content', $content);
            }

            //获取列表
            $list = $this->_list(CM('Chatlog'),array_merge($map, ['user_type' => 1]));

            //房间赛事信息
            $fbids = $bkids = $userids = [];
            foreach($list as $k=>$v){
                $room = explode('_', $v['room_id']);
                if($room[0] == 1){
                    $fbids[] = $room[1];
                }else if($room[0] == 2){
                    $bkids[] = $room[1];
                }else if($room[0] == 'live'){
                    $live_room_id[] = $room[1];
                }

                $userids[] = $v['user_id'];
            }

            $bkids = array_unique($bkids);
            $fbids = array_unique($fbids);
            $userids = array_unique($userids);

            $fbGameInfo = M('GameFbinfo')
                ->where(['game_id' => ['IN', $fbids]])
                ->getField('game_id,union_name,home_team_name,away_team_name',true);

            $bkGameInfo = M('GameBkinfo')
                ->where(['game_id' => ['IN', $bkids]])
                ->getField('game_id,union_name,home_team_name,away_team_name',true);

            //房间主播信息
            $lives = M('LiveLog')
                ->where(['room_id' => ['IN', $live_room_id]])
                ->getField('room_id,title,user_id',true);

            //聊天状态、用户状态
            $forbids = M('ChatForbid')
                ->where(['user_id' => ['IN', $userids]])
                ->getField('user_id,type,status,operator,operate_type,operate_time', true);


            if($lives){
                $uids = array_column($lives, 'user_id');
                $userids = array_merge($userids, $uids);
            }
            $front_user = M('FrontUser')->where(['id' => ['IN', $userids]])->getField('id,status,nick_name',true);

            //获取屏蔽操作员
            foreach($forbids as $fk => $fv){
                if($fv['operate_type']){
                    $operators[$fv['operate_type']][] = $fv['operator'];
                }
            }

            //后台台管理员
            $admin_operators = M('User')->where(['id' => ['IN', $operators[2]]])->getField('id,nickname',true);
            //前台管理员
            $front_operators = M('FrontUser')->where(['id' => ['IN', $operators[3]]])->getField('id,nick_name',true);

            foreach($list as $k => $v){
                $room = explode('_', $v['room_id']);
                if($v['room_type'] == 2){//主播
                    $live_user_id = $lives[$room[1]]['user_id'];
                    $list[$k]['room'] = "【主播】【房间{$v['room_id']} 】 {$front_user[$live_user_id]['nick_name']}";
                }else{
                    $gameInfo = $room[0] == 1 ? $fbGameInfo[$room[1]] : $bkGameInfo[$room[1]];

                    $home_team_name = explode(',', $gameInfo['home_team_name'])[0];
                    $away_team_name = explode(',', $gameInfo['away_team_name'])[0];
                    $game_type = $room[0] == 1 ? '足球' : '篮球';

                    $list[$k]['room'] = "【{$game_type}】【房间{$v['room_id']}】{$home_team_name} <strong>VS</strong> {$away_team_name}";
                }

                $forbid = $forbids[$v['user_id']];

                //-----------用户聊天屏蔽状态----------------
                $list[$k]['forbid_status'] = 1;
                $list[$k]['kickout'] = 1;

                $list[$k]['operate_time'] = $forbid['operate_time'];
                $list[$k]['forbid_operator'] = $this->getOperator($forbid, $admin_operators, $front_operators);

                switch ($forbid['type']){
                    case 1://屏蔽
                        if($forbid['status'] == 1){
                            $list[$k]['forbid_status'] = 0;//永久禁言
                            $list[$k]['forbid_status_desc'] = '永久禁言';
                        }
                        break;

                    case 2://举报
                        if ($forbid['status'] == 1) {
                            $list[$k]['forbid_status'] = 0;//永久禁言
                            $list[$k]['forbid_status_desc'] = '永久禁言';
                        } else {
                            if (NOW_TIME < $forbid['operate_time'] + 600 && $v['status'] == 3) {
                                $list[$k]['forbid_status'] = 0;//限时禁言/踢出
                                $list[$k]['forbid_status_desc'] = '限时禁言';
                            }
                        }
                        break;

                    case 3://限时禁言/踢出
                        if (NOW_TIME < $forbid['operate_time'] + 600  && $v['status'] == 3) {
                            $list[$k]['forbid_status'] = 0;//限时禁言/踢出
                            $list[$k]['forbid_status_desc'] = '限时禁言';

                        }
                        break;
                }

                $list[$k]['operate_time'] = $list[$k]['operate_time'] ? date('Y-m-d H:i:s', $list[$k]['operate_time']) : '-';
                //用户状态
                $list[$k]['user_status'] = intval($front_user[$v['user_id']]['status']);

            }
            $this->assign('list', $list);
            $this->display();
        }


    /**
     * @param $data
     * @param $admin_operators
     * @param $front_operators
     * @return string
     */
    public function getOperator($data, $admin_operators, $front_operators){
        if($data['operate_type'] == 1){//系统屏蔽
            $systemArr = ['程序','广告程序', '刷屏程序', '敏感词程序'];
            $forbid_operator  = '系统:' . $systemArr[$data['operator']];
        }elseif ($data['operate_type'] == 2){//后台管理员屏蔽
            $forbid_operator  = $admin_operators[$data['operator']];
        }elseif ($data['operate_type'] == 3){//前台管理员屏蔽
            $forbid_operator  = $front_operators[$data['operator']];
        }

        return $forbid_operator;
    }


    /**
     * 保存/修改记录
     *
     * @return #
     */
    public function save()
    {
        //用户状态
        if(isset($_REQUEST['user_status'])){
            M('FrontUser')->where(['id' => (int)$_REQUEST['user_id']])->save(['status' => $_REQUEST['user_status']]);
        }else{
            //通知客户端
            $redis = connRedis();

            $forbid = M('Chatlog')->find((int)$_REQUEST['id']);

            if($forbid){
                M('Chatlog')->where(['user_id' => (int)$_REQUEST['user_id']])->save(['status' => 'hidden']);
                //添加到禁止列表
                $forbid = [
                    'user_id' => $forbid['user_id'],
                    'type' => $_REQUEST['status'],
                    'content' => $forbid['content'],
                    'room_id' => $forbid['room_id'],
                    'room_type' => $forbid['room_type'],
                    'msg_id' => $forbid['msg_id'],
                    'chat_time' => $forbid['chat_time'],
                    'status' => $_REQUEST['status'],
                    'create_time' => NOW_TIME,
                    'operate_time' => NOW_TIME,
                    'operator' => $_SESSION['authId'],
                    'operate_type' => 2,

                ];
                M('ChatForbid')->add($forbid);

                //mqtt推送
                if ($_REQUEST['status'] == 1) {
                    $action = 'forbid';
                    $notice_str = '您的聊天内容已经严重违反了全球体育平台规则，您将被永久屏蔽帐号';
                } else {
                    $action = 'kickout';
                    $notice_str = '您的聊天内容影响到其他用户，你将被禁言十分钟';
                }

                $pubData = [
                    'data' => ['user_id' => $forbid['user_id'], 'notice_str' => $notice_str, 'msg_id' => $forbid['msg_id']],
                    'action' => $action,
                    'dataType' => 'text',
                    'status' => 1,
                    'platform' => 1,
                ];

                //mqtt
                $opt = [
                    'topic' => 'qqty/' . $forbid['room_id'] . '/chat',
                    'payload' => $pubData,
                    'clientid' => md5(time() . $forbid['user_id']),
                ];

                mqttPub($opt);

                //记录被禁用户
                $redis->sAdd('qqty_chat_forbid_userids', $forbid['user_id']);

                //过滤被屏蔽的用户消息
                $chat_log = $redis->lRange('qqty_chat_' . $forbid['room_id'], 0, -1);
                $members = $redis->sMembers('qqty_chat_forbid_userids');

                foreach ($chat_log as $k => $v) {
                    $log = json_decode($v, true);
                    if (in_array($log['user_id'], $members)) {
                        $redis->lRem('qqty_chat_' . $forbid['room_id'], $v, 1);
                    }
                }
            }
        }

        //成功提示
        $this->success('保存成功!', cookie('_currentUrl_'));
    }

    /**
     * 屏蔽消息和禁用用户
     */
    public function saveAll(){
        $chat_log = M('Chatlog')->where(['id' => ['IN', $_REQUEST['id']]])->select();

        //更新消息状态为屏蔽
        M('Chatlog')->where(['id' => ['IN', $_REQUEST['id']]])->save(['status' => 'hidden']);

        $userids = $chat_forbid = [];
        $redis = connRedis();
        foreach($chat_log as $k => $v){
            $userids[] = $v['user_id'];
            $chat_forbid[] = [
                'user_id' => $v['user_id'],
                'type' => '1',
                'content' => $v['content'],
                'room_type' => $v['room_type'],
                'room_id' => $v['room_id'],
                'msg_id' => $v['msg_id'],
                'chat_time' => $v['chat_time'],
                'status' => 1,
                'create_time' => NOW_TIME,
                'operate_time' => NOW_TIME,
                'operator' => $_SESSION['authId'],
                'operate_type' => 2
            ];

            //记录被禁用户
            $redis->sAdd('qqty_chat_forbid_userids', $v['user_id']);

            //过滤被屏蔽的用户消息
            $chat_log = $redis->lRange('qqty_chat_' . $v['room_id'], 0, -1);
            $members = $redis->sMembers('qqty_chat_forbid_userids');

            foreach ($chat_log as $k1 => $v1) {
                $log = json_decode($v1, true);
                if (in_array($log['user_id'], $members)) {
                    $redis->lRem('qqty_chat_' . $v['room_id'], $v1, 1);
                }
            }
        }
        M('ChatForbid')->addAll($chat_forbid);

        if($_REQUEST['type'] == 2){//禁用用户
            M('FrontUser')->where(['id' => ['IN', $userids]])->save(['status' => 0]);
        }

        //成功提示
        $this->success('保存成功!', cookie('_currentUrl_'));
    }

    //恢复
    public function recover()
    {
        $model = M("ChatForbid");
        if (!empty($model)) {
            $user_id = isset($_REQUEST['user_id']) ? $_REQUEST['user_id'] : null;
            if ($user_id) {
                $redis = connRedis();

                $forbidUp = [
                    'operate_time' => NOW_TIME,
                    'operator' => $_SESSION['authId'],
                    'operate_type' => 2,
                    'status' => 2
                ];
                if (false !== $model->where(['user_id' => $user_id])->save($forbidUp)) {
                    $redis->sRem('qqty_chat_forbid_userids', $user_id);

                    $this->success('解除成功');
                } else {
                    $this->error('解除失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }
}

?>