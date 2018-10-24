
	var ARTICLE_DATA = {
	workinfo:'',
	//评论微博转发视频地址
	video_url:'',
	//评论微博转发图片地址，可置空会自动取图
	pic_url:'',
	
	//频道
	channel:'ty',
	//新闻id
	newsid:'slidenews-album-786-97868',
	//组，默认为0
	
	group:1,
	//微博转发参数
	source: '',
	sourceUrl: '',
	uid: '',
	autoLogin:1,
	cmntFix:0,
	channelId: 0,
	//1滚动到底部自动加载，0点击更多加载
	scrollLoad:1,
	width:1000,
	sharePopDirection:'top',
	// 高清图在切换过程中，评论参数修改
	customNewsId:'',
	customShareUrl:'',
	customImgUrl:''
	};
	var SLIDE_DATA = {
	   
		ch:'2',
		sid:'786',
		aid:'97868',
		range:'',
		key:'',
		pvurl:'',
		soundSrc:'',
		soundAltSrc:'',
		imgType:'img',
		allowDownload:true,
		likeBoard:'board=all-all,sports-2',
		
	
	recommenderReady:function(wrap){
			if(!SINAADS_CONFIG_POS_PDPS['recommender'][__ch_id__ + '']){return;}
			var byAttr = ___SinaRecommender___.util.byAttr;
			var items = byAttr(wrap,'recommend-type','item');
			var total = 20;
			(function(d, s, id) {
				var s, n = d.getElementsByTagName(s)[0];
				if (d.getElementById(id)) return;
				s = d.createElement(s);
				s.id = id;
				s.setAttribute("charset", "utf-8");
				s.src = "//d" + Math.floor(0 + Math.random() * (9 - 0 + 1)) + ".sina.com.cn/litong/zhitou/sinaads/release/sinaads.js";
				n.parentNode.insertBefore(s, n);
			})(document, "script", "sinaads-script");
			//保存推荐内容, 用于后面填充
			var oHtml = items[4].innerHTML;
			var _insId = 'ID' + SINAADS_CONFIG_POS_PDPS['recommender'][__ch_id__ + ''];
			//在推荐中插入广告执行代码4 和 24 的位置
			//异步插入广告的方式需要防止参数匹配错误，使用sync加载
			//填充其他广告复制节点
			function fillOther(html, start) {
				for (var i = start; i < 2; i++) {
					items[4 + i * total].innerHTML = html;
				}
			}
			//执行广告请求
			//create a hidden ad slot for request
			var hiddenNode = document.createElement('div');
			hiddenNode.style.cssText = 'display:none';
			document.body.appendChild(hiddenNode);
			hiddenNode.innerHTML = '<ins class="sinaads"' +
				' data-ad-pdps="' + SINAADS_CONFIG_POS_PDPS['recommender'][__ch_id__ + ''] + '"' +
				' id="' + _insId + '"' +
				' data-ad-status="sync"' +
			'></ins>';
	
			sinaads = window.sinaads || [];
			sinaads.push({
				element: document.getElementById(_insId),
				params: {
					sinaads_ad_width: 200,
					sinaads_ad_height: 153,
					sinaads_fail_handler:function(){
						//fail, fill ohtml 2 slider
						fillOther(oHtml, 0);
					},
					sinaads_success_handler: function (el) {
						//when success, fill hidden slot html 2 slider
						var html = el.innerHTML;
						fillOther(html, 0);
					},
					sinaads_ad_tpl: function() {
						return '<a title="#{src1}" target="_blank" href="#{link0}"><span class="pic"><img src="#{src0}" /><i></i></span><span class="txt">#{src1}</span></a>';
					}
				}
			});
		}
	  };