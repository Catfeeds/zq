$(function () {

    //获取列表
    getList();

    var scrollTop = Cookie.getCookie('scrollTop');
    if (scrollTop) {
        $("html, body").animate({scrollTop: scrollTop}, 500);
        Cookie.delCookie('scrollTop');
    }
    $("#day_sele").on('change', function () {
        location.href = "?date=" + $(this).val();
    });
    //确定
    $("#sele_confirm").click(function () {
        if($(".subnav_list a.on").length<1){
            alert('至少选择一项!');
            return false;
        }
        var chioce = '';
        $('.liveList>div').css('display','none');
        $(".subnav_list a").each(function () {
            $this = $(this);
            if ($this.hasClass('on')) {
                chioce += $this.data('key') + ',';
                $('div[unionId='+$this.data('key')+']').css('display','');
            }
        });
        chioce = chioce.substring(0, chioce.length - 1);
        Cookie.setCookie('ScheRes'+cookieUa+_time, chioce);
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
        url:'/ScheduleResult/getGameList.html',
        data: {"date": _time},
        cache: false,
        success: function (data) {
            if(data.status == 1){
                var showUnion = Cookie.getCookie('ScheRes'+cookieUa+_time);
                if(showUnion != null){
                    showUnionId = showUnion.split(',');
                }
                var listHtml = unionHtml = '';
                for(var i = 0;i<data.data.length;i++){
                    listHtml = listHtml + handleGame(data.data[i]);
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

//处理html列表
function handleGame(data){
    var html = _show = _url = unionName = homeName = awayName = _19Html = _17Html = _11Html = _12Html = _20Html = _18Html = _23Html = _26Html = '';
    if(_language  < 1){
        unionName = data[2][0];
        homeName = data[9][0];
        awayName = data[10][0];
    }else{
        unionName = data[2][1];
        homeName = data[9][1];
        awayName = data[10][1];
    }

    if(data[19] != 0){
        _19Html = '<span class="yel_card">'+data[19]+'</span>';
    }
    if(data[17] != 0){
        _17Html = '<span class="red_card">'+data[17]+'</span>';
    }
    if(data[11] != ''){
        _11Html = '<span class="teamRank">['+data[11]+']</span>';
    }
    if(data[12] != ''){
        _12Html = '<span class="teamRank">['+data[12]+']</span>';
    }
    if(data[20] != 0){
        _20Html = '<span class="yel_card">'+data[20]+'</span>';
    }
    if(data[18] != 0){
        _18Html = '<span class="red_card">'+data[18]+'</span>';
    }
    if(data[23] != ''){
        _23Html = '<div class="odds rf">' +
            '<span class="oddsType">让</span>' +
            '<span class="addsNub">'+data[23]+'</span>' +
            '<span class="addsPankou">'+data[24]+'</span>' +
            '<span class="addsNub">'+data[25]+'</span>' +
            '</div>';
    }
    if(data[26] != ''){
        _26Html = '<div class="odds dx">' +
            '<span class="oddsType">大</span>' +
            '<span class="addsNub">'+data[26]+'</span>' +
            '<span class="addsPankou">'+data[27]+'</span>' +
            '<span class="addsNub">'+data[28]+'</span>' +
            '</div>';
    }
    if(showUnionId != ''){
        if($.inArray(data[1],showUnionId) == -1 ){
            _show = 'style="display:none;"';
        }
    }
    if(cookieUa == '_f'){
        _url = 'javascript:void(0);';
    }else{
        _url = '/Details/event_technology/scheid/'+data[0]+'.html';
    }
    html = '<div '+_show+' class="match  js-data js-detail"  data-url="'+_url+'" unionId="'+data[1]+'">' +
        '<div class="top">' +
        '<table class="table" width="100%" cellpadding="0" cellspacing="0">' +
        '<tr>' +
        '<td width="45%" class="q-tl"><span class="match_name" style="color:' + data[3] + '">' +
        unionName +
        '</span> <em class="mach_will_time">' + data[7] + '</em></td>' +
        '<td width="10%" class="mach_over_time">完场</td>' +
        '<td width="45%" class="q-tr tv_img"></td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '<div class="bottom">' +
        '<table class="table" width="100%" cellpadding="0" cellspacing="0">' +
        '<tr>' +
        '<td width="45%" class="q-tr">' +
        _19Html + _17Html + _11Html +
        '<span class="homeTeamName">' + homeName + '</span>' +
        '</td>' +
        '<td width="10%"><span class="mach_over js-score">' + data[13] + '-' + data[14] + '</span></td>' +
        '<td width="45%" class="q-tl">' + awayName + _12Html + _20Html + _18Html +
        '</td>' +
        '</tr>' +
        '<tr>' +
        '<td width="45%" class="q-tr">' + _23Html +
        '</td>' +
        '<td width="10%"><em class="mach_half">(' + data[15] + '-' + data[16] + ')</em></td>' +
        '<td width="45%" class="q-tl">' + _26Html +
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

