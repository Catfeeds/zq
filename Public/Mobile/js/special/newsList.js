/**
 * Created by cytusc on 2018/4/9.
 */
$(function(){
    getList();
});
function getList()
{
    $.ajax({
        url:'/Special/getNewsList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
            keyWord:keyWord,time:listtime,page:page
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        beforeSend:function(xhr){
            $('.loading').css('display','');
            $('.nothing').css('display','none');
        },
        success:function(data,textStatus,jqXHR){
            if(data.code == 200)
            {
                $('.nothing').css('display','none');
                //定义各模块初始值
                var _hot = '';
                var res = data.data;
                $('.HotList').css('display','');
                for(var i = 0;i<res.length;i++)
                {
                    $('.HotList').append(newHtml(res[i]));
                }
                page = page+1;
            }else{
                $('.nothing').css('display','');
            }
            $('.loading').css('display','none');
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
}

//生成新闻资讯html
function newHtml(data)
{
    var html = '';
    var html = _hot = '';
    if(data['isHot'] == 1)
    {
        _hot = '<span class="hot"><img src="'+IMAGES+'/user/hot.png" alt="全球体育网"></span>';
    }
    html = '<li>'+
        '<a class="clearfix" href="'+data['href']+'" title="'+data['title']+'" title="'+data['title']+'">'+
        '<div class="left-part">'+
        '<h2>'+data['title']+'</h2>'+
        '<div class="Tit-t">'+
        _hot+
        '<span>'+data['add_time']+'</span>'+
        '<span class="num click_number">'+
        data['click_number']+
        '<img src="'+IMAGES+'/eye-icon.png" alt="全球体育网">'+
        '</span>'+
        '</div>'+
        '</div>'+
        '<div class="right-part">'+
        '<img src="'+data['img']+'" alt="'+data['title']+'">'+
        '</div>'+
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
        //免费直播
        getList();
    }
});