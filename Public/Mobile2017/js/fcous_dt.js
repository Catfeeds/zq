
$(function () {

    if($('.js-list').size() < 1)
    {
        $('#loadMore').css('display','none');
        $('#showLess').css('display','none');
        $('#emptyData').css('display','block');
    }

    //信息提示弹框关闭
    $('#yesDailog').on('click',function () {
        $('#dailogFixBox').css({'display':'none'});
    });

    if ($(".list").size() < 10 && $(".list").size() > 0)
    {
        $('#emptyData').css('display','none');
        $('#showLess').css('display','block');
    }

    //滚动加载滚动---亚盘、竞彩
    $(window).scroll(function () {
        //$(window).scrollTop()这个方法是当前滚动条滚动的距离
        //$(window).height()获取当前窗体的高度
        //$(document).height()获取当前文档的高度
        var bot = 100; //bot是底部距离的高度
        //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
        if (($(window).scrollTop()) >= ($(document).height() - $(window).height() - bot))
        {
            if ($('#showLess').attr("style") != 'display:block;')
            {
                    gambleListMore();
            }
        }
    });


    function gambleListMore()
    {
        $('#loadMore').css('display','block');
        // 初始化页面，点击事件从第二页开始
        var page = $('#page').val();
        page++;
        $('#loadMore').css('display','block');
        $('#emptyData').css('display','none');
        $.ajax({
            type: 'post',
            url: "",
            async : false,
            data: {page: page},
            dataType: 'json',
            success: function (data) {
                if (data.status == 1)
                {
                    var list = data.list;

                    if (list != null)
                    {
                        $.each(list,function (k,v) {

                            //等级
                            var lvHtml = v['play_type'] == '1' || v['play_type'] == '-1' ? '<em class="ya_text">亚:<i>LV'+v['lv']+'</i></em>' : '<em class="ya_text">竞:<i>LV'+v['lv_bet']+'</i></em>';
                            //连胜
                            var winHtml = v['curr_victs'] > 1 ? '<em>'+v['curr_victs']+'连胜</em>' : '';
                            //十中几
                            var tenHtml = v['tenGambleRate'] > 5 ? '<img src="'+IMAGES+'/index/ic_'+v['tenGambleRate']+'.png" alt="">' : '';
                            //竞彩标识
                            var betCodeHtml = v['play_type'] == '2' || v['play_type'] == '-2' ? '<em>'+v['bet_code']+' </em>' : '';
                            //比分
                            var scoreHtml = v['result'] == 0 ? 'VS' : '<strong style="color: red">'+v['score'].replace('-','：')+'</strong>';

                            //玩法
                            var playHtml = '';
                            switch (v['play_type'])
                            {
                                case '1': playHtml = '让球'; break;
                                case '-1': playHtml = '大小球'; break;
                                case '2': playHtml = '竞彩'; break;
                                case '-2': playHtml = '竞彩'; break;
                            }
                            var resultHtml = '';
                            var fenxiHtml = '';
                            var coinsHtml = '';

                            if (v['is_trade'] == 1)
                            {
                                //金币、免费、分析图标
                                fenxiHtml = v['desc'] != '' && v['desc'] != null && v['desc'] != undefined ? '<span><img src="'+IMAGES+'/guess/fenxi.png" alt="分析"></span>' : '';
                                coinsHtml = v['tradeCoin'] > 0 ? '<span class="coins">'+v['tradeCoin']+'</span>' : '<span><img src="'+IMAGES+'/guess/free.png" alt="免费"></span>';

                                //推荐结果
                                var answerHtml = ''
                                if (v['play_type'] == '1' || v['play_type'] == '-1')
                                {
                                    answerHtml = '<p class="p_4">推荐：<span>'+v['Answer']+' '+v['handcp']+' </span><em>（'+v['odds']+'）</em></p>';
                                }

                                if (v['play_type'] == '2' || v['play_type'] == '-2')
                                {
                                    answerHtml = '<p class="p_4">推荐：<span>'+v['home_team_name']+' ('+v['handcp']+') '+v['Answer']+' </span><em>（'+v['odds']+'）</em>';
                                }

                                resultHtml += answerHtml;
                                //分析
                                var descHtml = v['desc'] != null && v['desc'] != '' ? '<p class="p_5 q-two">分析：<span>'+v['desc']+'</span></p>' : '<p class="p_5 q-two">分析：<span>暂无分析</span></p>';
                                resultHtml += descHtml;

                                switch (v['result'])
                                {
                                    case '-1': resultHtml += '<div class="lose"></div>'; break;
                                    case '-0.5': resultHtml += '<div class="lose"></div>'; break;
                                    case '1': resultHtml += '<div class="win"></div>'; break;
                                    case '0.5': resultHtml += '<div class="win"> </div>'; break;
                                    case '2': resultHtml += '<div class="ping"> </div>'; break;
                                    case '-10': resultHtml += '<div class="cancel"></div>'; break;
                                    case '-11': resultHtml += '<div class="pending"></div>'; break;
                                    case '-12': resultHtml += '<div class="cut"></div>'; break;
                                    case '-13': resultHtml += '<div class="interrupt"></div>'; break;
                                    case '-14': resultHtml += '<div class="putoff"></div>'; break;

                                }

                            }
                            else
                            {
                                var tradeCoinHtml = v['tradeCoin'] > 0 ? '<div class="gold">'+v['tradeCoin']+'金币</div>' : '<div class="gold bg_green">免费</div>';

                                resultHtml = '<a href="javascript:;" data-play="'+v['play_type']+'" onclick="payment(this,'+v['gamble_id']+','+v['tradeCoin']+',5555)">'+tradeCoinHtml +'</a> ';
                            }

                            var html = '<li class="list">' +
                                ' <a href="//'+DOMAIN+'/Guess/other_page/user_id/'+v['user_id']+'/type/'+v['play_type']+'">' +
                                ' <div class="n_top clearfix">' +
                                ' <div class="n_top_left">' +
                                ' <img class="lazy" data-original="'+v['face']+'" src="'+IMAGES+'/index/headImg.png" >' +
                                ' <div class="ntl_main fl">' +
                                ' <div class="ntl_name">'+v['nick_name']+lvHtml+' </div>' +
                                ' <div class="ntl_per"><em>周胜: '+v['weekPercnet']+'%</em>'+winHtml+'</div>' +
                                ' </div>' +
                                ' </div>' +
                                ' <div class="n_top_right"> '+tenHtml+'</div>' +
                                ' </div>' +
                                ' <div class="p_1">' +
                                ' <div class="t_vs">'+betCodeHtml+'<em style="color: '+v['union_color']+'">'+v['union_name']+'</em> <em>'+v['gDate']+'</em> </div>' +
                                ' <div class="etip">'+fenxiHtml+coinsHtml+'</div>' +
                                ' </div>' +
                                ' <p class="p_2">'+v['home_team_name']+scoreHtml+v['away_team_name']+' </p>' +
                                ' <p class="p_3">玩法：<span>'+playHtml+'</span> </p>' +
                                ' </a>'+resultHtml+' </li> ';

                            $('#js-list').append(html);
                        });

                    }
                    else
                    {
                        $('#loadMore').css('display','none');
                        $('#showLess').css({'display':'block'});
                    }
                }
                else
                {
                    $('#dailogContent').html('连接失败');
                    $('#dailogFixBox').css({'display':'block'});
                }

            },
            complete:function () {
                $('#page').val(page);

                //头像懒加载
                lazyload();
            }
        });
    }
});
