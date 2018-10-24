/**
 * Created by Liangzk on 2017/1/12.
 * 足球比分--即时指数
 */
var ajaxData = [];
var domain = config.domain;
var CookieArray = ['1', '1', '1', '1', '1', '1', '1', '1', '1', '1', '0', '1','0']; //默认功能设置cookie
$(function () {

    //插入html
    insertHtml();
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
    $('#checkAll1').on('click',function () {
        $('#unionSelect li').addClass('on');
        $('#unionLevel input[class=userid]:checkbox').each(function () {

            $(this).attr("checked",true)
        });
        getHideCount();//赛事隐藏统计
    });
    $('#reverse1').on('click',function () {

        $('#unionLevel input[class=userid]:checkbox').each(function () {
            if (this.checked == true) {
                this.checked = false;
            } else {
                this.checked = true;
            }
        });
        getHideCount();//赛事隐藏统计
    });

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


        //联赛ID
        unionIdStr = unionIdStr.substring(0, unionIdStr.length-1);

        $('table.unionList').css('display','none');
        var unionIdArr = unionIdStr.split(',');
        Cookie.setCookie('IndUnionArr',unionIdStr);
        if (unionIdStr.length > 0)
        {
            $.each(unionIdArr,function(index,value){
                var unionId = value;
                $('table.unionList').each(function () {
                    if ($(this).data('unionid') == unionId)
                    {
                        $(this).css('display','');

                    }
                });

            });
        }
        else
        {
            //显示
            $.each(unionIdArr,function(index,value){
                var unionId = value;

                $('table.unionList').each(function () {
                    if ($(this).data('unionid') == unionId)
                    {
                        $(this).css('display','block');

                    }
                });

            });
        }




        //统计隐藏赛事
        // getHideCount();

        $('.box-list.dropdown-menu').css('display','none');



    });


    //赛事筛选点击关闭事件
    $('#removeAll').on('click',function () {

        var unionId = [];
        if(Cookie.getCookie('IndUnionArr') != null)
        {
            $('.navUnionList').find('input').prop('checked',false);
            unionId = Cookie.getCookie('IndUnionArr').split(',');

            $('.navUnionList').find('input').each(function (index, element) {
                var _this = $(this);
                if($.inArray(_this.attr('value'),unionId) > -1)
                {
                    $(this).attr('checked',true);
                }
            });
        }

        //统计隐藏赛事
        getHideCount();
    });


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


        var companyIdArr = companyIdStr.split(',');
        Cookie.setCookie('companyId',companyIdArr)
        $('tr.oddsList').css('display','none');
        $('tr.emptyList').remove();
        $.each(companyIdArr,function (k,v) {

            $('tr.oddsList').each(function () {
                if($(this).hasClass('.bcHtml'))
                {
                    $(this).remove();
                }
                if (v == $(this).data('companyid'))
                {
                    $(this).css('display','');
                }
            });
        });

        $('.gs-list').css('display','none');

        //插入表格
        addTableTr();

    });

    //公司筛选点击关闭时间
    $('#closeAll').on('click',function () {

        var unionId = [];
        var ruleId = Cookie.getCookie('companyId')?Cookie.getCookie('companyId').split(','):companyId;
        if(ruleId != null)
        {
            $('.companyList').find('input').prop('checked',false);

            $('.companyList').find('input').each(function (index, element) {
                var _this = $(this);
                if($.inArray(_this.attr('value'),ruleId) > -1)
                {
                    $(this).attr('checked',true);
                }
            });
        }

        $('.gs-list').css('display','none');
    });

    //语言切换事件
    $('#languageSle li a').on('click', function () {
        //0简体 1繁体 2英语
        var language = $(this).data('language');
        //切换
        $('.changeLang').each(function(){
            var langName = $(this).attr('langName').split(',');
            $(this).text(langName[language]);
        })
        var languageName = $(this).text();
        $('#languageContent').text(languageName);
        Cookie.setCookie('lang', language,5);
        setFbCookie(12,language);
    })

    //点查看事件
    $('#check').on('click',function () {
        searchText();
    });

    // $("#search_text").keyup(function () {
    //     var indicesCookie = Cookie.getCookie('indicesCookie');
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
    function searchText() {

        var searchText = $('#search_text').val();
        if (searchText.length > 0)
        {
            $('table.unionList:visible').each(function () {

                var tableThis = $(this);
                var sign = false;

                //匹配联赛
                var unionName = tableThis.find('.listUnionName').attr('langname');
                if (unionName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                {
                    sign = true;
                }
                //匹配主队
                var homeName = tableThis.find('.listHomeName').attr('langname');
                if(sign){
                    if (homeName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                    {
                        sign = true;
                    }else{
                        sign = false;
                    }
                }
                //匹配客队
                var awayName = tableThis.find('.listAwayName').attr('langname');
                if(sign){
                    if (awayName.toLowerCase().indexOf(searchText.toLowerCase()) == -1)
                    {
                        sign = true;
                    }else{
                        sign = false;
                    }
                }
                if(sign)
                {
                    tableThis.css('display','none');
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
            $('.unionList').css('display','');
        }


    }



    //5秒请求一次
    // var begin = setInterval(function () {
    //     getChangeChodds(5000);
    //     clearInterval(begin);
    // }, 5000);


});



function addTableTr()
{
    var html = '<tr class="emptyList hover_bg oddsShow bcHtml"> <td  width="50" class="text-999"> --</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td>' +
        ' <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td>' +
        ' <td>--</td> <td>--</td></tr> ';
    $('table.unionList').each(function () {
        var tableThis = this;
        var count = $(tableThis).find('.oddsList:visible').size();
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

var companyId = ['1','3','8'];

function insertHtml()
{
    $.ajax({
        type: "get",
        url: '/getIndices.html',
        cache: false,
        dataType : 'json',
        success: function (data) {
            if(data.status == 1){
                var union = data.data.union;
                unionHtml(union);
                $('.navUnionList li').on('click',function(){
                    getHideCount();
                });
                var company = data.data.company;
                companyHtml(company);
                var game = data.data.info;
                gameHtml(game);
            }
            //插入表格
            addTableTr();
            $(".loading_gif").hide();
        }
    });
}


//處理賽事列表
function gameHtml(data)
{
    var unionId = new Array();
    if(Cookie.getCookie('IndUnionArr') != null)
    {
        unionId = Cookie.getCookie('IndUnionArr').split(',');
    }
    $.each(data,function(k,v){
        var _css = '';
        if($.inArray(v[1],unionId) == -1 && unionId.length > 0)
        {
            _css = 'style="display:none;"';
        }
        var union_name = langSwitch(v[2][0],v[2][1],v[2][2]);//聯賽名
        var home_name = langSwitch(v[4][0],v[4][1],v[4][2]);//主隊名
        var away_name = langSwitch(v[5][0],v[5][1],v[5][2]);//客隊名
        var tuijian = v['tuijian'] == 1 ?'<a href="/gambleDetails/game_id/'+v[0]+'/sign/1.html" target="_blank" style="margin-right: 0">猜</a>':'';
        var html = '<table '+_css+' data-unionId="'+v[1]+'" data-gameId="'+v[0]+'" class="table table-bordered tb_nindex unionList">'+
        '<tbody>'+
        '<tr>'+
        '<th width="240" class="th_first" rowspan="3">'+
            '<div class="th_left cb">'+
            '<label style="color: '+v[3]+';" class="inline myLabel01" for="check">'+
            '<span class="changeLang listUnionName" langName="'+v[2][0]+','+v[2][1]+','+v[2][2]+'">'+union_name+'</span>'+
        '</label>'+
        '</div>'+
        '<div class="th_right">'+
            '<p>'+v[6]+'</p>'+
        '</div>'+
        '</th>'+
        '<th width="60" class="th_second" rowspan="3">公司</th>'+
            '<th width="300" class="pType" colspan="6">亚盘</th>'+
            '<th width="300" class="pType" colspan="6">欧赔</th>'+
            '<th width="300" class="pType" colspan="6">大小</th>'+
            '</tr>'+
            '<tr>'+
            '<td width="150" colspan="3">初盘</td>'+
            '<td class="bgLg" width="150" colspan="3">即时</td>'+
            '<td width="150" colspan="3">初盘</td>'+
            '<td class="bgLg" width="150" colspan="3">即时</td>'+
            '<td width="150" colspan="3">初盘</td>'+
            '<td class="bgLg" width="150" colspan="3">即时</td>'+
            '</tr>'+
            '<tr>'+
            '<td>主队</td> <td>让球</td> <td>客队</td> <td class="bgLg">主队</td> <td class="bgLg">让球</td> <td class="bgLg">客队</td> <td>主胜</td> <td>和局</td> <td>客胜</td> <td class="bgLg">主胜</td> <td class="bgLg">和局</td> <td class="bgLg">客胜</td> <td>大球</td> <td>盘口</td> <td>小球</td> <td class="bgLg">大球</td> <td class="bgLg">盘口</td> <td class="bgLg">小球</td>'+
            '</tr>'+
            '<tr class="hover_bg">'+
            '<td rowspan="4" class="bgLg">'+
            '<div class="team_content clearfix">'+
            '<p><em><em class="changeLang listHomeName" langName="'+v[4][0]+','+v[4][1]+','+v[4][2]+'">'+home_name+'</em></em></p>'+
        '<p class="team_vs"><em>VS</em><span></span></p>'+
            '<p><em><em class="changeLang listAwayName" langName="'+v[5][0]+','+v[5][1]+','+v[5][2]+'">'+away_name+'</em></em></p>'+
        '<div class="pk_msg">'+
            '<a href="/ypOdds/game_id/'+v[0]+'/sign/1.html" target="_blank">亚</a>'+
            '<a href="/ypOdds/game_id/'+v[0]+'/sign/2.html" target="_blank">大</a>'+
            '<a href="/eur_index/game_id/'+v[0]+'/sign/1.html" target="_blank">欧</a>'+
            '<a href="/dataFenxi/game_id/'+v[0]+'/sign/1.html" target="_blank">析</a>'+tuijian+
        '</div>'+
        '</div>'+
        '</td>'+
        '</tr>'+
        getPeiInfo(v)+
        '</tbody>'+
        '</table>';
        $('.main').append(html);
    });
}

//處理聯賽html
function unionHtml(data){
    var unionId = [];
    if(Cookie.getCookie('IndUnionArr') != null)
    {
        unionId = Cookie.getCookie('IndUnionArr').split(',');
    }
    for(var i=0;i<data.length;i++){
        var union_name = langSwitch(data[i]['union_name'][0],data[i]['union_name'][1],data[i]['union_name'][2]);//聯賽名
        var _css = 'checked';
        if($.inArray(data[i]['union_id'].toString(),unionId) == -1 && unionId.length>0)
        {
            _css = '';
        }
        var html = '<li style="border-color: '+data[i]['union_color']+';" data-level="'+data[i]['level']+'"><label class="inline"><input total="'+data[i]['total']+'" '+_css+' type="checkbox" class="userid" name="'+data[i]['union_name'][0]+'" value="'+data[i]['union_id']+'"><span class="changeLang" langName="'+data[i]['union_name'][0]+','+data[i]['union_name'][1]+','+data[i]['union_name'][2]+'">'+union_name+'</span><em class="gameCount">['+data[i]['total']+']</em></label></li>';
        $('.navUnionList').append(html);
    }
}

//處理公司html
function companyHtml(data){
    var ruleId = Cookie.getCookie('companyId')?Cookie.getCookie('companyId').split(','):companyId;
    $.each(data,function(k,v){
        var is_check = '';
        if($.inArray(k, ruleId) >-1)
        {
            is_check = 'checked';
        }
        var html = '<li>'+
            '<label class="inline">'+
        '<input '+is_check+' type="checkbox" class="userid" name="'+v+'"  value="'+k+'"><span class="bg-ac">'+v+'</span>'+
        '</label>'+
        '</li>';
        $('.companyList').append(html);
    })

}

//處理賠率的html
function getPeiInfo(v){
    var bcHtml = '<tr class="emptyList hover_bg oddsShow"> <td  width="50" class="text-999"> --</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td>' +
        ' <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td> <td>--</td>' +
        ' <td>--</td> <td>--</td></tr> ';
    var info = v.info;
    var html = '';
    for(var i=0;i<info.length;i++){
        var ruleId = Cookie.getCookie('companyId')?Cookie.getCookie('companyId').split(','):companyId;
        var is_none = 'style="display:none;"';
        if($.inArray(info[i][0].toString(), ruleId) >-1)
        {
            is_none = '';
        }
        html += '<tr '+is_none+' class="hover_bg oddsList  oddsShow" data-companyid="'+info[i][0]+'">' +
            '<td class="td_second companyIdClass" data-companyid="1" data-gameid="1559551">'+info[i]['name']+'</td>' +
            '<td class="text-999 plate one">'+info[i][1]+'</td>' +
            '<td class="text-999 plate two">'+info[i][2]+'</td>' +
            '<td class="text-999 plate three">'+info[i][3]+'</td>' +
            '<td class="bgLg plate changeOne '+info[i]['y_h_c']+'">'+info[i][4]+'</td>' +
            '<td class="bgLg plate changeTwo '+info[i]['y_p_c']+'">'+info[i][5]+'</td>' +
            '<td class="bgLg plate changeThree '+info[i]['y_a_c']+'">'+info[i][6]+'</td>' +
            '<td class="text-999 size one">'+info[i][7]+'</td>' +
            '<td class="text-999 size two">'+info[i][8]+'</td>' +
            '<td class="text-999 size three">'+info[i][9]+'</td>' +
            '<td class="bgLg size changeOne '+info[i]['d_h_c']+'">'+info[i][10]+'</td>' +
            '<td class="bgLg size changeTwo '+info[i]['d_p_c']+'">'+info[i][11]+'</td>' +
            '<td class="bgLg size changeThree '+info[i]['d_a_c']+'">'+info[i][12]+'</td>' +
            '<td class="text-999 compensate one">'+info[i][13]+'</td>' +
            '<td class="text-999 compensate two">'+info[i][14]+'</td>' +
            '<td class="text-999 compensate three">'+info[i][15]+'</td>' +
            '<td class="bgLg compensate changeOne '+info[i]['o_h_c']+'">'+info[i][16]+'</td>' +
            '<td class="bgLg compensate changeTwo '+info[i]['o_p_c']+'">'+info[i][17]+'</td>' +
            '<td class="bgLg compensate changeThree '+info[i]['o_a_c']+'">'+info[i][18]+'</td>' +
            '</tr>';
    }
    return html;
}


//切換 語言
function langSwitch(lang1,lang2,lang3)
{
    var arr = new Array();
    arr[0] = lang1;
    arr[1] = lang2;
    arr[2] = lang3;
    var type = getFbCookie(12)?getFbCookie(12):0;
    return arr[type];
}

//设置对应cookie数值
function setFbCookie(serial, status) {
    //是否存在cookie
    var fbCookie = Cookie.getCookie('fbCookie');
    var array = fbCookie ? fbCookie.split('^') : CookieArray;
    //改变对应数值
    array[serial] = status;
    Cookie.setCookie('fbCookie', array.join('^'), 7);
}
//获取对应cookie数值
function getFbCookie(serial) {
    if (serial == '' || serial == undefined)
        serial = 'all';
    var fbCookie = Cookie.getCookie('fbCookie');
    var array = fbCookie ? fbCookie.split('^') : CookieArray;
    if (serial == 'all') return array;
    //返回对应数值
    return array[serial];
}

//赛事隐藏统计
function getHideCount()
{
    var hideCount = 0;
    $('#unionCheck input[class=userid]:checkbox').each(function (index, element) {

        // console.log($(this).sibling().find('.gameCount').html());
        if (!$(this).attr('checked'))
        {
            hideCount = hideCount + parseInt($(this).attr('total'));
        }
    });
    $('#hideCount').html(hideCount);
}

//接收数据处理
MqInit.onMessage(function (topic, message) {
    var data = message;
    changeOdds(data);
}, ['qqty/api501/fb/index/#']);

//改變頁面公司賠率
function changeOdds(data){
    var temp = JSON.parse(data)['data'];
    var game_id = temp['g_id'];
    var c_id = temp['c_id'];
    var type = temp['o_t'];
    var odds = temp['odds'];
    var chomeEq = cpanEq = cawayEq = jhomeEq = jpanEq = jawayEq = 0;
    switch(type){
        case 'a':
            chomeEq = 1;
            cpanEq = 2;
            cawayEq = 3;
            jhomeEq = 4;
            jpanEq = 5;
            jawayEq = 6;
            break;
        case 'o':
            chomeEq = 7;
            cpanEq = 8;
            cawayEq = 9;
            jhomeEq = 10;
            jpanEq = 11;
            jawayEq = 12;
            break;
        case 'd':
            chomeEq = 13;
            cpanEq = 14;
            cawayEq = 15;
            jhomeEq = 16;
            jpanEq = 17;
            jawayEq = 18;
            break;
        default:
            return true;
    }
    var oddData = $('.main table[data-gameid='+game_id+']').find('tr[data-companyid='+c_id+']');
    if(oddData.html() == undefined){
        return true;
    }
    //獲取初盤
    var chuHome = oddData.find('td').eq(chomeEq).html();
    if(chuHome == '--'){
        return true;
    }
    chuHome = parseFloat(chuHome);
    var chuPan = oddData.find('td').eq(cpanEq).html();
    var chuAway = parseFloat(oddData.find('td').eq(cawayEq).html());
    //獲取推送數據
    var nowHome = parseFloat(odds[1]);
    var nowPan = odds[0];
    var nowAway = parseFloat(odds[2]);
    // console.log(game_id,chuHome,chuPan,chuAway,nowHome,nowPan,nowAway);

    //修改主客隊數據
    oddData.find('td').eq(jhomeEq).removeClass('text-green text-red');
    oddData.find('td').eq(jhomeEq).html(nowHome);
    if(chuHome < nowHome){
        oddData.find('td').eq(jhomeEq).addClass('text-red').addClass('odds_red_bg').delay(10000).queue(function () {
            $(this).removeClass('odds_red_bg');
            $(this).dequeue();
        });
    }else if(chuHome > nowHome){
        oddData.find('td').eq(jhomeEq).addClass('text-green').addClass('odds_green_bg').delay(10000).queue(function () {
            $(this).removeClass('odds_green_bg');
            $(this).dequeue();
        });
    }
    oddData.find('td').eq(jawayEq).removeClass('text-green text-red');
    oddData.find('td').eq(jawayEq).html(nowAway);
    if(chuAway < nowAway){
        oddData.find('td').eq(jawayEq).addClass('text-red').addClass('odds_red_bg').delay(10000).queue(function () {
            $(this).removeClass('odds_red_bg');
            $(this).dequeue();
        });
    }else if(chuAway > nowAway){
        oddData.find('td').eq(jawayEq).addClass('text-green').addClass('odds_green_bg').delay(10000).queue(function () {
            $(this).removeClass('odds_green_bg');
            $(this).dequeue();
        });
    }

    //處理盤口位置數據
    if(type == 'o'){
        oddData.find('td').eq(jpanEq).removeClass('text-green text-red');
        oddData.find('td').eq(jpanEq).html(nowPan);
        if(chuPan < nowPan){
            oddData.find('td').eq(jpanEq).addClass('text-red').addClass('odds_red_bg').delay(10000).queue(function () {
                $(this).removeClass('odds_red_bg');
                $(this).dequeue();
            });
        }else if(chuPan > nowPan){
            oddData.find('td').eq(jpanEq).addClass('text-green').addClass('odds_green_bg').delay(10000).queue(function () {
                $(this).removeClass('odds_green_bg');
                $(this).dequeue();
            });
        }
    }else{
        var preTag = '';
        var score = nowPan;
        if (nowPan.indexOf('-') >= 0) {
            preTag = "-";
            score = nowPan.split('-')[1];
        }
        var panDaa = compScore[score] == undefined?score:compScore[score];
        panData =  preTag + panDaa;
        oddData.find('td').eq(jpanEq).html(panData);
    }

}