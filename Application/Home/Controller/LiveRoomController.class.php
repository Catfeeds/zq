<?php
/**
 * 前台用户中心公共控制器
 * 为防止多分组Controller名称冲突，公共Controller名称统一使用分组名称
 * @author dengweijun <406516482@qq.com>
 * @since  2015-11-27
 */
use Think\Controller;
use Think\Tool\Tool;
class LiveRoomController extends CommonController {

    public function index(){

        //获取后台配置文字公告
        if (!$AdCarousel = S('web_intro_AdCarousel')) {
            $AdCarousel = Tool::getAdList(118,1);
            S('web_intro_AdCarousel', json_encode($AdCarousel), 60);
        }
        $this->assign('AdCarousel', $AdCarousel[0]);


        $roomId = I('roomId');//房间ID
        if(isMobile()){
            redirect(U("/liveRoom/".$roomId.'@m'));
        }
        $roomInfo = M('LiveLog lg')->field('lg.id,lg.room_id,lg.title,lg.img,lg.start_time,lg.live_status,fu.id,fu.nick_name,fu.head,fu.descript')->join('LEFT JOIN qc_front_user fu ON fu.id = lg.user_id')->where(['lg.room_id'=>$roomId])->find();
        if($roomInfo){
            if($roomInfo['live_status'] > 0)
                $roomInfo['live_url'] = D('Live')->getLiveUrl($roomInfo['room_id'], $roomInfo['start_time']);
            else
                $roomInfo['live_url'] = D('Live')->CreateLiveStreamRecordIndexFiles($roomInfo['room_id'], $roomInfo['start_time'])['RecordUrl'];
            $roomInfo['img'] = (string)Tool::imagesReplace($roomInfo['img']);
            $roomInfo['mqtt_room_topic'] = 'qqty/live_' . $roomInfo['room_id'] . '/#';//mqtt room topic
            $roomInfo['head'] = frontUserFace($roomInfo['head']);
        }else{
            parent::_empty();
        }
        $this->assign('roomInfo',$roomInfo);
        //mqtt配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());
        $this->assign('ip', get_client_ip());
        $this->assign('userInfo', $this->getUserInfo());
    	$this->display();
    }

    public function getUserInfo(){
        //用户信息
        $uinfo = M('FrontUser')->field('id as user_id,username,nick_name,head,lv,lv_bet,lv_bk,coin,unable_coin,status')->where(['id' => is_login()])->find();
        if ($uinfo) {
            $uinfo['head'] = frontUserFace($uinfo['head']);

            //状态是否被禁用
            if ($uinfo['status'] != 1)
                $userStatus = -1;//您的账号被管理员屏蔽了

            //判断是否被屏蔽、踢出
            $forbid = M('ChatForbid')->where(['user_id' => $uinfo['user_id'], 'status' => ['IN', [1, 3]]])->order('id DESC')->find();
            if ($forbid) {
                if ($forbid['type'] == 1) {
                    $userStatus = -2;//您被管理员屏蔽了聊天功能
                } else if ($forbid['type'] == 3) {
                    if (NOW_TIME < $forbid['operate_time'] + 600) {
                        $userStatus = -3;//您已被管理员限时禁言
                    }
                } else if ($forbid['type'] == 2) {
                    if ($forbid['status'] == 1) {
                        $userStatus = -2;
                    } else {
                        if (NOW_TIME < $forbid['operate_time'] + 600) {
                            $userStatus = -3;//您已被管理员限时禁言
                        }
                    }
                }
            }
            $uinfo['userStatus'] = $userStatus;
        }
        return $uinfo ? json_encode($uinfo) : '';
    }

    public function offline(){
        $user_id = I('userId');
        if(isMobile()){
            redirect(U("/offLine/".$user_id.'@m'));
        }
        //查询是否在直播
        $is_live = M('LiveLog')->where(['user_id'=>$user_id,'live_status'=>['in',[1,2]],'status'=>1])->order('add_time desc')->find();
        if($is_live){
            if($is_live['game_id'] > 0)
                redirect(U("/live/".$is_live['game_id'].'@bf'));
            else
                redirect(U("/liveRoom/".$is_live['room_id']));
        }

        $roomInfo = M('LiveUser lu')->field('lu.id,lu.user_id,lu.live_status,fu.nick_name,fu.head,fu.descript')->join('LEFT JOIN qc_front_user fu ON fu.id = lu.user_id')->where(['lu.user_id'=>$user_id,'lu.status'=>1])->find();
        if($roomInfo){
            $roomInfo['live_url'] = D('Live')->getLiveUrl($roomInfo['room_id'], $roomInfo['start_time']);
            $roomInfo['img'] = (string)Tool::imagesReplace($roomInfo['img']);
            $roomInfo['mqtt_room_topic'] = 'qqty/live_' . $roomInfo['room_id'] . '/#';//mqtt room topic
            $roomInfo['head'] = frontUserFace($roomInfo['head']);
        }else{
            parent::_empty();
        }
        $liveList = $this->offLinePage();
        $liveList = array_chunk($liveList,2);
        $this->assign('liveList',$liveList);
        $this->assign('roomInfo',$roomInfo);
        $this->assign('userInfo', $this->getUserInfo());
        $this->display();
    }

    /**
     * 黄宗隆写的
     * 主播离线页面
     */
    public function offLinePage($prefixUrl = ''){
        //获取当前正在直播的记录
        $livingArr = M('liveLog')
            ->alias('Lg')
            ->field('Lg.user_id, LU.unique_id, Lg.live_status, Lg.title, Lg.room_id,  Lg.start_time, Lg.game_id, Lg.img, LU.user_id, U.nick_name')
            ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
            ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
            ->where(['Lg.status' => 1, 'LU.status' => 1, 'Lg.live_status' => ['IN', [1, 2]]])
            ->order('Lg.start_time DESC, Lg.add_time DESC')
            ->limit(1000)
            ->select();

        $lives = $livingArr ?:[];

        if(count($livingArr) < 10){//直播小于10，则取直播+回访+离线共10条记录,每个主播只取4条
            //获取回放，按照用户ID分组
            $liveUser = M('liveLog')
                ->alias('Lg')
                ->field('GROUP_CONCAT(Lg.id) as log_ids')
                ->where(['Lg.status' => 1,'Lg.live_status' => 0,'Lg.live_time'=>['gt',0]])
                ->group('Lg.user_id')
                ->order('Lg.start_time DESC, Lg.add_time DESC')
                ->limit(10)
                ->select();

            $liveIDs = [];
            foreach($liveUser as $k => $v){
                $tempIds = explode(',', $v['log_ids']);
                rsort($tempIds);
                $liveIDs = array_merge(array_slice($tempIds, 0, 3), $liveIDs);
            }

            $liveOverArr = [];
            if($liveIDs){
                //获取回放记录
                $liveOverArr = M('liveLog')
                    ->alias('Lg')
                    ->field('Lg.user_id, LU.unique_id, Lg.live_status, Lg.title, Lg.replay_url, Lg.room_id,  Lg.start_time, Lg.game_id, Lg.img, LU.user_id, U.nick_name')
                    ->join('LEFT JOIN qc_live_user LU ON LU.user_id = Lg.user_id')
                    ->join('LEFT JOIN qc_front_user U ON U.id = Lg.user_id')
                    ->where(['Lg.id' => ['IN', $liveIDs]])
                    ->order('Lg.start_time DESC, Lg.add_time DESC')
                    ->select();
            }

            $liveOverArr = array_slice($liveOverArr, 0, (10 - count($livingArr)));
            $lives = array_merge($lives, $liveOverArr);

            //获取离线主播
            $templiveOffLine = M('LiveUser')
                ->alias('LU')
                ->field('LU.user_id, LU.unique_id, LU.img, LU.live_desc, LU.live_status, LU.live_desc, U.nick_name,U.descript')
                ->where(['LU.live_status' => 0])
                ->join('LEFT JOIN qc_front_user U ON U.id = LU.user_id')
                ->order('LU.live_status DESC')
                ->select();

            $liveOffLine = [];
            foreach($templiveOffLine as $k => $v){
                $liveOffLine[$k]['live_status'] = '-1';
                $liveOffLine[$k]['user_id'] = $v['user_id'];
                $liveOffLine[$k]['unique_id'] = $v['unique_id'];
                $liveOffLine[$k]['title'] = $v['descript'];
                $liveOffLine[$k]['img'] = $v['img'] ? (string)Tool::imagesReplace($v['img']) : '';;
                $liveOffLine[$k]['nick_name'] = $v['nick_name'];
                $liveOffLine[$k]['live_desc'] = (string)$v['live_desc'];
            }
            $lives = array_merge($lives, $liveOffLine);
            $lives = array_slice($lives, 0, 10);
        }

        foreach($lives as $k => $v){
            $v['img'] = $v['img'] ? (string)Tool::imagesReplace($v['img']) : '';
            $v['game_id'] = (string)$v['game_id'];
            if($v['live_status'] != '-1'){
                if(in_array($v['live_status'], [1, 2])){
                    $v['live_url'] = D('Live')->getLiveUrl($v['room_id'], $v['start_time']);
                }else{
                    //D('Live')->CreateLiveStreamRecordIndexFiles($v['room_id'], $v['start_time'])['RecordUrl']
                    $v['live_url'] = $v['replay_url'] != '' ? $v['replay_url'] : '';
                }
            }
            if($v['live_status'] > 0){
                if($v['game_id'] > 0 && !$prefixUrl)
                    $url = U('/live/'.$v['game_id'].'@bf').'?is_live=1';
                else
                    $url = U('/liveRoom/'.$v['room_id'].$prefixUrl);
            }elseif($v['live_status'] < 0){
                $url = U('/offLine/'.$v['user_id'].$prefixUrl);
            }else{
                $url = U('/liveRoom/'.$v['room_id'].$prefixUrl);
            }
            $v['RoomUrl'] = $url;
            $lives[$k] = $v;
        }

        return $lives ?:[];
    }

    //直播推流地址回调
    public function liveNotifyUrl(){
//        $json = '{"action":"publish_done","ip":"183.3.152.226","id":"StreamName2151535945405-201809031130","app":"stream.qqty.com","appname":"live_test","time":"1535945827","usrargs":"vhost=stream.qqty.com&amp;auth_key=1535949005-0-0-07756e8db2b5e0029a196293e56e09bd&amp;alilive_streamidv2=p011133174008.et15_19742_432551817_1535945728816","node":"et15"}';
        $data = array_merge(I('get.'),I('post.'));
        $room_id = explode('-',explode('Name',$data['id'])[1])[0];
        //查询该房间号
        $roomInfo = M('LiveLog')->field('id,live_status,user_id')->where(['room_id'=>$room_id,'live_status'=>['gt',0]])->find();
        if(!$roomInfo) $this->ajaxReturn(['code'=>200,'msg'=>'ok!']);
        if($data['action'] == 'publish'){
            $saveData['live_status'] = 1;
        }elseif($data['action'] == 'publish_done'){
            $saveData['live_status'] = 2;
            $saveData['stop_time'] = time();
        }
        //判断当前状态是否需要修改
        if((int)$roomInfo['live_status'] == $saveData['live_status']) $this->ajaxReturn(['code'=>200,'msg'=>'Cooooooooool!']);
        //修改直播状态
        $userInfo = M('LiveUser')->where(['user_id'=>$roomInfo['user_id']])->find();
        M('LiveUser')->where(['id'=>$userInfo['id']])->save(['live_status'=>$saveData['live_status']]);
        $res = M('LiveLog')->where(['id'=>$roomInfo['id']])->save($saveData);
        //修改成功进行推送操作
        if($res){
            //推送直播状态
            if($saveData['live_status'] == 1){
                sleep(10);
                $msg = ['notice_str'=>"主播直播进行中"];
                $action = 'liveContinue';
            }else{
                $msg = ['notice_str'=>"主播暂停直播中"];
                $action = 'livePause';
            }
            $this->liveHandle($msg,$action,$room_id);
        }

    }

    //主播操作時進行推送
    public function liveHandle($msg,$action,$id,$topic = ''){
        //mqtt
        $say['action'] = $action;
        $say['data'] = $msg;
        $say['status'] = 1;
        $say["dataType"] = "text";
        if($topic == '') $topic = 'qqty/live_' . $id . '/chat';
        $options = [
            'topic' => $topic,
            'payload' => $say,
            'clientid' => md5(time() . $id),
        ];
        mqttPub($options);//mqtt推送
    }

    //设置阿里云直播回调地址
    public function SetLiveStreamsNotifyUrl(){
        $url = C('LIVE')['Notify'];
        $res = D('live')->SetLiveStreamsNotifyUrlConfig($url);
    }

    //定时任务修改当前主播状态
    public function liveTask(){
        $log = M('LiveLog')->where(['live_status'=>2,'status'=>1])->select();
        $tmp = [];
        if($log){
            foreach($log as $val){
                if($val['stop_time']<(time()-20*60)){
                    $res = D('Live')->CreateLiveStreamRecordIndexFiles($val['room_id'], $val['start_time']);
                    if($res){
                        $data['replay_url'] = $res['RecordUrl'];
                        $Duration = ceil($res['Duration'] / 60);
                        $data['live_time'] = $Duration;
                    }else{
                        $data['live_time'] = 0;
                        $data['status'] = 0;
                    }
                    $data['live_status'] = 0;
                    $data['end_time'] = time();
                    M('LiveLog')->where(['id'=>$val['id']])->save($data);
                    $tmp[] = $val['id'];
                }
            }
        }
        $this->ajaxReturn($tmp);
    }

}