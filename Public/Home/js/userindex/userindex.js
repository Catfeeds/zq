/**
 * @author Chensiren <245017279@qq.com>
 * @since  2015-12-01
*/
var rankType = 1;
$(function(){
    rankHtml('footRankWeek',1);
    $('.jc_sele select option:first').prop("selected", 'selected');
    //首页导航
    $('.nav-con .nav li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
    });
    $('.jc_sele select').on('change',function(e) {
        var myNum = $(this).val();
        var _class = '';
        if(myNum==1){
            rankType = 1;
            $('.foot-list-con').show();
            $('.basket-list-con').hide();
            switch($('.current a').attr('val'))
            {
                case '1':
                    _class = 'footRankWeek';
                    break;
                case '2':
                    _class = 'footRankMonth';
                    break;
                case '3':
                    _class = 'footRankSeason';
                    break;
            }
            rankHtml(_class,$('.current a').attr('val'));
        }else{
            rankType = 2;
            $('.foot-list-con').hide();
            $('.basket-list-con').show();
            switch($('.current a').attr('val'))
            {
                case '1':
                    _class = 'BettingWeek';
                    break;
                case '2':
                    _class = 'BettingMonth';
                    break;
                case '3':
                    _class = 'BettingSeason';
                    break;
            }
            rankHtml(_class,$('.current a').attr('val'));
        }
    });
    $('.time-tab li').click(function(e) {
        $(this).addClass('current').siblings().removeClass('current');
        var myNum = $(this).index();
        $('.foot-list-con .rank-list').eq(myNum).show().siblings().hide();
    });
    $('.time-tab li').click(function(e) {
        $(this).addClass('current').siblings().removeClass('current');
        var myNum = $(this).index();
        $('.basket-list-con .rank-list').eq(myNum).show().siblings().hide();
    });
    //鼠标移上出现modal
    $('.rank-list .table .td02').hover(function(e) {
       $(this).children('div.myRecord').stop().fadeIn(500);
    },function(){
        $(this).children('div.myRecord').stop().fadeOut(500);
    });
    //积分排名切换
    var t_li = $(".match-score .match-tab li")
    var c_li = $(".match-score .score-list li")
    t_li.hover(function(){
        var i = t_li.index($(this));
        function way(){
            t_li.removeClass("on").eq(i).addClass("on");
            c_li.hide().eq(i).show();
        }
            timer=setTimeout(way);
    });

});
//绑定a标签
$("#GO").find("a").bind("click", function(){
    var p = $("input[name='p']").val();
    if (isNaN(p)) {
        return;
    } else if (p>0){
        $('#jsForm').submit(); 
    }
});
//免费查看竞猜
function showDetail(obj){
    //判断登录
    var is_login = $("input[name='userId']").val();
    if (is_login == '')
    {
        modalLogin();
        return;
    }
    var gamble_id = $(obj).attr('gamble_id');
    var game_type = $("input[name='game_type']").val();
    var gtime = $(obj).parent().siblings().eq(1).text();
    var play_type = $(obj).parent().siblings().eq(5).text();
    $.ajax({
        type: "POST",
        url: "/UserIndex/trade.html",
        data: {'gamble_id':gamble_id,'game_type':game_type},
        dataType: "json",
        success: function(data){
            if(data.status == 1){
                var game = data.info;
                var game_team = game['home_team_name']+' VS '+game['away_team_name'];
                $('.doAnswer').find('.mach_name').text(game['union_name']);
                $('.doAnswer').find('.game_team').text(game_team);
                $('.doAnswer').find('.game_date').text(gtime);
                $('.doAnswer').find('.play_type').text(play_type);
                $('.doAnswer').find('.answer').text(game['Answer']);
                var odds = game['handcp'] + "("+game['odds']+")";
                $('.doAnswer').find('.odds').text(odds);
                var desc = game['desc'] != '' ? game['desc'] : "<span class='text-999'>暂无分析<span>";
                $('.doAnswer').find('.desc').html(desc);
                $('.doAnswer').modal('show');
                var free = $(obj).attr('free');
                if(free == 1){
                    var html = "<button type=\"button\" class=\"btn detail-btn\" onclick=\"showDetail(this)\" gamble_id=\""+gamble_id+"\">详情</button>";
                    $(obj).parent().html(html);
                }
            }else{
                _alert("温馨提示",data.info);
            }
        }
    });
}
//购买确认框
function doAnswer(obj){
    //判断登录
    var is_login = $("input[name='userId']").val();
    if (is_login == '')
    {
        modalLogin();
        return;
    }
    //还原清空确认框
    $('.checkGame').find('.answer').text("购买后显示");
    $('.checkGame').find('.odds').text("");
    $('.checkGame').find('.desc').text("购买后显示");
    var tradeCoin = $(obj).text();
    $('.checkGame').find('.tradeCoin').attr("onclick","payment(this)").text('支付'+tradeCoin);
    $('.checkGame').find('.btn-con').removeClass('hidden');
    var gamble_id = $(obj).attr('gamble_id');
    var union_name = $(obj).parent().siblings().eq(0).find('a').attr('title');
    var game_date  = $(obj).parent().siblings().eq(1).find('a').attr('title');
    var home_team_name = $(obj).parent().siblings().eq(2).find('a').attr('title');
    var away_team_name = $(obj).parent().siblings().eq(4).find('a').attr('title');
    var play_type = $(obj).parent().siblings().eq(5).find('a').attr('title');
    //赋值赛事信息
    $('.checkGame').find('.union_name').text(union_name);
    $('.checkGame').find('.game_team').text(home_team_name+" VS "+away_team_name);
    $('.checkGame').find('.game_date').text(game_date);
    $('.checkGame').find('.play_type').text(play_type);
    $('.checkGame').find('.gamble_id').val(gamble_id);
    $('.checkGame').modal('show');
}
//提交购买
function payment(obj){
    var gamble_id = $(obj).parents('.checkGame').find('.gamble_id').val();
    var game_type = $("input[name='game_type']").val();
    var coin = $(obj).text();
    $.ajax({
        type: "POST",
        url: "/UserIndex/trade.html",
        data: {'gamble_id':gamble_id,'game_type':game_type},
        dataType: "json",
        beforeSend:function(){
            $(obj).text("正在提交...").attr("disabled","disabled");
        },
        success: function(data){
            if(data.status == 1){
                $('.checkGame').find('.answer').text(data.info.Answer);
                var odds = data.info.handcp + "("+data.info.odds+")";
                $('.checkGame').find('.odds').text(odds);
                var desc = data.info.desc != '' ? data.info.desc : "<span class='text-999'>暂无分析<span>";
                $('.checkGame').find('.desc').html(desc);
                var html = "<button type=\"button\" class=\"btn detail-btn jb_icon\" onclick=\"showDetail(this)\" gamble_id='"+gamble_id+"'>详情</button>";
                $('.table-bordered').find("button[gamble_id="+gamble_id+"]").parent().html(html);
                showMsg("已成功"+coin+"！",0,'success');
                $(obj).parent().addClass('hidden');
            }else{
                $(obj).text("重新支付");
                gDialog.fConfirm("温馨提示",data.info,function(rs){
                    if(!rs){
                        return;
                    }
                    window.open("/UserInfo/charge.html");
                });
            }
            $(obj).removeAttr("disabled")
        },
    });
}
$('.leftRank a').on('click',function(){
    var change = $(this).attr('val');
    var _class = '';
    switch(rankType)
    {
        case 1:
            switch(change)
            {
                case '1':
                    _class = 'footRankWeek';
                    break;
                case '2':
                    _class = 'footRankMonth';
                    break;
                case '3':
                    _class = 'footRankSeason';
                    break;
            }
            break;
        case 2:
            switch(change)
            {
                case '1':
                    _class = 'BettingWeek';
                    break;
                case '2':
                    _class = 'BettingMonth';
                    break;
                case '3':
                    _class = 'BettingSeason';
                    break;
            }
            break;
    }
    rankHtml(_class,change);
})

window.onload = function(){

}


function rankHtml(_class,change)
{
    if($('.'+_class).html().length > 50)
    {
        return true;
    }
    $('.rankContent').css('display','none');
    $('.rankload').css('display','block');
    $.ajax({
        type: "POST",
        url: "/UserIndex/ajaxRank.html",
        data: {type:rankType,change:change},
        dataType: "html",
        async:false,
        success: function(data){
            $('.'+_class).html(data);
            //鼠标移上出现modal
            $('.rank-list .table .td02').hover(function(e) {
                $(this).children('div.myRecord').stop().fadeIn(500);
            },function(){
                $(this).children('div.myRecord').stop().fadeOut(500);
            });
        },
    });
    $('.rankload').css('display','none');
    $('.rankContent').css('display','block');
}
