$(function () {
    var p = 2;// 初始化页面，点击事件从第二页开始
    var flag = false;
    if ($(".list").size() <= 0)
    {
        $("#loadMore").hide();
        $("#showLess").show();
    }
    $(document).on('click','#loadMore',function(){
        send();
    });
    function send() {
        if (flag) {
            return false;
        }
        flag=true;
        $("#loadMore").hide();
        $('.load_gif').show();
        var class_id = $("#class_id").val();
        $.ajax({
            type: 'post',
            url: "/News/loadMore_g.html",
            data: {k: p, class_id: class_id},
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    var list = data.info;
                    if (list != null) {
                            $.each(list, function (k, v) {
                                var img = v.images;
                                if (v.images == '') {
                                    img = '/Public/Mobile/images/default.png';
                                }
                                var html='<li class="list">'+
                                        "<a href='//" + DOMAIN + "/photo/" + v['id'] + ".html'>"+
                                        '<p><img src=' + img + ' alt=' + v.title + '></p>'+
                                        '<div class="g_tool clearfix"><h1 class="q-one">'+v.title+'</h1><em><img src="/Public/Mobile/images/view.png" alt="浏览量"><span>'+v.click_number+'</span></em></div>'+
                                        '</a></li>';
                                $(".posts li:last").after(html);
                            });
                        if (data.info.length < 20) {
                            $(".load_gif").hide();
                            $("#showLess").show();
                            flag = true;
                        }else{
                            $("#loadMore").show();
                            $('.load_gif').hide();
                            flag = false;
                        }
                    }
                } else {
                    $(".load_gif").hide();
                    $("#showLess").show();
                    flag = true;
                }

            }
        });
        p++;
    }
});