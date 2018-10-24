$(function () {
    var Top = Cookie.getCookie('isTopBk');
    if (Top) {
        Top = Top.substring(0, Top.length - 1);
        var top_arr = Top.split(',');
        $.each(top_arr, function (i, val) {
            if (i == 0) {
                $(".liveList").prepend($('#scheid' + val));
            } else {
                $(".star_img_on:last").parents('.js-data').after($('#scheid' + val));
            }
            $('#scheid' + val).find('.star_img').addClass('star_img_on');
        });
    }
    $(document).on('click', '.js-data', function () {
        var topHeight = $(document).scrollTop();
        Cookie.setCookie('scrollTop', topHeight, 60000);
        window.location.href = $(this).data('url');
    }).on('click','.star_img',function(){
        var scheid=$(this).parents('.js-data').data('id');
        var othis=$(this);
        var siTop = Cookie.getCookie('isTopBk');
    if ($(othis).hasClass('star_img_on')) {
        $key = $(othis).parents('.js-data').data('key');
        var change = false;
        $('.js-data').each(function () {
            $this = $(this);
            if (!$this.find('.star_img').hasClass('star_img_on')) {
                if ($key < $this.data('key')) {
                    $this.before($(othis).parents('.js-data'));
                    change = true;
                    return false;
                }
            }
        });
        $(othis).removeClass('star_img_on');
        var str = scheid + ',';
        Cookie.setCookie("isTopBk", siTop.replace(new RegExp(str), ''));
    } else {
        var pdata = $(othis).parents('.js-data');
        var top = '';
        var flag = false;
        if ($('.star_img').hasClass('star_img_on')) {
            $(".star_img_on").each(function (i, v) {
                var tdata = $(this).parents('.js-data');
                if (pdata.data('time') > tdata.data('time')) {
                    tdata.after(pdata);
                    flag = true;
                }
            });
            if (!flag) {
                $('.liveList').prepend(pdata);
            }
        } else {
            $('.liveList').prepend(pdata);
        }
        $(othis).addClass('star_img_on');
        $(".star_img_on").each(function (i, v) {
            top += $(this).parents('.js-data').data('id') + ',';
        });
        Cookie.setCookie('isTopBk', top);
    }
    return false;
    });
    var begin = setInterval(function () {
        var refreshTime = localStorage.getItem("refreshTime") ? localStorage.getItem("refreshTime") : 5;
        refreshTime = parseInt(refreshTime + '000');
        if (typeof (refreshTime) == 'number' && (refreshTime >= 3000)) {
            scoreTan(refreshTime);
            goal(refreshTime);
        } else {
            scoreTan(5000);
            goal(5000);
        }
        clearInterval(begin);
    }, 5000);

});

//比分时间更新
function scoreTan(time)
{
    if (time == undefined || time < 3000) {
        time = 5000;
    }
    $.ajax({
        type: 'post',
        url: '/Index/BkScoreChange.html',
        dataType: 'json',
        success: function (data) {
            if (data['status'] == 1) {
                $.each(data.info, function (i, val) {

                    if (val[0] == 0)
                        return true;

                    //比赛状态
                    switch (val[1])
                    {
                        case '0':$('#gameStatus'+val[0]).html('未开');break;
                        case '1':$('#gameStatus'+val[0]).html('第一节');break;
                        case '2':$('#gameStatus'+val[0]).html('第二节');break;
                        case '3':$('#gameStatus'+val[0]).html('第三节');break;
                        case '4':$('#gameStatus'+val[0]).html('第四节');break;
                        case '50':$('#gameStatus'+val[0]).html('中场');break;
                        case '-2':$('#gameStatus'+val[0]).html('待定');break;
                        case '-12':$('#gameStatus'+val[0]).html('腰斩');break;
                        case '-13':$('#gameStatus'+val[0]).html('中断');break;
                        case '-14':$('#gameStatus'+val[0]).html('推迟');break;
                        case '-1':
                            $('#gameStatus'+val[0]).html('完场');
                            //主队总得分
                            $('#homeTotalScore'+val[0]).removeClass('march_scroe_color');
                            $('#homeTotalScore'+val[0]).removeClass('mach_begin_time');
                            $('#homeTotalScore'+val[0]).addClass('mach_begin_time');
                            //客队总得分
                            $('#awayTotalScore'+val[0]).removeClass('march_scroe_color');
                            $('#awayTotalScore'+val[0]).removeClass('mach_begin_time');
                            $('#awayTotalScore'+val[0]).addClass('mach_begin_time');
                            break;
                        case '-10':$('#gameStatus'+val[0]).html('取消');break;
                        case '-5':break;//异常状态
                        default:
                            $('#gameStatus'+val[0]).html(val[13]+"'OT")
                    }

                    if (val[1] == '-1')//完场的
                        return true;

                    //比赛小节时间
                    $('#gameTime'+val[0]).html(val[2])

                    if (val[1] >= 1 && val[1] <= 4)//一到四节
                    {
                        for (var i = 0; i < 4; i++)//一到四节
                        {
                            //主队比分
                            if ($('#homeScore'+val[0]+' ul li').eq(i).html() != val[5+2*i])
                            {
                                $('#homeScore'+val[0]+' ul li').eq(i).html(val[5+2*i]);
                                $('#homeScore'+val[0]+' ul li').eq(i).addClass('goal_scroe_bg')

                            }

                            //客队比分
                            if ($('#awayScore'+val[0]+' ul li').eq(i).html() != val[6+2*i])
                            {
                                $('#awayScore'+val[0]+' ul li').eq(i).html(val[6+2*i]);
                                $('#awayScore'+val[0]+' ul li').eq(i).addClass('goal_scroe_bg')

                            }

                        }
                        setTimeout(function(){
                                for (var i = 0; i < 4; i++)//一到四节
                                {
                                    $('#homeScore'+val[0]+' ul li').eq(i).removeClass('goal_scroe_bg');
                                    $('#awayScore'+val[0]+' ul li').eq(i).removeClass('goal_scroe_bg');
                                }
                            },2000);
                    }


                    //加时
                    for (var i = 0; i < val[13]; i++)
                    {
                        if ($('#homeScore'+val[0]+' ul li').eq(4+i).html() == undefined )//判断是否添加了<li>
                        {
                            $('#homeScore'+val[0]+' ul').append('<li>'+val[14+2*i]+'</li>');//主队加时比分
                            $('#homeScore'+val[0]+' ul li').eq(4+i).addClass('goal_scroe_bg')
                        }
                        else
                        {
                            if ($('#homeScore'+val[0]+' ul li').eq(4+i).html() != val[14+2*i])//主队加时比分
                            {
                                $('#homeScore'+val[0]+' ul li').eq(4+i).html(val[14+2*i]);
                                $('#homeScore'+val[0]+' ul li').eq(4+i).addClass('goal_scroe_bg');
                            }

                        }

                        if ($('#awayScore'+val[0]+' ul li').eq(4+i).html() == undefined )//判断是否添加了<li>
                        {
                            $('#awayScore'+val[0]+' ul').append('<li>'+val[15+2*i]+'</li>');//客队加时比分
                            $('#awayScore'+val[0]+' ul li').eq(4+i).addClass('goal_scroe_bg');
                        }
                        else
                        {
                            if ($('#awayScore'+val[0]+' ul li').eq(4+i).html() != val[15+2*i])//客队加时比分
                            {
                                $('#awayScore'+val[0]+' ul li').eq(4+i).html(val[15+2*i]);
                                $('#awayScore'+val[0]+' ul li').eq(4+i).addClass('goal_scroe_bg');
                            }
                        }
                    }
                    for (var i = 0; i < val[13]; i++)
                    {
                        setTimeout(function(){
                                   $('#homeScore'+val[0]+' ul li').eq(4+i).removeClass('goal_scroe_bg');
                                },2000);
                        setTimeout(function(){
                               $('#awayScore'+val[0]+' ul li').eq(4+i).removeClass('goal_scroe_bg');
                            },2000);
                    }
                    //主队总得分
                    if ($('#homeTotalScore'+val[0]).html() != val[3])
                    {
                        $('#homeTotalScore'+val[0]).html(val[3]);
                        $('#homeTotalScore'+val[0]).addClass('goal_scroe_bg');
                        setTimeout(function(){
                           $('#homeTotalScore'+val[0]).removeClass('goal_scroe_bg');
                        },2000);

                    }
                    //客队总得分
                    if ($('#awayTotalScore'+val[0]).html() != val[4])
                    {
                        $('#awayTotalScore'+val[0]).html(val[4]);
                        $('#awayTotalScore'+val[0]).addClass('goal_scroe_bg')
                        setTimeout(function(){
                           $('#awayTotalScore'+val[0]).removeClass('goal_scroe_bg');
                        },2000);

                    }

                })
            }
        }
    });
    setTimeout("scoreTan(" + time + ")", time);
}

//篮球今日赔率变化数据
function goal(time)
{
    $.ajax({
        type: 'post',
        url: '/Index/bkGoalChange.html',
        dataType: 'json',
        success: function (data) {
            if (data['status'] == 1) {
                $.each(data.info, function (i, val) {
                    if (i == 0 || i == '' || i == null)//赛程id为空就跳过
                        return true;
                    //主队让分赔率
                    if ($('#homeAdds'+i).html() != val[0])
                    {

                        if ($('#homeAdds'+i).html() < val[0])
                        {
                            $('#homeAdds'+i).html(val[0]);
                            $('#homeAdds'+i).addClass('asc_hand_bg')
                            setTimeout(function(){
                               $('#homeAdds'+i).removeClass('asc_hand_bg');
                            },2000);

                        }
                        else
                        {
                            $('#homeAdds'+i).html(val[0])
                            $('#homeAdds'+i).addClass('desc_hand_bg');
                            setTimeout(function(){
                               $('#homeAdds'+i).removeClass('desc_hand_bg');
                            },2000);

                        }
                    }

                    //让分盘口
                    if ($('#handcp'+i).html() != val[1])
                    {

                        if ($('#handcp'+i).html() < val[1])
                        {
                            $('#handcp'+i).html(val[1]);
                            $('#handcp'+i).addClass('asc_hand_bg')
                            setTimeout(function(){
                               $('#handcp'+i).removeClass('asc_hand_bg');
                            },2000);

                        }
                        else
                        {
                            $('#handcp'+i).html(val[1]);
                            $('#handcp'+i).addClass('desc_hand_bg');
                            setTimeout(function(){
                               $('#handcp'+i).removeClass('desc_hand_bg');
                            },2000);

                        }
                    }

                    //客队让分赔率
                    if ($('#awayAdds'+i).html() != val[2])
                    {

                        if ($('#awayAdds'+i).html() < val[2])
                        {
                            $('#awayAdds'+i).html(val[2])
                            $('#awayAdds'+i).addClass('asc_hand_bg')
                            setTimeout(function(){
                               $('#awayAdds'+i).removeClass('asc_hand_bg');
                            },2000);

                        }
                        else
                        {
                            $('#awayAdds'+i).html(val[2])
                            $('#awayAdds'+i).addClass('desc_hand_bg');
                            setTimeout(function(){
                               $('#awayAdds'+i).removeClass('desc_hand_bg');
                            },2000);

                        }
                    }

                    //赔率----总的
                    if ($('#homeTotalAdds'+i).html() == undefined || $('#totalAdds'+i).html() == undefined || $('#awayTotalAdds'+i).html() == undefined)
                    {
                        $('#list'+i).append('<span class="oddsType">总</span>'+
                            '<span id="homeTotalAdds'+i+'" class="addsNub js-home-ball " style=""></span>'+
                            '<span id="totalAdds'+i+'" class="addsPankou js-all-ball"></span>'+
                            '<span id="awayTotalAdds'+i+'" class="addsNub js-away-ball" style=""></span>');
                    }
                    //主队总分赔率
                    if ($('#homeTotalAdds'+i).html() != val[3])
                    {

                        if ($('#homeTotalAdds'+i).html() < val[3])
                        {
                            $('#homeTotalAdds'+i).html(val[3]);
                            $('#homeTotalAdds'+i).addClass('asc_hand_bg');
                            setTimeout(function(){
                               $('#homeTotalAdds'+i).removeClass('asc_hand_bg');
                            },2000);

                        }
                        else
                        {
                            $('#homeTotalAdds'+i).html(val[3]);
                            $('#homeTotalAdds'+i).addClass('desc_hand_bg');
                            setTimeout(function(){
                               $('#homeTotalAdds'+i).removeClass('desc_hand_bg');
                            },2000);

                        }
                    }

                    //总分赔率
                    if ($('#totalAdds'+i).html() != val[4])
                    {

                        if ($('#totalAdds'+i).html() < val[4])
                        {
                            $('#totalAdds'+i).html(val[4]);
                            $('#totalAdds'+i).addClass('asc_hand_bg');
                            setTimeout(function(){
                                $('#totalAdds'+i).removeClass('asc_hand_bg');
                            },2000);

                        }
                        else
                        {
                            $('#totalAdds'+i).html(val[4])
                            $('#totalAdds'+i).addClass('desc_hand_bg');
                            setTimeout(function(){
                               $('#totalAdds'+i).removeClass('desc_hand_bg');
                            },2000);

                        }
                    }

                    //客队总分赔率
                    if ($('#awayTotalAdds'+i).html() != val[5])
                    {

                        if ($('#awayTotalAdds'+i).html() < val[5])
                        {
                            $('#awayTotalAdds'+i).html(val[5])
                            $('#awayTotalAdds'+i).addClass('asc_hand_bg');
                            setTimeout(function(){
                               $('#awayTotalAdds'+i).removeClass('asc_hand_bg');
                            },2000);

                        }
                        else
                        {
                            $('#awayTotalAdds'+i).html(val[5])
                            $('#awayTotalAdds'+i).addClass('desc_hand_bg');
                            setTimeout(function(){
                                $('#awayTotalAdds'+i).removeClass('desc_hand_bg');
                            },2000);

                        }
                    }



                });
            }
        }
    });
    setTimeout("goal(" + time + ")", time);
}