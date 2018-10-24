//滚动条控制
(function ($) {
    $(window).load(function () {
        $(".hotLiveList").mCustomScrollbar({
            theme: "dark"
            // 这里可以根据背景颜色来通过theme选择自定义样式，
        });
        $(".conEventList").mCustomScrollbar({
            theme: "dark"
            // 这里可以根据背景颜色来通过theme选择自定义样式，
        });
        $(".conChatList").mCustomScrollbar({
            theme: "dark"
            // 这里可以根据背景颜色来通过theme选择自定义样式，
        });

        $(".conChatList").mCustomScrollbar("scrollTo", "bottom", {
            scrollInertia: 0
        });
    });
})(jQuery);
$(function () {

    if (!userInfo) {
        $('#chatTxt').attr('placeholder', '登录一起参与聊球吧...');
    } else {
        $('#chatTxt').attr('placeholder', '请输入聊天内容...');
    }

    $('.indentBtn').click(function (e) {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
            $('.hotLiveBox').stop().animate({'left': '-214px', 'z-indent': '1'}, 500)
        } else {
            $(this).addClass('on');
            $('.hotLiveBox').stop().animate({'left': '0', 'z-indent': '-1'}, 500)
        }
    });

    //高手推荐tab
    $('.navTab ul li').click(function (e) {
        var num = $(this).index();
        $(this).addClass('on').siblings().removeClass('on');
        $('.shotBox ul').eq(num).show().siblings().hide();
    });
    //Marquee通告
    $('.noticeRight').kxbdSuperMarquee({
        isMarquee: true,
        isEqual: false,
        scrollDelay: 30,
        controlBtn: {up: '#goUM', down: '#goDM'},
        direction: 'left'
    });
    //关闭app二维码
    $('.closeApp').click(function (e) {
        $('.appEw').hide();
    });

    var chatTemp = '';
    var chatSend = '';
    var esrAddress = '';
    socketIo(gameId);
    getOdds();
    $("#chatTxt").emoji({
        showTab: true,
        animation: 'fade',
        icons: [{
            name: "通用表情",
            path: "/Public/Home/score/dist/img/tieba/",
            maxNum: 50,
            file: ".png",
            placeholder: "{alias}",
            alias: {
                1: '1f60a',
                2: "1f60b",
                3: "1f60c",
                4: "1f60d",
                5: "1f60e",
                6: "1f60f",
                7: "1f61a",
                8: "1f61b",
                9: "1f61c",
                10: "1f61d",
                11: "1f61e",
                12: "1f61f",
                13: "1f62a",
                14: "1f62b",
                15: "1f62c",
                16: "1f62d",
                17: "1f62e",
                18: "1f62f",
                19: "1f600",
                20: "1f601",
                21: "1f602",
                22: "1f603",
                23: "1f604",
                24: "1f605",
                25: "1f606",
                26: "1f607",
                27: "1f608",
                28: "1f609",
                29: "1f610",
                30: "1f611",
                31: "1f612",
                32: "1f613",
                33: "1f614",
                34: "1f615",
                35: "1f616",
                36: "1f617",
                37: "1f618",
                38: "1f619",
                39: "1f620",
                40: "1f621",
                41: "1f622",
                42: "1f623",
                43: "1f624",
                44: "1f625",
                45: "1f626",
                46: "1f627",
                47: "1f628",
                48: "1f629",
                49: "1f630",
                50: "1f631"
            },
            title: {
                1: "呵呵",
                2: "哈哈",
                3: "吐舌",
                4: "啊",
                5: "酷",
                6: "怒",
                7: "开心",
                8: "汗",
                9: "泪",
                10: "黑线",
                11: "鄙视",
                12: "不高兴",
                13: "真棒",
                14: "钱",
                15: "疑问",
                16: "阴脸",
                17: "吐",
                18: "咦",
                19: "委屈",
                20: "花心",
                21: "呼~",
                22: "笑脸",
                23: "冷",
                24: "太开心",
                25: "滑稽",
                26: "勉强",
                27: "狂汗",
                28: "乖",
                29: "睡觉",
                30: "惊哭",
                31: "生气",
                32: "惊讶",
                33: "喷",
                34: "爱心",
                35: "心碎",
                36: "玫瑰",
                37: "礼物",
                38: "彩虹",
                39: "星星月亮",
                // 40: "太阳",
                // 41: "钱币",
                // 42: "灯泡",
                // 43: "茶杯",
                // 44: "蛋糕",
                // 45: "音乐",
                // 46: "haha",
                // 47: "胜利",
                // 48: "大拇指",
                // 49: "弱",
                // 50: "OK"
            }
        },

        ]
    });


    //聊天室输入框 只粘贴文本
    $('#chatTxt').on('paste', function (e) {
        e.preventDefault();
        var text = null;
        var _this = $(this);
        if (window.clipboardData && clipboardData.setData) {
            // IE
            text = window.clipboardData.getData('text');
        } else {
            text = (e.originalEvent || e).clipboardData.getData('text/plain');
        }
        _this.focus();
        _this.html(_this.html() + text);
    });

    //聊天室输入框 光标重置
    $('#chatTxt').click(function () {
        var content = $(this).html();
        if (content == '') {
            $(this).focus();
            $(this).html("&nbsp;");
        }
    })

})

/**
 * 获取赔率
 */
function getOdds() {
    $.ajax({
        type: 'POST',
        url: '/odds',
        dataType: 'json',
        data: {gameId: gameId},
        success: function (data) {
            $('.selectOdd').eq(0).find('span').eq(0).text(data.fsw_exp_home);
            $('.selectOdd').eq(0).find('span').eq(1).text(data.fsw_exp);
            $('.selectOdd').eq(0).find('span').eq(2).text(data.fsw_exp_away);

            $('.selectOdd').eq(2).find('span').eq(0).text(data.fsw_ball_home);
            $('.selectOdd').eq(2).find('span').eq(1).text(data.fsw_ball);
            $('.selectOdd').eq(2).find('span').eq(2).text(data.fsw_ball_away);

            $('.selectOdd').eq(1).find('span').eq(0).text(data.half_exp_home);
            $('.selectOdd').eq(1).find('span').eq(1).text(data.half_exp);
            $('.selectOdd').eq(1).find('span').eq(2).text(data.half_exp_away);

            $('.selectOdd').eq(3).find('span').eq(0).text(data.half_ball_home);
            $('.selectOdd').eq(3).find('span').eq(1).text(data.half_ball);
            $('.selectOdd').eq(3).find('span').eq(2).text(data.half_ball_away);
        },
    })
}

/**
 * 获取赔率
 */
getLiveText();
function getLiveText() {
    $.ajax({
        type: 'POST',
        url: '/textliving',
        dataType: 'json',
        data: {gameId: gameId},
        success: function (res) {
            if (res.code == 200 && typeof res.data == 'object') {
                var li = '';
                var data = res.data.reverse();
                $.each(data, function (k, event) {
                    var eventArr = event.data.split("-");
                    var spot = event.position == 1 ? 'homeSpot' : 'awaySpot';
                    var eventIcon = '';
                    switch (event.type) {
                        case '1' :
                            eventIcon = 'jinqiu.png';
                            break;
                        case '2' :
                            eventIcon = 'icon-corner-ball.png';
                            break;
                        case '3' :
                            eventIcon = 'icon-yellow-card.png';
                            break;
                        case '4' :
                            eventIcon = 'icon-red-card.png';
                            break;
                    }
                    li += '<li><div class="timePoint">' + event.time + '</div><div class="triangle-border"><img src="/Public/Home/score/images/scoreLive/' + eventIcon + '" width="30" height="30">' + eventArr[0] + '-' + eventArr[1] + '</div><span class="spotPoint ' + spot + '"></span></li>';
                });

                $(".eventList ul").append(li);
            }
        },
    })
}

/**
 * 进入聊天室
 */
function joinRoom() {
    $.ajax({
        type: 'POST',
        url: '/joinRoom',
        dataType: 'json',
        data: {game_id: gameId, game_type: 1},
        success: function (data) {
            var chatLog = data.chatLog;
            isAdmin = data.isAdmin;
            var report = '';
            var nickStyle = '';
            chatTemp = chatLog;
            var li = '';
            $.each(chatLog, function (k, data) {
                var lvSpan = '';
                if (data.lv != undefined && data.lv != '') {
                    lvSpan = '<span class="tips"><span class="m-r-2">LV</span>' + data.lv + '</span>';
                }

                if (isAdmin == 1) {
                    report = '<a class="reportBtn" onclick="report(1,chatTemp[' + k + '])">屏蔽</a><a class="reportBtn" onclick="report(3,chatTemp[' + k + '])">踢出</a>';
                }
                if (userInfo && data.user_id == userInfo.user_id) {
                    nickStyle = 'style="color:#04AF77"'
                } else {
                    nickStyle = 'style="color:#6daade"';
                }
                var chatRowId = "chat-row-" + data.user_id + data.msg_id;
                li = '<li class="start clearfix" id=' + chatRowId + '><span class="live-lb initial pull-left">' + lvSpan + '</span></span><span class="name pull-left" ' + nickStyle + '>' + data.nick_name + '<span class="colon">：</span><span class="shield-report">' + report + '<a class="reportBtn" onclick="report(2,chatTemp[' + k + '])">举报</a></span></span><span class="content-txt">' + jEmoji.unifiedToHTML(data.content) + '</span></li>';
                $(".conChatList ul").append(li);
            });
        }
    })
}

function socketIo(gameId) {
    return;
    var socket = io.connect(esrAddress, {'transports': ['websocket']});
    socket.on('connection');
    socket.emit('init', {type: 1001, data: {gameId: gameId, gameType: 1}});
    socket.emit('init', {type: 1002, data: {gameId: gameId, gameType: 1}});

    socket.emit('scoreOdds', {type: 1004});

    socket.on('init', function (msg) {
        if (msg.status == 1 && msg.type == 1001) {
            socket.emit('live', {type: 1001, data: {}});
        }
    });

    /**动画相关**/
    socket.on('live', function (msg) {
        var type = msg.type;
        switch (type) {
            case 2:
                if (typeof msg.data == 'object' && msg.data.constructor == Array) {
                    var li = '';
                    $.each(msg.data.reverse(), function (k, event) {
                        var eventArr = event.split("-");
                        var spot = event.indexOf("is_home") > -1 ? 'homeSpot' : 'awaySpot';
                        var eventIcon = '';
                        if (event.indexOf("進球") > -1) {
                            eventIcon = 'jinqiu.png';
                        } else if (event.indexOf("黃牌") > -1) {
                            eventIcon = 'icon-yellow-card.png';
                        } else if (event.indexOf("角球") > -1) {
                            eventIcon = 'icon-corner-ball.png';
                        } else if (event.indexOf("红牌") > -1) {
                            eventIcon = 'icon-red-card.png';
                        }
                        li += '<li><div class="timePoint">' + eventArr[0] + '</div><div class="triangle-border"><img src="/Public/Home/score/images/scoreLive/' + eventIcon + '" width="30" height="30">' + eventArr[1] + '-' + eventArr[2] + '</div><span class="spotPoint ' + spot + '"></span></li>';
                    });

                    $(".eventList ul").append(li);
                } else {
                    var event = msg.data.event;
                    var eventArr = event.split("-");
                    var spot = event.indexOf("is_home") > -1 ? 'homeSpot' : 'awaySpot';
                    var eventIcon = '';
                    if (event.indexOf("進球") > -1) {
                        eventIcon = 'jinqiu.png';
                    } else if (event.indexOf("黃牌") > -1) {
                        eventIcon = 'icon-yellow-card.png';
                    } else if (event.indexOf("角球") > -1) {
                        eventIcon = 'icon-corner-ball.png';
                    } else if (event.indexOf("红牌") > -1) {
                        eventIcon = 'icon-red-card.png';
                    }
                    var li = '<li><div class="timePoint">' + eventArr[0] + '</div><div class="triangle-border"><img src="/Public/Home/score/images/scoreLive/' + eventIcon + '" width="30" height="30">' + eventArr[1] + '-' + eventArr[2] + '</div><span class="spotPoint ' + spot + '"></span></li>';

                    $(".eventList ul").prepend(li);
                }

                break;

        }
    });

}

$("#chatTxt").keydown(function(event) {
    if (event.keyCode == 13) {
        say();
    }
    return;
});

$(".btnPost").click(function () {
    say();
});

/**
 * 发言
 */

function say(){
    if (chatOpen != '1') {
        showMsg('不再聊天时间，不能发言', 0, 'error');
        return;
    }

    if (userStatus == '-1') {
        showMsg('您的账号被管理员屏蔽了', 0, 'error');
        return;
    } else if (userStatus == '-2') {
        _alert('系统提示', '您的聊天内容已经严重违反了全球体育平台规则，您将被永久屏蔽帐号');
        return;
    } else if (userStatus == '-3') {
        _alert('系统提示', '您的聊天内容影响到其他用户，你将被禁言十分钟');
        return;
    }

    if (!userInfo) {
        $('.myLogin').modal('show');
        return;
    }

    //表情替换
    var chatHtml = $("#chatTxt").html();
    var emojiReg = /<img class="emoji_icon" src="(.*?)" title="(.*?)">/g;
    var chatHtml2 = chatHtml.replace(emojiReg, function (arg1, arg2, arg3) {
        return emojiData[arg3];
    });

    $("#chatTxt2").html(chatHtml2);
    var content = $("#chatTxt2").text().trim();

    if (content.length < 1) {
        showMsg('请输入聊天内容', 0, 'error');
    } else {
        var msg_id = uuid();
        var payload = {
            action: 'say',
            dataType: 'text',
            data: {
                user_id: userInfo.user_id,
                nick_name: userInfo.nick_name,
                head: userInfo.head,
                lv: userInfo.lv,
                lv_bet: userInfo.lv_bet,
                lv_bk: userInfo.lv_bk,
                chat_time: Date.parse(new Date()) / 1000,
                content: removeHTMLTag(content),
                msg_id: msg_id
            },
            status: 1
        };
        var jsonStr = JSON.stringify(payload);console.log(jsonStr);
        client.publish('qqty/1_' + gameId + '/chat', jsonStr,1);
    }
    $('#chatTxt').empty();
    $('#chatTxt').focus();
}
/**
 * 清空聊天记录
 */
function clearChatLog() {
    gDialog.fConfirm('确认框', '确定要清屏吗', function (rs) {
        if (rs) $(".chatList ul").empty();
    });
}
function report(type, data) {
    if (data) {
        $.ajax({
            type: 'POST',
            url: '/forbid',
            dataType: 'json',
            data: {
                game_id: gameId,
                game_type: 1,
                type: type,
                content: data.content,
                user_id: data.user_id,
                msg_id: data.msg_id
            },
            success: function (data) {
                if (data.code == 1011) {
                    $('.myLogin').modal('show');
                } else if (data.code == 200) {
                    _alert('提示', '操作成功');
                } else {
                    _alert('提示', data.msg);
                }
            }
        })
    }
}

/**
 * 让球盘口转换
 * @param v
 * @returns {*}
 */
function fswExpReplace(v) {
    var vv = '';
    var str = '';
    var e = v.toString();
    if (e.indexOf('-') > -1) {
        vv = v.split('-')[1];
        str = '受';
    } else {
        vv = e;
    }

    var exp = {
        '0': '平手',
        '0/0.5': '平/半',
        '0.5': '半球',
        '0.5/1': '半/一球',
        '1': '一球',
        '1/1.5': '一/球半',
        '1.5': '球半',
        '1.5/2': '球半/两球',
        '2': '两球',
        '2/2.5': '两/两球半',
        '2.5': '两球半',
        '2.5/3': '两球半/三球',
        '3': '三球',
        '3/3.5': '三/三球半',
        '3.5': '三球半',
        '3.5/4': '三球半/四球',
        '4': '四球',
        '4/4.5': '四/四球半',
        '4.5': '四球半',
        '4.5/5': '四球半/五球',
        '5': '五球',
        '5/5.5': '五/五球半',
        '5.5': '五球半',
        '5.5/6': '五球半/六球',
        '6': '六球',
        '6/6.5': '六/六球半',
        '6.5': '六球半',
        '6.5/7': '六球半/七球',
        '7': '七球',
        '7/7.5': '七/七球半',
        '7.5': '七球半',
        '7.5/8': '七球半/八球',
        '8': '八球',
        '8/8.5': '八/八球半',
        '8.5': '八球半',
        '8.5/9': '八球半/九球',
        '9': '九球',
        '9/9.5': '九/九球半',
        '9.5': '九球半',
        '9.5/10': '九球半/十球',
        '10': '十球',
        '10/10.5': '十/十球半',
        '10.5': '十球半',
        '10.5/11': '十球半/十一球',
        '11': '十一球',
        '11/11.5': '十一/十一球半',
        '11.5': '十一球半',
        '11.5/12': '十一球半/十二球',
        '12': '十二球',
        '12/12.5': '十二/十二球半',
        '12.5': '十二球半',
        '12.5/13': '十二球半/十三球',
        '13': '十三球',
        '13/13.5': '十三/十三球半',
        '13.5': '十三球半',
        '13.5/14': '十三球半/十四球',
        '14': '十四球',
        '14/14.5': '十四/十四球半',
        '14.5': '十四球半',
        '14.5/15': '十四球半/十五球',
        '15': '十五球',
        '15/15.5': '十五/十五球半',
        '15.5': '十五球半',
        '15.5/16': '十五球半/十六球',
        '16': '十六球',
        '16/16.5': '十六/十六球半',
        '16.5': '十六球半',
        '16.5/17': '十六球半/十七球',
        '17': '十七球',
        '17/17.5': '十七/十七球半',
        '17.5': '十七球半',
        '17.5/18': '十七球半/十八球',
        '18': '十八球',
        '18/18.5': '十八/十八球半',
        '18.5': '十八球半',
        '18.5/19': '十八球半/十九球',
        '19': '十九球',
        '19/19.5': '十九/十九球半',
        '19.5': '十九球半',
        '19.5/20': '十九球半/二十球',
        '20': '二十球',
        '20/20.5': '二十/二十球半',
        '20.5': '二十球半',
        '20.5/21': '二十球半/二十一球',
    };

    return str + exp[vv];
}

function uuid() {
    var s = [];
    var hexDigits = "0123456789abcdef";
    for (var i = 0; i < 36; i++) {
        s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
    }
    s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
    s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
    s[8] = s[13] = s[18] = s[23] = "-";

    var uuid = s.join("");
    return uuid;
}

var emojiMap = {
    "1f004": "\xf0\x9f\x80\x84",
    "1f0cf": "\xf0\x9f\x83\x8f",
    "1f170": "\xf0\x9f\x85\xb0",
    "1f171": "\xf0\x9f\x85\xb1",
    "1f17e": "\xf0\x9f\x85\xbe",
    "1f17f": "\xf0\x9f\x85\xbf",
    "1f18e": "\xf0\x9f\x86\x8e",
    "1f191": "\xf0\x9f\x86\x91",
    "1f192": "\xf0\x9f\x86\x92",
    "1f193": "\xf0\x9f\x86\x93",
    "1f194": "\xf0\x9f\x86\x94",
    "1f195": "\xf0\x9f\x86\x95",
    "1f196": "\xf0\x9f\x86\x96",
    "1f197": "\xf0\x9f\x86\x97",
    "1f198": "\xf0\x9f\x86\x98",
    "1f199": "\xf0\x9f\x86\x99",
    "1f19a": "\xf0\x9f\x86\x9a",
    "1f201": "\xf0\x9f\x88\x81",
    "1f202": "\xf0\x9f\x88\x82",
    "1f21a": "\xf0\x9f\x88\x9a",
    "1f22f": "\xf0\x9f\x88\xaf",
    "1f232": "\xf0\x9f\x88\xb2",
    "1f233": "\xf0\x9f\x88\xb3",
    "1f234": "\xf0\x9f\x88\xb4",
    "1f235": "\xf0\x9f\x88\xb5",
    "1f236": "\xf0\x9f\x88\xb6",
    "1f237": "\xf0\x9f\x88\xb7",
    "1f238": "\xf0\x9f\x88\xb8",
    "1f239": "\xf0\x9f\x88\xb9",
    "1f23a": "\xf0\x9f\x88\xba",
    "1f250": "\xf0\x9f\x89\x90",
    "1f251": "\xf0\x9f\x89\x91",
    "1f300": "\xf0\x9f\x8c\x80",
    "1f301": "\xf0\x9f\x8c\x81",
    "1f302": "\xf0\x9f\x8c\x82",
    "1f303": "\xf0\x9f\x8c\x83",
    "1f304": "\xf0\x9f\x8c\x84",
    "1f305": "\xf0\x9f\x8c\x85",
    "1f306": "\xf0\x9f\x8c\x86",
    "1f307": "\xf0\x9f\x8c\x87",
    "1f308": "\xf0\x9f\x8c\x88",
    "1f309": "\xf0\x9f\x8c\x89",
    "1f30a": "\xf0\x9f\x8c\x8a",
    "1f30b": "\xf0\x9f\x8c\x8b",
    "1f30c": "\xf0\x9f\x8c\x8c",
    "1f30d": "\xf0\x9f\x8c\x8d",
    "1f30e": "\xf0\x9f\x8c\x8e",
    "1f30f": "\xf0\x9f\x8c\x8f",
    "1f310": "\xf0\x9f\x8c\x90",
    "1f311": "\xf0\x9f\x8c\x91",
    "1f312": "\xf0\x9f\x8c\x92",
    "1f313": "\xf0\x9f\x8c\x93",
    "1f314": "\xf0\x9f\x8c\x94",
    "1f315": "\xf0\x9f\x8c\x95",
    "1f316": "\xf0\x9f\x8c\x96",
    "1f317": "\xf0\x9f\x8c\x97",
    "1f318": "\xf0\x9f\x8c\x98",
    "1f319": "\xf0\x9f\x8c\x99",
    "1f31a": "\xf0\x9f\x8c\x9a",
    "1f31b": "\xf0\x9f\x8c\x9b",
    "1f31c": "\xf0\x9f\x8c\x9c",
    "1f31d": "\xf0\x9f\x8c\x9d",
    "1f31e": "\xf0\x9f\x8c\x9e",
    "1f31f": "\xf0\x9f\x8c\x9f",
    "1f320": "\xf0\x9f\x8c\xa0",
    "1f321": "\xf0\x9f\x8c\xa1",
    "1f324": "\xf0\x9f\x8c\xa4",
    "1f325": "\xf0\x9f\x8c\xa5",
    "1f326": "\xf0\x9f\x8c\xa6",
    "1f327": "\xf0\x9f\x8c\xa7",
    "1f328": "\xf0\x9f\x8c\xa8",
    "1f329": "\xf0\x9f\x8c\xa9",
    "1f32a": "\xf0\x9f\x8c\xaa",
    "1f32b": "\xf0\x9f\x8c\xab",
    "1f32c": "\xf0\x9f\x8c\xac",
    "1f32d": "\xf0\x9f\x8c\xad",
    "1f32e": "\xf0\x9f\x8c\xae",
    "1f32f": "\xf0\x9f\x8c\xaf",
    "1f330": "\xf0\x9f\x8c\xb0",
    "1f331": "\xf0\x9f\x8c\xb1",
    "1f332": "\xf0\x9f\x8c\xb2",
    "1f333": "\xf0\x9f\x8c\xb3",
    "1f334": "\xf0\x9f\x8c\xb4",
    "1f335": "\xf0\x9f\x8c\xb5",
    "1f336": "\xf0\x9f\x8c\xb6",
    "1f337": "\xf0\x9f\x8c\xb7",
    "1f338": "\xf0\x9f\x8c\xb8",
    "1f339": "\xf0\x9f\x8c\xb9",
    "1f33a": "\xf0\x9f\x8c\xba",
    "1f33b": "\xf0\x9f\x8c\xbb",
    "1f33c": "\xf0\x9f\x8c\xbc",
    "1f33d": "\xf0\x9f\x8c\xbd",
    "1f33e": "\xf0\x9f\x8c\xbe",
    "1f33f": "\xf0\x9f\x8c\xbf",
    "1f340": "\xf0\x9f\x8d\x80",
    "1f341": "\xf0\x9f\x8d\x81",
    "1f342": "\xf0\x9f\x8d\x82",
    "1f343": "\xf0\x9f\x8d\x83",
    "1f344": "\xf0\x9f\x8d\x84",
    "1f345": "\xf0\x9f\x8d\x85",
    "1f346": "\xf0\x9f\x8d\x86",
    "1f347": "\xf0\x9f\x8d\x87",
    "1f348": "\xf0\x9f\x8d\x88",
    "1f349": "\xf0\x9f\x8d\x89",
    "1f34a": "\xf0\x9f\x8d\x8a",
    "1f34b": "\xf0\x9f\x8d\x8b",
    "1f34c": "\xf0\x9f\x8d\x8c",
    "1f34d": "\xf0\x9f\x8d\x8d",
    "1f34e": "\xf0\x9f\x8d\x8e",
    "1f34f": "\xf0\x9f\x8d\x8f",
    "1f350": "\xf0\x9f\x8d\x90",
    "1f351": "\xf0\x9f\x8d\x91",
    "1f352": "\xf0\x9f\x8d\x92",
    "1f353": "\xf0\x9f\x8d\x93",
    "1f354": "\xf0\x9f\x8d\x94",
    "1f355": "\xf0\x9f\x8d\x95",
    "1f356": "\xf0\x9f\x8d\x96",
    "1f357": "\xf0\x9f\x8d\x97",
    "1f358": "\xf0\x9f\x8d\x98",
    "1f359": "\xf0\x9f\x8d\x99",
    "1f35a": "\xf0\x9f\x8d\x9a",
    "1f35b": "\xf0\x9f\x8d\x9b",
    "1f35c": "\xf0\x9f\x8d\x9c",
    "1f35d": "\xf0\x9f\x8d\x9d",
    "1f35e": "\xf0\x9f\x8d\x9e",
    "1f35f": "\xf0\x9f\x8d\x9f",
    "1f360": "\xf0\x9f\x8d\xa0",
    "1f361": "\xf0\x9f\x8d\xa1",
    "1f362": "\xf0\x9f\x8d\xa2",
    "1f363": "\xf0\x9f\x8d\xa3",
    "1f364": "\xf0\x9f\x8d\xa4",
    "1f365": "\xf0\x9f\x8d\xa5",
    "1f366": "\xf0\x9f\x8d\xa6",
    "1f367": "\xf0\x9f\x8d\xa7",
    "1f368": "\xf0\x9f\x8d\xa8",
    "1f369": "\xf0\x9f\x8d\xa9",
    "1f36a": "\xf0\x9f\x8d\xaa",
    "1f36b": "\xf0\x9f\x8d\xab",
    "1f36c": "\xf0\x9f\x8d\xac",
    "1f36d": "\xf0\x9f\x8d\xad",
    "1f36e": "\xf0\x9f\x8d\xae",
    "1f36f": "\xf0\x9f\x8d\xaf",
    "1f370": "\xf0\x9f\x8d\xb0",
    "1f371": "\xf0\x9f\x8d\xb1",
    "1f372": "\xf0\x9f\x8d\xb2",
    "1f373": "\xf0\x9f\x8d\xb3",
    "1f374": "\xf0\x9f\x8d\xb4",
    "1f375": "\xf0\x9f\x8d\xb5",
    "1f376": "\xf0\x9f\x8d\xb6",
    "1f377": "\xf0\x9f\x8d\xb7",
    "1f378": "\xf0\x9f\x8d\xb8",
    "1f379": "\xf0\x9f\x8d\xb9",
    "1f37a": "\xf0\x9f\x8d\xba",
    "1f37b": "\xf0\x9f\x8d\xbb",
    "1f37c": "\xf0\x9f\x8d\xbc",
    "1f37d": "\xf0\x9f\x8d\xbd",
    "1f37e": "\xf0\x9f\x8d\xbe",
    "1f37f": "\xf0\x9f\x8d\xbf",
    "1f380": "\xf0\x9f\x8e\x80",
    "1f381": "\xf0\x9f\x8e\x81",
    "1f382": "\xf0\x9f\x8e\x82",
    "1f383": "\xf0\x9f\x8e\x83",
    "1f384": "\xf0\x9f\x8e\x84",
    "1f385": "\xf0\x9f\x8e\x85",
    "1f386": "\xf0\x9f\x8e\x86",
    "1f387": "\xf0\x9f\x8e\x87",
    "1f388": "\xf0\x9f\x8e\x88",
    "1f389": "\xf0\x9f\x8e\x89",
    "1f38a": "\xf0\x9f\x8e\x8a",
    "1f38b": "\xf0\x9f\x8e\x8b",
    "1f38c": "\xf0\x9f\x8e\x8c",
    "1f38d": "\xf0\x9f\x8e\x8d",
    "1f38e": "\xf0\x9f\x8e\x8e",
    "1f38f": "\xf0\x9f\x8e\x8f",
    "1f390": "\xf0\x9f\x8e\x90",
    "1f391": "\xf0\x9f\x8e\x91",
    "1f392": "\xf0\x9f\x8e\x92",
    "1f393": "\xf0\x9f\x8e\x93",
    "1f396": "\xf0\x9f\x8e\x96",
    "1f397": "\xf0\x9f\x8e\x97",
    "1f399": "\xf0\x9f\x8e\x99",
    "1f39a": "\xf0\x9f\x8e\x9a",
    "1f39b": "\xf0\x9f\x8e\x9b",
    "1f39e": "\xf0\x9f\x8e\x9e",
    "1f39f": "\xf0\x9f\x8e\x9f",
    "1f3a0": "\xf0\x9f\x8e\xa0",
    "1f3a1": "\xf0\x9f\x8e\xa1",
    "1f3a2": "\xf0\x9f\x8e\xa2",
    "1f3a3": "\xf0\x9f\x8e\xa3",
    "1f3a4": "\xf0\x9f\x8e\xa4",
    "1f3a5": "\xf0\x9f\x8e\xa5",
    "1f3a6": "\xf0\x9f\x8e\xa6",
    "1f3a7": "\xf0\x9f\x8e\xa7",
    "1f3a8": "\xf0\x9f\x8e\xa8",
    "1f3a9": "\xf0\x9f\x8e\xa9",
    "1f3aa": "\xf0\x9f\x8e\xaa",
    "1f3ab": "\xf0\x9f\x8e\xab",
    "1f3ac": "\xf0\x9f\x8e\xac",
    "1f3ad": "\xf0\x9f\x8e\xad",
    "1f3ae": "\xf0\x9f\x8e\xae",
    "1f3af": "\xf0\x9f\x8e\xaf",
    "1f3b0": "\xf0\x9f\x8e\xb0",
    "1f3b1": "\xf0\x9f\x8e\xb1",
    "1f3b2": "\xf0\x9f\x8e\xb2",
    "1f3b3": "\xf0\x9f\x8e\xb3",
    "1f3b4": "\xf0\x9f\x8e\xb4",
    "1f3b5": "\xf0\x9f\x8e\xb5",
    "1f3b6": "\xf0\x9f\x8e\xb6",
    "1f3b7": "\xf0\x9f\x8e\xb7",
    "1f3b8": "\xf0\x9f\x8e\xb8",
    "1f3b9": "\xf0\x9f\x8e\xb9",
    "1f3ba": "\xf0\x9f\x8e\xba",
    "1f3bb": "\xf0\x9f\x8e\xbb",
    "1f3bc": "\xf0\x9f\x8e\xbc",
    "1f3bd": "\xf0\x9f\x8e\xbd",
    "1f3be": "\xf0\x9f\x8e\xbe",
    "1f3bf": "\xf0\x9f\x8e\xbf",
    "1f3c0": "\xf0\x9f\x8f\x80",
    "1f3c1": "\xf0\x9f\x8f\x81",
    "1f3c2": "\xf0\x9f\x8f\x82",
    "1f3c3": "\xf0\x9f\x8f\x83",
    "1f3c4": "\xf0\x9f\x8f\x84",
    "1f3c5": "\xf0\x9f\x8f\x85",
    "1f3c6": "\xf0\x9f\x8f\x86",
    "1f3c7": "\xf0\x9f\x8f\x87",
    "1f3c8": "\xf0\x9f\x8f\x88",
    "1f3c9": "\xf0\x9f\x8f\x89",
    "1f3ca": "\xf0\x9f\x8f\x8a",
    "1f3cb": "\xf0\x9f\x8f\x8b",
    "1f3cc": "\xf0\x9f\x8f\x8c",
    "1f3cd": "\xf0\x9f\x8f\x8d",
    "1f3ce": "\xf0\x9f\x8f\x8e",
    "1f3cf": "\xf0\x9f\x8f\x8f",
    "1f3d0": "\xf0\x9f\x8f\x90",
    "1f3d1": "\xf0\x9f\x8f\x91",
    "1f3d2": "\xf0\x9f\x8f\x92",
    "1f3d3": "\xf0\x9f\x8f\x93",
    "1f3d4": "\xf0\x9f\x8f\x94",
    "1f3d5": "\xf0\x9f\x8f\x95",
    "1f3d6": "\xf0\x9f\x8f\x96",
    "1f3d7": "\xf0\x9f\x8f\x97",
    "1f3d8": "\xf0\x9f\x8f\x98",
    "1f3d9": "\xf0\x9f\x8f\x99",
    "1f3da": "\xf0\x9f\x8f\x9a",
    "1f3db": "\xf0\x9f\x8f\x9b",
    "1f3dc": "\xf0\x9f\x8f\x9c",
    "1f3dd": "\xf0\x9f\x8f\x9d",
    "1f3de": "\xf0\x9f\x8f\x9e",
    "1f3df": "\xf0\x9f\x8f\x9f",
    "1f3e0": "\xf0\x9f\x8f\xa0",
    "1f3e1": "\xf0\x9f\x8f\xa1",
    "1f3e2": "\xf0\x9f\x8f\xa2",
    "1f3e3": "\xf0\x9f\x8f\xa3",
    "1f3e4": "\xf0\x9f\x8f\xa4",
    "1f3e5": "\xf0\x9f\x8f\xa5",
    "1f3e6": "\xf0\x9f\x8f\xa6",
    "1f3e7": "\xf0\x9f\x8f\xa7",
    "1f3e8": "\xf0\x9f\x8f\xa8",
    "1f3e9": "\xf0\x9f\x8f\xa9",
    "1f3ea": "\xf0\x9f\x8f\xaa",
    "1f3eb": "\xf0\x9f\x8f\xab",
    "1f3ec": "\xf0\x9f\x8f\xac",
    "1f3ed": "\xf0\x9f\x8f\xad",
    "1f3ee": "\xf0\x9f\x8f\xae",
    "1f3ef": "\xf0\x9f\x8f\xaf",
    "1f3f0": "\xf0\x9f\x8f\xb0",
    "1f3f3": "\xf0\x9f\x8f\xb3",
    "1f3f4": "\xf0\x9f\x8f\xb4",
    "1f3f5": "\xf0\x9f\x8f\xb5",
    "1f3f7": "\xf0\x9f\x8f\xb7",
    "1f3f8": "\xf0\x9f\x8f\xb8",
    "1f3f9": "\xf0\x9f\x8f\xb9",
    "1f3fa": "\xf0\x9f\x8f\xba",
    "1f3fb": "\xf0\x9f\x8f\xbb",
    "1f3fc": "\xf0\x9f\x8f\xbc",
    "1f3fd": "\xf0\x9f\x8f\xbd",
    "1f3fe": "\xf0\x9f\x8f\xbe",
    "1f3ff": "\xf0\x9f\x8f\xbf",
    "1f400": "\xf0\x9f\x90\x80",
    "1f401": "\xf0\x9f\x90\x81",
    "1f402": "\xf0\x9f\x90\x82",
    "1f403": "\xf0\x9f\x90\x83",
    "1f404": "\xf0\x9f\x90\x84",
    "1f405": "\xf0\x9f\x90\x85",
    "1f406": "\xf0\x9f\x90\x86",
    "1f407": "\xf0\x9f\x90\x87",
    "1f408": "\xf0\x9f\x90\x88",
    "1f409": "\xf0\x9f\x90\x89",
    "1f40a": "\xf0\x9f\x90\x8a",
    "1f40b": "\xf0\x9f\x90\x8b",
    "1f40c": "\xf0\x9f\x90\x8c",
    "1f40d": "\xf0\x9f\x90\x8d",
    "1f40e": "\xf0\x9f\x90\x8e",
    "1f40f": "\xf0\x9f\x90\x8f",
    "1f410": "\xf0\x9f\x90\x90",
    "1f411": "\xf0\x9f\x90\x91",
    "1f412": "\xf0\x9f\x90\x92",
    "1f413": "\xf0\x9f\x90\x93",
    "1f414": "\xf0\x9f\x90\x94",
    "1f415": "\xf0\x9f\x90\x95",
    "1f416": "\xf0\x9f\x90\x96",
    "1f417": "\xf0\x9f\x90\x97",
    "1f418": "\xf0\x9f\x90\x98",
    "1f419": "\xf0\x9f\x90\x99",
    "1f41a": "\xf0\x9f\x90\x9a",
    "1f41b": "\xf0\x9f\x90\x9b",
    "1f41c": "\xf0\x9f\x90\x9c",
    "1f41d": "\xf0\x9f\x90\x9d",
    "1f41e": "\xf0\x9f\x90\x9e",
    "1f41f": "\xf0\x9f\x90\x9f",
    "1f420": "\xf0\x9f\x90\xa0",
    "1f421": "\xf0\x9f\x90\xa1",
    "1f422": "\xf0\x9f\x90\xa2",
    "1f423": "\xf0\x9f\x90\xa3",
    "1f424": "\xf0\x9f\x90\xa4",
    "1f425": "\xf0\x9f\x90\xa5",
    "1f426": "\xf0\x9f\x90\xa6",
    "1f427": "\xf0\x9f\x90\xa7",
    "1f428": "\xf0\x9f\x90\xa8",
    "1f429": "\xf0\x9f\x90\xa9",
    "1f42a": "\xf0\x9f\x90\xaa",
    "1f42b": "\xf0\x9f\x90\xab",
    "1f42c": "\xf0\x9f\x90\xac",
    "1f42d": "\xf0\x9f\x90\xad",
    "1f42e": "\xf0\x9f\x90\xae",
    "1f42f": "\xf0\x9f\x90\xaf",
    "1f430": "\xf0\x9f\x90\xb0",
    "1f431": "\xf0\x9f\x90\xb1",
    "1f432": "\xf0\x9f\x90\xb2",
    "1f433": "\xf0\x9f\x90\xb3",
    "1f434": "\xf0\x9f\x90\xb4",
    "1f435": "\xf0\x9f\x90\xb5",
    "1f436": "\xf0\x9f\x90\xb6",
    "1f437": "\xf0\x9f\x90\xb7",
    "1f438": "\xf0\x9f\x90\xb8",
    "1f439": "\xf0\x9f\x90\xb9",
    "1f43a": "\xf0\x9f\x90\xba",
    "1f43b": "\xf0\x9f\x90\xbb",
    "1f43c": "\xf0\x9f\x90\xbc",
    "1f43d": "\xf0\x9f\x90\xbd",
    "1f43e": "\xf0\x9f\x90\xbe",
    "1f43f": "\xf0\x9f\x90\xbf",
    "1f440": "\xf0\x9f\x91\x80",
    "1f441": "\xf0\x9f\x91\x81",
    "1f442": "\xf0\x9f\x91\x82",
    "1f443": "\xf0\x9f\x91\x83",
    "1f444": "\xf0\x9f\x91\x84",
    "1f445": "\xf0\x9f\x91\x85",
    "1f446": "\xf0\x9f\x91\x86",
    "1f447": "\xf0\x9f\x91\x87",
    "1f448": "\xf0\x9f\x91\x88",
    "1f449": "\xf0\x9f\x91\x89",
    "1f44a": "\xf0\x9f\x91\x8a",
    "1f44b": "\xf0\x9f\x91\x8b",
    "1f44c": "\xf0\x9f\x91\x8c",
    "1f44d": "\xf0\x9f\x91\x8d",
    "1f44e": "\xf0\x9f\x91\x8e",
    "1f44f": "\xf0\x9f\x91\x8f",
    "1f450": "\xf0\x9f\x91\x90",
    "1f451": "\xf0\x9f\x91\x91",
    "1f452": "\xf0\x9f\x91\x92",
    "1f453": "\xf0\x9f\x91\x93",
    "1f454": "\xf0\x9f\x91\x94",
    "1f455": "\xf0\x9f\x91\x95",
    "1f456": "\xf0\x9f\x91\x96",
    "1f457": "\xf0\x9f\x91\x97",
    "1f458": "\xf0\x9f\x91\x98",
    "1f459": "\xf0\x9f\x91\x99",
    "1f45a": "\xf0\x9f\x91\x9a",
    "1f45b": "\xf0\x9f\x91\x9b",
    "1f45c": "\xf0\x9f\x91\x9c",
    "1f45d": "\xf0\x9f\x91\x9d",
    "1f45e": "\xf0\x9f\x91\x9e",
    "1f45f": "\xf0\x9f\x91\x9f",
    "1f460": "\xf0\x9f\x91\xa0",
    "1f461": "\xf0\x9f\x91\xa1",
    "1f462": "\xf0\x9f\x91\xa2",
    "1f463": "\xf0\x9f\x91\xa3",
    "1f464": "\xf0\x9f\x91\xa4",
    "1f465": "\xf0\x9f\x91\xa5",
    "1f466": "\xf0\x9f\x91\xa6",
    "1f467": "\xf0\x9f\x91\xa7",
    "1f468": "\xf0\x9f\x91\xa8",
    "1f469": "\xf0\x9f\x91\xa9",
    "1f46a": "\xf0\x9f\x91\xaa",
    "1f46b": "\xf0\x9f\x91\xab",
    "1f46c": "\xf0\x9f\x91\xac",
    "1f46d": "\xf0\x9f\x91\xad",
    "1f46e": "\xf0\x9f\x91\xae",
    "1f46f": "\xf0\x9f\x91\xaf",
    "1f470": "\xf0\x9f\x91\xb0",
    "1f471": "\xf0\x9f\x91\xb1",
    "1f472": "\xf0\x9f\x91\xb2",
    "1f473": "\xf0\x9f\x91\xb3",
    "1f474": "\xf0\x9f\x91\xb4",
    "1f475": "\xf0\x9f\x91\xb5",
    "1f476": "\xf0\x9f\x91\xb6",
    "1f477": "\xf0\x9f\x91\xb7",
    "1f478": "\xf0\x9f\x91\xb8",
    "1f479": "\xf0\x9f\x91\xb9",
    "1f47a": "\xf0\x9f\x91\xba",
    "1f47b": "\xf0\x9f\x91\xbb",
    "1f47c": "\xf0\x9f\x91\xbc",
    "1f47d": "\xf0\x9f\x91\xbd",
    "1f47e": "\xf0\x9f\x91\xbe",
    "1f47f": "\xf0\x9f\x91\xbf",
    "1f480": "\xf0\x9f\x92\x80",
    "1f481": "\xf0\x9f\x92\x81",
    "1f482": "\xf0\x9f\x92\x82",
    "1f483": "\xf0\x9f\x92\x83",
    "1f484": "\xf0\x9f\x92\x84",
    "1f485": "\xf0\x9f\x92\x85",
    "1f486": "\xf0\x9f\x92\x86",
    "1f487": "\xf0\x9f\x92\x87",
    "1f488": "\xf0\x9f\x92\x88",
    "1f489": "\xf0\x9f\x92\x89",
    "1f48a": "\xf0\x9f\x92\x8a",
    "1f48b": "\xf0\x9f\x92\x8b",
    "1f48c": "\xf0\x9f\x92\x8c",
    "1f48d": "\xf0\x9f\x92\x8d",
    "1f48e": "\xf0\x9f\x92\x8e",
    "1f48f": "\xf0\x9f\x92\x8f",
    "1f490": "\xf0\x9f\x92\x90",
    "1f491": "\xf0\x9f\x92\x91",
    "1f492": "\xf0\x9f\x92\x92",
    "1f493": "\xf0\x9f\x92\x93",
    "1f494": "\xf0\x9f\x92\x94",
    "1f495": "\xf0\x9f\x92\x95",
    "1f496": "\xf0\x9f\x92\x96",
    "1f497": "\xf0\x9f\x92\x97",
    "1f498": "\xf0\x9f\x92\x98",
    "1f499": "\xf0\x9f\x92\x99",
    "1f49a": "\xf0\x9f\x92\x9a",
    "1f49b": "\xf0\x9f\x92\x9b",
    "1f49c": "\xf0\x9f\x92\x9c",
    "1f49d": "\xf0\x9f\x92\x9d",
    "1f49e": "\xf0\x9f\x92\x9e",
    "1f49f": "\xf0\x9f\x92\x9f",
    "1f4a0": "\xf0\x9f\x92\xa0",
    "1f4a1": "\xf0\x9f\x92\xa1",
    "1f4a2": "\xf0\x9f\x92\xa2",
    "1f4a3": "\xf0\x9f\x92\xa3",
    "1f4a4": "\xf0\x9f\x92\xa4",
    "1f4a5": "\xf0\x9f\x92\xa5",
    "1f4a6": "\xf0\x9f\x92\xa6",
    "1f4a7": "\xf0\x9f\x92\xa7",
    "1f4a8": "\xf0\x9f\x92\xa8",
    "1f4a9": "\xf0\x9f\x92\xa9",
    "1f4aa": "\xf0\x9f\x92\xaa",
    "1f4ab": "\xf0\x9f\x92\xab",
    "1f4ac": "\xf0\x9f\x92\xac",
    "1f4ad": "\xf0\x9f\x92\xad",
    "1f4ae": "\xf0\x9f\x92\xae",
    "1f4af": "\xf0\x9f\x92\xaf",
    "1f4b0": "\xf0\x9f\x92\xb0",
    "1f4b1": "\xf0\x9f\x92\xb1",
    "1f4b2": "\xf0\x9f\x92\xb2",
    "1f4b3": "\xf0\x9f\x92\xb3",
    "1f4b4": "\xf0\x9f\x92\xb4",
    "1f4b5": "\xf0\x9f\x92\xb5",
    "1f4b6": "\xf0\x9f\x92\xb6",
    "1f4b7": "\xf0\x9f\x92\xb7",
    "1f4b8": "\xf0\x9f\x92\xb8",
    "1f4b9": "\xf0\x9f\x92\xb9",
    "1f4ba": "\xf0\x9f\x92\xba",
    "1f4bb": "\xf0\x9f\x92\xbb",
    "1f4bc": "\xf0\x9f\x92\xbc",
    "1f4bd": "\xf0\x9f\x92\xbd",
    "1f4be": "\xf0\x9f\x92\xbe",
    "1f4bf": "\xf0\x9f\x92\xbf",
    "1f4c0": "\xf0\x9f\x93\x80",
    "1f4c1": "\xf0\x9f\x93\x81",
    "1f4c2": "\xf0\x9f\x93\x82",
    "1f4c3": "\xf0\x9f\x93\x83",
    "1f4c4": "\xf0\x9f\x93\x84",
    "1f4c5": "\xf0\x9f\x93\x85",
    "1f4c6": "\xf0\x9f\x93\x86",
    "1f4c7": "\xf0\x9f\x93\x87",
    "1f4c8": "\xf0\x9f\x93\x88",
    "1f4c9": "\xf0\x9f\x93\x89",
    "1f4ca": "\xf0\x9f\x93\x8a",
    "1f4cb": "\xf0\x9f\x93\x8b",
    "1f4cc": "\xf0\x9f\x93\x8c",
    "1f4cd": "\xf0\x9f\x93\x8d",
    "1f4ce": "\xf0\x9f\x93\x8e",
    "1f4cf": "\xf0\x9f\x93\x8f",
    "1f4d0": "\xf0\x9f\x93\x90",
    "1f4d1": "\xf0\x9f\x93\x91",
    "1f4d2": "\xf0\x9f\x93\x92",
    "1f4d3": "\xf0\x9f\x93\x93",
    "1f4d4": "\xf0\x9f\x93\x94",
    "1f4d5": "\xf0\x9f\x93\x95",
    "1f4d6": "\xf0\x9f\x93\x96",
    "1f4d7": "\xf0\x9f\x93\x97",
    "1f4d8": "\xf0\x9f\x93\x98",
    "1f4d9": "\xf0\x9f\x93\x99",
    "1f4da": "\xf0\x9f\x93\x9a",
    "1f4db": "\xf0\x9f\x93\x9b",
    "1f4dc": "\xf0\x9f\x93\x9c",
    "1f4dd": "\xf0\x9f\x93\x9d",
    "1f4de": "\xf0\x9f\x93\x9e",
    "1f4df": "\xf0\x9f\x93\x9f",
    "1f4e0": "\xf0\x9f\x93\xa0",
    "1f4e1": "\xf0\x9f\x93\xa1",
    "1f4e2": "\xf0\x9f\x93\xa2",
    "1f4e3": "\xf0\x9f\x93\xa3",
    "1f4e4": "\xf0\x9f\x93\xa4",
    "1f4e5": "\xf0\x9f\x93\xa5",
    "1f4e6": "\xf0\x9f\x93\xa6",
    "1f4e7": "\xf0\x9f\x93\xa7",
    "1f4e8": "\xf0\x9f\x93\xa8",
    "1f4e9": "\xf0\x9f\x93\xa9",
    "1f4ea": "\xf0\x9f\x93\xaa",
    "1f4eb": "\xf0\x9f\x93\xab",
    "1f4ec": "\xf0\x9f\x93\xac",
    "1f4ed": "\xf0\x9f\x93\xad",
    "1f4ee": "\xf0\x9f\x93\xae",
    "1f4ef": "\xf0\x9f\x93\xaf",
    "1f4f0": "\xf0\x9f\x93\xb0",
    "1f4f1": "\xf0\x9f\x93\xb1",
    "1f4f2": "\xf0\x9f\x93\xb2",
    "1f4f3": "\xf0\x9f\x93\xb3",
    "1f4f4": "\xf0\x9f\x93\xb4",
    "1f4f5": "\xf0\x9f\x93\xb5",
    "1f4f6": "\xf0\x9f\x93\xb6",
    "1f4f7": "\xf0\x9f\x93\xb7",
    "1f4f8": "\xf0\x9f\x93\xb8",
    "1f4f9": "\xf0\x9f\x93\xb9",
    "1f4fa": "\xf0\x9f\x93\xba",
    "1f4fb": "\xf0\x9f\x93\xbb",
    "1f4fc": "\xf0\x9f\x93\xbc",
    "1f4fd": "\xf0\x9f\x93\xbd",
    "1f4ff": "\xf0\x9f\x93\xbf",
    "1f500": "\xf0\x9f\x94\x80",
    "1f501": "\xf0\x9f\x94\x81",
    "1f502": "\xf0\x9f\x94\x82",
    "1f503": "\xf0\x9f\x94\x83",
    "1f504": "\xf0\x9f\x94\x84",
    "1f505": "\xf0\x9f\x94\x85",
    "1f506": "\xf0\x9f\x94\x86",
    "1f507": "\xf0\x9f\x94\x87",
    "1f508": "\xf0\x9f\x94\x88",
    "1f509": "\xf0\x9f\x94\x89",
    "1f50a": "\xf0\x9f\x94\x8a",
    "1f50b": "\xf0\x9f\x94\x8b",
    "1f50c": "\xf0\x9f\x94\x8c",
    "1f50d": "\xf0\x9f\x94\x8d",
    "1f50e": "\xf0\x9f\x94\x8e",
    "1f50f": "\xf0\x9f\x94\x8f",
    "1f510": "\xf0\x9f\x94\x90",
    "1f511": "\xf0\x9f\x94\x91",
    "1f512": "\xf0\x9f\x94\x92",
    "1f513": "\xf0\x9f\x94\x93",
    "1f514": "\xf0\x9f\x94\x94",
    "1f515": "\xf0\x9f\x94\x95",
    "1f516": "\xf0\x9f\x94\x96",
    "1f517": "\xf0\x9f\x94\x97",
    "1f518": "\xf0\x9f\x94\x98",
    "1f519": "\xf0\x9f\x94\x99",
    "1f51a": "\xf0\x9f\x94\x9a",
    "1f51b": "\xf0\x9f\x94\x9b",
    "1f51c": "\xf0\x9f\x94\x9c",
    "1f51d": "\xf0\x9f\x94\x9d",
    "1f51e": "\xf0\x9f\x94\x9e",
    "1f51f": "\xf0\x9f\x94\x9f",
    "1f520": "\xf0\x9f\x94\xa0",
    "1f521": "\xf0\x9f\x94\xa1",
    "1f522": "\xf0\x9f\x94\xa2",
    "1f523": "\xf0\x9f\x94\xa3",
    "1f524": "\xf0\x9f\x94\xa4",
    "1f525": "\xf0\x9f\x94\xa5",
    "1f526": "\xf0\x9f\x94\xa6",
    "1f527": "\xf0\x9f\x94\xa7",
    "1f528": "\xf0\x9f\x94\xa8",
    "1f529": "\xf0\x9f\x94\xa9",
    "1f52a": "\xf0\x9f\x94\xaa",
    "1f52b": "\xf0\x9f\x94\xab",
    "1f52c": "\xf0\x9f\x94\xac",
    "1f52d": "\xf0\x9f\x94\xad",
    "1f52e": "\xf0\x9f\x94\xae",
    "1f52f": "\xf0\x9f\x94\xaf",
    "1f530": "\xf0\x9f\x94\xb0",
    "1f531": "\xf0\x9f\x94\xb1",
    "1f532": "\xf0\x9f\x94\xb2",
    "1f533": "\xf0\x9f\x94\xb3",
    "1f534": "\xf0\x9f\x94\xb4",
    "1f535": "\xf0\x9f\x94\xb5",
    "1f536": "\xf0\x9f\x94\xb6",
    "1f537": "\xf0\x9f\x94\xb7",
    "1f538": "\xf0\x9f\x94\xb8",
    "1f539": "\xf0\x9f\x94\xb9",
    "1f53a": "\xf0\x9f\x94\xba",
    "1f53b": "\xf0\x9f\x94\xbb",
    "1f53c": "\xf0\x9f\x94\xbc",
    "1f53d": "\xf0\x9f\x94\xbd",
    "1f549": "\xf0\x9f\x95\x89",
    "1f54a": "\xf0\x9f\x95\x8a",
    "1f54b": "\xf0\x9f\x95\x8b",
    "1f54c": "\xf0\x9f\x95\x8c",
    "1f54d": "\xf0\x9f\x95\x8d",
    "1f54e": "\xf0\x9f\x95\x8e",
    "1f550": "\xf0\x9f\x95\x90",
    "1f551": "\xf0\x9f\x95\x91",
    "1f552": "\xf0\x9f\x95\x92",
    "1f553": "\xf0\x9f\x95\x93",
    "1f554": "\xf0\x9f\x95\x94",
    "1f555": "\xf0\x9f\x95\x95",
    "1f556": "\xf0\x9f\x95\x96",
    "1f557": "\xf0\x9f\x95\x97",
    "1f558": "\xf0\x9f\x95\x98",
    "1f559": "\xf0\x9f\x95\x99",
    "1f55a": "\xf0\x9f\x95\x9a",
    "1f55b": "\xf0\x9f\x95\x9b",
    "1f55c": "\xf0\x9f\x95\x9c",
    "1f55d": "\xf0\x9f\x95\x9d",
    "1f55e": "\xf0\x9f\x95\x9e",
    "1f55f": "\xf0\x9f\x95\x9f",
    "1f560": "\xf0\x9f\x95\xa0",
    "1f561": "\xf0\x9f\x95\xa1",
    "1f562": "\xf0\x9f\x95\xa2",
    "1f563": "\xf0\x9f\x95\xa3",
    "1f564": "\xf0\x9f\x95\xa4",
    "1f565": "\xf0\x9f\x95\xa5",
    "1f566": "\xf0\x9f\x95\xa6",
    "1f567": "\xf0\x9f\x95\xa7",
    "1f56f": "\xf0\x9f\x95\xaf",
    "1f570": "\xf0\x9f\x95\xb0",
    "1f573": "\xf0\x9f\x95\xb3",
    "1f574": "\xf0\x9f\x95\xb4",
    "1f575": "\xf0\x9f\x95\xb5",
    "1f576": "\xf0\x9f\x95\xb6",
    "1f577": "\xf0\x9f\x95\xb7",
    "1f578": "\xf0\x9f\x95\xb8",
    "1f579": "\xf0\x9f\x95\xb9",
    "1f587": "\xf0\x9f\x96\x87",
    "1f58a": "\xf0\x9f\x96\x8a",
    "1f58b": "\xf0\x9f\x96\x8b",
    "1f58c": "\xf0\x9f\x96\x8c",
    "1f58d": "\xf0\x9f\x96\x8d",
    "1f590": "\xf0\x9f\x96\x90",
    "1f595": "\xf0\x9f\x96\x95",
    "1f596": "\xf0\x9f\x96\x96",
    "1f5a5": "\xf0\x9f\x96\xa5",
    "1f5a8": "\xf0\x9f\x96\xa8",
    "1f5b1": "\xf0\x9f\x96\xb1",
    "1f5b2": "\xf0\x9f\x96\xb2",
    "1f5bc": "\xf0\x9f\x96\xbc",
    "1f5c2": "\xf0\x9f\x97\x82",
    "1f5c3": "\xf0\x9f\x97\x83",
    "1f5c4": "\xf0\x9f\x97\x84",
    "1f5d1": "\xf0\x9f\x97\x91",
    "1f5d2": "\xf0\x9f\x97\x92",
    "1f5d3": "\xf0\x9f\x97\x93",
    "1f5dc": "\xf0\x9f\x97\x9c",
    "1f5dd": "\xf0\x9f\x97\x9d",
    "1f5de": "\xf0\x9f\x97\x9e",
    "1f5e1": "\xf0\x9f\x97\xa1",
    "1f5e3": "\xf0\x9f\x97\xa3",
    "1f5e8": "\xf0\x9f\x97\xa8",
    "1f5ef": "\xf0\x9f\x97\xaf",
    "1f5f3": "\xf0\x9f\x97\xb3",
    "1f5fa": "\xf0\x9f\x97\xba",
    "1f5fb": "\xf0\x9f\x97\xbb",
    "1f5fc": "\xf0\x9f\x97\xbc",
    "1f5fd": "\xf0\x9f\x97\xbd",
    "1f5fe": "\xf0\x9f\x97\xbe",
    "1f5ff": "\xf0\x9f\x97\xbf",
    "1f600": "\xf0\x9f\x98\x80",
    "1f601": "\xf0\x9f\x98\x81",
    "1f602": "\xf0\x9f\x98\x82",
    "1f603": "\xf0\x9f\x98\x83",
    "1f604": "\xf0\x9f\x98\x84",
    "1f605": "\xf0\x9f\x98\x85",
    "1f606": "\xf0\x9f\x98\x86",
    "1f607": "\xf0\x9f\x98\x87",
    "1f608": "\xf0\x9f\x98\x88",
    "1f609": "\xf0\x9f\x98\x89",
    "1f60a": "\xf0\x9f\x98\x8a",
    "1f60b": "\xf0\x9f\x98\x8b",
    "1f60c": "\xf0\x9f\x98\x8c",
    "1f60d": "\xf0\x9f\x98\x8d",
    "1f60e": "\xf0\x9f\x98\x8e",
    "1f60f": "\xf0\x9f\x98\x8f",
    "1f610": "\xf0\x9f\x98\x90",
    "1f611": "\xf0\x9f\x98\x91",
    "1f612": "\xf0\x9f\x98\x92",
    "1f613": "\xf0\x9f\x98\x93",
    "1f614": "\xf0\x9f\x98\x94",
    "1f615": "\xf0\x9f\x98\x95",
    "1f616": "\xf0\x9f\x98\x96",
    "1f617": "\xf0\x9f\x98\x97",
    "1f618": "\xf0\x9f\x98\x98",
    "1f619": "\xf0\x9f\x98\x99",
    "1f61a": "\xf0\x9f\x98\x9a",
    "1f61b": "\xf0\x9f\x98\x9b",
    "1f61c": "\xf0\x9f\x98\x9c",
    "1f61d": "\xf0\x9f\x98\x9d",
    "1f61e": "\xf0\x9f\x98\x9e",
    "1f61f": "\xf0\x9f\x98\x9f",
    "1f620": "\xf0\x9f\x98\xa0",
    "1f621": "\xf0\x9f\x98\xa1",
    "1f622": "\xf0\x9f\x98\xa2",
    "1f623": "\xf0\x9f\x98\xa3",
    "1f624": "\xf0\x9f\x98\xa4",
    "1f625": "\xf0\x9f\x98\xa5",
    "1f626": "\xf0\x9f\x98\xa6",
    "1f627": "\xf0\x9f\x98\xa7",
    "1f628": "\xf0\x9f\x98\xa8",
    "1f629": "\xf0\x9f\x98\xa9",
    "1f62a": "\xf0\x9f\x98\xaa",
    "1f62b": "\xf0\x9f\x98\xab",
    "1f62c": "\xf0\x9f\x98\xac",
    "1f62d": "\xf0\x9f\x98\xad",
    "1f62e": "\xf0\x9f\x98\xae",
    "1f62f": "\xf0\x9f\x98\xaf",
    "1f630": "\xf0\x9f\x98\xb0",
    "1f631": "\xf0\x9f\x98\xb1",
    "1f632": "\xf0\x9f\x98\xb2",
    "1f633": "\xf0\x9f\x98\xb3",
    "1f634": "\xf0\x9f\x98\xb4",
    "1f635": "\xf0\x9f\x98\xb5",
    "1f636": "\xf0\x9f\x98\xb6",
    "1f637": "\xf0\x9f\x98\xb7",
    "1f638": "\xf0\x9f\x98\xb8",
    "1f639": "\xf0\x9f\x98\xb9",
    "1f63a": "\xf0\x9f\x98\xba",
    "1f63b": "\xf0\x9f\x98\xbb",
    "1f63c": "\xf0\x9f\x98\xbc",
    "1f63d": "\xf0\x9f\x98\xbd",
    "1f63e": "\xf0\x9f\x98\xbe",
    "1f63f": "\xf0\x9f\x98\xbf",
    "1f640": "\xf0\x9f\x99\x80",
    "1f641": "\xf0\x9f\x99\x81",
    "1f642": "\xf0\x9f\x99\x82",
    "1f643": "\xf0\x9f\x99\x83",
    "1f644": "\xf0\x9f\x99\x84",
    "1f645": "\xf0\x9f\x99\x85",
    "1f646": "\xf0\x9f\x99\x86",
    "1f647": "\xf0\x9f\x99\x87",
    "1f648": "\xf0\x9f\x99\x88",
    "1f649": "\xf0\x9f\x99\x89",
    "1f64a": "\xf0\x9f\x99\x8a",
    "1f64b": "\xf0\x9f\x99\x8b",
    "1f64c": "\xf0\x9f\x99\x8c",
    "1f64d": "\xf0\x9f\x99\x8d",
    "1f64e": "\xf0\x9f\x99\x8e",
    "1f64f": "\xf0\x9f\x99\x8f",
    "1f680": "\xf0\x9f\x9a\x80",
    "1f681": "\xf0\x9f\x9a\x81",
    "1f682": "\xf0\x9f\x9a\x82",
    "1f683": "\xf0\x9f\x9a\x83",
    "1f684": "\xf0\x9f\x9a\x84",
    "1f685": "\xf0\x9f\x9a\x85",
    "1f686": "\xf0\x9f\x9a\x86",
    "1f687": "\xf0\x9f\x9a\x87",
    "1f688": "\xf0\x9f\x9a\x88",
    "1f689": "\xf0\x9f\x9a\x89",
    "1f68a": "\xf0\x9f\x9a\x8a",
    "1f68b": "\xf0\x9f\x9a\x8b",
    "1f68c": "\xf0\x9f\x9a\x8c",
    "1f68d": "\xf0\x9f\x9a\x8d",
    "1f68e": "\xf0\x9f\x9a\x8e",
    "1f68f": "\xf0\x9f\x9a\x8f",
    "1f690": "\xf0\x9f\x9a\x90",
    "1f691": "\xf0\x9f\x9a\x91",
    "1f692": "\xf0\x9f\x9a\x92",
    "1f693": "\xf0\x9f\x9a\x93",
    "1f694": "\xf0\x9f\x9a\x94",
    "1f695": "\xf0\x9f\x9a\x95",
    "1f696": "\xf0\x9f\x9a\x96",
    "1f697": "\xf0\x9f\x9a\x97",
    "1f698": "\xf0\x9f\x9a\x98",
    "1f699": "\xf0\x9f\x9a\x99",
    "1f69a": "\xf0\x9f\x9a\x9a",
    "1f69b": "\xf0\x9f\x9a\x9b",
    "1f69c": "\xf0\x9f\x9a\x9c",
    "1f69d": "\xf0\x9f\x9a\x9d",
    "1f69e": "\xf0\x9f\x9a\x9e",
    "1f69f": "\xf0\x9f\x9a\x9f",
    "1f6a0": "\xf0\x9f\x9a\xa0",
    "1f6a1": "\xf0\x9f\x9a\xa1",
    "1f6a2": "\xf0\x9f\x9a\xa2",
    "1f6a3": "\xf0\x9f\x9a\xa3",
    "1f6a4": "\xf0\x9f\x9a\xa4",
    "1f6a5": "\xf0\x9f\x9a\xa5",
    "1f6a6": "\xf0\x9f\x9a\xa6",
    "1f6a7": "\xf0\x9f\x9a\xa7",
    "1f6a8": "\xf0\x9f\x9a\xa8",
    "1f6a9": "\xf0\x9f\x9a\xa9",
    "1f6aa": "\xf0\x9f\x9a\xaa",
    "1f6ab": "\xf0\x9f\x9a\xab",
    "1f6ac": "\xf0\x9f\x9a\xac",
    "1f6ad": "\xf0\x9f\x9a\xad",
    "1f6ae": "\xf0\x9f\x9a\xae",
    "1f6af": "\xf0\x9f\x9a\xaf",
    "1f6b0": "\xf0\x9f\x9a\xb0",
    "1f6b1": "\xf0\x9f\x9a\xb1",
    "1f6b2": "\xf0\x9f\x9a\xb2",
    "1f6b3": "\xf0\x9f\x9a\xb3",
    "1f6b4": "\xf0\x9f\x9a\xb4",
    "1f6b5": "\xf0\x9f\x9a\xb5",
    "1f6b6": "\xf0\x9f\x9a\xb6",
    "1f6b7": "\xf0\x9f\x9a\xb7",
    "1f6b8": "\xf0\x9f\x9a\xb8",
    "1f6b9": "\xf0\x9f\x9a\xb9",
    "1f6ba": "\xf0\x9f\x9a\xba",
    "1f6bb": "\xf0\x9f\x9a\xbb",
    "1f6bc": "\xf0\x9f\x9a\xbc",
    "1f6bd": "\xf0\x9f\x9a\xbd",
    "1f6be": "\xf0\x9f\x9a\xbe",
    "1f6bf": "\xf0\x9f\x9a\xbf",
    "1f6c0": "\xf0\x9f\x9b\x80",
    "1f6c1": "\xf0\x9f\x9b\x81",
    "1f6c2": "\xf0\x9f\x9b\x82",
    "1f6c3": "\xf0\x9f\x9b\x83",
    "1f6c4": "\xf0\x9f\x9b\x84",
    "1f6c5": "\xf0\x9f\x9b\x85",
    "1f6cb": "\xf0\x9f\x9b\x8b",
    "1f6cc": "\xf0\x9f\x9b\x8c",
    "1f6cd": "\xf0\x9f\x9b\x8d",
    "1f6ce": "\xf0\x9f\x9b\x8e",
    "1f6cf": "\xf0\x9f\x9b\x8f",
    "1f6d0": "\xf0\x9f\x9b\x90",
    "1f6e0": "\xf0\x9f\x9b\xa0",
    "1f6e1": "\xf0\x9f\x9b\xa1",
    "1f6e2": "\xf0\x9f\x9b\xa2",
    "1f6e3": "\xf0\x9f\x9b\xa3",
    "1f6e4": "\xf0\x9f\x9b\xa4",
    "1f6e5": "\xf0\x9f\x9b\xa5",
    "1f6e9": "\xf0\x9f\x9b\xa9",
    "1f6eb": "\xf0\x9f\x9b\xab",
    "1f6ec": "\xf0\x9f\x9b\xac",
    "1f6f0": "\xf0\x9f\x9b\xb0",
    "1f6f3": "\xf0\x9f\x9b\xb3",
    "1f910": "\xf0\x9f\xa4\x90",
    "1f911": "\xf0\x9f\xa4\x91",
    "1f912": "\xf0\x9f\xa4\x92",
    "1f913": "\xf0\x9f\xa4\x93",
    "1f914": "\xf0\x9f\xa4\x94",
    "1f915": "\xf0\x9f\xa4\x95",
    "1f916": "\xf0\x9f\xa4\x96",
    "1f917": "\xf0\x9f\xa4\x97",
    "1f918": "\xf0\x9f\xa4\x98",
    "1f980": "\xf0\x9f\xa6\x80",
    "1f981": "\xf0\x9f\xa6\x81",
    "1f982": "\xf0\x9f\xa6\x82",
    "1f983": "\xf0\x9f\xa6\x83",
    "1f984": "\xf0\x9f\xa6\x84",
    "1f9c0": "\xf0\x9f\xa7\x80"
};
var emojiData = {
    '1f60a': "😊",
    "1f60b": "😋",
    "1f60c": "😌",
    "1f60d": "😍",
    "1f60e": "😎",
    "1f60f": "😏",
    "1f61a": "😚",
    "1f61b": "😛",
    "1f61c": "😜",
    "1f61d": "😝",

    "1f61e": "😞",
    "1f61f": "😟",
    "1f62a": "😪",
    "1f62b": "😫",
    "1f62c": "😬",
    "1f62d": "😭",
    "1f62e": "😮",
    "1f62f": "😧",
    "1f600": "😃",
    "1f601": "😄",

    "1f602": "😂",
    "1f603": "😃",
    "1f604": "😄",
    "1f605": "😅",
    "1f606": "😆",
    "1f607": "😇",
    "1f608": "😈",
    "1f609": "😉",
    "1f610": "😐",
    "1f611": "😑",

    "1f612": "😒",
    "1f613": "😓",
    "1f614": "😌",
    "1f615": "😕",
    "1f616": "😫",
    "1f617": "😗",
    "1f618": "😘",
    "1f619": "😙",
    "1f620": "😠",
    "1f621": "😡",

    "1f622": "😥",
    "1f623": "😣",
    "1f624": "😤",
    "1f625": "😥",
    "1f626": "😧",
    "1f627": "😲",
    "1f628": "😨",
    "1f629": "😫",
    "1f630": "😰",
    "1f631": "😱"

};


function removeHTMLTag(str) {
    str = str.replace(/<\/?[^>]*>/g,''); //去除HTML tag
    str = str.replace(/[ | ]*\n/g,'\n'); //去除行尾空白
    str=str.replace(/ /ig,'');//去掉
    str=str.replace(/&nbsp;/g,' ');//去掉
    return str;
}