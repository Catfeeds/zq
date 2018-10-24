$(function(){
    //给已经竞猜过的赛事添加已选样式
    if (gambleList != null)
    {
        for (var i = 0; i < gambleList.length; i++)
        {
            $('tr[gameid="'+gambleList[i]['game_id']+'"]')
                .find('ul[playTypeVal="'+gambleList[i]['play_type']+'"]')
                .find('.gamble[choseSideVal="'+gambleList[i]['chose_side']+'"]')
                .addClass('on')
        }
    }

    //赛事赛选
    $(".eventChoose a").click(function(e) {
        if ($(".box-list").is(":hidden")) {
            $(".box-list").stop().fadeIn();
            e?e.stopPropagation():event.cancelBubble = true;
        }
    });
    $(".box-list").click(function(e) {
        e?e.stopPropagation():event.cancelBubble = true;
    });
    //显示全部
    $('#showAll').click(function(){
        $('#game_table tr').removeClass('hidden');
        $('.menu_list input').attr('checked',true);
        getHiddenNum();
    })
    //全选
    $('#checkAll').on('click',function(){
        $('.menu_list input').attr('checked',true);
    })
    //反选
    $('#removeAll').on('click',function(){
        $(".menu_list input").each(function () {  
            $(this).attr("checked", !$(this).attr("checked"));  
        }); 
    })
    //关闭并执行隐藏/显示赛事
    $(".btn-con .closed").click(function() {
        doHideGame();
    });
    $(document).click(function() {
        if($(".box-list").css('display') == 'block'){
            doHideGame();
        }
    });
    //执行隐藏赛事
    function doHideGame(){
        var chknum = $(".menu_list input:checked").size();//选项总个数 
        if(chknum == 0){
            showMsg('请选择至少一个赛事！',0,'error');
            return;
        }
        $(".menu_list input").each(function () {  
            var union_id = $(this).val();
            if($(this).is(':checked') == false){
                $('#game_table tr[unionid='+union_id+']').addClass('hidden');
            }else{
                $('#game_table tr[unionid='+union_id+']').removeClass('hidden');
            }
        }); 
        getHiddenNum();
        $(".box-list").stop().fadeOut();
    }
    //计算已隐藏的数量
    function getHiddenNum(){
        var hiddenNum = $('#game_table tr[class="hidden"]').length;
        $('.hideMac span').text(hiddenNum);
        $(document).scrollTop(1); 
        $(document).scrollTop(0);
    }
    
    //简繁体切换
    $('.radio-inline input').click(function() {
        var newLang = $(this).val();
        var oldLang = Cookie.getCookie('lang');

        if (newLang != oldLang)
        {
            $('.guess_content .hidden').removeClass('hidden').siblings('em').addClass('hidden');
            Cookie.setCookie('lang',newLang,30);
        }
    });

    //定时更新赛程比分、状态
    setInterval(function(){
        $.ajax({  
            type:'get',  
            url : DOMAIN_URL+'/home/pcdata/changeTwo',  
            dataType : 'jsonp',  
            jsonp:"jsoncallback",  
            success  : function(msg) { 
                if(msg.status == 0) return;
                var data = msg.data;
                for (var k in data)
                {
                    var newStatus = data[k][1];
                    if (newStatus == 0) continue;
                    var game = $('tr[gameid="'+data[k][0]+'"]');
                    if (game.length != 0)
                    {
                        //比分
                        if (newStatus > -10)
                        {
                            var score = data[k][2] +'-'+ data[k][3];
                            game.find('.all_score').text(score).removeClass('blue').addClass('red');

                            if (newStatus > 2 && data[k][4] != '' && data[k][5] != '')
                            {
                                var halfScore = ' (' + data[k][4] + '-' + data[k][5] + ')';
                                game.find('.half_score').text(halfScore);
                            }
                        }

                        //状态
                        var oldStatus = game.find('.status').attr('status');

                        if (newStatus != oldStatus)
                        {
                            var statusStr = '';
                            switch (newStatus)
                            {
                                case '1':
                                case '3':
                                    var goTime = showGoTime(data[k][11],newStatus);
                                    statusStr = '<time>'+goTime+'</time>'+'<img src="/Public/Home/images/common/in.gif">';
                                break;
                                case '2':  statusStr = '中场';   break;
                                case '4':  statusStr = '加时';   break;
                                case '-1': statusStr = '完场';   break;
                                case '-10': statusStr = '取消';  break;
                                case '-11': statusStr = '待定';  break;
                                case '-12': statusStr = '腰斩';  break;
                                case '-13': statusStr = '中断';  break;
                                case '-14': statusStr = '推迟';  break;
                            }

                            var wentStatus = game.find('.status').find('.wentStatus');
                            if (newStatus != 0)
                            {
                                wentStatus.addClass('red');
                            }

                            wentStatus.html(statusStr);
                            game.find('.status').attr('status',newStatus);

                            //移动到最后
                            if (newStatus < 0)
                            {
                                $('#game_table tr:last').after(game);
                            }
                        }
                    }
                }
            },  
        });
    },3000);

    //比赛进行多长时间
    function showGoTime(startTime,status)
    {
        var t      = startTime.split(",");
        var t2     = new Date(t[0], t[1], t[2], t[3], t[4], t[5]);
        var goMins = Math.floor((new Date() - t2) / 60000);

        switch (status)
        {
            case '1':
                if (goMins > 45)  goMins = "45+";
                if (goMins < 1)   goMins = "1";
            break;
            case '3':
                goMins += 46;
                if (goMins > 90)  goMins = "90+";
                if (goMins < 1)   goMins = "46";
            break;
        }

        return goMins;
    }

    //定时更新指数盘口
    setInterval(function(){
        $.ajax({  
            type:'get',  
            url : DOMAIN_URL+'/home/pcdata/fbbetodds',  
            dataType : 'jsonp',  
            jsonp:"jsoncallback",  
            success  : function(msg) { 
                if(msg.status == 0) return;
                var data = msg.data;
                for(var k in data)
                {
                    var gameid = data[k]['game_id'];
                    var oldOdds = $('tr[gameid="'+gameid+'"]').find('.odds_change');
                    if (oldOdds.length != 0)
                    {
                        var home_odds    = data[k]['home_odds'];
                        var draw_odds    = data[k]['draw_odds'];
                        var away_odds    = data[k]['away_odds'];
                        var let_exp      = data[k]['let_exp'];
                        var home_letodds = data[k]['home_letodds'];
                        var draw_letodds = data[k]['draw_letodds'];
                        var away_letodds = data[k]['away_letodds'];

                        var _home_odds    = oldOdds.find('.home_odds').text();
                        var _draw_odds    = oldOdds.find('.draw_odds').text();
                        var _away_odds    = oldOdds.find('.away_odds').text();
                        var _let_exp      = oldOdds.find('.let_exp').text();
                        var _home_letodds = oldOdds.find('.home_letodds').text();
                        var _draw_letodds = oldOdds.find('.draw_letodds').text();
                        var _away_letodds = oldOdds.find('.away_letodds').text();
                        //不等于时改变
                        if(home_odds != _home_odds)
                        {
                            fc0 = home_odds > _home_odds  ? 'handCpRed' : 'handCpGreen';
                            oldOdds.find('.home_odds').text(home_odds).addClass(fc0).delay(20000).queue(function() 
                            {
                               $(this).removeClass('handCpRed').removeClass('handCpGreen');
                               $(this).dequeue();
                            });
                        }
                        if(draw_odds != _draw_odds)
                        {
                            fc1 = draw_odds > _draw_odds ? 'handCpRed' : 'handCpGreen';
                            oldOdds.find('.draw_odds').text(draw_odds).addClass(fc1).delay(20000).queue(function() 
                            {
                               $(this).removeClass('handCpRed').removeClass('handCpGreen');
                               $(this).dequeue();
                            });
                        }
                        if(away_odds != _away_odds)
                        {
                            fc2 = away_odds > _away_odds ? 'handCpRed' : 'handCpGreen';
                            oldOdds.find('.away_odds').text(away_odds).addClass(fc2).delay(20000).queue(function() 
                            {
                               $(this).removeClass('handCpRed').removeClass('handCpGreen');
                               $(this).dequeue();
                            });
                        }
                        if(home_letodds != _home_letodds)
                        {
                            fc3 = home_letodds > _home_letodds ? 'handCpRed' : 'handCpGreen';
                            oldOdds.find('.home_letodds').text(home_letodds).addClass(fc3).delay(20000).queue(function() 
                            {
                               $(this).removeClass('handCpRed').removeClass('handCpGreen');
                               $(this).dequeue();
                            });
                        }
                        if(draw_letodds != _draw_letodds)
                        {
                            fc4 = draw_letodds > _draw_letodds ? 'handCpRed' : 'handCpGreen';
                            oldOdds.find('.draw_letodds').text(draw_letodds).addClass(fc4).delay(20000).queue(function() 
                            {
                               $(this).removeClass('handCpRed').removeClass('handCpGreen');
                               $(this).dequeue();
                            });
                        }
                        if(away_letodds != _away_letodds)
                        {
                            fc5 = away_letodds > _away_letodds ? 'handCpRed' : 'handCpGreen';
                            oldOdds.find('.away_letodds').text(away_letodds).addClass(fc5).delay(20000).queue(function() 
                            {
                               $(this).removeClass('handCpRed').removeClass('handCpGreen');
                               $(this).dequeue();
                            });
                        }
                        if(let_exp != '')
                        {
                            oldOdds.find('.let_exp').html(let_exp);
                        }
                    }
                }
            }
        });
    },3000);

    //切换价格选中
    $('.price_ul li .odd').click(function(e) {
        $(this).addClass('on').parents('li').siblings().find('.odd').removeClass('on'); 
    });

    //竞猜点击
    $('.gamble').click(function() {
        //判断登陆
        var This = $(this);
        var userId = $("input[name='userId']").val();
        if (userId == '')
        {
            $('.myLogin').modal('show');
        }
        else
        {
            //判断赛程状态
            var gameStatus = This.parents('td').siblings('.status').attr('status');
            if (gameStatus != 0)
            {
                var msg = '';
                switch(gameStatus)
                {
                    case '1':
                    case '2':
                    case '3':
                    case '4': msg = '开始';   break;
                    case '-1': msg = '完场';  break;
                    case '-10': msg = '取消'; break;
                    case '-11': msg = '待定'; break;
                    case '-12': msg = '腰斩'; break;
                    case '-13': msg = '中断'; break;
                    case '-14': msg = '推迟'; break;
                }
                showMsg('赛事已'+msg+'，不能参与竞猜了！',0,'error');
                return;
            }

            //显示竞猜框
            var gameId         = This.parents('tr').attr('gameid');
            var playTypeVal    = This.parents('ul').attr('playTypeVal');
            var playTypeText   = playTypeVal == 2 ? '不让球' : '让球';
            var choseSideVal   = This.attr('choseSideVal');
            var handcpVal      = This.parent().siblings('.rangq').text();
            switch(choseSideVal)
            {
            	case '1' :var choseSideText  = playTypeText+'胜 '+handcpVal;break;
            	case '0' :var choseSideText  = playTypeText+'平 '+handcpVal;break;
            	case '-1':var choseSideText  = playTypeText+'负 '+handcpVal;break;
            } 
            var odds           = This.find('span').text();
            var union_name     = This.parents('tr').find('.union_name').find('.hidden').siblings().text();
            var home_team_name = This.parents('tr').find('.home_team_name').find('.hidden').siblings().text();
            var away_team_name = This.parents('tr').find('.away_team_name').find('.hidden').siblings().text();
            var bet_code       = This.parents('tr').find('.bet_code').text();
            var team_name      = home_team_name+" VS "+away_team_name; 

            if (odds == '' || handcpVal == '')
            {
                showMsg('暂未开启竞猜',0,'error');
                return;
            }
            var mySelect = $('.mySelect');
            mySelect.find('.game_id').val(gameId);
            mySelect.find('.playType').attr('value',playTypeVal).text(playTypeText);
            mySelect.find('.choseSide').attr('value',choseSideVal).text(choseSideText);
            mySelect.find('._odds').attr('value',odds).text(odds);
            mySelect.find('.union_name').text(union_name);
            mySelect.find('.team_name').text(team_name);
            mySelect.find('.bet_code').text(bet_code+' ');
            mySelect.modal('show');
        }
    });

    //确定竞猜
    $('#makeGamble').click(function() {
        var mySelect = $('.mySelect');
        var param           = {};
        param['game_id']    = mySelect.find('.game_id').val();
        param['play_type']  = mySelect.find('.playType').attr('value');
        param['chose_side'] = mySelect.find('.choseSide').attr('value');
        param['is_impt']    = $("input[name='point']:checked").val();
        param['desc']       = mySelect.find('.desc').val();
        param['tradeCoin']  = mySelect.find('.tradeCoin').find('.on').attr('value');

        $.ajax({
            type:'post',
            url:"/gamble.html",
            data:param,
            dataType:'json',
            beforeSend:function(){
                $("#makeGamble").attr('disabled','disabled').text("正在提交...");
            },
            success:function(data){
                if (data.status)
                {
                    showMsg('提交成功，感谢您的参与！');
                    mySelect.modal('hide');

                    $('tr[gameid="'+param['game_id']+'"]')
                        .find('ul[playTypeVal="'+param['play_type']+'"]')
                        .find('.gamble[choseSideVal="'+param['chose_side']+'"]')
                        .addClass('on');

                    //更新剩余竞猜次数
                    mySelect.find('.normLeftTimes').text(data.info.normLeftTimes);
                    mySelect.find('.desc').val(''); //清空分析内容
                    mySelect.find('.tradeCoin').find('.on').removeClass('on');//回到默认选择
                    mySelect.find('.default-coin').addClass('on');
                }
                else
                {
                    switch(data.info)
                    {
                        case -1:
                            $('.mySelect').modal('hide');
                            $('.myLogin').modal('show');
                        break;
                        default:
                            showMsg(data.info,0,'error');
                        break;
                    }
                }
            },
            complete:function(){
                $("#makeGamble").removeAttr('disabled').text("确定");
            },
        })
    });

    var date       = new Date();
    var enterTime  = date.getTime();
    var updateTime = date.getTime(date.setHours(10,32,10));
    var reloadTime = 0;

    //定时任务
    setInterval(function(){
        // 定时刷新比赛分钟数
        $('.status time').each(function(idx,ele){
            var status = $(this).parents('.status').attr('status');
            var goMins = parseInt($(this).text().replace("+","")) + 1;

            switch (status)
            {
                case '1':
                    if (goMins > 45)  goMins = "45+";
                    if (goMins < 1)   goMins = "1";
                break;
                case '3':
                    if (goMins > 90)  goMins = "90+";
                    if (goMins < 1)   goMins = "46";
                break;
            }

            $(this).text(goMins);
        });

        //检测是否需要刷新页面
        var nowTime = new Date().getTime();
        if (nowTime > updateTime && enterTime < updateTime)
        {
            window.location.reload();
        }
        reloadTime += 1;
        //30分钟自动刷新页面
        if(reloadTime % 30 == 0){
            window.location.reload();
        }
    },60000);
});