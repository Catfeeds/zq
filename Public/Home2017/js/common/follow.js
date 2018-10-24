//取消关注
var FollowUrl =  document.domain.replace('www.','').split(".").length-1 > 1 ? '' : '/Common';
function cancelFollow(id,str,obj){
    gDialog.fConfirm("提示","您确认取消关注吗？",function(rs){
        if(!rs){
            return;
        }
        $.ajax({
            type:"post",
            url : FollowUrl+"/cancelFollow.html",
            data:{'id':id},
            dataType:'json',
            success: function(msg){
                if(msg.status==1){
                    switch(str){
                        case 'rank' : 
                        case 'statistics' : 
                            $(obj).removeClass("btn-default").addClass("btn-orange").text('+关注').attr('onclick',"addFollow("+id+",'"+str+"',this);");
                            var num = parseInt($(obj).prev().text()) - 1;
                            $(obj).prev().text(num);
                        break;
                        case 'userindex' :
                            $(obj).removeClass("reduce").addClass("add-att").text('关注').attr('onclick',"addFollow("+id+",'"+str+"',this);");
                            var num = parseInt($(obj).prev().find('em').text()) - 1;
                            $(obj).prev().find('em').text(num);
                        break;
                        case 'gambleDetails' :
                            $(obj).removeClass("btn-default").addClass("btn-orange").text('+关注').attr('onclick',"addFollow("+id+",'"+str+"',this);");
                            var num = parseInt($(obj).prev().text()) - 1;
                            $(obj).prev().text(num);
                        break;
                    }
                    showMsg(msg.info);
                }else{
                    showMsg(msg.info,0,'error');
                }
            }
        });
    });
}
//关注
function addFollow(id,str,obj){
    //判断登录
    var is_login = $("input[name='userId']").val();
    if (is_login == '')
    {
        $('.myLogin').modal('show');
    }
    else
    {
        gDialog.fConfirm("提示","您确认关注他吗？",function(rs){
            if(!rs){
                return;
            }
            $.ajax({
                type:"post",
                url : FollowUrl+"/addFollow.html",
                data:{'id':id},
                dataType:'json',
                success: function(msg){
                    if(msg.status==1){
                        switch(str){
                            case 'rank' : 
                            case 'statistics' : 
                                $(obj).removeClass("btn-orange").addClass("btn-default").text('已关注').attr('onclick',"cancelFollow("+id+",'"+str+"',this);");
                                var num = parseInt($(obj).prev().text()) + 1;
                                $(obj).prev().text(num);
                            break;
                            case 'userindex' :
                                $(obj).removeClass("add-att").addClass("reduce").text('取消关注').attr('onclick',"cancelFollow("+id+",'"+str+"',this);");
                                var num = parseInt($(obj).prev().find('em').text()) + 1;
                                $(obj).prev().find('em').text(num);
                            break;
                            case 'gambleDetails' :
                                $(obj).removeClass("btn-orange").addClass("btn-default").text('已关注').attr('onclick',"cancelFollow("+id+",'"+str+"',this);");
                                var num = parseInt($(obj).prev().text()) + 1;
                                $(obj).prev().text(num);
                            break;
                        }
                        showMsg(msg.info);
                    }else{
                        showMsg(msg.info,0,'error');
                    }
                }
            });
        });
    }
}