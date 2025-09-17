</div>
</body>

<script>
$(function(){
	var motify = {
		checkIos:function(){
			if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
				return true;
			}else{
				return false;
			}
		},
		checkAndroid:function(){
			if(/(android)/.test(navigator.userAgent.toLowerCase())){
				return true;
			}else{
				return false;
			}
		},
		checkLifeApp:function(){
			if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
				return true;
			}else{
				return false;
			}
		},
		getLifeAppVersion:function(){
			var reg = /versioncode=(\d+),/;
			var arr = reg.exec(navigator.userAgent.toLowerCase());
			if(arr == null){
				return 0;
			}else{
				return parseInt(arr[1]);
			}
		},
		getAndroidVersion:function(){
			var index = navigator.userAgent.indexOf("Android");
			if(index >= 0){
				var androidVersion = parseFloat(navigator.userAgent.slice(index+8));
				if(androidVersion > 1){
					return androidVersion;
				}else{
					return 100;
				}
			}else{
				return 100;
			}
		},
		iosFunction:function(paramStr, paramObj){
			var pack_version = motify.getLifeAppVersion();
			var str = parseFloat(pack_version) >= 1000
			if ( str == true) {
				window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
			} else {
				$('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
			}
			
		}
	}
	
	if(motify.checkLifeApp() && motify.getLifeAppVersion() >= 50 && (motify.checkIos() || motify.checkAndroid())){
		
		$('#return_index').off('click');
		
		if(motify.checkAndroid()){
			// alert(1) 
			setTimeout(function(){
				window.lifepasslogin.hideWebViewHeader(false);
				$('#return_index').on('click',function(){
					window.lifepasslogin.webViewGoBack();
				});
			},300)
			
		}else{
			
			motify.iosFunction('hideWebViewHeader/false', {name:'hideWebViewHeader', show_head:'0' })
            $('.app-header').css('height', '60px');
            $('.app-header .margin-top3').css('margin-top', '15px');
            $('.app-header #return_index.margin-top3').css('margin-top', '12px');
			$('#return_index').on('click',function(){
				motify.iosFunction('webViewGoBack', {name:'webViewGoBack'});
			});
		}
	}
});
function onPageFinished (){

	window.lifepasslogin.hideWebViewHeader(false);
	$('#return_index').on('click',function(){
		window.lifepasslogin.webViewGoBack();
	});
}


</script>
</html>