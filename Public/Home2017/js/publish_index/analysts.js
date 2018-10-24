/**
 * @User liangzk <liangzk@qc.com>
 * @DataTime 2016-08-16 11:17
 * 情报分析
 */
//获取连胜多的用户的竞猜
$(function(){
    //焦点图轮播
    $('.focus-banner').hover(function(e) {
        $('.carousel-control').stop().fadeIn(500);
    },function(){
        $('.carousel-control').stop().fadeOut(500);
    });
    $('.carousel-control').hover(function(e) {
        $(this).animate({"opacity":"0.75"},200);
    },function(){
        $(this).animate({"opacity":"0.5"},200);
    });
    // 竞猜指数滚动条 CSS3
    $('body').on('inview', '[data-animation]', function(){
        var $this = $(this);

        var animations = $this.data('animation');
        // 去掉所有空格
        animations = animations.replace(/\s+/g, '');
        // 拆分为数组
        animations = animations.split(',');
        // 添加首元素
        animations.unshift('animation');
        // 合并为字符串 "animation-animation1-animation2-..."
        animations = animations.join('-');

        var percent = $this.data('percent');

        $this.addClass(animations).css('width', percent);
    });
    //分析切换
    $('.ana_nav ul li').click(function(e) {
        $(this).addClass('on').siblings().removeClass('on');
        var itemNum = $(this).index();
        $('.main-left-con div.itemList').eq(itemNum).show().siblings('div.itemList').hide();
    });
    // $.ajax({
    //     url:'getWinningGame.html',
    //     type:'POST',
    //     dataType:'JSON',
    //     success:function(data)
    //     {
    //         if (data.status == 1)
    //         {
    //             var list = data.info;
    //             if (list!=null && list!='')
    //             {
    //                 $('#mySelect').css({display: 'block'});
    //                 $.each(list,function(k,v){
    //                     if (v['play_type'] == 1)
    //                         var gambleType = '让球';
    //                     else
    //                         var gambleType = '大小球';
    //                     if (v['desc'] == null || v['desc'] == '')
    //                         var describe = '暂无分析';
    //                     else
    //                         var describe = v['desc'];
    //                     if (v['tradeCoin'] == 0 || v['tradeCoin'] == null)
    //                         var tradeCoinHtml = '免费';
    //                     else
    //                         var tradeCoinHtml = v['tradeCoin']+'金币';
    //                     if (v['is_check'] != null  || v['login_user'] == v['user_id'] || v['result'] != 0)
    //                     {
    //                         var BuyHtml = '<div class="freeShow">'+
    //                                             '<p class="p1">竞猜：'+v['Answer']+'<em class="text-red">'+v['handcp']+'（'+v['odds']+'）</em>'+
    //                                             '</p>'+
    //                                             '<p class="p2 text-999">分析：'+describe+' </p>'+
    //                                         '</div>';
    //                     }
    //                     else
    //                     {
    //                         var BuyHtml = '<div id="buyGamble">'+
    //                                             '<a href="javascript:;" class="btn btn-orange" page_type="analysts" onclick="payment(this,'+v['id']+','+v['tradeCoin']+')">'+
    //                                             tradeCoinHtml+
    //                                             '</a>'+
    //                                         '</div>';
    //                     }
    //                     var html = '<li>'+
    //                                     '<a target="_blank" href="//www.'+DOMAIN+'/userindex/'+v['user_id']+'.html" class="sel_top clearfix">'+
    //                                         '<div class="pull-left lef1"><img src="'+v['head']+'" width="36" height="36"></div>'+
    //                                         '<div class="pull-left lef2">'+
    //                                             '<strong class="pull-left">'+v['nick_name']+'</strong>'+
    //                                             '<i class="pull-left myIcon level lv'+v['lv']+'"></i>'+
    //                                         '</div>'+
    //                                         '<div class="pull-right rig1">'+
    //                                             '<div class="lwin">'+v['winningNum']+'连胜</div>'+
    //                                             // '<div class="pwin">周胜：86%</div>'+
    //                                         '</div>'+
    //                                     '</a>'+
    //                                     '<div class="sel_bottom">'+
    //                                         '<p class="p1">'+
    //                                             '<span style="color:'+v['union_color']+';">'+v['union_name']+'</span> '+
    //                                             '<em>'+v['game_date']+'  '+v['game_time']+'</em>'+
    //                                         '</p>'+
    //                                         '<p>'+v['home_team_name']+' VS '+v['away_team_name']+'</p>'+
    //                                         '<p class="p3">'+
    //                                             '<span>类型：</span>'+
    //                                             '<em>'+gambleType+'</em>'+
    //                                         '</p>'+BuyHtml+
    //                                     '</div>'+
    //                                 '</li>';

    //                     $('#choiceGamble').append(html);
    //                 });
    //             }
    //             else
    //             {
    //                 $('#mySelect').css({display: 'none'});
    //             }


    //         }
    //         else
    //         {
    //             $('#mySelect').css({display: 'none'});
    //         }
    //     },
    //     complete:function()
    //     {
    //         $("#gambleLoad").remove();
    //     },

    // });
});
var teacher_page = 2; //名师解盘从第二页开始
function loadMore(obj)//加载更多
{
    var class_id = $(obj).attr('class_id');

    if(class_id == 10)
    {
        $.ajax({
            url: 'analysts.html',
            type: 'POST',
            dataType: 'JSON',
            data: {'class_id':class_id,'page':teacher_page},
            success: function(data)
            {
                if(data.status == 1)
                {
                    var list = data.info;
                    if(list!=null)
                    {
                        $.each(list,function(k,v){
                            var html = '<li class="ds-list clearfix">'+
                                            '<div class="rec_face">'+
                                                '<a target="_blank" href="//www.'+DOMAIN+'/info_n/'+v['id']+'.html">'+
                                                    '<img src="'+v['face']+'" alt="" original="//img1.qqty.com/Uploads/publish/61697.jpg" style="display: inline;">'+
                                                    '<p><strong>'+v['nick_name']+'</strong></p>'+
                                                '</a>'+
                                            '</div>'+
                                            ' <div class="rec_right rec_right2">'+
                                                '<p class="mlc_title">'+
                                                    '<a target="_blank" href="//www.'+DOMAIN+'/info_n/'+v['id']+'.html" style="max-width: 370px;">'+
                                                        v['title']+
                                                    '</a>'+
                                                '</p>'+
                                                '<p class="mlc_des">'+
                                                    '<a target="_blank" href="//www.'+DOMAIN+'/info_n/'+v['id']+'.html">'+
                                                        v['remark']+
                                                    '...</a>'+
                                                '</p>'+
                                                '<div class="mlc_share">'+
                                                    '<span>'+v['update_time']+'</span>'+
                                                    '<span>发表</span>'+
                                                    '<em class="pinl"> '+v['click_number']+'</em>'+
                                                '</div>'+
                                            '</div>'+
                                        '</li>';

                            $('#teacher li:last').after(html);

                         });
                        teacher_page++;
                    }

                }
                else
                {
                    $('#teacher_loadMore').css({display: 'none'});
                    $('#teacher_showLess').css({display: 'block'});
                }
            }
        })
    }
    else
    {
        initData(obj);
    }

}
var elite_page = 2;//精英分析
var race_page = 2;//竞彩预测
var north_page = 2;//北单推荐
var page =2;
function initData(obj)
{
    var class_id = $(obj).attr('class_id');
    if (class_id == 6)
        page = elite_page;
    else if (class_id == 54)
        page = race_page;
    else if (class_id == 55)
        page = north_page;
    $.ajax({
        url: 'analysts.html',
        type: 'POST',
        dataType: 'JSON',
        data: {'class_id':class_id,'page':page},
        success: function(data)
        {
            if(data.status == 1)
            {
                var list = data.info;
                if(list!=null)
                {
                    $.each(list,function(k,v){
                        var html = '<li class="ds-list clearfix">'+
                                        '<div class="rec_img">'+
                                            '<a target="_blank" href="//www.'+DOMAIN+'/info_n/'+v['id']+'.html">'+
                                                '<img src="'+v['img']+'" alt="" original="//img1.qqty.com/Uploads/publish/61697.jpg" style="display: inline;">'+
                                            '</a>'+
                                        '</div>'+
                                        '<div class="rec_right">'+
                                            '<p class="mlc_title">'+
                                                '<a target="_blank" href="//www.'+DOMAIN+'/info_n/'+v['id']+'.html" style="max-width: 370px;">'+
                                                    v['title']+
                                                '</a>'+
                                            '</p>'+
                                            '<p class="mlc_des">'+
                                                '<a target="_blank" href="//www.'+DOMAIN+'/info_n/'+v['id']+'.html">'+
                                                    v['remark']+
                                                '...</a>'+
                                            '</p>'+
                                            '<div class="mlc_share">'+
                                                // '<em>'+v['nick_name']+' </em>'+
                                                '<span>'+v['update_time']+'</span>'+
                                                '<span>发表</span>'+
                                                '<em class="pinl"> '+v['click_number']+'</em>'+
                                            '</div>'+
                                        '</div>'+
                                    '</li>';
                        if (class_id == 6)
                            $('#elite li:last').after(html);
                        else if (class_id == 54)
                            $('#race li:last').after(html);
                        else if (class_id == 55)
                            $('#north li:last').after(html);

                     });
                        if (class_id == 6)
                            elite_page++;
                        else if (class_id == 54)
                            race_page++;
                        else if (class_id == 55)
                            north_page++;
                }

            }
            else
            {
                if (class_id == 6)
                {
                    $('#elite_loadMore').css({display: 'none'});
                    $('#elite_showLess').css({display: 'block'});
                }
                else if (class_id == 54)
                {
                    $('#race_loadMore').css({display: 'none'});
                    $('#race_showLess').css({display: 'block'});
                }
                else if (class_id == 55)
                {
                    $('#north_loadMore').css({display: 'none'});
                    $('#north_showLess').css({display: 'block'});
                }
            }
        }
    })
}