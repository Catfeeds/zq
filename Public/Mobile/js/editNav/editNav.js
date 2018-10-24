/**
 * Created by cytusc on 2018/4/8.
 */
var is_order = 1;
//判断是否配置过
if(!store('?navKey') || store('navKey') == undefined)
{
    is_order = 0;
}

var showKey = listArr = [];

$(function(){
    getList();

})

function getList()
{
    //获取导航栏列表
    $.ajax({
        url:'/Nav/getNavList.html',
        type:'get', //GET
        async:false,    //或false,是否异步
        data:{
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success:function(data){
            if(data.code == 200)
            {
                listArr = data.data;
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });

    //当第一次进入时默认全部显示
    if(is_order)
    {
        var changKey = JSON.parse(store('navKey'));
        var listTmp = [];
        //优化数据结构
        for(var i = 0;i<listArr.length;i++)
        {
            listTmp[listArr[i]['sign']] = listArr[i];
        }
        //将频道进行上下分类
        for(var i = 0;i<changKey.length;i++)
        {
            var k = changKey[i];
            if(listTmp[k])
            {
                var html = '<li class="" navUrl="'+listTmp[k]['url']+'" navId="'+listTmp[k]['sign']+'">'+
                    '<a>'+
                    '<span class="">'+listTmp[k]['name']+'</span>'+
                    '</a>'+
                    '</li>';
                $('.showArea').append(html);
            }

        }
        for(var i = 0;i<listArr.length;i++)
        {
            if(changKey.indexOf(listArr[i]['sign']) == -1)
            {
                var html = '<li class="" navUrl="'+listArr[i]['url']+'" navId="'+listArr[i]['sign']+'">'+
                    '<a>'+
                    '<span class="">'+listArr[i]['name']+'</span>'+
                    '</a>'+
                    '</li>';
                $('.hiddenArea').append(html);
            }
        }
        if($('.showArea').html() == '')
        {
            $('.hiddenArea').html('');
            creatNav(listArr);
        }
    }else{
        creatNav(listArr)
    }
    clickUrl();
}

//插入数据
function creatNav(listArr)
{
    for(var i = 0;i<listArr.length;i++)
    {
        var html = '<li class="" navUrl="'+listArr[i]['url']+'" navId="'+listArr[i]['sign']+'">'+
            '<a>'+
            '<span class="">'+listArr[i]['name']+'</span>'+
            '</a>'+
            '</li>';
        $('.showArea').append(html);
    }
}

//编辑点击按钮
var isEdit = 0;
$('.editButton').on('click',function(){
    console.log(222);
    $('li').unbind("click");
    if($(this).html() == '编辑')
    {
        $(this).html('完成');
        isEdit = 1;
        editMod();
    }else{
        $(this).html('编辑');
        isEdit = 0;
        doneMod();
    }
});

//将列表变成编辑模式
function editMod(){
    $('.showArea li').each(function(){
        var target = $(this);
        var name = target.find('span').html();
        if(name != '首页')
        {
            navLi(target);
        }
    });
    $('.cut').find('li').each(function(){
        var target = $(this);
        target.on('click',function(){
            var parents = target.parents('ul').hasClass('showArea');
            if(parents)
            {
                var _html = target;
                _html.removeClass();
                $(_html).find('span').eq(0).remove();
                $(_html).find('span').removeClass();
                $('.hiddenArea').append(_html);
            }else{
                var name = target.find('span').html();
                var _class = _classP = _classN = '';
                if(name.length == 2)
                {
                    _class = 'two';
                    _classP = 'move-left';
                    _classN = 'move-right';
                }else if(name.length == 3){
                    _class = 'three';
                }
                target.addClass(_class);
                target.find('span').addClass(_classN);
                var html = '<span class="del '+_classP+'"><img src="/Public/Mobile/images/index/del.png"></span>';
                target.find('a').prepend(html);
                $('.showArea').append(target);
            }
        });
    });

}

//li公用
function navLi(target)
{
    var name = target.find('span').html();
    var _class = _classP = _classN = '';
    if(name.length == 2)
    {
        _class = 'two';
        _classP = 'move-left';
        _classN = 'move-right';
    }else if(name.length == 3){
        _class = 'three';
    }
    target.addClass(_class);
    target.find('span').addClass(_classN);
    var html = '<span class="del '+_classP+'"><img src="/Public/Mobile/images/index/del.png"></span>';
    target.find('a').prepend(html);
}

//将列表变成完成模式
function doneMod()
{
    showKey = [];
    $('.showArea li').each(function(k,v){
        var target = $(this);
        showKey[k] = target.attr('navId');
        target.removeClass();
        $(target).find('.del').remove();
        $(target).find('span').removeClass();
    })
    var tmp = JSON.stringify(showKey)
    store.remove('navKey');
    store('navKey',tmp);
    clickUrl();
}

//点击跳转
function clickUrl()
{
    $('.cut').find('li').each(function(){
        var target = $(this);
        target.on('click',function(){
            var url = target.attr('navUrl');
            window.location.href=url;
        });
    });

}