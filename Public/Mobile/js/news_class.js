$(function () {
    if ($(".premier li").size() <= 0)
    {
        $("#loadMore").hide();
        $("#showLess").show();
    }
});


var p = 2;// 初始化页面，点击事件从第二页开始
var flag = false;

$(document).on('click','#loadMore',function(){
    send();
});
function send() {
    if (flag) {
        return false;
    }
    $("#loadMore").hide();
    $('.load_gif').show();
    flag = true;
    var class_id = $("#class_id").val();
    $.ajax({
        type: 'post',
        url: "/News/loadMore.html",
        data: {k: p, class_id: class_id},
        dataType: 'json',
        success: function (data) {
            if (data.status == 1) {
                var list = data.info;
                if (list != null) {
                    if (class_id == 10) {
                        //名师
                        $.each(list, function (k, v) {
                            var img = v.img;
                            if (v.img == '') {
                                img = '/Public/Mobile/images/default_head.png';
                            }
                            var newsPath = v['is_original'] == 1 ? 'news' : 'info_n';
                            var html = getHtml(v);
                            $(".HotList li:last").after(html);
                        });
                    } else {
                        $.each(list, function (k, v) {
                            var img = v.img;
                            if (v.img == '') {
                                img = '/Public/Mobile/images/default.jpg';
                            }
                            var newsPath = v['is_original'] == 1 ? 'news' : 'info_n';
                            var html = getHtml(v);
                            $(".News li:last").after(html);
                        });
                    }
                    flag=false;
                    if (data.info.length < 20) {
                        $(".load_gif").hide();
                        $("#showLess").show();
                        flag = true;
                    }else{
                        $("#loadMore").show();
                        $('.load_gif').hide();
                    }
                }
            } else {
                $(".load_gif").hide();
                $("#showLess").show();
                flag = true;
            }

        },
    });
    p++;
}

function getHtml(v)
{
    var html = '<li>'+
        '<a class="clearfix" href="'+v.url+'" title="'+v.title+'">'+
        '<div class="left-part"><h2>'+v.title+'</h2>'+
        '<div class="Tit-t">'+
        '<span>'+v.add_time+'</span>'+
        '<span class="num click_number">'+v.click_number+'<img src="/Public/Mobile/images/eye-icon.png" alt="全球体育网"></span>'+
        '</div>'+
        '</div>'+
        '<div class="right-part"><img src="'+v.img+'" alt="'+v.title+'"></div>'+
        '</a>'+
        '</li>';
    return html;
}