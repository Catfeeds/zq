<?php
/**

本配置在各版本的Api接口模块通用，修改时请注意兼容

 */
return [
    // 版本號
    'version' => '201805071912',
    
    //短信相关
    'verifyCodeTime' => 10 * 60,    //短信验证码存活时间
    'reSendCodeTime' => 60,         //再次发送短信验证码的时间
    'smsPrefix'      => 'mobile:',  //短信缓存键名前缀

    //登陆注册
    'givePoint' => [
        'registe' => 3000, //注册赠送积分
        'login'   => 100,  //登陆赠送积分
    ],

    //ios  vip会员有效期
    'vip_time' => 366 * 86400,

    //ios vip会员资讯id
    'vipClassId' => 110,

    'loginLifeTime'  => 24 * 3600 * 7,  //app登陆状态存活时间
    'loginTokenTime' => 10 * 60,        //第三方openid存活时间

    //资讯、帖子评论
    'replyTime'      => 10,  //回复时间限制
    'thumbImgSize'   => 200, //帖子缩略图大小
    'newsCacheTime'  => 300, //资讯缓存时间

    //帖子防刷配置
    'postConfig' => [
        'forbidTime' => 60,
        'forbidNum'  => 3,
    ],

    //回帖防刷配置
    'replyConfig' => [
        'forbidTime' => 60,
        'forbidNum'  => 5,
    ],

    //資訊詳情內容關鍵字匹配數量
    'contKetNum'    =>  5,

    //资讯评论防刷配置
    'newsConfig' => [
        'forbidTime' => 60,
        'forbidNum'  => 5,
    ],

    //情报分类资讯ID配置
    'informationIdArr'   =>  [10,108,109],

    //动画直播地址
    'flash_url' => SITE_URL.'dh.qqty.com/animate/animate.html',

    //APP签名校验相关
    'nosignStr' => 'api_qqty_ipa',  //不需校验的约定串
    'nosignUri' => [        //不需校验的模块/方法
        'Index',
        'Home/picDetail',
        'User/uploadFace',
        'PayNotify/notifyAlipay',
        'PayNotify/notifyWxpay',
        'PayNotify/notifyYeepay',
        'Post/loadMore',
        'Post/detail',
        'User/userInvitation',
        'Home/loadMoreComment',
        'User/setUserCustomerMsg',
    ],

    //购买人数配置
    'quiz_number' => 10,

    //足球推荐分割时间
    'fb_gamble_time' => "10:32:00",

    //蓝球推荐分割时间
    'bk_gamble_time' => "12:00:00",

    //篮球推荐使用的赔率公司
    'bk_company_id' => '2',

    //足球赛事更新分割时间
    'fb_gameup_time' => "10:32:00",

    //足球赛事更新分割时间
    'fb_bigdata_time' => "10:35:00",

    //篮球赛事更新分割时间
    'bk_gameup_time' => "15:30:00",

    //蓝球资讯classid
    'gameTypeClass' => [3,4,61],

    //资讯点击量的默认值
    /*
    独家 （*8）+34 => 10,55,54,62
    北单（*5）+29 => 55
    竞彩（*7）+44 => 54
    NBA（*6）+76  => 3,4
    图库（*9）+163 => 18
    英超（*8）+146 => 13
    西甲（*6）+138 => 14
    意甲（*5）+189 => 17
    德甲（*5）+201 => 15
    中超（*7）+354 => 2,18,28
    秘笈（*3）+67  => 62
     */
    'clickConfig' => [
        1 => [
                10 => [8,34], 55 => [8,34], 54 => [8,34], 62 => [8,34], 54 => [7,44], 3 => [6,76], 4 => [6,76],
                13 => [8,146], 14 => [6,138], 17 => [5,189], 15 => [5,201], 2 => [7,354], 18 => [7,354],
                28 => [7,354], 62 => [3,67],
            ],
        2 => [//图库直接加，不需要区分
            9,163
        ]
    ],
    //意见反馈接受信息手机号码,逗号隔开，为空不发送
    'feedbackConfig' => [
        'mobile' => '13560108330,13580437445',
        'sendTime' => 60,
    ],

    //赛事分割时间
    'gameTime' =>[
        'fb' => [
            'sTime' => '10:32:00',
            'eTime' => '8:00:00',
        ],
        'bk' => [
            'sTime' => '15:30:00',
        ],
    ],
	
	// 回报率统计时间
	'earningsFigure' => '12:31:00',
	'predictiveModelStartDate' => '2018-01-01',

    //版本号
    'api' => 'api500',

    //Jwt密钥参数
    'jwtToken' => [
        'unique_id' => "quancaiguest",
        'nbf' => time() - 3600, //定义jwt在什么时间之前不可用
        'iat' => time(), //定义jwt的签发时间
        'exp' => time() + 604800, //定义jwt过期时间在此后不可用
    ],

    //数据库配置2
    'DB_CONFIG2' => 'mysql://cmsdev:gz1710@192.168.1.223:3306/cp#utf8',

    //过滤昵称的配置
    'filterNickname' => ['足彩','彩民','竞彩','博彩','赌'],
    //替换的结果
    'replaceWord' => '*',
    //专家资讯版本声明文字
    'user_statement' => '声明：本文由入驻的作者编辑撰写，除官方账号外，观点仅代表作者本人，不代表本网站立场，如有侵犯您的知识产权的作品和其它问题，请与我们取得联系，我们会及时修改或删除',
    //抓取资讯版本声明文字
    'news_statement' => '版本声明：本文的文字和图片均来源于互联网，文章著作权归原作者所有，不代表全球体育立场
。如有侵犯您的知识产权的作品和其它问题，请与我们取得联系，我们会及时修改或删除',
    //专家说彩目录配置
    'sportteryPath' => [
        73 => 'sporttery',
        10 => 'dujia',
        54 => 'jingcai',
        55 => 'beidan',
        62 => 'djmj',
    ],
];
 ?>