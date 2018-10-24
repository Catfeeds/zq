/**
 * Created by cytusc on 2018/8/16.
 */
//滚动条控制
(function($) {
    $(window).load(function() {
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

        //下滑事件
        // $(".conChatList").mCustomScrollbar("scrollTo","bottom",{
        //     scrollInertia: 500
        // });
        getLiveHistoryChat()
        sayHello();
    });
})(jQuery);

window.onload = function(){
    startLiveBanner();
}


$(function(){
    $('.indentBtn').click(function(e) {
        if($(this).hasClass('on')){
            $(this).removeClass('on');
            $('.hotLiveBox').stop().animate({'left':'-214px','z-indent':'1'},500)
        } else{
            $(this).addClass('on');
            $('.hotLiveBox').stop().animate({'left':'0','z-indent':'-1'},500)
        }
    });

    //高手推荐tab
    $('.navTab ul li').click(function(e) {
        var num = $(this).index();
        $(this).addClass('on').siblings().removeClass('on');
        $('.shotBox ul').eq(num).show().siblings().hide();
    });
    //Marquee通告
    $('.noticeRight').kxbdSuperMarquee({
        isMarquee:true,
        isEqual:false,
        scrollDelay:30,
        controlBtn:{up:'#goUM',down:'#goDM'},
        direction:'left'
    });
    //关闭app二维码
    $('.closeApp').click(function(e) {
        $('.appEw').hide();
    })
    $('.textarea-box textarea').html(document.location.href)

});

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


//直播播放器
var videoObject = {
    container: '#dplayer',//“#”代表容器的ID，“.”或“”代表容器的class
    variable: 'player',//该属性必需设置，值等于下面的new chplayer()的对象
    autoplay:true,//自动播放
    live:is_live,
    loaded: 'loadedHandler', //当播放器加载后执行的函数
    poster: liveImg, //封面图片
    video:liveUrl//视频地址
};
var player=new ckplayer(videoObject);

//当视频源播放出错
function loadedHandler() {
    player.addListener('error', errorHandler); //监听元数据
}

function errorHandler() {
    $('.liveRegion>div').css('display','none');
    $('.liveError').css('display','');
}



//發送彈幕
function sendDanmu(text){
    if(!$('.dplayer').is(':visible') || liveUrl == ''){
        return true;
    }
    var Range = 80 - 1;
    var Rand = Math.random();
    var _y = 1 + Math.round(Rand * Range);

    //弹幕说明
    var danmuObj = {
        list: [{
            type: 'text', //说明是文本
            text: text, //文本内容
            color: '#FFF', //文本颜色
            size: 20, //文本字体大小，单位：px
            font: '"Microsoft YaHei", YaHei, "微软雅黑", SimHei,"\5FAE\8F6F\96C5\9ED1", "黑体",Arial', //文本字体
            leading: 30, //文字行距
            alpha: 1, //文本透明度(0-1)
            paddingLeft: 10, //文本内左边距离
            paddingRight: 10, //文本内右边距离
            paddingTop: 0, //文本内上边的距离
            paddingBottom: 0, //文本内下边的距离
            marginLeft: 0, //文本离左边的距离
            marginRight: 10, //文本离右边的距离
            marginTop: 10, //文本离上边的距离
            marginBottom: 0, //文本离下边的距离
            // backgroundColor: '#FFF', //文本的背景颜色
            backAlpha: 0.5, //文本的背景透明度(0-1)
            backRadius: 30, //文本的背景圆角弧度
            clickEvent: "actionScript->videoPlay"
        }],
        // x: '100%', //x轴坐标
        y: _y+"%", //y轴坐标
        time:20,
        //position:[2,1,0],//位置[x轴对齐方式（0=左，1=中，2=右），y轴对齐方式（0=上，1=中，2=下），x轴偏移量（不填写或null则自动判断，第一个值为0=紧贴左边，1=中间对齐，2=贴合右边），y轴偏移量（不填写或null则自动判断，0=紧贴上方，1=中间对齐，2=紧贴下方）]
        alpha: 1,
        //backgroundColor:'#FFFFFF',
        backAlpha: 0.8,
        backRadius: 30 //背景圆角弧度
    }
    var danmu = player.addElement(danmuObj);
    var danmuS = player.getElement(danmu);
    var obj = {
        element: danmu,
        parameter: 'x',
        static: true, //是否禁止其它属性，true=是，即当x(y)(alpha)变化时，y(x)(x,y)在播放器尺寸变化时不允许变化
        effect: 'None.easeOut',
        start: null,
        end: -danmuS['width'],
        speed: 10,
        overStop: true,
        pauseStop: true,
        callBack: 'deleteChild'
    };
    var danmuAnimate = player.animate(obj);
}

//接收数据处理
MqInit.onMessage(function (topic, message) {
    try {
        var tp = topic;
        if (tp.indexOf('/chat') > -1) {//接受聊天室信息
            var temp = JSON.parse(message);
            console.log(temp)
            switch(temp.action){
                //暂停
                case 'livePause':
                    stopLive(2);
                    break;
                //继续
                case 'liveContinue':
                    stopLive(1);
                    break;
                //文字广告
                case 'liveNotice':
                    liveNotice(temp);
                    break;
                //主播切换场次
                case 'liveSwitchGame':
                case 'liveCancelGameLink':
                    changeGame(temp);
                    break;
                default:
                    //发言
                    receiveMsg(temp);
            }
        }else if (tp.indexOf('/stopLive') > -1) {//視頻直播狀態
            stopLive(message);
        }else if (tp.indexOf('/changeGame') > -1) {//視頻賽事切換
            changeGame(message);
        }
    } catch (e) {
        console.log(e);
    }
}, [topic]);

//直播狀態顯示
function stopLive(data){
    $('.liveRegion>div').css('display','none');
    if(data == 1){
        //播放
        $('.dplayer').css('display','');
        player.videoPlay()
    }else{
        //暫停
        $('.quit').css('display','');
        player.videoPause()
    }
}


//接受用戶聊天消息
function receiveMsg(temp){
    var data = chatSend = temp['data'];
    var content = data['content'];

    var nickStyle = '';
    var report = '';

    if ((temp.action == 'say' || temp.action == 'sayHello') && temp.dataType == 'text') {
        if($('.hints').is(':visible')){
            // $('.hints').css('display','none');
        }
        var lv = '';
        if(parseInt(data['lv']) > 0){
            lv = parseInt(data['lv']);
        }
        if(temp['action'] == 'sayHello'){
            content = '进入直播间';
        }else{
            // sendDanmu(content);
        }

        if (isAdmin == 1) {
            report = '<a class="reportBtn" onclick="report(1,chatSend)">屏蔽用户</a><a class="reportBtn" onclick="report(3,chatSend)">踢出</a>';
        }

        if (userInfo && data.user_id == userInfo.user_id) {
            nickStyle = 'style="color:#04AF77"'
        } else {
            nickStyle = 'style="color:#6daade"';
        }
        var c = jEmoji.unifiedToHTML(content);
        var chatRowId = "chat-row-" + data.user_id + data.msg_id;

        var html = '<li class="start clearfix" id=' + chatRowId + '>'+
            '<span class="live-lb initial pull-left">'+
            '<img src="/Public/Home/images/LiveRoom/ic_0'+lv+'.png">'+
            '</span>'+
            '<span class="name pull-left" ' + nickStyle + '>'+
            data['nick_name']+
            '<span class="colon">：</span>'+
            '<span class="shield-report">' + report + '<a class="reportBtn" onclick="report(2,chatSend)">举报</a></span></span>'+
            '<span class="shield-report">'+
            report+
            '</span>'+
            '</span>'+
            '<span class="content-txt">'+c+'</span>'+
            '</li>';
        $('#chatList').append(html);

        var is_gundong = 1;
        if($('#chatList li').length > 30){
            var remindTop = $('.numRemind').offset().top;
            var lastLiTop = $('#chatList li').eq(-5).offset().top;
            if(lastLiTop > remindTop){
                is_gundong = 0;
            }
        }

        if(is_gundong){
            $(".conChatList").mCustomScrollbar("scrollTo", "bottom", {
                scrollInertia: 10
            });
            $('.information span').html('0');
            $('.information').css('display','none');
        }else{
            var newNum  = parseInt($('.information span').html());
            newNum = newNum+1;
            $('.information span').html(newNum);
            $('.information').css('display','block');
        }



        //判断新插入聊天是否
        //获取列表区域高度
        // var regionHeight = $('#mCSB_2').height();
        // //获取消息列表总高度
        // var listHeight = $('#chatList').height();
        // //获取当前显示区域的位置
        // var nowTop = $('#mCSB_2_container').position().top * -1;
        // console.log((listHeight - regionHeight - 10) > nowTop,listHeight - regionHeight - 10)
        // if((listHeight - regionHeight - 10) > nowTop){

        // }else{
        //     var nowtop = listHeight - regionHeight * -1;
        //     $('#mCSB_2_container').css('top',nowtop+'px');
        // }

    } else if (temp.action == 'kickout' || temp.action == 'forbid' || temp.action == 'sensitiveSay') {
        var chatRowId = "chat-row-" + temp.data.user_id;
        $('li[id^=' + chatRowId + ']').each(function () {
            $(this).remove();
        });

        if(userInfo && temp.data.user_id == userInfo.user_id){
            _alert('系统提示', temp.data.notice_str);
        }
        if(temp.action == 'kickout' || temp.action == 'forbid'){
            window.location.href = '//www.'+DOMAIN+'/User/logout.html';
        }
    }else if(temp.action == 'timeLimit' && temp.data.user_id == userInfo.user_id){
        _alert('系统提示', temp.data.notice_str);
    }

}

//点击消息数量跳转 至最新消息
$('.information').on('click',function(){
    $(".conChatList").mCustomScrollbar("scrollTo", "bottom", {
        scrollInertia: 10
    });
    $('.information span').html('0');
    $('.information').css('display','none');
})

/**
 * 清空聊天记录
 */
function clearChatLog() {
    _confirm('提示', '确定要清屏吗', function (rs) {
        if (rs) $("#chatList").empty();
    });
}

//主播更换关联赛事操作
function changeGame(temp){
    $('.liveRegion>div').css('display','none');
    // var temp = JSON.parse(payload);
    var html = '';
    if(temp.action == "liveSwitchGame"){
        html = temp['data']['home_name']+' <i>VS</i>' + temp['data']['away_name'];
        $('.topbut a').attr('href','//bf.'+DOMAIN+'/live/'+temp['data']['game_id']+'.html?is_live=1');
        $('.topmid').html(html);
        $('.Projectile').css('display','block');
    }
}


$(".btnPost").click(function () {
    say();
});

/**
 * 发言
 */

function say(){

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
        modalLogin();
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
                room_type: 2,
                chat_time: Date.parse(new Date()) / 1000,
                content: removeHTMLTag(content),
                msg_id: msg_id,
                ip:ip
            },
            status: 1
        };
        var jsonStr = JSON.stringify(payload);
        MqInit.publishToTopic('qqty/live_' + roomId + '/chat', jsonStr);
    }
    $('#chatTxt').empty();
    $('#chatTxt').focus();
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

function removeHTMLTag(str) {
    str = str.replace(/<\/?[^>]*>/g,''); //去除HTML tag
    str = str.replace(/[ | ]*\n/g,'\n'); //去除行尾空白
    str=str.replace(/ /ig,'');//去掉
    str=str.replace(/&nbsp;/g,' ');//去掉
    return str;
}

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
// $('.share').on('click',function(){
//     if($('.share-box').css('display') == 'none'){
//         $('.share-box').css('display','block');
//     }else{
//         $('.share-box').css('display','none');
//     }
// })

//分享的功能
var myBtn  = document.getElementsByClassName('share')[0];
var myDiv = document.getElementsByClassName('share-box')[0];

myBtn.onmouseover = function(){
    $('.qqty-wx').html("");
    var val = myDiv.style.display;
    if(val == 'none'){
        myDiv.style.display = 'block'; //显示
        $('.app-load').css('display','none');
        //获取放置微信二维码的DIV
        var content = document.getElementsByClassName("qqty-wx")[0];
        //设置属性
        var qrcode = new QRCode(content, {
            width: 200,
            height: 200
        });
        //设置二维码内容
        var defaultContent = document.location.href;
        qrcode.makeCode(defaultContent);

        event.stopPropagation();
    }else{
        myDiv.style.display = 'none'; //隐藏
        event.stopPropagation();
    }

}
$('.share-box').mouseover(function(){
    event.stopPropagation();
})

//用手机看
var loadBtn  = document.getElementsByClassName('go-load')[0];
var myload = document.getElementsByClassName('app-load')[0];
loadBtn.onmouseover = function(){
    var val = myload.style.display;
    if(val == 'none'){
        myload.style.display = 'block'; //显示
        event.stopPropagation();
        $('.share-box').css('display','none');
    }else{
        myload.style.display = 'none'; //隐藏
        event.stopPropagation();
    }

}
$('.app-load').mouseover(function(){
    event.stopPropagation();
})
function getCode(id) {
    var _dom = document.getElementById(id);
    var content = _dom.innerHTML || _dom.value;
    // 复制内容
    _dom.select();
    // 将内容复制到剪贴板
    document.execCommand("copy");
}
// 分享功能
function shareTo(stype){
    var ftit = '';
    var flink = '';
    var lk = '';
    //获取文章标题
    // ftit = $('.pctitle').text();
    ftit= shareTitle;
    //获取网页中内容的第一张图片
    flink = $('.portrait img').eq(0).attr('src');

    var shareImg=$('.lazy').attr('src');
    if(typeof flink == 'undefined'){
        flink='';
    }
    //当内容中没有图片时，设置分享图片为网站logo
    if(flink == ''){
        lk = 'http://'+window.location.host+'/static/images/logo.png';
    }
    //如果是上传的图片则进行绝对路径拼接
    if(flink.indexOf('/uploads/') != -1) {
        lk = 'http://'+window.location.host+flink;
    }
    //百度编辑器自带图片获取
    if(flink.indexOf('ueditor') != -1){
        lk = flink;
    }
    //qq空间接口的传参
    if(stype=='qzone'){
        window.open('https://sns.qzone.qq.com/cgi-bin/qzshare/cgi_qzshare_onekey?url='+document.location.href+'?sharesource=qzone&title='+ftit+'&pics='+lk+'&summary='+document.querySelector('meta[name="description"]').getAttribute('content'));
    }
    //新浪微博接口的传参
    if(stype=='sina'){
        window.open('http://service.weibo.com/share/share.php?url='+document.location.href+'?sharesource=weibo&title='+ftit+'&pic='+lk+'&appkey=2706825840');
    }
    //qq好友接口的传参
    if(stype == 'qq'){
        // window.open('http://connect.qq.com/widget/shareqq/index.html?url='+document.location.href+'?sharesource=qzone&title='+'&pics='+lk+'&summary='+document.querySelector('meta[name="description"]').getAttribute('content')+'&desc='+ftit);
        var p = {
            url : document.location.href, /*获取URL，可加上来自分享到QQ标识，方便统计*/
            title : ftit, /*分享标题(可选)*/
            summary : document.querySelector('meta[name="description"]').getAttribute('content'), /*分享摘要(可选)*/
            pics : shareImg, /*分享图片(可选)*/
            flash : '', /*视频地址(可选)*/
            site : document.location.href, /*分享来源(可选) 如：QQ分享*/
            style : '201',
            width : 32,
            height : 32
        };
        var s = [];
        for ( var i in p) {
            s.push(i + '=' + encodeURIComponent(p[i] || ''));
        }
       console.log(s.join('&'))
        var url = "http://connect.qq.com/widget/shareqq/index.html?"+s.join('&');
        window.open(url)
    }

}

function qqFriend() {
    var p = {
        url : 'http://www.junlenet.com', /*获取URL，可加上来自分享到QQ标识，方便统计*/
        desc:'',
        //title : '新玩法，再不来你就out了！', /*分享标题(可选)*/
        title:desc_,
        summary : '', /*分享摘要(可选)*/
        pics : 'http://www.junlenet.com/uploads/allimg/150510/1-150510104044.jpg', /*分享图片(可选)*/
        flash : '', /*视频地址(可选)*/
        site : 'http://www.junlenet.com', /*分享来源(可选) 如：QQ分享*/
        style : '201',
        width : 32,
        height : 32
    };
    var s = [];
    for ( var i in p) {
        s.push(i + '=' + encodeURIComponent(p[i] || ''));
    }
    var url = "http://connect.qq.com/widget/shareqq/index.html?"+s.join('&');
    return url;
    //window.location.href = url;
    //document.write(['<a class="qcShareQQDiv" href="http://connect.qq.com/widget/shareqq/index.html?',s.join('&'), '" >分享给QQ好友</a>' ].join(''));
}
//联系客服
function goUrl(){
    var data = '';
    if(userInfo['username'] > 0){
        data = '?tel='+userInfo['username'];
    }
    url='http://m.customer.qqty.com/#/m/online'+data,
        window.open(url,'','width=610,height=760,left='+($(document).width() - 610 )/2+',top=100,resizable=yes,menubar=no,location=no,status=yes,scrollbars=yes');
}

//文字广告滚动
function liveNotice(data){
    $('.liveBanner').html(data.data.notice_str);
    rollType = 2;
    startLiveBanner();
}
var hints=setInterval("hint()",1000)
var num3=3;
function hint(){
    console.log(num3)
    if(num3>0){
        num3--;
    }else{
        $('.hints').css('display','none')
    }

}
// var cars = $("#tgGoox").width();
// var startTime=setInterval("start()",12);
// var startTimes=setInterval("starts()",12);
// var i =0;
// var num=6*cars;
// var car;
// //文字广告滚动
// function liveNotice(data){
//   
//     $('.marquee').remove();
//     clearInterval(startTime);
//     $('#tgGoox').css('left','0');
//     $('#tgGoox').css('right','0');
//     var ele='<span class="marquee" style="overflow: hidden;">'+data['data']['notice_str']+'</span>'
//     var eles='<span>'+data['data']['notice_str']+'</span>'
//      $('#m-n').append(eles);
//      car= $("#m-n").width();
//     $('#tgGoox').append(ele);
//     cars = $("#tgGoox").width();
//     var i =0;
//     num=6*car;
// }
function start(data){

    // num > 0 ?num--: clearInterval(startTime)
     i--;
    if(i<=-cars){
        i=1*cars;
       document.getElementById('tgGoox').style.right =-cars+'px';
    //    document.getElementById('tgGoox').style.left =-i+'px';
    }else{
         document.getElementById('tgGoox').style.left =i+'px';
    }
    //  setTimeout(start,10);

}
function starts(){
 
    if(num > 1.2*car){
        num--
    }else{
        clearInterval(startTimes)
        $('#tgGoox').empty();
        var ele='<span class="marquee" style="overflow: hidden;">'+adConten+'</span>'
        $('#tgGoox').append(ele);
        startTime=setInterval("start()",12)
    }
    i--;
    if(i<=-cars){
        i=1*cars;
        document.getElementById('tgGoox').style.right =-cars+'px';
    }else{
        document.getElementById('tgGoox').style.left =i+'px';
    }
    //  setTimeout(start,10);

}



// $('#img').click(function(){
//     $('marquee').remove();
//     var ele = '<marquee direction="left" behavior="slide" scrollamount="10" scrolldelay="0" loop="2" width="1090" height="40" line-height="40" hspace="10" vspace="10" onMouseOut="this.start()" onMouseOver="this.stop()" style="margin: -40px 0 40px 38px;"class="marquee">欢迎各位老铁光临全球体育直播间</marquee>'
//     $('#reminder').append(ele);
// });

function report(type, data) {
    if (data) {
        $.ajax({
            type: 'POST',
            url: '/Score/forbid.html',
            dataType: 'json',
            data: {
                room_id: roomId,
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

//进入直播间
function sayHello(){
    if(userInfo.user_id > 0){
        var msg_id = uuid();
        var payload = {
            action: 'sayHello',
            dataType: 'text',
            data: {
                user_id: userInfo.user_id,
                nick_name: userInfo.nick_name,
                head: userInfo.head,
                lv: userInfo.lv,
                lv_bet: userInfo.lv_bet,
                lv_bk: userInfo.lv_bk,
                room_type: 2,
                chat_time: Date.parse(new Date()) / 1000,
                content: '进入直播间',
                msg_id: msg_id,
                ip:ip
            },
            status: 1
        };
        console.log(payload);
        var jsonStr = JSON.stringify(payload);
        MqInit.publishToTopic('qqty/live_' + roomId + '/chat', jsonStr);
    }
}

//获取美女直播历史消息
function getLiveHistoryChat(){
    $.ajax({
        type: 'get',
        url: '/Score/getLiveHistoryChat.html',
        dataType: 'json',
        data: {
            room_id: roomId
        },
        success: function (data) {
            if(data.code == 200){
                var res = data.data;
                for(var i = 0;i<res.length;i++){
                    var temp = JSON.parse(res[i]);
                    receiveMsg(temp);
                }
            }
        }
    })
}
var adInterval = '';//定义定时器
var adNum = 1;//定时器循环次数初始值
var rollType = 1;
function startLiveBanner(){
    adNum = 1;
    window.clearInterval(adInterval);
    $('.liveBanner').css('margin-left','1050px');
    adInterval=window.setInterval(liveBannerRoll, 5);
}

//直播文字滚动事件
function liveBannerRoll(){
    //当前文字区域所占宽度
    var nowWidth = $('.liveBanner').width() * -1;
    var marginLeft = $('.liveBanner').css('margin-left');
    var roll = parseInt(marginLeft);
    if(roll > nowWidth){
        $('.liveBanner').css('margin-left',(roll-1)+'px');
    }else{
        if(adNum < 5 || rollType == 1){
            adNum = adNum+1;
            $('.liveBanner').css('margin-left','1050px');
        }else{
            // $('.liveBanner').css('margin-left','0px');
            adNum = 1;
            // window.clearInterval(adInterval);
            //还原公告
            $('.liveBanner').html(adConten);
            rollType = 1;
        }
    }
}