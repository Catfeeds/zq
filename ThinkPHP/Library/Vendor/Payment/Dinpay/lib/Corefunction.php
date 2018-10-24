<?php
/**
 * @author huangzl<huangzl@qc.mail> 2016.06.22
 */

/**
 * @param $sourceStr
 * @return bool|string
 *
 */
function getRsasing($sourceStr)
{
    if (!$sourceStr) return false;
    $priKey = file_get_contents(str_replace('\\','/',THINK_PATH).'Library/Vendor/Payment/Dinpay/rsa_private_key.pem');
    $priKey = openssl_get_privatekey($priKey);
    openssl_sign($sourceStr, $sign_info, $priKey, OPENSSL_ALGO_MD5);
    return $sign = base64_encode($sign_info);

}

function verifyNotify($responseData)
{
    if (!$responseData) return false;
    $pubKey = file_get_contents(str_replace('\\','/',THINK_PATH).'Library/Vendor/Payment/Dinpay/rsa_public_key.pem');
    $pubKey = openssl_get_publickey($pubKey);

    //接收智付服务器发送的数据
    $merchant_code = $responseData["merchant_code"];
    $notify_type = $responseData["notify_type"];
    $notify_id = $responseData["notify_id"];
    $interface_version = $responseData["interface_version"];
    $sign_type = $responseData["sign_type"];
    $dinpaySign = base64_decode($responseData["sign"]);
    $order_no = $responseData["order_no"];
    $order_time = $responseData["order_time"];
    $order_amount = $responseData["order_amount"];
    $extra_return_param = $responseData["extra_return_param"];
    $trade_no = $responseData["trade_no"];
    $trade_time = $responseData["trade_time"];
    $trade_status = $responseData["trade_status"];
    $bank_seq_no = $responseData["bank_seq_no"];

    //RSA-S验签
    $signStr = "";
    if ($bank_seq_no != "") {
        $signStr = $signStr . "bank_seq_no=" . $bank_seq_no . "&";
    }
    if ($extra_return_param != "") {
        $signStr = $signStr . "extra_return_param=" . $extra_return_param . "&";
    }
    $signStr = $signStr . "interface_version=" . $interface_version . "&";
    $signStr = $signStr . "merchant_code=" . $merchant_code . "&";
    $signStr = $signStr . "notify_id=" . $notify_id . "&";
    $signStr = $signStr . "notify_type=" . $notify_type . "&";
    $signStr = $signStr . "order_amount=" . $order_amount . "&";
    $signStr = $signStr . "order_no=" . $order_no . "&";
    $signStr = $signStr . "order_time=" . $order_time . "&";
    $signStr = $signStr . "trade_no=" . $trade_no . "&";
    $signStr = $signStr . "trade_status=" . $trade_status . "&";
    $signStr = $signStr . "trade_time=" . $trade_time;
    if (openssl_verify($signStr, $dinpaySign, $pubKey, OPENSSL_ALGO_MD5)) {
//        echo "SUCCESS";//异步通知必须响应大写SUCCESS
        /**验签成功，在此处理业务逻辑**/
        return true;
    } else {
        return false;
    }

}

/**
 * 写日志，方便测试（看网站需求，也可以改成把记录存入数据库）
 * 注意：服务器需要开通fopen配置
 * @param $word 要写入日志里的文本内容 默认值：空值
 */
function logResult($word = '', $file = '')
{
    if(empty($file))
        $fp = fopen("Public/log/log.txt","a");
    else
        $fp = fopen('Public/log/'.$file,"a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . strftime("%Y%m%d%H%M%S", time()) . "\n" . $word . "\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}