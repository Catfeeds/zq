$(function(){
	$('.cj').luckDraw({
		line:3, //几行
		list:3, //几列
		click:".bt" //点击对象
	});
	redrawBtn();
	$(".close_btn").click(function () {
	    $(".dialog").hide();
	});
});

/*function checkFormAward() {
    var sMobile = $(".frm-award input[name=mobile]").val();
    if (sMobile == "") {
        alert("未输入手机号码");
        return false;
    } else {
        return true;
    }
}*/
/*function redrawBtn() {
    $(".bt").css({ "width": $(".cj li").width(), "height": $(".cj li>div").height(), "line-height": $(".cj li>div").height() + "px" });
}*/
function redrawBtn() {
    $(".bt").css({ "width": 28+ "%", "height": 28+ "%"});
}
window.onresize = function () {
    redrawBtn();
};

$.fn.extend({
	luckDraw:function(data){
		var anc = $(this); //祖父元素
		var list = anc.children("li");
		var click; //点击对象
		var lineNumber; //几行 3
		var	listNumber; //几列 4
		var thisWidth;
		var thisHeight;
		if(data.line==null){return;}else{lineNumber=data.line;}
		if(data.list==null){return;}else{listNumber=data.list;}
		if(data.click==null){return;}else{click=data.click;}

		var all = listNumber*lineNumber - (lineNumber-2)*(listNumber-2)  //应该有的总数
		if(all>list.length){ //如果实际方块小于应该有的总数
			for(var i=0;i<(all-list.length);i++){
				anc.append("<li>"+ parseInt(list.length+i+1)+"</li>");
			}
		}
		
		list = anc.children("li");

		list.each(function(index){
			if(index+1 > all){
				$(this).remove();
			}
		});
		var ix = 0;
		var speed = 100;
		var Countdown = 1000; //倒计时
		var isRun = false;
		var dgTime = 200;


		$(click).click(function(){
			if(isRun){
				return;
			}else{
				var endTime  = Math.round(new Date().getTime()/1000);
				if(endTime > 1468857600){
					showMsg('更多活动<br/>敬请期待','error');
					return;
				}
				var userid = $("#userid").val();
				if(userid == ''){
				    showMsg('请先登录','error');
				    return;
				}
				var gacha_times = $('#gacha_times').text();
				if(gacha_times <= 0){
				    showdiv();
				    return;
				}
				$.ajax({
				    type:"post",
				    url : "/Etc/getPrize.html",
				    data:{userid:userid},
				    dataType:'json',
				    success: function(data){
				        if(data.status==1){
							//中奖位置
							var conf = data.info;
				            dgTime += conf.rid*10 + 80;
				            uniform();
				            $("#gacha_times").text(conf.gacha_times);
				            $('#gacha_id').val(conf.gacha_id);
				            return;
				        }else{
				            showMsg(data.info,'error');
				            return;
				        }
				    }
				});
				//var stime = Math.floor(Math.random()*8+1);      //8为奖项数目
				//$('.zt').html('已点击，结果是数字<span> '+stime+' </span>号中奖');  ///可注释掉
				//speedUp();
			}
		});
		function speedUp(){ //加速
			isRun = true;
			list.removeClass("adcls");
			list.eq(ix).addClass("adcls");
			ix++;
			init(ix);
			speed -= 50;
			if(speed == 100){
				clearTimeout(stop);
				uniform();
			}else{
				var stop = setTimeout(speedUp,speed);
			}
		}
		function uniform() { //匀速
		    isRun = true;
			list.removeClass("adcls");
			list.eq(ix).addClass("adcls");
			ix++;
			init(ix);
			Countdown -= 50 ;
			if(Countdown == 0){
				clearTimeout(stop);
				speedDown();
			}else{
				var stop = setTimeout(uniform,speed);
			}
		}
		function speedDown(){ //减速
			list.removeClass("adcls");
			list.eq(ix).addClass("adcls");
			ix++;
			init(ix);
			speed += 10;
			if(speed == dgTime+20){
				clearTimeout(stop);
				end();
			}else{
				var stop = setTimeout(speedDown,speed);
			}
		} 
		function end(){
			if(ix == 0){
				ix = 8;    //此处需要与设立的奖项数量相同
			}

			var cpImg = $('.cj li.adcls > div').children("p:nth-child(1)").find("img").attr("src");
			var cpName = $('.cj li.adcls > div').children("p:nth-child(2)").text();
			$('.jieguo').find('.cpName').text(cpName);
			$('.jieguo').find('img').attr('src',cpImg);
			if(ix == 5 || ix == 7){
				//积分到账提示
				$('.jieguo').find('.tishi').text('积分已到账，详情请查看积分明细');
				$('.jieguo').find('.ajax_btn').html("<a href=\"javascript:;\" onclick=\"$(this).parents('.dialog').hide()\">继续抽奖</a>");
			}
			if(ix == 3 || ix == 6){
				//流量充值
				$('.jieguo').find('.tishi').text('请点击请输入充值手机号');
				$('.jieguo').find('.ajax_btn').html("<a href=\"javascript:;\" onclick=\"$(this).parents('.dialog').hide();$('#flow,#bg').show()\">填写手机号</a>");
			}

			$('.jieguo').parent(".dialog").show();  ///抽奖结果

			initB();
		}
		///--归0
		function init(o){
			if(o == all){
				ix = 0;	
			}
		}
		///
		function initB(){
			ix = 0;
			dgTime = 200;
			speed = 100;
			Countdown = 1000;
			isRun = false;
		}
	}
});   
