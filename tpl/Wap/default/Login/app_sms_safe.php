<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>安全验证</title>
		<meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name='apple-touch-fullscreen' content='yes'>
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=no">
		<meta name="format-detection" content="address=no">

		<link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
		<link href="{pigcms{$static_path}css/index_wap.css" rel="stylesheet"/>
		<style>
			body{background-color:white;}
			#container{padding:20px;}
			#captchaTip{font-size:20px;margin-bottom:20px;}
		</style>
	</head>
	<body id="index" data-com="pagecommon">
		<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script>
			var showFrom = "{pigcms{$_GET['from']}";
		</script>
		<if condition="$config['qcloud_captcha_appid']">
			<script src="https://turing.captcha.qcloud.com/TCaptcha.js"></script>
			<script>
				var myCaptcha = new TencentCaptcha("{pigcms{$config.qcloud_captcha_appid}", function(res){
					console.log('qcloud_captcha_res', res);
					
					if(res.ticket && res.randstr){
						var token = res.ticket + '___' + res.randstr;
						myCaptcha.destroy();
						
						if(showFrom == 'pc_iframe'){		//PC端iframe嵌套
							window.parent.getVerifyCode(token);
						} else {
							if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase()) && /(iphone)/.test(navigator.platform.toLowerCase())){
								window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'passCaptchaToken', token:token});
							} else {
								window.lifepasslogin.passCaptchaToken(JSON.stringify({token:token}));
							}
						}
					}else{
						myCaptcha.destroy();
						myCaptcha.show();
					}
				}, {});
				$('.dx_captcha_basic_tr-btn-close').hide();
				myCaptcha.show();
			</script>
		<else/>
			<div id="container">
				<div id="captchaTip">拖动下方滑块完成拼图</div>
				<div id="captchaSendCode"></div>
			</div>
			<script src="https://turing.captcha.qcloud.com/TCaptcha.js"></script>
			<script>
				var myCaptcha = _dx.Captcha(document.getElementById('captchaSendCode'), {
					appId: "{pigcms{$config.dingxiang_captcha_appid}",
					style: 'embed',
					width: $(window).width() - 40,
					success: function (token) {
						CaptchaToken = token;
						console.log('Captcha token:', token)
						myCaptcha.hide();
						
						if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase()) && /(iphone)/.test(navigator.platform.toLowerCase())){
							window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'passCaptchaToken', token:token});
						}else{
							window.lifepasslogin.passCaptchaToken(JSON.stringify({token:token}));
						}
					}
				});
				$('.dx_captcha_basic_tr-btn-close').hide();
				myCaptcha.show();
			</script>
		</if>
		
		{pigcms{$hideScript}
	</body>
</html>