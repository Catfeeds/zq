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
        Cookie.setCookie('Schedule'+cookieUa+_time, chioce);
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

var swiper = new Swiper('.swiper-container', {
    pagination: '.swiper-pagination',
    slidesPerView: 7,
    initialSlide :4,
    paginationClickable: true,
    spaceBetween: 30
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
                var showUnion = Cookie.getCookie('Schedule'+cookieUa+_time);
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
        }
    });
}

//处理html列表
function handleGame(data){
    var html = _url = _show = unionName = homeName = awayName = _11Html = _12Html = _13Html = _14Html = _23Html = _26Html = '';
    if(_language < 1){
        unionName = data[2][0];
        homeName = data[9][0];
        awayName = data[10][0];
    }else{
        unionName = data[2][1];
        homeName = data[9][1];
        awayName = data[10][1];
    }

    if(data[11] != ''){
        _11Html = '<span class="teamRank">['+data[11]+']</span>';
    }
    if(data[12] != ''){
        _12Html = '<span class="teamRank">['+data[12]+']</span>';
    }
    if(data[13] != ''){
        _13Html = 'style="display:none;"';
    }
    if(data[14] != ''){
        _14Html = 'style="display:none;"';
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
        _url = '/Details/data/scheid/'+data[0]+'.html';
    }
    html = '<div '+_show+' class="match js-data js-detail" data-url="'+_url+'" unionId="'+data[1]+'">' +
        '<div class="top">' +
        '<table class="table" width="100%" cellpadding="0" cellspacing="0">' +
        '<tr>' +
        '<td width="45%" class="q-tl"><span class="match_name" style="color:'+data[3]+'">'+unionName+'</span> <em class="mach_will_time">'+data[7]+'</em></td>' +
        '<td width="10%" class="mach_will_time">未开赛</td>' +
        '<td width="45%" class="q-tr tv_img"></td>' +
        '</tr>' +
        '</table>' +
        '</div>' +
        '<div class="bottom">' +
        '<table class="table" width="100%" cellpadding="0" cellspacing="0">' +
        '<tr>' +
        '<td width="45%" class="q-tr">' +
        _11Html +
        '<span class="homeTeamName">'+homeName+'</span>' +
        '</td>' +
        '<td width="10%"><span>VS</span></td>' +
        '<td width="45%" class="q-tl">' +
        '<span class="guestTeamName">'+awayName+'</span>' +
        _12Html +
        '</td>' +
        '</tr>' +
        '<tr>' +
        '<td width="45%" class="q-tr" '+_13Html+'>' +
        _23Html+
        '</td>' +
        '<td width="10%"></td>' +
        '<td width="45%" class="q-tl"  '+_14Html+'>' +
        _26Html+
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

