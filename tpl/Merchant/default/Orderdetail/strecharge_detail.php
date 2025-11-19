<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">{pigcms{:L_("订单编号")}</th>
		<th colspan="3">{pigcms{$order['order_id']}</th>
	</tr>
	<if condition="$order.orderid neq 0">
	<tr>
		<th colspan="1">{pigcms{:L_("流水号")}</th>
		<th colspan="3">store_{pigcms{$order['orderid']}</th>
	</tr>
	</if>
	<tr>
		<th colspan="1">{pigcms{:L_("名称")}</th>
		<th colspan="3">{pigcms{$order['name']}</th>
	</tr>
	
	
	<tr>
		<th colspan="4">{pigcms{:L_("客户姓名：")}{pigcms{$order['nickname']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("客户手机：")}{pigcms{$order['phone']}</th>
	</tr>
	
	<tr>
		<th colspan="4">{pigcms{:L_("下单时间：")}{pigcms{$order['dateline']|date="Y-m-d H:i:s",###} </th>
	</tr>
	<if condition="$order['pay_time']">
	<tr>
		<th colspan="4">{pigcms{:L_("支付时间：")}{pigcms{$order['pay_time']|date="Y-m-d H:i:s",###} </th>
	</tr>
	</if>
	
	<tr>
		<th colspan="4">{pigcms{:L_("商品总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("优惠费用：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['discount_price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("实际支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']+$order['merchant_balance']+$order['card_give_money']+$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	
	<if condition="$order['balance_pay']">
	<tr>
		<th colspan="4">{pigcms{:L_("平台余额支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['merchant_balance']">
	<tr>
		<th colspan="4">{pigcms{:L_("商家余额支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']+$order['card_give_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['payment_money']">
	<tr>
		<th colspan="4">{pigcms{:L_("在线支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['score_deducte']">
	<tr>
		<th colspan="4">{pigcms{:L_("平台")}{pigcms{$config['score_name']}{pigcms{:L_("抵扣：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['coupon_price']">
	<tr>
		<th colspan="4">{pigcms{:L_("使用平台优惠券：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['coupon_price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['card_id']">
	<tr>
		<th colspan="4">{pigcms{:L_("店铺优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['card_price']} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	
	<tr>
		<th colspan="4">{pigcms{:L_("支付方式：")}{pigcms{$pay_method}</th>
	</tr>
	
</table>
		
	</body>
</html>