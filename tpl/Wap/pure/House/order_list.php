<!DOCTYPE html>
<html>

	<head>
		<meta charset="utf-8" />
		<title>个人中心</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
		<meta name="apple-mobile-web-app-capable" content="yes" />
		<meta name='apple-touch-fullscreen' content='yes' />
		<meta name="apple-mobile-web-app-status-bar-style" content="black" />
		<meta name="format-detection" content="telephone=no" />
		<meta name="format-detection" content="address=no" />

		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/new_village.css" />
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.cookie.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/iscroll.js?444" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?210" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/village_my.js?210" charset="utf-8"></script>
	</head>

	<body>
		<div id="container">
			<div id="scroller" class="village_my">
				<nav>
					<section class="link-url" data-url="{pigcms{:U('My/group_order_list')}"><span><img src="{pigcms{$static_path}images/tuan.png" /></span>
						<p>{pigcms{$config.group_alias_name}订单</p>
					</section>
					<section class="link-url" data-url="{pigcms{:U('My/shop_order_list')}"><span><img src="{pigcms{$static_path}images/dian.png" /></span>
						<p>{pigcms{$config.shop_alias_name}订单</p>
					</section>
					<section class="link-url" data-url="{pigcms{:U('My/appoint_order_list')}"><span><img src="{pigcms{$static_path}images/yuyue.png" /></span>
						<p>{pigcms{$config.appoint_alias_name}订单</p>
					</section>
				</nav>
			</div>

<!--			<div id="pullUp" style="bottom:-60px;">-->
<!--				<img src="/static/logo.png" style="width:130px;height:40px;margin-top:10px" />-->
<!--			</div>-->
		</div>
	</body>

</html>