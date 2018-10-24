/**
 * 足球比分JS文件
 * @author Chensiren <245017279@qq.com>
 * @since  2016-11-07
 **/
var CookieArray = [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0]; //默认功能设置cookie
//[0红牌, 1比分进球, 2显示黄牌, 3提示声,4排名, 5显示往绩, 6弹框位置, 7半场, 8亚盘, 9大小, 10欧赔]
var url_name = 'bk_index';
$(function () {
    $("[data-toggle='tooltip']").tooltip();
    console.log(mqHost)
    // languageChange();
    url_name = window.location.pathname.substr(1);
    url_name.substr(0,url_name.length-5);
    // languageChange();
    //已隐藏的赛事
    var fbgameHideTr = Cookie.getCookie('fbgameHideTr');
    if(fbgameHideTr){
        var fbgameHideTrArray = fbgameHideTr.split(',');
        $.each(fbgameHideTrArray,function(k,v){
            $("tr[game_id='"+v+"']").css('display','none');
            $("tr[game_id='"+v+"']").find('.gameId').attr('checked',true);
        })
    }
    //赛事隐藏统计
    hideCount();
    $('.livescore_table').removeClass('hidden');
    //嵌入页面时调整宽度
    //加载gif动画效果
    $('#removeAll').click(function (e) {
        $('.box-list').fadeOut(300);
    });
});
window.onload=function (){
    $('.mytable01').css('display','block');
    $('.loading_gif').hide();
}

//赛事选择的js事件--S
var _team = '';

$(".event").on('click', function () {
    _team = $(".box-team").html();
});

//接收数据处理
MqInit.onMessage(function (topic, message) {
    var data = message;
    if (topic.indexOf('bk/goal') > -1) {//足球全场赔率
        getGameOdds(data);
    } else if (topic.indexOf('/bk/change') > -1) {//比分变化
        gameChange(data);
    }
}, ["qqty/api500/bk/#"]);

//功能键滑动固定
if($('.qqtyLiveTop').length > 0){
    var navOffset=$(".control-con").offset().top;
    $(window).scroll(function(){
        var scrollPos=$(window).scrollTop();
        if(scrollPos >=navOffset){
            $(".control-con").addClass("div-fixed");
            $(".control-box").css({'width': '1160px','margin':'0 auto'});
            $('#navig').css({'display': 'block','position': 'fixed','margin-left': '-600px','left': '50%','top': '59px','z-index': '99','width':'1200px'});
        }else{
            $(".control-con").removeClass("div-fixed");
            $(".control-box").css({'width': '','margin':''});
            $('#navig').hide();
        }
    });
}

//定时更新弹框赔率
// setInterval(function () {
    // getGameAllOdds();
    // getGameOdds();
    // gameChange();
// }, 10000);

//定时更新赛事事件
// setInterval(function () {
//     getGameDetail();
// }, 30000);

//显示全部赛事事件
$('#showGameAll,#all').on('click', function () {
    //切换到完整
    $('#centerNav li').removeClass('on');
    $('#all').parent().addClass('on');
    //还原初始化赛事
    resetGame();
    //统计页面隐藏的赛事数
    hideCount();
});

//还原初始化赛事
function resetGame(){
    $('.gameList').show();
    Cookie.delCookie('fbgameHideTr');
    $('.gameId').removeAttr("checked");
}

//存隐藏赛事cookie
function setGameHideTr(str){
    Cookie.setCookie('fbgameHideTr', str);
    //赛事隐藏统计
    hideCount();
}

//根据复选框显示隐藏赛程列表
$(".pitch").on('click', function () {
    _css = $(this).attr('type');
    var _url = Cookie.getCookie(url_name);
    if (_css == 'none') {
        if(_url == null || _url.substr(0,4) == 'show')
        {
            Cookie.setCookie(url_name,'none?');
        }
        $(".mytable01 .list_teble").each(function () {
            if (typeof($(this).attr('list_type')) == 'undefined') {
                _type = 'now';
            } else {
                _type = $(this).attr('list_type');
            }
            console.log($(this).children('.table').find('.gameId').is(':checked'))
            if ($(this).children('.table').find('.gameId').is(':checked')) {
                $(this).css('display', 'none');
                $(this).children().children().attr('checked',false);
                savelog($(this).attr('g_id'));
            }
        });
    } else {
        if(_url == null || _url.substr(0,4) == 'none')
        {
            Cookie.setCookie(url_name,'show?');
        }
        $(".mytable01 .list_teble").each(function () {
            if (!$(this).children('.table').find('.gameId').is(':checked')) {
                $(this).css('display', 'none');
            }
        });
    }
    bgcolor(1);
});
//cookie保存赛事勾选
function savelog(gameid)
{
    var log = Cookie.getCookie(url_name);
    if(gameid > 0){
        Cookie.setCookie(url_name,log + gameid + '?');
    }
}

//统计这个页面隐藏的赛事数
function hideCount() {
    var hideCount = 0;
    $('.gameList').each(function (index, element) {
        var display = $(this).css('display');
        if (display == 'none') {
            hideCount++;
        }
    });
    $('#gameHideCount').html(hideCount);
    $('.menu_count').html(hideCount);

    var doGameNum = noGameNum = overGameNum = 0;
    //进行中赛事判断
    $('#do_game .gameList').each(function(){
        var display = $(this).css('display');
        if (display != 'none') {
            doGameNum++;
        }
    })
    if(doGameNum == 0){
        $('#do_game .p10').parent().hide();
    }else{
        $('#do_game .p10').parent().show();
    }
    //未开赛事判断
    $('#no_game .gameList').each(function(){
        var display = $(this).css('display');
        if (display != 'none') {
            noGameNum++;
        }
    })
    if(noGameNum == 0){
        $('#no_game .p10').parent().hide();
    }else{
        $('#no_game .p10').parent().show();
    }
    //完场赛事判断
    $('#over_game .gameList').each(function(){
        var display = $(this).css('display');
        if (display != 'none') {
            overGameNum++;
        }
    })
    if(overGameNum == 0){
        $('#over_game .p10').parent().hide();
    }else{
        $('#over_game .p10').parent().show();
    }
    //背景颜色重置
    bgcolor();
}
//滚球选项
$(".type-li02").on('click', function () {
    type_li('is_sport');
});
//滚球选项
$(".type-li03").on('click', function () {
    type_li('nba');
});
function type_li(_li) {
    $(".mytable01 .list_teble").css('display', 'none');
    $(".mytable01 .list_teble").each(function () {
        if (typeof($(this).attr('list_type')) == 'undefined') {
            _type = 'now';
        } else {
            _type = $(this).attr('list_type');
        }
        if (_type == 'now') {
            if ($(this).attr(_li) == 1) {
                $(this).css('display', '');
            }
        }
    });
    bgcolor();
}
//显示全部
function show() {
    $(".mytable01 .list_teble").css('display', '');
    $(".union_list dd").css('display', '');
    $(".count").html('0');
    bgcolor();
}
//赛事选择
$('.event').click(function (e) {
    $('.box-list').fadeIn(300);
    $('.layer-list').fadeOut(300);
    $('.gs-list').fadeOut(300);
});
//联赛级别筛选
$('.rank-ul li').on('click', function () {
    var num = $('.rank-ul li').index(this) + 1;

    if ($(this).hasClass('on')) {
        if (num == 1) {
            menu_display(0, false);
            menu_display(1, false);
        } else {
            menu_display(num, false);
        }
        $(this).removeClass('on');
    } else {
        if (num == 1) {
            menu_display(0, true);
            menu_display(1, true);
        } else {
            menu_display(num, true);
        }
        $(this).addClass('on');
    }
    dynamic();
    // bgcolor();
});
$(".inline").on("click", function () {
    dynamic();
});
//赛事选择复选框
function menu_display($num, $che) {
    $(".match-team li").each(function () {
        if ($(this).attr('match_level') == $num) {
            $(this).children('.inline').children('.userid').attr("checked", $che);
        }
    });
}

//赛事选择确定事件
$('#ensures').on('click', function () {
    //判断是否有勾选联赛
    var is_check = false;
    $('#menu_list input[class=userid]:checkbox').each(function () {
        if ($(this).attr('checked')) {
            is_check = true;
        }
    });
    if (!is_check) {
        showMsg('请选择联赛！', 0, 'error');
        return;
    }
    $(".union_list dd").not(":eq(0)").css('display', 'none');
    $(".mytable01 .list_teble").css('display', 'none');
    $(".mytable01 div").each(function () {
        $(this).attr('list_type', 'old');
    });
    $(".match-team li").each(function () {
        var cbox = $(this).children('.inline').children('.userid');
        if (cbox.is(':checked')) {
            $(".union_list dd").each(function () {
                if ($(this).attr('union_id') == cbox.attr('union_m')) {
                    $(this).css('display', '');
                }
            });
            $(".mytable01 .list_teble").each(function () {
                if ($(this).attr('union_id') == cbox.attr('union_m')) {
                    $(this).attr('list_type', 'now');
                    $(this).css('display', '');
                }
            });
        }
    });
    // bgcolor();
    var _count = dynamic();
    $(".count").html(_count);
    $('.box-list.dropdown-menu').css('display', 'none');
    bgcolor(1);
});

//赛事选择点击关闭事件
$("#removeAll").on('click', function () {
    setTimeout(function () {
        $(".box-team").empty();
        $(".box-team").append(_team);
        /*赛事选择js效果*/
        $('.rank-ul li').on('click', function () {
            var num = $('.rank-ul li').index(this) + 1;
            if ($(this).attr('class').split(' ').length > 1) {
                if (num == 1) {
                    menu_display(0, false);
                    menu_display(1, false);
                } else {
                    menu_display(num, false);
                }
                $(this).removeClass('on');
            } else {
                if (num == 1) {
                    menu_display(0, true);
                    menu_display(1, true);
                } else {
                    menu_display(num, true);
                }
                $(this).addClass('on');
            }

            bgcolor();
        });
    }, 700);

});

//完整、滚球切换
$('.control-2 li').click(function (e) {
    $(this).addClass('on').siblings().removeClass();
});

//语言切换事件
$('#languageSle li a').on('click', function () {

    //1:简体 2：繁体 3：英语
    var language = $(this).data('language');
    $('#languageContent').attr('language', language);
    switch (language) {
        case '1':
            $('#languageContent').html('简体');
            break;
        case '2':
            $('#languageContent').html('繁体');
            break;
        case '3':
            $('#languageContent').html('EN');
            break;
    }
    Cookie.setCookie('indexLanguageSle', language);
    //页面语言显示改变
    languageChange();

})

//页面语言显示改变
function languageChange() {
    var language = Cookie.getCookie('indexLanguageSle');
    $('.language').css('display', 'none');
    $('.lang_cn').addClass("lang_css");
    $('.lang_tw').addClass("lang_css");
    $('.lang_en').addClass("lang_css");
    switch (language) {
        case '2':
            $('#languageContent').html('繁体');
            $('.lang_tw').removeClass("lang_css");
            break;
        case '3':
            $('#languageContent').html('EN');
            $('.lang_en').removeClass("lang_css");
            break;
        default:
            $('#languageContent').html('简体');
            $('.lang_cn').removeClass("lang_css");
    }

    $('#languageContent').attr('language', language ? language : 1);
}

//语言切换
$('.odds-language .dropdown-menu li').click(function (e) {
    var languageName = $(this).children('a').text();
    $('.odds-language span').html(languageName);
});

//功能选择
$('.fun').click(function (e) {
    $('.layer-list').fadeIn(300);
    $('.box-list').fadeOut(300);
});
$('.bts .btn').click(function (e) {
    $('.layer-list').fadeOut(300);
});
//公司选择
$('.gongSi').click(function (e) {
    $('.gs-list').fadeIn(300);
    $('.box-list').fadeOut(300);
});
$('#closeAll').click(function (e) {
    $('.gs-list').fadeOut(300);
});

$("#reverse").on('click', function () {
    $(".userid").each(function() {
        if (this.checked == true) {
            this.checked = false;
        } else {
            this.checked = true;
        }
    });
    $(".rank-ul li").each(function () {
        var _li = $(this);
        if (_li.attr('class').split(" ")[1] == 'on') {
            _li.removeClass('on');
        } else {
            _li.addClass('on')
        }
    });
    dynamic();
});
$("#checkAll").on('click', function () {
    $(".rank-ul li").addClass('on');
    $(".userid").each(function () {
        this.checked = true;
    });
    dynamic();
});

//功能选择设置
$('.feat-select input').on('change', function () {
    var status = $(this).is(':checked') ? 1 : 0;
    var serial = $(this).val();
    setFbCookie(serial, status);
    switch (serial) {
        case '2':
            //黄牌显示切换
            if (status == 0) {
                $('.yellow-card').addClass('hidden');
            } else {
                $('.yellow-card').removeClass('hidden');
            }
            break;
        case '4':
            //球队排名显示切换
            if (status == 0) {
                $('.rank').addClass('hidden');
            } else {
                $('.rank').removeClass('hidden');
            }
            break;
    }
})

//提示框位置设置
$('.feat-select select').on('change', function () {
    var status = $(this).val();
    setFbCookie(6, status);
})

//设置对应cookie数值
function setFbCookie(serial, status) {
    //是否存在cookie
    var fbCookie = Cookie.getCookie('fbCookie');
    var array = fbCookie ? fbCookie.split('^') : CookieArray;
    //改变对应数值
    array[serial] = status;
    Cookie.setCookie('fbCookie', array.join('^'), 7);
}

//获取对应cookie数值
function getFbCookie(serial) {
    if (serial == '' || serial == undefined)
        serial = 'all';
    var fbCookie = Cookie.getCookie('fbCookie');
    var array = fbCookie ? fbCookie.split('^') : CookieArray;
    if (serial == 'all') return array;
    //返回对应数值
    return array[serial];
}

//赛事选择动态变化隐藏数量
$('.match-team li').on('click', function () {
    dynamic();
});

//统计联赛筛选所隐藏的赛事
function dynamic() {
    var num = 0;
    $('#menu_list input[class=userid]:checkbox').each(function () {
        var str = '';
        if (!$(this).attr('checked')) {
            str = $(this).parent().children("em").html();
            str = str.substring(0, str.length - 1);
            str = str.substr(1);
            num = num + Number(str);
        }
    });
    $(".menu_count").html(num);
    return num;
}
//列表底色更改
function bgcolor(type) {
    if(type != 1)
    {
        $('.loading_gif').show().animate({'display':'none'},500).css('display','none');
        $('.mytable01').hide().animate({'display':'block'},500).css('display','block');
    }
    var num = 0;
    $(".mytable01").find('.list_teble').each(function () {
        if($(this).css('display') == 'none')
        {
            num++;
        }
    });

    //根据页面隐藏数量
    var now_type = 0;
    $('.now').find('.list_teble').each(function(){
        if($(this).css('display') != 'none')
        {
            now_type = 1;
        }
    });
    if(now_type == 0)
    {
        $('.now_title').css('display','none');
    }
    var unopened_type = 0;
    $('.unopened').find('.list_teble').each(function(){
        if($(this).css('display') != 'none')
        {
            unopened_type = 1;
        }
    });
    if(unopened_type == 0)
    {
        $('.unopened_title').css('display','none');
    }
    var over_type = 0;
    $('.over').find('.list_teble').each(function(){
        if($(this).css('display') != 'none')
        {
            over_type = 1;
        }
    });
    if(over_type == 0)
    {
        $('.over_title').css('display','none');
    }
    $(".count").html(num);
}

//赛事变化数据，比分、红黄牌，比赛时间
function gameChange(payload) {
    if(payload === undefined) return false;
    // payload = '{"status":"1","data":{"307338":["307338","2","","19","32","15","29","4","3","","","","","0","0","0","","","","","1.2,3.2","hehehe<br/>qweqe"]},"msg":""}';
    console.log('比分变化:'+payload)
    var temp = JSON.parse(payload);
    var data = temp['data'];
    console.log(data);
    $.each(data, function (k, v) {
        //赛事的比赛状态或比赛时间
        var newStatus = v[1];
        console.log(newStatus)
        var statusStr = null;
        var parent = '';
        parent = $('.mytable01').find('.list_teble[g_id='+k+']');
        if(parent.length == 0 || newStatus > 8)
            return true;
        var union_name = parent.find('.lang_cn').html();
        if(newStatus == 0)
        {
            return true;
        }
        switch (newStatus) {
            case '1':
                statusStr = '第一节';
                break;
            case '2':
                statusStr = '第二节';
                break;
            case '3':
                statusStr = '第三节';
                break;
            case '4':
                statusStr = '第四节';
                break;
            case '-1':
                statusStr = '完场';
                break;
            case '-10':
                statusStr = '取消';
                break;
            case '-11':
                statusStr = '待定';
                break;
            case '-12':
                statusStr = '腰斩';
                break;
            case '-13':
                statusStr = '中断';
                break;
            case '-14':
                statusStr = '推迟';
                break;
            case '5':
                statusStr = "1'OT";
                break;
            case '6':
                statusStr = "2'OT";
                break;
            case '7':
                statusStr = "3'OT";
                break;
            case '8':
                statusStr = "4'OT";
                break;
        }
        var union_type = 0;
        var order_id = newStatus-1;
        if(union_name == 'NCAA')
        {
            union_type = 1;
            order_id = newStatus-3;
            switch (newStatus) {
                case '1':
                    statusStr = '上半场';
                    order_id = 0;
                    break;
                case '3':
                    statusStr = '下半场';
                    order_id = 1;
                    break;
            }
        }
        if(statusStr == null)
            return true;

        var header = parent.find('.score_header td').eq(order_id).html();
        console.log(header,order_id,union_type)
        if(header === undefined)
        {
            parent.find('.score_header').append('<td width="25">'+statusStr+'</td>');
            parent.find('.home_tr .score_info').append('<td width="25">'+v[newStatus*2+4]+'</td>');
            parent.find('.away_tr .score_info').append('<td width="25">'+v[newStatus*2+5]+'</td>');
        }
        parent.find('.game_status').removeClass('text-999').addClass('text-red');
        parent.find('.game_status div').eq(0).html(statusStr);
        var gtime = '';
        if(parseInt(v[1]) > 0)
        {
            gtime = v[2];
        }
        var score = [];
        var score_key = 0;
        for(var j=3;j<=16;j++)
        {
            score[score_key] = isNaN(parseInt(v[j]))?0:parseInt(v[j]);
            score_key++;
        }
        score_bg_color(parent,v,union_type);
        parent.find('.game_status div').eq(1).html(gtime);
        if(newStatus > 0)
        {
            if(union_type == 1)
            {
                parent.find('.home_tr .score_info td').eq(0).html(v[5])
                parent.find('.home_tr .score_info td').eq(1).html(v[9])
                parent.find('.away_tr .score_info td').eq(0).html(v[6])
                parent.find('.away_tr .score_info td').eq(1).html(v[10])
                for(var ii = 5; ii<=newStatus;ii++)
                {
                        parent.find('.home_tr .score_info td').eq(ii-3).html(v[ii*2+4])
                        parent.find('.away_tr .score_info td').eq(ii-3).html(v[ii*2+5])
                }
            }else{
                for(var ii = 0; ii<newStatus;ii++)
                {

                    if(ii < 4)
                    {
                        parent.find('.home_tr .score_info td').eq(ii).html(v[(ii+1) *2+3])
                        parent.find('.away_tr .score_info td').eq(ii).html(v[(ii+1) *2+4])
                    }else{
                        parent.find('.home_tr .score_info td').eq(ii).html(v[(ii+1) *2+4])
                        parent.find('.away_tr .score_info td').eq(ii).html(v[(ii+1) *2+5])
                    }
                }
            }
        }
        //主队栏比分变化
        parent.find('.home_tr .half_score').html((score[2] + score[4]) + '/' + (score[6] + score[8]))
        parent.find('.home_tr .all_court').html(score[0])
        parent.find('.home_tr .bk_match').html('半:' + ((score[2] + score[4]) - (score[3] + score[5])))
        parent.find('.home_tr .all_match').html('半:'+(score[2] + score[4] + score[3] + score[5]))
        //客队栏比分变化
        parent.find('.away_tr .half_score').html((score[3] + score[5]) + '/' + (score[7] + score[9]))
        parent.find('.away_tr .all_court').html(score[1])
        if(2 < newStatus)
        {
            parent.find('.away_tr .bk_match').html('全:' + (score[0] - score[1]))
            parent.find('.away_tr .all_match').html('全:'+(score[0] + score[1]))
        }
        parent.find('.textlive').html(v[21])
        if(parent.attr('game_status') != v[1])
        {
            move_game(parent,v[1])
        }
        //欧赔变化
        if(v[20].length > 3)
        {
            var our_odds = v[20].split(",");
            oddsChange(parent.find('.home_tr .our_odds'),returnFloat(our_odds[0]))
            oddsChange(parent.find('.away_tr .our_odds'),returnFloat(our_odds[1]))
        }
    });
}

//小数位自动补零
function returnFloat(value){
    var xsd=value.toString().split(".");
    if(xsd.length==1){
        value=value.toString()+".00";
        return value;
    }
    if(xsd.length>1){
        if(xsd[1].length<2){
            value=value.toString()+"0";
        }
        return value;
    }
}

//各节数比分变化时变为红色
function score_bg_color(path,data,type)
{
    var status = data[1];
    var order_id = status - 1;
    if(type == 1)
    {
        if(status == 1)
        {
            order_id = 0;
        }else if(status == 3){
            order_id = 1;
        }else if(status > 3)
        {
            order_id = status - 3;
        }
    }
    var home_score = path.find('.home_tr .score_info td').eq(order_id).html();
    var away_score = path.find('.away_tr .score_info td').eq(order_id).html();
    home_score = isNaN(parseInt(home_score))?0:home_score;
    away_score = isNaN(parseInt(away_score))?0:away_score;
    var new_home_score = data[status*2+3];
    var new_away_score = data[status*2+4];
    if(status > 4)
    {
        new_home_score = data[status*2+4];
        new_away_score = data[status*2+5];
    }
    console.log(home_score,new_home_score,away_score,new_away_score);
    if(home_score < new_home_score)
    {
        path.find('.home_tr .score_info td').eq(order_id).addClass('text-red').addClass('strong');
        setTimeout(function(){
            path.find('.home_tr .score_info td').eq(order_id).removeClass('text-red').removeClass('strong');
        },10000);
        path.find('.home_tr .all_court').addClass('up-red');
        setTimeout(function(){
            path.find('.home_tr .all_court').removeClass('up-red');
        },10000);
    }else if(away_score < new_away_score){
        path.find('.away_tr .score_info td').eq(order_id).addClass('text-red').addClass('strong');
        setTimeout(function(){
            path.find('.away_tr .score_info td').eq(order_id).removeClass('text-red').removeClass('strong');
        },10000);
        path.find('.away_tr .all_court').addClass('up-red');
        setTimeout(function(){
            path.find('.away_tr .all_court').removeClass('up-red');
        },10000);
    }
}


function move_game(path,status)
{
    var class_path = 'over';
    if(status > 0)
    {
        class_path = 'now';
    }else{
        var home_score = path.find('.home_tr .all_court').html();
        var away_score = path.find('.away_tr .all_court').html();
        if(home_score < away_score)
        {
            path.find('.home_tr .all_court').addClass('text-blue');
            path.find('.away_tr .all_court').addClass('text-red');
        }else if(home_score > away_score)
        {
            path.find('.home_tr .all_court').addClass('text-red');
            path.find('.away_tr .all_court').addClass('text-blue');
        }else
        {
            path.find('.home_tr .all_court').addClass('text-red');
            path.find('.away_tr .all_court').addClass('text-red');
        }
    }
    $('.'+class_path).append(path);
    if($('.now').html().replace(/(^\s+)|(\s+$)/g,"").length == 0)
    {
        $('.now_title').css('display','none');
    }else{
        $('.now_title').css('display','');
    }
    if($('.unopened').html().replace(/(^\s+)|(\s+$)/g,"").length == 0)
    {
        $('.unopened_title').css('display','none');
    }
    if($('.over').html().replace(/(^\s+)|(\s+$)/g,"").length != 0)
    {
        $('.over_title').css('display','');
    }
    desc_list(class_path)
}

//球队搜索
$(".Colladd").on('click', function () {
    var seach = $("#search_text").val();
    seach = seach.replace(/(^\s*)|(\s*$)/g, "");
    if (seach) {
        $(".mytable01 .list_teble").each(function () {
            if($(this).css('display') != 'none')
            {
                var home = $(this).children('.table').find('.gameHomeName').html();
                var away = $(this).children('.table').find('.gameAwayName').html();
                var union = $(this).children('.table').find('.match-name').html();
                if (home.indexOf(seach) == -1 && away.indexOf(seach) == -1 && union.indexOf(seach) == -1) {
                    $(this).css('display','none');
                }
            }
        });
        bgcolor();
    } else {
        show();
    }
});

//获取最新实时赔率
function getGameOdds(payload) {
    if(payload === undefined) return false;
    // payload = '{"data":{"290263":["6.5","0.96","0.80","212.5","0.83","0.83","-1.5","0.80","0.90","100.5","0.80","0.80"]}}'
    console.log('赔率变化:'+payload)
    var temp = JSON.parse(payload);
    // console.log(temp)
    // if(temp.status != 1) return false;
    var res = temp['data'];
    console.log(res)
    $.each(res, function (key, data) {
        var parent = '';
        parent = $('.mytable01').find('.list_teble[g_id='+key+']');
        if(parent.length == 0)
            return true;
        oddsChange(parent.find('.home_tr .rang_score').children('span').eq(1),returnFloat(data[0]))
        oddsChange(parent.find('.away_tr .rang_score').children('span').eq(1),returnFloat(data[2]))
        oddsChange(parent.find('.home_tr .big_score').children('span').eq(0),data[4])
        oddsChange(parent.find('.away_tr .big_score').children('span').eq(0),data[4])
        oddsChange(parent.find('.home_tr .big_score').children('span').eq(1),returnFloat(data[3]))
        oddsChange(parent.find('.away_tr .big_score').children('span').eq(1),returnFloat(data[5]))

        //处理让分盘第一个数据
        var new_rang = data[1]
        if(new_rang.indexOf('-') == -1 && parent.find('.home_tr .rang_score').children('span').eq(0).html() != '')
        {
            oddsChange(parent.find('.home_tr .rang_score').children('span').eq(0),new_rang)
        }else if(new_rang.indexOf('-') != -1 && parent.find('.away_tr .rang_score').children('span').eq(0).html() != ''){
            oddsChange(parent.find('.away_tr .rang_score').children('span').eq(0),new_rang.substr(1))
        }else{
            parent.find('.home_tr .rang_score').children('span').eq(0).removeClass();
            parent.find('.away_tr .rang_score').children('span').eq(0).removeClass();
            if(new_rang.indexOf('-') == -1){
                parent.find('.home_tr .rang_score').children('span').eq(0).html(new_rang)
                parent.find('.away_tr .rang_score').children('span').eq(0).html('')
            }else{
                parent.find('.home_tr .rang_score').children('span').eq(0).html('')
                parent.find('.away_tr .rang_score').children('span').eq(0).html(new_rang.substr(1))
            }
        }

    });

}

//出入节点路径处理赔率变化
function oddsChange(path,newData)
{
    path.removeClass('up-red');
    path.removeClass('down-green');
    var oldData = path.html();
    path.html(newData);
    if(oldData != '')
    {
        if(oldData < newData)
        {
            path.addClass('up-red')
        }else if(oldData > newData){
            path.addClass('down-green')
        }
    }

}

//让分中文显示
function handCpSpread(score) {
    if (score == '' || score == undefined) return '';
    var preTag = '';
    if (score.indexOf('-') >= 0) {
        preTag = "受";
        var score = score.split('-')[1];
    }
    return preTag + sprScore[score];
}
var reloadTime = 0;
//定时任务
setInterval(function () {
    // 定时刷新比赛分钟数
    $('.gameStatusStr time').each(function (idx, ele) {
        var status = $(this).parents('td').attr('game_state');
        var goMins = parseInt($(this).text().replace("+", "")) + 1;

        switch (status) {
            case '1':
                if (goMins > 45)  goMins = "45+";
                if (goMins < 1)   goMins = "1";
                break;
            case '3':
                if (goMins > 90)  goMins = "90+";
                if (goMins < 1)   goMins = "46";
                break;
        }

        $(this).text(goMins);
    });
    reloadTime ++;
    //30分钟自动刷新页面
    if(reloadTime >= 60){
        reloadTime = 0;
        window.location.reload();
    }
}, 60000);

//移动赛事后进行排序处理
function desc_list(path)
{
    var aDiv = $('.'+ path +' .list_teble');
    var arr = [];
    for(var i=0;i<aDiv.length;i++)
    {
        arr.push(aDiv[i]);  //aDiv是元素的集合，并不是数组，所以不能直接用数组的sort进行排序。
    }
    arr.sort(function(a,b){return a.getAttribute('g_time') - b.getAttribute('g_time')});
    for(var i=0;i<arr.length;i++)
    {
        $('.'+path).append(arr[i]); //将排好序的元素，重新塞到body里面显示。
    }
}


$('.tdBtn').on('click',function(){
    if($(this).hasClass('icon-top')){
        $(this).removeClass('icon-top');
        var list_id = $(this).parents("div:first").attr('data-key');
        $('.mytable01').find('.list_teble[data-key='+list_id+']').removeClass('hidden');
        $('.mytable01').find('.list_teble[data-key='+list_id+']').find('.tdBtn').removeClass('icon-top');
        $(this).parents("div:first").remove()
    }else{
        $(this).addClass('icon-top');
        var game = $(this).parents("div:first");
        $('.mytable01').prepend(game.clone(true));
        game.addClass('hidden');
    }
});