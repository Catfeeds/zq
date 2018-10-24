<?php

/**
 * User: junguo
 * Date: 2016/4/11
 * Time: 10:48
 */
use Think\Controller;

class PaynotifyController extends Controller {

    /**
     * 支付宝支付
     * */
    public function ali() {
        vendor('Payment.Alipay.lib.Corefunction');
        vendor('Payment.Alipay.lib.Md5function');
        vendor('Payment.Alipay.lib.Notify');
        $alipay_config = C('alipay.alipay_config');

        $alipayNotify = new AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();

        if ($verify_result) {
            $out_trade_no = $_POST['out_trade_no'];
            $end_time = I('gmt_payment');
            if (!$end_time) {
                $end_time = I('notify_time');
            }
            $trade_no = $_POST['trade_no'];
            $trade_status = $_POST['trade_status'];
            $total_fee = $_POST['total_fee'];
            $total_fee = number_format($total_fee, 2, '.', '');
            if ($_POST['trade_status'] == 'TRADE_FINISHED') {

            } else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                $trade = M('TradeRecord');
                $info = $trade->where(['trade_no' => $out_trade_no])->find();
                if ($info['trade_state'] == '2') {
                    echo "success";
                    die;
                }
                $user = M('front_user')
                        ->field('unable_coin,coin')
                        ->where(['id' => $info['user_id']])
                        ->find();
                M()->startTrans();

                // 如果金额在充值赠送金额内那么 自动充值金额
                $chang_num =  number_format(0, 2, '.', '');
                $config = getWebConfig('recharge')['recharge'];//充值配置
                $is_gift = FALSE;
                foreach($config as $k => $v){
                    if(intval($v['account']) == intval($total_fee)){
                        if ($v['number'] != '') {
                            $chang_num = number_format($v['number'], 2, '.', '');
                            $is_gift = TRUE;
                            break;
                        }
                    }
                }
                $all_total_fee = number_format(intval($total_fee) + intval($chang_num), 2, '.', '');


                $rs1 = $trade
                        ->where(['trade_no' => $out_trade_no])
                        ->save([
                    'alipay_trade_no' => $trade_no,
                    'etime' => $end_time,
                    'trade_state' => 2,
                    'pay_fee' => $total_fee,
                    'buyer_id' => I('buyer_id'),
                    'buyer_email' => I('buyer_email'),
                ]);
                $rs2 = M('account_log')->add([
                    'user_id' => $info['user_id'],
                    'log_type' => 8,
                    'log_status' => 1,
                    'log_time' => NOW_TIME,
                    'change_num' => $total_fee,
                    'total_coin' => $user['unable_coin'] + $user['coin'] + $total_fee,
                    'platform' => 1,
                    'pay_way' => 1,
                    'order_id' => $out_trade_no,
                    'desc' => '支付宝支付充值',
                    'operation_time' => NOW_TIME,
                ]);


                if ($is_gift) {
                    $rsx = M('account_log')->add([
                        'user_id' => $info['user_id'],
                        'log_type' => 5,
                        'log_status' => 1,
                        'log_time' => NOW_TIME,
                        'change_num' => $chang_num,
                        'total_coin' => $user['unable_coin'] + $user['coin'] + $all_total_fee,
                        'platform' => 1,
                        'pay_way' => 1,
                        'order_id' => $out_trade_no,
                        'desc' => '支付宝支付充值赠送',
                        'operation_time' => NOW_TIME,
                    ]);
                }

                $rs3 = M('front_user')->where(['id' => $info['user_id']])->save(['unable_coin' => $user['unable_coin'] + $all_total_fee]);
                //发送消息
                $rs4 = sendMsg($info['user_id'],'充值通知',"您好，您已完成充值，充值{$all_total_fee}币。");
                if ($rs1 && $rs2 && $rs3 && $rs4) {
                    M()->commit(); //提交事务

                    //充值邀请好友既达标
                    D('Common')->checkPay($info['user_id'], $out_trade_no);

                    echo "success";
                } else {
                    M()->rollback();
                }
            }
            echo "success";
        } else {
            echo "fail";
        }
    }

    public function wx() {
        libxml_disable_entity_loader(true); //禁止引用外部xml实体
        vendor('Payment.Wx.WxPayPubHelper');
        $notify = new \Notify_pub();
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");
            $notify->setReturnParameter("return_msg", "签名失败");
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");
        }
        if ($notify->checkSign() == TRUE) {
            if ($notify->data["return_code"] == "FAIL") {
                
            } elseif ($notify->data["result_code"] == "FAIL") {
                
            } else {
                $returnXml = $notify->returnXml();
                $shop = substr($notify->data['attach'], 0, 4);
                if($shop == 'shop')
                {
                    $shop_id = substr($notify->data['attach'], 4);
                    $res = M('order','tp_',SP_DB)->where(['order_id'=>$shop_id])->save(['pay_status'=>1]);
                    //插入一条支付记录
                    $info = json_decode(D('FrontUser')->decrypt($_COOKIE['u_k']),true);
                    $data['order_id'] = $info['u_k'];
                    $data['action_user'] = 0;
                    $data['order_status'] = 0;
                    $data['shipping_status'] = 0;
                    $data['pay_status'] = 1;
                    $data['log_time'] = time();
                    $data['status_desc'] = '付款成功';
                    $data['action_note'] = '微信支付';
                    $rs = M('order_action','tp_',SP_DB)->add($data);
                    echo $returnXml;
                    die;
                }
                $out_trade_no = $notify->data['attach'];
                $end_time = strtotime($notify->data['time_end']);
                $openid = $notify->data['openid'];
                $bank_type = $notify->data['bank_type'];
                $trade_no = $notify->data['transaction_id'];
                $total_fee = $notify->data['total_fee'] / 100;
                $total_fee = number_format($total_fee, 2, '.', '');
                $trade = M('TradeRecord');
                $info = $trade->where(['trade_no' => $out_trade_no])->find();
                if ($info['trade_state'] == '2') {
                    echo $returnXml;
                    die;
                }
                $user = M('front_user')
                        ->field('unable_coin,coin')
                        ->where(['id' => $info['user_id']])
                        ->find();
                M()->startTrans();

                // 如果金额在充值赠送金额内那么 自动充值金额
                $chang_num =  number_format(0, 2, '.', '');
                $config = getWebConfig('recharge')['recharge'];//充值配置
                $is_gift = FALSE;
                foreach($config as $k => $v){
                    if(intval($v['account']) == intval($total_fee)){
                        if ($v['number'] != '') {
                            $chang_num = number_format($v['number'], 2, '.', '');
                            $is_gift = TRUE;
                            break;
                        }
                    }
                }
                $all_total_fee = number_format(intval($total_fee) + intval($chang_num), 2, '.', '');
                $rs1 = $trade
                        ->where(['trade_no' => $out_trade_no])
                        ->save([
                    'alipay_trade_no' => $trade_no,
                    'etime' => $end_time,
                    'trade_state' => 2,
                    'pay_fee' => $total_fee,
                    'buyer_id' => $openid,
                    'buyer_email' => $bank_type,
                ]);
                $rs2 = M('account_log')->add([
                    'user_id' => $info['user_id'],
                    'log_type' => 8,
                    'log_status' => 1,
                    'log_time' => NOW_TIME,
                    'change_num' => $total_fee,
                    'total_coin' => $user['unable_coin'] + $user['coin'] + $total_fee,
                    'platform' => 1,
                    'pay_way' => 2,
                    'order_id' => $out_trade_no,
                    'desc' => '微信支付充值',
                    'operation_time' => NOW_TIME,
                ]);

                if ($is_gift) {
                    $rsx = M('account_log')->add([
                        'user_id' => $info['user_id'],
                        'log_type' => 5,
                        'log_status' => 1,
                        'log_time' => NOW_TIME,
                        'change_num' => $chang_num,
                        'total_coin' => $user['unable_coin'] + $user['coin'] +$all_total_fee,
                        'platform' => 1,
                        'pay_way' => 2,
                        'order_id' => $out_trade_no,
                        'desc' => '微信支付充值赠送',
                        'operation_time' => NOW_TIME,
                    ]);
                }
                $rs3 = M('front_user')->where(['id' => $info['user_id']])->save(['unable_coin' => $user['unable_coin'] + $all_total_fee]);
                //发送消息
                $rs4 = sendMsg($info['user_id'],'充值通知',"您好，您已完成充值，充值{$all_total_fee}币。");
                if ($rs1 && $rs2 && $rs3 && $rs4) {
                    M()->commit(); //提交事务

                    //充值邀请好友既达标
                    D('Common')->checkPay($info['user_id'], $out_trade_no);

                    echo $returnXml;
                } else {
                    M()->rollback();
                }
            }
        }
    }
}
