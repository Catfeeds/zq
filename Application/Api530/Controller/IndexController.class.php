<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2015.12.25
 */

class IndexController extends PublicController
{
    public function index()
    {
        if($this->param['begin'] == 'go' && $this->param['gameId'] && $this->param['gameType']){
            $gameId   = $this->param['gameId'] ?: 0;
            $gameType = $this->param['gameType'] ?: 0;
            $isDelete  = $this->param['isDelete'] ?: 0;
            $redis = connRedis();
            $key_suffix = $gameType . '_' . $gameId;

//        $a['normalMembers'] = $redis->sMembers('qqty_chat_normal_online:' . $key_suffix);
//        $a['robotMembers']  = $redis->sMembers('qqty_chat_robot_online:' . $key_suffix);
            if($isDelete){
                $a['robotMembers'] = $redis->del('qqty_chat_robot_online:' . $key_suffix);
            }else{
                $a['normalMembers']  = (int) $redis->sCard('qqty_chat_normal_online:' . $key_suffix);
                $a['robotMembers']   = (int) $redis->sCard('qqty_chat_robot_online:' . $key_suffix);
                $a['chatDefaultNum'] = S('chatDefaultNum_'.$key_suffix);
            }

            $this->ajaxReturn($a);
        }else{
            $this->ajaxReturn('hello');
        }
    }

    //获取配置参数
    public function config()
    {
        $config = getConfig();
        $this->ajaxReturn(['config'=>$config]);
    }

    //意见反馈
    public function feedback()
    {
        if (!$this->param['content'])
            $this->ajaxReturn(4001);

        $userInfo = $this->getInfo($this->param['userToken']);
        $feedback_sign = $userInfo['userid'].'feedback_sign';

        if(S($feedback_sign)) 
            $this->ajaxReturn(1059);

        $data = [
            'user_id'     => $userInfo['userid'],
            'create_time' => time(),
            'content'     => $this->param['content'],
        ];

        if (!M('Feedback')->add($data))
            $this->ajaxReturn(4002);

        //发送短信通知运营
        $feedbackConfig = C('feedbackConfig');
        if($feedbackConfig['mobile'] != ''){
            sendingSMS($feedbackConfig['mobile'],"用户昵称：{$userInfo['nick_name']}，反馈内容：{$this->param['content']}");
        }
        S($feedback_sign,1,$feedbackConfig['sendTime']);

        $this->ajaxReturn(['result'=>1]);
    }

    //版本更新
    public function version()
    {
        $appChannel = I('channel') == 'f30' ? 'f30' : 'official';
        $field = ['app_type','app_name','app_pkg_name','app_version','app_url','descript','is_upgrade','update_time'];
        $version = M('AppVersion')->field($field)->where(['status'=>1,'app_channel'=>$appChannel])->order('id desc')->limit(1)->find();
        $version['app_url'] = $version['app_url'].'?rand='.microtime(true);
        $this->ajaxReturn(['version'=>$version]);
    }

    //ios版本更新
    public function version_ios()
    {
        $pkg = $this->param['pkg'] != '' ? ($this->param['pkg'] != 'company' ? '_'.$this->param['pkg'] : '') : '_personal';
        $this->ajaxReturn(['version'=>getWebConfig('common')['ios_version'.$pkg]]);
    }

    //分享
    public function share()
    {
        $share = getWebConfig('share');
        $shareImage = \Think\Tool\Tool::imagesReplace($share['img']).'?'.rand(1000,9999);
        $share['shareImage'] = $shareImage ? $shareImage : '';
        unset($share['img']);
        $this->ajaxReturn(['share'=>$share]);
    }

    //规则帮助页面
    public function help()
    {
        $this->pkg = $this->param['pkg'] ?: '';//现在有彩票推荐大师版本
        if(iosCheck()){
            $this->character = getWebConfig('common')['ios_character'];
            $this->display('Api@Index:help_ios');
        }else{
            $this->display('Api@Index:help');
        }
    }

    //服务协议页面
    public function agreement()
    {
        $this->pkg = $this->param['pkg'] ?: '';//现在有彩票分析大师版本

        if(iosCheck()){
            $this->display(T('Index/agreement_ios'));
        }else{
            $this->display(T('Index/agreement'));
        }
    }

    //下载页面
    public function download()
    {
        $this->code = $this->param['code'] ?: '';
        $this->display();
    }

    //启动页广告
    public function startAdver()
    {
        $imgType = $this->param['imgType'] ?: '';
        $adver   = @Think\Tool\Tool::getAdList(16, 2, $this->param['platform'], $imgType);

        if (isset($adver[1]))
            $adver[0]['img4s'] = $adver[1]['img'];

        if(I('pkg') == 'topSpeed' || I('pkg') == 'scoreApp'   || I('pkg') == 'basketball' || I('pkg') == 'valuableBook')
            $this->ajaxReturn(['adver'=>'']);

        $this->ajaxReturn(['adver'=>$adver[0]]);
    }

    //比分页面的广告
    public function adverList()
    {
        switch ($this->param['pageType'])
        {
            case '1': $classId = 17; break;
            case '2': $classId = 18; break;
            case '3': $classId = 19; break;
            case '4': $classId = 29; break;
            case '5': $classId = 30; break;
            default: $this->ajaxReturn(101);
        }
        $pkg = I('pkg');
        $iosCheck = iosCheck();
        if($pkg == 'topSpeed' || $pkg == 'scoreApp'  || $pkg == 'basketball' || $pkg == 'valuableBook' || $iosCheck)
            $this->ajaxReturn(['adver'=>[]]);

        $adver = @Think\Tool\Tool::getAdList($classId,20,$this->param['platform']);
/*
        foreach ($adver as $k => $v)
        {
            unset($adver[$k]['id']);
        }
*/
        $this->ajaxReturn(['adver'=>$adver ? $adver : []]);

    }

    /**
     * 获得二维码
     */
    public function getEWM(){
        if(in_array($_SERVER['HTTP_HOST'], array('www.qt.com', '183.3.152.226:8088', 'beta-dev.qqty.com:8088'))){
            $urlHost = 'beta-dev.qqty.com:8088';
        }else if(in_array($_SERVER['HTTP_HOST'], array('www.qw.com', '183.3.152.226:8099', 'beta-dev.qqty.com:8099'))){
            $urlHost = 'beta-dev.qqty.com:8099';
        }else{
            $urlHost = $_SERVER['HTTP_HOST'];
        }
        qrcode(SITE_URL.$urlHost.'/'.MODULE_NAME.'/Index/download.html?code='.$this->param['code']);
    }

    /**
     * 邀请好友详细页
     */
    public function inviteDetail(){
        $platform   = $this->param['platform'] ?: 0;
        $level      = $this->param['level'];
        $start_time = isset($this->param['start_time']) ? strtotime(trim($this->param['start_time']).' 00:00:00') : mktime(0, 0 , 0, date("m"), 1, date("Y"));
        $end_time   = isset($this->param['end_time']) ? strtotime(trim($this->param['end_time']).' 23:59:59') : mktime(23, 59, 59, date("m"), date("t"), date("Y"));
        $userToken  = getUserToken($this->param['userToken']);

        if($start_time < $end_time) {
            //新功能分隔时间
            $oldTime = strtotime('20161101 00:00:00');

            if($start_time > $oldTime){//开始时间大于分隔时间
                //查询每级的人数及相关金币数
                $ids = M('InviteRelation')->where(['user_id' => $userToken['userid'], 'lv' => $level,
                    'create_time' => ['between', [$start_time, $end_time]]])->getField('invited_id', true);

                if ($ids) {
                    //总金币数
                    if ($level == 1) {
                        $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'first_lv_uid' => ['in', $ids], 'second_lv_uid' => 0, 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $end_time]]])->sum('coin');
                        $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'second_lv_uid' => ['in', $ids], 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $end_time]]])->sum('first_coin');
                        $coin3 = (int)M('InviteLog')->where(['second_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids], 'create_time' => ['between', [$start_time, $end_time]]])->sum('second_coin');
                        $total_coin = $coin1 + $coin2 + $coin3;
                    } else if ($level == 2) {
                        $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'second_lv_uid' => ['in', $ids], 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $end_time]]])->sum('coin');
                        $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids], 'create_time' => ['between', [$start_time, $end_time]]])->sum('first_coin');
                        $total_coin = $coin1 + $coin2;
                    } else {
                        $total_coin = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'third_lv_uid' => ['in', $ids], 'create_time' => ['between', [$start_time, $end_time]]])->sum('coin');
                    }

                    //可提金币
                    $valid_coin = (int)M('InviteRecordInfo')->where(['superior_id' => $userToken['userid'], 'user_id' => ['in', $ids], 'type' => 1])->sum('coin');

                    //失效金币
                    $invalid_coin = (int)M('InviteRecordInfo')->where(['superior_id' => $userToken['userid'], 'user_id' => ['in', $ids], 'type' => 2])->sum('coin');

                    //不可提金币
                    $await_coin = $total_coin - $valid_coin - $invalid_coin;
                }
            }else{//开始时间小于分隔时间
                if($end_time > $oldTime){
                    //分两段，一段旧的，一段新的
                    $ids1 = M('InviteRelation')->where(['user_id' => $userToken['userid'], 'lv' => $level,
                        'create_time' => ['between', [$start_time, $oldTime]]])->getField('invited_id', true);

                    if ($ids1) {
                        //总金币数
                        if ($level == 1) {
                            $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'first_lv_uid' => ['in', $ids1], 'second_lv_uid' => 0, 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $oldTime]]])->sum('coin');
                            $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'second_lv_uid' => ['in', $ids1], 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $oldTime]]])->sum('first_coin');
                            $coin3 = (int)M('InviteLog')->where(['second_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids1], 'create_time' => ['between', [$start_time, $oldTime]]])->sum('second_coin');
                            $total_coin1 = $coin1 + $coin2 + $coin3;
                        } else if ($level == 2) {
                            $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'second_lv_uid' => ['in', $ids1], 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $oldTime]]])->sum('coin');
                            $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids1], 'create_time' => ['between', [$start_time, $oldTime]]])->sum('first_coin');
                            $total_coin1 = $coin1 + $coin2;
                        } else {
                            $total_coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'third_lv_uid' => ['in', $ids1], 'create_time' => ['between', [$start_time, $oldTime]]])->sum('coin');
                        }

                        //可提金币
                        $valid_coin1 = $total_coin1;

                        //失效金币
                        $invalid_coin1 = 0;

                        //不可提金币
                        $await_coin1 = 0;
                    }

                    //新的数据
                    $ids2 = M('InviteRelation')->where(['user_id' => $userToken['userid'], 'lv' => $level,
                        'create_time' => ['between', [$oldTime, $end_time]]])->getField('invited_id', true);

                    if ($ids2) {
                        //总金币数
                        if ($level == 1) {
                            $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'first_lv_uid' => ['in', $ids2], 'second_lv_uid' => 0, 'third_lv_uid' => 0, 'create_time' => ['between', [$oldTime, $end_time]]])->sum('coin');
                            $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'second_lv_uid' => ['in', $ids2], 'third_lv_uid' => 0, 'create_time' => ['between', [$oldTime, $end_time]]])->sum('first_coin');
                            $coin3 = (int)M('InviteLog')->where(['second_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids2], 'create_time' => ['between', [$oldTime, $end_time]]])->sum('second_coin');
                            $total_coin2 = $coin1 + $coin2 + $coin3;
                        } else if ($level == 2) {
                            $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'second_lv_uid' => ['in', $ids2], 'third_lv_uid' => 0, 'create_time' => ['between', [$oldTime, $end_time]]])->sum('coin');
                            $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids2], 'create_time' => ['between', [$oldTime, $end_time]]])->sum('first_coin');
                            $total_coin2 = $coin1 + $coin2;
                        } else {
                            $total_coin2 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'third_lv_uid' => ['in', $ids2], 'create_time' => ['between', [$oldTime, $end_time]]])->sum('coin');
                        }

                        //可提金币
                        $valid_coin2 = (int)M('InviteRecordInfo')->where(['superior_id' => $userToken['userid'], 'user_id' => ['in', $ids2], 'type' => 1])->sum('coin');

                        //失效金币
                        $invalid_coin2 = (int)M('InviteRecordInfo')->where(['superior_id' => $userToken['userid'], 'user_id' => ['in', $ids2], 'type' => 2])->sum('coin');

                        //不可提金币
                        $await_coin2 = $total_coin2 - $valid_coin2 - $invalid_coin2;
                    }

                    $total_coin   = (int)$total_coin1 + (int)$total_coin2;
                    $valid_coin   = (int)$valid_coin1 + (int)$valid_coin2;
                    $invalid_coin = (int)$invalid_coin1 + (int)$invalid_coin2;
                    $await_coin   = (int)$await_coin1 + (int)$await_coin2;
                    $ids          = array_merge((array)$ids1, (array)$ids2);
                }else{//结束时间小于分隔时间
                    //查询每级的人数及相关金币数
                    $ids = M('InviteRelation')->where(['user_id' => $userToken['userid'], 'lv' => $level,
                        'create_time' => ['between', [$start_time, $end_time]]])->getField('invited_id', true);

                    if ($ids) {
                        //总金币数
                        if ($level == 1) {
                            $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'first_lv_uid' => ['in', $ids], 'second_lv_uid' => 0, 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $end_time]]])->sum('coin');
                            $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'second_lv_uid' => ['in', $ids], 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $end_time]]])->sum('first_coin');
                            $coin3 = (int)M('InviteLog')->where(['second_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids], 'create_time' => ['between', [$start_time, $end_time]]])->sum('second_coin');
                            $total_coin = $coin1 + $coin2 + $coin3;
                        } else if ($level == 2) {
                            $coin1 = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'second_lv_uid' => ['in', $ids], 'third_lv_uid' => 0, 'create_time' => ['between', [$start_time, $end_time]]])->sum('coin');
                            $coin2 = (int)M('InviteLog')->where(['first_lv_uid' => $userToken['userid'], 'third_lv_uid' => ['in', $ids], 'create_time' => ['between', [$start_time, $end_time]]])->sum('first_coin');
                            $total_coin = $coin1 + $coin2;
                        } else {
                            $total_coin = (int)M('InviteLog')->where(['user_id' => $userToken['userid'], 'third_lv_uid' => ['in', $ids], 'create_time' => ['between', [$start_time, $end_time]]])->sum('coin');
                        }

                        //可提金币
                        $valid_coin = $total_coin;

                        //失效金币
                        $invalid_coin = 0;

                        //不可提金币
                        $await_coin = 0;
                     }
                }
            }
        }

        if($ids){
            //用户信息
            $userInfo = M('FrontUser')->field('nick_name, reg_time, login_count')->where(['id' => ['in', $ids]])->order('id desc')->select();
        }

        //邀请人数
        $this->total_num    = $ids ? count($ids) : 0;
        $this->total_coin   = $ids ? $total_coin : 0;
        $this->valid_coin   = $ids ? $valid_coin : 0;
        $this->invalid_coin = $ids ? $invalid_coin : 0;
        $this->await_coin   = $ids ? $await_coin : 0;
        $this->userInfo     = $ids ? $userInfo : array();
        $this->level        = $this->param['level'];
        $this->userToken    = $this->param['userToken'];
        $this->start_time   = date('Y-m-d', $start_time);
        $this->end_time     = date('Y-m-d', $end_time);
        $backUrl = '/'.MODULE_NAME.'/User/userInvitation.html?'.http_build_query(array_filter($this->param));
        $this->assign('backUrl',$backUrl);
        if($platform == 2){
            $this->display(T('User/inviteDetail_ios'));
        }else{
            $this->display(T('User/inviteDetail'));
        }
    }

    //热更新
    public function hotfix(){
        
        $sign   = strtolower(MODULE_NAME) . 'Hotfix';
        $res    = M('Config')->where(['sign' => $sign])->find();

        $config = json_decode($res['config'], true);
        
        $this->ajaxReturn(['code' => $config[$this->param['platform']]['code'] ?: '']);
    }

    //安卓热修复
    public function androidHot(){
        $param     = $this->param;
        $channel   = $param['channel'];
        $baseAppId = $param['baseAppId'];
        $patchId   = $param['patchId'];

        if($channel == '' || $baseAppId == '' || $patchId == ''){
            $this->ajaxReturn(101);
        }

        $hot = M('androidHot')->where(['status' => 1,'baseAppId' => $baseAppId])->find();

        if(!$hot) $this->ajaxReturn(201);

        $data = json_decode($hot['data'],true);

        $returnData['isMustUpdate'] = $hot['isMustUpdate'];
        $returnData['remark'] = $hot['remark'];
        $IMG_SERVER = C('IMG_SERVER');
        foreach ($data as $k => $v) 
        {
            $channelArr = explode(',', $v['channel']);
            //匹配对应渠道号
            if(in_array($channel, $channelArr)){
                $returnData['patchId']  = $v['patchId'];
                $returnData['patchUrl'] = $v['patchUrl'] != '' ? $IMG_SERVER.$v['patchUrl'] : '';
                break;
            }
        }

        if($returnData['patchId'] == '')
        {
            //匹配不到时取最后一个
            $endChannel = end($data);
            $returnData['patchId']  = $endChannel['patchId'];
            $returnData['patchUrl'] = $endChannel['patchUrl'] != '' ? $IMG_SERVER.$endChannel['patchUrl'] : '';
        }
        
        $this->ajaxReturn($returnData);
    }

    //安卓热修复成功统计
    public function androidCount()
    {
        $param     = $this->param;
        $baseAppId = $param['baseAppId'];
        $patchId   = $param['patchId'];
        $type      = $param['type'];
        $hot = M('androidCount')->where(['baseAppId' => $baseAppId,'patchId'=>$patchId])->find();
        if(!$hot){
            //新增记录
            $rs = M('androidCount')->add([
                    'baseAppId' => $baseAppId,
                    'patchId'   => $patchId,
                    'num'       => 1
                ]);
        }else{
            //统计记录
            if($type == 1){
                //成功次数+1
                $data = ['successNum' => ['exp','successNum+1']];
            }else{
                //修复次数加+
                $data = ['num' => ['exp','num+1']];
            }
            $rs = M('androidCount')->where(['baseAppId' => $baseAppId,'patchId'=>$patchId])->save($data);
        }
        if($rs)
            $this->ajaxReturn(['msg'=>'统计成功']);
        else
            $this->ajaxReturn(403);
    }

    /**
     * 新手指导
     */
    public function noviceGuide()
    {
        $this->display('NoviceGuide/index');
    }

    /**
     * 新手指引的跳转
     */
    public function noviceGuideJump()
    {
        $num = $this->param['num'];
        $this->display('NoviceGuide/guide0'.$num);
    }

    /**
     * 关于我们
     */
    public function aboutUs(){
        $pkg = $this->param['pkg'] ?: 'company';
        $res = getWebConfig('aboutUs')[$pkg];
        $this->ajaxReturn(['result' => trim($res)]);
    }

    /**
     * 获得全部外链
     */
    public function getOutsideChain(){
        if($this->param['type'] != 'go')
            return false;

        $outsideChain = getOutsideChain();
        $this->ajaxReturn(['result' => $outsideChain]);
    }

    /**
     * 球王介绍页面
     */
    public function introPage()
    {
        $pkg = $this->param['pkg'] ?: 'company';
        $this->currency = $this->param['platform'] == 2 ? 'Q币' : '金币';

        if($pkg == 'master'){
            $this->display(T('Intro/indexCp'));
        }else{
            $this->display(T('Intro/index'));
        }
    }

    /**
     * 点击增加次数：广告、集锦
     */
    public function clickInc(){
        $id     = $this->param['id'];
        $type   = $this->param['type'] ?: 1;
        if(!$id)
            $this->ajaxReturn(101);

        if($type == 1){
            $rs = D('Common')->SetIncAdver($id);
        }elseif($type==2){
            $rs = M("Highlights")->where(['id'=>$id])->setInc('click_num');
        }

        $this->ajaxReturn(['result' => $rs]);
    }

    /**
     * 专家审核通过页面
     */
    public function expertSuccess(){
        $this->display(T('Index/expert_success'));
    }

    /**
     * 专家审核协议
     */
    public function expertAgreement(){
        $this->display(T('Index/expert_agreement'));
    }

    /**
     * 机器人列表
     */
    public function robots(){
        if($this->param['platform'] != 'robot')
            $this->ajaxReturn(101);

        $page = $this->param['page'] ?: 1;
        $data = D('Robot')->getRobotList($page);

        $this->ajaxReturn($data);
    }

    /**
     * 5.1新版机器人列表
     */
    public function robotList(){
        if($this->param['platform'] != 'robot') $this->ajaxReturn(101);

        $gameId   = $this->param['gameId'] ?: 0;
        $gameType = $this->param['gameType'] ?: 0;
        $firstTime = $this->param['firstTime'] ?: 0;
        if($gameId == 0 || $gameType == 0) $this->ajaxReturn(101);

        $url = SITE_URL.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        $data = D('Robot')->getNewRobotList($gameType, $gameId, $firstTime);

        $log = "url：{$url}，data：".json_encode($_REQUEST)."，增加：".count($data['list']).'，减少：'.$data['num'];
        logRecord($log, 'robot.txt');
        $this->ajaxReturn($data ?: (object)[]);
    }

    /**
     * 统计十中几
     */
    public function countTeneGamble(){
        set_time_limit(0);
        if($this->param['begin'] != 'go')
            return false;

//        $userArr = [10,62,69,100,71,147];
        $res = D('GambleHall')->countTeneGamble();

        $this->ajaxReturn($res);
    }

    /**
     * 默认球队简称
     */
    public function setShortTeamName(){
        return false;
        set_time_limit(0);
        $begin = $this->param['begin'] ?: '';
        $gameType = $this->param['gameType'] ?: 0;

        $res = D('Home')->setShortTeamName($begin, $gameType);

        $this->ajaxReturn($res);
    }

    /**
     * 大数据分类信息页
     */
    public function bigdataInfo(){
        $classSign = $this->param['classSign'] ?: '';

        if(empty($classSign)) $this->ajaxReturn(101);

        $data = M('BigdataClass')->where(['sign' => $classSign])->find();
        $data['description'] = explode('|', $data['description']);

        $this->assign('data', $data);
        $this->display('Api@Index:bigdataInfo');
    }

    public function setTeamLogoData(){
        return false;
        /*
        set_time_limit(0);
        $page = $this->param['page'] ?: 1;
        $limit = 1000;
        $startRow    = ($page - 1) * $limit;
        $IMG_SERVER = C('IMG_SERVER');
        $list = M('GameTeambk')->field('id, team_id, img_url')->where(['img_url' => ['neq', '']])
                ->limit($startRow, $limit)->select();
//        var_dump(M('GameTeambk')->_sql());die;
        $ids = [];
        foreach($list as $k => &$v){
            $img = $this->remoteFileExists($IMG_SERVER.$v['img_url']);
            //直接请求，判断404
            if ($img) {
                continue;
            } else {
                //删除
                M('GameTeambk')->where(['id' => $v['id']])->save(['img_url' => '']);
                $ids[] = $v['id'];
            }
        }

        $this->ajaxReturn($ids);
        */
    }

    /**
     * 定时清除比赛结束的机器人
     */
    public function clearRobot(){
        set_time_limit(0);
        if($this->param['begin'] != 'go') $this->ajaxReturn(101);

        D('Robot')->clearRobot();

        $this->ajaxReturn('ok');
    }

}


?>