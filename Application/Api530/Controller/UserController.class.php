<?php
/**
 * 用户中心
 * @author huangjiezhen <418832673@qq.com> 2015.12.15
 */

class UserController extends CommonController
{
    //获取用户的信息
    public function index()
    {
        $user_id = $this->userInfo['userid'];
        $userInfo = M('FrontUser')
                ->field(['nick_name','username','vip_time','predictive_model_vip','area_code','lv','lv_bet','lv_bk','head face','point','coin','unable_coin','descript','weixin_unionid','sina_unionid','qq_unionid','mm_unionid','is_expert','expert_status','reason','customer_msg as customerNum'])
                ->where(['id'=>$user_id])
                ->find();

        $userInfo['fansNum']        = M('FollowUser')->where(['follow_id'=>$user_id])->count();
        $userInfo['face']           = frontUserFace($userInfo['face']);
        $userInfo['weixin_unionid'] = (string)$userInfo['weixin_unionid'];
        $userInfo['sina_unionid']   = (string)$userInfo['sina_unionid'];
        $userInfo['qq_unionid']     = (string)$userInfo['qq_unionid'];
        $userInfo['mm_unionid']     = (string)$userInfo['mm_unionid'];
        $userInfo['username']       = (string)$userInfo['username'];
        $userInfo['area_code']      = (string)$userInfo['area_code'];
        $userInfo['inviteNum']      = (string)M('InviteInfo')->where(['user_id' => $user_id])->getField('total_coin');

        if(iosCheck()) $userInfo['descript'] = str_replace(C('filterNickname'), C('replaceWord'), $userInfo['descript']);

        //优惠券、体验卷数量
        $tickets = (int)M('TicketLog')->where(['_string' => 'over_time >' . NOW_TIME . ' and status=1 and is_use = 0','user_id' => $user_id])->count();
        $userInfo['ticket_count'] = (string)$tickets;

        //消息通知查询
        $msgNum = M('Msg')->where(['front_user_id'=>$user_id,'is_read'=>0])->count();
        //关注通知查询
        $redis = connRedis();
        $followNum = $redis->get('qqty/'.$user_id.'/userNotify/1');
        $userInfo['msgNum'] = $msgNum;
        $userInfo['followNum'] = $followNum ? : '0';

        //判断vip
        $userInfo['is_vip'] = checkVip($userInfo['vip_time']);
        //到期时间
        $userInfo['vip_time'] = !empty($userInfo['vip_time']) ? date('Y-m-d',$userInfo['vip_time']) : '';

        //判断预测会员vip
        $userInfo['is_model_vip'] = checkVip($userInfo['predictive_model_vip']);
        //预测会员vip到期时间
        $userInfo['model_vip_time'] = !empty($userInfo['predictive_model_vip']) ? date('Y-m-d',$userInfo['predictive_model_vip']) : '';
        unset($userInfo['predictive_model_vip']);
        $this->ajaxReturn(['userInfo'=>$userInfo]);
    }

    //修改昵称
    public function editNickName()
    {
        $nickName = $this->param['nick_name'];

        //过滤表情，
        if(preg_match('/[\x{10000}-\x{10FFFF}]/u', $nickName,$matchs))
            $this->ajaxReturn(1065);

        //长度判断
        $nameLen = Think\Tool\Tool::utf8_strlen($nickName);
        if ($nameLen < 2 || $nameLen > 10)
            $this->ajaxReturn(1052);

        //敏感词过滤
        if(!matchFilterWords('nickFilter',$this->param['nick_name']))
            $this->ajaxReturn(1061);

        //查重
        if (M('FrontUser')->where(['id'=>['neq',$this->userInfo['userid']],'nick_name'=>$nickName])->find())
            $this->ajaxReturn(1011);

        //更新
        if (M('FrontUser')->save(['id'=>$this->userInfo['userid'],'nick_name'=>$nickName]) === false)
            $this->ajaxReturn(1012);

        $this->ajaxReturn(['nick_name'=>$nickName]);
    }

    //关注用户
    public function followUser()
    {
        if ($this->userInfo['userid'] == $this->param['user_id'])
            $this->ajaxReturn(1016);

        $isFollow = M('FollowUser')->where(['user_id'=>$this->userInfo['userid'],'follow_id'=>$this->param['user_id']])->find(); //是否已经关注

        if ($isFollow)
            $this->ajaxReturn(1017);

        $add = M('FollowUser')->add(['user_id'=>$this->userInfo['userid'],'follow_id'=>$this->param['user_id'],'follow_time'=>time()]);

        if (!$add)
            $this->ajaxReturn(1018);

        $this->ajaxReturn(['user_id'=>$this->param['user_id']]);
    }

    //取消关注用户
    public function cancleFollow()
    {
        if (M('FollowUser')->where(['user_id'=>$this->userInfo['userid'],'follow_id'=>$this->param['user_id']])->delete() == false)
            $this->ajaxReturn(1019);

        $this->ajaxReturn(['user_id'=>$this->param['user_id']]);
    }

    //订阅用户
    public function subscribeUser()
    {
        if(!$this->param['user_id'])
            $this->ajaxReturn(101);

        if ($this->userInfo['userid'] == $this->param['user_id'])
            $this->ajaxReturn(1076);

        $subInfo = M('FollowUser')
            ->field(['id','sub'])
            ->where (['user_id'=>$this->userInfo['userid'],'follow_id'=>$this->param['user_id']])
            ->find();

        if ($subInfo && $subInfo['sub'] != 1){
            $save = M('FollowUser')->where(['id' => $subInfo['id']])->save(['sub_time' => NOW_TIME,'sub' => 1]);

            if (!$save)
                $this->ajaxReturn(1077);

        } elseif (!$subInfo){

            $addData = [
                'user_id'       => $this->userInfo['userid'],
                'follow_id'     => $this->param['user_id'],
                'follow_time'   => NOW_TIME,
                'sub_time'      => NOW_TIME,
                'sub'           => 1
            ];
            $add = M('FollowUser')->add($addData);

            if (!$add)
                $this->ajaxReturn(1077);

        } else {
            $this->ajaxReturn(1078);
        }

        $this->ajaxReturn(['user_id'=>$this->param['user_id']]);
    }

    //取消订阅用户
    public function cancleSubscribe()
    {
        if(!$this->param['user_id'])
            $this->ajaxReturn(101);

        $res = M('FollowUser')
            ->where(['user_id'=>$this->userInfo['userid'],'follow_id'=>$this->param['user_id']])
            ->save(['sub' =>0]);

        if ( $res == false)
            $this->ajaxReturn(1079);

        $this->ajaxReturn(['user_id'=>$this->param['user_id']]);
    }

    //通知
    public function msg()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;
        $user_id = $this->userInfo['userid'];
        $list = M('Msg')->field(['title','content','is_read','send_time'])
                ->where(['front_user_id'=>$user_id])
                ->page($page.','.$pageNum)
                ->order('id desc')
                ->select();

        if($list){
            //把消息设置成已读
            M('Msg')->where(['front_user_id'=>$user_id,'is_read'=>0])->save(['is_read'=>1]);
        }

        //金币改Q币
        foreach($list as $k => &$v){
            $v['content'] = str_replace('金', 'Q', $v['content']);
        }

        $this->ajaxReturn(['msgList'=>(array)$list]);
    }

    //标识通知已读  (5.2版本已废弃不用)
    public function readMsg()
    {
        M('Msg')->where(['front_user_id'=>$this->userInfo['userid']])->save(['is_read'=>1]);
        $this->ajaxReturn(['read'=>1]);
    }

    //定时获取新的通知 (5.2版本已废弃不用)
    public function getNewMsg()
    {
        $user_id = $this->userInfo['userid'];
        $msgNum = M('Msg')->where(['front_user_id'=>$user_id,'is_read'=>0])->count();
        $redis = connRedis();
        $followNum = $redis->get('qqty/'.$user_id.'/userNotify/1');
        $this->ajaxReturn(['msgNum'=>$msgNum,'followNum'=>$followNum ? : '0']);
    }

    /**
     * 我的推荐
     */
    public function gambleInfo()
    {
        $userid     = $this->userInfo['userid'];
        $playType   = $this->param['play_type'] ?: 0;//默认0，全部，让分：1，大小：-1；竞彩：2
        $gameType   = $this->param['game_type'] ?: 1;//足球：1 篮球：2
        $page       = $this->param['page'] ?: 1;
        $gambleType = $this->param['gamble_type'];//默认0，1亚盘 2竞彩

        //只有第一页时返回用户的推荐统计
        if (in_array($playType, [0, 1, -1, 2]) && $page <= 1) {
            //获取用户等级
            $info = M('FrontUser')->field(['lv_bet','lv', 'lv_bk'])->where(['id' => $userid])->find();
            //亚盘统计
            $userInfo['yp']                     = D('GambleHall')->getWinning($userid, $gameType, 0, 1, 0);//查总的
            $userInfo['yp']['lv']               = $gambleType == 1 ? $info['lv'] : $info['lv_bet'];
            $userInfo['yp']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($userid, $gameType, 1);
            $userInfo['yp']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($userid, $gameType, 2);
            $userInfo['yp']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($userid, $gameType, 3);
            $userInfo['yp']['total_times']      = (string)$userInfo['yp']['total_times'];

            if($gameType == 1){
                //竞彩统计
                $userInfo['jc']                     = D('GambleHall')->getWinning($userid, $gameType, 0, 2, 0);
                $userInfo['jc']['lv']               = $info['lv_bet'];
                $userInfo['jc']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($userid, $gameType, 1, false, false, 0, 2);
                $userInfo['jc']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($userid, $gameType, 2, false, false, 0, 2);
                $userInfo['jc']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($userid, $gameType, 3, false, false, 0, 2);
                $userInfo['jc']['total_times']      = (string)$userInfo['jc']['total_times'];
                $userInfo['jc']['lv'] = $info['lv_bet'];
                $userInfo['yp']['lv'] = $info['lv'];
                unset($userInfo['jc']['level']);
            } else {
                $userInfo['yp']['lv'] = $info['lv_bk'];
            }
        }

        //推荐记录
        if($playType == 2){
            $gambleType = 2;
            $playType = 0;
        }
        $total_times = D('GambleHall')->getGambleList($userid, $playType, $page, $gamble_id = 0, $gambleType, $gameType,true);
        $gambleList = D('GambleHall')->getGambleList($userid, $playType, $page, $gamble_id = 0, $gambleType, $gameType);

        foreach ($gambleList as $k => $v){
            $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['gamble_id'=>$v['gamble_id'], 'game_type' => $gameType])->count();
            $gambleList[$k]['game_type'] = $gameType?:1;
        }

        $this->ajaxReturn(['userInfo'=>$userInfo ?:'','gambleList'=>$gambleList,'total_times' => $total_times]);
    }

    /**
     * 我关注的用户列表
     */
    public function myFollowUser()
    {
        $type = $this->param['type'] ?: 1; //类型 1足球 2篮球 默认1
        $page = $this->param['page'] ?: 1; //页码 默认1
        $pageNum = 20;

        $user_id = $this->userInfo['userid'];
        switch ($type) {
            case '1':
                //足球
                $model = M('Gamble');
                //亚盘等级排序
                $list1 = M('FollowUser f')
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.follow_id')
                ->field('f.follow_id user_id,u.nick_name,u.head face,u.descript,u.lv,u.lv_bet')      
                ->where(['f.user_id'=>$user_id])
                ->page($page.','.$pageNum)
                ->order('u.lv desc,u.lv_bet desc')
                ->select();
                //竞彩等级排序
                $list2 = M('FollowUser f')
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.follow_id')
                ->field('f.follow_id user_id,u.nick_name,u.head face,u.descript,u.lv,u.lv_bet')      
                ->where(['f.user_id'=>$user_id])
                ->page($page.','.$pageNum)
                ->order('u.lv_bet desc,u.lv desc')
                ->select();
                $list3 = array_merge($list1,$list2);
                //去重
                $list = $userIdArr = $sort = [];
                foreach ($list3 as $k => $v) 
                {
                    if(!in_array($v['user_id'], $userIdArr))
                    {
                        //排序 亚盘与竞彩哪个大就取哪个
                        $lv = $v['lv'] >= $v['lv_bet'] ? $v['lv'] : $v['lv_bet'];
                        //等级类型1为亚盘  2竞彩
                        $v['type']   = $v['lv'] >= $v['lv_bet'] ? 1 : 2; 
                        $v['lv']     = $lv;
                        unset($v['lv_bet']);
                        $list[]      = $v;
                        $sort[]      = $lv; //用于排序
                        $userIdArr[] = $v['user_id']; //去重标记
                    }
                }
                array_multisort($sort,SORT_DESC,$list);
                break;
            case '2':
                //篮球
                $model = M('Gamblebk');
                $list = M('FollowUser f')
                        ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.follow_id')
                        ->field('f.follow_id user_id,u.nick_name,u.head face,u.descript,u.lv_bk as lv,1 as type')      
                        ->where(['f.user_id'=>$user_id])
                        ->page($page.','.$pageNum)
                        ->order('u.lv_bk desc,u.bk_ten_gamble desc')
                        ->select();
                break;
        }
        
        foreach ($list as $k => $v) {
            $userIdArr[] = $v['user_id'];
        }

        //一并查出今天的推荐
        $blockTime = getBlockTime($type, true);//获取竞猜分割日期的区间时间
        $today_gamble = $model->where(['user_id' => ['in',$userIdArr], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->group('user_id')->getField('user_id',true);

        foreach ($list as $k => $v)
        {
            $list[$k]['face'] = frontUserFace($v['face']);
            $list[$k]['today_gamble']  =  in_array($v['user_id'], $today_gamble) ? 1 : 0;
        }
        //去掉红点标记
        if($list){
            $redis = connRedis();
            $followNum = $redis->del('qqty/'.$user_id.'/userNotify/1');
        }

        $this->ajaxReturn(['userList' => $list?:[]]);
    }

    /**
     * 我的粉丝 (hzl)
     */
    public function myFans()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $list = M('FollowUser f')
            ->field('f.user_id,u.nick_name,u.lv,u.lv_bk,u.lv_bet,u.head face,u.descript')
            ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.user_id')
            ->where(['f.follow_id'=> $this->userInfo['userid'], 'u.id' => ['GT', '0']])
            ->page($page.','.$pageNum)
            ->order('f.id desc')
            ->select();

        foreach ($list as $k => $v)
        {
            $res = M('FollowUser')->where(['user_id'=>$this->userInfo['userid'],'follow_id'=>$v['user_id']])->find();
            $list[$k]['isFollow']   =  $res ? '1' : '0';
            $list[$k]['sub']        =  $res ? (string)$res['sub'] : '0';
            $list[$k]['face']       = frontUserFace($v['face']);
        }
        $this->ajaxReturn(['userList' => $list ?: []]);
    }

    /**
     * 我的关注-动态 (5.2版本已废弃不用)
     * 足球、篮球竞猜记录
     */
    public function myFollowGamble()
    {
        $follows = M('FollowUser fu')
            ->field('u.id user_id, u.nick_name, u.lv, u.lv_bet, u.lv_bk,u.head face, fu.follow_id')
            ->join('LEFT JOIN qc_front_user u ON u.id = fu.follow_id')
            ->where(['user_id' => $this->userInfo['userid']])
            ->select();
        $userArr = $followId = [];

        if ($follows) {
            foreach ($follows as $k => $users) {
                $users['face'] = frontUserFace($users['face']);
                $userArr[$users['follow_id']] = $users;
                $followId[] = $users['follow_id'];
            }

            $page = $this->param['page']?:'1';
            $where['user_id'] = ['IN', $followId];

            if(iosCheck()) $where['tradeCoin'] = 0;

            //根据时间区间分别查询足球、篮球数据查询
            $field = [
                'g.id gamble_id', 'g.user_id', 'g.game_id', 'g.union_name', 'g.home_team_name', 'g.away_team_name',
                'g.game_date', 'g.game_time', 'g.score', 'g.half_score', 'g.play_type', 'g.chose_side', 'g.handcp',
                'g.odds', 'g.result', 'g.tradeCoin', 'g.vote_point', 'g.earn_point', 'g.create_time', 'g.`desc`',
                'qu.union_color', 'gf.game_state','(g.quiz_number + g.extra_number) as quiz_number','g.voice','g.is_voice','g.voice_time'
            ];

            $field[]   = 'gf.bet_code';
            $fblist = (array)M('Gamble g')->field($field)
                ->join("LEFT JOIN qc_union AS qu ON g.union_id = qu.union_id")
                ->join("LEFT JOIN qc_game_fbinfo AS gf ON g.game_id = gf.game_id")
                ->where($where)
                ->order('g.id DESC')
                ->page($page . ',20')
                ->select();

            $bklist = (array)M('Gamblebk g')->field($field)
                ->join("LEFT JOIN qc_bk_union AS qu ON g.union_id = qu.union_id")
                ->join("LEFT JOIN qc_game_bkinfo AS gf ON g.game_id = gf.game_id")
                ->where($where)
                ->order('g.id DESC')
                ->page($page . ',20')
                ->select();

            if($fblist || $bklist){
                foreach($fblist as $fk=>$fv){
                    $fblist[$fk]['game_type'] = '1';
                }

                foreach($bklist as $bk=>$bv){
                    $bklist[$bk]['game_type'] = '2';
                }

                $lists = array_merge($bklist, $fblist);
                foreach($lists as $k=>$v){
                    $sort_time[] = $v['create_time'];
                }

                array_multisort($sort_time,SORT_DESC,$lists);
                $gambleList = array_slice($lists,0,20);
                foreach ($gambleList as $k => $v) {
                    $gambleList[$k]['union_name']     = explode(',', $v['union_name']);
                    $gambleList[$k]['home_team_name'] = explode(',', $v['home_team_name']);
                    $gambleList[$k]['away_team_name'] = explode(',', $v['away_team_name']);

                    if($v['voice'] != '' && $v['is_voice'] == '1' ){
                        $gambleList[$k]['voice'] = C('IMG_SERVER') . $v['voice'];
                    }else{
                        $gambleList[$k]['voice'] = '';
                    }

                    $userid = $v['user_id'];
                    $userInfo['is_trade'] = M('QuizLog')->where(['user_id' => $this->userInfo['userid'], 'gamble_id' => $v['gamble_id']])->getField('id') ? '1' : '0';

                    $gambleList[$k]['game_state'] = (string)$v['game_state'];
                    $gambleList[$k]['bet_code'] = (string)$v['bet_code'];
                    $gambleList[$k]['union_color'] = (string)$v['union_color'];

                    unset($gambleList[$k]['user_id']);
                    unset($gambleList[$k]['is_voice']);
                    unset($userArr[$userid]['follow_id']);

                    $gambleList[$k]['quiz_number'] = D('Common')->getQuizNumber($v['quiz_number']);
                    $gl[] = array_merge($gambleList[$k], $userInfo, $userArr[$userid]);
                }
            }
        }

        $this->ajaxReturn(['gambleList' => $gl ?: []]);
    }

    // 账户明细（全部、1支出、2收入、3待结算）
    //支出 2：提款 3：交易(查看) 9：系统扣除 12：扣除分成金币  14：重置数据
    //收入 1：营销支出 4：分成 5：系统赠送 6：积分兑换 7：充值收入 8：自动充值 10：提款失败退回 11：返还购买金币  13：邀请好友
    public function accountLog()
    {
        $page       = $this->param['page'] ?: 1;
        $pageNum    = 20;
        $userid     = $this->userInfo['userid'];
        $type       = $this->param['type'];//1：支出，2：收入，3：待结算
        $where      = ['user_id' => $userid];
        $exType     = C('payAccountType');
        //获取记录
        if($type == 3){
            $list1 = (array)M('quizLog q')
                ->join("RIGHT JOIN qc_gamble g on g.id = q.gamble_id")
                ->field('g.home_team_name,g.away_team_name,g.play_type,g.chose_side,g.quiz_number,g.income,q.log_time,q.gamble_id,q.game_type')
                ->where("g.user_id = ".$userid." AND g.result = 0 AND g.tradeCoin > 0 AND g.quiz_number > 0 AND g.is_back = 0 and q.game_type = 1")
                ->group("g.id")
                ->order('q.log_time desc')
                ->select();

            $list2 = (array)M('quizLog q')
                ->join("RIGHT JOIN qc_gamblebk g on g.id = q.gamble_id")
                ->field('g.home_team_name,g.away_team_name,g.play_type,g.chose_side,g.quiz_number,g.income,q.log_time,q.gamble_id,q.game_type')
                ->where("g.user_id = ".$userid." AND g.result = 0 AND g.tradeCoin > 0 AND g.quiz_number > 0 AND g.is_back = 0 and q.game_type = 2")
                ->group("g.id")
                ->order('q.log_time desc')
                ->select();

            $list3 = array_merge($list1,$list2);
            foreach($list3 as $k1 => $v1){
                $log_time[] = $v1['log_time'];
            }
            array_multisort($log_time,SORT_DESC,$list3);

            $list = array_slice($list3,($page-1) * 20,$pageNum);

            foreach($list as $k => $v)
            {
                 //编辑描述
                $desc = '';
                $home_name  = explode(',',$v['home_team_name'])[0];
                $away_name  = explode(',',$v['away_team_name'])[0];

//                if(iosCheck()){
//                    $list[$k]['desc'] = '';
//                }else {
                    $desc .= '您推荐的【';
                    if ($v['game_type'] == 2) {
                        $desc .= '篮球-';
                        $desc .= C('bk_play_type')[$v['play_type']];
                    } else {
                        $desc .= in_array($v['play_type'], ['2', '-2']) ? '竞彩-' : '亚盘-';
                        if (!iosCheck()) $desc .= C('fb_play_type')[$v['play_type']];
                    }

                    $desc .= " {$home_name}VS{$away_name}】";
                    $desc .= "被{$v['quiz_number']}人查看";

                    $list[$k]['desc'] = $desc ? $desc : $v['desc'];
                    $list[$k]['desc'] = str_replace('金', 'Q', $list[$k]['desc']);
//                }

                $list[$k]['change_num'] = $v['income'];
                $list[$k]['log_type'] = '4';
                $list[$k]['log_status'] = '1';
                unset($list[$k]['gamble_id']);
                unset($list[$k]['game_type']);
                unset($list[$k]['income']);
            }

        }else{
            if($type){
                $where['log_type'] = $this->param['type'] == '1' ? ['IN', $exType] : ['NOT IN', $exType];
            }
            $list = M('AccountLog')->field(['log_type','log_status','log_time','change_num','desc','gamble_id','game_type','ticket_id'])
                    ->where( $where )
                    ->page($page.','.$pageNum)
                    ->order('id desc')
                    ->select();
        }

        foreach ($list as $k2 => $v2)
        {
            $accountStatus = C('accountStatus');
            $list[$k2]['status_desc'] = $accountStatus[$list[$k2]['log_status']];

            //标记是收入还是支出
            if (in_array($v2['log_type'], $exType)){
                $list[$k2]['type'] = '1';
            }else{
                $list[$k2]['type'] = '2';
            }

            if($v2['log_type'] == '1'){
                $list[$k2]['log_type'] = '4';
            }

//            if(iosCheck()){
//                $list[$k2]['desc'] = '';
//            } else{
            $list[$k2]['desc'] = str_replace('金', 'Q', $list[$k2]['desc']);
//            }

            unset($list[$k2]['ticket_id']);
        }

        if ($page != 1)
            $this->ajaxReturn(['logList' => $list?:[]]);

        $exNum  = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['IN',$exType]])->sum('change_num');//支出
        $inNum  = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['NOT IN',$exType]])->sum('change_num');//收入
        $wjsNum1 = (int)M('gamble')->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)->sum('income'); //足球待结算
        $wjsNum2 = (int)M('gamblebk')->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)->sum('income'); //篮球待结算
        $totalNum   = $inNum - $exNum;//余额

        $this->ajaxReturn(['totalNum' => $totalNum, 'exNum' => $exNum, 'inNum' => $inNum, 'wjsNum' => $wjsNum1 + $wjsNum2,'logList' => $list?:[]]);
    }

    //积分明细
    public function pointLog()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $where = ['user_id'=>$this->userInfo['userid']];
        $exType = [2,6,19]; //支出的类型

        switch ($this->param['type'])
        {
            case '1':   $where['log_type'] = ['in',$exType];       break;
            case '2':   $where['log_type'] = ['not in',$exType];   break;
        }

        $list = M('PointLog')->field(['log_type','log_time','change_num','desc'])
                ->where($where)
                ->page($page.','.$pageNum)
                ->order('id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            if (in_array($v['log_type'], $exType))
                $list[$k]['type'] = 1;
            else
                $list[$k]['type'] = 2;

            // if(iosCheck()){
            //     $list[$k]['desc'] = '';
            // }else{
                $list[$k]['desc'] = str_replace('金', 'Q', $list[$k]['desc']);
            //}
        }

        if ($page != 1)
            $this->ajaxReturn(['logList'=>$list?:[]]);

        $exNum = (int)M('PointLog')->where(['user_id'=>$this->userInfo['userid'],'log_type'=>['in',$exType]])->sum('change_num');
        $inNum = (int)M('PointLog')->where(['user_id'=>$this->userInfo['userid'],'log_type'=>['not in',$exType]])->sum('change_num');
        $totalNum = $inNum - $exNum;

        $this->ajaxReturn(['totalNum'=>$totalNum,'exNum'=>$exNum,'inNum'=>$inNum,'logList'=>$list?:[]]);
    }

    /**
     * 查看记录（我的购买）
     * @return array
     */
    public function tradeLog()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        //篮球：全场让分：1，全场大小：-1；足球：1：让分，-1大小；2：胜平负，-2：让球胜平负；
        $playType    = $this->param['play_type'] ?: 0;//玩法类型，1：让球；2：大小；
        $gamebleType = $this->param['gamble_type'] ?: 0;//推荐类型(1:亚盘;2:竞彩 )
        $game_type   = $this->param['game_type'] ?: 1;//默认1足球，2篮球

        if($gamebleType == 1){
            $where1 = ' play_type in (-1,1) ';
            $where2['play_type'] = ['in', [-1,1]];
            $where3 = ['in', [-1,1]];
        }else if($gamebleType == 2){
            $where1 = ' play_type in (-2,2) ';
            $where2['play_type'] = ['in', [-2,2]];
            $where3 = ['in', [-2,2]];
        }

        if($playType){
            $where1 = ' play_type = '.(int)$playType;
            $where2['play_type'] = (int)$playType;
            $where3 = $playType;
        }

        if(iosCheck()){
            $where4 = 0;
        }else{
            $where4 = ['EGT', 0];
        }

        if($game_type == 1){
            //旧表和新表的联表
            /*
            $subQuery = M('Gamble')->field('id')
                ->union('SELECT id from qc_gamble_reset WHERE '.$where1, true)
                ->where($where2)->buildSql();
            $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id','q.game_id'])
                ->join('INNER JOIN  '.$subQuery.' gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$this->userInfo['userid'], 'q.game_type' => $game_type])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();
*/

            $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id','q.game_id'])
                ->join('INNER JOIN  qc_gamble gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$this->userInfo['userid'], 'q.game_type' => $game_type, 'gm.play_type' => $where3, 'gm.tradeCoin' => $where4])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();
        }else{
            $string = $playType ?: ['in', [-1,1]];
            $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id','q.game_id'])
                ->join('INNER JOIN  qc_gamblebk gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$this->userInfo['userid'], 'q.game_type' => $game_type, 'gm.play_type' => $string, 'gm.tradeCoin' => $where4])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();
        }

        if($list) {
            foreach ($list as $k => $v) {
                $userInfo = M('FrontUser')->where(['id' => $v['user_id']])->field('lv,lv_bet,lv_bk,nick_name,head,fb_gamble_win,fb_bet_win,bk_gamble_win')->find();
                $list[$k]['nick_name'] = $userInfo['nick_name'];
                $list[$k]['lv'] = $gamebleType == 2 ? $userInfo['lv_bet'] : ($game_type == 1 ? $userInfo['lv'] : $userInfo['lv_bk']);
                $list[$k]['face'] = frontUserFace($userInfo['head']);
                $list[$k]['tenGamble'] = D('GambleHall')->getTenGamble($v['user_id'], $game_type, $gamebleType) ?: [];
                $list[$k]['gambleInfo'] = D('GambleHall')->getGambleInfo($v['gamble_id'], $game_type);
//                $list[$k]['curr_victs'] = D('GambleHall')->getWinning($v['user_id'], $game_type, 0, $gamebleType, 30)['curr_victs'];

                if($game_type == 1){
                    $list[$k]['curr_victs'] = $gamebleType == 1 ? $userInfo['fb_gamble_win'] : $userInfo['fb_bet_win'];
                }else{
                    $list[$k]['curr_victs'] = $userInfo['bk_gamble_win'];
                }

                $list[$k]['gambleInfo']['bet_code'] = '';
                if ($gamebleType == 2) $list[$k]['gambleInfo']['bet_code'] = M('FbBetodds')->where(['game_id' => $v['game_id']])->getField('bet_code');

                $list[$k]['gambleInfo']['game_id'] = $v['game_id'];
                $list[$k]['gambleInfo']['game_type'] = $game_type ?: 1;
                unset($list[$k]['gamble_id']);
            }
        }

        $this->ajaxReturn(['tradeList'=>$list ?: []]);
    }

    //身份认证
    public function confirmID($okReturn = true)
    {
        /*暂时放开限制
        if (!preg_match("/^[\x{4e00}-\x{9fa5}]{2,}+$/u",$this->param['true_name']))
            $this->ajaxReturn(1020);

        if (!Think\Tool\Tool::validateIDCard($this->param['identfy']))
            $this->ajaxReturn(1021);
        */
        //是否已经存在身份证信息
        $userInfo = M('FrontUser')->field(['true_name','identfy'])->where(['id'=>$this->userInfo['userid']])->find();

        if ($userInfo['true_name'] && $userInfo['identfy'])
            $this->ajaxReturn(1022);

        //身份证不可以重复
        $isIdentfy = M('FrontUser')->where(['identfy'=>$this->param['identfy']])->find();

        if ($isIdentfy)
            $this->ajaxReturn(1045);

        //绑定身份证信息
        $data = ['true_name'=>$this->param['true_name'],'identfy'=>$this->param['identfy']];

        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save($data) === false)
            $this->ajaxReturn(1023);

        if ($okReturn)
            $this->ajaxReturn($data);
    }

    //修改密码
    public function changePwd()
    {
        if ($this->param['smsCode'] == null || S(md5(C('smsPrefix').$this->userInfo['username']))['rank'] != $this->param['smsCode'])
            $this->ajaxReturn(1007);

        if (md5($this->param['oldPassword']) != M('FrontUser')->where(['id'=>$this->userInfo['userid']])->getField('password'))
            $this->ajaxReturn(1024);

        if (!Think\Tool\Tool::checkPassword($this->param['password']))
            $this->ajaxReturn(1006);

        if ($this->param['password'] != $this->param['rePassword'])
            $this->ajaxReturn(1025);

        $newPwd = md5($this->param['password']);

        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save(['password'=>$newPwd]) === false)
            $this->ajaxReturn(1026);

        //重新设置token
        $this->userInfo['password'] = $newPwd;
        S($this->param['userToken'],$this->userInfo,C('loginLifeTime'));

        $this->ajaxReturn(['result'=>1]);
    }

    //修改简介
    public function editDescript()
    {
        if(empty($this->param['descript']))
            $this->ajaxReturn(1086);

        if (Think\Tool\Tool::utf8_strlen($this->param['descript']) > 20)
            $this->ajaxReturn(1027);

        if(!matchFilterWords('FilterWords',$this->param['descript']))
            $this->ajaxReturn(1061);

        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save(['descript'=>$this->param['descript']]) === false)
            $this->ajaxReturn(1028);

      $this->ajaxReturn(['descript'=>$this->param['descript']]);
    }

    //上传头像
    public function uploadFace()
    {
        $imgData = $this->param['face'];

        if (empty($imgData))
            $this->ajaxReturn(1029);

        $result = D('Uploads')->uploadFileBase64($imgData, "user", "face", "200", $this->userInfo['userid']);
        if($result['status'] == 1)
            M("frontUser")->where(['id'=>$this->userInfo['userid']])->save(['head'=>$result['url']]);
        else
            $this->ajaxReturn(1031);

        $this->ajaxReturn(['result'=>1,'faceUrl'=>Think\Tool\Tool::imagesReplace($result['url'])]);
    }


    //上传头像
    public function test()
    {
        $imgData = $this->param['face'];

        if (empty($imgData))
            $this->ajaxReturn(1029);

        $result = D('Uploads')->uploadFileBase64($imgData, "user", "face", "200", $this->userInfo['userid']);
        if($result['status'] == 1)
            M("frontUser")->where(['id'=>$this->userInfo['userid']])->save(['head'=>$result['url']]);
        else
            $this->ajaxReturn(1031);

        $this->ajaxReturn(['result'=>1,'faceUrl'=>Think\Tool\Tool::imagesReplace($result['url'])]);
    }

    //积分兑换
    public function exchange()
    {
        $config = getWebConfig('platformSetting');
        $userPoint = M('FrontUser')->where(['id'=>$this->userInfo['userid']])->getField('point');

        if ($userPoint < $config['pointLimit'])
            $this->ajaxReturn(1032);

        $exchPoint = $config['point2Coin'.$this->param['exchangeNo']];
        $exchCoin  = $config['coin'.$this->param['exchangeNo']];

        if (!$exchPoint || !$exchCoin)
            $this->ajaxReturn(1033);

        $leftPoint = $userPoint - $exchPoint;

        if ($leftPoint < 0)
            $this->ajaxReturn(1034);

        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save(['point'=>$leftPoint,'coin'=>['exp','coin+'.$exchCoin]]) == false)
            $this->ajaxReturn(1035);

        //增加积分记录
        $insertPointLog = M('PointLog')->add([
            'user_id'     => $this->userInfo['userid'],
            'log_time'    => time(),
            'log_type'    => 6,
            'change_num'  => $exchPoint,
            'total_point' => $leftPoint,
            'desc'        => '您已使用'.$exchPoint.'积分兑换'.$exchCoin.'金币'
        ]);

        //添加球币记录
        $userCoin = M('FrontUser')->master(true)->field(['coin','unable_coin'])->where(['id'=>$this->userInfo['userid']])->find();
        $insertCoinLog = M('AccountLog')->add([
            'user_id'    =>  $this->userInfo['userid'],
            'log_time'   =>  time(),
            'log_type'   =>  6,
            'log_status' =>  1,
            'change_num' =>  $exchCoin,
            'total_coin' =>  $userCoin['coin']+$userCoin['unable_coin'],
            'desc'       =>  '您已使用'.$exchPoint.'积分兑换'.$exchCoin.'金币',
            'platform'   =>  $this->userInfo['platform']
        ]);


        $this->ajaxReturn(['point'=>$leftPoint,'coin'=>$userCoin['coin'],'unable_coin'=>$userCoin['unable_coin']]);
    }

    //进入提款界面
    public function extractTpl()
    {
        $userInfo = M('FrontUser')->field(['coin','true_name','identfy','bank_name','bank_card_id','coin'])->find($this->userInfo['userid']);
        $iosExtractMoney = getWebConfig('common')['iosExtractMoney'];//提款限制金额
        if($userInfo['coin'] < $iosExtractMoney)
            $this->ajaxReturn(1081);

        $bankList = M('Bank')->getField('bank_name',true);
        $this->ajaxReturn(['userInfo'=>$userInfo,'bankList'=>$bankList]);
    }

    //绑定银行卡
    public function bindBankCard()
    {
        if (!$this->param['bank_name'])
            $this->ajaxReturn(1047);

        if (!$this->param['province'] || !$this->param['city'])
            $this->ajaxReturn(1048);

        if (!is_numeric($this->param['bank_card_id']))
            $this->ajaxReturn(1046);

        if (strlen($this->param['bank_extract_pwd']) != 6 || !is_numeric($this->param['bank_extract_pwd']))
            $this->ajaxReturn(1036);

        $trueName = M('FrontUser')->where(['id'=>$this->userInfo['userid']])->getField('true_name');

        if (!$trueName)
            $this->ajaxReturn(1037);

        if ($trueName != $this->param['true_name'])
            $this->ajaxReturn(1038);

        $data = [
            'bank_name'        => $this->param['bank_name'],
            'bank_card_id'     => $this->param['bank_card_id'],
            'bank_region'      => $this->param['province'] .' '. $this->param['city'],
            'bank_extract_pwd' => md5($this->param['bank_extract_pwd'])
        ];

        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save($data) === false)
            $this->ajaxReturn(1039);

        $this->ajaxReturn(['bank_name'=>$this->param['bank_name'],'bank_card_id'=>$this->param['bank_card_id']]);
    }

    //提款绑定
    public function extractBind(){

        //参数验证
        if (strlen($this->param['bank_extract_pwd']) != 6 || !is_numeric($this->param['bank_extract_pwd']))
            $this->ajaxReturn(1036);

        switch($this->param['bind_type']){

            case '1':
                if (!$this->param['bank_name'])
                    $this->ajaxReturn(1047);

                if (!$this->param['province'] || !$this->param['city'])
                    $this->ajaxReturn(1048);

                if (!is_numeric($this->param['bank_card_id']))
                    $this->ajaxReturn(1046);

                //银行卡号只能一个用户绑定一个，多个则不能绑定
                if(M('FrontUser')->master(true)->where(['bank_card_id' => $this->param['bank_card_id']])->count())
                    $this->ajaxReturn(1080);

                $data = [
                    'bank_name'        => $this->param['bank_name'],
                    'bank_card_id'     => $this->param['bank_card_id'],
                    'bank_region'      => $this->param['province'] .' '. $this->param['city'],
                    'bank_extract_pwd' => md5($this->param['bank_extract_pwd'])
                ];
                $retArr = [
                    'bank_name'        => $this->param['bank_name'],
                    'bank_card_id'     => $this->param['bank_card_id'],
                ];
                break;

            case '2':

                if ($this->param['alipay_id'] == '')
                    $this->ajaxReturn(1067);

                //支付宝账号只能一个用户绑定一个，多个则不能绑定
                if(M('FrontUser')->master(true)->where(['alipay_id' => $this->param['alipay_id']])->count())
                    $this->ajaxReturn(1080);

                $data = [
                    'alipay_id'        => $this->param['alipay_id'],
                    'bank_extract_pwd' => md5($this->param['bank_extract_pwd'])
                ];
                $retArr = [
                    'alipay_id'        => $this->param['alipay_id'],
                ];
                break;

            default:
                $this->ajaxReturn(101);
        }

        //账户验证
        $bindInfo = M('FrontUser')->field('true_name,alipay_id,bank_card_id')->where(['id'=>$this->userInfo['userid']])->find();

        if($bindInfo['bank_card_id'])
            $this->ajaxReturn(1068);

        if ($bindInfo['alipay_id'])
            $this->ajaxReturn(1069);

        if(!$bindInfo['true_name'])
            $this->ajaxReturn(1037);

        if ($bindInfo['true_name'] != $this->param['true_name'])
            $this->ajaxReturn(1038);

        //执行绑定
        if ( M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save($data) === false )
            $this->ajaxReturn(1039);

        $this->ajaxReturn($retArr);

    }

    //提款申请
    public function extract()
    {
        //判断是否60秒前是否已经提款过
        if(S('extract_'.$this->userInfo['userid']))
            $this->ajaxReturn(4019);

        $iosExtractMoney = getWebConfig('common')['iosExtractMoney'];//提款限制金额

        if (
            $this->param['extractNum'] < $iosExtractMoney || $this->param['extractNum'] > 10000
            || !is_numeric($this->param['extractNum'])
            || floor($this->param['extractNum']) != $this->param['extractNum']
        )
        {
            $this->ajaxReturn(1041,'提款金额在'.$iosExtractMoney.'-10000之间整数');
        }

        if($this->param['extractNum'] < $iosExtractMoney)
            $this->ajaxReturn(1081,'可提金币少于'.$iosExtractMoney.'，不能提款');

        $this->param['extractNum'] = floor($this->param['extractNum']);

        $field = ['coin','unable_coin','bank_name','bank_card_id','bank_extract_pwd','alipay_id'];
        $userInfo = M('FrontUser')->master(true)->field($field)->where(['id'=>$this->userInfo['userid']])->find();

        if(!($userInfo['bank_name'] && $userInfo['bank_card_id']) && !$userInfo['alipay_id'] || !$userInfo['bank_extract_pwd'])
            $this->ajaxReturn(1040);

        if ($this->param['extractNum'] > $userInfo['coin'])
            $this->ajaxReturn(1043);

        if (md5($this->param['bank_extract_pwd']) != $userInfo['bank_extract_pwd'])
            $this->ajaxReturn(1042);

        //每天只能申请提款一次
        $where = [
            'user_id'  => $this->userInfo['userid'],
            'log_type' => 2,
            'log_time' => ['BETWEEN',[strtotime("today"),strtotime("today")+86400]]
        ];

        if (M('AccountLog')->master(true)->where($where)->find())
            $this->ajaxReturn(1049);

        //提款
        M()->startTrans();

        $rs1 = M('AccountLog')->add([
                            'user_id'    => $this->userInfo['userid'],
                            'log_time'   => NOW_TIME,
                            'log_type'   => 2,
                            'change_num' => $this->param['extractNum'],
                            'total_coin' => $userInfo['coin']+$userInfo['unable_coin']-$this->param['extractNum'],
                            'desc'       => "提款申请",
                            'platform'   => $this->userInfo['platform'],
                        ]);

        $rs2 = M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save([
                            'coin'        => ['exp','coin-'.$this->param['extractNum']],
                            'frozen_coin' => ['exp','frozen_coin+'.$this->param['extractNum']]
                        ]);

        if (!$rs1 || !$rs2)
        {
            M()->rollback();
            $this->ajaxReturn(1044);
        }

        M()->commit();

        //保存提款记录时间，保存1分钟
        S('extract_'.$this->userInfo['userid'], NOW_TIME, 60);

        $this->ajaxReturn(['coin'=>(string)$userInfo['coin']-$this->param['extractNum']]);
    }

    //发送短信验证码
    public function sendCode()
    {
        if (!$this->userInfo['username'])
            $this->ajaxReturn(1060);

        $sendResult = sendCode($this->userInfo['username'],$this->param['codeType']);

        if ($sendResult === -1)
            $this->ajaxReturn(1059);

        if (!$sendResult)
            $this->ajaxReturn(1004);

        $this->ajaxReturn(['registe'=>1, 'type' => ($sendResult['mobileSMS'] == 3) ? '2' : '1']);
    }

    //修改提款密码
    public function changeExtractPwd()
    {
        //是否检测密码，0：检测；1：不检测
        $no_check = $this->param['no_check'] ? (int)$this->param['no_check'] : 0;

        //旧接口需要检测验证码和旧密码
        if($no_check == 0){
            if ($this->param['smsCode'] == null || S(md5(C('smsPrefix').$this->userInfo['username']))['rank'] != $this->param['smsCode'])
                $this->ajaxReturn(1007);

            if (md5($this->param['oldPassword']) != M('FrontUser')->where(['id'=>$this->userInfo['userid']])->getField('bank_extract_pwd'))
                $this->ajaxReturn(1024);
        }

        if (strlen($this->param['password']) != 6 || !is_numeric($this->param['password']))
            $this->ajaxReturn(1036);

        if ($this->param['password'] != $this->param['rePassword'])
            $this->ajaxReturn(1025);

        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save(['bank_extract_pwd'=>md5($this->param['password'])]) === false)
            $this->ajaxReturn(1026);

        $this->ajaxReturn(['result'=>1]);
    }

    //苹果内购增加金币
    public function applePurchase()
    {
        if ($this->param['platform'] != 2) //必须是苹果平台
            $this->ajaxReturn(403);

        //请求验证参数票据
        $bodyString = $this->param['data'];
        if(empty($bodyString)){
            $this->ajaxReturn(5001);
        }

        //登录判断
        $userId  = $this->userInfo['userid'];
        $result  = ExecutiveIosRecharge($userId,$bodyString);
        if(!is_array($result)){
            $this->ajaxReturn($result);
        }

        $this->ajaxReturn($result);
    }

    //完善资料，第三方和已经注册都会来 true_name、identfy、mobile、password、smsCode
    public function perfectInfo()
    {
        //是否检测密码，0：检测；1：不检测
        $is_check = $this->param['is_check'] ? (int)$this->param['is_check'] : 0;

        //检测密码
        if ($is_check == 0 && !\Think\Tool\Tool::checkPassword($this->param['password']))
            $this->ajaxReturn(1006);

        //校验验证码
        if ($this->param['smsCode'] == null || S(md5(C('smsPrefix') . $this->param['mobile']))['rank'] != $this->param['smsCode'])
            $this->ajaxReturn(1007);

        $userInfo = M('FrontUser')->field(['username mobile','coin','true_name','identfy','bank_name','bank_card_id'])->find($this->userInfo['userid']);

        //没有绑定手机就验证
        if(!$userInfo['mobile']){
            //手机号码不能用别人的，可以是自己的
            if(M('FrontUser')->where(['username' => $this->param['mobile'], 'id' => ['NEQ', $this->userInfo['userid']]])->count())
                $this->ajaxReturn(1003);
        }

        //身份认证
        if(!($userInfo['true_name'] || $userInfo['identfy']) && $this->param['true_name'] && $this->param['identfy'])
            $this->confirmID(0);

        $area_code = $this->param['area_code'] ? (string)$this->param['area_code'] : '';
        if(!in_array($area_code, array('', '852', '853', '886')))
            $this->ajaxReturn(101);

        //执行绑定
        $pwd  = ($is_check == 0) ? md5($this->param['password']) : '';
        $data = array('username' => $this->param['mobile'], 'password' => $pwd, 'area_code' => $area_code ? '00'.$area_code : '');
        $re   = M('FrontUser')->where('id='.$this->userInfo['userid'])->save($data);

        if ($re===false)
            $this->ajaxReturn(1058);

        //重置token
        $this->userInfo['password'] = $pwd;
        $this->userInfo['username'] = $this->param['mobile'];
        S($this->param['userToken'],$this->userInfo,C('loginLifeTime'));

        $this->ajaxReturn(['result'=>1]);
    }

    /**
     * 用户邀请信息
     */
    public function userInvitation(){
        //邀请信息
        $info = M('InviteInfo')->field('first_num, second_num, third_num, total_num, first_coin, second_coin, third_coin, total_coin, valid_coin, invalid_coin, await_coin')
            ->where(['user_id' => $this->userInfo['userid']])->find();

        //邀请码
        $code = M('FrontUser')->where(['id' => $this->userInfo['userid']])->getField('invitation_code');
        if (!$code) {
            $code = D('FrontUser')->getInvitationCode($this->userInfo['userid']);
            M('FrontUser')->where(['id' => $this->userInfo['userid']])->save(['invitation_code' => $code]);
            M('InviteInfo')->add(['user_id' => $this->userInfo['userid'], 'create_time' => NOW_TIME]);//先入库，以便后来邀请的人使用
        }

        //邀请达人榜每天12点更新一次
        if((time() >= strtotime('12:00') && time() <= strtotime('12:01')) || !S('invitationList'.MODULE_NAME)){
            $list = M('InviteInfo i')->field('i.user_id, i.total_coin, u.nick_name, u.head as face')
                ->join(' LEFT JOIN qc_front_user u on i.user_id = u.id ')
                ->order('i.total_coin desc, i.total_num desc, i.first_num desc, i.second_num desc, i.third_num desc')
                ->limit(10)->select();

            S('invitationList'.MODULE_NAME, json_encode($list), 3600 * 24);//保存24小时
        }else{
            $list = S('invitationList'.MODULE_NAME);
        }

        foreach($list as $k => $v){
            $list[$k]['face'] = frontUserFace($v['face']);
        }

        $this->inviteConf = getWebConfig('invite');
        $this->info = $info;
        $this->code = $code;
        $this->list = $list;
        $this->userToken = $this->param['userToken'];
        $this->platform  = $this->param['platform'] ?: 0;

        $detailUrl = '/'.MODULE_NAME.'/Index/inviteDetail.html?'.http_build_query(array_filter($this->param));
        $this->assign('detailUrl',$detailUrl);

        if(I('platform') == 2){
            $this->display(T('User/inviteIndex_ios'));
        }else{
            $this->display(T('User/inviteIndex'));
        }
    }

    /**
     * 邀请好友过程（别人输入邀请码）
     */
    public function invitationProcess(){
        //开关配置，判断是否为开状态
        $commonConf = getWebConfig('common');
        if($commonConf['invite'] == 0)
            $this->ajaxReturn(403);

        //先判断邀请是否正确
        if(empty($this->param['invitation_code']))
            $this->ajaxReturn(101);

        //判断该用户是否是11月1后注册的 => strtotime('2016-11-01 00:00')，且要注册后30天之内填写才行
        $reg_time = M('FrontUser')->where(['id' => $this->userInfo['userid']])->getField('reg_time');
        if($reg_time < strtotime('2016-11-01 00:00') || NOW_TIME - $reg_time > 60*60*24*30)
            $this->ajaxReturn(6021);

        //推荐人id
        $recommend_id = M('FrontUser')->master(true)->where(['invitation_code' => $this->param['invitation_code'], 'invite_status' => 1])->getField('id');
        if (!$recommend_id)
            $this->ajaxReturn(6017);

        //判断是否为自己的邀请码
        $code_id = M('FrontUser')->master(true)->where(['invitation_code' => $this->param['invitation_code']])->getField('id');
        if ($code_id == $this->userInfo['userid'])
            $this->ajaxReturn(6020);

        //判断当前用户是否已经有上级和下级
        $pre  = M('FrontUser')->master(true)->where(['id' => $this->userInfo['userid']])->getField('recommend_id');
        $next = M('FrontUser')->master(true)->where(['recommend_id' => $this->userInfo['userid']])->count();
        if($pre > 0 || $next > 0)
            $this->ajaxReturn(6019);

        $inviteConf = getWebConfig('invite');

        //邀请好友注册过程
        $res = D('FrontUser')->inviteProcess($this->userInfo['userid'], $recommend_id, $inviteConf);

        if($res != 1)
            $this->ajaxReturn(4002);

        $this->ajaxReturn(['result'=>'1', 'num' => (string)$inviteConf[0]]);
    }

    /**
     * 数据重置
     */
    public function resetData()
    {
        if(empty($this->param['gambleType']) || !in_array($this->param['gambleType'], array(1,2)))
            $this->ajaxReturn(101);

        $result = D('GambleHall')->resetGambleData($this->userInfo['userid'], $this->userInfo['platform'], $this->param['gambleType']);

        if($result != 1) {
            $this->ajaxReturn($result);
        } else {
            $this->ajaxReturn(['result'=>'1']);
        }
    }

    /**
     * 绑定充值成功后送5金币
     */
    public function bindPayCoin(){
        $user_id   = $this->userInfo['userid'];
        $chang_num = (int)getWebConfig('recharge')['rechargeBind'];
        $trade_no  = $this->param['trade_no'] ?: '';
        $order     = M('tradeRecord')->where(['trade_no'=>$trade_no])->field("trade_state, platform")->find();

        if(empty($trade_no) || !in_array($order['trade_state'], [1, 2]))
            $this->ajaxReturn(101);

        //只能赠送一次
        if(M('AccountLog')->where(['user_id' => $user_id, 'log_type' => 5, 'desc' => '绑定充值赠送'])->count()){
            $this->ajaxReturn(['result' => '2']);
        }

        $user = M('FrontUser')->field('coin, unable_coin')->where(['id' => $user_id])->find();
        $rs   = M('FrontUser')->where(['id'=>$user_id])->save(['coin'=>['exp', "coin+{$chang_num}"]]);
        if($rs){
            $array = array(
                'user_id'   => $user_id,
                'log_type'  => 5,
                'log_status'=> 1,
                'log_time'  => time(),
                'change_num'=> $chang_num,
                'total_coin'=> $user['coin'] + $user['unable_coin'] + $chang_num,
                'desc'      => '绑定充值赠送',
                'platform'  => $order['platform'],
                'order_id'  => $trade_no,
                'operation_time' => time(),
            );

            //添加记录
            $rs1 = M('AccountLog')->add($array);
            if($rs1 === false){
                logRecord("金币添加 绑定充值赠送记录 ：".M()->getLastsql().'====>'.$rs1,'logWx.txt');
                $this->ajaxReturn(['result' => '2']);
            }
        }else{
            logRecord("金币添加 FrontUser绑定充值赠送记录 ：".M()->getLastsql().'====>'.$rs,'logWx.txt');
            $this->ajaxReturn(['result' => '2']);
        }

        $this->ajaxReturn(['result' => '1']);
    }

    /**
     * 忘记密码校对数据
     */
    public function checkInfo()
    {
        if(empty($this->param['true_name']) || empty($this->param['identfy']) || empty($this->param['mobile']) || empty($this->param['smsCode']))
            $this->ajaxReturn(101);

        $res = [1 => '', 2 => '', 3 => '', 4 => ''];

        $userInfo = M('FrontUser')->field(['username mobile','coin','true_name','identfy','bank_name','bank_card_id'])->find($this->userInfo['userid']);
        //身份认证，真实姓名和身份证
        if($userInfo['true_name'] != (string)$this->param['true_name'])
            $res[1] = '填写的姓名与绑定的不相符';

        if($userInfo['identfy'] != (string)$this->param['identfy'])
            $res[2] = '填写的身份证与绑定的不相符';

        //手机号码校对
        if($userInfo['mobile'] != (string)$this->param['mobile'])
            $res[3] = '填写的手机号码与绑定的不相符';

        //校验验证码
        if ($this->param['smsCode'] == null || S(md5(C('smsPrefix') . $this->param['mobile']))['rank'] != $this->param['smsCode'])
            $res[4] = '填写的验证码与绑定的不相符';

        $this->ajaxReturn(['result'=>$res]);
    }

    /**
     * 我的体验券
     */
    public function myTicket(){
        $page    = $this->param['page'] ?: 1;
        $pageNum = 20;
        $type    = $this->param['type'] ?: 0;//可用：0,；不可用：1

        $where['user_id']   = $this->userInfo['userid'];
        $where['status']    = 1;
        $where1 = $where2 = $where;

        //可用就是没有用过，且没有过期；
        $where1['is_use']    = 0;
        $where1['over_time'] = ['gt', NOW_TIME];

        //不可用包括已经使用，30日内和未使用已过期，且在30天内
        $startTime = NOW_TIME - 3600 * 24 * 30;
        $endTime   = NOW_TIME;
        $where2['_string']  = " (is_use = 1 AND use_time > {$startTime} AND use_time < {$endTime}) OR (is_use = 0 AND  over_time > {$startTime} AND over_time < {$endTime}) ";

        if($type == 0){
            $order = ' id desc ';
            $where3 = $where1;
        }else{
            $order = ' use_time desc ';
            $where3 = $where2;
        }

        $fields = ' id, name, type, price, IF(give_coin = 0, price, give_coin) as give_coin, over_time, get_type, remark, is_use ';
        $res    = M('TicketLog')->field($fields)->where($where3)->page($page . ',' . $pageNum)->order($order)->select();

        //ios金币改Q币
//        if($this->param['platform'] == 2){
        foreach($res as $k => &$v){
            $v['name'] = str_replace('金', 'Q', $v['name']);
        }
//        }

        $num1   = M('TicketLog')->where($where1)->count();//可用总数
        $num2   = M('TicketLog')->where($where2)->count();//不可用总数

        unset($where, $where1, $where2, $where3);
        $this->ajaxReturn(['result'=>(array)$res, 'num1' => (string)$num1, 'num2' => (string)$num2]);
    }

    /**
     * 球王订购
     * 1、我的订购。显示已经发布的
     */
    public function diegoOrder(){
        $page = $this->param['page']?:1;
        $start = ($page - 1) * 20;
        $bug_log = M('IntroBuy')->alias('B')
            ->field('B.product_id,P.name,P.total_rate as totalRate,P.rate_num,P.total_num,P.game_num,P.sale,P.logo,B.list_id,L.pub_time,L.remain_num')
            ->join('LEFT JOIN qc_intro_products P ON P.id = B.product_id')
            ->join('LEFT JOIN qc_intro_lists L ON L.id = B.list_id')
            ->where(['B.user_id' => $this->userInfo['userid']])
            ->order('B.create_time DESC')
            ->limit($start, 20)
            ->select();
        foreach($bug_log as $key => $val){
            $res = [];
            $published = $val['pub_time'] && $val['pub_time'] < NOW_TIME &&  $val['list_id'] ? '1' : '0';
            $bug_log[$key]['published'] = $published;

            if($published == '1'){
                $res = M('IntroGamble')->alias('G')
                    ->join('LEFT JOIN qc_union U ON U.union_id = G.union_id')
                    ->field('G.game_id,G.union_id,U.union_color,G.union_name,G.gtime,G.home_team_name,G.away_team_name,G.score,G.handcp,G.odds,G.chose_side,G.play_type,G.result')
                    ->where(['G.list_id' => $val['list_id']])->order('gtime ASC')->select();

                foreach($res as $k => $v){
                    $res[$k]['union_name']      = explode(',', $v['union_name']);
                    $res[$k]['home_team_name']  = explode(',', $v['home_team_name']);
                    $res[$k]['away_team_name']  = explode(',', $v['away_team_name']);
                    $res[$k]['score'] = (string)$v['score'];

                    unset($res[$k]['create_time']);
                    unset($res[$k]['pub_time']);
                    unset($res[$k]['union_id']);
                }
                $bug_log[$key]['buy_num'] = (string)($val['total_num'] - $val['remain_num']);
            }else{
                $blockTime = getBlockTime(1, true);
                $num = M('IntroBuy')
                    ->where(['product_id' => $val['product_id'], 'create_time' => ['between', [$blockTime['beginTime'], $blockTime['endTime']]]])->count();

                $bug_log[$key]['buy_num'] =  $num > $val['total_num'] ? $val['total_num'] : $num;
            }

            $bug_log[$key]['logo'] = $val['logo'] ? Think\Tool\Tool::imagesReplace($val['logo']) :'';
            $bug_log[$key]['win_rate']  = sprintf('进%s中%s',C('introRateNum'),$val['rate_num']);
            $bug_log[$key]['gamble'] = $res?:[];
            unset( $bug_log[$key]['list_id']);
            unset( $bug_log[$key]['remain_num']);
            unset( $bug_log[$key]['ten_num']);
        }

        $this->ajaxReturn(['list' => $bug_log?:[]]);
    }

    /**
     * 球王订购-最新发布
     *
     */
    public function newIntro(){
        $page = $this->param['page']?:1;
        $start = ($page - 1) * 10;
        $blockTime = getBlockTime(1, true);

        //已关注的产品、当天有推介、比赛未完场
        $list = M('IntroFollow')->master(true)->alias('F')
            ->field('G.list_id,P.id product_id,P.name,P.total_rate as totalRate,P.rate_num,P.total_num,P.game_num,P.sale,P.logo,G.create_time')
            ->join('LEFT JOIN qc_intro_gamble G ON F.product_id = G.product_id')
            ->join('LEFT JOIN qc_intro_products P ON P.id = F.product_id')
            ->where(['user_id' => $this->userInfo['userid'],'G.result' => ['EQ','0']])
            ->group('G.list_id')
            ->having("G.create_time  between {$blockTime['beginTime']} and {$blockTime['endTime']}")
            ->order('F.id DESC')
            ->limit($start,20)->select();

        //比赛详情
        foreach($list as $key => $val){
            $list[$key]['logo'] =  $val['logo'] ? Think\Tool\Tool::imagesReplace($val['logo']) : '';
            //订购数量
            $intro = M('IntroLists')->where(['id' => $val['list_id']])->find();
            $list[$key]['buy_num'] = $val['total_num'] - $intro['remain_num'];
            $list[$key]['win_rate']  = sprintf('进%s中%s',C('introRateNum'),$val['rate_num']);

            unset($list[$key]['create_time']);
            unset($list[$key]['ten_num']);
        }

        $this->ajaxReturn(['list' => $list?:[]]);

    }

    /**
     * 专家资料申请接口
     */
    public function checkExpertInfo(){
        //校验验证码
        if ($this->param['smsCode'] == null || S(md5(C('smsPrefix') . $this->param['mobile']))['rank'] != $this->param['smsCode'])
            $this->ajaxReturn(1007);

        $data['username'] = $this->param['mobile'];
        //个人简介
        if (Think\Tool\Tool::utf8_strlen($this->param['descript']) > 20)
            $this->ajaxReturn(1027);

        if(!matchFilterWords('FilterWords',$this->param['descript']))
            $this->ajaxReturn(1061);

        $data['descript'] = $this->param['descript'];

        //专家擅长
        $data['be_good_at'] = $this->param["be_good_at"];

        //头像
        if ($this->param['face']) {
            $result = D('Uploads')->uploadFileBase64($this->param['face'], "user", "face", "200", $this->userInfo['userid']);
            if ($result['status'] == 1)
                $data['head'] = $result['url'];
            else
                $this->ajaxReturn(1031);
        }

        //身份证，真实姓名，身份认证
        if($this->param['true_name'])
            $data['true_name'] = $this->param['true_name'];

        if($this->param['identfy']){
            //身份证不可以重复
            $isIdentfy = M('FrontUser')->where(['identfy'=>$this->param['identfy'], 'id' => ['neq', $this->userInfo['userid']]])->find();
            if ($isIdentfy)
                $this->ajaxReturn(1045);

            $data['identfy'] = $this->param['identfy'];
        }

        //证件照
        if(empty($this->param['identfy_pic']))
            $this->ajaxReturn(1083);

        $result = D('Uploads')->uploadFileBase64($this->param['identfy_pic'], "user", "identfy", $this->userInfo['userid']);
        if ($result['status'] == 1)
            $data['identfy_pic'] = $result['url'];
        else
            $this->ajaxReturn(1084);
        
        $data['expert_status'] = 2;//审核状态
        $data['expert_register_time'] = time(); //注册时间
        $res = M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save($data);
        if($res === false)
            $this->ajaxReturn(4002);

        $this->ajaxReturn(['result' => '1']);
    }

    //开通模型预测会员
    public function openPredictiveModelVip()
    {
        //登录判断
        $user  = $this->userInfo;
        $userId = $user['userid'];

        //开通会员配置
        $predictiveModelVipConfig = getWebConfig('PredictiveModelConfig')['vipConfig'];
        $openPrice = $predictiveModelVipConfig['price']; //价格
        $openTime  = $predictiveModelVipConfig['days'] * 86400;  //有效天数

        //判断金币是否足够
        if($user['balance'] < $openPrice){
            $this->ajaxReturn(1089);
        }

        if($user['unable_coin'] >= $openPrice){
            //使用不可提金币
            $saveUser['unable_coin'] = ['exp','unable_coin-'.$openPrice];
        }else{
            //不可提金币为0，剩下的使用可提金币
            $saveUser['unable_coin'] = 0;
            $saveUser['coin'] = ['exp','coin-'.($openPrice - $user['unable_coin'])];
        }

        //根据会员状态处理会员时间
        if($user['is_model_vip'] == 1){
            //续费（累加时间）
            $predictive_model_vip = $user['predictive_model_vip'] + $openTime;
            
        }else{
            //开通（当前时间加上会员时间）
            $predictive_model_vip = strtotime(date(Ymd)) + $openTime;
        }
        $saveUser['predictive_model_vip'] = $predictive_model_vip;
 
        //扣除金币并开通会员
        M()->startTrans();
        $rs1 = M('FrontUser')->where(array('id'=>$userId))->save($saveUser);

        if(!$rs1) $this->ajaxReturn(7001);
        
        //首页开通为购买，后面都是续费
        if($user['predictive_model_vip'] == 0){
            $log_type = 25;
            $desc     = '您已成功购买预测模型会员服务';
        }else{
            $log_type = 26;
            $desc     = '您已成功续费预测模型会员服务';
        }
        //添加流水记录
        $accountLogArr = [
            'user_id'    =>  $userId,
            'log_time'   =>  time(),
            'model_overtime' => $predictive_model_vip,
            'log_type'   =>  $log_type,
            'log_status' =>  1,
            'change_num' =>  $openPrice,
            'total_coin' =>  $user['balance'] - $openPrice,
            'desc'       =>  $desc,
            'platform'   =>  $this->param['platform'],
            'operation_time' => time(),
        ];
        $rs2 = M('AccountLog')->add($accountLogArr);

        //发送系统消息
        $overDate = date('Y-m-d',$predictive_model_vip);
        sendMsg($userId, '系统通知','尊敬的'.$user['nick_name'].'，恭喜您成功购买大数据预测服务，使用期限为'.date('Y-m-d').' 至 '.$overDate.'。');
        if(!$rs2){
            M()->rollback();
            $this->ajaxReturn(7001);
        }

        M()->commit();
        $this->ajaxReturn(['result'=>1,'msg'=>'开通成功','date'=>$overDate]);
    }

}
 ?>