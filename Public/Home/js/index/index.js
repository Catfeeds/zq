/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 * 
 * @since  2018-01-10
 *
**/
$(function(){
	getLiveGame();
	// fbData(75, 'worldcup', 'jifen', 'A', 0);
	bkData(1, 2, 'nba', 'xibu', 0);

	//banner
    $('.banner').hover(function(e) {
        $('.carousel-control').stop().fadeIn(500);
    },function(){
        $('.carousel-control').stop().fadeOut(500);
    });
    $('.carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
       $(this).animate({"opacity":"0.5"},200);
    });

	$("#fbLista").als({
		visible_items: 10,
		scrolling_items: 5,
		orientation: "horizontal",
		circular: "yes",
		autoscroll: "no",
		interval: 5000,
		speed: 500,
		easing: "linear",
		direction: "left",
		start_from: 0
	});
	$('.fbTable').on('click', '.next' ,function(){
		var left = $('.fbTable .als-wrapper .als-item').eq(0).css('left');

		if(left == '0px' && '254px' != $('.fbTable .scrollWrap').css('width') ){
			$('.fbTable .scrollWrap').css({'width':'254px'});
			$('.fbTable .ladyScroll .prev').css({'display':'block'});
		}else if(left == '44px' && '254px' == $('.fbTable .scrollWrap').css('width')	){
			$('.fbTable .scrollWrap').css({'width':'274px'});
			$('.fbTable .ladyScroll .prev').css({'display':'none'});
		}
	});
	$('.fbTable').on('click', '.prev' ,function(){
		var left = $('.fbTable .als-wrapper .als-item').eq(0).css('left');

		if(left == '0px' && '254px' != $('.fbTable .scrollWrap').css('width') ){
			$('.fbTable .scrollWrap').css({'width':'254px'});
			$('.fbTable .ladyScroll .prev').css({'display':'block'});
		}else if(left == '-44px' && '254px' == $('.fbTable .scrollWrap').css('width')	){
			$('.fbTable .scrollWrap').css({'width':'274px'});
			$('.fbTable .ladyScroll .prev').css({'display':'none'});
		}
	});

	//各联赛足球积分榜和射手榜
	$('#fbLista .scrollWrap li a').click(function(e) {
		var unionId  = $(this).attr('unionId');
		jifenData(unionId,'A');
		if(unionId == 103 || unionId == 192 || unionId == 75){
			$('.groupList').show();
		}else{
			$('.groupList').hide();
		}
		$(".fbTabNva a").removeClass('on');
		$(".fbTabNva a:eq(0)").addClass('on');
		$('.fbTableCon thead tr.jifen').show().siblings().hide();
		$(".groupList a:eq(0)").addClass('on').siblings('a').removeClass('on');
		$(this).addClass('active').parents().siblings().children('a').removeClass('active');
	});

	//分组切换
	$('.fbTable .groupList').on('click', 'a', function(e) {
		var unionId = $("#fbLista ul li a.active").attr('unionId');
		var group   = $.trim($(this).text());

		jifenData(unionId,group);

		$(this).addClass('on').siblings('a').removeClass('on');
	});

	//积分射手榜切换
	$('.fbTabNva li a').on('click',function(){
		var unionId = $("#fbLista ul li a.active").attr('unionId');
		var tableType = $(this).data('type');
		$('.fbTableCon thead tr.'+tableType).show().siblings().hide();
		if(tableType == 'sheshou'){
			$('.groupList').hide();
			sheshouData(unionId);
		}else{
			if(unionId == 103 || unionId == 192 || unionId == 75){
				$('.groupList').show();
			}
			
			jifenData(unionId,'A');
			$(".groupList a:eq(0)").addClass('on').siblings('a').removeClass('on');
		}
		$(this).addClass('on').parents().siblings().children('a').removeClass('on');
	})

	//积分榜切换
	function jifenData(unionId, group){
		$.ajax({
			type: 'post',
			url: "/Index/getLeagueData.html",
			data: {unionId: unionId, group: group},
			dataType: 'json',
			beforeSend:function(){
				$(".fbTableCon tbody").html("<tr style='text-align: center;'><td colspan='4'><img class='load' src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...</td><tr>");
			},
			success: function (data) {
				if (data.status == 1) {
					var info = data.info;
					var tr = '';
					$.each(info,function(k,v){
						var bgcolor   = '';
						var rankClass = '';
						var matchLogo = '';
						switch(v.rank){
							case '1': 
								rankClass = "noRank noOne";
								bgcolor   = "bgcolor='#fafafa'";
								matchLogo = '<div class="matchLogo"><img src="'+v.team_logo+'" width="48" height="48"></div>';
								break;
							case '2': rankClass = "noRank noTwo";break;
							case '3': rankClass = "noRank noThree";break;
							case '4': rankClass = "noRank noFour";break;
						}
						tr += "<tr "+bgcolor+">"+
                                    "<td><strong class='"+rankClass+"'>"+v.rank+"</strong></td>"+
                                    "<td>"+matchLogo+
                                        '<p class="teamName text-hidden" title="'+v.team_name+'"><a href="'+v.url+'" target="_blank" title="'+v.team_name+'">'+v.team_name+'</a></p>'+
                                    '</td>'+
                                    '<td>'+v.win+'/'+v.draw+'/'+v.lose+' </td>'+
                                    '<td><strong> '+v.int+'</strong></td>'+
                                "</tr>";
					})
					$(".fbTableCon tbody").html(tr);
				}else{
					layer.msg(data.info);
				}
			},
			complete:function(){
				$(".fbTableCon tbody .load").parent().parent().remove();
			},
		});
	};

	//射手榜切换
	function sheshouData(unionId){
		$.ajax({
			type: 'post',
			url: "/Index/getArcherData.html",
			data: {unionId: unionId},
			dataType: 'json',
			beforeSend:function(){
				$(".fbTableCon tbody").html("<tr style='text-align: center;'><td colspan='4'><img class='load' src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...</td><tr>");
			},
			success: function (data) {
				if (data.status == 1) {
					var info = data.info;
					var tr = '';
					$.each(info,function(k,v){
						var bgcolor   = '';
						var rankClass = '';
						var matchLogo = '';
						var playerLogo = '';
						switch(v.rank){
							case '1': 
								rankClass = "noRank noOne";
								bgcolor   = "bgcolor='#fafafa'";
								matchLogo = '<div class="matchLogo"><img src="'+v.team_logo+'" width="48" height="48"></div>';
								playerLogo = '<div class="playerLogo"><img src="'+v.player_logo+'" width="48" height="48"></div>';
								break;
							case '2': rankClass = "noRank noTwo";break;
							case '3': rankClass = "noRank noThree";break;
							case '4': rankClass = "noRank noFour";break;
						}
						tr += '<tr '+bgcolor+'>'+
                                    '<td><strong class="'+rankClass+'">'+v.rank+'</strong></td>'+
                                    '<td>'+playerLogo+
                                        '<p class="playerName text-hidden" title="'+v.player_name+'"><a target="_blank" title="'+v.player_name+'" href="'+v.p_url+'">'+v.player_name+'</a></p>'+
                                    '</td>'+
                                    '<td>'+matchLogo+
                                        '<p class="teamName" title="'+v.team_name+'"><a target="_blank" title="'+v.team_name+'" href="'+v.t_url+'">'+v.team_name+'</a></p></td><td><strong> '+v.val+'</strong>'+
                                    '</td>'+
                                '</tr>';
					});
					$(".fbTableCon tbody").html(tr);
				}else{
					layer.msg(data.info);
				}
			},
			complete:function(){
				$(".fbTableCon tbody .load").parent().parent().remove();
			},
		});
	}

	//篮球切换
	$('.bsTable .scrollWrap li a').click(function(e) {
		var unionId = $(this).attr('unionId');
		var dataType = $(this).data('type');

		if(unionId == 1){
			var id = 'xibu';
		}else{
			var id = 'liansai';
		}

		bkData(unionId, 2, dataType, id, 1);
		$(this).addClass('active').parents().siblings().children('a').removeClass('active');
	});

	//篮球胜率榜切换
	function bkData(unionId, type, dataType, id, isOpen) {
		if(unionId == undefined || type == undefined ||dataType == undefined){
			return false;
		}

		if(unionId == 1){
			$('.bsTabNva .nba-class-ul').show();
			$('.bsTabNva .nba-class-ul li:eq(0) a').addClass('on').parents().siblings().children('a').removeClass('on');
			$('.bsTabNva .cba-class-ul').hide();
		}else{
			$('.bsTabNva .nba-class-ul').hide();
			$('.bsTabNva .cba-class-ul li:eq(0) a').addClass('on').parents().siblings().children('a').removeClass('on');
			$('.bsTabNva .cba-class-ul').show();
		}

		getBkData(unionId, type, dataType, id, isOpen);
	}

	//积分榜等
	$('.bsTabNva li a').click(function(){
		var unionId = $('.bsTable .scrollWrap li a.active').attr('unionId');
		var type = $(this).attr('type');
		var dataType = $('.bsTable .scrollWrap li a.active').data('type');
		var id = $(this).data('id');

		if(unionId == undefined || type == undefined){
			return false;
		}

		if($("#bk-table-"+dataType+'-'+id).children().length > 0){
			$("#bk-table-"+dataType+'-'+id).show().siblings('table').hide();
			$(this).addClass('on').parents().siblings().children('a').removeClass('on');
			return false;
		}

		getBkData(unionId, type, dataType, id, 1);

		$(this).addClass('on').parents().siblings().children('a').removeClass('on');
	});

	function getBkData(unionId, type, dataType, id, isOpen){
		$("#bk-table-"+dataType+'-'+id).show().siblings('table').hide();
		$.ajax({
			type: 'post',
			url: "/Index/getBkData.html",
			data: {unionId: unionId, type: type},
			dataType: 'json',
			beforeSend: function(){
				$("#bk-table-"+dataType+'-'+id).html("<tr style='text-align: center;'><td colspan='5'><img src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...</td><tr>");
			},
			success: function(data) {
				if (data.status == 1) {
					$("#bk-table-"+dataType+'-'+id).html(data.info);
					$("#bk-table-"+dataType+'-'+id).show().siblings('table').hide();
				}else{
					if(isOpen){
						layer.msg(data.info);
					}
				}
			},
			complete: function(){
				$("#bk-table-"+dataType+'-'+id+" .load").remove();
			},
		});
	};


	function getLiveGame(){
		$.ajax({
			type: 'post',
			url: "/Index/getLiveGames.html",
			data: {},
			dataType: 'json',
			success: function (data) {
				if (data.status == 1) {
					$(".live-game-div .dlList").html(data.info);

					//比赛列表
					$("#lista1").als({
						visible_items: 5,
						scrolling_items: 5,
						orientation: "vertical",
						circular: "yes",
						autoscroll: "no",
						interval: 5000,
						speed: 500,
						easing: "linear",
						direction: "down",
						start_from: 0
					});
				}
			}
		});
	};

})