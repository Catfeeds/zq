$(function () {
    var refreshTime = localStorage.getItem("refreshTime") ? localStorage.getItem("refreshTime") : 5;
    refreshTime = parseInt(refreshTime + '000');
    if (typeof (refreshTime) == 'number' && (refreshTime >= 3000)) {
        odds(refreshTime);
    } else {
        odds(5000);
    }
});
//赔率实时数据更新
function odds(time)
{
    $.ajax({
        type: 'post',
        url: "/Odds/goal.html",
        dataType: 'json',
        success: function (data) {
            if (data['status'] == 1) {
                $.each(data.info, function (i, j) {
                    var $scheid = $('#scheid' + i);
                    if ($scheid.length == 1) {
                        if ($scheid.data('scheid') == i) {
                            $.each(j,function(k,val){
                                var $this=$scheid.find('.compyid'+k);
                                if($this.length==1){
                                    if ($this.find('.js-home').html() != val[2]) {
                                        var original = parseFloat($this.find('.js-home-o').html());
                                        var compare = parseFloat(val[2]);
                                        if (original > compare) {
                                            $this.find('.js-home').removeClass('red').addClass('green').html(val[2]);
                                        } else if (original < compare) {
                                            $this.find('.js-home').removeClass('green').addClass('red').html(val[2]);
                                        } else {
                                            $this.find('.js-home').removeClass('green').removeClass('red').html(val[2]);
                                        }
                                    }
                                    if ($this.find('.js-all').html() != val[3]) {
                                        var all_star = $this.find('.js-all-o').html();
                                        var t1 = '';
                                        var t2 = '';
                                        if (val[3].indexOf('/') == '-1') {
                                            t2 = val[3];
                                        } else {
                                            var arr_now = val[3].split("/");
                                            var t2 = (arr_now[0] + arr_now[1]) / 2;
                                        }
                                        if (all_star.indexOf('/') == '-1') {
                                            t1 = all_star;
                                        } else {
                                            var arr_star = all_star.split("/");
                                            var t1 = (arr_star[0] + arr_star[1]) / 2;
                                        }
                                        if (parseFloat(t1) > parseFloat(t2)) {
                                            $this.find('.js-all').removeClass('red').addClass('green').html(val[3]);
                                        } else if (parseFloat(t1) < parseFloat(t2)) {
                                            $this.find('.js-all').removeClass('green').addClass('red').html(val[3]);
                                        } else {
                                            $this.find('.js-all').removeClass('green').removeClass('red').html(val[3]);
                                        }
                                    }
                                    if ($this.find('.js-away').html() != val[4]) {
                                        var original = parseFloat($this.find('.js-away-o').html());
                                        var compare = parseFloat(val[4]);
                                        if (original > compare) {
                                            $this.find('.js-away').removeClass('red').addClass('green').html(val[4]);
                                        } else if (original < compare) {
                                            $this.find('.js-away').removeClass('green').addClass('red').html(val[4]);
                                        } else {
                                            $this.find('.js-away').removeClass('green').removeClass('red').html(val[4]);
                                        }
                                    }
                                }
                            });
                            
                                
                        }
                    }
                });
            }
        }
    });
    setTimeout("odds(" + time + ")", time);
}