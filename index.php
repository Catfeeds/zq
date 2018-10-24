<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>44580
// +----------------------------------------------------------------------
header("Content-type: text/html; charset=utf-8");
// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
//加载环境变量配置
$env = include('env.php');

//Cli模式下运行22
$cur_dir = dirname(__FILE__);
chdir($cur_dir); 
define('MODE_NAME', 'cli');

//设置cookie二级域名共享
if(strpos($_SERVER['SERVER_NAME'], 'bobobong') !== false){
    $domain = 'bobobong.com';
    ini_set('session.cookie_domain', '.'.$domain);
    define('DOMAIN',$domain);
}else{
    $domain = $env['app_domain'];
    define('DOMAIN',$domain);
    ini_set('session.cookie_domain', '.'.$domain);
}


//https判断
define( 'SITE_URL', isset($env['http_type'])?$env['http_type']:'http://' );

// 兼容 windows linux 系统的分割符
define('SEP' , DIRECTORY_SEPARATOR);
define('F_DATA_DIR','/Public/Data/');

// 所在磁盘物理路径
define('SITE_PATH' ,  dirname(__FILE__).SEP );
define('ROOT', SITE_PATH);

// 根目录
define('DataPath', SITE_PATH.'../collect/bfdata'.SEP);

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',$env['app_debug']);

//缓存目录设置 此目录必须可写，建议移动到非WEB目录
define ( 'RUNTIME_PATH', './Runtime/' );

//关闭目录安全文件的生成
define('BUILD_DIR_SECURE', true);

// 定义应用目录
define('APP_PATH','./Application/');

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单