$(function () {
    //获取列表
    getList();
    var scrollTop = Cookie.getCookie('scrollTop');
    if (scrollTop) {
        $("html, body").animate({scrollTop: scrollTop}, 500);
        Cookie.delCookie('scrollTop');
    }
    var color = $(".color_tips");
    var score = $(".score_tips");
    //如果上面【红牌黄牌提示】显示并且下面【进球提示】不显示：位置下移
    if (!color.is(":hidden") && score.is(":hidden")) {
        color.css("top", "-.28rem");
    }
    //确定
    $("#sele_confirm").click(function () {
        var chioce = '';
        if($(".subnav_list a.on").length<1){
            alert('至少选择一项!');
            return false;
        }
        $(".subnav_list a").each(function () {
            $this = $(this);
            if ($this.hasClass('on')) {
                chioce += $this.data('key') + ',';
                $('div[union_id='+$this.data('key')+']').css('display','');
            }
        });
        chioce = chioce.substring(0, chioce.length - 1);
        Cookie.setCookie('M_Now'+cookieUa, chioce);
        $("#zhishu_event,#nav_sele").hide();
        // window.location.reload();
    });

    $('.m_rule').on('click',function(){
        var hiddenNum = 0;
        $('.subnav_list>a').each(function(){
            if(!$(this).hasClass('on')){
                hiddenNum = hiddenNum+1;
            }
        });
        $('#shai_hide').html(hiddenNum);
        if(hiddenNum === 0){
            $('.subnav_level a').addClass('on');
        }
    })
});

var showUnionId = '';
//异步加载赛事列表
function getList(){
    $.ajax({
        type: "get",
        url:'/Sporttery/getGameList.html',
        cache: false,
        success: function (data) {
            if(data.status == 1){
                var showUnion = Cookie.getCookie('M_Now'+cookieUa);
                if(showUnion != null){
                    showUnionId = showUnion.split(',');
                }
                var listHtml = unionHtml = '';
                for(var i = 0;i<data.data.length;i++){
                    listHtml = listHtml + handleGame(data.data[i],i);
                }
                $('.liveList').append(listHtml);
                unionHtml = handleUnion(data.union);
                $('.subnav_list').append(unionHtml);
                $(".subnav_list a").on('click',function(){
                    if($(this).hasClass('on')){
                        $(this).removeClass('on');
                    }else{
                        $(this).addClass('on');
                    }
                });
                if(showUnionId == ''){
                    $('.subnav_level a').addClass('on');
                }

                $('#rankListMore').css('display','none');
            }
            console.log(data,showUnionId)
        }
    });
}

//处理赛事列表
function handleGame(v, k) {
    var html = _show = _5Html = unionName = homeName = awayName = gameSataeHtml = _quanjiaoHtml = _11Html = _12Html = _23Html = _24Html = _25Html = _26Html = _vsHtml = _17Html = _15Html = _20Html = '';
    v[5] = parseInt(v[5]);
    switch (v[5]) {
        case 1:
        case 2:
        case 3:
        case 4:
            _5Html = '/Details/event_case/scheid/' + v[0] + '.html';
            break;
        case -1:
            _5Html = '/Details/event_technology/scheid/' + v[0] + '.html';
            break;
        default:
            _5Html = '/Details/data/scheid/' + v[0] + '.html';
    }
    if (_language < 1) {
        unionName = v[2][0];
        homeName = v[9][0];
        awayName = v[10][0];
    } else {
        unionName = v[2][1];
        homeName = v[9][1];
        awayName = v[10][1];
    }

    switch (v[5]) {
        case 0:
        case -10:
        case -11:
        case -12:
        case -13:
        case -14:
            gameSataeHtml = '<td width="10%" class="js_mach_time mach_will_time">' + v['gameStateText'] + '</td>';
            _vsHtml = '<span class="js-score">VS</span>';
            break;
        case 1:
        case 2:
        case 3:
        case 4:
            _vsHtml = '<span class="mach_begin js-score">' + v[13] + '-' + v[14] + '</span>';
            gameSataeHtml = '<td width="10%" class="js_mach_time mach_begin_time">' + v['gameStateText'] + '</td>';
            break;
        case -1:
            _vsHtml = '<span class="mach_over">' + v[13] + '-' + v[14] + '</span>';
            gameSataeHtml = '<td width="10%" class="js_mach_time mach_begin_time">' + v['gameStateText'] + '</td>';
            break;
        default:
            gameSataeHtml = '<td width="10%" class="js_mach_time mach_begin_time">' + v['gameStateText'] + '</td>';
            _vsHtml = '<span class="js-score">VS</span>';
    }

    if (v[23] < 1) {
        _23Html = 'style="display:none"';
    }
    if (v[24] < 1) {
        _24Html = 'style="display:none"';
    }
    if (v[25] < 1) {
        _25Html = 'style="display:none"';
    }
    if (v[26] < 1) {
        _26Html = 'style="display:none"';
    }
    if (v[11] != '') {
        _11Html = '<span class="teamRank" style="display:none;">[' + v[11] + ']</span>';
    }

    if (v[12] != '') {
        _12Html = '<span class="teamRank" style="display:none;">[' + v[12] + ']</span>';
    }
    if (v[17] == '') {
        _17Html = 'style="display:none;"';
    }
    if (v[20] == '') {
        _20Html = 'style="display:none;"';
    }
    if (v[15] == '') {
        if(v[5] > 0){
            _15Html = '<td width="10%" class="mach_half">(0:0)</td>';
        }else{
            _15Html = '<td width="10%" class="mach_half"></td>';
        }
    } else {
        _15Html = '<td width="10%" class="mach_half">(' + v[15] + ':' + v[16] + ')</td>';
    }

    //完场或异常的不用变化
    if ($.inArray(v[5], [-1, 1, 2, 3, 4]) > -1) {
        _quanjiaoHtml = '全角[<span class="home_corner">' + v[27] + '</span>-<span class="away_corner">' + v[28] + '</span>]'
    }

    if(showUnionId != ''){
        if($.inArray(v[1],showUnionId) == -1 ){
            _show = 'style="display:none;"';
        }
    }
    if(cookieUa == '_f'){
        _5Html = 'javascript:void(0);';
    }
    html = '<div '+_show+' id="scheid' + v[0] + '" class="match js-data live_now ios_touch" data-key="' + k + '" game_id="' + v[0] + '" data-id="' + v[0] + '" data-time="' + v[6] + ' ' + v[7] + '" data-status="' + v[5] + '" data-url="' + _5Html + '"union_id="'+v[1]+'">' +
        '<div class="top">' +
        '<table class="table" width="100%" cellpadding="0" cellspacing="0">' +
        '<tr>' +
        '<td width="8%" ><a href="javascript:;" style="display: block;"  class="star_img">&nbsp;</a></td>' +
        '<td width="37%" class="q-tl"><span class="match_name" style="color:' + v[3] + '">' + unionName + '</span> <em class="mach_will_time">' + v[7] + '</em></td>' +
        gameSataeHtml +
        '<td width="45%" class="corner_box q-tl jq" >' + _quanjiaoHtml + '</td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '<div class="bottom">' +
        '<table class="table" width="100%" cellpadding="0" cellspacing="0">' +
        '<tr>' +
        '<td width="45%" class="q-tr">' +
        '<span id="js-home-ycard" class="yel_card js-home-ycard" ' + _25Html + '>' + v[25] + '</span>' +
        '<span id="js-home-rcard" class="red_card js-home-rcard" ' + _23Html + '>' + v[23] + '</span>' +
        _11Html +
        '<span class="homeTeamName">' + homeName + '</span>' +
        '</td>' +
        '<td width="10%">' +
        _vsHtml +
        '</td>' +
        '<td width="45%" class="q-tl">' +
        '<span class="guestTeamName">' + awayName + '</span>' +
        _12Html +
        '<span id="js-guest-ycard" class="yel_card js-guest-ycard" ' + _26Html + '>' + v[26] + '</span>' +
        '<span id="js-guest-rcard" class="red_card js-guest-rcard" ' + _24Html + '>' + v[24] + '</span>' +
        '</td>' +
        '</tr>' +
        '<tr>' +
        '<td width="45%" class="q-tr" >' +
        '<div class="odds rf" ' + _17Html + '>' +
        '<span class="oddsType">让</span>' +
        '<span   class="addsNub js-home-asian">' + v[17] + '</span>' +
        '<span  class="addsPankou js-all-asian">' + v[18] + '</span>' +
        '<span class="addsNub js-away-asian">' + v[19] + '</span>' +
        '</div>' +
        '</td>' +
        _15Html +
        '<td width="45%" class="q-tl">' +
        '<div class="odds dx"  ' + _20Html + '>' +
        '<span class="oddsType">大</span>' +
        '<span  class="addsNub js-home-ball">' + v[20] + '</span>' +
        '<span  class="addsPankou js-all-ball">' + v[21] + '</span>' +
        '<span  class="addsNub js-away-ball">' + v[22] + '</span>' +
        '</div>' +
        '</td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '</div>';
    return html;
}



//处理联赛列表
function handleUnion(data){
    var html = '';
    $.each(data,function(key,val){
        $.each(val,function(k,v){
            var _show = 'on';
            if(showUnionId != ''){
                if($.inArray(k,showUnionId) == -1 ){
                    _show = '';
                }
            }
            html = html + '<a id="schetype'+k+'" href="javascript:;" data-level="'+key+'"  data-key="'+k+'" class="leagus'+(key-1)+' '+_show+'">'+v+'</a>';
        })
    });
    return html;
}

//动态监听topic，接收数据处理
//也可以动态添加topic MqInit.subscribeTopic(['aa/bb']);
MqInit.onMessage(function (topic, message) {
    var data = message;
    if (topic.indexOf('fb/goal') > -1 && topic.indexOf('fb/goalpsw') < 0) {//足球全场赔率
        getGameOdds(data);
    }else if (topic.indexOf('/fb/change') > -1) {//比分变化
        gameChange(data);
    } else if (topic.indexOf('/fb/gamelist') > -1) {//赛事变化
        $('#rankListMore').css('display','');
        $('.liveList').empty();
        $('.subnav_list').empty();
        getList();
    }
}, ['qqty/api500/fb/goal', 'qqty/api500/fb/goalpsw', 'qqty/api500/fb/change', 'qqty/api500/fb/gamelist','qqty/live/notify']);


//赔率变化
function getGameOdds(payload){
    var temp = JSON.parse(payload);
    if(temp.status != 1) return false;
    var data = temp['data'];
    $.each(data, function (k, v) {
        var game_id = k;
        var obj = $('div[game_id='+game_id+']');
        if (obj.length == 0) return true;
        var scoreHtml = obj.find('.mach_half').html();
        var html = '<td width="45%" class="q-tr">' +
            '<div class="odds rf">' +
            '<span class="oddsType">让</span><span class="addsNub js-home-asian">'+v[1]+'</span>' +
            '<span class="addsPankou js-all-asian">'+v[0]+'</span>' +
            '<span class="addsNub js-away-asian">'+v[2]+'</span>' +
            '</div>' +
            '</td>' +
            '<td width="10%" class="mach_half">'+scoreHtml+'</td>' +
            '<td width="45%" class="q-tl">' +
            '<div class="odds dx">' +
            '<span class="oddsType">大</span>' +
            '<span class="addsNub js-home-ball">'+v[7]+'</span>' +
            '<span class="addsPankou js-all-ball">'+v[6]+'</span>' +
            '<span class="addsNub js-away-ball">'+v[8]+'</span>' +
            '</div>' +
            '</td>'
        obj.find('tbody tr').eq(2).html(html);
    });
}

//比分变化
function gameChange(payload){
    var temp = JSON.parse(payload);
    if(temp.status != 1) return false;
    var data = temp['data'];
    $.each(data, function (k, v) {
        var game_id = k;
        var obj = $('div[game_id='+game_id+']');
        var newStatus = v[1];
        if (obj.length == 0) return true;
        var scoreHtml = '('+v[2]+':'+v[3]+')';
        obj.find('tbody tr').eq(2).find('.mach_half').html(scoreHtml);
        //红牌处理
        if(v[6] > 0){
            obj.find('#js-home-rcard').css('display','none').html(v[6]);
        }
        if(v[7] > 0){
            obj.find('#js-guest-rcard').css('display','none').html(v[7]);
        }
        //黄牌
        if(v[8] > 0){
            obj.find('#js-home-ycard').css('display','none').html(v[8]);
        }
        if(v[9] > 0){
            obj.find('#js-guest-ycard').css('display','none').html(v[9]);
        }
        //赛事的比赛状态或比赛时间
        var statusStr = '';
        switch (newStatus) {
            case '1':
            case '3':
                var goTime = showGoTime(v[11], newStatus);
                statusStr = '<time>' + goTime + '</time>\'';
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
        obj.find('.mach_begin_time').html(statusStr);

        if(v[12] > 0 || v[13] > 0)
        {
            quanjiaoHtml = '全角[<span class="home_corner">' + v[12] + '</span>-<span class="away_corner">' + v[13] + '</span>]'
            obj.find('.jq').html(quanjiaoHtml);
        }
        if(v[1] < 0){
            if(!obj.find('.star_img').hasClass('star_img_on')){
                $('.liveList').append(obj.clone());
                obj.remove();
            }
        }
    });
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

