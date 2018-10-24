/**
 * Created by Liangzk on 2016/11/24.
 * 大咖广场
 */

$(function(){


    //获取点头像跳转前的滚动位置,并滚动到此
    var newPutScrollTop = Cookie.getCookie('newPutScrollTop');
    var newPutGambleType = Cookie.getCookie('newPutGambleType');
    var newPutVictoryType = Cookie.getCookie('newPutVictoryType');
    var newPutLvType = Cookie.getCookie('newPutLvType');
    var newPutPriceType = Cookie.getCookie('newPutPriceType');
    if (newPutGambleType != null || newPutVictoryType != null || newPutPriceType != null || newPutLvType != null)
    {
        $('#gambleType ul li').removeClass('on');
        $('#victoryType ul li').removeClass('on');
        $('#lvType ul li').removeClass('on');
        $('#priceType ul li').removeClass('on');
        if (newPutGambleType != null)
        {
            $('#gambleType ul li a').each(function () {
                if ($(this).data('gambletype') == newPutGambleType) {
                    $(this).parent().addClass('on');

                    switch (newPutGambleType)
                    {
                        case '1':$('#h_val1').html('亚盘<i></i>');break;
                        case '2':$('#h_val1').html('竞彩<i></i>');break;
                    }
                }
            });
        }
        if (newPutVictoryType != null)
        {
            $('#victoryType ul li a').each(function () {
                if ($(this).data('victorytype') == newPutVictoryType) {
                    $(this).parent().addClass('on');
                    switch (newPutVictoryType)
                    {
                        case '0':$('#h_val2').html('综合<i></i>');break;
                        case '1':$('#h_val2').html('周胜率<i></i>');break;
                        case '2':$('#h_val2').html('高命中<i></i>');break;
                        case '3':$('#h_val2').html('连胜多<i></i>');break;
                        case '4':$('#h_val2').html('人气旺<i></i>');break;
                        case '5':$('#h_val2').html('我的关注<i></i>');break;
                    }
                }
            });
        }

        if (newPutLvType != null)
        {
            $('#lvType ul li a').each(function () {
                if ($(this).data('lvtype') == newPutLvType) {
                    $(this).parent().addClass('on');
                    switch (newPutLvType)
                    {
                        case '0':$('#h_val5').html('等级<i></i>');break;
                        case '1':$('#h_val5').html('LV1<i></i>');break;
                        case '2':$('#h_val5').html('LV2<i></i>');break;
                        case '3':$('#h_val5').html('LV3<i></i>');break;
                        case '4':$('#h_val5').html('LV4<i></i>');break;
                        case '5':$('#h_val5').html('LV5<i></i>');break;
                        case '6':$('#h_val5').html('LV6<i></i>');break;
                        case '7':$('#h_val5').html('LV7<i></i>');break;
                        case '8':$('#h_val5').html('LV8<i></i>');break;
                        case '9':$('#h_val5').html('LV9<i></i>');break;
                    }
                }
            });
        }

        if (newPutPriceType != null)
        {
            $('#priceType ul li a').each(function () {
                if ($(this).data('pricetype') == newPutPriceType) {
                    $(this).parent().addClass('on');
                    switch (newPutPriceType)
                    {
                        case '0':$('#h_val3').html('价格<i></i>');break;
                        case '1':$('#h_val3').html('价格高<i></i>');break;
                        case '2':$('#h_val3').html('价格低<i></i>');break;
                    }
                }
            });
        }
        Cookie.delCookie('newPutGambleType');
        Cookie.delCookie('newPutVictoryType');
        Cookie.delCookie('newPutLvType');
        Cookie.delCookie('newPutPriceType');

        //获取上次打开的请求数据
        getGambleList({
            gambleType : newPutGambleType,
            victoryType: newPutVictoryType,
            lvType  : newPutLvType,
            priceType  : newPutPriceType,
        });

    }
    else
    {
        //默认情况选择不分玩法
        Cookie.setCookie('newPut_unionId', -1);
        getGambleList({gambleType  : 1,priceType:1});

    }
    if (newPutScrollTop) {
        $("html, body").animate({scrollTop: newPutScrollTop}, 1000);
        Cookie.delCookie('newPutScrollTop');

    }

    //记录点用户
    // 头像跳转前的滚动位置
    $(document).on('click','#gambleList ul li a',function () {
        var topHeight = $(document).scrollTop();
        Cookie.setCookie('newPutScrollTop', topHeight, 60000);//点击位置

        Cookie.setCookie('newPutGambleType', $('#gambleType ul .on a').data('gambletype'), 60000);//筛选的玩法
        Cookie.setCookie('newPutVictoryType', $('#victoryType ul .on a').data('victorytype'), 60000);//筛选的胜率
        Cookie.setCookie('newPutLvType', $('#lvType ul .on a').data('lvtype'), 60000);//筛选的价格
        Cookie.setCookie('newPutPriceType', $('#priceType ul .on a').data('pricetype'), 60000);//筛选的价格

    });


    //玩法筛选
    $('.daka_nav ul li').each(function(e){
        $(this).on('click',function(){
            //显示遮罩层
            // $('#maskLayer').show();
            // $('.daka_nav').css('border-bottom','0');
            // //内容切换
            // $('.nav_con').hide();
            // $('.nav_con').eq(e).show();
            // $(this).addClass('on').siblings('li').removeClass('on');
            var nav_dis = $('.nav_con').eq(e).css("display");
            if(nav_dis=="none"){
                //显示内容
                $('.nav_con').hide();
                $('.nav_con').eq(e).css("display","block");
                //点击li
                $('.daka_nav ul li').removeClass('on');
                $(this).addClass('on');
                $('.daka_nav').css('border-bottom','0');
                //显示遮罩层
                $('.maskLayer').hide();
                $('.maskLayer').eq(e).show();
            }else{
                //隐藏内容
                $('.nav_con').eq(e).css("display","none");
                $(this).removeClass('on');
                $('.daka_nav').css('border-bottom','1px solid #ddd');
                //隐藏遮罩层
                $('.maskLayer').eq(e).hide();
            }
        });
    });

    //内容筛选
    $('.nav_con ul li').on('click',function(){
        hideAll();
        $(this).addClass('on').siblings('li').removeClass('on');

    });
    //遮罩层点击
    $('.maskLayer').on('click',function(){
        //筛选赛事时，如果用户点击遮罩层就从新改变筛选赛事样式
        if ($('#unionNav').hasClass('on'))
        {
            $("#unionNameList a").removeClass('on');
            var unionIdArr = Cookie.getCookie('newPut_unionId').split(',');
            unionid = '';
            for(var i in unionIdArr)
            {
                $("#unionNameList a").each(function () {
                    unionid = $(this).data('unionid');
                    if (unionid == unionIdArr[i])
                    {
                        $(this).addClass('on');
                    }

                });

            }


        }
        hideAll();//隐藏
    });
    //隐藏function
    function hideAll(){
        //隐藏遮罩层 and 隐藏内容选择
        $('.maskLayer,.nav_con').hide();
        //去掉选择样式
        $('.daka_nav ul li').removeClass('on');
        //筛选下边框
        $('.daka_nav').css('border-bottom','1px solid #e6e6e6');
    }
    //全选
    $('#all_sele').on('click',function(){
        $('.subnav_list a').addClass('on');
    });
    //反选
    $('#back_sele').on('click',function(){
        $('.subnav_list a').removeClass('on');
    });

    //信息提示弹框点击隐藏
    $('#dailogFixBox').on('click',function () {
       $(this).css('display','none');
    });



    //玩法的筛选
    $('#gambleType ul li a').on('click',function () {


        var gambletype = $(this).data('gambletype');
        $('#victoryType ul li').removeClass('on');//去掉综合的筛选
        $('#lvType ul li').removeClass('on');//去掉的等级的筛选
        // $('#priceType ul li').removeClass('on');//去掉的价格的筛选
        var data ={
            gambleType : $(this).data('gambletype'),
            priceType:1
        };
        switch (gambletype)
        {
            case 1:$('#h_val1').html('亚盘<i></i>');break;
            case 2:$('#h_val1').html('竞彩<i></i>');break;
        }
        $('#h_val2').html('综合<i></i>');
        $('#victoryType ul li').eq(0).addClass('on');

        $('#h_val5').html('等级<i></i>');
        $('#lvType ul li').eq(0).addClass('on');

        // $('#h_val3').html('价格<i></i>');
        // $('#priceType ul li').eq(0).addClass('on');

        $('#gambleList ul').html('');

        Cookie.setCookie('newPut_unionId',-1);

        $('#gambleList ul').html('');
        getGambleList(data);
        $("#unionNameList a").addClass('on');
    });

    //综合的筛选
    $('#victoryType ul li a').on('click',function () {
        // $('#priceType ul li').removeClass('on');//去掉的价格的筛选
        var victorytype = $(this).data('victorytype');//综合
        var lvtype = $('#lvType ul .on a').data('lvtype');//等级
        var pricetype = $('#priceType ul .on a').data('pricetype');//价格
        var data = {
            gambleType : $('#gambleType ul .on a').data('gambletype'),//获取玩法
            victoryType : victorytype,//综合筛选
            lvType : lvtype,//等级筛选
            priceType : pricetype,//价格筛选
        };
        switch (victorytype)
        {
            case 0:$('#h_val2').html('综合<i></i>');break;
            case 1:$('#h_val2').html('周胜率<i></i>');break;
            case 2:$('#h_val2').html('高命中<i></i>');break;
            case 3:$('#h_val2').html('连胜多<i></i>');break;
            case 4:$('#h_val2').html('人气旺<i></i>');break;
            case 5:$('#h_val2').html('我的关注<i></i>');break;
        }
        // Cookie.setCookie('newPut_unionId', -1);
        $('#gambleList ul').html('');
        getGambleList(data);
        // $("#unionNameList a").addClass('on');
    });

    //等级的筛选
    $('#lvType ul li a').on('click',function () {

        var victorytype = $('#victoryType ul .on a').data('victorytype');//综合
        var lvtype = $(this).data('lvtype');//价格
        var pricetype = $('#priceType ul .on a').data('pricetype');//价格
        var data = {
            gambleType : $('#gambleType ul .on a').data('gambletype'),//获取玩法
            victoryType : victorytype,//综合筛选
            lvType : lvtype,//等级筛选
            priceType : pricetype,//价格筛选
        };

        switch (lvtype)
        {
            case 0:$('#h_val5').html('等级<i></i>');break;
            case 1:$('#h_val5').html('LV1<i></i>');break;
            case 2:$('#h_val5').html('LV2<i></i>');break;
            case 3:$('#h_val5').html('LV3<i></i>');break;
            case 4:$('#h_val5').html('LV4<i></i>');break;
            case 5:$('#h_val5').html('LV5<i></i>');break;
            case 6:$('#h_val5').html('LV6<i></i>');break;
            case 7:$('#h_val5').html('LV7<i></i>');break;
            case 8:$('#h_val5').html('LV8<i></i>');break;
            case 9:$('#h_val5').html('LV9<i></i>');break;
        }
        // Cookie.setCookie('newPut_unionId', -1);
        $('#gambleList ul').html('');
        getGambleList(data);
        // $("#unionNameList a").addClass('on');
    });

    //价格的筛选
    $('#priceType ul li a').on('click',function () {
        // $('#victoryType ul li').removeClass('on');//去掉的价格的筛选
        var victorytype = $('#victoryType ul .on a').data('victorytype');//综合
        var lvtype = $('#lvType ul .on a').data('lvtype');//等级
        var pricetype = $(this).data('pricetype');//价格
        var data = {
            gambleType : $('#gambleType ul .on a').data('gambletype'),//获取玩法
            victoryType : victorytype,//综合筛选
            lvType : lvtype,//等级筛选
            priceType : pricetype,//价格筛选
        };

        switch (pricetype)
        {
            case 0:$('#h_val3').html('价格<i></i>');break;
            case 1:$('#h_val3').html('价格高<i></i>');break;
            case 2:$('#h_val3').html('价格低<i></i>');break;
        }
        // Cookie.setCookie('newPut_unionId', -1);
        $('#gambleList ul').html('');
        getGambleList(data);
        // $("#unionNameList a").addClass('on');
    });

    //赛事筛选确定
    $("#subnavSubmit").click(function () {

        hideAll();//隐藏筛选

        if($("#unionNameList a.on").length<1){

            //筛选赛事时，如果用户点击不全选就从新改变筛选赛事样式
            $("#unionNameList a").removeClass('on');
            var unionIdArr = Cookie.getCookie('newPut_unionId').split(',');
            for(var i in unionIdArr)
            {
                $("#unionNameList a").each(function () {
                    if ($(this).data('unionid') == unionIdArr[i])
                    {
                        $(this).addClass('on');
                    }

                });

            }

            $('#dailogFixBox').css('display','block');
            $('#dailogContent').html('至少选择一项!');
            return false;
        }
        var unionId = '';
        $("#unionNameList a").each(function () {
            $this = $(this);
            if ($this.hasClass('on')) {
                unionId += $this.data('unionid') + ',';
            }
        });
        Cookie.setCookie('newPut_unionId', unionId);

        var victorytype = $('#victoryType ul .on a').data('victorytype');//中
        var lvtype = $('#lvType ul .on a').data('lvtype');//等级
        var pricetype = $('#priceType ul .on a').data('pricetype');//价格

        var data ={
            gambleType : $('#gambleType ul .on a').data('gambletype'),
            victoryType : victorytype,//综合筛选
            lvType : lvtype,//等级筛选
            priceType : pricetype,//价格筛选
        };
        $('#gambleList ul').html('');
        getGambleList(data)

    });

    //滚动加载滚动---亚盘、竞彩
    $(window).scroll(function () {
        //$(window).scrollTop()这个方法是当前滚动条滚动的距离
        //$(window).height()获取当前窗体的高度
        //$(document).height()获取当前文档的高度
        // var bot = 50; //bot是底部距离的高度
        //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
        if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
        {
            if ($('#emptyList').attr("style") != 'display:block;')
                getGambleListMore();
        }
    });
});

//头像懒加载
function lazyload(){
    $("img.lazy").lazyload({
        effect: "fadeIn",
        threshold : 10,
        failurelimit:10
    });
}


function getGambleList(data)
{
    $('#emptyList').remove();//即将发布提示
    $('#gambleListLoad').css('display','block');//显示加载提示
    $('#gambleListMore').css('display','none');//隐藏加载更多提示
    $('#showLess').css('display','none');//提示数据已经加载完

    //用于分页--
    $('#page').val(2);
    $.ajax({
        type: 'post',
        url: "/Guess/new_put.html",
        data: data,
        dataType: 'json',
        success: function (data) {
            if(data.status==1)
            {
                if (data.unionNameArr != null)
                {
                    getUnionName(data.unionNameArr,data.unionIdArr);
                }
                var list = data.list;
                if (list != null && list != false && list != undefined)
                {
                    $.each(list, function (k, v) {

                        //等级
                        var lvHtml = v['play_type'] == '1' || v['play_type'] == '-1' ? '<em class="ya_text">亚:<i>LV'+v['lv']+'</i></em>' : '<em class="ya_text">竞:<i>LV'+v['lv']+'</i></em>';
                        //连胜
                        var winHtml = v['curr_victs'] > 1 ? '<span>'+v['curr_victs']+'连胜</span>' : '';
                        //十中几
                        var tenHtml = v['tenGambleRate'] > 5 ? '<img src="'+IMAGES+'/index/ic_'+v['tenGambleRate']+'.png" alt="">' : '';
                        //竞彩标识
                        var betCodeHtml = v['play_type'] == '2' || v['play_type'] == '-2' ? '<em>'+v['bet_code']+' </em>' : '';

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

                        if (v['is_quiz'] == 1)
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

                        }
                        else
                        {
                            var tradeCoinHtml = v['tradeCoin'] > 0 ? '<div class="gold">'+v['tradeCoin']+'金币</div>' : '<div class="gold bg_green">免费</div>';

                            resultHtml = '<a href="javascript:;" data-play="'+v['play_type']+'" onclick="payment(this,'+v['id']+','+v['tradeCoin']+',2222)">'+tradeCoinHtml +'</a> ';
                        }

                        var html = '<li class="list">' +
                            ' <a href="//'+DOMAIN+'/expUser/'+v['user_id']+'/'+v['play_type']+'.html">' +
                            ' <div class="n_top clearfix">' +
                            ' <div class="n_top_left">' +
                            ' <img class="lazy" data-original="'+v['face']+'" src="'+IMAGES+'/index/headImg.png" >' +
                            ' <div class="ntl_main fl">' +
                            ' <div class="ntl_name">'+v['nick_name']+lvHtml+' </div>' +
                            ' <div class="ntl_per"><em>周胜: '+v['winrate']+'%</em>'+winHtml+'</div>' +
                            ' </div>' +
                            ' </div>' +
                            ' <div class="n_top_right"> '+tenHtml+'</div>' +
                            ' </div>' +
                            ' <div class="p_1">' +
                            ' <div class="t_vs">'+betCodeHtml+'<em style="color: '+v['union_color']+'">'+v['union_name']+'</em> <em>'+v['gDate']+'</em> </div>' +
                            ' <div class="etip">'+fenxiHtml+coinsHtml+'</div>' +
                            ' </div>' +
                            ' <p class="p_2">'+v['home_team_name']+' VS '+v['away_team_name']+' </p>' +
                            ' <p class="p_3">玩法：<span>'+playHtml+'</span> </p>' +
                            ' </a>'+resultHtml+' </li> ';

                        $('#gambleList ul').append(html);
                    });

                }
                else
                {

                    $('#gambleList').append(
                            '<div id="emptyList" class="paged boxs" style="display:block;">' +
                                ' <div class="load_gif fs24 text-999">' +
                                    ' <div><img src="'+IMAGES+'/none.png" alt="" style="width: 2rem;margin-top: 20%"><p>暂无推荐</p></div>' +
                                ' </div>' +
                            ' </div>'
                        );
                    $('#gambleList').css('height',300);
                }
            }
            else if(data.status==1111)
            {
                //登录
                window.location.href='//'+DOMAIN+'/User/login.html';
            }
            else
            {


            }
        },
        complete:function () {
            $('#gambleListLoad').css('display','none');//隐藏加载提示
            $('#gambleListMore').css('display','block');//显示加载更多提示
            if ($('#gambleList ul').length < 10)
            {
                $('#gambleListMore').css('display','none');//显示加载更多提示
            }

            //头像懒加载
            lazyload();
        }

    });
}

function getGambleListMore()
{
    $('#gambleListMore').css('display','block');//显示加载更多提示
    $('#showLess').css('display','none');//提示数据已经加载完
    var page = $('#page').val();
    var data = {
        gambleType : $('#gambleType ul .on a').data('gambletype'),
        victoryType: $('#victoryType ul .on a').data('victorytype'),
        lvType  : $('#lvType ul .on a').data('lvtype'),
        priceType  : $('#priceType ul .on a').data('pricetype'),
        page       : page,

    };
    $.ajax({
        type: 'post',
        async : false,
        url: "/Guess/new_put.html",
        data: data,
        dataType: 'json',
        success: function (data) {
            if(data.status==1)
            {
                if (data.unionNameArr != null)
                {
                    getUnionName(data.unionNameArr,data.unionIdArr);
                }
                var list = data.list;
                if (list != null)
                {
                    $.each(list, function (k, v) {

                        //等级
                        var lvHtml = v['play_type'] == '1' || v['play_type'] == '-1' ? '<em class="ya_text">亚:<i>LV'+v['lv']+'</i></em>' : '<em class="ya_text">竞:<i>LV'+v['lv']+'</i></em>';
                        //连胜
                        var winHtml = v['curr_victs'] > 1 ? '<span>'+v['curr_victs']+'连胜</span>' : '';
                        //十中几
                        var tenHtml = v['tenGambleRate'] > 5 ? '<img src="'+IMAGES+'/index/ic_'+v['tenGambleRate']+'.png" alt="">' : '';
                        //竞彩标识
                        var betCodeHtml = v['play_type'] == '2' || v['play_type'] == '-2' ? '<em>'+v['bet_code']+' </em>' : '';

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

                        if (v['is_quiz'] == 1)
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

                        }
                        else
                        {
                            var tradeCoinHtml = v['tradeCoin'] > 0 ? '<div class="gold">'+v['tradeCoin']+'金币</div>' : '<div class="gold bg_green">免费</div>';

                            resultHtml = '<a href="javascript:;" data-play="'+v['play_type']+'" onclick="payment(this,'+v['id']+','+v['tradeCoin']+',2222)">'+tradeCoinHtml +'</a> ';
                        }

                        var html = '<li class="list">' +
                            ' <a href="//'+DOMAIN+'/expUser/'+v['user_id']+'/'+v['play_type']+'.html">' +
                            ' <div class="n_top clearfix">' +
                            ' <div class="n_top_left">' +
                            ' <img class="lazy" data-original="'+v['face']+'" src="'+IMAGES+'/index/headImg.png" >' +
                            ' <div class="ntl_main fl">' +
                            ' <div class="ntl_name">'+v['nick_name']+lvHtml+' </div>' +
                            ' <div class="ntl_per"><em>周胜: '+v['winrate']+'%</em>'+winHtml+'</div>' +
                            ' </div>' +
                            ' </div>' +
                            ' <div class="n_top_right"> '+tenHtml+'</div>' +
                            ' </div>' +
                            ' <div class="p_1">' +
                            ' <div class="t_vs">'+betCodeHtml+'<em style="color: '+v['union_color']+'">'+v['union_name']+'</em> <em>'+v['gDate']+'</em> </div>' +
                            ' <div class="etip">'+fenxiHtml+coinsHtml+'</div>' +
                            ' </div>' +
                            ' <p class="p_2">'+v['home_team_name']+' VS '+v['away_team_name']+' </p>' +
                            ' <p class="p_3">玩法：<span>'+playHtml+'</span> </p>' +
                            ' </a>'+resultHtml+' </li> ';


                        $('#gambleList ul').append(html);
                    });

                    page++;

                }
                else
                {
                    $('#gambleListMore').css('display','none');//隐藏加载更多提示
                    $('#showLess').css('display','block');//提示数据已经加载完
                }
            }
            else
            {


            }
        },
        complete:function () {

            $('#page').val(page);
            //头像懒加载
            lazyload();
        }

    });
}
function getUnionName(unionNameArr) {

    $('#unionNameList').html('');
    $.each(unionNameArr,function (k,v) {

        var html = '<a href="javascript:;" data-unionid="'+k+'" >'+v+'</a>';
        $('#unionNameList').append(html);

    });

    var unionIdArr = Cookie.getCookie('newPut_unionId').split(',');
    unionid = '';
    for(var i in unionIdArr)
    {
        $("#unionNameList a").each(function () {
            unionid = $(this).data('unionid');
            if (unionid == unionIdArr[i])
            {
                $(this).addClass('on');
            }

        });

    }

    $('#unionNameList a').on('click',function(){
        if($(this).hasClass('on')){
            $(this).removeClass('on');
        }else{
            $(this).addClass('on');
        }
    });


}

