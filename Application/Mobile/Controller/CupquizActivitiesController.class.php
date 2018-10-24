<?php
/**
 *
 * @author huangzl <443629770@qq.com>
 *
 * @since  2018-06-19
 */
use Think\Tool\Tool;
use Think\Model;
use Common\Tool\UploadTool;
class CupquizActivitiesController extends CommonController {

    /**
     * 世界杯活动页
     */
    public function activePage()
    {
        //查询最后一个活动
        $now = time();
        $activity = M('CupquizActivities')
            ->where(['status' => 1, 'quize_settle' => 0, 'start_time' => ['LT', $now], 'end_time' => ['GT', $now]])
            ->order('id desc')
            ->limit(1)
            ->find();

        // 无活动直接输出
        if($activity){
            $user_id = is_login();
            if(!$user_id){
                //如果是微信浏览器，则自动注册、登录
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                    $wx = A('Mobile/WxOauth');
                    $wx->oauth(C('CupquizMode'));//微信授权
                    exit;
                }else{
                    //是否已发起过
                    $userToken = I('userToken');

                    $userInfo = getUserToken($userToken, true);
                    if($userInfo === false){//客户端会拼接参数，所以做一下判断，需要客户端测试一下
                        $_userToken = explode('?userToken=', $userToken);
                        $userInfo = getUserToken($_userToken[0], true);
                    }

                    if($user_id = $userInfo['userid']){
                        $user_id = $userInfo['userid'];
                        D('FrontUser')->autoLogin($user_id);//登录m站
                    }else{
                        $user_id = is_login();
                    }
                }
            }

            if($user_id){
                $cupquizSpornsor = M('CupquizSponsor');
                $cupS = $cupquizSpornsor
                    ->where(['user_id' => $user_id, 'act_id' => $activity['id']])
                    ->limit(1)
                    ->find();

                if($cupS){
                    $this->redirect('CupquizActivities/guessResult', array('id'=>$cupS['id']));exit;
                }

            }

            $game_options = json_decode($activity['game_options']);

            // 日期
            $today = date('m月d日', time());

            $gameIds =array_column($game_options, 0);
            $gambleList = M('CupquizGamble')->where(['game_id' => ['IN', array_unique($gameIds)]])->select();

            $per = [];
            foreach ($gambleList as $k => $v){
                $per[$v['game_id']][$v['play_type']][$v['chose_side']] += 1;
            }

            foreach ($per as $k1 => $v1){
                foreach ($v1 as $kk => $vv){
                    $per[$k1][$kk]['count'] = array_sum(array_values($vv));
                }
            }

            // 场次
            $num = count($game_options);

            // 上轮最高
            $top = $this->_top_sponsor();
            $game_type = $this->_game_type($game_options);
            // 百分比
            foreach ($game_type as $gtk => $gtv){
                foreach ($gtv['play']['options'] as $gblk => $gblv){
                    if($per){
                        $game_type[$gtk]['play']['options'][$gblk]['per'] = round($per[$gtv['game']['game_id']][$gtv['play']['id']][$gblv[0]] / $per[$gtv['game']['game_id']][$gtv['play']['id']]['count'] * 100, 1);
                    }else{
                        $game_type[$gtk]['play']['options'][$gblk]['per'] = 0;
                    }

                }
            }
            //token 5 分钟
            $token = md5(GetRandStr(8) . rand(10000, 99999) . time());
            S($token, time(), 300);

            cookie('redirectUrl', __SELF__);

            //头像
            $user = M('FrontUser');
            $userInfo = $user
                ->where(['id' => $user_id])
                ->find();
            $frontFace = frontUserFace($userInfo['head']);

            $this->assign('today',$today);
            $this->assign('num',$num);
            $this->assign('game_type',$game_type);
            $this->assign('top',$top);
            $this->assign('frontFace',$frontFace);
            $this->assign('coin',$userInfo['coin']);
            $this->assign('_Tk',$token);
        }

        $this->assign('activity', $activity);
        $this->display();
    }

    /**
     * 接受竞猜
     * mod by hzl
     */
    public function guessing()
    {
        try{
            $act_id = I('act_id');
            $_tk = I('_tk');

            //是否非法请求
            if(!$act_id || !$_tk || !S($_tk))
                throw new Exception('已超时，请在自动刷新后重试！', -1);

            //是否登录
            if(!$user_id = is_login())
                throw new Exception('请先登录', 1000);


            $module = M('CupquizActivities');
            $activity = $module
                ->where(['status' => 1, 'id' => $act_id])
                ->order('id desc')
                ->limit(1)
                ->find();

            //检查活动有效性
            if(time() < (int)$activity['start_time'])
                throw new Exception('活动尚未开始', 1002);

            if(!$activity || $activity['is_settle'] || $activity['end_time'] < time())
                throw new Exception('活动不存在或者已经结束', 1002);

            //检测IP，同一个ip是否发起大于5次
            $ipRecord = $this->ipRecord(get_client_ip(), 'guessing_' . $act_id);
            if(!$ipRecord){
                throw new Exception('您已被限制提交预测！', -1);
            }

            //是否已发起过
            $cupquizSpornsor = M('CupquizSponsor');
            $cupS = $cupquizSpornsor
                ->where(['user_id' => $user_id, 'act_id' => $act_id])
                ->limit(1)
                ->find();

            if($cupS)
                throw new Exception('您已经参加过该场活动，请勿重复提交!', 1003);

            if($activity['sponsor'] >= $activity['limit_num'])
                throw new Exception('活动人数已达上限，敬请期待下个活动！', 1004);

            // 比赛
            $options = $_REQUEST['options'];
            $temO = explode(';', $options);

            foreach ($temO as $k => $v){
                $post_game_options[] = explode(',', $v);
            }

            $game_options = json_decode($activity['game_options']);
            $game_type = $this->_game_type($game_options);
            $gameOptions = [];

            foreach ($post_game_options as $k => $v){
                if(!in_array($v, $gameOptions)){
                    $gameOptions[] = $v;
                }
            }

            if(count($gameOptions) != count($game_type))
                throw new Exception('提交错误', 1004);

            foreach ($gameOptions as $k => $v){
                $tem = false;
                foreach ($game_type as $gk => $gv){
                    $tOptions = $gv['play']['options'];
                    foreach ($tOptions as $tok => $tov){
                        $tOs[] = $tov[0];
                    }
                    if(($v[0] == $gv['game']['game_id']) && ($v[1] == $gv['play']['id']) && (in_array($v[2], $tOs))){
                        $tem = true;
                        $gamble[] = [
                            $gv['game']['union_name'],
                            $gv['game']['union_id'],
                            $gv['game']['game_id'],
                            $gv['game']['gtime'],
                            $gv['game']['home_team_name'],
                            $gv['game']['away_team_name'],
                            $gv['play']['id'], $v[2]
                        ];
                    }
                }
                if(!$tem)
                    throw new Exception('提交错误', 1005);
            }

            // 入库
            // 人数 + 1
            $module->where('id='.$act_id)->setInc('sponsor');

            // 发起人
            $insertData = array();
            $insertData['user_id'] = $user_id;
            $insertData['act_id'] = $act_id;
            $insertData['ip'] = get_client_ip();
            $insertData['add_time'] = time();
            $insertData['qcoin'] = 2;
            $result = $cupquizSpornsor->add($insertData);

            // 比赛
            $cupquizGamble = M('CupquizGamble');
            foreach ($gamble as $k => $v){
                $insertData = array();
                $insertData['launch_id'] = $result;
                $insertData['act_id'] = $act_id;
                $insertData['union_name'] = $v[0];
                $insertData['union_id'] = $v[1];
                $insertData['game_id'] = $v[2];
                $insertData['gtime'] = $v[3];
                $insertData['home_team_name'] = $v[4];
                $insertData['away_team_name'] = $v[5];
                $insertData['play_type'] = $v[6];
                $insertData['chose_side'] = $v[7];
                $insertData['create_time'] = time();
                $cupquizGamble->add($insertData);
            }

            $this->ajaxReturn(['err' => 0, 'msg' => '提交成功', 'sid' => $result, 'uid' => $user_id]);

        }catch(Exception $e){
            $this->ajaxReturn(['err' => $e->getCode(), 'msg' => $e->getMessage()]);
        }
    }

    /**
     * 竞猜结果页
     */
    public function guessResult(){
        $sponsorId = I('id');
        $cupquizSpornsor = M('CupquizSponsor cs');
        $cupS = $cupquizSpornsor
            ->join('LEFT JOIN qc_front_user f on f.id=cs.user_id')
            ->field('cs.id,cs.user_id,cs.act_id,cs.help_num,cs.limit_num,cs.qcoin,cs.result,cs.result_time,cs.send_coin_time,cs.send_coin,cs.is_send_coin,cs.is_settle,cs.settle_time,cs.settle_type,cs.status,cs.add_time,f.head')
            ->where(['cs.id' => $sponsorId])
            ->find();

        if(!$cupS){
            $this->error('查询不到你的竞猜!');
            exit;
        }

        $user_id = is_login();

        if($cupS['user_id'] != $user_id){
            session('user_auth', null);
            $this->redirect('CupquizActivities/friendPush', 'id=' . $sponsorId);
            exit;
        }

        if(!$user_id){
            $this->redirect('User/login');
            exit;
        }

        $module = M('CupquizActivities');
        $activity = $module
            ->where(['status' => 1, 'id' => $cupS['act_id']])
            ->order('id desc')
            ->limit(1)
            ->find();
        // 预测
        $gamble = $this->_user_gamble($user_id, $cupS['act_id']);
        // 助力列表
        $helper = $this->_helper($user_id, $cupS['act_id'],0);
        // 上轮最高
        $top = $this->_top_sponsor();
        //头像
        $user = M('FrontUser');
        $userInfo = $user
            ->where(['id' => $user_id])
            ->find();
        $frontFace = frontUserFace($userInfo['head']);

        $this->assign('activity',$activity);
        $this->assign('cupS',$cupS);
        $this->assign('gamble',$gamble);
        $this->assign('helper',$helper['helper']);
        $this->assign('qcoin',$helper['qcoin']);
        $this->assign('top',$top);
        $this->assign('coin',$userInfo['coin']);
        $this->assign('frontFace',$frontFace);

        $this->display();
    }

    /**
     * 预测列表
     */
    public function guessList(){
        $user_id=is_login();
        if(!$user_id){
            $this->redirect('User/login');
            exit;
        }

        //用户预测活动id
        $cupquizS = M('CupquizSponsor cs');
        $cupquizSponsorId = $cupquizS
            ->join('LEFT JOIN qc_cupquiz_activities ac on ac.id=cs.act_id')
            ->where(['user_id' => $user_id])
            ->field('cs.id,cs.act_id,cs.qcoin,cs.result,ac.end_time')
            ->order('cs.add_time desc')
            ->limit(4)
            ->select();
        $gamble = [];
        foreach ($cupquizSponsorId as $k => $v){
            $ug = $this->_user_gamble($user_id, $v['act_id']);
            $ugg = [];
            foreach ($ug as $ugk => $ugv){
                $ugg[$ugv['game_id']] = [$ugv['home_team_name'], $ugv['away_team_name']];
            }
            $tem['gamble'] = $ug;
            $tem['qcoin'] = $v['qcoin'];
            $tem['end_time'] = $v['end_time'];
            $tem['sIdUrl'] = U('CupquizActivities/guessResult', 'id='.$v['id']);
            $tem['gambleT'] = $ugg;
            $tem['result'] = $v['result'];
            $gamble[] = $tem;
        }
        //头像 总币
        $user = M('FrontUser');
        $userInfo = $user
            ->where(['id' => $user_id])
            ->find();
        $frontFace = frontUserFace($userInfo['head']);
        $this->assign('frontFace',$frontFace);
        $this->assign('gamble',$gamble);
        $this->assign('coin',$userInfo['coin']);

        $this->display();
    }

    /**
     * 好友助力
     */
    public function friendPush(){
        cookie('redirectUrl', __SELF__);
        
        $user_id=is_login();
        if(!$user_id){
            $wx = A('Mobile/WxOauth');
            $wx->oauth(C('CupquizMode'));//微信授权
            exit;
        }

        //token 2 分钟 助力成功后删除
        $token = md5($user_id . GetRandStr(8) . rand(10000, 99999) . time());
        S($token, time(), 300);

        $sponsorId = I('id');
        $cupquizSpornsor = M('CupquizSponsor cs');
        $cupS = $cupquizSpornsor
            ->join('LEFT JOIN qc_front_user f on f.id=cs.user_id')
            ->field('cs.id,cs.user_id,cs.act_id,cs.help_num,cs.limit_num,cs.qcoin,cs.result,cs.result_time,cs.send_coin_time,cs.send_coin,cs.is_send_coin,cs.is_settle,cs.settle_time,cs.settle_type,cs.status,cs.add_time,f.head')
            ->where(['cs.id' => $sponsorId])
            ->find();

        if(!$cupS){
            $this->error('查询不到你的竞猜!');
        }

        // 自己扫码进入
        if($user_id == $cupS['user_id']){
            $this->redirect('CupquizActivities/guessResult', 'id='.$sponsorId);
            exit;
        }

        $module = M('CupquizActivities');
        $activity = $module
            ->where(['status' => 1, 'id' => $cupS['act_id']])
            ->order('id desc')
            ->limit(1)
            ->find();
        if(!$activity){
            $data['err'] = 9;
            $data['msg'] = '查询不到此活动';
            $this->ajaxReturn($data);
        }

        // 已助力
        $dayBegin = strtotime(date('Y-m-d', time()));
        $dayEnd = $dayBegin + 3600 * 24 ;
        $CupquizHelper = M('CupquizHelper');
        $helper = $CupquizHelper
            ->where(['launch_id' => $sponsorId, 'user_id' => $user_id, 'add_time' => array('between',array($dayBegin,$dayEnd))])
            ->find();
        if($helper){
            $helped = true;
        }else{
            $helped = false;
        }
        //活动结束也算已助力,显示我也去预测
        $now = time();
        if($activity['end_time'] < $now){
            $helped = true;
        }

        // 预测
        $gamble = $this->_user_gamble($user_id, $cupS['act_id']);
        // 助力列表
        $helper = $this->_helper($cupS['user_id'], $cupS['act_id'], 0);
        // 上轮最高
        $top = $this->_top_sponsor();

        //人数
        $pNum = 999 + count($helper);;
        //累积
        $may = $CupquizHelper
            ->where(['launch_id' => $sponsorId])
            ->sum('qcoin');
        if(!$may){
            $may = 0;
        }

        //头像 总币
        $user = M('FrontUser');
        $userInfo = $user
            ->where(['id' => $user_id])
            ->find();
        $frontFace = frontUserFace($userInfo['head']);
        $this->assign('frontFace',$frontFace);

        $this->assign('activity',$activity);
        $this->assign('cupS',$cupS);
        $this->assign('gamble',$gamble);
        $this->assign('helper',$helper['helper']);
        $this->assign('qcoin',$helper['qcoin']);
        $this->assign('top',$top);
        $this->assign('pNum',$pNum);
        $this->assign('coin',$userInfo['coin']);
        $this->assign('helped',$helped);
        $this->assign('may',$may);
        $this->assign('_tk',$token);

        $this->display();
    }

    /**
     * 接受好友助力
     */
    public function helpPush(){
        if(!IS_AJAX || !I('_tk') || !S(I('_tk'))){
            $data['err'] = -1;
            $data['msg'] = '提交错误，请刷新重试';
            $this->ajaxReturn($data);
        }

        S(I('_tk'), null);//token验证之后 清空 防止重复请求或者刷接口

        $user_id=is_login();
        if(!$user_id){
            $data['err'] = 7;
            $data['msg'] = '请先登录';
            $data['url'] = U('User/login');
            $this->ajaxReturn($data);
        }

        $sponsorId = I('id');

        $cupquizSpornsor = M('CupquizSponsor cs');
        $cupS = $cupquizSpornsor
            ->join('LEFT JOIN qc_cupquiz_activities ca on ca.id = cs.act_id')
            ->where(['cs.id' => $sponsorId])
            ->field('cs.id,cs.user_id,cs.act_id,cs.help_num,cs.limit_num,cs.qcoin,cs.result,cs.result_time,cs.send_coin_time,cs.send_coin,cs.is_send_coin,cs.is_settle,cs.settle_time,cs.settle_type,cs.status,cs.add_time,ca.start_time,ca.end_time,ca.status')
            ->find();

        if(!$cupS){
            $data['err'] = 1;
            $data['msg'] = '未查询到助力信息';
            $this->ajaxReturn($data);
        }else{
            //活动已禁
            if($cupS['status'] == 0){
                $data['err'] = 3;
                $data['msg'] = '亲，此活动已禁用';
                $this->ajaxReturn($data);
            }
            // 活动未开始
            $now = time();
            if($cupS['start_time'] > $now){
                $data['err'] = 4;
                $data['msg'] = '亲，此活动未开始哦';
                $this->ajaxReturn($data);
            }
            // 活动已结束
            if($cupS['end_time'] < $now){
                $data['err'] = 5;
                $data['msg'] = '亲，此活动已结束了哦';
                $this->ajaxReturn($data);
            }
        }

        if($user_id == $cupS['user_id']){
            $data['err'] = 2;
            $data['msg'] = '亲，不能对自己助力哦';
            $this->ajaxReturn($data);
        }

        //检测IP，同一个ip是否为一个活动助力大于5次
        $ipRecord = $this->ipRecord(get_client_ip(), 'help_' . $cupS['act_id']);
        if(!$ipRecord){
            $data['err'] = -1;
            $data['msg'] = '您已被限制为好友助力！';
            $this->ajaxReturn($data);exit;
        }

        //当天，只能助力一次
        $dayBegin = strtotime(date('Y-m-d', time()));
        $dayEnd = $dayBegin + 3600 * 24 ;
        $CupquizHelper = M('CupquizHelper');
        $helper = $CupquizHelper
            ->where(['launch_id' => $sponsorId, 'user_id' => $user_id, 'add_time' => array('between',array($dayBegin,$dayEnd))])
            ->find();
        if($helper){
            $data['err'] = 6;
            $data['msg'] = '亲，你今天已助力了哦';
            $this->ajaxReturn($data);
        }
        //当天，最多5次助力
        $CupquizHelper = M('CupquizHelper');
        $todayHelper = $CupquizHelper
            ->where(['user_id' => $user_id, 'add_time' => array('between',array($dayBegin,$dayEnd))])
            ->count("id");
        if((int)$todayHelper >= 5){
            $data['err'] = 7;
            $data['msg'] = '你今日助力的次数已达到上限';
            $this->ajaxReturn($data);
        }

        //入库
        $num = rand(1, 2);
        $insertData = array();
        $insertData['launch_id'] = $sponsorId;
        $insertData['user_id'] = $user_id;
        $insertData['platform'] = 0; // ??平台，即用户属性是0手机号，1微信、2QQ、还是3新浪
        $insertData['qcoin'] = $num;
        $insertData['add_time'] = time();
        $insertData['ip'] = get_client_ip();
        $result = $CupquizHelper->add($insertData);
        // 助力 + 1
        $cupquizSpornsor = M('CupquizSponsor');
        $cupquizSpornsor->where(['id' => $sponsorId])->setInc('help_num');
        // 助力 + qcoin
        $cupquizSpornsor->where(['id' => $sponsorId])->setInc('qcoin', $num);
        // 对时
        $helperCoin = $CupquizHelper
            ->where(['launch_id' => $sponsorId])
            ->sum('qcoin');

        $data['err'] = 0;
        $data['msg'] = '亲，助力成功了';
        $data['num'] = $num;
        $this->ajaxReturn($data);
    }

    /**
     * 提现
     */
    public function convert(){
        $user_id=is_login();
        if(!$user_id){
            $this->redirect('User/login');
            exit;
        }

        //头像 总币
        $user = M('FrontUser');
        $userInfo = $user
            ->where(['id' => $user_id])
            ->find();
        $frontFace = frontUserFace($userInfo['head']);
        $this->assign('frontFace',$frontFace);
        $this->assign('coin',$userInfo['coin']);

        $this->display();
    }

    /**
     * 用户预测
     */
    private function _user_gamble($user_id, $actId){
        $module = M('CupquizActivities');
        $activity = $module
            ->where(['status' => 1, 'id' => $actId])
            ->order('id desc')
            ->limit(1)
            ->find();
        $cupquizGamble = M('CupquizGamble cg');
        $gamble = $cupquizGamble
            ->join('LEFT JOIN qc_cupquiz_sponsor cs on cs.id = cg.launch_id')
            ->field('cg.id,cg.launch_id,cg.act_id,cg.union_name,cg.union_id,cg.game_id,cg.gtime,cg.home_team_name,cg.away_team_name,cg.play_type,cg.chose_side,cg.result')
            ->where(['cs.user_id' => $user_id, 'cs.act_id' => $actId])
            ->select();
        //
        $game_options = json_decode($activity['game_options']);
        $game_type = $this->_game_type($game_options);
        foreach ($gamble as $gk => $gv){
            foreach ($game_type as $gtk => $gtv){
                $tOptions = $gtv['play']['options'];
                if(($gv['game_id'] == $gtv['game']['game_id']) && ($gv['play_type'] == $gtv['play']['id'])){
                    $gamble[$gk]['play_name'] = $gtv['play']['name'];
                    $gamble[$gk]['end_time'] = $activity['end_time'];
                    $gamble[$gk]['tOptions'] = $tOptions;
                    foreach ($tOptions as $tOk => $tOv){
                        if($gv['chose_side'] == $tOv[0]){
                            $gamble[$gk]['option_name'] = $tOv[1];
                            $gamble[$gk]['option_index'] = $tOk;
                        }
                        foreach ($game_options as $goOk => $goOv){
                            if(($gv['game_id'] == $goOv[0]) && ($gv['play_type'] == $goOv[1])){
                                $gamble[$gk]['option_anwser'] = $goOv[2];
                            }
                        }
                    }
                }
            }
        }
        return $gamble;
    }

    /**
     * 上轮最高
     */
    private function _top_sponsor(){
        $top = 2000;
        $now = time();
        $cupquizSponsor = M('CupquizSponsor cs');
        $topS = $cupquizSponsor
            ->join('LEFT JOIN qc_cupquiz_activities ca on ca.id=cs.act_id')
            ->field('cs.qcoin')
            ->where(['ca.quize_settle' => 1, 'ca.status' => 1, 'ca.end_time' => array('lt', $now), 'cs.result' => 1])
            ->order('cs.qcoin desc')
            ->find();
        if($topS){
            $top += (int)$topS['qcoin'];
        }
        return $top;
    }
    /**
     * @param $game_options
     * @return array
     */

    private function _game_type($game_options){
        // 比赛及玩法
        foreach ($game_options as $k => $v){
            // 主客队ID
            $game_ids[] = $v[0];
            // 玩法ID
            $playType_ids[] = $v[1];
        }
        $game_ids = array_unique($game_ids);
        $games = M('GameFbinfo')
            ->field('home_team_name,away_team_name,game_id,home_team_id,away_team_id,union_id,union_name,gtime')
            ->where(['game_id' => ['IN', $game_ids]])
            ->select();
        $keyArr = array_column($games, 'game_id');
        $valArr = array_values($games);
        $games  = array_combine($keyArr, $valArr);
        foreach($games as $gk => $gv){
            $hn = explode(',', $gv['home_team_name']);
            $an = explode(',', $gv['away_team_name']);
            $games[$gk]['home_team_name'] = $hn[0];
            $games[$gk]['away_team_name'] = $an[0];
            $homeLogo = getLogoTeam($gv['home_team_id'], 1, 1);
            $awayLogo = getLogoTeam($gv['away_team_id'], 2, 1);
            $games[$gk]['home_team_logo'] = $homeLogo;
            $games[$gk]['away_team_logo'] = $awayLogo;
            $games[$gk]['union_id'] = $gv['union_id'];
            $un = explode(',', $gv['union_name']);
            $games[$gk]['union_name'] = $un[0];
            $games[$gk]['gtime'] = $gv['gtime'];
        }
        $playType_ids = array_unique($playType_ids);
        $playType = M('CupquizPlaytype')
            ->field('id,name,options,status')
            ->where(['game_id' => ['IN', $playType_ids]])
            ->select();
        $keyArr = array_column($playType, 'id');
        $valArr = array_values($playType);
        $playTypes  = array_combine($keyArr, $valArr);
        foreach ($playTypes as $k => $v){
            $playTypes[$k]['options'] = json_decode($playTypes[$k]['options']);
        }
        $gData = Array();
        foreach ($game_options as $k => $v){
            $gTem['game'] = $games[$v[0]];
            $gTem['play'] = $playTypes[$v[1]];
            $gData[] = $gTem;
        }
        return $gData;
    }
    
    /**
     * 好友助力
     * @param $user_id 用户id
     * @param $actId 活动id
     * @param $l 条数
     * @return array
     */
    private function _helper($user_id, $actId, $l=4){
        // 好友助力
        $cupquizHelper = M('CupquizHelper h');
        $helper = $cupquizHelper
            ->join('LEFT JOIN qc_front_user f on f.id = h.user_id')
            ->join('LEFT JOIN qc_cupquiz_sponsor cs on cs.id = h.launch_id')
            ->field('h.id, h.launch_id, h.user_id, h.platform, h.qcoin, h.add_time, f.nick_name')
            ->where(['cs.user_id' => $user_id, 'cs.act_id' => $actId])
            ->order('h.qcoin desc,h.add_time desc')
            ->limit($l)
            ->select();
        $qcoin = 0;
        foreach ($helper as $hk => $hv){
            $qcoin += $hv['qcoin'];
        }
        return ['helper' => $helper, 'qcoin' => $qcoin];
    }
    
    /**
     * 显示对应的分享图
     *
     * @User mjf
     * @DateTime 2018年6月22日
     *
     */
    public function createImg(){
        $pageType = I('pageType', 1);
        $actId = I('actId');
        $this->assign('pageType', $pageType);
    
        // 当前用户名
        $userInfo = session('user_auth');
        $userName = $userInfo['nick_name'];
        $userId = $userInfo['id'];
        $this->assign('userName', $userName);
    
        // 我的预测
        $gamble = $this->_user_gamble($userId, $actId);
        $this->assign('gamble',$gamble);
    
        // 生产二维码
        $sponsorId = I('sponsor_id');
        $this->assign('sponsorId', $sponsorId);
        
        $isHtml = I('isHtml');
        if(!empty($isHtml)){
            return $this->display('createImg');
        }
        
        $noBody = I('noBody');
        if(!empty($noBody)){
            $html = $this->fetch('imgContent');
        }
        
        if(empty($noBody)){
            $html = $this->fetch('createImg');
        }
    
        
        $returnData = array(
            'status' => 1,
            'info' => '',
            'data' => ['html'=>$html]
        );
        
        $this->ajaxReturn($returnData);
    }
    
    /**
     * 生成入口的二维码，不需要每次都生成
     * 
     * @User mjf
     * @DateTime 2018年6月26日
     *
     */
    public function saveQrcode(){
       $domain = $_SERVER['HTTP_HOST'];
       $domain = 'qqty.com';
        
        $cupCode = SITE_PATH.'/Public/Mobile/images/CupquizActivities/'.$domain.'.png';
        if(!file_exists($cupCode)){
            // 如果不存在入口二维码，则生成
            $data = @file_get_contents('https://m.'.$domain.'/CupquizActivities/helpQrcode.html');
            @file_put_contents($cupCode, $data);
        }
    }
    
    public function helpQrcode(){
        $sponsorId = I('sponsor_id');
        $url = U('CupquizActivities/friendPush', 'id='.$sponsorId);
        
        qrcode($url);
    }
    
    /**
     * base64转png
     *
     * @User mjf
     * @DateTime 2018年6月22日
     *
     */
    public function base64ToImage() {
        $base64 = I('base64');
        //         $image = base64_decode($base64); // 如果不用UploadTool上传
    
        $data = (new UploadTool())->uploadFileBase64($base64, 'test');
        
        if(!empty($data['status'])){
            $sessionId = 'cup_down_share_png_'. uniqid();
            $data['url'] = imagesReplace($data['url']);
            $_SESSION[$sessionId] = $data['url'];
    
            $data['session_id'] = $sessionId;
        }
    
        return $this->ajaxReturn($data);
    }
    
    /**
     * 下载图片
     *
     * @User mjf
     * @DateTime 2018年6月22日
     *
     */
    public function saveImg(){
        $sponsorId = I('sponsor_id');
        $pageType = I('page_type', 1);
        
        $field = 'help_url';
        if(2 == $pageType){
            $field = 'win_url';
        }
        
        if(3 == $pageType){
            $field = 'nowin_url';
        }
        
        $cupquizSponsor = M('CupquizSponsor');
        $find = $cupquizSponsor->field($field)->where(array('id'=>$sponsorId))->find();
        $url = $find['url'];
        
        $download = new \Common\Tool\DownloadTool();
        $download->remoteDownload($url, 'share.png');
    }

    /**
     * 服务器生成图片
     * 
     * @User mjf
     * @DateTime 2018年6月24日
     *
     */
    public function shareImg() {
        $pageType = I('pageType', 1);
        $actId = I('actId');
        $sponsorId = I('sponsor_id');
        
        $field = 'help_url';
        if(2 == $pageType){
            $field = 'win_url';
        }
        
        if(3 == $pageType){
            $field = 'nowin_url';
        }
        
        $cupquizSponsor = M('CupquizSponsor');
        $find = $cupquizSponsor->field($field)->where(array('id'=>$sponsorId))->find();
        $newUrl = I('new_url', 1); // 是否重新生成url
        
        if(!empty($find[$field]) && empty($newUrl)){
            $data = [
                'status' => 1,
                'info' => '',
                'url' => $find[$field]
            ];
            
            if(empty(I('is_html'))){
                return $this->ajaxReturn($data);
            }
        }
        
        // 当前用户名
        $userInfo = session('user_auth');
        $userName = $userInfo['nick_name'];
        $userId = $userInfo['id'];
        $this->assign('userName', $userName);
        $this->assign('pageType', $pageType);
        
        // 我的预测
        $gamble = $this->_user_gamble($userId, $actId);
        $this->assign('gamble',$gamble);
        
        // 金币
        $qcoin = '';
        if(2 == $pageType){
            $cupS = $cupquizSponsor
            ->where(['id' => $sponsorId])
            ->field(['qcoin'])
            ->find();
            $qcoin = $cupS['qcoin'];
        }
        
        $this->assign('qcoin', $qcoin);
        $this->assign('sponsorId', $sponsorId);
        
        $user = M('FrontUser');
        $userInfo = $user
        ->where(['id' => $userId])
        ->find();
        $frontFace = frontUserFace($userInfo['head']);
        
        $this->assign('frontFace', $frontFace);
        
        $html = $this->fetch();
         
        if(!empty(I('is_html'))){
            return $this->display();
        }
        
        $fileName = $userId . '_' . $actId . '_' . $pageType;
        $filePath = SITE_PATH . 'Runtime/Temp';
        
        $htmlFile = $filePath . DIRECTORY_SEPARATOR . $fileName . '.html';
        $pngFile = $filePath . DIRECTORY_SEPARATOR . $fileName . '.png';
        
        file_put_contents($htmlFile, $html);
        $cmd = 'wkhtmltoimage --crop-w 528 ' . $htmlFile . ' ' . $pngFile;
        system($cmd);
        
        $pngFile = (new \Common\Tool\ImgcompressTool($pngFile, 1))->compressImg($pngFile);
        
        $_FILES['fileInput'] = [
            'name' => $fileName . '.png',
            'type' => 'image/png',
            'tmp_name' => $pngFile,
            'error' => 0,
            'size' => filesize($pngFile)
        ];
        
        $data = (new UploadTool())->uploadImg('fileInput', 'cup', $fileName);
        if(!empty($data['status'])){
            // 更新数据库对应生成图片的地址
            $data['url'] = imagesReplace($data['url']);
            $cupquizSponsor->where('id='.$sponsorId)->save(array($field=>$data['url']));
        }
        
        return $this->ajaxReturn($data);
    }

    /***
     * ip记录，同一个ip发起活动和助力的次数
     * @param $ip
     * @param $suffix
     * @return int
     */
    public function ipRecord($ip, $suffix){
        $redis = connRedis();
        $key = 'cup_activity_' . $ip . '_' .$suffix;
        $num = $redis->get($key);

        if((int)$num >= 5)
            return false;

        //递增1
        $redis->incr($key);

        //设置过期事间
        if($num === false)
             $redis->expire($key, 24 * 3600);

        return true;

    }

}
