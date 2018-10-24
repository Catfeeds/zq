/**
 * Created by cytusc on 2018/7/13.
 */
$(function(){
    $(document).on('click', '.version li', function(e){
        var liNum = $(this).index();
        switch(liNum){
//            case 0:
//                $(this).addClass('on');
//                layer.open({
//                    content: '请先登录',
//                    btn: '确定',
//                    yes:function(index, layero){
//                        $(this).removeClass('on');
//                        window.location.href = "https://m.qqty.com/User/login.html";
//                    }
//                })
//                break;

            case 1:
                $(this).addClass('on');
                setTimeout(function(){
                    $(this).removeClass('on');
                    window.location.href = "//www.qqty.com/?from=m";
                }, 500);
                break;

            case 2:
                $(this).addClass('on');
                setTimeout(function(){
                    $(this).removeClass('on');
                    window.location.href = "http://a.app.qq.com/o/simple.jsp?pkgname=cn.qqw.app";
                }, 500);
                break;

            case 3:
                $('body').css('overflow','hidden');
                $('#pop-up,#hint').show();
                $(this).addClass('on');
                $('body').bind("touchmove",function(e){
                    e.preventDefault();
                });
                break;
        }

    });

    $('.bnt').click(function(e){
        $('body').css('overflow','inherit');
        $('.version li').eq(3).removeClass('on');
        $('#pop-up,#hint').hide();
    });

    $('.customer_button').click(function(){
        var url = $(this).attr('cust-url');
        window.open(url,'','width=610,height=760,left='+($(document).width() - 610 )/2+',top=100,resizable=yes,menubar=no,location=no,status=yes,scrollbars=yes');
        
        /*if(url.indexOf("&tel") == -1){
        	layer.open({
                content: '请先登录',
                btn: '确定',
                yes:function(index, layero){
                    window.location.href = 'https://m.qqty.com/User/login.html';
                }
            })
        }else{
        	
            window.open(url,'','width=610,height=760,left='+($(document).width() - 610 )/2+',top=100,resizable=yes,menubar=no,location=no,status=yes,scrollbars=yes');
        }*/
    });

});