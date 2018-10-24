$(function () {
    var p = 2;// 初始化页面，点击事件从第二页开始
    var flag = false;
    var img_path='/Public/Mobile/images';
    $(window).scroll(function () {
        //初始状态，如果没数据return ,false;否则
        if ($(document).height() - ($(document).scrollTop() + $(window).height()) <= 200) {
            send();
        }
    });
    function send() {
        if (flag) {
            return false;
        }
        flag = true;
        var params = {
            page: p,
            type: $('.nav_list').find('.on').attr('type'),
        }
        $.ajax({
            type: 'post',
            url: "/User/mybuy.html",
            data: params,
            dataType: 'json',
            beforeSend:function(){
                $(".load_gif").show();
            },
            success: function (data) {
                if (data.status == 1) {
                    var list = data.info;
                    if (list != null) {
                        $.each(list, function (k, v) {
                            var res = '';
                            var score='';
                            var desc= '';
                            switch (v['result'])
                            {
                                case '0':
                                    res = '';
                                    break;
                                case '0.5':
                                    res = '<div class="win_half"></div>';
                                    break;
                                case '1':
                                    res = '<div class="win"></div>';
                                    break;
                                case '-0.5':
                                    res = '<div class="lose_half"></div>';
                                    break;
                                case '-1':
                                    res = '<div class="lose"></div>';
                                    break;
                                case '2':
                                    res = '<div class="ping"></div>';
                                    break;
                            }
                            if(v.score == '' || v.score == null){
                                score='<span>VS</span> ';
                            }else{
                                score='<span style="color:red">'+v.score+'</span> ';
                            }
                            //等级
                            var lvHtml = v['play_type'] == 1 || v['play_type'] == -1 ? '<em class="lv lv'+v['lv']+'"></em>' : '<em class="lv jc_lv'+v['lv_bet']+'"></em>';

                            var html = '<li>'+res+'<div class="n_top clearfix"><div class="n_top_left">'+
                                            '<a href="//'+DOMAIN+'/Guess/other_page/user_id/'+v.user_id+'">' +
                                                '<img   class="lazy" data-original="' + v['face'] + '" src="'+IMAGES+'/index/headImg.png" alt="'+v.nick_name+'"><span>'+v.nick_name+'</span>' +lvHtml+
                                            '</a>' +
                                        '</div>' +
                                '<div class="n_top_right">';
                                    if(v.tenGambleRate>=6){
                                        html+='<em class="mingz">近10中'+v.tenGambleRate+'</em>';
                                    }
                                    if(v['curr_victs']>=2){
                                        html+='<em class="lians">'+v['curr_victs']+'连胜</em>';
                                    }
                                    var  bet_codeHtml = v['play_type'] == '1' || v['play_type'] == '-1' ? '' : '<em>'+v['bet_code']+' </em>';
                                    html+='</div></div><div class="p_1">'+
                                          '<div class="t_vs">'+bet_codeHtml+'<em style="color: '+v['union_color']+'">'+v['union_name']+'</em> '+v['home_team_name']+score;

                                    html+=v['away_team_name']+'</div>'+
                                            '<div class="etip">';
                                    if(v['tradeCoin']=='0'){
                                        html+='<span><img src="/Public/Mobile/images/guess/free.png" alt="免费"></span>';
                                    }else{
                                        html+='<span class="coins">'+v['tradeCoin']+'</span>';
                                    }
                                    html+='</div></div><p class="p_3">比赛时间：<span>'+v.day+'</span></p><p class="p_3">玩法：<span>';

                                    switch (v['play_type'])
                                    {
                                        case '1': html+='让球'; break;
                                        case '-1': html+='大小球'; break;
                                        case '-2': html+='竞彩'; break;
                                        case '2': html+='竞彩'; break;
                                    }
                                    // html+='</span></p><p class="p_4">竞猜：<span>'+v.Answer+'</span>&nbsp;&nbsp;'+v.handcp;
                                    //竞猜玩法
                                    gamblePlayHtml = v['play_type'] == 1 || v['play_type'] == -1
                                        ?
                                    '<p class="p_4">推荐：<span>'+v['Answer']+' '+v['handcp']+' </span><em>（'+v['odds']+'）</em></p>'
                                        :
                                    '<p class="p_4">推荐：<span>'+v['home_team_name']+' ('+v['handcp']+') '+v['Answer']+' </span><em>（'+v['odds']+'）</em></p>';
                                    html+= gamblePlayHtml;


                                    if(v.desc==''){
                                        desc='暂无分析';
                                    }else{
                                        desc=v.desc;
                                    }
                                    html+= '<p class="p_5 q-two">分析：<span>' + desc + '</span></p></li>';
                                    $("#js-list li:last").after(html);
                        });
                        flag = false;
                    }else{
                        $(".load_gif").hide();
                        $("#showLess").show();
                        flag = true;
                    }
                } else {
                    $(".load_gif").hide();
                    $("#showLess").show();
                    flag = true;
                }
            },
            complete:function(){
                $(".load_gif").hide();
                //头像懒加载
                lazyload();

            },
        });
        p++;
    }

});