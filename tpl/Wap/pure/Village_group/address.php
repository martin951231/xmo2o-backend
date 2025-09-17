<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>自提点设置</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<style type="text/css">
			input, input:active{
				border: none;
				outline: none;
			}

			.line{
				margin: 0 15px;
				padding: 15px 0;
				border-bottom: 1px solid #efefef;
				color: #333;
			}

			.line:last-child{
				border: none;
			}
			
			.line span{
				width: 60px;
				display: inline-block;
			}

			.line input{
				width: calc(100% - 65px);
			}

			.line input.submit{
				width: 100%;
				padding: 10px;
				border-radius: 5px;
				background: #00b500;
				color: #fff;
				font-size: 16px;
			}

			.line input.submit:active{
				background:#019301;
			}
		</style>
	</head>
	<body >
		<form>
			<div class="line">
				<span>联系人</span>
				<input type="text" name="name" placeholder="请输入联系人名称">
			</div>
			<div class="line">
				<span>联系电话</span>
				<input type="text" name="phone" pattern="[0-9]" placeholder="请输入联系电话">
			</div>
			<div class="line">
				<span>详细地址</span>
				<input type="text" name="address" placeholder="请输入详细的自提点地址">
			</div>
			<div class="line">
				<input class="submit" type="submit" name="submit" value='保存'>
			</div>
		</form>
	</body>
</html>