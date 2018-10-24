$(function () {
    $("div.foot-discuss > a").on("click", function() {
        getData();
    });

    /**
     * 异步请求数据
     */
    function getData() {
        $.ajax({
            url:'/Index/getData.html',
            type:'get',
            async:false,
            data:{
            },
            timeout:5000,
            dataType:'json',
            beforeSend:function(xhr){
            },
            success:function(data,textStatus,jqXHR){
                getRandomData(data);
            },
            error:function(xhr,textStatus){
                console.log('错误');
                console.log(xhr);
                console.log(textStatus);
            },
            complete:function(){
            }
        });
    }

    /**
     * 获取过滤信息 并填充
     * @param data
     */
    function getRandomData (data) {
        var indexNewsData = shuffle(data.indexNews);
        indexNewsData  = indexNewsData.slice(0,4);
        $("ul.premier:first").html(eachDataHtml(indexNewsData, false));

        var pictureData = shuffle(data.pictureData);
        pictureData = pictureData.slice(0,2);
        $("ul.pictrue.clearfix:first").html(indexPictureHtml(pictureData));

        var topicsData = topicDataShuffle(data.topicsData);
        $("div.topicsData").html(topicDataVideoHtml(topicsData))
    }


    /**
     * 遍历新闻数据html
     * @param data
     * @param isTopic
     * @returns {string}
     */
    function eachDataHtml(data, isTopic) {
        var string = '';
        for (var i = 0 ; i < data.length; i++) {
            var type = data[i].type === undefined ? "general" : data[i].type;
            string += '<li>' +
                '<a class="clearfix" href="'+data[i].news_url+'" title="'+data[i].title+'">' +
                '<div class="left-part">' +
                '<h2>'+cutString(data[i].title, 56)+'</h2>' +
                '<div class="Tit-t">' +hoticon(data[i].hot) +
                '<span>'+data[i].time+'</span>' +
                '<span class="num click_number">'+
                data[i]['click_number']+
                '<img src="/Public/Mobile/images/eye-icon.png" alt="全球体育网">'+
                '</span>'+
                singicon(isTopic, data[i].sign)+
                '</div></div><div class="right-part"><img src="'+data[i].img+'" alt="'+data[i].title+'"></div>' +
                '</a>' +
                '</li>'
        }
        return string;
    }


    /**
     * 新闻图片的html
     * @param data
     * @returns {string}
     */
    function indexPictureHtml(data) {
        var string = "";
        for (var i = 0; i < data.length; i++) {
        string += '<li class="nom '+ (i === 0 ? "one" : "two") +'">' +
            '<a href="/photo/'+data[i].id+'.html" title="'+data[i].title+'">' +
            '<img src="'+data[i].cover+'" alt="'+data[i].title+'">' +
            '<div class="explain"><span>'+data[i].img_count+'</span><span><img src="Public/Mobile/images/index/i.png"></span></div>' +
            '<h3>'+data[i].title+'</h3>' +
            '</a>' +
            '</li>';
        }
        return string;
    }


    /**
     * 遍历video
     * @param data
     * @returns {string}
     */
    function eachVideoHtml(data) {
        var string = '';
        for (var i = 0; i < data.length; i++) {
            string += '<li class="nom '+ (i === 0 ? "one" : "two") +'">' +
                '<a href="'+data[i].m_url+'" title="'+data[i].title+'">' +
                '<img src="'+data[i].img_url+'" alt="'+data[i].title+'">' +
                '<h3>'+data[i].title+'</h3>' +
                '</a></li>';
        }
        return string;
    }


    /**
     * 遍历topics数据和video
     * @param data
     * @returns {string}
     */
    function topicDataVideoHtml(data) {
        var string = "";
       for (var i = 0; i < data.length; i ++) {
           string += topicsListHtml(data[i].Nav)+
           '<div class="premier-box clearfix">' +
           '<ul class="premier">' +
           eachDataHtml(data[i].Data, true)+
           '</ul>' +
           '<ul class="pictrue clearfix">' +
           eachVideoHtml(data[i].Video)+
           '</ul>' +
           '</div>'
           ;
       }
       return string;
    }


    /**
     * 随机数据筛选
     * @param data
     * @returns {*}
     */
    function topicDataShuffle(data) {
        for (var i = 0; i < data.length; i++) {
            data[i].Data = shuffle(data[i].Data).slice(0,4);
            data[i].Video = shuffle(data[i].Video).slice(0,2);
        }
        return data;
    }


    /**
     * val.Nav
     * @param data
     * @returns {string}
     */
    function topicsListHtml(data) {
        return (data === undefined || data.length === 0) ? '' :  '<div class="swiper-container list">' +
            '<div class="swiper-wrapper">' +
            eachListHtml(data)+
            '</div>'+
            '</div>' +
           '<script>var swiper=new Swiper(".list",{pagination:{el:".swiper-pagination",type:"progressbar",},});</script>';
    }

    /**
     * val.Nav
     * @param data
     * @returns {string}
     */
    function eachListHtml(data) {
        var string = '';
        for (var i = 0; i < data.length; i++) {
            string += '<div class="swiper-slide percent"><a href="'+data[i].url+'" title="'+data[i].name+'"><div class="shrink"><img src="'+data[i].iconUrl+'" alt="'+data[i].name+'"></div><span>'+data[i].name+'</span></a></div>'
        }
        return string;
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


    /** 火热icon */
    function hoticon(hot) {
        if (hot == 1) {
            return '<span class="hot"><img src="/Public/Mobile/images/index/hot.png"></span>';
        }
        return "";
    }

    /** sign标志 */
    function singicon(isTopic, sign) {
        return isTopic ? '<span class="y-c">'+sign+'</span>' : ""
    }


    /**
     随机化原数组
     **/
    function shuffle(array) {
        var m = array.length;
        var t, i;
        // 如果还剩有元素…
        while (m) {
            // 随机选取一个元素…
            i = Math.floor(Math.random() * m--);
            // 与当前元素进行交换
            t = array[m];
            array[m] = array[i];
            array[i] = t;
        }
        return array;
    }

     /*
     swiper 拖动
    */
    var swiper2 = new Swiper('.live', {
        pagination: {
            el:'.swiper-pagination',
            type:'progressbar',
        },
    });

    //banner 切换
    var swiper3 = new Swiper('.swiper-container1', {
        pagination: '.ban',
        paginationClickable: true,
        spaceBetween: 30,
        centeredSlides: true,
        autoplay: false,
        autoplayDisableOnInteraction: false
    });
});