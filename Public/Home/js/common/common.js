/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 * 
 * @since  2018-01-16
 *
**/
/**
 +----------------------------------------------------------
 * 获取网站域名
 +----------------------------------------------------------
 */
var web_url      = document.location.href;
var staticDomain = config.staticDomain;
var DOMAIN       = config.domain;
var getweburl    = web_url.split('?')[0];
var getwebhost   = window.location.protocol+"//"+window.location.host;
var DOMAIN_URL   = (DOMAIN == 'qqty.com' ? 'https://' : 'http://') +"www."+DOMAIN;
var userLoginInfo  = []; //登录用户信息
$(function(){
    //返回顶部
    $(window).scroll(function(e) {
        if($(window).scrollTop()>100){
            $('.toolsList li').eq(3).css("visibility","visible");
        }else {
            $('.toolsList li').eq(3).css("visibility","hidden");
        }
    });
    $('.moveTop').click(function(e) {
        $('body,html').animate({'scrollTop':'0'},500);

    });

    //梯口模块
    $('.toolsList li a').hover(function(e) {
        $(this).children('i').stop().animate({"margin-top":"-50px"},300)
        var myLi = $(this).parent('li').index();
        if(myLi==0){
            $('.ewm').stop().fadeIn(400);
        }
    },function(){
        $(this).children('i').stop().animate({"margin-top":"0px"},300)
        $('.ewm').stop().fadeOut(300);
    });
    lazyload();
    ajaxCheckLogin();
})

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
 * 通用弹出框
 * @param title         提示框标题
 * @param content       提示框内容
 * @param functionName  确定之后执行的js
 +----------------------------------------------------------
 */
 function _alert(title, content, functionName){
    layer.open({
        title: title,
        content: content,
        yes: function(index, layero){
            layer.close(index);
            if(functionName){
                eval(functionName);
            }
        }
    }); 
 }

/**
 +----------------------------------------------------------
 * 通用弹出确认框
 * @param title         提示框标题
 * @param content       提示框内容
 * @param functionName  确定之后执行的js
 +----------------------------------------------------------
 */
function _confirm(title, content, functionName){
    layer.confirm(content, {
        title:title,
        btn: ['确定','取消'] //按钮
    }, function(index, layero){
        layer.close(index);
        if(functionName){
            functionName(true);
        }
    }, function(index, layero){
        layer.close(index);
            if(functionName){
            functionName(false);
        }
    });
}

 /**
 +----------------------------------------------------------
 * 显示提示信息js
 * @param msg       提示内容
 * @param isReload  是否刷新 默认不刷新 传入数值几秒后刷新
 * @param style     提示样式 成功：success  失败 error
 +----------------------------------------------------------
 */
function showMsg(msg,isReload,style)
{
    var icon = style == 'success' ? 1 : 5; 
    layer.msg(msg, {icon: icon});

    if(isReload > 0){
        window.setTimeout("window.location.reload()",isReload); 
    }
}

//登录信息
function ajaxCheckLogin(){
    var is_complete = $("#is_complete").val();
    $.ajax({
        type:'get',
        url : DOMAIN_URL+"/Common/ajaxCheckLogin.html",  
        data:{'is_complete':is_complete},
        dataType:'jsonp',
        jsonp:'logincallback',
        success:function(result) {
            var info = result.info;
            if(result.status == 1){
                $(".lnk-user .userFace").html('<img src="'+info.head+'" alt="头像" width="18" height="18">');
                $(".lnk-user .userName a").text(info.nick_name);
                $(".lnk-user").removeClass('hidden');
                if(info.is_expert == 1){
                    $(".lnk-user .badgeIcon").html('<img src="'+staticDomain+'/Public/Home/images/common/badge.png" alt="专家标志" width="8" height="11">');
                }else{
                    $('.sport-user').removeClass('hidden');
                }
                userLoginInfo = info;
                $("input[name='userId']").val(info.id);
                $("input[name='balance']").val(info.balance);
                $('.um_head_img').html('<img src="'+result.info.head+'" alt="头像">');
                $('.umr_name').text(result.info.nick_name);
                $('.umr_gold').html('金币：'+result.info.balance);
                $('.user_main_intro').removeClass('hidden');
            }else{
                if(info == 0){
                    $('.loginwrap,.sport-user').removeClass('hidden');
                    $('.user_main_login').removeClass('hidden');
                }else if (info == '-1'){
                    layer.open({
                        title : '温馨提示',
                        content: '请完善资料',
                        end:function(index, layero){
                            window.location.href=DOMAIN_URL+"/User/complete_nick.html";
                        }
                    })
                }else{
                    layer.open({
                        title : '温馨提示',
                        content: info,
                        end:function(index, layero){
                            window.location.reload();
                        }
                    })
                }
                $("input[name='userId']").val('');
                $("input[name='balance']").val('');
            }
            $('.user_main').addClass('hidden');
        },
    });
}

/**
 +----------------------------------------------------------
 * 延迟加载
 +----------------------------------------------------------
 */
function lazyload(){
    if($("img.lazy").length > 0) {
        $("img.lazy").lazyload({
            placeholder: staticDomain+"/Public/Images/loading.png",
            effect: "fadeIn",
            threshold: 150,
            failurelimit: 100
        });
    }
}

//登录弹框
function modalLogin(){
    var url =  document.domain.replace('www.','').split(".").length-1 > 1 ? '/modalLogin.html' : '/User/modalLogin.html';
    $.ajax({
        type: "POST",
        url: url,
        dataType:'json',
        success: function(data){
            if(data.status == 1){
                var modalHtml = '';
                    modalHtml += '<div class="modalLogin"><form id="ajaxLogin" novalidate="novalidate">',
                    modalHtml += '<input type="hidden" value="'+data.info+'" name="token">',
                    modalHtml += '<div class="item item-fore1"><label for="loginname" class="login-label name-label"></label><input type="password" class="hidden"><input id="loginname" type="text" class="itxt form-control" name="username" tabindex="1" autocomplete="off" placeholder="输入手机号"><span class="clear-btn"></span></div>',
                    modalHtml += '<div class="item item-fore1 item-fore2"><label class="login-label pwd-label" for="nloginpwd"></label><input type="password" class="hidden"><input type="password" id="nloginpwd" name="password" class="itxt itxt-error form-control" tabindex="2" autocomplete="off" placeholder="登录密码"><span class="clear-btn"></span></div>',
                    modalHtml += '<div class="clearfix remeber"><div class="pull-left"><label for="checkbox"><input id="checkbox" type="checkbox" name="remember"> 保持登录</label></div><div class="pull-right"><a target="_blank" href="'+DOMAIN_URL+'/User/register.html">免费注册</a>&nbsp;|&nbsp;<a target="_blank" href="'+DOMAIN_URL+'/User/re_phone.html">忘记密码？</a></div></div>',
                    modalHtml += '<div class="login-btn"><input class="btn btn-warning" type="submit" value="登录"></div>',
                    modalHtml += '<div class="else_login"><a href="'+DOMAIN_URL+'/User/sdk_login/type/qq.html" target="_blank;"><img src="'+staticDomain+'/Public/Home/images/login/qq.png">QQ登录</a><a href="'+DOMAIN_URL+'/User/sdk_login/type/sina.html" target="_blank;"><img src="'+staticDomain+'/Public/Home/images/login/sina.png">微博登录</a><a href="'+DOMAIN_URL+'/User/sdk_login/type/weixin.html" target="_blank;"><img src="'+staticDomain+'/Public/Home/images/login/wx.png">微信登录</a></div>',
                    modalHtml += '</form></div>';
                layer.open({
                    id:'modalLogin',
                    type: 1,
                    title: '用户登录',
                    content: modalHtml,
                    btn:false,
                    anim: 0,
                    success: function(layero, index){
                        var url =  document.domain.replace('www.','').split(".").length-1 > 1 ? '/login.html' : '/User/login.html';
                        //点击登陆
                        $("#ajaxLogin").validate({
                            onkeyup:false,
                            rules: {
                                username: {required: true},
                                password: {required: true}
                            },
                            messages : {
                                username: {required: '请输入手机号码'},
                                password: {required: '请输入登录密码'}
                            },
                            submitHandler:function(form){
                                var data = $('.modalLogin form').serialize();
                                $.ajax({
                                    type: "POST",
                                    url: url,
                                    data: data,
                                    dataType:'json',
                                    success: function(data){
                                      if(data.status){
                                        window.location.reload();
                                      }else{
                                        $("input[name='password']").val('');
                                        var html = "<label class='field-msg field-error' for='nloginpwd'>"+data.info+"</label>";
                                        $("input[name='password']").after(html);
                                      }
                                    }
                                });
                            }
                        })
                    }
                }); 
            }else{
                _alert('提示','获取登录失败！');
            }
        }
    });
}

//生成uuid
function getUuid() {
    var s = [];
    var hexDigits = "0123456789abcdef";
    for (var i = 0; i < 36; i++) {
        s[i] = hexDigits.substr(Math.floor(Math.random() * 0x10), 1);
    }
    s[14] = "4";  // bits 12-15 of the time_hi_and_version field to 0010
    s[19] = hexDigits.substr((s[19] & 0x3) | 0x8, 1);  // bits 6-7 of the clock_seq_hi_and_reserved to 01
    s[8] = s[13] = s[18] = s[23] = "-";

    var uuid = s.join("");
    return uuid;
}