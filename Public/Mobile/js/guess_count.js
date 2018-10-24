$(function () {

    //滚动加载滚动---亚盘、竞彩
    $(window).scroll(function () {
        //$(window).scrollTop()这个方法是当前滚动条滚动的距离
        //$(window).height()获取当前窗体的高度
        //$(document).height()获取当前文档的高度
        // var bot = 50; //bot是底部距离的高度
        //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
        if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
        {
            gambleListMore();
        }
    });

    //头像懒加载
    function lazyload(){
        $("img.lazy").lazyload({
            effect: "fadeIn",
            threshold : 10,
            failurelimit:10
        });
    }

    //头像懒加载
    lazyload();


    //推荐列表的加载
    function gambleListMore() {

        var page = $('#page').val();
        var params = {
            page: page,
            scheid: $('#game_id').val(),
            type: $('#type').val(),
        }
        $('#showLess').css({'display': 'none'});
        $('#moreLoad').css({'display': 'block'});
        $.ajax({
            type: 'post',
            url: "",
            data: params,
            dataType: 'json',
            success: function (data) {
                if (data.status == 1)
                {
                    var list = data.list;

                    if (list != null)
                    {
                        $.each(list, function (k, v) {

                            var res = '';

                            if (v['tenGamble'] != null && v['tenGamble'] != '' && v['tenGamble'] != undefined)
                            {
                                $.each(v['tenGamble'], function (j, val) {
                                    switch (val) {
                                        case '0.5':
                                        case '1':
                                            res += '<em class="c_win">红</em>';
                                            break;
                                        case '-0.5':
                                        case '-1':
                                            res += '<em class="c_lose">黑</em>';
                                            break;
                                        case '2':
                                            res += '<em class="c_ping">走</em>';
                                            break;
                                    }
                                });
                            }

                            var html = '<li>';
                            switch (v['result']) {
                                case '0.5':
                                    html += '<div class="win_half"></div>';
                                    break;
                                case '1':
                                    html += '<div class="win"></div>';
                                    break;
                                case '-0.5':
                                    html += '<div class="lose_half"></div>';
                                    break;
                                case '-1':
                                    html += '<div class="lose"></div>';
                                    break;
                                case '2':
                                    html += '<div class="ping"></div>';
                                    break;
                                case '-10':
                                    html += '<div class="cancel"></div>';
                                    break;
                                case '-11':
                                    html += '<div class="pending"></div>';
                                    break;
                                case '-12':
                                    html += '<div class="cut"></div>';
                                    break;
                                case '-13':
                                    html += '<div class="interrupt"></div>';
                                    break;
                                case '-14':
                                    html += '<div class="putoff"></div>';
                                    break;

                            }

                            html += '<div class="gc_top clearfix">' +
                                '<a href="//' + DOMAIN + '/expUser/'+v['user_id']+'/'+v['play_type']+'.html">' +
                                '<img class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"  alt="' + v['nick_name'] + '">' +
                                '<div class="gct_right fl">' +
                                '<p>' + v['nick_name'] + '</p>' +
                                '<p>周胜：<span>' + v['weekPercnet'] + '% </span>  月胜：<span>' + v['monthPercnet'] + '%</span>   季胜：<span>' + v['seasonPercnet'] + '%</span></p>' +
                                '</div></a></div><div class="gc_bottom clearfix">' +
                                '<span>近十场</span>' + res + '</div>' +
                                '<div class="gc_des clearfix">';

                            if (v['is_trade'] == 1 || v['result'] != 0)
                            {
                                var home_name = $('#home_name').html();
                                var away_name = $('#away_name').html();

                                html += '<p class="p_4">推荐：';

                                if (v['play_type'] == 1)
                                {
                                    if (v.chose_side == 1)
                                    {
                                        html += home_name;
                                    }
                                    else
                                    {
                                        html += away_name;
                                    }

                                    html += ' ' + v.handcp + ' <span>(' + v.odds + ')</span></p>';
                                }
                                else if (v.play_type == -1)
                                {
                                    if (v.chose_side == 1)
                                    {
                                        html += '大';
                                    }
                                    else
                                    {
                                        html += '小';
                                    }
                                    html += ' ' + v['handcp'] + ' <span>(' + v.odds + ')</span></p>';
                                }
                                else
                                {
                                    var answer = '';
                                    switch (v.chose_side)
                                    {
                                        case '1': answer = '胜' ;break;
                                        case '0': answer = '平' ; break;
                                        case '-1':  answer = '负'; break;
                                    }

                                    html += home_name + ' ' + '('+v.handcp+') ' +answer+ ' <span>(' + v.odds + ')</span></p>';
                                }

                                html += '</p>'

                                if (v.desc == '' || v.desc == null)
                                {
                                    var desc = '暂无分析';
                                }
                                else
                                {
                                    var desc = v.desc;
                                }
                                html += '<p class="p_5 q-two">分析：<span>' + desc + '</span></p>';
                            }
                            else
                            {
                                html += '<a href="javascript:;" class="gold" data-coin="'+v['tradeCoin']+'" id="to-view" data-gambleid="' + v.gamble_id + '"';
                                if (v.tradeCoin == 0)
                                {
                                    html += 'style="background: green;">免费</a></div></li>';
                                } else
                                {
                                    html += '>' + v.tradeCoin + '金币</a></div></li>';
                                }
                            }

                            $("#js-list li:last").after(html);
                        });
                        page++;
                        $('#page').val(page);
                    }
                    else
                    {
                        $('#moreLoad').css({'display': 'none'});
                        $('#showLess').css({'display': 'block'});
                    }

                }
                else
                {

                }
            },
            complete: function () {
                $('#moreLoad').css({'display': 'none'});
                //头像懒加载
                lazyload();
            }
        });
    }
    //查看竞猜
    $(document).on('click', '#to-view', function () {

        var $this = $(this);
        var coin = $this.html();
        var gambleid = $this.data('gambleid');
        var coinNum = $this.data('coin');
        payment($this,gambleid,coinNum,3333)
    });
});