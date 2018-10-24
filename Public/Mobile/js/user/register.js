
$(function () {
    $(document).on('click', '.icon_weibo', function () {
        location.href = '/user/sdk_login/type/sina.html';
    }).on('click', '.icon_wx', function () {
        location.href = '/User/wechat_login.html';
    }).on('click', '.icon_qq', function () {
        location.href = '/user/sdk_login/type/qq.html';
    });
 
    //获取短信验证码
    $(document).on('click', '.made_code', function () {
        var code = $(this);
        if (code.hasClass("notallowed")) {
            return false;
        }
        var mobile = $("#mobile").val();
        if (!isMobile(mobile)) {
            $('#dailogContent').html('请输入有效的11位手机号码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        var change = $("#change").val();
        code.addClass("notallowed");
        var token=$("#mobile").attr('token');
        $.post("/User/sendMobileMsg", {token:token,mobile: mobile, change: change}, function (data) {
            if (data.status === 1) {
                countDownSms(code, 60);
                alert(data.info);
            } else {
                code.removeClass("notallowed");
                $('#dailogContent').html(data.info);
                $('#dailogFixBox').css('display','block');
            }
        }, 'json');
        function countDownSms($o, sec) {
            var sec = sec >= 0 ? sec : 60;
            if (sec === 0) {
                $o.text("获取验证码").removeClass("notallowed");
            } else {
                $o.text(sec + "秒后获取");
                sec--;
                setTimeout(function () {
                    countDownSms($o, sec);
                }, 1000);
            }
        }
    })

});