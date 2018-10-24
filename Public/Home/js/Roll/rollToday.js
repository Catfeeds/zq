/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 * 
 * @since  2018-01-10
 *
**/
$(function(){
	//banner
    $('.banner01').hover(function(e) {
        $('.banner01 .carousel-control').stop().fadeIn(500);
    },function(){
        $('.banner01 .carousel-control').stop().fadeOut(500);
    });
    $('.banner01 .carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });
	
	//赛事切换
	$('.raceNav ul li').click(function(e) {
        var numIndex = $(this).index();
		$(this).addClass('active').siblings().removeClass('active');
		$('.raceCon .raceList').eq(numIndex).removeClass('hide').addClass('show').siblings().removeClass('show').addClass('hide');
    });
	$(".raceList .raceMain ul li").click(function() {
		$(this).addClass("active").siblings().removeClass("active");
		var t = $(this).parent("ul").find(".popLi .popLiIn");
		if (0 != t.length) {
			var i = $(this).find("div").html(),
				a = $(this).parent("ul").find(".popLi"),
				e = $("i.arrowTop"),
				n = $(this).parent().find("li").index($(this)) + 1,
				s = n % 4 == 0 ? n / 4 * 4 : 4 * Math.floor(n / 4) + 4;
				t.html(i);
			for (var o = 1; o <= 4; o++) {
				var l = $(this).parent().find("li").eq(s - o).length;
				if (0 !== l) {
					$(this).parent().find("li").eq(s - o).after(a);
					break
				}
			}
			var c = $(this).offset().left + $(".raceMain ul li").outerWidth() / 2 - t.offset().left - e.outerWidth() / 2;
			e.css("left", c)
		}
	});
	//今日赛事
	$('.hotRace .panel-title a').click(function(e) {
        $(this).stop().toggleClass('active').parents('div.panel').siblings().find('.panel-title a').removeClass('active');
    });
	//右边球队介绍
	
	//世界杯有奖竞猜滚动
	// $(".introBox").mCustomScrollbar({
	//     theme: "light-3",
	//     autoDraggerLength: true
	// });
	
	//热门专区
	$('.zoneNav li').click(function(e) {
        $(this).addClass('active').siblings().removeClass('active');
		var zoneNum = $(this).index();
		$('.zoneCo .zoneBox').eq(zoneNum).show().siblings().hide();
    });
})

$('#pageLimit').bootstrapPaginator({
	currentPage: newP,
	totalPages: pageCount,
	size: "normal",
	bootstrapMajorVersion: 3,
	alignment: "right",
	numberOfPages: 8,
	itemTexts: function (type, page, current) {
		switch (type) {
			case "first":
				return "首页";
			case "prev":
				return "上一页";
			case "next":
				return "下一页";
			case "last":
				return "末页";
			case "page":
				return page;
		}  //默认显示的是第一页。
	},
	onPageClicked: function (event, originalEvent, type, page) {
		//给每个页眉绑定一个事件，其实就是ajax请求，其中page变量为当前点击的页上的数字。
		// $.ajax({
		// 	url: '/task_list_page/',
		// 	type: 'POST',
		// 	data: {'page': page, 'count': 12},
		// 	dataType: 'JSON',
		// 	success: function (callback) {
		// 		$('tbody').empty();
		// 		var page_count = callback.page_count;
		// 		var page_cont = callback.page_content;
		// 		$('tbody').append(page_cont);
		// 		$('#last_page').text(page_count)
		// 	}
		// })
		var searchData = getData();
		var url = '//www.'+ DOMAIN + '/roll.html';
		if(searchData != '')
		{
			url = url + '?' + searchData + '&p='+page;
		}else{
			url = url + '?p=' +page;
		}

		window.location.href = url;
	}
});

//本小插件支持移动端哦

//这里是初始化
$('.filter-box').selectFilter({
	callBack: function (val) {
		//返回选择的值
		// console.log(val + '-是返回的值')
	}
});
//这里是初始化
$('.filter-box1').selectFilter({
	callBack: function (val) {
		//返回选择的值
		// console.log(val + '-是返回的值')
	}
});

//搜索按钮点击事件
$('.serch-button').on('click',function(){

	var searchData = getData();
	var url = '//www.'+ DOMAIN + '/roll.html';
	if(searchData != '')
	{
		 url = url + '?' + searchData;
	}
	window.location.href = url;
})

//获取传参
function getData()
{
	var searchKey = $('.searchKey').val();//搜索框
	var searchType = $('.searchType option:selected').val();//搜索类型
	var searchTime = $('.searchTime option:selected').val();//搜索时间
	var data = new Array();
	var num = 0;
	if(searchKey.length > 0)
	{
		data[num] = 'searchKey='+encodeURIComponent(searchKey);
		num = num+1;
	}
	if(searchType == '2')
	{
		data[num] = 'type='+searchType;
		num = num+1;
	}
	if(searchTime != '')
	{
		data[num] = 'time='+searchTime;
	}
	return data.join("&");
}

//刷新按钮
$('.freshen').on('click',function(){
	var url = '//www.'+ DOMAIN + '/roll.html';
	window.location.href = url;
})