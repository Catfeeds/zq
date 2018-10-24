<?php
$env = include('env.php');
return array(
    //应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE' =>    false,
    'URL_MODEL'         =>  2,         // 如果你的环境不支持PATHINFO 请设置为3
    'LOAD_EXT_CONFIG'   => 'pay,url',  //加载扩展配置
    'DATA_CACHE_PREFIX' =>  '',   // 缓存前缀
    'SESSION_PREFIX'    =>  'home_',   //session前缀
    'SESSION_EXPIRE'    =>   86400*7,  //session有效期
    /* 新增模板中替换字符串 */
    'TMPL_PARSE_STRING' => array(
        '__STATICDOMAIN__' => $env['STATIC_SERVER'],
        '__PUBLIC__' => $env['STATIC_SERVER'].'/Public',
        '__STATIC__' => $env['STATIC_SERVER'].'/Public/Home',
        '__IMAGES__' => $env['STATIC_SERVER'].'/Public/Home/images',
        '__JS__'     => $env['STATIC_SERVER'].'/Public/Home/js',
        '__CSS__'    => $env['STATIC_SERVER'].'/Public/Home/css',
        '__DOMAIN__' => DOMAIN,
    ),

    'URL_HTML_SUFFIX'       => 'html|xml' , //URL伪静态后缀设置
    /* 错误设置 */
    'ERROR_MESSAGE'         =>  '抱歉，您访问的页面不存在！',//错误显示信息,非调试模式有效
    'ERROR_PAGE'            =>  '',  // 错误定向页面
    'SHOW_ERROR_MSG'        =>  false,    // 显示错误信息
    // 开启路由
    'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES' => array(
        //新闻资讯
        'list_n/:class_id\w/[:p\d]'  => array('PublishIndex/publishClass'),
        'tag/:class_id\w/[:p\d]'     => array('Video/videoClass'),
        'info_n/:id\d$'              => array('PublishIndex/publishContent'),
        'news/:date\d/:id\d$'        => array('PublishIndex/publishContent'),
        'news/:id\d$'                => array('PublishIndex/publishContent'),
        //网站公告
        'notice$'                    => array('PublishIndex/publishClass?class_id=6'),
        'notice/:date\d/:id\d$'      => array('PublishIndex/publishContent'),
        //图库
        'list_p/:pid\d'              => array('GalleryIndex/index'),
        'list_p'                     => array('GalleryIndex/index'),
        'info_p/:id\d$'              => array('GalleryIndex/picture_list'),
        //专家推介
        'list_e/:user_id\w/:class_id\w/[:p\d]' => array('PublishIndex/userList'),
        //推荐大厅
        'football/[:unionid\d]'      => array('GambleHall/index?info=football'),
        'basketball/[:unionid\d]'    => array('GambleHall/index?info=basketball'),
        'betting/[:unionid\d]'       => array('GambleHall/index?info=betting'),
        //视频集锦
        'Highlights/[:union_id\d]'   => array('Highlights/index'),
        //视频标签列表
//        'video_n/:class_id\w/[:p\d]'  => 'Video/videoClass',
        //统计页面
        'statistics/:game_id\d/[:play_type\d]/[:p\d]'    => array('GambleHall/statistics'),
        'statistics_bk/:game_id\d/[:play_type\d]/[:p\d]' => array('GambleHall/statistics_bk'),
        //亚盘排行榜
        'rank/:gameType/:dateType\d/[:p\d]'    => array('GambleHall/rank'),
        'profit/:gameType/:dateType\d/[:p\d]'  => array('GambleHall/profit'),
        //竞彩排行榜
        'rank_bet/:gameType/:dateType\d/[:p\d]'   => array('GambleHall/rank_bet'),
        'profit_bet/:gameType/:dateType\d/[:p\d]' => array('GambleHall/profit_bet'),
        //兑换中心
        'exchange'                   => array('GambleHall/exchange'),
        //友情链接
        'link'                       => array('Copyright/blogroll'),
        //推荐规则
        'rule'                       => array('GambleHall/rule'),
        //我的推荐
        'myGamble/:game_type\d'      => array('GambleHall/myGamble'),
        //帮助中心
        'help'                       => array('Help/index'),
        'help_list/:classId\d/:class_id\d/[:p\d]'            => array('Help/help_list'),
        'help_detail/:classId\d/:class_id\d/:articleId\d$'   => array('Help/help_detail'),
        'help_detail/:articleId\d/:is_show\d$'               => array('Help/help_detail'),
        'help_search/:keyword/[:p\d]'           => array('Help/help_search'),
        //个人主页
        'userindex/:user_id\d/[:game_type\d]'   => array('UserIndex/index'),
        //专家个人主页
        'expuser/:user_id\d'        => array('UserIndex/expUser'),
        //广告转跳
        'adver/:adver_id\d$'        => array('Common/adver'),
        //动画直播页面
        'live/:game_id\d$'          => array('Score/live'),
        //资料库
        'league/:season/:union_id'  => array('Info/league'),
        'league/:union_id'          => array('Info/league'),
        'subLeague/:union_id\d'     => array('Info/subLeague'),
        'cupMatch/:season/:union_id'=> array('Info/cupMatch'),
        'cupMatch/:union_id\d'      => array('Info/cupMatch'),
        'team/:team_id\d'           => array('Info/team'),
        'player/:player_id\d'       => array('Info/player'),
        'getrecommend'              => array('Common/getrecommend'),
        //美女主播入口
        'liveRoom/:roomId\d'       => array('LiveRoom/index'),//直播,回播
        'offLine/:userId\d'       => array('LiveRoom/offline'),//主播离线
        //app下载h5推广页
        'App/download'            => array('Public/appDownload'),
        //app下载h5推广页
        'App/introduce'           => array('Public/appIntroduce'),

    ),

    'HTML_CACHE_ON'     =>    0,       // 开启静态缓存
    'HTML_CACHE_TIME'   =>    60,      // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX'  =>    '.html', // 设置静态缓存文件后缀
    'LINK_NUM'          =>    6,       //设置友情链接单行显示个数
    'HTML_CACHE_RULES'  =>    array(   // 定义静态缓存规则
        //首页
        'Index:'               => array('Index/{:action}_index','60'),
        //资讯首页
        'PublishIndex:index'   => array('PublishIndex/{:action}_news','60'),
        //情报分析
        'PublishIndex:analysts'=> array('PublishIndex/{:action}_analysts','60'),
        //资讯栏目页
        'PublishIndex:publishClass'=>array('PublishIndex/{:action}_{class_id}','60'),
        //图库首页
        'GalleryIndex:index'   => array('GalleryIndex/{:action}_{class_id}','60'),
        //帮助中心
        'Help:index'           => array('Help/{:action}_index','60'),
        //集锦首页
        'Highlights:index'     => array('Highlights/{:action}_index','60'),
        //集锦分类页
        'Highlights:more'      => array('Highlights/{:action}_{union_id}','60'),
    )
);