/**
 * Created by cytusc on 2018/4/25.
 */
$(function(){
    var artMainH = $('.article_main').height();
    var artHtml  = '<div class="hit">'+
        '<span>点击查看原文</span>'+
        '<i><img src="'+IMAGES+'/special/ellipse.png"></i>'+
        '</div>';
    if(artMainH>=1500){
        $('.article_main').css({'height': '1500px','overflow':'hidden'}).after(artHtml);
    }else{
        $('.article_main').removeAttr('style');
        $('.hit').remove();
    }
    $(document).on('click', '.hit span', function(e) {
        //e.preventDefault();
        /* Act on the event */
        $('.article_main').removeAttr('style');
        $('.hit').remove();
    });
    if(moreUrl != '')
    {
        var parentHtml = '<div class="popular">' +
            '<h2>精彩推荐</h2>' +
            '<ul>' +
            '</ul>' +
            '</div>';
        $('.RacingDiscuss').append(parentHtml);
        getButtomList();
    }
});

//定义底部列表加载次数计数器
var page = 1;

//加载底部精彩推荐列表
function getButtomList(){
    if(page >pageNum)
    {
        return true;
    }
    $.ajax({
        url:'/Special/getButtomList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
            class_id:class_id,time:_time,page:page
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        beforeSend:function(xhr){
        },
        success:function(data,textStatus,jqXHR){
            if(data.code == 200)
            {
                var res = data.data;
                for(var i = 0;i<res.length;i++)
                {
                    var html = '<li>' +
                        '<a class="clearfix" href="'+res[i]['url']+htmlData+'">' +
                        '<div class="pright">' +
                        '<h3>' + res[i]['title'] + '</h3>' +
                        '<div class="ptime">' +
                        '<span class="hours">'+res[i]['time']+'</span>' +
                        '<span class="read"><img src="'+IMAGES+'/photo/eye.png">'+res[i]['click_number']+'</span>' +
                        '</div>' +
                        '</div>' +
                        '<div class="pleft"><img src="'+res[i]['img']+'"></div>' +
                        '</a>' +
                        '</li>';
                    $('.popular ul').append(html);
                }
                page = page+1;
            }else{
                page = 3;
                return false;
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
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
        getButtomList();
    }
});

$('.appUserIndex').on('click',function(){
    var a = $(this);
    window.location.href = 'user:' + a.attr('user') + ':'+ a.attr('is_expert');
})