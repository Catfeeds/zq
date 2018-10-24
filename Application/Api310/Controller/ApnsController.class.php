<?php

/**
 * Apns推送相关
 * @author hunagzl <496331832@qq.com> 2017.02.10
 */
class ApnsController extends PublicController
{
    /**
     * 将设备标识和用户id进行绑定
     */
    public function binding(){
        $info = getUserToken($this->param['userToken']);

        if(!isset($this->param['deviceID']) || !isset($this->param['platform']))
            $this->ajaxReturn(101);

        $res1 = M('ApnsUsers')->where(['device_token' => $this->param['deviceID']])->find();

        if(!$res1){
            $add_data = [
                'device_token'  => $this->param['deviceID'],
                'platform'      => $this->param['platform'],
                'create_time'   => NOW_TIME
            ];
            $add_res = M('ApnsUsers')->add($add_data);

            if(!$add_res)
                $this->ajaxReturn(3012);

            $res1 = array_merge($add_data, ['id' => $add_res, 'is_push' => '1']);
        }
        $save_data['login_time'] = NOW_TIME;

        if(isset($info['userid'])){
            $redis = connRedis();
            $res2 = M('ApnsUsers')->where(['user_id' => $info['userid']])->find();
            if(!$res2){
                $save_data['user_id'] = $info['userid'];
            }else{
                if($res1['id'] != $res2['id']){
                    $save_data['user_id'] = $info['userid'];

                    //解除之前的关系
                    $save_res = M('ApnsUsers')->where(['id' => $res2['id']])->setField('user_id', null);
                    if(!$save_res)
                        $this->ajaxReturn(3012);

                    //将用户在别的设备关注的赛事添加到当前的设备，接收推送
                    $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
                    $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
                    $user_fb_follow     = $redis->sMembers($fbUserFollowKey);
                    $user_bk_follow     = $redis->sMembers($bkUserFollowKey);

                    foreach($user_fb_follow as $k => $gid){
                        $fbGameFollowKey    = 'push_apns_game_fb_follow:' . $gid;
                        $redis->sadd($fbGameFollowKey, $this->param['deviceID']);
                    }

                    foreach($user_bk_follow as $k2 => $gid2){
                        $bkGameFollowKey    = 'push_apns_game_bk_follow:' . $gid2;
                        $redis->sadd($bkGameFollowKey, $this->param['deviceID']);
                    }
                }
            }
        }

        $save_res = M('ApnsUsers')->where(['id' => $res1['id']])->save($save_data);
        if(!$save_res)
            $this->ajaxReturn(3012);

        $this->ajaxReturn(['result' => '1', 'is_push' => $res1['is_push']]);
    }

    /**
     * 关注赛程;登录时，依然可以收到未登陆时设备关注的赛程推送
     * 用户没登录时用环信device_token做标识
     */
    public function followGame()
    {
        if (!$this->param['gameId'] || !$this->param['gameType'] || !$this->param['playType'])
            $this->ajaxReturn(101);

        if(!$this->param['HxDeviceID'] && !$this->param['deviceID'])
            $this->ajaxReturn(101);

        //设置键名
        $info = getUserToken($this->param['userToken']);

        //Fb key
        $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
        $fbDeviceFollowKey  = 'push_apns_user_fb_follow:' . $this->param['deviceID'];
        $fbHxDeviceFollowKey  = 'push_hx_user_fb_follow:' . $this->param['HxDeviceID'];
        $fbGameFollowKey    = 'push_apns_game_fb_follow:' . $this->param['gameId'];

        //Bk key
        $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
        $bkDeviceFollowKey  = 'push_apns_user_bk_follow:' . $this->param['deviceID'];
        $bkHxDeviceFollowKey  = 'push_hx_user_bk_follow:' . $this->param['HxDeviceID'];
        $bkGameFollowKey    = 'push_apns_game_bk_follow:' . $this->param['gameId'];

        switch($this->param['gameType']){
            case 1://足球
                $userFollowKey = $fbUserFollowKey;
                $deviceFollowKey = $fbDeviceFollowKey;
                $hxDeviceFollowKey = $fbHxDeviceFollowKey;
                $gameFollowKey = $fbGameFollowKey;
                break;

            case 2://篮球
                $userFollowKey = $bkUserFollowKey;
                $gameFollowKey = $bkGameFollowKey;
                $hxDeviceFollowKey = $bkHxDeviceFollowKey;
                $deviceFollowKey = $bkDeviceFollowKey;
                break;
        }
        $redis = connRedis();

        //用户登录
        if(isset($info['userid'])){
            $redis->sadd($userFollowKey, $this->param['gameId']);
            $redis->hset('hash_' . $userFollowKey, $this->param['gameId'], $this->param['playType']);
            $redis->expire($userFollowKey, 3600 * 24);
            $redis->expire('hash_' . $userFollowKey, 3600 * 24);
        }

        //APNS设备号
        if($this->param['deviceID'] != ''){
            $redis->sadd($deviceFollowKey, $this->param['gameId']);
            $redis->sadd($gameFollowKey, $this->param['deviceID']);
            //新增赛程、玩法对应关系
            $redis->hset('hash_' . $deviceFollowKey, $this->param['gameId'], $this->param['playType']);
            $redis->expire($deviceFollowKey, 3600 * 24);
            $redis->expire('hash_' . $deviceFollowKey, 3600 * 24);
        }

        //环信设备号
        if($this->param['HxDeviceID'] != ''){
            $redis->sadd($hxDeviceFollowKey, $this->param['gameId']);
            //新增赛程、玩法对应关系
            $redis->hset('hash_' . $hxDeviceFollowKey, $this->param['gameId'], $this->param['playType']);
            $redis->expire($hxDeviceFollowKey, 3600 * 24);
            $redis->expire('hash_' . $hxDeviceFollowKey, 3600 * 24);
        }

        $this->ajaxReturn(['gameId' => $this->param['gameId']]);
    }

    /**
     * 取消关注赛程
     */
    public function cancelFollowGame()
    {
        if (!$this->param['gameId'] || !$this->param['gameType'])
            $this->ajaxReturn(101);

        if(!$this->param['HxDeviceID'] && !$this->param['deviceID'])
            $this->ajaxReturn(101);

        //设置键名
        $info = getUserToken($this->param['userToken']);

        //Fb key
        $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
        $fbDeviceFollowKey  = 'push_apns_user_fb_follow:' . $this->param['deviceID'];
        $fbHxDeviceFollowKey  = 'push_hx_user_fb_follow:' . $this->param['HxDeviceID'];
        $fbGameFollowKey    = 'push_apns_game_fb_follow:' . $this->param['gameId'];

        //Bk key
        $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
        $bkDeviceFollowKey  = 'push_apns_user_bk_follow:' . $this->param['deviceID'];
        $bkHxDeviceFollowKey  = 'push_hx_user_bk_follow:' . $this->param['HxDeviceID'];
        $bkGameFollowKey    = 'push_apns_game_bk_follow:' . $this->param['gameId'];

        switch($this->param['gameType']){
            case 1://足球
                $userFollowKey = $fbUserFollowKey;
                $gameFollowKey = $fbGameFollowKey;
                $deviceFollowKey = $fbDeviceFollowKey;
                $hxDeviceFollowKey = $fbHxDeviceFollowKey;
                break;

            case 2://篮球
                $userFollowKey = $bkUserFollowKey;
                $gameFollowKey = $bkGameFollowKey;
                $deviceFollowKey = $bkDeviceFollowKey;
                $hxDeviceFollowKey = $bkHxDeviceFollowKey;
                break;
        }
        $redis = connRedis();

        $redis->srem($userFollowKey, $this->param['gameId']);
        $redis->srem($gameFollowKey, $this->param['deviceID']);
        $redis->srem($hxDeviceFollowKey, $this->param['HxDeviceID']);
        $redis->srem($deviceFollowKey, $this->param['gameId']);

        //取消赛程、玩法对应关系
        $redis->hDel('hash_' . $userFollowKey, $this->param['gameId']);
        $redis->hDel('hash_' . $hxDeviceFollowKey, $this->param['gameId']);

        //用户登录时，取消关注同时也取消本机关注的对应赛事
        if (isset($info['userid'])) {
            $redis->srem($deviceFollowKey, $this->param['gameId']);
            $redis->hDel('hash_' . $deviceFollowKey, $this->param['gameId']);
        }

        $this->ajaxReturn(['gameId' => $this->param['gameId']]);
    }

    /**
     * 获取关注的赛程
     */
    public function myFollowGame()
    {
        if (!$this->param['deviceID'] && !$this->param['HxDeviceID'])
            $this->ajaxReturn(101);

        //设置键名
        $info = getUserToken($this->param['userToken']);

        //Fb key
        $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
        $fbDeviceFollowKey  = 'push_apns_user_fb_follow:' . $this->param['deviceID'];
        $fbHxDeviceFollowKey  = 'push_hx_user_fb_follow:' . $this->param['HxDeviceID'];

        //Bk key
        $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
        $bkDeviceFollowKey  = 'push_apns_user_bk_follow:' . $this->param['deviceID'];
        $bkHxDeviceFollowKey  = 'push_hx_user_bk_follow:' . $this->param['HxDeviceID'];

        $redis = connRedis();

        //把设备关注和用户关注的赛事都返回
        if(isset($info['userid']) || $this->param['deviceID'] !='' ){
            $fbDeviceRes = $redis->hGetAll('hash_' . $fbDeviceFollowKey);
            $res1        = $redis->hMset('hash_' . $fbUserFollowKey, $fbDeviceRes);
            $fbRes       = $redis->hGetAll('hash_' . $fbUserFollowKey);

            $bkDeviceRes = $redis->hGetAll('hash_' . $bkDeviceFollowKey);
            $res2        = $redis->hMset('hash_' . $bkUserFollowKey, $bkDeviceRes);
            $bkRes       = $redis->hGetAll('hash_' . $bkUserFollowKey);
        }else{
            $fbRes       = $redis->hGetAll('hash_' . $fbHxDeviceFollowKey);
            $bkRes       = $redis->hGetAll('hash_' . $bkHxDeviceFollowKey);
        }

        foreach($fbRes as $fk => $fv){
            $fbList[] = ['game_id' => (string)$fk, 'play_type' => $fv];
        }

        foreach($bkRes as $fk => $fv){
            $bkList[] = ['game_id' => (string)$fk, 'play_type' => $fv];
        }

        $this->ajaxReturn(['fbList' => $fbList ?:[], 'bkList' => $bkList ?:[]]);
    }

    /**
     * 测试apns推送
     */
    public function push_test(){
        //apns
        import('Vendor.apns.ApnsPush');
        $apns = new ApnsPush(C('apns_env'), 'qqty888');
        $apns->connect();

        //推送消息体
        $payload = ['aps' => [
            'alert' => ["body" => I('content')]],
            'e' =>[
                'em_module' => ['module' => I('module'), 'value' => I('value'), 'url' => I('module')],
                'show_type' => 1,
                'msg_id'    => I('msg_id')?I('msg_id'):1
            ]

        ];
        echo '数据格式：'.json_encode($payload);
        echo '<br/>';
        $apns->setBody($payload);
        $res = $apns->send(I('device_token'), I('msg_id')?I('msg_id'):1);
        echo $res?'success':'fail, retry';
        sleep(1);
        $err = $apns->readErrMsg();
        var_dump($err);
        $apns->close();
    }

    /**
     * 获取无效的token，只返回一次之前推送无效的token，再次请求有可能为空
     */
    public function feedback(){
        //apns
        import('Vendor.apns.ApnsPush');
        $apns = new ApnsPush(C('apns_env'), 'qqty888');
        $apns->apns_urls = ['tls://feedback.push.apple.com:2196', 'tls://feedback.sandbox.push.apple.com:2196'];

        $apns->connect();
        print_r($apns->feedback());

        $apns->close();
    }
}

?>