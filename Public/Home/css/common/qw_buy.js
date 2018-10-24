/**
 * Created by Liangzk on 2017/4/21.
 * 球王查看公共jS
 */

function qwBuyModal(obj, productId,sign) {

    $.ajax({
        type: "POST",
        url: "/Common/qwBuyCheck.html",
        data: {'productId': productId},
        dataType: "json",
        success: function (data) {
            if (data.status == 1)
            {
                var $modalHtml = '<div class="modal-scrollable" style="z-index: 1050;">' +
                    ' <div class="modal fade bs-example-modal-sm in" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="false" style="display: block; margin-top: -99.5px;top: 45%;">' +
                    ' <div class="modal-content">' +
                    ' <div class="modal-header" style="text-align:center;">' +
                    ' <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"></span></button>' +
                    ' <h4 class="modal-title" style="color: #000000;">温馨提示</h4>' +
                    ' </div>' +
                    ' <div class="modal-body" style="padding: 20px 50px;">' +
                    ' <dl class="clearfix text-center">' +
                    ' <dt style="padding-bottom: 10px; font-weight: normal; font-size: 16px; margin-bottom: 10px;">' +
                    ' 查看该服务需要花费 <span class="text-red">' + data.saleCoin + '</span> 金币' +
                    ' </dt>' +
                    ' <div class="btn-con" style="text-align: center; margin-top: 15px;">' +
                    ' <button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-orange" onclick="setQwBuy('+productId+','+sign+')"> 确定 </button>' +
                    ' <button style="width: 100px; border-radius: 3px;" type="button"  class="btn btn-default" data-dismiss="modal" onclick="$(\'.modal-scrollable\').addClass(\'hidden\')">取消</button>' +
                    ' </div>' +
                    ' </div>' +
                    ' </div>' +
                    ' </div>' +
                    ' <div class="modal-backdrop fade in" style="z-index: 1040;"></div>' +
                    ' </div>';
                $('body').append($modalHtml);
            }
            else
            {

                if (data.errorCode == 8009)
                {
                    var $modalHtml = '<div class="modal-scrollable" style="z-index: 1050;">' +
                        ' <div class="modal fade bs-example-modal-sm in" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="false" style="display: block; margin-top: -99.5px;top: 45%;">' +
                        ' <div class="modal-content">' +
                        ' <div class="modal-header" style="text-align:center;color: #000000;">' +
                        ' <button  type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"></span></button>' +
                        ' <h4 class="modal-title" style="color: #000000;">温馨提示</h4>' +
                        ' </div>' +
                        ' <div class="modal-body" style="padding: 20px 50px;">' +
                        ' <dl class="clearfix text-center">' +
                        ' <dt style="padding-bottom: 10px; border-bottom: 1px solid #e5e5e5; font-weight: normal; font-size: 16px; margin-bottom: 10px;">' +
                        ' 查看该服务需要花费 <span class="text-red">' + data.saleCoin + '</span> 金币' +
                        ' </dt>' +
                        ' <dd>您的余额不足，请充值！</dd></dl>' +
                        ' <div class="btn-con" style="text-align: center; margin-top: 15px;">' +
                        ' <button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-orange"  onclick="window.open(\'//www.' + DOMAIN + '/UserAccount/charge.html\')">马上去' +
                        ' </button>' +
                        ' <button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-default" data-dismiss="modal" onclick="$(\'.modal-scrollable\').addClass(\'hidden\')">再逛逛</button>' +
                        ' </div>' +
                        ' </div>' +
                        ' </div>' +
                        ' </div>' +
                        ' <div class="modal-backdrop fade in" style="z-index: 1040;"></div>' +
                        ' </div>'
                    $('body').append($modalHtml);
                }
                else if (data.errorCode == 1111)
                {
                    var $modalHtml = '<div class="modal-scrollable" style="z-index: 1050;">' +
                        ' <div class="modal fade bs-example-modal-sm in" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="false" style="display: block; margin-top: -99.5px;top: 45%;">' +
                        ' <div class="modal-content">' +
                        ' <div class="modal-header" style="text-align:center;color: #000000;">' +
                        ' <button  type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true"></span></button>' +
                        ' <h4 class="modal-title" style="color: #000000;">温馨提示</h4>' +
                        ' </div>' +
                        ' <div class="modal-body" style="padding: 20px 50px;">' +
                        ' <dl class="clearfix text-center">' +
                        ' <dt style="padding-bottom: 10px; border-bottom: 1px solid #e5e5e5; font-weight: normal; font-size: 16px; margin-bottom: 10px;">' +
                        ' 您还未登入，是否登入？ ' +
                        ' </dt>' +
                        '</dl>' +
                        ' <div class="btn-con" style="text-align: center; margin-top: 15px;">' +
                        ' <button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-orange"  onclick="window.open(\'//www.' + DOMAIN + '/User/login.html\')">马上去' +
                        ' </button>' +
                        ' <button style="width: 100px; border-radius: 3px;" type="button" class="btn btn-default" data-dismiss="modal" onclick="$(\'.modal-scrollable\').addClass(\'hidden\')">再逛逛</button>' +
                        ' </div>' +
                        ' </div>' +
                        ' </div>' +
                        ' </div>' +
                        ' <div class="modal-backdrop fade in" style="z-index: 1040;"></div>' +
                        ' </div>'
                    $('body').append($modalHtml);
                }
                else 
                {
                    showMsg(data.msg, false, 'error');
                }

            }
        }
    });

}

function setQwBuy(productId,sign)
{
    $('.modal-scrollable').addClass('hidden');
    $.ajax({
        type: "POST",
        url: "/Common/setQwBuy.html",
        data: {'productId': productId},
        dataType: "json",
        success: function (data) {
            if (data.status == 1)
            {
                var res = data.res;
                if (res != null)
                {
                    if (res.status == true)
                    {

                        switch (sign)
                        {
                            //我的关注--球王购买
                            case 1111:

                                var list = data.introGamble;
                                var gameHtml = '';
                                if(list != null)
                                {
                                    $.each(list,function (key,value) {

                                        var scoreHtml = value['score'] ? value['score'] : 'VS';

                                        var playTypeHtml = '';
                                        switch (value['play_type'])
                                        {
                                            case '1': playTypeHtml = '让球'; break;
                                            case '-1': playTypeHtml = '大小'; break;
                                        }

                                        var resulthtml = '';
                                        if (value['result'] == '1' || value['result'] == '0.5')
                                        {
                                            resulthtml = '<img src="/Public/Home/images/qw/win.png" alt="">';
                                        }
                                        if (value['result'] == '-1' || value['result'] == '-0.5')
                                        {
                                            resulthtml = '<img src="/Public/Home/images/qw/lose.png" alt="">';
                                        }
                                        if (value['result'].indexOf("-10,-11,-12,-13,-14") != '-1')
                                        {
                                            resulthtml = '<img src="/Public/Home/images/qw/zou.png" alt="">';
                                        }


                                        gameHtml += '<tr>' +
                                            ' <td>'+value['gtime']+'</td>' +
                                            ' <td>'+value['union_name']+'</td>' +
                                            ' <td class="game_vs">'+value['home_team_name']+'<span> '+scoreHtml+' </span>'+value['away_team_name']+'</td>' +
                                            ' <td>'+playTypeHtml+'</td>' +
                                            ' <td>'+value['handcp']+'(<font>'+value['odds']+'</font>)</td>' +
                                            ' <td class="g_result">'+value['Answer']+'('+value['odds']+')'+resulthtml+'</td>' +
                                            ' </tr>';

                                    });

                                }
                                else
                                {
                                    gameHtml = ' <tr> <td colspan = "6" class="wait"><img src="/Public/Home/images/qw/wait.png" alt="">敬请期待 </td> </tr>';
                                }
                                var html = '<div class="put_game">' +
                                    ' <table class="table tb_put_game">' +
                                    ' <tbody>' +
                                    ' <tr class="tr_border"><th>开赛时间</th><th>赛事</th> <th>对阵比赛</th><th>玩法</th> <th>盘口/赔率</th><th>推介</th> </tr>'
                                    +gameHtml +
                                    ' </tbody>' +
                                    ' </table>' +
                                    ' </div>';

                                $('#list'+productId).parent().append(html);
                                $('#list'+productId).remove();

                                $typeHtml = $('#type'+productId).html();//判断是否发布

                                if($typeHtml.indexOf('已发布') != -1)
                                {
                                    _alert('温馨提醒','购买成功');
                                }
                                else
                                {
                                    _alert('温馨提醒','订购成功');
                                }
                                break;
                                //球王详情--球王购买
                                case 2:
                                    location.reload();
                                break;
                        }


                    }
                    else
                    {
                        showMsg(res.msg, false, 'error');
                    }
                }
            }
            else
            {
                showMsg('请求失败！！', false, 'error');
            }
        }
    });

}
