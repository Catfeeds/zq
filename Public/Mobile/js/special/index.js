/**
 * Created by cytusc on 2018/4/9.
 */
$(function(){
    if(pId == 96 && topHight > 0)
    {
        $(function(){

            var navOffset = $(".headline").offset().top;
            //当页面滚动时对顶部导航栏进行移动处理
            $(window).scroll(function(){
                var scrollPos=$(window).scrollTop();
                if(scrollPos >navOffset){
                    $('.headline').css({'position': 'fixed','top': topHight+'rem','z-index': '999'});
                    $('.disTop').css('height','.5rem');
                }else{
                    $('.disTop').css('height','0px');
                    $('.headline').css({'position': 'relative','top': '0rem'});
                }
            });
        })
    }
    getList();
});

//用来保存现有列表所有资讯id
var listId = '';
//使用点击加载更多时的分页
var listPage = 1;

//点击刷新进行局部刷新
$('.foot-discuss a').on('click',function(){
    if(listPage > 1)
    {
        $("html,body").animate({scrollTop:0}, 500);
        $('.more span').css('display','none');
        $('.loadMore').css('display','');
    }
    listPage = 1;
    listId = '';
    getList();
})

//获取当前页面需要的数据
function getList()
{
    $.ajax({
        url:'/Special/SpecialList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
            pubId:pId,videoId:hId,imgId:iId,noData:noData
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        beforeSend:function(xhr){
        },
        success:function(data,textStatus,jqXHR){
            if(data.code == 200)
            {
                //定义各模块初始值
                var _hot = _listNd = _listRd = _photo = _video = '';
                var res = data.data;
                //资讯第一模块
                if(res.hot == 0 && listHidden != 2)
                {
                    $('.HotList').css('display','none');
                }else{
                    $('.HotList').css('display','');
                    for(var i = 0;i<res.hot.length;i++)
                    {
                        _hot += newHtml(res.hot[i]);
                    }
                    $('.HotList').html(_hot);
                }
                //资讯第二模块
                if(res.listNd == 0 && listHidden != 2)
                {
                    $('.ListNd').css('display','none');
                }else{
                    $('.ListNd').css('display','');
                    for(var i = 0;i<res.listNd.length;i++)
                    {
                        _listNd += newHtml(res.listNd[i]);
                    }
                    $('.ListNd').html(_listNd);
                }
                //资讯第三模块
                if(res.listRd == 0)
                {
                    $('.ListRd').css('display','none');
                }else{
                    $('.ListRd').css('display','');
                    for(var i = 0;i<res.listRd.length;i++)
                    {
                        _listRd += newHtml(res.listRd[i]);
                    }
                    $('.ListRd').html(_listRd);
                }
                //图片模块
                if(res.photo == 0 && listHidden != 2)
                {
                    $('.ListP').css('display','none');
                }else{
                    $('.ListP').css('display','');
                    for(var i = 0;i<res.photo.length;i++)
                    {
                        _photo += photoHtml(res.photo[i],1);
                    }
                    $('.ListP').html(_photo);
                }
                //视频模块
                if(res.video == 0 && listHidden != 2)
                {
                    $('.ListV').css('display','none');
                }else{
                    $('.ListV').css('display','');
                    for(var i = 0;i<res.video.length;i++)
                    {
                        _video += photoHtml(res.video[i],0);
                    }
                    $('.ListV').html(_video);
                }
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
}

//生成新闻资讯html
function newHtml(data,type)
{
    if(type != 1)
    {
        listId += data['id']+'+';
    }
    var html = _hot = '';
    if(data['isHot'] == 1)
    {
        _hot = '<span class="hot"><img src="'+IMAGES+'/user/hot.png" alt="全球体育网"></span>';
    }
    html = '<li>'+
        '<a class="clearfix" href="'+data.url+htmlData+'" title="'+data['title']+'">'+
        '<div class="left-part">'+
        '<h2>'+data['title']+'</h2>'+
        '<div class="Tit-t">'+
        _hot+
        '<span>'+data['time']+'</span>'+
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

//生成图片html
function photoHtml(data,type)
{
    var html = _photo = _url = '';
    if(type == 1)
    {
        _photo ='<div class="explain">'+
            '<span>'+data['imgTotal']+'</span>'+
            '<span><img src="'+IMAGES+'/special/i.png" alt="全球体育网"></span>'+
            '</div>';
    }
    html = '<li class="nom">'+
        '<a href="'+data.url+htmlData+'" title="'+data['title']+'">'+
        '<div class="nom-l">'+
        '<img src="'+data['img']+'" alt="'+data['title']+'">'+
        _photo+
        '<div class="state">'+
        '<span>'+data['title']+'</span>'+
        '</div>'+
        '</div>'+
        '</a>'+
        '</li>';
    return html;
}

//点击更多的事件
function loadMore(){

    $('.more span').css('display','none');
    $('.loading').css('display','');
    $.ajax({
        url:'/Special/getMoreList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
            pid:pId,inId:listId,page:listPage,time:_time
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        beforeSend:function(xhr){
        },
        success:function(data,textStatus,jqXHR){
            if(data.code == 200)
            {
                for(var i = 0;i<data.data.length;i++)
                {
                    $('.ListRd').append(newHtml(data.data[i],1))
                }
                $('.more span').css('display','none');
                $('.loadMore').css('display','');
                listPage = listPage+1;
            }else{
                $('.more span').css('display','none');
                $('.nothing').css('display','');
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
}

$(window).scroll(function () {
    if(topHight != 0)
    {
        return true;
    }
    //$(window).scrollTop()这个方法是当前滚动条滚动的距离
    //$(window).height()获取当前窗体的高度
    //$(document).height()获取当前文档的高度
    // var bot = 50; //bot是底部距离的高度
    //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
    if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
    {
        //加载列表
        loadMore();
    }
});