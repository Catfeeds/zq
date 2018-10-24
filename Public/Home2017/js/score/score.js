$(function () {
    var Top = Cookie.getCookie('M_isTop');
    if (Top) {
        Top = Top.substring(0, Top.length - 1);
        var top_arr = Top.split(',');
        $.each(top_arr, function (i, val) {
            if (i == 0) {
                $(".liveList").prepend($('#scheid' + val));
            } else {
                $(".star_img_on:last").parents('.js-data').after($('#scheid' + val));
            }
            $('#scheid' + val).find('.star_img').addClass('star_img_on');
        });
    }
    var begin = setInterval(function () {
        var refreshTime=3000;
        if (typeof (refreshTime) == 'number' && (refreshTime >= 3000)) {
            scoreTan(refreshTime);
            goal(refreshTime);
        } else {
            scoreTan(5000);
            goal(5000);
        }
        clearInterval(begin);
    }, 3000);


    $(document).on('click', '.tips', function () {
        $(this).remove();
    });
    var date = new Date();
    var enterTime = date.getTime();
    var updateTime = date.getTime(date.setHours(10, 35, 00));
    setInterval(function () {
        // 定时刷新比赛分钟数
        $('.js_mach_time time').each(function (idx, ele) {
            var status = $(this).parents('.js-data').data('status');
            var goMins = parseInt($(this).text().replace("+", "")) + 1;
            switch (status)
            {
                case 1:
                    if (goMins > 45)
                        goMins = "45+";
                    if (goMins < 1)
                        goMins = "1";
                    break;
                case 3:
                    if (goMins > 90)
                        goMins = "90+";
                    if (goMins < 1)
                        goMins = "46";
                    break;
                case 4:
                    goMins = '90+';
            }

            $(this).text(goMins);
        });

        //检测是否需要刷新页面
        var nowTime = new Date().getTime();
        if (nowTime > updateTime && enterTime < updateTime)
        {
            window.location.reload();
        }
    }, 60000);
});

//定时器事件
function eventTime(obj, event, time) {
    var int = setInterval(function () {
        switch (event)
        {
            case 'remove':
                $(obj).remove();
                break;
            case 'background':
                $(obj).css('background', '');
                break;
            case 'color':
                $(obj).css('color', '');
        }
        clearInterval(int);
    }, time);
}

//弹窗、黄牌、比分实时数据更新
function scoreTan(time)
{
    if (time == undefined || time < 3000) {
        time = 3000;
    }
    $.ajax({
        type: 'post',
        url: '/ScoreInstant.html',
        dataType: 'json',
        success: function (data) {
            if (data['status'] == 1) {
                $.each(data.info, function (i, val) {
                    if (val[0] == 0)
                        return true;
                    var $this = $('#scheid' + val[0]);
                    //比分
                    if ($this.length == 0) {
                        return true;
                    }
                    //比赛状态
                    var oldStatus = $this.data('status');
                    if (val[1] != oldStatus) {
                        var statusStr = '';
                        var wentStatus = $this.find('.js_mach_time');
                        switch (val[1])
                        {
                            case '1':
                                if (!$this.find('.js-score').hasClass('mach_begin')) {
                                    $this.find('.js-score').addClass('mach_begin').html(val[2] + '-' + val[3]);
                                }
                                if (wentStatus.hasClass('mach_will_time')) {
                                    wentStatus.removeClass('mach_will_time');
                                    wentStatus.addClass('mach_begin_time');
                                }
                                var goTime = showGoTime(val[11], val[1]);
                                statusStr = '<time>' + goTime + '</time>' + '\'';
                                $this.find('.corner_box').html("全角[<span class='home_corner'>0</span>-<span class='away_corner'>0</span>]");
                                break;
                            case '3':
                                var goTime = showGoTime(val[11], val[1]);
                                statusStr = '<time>' + goTime + '</time>' + '\'';
                                break;
                            case '4':
                                statusStr = '<time>90+</time>' + '\'';
                                break;
                            case '2':
                                statusStr = '中场';
                                if (val[4] != '' && val[5] != '')
                                {
                                    var halfScore = ' (' + val[4] + ':' + val[5] + ')';
                                    $this.find('.mach_half').html(halfScore);
                                }
                                break;
                            case '-1':
                            case '-10':
                            case '-11':
                            case '-12':
                            case '-13':
                            case '-14':
                                statusStr = showStatus(val[1]);
                                break;
                        }
                        wentStatus.html(statusStr);
                        $this.attr('data-status', val[1]);

                        //移动到最后
                        if ((val[1] < 0))
                        {
                            var key = $('.js-data').last().data('key') + 1;
                            $this.attr('data-key', key);
                            if($this.find('.star_img').hasClass('star_img_on') == false){
                                 $this.appendTo('.liveList');
                            }
                        }
                    }
                    if (val[1] >= 1 && val[1] <= 4)
                    {
                        var score = val[2] + '-' + val[3];
                        var mach_begin = $this.find('.mach_begin').html().split('-');
                        if (mach_begin) {
                            if (val[2] != mach_begin[0] || val[3] != mach_begin[1]) {
                                //进球声音提示
                                    document.getElementById("sound_score").play();
                                
                                //进球弹窗提示
                                var html = '<div id="score' + val[0] + '" class="tips score_tips show">' +
                                        '<div class="t_left">' + $this.find('.match_name').html() + ' ' + showGoTime(val[11], val[1]) + '\'</div>' +
                                        '<div class="t_right">';
                                if (mach_begin[0] != val[2]) {
                                    $this.find('.homeTeamName').css('color', 'red');
                                    html += '<p style="color:red;">' + val[2] + ' ' + $this.find('.homeTeamName').html() + '<em></em></p>';
                                    html += '<p>' + val[3] + ' ' + $this.find('.guestTeamName').html() + '</p>';
                                } else if (mach_begin[1] != val[3]) {
                                    $this.find('.guestTeamName').css('color', 'red');
                                    html += '<p>' + val[2] + ' ' + $this.find('.homeTeamName').html() + '</p>';
                                    html += '<p style="color:red;">' + val[3] + ' ' + $this.find('.guestTeamName').html() + '<em></em></p>';
                                }
                                html += '</div></div>';
                                    $("#tips_box").append(html);
                                    //10秒后消除
                                    eventTime('#score' + val[0], 'remove', 10000);
                                $this.css('background', '#fff6d7');
                                eventTime($this, 'background', 20000);
                                eventTime('.homeTeamName,.guestTeamName', 'color', 20000);
                                $this.find('.js-score').html(val[2] + '-' + val[3]);
                            }
                        }
                        //红黄牌信息
                        var home_ycard = $this.find('#js-home-ycard').html();
                        var home_rcard = $this.find('#js-home-rcard').html();
                        var guest_ycard = $this.find('#js-guest-ycard').html();
                        var guest_rcard = $this.find('#js-guest-rcard').html();
                        if (home_rcard != val[6] || guest_rcard != val[7]) {
                            //红牌声音提示
                                document.getElementById("sound_card").play();
                            
                            //红牌弹窗提示

                            var html = '<div id="red' + val[0] + '" class="tips color_tips show">' +
                                    '<div class="t_left">' + $this.find('.match_name').html() + ' ' + showGoTime(val[11], val[1]) + '\'</div>' +
                                    '<div class="t_right">';
                            if (home_rcard != val[6]) {
                                $this.find('.homeTeamName').css('color', 'red');
                                html += '<p style="color:red;">' + val[2] + ' ' + $this.find('.homeTeamName').html() + ' <em class="red"></em></p>';
                                html += '<p>' + val[3] + ' ' + $this.find('.guestTeamName').html() + '</p>';
                            } else if (guest_rcard != val[7]) {
                                $this.find('.guestTeamName').css('color', 'red');
                                html += '<p>' + val[2] + ' ' + $this.find('.homeTeamName').html() + '</p>';
                                html += '<p style="color:red;">' + val[3] + ' ' + $this.find('.guestTeamName').html() + '<em class="red"></em></p>';
                            }
                            html += '</div></div>';
                                $("#tips_box").append(html);
                                //10秒后消除
                                eventTime('#red' + val[0], 'remove', 10000);
                            
                            $this.css('background', '#fff6d7');
                            eventTime($this, 'background', 20000);
                            eventTime('.homeTeamName,.guestTeamName', 'color', 20000);
                            if (home_rcard != val[6]) {
                                $this.find('#js-home-rcard').css('display', '').html(val[6]);
                            }
                            if (guest_rcard != val[7]) {
                                $this.find('#js-guest-rcard').css('display', '').html(val[7]);
                            }
                        }
                        if (home_ycard != val[8] && val[8] != 0) {
                            $this.find('#js-home-ycard').css('display', '').html(val[8]);
                        }
                        if (guest_ycard != val[9] && val[9] != 0) {
                            $this.find('#js-guest-ycard').css('display', '').html(val[9]);
                        }
                        //全角变化
                        var home_corner = $this.find('.home_corner').html();
                        var away_corner = $this.find('.away_corner').html();
                        if(home_corner!=val[12] && val[12] != 0){
                            $this.find('.home_corner').html(val[12]);
                        }
                        if(away_corner!=val[13] && val[13] != 0){
                            $this.find('.away_corner').html(val[13]);
                        }
                    }
                });
            }
        }
    });
    setTimeout("scoreTan(" + time + ")", time);
}

//赔率实时数据更新
function goal(time)
{
    $.ajax({
        type: 'post',
        url: '/goal.html',
        dataType: 'json',
        success: function (data) {
            if (data['status'] == 1) {
                $.each(data.info, function (i, val) {
                    if (i == 0)
                        return true;
                    var $this = $('#scheid' + i);
                    //比分
                    if ($this.length == 0) {
                        return true;
                    }
                    //亚盘数据更新
                    if ($this.find('.js-home-asian').html() != val[1]) {
                        $this.find('.odds').css('display', '');
                        var original = parseFloat($this.find('.js-home-asian').html());
                        var compare = parseFloat(val[1]);
                        if (original > compare) {
                            $this.find('.js-home-asian').css('background', '#31c786').html(val[1]);
                        } else {
                            $this.find('.js-home-asian').css('background', '#ff9494').html(val[1]);
                        }
                        eventTime('.js-home-asian', 'background', 5000);
                    }
                    if ($this.find('.js-all-asian').html() != val[0]) {
                        if(val[0]=='100'){
                            $this.find('.js-all-asian').html('封');
                        }else{
                            $this.find('.js-all-asian').html(val[0]);
                        }
                    }
                    if ($this.find('.js-away-asian').html() != val[2]) {
                        var original = parseFloat($this.find('.js-away-asian').html());
                        var compare = parseFloat(val[2]);
                        if (original > compare) {
                            $this.find('.js-away-asian').css('background', '#31c786').html(val[2]);
                        } else {
                            $this.find('.js-away-asian').css('background', '#ff9494').html(val[2]);
                        }
                        eventTime('.js-away-asian', 'background', 5000);
                    }
                    //大小数据更新
                    if ($this.find('.js-home-ball').html() != val[7]) {
                        var original = parseFloat($this.find('.js-home-ball').html());
                        var compare = parseFloat(val[7]);
                        if (original > compare) {
                            $this.find('.js-home-ball').css('background', '#31c786').html(val[7]);
                        } else {
                            $this.find('.js-home-ball').css('background', '#ff9494').html(val[7]);
                        }
                        eventTime('.js-home-ball', 'background', 5000);
                    }
                    if ($this.find('.js-all-ball').html() != val[6]) {
                        if(val[6]=='100'){
                            $this.find('.js-all-ball').html('封');
                        }else{
                            $this.find('.js-all-ball').html(val[6]);
                        }
                    }
                    if ($this.find('.js-away-ball').html() != val[8]) {
                        var original = parseFloat($this.find('.js-home-ball').html());
                        var compare = parseFloat(val[8]);
                        if (original > compare) {
                            $this.find('.js-away-ball').css('background', '#31c786').html(val[8]);
                        } else {
                            $this.find('.js-away-ball').css('background', '#ff9494').html(val[8]);
                        }
                        eventTime('.js-away-ball', 'background', 5000);
                    }

                });
            }
        }
    });
    setTimeout("goal(" + time + ")", time);
}

//收藏置顶
function zhiDing(scheid, othis) {
    var siTop = Cookie.getCookie('M_isTop');
    if ($(othis).hasClass('star_img_on')) {
        $key = $(othis).parents('.js-data').data('key');
        var change = false;
        $('.js-data').each(function () {
            $this = $(this);
            if (!$this.find('.star_img').hasClass('star_img_on')) {
                if ($key < $this.data('key')) {
                    $this.before($(othis).parents('.js-data'));
                    change = true;
                    return false;
                }
            }
        });
        $(othis).removeClass('star_img_on');
        var str = scheid + ',';
        Cookie.setCookie("M_isTop", siTop.replace(new RegExp(str), ''));
    } else {
        var pdata = $(othis).parents('.js-data');
        var top = '';
        var flag = false;
        if ($('.star_img').hasClass('star_img_on')) {
            $(".star_img_on").each(function (i, v) {
                var tdata = $(this).parents('.js-data');
                if (pdata.data('time') > tdata.data('time')) {
                    tdata.after(pdata);
                    flag = true;
                }
            });
            if (!flag) {
                $('.liveList').prepend(pdata);
            }
        } else {
            $('.liveList').prepend(pdata);
        }
        $(othis).addClass('star_img_on');
        $(".star_img_on").each(function (i, v) {
            top += $(this).parents('.js-data').data('id') + ',';
        });
        Cookie.setCookie('M_isTop', top);
    }
}

//比赛进行多长时间
function showGoTime(startTime, status)
{
    var t2 = new Date(startTime.substring(0, 4), startTime.substring(4, 6) - 1, startTime.substring(6, 8), startTime.substring(8, 10), startTime.substring(10, 12), startTime.substring(12, 14));
    var goMins = Math.floor((new Date() - t2) / 60000);
    switch (status)
    {
        case '1':
            if (goMins > 45)
                goMins = "45+";
            if (goMins < 1)
                goMins = "1";
            break;
        case '3':
            goMins += 46;
            if (goMins > 90)
                goMins = "90+";
            if (goMins < 1)
                goMins = "46";
            break;
        case '4':
            goMins = '90+';
            break;
    }

    return goMins;
}

//比赛进行多长时间
function showStatus(game_status)
{
    var arr = new Array();
    arr['0'] = '未开';
    arr['1'] = '上半场';
    arr['2'] = '中场';
    arr['3'] = '下半场';
    arr['4'] = '加时';
    arr['-1' ] = '完场';
    arr['-10'] = '取消';
    arr['-11'] = '待定';
    arr['-12'] = '腰斩';
    arr['-13'] = '中断';
    arr['-14'] = '推迟';
    return arr[game_status];
}