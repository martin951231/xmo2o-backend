<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>购物车</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=yes"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/index.css?t={pigcms{:time()}"/>
		<script type="text/javascript">
            var nowPage = 'pageCart';
            var leader_id ='{pigcms{$_GET['leader_id']}';
            var main_color='{pigcms{$config["mobile_head_color"]}';
            var map_config = "{pigcms{$config['map_config']}";
            var google_map_ak = "{pigcms{$config['google_map_ak']}";
            var lat = "{pigcms{$header['pick_address']['lat']}";
            var long = "{pigcms{$header['pick_address']['long']}";
            var city = "{pigcms{$now_area['area_name']}";
        </script>
	</head>
	<body>
		<div class="pageBg"></div>
		<div id='headerFixed'></div>
		<scrollView id="pageIndex" class="page pageIndex y hide" data-loadurl="{pigcms{:U('index',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="{pigcms{$config['village_group_alias']}"></scrollView>
		<scrollView id="pageCart" class="page pageCart y hide" data-url="{pigcms{:U('cart',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="购物车" data-pagebg="">
			<div class="container cart">

				<div class="goodsBox cart cartBox">
						
				</div>
				
				<div class="rel emptyBox hidden">
					<div class="emptyInfo">
						<div><img src="{pigcms{$static_path}villagegroup/images/cart_null.png"></div>
						<p>购物车被饿扁了，赶紧去逛逛吧~</p>
					</div>
				</div>
				

			</div>
		</scrollView>
		<scrollView id="pageMy" class="page pageMy y hide" data-loadurl="{pigcms{:U('my',array('leader_id'=>$_GET['leader_id']))}" data-pagetitle="个人中心"></scrollView>


		<include file="index_footer"/>
	
	</body>
</html>