/**
 * Created by cytusc on 2018/8/9.
 */
var doc=document;
var _wheelData=-1;
var mainBox=doc.getElementById('mainBox');
function bind(obj,type,handler){
    var node=typeof obj=="string"?$(obj):obj;
    if(node.addEventListener){
        node.addEventListener(type,handler,false);
    }else if(node.attachEvent){
        node.attachEvent('on'+type,handler);
    }else{
        node['on'+type]=handler;
    }
}


$(function(){
    var btns = document.querySelectorAll('.copyButton');
    var clipboard = new Clipboard(btns);

    clipboard.on('success', function(e) {
        showMsg('复制成功', 0, 'success');
    });

    clipboard.on('error', function(e) {
        showMsg('复制失败', 0, 'error');
    });
    var int=self.setInterval('saveImg()',5000);
    var liveStatus=self.setInterval('getLiveStatus()',5000);
});

$('.gameListButton').on('click',function(){
    $('.box-list').fadeIn(300);
    $('.userid').prop('checked','');
    $(".gameList tr:visible").each(function() {
        var tmp = $(this);
        $('#menu_list input[value='+tmp.attr('union_id')+']').prop('checked',true);
    });
});
//全选操作
$('#checkAll').on('click',function(){
    $('#menu_list input[class=userid]').prop('checked',true);
});
//反选操作
$("#reverse").on('click', function () {
    $(".userid").each(function() {
        var tmp = $(this);
        if ($(this).is(':checked')) {
            tmp.prop('checked','');
        } else {
            tmp.prop('checked',true);
        }
    });
});
//取消
$('#removeAll').click(function (e) {
    $('.box-list').fadeOut(300);
});
//确定
$("#ensure").on('click', function () {
    //判断是否有勾选联赛
    var is_check = false;
    $('#menu_list input[class=userid]:checkbox').each(function () {
        if ($(this).is(':checked')) {
            is_check = true;
        }
    });
    if (!is_check) {
        showMsg('请选择联赛！', 0, 'error');
        return;
    }
    $('.gameList tr').css('display','none');
    $(".userid").each(function() {
        var tmp = $(this);
        if (tmp.is(':checked')) {
            var union_id = tmp.attr('value');
            $('.gameList tr[union_id='+union_id+']').css('display','');
        }
    });
    $('.ischeck').parents('tr').css('display','');
    $('.box-list').fadeOut(300);
});

//點擊我要開播
$('.click button').on('click',function(){
    var _button = $(this);
    if(_button.hasClass('right')){
        //结算直播操作
        var state = stopLive();
        _button.html('我要开始了');
        _button.removeClass('right');
        $('.startStop').css('display','none');
        $('.liveRadio').css('display','none');
        $('.saveTitle').css('display','none');
        $('.request').html('');
        $('.titleInput').val('');
        $('.long a').html('');
        $('.roomId').html('');
        $('.copyButton').attr('data-clipboard-text','');
        $('.checkLive a').removeClass('ischeck').addClass('nocheck');
        showMsg('直播已结束!', 0, 'success');
    }else{
        //直播开始操作
        var state = startLive();
        if(!state){
            return true;
        }
        _button.html('直播结束');
        _button.addClass('right');
        $(".startStop img").attr('src','/Public/Home/images/click.png');
        $('.startStop').removeClass('isstart').addClass('isstop');
        $('.startStop').css('display','');
        $('.liveRadio').css('display','');
        $('.saveTitle').css('display','');
        showMsg('即将开播，请将直播链接粘贴至OBS～!', 0, 'success');
    }
});

//暫停繼續按鈕
$('.startStop').on('click',function(){
    var tmp = $(this);
    var liveStatus = 0;
    $.ajax({
        type: "get",
        url:"/UserInfo/stopLive.html",
        cache: false,
        async: false,
        success: function (data) {
            if(data.code==200)
            {
                var msg = '直播已暂停!'
                if(data.data == 1)
                {
                    $(".startStop img").attr('src','/Public/Home/images/click.png');
                    tmp.removeClass('isstart').addClass('isstop');
                    msg = '直播正在继续!';
                }else{
                    $(".startStop img").attr('src','/Public/Home/images/start.png');
                    tmp.removeClass('isstop').addClass('isstart');
                }
                liveStatus = data.data;
                showMsg(msg, 0, 'success');
            }else{
                showMsg(data.msg, 0, 'error');
            }
        }
    });
    if(liveStatus > 0){
        $.ajax({
            type: "get",
            url:"/UserInfo/mqttStopLive.html",
            cache: false,
            success: function (data) {
            }
        });
    }
});


//關聯賽事
$('.checkLive').on('click',function(){
    $('.topright').html('');
    if($(this).find('a').hasClass('ischeck')){
        $('.checkLive a').removeClass('ischeck').addClass('nocheck');
    }else{
        $('.checkLive a').removeClass('ischeck').addClass('nocheck');
        $(this).find('a').removeClass('nocheck').addClass('ischeck');
        var home_name = $(this).siblings('.four').html();
        var away_name = $(this).siblings('.five').html();
        var name = home_name+' vs '+away_name;
        $('.topright').html(name);
    }
    updataLive(2);

});

//监控直播间名输入字数
$('.titleInput').on('input',function(){
    $('.titleDiv .before-wore').html($('.titleInput').val().length);
});
//监控直播间名输入字数
$('.liveAd').on('input',function(){
    $('.impose .left').html($('.liveAd').val().length);
});

//上传图片js
$('.add .liveImage').hover(function(e) {
    $(this).children('div').stop().animate({'top':'0'},300);
},function(){
    $(this).children('div').stop().animate({'top':'120px'},300);
});

//提交直播参数
function startLive(){
    var state = true;
    var img = $('#viewUploadImg_77').attr('src');
    var title = $('.titleInput').val();
    var game_id = $('.ischeck').parents('tr').attr('game_id');
    if(img == undefined)
    {
        showMsg('请上传封面图！', 0, 'error');
        state =  false;
    }
    if(title.length < 1)
    {
        showMsg('请填写直播标题！', 0, 'error');
        state =  false;
    }

    if(state){
        if(game_id == undefined)
        {
            game_id = 0;
        }
        $.ajax({
            type: "POST",
            url:"/UserInfo/startLive.html",
            data: {"gameId": game_id,'title':title,'img':img},
            cache: false,
            async: false,
            success: function (data) {
                if(data.code==200)
                {
                    $('.long a').html(data.url);
                    $('.copyButton').attr('data-clipboard-text',data.url);
                    $('.roomId').html(data.room_id);
                }else{
                    showMsg(data.msg, 0, 'error');
                    state =  false;
                }
            }
        });
    }
    RoomImg = img;
    return state;
}

//停止直播
function stopLive(){
    var res = true;
    $.ajax({
        type: "POST",
        url:"/UserInfo/overLive.html",
        cache: false,
        async: false,
        success: function (data) {
            if(data.code!=200)
            {
                res = false;
            }
        }
    });
    return res;
}

//信息重發
$('.reSend').on('click',function(){
    reSend($(this));
});

//信息重發處理
function reSend(obj){
    var tmp = obj.parent('div');
    var adId = tmp.attr('adId');
    $.ajax({
        type: "POST",
        url:"/UserInfo/reSend.html",
        data: {'id':adId},
        cache: false,
        async: false,
        success: function (data) {
            if(data.code==200)
            {
                showMsg(data.msg, 0, 'success');
            }else{
                showMsg(data.msg, 0, 'error');
            }
        }
    });
}

//信息重發
$('.delMsg').on('click',function(){
    delMsg($(this));
});

//信息删除處理
function delMsg(obj){
    var tmp = obj.parent('div');
    var adId = tmp.attr('adId');
    $.ajax({
        type: "POST",
        url:"/UserInfo/delMsg.html",
        data: {'id':adId},
        cache: false,
        async: false,
        success: function (data) {
            if(data.code==200)
            {
                showMsg(data.msg, 0, 'success');
                tmp.remove();
            }else{
                showMsg(data.msg, 0, 'error');
            }
        }
    });
}

// /消息发送
$('.toSend').on('click',function(){
    var text = $('.liveAd').val();
    if(text.trim().length > 0){
        $.ajax({
            type: "get",
            url:"/UserInfo/toSend.html",
            data: {'msg':text},
            cache: false,
            async: false,
            success: function (data) {
                if(data.code==200)
                {
                    showMsg(data.msg, 0, 'success');
                    var html = '<div adId="'+data.key+'"  class="request-box">'+
                        '<span class="worlds"><p>'+text+'</p></span>'+
                        '<span class="button reSend">再次发送</span>' +
                        '<span class=" delMsg">删除记录</span>'+
                        '</div>';
                    $('.request').append(html);
                    $('.delMsg').unbind("click");
                    $('.reSend').unbind("click");
                    $('.delMsg').on('click',function(){
                        delMsg($(this));
                    });
                    $('.reSend').on('click',function(){
                        reSend($(this));
                    });
                }else{
                    showMsg(data.msg, 0, 'error');
                }
            }
        });
    }
});

//直播中修改标题
$('.saveTitle').on('click',function(){
    updataLive(1);
});

//直播中修改操作
function updataLive(type){
    if($('.click button').hasClass('right')){
        var param = {};
        switch(type){
            case 1:
                param['title'] = $('.titleInput').val();
                break;
            case 2:
                var game_id = $('.ischeck').parents('tr').attr('game_id');
                if(game_id == undefined)
                {
                    game_id = -1;
                }
                param['game_id'] = game_id;
                break;
        }
        $.ajax({
            type: "get",
            url:"/UserInfo/updataLive.html",
            data: param,
            cache: false,
            async: false,
            success: function (data) {
                if(data.code==200)
                {
                    showMsg(data.msg, 0, 'success');
                }else{
                    showMsg(data.msg, 0, 'error');
                }
            }
        });
    }
}


//定时器监听封面图更新
function saveImg(){
    var nowImg = $('#viewUploadImg_77').attr('src');
    if(!(nowImg == RoomImg)){
        $.ajax({
            type: "POST",
            url:"/UserInfo/saveLiveUserImg.html",
            data: {'img':nowImg},
            cache: false,
            async: false,
            success: function (data) {
            }
        });
        RoomImg = nowImg;
    }
}

//裁剪弹窗
$('.openpops').click(function(){
    var nowImg =$('#viewUploadImg_77').attr('src');
   $('.pops').css('display','block')
   var isMyFaceUrl = $("#myFaceUrl").val();
   isMyFaceUrl=nowImg;
   $('.imageBox').css('background-image',"url(" + nowImg + ")")

})
$('.btn-close').click(function(){
    $('.pops').css('display','none')
})

//轮训当前直播状态
function getLiveStatus(){
    if($('.startStop').is(':visible')){
        $.ajax({
            type: "POST",
            url:"/UserInfo/getLiveStatus.html",
            cache: false,
            async: false,
            success: function (data) {
                switch(data.code){
                    case 200:
                        if(data.status == 1){
                            $(".startStop img").attr('src','/Public/Home/images/click.png');
                            $('.startStop').removeClass('isstop').addClass('isstart');
                        }else{
                            $(".startStop img").attr('src','/Public/Home/images/start.png');
                            $('.startStop').removeClass('isstart').addClass('isstop');
                        }
                        break;
                    case 305:
                        $('.click button').html('我要开始了');
                        $('.click button').removeClass('right');
                        $('.startStop').css('display','none');
                        $('.liveRadio').css('display','none');
                        $('.saveTitle').css('display','none');
                        $('.request').html('');
                        $('.titleInput').val('');
                        $('.long a').html('');
                        $('.roomId').html('');
                        $('.copyButton').attr('data-clipboard-text','');
                        $('.checkLive a').removeClass('ischeck').addClass('nocheck');
                        showMsg('直播已结束!', 0, 'success');
                        break;
                }
            }
        });
    }

}