/*
 * 图片上传预览 -IE是用了滤镜
 * @param index #指针序号
 * @param obj   #操作对象，该对象操作的必须是隐藏域，如存在则为将文件流存储到该对象的value里面
 *
*/
function previewImage(index,obj)
{
	//初始化三个必要的对象
	var file = document.getElementById('viewUploadInput_'+index);
	var div = document.getElementById('viewUploadDiv_'+index);
	var img = document.getElementById('viewUploadImg_'+index);	
	
	var imgSrc = '';/*用于存储img的数据流src*/
	var filepath=file.value;
	
	var extStart=filepath.lastIndexOf(".");
	var ext=filepath.substring(extStart,filepath.length).toUpperCase();
	if(ext!=".BMP"&&ext!=".PNG"&&ext!=".GIF"&&ext!=".JPG"&&ext!=".JPEG"&&ext!=".SWF"){
		$("#viewUploadInput_"+index).val('');
		var msg = "图片限于bmp,png,gif,jpeg,jpg格式";
		alert(msg);
		return;
	}

	var imgFilePath=$(file).val();
	var file_size = 0;
	/*if ($.browser.msie) {
		var imgObj = new Image();
		imgObj.src = imgFilePath;
		while (true) {
			if (imgObj.fileSize > 0) {
				if (imgObj.fileSize > 1024) {
					alert("图片不大于100MB。");
				} else {
					var num03 = imgObj.fileSize / 1024;
					num04 = num03.toFixed(2); //把 Number 四舍五入为指定小数位数的数字
					alert(num04 + "KB");
				}
				break;
			}
		}
	}else {*/
		file_size = file.files[0].size;
		var size = file_size / 1024;
		if (size > 2048) {
			num02 = size.toFixed(2);
			$("#viewUploadInput_"+index).val('');
			var msg = "图片文件大小超过限制。请上传小于2M的文件，当前文件大小为"+num02+"KB";
			alert(msg);
			return;
		} else {
			
		}
	//}

	var MAXWIDTH  = 80;
	var MAXHEIGHT = 180;
	
	if (file.files && file.files[0])
	{
		div.innerHTML ='<img id="viewUploadImg_'+index+'">';
		var img = document.getElementById('viewUploadImg_'+index);
		img.onload = function(){
			var rect = clacImgZoomParam(MAXWIDTH, MAXHEIGHT, img.offsetWidth, img.offsetHeight);
			img.width  =  rect.width;
			img.height =  rect.height;
			//img.style.marginLeft = rect.left+'px';
			//img.style.marginTop = rect.top+'px';
		}
		var reader = new FileReader();
		reader.onload = function(evt){
			img.src = evt.target.result; 
			imgSrc = evt.target.result;			
			if (obj){
				//获取文件流赋值给隐藏域
				$(obj).val(imgSrc);
			}
		}
		reader.readAsDataURL(file.files[0]);
	}
	else //兼容IE
	{
		var sFilter='filter:progid:DXImageTransform.Microsoft.AlphaImageLoader(sizingMethod=scale,src="';
		file.select();
		var src = document.selection.createRange().text;
		div.innerHTML = '<img id="viewUploadImg_'+index+'">';
		var img = document.getElementById('viewUploadImg_'+index);
		img.filters.item('DXImageTransform.Microsoft.AlphaImageLoader').src = src;
		var rect = clacImgZoomParam(MAXWIDTH, MAXHEIGHT, img.offsetWidth, img.offsetHeight);
		status =('rect:'+rect.top+','+rect.left+','+rect.width+','+rect.height);
		//div.innerHTML = "<div id='viewUploadDiv_"+index+"' style='width:"+rect.width+"px;height:"+rect.height+"px;margin-top:"+rect.top+"px;"+sFilter+src+"\"'></div>";
		div.innerHTML = "<div id='viewUploadDiv_"+index+"' style='width:"+rect.width+"px;height:"+rect.height+"px;"+sFilter+src+"\"'></div>";
		if (obj){
			//获取文件流赋值给隐藏域
			var imgSrc = document.getElementById('viewUploadImg_'+index).src;
			$(obj).val(imgSrc);
		}
	}
	
}
function clacImgZoomParam( maxWidth, maxHeight, width, height ){
	var param = {top:0, left:0, width:width, height:height};
	if( width>maxWidth || height>maxHeight )
	{
		rateWidth = width / maxWidth;
		rateHeight = height / maxHeight;
		 
		if( rateWidth > rateHeight )
		{
			param.width =  maxWidth;
			param.height = Math.round(height / rateWidth);
		}else
		{
			param.width = Math.round(width / rateHeight);
			param.height = maxHeight;
		}
	}
	 
	param.left = Math.round((maxWidth - param.width) / 2);
	param.top = Math.round((maxHeight - param.height) / 2);
	return param;
}
	
/*
 * 点击选择需要上传预览的图片
 *
*/
function selectViewUploadImg(obj){
	if ($(obj).prev().attr('type')=="file"){
		$(obj).prev().click();
	} else {
		$(obj).click();
	}
}