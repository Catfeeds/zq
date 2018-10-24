/**
 * Created by Administrator on 2018/1/23.
 */
$(function(){
	var navOffset=$(".secTop").offset().top;  
	$(window).scroll(function(){  
	    var scrollPos=$(window).scrollTop();  
	    if(scrollPos >=navOffset){  
	        $(".secTop").addClass("div-fixed");  
	    }else{  
	        $(".secTop").removeClass("div-fixed");   
	    }  
	});
});
