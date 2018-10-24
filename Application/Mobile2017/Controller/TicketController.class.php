<?php
/**
 * 体验券控制器
 */
use Think\Tool\Tool;
class TicketController extends CommonController
{
/*
    protected function _initialize() {
        $user = session('user_auth');
        if (!empty($user))
        {
            $user = session('user_auth');
        }

    }
*/
    /**
     * 我的体验券
     */
    public function myTicket(){
        $userId = is_login();

        if(empty($userId)) {
            redirect(U('User/login'));
            die;
        }

        cookie('redirectUrl', __SELF__);
        $page    = I('page', 1, 'intval');
        $pageNum = 20;
        $type    = I('type', 0, 'intval');//可用：0,；不可用：1

        $where['user_id']   = $userId;
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

        $num1   = M('TicketLog')->where($where1)->count();//可用总数
        $num2   = M('TicketLog')->where($where2)->count();//不可用总数

        unset($where, $where1, $where2, $where3);

        if($res){
            $html = '';
            $confType = [1 => '购买', 2 => '兑换', 3 => '注册赠送', 4 => '活动赠送', 5 => '系统赠送', 6 => '摇一摇赠送'];
            foreach($res as $k => $v){
                $res[$k]['get_type']  = $confType[$v['get_type']];
                $res[$k]['over_time'] = date('Y.m.d', $v['over_time']);
                $res[$k]['remark']    = explode('-', $v['remark'])[0];
                $res[$k]['isExpire']  = ($v['over_time'] - NOW_TIME <= 5 * 3600 * 24) ? 1 :0;

                $res[$k]['deadline']  = $v['over_time'] < NOW_TIME ? 1 : 0;

                if($page >= 2){
                    $part = '';
                    if($type == 1){//不可用
                        if($v['is_use'] == 1){
                            $part = '<i class="ponUsed"></i>';
                        }else{
                            if($v['deadline']){
                                $part = '<i class="ponOver"></i>';
                            }else{
                                $part = '';
                            }
                        }

                        $part1 = 'ponDet';
                        $html .= '<a href="javascript:;" type="'.$v['type'].'" disabled="disabled" class="item clearfix"> <div class="fl itemLeft '.$part1.'"> <p><span>'.$v['give_cion'].'</span>金币</p> <p>'.$v['name'].'</p> </div> <div class="fl itemRight"> <p><span class="fs38">'.$res[$k]['remark'].'</span><span class="text-999">（'.$res[$k]['get_type'].'）</span></p> <p class="text-666 fs24">有效期至'.$res[$k]['over_time'].'</p> </div> '.$part.'</a>';
                    }else{//可用
                        if($res[$k]['isExpire'] == 1){
                            $part = '<i class="ponWill"></i>';
                        }

                        $part1 = 'ponOn';
                        $html .= '<a href="javascript:;" type="'.$v['type'].'"  class="item clearfix"> <div class="fl itemLeft '.$part1.'"> <p><span>'.$v['give_cion'].'</span>金币</p> <p>'.$v['name'].'</p> </div> <div class="fl itemRight"> <p><span class="fs38">'.$res[$k]['remark'].'</span><span class="text-999">（'.$res[$k]['get_type'].'）</span></p> <p class="text-666 fs24">有效期至'.$res[$k]['over_time'].'</p> </div> '.$part.'</a>';
                    }
                }
            }
        }

        if($page >= 2){
                $this->ajaxReturn(['status' => 1, 'num' => count($res), 'page' => $page, 'list' => $res ? $html : '']);
        }else{
            $this->type = $type;
            $this->page = $page;
            $this->list = (array)$res;
            $this->num1 = $num1;
            $this->num2 = $num2;

            $this->display('index');
        }
    }

    /**
     * 购买体验券列表
     */
    public function ticketList()
    {
        $page     = I('page', 1, 'intval');
        $pageNum  = 20;
        $userId   = is_login();

        $res = M('TicketConf')->field('id, name, sale, over_time, totle_num, over_num as rest_num ')
                ->where(['start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                ->page($page . ',' . $pageNum)->order(' id desc ')->select();

        if($res){
            $html = '';
            foreach($res as $k => $v){
                $res[$k]['is_buy'] = M('TicketLog')->where(['class_id' => $v['id'], 'user_id' => (int)$userId])->count() ? 1 : 0;

            if($page >= 2){
                if($v['rest_num'] == 0){
                    $class1 = 'noneBuy';
                    $class2 = '<p><a href="javascript:;" class="buyBtn buyBtnOn buyBtnDet">已抢光</a></p>';
                }else{
                    $class1 = '';
                    if($v['is_buy'] == 1){
                        $class2 = '<p><a href="javascript:;" class="buyBtn buyBtnDet">已抢购</a></p>';
                    }else{
                        $class2 = '<p><a href="javascript:;" ticketid="'.$v['id'].'"  price="'.$v['sale'].'" class="buyBtn buyBtnOn ticketClass">抢购</a></p> ';
                    }
                }

                $html .= '<div href="javascript:;" class="item clearfix">
                        <div class="fr buyItemR '.$class1.'
                            <p class="fs26">数量</p>
                            <p><strong><span>'.$v['rest_num'].'</span>/'.$v['totle_num'].'</strong></p>'.$class2.'
                            <i class="halfTop"></i>
                        </div>
                        <div class="fl buyItemL clearfix">
                            <div class="fl textLeft">
                                <p class="fs24"><span>'.$v['sale'].'</span>元</p>
                                <i class="mark">体验券</i>
                            </div>
                            <div class="fr textRight">
                                <p class="fs34">'.$v['name'].'</p>
                                <p class="text-666 fs24">有效期至'.date("Y.m.d", $v['over_time']).'</p>
                            </div>
                        </div>
                    </div>';
                }
            }
        }

        if($page >= 2){
            $this->ajaxReturn(['status' => 1, 'num' => count($res), 'page' => $page, 'list' => $res ? $html : '']);
        }else{
            $this->page = $page;
            $this->list = (array)$res;
            $this->total_coin = (int)M('FrontUser')->where(['id' => $userId])->getField(' coin+unable_coin as num');
            $this->display('list');
        }
    }

    /**
     * 购买体验券
     */
    public function buyTicket()
    {
        $id     = I('id', 0, 'intval');
        $userId = is_login();

        if(empty($id) || empty($userId))
            $this->error('参数错误');

        //购买体验券先判断有无手机号码
        if(empty(session('user_auth')['username']))
            $this->error('参数错误');

        $one = M('TicketConf')->master(true)->where(['id' => $id])->find();

        if($one){
            //判断有没有之前购买
            if(M('TicketLog')->master(true)->where(['class_id' => $id, 'user_id' => $userId])->count())
                $this->error('不能重复购买');

            //抢购完就不能抢
            if($one['over_num'] == 0)
                $this->error('已经抢购完毕');

            //判断金币
            $userInfo = M('FrontUser')->master(true)->field(['coin','unable_coin','(coin+unable_coin) as totalCion'])->where(['id'=>$userId])->find();

            $coin = $one['sale'];
            if ($userInfo['unable_coin'] < $coin) {
                $userInfo['coin'] = $userInfo['coin'] - ($coin - $userInfo['unable_coin']);
                $userInfo['unable_coin'] = 0;

                if ($userInfo['coin'] < 0)
                    $this->error('金币不足');
            } else {
                $userInfo['unable_coin'] -= $coin;
            }

            //捕抓错误，开启事务
            try{
                M()->startTrans();

                //修改相关表
                $res1 = M('FrontUser')->master(true)->where(['id'=>$userId])->save(['unable_coin'=>$userInfo['unable_coin'],'coin'=>$userInfo['coin']]);

                $res2 = M('AccountLog')->add([
                    'user_id'    =>  $userId,
                    'log_time'   =>  NOW_TIME,
                    'log_type'   =>  15,
                    'log_status' =>  1,
                    'change_num' =>  $coin,
                    'total_coin' =>  $userInfo['totalCion']-$coin,
                    'desc'       =>  "购买体验券-".$one['name'],
                    'platform'   =>  4,
                    'operation_time' => NOW_TIME
                ]);

                if ($res1=== false || $res2=== false) {
                    throw new Exception();
                }else{
                    $data = [];
                    $data['name']        = $one['name'];
                    $data['class_id']    = $id;
                    $data['user_id']     = $userId;
                    $data['type']        = 1;
                    $data['price']       = $one['price'];
                    $data['give_coin']   = $one['sale'];
                    $data['get_time']    = NOW_TIME;
                    $data['over_time']   = $one['over_time'];
                    $data['plat_form']   = 4;
                    $data['get_type']    = 1;
                    $data['remark']      = '推荐体验券-购买';
                    //添加记录
                    $res3 = M('TicketLog')->add($data);
                    //总数减少
                    $res4 = M('TicketConf')->where(['id' => $id])->save(['over_num' => ['exp', 'over_num-1'], 'buy_num' => ['exp', 'buy_num+1']]);

                    if($res3 === false || $res4 === false){
                        throw new Exception();
                    }
                }

                M()->commit();

                $this->success('购买成功！');
            }catch(Exception $e) {
                M()->rollback();

                $this->error('购买失败');
            }
        }else{
            $this->error('参数错误');
        }
    }

    /**
     * 兑换体验券
     */
    public function exchangeTicket(){
        $code   = I('code', '', 'strval');
        $userId = is_login();

        if(empty($userId)) {
            $this->error('请先登录', U('User/login'));
        }

        if(empty($code))
            $this->error('参数错误');

        $one = M('TicketCode')->master(true)->where(['code' => $code, 'over_time' => ['gt', NOW_TIME], 'is_use' => 0, 'status' => 1])->find();
        if($one){
            $data = [];
            $data['name']        = $one['price'].C('giftPrice');
            $data['user_id']     = $userId;
            $data['partner_id']  = $one['partner_id'];
            $data['type']        = 1;
            $data['price']       = $one['price'];
            $data['get_time']    = NOW_TIME;
            $data['over_time']   = $one['over_time'];
            $data['plat_form']   = 4;
            $data['get_type']    = 2;
            $data['code']        = $code;
            $data['remark']      = '推荐体验券-兑换';

            $res = M('TicketLog')->add($data);
            if($res === false)
                $this->error('兑换失败');

            M('TicketCode')->where(['code' => $code])->save(['user_id' => $userId, 'is_use' => 1, 'use_time' => NOW_TIME]);
        }else{
            $this->error('兑换码无效');
        }

        $this->success($data['price'].'金币');
    }


    /**
     * 体验券分类信息
     */
    public function ticketType(){
        $coin   = I('coin',0,'int');
        $type   = I('type',0,'int');//1：体验卷  2：优惠卷
		$userId = is_login();

        if(empty($coin) || empty($type) || !$userId)
            $this->error('请求失败！');

        $where['user_id']   = $userId;
        $where['price']     = $coin;
        $where['is_use']    = 0;
        $where['status']    = 1;
        $where['type']      = $type;
        $where['over_time'] = ['gt', NOW_TIME];

        //体验券
        if($type == 1){
            $fields = ' name, price, give_coin ';
            $res    = M('TicketLog')->field($fields)->where($where)->order(' price desc ')->select();

            if($res){
                $data[0]['name']      = $res[0]['name'];
                $data[0]['num']       = count($res);
                $data[0]['price']     = $coin;
                $data[0]['give_coin'] = $res[0]['give_coin'];
                $data[0]['isExpire']  = 0;
            }
        }else{//优惠券
            $fields = ' name, price, give_coin, over_time, count(*) as num ';
            $data   = M('TicketLog')->field($fields)->where($where)->group('give_coin')->order(' price desc ')->select();

            if($data) {
                foreach ($data as $k => $v) {
                    $data[$k]['isExpire'] = 0;
                    if ($v['num'] > 1) {
                        $where['give_coin'] = $v['give_coin'];
                        $timeArr = M('TicketLog')->where($where)->getField('id, over_time');

                        //遍历出将到期的优惠券
                        foreach ($timeArr as $tk => $tv) {
                            if ($tv - NOW_TIME <= 5 * 3600 * 24) {
                                $data[$k]['isExpire'] = 1;
                                break;
                            }
                        }
                    } else {
                        //选出将到期的优惠券
                        if ($v['over_time'] - NOW_TIME <= 5 * 3600 * 24) {
                            $data[$k]['isExpire'] = 1;
                        }
                    }
                    unset($data[$k]['over_time']);
                }
            }
        }

        $this->success(['result'=> (array)$data]);
    }

    /**
     * 活动赠送礼包领取
     */
    public function getGift(){
        $user_id   = is_login();
        $gift_id   = I('gift_id', 0, 'intval');

        if(empty($user_id) || empty($gift_id))
            $this->error('参数错误');

        //活动赠送大礼包，活动时间内
        $res = D('FrontUser')->giftBag($user_id, 4, 3, 4, $gift_id);

        if($res)
            $gift2 = M('GiftsConf')->where(['type' => 3, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                     ->order(' id desc ')->limit(1)->getField('id');

            S("gift2_close_{$user_id}_{$gift2}", 1, time()+3600*24*30);
            $this->success('赠送成功');

         $this->error('赠送失败');
    }

    /**
     * 记录活动赠送的信息
     */
    public function recordGift(){
        $user_id = is_login();
        $type    = I('type', 0, 'intval');//gift1：1,；gift2：2

        if(empty($user_id) || empty($type))
            $this->error('参数错误');

        if($type == 1){
            $gift1 = M('GiftsConf')->where(['type' => 1, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                     ->order(' id desc ')->limit(1)->getField('id');
            S("gift1_close_{$user_id}_{$gift1}", 1, time()+3600*24*30);
        }else if($type == 2){
            $gift2 = M('GiftsConf')->where(['type' => 3, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                    ->order(' id desc ')->limit(1)->getField('id');
            S("gift2_close_{$user_id}_{$gift2}", 1, time()+3600*24*30);
        }

        $this->success('成功');
    }

}