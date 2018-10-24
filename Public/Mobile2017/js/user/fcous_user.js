$(function () {
    var p = 2;// 初始化页面，点击事件从第二页开始
    var flag = false;
    var img_path='/Public/Mobile/images';
    if ($(".list").size() < 10)
    {
        $(".load_gif").hide();
        $("#showLess").show();
    }
    $(window).scroll(function () {
        //初始状态，如果没数据return ,false;否则
        if ($(".list").size() <= 0)
        {
            return false;
        } else {
            if ($(document).height() - ($(document).scrollTop() + $(window).height()) <= 200) {
                send();
            }
        }
    });
    function send() {
        if (flag) {
            return false;
        }
        $('.load_gif').show();
        flag = true;
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


                            var html = '<tr class="list">' +
                                ' <td><a href="//'+DOAMIN+'/Guess/other_page/user_id/'+v.user_id+'/type/1.html"><img    class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png"  alt="'+v['nick_name']+'" ></a></td>' +
                                ' <td>' +
                                ' <a href="//' + DOAMIN + '/Guess/other_page/user_id/'+v.user_id+'/type/1.html">' +
                                ' <div>' +
                                ' <span class="q_name">'+v['nick_name']+'</span>' +
                                ' <em class="lv lv'+v['lv']+'"></em>' +
                                ' <em class="lv jc_lv'+v['lv_bet']+'"></em>' +
                                ' </div>' +
                                ' <p class="q-one"><span>'+v['descript']+'</span></p>' +
                                ' </a>' +
                                ' </td>' +
                                ' <td class="star_img_on" data-id="'+v['user_id']+'"></td>' +
                            ' </tr>';
                            $("#js-list tr:last").after(html);
                        });

                        flag = false;
                        $('.load_gif').hide();
                    }else{
                        $(".load_gif").hide();
                        $("#showLess").show();
                        flag = true;
                    }
                } else {
                    flag = false;
                }
            },
            complete:function () {
                //头像懒加载
                lazyload();
            }
        });
        p++;
    }

    $(document).on('click', '.star_img_on', function () {
        var $this=$(this);
        var id= $this.data('id');
        if(confirm("确定取消关注吗？") == true){
            $.ajax({
                type: 'post',
                url: "/User/cancel_fcous.html",
                data: {id:id},
                dataType: 'json',
                success: function (data) {
                    if(data.status==1){
                        $this.parent("tr").stop().fadeOut();
                    }else{
                        alert(data.info);
                    }
                }
            });
        }
    });

});