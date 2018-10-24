<?php
use Think\Tool\Tool;

class ChatForbidController extends CommonController
{
    public function index()
    {
        //列表过滤器，生成查询Map对象
        $map = $this->_search('ChatForbid');

        if($_REQUEST['nick_name']){
            $user = M('FrontUser')->where(['nick_name'=> trim($_REQUEST['nick_name'])])->find();
            $map['user_id'] = ['eq', $user['id']];
            $this->assign('nick_name', $_REQUEST['nick_name']);
        }

        if($_REQUEST['username']){
            $user = M('FrontUser')->where(['username'=> trim($_REQUEST['username'])])->find();
            $map['user_id'] = ['eq', $user['id']];
            $this->assign('nick_name', $_REQUEST['nick_name']);
        }

        if($_REQUEST['content']){
            $content = trim($_REQUEST['content']);
            $map['content'] = ['like', "%{$content}%"];
            $this->assign('content', $content);
        }

        //手动获取列表
        $list = $this->_list(CM("ChatForbid"), $map, 'create_time', false);

        //获取屏蔽操作员
        foreach($list as $fk => $fv){
            if($fv['operate_type']){
                $operators[$fv['operate_type']][] = $fv['operator'];
            }
            $room = explode('_', $fv['room_id']);
            if($room[0] == 'live'){
                $live_room_id[] = $room[1];
            }
        }

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

        if($lives){
            $uids = array_column($lives, 'user_id');
            $liveUser = M('FrontUser')->where(['id' => ['IN', $uids]])->getField('id,status,nick_name');
        }


        //后台台管理员
        $admin_operators = M('User')->where(['id' => ['IN', $operators[2]]])->getField('id,nickname',true);

        //前台管理员
        $front_operators = M('FrontUser')->where(['id' => ['IN', $operators[3]]])->getField('id,nick_name',true);

        foreach($list as $k => $v){
            if($v['room_id']){
                $room = explode('_', $v['room_id']);
                if($v['room_type'] == 2){//主播
                    $live_user_id = $lives[$room[1]]['user_id'];
                    $list[$k]['room'] = "【主播】【房间{$v['room_id']} 】 {$liveUser[$live_user_id]['nick_name']}";
                }else{
                    $gameInfo = $room[0] == 1 ? $fbGameInfo[$room[1]] : $bkGameInfo[$room[1]];

                    $home_team_name = explode(',', $gameInfo['home_team_name'])[0];
                    $away_team_name = explode(',', $gameInfo['away_team_name'])[0];
                    $game_type = $room[0] == 1 ? '足球' : '篮球';

                    $list[$k]['room'] = "【{$game_type}】【房间{$v['room_id']}】{$home_team_name} <strong>VS</strong> {$away_team_name}";
                }
            }

            $list[$k]['forbid_status'] = 1;
            $list[$k]['kickout'] = 1;
            $list[$k]['operate_time'] = $v['operate_time'];
            $list[$k]['forbid_operator'] = $this->getOperator($v, $admin_operators, $front_operators);

            switch ($v['type']){
                case 1://屏蔽
                    if ($v['status'] == 1) {
                        $list[$k]['forbid_status'] = 0;//永久禁言
                        $list[$k]['forbid_status_desc'] = '永久禁言';
                    }

                    break;

                case 2://举报
                    if ($v['status'] == 1) {
                        $list[$k]['forbid_status'] = 0;//禁言
                        $list[$k]['forbid_status_desc'] = '永久禁言';
                    } else {
                        if (NOW_TIME < $v['operate_time'] + 600 && $v['status'] == 3) {
                            $list[$k]['forbid_status'] = 0;//限时禁言/踢出
                            $list[$k]['forbid_status_desc'] = '限时禁言';
                        }
                    }
                    break;

                case 3://限时禁言/踢出
                    if (NOW_TIME < $v['operate_time'] + 600 && $v['status'] == 3) {
                        $list[$k]['forbid_status'] = 0;//限时禁言/踢出
                        $list[$k]['forbid_status_desc'] = '限时禁言';
                    }
                    break;
            }

            $list[$k]['operate_time'] = $list[$k]['operate_time'] ? date('Y-m-d H:i:s', $list[$k]['operate_time']) : '-';
        }

        import('Vendor.Emoji.Emoji');
        foreach ($list as $k => $v) {
            $userids = [$v['user_id'], $v['from_id']];
            $user = M('FrontUser')->where(['id' => ['IN', $userids]])->getField('id,nick_name');
            $list[$k]['forbid_nick'] = $user[$v['user_id']];
            $list[$k]['from_nick'] = $user[$v['from_id']];
            $list[$k]['content'] = emoji_unified_to_html(json_decode($v['content'])) ? : emoji_unified_to_html($v['content']);
        }

        $this->assign('list', $list);
        $this->display();
    }

    public function add()
    {
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
        $redis = connRedis();

        $model = D('ChatForbid');

        if (!isset($_REQUEST['status'])) {//屏蔽、踢出
            $type = (int)$_REQUEST['type'];
            $forbidUp = [
                'type' => $type,
                'operate_time' => NOW_TIME,
                'operator' => $_SESSION['authId'],
                'operate_type' => 2,
                'status' => $type
            ];
            $rs = $model->where(['id' => (int)$_REQUEST['id']])->save($forbidUp);

            if($rs === false)
                $this->error('保存失败!');

            $forbid = $model->find((int)$_REQUEST['id']);

            M('Chatlog')->where(['user_id' => (int)$_REQUEST['user_id']])->save(['status' => 'hidden']);

            //通知客户端
            if ($_REQUEST['type'] == 1) {
                $action = 'forbid';
                $notice_str = '您的聊天内容已经严重违反了全球体育平台规则，您将被永久屏蔽帐号';

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
        } else {
            $forbidUp = [
                'operate_time' => NOW_TIME,
                'operator' => $_SESSION['authId'],
                'operate_type' => 2,
                'status' => 2
            ];

            $user_id = (int)$_REQUEST['user_id'];

            $rs = $model->where(['user_id' => $user_id])->save($forbidUp);

            if($rs === false)
                $this->error('保存失败!');

            //从redis集合移除被禁用户
            $redis->sRem('qqty_chat_forbid_userids', $user_id);
        }

        //成功提示
        $this->success('保存成功!', cookie('_currentUrl_'));
    }

    //批量恢复
    public function recoverAll()
    {
        $model = M("ChatForbid");
        if (!empty($model)) {
            $ids = isset($_POST['id']) ? $_POST['id'] : null;
            if ($ids) {
                $redis = connRedis();

                $idsArr = explode(',', $ids);
                $condition = array("id" => array('in', $idsArr));
                $forbidUp = [
                    'operate_time' => NOW_TIME,
                    'operator' => $_SESSION['authId'],
                    'operate_type' => 2,
                    'status' => 2
                ];
                if (false !== $model->where($condition)->save($forbidUp)) {
                    $list = $model->where($condition)->select($forbidUp);
                    $user_ids = array_column($list, 'user_id');
                    foreach($user_ids as $k => $v){
                        //从redis集合移除被禁用户
                        $redis->sRem('qqty_chat_forbid_userids', $v);
                    }

                    $this->success('批量恢复成功！');
                } else {
                    $this->error('批量恢复失败！');
                }
            } else {
                $this->error('非法操作');
            }
        }
    }

}