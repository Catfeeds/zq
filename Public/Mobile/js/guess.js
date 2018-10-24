$(function(){


    //获取点头像跳转前的滚动位置,并滚动到此
    var rankScrollTop = Cookie.getCookie('rankScrollTop');
    var rankGambleType = Cookie.getCookie('rankGambleType');
    var rankDateType = Cookie.getCookie('rankDateType');
    var rankRankType = Cookie.getCookie('rankRankType');

    $('#ypTable table').html('');
    $('#jcTable table').html('');
    if (gambleType)
    {
        $('#ypContent').css('display','none');
        $('#jcContent').css('display','none');

        // $('#navTab a').removeClass('on');
        // $('#ypRankNav ul li').removeClass('on');
        // $('#jcRankNav ul li').removeClass('on');
        //亚盘
        if (gambleType == 1)
        {
            $('#ypContent').css('display','block');
            // $('#yp').addClass('on');

            // $('#ypRankNav ul li a').each(function () {
            //     if ($(this).data('ranktype') == rankDateType) {
            //         $(this).parent().addClass('on');
            //     }
            // });

            //胜率
            if (rankRankType == 'profit')
            {
                //盈利榜
                // ypProfitList({gambleType : 1, dateType: rankDateType})
                ypRankList({gambleType : gambleType, dateType: dateType});
            }
            else
            {
                // ypRankList({gambleType : 1, dateType: rankDateType});
                ypRankList({gambleType : gambleType, dateType: dateType});
            }
        }
        else
        {
            $('#jcContent').css('display','block');
            // $('#jc').addClass('on');
            // $('#jcRankNav ul li a').each(function () {
            //     if ($(this).data('ranktype') == rankDateType) {
            //         $(this).parent().addClass('on');
            //     }
            //
            // });
            //竞彩
            if (rankRankType == 'profit')
            {
                //盈利榜
                // jcProfitList({gambleType : 2, dateType: rankDateType})
                jcProfitList({gambleType : gambleType, dateType: dateType});
            }
            else
            {
                //胜率
                // jcRankList({gambleType : 2, dateType: rankDateType});
                jcRankList({gambleType : gambleType, dateType: dateType});
            }
        }
        Cookie.delCookie('rankGambleType');
        Cookie.delCookie('rankDateType');
        Cookie.delCookie('rankRankType');
    }
    else
    {
        //一开始默认时亚盘日榜
        // ypRankList({
        //     gambleType : 1,
        //     dateType   : 4
        // });
        ypRankList({gambleType : gambleType, dateType: dateType});
    }

    //亚盘
    if (rankGambleType == 1)
    {

        if (rankRankType == 'rank')
        {
            //胜率
            $("#rankType option").eq(0).attr("selected",true);

        }
        else if (rankRankType == 'profit')
        {
            //盈利榜
            $("#rankType option").eq(1).attr("selected",true);

        }

    }
    else
    {
        if (rankRankType == 'rank')
        {
            //胜率
            $("#jcRankType option").eq(0).attr("selected",true);

        }
        else if (rankRankType == 'profit')
        {
            //盈利榜
            $("#jcRankType option").eq(1).attr("selected",true);

        }
    }
    //滚动操作
    if (rankScrollTop) {
        $("html, body").animate({scrollTop: rankScrollTop}, 1000);
        Cookie.delCookie('rankScrollTop');

    }
    //记录点用户
    // 头像跳转前的滚动位置---亚盘
    $(document).on('click','table .js-list .headImg a',function () {
        var topHeight = $(document).scrollTop();
        Cookie.setCookie('rankScrollTop', topHeight, 60000);//点击位置

        var gambleType = $('#navTab .on').data('gambletype');
        var dateType = '';
        var rankType = '';
        if (gambleType == 1)
        {
            dateType = $('#ypRankNav ul .on a').data('ranktype');
            rankType = $('#rankType').val();
        }
        else
        {
            dateType = $('#jcRankNav ul .on a').data('ranktype');
            rankType = $('#jcRankType').val();
        }

        Cookie.setCookie('rankGambleType', gambleType, 60000);//筛选的玩法
        Cookie.setCookie('rankDateType', dateType, 60000);//筛选的胜率
        Cookie.setCookie('rankRankType', rankType, 60000);//筛选的价格

    });



    //亚盘Tab
    $('#yp').on('click',function () {
        return true;
        $('#jc').removeClass('on');
        $('#jcContent').css({'display':'none'});
        $(this).addClass('on');
        $('#ypContent').css({'display':'block'});

        //当没有请求过数据时,就请求一次
        if ($('#ypTable table').html() == '')
        {
            // var data = {
            //     gambleType : 1,
            //     dateType   : 4,
            // };
            var data = {gambleType : gambleType, dateType: dateType};
            ypRankList(data);
            $('#ypRankNav ul li a').each(function () {
                if ($(this).data('ranktype') == 4) {
                    $(this).parent().addClass('on');
                }
            });
        }
    });

    //竞彩Tab
    $('#jc').on('click',function () {
        return true;
        $('#yp').removeClass('on');
        $('#ypContent').css({'display':'none'});
        $(this).addClass('on');
        $('#jcContent').css({'display':'block'});

        //当没有请求过数据时,就请求一次
        if ($('#jcTable table').html() == '')
        {
            // var data = {
            //     gambleType : 2,
            //     dateType   : 4,
            // };
            var data = {
                gambleType : gambleType,
                dateType   : dateType,
            };
            jcRankList(data);
            $('#jcRankNav ul li a').each(function () {
                if ($(this).data('ranktype') == 4) {
                    $(this).parent().addClass('on');
                }
            });
        }
    });

    //胜率，盈利榜的筛选---亚盘
    $(document).on('change','#rankType',function () {

        if ($('#yp').hasClass('on') && $("#ypRankNav ul li").hasClass('on'))//亚盘
        {
            $("#ypTable table tbody tr").eq(0).nextAll().remove();//清空筛选框一下的行
            var rankTypeName = $(this).val();//获取筛选的值
            var data = {
                gambleType : 1,//亚盘
                dateType   : $("#ypRankNav ul .on a").data('ranktype'),//获取日或周或月或季
                rankTypeName:rankTypeName,
            };
            var data = {
                gambleType : gambleType,//亚盘
                dateType   : dateType,//获取日或周或月或季
                rankTypeName:rankTypeName,
            };
            if (rankTypeName == 'rank')//胜率
            {
                ypRankList(data);
            }
            else if (rankTypeName == 'profit')//盈利榜
            {
                ypProfitList(data)
            }

        }
    });

    //胜率，盈利榜的筛选---竞彩
    $(document).on('change','#jcRankType',function () {

        if ($('#jc').hasClass('on') && $("#jcRankNav ul li").hasClass('on'))
        {
            $("#jcTable table tbody tr").eq(0).nextAll().remove();//清空筛选框一下的行
            var jcRankTypeName = $(this).val();//获取筛选的值
            // var data = {
            //     gambleType : 2,//竞彩
            //     dateType   : $("#jcRankNav ul .on a").data('ranktype'),//获取日或周或月或季
            //     rankTypeName:jcRankTypeName,
            // };
            var data = {
                gambleType : gambleType,//竞彩
                dateType   : dateType,//获取日或周或月或季
                rankTypeName:jcRankTypeName,
            };
            //胜率
            if (jcRankTypeName == 'rank')
            {
                jcRankList(data);
            }
            else if (jcRankTypeName == 'profit')
            {
                //盈利榜
                jcProfitList(data)
            }

        }
    });

    //显示有竞猜用户
    if (Cookie.getCookie('m_quiz') == 1) {
        $(".gambleCheck").attr("checked",true);
    }

    $(document).on('change','.gambleCheck',function () {


        if ($(this).is(':checked'))
        {
            Cookie.setCookie('m_quiz',1);
            $(".gambleCheck").attr("checked",true);
        }
        else
        {
            Cookie.setCookie('m_quiz',false);
            $(".gambleCheck").attr("checked",false);
        }


        var rankTypeName = $('#rankType').val();
        var jcRankTypeName = $('#jcRankType').val();

        $("#ypTable table tbody tr").eq(0).nextAll().remove();//清空筛选框一下的行
        $("#jcTable table tbody tr").eq(0).nextAll().remove();//清空筛选框一下的行
        //亚盘
        // var data = {
        //     rankTypeName : rankTypeName,
        //     gambleType : 1,//亚盘
        //     dateType   : $('#ypRankNav ul .on a').data('ranktype'),//获取日或周或月或季
        // };
        var data = {
            rankTypeName : rankTypeName,
            gambleType : gambleType,//亚盘
            dateType   : dateType,//获取日或周或月或季
        };

        if (rankTypeName == 'rank')//胜率
        {
            ypRankList(data);
        }
        else if (rankTypeName == 'profit')//盈利榜
        {
            ypProfitList(data)
        }

        //竞彩
        // var jcData = {
        //     rankTypeName:jcRankTypeName,
        //     gambleType : 2,//竞彩
        //     dateType   : $('#jcRankNav ul .on a').data('ranktype'),//获取日或周或月或季
        // };
        var jcData = {
            rankTypeName:jcRankTypeName,
            gambleType : gambleType,//竞彩
            dateType   : dateType,//获取日或周或月或季
        };
        //胜率
        if (jcRankTypeName == 'rank')
        {
            jcRankList(jcData);
        }
        else if (jcRankTypeName == 'profit')
        {
            //盈利榜
            jcProfitList(jcData)
        }

    });




    //亚盘日、周、月、季排行榜
    $("#ypRankNav ul li a").on('click',function () {
        return true;
        $("#ypRankNav ul li").removeClass('on');
        $(this).parent().addClass('on');
        // var data = {
        //     gambleType : 1,
        //     dateType   : $(this).data('ranktype'),
        // };
        var data = {
            gambleType : gambleType,
            dateType   : dateType,
        };
        $('#ypTable table').html('');
        ypRankList(data);

    });
    //竞彩日、周、月、季排行榜
    $("#jcRankNav ul li a").on('click',function () {
        return true;
        $("#jcRankNav ul li").removeClass('on');
        $(this).parent().addClass('on');
        // var data = {
        //     gambleType : 2,
        //     dateType   : $(this).data('ranktype'),
        // };
        var data = {
            gambleType : gambleType,
            dateType   : dateType,
        };
        $('#jcTable table').html('');
        jcRankList(data);

    });

    //头像懒加载
    function lazyload(){
        $("img.lazy").lazyload({
            effect: "fadeIn",
            threshold : 10,
            failurelimit:10
        });
    }


    //亚盘排行榜
    function ypRankList(data)
    {
        $('#rankListMore').css({'display':'none'});//隐藏底部的加载块
        $('#showLess').css({'display':'none'});//隐藏底部的加载完提示
        //胜率、盈利榜的筛选
        if (data.rankTypeName == undefined || data.rankTypeName == '')//当不是做胜率、盈利筛选操作时，重新加载选择框
        {
            $('#ypTable table').append('<tr bgcolor="#fff">' +
                    ' <td colspan="9" class="f-td">' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <nav class="win_rank_sele fl">' +
                            ' <select name="rankTypeName" id="rankType">' +
                                '<option value="rank" >胜率榜</option>' +
                                '<option value="profit">盈利榜</option>'+
                            ' </select>' +
                        ' </nav>' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <div class="fl fs22 red update_time">每天上午11点更新榜单</div>' +
                        ' <div class="fr text-999 show_user">' +
                            ' <span>显示有推荐用户</span><input type="checkbox"    class="gambleCheck">' +
                        ' </div>' +
                    ' </td>' +
                ' </tr>' );
        }

        var html_rank = '<tr bgcolor="c5d6e0" class="tr_rtitle">' +
            '<td style="text-align:left!important;padding-left:.1rem" colspan="2">名次</td>' +
            '<td style="text-align:left!important;padding-left:.2rem">昵称</td>' +
            '<td><span>胜</span></td>' +
            '<td><span>平</span></td>' +
            '<td><span>负</span></td>' +
            '<td>胜率</td>' +
            '<td>盈利</td>' +
            '<td>连胜</td>' +
            '</tr>';
        $('#ypTable table').append(html_rank);

        //提示正在加载
        $('#ypTable table').append('<tr id="rankListLoad" bgcolor="#fff">' +
                ' <td colspan="9" class="f-td">' +
                    ' <div  class="paged boxs" style="display:block;">' +
                    ' <div class="load_gif fs24 text-999">' +
                    ' <span><img src="/Public/Mobile/images/load.gif"></span>' +
                    ' <span>正在加载更多的数据...</span>' +
                    ' </div>' +
                    ' </div>' +
                ' </td>' +
            ' </tr>');
        //用于分页--
        $('#ypTable table').append('<input id="ypPage" type="hidden" value="1">');
        $.ajax({
            type: 'post',
            data : data,
            url: "/Guess/rank.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1)
                {

                    if (data.is_login != null && data.is_login != false)
                    {
                        //我的排名
                        var win = data.my_rank['win'] == '' || data.my_rank['win'] == undefined ? 0 : data.my_rank['win'];
                        var level = data.my_rank['level'] == '' || data.my_rank['level'] == undefined ? 0 : data.my_rank['level'];
                        var transport = data.my_rank['transport'] == '' || data.my_rank['transport'] == undefined ? 0 : data.my_rank['transport'];
                        var winrate = data.my_rank['winrate'] == '' || data.my_rank['winrate'] == undefined ? '0' : data.my_rank['winrate'];
                        var pointCount = data.my_rank['pointCount'] == '' || data.my_rank['pointCount'] == undefined ? '0' : data.my_rank['pointCount'];

                        $('#ypTable table').append('<tr bgcolor="#fee4c9" id="rank_tr">' +
                            ' <td colspan="3">我的排名：<span>'+data.my_rank['ranking']+'</span></td>' +
                            ' <td width="10%" class="fs24"><em class="red">'+win+'</em></td>' +
                            ' <td width="10%" class="fs24"><em class="blue">'+level+'</em></td>' +
                            ' <td width="10%" class="fs24"><em class="green">'+transport+'</em></td>' +
                            ' <td width="10%" class="fs24 red">'+winrate+'%</td>' +
                            ' <td width="8%">'+pointCount+'</td> ' +
                            ' <td width="8%">'+data.my_rank['curr_victs']+'</td> ' +
                            '</tr>' );
                    }
                    var list = data.list;
                    if (list != null) {
                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = (v['is_quiz'] == undefined || v['is_quiz'] == null) && data.m_quiz == 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['is_quiz'] == undefined || v['is_quiz'] == null ? '' : '<span class="fl"></span>';

                            //赢的场数
                            var winCount = Number(v['win'])+Number(v['half']);
                            //输的场数
                            var loseCount = Number(v['transport'])+Number(v['donate']);
                            //推荐场数
                            var gambleCount = loseCount+Number(v['level'])+winCount;
                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/1.html';
                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +

                                        ' <td class="rankImg">'+topThreeImg+' </td>' +
                                        ' <td class="headImg"><a href="'+urlHtml+'"><img class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"  /></a></td>' +
                                        ' <td class="q-tl flogImg">' +
                                            ' <p class="fs26 clearfix">' +
                                                ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                                +isQuizHtml+
                                            '</p>' +
                                            ' <p class="fs22 text-999">推荐'+gambleCount+'场</p>' +
                                        ' </td>' +
                                        ' <td class="fs24"><em class="red">'+winCount+'</em></td>' +
                                        ' <td class="fs24"><em class="blue">'+v['level']+'</em></td>' +
                                        ' <td class="fs24"><em class="green">'+loseCount+'</em></td>' +
                                        ' <td class="fs24 red">'+v['winrate']+'%</td>' +
                                        ' <td>'+v['pointCount']+'</td>' +
                                        '<td>'+v['curr_victs']+'</td>' +
                                ' </tr>';
                            $('#ypTable table').append(html);

                            trHref();
                        });
                    }
                    else
                    {
                        $('#ypTable table').append('<tr bgcolor="#fff">' +
                                ' <td colspan="9" class="f-td">' +
                                    '<div id="ypEmptyList" class="paged boxs" style="display:block;">' +
                                    ' <div class="load_gif fs24 text-999">' +
                                        ' <span>暂无数据</span>' +
                                    ' </div>' +
                                    ' </div>'+
                                ' </td>' +
                            ' </tr>');
                    }
                }
                else
                {


                }
            },
            complete:function () {
                //显示有推荐用户
                if (Cookie.getCookie('m_quiz') == 1) {
                    $(".gambleCheck").attr("checked",true);
                }
                $('#rankListLoad').css({'display':'none'});
                //头像懒加载
                lazyload();
            }
        });
    }

    //亚盘排行榜列表更多加载操作----滚动加载---同步ajax--胜率
    function ypRankListMore()
    {
        $('#rankListMore').css({'display':'block'});
        var page = $('#ypPage').val();//默认重第二页开始--亚盘
        page++;
        // var data = {
        //     gambleType : 1,
        //     dateType   : $("#ypRankNav ul li.on").data('ranktype'),
        //     page   : page,
        // };
        var data = {
            gambleType : gambleType,
            dateType   : dateType,
            page   : page,
        };
        $.ajax({
            type: 'post',
            async : false,
            data : data,
            url: "/Guess/rank.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1)
                {

                    var list = data.list;
                    if (list != null) {

                        $('ypEmptyList').css({'display':'none'});//当滚动新数据时，隐藏暂无数据的提示
                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = (v['is_quiz'] == undefined || v['is_quiz'] == null) && data.m_quiz == 1 ? 'hidden' : '';
                            //前三名的奖牌图片
                            var topThreeImg =  v['ranking'];
                            //是否有新推荐的标记
                            var isQuizHtml = v['is_quiz'] == undefined || v['is_quiz'] == null ? '' : '<span class="fl"></span>';

                            //赢的场数
                            var winCount = Number(v['win'])+Number(v['half']);
                            //输的场数
                            var loseCount = Number(v['transport'])+Number(v['donate']);
                            //推荐场数
                            var gambleCount = loseCount+Number(v['level'])+winCount;

                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/1.html';
                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +

                                        ' <td class="rankImg">'+topThreeImg+' </td>' +
                                        ' <td class="headImg"><a href="'+urlHtml+'"><img  class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png" /></a></td>' +
                                        ' <td class="q-tl flogImg">' +
                                        ' <p class="fs26 clearfix">' +
                                        ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                        +isQuizHtml+
                                        '</p>' +
                                        ' <p class="fs22 text-999">推荐'+gambleCount+'场</p>' +
                                        ' </td>' +
                                        ' <td class="fs24"><em class="red">'+winCount+'</em></td>' +
                                        ' <td class="fs24"><em class="blue">'+v['level']+'</em></td>' +
                                        ' <td class="fs24"><em class="green">'+loseCount+'</em></td>' +
                                        ' <td class="fs24 red">'+v['winrate']+'%</td>' +
                                        ' <td>'+v['pointCount']+'</td>' +
                                        '<td>'+v['curr_victs']+'</td>' +
                                        ' </tr>';
                            $('#ypTable table').append(html);
                            trHref();
                        });
                        // flag = false;
                        // if (data.list.length < 30) {
                        //     $("#showLess").show();
                        //     flag = true;
                        // }else{
                        //     $("#loadMore").show();
                        // }
                    }
                    else
                    {
                        $('#rankListMore').css({'display':'none'});
                        $("#showLess").show();
                    }
                }
                else
                {


                }
            },
            complete:function () {
                $('#rankListLoad').css({'display':'none'});
                $('#ypPage').val(page);

                //头像懒加载
                lazyload();
            }
        });

    }

    //亚盘盈利榜
    function ypProfitList(data)
    {
        $('#rankListMore').css({'display':'none'});//隐藏底部的加载块
        $('#showLess').css({'display':'none'});//隐藏底部的加载完提示

        //胜率、盈利榜的筛选
        if (data.rankTypeName == undefined || data.rankTypeName == '')//当不是做胜率、盈利筛选操作时，重新加载选择框
        {
            $('#ypTable table').append('<tr bgcolor="#fff">' +
                    ' <td colspan="9" class="f-td">' +
                    ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <nav class="win_rank_sele fl">' +
                            ' <select name="rankTypeName" id="rankType">' +
                                '<option value="rank" >胜率榜</option>' +
                                '<option value="profit">盈利榜</option>'+
                            ' </select>' +
                        ' </nav>' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <div class="fl fs22 red update_time">每天上午11点更新榜单</div>' +
                        ' <div class="fr text-999 show_user">' +
                            ' <span>显示有推荐用户</span><input type="checkbox"     class="gambleCheck">' +
                        ' </div>' +
                    ' </td>' +
                ' </tr>' );
        }
        var html_rank = '<tr bgcolor="c5d6e0" class="tr_rtitle">' +
            '<td style="text-align:left!important;padding-left:.1rem" colspan="2">名次</td>' +
            '<td style="text-align:left!important;padding-left:.2rem">昵称</td>' +
            '<td><span>胜</span></td>' +
            '<td><span>平</span></td>' +
            '<td><span>负</span></td>' +
            '<td>胜率</td>' +
            '<td>盈利</td>' +
            '<td>连胜</td>' +
            '</tr>';
        $('#ypTable table').append(html_rank);

        //提示正在加载
        $('#ypTable table').append('<tr id="rankListLoad" bgcolor="#fff">' +
                ' <td colspan="9" class="f-td">' +
                    ' <div  class="paged boxs" style="display:block;">' +
                        ' <div class="load_gif fs24 text-999">' +
                            ' <span><img src="/Public/Mobile/images/load.gif"></span>' +
                            ' <span>正在加载更多的数据...</span>' +
                        ' </div>' +
                    ' </div>' +
                ' </td>' +
            ' </tr>');
        //用于分页--
        $('#ypTable table').append('<input id="ypPage" type="hidden" value="1">');
        $.ajax({
            type: 'post',
            data : data,
            url: "/Guess/profit.html",
            dataType: 'json',
            success: function (data) {


                if(data.status==1)
                {
                    if (data.is_login != null && data.is_login != false)
                    {
                        var ranking = data.myRank['ranking'] == '' || data.myRank['ranking'] == undefined ? '未上榜' : data.myRank['ranking'];
                        var pointCount = data.myRank['pointCount'] == '' || data.myRank['pointCount'] == undefined ? 0 : data.myRank['pointCount'];
                        var level = data.myRank['level'] == '' || data.myRank['level'] == undefined ? 0 : data.myRank['level'];
                        var winrate = data.myRank['winrate'] == '' || data.myRank['winrate'] == undefined ? 0 : data.myRank['winrate'];
                        var winCount = Number(data.myRank['win']) + Number(data.myRank['half']) ;
                        var loseCount = Number(data.myRank['transport']) + Number(data.myRank['donate']) ;

                        $('#ypTable table').append('<tr bgcolor="#fee4c9" id="rank_tr">' +
                            ' <td colspan="3">我的排名：<span>'+ranking+'</span></td>' +
                            ' <td width="10%" class="fs24"><em class="red">'+winCount+'</em></td>' +
                            ' <td width="10%" class="fs24"><em class="blue">'+level+'</em></td>' +
                            ' <td width="10%" class="fs24"><em class="green">'+loseCount+'</em></td>' +
                            ' <td width="10%" class="fs24 red">'+winrate+'%</td>' +
                            ' <td width="8%">'+pointCount+'</td> ' +
                            ' <td width="8%">'+data.myRank['curr_victs']+'</td> ' +
                            '</tr>' );
                    }
                    var list = data.list;
                    if (list != null) {
                        //我的盈利榜排名

                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = v['today_gamble'] != 1 && data.m_quiz== 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['today_gamble'] !=1 ? '' : '<span class="fl"></span>';

                            //赢的场数
                            var winCount = Number(v['win'])+Number(v['half']);
                            //输的场数
                            var loseCount = Number(v['transport'])+Number(v['donate']);
                            //推荐场数
                            var gambleCount = loseCount+Number(v['level'])+winCount;

                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/1.html';

                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +

                                ' <td class="rankImg">'+topThreeImg+' </td>' +
                                ' <td class="headImg"><a href="'+urlHtml+'"><img class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"/></a></td>' +
                                ' <td class="q-tl flogImg">' +
                                ' <p class="fs26 clearfix">' +
                                ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                +isQuizHtml+
                                '</p>' +
                                ' <p class="fs22 text-999">推荐'+gambleCount+'场</p>' +
                                ' </td>' +
                                ' <td class="fs24"><em class="red">'+winCount+'</em></td>' +
                                ' <td class="fs24"><em class="blue">'+v['level']+'</em></td>' +
                                ' <td class="fs24"><em class="green">'+loseCount+'</em></td>' +
                                ' <td class="fs24 red">'+v['winrate']+'%</td>' +
                                ' <td>'+v['pointCount']+'</td>' +
                                '<td>'+v['curr_victs']+'</td>' +
                                ' </tr>';
                            $('#ypTable table').append(html);
                            trHref();
                        });
                    }
                    else
                    {
                        $('#ypTable table').append('<tr bgcolor="#fff">' +
                                ' <td colspan="8" class="f-td">' +
                                    '<div id="ypEmptyList" class="paged boxs" style="display:block;">' +
                                        ' <div class="load_gif fs24 text-999">' +
                                            ' <span>暂无数据</span>' +
                                        ' </div>' +
                                    ' </div>'+
                                ' </td>' +
                            ' </tr>');
                    }

                }
                else
                {


                }
            },
            complete:function () {
                //显示有推荐用户
                if (Cookie.getCookie('m_quiz') == 1) {
                    $(".gambleCheck").attr("checked",true);
                }
                $('#rankListLoad').css({'display':'none'});

                //头像懒加载
                lazyload();
            }
        });
    }
    //亚盘排行榜列表更多加载操作----滚动加载---同步ajax--盈利
    function ypProfitListMore()
    {
        $('#rankListMore').css({'display':'block'});
        var page = $('#ypPage').val();//默认重第二页开始--亚盘
        page++;
        // var data = {
        //     gambleType : 1,
        //     dateType   : $("#ypRankNav ul .on a").data('ranktype'),
        //     page   : page,
        // };
        var data = {
            gambleType : gambleType,
            dateType   : dateType,
            page   : page,
        };
        $.ajax({
            type: 'post',
            async : false,
            data : data,
            url: "/Guess/profit.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1)
                {

                    var list = data.list;
                    if (list != null) {
                        $('ypEmptyList').css({'display':'none'});//当滚动新数据时，隐藏暂无数据的提示
                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = v['today_gamble'] != 1 && data.m_quiz == 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['today_gamble'] !=1 ? '' : '<span class="fl"></span>';

                            //赢的场数
                            var winCount = Number(v['win'])+Number(v['half']);
                            //输的场数
                            var loseCount = Number(v['transport'])+Number(v['donate']);
                            //推荐场数
                            var gambleCount = loseCount+Number(v['level'])+winCount;

                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/1.html';

                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +

                                ' <td class="rankImg">'+topThreeImg+' </td>' +
                                ' <td class="headImg"><a href="'+urlHtml+'"><img  class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"/></a></td>' +
                                ' <td class="q-tl flogImg">' +
                                ' <p class="fs26 clearfix">' +
                                ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                +isQuizHtml+
                                '</p>' +
                                ' <p class="fs22 text-999">推荐'+gambleCount+'场</p>' +
                                ' </td>' +
                                ' <td class="fs24"><em class="red">'+winCount+'</em></td>' +
                                ' <td class="fs24"><em class="blue">'+v['level']+'</em></td>' +
                                ' <td class="fs24"><em class="green">'+loseCount+'</em></td>' +
                                ' <td class="fs24 red">'+v['winrate']+'%</td>' +
                                ' <td>'+v['pointCount']+'</td>' +
                                '<td>'+v['curr_victs']+'</td>' +
                                ' </tr>';
                            $('#ypTable table').append(html);
                            trHref();
                        });
                    }

                    else
                    {
                        $('#rankListMore').css({'display':'none'});
                        $("#showLess").show();
                    }
                }
                else
                {


                }
            },
            complete:function () {
                $('#rankListLoad').css({'display':'none'});
                $('#ypPage').val(page);

                //头像懒加载
                lazyload();
            }
        });
    }

    //竞彩胜率
    function jcRankList(data)
    {
        $('#jcRankListMore').css({'display':'none'});//隐藏底部的加载块
        $('#jcsShowLess').css({'display':'none'});//隐藏底部的数据加载完的提示

        //胜率、盈利榜的筛选
        if (data.rankTypeName == undefined || data.rankTypeName == '')//当不是做胜率、盈利筛选操作时，重新加载选择框
        {
            $('#jcTable table').append('<tr bgcolor="#fff">' +
                '   <td colspan="8" class="f-td">' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <nav class="win_rank_sele fl">' +
                            ' <select name="jcRankTypeName" id="jcRankType">' +
                                '<option value="rank" >胜率榜</option>' +
                                '<option value="profit">盈利榜</option>'+
                            ' </select>' +
                        ' </nav>' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <div class="fl fs22 red update_time">每天上午11点更新榜单</div>' +
                        ' <div class="fr text-999 show_user">' +
                            ' <span>显示有推荐用户</span><input type="checkbox"  class="gambleCheck">' +
                        ' </div>' +
                    ' </td>' +
                ' </tr>' );
        }
        var html_rank = '<tr bgcolor="c5d6e0" class="tr_rtitle">' +
            '<td style="text-align:left!important;padding-left:.1rem" colspan="2">名次</td>' +
            '<td style="text-align:left!important;padding-left:.2rem">昵称</td>' +
            '<td><span>胜</span></td>' +
            '<td><span>负</span></td>' +
            '<td>胜率</td>' +
            '<td>盈利</td>' +
            '<td>连胜</td>' +
            '</tr>';
        $('#jcTable table').append(html_rank);

        //提示正在加载
        $('#jcTable table').append('<tr id="jcRankListLoad" bgcolor="#fff">' +
                ' <td colspan="8" class="f-td">' +
                    ' <div  class="paged boxs" style="display:block;">' +
                        ' <div class="load_gif fs24 text-999">' +
                            ' <span><img src="/Public/Mobile/images/load.gif"></span>' +
                            ' <span>正在加载更多的数据...</span>' +
                        ' </div>' +
                    ' </div>' +
                ' </td>' +
            ' </tr>');

        //用于分页--
        $('#jcTable table').append('<input id="jcPage" type="hidden" value="1">');
        $.ajax({
            type: 'post',
            data : data,
            url: "/Guess/rank.html",
            dataType: 'json',
            success: function (data) {

                if(data.status==1)
                {
                    if (data.is_login != null && data.is_login != false)
                    {
                        var win = data.my_rank['win'] == '' || data.my_rank['win'] == undefined ? 0 : data.my_rank['win'];
                        var transport = data.my_rank['transport'] == '' || data.my_rank['transport'] == undefined ? 0 : data.my_rank['transport'];
                        var winrate = data.my_rank['winrate'] == '' || data.my_rank['winrate'] == undefined ? '0' : data.my_rank['winrate'];
                        var pointCount = data.my_rank['pointCount'] == '' || data.my_rank['pointCount'] == undefined ? '0' : data.my_rank['winrate'];
                        var curr_victs = data.my_rank['curr_victs'] == '' || data.my_rank['curr_victs'] == undefined ? '0' : data.my_rank['curr_victs'];

                        $('#jcTable table').append('<tr bgcolor="#fee4c9" id="rank_tr">' +
                            ' <td colspan="3">我的排名：<span>'+data.my_rank['ranking']+'</span></td>' +
                            ' <td width="10%" class="fs24"><em class="red">'+win+'</em></td>' +
                            ' <td width="10%" class="fs24"><em class="green">'+transport+'</em></td>' +
                            ' <td width="10%" class="fs24 red">'+winrate+'%</td>' +
                            ' <td width="8%">'+pointCount+'</td> ' +
                            ' <td width="8%">'+curr_victs+'</td> ' +
                            '</tr>' );
                    }
                    var list = data.list;
                    if (list != null) {
                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = (v['is_quiz'] == undefined || v['is_quiz'] == null) && data.m_quiz == 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['is_quiz'] == undefined || v['is_quiz'] == null ? '' : '<span class="fl"></span>';

                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/2.html';
                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +
                                ' <td width="8%" class="rankImg">'+topThreeImg+' </td>' +
                                ' <td width="12%" class="headImg"><a href="'+urlHtml+'"><img  class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"/></a></td>' +
                                ' <td width="32%" class="q-tl flogImg">' +
                                ' <p class="fs26 clearfix">' +
                                ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                +isQuizHtml+
                                '</p>' +
                                ' <p class="fs22 text-999">推荐'+v['gameCount']+'场</p>' +
                                ' </td>' +
                                ' <td width="10%" class="fs24"><em class="red">'+v['win']+'</em></td>' +
                                ' <td width="10%" class="fs24"><em class="green">'+v['transport']+'</em></td>' +
                                ' <td width="10%" class="fs24 red">'+v['winrate']+'%</td>' +
                                ' <td width="8%">'+v['pointCount']+' </td>' +
                                ' <td width="8%">'+v['curr_victs']+' </td>' +
                                ' </tr>';
                            $('#jcTable table').append(html);
                            trHref();
                        });
                    }
                    else
                    {
                        $('#jcTable table').append('<tr bgcolor="#fff">' +
                                ' <td colspan="8" class="f-td">' +
                                    '<div id="jcEmptyList" class="paged boxs" style="display:block;">' +
                                        ' <div class="load_gif fs24 text-999">' +
                                        ' <span>暂无数据</span>' +
                                        ' </div>' +
                                    ' </div>'+
                                ' </td>' +
                            ' </tr>');
                    }
                }
                else
                {


                }
            },
            complete:function () {
                //显示有推荐用户
                if (Cookie.getCookie('m_quiz') == 1) {
                    $(".gambleCheck").attr("checked",true);
                }
                $('#jcRankListLoad').css({'display':'none'});

                //头像懒加载
                lazyload();
            }
        });

    }
    //足球竞彩胜率加载更多---滚动加载---同步ajax--胜率
    function jcRankListMore()
    {
        $('#jcRankListMore').css({'display':'block'});
        var page = $('#jcPage').val();//默认重第二页开始--亚盘
        page++;
        // var data = {
        //     gambleType : 2,
        //     dateType   : $("#jcRankNav ul .on a").data('ranktype'),
        //     page   : page,
        // };
        var data = {
            gambleType : gambleType,
            dateType   : dateType,
            page   : page,
        };
        $.ajax({
            type: 'post',
            async : false,
            data : data,
            url: "/Guess/rank.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1)
                {
                    var list = data.list;
                    if (list != null) {
                        $('jcEmptyList').css({'display':'none'});//当滚动新数据时，隐藏暂无数据的提示
                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = (v['is_quiz'] == undefined || v['is_quiz'] == null) && data.m_quiz == 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['is_quiz'] == undefined || v['is_quiz'] == null ? '' : '<span class="fl"></span>';

                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/2.html';
                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +
                                    ' <td width="8%" class="rankImg">'+topThreeImg+' </td>' +
                                    ' <td width="12%" class="headImg"><a href="'+urlHtml+'"><img class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"/></a></td>' +
                                    ' <td width="32%" class="q-tl flogImg">' +
                                    ' <p class="fs26 clearfix">' +
                                    ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                    +isQuizHtml+
                                    '</p>' +
                                    ' <p class="fs22 text-999">推荐'+v['gameCount']+'场</p>' +
                                    ' </td>' +
                                    ' <td width="10%" class="fs24"><em class="red">'+v['win']+'</em></td>' +
                                    ' <td width="10%" class="fs24"><em class="green">'+v['transport']+'</em></td>' +
                                    ' <td width="10%" class="fs24 red">'+v['winrate']+'%</td>' +
                                    ' <td width="8%">'+v['pointCount']+' </td>' +
                                    ' <td width="8%">'+v['curr_victs']+' </td>' +
                                    ' </tr>';
                            $('#jcTable table').append(html);
                            trHref();
                        });
                    }
                    else
                    {
                        $('#jcRankListMore').css({'display':'none'});
                        $("#jcShowLess").show();
                    }
                }
                else
                {


                }
            },
            complete:function () {
                $('#jcRankListLoad').css({'display':'none'});
                $('#jcPage').val(page);

                //头像懒加载
                lazyload();
            }
        });

    }

    //竞彩盈利
    function jcProfitList(data)
    {
        $('#jcRankListMore').css({'display':'none'});//隐藏底部的加载块
        $('#jcsShowLess').css({'display':'none'});//隐藏底部的数据加载完的提示

        //胜率、盈利榜的筛选
        if (data.rankTypeName == undefined || data.rankTypeName == '')//当不是做胜率、盈利筛选操作时，重新加载选择框
        {
            $('#jcTable table').append('<tr bgcolor="#fff">' +
                    '   <td colspan="8" class="f-td">' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <nav class="win_rank_sele fl">' +
                            ' <select name="jcRankTypeName" id="jcRankType">' +
                            '<option value="rank" >胜率榜</option>' +
                            '<option value="profit">盈利榜</option>'+
                            ' </select>' +
                        ' </nav>' +
                        ' <!-- 胜率榜 盈率榜 s-->' +
                        ' <div class="fl fs22 red update_time">每天上午11点更新榜单</div>' +
                        ' <div class="fr text-999 show_user">' +
                            ' <span>显示有推荐用户</span><input type="checkbox" class="gambleCheck" >' +
                        ' </div>' +
                    ' </td>' +
                ' </tr>' );
        }
        var html_rank = '<tr bgcolor="c5d6e0" class="tr_rtitle">' +
            '<td style="text-align:left!important;padding-left:.1rem" colspan="2">名次</td>' +
            '<td style="text-align:left!important;padding-left:.2rem">昵称</td>' +
            '<td><span>胜</span></td>' +
            '<td><span>负</span></td>' +
            '<td>胜率</td>' +
            '<td>盈利</td>' +
            '<td>连胜</td>' +
            '</tr>';
        $('#jcTable table').append(html_rank);
        //提示正在加载
        $('#jcTable table').append('<tr id="jcRankListLoad" bgcolor="#fff">' +
                ' <td colspan="8" class="f-td">' +
                    ' <div  class="paged boxs" style="display:block;">' +
                        ' <div class="load_gif fs24 text-999">' +
                            ' <span><img src="/Public/Mobile/images/load.gif"></span>' +
                            ' <span>正在加载更多的数据...</span>' +
                        ' </div>' +
                    ' </div>' +
                ' </td>' +
            ' </tr>');

        $.ajax({
            type: 'post',
            data : data,
            url: "/Guess/profit.html",
            dataType: 'json',
            success: function (data) {


                if(data.status==1)
                {
                    if (data.is_login != null && data.is_login != false)
                    {
                        var ranking = data.myRank['ranking'] == '' || data.myRank['ranking'] == undefined ? '未上榜' : data.myRank['ranking'];
                        var pointCount = data.myRank['pointCount'] == '' || data.myRank['pointCount'] == undefined ? 0 : data.myRank['pointCount'];

                        var winrate = data.myRank['winrate'] == '' || data.myRank['winrate'] == undefined ? 0 : data.myRank['winrate'];
                        var winCount = data.myRank['win'] == '' || data.myRank['win'] == undefined ? '0' : data.myRank['win'];
                        var loseCount = data.myRank['transport'] == '' || data.myRank['transport'] == undefined ? '0' : data.myRank['transport'];


                        $('#jcTable table').append('<tr bgcolor="#fee4c9" id="rank_tr">' +
                            ' <td colspan="3">我的排名：<span>'+ranking+'</span></td>' +
                            ' <td width="10%" class="fs24"><em class="red">'+winCount+'</em></td>' +
                            ' <td width="10%" class="fs24"><em class="green">'+loseCount+'</em></td>' +
                            ' <td width="10%" class="fs24 red">'+winrate+'%</td>' +
                            ' <td width="8%">'+pointCount+'</td> ' +
                            ' <td width="8%">'+data.myRank['curr_victs']+'</td> ' +
                            '</tr>' );
                    }
                    var list = data.list;
                    if (list != null) {
                        //我的盈利榜排名

                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = v['today_gamble'] != 1 && data.m_quiz == 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['today_gamble'] !=1 ? '' : '<span class="fl"></span>';
                            //推荐场数
                            var gambleCount = Number(v['win'])+Number(v['transport']);

                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/2.html';
                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +
                                                ' <td class="rankImg">'+topThreeImg+' </td>' +
                                                ' <td class="headImg"><a href="'+urlHtml+'"><img  class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"/></a></td>' +
                                                ' <td class="q-tl flogImg">' +
                                                ' <p class="fs26 clearfix">' +
                                                ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                                +isQuizHtml+
                                                '</p>' +
                                                ' <p class="fs22 text-999">推荐'+gambleCount+'场</p>' +
                                                ' </td>' +
                                                ' <td class="fs24"><em class="red">'+v['win']+'</em></td>' +
                                                ' <td class="fs24"><em class="green">'+v['transport']+'</em></td>' +
                                                ' <td class="fs24 red">'+v['winrate']+'%</td>' +
                                                ' <td>'+v['pointCount']+'</td>' +
                                                '<td>'+v['curr_victs']+'</td>' +
                                                ' </tr>';
                            $('#jcTable table').append(html);
                            trHref();
                        });
                    }
                    else
                    {
                        $('#jcTable table').append('<tr bgcolor="#fff">' +
                            ' <td colspan="9" class="f-td">' +
                            '<div id="jcEmptyList" class="paged boxs" style="display:block;">' +
                            ' <div class="load_gif fs24 text-999">' +
                            ' <span>暂无数据</span>' +
                            ' </div>' +
                            ' </div>'+
                            ' </td>' +
                            ' </tr>');
                    }

                }
                else
                {


                }
            },
            complete:function () {

                //显示有推荐用户
                if (Cookie.getCookie('m_quiz') == 1) {
                    $(".gambleCheck").attr("checked",true);
                }
                $('#jcRankListLoad').css({'display':'none'});

                //头像懒加载
                lazyload();
            }
        });

    }
    function jcProfitListMore()
    {
        $('#jcRankListMore').css({'display':'block'});
        var page = $('#jcPage').val();//默认重第二页开始--竞彩
        page++;
        // var data = {
        //     gambleType : 1,
        //     dateType   : $("#jcRankNav ul .on a").data('ranktype'),
        //     page   : page,
        // };
        var data = {
            gambleType : gambleType,
            dateType   : dateType,
            page   : page,
        };
        $.ajax({
            type: 'post',
            async : false,
            data : data,
            url: "/Guess/profit.html",
            dataType: 'json',
            success: function (data) {
                if(data.status==1)
                {

                    var list = data.list;
                    if (list != null) {
                        $('jcEmptyList').css({'display':'none'});//当滚动新数据时，隐藏暂无数据的提示
                        // var m_quiz=Cookie.getCookie('m_quiz');
                        $.each(list, function (k, v) {

                            var modClass = k%2 == 0 ? 'f7' : 'ff';
                            //用于只显示有推荐的用户
                            var hiddenClass = v['today_gamble'] != 1 && data.m_quiz == 1 ? 'hidden' : '';

                            //是否有新推荐的标记
                            var isQuizHtml = v['today_gamble'] !=1 ? '' : '<span class="fl"></span>';
                            //推荐场数
                            var gambleCount = Number(v['win'])+Number(v['transport']);

                            //前三名的奖牌图片或名次
                            var topThreeImg =  v['ranking'];
                            switch (v['ranking'])
                            {
                                case '1': topThreeImg = '<img src="/Public/Mobile/images/rank01.png">'; break;
                                case '2': topThreeImg = '<img src="/Public/Mobile/images/rank02.png">'; break;
                                case '3': topThreeImg = '<img src="/Public/Mobile/images/rank03.png">'; break;
                            }
                            var urlHtml = '//'+DOMAIN+'/expUser/'+v['user_id']+'/2.html';
                            var html = '<tr class="clickHref js-list ios_touch '+modClass+' '+hiddenClass+' " data-id="'+v['user_id']+'" href="'+urlHtml+'">' +
                                ' <td class="rankImg">'+topThreeImg+' </td>' +
                                ' <td class="headImg"><a href="'+urlHtml+'"><img  class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"/></a></td>' +
                                ' <td class="q-tl flogImg">' +
                                ' <p class="fs26 clearfix">' +
                                ' <em class="fl q-one" class="fl">'+v["nick_name"]+'</em>'
                                +isQuizHtml+
                                '</p>' +
                                ' <p class="fs22 text-999">推荐'+gambleCount+'场</p>' +
                                ' </td>' +
                                ' <td class="fs24"><em class="red">'+v['win']+'</em></td>' +
                                ' <td class="fs24"><em class="green">'+v['transport']+'</em></td>' +
                                ' <td class="fs24 red">'+v['winrate']+'%</td>' +
                                ' <td>'+v['pointCount']+'</td>' +
                                '<td>'+v['curr_victs']+'</td>' +
                                ' </tr>';
                            $('#jcTable table').append(html);
                            trHref();
                        });
                    }
                    else
                    {
                        $('#jcRankListMore').css({'display':'none'});
                        $("#jcShowLess").show();
                    }
                }
                else
                {


                }
            },
            complete:function () {
                $('#jcRankListLoad').css({'display':'none'});
                $('#jcPage').val(page);

                //头像懒加载
                lazyload();
            }
        });

    }
    //滚动加载滚动---亚盘、竞彩
    $(window).scroll(function () {
        //$(window).scrollTop()这个方法是当前滚动条滚动的距离
        //$(window).height()获取当前窗体的高度
        //$(document).height()获取当前文档的高度
        var bot = 50; //bot是底部距离的高度
        //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
        if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
        {
            if ($('#yp').hasClass('on'))//亚盘
            {
                var rankTypeName = $('#rankType').val();
                if (rankTypeName == 'rank')//胜率
                {
                    if ($('#ypEmptyList').attr("style") != 'display:block;')
                        ypRankListMore();
                }
                else if (rankTypeName == 'profit')//盈利
                {
                    if ($('#ypEmptyList').attr("style") != 'display:block;')
                        ypProfitListMore();
                }
            }
            else if ($('#jc').hasClass('on'))
            {
                var jcRankTypeName = $('#jcRankType').val();
                if (jcRankTypeName == 'rank')//胜率
                {
                    if ($('#jcEmptyList').attr("style") != 'display:block;')
                    {
                        jcRankListMore();//加载更多
                    }

                }
                else if (jcRankTypeName == 'profit')//盈利
                {
                    if ($('#jcEmptyList').attr("style") != 'display:block;')
                    {
                        jcProfitListMore();//加载更多
                    }

                }

            }
        }
    });


});

function trHref()
{
    $('.clickHref').on('click',function(){
        var hrefUrl = $(this).attr('href');
        window.location.href=hrefUrl;
    });
}