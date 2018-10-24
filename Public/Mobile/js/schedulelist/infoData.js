$(function () {
    var level = $("input[name=level]").val();
    var name = $("input[name=name]").val();
    var page_num = 1;
    getData();
    var all_data;

    function getData(page , is_plus) {
        page = page ? page : 1;
        is_plus = is_plus ? is_plus : false;
        $.ajax({
            url: '/Schedulelist/getInfo',
            type: 'get',
            async: false,
            data: {
                "type": name,
                "time_stamp" : time_stamp,
                "page_num" : page
            },
            timeout: 5000,
            dataType: 'json',
            beforeSend: function (xhr) {
            },
            success: function (data, textStatus, jqXHR) {
                console.log(data);
                if (data == null || data.data.length < 20) {
                    $("button.over").removeClass("hide_swiper");
                    $("button.more").addClass("hide_swiper");
                }
                if (!is_plus) {
                    $("span.leaguesIcon").html('<img src="'+data.iconUrl+'">');
                    $("section.container").html(infoDataHtml(data.data));
                } else {
                    $("section.container").append(infoDataHtml(data.data));
                }
            },
            error: function (xhr, textStatus) {
                console.log('错误');
                console.log(xhr);
                console.log(textStatus);
            },
            complete: function () {
            }
        });
    }

    $("button.more").on("click",function() {
        if(!is_load){
            return true;
        }
        page_num ++;
        getData(page_num, true);
    });
    $(window).scroll(function () {
        //$(window).scrollTop()这个方法是当前滚动条滚动的距离
        //$(window).height()获取当前窗体的高度
        //$(document).height()获取当前文档的高度
        // var bot = 50; //bot是底部距离的高度
        //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
        if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
        {
            if(is_load){
                return true;
            }
            page_num ++;
            getData(page_num, true);
        }
    });


    function infoDataHtml(data) {
        var string = "";
        string += '<div class="premier-box">' +
            '<ul class="premier">';
        for (var i = 0; i < data.length; i++) {
            var type = data[i].type === undefined ? "general" : data[i].type;
            string += '<li>' +
                '<a class="clearfix" href="'+data[i].news_url+htmlData+'">' +
                '<div class="left">' +
                '<h2>'+cutString(data[i].title, 56)+'</h2>' +
                '<div class="Tit-t">' +hoticon(data[i].hot) +
                '<span>'+data[i].time+'</span>' +
                '</div></div><div class="right"><img src="'+data[i].img+'"></div>' +
                '</a>' +
                '</li>'
        }
        string += '</ul></div>';
        return string;
    }


    /** 火热icon */
    function hoticon(hot) {
        if (hot == 1) {
            return '<span class="hot"><img src="/Public/Mobile/images/index/hot.png"></span>';
        }
        return "";
    }



    /**参数说明：
     * 根据长度截取先使用字符串，超长部分追加…
     * str 对象字符串
     * len 目标字节长度
     * 返回值： 处理结果字符串
     */
    function cutString(str, len) {
        //length属性读出来的汉字长度为1
        if(str.length*2 <= len) {
            return str;
        }
        var strlen = 0;
        var s = "";
        for(var i = 0;i < str.length; i++) {
            s = s + str.charAt(i);
            if (str.charCodeAt(i) > 128) {
                strlen = strlen + 2;
                if(strlen >= len){
                    return s.substring(0,s.length-1) + "...";
                }
            } else {
                strlen = strlen + 1;
                if(strlen >= len){
                    return s.substring(0,s.length-2) + "...";
                }
            }
        }
        return s;
    }


});