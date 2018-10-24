/**
 * Created by cytusc on 2018/4/9.
 */
$(function(){
    getList();
});
//获取资讯列表
function getList()
{
    $.ajax({
        url:'/video/getVideoList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
            p:page,time:listtime,key:keyWord
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
                var res = data.data;
                for (var i = 0; i < res.length; i++) {
                    var html = is_top = '';
                    if(res[i]['is_top'] == 1)
                    {
                        is_top = '<a class="tally"><img src="'+IMAGES+'/video/hot.png" alt="全球体育网"></a>';
                    }
                    html = '<div class="banner">'+
                        '<a href="'+res[i]['url']+htmlData+'" title="'+res[i]['title']+'"><img src="'+res[i]['img']+'" alt="'+res[i]['title']+'">'+
                        '<div class="explain">'+
                        '<span>'+res[i]['title']+'</span>'+
                        '</div>'+
                        '<div class="video">'+
                        '<img src="'+IMAGES+'/video/video.png" alt="全球体育网">'+
                        '</div>'+
                        '<div class="state">'+
                        '<span class="number-s">'+res[i]['click']+'次播放</span>'+
                        '</div>'+
                        '</a>'+
                        '</div>'+
                        '<div class="button">'+



                        '<a class="one">'+
                        '<span><img src="'+res[i]['head']+'" alt="'+res[i]['name']+'"></span>'+
                        '<span class="name-o">'+res[i]['name']+'</span>'+
                        '</a>'+
                        '<a class="two">'+
                        '<span>'+res[i]['class']+'</span>'+
                        '</a>'+
 

                        is_top+

                        '</div>';
                    $('.videoList').append(html);
                }

            }else{
                $('.nothing').css('display','');
            }
            $('.loading').css('display','none');
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
            page = page + 1;
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
        getList();
    }
});