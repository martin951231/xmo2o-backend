<?php if (!defined('PigCms_VERSION')) {
	exit('deny access!');
} ?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
	<meta charset="utf-8" />
	<title>输入密码</title>
	<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width" />
	<meta name="apple-mobile-web-app-capable" content="yes" />
	<meta name='apple-touch-fullscreen' content='yes' />
	<meta name="apple-mobile-web-app-status-bar-style" content="black" />
	<meta name="format-detection" content="telephone=no" />
	<meta name="format-detection" content="address=no" />
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/base.css" />
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}js/common.js" charset="utf-8"></script>
	<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js" charset="utf-8"></script>

	<style>
		html,
		body {
			width: 100%;
			height: 100%;
		}

		.scopeWrap {
			width: 100%;
			height: 100%;
			font-size: 14px;
			line-height: 2;
			padding: 15px;
			box-sizing: border-box;
		}

		.scopeWrap .br-e4 {
			height: 35px;
			width: 100%;
			margin-bottom: 15px;
			padding: 0 10px;
			box-sizing: border-box;
		}

		.scopeWrap .btnWrap {
			position: absolute;
			bottom: 0;
			left: 15px;
			right: 15px;
			padding-bottom: 15px;
		}

		.scopeWrap .btnWrap .btn {
			background: #3D8AF2;
			width: 100%;
			height: 40px;
			border-radius: 4px;
		}

		.payPsdWrap {
			text-align: center;
			margin-top: 50%;
		}

		.passwordWrap {
			position: relative;
			font-size: 0;
			text-align: center;
		}

		.passwordWrap .border {
			width: 40px;
			height: 40px;
			display: inline-block;
			border-left: 1px solid #e4e4e4;
			border-top: 1px solid #e4e4e4;
			border-bottom: 1px solid #e4e4e4;
			font-size: 20px;
			font-weight: bold;
			color: #000;
			box-sizing: border-box;
			vertical-align: middle;
			line-height: 36px;
		}

		.passwordWrap .border:last-child {
			border-right: 1px solid #e4e4e4;
		}

		.passwordWrap #password {
			position: absolute;
			top: -10000000px;
		}
	</style>
</head>

<body>

	<div class="bg-fff pr scopeWrap">
		<!-- 输入账号密码 -->
		<div class="loginWrap" <if condition="$_GET['type'] neq 'authorization'">style="display: none;"</if>>
			<div>当前账号{pigcms{$user_session['nickname']}申请智慧仙桃支付权限</div>
			<form action="">
				<div>
					
					<input class="mt5 br-e4 phone" type="number" placeholder="请输入登录手机号" value='{pigcms{$user_session['phone']}' disabled="">
					
				</div>
				<div>
					<input class="br-e4 loginPsd" type="password" placeholder="请输入智慧仙桃登录密码">
				</div>
				<div class="btnWrap">
					<input class="white btn" type="button" value="确认">
				</div>
			</form>
		</div>

		<!-- 输入密码 -->
		<div class="payPsdWrap" <if condition="$_GET['type'] neq 'pay_pwd'">style="display: none;"</if>>
			<div>请输入智慧仙桃电子卡支付密码</div>
			<div class="f30 c-000">¥{pigcms{$_GET['pay_money']}</div>
			<div class="passwordWrap">
				<input type="password" id="password" maxlength="6" autofocus="autofocus">
				<label for="password" class="border"></label>
				<label for="password" class="border"></label>
				<label for="password" class="border"></label>
				<label for="password" class="border"></label>
				<label for="password" class="border"></label>
				<label for="password" class="border"></label>
			</div>
		</div>

	</div>


	<script type="text/javascript">
		$(function() {

			$('.loginWrap .btn').on('click',function() {
				var params = {
					password: $('.loginPsd').val(),
					phone: $('.phone').val()
				}
				var layerLoading = layer.open({type: 2});
				$.ajax({
					url: "{pigcms{:U('toAuthorization')}",
					dataType: "json",
					data: params,
					type: "post",
					success: function(data) {
						layer.close(layerLoading);
						if(data.error_code > 0){// 失败
							layer.open({type:0,content:data.msg});
						}else{
							layer.open({
								content:'授权成功'
								,btn: ['确定']
							    ,yes: function(index){
									history.back()
							      	layer.close(index);
							    }
							});
						}
					}
				})
			})

			$('#password').on('input', function() {

				var value = $(this).val()
				var valueArr = value.split('')
				var className = ".passwordWrap .border:nth-child(" + (valueArr.length + 1) + ")"
				
				$('.border').each(function(index, item) {
					if (index < valueArr.length) {
						$(item).text('·')
					} else {
						$(item).text('')
					}
				})


				var params = {
					password: value
				}
				if (valueArr.length == 6) {


					$.ajax({
						url: "{pigcms{:U('savePassword', $_GET)}",
						dataType: "json",
						data: params,
						type: "post",
						success: function(data) {
							if(data.status == 0){// 失败
								layer.open({type:0,content:data.info});
							}else{// 跳转支付

								window.location.href = data.url
							}
						}
					})
				}
			})
		})
	</script>

</body>

</html>