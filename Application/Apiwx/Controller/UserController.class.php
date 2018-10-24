<?php
/**
 * 用户中心
 * @author huangjiezhen <418832673@qq.com> 2015.12.15
 */

class UserController extends PublicController
{
    //获取用户的信息
    public function index()
    {
        if(empty($this->param['userid']))
            $this->ajaxReturn(101);

        $userInfo = M('FrontUser')
                ->field(['nick_name','username','area_code','lv','lv_bet','head face','point','coin','unable_coin','descript'])
                ->where(['id'=>$this->param['userid']])
                ->find();

//        $userInfo['fansNum']        = M('FollowUser')->where(['follow_id'=>$this->param['id']])->count();
        $userInfo['face']           = frontUserFace($userInfo['face']);
//        $userInfo['weixin_unionid'] = (string)$userInfo['weixin_unionid'];
//        $userInfo['sina_unionid']   = (string)$userInfo['sina_unionid'];
//        $userInfo['qq_unionid']     = (string)$userInfo['qq_unionid'];
//        $userInfo['mm_unionid']     = (string)$userInfo['mm_unionid'];
        $userInfo['username']       = (string)$userInfo['username'];
        $userInfo['area_code']      = (string)$userInfo['area_code'];
//        $userInfo['inviteNum']      = (string)M('InviteInfo')->where(['user_id' => $this->userInfo['userid']])->getField('total_coin');

        //优惠券、体验卷数量
//        $tickets = (int)M('TicketLog')->where(['_string' => 'over_time >' . NOW_TIME . ' and status=1 and is_use = 0','user_id' => $this->userInfo['userid']])->count();
//        $userInfo['ticket_count'] = (string)$tickets;
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

    /**
     * 我的推荐
     */
    public function gambleInfo()
    {
        if(empty($this->param['userid']))
            $this->ajaxReturn(101);

        $userid     = $this->param['userid'];
        $playType   = $this->param['play_type'] ?: 0;//默认0，全部，让分：1，大小：-1；竞彩：2
        $gameType   = $this->param['game_type'] ?: 1;//足球：1 篮球：2
        $page       = $this->param['page'] ?: 1;
        $gambleType = $this->param['gameble_type'] ?: 0;//默认0，1亚盘 2竞彩

        //只有第一页时返回用户的推荐统计
        if (in_array($playType, [0, 1, -1, 2]) && $page <= 1)
        {
            //竞彩统计
            $userInfo['jc']                     = D('GambleHall')->getWinning($userid, $gameType, 0, 2);
            $userInfo['jc']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($userid, 1, 1, false, false, 0, 2);
            $userInfo['jc']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($userid, 1, 2, false, false, 0, 2);
            $userInfo['jc']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($userid, 1, 3, false, false, 0, 2);

            //亚盘统计
            $userInfo['yp']                     = D('GambleHall')->getWinning($userid, $gameType, 0, 1);//查总的
            $userInfo['yp']['weekPercnet']      = (string)D('GambleHall')->CountWinrate($userid, 1, 1);
            $userInfo['yp']['monthPercnet']     = (string)D('GambleHall')->CountWinrate($userid, 1, 2);
            $userInfo['yp']['seasonPercnet']    = (string)D('GambleHall')->CountWinrate($userid, 1, 3);
            $userInfo['yp']['total_times']      = (string)D('GambleHall')->getWinning($userid, $gameType, $playType, 1)['total_times'];//对应玩法的总场数

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
            $gambleList[$k]['tradeCount'] = M('QuizLog')->where(['gamble_id'=>$v['gamble_id']])->count();
        }

        $this->ajaxReturn(['userInfo'=>$userInfo ?:'','gambleList'=>$gambleList]);
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
        $exType     = ['2', '3', '9', '12', '14','15','17'];
        //获取记录
        if($type == 3){
            $list = M('quizLog q')
                    ->join("RIGHT JOIN qc_gamble g on g.id = q.gamble_id")
                    ->field('g.home_team_name,g.away_team_name,g.play_type,g.chose_side,g.quiz_number,g.income,q.log_time,q.gamble_id,q.game_type')
                    ->where("g.result = 0 AND g.tradeCoin > 0 AND g.quiz_number > 0 AND g.is_back = 0 AND g.user_id = ".$userid)
                    ->page($page.','.$pageNum)
                    ->group("g.id")
                    ->order('q.log_time desc')
                    ->select();
            foreach($list as $k => $v)
            {
                //编辑描述
                $desc = '';
                $home_name  = explode(',',$v['home_team_name'])[0];
                $away_name  = explode(',',$v['away_team_name'])[0];

                $desc .= '您推荐的【';
                $desc .= in_array($v['play_type'], ['2', '-2']) ? '竞彩-':'亚盘-';
                $desc .= C('fb_play_type')[$v['play_type']];
                $desc .= " {$home_name}VS{$away_name}】";
                $desc .= "被{$v['quiz_number']}人查看";

                $list[$k]['change_num'] = $v['income'];
                $list[$k]['desc'] = $desc ? $desc : $v['desc'];

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

            unset($list[$k2]['ticket_id']);
        }

        if ($page != 1)
            $this->ajaxReturn(['logList' => $list?:[]]);

        $exNum  = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['IN',$exType]])->sum('change_num');//支出
        $inNum  = (int)M('AccountLog')->where(['user_id'=>$userid,'log_type'=>['NOT IN',$exType]])->sum('change_num');//收入
        $wjsNum = (int)M('gamble')->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)->sum('income'); //待结算
        $totalNum   = $inNum - $exNum;//余额

        $this->ajaxReturn(['totalNum' => $totalNum, 'exNum' => $exNum, 'inNum' => $inNum, 'wjsNum' => $wjsNum,'logList' => $list?:[]]);
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
        if(empty($this->param['userid']))
            $this->ajaxReturn(101);

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
                ->where(['q.user_id'=>$this->param['userid']])
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
            $list[$k]['curr_victs'] = D('GambleHall')->getWinning($v['user_id'],$gameType=1)['curr_victs'];

            $list[$k]['gambleInfo']['bet_code'] = '';
            if($gamebleType == 2)
                $list[$k]['gambleInfo']['bet_code'] = M('FbBetodds')->where(['game_id' => $v['game_id']])->getField('bet_code');

            $list[$k]['gambleInfo']['game_id'] = $v['game_id'];
            unset($list[$k]['gamble_id']);
        }

        $this->ajaxReturn(['tradeList'=>$list?:[]]);
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





}
 ?>