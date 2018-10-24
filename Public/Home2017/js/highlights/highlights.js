$(function(){
    //返回顶部
    $(window).scroll(function(e) {
        if($(window).scrollTop()>$(window).height()){
            $('.return-top').fadeIn(300);
        }else {
            $('.return-top').fadeOut(300);
        }
    });
    $('.return-top').click(function(e) {
        $('body,html').animate({'scrollTop':'0'},500);

    });
    var p=2;// 初始化页面，点击事件从第二页开始
    var flag=false;
    //加载
    $('#loadMore').click(function () {
        //初始状态，如果没数据return ,false;否则
        if($(".list_item").size()<=0)
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
        var game_type = $("#game_type").val();
        var union_id  = $("#union_id").val();
        $.ajax({
            type:'post',
            url:"/Highlights/sendMore.html",
            data:{k:p,game_type:game_type,union_id:union_id},
            dataType:'json',
            beforeSend:function(){
             $(".posts").append("<div id='load'></div>");
            },
            success:function(data){
                if(data.status == 1){
                    var list = data.info;
                    if(list!=null){
                        $.each(list,function(k,v){
                            if(v['web_ischain'] == 0){
                                var url = "/Video/lives/type/"+v['game_type']+"/id/"+v['game_id']+"/jj_id/"+v['id']+".html";
                            }else{
                                var url = v['web_url'];
                            }
                            var html = "<li class=\"list_item\">"+
                                            "<a href=\""+url+"\" target=\"_blank\" class=\"figure\" tabindex=\"-1\">"+
                                                "<img src=\""+v['img']+"\" width=\"186\" height=\"104\" alt=\"NBA\">"+
                                            "</a>"+       
                                            "<strong class=\"figure_title\">"+
                                                "<a href=\""+url+"\" target=\"_blank\">"+v['title']+"</a>"+
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
                            $(".posts li:last").after(html);
                        })
                    }
                }else{
                    $("#loadMore").hide();
                    $("#showLess").show();
                    flag=true;
                }
            },
            complete:function(){
               $("#load").remove();
            },
        });
        p++;
    }
});