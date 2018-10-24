$(function () {
    var level = $("input[name=level]").val();
    var name = $("input[name=name]").val();
    console.log(level);
    console.log(name);
    getData();
    if (parseInt(level) !== 3) {
        getShooter();
    }
    var all_data;
    function getData() {
        $.ajax({
            url:'/Schedulelist/getRankData',
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
                all_data = data;
                if (parseInt(level) === 1) {
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    $("div.leaguerank-rank").html(leaguerankData(data.data))
                } else if (parseInt(level) === 2) {
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    $("div.champion-rank").html(championrankData(data.data));
                } else if (parseInt(level) === 3) {
                    if (name === "nba") {
                        $("span.leaguesIcon").html('<img src="'+data.nba.iconUrl+'">');
                        $("div.second-nav").html(bkSecondNav(name));
                        $("div.bk-rank-shooter").html(bkNbaTeamData(data.nba.east));
                    } else if (name === "cba") {
                        $("span.leaguesIcon").html('<img src="'+data.cba.iconUrl+'">');
                        $("div.second-nav").html(bkSecondNav(name));
                        $("div.bk-rank-shooter").html(bkNbaTeamData(data.cba.integral));
                    }
                    // $("div.bk-rank-shooter").html(bkNbaTeamData(data.west));
                } else if (parseInt(level) === 4) {
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    $("div.champion-rank").html(championrankData(data.data));
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


    function getShooter() {
        $.ajax({
            url:'/Schedulelist/getShooter',
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
                if (parseInt(level) === 1) {
                    $("div.leaguerank-shooter").html(leagueshooterrankData(data));
                } else if (parseInt(level) === 2) {
                    $("div.champion-shooter").html(leagueshooterrankData(data));
                } else if (parseInt(level) === 4) {
                    if (data.length === 0) {
                        $("div.champion-shooter").html('<div class="no-data"><img src="/Public/Mobile/images/schedule/ic_normal_style_data.png" alt=""><p class="no-data-p">暂时没有数据</p></div>');
                    } else {
                        $("div.champion-shooter").html(leagueshooterrankData(data));
                    }
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


    function leaguerankData(data) {
        var string = "";
        string += '<div class="grouping clearfix"><div class="right-s">' +
            '<!--<span>数据截止到03月15日</span>--></div></div>' +
            '<div class="list clearfix"><ul>' +
            '<li class="ranking-s remove">排名</li>' +
            '<li class="team-s league-team">球队</li>' +
            '<li class="session-f league-win-lose">胜平负</li>' +
            '<li class="team-a">积分</li>' +
            '</ul></div><div class="main-s"><ul>';
        for (var i = 0; i < data.length; i++) {
            var color = "";
            switch (i) {
                case 0:
                    color = "";
                    break;
                case 1 :
                    color = "orange";
                    break;
                case 2 :
                    color = "yellow";
                    break;
                default:
                    color = "gray";
                    break;
            }
            string += '<li class="m-s line">' +
                '<span class="number-q '+color+'">'+data[i].rank+'</span>' +
                '<span class="nteam-q league-name">'+data[i].team_name+'</span>' +
                '<span class="game-a win-lose">'+data[i].win+'/'+data[i].draw+'/'+data[i].lose+'</span>' +
                '<span class="success-q point">'+data[i].int+'</span>' +
                '</li>';
        }
        string += "</ul></div>";
        return string;
    }


    function championrankData(data) {
        var string = "";
        for (var team_key in data) {
            string += '<div class="grouping clearfix"><div class="left-s">' +
                '<span class="one-s">'+team_key+'</span>' +
                '<span class="two-s">组</span>' +
                '</div>' +
                '<div class="right-s"><!--<span>数据截止到03月15日</span>--></div>' +
                '</div>' +
                '<div class="list clearfix"><ul>' +
                '<li class="ranking-s rankteam">排名</li><li class="team-s gameteam">球队</li><li class="success-s">胜</li><li class="flat-s">平</li><li class="lose-s">负</li><li class="integral-s">积分</li>' +
                '</ul></div>' +
                '<div class="main-s"><ul>';

            for (var i = 0; i < data[team_key].length; i++) {
                var color = "";
                switch (i) {
                    case 1 :
                        color = "orange";
                        break;
                    case 2 :
                        color = "yellow";
                        break;
                    case 3:
                        color = "gray";
                        break;
                }
                string += '<li class="m-s line">' +
                    '<span class="number-q '+color +'  numberteam">'+(i+1)+'</span>' +
                    '<span class="nteam-x namegame">'+data[team_key][i].team_name+'</span>' +
                    '<span class="success-x">'+data[team_key][i].win+'</span>' +
                    '<span class="flat-q">'+data[team_key][i].draw+'</span>' +
                    '<span class="lose-q">'+data[team_key][i].lose+'</span>' +
                    '<span class="integral-q">'+data[team_key][i].int+'</span></li>';
            }
                string += '</ul></div>';
        }
        return string;
    }


    function leagueshooterrankData(data) {
        var string = "";
        string += '<div class="grouping clearfix"><div class="right-s"><!--<span>数据截止到03月15日</span>--></div></div>' +
            '<div class="list clearfix">' +
            '<ul><li class="ranking-s">排名</li><li class="team-s">球员</li><li class="session-f">球队</li><li class="team-a">进球数</li></ul>' +
            '</div>' +
            '<div class="main-s">' +
            '<ul>';
        for (var i = 0; i < data.length; i++) {
            var color = "";
            switch (i) {
                case 0:
                    color = "";
                    break;
                case 1 :
                    color = "orange";
                    break;
                case 2 :
                    color = "yellow";
                    break;
                default:
                    color = "gray";
                    break;
            }
            string += '<li class="m-s line">' +
                '<span class="number-q '+color+'">'+data[i].rank+'</span>' +
                '<span class="nteam-q r">'+data[i].player_name+'</span>' +
                '<span class="game-a">'+data[i].team_name+'</span>' +
                '<span class="success-q">'+data[i].val+'</span></li>';
        }
        string += '</ul></div>';
        return string;
    }

    changeClick($("div.leaguerank-rank"), $("div.leaguerank-shooter"));
    changeClick($("div.champion-rank"), $("div.champion-shooter"));

    function changeClick(rankDom, shooterDom) {
        $("#integral").on("click", function() {
            rankDom.addClass("display_swiper").removeClass("hide_swiper");
            shooterDom.addClass("hide_swiper").removeClass("display_swiper");
            $(this).parent().addClass("on").removeClass("name").siblings("li").removeClass("on").addClass("name");
        }).click();

        $("#shooter").on("click", function() {
            rankDom.addClass("hide_swiper").removeClass("display_swiper");
            shooterDom.addClass("display_swiper").removeClass("hide_swiper");
            $(this).parent().addClass("on").removeClass("name").siblings("li").removeClass("on").addClass("name");
        });
    }

    function bkSecondNav(name) {
        var string = "";
        if (name === "nba") {
            string = '<div class="second-list-t"><ul><li class="on"><a id="east">东部排名</a></li><li class="name"><a id="west">西部排名</a></li><li class="name"><a id="points">得分榜</a></li><li class="name"><a id="assists">助攻榜</a></li><li class="name"><a id="rebound">篮板榜</a></li></ul></div>';
            string += '<div class="list"><ul><li class="ranking">排名</li><li class="team">球员</li><li class="session-l">胜负</li><li class="integral">胜率</li></ul></div>';
        } else if (name === "cba") {
            string = '<div class="second-list"><ul><li class="on"><a id="cba-integral">胜率榜</a></li><li class="name"><a id="cba-points">得分榜</a></li><li class="name"><a id="cba-assists">助攻榜</a></li><li class="name"><a id="cba-rebound">篮板榜</a></li></ul></div>';
            string += '<div class="list"><ul><li class="ranking">排名</li><li class="team">球员</li><li class="session-l">胜负</li><li class="integral">胜率</li></ul></div>';
        }
        $("div.second-nav").html(string);
    }


    function bkNbaTeamData(data) {
        console.log(data);
        var string = "";
        string += '<section class="matchCon"><ul>';
        for (var i = 0; i < data.length; i ++) {
            var color = "";
            switch (i) {
                case 0:
                    color = "one-f";
                    break;
                case 1 :
                    color = "two-f";
                    break;
                case 2 :
                    color = "three-f";
                    break;
                default:
                    color = "four-f";
                    break;
            }
            string +='<li><span class="serial-number '+color+'">'+data[i].rank+'</span>' +
                '<span class="pho-f"><img src="'+data[i].team_logo+'" height="24" width="24"></span>' +
                '<span class="team-name-f">'+data[i].team_name+'</span>' +
                '<span class="number-left-f">'+data[i].win +'/' + data[i].lose+'</span>' +
                '<span class="number-right-f">'+data[i].win_ratio+'%'+'</span></li>';
        }
        string += '</ul></section>';
        return string;
    }

    function bkNbaPlayerData(data) {
        var string = "";
        string += '<section class="matchCon"><ul>';
        for (var i = 0; i < data.length; i ++) {
            var color = "";
            switch (i) {
                case 0:
                    color = "one-f";
                    break;
                case 1 :
                    color = "two-f";
                    break;
                case 2 :
                    color = "three-f";
                    break;
                default:
                    color = "four-f";
                    break;
            }
            string +='<li><span class="serial-number '+color+'">'+data[i].rank+'</span>' +
                '<span class="pho-f"><img src="'+data[i].team_logo+'"  height="24" width="24"></span>' +
                '<span class="team-name-f">'+data[i].player_name+'</span>' +
                '<span class="number-left-f">'+data[i].team_name+'</span>' +
                '<span class="number-right-f">'+data[i].val+'</span></li>';
        }
        string += '</ul></section>';
        return string;
    }

    if (parseInt(level) === 3) {
        bkClickFunction($("a#east"), all_data.nba.east, false);
        bkClickFunction($("a#west"), all_data.nba.west, false);
        bkClickFunction($("a#points"), all_data.nba.points, true);
        bkClickFunction($("a#assists"), all_data.nba.assists, true);
        bkClickFunction($("a#rebound"), all_data.nba.rebound, true);

        bkClickFunction($("a#cba-integral"), all_data.cba.integral, false);
        bkClickFunction($("a#cba-points"), all_data.cba.points, true);
        bkClickFunction($("a#cba-assists"), all_data.cba.assists, true);
        bkClickFunction($("a#cba-rebound"), all_data.cba.rebound, true);
    }


    function bkClickFunction(clickDom, data, type) {
        clickDom.on("click", function() {
            if (type) {
                $("div.bk-rank-shooter").html(bkNbaPlayerData(data));
                $("li.session-l").html("球队").siblings("li.integral").html('均场');
            } else {
                $("div.bk-rank-shooter").html(bkNbaTeamData(data));
                $("li.session-l").html("胜负").siblings("li.integral").html('胜率');
            }
            $(this).parents("li").addClass("on").removeClass("name").siblings("li").removeClass("on").addClass("name");
        });
    }


});





