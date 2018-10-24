<?php
//支付配置
return array(
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
//        'notify_url'=>'http://183.3.152.226:8099/Home/Paynotify/ali',  //qw
      //  'return_url'=> 'https://www.qw.com/UserInfo/index.html',          //qw
        'notify_url'=>'https://www.qqty.com/Home/Paynotify/ali',
        'return_url'=> 'https://www.qqty.com/UserInfo/index.html',
    ],
    'wxpay' => [
        'wxpay_config'=>array(
            'appid'         => 'wx4e27335fb7cfba88' ,
            'mch_id'        => '1301380001' ,
            'appsecret'     => '5618405b6273235c665667ab9add0008' ,
            'key'           => '5ccfe05101bda2ebfddda9131558e7c2' ,
            'seller_email'  => '15989161144@163.com' ,
        ),
        //'notify_url'=>'https://183.3.152.226:8088/Home/Paynotify/wx' //qqw
//        'notify_url'=>'http://183.3.152.226:8099/Home/Paynotify/wx',         //qw
        'notify_url'=> 'https://www.qqty.com/Home/Paynotify/wx'
    ]
);
?>