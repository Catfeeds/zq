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
            type: $('#type').val(),
        }
        $.ajax({
            type: 'post',
            url: "",
            data: params,
            dataType: 'json',
            success: function (data) {
                if (data.status == 1) {
                    var list = data.info;
                    if (list != null && list.length>0) {
                        $.each(list, function (k, v) {
                            var res = '';
                            var score='';
                            var play='';
                            var desc= '';
                            var coin='0';
                            switch (v.result)
                            {
                                case '0.5':
                                    res = '<div class="win_half"></div>';
                                    coin='+'+v.earn_point;
                                    break;
                                case '1':
                                    res = '<div class="win"></div>';
                                    coin='+'+v.earn_point;
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
                            if(v.play_type == 1)
                            {
                                play='<img src="'+img_path+'/myguess/rangq.png" alt="让球">';
                            }
                            else if (v.play_type == -1)
                            {
                                play='<img src="'+img_path+'/myguess/bsmall.png" alt="大小">';
                            }
                            else
                            {
                                play='<img src="'+img_path+'/myguess/jingcai_play.png" alt="竞彩">';
                            }
                            var betCodeHtml = v['play_type'] == 1 || v['play_type'] == -1 ? '' : '<em>'+v['bet_code']+' </em>';

                            var html ='<li>'+res+'<div class="p_1">'+
                                    '<div class="t_vs">'+betCodeHtml+'<em style="color: '+v.union_color+'">'+v['union_name'][0]+'</em> '+v['home_team_name'][0]+ score + v['away_team_name'][0]+'</div><div class="dtime">'+v.day+'  '+v.game_time+'</div>'+
                                    '</div><div class="g_detail"><div class="guess_type">'+play+'</div><div class="guess_right clearfix">'+
                                    '<p><img src="/Public/Mobile/images/myguess/recommend.png" alt="赞">';
                                if(v.play_type==1){
                                    if(v.chose_side==1){
                                        html+=v['home_team_name'][0];
                                    }else{
                                        html+=v['away_team_name'][0];
                                    }
                                    html+=' '+v.handcp+'('+v.odds+')'+'</p>';
                                }
                                else if (v.play_type == -1)
                                {
                                    html+= v.chose_side==1 ? '大球' : '小球';
                                    html+=' '+v.handcp+'('+v.odds+')'+'</p>';
                                }
                                else
                                {
                                    html+=v['home_team_name'][0];
                                    switch (v.chose_side)
                                    {
                                        case '1':  html+= ' ('+v.handcp+') '+'胜'+'('+v.odds+')'+'</p>';break;
                                        case '0':  html+= ' ('+v.handcp+') '+'平'+'('+v.odds+')'+'</p>';break;
                                        case '-1':  html+= ' ('+v.handcp+') '+'负'+'('+v.odds+')'+'</p>';break;

                                    }
                                }
                                if(v.desc==''){
                                        desc='暂无分析';
                                    }else{
                                        desc=v.desc;
                                    }
                                html+= '<p><span><img src="' + img_path + '/myguess/give.png" alt="投">'+v.vote_point+'</span><em><img src="'+img_path+'/myguess/fen.png" alt="分">'+coin+'</em></p>'+
                                      '<p><span><img src="'+img_path+'/myguess/gold.png" alt="币">'+v.tradeCoin+'</span><em><img src="'+img_path+'/myguess/buy.png" alt="购">'+v.tradeCount+'</em></p></div></div>'+
                                      '<div id="my_fenx"><span>分析：</span>'+desc+'</div></li>';
                                      $("#js-list li:last").after(html);
                        });
                        flag = false;
                    }else{
                        $('.load_gif').hide();
                        $('#showLess').show();
                        flag = true;
                    }
                    if(list.length<10){
                        $('.load_gif').hide();
                        $('#showLess').show();
                    }
                } else {
                    $('.load_gif').hide();
                    $('#showLess').show();
                    flag = true;
                }
            }
        });
        p++;
    }

    if($('.js-list').size() < 1)
    {
        $('.load_gif').hide();
        $('.load_gif').parent().append('<span class=" text-999 textContent">暂无数据！</span>');
    }
});