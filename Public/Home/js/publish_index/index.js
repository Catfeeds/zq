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

    var navOffset=$(".secTop").offset().top;  
    $(window).scroll(function(){  
        var scrollPos=$(window).scrollTop();  
        if(scrollPos >=navOffset){  
            $(".secTop").addClass("div-fixed");  
        }else{  
            $(".secTop").removeClass("div-fixed");   
        }  
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
    //浮窗点击关闭
    $('.close').click(function(){
         $('.floating').css('display','none');
    })
	
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

	//根据classId选定文章类型
	$(".navList ul li").each(function(index, element){
        var className = $(".hidden-className").val();
        if(className == '专家说彩'){
        	className = '全部';
        }
        if ($(element).children("a").text() === className) {
            $(element).addClass("active");
        }
    });

    //绑定a标签
    $("#GO").find("a").bind("click", function(){
        var p = $("input[name='p']").val();
        if (isNaN(p)) {
            return;
        } else if (p>0){
            var class_flag = $("input[name='class_flag']").val();
            var filter = $("input[name='hidden_filter']").val();
            if (filter != 0) {
                window.location.href = class_flag+'.html' + "?p="+p+"&filter="+filter;
			}else {
                window.location.href = class_flag+'.html' + "?p="+p;
            }
        }
    });

    /**
	 * 筛选方法
     */
	$(document).on("change", "select.union_id", function() {
        var p = $("input[name='hidden_p']").val();
	    var s = $(this).val();
	    if (s === 0) {
	        return;
        } else if (s !== 0) {
            var class_flag = $("input[name='class_flag']").val();
            window.location.href = class_flag+".html"+"?p="+p +"&filter="+s;
        }
	});

    /**
     * 筛选添加选中attr
     */
    $("select.union_id").children("option").each(function(i, e) {
        var filter = $("input[name='hidden_filter']").val();
        if ($(e).val() == filter ) {
        	$(e).prop("selected", "selected")
		}
    });
    getVideoPhoto();
});

$('.renew').on('click',function(){
    getVideoPhoto();
});
//获取视频图库数据
function getVideoPhoto()
{
    $.ajax({
        url:'/getrecommend.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success:function(data){
            var video = data.video;
            var videoHtml = '';
            for(var i=0;i<video.length;i++){
                videoHtml = videoHtml+getVHtml(video[i]);
            }
            $('.listBox04 ul').html(videoHtml);
            //图库
            var photo = data.photo;
            var photoHtml = '';
            for(var i=0;i<photo.length;i++){
                photoHtml = photoHtml+getPHtml(photo[i]);
            }
            $('.left-photo').html(photoHtml);
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
}


//生成视频模块html
function getVHtml(data)
{
    var html = '';
    html = '<li>'+
        '<a target="_blank" href="'+data.href+'" title="'+data.title+'">' +
        '<span></span>' +
        '<img src="'+data.img+'" width="302" height="175" alt="'+data.title+'" />' +
        '<p>'+data.title+'</p>' +
        '</a>'+
        '</li>';
    return html;
}

//生成图库模块html
function getPHtml(data)
{
    var html = '';
    html = '<li class=" pull-left">' +
        '<a class="numb-tab-d" target="_blank" href="' + data.info_url + '" title="' + data.title + '">' +
        '<div class="tab-img"><img alt="' + data.title + '" src="' + data.cover_img + '">' +
        '</div>' +
        '<div class="tab-work">' +
        '<p>' + data.title + '</p>' +
        '</div>' +
        '</a>' +
        '</li>';
    return html;
}