<?php

/**
 * User: chenzj
 * Date: 2016/7/7
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
            $param = I('param.');
            $out_trade_no = $param['out_trade_no'];
            $end_time = I('gmt_payment');
            if (!$end_time) {
                $end_time = I('notify_time');
            }
            $trade_no = $param['trade_no'];
            $total_fee = $param['total_fee'];
            $total_fee = number_format($total_fee, 2, '.', '');
            $total_fee = round($total_fee);
            if ($param['trade_status'] == 'TRADE_FINISHED') {
                
            } else if ($param['trade_status'] == 'TRADE_SUCCESS') {
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
                $rs1 = $trade
                        ->where(['trade_no' => $out_trade_no])
                        ->save([
                    'alipay_trade_no' => $trade_no,
                    'etime' => strtotime($end_time),
                    'trade_state' => 2,
                    'pay_fee' => $total_fee,
                    'buyer_id' => I('buyer_id'),
                    'buyer_email' => I('buyer_email'),
                    'status' => 1,
                ]);
                $rs2 = M('account_log')->add([
                    'user_id' => $info['user_id'],
                    'log_type' => 8,
                    'log_status' => 1,
                    'log_time' => NOW_TIME,
                    'change_num' => $total_fee,
                    'total_coin' => $user['unable_coin'] + $user['coin'] + $total_fee,
                    'platform' => 4,
                    'pay_way' => 1,
                    'order_id' => $out_trade_no,
                    'desc' => '支付宝支付充值',
                    'operation_time' => NOW_TIME,
                ]);
                $rs3 = M('front_user')->where(['id' => $info['user_id']])->save(['unable_coin' => $user['unable_coin'] + $total_fee]);
                //发送消息
                $rs4 = sendMsg($info['user_id'],'充值通知',"您好，您已完成充值，充值{$total_fee}币。");
                if ($rs1 && $rs2 && $rs3 && $rs4) {
                    M()->commit(); //提交事务

                    //充值邀请好友既达标
                    D('Common')->checkPay($info['user_id'], $out_trade_no);

                    echo "success";
                } else {
                    M()->rollback();
                }
            }
        } else {
            echo "fail";
        }
    }

    public function wx() {
        vendor('Payment.Wx.WxPayPubHelper');
        $notify = new \Notify_pub();
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $notify->saveData($xml);
        logRecord("WX交易原始数据：".$xml,'logWx_h5.txt');
        if ($notify->checkSign() == FALSE) {
            $notify->setReturnParameter("return_code", "FAIL");
            $notify->setReturnParameter("return_msg", "签名失败");
            logRecord("WX签名失败",'logWx_h5.txt');
        } else {
            $notify->setReturnParameter("return_code", "SUCCESS");
        }
        if ($notify->checkSign() == TRUE) {
            if ($notify->data["return_code"] == "FAIL") {
                
            } elseif ($notify->data["result_code"] == "FAIL") {
                
            } else {
                logRecord("WX执行充值",'logWx_h5.txt');
                $returnXml = $notify->returnXml();
                $out_trade_no = $notify->data['attach'];
                $end_time = strtotime($notify->data['time_end']);
                $openid = $notify->data['openid'];
                $bank_type = $notify->data['bank_type'];
                $trade_no = $notify->data['transaction_id'];
                $total_fee = $notify->data['total_fee'] / 100;
                $total_fee = number_format($total_fee, 2, '.', '');
                $total_fee = round($total_fee);
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
                $rs1 = $trade
                        ->where(['trade_no' => $out_trade_no])
                        ->save([
                    'alipay_trade_no' => $trade_no,
                    'etime' => $end_time,
                    'trade_state' => 2,
                    'pay_fee' => $total_fee,
                    'buyer_id' => $openid,
                    'buyer_email' => $bank_type,
                    'status' => 1,
                ]);
                $rs2 = M('account_log')->add([
                    'user_id' => $info['user_id'],
                    'log_type' => 8,
                    'log_status' => 1,
                    'log_time' => NOW_TIME,
                    'change_num' => $total_fee,
                    'total_coin' => $user['unable_coin'] + $user['coin'] + $total_fee,
                    'platform' => 4,
                    'pay_way' => 2,
                    'order_id' => $out_trade_no,
                    'desc' => '微信支付充值',
                    'operation_time' => NOW_TIME,
                ]);
                $rs3 = M('front_user')->where(['id' => $info['user_id']])->save(['unable_coin' => $user['unable_coin'] + $total_fee]);
                //发送消息
                $rs4 = sendMsg($info['user_id'],'充值通知',"您好，您已完成充值，充值{$total_fee}币。");
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
