/**
 * Created by cytusc on 2018/5/25.
 */

$(function(){
    if(store('scheduleData') != undefined)
    {
        getVote(-1);
    }
});

//获取投票数据
function getVote(id)
{
    $.ajax({
        url:'/getVote.html',
        type:'post', //GET
        async:false,    //或false,是否异步
        data:{
            id:id,
        },
        timeout:5000,    //超时时间
        dataType:'json',    //返回的数据格式：json/xml/html/script/jsonp/text
        success:function(data){
            for(var i=0;i<8;i++)
            {
                $('.vote'+i+' p').css('height',data[i][1]+'%');
                $('.vote'+i+' p').find('span').html(data[i][0]);
            }
        },
        error:function(xhr,textStatus){
        },
        complete:function(){
        }
    });

}

$('.but').on('click',function(){
    if(store('scheduleData') != undefined)
    {
        _alert('温馨提示','你已经投票过了');
    }else{
        var id = $(this).attr('num')
        getVote(id);
        store('scheduleData','is_vote')
    }

})