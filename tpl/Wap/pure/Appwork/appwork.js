var is_weixin_browser     = <php> echo $config['is_weixin_browser'] ? 'true' : 'false';</php>; //微信H5
var is_wxapp_browser      = <php> echo $config['is_wxapp_browser'] ? 'true' : 'false';</php>;  //微信小程序H5
var is_alipay_browser 	  = <php> echo $config['is_alipay_browser'] ? 'true' : 'false';</php>; //支付宝H5
var is_alipayapp_browser  = <php> echo $config['is_alipayapp_browser'] ? 'true' : 'false';</php>; //支付宝小程序H5

var appwork = {};

//微信小程序下加载微信的方法
if(is_wxapp_browser){
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = 'https://res.wx.qq.com/open/js/jweixin-1.4.0.js'; 
	document.getElementsByTagName('head')[0].appendChild(script);
}
//支付宝小程序下加载支付宝小的方法
if(is_alipayapp_browser){
	var script = document.createElement('script');
	script.type = 'text/javascript';
	script.src = 'https://appx/web-view.min.js'; 
	document.getElementsByTagName('head')[0].appendChild(script);
}

appwork.switchTab = function(param){
	if(is_wxapp_browser){
		wx.miniProgram.switchTab(param);
	}else if(is_alipayapp_browser){
		my.switchTab(param);
	}
}
appwork.navigateTo = function(param){
	if(is_wxapp_browser){
		wx.miniProgram.navigateTo(param);
	}else if(is_alipayapp_browser){
		my.navigateTo(param);
	}
}
appwork.redirectTo = function(param){
	if(is_wxapp_browser){
		wx.miniProgram.redirectTo(param);
	}else if(is_alipayapp_browser){
		my.redirectTo(param);
	}
}

appwork.parseUrl = function(url, root, isReturn, pageTitle) {
	var tmpArr = [];
	var goType = '';
	var goParam = '';
	var goPath = '';
	//快店详情
	if (url.indexOf('c=Shop&a=index&shop-id') >= 0) {
		tmpArr = url.split('=');
		goType = 'shopDetail';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/shopDetail/shopDetail';
	} else if (url.indexOf('c=Shop&a=classic_shop&shop_id=') >= 0) {
		tmpArr = url.split('=');
		goType = 'shopDetail';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/shopDetail/shopDetail';
	} else if (url.indexOf('c=Shop&a=index#shop-') >= 0) {
		tmpArr = url.split('-');
		goType = 'shopDetail';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/shopDetail/shopDetail';
	} else if (url.indexOf('c=Shop&a=index&#cat-') >= 0) {
		tmpArr = url.split('-');
		goType = 'shopCat';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/cart/cart';
	} else if (url.indexOf('c=Shop&a=index&cat=') >= 0) {
		tmpArr = url.split('=');
		goType = 'shopCat';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/cart/cart';
	} else if (url.indexOf('c=Shop&a=classic_cat&cat_url=') >= 0) {
		tmpArr = url.split('=');
		goType = 'shopCat';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/cart/cart';
	} else if (url.indexOf('c=Shop&a=classic_cat&cat=') >= 0) {
		tmpArr = url.split('=');
		goType = 'shopCat';
		goParam = tmpArr.pop();
		goPath = '/pages/shop_new/cart/cart';
	} else if (url.indexOf('c=Shop&a=index#good-') >= 0) {
		tmpArr = url.split('-');
		goType = 'shopGood';
		goParam = [];
		goParam.push(tmpArr.pop());
		goParam.push(tmpArr.pop());
		goPath = '/pages/shop_new/goodsDetail/goodsDetail';
	} else if (url.indexOf('c=Shop&a=classic_good') >= 0) {
		goType = 'shopGood';
		goParam = [];
		goParam.push(common.getUrlParam(url, 'good_id'));
		goParam.push(common.getUrlParam(url, 'shop_id'));
		goPath = '/pages/shop_new/goodsDetail/goodsDetail';
	} else if (url.indexOf('c=Shop&a=index') >= 0) {
		goType = 'shopIndex';
		goParam = '';
		goPath = '/pages/shop_index/index';
	} else if (url.indexOf('c=Shop&a=classic_index') >= 0) {
		goType = 'shopIndex';
		goParam = '';
		goPath = '/pages/shop_index/index';
	} else if (url.indexOf('c=My&a=pay') >= 0 && url.indexOf('mer_id=') >= 0) {
		goType = 'storePrepay';
		goParam = common.getUrlParam(url, 'mer_id');
		goPath = '';
	} else if (url.indexOf('c=Home&a=index') >= 0) {
		goType = 'homeIndex';
		goPath = '/pages/index/index';
	} else if (url.indexOf('c=My&a=index') >= 0) {
		goType = 'myIndex';
		goPath = '/pages/my/index';
	} else if (url.indexOf('c=Pay&a=check') >= 0) {
		goType = 'Pay';
		goParam = {};
		goParam.order_id = common.getUrlParam(url, 'order_id');
		goParam.order_type = common.getUrlParam(url, 'type');
		goPath = '';
	} else if (url.indexOf('pigvillage/index/index') >= 0 ) {
		goType = 'pigvillageIndex';
		goPath = '/pages/pigvillage/index/index';
	} else if (url.indexOf('pigvillage/convenient/convenient') >= 0 ) {
		goType = 'pigvillageConvenient';
		goPath = '/pages/pigvillage/convenient/convenient';
	} else if (url.indexOf('pigvillage/resident/resident') >= 0 ) {
		goType = 'pigvillageResident';
		goPath = '/pages/pigvillage/resident/resident';
	} else if (url.indexOf('pigvillage/my/myindex') >= 0 ) {
		goType = 'pigvillageMyindex';
		goPath = '/pages/pigvillage/my/myindex';
	} else {
		goType = 'url';
		goParam = url;
		goPath = '';
	}
	if (isReturn == true) {
		return {
			goType: goType,
			goParam: goParam,
			goPath: goPath
		};
	}


	console.log('=====goUrl======');
	console.log('old_url', url);
	console.log('goType', goType);
	console.log('goParam', goParam);

	if (goType != 'url' && url.indexOf('Shop') != -1) {
		var address = app ? app.globalData.delivery_address : '';
		if (address) {
			common.gotoShopPage(goType, goParam, pageTitle)
		} else {
			wx.getSetting({
				success: (res) => {
					if (res.authSetting['scope.userLocation'] == true) {
						common.getLocation((res) => {
							app.globalData.delivery_address = {
								lng: res.lng,
								lat: res.lat
							}
							common.gotoShopPage(goType, goParam, pageTitle)
								
						})
					} else {
						if (common.checkIsFooterPage("/pages/shop_index/index")) {
							appwork.switchTab({
								url: "/pages/shop_index/index"
							});
						} else {
							appwork.navigateTo({
								url: '/pages/nofooter/shop/shop_index',
							})
						}
					}
				}
			})
		}
	} else if (goType == 'homeIndex') {
		if (common.checkIsFooterPage("/pages/index/index")) {
			appwork.switchTab({
				url: "/pages/index/index"
			});
		} else {
			appwork.navigateTo({
				url: '/pages/nofooter/index',
			})
		}
	} else if (goType == 'myIndex') {
		if (common.checkIsFooterPage("/pages/my/index")) {
			appwork.switchTab({
				url: "/pages/my/index"
			});
		} else {
			appwork.navigateTo({
				url: '/pages/nofooter/my_index',
			})
		}
	} else if (goType == 'shopSpecial') {
		appwork.navigateTo({
			url: "/pages/shop/special?id=" + goParam
		});
	} else if (goType == 'merchantCard') {
		appwork.navigateTo({
			url: "/pages/merchant/card"
		});
	} else if (goType == 'merchantOrder') {
		appwork.navigateTo({
			url: "/pages/merchant/order"
		});
	} else if (goType == 'merchantCoupon') {
		appwork.navigateTo({
			url: "/pages/merchant/coupon"
		});
	} else if (goType == 'merchantRight') {
		appwork.navigateTo({
			url: "/pages/merchant/right"
		});
	} else if (goType == 'merchantPersonal') {
		appwork.navigateTo({
			url: "/pages/merchant/personal"
		});
	} else if (goType == 'merchantPoint') {
		appwork.navigateTo({
			url: "/pages/merchant/point"
		});
	} else if (goType == 'merchantTransrecord') {
		appwork.navigateTo({
			url: "/pages/merchant/transrecord"
		});
	} else if (goType == 'merchantPrepay') {
		appwork.navigateTo({
			url: "/pages/merchant/prepay"
		});
	} else if (goType == 'merchantCouponList') {
		appwork.navigateTo({
			url: "/pages/merchant/coupon_list"
		});
	} else if (goType == 'merchantStore') {
		appwork.navigateTo({
			url: "/pages/merchant/store"
		});
	} else if (goType == 'merchantShopList') {
		appwork.navigateTo({
			url: "/pages/merchant/store?goparam=shop"
		});
	} else if (goType == 'storeOrderList') {
		appwork.navigateTo({
			url: "/pages/my/store_order?mer_id=" + goParam
		});
	} else if (goType == 'groupOrderList') {
		appwork.navigateTo({
			url: "/pages/my/group_order?mer_id=" + goParam
		});
	} else if (goType == 'shopOrderList') {
		appwork.navigateTo({
			url: "/pages/my/shop_order?mer_id=" + goParam
		});
	} else if (goType == 'shopProductDetail') {
		console.log(url);
		var goUrl = "/pages/shop/product?product_id=" + common.getUrlParam(url, 'product_id');
		var otherLink = common.getUrlParam(url, 'otherLink');
		if (otherLink != '') {
			goUrl += "&otherLink=" + otherLink;
		}
		console.log(goUrl);
		appwork.navigateTo({
			url: goUrl
		});
	} else if (goType == 'storePrepay') {
		appwork.navigateTo({
			url: "/pages/store/prepay?mer_id=" + goParam
		});
	} else if (goType == 'groupDetail') {
		appwork.navigateTo({
			url: "/pages/group/detail?group_id=" + goParam
		});
	} else if (goType == 'Pay') {
		appwork.navigateTo({
			url: "/pages/pay/index?order_id=" + goParam.order_id + "&type=" + goParam.order_type
		});
	} else if (goType == 'pigvillageIndex') {
		if (common.checkIsFooterPage("/pages/pigvillage/index/index")) {
			appwork.switchTab({
				url: "/pages/pigvillage/index/index"
			});
		} else {
			appwork.navigateTo({
				url: '/pages/pigvillage/index/index',
			})
		}
	} else if (goType == 'pigvillageConvenient') {
		if (common.checkIsFooterPage("/pages/pigvillage/convenient/convenient")) {
			appwork.switchTab({
				url: "/pages/pigvillage/convenient/convenient"
			});
		} else {
			appwork.navigateTo({
				url: '/pages/pigvillage/convenient/convenient',
			})
		}
	} else if (goType == 'pigvillageResident') {
		if (common.checkIsFooterPage("/pages/pigvillage/resident/resident")) {
			appwork.switchTab({
				url: "/pages/pigvillage/resident/resident"
			});
		} else {
			appwork.navigateTo({
				url: '/pages/pigvillage/resident/resident',
			})
		}
	} else if (goType == 'pigvillageMyindex') {
		if (common.checkIsFooterPage("/pages/pigvillage/index/index")) {
			appwork.switchTab({
				url: "/pages/pigvillage/my/myindex"
			});
		} else {
			appwork.navigateTo({
				url: '/pages/pigvillage/my/myindex',
			})
		}
	} else if (goType == 'url') {
		console.log('url',url);
		// #ifdef H5
		if (url.indexOf('url') == 0) {
			tmpArr = url.split('/');
			url = '/wap.php?c=' + tmpArr[1] + '&a=' + tmpArr[2];
			if (process.env.NODE_ENV === 'development') {
				url = 'http://www.group.com' + url;
			}
			console.log('333333332222', url)
		}
		if (common.checkAlipayApp()) {
			url = '/pages/foodshop/index/index?webview_url=' + encodeURIComponent(url) + '&webview_title=' + encodeURIComponent(
				'打开新页面')
			if (!common.isAliApp) {
				loadJs("https://appx/web-view.min.js", function() {
					common.isAliApp = true
					my.navigateTo({
						url: url
					});
				});
			} else {
				my.navigateTo({
					url: url
				});
			}
		} else {
			location.href = url
		}

		return
		// #endif

		// #ifdef MP-WEIXIN || MP-ALIPAY
		if (url.indexOf('wxapp') == 0) {
			var arr = url.split('://')
			console.log('跳转小程序参数', arr)
			wx.navigateToMiniProgram({
				appId: arr[1],
				path: arr[2] ? arr[2] : '',
				success(res) {
					console.log('打开小程序成功appId=' + arr[1])
				}
			})
			return
		}

		var wxapp_config = wx.getStorageSync('wxapp_config');
		if (url.indexOf('url') == 0) {
			tmpArr = url.split('/');
			url = requestDomain + 'wap.php?c=' + tmpArr[1] + '&a=' + tmpArr[2];
		}
		url = url.replace('http://', 'https://');
		if (url.indexOf('https://') < 0 && url.indexOf('/') == 0) {
			url = requestDomain + url.substr(1);
		} else {
			tmpArr = url.split('/');
			var arr2 = requestDomain.split('/');
			var arr1 = wxapp_config.wxapp_domain_list ? wxapp_config.wxapp_domain_list : [];
			arr1.push(arr2[2], 'im-link.weihubao.com');
			console.log('能否打开网址', wxapp_config, arr1, tmpArr[2])
			arr1 = arr1.filter(item => item == tmpArr[2])
			if (arr1.length === 0) {
				wx.showModal({
					title: app ? app.changeTxt('提示') : '提示',
					content: app ? app.changeTxt('不支持打开该链接！') : '不支持打开该链接！',
					showCancel: false
				})
				return false;
			}
		}

		if (url.indexOf('Service&a=publish_detail&type=2') > 0) {
			if (wxapp_config && wxapp_config.config.wxapp_paotui_appid && wxapp_config.config.wxapp_paotui_appid != '') {
				wx.navigateToMiniProgram({
					appId: wxapp_config.config.wxapp_paotui_appid,
					success: function() {

					},
					fail: function(res) {
						common.parseUrl(url.replace('Service&a=publish_detail&type=2', 'Service&a=index&new_service=1'), '', false,
							pageTitle);
					}
				});
				return false;
			}
		}
		if (!pageTitle) {
			pageTitle = "";
		}
		url = common.changeURLArg(url, 'device_id', device_id);

		var ticket = uni.getStorageSync('ticket');
		if (ticket) {
			url = common.changeURLArg(url, 'ticket', ticket);
		}
		if (wxapp_config.city && wxapp_config.city.top_city && wxapp_config.city.top_city.area_id && url.indexOf('now_city=') ==
			-1) {
			url = common.changeURLArg(url, 'now_city', wxapp_config.city.top_city.area_id);
		}
		if (wxapp_config.city && wxapp_config.city.top_city && wxapp_config.city.area_id != wxapp_config.city.top_city.area_id &&
			url.indexOf('now_area=') == -1) {
			url = common.changeURLArg(url, 'now_area', wxapp_config.city.area_id);
		}
		var shop_city = app ? app.globalData.delivery_address : '';
		if (shop_city) {
			url = common.changeURLArg(url, 'now_shop_city', shop_city.city_id);
		}
		url = common.changeURLArg(url, 'wxappp_version', wxappVersionCode);
		if (wxapp_config.config.open_multilingual && wxapp_config.config.open_multilingual == 1) {
			url = common.changeURLArg(url, 'now_lang', wxapp_config.config.lang_config.now_lang);
		}

		console.log('打开的webview页面链接', url);
		uni.navigateTo({
			url: "/pages/webview/webview?webview_url=" + encodeURIComponent(url) + "&webview_title=" + encodeURIComponent(
				pageTitle)
		});
		// #endif
	}
}