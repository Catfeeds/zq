<?php

/**
 * 交易管理
 *
 * @author dengwj <406516482@qq.com>
 *
 * @since  2015-12-7
 */
use Think\Controller;
use Think\Tool\Tool;
class AccountLogController extends CommonController {

    public function index(){
            //生成查询条件
            $map = $this->_search("AccountLog");
            //时间查询
            if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
                if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $endTime   = strtotime($_REQUEST ['endTime']);
                    $map['log_time'] = array('BETWEEN',array($startTime,$endTime));
                } elseif (!empty($_REQUEST['startTime'])) {
                    $startTime = strtotime($_REQUEST ['startTime']);
                    $map['log_time'] = array('EGT',$startTime);
                } elseif (!empty($_REQUEST['endTime'])) {
                    $endTime = strtotime($_REQUEST['endTime']);
                    $map['log_time'] = array('ELT',$endTime);
                }
            }
            if(!empty($_REQUEST ['start_time']) || !empty($_REQUEST ['end_time'])){
                if(!empty($_REQUEST ['start_time']) && !empty($_REQUEST ['end_time'])){
                    $start_time = strtotime($_REQUEST ['start_time']);
                    $end_time   = strtotime($_REQUEST ['end_time']);
                    $map['operation_time'] = array('BETWEEN',array($start_time,$end_time));
                } elseif (!empty($_REQUEST['start_time'])) {
                    $start_time = strtotime($_REQUEST ['start_time']);
                    $map['operation_time'] = array('ELT',$start_time);
                } elseif (!empty($_REQUEST['end_time'])) {
                    $end_time   = strtotime($_REQUEST ['end_time']);
                    $map['operation_time'] = array('ELT',$end_time);
                }
            }
            $log_type = I("logType");
            if(!empty($log_type))
            {
                if($log_type == 2)
                {
                    //提款记录、手动扣除
                    $map['log_type'] = $log_type;
                    $log_status = I('log_status');
                    empty($log_status) ? $map['log_status'] = array('neq','0') : $map['log_status'] = $log_status;
                }
                elseif($log_type == 1)
                {
                    //充值记录
                    if(I('log_type') == '')
                        $map['log_type'] = array('in',array(1,5,7,8,16,24));
                }
            }

			//查询开通预测模型会员 包括开通和续费
            $checkOpenVip = I('checkOpenVip');
            if($checkOpenVip == 1){
                $map['log_type'] = array('in',array(25,26));
            }

            //提款申请
            $drawing = I("drawing");
            if($drawing == 1){
                $map['log_status'] = '0';
                $map['log_type'] = '2';
            }

            //点击渠道查询中的昵称所传过来的user_id,进行筛选
            $user_id=I('user_id');
            if(!empty($user_id))
            {
                $map['user_id'] = $user_id;
                $this->assign('backUrl',cookie('_currentUrl_'));
                $coinRes = M('FrontUser')->where(['id'=>$user_id])->Field('coin,unable_coin,frozen_coin')->find();
                $this->assign('balance',$coinRes['coin']+$coinRes['unable_coin']);//余额
                //支出类型
                $exType     = C('payAccountType');
                $this->assign('incomeCoin',M('AccountLog')
                                            ->where(['log_type'=>['NOT IN',$exType],'log_status'=>1,'user_id'=>$user_id])
                                            ->Field('sum(change_num) as incomeCoin')
                                            ->find()['incomeCoin']);//收入金币
                $this->assign('expendCoin',M('AccountLog')
                                            ->where(['log_type'=>['IN',$exType],'log_status'=>1,'user_id'=>$user_id])
                                            ->Field('sum(change_num) as expendCoin')
                                            ->find()['expendCoin']);//支出金币
                $this->assign('frozenCoin',$coinRes['frozen_coin']);//冻结金币

            }
            $totalAmount= round(D('AccountLog')->where($map)->sum('change_num'),2);//涉及总金币
            $totalUser  = D('AccountLog')->field('COUNT(DISTINCT a.user_id) as totalUser')->where($map)->find();//涉及人数
            //导出Excel
            $Export=I('Export');
            if(!empty($Export))
            {
                if(empty($map)) $limit = 1000;
                $dealList=D('AccountLog')->where($map)->order($log_type == 2 ? 'operation_time desc' : 'log_time desc')->limit($limit)->select();
                $this->accountLogExport("",$dealList,$log_type,$totalAmount,$totalUser['totalUser']);
            }
            else
            {
                $list = $this->_list(D('AccountLog'), $map);
                foreach ($list as $k => $v) {
                    $order_number_arr[] = $v['order_id'];
                    $device_token[] = $v['device_token'];
                    $last_ip[]      = $v['last_ip'];
                    //分成类型
                    if($v['log_type'] == 4){
                        if($v['game_type'] == 1){
                            $GambleFbArr[] = $v['gamble_id'];
                        }
                        if($v['game_type'] == 2){
                            $GambleBkArr[] = $v['gamble_id'];
                        }
                    }
                }
                //查询购买人数据
                $quizFbUser = $quizBkUser = [];
                if(!empty($GambleFbArr)){
                    //足球购买记录
                    $quizFbUser = M('QuizLog q')->join('LEFT JOIN qc_front_user f on f.id=q.user_id')->field('q.gamble_id,q.user_id,f.last_ip,f.device_token')->where(['gamble_id'=>['in',$GambleFbArr],'game_type'=>1])->select() ? : [];
                }
                if(!empty($GambleBkArr)){
                    //篮球购买记录
                    $quizBkUser = M('QuizLog q')->join('LEFT JOIN qc_front_user f on f.id=q.user_id')->field('q.gamble_id,q.user_id,f.last_ip,f.device_token')->where(['gamble_id'=>['in',$GambleBkArr],'game_type'=>2])->select() ? : [];
                }
                $quizArr = array_merge($quizFbUser,$quizBkUser);
                foreach ($quizArr as $k => $v) {
                    $quizData[$v['gamble_id']][] = $v;
                }
                foreach ($list as $k => $v) {
                    if(isset($quizData[$v['gamble_id']]) && $v['log_type'] == 4){
                        $quiz = $quizData[$v['gamble_id']];
                        $list[$k]['yichang'] = $this->checkYC($quiz,$v['device_token'],$v['last_ip']);
                    }
                }
                if($drawing == 1){
                    //设备异常查询
                    $device_token = array_values(array_unique(array_filter($device_token)));
                    $device = M("FrontUser")->field('device_token,count(device_token) as num')->where(['device_token'=>['in',$device_token],'status'=>1])->group('device_token')->having("num > 1")->select();
                    $deviceArr = [];
                    foreach ($device as $k => $v) {
                        $deviceArr[$v['device_token']] = $v;
                    }
                    //登录ip异常查询
                    $last_ip = array_values(array_unique(array_filter($last_ip)));
                    $last = M("FrontUser")->field('last_ip,count(last_ip) as num')->where(['last_ip'=>['in',$last_ip],'status'=>1])->group('last_ip')->having("num > 1")->select();
                    $lastArr = [];
                    foreach ($last as $k => $v) {
                        $lastArr[$v['last_ip']] = $v;
                    }
                }

                $order_number = M('tradeRecord')->field('trade_no,alipay_trade_no,pay_type,pkg')->where(['trade_no'=>['in',$order_number_arr]])->select();
                foreach ($list as $k => $v) {
                    foreach ($order_number as $kk => $vv) {
                        if($v['order_id'] == $vv['trade_no']){
                            $list[$k]['trade_no'] = $vv['trade_no'];
                            $list[$k]['alipay_trade_no'] = $vv['alipay_trade_no'];
                            $list[$k]['pay_type'] = $vv['pay_type'];
                            $list[$k]['pkg']      = $vv['pkg'];
                        }
                    }
                    if($drawing == 1){
                        //设备与登录ip异常查询
                        if(isset($deviceArr[$v['device_token']])){
                            $list[$k]['device'] = $deviceArr[$v['device_token']]['device_token'];
                        }
                        if(isset($lastArr[$v['last_ip']])){
                            $list[$k]['last'] = $lastArr[$v['last_ip']]['last_ip'];
                        }
                    }
                }

                $this->assign('totalAmount',$totalAmount);
                $this->assign('totalUser',$totalUser['totalUser']);
                $this->assign('list',$list);
                $this->display();
            }
    }

    //异常设备号，ip查询
    public function checkYC($quiz,$device,$last_ip){
        $yichang = 0;
        foreach ($quiz as $k => $v) {
            if($v['last_ip'] == $last_ip || ($v['device_token'] == $device && !empty($v['device_token'])) ){
                $yichang = 1;
                break;
            }
            $lastArr[]   = $v['last_ip'];
            $deviceArr[] = $v['device_token'];
        }

        //异常ip查询
        $lastArr = array_count_values($lastArr);
        foreach ($lastArr as $k => $v) {
            if($v > 1 && !empty($k)){
                $yichang = 1;
                break;
            }
        }
        
        //异常设备号查询
        $deviceArr = array_count_values($deviceArr);
        foreach ($deviceArr as $k => $v) {
            if($v > 1 && !empty($k)){
                $yichang = 1;
                break;
            }
        }
        return $yichang;
    }

    /**
     * 导出Excel
     * @param string $filename [文件名，当为空时就以当前日期为文件名]
     * @param list $list [列表数据]
     * @param $log_type
     * @param $totalAmount 涉及总金币
     * @param $totalUser 涉及人数
    **/
    public function accountLogExport($filename="",$list,$log_type,$totalAmount,$totalUser)
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;">涉及总金币:</th>';
        $strTable .= '<td style="text-align:center;font-size:12px;color:red;">'.$totalAmount.'</td>';
        $strTable .= '<th style="text-align:center;font-size:12px;">涉及人数:</th>';
        $strTable .= '<td style="text-align:center;font-size:12px;color:red;">'.$totalUser.'</td>';
        $strTable .= '</tr>';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width=120px;>昵称/手机号</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">注册时间</th>';
        if(getUserPower()['is_show_count'] == 1){
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">真实姓名</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">银行名称</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">银行卡号</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">支付宝账号</th>';
        }
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">创建时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">记录类型</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">涉及金额</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">转账手续费</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">实际转账金额</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">转账方式</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">余额</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">平台</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">状态</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">处理人</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">处理时间</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">说明</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">积分</th>';
        
        $strTable .= '</tr>';
        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['user_id'].' </td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['nick_name']."(<span style=\"color: red;\">".is_show_mobile($val['username'])."</span>)".'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d',$val['reg_time']).'</td>';
            if(getUserPower()['is_show_count'] == 1){
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['true_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_name'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['bank_card_id'].'</td>';
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['alipay_id'].'</td>';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y/m/d H:i:s',$val['log_time']).'</td>';
            switch ($val['log_type']) 
            {
            case '1':$log_type="手动充值";break;
            case '2':$log_type='提款';break;
            case '3':$log_type='购买推荐';break;
            case '4':$log_type='销售分成';break;
            case '5':$log_type='系统赠送';break;
            case '6':$log_type='积分兑换';break;
            case '7':$log_type='充值收入';break;
            case '8':
                    $log_type='自动充值';
                    switch ($val['pay_way']) {
                        case '1':$log_type.='(支付宝'.$val['alipay_trade_no'].')';break;
                        case '2':$log_type.='(微信'.$val['alipay_trade_no'].')';break;
                        case '3':$log_type.='(银联)';break;
                        case '4':$log_type.='(移动支付)';break;
                        case '5':$log_type.='(苹果支付)';break;
                    }break;
            default: $log_type = '--';
            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$log_type.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['change_num'].'</td>';
            if($val['poundage'] == '') $val['poundage'] = 0;
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['poundage'].'</td>';
            $ture_coin  =  $val['change_num']+$val['poundage'];
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$ture_coin.'</td>';
            if($val['transfer_way'] == '') $val['transfer_way'] = '--';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['transfer_way'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['total_coin'].'</td>';
            switch ($val['platform']) {
                case '1':$platform='Web';break;
                case '2':$platform='IOS';break;
                case '3':$platform='ANDRIOD';break;
                case '4':$platform='M站';break;
                default:$platform='--';

            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$platform.'</td>';
            switch ($val['log_status']) {
                case '0':$log_status='待审核';break;
                case '1':$log_status='成功';break;
                case '2':$log_status='不通过';break;
                case '3':$log_status='待汇款';break;
                case '4':$log_status='驳回';break;
                default:$log_status='--';

            }
            $strTable .= '<td style="text-align:left;font-size:12px;">'.'&nbsp&nbsp'.$log_status.'</td>';
            $nickName = ! empty($val['admin_id']) ? $val['nickname'] : '系统处理' ;
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$nickName.'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.date('Y-m-d H:i:s',$val['operation_time']).'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['descc'].'</td>';
            $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['point'].'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';dump($orderList);
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }

    /*
     * 金币余额统计
     * @author liangzk <1343724998@qq.com>
     * @date 2016-07-18
     * @time 18:43
     *  version 1.0
     */
    public function balanceCount()
    {
        $_REQUEST['numPerPage'] = 30;
        //过滤
        $map = $this->_search('FrontUser');
        //时间查询

        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $timeSection = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $timeSection = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $timeSection = array('ELT',$endTime);
            }
        }
        //用户操作异常记录列表传过来的
        $user_id = I('user_id');
        if (! empty($user_id))
        {
            $map['id'] = $user_id;
        }

        //金币额度查询
        $balance = I('balance');
        if (! empty($balance))
        {
            switch ($balance)
            {
                case '1': $map['_string'] = 'coin+unable_coin < 30';  break;
                case '2': $map['_string'] = 'coin+unable_coin >=  30 AND coin+unable_coin < 50';  break;
                case '3': $map['_string'] = 'coin+unable_coin >=  50 AND coin+unable_coin < 100';  break;
                case '4': $map['_string'] = 'coin+unable_coin >= 100';  break;
            }
        }
        if (I('Export') == 1)
        {
            //是否为导出操作
            if(empty($map)) $limit = 1000;
            $list = M('FrontUser')->where($map)->order('coin+unable_coin desc')->limit($limit)->select();//导出的数据
        }
        else {
            $list = $this->_list(CM('FrontUser'),$map,'coin+unable_coin');//显示列表
        }
        //总余额、总可提金币
        $coinSumRes = M('FrontUser')->where($map)->Field('SUM(coin+unable_coin) as balanceSum,SUM(coin) as coinSum')->find();

        //获取用户ID
        $userIdArr = array_map('array_shift',$list);

        //model初始化
        $accountLogModel = M('AccountLog');
        //每个用户的充值总金币
        $rechargeSumArr = $accountLogModel
                        ->Field('user_id,sum(change_num) as rechargeSum')
                        ->where(['user_id'=>['in',$userIdArr],'log_type'=>['in',[1,8]]])
                        ->where(empty($timeSection) ? array() : ['log_time'=>$timeSection])
                        ->group('user_id')
                        ->select();
        //每个用户的总提款金币
        $drawCoinSumArr = $accountLogModel
                        ->Field('user_id,sum(change_num) as drawCoinSum')
                        ->where(['log_type'=>2,'log_status'=>1])
                        ->where(empty($timeSection) ? array() : ['log_time'=>$timeSection])
                        ->group('user_id')
                        ->select();
        //获取查看竞猜的用户ID
        $quizLogArr = M('QuizLog')->field('game_type,user_id,gamble_id')->where(['coin'=>['GT',0]])->select();

        //分类（足球、篮球）
        $quizLogClass =array();
        foreach ($quizLogArr as $k => $v)
        {
            $quizLogClass[$v['game_type']][] = $v;
        }
        unset($quizLogArr);
        //每个用户的足球消费
        $consumeArrFB = $accountLogModel
                        ->Field('user_id,sum(change_num) as consumeFB')
                        ->where(['log_type'=>3,'user_id'=>['in',get_arr_column($quizLogClass['1'],'user_id')]])
                        ->where(empty($timeSection) ? [] : ['log_time'=>$timeSection])
                        ->group('user_id')
                        ->select();
        //每个用户的篮球消费
        $consumeArrBK = $accountLogModel
                        ->Field('user_id,sum(change_num) as consumeBK')
                        ->where(['log_type'=>3,'user_id'=>['in',get_arr_column($quizLogClass['2'],'user_id')]])
                        ->where(empty($timeSection) ? [] : ['log_time'=>$timeSection])
                        ->group('user_id')
                        ->select();

        //model初始化
        $quizLogModel = M('QuizLog q');
        //每个用户购买足球竞猜的赢输平
        $where = empty($timeSection) ?
                    ['q.coin'=>['GT',0],'q.game_type'=>1,'result'=>['in',[1,0.5,-1,-0.5,2]]]
                    :
                    ['q.coin'=>['GT',0],'q.game_type'=>1,'result'=>['in',[1,0.5,-1,-0.5,2]],['log_time'=>$timeSection]];
        $gambleIngResArr = $quizLogModel->Field('q.user_id,g.result,count(result) as gambleRes ')
                        ->join('inner JOIN  qc_gamble g on  g.id = q.gamble_id')
                        ->union(['field'=>'q.user_id,g.result,count(result) as gambleRes ',
                                 'table'=>' qc_quiz_log q inner JOIN qc_gamble_reset g on g.id = q.gamble_id ',
                                 'where'=>$where,'group'=>'q.user_id,g.result'], true)
                        ->where($where)
                        ->group('q.user_id,g.result')
                        ->select();
        unset($where);

        //每个用户被购买足球竞猜的赢输平
        $where = empty($timeSection)
                    ?
                    ['result'=>['in',[1,0.5,-1,-0.5,2]],'id'=>['in',array_unique(get_arr_column($quizLogClass['1'],'gamble_id'))]]
                    :
                    ['result'=>['in',[1,0.5,-1,-0.5,2]],'id'=>['in',array_unique(get_arr_column($quizLogClass['1'],'gamble_id'))],['create_time'=>$timeSection]];

        $gambleByResArr = M('Gamble')
                    ->union(array('field'=>'user_id,result,count(result) as gambleRes ',
                                  'where'=>$where,
                                  'group'=>'user_id,result','table'=>'qc_gamble_reset'),true)
                    ->Field('user_id,result,count(result) as gambleRes ')
                    ->where($where)
                    ->group('user_id,result')
                    ->select();
        unset($where);


        //每个用户购买篮球竞猜的赢输平
        $gambleIngResArrBK = $quizLogModel->Field('q.user_id,g.result,count(result) as gambleRes ')
                        ->join('LEFT JOIN  qc_gamblebk g on  g.id = q.gamble_id')
                        ->where(['q.coin'=>['GT',0],'q.game_type'=>2,'result'=>['in',[1,-1,2]]])
                        ->where(empty($timeSection) ? [] : ['log_time'=>$timeSection])
                        ->group('q.user_id,g.result')
                        ->select();
        //每个用户被购买篮球竞猜的赢输平
        $gambleByResArrBK = M('Gamblebk')->Field('user_id,result,count(result) as gambleRes ')
                        ->where(['result'=>['in',[1,-1,2]],'id'=>['in',array_unique(get_arr_column($quizLogClass['1'],'gamble_id'))]])
                        ->where(empty($timeSection) ? [] : ['create_time'=>$timeSection])
                        ->group('user_id,result')
                        ->select();
        unset($quizLogClass);
        foreach ($list as $key => $value)
        {
            //每个用户的充值总金币
            foreach ($rechargeSumArr as $k => $v)
            {
                if($value['id'] == $v['user_id']) $list[$key]['rechargeSum'] = $v['rechargeSum'];
            }
            //每个用户的总提款金币
            foreach ($drawCoinSumArr as $k => $v)
            {
                if($value['id'] == $v['user_id']) $list[$key]['drawCoinSum'] = $v['drawCoinSum'];
            }

            //每个用户的足球消费
            foreach ($consumeArrFB as $k => $v)
            {
                if($value['id'] == $v['user_id']) $list[$key]['consumeFB'] = $v['consumeFB'];
            }
            //每个用户的篮球消费
            foreach ($consumeArrBK as $k => $v)
            {
                if($value['id'] == $v['user_id']) $list[$key]['consumeBK'] = $v['consumeBK'];
            }
            //每个用户购买足球竞猜的赢输平
            foreach ($gambleIngResArr as $k => $v)
            {
                if ($value['id'] == $v['user_id'])
                {
                    if ($v['result'] == 1 ) $list[$key]['gambleIngWinFB'] += $v['gambleRes'];
                    if ($v['result'] == 0.5 ) $list[$key]['gambleIngWinFB'] += $v['gambleRes'];
                    if ($v['result'] == -1 ) $list[$key]['gambleIngLoseFB'] += $v['gambleRes'];
                    if ($v['result'] == -0.5 ) $list[$key]['gambleIngLoseFB'] += $v['gambleRes'];
                    if ($v['result'] == 2 ) $list[$key]['gambleIngFlatFB'] += $v['gambleRes'];

                }
            }
            //每个用户被购买足球竞猜的赢输平
            foreach ($gambleByResArr as $k => $v)
            {
                if ($value['id'] == $v['user_id'])
                {
                    if ($v['result'] == 1 ) $list[$key]['gambleByWinFB'] += $v['gambleRes'];
                    if ($v['result'] == 0.5 ) $list[$key]['gambleByWinFB'] += $v['gambleRes'];
                    if ($v['result'] == -1 ) $list[$key]['gambleByLoseFB'] += $v['gambleRes'];
                    if ($v['result'] == -0.5 ) $list[$key]['gambleByLoseFB'] += $v['gambleRes'];
                    if ($v['result'] == 2 ) $list[$key]['gambleByFlatFB'] += $v['gambleRes'];

                }
            }
            //每个用户购买足球竞猜的赢输平
            foreach ($gambleIngResArrBK as $k => $v)
            {
                if ($value['id'] == $v['user_id'])
                {
                    if ($v['result'] == 1 ) $list[$key]['gambleIngWinBK'] += $v['gambleRes'];
                    if ($v['result'] == -1 ) $list[$key]['gambleIngLoseBK'] += $v['gambleRes'];
                    if ($v['result'] == 2 ) $list[$key]['gambleIngFlatBK'] += $v['gambleRes'];

                }
            }
            //每个用户被购买足球竞猜的赢输平
            foreach ($gambleByResArrBK as $k => $v)
            {
                if ($value['id'] == $v['user_id'])
                {
                    if ($v['result'] == 1 ) $list[$key]['gambleByWinBK'] += $v['gambleRes'];
                    if ($v['result'] == -1 ) $list[$key]['gambleByLoseBK'] += $v['gambleRes'];
                    if ($v['result'] == 2 ) $list[$key]['gambleByFlatBK'] += $v['gambleRes'];

                }
            }
            $gambleCount = $list[$key]['gambleByWinFB']+$list[$key]['gambleByLoseFB']
                            +$list[$key]['gambleByFlatFB']+$list[$key]['gambleByWinBK']
                            +$list[$key]['gambleByLoseBK']+$list[$key]['gambleByFlatBK'];
            //每个用户被购买的竞猜胜率
            $list[$key]['winPercentage'] = round(($list[$key]['gambleByWinFB']+$list[$key]['gambleByWinBK'])/$gambleCount*100)."%";

        }


        if (I('Export') == 1)//导出操作
        {
			 $totalCount = I('totalCount','',int);
            if($totalCount > 20000)
            {
                $this->error('导出数据量过大请在20000条以内，请根据条件筛选后再导出');
            }
            $this->excelExport($list,'',$coinSumRes['balanceSum'],$coinSumRes['coinSum']);
        }
        else
        {
            $this->assign('balanceSum',$coinSumRes['balanceSum']);
            $this->assign('coinSum',$coinSumRes['coinSum']);
            $this->assign('list',$list);
            $this->display();
        }
    }

    //当月余额统计 每月月底自动执行 发送短信通知运营
    public function monthCoinLog()
    {
        $map['is_robot']  = 0;
        $map['user_type'] = 1;
        //总余额、总可提金币
        $coinSumRes = M('FrontUser')->where($map)->Field('SUM(coin+unable_coin) as balanceSum,SUM(coin) as coinSum')->find();
        $mobile = '17620047421;13580437445';
        $msg = '【全球体育】本月正常用户总余额为'.$coinSumRes['balanceSum'].'金币，可提款余额为'.$coinSumRes['coinSum'].'金币。统计时间：'.date('Y-m-d H:i:s');

        $fileName = dirname(__FILE__).'./../Conf/monthCoinLog.php';
        if (!file_exists($fileName)) fopen($fileName,'w');

        $array = include_once($fileName);
        if(!is_array($array)) $array = [];

        $coinSumRes['time'] = date('Y-m-d H:i:s'); //添加时间
        $coinSumRes['msg']  = $msg;                //备注
        $array[] = $coinSumRes;

        //组装数组
        $arr='<?php'."\r\n".
            'return'." ".var_export($array, true).";";
        $rs = file_put_contents($fileName,$arr);   //写入文件

        $result = BxtSMS($mobile,$msg);        //发送短信通知
        echo $result.'：'.$msg;
        die;
    }

    /**
     * 金币统计导出
     * @author liangzk <1343724998@qq.com>
     * @Date 2016-07-19 Time 12:00
     * @version v1.0
     * @param        $list  列表
     * @param        $balanceSum  总余额
     * @param        $coinSum  总可提现余额
     * @param string $filename  导出的文件名
     *
     */
    public function excelExport($list,$filename="",$balanceSum,$coinSum)
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">总余额</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">'.$balanceSum.'</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">总可提现余额</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">'.$coinSum.'</th>';
        $strTable .= '</tr>';
        $strTable .= '<tr></tr>';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">ID</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">会员名称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">用户名</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买推荐胜率</th>';
        if(getUserPower()['is_show_count'] == 1){
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">积分数</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">余额金币（可提金币）</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">总充值金币</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">总提款金币</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">总消费金币</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">足球消费金币</th>';
        }
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">足球购买后赢</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">足球购买后输</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">足球购买后平</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*"><span style="color: red;">足球被</span>购买后赢</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*"><span style="color: red;">足球被</span>购买后输</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*"><span style="color: red;">足球被</span>购买后平</th>';
        if(getUserPower()['is_show_count'] == 1){
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">篮球消费金币</th>';
        }
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">篮球购买后赢</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">篮球购买后输</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">篮球购买后平</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*"><span style="color: red;">篮球被</span>购买后赢</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*"><span style="color: red;">篮球被</span>购买后输</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*"><span style="color: red;">篮球被</span>购买后平</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['id'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['nick_name'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.is_show_mobile($val['username']).'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['winPercentage'].'</td>';
            if(getUserPower()['is_show_count'] == 1){
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$val['point'].'</td>';
                $coinSum = $val['unable_coin'] + $val['coin'];
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$coinSum.'(<span style="color:red;">可提：'.$val['coin'].'</span>)'.'</td>';

                $rechargeSum = empty($val['rechargeSum']) ? 0 : $val['rechargeSum'];
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$rechargeSum.'</td>';

                $drawCoinSum = empty($val['drawCoinSum']) ? 0 : $val['drawCoinSum'];
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$drawCoinSum.'</td>';

                $consumeSum = $val['consumeFB']+$val['consumeBK'];
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$consumeSum.'</td>';

                $consumeFB = empty($val['consumeFB']) ? 0 : $val['consumeFB'];
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$consumeFB.'</td>';
            }
            $gambleIngWinFB = empty($val['gambleIngWinFB']) ? '--' : $val['gambleIngWinFB'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color: rgb(80, 182, 244);">' .$gambleIngWinFB.'</td>';

            $gambleIngLoseFB = empty($val['gambleIngLoseFB'])? '--' : $val['gambleIngLoseFB'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color: rgb(80, 182, 244);">' .$gambleIngLoseFB.'</td>';

            $gambleIngFlatFB = empty($val['gambleIngFlatFB'])? '--' : $val['gambleIngFlatFB'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color: rgb(80, 182, 244);">' .$gambleIngFlatFB.'</td>';

            $gambleByWinFB = empty($val['gambleByWinFB'])? '--' : $val['gambleByWinFB'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color:yellow;">'.$gambleByWinFB.'</td>';

            $gambleByLoseFB = empty($val['gambleByLoseFB'])? '--' : $val['gambleByLoseFB'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color:yellow;">'.$gambleByLoseFB.'</td>';

            $gambleByFlatFB = empty($val['gambleByFlatFB'])? '--' : $val['gambleByFlatFB'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color:yellow;">'.$gambleByFlatFB.'</td>';
            if(getUserPower()['is_show_count'] == 1){
                $consumeBK = empty($val['consumeBK'])? 0 : $val['consumeBK'];
                $strTable .= '<td style="text-align:left;font-size:12px;">'.$consumeBK.'</td>';
            }

            $gambleIngWinBK = empty($val['gambleIngWinBK'])? '--' : $val['gambleIngWinBK'];
            $strTable .= '<td style="text-align:left;font-size:12px; background-color: rgb(80, 182, 244);">' .$gambleIngWinBK.'</td>';

            $gambleIngLoseBK = empty($val['gambleIngLoseBK'])? '--' : $val['gambleIngLoseBK'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color: rgb(80, 182, 244);">' .$gambleIngLoseBK.'</td>';

            $gambleIngFlatBK = empty($val['gambleIngFlatBK'])? '--' : $val['gambleIngFlatBK'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color: rgb(80, 182, 244);">' .$gambleIngFlatBK.'</td>';

            $gambleByWinBK = empty($val['gambleByWinBK'])? '--' : $val['gambleByWinBK'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color:yellow;">'.$gambleByWinBK.'</td>';

            $gambleByLoseBK = empty($val['gambleByLoseBK'])? '--' : $val['gambleByLoseBK'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color:yellow;">'.$gambleByLoseBK.'</td>';

            $gambleByFlatBK = empty($val['gambleByFlatBK'])? '--' : $val['gambleByFlatBK'];
            $strTable .= '<td style="text-align:left;font-size:12px;background-color:yellow;">'.$gambleByFlatBK.'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        unset($orderList);
        downloadExcel($strTable,$filename);
        exit();
    }
    //通过审核页面
    public function toPass()
    {
        $this->display();
    }

    //处理审核
    public function ToExamine()
    {
        $id = I('id',0);
        $user_id = I('user_id',0);
        $log_status = I('log_status');
        $desc = str_replace(PHP_EOL, '',  I('desc','','htmlspecialchars'));
        if (empty($id) || empty($user_id) || empty($log_status) || empty($desc))  $this->error('参数出错');
        //验证是否已被处理过
        $accountRes = M('AccountLog')->where(['id'=>$id,'user_id'=>$user_id,'log_status'=>['NOT IN',[1,2,4]]])->find();
        if (empty($accountRes)) throw new Exception('参数出错或已经处理');

        try
        {
            $userInfo = M('FrontUser')->master(true)->field(['coin','unable_coin'])->where(['id'=>$user_id])->find();
            M()->startTrans();
            if ($log_status == 2 || $log_status == 4) //不通过审核和驳回操作
            {

                $data = array(
                    'log_status'    => $log_status,
                    'admin_id'      => $_SESSION['authId'],
                    'operation_time'=> time(),
                    'desc'          => $desc,
                );
                $not_pass = M("AccountLog")->where(['id'=>$id])->save($data);//修改审核状态
                if (empty($not_pass)) throw new Exception('数据库操作出错了');
                //减去冻结金额
                $saveCoin  = M("FrontUser")->where(['id'=>$user_id])->save([
                        'frozen_coin'=>['exp','frozen_coin-'.$accountRes['change_num']],
                        'coin'       =>['exp','coin+'.$accountRes['change_num']]
                    ]);

                //添加提款失败，金币退回用户账号的记录
                $drawDesc = $log_status == 2 ? '提款审核不通过,退回用户金币' : '驳回提款审核,退回用户金币';
                $data = array(
                    'log_type'      => 10,
                    'user_id'       => $user_id,
                    'log_status'    => 1,
                    'log_time'      => time(),
                    'change_num'    => $accountRes['change_num'],
                    'total_coin'    => $userInfo['coin']+$userInfo['unable_coin']+$accountRes['change_num'],
                    'admin_id'      => $_SESSION['authId'],
                    'desc'          => $drawDesc,
                );
                $accFailureLog = M("AccountLog")->add($data);
                //提款失败，通知用户的信息记录
                $drawContent = $log_status == 2 ? '您的提款申请，本网站不通过，请联系在线客服！' : '您的提款申请，由于特殊原因，已被本网站驳回，请联系在线客服！';
                //发送消息
                $msg = sendMsg($user_id, '审核通知', $drawContent, ['user_id'=>$_SESSION['authId']]);

                if ($saveCoin == false || empty($accFailureLog) || empty($msg))
                    throw new Exception('数据库操作出错了');
            }
            elseif ($log_status == 3)//待汇款
            {
                //验证是否已被处理过
                $accountRes = M('AccountLog')->where(['id'=>$id,'user_id'=>$user_id,'log_status'=>['NEQ',3]])->find();
                if (empty($accountRes)) throw new Exception('参数出错或已经处理');
                //通过了第一步审核
                $data = array(
                    'log_status'    => 3,
                    'admin_id'      => $_SESSION['authId'],
                    'operation_time'=> time(),
                    'desc'          => $desc,
                );
                $is_pass = M("AccountLog")->where(['id'=>$id])->save($data);//修改审核状态
                if (empty($is_pass)) throw  new Exception('数据库操作出错了');

            }
            elseif ($log_status == 1)//提款审核通过
            {
                $transfer_way = I('transfer_way','','htmlspecialchars');
                $pay_no = I('pay_no','','htmlspecialchars');
                if (empty($transfer_way) || empty($pay_no)) throw new Exception('参数出错');
                //通过审核
                $data = array(
                    'log_status'     => '1',
                    'poundage'       => I('poundage','0','htmlspecialchars'),
                    'admin_id'       => $_SESSION['authId'],
                    'transfer_way'   => $transfer_way,
                    'pay_no'         => $pay_no,
                    'operation_time' => time(),
                    'desc'           => $desc,
                );
                $is_pass = M("AccountLog")->where(array('id'=>$id))->save($data);
                if (empty($is_pass)) throw  new Exception('数据库操作出错了');

                //减去冻结金额
                $frozenDesc = M("FrontUser")->where(array('id'=>$user_id))->setDec('frozen_coin',$accountRes['change_num']);
                //发送消息
                $msg = sendMsg($user_id, '审核通知', '您的提款申请已通过，已将金额汇款到您指定的银行账号中，请注意查询！', ['user_id'=>$_SESSION['authId']]);

                if (empty($frozenDesc) || empty($msg)) throw  new Exception('数据库操作出错了');
            }
            elseif ( $log_status == 5 ) //冻结操作
            {
                $data = array(
                    'log_status'    => $log_status,
                    'admin_id'      => $_SESSION['authId'],
                    'operation_time'=> time(),
                    'desc'          => $desc,
                );
                $not_pass = M("AccountLog")->where(['id'=>$id])->save($data);//修改冻结状态
                //发送消息
                $msg = sendMsg($user_id, '审核通知', '您好，由于系统监测到您使用不正当手段获取金币，因此您申请的提款不通过。', ['user_id'=>$_SESSION['authId']]);

                if (empty($not_pass) || empty($msg)) throw new Exception('数据库操作出错了');
            }
            M()->commit();
            $this->success('处理成功');
        }catch (Exception $e) {
            M()->rollback();
            $this->error($e->getMessage());
        }

    }
    /*
     *审核显示信息
     */
    public function check()
    {
        $id = I('id');
        if (empty($id)) $this->error('参数出错');
        $list = M('AccountLog a')
                ->Field('a.id,a.user_id,a.change_num,a.log_status,a.transfer_way,a.pay_no,a.poundage,a.desc,
                            a.operation_time,f.nick_name,f.username,f.true_name,f.bank_name,f.bank_card_id,f.alipay_id,f.bank_full_name,f.bank_region')
                ->join('LEFT JOIN qc_front_user f on a.user_id = f.id')
                ->where(['a.id'=>$id,])
                ->find();
        if (empty($list)) $this->error('参数出错');
        $this->assign('list',$list);

        if (I('pass') == 1){
            $this->display('pass');
        }elseif (I('edit') == 1) {
            $this->display('edit');
        }
        else {
            $this->display();
        }

    }
    /**
     * 编辑提款审核信息
     */
    public function save()
    {
        $id = I('id');
        if (empty($id) ) $this->error('参数出错');
        $accountLogRes = M('AccountLog')->where(['id'=>$id,'log_status'=>['IN',[1,2,4]]])->find();
        if (! $accountLogRes)  $this->error('参数出错');
        $desc = str_replace(PHP_EOL, '',  I('desc','','htmlspecialchars'));
        if ($accountLogRes['log_status'] == 1)
        {
            $transfer_way = I('transfer_way','','htmlspecialchars');
            $pay_no = I('pay_no','','htmlspecialchars');
            $poundage = I('poundage');
            if (empty($transfer_way) || empty($pay_no)  || empty($desc)) $this->error('参数出错');

            $data = [
                'transfer_way'=>$transfer_way,
                'pay_no'=>$pay_no,
                'poundage'=>$poundage,
                'desc'=>$desc,
            ];
        }elseif ($accountLogRes['log_status'] == 2 || $accountLogRes['log_status'] == 4)
        {
            if (empty($desc)) $this->error('参数出错');
            $data = [
                'desc'=>$desc,
            ];
        }
        if (! empty($data))
            $res = M('AccountLog')->where(['id'=>$id])->save($data);

        $res < 0 ? $this->error('数据库操作出错') : $this->success('操作成功',cookie('_currentUrl_'));


    }
    /**
     *收支明细
     * @author liangzk  <liangzk@qc.mail>
     * @date 2016-07-25  @time 14:00
     * @version 1.0
     */
    public function income_pay_log()
    {
        //过滤
        $map =$this->_search('AccountLog');
        //时间查询
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])){
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['operation_time'] = array('BETWEEN',array($startTime,$endTime));
            } elseif (!empty($_REQUEST['startTime'])) {
                $strtotime = strtotime($_REQUEST ['startTime']);
                $map['operation_time'] = array('EGT',$strtotime);
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['operation_time'] = array('ELT',$endTime);
            }
        }
        $map['log_type'] = ['IN',[1,2,8,9]];//手动充值、自动充值、提款
        $map['log_status'] = 1;//手动充值、自动充值和提款的成功操作

        //Model初始化
        $accountLogModel = M('AccountLog');

        //统计记录的数量
        $timeDayCount =count($accountLogModel->where($map)->group('FROM_UNIXTIME(operation_time,"%Y%m%d")')->select());
        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;

        $order = I('_order') ? I('_order').' '.I('_sort') : 'operation_time desc';//按时间排序

        $list = $accountLogModel
            ->where($map)
            ->where(['id'=>['GT',0]])
            ->Field('operation_time')
            ->group('FROM_UNIXTIME(operation_time,"%Y%m%d")')
            ->limit($pageNum*($currentPage-1),$pageNum)
            ->order($order)
            ->select();//获取日期
        //充值金币
        $rechargeCoinRes = $accountLogModel
            ->where(['log_type'=>['IN',[7,8]],'log_status'=>1])
            ->Field('FROM_UNIXTIME(operation_time,"%Y%m%d") as operation_time,sum(change_num) as rechargeSum,pay_way')
            ->group('FROM_UNIXTIME(operation_time,"%Y%m%d"),log_type,pay_way')
            ->select();
        //分类（分为手动充值、支付宝（自动充值）、微信（自动充值）、易宝（自动充值）、移动支付（自动充值））
        $rechargeClass =array();
        foreach ($rechargeCoinRes as $k => $v)
        {
            $rechargeClass[$v['pay_way']][] = $v;
        }
        unset($rechargeCoinRes);
        //提款金币（审核通过的）
        $drawCoinRes = $accountLogModel
            ->where(['log_type'=>2,'log_status'=>1])
            ->Field('FROM_UNIXTIME(operation_time,"%Y%m%d") as operation_time,sum(change_num) as drawCoinSum,sum(poundage) as poundageSum')
            ->group('FROM_UNIXTIME(operation_time,"%Y%m%d")')
            ->select();
        //营销支出
        $MarketingNum = $accountLogModel
            ->where(['log_type'=>1,'log_status'=>1])
            ->Field('FROM_UNIXTIME(operation_time,"%Y%m%d") as operation_time,sum(change_num) as MarketingNum')
            ->group('FROM_UNIXTIME(operation_time,"%Y%m%d")')
            ->select();
        //提款审核通过的笔数
        $drawDateNum = $accountLogModel
            ->where(['log_type'=>2,'log_status'=>1])
            ->Field('FROM_UNIXTIME(operation_time,"%Y%m%d") as operation_time,COUNT(id) as drawNum')
            ->group('FROM_UNIXTIME(operation_time,"%Y%m%d")')
            ->select();
        //系统扣除的金币
        $sysCoinRes = $accountLogModel
            ->where(['log_type'=>9,'log_status'=>1])
            ->Field('FROM_UNIXTIME(operation_time,"%Y%m%d") as operation_time,sum(change_num) as sysCoinSum')
            ->group('FROM_UNIXTIME(operation_time,"%Y%m%d")')
            ->select();
        //添加到列表
        foreach ($list as $key => $value)
        {
            //每天的支付宝（自动充值）
            foreach ($rechargeClass['1'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['alipay'] = $v['rechargeSum'];
            }
            //每天的微信（自动充值）
            foreach ($rechargeClass['2'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['weChatPay'] = $v['rechargeSum'];
            }
            //每天的易宝（自动充值）
            foreach ($rechargeClass['3'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['yeepay'] = $v['rechargeSum'];
            }
            //每天的移动支付（自动充值）
            foreach ($rechargeClass['4'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['mobilePay'] = $v['rechargeSum'];
            }
            //每天的苹果支付（自动充值）
            foreach ($rechargeClass['5'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['applePay'] = $v['rechargeSum'];
            }
            //每天的苹果充值测试（自动充值）
            foreach ($rechargeClass['6'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['applePayTest'] = $v['rechargeSum'];
            }
            //每天的手动充值（自动充值）
            foreach ($rechargeClass['0'] as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['manualRecharge'] = $v['rechargeSum'];
            }

            foreach ($drawCoinRes as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time'])
                {
                    $list[$key]['drawCoinSum'] = $v['drawCoinSum'];//每天的总提款金额
                    $list[$key]['poundageSum'] = $v['poundageSum'];//每天的总转账手续费

                }
            }
            foreach ($MarketingNum as $k => $v)//营销支出
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['MarketingNum'] = $v['MarketingNum'];//每天的营销支出
            }
            foreach ($drawDateNum as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['drawNum'] = $v['drawNum'];//每天的成功提款数量
            }
            foreach ($sysCoinRes as $k => $v)
            {
                if (date('Ymd',$value['operation_time']) == $v['operation_time']) $list[$key]['sysCoinSum'] = $v['sysCoinSum'];//每天的系统扣除的金币
            }

        }
        //收支相关统计
        unset($map['log_type']);
        $where = ['log_type'=>['in',[1,2,7,8,9]]];
        $totle_coin = $accountLogModel
                    ->where($map)
                    ->where($where)
                    ->select();
        unset($accountLogModel);
        foreach ($totle_coin as $k => $vo) {
            if($vo['log_type'] == 8)  $gainCoin += $vo['change_num'];
            if($vo['pay_way'] == 1 && ($vo['log_type'] == 8 || $vo['log_type'] == 7)) $totalAliPay += $vo['change_num'];
            if($vo['pay_way'] == 2 && ($vo['log_type'] == 8 || $vo['log_type'] == 7)) $totalWeChatPay += $vo['change_num'];
            if($vo['pay_way'] == 4 && ($vo['log_type'] == 8 || $vo['log_type'] == 7)) $totalMobilePay += $vo['change_num'];
            if($vo['pay_way'] == 5 && ($vo['log_type'] == 8 || $vo['log_type'] == 7)) $totalApplePay += $vo['change_num'];
            if($vo['pay_way'] == 6 && ($vo['log_type'] == 8 || $vo['log_type'] == 7)) $totalApplePayTest += $vo['change_num'];
            if($vo['pay_way'] == 3 && ($vo['log_type'] == 8 || $vo['log_type'] == 7)) $totalYeepay += $vo['change_num'];
            if($vo['log_type'] == 1) $totalMarketingNum += $vo['change_num'];
            if($vo['log_type'] == 2) $totaldrawCoinSum += $vo['change_num'];
            if($vo['log_type'] == 2) $totaldrawNum += count($vo['log_type']);
            if($vo['log_type'] == 2) $totaldrawNumSum += $vo['poundage'];
            if($vo['log_type'] == 9) $totalsysCoinSum += $vo['change_num'];
        }
        $this->assign('gainCoin',$gainCoin);//充值总金币
        $this->assign('totalAliPay',$totalAliPay);//支付宝
        $this->assign('totalWeChatPay',$totalWeChatPay);//微信充值
        $this->assign('totalYeepay',$totalYeepay);//易宝
        $this->assign('totalMobilePay',$totalMobilePay);//移动
        $this->assign('totalApplePay',$totalApplePay);//苹果
        $this->assign('totalApplePayTest',$totalApplePayTest);//苹果测试
        $this->assign('totalMarketingNum',$totalMarketingNum);//营销支出
        $this->assign('totaldrawCoinSum',$totaldrawCoinSum);//总提款金额
        $this->assign('totaldrawNum',$totaldrawNum);//提款笔数
        $this->assign('totaldrawNumSum',$totaldrawNumSum);//转账手续费
        $this->assign('totalsysCoinSum',$totalsysCoinSum);//系统扣除

        $this->assign ( 'totalCount', $timeDayCount );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();

        $this->assign('list',$list);
        $this->display();
    }
    //不通过说明窗口
    public function reason(){
        $this->display();
    }
    //充值窗口
    public function recharge(){
        if(IS_POST){
            //获取数据
            $desc  = I('desc');
            if($desc == ''){
                $this->error("请填写充值说明！");
            }
            $coin  = I('coin');
            $coinType = I('coinType');
            $LogData ['log_type']   = I('log_type');
            $LogData ['desc']       = $desc;
            $LogData ['admin_id']   = $_SESSION['authId'];
            $LogData ['log_time']   = time();
            $LogData ['platform']   = 1;
            $LogData ['log_status'] = '1';
            $LogData ['change_num'] = $coin;
            $LogData ['operation_time'] = time();
            $model = D('FrontUser');
            //获取所有用户的id
            $FrontUserId = explode(',',I("FrontUser_id"));
            M()->startTrans();
            $num = 0;
            foreach ($FrontUserId as $key => $value)
            {
                $total_coin = M('FrontUser')->where(array('id'=>$value))->field('coin,unable_coin')->find();
            	//充值金额类型
                $coin_name = $coinType == 1 ? 'coin' : 'unable_coin';
               	$rs = $model->where(array('id'=>$value))->setInc($coin_name,$coin);
                //添加充值记录
                $LogData['user_id'] = $value;
                $LogData['total_coin'] = $total_coin['coin'] + $total_coin['unable_coin'] + $coin;
                $rs2 = M("AccountLog")->add($LogData);
                //发送消息
                $rs3 = sendMsg($value, '充值通知', "您好，您已完成充值{$coin}金币，当前账户余额为{$LogData['total_coin']}金币。（".$desc."）", ['user_id'=>$_SESSION['authId']]);
                if($rs && $rs2 && $rs3)
                {
                    $num ++;
                }
            }
            if($num == count($FrontUserId)){
                M()->commit();
                $this->success("充值成功");
            }else{
                M()->rollback();
                $this->error("充值失败");
            }
        }else{
            $this->display();
        }
    }

    //获取设置文件和修改
    public function exchangeSet(){
        $sign = 'platformSetting';
        $platConf = M('config')->where(['sign'=>$sign])->find();

        if (IS_POST)
        {
            $config['sign'] = $sign;
            $platformSetting=array(
               'pointLimit'    => $_POST['pointLimit'],  //积分
               'point2Coin1'   => $_POST['point2Coin1'],  //积分
               'point2Coin2'   => $_POST['point2Coin2'],  //积分
               'point2Coin3'   => $_POST['point2Coin3'],  //积分
               'point2Coin4'   => $_POST['point2Coin4'],  //积分
               'coin1'         => $_POST['coin1'],        //金币
               'coin2'         => $_POST['coin2'],        //金币
               'coin3'         => $_POST['coin3'],        //金币
               'coin4'         => $_POST['coin4'],        //金币

               'websiteSales1' => $_POST['websiteSales1'],//配置网站默认
               'userSales1'    => $_POST['userSales1'],   //配置会员默认值
               'websiteSales2' => $_POST['websiteSales2'],
               'userSales2'    => $_POST['userSales2'],//特殊会员配置

               'tradeCoin1'   => $_POST['tradeCoin1'],  //周榜、月榜和季榜前10名的，普通竞猜售金币个数
               'tradeCoin2'   => $_POST['tradeCoin2'],  //周榜、月榜和季榜前10名的，重点竞猜售金币个数
               'tradeCoin3'   => $_POST['tradeCoin3'],  //周榜、月榜和季榜前11名——100名的，普通竞猜售金币个数
               'tradeCoin4'   => $_POST['tradeCoin4'],  //周榜、月榜和季榜前11名——100名的，重点竞猜售金币个数
             );
            $config['config'] = json_encode($platformSetting);

            if(!$platConf){
                //新增
                $rs = M('config')->add($config);
            }else{
                //修改
                $rs = M('config')->where(['sign'=>$sign])->save($config);
                if(!is_bool($rs)){
                    $rs = true;
                }
            }
            if ($rs)
                $this->success('修改成功');

            $this->error('修改失败!');


        }
        else
        {
            $this->assign('config',json_decode($platConf['config'],true));
        }


        $this->display();
    }
    /**
     *手动扣除金币（先扣除不可提的金币，不够再扣除可提的金币）
     */
    public function deduct()
    {
        $user_id = I('user_id');
        $frontUserModel = M('FrontUser');
        if(I("coin") == 1)//获取余额操作
        {
            if(empty($user_id))
            {
                $this->error('参数出错');
            }
            $balance = $frontUserModel->where(['id'=>$user_id])->Field('coin,unable_coin,frozen_coin')->find();//获取余额
            if(empty($balance))
            {
                $this->error('参数出错');
            }
            else
            {
                $this->success($balance);
            }

        }
        if(I('deductAmount') == 1)//判断扣除的金额是否大于余额
        {
            if(empty($user_id))
            {
                $this->error('参数出错');
            }
            $change_num = I('change_num','int');
            $balance = $frontUserModel->where(['id'=>$user_id])->Field('coin,unable_coin,frozen_coin')->find();//获取余额
            if((intval($balance['unable_coin'] ) + intval($balance['coin']) ) < intval($change_num))
            {
                $this->success('余额不足');
            }
            else
            {
                $this->success($balance);
            }

        }
        if(I('deduct') == 1)
        {
            $user_id = I('FrontUser_id');
            $change_num = I('change_num');
            $desc = I('desc');
            $balance = $frontUserModel->where(['id'=>$user_id])->Field('coin,unable_coin,frozen_coin')->find();//获取余额
            if((intval($balance['unable_coin'] ) + intval($balance['coin']) ) < intval($change_num))
            {

                $this->error('余额不足');

            }
            elseif (intval($balance['unable_coin'] ) > intval($change_num))
            {
                 M()->startTrans();//开启事务

               $decRes = $frontUserModel->where(['id'=>$user_id])->setDec('unable_coin',intval($change_num));
               if($decRes)
               {
                    //添加扣除记录
                    $data = array(
                        'user_id'=>$user_id,
                        'log_type'=>9,
                        'log_time'=>strtotime(date('Ymd H:i:s')),
                        'operation_time'=>strtotime(date('Ymd H:i:s')),
                        'admin_id'=>$_SESSION['authId'],
                        'platform'=>1,
                        'log_status'=>1,
                        'change_num'=>$change_num,
                        'total_coin'=>$balance['coin']+$balance['unable_coin']-$change_num,
                        'desc'=> $desc,
                        );
                    $accountLogRes = M('AccountLog')->add($data);//添加扣除记录

                    //发送消息
                    $msgRes = sendMsg($user_id, '金币扣款通知', '您好，系统已从您账号余额中扣除'.$change_num.'币。', ['user_id'=>$_SESSION['authId']]);

                    if($accountLogRes && $msgRes)
                    {
                        M()->commit();//提交事务
                        $this->success('扣除成功');
                    }
                    else
                    {
                        $this->error('操作失败');
                    }

               }
               else
               {
                    M()->rollback();//操作失败回滚
                    $this->error('操作失败');
               }
            }
            else
            {
                 M()->startTrans();//开启事务

                $coin = intval($balance['unable_coin'] ) + intval($balance['coin']) - intval($change_num);
                $edit = $frontUserModel->where(['id'=>$user_id])->save(['coin'=>$coin,'unable_coin'=>0]);
                if($edit)
                {
                    //添加扣除记录
                    $data = array(
                        'user_id'=>$user_id,
                        'log_type'=>9,
                        'log_time'=>strtotime(date('Ymd H:i:s')),
                        'operation_time'=>strtotime(date('Ymd H:i:s')),
                        'admin_id'=>$_SESSION['authId'],
                        'platform'=>1,
                        'log_status'=>1,
                        'change_num'=>$change_num,
                        'total_coin'=>$coin,
                        'desc'=> $desc,
                        );
                    $accountLogRes = M('AccountLog')->add($data);//添加扣除记录

                    //发送消息
                    $msgRes = sendMsg($user_id, '金币扣款通知', '您好，系统已从您账号余额中扣除'.$change_num.'币。', ['user_id'=>$_SESSION['authId']]);

                    if($accountLogRes  && $msgRes)
                    {
                         M()->commit();//提交事务
                        $this->success('扣除成功');

                    }
                    else
                    {
                        $this->error('操作失败');
                    }
                }
                else
                {
                     M()->rollback();//操作失败回滚
                    $this->error('操作失败');
                }
            }


        }

        $this->display();

    }

    //用户销售统计
    public function salesCount()
    {
        if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
            if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']);
                $endTime   = strtotime($_REQUEST ['endTime'])+86400;
                $map['log_time'] = array('BETWEEN',array($startTime,$endTime));
                //查看记录的时间查询
                $logtimeSql = ' ql.log_time between  '.$startTime.' and '.$endTime;
            } elseif (!empty($_REQUEST['startTime'])) {
                $startTime = strtotime($_REQUEST ['startTime']);
                $map['log_time'] = array('EGT',$startTime);
                //查看记录的时间查询
                $logtimeSql = ' ql.log_time >=  '.$startTime;
            } elseif (!empty($_REQUEST['endTime'])) {
                $endTime = strtotime($_REQUEST['endTime'])+86400;
                $map['log_time'] = array('ELT',$endTime);
                //查看记录的时间查询
                $logtimeSql = ' ql.log_time <=  '.$endTime;
            }
        }
        else
        {
            $map['log_time'] = array('EGT',strtotime(date('Y-m-d',time())));
            $_REQUEST['startTime'] = date('Y-m-d',time());
            //查看记录的时间查询
            $logtimeSql = ' ql.log_time >=  '.strtotime(date('Y-m-d',time()));

        }

        //用户类型筛选
        $user_type = I('usertype');
        switch ($user_type)
        {
            case '1':
                $map['is_robot']  = ['neq',1];
                $map['is_expert'] = ['neq',1];
                $map['user_type'] = ['eq',1];
                break;
            case '2': $map['is_expert'] = ['eq',1]; break;
            case '3': $map['is_robot']  = ['eq',1]; break;
            case '4': $map['user_type'] = ['eq',2]; break;
        }

        $coin = (int)I('coin');
        if($coin == 1 || $coin == '')
        {
            $map['a.coin'] = ['gt',0];
            $where['coin'] = ['gt',0];
            $subqueryWhere = ' ql.coin > 0 ';
        }
        elseif ($coin == 2) {
            $map['a.coin'] = ['eq',0];
            $where['coin'] = ['eq',0];
            $subqueryWhere = ' ql.coin = 0 ';
        }

        //手机号查询
        $username = trim(I('username'));
        if (!empty($username))
        {
            $map['f.username'] = ['Like',$username.'%'];
        }
        //昵称查询
        $nick_name = trim(I('nick_name'));
        if (!empty($nick_name))
        {
            $map['f.nick_name'] = ['Like',$nick_name.'%'];
        }
        //统计记录的数量
        $querySql = M('QuizLog a')
                    ->join('qc_front_user f ON a.cover_id = f.id')
                    ->where($map)
                    ->where(['a.game_type'=>1])
                    ->field('a.id')
                    ->group('a.cover_id')
                    ->buildSql();
        $totalCount = M()->table($querySql.' a')->count('id');

        //获取每页显示的条数
        $pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        //获取当前的页码
        $currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
        //列表的编号
        $this->assign('serial',($currentPage-1)*$pageNum);
        if ($totalCount > 0)
        {
            $subquery = [
                //销售亚盘
                'letPointNum'=>',(select count(id) from qc_quiz_log ql where ql.user_id = a.cover_id and ql.game_type = 1 and '.$subqueryWhere.' and '.$logtimeSql.' and (EXISTS (select 1 from qc_gamble where id = ql.gamble_id and play_type IN (1,-1))
                    OR EXISTS (select 1 from qc_gamble_reset where id = ql.gamble_id and play_type = 1))) as letPointNum',

                //销售竞彩
                'jcNum'=>',(select count(id) from qc_quiz_log ql where ql.user_id = a.cover_id and ql.game_type = 1 and '.$subqueryWhere.' and '.$logtimeSql.'   and (EXISTS (select 1 from qc_gamble where id = ql.gamble_id and play_type IN (2,-2))
                        OR EXISTS (select 1 from qc_gamble_reset where id = ql.gamble_id and play_type IN (2,-2)))) as jcNum',
            ];

            //升降排序的字段和类型
            $_sort = I('_sort') == 'desc' ? 'desc' : 'asc';
            $_order = I('_order');

            $fieldName = 'a.cover_id,a.log_time,sum(a.coin) as salesCoinSum,count(a.id) as totalFieldNum,f.nick_name,f.username,f.coin+f.unable_coin as residueCoin,f.point'.$subquery[$_order];
            if (! empty($_order))
            {
                $orderSql = $_order.' '.$_sort;
            }
            else
            {
                $orderSql = ' totalFieldNum desc';
            }

            $list = M('QuizLog a')
                        ->join('qc_front_user f ON a.cover_id = f.id')
                        ->where($map)
                        ->where(['a.game_type'=>1])
                        ->field($fieldName)
                        ->group('cover_id')
                        ->order($orderSql)
                        ->limit($pageNum*($currentPage-1),$pageNum)
                        ->select();

            //获取用户ID
            $coverIdArr = get_arr_column($list,'cover_id');
            $letPointWhere = 'g.play_type IN(1,-1) and '.$logtimeSql;
            $letPointArr = M('QuizLog ql')
                    ->join('qc_gamble g on g.id = ql.gamble_id')
                    ->where(['ql.cover_id'=>['IN',$coverIdArr],'ql.game_type'=>1])
                    ->where($where)
                    ->where(['_string'=>$letPointWhere])
                    ->field('ql.cover_id,g.result')
                    ->group('ql.id')
                    ->select();

            $jcNumWhere = 'g.play_type IN(2,-2) and '.$logtimeSql;
            $jcNumArr = M('QuizLog ql')
                        ->join('qc_gamble g on g.id = ql.gamble_id')
                        ->where(['ql.cover_id'=>['IN',$coverIdArr],'ql.game_type'=>1])
                        ->where($where)
                        ->where(['_string'=>$jcNumWhere])
                        ->field('ql.cover_id,g.result')
                        ->group('ql.id')
                        ->select();

            //获取竞猜
            foreach ($list as $key => $value)
            {
                $letPointNum = $letWin = $letCount = $jcNum = $jcWin = $jcCount = 0;
                //用户购买亚盘竞猜总场数
                foreach ($letPointArr as $k => $v)
                {
                    if ($value['cover_id'] == $v['cover_id'])
                    {
                        if($v['result'] == 1 || $v['result'] == 0.5){
                            $letWin ++;
                        }
                        if(in_array($v['result'], [1,-1,0.5,-0.5])){
                            $letCount ++;
                        }
                        $letPointNum ++;
                    }
                }
                $list[$key]['letPointNum'] = $letPointNum;
                $list[$key]['letWin'] = $letCount > 0 ? round($letWin / $letCount * 100).'%' : '-';
                //用户购买竞彩竞猜总场数
                foreach ($jcNumArr as $k => $v)
                {
                    if ($value['cover_id'] == $v['cover_id'])
                    {
                        if($v['result'] == 1 || $v['result'] == 0.5){
                            $jcWin ++;
                        }
                        if(in_array($v['result'], [1,-1,0.5,-0.5])){
                            $jcCount ++;
                        }
                        $jcNum ++;
                    }
                }
                $list[$key]['jcNum'] = $jcNum;
                $list[$key]['jcWin'] = $jcCount > 0 ? round($jcWin / $jcCount * 100).'%' : '-';
            }

            unset($totalFieldArr,$letPointArr,$jcNumArr);


        }

        if (I('Export') == 1)//导出操作
        {
            if(count($list) > 1000)
            {
                $this->error('导出数据量过大请在1000条以内，请根据条件筛选后再导出');
            }
            $this->excelExportSalses($list);
        }

        $this->assign ( 'totalCount', $totalCount );//当前条件下数据的总条数
        $pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
        $this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
        $this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
        $this->setJumpUrl();
        $this->assign('list',$list);

        $this->display();


    }
    //用户消费统计
    public function consumeCount()
    {
		if(!empty($_REQUEST ['startTime']) || !empty($_REQUEST ['endTime'])){
			if(!empty($_REQUEST ['startTime']) && !empty($_REQUEST ['endTime'])) {
				$startTime = strtotime($_REQUEST ['startTime']);
				$endTime   = strtotime($_REQUEST ['endTime'])+86400;
				$map['log_time'] = array('BETWEEN',array($startTime,$endTime));
				//查看记录的时间查询
				$logtimeSql = ' ql.log_time between  '.$startTime.' and '.$endTime;
			} elseif (!empty($_REQUEST['startTime'])) {
				$startTime = strtotime($_REQUEST ['startTime']);
				$map['log_time'] = array('EGT',$startTime);
				//查看记录的时间查询
				$logtimeSql = ' ql.log_time >=  '.$startTime;
			} elseif (!empty($_REQUEST['endTime'])) {
				$endTime = strtotime($_REQUEST['endTime'])+86400;
				$map['log_time'] = array('ELT',$endTime);
				//查看记录的时间查询
				$logtimeSql = ' ql.log_time <=  '.$endTime;
			}
		}
		else
		{
			$map['log_time'] = array('EGT',strtotime(date('Y-m-d',time())));
			$_REQUEST['startTime'] = date('Y-m-d',time());
			//查看记录的时间查询
			$logtimeSql = ' ql.log_time >=  '.strtotime(date('Y-m-d',time()));

		}

        //用户类型筛选
        $user_type = I('usertype');
        switch ($user_type)
        {
            case '1':
                $map['is_robot']  = ['neq',1];
                $map['is_expert'] = ['neq',1];
                $map['user_type'] = ['eq',1];
                break;
            case '2': $map['is_expert'] = ['eq',1]; break;
            case '3': $map['is_robot']  = ['eq',1]; break;
            case '4': $map['user_type'] = ['eq',2]; break;
        }
        
        $coin = (int)I('coin');
        if($coin == 1 || $coin == '')
        {
            $map['a.coin'] = ['gt',0];
            $where['coin'] = ['gt',0];
            $subqueryWhere = ' ql.coin > 0 ';
        }
        elseif ($coin == 2) {
            $map['a.coin'] = ['eq',0];
            $where['coin'] = ['eq',0];
            $subqueryWhere = ' ql.coin = 0 ';
        }

		//手机号查询
		$username = trim(I('username'));
		if (!empty($username))
		{
			$map['f.username'] = ['Like',$username.'%'];
		}
		//昵称查询
		$nick_name = trim(I('nick_name'));
		if (!empty($nick_name))
		{
			$map['f.nick_name'] = ['Like',$nick_name.'%'];
		}

		//统计记录的数量
		$querySql = M('QuizLog a')
					->join('qc_front_user f ON a.user_id = f.id')
					->where($map)
					->where(['a.game_type'=>1,'f.is_robot'=>0])
					->field('a.id')
					->group('a.user_id')
					->buildSql();
		$totalCount = M()->table($querySql.' a')->count('id');

		//获取每页显示的条数
		$pageNum = empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
		//获取当前的页码
		$currentPage =! empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1;
		//列表的编号
		$this->assign('serial',($currentPage-1)*$pageNum);
    	if ($totalCount > 0)
    	{
			$subquery = [
				//购买亚盘
				'letPointNum'=>',(select count(id) from qc_quiz_log ql where ql.user_id = a.user_id and ql.game_type = 1 and '.$subqueryWhere.' and '.$logtimeSql.'	and (EXISTS (select 1 from qc_gamble where id = ql.gamble_id and play_type IN (1,-1))
					OR EXISTS (select 1 from qc_gamble_reset where id = ql.gamble_id and play_type = 1))) as letPointNum',

				//购买竞彩
				'jcNum'=>',(select count(id) from qc_quiz_log ql where ql.user_id = a.user_id and ql.game_type = 1 and '.$subqueryWhere.' and '.$logtimeSql.'	and (EXISTS (select 1 from qc_gamble where id = ql.gamble_id and play_type IN (2,-2))
						OR EXISTS (select 1 from qc_gamble_reset where id = ql.gamble_id and play_type IN (2,-2)))) as jcNum',
			];

			//升降排序的字段和类型
			$_sort = I('_sort') == 'desc' ? 'desc' : 'asc';
			$_order = I('_order');

			$fieldName = 'a.user_id,a.log_time,sum(a.coin) as salesCoinSum,count(a.id) as totalFieldNum,f.nick_name,f.username,f.coin+f.unable_coin as residueCoin,f.point'.$subquery[$_order];
			if (! empty($_order))
			{
				$orderSql = $_order.' '.$_sort;
			}
			else
			{
				$orderSql = ' totalFieldNum desc';
			}

			$list = M('QuizLog a')
						->join('qc_front_user f ON a.user_id = f.id')
						->where($map)
						->where(['a.game_type'=>1,'f.is_robot'=>0])
						->field($fieldName)
						->group('user_id')
						->order($orderSql)
						->limit($pageNum*($currentPage-1),$pageNum)
						->select();

			//获取用户ID
			$userIdArr = get_arr_column($list,'user_id');

			$letPointWhere = 'g.play_type IN(1,-1) and '.$logtimeSql;
			$letPointArr = M('QuizLog ql')
                    ->join('qc_gamble g on g.id = ql.gamble_id')
					->where(['ql.user_id'=>['IN',$userIdArr],'ql.game_type'=>1])
                    ->where($where)
					->where(['_string'=>$letPointWhere])
					->field('ql.user_id,g.result')
					->group('ql.id')
					->select();

			$jcNumWhere = 'g.play_type IN(2,-2) and '.$logtimeSql;
			$jcNumArr = M('QuizLog ql')
                        ->join('qc_gamble g on g.id = ql.gamble_id')
						->where(['ql.user_id'=>['IN',$userIdArr],'ql.game_type'=>1])
                        ->where($where)
						->where(['_string'=>$jcNumWhere])
						->field('ql.user_id,g.result')
						->group('ql.id')
						->select();

            //获取竞猜
			foreach ($list as $key => $value)
			{
                $letPointNum = $letWin = $letCount = $jcNum = $jcWin = $jcCount = 0;
				//用户购买亚盘竞猜总场数
				foreach ($letPointArr as $k => $v)
				{
					if ($value['user_id'] == $v['user_id'])
					{
                        if($v['result'] == 1 || $v['result'] == 0.5){
                            $letWin ++;
                        }
                        if(in_array($v['result'], [1,-1,2,0.5,-0.5])){
                            $letCount ++;
                        }
                        $letPointNum ++;
					}
				}
                $list[$key]['letPointNum'] = $letPointNum;
                $list[$key]['letWin'] = round($letWin / $letCount * 100);
				//用户购买竞彩竞猜总场数
				foreach ($jcNumArr as $k => $v)
				{
					if ($value['user_id'] == $v['user_id'])
					{
                        if($v['result'] == 1 || $v['result'] == 0.5){
                            $jcWin ++;
                        }
                        if(in_array($v['result'], [1,-1,2,0.5,-0.5])){
                            $jcCount ++;
                        }
						$jcNum ++;
					}
				}
                $list[$key]['jcNum'] = $jcNum;
                $list[$key]['jcWin'] = round($jcWin / $jcCount * 100);
			}

			unset($totalFieldArr,$letPointArr,$jcNumArr);


		}

        if (I('Export') == 1)//导出操作
        {
            if(count($list) > 1000)
            {
                $this->error('导出数据量过大请在1000条以内，请根据条件筛选后再导出');
            }
            $this->excelExportConsume($list);
        }

		$this->assign ( 'totalCount', $totalCount );//当前条件下数据的总条数
		$pageNum =empty($_REQUEST['numPerPage']) ? C('PAGE_LISTROWS') : $_REQUEST['numPerPage'];
		$this->assign ( 'numPerPage', $pageNum ); //每页显示多少条
		$this->assign ( 'currentPage', !empty($_REQUEST[C('VAR_PAGE')])?$_REQUEST[C('VAR_PAGE')]:1);//当前页码
		$this->setJumpUrl();
		$this->assign('list',$list);

		$this->display();


    }

    /**
     * 用户消费统计导出
     * @param        $list  列表
     * @param string $filename  导出的文件名
     *
     */
    public function excelExportConsume($list,$filename="")
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">编号</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">昵称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*";>购买总场数</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买亚盘</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">亚盘胜率</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">购买竞彩</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞彩胜率</th>';
        if(getUserPower()['is_show_count'] == 1){
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">消费金币</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">剩余金币</th>';
        }
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">剩余积分</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $serial = $k+1;
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$serial.'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$_REQUEST['startTime'] .'至'. $_REQUEST['endTime'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['nick_name'].'（'.is_show_mobile($val['username']).'）</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['totalFieldNum'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['letPointNum'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['letWin'].'%</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['jcNum'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['jcWin'].'%</td>';
            if(getUserPower()['is_show_count'] == 1){
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['salesCoinSum'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['residueCoin'].'</td>';
            }
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['point'].'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        downloadExcel($strTable,$filename);
        exit();
    }

    /**
     * 用户销售统计导出
     * @param        $list  列表
     * @param string $filename  导出的文件名
     *
     */
    public function excelExportSalses($list,$filename="")
    {
        $filename = empty($filename) ? date('Y-m-d') : $filename;
        $strTable ='<table width="500" border="1">';
        $strTable .= '<tr>';
        $strTable .= '<th style="text-align:center;font-size:12px;width:"*">编号</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">日期</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">昵称</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*";>被购买总场数</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买亚盘</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">亚盘胜率</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">被购买竞彩</th>';
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">竞彩胜率</th>';
        if(getUserPower()['is_show_count'] == 1){
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">销售金币</th>';
            $strTable .= '<th style="text-align:center;font-size:12px;" width="*">剩余金币</th>';
        }
        $strTable .= '<th style="text-align:center;font-size:12px;" width="*">剩余积分</th>';
        $strTable .= '</tr>';

        foreach($list as $k=>$val){
            $strTable .= '<tr>';
            $serial = $k+1;
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$serial.'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$_REQUEST['startTime'] .'至'. $_REQUEST['endTime'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['nick_name'].'（'.is_show_mobile($val['username']).'）</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['totalFieldNum'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['letPointNum'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['letWin'].'%</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['jcNum'].'</td>';
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['jcWin'].'%</td>';
            if(getUserPower()['is_show_count'] == 1){
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['salesCoinSum'].'</td>';
                $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['residueCoin'].'</td>';
            }
            $strTable .= '<td style="text-align:center;font-size:12px;">&nbsp;'.$val['point'].'</td>';
            $strTable .= '</tr>';
        }
        $strTable .='</table>';
        downloadExcel($strTable,$filename);
        exit();
    }

    /**
     * 充值配置
     */
    public function rechargeConfig(){
        $rechargeConfig = M('config')->where(['sign' => 'recharge'])->getField('config');

        if(IS_POST) {
            $account = I('account');
            $number  = I('number');

            foreach($account as $k => $v){
                $res1[$k]['account'] = $v;
                $res1[$k]['number']  = $number[$k];
            }

            $data['recharge']     = $res1;
            $data['rechargeBind'] = I('rechargeBind', 0, 'intval');

            $rs =  M('config')->where(['sign' => 'recharge'])->save(['config' => json_encode($data)]);
            if ($rs !== false)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('rechargeConfig', json_decode($rechargeConfig, true));
        }

        $this->display();
    }

    /**
     * IOS内购配置
     */
    public function iosRechargeConfig(){
        $rechargeConfig = M('config')->where(['sign' => 'iosRecharge'])->getField('config');

        if(IS_POST) {
            $account = I('account');
            $number  = I('number');

            foreach($account as $k => $v){
                $res1[$k]['account'] = $v;
                $res1[$k]['number']  = $number[$k] ? : '0';
            }

            $data['recharge']   = $res1;
            $data['vip_give']   = I('vip_give', 0, 'intval');

            $rs =  M('config')->where(['sign' => 'iosRecharge'])->save(['config' => json_encode($data)]);
            if ($rs !== false)
                $this->success('修改成功');

            $this->error('修改失败!');
        }else{
            $this->assign('rechargeConfig', json_decode($rechargeConfig, true));
        }

        $this->display();
    }
}

?>