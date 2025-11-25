<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>订单评价</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}villagegroup/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/common.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css"/>
		<style type="text/css">
			body{
				padding-bottom: 60px;
			}
			.goods{
				border-bottom: 1px solid #e5e5e5;
				font-size: 14px;
				color: #333;
			}

			.goods img{
				width: 30px;
				height: 30px;
				border-radius: 2px;
				margin-right: 10px;
			}

			.goods .name{
				width: calc(100% - 40px);

			}

			.reply-box>div{
				width: 33.3333%;
				text-align: center;
				font-size: 14px;
				color: #333;
			}
			.reply-box>div:last-child{
				margin-right: 0;
			}
			.reply-box img{
				width: 40%;
				margin: 0 auto;
			}
			.gap{
				background: #f4f4f4;
				height: 10px;
				width: 100%;
			}
			.item:last-child .gap{
				height: 0;
			}

			.btn-wrapper{
				position: fixed;
				bottom: 0;
				left: 0;
				right: 0;
				height: 60px;
				padding: 10px;
				box-sizing: border-box;
			}

			.btn-wrapper button{
				width: 100%;
				height: 100%;
				background: #fe3112;
				color: #fff;
				outline: none;
				border: none;
				border-radius: 4px;
			}

			.btn-wrapper button:active{
				background: #f22203;
			}

			.reply-box img{
				width: 40px;
				height: 40px;
			}
		</style>
	</head>
	<body>
		<ul>
			<form id="form">
				<input type="hidden" name="order_id" value="{pigcms{$order.order_id}">
				<volist name="order.order_detail" id="vo">
				<li class="item bg-f ">
					<div class="flex pd10 goods">
						<img src="{pigcms{$vo.image}">
						<span class="name">{pigcms{$vo['name']}</span></div>
					<div class="flex pd10 reply-box">
						<div class="not-good" data-level="1" data-id="{pigcms{$vo['id']}">
							<img src="{pigcms{$static_path}villagegroup/images/not-good-no.png">
							<div>一般</div>
						</div>
						<div class="good" data-level="2" data-id="{pigcms{$vo['id']}">
							<img src="{pigcms{$static_path}villagegroup/images/good-no.png">
							<div>好</div>
						</div>
						<div class="very-good" data-level="3" data-id="{pigcms{$vo['id']}">
							<img src="{pigcms{$static_path}villagegroup/images/very-good.png">
							<div>非常好</div>
						</div>
						<input type="hidden" name="order_ids[]" value="{pigcms{$vo['id']}" class="order_ids_val">
						<input type="hidden" name="level[]" value="3" class="level_val">
					</div>
					<div class="gap"></div>
				</li>
				</volist>
			</form>
		</ul>

		<div class="btn-wrapper submit"><button>提交</button></div>

		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}villagegroup/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		{pigcms{$hideScript}
		<script type="text/javascript">
			$(function(){
				$(document).on('click', '.item .reply-box>div', function(){
					$(this).parents('.item').find('.reply-box>div').each(function(index){
						$(this).find('img').attr('src', '{pigcms{$static_path}villagegroup/images/'+ $(this).attr('class') +'-no.png')
					})
					$(this).find('img').attr('src', '{pigcms{$static_path}villagegroup/images/'+ $(this).attr('class') +'.png');
					$(this).siblings('input[name="level[]"]').val($(this).data('level'))
				})

				$(document).on('click', '.submit', function(){
					var order_ids = [];
					var level = [];
					var data = {order_ids:order_ids,level:level};
					data.order_id = '{pigcms{$order.order_id}';
					$.post('{pigcms{:U("reply_save")}',$('#form').serialize(),function(data){
						motify.log(data.msg);
						if (data.code==0) {
							setTimeout(function(){
								window.history.back();
							},2000)
						}
					},'json')
				})
			})
		</script>
	</body>
</html>