<?php
/**

本配置在各版本的Api接口模块通用，修改时请注意兼容

 */

/**
 * pc第三方登陆配置
 */
define('URL_CALLBACK', 'https://www.' . DOMAIN . '/User/callback/type/'); //定义pc端回调URL通用的URL

$pc_sdk_login = array(
    //流量充值
    'THINK_SDK_FLOW' => array(
        'APP_KEY'    =>'shengya-qqty',
        'APP_SECRET' =>'395xyhc8xwj2rccmq4vr0bcdu6h8mut3',
    ),
    //支付宝登录
    'THINK_SDK_ALIPAY' => array(
        'APP_KEY'    => '',
        'APP_SECRET' => '',
        'CALLBACK'   => URL_CALLBACK . 'alipay',
    ),
    //微信登录
    'THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wxc183c9c134780a53',                  //应用注册成功后分配的 APP ID
        'APP_SECRET' => '54cd95f3155248b39f7dddd55494b45c',    //应用注册成功后分配的KEY
        'CALLBACK'   => URL_CALLBACK . 'weixin',
    ),
    //腾讯QQ登录配置
    'THINK_SDK_QQ' => array(
        'APP_KEY'    => '101310152',
        'APP_SECRET' => 'f1cd05f8f85f060e3bff2357858096fd',
        'CALLBACK'   => URL_CALLBACK . 'qq',
    ),
    //新浪微博配置
    'THINK_SDK_SINA' => array(
        'APP_KEY'    => '1064305106',
        'APP_SECRET' => '62c9a2d0d96128746a3c82a73939bb46',
        'CALLBACK'   => URL_CALLBACK . 'sina',
    ),
);

/**
 * APP第三方登陆配置
 */
$app_sdk_login = array(
    //微信小程序登录
    'WEIXIN_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx2e2e62a6a9ac5b17',
        'APP_SECRET' => 'dfd4df3253d11f3db3395dfc4b46deb6',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/jscode2session',
    ),

    /*------------------全球体育 com.qqtyw.ios- 公司版-----------------*/
    //微信登录
    'APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx52a78139e91e020a',
        'APP_SECRET' => '1c7f0657e417a18fb4da668e3b100d09',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '1105151415',
        'APP_SECRET' => 'VQo1oxaI3nAqgsXq',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '2544650897',
        'APP_SECRET' => '46b0ce43759e763a1b6adc252bde8941',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),
    //移动号码登陆配置
    'APP_THINK_SDK_MM' => array(
        'APP_KEY'    => '', //无需（但留此字段）
        'APP_SECRET' => '', //无需（但留此字段）
        'TokenUri'   => '', //无需（但留此字段）
    ),

    /*------------------新公司包------------------*/
    //微信登录
    'ZUZU_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx52a78139e91e020a',
        'APP_SECRET' => '1c7f0657e417a18fb4da668e3b100d09',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'ZUZU_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '1105151415',
        'APP_SECRET' => 'VQo1oxaI3nAqgsXq',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'ZUZU_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '2544650897',
        'APP_SECRET' => '46b0ce43759e763a1b6adc252bde8941',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),
    //移动号码登陆配置
    'ZUZU_APP_THINK_SDK_MM' => array(
        'APP_KEY'    => '', //无需（但留此字段）
        'APP_SECRET' => '', //无需（但留此字段）
        'TokenUri'   => '', //无需（但留此字段）
    ),

    /*------------------全球体育彩票版 com.qqtyw.ios.two------------------*/
    //微信登录
    'COMPANY_TWO_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx13bfd24152dd9184',
        'APP_SECRET' => 'ce5881d0e77c3592fb105f6a69fe8b78',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'COMPANY_TWO_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '1105151415',
        'APP_SECRET' => 'VQo1oxaI3nAqgsXq',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'COMPANY_TWO_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '2544650897',
        'APP_SECRET' => '46b0ce43759e763a1b6adc252bde8941',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),
    //移动号码登陆配置
    'COMPANY_TWO_APP_THINK_SDK_MM' => array(
        'APP_KEY'    => '',
        'APP_SECRET' => '',
        'TokenUri'   => '',
    ),

    /*------------------全球体育企业版 com.qqty.enterprise------------------*/
    //微信登录
    'ENTERPRISE_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx13bfd24152dd9184',
        'APP_SECRET' => 'ce5881d0e77c3592fb105f6a69fe8b78',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'ENTERPRISE_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '1105151415',
        'APP_SECRET' => 'VQo1oxaI3nAqgsXq',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'ENTERPRISE_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '2544650897',
        'APP_SECRET' => '46b0ce43759e763a1b6adc252bde8941',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),
    //移动号码登陆配置
    'ENTERPRISE_APP_THINK_SDK_MM' => array(
        'APP_KEY'    => '',
        'APP_SECRET' => '',
        'TokenUri'   => '',
    ),

    /*------------------全球体育竞彩版 com.qqty.ios------------------*/
    //微信登录
    'PERSONAL_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx52a78139e91e020a',
        'APP_SECRET' => '1c7f0657e417a18fb4da668e3b100d09',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'PERSONAL_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '1105151415',
        'APP_SECRET' => 'VQo1oxaI3nAqgsXq',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'PERSONAL_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '666299454',
        'APP_SECRET' => '066e1d35f34a728c184d303248ae9343',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),
    //移动号码登陆配置
    'PERSONAL_APP_THINK_SDK_MM' => array(
        'APP_KEY'    => '',
        'APP_SECRET' => '',
        'TokenUri'   => '',
    ),

    /*------------------分析大师 MASTER------------------*/
    //微信登录
    'MASTER_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wxd5ca243d4e8145c1',
        'APP_SECRET' => '066f452be7916c9a035a74fe650bc72d',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'MASTER_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '1105620032',
        'APP_SECRET' => 'ceuwCyHAWNlg62Ua',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'MASTER_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '1594036254',
        'APP_SECRET' => '9a287b40601b06144be62270872a7b48',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),

    /*------------------世界杯版 WORLDCUP------------------*/
    //微信登录
    'WORLDCUP_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx52a78139e91e020a',
        'APP_SECRET' => '1c7f0657e417a18fb4da668e3b100d09',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'WORLDCUP_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '101310152',
        'APP_SECRET' => 'f1cd05f8f85f060e3bff2357858096fd',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'WORLDCUP_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '2544650897',
        'APP_SECRET' => '46b0ce43759e763a1b6adc252bde8941',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),

    /*------------------比分宝典版 WORLDCUP------------------*/
    //微信登录
    'VALUABLEBOOK_APP_THINK_SDK_WEIXIN' => array(
        'APP_KEY'    => 'wx52a78139e91e020a',
        'APP_SECRET' => '1c7f0657e417a18fb4da668e3b100d09',
        'TokenUri'   => 'https://api.weixin.qq.com/sns/oauth2/access_token',
    ),
    //腾讯QQ登录配置
    'VALUABLEBOOK_APP_THINK_SDK_QQ' => array(
        'APP_KEY'    => '101310152',
        'APP_SECRET' => 'f1cd05f8f85f060e3bff2357858096fd',
        'TokenUri'   => 'https://graph.qq.com/oauth2.0/me',
    ),
    //新浪微博配置
    'VALUABLEBOOK_APP_THINK_SDK_SINA' => array(
        'APP_KEY'    => '2544650897',
        'APP_SECRET' => '46b0ce43759e763a1b6adc252bde8941',
        'TokenUri'   => 'https://api.weibo.com/oauth2/get_token_info',
    ),

);

return array_merge($pc_sdk_login,$app_sdk_login);