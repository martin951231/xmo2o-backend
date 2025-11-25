<!doctype html>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=Edge">
	<meta charset="utf-8">
	<if condition="$config['site_favicon']">
		<link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
	</if>
	<title>{pigcms{$store.name}</title>
	
	<style>
		*{
			padding:0px;
			margin:0px;
		}
		.container{
			width:420px;
			height:812px;
			margin:20px auto;
		}
		.container iframe{
			border:0;
			padding:0;
			width:100%;
			height:100%;
			background:white;
		}
	</style>
	<script>
		if(/(iphone|ipod|android|windows phone)/.test(navigator.userAgent.toLowerCase())){
			window.location.href = '{pigcms{$storeUrl}';
		}
	</script>
</head>
<body style="background: #f5f5f5;">
	<div class="container">
		<iframe id="shopIframe" src="{pigcms{$storeUrl}" onload="iframeLoad()"></iframe>
	</div>
	<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
	<script>
		$(function(){
			$('.container').css('max-height', ($(window).height() -40));
		});
		function iframeLoad(){
			var css =
				"::-webkit-scrollbar{width: 8px;height: 8px;background-color: #f6f8f8;-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;}::-webkit-scrollbar-thumb {background-color:rgba(45, 41, 41, 0.5);-webkit-border-radius: 4px;-moz-border-radius: 4px;border-radius: 4px;}";
			$('#shopIframe').contents().find('body').append('<style type="text/css">' + css + '</style>');
		}
	</script>
</body>
</html>