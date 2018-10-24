/**
 * 足球比分JS文件
 * @author dengwj
 * @since  2018-06-22
 **/
var windowTime = 10000; //红牌进球弹框时间
var CookieArray = ['1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1','0']; //默认功能设置cookie
//[0红牌, 1比分进球, 2显示黄牌, 3提示声,4排名, 5显示往绩, 6弹框位置, 7半场, 8亚盘, 9大小, 10欧赔, 11（1完整2滚球3竞彩4精简）,12(0简体1繁体2英语)]
$(function () {
    getGameList();//异步获取赛事
    fbCookieConfig();//cookie功能值设置
});
var domain = config.domain;

//cookie功能值设置
function fbCookieConfig(){
    var fbCookie = getFbCookie();
    var featSelect = $('.feat-select label');
    if(fbCookie[0] == 0) featSelect.eq(0).find('input').attr('checked',false);
    if(fbCookie[1] == 0) featSelect.eq(1).find('input').attr('checked',false);
    if(fbCookie[2] == 0) featSelect.eq(2).find('input').attr('checked',false);
    if(fbCookie[3] == 0) featSelect.eq(3).find('input').attr('checked',false);
    if(fbCookie[4] == 0) featSelect.eq(4).find('input').attr('checked',false);
    if(fbCookie[5] == 0) featSelect.eq(5).find('input').attr('checked',false);
    if(fbCookie[6] == 0) featSelect.eq(6).find('select').val(0);
    if(fbCookie[7] == 0) {
        $('#halfCheck').siblings().addClass('myLabel02').removeClass('myLabel01');
        $('.halfCheck').addClass('hidden');
    }
    if(fbCookie[8] == 0) $('#ypCheck').siblings().addClass('myLabel02').removeClass('myLabel01');
    if(fbCookie[9] == 0) $('#sizeCheck').siblings().addClass('myLabel02').removeClass('myLabel01');
    if(fbCookie[10] == 1) $('#ouCheck').siblings().addClass('myLabel01').removeClass('myLabel02');
    if(fbCookie[11] != 1){
        switch(fbCookie[11]){
            case '2': $('#centerNav li:eq(2)').addClass('on').siblings().removeClass('on'); break;
            case '3': $('#centerNav li:eq(4)').addClass('on').siblings().removeClass('on'); break;
            case '4': $('#centerNav li:eq(6)').addClass('on').siblings().removeClass('on'); break;
        }
    }
    if(fbCookie[12] != 0){
        var langStr = fbCookie[12] == 1 ? '繁体' : 'EN';
        $('#languageContent').text(langStr);
    }
}

//异步获取赛事
function getGameList(){
    var fbCookie = getFbCookie();
    $.ajax({
        type: "get",
        url: '/fbGame.html',
        cache: false,
        dataType : 'json',  
        success: function (data) {
            if(data.status == 1){
                var game  = data.data.game;
                var union = data.data.union;
                if(union.length > 0){
                    //赛事列表筛选框
                    var unionTr = '';
                    $.each(union,function(k,v){
                        unionTr += 
                        '<li style="border-color: '+v[4]+';">'+
                        '<label class="inline">'+
                        '<input type="checkbox" data-unionLevel="'+v[6]+'" data-gameCount="'+v[10]+'" class="userid" checked value="'+v[0]+'">'+
                        '<span class="changeLang" langName="'+v[1]+','+v[2]+','+v[3]+'">'+v[1]+'</span>'+
                        '<em>['+v[10]+']</em>'+
                        '</label>'+
                        '</li>';
                    });
                    $('#menu_list ul').html(unionTr);
                }
                //赛事列表
                var game_state = data.data.state;
                var do_game_tr = no_game_tr = over_game_tr = '';
                $('.gameList,.explain').remove();
                $.each(game,function(k,v){
                    //比赛时间
                    switch(v[7]){
                        case 0: case -10: case -11: case -12: case -13: case -14:
                                var colorClass = v[7] != 0 ? 'text-red' : '';
                                var timeStr = '<span class="'+colorClass+'">'+game_state[v[7]]+'</span>';
                            break;
                        case -1: case 1: case 2: case 3: case 4:case 5:
                                if($.inArray( v[7], [1,3]) == -1){
                                    var stateStr = game_state[v[7]];
                                }else{
                                    var stateStr = '<time>'+showGoTime(v[10],v[7])+'</time><img src="'+staticDomain+'/Public/Home/score/images/scoreLive/time.gif">';
                                }
                                var timeStr = '<span class="text-red">'+stateStr+'</span>';
                            break;
                    }
                    //天气
                    var watherStr = v[45] != '' ? '<i class="icon-weather '+v[46]+'"><span class="tip">'+v[45]+'</span></i>' :'';
                    //红牌
                    var homeRedStr = v[25] > 0 ? '<em class="red-card homeRedCard">'+v[25]+'</em>' : '<em class="homeRedCard"></em>';
                    var awayRedStr = v[26] > 0 ? '<em class="red-card awayRedCard">'+v[26]+'</em>' : '<em class="awayRedCard"></em>';
                    //黄牌
                    var yellowHidden  = fbCookie[2] == 0 ? 'hidden' : ''; //黄牌显示控制
                    var homeYellowStr = v[27] > 0 ? '<em class="yellow-card homeYellowCard '+yellowHidden+'">'+v[27]+'</em>' : '<em class="homeYellowCard '+yellowHidden+'"></em>';
                    var awayYellowStr = v[28] > 0 ? '<em class="yellow-card awayYellowCard '+yellowHidden+'">'+v[28]+'</em>' : '<em class="awayYellowCard '+yellowHidden+'"></em>';
                    //球队排名
                    var rankHidden  = fbCookie[4] == 0 ? 'hidden' : ''; //球队排名显示控制
                    var homeRankStr = v[19] != '' ? '<em class="mr5 rank '+rankHidden+'">['+v[19]+']</em>' : '';
                    var awayRankStr = v[20] != '' ? '<em class="ml5 rank '+rankHidden+'">['+v[20]+']</em>' : '';
                    //比分 
                    if(v[7] == -1){
                        var homeColor = v[21] < v[22] ? 'text-blue' : 'text-red';
                        var awayColor = v[22] < v[21] ? 'text-blue' : 'text-red';
                        var scoreStr  = '<span class="'+homeColor+'">'+v[21]+'</span>-<span class="'+awayColor+'">'+v[22]+'</span>';
                    }else{
                        var scoreStr = $.inArray(v[7],[-1,1,2,3,4,5]) != -1 ? '<span>'+v[21]+'-'+v[22]+'</span>' : '-';
                    }
                    //是否有直播
                    var videoStr= liveStr = '';
                    if(v[40] == 1 || v[41] == 1){
                        var videoImg = v[40] == 1 ? 'icon-tv.png' : 'icon-flash.png';
                        videoStr = '<a target="_blank" href="/live/'+v[0]+'.html"><img src="'+staticDomain+'/Public/Home/score/images/scoreLive/'+videoImg+'" width="24" height="24"></a>';
                    }
                    //判断是否有美女直播
                    if(v[52] == 1 && v[7] > -1){
                        liveStr = '<a target="_blank" href="/live/'+v[0]+'.html?is_live=1"><img src="'+staticDomain+'/Public/Home/score/images/scoreLive/icon_live.png" width="24" height="24"></a>';
                    }
                    //角球
                    var cornerStr =  $.inArray(v[7],[0,-10,-11,-12,-13,-14]) != -1 ? '--' : v[29]+'-'+v[30];
                    //半场比分
                    var halfScoreStr = $.inArray(v[7],[-1,2,3,4,5]) != -1 ? v[23]+'-'+v[24] : '--';
                    //亚盘赔率显示控制
                    var yaHidden = fbCookie[8] == 0 ? 'hidden' : '';
                    var daHidden = fbCookie[9] == 0 ? 'hidden' : '';
                    var ouHidden = fbCookie[10] == 0 ? 'hidden' : '';
                    //半场比分显示控制
                    var halfHidden = fbCookie[7] == 0 ? 'hidden' : '';
                    var gambleStr = v[44] == 1 ? '<a href="/gambleDetails/game_id/'+v[0]+'.html" target="_blank" class="text-bblue">推荐</a>' : '';
                    var newsStr = v[48] == 1 ? '<a href="/news/game_id/'+v[0]+'.html" target="_blank" class="text-bblue">情报</a>' : '';
                    if(v[49] != '' || v[50] != ''){
                        var brStr = (v[49] != '' && v[50] != '') ? '<br>' : '';
                        var explainStr = v[49] + brStr + v[50];
                        var explain = '<tr class="explain" game_id="'+v[0]+'"><td bgcolor="#e6e6eb" class="overtime-data" colspan="18">'+explainStr+'</td></tr>';
                    }else{
                        var explain = '';
                    }
                    //判斷比賽不同狀態下的跳轉連接
                    var bfUrl = '/event_technology/game_id/'+v[0]+'.html';
                    if(v[7] == 0)
                    {
                        bfUrl = '/dataFenxi/game_id/'+v[0]+'.html';
                        if(v[44] == 1)
                        {
                            bfUrl = '/gambleDetails/game_id/'+v[0]+'.html';
                        }
                    }
                   //賠率跳轉連接
                    var peiUrl = '/oddsinfo/game_id/'+v[0]+'/sign/4.html';
                    if(v[47][0] == ''){
                        peiUrl = '/oddsinfo/game_id/'+v[0]+'/compid/8/sign/4.html';
                    }
                    var unionUrl = 'javascript:void(0);';
                    if(v[51] == '2'){
                        unionUrl = '//data.'+domain+'/cupMatch/'+v[1]+'.html';
                    }else if(v[51] == '1'){
                        unionUrl = '//data.'+domain+'/league/'+v[1]+'.html';
                    }
                    var tr =
                            '<tr class="gameList" data-sub="'+v[6]+'" data-unionId="'+v[1]+'" game_id="'+v[0]+'" data-jc="'+v[42]+'" data-grounder="'+v[39]+'">'+
                            '<td class="no-b-r">'+
                            '<input class="gameId" type="checkbox" value="'+v[0]+'">'+
                            '</td>'+
                            '<td class="match-name no-b-r no-b-l" union_color="'+v[5]+'"><a href="'+unionUrl+'" target="_blank" style="background: '+v[5]+'"><span class="changeLang" langName="'+v[2]+','+v[3]+','+v[4]+'">'+v[2]+'</span></a></td>'+
                            '<td class="no-b-r no-b-l">'+v[9]+'</td>'+
                            '<td game_state="'+v[7]+'" class="gameStatusStr text-999 no-b-r no-b-l">'+timeStr+'</td>'+
                            '<td class="no-b-r no-b-l">'+watherStr+'</td>'+
                            '<td class="gameHomeName no-b-r" style="text-align: right;"><a target="_blank" href="//data.'+domain+'/team/'+v[11]+'.html">'+homeRedStr+homeYellowStr+'<span>'+homeRankStr+'<span class="changeLang" langName="'+v[13]+','+v[14]+','+v[15]+'">'+v[13]+'</span></span></a></td>'+
                            '<td class="show_score gameScoreSign no-b-r no-b-l" first="'+v[34]+'">'+
                            '<a class="text-blue strong" target="_blank" href="'+bfUrl+'">'+scoreStr+'</a>'+
                            '</td>'+
                            '<td class="gameAwayName no-b-l no-b-r" style="text-align: left;"><a target="_blank" href="//data.'+domain+'/team/'+v[12]+'.html"><span><span class="changeLang" langName="'+v[16]+','+v[17]+','+v[18]+'">'+v[16]+'</span>'+awayRankStr+'</span>'+awayYellowStr+awayRedStr+'</a></td>'+
                            '<td class="liveIcon no-b-r no-b-l">'+liveStr+'</td>'+
                            '<td class="no-b-r no-b-l">'+videoStr+'</td>'+
                            '<td class="record_score">'+
                            '<div class="gameCornerScore jqColor">'+cornerStr+'</div>'+
                            '<div class="gameHalfScoreSign text-red">'+halfScoreStr+'</div>'+
                            '</td>'+
                            '<td class="oddss no-b-r">'+
                            '<a target="_blank" href="/oddsinfo/game_id/'+v[0]+'/compid/3/sign/1.html">'+
                            '<div class="ypCheck '+yaHidden+'">'+v[47][0]+'</div>'+
                            '<div class="sizeCheck '+daHidden+'">'+v[47][6]+'</div>'+
                            '<div class="ouCheck '+ouHidden+'">'+v[47][3]+'</div>'+
                            '</a>'+
                            '</td>'+
                            '<td class="pankou show_handcp no-b-r no-b-l">'+
                            '<a target="_blank" href="/oddsinfo/game_id/'+v[0]+'/compid/3/sign/1.html">'+
                            '<div class="ypCheck '+yaHidden+'">'+handCpSpread(v[47][1])+'</div>'+
                            '<div class="sizeCheck '+daHidden+'">'+handCpTotal(v[47][7])+'</div>'+
                            '<div class="ouCheck '+ouHidden+'">'+v[47][4]+'</div>'+
                            '</a>'+
                            '</td>'+
                            '<td class="oddss no-b-r no-b-l">'+
                            '<a target="_blank" href="/oddsinfo/game_id/'+v[0]+'/compid/3/sign/1.html">'+
                            '<div class="ypCheck '+yaHidden+'">'+v[47][2]+'</div>'+
                            '<div class="sizeCheck '+daHidden+'">'+v[47][8]+'</div>'+
                            '<div class="ouCheck '+ouHidden+'">'+v[47][5]+'</div>'+
                            '</a>'+
                            '</td>'+
                            '<td class="oddss no-b-r halfCheck '+halfHidden+'">'+
                            '<div class="ypCheck '+yaHidden+'">'+v[47][9]+'</div>'+
                            '<div class="sizeCheck '+daHidden+'">'+v[47][15]+'</div>'+
                            '<div class="ouCheck '+ouHidden+'">'+v[47][12]+'</div>'+
                            '</td>'+
                            '<td class="no-b-r no-b-l pankou halfCheck half_handcp '+halfHidden+'">'+
                            '<div class="ypCheck '+yaHidden+'">'+handCpSpread(v[47][10])+'</div>'+
                            '<div class="sizeCheck '+daHidden+'">'+handCpTotal(v[47][16])+'</div>'+
                            '<div class="ouCheck '+ouHidden+'">'+v[47][13]+'</div>'+
                            '</td>'+
                            '<td class="oddss no-b-r no-b-l halfCheck '+halfHidden+'">'+
                            '<div class="ypCheck '+yaHidden+'">'+v[47][11]+'</div>'+
                            '<div class="sizeCheck '+daHidden+'">'+v[47][17]+'</div>'+
                            '<div class="ouCheck '+ouHidden+'">'+v[47][14]+'</div>'+
                            '</td>'+
                            '<td class="data-a">'+
                            '<div class="dataLink">'+
                            '<a href="'+peiUrl+'" target="_blank">赔率</a>'+
                            '<a href="/dataFenxi/game_id/'+v[0]+'.html" target="_blank">分析</a>'+
                            newsStr+gambleStr+
                            '</div>'+
                            '</td>'+
                            '<td class="no-b-l">'+
                            '<a href="javascript:;" class="tdBtn icon-default placeToTop"></a>'+
                            '</td>'+
                            '</tr>'+explain;
                    //进行中
                    if($.inArray( v[7], [1,2,3,4,5]) != -1){
                        do_game_tr += tr;
                    }
                    //未开
                    if(v[7] == 0){
                        no_game_tr += tr;
                    }
                    //完场
                    if($.inArray( v[7], [-1, -10, -11, -12, -13, -14]) != -1){
                        over_game_tr += tr;
                    }
                });
                
                $('#do_game').append(do_game_tr);
                $('#no_game').append(no_game_tr);
                $('#over_game').append(over_game_tr);
            }
        },
        complete: function(XMLHttpRequest, textStatus) {
            //已隐藏的赛事
            var fbgameHideTr = Cookie.getCookie('fbgameHideTr');
            if(fbgameHideTr){
                var fbgameHideTrArray = fbgameHideTr.split(',');
                $.each(fbgameHideTrArray,function(k,v){
                    $("tr[game_id='"+v+"']").css('display','none');
                    $("tr[game_id='"+v+"']").find('.gameId').attr('checked',true);
                })
            }
            //恢复置顶
            var fbTopCookie = Cookie.getCookie('fbTopList');
            if(fbTopCookie){
                var fbTopList = fbTopCookie.split(",");
                $.each(fbTopList,function(k,v){
                    //记录原始位置
                    var tr = $(".gameList[game_id="+v+"]");
                    tr.find('.placeToTop').addClass('icon-top');
                    tr.before("<tr class='hidden' log_id='"+v+"'></tr>");
                    $('.livescore_table .table-header').after(tr);
                    //状态文字跟着移动
                    moveExplain(tr,v);
                })
            }
            //语言切换
            if(fbCookie[12] != 0){
                $('.changeLang').each(function(){
                    var langName = $(this).attr('langName').split(',');
                    $(this).text(langName[fbCookie[12]]);
                })
            }
            //赛事隐藏统计
            hideCount();
            $(".livescore_table").removeClass('hidden').animate({opacity:1},500);
            $('.loading_gif').remove();
            // var data = '{"status":"1","data":{"1528929":["0.25","1.12","0.71","1.54","3.55","4.60","1.5\/2","0.89","0.91"]},"msg":""}'
            // var data = '{"status": 1, "data": {"1575473": ["1575473", "5", "4", "0", "2", "0", "0", "2", "1", "3", "", "20180719094017", "3", "2", "测试1", "测试2"]}, "msg": 1531967251.1519701}'
            // gameChange(data);
        }
    });
}

//动态监听topic，接收数据处理
//也可以动态添加topic MqInit.subscribeTopic(['aa/bb']);
MqInit.onMessage(function (topic, message) {
    var data = message;
    if (topic.indexOf('fb/goal') > -1 && topic.indexOf('fb/goalpsw') < 0) {//足球全场赔率
        getGameOdds(data,1);
    } else if (topic.indexOf('/fb/goalpsw') > -1) {//足球半场赔率
        getGameOdds(data,2);
    } else if (topic.indexOf('/fb/change') > -1) {//比分变化
        gameChange(data);
    } else if (topic.indexOf('/fb/gamelist') > -1) {//赛事变化
        getGameList();
    } else if(topic.indexOf('qqty/live/notify') > -1) {
        var temp = JSON.parse(message);
        switch(temp.action){
            //显示隐藏icon
            case 'show':
            case 'hidden':
                showLiveIcon(temp);
                break;
        }
    }
}, ['qqty/api500/fb/goal', 'qqty/api500/fb/goalpsw', 'qqty/api500/fb/change', 'qqty/api500/fb/gamelist','qqty/live/notify']);


//天气隐藏显示
$(document).on('mouseenter','.icon-weather',function(e) {
    $(this).find('.tip').css('display','inline');
}).on('mouseleave','.icon-weather',function(e) {
    $(this).find('.tip').css('display','none');
});

//功能键滑动固定
if($('.qqtyLiveTop').length > 0){
    var navOffset=$(".control-con").offset().top;  
    $(window).scroll(function(){  
        var scrollPos=$(window).scrollTop();  
        if(scrollPos >=navOffset){  
            $(".control-con").addClass("div-fixed");  
            $(".control-box").css({'width': '1160px','margin':'0 auto'});
            $('#navig').css({'display': 'block','position': 'fixed','margin-left': '-600px','left': '50%','top': '55px','z-index': '99','width':'1200px'});  
        }else{  
            $(".control-con").removeClass("div-fixed");  
            $(".control-box").css({'width': '','margin':''});
            $('#navig').hide();   
        }  
    });
}

//还原初始化赛事
function resetGame(){
    $('.gameList,.explain').show();
    Cookie.delCookie('fbgameHideTr');
    $('.gameId').removeAttr("checked");
}

//存隐藏赛事cookie
function setGameHideTr(str){
    $.each(str,function(k,v){
        $("tr[game_id='"+v+"']").css('display','none');
        $(".gameList[game_id='"+v+"']").find('.gameId').attr('checked',true);
    })
    Cookie.setCookie('fbgameHideTr', str.join(','));
    //赛事隐藏统计
    hideCount();
}

//根据复选框显示隐藏赛程列表
$(".pitch").on('click', function () {
    _css = $(this).attr('type');
    var gameHideTr = new Array();
    var i = 0;
    if (_css == 'none') {
        //隐藏选中
        $(".gameList").each(function (k,v) {
            if($(this).find('.gameId').is(':checked')){
                var game_id = $(this).attr('game_id');
                gameHideTr[i] = game_id;
                i++;
            }
        });
    } else {
        //保留选中
        $(".gameList").each(function (k,v) {
            var display = $(this).css('display');
            if(!$(this).find('.gameId').is(':checked')|| display == 'none'){
                var game_id = $(this).attr('game_id');
                gameHideTr[i] = game_id;
                i++;
            }
        });
    }
    //保存隐藏赛事cookie
    setGameHideTr(gameHideTr);
});

//统计页面隐藏的赛事数
function hideCount() {
    var hideCount = 0;
    $('.gameList').each(function (index, element) {
        var display = $(this).css('display');
        if (display == 'none') {
            hideCount++;
        }
    });
    $('#gameHideCount').html(hideCount);
    $('.menu_count').html(hideCount);

    var doGameNum = noGameNum = overGameNum = 0;
    //进行中赛事判断
    $('#do_game .gameList').each(function(){
        var display = $(this).css('display');
        if (display != 'none') {
            doGameNum++;
        }
    })
    if(doGameNum == 0){
        $('#do_game .p10').parent().hide();
    }else{
        $('#do_game .p10').parent().show();
    }
    //未开赛事判断
    $('#no_game .gameList').each(function(){
        var display = $(this).css('display');
        if (display != 'none') {
            noGameNum++;
        }
    })
    if(noGameNum == 0){
        $('#no_game .p10').parent().hide();
    }else{
        $('#no_game .p10').parent().show();
    }
    //完场赛事判断
    $('#over_game .gameList').each(function(){
        var display = $(this).css('display');
        if (display != 'none') {
            overGameNum++;
        }
    })
    if(overGameNum == 0){
        $('#over_game .p10').parent().hide();
    }else{
        $('#over_game .p10').parent().show();
    }
    //背景颜色重置
    bgcolor();
}

//赛事选择
$('.event').click(function (e) {
    var unionArr = new Array();
    var i = 0;
    var one = two = three = 0;
    $('.gameList').each(function(){
        var display = $(this).css('display');
        if(display != 'none'){
            var unionid = $(this).data('unionid');
            var level = $(this).data('sub');
            switch(level){
                case 0: 
                case 1: one++;break;
                case 2: two++;break;
                case 3: three++;break;
            }
            unionArr[i] = unionid;
            i++;
        }
    })
    if(one == 0) $('.rank-ul-li01').removeClass('on');
    if(two == 0) $('.rank-ul-li02').removeClass('on');
    if(three == 0) $('.rank-ul-li03').removeClass('on');
    $('#unionLevel input[class=userid]:checkbox').each(function () {
        var union_id = $(this).val();
        if($.inArray(parseInt(union_id), unionArr) < 0){
            $(this).attr('checked',false);
        }else{
            $(this).attr('checked',true);
        }
    });
    hideCount();
    $('.box-list').fadeIn(300);
    $('.layer-list').fadeOut(300);
    $('.gs-list').fadeOut(300);
});
//联赛级别筛选
$('.rank-ul li').click(function (e) {
    var unionLevel = $(this).find('a').data('unionlevel');
    if ($(this).hasClass('on')) {
        $(this).removeClass('on');
        $('#unionLevel input[class=userid]:checkbox').each(function () {
            var level = $(this).data('unionlevel');
            //0和1为一级
            level = level == 1 || level == 0 ? 1 : level;
            if (unionLevel == level) {
                $(this).prop("checked", false);
            }
        });
    }
    else 
    {
        $(this).addClass('on');
        $('#unionLevel input[class=userid]:checkbox').each(function () {
            var level = $(this).data('unionlevel');
            //0和1为一级
            level = level == 1 || level == 0 ? 1 : level;
            if (unionLevel == level) {
                $(this).prop("checked", true);
            }
        });
    }
    //统计联赛筛选所隐藏的赛事
    dynamic();
});

//赛事选择确定事件
$('#ensure').on('click', function () {
    var unionIdStr = '';
    //判断是否有勾选联赛
    if ($('#unionLevel input[class=userid]:checkbox:checked').length <= 0) {
        showMsg('请选择联赛！', 0, 'error');
        return;
    }
    //先显示全部
    resetGame();
    //获取需要隐藏的联盟
    var hideUnion = new Array();
    var i = 0;
    $('#unionLevel input[class=userid]:checkbox').each(function (k,v) {
        if ($(this).is(':checked') == false) {
            //$("tr[data-unionId='"+$(this).val()+"']").css('display', 'none');
            hideUnion[i] = $(this).val();
            i++;
        }
    });
    var gameHideTr = new Array();
    var i = 0;
    $('.gameList').each(function () {
        var union_id = $(this).data('unionid');
        if($.inArray(String(union_id), hideUnion) >= 0){
            var game_id = $(this).attr('game_id');
            gameHideTr[i] = game_id;
            i++;
        }
        
    });
    $('#unionLevel').hide();
    //保存隐藏赛事cookie
    setGameHideTr(gameHideTr);
});

//赛事选择点击关闭事件
$("#removeAll").on('click', function () {
    $('.box-list').fadeOut(300);
});

//完整，滚球，竞彩，精简切换
function gameLottery(obj,type){
    //先显示全部
    resetGame();
    var gameHideTr = new Array();
    var i = 0;
    switch(type){
        case 'all':
            $('#centerNav li:eq(0)').addClass('on').siblings().removeClass('on');
            setFbCookie(11, 1);
        break;
        case 'grounder':
            //非滚球隐藏
            $('.gameList').each(function () {
                if ($(this).data('grounder') != 1) {
                    var game_id = $(this).attr('game_id');
                    gameHideTr[i] = game_id;
                    i++;
                }
            });
            setFbCookie(11, 2);
        break;
        case 'jc':
            //隐藏不是竞彩的赛事
            $('.gameList').each(function () {
                if ($(this).data('jc') != 1) {
                    var game_id = $(this).attr('game_id');
                    gameHideTr[i] = game_id;
                    i++;
                }
            });
            setFbCookie(11, 3);
        break;
        case 'streamline':
            //联盟级别大于2级隐藏
            $('.gameList').each(function () {
                if ($(this).data('sub') > 2) {
                    var game_id = $(this).attr('game_id');
                    gameHideTr[i] = game_id;
                    i++;
                }
            });
            setFbCookie(11, 4);
        break;
    }
    $(obj).parent().addClass('on').siblings().removeClass('on');
    //保存隐藏赛事cookie
    setGameHideTr(gameHideTr);
}
//半场筛选
$('#halfCheck').on('change', function () {
    if ($(this).is(':checked')) {
        $('.halfCheck').each(function () {
            $(this).addClass('hidden');
        });
    }
    else {
        $('.halfCheck').removeClass('hidden');

    }
});
//亚盘筛选
$('#ypCheck').on('change', function () {

    if ($(this).is(':checked')) {
        $('.ypCheck').each(function () {
            $(this).addClass('hidden');
        });
    }
    else {
        $('.ypCheck').removeClass('hidden');

    }
});
//大小筛选
$('#sizeCheck').on('change', function () {

    if ($(this).is(':checked')) {
        $('.sizeCheck').each(function () {
            $(this).addClass('hidden');
        });
    }
    else {
        $('.sizeCheck').removeClass('hidden');

    }
});
//欧赔筛选
$('#ouCheck').on('change', function () {

    if ($(this).is(':checked')) {
        $('.ouCheck').each(function () {
            $(this).addClass('hidden');
        });
    }
    else {
        $('.ouCheck').removeClass('hidden');
    }
});

//语言切换事件
$('#languageSle li a').on('click', function () {
    //0简体 1繁体 2英语
    var language = $(this).data('language');
    //切换
    $('.changeLang').each(function(){
        var langName = $(this).attr('langName').split(',');
        $(this).text(langName[language]);
    })
    var languageName = $(this).text();
    $('#languageContent').text(languageName);
    setFbCookie(12,language);
})

//置顶点击事件
$(document).on('click', '.placeToTop', function () {
    var tr = $(this).parents('tr');
    var game_id = tr.attr('game_id');
    var fbTopCookie = Cookie.getCookie('fbTopList');
    if(fbTopCookie){
        var fbTopList = fbTopCookie.split(",");
    }else{
        var fbTopList = new Array();
    }
    
    if ($(this).hasClass('icon-top')) {
        var game_state = tr.find('.gameStatusStr').attr('game_state');
        $(this).removeClass('icon-top');
        if(game_state < 0){
            //完场移动到最后
            $('#over_game').append(tr);
        }else{
            //还原位置
            $("tr[log_id='"+game_id+"']").after(tr).remove();
        }
        //删除cookie
        fbTopList.splice($.inArray(game_id,fbTopList),1);
    }
    else 
    {
        //置顶
        $(this).addClass('icon-top');
        if (tr.index() != 0) {
            //记录原始位置
            $(this).parents('tr').before("<tr class='hidden' log_id='"+game_id+"'></tr>");
            $('.livescore_table .table-header').after(tr);
            //设置cookie
            fbTopList.push(game_id);
        }
    }
    //移动状态文字
    moveExplain(tr,game_id);
    //保存cookie
    Cookie.setCookie('fbTopList',fbTopList.join(','));
    tr.fadeOut().fadeIn();//动画
    hideCount();
});

//input 背景更换
$('.cb label').click(function (e) {
    var serial = $(this).siblings('input[type="checkbox"]').val();
    if ($(this).hasClass('myLabel02')) {
        $(this).removeClass('myLabel02');
        $(this).siblings('input[type="checkbox"]').attr("checked", true);
        setFbCookie(serial, 1);
    } else {
        $(this).addClass('myLabel02');
        $(this).siblings('input[type="checkbox"]').attr("checked", false);
        setFbCookie(serial, 0);
    }
});

//赔率公司切换
$('.odds-company .dropdown-menu li').click(function (e) {
    var companyName = $(this).children('a').text();
    $('.odds-company span').html(companyName);
});
//语言切换
$('.odds-language .dropdown-menu li').click(function (e) {
    var languageName = $(this).children('a').text();
    $('.odds-language span').html(languageName);
});

//功能选择
$('.fun').click(function (e) {
    $('.layer-list').fadeIn(300);
    $('.box-list').fadeOut(300);
});
$('.bts .btn').click(function (e) {
    $('.layer-list').fadeOut(300);
});
//公司选择
$('.gongSi').click(function (e) {
    $('.gs-list').fadeIn(300);
    $('.box-list').fadeOut(300);
});
$('#closeAll').click(function (e) {
    $('.gs-list').fadeOut(300);
});

//联赛全选
$("#checkAll").click(function () {
    $(".userid").each(function () {
        this.checked = true;
    });
    $(".rank-ul li").addClass('on');
    dynamic();
});
//联赛反选
$("#reverse").click(function () {
    $(".userid").each(function () {
        if (this.checked == true) {
            this.checked = false;
        } else {
            this.checked = true;
        }
    });
    $(".rank-ul li").each(function () {
        var _li = $(this);
        if (_li.attr('class').split(" ")[2] == 'on') {
            _li.removeClass('on');
        } else {
            _li.addClass('on')
        }
    });
    dynamic();
});

//功能选择设置
$('.feat-select input').on('change', function () {
    var status = $(this).is(':checked') ? 1 : 0;
    var serial = $(this).val();
    setFbCookie(serial, status);
    switch (serial) {
        case '2':
            //黄牌显示切换
            if (status == 0) {
                $('.yellow-card').addClass('hidden');
            } else {
                $('.yellow-card').removeClass('hidden');
            }
            break;
        case '4':
            //球队排名显示切换
            if (status == 0) {
                $('.rank').addClass('hidden');
            } else {
                $('.rank').removeClass('hidden');
            }
            break;
    }
})

//提示框位置设置
$('.feat-select select').on('change', function () {
    var status = $(this).val();
    setFbCookie(6, status);
})

//设置对应cookie数值
function setFbCookie(serial, status) {
    //是否存在cookie
    var fbCookie = Cookie.getCookie('fbCookie');
    var array = fbCookie ? fbCookie.split('^') : CookieArray;
    //改变对应数值
    array[serial] = status;
    Cookie.setCookie('fbCookie', array.join('^'), 7);
}

//获取对应cookie数值
function getFbCookie(serial) {
    if (serial == '' || serial == undefined)
        serial = 'all';
    var fbCookie = Cookie.getCookie('fbCookie');
    var array = fbCookie ? fbCookie.split('^') : CookieArray;
    if (serial == 'all') return array;
    //返回对应数值
    return array[serial];
}

//赛事选择动态变化隐藏数量
$('.match-team li').on('click', function () {
    dynamic();
});

//统计联赛筛选所隐藏的赛事
function dynamic() {
    var hideGameCount = 0;
    $('#unionLevel input[class=userid]:checkbox').each(function () {
        if (!$(this).is(':checked')) {
            hideGameCount += $(this).data('gamecount');
        }
    });
    $(".menu_count").html(hideGameCount);
}
//列表底色更改
function bgcolor() {
    var right = 0;
    $(".gameList").each(function () {
        if ($(this).css('display') != 'none') {
            if (right % 2 == 0) {
                $(this).attr('bgcolor', '#ffffff');
            } else {
                $(this).attr('bgcolor', '#f7f7f7');
            }
            right++;
        }
    });
}

//赛事变化数据，比分、红黄牌，比赛时间
function gameChange(payload) {
    var temp = JSON.parse(payload);
    if(temp.status != 1) return false;
    var data = temp['data'];
    var FbCookie = getFbCookie();//功能设置
    $.each(data, function (k, v) {
        var game = $('tr[game_id="' + v[0] + '"]');
        var newStatus = v[1];
        if (game.length == 0) return true;
        
        //赛事的比赛状态或比赛时间
        var statusStr = '';
        switch (newStatus) {
            case '1':
            case '3':
                var goTime = showGoTime(v[11], newStatus);
                statusStr = '<time>' + goTime + '</time>' + '<img src="'+staticDomain+'/Public/Home/images/common/in.gif">';
                break;
            case '2':
                statusStr = '中场';
                break;
            case '4':
                statusStr = '加时';
                break;
            case '5':
                statusStr = '点球';
                break;
            case '-1':
                statusStr = '完场';
                break;
            case '-10':
                statusStr = '取消';
                break;
            case '-11':
                statusStr = '待定';
                break;
            case '-12':
                statusStr = '腰斩';
                break;
            case '-13':
                statusStr = '中断';
                break;
            case '-14':
                statusStr = '推迟';
                break;
        }

        if (newStatus != 0) statusStr = "<span class='text-red'>" + statusStr + "</span>";

        //页面红牌
        var home_card = game.find('.homeRedCard').text();
        var away_card = game.find('.awayRedCard').text();
        if (home_card == '') home_card = 0;
        if (away_card == '') away_card = 0;

        //页面比分
        var score = $.trim(game.find('.gameScoreSign').text()).split('-');
        var home_score = score[0];
        var away_score = score[1];
        if (home_score == '') home_score = 0;
        if (away_score == '') away_score = 0;

        //红牌与比分变化
        if (v[6] != home_card || v[7] != away_card || v[2] != home_score || v[3] != away_score) {
            var msg = v[6] > home_card || v[7] > away_card ? '红牌' : '进球';

            var home_team_color = (v[6] > home_card) || (v[2] > home_score) ? 'text-red' : '';
            var away_team_color = (v[7] > away_card) || (v[3] > away_score) ? 'text-red' : '';
            //主队变颜色
            if (home_team_color != '') {
                game.find('.gameHomeName').css('background', '#bbbb22').delay(windowTime).queue(function () {
                    $(this).css('background', '');
                    $(this).dequeue();
                });
            }
            //客队变颜色
            if (away_team_color != '') {
                game.find('.gameAwayName').css('background', '#bbbb22').delay(windowTime).queue(function () {
                    $(this).css('background', '');
                    $(this).dequeue();
                });
            }
            var is_window = msg == '红牌' ? FbCookie[0] : FbCookie[1];
            if (is_window == 1 && game.css('display') != 'none' && $.inArray(Number(newStatus),[1,2,3,4,5]) != -1) {
                //获取页面数据
                var union_name     = game.find('.match-name .changeLang').text();
                var union_color    = game.find('.match-name').attr('union_color');
                var home_rank      = game.find('.gameHomeName .rank').prop("outerHTML");
                var home_team_name = game.find('.gameHomeName .changeLang').text();
                var away_rank      = game.find('.gameAwayName .rank').prop("outerHTML");
                var away_team_name = game.find('.gameAwayName .changeLang').text();
                var home_card_html = v[6] > 0 ? '<em class="homeRedCard red-card">' + v[6] + '</em>' : '';
                var away_card_html = v[7] > 0 ? '<em class="homeRedCard red-card">' + v[7] + '</em>' : '';
                if (home_rank == undefined) home_rank = '';
                if (away_rank == undefined) away_rank = '';
                var header_color = msg == '红牌' ? '#c40227' : '#10af63';
                //弹框html
                var html = '<div class="jinqiu windowCheck-' + v[0] + '" style="box-shadow: 0 0 1px 2px rgba(0,0,0,0.1)">' +
                    '<div class="title" style="background:' + header_color + '">' + msg + '提示<a href="javascript:;" onclick="$(this).parent().parent().remove()" class="pull-right"><img style="display:block;" src="'+staticDomain+'/Public/Home/score/images/scoreLive/bts-close.png"></a></div>' +
                    '<table class="table" style=" margin-bottom:0;background: #fff">' +
                    '<tr>' +
                    '<td width="65" class="match-name">' +
                    '<a href="javascript:;" style="background: ' + union_color + '">' + union_name + '</a>' +
                    '</td>' +
                    '<td>' + statusStr + '</td>' +
                    '<td class="' + home_team_color + '">' +
                    home_card_html +
                    home_rank + home_team_name +
                    '</td>' +
                    '<td><span class="text-red"><strong>' + v[2] + '-' + v[3] + '</span></td>' +
                    '<td class="' + away_team_color + '">' +
                    away_team_name + away_rank +
                    away_card_html +
                    '</td>' +
                    '</tr>' +
                    '</table>' +
                    '</div>';

                if (FbCookie[6] == 1) {
                    //正上方
                    $('.prompt').append(html)
                } else {
                    //正下方
                    $('.prompt-bottom').append(html)
                }
                if (FbCookie[3] == 1 && $.support.leadingWhitespace) {
                    var videoName = msg == '红牌' ? 'cardAudio' : 'goalAudio';
                    $('#' + videoName)[0].play(); //播放声音
                }
                window.setTimeout(function () {
                    $('.windowCheck-' + v[0]).remove();
                }, windowTime);
            }
        }

        //主队红牌
        if (v[6] > 0 && v[6] != '') game.find('.homeRedCard').addClass('red-card').html(v[6]);
        //客队红牌
        if (v[7] > 0 && v[7] != '') game.find('.awayRedCard').addClass('red-card').html(v[7]);

        //主队黄牌
        if (v[8] > 0 && v[8] != '') game.find('.homeYellowCard').addClass('yellow-card').html(v[8]);
        //客队黄牌
        if (v[9] > 0 && v[9] != '') game.find('.awayYellowCard').addClass('yellow-card').html(v[9]);

        //比分变化
        if(v[2] != home_score || v[3] != away_score){
            if(newStatus > 0){
                //进行中
                var score = v[2] + '-' + v[3];
                game.find('.gameScoreSign').find('a').html("<span>"+score+"</span>");
            }else{
                //完场
                var homeColor = v[2] < v[3] ? 'text-blue' : 'text-red';
                var awayColor = v[3] < v[2] ? 'text-blue' : 'text-red';
                game.find('.gameScoreSign').find('a').html("<span class='"+homeColor+"'>"+v[2]+"</span>-<span class='"+awayColor+"'>"+v[3]+"</span>");
                
            }
        }
        //半场比分
        if (newStatus >= 2) {
            var half_home_score = v[4];
            var half_away_score = v[5];
            if (half_home_score == '') half_home_score = 0;
            if (half_away_score == '') half_away_score = 0;
            var halfScore = half_home_score + '-' + half_away_score;
            game.find('.gameHalfScoreSign').text(halfScore);
        }

        //角球变化
        var oldCornerScore = game.find('.gameCornerScore').text();
        var CornerScore    = v[12] + '-' + v[13];
        if ((v[12] > 0 || v[13] > 0) && oldCornerScore != CornerScore) game.find('.gameCornerScore').text(CornerScore);

        //赛事状态文字
        

        //更新状态
        var oldStatus = game.find('.gameStatusStr').attr('game_state');
        game.find('.gameStatusStr').attr('game_state', newStatus).html(statusStr);
        if (newStatus != oldStatus){
            //未开变成开始移动到开始栏中
            if(oldStatus <= 0 && $.inArray(parseInt(newStatus),[1,2,3,4,5]) >= 0){
                game.find('.show_score a').addClass('text-blue');
                var score = v[2] + '-' + v[3];
                game.find('.gameScoreSign').find('a').html("<span>"+score+"</span>");
                $('#do_game').append(game);
                //状态文字
                addExplain(game,v[0],v[14],v[15]);
                hideCount();
                return;
            }
            //完场10秒后移动到最后（置顶的不需要移动）
            if(newStatus < 0){
                var homeColor = v[2] < v[3] ? 'text-blue' : 'text-red';
                var awayColor = v[3] < v[2] ? 'text-blue' : 'text-red';
                game.find('.gameScoreSign').find('a').html("<span class='"+homeColor+"'>"+v[2]+"</span>-<span class='"+awayColor+"'>"+v[3]+"</span>");
                if(game.find('.placeToTop').hasClass('icon-top') == false){
                    window.setTimeout(function () {
                        //完场
                        $('#over_game').append(game);
                        //状态文字
                        addExplain(game,v[0],v[14],v[15]);
                        hideCount();
                    }, windowTime);
                    return;
                }
            }
        }
        //状态文字
        addExplain(game,v[0],v[14],v[15]);
    });
}

//添加状态文字
function addExplain(game,game_id,explain,remark){
    if(explain != '' || remark != ''){
        var brStr = (explain != '' && remark != '') ? '<br>' : '';
        var explainStr = explain + brStr + remark;
        var display = game.css('display') == 'none' ? 'style="display: none;"' : '';
        var explainHtml = '<tr class="explain" game_id="'+game_id+'" '+display+'><td bgcolor="#e6e6eb" class="overtime-data" colspan="18">'+explainStr+'</td></tr>';
        $('.explain[game_id="' + game_id + '"]').remove();
        game.after(explainHtml);
    }
}

//状态文字移动
function moveExplain(tr,game_id){
    var explainTr = $('.explain[game_id="' + game_id + '"]');
    if(explainTr.length > 0){
        tr.after(explainTr);
    }
}

//鼠标移到上半场比分出现比赛记录
$(document).on('mouseenter','.record_score',function (e) {
    if (getFbCookie(5) == 0) return false;
    //获取赛事ID
    var gameId = $(this).parent().attr('game_id');
    var panlu = panluData = '';
    var tr_panlu = $(this).attr('panlu');
    if(tr_panlu == undefined){
        $.ajax({
            type: "get",
            url: DOMAIN_URL+'/Webfb/getFbPanlu.html',
            data:{gameId:gameId},
            cache: false,
            async: false,
            dataType : 'json',  
            success: function (data) {
                panluData = data;
            }
        });
        $(this).attr('panlu',JSON.stringify(panluData));
    }else{
        panluData = JSON.parse(tr_panlu);
    }
    
    panlu = panluData[gameId];
    if(panlu == undefined){
        panlu = '';
    }
    var mTop = $(this).parents('tr').offset().top;
    var sTop = $(window).scrollTop();
    var result = mTop - sTop;//tr距离浏览器可视区域顶部的高度
    var butt = document.documentElement.clientHeight - result - 52;//tr距离底部的距离，用于判断
    var tdHeiht = $(this).height();
    var tdTopHeight = $(this).offset().top;
    var ssHeight = tdHeiht + tdTopHeight;
    var tbHeight = $('.livescore_table').offset().top;
    var myHeight = ssHeight - tbHeight;
    var panlu_num = panlu.length;

    if (panlu_num > 0) {
        var yingpan_num = panlu_num;
        //主队名称
        var home_team = $(this).siblings('.gameHomeName').find('.changeLang').text();
        //客队名称
        var away_team = $(this).siblings('.gameAwayName').find('.changeLang').text();
        $('#winScore .winScore-home').text(home_team);
        $('#winScore .winScore-away').text(away_team);
        var html = '';
        var pl_num = sf_num = dx_num = ds_num = 0;
        for (var k in panlu) {
            var pl = sf = dx = ds = '-';
            switch (panlu[k][9]) {
                case '赢' :
                    pl = '<font class="text-red">赢</font>';
                    pl_num++;
                    break;
                case '走' :
                    pl = '<font class="text-blue">走</font>';
                    yingpan_num --;
                    break;
                case '输':
                    pl = '<font class="text-green">输</font>';
                    break;
            }
            switch (panlu[k][10]) {
                case '1' :
                    sf = '<font class="text-red">胜</font>';
                    sf_num++;
                    break;
                case '0' :
                    sf = '<font class="text-blue">平</font>';
                    break;
                case '-1':
                    sf = '<font class="text-green">负</font>';
                    break;
            }
            switch (panlu[k][11]) {
                case '1' :
                    dx = '<font class="text-red">大</font>';
                    dx_num++;
                    break;
                case '0' :
                    dx = '<font class="text-blue">走</font>';
                    break;
                case '-1':
                    dx = '<font class="text-green">小</font>';
                    break;
            }
            switch (panlu[k][12]) {
                case '1' :
                    ds = '<font class="text-red">单</font>';
                    ds_num++;
                    break;
                case '2' :
                    ds = '<font class="text-blue">双</font>';
                    break;
            }
            var color = (k % 2) == 0 ? '#ffffff' : '#f7f7f7';
            var fbCookie = getFbCookie();
            var langNum = fbCookie[12] ?fbCookie[12]:0;
            var homeColor = panlu[k][13] == 1 ? 'text-red' : '';
            var awayColor = panlu[k][13] == 0 ? 'text-red' : '';
            html += '<tr align="center" bgcolor="' + color + '">' +
                '<td bgcolor="' + panlu[k][3] + '" height="22"><font color="#FFFFFF">' + panlu[k][2].split(",")[langNum] + '</font></td>' +
                '<td>' + panlu[k][1] + '</td>' +
                '<td class="'+homeColor+'">' + panlu[k][4].split(",")[langNum] + '</td>' +
                '<td style="color:red"><b>' + panlu[k][6] + '</b></td>' +
                '<td class="'+awayColor+'">' + panlu[k][5].split(",")[langNum] + '</td>' +
                '<td><font color="red">' + panlu[k][7] + '</font></td>' +
                '<td>' + panlu[k][8] + '</td>' +
                '<td>' + pl + '</td>' +
                '<td>' + sf + '</td>' +
                '<td>' + dx + '</td>' +
                '<td>' + ds + '</td>' +
                '</tr>';
        }
        var pl_win = parseFloat((pl_num / yingpan_num * 100).toFixed(1));
        var sf_win = Math.round(sf_num / panlu_num * 100);
        var dx_win = Math.round(dx_num / panlu_num * 100);
        var ds_win = Math.round(ds_num / panlu_num * 100);
        html += '<td height="20" align="center" colspan="11" bgcolor="white">' +
            '最近[<font color="red"> ' + panlu_num + ' </font>]场，' +
            '赢盘率：<font color="red"> ' + pl_win + '% </font>，' +
            '胜率：<font color="red"> ' + sf_win + '% </font>，' +
            '大球：<font color="red"> ' + dx_win + '% </font>，' +
            '单：<font color="red"> ' + ds_win + '% </font>' +
            '</td>';
        $('#winScore thead').after(html);
        //有历史对战
        if ($('#winScore').height() > butt) {
            myHeight = myHeight - $('#winScore').height() - 42;
        }
        $('#winScore').stop().fadeIn(0).css({'top': '' + myHeight + 'px'});
    } else {
        //没有历史对战
        var union_name = $(this).siblings('.match-name').find('.changeLang').text();
        $('#noScoreContent').html('无&nbsp;&nbsp;' + union_name + '&nbsp;&nbsp;对战记录!');
        if ($('#noScore').height() > butt) {
            myHeight = myHeight - $('#noScore').height() - 42;
        }
        $('#noScore').stop().fadeIn(0).css({'top': '' + myHeight + 'px'});
    }
}).on('mouseleave','.record_score',function(){
    $('#winScore,#noScore').stop().fadeOut(0);
    $('#winScore thead').nextAll().remove();
});

//鼠标移到上全场比分出现（比分事件）
$(document).on('mouseenter','.show_score',function (e) {
    var game_id = $(this).parents('tr').attr('game_id');
    var status  = $(this).siblings('.gameStatusStr').attr('game_state');
    var This = $(this);
    var teamData = '';
    //主队名称
    var home_team = This.prev().find('.changeLang').text();
    //客队名称
    var away_team = This.next().find('.changeLang').text();
    var first = This.attr('first');
    var html = '<table game_id="' + game_id + '" class="table table-bordered" width="500" bgcolor="#E1E1E1" cellpadding="0" cellspacing="0" border="0" style="width:500px">' +
        '<tbody><tr>' +
        '<td colspan="5" bgcolor="#4d7083" align="center"><font color="white"><b>初盘参考：' + first + '</b></font></td>' +
        '</tr><tr id="teamhead" bgcolor="#e5e5e5" align="center">' +
        '<td colspan="2" width="44%"><font>' + home_team + '</font></td>' +
        '<td width="12%">时间</td>' +
        '<td colspan="2" width="44%"><font>' + away_team + '</font></td></tr>';
    //未开赛无需请求
    if(status != 0){
        $.ajax({
            type: "get",
            url: DOMAIN_URL+'/Webfb/detail.html',
            cache: false,
            async: false,
            data: {"gameId": game_id},
            success: function (data) {
                teamData = data.data.t;
                if(teamData){
                    var team = teamData[game_id];
                    html += doTeamDetail(team);
                }
            }
        });
    }
    html += '</tbody></table>';
    var mTop    = This.parents('tr').offset().top;
    var sTop    = $(window).scrollTop();
    var result  = mTop - sTop;//tr距离浏览器可视区域顶部的高度
    var butt    = document.documentElement.clientHeight - result - 52;//tr距离底部的距离，用于判断
    var tdHeiht = This.height() + 5;
    var tdTopHeight = This.offset().top;
    var ssHeight = tdHeiht + tdTopHeight;
    var tbHeight = $('.livescore_table').offset().top;
    var myHeight = ssHeight - tbHeight;
    $('#jinq_box').append(html);
    if ($('#jinq_box').height() > butt) {
        myHeight = myHeight - $('#jinq_box').height() - 42;
    }
    $('#jinq_box').stop().fadeIn(0).css({'top': '' + myHeight + 'px'});
}).on('mouseleave','.show_score',function(){
    $('#jinq_box').empty();
    $('#jinq_box').stop().fadeOut(0);
});

//鼠标移到赔率弹窗
$(document).on('mouseenter','td.show_handcp',function(e) {
    var This = $(this);
    if($.trim(This.find('div').text()) == '') return false;

    var union_color = This.siblings('.match-name').find('a').attr('style');
    //联赛名称
    var union_name = This.siblings('.match-name').find('.changeLang').text();
    //主队名称
    var home_team = This.siblings('.gameHomeName').find('.changeLang').text();
    //客队名称
    var away_team = This.siblings('.gameAwayName').find('.changeLang').text();
    var game_id = This.parents('tr').attr('game_id');
    var oddsData = '';
    $.ajax({
        type: "get",
        url: DOMAIN_URL+'/Webfb/oddsData.html',
        cache: false,
        async: false,
        data:{gameId:game_id},
        success: function (data) {
            if (data.status == 0) return;
            oddsData = data.data;
            var odds = oddsData[game_id];
            var ya_odds  = odds[0];  //亚盘
            var ou_odds  = odds[1];  //欧盘
            var da_odds  = odds[2];  //大小
            var all_odds = odds[6];  //各公司赔率
            var comHtml = '';
            var a = 0;
            for (var k in all_odds) {
                var companyName = company[k];
                comHtml += '<td width="20%" bgcolor="#e5e5e5">' + companyName + '</td>' +
                    '<td width="10%" bgcolor="#f9f9f9"><div>' + all_odds[k][0] + '</div></td>' +
                    '<td width="10%" bgcolor="#f9f9f9">' + all_odds[k][1] + '</td>' +
                    '<td width="10%" bgcolor="#f9f9f9"><div>' + all_odds[k][2] + '</div></td>';
                a++;
                if (a % 2 == 0) comHtml += '^'; //两个为一组
            }
            var comArr = comHtml.split('^')
            var tr = '';
            $.each(comArr, function (k, v) {
                tr += '<tr>' + v + '</tr>';
            })

            var a_color = ya_odds[3] > ya_odds[0] ? 'up-red' : ya_odds[3] < ya_odds[0] ? 'down-green' : '';
            var b_color = ya_odds[5] > ya_odds[2] ? 'up-red' : ya_odds[5] < ya_odds[2] ? 'down-green' : '';
            var c_color = da_odds[3] > da_odds[0] ? 'up-red' : da_odds[3] < da_odds[0] ? 'down-green' : '';
            var d_color = da_odds[5] > da_odds[2] ? 'up-red' : da_odds[5] < da_odds[2] ? 'down-green' : '';
            var e_color = ou_odds[3] > ou_odds[0] ? 'up-red' : ou_odds[3] < ou_odds[0] ? 'down-green' : '';
            var f_color = ou_odds[5] > ou_odds[2] ? 'up-red' : ou_odds[5] < ou_odds[2] ? 'down-green' : '';
            var html = '<div class="livetab" game_id="' + game_id + '" style="width: 550px; visibility: visible;">' +
                '<table class="table table01" width="100%" border="0" cellpadding="0" cellspacing="1">' +
                '<tbody>' +
                '<tr>' +
                '<td class="event" width="20%" style="border-top: none;' + union_color + '"><a href="#" target="_blank" style="color:#FFF">' + union_name + '</a></td>' +
                '<td width="33%" bgcolor="#ffffff" style="text-align:right;border-top: none;"><font style="font-size: 14px;">' + home_team + '</font></td>' +
                '<td width="14%" bgcolor="#ffffff" style=" border-top: none;">VS</td>' +
                '<td width="33%" bgcolor="#ffffff" style="text-align:left;border-top: none;"><font style="font-size: 14px;">' + away_team + '</font></td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '<table class="table" width="100%" border="0" cellpadding="0" cellspacing="1">' +
                '<tbody>' +
                tr +
                '</tbody>' +
                '</table>' +
                '<table class="table table03"  width="100%" border="0" cellpadding="0" cellspacing="1">' +
                '<tbody>' +
                '<tr class="tr01">' +
                '<td width="10%" bgcolor="#4c9ffa" class="no-b-l">SB</td>' +
                '<td width="30%" colspan="3" bgcolor="#4c9ffa">让球<span>(全场)</span></td>' +
                '<td width="30%" colspan="3" bgcolor="#4c9ffa">大小<span>(全场)</span></td>' +
                '<td width="30%" colspan="3" bgcolor="#4c9ffa">欧指数<span>(全场)</span></td>' +
                '</tr>' +
                '<tr>' +
                '<td height="26" bgcolor="#e5e5e5" class="no-b-l">初盘</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div>' + ya_odds[0] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + handCpSpread(ya_odds[1]) + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l"><div>' + ya_odds[2] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div>' + da_odds[0] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + handCpTotal(da_odds[1]) + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l"><div>' + da_odds[2] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div>' + ou_odds[0] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + ou_odds[1] + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r no-b-l"><div>' + ou_odds[2] + '</div></td>' +
                '</tr>' +
                '<tr class="js">' +
                '<td height="26" bgcolor="#e5e5e5" class="no-b-l">即时</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div class="'+a_color+'">' + ya_odds[3] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + handCpSpread(ya_odds[4]) + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l"><div class="'+b_color+'">' + ya_odds[5] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div class="'+c_color+'">' + da_odds[3] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + handCpTotal(da_odds[4]) + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l"><div class="'+d_color+'">' + da_odds[5] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div class="'+e_color+'">' + ou_odds[3] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + ou_odds[4] + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r no-b-l"><div class="'+f_color+'">' + ou_odds[5] + '</div></td>' +
                '</tr>' +
                '<tr class="gq">' +
                '<td height="26" bgcolor="#e5e5e5" class="no-b-l">滚球</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div>' + ya_odds[6] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + handCpSpread(ya_odds[7]) + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l"><div>' + ya_odds[8] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div>' + da_odds[6] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + da_odds[7] + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l"><div>' + da_odds[8] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r"><div>' + ou_odds[6] + '</div></td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-l no-b-r">' + ou_odds[7] + '</td>' +
                '<td height="26" bgcolor="#f7f7f7" class="no-b-r no-b-l"><div>' + ou_odds[8] + '</div></td>' +
                '</tr>' +
                '</tbody>' +
                '</table>' +
                '</div>';
            This.append(html);
            //获取页面更新元素更新
            dolivetab();
            //判断 livetab 溢出底部上移
            var top01 = This[0].offsetTop;
            var top03 = $(window).scrollTop();
            var top02 = $(".livetab").height();//+'top02'+
            //var se    = document.documentElement.clientHeight;
            se = $(window).scrollTop();
            if(top01-top03>top02){
                $(".livetab").css('top', -top02 +'px');
            }else{
                $(".livetab").css('top', '3px');
            }
        }
    });
}).on('mouseleave','td.show_handcp',function(){
    $(".livetab").remove();
});


//处理赛事事件html
function doTeamDetail(team) {
    var html = '';
    for (var k in team) {
        var home_team_name = away_team_name = home_img = away_img = img = '';
        var path = staticDomain+'/Public/Home/score/images/event/';
        switch (team[k][2]) {
            case  '1':
                img = '<img src="' + path + 'jinqiu.png" width="15" height="16">';
                break;
            case  '2':
                img = '<img src="' + path + 'red-card.png" width="14" height="18">';
                break;
            case  '3':
                img = '<img src="' + path + 'yellow-card.png" width="14" height="18">';
                break;
            case  '7':
                img = '<img src="' + path + 'penalty.png" width="15" height="16">';
                break;
            case  '8':
                img = '<img src="' + path + 'oolong.png" width="15" height="16">';
                break;
            case  '9':
                img = '<img src="' + path + 'yellow-card.png" width="14" height="18">';
                break;
            case '11':
                img = '<img src="' + path + 'in-out.png" width="22" height="12">';
                break;
            case '13':
                img = '<img src="' + path + 'no-kick.png" width="15" height="16">';
                break;
        }
        if (team[k][1] == 1) {
            var home_team_name = team[k][6];
            var home_img = img;
        } else {
            var away_team_name = team[k][6];
            var away_img = img;
        }
        var color = (k % 2) == 0 ? '#ffffff' : '#f7f7f7';
        html += '<tr bgcolor="' + color + '" align="center">' +
            '<td width="8%">' + home_img + '</td>' +
            '<td width="36%">' + home_team_name + '</td>' +
            '<td width="12%" bgcolor="#e5e5e5">' + team[k][3] + '\'</td>' +
            '<td width="36%">' + away_team_name + '</td>' +
            '<td width="8%">' + away_img + '</td>' +
            '</tr>';
    }
    return html;
}

//获取最新实时赔率
function getGameOdds(payload,oddsType) {
    var temp = JSON.parse(payload);
    if(temp.status != 1) return false;
    var data = temp['data'];
    if(oddsType == 1){
        //全场变化
        $.each(data, function (k, v) {
            var game_id = k;
            var oldOdds = $('tr[game_id="' + game_id + '"]').find('.show_handcp');
            if(oldOdds.length == 0) return true;
            //全场
            var fsw_exp_home     = v[1];
            var fsw_exp          = handCpSpread(v[0]);
            var fsw_exp_away     = v[2];
            var fsw_ball_home    = v[7];
            var fsw_ball         = v[6];
            var fsw_ball_away    = v[8];
            var fsw_europe_home  = v[3];
            var fsw_europe       = v[4];
            var fsw_europe_away  = v[5];
            var _fsw_exp_home    = oldOdds.prev().find('div').eq(0).text();
            var _fsw_exp         = $.trim(oldOdds.find('div').eq(0).text());
            var _fsw_exp_away    = oldOdds.next().find('div').eq(0).text();
            var _fsw_ball_home   = oldOdds.prev().find('div').eq(1).text();
            var _fsw_ball        = oldOdds.find('div').eq(1).text();
            var _fsw_ball_away   = oldOdds.next().find('div').eq(1).text();
            var _fsw_europe_home = oldOdds.prev().find('div').eq(2).text();
            var _fsw_europe      = oldOdds.find('div').eq(2).text();
            var _fsw_europe_away = oldOdds.next().find('div').eq(2).text();

            //全-亚
            if (fsw_exp_home != _fsw_exp_home) {
                fc0 = fsw_exp_home > _fsw_exp_home ? 'up-red' : 'down-green';
                oldOdds.prev().find('div').eq(0).removeClass('up-red down-green');
                if(fsw_exp_home != ''){
                    oldOdds.prev().find('div').eq(0).html(fsw_exp_home).addClass(fc0).delay(10000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }else{
                    oldOdds.prev().find('div').eq(0).html('&nbsp;');
                }
            }
            if (fsw_exp != '') oldOdds.find('div').eq(0).html(fsw_exp);
            if (fsw_exp_away != _fsw_exp_away) {
                fc1 = fsw_exp_away > _fsw_exp_away ? 'up-red' : 'down-green';
                oldOdds.next().find('div').eq(0).removeClass('up-red down-green');
                if(fsw_exp_away != ''){
                    oldOdds.next().find('div').eq(0).html(fsw_exp_away).addClass(fc1).delay(10000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }else{
                    oldOdds.next().find('div').eq(0).html('&nbsp;');
                }
            }
            //全-大
            if (fsw_ball_home != _fsw_ball_home) {
                fc2 = fsw_ball_home > _fsw_ball_home ? 'up-red' : 'down-green';
                oldOdds.prev().find('div').eq(1).removeClass('up-red down-green');
                if(fsw_ball_home != ''){
                    oldOdds.prev().find('div').eq(1).html(fsw_ball_home).addClass(fc2).delay(10000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }else{
                    oldOdds.prev().find('div').eq(1).html('&nbsp;');
                }
            }
            if (fsw_ball != '') oldOdds.find('div').eq(1).html(fsw_ball);
            if (fsw_ball_away != _fsw_ball_away) {
                fc3 = fsw_ball_away > _fsw_ball_away ? 'up-red' : 'down-green';
                oldOdds.next().find('div').eq(1).removeClass('up-red down-green');
                if(fsw_ball_away != ''){
                    oldOdds.next().find('div').eq(1).html(fsw_ball_away).addClass(fc3).delay(10000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }else{
                    oldOdds.next().find('div').eq(1).html('&nbsp;');
                }
            }
            //全-欧
            if (fsw_europe_home != _fsw_europe_home) {
                fc4 = fsw_europe_home > _fsw_europe_home ? 'up-red' : 'down-green';
                oldOdds.prev().find('div').eq(2).removeClass('up-red down-green');
                if(fsw_europe_home != ''){
                    oldOdds.prev().find('div').eq(2).html(fsw_europe_home).addClass(fc4).delay(10000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }else{
                    oldOdds.prev().find('div').eq(2).html('&nbsp;');
                }
            }
            if (fsw_europe != '') oldOdds.find('div').eq(2).html(fsw_europe);
            if (fsw_europe_away != _fsw_europe_away) {
                fc5 = fsw_europe_away > _fsw_europe_away ? 'up-red' : 'down-green';
                oldOdds.next().find('div').eq(2).removeClass('up-red down-green');
                if(fsw_europe_away != ''){
                    oldOdds.next().find('div').eq(2).html(fsw_europe_away).addClass(fc5).delay(10000).queue(function () {
                        $(this).removeClass('up-red down-green');
                        $(this).dequeue();
                    });
                }else{
                    oldOdds.next().find('div').eq(2).html('&nbsp;');
                }
            }
        })
    }else{
        //半场变化
        $.each(data, function (k, v) {
            var game_id = k;
            var halfOdds = $('tr[game_id="' + game_id + '"]').find('.half_handcp');
            if(halfOdds.length == 0) return true;
            //半场
            var half_exp_home     = v[1];
            var half_exp          = handCpSpread(v[0]);
            var half_exp_away     = v[2];
            var half_ball_home    = v[7];
            var half_ball         = v[6];
            var half_ball_away    = v[8];
            var half_europe_home  = v[3];
            var half_europe       = v[4];
            var half_europe_away  = v[5];
            var _half_exp_home    = halfOdds.prev().find('div').eq(0).text();
            var _half_exp         = $.trim(halfOdds.find('div').eq(0).text());
            var _half_exp_away    = halfOdds.next().find('div').eq(0).text();
            var _half_ball_home   = halfOdds.prev().find('div').eq(1).text();
            var _half_ball        = halfOdds.find('div').eq(1).text();
            var _half_ball_away   = halfOdds.next().find('div').eq(1).text();
            var _half_europe_home = halfOdds.prev().find('div').eq(2).text();
            var _half_europe      = halfOdds.find('div').eq(2).text();
            var _half_europe_away = halfOdds.next().find('div').eq(2).text();

            //半-亚
            if (half_exp_home != _half_exp_home && half_exp_home != '') {
                fc0 = half_exp_home > _half_exp_home ? 'up-red' : 'down-green';
                halfOdds.prev().find('div').eq(0).removeClass('up-red down-green');
                halfOdds.prev().find('div').eq(0).text(half_exp_home).addClass(fc0).delay(10000).queue(function () {
                    $(this).removeClass('up-red down-green');
                    $(this).dequeue();
                });
            }
            if (half_exp != '') halfOdds.find('div').eq(0).text(half_exp);
            if (half_exp_away != _half_exp_away && half_exp_away != '') {
                fc1 = half_exp_away > _half_exp_away ? 'up-red' : 'down-green';
                halfOdds.next().find('div').eq(0).removeClass('up-red down-green');
                halfOdds.next().find('div').eq(0).text(half_exp_away).addClass(fc1).delay(10000).queue(function () {
                    $(this).removeClass('up-red down-green');
                    $(this).dequeue();
                });
            }
            //半-大
            if (half_ball_home != _half_ball_home && half_ball_home != '') {
                fc2 = half_ball_home > _half_ball_home ? 'up-red' : 'down-green';
                halfOdds.prev().find('div').eq(1).removeClass('up-red down-green');
                halfOdds.prev().find('div').eq(1).text(half_ball_home).addClass(fc2).delay(10000).queue(function () {
                    $(this).removeClass('up-red down-green');
                    $(this).dequeue();
                });
            }
            if (half_ball != '') halfOdds.find('div').eq(1).text(half_ball);
            if (half_ball_away != _half_ball_away && half_ball_away != '') {
                fc3 = half_ball_away > _half_ball_away ? 'up-red' : 'down-green';
                halfOdds.next().find('div').eq(1).removeClass('up-red down-green');
                halfOdds.next().find('div').eq(1).text(half_ball_away).addClass(fc3).delay(10000).queue(function () {
                    $(this).removeClass('up-red down-green');
                    $(this).dequeue();
                });
            }
            //半-欧
            if (half_europe_home != _half_europe_home && half_europe_home != '') {
                fc4 = half_europe_home > _half_europe_home ? 'up-red' : 'down-green';
                halfOdds.prev().find('div').eq(2).removeClass('up-red down-green');
                halfOdds.prev().find('div').eq(2).text(half_europe_home).addClass(fc4).delay(10000).queue(function () {
                    $(this).removeClass('up-red down-green');
                    $(this).dequeue();
                });
            }
            if (half_europe != '') halfOdds.find('div').eq(2).text(half_europe);
            if (half_europe_away != _half_europe_away && half_europe_away != '') {
                fc5 = half_europe_away > _half_europe_away ? 'up-red' : 'down-green';
                halfOdds.next().find('div').eq(2).removeClass('up-red down-green');
                halfOdds.next().find('div').eq(2).text(half_europe_away).addClass(fc5).delay(10000).queue(function () {
                    $(this).removeClass('up-red down-green');
                    $(this).dequeue();
                });
            }
        })
    }

    //弹框出现更新弹框赔率
    if ($('.livetab').children().length > 0) {
        dolivetab();
    }
}

//弹框出现更新弹框赔率
function dolivetab(){
    var game_id2 = $('.livetab').attr('game_id');
    var game_state = $('tr[game_id="' + game_id2 + '"]').find('.gameStatusStr').attr('game_state');
    //完场或异常的不用变化
    if($.inArray(game_state,[0,1,2,3,4,5]) == -1){
        return false;
    }
    var oldOdds2 = $('tr[game_id="' + game_id2 + '"]').find('.show_handcp');

    var _gq = $('.livetab .gq td');
    var _js = $('.livetab .js td');
    var _yp = _gq.eq(2).text() != '' ? _gq : _js;
    var _dx = _gq.eq(5).text() != '' ? _gq : _js;
    var _op = _gq.eq(8).text() != '' ? _gq : _js;

    //改变后最新的赔率
    var t_exp_home = oldOdds2.prev().find('div').eq(0).text();
    var t_exp = $.trim(oldOdds2.find('div').eq(0).text());
    var t_exp_away = oldOdds2.next().find('div').eq(0).text();
    var t_ball_home = oldOdds2.prev().find('div').eq(1).text();
    var t_ball = oldOdds2.find('div').eq(1).text();
    var t_ball_away = oldOdds2.next().find('div').eq(1).text();
    var t_europe_home = oldOdds2.prev().find('div').eq(2).text();
    var t_europe = oldOdds2.find('div').eq(2).text();
    var t_europe_away = oldOdds2.next().find('div').eq(2).text();

    //弹框的赔率
    var _t_exp_home = _yp.eq(1).find('div').text();
    var _t_exp_away = _yp.eq(3).find('div').text();
    var _t_ball_home = _dx.eq(4).find('div').text();
    var _t_ball_away = _dx.eq(6).find('div').text();
    var _t_europe_home = _op.eq(7).find('div').text();
    var _t_europe_away = _op.eq(9).find('div').text();

    if(t_exp != '封'){
        //亚盘
        if (t_exp_home != _t_exp_home) {
            _yp.eq(1).find('div').text(t_exp_home).addClass(t_exp_home > _t_exp_home ? 'up-red' : 'down-green');
        }
        _yp.eq(2).text(t_exp);
        if (t_exp_away != _t_exp_away) {
            _yp.eq(3).find('div').text(t_exp_away).addClass(t_exp_away > _t_exp_away ? 'up-red' : 'down-green');
        }
    }
    if(t_ball != '封'){
        //大小
        if (t_ball_home != _t_ball_home) {
            _dx.eq(4).find('div').text(t_ball_home).addClass(t_ball_home > _t_ball_home ? 'up-red' : 'down-green');
        }
        _dx.eq(5).text(t_ball);
        if (t_ball_away != _t_ball_away) {
            _dx.eq(6).find('div').text(t_ball_away).addClass(t_ball_away > _t_ball_away ? 'up-red' : 'down-green');

        }
    }
    if(t_europe != '封'){
        //欧盘
        if (t_europe_home != _t_europe_home) {
            _op.eq(7).find('div').text(t_europe_home).addClass(t_europe_home > _t_europe_home ? 'up-red' : 'down-green');
        }
        _op.eq(8).text(t_europe);
        if (t_europe_away != _t_europe_away) {
            _op.eq(9).find('div').text(t_europe_away).addClass(t_europe_away > _t_europe_away ? 'up-red' : 'down-green');
        }
    }
}

//让分中文显示
function handCpSpread(score) {
    if(score == '封'){
        return score;
    }
    if (score == '' || score == undefined) return '';
    var preTag = '';
    if (score.indexOf('-') >= 0) {
        preTag = "受";
        var score = score.split('-')[1];
    }

    if(score.indexOf('/') <= 0){
        score = parseFloat(score);
    }

    return preTag + sprScore[score];
}

//大小显示
function handCpTotal(score) {
    if(score == '封'){
        return score;
    }
    if (score == '' || score == undefined) return '';
    var num = Math.floor(score);
    var deci = score - num;
    if (deci == 0.25) {
        var score1 = num;
        var score2 = num + 0.5;
        return score1 + '/' + score2;
    }
    if (deci == 0.75) {
        var score1 = num + 0.5;
        var score2 = num + 1;
        return score1 + '/' + score2;
    }
    if(score.indexOf('/') <= 0){
        score = parseFloat(score);
    }
    return score;
}

//比赛进行多长时间
function showGoTime(startTime, status) {
    var pattern = /(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})/;
    var formatedDate = startTime.replace(pattern, '$1/$2/$3 $4:$5:$6');
    //当前时间戳
    var timestamp = Date.parse(new Date())/1000;
    var time   = Date.parse(new Date(formatedDate))/1000;
    var goMins = Math.floor((timestamp - time) / 60);
    switch (parseInt(status)) {
        case 1:
            if (goMins > 45)  goMins = "45+";
            if (goMins < 1)   goMins = "1";
            break;
        case 3:
            goMins += 46;
            if (goMins > 90)  goMins = "90+";
            if (goMins < 1)   goMins = "46";
            break;
    }
    return goMins;
}

//定时任务
setInterval(function () {
    // 定时刷新比赛分钟数
    $('.gameStatusStr time').each(function (idx, ele) {
        var status = $(this).parents('td').attr('game_state');
        var goMins = parseInt($(this).text().replace("+", "")) + 1;

        switch (status) {
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
}, 60000);


//显示隐藏直播icon
function showLiveIcon(data){
    console.log(data)
    var html = '<a target="_blank" href="/live/'+data.data.game_id+'.html?is_live=1"><img src="'+staticDomain+'/Public/Home/score/images/scoreLive/icon_live.png" width="24" height="24"></a>';
    var type = data.action;
    var tdIcon = $("tr[game_id="+data.data.game_id+"]").find('.liveIcon');
    if(type == 'show'){
        tdIcon.html(html);
    }else{
        tdIcon.empty();
    }
}