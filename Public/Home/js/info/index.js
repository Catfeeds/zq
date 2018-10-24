/**
 * index js
 *
 * @author Chensiren <245017279@qq.com>
 *
 * @since  2018-01-10
 *
 **/
$(function () {
	 var unions = JSON.parse($('.data_info_unions').html());
     var continents = JSON.parse($('.data_info_continents').html());
     var countryMap = JSON.parse($('.data_info_countryMap').html());
     var country_unions = JSON.parse($('.data_info_country_unions').html());
     
     $("[data-toggle='tooltip']").tooltip();
     
    //世界杯有奖竞猜滚动
    $(".panel-group-box").mCustomScrollbar({
        theme: "light-3",
        autoDraggerLength: true
    });
    //赛事切换
    $('.raceNav ul li').click(function (e) {
        var numIndex = $(this).index();
        $(this).addClass('active').siblings().removeClass('active');
        $('.raceCon .raceList').eq(numIndex).removeClass('hide').addClass('show').siblings().removeClass('show').addClass('hide');
        showList();
    });

    //tab切换
    $('.match-tit a').click(function (e) {
        $(this).addClass('on').siblings().removeClass('on');
        showList();
    });

    //国家选择
    //hover展示
    $(document).on("mouseover mouseout", ".raceList .raceMain ul li", function (event) {
        $(this).parent("ul").find(".popLi").css({'display': 'block'});
        if (event.type == "mouseover") {
            $(this).addClass("active").siblings().removeClass("active");
            var t = $(this).parent("ul").find(".popLi .popLiIn");
            var continent = $('.raceNav .active').attr('data-type');//洲
            var selectType = $('.match-tit .on').attr('data-type');//tab
            if (continent != 0 && selectType == 0) {
                var i = $(this).find("div").html(),
                    a = $(this).parent("ul").find(".popLi"),
                    e = $("i.arrowTop"),
                    n = $(this).parent().find("li").index($(this)) + 1,
                    s = n % 5 == 0 ? n / 5 * 5 : 5 * Math.floor(n / 5) + 5;
                t.html(i);
                for (var o = 1; o <= 5; o++) {
                    var l = $(this).parent().find("li").eq(s - o).length;
                    if (0 !== l) {
                        $(this).parent().find("li").eq(s - o).after(a);
                        break
                    }
                }
                var c = $(this).offset().left + $(".raceMain ul li").outerWidth() / 2 - t.offset().left - e.outerWidth() / 2;
                e.css("left", c);

                showList($(this).attr('data-id'));
            }
        } else if (event.type == "mouseout") {
            return;//鼠标离开
        }
    });

    $('.raceCon').mouseleave(function (event) {
        $(".raceMain .popLi").css({'display': 'none'});
    });
    //click展示
    // $(document).on('click', ".raceList .raceMain ul li", function () {
    //     $(this).addClass("active").siblings().removeClass("active");
    //     var t = $(this).parent("ul").find(".popLi .popLiIn");
    //     var continent = $('.raceNav .active').attr('data-type');//洲
    //     var selectType = $('.match-tit .on').attr('data-type');//tab
    //     if (continent != 0 && selectType == 0) {
    //         var i = $(this).find("div").html(),
    //             a = $(this).parent("ul").find(".popLi"),
    //             e = $("i.arrowTop"),
    //             n = $(this).parent().find("li").index($(this)) + 1,
    //             s = n % 4 == 0 ? n / 4 * 4 : 4 * Math.floor(n / 4) + 4;
    //         t.html(i);
    //         for (var o = 1; o <= 4; o++) {
    //             var l = $(this).parent().find("li").eq(s - o).length;
    //             if (0 !== l) {
    //                 $(this).parent().find("li").eq(s - o).after(a);
    //                 break
    //             }
    //         }
    //         var c = $(this).offset().left + $(".raceMain ul li").outerWidth() / 2 - t.offset().left - e.outerWidth() / 2;
    //         e.css("left", c)

    //         showList($(this).attr('data-id'));
    //     }
    // });

    //今日赛事
    $(document).on('click', ".hotRace .panel-title a", function () {
        $(this).stop().toggleClass('active').parents('div.panel').siblings().find('.panel-title a').removeClass('active');
    });

    // $(document).on('click', ".hotRace .more", function () {
    //     $(".hotRace .panel").css('display','block');
    //     $(this).css('display','none');
    // });

    $('.raceNav ul').find('li').eq(1).addClass('active');

    showList();

    /**
     * 显示列表
     * @param country_id
     */
    function showList(country_id) {
        var html = '';
        var continent = $('.raceNav .active').attr('data-type');//左侧选择
        var selectType = $('.match-tit .on').attr('data-type');//国家洲际tab选择

        //tab切换逻辑
        if (continent == 0) {
            if(selectType == 0){
                $('.match-tit').find('a').eq(1).css('display', 'none');
                $('.match-tit').find('a').eq(0).text('洲际赛事');
                $('.match-tit').find('a').eq(0).addClass('on').siblings().removeClass('on');
                selectType = 1;
            }else{
                $('.match-tit').find('a').eq(0).css('display', 'none');
                $('.match-tit').find('a').eq(1).addClass('on').siblings().removeClass('on');
                selectType = 1;
            }
        }else {
            $('.match-tit').find('a').eq(0).text('国家');
            $('.match-tit').find('a').eq(0).css('display', '');
            $('.match-tit').find('a').eq(1).css('display', '');
        }

        var cid = '';
        $.each(continents, function (kk, vv) {
            if (vv['continent_id'] == continent) {
                cid = vv['country_id'];
            }
        });

        //选择国际赛事-洲际
        if (continent == 0 && selectType == 1) {
            $.each(unions, function (uk, uv) {
                if (uv['country_id'] == cid) {
                    html += '<li> ' +
                        '<i><img src="' + uv.logo + '" width="24" height="16"></i> ' +
                        '<a href="' + uv.jump_url + '" target="_blank">' + uv.union_name + '</a> ' +
                        '<div class="popLiIn hide"> </div> ' +
                        '</li>';
                }
            });
            $('.raceList ul').html(html);
        }

        //选择洲际-国家
        if (continent > 0 && selectType == 0) {
            if (country_id) {
                var countryLeague = '';
                $.each(country_unions, function (k2, v2) {
                    if (country_id == v2['country_id'] && v2.hasOwnProperty('order')) {
                        countryLeague += '<a href="' + v2.jump_url + '" target="_blank" >' + v2['union_name'] + '</a>';
                    }
                });

                $('.popLi .popLiIn').html(countryLeague);
            } else {
                var i = 0;
                $.each(countryMap[continent], function (k, v) {
                    if (cid != v['country_id']) {
                        html += ' <li data-id="' + v['country_id'] + '"> ' +
                            '<i><img src="' + v.logo + '" width="24" height="16"></i> ' +
                            '<a href="javascript:void (0);">' + v.s_name + '</a>' +
                            '</li>';

                        if ((countryMap[continent].length > 3 && i == 3) || (countryMap[continent].length < 3 && i == 0)) {
                            var countryLeague = '';

                            $.each(country_unions, function (k2, v2) {
                                if (countryMap[continent][0]['country_id'] == v2['country_id']) {
                                    countryLeague += '<a href="' + v2.jump_url + '" target="_blank" title="' + v2['union_name'] + '">' + v2['union_name'] + '</a>';
                                }
                            });

                            var defaultShow = '<div class="popLi"> ' +
                                '<i class="arrowTop" style="left: 48.5px;"></i> ' +
                                '<div class="popLiIn">' + countryLeague + '</div> ' +
                                '</div>';
                            html += defaultShow;
                        }
                        i++;
                    }
                });
                $('.raceList ul').html(html);
            }
        }

        //选择洲际-洲际赛事
        if (continent > 0 && selectType == 1) {
            $.each(unions, function (uk, uv) {
                if (uv['country_id'] == cid) {
                    html += '<li> ' +
                        '<i><img src="' + uv.logo + '" width="24" height="16"></i> </i> ' +
                        '<a href="' + uv.jump_url + '" target="_blank">' + uv.union_name + '</a> ' +
                        '<div class="popLiIn hide"> </div> ' +
                        '</li>';
                }
            });
            $('.raceList ul').html(html);
        }
    }

    $(document).on('click', '#accordion .panel-body ul li a', function (event) {
        event.stopPropagation();
    });

    $(document).on('click', '#accordion .panel-body ul li', function (event) {
        event.stopPropagation();
        window.parent.location = $(this).attr('hrefurl');
        // window.location.href = $(this).attr('hrefurl');
    });

});

