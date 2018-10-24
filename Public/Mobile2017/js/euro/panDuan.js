/**
 * 系统判断js文件
 *
 * @author pengjian <710348662@qq.com>
 *
 * @since  2016-04-27
 *
**/

$(function(){
	$('#load_btn,#load_btn01').click(function(event) {
		/* Act on the event */
		androidURL = "http://www.qqtyw.com/Uploads/App/qqw_v1.0.0.apk";
			var browser = {

				versions: function() {

					var u = navigator.userAgent,

					app = navigator.appVersion;

					return {

						android: u.indexOf('Android') > -1 || u.indexOf('Linux') > -1,

						iPhone: u.indexOf('iPhone') > -1 ,

						iPad: u.indexOf('iPad') > -1,

						iPod: u.indexOf('iPod') > -1,

						MicroMessenger: u.indexOf('MicroMessenger') > -1,

					};

				} (),

				language: (navigator.browserLanguage || navigator.language).toLowerCase()

			}

			if (browser.versions.iPhone||browser.versions.iPad||browser.versions.iPod)

			{

				 //如果是ios系統，直接跳轉至appstore該應用首頁，傳遞参數为該應用在appstroe的id號

				 window.location.href="https://itunes.apple.com/cn/app/quan-qiu-ti-yu/id1079328958?mt=8";
				 // alert('不好意思，正在开发中^_^');

			}


			if (browser.versions.MicroMessenger)

			{

				 //如果是微信系統，用右上角的浏览器打开
				 alert('请用右上角的浏览器打开^_^');

			}

			else if(browser.versions.android)

			{

				window.location.href = androidURL;

			};
	});
})