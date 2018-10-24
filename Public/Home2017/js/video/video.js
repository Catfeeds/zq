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
    //点赞支持
    $('.icon_support_visit').click(function (e) {
        $(this).animate({'background-position-x': '2px', 'background-position-y': '-164px'}, 300);
        var url = '/index.php?m=home&c=video&a=ajax_up&type={$_GET[type]}&id={$_GET[id]}&host=1';
        $.get(url, function (res) {
            $("#home_up").html(res.home_up);
            $("#away_up").html(res.away_up);
            $("#away_score").html(res.away_score + '%');
            $("#home_score").html(res.home_score + '%');
        });
    });
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
    //锦集列表
    $('.live-content ul li').click(function (e) {
        $(this).addClass('on').siblings().removeClass('on');
        var url = $(this).data('url');
        var id = $(this).data('id');
        var title = $(this).find('dt').text();
        var remark = $(this).data('remark');
        var html = '<embed allowfullscreen="true" allowscriptaccess="always" bgcolor="#000000" width="720" height="500" id="ply"name="ply" quality="high" salign="lt"' +
            'src="' + url + '"' +
            'type="application/x-shockwave-flash" wmode="opaque"></embed>';
        $("#Player").html(html);
        $('.title h3').text(title);
        $('.ticle_box p').text(remark);
        //更新播放量
        var urls = '/index.php?m=home&c=video&a=ajax_click&id=' + id;
        $.get(urls);
    });
    ajax_comment();
});

$(".comment_load").on('click',function(){
    ajax_comment();
});


var p = 1;
var ajax_type = true;
function ajax_comment() {
    ajax_type = false;
    var id = $('#video_id').attr('val');
    var time = $('#comment_time').attr('val');
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

function comment_html(data) {
    var html = '';
    html = '<li>' +
        '<a href="#"><img src="'+data.head+'"></a>' +
        '<div class="cont-top">' +
        '<div class="deta">' +
        '<div class="deta-o">' +
        '<span class="change">'+data.username+'</span>' +
        '<span>'+data.create_time+'</span>' +
        '</div>' +
        '<div class="deta-t">' +
        '<span class="icon- icon-thumbs-up"></span>' +
        '<span>赞</span>' +
        '</div>' +
        '</div>' +
        '<div class="deta-l">' +
        '<span>'+data.filter_content+'</span>' +
        '</div>' +
        '</div>' +
        '</li>';
    $(html).insertBefore(".comment_load");
}
