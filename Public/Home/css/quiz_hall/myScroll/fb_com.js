/**
 * 全彩竞猜大厅首页js样式文件
 * 
 * @author Chensiren <245017279@qq.com>
 *
 * @since  2016-03-01
**/
$(function(){
	(function($){
		$(window).load(function(){
			$("#content-1").mCustomScrollbar({
				theme:"minimal"
			});
			
		});
	})(jQuery);
	//左边滚动
	$(window).scroll(function(){
		var myheight = $('.main-box').offset().top;
		var mytop = $(window).scrollTop();
		var mydif =  mytop - myheight;
		var myWindowH = $(window).height();
		if(myheight >= mytop){
			$('.box-left').stop().animate({'top':'0px'},0);
		}else{
			$('.box-left').stop().animate({'top':''+mydif+'px'},100);
		};
		
	});
	
	$(window).resize(function(e) {
		var myWindowH = $(window).height()-430;
		$('.tab-con').css('height',''+myWindowH+'px');
	});
});

		