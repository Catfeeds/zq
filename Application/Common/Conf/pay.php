<?php
/**

本配置在各版本的Api接口模块通用，修改时请注意兼容

 */

/**
 * APP支付配置
 */
return array(
    //支付宝支付
    'appalipay' => [
        'alipay_config'=>array(
            'partner'          => '2088121641347845',
            'seller_email'     => '15989161144@163.com',
            'key'              => 'w40h7rdxq619sikmajcvlmyjrj5i6qw3',
            'sign_type'        => strtoupper('RSA'),
            'input_charset'    => strtolower('utf-8'),
            'cacert'           => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/cacert.pem',
            'private_key_path' => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/rsa_private_key.pem',
            'public_key_path'  => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/rsa_private_key.pem',
            'ali_public_key_path' => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/alipay_public_key.pem',
            'transport'        => 'http',
        ),
        'notify_url'=>'https://www.qqty.com/Api103/PayNotify/notifyAlipay',
    ],

	//支付宝h5支付配置
    'alipay' => [
        'alipay_config'=>array(
            'partner'       => '2088121641347845',
            'seller_email'  => '15989161144@163.com',
            'key'           => 'w40h7rdxq619sikmajcvlmyjrj5i6qw3',
            'sign_type'     => strtoupper('MD5'),
            'input_charset' => strtolower('utf-8'),
            'cacert'        => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/cacert.pem',
            'private_key_path' => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/rsa_private_key.pem',
            'public_key_path' => getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/rsa_private_key.pem',
            'ali_public_key_path' =>getcwd().'/ThinkPHP/Library/Vendor/Payment/Alipay/aliKey/alipay_public_key.pem',
            'transport'     => 'https',
        ),
       // 'notify_url'=>'https://183.3.152.226:8099/Home/Paynotify/ali',  //qw
      //  'return_url'=> 'https://www.qw.com/UserInfo/index.html',          //qw
        'notify_url'=>'https://m.qqty.com/Paynotify/ali',
        'return_url'=> '',
    ],

    //微信支付
    'appwxpay' => [
        'wxpay_config'=>array(
            'appid'         => 'wx52a78139e91e020a',   //微信开放平台审核通过的应用APPID
            'mch_id'        => '1326729301',
            'appsecret'     => '1c7f0657e417a18fb4da668e3b100d09',
            'key'           => '5ccfe05101bda2ebfddda9131558e7c2',
            'seller_email'  => '15989161144@163.com',
        ),
        'notify_url'=>'https://www.qqty.com/Api103/PayNotify/notifyWxpay',
    ],

    //微信支付(竞彩版)
    'personal_appwxpay' => [
        'wxpay_config'=>array(
            'appid'         => 'wx13bfd24152dd9184',   //微信开放平台审核通过的应用APPID
            'mch_id'        => '1309224101',
            'appsecret'     => 'ce5881d0e77c3592fb105f6a69fe8b78',
            'key'           => '5ccfe05101bda2ebfddda9131558e7c2',
            'seller_email'  => '15989161144@163.com',
        ),
        'notify_url'=>'https://www.qqty.com/Api103/PayNotify/notifyWxpay',
    ],

    //微信支付(分析大师)
    'master_appwxpay' => [
        'wxpay_config'=>array(
            'appid'         => 'wxd5ca243d4e8145c1',   //微信开放平台审核通过的应用APPID
            'mch_id'        =>  '1463750302',
            'appsecret'     => '066f452be7916c9a035a74fe650bc72d',
            'key'           => 'pmZIGBfZYgyDnL29CAFvlteMSbQ2pmb0',
            'seller_email'  => '15989161144@163.com',
        ),
        'notify_url'=>'https://www.qqty.com/Api103/PayNotify/notifyWxpay',
    ],

    //易宝支付
    'appyeepay' => [
        'yeepay_config'=>array(
            //商户编号
            'merchantaccount'    => '10013611054',
            //商户私钥
            'merchantPrivateKey' => 'MIICdQIBADANBgkqhkiG9w0BAQEFAASCAl8wggJbAgEAAoGBAJqL8mnIjJU6EIIshvfFfsBHRVDYS4iKgvjrqnuCasgrkef/leAcx8z8jEdBQ9N9ikx89IRqjnbxK/X+E9SHsLcwMESys9lhXLv/VPabq08cWR+uWO8V8XHnrwYREb45FP2Ib3rviQ36lNA/NA58xKSpFLvuyaw/3aeE2T/yDP65AgMBAAECgYAiAJZmJcSgdHT7XvaW/vHDNisO/Xeo4+irAZaxs+Dwh32DkJ0WAN8Iv6vRZY+ZsW5DI97cX2FW0/r7FVqhkPUVEN9iVEnSKkFhk4GKIf9e+d82+UX4K3V5HAocHHIay3TgzNuM/8smc6IFuRVCGVOFTGjMh9dfa4QkxzUpvlusgQJBANw4Y4RiLc/A8uL7ovYFTR5V3/fDBwoE2TN+Z6VgBl0eWF9EPRt4O0OwWQKPSIDoTJ9POqGC3sqHw25dDLli8QkCQQCzp//LlZuo1tt4gnzc9kseO8ODS1SNtwD3ULLB3rihZczz+Hv/fX4+ObdB2JPMtj/t0w1/IewRnffpx964GPwxAj8QWB8KtD6yLJ/aONLfb4VJuxXkTZU4KSP1rmwC2h2ey6HDcE0YvNOpMm1owzmeV59YM4kmc5AVd/4JMt6+45kCQDanN/DV/Xmaiz+Y6qVJo8Z5xduOMhW+g2O5P/gsahxnXLcnc4lgwuPpKO+2FBhlpQMTfnqbdjZRyRZsgDufFjECQQCY6Y0A2Hw6s+4ptQZNGxv6jPBycOjVm5JgTVVipNdh1OtQ6tbx62fcUT5agccS5tyaXpaXXlGYRViyri0w+sG4',
            //商户公钥
            'merchantPublicKey'  => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCai/JpyIyVOhCCLIb3xX7AR0VQ2EuIioL466p7gmrIK5Hn/5XgHMfM/IxHQUPTfYpMfPSEao528Sv1/hPUh7C3MDBEsrPZYVy7/1T2m6tPHFkfrljvFfFx568GERG+ORT9iG9674kN+pTQPzQOfMSkqRS77smsP92nhNk/8gz+uQIDAQAB',
            //易宝公钥
            'yeepayPublicKey'    => 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCWNABqEQ4Y8OOt9McBwHSI4Jo2w3W4qUx6KEG6mkMhkFfA2cTm4/rfRH9EsGteKDaGfnNvnRemIbIK2haP2MfqPpDwle6ZSLktusHB0z9PKbkCP7gH9c9LXyPI1u5Zix27lxloc8w+1aB9v/Y6OBP15+K0ZVk0kEOwPFAT1lyq6wIDAQAB',
        ),
        'return_url'=>'https://www.qqty.com/Api103/PayNotify/notifyYeepay/type/return', //页面跳转
        'notify_url'=>'https://www.qqty.com/Api103/PayNotify/notifyYeepay/type/notify', //回调通知
    ],

    //wabp支付
    'appwabppay' => [
        //内容(价格)对应wabp平台的sin
        'content' => [
            '6'  => 'kdddy',
            '10' => 'kdddo',
            '16' => 'kdddn',
        ],
        //sin对应wabp返回的ServiceId
        'verify_content' => [
            'kdddy' => '41114',
            'kdddo' => '41116',
            'kdddn' => '41118',
        ],
        'ch' => '41120', //渠道号码
        'ex' => '169000', //渠道扩展id
        'private_key_path'     => getcwd().'/ThinkPHP/Library/Vendor/Payment/wabp/dsakey/dsakey-priv.pem',      //合作伙伴私钥
        'wabp_public_key_path' => getcwd().'/ThinkPHP/Library/Vendor/Payment/wabp/dsakey/dsakey-wabp-pub.pem',  //wabp平台公钥
        'return_url'  => 'https://www.qqty.com/Api103/PayNotify/notifyWabpPay/type/return',  //返回合作方的url
        // 'comfirm_url' => 'https://www.qqty.com/Api103/PayNotify/notifyWabpPay/type/confirm', //确认用户状态地址 需配置在wabp平台
        // 'notify_url'  => 'https://www.qqty.com/Api103/PayNotify/notifyWabpPay/type/notify',  //订购关系同步地址 需配置在wabp平台
    ],

    //安卓移动支付
    'androidmovepay' => [
        'appid'         => 'P-gdqc-001',   //收款方应用APPID
        'appkey'        => 'bc29e678889269f5',
        'servpltfmcode' => 'plt-gdqc-001',
    ],

);

?>