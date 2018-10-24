/**
 * Created by cytusc on 2018/1/23.
 */

var is_img = false;
var is_content = true;
$(function () {
    GetUrlParam()
});
//足球篮球切换
$('.modal-title a').click(function (e) {
    var indexNun = $(this).index();
    $(this).addClass('cur').siblings().removeClass('cur');
    $('.modalTab .modal-body').eq(indexNun).show().siblings().hide();
});
function form_submit() {
    var title = $("#title").val().length;
    if (title < 1) {
        _alert('温馨提示', "请编写文章标题");
        return false;
    }
    if (title > 24) {
        _alert('温馨提示', "文章标题字数不能多于24个字");
        return false;
    }
    if (is_img) {
        var img = $("#viewUploadInput_77").val();
        if (img == '') {
            _alert('温馨提示', "请上传封面图片");

            return false;
        }
    }
    if (is_content) {
        var count = editor.getContentLength(true);
        if (count < 300) {
            _alert('温馨提示', "文章内容不应小于300个字");
            return false;
        }
        if(is_img)
        {
            if($('.class_nd').val() == null)
            {
                _alert('温馨提示', "请选择文章分类");
                return false;
            }
        }else{
            if($('.game_input').html() == '')
            {
                _alert('温馨提示',"请选择相关赛事");
                return false;
            }
            var chose_side = $("input[name='chose_side']").val();
            var chose_arr = new Array('1','0','-1');
            var play_type = $("input[name='play_type']").val();
            var play_arr = new Array('1','-1','2','-2');
            if(chose_side === undefined || play_type === undefined)
            {
                _alert('温馨提示',"请选择推荐玩法");
                return false;
            }
            if($.inArray(chose_side, chose_arr) == -1 || $.inArray(play_type, play_arr) == -1)
            {
                _alert('温馨提示',"参数错误");
                return false;
            }
            return isagree();
        }
    }else{
        var remark = $(".remark").val().length;
        if (remark < 1) {
            _alert('温馨提示', "请编写视频简介");
            return false;
        }
        var weburl = $("#weburl").val().length;
        if (weburl < 1) {
            _alert('温馨提示', "请填写视频地址");
            return false;
        }
        if(weburl < 6)
        {
            _alert('温馨提示', "请填写正确的视频地址");
            return false;
        }
        if(is_img)
        {
            if($('.class_rd').val() == null)
            {
                _alert('温馨提示', "请选择视频分类");
                return false;
            }
        }
    }
    var html = '<input disabled="disabled" type="submit" class="btn btn-orange" value="发布中">';
    $(".reg-btn").html(html);
}
$("#ensure").on("click",function(){
    $(".game_ya").css('display','none');
    $(".game_ji").css('display','none');
    var game_id = $('input:radio:checked').val();
    var game_info = $(".game_"+game_id).attr("value");
    var gtype = $(".game_"+game_id).attr("gtype");
    info=game_info.split("_");
    var game_title = $('input:radio:checked').next().text();
    $(".game_input").text(game_title);
    var team = game_title.split(" ");
    var _pan = '';
    if(gtype == 'bk')
    {
        _pan = info[7];
    }else{
        _pan = handCpSpread(info[7]);
    }
    if(info[7] != '' || info[10] != '')
    {
        var game_ya = '';
        if(info[7] != '') {
            game_ya += '<tr>' +
                '<td><strong>让球</strong></td>' +
                '<td class="selectOdd" value="1,1">' + team[0] + '(' + info[6] + ')</td>' +
                '<td class="">' + _pan + '</td>' +
                '<td class="selectOdd" value="1,-1">' + team[2] + '(' + info[8] + ')</td>' +
                '</tr>';
        }
        if(info[10] != '') {
            game_ya += '<tr>' +
                '<td><strong>大小</strong></td>' +
                '<td class="selectOdd" value="-1,1">大球(' + info[9] + ')</td>' +
                '<td class="">' + handCpTotal(info[10]) + '</td>' +
                '<td class="selectOdd" value="-1,-1">小球(' + info[11] + ')</td>' +
                '</tr>';
        }
        $("#game_ya").html(game_ya);
        $(".game_ya").css('display','block');
    }
    if(info[1] != '' || info[4] != '' )
    {
        var game_ji = '';
        if(info[1] != '')
        {
            game_ji += '<tr>'+
                '<td><strong>胜负平</strong></td>'+
                '<td class="selectOdd" value="2,1">'+info[0]+'</td>'+
                '<td class="selectOdd" value="2,0">'+info[1]+'</td>'+
                '<td class="selectOdd" value="2,-1">'+info[2]+'</td>'+
                '</tr>';
        }
        if(info[4] != '') {
            game_ji += '<tr>' +
                '<td><strong>让球（' + info[12] + '）</strong></td>' +
                '<td class="selectOdd" value="-2,1">' + info[3] + '</td>' +
                '<td class="selectOdd" value="-2,0">' + info[4] + '</td>' +
                '<td class="selectOdd" value="-2,-1">' + info[5] + '</td>' +
                '</tr>';
        }
        $("#game_ji").html(game_ji);
        $(".game_ji").css('display','block');
    }
    $(".selectOdd").on("click",function(){
        var _on = $(this);
        var game_type = _on.attr('value').split(",");
        $.ajax({
            url: "/UserInfo/is_gamble.html",
            type:'post',
            data:{'game_id':game_id,'game_type':game_type[0],'gtype':gtype},
            dataType: "json",
            success: function(data){
                if(data.status){
                    $(".selectOdd").removeClass("on");
                    _on.addClass("on").addClass('change_play');
                    var type_html = '<input type="hidden" name="play_type" value="'+game_type[0]+'"/><input type="hidden" name="chose_side" value="'+game_type[1]+'"/><input type="hidden" name="game_id" value="'+game_id+'"/><input type="hidden" name="gtype" value="'+gtype+'"/>';
                    $("#game_type").html(type_html);
                }else{
                    _alert('温馨提示',data.info);
                }
            }
        });
    });
});

//大小显示
function handCpTotal(score) {
    var num = Math.floor(score);
    var deci = score - num;

    if (deci == 0.25) {
        var score1 = num;
        var score2 = num + 0.5;
        return score1 + '/' + score2;
    }

    if (deci == 0.75) {
        var score1 = num + 0.5;
        var score2 = num + 1;
        return score1 + '/' + score2;
    }

    return parseFloat(score);
}
//让分中文显示
function handCpSpread(score) {
    if(score == '封'){
        return score;
    }
    if (score == '' || score == undefined) return '';
    var preTag = '';
    if (score.indexOf('-') >= 0) {
        preTag = "受";
        var score = score.split('-')[1];
    }

    if(score.indexOf('/') <= 0){
        score = parseFloat(score);
    }

    return preTag + sprScore[score];
}

$("#union li").on('click', function () {
    $("#union li").removeClass("on");
    $(this).addClass("on");
    var union_id = $(this).children('a').attr('union_id');
    if (union_id == 'all') {
        $("#game_list li").css('display', 'block');
    } else {
        $("#game_list li").each(function () {
            if ($(this).attr('union_id') == union_id) {
                $(this).css('display', 'block');
            } else {
                $(this).css('display', 'none');
            }
        });
    }
});
$('.class_st').on('change', function () {
    $('.class_nd').val('')
    $('.class_rd').val('')
    $('.class_nd option').css('display', 'none');
    $('.class_rd').removeClass('show').addClass('hidden');
    if ($(this).find('option:selected').val() == 'news') {
        is_content = true;
        $('.news_content').css('display', '');
        $('.weburl').css('display', 'none');
        $("option[type='1']").css('display', '');
    } else if ($(this).find('option:selected').val() == 'video') {
        is_content = false;
        $('.news_content').css('display', 'none');
        $('.weburl').css('display', '');
        $("option[type='2']").css('display', '');
        $('.class_rd').removeClass('hidden').css('display','inline-block');
        var pid = $("option[type='2']").val();
        $('.class_rd option').css('display', 'none');
        $("option[pid='" + pid + "']").css('display', '');
    }
})
$('.class_nd').on('change', function () {
    $('.class_rd').val('')
    var pid = $(this).find('option:selected').val();
    $('.class_rd option').css('display', 'none');
    $("option[pid='" + pid + "']").css('display', '');
})
function GetUrlParam() {
    var url = document.location.toString();
    if (url.indexOf("/type/2") == -1) {
        is_img = true;
    }
}
function isagree() {
    var res = true;
    var obj = $('#form').serialize();
    $.ajax({
        type: "POST",
        url: "/UserInfo/ajaxHaveOdda",
        data: obj,// 要提交的表单
        dataType: "json",
        async: false,
        success: function (msg) {
            if(msg.status == 201)
            {
                _alert('温馨提示',msg.msg);
                res = false;
            }
        },
        error: function (error) {
            _alert('温馨提示','提交失败,请稍候重试!!');
        }
    });
    return res;
}

$('.preview').on('click',function(){
    var title = $("#title").val().length;
    if (title < 1) {
        _alert('温馨提示', "请编写文章标题");
        return false;
    }
    var style = '<style>';
    style +=    '.left{ width: 690px;margin-left: 25px;}'+
                '.long h5{ font-size: 30px; line-height: 30px; height: 30px; margin: 20px 0px; white-space:nowrap; text-overflow:ellipsis; -o-text-overflow:ellipsis; overflow: hidden;}'+
                '.long span img{ width: 32px; height: 15px; border-radius: 5px;}'+
                '.cont{ width: 690px; margin-top: 123px; padding-bottom: 45px; margin: 30px 0;}'+
                '.cont .cont-ban{ width: 690px;}'+
                'tbody tr td:nth-child(1),td:nth-child(3){width: 70px;}'+
                'tbody tr td:nth-child(2),td:nth-child(4){width: 122px;}'+
                '.table{margin-bottom:0}'+
                '.table th{background:#f5f5f5;font:13px/22px "Helvetica Neue", Helvetica, Arial, sans-serif}'+
                '.on{background: #0094d5;color: #ffffff;}';
    style += '</style>';
    //生成时间
    var mydate = new Date();
    //生成标题与时间
    var title = '<div class="long testCss">'+
                    '<h5>'+$('#title').val()+'</h5>'+
                    '<span class="f12 text-80 mr5">'+mydate.getFullYear()+'-'+mydate.getMonth()+'-'+mydate.getDate()+' '+mydate.getHours()+':'+mydate.getMinutes()+':'+mydate.getSeconds()+'</span>'+
                    '<span class="f12 text-80 mr5">|</span>'+
                    '<span class="f12 text-80">作者: '+$('.userName a').html()+' </span>'+
                '</div>';
    var content = '';
    var content_js = '';
    if(is_img)
    {
        if(is_content)
        {
            content = '<div class="ban-w"><span>'+editor.getContent()+'</span></div>';
        }else{
            content = getvdeo();
        }
    }else{
        var content = '<div class="ban-w"><span>'+editor.getContent()+'</span></div>';
        var game_id = $('input:radio:checked').attr('value');
        if(game_id > 0)
        {
            var home_logo = $('input:radio:checked').attr('home_logo');
            var away_logo = $('input:radio:checked').attr('away_logo');
            var home_name = $('input:radio:checked').attr('home_name');
            var away_name = $('input:radio:checked').attr('away_name');
            var gtime = $('input:radio:checked').attr('gtime');
            var union_name = $('input:radio:checked').attr('union_name');
            var ya_url = 'javascript:void(0)';
            var ou_url = 'javascript:void(0)';
            var fen_url = 'javascript:void(0)';
            var gao_url = 'javascript:void(0)';
            if($('input:radio:checked').attr('gtype') != 'bk')
            {
                ya_url = '//bf.'+DOMAIN+'/ypOdds/game_id/'+game_id+'/sign/1.html';
                ou_url = '//bf.'+DOMAIN+'/eur_index/game_id/'+game_id+'.html';
                fen_url = '//bf.'+DOMAIN+'/dataFenxi/game_id/'+game_id+'.html';
                gao_url = '//bf.'+DOMAIN+'/gambleDetails/game_id/'+game_id+'.html';
            }
            var game_info = '<div class="ban">'+
                '<div class="matchBox clearfix">'+
                '<div class="pull-left matchLogo matchLogoL"><img class="homeLogo" data-original="'+home_logo+'" alt="'+home_name+'"></div>'+
                '<div class="pull-left matchIn">'+
                '<div class="matchTitle clearfix">'+
                '<div class="pull-left matchName" style=" background: #6ba7c6;">'+union_name+'<span class="triangleTopleft" style="border-top: 28px solid #6ba7c6;"></span></div>'+
                '<div class="pull-right matchTime">  '+gtime+'</div>'+
                '</div>'+
                '<div class="matchScore clearfix">'+
                '<div class="pull-left teamName text-r" title="'+home_name+'"><strong>'+home_name+'</strong>'+
                '</div>'+
                '<div class="pull-left teamTime">'+
                '<strong>'+
                '<span class="text-red">'+
                'VS'+
                '</span>'+
                '</strong>'+
                '</div>'+
                '<div class="pull-left teamName text-l" title="'+away_name+'"><strong>'+away_name+'</strong>'+
                '</div>'+
                '</div>'+
                '<div class="matchLink">'+
                '<a href="'+ya_url+'" target="_blank">亚赔</a>'+
                '<a href="'+ou_url+'" target="_blank">欧赔</a>'+
                '<a href="'+fen_url+'" target="_blank">分析</a>'+
                '<a href="'+gao_url+'" target="_blank">高手推荐</a>'+
                '</div>'+
                '</div>'+
                '<div class="pull-left matchLogo matchLogoR"><img class="awayLogo" data-original="'+away_logo+'" alt="'+away_name+'"></div>'+
                '</div>'+
                '</div>';
            content = game_info + content;
        }
        if($('.change_play').html() != undefined)
        {
            var play = $('.on').parents('.form-in').children('.input-con').html();
            content = content + play;
        }
    }
    var cont = '<div class="cont">'+
        '<div class="cont-ban">'+
        content +
        '</div>'+
        '</div>';
    var html = '<div class="left">' + style + title + cont+ '</div>';
    preview(html);
    $(".layui-layer-content").find("tbody tr").css("display","none");
    $(".on").parents("tr").css("display","");
    $("img.homeLogo").lazyload({
        placeholder: staticDomain+"/Public/Home/images/common/home_def.png",
        effect: "fadeIn",
        threshold: 150,
        failurelimit: 50
    });
    $("img.awayLogo").lazyload({
        placeholder: staticDomain+"/Public/Home/images/common/away_def.png",
        effect: "fadeIn",
        threshold: 150,
        failurelimit: 50
    });

})

function getvdeo()
{
    var url = $('#weburl').val();
    var url_html = '';
    if(url.indexOf(".swf") >= 0 || url.indexOf(".flv") >= 0 || url.indexOf(".mp4") >= 0 || url.indexOf(".m3u8") >= 0 || url.indexOf(".rtmp") >= 0) {
        url_html = '<embed allowfullscreen="true" allowscriptaccess="always" bgcolor="#000000" width="100%" height="500" id="ply" name="ply" quality="high" autostart="true" salign="lt" src="'+url+'" type="application/x-shockwave-flash" wmode="opaque">'+
            '</embed>';
    }else if(url.length > 5){
        url_html = '<div style="width: 100%; height: 500px; background: #000;">'+
            '<iframe width="100%" height="100%" scrolling="no" frameborder="0" src="'+url+'"></iframe>'+
            '</div>';
    }
    var content = url_html +
        '<div class="ban-w"><span>'+$('.remark').val()+'</span></div>';
    return content;
}

function getzx()
{
    var content = '<div class="ban-w"><span>'+editor.getContent()+'</span></div>';
    var cont = '<div class="cont">'+
        '<div class="cont-ban">'+
        content +
        '</div>'+
        '</div>';
    return cont;
}

function preview(html){
    //页面层
    layer.open({
        type: 1,
        skin: 'layui-layer-rim', //加上边框
        area: ['750px', '800px'], //宽高
        content: html
    });
}