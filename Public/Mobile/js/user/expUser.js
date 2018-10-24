/**
 * Created by cytusc on 2018/4/8.
 */
$(function(){
    getList();
});

//新闻资讯点击事件
// $('.new_button').on('click',function(){
//     type = 1;
//     nav_click('.new_button','.cj_button','.newsList','.caijinList');
// });
//
// //彩经推荐点击事件
// $('.cj_button').on('click',function(){
//     type = 2;
//     nav_click('.cj_button','.new_button','.caijinList','.newsList');
// });

//导航处理点击事件
// function nav_click(_click,no_click,_list,no_list)
// {
//     $(_click).addClass('on');
//     $(no_click).removeClass('on');
//     $(_list).css('display','');
//     $(no_list).css('display','none');
//     if($(_list).html().length == undefined || $(_list).html().length < 20)
//     {
//         getList();
//     }
// }

//获取资讯列表
// function getList()
// {
//     var _class = _page = '';
//     if(type == 1)
//     {
//         _class = '.newsList';
//         _page = new_page;
//     }else{
//         _class = '.caijinList';
//         _page = caijin_page;
//     }
//     $.ajax({
//         url:'/User/expUserList.html',
//         type:'get', //GET
//         async:false,    //或false,是否异步
//         data:{
//             id:expId,page:_page,type:type,time:listtime
//         },
//         timeout:5000,    //超时时间
//         dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
//         beforeSend:function(xhr){
//             $('.loading').css('display','');
//             $('.nothing').css('display','none');
//         },
//         success:function(data,textStatus,jqXHR){
//             if(data.code == 200)
//             {
//                 var res = data.data;
//                 var html = '';
//                 for(var i = 0;i<res.length;i++)
//                 {
//                     var _logo = '';
//                     if(type == 1)
//                     {
//                         if(res[i]['name'] != undefined && res[i]['name'] != '')
//                         {
//                             _logo = '<span class="s-pl">'+res[i]['name']+'</span>';
//                         }
//                     }
//                     var _hot = '';
//                     if(res[i]['is_hot'] == 1)
//                     {
//                         _hot = '<span class="hot"><img src="'+IMAGES+'/user/hot.png" alt="全球体育网"></span>';
//                     }
//                     html += '<li>' +
//                         '<a class="clearfix" href="'+res[i]['url']+'" title="'+res[i]['title']+'">' +
//                         '<div class="left-o">' +
//                         '<h2>'+res[i]['title']+'</h2>' +
//                         '<div class="Tit-t">' +
//                         _hot +
//                         '<span>'+res[i]['add_time']+'</span>' +
//                         '<span class="num">'+res[i]['click_number']+'<img src="'+IMAGES+'/eye-icon.png" alt="全球体育网"></span>' +
//                         _logo+
//                         '</div>' +
//                         '</div>' +
//                         '<div class="right-o">' +
//                         '<img class="imgCover" src="'+res[i]['img']+'" alt="'+res[i]['title']+'">' +
//                         '</div>' +
//                         '</a>' +
//                         '</li>';
//                 }
//                 $(_class).append(html);
//             }else{
//                 $('.nothing').css('display','');
//             }
//             $('.loading').css('display','none');
//         },
//         complete:function(){
//             if(type == 1)
//             {
//                 new_page = new_page+1;
//             }else{
//                 caijin_page = caijin_page+1;
//             }
//         }
//     });
// }
$(window).scroll(function () {
    //$(window).scrollTop()这个方法是当前滚动条滚动的距离
    //$(window).height()获取当前窗体的高度
    //$(document).height()获取当前文档的高度
    // var bot = 50; //bot是底部距离的高度
    //当底部基本距离+滚动的高度〉=文档的高度-窗体的高度时；
    if (($(window).scrollTop()) >= ($(document).height() - $(window).height()))
    {
        //加载列表
        getList();
    }
});
$('.back').on('click', function () {
    window.history.back();
})

//关注
function doFollow(obj,id){
    //判断是否在登录的状态--同步请求
    var isLogin = false;
    $.ajax({
        type: 'post',
        async : false,
        url: "/Guess/show_guess.html",
        dataType: 'json',
        success: function (data) {
            if(data.status==1)
            {
                isLogin = true;
            }
            else
            {
                isLogin = false;
                window.location.href = '/User/login.html';
            }
        }
    });
    if (! isLogin)
        return false;
    var type = $(obj).html()=='已关注' ? '2' : '1';
    $.ajax({
        type:'post',
        url:"/Guess/focus.html",
        data:{id:id,type:type},
        dataType:'json',
        success:function(data){
            if(data.status==-1){
                location.href=data.url;
                return false;
            }
            if(data.status==1){
                if (type==1) {
                    $(obj).html('已关注');
                    var num = $('.article em').html();
                    $('.article em').html(parseInt(num)+1);
                } else {
                    $(obj).html('+关注');
                    var num = $('.article em').html();
                    $('.article em').html(parseInt(num)-1);
                }
            }else{
//                  $('#tips_bg').html(data.info).stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
                $('#dailogContent').html(data.info);
                $('#dailogFixBox').css({'display':'block'});
            }
        }
    });
}