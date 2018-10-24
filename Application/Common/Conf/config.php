<?php
$env = include('env.php');
return array(
    //允许访问的模块列表
    'MODULE_ALLOW_LIST'   =>  array('Home','qqty_admin','Mobile','Daemon','Api','Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400','Api500','Api510','Api520','Apiwx','Api530'),
    'DEFAULT_MODULE'      =>  'Home', //默认访问模块
    'URL_MODULE_MAP'      =>  array('qqty_admin' => 'Admin'), //模块映射
    'URL_MODEL'           =>  2,      // 如果你的环境不支持PATHINFO 请设置为3
    //数据库配置
    'DB_TYPE'             =>  'mysql',
    'DB_DEPLOY_TYPE'      =>  1,      //设置分布式数据库支持
    'DB_RW_SEPARATE'      =>  true,   //读写分离
    //mysql配置
    'DB_HOST'             =>  $env['DB_HOST'],
    'DB_NAME'             =>  $env['DB_NAME'],
    'DB_USER'             =>  $env['DB_USER'],
    'DB_PWD'              =>  $env['DB_PWD'],
    'DB_PREFIX'           =>  'qc_',
    'DB_PORT'             =>  '3306',

    //mogo配置
    'DB_MONGO' => [
        'DB_TYPE'         =>  'mongo',
        'DB_HOST'         =>  $env['MONGO_DB_HOST'],//mongo地址
        'DB_USER'         =>  $env['MONGO_DB_USER'],//mongo账号
        'DB_PWD'          =>  $env['MONGO_DB_PWD'], //mongo密码
        'DB_NAME'         =>  $env['MONGO_DB_NAME'],//mongo数据库名
        'DB_PORT'         =>  '27017',
        'DB_PREFIX'       =>  '',
    ],
    
    'SP_DB'               => $env['SP_DB'],//商城数据库连接
    'KF_DB'               => $env['KF_DB'],//客服数据库
    'apns_env'            => 1,        //0为开发环境，1为生产环境
    'em'                  => 'em_',    //环信相关前缀
    'um'                  => 'umeng_', //友盟推送前缀
    //独立图片服务器地址 img1.qqty.com
    'IMG_SERVER'          => $env['IMG_SERVER'],
    'STATIC_SERVER'       => $env['STATIC_SERVER'],
    //esr链接
    'ESR_URL'             => $env['ESR_URL'],
    'push_adress'         => $env['push_adress'],
    //动画直播地址
    'dh_host'             => $env['dh_host'],
    //mqtt地址
    'MQTT'                => $env['MQTT'],
    'MQTTOLD'             => $env['MQTTOLD'],
    //环信推送
    'Easemob' => [
        'client_id'       => $env['Easemob']['client_id'],
        'client_secret'   => $env['Easemob']['client_secret'],
        'org_name'        => $env['Easemob']['org_name'],
        'app_name'        => $env['Easemob']['app_name'],
    ],
    //友盟推送
    'umeng'   => [
        'AppKey'          => $env['umeng']['AppKey'],
        'AppMasterSecret' => $env['umeng']['AppMasterSecret'],
        'platform'        => $env['umeng']['platform'],

    ],
    //极验配置
    'Gee'     => [
        'GeeKey'          =>  $env['Gee']['GeeKey'],
        'GeeId'           =>  $env['Gee']['GeeId'],
    ],
    //其他配置
    'SHOW_PAGE_TRACE'     => $env['SHOW_PAGE_TRACE'],  //显示调试信息
    'LOAD_EXT_CONFIG'     => 'api,errorCode,odds_config,pay,qqw,score,sdk_config,thirdparty,cover',   //加载扩展配置
    'LOAD_EXT_FILE'       => 'api',                    //加载扩展函数库
    'DEFAULT_TIMEZONE'    => 'Asia/Shanghai',          //设置时区
    'DEFAULT_FILTER'      => 'trim,htmlspecialchars',  //I()方法的默认过滤
    //模板相关配置
    'TMPL_PARSE_STRING'   => array(
        '__UPLOADS__'     => __ROOT__ . '/Uploads', //图片目录
        '__DOWNFILE__'    => __ROOT__ . '/collect/bfdata/', //数据接口目录
        '__DOMAIN__'      => DOMAIN
    ),
    'URL_CASE_INSENSITIVE'=>  false, //设置debug在关闭的时候，生成的url变成小写的问题
    'TMPL_EXCEPTION_FILE' =>  THINK_PATH.'Tpl/think_qqty_error.html',// 异常页面的模板文件
    'HTTP_CACHE_CONTROL'  => 'no-cache,no-store', //缓存时间//'no-cache,no-store,max-age=3600'
    //Redis配置
    'REDIS_HOST'          => $env['REDIS_HOST'],
    'REDIS_PORT'          => 6379,
    'REDIS_AUTH'          => '',
    //缓存配置
    'DATA_CACHE_TYPE'     =>  'Redis',            // 数据缓存类型
    'DATA_CACHE_TIME'     =>  0,                  // 数据缓存有效期 0表示永久缓存
    //Redis Session配置
    'SESSION_AUTO_START'  =>  true,               // 是否自动开启Session
    'SESSION_TYPE'        =>  'Redis',            //session类型
    'SESSION_PERSISTENT'  =>  1,                  //是否长连接(对于php来说0和1都一样)
    'SESSION_CACHE_TIME'  =>  5,                  //连接超时时间(秒)
    'SESSION_EXPIRE'      =>  0,                  //session有效期(单位:秒) 0表示永久缓存
    'SESSION_PREFIX'      =>  'sess_',            //session前缀
    'SESSION_REDIS_HOST'  =>  $env['REDIS_HOST'], //分布式Redis,默认第一个为主服务器
    'SESSION_REDIS_PORT'  =>  '6379',             //端口,如果相同只填一个,用英文逗号分隔
    'SESSION_REDIS_AUTH'  =>  '',                 //Redis auth认证(密钥中不能有逗号),如果相同只填一个,用英文逗号分隔
    //子域名配置
    'APP_SUB_DOMAIN_DEPLOY' =>    true, // 开启子域名配置
    'APP_SUB_DOMAIN_RULES'  =>    array(
        'news'            => array('Home/PublishIndex'),
        'photo'           => array('Home/GalleryIndex'),
        'jc'              => array('Home/GambleHall'),
        'm'               => 'Mobile',
        'bf'              => array('Home/Score'),
        //专题二级域名
        'premierleague'   => array('Home/Special'),
        'laliga'          => array('Home/Special'),
        'bundesliga'      => array('Home/Special'),
        'seriea'          => array('Home/Special'),
        'championsleague' => array('Home/Special'),
        'afccl'           => array('Home/Special'),
        'csl'             => array('Home/Special'),
        'nba'             => array('Home/Special'),
        'cba'             => array('Home/Special'),
        'tennis'          => array('Home/Special'),
        'baseball'        => array('Home/Special'),
        'snooker'         => array('Home/Special'),
        'nfl'             => array('Home/Special'),
        'esports'         => array('Home/Special'),
        'lol'             => array('Home/Special'),
        'dota2'           => array('Home/Special'),
        'pubg'            => array('Home/Special'),
        'pvp'             => array('Home/Special'),
        'pingpong'        => array('Home/Special'),
        'vollyball'       => array('Home/Special'),
        '2018worldcup'    => array('Home/Special'),
        'ligue1'          => array('Home/Special'),
        'wuzhou'          => array('Home/Special'),
        //专家说彩
        'sporttery'       => array('Home/YpRadar'),
        'video'           => array('Home/Video'),
        'data'            => array('Home/Info'),
        '2018zhitongche'  => array('Home/WorldCup'),
    ),
    //最大注册次数
    'regMaxNum'           => $env['regMaxNum'],
    //预生产客服地址
    'kefuUrl'             => $env['kefuUrl']?:'',
    //足球动画关联测试表后缀
    'TableSuffix'         => $env['TableSuffix']?:'',
    'CupquizMode'         => $env['CupquizMode']?:'',

    //生成直播播流推流地址參數
    'LIVE'                => $env['LIVE'],
    'RED_PACKET'          => $env['RED_PACKET'],
//	'LOG_TYPE' => $env['LOG_TYPE'],
);