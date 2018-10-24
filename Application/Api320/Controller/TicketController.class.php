<?php
/**
 * 体验券控制器
 */
use Think\Tool\Tool;
class TicketController extends PublicController
{
    /**
     * 购买体验券列表
     */
    public function ticketList()
    {
        $page      = $this->param['page'] ?: 1;
        $pageNum   = 20;
        $userToken = getUserToken($this->param['userToken']);

        $res = M('TicketConf')->field('id, name, sale, over_time, totle_num, over_num as rest_num ')
                ->where(['start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
                ->page($page . ',' . $pageNum)->order(' id desc ')->select();

        if($res){
            foreach($res as $k => $v){
                $res[$k]['is_buy'] = M('TicketLog')->where(['class_id' => $v['id'], 'user_id' => $userToken['userid']])->count() ? 1 : 0;
            }
        }

        $this->ajaxReturn(['result'=>(array)$res]);
    }

    /**
     * 购买体验券
     */
    public function buyTicket()
    {
        $id = $this->param['id'] ?: 0;
        $userToken = getUserToken($this->param['userToken']);

        if(empty($id) || empty($userToken))
            $this->ajaxReturn(101);

        //购买体验券先判断有无手机号码
        if(empty($userToken['username']))
            $this->ajaxReturn(1060);

        $one = M('TicketConf')->where(['id' => $id])->find();

        if($one){
            //判断有没有之前购买
            if(M('TicketLog')->where(['class_id' => $id, 'user_id' => $userToken['userid']])->count())
                $this->ajaxReturn(7002);

            //抢购完就不能抢
            if($one['over_num'] == 0)
                $this->ajaxReturn(7007);

            //判断金币
            $userInfo = M('FrontUser')->field(['coin','unable_coin','(coin+unable_coin) as totalCion'])->where(['id'=>$userToken['userid']])->find();

            $coin = $one['sale'];
            if ($userInfo['unable_coin'] < $coin) {
                $userInfo['coin'] = $userInfo['coin'] - ($coin - $userInfo['unable_coin']);
                $userInfo['unable_coin'] = 0;

                if ($userInfo['coin'] < 0)
                    return $this->ajaxReturn(1072);
            } else {
                $userInfo['unable_coin'] -= $coin;
            }

            //捕抓错误，开启事务
            try{
                M()->startTrans();

                //修改相关表
                $res1 = M('FrontUser')->master(true)->where(['id'=>$userToken['userid']])->save(['unable_coin'=>$userInfo['unable_coin'],'coin'=>$userInfo['coin']]);

                $res2 = M('AccountLog')->add([
                    'user_id'    =>  $userToken['userid'],
                    'log_time'   =>  NOW_TIME,
                    'log_type'   =>  15,
                    'log_status' =>  1,
                    'change_num' =>  $coin,
                    'total_coin' =>  $userInfo['totalCion']-$coin,
                    'desc'       =>  "购买体验券-".$one['name'],
                    'platform'   =>  $this->param['platform'],
                    'operation_time' => NOW_TIME
                ]);

                if ($res1=== false || $res2=== false) {
                    throw new Exception();
                }else{
                    $data = [];
                    $data['name']        = $one['name'];
                    $data['class_id']    = $id;
                    $data['user_id']     = $userToken['userid'];
                    $data['type']        = 1;
                    $data['price']       = $one['price'];
                    $data['give_coin']   = $one['sale'];
                    $data['get_time']    = NOW_TIME;
                    $data['over_time']   = $one['over_time'];
                    $data['plat_form']   = $this->param['platform'];
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
                $this->ajaxReturn(['result'=>1]);
            }catch(Exception $e) {
                M()->rollback();
                $this->ajaxReturn(7001);
            }
        }else{
            $this->ajaxReturn(101);
        }
    }

    /**
     * 兑换体验券
     */
    public function exchangeTicket(){
        $code      = $this->param['code'] ?: '';
        $userToken = getUserToken($this->param['userToken']);

        if(empty($code) || empty($userToken))
            $this->ajaxReturn(101);

        $one = M('TicketCode')->where(['code' => $code, 'over_time' => ['gt', NOW_TIME], 'is_use' => 0, 'status' => 1])->find();
        if($one){
            $data = [];
            $data['name']        = $one['price'].C('giftPrice');
            $data['user_id']     = $userToken['userid'];
            $data['partner_id']  = $one['partner_id'];
            $data['type']        = 1;
            $data['price']       = $one['price'];
            $data['get_time']    = NOW_TIME;
            $data['over_time']   = $one['over_time'];
            $data['plat_form']   = $this->param['platform'];
            $data['get_type']    = 2;
            $data['code']        = $code;
            $data['remark']      = '推荐体验券-兑换';

            $res = M('TicketLog')->add($data);
            if($res === false)
                $this->ajaxReturn(7004);

            M('TicketCode')->where(['code' => $code])->save(['user_id' => $userToken['userid'], 'is_use' => 1, 'use_time' => NOW_TIME]);
        }else{
            $this->ajaxReturn(7003);
        }

        $this->ajaxReturn(['result'=> (string)$data['price']]);
    }


    /**
     * 体验券分类信息
     */
    public function ticketType(){
        $coin      = $this->param['coin'] ?: 0;
        $type      = $this->param['type'] ?: 0;//1：体验卷  2：优惠卷
        $userToken = getUserToken($this->param['userToken']);

        if(empty($coin) || empty($type) || empty($userToken))
            $this->ajaxReturn(101);

        $where['user_id']   = $userToken['userid'];
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

        $this->ajaxReturn(['result'=> (array)$data]);
    }

    /**
     * 活动赠送礼包领取
     */
    public function getGift(){
        $userToken = getUserToken($this->param['userToken']);
        $user_id   = $userToken['userid'];
        $gift_id   = $this->param['gift_id'] ?: 0;

        if(empty($user_id) || empty($gift_id))
            $this->ajaxReturn(101);

        //活动赠送大礼包，活动时间内
        $res = D('FrontUser')->giftBag($user_id, $this->param['platform'], 3, 4, $gift_id);

        if($res)
            $this->ajaxReturn(['result' => '1']);

        $this->ajaxReturn(7008);
    }


}