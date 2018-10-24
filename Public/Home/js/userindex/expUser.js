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
	circlePercent(".circleLeft", 20);
	circlePercent(".circleRight", 68);
	
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
})
var _class = 'zxDiv';
$('.navTitle li').on('click',function(){
	$('.navTitle li').removeClass('active')
	$(this).addClass('active');
	$('.expLiBox').css('display','none');
	_class = $(this).attr('sonDiv');
	$('.'+_class).css('display','');
});
$('.listLoad').on('click',function(){ajaxList()});


//ajax加载文章列表
function ajaxList(pageNum)
{
    $('.'+_class+' ul').html('');
    $('.P'+_class).html('正在加载数据!!');
	var type = $('.active').attr('val');
	$.ajax({
		type: "POST",
		url: "/UserIndex/ajaxNewList.html",
		data: {user_id:user_id,tme:time,page:pageNum,classType:type},
		dataType: "json",
		async:false,
		success: function(data){
			if(data.info.html.length > 0)
			{
				if(data.info.html == '')
				{
					$('.'+_class+' ul').html('');
					$('.P'+_class).html('无更多数据!!');
				}else{
					$('.'+_class+' ul').html(data.info.html);
					$('.P'+_class).html(data.info.page);
				}
			}
		},
	});
}

$(document).on('click', '.page-con ul li', function () {
	var This = $(this);
	var _goPage = $('.isTxtBig').val();
	var page = '';
	if ($(this).attr("id") == 'GO') {
		if(isNaN(parseInt(_goPage)) || parseInt(_goPage) < 1)
		{
			page = 1;
		}else {
			page = _goPage;
		}
	}else{
		page = This.attr('page');//第二页开始
	}
	if(page === undefined)
	{
		return true;
	}
	ajaxList(page - 1);
	$('html, body').animate({
		scrollTop: $('.navList').offset().top
	}, 500);
});


var FollowUrl =  document.domain.replace('www.','').split(".").length-1 > 1 ? '' : '/Common';
//关注
$('.follow').on('click',function(){
    var id = $('.follow').attr('val');
    //判断登录
    var is_login = $("input[name='userId']").val();
    if (is_login == '')
    {
		modalLogin();
    }
    else
    {
		if($(this).children('span').hasClass('added'))
		{
			$('.follow span').addClass('plus').addClass('added').html('已关注');
			$('.is_follow').unbind('mouseover').unbind('mouseout');
			$.ajax({
				type:"post",
				url : FollowUrl+"/cancelFollow.html",
				data:{'id':id},
				dataType:'json',
				success: function(msg){
					if(msg.status==1){
						$('.follow span').removeClass('added').addClass('add').html('关注');
						layer.msg(msg.info);
						$('.fansNum strong').html(parseInt($('.fansNum strong').html()) - 1)
					}else{
						layer.msg(msg.info);
					}
				}
			});
		}else{
			$.ajax({
				type:"post",
				url : FollowUrl+"/addFollow.html",
				data:{'id':id},
				dataType:'json',
				success: function(msg){
					if(msg.status==1){
						$('.follow span').removeClass('add').addClass('added').html('已关注');
						$('.follow').addClass('is_follow');
						$('.is_follow').mouseover(function(){
							$('.follow span').removeClass('plus').html('取消关注');
						}).mouseout(function(){
							$('.follow span').addClass('plus').html('已关注');
						});

						layer.msg(msg.info);
						$('.fansNum strong').html(parseInt($('.fansNum strong').html()) + 1);
					}else{
						layer.msg(msg.info);
					}
				}
			});
		}

    }
})

$('.is_follow').mouseover(function(){
	$('.follow span').removeClass('plus').html('取消关注');
}).mouseout(function(){
	$('.follow span').addClass('plus').html('已关注');
});