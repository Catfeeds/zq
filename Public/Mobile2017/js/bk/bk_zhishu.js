
$(function(){
    var begin = setInterval(function () {
        var refreshTime = localStorage.getItem("refreshTime") ? localStorage.getItem("refreshTime") : 5;
        refreshTime = parseInt(refreshTime + '000');
        if (typeof (refreshTime) == 'number' && (refreshTime >= 3000)) {
            bkaddsGoal(refreshTime);
        } else {
            bkaddsGoal(5000);
        }
        clearInterval(begin);
    }, 5000);
})
//赔率实时数据更新
function bkaddsGoal(time)
{
    $.ajax({
        type: 'post',
        url: "/Odds/bkgoal.html",
        dataType: 'json',
        success: function (data) {
            $.each(data.info, function (key, val) {
                if (key > 0)
                {
                    //主队让分赔率
                    if ($('#letHome'+key).html() != val[0])
                    {


                        if ($('#letHome'+key).html() > val[0])
                        {
                            $('#instantHome'+key).html(val[0]);//主队让分赔率
                            $('#instantHome'+key).removeClass('red');
                            $('#instantHome'+key).removeClass('green');
                            $('#instantHome'+key).addClass('green');
                        }
                        else
                        {
                            $('#instantHome'+key).html(val[0]);//主队让分赔率
                            $('#instantHome'+key).removeClass('red');
                            $('#instantHome'+key).removeClass('green');
                            $('#instantHome'+key).addClass('red');
                        }
                    }

                    //让分盘口
                    if ($('#letHands'+key).html() != val[1])
                    {


                        if ($('#letHands'+key).html() > val[1])
                        {
                            $('#instantHands'+key).html(val[1]);//让分盘口
                            $('#instantHands'+key).removeClass('red');
                            $('#instantHands'+key).removeClass('green');
                            $('#instantHands'+key).addClass('green');
                        }
                        else
                        {
                            $('#instantHands'+key).html(val[1]);//让分盘口
                            $('#instantHands'+key).removeClass('red');
                            $('#instantHands'+key).removeClass('green');
                            $('#instantHands'+key).addClass('red');
                        }
                    }

                    //客队让分赔率
                    if ($('#letAway'+key).html() != val[2])
                    {


                        if ($('#letAway'+key).html() > val[2])
                        {
                            $('#instantAway'+key).html(val[2]);//客队让分赔率
                            $('#instantAway'+key).removeClass('red');
                            $('#instantAway'+key).removeClass('green');
                            $('#instantAway'+key).addClass('green');
                        }
                        else
                        {
                            $('#instantAway'+key).html(val[2]);//客队让分赔率
                            $('#instantAway'+key).removeClass('red');
                            $('#instantAway'+key).removeClass('green');
                            $('#instantAway'+key).addClass('red');
                        }
                    }

                    //主队总分赔率
                    if ($('#totalHome'+key).html() != val[3])
                    {


                        if ($('#totalHome'+key).html() > val[3])
                        {
                            $('#instantTotalHome'+key).html(val[3]);//主队总分赔率
                            $('#instantTotalHome'+key).removeClass('red');
                            $('#instantTotalHome'+key).removeClass('green');
                            $('#instantTotalHome'+key).addClass('green');
                        }
                        else
                        {
                            $('#instantTotalHome'+key).html(val[3]);//主队总分赔率
                            $('#instantTotalHome'+key).removeClass('red');
                            $('#instantTotalHome'+key).removeClass('green');
                            $('#instantTotalHome'+key).addClass('red');
                        }
                    }

                    //总分赔率
                    if ($('#totalHands'+key).html() != val[4])
                    {


                        if ($('#totalHands'+key).html() > val[4])
                        {
                            $('#instantTotalHands'+key).html(val[4]);//总分赔率
                            $('#instantTotalHands'+key).removeClass('red');
                            $('#instantTotalHands'+key).removeClass('green');
                            $('#instantTotalHands'+key).addClass('green');
                        }
                        else
                        {
                            $('#instantTotalHands'+key).html(val[4]);//总分赔率
                            $('#instantTotalHands'+key).removeClass('red');
                            $('#instantTotalHands'+key).removeClass('green');
                            $('#instantTotalHands'+key).addClass('red');
                        }
                    }

                    //客队总分赔率
                    if ($('#totalAway'+key).html() != val[5])
                    {


                        if ($('#totalAway'+key).html() > val[5])
                        {
                            $('#instantTotalHands'+key).html(val[5]);//客队总分赔率
                            $('#instantTotalAway'+key).removeClass('red');
                            $('#instantTotalAway'+key).removeClass('green');
                            $('#instantTotalAway'+key).addClass('green');
                        }
                        else
                        {
                            $('#instantTotalHands'+key).html(val[5]);//客队总分赔率
                            $('#instantTotalAway'+key).removeClass('red');
                            $('#instantTotalAway'+key).removeClass('green');
                            $('#instantTotalAway'+key).addClass('red');
                        }
                    }
                }
            })
        }
    })
    setTimeout("bkaddsGoal(" + time + ")", time);
}