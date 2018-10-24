$(function(){
	$('.invitation-button').click(function(){
      $('.popup').css('display','block');
      $('.popup').css('text-align','center');
    
        var actId = $(this).attr('act_id');
        var pageType = $(this).attr('page_type');
        var sponsorId = $(this).attr('sponsor_id');
		// $.ajax({
        //     type: "POST",
        //     url: "/CupquizActivities/createImg.html",
        //     data: {
        //     	'pageType' : pageType, 
        //     	'actId' : actId,
        //     	'sponsor_id' : sponsorId,
        //     	'noBody' : 1
        //     },
            
        //     success: function(data){
        //         if(data.status){
        //             var html = data.data.html;
        //             $(".popup").empty();
        //             $(".popup").append(html);
        //         }
        //     }
        // });
        $.ajax({
            type: "POST",
            url: "/CupquizActivities/shareImg.html",
            data: {
            	'pageType' : pageType, 
            	'actId' : actId,
            	'sponsor_id' : sponsorId,
            	'noBody' : 1
            },
            
            success: function(data){
                
                if(data.status){
                    var url = data.url;
                    var html = '<img src="'+url+'" download="'+url+'" style="-webkit-user-select: none;-webkit-touch-callout:default;"/>';

                    html += '<div class="bot">'
                    	 + '<p class="protect" id="preview-window-button">长按或截图保存图片 分享给好友助力</p>' 
                    	// + '<a href="'+url+'" download="">点击去保存<img src="'+url+'" style="display:none;"/></a>'
                         + '<p class="close-btn" id="close-btn" onclick=\"closes();  return false;\">返回</p>'
                         + '</div>';
                     $(".popupChild").empty();
                     $(".popupChild").append(html);
                     $("body").css("overflow","hidden");
                     $(".wrapCon").css("padding","0,0,0,0");
                }
            }
        });
    });
  
});


function closes(){
  $('.popup').css('display','none');
}