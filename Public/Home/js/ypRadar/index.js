/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 * 
 * @since  2018-01-10
 *
**/
var domain = config.domain;
$(function(){
	//banner
    $('.banner').hover(function(e) {
        $('.carousel-control').stop().fadeIn(500);
    },function(){
        $('.carousel-control').stop().fadeOut(500);
    });
    $('.carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });
	
    //特约列表
    $("#Scroll").als({
        visible_items: 4,
        scrolling_items: 4,
        orientation: "horizontal",
        circular: "yes",
        autoscroll: "yes",
        interval: 5000,
        speed: 500,
        easing: "linear",
        direction: "left",
        start_from: 0
    });
	//特约列表
	$("#expScroll").als({
		visible_items: 4,
		scrolling_items: 4,
		orientation: "horizontal",
		circular: "yes",
		autoscroll: "yes",
		interval: 5000,
		speed: 500,
		easing: "linear",
		direction: "left",
		start_from: 0
	});
	
	//circlePercent
	function circlePercent(circleClass, num){
		if(num>100)return;
		$(circleClass + " span").html(num);
		num=num*3.6;
		if(num<=180){
		  $(circleClass + " .pieRightIn").css({"transform":"rotate(" + num + "deg)"});
			  }else{
		  $(circleClass + " .pieRightIn").css({"transform":"rotate(180deg)"});
		  $(circleClass + " .pieLeftIn").css({"transform":"rotate(" + (num - 180) + "deg)"});
		}          
	  }
	  var diff = $("input[name='dif']").val() ? $("input[name='dif']").val() : 0;
       circlePercent(".circleRight", diff);
});

//也可以动态添加topic MqInit.subscribeTopic(['aa/bb']);
MqInit.onMessage(function (topic, message) {
    var data = message;
    if(topic.indexOf('qqty/live/notify') > -1) {
        var temp = JSON.parse(message);
        switch(temp.action){
            //主播修改状态
            case 'liveStatusChange':
                liveStatusChange(temp);
                break;
            //主播切换赛事
            case 'liveSwitchGame':
                liveSwitchGame(temp);
                break;
            //主播取消赛事关联
            case 'liveCancelGameLink':
                liveCancelGameLink(temp);
                break;
        }
    }
}, ['qqty/live/notify']);

//主播修改状态
function liveStatusChange(data) {
	var status = data.data.live_status;
	if(status < 1){
		var html = '<i style=" background:#efa658;display: inline-block;width:100%;height:100%;font-style: normal;color:#fbebdd">回播<img src="/Public/Home/images/ic_rk_hf.png" style="width: 10px; height: auto; margin-left: 2px;"></i>';
		$("li[room_id="+data.data.room_id+"]").find('.add').html(html);
	}
}

//主播切换赛事
function liveSwitchGame(data){
	var url = '//bf.'+domain+'/live/'+data.data.game_id+'.html?is_live=1';
    $("li[room_id="+data.data.room_id+"]").find('a').attr('href',url);
}

//主播取消赛事关联
function liveCancelGameLink(data){
    var url = '//www.'+domain+'/liveRoom/'+data.data.room_id+'.html';
    $("li[room_id="+data.data.room_id+"]").find('a').attr('href',url);
}