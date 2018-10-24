/**
 * Created by cytusc on 2018/8/15.
 */
$(function(){
    getLiveHistoryChat();
});

var videoObject = {
    container: '#dplayer',//“#”代表容器的ID，“.”或“”代表容器的class
    variable: 'player',//该属性必需设置，值等于下面的new chplayer()的对象
    autoplay:true,//自动播放
    live:true,
    poster: liveImg, //封面图片
    mobileCkControls:true,//是否在移动端（包括ios）环境中显示控制栏
    video:liveUrl//视频地址
};
var player=new ckplayer(videoObject);
var myVideo = document.getElementsByTagName('video')[0];
$(document).ready(
    function() {
        $("#publicChat").niceScroll({background:"#454646", cursoropacitymin: 0,   cursoropacitymax: 1, cursorwidth: "-10px",  cursorborder: "1px solid #454646",});
        // $("#publicChat").getNiceScroll(0).doScrollTop(2000000, 10); // Scroll Y Axis
        if(liveStart == 2){
            player.videoPause()
        }else{
            player.videoPlay()
        }
    }
);

$('#messageEditor').click(function(){
    $('.mask').css('display','block');
    myVideo.pause();
    $('#dplayer').css('display','none')
    $('.video-box img').css('display','block');
})
$('.close-btn').click(function(){
    $('.mask').css('display','none')
    $('#dplayer').css('display','block')
    $('.video-box img').css('display','none');
})

$('#mvMask').click(function(){
    $('#dplayer').css('display','block')
    $('.video-box img').css('display','none');
    // dp.play();
})
//滚动到最下面
var headHeight = document.getElementById('#head_1').offsetHeight,
    footerHeight = $('#footer').height(),
    bodyH = document.body.offsetHeight;
$("#sendBtn").click(function(){MsgSend();});
$('#publicChat').height(bodyH - headHeight - footerHeight);
// $('#aa').click(function(){
//     var div = document.getElementById('publicChat');
//     var divheight=$('#publicChat').height();
//     $('#publicChat').height(divheight-70);
//      div.scrollTop = div.scrollHeight+400;
// })

//接收数据处理
MqInit.onMessage(function (topic, message) {
    try {
        var tp = topic;
        if (tp.indexOf('/chat') > -1) {//接受聊天室信息
            var temp = JSON.parse(message);
            switch(temp.action){
                //发言
                case 'say':
                case 'sayHello':
                    showMsg(message);
                    break;
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
                    changeGame(message);
                    break;
            }
        }
    } catch (e) {
        console.log(e);
    }
}, topic);

//接受用戶聊天消息
function showMsg(payload){
    var temp = JSON.parse(payload);
    var data = temp['data'];
    var content = data['content'];
    var lv = '';
    if(parseInt(data['lv']) > 0){
        lv = parseInt(data['lv']);
    }
    if(temp['action'] == 'sayHello'){
        content = '进入直播间';
    }
    var html = '<div class="chat">' +
        '<img src="/Public/Mobile/images/LiveRoom/ic_0'+lv+'.png" class="icon">' +
        '<span class="name">'+data['nick_name']+'</span>' +
        '<span class="come">'+content+'</span>' +
        '</div>';
    //隱藏提示
    if($(".remind").is(':visible')){
        // var remindHeight = $('.remind').outerHeight();
        // var remindoutHeight = $('.remind').outerHeight(true);
        // var remindmarTop = remindoutHeight - remindHeight;
        // var publicChatHeight = $('#publicChat').height()+remindHeight;
        // $('#publicChat').css('height',publicChatHeight+'px').css('margin-top',remindmarTop+'px');
        // $('.remind').css('display','none');
    }
    var hints=setInterval(hind,1000);

    var num2=3;
    function hind(){
        if(num2>0){
            num2--;
        }else{
        //       var remindHeight = $('.remind').outerHeight();
        // var remindoutHeight = $('.remind').outerHeight(true);
        // var remindmarTop = remindoutHeight - remindHeight;
        // var publicChatHeight = $('#publicChat').height()+remindHeight;
        // $('#publicChat').css('height',publicChatHeight+'px').css('margin-top',remindmarTop+'px');
            $(".remind").css('display','none');
            clearInterval(hints);
        }
    }
       $('#publicChat').append(html);
       //滑动到最下面
        var div = document.getElementById('publicChat');
        // var divheight=$('#publicChat').height();
        // console.log(divheight)
        // $('#publicChat').height(divheight-70);
         div.scrollTop = div.scrollHeight+400;
}

//直播狀態顯示
function stopLive(data){
    if(data == 1){
        $('#dplayer').css('display','');
        $('#pause-two').css('display','none');
        $('#pause-three').css('display','none');
        player.videoPlay()
    }else{
        $('#dplayer').css('display','none');
        $('#pause-two').css('display','');
        $('#pause-three').css('display','none');
        player.videoPause()
    }
}

//主播更换关联赛事操作
function changeGame(payload){
    var temp = JSON.parse(payload);
    var data = temp['data'];
    var html = '';
    if(temp.action == "liveSwitchGame"){
        html = temp['data']['home_name']+' <i>VS</i>' + temp['data']['away_name'];
    }else{
        html = temp['data']['title'];
    }
    $('.troops').html(html);
    $('.change').css('display','block');

}

//获取美女直播历史消息
function getLiveHistoryChat(){
    $.ajax({
        type: 'get',
        url: '/MLiveRoom/getLiveHistoryChat.html',
        dataType: 'json',
        data: {
            room_id: topic
        },
        success: function (data) {
            if(data.code == 200){
                var res = data.data;
                for(var i = 0;i<res.length;i++){
                    showMsg(res[i]);
                }
            }
        }
    })
}