/**
 * Created by cytusc on 2018/5/24.
 */

//列表请求开始的页码
var page = 2;

$('.moreList').on('click',function(){
//获取导航栏列表
    $.ajax({
        url:'/recommend.html',
        type:'post', //GET
        async:false,    //或false,是否异步
        data:{
            page:page,time:listTime
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success:function(data){
            if(data.code == 200)
            {
                var data = data.data;
                for(var i = 0;i<data.length;i++)
                {
                    var html = getHtml(data[i]);
                    $('.moreList').before(html);
                }

                page = page+1;
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
});

function getHtml(data) {
    var html = '';
    var short_title = data.short_title;
    if(short_title == null)
    {
        short_title = '';
    }
    html = '<li>' +
        '<a href="' + data.href + '" title="' + data.title + '" class="expect clearfix">' +
        '<div class="number-left">第' + data.vol + '期</div>' +
        '<div class="triangle"></div>' +
        '<div class="number-right">' +
        '<h3>' + short_title + '</h3>' +
        '<p>' + data.title + '</p>' +
        '</div>' +
        '<div class="magnify"><img src="' + data.img + '" alt="' + data.title + '"></div>' +
        '</a>' +
        '</li>';
    return html;
}

$(document).ready(function(){
    var timerId = '';
    function addAnimateClass(){
        $(".group-all").addClass('active');
        clearInterval(timerId);
        setTimeout(function(){
            $(".group-all").removeClass('active');
            timerId = setInterval(function(){
                addAnimateClass();
            },2000)
        },2000)
    };
    timerId = setInterval(function(){
        addAnimateClass();
    },1000)
})

$('.tab-left').on('click',function(){
    $('.tab-right').removeClass('on');
    $('.tab-left').addClass('on');
    $('.yaList').css('display','');
    $('.jinList').css('display','none');
});

$('.tab-right').on('click',function(){
    $('.tab-right').addClass('on');
    $('.tab-left').removeClass('on');
    $('.yaList').css('display','none');
    $('.jinList').css('display','');
});