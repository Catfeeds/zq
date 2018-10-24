$(function () {
    //信息提示弹框关闭
    $('#yesDailog').on('click',function () {
        $('#dailogFixBox').css({'display':'none'});
    });

    $(document).on('click', '.dologin', function () {
        var index=$('.bind_nav a.on').index();
        var login_opwd = $('#old_pwd').val();
        var login_pwd = $('#password').val();
        var login_code = $('#code').val();
        var draw_opwd = $('#old_pwd2').val();
        var draw_pwd = $('#password2').val();
        var draw_code = $('#code2').val();
        var mobile = $("#mobile").val();
        if(mobile==''){

            $('#dailogContent').html('请完善你的手机号码信息！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if ((login_opwd == '' || login_pwd == '') && index=='0') {
            $('#dailogContent').html('请输入登录密码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if ((draw_opwd == '' || draw_pwd == '') && index=='1') {
            $('#dailogContent').html('请输入提款密码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if ((login_opwd == login_pwd) && index==0  || (draw_opwd==draw_pwd) && index==1) {
            $('#dailogContent').html('旧密码与新密码不能相同！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if (login_code=='' && index==0  || draw_code=='' && index==1) {
            $('#dailogContent').html('请输入验证码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if ((!isPwd(login_opwd) || !isPwd(login_pwd)) && index=='0') {
            $('#dailogContent').html('请输入6-16位密码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if ((!isPwdDraw(draw_opwd) || !isPwdDraw(draw_pwd)) && index=='1') {
            $('#dailogContent').html('请输入6位数字密码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if(index=='1'){
            var params = {
                old_pwd: draw_opwd,
                pwd: draw_pwd,
                code:draw_code,
                type:index
            };
        }else{
            var params = {
                old_pwd: login_opwd,
                pwd: login_pwd,
                code:login_code,
                type:index
            };
        }
        $.post("/User/dopwd", params, function (data) {
            $('#dailogContent').html(data.info);
            $('#dailogFixBox').css('display','block');
            if (data.status === 1 || data.info == '请先登录') {
                window.location.href = data.url;
            }
        }, 'json');
        return false;
    }).on('click', '.made_code', function () {
        //获取短信验证码
        var index=$('.bind_nav a.on').index();
        var code = $('.made_code');
        if (code.hasClass("notallowed")) {
            return false;
        }
        var old_pwd = $('#old_pwd').val();
        var pwd = $('#password').val();
        var old_pwd = $('#old_pwd').val();
        var pwd = $('#password').val();
        if ((old_pwd == '' || pwd == '') && index=='0') {
            $('#dailogContent').html('请输入密码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        if ((old_pwd == '' || pwd == '') && index=='0') {
            $('#dailogContent').html('请输入密码！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        var mobile = $("#mobile").val();
        if(mobile==''){
            $('#dailogContent').html('请完善你的手机号码信息！');
            $('#dailogFixBox').css('display','block');
            return false;
        }
        code.addClass("notallowed");
        countDownSms(code, 60);
        $.post("/User/sendMobileMsg", {mobile: mobile, change: 1}, function (data) {
            if (data.status === 1) {
                countDownSms(code, 60);
                $('#dailogContent').html(data.info);
                $('#dailogFixBox').css('display','block');
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
    }).on('click', '#ne_btn2,.ne_btn2', function () {
        //显示密码
        if ($(this).hasClass('no-pw')) {
            $(this).removeClass('no-pw');
            $(this).addClass('yes-pw');
            $('#inputbox2 .xspwd').css('display', 'block').val($('#inputbox2 .ycpwd').val());
            $('#inputbox2 .ycpwd').hide();
        } else {
            $(this).removeClass('yes-pw');
            $(this).addClass('no-pw');
            $('#inputbox2 .xspwd').hide();
            $('#inputbox2 .ycpwd').css('display', 'block').val($('#inputbox .xspwd,.inputbox .xspwd').val());
        }
    });

});