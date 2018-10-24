/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 * 
 * @since  2018-01-10
 *
**/


   
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
	$("#expScroll").als({
		visible_items: 4,
		scrolling_items: 1,
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

	var length = $("input[name=data-length]").val();
	for (var i =0; i<=length; i++) {
        circlePercent(".left-circle"+i, $('.left-circle'+i+' .left-circle'+i+'-span').html());
    }

    //主页面数据
    var left_circle0 = $('.left-circle0 .left-circle0-span').html();
    var left_circle1 = $('.left-circle1 .left-circle1-span').html();
    var left_circle2 = $('.left-circle2 .left-circle2-span').html();
    var right_circle0 = $('.right-circle0 .right-circle0-span').html();
    var right_circle1 = $('.right-circle1 .right-circle1-span').html();
    var right_circle2 = $('.right-circle2 .right-circle2-span').html();
    circlePercent(".left-circle0", left_circle0);
    circlePercent(".left-circle1", left_circle1);
    circlePercent(".left-circle2", left_circle2);
    circlePercent(".right-circle0", right_circle0);
    circlePercent(".right-circle1", right_circle1);
    circlePercent(".right-circle2", right_circle2);
});