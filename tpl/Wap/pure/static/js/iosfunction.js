
/*判断IOS方法调用*/
var iosCommonNew = {  
	getVersion: function () {
		var reg = /versioncode=(\d+),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '85';
		}else{
			return arr[1];
		}
	},
	iosFunction:function(paramStr, paramObj){ 
 
		var pack_version = iosCommonNew.getVersion();
		var str = parseFloat(pack_version) >= 1000
		if ( str == true) {
			window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
		}
		
	}
}

