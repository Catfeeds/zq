$(function () {
    $("#auto_load").val(1);
    var p = 2;// 初始化页面，点击事件从第二页开始
    var flag = false;
    //初始状态，如果没数据return ,false;否则
    if ($(".js-list").size() < 6)
    {
        $("#auto_load").val(0);
        $(".load_gif").hide();
        $("#showLess").show();
        return false;
    }
    //加载
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
            url: "/Euro/photo_load.html",
            data: {k: p},
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    var list = data.info;
                    if (list != null) {
                        $.each(list, function (k, v) {
                            var html = '<li class="js-list">' +
                                    '<a href="//m.' + DOMAIN + "/Euro/photo_detail/id/" + v['id'] + '">' +
                                    '<figure><img src="' + v.img_array[1] + '" alt="' + v.title + '"></figure>' +
                                    '<figcaption class="q-two">' + v.title + '</figcaption>' +
                                    '</a></li>';
                            $("#posts li:last").after(html);
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
        location.href = "?type=" + $('#type').val() + '&event=' + $(this).data('event');
    });

});