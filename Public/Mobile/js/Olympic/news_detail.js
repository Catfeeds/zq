$(function () {
    var p = 2;// 初始化页面，点击事件从第二页开始
    //新闻评论框文字输入改变后效果
    $(document).on('input propertychange','#txt_pl',function(){
       $(".submit").css("border", "1px solid #01af63");
        if ($(this).val() == "" || $(this).val() == "评论资讯") {
            $(".submit").css("border", "1px solid #999999");
        }
    }).on('click','.reply02',function(){
        //回复
        $(this).children(".huifu02").toggle();
    }).on('click','.c_reply',function(){
        //回复
        $(this).next(".huifu").toggle();
    }).on('click','.js-like',function(){
        var $this = $(this);
        if ($this.hasClass('on')) {
            $('.bubbleTips').html('您已经赞过了!').stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
            return false;
        }
        var url=window.location.href;
        var id=$this.parents('.comment_box').data('id');
        $.ajax({
            type: 'post',
            url: "/Olympic/dolike.html",
            data: {id: id,type:2,url:url},
            dataType: 'json',
            success: function (data) {
                if(data.status==1){
                    $this.addClass('on');
                    $this.find('img').attr('src','/Public/Mobile/images/Olympic/p_zan.png');
                    var like_num=$this.children('em');
                    var num=parseInt(like_num.html())+1;
                    like_num.html(num);
                }else if(data.status==-1){
                    alert(data.info);
                    location.href=data.url;
                    return false;
                }
                $('.bubbleTips').html(data.info).stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
            }
        });
    }).on('click','.submit',function(){
        if($('#txt_pl').val()==''){
            $('.bubbleTips').html('请输入评论内容!').stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
            return false;
        }
        if($('#txt_pl').val().length>255){
            $('.bubbleTips').html('评论字数不能超过255个字!').stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
            return false;
        }
        var params={
            top_id:$('#txt_pl').data('top_id'),
            pid:$('#txt_pl').data('pid'),
            contnet:$('#txt_pl').val(),
            publish_id:$('#publish_id').val()
        };
        $.ajax({
            type:"POST",
            url:"/Olympic/addComment.html",
            data:params,
            dataType:"json",
            success:function(data){
                if(data.status==1){
                    var v=data.info;
                    if(v.pid==0){
                        var html='<div class="comment_box clearfix" data-id="'+v.id+'">'+
                                '<div class="head">'+
                                '<img src="'+v.face+'" alt="head"></div>'+
                                '<div class="comment_wrap">'+
                                '<aside class="c_reply"><a href="javascript:;"><img src="/Public/Mobile/images/Olympic/rp.png" alt="回复"></a></aside>'+
                                '<div class="huifu"><a href="javascript:;"></a><a href="javascript:;"></a></div>'+
                                '<p><span>'+v.nick_name+'</span><a class="js-like" href="javascript:;" >(<em>0</em>)</a></p>'+
                                '<time>'+v.create_time+'</time>'+
                                '<article>'+v.filter_content+'</article><div class="reply_box"></div></div></div>';
                        $('#content_box').prepend(html);
                    }else{
                        var html='<article class="reply reply02" data-name="'+v.nick_name+'" data-pid="'+v.id+'">'+
                                    '<div class="huifu02"><a href="javascript:;"></a><a href="javascript:;"></a></div>'+
                                    '<em>'+v.nick_name+' 回复 '+v.by_name+'：</em>'+v.filter_content+'</article>';
                        $(".comment_box[data-id='"+v.top_id+"']").find('.reply_box').prepend(html);
                    }
                    $('.bubbleTips').html('评论成功!').stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
                    $('#txt_pl').val('');
                     $('#txt_pl').prop('placeholder','评论资讯').data('pid',0).data('top_id',0);
                }else if(data.status==-1){
                    alert(data.info);
                    location.href=data.url;
                }else{
                    $('.bubbleTips').html(data.info).stop().fadeIn(300).animate({'top': '40%'}, 1500).hide(0).animate({'top': '50%'});
                }
            }
        });
    }).on('click','.huifu :first-child',function(){
        var id=$(this).parents('.comment_box').data('id');
        var nick_name=$(this).parent().next().children().html();
        $('.huifu').hide();
        $('#txt_pl').prop('placeholder','回复:'+nick_name).data('pid',id).data('top_id',id).focus();
    }).on('click','.huifu :last-child',function(){
        var id=$(this).parents('.comment_box').data('id');
        $('#report-submit').data('id',id);
        $('#show').show();
        $('#bg').show();
        $('.huifu').hide();
    }).on('click','.huifu02 :first-child',function(){
        var top_id=$(this).parents('.comment_box').data('id');
        var pid=$(this).parents('.reply02').data('pid');
        var nick_name=$(this).parents('.reply02').data('name');
        $('.huifu').hide();
        $('#txt_pl').prop('placeholder','回复:'+nick_name).data('pid',pid).data('top_id',top_id).focus();
    }).on('click','.huifu02 :last-child',function(){
        var id=$(this).parents('.reply02').data('pid');
        $('#report-submit').data('id',id);
        $('#show').show();
        $('#bg').show();
    }).on('click','#report-submit',function(){
        var id=$(this).data('id');
        var choice=$('input[name="report_content"]:checked').val();
        $('.huifu').hide();
        $.ajax({
            type:"POST",
            url:"/Olympic/toReport.html",
            data:{id:id,choice:choice},
            dataType:"json",
            success:function(data){
                $('#report-submit').data('id',null);
                $('#show').hide();
                $('#bg').hide();
                alert(data.info);
            }
        });
    }).on('click','#modal-close',function(){
        $('#report-submit').data('id',null);
        $('#show').hide();
        $('#bg').hide();
    }).on('click','#js-loadmore',function(){
        //加载评论
        var params = {
            page: p,
        }
        $.ajax({
            type: 'post',
            url: "",
            data: params,
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    var list = data.info;
                    if (list != null) {
                        $.each(list, function (k, v) { 
                            var is_like='';
                            if(v.is_like=='1'){
                                is_like=' on ';   
                            }
                            var html = '<div class="comment_box clearfix" data-id="'+v.id+'">'+
                                        '<div class="head">'+
                                        '<img src="'+v.head+'" alt="'+v.nick_name+'"></div>'+
                                        '<div class="comment_wrap">'+
                                        '<aside class="c_reply"><a href="javascript:;"><img src="/Public/Mobile/images/Olympic/rp.png" alt="更多"></a></aside>'+
                                        '<div class="huifu"><a href="javascript:;"></a><a href="javascript:;"></a></div>'+
                                        '<p><span class="nick_name">'+v.nick_name+'</span><a class="js-like '+is_like+'" href="javascript:;">(<em>'+v.like_num+'</em>)</a></p>'+
                                        '<time>'+v.create_time+'</time>'+
                                        '<article>'+v.filter_content+'</article>'+
                                        '<div class="reply_box">';
                                if(v.children){
                                    $.each(v.children, function (key, vv) {
                                        if(vv.status=='1'){
                                            html+='<article class="reply"><em>'+vv.nick_name+' 回复 '+vv.by_username+'：</em>'+vv.filter_content+'</article>';
                                        }else{
                                            html+='<article class="reply">该条评论已被管理员屏蔽</article>';
                                        }
                                    });
                                }
                            $("#content_box .comment_box:last").after(html);
                        });
                    }
                    if (data.info.length < 10) {
                        $('#js-loadmore').hide();
                        $('#showLess').show();
                    }
                } else {
                    $('#js-loadmore').hide();
                    $('#showLess').show();
                }
            }
        });
        p++;
    });
});