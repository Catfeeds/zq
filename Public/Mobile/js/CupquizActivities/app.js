$(function () {

	// 用于在IE6和IE7浏览器中，支持Element.querySelectorAll方法
	var qsaWorker = (function () {
	    var idAllocator = 10000;

	    function qsaWorkerShim(element, selector) {
	        var needsID = element.id === "";
	        if (needsID) {
	            ++idAllocator;
	            element.id = "__qsa" + idAllocator;
	        }
	        try {
	            return document.querySelectorAll("#" + element.id + " " + selector);
	        }
	        finally {
	            if (needsID) {
	                element.id = "";
	            }
	        }
	    }

	    function qsaWorkerWrap(element, selector) {
	        return element.querySelectorAll(selector);
	    }

	    // Return the one this browser wants to use
	    return document.createElement('div').querySelectorAll ? qsaWorkerWrap : qsaWorkerShim;
	})();
	
	function createPng() {
		
//        html2canvas(document.querySelector('#preview-window')).then(function (canvas) {
       	html2canvas(qsaWorker(document, '#preview-window')).then(function (canvas) {
            $('#png-content').empty();
            document.getElementById('png-content').appendChild(canvas);

            $('canvas', document.getElementById('png-content')).toggleClass('canvas-png');
            $('canvas', document.getElementById('png-content')).attr('id', 'canvas-content');
            $('canvas', document.getElementById('png-content')).css('display', 'none');
        });
       	
//       	savePng();
    }
	
	function convertBase64UrlToBlob(urlData,type){
	    var bytes=window.atob(urlData.split(',')[1]);        //去掉url的头，并转换为byte
	    //处理异常,将ascii码小于0的转换为大于0
	    var ab = new ArrayBuffer(bytes.length);
	    var ia = new Uint8Array(ab);
	    for (var i = 0; i < bytes.length; i++) {
	        ia[i] = bytes.charCodeAt(i);
	    }
	    return new Blob( [ab] , {type : 'image/'+type});
	}
	
	function savePng(){
		var canvas = document.getElementById("canvas-content");
		
		
//		var ctx = canvas.getContext("2d");
//		console.log(ctx);
		var dataURL = canvas.toDataURL("image/png");
//		var base64 = dataURL.replace(/^data:image\/(png|jpg);base64,/, "");  // 如果不用UploadTool上传
		var base64 = dataURL;
		
//		var oImage = new Image;
//		oImage.src = base64;
		//图片加载完成后执行 gameLoop 函数
//		oImage.addEventListener("load", function(){

			$.ajax({
	            type: "POST",
	            url: "/CupquizActivities/base64ToImage.html",
	            data: {'base64':base64},
	            
	            success: function(data){
	                if(data.status){
	                	if(data.session_id){
	                		$('#preview-window').empty();
	                		$('#preview-window').html('<img src="'+data.url+'"/>');
	                		$('#preview-window-button').html('长按上图保存');
//	                		var session_id = data.session_id;
//	                    	var href = '/CupquizActivities/saveImg.html?session_id=' + session_id;
//	                    	console.log(href);
//	                    	window.location.href = href;
	                	}
	                }
	            }
	        });
//		});
		
	}
	
	
	$(document).ready(function(){
		createPng();
	});
	
//	$('body').off('click.#preview-window-button').on('click.#preview-window-button', function() {
	$('#preview-window-button').click(function(){
		savePng();
    });
	
	
	 $('.close-btn').click(function(){
         $('.popup').css('display','none');
     });
	
//	$("#preview-window-button").on({    
//        touchstart: function(e) {   
//            // 长按事件触发    
//            timeOutEvent = setTimeout(function() {    
//                timeOutEvent = 0;    
//                savePng();
//                
//            }, 400);    
//            //长按400毫秒     
//            // e.preventDefault();      
//        },    
//        touchmove: function() {    
//            clearTimeout(timeOutEvent);    
//            timeOutEvent = 0;    
//        },    
//        touchend: function() {    
//            clearTimeout(timeOutEvent);    
//            if (timeOutEvent != 0) {    
//                // 点击事件    
//                // location.href = '/a/live-rooms.html';    
//               // alert('你点击了');    
//            }    
//            return false;    
//        }    
//    });

});