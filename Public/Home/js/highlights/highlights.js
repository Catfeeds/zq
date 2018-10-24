$(function(){
    var p=2;// 初始化页面，点击事件从第二页开始
    //加载
    $('a.load_ajax').click(function () {
        //初始状态，如果没数据return ,false;否则
        if($(".list_item").size()<=0)
        {
            return false;
        }else{
            send();
        }
    });
    function send(){
        var path  = $('.load_ajax').attr('path');
        $.ajax({
            type:'post',
            url:"/sendMore.html",
            data:{k:p,path:path},
            dataType:'json',
            beforeSend:function(){
                $(".load_ajax").html("<img src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...");
            },
            success:function(data){
                if(data.status == 1){
                    var list = data.info;
                    if(list){
                        var html = '';
                        $.each(list,function(k,v){
                            html += "<li class=\"list_item\">"+
                                        "<a href=\""+v['href']+"\" target=\"_blank\" class=\"figure\" tabindex=\"-1\">"+
                                            "<img class=\"lazy"+p+"\" data-original=\""+v['img']+"\" width=\"186\" height=\"104\" alt=\""+v['title']+"\">"+
                                        "</a>"+       
                                        "<strong class=\"figure_title\">"+
                                            "<a href=\""+v['href']+"\" target=\"_blank\">"+v['title']+"</a>"+
                                        "</strong>"+
                                        "<div class=\"figure_info\">"+
                                            "<span class=\"figure_info_brand\">"+
                                                "<span class=\"info_inner\">"+v['add_time']+"</span>"+
                                            "</span>"+
                                            "<span class=\"figure_info_play figure_info_right\">"+
                                                "<i class=\"ico_play_12\"></i>"+
                                                "<span class=\"info_inner\">"+v['click_num']+"</span>"+
                                            "</span>"+
                                        "</div>"+
                                    "</li>";
                        })
                        $(".posts li:last").after(html);
                        $("img.lazy"+p).lazyload({
                            placeholder: staticDomain+"/Public/Images/loading.png",
                            effect: "fadeIn",
                            threshold: 150,
                            failurelimit: 50
                        });
                        p++;
                        $(".load_ajax").html("查看更多");
                    }else{
                        $(".load_ajax").html("没有更多了");
                    }
                }else{
                    $(".load_ajax").html("没有更多了");
                }
            },
        });
    }
});