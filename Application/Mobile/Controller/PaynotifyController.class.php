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
        logRecord("Alipay交易原始数据：".json_encode($_POST),'logAli_h5.txt');
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
            if($param['trade_status'] == 'TRADE_SUCCESS' || $param['trade_status'] == 'TRADE_FINISHED') {
                $trade = M('TradeRecord');
                $info = $trade->where(['trade_no' => $out_trade_no])->find();
                if(!$info){
                    echo "fail";
                    die;
                }
                if ($info['trade_state'] == '2') {
                    echo "success";
                    die;
                }
                $rs1 = $trade->where(['trade_no' => $out_trade_no])->save([
                        'alipay_trade_no' => $trade_no,
                        'etime'           => strtotime($end_time),
                        'trade_state'     => 2,
                        'pay_fee'         => $total_fee,
                        'buyer_id'        => I('buyer_id'),
                        'buyer_email'     => I('buyer_email'),
                        'status'          => 1,
                ]);
                if ($rs1) {
                    ExecutiveRecharge($out_trade_no);
                    echo "success";
                } else {
                    echo "fail";
                }
            }
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
                $returnXml = $notify->returnXml();
                $out_trade_no = $notify->data['attach'];
                $end_time = strtotime($notify->data['time_end']);
                $openid = $notify->data['openid'];
                $bank_type = $notify->data['bank_type'];
                $trade_no = $notify->data['transaction_id'];
                $total_fee = $notify->data['total_fee'] / 100;
                $total_fee = number_format($total_fee, 2, '.', '');
                $total_fee = round($total_fee);
                logRecord("WX执行充值".$out_trade_no,'logWx_h5.txt');
                $trade = M('TradeRecord');
                $info = $trade->where(['trade_no' => $out_trade_no])->find();
                if (!$info || $info['trade_state'] == '2') {
                    echo $returnXml;
                    die;
                }

                $rs1 = $trade->where(['trade_no' => $out_trade_no])->save([
                        'alipay_trade_no' => $trade_no,
                        'etime' => $end_time,
                        'trade_state' => 2,
                        'pay_fee' => $total_fee,
                        'buyer_id' => $openid,
                        'buyer_email' => $bank_type,
                        'status' => 1,
                ]);

                if ($rs1) {
                    ExecutiveRecharge($out_trade_no);
                    echo $returnXml;
                } else {
                    echo $returnXml;
                }
            }
        }else{
            echo "fail";
        }
    }

}
