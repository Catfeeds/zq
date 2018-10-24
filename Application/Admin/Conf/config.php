<?php
$env = include('env.php');
return array(
    //囚鸟先生
    'VAR_PAGE'       => 'pageNum',
    'PAGE_LISTROWS'  => 100,  //分页 每页显示多少条
    'PAGE_NUM_SHOWN' => 100, //分页 页标数字多少个
    'SESSION_PREFIX'        =>  'admin_',    //session前缀
    'SESSION_EXPIRE'        =>  86400*7,       //session有效期
    'sitename'              => '全球体育后台管理系统',
    'SESSION_AUTO_START'    =>  true,
    'TMPL_ACTION_ERROR'     =>  'Public:error', // 默认错误跳转对应的模板文件
    'TMPL_ACTION_SUCCESS'   =>  'Public:success', // 默认成功跳转对应的模板文件
    'USER_AUTH_ON'          =>  true,
    'USER_AUTH_TYPE'		=>  2,		// 默认认证类型 1 登录认证 2 实时认证
    'USER_AUTH_KEY'         =>  'authId',	// 用户认证SESSION标记
    'ADMIN_AUTH_KEY'		=>  'administrator',
    'USER_AUTH_MODEL'       =>  'User',	// 默认验证数据表模型
    'AUTH_PWD_ENCODER'      =>  'md5',	// 用户认证密码加密方式
    'USER_AUTH_GATEWAY'     =>  'qqty_admin/Public/login',// 默认认证网关
    'NOT_AUTH_MODULE'       =>  'Public',	// 默认无需认证模块
    'REQUIRE_AUTH_MODULE'   =>  '',		// 默认需要认证模块
    'NOT_AUTH_ACTION'       =>  '',		// 默认无需认证操作
    'REQUIRE_AUTH_ACTION'   =>  '',		// 默认需要认证操作
    'GUEST_AUTH_ON'         =>  false,    // 是否开启游客授权访问
    'GUEST_AUTH_ID'         =>  0,        // 游客的用户ID
    'DB_LIKE_FIELDS'        =>  'title|remark|username',
    'RBAC_ROLE_TABLE'       =>  'qc_role',
    'RBAC_USER_TABLE'       =>  'qc_role_user',
    'RBAC_ACCESS_TABLE'     =>  'qc_access',
    'RBAC_NODE_TABLE'       =>  'qc_node',
    'RBAC_LOGIN_USER'           =>  array(1,17),

    /* 模板相关配置 */
    'TMPL_PARSE_STRING' => array(
        '__PUBLIC__'    => $env['STATIC_SERVER']. '/Public',
        '__STATIC__'    => $env['STATIC_SERVER']. '/Public/static',
        '__DOWNFILE__'  => $env['STATIC_SERVER']. '/Uploads',
        '__DOMAIN__'    => DOMAIN,
        '__ADMIN__'     => '/qqty_admin'
    ),

    'HTTP_CACHE_CONTROL' => 'no-cache,no-store',
    'TMPL_CACHE_ON' => false,//禁止模板编译缓存 
    'HTML_CACHE_ON' => false,//禁止静态缓存 
    //应用类库不再需要使用命名空间
    'APP_USE_NAMESPACE'    =>  false,
    'LOAD_EXT_CONFIG'      => 'channel_config,mission_config',   //加载扩展配置

	'DB_URL'=>'mysql://cmsdev:gz1710@192.168.1.214:3306/live',
);
