/**
 * 系统判断js文件
**/
$(function(){
	$('#load_btn,#load_btn01,#app-bar').on('click',function() {
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
			window.location.href="https://www.pgyer.com/qqty_enterprise";
		}


		if (browser.versions.MicroMessenger)
		{
			//如果是微信系統
			if(browser.versions.iPhone){
				window.location.href="https://www.pgyer.com/qqty_enterprise";
			}else if(browser.versions.android){
				window.location.href="http://a.app.qq.com/o/simple.jsp?pkgname=cn.qqw.app";
			}
		}
		else if(browser.versions.android)
		{
			//如果是安卓系統
			window.location.href="http://a.app.qq.com/o/simple.jsp?pkgname=cn.qqw.app";
		};
	});
})