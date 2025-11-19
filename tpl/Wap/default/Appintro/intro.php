<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8"/>
		<title>{pigcms{$intro.title}</title>
		<meta name="description" content="{pigcms{$config.seo_description}">
		<meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
		<meta name="apple-mobile-web-app-capable" content="yes">
		<meta name='apple-touch-fullscreen' content='yes'>
		<meta name="apple-mobile-web-app-status-bar-style" content="black">
		<meta name="format-detection" content="telephone=no">
		<meta name="format-detection" content="address=no">

		<link href="{pigcms{$static_path}css/mp_news.css" rel="stylesheet"/>
	</head>
	<style>
		.rich_media{
			width: 100%;
    		overflow: hidden;
		}
	</style>
		<body id="activity-detail" class=" ">
		<div class="rich_media container" style="background:white;">
			<div class="header" style="display:none;"></div>
			<div class="rich_media_inner content">
				
				<div id="page-content" class="content">
					{pigcms{$intro.content}
				</div>
			</div>
		</div>
		<div style="display:none;">{pigcms{$config.wap_site_footer}</div>
		<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script>
			$('.rich_media_inner').css('min-height',$(window).height()+'px');
		</script>
		<script type="text/javascript">
			window.shareData = {  
				"moduleName":"Article",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>", 
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Appintro/intro', array('id' => $intro['id']))}",
				"tTitle": "{pigcms{$intro['title']} - {pigcms{$config['site_name']}",
				"tContent": "{pigcms{$config['site_name']}"
			};
		</script>
		{pigcms{$shareScript}
	</body>
</html>