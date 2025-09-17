<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta charset="utf-8"/>
	<title>查看二维码</title>
	<style>
		*{margin:0;padding:0;}
		img{
			width:250px;
			height:250px;
		}
		.box{
			padding-top:20px;
			text-align:center;
		}
		div.item{
			width:32%;
			display:inline-block;
			overflow:hidden;
			text-align:center;
		}
	</style>
</head>
<body>
	<div class="box">
		<div class="item">
			<img src="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=shop&id={pigcms{$_GET['id']}"/>
			<p>公众号二维码</p>
		</div>
		<div class="item">
			<img src="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=get_own_qrcode&qrCon={pigcms{:urlencode($config['site_url']. '/index.php?g=Shop&c=Store&a=mobile_page&store_id=' . $_GET['id'] . '&page_from=store_qr_code')}"/>
			<p>网页二维码</p>
		</div>
		<if condition="$config['pay_wxapp_appid']">
			<div class="item">
				<img src="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition_wxapp&a=create_page_qrcode&page={pigcms{:urlencode('pages/shop_new/shopDetail/shopDetail?store_id=' . $_GET['id'] . '&page_from=store_qr_code')}"/>
				<p>小程序二维码</p>
			</div>
		</if>
	</div>
</body>
</html>