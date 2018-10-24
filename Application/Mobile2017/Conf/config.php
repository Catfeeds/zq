<?php
return array(
    //应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE'    =>    false,
    'URL_MODEL'         =>  2, // 如果你的环境不支持PATHINFO 请设置为3
    'LOAD_EXT_CONFIG'   =>  'url,pay', 
    'DATA_CACHE_PREFIX' =>  '',  // 缓存前缀
    'SESSION_PREFIX'    =>  'home_',   //session前缀
    'SESSION_EXPIRE'    =>  86400*7,
    'HTTP_CACHE_CONTROL' => 'no-cache,no-store',
    // 开启路由
    'URL_ROUTER_ON'   => true, 
    'URL_ROUTE_RULES'=>array(
        //新闻资讯
        'list_n/:class_id\w/[:p\d]'  => 'News/index',
        'info_n/:id\d$'              => 'News/detail',
        'news/:id\d$'                => 'News/detail',
        'info_p/:id\d$'              => 'News/detail_g',
        'info_n/:id\d/:is_show$'     => 'News/detail',
        //个人主页
        'userindex/:user_id\d/[:type\d]' => 'Guess/other_page',
        //广告转跳
        'adver/:adver_id\d$'     => 'Common/adver',
    ),
    /* 新增模板中替换字符串 */
    'TMPL_PARSE_STRING'     => array(
        '__PUBLIC__'    => __ROOT__.'/Public',
        '__VIDEO__'    => __ROOT__.'/Public/'.MODULE_NAME.'/video',
        '__IMAGES__'    => __ROOT__.'/Public/'.MODULE_NAME.'/images',
        '__JS__'        => __ROOT__.'/Public/'.MODULE_NAME.'/js',
        '__CSS__'  => __ROOT__.'/Public/'.MODULE_NAME.'/css',
        '__DOMAIN__' => DOMAIN,
    ),
    'TMPL_ACTION_ERROR'     =>  'Public:error', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'   =>  'Public:success', // 默认成功跳转对应的模板文件
    'HTML_CACHE_ON'     =>    false, // 开启静态缓存
    'HTML_CACHE_TIME'   =>    60,   // 全局静态缓存有效期（秒）
    'HTML_FILE_SUFFIX'  =>    '.html', // 设置静态缓存文件后缀
    'HTML_CACHE_RULES'  =>     array(  // 定义静态缓存规则
    )
);