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
                                var html = '<li class="list clearfix">' +
                                        "<a href='//" + DOMAIN + "/"+newsPath+"/" + v['id'] + ".html'>" +
                                        '<div class="nh_top clearfix">' +
                                        "<aside><img src='" + img + "' alt='" + v.nick_name + "'></aside>" +
                                        '<div class="nht_right boxf">' +
                                        '<p><span>' + v.nick_name + '</span></p>' +
                                        '<h3>' + v.title + '</h3>' +
                                        '</div>' +
                                        '</div>' +
                                        '</a>' +
                                        '</li>';
                                $(".posts li:last").after(html);
                            });
                        } else {
                            $.each(list, function (k, v) {
                                var img = v.img;
                                if (v.img == '') {
                                    img = '/Public/Mobile/images/default.jpg';
                                }
                                var newsPath = v['is_original'] == 1 ? 'news' : 'info_n';
                                var html = '<li class="list clearfix">' +
                                        '<a href="//' + DOMAIN + "/"+newsPath+"/" + v['id'] + '.html">' +
                                        '<div class="n_img"><img src="' + img + '" alt="' + v.title + '"></div>' +
                                        '<div class="n_des">' +
                                        '<h3 class="overflow">' + v.title + '</h3>' +
                                        '<p class="q-two">' + v.source + '</p>' +
                                        '</div>' +
                                        '</a></li>';
                                $(".posts li:last").after(html);
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
});