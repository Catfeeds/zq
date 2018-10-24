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

    //队员
    $('.zoneNav li').eq(0).addClass('active').siblings().removeClass('active');
    $('.zoneCo .zoneBox').eq(0).show().siblings().hide();

    $('.zoneNav li').click(function (e) {
        $(this).addClass('active').siblings().removeClass('active');
        var zoneNum = $(this).index();
        $('.zoneCo .zoneBox').eq(zoneNum).show().siblings().hide();
    });

    var pNum = 15;
    //近期数据帅选
    var curTwoYearData, twoYearData;
    $(document).on('click', '#twoYearMenu li', function () {
        var data_type = $(this).text();
        $('.rouNum').text(data_type);
        var html = '';
        curTwoYearData = new Array();
        var i = 0;
        $.each(twoYearData, function (k, v) {
            if (v[1] == data_type || data_type == '' || data_type == '全部赛事') {
                curTwoYearData.push(v);
                if (i < pNum) {
                    html += returnHtml('two_year_data', v);
                }
                i++;
            }
        });

        $('#twoYearData').html(html);
        $('#twoYearData tr:even').css("background-color", "#FFFFFF");
        showTwoYearPage(1);
    });

    //近期数据点击分页
    var curTwoYearData, twoYearData;
    $(document).on('click', '#tyPageLi li', function () {
        var p = $(this).attr('page-data');
        var start = (p - 1) < 0 ? 1 : p - 1;
        var html = '';
        for (var i = 0; i < curTwoYearData.length; i++) {
            if (i >= start * pNum && i < (start * pNum + pNum)) {
                html += returnHtml('two_year_data', curTwoYearData[i]);
            }
        }

        $('#twoYearData').html(html);
        showTwoYearPage(p);
        $('#twoYearData tr:even').css("background-color", "#FFFFFF");

    });

    player_data();
    function player_data() {
        $.ajax({
            type: 'POST',
            url: "/player_data",
            dataType: 'json',
            data: {player_id: player_id},
            success: function (e) {
                if (e.status == 1) {
                    var html = '';
                    if (e.two_year != undefined) {
                        twoYearData = curTwoYearData = e.two_year;
                        var n = e.two_year.length > pNum ? pNum : e.two_year.length;
                        for (var i = 0; i < n; i++) {
                            html += returnHtml('two_year_data', e.two_year[i]);
                        }

                        $('#twoYearData').html(html);

                        showTwoYearPage(1);
                    }

                    if (e.two_year_menu != undefined) {
                        $.each(e.two_year_menu, function (k, v) {
                            $('#twoYearMenu').append('<li><a href="javascript:void (0);" data-type="' + k + '">' + v + '</a></li>')
                        })
                    }
                }
                $('#twoYearData tr:even').css("background-color", "#FFFFFF");
                $('#tableData01 tbody tr:even').css("background-color", "#FFFFFF");
            }
        });
    }

    //返回html
    function returnHtml(t, v) {
        var html = '';
        if(typeof v[25] != 'undefined'){
            var pf = v[25];
        }else {
            var pf = 0;
        }
        if (t == 'two_year_data') {
            html += '<tr bgcolor="#f8f9fd"> ' +
                '<td bgcolor="' + v[9] + '" class="text-fff">' + v[1] + '</td> ' +
                '<td>' + v[4] + '</td> ' +
                '<td> <a href="/team/' + v[5] + '.html" target="_blank">' + v[10] + '</a></td> ' +
                '<td>' + v[7] + ' - ' + v[8] + '</td> ' +
                '<td><a href="/team/' + v[6] + '.html" target="_blank">' + v[13] + '</a></td> ' +
                '<td>' + v[18] + '</td> ' +
                '<td>' + v[19] + '</td> ' +
                '<td>' + v[17] + '</td> ' +
                '<td>' + v[16] + '</td> ' +
                '<td class="text-red">' + pf + '</td> ' +
                '</tr>';
        }

        return html;
    }


    //显示近期数据分页
    function showTwoYearPage(p) {
        var pg = parseInt(p);
        var total = curTwoYearData.length;
        var pn = Math.ceil(total / pNum);

        if (pn <= 1)
            return;

        var page = '';
        for (var i = 1; i <= pn; i++) {
            page += '<li  page-data="' + i + '"> <a href="javascript:;">' + i + '</a></li>';
        }

        //下一页、上一页
        var prev = (pg - 1) <= 1 ? 1 : (pg - 1);
        var next = (pg + 1) > pn ? 1 : (pg + 1);

        var html = '<tr> ' +
            '<td colspan="12"> ' +
            '<nav aria-label="Page navigation"> ' +
            '<ul class="pagination pagination-sm" id="tyPageLi"> ' +
            '<li page-data="'+prev+'"> <a href="javascript:;" aria-label="Previous"> <span aria-hidden="true">«</span> </a> </li> ' + page +
            '<li page-data="'+next+'"> <a href="javascript:;" aria-label="Next"> <span aria-hidden="true">»</span> </a> </li></ul></nav></td> </tr>';

        $('#twoYearData').append(html);
        $('#tyPageLi').find('li').eq(pg).addClass('active');

    }

    $('.tableData01 tbody tr:even').css("backgroundColor", "#ffffff");
});