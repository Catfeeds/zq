/**
 +----------------------------------------------------------
 * 全局js
 +----------------------------------------------------------
 */

/**
 +----------------------------------------------------------
 * 获取网站域名
 +----------------------------------------------------------
 */
var web_url = document.location.href;
var getweburl = web_url.split('?')[0];
var getwebhost = window.location.protocol+"//"+window.location.host;
var DOMAIN_URL = (DOMAIN == 'qqty.com' ? 'https://' : 'http://') +"www."+DOMAIN;

/** +----------------------------------------------------------
 * 监听滚屏事件
 +----------------------------------------------------------
 */
/*$(window).scroll(function(){
	if ($(window).scrollTop()>0) {   
		 $('.guding').fadeIn(1000);
	}
	else {   
		 $('.guding').fadeOut(1000);
	}
});*/

 /**
 +----------------------------------------------------------
 * 销毁自身
 +----------------------------------------------------------
 */
function delSelf(obj){
	$(obj).remove();
}
  /**
 +----------------------------------------------------------
 * 隐藏和显示切换
 +----------------------------------------------------------
 */
function switchSelf(obj){
	$(obj).slideToggle("slow");
}
 /**
 +----------------------------------------------------------
 * 收藏本站
 +----------------------------------------------------------
 */
function AddFavorite(title, url) {
    try {
        window.external.addFavorite(url, title);
    }
    catch (e) {
        try {
            window.sidebar.addPanel(title, url, "");
        }
        catch (e) {
            alert("\u62b1\u6b49\uff0c\u60a8\u6240\u4f7f\u7528\u7684\u6d4f\u89c8\u5668\u65e0\u6cd5\u5b8c\u6210\u6b64\u64cd\u4f5c\u3002\u52a0\u5165\u6536\u85cf\u5931\u8d25\uff0c\u8bf7\u4f7f\u7528Ctrl+D\u8fdb\u884c\u6dfb\u52a0\uff01");
        }
    }
}

 /**
 +----------------------------------------------------------
 * 通用弹出确认提示框
 * @param title 	提示框标题
 * @param content 	提示框内容
 * @param ensureUrl 确定后跳转地址或执行的js
 * @param isJs 		是否为js，只要有值就是
 +----------------------------------------------------------
 */
 function _confirm(title, content, ensureUrl, isJs){
	gDialog.fConfirm(title,content,function(rs){
		if(rs && isJs){
			//执行js
			eval(ensureUrl);
		}else if (rs && !isJs){
			location.href=""+ensureUrl+"";
		}
	});
 }
 /**
 +----------------------------------------------------------
 * 通用弹出确认提示框-供子页使用
 * @param title 		提示框标题
 * @param content 		提示框内容
 * @param functionName 	子页面js
 * @param childName 	子页面名称
 +----------------------------------------------------------
 */
 function _confirmChild(title, content, functionName, childName){
	var ifr = document.getElementById(childName);
	var win = ifr.window || ifr.contentWindow;
	gDialog.fConfirm(title,content,function(rs){
		if(rs){
			//yes
			eval("win."+functionName);
		}else{
		  //no
		}
	});
 }
/**
 +----------------------------------------------------------
 * 通用弹出框
 * @param title 		提示框标题
 * @param content 		提示框内容
 * @param functionName 	确定之后执行的js
 +----------------------------------------------------------
 */
 function _alert(title, content, functionName){
	gDialog.fAlert(title,content,function(rs){
		if(functionName){
			eval(functionName);
		}
	});
 }
/**
 +----------------------------------------------------------
 * 通用弹出框 - 供子页面使用
 * @param title 		提示框标题
 * @param content 		提示框内容
 * @param functionName 	子页面js
 * @param childName 	子页面名称
 +----------------------------------------------------------
 */
 function _alertChild(title, content, functionName, childName){
	var ifr = document.getElementById(childName);
	var win = ifr.window || ifr.contentWindow;
	gDialog.fAlert(title,content,function(rs){
		if(functionName){
			eval("win."+functionName);
		}
	});
 }
 /**
 +----------------------------------------------------------
 * 父页面执行子页面js
 * @param functionName 	子页面js
 * @param childName 	子页面名称
 +----------------------------------------------------------
 */
 function _runChildJs(functionName, childName){
	var ifr = document.getElementById(childName);
	var win = ifr.window || ifr.contentWindow;
	eval("win."+functionName);
 }

 /**
 +----------------------------------------------------------
 * 显示提示信息js
 * @param msg 	    提示内容
 * @param isReload 	是否刷新 默认不刷新 传入数值几秒后刷新
 * @param style 	提示样式 成功：success  失败 error
 +----------------------------------------------------------
 */
function showMsg(msg,isReload,style)
{
    var html = "<div class='bubbleTips' style='display:none;width: 300px; height: 118px; line-height: 118px; position: fixed; left: 50%; margin-left: -150px; top: 400px; background: #000; font-size: 16px; z-index: 10000; border-radius: 10px; background-color: rgba(0,0,0,.5); text-align: center; transition: 1s margin-top ease-out; color: #fff; background: url(../../images/quiz_hall/trans-bg.png)\9;' >"+
				    "<span style='margin-right:5px;'><img width='32' height='32' /></span>"+
				    "<span class='alertMsg'></span>"+
				"</div>"
	if($(".bubbleTips").length == 0){
		$("body").append(html);
	}
	$('.bubbleTips').show();
    $('.bubbleTips').find('.alertMsg').html(msg);
    switch(style)
    {
        case undefined:
        case 'success': var imgScr = 'success.png'; break;
        case 'error'  : var imgScr = 'icon-error.png';  break;
    }
    $('.bubbleTips').find('img').attr('src','/Public/Home/images/quiz_hall/'+imgScr);
    $('.bubbleTips').stop().animate({'top':'300px'},1500).hide(0).animate({'top':'400px'});
    if(isReload > 0){
    	window.setTimeout("window.location.reload()",isReload); 
    }
}

//登录信息
$(function(){
    lazyload();
    var is_complete = $("#is_complete").val();
    $.ajax({
        type:'get',
        url : DOMAIN_URL+"/Common/ajaxCheckLogin.html",  
        data:{'is_complete':is_complete},
        dataType:'jsonp',
        jsonp:'logincallback',
        success:function(result) {
            if(result.status == 1){
                var msg = result.info.msg > 0 ? "<span class=\"info\"></span>" : "";
                var html = "<span class=\"dropdown dropdown-top user-name\">"+
                                "<a target=\"_blank\" href=\""+DOMAIN_URL+"/UserInfo/index.html\" class=\"nav on\" title=\""+result.info.nick_name+"\">"+result.info.nick_name+"</a>"+
                                "<span class=\"caret\"></span>"+
                                "<ul class=\"dropdown-menu\">"+
                                    "<li><a target=\"_blank\" href=\""+DOMAIN_URL+"/UserInfo/index.html\">个人中心</a></li>"+
                                    "<li><a target=\"_blank\" href=\""+DOMAIN_URL+"/UserInfo/basic_infor.html\">帐号设置</a></li>"+
                                    "<li><a href=\""+DOMAIN_URL+"/User/logout.html\">退出</a></li>"+
                                "</ul>"+
                                "</span>"+
                            "<a class=\"link-info\" href=\""+DOMAIN_URL+"/UserInfo/station_notice.html\"><img src=\"/Public/Home/images/index/link-info.png\" width=\"16\" height=\"12\">"+msg+"</a>";
                $("input[name='userId']").val(result.info.id);
                $("input[name='balance']").val(result.info.balance);
                $('.um_head_img').html('<img src="'+result.info.head+'" alt="头像">');
                $('.umr_name').text(result.info.nick_name);
                $('.umr_gold').html('金币：'+result.info.balance);
                $('.user_main_intro').removeClass('hidden');
            }else{
                if(result.info == 0){
                    var html = "<a class=\"lnk-login\" href=\""+DOMAIN_URL+"/User/login.html\">登录</a><a class=\"lnk-reg\" href=\""+DOMAIN_URL+"/User/register.html\">注册</a>";
                    $('.user_main_login').removeClass('hidden');
                }else if (result.info == '-1'){
                    _alert("温馨提示","请完善资料","window.location.href='"+DOMAIN_URL+"/User/complete_nick.html'");
                }else{
                    gDialog.fConfirm("温馨提示",result.info,function(rs){
                        location.reload();
                    });
                }
                $("input[name='userId']").val('');
                $("input[name='balance']").val('');
            }
            $(".lnk-welcome").after(html);
            $('.user_main').addClass('hidden');
        },
    });
    //导航切换
    $('.navUl>li').hover(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var navLiNum = $(this).index();
        $('.navList>ul').eq(navLiNum).removeClass('hidden').siblings().addClass('hidden');
    });
    $('.nav-con .nav').mouseleave(function(){
        $('.navUl').find('.selected').addClass('on').siblings().removeClass('on');
        $('.navList').find('.selected').removeClass('hidden').siblings().addClass('hidden');
    });
})

/**
 +----------------------------------------------------------
 * 延迟加载
 +----------------------------------------------------------
 */
function lazyload(){
    $("img.lazy").lazyload({
          placeholder : "/Public/Home/images/common/loading.png",
          //effect: "slideDown",
          effect: "fadeIn",
          threshold : 180,
          failurelimit:100
    });
}

//百度统计
// var _hmt = _hmt || [];
// (function() {
//   var hm = document.createElement("script");
//   hm.src = "//hm.baidu.com/hm.js?0452415b61a2145478f9493dac7e2a81";
//   var s = document.getElementsByTagName("script")[0];
//   s.parentNode.insertBefore(hm, s);
// })();
// (function(){
//    var src = (document.location.protocol == "http:") ? "http://js.passport.qihucdn.com/11.0.1.js?503d8030a4adf9abdae13b9315dd41c3":"https://jspassport.ssl.qhimg.com/11.0.1.js?503d8030a4adf9abdae13b9315dd41c3";
//    document.write('<script src="' + src + '" id="sozz"><\/script>');
// })();
function getCookie(name)
{
    var arr,reg=new RegExp("(^| )"+name+"=([^;]*)(;|$)");
    if(arr=document.cookie.match(reg))
    return unescape(arr[2])+'';
    else
    return undefined;
}

// var userId = getCookie('us_fjs');

// //深圳数据统计
// var _paq = _paq || [];
// _paq.push(['trackPageView']);
// _paq.push(['enableLinkTracking']);
// (function() {
// var u="https://www.453win.com/";
// _paq.push(['setTrackerUrl', u+'453win.php']);
// _paq.push(['setSiteId', '2']);
// _paq.push(['setUserId', userId]);
// var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
// g.type='text/javascript'; g.async=true; g.defer=true; g.src=u+'453win.js'; s.parentNode.insertBefore(g,s);
// })();
