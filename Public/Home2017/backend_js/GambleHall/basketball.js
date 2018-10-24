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

    //竞猜点击
    $('.gamble').click(function() {
        //判断登陆
        var userId = $("input[name='userId']").val();
        if(userId == ''){
            $('.myLogin').modal('show');
            return;
        }
        //判断赛程状态
        var gameStatus = $(this).parents('td').siblings('.status').attr('status');

        if (gameStatus != 0)
        {
            var msg = '';
            switch(gameStatus)
            {
                case '1':
                case '2':
                case '50':
                case '3':
                case '4':
                case '5':
                case '6': msg = '开始';   break;
                case '-1': msg = '完场';  break;
                case '-10': msg = '取消'; break;
                case '-2': msg = '待定'; break;
                case '-12': msg = '腰斩'; break;
                case '-13': msg = '中断'; break;
                case '-14': msg = '推迟'; break;
            }
            showMsg('赛事已'+msg+'，不能参与竞猜了！',0,'error');
            return;
        }
        //显示竞猜框
        var gameId         = $(this).parents('tr').attr('gameid');
        var playTypeVal    = $(this).parents('ul').attr('playTypeVal');
        switch(playTypeVal)
        {
            case '1': playTypeText = '全场让球';   break;
            case '-1': playTypeText = '全场大小';  break;
            case '2': playTypeText = '半场让球'; break;
            case '-2': playTypeText = '半场大小'; break;
        }

        var choseSideVal  = $(this).attr('choseSideVal');
        switch(playTypeVal)
        {
            case '1':
            case '2': var choseSideText = choseSideVal == 1 ? '主' : '客';  break;
            case '-1':
            case '-2': var choseSideText = choseSideVal == 1 ? '大' : '小';  break;
        }

        var odds          = $(this).find('span').text();
        var handcpVal     = $(this).parent().siblings('.yapan').attr('handcpVal');
        var handcpText    = $.trim($(this).parent().siblings('.yapan').text());

        if (odds == '' || handcpVal == '')
        {
            showMsg('暂未开启竞猜',0,'error');
            return;
        }
        var union_name     = $(this).parents('tr').find('.union_name').find('.hidden').siblings().text();
        var home_team_name = $(this).parents('tr').find('.home_team_name').find('.hidden').siblings().text();
        var away_team_name = $(this).parents('tr').find('.away_team_name').find('.hidden').siblings().text();
        var team_name      = home_team_name+" VS "+away_team_name; 

        var mySelect = $('.mySelect');
        mySelect.find('.game_id').val(gameId);
        mySelect.find('.playType').attr('value',playTypeVal).text(playTypeText);
        mySelect.find('.choseSide').attr('value',choseSideVal).text(choseSideText);
        mySelect.find('._odds').attr('value',odds).text(odds);
        mySelect.find('.handcp').attr('value',handcpVal).text(handcpText);
        mySelect.find('.union_name').text(union_name);
        mySelect.find('.team_name').text(team_name);
        mySelect.modal('show');
    });

    //切换价格选中
    $('.price_ul li .odd').click(function(e) {
        $(this).addClass('on').parents('li').siblings().find('.odd').removeClass('on'); 
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
        param['gameType']   = 2;
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
    function GetDateStr(AddDayCount) {
        var date = new Date();
        date.setDate(date.getDate()+AddDayCount);//获取AddDayCount天后的日期
        var month = date.getMonth() + 1;
        var strDate = date.getDate();
        if (month >= 1 && month <= 9) {
            month = "0" + month;
        }
        if (strDate >= 0 && strDate <= 9) {
            strDate = "0" + strDate;
        }
        var currentdate = date.getFullYear() + month + strDate;
        return currentdate;
    }
    if(new Date().getHours() < 16){
        var dateStr = GetDateStr(-1);
    }else{
        var dateStr = GetDateStr(0);
    }
    //初始化最新指数盘口
    $.ajax({  
        type:'get',  
        url : DOMAIN_URL+'/Home/Pcdata/bkodds',  
        dataType : 'jsonp',  
        jsonp:"jsoncallback",  
        success  : function(msg) { 
            if(msg.status == 0) return;
            var data = msg.data;
            updateOdds(data);
        }
    })

    //定时更新指数
    setInterval(function(){
        $.ajax({  
            type:'get',  
            url : DOMAIN_URL+'/Home/Pcdata/bkodds',  
            dataType : 'jsonp',  
            jsonp:"jsoncallback",  
            success  : function(msg) { 
                if(msg.status == 0) return;
                var data = msg.data;
                updateOdds(data);
            }
        })
    },3000);

    //更新指数盘口处理
    function updateOdds(data)
    {
        $.each(data,function(k,v){
            var gameid = k;
            //var v = ["0.88", "-5", "0.82", "0.91", "167", "0.85", "1.3", "3.24"];
            var oldOdds = $('#game_table tr[gameid='+gameid+']').find('.odds_change');
            if(oldOdds.length > 0)
            {
                var fsw_exp_home   = oldOdds.find('.fsw_exp_home').text();
                var fsw_exp        = oldOdds.find('.fsw_exp').attr('handcpVal');
                var fsw_exp_away   = oldOdds.find('.fsw_exp_away').text();
                var fsw_total_home = oldOdds.find('.fsw_total_home').text();
                var fsw_total      = oldOdds.find('.fsw_total').attr('handcpVal');
                var fsw_total_away = oldOdds.find('.fsw_total_away').text();
                //赔率变化
                if(fsw_exp_home != v[0]){
                    fc0 = fsw_exp_home < v[0] ? 'handCpRed' : fsw_exp_home > v[0] ? 'handCpGreen' : '';
                    oldOdds.find('.fsw_exp_home').text(v[0]).addClass(fc0).delay(20000).queue(function() 
                    {
                       $(this).removeClass('handCpRed').removeClass('handCpGreen');
                       $(this).dequeue();
                    });
                }
                if(fsw_exp_away != v[2]){
                    fc1 = fsw_exp_away < v[2] ? 'handCpRed' : fsw_exp_away > v[2] ? 'handCpGreen' : '';
                    oldOdds.find('.fsw_exp_away').text(v[2]).addClass(fc1).delay(20000).queue(function() 
                    {
                       $(this).removeClass('handCpRed').removeClass('handCpGreen');
                       $(this).dequeue();
                    });
                }
                if(fsw_total_home != v[3]){
                    fc2 = fsw_total_home < v[3] ? 'handCpRed' : fsw_total_home > v[3] ? 'handCpGreen' : '';
                    oldOdds.find('.fsw_total_home').text(v[3]).addClass(fc2).delay(20000).queue(function() 
                    {
                       $(this).removeClass('handCpRed').removeClass('handCpGreen');
                       $(this).dequeue();
                    });
                }
                if(fsw_total_away != v[5]){
                    fc3 = fsw_total_away < v[5] ? 'handCpRed' : fsw_total_away > v[5] ? 'handCpGreen' : '';
                    oldOdds.find('.fsw_total_away').text(v[5]).addClass(fc3).delay(20000).queue(function() 
                    {
                       $(this).removeClass('handCpRed').removeClass('handCpGreen');
                       $(this).dequeue();
                    });
                }
                //盘口变化
                if(v[1] != fsw_exp)
                {
                    var desc = v[1] > 0 ? '主让' : v[1] < 0 ? '客让' : '';
                    oldOdds.find('.fsw_exp').html(desc + v[1].replace('-',''));
                }
                if(v[4] != fsw_total)
                {
                    oldOdds.find('.fsw_total').html(v[4]);
                } 
            }
        })
    }

    //定时更新比分
    setInterval(function()
    {
        $.ajax({  
            type:'get',  
            url : DOMAIN_URL+'/Home/Pcdata/bkchange',  
            dataType : 'jsonp',  
            jsonp:"jsoncallback",  
            success  : function(msg) { 
                if(msg.status == 0) return;
                var data = msg.data;
                $.each(data,function(n,info){
                    //var info = ["279293", "1", "", "120", "101", "33", "21", "34", "30", "26", "25", "30", "25", "0", "凯尔特人-得分:布拉德利(29) 篮板:马库斯-...21) 篮板:戈塔特(11) 助攻:比尔(4)", "4", "", "", "", "", "", "", "<font color=#FF0000>总比分[...=_blank><font color=blu"];
                    var gameid = info[0];
                    var game = $('#game_table tr[gameid='+gameid+']');
                    //更新分数
                    if(game.length > 0)
                    {
                        var newStatus = info[1];
                        //全场比分
                        var score = info[3] +'-'+ info[4];
                        if(game.find('.all_score').text() != score){
                            game.find('.all_score').text(score).removeClass('blue').addClass('red');
                        }
                        //半场比分
                        var halfScore = ' (' + (info[5]*1+info[7]*1) + '-' + (info[6]*1+info[8]*1) + ')';
                        if(game.find('.half_score').text() != halfScore){
                            game.find('.half_score').text(halfScore);
                        }

                        //状态
                        var oldStatus = game.find('.status').attr('status');

                        if (newStatus != oldStatus)
                        {
                            var statusStr = '';

                            if(info[15] == 2)
                            {
                                switch(newStatus)
                                {
                                    case '0': statusStr = '未开'; StateClass  = 'match-state'; break;
                                    case '1': statusStr = '上半场'+' '+info[2]; StateClass = 'text-red'; break;
                                    case '50': statusStr = '中场'; StateClass = 'text-red'; break;
                                    case '3': statusStr = '下半场'+' '+info[2]; StateClass = 'text-red'; break;
                                    case '5': statusStr = "1'OT"+' '+info[2]; StateClass = 'text-red'; break;
                                    case '6': statusStr = "2'OT"+' '+info[2]; StateClass = 'text-red'; break;
                                    case '7': statusStr = "3'OT"+' '+info[2]; StateClass = 'text-red'; break;
                                    case '-1': statusStr = '完场'; StateClass = 'text-green'; break;
                                    case '-10': statusStr = '取消'; StateClass = 'text-red'; break;
                                    case '-2': statusStr = '待定'; StateClass = 'text-red'; break;
                                    case '-12': statusStr = '腰斩'; StateClass = 'text-red'; break;
                                    case '-13': statusStr = '中断'; StateClass = 'text-red'; break;
                                    case '-14': statusStr = '推迟'; StateClass = 'text-red'; break;
                                    case '-5': statusStr = '未知'; StateClass = 'text-red'; break;
                                }
                            }
                            else if(info[15] == 4)
                            {
                                switch(newStatus)
                                {
                                    case '0': statusStr = '未开'; StateClass  = 'match-state'; break;
                                    case '1': statusStr = '第一节'+' '+info[2]; StateClass = 'text-red'; break;
                                    case '2': statusStr = '第二节'+' '+info[2]; StateClass = 'text-red'; break;
                                    case '50': statusStr = '中场'; StateClass = 'text-red'; break;
                                    case '3': statusStr = '第三节'+' '+info[2]; StateClass = 'text-red'; break;
                                    case '4': statusStr = '第四节'+' '+info[2]; StateClass = 'text-red'; break;
                                    case '5': statusStr = "1'OT"+' '+info[2]; StateClass = 'text-red'; break;
                                    case '6': statusStr = "2'OT"+' '+info[2]; StateClass = 'text-red'; break;
                                    case '7': statusStr = "3'OT"+' '+info[2]; StateClass = 'text-red'; break;
                                    case '-1': statusStr = '完场'; StateClass = 'text-green'; break;
                                    case '-10': statusStr = '取消'; StateClass = 'text-red'; break;
                                    case '-2': statusStr = '待定'; StateClass = 'text-red'; break;
                                    case '-12': statusStr = '腰斩'; StateClass = 'text-red'; break;
                                    case '-13': statusStr = '中断'; StateClass = 'text-red'; break;
                                    case '-14': statusStr = '推迟'; StateClass = 'text-red'; break;
                                    case '-5': statusStr = '未知'; StateClass = 'text-red'; break;
                                }
                            }

                            game.find('.status').attr('status',newStatus);
                            //game.find('.game-events').html(info[14]);//事件
                            game.find('.gameState').text(statusStr).removeClass('match-state text-green text-red').addClass(StateClass);

                            //移动到最后
                            if (newStatus < 0)
                            {
                                $('#game_table tr:last').after(game);
                            }
                        }
                    }
                })
            }
        })
    },'3000')

    var date       = new Date();
    var enterTime  = date.getTime();
    var updateTime = date.getTime(date.setHours(12,00,10));
    var reloadTime = 0;

    //定时任务
    setInterval(function(){
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
    },'60000');
});