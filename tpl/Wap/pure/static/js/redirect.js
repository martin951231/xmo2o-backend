// 回退1級頁面，以後可能會擴展到回退任意級頁面
function linkBack() {
    if(checkWeixinApp()){
        wx.miniProgram.navigateBack();
    }else if(checkApp()){
        if(checkAndroidApp()){
            window.lifepasslogin.webViewGoBack();
        }else{
            iosFunction('webViewGoBack', {name:'webViewGoBack'});
        }
    }else{
        window.history.go(-1);
    }
}

// 打開新頁面
function linkNew(url) {
    console.log('22222', getUrlParam(url,'shop_id'))
    if(checkWeixinApp()){
        if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
            url = 'https://' + document.domain + url;
        }
        if(url == location.url){
            return false;
        }
        url = wxappUrlCheck(url)
        wx.miniProgram.navigateTo({ url: url });
    }else if(checkApp()){
		var checkUrl = wxappUrlCheck(url)
        if(url.substr(0,1) == '/'){
            url = window.location.protocol+'//'+ window.location.host + url;
        }
		if (checkUrl.indexOf('/pages/shop_new/shopDetail') != -1) {
			window.location.href = url;
			return false;
		}
		
        if(checkAndroidApp()){
        	if (parseFloat(getVersion()) >= 1400) {
        		window.lifepasslogin.createwebview(url);
        	} else {
        		window.location.href = url;
        	}
        }else{
            var iosUrl = window.btoa(url);
            iosUrl = iosUrl.replace('/','&');
            iosFunction('createwebview/'+iosUrl, {name:'createwebview', url:iosUrl, webUrl: url});
        }
    }else{
        window.location.href = url;
    }
}


// 在小程序頁面中的鏈接判斷
function wxappUrlCheck (url) {
    //快店详情
	if (url.indexOf('c=Shop&a=index&shop-id=') >= 0) {
	    return "/pages/shop_new/shopDetail/shopDetail?store_id=" + getUrlParam(url,'shop-id')
	}
    if (url.indexOf('c=Shop&a=index&shop-') >= 0 || url.indexOf('c=Shop&a=index#shop-') >= 0) {
        var tmpArr = url.split('-');
        var shop_id = tmpArr.pop();
        return "/pages/shop_new/shopDetail/shopDetail?store_id=" + shop_id
    }
    if (url.indexOf('c=Shop&a=classic_shop&shop_id=') >= 0) {
        return "/pages/shop_new/shopDetail/shopDetail?store_id=" + getUrlParam(url,'shop_id')
    }
    // 快店商品詳情
    if (url.indexOf('c=Shop&a=classic_good') >= 0 || url.indexOf('c=Shop&a=index#good-') >= 0) {
        return "/pages/shop_new/shopDetail/shopDetail?store_id=" + getUrlParam(url,'shop_id') + '&product_id=' + getUrlParam(url,'good_id')
    }
    // 快店首頁
    if (url.indexOf('c=Shop&a=index') >= 0 || url.indexOf('c=Shop&a=classic_index') >= 0) {
        return '/pages/nofooter/shop/shop_index'
    }
    if (url.indexOf('c=My&a=pay') >= 0 && url.indexOf('mer_id=') >= 0) {
        return "/pages/store/prepay?mer_id=" + getUrlParam(url,'mer_id')
    }
    // 平台首頁
    if (url.indexOf('c=Home&a=index') >= 0) {
        return '/pages/nofooter/index'
    }
    // 個人中心
    if (url.indexOf('c=My&a=index') >= 0) {
        return '/pages/nofooter/my_index'
    }
    // 支付頁
    if (url.indexOf('c=Pay&a=check') >= 0) {
        return "/pages/pay/index?order_id=" + getUrlParam(url, 'order_id') +"&type="+ getUrlParam(url, 'type')
    }
    return '/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent('打开新页面')
}

// 獲取url鏈接中的參數
var urlParamObj = {};
function getUrlParam (url, paramName) {
    if (!urlParamObj[url]){
        var param = url.substr(url.indexOf('?') + 1);
        var arr = param.split("&");
        console.log('arr', arr);
        urlParamObj[url] = {}
        for (var i = 0; i < arr.length; i++) {
            var paramArr = arr[i].split("=");
            urlParamObj[url][paramArr[0]] = paramArr[1];
        }
    }
    if (urlParamObj[url][paramName]) {
        return urlParamObj[url][paramName];
    } else {
        return '';
    }

}

function checkWeixinApp () {
    if(window.__wxjs_environment === 'miniprogram' || (/(micromessenger)/.test(navigator.userAgent.toLowerCase()) && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
        return true;
    }else{
        return false;
    }
}

function checkApp () {
    if(/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) || /(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
        return true;
    }else{
        return false;
    }
}

function checkIos () {
    if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase()) && /(iphone)/.test(navigator.platform.toLowerCase())){
        return true;
    }else{
        return false;
    }
}

function getVersion(){
    var reg = /versioncode=(.*?),/;
    var arr = reg.exec(navigator.userAgent.toLowerCase());
    if(arr == null){
        return '85';
    }else{
        return arr[1];
    }
}

function iosFunction (paramStr, paramObj){
    var pack_version = getVersion();
    var str = parseFloat(pack_version) >= 1400;
    if ( str == true) {
        window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
    } else {
		if (paramObj.name  == 'createwebview') {
			window.location.href = paramObj.webUrl
			return
		}
        $('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
        setTimeout(function(){
            $('#iosIframe').remove();
        },50);
		
    }
}

function checkAndroid(){
    if(/(android)/.test(navigator.userAgent.toLowerCase())){
        return true;
    }else{
        return false;
    }
}

function checkAndroidApp () {
    if(checkApp() && checkAndroid()){
        return true;
    }else{
        return false;
    }
}