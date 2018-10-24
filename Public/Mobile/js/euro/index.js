$(function () {
    $("#auto_load").val(1);
    var p = 2;// 初始化页面，点击事件从第二页开始
    var flag = false;
    //加载
    if ($(".js-list").size() < 5)
    {
        $("#auto_load").val(0);
        $(".load_gif").hide();
        $("#showLess").show();
    }
    if ($(document).height() - ($(document).scrollTop() + $(window).height()) <= 400) {
        var auto_load = $("#auto_load").val();
        if (auto_load == 1) {
            $("#auto_load").val(0);
            send();
        }
    }
    $(window).scroll(function () {
        var auto_load = $("#auto_load").val();
        if (auto_load == 0) {
            return false;
        }
        //初始状态，如果没数据return ,false;否则
        if ($(document).height() - ($(document).scrollTop() + $(window).height()) <= 400) {
            if (auto_load == 1) {
                $("#auto_load").val(0);
                send();
            }
        }
    });
    function send() {
        if (flag) {
            return false;
        }
        $.ajax({
            type: 'post',
            url: "/Euro/hot_load.html",
            data: {k: p},
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    var list = data.info;
                    if (list != null) {
                        $.each(list, function (k, v) {
                            var img = v.img;
                            if (v.img == '' || v.img == null) {
                                img = '/Public/Mobile/images/default.jpg';
                            }
                            var html = '<li class="list js-list clearfix">' +
                                    '<a href="//m.' + DOMAIN + "/Euro/hot_detail/id/" + v['id'] + '.html">' +
                                    '<div class="n_img"><img src="' + img + '" alt="' + v.short_title + '"></div>' +
                                    '<div class="n_des">' +
                                    '<h3 class="q-one">' + v.short_title + '</h3>' +
                                    '<p class="q-two">' + v.remark + '</p>' +
                                    '</div></a></li>';
                            $("#news_list li:last").after(html);
                        });
                    }
                    $("#auto_load").val(1);
                    if (data.info.length < 5) {
                        $("#auto_load").val(0);
                        $(".load_gif").hide();
                        $("#showLess").show();
                        flag = true;
                    }
                } else {
                    $("#auto_load").val(0);
                    $(".load_gif").hide();
                    $("#showLess").show();
                    flag = true;
                }
            }
        });
        p++;
    }

    $(document).on('click', '.js-event', function () {
        location.href = $(this).data('href');
    });

});