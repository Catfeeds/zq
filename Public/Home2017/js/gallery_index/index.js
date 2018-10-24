/**
 * @author Chensiren <245017279@qq.com>
 * @since  2015-12-01
*/
$(function(){
    var p=2;// 初始化页面，点击事件从第二页开始
    var flag=false;
    //加载
    $('#loadMore').click(function () {
        //初始状态，如果没数据return ,false;否则
        if($(".row").size()<=0)
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
        var class_id = $('#class_id').val();
        $.ajax({
            type:'post',
            url:"/sendPhotos.html",
            data:{k:p,class_id:class_id},
            dataType:'json',
            beforeSend:function(){
                var load = "<div class=\"text-999 loadp\" style='margin-top:10px;'>"+
                                "<span><img src=\"/Public/Mobile/images/load.gif\"></span>"+
                                "<span style='margin-left: 5px;'>数据加载中，请稍候......</span>"+
                            "</div>";
                $(".loadPhotos").append(load);
            },
            success:function(data){
                if(data.status == 1){
                    var list = data.info;
                    if(list!=null){
                        $.each(list,function(k,v){
                            var html = "<li class=\"col-xs-3\">"+
                                        "<div class=\"desc_img\">"+
                                        "<a target=\"_blank\" href=\"//www."+DOMAIN+"/info_p/"+v['id']+".html\">"+
                                        "<img src=\""+v['images']+"\" width=\"240\" height=\"157\">"+
                                        "<div class=\"img_text clearfix\"><span class=\"pull-left\">"+v['short_title']+"</span><em class=\"pull-right img_text03\">"+v['imagesCount']+"/张</em></div>"+
                                        "</a>"+
                                        "</div>"+
                                        "</li>";
                            $(".row li:last").after(html);
                        })
                    }
                }else{
                    $("#loadMore").hide();
                    $("#showLess").show();
                    flag=true;
                }
            },
            complete:function(){
               $(".loadp").remove();
            },
        });
        p++;
    }
});