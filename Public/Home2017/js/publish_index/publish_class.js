$(function(){
    // 动画效果 CSS3
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
    var p=2;// 初始化页面，点击事件从第二页开始
    var flag=false;
    //加载
    $('#loadMore').click(function () {
        //初始状态，如果没数据return ,false;否则
        if($(".ds-list").size()<=0)
        {
            return false;
        }else{
            send();
        }
    });
    function send(){
        if(flag){
            return false;
        }
        var class_id = $("#class_id").val();
        $.ajax({
            type:'post',
            url:"/sendNews.html",
            data:{k:p,class_id:class_id},
            dataType:'json',
            beforeSend:function(){
             $("#loadMore").text("数据加载中，请稍候......");
            },
            success:function(data){
                if(data.status == 1){
                    var list = data.info;
                    if(list!=null){
                        if(class_id == 10){
                            //名师
                            $.each(list,function(k,v){
                                var html = "<li class='ds-list'>"+
                                               /*   "<em class='tuijian'></em>"+*/
                                                  "<div class='rec_img'>"+
                                                      "<a target='_blank' href='http://www."+DOMAIN+"/userindex/"+v['user_id']+".html'><img src='"+v['userFace']+"'></a><p>"+v['nick_name']+"</p>"+
                                                  "</div>"+
                                                  "<div class='rec_right'>"+
                                                      "<p class='mlc_title'><a target='_blank' href='http://www."+DOMAIN+"/info_n/"+v['id']+".html'>"+v['title']+"</a><span class='add_time'>"+v['update_time']+"</span></p>"+
                                                      "<p class='mlc_des'>"+v['remark']+"...</p>"+
                                                      "<div class='mlc_share'>"+
                                                      "<em>浏览："+v['click_number']+"</em>"+
                                                      "<div class=\"s_main\"><a href=\"javascript:;\" class=\"share\">分享</a>"+
                                                          "<div>"+
                                                              "<a class=\"jiathis_button_weixin\" href=\"javascript:;\"><img src=\"/Public/Home/images/publish_index/share01.jpg\"></a>"+
                                                              "<a class=\"jiathis_button_tsina\" href=\"javascript:;\"><img src=\"/Public/Home/images/publish_index/share02.jpg\"></a>"+
                                                              "<a class=\"jiathis_button_qzone\" href=\"javascript:;\"><img src=\"/Public/Home/images/publish_index/share03.jpg\"></a>"+
                                                              "<a class=\"jiathis_button_cqq\" href=\"javascript:;\"><img src=\"/Public/Home/images/publish_index/share04.jpg\"></a>"+
                                                          "</div>"+
                                                      "</div>"+
                                                      "<a target='_blank' href='http://www."+DOMAIN+"/info_n/"+v['id']+".html#cm' class='pinl'><img src='/Public/Home/images/publish_index/pinl.png' alt='评论'> 评论 （"+v['comment']+"）</a>"+
                                                      "</div>"+
                                                  "</div>"+
                                              "</li>";
                                $(".posts li:last").after(html);
                            })
                            getShare();
                            $(".main-left-con ul li:odd").css("background-color","#f5f5f5")
                        }else{
                            $.each(list,function(k,v){
                                var html = "<li class='ds-list'>"+
                                             "<div class='rec_img'>"+
                                             "<a target='_blank' href='http://www."+DOMAIN+"/info_n/"+v['id']+".html'><img src='"+v['img']+"' alt=''></a>"+
                                             "</div>"+
                                           "<div class='rec_right'>"+
                                             "<p class='mlc_title'><a target='_blank' href='http://www."+DOMAIN+"/info_n/"+v['id']+".html'>"+v['title']+"</a><span>"+v['update_time']+"</span></p>"+
                                             "<p class='mlc_des'><a target='_blank' href=''>"+v['remark']+"...</a></p>"+
                                             "<div class='mlc_share'>"+
                                             "<em>浏览："+v['click_number']+"</em>"+
                                               "<div class='s_main'><a href='javascript:;' class='share'>分享</a>"+
                                                 "<div>"+
                                                 "<a class='jiathis_button_weixin' href='javascript:;'><img src='/Public/Home/images/login/wx.png'></a>"+
                                                 "<a class='jiathis_button_tsina' href='javascript:;'><img src='/Public/Home/images/login/sina.png'></a>"+
                                                 "<a class='jiathis_button_qzone' href='javascript:;'><img src='/Public/Home/images/login/kojian.png'></a>"+
                                                 "<a class='jiathis_button_cqq' href='javascript:;'><img src='/Public/Home/images/login/qq.png'></a>"+
                                                 "</div>"+
                                               "</div>"+
                                             "<a target='_blank' href='http://www."+DOMAIN+"/info_n/"+v['id']+".html#cm' class='pinl'><img src='/Public/Home/images/publish_index/pinl.png' alt='评论'> 评论 （"+v['comment']+"）</a>"+
                                             "</div>"+
                                           "</div>"+
                                           "</li>";
                                $(".posts li:last").after(html);
                            })
                            getShare();
                        }
                        $(".s_main").hover(function(){ 
                           $(this).children("div").show();
                        },function(){
                           $(this).children("div").hide();
                        });
                        $.getScript("http://v3.jiathis.com/code/jia.js");
                    }
                }else{
                    $("#loadMore").hide();
                    $("#showLess").show();
                    flag=true;
                }
            },
            complete:function(){
               $("#loadMore").text("加载更多");
            },
        });
        p++;
    }
    $(".s_main").hover(function(){ 
       $(this).children("div").show();
    },function(){
       $(this).children("div").hide();
    }); 
});