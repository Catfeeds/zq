//足球排名切换
$('.match-tab li').click(function (e) {
    $(this).addClass('on').siblings().removeClass('on');
    var myNum = $(this).index();
    $('.tabCon .tab-content').eq(myNum).show().siblings().hide();
});
$('.time-tab-one li').hover(function (e) {
    $(this).addClass('current').siblings().removeClass('current');
    var myNum = $(this).index();
    $('.rank-list-con-one .rank-list').eq(myNum).show().siblings().hide();
});
$('.time-tab-two li').hover(function (e) {
    $(this).addClass('current').siblings().removeClass('current');
    var myNum = $(this).index();
    $('.rank-list-con-two .rank-list').eq(myNum).show().siblings().hide();
});
/**
 +----------------------------------------------------------
 * 通用弹出框
 * @param title         提示框标题
 * @param content       提示框内容
 * @param functionName  确定之后执行的js
 +----------------------------------------------------------
 */
 function _alert(title, content, functionName){
    gDialog.fAlert(title,content,function(rs){
        if(functionName){
            eval(functionName);
        }
    });
 }



var hrefUrl = '';
//所有a标签进行验证
$('a').on('click',function()
{
    if($('#login_main').html() == ''){

         var xmlhttpLogin;
         if(window.XMLHttpRequest){
            xmlhttpLogin = new XMLHttpRequest();
         }else{
            xmlhttpLogin =new ActiveXObject(Microsoft.XMLHTTP);
         }
             // xmlhttp.open("get","test.pnp?s="+ Math.random(),true);
             xmlhttpLogin.open("get","loginForm.html",true);
             // xmlhttp.open("get","PublishIndex/analysts.html",true);
             xmlhttpLogin.send();
             xmlhttpLogin.onreadystatechange = function()
             {
               if(xmlhttpLogin.readyState ==4 && xmlhttpLogin.status == 200){
                 // alert(xmlhttp.responseText)
                  document.getElementById("login_main").innerHTML= xmlhttpLogin.responseText;
               }
             }
    }
    if($('#reg_main').html() == '' && $(this).attr('register') == 1){

         var xmlhttpReg;
         if(window.XMLHttpRequest){
            xmlhttpReg = new XMLHttpRequest();
         }else{
            xmlhttpReg =new ActiveXObject(Microsoft.XMLHTTP);
         }
             // xmlhttp.open("get","test.pnp?s="+ Math.random(),true);
             xmlhttpReg.open("get","reisterForm.html",true);
             // xmlhttp.open("get","PublishIndex/analysts.html",true);
             xmlhttpReg.send();
             xmlhttpReg.onreadystatechange = function()
             {
               if(xmlhttpReg.readyState ==4 && xmlhttpReg.status == 200){
                 // alert(xmlhttp.responseText)
                  document.getElementById("reg_main").innerHTML= xmlhttpReg.responseText;
               }
             }
    }

    var register = $(this).attr('register');
    var href = $(this).attr('href');
    if (register == 1) //部分a标签需要登录
    {
        $.ajax({
            type:'get',
            url : window.location.protocol+"//www."+DOMAIN+"/Common/ajaxCheckLogin.html",
            dataType:'jsonp',
            jsonp:'logincallback',
            success:function(result)
            {
                if(result.status == 1){
                    $("#focusText").text('已关注');
                    hrefUrl = href;
                }
                else
                {
                    hrefUrl = '';
                    $('.myLogin').modal('show');
                    return false;
                }

            },
        });
        setTimeout(function () {
            if (hrefUrl != '')
            {
                window.open(href);
            }
        }, 300);

        return false;
    }

});

//点击登陆
var url =  document.domain.replace('www.','').split(".").length-1 > 1 ? '/login.html' : '/Common/login.html';
function submitLogin()
{
    var username = $('#loginname').val();
    var password = $('#nloginpwd').val();
    $.ajax({
            type: "POST",
            url: url,
            data: {'username':username,'password':password},
            dataType:'json',
            success: function(data){
              if(data.status){
                window.location.reload();
                if (hrefUrl != '') {
                   window.open(hrefUrl);
                   return false;
                }

              }else{
                $("input[name='password']").val('');
                var html = "<label class='field-msg field-error' for='nloginpwd'>"+data.info+"</label>";
                $("input[name='password']").after(html);
              }
            }
        });

}
function submitRegiter()
{
   if($("#agree").is(':checked')==false){
        _alert("提示","您必须同意注册协议！");
        return;
      }
      var mobile = $('#phone').val(); //手机
      if(mobile==''){
        $('#phone_tip').removeClass('tip_hide');
        $('#phone_tip').addClass('tip_show');
        return false;
      }else{
        $('#phone_tip').addClass('tip_hide');
      }
      var captcha = $('#ranks').val(); //验证码
      if(captcha==''){
        $('#ranks_tip').removeClass('tip_hide');
        $('#ranks_tip').addClass('tip_show');
        return false;
      }else{
        $('#ranks_tip').addClass('tip_hide');
      }
      var nick_name = $('#nick_name').val();//用户昵称：
      if(nick_name==''){
        $('#nick_name_tip').removeClass('tip_hide');
        $('#nick_name_tip').addClass('tip_show');
        return false;
      }else{
        $('#nick_name_tip').addClass('tip_hide');
      }
      var passd = $('#passd').val(); //密码
      if(passd==''){
        $('#passd_tip').removeClass('tip_hide');
        $('#passd_tip').addClass('tip_show');
        return false;
      }else{
        $('#passd_tip').addClass('tip_hide');
      }
      var com_passd = $('#com_passd').val(); //验证密码
      if(com_passd==''){
        $('#com_passd_tip').removeClass('tip_hide');
        $('#com_passd_tip').addClass('tip_show');
        return false;
      }else{
        $('#com_passd_tip').addClass('tip_hide');
      }
      $.ajax({
          type: "POST",
          url: "seo_register.html",
          data: {'mobile':mobile,'captcha':captcha,'nick_name':nick_name,'passd':passd,'com_passd':com_passd},
          success: function(data){
            if(data.status){
              window.location.reload();
            }
            else
            {
              if (data.info['sign'] == 1) {
                  $('#phone_tip').removeClass('tip_hide');
                  $('#phone_tip').text(data.info['msg']);
                  $('#phone_tip').addClass('tip_show');
              }else if (data.info['sign'] == 2) {
                  $('#ranks_tip').removeClass('tip_hide');
                  $('#ranks_tip').text(data.info['msg']);
                  $('#ranks_tip').addClass('tip_show');
              }else if (data.info['sign'] == 3) {
                  $('#nick_name_tip').removeClass('tip_hide');
                  $('#nick_name_tip').text(data.info['msg']);
                  $('#nick_name_tip').addClass('tip_show');
              }else if (data.info['sign'] == 4) {
                  $('#passd_tip').removeClass('tip_hide');
                  $('#passd_tip').text(data.info['msg']);
                  $('#passd_tip').addClass('tip_show');
              }else if (data.info['sign'] == 5) {
                  $('#com_passd_tip').removeClass('tip_hide');
                  $('#com_passd_tip').text(data.info['msg']);
                  $('#com_passd_tip').addClass('tip_show');
              }else
              {
                _alert('温馨提示',data.info);
              }


            }
          }
      });
}
 /**
 * 发送手机验证码
 *
*/
function sendMobileMsg(){
  var mobile = $('#phone').val();
  //验证手机
  if(mobile == ""){
    _alert('温馨提示',"请输入手机号码！");
    return false;
  }
  if (!/^1[3456789]{1}\d{9}$/.test(mobile)){
    _alert('温馨提示',"手机号码格式不正确，请重新输入！");
    return false;
  }
  $.ajax({
    url: "sendMobileMsg.html",
    type:'post',
    data:{'mobile':mobile,'msgType':'registe'},
    dataType: "json",
    beforeSend:function(XMLHttpRequest)
      {
        $("#sendMobileBtn").parent().html("<span id='sendMobileBtn'></span>正在发送…").removeAttr("onclick").attr("disabled","disabled");
      },
    success: function(data){
      if(data.status){
        _alert('温馨提示',data.info);
        daojishi(60);
        return;
      }else{
        $("#sendMobileBtn").parent().html("<span id='sendMobileBtn'></span>获取验证码").attr("onclick",'sendMobileMsg()').removeAttr("disabled");
        _alert('温馨提示',data.info);
      }
    }
  });
}
/**
 * 倒计时
 *
*/
function daojishi(S){
  if (S>0){
    var S = S-1;
    $("#sendMobileBtn").parent().html("<span id='sendMobileBtn'></span>秒后重发").removeAttr("onclick").attr("disabled","disabled");
    $("#sendMobileBtn").html(S);
    setTimeout("daojishi("+S+")",1000);
    return;
  } else {
    $("#sendMobileBtn").parent().html("<span id='sendMobileBtn'></span>获取验证码").attr("onclick",'sendMobileMsg()').removeAttr("disabled");
  }
}
