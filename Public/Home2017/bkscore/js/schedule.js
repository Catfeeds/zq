var url_name = '';
$(function () {
    // languageChange();
    url_name = window.location.pathname.substr(1);
    url_name.substr(0,url_name.length-5);
    checklog();
    $(".loading_gif").hide();
    $(".contentTable").show();
});
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

//cookie读取页面勾选记录
function checklog()
{
    //已隐藏的赛事
    var fbgameHideTr = Cookie.getCookie(url_name);
    if(fbgameHideTr == null)
    {
        return true;
    }
    if(fbgameHideTr.length > 5){
        var fbgameHideTrArray = fbgameHideTr.split('?');
        if(fbgameHideTrArray[0] == 'show')
        {
            $('.mytable01 .list_teble').css('display','none');
            $.each(fbgameHideTrArray,function(k,v){
                $("tr[g_id='"+v+"']").css('display','');
                $("tr[g_id='"+v+"'] td").eq(0).children().attr('checked',true);
            })
        }else{
            $.each(fbgameHideTrArray,function(k,v){
                $("tr[g_id='"+v+"']").css('display','none');
            })
        }
        bgcolor();
    }
}
//cookie保存赛事勾选
function savelog(gameid)
{
    var log = Cookie.getCookie(url_name);
    if(gameid > 0){
        Cookie.setCookie(url_name,log + gameid + '?');
    }
}



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
//赛事选择复选框
function menu_display($num, $che) {
    $(".match-team li").each(function () {
        if ($(this).attr('match_level') == $num) {
            $(this).children('.inline').children('.userid').attr("checked", $che);
        }
    });
}
//根据赛事选择显示相应的赛事列表跟赛程列表
$("#ensures").on('click', function () {
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
});

//点击左侧显示对应赛事列表
$(".union_list dd").on('click', function () {
    $(".union_list dd").each(function () {
        $(this).children().removeClass('on');
    });
    $(this).children().addClass('on');
    var id = $(this).attr('union_id');
    $(".mytable01 .list_teble").css('display', 'none');
    if (id == 0) {
        game_all();
    } else {
        $(".mytable01 .list_teble").each(function () {
            if ($(this).attr('game_id') == id) {
                $(this).css('display', '');
            }
        });
    }
    var mTop = $(".control-con").offset().top;
    if ($("#navig").css('display') == 'block') {
        $('body,html').animate({'scrollTop': mTop}, 200);
    }
    bgcolor();
});
//点击全部显示列表
function game_all() {
    $(".union_list dd").each(function () {
        if ($(this).css('display') != 'none') {
            var id = $(this).attr('union_id');
            $(".mytable01 .list_teble").each(function () {
                if ($(this).attr('game_id') == id) {
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
    var _url = Cookie.getCookie(url_name);
    console.log(_css)
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
    bgcolor();
});

//列表底色更改
function bgcolor() {
    var num = 0;
    $(".mytable01 .list_teble").each(function () {
        if($(this).css('display') == 'none')
        {
            num++;
        }
    });
    $(".count").html(num);
}
//完整选项
$(".type-li01").on('click', function () {
    $(".mytable01 .list_teble").css('display', 'none');
    $(".mytable01 .list_teble").each(function () {
        if (typeof($(this).attr('list_type')) == 'undefined') {
            _type = 'now';
        } else {
            _type = $(this).attr('list_type');
        }
        if (_type == 'now') {
            $(this).css('display', '');
        }
    });
    bgcolor();
});
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
    Cookie.setCookie(url_name,'');
    bgcolor();
}

$(".Colladd").on('click', function () {
    var seach = $("#search_text").val();
    seach = seach.replace(/(^\s*)|(\s*$)/g, "");
    console.log(seach)
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
    sessionStorage.setItem('indicesLanguageSle', language);
    //页面语言显示改变
    languageChange();

})

//页面语言显示改变
function languageChange() {
    var language = sessionStorage.getItem('indicesLanguageSle');
    $('.language').css('display', 'none');
    $('.lang_cn').addClass("lang_css");
    $('.lang_tw').addClass("lang_css");
    $('.lang_en').addClass("lang_css");
    console.log(language);
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
var teamData = '';
//鼠标移到上全场比分出现（比分事件）
$('.s_score').hover(function (e) {
    var mTop = $(this).parents('tr').offset().top;
    var sTop = document.body.scrollTop;
    var result = mTop - sTop;//tr距离浏览器可视区域顶部的高度
    var butt = document.documentElement.clientHeight - result - 52;//tr距离底部的距离，用于判断
    var game_id = $(this).parents('tr').attr('g_id');
    $.ajax({
        data: "get",
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
    var home_team = '';
    var away_team = '';
    $(this).prev().find('strong').each(function () {
        if ($(this).css('display') != 'none') {
            home_team = $(this).text();
        }
    });
    $(this).next().find('strong').each(function () {
        if ($(this).css('display') != 'none') {
            away_team = $(this).text();
        }
    });
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

//赛事选择动态变化隐藏数量
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

//处理赛事事件html
function doTeamDetail(team) {
    var html = '';
    for (var k in team) {
        var home_team_name = away_team_name = home_img = away_img = img = '';
        var path = '/Public/Home/score/images/event/';
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


$(function () {

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
    // old_value = $("#search_text").val();
    // $("#search_text").focus(function () {
    //     if ($("#search_text").val() == "") {
    //         AutoComplete("auto_div", "search_text", test_list);
    //     }
    // });
    //
    // $("#search_text").keyup(function () {
    //     AutoComplete("auto_div", "search_text", test_list);
    // });
});

$('.tdBtn').on('click',function(){
    if($(this).hasClass('icon-top')){
        $(this).removeClass('icon-top');
        var list_id = $(this).parents("div:first").attr('data-key');
        $('.mytable01').children('.list_teble[data-key='+list_id+']').removeClass('hidden');
        $('.mytable01').children('.list_teble[data-key='+list_id+']').find('.tdBtn').removeClass('icon-top');
        $(this).parents("div:first").remove()
    }else{
        $(this).addClass('icon-top');
        var game = $(this).parents("div:first");
        $('.mytable01').prepend(game.clone(true));
        game.addClass('hidden');
    }
});





