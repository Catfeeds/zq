$(function () {
    //获取列表
    getList();
    var scrollTop=Cookie.getCookie('scrollTop');
    if(scrollTop){
        $("html, body").animate({scrollTop: scrollTop}, 500);
        Cookie.delCookie('scrollTop');
    }
});


//异步加载赛事列表
var oddType = 0;
function getList(){
    $.ajax({
        type: "get",
        url:'/Odds/getGameList.html',
        cache: false,
        success: function (data) {
            console.log(data);
            if(data.status == 1){
                oddType =  data['oddsType'];
                var html = handleGame(data.data);
                $('.gameLive').append(html);
                $('#rankListMore').css('display','none');
            }
        }
    });
}

//处理赛事列表
function handleGame(data){
    var html = '';
    $.each(data,function(key,val){
        var tmp = _title = _url = '';
        $.each(val[oddType],function(k,v){
            switch(oddType){
                case 8:
                    _title = '<th>主队</th> <th>让球</th> <th>客队</th>';
                    break;
                case 9:
                    _title = '<th>主胜</th> <th>平局</th> <th>客胜</th>';
                    break;
                case 10:
                    _title = '<th>大球</th> <th>大小</th> <th>小球</th>';
                    break;
                default:
                    _title = '<th>主队</th> <th>让球</th> <th>客队</th>';
            }
            if(k == 1){
                var _time = val[6];
                if(cookieUa == '_f'){
                    _url = 'javascript:void(0);';
                }else{
                    _url = '/Details/odds_asia/scheid/'+val[0]+'.html';
                }
                tmp = '<table id="scheid'+val[0]+'" class="zhishu js-data" border="0" cellpadding="0" cellspacing="0" data-scheid="'+val[0]+'">' +
                    '<tbody>' +
                    '<div class="zhishu_title js-detail" data-url="'+_url+'">' +
                    '<p><em style="background: '+val[3]+'"></em><span style="color:'+val[3]+'">'+val[2][0]+'</span>  <span>'+_time.substr(4,2)+'-'+_time.substr(6,2)+'  '+_time.substr(8,2)+':'+_time.substr(10,2)+'</span></p><p>'+val[4][0]+'<span>VS</span>'+val[5][0]+'</p>' +
                    '</div>' +
                    '<tr>' +
                    '<th>公司' +
                    '</th>' +
                    '<th>初/即</th>' +
                    _title+
                    '</th>' +
                    '</tr>';
            }
            tmp = tmp+
                '<tr class="js-jipan compyid'+v[0]+'"  data-compyid="'+v[0]+'">' +
                '<td><span>'+v[1]+'</span></td>' +
                '<td><div class="bottom_line">初盘</div><div class="red">即盘</div></td>' +
                '<td><div class="js-home-o bottom_line">'+v[2]+'</div><div class="js-home '+daxiao(v[5],v[2])+'">'+v[5]+'</div></td>' +
                '<td><div class="js-all-o bottom_line">'+v[3]+'</div><div class="js-all '+daxiao(v['res_now'],v['res_start'])+'">'+v[6]+'</div></td>' +
                '<td><div class="js-away-o bottom_line">'+v[4]+'</div><div class="js-away '+daxiao(v[7],v[4])+'">'+v[7]+'</div></td>' +
                '</tr>';
        });
        tmp = tmp +
            '</tbody>' +
            '</table>';
        html = html + tmp;
    });
    return html;
}
//比较大小返回class
function daxiao(one,two){
    if(one > two)
    {
        return 'red';
    }else if(one < two){
        return 'green';
    }
}