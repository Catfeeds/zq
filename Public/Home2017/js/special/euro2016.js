/**
 * 欧洲杯加JS样式文件
 * 
 * @author Chensiren <245017279@qq.com>
 *
 * @since  2016-05-18
**/
var cityAll = {
    _liindex: [], //当前有比赛的点标识
    _ppindex: 0, //存储当前球场标识
    _addindex: null,
    _scollnum: []
};

var varArr = {
    'titleX': 'right',
    //标题水平方向
    'titleFt': 150,
    //标题初始位置
    'titleFx': 100,
    //标题位置
    'titleY': '128',
    //标题垂直位置
    'mapLeft': 40,
    //地图位置
    'tipsNo': '今日此球场无赛事',
    'tips': '今日赛事',
    'popscrollLeft': 310,
    //弹框隐藏位置
    'popLeft': 540 //弹框显示位置
};
$(function(){
    //导航点击
    $('.nav-list ul li a').eq(0).click(function(e) {
        var cont_sec = $('.cont_sec').offset().top-150;
        $('html,body').animate({'scrollTop':cont_sec},500);
    });
    $('.nav-list ul li a').eq(1).click(function(e) {
        var events = $('.event').offset().top;
        $('html,body').animate({'scrollTop':events},500);
    });
    $('.nav-list ul li a').eq(2).click(function(e) {
        var cont_vds = $('.cont_vds').offset().top;
        $('html,body').animate({'scrollTop':cont_vds},500);
    });
    $('.nav-list ul li a').eq(3).click(function(e) {
        var cityMap = $('.cityMap').offset().top;
        $('html,body').animate({'scrollTop':cityMap},500);
    });
    $('.nav-list ul li a').eq(4).click(function(e) {
        var history = $('.history').offset().top;
        $('html,body').animate({'scrollTop':history},500);
    });
    //焦点图轮播
    $('.cont_sec_left').hover(function(e) {
        $('.carousel-control').stop().fadeIn(500);
        },function(){
            $('.carousel-control').stop().fadeOut(500);
        });
        $('.carousel-control').hover(function(e) {
            $(this).animate({"opacity":"0.75"},200);
        },function(){
           $(this).animate({"opacity":"0.5"},200);
    });
    //淘汰赛切换
    $('.mach-tab01 li').hover(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.tabCon0 .mach-tab-con').eq(myNum).show().siblings('div.mach-tab-con').hide();
    });
    //小组赛切换
    $('.mach-tab li').hover(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.tabCon01 .mach-tab-con').eq(myNum).show().siblings('div.mach-tab-con').hide();
    });
    //积分切换
    $('.mach-tab02 li').hover(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.tabCon02 .mach-tab-con').eq(myNum).show().siblings('div.mach-tab-con').hide();
    });
    //大标题切换
    $('.nav-tab li').hover(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.event_con_left .tabCon').eq(myNum).show().siblings('div.tabCon').hide();
    });
    //历史回顾
    $('.history_con ul li a').hover(function(e) {
        $(this).addClass('active').parents('li').siblings().children('a').removeClass('active');
    });
});
//滚动条
$(function(){
    $(".tabBottom03,.tabBottom0").niceScroll({
        cursorcolor: "#194cac",
        cursorborder: "1px solid #194cac",
        horizrailenabled: false,
        cursorfixedheight: 50, 
    });
})
$(function(){
    //返回顶部
    $(window).scroll(function(e) {
        if($(window).scrollTop()>$(window).height()){
            $('.return-top').fadeIn(300);
        }else {
            $('.return-top').fadeOut(300);
        }
    });
    $('.return-top').click(function(e) {
        $('body,html').animate({'scrollTop':'0'},500);

    });
});
//赛事战报切换
$(".event_right ul li").click(function(){
    $(this).addClass('on').siblings().removeClass('on');
    var class_id = $(this).attr('class_id');
    $.ajax({
        type:'post',
        url:'/Special/getEuroNews.html',
        data:{class_id:class_id},
        dataType:'json',
        beforeSend:function(){
            var load = "<li style='text-align: center; padding: 20px 0;'><div class=\"text-999 loadp\">"+
                            "<span style='margin-left: 5px;'>数据加载中，请稍候......</span>"+
                        "</div></li>";
            $('.event_con_right ul').html(load);
        },
        success:function(data){
            $('.event_con_right ul').html(data.info);
        },
        complete:function(){
           $(".loadp").remove();
        },
    })
})
$(function(){
    //城市切换
    $('.city_list li span').mouseover(function(){
        $(this).parent('li').addClass('cp_on').siblings('li').removeClass('cp_on');
        cityAll._addindex = $('.city_list li span').index(this);
        shake(cityAll._addindex);
    });
});
//弹框抖动显示
function shake(num){
    $('.cp_item').eq(num).children('.cp_box').show(400);
    $('.cp_item').eq(num).siblings().children('.cp_box').hide();
    $('.cp_item').eq(num).show().siblings('.cp_item').hide();
    $('.cp_item').eq(num).animate({'width':'424px','height':'272px','top':'145px','left':varArr.popLeft+'px','opacity':'1'},250).siblings().css({'width':'0px','height':'0','top':'460px','left':varArr.popscrollLeft+'px','opacity':'0'});
    if(cityAll._ppindex != num) {
        $('.cp_item').eq(num).queue(function () {
            $(this).animate({left: (varArr.popLeft-10) + 'px'}, 50);
            $(this).animate({left: (varArr.popLeft+2) + 'px'}, 50);
            $(this).animate({left: varArr.popLeft + 'px'}, 50);
            $(this).dequeue();
        })
    }
    cityAll._ppindex = num;
}