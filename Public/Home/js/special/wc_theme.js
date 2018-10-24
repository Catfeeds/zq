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
    $('.banner01').hover(function(e) {
        $('.banner01 .carousel-control').stop().fadeIn(500);
    },function(){
        $('.banner01 .carousel-control').stop().fadeOut(500);
    });
    $('.banner01 .carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });

    //翻页
    $(document).on('click', '.schNav ul li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        var type = $(this).attr('data-type');
        $("div[id$='-schedule']").css('display', 'none');
        $('#' + type + '-schedule').css('display', 'block');
    })
});