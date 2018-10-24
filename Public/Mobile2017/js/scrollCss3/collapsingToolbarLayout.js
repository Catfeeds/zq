/**
 * 数据js样式文件
 * 
 * @Author Chensiren <245017279@qq.com>
 *
 * @since  2016-10-17
**/
 
$(function(){
	collapsingToolbar();
})

function collapsingToolbar(){
    //var coll_flag = true;
    //var scroll_flag = true;
    var windowHight = $(window).height()*1,//屏幕高度
        topHight = $('#other_header').outerHeight(true)*1,//顶部高度
        headerHight = $('.score').outerHeight(true)*1,//头部高度
        navHight = $('.n_module').outerHeight(true)*1,//tab高度
		scoreMainHeight = $('.score_main').outerHeight(true)*1,
		sc_scoreHight = $('.sc_score').height(),
        scorllHeight = 0,
        data_con_height = windowHight - headerHight - navHight-topHight,
        iframeHeight = 0;//iframe高度
        //headerHightEnd;

    $('.data_con').css({'height': data_con_height + 'px'});

    $('.data_con').scroll(function () {//滚动侦听
        if($('.score_main').hasClass('animateHome')){//是否中场或完场，重新计算高度
            headerHight = $('.score').outerHeight(true);
			data_con_height = windowHight - headerHight - navHight-topHight;
			$('.data_con').css({'height': data_con_height + 'px'});
        }
		
        scorllHeight = $('.data_con')[0].scrollHeight;

        var _targetTop = 5;
        var _partScrollTop = $('.data_con').scrollTop();
        //if(scroll_flag == true) {
        $('.data_con').find('div.iframe').each(function () {
            if ($(this).html() != '') {
                iframeHeight = $(this).height();
                if ((iframeHeight - data_con_height) > 0 && (iframeHeight - data_con_height) < 300) {
                    var _iframehight = iframeHeight + 20;
                    $(this).css({'height': _iframehight + 'px'});
                }
            }
        });
        //}
        if (_partScrollTop > _targetTop) {//向上滚动头部缩小;滚动条大于0且滚动条高度和页面高度差值大于100
			$('.data_con').css({'height': data_con_height + 'px'});
			$('.score_main').addClass('animateHome');
			$('.score_main').css({'height': sc_scoreHight+ 'px'});
        } else if (_partScrollTop >= 0 && _partScrollTop <= 5) {//向下滚动头部恢复
            /*$('.score').stop(true, false).animate({'height': headerHight + 'px'},100,'linear');
            setTimeout(function(){$(".score").removeAttr('style')}, 120);*/
			$('.data_con').css({'height': data_con_height + 'px'})
			$('.score_main').removeClass('animateHome');
			$('.score_main').css({'height': scoreMainHeight + 'px'});
        }
        //headerHightEnd = $('.score').outerHeight(true);
    });
}
