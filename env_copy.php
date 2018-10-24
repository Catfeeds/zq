<?php
return array(
	'http_type'       => 'http://',       //http协议类型
	'app_debug'       => true,            //是否开启debug
	'SHOW_PAGE_TRACE' => false,           //是否显示调试信息
	'app_domain'      => 'qt.com',        //网站域名
	'DB_HOST'         => '192.168.1.223', //数据库地址
	'DB_NAME'         => 'cms',           //数据库名
	'DB_USER'         => 'cmsdev',        //数据库账号 
	'DB_PWD'          => 'gz1710',        //数据库密码

	'REDIS_HOST'      => '192.168.1.223',     //redis地址

	'MONGO_DB_HOST'   => '121.46.23.146', //mongo地址
	'MONGO_DB_USER'   => '',              //mongo账号
	'MONGO_DB_PWD'    => '',              //mongo密码
	// 'MONGO_DB_HOST'   => '192.168.1.231',   //测试mongo地址
	// 'MONGO_DB_USER'   => 'quancai',         //测试mongo账号
	// 'MONGO_DB_PWD'    => 'quancai2018',     //测试mongo密码
	'MONGO_DB_NAME'   => 'qcsports',      //mongo数据库名
	'SP_DB'           =>'mysql://cmsdev:gz1710@192.168.1.223:3306/tpshop',//商城数据库连接
  	'KF_DB'           => "mysql://kefu:rYyK50ucSMK3NFda@192.168.0.21:3306/kf",//客服数据库
    //MQTT服务器地址
    'MQTT'    => [
        'address'         => 'tcp://192.168.1.248:10241',//安卓
        'host'            => '192.168.1.241',
        'port'            => 1883,
        'wss_port'        => 8084,
        'ws_port'         => 8083,
        'ssl_port'        => 8883,
        'qos'             => 1,
        'useSSL'          => false
    ],
    //旧版mqtt配置
    'MQTTOLD'    => [
        'address'         => 'tcp://121.46.29.199:18080',
        'host'            => '121.46.29.199',
        'port'            => 18080,
        'qos'             => 1,
    ],
	'ESR_URL'         => 'http://183.3.152.226:8883',//esr链接
	'dh_host'         => 'http://dh.qw.com/dh-test-svg/',//动画直播地址
	'IMG_SERVER'      => 'http://183.3.152.226:8089',//独立图片服务器地址
	'STATIC_SERVER'   => '',                         //静态文件服务器地址
	//极验配置
	'Gee'     => [
	    'GeeKey'      =>  "cdcf307dbb79e344118017eb06014a90",
	    'GeeId'       =>  "1382fdc1717a39bfe31ace3e63781c45"
	],
	'push_adress'     => 'http://121.46.29.199:8090/v1/message',//推送
	//环信正式推送key（后面的是生产的）
	'Easemob' => [
	    'client_id'     => 'YXA6JDMygKvuEea6DVksUgsueQ',//'YXA6X7CPEL6REeWnW19-ianXRA',
	    'client_secret' => 'YXA6wEu5V-zsIqmg2OTqhL_sPtzE6K4',//'YXA6QpAdj87hhm5gL4hjhkpGIiEqwPI',
	    'org_name'      => '1158161116115539',//'gdquancai',
	    'app_name'      => 'qqty0',//'qqtyw'
	],
	//友盟推送正式key（后面的是生产的）
	'umeng'   => [
	    'AppKey'          => '578d9a39e0f55afc1b002c84',//'56d01d0ce0f55a59bc0023c8',
	    'AppMasterSecret' => 'e3ed4vese4pdrvbokvnpquk0dsbu0igx',//'qlwsft0o7pdfrwosob29yvu1opkym5vs',
        'platform'          => 'AndroidQQTYTest',
	],
    //最大注册次数
    'regMaxNum' => 4,

    //预生产客服地址(不是预生产请留空)
    'kefuUrl' => 'http://m.customer.qqty.com/#/m/online',
    
    //足球动画关联测试表后缀
    //'TableSuffix'   =>  '_test',
    //生成直播播流推流地址參數
    'LIVE' => [
        'key' =>    'gRzMenlGMT',
        'domain' => 'stream.qqty.com',
        'time'  =>  '+1 hours',
        'AccessKeyId'  =>  '9A2TXQhqrhnIm0hn',
        'AccessKeySecret'  =>  '3N2IQAvh6jfGVnx79XU6nXXbTf0Hzl',
        'RegionId' =>  'cn-shanghai',
        'AppName'  =>   'live_test',//测试及本地的空间
//        'AppName'  =>   'live',//生产的空间
        'PREFIX'   =>   'StreamName',
        'OssBucket'=>   'live-qqty',
        'Notify'   =>   'http://beta-dev.qqty.com:8099/Home/LiveRoom/liveNotifyUrl.html',
    ],
);
