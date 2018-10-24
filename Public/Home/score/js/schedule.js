var domain = config.domain;
var CookieArray = ['1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1','0']; //默认功能设置cookie
$(function () {
    // languageChange();
    //獲取頁面數據
    getHtmlData();
    window.onscroll = function () {
        var homeWidth = $('.home ').width();
        var mTop = $("#nav_tr").offset().top;
        var sTop = document.body.scrollTop;
        var result = mTop - sTop;
        if (result < 0) {
            $("#navig").css({
                'display': 'block',
                'position': 'fixed',
                'margin': '0',
                'top': '0',
                'z-index': 99
            });
            $(".navig_tr").css('width', homeWidth+'px');
        } else {
            $("#navig").css('display', 'none');
        }

    }
    url_name = window.location.pathname.substr(1);
    url_name.substr(0,url_name.length-5);

//赛事选择的js事件--S
var _team = '';

$(".event").on('click', function () {
    _team = $(".box-team").html();
});

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

//赛事选择的js事件--E


$('.time-li a').on('click', function () {
    var div_id = $(this).attr('div_id');
    $('.time-li a').parent('li').removeClass('on');
    $(this).parent('li').addClass('on');
    $("#game_list div").each(function () {
        if ($(this).attr('list_id') == div_id) {
            $("#game_list").children().hide();
            $(this).css('display', 'block');
        }
    });
    bgcolor();
});
/*赛事选择js效果*/
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

//根据赛事选择显示相应的赛事列表跟赛程列表
$("#ensures").on('click', function () {
    //判断是否有勾选联赛
    var is_check = false;
    $('#menu_list input[class=userid]:checkbox').each(function () {
        if ($(this).is(':checked')) {
            is_check = true;
        }
    });
    if (!is_check) {
        showMsg('请选择联赛！', 0, 'error');
        return;
    }
    $(".union_list dd").not(":eq(0)").css('display', 'none');
    $(".game_list tr").css('display', 'none');
    $(".game_list tr").each(function () {
        $(this).attr('list_type', 'old');
    });
    Cookie.setCookie(cookie_name,'show?');
    $(".match-team li").each(function () {
        var cbox = $(this).children('.inline').children('.userid');
        if (cbox.is(':checked')) {
            $(".union_list dd").each(function () {
                if ($(this).attr('union_id') == cbox.attr('union_m')) {
                    $(this).css('display', '');
                }
            });
            $(".game_list tr").each(function () {
                var _this = $(this);
                if (_this.attr('union_id') == cbox.attr('union_m')) {
                    _this.attr('list_type', 'now');
                    _this.css('display', '');
                    savelog(_this.attr('game_id'));
                }
            });
        }
    });
    bgcolor();
    $('.box-list.dropdown-menu').css('display', 'none');
});

//点击全部显示列表
function game_all() {
    $(".union_list dd").each(function () {
        if ($(this).css('display') != 'none') {
            var id = $(this).attr('union_id');
            $(".game_list tr").each(function () {
                if ($(this).attr('union_id') == id) {
                    $(this).css('display', '');
                }
            });
        }
    });
    bgcolor();
}

//根据复选框显示隐藏赛程列表
$(".pitch").on('click', function () {
    _css = $(this).attr('type');
    Cookie.setCookie(cookie_name,'show?');
    if (_css == 'none') {
        $(".game_list tr:visible").each(function () {
            if ($(this).children().children().is(':checked')) {
                var game_id = $(this).attr('game_id');
                $(".game_list tr[game_id="+game_id+"]").css('display','none');
            }
        });
    } else {
        $(".game_list tr:visible").each(function () {
            if(!$(this).hasClass('explain'))
            {
                if ($(this).find('input').is(':checked') == false) {
                    var game_id = $(this).attr('game_id');
                    $(".game_list tr[game_id="+game_id+"]").css('display','none');
                }
            }
        });
    }
    $('.union_list dd:not(.list_all)').css('display','none');
    $('.game_list tr:visible').each(function(){
        savelog($(this).attr('game_id'));
        $(".union_list [union_id="+$(this).attr('union_id')+"]").css('display','');
    });
    bgcolor();
});

//完整选项
$(".type-li01").on('click', function () {
    show();
    Cookie.setCookie(nav_cookie,'type-li01');
    bgcolor();
});
//滚球选项
$(".type-li02").on('click', function () {
    type_li('grounder');
    Cookie.setCookie(nav_cookie,'type-li02');
});
//滚球选项
$(".type-li03").on('click', function () {
    type_li('gues');
    Cookie.setCookie(nav_cookie,'type-li03');
});
//精简选项
$(".type-li04").on('click', function () {

    $('.union_list dd:not(.list_all)').css('display','none');
    Cookie.setCookie(cookie_name,'show?');
    $(".game_list tr").css('display', 'none');
    $(".game_list tr:not(.explain)").each(function () {
        if ($(this).attr('union_level') < 3) {
            $(this).css('display', '');
            savelog($(this).attr('game_id'));
            $(".union_list dd[union_id="+$(this).attr('union_id')+"]").css('display','');
            $('.game_list tr[game_id='+$(this).attr('game_id')+']').css('display','');
        }
    });
    Cookie.setCookie(nav_cookie,'type-li04');
    bgcolor();
});

$(".Colladd").on('click', function () {
    var seach = $("#search_text").val();
    seach = seach.replace(/(^\s*)|(\s*$)/g, "");
    if (seach) {
        $('.game_list tr').not('.explain').each(function () {

            var tableThis = $(this);
            var sign = '';

            //匹配联赛
            var unionName = tableThis.find('.listUnionName').attr('langname');
            if (unionName.toLowerCase().indexOf(seach.toLowerCase()) == -1)
            {
                sign = 'none';
            }
            //匹配主队
            var homeName = tableThis.find('.listHomeName').attr('langname');
            if(sign){
                if (homeName.toLowerCase().indexOf(seach.toLowerCase()) == -1)
                {
                    sign = 'none';
                }else{
                    sign = '';
                }
            }
            //匹配客队
            var awayName = tableThis.find('.listAwayName').attr('langname');
            if(sign){
                if (awayName.toLowerCase().indexOf(seach.toLowerCase()) == -1)
                {
                    sign = 'none';
                }else{
                    sign = '';
                }
            }
            tableThis.css('display',sign);
            var game_id = tableThis.attr('game_id');
            $('.explain[game_id='+game_id+']').css('display',sign);
        });
        bgcolor();
    } else {
        show();
    }
});

//语言切换事件
$('#languageSle li a').on('click', function () {
    //0简体 1繁体 2英语
    var language = $(this).data('language');
    //切换
    $('.changeLang').each(function(){
        var langName = $(this).attr('langName').split(',');
        $(this).text(langName[language]);
    })
    var languageName = $(this).text();
    $('#languageContent').text(languageName);
    Cookie.setCookie('lang', language,5);
})

var teamData = '';
//鼠标移到上全场比分出现（比分事件）
$('.s_score').hover(function (e) {
    var mTop = $(this).parents('tr').offset().top;
    var sTop = document.body.scrollTop;
    var result = mTop - sTop;//tr距离浏览器可视区域顶部的高度
    var butt = document.documentElement.clientHeight - result - 52;//tr距离底部的距离，用于判断
    var game_id = $(this).parents('tr').attr('game_id');
    $.ajax({
        type: "get",
        url:DOMAIN_URL+"/Webfb/detail.html",
        data: {"gameId": game_id},
        cache: false,
        async: false,
        success: function (data) {
            teamData = data.data.t;
        }
    });
    for (var k in teamData) {
        if (k == game_id) {
            var team = teamData[k];
        }
    }
    //主队名称
    var home_team = $(this).prev().find('.changeLang').text();
    //客队名称
    var away_team = $(this).next().find('.changeLang').text();
    var first = $(this).attr('first');
    var html = '<table game_id="' + game_id + '" class="table table-bordered" width="500" bgcolor="#E1E1E1" cellpadding="0" cellspacing="0" border="0" style="width:500px">' +
        '<tbody><tr>' +
        '<td colspan="5" bgcolor="#2e76c7" align="center"><font color="white"><b>初盘参考：' + first + '</b></font></td>' +
        '</tr><tr id="teamhead" bgcolor="#e5e5e5" align="center">' +
        '<td colspan="2" width="44%"><font>' + home_team + '</font></td>' +
        '<td width="12%">时间</td>' +
        '<td colspan="2" width="44%"><font>' + away_team + '</font></td></tr>';
    html += doTeamDetail(team);
    html += '</tbody></table>';
    var tdHeiht = $(this).height() + 5;
    var tdTopHeight = $(this).offset().top;
    var ssHeight = tdHeiht + tdTopHeight;
    var tbHeight = $('.livescore_table').offset().top;
    var myHeight = ssHeight - tbHeight;
    $('#jinq_box').append(html);
    if ($('#jinq_box').height() > butt) {
        myHeight = myHeight - $('#jinq_box').height() - 42;
    }
    $('#jinq_box').stop().fadeIn(0).css({'top': '' + myHeight + 'px'});
}, function () {
    $('#jinq_box').empty();
    $('#jinq_box').stop().fadeOut(0);
});

$(".hover_bg td:not('.no_bg')").hover(function () {
    $(this).addClass('td_bg');
    $(this).siblings('td').not('.no_bg').addClass('td_bg');
}, function () {
    $(this).removeClass('td_bg');
    $(this).siblings('td').removeClass('td_bg');
})

//去掉分析最后一个margin
//$('.pk_msg ul li:last-child a').css('margin-right','0');
//input 背景更换
$('.cb label').click(function (e) {
    if ($(this).hasClass('myLabel02')) {
        $(this).removeClass('myLabel02');
        $(this).siblings('input[type="checkbox"]').attr("checked", true);
    } else {
        $(this).addClass('myLabel02');
        $(this).siblings('input[type="checkbox"]').attr("checked", false);
    }
});
//完整、滚球切换
$('.control-2 li').click(function (e) {
    $(this).addClass('on').siblings().removeClass();
});
//赔率公司切换
$('.odds-company .dropdown-menu li').click(function (e) {
    var companyName = $(this).children('a').text();
    $('.odds-company span').html(companyName);
});
//语言切换
$('.odds-language .dropdown-menu li').click(function (e) {
    var languageName = $(this).children('a').text();
    $('.odds-language span').html(languageName);
});
//赛事选择
$('.event').click(function (e) {
    //根據裂變顯示聯賽
    $('.match-team').find('.userid').prop('checked','');
    $('.union_list dd:visible').each(function(){
        $("[union_m="+$(this).attr('union_id')+"]").prop('checked',true);
    });

    $('.box-list').fadeIn(300);
    $('.layer-list').fadeOut(300);
    $('.gs-list').fadeOut(300);
});
$('#removeAll').click(function (e) {
    $('.box-list').fadeOut(300);
});
$('.rank-ul li').click(function (e) {
    $(this).stop().toggleClass('on');
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
$('#removeAll').click(function (e) {
    $('.box-list').fadeOut(300);
});
$('#closeAll').click(function (e) {
    $('.gs-list').fadeOut(300);
});
$('.rank-ul li').click(function (e) {
    $(this).stop().toggleClass('on');
});
//全选、反选、取消
//添加置顶、智能提醒

});


//cookie读取页面勾选记录
function checklog()
{
    //已隐藏的赛事
    var fbgameHideTr = Cookie.getCookie(cookie_name);
    if(fbgameHideTr == null)
    {
        return true;
    }
    if(fbgameHideTr.length > 5){
        var fbgameHideTrArray = fbgameHideTr.split('?');
        if(fbgameHideTrArray[0] == 'show')
        {
            $('.game_list tr').css('display','none');
            $('.union_list dd:not(.list_all)').css('display','none');
            $('.match-team').find('.userid').prop('checked','');
            $.each(fbgameHideTrArray,function(k,v){
                $(".game_list tr[game_id='"+v+"']").css('display','');
                $("tr[game_id='"+v+"']").find('input').attr('checked',true);
                var union_id = $("tr[game_id='"+v+"']").attr('union_id');
                $("[union_id="+union_id+"]").css('display','');
                $("[union_m="+union_id+"]").prop('checked',true);
            })
        }else{
            $.each(fbgameHideTrArray,function(k,v){
                $("tr[game_id='"+v+"']").css('display','none');
                $("[union_id="+$("tr[game_id='"+v+"']").attr('union_id')+"]").css('display','none');
            })
        }
        bgcolor();
    }
    var navClass = Cookie.getCookie(nav_cookie);
    if(navClass != null)
    {
        $('.nav_list li').removeClass('on');
        $('.'+navClass).parent().addClass('on');
    }
}
//cookie保存赛事勾选
function savelog(gameid)
{
    var log = Cookie.getCookie(cookie_name);
    if(log == null) log = 'show?';
    if(gameid > 0){
        Cookie.setCookie(cookie_name,log + gameid + '?');
    }
}
//赛事选择复选框
function menu_display($num, $che) {
    $(".match-team li").each(function () {
        if ($(this).attr('match_level') == $num) {
            $(this).children('.inline').children('.userid').prop("checked", $che);
        }
    });
}


//列表底色更改
function bgcolor() {
    var left = 1;
    $(".union_list dd").each(function () {
        if ($(this).css('display') != 'none') {
            if (left % 2) {
                $(this).children().removeClass('dd-gray');
            } else {
                $(this).children().addClass('dd-gray');
            }
            left++;
        }
    });
    var _count = 0;
    var right = 1;
    $(".game_list tr").each(function () {
        if ($(this).css('display') != 'none') {
            if (right % 2) {
                $(this).attr('bgcolor', '#ffffff');
            } else {
                $(this).attr('bgcolor', '#f7f7f7');
            }
            right++;
        } else {
            _count++;
        }
    });
    if ($(".union_list").height() < $(".livescore_table").height()) {
        _height = $(".livescore_table").height();
    } else {
        _height = $(".union_list").height();
    }
    $(".content").css('height', _height);
    $(".count").html(_count);
}

//显示全部
function show() {
    $(".game_list tr").css('display', '');
    $(".union_list dd").css('display', '');
    Cookie.setCookie(cookie_name,'');
    bgcolor();
}
//赛事选择动态变化隐藏数量
function dynamic() {
    var num = 0;
    $('#menu_list input[class=userid]:checkbox').each(function () {
        var str = '';
        if (!$(this).is(':checked')) {
            str = $(this).parent().children("em").html();
            str = str.substring(0, str.length - 1);
            str = str.substr(1);
            num = num + Number(str);
        }
    });
    $(".menu_count").html(num);
}

//处理赛事事件html
function doTeamDetail(team) {
    var html = '';
    for (var k in team) {
        var home_team_name = away_team_name = home_img = away_img = img = '';
        var path = staticDomain+'/Public/Home/score/images/event/';
        switch (team[k][2]) {
            case  '1':
                img = '<img src="' + path + 'jinqiu.png" width="15" height="16">';
                break;
            case  '2':
                img = '<img src="' + path + 'red-card.png" width="14" height="18">';
                break;
            case  '3':
                img = '<img src="' + path + 'yellow-card.png" width="14" height="18">';
                break;
            case  '7':
                img = '<img src="' + path + 'penalty.png" width="15" height="16">';
                break;
            case  '8':
                img = '<img src="' + path + 'oolong.png" width="15" height="16">';
                break;
            case  '9':
                img = '<img src="' + path + 'yellow-card.png" width="14" height="18">';
                break;
            case '11':
                img = '<img src="' + path + 'in-out.png" width="22" height="12">';
                break;
            case '13':
                img = '<img src="' + path + 'no-kick.png" width="15" height="16">';
                break;
        }
        if (team[k][1] == 1) {
            var home_team_name = team[k][6];
            var home_img = img;
        } else {
            var away_team_name = team[k][6];
            var away_img = img;
        }
        var color = (k % 2) == 0 ? '#ffffff' : '#f7f7f7';
        html += '<tr bgcolor="' + color + '" align="center">' +
            '<td width="8%">' + home_img + '</td>' +
            '<td width="36%">' + home_team_name + '</td>' +
            '<td width="12%" bgcolor="#e5e5e5">' + team[k][3] + '\'</td>' +
            '<td width="36%">' + away_team_name + '</td>' +
            '<td width="8%">' + away_img + '</td>' +
            '</tr>';
    }
    return html;
}

function type_li(_li) {

    $('.union_list dd:not(.list_all)').css('display','none');
    Cookie.setCookie(cookie_name,'show?');
    $(".game_list tr").css('display', 'none');
    $(".game_list tr:not('.explain')").each(function () {
        if ($(this).attr(_li) == 1) {
            savelog($(this).attr('game_id'))
            $(".union_list dd[union_id="+$(this).attr('union_id')+"]").css('display','');
            $('.game_list tr[game_id='+$(this).attr('game_id')+']').css('display','');
        }
    });
    bgcolor();
}

var ajaxData = new Array();
//獲取頁面數據拼接html
function getHtmlData()
{
    $.ajax({
        type: "get",
        url: '/getOverGame.html',
        cache: false,
        data: {'time':listTime,'type':type},
        dataType : 'json',
        success: function (data) {
            if(data.status == 1){
                var game = data.data.info;
                var union = data.data.match;
                //處理聯賽相關的html
                var listHtml = gameList = navHtml = '';
                for(var i=0;i<union.length;i++)
                {
                    if(union[i]['union_name'])
                    {
                        listHtml += '<li style="border-color: '+union[i]['union_color']+';" match_level="'+union[i]['level']+'">'+
                            '<label class="inline">'+
                            '<input type="checkbox" class="userid" id="" checked="" value="option1"  union_m="'+union[i]['union_id']+'">'+
                            '<span class="changeLang" langName="'+union[i]['union_name'][0]+','+union[i]['union_name'][1]+','+union[i]['union_name'][2]+'">'+langSwitch(union[i]['union_name'][0],union[i]['union_name'][1],union[i]['union_name'][2])+'</span>'+
                            '<em>['+union[i]['total']+']</em>'+
                            '</label>'+
                            '</li>';
                        navHtml += '<dd union_id="'+union[i]['union_id']+'">'+
                            '<a id="union_bg" href="javascript:;">'+
                            '<span class="changeLang" langName="'+union[i]['union_name'][0]+','+union[i]['union_name'][1]+','+union[i]['union_name'][2]+'">'+langSwitch(union[i]['union_name'][0],union[i]['union_name'][1],union[i]['union_name'][2])+'</span>'+
                            '</a>'+
                            '</dd>';
                    }
                }

                $('.match-team').append(listHtml);
                $('.union_list').append(navHtml);

                //處理賽事列表html
                for(var i=0;i<game.length;i++)
                {
                    gameList += getGmaeHtml(game[i],i);
                }
                $('.game_list').append(gameList);
            }
            union_listClick();
            checklog();
            $(".loading_gif").hide();
            $(".contentTable").show();
        }
    });
}


//生成賽事列表html
function getGmaeHtml(data,i)
{
    //列表背景色
    var _color = '#ffffff';
    if(i%2){
        _color = '#f1f1f1';
    }
    var union_name = langSwitch(data[2][0],data[2][1],data[2][2]);//聯賽名
    var home_name = langSwitch(data[9][0],data[9][1],data[9][2]);//主隊名
    var away_name = langSwitch(data[10][0],data[10][1],data[10][2]);//客隊名

    //主隊名的相關html
    var homeHtml = awayHtml = '';
    if(data[17] > 0){homeHtml = '<em class="red-card mr5">'+data[17]+'</em>';}
    if(data[19] > 0){homeHtml += '<em class="yellow-card mr5">'+data[19]+'</em>';}
    homeHtml += '<em class="mr5">';
    if(data[11] != '') homeHtml += [data[11]];
    homeHtml += '</em>';

    //客隊名相關的html
    awayHtml = '<em class="ml5">';
    if(data[12] != '') awayHtml += [data[12]];
    awayHtml += '</em>&nbsp;';
    if(data[18] > 0){awayHtml += '<em class="red-card mr5">'+data[18]+'</em>';}
    if(data[20] > 0){awayHtml += '<em class="yellow-card mr5">'+data[20]+'</em>';}

    var sb = '--'
    if(data['sb'] != ''){sb = data['sb'];}

    //情報等入口
    var gambleStr = data['tuijian'] == 1 ? '<a href="/gambleDetails/game_id/'+data[0]+'.html" target="_blank" class="text-bblue">推荐</a>' : '';
    var newsStr = data['news'] == 1 ? '<a href="/news/game_id/'+data[0]+'.html" target="_blank" class="text-bblue">情报</a>' : '';
    if(data[33] != '' || data[34] != ''){
        var brStr = (data[33] != '' && data[34] != '') ? '<br>' : '';
        var explainStr = data[33] + brStr + data[34];
        var explain = '<tr union_id="'+data[1]+'" class="explain" game_id="'+data[0]+'"><td bgcolor="#e6e6eb" class="overtime-data2" colspan="18">'+explainStr+'</td></tr>';
    }else{
        var explain = '';
    }

    var score = '--';
    if(data[13] != '' && data[14] != ''){
        score = '<font class="'+data['home_col']+'">'+data[13]+'</font>:<font class="'+data['away_col']+'">'+data[14]+'</font>';
    }
//賠率跳轉連接
    var peiUrl = '/oddsinfo/game_id/'+data[0]+'/sign/4.html';
    if(sb == '--'){
        peiUrl = '/oddsinfo/game_id/'+data[0]+'/compid/8/sign/4.html';
    }
    var unionUrl = 'javascript:void(0);';
    if(data[38] == '2'){
        unionUrl = '//data.'+domain+'/cupMatch/'+data[1]+'.html';
    }else if(data[38] == '1'){
        unionUrl = '//data.'+domain+'/league/'+data[1]+'.html';
    }
    var eventUrl = '/event_technology/game_id/'+data[0]+'.html';
    if(data[5] == 0)
    {
        eventUrl = '/dataFenxi/game_id/'+data[0]+'.html';
    }
    var html = '<tr union_id="'+data[1]+'" game_id="'+data[0]+'" bgcolor="'+_color+'" order="1" union_level="'+data['union_level']+'" grounder="'+data['is_go']+'" gues="'+data['is_betting']+'" >'+
    '<td class="no-b-r"><input type="checkbox" id="" value="option0"></td>'+
        '<td class="match-name no-b-r no-b-l"><a href="'+unionUrl+'" target="_blank" style="background: '+data[3]+'">'+
        '<span class="listUnionName changeLang" langName="'+data[2][0]+','+data[2][1]+','+data[2][2]+'">'+union_name+'</span>'+
    '</a></td>'+
    '<td class="no-b-r no-b-l">'+data[7]+'</td>'+
        '<td class="text-red no-b-r no-b-l">'+data['game_state']+'</td>'+
    '<td class="no-b-r no-b-l">--</td>'+
        '<td class="text-r"><a target="_blank" title="'+home_name+'" href="//data.'+domain+'/team/'+data[35]+'.html">'+homeHtml+
    '<span class="listHomeName changeLang" langName="'+data[9][0]+','+data[9][1]+','+data[9][2]+'">'+home_name+'</span>'+
    '</a></td>'+
    '<td class="s_score" style="cursor:pointer;" first="'+data['sb']+'">'+
        '<a target="_blank" href="'+eventUrl+'"><strong>'+score+'</strong></a></td>'+
        '<td class="text-l"><a target="_blank" title="'+away_name+'" href="//data.'+domain+'/team/'+data[36]+'.html">'+
        '<span class="listAwayName changeLang" langName="'+data[10][0]+','+data[10][1]+','+data[10][2]+'">'+away_name+'</span>'+awayHtml+
    '</a></td>'+
    '<td ><div class="jqColor"><strong>'+data[21]+'</strong>-<strong>'+data[22]+'</strong></div><div class="bcColor"><strong>'+data[15]+'-'+data[16]+'</strong></div></td>'+
        '<td aloc="1" class="text-green">'+sb+'</td>'+
    '<td><font color="'+data['win_col']+'">'+data['win']+'</font></td>'+
    '<td class="">'+data['total']+'</td>'+
    '<td class="'+data['score_col']+'">'+data['score']+'</td>'+
    '<td class="'+data['double_col']+'">'+data['double']+'</td>'+
    '<td class="b-l">'+data['draw']+'</td>'+
    '<td class="b-l">'+
        '<div class="dataLink">'+
            '<a href="'+peiUrl+'" target="_blank">赔率</a>'+
            '<a href="/dataFenxi/game_id/'+data[0]+'.html" target="_blank">分析</a>'+
            newsStr+gambleStr+
    '</div>'+
    '</td>'+
    '</tr>'+explain;
    return html;
}

//切換 語言
function langSwitch(lang1,lang2,lang3)
{
    var arr = new Array();
    arr[0] = lang1;
    arr[1] = lang2;
    arr[2] = lang3;
    var type = getFbCookie(12)?getFbCookie(12):0;
    return arr[type];
}


//语言切换事件
$('#languageSle li a').on('click', function () {
    //0简体 1繁体 2英语
    var language = $(this).data('language');
    //切换
    $('.changeLang').each(function(){
        var langName = $(this).attr('langName').split(',');
        $(this).text(langName[language]);
    })
    var languageName = $(this).text();
    $('#languageContent').text(languageName);
    setFbCookie(12,language);
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

//点击左侧显示对应赛事列表
function union_listClick()
{
    $(".union_list dd").on('click', function () {
        $(".union_list dd").each(function () {
            $(this).children().removeClass('on');
        });
        $(this).children().addClass('on');
        var id = $(this).attr('union_id');
        $(".game_list tr").css('display', 'none');
        if (id == 0) {
            game_all();
        } else {
            $("tr[union_id="+id+"]").css('display','');
        }
        var mTop = $(".control-con").offset().top;
        if ($("#navig").css('display') == 'block') {
            $('body,html').animate({'scrollTop': mTop}, 200);
        }
        bgcolor();
    });
}




