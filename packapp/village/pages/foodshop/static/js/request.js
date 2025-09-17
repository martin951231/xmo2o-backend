/**
 * 基础请求
 * url -> 请求的url
 * params -> 请求的参数
 * showLoading -> 是否显示loading
 * loadingTitle -> 加载中的标题
 * success -> 成功回调
 */

// 配置集
import platConfig from '../../../../utils/platConfig.js'
import common from '../../../../utils/common.js'

// 首页路径
var firstPage = platConfig.firstPage
var wxapp_type = platConfig.wxapp_type

// #ifdef H5
var requestDomain = '/';
if (process.env.NODE_ENV === 'development') {
	console.log('开发环境')

	//判断是不是 localhost:8080打开的
	if (location.host == 'localhost:8080') {
		location.href = location.href.replace('localhost:8080', 'www.group.com');
	}
	if (location.host == '127.0.0.1:8080') {
		location.href = location.href.replace('127.0.0.1:8080', 'www.group.com');
	}
	
	if(platConfig.h5_test_domain != ''){
		requestDomain = platConfig.h5_test_domain
	}else{
		requestDomain = location.protocol + '//' + location.host;
	}
} else {
	requestDomain = location.protocol + '//' + location.host;
}
var device_id = uni.getStorageSync('device') ? uni.getStorageSync('device') : 'packapp';
var app_type = 'packapp';
// #endif

// #ifdef MP-WEIXIN || MP-ALIPAY
var requestDomain = platConfig.mp_domain
// #endif
var wxappVersionCode = 1510;
// #ifdef MP-WEIXIN
var device_id = 'wxapp';
var app_type = 'wxapp';
// #endif

// #ifdef MP-ALIPAY
var device_id = 'alipayapp';
var app_type = 'alipayapp';
// #endif

function post(url, _success, param='', showLoading=false, loadingTitle='') {
	const that = this
	
	if (typeof(url) == 'undefined' || url == '') {
		return false;
	}
	
	const app = getApp()
	
	url = `${requestDomain}${url}`
	
	param = param ? param : {}
	param.now_lang = wx.getStorageSync('now_lang');
	param.wxapp_type = wxapp_type;
	param.app_type = app_type;
	param['Device-Id'] = device_id;
	param.ticket = uni.getStorageSync('ticket') ? uni.getStorageSync('ticket') : ''; 
	param.app_version = wxappVersionCode;
	
	var wxapp_config = wx.getStorageSync('wxapp_config');
	if (wxapp_config && wxapp_config.city) {
		param.now_city = wxapp_config.city.top_city ? wxapp_config.city.top_city.area_id : wxapp_config.city.area_id;
		if (wxapp_config.city.area_id && wxapp_config.city.top_city && wxapp_config.city.top_city.area_id != wxapp_config.city
			.area_id) {
			param.now_area = wxapp_config.city.area_id;
		}
	} else {
		var city_param = wx.getStorageSync('city_param')
		param.now_city = city_param && city_param.now_city ? city_param.now_city : ''
		param.now_area = city_param && city_param.now_area ? city_param.now_area : ''
	}
	
	
	// #ifdef H5
		// userAgent中的，一般用于APP的页面
		if(common.$checkPlat.checkApp()){
			var reg = /now_city=(.*?),/;
			var arr = reg.exec(navigator.userAgent);
			if(arr !== null){
				param.now_city = arr[1];
			}
		}
	// #endif
	
	var shop_city = app ? app.globalData.delivery_address : '';
	if (shop_city) {
		param.now_shop_city = shop_city.city_id;
	}
	// #ifdef H5
		// userAgent中的，一般用于APP的页面
		if(common.$checkPlat.checkApp()){
			var reg = /now_shop_city=(.*?),/;
			var arr = reg.exec(navigator.userAgent);
			if(arr !== null){
				param.now_shop_city = arr[1];
			}
		}
	// #endif

	if (showLoading) {
		uni.showLoading({
			title: common.changeTxt('loadingTitle'),
		})
	}

	return new Promise(function(resolve, reject) {
		uni.request({
			url: url,
			data: param,
			method: "POST",
			header: {
				'Content-Type': 'application/json'
			},
			success: function(res) {
				if (res.statusCode == 200) {
					//还可以做一些业务代码的判断
					resolve(_success(res));
					if (res.data.status != 1000) {
						if (res.data.status == 1002) {
							// 需要登录
							var tmpDomain = requestDomain;
							if (tmpDomain == '/') {
								tmpDomain = 'http://www.group.com/';
							}
							var url = tmpDomain + 'packapp/my/login.html?back=' + encodeURIComponent(location.href) + '&loginFail=' + encodeURIComponent(location.href)
							location.replace(url)
						} else {
							uni.showToast({
								title: common.changeTxt(res.data.msg),
								icon: "none",
								duration: 2000
							})
						}
					}
                } else {
					uni.showToast({
						title: common.changeTxt('连接失败,稍后重试'),
						icon: "none",
						duration: 2000
					})
				}
				
				if (showLoading) {
					uni.hideLoading()
				}
			},
			fail: function(res) {
				uni.showToast({
					title
				})
				
				if (showLoading) {
					uni.hideLoading()
				}
			},
			complete: function(res) {
				// 接口调用结束的回调函数（调用成功、失败都会执行）
				console.log("requestUrl => " + url + 
				"\nparam => " + JSON.stringify(param) + 
				"\nmessage => " + (res.data.msg || '') + 
				"\nstatus => " + (res.data.status||''))
				
				//  + "\ndata => " + JSON.stringify(res.data)
				
				if (showLoading) {
					uni.hideLoading()
				}
			}
		})
	})
}

module.exports = {
	post: post
}