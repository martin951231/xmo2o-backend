<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>余额充值</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
	<style>
		.btn{background-color:#44cec1;}
		.recharge_card{    
			color: #06c1ae;
			border: #06c1ae 1px solid;    
			display: inline-block;
		    border-radius: 5px;
		    padding: 1px 3px;
		    font-size: 10px;   
		     margin-left: 25px;
		}
		#card_password{
			height:40px;
			padding:4px 12px;
			padding-left:5px;
			font-size:16px;
			line-height:40px;
			border: 0;
    		width: 100%;
		}
	</style>
</head>
<body id="index">
	<div id="widget_container">
		<if condition="$error">
			<div id="tips" class="tips tips-err" style="display:block;">{pigcms{$error}</div>
		<else/>
			<div id="tips" class="tips"></div>
		</if>
		<form id="form" method="post" action="{pigcms{:U('My/do_recharge_card')}">
			<input id="card_password" placeholder="请填写充值卡秘钥" type="text" name="card_password" value=""/>
			<input id="card_id"  type="hidden" name="card_id" value="0"/>
			
			<p class="btn-wrapper msg_content" style="display: none;">
				充值金额：<span class="momey"></span>
			</p>
			<div class="btn-wrapper"><button type="submit" class="btn btn-block btn-larger submit">充值</button></div>
		</form>
	</div>
	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
	<script src="{pigcms{$static_path}layer/layer.m.js"></script>
	<script>
		$(function(){
			$('#card_password').keyup(function(){
				get_recharge_card();
			})
			$('#card_password').onblur(function(){
				get_recharge_card();
			})

			function get_recharge_card(){
				$.post("{pigcms{:U('get_recharge_card')}",$('#form').serialize(),function(result){
					if(result.code == 1){
						$('.momey').html('');
						$('.msg_content').hide();
					}else{
						$('.momey').html(result.data.money).show();
						$('.msg_content').show();
					}
				},'json');
			}
		})
	</script>
	{pigcms{$hideScript}
</body>
</html>