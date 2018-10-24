<?php
$env = include('env.php');
return array(
    //应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE'    =>    false,
    'URL_MODEL'         =>  2, // 如果你的环境不支持PATHINFO 请设置为3
    'LOAD_EXT_CONFIG'   =>  'url,pay', 
    'DATA_CACHE_PREFIX' =>  '',  // 缓存前缀
    'SESSION_PREFIX'    =>  'home_',   //session前缀
    'SESSION_EXPIRE'    =>  86400*7,
    /* 新增模板中替换字符串 */
    'TMPL_PARSE_STRING' => array(
        '__STATICDOMAIN__' => $env['STATIC_SERVER'],
        '__PUBLIC__'    => $env['STATIC_SERVER'].'/Public',
        '__STATIC__'    => $env['STATIC_SERVER'].'/Public/'.MODULE_NAME,
        '__VIDEO__'     => $env['STATIC_SERVER'].'/Public/'.MODULE_NAME.'/video',
        '__IMAGES__'    => $env['STATIC_SERVER'].'/Public/'.MODULE_NAME.'/images',
        '__JS__'        => $env['STATIC_SERVER'].'/Public/'.MODULE_NAME.'/js',
        '__CSS__'       => $env['STATIC_SERVER'].'/Public/'.MODULE_NAME.'/css',
        '__DOMAIN__'    => DOMAIN,
    ),
    'URL_HTML_SUFFIX'       => 'html|xml' , //URL伪静态后缀设置
    /* 错误设置 */
    'ERROR_MESSAGE'         =>  '抱歉，您访问的页面不存在！',//错误显示信息,非调试模式有效
    'ERROR_PAGE'            =>  '',  // 错误定向页面
    'SHOW_ERROR_MSG'        =>  false,    // 显示错误信息

    // 'TMPL_ACTION_ERROR'     =>  'Public:error', // 默认错误跳转对应的模板文件
    // 'TMPL_ACTION_SUCCESS'   =>  'Public:success', // 默认成功跳转对应的模板文件
    // 开启路由
    'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES'=>array(
        //新闻资讯
        'list_n/:class_id\w/[:p\d]'  => 'News/index',
        'info_n/:id\d$'              => 'News/detail',
        'news/:id\d$'                => 'News/detail',
        'info_p/:id\d$'              => 'News/detail_g',
        //个人主页
        'userindex/:user_id\d/[:type\d]' => 'Guess/other_page',
        //广告转跳
        'adver/:adver_id\d$'          => 'Common/adver',
        //专家个人主页
        'expUser/:user_id\d/:type\d$'              => 'User/expUser',
        'expUser/:user_id\d$'              => 'User/expUser',
        //专题页资讯详情
        'seriea/news/:id\d$'          =>  'Special/news',//意甲
        'bundesliga/news/:id\d$'      =>  'Special/news',//德甲
        'laliga/news/:id\d$'          =>  'Special/news',//西甲
        'csl/news/:id\d$'             =>  'Special/news',//中超
        'championsleague/news/:id\d$' =>  'Special/news',//欧冠
        'afccl/news/:id\d$'           =>  'Special/news',//亚冠
        'nba/news/:id\d$'             =>  'Special/news',//NBA
        'cba/news/:id\d$'             =>  'Special/news',//CBA
        'tennis/news/:id\d$'          =>  'Special/news',//网球
        'snooker/news/:id\d$'         =>  'Special/news',//斯诺克
        'nfl/news/:id\d$'             =>  'Special/news',//橄榄球
        'pingpong/news/:id\d$'        =>  'Special/news',//乒乓球
        'vollyball/news/:id\d$'       =>  'Special/news',//排球
        'baseball/news/:id\d$'        =>  'Special/news',//棒球
        'lol/news/:id\d$'             =>  'Special/news',//英雄联盟
        'pvp/news/:id\d$'             =>  'Special/news',//王者荣耀
        'pubg/news/:id\d$'            =>  'Special/news',//绝地求生
        'dota2/news/:id\d$'           =>  'Special/news',//DOTA2
        'premierleague/news/:id\d$'   =>  'Special/news',//英超
        '2018worldcup/news/:id\d$'    =>  'Special/news',//世界杯
        'wuzhou/news/:id\d$'          =>  'Special/news',//五洲
        'ligue1/news/:id\d$'          =>  'Special/news',//法甲
        'general/news/:id\d$'         =>  'Special/news',//没有时使用
        'sporttery/news/:id\d$'       =>  'Special/news',//专家说彩
        //二级目录
        // 'djmj/news/:id\d$'            =>  'Special/news',//独家秘笈
        // 'dujia/news/:id\d$'           =>  'Special/news',//独家解盘
        // 'jingcai/news/:id\d$'         =>  'Special/news',//竞彩前瞻
        // 'beidan/news/:id\d$'          =>  'Special/news',//北单推荐
        // 'others/news/:id\d$'          =>  'Special/news',//世界杯其他
        // 'england/news/:id\d$'         =>  'Special/news',//英格兰
        // 'russia/news/:id\d$'          =>  'Special/news',//俄罗斯
        // 'spain/news/:id\d$'           =>  'Special/news',//西班牙
        // 'belgium/news/:id\d$'         =>  'Special/news',//比利时
        // 'germany/news/:id\d$'         =>  'Special/news',//德国
        // 'brazil/news/:id\d$'          =>  'Special/news',//巴西
        // 'france/news/:id\d$'          =>  'Special/news',//法国
        // 'portugal/news/:id\d$'        =>  'Special/news',//葡萄牙
        // 'argentina/news/:id\d$'       =>  'Special/news',//阿根廷
        // 'INTER/news/:id\d$'           =>  'Special/news',//国际米兰
        // 'ACM/news/:id\d$'             =>  'Special/news',//AC米兰
        // 'JUV/news/:id\d$'             =>  'Special/news',//尤文图斯
        // 'djzq/news/:id\d$'            =>  'Special/news',//德甲诸强
        // 'DOT/news/:id\d$'             =>  'Special/news',//多特蒙德
        // 'FCB/news/:id\d$'             =>  'Special/news',//拜仁
        // 'AMAD/news/:id\d$'            =>  'Special/news',//马竞
        // 'BAR/news/:id\d$'             =>  'Special/news',//巴萨
        // 'RMAD/news/:id\d$'            =>  'Special/news',//皇马
        // 'ARS/news/:id\d$'             =>  'Special/news',//阿森纳
        // 'LIV/news/:id\d$'             =>  'Special/news',//利物浦
        // 'CFC/news/:id\d$'             =>  'Special/news',//切尔西
        // 'MNC/news/:id\d$'             =>  'Special/news',//曼城
        // 'MNU/news/:id\d$'             =>  'Special/news',//曼联
        'notice/news/:id\d$'          =>  'Special/news',//网站公告

        //赛程专题页跳转路由
        'premierleague/info$'         =>  'Schedulelist/info',//英超排行榜
        'seriea/info$'                =>  'Schedulelist/info',//意甲
        'bundesliga/info$'            =>  'Schedulelist/info',//德甲
        'laliga/info$'                =>  'Schedulelist/info',//西甲
        'csl/info$'                   =>  'Schedulelist/info',//中超
        'championsleague/info$'       =>  'Schedulelist/info',//欧冠
        'afccl/info$'                 =>  'Schedulelist/info',//亚冠
        'nba/info$'                   =>  'Schedulelist/info',//NBA
        'cba/info$'                   =>  'Schedulelist/info',//CBA
        '2018worldcup/info$'          =>  'Schedulelist/info',//俄罗斯世界杯
        'premierleague/rank$'         =>  'Schedulelist/rank', //英超排行榜
        'seriea/rank$'                =>  'Schedulelist/rank',//意甲
        'bundesliga/rank$'            =>  'Schedulelist/rank',//德甲
        'laliga/rank$'                =>  'Schedulelist/rank',//西甲
        'csl/rank$'                   =>  'Schedulelist/rank',//中超
        'championsleague/rank$'       =>  'Schedulelist/rank',//欧冠
        'afccl/rank$'                 =>  'Schedulelist/rank',//亚冠
        'nba/rank$'                   =>  'Schedulelist/rank',//NBA
        'cba/rank$'                   =>  'Schedulelist/rank',//CBA
        '2018worldcup/rank$'          =>  'Schedulelist/rank',//俄罗斯世界杯
        'premierleague/schedule$'     =>  'Schedulelist/index', //英超
        'seriea/schedule$'            =>  'Schedulelist/index',//意甲
        'bundesliga/schedule$'        =>  'Schedulelist/index',//德甲
        'laliga/schedule$'            =>  'Schedulelist/index',//西甲
        'csl/schedule$'               =>  'Schedulelist/index',//中超
        'championsleague/schedule$'   =>  'Schedulelist/index',//欧冠
        'afccl/schedule$'             =>  'Schedulelist/index',//亚冠
        'nba/schedule$'               =>  'Schedulelist/index',//NBA
        'cba/schedule$'               =>  'Schedulelist/index',//CBA
        '2018worldcup/schedule$'      =>  'Schedulelist/index',//俄罗斯世界杯

        //专题页跳转路由
        'seriea$'                      =>  'Special/index',//意甲
        'bundesliga$'                  =>  'Special/index',//德甲
        'laliga$'                      =>  'Special/index',//西甲
        'csl$'                         =>  'Special/index',//中超
        'championsleague$'             =>  'Special/index',//欧冠
        'afccl$'                       =>  'Special/index',//亚冠
        'nba$'                         =>  'Special/index',//NBA
        'cba$'                         =>  'Special/index',//CBA
        'tennis$'                      =>  'Special/index',//网球
        'snooker$'                     =>  'Special/index',//斯诺克
        'nfl$'                         =>  'Special/index',//橄榄球
        'pingpong$'                    =>  'Special/index',//乒乓球
        'vollyball$'                   =>  'Special/index',//排球
        'lol$'                         =>  'Special/index',//英雄联盟
        'pvp$'                         =>  'Special/index',//王者荣耀
        'pubg$'                        =>  'Special/index',//绝地求生
        'dota2$'                       =>  'Special/index',//DOTA2
        'premierleague$'               =>  'Special/index',//英超
        '2018worldcup$'                =>  'Special/index',//世界杯
        'wuzhou$'                      =>  'Special/index',//五洲
        'ligue1$'                      =>  'Special/index',//法甲
        //独家解盘二级资讯栏目页
        'sporttery/dujia$'            => 'News/index?class_id=10',//独家
        'sporttery/jingcai$'          => 'News/index?class_id=54',//竞彩
        'sporttery/beidan$'           => 'News/index?class_id=55',//北单
        'sporttery/djmj$'             => 'News/index?class_id=62',//秘籍
        'sporttery/girl$'             => 'News/index?class_id=girl',//美女
        //二级资讯栏目页
        // 'others$'           => 'Special/articleList?class_id=106',//世界杯其他
        // 'england$'          => 'Special/articleList?class_id=105',//英格兰
        // 'russia$'           => 'Special/articleList?class_id=104',//俄罗斯
        // 'spain$'            => 'Special/articleList?class_id=103',//西班牙
        // 'belgium$'          => 'Special/articleList?class_id=102',//比利时
        // 'germany$'          => 'Special/articleList?class_id=101',//德国
        // 'brazil$'           => 'Special/articleList?class_id=100',//巴西
        // 'france$'           => 'Special/articleList?class_id=99',//法国
        // 'portugal$'         => 'Special/articleList?class_id=98',//葡萄牙
        // 'argentina$'        => 'Special/articleList?class_id=97',//阿根廷
        // 'INTER$'            => 'Special/articleList?class_id=92',//国际米兰
        // 'ACM$'              => 'Special/articleList?class_id=91',//AC米兰
        // 'JUV$'              => 'Special/articleList?class_id=90',//尤文图斯
        // 'djzq$'             => 'Special/articleList?class_id=89',//德甲诸强
        // 'DOT$'              => 'Special/articleList?class_id=88',//多特蒙德
        // 'FCB$'              => 'Special/articleList?class_id=87',//拜仁
        // 'AMAD$'             => 'Special/articleList?class_id=86',//马竞
        // 'BAR$'              => 'Special/articleList?class_id=85',//巴萨
        // 'RMAD$'             => 'Special/articleList?class_id=84',//皇马
        // 'ARS$'              => 'Special/articleList?class_id=83',//阿森纳
        // 'LIV$'              => 'Special/articleList?class_id=82',//利物浦
        // 'CFC$'              => 'Special/articleList?class_id=81',//切尔西
        // 'MNC$'              => 'Special/articleList?class_id=80',//曼城
        // 'MNU$'              => 'Special/articleList?class_id=79',//曼联
        // 'notice$'           => 'Special/articleList?class_id=6',//网站公告
        
        //规范视频专区ajax路由
        'video/getVideoList$'=>  'Highlights/getVideoList',
        //视频播放详情页
        'video/:id\d$'      =>  'Highlights/info',
        //视频标签列表
        'video/:class_id\w/[:p\d]'  => 'Highlights/index',
        //视频专题页
        'video$'            =>  'Highlights/index',
        //导航编辑页
        'editNav$'           =>  'Nav/editNav',
        //图片播放详情页
        'photo/:id\d$'      =>  'Photos/info',
        //图集详情页
        'photo/getPhoto$'            =>  'Photos/getPhoto',
        'photo$'            =>  'Photos/index',
        //资讯标签列表
        'tag/:key\w/[:p\d]'  => 'Special/newsList',
        //世界杯专题页
        'WorldCupTeam/info/:id\d$'          =>  'WorldCup/TeamAanalysisInfo',
        'WorldCupTeam/forecast$'          =>  'WorldCup/forecast',
        'WorldCupTeam/recommend$'          =>  'WorldCup/recommend',
        'WorldCupTeam/competition$'          =>  'WorldCup/competition',
        'WorldCupTeam/crunchies$'          =>  'WorldCup/crunchies',
        'WorldCupTeam$'          =>  'WorldCup/TeamAanalysisList',
        //美女主播入口
        'liveRoom/:roomId\d'       => array('MLiveRoom/index'),//直播,回播
        'offLine/:userId\d'       => array('MLiveRoom/offLine'),//主播离线
    ),
    
    'HTML_CACHE_ON'         =>  false, // 开启静态缓存
    'HTML_CACHE_TIME'       =>  60,   // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX'      =>  '.html', // 设置静态缓存文件后缀
    'HTML_CACHE_RULES'      =>  array(  // 定义静态缓存规则
    )
);