var pageIndexMiniRefresh = null;
var showPageLoadTip = true;
var pageLoadedFunc = {
	pageIndex: function(){
		setTimeout(function(){
			//初始化商品列表宽高等效果
			goodList_before();
			
			var categoryBoxSlider = new Swiper('#categoryBox',{
				freeMode: true,
				slidesPerView: 'auto',
				// loopedSlides:1,
				onSlideChangeEnd: function(swiper){
					
				}
			});
			
			//首页轮播图等
			$('.sliderBox .sliderImg img').height(($(window).width()-30)*500/900);
			var recommendSlider = new Swiper('#recommend',{
				onSlideChangeEnd: function(swiper){
					$('.recommend-pagination .now').html(swiper.activeIndex+1);
				}
			});
			//下拉刷新效果
			pageIndexMiniRefresh = new MiniRefresh({
				container: '#pageIndex',
				// isLockX:false,
				down: {
					callback: function(){
						setTimeout(function(){
							pageIndexMiniRefresh.endDownLoading(true);
							showPageLoadTip = false;
							pageLoaded[nowPage] = null;
							$(".tabBtn[data-page='"+nowPage+"']").trigger('click');
						},600);
					},
					// 这个也生效
					isWrapCssTranslate: true
				},
				up: {
					isLock:true
				}
			});
		},0);
	},
	pageFind: function(){
		
	},
	pageMy: function(){
		setTimeout(function(){
			pageIndexMiniRefresh = new MiniRefresh({
				container: '#pageMy',
				// isLockX:false,
				down: {
					callback: function(){
						setTimeout(function(){
							pageIndexMiniRefresh.endDownLoading(true);
							showPageLoadTip = false;
							pageLoaded[nowPage] = null;
							$(".tabBtn[data-page='"+nowPage+"']").trigger('click');
						},600);
					},
					// 这个也生效
					isWrapCssTranslate: true
				},
				up: {
					isLock:true
				}
			});
		},0);
	}
};
//页面级的选项卡
var pageLoaded = {};
pageLoaded[nowPage] = true;
//如果方法存在，则调用方法初始化页面
if(pageLoadedFunc[nowPage]){
	pageLoadedFunc[nowPage]();
}
$('.tabBtn').click(function(){
	$(this).addClass('active').siblings('.tabBtn').removeClass('active');
	nowPage = $(this).data('page');
	var pageDom = $('.'+nowPage);
	pageDom.removeClass('hide').siblings('.page').addClass('hide');
	$('.pageChild').addClass('hide');
	$('.'+nowPage+'Child').removeClass('hide');
	//页面标题
	if(pageDom.data('pagetitle')){
		document.title = pageDom.data('pagetitle');
	}
	//页面背景
	if(pageDom.data('pagebg')){
		$('.pageBg,.'+nowPage).css('background-color',pageDom.data('pagebg'));
	}else{
		$('.pageBg,.'+nowPage).css('background-color','');
	}
	//模拟更新网址栏，防止用户刷新时，还是原页面
	var stateObj = { foo: nowPage };
	history.replaceState(stateObj, "", pageDom.data('loadurl'));
	
	
	if(!pageLoaded[nowPage]){
		pageLoaded[nowPage] = true;
		if(showPageLoadTip){
			var layerLoadTip = layer.open({type: 2,shade: 'background-color: rgba(0,0,0,.3)'});
		}
		pageDom.load(pageDom.data('loadurl')+' .'+nowPage+' .container',function(res){
			if(showPageLoadTip){
				layer.close(layerLoadTip);
			}
			showPageLoadTip = true;
			// console.log(res);
			//如果方法存在，则调用方法初始化页面
			if(pageLoadedFunc[nowPage]){
				pageLoadedFunc[nowPage]();
			}
		});
	}
});
$(".tabBtn[data-page='"+nowPage+"']").trigger('click');

var pageIndexTimer = setInterval(function(){
	var goPageIndex = localStorage.getItem('goPageIndex');
	if(goPageIndex){
		localStorage.removeItem('goPageIndex');
		$(".tabBtn[data-page='pageIndex']").trigger('click');
	}
},800);


$('body').delegate('#setting_inviteWechat','click',function(){
	if(motify.checkWeixinApp()){
		$('.pageWxappWechatBox').show();
	}else if(motify.checkWeixin()){
		var img = $('.pageWechatQrcodeBox img').attr('src');
		layer.open({
			content:'<div style="text-align: center;width: 100%;"><div style="font-size: 16px; margin-bottom: 10px;">产品上新优惠提醒不错过</div><img style="width:120px;height:120px;" src="'+img+'" style="margin-bottom:5px;background:url(/static/images/loading.gif) center center no-repeat;"/><br/><div style="color: #B7B7B7;display: inline;text-align: center;background-color: #F8F8F8;padding: 8px 8px;border-radius: 5px;font-size: 12px;">长按图片识别关注公众号</div></div>',
			btn: ['关闭'],
			success:function(){
				console.log('确定end');
				layer.close();
			}
		});
		$('.layermbtn').css('color', '#DC3B3C');
		$('.layermbox0 .layermchild').css('width', '260px');
	}
});
$('body').delegate('.pageWxappWechatBox','click',function(){
	$('.pageWxappWechatBox').hide();
});

$('body').delegate('.go_login','click',function(){
	location.href = '?c=Login&a=index&referer='+encodeURIComponent(location.href);
});

if(/(main_webview)/.test(navigator.userAgent.toLowerCase())){
	$('.page').height($(window).height());
	$('.footerMenu').hide();
	$('.merchantIncomeBox').css('bottom', '30px');
}