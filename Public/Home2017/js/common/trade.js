//查看竞猜
var Url =  document.domain.replace('www.','').split(".").length-1 > 1 ? '' : '/Common';
var game_type = $("input[name='game_type']").val();
function payment(obj,id,coin){
    //判断登录
    var is_login = $("input[name='userId']").val();
    if (is_login == '')
    {
        $('.myLogin').modal('show');
        return;
    }
    if(coin > 0)
    {
        var balance = $("input[name='balance']").val();

        if(balance >= coin){
            var modalhtml = 
            '<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">'+
                '<div class="modal-content">'+
                    '<div class="modal-header">'+
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<h4 class="modal-title" id="myModalLabel">温馨提示</h4>'+
                    '</div>'+
                    '<div class="modal-body" style="padding: 20px 50px;">'+
                        '<dl class="clearfix text-center">'+
                            '<dt style="padding-bottom: 10px; border-bottom: 1px solid #e5e5e5; font-weight: normal; font-size: 16px; margin-bottom: 10px;">查看该场竞猜需 <span class="text-red">'+coin+'</span> 金币</dt>'+
                            '<dd>余额充足，放心购买！</dd>'+
                        '</dl>'+
                        '<div class="btn-con" style="text-align: center; margin-top: 15px;">'+
                            '<button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-orange JConfirmBtn">确定</button> '+
                            '<button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-default" data-dismiss="modal">取消</button>'+
                        '</div>'+
                    '</div>'+
                '</div>'+
            '</div>';
        }else{
            var modalhtml = 
            '<div class="modal fade bs-example-modal-sm" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel">'+
                '<div class="modal-content">'+
                    '<div class="modal-header">'+
                        '<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>'+
                        '<h4 class="modal-title" id="myModalLabel">温馨提示</h4>'+
                    '</div>'+
                    '<div class="modal-body" style="padding: 20px 50px;">'+
                        '<dl class="clearfix text-center">'+
                            '<dt style="padding-bottom: 10px; border-bottom: 1px solid #e5e5e5; font-weight: normal; font-size: 16px; margin-bottom: 10px;">查看该场竞猜需 <span class="text-red">'+coin+'</span> 金币</dt>'+
                            '<dd>您的余额不足，请充值！</dd>'+
                        '</dl>'+
                        '<div class="btn-con" style="text-align: center; margin-top: 15px;">'+
                            '<button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-orange" onclick="window.open(\'//www.'+DOMAIN+'/UserAccount/charge.html\')">马上去</button> '+
                            '<button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-default" data-dismiss="modal">再逛逛</button>'+
                        '</div>'+
                    '</div>'+
                '</div>'+
            '</div>';  
        }
        $(".bs-example-modal-sm").remove();
        $("body").append(modalhtml);
        $('.bs-example-modal-sm').find(".JConfirmBtn").click(function(){
            $('.bs-example-modal-sm').modal('hide');
            var balanceCoin = balance - coin;
            ajaxTrade(obj,id,balanceCoin);
        });
        $('.bs-example-modal-sm').modal('show');
    }
    else
    {
        ajaxTrade(obj,id,false);
    }
}
function ajaxTrade(obj,id,balanceCoin){
    $.ajax({
        type: "POST",
        url: Url+"/trade.html",
        data: {'gamble_id':id,'game_type':game_type},
        dataType: "json",
        success: function(data){
            if(data.status == 1){
                var page_type = $(obj).attr("page_type");
                switch(page_type)
                {
                    case 'content': content(obj,data); break;
                    case 'statistics': statistics(obj,data); break;
                    case 'analysts': analysts(obj,data); break;
                }
                if(balanceCoin != false){
                    $("input[name='balance']").val(balanceCoin);
                }
            }else{
                gDialog.fConfirm("温馨提示",data.info,function(rs){
                    if(!rs){
                        return;
                    }
                    if(data.info = '赛事已结算'){
                        window.location.reload();
                        return;
                    }
                    window.open("//www."+DOMAIN+"/UserAccount/charge.html");
                });
            }
        }
    });
}

//资讯内容页
function content(obj,data){
    var game = data.info;
    var desc = game['desc'] != '' ? game['desc'] : '暂无分析';
    var html = "<div class=\"freeShow\">"+
                    "<p class=\"p1\">竞猜："+game['Answer']+"<em class=\"text-red\">"+game['handcp']+"（"+game['odds']+"）</em></p>"+
                    "<p class=\"p2 text-999\">分析："+desc+"</p>"+
                "</div>";
    $(obj).parent().html(html);
    if(game['tradeCoin'] > 0) showMsg("已成功支付"+game['tradeCoin']+"金币！");
}

//统计页
function statistics(obj,data){

    //开始

    var game = data.info;
    var desc = game['desc'];
    var fx_len = desc.length;
    if(fx_len > 85){
        desc = '<span>'+desc.substring(0,85)+ '...</span>';
    }else if(fx_len == 0){
        desc = '<span style="color: #8a8a8a">暂无分析</span>'
    }else{
        desc = '<span>'+desc+ '</span>';
    }
    var voice = '';
    if(game['is_voice'] == 1 && game['voice'] != '')
    {
        voice = '</span>'+
                '<a href="javascript:;" class="pull-left music musicOff">'+game['voice_time']+'</a>'+
                '<audio class="voice_play">'+
                '<source src="'+game['voice']+'" type="audio/mpeg">。'+
    '</audio>'
    }else{
        voice = '暂无分析</span>';
    }
    console.log(game['result']);
    var result = '';
    switch(game['result'])
    {
        case '1':
        case '0.5':
            result = 'win';
            break;
        case '-1':
        case '-0.5':
            result = 'lose';
            break;
        case '2':
            result = 'split';
            break;
        case '-10':
            result = 'cancel';
            break;
        case '-11':
            result = 'pending';
            break;
        case '-12':
            result = 'cut';
            break;
        case '-13':
            result = 'interrupt';
            break;
        case '-14':
            result = 'putoff';
            break;
    }


    var html = '<p class="tjList">'+
                '<span>推荐情况：</span>'+
                '<em class="name">'+game['Answer']+'</em>'+
                '<em>盘口：<font>'+game['handcp']+'</font></em>'+
                '<em>赔率：<font>'+game['odds']+'</font></em>'+
                '</p>'+
                '<p class="text-8a fenxi q-two">独家分析：'+ desc + '</p>'+
                '<div class="audioFx clearfix">'+
                '<span class="text-8a pull-left">语音分析：'+ voice +
                '<i class="sign '+result+'"></i>';


    //结束

    $(obj).prev('.st_count').html(html);
    //展开和隐藏
    $('.guess_view').click(function(){ 
        var s_val  = $(this).prev().text();
        var g_val  = $(this).attr('desc');
        var g_html = $(this).html();
        $(this).prev().html(g_val);
        $(this).attr('desc',s_val);
        if(g_html=='展开详情'){  
            $(this).parents('.list-li').addClass('h_auto');
            $(this).html('收起');
        }else{ 
            $(this).parents('.list-li').removeClass('h_auto');
            $(this).html('展开详情');
        }
    });
    $('.list-li .music').click(function(e) {
        $(".list-li .music").each(function(){
            $(this).siblings('audio').get(0).currentTime = 0
        });
        if($(this).hasClass('musicOff')){
            $(this).removeClass('musicOff');
            $(this).siblings('audio').get(0).play();
        }else{
            $(this).addClass('musicOff');
            $(this).siblings('audio').get(0).pause();
        }

    });
    if(game['tradeCoin'] > 0) showMsg("已成功支付"+game['tradeCoin']+"金币！");
    $(obj).remove();
}

//情报分析---连胜多用户的竞猜
function analysts(obj,data)
{
    var game = data.info;
    var desc = game['desc'] != '' ? game['desc'] : '暂无分析';
    var html = '<div class="freeShow">'+
                        '<p class="p1">竞猜：'+game['Answer']+'<em class="text-red">'+game['handcp']+'（'+game['odds']+'）</em>'+
                        '</p>'+
                        '<p class="p2 text-999">分析：'+desc+' </p>'+
                    '</div>';
    $(obj).parent('#buyGamble').html(html);
    if(game['tradeCoin'] > 0) showMsg("已成功支付"+game['tradeCoin']+"金币！");
}