/**
 * 欧冠js
 * @since  2018-01-31
 *
**/
$(function(){
	groupData(unionId, 'A', dataType, 0);
	//分组积分
	$('.poiTable .groupList a').click(function(e) {
		var unionId = $(this).parents().attr('unionId');
		var groupId = $.trim($(this).text());
		var dataType = $(this).parents().data('type');

		if($("#tbody-"+dataType+"-"+groupId).children().length > 0){
			$("#tbody-"+dataType+"-"+groupId).show().siblings('tbody').hide();
			$(this).addClass('on').siblings().removeClass('on');
			return false;
		}

		groupData(unionId, groupId, dataType, 1);
		$("#tbody-"+dataType+"-"+groupId).show().siblings('tbody').hide();
		$(this).addClass('on').siblings().removeClass('on');
	});

	function groupData(unionId, groupId, dataType, isOpen){
		if(unionId == undefined || groupId == undefined || dataType == undefined){
			return false;
		}

		$.ajax({
			type: 'post',
			url: "getGroupData.html",
			data: {unionId: unionId, groupId: groupId},
			dataType: 'json',
			beforeSend:function(){
				$("#tbody-"+dataType+"-"+groupId).html("<tr style='text-align: center;'><td colspan='4'><img class='load' src='"+staticDomain+"/Public/Images/load.gif'> 数据加载中，请稍候...</td><tr>");
			},
			success: function (data) {
				if (data.status == 1) {
					$("#tbody-"+dataType+"-"+groupId).html(data.info);
				}else{
					if(isOpen){
						layer.msg(data.info);
					}
				}
			},
			complete:function(){
				$("#tbody-"+dataType+"-"+groupId+' .load').parent().parent().remove();
			},
		});
	};

})