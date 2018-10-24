/**
 * Created by cytusc on 2018/4/9.
 */
$('.main').on('click',function(){
    if($('.videoLogo').css('display') == 'none')
    {
        $('.videoLogo').css('display','');
        $('.videoCont').css('display','none');
        $('video').trigger('pause');
    }else{
        $('.videoLogo').css('display','none');
        $('.videoCont').css('display','');
        $('video').trigger('play');
    }
});
$('.videoCont video').on('ended',function(){
    $('.videoLogo').css('display','');
    $('.videoCont').css('display','none');
});
$('.appUserIndex').on('click',function(){
    var a = $(this);
    window.location.href = 'user:' + a.attr('user') + ':'+ a.attr('is_expert');
});