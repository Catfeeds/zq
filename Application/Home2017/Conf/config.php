<?php
return array(
    //应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE'    =>    false,
    'URL_MODEL'         =>  2,         // 如果你的环境不支持PATHINFO 请设置为3
    'LOAD_EXT_CONFIG'   => 'pay,url',  //加载扩展配置
    'DATA_CACHE_PREFIX' =>  'home_',   // 缓存前缀
    'SESSION_PREFIX'    =>  'home_',   //session前缀
    'SESSION_EXPIRE'    =>   86400*3,  //session有效期
    // 开启路由
    'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES'=>array(
        //新闻资讯
        'list_n/:class_id\w/[:p\d]'  => 'PublishIndex/publishClass',
        'info_n/:id\d$'              => 'PublishIndex/publishContent',
        'news/:id\d$'                => 'PublishIndex/publishContent',
        'analysts'                   => 'PublishIndex/analysts',
        //图库
        'list_p/:class_id\d/[:p\d]'  => 'GalleryIndex/index',
        'list_p'                     => 'GalleryIndex/index',
        'info_p/:id\d$'              => 'GalleryIndex/picture_list',
	//专家推介
        'list_e/:user_id\w/:class_id\w/[:p\d]'   => 'PublishIndex/userList',
        //推荐大厅
        'football/[:unionid\d]'      => 'GambleHall/index?info=football',
        'basketball/[:unionid\d]'    => 'GambleHall/index?info=basketball',
        'betting/[:unionid\d]'       => 'GambleHall/index?info=betting',
        //统计页面
        'statistics/:game_id\d/[:play_type\d]/[:p\d]'    => 'GambleHall/statistics',
        'statistics_bk/:game_id\d/[:play_type\d]/[:p\d]'    => 'GambleHall/statistics_bk',
        //亚盘排行榜
        'rank/:gameType/:dateType\d/[:p\d]'    => 'GambleHall/rank',
        'profit/:gameType/:dateType\d/[:p\d]'  => 'GambleHall/profit',
        //竞彩排行榜
        'rank_bet/:gameType/:dateType\d/[:p\d]'    => 'GambleHall/rank_bet',
        'profit_bet/:gameType/:dateType\d/[:p\d]'  => 'GambleHall/profit_bet',
        //兑换中心
        'exchange'                   => 'GambleHall/exchange',
        //友情链接
        'link'                       => 'Copyright/blogroll',
        //推荐规则
        'rule'                       => 'GambleHall/rule',
        //我的推荐
        'myGamble/:game_type\d'      => 'GambleHall/myGamble',
        //帮助中心
        'help'                       => 'Help/index',
        'help_list/:classId\d/:class_id\d/[:p\d]'            => 'Help/help_list',
        'help_detail/:classId\d/:class_id\d/:articleId\d$'   => 'Help/help_detail',
        'help_detail/:articleId\d/:is_show\d$'               => 'Help/help_detail',
        'help_search/:keyword/[:p\d]'           => 'Help/help_search',
        //个人主页
        'userindex/:user_id\d/[:game_type\d]'         => 'UserIndex/index',
        //比分
        'analysis/:scheid'=>'FbScore/data',
        'odds_asia/:scheid'=>'FbScore/odds_asia',
        'odds_bigs/:scheid'=>'FbScore/odds_bigs',
        'odds_euro/:scheid'=>'FbScore/odds_euro',
        'detail_action/:scheid'=>'FbScore/event_case',
        'detail_statistical/:scheid'=>'FbScore/event_technology',
        'detail_team/:scheid'=>'FbScore/event_squad',
        //广告转跳
        'adver/:adver_id\d$'     => 'Common/adver',
        //动画直播页面
        'live/:game_id\d$'      =>  'Score/live',
    ),
    'URL_HTML_SUFFIX'   => 'html|htm|xml' , //URL伪静态后缀设置
    'HTML_CACHE_ON'     =>    0, // 开启静态缓存
    'HTML_CACHE_TIME'   =>    60,   // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX'  =>    '.html', // 设置静态缓存文件后缀
    'LINK_NUM'          =>     6,      //设置友情链接单行显示个数
    'HTML_CACHE_RULES'  =>     array(  // 定义静态缓存规则
        //首页
        'Index:'       =>array('Index/{:action}_index','60'),
        //资讯首页
        'PublishIndex:index'=>array('PublishIndex/{:action}_news','60'),
        //情报分析
        'PublishIndex:analysts'=>array('PublishIndex/{:action}_analysts','60'),
        //资讯栏目页
        'PublishIndex:publishClass'=>array('PublishIndex/{:action}_{class_id}','60'),
        //图库首页
        'GalleryIndex:index'=>array('GalleryIndex/{:action}_{class_id}','60'),
        //欧冠专题
        'Special:ouguan'=>array('Special/{:action}_ouguan','60'),
        //欧洲杯专题
        'Special:euro2016'=>array('Special/{:action}_euro2016','60'),
        //帮助中心
        'Help:index'=>array('Help/{:action}_index','60'),
        //集锦首页
        'Highlights:index'=>array('Highlights/{:action}_index','60'),
        //集锦分类页
        'Highlights:more'=>array('Highlights/{:action}_{union_id}','60'),
    )
);