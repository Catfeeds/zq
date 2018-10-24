/**
 * Created by liangzk on 2016/7/28.
 */
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
        url: "/UserInfo/trade.html",
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
                layer.confirm(data.info);
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
    $('.checkGame').find('.desc').text("购买后显示");
    var tradeCoin = $(obj).text();
    $('.checkGame').find('.tradeCoin').attr("onclick","payment(this)").text('支付'+tradeCoin);
    $('.checkGame').find('.btn-con').removeClass('hidden');
    var gamble_id = $(obj).attr('gamble_id');
    var union_name = $(obj).parent().siblings().eq(0).find('span').attr('title');
    var game_date  = $(obj).parent().siblings().eq(1).find('span').attr('title');
    var home_team_name = $(obj).parent().siblings().eq(2).find('a').attr('title');
    var away_team_name = $(obj).parent().siblings().eq(4).find('a').attr('title');
    var play_type = $(obj).parent().siblings().eq(5).find('span').attr('title');
    //赋值赛事信息
    $('.checkGame').find('.union_name').text(union_name);
    $('.checkGame').find('.game_team').text(home_team_name+" VS "+away_team_name);
    $('.checkGame').find('.game_date').text(game_date);
    $('.checkGame').find('.play_type').text(play_type);
    $('.checkGame').find('.gamble_id').val(gamble_id);
    $('.checkGame').removeClass('hide');
    $('.checkGame').modal('show');
}
//提交购买
function payment(obj){
    var gamble_id = $(obj).parents('.checkGame').find('.gamble_id').val();
    var game_type = $("input[name='game_type']").val();
    var coin = $(obj).text();
    $.ajax({
        type: "POST",
        url: "/UserInfo/trade.html",
        data: {'gamble_id':gamble_id,'game_type':game_type},
        dataType: "json",
        beforeSend:function(){
            $(obj).text("正在提交...");
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
                layer.confirm(data.info,function(rs){
                    if(!rs){
                        return;
                    }
                    window.open("/UserInfo/charge.html");
                });
            }
        },
    });
}