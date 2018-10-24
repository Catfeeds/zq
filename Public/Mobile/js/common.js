/*手机验证*/
function isMobile(str) {
    var reg = /^1[3456789]{1}\d{9}$/;
    return reg.test(str);
}
/*验证密码*/
function isPwd(str) {
    var reg = /[0-9a-zA-Z]{6,15}/;
    return reg.test(str);
}
//提款密码
function isPwdDraw(str) {
    var reg = /^\d{6}$/;
    return reg.test(str);
}
$(function () {
    //先登录
    if(user_auth){
        //注册后弹框
        if(gift1_after && gift1_open != '' && gift1_close == '') {
            $('#gift1-class').find('img').eq(0).attr('src', gift1_after);
            $('#bg-gift').show();
            $('#gift1-class').show();
            //点击注册跳转过来活动弹框
        }else if(gift2_before && gift2_close == '' && gift2_frame){
            getGift2(gift2_before);
            //只有活动弹框
        }else if(gift2_before && gift2_close == '' && gift2_frame == ''){
            getGift2(gift2_before);
        }
    }

    //关闭
    $('#gift1-class .closedTbn').click(function() {
        $('#bg-gift').hide();
        $('#gift1-class').hide();

        $.ajax({
            type: 'post',
            url: "/Ticket/recordGift.html",
            async: false,
            data: {type: 1},
            dataType: 'json',
            success: function (data) {
                if(data.status == 1){
                    if(user_auth != '' && gift2_before != '' && gift2_close ==''){
                        getGift2(gift2_before);
                    }
                }
            }
        });
    });

    $('#gift2-class .closedTbn').click(function() {
        $('#bg-gift').hide();
        $('#gift2-class').hide();

        $.ajax({
            type: 'post',
            url: "/Ticket/recordGift.html",
            async: false,
            data: {type: 2},
            dataType: 'json',
            success: function (data) {

            }
        });
    });

    //注册跳转
    $('#gift1-class .getBtn').click(function() {
        $.ajax({
            type: 'post',
            url: "/Ticket/recordGift.html",
            async: false,
            data: {type: 1},
            dataType: 'json',
            success: function (data) {
                if(data.status == 1){
                    window.location.href = '/Ticket/myTicket.html?gift2_frame=1';
                }
            }
        });
    });

    //活动获取
    $('#gift2-class .getBtn').click(function() {
        if($('#gift2-class').hasClass('done')){
            $('#gift2-class .closedTbn').click();
            window.location.href = '/Ticket/myTicket.html';
        }else{
            if(user_auth){
                var gift_id = "{$gift2['id']}";
                $.ajax({
                    type: 'post',
                    url: "/Ticket/getGift.html",
                    async: false,
                    data: {gift_id: gift_id},
                    dataType: 'json',
                    success: function (data) {
                        if (data.status == 1) {
                            $('#gift2-class .closedTbn').click();
                            $('#gift2-class').find('img').eq(0).attr('src', gift2_after);
                            $('#bg-gift').show();
                            $('#gift2-class').show();
                            $('#gift2-class').addClass('done');
                        } else {
                            alert(data.info);
                        }
                    }
                });
            }
        }

    });
    function getGift2(gift2_before){
        $('#gift2-class').find('img').eq(0).attr('src', gift2_before);
        $('#bg-gift').show();
        $('#gift2-class').show();
    }

    //返回顶部
    $(window).scroll(function(e) {
        //$(window).height()
        if($(window).scrollTop()> 1500){
            // $('.return-top').show();
        }else {
            $('.return-top').hide();
        }
    });
    $('.return-top').click(function(e) {
        $('body,html').animate({'scrollTop':'0'},100);
    });
    $('.backtop').click(function(e) {
        $('body,html').animate({'scrollTop':'0'},100);
    });
    //隐藏网站统计
    $('#cnzz a').css('display','none');
    if (localStorage.getItem("scoreSound") == null) {
        localStorage.setItem("scoreSound", 1);
    }
    if (localStorage.getItem("redSound") == null) {
        localStorage.setItem("redSound", 1);
    }
    if (localStorage.getItem("redTan") == null) {
        localStorage.setItem("redTan", 1);
    }
    if (localStorage.getItem("scoreTan") == null) {
        localStorage.setItem("scoreTan", 1);
    }
    if (localStorage.getItem("refreshTime") == null) {
        localStorage.setItem("refreshTime", 5);
    }
    if (Cookie.getCookie('language') == null) {
        Cookie.setCookie('language', 0);
    }
    if (Cookie.getCookie('appbar') != 1) {
        $('#app-bar').show();
    }
    $(document).on('click', '.subnav_level a', function () {
        $this = $(this);
        if ($this.hasClass('on')) {
            $this.removeClass('on');
            $(".subnav_list .leagus" + $this.index()).removeClass("on");
        } else {
            $this.addClass('on');
            $(".subnav_list .leagus" + $this.index()).addClass("on");
        }
        $("#shai_hide").html(parseInt($(".subnav_list a").length) - parseInt($(".subnav_list .on").length));
    }).on('click', '#sele_cancel', function () {
        $("#zhishu_event,#nav_sele").hide();
    }).on('click', '.js-detail', function () {
        var topHeight = $(document).scrollTop();
        Cookie.setCookie('scrollTop', topHeight, 60000);
        window.location.href = $(this).data('url');
    }).on('click', '#ne_btn,.ne_btn', function () {
        //显示密码
        if ($(this).hasClass('no-pw')) {
            $(this).removeClass('no-pw');
            $(this).addClass('yes-pw');
            $('#inputbox .xspwd,.inputbox .xspwd').css('display', 'block').val($('#inputbox .ycpwd,.inputbox .ycpwd').val());
            $('#inputbox .ycpwd,.inputbox .ycpwd').hide();
        } else {
            $(this).removeClass('yes-pw');
            $(this).addClass('no-pw');
            $('#inputbox .xspwd,.inputbox .xspwd').hide();
            $('#inputbox .ycpwd,.inputbox .ycpwd').css('display', 'block').val($('#inputbox .xspwd,.inputbox .xspwd').val());
        }
    }).on('click', '.ne_close', function () {
        //清除input框内容
        $(this).siblings('input').val('');
    }).on('change','.inputbox .xspwd,#inputbox .xspwd',function(){
        $('.inputbox .ycpwd,#inputbox .ycpwd').val($('.inputbox .xspwd,#inputbox .xspwd').val());
    }).on('click','#cone',function(){
        Cookie.setCookie('appbar', 1);
        $('#app-bar').hide();
        return false;
    });
    //导航 计算a的平均宽度
    //getNavWidth();
    //点击显示 赛事筛选
    showEvent();
    //赛事筛选
    eventfun();
    //选择全部等级显示 - 全站
    showLevel();
    //导航条滚动事件
    navHead();

})

//导航条滚动事件
function navHead(){
    if($('.n_module ').html() === undefined)
    {
        return true;
    }
    var navOffset = $(".n_module").offset().top;
    //当页面滚动时对顶部导航栏进行移动处理
    $(window).scroll(function(){
        var scrollPos=$(window).scrollTop();
        console.log(scrollPos >=navOffset);
        if(scrollPos >=navOffset){
            $('.navFixed').css('top','0px');
        }else{
            $('.navFixed').css('top','.89rem');
        }
    });
}


//导航 计算a的平均宽度
// function getNavWidth() {
//     var a_num = $(".nav_list a").length;
//     var a_margin = a_num * 2;  //margin间距
//     var a_w = (96 - a_margin) / a_num;
//     $(".nav_list a").css("width", a_w + "%")
// }

//点击显示 赛事筛选
function showEvent() {
//     $(".set_list a").eq(1).click(function () {
//         $("#zhishu_event").stop().fadeToggle(200);
//         $("#nav_sele").stop().fadeToggle(200);
//     });
    $(".posnal_list a").eq(2).click(function () {
        $("#zhishu_event").stop().fadeToggle(200);
        $("#nav_sele").stop().fadeToggle(200);
    });
    
    $(".posnal_list .m_rule").click(function () {
        $("#zhishu_event").stop().fadeToggle(200);
        $("#nav_sele").stop().fadeToggle(200);
    });
}

//赛事筛选
function eventfun() {
    var showNum = $(".ns_l span");
    $(".subnav_list a").each(function () {
        $(this).click(function () {

            if ($(this).hasClass("on")) {
                $(this).removeClass("on");
                $("#shai_hide").html(parseInt($("#shai_hide").html()) + 1);
                //showNum.html(parseInt($(".ns_l span").html()) -1 )
            } else {
                $(this).addClass("on");
                $("#shai_hide").html(parseInt($("#shai_hide").html()) - 1);
                //showNum.html($(".subnav_list .on").length);
            }
            if (parseInt($(".subnav_list .on").length) >= 1) {
                $(".nav_sele").fadeIn();
            } else {
                //$(".nav_sele").fadeOut();
            }
        });
    });
    $("#shai_hide").html(parseInt($(".subnav_list a").length) - parseInt($(".subnav_list .on").length));
    //全选
    $("#sele_all").click(function () {
        $(".subnav_list a").addClass("on");
        $('.subnav_level a').addClass('on');
        $("#shai_hide").html(0);
        //showNum.html($(".subnav_list a").length);
    })
    //不全选
    $("#sele_all_no").click(function () {
        $(".subnav_list a").removeClass("on");
        $('.subnav_level a').removeClass('on');
        $("#shai_hide").html($(".subnav_list a").length);
        //showNum.html(0);
    })
}

//选择全部等级显示 - 全站
function showLevel() {
    var le0_len = $(".subnav_list .leagus0").length;
    var le0_on_len = $(".subnav_list a[class='leagus0 on']").length;
    if ((le0_len != 0) && (le0_len == le0_on_len)) {
        $(".subnav_level a").eq(0).addClass("on");
    }

    var le1_len = $(".subnav_list .leagus1").length;
    var le1_on_len = $(".subnav_list a[class='leagus1 on']").length;
    if ((le1_len != 0) && (le1_len == le1_on_len)) {
        $(".subnav_level a").eq(1).addClass("on");
    }

    var le2_len = $(".subnav_list .leagus2").length;
    var le2_on_len = $(".subnav_list a[class='leagus2 on']").length;
    if ((le2_len != 0) && (le2_len == le2_on_len)) {
        $(".subnav_level a").eq(2).addClass("on");
    }
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

//设置遮罩层高度等于屏幕高度
// $('.fixBox').css('height',$(window).height());
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
