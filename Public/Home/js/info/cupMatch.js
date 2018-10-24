/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 *
 * @since  2018-01-10
 *
 **/
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


    //世界杯有奖竞猜滚动
    $(".introBox").mCustomScrollbar({
        theme: "light-3",
        autoDraggerLength: true
    });

    var unions = JSON.parse(union);

    $(document).on('click', '.tableData01 .dropdown-menu  li', function () {
        $('#scoreType').text($(this).text());
        showScoreRank($(this).attr('data-type'));
    });

    //盘路切换
    $(document).on('click', '#tablePanData  .tabNav li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        exp_bigsmall_st();
    });

    $(document).on('click', '#tablePanData .liTab  a', function () {
        $(this).addClass('on').siblings().removeClass('on');
        exp_bigsmall_st();
    });


    $(document).on('click', '#tableBallData .bigStatTit  li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        ball_st();
    });

    $(document).on('click', '#tableBallData .ul-tab  li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        ball_st();
    });

    //热门专区
    $(document).on('click', '.rZone .zoneNav  li', function () {
        $(this).addClass('active').siblings().removeClass('active');
        var inx = $(this).index();
        $(".zoneCo").children("ul:eq(" + inx + ")").css('display', 'block').siblings().css('display', 'none');

    });
    //赛程统计
    $(document).on('click', '#stPage  li', function () {
        $(this).addClass('active').siblings().removeClass('active');
        var start = ($(this).attr('page') - 1) * 6;
        var end = start + 6 > statistics_count ? statistics_count : start + 6;
        for (var i = 0; i <= statistics_count; i++) {
            if (i >= start && i < end) {
                $('#statistics .schTj').eq(i).css('display', '');
            } else {
                $('#statistics .schTj').eq(i).css('display', 'none');
            }
        }
    });

    exp_bigsmall_st();
    ball_st();
    goals_rank(1, 0);
    showScoreRank(cur_cup_rank);

    //积分榜
    function showScoreRank(type) {
        try{
            //判断是分组还是淘汰赛
            var types = JSON.parse(score_rank_type);
            var isGroup = false;
            if(types){
                for (var i = 0; i < types.length; i++) {
                    if (type == types[i][0]) {
                        if (types[i][2] == 1) {
                            isGroup = true;
                        }
                    }
                }
            }

            if (isGroup) {
                var data = JSON.parse(score_rank)[type];

                var html = '';
                if(data){
                    $.each(data, function (k, v) {
                        html += ' <table class="table intJf" cellspacing="0" cellpadding="0" id="jfTab"> ' +
                            '<thead> ' +
                            '<tr bgcolor="#cfd1dd"> <th width="100%" colspan="10" class="strong text-uppercase">' + k + '组积分</th> </tr> ' +
                            '</thead> ' +
                            '<tbody> <tr bgcolor="#f7f0f2"> ' +
                            '<td width="50">排名</td> ' +
                            '<td width="270">球队</td> ' +
                            '<td width="40">总</td> ' +
                            '<td width="40">胜</td> ' +
                            '<td width="40">平</td> ' +
                            '<td width="40">负</td> ' +
                            '<td width="40">得</td> ' +
                            '<td width="40">失</td> ' +
                            '<td width="40">净</td> ' +
                            '<td width="50">积分</td> </tr>';

                        var html2 = '';
                        $.each(v, function (k2, v2) {

                            var teamName = teams[v2[1]] != undefined ?teams[v2[1]]['team_name']:'';
                            html2 += '<tr bgcolor="#f8f9fd"> ' +
                                '<td width=""><i class="strong">' + v2[0] + '</i></td> ' +
                                '<td width=""><a href="/team/' + v2[1] + '.html" class="text-red" target="_blank">' + teamName + '</a></td> ' +
                                '<td width="">' + v2[2] + '</td> ' +
                                '<td width="">' + v2[3] + '</td> ' +
                                '<td width="">' + v2[4] + '</td> ' +
                                '<td width="">' + v2[5] + '</td> ' +
                                '<td width="">' + v2[6] + '</td> ' +
                                '<td width="">' + v2[7] + '</td> ' +
                                '<td width="">' + v2[8] + '</td> ' +
                                '<td width="" class="text-red">' + v2[9] + '</td> ' +
                                '</tr>';
                        });
                        html += html2;
                        html += '</tbody> </table>';
                        $('.scoreRank .intJf').remove();
                        $('.scoreRank').append(html);
                    });
                }else{
                    $('.scoreRank .intJf').remove();
                }

            } else {
                var data = JSON.parse(knockount);
                if (data && data[type] != undefined) {
                    data = data[type];
                    var html = '<table class="table intJf" cellspacing="0" cellpadding="0"> ' +
                        '<thead> <tr bgcolor = "#cfd1dd"> ' +
                        '<th width = "74" rowspan = "2">赛事</th> ' +
                        '<th width = "60" rowspan = "2">时间</th> ' +
                        '<th width = "62" rowspan = "2">主队</th> ' +
                        '<th width = "50" rowspan = "2">比分</th> ' +
                        '<th width = "62" rowspan = "2">客队</th> ' +
                        '<th width = "130" colspan="2" bgcolor="#acb0c5">让球</th> ' +
                        '<th width = "130" colspan="2" bgcolor="#acb0c5">大小</th> ' +
                        '<th width = "60" rowspan = "2">资料</th> ' +
                        '<th width = "60" rowspan = "2">半场</th> ' +
                        '</tr> <tr bgcolor = "#dddfe9"> ' +
                        '<th width="65">全场</th> ' +
                        '<th width="65">半场</th> ' +
                        '<th width="65">全场</th> ' +
                        '<th width="65">半场</th> </tr> ' +
                        '</thead>';

                    $.each(data, function (k, v) {
                        if(v){
                            var asia_odds_sb = v.asia_odds_sb == undefined ? '' : v.asia_odds_sb[1];
                            var asia_odds_sb_half = v.asia_odds_sb_half != undefined ? v.asia_odds_sb_half[1] : '';
                            var bigsmall_odds_sb = v.bigsmall_odds_sb != undefined ? v.bigsmall_odds_sb[1] : '';
                            var bigsmall_odds_sb_half = v.bigsmall_odds_sb_half != undefined ? v.bigsmall_odds_sb_half[1] : '';
                            var cai = v.c_url ?'<a href="' + v.c_url + '" class="text-blue"  target="_blank">[猜]</a>':'';
                            html += '<tr bgcolor="#f8f9fd"> ' +
                                '<td bgcolor="#690105" class="text-fff">' + unions.union_name + '</td> ' +
                                '<td><p>' + v.gdate + '</p><p class="text-999">' + v.gtime + '</p></td> ' +
                                '<td class="text-blue"><a href="/team/' + v.home_team_id + '.html" target="_blank">' + v.home_team_name[0] + '</a></td> ' +
                                '<td class="text-red">' + v.score + '</td> ' +
                                '<td class="text-blue"><a href="/team/' + v.away_team_id + '.html" target="_blank">' + v.away_team_name[0] + '</a></td> ' +
                                '<td>' + asia_odds_sb + '</td> ' +
                                '<td>' + asia_odds_sb_half + '</td> ' +
                                '<td>' + bigsmall_odds_sb + '</td> ' +
                                '<td>' + bigsmall_odds_sb_half + '</td> ' +
                                '<td>' +
                                '<a href="' + v.y_url + '" class="text-blue"  target="_blank">[亚]</a>' +
                                '<a href="' + v.o_url + '" class="text-blue"  target="_blank">[欧]</a>' +
                                '<a href="' + v.x_url + '" class="text-blue"  target="_blank">[析]</a>' + cai
                                +
                                '</td> ' +
                                '<td>' + v.half_score + '</td> ' +
                                '</tr>';
                        }

                    });
                    html += '</table>';

                    $('.scoreRank .intJf').remove();
                    $('.scoreRank').append(html);
                } else {
                    $('.scoreRank .intJf tbody').remove();
                }

            }
        }catch(e){
            console.log(e)
        }
    }

    //盘路
    function exp_bigsmall_st() {
        var curSeason = $('#season').text();
        var data_type = $('#tablePanData .on').attr('data-type');
        var sub_type = $('#tablePanData .liTab .on').attr('sub-data-type');

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
        var html = '';
        $.ajax({
            type: 'POST',
            url: "/tech_statistics",
            dataType: 'json',
            data: {data_type: data_type, season: curSeason, union_id: unions.union_id},
            success: function (e) {
                try {
                    if (e.status == 1) {
                        var data = sub_type != undefined ? e.list[sub_type_map[data_type][sub_type]] : e.list[sub_type_map[data_type]];
                        $.each(data, function (k, v) {
                            var teamName = teams[v[1]] != undefined ?teams[v[1]]['team_name']:'';
                            if (data_type == 'letGoal') {
                                html += '<tr> ' +
                                    '<td><i>' + (k + 1) + '</i></td> ' +
                                    '<td class="f12"><a href="/team/' + v[1] + '.html" target="_blank">' + teamName + '</a></td> ' +
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
                                    '<td class="f12"><a href="/team/' + v[1] + '.html" target="_blank">' + teamName + '</a></td> ' +
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

                        if (html) {
                            $('#oddsRank').html(html);
                        } else {
                            $('#tablePanData').remove();
                        }

                        //分页显示
                        var len = $('#oddsRank').children('tr').length;
                        var page = '';
                        var pn = Math.ceil(len / 10);
                        for (var i = 1; i <= pn; i++) {
                            if (i == 1) {
                                page += '<li type="page"><a href="javascript:void(0);" class="on">' + i + '</a></li>';
                            } else {
                                page += '<li type="page"><a href="javascript:void(0);">' + i + '</a></li>';
                            }
                        }

                        for (var j = 0; j <= 9; j++) {
                            // $('#oddsRank').children(':eq('+j+')').css('display', '');
                        }

                        //全场让球 全场大小球盘路数据统计
                        var html1 = returnTechHtml(data_type, e.list.add_up);


                        //半场让球 半场大小球盘路数据统计
                        var html2 = returnTechHtml(data_type, e.list.add_up_half);

                        $('#oddsTech1 tbody').html(html1);
                        $('#oddsTech2 tbody').html(html2);

                        $('#oddsRank tbody tr:even').css("background-color", "#FFFFFF");
                    }
                } catch (e) {
                    console.log(e)
                }

            }
        });
    }

    //入球总数/单双、半全场胜负、上下半场入球
    function ball_st() {
        var curSeason = $('#season').text();
        var data_type = $('#tableBallData .tabNav .on').attr('data-type');

        var sub_type_map = ['allData', 'homeData', 'guestData'];

        $('#tableBallData .ul-tab').css('display', 'none');

        var html = returnTechHtml(data_type + 'Th');
        if (data_type == 'allHalf') {
            $('#tableBallData .ul-tab').css('display', '');
        }

        $('#tableBallData .goalNum').remove();
        $('#tableBallData').append(html);
        $('#tableBallData tbody tr:even').css("bgcolor", "#ffffff");

        var sub_type = $('#tableBallData .ul-tab .on').attr('sub-data-type');
        $.ajax({
            type: 'POST',
            url: "/tech_statistics",
            dataType: 'json',
            data: {data_type: data_type, season: curSeason, union_id: unions.union_id},
            success: function (e) {
                var _html = '';
                if (e.status == 1) {
                    try {
                        var data = data_type == 'allHalf' ? e.list[sub_type_map[sub_type]] : e.list;
                        $.each(data, function (k, v) {
                            _html += returnTechHtml(data_type, v);
                        });
                    } catch (e) {

                    }

                    $('#tableBallData .goalNum tbody').html(_html);
                    $('#tableBallData tbody tr:even').css("background-color", "#FFFFFF");
                }
            }
        });
    }

    //射手榜、助攻榜
    function goals_rank(p, pt) {
        var curSeason = $('#season').text();
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

                    $('#goalsRank').append(goalHead);
                    $('#passRank').append(passHead);

                    //射手榜
                    if (e.goal_rank && (pt == 1 || pt == 0)) {
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

                    if (!e.goal_rank) {
                        $('#goalsRank').remove();
                    }


                    //助攻榜 洲际赛目前不显示助攻榜
//                    if (e.pass_rank && (pt == 2 || pt == 0)) {
//                        var html2 = '';
//                        $.each(e.pass_rank, function (k2, v2) {
//                            html2 += returnTechHtml('pass_rank_' + e.type, v2)
//                        });
//
//                        var pageCount = Math.ceil(e.goal_rank_c / 30);
//                        var li = '';
//                        for (var i = 1; i <= pageCount; i++) {
//                            li += '<li><a href="javascript:void(0)" onclick="goals_rank(' + i + ',2)">' + i + '</a></li>';
//                        }
//
//                        var next = p + 1;
//                        if (next > pageCount) {
//                            next = 1;
//                        }
//
//                        var prev = p - 1;
//                        if (prev <= 0) {
//                            prev = 1;
//                        }
//
//                        var ssPage = '<tr id="passPage"> ' +
//                            '<td colspan="9"> <nav aria-label="Page navigation"> ' +
//                            '<ul class="pagination pagination-sm"> ' +
//                            '<li> <a  href="javascript:void(0)" onclick="goals_rank(' + prev + ',2)" aria-label="Previous"> <span aria-hidden="true">&laquo;</span> </a> </li>  '
//                            + li +
//                            '<li> <a  href="javascript:void(0)" onclick="goals_rank(' + next + ',2)" aria-label="Next"> <span aria-hidden="true">&raquo;</span> </a> </li> ' +
//                            '</ul> </nav> </td> ' +
//                            '</tr>';
//                        $('#passRank .assTable tbody').html(html2 + ssPage);
//                        $('#passRank .assTable tr:even').css("backgroundColor", "#f8f9fd");
//                        $('#passRank li').eq(p).addClass('active');
//                    }
                }
            }
        });
    }

    //获取球队名
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

    //盘路html处理
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
                '<td>' + data[10][0] + '%</td> ' +
                '</tr> ' +
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
                '<td>' + data[9][0] + '%</td> ' +
                '</tr> ' +
                '<tr> ' +
                '<td>客场<font class="text-red">大球</font>最多球队</td> ' +
                '<td>' + getTeamName(teams, data[10]) + '</td> ' +
                '<td>' + data[10][0] + '%</td> ' +
                '</tr> ' +
                '<tr bgcolor="#f8f9fd"> ' +
                '<td bgcolor="#f0f2f7">客场<font class="text-blue">小球</font>最多球队</td> ' +
                '<td>' + getTeamName(teams, data[11]) + '</td> ' +
                '<td>' + data[11][0] + '%</td> ' +
                '</tr>';
        } else if (ty == 'SinDou') {
            html = '<tr class="f12" bgcolor="#f8f9fd"> ' +
                '<td width="200"><a href="/team/' + data[0] + '.html" target="_blank">' + teams[data[0]]['team_name'] + '</a></td> ' +
                '<td width="50">' + data[1] + '</td> ' +
                '<td width="50">' + data[2] + '</td> ' +
                '<td width="50">' + data[3] + '</td> ' +
                '<td width="50">' + data[4] + '</td> ' +
                '<td width="50">' + data[5] + '</td> ' +
                '<td width="50">' + data[6] + '</td> ' +
                '<td width="90">' + data[7] + '</td> ' +
                '<td width="50" class="text-red">' + data[8] + '</td> ' +
                '<td width="50" class="text-blue">' + data[9] + '</td> ' +
                '</tr>';
        } else if (ty == 'allHalf') {
            html = '<tr class="f12" bgcolor="#f8f9fd"> ' +
                '<td><a href="/team/' + data[0] + '.html" target="_blank">' + teams[data[0]]['team_name'] + '</a></td> ' +
                '<td></td> ' +
                '<td>' + data[1] + '</td> ' +
                '<td class="text-red">' + data[2] + '</td> ' +
                '<td>' + data[3] + '</td> ' +
                '<td>' + data[4] + '</td> ' +
                '<td>' + data[5] + '</td> ' +
                '<td class="text-blue">' + data[6] + '</td> ' +
                '<td>' + data[7] + '</td> ' +
                '<td>' + data[8] + '</td> ' +
                '<td>' + data[9] + '</td> ' +
                '</tr>';
        } else if (ty == 'moreBall') {
            html = ' <tr class="f12" bgcolor="#f8f9fd"> ' +
                '<td><a href="/team/' + data[0] + '.html" target="_blank">' + teams[data[0]]['team_name'] + '</a></td> ' +
                '<td>' + data[1] + '</td> ' +
                '<td class="text-red">' + data[2] + '</td>' +
                '<td>' + data[3] + '</td> ' +
                '<td>' + data[4] + '</td> ' +
                '<td class="text-blue">' + data[5] + '</td> ' +
                '<td>' + data[6] + '</td> ' +
                '<td>' + data[7] + '</td> ' +
                '<td class="text-green">' + data[8] + '</td> ' +
                '<td>' + data[9] + '</td> ' +
                '</tr>';
        } else if (ty == 'SinDouTh') {
            html = '<table class="table goalNum"> ' +
                '<thead>' +
                ' <tr bgcolor="#cfd2dd"> ' +
                '<th width="200" rowspan="2" class="b-r b-l">球队名称</th> ' +
                '<th width="390" colspan="7" class="b-r b-l">入球总数</th> ' +
                '<th width="100" colspan="2" class="b-l">单双</th> </tr> ' +
                '<tr bgcolor="#dce0ec"> ' +
                '<th width="50">0球</th> ' +
                '<th width="50">1球</th> ' +
                '<th width="50">2球</th> ' +
                '<th width="50">3球</th> ' +
                '<th width="50">4球</th> ' +
                '<th width="50">5球</th> ' +
                '<th width="90" class="b-r">6球以上</th> ' +
                '<th width="50" class="b-l">单</th> ' +
                '<th width="50">双</th> ' +
                '</tr> ' +
                '<tbody> </tbody>' +
                '</thead>  </table>';
        } else if (ty == 'allHalfTh') {
            html = '<table class="table goalNum"> <thead> ' +
                '<tr bgcolor="#cfd2dd"> ' +
                '<th width="200" rowspan="2" class="b-r">球队名称</th> ' +
                '<th width="80">半场</th> ' +
                '<th width="46">胜</th> ' +
                '<th width="46">胜</th> ' +
                '<th width="46">胜</th> ' +
                '<th width="46">和</th> ' +
                '<th width="46">和</th> ' +
                '<th width="46">和</th> ' +
                '<th width="46">负</th> ' +
                '<th width="46">负</th>  ' +
                '<th width="46">负</th>  ' +
                '</tr> ' +
                '<tr bgcolor="#cfd2dd"> ' +
                '<th width="80">全场</th> ' +
                '<th width="46">胜</th> ' +
                '<th width="46">和</th> ' +
                '<th width="46">负</th> ' +
                '<th width="46">胜</th> ' +
                '<th width="46">和</th> ' +
                '<th width="46">负</th> ' +
                '<th width="46">胜</th> ' +
                '<th width="46">和</th> ' +
                '<th width="46">负</th> ' +
                '</tr>' +
                '<tbody> </tbody> ' +
                '</thead> </table>';
        } else if (ty == 'moreBallTh') {
            html = '<table class="table goalNum"> ' +
                '<thead> ' +
                '<tr bgcolor="#cfd2dd"> ' +
                '<th width="200" rowspan="2" class="b-r">球队名称</th> ' +
                '<th width="163" colspan="3" class="b-r b-l">总</th> ' +
                '<th width="163" colspan="3" class="b-r b-l">主</th> ' +
                '<th width="163" colspan="3" class="b-l">客</th> ' +
                '</tr> ' +
                '<tr bgcolor="#cfd2dd"> ' +
                '<th width="45" class="no-b-t b-l"><p>上半场入球</p> <p>数较多</p></th> ' +
                '<th width="60" class="no-b-t"><p>上下半场入球</p> <p>数相同</p></th> ' +
                '<th width="45" class="no-b-t b-r"><p>下半场入球</p> <p>数较多</p></th> ' +
                '<th width="45" class="no-b-t"><p>上半场入球</p> <p>数较多</p></th> ' +
                '<th width="60" class="no-b-t"><p>上下半场入球</p> <p>数相同</p></th> ' +
                '<th width="45" class="no-b-t b-r"><p>下半场入球</p> <p>数较多</p></th> ' +
                '<th width="45" class="no-b-t"><p>上半场入球</p> <p>数较多</p></th> ' +
                '<th width="60" class="no-b-t"><p>上下半场入球</p> <p>数相同</p></th> ' +
                '<th width="45" class="no-b-t"><p>下半场入球</p> <p>数较多</p></th> ' +
                '</tr> ' +
                '</thead> ' +
                '<tbody> </tbody> </table>';
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
                '<td class="f12 text-l"><a href="/player/' + data.t1 + '.html"  target="_blank">' + data.t2 + '</a></td> ' +
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

    $('.tableData01 tbody tr:even').css("backgroundColor", "#ffffff");
    $('.scoreRank tbody tr:even').css("backgroundColor", "#ffffff");


});

