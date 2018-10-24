$(function () {
    var level = $("input[name=level]").val();
    var name = $("input[name=name]").val();

    console.log(level);
    console.log(name);

    getData();
    var all_data;

    function getData() {
        $.ajax({
            url:'/Schedulelist/schedule',
            type:'get',
            async:false,
            data:{
                "type":name
            },
            timeout:5000,
            dataType:'json',
            beforeSend:function(xhr){
            },
            success:function(data,textStatus,jqXHR){
                console.log(data);
                if (parseInt(level) === 1) {
                    all_data = data;
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    var html = sectionHtml(data.nowNum) + bannerHtml(data.allNum, data.nowNum)+ ListMain(data.info[data.nowNum -1]);
                    $("section.wrapCon").html(html);
                } else if (parseInt(level) === 2) {
                    all_data = data;
                    var string = all_data.isTaotai ? all_data.nowGroup : "";
                    var init_data = all_data.isTaotai ? ClickListMainTaotai(all_data.taotai) : ClickListMain(all_data.xiaozu[all_data.nowGroup - 1]);
                    $("span.leaguesIcon").html('<img src="'+all_data.iconUrl+'">');
                    $("li.name >a").html(string);
                    $("section.n_module").html(chSectionModule(all_data.isTaotai, all_data.nowGroup));
                    $("div.swiper-container").html(chBannerHtml(all_data.xiaozu.length, all_data.nowGroup));
                    $("div.main > ul").html(init_data);
                    if (all_data.isTaotai) {
                        $("div.row_num").addClass("hide_click");
                        $("div.swiper-container1").addClass("hide_swiper");
                        $('.row_num').next().removeClass('arrow');
                    }
                } else if (parseInt(level) === 3) {
                    var now = new Date().format("yyyy-MM-dd");
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    window.all_data = data.data;
                    if (filterData(data.data, now).length > 0) {
                        $("div.no-data").addClass("hide_swiper").removeClass("display_swiper");
                        $("#bkschedule").html(bkClickListMain(filterData(data.data, now)));
                    } else {
                        $("div.no-data").addClass("display_swiper").removeClass("hide_swiper");
                    }
                } else if (parseInt(level) === 4) {
                    all_data = data['data'];
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    $("div.main > ul").html(ClickListMain(all_data.day_schedule));
                   $("div.group").html(WorldCupSchedule(all_data['group_schedule'], getPointTeam(all_data.point_rank), 1));
                   $("div.eliminate").html(WorldCupSchedule(all_data['knockout_matchs'], undefined, 2));
                   $("div.powerful").html(WorldCupSchedule(all_data['giant_matchs'], undefined, 0));
                }
            },
            error:function(xhr,textStatus){
                console.log('错误');
                console.log(xhr);
                console.log(textStatus);
            },
            complete:function(){
            }
        });
    }


    /**
     *  联赛部分 js 开始 -----------------------------------------------------
     */



    /**
     * 联赛点击
     */
    $("a.row_link").on("click",  function() {
        $(".list-f li a").removeClass("row_link_on");
        $(this).addClass("row_link_on");
        var num = $(this).text();
        $("div.main > ul").html(ClickListMain(all_data.info[num -1]));
        $("div.row_num").html("第"+num+"轮");
    });

    $("div.row_num").on("click", function() {
        if (!$(this).hasClass("hide_click")) {
            if ($("div.swiper-container1").hasClass("display_swiper")){
                $("div.swiper-container1").removeClass("display_swiper").addClass("hide_swiper");
            } else {
                $("div.swiper-container1").removeClass("hide_swiper").addClass("display_swiper");
            }
        }
    }).click().click();



    /**
     * 推荐
     * @param nowRow
     * @returns {string}
     */
    function sectionHtml(nowRow) {
        return '<section class="n_module">' +
            '<ul class="nav_list nav_list03 clearfix">' +
            '<li><a href="info.html"><span>资讯</span></a></li>' +
            '<li><a href="javascript:;" class="on" ><div class="row_num"><span>第'+nowRow+'轮</span></div><div class="arrow"></div></div></a></li>' +
            '<li><a href="rank.html"><span>排行</span></a></li>' +
            '</ul>' +
            '</section>' +
            '<script  type="text/javascript"></script>';
    }


    /**
     * banner图
     * @param all
     * @param now
     * @returns {string}
     */
    function bannerHtml(all, now) {
        var totalNum = Math.ceil(all/10);
        var page = Math.floor((now/10) - 0.01);
        var string = '<div class="swiper-container swiper-container1 banner-w"><div class="swiper-wrapper">';
        for (var i = 0; i < totalNum; i++) {
            string += '<div class="swiper-slide b"><ul class="list-f">';
            for (var k = 1; k < 11; k++) {
                if ((k + i * 10) > all) {
                    break;
                }
                if ((k + i * 10) === parseInt(now)) {
                    string += '<li><a class="row_link_on row_link">'+(k + i*10 )+'</a></li>';
                }else {
                    string += '<li><a class="row_link">'+(k + i*10 )+'</a></li>';
                }
            }
            string += '</ul></div>';
        }
        string += '</div><div class="swiper-pagination ban"><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet"></span></div></div>' +
            '<script>$(function(){var swiper=new Swiper(".swiper-container1",{pagination: {el : ".ban", clickable:true}, observer:true,observeParents:true,spaceBetween:30,centeredSlides:true,autoplay:false,autoplayDisableOnInteraction:false});swiper.slideTo('+(page)+', false, false);})</script>';
        return string;
    }


    /**
     * 初始数据
     * @return {string}
     */
    function ListMain(data) {
        var string = '<div class="main"><ul>';
        for (var i = 0; i < data.length; i++) {
            var status = "";
            var status_color = "";
            switch(data[i].game_state) {
                case -1 :
                    status = "已完赛";
                    status_color = "right-r";
                    break;
                case 0 :
                    status = "未开赛";
                    status_color = "right-r";
                    break;
                case 1 :
                    status = "进行中";
                    status_color = "right-f";
                    break;
                case 2 :
                    status = "进行中";
                    status_color = "right-f";
                    break;
                case 3 :
                    status = "进行中";
                    status_color = "right-f";
                    break;
                case 4 :
                    status = "进行中";
                    status_color = "right-f";
                    break;
                default :
                    status = "待定";
                    status_color = "right-r";
                    break;
            }
            var date_arr = data[i].gtime.split(" ");
            var month = date_arr[0].split("-")[0];
            var day = date_arr[0].split("-")[1];
            var score;
            if (data[i].game_state == -1) {
                score = data[i].score;
            } else {
                score = "- -"
            }

            string += '<li class="main-f"><a href="../Details/data/scheid/'+data[i].game_id+'.html"><div class="time-f">' +
                '<span class="left-f">'+month+'月'+day+'日 '+data[i].week+'</span>'+
                '<span class="middle-f">'+date_arr[1]+'</span>'+
                '<span class="'+status_color+'">'+status+'</span>'+
                '</div><div class="name-f">' +
                '<span class="team-f"><img src="'+data[i].homeTeamLogo+'"></span>' +
                '<span class="team-name"><ul><li class="h-j">'+data[i].home_team_name+'</li><li class="b-f">'+score+'</li><li class="h-j">'+data[i].away_team_name+'</li></ul></span>' +
                '<span class="team-y"><img src="'+data[i].awayTeamLogo+'"></span>' +
                '</div></a></li>'
        }
        string +='</ul></div>';
        return string;
    }


    /**
     * 点击获取数据
     * @return {string}
     */
    function ClickListMain(data) {
        var string = "";
        for (var i = 0; i < data.length; i++) {
            string += ClickListMainCommon(data[i]);
        }
        return string;
    }


    /**
     *  联赛部分 js 结束 -----------------------------------------------------
     */






    /**
     *  杯赛部分 js 开始 -----------------------------------------------------
     */


    $("a.ch_row_link").on("click",  function() {
        $(".list-f li a").removeClass("ch_row_link_on");
        $(this).addClass("ch_row_link_on");
        var num = $(this).text();
        $("div.main > ul").html(ClickListMain(all_data.xiaozu[num -1]));
        $("div.row_num").html("第"+num+"轮");
    });

    $("div.second-list ul.champion li a:eq(0)").on("click", function() {
        var now_num = all_data.isTaotai ? all_data.xiaozu.length : all_data.nowGroup;
        $("div.main > ul").html(ClickListMain(all_data.xiaozu[now_num -1]));
        $("div.row_num").removeClass("hide_click").html("第"+now_num +"轮");
        $(".list-f li a").removeClass("ch_row_link_on").each(function () {
            if ($(this).text() == now_num) {
                $(this).addClass("ch_row_link_on");
            }
        });
        $('.row_num').next().addClass('arrow');
    });

    $("div.second-list ul.champion li a:eq(1)").on("click", function() {
        var now_num = all_data.isTaotai ? all_data.nowGroup : null;
        $("div.main > ul").html(ClickListMainTaotai(all_data.taotai));
        $("div.row_num").html(now_num);
        $("div.row_num").addClass("hide_click");
        $("div.swiper-container1").addClass("hide_swiper");
        $('.row_num').next().removeClass('arrow');
    });

    function ClickListMainTaotai(data)
    {
        var string = "";
        for (var i = 0; i < data.length; i++) {
            var title = data[i]['title']?'决赛':'比赛';
            string += '<div class="grouping clearfix"><span class="left-s">'+data[i]['title']+'</span><span class="right-s">'+title+'</span></div>';
            for (var j = 0; j < data[i]['data'].length; j++) {
                string += ClickListMainCommon(data[i]['data'][j]);
            }
        }
        return string;
    }

    //赛事li生成公共方法
    function ClickListMainCommon(data) {
        var string = "";
        var status = "";
        var status_color = "";
        switch(data.game_state) {
            case -1 :
                status = "已完赛";
                status_color = "right-r";
                break;
            case 0 :
                status = "未开赛";
                status_color = "right-r";
                break;
            case 1 :
                status = "进行中";
                status_color = "right-f";
                break;
            case 2 :
                status = "进行中";
                status_color = "right-f";
                break;
            case 3 :
                status = "进行中";
                status_color = "right-f";
                break;
            case 4 :
                status = "进行中";
                status_color = "right-f";
                break;
            default :
                status = "未开赛";
                status_color = "right-r";
                break;
        }
        var date_arr = data.gtime.split(" ");
        var month = date_arr[0].split("-")[0];
        var day = date_arr[0].split("-")[1];
        var score;
        var home_img_url = data.homeTeamLogo;
        var away_img_url = data.awayTeamLogo;
        if (data.game_state == -1) {
            score = data.score;
        } else {
            score = "- -"
        }
        if (level == 4) {
            if (!(data.homeTeamLogo === undefined)) {
                home_img_url = data.homeTeamLogo;
            } else {
                home_img_url = "/Public/Mobile/images/schedule/no_image.png";
            }
            if (!(data.awayTeamLogo === undefined)) {
                away_img_url = data.awayTeamLogo;
            } else {
                away_img_url = "/Public/Mobile/images/schedule/no_image.png";
            }
        }
        var url = '../Details/data/scheid/'+data.game_id+'.html';
        if(!htmlData)
        {
            url = 'javascript:;';
        }
        string = '<li class="main-f"><a href="'+url+'"><div class="time-f">' +
            '<span class="left-f">'+month+'月'+day+'日 '+data.week+'</span>'+
            '<span class="middle-f">'+date_arr[1]+'</span>'+
            '<span class="'+status_color+'">'+status+'</span>'+
            '</div><div class="name-f">' +
            '<span class="team-f"><img src="'+home_img_url+'"></span>' +
            '<span class="team-name"><ul><li class="h-j">'+data.home_team_name+'</li><li class="b-f">'+score+'</li><li class="h-j">'+data.away_team_name+'</li></ul></span>' +
            '<span class="team-y"><img src="'+away_img_url+'"></span>' +
            '</div></a></li>';

        return string;
    }


    function chSectionModule(status, data) {
       data =  status ? data : "第" + data + "轮";
        return '<ul class="nav_list nav_list03 clearfix">' +
        '<li><a href="info.html"><span>资讯</span></a></li>' +
        '<li><a href="javascript:;" class="on" ><div class="row_num"><span>'+data+'</span></div><div class="arrow"></div></a></li>' +
            '<li><a href="rank.html"><span>排行</span></a></li></ul>';
    }


    function chBannerHtml(all, now) {
        var totalNum = Math.ceil(all/10);
        var page = Math.floor((now/10) - 0.01);
        page = isNaN(page) ? 0 : page;
        console.log(page);
        var string = '<div class="swiper-wrapper">';
        for (var i = 0; i < totalNum; i++) {
            string += '<div class="swiper-slide b"><ul class="list-f">';
            for (var k = 1; k < 11; k++) {
                if ((k + i * 10) > all) {
                    break;
                }
                if ((k + i * 10) === parseInt(now)) {
                    string += '<li><a class="ch_row_link_on ch_row_link">'+(k + i*10 )+'</a></li>';
                }else {
                    string += '<li><a class="ch_row_link">'+(k + i*10 )+'</a></li>';
                }
            }
            string += '</ul></div>';
        }
        string += '</div><div class="swiper-pagination ban"><span class="swiper-pagination-bullet swiper-pagination-bullet-active"></span><span class="swiper-pagination-bullet"></span><span class="swiper-pagination-bullet"></span></div>' +
            '<script>$(function(){var swiper=new Swiper(".swiper-container1",{pagination: {el : ".ban", clickable:true}, observer:true,observeParents:true,paginationClickable:true,spaceBetween:30,centeredSlides:true,autoplay:false,autoplayDisableOnInteraction:false});swiper.slideTo('+(page)+', false, false);})</script>';
        return string;
    }

    /**
     *  杯赛部分 js 结束 -----------------------------------------------------
     */


    /**
     *  2018世界杯部分 js 开始 -----------------------------------------------------
     * @return {string}
     */
    function WorldCupSchedule(data, team, type) {
         var string = "";
         var key_name = "";
        for (var key in  data) {
            if (type === 1) {
                key_name = '<span class="one-s-t">'+key+'</span>'+ " <span class='group'>组</span> <span class='declare'>(" + team[key].join("、") + ')</span>';
            } else {
                key_name = key;
            }
            if (type === 2) {
                string += '<div class="grouping clearfix"><span class="left-s">' +
                    key_name+'</span><span class="right-s">决赛</span></div>' +
                    '<div class="list clearfix"><ul><li class="b-time">北京时间</li><li class="b-team">主队</li><li class="b-session">客队</li><li class="b-success">资料</li></ul>' +
                    '</div>';
            } else {
                string += '<div class="grouping clearfix"><div class="left-s">' +
                    key_name+'</div></div>' +
                    '<div class="list clearfix"><ul><li class="b-time">北京时间</li><li class="b-team">主队</li><li class="b-session">客队</li><li class="b-success">资料</li></ul>' +
                    '</div>';
            }
            string += '<div class="main-s"><ul>';
            for (var i = 0; i < data[key].length; i++) {
                var mon_day = data[key][i].gtime.split(" ")[0].split("-");
                var time = data[key][i].gtime.split(" ")[1];
                var line = (i === data[key].length -1) ? "" : "line";
                var url = '../Details/data/scheid/'+data[key][i].game_id+'.html';
                if(!htmlData)
                {
                    url = 'javascript:;';
                }
                var score = (data[key][i].game_state == 0) ? "---" : data[key][i].score;
                string += '<li class="m-s '+line+'"><span class="specific">' +
                    '<div class="year">'+mon_day[0]+'/'+mon_day[1]+'</div>' +
                    '<div class="clock">'+time+'</div>' +
                    '</span>' +
                    '<span class="toponymy">'+data[key][i].home_team_name+'</span>' +
                    '<span class="symbol">'+score+'</span>' +
                    '<span class="place">'+data[key][i].away_team_name+'</span>' +
                    '<span class="particular"><a href="'+url+'">详</a></span></li>';
            }
            string += '</ul></div>';
        }
        return string;
    }


    function getPointTeam(data) {
        var JsonString = "";
        for (var key in data) {
            var k = JSON.stringify(key);
            var a = [];
            for (var i = 0; i < data[key].length; i++) {
                a.push(data[key][i].team_name);
            }
            JsonString += k + " : " + JSON.stringify(a) + ",";
        }
        JsonString =  "{" + JsonString.substring(0, JsonString.length -1) + "}";
        var JsonObject = JSON.parse(JsonString);
        return JsonObject;
    }

    if (parseInt(level) === 2) {
        if (all_data.isTaotai) {
            $("div.second-list  ul  li:eq(1)").addClass("on").removeClass("name").siblings(this).removeClass("on").addClass("name");
        } else {
            $("div.second-list  ul  li:eq(0)").addClass("on").removeClass("name").siblings(this).removeClass("on").addClass("name");
            if (level != 4) {
                $("div.second-list  ul  li:eq(1)").addClass("hide_swiper");
            }
        }
    }

    $("div.second-list  ul  li").each(function (i, n) {
       $(this).on("click",function () {
           $("div.list_game:eq("+i+")").addClass("display_swiper").removeClass("hide_swiper")
               .siblings("div.list_game").addClass("hide_swiper").remove("display_swiper");
           $(this).addClass("on").removeClass("name").siblings(this).removeClass("on").addClass("name");
       });
    });

    /**
     *  2018世界杯部分 js 结束 -----------------------------------------------------
     */



    /**
     * 篮球部分
     */
    if (parseInt(level) === 3) {
        setInterval("TimeOutForCalendarInput()","300");
    }


});


function filterData(data, date) {
    var filter_data =  [];
    for (var i = 0; i < data.length; i++) {
        if (data[i].day === date) {
            filter_data.push(data[i]);
        }
    }
    return filter_data;
}

function  bkClickListMain(data) {
    data = data.reverse();
    var string = "";
    var status = "";
    var status_color = "";
    for (var i = 0; i < data.length; i++) {
        switch(parseInt(data[i].game_status)){
            case -1 :
                status = "已完赛";
                status_color = "right-r";
                break;
            case 0 :
                status = "未开赛";
                status_color = "right-r";
                break;
            default :
                status = "进行中";
                status_color = "right-f";
                break;
        }
        var month = data[i].game_month;
        var day = data[i].game_day;
        var game_time = data[i].game_time;
        var home_team_name = data[i].home_team_name[0];
        var away_team_name = data[i].away_team_name[0];
        var score;
        if (data[i].game_status == -1) {
            score = data[i].home_team_score + "-" + data[i].away_team_score;
        } else {
            score = "- -"
        }
        string += '<li class="main-f"><a href="../DetailsBk/event_case/scheid/'+data[i].id+'.html"><div class="time-f">' +
            '<span class="left-f">'+month+'月'+day+'日 '+data[i].week+'</span>'+
            '<span class="middle-f">'+game_time+'</span>'+
            '<span class="'+status_color+'">'+status+'</span>'+
            '</div><div class="name-f">' +
            '<span class="team-f"><img src="'+data[i].homeTeamLogo+'"></span>' +
            '<span class="team-name"><ul><li class="h-j">'+home_team_name+'</li><li class="b-f">'+score+'</li><li class="h-j">'+away_team_name+'</li></ul></span>' +
            '<span class="team-y"><img src="'+data[i].awayTeamLogo+'"></span>' +
            '</div></a></li>'
    }
    return string;
}


function TimeOutForCalendarInput() {
    var calendar_val =  $("#calendar_input");
    var calendar_temp = $("#calendar_value_temp");
    if (calendar_val.val() !== calendar_temp.val()) {
        var dom = document.getElementById("bkschedule");
        var data = document.getElementById("calendar_input").value;
        if (filterData(window.all_data, data).length > 0) {
            $("div.no-data").addClass("hide_swiper").removeClass("display_swiper");
            dom.innerHTML = bkClickListMain(filterData(window.all_data, data));
        } else {
            dom.innerHTML = "";
            $("div.no-data").addClass("display_swiper").removeClass("hide_swiper");
        }
        calendar_temp.val(calendar_val.val());
    }
}