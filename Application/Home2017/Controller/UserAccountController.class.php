<?php
/**
 * 账户管理
 * @author huangjiezhen <418832673@qq.com> 2015.11.27
 */
class UserAccountController extends HomeController
{
    //账户明细
    public function details()
    {
        //生成查询数据
        $map = $this->_search("accountLog");
        $userid = is_login();
        //日期筛选,默认一周
        $dateType = I('get.dateType') ?: '3months';
        $this->assign('dateType',$dateType);
        switch ($dateType)
        {
            case 'aweek':   $searchTime = 7 * 86400;    break;
            case 'amonth':  $searchTime = 30 * 86400;   break;
            case '3months': $searchTime = 90 * 86400;   break;
        }
        $map['log_time'] = ['egt',time() - $searchTime];
        //当前登录用户明细记录
        $map['user_id']    = $userid;
        //获取列表
        $list= $this->_list(D("accountLog"),$map,12,'','','',"/UserAccount/details/dateType/{$dateType}/p/%5BPAGE%5D.html");
        $this->assign('list',$list);

        $this->position = '个人中心';
        
        $this->display();
    }

    //待结算账户明细
    public function wait_details()
    {
        $userid = is_login();

        //当前登录用户明细记录
        $map['user_id']     = $userid;
        $map['result']      = ['eq',0];
        $map['tradeCoin']   = ['gt',0];
        $map['quiz_number'] = ['gt',0];
        $map['is_back']     = ['eq',0];

        //获取列表总数
        $countfb = M('gamble')->where ( $map )->count ();
        $countbk = M('gamblebk')->where ( $map )->count ();
        $count = $countfb+$countbk;
        //实例化分页类
        $page = new \Think\Page ( $count, 12 );
        //足球待结算
        $fb_list = M('gamble')->where($map)->field("game_date,game_time,union_name,home_team_name,away_team_name,play_type,chose_side,quiz_number,income")->group('id')->order('game_date desc,game_time desc')->select();
        foreach ($fb_list as $k => $v) {
            $fb_list[$k]['gameType'] = in_array($v['play_type'], ['2', '-2']) ? '足球竞彩 - ':'足球亚盘 - ';
            $fb_list[$k]['playDesc'] = C('fb_play_type')[$v['play_type']];
        }
        //篮球待结算
        $bk_list = M('gamblebk')->where($map)->field("game_date,game_time,union_name,home_team_name,away_team_name,play_type,chose_side,quiz_number,income")->group('id')->order('game_date desc,game_time desc')->select();
        foreach ($bk_list as $k => $v) {
            $bk_list[$k]['gameType'] = '篮球 - ';
            $bk_list[$k]['playDesc'] = C('bk_play_type')[$v['play_type']];
        }
        //合并
        $list = array_merge($fb_list,$bk_list);
        foreach ($list as $k => $v) {
            $game_date[] = $v['game_date'];
            $game_time[] = $v['game_time'];
        }
        array_multisort($game_date,SORT_ASC,$game_time,SORT_ASC,$list);
        $list = array_slice($list, $page->firstRow,$page->listRows);
        $list = HandleGamble($list);
        $page->url = "/UserAccount/wait_details/p/%5BPAGE%5D.html";
        //模板赋值显示
        $this->assign ( "show", $page->showJs());
        $this->assign('totalCount', $count );
        $this->assign('numPerPage', $page->listRows );
        $this->assign('list', $list );
        //待结算总金币
        $fb_income  = (int)M('gamble')
                ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)
                ->sum('income');
        $bk_income  = (int)M('gamblebk')
                ->where("result = 0 AND tradeCoin > 0 AND quiz_number > 0 AND is_back = 0 AND user_id = ".$userid)
                ->sum('income');
        $this->assign('income',$fb_income + $bk_income);
        
        $this->display();
    }

    /**
     * @author :junguo
     * @desc:充值
     * */
    public function charge()
    {
        $this->position = '个人中心';
        $user  =session('user_auth');
        $this->assign('user_id',$user['username']);
        
        $this->display();
    }

    //充值记录
    public function chargeLog()
    {
        //生成查询数据
        $map = $this->_search("accountLog");
        //条件
        $map = ['user_id'=>is_login(),'log_status'=>['gt',0],'log_type'=>1];
        //获取列表
        $list= $this->_list(D("accountLog"),$map,13,'','','',"/UserAccount/chargeLog/p/%5BPAGE%5D.html");
        $this->assign('list',$list);
        $this->position = '个人中心';
        
        $this->display();
    }

    //提款
    public function extract()
    {
        $user_id = is_login();
        $user = M('FrontUser')
                ->field('username,coin,unable_coin,alipay_id,true_name,identfy,bank_name,bank_card_id,alipay_id,bank_extract_pwd')
                ->find($user_id);
        $this->exMoney = $user['coin']; //可提款
        //判断是否身份认证、绑定银行卡、绑定手机
        if (! $user['true_name'] || !$user['identfy'])
            $this->noTrueName = true;
        //判断是否绑定银行卡或支付宝账号
        if (! $user['bank_name'] || !$user['bank_card_id'])
        {
            if (! $user['alipay_id']) $this->noBindBank = true;
        }
        //判断是否绑定手机
		if (! $user['username'])
            $this->noUsername = true;
        
        if (isset($this->noTrueName) || isset($this->noBindBank) || isset($this->noUsername)){
            $tpl = 'extractNotice';
        }else{
            if(IS_AJAX && IS_POST){
                $bank_extract_pwd = I('bank_extract_pwd');
                //验证提款密码
                if(md5($bank_extract_pwd) !== $user['bank_extract_pwd']){
                    $this->error("提款密码错误！");
                    exit;
                }
                $coin = I('coin');
                //验证金额
                if($coin>$user['coin']){
                    $this->error("可提现金额为{$user['coin']}元");
                    exit;
                }
                if($coin<50 || $coin>10000){
                    $this->error("每次提款金额最小为50元,最大为10000元");
                    exit;
                }
                //每天只能申请提款一次
                $begin = strtotime("today");
                $end   = strtotime("today")+86400;
                $where['user_id'] = $user_id;
                $where['log_type'] = 2;
                $where['log_time'] = array('BETWEEN',array($begin,$end));
                $is_true = M("accountLog")->where($where)->select();
                if($is_true){
                    $this->error("亲，每天只能提款一次哦，明天再来吧！");
                    exit;
                }
                M("accountLog")->startTrans();
                //添加提款申请
                $rs = M("accountLog")->add(
                        array(
                                'user_id'=>$user_id,
                                'log_time'=>time(),
                                'log_type'=>2,
                                'change_num'=>$coin,
                                'total_coin'=>($user['coin']+$user['unable_coin'])-$coin,
                                'desc'=>"提款申请",
                                'platform'=>1,
                            )
                    );
                if($rs){
                    //减去金额
                    $rs2 = M("FrontUser")->where(array('id'=>$user_id))->setDec('coin',$coin);
                    //添加到冻结提款金额
                    $rs3 = M("FrontUser")->where(array('id'=>$user_id))->setInc('frozen_coin',$coin);
                }
                if($rs && $rs2 && $rs3){
                    M("accountLog")->commit();
                    $this->success("申请提款成功，请等待审核！");
                }else{
                    M("accountLog")->rollback();
                    $this->error("申请提款失败！");
                }
            }
            $tpl = 'extract';
            $this->assign('user',$user);
        }
        $this->position = '个人中心';
        $this->display($tpl);
    }
    /**
     * 绑定支付宝
     * @User liangzk <liangzk@qc.com>
     * @DateTime 2016-0-25
     * @versoin v2.1
     */
    public function bindAlipay()
    {
        $this->position = '个人中心';
        $user_id = is_login();
    
        if (IS_AJAX)
        {
        
            $alipay_id = I('alipay_id','char');
            $true_name = I('true_name','char');
            $bank_extract_pwd = I('bank_extract_pwd','int');
            $re_bank_extract_pwd = I('re_bank_extract_pwd','int');
            if (! preg_match('/^\d{6}$/',$bank_extract_pwd))
                $this->error('输入格式不正确！');
            if (! preg_match('/^\d{6}$/',$re_bank_extract_pwd))
                $this->error('输入格式不正确！');
        
            $userData = M('FrontUser')->where(['id'=>$user_id])->field('identfy,bank_card_id,alipay_id,true_name')->find();
    
            //是否已经认证身份
            if (empty($userData['true_name']) || empty($userData['identfy']))
                $this->error('请身份认证！');
            if (empty($alipay_id))
                $this->error('支付宝账号不能为空！');
            if ($true_name !== $userData['true_name'])
                $this->error('真实姓名必须与身份证姓名一致！');
            if ($bank_extract_pwd !== $re_bank_extract_pwd)
                $this->error('提款密码不一致！');
            if (! empty($userData['bank_card_id']))
                $this->error('已绑定过银行卡号！');
            if (! empty($userData['alipay_id']))
                $this->error('已绑定支付宝账号！');
        
            $res = M('FrontUser')->where(['id'=>$user_id])->save(['alipay_id'=>$alipay_id,'bank_extract_pwd'=>md5($bank_extract_pwd)]);
       
            if ($res === false)
                $this->error('操作失败');
        
            $this->success('操作成功');
        }
        
        $userData = M('FrontUser')->where(['id'=>$user_id])->field('identfy,true_name,alipay_id,bank_card_id')->find();
    
        //是否已经认证身份
        if (empty($userData['true_name']) || empty($userData['identfy']))
            $this->redirect('UserInfo/identity');
        
        if (! empty($userData['alipay_id']))//判断是否绑定过
        {
            $this->assign('alipay_id',hideStar($userData['alipay_id']));
            $this->display('bankCardInfo');
            exit;
        }
        else
        {
            if (! empty($userData['bank_card_id']))
            {
                $this->assign('bank_card_id',$userData['bank_card_id']);
                $this->display();
                exit;
            }
        }
        $this->display();
    }
    //绑定银行卡
    public function bindBankCard()
    {
        $this->position = '个人中心';
        $user_id = is_login();
        //是否ajax请求绑定银行卡
        if (IS_AJAX)
        {
            $user = M('FrontUser')->field('true_name,alipay_id,bank_card_id')->find($user_id);
    
            //判断是否已绑定支付宝账号
            if (! empty($user['alipay_id']))
                $this->error('已经绑定过支付宝账号');
            if (! empty($user['bank_card_id']))
                $this->error('已经绑定过银行卡号');
            
            $post = I('post.');

            if ($user['true_name'] != $post['true_name'])
                $this->error('真实姓名不一致');

            if ($post['bank_extract_pwd'] != $post['re_bank_extract_pwd'])
                $this->error('两次密码输入不一致');

            $data = [
                'bank_name'        => $post['bank_name'],
                'bank_card_id'     => $post['bank_card_id'],
                'bank_region'      => $post['province'] .' '. $post['city'],
                'bank_extract_pwd' => md5($post['bank_extract_pwd'])
            ];

            $update = M('FrontUser')->where(['id'=>$user_id])->save($data);

            if ($update)
                $this->success('绑定成功');
            else
                $this->error('绑定失败');
        }

        $user = M('FrontUser')->field('alipay_id,true_name,identfy,bank_name,bank_card_id,bank_region')->find($user_id);
        //判断是否已绑定支付宝账号
        if (! empty($user['alipay_id']))
        {
            $this->assign('alipay_id',$user['alipay_id']);
            $this->display();
            exit;
        }
        //是否已经认证身份
        if (!$user['true_name'] || !$user['identfy'])
            $this->redirect('UserInfo/identity');

        //是否已经绑定银行卡
        if ($user['bank_name'] && $user['bank_card_id'])
        {
            $this->user = $user;
            $this->display('bankCardInfo');
            exit;
        }
        else
        {
            $this->bank = M('Bank')->field('bank_name')->select();
            $this->province = M('Area')->field('id,region_name')->where(['parent_id'=>1])->select();
        }

        $this->display();
    }

    //ajax获取各省的城市
    public function getCity()
    {
        if (!IS_AJAX)
            return;

        $regionid = I('post.regionid');

        if (!$regionid)
        {
            echo 0;
            exit;
        }

        $city = M('Area')->field('id,region_name')->where(['parent_id'=>$regionid])->select();
        $this->ajaxReturn($city);
    }

    //ajax校验绑定银行卡的姓名与身份证是否一致
    public function verifyTrueName ()
    {
        if (!IS_AJAX)
            return;

        $postName = I('post.true_name');
        $user = M('FrontUser')->field('true_name')->find(is_login());

        if ($postName == $user['true_name'])
            echo 'true';
        else
            echo 'false';
    }

    //积分明细
    public function pointLog()
    {
        //生成查询数据
        $map = $this->_search("PointLog");

        //日期筛选,默认一周
        $dateType = I('get.dateType') ?: 'aweek';
        $this->assign('dateType',$dateType);
        switch ($dateType)
        {
            case 'aweek':   $searchTime = 7 * 86400;    break;
            case 'amonth':  $searchTime = 30 * 86400;   break;
            case '3months': $searchTime = 90 * 86400;   break;
        }
        $map['dateType'] = $dateType;
        $map['log_time'] = ['egt',time() - $searchTime];
        //当前登录用户明细记录
        $map['user_id']    = is_login();
        //获取列表
        $list= $this->_list(D("PointLog"),$map,12,'','','',"/UserAccount/pointLog/dateType/{$dateType}/p/%5BPAGE%5D.html");
        $this->assign('list',$list);

        $this->position = '个人中心';
        
        $this->display();
    }

}

 ?>