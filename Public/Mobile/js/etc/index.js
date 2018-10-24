	 //Marquee通告
	// $('#marquee').kxbdSuperMarquee({
	// 	isMarquee:true,
	// 	isEqual:false,
	// 	scrollDelay:30,
	// 	controlBtn:{up:'#goUM',down:'#goDM'},
	// 	direction:'left'
	// });
	//banner 切换
	var swiper = new Swiper('.swiper-container', {
		pagination: '.swiper-pagination',
		paginationClickable: true,
		spaceBetween: 30,
		centeredSlides: true,
		autoplay: 8000,
		autoplayDisableOnInteraction: false
	});
	//竞猜选择
	$(function(){
		$('.mach_list .mach_btn a:nth-child(1),.mach_list .mach_btn a:nth-child(2),.mach_list .mach_btn a:nth-child(3)').click(function(e) {
			if($(this).hasClass('on')){
				$(this).removeClass('on');
				$(this).children('i.icon-tik-red').remove();
			}else{
				$(this).addClass('on').siblings().removeClass('on');
				$(this).append('<i class="icon-tik-red"></i>');
				$(this).addClass('on').siblings().children('i.icon-tik-red').remove();
			}
		});
		//弹出层
		//showdiv();
		//hidediv();
		$(document).on('click','.submit',function() {
                    var type=$('#gtype').val();
                    var gid=$('#gid').val();
                    var coin=$('#yb').val();
                    if(Number(coin)==0 || !(Number(coin)%100==0) || Number(coin)>5000){
                        $('.failTips').html('输入100的倍数且不大于5000!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
			$('#yb').val('');
                        return false;
                    }
                    if(Number(type)<1 || Number(type)>3){
                        $('.failTips').html('参数有误,请重新选择!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                        return false;
                    }
                    if(gid==undefined || gid=='' || gid==0){
                        $('.failTips').html('参数有误,请重新选择!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                        return false;
                    }
                    var params={
                        type:type,
                        coin:coin,
                        game_id:gid
                    };
                  $.ajax({
                    type:"post",
                    url : "/Etc/betting.html",
                    data:params,
                    dataType:'json',
                    success: function(data){
                        hidediv();
                        if(data.status==1){
                            $("#u_coin").html(data.info);
                            $('.bubbleTips').html('竞猜成功').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                        }else{
                            $('.failTips').html(data.info).stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                        }
                    }
                });
                        
        });
		
	});
	//弹出层
	function showdiv(othis) {
                var type=$(othis).siblings('.on').data('type');
                if(type==undefined){
                    $('.failTips').html('请选择要竞猜的类型!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                    return false;
                }
                var $par=$(othis).parents('li');
                if($par.data('gid')==undefined || $par.data('gid')=='' || $par.data('gid')==0){
                    $('.failTips').html('参数有误,请重试!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                    return false;
                }
                $('#gtype').val(type);
                $('#gid').val($par.data('gid'));
                var get_rsl=$(othis).siblings('.on').children().eq(0).html();
                var odds=$(othis).siblings('.on').children().eq(1).html();
                $('#bet_type').html(get_rsl+odds);
                $('#game_time').html($par.find('.gtime').html());
                var let=$par.find('#js-let').html();
                var let_html='';
                if(let!=undefined){
                    let_html+='(<em class="red">'+let+'</em>)';
                }
                $('#home_name').html($par.find('.home_name').html()+let_html);
                $('#away_name').html($par.find('.away_name').html());
		document.getElementById("bg").style.display ="block";
		document.getElementById("show").style.display ="block";
		/*$('html,body').animate({scrollTop: '0px'}, 100);
		$('#bg').bind("touchmove",function(e){  
                e.preventDefault();  
        });  
        $('#show').bind("touchmove",function(e){  
            e.stopPropagation();  
        });*/ 
	}
	function hidediv() {
            $('#yb').val('');
                $('#gtype').val('');
                $('#gid').val('');
		document.getElementById("bg").style.display ='none';
		document.getElementById("show").style.display ='none';
	}
	//输入100的倍数且不大于1000
	function check(){
		var myt = document.getElementById( "yb");
		if(Number(myt.value)==0 || !(Number(myt.value)%100==0) || Number(myt.value)>5000){
                    $('.failTips').html('输入100的倍数且不大于5000!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
			myt.value= " ";
		}else{
			return true;
		};
	}