<?php

/**
 * Apns推送相关
 * @author hunagzl <496331832@qq.com> 2017.02.10
 */
class ApnsController extends PublicController
{
    private  $oRedis;
    public function _initialize(){
        parent::_initialize();

        //重新登录的设备号，是否之前因为卸载app已被标记成无效token，需要从无效集合中移除
        $this->oRedis = connRedis();
        $this->oRedis->sRem('apns_invalid_token_lists_' . C('apns_env'), $this->param['pushID']);
        $this->oRedis->sRem('apns_invalid_token_lists_' . C('apns_env') . $this->param['certNo'], $this->param['pushID']);

    }

    /**
     * 将设备标识和用户id进行绑定
     */
    public function binding(){
        $info = getUserToken($this->param['userToken']);
        $certNo = $this->param['certNo'] ? $this->param['certNo'] : 0;

        if(!isset($this->param['pushID']) || !isset($this->param['platform']))
            $this->ajaxReturn(101);

        $res1 = M('ApnsUsers')->master(true)->where(['device_token' => $this->param['pushID']])->find();

        if(!$res1){
            $add_data = [
                'device_token'  => $this->param['pushID'],
                'platform'      => $this->param['platform'],
                'device_id'     => $this->param['deviceID'],
                'cert_no'       => $certNo,
                'create_time'   => NOW_TIME
            ];
            $add_res = M('ApnsUsers')->add($add_data);

            if(!$add_res)
                $this->ajaxReturn(3012);

            $res1 = array_merge($add_data, ['id' => $add_res, 'is_push' => '1']);
        }

        $save_data['login_time'] = NOW_TIME;
        $save_data['cert_no'] = $certNo;
        $save_data['device_id'] = $this->param['deviceID'];

        if(isset($info['userid'])){
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
                }
            }
        }

        //查询IOS用户信息表，是否是推送黑名单
        if($this->param['deviceID']){
            $appplog = M('AppLog')->where(['device_id' => $this->param['deviceID']])->find();
            if($appplog){
                $save_data['status'] = (int)$appplog['push_status'];
            }
        }

        if(isset($info['userid'])){
            $appplog = M('AppLog')->where(['user_id' => $info['userid']])->getField('push_status');
            if($appplog){
                $save_data['status'] = $save_data['status'] == 0 ? 0 : (int)$appplog['push_status'];
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

        if(!$this->param['deviceID'] && !$this->param['pushID'])
            $this->ajaxReturn(101);

        $info = getUserToken($this->param['userToken']);

        //Fb key
        $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
        $fbDeviceFollowKey  = 'push_apns_user_fb_follow:' . $this->param['pushID'];
        $fbHxDeviceFollowKey= 'push_hx_user_fb_follow:' . $this->param['deviceID'];
        $fbGameFollowKey    = 'push_apns_game_fb_follow:' . $this->param['gameId'];

        //Bk key
        $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
        $bkDeviceFollowKey  = 'push_apns_user_bk_follow:' . $this->param['pushID'];
        $bkHxDeviceFollowKey= 'push_hx_user_bk_follow:' . $this->param['deviceID'];
        $bkGameFollowKey    = 'push_apns_game_bk_follow:' . $this->param['gameId'];

        $_preKey            = '';
        switch($this->param['gameType']){
            case 1://足球
                $userFollowKey      = $fbUserFollowKey;
                $deviceFollowKey    = $fbDeviceFollowKey;
                $hxDeviceFollowKey  = $fbHxDeviceFollowKey;
                $gameFollowKey      = $fbGameFollowKey;
                $gameFollowKey2     = 'push_fb_game_follow:' . $this->param['gameId'];
                $_preKey            = 'push_fb_user_follow_';
                break;

            case 2://篮球
                $userFollowKey      = $bkUserFollowKey;
                $gameFollowKey      = $bkGameFollowKey;
                $gameFollowKey2     = 'push_bk_game_follow:' . $this->param['gameId'];
                $hxDeviceFollowKey  = $bkHxDeviceFollowKey;
                $deviceFollowKey    = $bkDeviceFollowKey;
                $_preKey            = 'push_bk_user_follow_';
                break;
        }

        $cert_no = M('ApnsUsers')->master()->where(['device_token' => $this->param['pushID']])->getField('cert_no');

        //用户登录
        $_preKey .= $this->param['gameType'] . ':';
        if(isset($info['userid'])){
            $this->oRedis->sadd( $_preKey . $info['userid'], $this->param['gameId']);
            $this->oRedis->sadd($userFollowKey, $this->param['gameId']);
//            if($cert_no){//副包
//                $this->oRedis->sadd($gameFollowKey2, $info['userid']);
//                $this->oRedis->expire($gameFollowKey2, 3600 * 24);
//            }
            $this->oRedis->hset('hash_' . $userFollowKey, $this->param['gameId'], $this->param['playType']);
            $this->oRedis->expire($userFollowKey, 3600 * 24);
            $this->oRedis->expire($_preKey . $info['userid'], 3600 * 24);
            $this->oRedis->expire('hash_' . $userFollowKey, 3600 * 24);
        }

        //APNS设备号
        if($this->param['pushID'] != '' && $cert_no){
            $this->oRedis->sadd($_preKey . $this->param['pushID'], $this->param['gameId']);
            $this->oRedis->sadd($deviceFollowKey, $this->param['gameId']);
            $this->oRedis->sadd($gameFollowKey, $this->param['pushID']);
            //新增赛程、玩法对应关系
            $this->oRedis->hset('hash_' . $deviceFollowKey, $this->param['gameId'], $this->param['playType']);
            $this->oRedis->expire($deviceFollowKey, 3600 * 24);
            $this->oRedis->expire($_preKey . $this->param['pushID'], 3600 * 24);
            $this->oRedis->expire('hash_' . $deviceFollowKey, 3600 * 24);
        }

        //环信设备号
        if($this->param['deviceID'] != ''){
            $this->oRedis->sadd($_preKey . $this->param['deviceID'], $this->param['gameId']);
            $this->oRedis->sadd($hxDeviceFollowKey, $this->param['gameId']);
//            if($cert_no && $cert_no){//副包
//                $this->oRedis->sadd($gameFollowKey2, $this->param['deviceID']);
//                $this->oRedis->expire($gameFollowKey2, 3600 * 24);
//            }

            //新增赛程、玩法对应关系
            $this->oRedis->hset('hash_' . $hxDeviceFollowKey, $this->param['gameId'], $this->param['playType']);
            $this->oRedis->expire($hxDeviceFollowKey, 3600 * 24);
            $this->oRedis->expire($_preKey . $this->param['deviceID'], 3600 * 24);
            $this->oRedis->expire('hash_' . $hxDeviceFollowKey, 3600 * 24);
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

        if(!$this->param['deviceID'] && !$this->param['pushID'])
            $this->ajaxReturn(101);

        //设置键名
        $info = getUserToken($this->param['userToken']);

        //Fb key
        $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
        $fbDeviceFollowKey  = 'push_apns_user_fb_follow:' . $this->param['pushID'];
        $fbHxDeviceFollowKey= 'push_hx_user_fb_follow:' . $this->param['deviceID'];
        $fbGameFollowKey    = 'push_apns_game_fb_follow:' . $this->param['gameId'];

        //Bk key
        $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
        $bkDeviceFollowKey  = 'push_apns_user_bk_follow:' . $this->param['pushID'];
        $bkHxDeviceFollowKey= 'push_hx_user_bk_follow:' . $this->param['deviceID'];
        $bkGameFollowKey    = 'push_apns_game_bk_follow:' . $this->param['gameId'];

        switch($this->param['gameType']){
            case 1://足球
                $userFollowKey = $fbUserFollowKey;
                $gameFollowKey = $fbGameFollowKey;
                $deviceFollowKey = $fbDeviceFollowKey;
                $hxDeviceFollowKey = $fbHxDeviceFollowKey;
                $_preKey = 'push_fb_user_follow_' . $this->param['gameType'] . ':';
                break;

            case 2://篮球
                $userFollowKey = $bkUserFollowKey;
                $gameFollowKey = $bkGameFollowKey;
                $deviceFollowKey = $bkDeviceFollowKey;
                $hxDeviceFollowKey = $bkHxDeviceFollowKey;
                $_preKey = 'push_bk_user_follow_' . $this->param['gameType'] . ':';
                break;
        }

        $this->oRedis->srem($userFollowKey, $this->param['gameId']);
        $this->oRedis->srem($gameFollowKey, $this->param['pushID']);
        $this->oRedis->srem($hxDeviceFollowKey, $this->param['deviceID']);
        $this->oRedis->srem($deviceFollowKey, $this->param['gameId']);


        $this->oRedis->srem($_preKey . $info['userid'], $this->param['gameId']);
        $this->oRedis->srem($_preKey . $this->param['pushID'], $this->param['gameId']);
        $this->oRedis->srem($_preKey . $this->param['deviceID'], $this->param['gameId']);

        //取消赛程、玩法对应关系
        $this->oRedis->hDel('hash_' . $userFollowKey, $this->param['gameId']);
        $this->oRedis->hDel('hash_' . $deviceFollowKey, $this->param['gameId']);
        $this->oRedis->hDel('hash_' . $hxDeviceFollowKey, $this->param['gameId']);

        //用户登录时，取消关注同时也取消本机关注的对应赛事
        if (isset($info['userid'])) {
            $this->oRedis->srem($deviceFollowKey, $this->param['gameId']);
            $this->oRedis->hDel('hash_' . $deviceFollowKey, $this->param['gameId']);
        }

        $this->ajaxReturn(['gameId' => $this->param['gameId']]);
    }

    /**
     * 获取关注的赛程
     */
    public function myFollowGame()
    {
        if (!$this->param['pushID'] && !$this->param['deviceID'])
            $this->ajaxReturn(101);

        //设置键名
        $info = getUserToken($this->param['userToken']);

        $cert_no = M('ApnsUsers')->where(['device_token' => $this->param['pushID']])->getField('cert_no');

        if(isset($info['userid'])){
            $fbKey    = 'hash_push_apns_user_fb_follow:' . $info['userid'];
            $bkKey    = 'hash_push_apns_user_bk_follow:' . $info['userid'];
        }elseif($this->param['pushID'] !='' && !$cert_no ){
            $fbKey    = 'hash_push_apns_user_fb_follow:' . $this->param['pushID'];
            $bkKey    = 'hash_push_apns_user_bk_follow:' . $this->param['pushID'];
        }elseif($this->param['deviceID'] != ''){
            $fbKey    = 'hash_push_hx_user_fb_follow:' . $this->param['deviceID'];
            $bkKey    = 'hash_push_hx_user_bk_follow:' . $this->param['deviceID'];
        }
        $fbRes  = $this->oRedis->hGetAll($fbKey);
        $bkRes  = $this->oRedis->hGetAll($bkKey);

        foreach($fbRes as $fk => $fv){
            $fbList[] = ['game_id' => (string)$fk, 'play_type' => $fv];
        }

        foreach($bkRes as $fk => $fv){
            $bkList[] = ['game_id' => (string)$fk, 'play_type' => $fv];
        }
//        //Fb key
//        $fbUserFollowKey    = 'push_apns_user_fb_follow:' . $info['userid'];
//        $fbDeviceFollowKey  = 'push_apns_user_fb_follow:' . $this->param['deviceID'];
//        $fbHxDeviceFollowKey  = 'push_hx_user_fb_follow:' . $this->param['deviceID'];
//
//        //Bk key
//        $bkUserFollowKey    = 'push_apns_user_bk_follow:' . $info['userid'];
//        $bkDeviceFollowKey  = 'push_apns_user_bk_follow:' . $this->param['deviceID'];
//        $bkHxDeviceFollowKey  = 'push_hx_user_bk_follow:' . $this->param['deviceID'];
//
//        //把设备关注和用户关注的赛事都返回
//        if(isset($info['userid']) || $this->param['deviceID'] !='' ){
//            $fbDeviceRes = $this->oRedis->hGetAll('hash_' . $fbDeviceFollowKey);
//            $res1        = $this->oRedis->hMset('hash_' . $fbUserFollowKey, $fbDeviceRes);
//            $fbRes       = $this->oRedis->hGetAll('hash_' . $fbUserFollowKey);
//
//            $bkDeviceRes = $this->oRedis->hGetAll('hash_' . $bkDeviceFollowKey);
//            $res2        = $this->oRedis->hMset('hash_' . $bkUserFollowKey, $bkDeviceRes);
//            $bkRes       = $this->oRedis->hGetAll('hash_' . $bkUserFollowKey);
//        }else{
//            $fbRes       = $this->oRedis->hGetAll('hash_' . $fbHxDeviceFollowKey);
//            $bkRes       = $this->oRedis->hGetAll('hash_' . $bkHxDeviceFollowKey);
//        }
//
//        foreach($fbRes as $fk => $fv){
//            $fbList[] = ['game_id' => (string)$fk, 'play_type' => $fv];
//        }
//
//        foreach($bkRes as $fk => $fv){
//            $bkList[] = ['game_id' => (string)$fk, 'play_type' => $fv];
//        }

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
                'em_module' => ['module' => I('module'), 'value' => I('value'), 'url' => I('value')],
                'show_type' => 1,
                'msg_id'    => I('msg_id')?I('msg_id'):1
            ]

        ];
        echo '数据格式：'.json_encode($payload);
        echo '<br/>';
        $apns->setBody($payload);
        $device_token = I('device_token')?I('device_token'):M('ApnsUsers')->where(['user_id' => I('user_id')])->getField('device_token');
        $res = $apns->send($device_token, 12313);
        dump($res);
        sleep(2);
        $err = $apns->readErrMsg();
        var_dump($err);
        $apns->close();
    }

    public function get_error(){
        //apns
        import('Vendor.apns.ApnsPush');
        $apns = new ApnsPush(C('apns_env'), 'qqty888');
        $apns->connect();
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
        $apns->apns_urls = ['ssl://feedback.push.apple.com:2196', 'ssl://feedback.sandbox.push.apple.com:2196'];

        $apns->connect();
        $lists  = $apns->feedback();

        $apns->close();
        print_r($lists);
        foreach($lists as $k => $v){
            //将无效的token保存，并且将用户绑定的改token移除
            $this->oRedis->sAdd('apns_invalid_token_lists_' . C('apns_env'), $v['deviceToken']);
            M('ApnsUsers')->where(['device_token' => $v['deviceToken']])->save(['device_token' => null]);
        }
        $this->oRedis->set('apns_invalid_token_lists_tmp_log', date('Y-m-d H:i:s', time()));
    }

    public function getInvalidToken(){
       $res = $this->oRedis->sMembers('apns_invalid_token_lists_' . C('apns_env'));
//       foreach($res as $k=>$v){
//           $this->oRedis->sMove('apns_invalid_token_lists_' . C('apns_env'), 'apns_invalid_token_lists_copy', $v);
//           //$this->oRedis->sAdd('apns_invalid_token_lists_copy', $v);
//       }
       $res2 = $this->oRedis->sMembers('apns_invalid_token_lists_copy');
        print_r($res);
        print_r($res2);
    }

    public function getApnsUser(){
        $ApnsUser = M('ApnsUsers')->where(['user_id' => $this->param['user_id']])->find();
        if($ApnsUser && $ApnsUser['cert_no']){
            $this->oRedis->rpush('apns_user_gameball_push_queue', json_encode(['device_token' => $ApnsUser['device_token'], 'content' => $this->param['content'] , 'cert_no' => $ApnsUser['cert_no']]));
        }
        $size = $this->oRedis->lSize('apns_user_gameball_push_queue');
        var_dump($ApnsUser,$size);
    }

}

?>