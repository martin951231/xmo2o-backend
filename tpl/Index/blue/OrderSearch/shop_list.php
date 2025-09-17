<!doctype html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=Edge">
<title>{pigcms{$config.shop_alias_name}订单| {pigcms{$config.site_name}</title>
<meta name="keywords" content="{pigcms{$config.seo_keywords}" />
<meta name="description" content="{pigcms{$config.seo_description}" />
<link href="{pigcms{$static_path}css/css.css" type="text/css"  rel="stylesheet" />
<link href="{pigcms{$static_path}css/header.css"  rel="stylesheet"  type="text/css" />
<link href="{pigcms{$static_path}css/meal_order_list.css"  rel="stylesheet"  type="text/css" />
<script src="{pigcms{$static_path}js/jquery-1.7.2.js"></script>
<script src="{pigcms{$static_public}js/jquery.lazyload.js"></script>
	<script type="text/javascript">
	   var  shop_alias_name = "{pigcms{$config.shop_alias_name}";
	</script>
<script src="{pigcms{$static_path}js/common.js"></script>
<script src="{pigcms{$static_path}js/category.js"></script>
<!--[if IE 6]>
<script  src="{pigcms{$static_path}js/DD_belatedPNG_0.0.8a.js" mce_src="{pigcms{$static_path}js/DD_belatedPNG_0.0.8a.js"></script>
<script type="text/javascript">
   DD_belatedPNG.fix('.enter,.enter a,.enter a:hover');
</script>
<script type="text/javascript">DD_belatedPNG.fix('*');</script>
<style type="text/css"> 
body{behavior:url("{pigcms{$static_path}css/csshover.htc");}
.category_list li:hover .bmbox {filter:alpha(opacity=50);}
.gd_box{display: none;}
</style>
<![endif]-->
<style>
	.body{
		width:100%;
	}
	#bd{
		width:100%;
	}
	.has-order-nav #content {
		width: 100%;
	}
	table{margin-bottom:10px;width:100%;}
	table tr,table th{background: url(img/hr.png) 0 bottom repeat-x;}
	table td{padding:7px 10px 9px 15px;color:#666;vertical-align:top;line-height:24px;}
	table th{padding:0px 10px 0px;background-color:#f8f8f8;border-right:1px solid #e5e3e3;font-weight:100;line-height:24px;vertical-align:top;text-align:left;}
	table tr:hover th{background-color:#eef3f6;}
	
	@charset "UTF-8";.title-block {    height: 26px;    text-align: center;}.block {    display: -webkit-box;    display: -ms-flexbox;    display: flex;    -webkit-box-orient: vertical;    -webkit-box-direction: reverse;    -ms-flex-flow: column-reverse nowrap;    flex-flow: column-reverse nowrap;    width: 360px;    margin: 0 auto;    font-size: 14px;    color: #4C4C4C;    line-height: 28px;    /*border: 1px solid #999;*/    padding: 10px;}.block > .detail {    max-height: 163px;    margin-bottom: 10px;    overflow: hidden;}.block > .case-block {    display: -webkit-box;    display: -ms-flexbox;    display: flex;    -webkit-box-pack: center;    -ms-flex-pack: center;    justify-content: center;    -webkit-box-align: center;    -ms-flex-align: center;    align-items: center;    width: 120px;    height: 24px;    margin: 0 auto 0;    color: #0e0e0e;    background: #fff;    border: 1px solid #0e0e0e;    border-radius: 5px;}.block > .case-block div:nth-of-type(1) {    display: block;}.block > .case-block div:nth-of-type(2) {    display: none;}.block > [type="checkbox"] {    position: relative;    display: block;    width: 120px;    height: 24px;    margin: -24px auto 0;    z-index: 1000;    opacity: 0;}.block > [type="checkbox"]:hover + .case-block {    background-color: #f5f5f5;}.block > [type="checkbox"]:checked + .case-block div:nth-of-type(1) {    display: none;}.block > [type="checkbox"]:checked + .case-block div:nth-of-type(2) {    display: block;}.block > [type="checkbox"]:checked + .case-block + .detail {    max-height: inherit;}
	.detail th{
		background-color:#ccc;
	}
	.has-order-nav #content .mainbox {
		min-height: 100%;
	}
	#nav.mainnav_title {    border-bottom: 1px solid #e5e5e5;
    margin-bottom: 10px;color:white;}
#nav.mainnav_title a {    padding: 0;
    height: 48px;
    padding: 0 20px;
    min-width: 0;
    border: none;
    background: transparent;
    font-size: 14px;
    color: #666666;
	line-height: 46px;
	    display: inline-block;}
#nav.mainnav_title a.on{border-bottom: 2px solid #0099FC;text-decoration: none;    color: #3283FA;}
#nav.mainnav_title a:hover{color:#3283FA;text-decoration: none;}

</style>
<script src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
</head>
<body id="orders" class="has-order-nav" style="position:static;">

 <div class="body pg-buy-process"> 
	<div id="doc" class="bg-for-new-index">
		<div id="bdw" class="bdw">
			<div id="bd" class="cf">
				<div id="content" class="coupons-box">
					<div class="mainbox mine">
						
						<div class="orders-wrapper" id="order-list">
						<div id="nav" class="mainnav_title">
							<ul>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=shop_list&phone={pigcms{$_GET['phone']}"  class="on">{pigcms{$config.shop_alias_name}订单</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=service_list&phone={pigcms{$_GET['phone']}" >帮我买帮我送</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=store_list&phone={pigcms{$_GET['phone']}" >店内消费</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=group_list&phone={pigcms{$_GET['phone']}" >{pigcms{$config.group_alias_name}订单</a>
								<a href="{pigcms{$config['site_url']}/index.php?c=OrderSearch&a=meal_list&token={pigcms{$_GET['token']}&phone={pigcms{$_GET['phone']}">{pigcms{$config.meal_alias_name}订单</a>
							</ul>
						</div>
						<php>if($list){</php>
							<volist name="list" id="vl">
							
							<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" <if condition="$key gt 0 AND !isset($_GET['page'])">style="display:none;"</if>>
								<tr>
									<th colspan="1">订单编号</th>
									<th colspan="6">{pigcms{$vl['real_orderid']}</th>
								</tr>
								<if condition="$vl.orderid neq 0">
								<tr>
									<th colspan="1">流水号</th>
									<th colspan="6"><if condition="$vl['pay_type'] neq 'baidu'">shop_</if>{pigcms{$vl['orderid']}</th>
								</tr>
								</if>
								<if condition="$vl['shop_pass']">
								<tr>
									<th colspan="1">消费码</th>
									<th colspan="6">{pigcms{$vl['shop_pass']}</th>
								</tr>
								</if>
								<tr>
									<th width="180">商品名称</th>
									<th><strong>原价</strong></th>
									<th><strong>优惠类型</strong></th>
									<th><strong>优惠率</strong></th>
									<th><strong>优惠价</strong></th>
									<th>数量</th>
									<th>规格属性详情</th>
								</tr>
								<volist name="vl['info']" id="rowset">
								<if condition="!empty($rowset['name'])">
								<tr><td colspan="7">{pigcms{$rowset['name']}</td></tr>
								</if>
								<volist name="rowset['list']" id="vo">
								<tr class="detail"> 
									<th width="180">{pigcms{$vo['name']}</th>
									<th style="color:#9F0050">{pigcms{$vo['price']|floatval}<if condition="$config.open_extra_price eq 1 AND $vo.extra_price gt 0">+{pigcms{$vo.extra_price}{pigcms{$config.extra_price_alias_name}</if></th>
									<th style="color:#9F0050"><php>if ($vo['discount_type'] == 1 || $vo['discount_type'] == 4) {echo '店铺折扣';} elseif ($vo['discount_type'] == 2 || $vo['discount_type'] == 5) {echo '分类折扣';} elseif ($vo['discount_type'] == 3) {echo 'VIP折扣';}</php></th>
									<th style="color:#9F0050"><strong><php>if ($vo['discount_rate']) echo $vo['discount_rate']/10;</php></strong></th>
									<th style="color:#9F0050"><strong><php>if ($vo['discount_price']) { echo floatval($vo['discount_price']); } else { echo floatval($vo['price']); }</php></strong></th>
									<th style="color:#9F0050"><strong>{pigcms{$vo['num']}</strong> ({pigcms{$vo['unit']})</th>
									<th>{pigcms{$vo['spec']}</th>
								</tr>
								</volist>
								</volist>
								<if condition="$vl['merchant']">
								<tr>
									<th colspan="7">商家名称：{pigcms{$vl['merchant']['name']}</th>
								</tr>
							
								</if>
								
									<if condition="$vl['store_']">
								<tr>
									<th colspan="2">店铺名称：{pigcms{$vl['store_']['name']}</th>
									<th colspan="2">店铺电话：{pigcms{$vl['store_']['phone']}</th>
									<th colspan="3"></th>
								</tr>
							
								</if>
							
								<if condition="$vl['deliver_user']">
								<tr>
									<th colspan="2">配送员姓名：{pigcms{$vl['deliver_user']['name']}</th>
								
									<th colspan="2">配送员手机号：{pigcms{$vl['deliver_user']['phone']}</th>
									<th colspan="3"></th>
								</tr>
								</if>
								<if condition="$vl['username']">
								<tr>
									<th colspan="7">客户姓名：{pigcms{$vl['username']}</th>
								</tr>
								</if>
								<if condition="$vl['userphone']">
								<tr>
									<th colspan="7">客户手机：{pigcms{$vl['userphone']}</th>
								</tr>
								</if>
								
								<if condition="$vl['register_phone']">
								<tr>
									<th colspan="7" style="color:red">客户注册手机：{pigcms{$vl['register_phone']}</th>
								</tr>
								</if>
								<php>if ($vl['order_from'] != 6) {</php>
								<if condition="$vl['is_pick_in_store'] eq 2">
								<tr>
									<th colspan="7">自提地址：{pigcms{$vl['address']}</th>
								</tr>
								<else />
								<tr>
									<th colspan="7">客户地址：{pigcms{$vl['address']}</th>
								</tr>
								</if>
								<tr>
									<th colspan="7">配送方式：{pigcms{$vl['deliver_str']}</th>
								</tr>
								<tr>
									<th colspan="7">配送状态：{pigcms{$vl['deliver_status_str']}</th>
								</tr>
								<if condition="$vl['is_pick_in_store'] eq 3 AND $vl['express_id']">
								<tr>
									<th colspan="7">快递公司：{pigcms{$vl['express_name']}</th>
								</tr>
								<tr>
									<th colspan="7">快递单号：{pigcms{$vl['express_number']}</th>
								</tr>
								</if>
								<if condition="$vl['deliver_user_info']">
								<tr>
									<th colspan="7">配送员姓名：{pigcms{$vl['deliver_user_info']['name']}</th>
								</tr>
								<tr>
									<th colspan="7">配送员电话：{pigcms{$vl['deliver_user_info']['phone']}</th>
								</tr>
								</if>
								<php>} else {</php>
								<tr>
									<th colspan="7">订单来源：线下零售 </th>
								</tr>
								<php>}</php>
								<tr>
									<th colspan="7">下单时间：{pigcms{$vl['create_time']|date="Y-m-d H:i:s",###} </th>
								</tr>
								<if condition="$vl['pay_time']">
								<tr>
									<th colspan="7">支付时间：{pigcms{$vl['pay_time']|date="Y-m-d H:i:s",###} </th>
								</tr>
								</if>
								<if condition="$vl['expect_use_time']">
								<tr>
									<th colspan="7">期望到货时间：{pigcms{$vl['expect_use_time']|date="Y-m-d H:i:s",###}</th>
								</tr>
								</if>
								<if condition="$vl['use_time']">
								<tr>
									<th colspan="7">送达时间：{pigcms{$vl['use_time']|date="Y-m-d H:i:s",###}</th>
								</tr>
								</if>
								<if condition="$vl['supplys']['confirm_time']">
									<tr>
										<th colspan="7">店员接单时长：{pigcms{$vl['supplys']['confirm_time']}</th>
									</tr>
								</if>
								<if condition="$vl['supplys']['order_time']">
									<tr>
										<th colspan="7">店员接单时间：{pigcms{$vl['supplys']['order_time']|date="Y-m-d H:i:s",###}</th>
									</tr>
								</if>
								<if condition="$vl['supplys']['grab_time']">
									<tr>
										<th colspan="7">配送员接单时长：{pigcms{$vl['supplys']['grab_time']}</th>
									</tr>
								</if>
								<if condition="$vl['supplys']['start_time']">
									<tr>
										<th colspan="7">配送员接单时间：{pigcms{$vl['supplys']['start_time']|date="Y-m-d H:i:s",###}</th>
									</tr>
								</if>
								<if condition="$vl['supplys']['deliver_use_time']">
									<tr>
										<th colspan="7">配送时长：{pigcms{$vl['supplys']['deliver_use_time']}</th>
									</tr>
								</if>
							<!--    <if condition="$supplys['end_time']">-->
							<!--        <tr>-->
							<!--            <th colspan="7">配送结束时间：{pigcms{$supplys['end_time']|date="Y-m-d H:i:s",###}</th>-->
							<!--        </tr>-->
							<!--    </if>-->
								<!-- deliver_time_info -->
								<if condition="$vl.delivery_times">
									<volist name="vl.delivery_times" id = "time">
										<tr>
											<th colspan="7">
												<if condition="$time['status'] eq 3">配送员接单时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
													<elseif condition="$time['status'] eq 4" />配送员取货时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
													<elseif condition="$time['status'] eq 5" />配送员开始配送时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
													<elseif condition="$time['status'] eq 6" />配送员送达时间：{pigcms{$time['dateline']|date="Y-m-d H:i:s",###}
												</if>
											</th>
										</tr>
									</volist>
								</if>
								<!-- end -->
								<tr>
									<th colspan="7">商品总价：{pigcms{$config['Currency_symbol']}{pigcms{$vl['goods_price']|floatval} {pigcms{$config['Currency_txt']}<if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">+{pigcms{$now_order.extra_price}{pigcms{$config.extra_price_alias_name}</if></th>
								</tr>
								<if condition="$vl['packing_charge'] gt 0">
								<tr>
									<th colspan="7">{pigcms{$store['pack_alias']|default='打包费'}：{pigcms{$config['Currency_symbol']}{pigcms{$vl['packing_charge']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['freight_charge'] gt 0">
								<tr>
									<th colspan="7">{pigcms{$store['freight_alias']|default='配送费用'}：{pigcms{$config['Currency_symbol']}{pigcms{$vl['freight_charge']|floatval} {pigcms{$config['Currency_txt']}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<if condition="$vl['distance'] gt 0">（配送距离：<font color="red">{pigcms{:round($vl['distance']/1000,2)} 公里</font>）【若设置为骑行距离、则是由地图接口提供，会根据当时路况等变化】</if></th>
								</tr>
								</if>
								<if condition="$vl['other_money'] gt 0">
								<tr>
									<th colspan="7">加价送的金额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['other_money']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<tr>
									<th colspan="7">订单总价：{pigcms{$config['Currency_symbol']}{pigcms{$vl['total_price']|floatval} {pigcms{$config['Currency_txt']}<if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">+{pigcms{$now_order.extra_price}{pigcms{$config.extra_price_alias_name}</if></th>
								</tr>
								
								<php>if (!empty($vl['discount_detail'])) {</php>
								<volist name="vl['discount_detail']" id="detail">
								<tr>
									<th colspan="7">优惠信息：
									<if condition="$detail['discount_type'] eq 1">平台首单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) .'</b>)';}</php>
									<elseif condition="$detail['discount_type'] eq 2" />平台满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
									<elseif condition="$detail['discount_type'] eq 3" />商家首单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
									<elseif condition="$detail['discount_type'] eq 4" />商家满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
									<elseif condition="$detail['discount_type'] eq 5" />平台配送订单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b> <php>if ($detail['plat_money'] > 0 || $detail['merchant_money'] > 0) {echo '(平台补贴:<b style="color: red">' . floatval($detail['plat_money']) . '</b>商家补贴:<b style="color: red">' . floatval($detail['merchant_money']) . '</b>)';}</php>
									<elseif condition="$detail['discount_type'] eq 6" />商家配送订单满<b style="color: red">{pigcms{$detail['money']|floatval}</b>减<b style="color: red">{pigcms{$detail['minus']|floatval}</b>
									</if>
									</th>
								</tr>
								</volist>
								<php>} else {</php>
								<if condition="$vl['merchant_reduce'] gt 0">
								<tr>
									<th colspan="7">店铺优惠：{pigcms{$config['Currency_symbol']}{pigcms{$vl['merchant_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['balance_reduce'] gt 0">
								<tr>
									<th colspan="7">平台优惠：{pigcms{$config['Currency_symbol']}{pigcms{$vl['balance_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<php>}</php>
								<if condition="$vl['card_discount'] neq 0 AND $vl['card_discount'] neq 10">
								<tr>
									<th colspan="7">会员卡：{pigcms{$vl['card_discount']|floatval} 折优惠</th>
								</tr>
								</if>
								<tr>
									<th colspan="7">实付金额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['price']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								<if condition="$vl['score_used_count']">
								<tr>
									<th colspan="7">使用{pigcms{$config.score_name}：{pigcms{$vl['score_used_count']} </th>
								</tr>
								<tr>
									<th colspan="7">{pigcms{$config.score_name}抵现：{pigcms{$config['Currency_symbol']}{pigcms{$vl['score_deducte']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
										
								<if condition="$vl['card_give_money'] gt 0">
								<tr>
									<th colspan="7">会员卡余额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['card_give_money']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								
								<if condition="$vl['merchant_balance'] gt 0">
								<tr>
									<th colspan="7">商家余额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['balance_pay'] gt 0">
								<tr>
									<th colspan="7">平台余额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['payment_money'] gt 0">
								<tr>
									<th colspan="7">在线支付：{pigcms{$config['Currency_symbol']}{pigcms{$vl['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['card_id']">
								<tr>
									<th colspan="7">店铺优惠券金额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['card_price']} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['coupon_id']">
								<tr>
									<th colspan="7">平台优惠券金额：{pigcms{$config['Currency_symbol']}{pigcms{$vl['coupon_price']} {pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<if condition="$vl['pay_type'] eq 'offline' AND empty($vl['third_id'])">
								<tr>
									<th colspan="7">线下需支付：{pigcms{$config['Currency_symbol']}{pigcms{$vl['price']-$vl['card_price']-$vl['merchant_balance']-$vl['balance_pay']-$vl['payment_money']-$vl['score_deducte']-$vl['coupon_price']|floatval}{pigcms{$config['Currency_txt']}</th>
								</tr>
								</if>
								<tr>
									<th colspan="7">支付状态：{pigcms{$vl['pay_status']}</th>
								</tr>
								<tr>
									<th colspan="7">支付方式：{pigcms{$vl['pay_type_str']}</th>
								</tr>
								<tr>
									<th colspan="7">订单状态：{pigcms{$vl['status_str']}<if condition="$vl['status'] eq 4">&nbsp;&nbsp;&nbsp;&nbsp;退款时间:{pigcms{$vl['last_time']|date="Y-m-d H:i:s",###}</if></th>
								</tr>
								<?php if(!empty($refund_list)){ ?>
									<table cellpadding="0" cellspacing="0" class="frame_form" width="100%" style="margin-top:38px;">
										<tr>
											<th colspan="4"><strong>退货信息</strong></th>
										</tr>
										<volist name="refund_list" id="rowset">
										<tr>
											<th colspan="4"><strong>退货商品</strong></th>
										</tr>
										<tr>
											<th><strong>商品名称</strong></th>
											<th><strong>单价</strong></th>
											<th><strong>数量</strong></th>
											<th><strong>规格属性详情</strong></th>
										</tr>
										<volist name="rowset['goodsList']" id="vvo">
											<tr>
												<th style="color:#9F0050">{pigcms{$vvo['name']}</th>
												<th style="color:#9F0050">{pigcms{$vvo['price']|floatval}</th>
												<th style="color:#9F0050"><strong>{pigcms{$vvo['num']}</strong><php>if ($vvo['unit']) {</php> ({pigcms{$vvo['unit']})<php>}</php></th>
												<th style="color:#9F0050">{pigcms{$vvo['spec']}</th>
											</tr>
										</volist>

										<tr>
											<th colspan="4">退款备注: <strong style="color:red">{pigcms{$rowset['reason']}</strong></th>
										</tr>

										<tr>
											<th colspan="4">退款金额: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$rowset['price']} 不含配送费、打包费、优惠金额</strong></th>
										</tr>
										<tr>
											<th colspan="4">退款凭证:
												<ul class="imgs" style="display:inline">
													<php>foreach ($rowset['image'] as $image) {</php>
													<li style="display:inline">
														<img src="{pigcms{$image}" height="50" class="refundImg"/>
													</li>
													<php>}</php>
												</ul>
											</th>
										</tr>

										<tr>
											<th colspan="4">退款状态:
												{pigcms{$rowset.showStatus}
												<if condition="$rowset.reply_content neq ''">
													&nbsp;&nbsp;&nbsp;&nbsp;理由：<span style="color: red;font-weight: bold;">{pigcms{$rowset.reply_content}</span>
												</if>
												&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{$rowset.reply_time|date="Y-m-d H:i",###}
											</th>
										</tr>
										</volist>
									</table>
								<?php } ?>
								<tr>
									<th colspan="7">备注:{pigcms{$vl['desc']|default="无"}</th>
								</tr>
								<if condition="$vl['invoice_head']">
									<tr>
										<th colspan="7">发票抬头:{pigcms{$vl['invoice_head']}</th>
									</tr>
								</if>
								<if condition="$vl['cue_field']">
									<tr>
										<th colspan="7">&nbsp;</th>
									</tr>
									<tr>
										<th colspan="7"><strong>分类填写字段</strong></th>
									</tr>
									<volist name="vl['cue_field']" id="vvvo">
										<tr>
											<th colspan="1">{pigcms{$vvvo.title}</th>
											<th colspan="6">{pigcms{$vvvo.txt}</th>
										</tr>
									</volist>
								</if>
							</table>
									
							</volist>
											<php>}else{</php>
												没有数据
											<php>}</php>
                        </div>
						{pigcms{$pagebar}
					</div>
					<if condition="count($list) gt 1 AND !isset($_GET['page'])">
					<section class="block">    
						<input type="checkbox" class="explode">    
						<div class="case-block">      
							<div >展开</div>      
							<div >收起</div>   
						</div>    
					</section>
					</if>
				</div>
			</div> <!-- bd end -->
		</div>
	</div>	
</div>

<script type="text/javascript">
	<if condition="!isset($_GET['page'])">
		var flag = false;
	</if>
	$(document).ready(function(){
		$("#select_status").change(function(data){
			location.href='{pigcms{:U("User/Index/shop_list")}&status=' + $(this).val();
		});
		if(!flag){
			$('.paginator-wrapper').hide();
		}
		
		$('.explode').click(function(){
			if(flag==false){
				$('.paginator-wrapper').show();
				$('#order-list table').show();
				flag = true;
			}else{
				$('#order-list table').hide();
				$('.paginator-wrapper').hide();
				$('#order-list table:first').show();
				flag = false;
			}
			
		})
	});
	
	
	function shop_order_cancel(order_id){
			if(confirm('确认取消该订单？')){
				var cancelUrl = "{pigcms{:U('shop_order_check_refund')}";
				cancelUrl += "&order_id="+order_id;
				location.href = cancelUrl;
			}
			
		}
    
    function shop_order_finish(order_id){
        if(confirm('您确定已经收到货物了？')){
            var cancelUrl = "{pigcms{:U('finishOrder')}";
            cancelUrl += "&order_id="+order_id;
            location.href = cancelUrl;
        }
    }
</script>
</body>
</html>
