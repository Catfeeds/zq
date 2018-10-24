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
    $('.banner').hover(function(e) {
        $('.banner .carousel-control').stop().fadeIn(500);
    },function(){
        $('.banner .carousel-control').stop().fadeOut(500);
    });
    $('.banner .carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });

	//根据专题类型获取直播条
	var specialType = $('.themeBox').attr('type');
	switch(specialType){
		case 'premierleague':
		case 'laliga':
		case 'bundesliga':
		case 'seriea':
		case 'csl':
			getLive(specialType);
		break;
		case 'championsleague':
		case 'afccl':
		case '2018worldcup':
			getLive2(specialType);
		break;
	}
})

if($(".guessLiCon").length > 0){
	//世界杯有奖竞猜滚动
	$(".guessLiCon").mCustomScrollbar({
	    theme: "light-3",
	    autoDraggerLength: true
	});
}

//直播条
function getLive(specialType){
	//获取直播赛事
	$.ajax({
		type:'post',
		data:{type:specialType},
		url: 'getLive.html',
		dataType:'json',
		success:function(data){
			if(data.status == 1){
				//轮次
				var numHtml = '';
				for (var i = 1; i <= data.allNum; i++) {
					if(i < 10){
						i = '0'+i;
					}
					numHtml += '<li class="pull-left"><a href="javascript:;">'+i+'</a></li>';
				}
				$('.matLiveLi .dropdown-menu').html(numHtml);
				//赛事
				var liveHtml = '';
				$.each(data.info,function(k,v){
					var ddHtml = '';
					$.each(v,function(kk,vv){
						var statusStr = '';
						var colorType = 'liveType';
						switch (vv.game_state)
						{
							case 0: statusStr = '未开赛'; colorType = 'noStatType'; break;
						    case 1:
						    case 2:
						    case 3:  
						    case 4:  statusStr = '直播中'; break;
						    case -1: statusStr = '已完赛'; colorType = 'endType'; break;
						    case -10: statusStr = '取消';  break;
						    case -11: statusStr = '待定';  break;
						    case -12: statusStr = '腰斩';  break;
						    case -13: statusStr = '中断';  break;
						    case -14: statusStr = '推迟';  break;
						    default: statusStr = '未开赛'; colorType = 'noStatType'; break;
						}
						var gtime = vv.gtime.split(' ');
						ddHtml += '<dd class="pull-left">'+
	                                    '<a href="'+vv.href+'" target="_blank" class="teamLive">'+
	                                        '<div class="timeBox">'+
	                                            '<span class="time">'+gtime[0]+'</span>'+
	                                            '<span class="text-999"> | </span>'+
	                                            '<span class="time">'+gtime[1]+'</span>'+
	                                        '</div>'+
	                                        '<div class="clearfix teamHome"><span class="teamName text-hidden">'+vv.home_team_name+'</span></div>'+
	                                        '<div class="clearfix score"><span class="text-hidden text-blue">'+vv.score+'</span></div>'+
	                                        '<div class="clearfix teamAway"><span class="teamName text-hidden">'+vv.away_team_name+'</span></div>'+
	                                        '<div class="liveTypeCon clearfix">'+
	                                            '<span class="pull-left rouTime">'+vv.rno+'轮</span>'+
	                                            '<span class="pull-left '+colorType+'">'+statusStr+'</span>'+
	                                        '</div>'+
	                                    '</a>'+
	                                '</dd>';
					})
					liveHtml += '<li class="pull-left liItem">'+
	                                '<dl class="clearfix">'+
	                                    '<dt class="pull-left"><h3>第'+(parseInt(k) + 1)+'轮</h3></dt>'+
	                                    ddHtml+
	                                '</dl>'+
	                            '</li>';
				});
				$('.matLiveLi .liveUl').html(liveHtml);
				gds(500,data.nowNum);//参数1：的意思是调整定时器的切换时间 参数2：是动画滚动的时间
			}
		}
	})
}	

function getLive2(specialType){
	//获取直播赛事
	$.ajax({
		type:'post',
		data:{type:specialType},
		url: 'getLive2.html',
		dataType:'json',
		success:function(data){
			if(data.status == 1){
				//赛事
				setLiveHtml(data,1);
				setLiveHtml(data,2);
			}
		}
	})
}	
//欧冠与亚冠拼接html
function setLiveHtml(data,type){
	if(type == 1){
		info = data.xiaozu;
	}else{
		info = data.taotai;
	}
	var liveHtml = '';
	$.each(info,function(k,v){
		var ddHtml = '';
		$.each(v,function(kk,vv){
			var statusStr = '';
			var colorType = 'liveType';
			switch (vv.game_state)
			{
				case 0: statusStr = '未开赛'; colorType = 'noStatType'; break;
			    case 1:
			    case 2:
			    case 3:  
			    case 4:  statusStr = '直播中'; break;
			    case -1: statusStr = '已完赛'; colorType = 'endType'; break;
			    case -10: statusStr = '取消';  break;
			    case -11: statusStr = '待定';  break;
			    case -12: statusStr = '腰斩';  break;
			    case -13: statusStr = '中断';  break;
			    case -14: statusStr = '推迟';  break;
			    default: statusStr = '未开赛'; colorType = 'noStatType'; break;
			}
			var gtime = vv.gtime.split(' ');
			var score = vv.score.split('-');
			var round = type == 1 ? vv.round+'组' : vv.unionName;
			ddHtml += '<dd class="pull-left">'+
	                        '<a href="'+vv.href+'" target="_blank" class="teamLive">'+
	                            '<div class="timeBox">'+
	                                '<span class="time">'+gtime[0]+'</span>'+
	                                '<span class="text-999"> | </span>'+
	                                '<span class="time">'+gtime[1]+'</span>'+
	                            '</div>'+
	                            '<div class="clearfix teamHome"><span class="teamName text-hidden">'+vv.home_team_name+'</span></div>'+
	                            '<div class="clearfix score"><span class="text-hidden text-blue">'+vv.score+'</span></div>'+
	                            '<div class="clearfix teamAway"><span class="teamName text-hidden">'+vv.away_team_name+'</span></div>'+
	                            '<div class="liveTypeCon clearfix">'+
	                                '<span class="pull-left rouTime">'+round+'</span>'+
	                                '<span class="pull-left '+colorType+'">'+statusStr+'</span>'+
	                            '</div>'+
	                        '</a>'+
	                    '</dd>';
		});
		liveHtml += '<li class="pull-left liItem">'+
	                    '<dl class="clearfix">'+ddHtml+'</dl>'+
	                '</li>';
	});
	if(type == 1){
		$('.matLiveLi .xzUl').html(liveHtml);
		xzMat(500,data.nowNum);
	}else{
		//淘汰赛
		$('.matLiveLi .ttUl').html(liveHtml);
		if(info.length > 0){
			$('#ttMatch').addClass('on').siblings().removeClass('on');
			$('.liveInner .ttUl').show();
			$('.liveInner .xzUl').hide();
			$('.xzRightBtn,.xzLeftBtn').removeClass('show').addClass('hide');
			$('.ttRightBtn,.ttLeftBtn').removeClass('hide').addClass('show');
			ttMat(500,0);
		}else{
			$('#ttMatch').addClass('noClick');
		}
	}
}

//直播条滑动控制
function gds(par2,nowNum){
	var liNum     = $('.liveInner ul li').length;
	var loopNum   = liNum - 1;//轮播图的索引极值
	var stopleft  = loopNum * -976
	var ulWidth   = liNum * 976;
	$('.liveInner ul').css({'width':''+ulWidth+'px'});
	var curRound  = nowNum - 1 ;//当前轮次
	var loopIndex = nowNum - 1;//轮播图的索引值
	var yuanDian  = nowNum - 1;//小圆点的索引值
	var curLeft   = curRound * -976;
	$('.liveInner ul').stop().animate({'left':''+curLeft+'px'},par2);
	$('.roundNum ol li').eq(yuanDian).addClass('current').siblings().removeClass('current');
	$('.matLiveLi .rightBtn').click(function(e) {
		$('.matLiveLi .rightBtn').removeClass('noClick');
		$('.matLiveLi .leftBtn').removeClass('noClick');
        //ol的li切换
		yuanDian++;
		if(yuanDian > loopNum){
			yuanDian = loopNum;
			//return false;
		}
		$('.roundNum ol li').eq(yuanDian).addClass('current').siblings().removeClass('current');
		//控制ul的left值
		loopIndex++;
		if(loopIndex >= loopNum){
			$('.matLiveLi .rightBtn').addClass('noClick');
		}
		if(loopIndex > loopNum){
			//如果大于li数我们的ul的li才到达了极值
			//禁止滑动
			loopIndex = loopNum;
			$('.liveInner ul').css('left',''+stopleft+'px');
		}
		var moveLeft = -976 * loopIndex;// '+moveLeft+'
		$('.liveInner ul').stop().animate({'left':''+moveLeft+'px'},par2);
		
    });	
	
	//左按钮
	$('.matLiveLi .leftBtn').click(function(e) {
		$('.matLiveLi .rightBtn').removeClass('noClick');
		$('.matLiveLi .leftBtn').removeClass('noClick');
        //ol的li执行--
		yuanDian--;
		if(yuanDian < 0){
			yuanDian = 0;
		}
		$('.roundNum ol li').eq(yuanDian).addClass('current').siblings().removeClass('current');
		
		//ul的索引值也是--
		loopIndex--;
		if(loopIndex <= 0){
			$('.matLiveLi .leftBtn').addClass('noClick');
		}
		if(loopIndex < 0){
			loopIndex = 0;
			$('.liveInner ul').css('left','0');
		}
		
		var moveLeft = -976 * loopIndex;// '+moveLeft+'
		$('.liveInner ul').stop().animate({'left':''+moveLeft+'px'},par2);
    });
	
	//点击小圆点控制轮播
	$('.roundNum ol li').click(function(e) {
		$('.matLiveLi .rightBtn').removeClass('noClick');
		$('.matLiveLi .leftBtn').removeClass('noClick');
        $(this).addClass('current').siblings().removeClass('current');
		//控制ul的left值
		var moveLeft = $(this).index() * -976;// '+moveLeft+'
		$('.liveInner ul').stop().animate({'left':''+moveLeft+'px'},par2);
		//一定要把全局变量也一起修改
		loopIndex = $(this).index();
		yuanDian = $(this).index();
		//最后一个和第一个时改变样式
		var liNum = $('.liveInner ul li').length - 1;
		if(loopIndex == liNum){
			$('.matLiveLi .rightBtn').addClass('noClick');
		}
		if(loopIndex == 0){
			$('.matLiveLi .leftBtn').addClass('noClick');
		}
    });
}

//小组赛
function xzMat(par1,xzCurRound){
	var liNum    = $('.liveInner .xzUl li').length,
		halfNum  = liNum/2,
		loopNum  = liNum - 1,//轮播的索引极值
		stopleft = loopNum*-976,
		ulWidth  = liNum*976,
		curRou   = xzCurRound + 1,
		curLeft  = 2*xzCurRound*-976,
		loopIndex = 2*xzCurRound;//轮播的索引值
		
	$('.liveInner .xzUl').css({'width':''+ulWidth+'px'});	
	$('.liveInner .xzUl').css({'left':''+curLeft+'px'},par1);
	$('.rou').html('第'+ curRou +'轮');
	$('.xzLive .xzRightBtn').click(function(e) {
		$('.xzLive .xzLeftBtn').removeClass('noClick');
		$('.xzLive .xzRightBtn').removeClass('noClick');

		loopIndex++;
		if(loopIndex >= loopNum){
			$('.xzLive .xzRightBtn').addClass('noClick');
		}
		if(loopIndex > loopNum){
			//如果大于li数
			//禁止滑动
			loopIndex = loopNum;
			//$('.liveInner .xzUl').css('left',''+stopleft+'px');
			$('.liveInner .xzUl').stop().animate({'left':''+stopleft+'px'},par1);
			$('.xzLive .xzRightBtn').addClass('noClick');
		}
		var moveLeft = -976 * loopIndex;// '+moveLeft+'
		$('.liveInner .xzUl').stop().animate({'left':''+moveLeft+'px'},par1);
		xzCurRound = curRou - 1;
		var moveNum = Math.abs(moveLeft) + 976;
		var moveRou	= Math.round(moveNum/1952);
		curLeft = moveNum -976;
		$('.rou').html('第'+ moveRou +'轮')
    });	
	//左按钮
	$('.xzLive .xzLeftBtn').click(function(e) {
		$('.xzLive .xzLeftBtn').removeClass('noClick');
		$('.xzLive .xzRightBtn').removeClass('noClick');

		loopIndex--;
		if(loopIndex <= 0){
			$('.xzLive .xzLeftBtn').addClass('noClick');
		}
		if(loopIndex < 0){
			//如果小于0个li数
			//禁止滑动
			loopIndex = 0;
			//$('.liveInner .xzUl').css('left','0');
			$('.liveInner .xzUl').stop().animate({'left':'0px'},par1);
			$('.xzLive .xzLeftBtn').addClass('noClick');
		}
		
		var moveLeft = -976 * loopIndex;// '+moveLeft+'
		$('.liveInner .xzUl').stop().animate({'left':''+moveLeft+'px'},par1);
		xzCurRound = curRou - 1;
		var moveNum = Math.abs(moveLeft) + 976;
		var moveRou	= Math.round(moveNum/1952);
		curLeft = moveNum -976;
		if(moveRou == 0){
			moveRou = 1
		};
		$('.rou').html('第'+ moveRou +'轮')
    });
}	

//淘汰赛
function ttMat(par2,ttCurRound){
	var liNum = $('.liveInner .ttUl li').length,
		loopNum = liNum - 1,//轮播的索引极值
		stopleft = loopNum*-976,
		ulWidth = liNum*976,
		curLeft = ttCurRound*-976,//滚动一次的距离
		loopIndex = 0;//轮播的索引值

	$('.liveInner .ttUl').css({'width':''+ulWidth+'px'});
	$('.liveInner .ttUl').css({'left':''+curLeft+'px'},par2);

	$('.ttLive .ttRightBtn').click(function(e) {
		$('.ttLive .ttLeftBtn').removeClass('noClick');
		$('.ttLive .ttRightBtn').removeClass('noClick');
		// e.preventDefault();
		loopIndex++;
		if(loopIndex >= loopNum){
			$('.ttLive .ttRightBtn').addClass('noClick');
		}
		if(loopIndex > loopNum){
			//如果大于li数
			//禁止滑动
			loopIndex = loopNum;
			//$('.liveInner .ttUl').css('left',''+stopleft+'px');
			$('.liveInner .ttUl').stop().animate({'left':''+stopleft+'px'},par2);
		}
		var moveLeft = -976 * loopIndex;// '+moveLeft+'
		$('.liveInner .ttUl').stop().animate({'left':''+moveLeft+'px'},par2);
		ttCurRound = loopNum;		
    });	
	//左按钮
	$('.ttLive .ttLeftBtn').click(function(e) {
		$('.ttLive .ttLeftBtn').removeClass('noClick');
		$('.ttLive .ttRightBtn').removeClass('noClick');
		// e.preventDefault();
		loopIndex--;
		if(loopIndex <= 0){
			$('.ttLive .ttLeftBtn').addClass('noClick');
		}

		if(loopIndex < 0){
			//如果小于0个li数
			//禁止滑动
			loopIndex = 0;
			//$('.liveInner .ttUl').css('left','0');
			$('.liveInner .ttUl').stop().animate({'left':'0px'},par2);
		}
		
		var moveLeft = -976 * loopIndex;// '+moveLeft+'
		$('.liveInner .ttUl').stop().animate({'left':''+moveLeft+'px'},par2);
		ttCurRound = loopNum;
    });
}

//关进联赛直播栏
$('#xzMatch').on('click', function() {
	$(this).addClass('on').siblings().removeClass('on');
	$('.liveInner .xzUl').show();
	$('.liveInner .ttUl').hide();
	$('.xzRightBtn,.xzLeftBtn').removeClass('hide').addClass('show');
	$('.ttRightBtn,.ttLeftBtn').removeClass('show').addClass('hide');
})
$('#ttMatch').on('click', function() {
	var liNum = $('.liveInner .ttUl li').length;
	if(liNum == 0){
		return;
	}
	$(this).addClass('on').siblings().removeClass('on');
	$('.liveInner .ttUl').show();
	$('.liveInner .xzUl').hide();
	$('.xzRightBtn,.xzLeftBtn').removeClass('show').addClass('hide');
	$('.ttRightBtn,.ttLeftBtn').removeClass('hide').addClass('show');
})

//锚点切换
var hash = window.location.hash.substr(1);
if(hash != ''){
    $('.navList ul li[data-classid='+hash+']').click();
}

//资讯切换
function changeNews(obj){
	if($(obj).hasClass('active')){
		return false;
	}

	$(obj).addClass('active').siblings().removeClass('active');
	var classid = $(obj).data('classid');
	var top     = $(obj).data('top');
	if(top == 'all'){
		$('.matListBox .newsAll').show();
		$('.matListBox .ajaxLoadNews').hide();
		return false;
	}
	$('.matListBox .ajaxLoadNews').show();
	$('.matListBox .newsAll').hide();
	$.ajax({
		type:'get',
		url:"/ajaxGetNews.html",
		data:{classid:classid,top:top},
		dataType:'json',
		beforeSend:function(){
		 	$(".matListBox .ajaxLoadNews").html("<li style='text-align: center;' class='load listInfor'><img src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...<li>");
		},
		success:function(data){
			if(data.status == 1){
				if(data.info){
					var news = data.info.news;
					if(news){
						var html = '';
						$.each(news,function(k,vo){
							html += '<li class="clearfix listInfor">'+
			                        	'<a target="_blank" href="'+vo.href+'"  class="pull-left inforImg"><img src="'+vo.img+'" width="180" height="124"></a>'+
			                        	'<div class="pull-left inforArt">'+
				                        	'<a target="_blank" href="'+vo.href+'">'+
				                        		'<h2 class="text-hidden">'+vo.title+'</h2>'+
				                        		'<article class="text-999 inforArtP">'+vo.remark+'</article>'+
				                        	'</a>'+
				                            '<div class="clearfix author">'+
					                        	'<span class="pull-left text-999 leftUser"><a href="'+vo.expert+'" target="_blank" class="text-999"><img class="img-circle" src="'+vo.head+'" width="28" height="28">'+vo.nick_name+'</a>&nbsp;&nbsp;&nbsp;'+vo.add_time+'</span>'+
					                            '<span class="pull-right text-999 rightEye">'+vo.click_number+'</span>'+
				                        	'</div>'+
			                            '</div>'+
		                            '</li>';
						});
						html += '<li class="listInfor"><a target="_blank" href="'+data.info.classUrl+'" class="more">查看更多</a></li>';
						$(".matListBox .ajaxLoadNews").html(html);
					}else{
						$(".matListBox .ajaxLoadNews").html("<li style='text-align: center;' class='listInfor'> 暂时没有数据<li>");
					}
				}else{
					$(".matListBox .ajaxLoadNews").html("<li style='text-align: center;' class='listInfor'> 暂时没有数据<li>");
				}
			}
		},
		complete:function(){
		   $(".matListBox .load").remove();
		   //history.pushState({},'','/');
		},
	})
}

//世界杯竞猜推荐选项
$('.guessItem .itemList a').on('click',function(){
	if(userLoginInfo == ''){
	    modalLogin();
	    return;
	}
	if($('.singleSubmit').hasClass('on')){
		return;
	}
	$(this).addClass('on').siblings().removeClass('on');
})

//查看更多与查看结果跳转
$('.guessMore,.look').on('click',function(){
    if(userLoginInfo == ''){
        modalLogin();
        return;
    }
    var url = $(this).data('url');
    window.open(url);
})

//世界杯有奖竞猜提交
$(document).on('click',".singleSubmit",function (e) {
    if(userLoginInfo == ''){
        modalLogin();
        return;
    }
    if($('.itemList a.on').length != $('.itemList').length){
        _alert('提示','请继续答完本轮的全部题目，才能成功提交哦！');
        return;
    }

    var strData = new Array();
    $('.itemList a.on').each(function (k,v) {
    	var singid = $(this).parents('.guessItem').data('singid');
        var quizid = $(this).parent().data('quizid');
        var aid = $(this).data('aid');
        strData[k] = singid+":"+quizid+":"+aid;
    });

    $.ajax({
        type: 'post',
        async : false,
        url: "/requestGamble.html",
        data:{strData:strData.toString(),titleid:$('.guessLiCon').data('titleid')},
        dataType: 'json',
        beforeSend:function(XMLHttpRequest){
            $('.singleSubmit').text('提交中...');
        },
        success: function (data) {
            if(data.status == 1){
                _alert('提示',data.info);
                $('.singleSubmit').removeClass('singleSubmit').addClass('on').text('已提交');
            }else{
                _alert('提示',data.info);
                $('.singleSubmit').text('提交');
            }
        },
    });
})

//世界杯积分榜切换
$('.wcTheme .groupList a').on('click',function(){
	$(this).addClass('on').siblings().removeClass('on');
	var sign = $(this).text();
	var tbody = $('.poiTableCon tbody.'+sign);
	tbody.removeClass('hide').siblings('tbody').addClass('hide');
	if($('.poiTableCon tbody.'+sign+' tr').length == 0){
		$.ajax({
		    type: 'get',
		    url : '/getWorldCupRank.html',
		    data: {unionId:75,sign:sign},
		    dataType:'json',
		    beforeSend:function(){
		        tbody.html("<tr style='text-align: center;'><td colspan='4'><img src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...</td><tr>");
		    },
		    success:function(data){
		        if(data.status == 1){
		            if(data.info){
		                var html = '';
		                $.each(data.info,function(k,vo){
		                    var color = vo.rank <= 3 ? 'redRank' : 'grayRank';
		                    html += '<tr>'+
                                        '<td><span class="noRank '+color+'">'+vo.rank+'</span></td>'+
                                        '<td title="'+vo.team_name+'"><p class="playerName text-hidden">'+vo.team_name+'</p></td>'+
                                        '<td>'+vo.count+'</td>'+
                                        '<td class="strong">'+vo.int+'</td>'+
                                    '</tr>';
		                });
		                tbody.html(html);
		            }else{
		                tbody.html("<li style='text-align: center;' class='listInfor'> 暂时没有数据<li>");

		            }
		        }
		    },
		    complete:function(){
		       $("#xibu .load").remove();
		    },
		})
	}
})