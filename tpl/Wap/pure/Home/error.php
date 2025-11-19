<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title><if condition="$title"><?php echo ($title);?><elseif condition="$is_app_browser && strpos($url,'c=Login&a=index')"/>{pigcms{:L_("跳转登录中")}<else/>{pigcms{:L_("页面提示")}</if></title>
	<meta name="description" content="{pigcms{$config.seo_description}">
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
	<link href="{pigcms{$static_path}layer/need/layer.css" rel="stylesheet"/>
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
    <script src="{pigcms{$static_path}layer/layer.m.js"></script>
    <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?2102" charset="utf-8"></script>
</head>
<body>
    <if condition="$is_app_browser && strpos($url,'c=Login&a=index')">
        <script type="text/javascript">
            layer.open({type: 2});
			function refresh(ticket,device){
				if(ticket && device){
					$.post("{pigcms{:U('Home/ajaxLogin')}", { ticket: ticket, 'Device-Id': device },function(){
						location.reload();
					});
				}
			}
			function ReturnLastPage(){
				history.back();
			}
            <if condition="$app_browser_type eq 'android'">
                var login_timer = setInterval(function(){
					if (typeof window.lifepasslogin == 'object' || typeof window.lifepasslogin == 'function') {
						window.lifepasslogin.startJsToJavaFunction();
						clearInterval(login_timer);
					}
				},200);
                function androidRefresh(ticket,device){
                    if(ticket && device){
                        $.post("{pigcms{:U('Home/ajaxLogin')}", { ticket: ticket, 'Device-Id': device },function(){
                            location.reload();
                        });
                    }
                }
            <else/>
				var login_timer = setInterval(function(){
					if( parseFloat(iosCommonNew.getVersion()) < 1000 || typeof window.webkit == 'object' || typeof window.webkit == 'function'){
						iosCommonNew.iosFunction('login', {name:'login'});
						clearInterval(login_timer);
					}
				},200);
            </if>
        </script>
	<elseif condition="$is_app_browser && strpos($url,'c=My&a=bind_user')"/>
        <script type="text/javascript">
            layer.open({type: 2});
			function ReturnLastPage(){
				history.back();
			}
			function refresh(ticket,device){
				if(ticket && device){
					$.post("{pigcms{:U('Home/ajaxLogin')}", { ticket: ticket, 'Device-Id': device },function(){
						window.location.reload();
					});
				}
			}
            <if condition="$app_browser_type eq 'android'">
                window.lifepasslogin.jumpToBindPhoneActivity();
                function androidRefresh(ticket,device){
                    if(ticket && device){
                        $.post("{pigcms{:U('Home/ajaxLogin')}", { ticket: ticket, 'Device-Id': device },function(){
                            location.reload();
                        });
                    }
                }
            <else/>
                iosCommonNew.iosFunction('bindphone', {name:'bindphone'});
            </if>
        </script>
    <else/>
        <script type="text/javascript">
            var location_url = '{pigcms{$url}', wxscan='{pigcms{$_GET["wxscan"]}', is_weixin_browser = '{pigcms{$is_weixin_browser}',wxapp_error_handle = '{pigcms{$_GET.wxapp_error_handle}';
            layer.open({content:'{pigcms{$msg}',btn: ['{pigcms{:L_("确定")}'],end:function(){
                if (wxscan == 1 && is_weixin_browser && (location_url == '' || location_url.indexOf('javascript') != -1)) {
                    wx.closeWindow();
                } else if(wxapp_error_handle == 'close_window'){
                    if (/(alipay)/.test(navigator.userAgent.toLowerCase()) && /(miniprogram)/.test(navigator.userAgent.toLowerCase())) {
						$.getScript('https://appx/web-view.min.js',function(){
							my.navigateBack();
						});
					}else if({pigcms{$is_wxapp_browser ? 'true' : 'false'}){
						wx.miniProgram.navigateBack();
					}else{
						location.href = location_url;
					}
                } else {
                    <php>if($is_app_browser){</php>
                        <php>if($app_browser_type == 'android'){</php>
							if(location_url.indexOf('javascript') == -1){
								location.href = location_url;
							}else{
								window.lifepasslogin.webViewGoBack();
							}
                        <php>}else{</php>
							if(location_url.indexOf('javascript') == -1){
								location.href = location_url;
							}else{
								iosCommonNew.iosFunction('webViewGoBack', {name:'webViewGoBack'});
							}
                        <php>}</php>
                    <php>}else{</php>
                        if(location_url.indexOf('javascript') == -1){
                            location.replace(location_url);
                        }else{
                            location.href = location_url;
                        }
                    <php>}</php>
                }
            }});
        </script>
    </if>
	
    {pigcms{$shareScript}
</body>
</html>