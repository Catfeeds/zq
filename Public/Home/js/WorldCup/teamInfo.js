
/**
 * Created by cytusc on 2018/5/25.
 */
$('.navGoup a').on('click',function(){
    $('.navGoup a').removeClass('on');
    $('.navList div').css('display','none');
    $(this).addClass('on');
    var title = $(this).attr('title');
    $('.teamList'+title).css('display','block');
});