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
                ->field(['nick_name','lv','head face','point','coin','unable_coin','descript','weixin_unionid','sina_unionid','qq_unionid','mm_unionid'])
                ->where(['id'=>$this->userInfo['userid']])
                ->find();

        $userInfo['fansNum']        = M('FollowUser')->where(['follow_id'=>$this->userInfo['userid']])->count();
        $userInfo['face']           = frontUserFace($userInfo['face']);
        $userInfo['weixin_unionid'] = (string)$userInfo['weixin_unionid'];
        $userInfo['sina_unionid']   = (string)$userInfo['sina_unionid'];
        $userInfo['qq_unionid']     = (string)$userInfo['qq_unionid'];
        $userInfo['mm_unionid']     = (string)$userInfo['mm_unionid'];
        $userInfo['inviteNum']  = (string)M('InviteInfo')->where(['user_id' => $this->userInfo['userid']])->getField('total_coin');

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

    //足球竞猜信息
    public function gambleInfo()
    {
        $userid   = $this->userInfo['userid'];
        $playType = $this->param['play_type'];//默认0，让分：1，大小：-1
        $page     = $this->param['page'];

        if (in_array($playType, array(0,1,-1)) && $page > 1)
        {
            $gambleList = D('GambleHall')->getGambleList($userid, $playType, $page); //竞猜记录

            foreach ($gambleList as $k => $v)
                $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id'=>$v['gamble_id']])->count();

            $this->ajaxReturn(['gambleList'=>$gambleList]);
        }

        $userInfo                  = D('GambleHall')->getWinning($userid,$gameType=1); //竞猜统计信息
        $userInfo['weekPercnet']   = D('GambleHall')->CountWinrate($userid,1,1);
        $userInfo['monthPercnet']  = D('GambleHall')->CountWinrate($userid,1,2);
        $userInfo['seasonPercnet'] = D('GambleHall')->CountWinrate($userid,1,3);

        $gambleList = D('GambleHall')->getGambleList($userid, $playType, 1); //竞猜记录，默认全部

        foreach ($gambleList as $k => $v)
            $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['game_type' => 1, 'gamble_id'=>$v['gamble_id']])->count();

        $this->ajaxReturn(['userInfo'=>$userInfo,'gambleList'=>$gambleList]);
    }

    //我关注的用户
    public function myFollowUser()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $list = M('FollowUser f')->field('f.follow_id user_id,u.nick_name,u.lv,u.head face,u.descript')
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.follow_id')
                ->where(['f.user_id'=>$this->userInfo['userid']])
                ->page($page.','.$pageNum)
                ->order('f.id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            $winnig                     = D('GambleHall')->getWinning($v['user_id'],1);
            $list[$k]['curr_victs']     = $winnig['curr_victs'];
            $list[$k]['max_victs']      = $winnig['max_victs'];
            $tenGamble                  = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $list[$k]['tenGambleRate']  = (string) countTenGambleRate($tenGamble); //近十场的胜率
            $list[$k]['face']           = frontUserFace($v['face']);
        }

        $this->ajaxReturn(['userList'=>$list]);
    }

    //我的粉丝
    public function myFans()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $list = M('FollowUser f')->field('f.user_id,u.nick_name,u.lv,u.head face,u.descript')
                ->join('LEFT JOIN __FRONT_USER__ u ON u.id = f.user_id')
                ->where(['f.follow_id'=>$this->userInfo['userid']])
                ->page($page.','.$pageNum)
                ->order('f.id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            $tenGamble = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $list[$k]['tenGambleRate'] = (string) countTenGambleRate($tenGamble); //近十场的胜率;

            $winnig =  D('GambleHall')->getWinning($v['user_id'], $gameType = 1);   //连胜记录
            $list[$k]['curr_victs']    = (string) $winnig['curr_victs'];            //连胜场数
            $list[$k]['win']           = (string) $winnig['win']; //胜场

            $list[$k]['face'] = frontUserFace($v['face']);
            $list[$k]['isFollow'] = M('FollowUser')->where(['user_id'=>$this->userInfo['userid'],'follow_id'=>$v['user_id']])->find() ? 1 : 0; //是否已经关注
        }

        $this->ajaxReturn(['userList'=>$list]);
    }

    //关注用户的最新竞猜
    public function myFollowGamble()
    {
        $page     = $this->param['page'] ?: 1;
        $followId = M('FollowUser')->where(['user_id'=>$this->userInfo['userid']])->getField('follow_id',true); //关注用户的id数组
        $list     = D('GambleHall')->getGambleList($followId,$playType=null,$page); //关注用户的竞猜记录


        foreach ($list as $k => $v)
        {
            $winnig                  = D('GambleHall')->getWinning($v['user_id'],$gameType=1); //竞猜统计信息
            $list[$k]['curr_victs']  = $winnig['curr_victs'];
            $list[$k]['max_victs']   = $winnig['max_victs'];
            $tenGamble                 = D('GambleHall')->getTenGamble($v['user_id'], 1);
            $list[$k]['tenGambleRate'] = (string) countTenGambleRate($tenGamble); //近十场的胜率

            $userInfo                = M('FrontUser')->where(['id'=>$v['user_id']])->field('nick_name,lv,head face')->find();
            $list[$k]['nick_name']   = $userInfo['nick_name'];
            $list[$k]['lv']          = $userInfo['lv'];
            $list[$k]['face']        = frontUserFace($userInfo['face']);
            $list[$k]['is_trade']    = M('QuizLog')->where(['game_type' => 1, 'user_id'=>$this->userInfo['userid'],'gamble_id'=>$v['gamble_id']])->getField('id') ? 1 : 0; //是否已经购买查看过
            $list[$k]['weekPercnet'] = D('GambleHall')->CountWinrate($v['user_id'], 1, 1);//周胜率
        }

        $this->ajaxReturn(['gambleList'=>$list]);
    }

    // 账户明细
    public function accountLog()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $where = ['user_id'=>$this->userInfo['userid']];
        $exType = [2,3,9,12,14]; //支出的类型

        switch ($this->param['type'])
        {
            case '1':   $where['log_type'] = ['in',$exType];      break;
            case '2':   $where['log_type'] = ['not in',$exType];  break;
        }

        $list = M('AccountLog')->field(['log_type','log_status','log_time','change_num','desc'])
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
        }

        if ($page != 1)
            $this->ajaxReturn(['logList'=>$list]);

        $exNum = (int)M('AccountLog')->where(['user_id'=>$this->userInfo['userid'],'log_type'=>['in',$exType]])->sum('change_num');
        $inNum = (int)M('AccountLog')->where(['user_id'=>$this->userInfo['userid'],'log_type'=>['not in',$exType]])->sum('change_num');
        $totalNum = $inNum - $exNum;

        $this->ajaxReturn(['totalNum'=>$totalNum,'exNum'=>$exNum,'inNum'=>$inNum,'logList'=>$list]);
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

        $this->ajaxReturn(['totalNum'=>$totalNum,'exNum'=>$exNum,'inNum'=>$inNum,'logList'=>$list]);
    }

    //查看记录
    public function tradeLog()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $playType = $this->param['play_type'] ?: 1;

        //旧表和新表的联表
        $subQuery = M('Gamble')->field('id')
                    ->union('SELECT id from qc_gamble_reset WHERE play_type = '.$playType, true)
                    ->where(['play_type'=>$playType])->buildSql();

        $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id'])
                ->join('RIGHT JOIN  '.$subQuery.' gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$this->userInfo['userid'], 'q.game_type' => 1])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            $userInfo               = M('FrontUser')->where(['id'=>$v['user_id']])->field('lv,nick_name,head')->find();
            $list[$k]['nick_name']  = $userInfo['nick_name'];
            $list[$k]['lv']         = $userInfo['lv'];
            $list[$k]['face']       = frontUserFace($userInfo['head']);
            $list[$k]['tenGamble']  = D('GambleHall')->getTenGamble($v['user_id'],$gameType=1)?:[];
            $list[$k]['gambleInfo'] = D('GambleHall')->getGambleInfo($v['gamble_id']);
            $list[$k]['curr_victs'] = D('GambleHall')->getWinning($v['user_id'],$gameType=1)['curr_victs'];

            unset($list[$k]['gamble_id']);
        }

        $this->ajaxReturn(['tradeList'=>$list?:[]]);
    }

    //身份认证
    public function confirmID($okReturn = true)
    {
        if (!preg_match("/^[\x{4e00}-\x{9fa5}]{2,}+$/u",$this->param['true_name']))
            $this->ajaxReturn(1020);

        if (!Think\Tool\Tool::validateIDCard($this->param['identfy']))
            $this->ajaxReturn(1021);

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
        //检测密码
        if (!\Think\Tool\Tool::checkPassword($this->param['password']))
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

        //执行绑定
        $pwd = md5($this->param['password']);
        $data = array('username' => $this->param['mobile'], 'password' => $pwd);
        $re = M('FrontUser')->where('id='.$this->userInfo['userid'])->save($data);

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
        $info = M('InviteInfo')->field('first_num, second_num, third_num, total_num, first_coin, second_coin, third_coin, total_coin')
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

        //推荐人id
        $recommend_id = M('FrontUser')->where(['invitation_code' => $this->param['invitation_code'], 'invite_status' => 1])->getField('id');
        if (!$recommend_id)
            $this->ajaxReturn(6017);

        //判断是否为自己的邀请码
        $code_id = M('FrontUser')->where(['invitation_code' => $this->param['invitation_code']])->getField('id');
        if ($code_id == $this->userInfo['userid'])
            $this->ajaxReturn(6020);

        //判断当前用户是否已经有上级和下级
        $pre = M('FrontUser')->where(['id' => $this->userInfo['userid']])->getField('recommend_id');
        $next = M('FrontUser')->where(['recommend_id' => $this->userInfo['userid']])->count();
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
        $result = D('GambleHall')->resetGambleData($this->userInfo['userid'], $this->userInfo['platform']);

        if($result != 1) {
            $this->ajaxReturn($result);
        } else {
            $this->ajaxReturn(['result'=>'1']);
        }
    }

}


 ?>