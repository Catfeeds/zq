<?php

/**
 * 任务和成就
 * Created by PhpStorm.
 * User: zhangwen
 * Date: 2016/6/21
 * Time: 14:34
 */
use Think\Tool\Tool;

class MissionController extends CommonController
{
    public $beginTime = '';
    public $endTime   = '';
    public $config    = '';

    public function _initialize(){
        parent::_initialize();
        $this->beginTime = strtotime(date('Y-m-d 00:00:00', time()));
        $this->endTime = strtotime(date('Y-m-d 23:59:59', time()));

        //获取配置信息
        $config = M('config')->where(['sign' => 'mission'])->getField('config');
        $this->config = json_decode($config, true);
    }


    public function index(){
        $configSign = array(
            'mission' => array(
                'publishGamble' => '参与竞猜',
                'buyGamble' => '购买竞猜',
                'shareGamble' => '分享竞猜',
                'shareNews' => '分享资讯',
                'replyNews' => '回复资讯',
                'publishArticle' => '发布帖子',
            ),
            'achievement' => array(
                'gambleNum' => '竞猜场次',
                'winNum' => '赢得竞猜场次',
                'getBuyNum' => '获得购买',
                'buyNum' => '购买竞猜数',
                'fansNum' => '粉丝数量',
                'publishComment' => '发布评论',
            )
        );
    }

    /**
     * 每日任务列表
     */
    public function dailyMission(){
        $userid = $this->userInfo['userid'];

        $missionStatus = array(
            'publishGamble' => 1,
            'buyGamble' => 2,
            'shareGamble' => 3,
            'shareNews' => 4,
            'replyNews' => 5,
            'publishArticle' => 6,
        );

        //今天是否签到
        $isSign = D('Mission')->isSign($userid, $this->beginTime, $this->endTime) ? 1 : 0;
        //昨天是否签到
        $yesisSign = D('Mission')->isSign($userid, strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))), strtotime(date('Y-m-d 23:59:59', strtotime('-1 day')))) ? 1 : 0;

        //今天签到不需要再检查
        if(!$isSign){
            //判断用户是否连续签到,不是就连续签到次数为0，签到时间为0（预防用户跨天登录）
            $sign_time = M('FrontUser')->where(['id'=>$userid])->getField('sign_time');
            //昨天不签到表示不是连续，判断依据：昨天没有签到，今天没有签到，签到时间不为0，昨天和今天的昨天不是同一天
            if($yesisSign == 0 && $sign_time != 0 && strtotime(date('Ymd', $sign_time)) != strtotime(date('Ymd',  strtotime('-1 day')))){
                M('FrontUser')->where(['id' => $userid])->save(['sign_num' => 0, 'sign_time' => 0]);
            }
        }

        $data = M('Mission')->field('id, name, points, sign, num')->where(['status'=>1, 'type'=>1])->order('sort Asc')->limit(6)->select();
        foreach((array)$data as $k => $v){
            $isDone = D('Mission')->getMission($userid, $v['sign'], $v['num'], $this->beginTime, $this->endTime);
            $isGet = D('Mission')->checkIsGet($userid, $v['id'], 1, $this->beginTime, $this->endTime);
            if($isGet == 1){//已领取
                $data[$k]['status'] = '3';
            }else if($isGet == 0 && $isDone == 1){//已完成，未领取
                $data[$k]['status'] = '2';
            }else{//未完成
                $data[$k]['status'] = '1';
            }
            unset($data[$k]['sign'], $data[$k]['num']);
            $data[$k]['mStatus'] = (string)$missionStatus[$v['sign']];
        }
        $res['data'] = $data;
        $res['sign_num'] = M('FrontUser')->master(true)->where(['id'=>$userid])->getField('sign_num');//连续签到的天数
        $res['isSign'] = D('Mission')->isSign($userid, $this->beginTime, $this->endTime) ? '1' : '0';

        $this->ajaxReturn(['dailyMission'=>$res]);
    }

    /**
     * 任务动作
     */
    public function missionAction(){
        if($this->config['status'] == 0)
            $this->ajaxReturn(403);

        //获取任务信息
        $missionInfo = M('Mission')->where(['id'=>$this->param['missionId'], 'type'=>1])->find();

        if (!$missionInfo)
            $this->ajaxReturn(101);

        $sign = $missionInfo['sign'];
        $num = $missionInfo['num'];
        $addPoint = $missionInfo['points'];
        $userid = $this->userInfo['userid'];

        //检查是否已领取
        $isGet = D('Mission')->checkIsGet($userid, $this->param['missionId'], 1, $this->beginTime, $this->endTime);
        if($isGet)
            $this->ajaxReturn(6006);

        //判断是否完成
        if($sign == 'publishGamble'){
            $result = D('Mission')->checkPublishGamble($userid, $num, $this->beginTime, $this->endTime);
        }else if($sign == 'buyGamble'){
            $result = D('Mission')->checkBuyGamble($userid, $num, $this->beginTime, $this->endTime);
        }else if($sign == 'shareGamble'){
            $result = D('Mission')->checkShare($userid, $num, 1, $this->beginTime, $this->endTime);
        }else if($sign == 'shareNews'){
            $result = D('Mission')->checkShare($userid, $num, 2, $this->beginTime, $this->endTime);
        }else if($sign == 'replyNews'){
            $result = D('Mission')->checkReply($userid, $num, $this->beginTime, $this->endTime);
        }else if($sign == 'publishArticle'){
            $result = D('Mission')->checkArticle($userid, $num, $this->beginTime, $this->endTime);
        }else{
            $result = 0;
        }

        //已达成
        if($result){
            //插入积分记录
            $point = M('FrontUser')->where(['id'=>$userid])->getField('point');
            $point += $addPoint;
            D('Mission')->addPointLog($userid, 14, $addPoint, $point, '每日任务赠送');

            //插入领取记录
            D('Mission')->addMissionLog($userid, $this->param['missionId'], 1);

            //更新用户信息
            $res = D('Mission')->updateUserInfo($userid, $point);

            if ($res === false){
                $this->ajaxReturn(6002);
            }

            $this->ajaxReturn(['result' => $addPoint]);
        }else{
            $this->ajaxReturn(6001);
        }
    }

    /**
     * 我的成就
     */
    public function myAchievements(){
        $userid = $this->userInfo['userid'];
        $data = M('Mission')->field('id, name, points, img, sign, num')->where(['status'=>1, 'type'=>2])->order('sort Asc')->limit(19)->select();

        foreach((array)$data as $k => $v){
            $data[$k]['img'] = Tool::imagesReplace($v['img']);
            $isDone = D('Mission')->getAchievements($userid, $v['sign'], $v['num']);
            $isGet = D('Mission')->checkIsGet($userid, $v['id'], 2, 0, 0);
            if($isGet == 1){//已领取
                $data[$k]['status'] = 3;
            }else if($isGet == 0 && $isDone == 1){//已完成，未领取
                $data[$k]['status'] = 2;
            }else{//未完成
                $data[$k]['status'] = 1;
            }
            unset($data[$k]['sign'], $data[$k]['num']);
        }

        $this->ajaxReturn(['myAchievements'=>$data]);
    }

    /**
     * 每日签到
     */
    public function dailySignIn(){
        if($this->config['status'] == 0)
            $this->ajaxReturn(403);

        $userid = $this->userInfo['userid'];

        $user = M('FrontUser')->field('sign_time, sign_num')->where(['id'=>$userid])->find();
        //判断是否连续签到，昨天和今天的昨天是否相同（预防用户跨天登录）
        if(strtotime(date('Ymd', $user['sign_time'])) == strtotime(date('Ymd', strtotime('-1 day')))){
            if($user['sign_num'] < 7){
                $sign_num = $user['sign_num'] + 1;
            }else{//够7天重置为1
                $sign_num = 1;
            }
        }else{//不连续则是第一次
            $sign_num = 1;
        }

        //检查今天是否已领取积分
        $isSign = D('Mission')->isSign($userid, $this->beginTime, $this->endTime);
        if($isSign)
            $this->ajaxReturn(6006);

        //签到天数对应积分
        $addPoint = $this->config['dailySignIn'][$sign_num];

        //插入积分记录
        $point = M('FrontUser')->where(['id'=>$userid])->getField('point');
        $point += $addPoint;
        D('Mission')->addPointLog($userid, 16, $addPoint, $point, '每日签到赠送');

        //更新用户信息
        $res = D('Mission')->updateUserInfo($userid, $point, $sign_num, NOW_TIME);

        if ($res === false){
            $this->ajaxReturn(6003);
        }

        $this->ajaxReturn(['result' => $addPoint]);
    }

    /**
     * 分享接口
     */
    public function getShareData(){
        $otherid = isset($this->param['otherid']) ? (int)$this->param['otherid'] : 0;
        $plat = isset($this->param['plat']) ? (int)$this->param['plat'] : 0;
        $type = isset($this->param['type']) ? (int)$this->param['type'] : 0;
        $play_type = isset($this->param['play_type']) ? (int)$this->param['play_type'] : 0;//默认0，资讯不需要，竞猜一定要，让分：1，大小：-1
        $userid = $this->userInfo['userid'];

        if(empty($otherid) || empty($plat) || empty($type)){
            $this->ajaxReturn(101);
        }

        //判断今天是否已经分享入库，资讯和竞猜(不同玩法)每日只需要记录一次
        if(M('Share')->where(['user_id' => $userid, 'otherid' => $otherid, 'type' => $type, 'play_type' => $play_type, 'create_time' => ['between', [$this->beginTime, $this->endTime]]])->count()){
            $this->ajaxReturn(['result' => 0]);
        }

        $config = getWebConfig('fbConfig');
        $addPoint = $config['gamble_share'];

        //竞猜分享首次送积分
        if($type == 1 && $addPoint != 0){
            //插入积分记录
            $point = M('FrontUser')->where(['id'=>$userid])->getField('point');
            $point += $addPoint;
            D('Mission')->addPointLog($userid, 17, $addPoint, $point, '竞猜分享');

            //更新用户信息
            D('Mission')->updateUserInfo($userid, $point);
        }

        $data['user_id'] = $userid;
        $data['otherid'] = $otherid;
        $data['plat'] = $plat;
        $data['type'] = $type;
        $data['play_type'] = $play_type;
        $data['create_time'] = time();

        $res = M('Share')->add($data);
        if ($res === false){
            $this->ajaxReturn(6003);
        }

        $this->ajaxReturn(['result' => (string)$addPoint]);
    }

    /**
     * 点击领取成就积分事件
     */
    public function achievementAction(){
        if($this->config['status'] == 0)
            $this->ajaxReturn(403);

        //获取成就信息
        $missionInfo = M('Mission')->where(['id'=>$this->param['missionId'], 'type'=>2])->find();

        if (!$missionInfo)
            $this->ajaxReturn(101);

        $sign = $missionInfo['sign'];
        $num = $missionInfo['num'];
        $addPoint = $missionInfo['points'];
        $userid = $this->userInfo['userid'];

        //检查是否已领取
        $isGet = D('Mission')->checkIsGet($userid, $this->param['missionId'], 2, 0, 0);
        if($isGet)
            $this->ajaxReturn(6006);

        //判断是否完成
        if($sign == 'gambleNum'){
            $result = D('Mission')->countGambleNum($userid, $num);
        }else if($sign == 'winNum'){
            $result = D('Mission')->countGambleNum($userid, $num, 1);
        }else if($sign == 'getBuyNum'){
            $result = D('Mission')->countBuyNum($userid, $num, 1, 1);
        }else if($sign == 'buyNum'){
            $result = D('Mission')->countBuyNum($userid, $num, 2, 1);
        }else if($sign == 'fansNum'){
            $result = D('Mission')->countFansNum($userid, $num);
        }else if($sign == 'publishComment'){
            $result = D('Mission')->countCommentNum($userid, $num);
        }else{
            $result = 0;
        }

        //已达成
        if($result){
            //插入积分记录
            $point = M('FrontUser')->where(['id'=>$userid])->getField('point');
            $point += $addPoint;
            D('Mission')->addPointLog($userid, 15, $addPoint, $point, '我的成就赠送');

            //插入领取记录
            D('Mission')->addMissionLog($userid, $this->param['missionId'], 2);

            //更新用户信息
            $res = D('Mission')->updateUserInfo($userid, $point);

            if ($res === false){
                $this->ajaxReturn(6002);
            }

            $this->ajaxReturn(['result' => $addPoint]);
        }else{
            $this->ajaxReturn(6005);
        }
    }
}