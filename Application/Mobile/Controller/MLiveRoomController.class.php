<?php
/**
 * 首页
 *
 * @author liuwt <liuwt@qqty.com>
 *
 * @since  2018-08-14
 */
use Think\Tool\Tool;
class MLiveRoomController extends CommonController {
    public function index() {
        $roomId = I('roomId');//房间ID
        if(!isMobile()){
            redirect(U("/liveRoom/".$roomId.'@www'));
        }
        $roomInfo = M('LiveLog lg')->field('lg.id,lg.room_id,lg.title,lg.img,lg.start_time,lg.live_status,fu.id,fu.nick_name,fu.head')->join('LEFT JOIN qc_front_user fu ON fu.id = lg.user_id')->where(['lg.room_id'=>$roomId])->find();
        if($roomInfo){
            if($roomInfo['live_status'] > 0)
                $roomInfo['live_url'] = D('Live')->getLiveUrl($roomInfo['room_id'], $roomInfo['start_time']);
            else
                $roomInfo['live_url'] = D('Live')->CreateLiveStreamRecordIndexFiles($roomInfo['room_id'], $roomInfo['start_time'])['RecordUrl'];
            $roomInfo['img'] = (string)Tool::imagesReplace($roomInfo['img']);
            $roomInfo['mqtt_room_topic'] = 'qqty/live_' . $roomInfo['room_id'] . '/#';//mqtt room topic
            $roomInfo['head'] = frontUserFace($roomInfo['head']);
        }
        $this->assign('roomInfo',$roomInfo);
        //mqtt配置
        $mqtt = C('Mqtt');
        $this->assign('mqttOpt', $mqtt);
        $this->assign('mqttUser', setMqttUser());
        $this->display();
    }

    public function offLine(){
        $user_id = I('userId');

        if(!isMobile()){
            redirect(U("/offLine/".$user_id.'@www'));
        }
        //查询是否在直播
        $is_live = M('LiveLog')->where(['user_id'=>$user_id,'status'=>1])->order('add_time desc')->find();
        if($is_live) redirect(U("/liveRoom/".$is_live['room_id'].'@m'));

        $roomInfo = M('LiveUser lu')->field('lu.id,lu.user_id,lu.live_status,fu.nick_name,fu.head,fu.descript')->join('LEFT JOIN qc_front_user fu ON fu.id = lu.user_id')->where(['lu.user_id'=>$user_id,'lu.status'=>1])->find();
        if($roomInfo){
            $roomInfo['live_url'] = D('Live')->getLiveUrl($roomInfo['room_id'], $roomInfo['start_time']);
            $roomInfo['img'] = (string)Tool::imagesReplace($roomInfo['img']);
            $roomInfo['mqtt_room_topic'] = 'qqty/live_' . $roomInfo['room_id'] . '/#';//mqtt room topic
            $roomInfo['head'] = frontUserFace($roomInfo['head']);
        }else{
            parent::_empty();
        }
        $this->assign('roomInfo',$roomInfo);
        //美女直播列表
        $liveList = A('Home/LiveRoom')->offLinePage('@m');
        $this->assign('liveList',$liveList);
        $this->display();
    }

    //ajax获取美女聊天室历史消息
    public function getLiveHistoryChat(){
        $roomTopic = I('room_id')[0];
        if(is_numeric($roomTopic))
            $room_id = $roomTopic;
        else
            $room_id = explode('_',explode('/',$roomTopic)[1])[1];
        $data = [];
        if($room_id){
            $dataService = new \Common\Services\DataService();
            $chatRecord = $dataService->chatRecord('live_'.$room_id);
            if($chatRecord){
                $tmp = [];
                foreach($chatRecord as $v){
                    $tmp[] = json_encode([
                        'action'=>'say',
                        'dataType'=>'text',
                        'status'=>1,
                        'data'=>$v
                    ]);
                }
                $data = ['code'=>200,'data'=>$tmp];
            }else{
                $data = ['code'=>404];
            }
        }else{
            $data = ['code'=>404];
        }

        $this->ajaxReturn($data);
    }
    
}