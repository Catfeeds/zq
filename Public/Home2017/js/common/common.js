/**
 * 首页2017 js修订版
 * @author Chensiren <245017279@qq.com>
 * @since  2015-12-01
*/
$(function(){
	//nav
	$('.nav-con .nav li').hover(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
		var navLiNum = $(this).index();
		$('.navList ul').eq(navLiNum).show().siblings().hide();
    });
    //banner
    $('.focus-banner').hover(function(e) {
        $('.carousel-control').stop().fadeIn(500);
    },function(){
        $('.carousel-control').stop().fadeOut(500);
    });
    $('.carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });


    //滚动条
    $(".tabList").niceScroll({
        cursorcolor: "#10af63",
        cursorborder: "1px solid #10af63",
        horizrailenabled: false,
        cursorfixedheight: 50,
    });


    // 动画效果 CSS3
    $('body').on('inview', '[data-animation]', function(){
        var $this = $(this);

        var animations = $this.data('animation');
        // 去掉所有空格
        animations = animations.replace(/\s+/g, '');
        // 拆分为数组
        animations = animations.split(',');
        // 添加首元素
        animations.unshift('animation');
        // 合并为字符串 "animation-animation1-animation2-..."
        animations = animations.join('-');

        var percent = $this.data('percent');

        $this.addClass(animations).css('width', percent);
    });
});