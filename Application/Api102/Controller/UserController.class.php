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
                ->field(['nick_name','lv','head','point','coin','unable_coin','descript','weixin_unionid','sina_unionid','qq_unionid'])
                ->where(['id'=>$this->userInfo['userid']])
                ->find();

        $userInfo['fansNum'] = M('FollowUser')->where(['follow_id'=>$this->userInfo['userid']])->count();
        $userInfo['face'] = frontUserFace($userInfo['head']);
        unset($userInfo['head']);

        $userInfo['weixin_unionid'] = (string)$userInfo['weixin_unionid'];
        $userInfo['sina_unionid']   = (string)$userInfo['sina_unionid'];
        $userInfo['qq_unionid']     = (string)$userInfo['qq_unionid'];

        $this->ajaxReturn(['userInfo'=>$userInfo]);
    }

    //修改昵称
    public function editNickName()
    {
        $nickName = $this->param['nick_name'];
        $nameLen = Think\Tool\Tool::utf8_strlen($nickName);

        if ($nameLen < 2 || $nameLen > 10)
            $this->ajaxReturn(1052);

        if (M('FrontUser')->where(['id'=>['neq',$this->userInfo['userid']],'nick_name'=>$nickName])->find())
            $this->ajaxReturn(1011);

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
        $playType = $this->param['play_type'];
        $page     = $this->param['page'];

        if ($playType && $page)
        {
            $gambleList = D('GambleHall')->getGambleList($userid, $playType, $page); //竞猜记录

            foreach ($gambleList as $k => $v)
                $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['gamble_id'=>$v['gamble_id']])->count();

            $this->ajaxReturn(['gambleList'=>$gambleList]);
        }

        $userInfo                  = D('GambleHall')->getWinning($userid,$gameType=1); //竞猜统计信息
        $userInfo['weekPercnet']   = D('GambleHall')->CountWinrate($userid,1,1);
        $userInfo['monthPercnet']  = D('GambleHall')->CountWinrate($userid,1,2);
        $userInfo['seasonPercnet'] = D('GambleHall')->CountWinrate($userid,1,3);

        $gambleList = D('GambleHall')->getGambleList($userid, 1, 1); //竞猜记录

        foreach ($gambleList as $k => $v)
            $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['gamble_id'=>$v['gamble_id']])->count();

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
            $list[$k]['face'] = frontUserFace($v['face']);
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
            $userInfo = M('FrontUser')->where(['id'=>$v['user_id']])->field('nick_name,lv,head face')->find();
            $list[$k]['nick_name'] = $userInfo['nick_name'];
            $list[$k]['lv']        = $userInfo['lv'];
            $list[$k]['face']      = frontUserFace($userInfo['face']);
            $list[$k]['is_trade']  = M('QuizLog')->where(['user_id'=>$this->userInfo['userid'],'gamble_id'=>$v['gamble_id']])->getField('id') ? 1 : 0; //是否已经购买查看过
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

        if ($page != 1)
            $this->ajaxReturn(['logList'=>$list]);

        $exNum = (int)M('AccountLog')->where(['user_id'=>$this->userInfo['userid'],'log_status'=>['in',[0,1]],'log_type'=>['in',[2,3]]])->sum('change_num');
        $inNum = (int)M('AccountLog')->where(['user_id'=>$this->userInfo['userid'],'log_status'=>['in',[0,1]],'log_type'=>['not in',[2,3]]])->sum('change_num');
        $totalNum = $inNum - $exNum;

        $this->ajaxReturn(['totalNum'=>$totalNum,'exNum'=>$exNum,'inNum'=>$inNum,'logList'=>$list]);
    }

    //积分明细
    public function pointLog()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $where = ['user_id'=>$this->userInfo['userid']];

        switch ($this->param['type'])
        {
            case '1':   $where['log_type'] = ['in',[2,6]];      break;
            case '2':   $where['log_type'] = ['in',[1,11,12,13]];  break;
        }

        $list = M('PointLog')->field(['log_type','log_time','change_num','desc'])
                ->where($where)
                ->page($page.','.$pageNum)
                ->order('id desc')
                ->select();

        if ($page != 1)
            $this->ajaxReturn(['logList'=>$list]);

        $exNum = (int)M('PointLog')->where(['user_id'=>$this->userInfo['userid'],'log_type'=>['in',[2,6]]])->sum('change_num');
        $inNum = (int)M('PointLog')->where(['user_id'=>$this->userInfo['userid'],'log_type'=>['in',[1,11,12,13]]])->sum('change_num');
        $totalNum = $inNum - $exNum;

        $this->ajaxReturn(['totalNum'=>$totalNum,'exNum'=>$exNum,'inNum'=>$inNum,'logList'=>$list]);
    }

    //查看记录
    public function tradeLog()
    {
        $page = $this->param['page'] ?: 1;
        $pageNum = 20;

        $playType = $this->param['play_type'] ?: 1;
        $list = M('QuizLog q')->field(['q.cover_id user_id','q.gamble_id'])
                ->join('LEFT JOIN __GAMBLE__ gm ON gm.id = q.gamble_id')
                ->where(['q.user_id'=>$this->userInfo['userid'],'gm.play_type'=>$playType])
                ->page($page.','.$pageNum)
                ->order('q.id desc')
                ->select();

        foreach ($list as $k => $v)
        {
            $userInfo               = M('FrontUser')->where(['id'=>$v['user_id']])->field('lv,nick_name,head')->find();
            $list[$k]['nick_name']  = $userInfo['nick_name'];
            $list[$k]['lv']         = $userInfo['lv'];
            $list[$k]['face']       = frontUserFace($userInfo['head']);
            $list[$k]['tenGamble']  = D('GambleHall')->getTenGamble($v['user_id'],$gameType=1);
            $list[$k]['gambleInfo'] = D('GambleHall')->getGambleInfo($v['gamble_id']);

            unset($list[$k]['gamble_id']);
        }

        $this->ajaxReturn(['tradeList'=>$list]);
    }

    //身份认证
    public function confirmID()
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

        $this->ajaxReturn($data);
    }

    //修改密码
    public function changePwd()
    {
        $oldPwd = M('FrontUser')->where(['id'=>$this->userInfo['userid']])->getField('password');

        if (md5($this->param['oldPassword']) != $oldPwd)
            $this->ajaxReturn(1024);

        if (!Think\Tool\Tool::checkPassword($this->param['password']))
            $this->ajaxReturn(1006);

        if ($this->param['password'] != $this->param['rePassword'])
            $this->ajaxReturn(1025);

        $newPwd = md5($this->param['password']);
        $res = M('FrontUser')->where(['id'=>$this->userInfo['userid']])->save(['password'=>$newPwd]);

        if ($res === false)
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

        $field = ['coin','unable_coin','bank_name','bank_card_id','bank_extract_pwd'];
        $userInfo = M('FrontUser')->field($field)->where(['id'=>$this->userInfo['userid']])->find();

        if (!$userInfo['bank_name'] || !$userInfo['bank_card_id'] || !$userInfo['bank_extract_pwd'])
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

        if (M('AccountLog')->where($where)->find())
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
}


 ?>