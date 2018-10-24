/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 *
 * @since  2018-01-10
 *
 **/
var fncData;
$(function () {
    //banner
    $('.banner01').hover(function (e) {
        $('.banner01 .carousel-control').stop().fadeIn(500);
    }, function () {
        $('.banner01 .carousel-control').stop().fadeOut(500);
    });
    $('.banner01 .carousel-control').hover(function (e) {
        $(this).animate({"opacity": "0.75"}, 200);
    }, function () {
        $(this).animate({"opacity": "0.5"}, 200);
    });

    $(".introBox").mCustomScrollbar({
        theme: "light-3",
        autoDraggerLength: true
    });


    $(document).on('click', '#roundList  li', function () {
        curRound = $(this).text();
        $('.rouNum').text('第' + curRound + '轮');
        getStatit();
        lineup();
    });

    $('li.arrsub').click(function () {
        $(this).parent().siblings('button.butText').text($(this).text());
        var r = $(this).attr('data-round');
        var subId = $(this).attr('data-sub');
        $('.rouNum').text('第' + r + '轮');
        uni(r);
        if(curSubLeague){
            curRound = r;
            //切换当前联赛
            $.each(arrSubLeague,function (k,v) {
                if(v[0] == subId){
                    curSubLeague = v;
                }
            });
        }
        getStatit();
        lineup();
        selectScoreRank();
    });

    $(document).on('click', '.scoreNav  li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        selectScoreRank();
    });

    $(document).on('click', '.tableData03 .tabNav  li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        exp_bigsmall_st();
    });

    $(document).on('click', '.tableData03 .liTab  a', function () {
        $(this).addClass('on').siblings().removeClass('on');
        exp_bigsmall_st();
    });

    $(document).on('click', '.tableData06 .tabNav  li', function () {
        $(this).addClass('on').siblings().removeClass('on');

        if ($(this).index() == 0) {
            if (fncData.firGetLose != null) {
                var arr_data = fncData.firGetLose.arr_data;
                var html1 = '<thead> ' +
                    '<tr bgcolor="#cfd2dd"> ' +
                    '<th width="33.3%" rowspan="2">球队</th> ' +
                    '<th width="33.3%" colspan="3">最先入球统计</th> ' +
                    '<th width="33.3%" colspan="3">最先失球统计</th> ' +
                    '</tr> </thead> <tbody>' +
                    '<tr bgcolor="#f0f2f7"> <td></td> ' +
                    '<td>总</td> ' +
                    '<td>主</td> ' +
                    '<td>客</td> ' +
                    '<td>总</td> ' +
                    '<td>主</td> ' +
                    '<td>客</td> ' +
                    '</tr>';

                $.each(arr_data, function (k, v) {
                    html1 += '<tr> ' +
                        '<td class="f12"> <a href="javascript:;" target="_blank"> <img class="mr10" src="" width="20" height="auto">' + teams[v[0]]['team_name'] + ' </a> </td> ' +
                        '<td class="f12 text-red">' + v[1] + '</td> ' +
                        '<td class="f12 text-666">' + v[2] + '</td> ' +
                        '<td class="f12 text-666">' + v[3] + '</td> ' +
                        '<td class="f12 text-red">' + v[4] + '</td> ' +
                        '<td class="f12 text-666">' + v[5] + '</td> ' +
                        '<td class="f12 text-666">' + v[6] + '</td> ' +
                        '</tr>';
                });

                $("#tableData06").html(html1);
            }

        } else {
            if (fncData.nogetlose) {
                var nogetlose = fncData.nogetlose;
                var html1 = '<thead> ' +
                    '<tr bgcolor="#f8f9fd"> ' +
                    '<th width="" rowspan="3" bgcolor="#cfd2dd">球队名称</th> ' +
                    '<th width="126" colspan="7" bgcolor="#cfd2dd">总</th> ' +
                    '<th width="126" colspan="7" bgcolor="#cfd2dd">主</th> ' +
                    '<th width="126" colspan="7" bgcolor="#cfd2dd">客</th> ' +
                    '</tr> ' +
                    '<tr bgcolor="#f8f9fd">' +
                    '<th width="28" rowspan="2" bgcolor="#f8f9fd">赛</th> ' +
                    '<th width="126" colspan="3" bgcolor="#abb0c4">没入球</th> ' +
                    '<th width="126" colspan="3" bgcolor="#abb0c4">没失球</th> ' +
                    '<th width="28" rowspan="2" bgcolor="#f8f9fd">赛</th> ' +
                    '<th width="126" colspan="3" bgcolor="#abb0c4">没入球</th> ' +
                    '<th width="126" colspan="3" bgcolor="#abb0c4">没失球</th> ' +
                    '<th width="28" rowspan="2" bgcolor="#f8f9fd">赛</th> ' +
                    '<th width="126" colspan="3" bgcolor="#abb0c4">没入球</th> ' +
                    '<th width="126" colspan="3" bgcolor="#abb0c4">没失球</th> ' +
                    '</tr> ' +
                    '<tr bgcolor="#f8f9fd"> ' +
                    '<th width="28" bgcolor="#cfd2dd">全场</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">上半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">下半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">全场</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">上半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">下半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">全场</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">上半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">下半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">全场</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">上半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">下半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">全场</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">上半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">下半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">全场</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">上半</th> ' +
                    '<th width="28" bgcolor="#cfd2dd">下半</th> ' +
                    '</tr> </thead>' +
                    '<tobody>';

                $.each(nogetlose, function (k, v) {
                    html1 += '<tr> ' +
                        '<td bgcolor="#f0f2f7">' + teams[v[0]]['team_name'] + ' </td> ' +
                        '<td class="text-red f12">' + v[1] + '</td> ' +
                        '<td class="text-666 f12">' + v[2] + '</td> ' +
                        '<td class="text-666 f12">' + v[3] + '</td> ' +
                        '<td class="text-666 f12">' + v[4] + '</td> ' +
                        '<td class="text-666 f12">' + v[5] + '</td> ' +
                        '<td class="text-666 f12">' + v[6] + '</td> ' +
                        '<td class="text-666 f12"> ' + v[7] + '</td> ' +
                        '<td class="text-orange f12">' + v[8] + '</td> ' +
                        '<td class="text-666 f12">' + v[9] + '</td> ' +
                        '<td class="text-666 f12">' + v[10] + '</td> ' +
                        '<td class="text-666 f12">' + v[11] + '</td> ' +
                        '<td class="text-666 f12">' + v[12] + '</td> ' +
                        '<td class="text-666 f12">' + v[13] + '</td> ' +
                        '<td class="text-666 f12">' + v[14] + '</td> ' +
                        '<td class="text-green f12">' + v[15] + '</td> ' +
                        '<td class="text-666 f12">' + v[16] + '</td> ' +
                        '<td class="text-666 f12">' + v[17] + '</td> ' +
                        '<td class="text-666 f12">' + v[18] + '</td> ' +
                        '<td class="text-666 f12">' + v[19] + '</td> ' +
                        '<td class="text-666 f12">' + v[20] + '</td> ' +
                        '<td class="text-666 f12">' + v[21] + '</td> ' +
                        '</tr>';
                });
                html1 += '</tobody>';
                $("#tableData06").html(html1);
            }

        }
    });

    // uni(curRound);
    //赛程
    getStatit();
    //积分榜
    selectScoreRank('total_score');
    //盘路统计
    exp_bigsmall_st();
    //射手榜、积分榜
    goals_rank(1, 0);
    //标准亚盘对照
    fnc();
    //流言转会
    tranRumor();
    hot_and_mip();
    statsaly_tzuqiu();

    //最佳阵容
    lineup();
    //league_best_player_cancel();

    $('.tableData01 tbody tr:even').css("backgroundColor", "#f8f9fd");
    $('#rumor tr:even').css("backgroundColor", "#f8f9fd");
    $('#tran tr:even').css("backgroundColor", "#f8f9fd");
    $('.teamWorth tr:even').css("backgroundColor", "#f8f9fd");

    $(document).on('click', '#zhuanhui  a', function () {
        $(this).addClass('on').siblings().removeClass('on');
        var index = $(this).index();
        $("#zhuanhuiTab tbody").eq(index).css('display', '').siblings().css('display', 'none')
    });
});
var unions = JSON.parse(union);

function uni(r) {
    var n = new Array(parseInt(r));
    var html = '';
    for(var i=0;i<n.length;i++){
        var tem = n.length - i;
        html += '<li><a href="javascript:void(0);">' + tem + '</a></li>';
    }
    $('#roundList').html(html);
}

/**
 * 联赛赛程
 */
function getStatit() {
    var curSeason = $('.butText:first').text();
    if(curSubLeague){
        var pData = {season: curSeason, round: curRound, union_id: union_id, subLeagueId: curSubLeague[0]}
    }else{
        var pData = {season: curSeason, round: curRound, union_id: union_id}
    }
    $.ajax({
        type: 'POST',
        url: "/league_statistics",
        dataType: 'json',
        data: pData,
        success: function (e) {
            var html = '';
            if (e.status == 1 && e.list && e.list != '') {
                $.each(e.list, function (k, v) {
                    var c_url = v.gamble ? '<a href="' + v.c_url + '" class="text-bblue" target="_blank">猜</a>' : '';
                    if (v.half_score == '') {
                        var score = '-';
                    } else {
                        var score = '';
                        if (v.half_score != '-' && v.score == '-') {
                            score += '<p>-</p>';
                        }

                        if (v.score != '-') {
                            score += '<p><span class="text-red2">' + v.hscore + '</span> : <span>' + v.ascore + '</span></p>';
                        }

                        score += '<p class="text-999">(' + v.half_score + ')</p>';
                    }
                    //赔率处理
                    var defaultOdds = '-|-|-|-|-|-'.split('|');
                    var asia_odds_sb = v.asia_odds_sb != undefined ? v.asia_odds_sb.split('|') : defaultOdds;
                    var bigsmall_odds_sb = v.bigsmall_odds_sb != undefined ? v.bigsmall_odds_sb.split('|') : defaultOdds;
                    var asia_odds_sb_half = v.asia_odds_sb_half != undefined ? v.asia_odds_sb_half.split('|') : defaultOdds;
                    var bigsmall_odds_sb_half = v.bigsmall_odds_sb_half != undefined ? v.bigsmall_odds_sb_half.split('|') : defaultOdds;

                    html += '<tr> ' +
                        '<td class="f12"><div class="rboxH"><p class="text-999">' + v.gdate + '</p><p>' + v.gtime + '</p></div></td> ' +
                        '<td class="f12 text-r"><a href="/team/' + v.home_team_id + '.html" target="_blank">' + v.home_team_name + '</a></td> ' +
                        '<td class="f12"><a href="javascript:;" class="scoreLink">' + score +
                        '</a></td> ' +
                        '<td class="f12 text-l"><a href="/team/' + v.away_team_id + '.html" target="_blank">' + v.away_team_name + '</a></td> ' +
                        '<td width="40" class="f12 oddss"><a href="javascript:;">' +
                        '<div class="lboxH">' +
                        '<div class="ypCheck ">' + asia_odds_sb[0] + '</div>' +
                        '<div class="sizeCheck ">' + bigsmall_odds_sb[0] + '</div>' +
                        '</div></a>' +
                        '</td> ' +
                        '<td width="86" class="f12 oddss"><a href="javascript:;">' +
                        '<div class="ypCheck ">' + asia_odds_sb[1] + '</div>' +
                        '<div class="sizeCheck ">' + bigsmall_odds_sb[1] + '</div></a>' +
                        '</td> ' +
                        '<td width="40" class="f12 oddss"><a href="javascript:;">' +
                        '<div class="rboxH">' +
                        '<div class="ypCheck ">' + asia_odds_sb[2] + '</div>' +
                        '<div class="sizeCheck ">' + bigsmall_odds_sb[2] + '</div>' +
                        '</div></a>' +
                        '</td> ' +
                        '<td width="40" class="f12 oddss"><a href="javascript:;">' +
                        '<div class="ypCheck ">' + asia_odds_sb_half[0] + '</div>' +
                        '<div class="sizeCheck ">' + bigsmall_odds_sb_half[0] + '</div></a>' +
                        '</td> ' +
                        '<td width="86" class="f12 oddss"><a href="javascript:;">' +
                        '<div class="ypCheck ">' + asia_odds_sb_half[1] + '</div>' +
                        '<div class="sizeCheck ">' + bigsmall_odds_sb_half[1] + '</div></a>' +
                        '</td> ' +
                        '<td width="40" class="f12 oddss"><a href="javascript:;">' +
                        '<div class="rboxH">' +
                        '<div class="ypCheck ">' + asia_odds_sb_half[2] + '</div>' +
                        '<div class="sizeCheck ">' + bigsmall_odds_sb_half[2] + '</div></div></a>' +
                        '</td>' +
                        '<td width="60" class="f12">' +
                        '<div class="dataLink text-l"> ' +
                        '<a href="' + v.y_url + '" target="_blank">亚</a> ' +
                        '<a href="' + v.o_url + '" target="_blank">欧</a> ' +
                        '<a href="' + v.x_url + '" target="_blank" class="text-bblue">析</a> ' +
                        c_url +
                        '</div> ' +
                        '</td> ' + '</tr>';
                });
                $('#statistics').html(html);
                $('#statistics tr:even').css("backgroundColor", "#f8f9fd");

            }else{
                $('#statistics').html('');
            }
        },
    });
}

function selectScoreRank(subId) {
    var curSeason = $('.butText:first').text();
    var data_type = $('.tableData02 .scoreNav .on').attr('data-type');
    if(curSubLeague){
        var pData = {data_type: data_type, season: curSeason, union_id: union_id, subId:curSubLeague[0]}
    }else{
        var pData = {data_type: data_type, season: curSeason, union_id: union_id}
    }
    $.ajax({
        type: 'POST',
        url: "/league_score_rank",
        dataType: 'json',
        data: pData,
        success: function (e) {
            var html = '';

            if (e.status == 1 && e.list) {
                $.each(e.list, function (k, v) {
                    var gamble = '';
                    
                    if('total_score' == data_type){
                    	if (v.gamble != undefined) {
                            for (var i = 0; i < v.gamble.length; i++) {
                                if (v.gamble[i] == 1) {
                                    gamble += '<span class="text-blue">D</span>';
                                } else if (v.gamble[i] == 2) {
                                    gamble += '<span class="text-green">L</span>';
                                } else {
                                    gamble += '<span class="text-red">W</span>';
                                }
                            }
                        }
                    }
                    var sc = e.scoreColor[v[0]] != undefined ? e.scoreColor[v[0]].split('|') : [];
                    if (data_type == 'total_score') {
                        html += '<tr bgcolor="#f8f9fd">' +
                            '<td class=""><span class="rank " style="background:'+sc[0]+'">' + (k + 1) + '</span></td> ' +
                            '<td class="f12 text-l">' +
                            '<img src="' + v[2].img_url + '" width="20" height="20" class="mr10"><a href="' + v[2].url + '" target="_blank">' + v[2]['team_name'] + '</a>' +
                            '</td> ' +
                            '<td class="f12">' + v[4] + '</td> ' +
                            '<td class="f12">' + v[5] + '</td> ' +
                            '<td class="f12">' + v[6] + '</td> ' +
                            '<td class="f12">' + v[7] + '</td> ' +
                            '<td class="f12">' + v[8] + '</td> ' +
                            '<td class="f12">' + v[9] + '</td> ' +
                            '<td class="f12">' + v[10] + '</td> ' +
                            '<td class="f12">' + v[11] + '%</td> ' +
                            '<td class="f12">' + v[16] + '</td> ' +
                            '<td class="f12 text-uppercase">' + gamble + '</td> ' +
                            '</tr>';
                        
                        $('.league_lastest_6').show();
                    } else {
                        html += '<tr bgcolor="#f8f9fd">' +
                            '<td class=""><span class="rank"  style="background:'+sc[0]+'">' + (k + 1) + '</span></td> ' +
                            '<td class="f12 text-l">' +
                            '<img src="' + v[1].img_url + '" width="20" height="20" class="mr10">' +
                            '<a href="' + v[1].url + '" target="_blank">' + v[1]['team_name'] + '</a></td> ' +
                            '<td class="f12">' + v[2] + '</td> ' +
                            '<td class="f12">' + v[3] + '</td> ' +
                            '<td class="f12">' + v[4] + '</td> ' +
                            '<td class="f12">' + v[5] + '</td> ' +
                            '<td class="f12">' + v[6] + '</td> ' +
                            '<td class="f12">' + v[7] + '</td> ' +
                            '<td class="f12">' + v[8] + '</td> ' +
                            '<td class="f12">' + v[9] + '%</td> ' +
                            '<td class="f12">' + v[14] + '</td> ' +
                           // '<td class="f12 text-uppercase">' + gamble + '</td> ' + // 不显示近6轮
                            '</tr>';
                        
                        $('.league_lastest_6').hide();
                    }

                });
                $('#scoreRank').html(html);
                $('#scoreRank').parents('.tableData02').css('display', '');
                $('#scoreRank tr:even').css("backgroundColor", "#ffff");
            }else{
                $('#scoreRank').parents('.tableData02').css('display', 'none');
            }
        },
    });
}

//让球、大小盘路统计
function exp_bigsmall_st() {
    var curSeason = $('.butText').eq(0).text();
    var data_type = $('.tableData03 .tabNav .on').attr('data-type');
    var sub_type = $('.tableData03 .liTab .on').attr('sub-data-type');

    var sub_type_map = {
        letGoal: ['total_pan_lou', 'home_pan_lu', 'guest_pan_lu', 'total_half_pan_lu', 'home_half_pan_lu', 'guest_half_pan_lu'],
        bigSmall: ['TotalBs', 'HomeBs', 'GuestBs', 'TotalBsHalf', 'HomeBsHalf', 'GuestBsHalf']
    };

    if (data_type == 'bigSmall') {
        $('#oddsNav').html('<tr bgcolor="#f0f2f7"> ' +
            '<th width="50">排名</th> ' +
            '<th width="">球队</th> ' +
            '<th width="30">赛</th> ' +
            '<th width="42">大球</th> ' +
            '<th width="42">走</th> ' +
            '<th width="42">小球</th> ' +
            '<th width="50">大球%</th> ' +
            '<th width="50">走%</th> ' +
            '<th width="50">小球%</th> ' +
            '</tr>')
    } else if (data_type == 'letGoal') {
        $('#oddsNav').html('<tr bgcolor="#f0f2f7"> ' +
            '<th width="50">排名</th> ' +
            '<th width="210">球队</th> ' +
            '<th width="30">赛</th> ' +
            '<th width="42">上盘</th> ' +
            '<th width="42">平盘</th> ' +
            '<th width="42">下盘</th> ' +
            '<th width="30">赢</th> ' +
            '<th width="30">走</th> ' +
            '<th width="30">输</th> ' +
            '<th width="30">净</th> ' +
            '<th width="50">胜%</th> ' +
            '<th width="50">走%</th> ' +
            '<th width="50">负%</th> ' +
            '</tr>');
    }

    var s1 = data_type == 'letGoal' ? '让球' : '大小球';
    $('#oddsTech1 thead th').text('全场' + s1 + '盘路数据统计');

    var s2 = data_type == 'letGoal' ? '让球' : '大小球';
    $('#oddsTech2 thead th').text('半场' + s2 + '盘路数据统计');

    $.ajax({
        type: 'POST',
        url: "/tech_statistics",
        dataType: 'json',
        data: {data_type: data_type, season: curSeason, union_id: unions.union_id},
        success: function (e) {
            var html = '';
            if (e.status == 1) {
                if(!e.list){
                    $('.tableData03').remove();
                }else{
                    var data = sub_type != undefined ? e.list[sub_type_map[data_type][sub_type]] : e.list[sub_type_map[data_type]];
                    $.each(data, function (k, v) {
                        teams =  e.teams;
                        var teamName = teams[v[1]] != undefined ? teams[v[1]]['team_name'] :'';
                        if (data_type == 'letGoal') {

                            html += '<tr> ' +
                                '<td><i>' + (k + 1) + '</i></td> ' +
                                '<td class="f12"><a href="/team/' + v[1] + '.html" class="text-red" target="_blank">' + teamName + '</a></td> ' +
                                '<td class="f12">' + v[2] + '</td> ' +
                                '<td class="f12">' + v[3] + '</td> ' +
                                '<td class="f12">' + v[4] + '</td> ' +
                                '<td class="f12">' + v[5] + '</td> ' +
                                '<td class="f12">' + v[6] + '</td> ' +
                                '<td class="f12">' + v[7] + '</td> ' +
                                '<td class="f12">' + v[8] + '</td> ' +
                                '<td class="text-red f12">' + v[9] + '</td> ' +
                                '<td class="f12">' + v[10] + '%</td> ' +
                                '<td class="f12">' + v[11] + '%</td> ' +
                                '<td class="f12">' + v[12] + '%</td> ' +
                                '</tr>';
                        } else if (data_type == 'bigSmall') {
                            html += '<tr> ' +
                                '<td><i>' + (k + 1) + '</i></td> ' +
                                '<td class="f12"><a href="/team/' + v[1] + '.html" class="text-red" target="_blank">' + teamName + '</a></td> ' +
                                '<td class="f12">' + v[2] + '</td> ' +
                                '<td class="f12">' + v[3] + '</td> ' +
                                '<td class="f12">' + v[4] + '</td> ' +
                                '<td class="f12">' + v[5] + '</td> ' +
                                '<td class="f12">' + v[6] + '%</td> ' +
                                '<td class="f12">' + v[7] + '%</td> ' +
                                '<td class="f12">' + v[8] + '%</td> ' +
                                '</tr>';
                        }

                    });
                    $('#oddsRank').html(html);

                    //全场让球 全场大小球盘路数据统计
                    var html1 = returnTechHtml(data_type, e.list.add_up);

                    //半场让球 半场大小球盘路数据统计
                    var html2 = returnTechHtml(data_type, e.list.add_up_half);

                    $('#oddsTech1 tbody').html(html1);
                    $('#oddsTech2 tbody').html(html2);
                    $('#oddsRank  tr:even').css("backgroundColor", "#f8f9fd");
                }

            }
        },
    });
}

//标准亚盘对照，无入球/失球，先入球/失球
function fnc() {
    var curSeason = $('.butText').eq(0).text();
    $.ajax({
        type: 'POST',
        url: "/tech_statistics",
        dataType: 'json',
        data: {data_type: 'firGetLose,nogetlose,contrast', season: curSeason, union_id: unions.union_id, mulSelect: 1},
        success: function (e) {
            fncData = e.list;

            if (e.list.firGetLose != null) {
                var arr_data = e.list.firGetLose.arr_data;
                var html1 = '<thead> ' +
                    '<tr bgcolor="#cfd2dd"> ' +
                    '<th width="33.3%" rowspan="2">球队</th> ' +
                    '<th width="33.3%" colspan="3">最先入球统计</th> ' +
                    '<th width="33.3%" colspan="3">最先失球统计</th> ' +
                    '</tr> </thead> <tbody>' +
                    '<tr bgcolor="#f0f2f7"> ' +
                    '<td></td> ' +
                    '<td>总</td> ' +
                    '<td>主</td> ' +
                    '<td>客</td> ' +
                    '<td>总</td> ' +
                    '<td>主</td> ' +
                    '<td>客</td> ' +
                    '</tr>';

                $.each(arr_data, function (k, v) {
                    teams =  e.teams;
                    var teamName = teams[v[0]] != undefined ? teams[v[0]]['team_name'] :'';
                    html1 += '<tr> ' +
                        '<td class="f12"> <a href="/team/' + v[0] + '.html" target="_blank"> <img class="mr10" src="" width="20" height="auto">' + teamName + ' </a> </td> ' +
                        '<td class="f12 text-red">' + v[1] + '</td> ' +
                        '<td class="f12 text-666">' + v[2] + '</td> ' +
                        '<td class="f12 text-666">' + v[3] + '</td> ' +
                        '<td class="f12 text-red">' + v[4] + '</td> ' +
                        '<td class="f12 text-666">' + v[5] + '</td> ' +
                        '<td class="f12 text-666">' + v[6] + '</td> ' +
                        '</tr>';
                });
                html1 += '</tbody>';

                $("#tableData06").html(html1);
            }else{
                $("#tableData06").parent().remove();
            }

            if (e.list.contrast != null) {
                var html = '';
                var map = ['主队1.25以下', '客队1.25以下', '主队1.26至1.4', '客队1.26至1.4', '主队1.41至1.65', '客队1.41至1.65', '主队1.66至1.75', '客队1.66至1.75', '主队1.76至1.85', '客队1.76至1.85', '主队1.86至1.95', '客队1.86至1.95', '主队1.96至2.05', '客队1.96至2.05', ' 主队2.06至2.15', '客队2.06至2.15', ' 主队2.16至2.25', '客队2.16至2.25', '主队2.26至2.35', '客队2.26至2.35', '主队2.36至2.45', '客队2.36至2.45', ' 主队2.56以上', '客队2.56以上'];
                var i = 0;
                $.each(e.list.contrast, function (k, v) {
                    html += '<tr> ' +
                        '<td bgcolor="#f0f2f7">' + map[i] + '</td> ' +
                        '<td class="text-666 f12">' + v[0] + '</td> ' +
                        '<td class="text-666 f12">' + v[1] + '</td> ' +
                        '<td class="text-666 f12">' + v[2] + '</td> ' +
                        '<td class="text-666 f12">' + v[3] + '</td> ' +
                        '<td class="text-red f12">' + v[4] + '</td> ' +
                        '<td class="text-666 f12">' + v[5] + '</td> ' +
                        '<td class="text-666 f12">' + v[6] + '</td> ' +
                        '<td class="text-green f12">' + v[7] + '</td> ' +
                        '<td bgcolor="#f0f2f7">' + (map[i + 1]) + '</td> ' +
                        '<td class="text-666 f12">' + v[8] + '</td> ' +
                        '<td class="text-666 f12">' + v[9] + '</td> ' +
                        '<td class="text-666 f12">' + v[10] + '</td> ' +
                        '<td class="text-666 f12">' + v[11] + '</td> ' +
                        '<td class="text-red f12">' + v[12] + '</td> ' +
                        '<td class="text-666 f12">' + v[13] + '</td> ' +
                        '<td class="text-666 f12">' + v[14] + '</td> ' +
                        '<td class="text-green f12">' + v[15] + '</td> ' +
                        '</tr>';
                    i = i + 2;
                })
                $("#contrast").html(html);
            }else{
                $(".tableData07").remove();
            }

        },
    });
}

//射手榜、助攻榜
function goals_rank(p, pt) {
    var curSeason = $('.butText').eq(0).text();

    $.ajax({
        type: 'POST',
        url: "/goals_rank",
        dataType: 'json',
        data: {season: curSeason, union_id: unions.union_id, p: p, pt: pt},
        success: function (e) {
            var html = '';
            if (e.status == 1) {
                if (e.type == 1) {
                    var goalHead = returnTechHtml('goal_rank_h_' + e.type);
                    var passHead = returnTechHtml('pass_rank_h_' + e.type);
                } else {
                    var goalHead = returnTechHtml('goal_rank_h_' + e.type);
                    var passHead = returnTechHtml('pass_rank_h_' + e.type);
                }

                //射手榜
                if (pt == 1 || pt == 0) {
                    if(!e.goal_rank){
                        $("#goalsRank").remove();return;
                    }

                    $('#goalsRank table').remove();
                    $('#goalsRank').append(goalHead);

                    $.each(e.goal_rank, function (k, v) {
                        html += returnTechHtml('goal_rank_' + e.type, v)
                    });

                    var pageCount = Math.ceil(e.goal_rank_c / 30);
                    var li = '';
                    for (var i = 1; i <= pageCount; i++) {
                        li += '<li><a href="javascript:void(0)" onclick="goals_rank(' + i + ',1)">' + i + '</a></li>';
                    }

                    var next = p + 1;
                    if (next > pageCount) {
                        next = 1;
                    }

                    var prev = p - 1;
                    if (prev <= 0) {
                        prev = 1;
                    }
                    var ssPage = '<tr id="goalPage"> ' +
                        '<td colspan="9"> <nav aria-label="Page navigation"> ' +
                        '<ul class="pagination pagination-sm"> ' +
                        '<li> <a  href="javascript:void(0)" onclick="goals_rank(' + prev + ',1)" aria-label="Previous"> <span aria-hidden="true">&laquo;</span> </a> </li>  '
                        + li +
                        '<li> <a  href="javascript:void(0)" onclick="goals_rank(' + next + ',1)" aria-label="Next"> <span aria-hidden="true">&raquo;</span> </a> </li> ' +
                        '</ul> </nav> </td> ' +
                        '</tr>';

                    $('#goalsRank .shootPm tbody').html(html + ssPage);
                    $('#goalsRank tr:even').css("backgroundColor", "#ffff");
                    $('#goalPage li').eq(p).addClass('active');
                }


                //助攻榜
                if (pt == 2 || pt == 0) {
                    if(!e.pass_rank){
                        $("#passRank").remove();return;
                    }
                    $('#passRank  table').remove();
                    $('#passRank').append(passHead);
                    var html2 = '';
                    $.each(e.pass_rank, function (k2, v2) {
                        html2 += returnTechHtml('pass_rank_' + e.type, v2)
                    });

                    var pageCount = Math.ceil(e.goal_rank_c / 30);
                    var li = '';
                    for (var i = 1; i <= pageCount; i++) {
                        li += '<li><a href="javascript:void(0)" onclick="goals_rank(' + i + ',2)">' + i + '</a></li>';
                    }

                    var next = p + 1;
                    if (next > pageCount) {
                        next = 1;
                    }

                    var prev = p - 1;
                    if (prev <= 0) {
                        prev = 1;
                    }

                    var ssPage = '<tr id="passPage"> ' +
                        '<td colspan="9"> <nav aria-label="Page navigation"> ' +
                        '<ul class="pagination pagination-sm"> ' +
                        '<li> <a  href="javascript:void(0)" onclick="goals_rank(' + prev + ',2)" aria-label="Previous"> <span aria-hidden="true">&laquo;</span> </a> </li>  '
                        + li +
                        '<li> <a  href="javascript:void(0)" onclick="goals_rank(' + next + ',2)" aria-label="Next"> <span aria-hidden="true">&raquo;</span> </a> </li> ' +
                        '</ul> </nav> </td> ' +
                        '</tr>';
                    $('#passRank .assTable tbody').html(html2 + ssPage);
                    $('#passRank .assTable tr:even').css("backgroundColor", "#f8f9fd");
                    $('#passRank li').eq(p).addClass('active');
                }
            }
        }
    });


}

//转会、流言
function tranRumor() {
    var curSeason = $('.butText:first').text();
    if(curSubLeague){
        var pData = {season: curSeason, round: curRound, union_id: union_id, subLeagueId: curSubLeague[0]}
    }else{
        var pData = {season: curSeason, round: curRound, union_id: union_id}
    }
    $.ajax({
        type: 'POST',
        url: "/rumorTran",
        dataType: 'json',
        data: pData,
        success: function (e) {
            var html = '';
            if (e.status == 1 && e.rumor && e.rumor != '') {
                $('#rumor').parents('.leftTra').css('display','');
                var html = '';

                $.each(e.rumor,function (k,v) {
                    html +='<tr> <td width="132"> <a href="/player/'+v.player_id+'.html" target="_blank"> <div class="pull-left faceImg"> <img class="lazy" data-original="'+v.player_logo+'" original="'+v.player_logo+'" style="display: inline;" src="'+v.player_logo+'" width="40" height="40"></div> <div class="pull-left faceInf"> <p class="mb5" title="'+v.playerName+'">'+v.playerName+'</p> <p class="f12 text-999">'+v.age+'&nbsp;&nbsp;'+v.playerMainPosition+'</p> </div> </a> </td> <td width="68"> <a href="/team/' + v.orig.team_id+'.html" target="_blank"> <p class="mb5"><img src="'+v.orig.img_url+'" width="auto" height="20"></p> <p class="f12">' + v.origClubName+'</p> </a> </td> <td width="40" class="per"> <p class="f12">' + v.probablity+'%</p> <p><img src="/Public/Home/images/info/tra-green.png" width="27" height="17" alt="转会"></p> </td> <td width="68"> <a href="/team/' + v.dest.team_id+'.html" target="_blank"> <p class="mb5"><img src="'+v.dest.img_url+'" width="auto" height="20"></p> <p class="f12">' + v.destClubName+'</p> </a> </td> </tr>';
                });
                $('#rumor').html(html);
                $('#rumor tr:even').css("backgroundColor", "#f8f9fd");
            }

            if(e.status == 1 && e.tran && e.tran != ''){
                $('#tran').parents('.leftTra').css('display','');
                var html = '';
                $.each(e.tran,function (k,v) {
                    html += ' <tr> <td width="132"> <a href="/player/' + v.player_id+'.html" target="_blank"> <div class="pull-left faceImg"> <img class="lazy" data-original="'+v.player_logo+'" original="'+v.player_logo+'" style="display: inline;" src="'+v.player_logo+'" width="40" height="40"></div> <div class="pull-left faceInf"> <p class="mb5" title="'+v.playerName+'">'+v.playerName+'</p> <p class="f12 text-999">'+v.age+'&nbsp;&nbsp;'+v.playerMainPosition+'</p> </div> </a> </td> <td width="68"> <a href="/team/' + v.orig.team_id+'.html" target="_blank"> <p class="mb5"><img src="'+v.orig.img_url+'" width="auto" height="20"></p> <p class="f12">' + v.mfClubName+'</p> </a> </td> <td width="40" class="per-orange"> <p class="f12">' + v.marketValue+'欧元</p> <p><img src="/Public/Home/images/info/tra-orange.png" width="27" height="17" alt="转会"></p> </td> <td width="68"> <a href="/team/' + v.dest.team_id+'.html" target="_blank"> <p class="mb5"><img src="'+v.dest.img_url+'" width="auto" height="20"></p> <p class="f12">' + v.miClubName+'</p> </a> </td> </tr>';
                })

                $('#tran').html(html);
                $('#tran tr:even').css("backgroundColor", "#f8f9fd");
            }
        },
    });
}
function getTeamName(teamArr, ids) {

    var tempHtml = "";
    for (var i = 1; i <= ids.length; i++) {
        try {
            tempHtml += teamArr[ids[i]]['team_name'] + ", ";
        } catch (e) {
        }
    }
    if (tempHtml == "")
        return "";

    return tempHtml;
}

function returnTechHtml(ty, data) {
    var html = '';
    if (ty == 'letGoal') {
        html = '<tr bgcolor="#f8f9fd"> ' +
            '<td width="210" bgcolor="#f0f2f7">主场赢盘</td> ' +
            '<td width="230">' + data[0] + '</td> ' +
            '<td width="250">' + data[1] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>和盘走水</td> ' +
            '<td>' + data[2] + '</td> ' +
            '<td>' + data[3] + '%</td> ' +
            '</tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">客场赢盘</td> ' +
            '<td>' + data[4] + '</td> ' +
            '<td>' + data[5] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>最佳<font class="text-red">投注</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[6]) + '</td> ' +
            '<td>' + data[6][0] + '%</td> ' +
            '</tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">避免<font class="text-blue">投注</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[7]) + '</td> ' +
            '<td>' + data[7][0] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>主场<font class="text-red">最佳</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[8]) + '</td> ' +
            '<td>' + data[8][0] + '%</td> ' +
            '</tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">主场<font class="text-blue">避免</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[9]) + '</td> ' +
            '<td>' + data[9][0] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>客场<font class="text-red">最佳</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[10]) + '</td> ' +
            '<td>' + data[10][0] + '%</td> </tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">客场<font class="text-blue">避免</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[11]) + '</td> ' +
            '<td>' + data[11][0] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>走水<font class="text-red">最多</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[12]) + '</td> ' +
            '<td>' + data[12][0] + '%</td> ' +
            '</tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">走水<font class="text-blue">最少</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[13]) + '</td> ' +
            '<td>' + data[13][0] + '%</td> ' +
            '</tr>';
    } else if (ty == 'bigSmall') {
        html = '<tr bgcolor="#f8f9fd"> ' +
            '<td width="210" bgcolor="#f0f2f7">大球</td> ' +
            '<td width="230">' + data[0] + '</td> ' +
            '<td width="250">' + data[1] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>走水</td> ' +
            '<td>' + data[2] + '</td> ' +
            '<td>' + data[3] + '%</td> ' +
            '</tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">小球</td> ' +
            '<td>' + data[4] + '</td> ' +
            '<td>' + data[5] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>大球<font class="text-red">最多</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[6]) + '</td> ' +
            '<td>' + data[6][0] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>小球<font class="text-red">最多</font>球队</td> ' +
            '<td>' + getTeamName(teams, data[7]) + '</td> ' +
            '<td>' + data[7][0] + '%</td> ' +
            '</tr> ' +
            '<tr> ' +
            '<td>主场<font class="text-red">大球</font>最多球队</td> ' +
            '<td>' + getTeamName(teams, data[8]) + '</td> ' +
            '<td>' + data[8][0] + '%</td> ' +
            '</tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">主场<font class="text-blue">小球</font>最多球队</td> ' +
            '<td>' + getTeamName(teams, data[9]) + '</td> ' +
            '<td>' + data[9][0] + '%</td> </tr> ' +
            '<tr> ' +
            '<td>客场<font class="text-red">大球</font>最多球队</td> ' +
            '<td>' + getTeamName(teams, data[10]) + '</td> ' +
            '<td>' + data[10][0] + '%</td> </tr> ' +
            '<tr bgcolor="#f8f9fd"> ' +
            '<td bgcolor="#f0f2f7">客场<font class="text-blue">小球</font>最多球队</td> ' +
            '<td>' + getTeamName(teams, data[11]) + '</td> ' +
            '<td>' + data[11][0] + '%</td> </tr>';
    } else if (ty == 'goal_rank_h_1') {
        html = '<table class="table shootPm shoTable" cellspacing="0" cellpadding="0"> <thead> ' +
            '<tr bgcolor="#cfd2dd"> ' +
            '<th width="50">排名</th> ' +
            '<th width="110" class="text-l">球员</th> ' +
            '<th width="140" class="text-l">球队</th> ' +
            '<th width="100">出场（替补）</th> ' +
            '<th width="40">射门</th> ' +
            '<th width="40">射正</th> ' +
            '<th width="100">入球转化率</th> ' +
            '<th width="50">进球</th> ' +
            '<th width="60">评分</th> ' +
            '</tr> </thead>' +
            '<tbody> </tbody> ' +
            '</table>';
    } else if (ty == 'goal_rank_h_0') {
        html = '<table class="table shootPm" cellspacing="0" cellpadding="0"> ' +
            '<thead> ' +
            '<tr bgcolor="#cfd1dd"> ' +
            '<th width="50">排名</th> ' +
            '<th width="180">球员</th> ' +
            '<th width="110">国籍</th> ' +
            '<th width="150">球队</th> ' +
            '<th width="50">主场</th> ' +
            '<th width="50">客场</th> ' +
            '<th width="100">总进球(点球)</th> ' +
            '</tr> ' +
            '</thead>' +
            ' <tbody> </tbody> ' +
            '</table>';
    } else if (ty == 'pass_rank_h_0') {
        html = '<table class="table shootPm " cellspacing="0" cellpadding="0"> <thead> ' +
            '<tr bgcolor="#cfd1dd"> ' +
            '<th width="50">排名</th> ' +
            '<th width="180">球员</th> ' +
            '<th width="110">国籍</th> ' +
            '<th width="150">球队</th> ' +
            '<th width="50">主场</th> ' +
            '<th width="50">客场</th> ' +
            '<th width="100">总助攻</th> ' +
            '</tr> </thead> <tbody> </tbody> </table>';
    } else if (ty == 'pass_rank_h_1') {
        html = ' <table class="table assTable" cellspacing="0" cellpadding="0"> <thead> ' +
            '<tr bgcolor="#cfd2dd"> ' +
            '<th width="50">排名</th> ' +
            '<th width="110" class="text-l">球员</th> ' +
            '<th width="140" class="text-l">球队</th> ' +
            '<th width="90">出场（替补）</th> ' +
            '<th width="40">传球</th> ' +
            '<th width="60">关键传球</th> ' +
            '<th width="100">传球成功率</th> ' +
            '<th width="40">助攻</th> ' +
            '<th width="60">评分</th> ' +
            '</tr> </thead> <tbody> </tbody> </table>';
    } else if (ty == 'goal_rank_0') {
        html += '<tr bgcolor="#f8f9fd" class="f12"> ' +
            '<td><i class="strong">' + data.rank + '</i></td> ' +
            '<td class="text-blue"><a href="/player/' + data.t1 + '.html" target="_blank">' + data.t2 + '</a></td> ' +
            '<td>' + data.t3 + '</td> ' +
            '<td class="text-blue"><a href="/team/' + data.t4[0] + '.html" target="_blank">' + data.t4[1] + '</a></td> ' +
            '<td class="text-blue">' + data.t6 + '</td> ' +
            '<td class="text-green">' + data.t7 + '</td> ' +
            '<td class="text-red">' + data.t5 + '(' + data.t8 + ')</td> ' +
            '</tr> ';
    } else if (ty == 'goal_rank_1') {

        html = '<tr bgcolor="#f8f9fd"> ' +
            '<td><i>' + data.rank + '</i></td> ' +
            '<td class="f12 text-l"><a href="/player/' + data.t1 + '.html" target="_blank">' + data.t2 + '</a></td> ' +
            '<td class="text-l"> <a href="/team/' + data.t4[0] + '.html" target="_blank"> <img class="mr10" src="" width="20" height="auto">' + data.t4[1] + ' </a> </td> ' +
            '<td class="f12 text-666">' + data.t13 + '(' + data.t14 + ')</td> ' +
            '<td class="f12 text-666">' + data.t17 + '</td> ' +
            '<td class="f12 text-666">' + data.t18 + '</td> ' +
            '<td> ' +
            '<div class="progress progress-striped"> <div class="progress-bar progress-bar-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: ' + data.t10 + '%;">' + data.t10 + '%</div> ' +
            '</div> ' +
            '</td> ' +
            '<td class="f12 text-red strong">' + data.t7 + '</td> ' +
            '<td><em class="score">' + data.t9 + '</em></td> ' +
            '</tr>';
    } else if (ty == 'pass_rank_0') {
        html = '<tr bgcolor="#f8f9fd" class="f12"> ' +
            '<td><i class="strong">' + data.rank + '</i></td> ' +
            '<td class="text-blue"><a href="/player/' + data.t1 + '.html" target="_blank">' + data.t2 + '</a></td> ' +
            '<td>' + data.t3 + '</td> ' +
            '<td class="text-blue">' + data.t4[1] + '</td> ' +
            '<td class="text-blue">' + data.t5 + '</td> ' +
            '<td class="text-green">' + data.t6 + '</td> ' +
            '<td class="text-red">' + data.t7 + '</td> ' +
            '</tr>';
    } else if (ty == 'pass_rank_1') {
        html = '<tr> ' +
            '<td><i>' + data.rank + '</i></td> ' +
            '<td class="f12 text-l"><a href="/player/' + data.t1 + '.html" target="_blank">' + data.t2 + ' </a></td> ' +
            '<td class="text-l"> <a href="/team/' + data.t4[0] + '.html" target="_blank"> <img class="mr10" src="" width="20" height="auto" >' + data.t4[1] + ' </a> </td> ' +
            '<td class="f12 text-666">' + data.t13 + '(' + data.t14 + ')</td> ' +
            '<td class="f12 text-666">' + data.t19 + '</td> ' +
            '<td class="f12 text-666">' + data.t20 + '</td> ' +
            '<td> ' +
            '<div class="progress progress-striped"> ' +
            '<div class="progress-bar progress-bar-blue" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: ' + data.t12 + '%;">' + data.t12 + '%</div> </div> ' +
            '</td> ' +
            '<td class="f12 text-red strong">' + data.t11 + '</td> ' +
            '<td><em class="score">' + data.t9 + '</em></td> ' +
            '</tr>';
    }

    return html;

}

//添加右侧 最佳球员 html
function addBestHtml(e, newStatsalyTzuqiu){
    if (e.data.best != undefined && e.data.best !=  '') {
        var data = e.data.best;
        var len = data.length < 6 ? data.length : 6;
        var html1 = '';
        var i= 0;
        for(best in data){
            i += 1;
            html1 += '<tr>' +
                ' <td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
                '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[best].player_id + '.html" target="_blank"> ' +
                '<div class="pull-left faceImg">' +
                '<img class="lazy" data-original="' + newStatsalyTzuqiu[best]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[best]['logo'] + '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[best]['logo'] + '" width="40" height="40"></div> ' +
                '<div class="pull-left faceInf"> ' +
                '<p class="mb5" title="' + newStatsalyTzuqiu[best]['playerName'] + '">' + newStatsalyTzuqiu[best]['playerName'] + '</p> ' +
                '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[best].age + '&nbsp;&nbsp;' + newStatsalyTzuqiu[best].playerMainPosition + '</p> ' +
                '<p class="f12 text-999"><img class="mr5" src="' + newStatsalyTzuqiu[best].team_logo + '" width="auto" height="12" >' + newStatsalyTzuqiu[best].teamName + '</p> ' +
                '</div> </a> </td> ' +
                '<td class="f12">' + newStatsalyTzuqiu[best]['cc'] + ' (' + newStatsalyTzuqiu[best]['tb'] + ')</td> ' +
                '<td class="f12"><em class="score">' + newStatsalyTzuqiu[best].averRate + '</em></td> ' +
                '</tr>';

        }

        $('#bestPlayer').html(html1);
        $('#bestPlayer tr:even').css("backgroundColor", "#f8f9fd");

    }else{
        $('#bestPlayer').parents('.rBest').remove();
    }
}

//添加右侧 状态火热 html
function addHotHtml(e, newStatsalyTzuqiu){
	if (e.data.hot != undefined && e.data.hot != '') {
        var html2 = '';
        var hotData = e.data.hot;
       
        var i = 0;
        for(key in hotData){
        	var hot = key.substr(7); // player_
        	
        	i += 1;
        	html2 += '<tr>' +
            ' <td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
            '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[hot]['player_id'] + '.html" target="_blank"> ' +
            '<div class="pull-left faceImg">' +
            '<img class="lazy" data-original="' + newStatsalyTzuqiu[hot]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[hot]['logo'] + '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[hot]['logo'] + '" width="40" height="40"></div> ' +
            '<div class="pull-left faceInf"> ' +
            '<p class="mb5" title="' + newStatsalyTzuqiu[hot]['playerName'] + '">' + newStatsalyTzuqiu[hot]['playerName'] + '</p> ' +
            '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[hot].age + '&nbsp;&nbsp;' + newStatsalyTzuqiu[hot].playerMainPosition + '</p> ' +
            '<p class="f12 text-999"><img class="mr5" src="' + newStatsalyTzuqiu[hot].team_logo + '" width="auto" height="12" >' + newStatsalyTzuqiu[hot].teamName + '</p> ' +
            '</div> </a> </td> ' +
            '<td class="f12">' + newStatsalyTzuqiu[hot].sn + '</td> ' +
            '<td class="f12"><em class="score">' + newStatsalyTzuqiu[hot].average + '</em></td> ' +
            '</tr>';
        	
        }
        
        $('#hotRank').html(html2);
        $('#hotRank  tr:even').css("backgroundColor", "#f8f9fd");
    }else{
        $('#hotRank').parents('.rBest').remove();
    }
}

//添加右侧 最快进步 html
function addMipHtml(e, newStatsalyTzuqiu){
	if (e.data.mip != undefined && e.data.mip) {
        var mipData = e.data.mip;
       
        var html1 = '';
        var i = 0;
        if(mipData != ''){
            for(key in mipData){
                var mip = key.substr(7); // player_

                i += 1;
                html1 += '<tr>' +
                    ' <td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
                    '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[mip].player_id + '.html" target="_blank"> ' +
                    '<div class="pull-left faceImg">' +
                    '<img class="lazy" data-original="' + newStatsalyTzuqiu[mip]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[mip]['logo'] + '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[mip]['logo'] + '" width="40" height="40"></div> ' +
                    '<div class="pull-left faceInf"> ' +
                    '<p class="mb5" title="' + newStatsalyTzuqiu[mip]['playerName'] + '">' + newStatsalyTzuqiu[mip]['playerName'] + '</p> ' +
                    '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[mip].age + '&nbsp;&nbsp;' + newStatsalyTzuqiu[mip].playerMainPosition + '</p> ' +
                    '<p class="f12 text-999"><img class="mr5" src="' + newStatsalyTzuqiu[mip].team_logo + '" width="auto" height="12" >' + newStatsalyTzuqiu[mip].teamName + '</p> ' +
                    '</div> </a> </td> ' +
                    '<td class="f12">' + newStatsalyTzuqiu[mip].newRate + '</td> ' +
                    '<td class="f12"><em class="score">' + newStatsalyTzuqiu[mip].diff + '</em></td> ' +
                    '</tr>';

            }
            $('#mipRank').html(html1);
            $('#mipRank  tr:even').css("backgroundColor", "#f8f9fd");
        }else{
            $('#mipRank').parents('.rBest').remove();
        }
    }
}


// 最佳、火热球员
function statsaly_tzuqiu() {
    $.ajax({
        url: '/statsaly_tzuqiu.html',
        datatype: 'JSON',
        type: 'POST',
        data: {'union_id': union_id},
        success: function (e) {
        	var newStatsalyTzuqiu = e.data.newStatsalyTzuqiu;
        	
        	addBestHtml(e, newStatsalyTzuqiu);//最佳球员
        }
    })
}

//最快进步、火热球员
function hot_and_mip() {
  $.ajax({
      url: '/hot_and_mip.html',
      datatype: 'JSON',
      type: 'POST',
      data: {'union_id': union_id},
      success: function (e) {
      	var newStatsalyTzuqiu = e.data.newStatsalyTzuqiu;
      	
      	addHotHtml(e, newStatsalyTzuqiu);//状态火热
      	addMipHtml(e, newStatsalyTzuqiu);//最快进步
      }
  })
}
//最佳阵容
function lineup() {
    var bData = bestlineup[curRound];
    if(bData == undefined){
        $(".btableData06").remove();
        return;
    }


    var html = '';
    $.each(bestlineup[curRound], function (k, v) {
        html += '<ul>';
        for (var i = 0; i < v.length; i++) {
            if(typeof teams[v[i]['team_id']] != 'undefined'){
                var img_url = teams[v[i]['team_id']]['img_url'];
                var team_name = teams[v[i]['team_id']]['team_name'];
            }else{
                var img_url = '';
                var team_name = '';
            }

            html += '<li> ' +
                '<a target="_blank" href="/player/' + v[i]['player_id'] + '.html" class="playerBox" title=' + v[i]["player_name"] + '> ' +
                '<div class="plaImg plaImgDef"> ' +
                '<div class="face"><img src="' + img_url + '" width="58" height="58"></div> ' +
                '<p class="f12 text-fff text-center">' + v[i]['rating'] + '</p> ' +
                '</div> ' +
                '<p class="text-center text-fff text-hidden" >' + v[i]['player_name'] + '</p> ' +
                '<p class="text-center text-fff text-hidden">' + team_name + '</p> ' +
                '</a> </li>';
        }
        html += '</ul>';
    });

    $('.bestFor').html(html);
}

//最佳球员

//function league_best_player_cancel() {
//    var curSeason = $('.butText').text();
//    $.ajax({
//        url: '/league_best_player.html',
//        datatype: 'JSON',
//        type: 'POST',
//        data: {season: curSeason, union_id: unions.union_id},
//        success: function (e) {
//            var html = '';
//            if (e.status == 1) {
//                $.each(e.list, function (k, v) {
//                    html += '<tr> ' +
//                        '<td><span class="rank rank0' + (k + 1) + '">' + (k + 1) + '</span></td> ' +
//                        '<td class="text-l"> <a href="/player/'+v['player_id']+'.html" title="卡拉斯科" target="_blank"> ' +
//                        '<div class="pull-left faceImg">' +
//                        '<img class="lazy" data-original="' + v['player_logo'] + '" original="' + v['player_logo'] + '" style="display: inline;" src="' + v['player_logo'] + '" width="40" height="40"></div>' +
//                        '<div class="pull-left faceInf"> ' +
//                        '<p class="mb5" title="' + v['player_name'] + '">' + v['player_name'] + '</p> ' +
//                        '<p class="f12 text-999 mb5">' + v['age'] + '&nbsp;&nbsp;' + v['position'] + '</p> ' +
//                        '<p class="f12 text-999">' +
//                        '<img class="mr5" src="' + teams[v["team_id"]]['img_url'] + '" width="auto" height="12">' + teams[v["team_id"]]['team_name'] +
//                        '</p> ' +
//                        '</div></a> ' +
//                        '</td> ' +
//                        '<td class="f12">' + v['cc'] + ' (' + v['tp'] + ')</td> ' +
//                        '<td class="f12"><em class="score">' + v['avg_score'] + '</em></td> ' +
//                        '</tr>';
//                });
//
//                $("#bestPlayer").html(html);
//            }
//        }
//    })
//}