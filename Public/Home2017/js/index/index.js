/**
 * 首页2017 js修订版
 * @author Chensiren <245017279@qq.com>
 * @since  2017-04-10
*/
$(function(){
    //banner
    $('.focus-banner').hover(function(e) {
        $('.carousel-control').stop().fadeIn(500);
    },function(){
        $('.carousel-control').stop().fadeOut(500);
    });
    $('.carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });
    //banner2
    $('#myGs01,#myGs02,#myGs03').carousel('cycle');

    //tab
	$('.tabNav ul li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        if(myNum == 4){
            $.ajax({
                type:'get',
                url :'/Index/getIntroFollow.html',
                dataType:'json',
                beforeSend:function(){
                    var load = '<div class="text-999 loadp" style="text-align: center;padding-top: 50px;">'+
                                    '<span><img src="/Public/Mobile/images/load.gif"></span>'+
                                    '<span style="margin-left: 5px;">数据加载中，请稍候......</span>'+
                                '</div>';
                    $(".followList").html(load);
                },
                success:function(data){
                    var code = data.code;
                    switch(code){
                        case 1:
                            var data = data.data;
                            var html = '<ul class="clearfix">';
                            $.each(data,function(k,v){
                                html += '<li>'+
                                            '<a href="/Intro/intro_info/id/'+v['product_id']+'.html" target="_blank" class="uIforBox clearfix">'+
                                                '<div class="clearfix uIforTop">'+
                                                    '<div class="pull-left uIforImg"><img src="'+v['logo']+'" alt=""></div>'+
                                                    '<div class="pull-left uIfor">'+
                                                        '<h4>'+v['name']+'</h4>'+
                                                        '<p class="text-999">累计回报率:<strong class="text-red">'+v['total_rate']+'%</strong></p>'+
                                                    '</div>'+
                                                '</div>'+
                                                '<p class="text-666 uInt">'+v['desc']+'...<span class="text-blue"></span></p>'+
                                                '<div class="rateIcon rate'+v['ten_num']+'"></div>'+
                                            '</a>'+
                                            '<div class="uIforBottom">'+
                                               '<div class="pull-left carLeft">'+
                                                    '<div class="text-999 carNum">购买人数</div>'+
                                                    '<div class="perCon clearfix">'+
                                                        '<div class="pull-left percent"><div class="expand" style="width: '+v['buyPercent']+'%"></div></div>'+
                                                        '<div class="pull-left">'+v['buyNum']+'/'+v['total_num']+'</div>'+
                                                    '</div>'+
                                                '</div>'+
                                                '<a href="javascript:;" class="pull-right text-666 carRight"><strong>'+v['sale']+'金币/'+v['game_num']+'</strong>场</a>'+
                                            '</div>'+
                                        '</li>';
                            })
                            html += '</ul>';
                        break;
                        case 2:
                            var html = '<div class="uState">'+
                                            '<div class="uImg text-center"><img src="/Public/Home/images/index/uImg.png"></div>'+
                                            '<p class="text-999 text-center">您还未登陆</p>'+
                                            '<a href="/User/login.html" class="btn btn-blue">立即登陆>></a>'+
                                        '</div>';
                        break;
                        case 3:
                            var html = '<div class="uState">'+
                                            '<div class="uImg text-center"><img src="/Public/Home/images/index/pImg.png" alt=""></div>'+
                                            '<p class="text-999 text-center">暂无任何关注</p>'+
                                            '<a href="/Intro.html" target="_blank" class="btn btn-blue">赶紧去关注>></a>'+
                                        '</div>';
                        break;
                    }
                    $('.followList').html(html);
                },
                complete:function(){
                   $(".loadp").remove();
                },
            })
        }
        $('.tabContent .tabItem').eq(myNum).show().siblings().hide();
    });
	
	$('.qbTab ul li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.newsCon .newsBig01').eq(myNum).show().siblings().hide();
    });
	
	$('.zxTab ul li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.newsCon .newsBig02').eq(myNum).show().siblings().hide();
    });
	
	
    $('.match-tab li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var myNum = $(this).index();
        $('.tabCon .tab-content').eq(myNum).show().siblings().hide();
    });
    $('.time-tab-one li').hover(function(e) {
        $(this).addClass('current').siblings().removeClass('current');
        var myNum = $(this).index();
        $('.rank-list-con-one .rank-list').eq(myNum).show().siblings().hide();
    });
    $('.time-tab-two li').hover(function(e) {
        $(this).addClass('current').siblings().removeClass('current');
        var myNum = $(this).index();
        $('.rank-list-con-two .rank-list').eq(myNum).show().siblings().hide();
    });

});