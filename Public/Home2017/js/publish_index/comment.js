$(function(){
    $('body').delegate('.comment-submit','click',function(){
        //判断登录
        var is_login = $("input[name='userId']").val();
        if (is_login == '')
        {
            $('.myLogin').modal('show');
            return;
        }
        //根据布局结构获取当前评论内容
        var content = $.trim($(this).parents(".review-box").find("textarea").val());
        if(""==content){
            _alert("提示温馨","评论内容不能为空哦！");
            return;
        }
        if(content.length > 255){
            showMsg("请限制字数在255字以内！",0,'error');
            return;
        }
        var cmdata = new Object();
        cmdata.pid = $(this).attr("pid");//上级评论id
        cmdata.top_id = $(this).attr("top_id");//最上级评论id
        cmdata.content = content;
        cmdata.user_id = is_login;//评论者id
        cmdata.by_user = $(this).attr("by_user");//被评论者id
        cmdata.by_name = $(this).attr("by_name");
        cmdata.publish_id = $("#publish_id").val();
        $.ajax({
            type:"POST",
            url:"/addComment.html",
            data:{
                comment:JSON.stringify(cmdata)
            },
            dataType:"json",
            beforeSend:function(){
                if(cmdata.pid == 0){
                    $(".review-box-0").append("<div id='loads'></div>");
                }else{
                    $(".pop_reply").append("<div id='loads'></div>");
                }
            },
            success:function(data){
                if(data.status == 1){
                    $(".ds-post-reply").next().remove();//删除已存在的所有回复div
                    if(cmdata.pid == 0){
                        //一级评论
                        var html = "<li class=\"ds-list\">"+
                                    "<div class=\"ds-post-self clearfix\">"+
                                    "<div class=\"ds-avatar pull-left\">"+
                                    "<img src=\""+data.info['face']+"\">"+
                                    "</div>"+
                                    "<div class=\"ds-comment-body pull-right\">"+
                                    "<div class=\"jubao\" commentid=\""+data.info['id']+"\">"+
                                    "<div class=\"ds-comment-header\">"+
                                        "<span class=\"ds-user-name text-green\">"+data.info['nick_name']+"</span>"+
                                        "<em class=\"text-999\">"+data.info['create_time']+"</em>"+
                                        "<a href=\"javascript:void(0)\" class=\"text-999 np-btn-report report jubao-"+data.info['id']+"\">举报</a>"+
                                    "</div>"+
                                        "<p class=\"comment-content\">"+replace_em(data.info['filter_content'])+"</p>"+
                                    "<div class=\"ds-comment-footer\">"+
                                        "<a class='top-"+data.info['id']+" ds-post-likes' top='"+data.info['id']+"' href='javascript:void(0);'>顶(<span>0</span>)</a>"+
                                        "<a class='ds-post-reply' commentid='"+data.info['id']+"' top_id='"+data.info['id']+"' by_user='"+data.info['user_id']+"' href='javascript:void(0);'>回复(<em>0</em>)</a>"+
                                    "</div>"+
                                    "</div>"+
                                    "</div>"+
                                    "</div>"+
                                    "</li>";
                        $("#saytext").val('');
                        $(".posts").prepend(html);
                    }else{
                        var html = "<div class=\"reply-con\">"+
                                    "<div class=\"ds-post-self clearfix\">"+
                                    "<div class=\"ds-avatar pull-left\">"+
                                    "<img src=\""+data.info['face']+"\">"+
                                    "</div>"+
                                    "<div class=\"ds-comment-body pull-right\">"+
                                    "<div class=\"jubao\" commentid=\""+data.info['id']+"\">"+
                                    "<div class=\"ds-comment-header\">"+
                                        "<span class=\"ds-user-name text-green\">"+data.info['nick_name']+"</span>"+
                                        "<em class=\"text-999\">"+data.info['create_time']+"</em>"+
                                        "<a href=\"javascript:void(0)\" class=\"text-999 np-btn-report report jubao-"+data.info['id']+"\">举报</a>"+
                                    "</div>"+
                                    "<p>回复 <span class=\"text-green\">"+data.info['by_name']+"</span>："+replace_em(data.info['filter_content'])+"</p>"+
                                    "<div class=\"ds-comment-footer\">"+
                                        "<a class='top-"+data.info['id']+" ds-post-likes' top='"+data.info['id']+"' href='javascript:void(0);'>顶(<span>0</span>)</a>"+
                                        "<a class='ds-post-reply' commentid='"+data.info['id']+"' top_id='"+data.info['top_id']+"' by_user='"+data.info['user_id']+"' href='javascript:void(0);'>回复</a>"+
                                    "</div>"+
                                    "</div>"+
                                    "</div>"+
                                    "</div>"+
                                    "</div>";
                        var num = $(".posts li a[top_id='"+data.info['top_id']+"']").first().find("em").html();
                        var num = parseInt(num)+1;
                        $(".posts li a[top_id='"+data.info['top_id']+"']").first().find("em").html(num);
                        $(".posts li a[top_id='"+data.info['top_id']+"']").first().parents('.jubao').after(html);
                    }
                    //更新评论总数
                    $(".title-r").find("span").text(data.info['commentCount']);
                    if($("#notComment").length > 0){
                        $("#notComment").hide();
                    }
                    showMsg(data.info['info'],0,'success');
                }else{
                    showMsg(data.info,0,'error');
                }
            },
            complete:function(){
               $("#loads").remove();
            },
        });
    })

    //回复
    $("body").delegate(".ds-post-reply","click",function(){
        //判断登录
        var is_login = $("input[name='userId']").val();
        if (is_login == '')
        {
            $('.myLogin').modal('show');
            return;
        }
        if($(this).parent().prev().attr("class") == "text-999"){
            showMsg("不能回复！",0,'error');
            return;
        }
        if($(this).next().length>0){
            $(this).next().remove();
        }else{
            $(".ds-post-reply").next().remove();
            var strHtml = '';
            //要回复的评论id
            var pid = $(this).attr("commentid");
            var by_user = $(this).attr("by_user");
            var top_id = $(this).attr("top_id");
            var username = $(this).parent().siblings(".ds-comment-header").find("span").text();
            strHtml += '<div class="pop_reply review-box">';
            strHtml += '<div class="textarea"><textarea tabindex="1" autocomplete="off" name="content" accesskey="u" placeholder="回复 @'+username+' : ""></textarea></div>';
            strHtml += '<div class="commtSub clearfix"><a href="javascript:void(0)" pid="'+pid+'" by_user="'+by_user+'" by_name="'+username+'" top_id="'+top_id+'" class="btn btn-green pull-right comment-submit">回复</a></div>';
            strHtml += '</div>';
            $(this).after(strHtml);
        }
    });
    //顶
    $("body").delegate(".ds-post-likes","click",function(){
        //判断登录
        var is_login = $("input[name='userId']").val();
        if (is_login == '')
        {
            $('.myLogin').modal('show');
            return;
        }
        var id = $(this).attr("top");
        if($(this).parent().prev().attr("class") == "text-999"){
            showMsg("不能点赞！",0,'error');
            return;
        }
        $.ajax({
            type:"POST",
            url:"/addLikeNum.html",
            data:{'id':id,'user_id':is_login},
            dataType:"json",
            success:function(data){
                if(data.status == 1){
                    var num = $(".top-"+id).find("span").html();
                    var num = parseInt(num)+1;
                    $(".top-"+id).removeClass('ds-post-likes').addClass('ds-post-likes-true').find("span").html(num);
                }else{
                    showMsg(data.info,0,'error');
                }
            }
        });
    });
    //弹出举报窗口
    $('body').delegate('.report','click',function(){
        //判断登录
        var is_login = $("input[name='userId']").val();
        if (is_login == '')
        {
            $('.myLogin').modal('show');
            return;
        }
        if($(this).parent().next().attr("class") == "text-999"){
            showMsg("该评论已被屏蔽！",0,'error');
            return;
        }
        var id = $(this).parents(".jubao").attr("commentid");
        var html = "<div class=\"np-report\" id=\"public_report\" >"+
                        "<div class=\"np-report-header clearfix\">"+
                            "<h4><strong>请您选择举报的原因</strong></h4>"+
                            "<a href=\"javascript:void(0)\" class=\"close\" onclick=\"$(this).parents('.np-report').remove()\" hidefocus=\"true\"></a>"+
                        "</div>"+
                        "<div class=\"np-report-content\">"+
                            "<ul>"+
                                "<li>"+
                                    "<input name=\"report_content\" id=\"content1\" type=\"radio\" value=\"反动言论\">"+
                                    "<label for=\"content1\">反动言论</label>"+
                                "</li>"+
                                "<li>"+
                                    "<input name=\"report_content\" id=\"content2\" type=\"radio\" value=\"淫秽色情\">"+
                                    "<label for=\"content2\">淫秽色情</label>"+
                                "</li>"+
                                "<li>"+
                                    "<input name=\"report_content\" id=\"content3\" type=\"radio\" value=\"虚假中奖\">"+
                                    "<label for=\"content3\">虚假中奖</label>"+
                                "</li>"+
                                "<li>"+
                                    "<input name=\"report_content\" id=\"content4\" type=\"radio\" value=\"广告营销\">"+
                                    "<label for=\"content4\">广告营销</label>"+
                                "</li>"+
                                "<li>"+
                                    "<input name=\"report_content\" id=\"content5\" type=\"radio\" value=\"人身攻击\">"+
                                    "<label for=\"content5\">人身攻击</label>"+
                                "</li>"+
                                "<li>"+
                                    "<input name=\"report_content\" id=\"content6\" type=\"radio\" value=\"其他\">"+
                                    "<label for=\"content6\">其他</label>"+
                                "</li>"+
                            "</ul>"+
                        "</div>"+
                        "<div class=\"np-report-footer clearfix\">"+
                            "<a class=\"btn btn-green pull-right report_submit\" href=\"javascript:void(0)\" commentid='"+id+"' hidefocus=\"true\">提交</a>"+
                            "<a class=\"btn btn-default pull-right\" href=\"javascript:void(0)\" hidefocus=\"true\" onclick=\"$(this).parents('.np-report').remove()\">取消</a>"+
                        "</div>"+
                    "</div>";
        $(".report").next().remove();//删除已存在的所有举报div
        $(this).after(html);
    });

    //提交举报
    $("body").delegate(".report_submit","click",function(){
        var id = $(this).attr("commentid");
        var report_content = $("input[name='report_content']:checked").val();
        if(undefined==report_content){
            showMsg("请您选择举报的原因！",0,'error');
            return;
        }
        $.ajax({
            type:"POST",
            url:"/addReport.html",
            data:{'id':id,'report_content':report_content},
            dataType:"json",
            success:function(data){
                if(data.status == 1){
                    $(".jubao-"+id).removeClass("report").text("举报已受理，请留意站内通知！")
                    $(".np-btn-report").next().remove();//删除已存在的所有举报div
                    showMsg(data.info);
                }else{
                    showMsg(data.info,0,'error');
                }
            }
        });
    });

    //替换标签标签
    function replace_em(str){
        str = str.replace(/\</g,'&lt;');
        str = str.replace(/\>/g,'&gt;');
        str = str.replace(/\n/g,'<br/>');
        str = str.replace(/\[em_([0-9]*)\]/g,'<img src="/Public/Home/images/publish_index/arclist/$1.gif" border="0" />');
        return str;
    }
    //替换标签标签
    $(".comment-content").each(function(index, element){
        var comment = $(element).text();
        $(element).html(replace_em(comment))
    })
    $('.emotion').qqFace({
        id : 'facebox',
        assign:'saytext',
        path:'/Public/Home/images/publish_index/arclist/'    //表情存放的路径
    });
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
        var publishid = $("#publishid").val();
        var sign      = $("#sign").val();
        $.ajax({
            type:'post',
            url:"/send.html",
            data:{k:p,publishid:publishid,sign:sign},
            dataType:'json',
            beforeSend:function(){
             $(".posts").append("<div id='load'></div>");
            },
            success:function(data){
                if(data.status == 1){
                    var list = data.info;
                    if(list!=null){
                        $.each(list,function(k,v){
                            var content = v['status'] == 1 ? "<p class='comment-content'>"+replace_em(v['filter_content'])+"</p>" : "<p class='text-999'>该条评论已被管理员屏蔽</p>";
                            var html = '';
                            html += "<li class='ds-list'>"+
                                        "<div class='ds-post-self clearfix'>"+
                                        "<div class='ds-avatar pull-left'>"+
                                        "<img src='"+v['face']+"'>"+
                                        "</div>"+
                                        "<div class='ds-comment-body pull-right'>"+
                                        "<div class='jubao' commentid='"+v['id']+"'>"+
                                        "<div class='ds-comment-header'>"+
                                        "<span class='ds-user-name text-green'>"+v['nick_name']+"</span>"+
                                        "<em class='text-999'>"+v['create_time']+"</em>"+
                                        "<a href='javascript:void(0)' class='text-999 np-btn-report report jubao-"+v['id']+"'>举报</a>"+
                                        "</div>"+
                                        content+
                                        "<div class='ds-comment-footer'>"+
                                        "<a class='top-"+v['id']+" "+v['is_like']+"' top='"+v['id']+"' href='javascript:void(0);'>顶(<span>"+v['like_num']+"</span>)</a>"+
                                        "<a class='ds-post-reply' commentid='"+v['id']+"' top_id='"+v['id']+"' by_user='"+v['user_id']+"' href='javascript:void(0);'>回复(<em>"+v['cm_count']+"</em>)</a>"+
                                        "</div>"+
                                        "</div>";

                            if (v['cm_count'] > 0)
                            {
                                $.each(v['children'],function(k2,v2){
                                    var content2 = v2['status'] == 1 ? "<p>回复 <span class='text-green'>"+v2['by_username']+"</span>："+replace_em(v2['filter_content'])+"</p>" : "<p class='text-999'>该条评论已被管理员屏蔽</p>";
                                    var html2 = '';
                                        html2 += "<div class='reply-con'>"+
                                                "<div class='ds-post-self clearfix'>"+
                                                "<div class='ds-avatar pull-left'>"+
                                                "<img src='"+v2['face']+"'>"+
                                                "</div>"+
                                                "<div class='ds-comment-body pull-right'>"+
                                                "<div class='jubao' commentid='"+v2['id']+"'>"+
                                                "<div class='ds-comment-header'>"+
                                                "<span class='ds-user-name text-green'>"+v2['nick_name']+"</span>"+
                                                "<em class='text-999'>"+v2['create_time']+"</em>"+
                                                "<a href='javascript:void(0)' class='text-999 np-btn-report report jubao-"+v2['id']+"'>举报</a>"+
                                                "</div>"+
                                                content2+
                                                "<div class='ds-comment-footer'>"+
                                                "<a class='top-"+v2['id']+" "+v2['is_like']+"' top='"+v2['id']+"' href='javascript:void(0);'>顶(<span>"+v2['like_num']+"</span>)</a>"+
                                                "<a class='ds-post-reply' commentid='"+v2['id']+"' top_id='"+v['id']+"' by_user='"+v2['user_id']+"' href='javascript:void(0);'>回复</a>"+
                                                "</div>"+
                                                "</div>"+
                                                "</div>"+
                                                "</div>"+
                                                "</div>";

                                        html += html2;

                                });
                            }

                            html +=     "</div>"+
                                        "</div>"+
                                    "</li>";
                            $(".posts").append(html);
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
})