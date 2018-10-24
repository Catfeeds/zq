<?php
/**
 * 环信相关
 * @author huangjiezhen <418832673@qq.com> 2016.01.21
 */

class EasemobController extends PublicController
{
    //获取环信账户密码
    public function getPassword()
    {
        if (!$this->param['username'] || !$this->param['platform'])
            $this->ajaxReturn(201);

        $eUserInfo = M('EasemobUser')->field(['password','is_push'])->where(['username'=>$this->param['username']])->find();

        if (!$eUserInfo)
        {
            import('Vendor.Easemob.Easemob');
            $Easemob = new \Easemob(C('Easemob'));

            //到环信服务器注册帐号
            $password = GetRandStr(8);
            $res = $Easemob->createUser($this->param['username'],md5($password));

            if (!$res && !isset($res['entities']) && !$res['entities'] && $res['error'] != 'duplicate_unique_property_exists')
                $this->ajaxReturn(3001);

            //保存帐号到自己的服务器
            $data = [
                'username'    => $this->param['username'],
                'password'    => $password,
                'platform'    => $this->param['platform'],
                'create_time' => NOW_TIME,
                'login_time'  => NOW_TIME
            ];

            $insertId = M('EasemobUser')->add($data);

            if (!$insertId)
                $this->ajaxReturn(3002);

            $eUserInfo['password'] = $password;
            $eUserInfo['is_push']  = "0";
        }

        M('EasemobUser')->where(['username'=>$this->param['username']])->save(['login_time'=>NOW_TIME]);

        $this->ajaxReturn($eUserInfo);
    }

    //绑定用户到环信帐号
    public function bindUser()
    {
        $userInfo = getUserToken($this->param['userToken']);

        if (!$userInfo)
            $this->ajaxReturn(1001);

        M('EasemobUser')->where(['uid'=>$userInfo['userid']])->save(['uid'=>null]); //去除之前的绑定用户
        $update = M('EasemobUser')->where(['username'=>$this->param['username']])->save(['uid'=>$userInfo['userid']]); //绑定到新的用户

        if ($update === false)
            $this->ajaxReturn(3003);

        //设置环信用户的昵称
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $nickname = M('FrontUser')->where(['id'=>$userInfo['userid']])->getField('nick_name');
        $Easemob->editNickname($this->param['username'],$nickname);

        $this->ajaxReturn(['bind'=>1]);
    }

    //设置是否推送
    public function isPush()
    {
        $isPush = (int)$this->param['is_push'] == 0 ? 0 : 1;

        if (M('EasemobUser')->where(['username'=>$this->param['username']])->save(['is_push'=>$isPush]) === false)
            $this->ajaxReturn(3004);

        $redis = connRedis();

        if ($redis->hset(C('em').'user:'.$this->param['username'],'is_push',$isPush) === false)
            $this->ajaxReturn(3004);

        $this->ajaxReturn(['is_push'=>$isPush]);
    }

    //关注赛程
    public function followGame()
    {
        if (!$this->param['username'] || !$this->param['game_id'])
            $this->ajaxReturn(201);

        $redis = connRedis();

        $keyUser = C('em').'user:'.$this->param['username'];
        $lastTime = $redis->hget($keyUser,'last_follow_time');

        $keyUserFollow = C('em').'user_follow:'.$this->param['username'];

        if ($lastTime < strtotime('10:30')) //今天10.半之前的重置关注列表
            $redis->del($keyUserFollow);

        if ($redis->sadd($keyUserFollow,$this->param['game_id']) === false || $redis->hset($keyUser,'last_follow_time',NOW_TIME) === false)
            $this->ajaxReturn(3005);

        $keyGameFollow  = C('em').'game_follow:'.$this->param['game_id'];

        if ($redis->sadd($keyGameFollow,$this->param['username']) === false || $redis->expire($keyGameFollow,3600*24) === false)
            $this->ajaxReturn(3005);

        $this->ajaxReturn(['game_id'=>$this->param['game_id']]);
    }

    //取消关注赛程
    public function cancelFollowGame()
    {
        if (!$this->param['username'] || !$this->param['game_id'])
            $this->ajaxReturn(201);

        $redis = connRedis();

        $keyUserFollow = C('em').'user_follow:'.$this->param['username'];
        $keyGameFollow  = C('em').'game_follow:'.$this->param['game_id'];

        if ($redis->srem($keyUserFollow,$this->param['game_id']) === false || $redis->srem($keyGameFollow,$this->param['username']) === false)
            $this->ajaxReturn(3006);

        $this->ajaxReturn(['game_id'=>$this->param['game_id']]);
    }

    //获取关注的赛程
    public function myFollowGame()
    {
        $redis = connRedis();
        $list = $redis->smembers(C('em').'user_follow:'.$this->param['username']);

        $this->ajaxReturn(['list'=>$list]);
    }

    /**
        聊天室相关
    */

    //进入聊天室
    public function joinChatroom()
    {
        //赛程状态的判断，如：推迟、取消
        // $gameInfo = M('GameFbinfo')->field(['game_date','game_time','game_state'])->where(['game_id'=>$this->param['game_id'],'is_video'=>1])->find();
        $gameInfo = M('GameFbinfo')->field(['gtime','game_state'])->where(['game_id'=>$this->param['game_id']])->find();

        if (!$gameInfo || !in_array($gameInfo['game_state'], [0,1,2,3,4,-1]))
            $this->ajaxReturn(3007);

        //进入聊天室时间在比赛前1小时后
        $beginTime = $gameInfo['gtime'];

        // if ($beginTime - time() > 3600)
        //     $this->ajaxReturn(['beginTime'=>(string)$beginTime]);

        //判断用户的屏蔽状态，屏蔽多久
        $eUserInfo = M('EasemobUser')->field(['is_block','block_time'])->where(['username'=>$this->param['username']])->find();

        if (!$eUserInfo)
            $this->ajaxReturn(3009);

        //聊天室是否存在，不存在创建
        $chatroomId = M('EasemobChatroom')->where(['game_id'=>$this->param['game_id']])->getField('chatroom_id');

        if (!$chatroomId)
        {
            $owner = 'quancai_admin';
            $chatroomInfo = $this->createChatRoom($this->param['game_id'],$owner);

            if ($chatroomId = @$chatroomInfo['data']['id'])
                M('EasemobChatroom')->data(['game_id'=>$this->param['game_id'],'chatroom_id'=>$chatroomId,'owner'=>$owner])->add();
            else
                $this->ajaxReturn(3010);
        }

        $data = array_merge(['chatroomId'=>$chatroomId,'beginTime'=>(string)$beginTime],$eUserInfo);
        $this->ajaxReturn($data);
    }

    //举报聊天内容
    public function reportChat()
    {
        if (
            !$this->param['uid'] ||
            !$this->param['username'] ||
            !$this->param['nickname'] ||
            !$this->param['content'] ||
            !$this->param['chat_time']
        )
        {
            $this->ajaxReturn(101);
        }

        $this->param['create_time'] = time();

        if (M('EasemobReport')->add($this->param) === false)
            $this->ajaxReturn(3011);

        $this->ajaxReturn(['result'=>1]);
    }

    //获取环信用户信息
    public function getUsers()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $info = $Easemob->getUsers(10000);
        $this->ajaxReturn(['userinfo'=>$info]);
    }

    //获取app中所有的聊天室
    public function getChatRooms()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $info = $Easemob->getChatRooms();
        $this->ajaxReturn(['info'=>$info]);
    }

    //创建聊天室
    public function createChatRoom($roomName='',$owner='quancai_admin')
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $roomName = $roomName ?: time();

        $data = [
            'name'        => 'room:'.$roomName,
            'description' => 'server create chatroom '.$roomName,
            'maxusers'    => '5000',
            'owner'       => $owner
        ];

        return $Easemob->createChatRoom($data);
    }

    //删除聊天室
    public function deleteChatRoom()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $info = $Easemob->deleteChatRoom($this->param['chatroom_id']);
        $result = M('EasemobChatroom')->where(['chatroom_id'=>$this->param['chatroom_id']])->delete();

        if ($result === false)
            $this->ajaxReturn(['info'=>'delete fail']);

        $this->ajaxReturn(['info'=>$info]);
    }

    //获取聊天室详情
    public function getChatRoomDetail()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $info = $Easemob->getChatRoomDetail($this->param['chatroom_id']);
        $this->ajaxReturn(['info'=>$info]);
    }

    //聊天室单个成员添加
    public function addChatRoomMember()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $roomId = '181611024011493844';
        $info = $Easemob->addChatRoomMember($roomId,$username='18f1ff5fbd943f5bbb5dcfc15056cd8ccn.qqw.app');
        $this->ajaxReturn(['info'=>$info]);
    }

    //聊天室单个成员删除
    public function deleteChatRoomMember()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $roomId = '184152682028597684';
        $info = $Easemob->deleteChatRoomMember($roomId,$username='18f1ff5fbd943f5bbb5dcfc15056cd8ccn.qqw.app');
        $this->ajaxReturn(['info'=>$info]);
    }

    //导出聊天记录----不分页
    public function getChatRecord()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $roomId = '181611024011493844';
        $info = $Easemob->addChatRoomMember($roomId,$username='8952d563a7b534df965cb645a7ad1d1dcn.qqw.app');
        $this->ajaxReturn(['info'=>$info]);
    }

    //获取一个用户加入的所有聊天室
    public function getChatRoomJoined()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $username='18f1ff5fbd943f5bbb5dcfc15056cd8ccn.qqw.app';
        $info = $Easemob->getChatRoomJoined($username);
        $this->ajaxReturn(['info'=>$info]);
    }

    //发送透传消息
    public function sendCmd()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $chatroom_id = $this->param['chatroom_id'];
        $username = $this->param['username'];

        $info = $Easemob->sendCmd($from="admin",$target_type='chatrooms',$target=[$chatroom_id],$action='kickout',$ext=['username'=>$username]);
        $this->ajaxReturn(['info'=>$info]);
    }

    //发送环信消息
    public function sendText()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $info = $Easemob->sendText($from="admin",$target_type='users',$target=[$this->param['username']],'testing a msg',['em_apns_ext'=>['em_push_title'=>'testing']]);
        $this->ajaxReturn(['info'=>$info]);
    }
}

 ?>