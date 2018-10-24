<?php
return array(

    'APP_USE_NAMESPACE' => false,   //应用类库不再需要使用命名空间
    'LOAD_EXT_CONFIG'   => '',      //加载扩展配置
    'DATA_CACHE_PREFIX' => '',  //缓存前缀
    'SHOW_PAGE_TRACE'   => false ,  //显示调试信息
    'URL_MODEL'         => 2,
    // 开启路由
    'URL_ROUTER_ON'   => true,
    'URL_ROUTE_RULES' => array(

        //gamble关键词替换
        'User/recommend'    => 'User/gambleInfo',           //我的推荐
        'Gbh/userHomePage'  => 'GambleHall/userHomePage',   //Ta的主页
        'User/myFollowGb'   => 'User/myFollowGamble',       //我的关注-动态
        'Gbh/index'         => 'GambleHall/index',          //竞猜大厅
        'Gbh/gbPage'        => 'GambleHall/gamblePage',     //进入竞猜详情页
        'Gb/gb'             => 'Gamble/gamble',             //提交竞猜
        'Gbh/gbCount'       => 'GambleHall/gambleCount',    //竞猜统计
        'Gb/gbView'         => 'Gamble/trade',              //查看竞猜（交易）
        'Gbh/rank'          => 'GambleHall/rank',           //排行榜
        'Gbh/hotMaster'     => 'GambleHall/hotMaster',      //热门高手
        'Gbh/exchange'      => 'GambleHall/exchange',       //兑换中心
        'Gbh/hotPush'       => 'GambleHall/hotPush',        //最新竞猜
        'Gbh/bigShotInfo'   => 'GambleHall/bigShotInfo',    //热门大咖__更多（废用）
        'Gbh/masterGb'      => 'GambleHall/masterGamble',   //大咖广场
        'Gbh/search'        => 'GambleHall/search',         //推荐王搜索
        'Home/masterGb'     => 'Home/masterGamble',         //高手推荐（5.1推荐王首页2）
        'Gbh/chatLike'      => 'GambleHall/chatLike',       //聊球点赞

        //pay关键词替换
        'User/czBind'       => 'User/bindPayCoin',          //绑定充值赠送

        //trade关键词替换
        'User/jyLog'        => 'User/tradeLog',             //查看记录（我的购买）
        'Appdata/Bifajy'    => 'Appdata/BifaTrade',         //必发界面详情

        //odds关键词替换
        'Appdata/asianOs'   => 'Appdata/asianOdds',         //亚赔界面接口
        'Appdata/ballOs'    => 'Appdata/ballOdds',          //大小界面接口
        'Appdata/europeOs'  => 'Appdata/europeOdds',        //欧赔界面接口
        'Appdata/changeOs'  => 'Appdata/chodds',            //即时指数界面即时赔率变化接口
        'Appdata/OsRoll'    => 'Appdata/oddsRoll',          //滚球赛事动态赔率接口
        'Appdata/OsDataDiv' => 'Appdata/oddsDataDiv',       //各赔率数据
        'AppBk/bkMatchOs'   => 'AppBk/bkMatchOdds',         //让欧总初盘即时盘界面数据
        'AppBk/bkchOs'      => 'AppBk/bkchodds',            //指数界面数据
        'AppBk/bkOsHistory' => 'AppBk/bkOddsHistory'        //公司历史赔率

        //bet关键词替换 无
        //payment关键词替换 无
    ),
);