$(function () {
    $('.share_con a span').remove();
    $(document).on('click','.price_ul li .odd',function(){
            $(this).parents('li').siblings().find('.odd').removeClass('on');  //移除本行其他单元格的on
    //$(this).parents('tr').siblings('tr').find('.odd').removeClass('on');  //移除另行所有单元格的on      
    //$(this).hasClass("on") ? $(this).removeClass("on") : $(this).addClass("on"); //条件成立执行这个语句1 ： 否则执行这个语句2
        $(this).toggleClass('on'); //切换类
    }).on('click','.js-option',function(){
        //主客队和大小球选择
        $('.js-option').removeClass('on');
        $(this).addClass('on');
    }).on('click','#js-showNext',function(){
        if(!$('.js-option').hasClass('on')){
            alert('请选择竞猜选项!');
            return false;
        }
        var $this=$(this);
        if($this.hasClass('js-disable')){
            return false;
        }
        $this.addClass('js-disable');
        $('#js-showNext').html('加载中...');
        $.ajax({
            type: 'post',   
            url: "/Guess/show_guess.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1){
                    //竞猜框获取数据
                    $('#team_vs').html($('#ag_homename').html()+'  VS  '+$('#ag_awayname').html());
                    var sel_type=$(".js-option.on").data('type');
                    var type=option_type(sel_type);
                    $('#js-options').html(type+':'+$(".js-option.on").html());
                    //$(".issued_main").css("top", "" + document.body.scrollTop + "px");
                    $("#maskLayer").show();
                    $(".issued_main").slideDown();
                }else{
                    alert(data.info);
                    if(data.url!=''){
                        location.href=data.url;
                    }
                }
                $('#js-showNext').html('下一步');
                $this.removeClass('js-disable');
            }
        });
    }).on('click','#sub_game',function(){
        //竞猜
        $this = $(this);
        if($this.hasClass("notallowed")){
            return false;
        }
        var type=$(".js-option.on").data('type');
        var side=$(".js-option.on").data('side');
        var desc=$("#desc").val();
        var game_id=$('#scheid').val();
        var coin =$('.odd.on').data('coin');
        var impt=$('.touzhu_m em.on').data('impt');
        if(desc.length>0 && (desc.length<10 || desc.length>50)){
            alert('请输入10-50字的内容分析!');
            return false;
        }
        $this.addClass("notallowed").html('正在提交中...');
        var params={
            chose_side:side,
            desc:desc,
            game_id:game_id,
            is_impt:impt,
            play_type:type,
            tradeCoin:coin
        }
        $.ajax({
            type: 'post',
            url: "/Guess/do_guess.html",
            data: params,
            dataType: 'json',
            success: function (data) {
                $("#maskLayer").hide();
                if(data.status==1){
                    $('#normLeftTimes').html(data.info.normLeftTimes);
                    $('#imptLeftTimes').html(data.info.imptLeftTimes);
                    $('#desc').val('');
                    $(".issued_main").hide();
                    alert('竞猜成功!');
                    //$('.bubbleTips').html('竞猜成功!').stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
                    location.reload();
                }else{
                    $(".issued_main").hide();
                    $('.bubbleTips').html(data.info).stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
                }
                $this.removeClass("notallowed").html('提交');
            }
        });
    });
});

//获取滚动条的高度
// function getScrollTop()
// {
//     var scrollTop=0;
//     if(document.documentElement&&document.documentElement.scrollTop)
//     {
//         scrollTop=document.documentElement.scrollTop;
//     }
//     else if(document.body)
//     {
//         scrollTop=document.body.scrollTop;
//     }
//     return scrollTop;
// } 

//百分百为0的情况
var rq_zero = $(".tb_gc tr:nth-child(1) td p em").eq(0).width();
var dx_zero = $(".tb_gc tr:nth-child(2) td p em").eq(0).width();
if (rq_zero == 0) {
    $(".tb_gc tr:nth-child(1) td p em:last-child").css("border-radius", ".1rem");
}
if (dx_zero == 0) {
    $(".tb_gc tr:nth-child(2) td p em:last-child").css("border-radius", ".1rem");
}

//切换选中
//$('.price_ul li .odd').click(function (e) {
//    $(this).parents('li').siblings().find('.odd').removeClass('on');  //移除本行其他单元格的on
//    //$(this).parents('tr').siblings('tr').find('.odd').removeClass('on');  //移除另行所有单元格的on      
//    //$(this).hasClass("on") ? $(this).removeClass("on") : $(this).addClass("on"); //条件成立执行这个语句1 ： 否则执行这个语句2
//    $(this).toggleClass('on'); //切换类
//});
//积分选择
$(".touzhu_m em").click(function () {
    $(this).addClass("on").siblings().removeClass("on");
})

//显示下一步
function showNext() {
        if(!$('.js-option').hasClass('on')){
            alert('请选择竞猜选项!');
            return false;
        }
        $.ajax({
            type: 'post',   
            url: "/Guess/show_guess.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1){
                    //竞猜框获取数据
                    $('#team_vs').html($('#ag_homename').html()+'  VS  '+$('#ag_awayname').html());
                    var sel_type=$(".js-option.on").data('type');
                    var type=option_type(sel_type);
                    $('#js-options').html(type+':'+$(".js-option.on").html());
                    $(".issued_main").css("top", "" + document.body.scrollTop + "px");
                    $("#maskLayer").show();
                    $(".issued_main").slideDown();
                }else{
                    alert(data.info);
                    if(data.url!=''){
                        location.href=data.url;
                    }
                }
            }
        });
}
function option_type(type){
    var arr=new Array();
       arr['1']='让球';
       arr['-1']='大小';
    return arr[type];
}
$("#maskLayer,#t_close,#share_close").click(function () {
    $("#maskLayer").hide();
    $(".issued_main").hide();
    $(".sub_share").hide();
});
