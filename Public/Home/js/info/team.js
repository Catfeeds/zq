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

    //右边球队介绍
    $(".introBox").mCustomScrollbar({
        theme: "light-3",
        autoDraggerLength: true
    });
    var pn = 10;//每页条数

    //球队数据
    $(document).on('click', '.teamDataNav li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        teamData();
    });

    $(document).on('click', '#teamData .dropdown-menu  li', function () {
        $("#teamData .rouNum").text($(this).text());
        $("#teamData .rouNum").attr('data-type', $(this).attr('data-type'));
        teamData();
    });


    //球队数据
    teamData();
    function teamData() {
        var count_sum_data = JSON.parse(count_sum);
        var team_count_data = JSON.parse(team_count);
        var data_type = $("#teamData .rouNum").attr('data-type');
        var sub_type = $("#teamData .teamDataNav .on").attr('sub-type');
        var s_count_sum;

        $("#teamCount thead tr").eq(sub_type).css('display', '').siblings().css('display', 'none');
        $("#teamDataGlist thead tr").eq(sub_type).css('display', '').siblings().css('display', 'none');

        for (var i = 0; i < count_sum_data.length; i++) {
            if (count_sum_data[i][0] == data_type) {
                s_count_sum = count_sum_data[i];
            }
        }

        if (sub_type == 0) {
            var hs = s_count_sum[13] * 100;
            var count_sum_html = '<tr bgcolor="#ffffff"> ' +
                '<td width="">' + s_count_sum[2] + '</td> ' +
                '<td width="">' + s_count_sum[3] + '</td> ' +
                '<td width="">' + s_count_sum[4] + '</td> ' +
                '<td width="">' + s_count_sum['sl']  + '%</td> ' +
                '<td width="">' + s_count_sum[5] + '</td> ' +
                '<td width="">' + s_count_sum[6] + '</td> ' +
                '<td width="">' + s_count_sum[7] + '</td> ' +
                '<td width="">' + s_count_sum[8] + '%</td> ' +
                '<td width="">' + s_count_sum[9] + '<span class="text-999">(' + s_count_sum[10] + ')</span></td> ' +
                '<td width="">' + s_count_sum[11] + '<span class="text-999">(' + s_count_sum[12] + ')</span></td> ' +
                '<td width=""> <div class="progress progress-striped"> <div class="progress-bar progress-bar-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: ' + hs + '%;">' + hs + '%</div> </div> </td> ' +
                '<td width=""> <em class="score">' + s_count_sum[24] + '</em> </td> ' +
                '</tr>';
        } else {
            var count_sum_html = '<tr bgcolor="#ffffff"> ' +
                '<td width="">' + s_count_sum[2] + '</td> ' +
                '<td width="">' + s_count_sum[3] + '</td> ' +
                '<td width="">' + s_count_sum[4] + '</td> ' +
                '<td width="">' + s_count_sum['sl'] + '%</td> ' +
                '<td width="">' + s_count_sum[15] + '</td> ' +
                '<td width="">' + s_count_sum[16] + '</td> ' +
                '<td width="">' + s_count_sum[17] + '<span class="text-999">(' + s_count_sum[18] + ')</span></td> ' +
                '<td width="">' + s_count_sum[19] + '</td> ' +
                '<td width="">' + s_count_sum[20] + '</td> ' +
                '<td width="">' + s_count_sum[21] + '</td> ' +
                '<td width="">' + s_count_sum[22] + '</td> ' +
                '<td width="">' + s_count_sum[23] + '</td> ' +
                '<td width=""> <em class="score">' + s_count_sum[24] + '</em> </td> ' +
                '</tr>';
        }

        $("#teamCount tbody").html(count_sum_html);

        //赛程
        var scHtml = '';
        var listCount = 0;
        $.each(team_count_data, function (k, v) {
            if (v[4] == data_type || data_type == '0') {
                if (sub_type == 0) {
                    var st = listCount < 10 ? '' : 'style="display:none"';

                    scHtml += '<tr bgcolor="#f8f9fd" ' + st + '> ' +
                        '<td bgcolor="' + v[6] + '" class="text-fff">' + v[5].split('^')[0] + '</td> ' +
                        '<td><p>' + v[3].split(' ')[0] + '</p><p class="text-999">' + v[3].split(' ')[1] + '</p></td> ' +
                        '<td><a href="/team/' + v[1] + '.html" target="_blank">' + v[7].split('^')[0] + '</a></td> ' +
                        '<td class="text-red">' + v[9] + ':' + v[10] + '</td> ' +
                        '<td><a href="/team/' + v[2] + '.html" target="_blank">' + v[8].split('^')[0] + '</a></td> ' +
                        '<td>' + v[11] + '</td> ' +
                        '<td>' + v[12] + '</td> ' +
                        '<td>' + v[13] + '</td> ' +
                        '<td>' + v[14] + '%</td> ' +
                        '<td>' + v[15] + '<br><span class="text-999">(' + v[16] + ')</span></td> ' +
                        '<td>' + v[17] + '<br><span class="text-999">(' + v[18] + ')</span></td> ' +
                        '<td> <div class="progress progress-striped"> <div class="progress-bar progress-bar-green" role="progressbar" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100" style="width: ' + v[19] + '%;">' + v[19] + '%</div> </div> </td> ' +
                        '<td width=""> <em class="score">' + v[31] + '</em> </td> ' +
                        '</tr>';
                } else {
                    var st = listCount < 10 ? '' : 'style="display:none"';
                    scHtml += '<tr bgcolor="#f8f9fd" ' + st + '> ' +
                        '<td bgcolor="' + v[6] + '" class="text-fff">' + v[5].split('^')[0] + '</td> ' +
                        '<td><p>' + v[3].split(' ')[0] + '</p><p class="text-999">' + v[3].split(' ')[1] + '</p></td> ' +
                        '<td><a href="/team/' + v[1] + '.html" target="_blank">' + v[7].split('^')[0] + '</a></td> ' +
                        '<td class="text-red">' + v[9] + ':' + v[10] + '</td> ' +
                        '<td><a href="/team/' + v[2] + '.html" target="_blank">' + v[8].split('^')[0] + '</a></td> ' +
                        '<td>' + v[21] + '</td> ' +
                        '<td>' + v[22] + '</td> ' +
                        '<td>' + v[24] + '<br><span class="text-999">(' + v[25] + ')</span></td> ' +
                        '<td>' + v[26] + '</td> ' +
                        '<td>' + v[27] + '</td> ' +
                        '<td>' + v[29] + '</td> ' +
                        '<td>' + v[30] + '</td> ' +
                        '<td width=""> <em class="score">' + v[31] + '</em> </td> ' +
                        '</tr>';
                }
                listCount += 1;
            }
        });

        $("#teamDataGlist tbody").html(scHtml);

        if (listCount / pn > 1) {
            var page = '';
            for (var i = 1; i <= Math.ceil(listCount / pn); i++) {
                page += '<li page-data="' + i + '"><a href="javascript:;" >' + i + '</a></li>';
            }

            var pageHtml = '<tr id="teamDataPage"> <td colspan="15"> <nav aria-label="Page navigation"> <ul class="pagination pagination-sm"> ' +
                '<li page-data="1" id="pPrev"> <a href="javascript:;" aria-label="Previous"> <span aria-hidden="true">«</span> </a> </li> ' + page +
                '<li page-data="2" id="pNext"> <a href="javascript:;" aria-label="Next"> <span aria-hidden="true">»</span> </a> </li> ' +
                '</ul> </nav> </td> </tr>';

            $("#teamDataGlist tbody").append(pageHtml);
        }
        $('#teamDataPage ul li').eq(1).addClass('active').siblings().removeClass('active');
    }

    //赛程
    showSchedule();
    function showSchedule() {
        var scHtml = '';
        var pn = 10;
        var listCount = schedule.length;

        if (listCount / pn > 1) {
            var page = '';
            for (var i = 1; i <= Math.ceil(listCount / pn); i++) {
                page += '<li page-data="' + i + '"><a href="javascript:;" >' + i + '</a></li>';
            }

            var pageHtml = '<tr id="schedulePage"> <td colspan="15"> <nav aria-label="Page navigation"> <ul class="pagination pagination-sm"> ' +
                '<li page-data="1" id="sPrev"> <a href="javascript:;" aria-label="Previous"> <span aria-hidden="true">«</span> </a> </li> ' + page +
                '<li page-data="2" id="sNext"> <a href="javascript:;" aria-label="Next"> <span aria-hidden="true">»</span> </a> </li> ' +
                '</ul> </nav> </td> </tr>';

            $("#schedule tbody").append(pageHtml);
        }
        $('#schedulePage ul li').eq(1).addClass('active').siblings().removeClass('active');
    }

    //点击球队数据赛程分页
    $(document).on('click', '#teamDataPage ul li', function () {
        var p = parseInt($(this).attr('page-data'));

        $('#teamDataPage ul li').eq(p).addClass('active').siblings().removeClass('active');

        var start = (p - 1) * pn;
        var end = start + pn;
        var listCount = $("#teamDataGlist tbody tr").length;

        if (p <= 1) {
            $("#pPrev").attr('page-data', 1);

        } else {
            $("#pPrev").attr('page-data', p - 1);
        }

        if (p >= listCount) {
            $("#pNext").attr('page-data', p );
        } else {
            $("#pNext").attr('page-data', p + 1);
        }

        for (var i = 0; i < listCount - 1; i++) {
            if (i >= start && i < end) {
                $("#teamDataGlist tbody tr").eq(i).css('display', '');
            } else {
                $("#teamDataGlist tbody tr").eq(i).css('display', 'none');
            }
        }
    });

    //点击赛程分页
    $(document).on('click', '#schedulePage ul li', function () {
        var p = parseInt($(this).attr('page-data'));
        $('#schedulePage ul li').eq(p).addClass('active').siblings().removeClass('active');
        var start = (p - 1) * pn;
        var end = start + pn;
        var listCount = schedule.length;

        if (p <= 1) {
            $("#sPrev").attr('page-data', 1);
        } else {
            $("#sPrev").attr('page-data', p - 1);
        }


        if (p < listCount/10) {
            $("#sNext").attr('page-data', p + 1);
        } else {
            $("#sNext").attr('page-data', p);
        }

        for (var i = 0; i < listCount - 1; i++) {
            if (i >= start && i < end) {
                $("#schedule tbody tr").eq(i).css('display', '');
            } else {
                $("#schedule tbody tr").eq(i).css('display', 'none');
            }
        }
    });

    //赛事统计
    $(document).on('click', '.matchTjNav li', function () {
        $(this).addClass('on').siblings().removeClass('on');
        var data_type = $('.matchTjNav .on').attr('data-type');
        var index = $(this).index();

        var sindex = index == 0 ? 1 : 0;
        $("#tournamentSt .dropdown-menu").eq(index).css('display', '');
        $("#tournamentSt .dropdown-menu").eq(sindex).css('display', 'none');

        if (data_type == 2) {
            var t = $("#tournamentSt .dropdown-menu").eq(1).find('li').eq(0).text();
            $("#tournamentSt .rouNum").text(t);
            var uid = $(this).attr('union-id');
        }

        tournament_st(uid);
    });

    //赛事统计-选择杯赛

    $(document).on('click', '#tournamentSt .dropdown-menu li', function () {
        var data_type = $('.matchTjNav .on').attr('data-type');
        if (data_type == 2) {
            $("#tournamentSt .dropdown-menu").eq(1).css('display', '');
            $("#tournamentSt .dropdown-menu").eq(0).css('display', 'none');

            $("#tournamentSt .rouNum").text($(this).text());

            var uid = $(this).attr('union-id');
            tournament_st(uid);
        }
    });

    tournament_st();
    function tournament_st(uid) {
        $('.matchTj').remove();

        var data_type = $('.matchTjNav .on').attr('data-type');
        var trHead1 = '<tr bgcolor="#cfd1de"> ' +
            '<td width="50"></td> ' +
            '<td width="40">赛</td> ' +
            '<td width="40">胜</td> ' +
            '<td width="40">平</td> ' +
            '<td width="40">负</td> ' +
            '<td width="40">得</td> ' +
            '<td width="40">失</td> ' +
            '<td width="40">净</td> ' +
            '<td width="60">胜%</td> ' +
            '<td width="60">平%</td> ' +
            '<td width="60">负%</td> ' +
            '<td width="60">均得</td> ' +
            '<td width="60">均失</td> ' +
            '<td width="60">积分</td> ' +
            '</tr>';
        var trHead2 = '<tr bgcolor="#cfd1de"> ' +
            '<td width="50"></td> ' +
            '<td width="40">赛</td> ' +
            '<td width="40">上盘</td> ' +
            '<td width="40">平盘</td> ' +
            '<td width="40">下盘</td> ' +
            '<td width="40">赢</td> ' +
            '<td width="40">走</td> ' +
            '<td width="40">输</td> ' +
            '<td width="60">净</td> ' +
            '<td width="60">胜%</td> ' +
            '<td width="60">走%</td> ' +
            '<td width="60">负%</td> ' +
            '<td width="60">排名</td> ' +
            '</tr>';
        var trHead3 = '<tr bgcolor="#cfd1de"> ' +
            '<td width="50"></td> ' +
            '<td width="40">赛</td> ' +
            '<td width="40">大球</td> ' +
            '<td width="40">走</td> ' +
            '<td width="40">小球</td> ' +
            '<td width="40">大球%</td> ' +
            '<td width="40">走%</td> ' +
            '<td width="40">小球%</td> ' +
            '<td width="60">排名</td> ' +
            '</tr>';

        $.ajax({
                url: "/tournament_st.html",
                type: 'POST',
                data: {data_type: data_type, union_id: uid ? uid : union_id, team_id: team_id},
                dataType: 'JSON',
                success: function (e) {
                    if (data_type == 1 && e.league_data != null) {

                        for (var i = 0; i < e.league_data.length; i++) {
                            var tt;
                            var trHead;
                            var tdData = '';
                            var html = '';
                            if (i == 0) {
                                tt = '全场联赛积分';
                                trHead = trHead1;
                                $.each(e.league_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.league_data[i][k];
                                    if (k == 'total_score') {
                                        tt2 = '总成绩';
                                        tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                            '<td>' + data[4] + '</td> ' +
                                            '<td>' + data[5] + '</td> ' +
                                            '<td>' + data[6] + '</td> ' +
                                            '<td>' + data[7] + '</td> ' +
                                            '<td>' + data[8] + '</td> ' +
                                            '<td>' + data[9] + '</td> ' +
                                            '<td>' + data[10] + '</td> ' +
                                            '<td class="text-red">' + data[11] + '%</td> ' +
                                            '<td>' + data[12] + '%</td> ' +
                                            '<td>' + data[13] + '%</td> ' +
                                            '<td>' + data[14] + '</td> ' +
                                            '<td>' + data[15] + '</td> ' +
                                            '<td>' + data[16] + '</td> </tr>';
                                    } else if (k == 'home_score') {
                                        tt2 = '主场';
                                    } else if (k == 'guest_score') {
                                        tt2 = '客场';
                                    }

                                    if (k == 'home_score' || k == 'guest_score') {
                                        tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                            '<td>' + data[2] + '</td> ' +
                                            '<td>' + data[3] + '</td> ' +
                                            '<td>' + data[4] + '</td> ' +
                                            '<td>' + data[5] + '</td> ' +
                                            '<td>' + data[6] + '</td> ' +
                                            '<td>' + data[7] + '</td> ' +
                                            '<td>' + data[8] + '</td> ' +
                                            '<td class="text-red">' + data[9] + '%</td> ' +
                                            '<td>' + data[10] + '%</td> ' +
                                            '<td>' + data[11] + '%</td> ' +
                                            '<td>' + data[12] + '</td> ' +
                                            '<td>' + data[13] + '</td> ' +
                                            '<td>' + data[14] + '</td> </tr>';
                                    }

                                })

                            } else if (i == 1) {
                                tt = '半场联赛积分';
                                trHead = trHead1;
                                $.each(e.league_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.league_data[i][k];
                                    if (k == 'half_score') {
                                        tt2 = '总成绩';
                                    } else if (k == 'home_half_score') {
                                        tt2 = '主场';
                                    } else if (k == 'guest_half_score') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td>' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td class="text-red">' + data[9] + '%</td> ' +
                                        '<td>' + data[10] + '%</td> ' +
                                        '<td>' + data[11] + '%</td> ' +
                                        '<td>' + data[12] + '</td> ' +
                                        '<td>' + data[13] + '</td> ' +
                                        '<td>' + data[14] + '</td> </tr>';
                                })

                            } else if (i == 2) {
                                tt = '全场让球盘路';
                                trHead = trHead2;
                                $.each(e.league_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.league_data[i][k];
                                    if (k == 'total_pan_lou') {
                                        tt2 = '总成绩';
                                    } else if (k == 'home_pan_lu') {
                                        tt2 = '主场';
                                    } else if (k == 'guest_pan_lu') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td>' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td class="text-red">' + data[9] + '</td> ' +
                                        '<td>' + data[10] + '%</td> ' +
                                        '<td>' + data[11] + '%</td> ' +
                                        '<td>' + data[12] + '%</td> ' +
                                        '<td>' + data[0] + '</td> </tr>';
                                })
                            } else if (i == 3) {
                                tt = '半场让球盘路';
                                trHead = trHead2;
                                $.each(e.league_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.league_data[i][k];
                                    if (k == 'total_half_pan_lu') {
                                        tt2 = '总成绩';
                                    } else if (k == 'home_half_pan_lu') {
                                        tt2 = '主场';
                                    } else if (k == 'guest_half_pan_lu') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td>' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td class="text-red">' + data[9] + '</td> ' +
                                        '<td>' + data[10] + '%</td> ' +
                                        '<td>' + data[11] + '%</td> ' +
                                        '<td>' + data[12] + '%</td> ' +
                                        '<td>' + data[0] + '</td> </tr>';
                                })
                            } else if (i == 4) {
                                tt = '全场大小球盘路';
                                trHead = trHead3;
                                $.each(e.league_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.league_data[i][k];
                                    if (k == 'TotalBs') {
                                        tt2 = '总成绩';
                                    } else if (k == 'HomeBs') {
                                        tt2 = '主场';
                                    } else if (k == 'GuestBs') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td class="text-red">' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td>' + data[0] + '</td> ';
                                })
                            } else if (i == 5) {
                                tt = '半场大小球盘路';
                                trHead = trHead3;
                                $.each(e.league_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.league_data[i][k];
                                    if (k == 'TotalBsHalf') {
                                        tt2 = '总成绩';
                                    } else if (k == 'HomeBsHalf') {
                                        tt2 = '主场';
                                    } else if (k == 'GuestBsHalf') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td class="text-red">' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td>' + data[0] + '</td> ';
                                })
                            }

                            html += '<table class="table matchTj" cellspacing="0" cellpadding="0">' +
                                ' <thead> ' +
                                '<tr bgcolor="#f8eff2"> <th width="100%" colspan="14" class="strong"><span class="mr10">本赛季 </span> ' + tt + '</th> </tr> ' +
                                '</thead> <tbody> ' + trHead + tdData + '</tbody> </table>';

                            $('#tournamentSt').append(html);
                            $('#tournamentSt .matchTj tbody tr:even').css("backgroundColor", "#ffffff");
                        }

                    } else if (data_type == 2 && e.cup_data != null) {
                        for (var i = 0; i < e.cup_data.length; i++) {
                            var tt;
                            var trHead;
                            var tdData = '';
                            var html = '';

                            if (i == 0) {
                                tt = '全场让球盘路';
                                trHead = trHead2;
                                $.each(e.cup_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.cup_data[i][k];
                                    if (k == 'total_pan_lou') {
                                        tt2 = '总成绩';
                                    } else if (k == 'home_pan_lu') {
                                        tt2 = '主场';
                                    } else if (k == 'guest_pan_lu') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td>' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td class="text-red">' + data[9] + '</td> ' +
                                        '<td>' + data[10] + '%</td> ' +
                                        '<td>' + data[11] + '%</td> ' +
                                        '<td>' + data[12] + '%</td> ' +
                                        '<td>' + data[0] + '</td> </tr>';
                                })
                            } else if (i == 1) {
                                tt = '半场让球盘路';
                                trHead = trHead2;
                                $.each(e.cup_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.cup_data[i][k];
                                    if (k == 'total_half_pan_lu') {
                                        tt2 = '总成绩';
                                    } else if (k == 'home_half_pan_lu') {
                                        tt2 = '主场';
                                    } else if (k == 'guest_half_pan_lu') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td>' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td class="text-red">' + data[9] + '</td> ' +
                                        '<td>' + data[10] + '%</td> ' +
                                        '<td>' + data[11] + '%</td> ' +
                                        '<td>' + data[12] + '%</td> ' +
                                        '<td>' + data[0] + '</td> </tr>';
                                })
                            } else if (i == 2) {
                                tt = '全场大小球盘路';
                                trHead = trHead3;
                                $.each(e.cup_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.cup_data[i][k];
                                    if (k == 'TotalBs') {
                                        tt2 = '总成绩';
                                    } else if (k == 'HomeBs') {
                                        tt2 = '主场';
                                    } else if (k == 'GuestBs') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td class="text-red">' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td>' + data[0] + '</td> ';
                                })
                            } else if (i == 3) {
                                tt = '半场大小球盘路';
                                trHead = trHead3;
                                $.each(e.cup_data[i], function (k, v) {
                                    var tt2 = '';
                                    var data = e.cup_data[i][k];
                                    if (k == 'TotalBsHalf') {
                                        tt2 = '总成绩';
                                    } else if (k == 'HomeBsHalf') {
                                        tt2 = '主场';
                                    } else if (k == 'GuestBsHalf') {
                                        tt2 = '客场';
                                    }

                                    tdData += '<tr bgcolor="#f8f9fd" class="f12"><td>' + tt2 + '</td> ' +
                                        '<td>' + data[2] + '</td> ' +
                                        '<td>' + data[3] + '</td> ' +
                                        '<td>' + data[4] + '</td> ' +
                                        '<td class="text-red">' + data[5] + '</td> ' +
                                        '<td>' + data[6] + '</td> ' +
                                        '<td>' + data[7] + '</td> ' +
                                        '<td>' + data[8] + '</td> ' +
                                        '<td>' + data[0] + '</td> ';
                                })
                            }

                            html += '<table class="table matchTj" cellspacing="0" cellpadding="0">' +
                                ' <thead> ' +
                                '<tr bgcolor="#f8eff2"> <th width="100%" colspan="14" class="strong"><span class="mr10">本赛季 </span> ' + tt + '</th> </tr> ' +
                                '</thead> <tbody> ' + trHead + tdData + '</tbody> </table>';

                            $('#tournamentSt').append(html);
                            $('#tournamentSt .matchTj tbody tr:even').css("backgroundColor", "#ffffff");
                        }
                    }
                }
            }
        )
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
        // 最快进步
        }
    }

    //添加右侧 最快进步 html
    function addMipHtml(e, newStatsalyTzuqiu){
    	if (e.data.mip != undefined && e.data.mip != '') {
            var mipData = e.data.mip;
           
            var html1 = '';
            var i = 0;
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
        // 最快进步
        }

    }
    
  //最快进步、火热球员
    hot_and_mip();
    function hot_and_mip() {
      $.ajax({
          url: '/hot_and_mip.html',
          datatype: 'JSON',
          type: 'POST',
          data: {'team_id': team_id},
          success: function (e) {
          	var newStatsalyTzuqiu = e.data.newStatsalyTzuqiu;
          	
          	addHotHtml(e, newStatsalyTzuqiu);//状态火热
          	addMipHtml(e, newStatsalyTzuqiu);//最快进步
          }
      })
    }
    
    //最佳、火热球员，助攻榜，射手榜
    statsaly_tzuqiu();
    function statsaly_tzuqiu() {
        $.ajax({
            url: '/statsaly_tzuqiu.html',
            datatype: 'JSON',
            type: 'POST',
            data: {'team_id': team_id},
            success: function (e) {
            	var newStatsalyTzuqiu = e.data.newStatsalyTzuqiu;
//                if (e.data.hot != undefined && e.data.hot) {
//                    var html2 = '';
//                    var hotData = e.data.hot;
//                   
//                    var i = 0;
//                    for(hot in hotData){
//                    	i += 1;
//                    	html2 += '<tr>' +
//                        ' <td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
//                        '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[hot].player_id + '.html" target="_blank"> ' +
//                        '<div class="pull-left faceImg">' +
//                        '<img class="lazy" data-original="' + newStatsalyTzuqiu[hot]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[hot]['logo'] + '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[hot]['logo'] + '" width="40" height="40"></div> ' +
//                        '<div class="pull-left faceInf"> ' +
//                        '<p class="mb5" title="' + newStatsalyTzuqiu[hot]['playerName'] + '">' + newStatsalyTzuqiu[hot]['playerName'] + '</p> ' +
//                        '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[hot].age + '&nbsp;&nbsp;' + newStatsalyTzuqiu[hot].playerMainPosition + '</p> ' +
//                        '<p class="f12 text-999"><img class="mr5" src="' + team_logo + '" width="auto" height="12" >' + newStatsalyTzuqiu[hot].teamName + '</p> ' +
//                        '</div> </a> </td> ' +
//                        '<td class="f12">' + newStatsalyTzuqiu[hot].sn + '</td> ' +
//                        '<td class="f12"><em class="score">' + newStatsalyTzuqiu[hot].average + '</em></td> ' +
//                        '</tr>';
//                    	
//                    }
//                    
//                    $('#hotRank').html(html2);
//                    $('#hotRank  tr:even').css("backgroundColor", "#f8f9fd");
//                }

                // 最快进步
//                if (e.data.mip != undefined && e.data.mip) {
//                    var mipData = e.data.mip;
//                   
//                    var html1 = '';
//                    var i = 0;
//                    for(mip in mipData){
//                    	i += 1;
//                    	html1 += '<tr>' +
//                        ' <td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
//                        '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[mip].player_id + '.html" target="_blank"> ' +
//                        '<div class="pull-left faceImg">' +
//                        '<img class="lazy" data-original="' + newStatsalyTzuqiu[mip]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[mip]['logo'] + '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[mip]['logo'] + '" width="40" height="40"></div> ' +
//                        '<div class="pull-left faceInf"> ' +
//                        '<p class="mb5" title="' + newStatsalyTzuqiu[mip]['playerName'] + '">' + newStatsalyTzuqiu[mip]['playerName'] + '</p> ' +
//                        '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[mip].age + '&nbsp;&nbsp;' + newStatsalyTzuqiu[mip].playerMainPosition + '</p> ' +
//                        '<p class="f12 text-999"><img class="mr5" src="' + team_logo + '" width="auto" height="12" >' + newStatsalyTzuqiu[mip].teamName + '</p> ' +
//                        '</div> </a> </td> ' +
//                        '<td class="f12">' + newStatsalyTzuqiu[mip].newRate + '</td> ' +
//                        '<td class="f12"><em class="score">' + newStatsalyTzuqiu[mip].diff + '</em></td> ' +
//                        '</tr>';
//                    	
//                    }
//
//                    $('#mipRank').html(html1);
//                    $('#mipRank  tr:even').css("backgroundColor", "#f8f9fd");
//                }
                
                // 射手榜
                if (e.data.goal != undefined && e.data.goal) {
                    var goalData = e.data.goal;
                   
                    var htmlGoal = '';
                    var i = 0;
                    for(goal in goalData){
                    	i += 1;
                    	htmlGoal += '<tr>' +
                    	'<td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
                        '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[goal].player_id + '.html" target="_blank"> ' +
                        '<div class="pull-left faceImg">' +
                        '<img class="lazy" data-original="' + newStatsalyTzuqiu[goal]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[goal]['logo'] + 
                        '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[goal]['logo'] + '" width="40" height="40"></div> ' +
                        '<div class="pull-left faceInf"> ' +
                        '<p class="mb5" title="' + newStatsalyTzuqiu[goal]['playerName'] + '">' + newStatsalyTzuqiu[goal]['playerName'] + '</p> ' +
                        '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[goal]['age'] + '&nbsp;&nbsp;' + newStatsalyTzuqiu[goal]['playerMainPosition'] + '</p> ' +
                        '<p class="f12 text-999"><img class="mr5" src="' + team_logo + '" width="auto" height="12" >' + team_name + '</p> ' +
                        '</div> </a> </td> ' +
                        '<td class="f12">' + newStatsalyTzuqiu[goal]['goal'] + '</td> ' +
                        '</tr>';
                    }

                    $('#goalsRank').html(htmlGoal);
                }else{
                    $('#goalsRank').parents('.rBest').remove();
                }

                
                goal_rank(e);// 射手榜
                pass_rank(e); // 助攻榜
            }
        })
    }
    
    // 射手榜
    function goal_rank(e){
        if (e.data.goal != undefined && e.data.goal) {
        	var newStatsalyTzuqiu = e.data.newStatsalyTzuqiu;
            var goalData = e.data.goal;
           
            var htmlGoal = '';
            var i = 0;
            for(goal in goalData){
            	i += 1;
            	htmlGoal += '<tr>' +
            	'<td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
                '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[goal].player_id + '.html" target="_blank"> ' +
                '<div class="pull-left faceImg">' +
                '<img class="lazy" data-original="' + newStatsalyTzuqiu[goal]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[goal]['logo'] + 
                '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[goal]['logo'] + '" width="40" height="40"></div> ' +
                '<div class="pull-left faceInf"> ' +
                '<p class="mb5" title="' + newStatsalyTzuqiu[goal]['playerName'] + '">' + newStatsalyTzuqiu[goal]['playerName'] + '</p> ' +
                '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[goal]['age'] + '&nbsp;&nbsp;' + newStatsalyTzuqiu[goal]['playerMainPosition'] + '</p> ' +
                '<p class="f12 text-999"><img class="mr5" src="' + team_logo + '" width="auto" height="12" >' + team_name + '</p> ' +
                '</div> </a> </td> ' +
                '<td class="f12">' + newStatsalyTzuqiu[goal]['goal'] + '</td> ' +
                '</tr>';
            }

            $('#goalsRank').html(htmlGoal);
        }
    }
    
    //助攻榜
    function pass_rank(e) {
    	if (e.data.pass != undefined && e.data.pass != '') {
    		var newStatsalyTzuqiu = e.data.newStatsalyTzuqiu;
            var goalData = e.data.pass;
           
            var htmlGoal = '';
            var i = 0;
            for(goal in goalData){
            	i += 1;
            	htmlGoal += '<tr>' +
            	'<td><span class="rank rank0' + i + '">' + i + '</span></td> ' +
                '<td class="text-l"> <a href="/player/' + newStatsalyTzuqiu[goal].player_id + '.html" target="_blank"> ' +
                '<div class="pull-left faceImg">' +
                '<img class="lazy" data-original="' + newStatsalyTzuqiu[goal]['logo'] + '" alt="sunli10pm" original="' +  newStatsalyTzuqiu[goal]['logo'] + 
                '" title="" style="display: inline;" src="' +  newStatsalyTzuqiu[goal]['logo'] + '" width="40" height="40"></div> ' +
                '<div class="pull-left faceInf"> ' +
                '<p class="mb5" title="' + newStatsalyTzuqiu[goal]['playerName'] + '">' + newStatsalyTzuqiu[goal]['playerName'] + '</p> ' +
                '<p class="f12 text-999 mb5">' + newStatsalyTzuqiu[goal]['age'] + '&nbsp;&nbsp;' + newStatsalyTzuqiu[goal]['playerMainPosition'] + '</p> ' +
                '<p class="f12 text-999"><img class="mr5" src="' + team_logo + '" width="auto" height="12" >' + team_name + '</p> ' +
                '</div> </a> </td> ' +
                '<td class="f12">' + newStatsalyTzuqiu[goal]['pass'] + '</td> ' +
                '</tr>';
            }

            $('#passRank').html(htmlGoal);
        }else{
            $('#passRank').parents('.rBest').remove();
        }
    }

    $('.tableData01 tbody tr:even').css("backgroundColor", "#ffffff");
    $('.teamZr tbody tr:even').css("backgroundColor", "#ffffff");
    $('.playerTj tbody tr:even').css("backgroundColor", "#ffffff");
    $('.playerZh tbody tr:even').css("backgroundColor", "#ffffff");


    setTimeout(function () {
        var len = $('.dataRight').find('.rBest').length;
        if(len ==0){
            $('.dataRight').append('<div class="right-no-data"></div>');
        }
    },2500)
});

function toDecimal(x) {
    var f = parseFloat(x);
    if (isNaN(f)) {
        return;
    }
    f = Math.round(x*100)/100;
    return f;
}
