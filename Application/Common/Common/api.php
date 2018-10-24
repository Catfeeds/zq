<?php
/**

本文件在各版本的Api接口模块通用，修改时请注意兼容

 */

use Think\Tool\Tool;

/**
 * 获取用户信息,userToken来自api，其他模块读取时，缓存前缀要改为api_
 * @param $token
 * @param bool $mode
 * @return bool|mixed|string
 */
function getUserToken($token, $mode = false)
{
    if(!$token)
        return '';

    if ($info = S($token))
        S($token,$info,C('loginLifeTime'));

    return $info;
}

/**
 * 获取配置
 * @param  int $gameType 赛程类型 1：足球，2：篮球
 * @return array
 */
function getConfig($gameType = 1)
{
    //只查一次
    $config = getWebConfig(['platformSetting', 'fbConfig', 'betConfig', 'bkConfig', 'common', 'mission', 'recharge', 'iosRecharge', 'ticket', 'bigDataAsk']);

    $c = $config['platformSetting'];

    //积分兑换相关
    $exchange = [
        'pointLimit'  => $c['pointLimit'],
        'point2Coin1' => $c['point2Coin1'],
        'point2Coin2' => $c['point2Coin2'],
        'point2Coin3' => $c['point2Coin3'],
        'point2Coin4' => $c['point2Coin4'],
        'coin1'       => $c['coin1'],
        'coin2'       => $c['coin2'],
        'coin3'       => $c['coin3'],
        'coin4'       => $c['coin4'],
    ];

    //查看交易相关（为了兼容v1.1的版本 保留此字段返回给客户端）
    $trade = [
        'tradeCoin1' => $c['tradeCoin1'],
        'tradeCoin2' => $c['tradeCoin2'],
        'tradeCoin3' => $c['tradeCoin3'],
        'tradeCoin4' => $c['tradeCoin4']
    ];

    //竞猜积分相关
    $fbGamblePoint = [
        'norm_point' => $config['fbConfig']['norm_point'],
        'impt_point' => $config['fbConfig']['impt_point'],
    ];

    $betGamblePoint = [
        'norm_point' => $config['betConfig']['norm_point'],
        'impt_point' => $config['betConfig']['impt_point'],
    ];

    //用户等级设定的金币数 只有让球，没有大小球----对应亚盘
    foreach ($config['fbConfig']['userLv'] as $k => $v)
    {
        $fbLvCoin[] = ['lv'=>$k,'letCoin'=>$v['letCoin']];
    }

    //用户等级设定的金币数 ----对应竞彩
    foreach ($config['betConfig']['userLv'] as $k => $v)
    {
        $betLvCoin[] = ['lv'=>$k,'letCoin'=>$v['letCoin']];
    }
    
    if(in_array(MODULE_NAME, ['Api300', 'Api310', 'Api320', 'Api400', 'Api500', 'Api510', 'Api520', 'Api530'])){
        //获取大咖广场的赛事信息
        $masterGamble = D('common')->getMatchList(1);
    }else{
        //获取高手竞猜导航栏配置信息
        $masterGamble = M('Nav')->where(['status'=>1, 'type' => 2])->order('sort asc')->field('sign, name')->select();
    }

    //大咖广场篮球配置
    if(in_array(MODULE_NAME, ['Api400', 'Api500', 'Api510', 'Api520', 'Api530'])){
        $masterGamblebk = D('common')->getMatchList(2);
    }else{
        $masterGamblebk = [];
    }

    //首页导航
    $navList = D('common')->getNavList();
    
    //底部导航栏配置
    if(in_array(MODULE_NAME, ['Api510', 'Api520', 'Api530'])){
        $bottomList = D('common')->getBottomList();
    }else{
        $bottomList = [];
    }

    //网站其他配置
    $commonConf = $config['common'];
    $invite     = $commonConf['invite'];
    $shopping   = $commonConf['shopping'];//商城开关
    $iosExtract = $commonConf['extract']['ios'];
    $iosPay     = 1; //苹果充值显示 0:隐藏 1:显示
    $iosInPurch = 0; //苹果内购显示 0:隐藏 1:显示
    $guess_switch    = 0;
    $zhishu_switch   = 0;

    //检查审核状态
    if (iosCheck($config['common']))
    {
        $iosCheck   = 1;//ios审核状态
        $iosPay     = 0;//隐藏充值
        $iosExtract = 0;//关闭提款选项
        $invite     = 0;//关闭好友邀请
    }else{
        $iosCheck = 0;
        $guess_switch    = 1;
        $zhishu_switch   = 1;
    }

    //5.0公司版默认是1
//    if(in_array(MODULE_NAME, ['Api500']) && I('pkg') == 'company'){
//        $iosInPurch = $iosPay = 1;
//    }

    //因为ios客户端在2.2版本移动充值传参的一个错误，所以隐藏掉移动充值
    if (I('platform') == 2 && MODULE_NAME == 'Api202')
        $commonConf['payment']['wabp'] = 0;

    //任务系统配置
    $missionConfig = $config['mission'];

    //充值配置
    $rechargeConfig = $config['recharge']['recharge'];

    //判断有无空
    foreach($rechargeConfig as $rk => $rv){
        if($rv['account'] == ''){
            unset($rechargeConfig[$rk]);
        }
    }

    //注册赠送礼包
    $gift1 = M('GiftsConf')->field('id, name, before_img, after_img')
            ->where(['type' => 1, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
            ->order(' id desc ')->limit(1)->find();

    if($gift1){
        $gift1['before_img'] = (string)Tool::imagesReplace($gift1['before_img']);
        $gift1['after_img']  = (string)Tool::imagesReplace($gift1['after_img']);
    }

    //活动赠送礼包
    $gift2 = M('GiftsConf')->field('id, name, before_img, after_img')
            ->where(['type' => 3, 'start_time' => ['lt', NOW_TIME], 'end_time' => ['gt', NOW_TIME], 'status' => 1])
            ->order(' id desc ')->limit(1)->find();

    if($gift2){
        $gift2['before_img'] = (string)Tool::imagesReplace($gift2['before_img']);
        $gift2['after_img']  = (string)Tool::imagesReplace($gift2['after_img']);
    }

    //大数据模块配置
    $bigData = D('Common')->getBigDataConfig();

    //比分直播资料库-专题导航
    $infoNav = [
        ['unionID' => 36, 'time' => '2017-2018', 'name' => ["英超", "英超", "ENG PR"], 'isLeague' => 1],
        ['unionID' => 31, 'time' => '2017-2018', 'name' => ["西甲", "西甲", "SPA D1"], 'isLeague' => 1],
        ['unionID' => 11, 'time' => '2017-2018', 'name' => ["法甲", "法甲", "FRA D1"], 'isLeague' => 1],
        ['unionID' => 8,  'time' => '2017-2018', 'name' =>  ["德甲", "德甲", "GER D1"], 'isLeague' => 1],
        ['unionID' => 34, 'time' => '2017-2018', 'name' => ["意甲", "意甲", "ITA D1"], 'isLeague' => 1],
        ['unionID' => 60, 'time' => '2017-2018', 'name' => ["中超", "中超", "CHA CSL"], 'isLeague' => 1],
    ];

    //客服参数，旧版不读动态配置
    // if(in_array(MODULE_NAME, ['Api204', 'Api310', 'Api320', 'Api400', 'Api500', 'Api510'])){
    //     $service = ['tel' => '', 'address' => 'http://kf.qqty.com/webview/livechat.php'];
    // }else{
        $kefuConf = $commonConf['service'];
        //读取预生产配置地址
        $kefuConf['address'] = C('kefuUrl') ? C('kefuUrl') : $kefuConf['address'];
        $service = (array)$kefuConf;
    //}

    //MQTT新旧版旧版
    if(in_array(MODULE_NAME, ['Api510', 'Api500']) && C('MQTTOLD')){
        $mqttConfig = C('MQTTOLD');
    }else{
        $mqttConfig = C('MQTT');
    }

    $returnData = [
        'exchange'      => $exchange,
        'trade'         => $trade,
        'game_state'    => C('game_state'),
        'gamble_result' => C('gamble_result'),
        'accountType'   => C('accountType'),
        'accountStatus' => C('accountStatus'),
        'pointType'     => C('pointType'),
        'gamble_point'  => $fbGamblePoint,
        'bet_gamble_point' => $betGamblePoint,
        'userLv'        => $fbLvCoin,
        'betUserLv'     => $betLvCoin,
        'dailySignIn'   => $missionConfig['dailySignIn'],//每日签到
        'masterGamble'  => (array) $masterGamble,
        'masterGamblebk'=> $masterGamblebk,
        'home_nav'      => (array) $navList,
        'bottomList'   => $bottomList,
        'payment'       => $commonConf['payment'],
        'sdklogin'      => $commonConf['sdklogin'],
        'app_cache'     => $commonConf['app_cache'],
        'invite'        => $invite,
        'ios_pay'       => $iosPay,
        'ios_inPurch'   => $iosInPurch,
        'ios_check'     => $iosCheck,
        'zhishu_sw'     => $zhishu_switch,
        'guess_sw'      => $guess_switch == 1 ? 'sw1' : 'swqtt',
        'missionStatus' => (string)$missionConfig['status'],
        'ios_extract'   => (string)$iosExtract, //提款显示
        'apk_extract'   => (string)$commonConf['extract']['apk'],
        'ios_extract_money'  => (int)$commonConf['iosExtractMoney'],

        'gamble_desc'        => (string)$config['fbConfig']['gamble_desc'],//竞猜分析
        'gamble_share'       => (string)$config['fbConfig']['gamble_share'],//竞猜分享
        'gamble_desc_tip'    => (string)$config['fbConfig']['gamble_desc_tip'],//竞猜分享描述
        'gamble_share_tip'   => (string)$config['fbConfig']['gamble_share_tip'],//竞猜分享描述

        'bet_gamble_desc'       => (string)$config['betConfig']['gamble_desc'],//竞彩竞猜分析
        'bet_gamble_share'      => (string)$config['betConfig']['gamble_share'],//竞彩竞猜分享
        'bet_gamble_desc_tip'   => (string)$config['betConfig']['gamble_desc_tip'],//竞彩竞猜分享描述
        'bet_gamble_share_tip'  => (string)$config['betConfig']['gamble_share_tip'],//竞彩竞猜分享描述

        'flash_url'      => (string) C('flash_url'),//动画直播地址
        'rechargeConfig' => $rechargeConfig,//充值配置
        'iosPurchase'    => $config['iosRecharge'], //ios内购充值配置
        'rechargeBind'   => $config['recharge']['rechargeBind'],//充值绑定赠送金币
        'gift1'          => $gift1 ? (array)$gift1 : '',//注册赠送
        'gift2'          => $gift2 ? (array)$gift2 : '',//活动赠送
        'service'        => $service,//客服参数
        'shopping'       => $shopping,
        "mqtt_opt"       => $mqttConfig,
        'bk_time'        => $config['bkConfig']['bk_time'] ?: (object)array(),//篮球显示时间段
        'iosAddress'     => $commonConf['iosAddress'] ?: '',
        'iosDocuments'   => $commonConf['iosDocuments'] ?: '',
        'iosHighLight'   => $commonConf['iosHighLight'] ?: '',
        'iosCharacter'   => $commonConf['ios_character'] ?: '',
        'description'    => $commonConf['description'],
        'bigData'        => ['isLogin' => $config['bigDataAsk']['isLogin'], 'bigData'=>$bigData],//问球页面是否需要登录
        '453WinSiteId'   => ['ios'=>'10','android'=>'11'],  //453统计代码 siteId
        'infoNav'        => $infoNav,
        'RankTime'       => ['fb'=>'11点','bk'=>'14:05'],
    ];

    //5.1,IOS:编码传输，并对应转换名字
    if(!in_array(MODULE_NAME, ['Api102','Api103','Api200','Api201','Api202','Api203','Api204','Api300','Api310','Api320','Api400','Api500']) && I('platform') == 2){
        $returnData['ios_fuzhi']   = $returnData['ios_pay'] ? 'a1' : 'qcc';
        $returnData['ios_chajian'] = $returnData['ios_check'] ? 'a2' : 'qtt';
        unset($returnData['ios_pay'], $returnData['ios_check']);
        return base64_encode(json_encode($returnData));
    }else{
        return $returnData;
    }
}

//检查ios审核状态
function iosCheck($commonConf='')
{
    if (I('platform') == 2) //ios平台
    {
        //极速比分 和 JRS篮球比分版 开启审核
        if(I('pkg') == 'topSpeed' || I('pkg') == 'basketball'){
            return true;
        }

        if($commonConf == ''){
            $commonConf = getWebConfig('common');
        }

        //无论是否内审，IP段在内的都屏蔽
        if(iosIpCheck()){
            return true;
        }

        if ($commonConf['iosCheck']) //审核总开关
        {
            $module = $commonConf['ios_check_module']; //审核的接口模块 和 包版本

            $ApiVersion = MODULE_NAME;
            if(I('version') != ''){
                $ApiVersion = I('version');
            }
            
            foreach ($module as $api => $pkg)
            {
                if ($ApiVersion == $api && in_array(I('pkg'),$pkg))
                    return true;
            }
        }
    }
    return false;
}

//检查客户端IP是否在美国屏蔽段内 （在 true | 不在false）
function iosIpCheck($ip=''){
    $client_ip = !empty($ip) ? ip2long($ip) : ip2long(get_client_ip());

    //美国ip文本
    if(!$shield_ip = S('ios_usa_shield_ip')){
        $shield_ip = Tool::getHttpContent('https://img1.qqty.com/Uploads/ip/USAip.txt',true);
        S('ios_usa_shield_ip',$shield_ip,86400);
    }

    $shield_ip = explode("\n", $shield_ip);
    foreach ($shield_ip as $k => $v)
    {
        if(checkUsaIp2($client_ip,$v)){
            return true;
        }
    }

    return false;
}

/**
 * 说明：检测IP是否在IP段内
 * ip段格式 '192.168.1.1/24
 */
function checkUsaIp2($ip, $ip_str) {
    $mark_len = 32;
    if (strpos($ip_str, "/") > 0) {
        list($ip_str, $mark_len) = explode("/", $ip_str);
    }
    $right_len = 32 - $mark_len;
    return $ip >> $right_len == ip2long($ip_str) >> $right_len;
}

/**
 * 说明：检测IP是否在IP段内 
 * ip段格式 '192.168.1.1/24';
 */
function checkUsaIp($ip, $ip_str) {
    $mark_len = 32;
    if (strpos($ip_str, "/") > 0) {
        list($ip_str, $mark_len) = explode("/", $ip_str);
    }
    $right_len = 32 - $mark_len;
    return ip2long($ip) >> $right_len == ip2long($ip_str) >> $right_len;
}

// function ip_parse($ip_str) {
//     $mark_len = 32;
//     if (strpos($ip_str, "/") > 0) {
//         list($ip_str, $mark_len) = explode("/", $ip_str);
//     }
//     $ip = ip2long($ip_str);
//     $mark = 0xFFFFFFFF << (32 - $mark_len) & 0xFFFFFFFF;
//     $ip_start = $ip & $mark;
//     $ip_end = $ip | (~$mark) & 0xFFFFFFFF;
//     return array($ip, $mark, $ip_start, $ip_end);
// }

//app记录添加
function addAppLogs($param){
    $info = getUserToken($param['userToken']);
    $data['device']   = $param['device'];
    $data['device_id']   = $param['deviceID'];
    $data['idfa']     = $param['idfa'];
    $data['location'] = $param['location'] ? :'';
    $data['os']       = $param['os'];
    $data['pkg']      = $param['pkg'];
    $data['version']  = $param['version'];
    $data['ip']       = get_client_ip();
    $data['type']     = $param['type'];
    $data['push_status']     = 1;
    $data['add_time'] = $param['offTime'] ? : $param['t'];
    if($info){
        $data['user_id']  = $info['userid'];
    }

    $rs = M('appLog')->add($data);
    return $rs;
}

?>