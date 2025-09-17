<!DOCTYPE html>
<html lang="zh-CN">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
	<meta http-equiv="pragma" content="no-cache"/>
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<meta name='apple-touch-fullscreen' content='yes'/>
	<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
	<meta name="format-detection" content="telephone=no"/>
	<meta name="format-detection" content="address=no"/> 
	<title>充值详情</title> 
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}css/weui.min.css"/>
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}css/idangerous.swiper.css"/> 
	<link type="text/css" rel="stylesheet" href="{pigcms{$static_path}my_card/css/card_new.css"/>
	<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>  
	<script src="{pigcms{$static_path}js/weui.min.js"></script>
	<script src="{pigcms{$static_path}js/idangerous.swiper.min.js"></script> 
	<!--[if lte IE 9]>
	<script src="scripts/html5shiv.min.js"></script>
	<![endif]-->  
</head>
<body style="background: #fff; zoom: 0;">
	 <div class="top_header">
		 <div class="go_backFF" onClick="javascript:history.back()"></div> 
		 <span class="fp_til">充值详情</span> 
	 </div>
	 <div class="chongzhiTop">
		 <div class="chongzhiTopTil">+<label>{pigcms{$info.pay_money}</label></div>
		 <p>老乡鸡</p>
	 </div>
	 
	 <ul class="ul_detailsList">
		 <li>当前状态<span>充值成功</span></li>
		 <li>商品名称<span>{pigcms{$info.order_name}</span></li>
		 <li>充值金额<span>￥ {pigcms{$info.pay_money}</span></li>
		 <li>获得饭票<span>{pigcms{$info.pay_money}元</span></li>
		 <li>获得米粒<span>{pigcms{$info.pay_money}元</span></li>
		 <li>支付方式<span>{pigcms{$info.pay_type_str}</span></li>
		 <li>交易单号<span>{pigcms{$info.cr_order_id}</span></li>
		 <li>充值时间<span>{pigcms{$info.pay_time|date="Y-m-d H:i",###}</span></li>
	 </ul> 
	 
	 <ul class="ul_linkList">
		 <li><a href="{pigcms{:U('merchant_prepay',array('mer_id'=>$info['mer_id']))}">继续充值</a></li>
		 <li><a href="{pigcms{$info.kefu_url}">联系客服</a></li>
		 <li><a href="{pigcms{:U('Mall/home')}">优惠活动</a></li> 
	 </ul>
</body> 
<script>  
</script> 
</html>