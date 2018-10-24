/**
 * Created by cytusc on 2018/4/8.
 */
$(function(){
    getList();
});
//获取资讯列表
function getList()
{
    $.ajax({
        url:'/photo/getPhoto.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
            p:page,time:listtime
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
                    var html = img1 = img2 = img3 = '';
                    if(res[i]['cover_img1'] != '')
                    {
                        img1 = '<div class="phone"><img src="' + res[i]['cover_img1'] + '" alt="'+ res[i]['title'] +'"></div>';
                    }
                    if(res[i]['cover_img2'] != '')
                    {
                        img2 = '<div class="phone"><img src="' + res[i]['cover_img2'] + '" alt="'+ res[i]['title'] +'"></div>';
                    }
                    if(res[i]['cover_img3'] != '')
                    {
                        img3 = '<div class="phone"><img src="' + res[i]['cover_img3'] + '" alt="'+ res[i]['title'] +'"></div>';
                    }
                    html = '<li class="clearfix">' +
                        '<div class="most clearfix">' +
                        '<a href="'+res[i]['url']+'" title="'+ res[i]['title'] +'">' +
                        '<h2>'+ res[i]['title'] +'</h2>' +
                        '<div class="counsel clearfix">' +
                        img1+
                        img2+
                        img3+
                        '</div>' +
                        '<div class="hotspot clearfix">' +
                        '<div class="htime">2018-01-17</div>' +
                        '<div class="hnumber">' + res[i]['click_number'] + '</div>' +
                        '<div class="review"><img src="' + IMAGES + '/eye-icon.png" alt="全球体育网"></div>' +
                        '</div>' +
                        '</a>' +
                        '</div>' +
                        '</li>';
                    $('.refer').append(html);
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