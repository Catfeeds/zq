/**
 * Created by Liangzk on 2017/5/9.
 * M站查看推荐公共js
 */
function payment(obj, id, coin,sign) {

    /**
    $('body').append('<div id="dailogCom" class="fixBox">' +
        ' <div class="fixBoxIn clearfix">' +
        ' <div class="tip" style="line-height: 0.8rem; "><span id="dailogCom" ></span></div>' +
        ' <a id="yesDailogCom" href="javascript:;" class="tip_btn fs30" style="width: 100%;">确定</a>' +
        ' </div>' +
        ' </div>');
    $('#yesDailogCom').on('click',function () {
        $('#dailogCom').remove();
    });
     **/
    //判断是否在登录的状态--同步请求
    var isLogin = false;
    $.ajax({
        type: 'post',
        async: false,
        url: "/Guess/show_guess.html",
        dataType: 'json',
        success: function (data) {
            if (data.status == 1) {
                isLogin = true;
            }
            else {
                isLogin = false;
                window.location.href = '/User/login.html';
            }
        }
    });
    if (!isLogin)
        return false;

    if (coin > 0)
    {
        $.ajax({
            type: 'post',
            async: false,
            url: "/Ticket/ticketType.html",
            data: {'type':1,'coin':coin},
            dataType: 'json',
            success: function (data) {
                if (data.status == 1)
                {
                    var list = data.info.result;
                    if (list != null) {

                        var tip_sele_html = Array.prototype.isPrototypeOf(list) && list.length === 0 ? '' : ' <div class="tip_sele"><input id="comCheck" type="checkbox" checked="checked" /> 使用'+list[0]['price']+'金币推荐体验券(剩余'+list[0]['num']+'张)</div>';
                        $('body').append('<div id="maskLayer" style="display: block;"></div>' +
                            ' <div id="com_tip" class="com_tip">' +
                            ' <div class="tip_title">温馨提示</div>' +
                            ' <div class="tip_con">查看该场次推荐需要'+coin+'金币</div>' +
                            tip_sele_html+
                            ' <div class="tip_btn">' +
                            ' <div id="tip_btn_clear" class="tip_btn_com">取消</div>' +
                            ' <div id="tip_btn_on" class="tip_btn_com">确定</div>' +
                            ' </div>' +
                            ' </div>')

                    }
                }

            }

        });

        //取消
        $('#tip_btn_clear').on('click',function () {
            $('#com_tip').remove();
            $('#maskLayer').remove();
        });

        var comCheck = document.getElementById('comCheck');

        //去掉
        $('#tip_btn_on').on('click',function () {
            $('#com_tip').remove();
            $('#maskLayer').remove();
            //判断是否使用体验卷
            var isTicket = 0;
            if (comCheck != null && comCheck != undefined)
            {
                isTicket = comCheck.checked ? 1 : 0;
            }

            ajaxTrade(obj, id, coin,isTicket,sign)
        });

    }
    else
    {
        ajaxTrade(obj, id, coin,0,sign);
    }

   

}

function ajaxTrade(obj, id, coin,isTicket,sign) {
    $.ajax({
        type: "POST",
        url: "/Guess/trade.html",
        data: {'gamble_id': id,'isTicket':isTicket},
        dataType: "json",
        success: function (data) {
            if (data.status == 1)
            {
                switch (sign)
                {
                    //首页
                    case 1111:
                        var game = data.info;
                        var descHtml = game['desc'] != null && game['desc'] != '' ? game['desc'] : '暂无分析';
                        if (game['play_type'] == 1 || game['play_type'] == -1)
                        {
                            $(obj).parent().append('<p class="p_4">推荐：<span>'+game['Answer']+' '+game['handcp']+ '</span><em>（'+game['odds']+'）</em></p>' +
                                '<p class="p_5 q-two">分析：<span>'+descHtml+'</span></p>');
                        }
                        else
                        {
                            $(obj).parent().append('<p class="p_4">推荐：<span>'+game['home_team_name']+' ('+game['handcp']+') '+game['Answer']+' </span><em>（'+game['odds']+'）</em></p>' +
                                '<p class="p_5 q-two">分析：<span>'+descHtml+'</span></p>');

                        }
                        $(obj).parent().find('.p_1 .etip').html('');
                        var etipHtml = '';
                        if (game['desc'] != '' && game['desc'] != null)
                        {
                            etipHtml += '<span><img src="/Public/Mobile/images/guess/fenxi.png" alt="分析"></span>';
                        }

                        if (game['tradeCoin'] > 0)
                        {
                            etipHtml += '<span class="coins">'+game['tradeCoin']+'</span>';
                        }
                        else
                        {
                            etipHtml += '<span><img src="/Public/Mobile/images/guess/free.png" alt="免费"></span>';
                        }

                        $(obj).parent().find('.p_1 .etip').append(etipHtml);
                        $(obj).remove();
                        break;
                    //大咖广场
                    case 2222 :
                        var game = data.info;
                        var descHtml = game['desc'] != null && game['desc'] != '' ? game['desc'] : '暂无分析';
                        if (game['play_type'] == 1 || game['play_type'] == -1) {
                            $(obj).parent().append('<p class="p_4">推荐：<span>' + game['Answer'] + ' ' + game['handcp'] + '</span><em>（' + game['odds'] + '）</em></p>' +
                                '<p class="p_5 q-two">分析：<span>' + descHtml + '</span></p>');
                        }
                        else {
                            $(obj).parent().append('<p class="p_4">推荐：<span>' + game['home_team_name'] + ' (' + game['handcp'] + ') ' + game['Answer'] + ' </span><em>（' + game['odds'] + '）</em></p>' +
                                '<p class="p_5 q-two">分析：<span>' + descHtml + '</span></p>');

                        }
                        $(obj).parent().find('.p_1 .etip').html('');
                        var etipHtml = '';
                        if (game['desc'] != '' && game['desc'] != null) {
                            etipHtml += '<span><img src="/Public/Mobile/images/guess/fenxi.png" alt="分析"></span>';
                        }

                        if (game['tradeCoin'] > 0) {
                            etipHtml += '<span class="coins">' + game['tradeCoin'] + '</span>';
                        }
                        else {
                            etipHtml += '<span><img src="/Public/Mobile/images/guess/free.png" alt="免费"></span>';
                        }

                        $(obj).parent().find('.p_1 .etip').append(etipHtml);
                        $(obj).remove();
                        break;
                    //赛事推荐统计
                    case 3333:
                        var home_name = $('#home_name').html();
                        var away_name = $('#away_name').html();
                        var html = '<p class="p_4">推荐：';
                        if (data.info.play_type == 1) {
                            if (data.info.chose_side == 1) {
                                html += home_name;
                            } else {
                                html += away_name;
                            }
                            html += ' ' + data.info.handcp + ' <span>(' + data.info.odds + ')</span></p>';
                        }
                        else if (data.info.play_type == -1)
                        {
                            if (data.info.chose_side == 1) {
                                html += '大';
                            } else {
                                html += '小';
                            }
                            html += ' ' + data.info.handcp + ' <span>(' + data.info.odds + ')</span></p>';
                        }
                        else
                        {
                            var answer = '';
                            switch (data.info.chose_side)
                            {
                                case '1': answer = '胜' ;break;
                                case '0': answer = '平' ; break;
                                case '-1':  answer = '负'; break;
                            }

                            html += home_name + ' ' + '('+data.info.handcp+') ' +answer+ ' <span>(' + data.info.odds + ')</span></p>';
                        }
                        if (data.info.desc == '' || data.info.desc == null) {
                            var desc = '暂无分析';
                        } else {
                            var desc = data.info.desc;
                        }
                        html += '<p class="p_5 q-two">分析：<span>' + desc + '</span></p>';
                        if (data.info.tradeCoin == '0') {
                            obj.parent().prev().prev().prepend('<div class="free"></div>');
                        }
                        obj.parent().html(html);
                        break;
                    //ta的主页
                    case 4444:
                        var game = data.info;
                        var desc = game['desc'] != '' ? game['desc'] : '暂无分析';
                        if (game['play_type'] == 1 || game['play_type'] == -1)
                        {
                            var html = "<p class=\"p_4\">推荐：<span>"+game['Answer']+" "+game['handcp']+"</span><em>（"+game['odds']+"）</em></p>"+
                                "<p class=\"p_5 q-two\">分析：<span>"+desc+"</span></p>";
                        }
                        else
                        {
                            var html = '<p class="p_4">推荐：<span>'+game['home_team_name']+' ('+game['handcp']+') '+game['Answer']+' </span><em>（'+game['odds']+'）</em></p>'+
                                "<p class=\"p_5 q-two\">分析：<span>"+desc+"</span></p>";
                        }

                        $(obj).before(html);
                        if(game['tradeCoin'] > 0){
                            $(obj).parents('li').find('.etip').append("<span class=\"coins\">"+game['tradeCoin']+"</span>");
                        }else{
                            $(obj).parents('li').find('.etip').append("<span><img src=\"/Public/Mobile/images/guess/free.png\" alt=\"免费\"></span>");
                        }
                        $(obj).remove();
                        break;
                    //我的关注
                    case 5555:

                        var game = data.info;
                        var descHtml = game['desc'] != null && game['desc'] != '' ? game['desc'] : '暂无分析';
                        if (game['play_type'] == 1 || game['play_type'] == -1)
                        {
                            $(obj).parent().append('<p class="p_4">推荐：<span>'+game['Answer']+' '+game['handcp']+ '</span><em>（'+game['odds']+'）</em></p>' +
                                '<p class="p_5 q-two">分析：<span>'+descHtml+'</span></p>');
                        }
                        else
                        {
                            $(obj).parent().append('<p class="p_4">推荐：<span>'+game['home_team_name']+' ('+game['handcp']+') '+game['Answer']+' </span><em>（'+game['odds']+'）</em></p>' +
                                '<p class="p_5 q-two">分析：<span>'+descHtml+'</span></p>');

                        }
                        $(obj).parent().find('.p_1 .etip').html('');
                        var etipHtml = '';
                        if (game['desc'] != '' && game['desc'] != null)
                        {
                            etipHtml += '<span><img src="/Public/Mobile/images/guess/fenxi.png" alt="分析"></span>';
                        }

                        if (game['tradeCoin'] > 0)
                        {
                            etipHtml += '<span class="coins">'+game['tradeCoin']+'</span>';
                        }
                        else
                        {
                            etipHtml += '<span><img src="/Public/Mobile/images/guess/free.png" alt="免费"></span>';
                        }

                        $(obj).parent().find('.p_1 .etip').append(etipHtml);
                        $(obj).remove();

                        break;

                }

            }
            else
            {
                var msg = data.info;
                if (msg == 2008) {
                    //充值提示
                    $('body').append('<div id="show" style="display: block;" class="modal">' +
                        ' <div class="modal-content">' +
                        ' <div class="modal-header">' +
                        ' <h4 class="modal-title">温馨提示</h4>' +
                        ' <p>查看该场推荐需要<span style="color:red" id="js-modal-coin">'+coin+'</span>金币，您的金币不足，请充值或者用积分兑换</p>' +
                        ' </div>' +
                        ' <div class="modal-body">' +
                        ' <ul class="modal_ul">' +
                        ' <li><a href="//'+DOMAIN+'/Guess/exchange.html">兑换</a></li>' +
                        ' <li><a href="//'+DOMAIN+'/Pay/index.html">充值</a></li>' +
                        ' <li id="js-modal-close"><a href="javascript:;">取消</a></li>' +
                        ' </ul>' +
                        ' </div>' +
                        ' </div>' +
                        ' </div>' +
                        ' <div id="bg" class="modal-backdrop" style="z-index: 100000; display: block;"></div>')
                }
                else {

                    //信息提示
                    $('body').append('<div id="dailogCom" class="fixBox">' +
                        ' <div class="fixBoxIn clearfix">' +
                        ' <div class="tip" style="line-height: 0.8rem; "><span id="dailogCom" >'+data.info+'</span></div>' +
                        ' <a id="yesDailogCom" href="javascript:;" class="tip_btn fs30" style="width: 100%;">确定</a>' +
                        ' </div>' +
                        ' </div>');

                }


            }
        },
        complete:function () {

            //提示
            $('#yesDailogCom').on('click',function () {
                $('#dailogCom').remove();
            });

            //充值提示
            $('#js-modal-close').on('click',function () {
                $('#show').remove();
                $('#bg').remove();

            });
        }
    });
}