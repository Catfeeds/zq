<?php

/**
 * 用户推送相关
 * @author huangjiezhen <418832673@qq.com> 2016.01.21
 */
class PushController extends PublicController
{
    private $_oRedis;
    private $_skeyPre               = 'push_';
    private $_bValidToken           = true;
    private $_sUserInfo             = '';

    private $_sFbUserFollowKey      = '';
    private $_sFbDeviceFollowKey    = '';
    private $_sFbGameFollowKey      = '';
    private $_sBKDeviceFollowKey    = '';
    private $_sBkUserFollowKey      = '';
    private $_sBkGameFollowKey      = '';

    private $_sPushName             = '';

    public function _initialize(){
        parent::_initialize();

        $this->_sPushName = $this->param['deviceID'];

        //userToken不为空的时候要验证有效性
        if( $this->param['userToken'] != ''){
            $this->_sUserInfo = getUserToken($this->param['userToken']);

            if(!$this->_sPushName = $this->_sUserInfo['userid']){
                $this->_bValidToken = false;
                $this->_sPushName   = $this->param['deviceID'];
            }
        }

        //Fb key
        $this->_sFbUserFollowKey    = $this->_skeyPre . 'fb_user_follow:' . $this->_sPushName;
        $this->_sFbDeviceFollowKey  = $this->_skeyPre . 'fb_user_follow:' . $this->param['deviceID'];
        $this->_sFbGameFollowKey    = $this->_skeyPre . 'fb_game_follow:';

        //Bk key
        $this->_sBkUserFollowKey    = $this->_skeyPre . 'bk_user_follow:' . $this->_sPushName;
        $this->_sBKDeviceFollowKey  = $this->_skeyPre . 'bk_user_follow:' . $this->param['deviceID'];
        $this->_sBkGameFollowKey    = $this->_skeyPre . 'bk_game_follow:';

        $this->_oRedis = connRedis();

    }

    /**
     * 环信，创建环信用户
     */
    public function createEasemobUser()
    {
        if (!$this->param['deviceID'] || !$this->param['platform'])
            $this->ajaxReturn(101);

        $userType = 1;

        $addData = [
            'username'      => $this->_sPushName,
            'user_type'     => $userType,
            'platform'      => $this->param['platform'],
            'login_time'    => NOW_TIME,
            'create_time'   => NOW_TIME
        ];
        $where = ['username' => $this->_sPushName, 'user_type' => $userType];

        if ($this->_sUserInfo['userid']) {
            //更新设备号到用户表
            M('FrontUser')->where(['id' => $this->_sUserInfo['userid']])->save(['device_token' => $this->param['deviceID']]);

            $addData['uid'] = $this->_sPushName;
            $this->moveFollowGame();
        }

        $emUser = M('EasemobUser')->master(true)->field(['username as eUser', 'password as ePwd', 'is_push as isPush'])->where($where)->find();

        if (!$emUser) {
            import('Vendor.Easemob.Easemob');
            $Easemob = new \Easemob(C('Easemob'));

            //先删除环信账号，再到环信服务器注册帐号
            $Easemob->deleteUser($this->_sPushName);
            $password = 'qc_12345';
            $res = $Easemob->createUser($this->_sPushName, md5($password));

            if(!$res || isset($res['error']) || !$res['entities'] || $res['error'] == 'duplicate_unique_property_exists')
                $this->ajaxReturn(3001);

            $addData['password'] = $password;
            M('EasemobUser')->add($addData);

            $emUser = ['isPush' => '0', 'eUser' => $this->_sPushName, 'ePwd' => $password];
        } else {
            M('EasemobUser')->where($where)->save(['login_time' => NOW_TIME]);
        }

        $this->ajaxReturn($emUser);
    }

    /**
     * 友盟，获取设备别名和设备别名类型
     */
    public function createUmengUser()
    {
        if (!$this->param['deviceID'] || !$this->param['platform'])
            $this->ajaxReturn(101);

        $userType = 2;

        $addData = [
            'username'      => $this->_sPushName,
            'user_type'     => $userType,
            'alias_type'    => 'QQTY',
            'platform'      => $this->param['platform'],
            'login_time'    => NOW_TIME,
            'create_time'   => NOW_TIME
        ];

        if ($this->_sUserInfo['userid']) {
            $where = ['username' => $this->_sPushName, 'alias_type' => 'QQTY'];

            $addData['uid'] = $this->_sPushName;

            $this->moveFollowGame();

        } else {
            $where = ['username' => $this->_sPushName, 'alias_type' => 'QQTY'];
            $addData['username'] = $this->_sPushName;
        }

        $umengUser = M('EasemobUser')
            ->master(true)
            ->field(['is_push as isPush', 'username as alias', 'alias_type as aliasType'])
            ->where($where)->find();

        if (!$umengUser) {
            M('EasemobUser')->add($addData);
            $umengUser['isPush']    = '0';
            $umengUser['alias']     = $this->_sPushName;
            $umengUser['aliasType'] = 'QQTY';
        } else {
            M('EasemobUser')->where($where)->save(['login_time' => NOW_TIME]);
        }

        $this->ajaxReturn($umengUser);
    }

    /**
     * 设置是否推送
     */
    public function setPush()
    {
        if (!$this->param['deviceID'])
            $this->ajaxReturn(101);

        if (!$this->_bValidToken)
            $this->ajaxReturn(1001);

        $isPush = (int)$this->param['isPush'] == 0 ? 0 : 1;

        if (M('EasemobUser')->where(['username' => $this->_sPushName])->save(['is_push' => $isPush]) === false)
            $this->ajaxReturn(3004);

        if ($this->_oRedis->hset($this->_skeyPre . 'user:' . $this->_sPushName, 'is_push', $isPush) === false)
            $this->ajaxReturn(3004);

        $this->ajaxReturn(['isPush' => $isPush]);
    }

    /**
     * 关注赛程;登录时，依然可以收到未登陆时设备关注的赛程推送
     */
    public function followGame()
    {
        if (!$this->param['deviceID'] || !$this->param['gameId'] || !$this->param['gameType'])
            $this->ajaxReturn(101);

        if (!$this->_bValidToken)
            $this->ajaxReturn(1001);

        switch($this->param['gameType']){
            case 1:
                $userFollowKey = $this->_sFbUserFollowKey;
                $gameFollowKey = $this->_sFbGameFollowKey . $this->param['gameId'];
                break;

            case 2:
                $userFollowKey = $this->_sBkUserFollowKey;
                $gameFollowKey = $this->_sBkGameFollowKey . $this->param['gameId'];
                break;
        }

        //今天10.半之前的重置用户关注赛程
//        if (NOW_TIME < strtotime('10:30'))
//            $this->_oRedis->del($userFollowKey);

        if ($this->_oRedis->sadd($userFollowKey, $this->param['gameId']) === false)
            $this->ajaxReturn(3005);

        if ($this->_oRedis->sadd($gameFollowKey, $this->_sPushName) === false)
            $this->ajaxReturn(3005);

        if ($this->_oRedis->expire($gameFollowKey, 3600 * 24) === false)
            $this->ajaxReturn(3005);

        if ($this->_oRedis->expire($userFollowKey, 3600 * 24) === false)
            $this->ajaxReturn(3005);

        $this->ajaxReturn(['gameId' => $this->param['gameId']]);
    }

    /**
     * 取消关注赛程
     */
    public function cancelFollowGame()
    {
        if (!$this->param['deviceID'] || !$this->param['gameId'] || !$this->param['gameType'])
            $this->ajaxReturn(101);

        if (!$this->_bValidToken)
            $this->ajaxReturn(1001);

        switch($this->param['gameType']){
            case 1:
                $userFollowKey = $this->_sFbUserFollowKey;
                $gameFollowKey = $this->_sFbGameFollowKey . $this->param['gameId'];
                break;

            case 2:
                $userFollowKey = $this->_sBkUserFollowKey;
                $gameFollowKey = $this->_sBkGameFollowKey . $this->param['gameId'];
                break;
        }

        if ($this->_oRedis->srem($userFollowKey, $this->param['gameId']) === false)
            $this->ajaxReturn(3006);

        if ($this->_oRedis->srem($gameFollowKey, $this->_sPushName) === false)
            $this->ajaxReturn(3006);

        $this->ajaxReturn(['gameId' => $this->param['gameId']]);
    }

    /**
     * 获取关注的赛程
     */
    public function myFollowGame()
    {
        if (!$this->param['deviceID'])
            $this->ajaxReturn(101);

        if (!$this->_bValidToken)
            $this->ajaxReturn(1001);

        $fbList = $this->_oRedis->smembers($this->_sFbUserFollowKey) ?: [];
        $bkList = $this->_oRedis->smembers($this->_sBkUserFollowKey) ?: [];

        $this->ajaxReturn(['fbList' => $fbList, 'bkList' => $bkList]);
    }

    /**
     * 聊天室相关
     */
    public function joinChatRoom()
    {
        if (!$this->_bValidToken)
            $this->ajaxReturn(1001);

        $gameType   = $this->param['gameType'];//1足球、2篮球
        $model      = '';
        $gameState  = [];

        //赛程状态的判断，如：推迟、取消
        switch ($gameType) {
            case 1:
                $model = M('GameFbinfo');
                $gameState = [0, 1, 2, 3, 4, -1];
                break;
            case 2:
                $model = M('GameBkinfo');
                $gameState = [0, 1, 2, 50, 3, 4, 5, 6, -1];
                break;
            default:
                $this->ajaxReturn(101);
                break;
        }

        $gameInfo = $model->field(['gtime', 'game_state'])->where(['game_id' => $this->param['gameId']])->find();

        if (!$gameInfo || !in_array($gameInfo['game_state'], $gameState))
            $this->ajaxReturn(3007);

        //进入聊天室时间在比赛前1小时后
        $beginTime = $gameInfo['gtime'];

        //判断用户(环信)的屏蔽状态，屏蔽多久
        $eUserInfo = M('EasemobUser')->field(['is_block', 'block_time'])->where(['username' => $this->_sPushName, 'user_type' => 1])->find();

        if (!$eUserInfo)
            $this->ajaxReturn(3009);

        //聊天室是否存在，不存在创建
        $chatroomId = M('EasemobChatroom')->where(['game_id' => $this->param['gameId'], 'game_type' => $gameType])->getField('chatroom_id');

        if (!$chatroomId) {
            $owner = 'quancai_admin';
            $rid = $gameType == 2 ? $gameType . '_' .$this->param['gameId']:$this->param['gameId'];
                $chatroomInfo = $this->createChatRoom($rid, $owner);

            if ($chatroomId = @$chatroomInfo['data']['id'])
                M('EasemobChatroom')->data(['game_id' => $this->param['gameId'], 'game_type' => $gameType, 'chatroom_id' => $chatroomId, 'owner' => $owner])->add();
            else
                $this->ajaxReturn(3010);
        }

        $data = array_merge(['gameState' => $gameInfo['game_state'], 'chatroomId' => $chatroomId, 'beginTime' => (string)$beginTime], $eUserInfo);
        $this->ajaxReturn($data);
    }

    /**
     * 举报聊天内容
     */
    public function reportChat()
    {

        if (!$this->param['eUser'] || !$this->param['nickname'] || !$this->param['content'] || !$this->param['chat_time'])
            $this->ajaxReturn(101);

        $data = [
            'username'      =>$this->param['eUser'],
            'nickname'      =>$this->param['nickname'],
            'content'       =>$this->param['content'],
            'chat_time'     =>$this->param['chat_time'],
            'create_time'   =>time(),
        ];

        if (M('EasemobReport')->add($data) === false)
            $this->ajaxReturn(3011);

        $this->ajaxReturn(['result' => 1]);
    }

    /**
     * 创建聊天室
     * @param string $roomName
     * @param string $owner
     * @return mixed
     */
    public function createChatRoom($roomName = '', $owner = 'quancai_admin')
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));

        $roomName = $roomName ?: time();

        $data = [
            'name' => 'room:' . $roomName,
            'description' => 'server create chatroom ' . $roomName,
            'maxusers' => '5000',
            'owner' => $owner
        ];

        return $Easemob->createChatRoom($data);
    }

    /**
     * 将未登陆时的关注赛程加入当前登录用户关注赛程;并且在对应的赛程下加入当前用户id的关注；分篮球和足球
     */
    public function moveFollowGame(){

        if (NOW_TIME < strtotime('10:30')) {
//            $this->_oRedis->del($this->_sFbDeviceFollowKey);
//            $this->_oRedis->del($this->_sBKDeviceFollowKey);
        } else {
            $this->_oRedis->sUnionStore($this->_sFbUserFollowKey, $this->_sFbDeviceFollowKey, $this->_sFbUserFollowKey);
            $this->_oRedis->sUnionStore($this->_sBkUserFollowKey, $this->_sBKDeviceFollowKey, $this->_sBkUserFollowKey);

            $fbDeviceFollowGame = $this->_oRedis->sMembers($this->_sFbDeviceFollowKey);
            $bkDeviceFollowGame = $this->_oRedis->sMembers($this->_sBKDeviceFollowKey);

            foreach ($fbDeviceFollowGame as $gid) {
                $this->_oRedis->sadd($this->_sFbGameFollowKey . $gid, $this->_sPushName);
                $this->_oRedis->expire($this->_sFbGameFollowKey . $gid, 3600 * 24);
            }

            foreach ($bkDeviceFollowGame as $gid) {
                $this->_oRedis->sadd($this->_sBkGameFollowKey . $gid, $this->_sPushName);
                $this->_oRedis->expire($this->_sBkGameFollowKey . $gid, 3600 * 24);
            }
        }

    }

    /**
     * 友盟推送调试
     */
    public function pushTest()
    {
        import('Vendor.umeng.Umeng');
//        $config = C('umeng');
//        $Umeng = new Umeng($config['AppKey'], $config['AppMasterSecret']);
//        var_dump($demo);exit;
        $Umeng = new Umeng('578d9a39e0f55afc1b002c84', 'e3ed4vese4pdrvbokvnpquk0dsbu0igx');

        $content = $this->param['content'] ?: '上全球体育玩竞彩足球';
        $options = [
            'ticker'    => $content,
            'title'     => $content,
            'text'      => '新版上线',
            'alias'     => $this->param['users'],
            'alias_type'=> 'QQTY',
            'after_open'=> 'go_custom',
            'custom'    => json_encode(['um_module' => ['module' => $this->param['module'], 'value' => $this->param['value'] ,'alias_type' => 'QQTY','alias' => explode(',',$this->param['users'])]]),
            'production_mode' => 'true'
        ];
        $res = $Umeng->sendAndroidCustomizedcast($options);
        var_dump($res);exit;
    }

    //发送环信消息
    public function sendText()
    {
        import('Vendor.Easemob.Easemob');
        $Easemob = new \Easemob(C('Easemob'));
        $module = $this->param['module'];                               //1：资讯  2：图集  9：外链
        $value = $this->param['value'] ?: 'http://m.qqty.com';              //type为1、2时，url值是对应频道id；为9时，url是外链链接
        $content = $this->param['content'] ?: '全球体育玩竞彩足球';     //推送标题
        $targets = explode(',', trim($this->param['users'], ','));  //多个用户逗号相隔，最多20个

        $ext = [
            'em_apns_ext' => [
                'em_push_title' => $content,
                'em_module' => ['module' => $module, 'value' => $value, 'url' => $value],
                'show_type' => $this->param['show_type']
            ]
        ];

        $info = $Easemob->sendText($from = "admin", $target_type = 'users', $target = $targets, $content, $ext);
        $this->ajaxReturn(['info' => $info]);
    }

    public function subTest()
    {
        $redis = connRedis();
        echo $redis->lpush(C('em') . 'user_gameball_push_list', $this->param['uid']); //竞猜的用户入列
        echo $redis->lpush(C('um') . 'user_gameball_push_list', $this->param['uid']); //竞猜的用户入列,友盟
    }

    /**
     * 获取关注的赛程
     */
    public function getFollowGameById()
    {

        $fbList = $this->_oRedis->smembers($this->_skeyPre . 'fb_user_follow:' . $this->param['name']) ?: [];
        $bkList = $this->_oRedis->smembers($this->_skeyPre . 'bk_user_follow:' . $this->param['name']) ?: [];

        $this->ajaxReturn(['fbList' => $fbList, 'bkList' => $bkList]);
    }

    /**
     * 获取赛程关注的用户
     */
    public function getGameFollow()
    {

        $fb = $this->_oRedis->smembers('push_fb_game_follow:' . $this->param['game_id']);
        $bk = $this->_oRedis->smembers('push_bk_game_follow:' . $this->param['game_id']);

        $this->ajaxReturn(['fbList' => $fb, 'bkList' => $bk]);
    }
}

?>