
$(document).ready(function(){
	//导航 计算a的平均宽度
	getNavWidth(); 
	//点击显示 赛事筛选
	showEvent();
	//赛事筛选
    eventfun(); 
    //点击遮罩层隐藏
    hideEvent()
})


//导航 计算a的平均宽度
function getNavWidth(){
		  var a_num = $(".nav_list a").length; 
		  var a_margin = a_num * 2;  //margin间距
		  var a_w = (96 - a_margin) /a_num; 
	      $(".nav_list a").css("width",a_w + "%")  
}

//点击显示 赛事筛选
function showEvent(){
	$(".set_list a").eq(1).click(function(){ 
            $("#maskLayer").stop().fadeToggle(200);
            $("#zhishu_event").stop().fadeToggle(200);
            $("#nav_sele").stop().fadeToggle(200);
    });
}
//点击遮罩层隐藏
function hideEvent(){
	$("#maskLayer").click(function(){
	 $(this).fadeOut();
	 $("#zhishu_event").fadeOut();
	 $("#nav_sele").fadeOut();
})
}
//赛事筛选
function eventfun(){
	        var showNum = $(".ns_l span");  
            $(".subnav_list a").each(function(){
            	$(this).click(function(){
                     
            		if($(this).hasClass("on")){
                       $(this).removeClass("on");
                       //showNum.html(parseInt($(".ns_l span").html()) -1 )
            		}else{
            		   $(this).addClass("on");
            		   //showNum.html($(".subnav_list .on").length);
            		}
            		if(parseInt($(".subnav_list .on").length) >=1){
            			$(".nav_sele").fadeIn();
            		}else{
            			$(".nav_sele").fadeOut();
            		}
            	})
            }) 
             
            //全选
        	$("#sele_all").click(function(){
        		$(".subnav_list a").addClass("on");
        		//showNum.html($(".subnav_list a").length);
        	})
        	//不全选
            $("#sele_all_no").click(function(){
        		$(".subnav_list a").removeClass("on");
        		//showNum.html(0);
        	})
}

 /**
 +----------------------------------------------------------
 * 显示提示信息js
 * @param msg       提示内容
 * @param style     提示样式 成功：success  失败 error
 +----------------------------------------------------------
 */
function showMsg(msg,style)
{
    switch(style)
    {
        case undefined:
        case 'success': var str = 'bubbleTips'; break;
        case 'error'  : var str = 'failTips';   break;
    }
    var html = "<div class='"+str+"'></div>"
    if($("."+str+"").length == 0){
        $("body").append(html);
    }
    $('.'+str+'').html(msg).stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
}