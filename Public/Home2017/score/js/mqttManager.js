var game_type = 1;
var chatSend = null;
var qos = 1;
var retain = false;
var topic = ['qqty/1_' + gameId + '/chat', "qqty/api500/fb/#"];

//接收数据处理
MqInit.onMessage(function (topic, message) {
    try {
        var tp = topic;
        if (tp.indexOf('fb/goal') > -1 && tp.indexOf('fb/goalpsw') < 0) {//足球全场赔率
            goal(message, 1);
        } else if (tp.indexOf('/fb/goalpsw') > -1) {//足球半场赔率
            goal(message, 2);
        } else if (tp.indexOf('/chat') > -1) {//聊天室消息
            onChat(message);
        }
    } catch (e) {
        console.log(e);
    }
}, topic);

function goal(payload, oddsType) {
    var temp = JSON.parse(payload);
    var data = temp['data'];
    if (data[gameId] != undefined) {
        var oddsData = data[gameId];
        var oddsArr = new Array();
        oddsArr[0] = oddsData[1];
        oddsArr[1] = oddsData[0];
        oddsArr[2] = oddsData[2];
        oddsArr[3] = oddsData[7];
        oddsArr[4] = oddsData[6];
        oddsArr[5] = oddsData[8];
        var spanIndex = 0;
        for (var i = 0; i < oddsArr.length; i++) {
            if (oddsType == 1) {//全场
                if (i <= 2) {
                    var selectOddIndex = 0;
                } else {
                    var selectOddIndex = 2;
                }
            } else if (oddsType == 2) {//半场
                if (i <= 2) {
                    var selectOddIndex = 1
                } else {
                    var selectOddIndex = 3;
                }
            }
            spanIndex = spanIndex > 2 ? 0 : spanIndex;

            if (spanIndex != 1) {
                var ob = $('.selectOdd').eq(selectOddIndex).find('span').eq(spanIndex);
                var oldVal = ob.text();
                var curVal = oddsArr[i];
                if (curVal != oldVal) {
                    var fc = curVal > oldVal ? 'up-red' : 'down-green';
                    if (i == 1) {
                        curVal = fswExpReplace(curVal);
                    }
                    ob.text(curVal).addClass(fc).delay(20000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }
            }
            spanIndex++;
        }
    }
}

function onChat(d) {
    var temp = JSON.parse(d);
    var data = chatSend = temp.data;
    console.log(temp);
    var nickStyle = '';
    var report = '';
    if ((temp.action == 'say' || temp.action == 'sayHello') && temp.dataType == 'text') {
        var lvSpan = '';
        if (data.lv != undefined && data.lv != '') {
            var lv = 0;
            if (game_type == 1) {
                lv = data.lv > data.lv_bet ? data.lv : data.lv_bet;
            } else {
                lv = data.lv_bk;
            }
            lv = parseInt(lv);
            if (lv < 4) {
                lv = '';
            }

            if (lv != '') {
                lvSpan = '<span class="tips"><span class="m-r-2">LV</span>' + lv + '</span>';
            } else {
                lvSpan = '';
            }
        }

        if (isAdmin == 1) {
            report = '<a class="reportBtn" onclick="report(1,chatSend)">屏蔽</a><a class="reportBtn" onclick="report(3,chatSend)">踢出</a>';
        }

        if (userInfo && data.user_id == userInfo.user_id) {
            nickStyle = 'style="color:#04AF77"'
        } else {
            nickStyle = 'style="color:#6daade"';
        }
        var c = jEmoji.unifiedToHTML(data.content);
        var chatRowId = "chat-row-" + data.user_id + data.msg_id;
        var li = '<li class="start clearfix" id=' + chatRowId + '><span class="live-lb initial pull-left">' + lvSpan + '</span></span><span class="name pull-left" ' + nickStyle + '>' + data.nick_name + '<span class="colon">：</span><span class="shield-report">' + report + '<a class="reportBtn" onclick="report(2,chatSend)">举报</a></span></span><span class="content-txt">' + c + '</span></li>';
        $(".conChatList ul").append(li);
        $(".conChatList").mCustomScrollbar("scrollTo", "bottom", {
            scrollInertia: 0
        });

        // $.ajax({
        //     type: 'POST',
        //     url: '/emojiToHtml',
        //     dataType: 'json',
        //     data: {content: data.content},
        //     success: function (d) {
        //         var chatRowId = "chat-row-" + data.user_id + data.msg_id;
        //         var li = '<li class="start clearfix" id=' + chatRowId + '><span class="live-lb initial pull-left">' + lvSpan + '</span></span><span class="name pull-left" ' + nickStyle + '>' + data.nick_name + '<span class="colon">：</span><span class="shield-report">' + report + '<a class="reportBtn" onclick="report(2,chatSend)">举报</a></span></span><span class="content-txt">' + d.content + '</span></li>';
        //         $(".conChatList ul").append(li);
        //         $(".conChatList").mCustomScrollbar("scrollTo", "bottom", {
        //             scrollInertia: 500
        //         });
        //     }
        // });

    } else if (temp.action == 'kickout' || temp.action == 'forbid' || temp.action == 'sensitiveSay') {
        var chatRowId = "chat-row-" + temp.data.user_id;
        $('li[id^=' + chatRowId + ']').each(function () {
            $(this).remove();
        });

        if(userInfo && temp.data.user_id == userInfo.user_id){
            _alert('系统提示', temp.data.notice_str);
        }
    }else if(temp.action == 'timeLimit'){
        if(userInfo && temp.data.user_id == userInfo.user_id){
            _alert('系统提示', temp.data.notice_str);
        }
    }
}
