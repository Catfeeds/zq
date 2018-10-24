/**
 * media number js
 *
 * @author Chensiren <245017279@qq.com>
 * 
 * @since  2018-02-01
 *
**/

$(function(){
	var status = $("#status").val();
    $('.formBox .formTab').eq(status).show().siblings('form.formTab').hide();
    $('.formNav li').eq(status).addClass("on").siblings().removeClass("on");
	$('.formNav li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
		var num = $(this).index();
		$('.formBox .formTab').eq(num).show().siblings('form.formTab').hide();
    });
})