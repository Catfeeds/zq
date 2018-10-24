var game_type = 1;
var chatSend = null;
var qos = 1;
var retain = false;
var topic = ['qqty/1_' + gameId + '/chat', "qqty/api500/fb/#", 'dh/fb/+/'+gameId,'qqty/woman_' + gameId + '/list'];
var listTopic = new Array();
$('#ul li').each(function(k,v){
    listTopic[k] = $(this).attr('mqtt_room_topic');
});
topic = topic.concat(listTopic);
var liveUrl = false;

$(function(){
    liveRegion();
    // if(zb_url)
    // {
    //     $(".svglive").css('padding','0px 0px');
    //     $(".firstLi").removeClass('on');
    //     $(".secondLi").addClass('on');
    //     video_iframe(url_type);
    //     $("#live").attr('src',zb_url);
    // }else{
    //     $("#iframe_box").removeClass();
    //     $("#iframe_box").addClass("dh_css");
    //     $("#live").attr('src',dh_url);
    // }
    if($("img.lazy_home").length > 0) {
        $("img.lazy_home").lazyload({
            placeholder: staticDomain+"/Public/Home/images/common/home_def.png",
            effect: "fadeIn",
            threshold: 150,
            failurelimit: 100
        });
    }
    if($("img.lazy_away").length > 0) {
        $("img.lazy_away").lazyload({
            placeholder: staticDomain+"/Public/Home/images/common/away_def.png",
            effect: "fadeIn",
            threshold: 150,
            failurelimit: 100
        });
    }
    if($("#live").attr('src').length > 0){
        liveUrl = $("#live").attr('src');
    }

    //获取美女聊天历史记录
    getLiveHistoryChat();

});


/**
 * 接收消息
 * @param message
 */
MqInit.onMessage(function (topic, message) {
    try {
        var tp = topic;
        if(tp.indexOf('qqty/live_') > -1){
            var verTopic = $('.liveTopic').val();
            if(tp.indexOf(verTopic) > -1){

                var temp = JSON.parse(message);
                switch(temp.action){
                    //暂停
                    case 'livePause':
                        stopLive(2);
                        break;
                    //继续
                    case 'liveContinue':
                        stopLive(1);
                        break;
                    //主播切换场次
                    case 'liveSwitchGame':
                    case 'liveCancelGameLink':
                        changeGame(temp);
                        break;
                    default:
                    //发言
                        onChat(message,'.liveRoom');
                }
            }
        }else if (tp.indexOf('qqty/woman_') > -1) {//更新美女直播列表
            updataLiveList(message);
        }else if (tp.indexOf('fb/goal') > -1 && tp.indexOf('fb/goalpsw') < 0) {//足球全场赔率
            goal(message, 1);
        } else if (tp.indexOf('/fb/goalpsw') > -1) {//足球半场赔率
            goal(message, 2);
        } else if (tp.indexOf('/chat') > -1) {//聊天室消息
            onChat(message,'.gameRoom');
        }else if(tp.indexOf('/flash') > -1 && !liveUrl){
            window.location.reload();
        }else if(tp.indexOf('fb/tech') > -1){
            var data = message;
            gameInfo(data);
        } else if(tp.indexOf('fb/event') > -1) {
            var data = message;
            gameDetail(data);
        }
    } catch (e) {
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
                    if (curVal == '') {
                        ob.text(curVal);
                    }else {
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
            } else {
                var ob = $('.selectOdd').eq(selectOddIndex).find('span').eq(spanIndex);
                var curVal = oddsArr[i];
                if (curVal != '封') {
                    curVal = fswExpReplace(curVal);
                    ob.text(curVal);
                } else {
                    ob.text(curVal);
                }
            }
            spanIndex++;
        }
    }
}

function onChat(d,_class) {
    var temp = JSON.parse(d);
    var data = chatSend = temp.data;
    var nickStyle = '';
    var report = '';
    if ((temp.action == 'say' || temp.action == 'sayHello') && temp.dataType == 'text') {
        var lvSpan = '';
        // if (data.lv != undefined && data.lv != '') {
        //     var lv = 0;
        //     if (game_type == 1) {
        //         if (data.lv_bet != undefined && data.lv_bet != '')
        //         {
        //             lv = data.lv > data.lv_bet ? data.lv : data.lv_bet;
        //         }else{
        //             lv = data.lv;
        //         }
        //     } else {
        //         lv = data.lv_bk;
        //     }
        //     lv = parseInt(lv);
        //     if (lv < 4) {
        //         lv = '';
        //     }
        //
        //     if (lv != '') {
        //         lvSpan = '<span class="tips"><span class="m-r-2">LV</span>' + lv + '</span>';
        //     } else {
        //         lvSpan = '';
        //     }
        // }

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
        $(_class+" ul").append(li);
        $(_class).mCustomScrollbar("scrollTo", "bottom", {
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
    }else if(temp.action == 'timeLimit' && temp.data.user_id == userInfo.user_id){
        _alert('系统提示', temp.data.notice_str);
    }
}


function gameDetail(data)
{
    if(data === undefined) return false;
    var temp = JSON.parse(data)['data'][0];
    var team_site = temp[1];
    var team_name = team_site === "1" ? home_team_name : away_team_name;
    var spot = team_site === "1" ? 'homeSpot' : 'awaySpot';
    var game_types = temp[2];
    var time = temp[3];
    var message = temp[6];
    var icon = "";
    var icon_site = "";
    switch (game_types) {
        case "1":
            icon = 'jinqiu.png';
            break;
        case "2":
            icon = "red-card.png";
            break;
        case "3":
            icon = "yellow-card.png";
            break;
        case "11":
            icon = "in-out.png";
            break;
        case "99":
            icon = "icon-corner-ball.png";
            break;
        case "57":
            icon = "penalty.png";
            break;
        case "7":
            icon = "penalty.png";
            break;
    }
    if (icon !== "") {
        icon_site = '<img src="/Public/Home/score/images/scoreLive/'+icon+'" width="auto" height="auto">';
    }
    var data_html = '<li>' +
        '<div class="timePoint">'+time+"'</div>" +
        '<div class="triangle-border">' +
        icon_site+message+'  -  ('+team_name+')</div>' +
        '<span class="spotPoint '+spot+'"></span>' +
        '</li>';
    $("div.eventList > ul").prepend(data_html)
}

//处理接受数据
function gameInfo(data)
{
    if(data === undefined) return false;
    // data = '{"is_home":0,"data":{"goal":"1","corner":"1","yellowCard":"1","redCard":"1","throwIn":"1","freeKick":"1","goalKick":"1","penalty":"1","substitution":"1", "shootInside" : "4","shootOutside" : "6","attack" : "86","dangerAttack" : "53","S5" : "","S6" : "","S7" : "47","ballKeep" : "","SC" : "1","offside":"10"},"tech_data":{"corner":["0","2"],"goal":["1","2"],"yellowCard":["3","4"],"redCard":["0","0"],"throwIn":["10","15"],"freeKick":["5","6"],"goalKick":["8","7"],"penalty":["0","0"],"substitution":["2","3"],"shootInside":["4","16"],"shootOutside":["3","3"],"attack":["10","30"],"dangerAttack":["5","20"],"ballKeep":["30","70"],"offside":["1","5"],"foul":["10","6"]}}'
    //{"status": 1, "data": {"290655": ["0.80", "9.5", "0.96", "0.87", "205.5", "0.79", "0.5", "0.80", "0.90", "105.5", "0.80", "0.80"]}, "update_time": 1519869212367}
    var temp = JSON.parse(data);
    var data = temp['tech_data'];

    //定义主客队class前缀
    var home = '.homeData-';
    var away = '.awayData-';

    //处理控球数
    if(data['ballKeep'])
    {
        var ballKeep = data['ballKeep'];
        var homeD = parseInt(ballKeep[0]);
        var awayD = parseInt(ballKeep[1]);
        var _homeD = (homeD/(homeD+awayD))*100;
        _homeD = Math.round(_homeD);
        var _awayD = (awayD/(homeD+awayD))*100;
        _awayD = Math.round(_awayD);
        $(home+'kql').find('h4').html(_homeD+'%');
        $(away+'kql').find('h4').html(_awayD+'%');
        $(home+'kqlt').find('span').css('width',_homeD+'%');
        $(away+'kqlt').find('span').css('width',_awayD+'%');
        if(awayD > 12)
        {
            $(away+'kqlt').find('em').css('color','');
        }else{
            $(away+'kqlt').find('em').css('color','#666');
        }
    }

    //处理危险进攻
    if(data['dangerAttack'])
    {
        var dangerAttack = data['dangerAttack'];
        var homeD = parseInt(dangerAttack[0]);
        var awayD = parseInt(dangerAttack[1]);
        $(home+'wxjg').find('.percent').html(homeD);
        $(away+'wxjg').find('.percent').html(awayD);
        var _homeD = (homeD/(homeD+awayD))*100;
        _homeD = Math.round(_homeD);
        var _awayD = (awayD/(homeD+awayD))*100;
        _awayD = Math.round(_awayD);
        if(_homeD < 30)
        {
            _homeD = 30;
            _awayD = 70;
        }else if(_awayD < 30){
            _homeD = 70;
            _awayD = 30;
        }
        $(home+'wxjg').removeClass('yesAttack').addClass('noAttack').css('width',_homeD+'%');
        $(away+'wxjg').removeClass('yesAttack').addClass('noAttack').css('width',_awayD+'%');
        $('.attackText').css('display','none');
        if(_homeD > _awayD)
        {
            $(home+'wxjg').removeClass('noAttack').addClass('yesAttack');
            $(home+'wxjg').find('.attackText').css('display','block');
        }else if(_homeD < _awayD){
            $(away+'wxjg').removeClass('noAttack').addClass('yesAttack');
            $(away+'wxjg').find('.attackText').css('display','block');
        }
    }

    //处理红牌
    if(data['redCard'])
    {
        var redCard = data['redCard'];
        var homeD = parseInt(redCard[0]);
        var awayD = parseInt(redCard[1]);
        $(home+'r').find('span').html(homeD);
        $(away+'r').find('span').html(awayD);
    }

    //处理黄牌
    if(data['yellowCard'])
    {
        var yellowCard = data['yellowCard'];
        var homeD = parseInt(yellowCard[0]);
        var awayD = parseInt(yellowCard[1]);
        $(home+'y').find('span').html(homeD);
        $(away+'y').find('span').html(awayD);
    }

    //处理角球
    if(data['corner'])
    {
        setScoreInfo(home,away,'jq',data['corner']);
    }

    //处理射门
    if(data['shootOutside'] && data['shootInside'])
    {
        var tmp = new Array;
        tmp[0] = parseInt(data['shootOutside'][0])+parseInt(data['shootInside'][0]);
        tmp[1] = parseInt(data['shootOutside'][1])+parseInt(data['shootInside'][1]);
        setScoreInfo(home,away,'sm',tmp);
    }

    //处理射中
    if(data['shootInside'])
    {
        setScoreInfo(home,away,'sz',data['shootInside']);
    }

    //处理犯规
    if(data['foul'])
    {
        setScoreInfo(home,away,'fg',data['foul']);
    }

    //处理任意球
    if(data['freeKick'])
    {
        setScoreInfo(home,away,'ryq',data['freeKick']);
    }

    //处理越位
    if(data['offside'])
    {
        setScoreInfo(home,away,'yw',data['offside']);
    }

}

//批量处理比分数据
function setScoreInfo(home,away,cKey,data)
{
    if(data[0] == 0 && data[1] == 0)
    {
        return true;
    }
    $(home+cKey).html(data[0]);
    $(away+cKey).html(data[1]);
    $('.data-'+cKey).css('background','#76b3e4');
    var tmp = parseInt(data[0])/(parseInt(data[0])+parseInt(data[1]));
    tmp = tmp.toFixed(2)*100;
    $('.data-'+cKey).find('span').css('border-right-color','#76b3e4').css('width',tmp+'%');
}

// $(".firstLi").on('click',function(){
//     if(dh_url != ''){
//         $("#iframe_box").removeClass();
//         $('#live').removeClass();
//         $("#iframe_box").addClass("dh_css");
//         $(".souBox").css('display','none');
//         var _this = $(".firstLi");
//         $("#live").attr('src',dh_url);
//         $("#live").load(function() {
//             _this.addClass('on');
//             $(".secondLi").removeClass('on');
//             $(".svglive").css('padding','46px 24px');
//         });
//     }
// });
// if(zb_url)
// {
//     if(zb_url != ''){
//         $(".secondLi").on('click',function(){
//             $(".souBox").css('display','block');
//             var _this = $(".secondLi");
//             $("#live").attr('src',zb_url);
//             video_iframe(url_type);
//             $("#live").load(function() {
//                 _this.addClass('on');
//                 $(".firstLi").removeClass('on');
//                 $(".svglive").css('padding','0px 0px');
//             });
//         });
//     }
//
// }
//直播源
// $('.souBox .souList').hover(function(e) {
//     $('.souBox .souList').addClass('animate');
// });
// $('.souBox').mouseleave(function(e) {
//     $('.souBox .souList').removeClass('animate');
// });
// $(".videourl").on('click',function(){
//     var pindao = $(this);
//     $(".videourl").children().removeClass('cur');
//     pindao.children().addClass('cur');
//     $("#live").attr('src',pindao.attr('url'));
//     video_iframe(pindao.attr('url_type'));
// });
// function video_iframe(type)
// {
//     if(type == 1)
//     {
//         $("#iframe_box").addClass("zb_css_top");
//         $("#live").addClass("zb_iframe_css_top");
//         $("#iframe_box").removeClass("zb_css");
//         $("#live").removeClass("zb_iframe_css");
//     }else{
//         $("#iframe_box").addClass("zb_css");
//         $("#live").addClass("zb_iframe_css");
//         $("#iframe_box").removeClass("zb_css_top");
//         $("#live").removeClass("zb_iframe_css_top");
//     }
// }
//视频播放
function play(video)
{
    var videoObject = {
        container: '#video', //容器的ID
        variable: 'player',
        autoplay: true, //是否自动播放
        loaded: 'loadedHandler', //当播放器加载后执行的函数
        video: video
    }
    var player = new ckplayer(videoObject);
}
//页面加载时调用,根据当前数据判断应该播放什么业务
function liveRegion()
{
    var cookieRoomId = Cookie.getCookie('nowRoomId');
    if($('li[room_id='+cookieRoomId+']').length > 0){
        liveListLiClick($('li[room_id='+cookieRoomId+']'));
    }
    changeChat(1);
    if(dh_url == ''){
        //视频直播禁止点击样式
        $('.firstLi a').css('cursor','not-allowed');
        $('.animateCon .svglive').css('padding','0');
    }
    switch(is_live){
        case 1:
        case 2:
            if(now_live == 1){
                zeroLiOnClick();
                firstLiOnClick(2);
            }else{
                zeroLiOnClick(2);
                firstLiOnClick();
            }
            break;
        case 3:
            if(now_live == 1){
                zeroLiOnClick();
                secondLiOnClick(2)
            }else{
                zeroLiOnClick(2);
                secondLiOnClick();
            }
            firstLiOnClick(2);
            break;
        default:
            firstLiOnClick();
    }
}

//视频直播加载事件
function secondLiOnClick(type = 1){
    if(type == 1){
        secondLiOnClickPack()
    }
    $('.secondLi').on('click',function(){
        secondLiOnClickPack()
    });
}
//视频直播加载事件核心方法
function secondLiOnClickPack(){
    $('.liveNav li').removeClass('on').removeClass('one');
    $('.secondLi').addClass('on');
    if(is_player == 1){
        $('#live').attr('src',zb_url);
    }else if(is_player == 2){
        play(zb_url);
    }
    $('.animateCon .svglive').css('padding','0');
    $('#iframe_box').css('display','none');
    $('#video').css('display','');
    $('.liveRegionClass').css('display','none');
    changeChat(1);
}

//动画直播加载事件
function firstLiOnClick(type = 1){
    if(type == 1){
        firstLiOnClickPack()
    }
    $('.firstLi').on('click',function(){
        firstLiOnClickPack()
    });
}
//动画直播加载事件核心方法
function firstLiOnClickPack(){
    $('.liveNav li').removeClass('on').removeClass('one');
    $('.firstLi').addClass('on');
    $('.secondLi').addClass('one');
    $('#live').attr('src',dh_url);
    $('#iframe_box').css('display','');
    $('#video').css('display','none');
    $('.animateCon .svglive').css('padding','46px 24px');
    $('.liveRegionClass').css('display','none');
    changeChat(1);
}

//美女直播加载事件
function zeroLiOnClick(type = 1){
    if(type == 1){
        playWomanLivePack()

    }
    $('.zeroLi').on('click',function(){
        playWomanLivePack()
    });
}
//美女直播加载事件核心方法
function playWomanLivePack(){
    $('.liveNav li').removeClass('on').removeClass('one');
    $('.zeroLi').addClass('on');
    $('#iframe_box').css('display','none');
    $('#video').css('display','none');
    $('.liveRegionClass').css('display','');
    $('.animateCon .svglive').css('padding','0');
    changeLive(2);
    changeChat(2);
}

//切换聊天室
function changeChat(type){
    if(type == 1){
        $('.gameRoom').css('display','');
        $('.liveRoom').css('display','none');
    }else{
        $('.liveRoom').css('display','');
        $('.gameRoom').css('display','none');
    }
}


//美女直播播放器
var livePlayer = '';
function livePlay(liveUrl,liveImg){
    //直播播放器
    var videoObject = {
        container: '#dplayer',//“#”代表容器的ID，“.”或“”代表容器的class
        variable: 'player',//该属性必需设置，值等于下面的new chplayer()的对象
        autoplay:true,//自动播放
        live:true,
        loaded: 'loadedHandler', //当播放器加载后执行的函数
        poster: liveImg, //封面图片
        video:liveUrl//视频地址
    };
    livePlayer =new ckplayer(videoObject);
}

//当视频源播放出错
function loadedHandler() {
    livePlayer.addListener('error', errorHandler); //监听元数据
}

function errorHandler() {
    $('.liveRegion>div').css('display','none');
    $('.liveError').css('display','');
}

//主播点击事件
$('#ul li').on('click',function(){
    liveListLiClick($(this))
});
//给上面绑定事件调用↑↑↑↑↑↑↑
function liveListLiClick(tmp){
    $('.liveNav li').removeClass('on').removeClass('one');
    $('.zeroLi').addClass('on');
    $('#iframe_box').css('display','none');
    $('#video').css('display','none');
    $('.liveRegionClass').css('display','');
    $('.animateCon .svglive').css('padding','0');
    $('.liveRegion>div').css('display','none');
    //播放
    $('.dplayer').css('display','');
    $('.liveRoom ul').empty();
    $('.liveChange').attr('ids',tmp.attr('ids'));
    $('.liveChange').attr('room_id',tmp.attr('room_id'));
    $('.liveChange').attr('img',tmp.attr('img'));
    $('.liveChange').attr('live_status',tmp.attr('live_status'));
    $('.liveChange').attr('nick_name',tmp.attr('nick_name'));
    $('.liveChange').attr('live_url',tmp.attr('live_url'));
    $('.liveChange').attr('mqtt_room_topic',tmp.attr('mqtt_room_topic'));
    changeLive(1);
    livePlayer.videoPlay()
    Cookie.setCookie('nowRoomId',tmp.attr('room_id'));
    $('#ipt').val(tmp.attr('nick_name'))
}

//切换主播播放
function changeLive(type){
    //查看是否有美女直播链接
    var tmp = $(".liveChange");
    var womanLive = tmp.attr('live_url');
    $('.animateCon .svglive').css('padding','0');
    // var womanLive = "http://www.flashls.org/playlists/test_001/stream_1000k_48k_640x360.m3u8";
    livePlay(womanLive);
    var topicTmp = tmp.attr('mqtt_room_topic').slice(0,tmp.attr('mqtt_room_topic').length-1);
    $('.liveTopic').val(topicTmp);
    $('.gameRoom').css('display','none');
    $('.liveRoom').css('display','');
}

//直播狀態顯示
function stopLive(data){
    $('.liveRegion>div').css('display','none');
    if(data == 1){
        //播放
        $('.dplayer').css('display','');
        livePlayer.videoPlay()
    }else{
        //暫停
        $('.quit').css('display','');
        livePlayer.videoPause()
    }
}

//主播更换关联赛事操作
function changeGame(temp){
    var data = temp['data'];
    var msg = data['msg'];
    $('.liveRegion>div').css('display','none');
    var game_id = data['game_id'];
    var html = '';
    if(temp.action == "liveSwitchGame"){
        html = temp['data']['home_name']+' <i>VS</i>' + temp['data']['away_name'];
    }else{
        html = temp['data']['title'];
    }
    $('.topmid').html(html);
    if(game_id > 0){
        $('.goWithLive').attr('href','//bf.'+DOMAIN+'/live/'+game_id+'.html?is_live=1');
    }else{
        $('.goWithLive').attr('href','//www.'+DOMAIN+'/liveRoom/'+data['room_id']+'.html');
    }
    $('.Projectile').css('display','block');
}

//更新主播播放列表
function updataLiveList(payload){
    var temp = JSON.parse(payload);
    var data = temp['data'];
    var listTmp = new Array();
    $('#ul').empty();
    var html = '';
    for(var i=0;i<data.length;i++){
        html += '<li ids="'+data[i]['id']+'" room_id="'+data[i]['room_id']+'" img="'+data[i]['img']+'" live_status="'+data[i]['live_status']+'" nick_name="'+data[i]['nick_name']+'" live_url="'+data[i]['live_url']+'" mqtt_room_topic="'+data[i]['mqtt_room_topic']+'"><a href="javascript:;">'+data[i]['nick_name']+'</a></li>';
        MqInit.subscribeTopic([data[i]['mqtt_room_topic']]);
    }
    $('#ul').append(html);
    $('#ul li').on('click',function(){
        liveListLiClick($(this))
    });
}

function delArr(arr,key){
    var tmp = new Array();

    for(var index in arr){
        if(index != key){
            tmp[index] = arr[index];
        }
    }
    return tmp;
}
$('.close-btn-close').click(function(){
    $('.Projectile').css('display','none')
})
//select 选择框
var ipt=document.getElementById('ipt');
var ul=document.getElementById('ul');
var li=ul.children;
ipt.onfocus=function(){
    ul.style.display='block';
};
ipt.onblur=function(){/*点击li(失去焦点)时触发*/
    setTimeout(function(){
        ul.style.display='none';
    },200);/*停留的时间如果过短，onclick事件无法执行，过长用户体验不好*/
}
//注册点击事件
for(var m=0;m<li.length;m++){
    li[m].onclick=function(){
        ipt.value=this.innerText;
    };
}

//获取美女直播历史消息
function getLiveHistoryChat(){
    $.ajax({
        type: 'get',
        url: '/getLiveHistoryChat.html',
        dataType: 'json',
        data: {
            room_id: $('.liveTopic').val()
        },
        success: function (data) {
            if(data.code == 200){
                var res = data.data;
                for(var i = 0;i<res.length;i++){
                    onChat(res[i],'.liveRoom');
                }
            }
        }
    })
}
