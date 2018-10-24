
$(document).ready(function () {
    //关闭悬浮app下载
//    $('.app-close').click(function (e) {
//        Cookie.setCookie('app-show','1');
//        $('.app-bar-con').stop().fadeOut(100);
//    });
 
    //计算a的平均宽度 没间距 - 共用
   // getAvg();
 
})


//计算a的平均宽度 没间距 - 共用
function getAvg() {
    var a_num = $("#get_avg a").length;
    //var a_margin = a_num * 2;  //margin间距
    var a_w = 100 / a_num;
    $("#get_avg a").css("width", a_w + "%")
}

