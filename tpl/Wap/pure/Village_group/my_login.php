<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>我的</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
		<script type="text/javascript">var nowPage = 'pageMy';</script>
	</head>
	<body>
		<div class="pageBg"></div>
		<include file="header_fixed"/>
		
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="{pigcms{$config.group_index_name|default='同城优选'}"></scrollView>
		<scrollView id="pageCart" class="page pageCart y hide" data-loadurl="{pigcms{:U('cart',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="为您挑好店" data-pagebg="{pigcms{$config.mobile_head_color}"></scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="个人中心">
			<div class="container">
				<div id="my-need-login" data-url="{pigcms{$redirect_url}"></div>
			</div>
		</scrollView>
		<include file="index_footer"/>

		<include file="code_mask"/>
	</body>
</html>