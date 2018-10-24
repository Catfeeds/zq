
$(function () {
    $('#js-modal-close').on('click',function () {
        $('#show').hide();
        $('#bg').hide();
    });
    $('#err_js-modal-close').on('click',function () {
        $('#err_info').hide();
        $('#bg').hide();
    });
    //滚动加载滚动
    $(window).scroll(function () {
        //$(window).scrollTop()这个方法是当前滚动条滚动的距离
        //$(window).height()获取当前窗体的高度
        //$(document).height()获取当前文档的高度
        // var bot = 50; //bot是底部距离的高度
        //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
        if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
        {
            if ($('#emptyList').attr("style") != 'display:block;')
            {
                var index_type = $('#type').val();
                if(index_type == 'index')
                {
                    index_list("/Intro/index");
                }else if(index_type == 'class_index'){
                    var class_id = $("#class_id").val();
                    index_list("/Intro/intro_class",class_id);
                }
                else{
                    history();
                }
            }
        }
    });
});

//ajax加载主页数据
function index_list(url,id)
{
    $('#gambleListMore').css('display','block');//显示加载更多提示
    var page = $('#page').val();
    var num = ++page;
    var data = {
        page       : num,
        infotype   : 1,
        class_id   : id,

    };
    $.ajax({
        type: 'post',
        async : false,
        url: url,
        data: data,
        dataType: 'json',
        success: function (data) {
            var arr = data.products;
            if(arr != '')
            {
                $.each(arr, function (k, v) {
                    var _desc = cutstr(v.desc,28)
                    var html = '<a href="/Intro/intro_info/id/' + v.id + '.html">' +
                        '<div class="im_list"><div class="iml_title" style="background:url(' + v.background + '"> <div class="imlt_l fl">' + v.name +
                        '</div><div class="imlt_r fr"><span><i>' + v.sale + '</i>金币</span>/' + v.game_num +
                        '场</div></div><div class="iml_con"><div class="imlc_main clearfix"><div class="imlc_com imlc_l"><img src="' + v.logo +
                        '" alt=""></div><div class="imlc_com imlc_c"><div class="ic_jianj">简介：' + _desc +
                        '...</div><div class="near_state"><em>近10中' + v.ten_num + '</em>';
                    var pub = '';
                    if(v.published == 0)
                    {
                        pub = '<em>未发布</em>';
                    }else{
                        pub = '<em style="background:#ff3e63;color:#FFF;">已发布</em>';
                    }
                    html = html + pub +
                           '</div></div><div class="imlc_com imlc_r"><p>' + v.total_rate +
                            ' <span>%</span></p><p>累计回报率</p></div></div><div class="iml_shuom q-one"><img src="' + IMAGES +
                            '/qw/cup.png" alt="">' + v.remark + '</div></div></div></a>';
                    $('.index_main').append(html);
                });

                $('#page').val(num);
            }else{
                $('#gambleListMore').css('display','none');//显示加载更多提示
                $('#showLess').css('display','block');//提示数据已经加载完
            }
        }
    });
}

//ajax获取历史推荐
function history()
{
    $('#gambleListMore').css('display','block');//显示加载更多提示
    var page = $('#page').val();
    var num = ++page;
    var product_id = $('#product_id').val();
    var data = {
        product_id  : product_id,
        page       : num,

    };
    $.ajax({
        type: 'post',
        async : false,
        url: "/Intro/ajax_info.html",
        data: data,
        dataType: 'json',
        success: function (data) {
            if(data != '')
            {
                $.each(data, function (key, val) {
                    var html = '<div class="history_list"><div class="his_data">' +
                        val.pub_time_format +
                        '</div><div class="game_list"><table class="table tb_glist" cellspacing="0" ><tbody>';
                    var tr_html = '';
                    $.each(val.gamble, function (k, v) {
                        var res_img = '';
                        if(v.photo)
                        {
                            res_img = '<img src="' + IMAGES + '/qw/' + v.photo + '.png" alt="">';
                        }
                        var score = '';
                        if(v.score)
                        {
                            score = v.score;
                        }else{
                            score = 'VS';
                        }
                        tr_html = tr_html +
                            '<tr><td class="td_gtype"><p style="color: ' + v.union_color + '">' +
                            v.union_name[0] +
                            '</p><p>' + v.gtime_day + '</p><p>' + v.gtime_hour + '</p>' +
                            '</td><td class="td_gvs"><p>' +
                            v.home_team_name[0] + '<span>' + score + '</span>' + v.away_team_name[0] +
                            '</p><p><span>推介：'+ v.chose +'</span>' +
                            v.handcp + '<em>(' + v.odds + ')</em></p></td><td class="td_gres">' + res_img +
                            '</td></tr>';
                    });
                    html = html + tr_html + '</tbody></table></div></div>';
                    $('#gambleList').append(html);
                });
                $('#page').val(num);
            }else{
                $('#gambleListMore').css('display','none');//显示加载更多提示
                $('#showLess').css('display','block');//提示数据已经加载完
            }
        }
    });
}

//查看竞猜
function payment(obj,coin,productId){

    //判断是否在登录的状态--同步请求
    var isLogin = false;
    $.ajax({
        type: 'post',
        async : false,
        url: "/Guess/show_guess.html",
        dataType: 'json',
        success: function (data) {
            if(data.status==1)
            {
                isLogin = true;
            }
            else
            {
                isLogin = false;
//                        $('#dailogContent').html(data.info);
//                        $('#dailogFixBox').css({'display':'block'});
                window.location.href = '/User/login.html';
            }
        }
    });
    if (! isLogin)
        return false;
    if (coin > 0)
    {
        $('#bg').show();
        $("#com_tip").show();
        return;
    }
}


$('#tip_btn_clear').on('click',function () {
    $('#com_tip').hide();
    $('#bg').hide();
});

$('#tip_btn_on').on('click',function () {
    $('#com_tip').hide();
    var product_id = $('#product_id').val();
    var type = 0;
    $.ajax({
        type: "POST",
        url: "/Common/qwBuyCheck.html",
        data: {'productId': product_id},
        dataType: "json",
        async: false,
        success: function (data) {
            if (data.status == 1)
            {
                type = 1;
            }else if(data.errorCode == 8009){
                $('#bg').show();
                $('#show').show();
            }
        }
    });
    if(type == 1)
    {
        $.ajax({
            type: "POST",
            url: "/Common/setQwBuy.html",
            data: {'productId': product_id},
            dataType: "json",
            async: false,
            success: function (data) {
                if (data.status == 1)
                {
                    var res = data.res;
                    if (res != null)
                    {
                        var buy_type = 0;
                        if(res.status) buy_type = 1;
                        percent(product_id,buy_type);
                        $('#err_msg').html(res.msg);
                        $('#bg').hide();
                    }else{
                        $('#err_msg').html('购买失败');
                        $('#bg').show();
                        $('#err_info').show();
                    }

                }
            }
        });
    }
});

function percent(productId,type)
{
    $.ajax({
        type: "POST",
        url: "/Intro/surplus_num.html",
        data: {'id': productId},
        dataType: "json",
        async: false,
        success: function (data) {
            if (data.status == 1)
            {
                if(data.game_code == 1)
                {
                    $("#buy_num").html(data.buy_num);
                    $("#total_num").html(data.total_num);
                    $("#percent").css('width',data.percent + "%");
                    if(type || data.percent == 100)
                    {
                        var msg = '已订购';
                        if(data.percent == 100) msg = "已抢光";
                        var html = "<a style='background:#999;font-size: .32rem;'>" + msg + "</a>";
                        $(".buyc_r").html(html);
                    }
                }else if(data.game_code == 10){
                    var html = '<div class="ptime_con clearfix"><div class="ptime_com fl">发布于：' + data.info.pub_time +
                        '</div><div class="ptime_com fr">' + data.info.sale +
                        '</div></div><div class="game_list"><table class="table tb_glist" cellspacing="0" ><tbody>';
                    var tab_html = '';
                    $.each(data.info.new, function (k, v) {
                        var res_img = '';
                        if(v.photo)
                        {
                            res_img = '<img src="' + IMAGES + '/qw/' + v.photo + '.png" alt="">';
                        }
                        var score = '';
                        if(v.score)
                        {
                            score = v.score;
                        }else{
                            score = 'VS';
                        }
                        tab_html = tab_html +
                            '<tr><td class="td_gtype"><p style="color: ' + v.union_color + '">' +
                            v.union_name[0] +
                            '</p><p>' + v.time_day + '</p><p>' + v.time_hour + '</p>' +
                            '</td><td class="td_gvs"><p>' +
                            v.home_team_name[0] + '<span>' + score + '</span>' + v.away_team_name[0] +
                            '</p><p><span>推介：' + v.chose + '</span> ' +
                            v.handcp + ' <em>(' + v.odds + ')</em></p></td><td class="td_gres">' + res_img +
                            '</td></tr>';
                    });
                    html = html + tab_html + '</tbody></table></div>';
                    $("#new_intro").html(html);
                }
            }else{
                window.location.reload();
            }
        }
    });
}
/**
 * js截取字符串，中英文都能用
 * @param str：需要截取的字符串
 * @param len: 需要截取的长度
 */
function cutstr(str, len) {
    var str_length = 0;
    var str_len = 0;
    str_cut = new String();
    str_len = str.length;
    for (var i = 0; i < str_len; i++) {
        a = str.charAt(i);
        str_length++;
        if (escape(a).length > 4) {
            //中文字符的长度经编码之后大于4
            str_length++;
        }
        str_cut = str_cut.concat(a);
        if (str_length >= len) {
            str_cut = str_cut.concat("...");
            return str_cut;
        }
    }
    //如果给定字符串小于指定长度，则返回源字符串；
    if (str_length < len) {
        return str;
    }
}
$(".user_save").on('click',function () {
    //判断是否在登录的状态--同步请求
    var isLogin = false;
    $.ajax({
        type: 'post',
        async : false,
        url: "/Guess/show_guess.html",
        dataType: 'json',
        success: function (data) {
            if(data.status==1)
            {
                isLogin = true;
            }
            else
            {
                isLogin = false;
                $('#bg').show();
                $('#err_msg').html('请登入后关注');
                $('#err_info').show();
            }
        }
    });
    if(!isLogin) return;
    var product_id = $('#product_id').val();
    var _type = $(".user_save").hasClass('on');
    var actionType = '';
    if(_type)
    {
        actionType = 2;
    }else{
        actionType = 1;
    }
    var data = {
        productId  : product_id,
        actionType       : actionType,

    };
    $.ajax({
        type: 'post',
        async : false,
        url: "/Intro/subscribe.html",
        data: data,
        dataType: 'json',
        success: function (data) {
            if(data.result == 1)
            {
                if(_type)
                {
                    $(".user_save").removeClass('on');
                    $('#err_msg').html('取消关注成功');
                }else{
                    $(".user_save").addClass('on');
                    $('#err_msg').html('关注成功');
                }
            }else{
                $('#err_msg').html('操作失败');
            }
            $('#bg').show();
            $('#err_info').show();
        }
    });

});