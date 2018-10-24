<?php
/**
 * 首页其他
 * @author huangjiezhen <418832673@qq.com> 2015.12.25
 */

class IndexController extends PublicController
{
    //todo
    public function index()
    {
        $this->ajaxReturn('hello,this is the api index');
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
        $pkg = $this->param['pkg'] != '' && $this->param['pkg'] != 'company' ? '_personal' : '';
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
        if(iosCheck()){
            $this->display(T('Index/help_ios'));
        }else{
            $this->display(T('Index/help'));
        }
    }

    //服务协议页面
    public function agreement()
    {
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
        $adver = @Think\Tool\Tool::getAdList(16,2,$this->param['platform']);

        if (isset($adver[1]))
            $adver[0]['img4s'] = $adver[1]['img'];

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
            default: $this->ajaxReturn(101);
        }

        $adver = @Think\Tool\Tool::getAdList($classId,20,$this->param['platform']);

        foreach ($adver as $k => $v)
        {
            unset($adver[$k]['id']);
        }

        $this->ajaxReturn(['adver'=>$adver ? $adver : '']);
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
        qrcode('http://'.$urlHost.'/'.MODULE_NAME.'/Index/download.html?code='.$this->param['code']);
    }

    /**
     * 邀请好友详细页
     */
    public function inviteDetail(){
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

        $this->display(T('User/inviteDetail'));
    }

    //热更新
    public function hotfix(){
        
        $sign   = strtolower(MODULE_NAME) . 'Hotfix';
        $res    = M('Config')->where(['sign' => $sign])->find();

        $config = json_decode($res['config'], true);
        
        $this->ajaxReturn(['code' => $config[$this->param['platform']]['code'] ?: '']);
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
        $res = getWebConfig('aboutUs');
        $this->ajaxReturn(['result' => trim($res)]);
    }

}


 ?>