
$(function () {
    //隐藏跟显示密码
    $('.ne_btn').click(function (e) {
        if ($(this).hasClass('no-pw')) {
            $(this).removeClass('no-pw');
            $(this).addClass('yes-pw');
            $('.inputbox .xspwd').css('display', 'block');
            $('.inputbox .ycpwd').hide();
        } else {
            $(this).removeClass('yes-pw');
            $(this).addClass('no-pw');
            $('.inputbox .xspwd').hide();
            $('.inputbox .ycpwd').css('display', 'block');
        }
        ;
    });
    //清除密码
    $('.ne_close').click(function (e) {
        $('input[name="username"]').val('');
    });
    $('.tip_btn').click(function (e) {
        $('.fixBox').hide();
    });
    //获取短信验证码
    var validCode = true;
    $(document).on('click', '.made_code', function () {
        var mobile = $("#mobile").val();
        if (!isMobile(mobile)) {
            alert('请输入有效的11位手机号码!');
            return false;
        }
        $.post("/User/sendMobileMsg", {mobile: mobile,change:1}, function (data) {
            if (data.status === 1) {
                var time = 60;
                var code = $(this);
                if (validCode) {
                    validCode = false;
                    code.removeClass("msgs1");
                    var t = setInterval(function () {
                        time--;
                        code.html(time + "秒");
                        if (time == 0) {
                            clearInterval(t);
                            code.html("重新获取");
                            validCode = true;
                            code.addClass("msgs1");
                        }
                    }, 1000);
                }
            } else {
                validCode = true;
                alert(data.info);
            }
        }, 'json');
    }).on('click',"#dologin", function () {
        var pwd=$("#password").val();
        var code=$("#code").val();
        var mobile=$("#mobile").val();
        if (!isMobile(mobile)) {
            alert('请输入有效的11位手机号码!');
            return false;
        }
        if (!isPwd(pwd)) {
            alert('请输入6~16位的密码!');
            return false;
        }
        if (code.length!=4) {
            alert('请输入正确的验证码!');
            return false;
        }
         var params={
             mobile:mobile,
             pwd:pwd,
             code:code
         };
        $.post('/User/doforget', params, function (data) {
            if (data.status === 1) {
                alert(data.info);
                window.location.href = data.url;
            }else{
                alert(data.info);
            }
        }, 'json');
        return false;
    });

});