<!DOCTYPE html>
<html lang="zh-CN">
<head>
<title>支付成功</title>
<meta charset="utf-8">
<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport" />
<meta content="application/xhtml+xml;charset=UTF-8"	http-equiv="Content-Type" />
<meta content="no-cache,must-revalidate" http-equiv="Cache-Control" />
<meta content="no-cache" http-equiv="pragma" />
<meta content="0" http-equiv="expires" />
<meta content="telephone=no, address=no" name="format-detection" />
<meta name="apple-mobile-web-app-capable" content="yes" />
<style type="text/css">
html,body {margin:0;padding:0;height: 100%;}
.success_box, .tip_txt, .money_box, .button_box{text-align:center;}
.container{
	display: none;
}
.success_box img{
	width:50px;
	height:50px;
}
.tip_txt{
	font-size:18px;
	font-weight: 500;
	margin:8px 0;
}
.money_box{
	font-size:24px;
	margin-bottom:15px;
	line-height:60px;
    display: flex;
	justify-content: center;
}
.money_box .current{
	vertical-align: top;
}
.money_box .money{
	font-size:40px;
	font-weight: 600;
}
.button_box button{
	border:1px solid {pigcms{$config.mobile_head_color};
	color:{pigcms{$config.mobile_head_color};
	outline:0;
	background:white;
	padding:0px 16px;
	height:32px;
	line-height:32px;
	border-radius:16px;
}
</style>
<script type="text/javascript" charset="UTF-8" src="https://wx.gtimg.com/pay_h5/goldplan/js/jgoldplan-1.0.0.js"></script>
<script type="text/javascript" charset="UTF-8" src="{pigcms{:C('JQUERY_FILE')}"></script>
<script>
	$(function(){
		
		//通知微信页面展示完成
		let mchData = {action: 'onIframeReady',displayStyle: 'SHOW_CUSTOM_PAGE'}
		let postData = JSON.stringify(mchData);
		parent.postMessage(postData, 'https://payapp.weixin.qq.com');
		
		
		//请求后端拿到价格和链接
		let post_data = {
			sub_mch_id: '{pigcms{$_GET.sub_mch_id}', 
			out_trade_no: '{pigcms{$_GET.out_trade_no}', 
			check_code: '{pigcms{$_GET.check_code}', 
			t: '{pigcms{$_GET.t}', 
		};
		console.log('post_data', post_data);
		$.post("{pigcms{$config.site_url}/v20/public/index.php/pay/pay/getTianqueInfo", post_data, function(result){
			let page_height = $(window).width() * 600 / 640;
			$('.container').css('padding', (page_height - $('.container').height()) / 2).show();
			
			//订单在线支付金额
			// $('.money_box .money').html(data.money);
			
			$('.money_box .money').html(result.data.money);
			//查看订单按钮点击事件
			$('.button_box button').click(function(){
				let mchData = {action: 'jumpOut', jumpOutUrl: result.data.url}
				let postData = JSON.stringify(mchData)
				parent.postMessage(postData, 'https://payapp.weixin.qq.com')
			});
		}, 'json');
		
	});
</script>
</head>
<body>
	<div class="container">
		<div class="success_box">
			<img src="{pigcms{$static_path}images/success.png"/>
		</div>
		<div class="tip_txt">支付成功</div>
		<div class="money_box">
			<span class="current">￥</span>
			<span class="money"></span>
		</div>
		<div class="button_box">
			<button>查看订单详情</button>
		</div>
	</div>
</body>
</html>