$(function(){
    duiwang();
    jin("jin_home");
    jin("jin_away");
    bifaOdds();
    explodeNearSixGame();
});
$("#columnsNav a").click(function () {
    $("html, body").animate({scrollTop: $($(this).attr("href")).offset().top -20+ "px"}, 500);
    return false;//不要这句会有点卡顿
});
$(".tds").on('click',function(){
    $(this).parent().parent().css('display','none');
    $(this).parent().parent().next().css('display','none');
});
$(".tdss").on("click",function(){
    $(".odds tr").css('display','');
});

var type = 1;

$(".checkes").on('click',function(){
    var _check = $(this);
    var is_check = $(this).attr('is_chick');
    var tbody = _check.parent().attr('value');
    if(is_check == 'no_chick')
    {
        var _chick_type = _check.parent().children().eq(0).is(':checked');
        if(_chick_type)
        {
            _check.parent().children().eq(0).prop("checked",false);
        }else{
            _check.parent().children().eq(0).prop("checked",true);
        }
    }
    var _parent = $(this).parents('tr');
    var checktype = _parent.attr('checktype');
    //将勾选过的联赛名存入数组用作筛选
    var union_arr = new Array();
    $("."+checktype+" div").each(function(){
        var union_check = $(this);
        if(union_check.children(0).is(':checked'))
        {
            union_arr.push(union_check.children(1).text());
        }
    });
    //下拉选项框所选数量
    var select_num = _parent.children('td').children('.fl').children('select').children('option:selected').val();
    var _away = _parent.children('td').children('.fl').eq(1).attr('away');
    //用于主客相同的判断
    var _num = '';
    if(_away == 'away')
    {
        _num = 4;
    }else{
        _num = 2;
    }
    var is_zhuke = _parent.children('td').children('.fl').eq(1).children().is(':checked');
    var _zhuke = '';
    if(is_zhuke)
    {
        _zhuke = true;
    }else{
        _zhuke = false;
    }

    //根据联赛勾选进行处理
    $("."+tbody+" tr").each(function(){
        var _tr = $(this);
        if(_tr.hasClass('games'))
        {
            var _td = _tr.children().eq(0).text();
            var in_arr = $.inArray(_td, union_arr);
            if(in_arr == -1)
            {
                _tr.css("display","none");
            }else{
                _tr.css("display","");
            }
        }
    });

    //勾选了同主客进行处理
    if(_zhuke)
    {
        //根据联赛勾选进行处理
        $("."+tbody+" tr").each(function(){
            var _tr = $(this);
            if(_tr.hasClass('games') && _tr.css('display') != 'none')
            {
                var _td = _tr.children().eq(_num);
                if(!_td.hasClass("text-red"))
                {
                    _tr.css("display","none");
                }
            }
        });
    }


    //根据下拉框赛事数量进行处理
    var temp_num = 0;
    $("."+tbody+" tr").each(function(){
        var _tr = $(this);
        if(_tr.hasClass('games') && _tr.css('display') != 'none')
        {
            if(temp_num < select_num)
            {
                temp_num++;
            }else{
                _tr.css("display","none");
            }
        }
    });

    tbodydata(tbody);
});


$('#compare').change(function(){
    var _num=$(this).children('option:selected').val();//这就是selected的值
    $(".select_data").each(function(){
        var _tr = $(this);
        if(_tr.attr('type') == 'tr')
        {
            if(_tr.attr('value') == (parseInt(_num) + 1))
            {
                _tr.css('display','');
            }else{
                _tr.css('display','none');
            }
        }
    });
});


// rightPercent(80);
function circlePercent(circleClass, num){
    if(num>100)return;
    $(circleClass + " span").html(num);
    num=num*3.6;
    if(num<=180){
        $(circleClass + " .pieRightIn").css({"transform":"rotate(" + num + "deg)"});
    }else{
        $(circleClass + " .pieRightIn").css({"transform":"rotate(180deg)"});
        $(circleClass + " .pieLeftIn").css({"transform":"rotate(" + (num - 180) + "deg)"});
    }
}

function leftPercent2(num){
    if(num>100)return;
    $(".mask2Left span").html(num);
    num=num*3.6;
    if(num<=180){
        $(".circleLeft2 .pieRightIn2").css({"transform":"rotate(" + num + "deg)"});
    }else{
        $(".circleLeft2 .pieRightIn2").css({"transform":"rotate(180deg)"});
        $(".circleLeft2 .pieLeftIn2").css({"transform":"rotate(" + (num - 180) + "deg)"});
    }
}

function rightPercent2(num){
    if(num>100)return;
    $(".mask2Right span").html(num);
    num=num*3.6;
    if(num<=180){
        $(".circleRight2 .pieRightIn2").css({"transform":"rotate(" + num + "deg)"});
    }else{
        $(".circleRight2 .pieRightIn2").css({"transform":"rotate(180deg)"});
        $(".circleRight2 .pieLeftIn2").css({"transform":"rotate(" + (num - 180) + "deg)"});
    }
}

function leftPercent3(num){
    if(num>100)return;
    $(".mask3Left span").html(num);
    num=num*3.6;
    if(num<=180){
        $(".circleLeft3 .pieRightIn3").css({"transform":"rotate(" + num + "deg)"});
    }else{
        $(".circleLeft3 .pieRightIn3").css({"transform":"rotate(180deg)"});
        $(".circleLeft3 .pieLeftIn3").css({"transform":"rotate(" + (num - 180) + "deg)"});
    }
}

function rightPercent3(num){
    if(num>100)return;
    $(".mask3Right span").html(num);
    num=num*3.6;
    if(num<=180){
        $(".circleRight3 .pieRightIn3").css({"transform":"rotate(" + num + "deg)"});
    }else{
        $(".circleRight3 .pieRightIn3").css({"transform":"rotate(180deg)"});
        $(".circleRight3 .pieLeftIn3").css({"transform":"rotate(" + (num - 180) + "deg)"});
    }
}

function duiwang()
{
    var _shen = 0;
    var _pin = 0;
    var _fu = 0;
    var _pan = 0;
    var _all = 0;
    var win_per = 0;
    var pin_per = 0;
    var fu_per = 0;
    var win_pan_per = 0;
    $(".duiwang tr").each(function(){
        var _tr = $(this);
        if(_tr.css('display') != 'none')
        {
            if(_tr.children().eq(5).html() == '赢')
            {
                _shen++;
            }
            if(_tr.children().eq(5).html() == '平')
            {
                _pin++;
            }
            if(_tr.children().eq(5).html() == '输')
            {
                _fu++;
            }
            if(_tr.children().eq(10).html() == '赢')
            {
                _pan++;
            }
        }
    });
    _all = _shen + _pin + _fu;
    pin_per = _pin / _all * 100;
    fu_per = _fu / _all * 100;
    if(_pan != 0){
        win_pan_per = _pan / _all * 100;
    }
    $(".text-c").html("近"+_all+"场交战");
    if(_shen == 0)
    {
        $(".text-c").next().children().eq(0).css('width','0');
    }else{
        win_per = _shen / _all * 100;
        $(".text-c").next().children().eq(0).css('width',win_per+'%');
        $(".text-c").next().children().eq(0).html(_shen+"胜");
    }
    if(_pin == 0)
    {
        $(".text-c").next().children().eq(1).css('width','0');
    }else{
        $(".text-c").next().children().eq(1).css('width',pin_per+'%');
        $(".text-c").next().children().eq(1).html(_pin+"平");
    }
    if(_fu == 0)
    {
        $(".text-c").next().children().eq(2).css('width','0');
    }else{
        $(".text-c").next().children().eq(2).css('width',fu_per+'%');
        $(".text-c").next().children().eq(2).html(_fu+"负");
    }
    circlePercent(".circleLeft", parseInt(win_per));
    circlePercent(".circleRight", parseInt(win_pan_per));
}
function jin(_class)
{
    var _shen = 0;
    var _pin = 0;
    var _fu = 0;
    var _pan = 0;
    var _all = 0;
    var win_per = 0;
    var win_pan_per = 0;
    $("."+_class+" tr").each(function(){
        var _tr = $(this);
        if(_tr.css('display') != 'none')
        {
            if(_tr.children().eq(5).html() == '赢')
            {
                _shen++;
            }
            if(_tr.children().eq(5).html() == '平')
            {
                _pin++;
            }
            if(_tr.children().eq(5).html() == '输')
            {
                _fu++;
            }
            if(_tr.children().eq(10).html() == '赢')
            {
                _pan++;
            }
        }
    });
    _all = _shen + _pin + _fu;
    if(_shen != 0)
    {
        win_per = _shen / _all * 100;
    }
    if(_pan != 0)
    {
        win_pan_per = _pan / _all * 100;
    }

    $("."+_class+"_data").children().eq(0).html("&nbsp;"+_shen+"&nbsp;");
    $("."+_class+"_data").children().eq(1).html("&nbsp;"+_pin+"&nbsp;");
    $("."+_class+"_data").children().eq(2).html("&nbsp;"+_fu+"&nbsp;");
    if(_class == 'jin_home')
    {
        leftPercent2(parseInt(win_pan_per));//左边蓝圈
        leftPercent3(parseInt(win_per));//左边红圈
    }else if(_class == 'jin_away'){
        rightPercent3(parseInt(win_per));//右边红圈
        rightPercent2(parseInt(win_pan_per));//右边篮圈
    }
}
function tbodydata(tbody)
{

    if(tbody == 'duiwang')
    {
        duiwang();
    }else if(tbody == 'jin_home'){
        jin("jin_home");
    }else if(tbody == 'jin_away'){
        jin("jin_away");
    }
}

//比分页面必发指数js点击效果
$("select#bifaOdds").change(function(){
    bifaOdds();
});

function bifaOdds()
{
    var str = $("select#bifaOdds").find("option:selected").attr("info");
    if(str !== undefined)
    {
        var strs= new Array(); //定义一数组
        strs=str.split(","); //字符分割
        console.log(strs[0]);
        $(".1-1").html(strs[0]);
        $(".1-2").html(strs[3] + '%');
        $(".1-3").html(strs[6] + '%');
        $(".2-1").html(strs[1]);
        $(".2-2").html(strs[4] + '%');
        $(".3-1").html(strs[2]);
        $(".3-2").html(strs[5] + '%');
    }

}

// 联赛盘路走势近6场颜色
function explodeNearSixGame() {
    let allDom = $(".nearSix");
    for (let i = 0; i < allDom.length; i++) {
        let string = $(allDom[i]).html();
        string = string.replace(/,/g, " ");
        string = string.replace(/赢/g, '<span class="text-red">赢</span>');
        string = string.replace(/输/g, '<span class="text-green">输</span>');
        string = string.replace(/走/g, '<span class="text-blue">走</span>');
        string = string.replace(/小/g, '<span class="text-green">小</span>');
        string = string.replace(/大/g, '<span class="text-red">大</span>');
        $(allDom[i]).html(string);
    }
    
}
