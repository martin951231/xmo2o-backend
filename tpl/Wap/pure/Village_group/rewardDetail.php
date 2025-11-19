<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>佣金详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<style type="text/css">
			body{
				background: #f8f8f8;
			}

			.info-box{
				background: #fff;
				padding: 20px;
			}

			.info-box img{
				width: 50px;
				height: 50px;
				border-radius: 50%;
				margin: 0 auto 10px;
			}

			.info-box .reward{
				font-size: 30px;
				text-align: center;
				padding:10px 0 30px;
				border-bottom: 1px solid #efefef;
			}

			.info-box .detail>div{
				margin: 20px 0;
				font-size: 13px;
			}

			.info-box .detail .left{
				width: 80px;
				color: #999;
				display: inline-block;
			}

			.info-box .detail .right{
				color: #333;
			}
		</style>
	</head>
	<body>
		<div class="info-box">
            <if condition="$user_info && $user_info['avatar']">
                <img src="{pigcms{$user_info['avatar']}">
                <else/>
                <img src="{pigcms{$static_path}images/new_my/pic-default.png">
            </if>
			<div class="font_size16 align_center">佣金的订单</div>
			<div class="reward">+{pigcms{$money|floatval}</div>
			<div class="detail">
				<div>
					<span class="left">当前状态</span>
					<span class="right">支付成功</span>
				</div>
				<div>
					<span class="left">支付方式</span>
					<span class="right">余额支付</span>
				</div>
				<div>
					<span class="left">转账时间</span>
					<span class="right">{pigcms{$time|date='Y-m-d H:i',###}</span>
				</div>
				<div>
					<span class="left">转账单号</span>
					<span class="right">{pigcms{$pigcms_id}</span>
				</div>
			</div>
		</div>
	</body>
</html>