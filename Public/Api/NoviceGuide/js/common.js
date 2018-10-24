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
$(function () {
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

})


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