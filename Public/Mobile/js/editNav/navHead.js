/**
 * Created by cytusc on 2018/4/13.
 */
var listArr = [];
var is_order = 1;
if(!store('?navKey') || store('navKey') == undefined)
{
    is_order = 0;
}

function alert() {} // 重写alert方法，去除火狐浏览器弹窗
$(function(){

    var navOffset = $(".top-nav").offset().top;
    //当页面滚动时对顶部导航栏进行移动处理
    $(window).scroll(function(){
        var scrollPos=$(window).scrollTop();
        if(scrollPos >=navOffset){
            $('.top-nav').css({'display': 'block','position': 'fixed','top': '0rem','z-index': '999','background-color':'#FFF'});
            $('.disTop').css('height','.7rem');
        }else{
            $('.disTop').css('height','0px');
            $('.top-nav').css({'position': 'relative'});
        }
    });
    getNav();
})
function getNav()
{
    //获取导航栏列表
    $.ajax({
        url:'/Nav/getNavList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success:function(data){
            if(data.code == 200)
            {
                listArr = data.data;
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });
    //当第一次进入时默认全部显示
    if(is_order)
    {
        var changKey = JSON.parse(store('navKey'));
        var listTmp = [];
        //优化数据结构
        for(var i = 0;i<listArr.length;i++)
        {
            listTmp[listArr[i]['sign']] = listArr[i];
        }
        //将频道进行上下分类
        for(var i = 0;i<changKey.length;i++)
        {
            var k = changKey[i];
            var _div = _class = '';
            if(listTmp[k] !== undefined)
            {
                if(listTmp[k]['sign'] == navId)
                {
                    _class = ' class="current"';
                }
                var html = '<li '+_class+'><a href="'+listTmp[k]['url']+'">'+listTmp[k]['name']+_div+'</a></li>';
                $('.scrol-in ul').append(html);
            }
        }
        if($('.scrol-in ul').html() == '')
        {
            creatNav(listArr)
        }
    }else{
        creatNav(listArr)
    }
    //navAddHref();
}

//插入数据
function creatNav(listArr)
{
    for(var i = 0;i<listArr.length;i++)
    {
        var _div = _class = '';
        if(listArr[i]['sign'] == navId)
        {
            _class = ' class="current"';
        }
        var html = '<li '+_class+'><a href="'+listArr[i]['url']+'">'+listArr[i]['name']+_div+'</a></li>';
        $('.scrol-in ul').append(html);
    }
    //navAddHref();

}

//给插入的导航添加点击跳转事件
// function navAddHref()
// {
//     $('.scrol-in ul li').on('click',function(){
//         var url = $(this).attr('href');
//         window.location.href=url;
//     })
// }