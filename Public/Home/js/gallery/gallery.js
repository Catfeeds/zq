/**
 * Created by Administrator on 2018/1/23.
 */
//滚动条控制
(function ($) {
    $(window).load(function () {

    });
})(jQuery);

$(function () {
    $('.indentBtn').click(function (e) {
        if ($(this).hasClass('on')) {
            $(this).removeClass('on');
            $('.hotLiveBox').stop().animate({'left': '-214px', 'z-indent': '1'}, 500)
        } else {
            $(this).addClass('on');
            $('.hotLiveBox').stop().animate({'left': '0', 'z-indent': '-1'}, 500)
        }
    });

    if (class_id) {
        $("#c-" + class_id + ' a').addClass('on').parents('li').siblings().children('a').removeClass('on');
    }

    //翻页
    $(document).on('click', '.page-con ul li', function () {
        var page = $(this).attr('page');

        var text = $(this).text();
        if (text.indexOf('跳到') > -1 || text.indexOf('页') > -1 || $(this).find('input').hasClass("isTxtBig"))
            return;

        var _goPage = $('.isTxtBig').val();
        if ($(this).attr("id") == 'GO') {
            page = _goPage;
            if (!_goPage > 0) {
                _alert('提示', '请输入正确的页码');
                return;
            }
        }
        var html = '';
        $.ajax({
            type: 'post',
            url: "/",
            dataType: 'json',
            data: {class_id: class_id, p: page},
            success: function (e) {
                if(e.list !== undefined &&  e.list.length != 0){
                    $.each(e.list, function (k, data) {
                        html += '<li class=" pull-left"> ' +
                            '<a class="numb-tab-d" target="_blank" href="' + data.info_url + '"> ' +
                            '<div class="tab-img"><img class="lazy" data-original="' + data.cover_img + '" alt="'+data.title+'"/></div> ' +
                            '<div class="tab-work"> ' +
                            '<p>' + data.title + '</p> ' +
                            '<span class="time-tab">' + data.date_format + '</span>' +
                            // '<span class="glyphicon glyphicon-heart"></span>' +
                            // '<span>' + data.like_num + '</span> ' +
                            '</div> ' +
                            '</a> ' +
                            '</li>';
                    });
                    $('.shotBox ul').html(html);
                    $('body,html').animate({'scrollTop':'0'},500);
                    $("img.lazy").lazyload({
                        placeholder: staticDomain+"/Public/Images/loading.png",
                        effect: "fadeIn",
                        threshold: 150,
                        failurelimit: 50
                    });
                }
                //分页
                $('.page-con ul').html(e.page);
            },
        });
    });

    if (pid) {
        $('#top-' + pid).addClass('on');
        $('#top-0').removeClass('on');
    }

});