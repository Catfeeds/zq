/**
 * Created by cytusc on 2018/1/16.
 */
/**
 * Created by cytusc on 2018/1/15.
 */
$(function () {
    (function ($) {
        $(window).load(function () {
            $("#content-1").mCustomScrollbar({
                theme: "minimal"
            });

        });
    })(jQuery);
    $('.icon_support_host').click(function (e) {
        $(this).animate({'background-position-x': '-50px', 'background-position-y': '-164px'}, 300);
        var url = '/index.php?m=home&c=video&a=ajax_up&type={$_GET[type]}&id={$_GET[id]}&host=0';
        $.get(url, function (res) {
            $("#home_up").html(res.home_up);
            $("#away_up").html(res.away_up);
            $("#away_score").html(res.away_score + '%');
            $("#home_score").html(res.home_score + '%');
        });
    });
    // ajax_comment();
});

$(".comment_load").on('click',function(){
    ajax_comment();
});


var p = 1;
var ajax_type = true;
function ajax_comment() {
    ajax_type = false;
    var id = jj_id;
    var time = comment_time;
    var get_status = false
    $.ajax({
        type: 'get',
        async : false,
        data: {'p': p,'id':id,'time':time},
        url: "/Video/getComment.html",
        dataType: 'json',
        success: function (data) {
            if(data.status == 200)
            {
                var res = data.data;
                if(res == null)
                {
                    $(".comment_load").css('display','none');
                    $(".comment_over").css('display','block');
                }else{
                    for (var i = 0;i<res.length;i++)
                    {
                        comment_html(res[i])
                    }
                    zan();
                    get_status = true;
                }
            }
        }
    });
    if(get_status)
    {
        p = p+1;
    }
    ajax_type = true;
}

function comment_html(data,type) {
    var html_type = type?type:1;
    var html = '';
    var like = data['like_num']?data['like_num']:0;
    var zan_color = '#b3b3b3';
    var zan_class = '';
    if(data['zan'] == 1)
    {
        zan_color = '#0182de';
        zan_class = 'on ';
    }
    html = '<li>' +
        '<a href="#"><img src="'+data.head+'"></a>' +
        '<div class="cont-top">' +
        '<div class="deta">' +
        '<div class="deta-o">' +
        '<span class="change" style="margin-right: 15px;">'+data.username+'</span>' +
        '<span>'+data.create_time+'</span>' +
        '</div>' +
        '<div class="deta-t">' +
        '<span value="'+data.id+'" class="'+zan_class+'zan_button glyphicon glyphicon-thumbs-up" style="color: '+zan_color+'; margin-right: 5px;"></span>' +
        ''+
        '<span style=" margin-right: 5px;">赞</span>' +
        '<span>'+like+'</span>' +
        '</div>' +
        '</div>' +
        '<div class="deta-l">' +
        '<span>'+data.filter_content+'</span>' +
        '</div>' +
        '</div>' +
        '</li>';
    if(html_type == 1)
    {
        $(html).insertBefore(".comment_load");
    }else{
        $("#comment").prepend(html);
    }
}

function zan(){
    $(".zan_button").on('click',function(){
        var _zan = $(this);
        if(_zan.hasClass('on') == false)
        {
            var id = $(this).attr('value');
            $.ajax({
                type: 'POST',
                async : false,
                data: {'id':id},
                url: "/Video/zanComment.html",
                dataType: 'json',
                success: function (data) {
                    layer.msg(data.msg);
                    if(data.status == 200)
                    {
                        _zan.css('color','#0182de');
                        var num = _zan.next().next().html();
                        _zan.next().next().html(parseInt(num)+1)
                    }
                }
            });
        }else{
            layer.msg('你已经赞过了!!');
        }
    });
}

$(".comment_button").on('click',function(){
    var content = $(".comment_value").val().replace(/(^\s*)|(\s*$)/g,'');
    if(content.length > 0)
    {
        $.ajax({
            type: 'POST',
            async : false,
            data: {'data':content,'id':jj_id},
            url: "/Video/saveComment.html",
            dataType: 'json',
            success: function (data) {
                if(data.status == 200)
                {
                    var res = data.data;
                    if(res != null)
                    {
                        comment_html(res,2);
                        zan();
                    }
                }
            }
        });
    }else{
        layer.msg('评论内容不能为空');
    }
});

$(".video_zan").on('click',function(){
    var _zan = $(this);
    if(_zan.hasClass('on') == false)
    {
        var id = $(this).attr('value');
        $.ajax({
            type: 'POST',
            async : false,
            data: {'id':id},
            url: "/Video/zanVideo.html",
            dataType: 'json',
            success: function (data) {
                layer.msg(data.msg);
                if(data.status == 200)
                {
                    _zan.css('color','#0182de');
                    var num = _zan.next().html();
                    _zan.next().html(parseInt(num)+1)
                }
            }
        });
    }else{
        layer.msg('你已经赞过了!!');
    }
});
