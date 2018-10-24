/**
 * Created by Liangzk on 2017/1/12.
 * 足球比分--即时指数
 */

$(function () {

    //语言切换
    $('.odds-language .dropdown-menu li').click(function(e) {
        var languageName = $(this).children('a').text();
        $('.odds-language span').html(languageName);
    });

    //赛事选择
    $('.event').click(function(e) {
        $('.box-list').fadeIn(300);
    });

    //公司选择
    $('.gongSi').click(function(e) {
        $('.gs-list').fadeIn(300);
        $('.box-list').fadeOut(300);
    });

    $('#removeAll').click(function(e) {
        $('.box-list').fadeOut(300);
    });


    //赛事隐藏统计
    function getHideCount()
    {
        var hideCount = 0;
        $('#unionCheck input[class=userid]:checkbox').each(function (index, element) {

            // console.log($(this).sibling().find('.gameCount').html());
            if (!$(this).attr('checked'))
            {
                hideCount++;
            }
        });
        $('#hideCount').html(hideCount);
    }


    //赛事级别选择
    $('#unionSelect li a').on('click',function () {
        var level = $(this).eq(0).attr("data-unionLevel");
        if ($(this).parent().hasClass('on'))
        {
            $(this).parent().removeClass('on');

            $('#unionLevel li').each(function () {

                if (level == $(this).data('level'))
                {
                    $(this).find('input[class=userid]:checkbox').each(function () {

                        $(this).attr("checked",false);
                    })
                }

            });

        }
        else
        {
            $(this).parent().addClass('on');
            $('#unionLevel li').each(function () {

                if (level == $(this).data('level'))
                {
                    $(this).find('input[class=userid]:checkbox').each(function () {

                        $(this).attr("checked",true);
                    })
                }

            });
        }


        getHideCount();//赛事隐藏统计

    });



    getHideCount();//赛事隐藏统计



    //级别一的全选、不全选事件
    $("#checkAll").on('click', function () {
        $(".rank-ul li").addClass('on');
        $(".userid").each(function () {
            this.checked = true;
        });
        dynamic();
    });
    $("#reverse").on('click', function () {
        $(".userid").each(function() {
            if (this.checked == true) {
                this.checked = false;
            } else {
                this.checked = true;
            }
        });
        $(".rank-ul li").each(function () {
            var _li = $(this);
            if (_li.hasClass('on')) {
                _li.removeClass('on');
            } else {
                _li.addClass('on')
            }
        });
        dynamic();
    });
    /*赛事选择js效果*/
    $('.rank-ul li').on('click', function () {
        var num = $('.rank-ul li').index(this) + 1;

        if ($(this).hasClass('on')) {
            if (num == 1) {
                menu_display(0, false);
                menu_display(1, false);
            } else {
                menu_display(num, false);
            }
            $(this).removeClass('on');
        } else {
            if (num == 1) {
                menu_display(0, true);
                menu_display(1, true);
            } else {
                menu_display(num, true);
            }
            $(this).addClass('on');
        }
        dynamic();
        // bgcolor();
    });
    //赛事选择复选框
    function menu_display($num, $che) {
        $(".match-team li").each(function () {
            if ($(this).attr('match_level') == $num) {
                $(this).children('.inline').children('.userid').attr("checked", $che);
            }
        });
    }
    //赛事选择动态变化隐藏数量
    function dynamic() {
        var num = 0;
        $('#menu_list input[class=userid]:checkbox').each(function () {
            var str = '';
            if (!$(this).attr('checked')) {
                str = $(this).parent().children("em").html();
                str = str.substring(0, str.length - 1);
                str = str.substr(1);
                num = num + Number(str);
            }
        });
        $(".menu_count").html(num);
        return num;
    }

    //赛事单项选择
    $('#unionCheck input[class=userid]:checkbox').on('click',function () {
        getHideCount();//赛事隐藏统计
    });

    //赛事筛选确定事件
    $('#ensure').on('click',function () {

        var unionIdStr = '';
        var unionCount = 0;
        $('#unionCheck input[class=userid]:checkbox').each(function (index, element) {
            if ($(this).attr('checked'))
            {
                unionIdStr+=$(element).val()+',';
                unionCount++;
            }

        });
        if (unionCount < 1)
        {
            showMsg('请选择联赛！',0,'error');
            return;
        }

        var searchGameId = '';
        var searchTextStr = $('#search_text').val();
        if (searchTextStr.length > 0)
        {
            var temp = ''
            searchGameId = searchText(temp);
        }


        // //联赛ID
        unionIdStr = unionIdStr.substring(0, unionIdStr.length-1);

        //注意：把多个类型的值，存到同一个Cookie变量里  联赛ID---前缀‘!’，后缀‘@’
        var indicesCookie = Cookie.getCookie('bkindicesCookie');
        if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
        {
            var endCookie = indicesCookie.split('@')[1];
            var startCookie = indicesCookie.split('!')[0];
            if (endCookie != null && endCookie != '' && endCookie != undefined && startCookie!= undefined && startCookie != null && startCookie != '')
            {
                Cookie.setCookie('bkindicesCookie',startCookie+'!'+unionIdStr+'@'+endCookie);
            }
            else if (startCookie!= undefined && startCookie != null && startCookie != '')
            {
                Cookie.setCookie('bkindicesCookie',startCookie+'!'+unionIdStr+'@');
            }
            else
            {
                Cookie.setCookie('bkindicesCookie','!'+unionIdStr+'@'+endCookie);
            }
        }
        else
        {
            Cookie.setCookie('bkindicesCookie','!'+unionIdStr+'@');
        }

        $('table.unionList').css('display','none');
        var unionIdArr = unionIdStr.split(',');

        if (searchTextStr.length > 0)
        {
            if (searchGameId != '' && searchGameId != null && searchGameId != undefined)
            {
                var gameIdArr = searchGameId.split(',');
                $.each(unionIdArr,function(index,value){
                    var unionId = value;
                    $('table.unionList').each(function () {
                        if ($(this).data('union_id') == unionId && $.inArray(String($(this).data('gameid')), gameIdArr) != -1)
                        {
                            $(this).css('display','block');

                        }
                    });

                });
                //返回 3,
            }
        }
        else
        {
            //显示
            $.each(unionIdArr,function(index,value){
                var unionId = value;

                $('table.unionList').each(function () {
                    if ($(this).attr('union_id') == unionId)
                    {
                        $(this).css('display','block');

                    }
                });

            });
        }




        //统计隐藏赛事
        getHideCount();

        $('.box-list.dropdown-menu').css('display','none');



    });


    //赛事筛选点击关闭事件
    $('#removeAll').on('click',function () {

        var indicesCookie = Cookie.getCookie('bkindicesCookie');
        var unionIdStr = '';
        if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
        {
            //根据拼接前缀、后缀获取
            var unionIdTemp = indicesCookie.split('!')[1];
            if (unionIdTemp != null && unionIdTemp != '' && unionIdTemp != undefined)
            {
                unionIdStr = unionIdTemp.split('@')[0];
            }
        }

        if (unionIdStr != null && unionIdStr != '' && unionIdStr != undefined)
        {
            unionIdArr = unionIdStr.split(',');
            //去掉勾选赛事
            $('#unionCheck input[class=userid]:checkbox').each(function (unionK,unionV) {
                $(unionV).attr("checked",false);
            });
            //勾选赛事
            $.each(unionIdArr,function(index,value){
                var unionId = value;
                $('#unionCheck input[class=userid]:checkbox').each(function (unionK,unionV) {

                    var checkUnionId = $(unionV).val();
                    if (checkUnionId == unionId)
                    {
                        $(unionV).attr("checked",true);
                    }

                });

            });
        }
        else
        {
            //去掉勾选赛事
            $('#unionCheck input[class=userid]:checkbox').each(function (unionK,unionV) {
                $(unionV).attr("checked",true);
            });
        }


        //统计隐藏赛事
        getHideCount();
    });

    //隐藏赛事
    // function gameHide()
    // {
    //     var indicesCookie = Cookie.getCookie('bkindicesCookie');
    //     var unionIdStr = '';
    //     if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
    //     {
    //         //根据拼接前缀、后缀获取
    //         var unionIdTemp = indicesCookie.split('!')[1];
    //         if (unionIdTemp != null && unionIdTemp != '' && unionIdTemp != undefined)
    //         {
    //             unionIdStr = unionIdTemp.split('@')[0];
    //         }
    //
    //     }
    //
    //     if (unionIdStr != null && unionIdStr != '' && unionIdStr != undefined)
    //     {
    //
    //         unionIdArr = unionIdStr.split(',');
    //
    //         //去掉勾选赛事
    //         $('#unionCheck input[class=userid]:checkbox').each(function (unionK,unionV) {
    //             $(unionV).attr("checked",false);
    //         });
    //
    //
    //         //显示
    //         $('table.unionList').css('display','none');
    //         $.each(unionIdArr,function(index,value){
    //             var unionId = value;
    //
    //             $('table.unionList').each(function () {
    //                 if ($(this).attr('union_id') == unionId )
    //                 {
    //                     $(this).css('display','block');
    //                 }
    //             });
    //
    //             $('#unionCheck input[class=userid]:checkbox').each(function (unionK,unionV) {
    //
    //                 var checkUnionId = $(unionV).val();
    //                 if (checkUnionId == unionId)
    //                 {
    //                     $(unionV).attr("checked",true);
    //                 }
    //
    //             });
    //
    //         });
    //
    //     }
    //
    // }

    //公司筛选
    $('#companySure').on('click',function () {
        var companyIdStr = '';
        var companyCount = 0;
        $('#companySelect ul input[class=userid]:checkbox').each(function (index, element) {
            if ($(this).attr('checked'))
            {
                companyCount++;
                companyIdStr+=$(element).val()+',';
            }
        });
        if (companyCount != 3)
        {
            showMsg('请同时选择三家公司！',0,'error');
            return false;
        }


        companyIdStr = companyIdStr.substring(0, companyIdStr.length-1);
        //注意：把多个类型的值，存到同一个Cookie变量里  联赛ID---前缀‘!’，后缀‘@’ 公司ID---前缀‘#’，后缀‘$’
        var indicesCookie = Cookie.getCookie('bkindicesCookie');
        if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
        {
            var endCookie = indicesCookie.split('$')[1];
            var startCookie = indicesCookie.split('#')[0];
            if (endCookie != null && endCookie != '' && endCookie != undefined && startCookie!= undefined && startCookie != null && startCookie != '')
            {
                Cookie.setCookie('bkindicesCookie',startCookie+'#'+companyIdStr+'$'+endCookie);
            }
            else if (startCookie!= undefined && startCookie != null && startCookie != '')
            {
                Cookie.setCookie('bkindicesCookie',startCookie+'#'+companyIdStr+'$');
            }
            else
            {
                Cookie.setCookie('bkindicesCookie','#'+companyIdStr+'$'+endCookie);
            }

        }
        else
        {
            Cookie.setCookie('bkindicesCookie','#'+companyIdStr+'$');
        }


        var companyIdArr = companyIdStr.split(',');
        $('tr.oddsList').addClass('hidden').removeClass('oddsShow');
        $('tr.emptyList').remove();
        $.each(companyIdArr,function (k,v) {

            $('tr.oddsList').each(function () {
                if (v == $(this).data('companyid'))
                {
                    $(this).removeClass('hidden').addClass('oddsShow');
                }
            });
        });

        $('.gs-list').css('display','none');

        //插入表格
        addTableTr();

    });

    //公司筛选点击关闭时间
    $('#closeAll').on('click',function () {

        $('#companySelect ul input[class=userid]:checkbox').each(function (index, element) {
            $(this).attr('checked',false);
        });

        var indicesCookie = Cookie.getCookie('bkindicesCookie');
        var companyIdStr = '';
        if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
        {
            //根据拼接前缀、后缀获取
            companyIdStr = indicesCookie.split('#')[1].split('$')[0];
        }
        if(companyIdStr != null && companyIdStr != '' && companyIdStr != undefined)
        {
            var companyIdArr = companyIdStr.split(',');
            $.each(companyIdArr,function (k,v) {
                $('#companySelect ul input[class=userid]:checkbox').each(function (index, element) {
                    if ($(element).val() == v)
                    {
                        $(this).attr('checked',true);
                    }
                });
            });

        }
        else
        {
            $('#companySelect ul input[class=userid]:checkbox').each(function (index, element) {

                //筛选ID 默认：“澳门”、“SB”、“BET365"
                if ($(element).val() == '澳门' || $(element).val() == '易胜博' || $(element).val() == 'SB')
                {
                    $(this).attr('checked',true);
                }

            });
        }

        $('.gs-list').css('display','none');
    });

    //语言切换事件
    $('#languageSle li a').on('click',function () {

        //1:简体 2：繁体 3：英语
        var language = $(this).data('language');
        $('#languageContent').attr('language',language);
        switch(language)
        {
            case '1':$('#languageContent').html('简体');break;
            case '2':$('#languageContent').html('繁体');break;
            case '3':$('#languageContent').html('EN');break;
        }
        Cookie.setCookie('indicesLanguageSle', language);
        //页面语言显示改变
        languageChange();

    });

    //页面语言显示改变
    function languageChange()
    {
        var language = Cookie.getCookie('indicesLanguageSle');
        $('.language').addClass('hidden');
        switch (language)
        {
            case '2':
                $('#languageContent').html('繁体');
                $('.language.traditional').removeClass('hidden');
                break;
            case '3':
                $('#languageContent').html('EN');
                $('.language.english').removeClass('hidden');
                break;
            default:
                $('#languageContent').html('简体');
                $('.language.simplified').removeClass('hidden');
        }

        $('#languageContent').attr('language',language ? language : 1);
    }

    //点查看事件
    $('#check').on('click',function () {
        var indicesCookie = Cookie.getCookie('bkindicesCookie');
        var unionIdStr = '';
        if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
        {
            //根据拼接前缀、后缀获取
            var unionIdTemp = indicesCookie.split('!')[1];
            if (unionIdTemp != null && unionIdTemp != '' && unionIdTemp != undefined)
            {
                unionIdStr = unionIdTemp.split('@')[0];
            }
        }

        searchText(unionIdStr);
    });

    // $("#search_text").keyup(function () {
    //     var indicesCookie = Cookie.getCookie('bkindicesCookie');
    //     var unionIdStr = '';
    //     if (indicesCookie != null && indicesCookie != '' && indicesCookie != undefined)
    //     {
    //         //根据拼接前缀、后缀获取
    //         unionIdStr = indicesCookie.split('!')[1].split('@')[0];
    //     }
    //
    //     searchText(unionIdStr);
    // });

    //赛事名称、主队、客队名称查询
    function searchText(unionIdStr) {

        var searchText = $('#search_text').val();
        if (unionIdStr != null && unionIdStr != '' && unionIdStr != undefined)
        {
            $('table.unionList').css('display','none');

            unionIdArr = unionIdStr.split(',');
            //显示
            $.each(unionIdArr,function(index,value){
                var unionId = value;

                $('table.unionList').each(function () {

                    if ($(this).attr('union_id') == unionId )
                    {
                        $(this).css('display','block');
                    }
                });

            });

        }
        else
        {
            //显示所有
            $('table.unionList').css('display','block');
        }

        if (searchText.length > 0)
        {
            $('table.unionList').each(function () {

                var tableThis = this;
                var display = $(tableThis).css('display');
                if (display == 'none')
                {
                    return true;
                }

                var sign = false;

                //匹配简体的
                $(tableThis).find('.simplified.unionLanguage').each(function () {

                    var unionName = $(this).html();
                    if (unionName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                    {
                        $(tableThis).css('display','none');
                    }
                    else
                    {
                        $(tableThis).css('display','block');
                        sign = true;
                    }
                });
                if (!sign)
                {
                    //匹配繁体
                    $(tableThis).find('.traditional.unionLanguage').each(function () {

                        var unionName = $(this).html();
                        if (unionName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                        {
                            $(tableThis).css('display','none');
                        }
                        else
                        {
                            $(tableThis).css('display','block');
                            sign = true;
                        }
                    });
                }

                if (!sign)
                {
                    //匹配英语的
                    $(tableThis).find('.english.unionLanguage').each(function () {

                        var unionName = $(this).html();
                        if (unionName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                        {
                            $(tableThis).css('display','none');
                        }
                        else
                        {
                            $(tableThis).css('display','block');
                            sign = true;
                        }
                    });
                }

                if (sign)
                    return true;

                //匹配主队简体的
                $(tableThis).find('.simplified.homeLanguage').each(function () {

                    var homeName = $(this).html();
                    if (homeName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                    {
                        $(tableThis).css('display','none');
                    }
                    else
                    {
                        $(tableThis).css('display','block');
                        sign = true;
                    }
                });
                if (!sign)
                {
                    //匹配主队繁体
                    $(tableThis).find('.traditional.homeLanguage').each(function () {

                        var homeName = $(this).html();
                        if (homeName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                        {
                            $(tableThis).css('display','none');
                        }
                        else
                        {
                            $(tableThis).css('display','block');
                            sign = true;
                        }
                    });
                }

                if (!sign)
                {
                    //匹配主队英语
                    $(tableThis).find('.english.homeLanguage').each(function () {

                        var homeName = $(this).html();
                        if (homeName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                        {
                            $(tableThis).css('display','none');
                        }
                        else
                        {
                            $(tableThis).css('display','block');
                            sign = true;
                        }
                    });
                }

                if (sign)
                    return true;

                //匹配客队简体
                $(tableThis).find('.simplified.awayLanguage').each(function () {

                    var awayName = $(this).html();

                    if (awayName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                    {
                        $(tableThis).css('display','none');
                    }
                    else
                    {
                        $(tableThis).css('display','block');
                        sign = true;
                    }
                });
                if (!sign)
                {
                    //匹配客队繁体
                    $(tableThis).find('.traditional.awayLanguage').each(function () {

                        var awayName = $(this).html();

                        if (awayName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                        {
                            $(tableThis).css('display','none');
                        }
                        else
                        {
                            $(tableThis).css('display','block');
                            sign = true;
                        }
                    });
                }
                if (!sign)
                {
                    //匹配客队英语
                    $(tableThis).find('.english.awayLanguage').each(function () {

                        var awayName = $(this).html();

                        if (awayName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                        {
                            $(tableThis).css('display','none');
                        }
                        else
                        {
                            $(tableThis).css('display','block');
                            sign = true;
                        }
                    });
                }

            });

            var searchGameIdStr = '';
            $('table.unionList').each(function () {
                var display = $(this).css('display');
                if (display != 'none')
                {
                    searchGameIdStr +=$(this).data('gameid')+',';
                }
            });
            searchGameIdStr = searchGameIdStr.substring(0, searchGameIdStr.length-1);

            return searchGameIdStr;
        }
        else
        {
            return '';
        }


    }

    // gameHide();//隐藏赛事


    //插入表格
    addTableTr();

});



function addTableTr()
{
    var html = '<tr class="emptyList hover_bg oddsShow"> <td  width="50" class="text-999"> --</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td>' +
        ' <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td>' +
        ' <td>--</td> <td>--</td></tr> ';
    $('table.unionList').each(function () {
        var tableThis = this;
        var count = $(tableThis).find('.oddsList.oddsShow').size();
        if(count > 2)
        {
            return true;
        }

        switch (count)
        {
            case 0:
                $(tableThis).append(html);
                $(tableThis).append(html);
                $(tableThis).append(html);
                break;
            case 1:
                $(tableThis).append(html);
                $(tableThis).append(html);
                break;
            case 2:
                $(tableThis).append(html);
                break;
        }

    });
}
function changeOdssNum(odds)
{
    if (odds == '' || odds == undefined || odds == '--')
    {
        return odds;
    }
    var oddsNew = odds;
    if (odds.indexOf('.25') >= 0)
    {
        var odds_arr = odds.split(".");
        odds_arr[1] = odds_arr[0] + 0.5;

        oddsNew = odds_arr[0]+'/'+odds_arr[1];

    }
    else if(odds.indexOf('.75') >=0)
    {
        var odds_arr = odds.split(".");
        odds_arr[1] = odds_arr[0]+1;
        odds_arr[0] = odds_arr[0]+0.5;
        oddsNew = odds_arr[0]+'/'+odds_arr[1];
    }

    return oddsNew;
}