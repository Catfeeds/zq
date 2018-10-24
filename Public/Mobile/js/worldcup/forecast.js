/**
 * Created by cytusc on 2018/6/20.
 */
var page = 1;

$(function(){
    getNews()
});

function getNews()
{
    $.ajax({
        url:'/WorldCupTeam/recommend.html',
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
                    $('.forecast-box').append(html);
                }

                page = page+1;
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
}
function getHtml(data) {
    var html = '';
    var short_title = data.short_title;
    if(short_title == null)
    {
        short_title = '';
    }

    html = '<li>'+
        '<a href="' + data.href +htmlData+ '" title="' + data.title + '">'+
        '<div class="order">'+
        '<span style="font-size: .5rem;white-space: nowrap;">' + data.vol + '</span><span>期</span></div>'+
        '<div class="forecast-center">'+
        '<img src="' + data.img + '" alt="' + data.title + '">'+
        '<span>'+
        '<h2>' + short_title + '</h2>'+
        '<span>' + data.title + '</span>'+
        '</span></div>'+
        '</a>'+
        '</li>';
    return html;
}

$(window).scroll(function () {
    //$(window).scrollTop()这个方法是当前滚动条滚动的距离
    //$(window).height()获取当前窗体的高度
    //$(document).height()获取当前文档的高度
    // var bot = 50; //bot是底部距离的高度
    //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
    if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
    {
        //加载列表
        getNews();
    }
});
