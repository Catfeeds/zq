$(function(){
    // $('.do_task').click(function () {
    //     $('.failTips').html('敬请期待!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
    // });
    $('#js-change').click(function (e) {
        var id = $("#pid").val();
        var type = $("#ptype").val();
        if(id==undefined || id=='' || id==0){
            $('.failTips').html('参数有误!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
            return false;
        }
        if(type=='3'){
           var re_name=$('#re_name').val();
           var re_phone=$('#re_phone').val();
           var re_address=$('#re_address').val();
           if(re_name=='' || re_phone=='' || re_address==''){
                $('.failTips').html('请输入收件人信息!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
               return false;
           }
           var params={
                id:id,
                re_name:re_name,
                re_phone:re_phone,
                re_address:re_address,
            };
        }else{
            var params={
                id:id,
            };
        }
        $.ajax({
            type: "post",
            url: "/Etc/doprize.html",
            data: params,
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    hidediv();
                    $('#u_coin').html(data.info.coin);
                    $('.bubbleTips').html(data.info.msg).stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                } else {
                    if(data.info==null){
                        data.info='兑换失败!';
                    }
                    $('.failTips').html(data.info).stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                }

            }
        });
    });
});
//弹出层
	function showdiv(othis) {
                var id=$(othis).data('id');
                var type=$(othis).data('type');
                var sale=$(othis).data('sale');
                if(sale!='1'){
                    $('.failTips').html('该商品已下架!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                    return false;
                }
                if(type!='3'){
                    $('#info_title').hide();
                    $('.form_div').hide();
                }else{
                    $('#info_title').show();
                    $('.form_div').show();
                }
                if(id==undefined || id=='' || id==0){
                     $('.failTips').html('请选择要兑换的奖品!').stop().fadeIn(300).animate({'top':'40%'},1500).hide(0).animate({'top':'50%'});
                    return false;
                }
                $('#pid').val(id);
                $('#ptype').val(type);
                $("#js-coin").html($(othis).html());
                $("#js-title").html($(othis).prev().prev().html());
		document.getElementById("bg").style.display ="block";
		document.getElementById("show").style.display ="block";
	}
	function hidediv() {
                $("#js-coin").html('');
                $("#js-title").html('');
		document.getElementById("bg").style.display ='none';
		document.getElementById("show").style.display ='none';
	}