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
        $userInfo = M('FrontUser')
                ->field(['nick_name','username','area_code','lv','lv_bet','head face','point','coin','unable_coin','descript','weixin_unionid','sina_unionid','qq_unionid','mm_unionid'])
                ->where(['id'=>$this->userInfo['userid']])
                ->find();

        $userInfo['fansNum']        = M('FollowUser')->where(['follow_id'=>$this->userInfo['userid']])->count();
        $userInfo['face']           = frontUserFace($userInfo['face']);
        $userInfo['weixin_unionid'] = (string)$userInfo['weixin_unionid'];
        $userInfo['sina_unionid']   = (string)$userInfo['sina_unionid'];
        $userInfo['qq_unionid']     = (string)$userInfo['qq_unionid'];
        $userInfo['mm_unionid']     = (string)$userInfo['mm_unionid'];
        $userInfo['username']       = (string)$userInfo['username'];
        $userInfo['area_code']      = (string)$userInfo['area_code'];
        $userInfo['inviteNum']      = (string)M('InviteInfo')->where(['user_id' => $this->userInfo['userid']])->getField('total_coin');

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

        $list = M('Msg')->field(['title','content','is_read','send_time'])
                ->where(['front_user_id'=>$this->userInfo['userid']])
                ->page($page.','.$pageNum)
                ->order('id desc')
                ->select();

        $this->ajaxReturn(['msgList'=>$list]);
    }

    //标识通知已读
    public function readMsg()
    {
        M('Msg')->where(['front_user_id'=>$this->userInfo['userid']])->save(['is_read'=>1]);
        $this->ajaxReturn(['read'=>1]);
    }

    //定时获取新的通知
    public function getNewMsg()
    {
        $num = M('Msg')->where(['front_user_id'=>$this->userInfo['userid'],'is_read'=>0])->count();
        $this->ajaxReturn(['msgNum'=>$num]);
    }

    /**
     * 足球推荐信息
     */
    public function gambleInfo()
    {
        $userid     = $this->userInfo['userid'];
        $playType   = $this->param['play_type'] ?: 0;//默认0，全部，让分：1，大小：-1；竞彩：2
        $gameType   = $this->param['game_type'] ?: 1;//足球：1 篮球：2
        $page       = $this->param['page'] ?: 1;
        $gambleType = 0;

        //只有第一页时返回用户的推荐统计
        if (in_array($playType, [0, 1, -1, 2]) && $page <= 1)
        {
            //竞彩统计
            $userInfo['jc']                     = D('GambleHall')->getWinning($userid, $gameType, 0, 2, 0);
            $userInfo['jc']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($userid, 1, 1, false, false, 0, 2);
            $userInfo['jc']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($userid, 1, 2, false, false, 0, 2);
            $userInfo['jc']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($userid, 1, 3, false, false, 0, 2);

            //亚盘统计
            $userInfo['yp']                     = D('GambleHall')->getWinning($userid, $gameType, 0, 1, 0);//查总的
            $userInfo['yp']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($userid, 1, 1);
            $userInfo['yp']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($userid, 1, 2);
            $userInfo['yp']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($userid, 1, 3);
            $userInfo['yp']['total_times']      = (string)D('GambleHall')->getWinning($userid, $gameType, $playType, 1, 0)['total_times'];//对应玩法的总场数

            //获取用户等级
            $info = M('FrontUser')->field(['lv_bet','lv'])->where(['id' => $userid])->find();
            $userInfo['yp']['lv'] = $info['lv'];
            $userInfo['jc']['lv'] = $info['lv_bet'];

            unset($userInfo['jc']['level']);
        }

        //推荐记录
        if($playType == 2){
            $gambleType = 2;
            $playType = 0;
        }

        $gambleList = D('GambleHall')->getGambleList($userid, $playType, $page, $gamble_id = 0, $gambleType);

        foreach ($gambleList as $k => $v){
            $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id'=>$v['gamble_id']])->count();
        }

        $this->ajaxReturn(['userInfo'=>$userInfo ?:'','gambleList'=>$gambleList]);
    }


    /**
     * 我关注的用户列表 (hzl)
     */
    public function myFollowUser()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $list = M('FollowUser f')->field('f.follow_id user_id,u.nick_name,u.lv, u.lv_bet,f.sub, u.head face,u.descript')
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.follow_id')
                ->where(['f.user_id'=>$this->userInfo['userid']])
                ->page($page.','.$pageNum)
                ->order('f.id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            $list[$k]['face'] = frontUserFace($v['face']);
            $list[$k]['sub'] = (string)$v['sub'];
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
            ->field('f.user_id,u.nick_name,u.lv,u.lv_bet,u.head face,u.descript')
            ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.user_id')
            ->where(['f.follow_id'=>$this->userInfo['userid']])
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
     * 我的关注-最新推荐动态 (hzl)
     */
    public function myFollowGamble()
    {
        $page = $this->param['page'] ?: 1;
        $follows = M('FollowUser fu')
            ->field('u.id user_id, u.nick_name, u.lv, u.lv_bet, u.head face, fu.follow_id')
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

            //粉丝的推荐列表
            $gambleList = D('GambleHall')->getGambleList($followId, $playType = null, $page);

            foreach ($gambleList as $k => $v) {

                $userid = $v['user_id'];

                //是否已经购买查看过
                $userInfo['is_trade'] = D('Common')->getTradeLog($v['gamble_id'], $this->userInfo['userid']);//是否已查看购买过

                unset($v['user_id']);
                unset($userArr[$userid]['follow_id']);
                $list[] = array_merge(['gamble' => $v, 'userInfo' => array_merge($userInfo, $userArr[$userid])]);
            }
        }

        $this->ajaxReturn(['gambleList' => $list ?: []]);
    }

    // 账户明细（全部、支出、收入、待结算）
    public function accountLog()
    {
        $page       = $this->param['page'] ?: 1;
        $pageNum    = 20;
        $userid     = $this->userInfo['userid'];
        $where      = ['user_id'=>$userid];

        $exType     = [2,3,9,12,14,15,17]; //支出的类型

        switch ($this->param['type'])
        {
            case '1':
                $where['log_type']      = ['IN',$exType];
                break;

            case '2':
                $where['log_type']      = ['NOT IN',$exType];
                break;
        }

        if($this->param['type'] == 3)
        {
            $list = M('quizLog q')
                    ->join("RIGHT JOIN qc_gamble g on g.id = q.gamble_id")
                    ->field('g.income,q.log_time')
                    ->where("g.result = 0 AND g.tradeCoin > 0 AND g.quiz_number > 0 AND g.is_back = 0 AND g.user_id = ".$userid)
                    ->page($page.','.$pageNum)
                    ->group("g.id")
                    ->order('q.log_time desc')
                    ->select();

            foreach ($list as $k => $v) 
            {
                $list[$k]['log_type']   = '4';
                $list[$k]['log_status'] = '6';
                $list[$k]['change_num'] = $v['income'];
                $list[$k]['desc']       = '被查看竞猜记录';
                unset($list[$k]['income']);
            }
        }
        else
        {
            $list = M('AccountLog')->field(['log_type','log_status','log_time','change_num','desc'])
                    ->where($where)
                    ->page($page.','.$pageNum)
                    ->order('id desc')
                    ->select();
        }

        foreach ($list as $k => $v)
        {
            $accountStatus = C('accountStatus');
            $list[$k]['status_desc'] = $accountStatus[$list[$k]['log_status']];

            if (in_array($v['log_type'], $exType)){
                $list[$k]['type'] = '1';
            }else{
                $list[$k]['type'] = '2';
            }
        }

        if ($page != 1)
            $this->ajaxReturn(['logList' => $list?:[]]);

        //支出
        $exNum      = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['IN',$exType]])->sum('change_num');
        //收入
        $inNum      = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['NOT IN',$exType]])->sum('change_num');
        //待结算
        $wjsNum     = (int)M('gamble')
                        ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)
                        ->sum('income');
        //余额
        $totalNum   = $inNum - $exNum;

        $this->ajaxReturn(['totalNum'=>$totalNum,'exNum'=>$exNum,'inNum'=>$inNum,'wjsNum' => $wjsNum,'logList'=>$list?:[]]);
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

            switch ($v['log_type']) //金币纠正时显示
            {
                case '18':$list[$k]['desc'] = '竞猜结算纠正增加';break;
                case '19':$list[$k]['desc'] = '竞猜结算纠正扣除';break;
            }
        }

        if ($page != 1)
            $this->ajaxReturn(['logList'=>$list]);

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

        $playType    = $this->param['play_type'] ?: 0;//玩法类型，1：让球；2：大小；
        $gamebleType = $this->param['gameble_type'] ?: 0;//推荐类型(1:亚盘;2:竞彩 )

        if($gamebleType == 1){
            $where1 = ' play_type in (-1,1) ';
            $where2['play_type'] = ['in', [-1,1]];
        }else if($gamebleType == 2){
            $where1 = ' play_type in (-2,2) ';
            $where2['play_type'] = ['in', [-2,2]];
        }

        if($playType){
            $where1 = ' play_type = '.(int)$playType;
            $where2['play_type'] = (int)$playType;
        }

        //旧表和新表的联表
        $subQuery = M('Gamble')->field('id')
                    ->union('SELECT id from qc_gamble_reset WHERE '.$where1, true)
                    ->where($where2)->buildSql();

        $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id','q.game_id'])
                ->join('RIGHT JOIN  '.$subQuery.' gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$this->userInfo['userid']])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            $userInfo               = M('FrontUser')->where(['id'=>$v['user_id']])->field('lv,lv_bet,nick_name,head')->find();
            $list[$k]['nick_name']  = $userInfo['nick_name'];
            $list[$k]['lv']         = $gamebleType == 2 ? $userInfo['lv_bet'] : $userInfo['lv'];
            $list[$k]['face']       = frontUserFace($userInfo['head']);
            $list[$k]['tenGamble']  = D('GambleHall')->getTenGamble($v['user_id'],$gameType=1)?:[];
            $list[$k]['gambleInfo'] = D('GambleHall')->getGambleInfo($v['gamble_id']);
            $list[$k]['curr_victs'] = D('GambleHall')->getWinning($v['user_id'],$gameType=1,$playType,$gamebleType,30)['curr_victs'];

            $list[$k]['gambleInfo']['bet_code'] = '';
            if($gamebleType == 2)
                $list[$k]['gambleInfo']['bet_code'] = M('FbBetodds')->where(['game_id' => $v['game_id']])->getField('bet_code');

            $list[$k]['gambleInfo']['game_id'] = $v['game_id'];
            unset($list[$k]['gamble_id']);
        }

        $this->ajaxReturn(['tradeList'=>$list?:[]]);
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
        if (Think\Tool\Tool::utf8_strlen($this->param['descript']) > 40)
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

        $result = D('Uploads')->uploadFileBase64($imgData, "user", "face", "200", $this->userInfo['userid'], "[[200,200,200]]");
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

        $result = D('Uploads')->uploadFileBase64($imgData, "user", "face", "200", $this->userInfo['userid'], "[[200,200,200]]");
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
            'desc'        => '积分兑换'
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
            'desc'       =>  "积分兑换",
            'platform'   =>  $this->userInfo['platform']
        ]);

        $this->ajaxReturn(['point'=>$leftPoint,'coin'=>$userCoin['coin'],'unable_coin'=>$userCoin['unable_coin']]);
    }

    //进入提款界面
    public function extractTpl()
    {
        $userInfo = M('FrontUser')->field(['coin','true_name','identfy','bank_name','bank_card_id'])->find($this->userInfo['userid']);
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
        $bindInfo = M('FrontUser')->field('true_name,alipay_id,bank_card_id')->where(['id'=>$this->userInfo['userid']])->select()[0];

        if($bindInfo['bank_card_id'])
            $this->ajaxReturn(1068);

        if ($bindInfo['alipay_id'])
            $this->ajaxReturn(1069);

        if($bindInfo['alipay_id']) $this->ajaxReturn(1037);

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

        if (
            $this->param['extractNum'] < 50 || $this->param['extractNum'] > 10000
            || !is_numeric($this->param['extractNum'])
            || floor($this->param['extractNum']) != $this->param['extractNum']
        )
        {
            $this->ajaxReturn(1041);
        }

        $this->param['extractNum'] = floor($this->param['extractNum']);

        $field = ['coin','unable_coin','bank_name','bank_card_id','bank_extract_pwd','alipay_id'];
        $userInfo = M('FrontUser')->master(true)->field($field)->where(['id'=>$this->userInfo['userid']])->find();

        if(!($userInfo['bank_name'] && $userInfo['bank_card_id']) && !$userInfo['alipay_id'] || !$userInfo['bank_extract_pwd'])
            $this->ajaxReturn(1040);

        if ($this->param['extractNum'] > $userInfo['coin'])
            $this->ajaxReturn(1043);

        $iosExtractMoney = getWebConfig('common')['iosExtractMoney'];//提款限制金额
        if($userInfo['coin'] < $iosExtractMoney)
            $this->ajaxReturn(1081);

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
        if ($this->param['smsCode'] == null || S(md5(C('smsPrefix').$this->userInfo['username']))['rank'] != $this->param['smsCode'])
            $this->ajaxReturn(1007);

        if (md5($this->param['oldPassword']) != M('FrontUser')->where(['id'=>$this->userInfo['userid']])->getField('bank_extract_pwd'))
            $this->ajaxReturn(1024);

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
        if ($this->userInfo['platform'] != 2) //必须是苹果平台
            $this->ajaxReturn(403);

        switch ($this->param['payContent'])
        {
            case '1': $payFee = 45; break;
            case '2': $payFee = 90; break;
            default: $this->ajaxReturn(5001); break;
        }

        $user = M('FrontUser')->field('coin,unable_coin')->find($this->userInfo['userid']);

        //为用户增加金币
        if (M('FrontUser')->where(['id'=>$this->userInfo['userid']])->setInc('unable_coin',$payFee) === false)
            $this->ajaxReturn(5004);

        //添加充值账户明细
        $rs2 = M('AccountLog')->add([
            'user_id'   => $this->userInfo['userid'],
            'log_type'  => 8,
            'log_status'=> 1,
            'log_time'  => NOW_TIME,
            'change_num'=> $payFee,
            'total_coin'=> $user['coin'] + $user['unable_coin'] + $payFee,
            'desc'      => '苹果内购',
            'platform'  => 2,
            'pay_way'   => $this->param['sandbox'] != '1' ? 5 : 6, //5：正式上架后的内购充值，6：沙盒内购充值
            'order_id'  => NOW_TIME,
            'operation_time' => NOW_TIME,
        ]);

        $this->ajaxReturn(['result'=>1]);
    }

    //完善资料 true_name、identfy、mobile、password、smsCode
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

        if($userInfo['mobile'])
            $this->ajaxReturn(1064);

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

        $this->display(T('User/inviteIndex'));
    }

    /**
     * 邀请好友过程
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

}


 ?>