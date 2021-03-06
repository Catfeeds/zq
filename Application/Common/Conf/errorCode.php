<?php
/**

本配置在各版本的Api接口模块通用，修改时请注意兼容

 */


return [
    'errorCode' => [
        100 => '', //请求成功
        101 => '参数有误',
        102 => '系统内部错误',
        103 => '当前时间与系统时间不一致，请重新设置当前时间',
        104 => '网络不给力，请耐心等待',

        200 => '', //请求成功
        201 => '数据验证有误',

        401 => '您的IP被禁止',
        403 => '请求失败',
        404 => 'url not found',
        405 => '请更新到最新的版本',

        1001 => '请先登陆',
        1002 => '请输入正确的手机号码',
        1003 => '该号码已经注册',
        1004 => '验证码发送失败',
        1005 => '您的帐号已被禁用',
        1006 => '密码为6-15位数字或者字母',
        1007 => '验证码已过期或无效',
        1008 => '注册失败',
        1009 => '手机号码或密码错误',
        1010 => '请填写昵称',
        1011 => '昵称已被占用',
        1012 => '昵称修改失败',
        1013 => '手机号码不存在',
        1014 => '请重新获取验证码',
        1015 => '重置密码失败',
        1016 => '不能关注自己',
        1017 => '您已关注此用户',
        1018 => '关注失败',
        1019 => '取消关注失败',
        1020 => '请输入至少2位的中文姓名',
        1021 => '请输入正确的身份证号码',
        1022 => '身份证信息已经存在，不可修改',
        1023 => '身份证认证失败',
        1024 => '原密码不正确',
        1025 => '确认密码不正确',
        1026 => '修改密码失败',
        1027 => '个人简介在40个字符以内',
        1028 => '修改个人简介失败',
        1029 => '图片不能为空',
        1030 => '文件格式不正确',
        1031 => '头像上传失败',
        1032 => '兑换总积分未达要求',
        1033 => '积分兑换类型有误',
        1034 => '积分不足',
        1035 => '积分兑换失败',
        1036 => '提款密码必须是6位的数字组成',
        1037 => '请先进行身份认证',
        1038 => '真实姓名不一致',
        1039 => '绑定银行卡失败',
        1040 => '请先绑定银行卡或者支付宝',
        1041 => '提款金额在50，10000之间整数',
        1042 => '提款密码错误',
        1043 => '提款金额不能大于现有金币',
        1044 => '提款失败',
        1045 => '身份证已经存在',
        1046 => '请输入正确的银行卡号',
        1047 => '请输入正确的银行名称',
        1048 => '请输入正确省市名称',
        1049 => '每天只能提款一次哦',
        1050 => '您的密码已修改，请重新登陆',
        1051 => '该账号已在其他终端登录，请重新登录',
        1052 => '昵称长度在2-10位',
        1053 => '登陆类型错误',
        1054 => '第三方登陆配置参数错误',
        1055 => '登陆第三方平台服务器失败',
        1056 => '绑定信息已失效请重新登陆第三方平台',
        1057 => '该帐号已经绑定，请绑定其他帐号',
        1058 => '绑定帐号失败，请重试',
        1059 => '请等待60秒后重新发送',
        1060 => '未绑定手机号码',
        1061 => '含有非法敏感词，请重新输入',
        1062 => '第三方登录失败',
        1063 => '该账号已经绑定过同平台第三方其他账号',
        1064 => '已经绑定过手机号',
        1065 => '昵称含有特殊字符，请重新输入',
        1067 => '请输入正确的支付宝账号',
        1068 => '该账户已经绑定过银行卡',
        1069 => '该账户已经绑定过支付宝',
        1070 => '支付宝账号不能为空',
        1071 => '注册失败，请联系客服',
        1072 => '金币不足',
        1073 => '重置失败',
        1074 => '无数据重置哟',
        1075 => '注册繁忙请稍后再试',
        1076 => '不能订阅自己',
        1077 => '订阅失败',
        1078 => '你已订阅该用户',
        1079 => '已取消订阅',
        1080 => '该银行卡/支付宝账号已经被其他用户绑定',
        1081 => '可提金币少于100，不能提款',
        1082 => '等级不够，不能发布音频',
        1083 => '证件照不能为空',
        1084 => '证件照上传失败',
        1085 => '游客登录失败',
        1086 => '个人简介不能为空',
        1087 => '请输入正确的图文验证码',
        1088 => '该设备注册次数过多',
        1089 => '没有足够金币',

        2001 => '赛事类型错误',
        2002 => '比赛已开始，不能推荐',
        2003 => '您已推荐，请选择其他玩法',
        2004 => '今天推荐已达上限',
        2005 => '100积分推荐场次已达上限',
        2006 => '300积分推荐场次已达上限',
        2007 => '提交失败，请联系管理员',
        2008 => '查看推荐信息金币不足',
        2009 => '查看失败',
        2010 => '您已查看过本推荐',
        2011 => '推荐推荐分析长度在10-400个字',
        2012 => '设置的金币不符合当前的用户等级',
        2013 => '推荐已经结算或取消',
        2014 => '不能购买自己的推荐推荐',
        2015 => '请输入搜索词',
        2016 => '亚盘玩法赔率低于0.60，不能推荐',
        2017 => '竞彩玩法赔率低于1.40，不能推荐',
        2018 => '当前比赛的盘口有变动，是否继续提交？',
        2019 => '暂时未能进行推荐',
        2020 => '请填写正确的直播链接',
        2021 => '球友们谢谢您提供的链接，小编将尽快审核通过',
        2022 => '有新功能更新，请重新操作',

        3001 => '注册环信帐号失败',
        3002 => '保存环信帐号失败',
        3003 => '绑定用户失败',
        3004 => '设置推送失败',
        3005 => '关注赛程失败',
        3006 => '取消关注赛程失败',
        3007 => '该赛事未开启直播或已取消',
        3008 => '在比赛开始前1小时开放聊天室',
        3009 => '客户端异常，请稍后再试!!!',
        3010 => '服务器异常，请稍后再试!!!',
        3011 => '举报失败',
        3012 => '更新apns设备号失败',
        3013 => '你已经点过赞了',
        3014 => '点赞失败',
        3015 => '发言失败',
        3016 => '不是管理员，不能屏蔽',
        3017 => '操作失败',
        3018 => '您的聊天内容已经严重违反了全球体育平台规则，您将被永久屏蔽帐号',
        3019 => '您的聊天内容影响到其他用户，你将被禁言十分钟',
        3020 => '开赛前1小时不能聊天',
        3021 => '聊天室关闭',
        3022 => '请勿刷屏',
        3023 => '无直播数据',

        4001 => '内容不能为空',
        4002 => '提交失败',
        4003 => '评论失败',
        4004 => '图集不存在',
        4005 => '您已点赞过了',
        4006 => '点赞失败',
        4007 => '集锦不存在',
        4008 => '评论不存在',
        4009 => '亲，不可举报自己哦！',
        4010 => '您已经举报过该评论了哦！',
        4011 => '举报失败',
        4012 => '你已经被屏蔽发言',
        4013 => '已达评论数上限，请另外发表评论',
        4015 => '你已经举报过该帖子了哦！',
        4016 => '帖子不存在',
        4017 => '10秒内只能评论一次哦',
        4018 => '重复请求',
        4019 => '操作频繁，请休息一下',

        5001 => '支付参数有误',
        5002 => '支付系统内部错误',
        5003 => '微信支付统一接口调用不成功',
        5004 => '支付异常，请稍后再试',
        5005 => '同一账号每天只能充值*元',
        5006 => '您已开通VIP，无需重复开通',

        6001 => '没有完成任务',
        6002 => '修改积分失败',
        6003 => '签到赠送积分失败',
        6004 => '分享入库失败',
        6005 => '没有达到成就条件',
        6006 => '已领取积分',
        6007 => '收藏失败',
        6008 => '帖子标题长度要求0-20个字符！',
        6009 => '请输入帖子内容！',
        6010 => '图片大小或者格式不符！',
        6011 => '帖子上传失败！',
        6012 => '取消收藏失败',
        6013 => '含有非法敏感词，请重新输入',
        6014 => '你已经被屏蔽发帖！',
        6015 => '你已收藏过',
        6016 => '你已关注过',
        6017 => '邀请码错误',
        6018 => '帖子内容不能超过1万字',
        6019 => '您的输入有误',
        6020 => '邀请码不能是自己的',
        6021 => '您已超过了受邀期限',

        7001 => '购买失败',
        7002 => '不能重复购买',
        7003 => '兑换码无效',
        7004 => '兑换失败',
        7005 => '体验券已经使用',
        7006 => '体验券不符合要求',
        7007 => '已经抢购完毕',
        7008 => '赠送失败',

        8001 => '你已经关注过该产品',
        8002 => '关注产品失败',
        8003 => '你未关注过该产品',
        8004 => '取消关注失败',
        8005 => '购买名额已满',
        8006 => '你已经购买该产品',
        8007 => '你已经订购该产品',
        8008 => '请前往“个人中心”绑定手机号码！',
        8009 => '没有足够的金币',
        8010 => '购买失败',
        8011 => '查询没有该产品',


    ]
];

 ?>