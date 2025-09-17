<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	</head>
	<body>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th width="180">{pigcms{:L_("菜品名称")}</th>
		<th>{pigcms{:L_("单价")}</th>
		<th>{pigcms{:L_("数量")}</th>
		<th>{pigcms{:L_("菜品备注")}</th>
	</tr>
	<volist name="order['info']" id="vo">
	<tr>
		<th width="180">{pigcms{$vo['name']}</th>
		<th>{pigcms{$vo['price']}</th>
		<th>{pigcms{$vo['num']}</th>
		<th>{pigcms{$vo['omark']}</th>
	</tr>
	</volist>
	<tr>
		<th width="180">{pigcms{:L_("实际总价：")}{pigcms{$config['Currency_symbol']}<if condition="$order['total_price'] gt 0">{pigcms{$order['total_price']|floatval}<else />{pigcms{$order['price']|floatval}</if> {pigcms{$config['Currency_txt']}</th>
		<th>{pigcms{:L_("优惠金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['minus_price']|floatval} {pigcms{$config['Currency_txt']}</th>
		<th colspan="2">{pigcms{:L_("应付总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th>{pigcms{:L_("订单号")}</th>
		<th>{pigcms{$order['order_id']}</th>
		<if condition="$order.orderid neq 0">
			<th>{pigcms{:L_("流水号")}</th>
			<th><if condition="$order['pay_type'] neq 'baidu'"><if condition="$order['meal_type']" >takeout_<else />food_</if> </if>{pigcms{$order['orderid']}</th>
		</if>
	</tr>
	<tr>
		<th>{pigcms{:L_("订单状态：")}
		<if condition="$order['status'] eq 0">
		<strong style="color:green">{pigcms{:L_("未使用")}</strong>
		<elseif condition="$order['status'] eq 1" />
		<span style="color:green">{pigcms{:L_("已使用")}</span>
		<elseif condition="$order['status'] eq 2" />
		<span style="color:green">{pigcms{:L_("已评价")}</span>
		<elseif condition="$order['status'] eq 3" />
		<span style="color:red">{pigcms{:L_("已退款")}</span>
		<elseif condition="$order['status'] eq 4" />
		<span style="color:red">{pigcms{:L_("已取消")}</span>
		<else />
		<span style="color:red">{pigcms{:L_("订单失效")}</span>
		</if>
		</th>
		
		<th>{pigcms{:L_("支付状态")}:　
		<if condition="empty($order['paid'])">{pigcms{:L_("未支付")}
		<elseif condition="$order['pay_type'] eq 'offline' AND empty($order['third_id'])" />{pigcms{:L_("线下未支付")}
		<elseif condition="$order['paid'] eq 2"  />
		<span style="color:green">{pigcms{:L_("已付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']+$order['merchant_balance']+$order['coupon_price']+$order['card_price']+$order['score_deducte']|floatval}</span>，
		<span style="color:red">
		<if condition="$order['total_price'] gt 0">
		{pigcms{:L_("未付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']-$order['minus_price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}
		<else />
		{pigcms{:L_("未付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}
		</if>
		</span>
		<else /><span style="color:green">{pigcms{:L_("已支付")}</span>
		</if>
		</th>
		<th colspan="2">{pigcms{:L_("支付方式：")}
		<if condition="$order['pay_type'] eq 'alipay'">
		<span style="color:green">{pigcms{:L_("支付宝")}</span>
		<elseif condition="$order['pay_type'] eq 'weixin'"/>
		<span style="color:green">{pigcms{:L_("微信支付")}</span>
		<elseif condition="$order['pay_type'] eq 'tenpay'"/>
		<span style="color:green">{pigcms{:L_("财付通[wap手机")}]</span>
		<elseif condition="$order['pay_type'] eq 'tenpaycomputer'"/>
		<span style="color:green">{pigcms{:L_("财付通[即时到帐")}]</span>
		<elseif condition="$order['pay_type'] eq 'yeepay'"/>
		<span style="color:green">{pigcms{:L_("易宝支付")}</span>
		<elseif condition="$order['pay_type'] eq 'allinpay'"/>
		<span style="color:green">{pigcms{:L_("通联支付")}</span>
		<elseif condition="$order['pay_type'] eq 'daofu'"/>
		<span style="color:green">{pigcms{:L_("货到付款")}</span>
		<elseif condition="$order['pay_type'] eq 'dianfu'"/>
		<span style="color:green">{pigcms{:L_("到店付款")}</span>
		<elseif condition="$order['pay_type'] eq 'chinabank'"/>
		<span style="color:green">{pigcms{:L_("网银在线")}</span>
		<elseif condition="$order['pay_type'] eq 'offline'"/>
		<span style="color:green">{pigcms{:L_("线下支付")}</span>
		<elseif condition="empty($order['pay_type']) AND $order['paid'] eq 1 AND $order['balance_pay'] gt 0" />
		<span style="color:green">{pigcms{:L_("平台余额支付")}</span>
		<elseif condition="empty($order['pay_type']) AND $order['paid'] eq 1 AND $order['merchant_balance'] gt 0" />
		<span style="color:green">{pigcms{:L_("商家会员卡余额支付")}</span>
		<else />
		<span style="color:green">{pigcms{:L_("暂未选择")}</span>
		</if>
		</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("余额支付金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{$config.score_name}{pigcms{:L_("抵扣金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order.score_deducte|floatval} {pigcms{$config['Currency_txt']}， {pigcms{$config.score_name}{pigcms{:L_("使用数量")}: {pigcms{$order.score_used_count}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("在线支付金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("使用商家会员卡余额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("平台优惠券抵扣金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order.coupon_price|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("商家优惠券抵扣金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order.card_price|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("店员还应收取现金：")}
		<font color="red">
		<if condition="$order['total_price'] gt 0">
		{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']-$order['minus_price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}{pigcms{$config['Currency_txt']}
		<else />
		{pigcms{$config['Currency_symbol']}{pigcms{$order['price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-$order['score_deducte']|floatval}{pigcms{$config['Currency_txt']}
		</if>
		</font>
		</th>
	</tr>
	<if condition="$order['meal_type'] eq 1" >
	<tr>
		<th colspan="4">{pigcms{:L_("配送状态：")}
		<if condition="$order['order_status'] eq 1">
		<strong style="color:green">{pigcms{:L_("订单完成")}</strong>
		<elseif condition="$order['order_status'] eq 2" />
		<span style="color:red">{pigcms{:L_("未接单")}</span>
		<elseif condition="$order['order_status'] eq 3" />
		<span style="color:green">{pigcms{:L_("已接单")}</span>
		<elseif condition="$order['order_status'] eq 4" />
		<span style="color:green">{pigcms{:L_("配送员已取货")}</span>
		<elseif condition="$order['order_status'] eq 5" />
		<span style="color:green">{pigcms{:L_("正在配送")}</span>
		<elseif condition="$order['order_status'] eq 6" />
		<span style="color:red">{pigcms{:L_("客户退单")}</span>
		<elseif condition="$order['order_status'] eq 7" />
		<span style="color:red">{pigcms{:L_("商家取消订单")}</span>
		<elseif condition="$order['order_status'] eq 8" />
		<span style="color:green">{pigcms{:L_("配送员已接单")}</span>
		<else />
		<span style="color:red">{pigcms{:L_("订单失效")}</span>
		</if>
		</th>
	</tr>
	</if>
	<if condition="$order['deliver_user_info']">
	<tr>
		<th colspan="4">{pigcms{:L_("配送员姓名：")}{pigcms{$order['deliver_user_info']['name']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("配送员电话：")}{pigcms{$order['deliver_user_info']['phone']}</th>
	</tr>
	</if>
	<tr>
		<td colspan="4" style="line-height:22px;padding-top:15px;">
		{pigcms{:L_("姓名：")}{pigcms{$order['name']}<br/>
		{pigcms{:L_("电话：")}{pigcms{$order['phone']}<br/>
		{pigcms{:L_("地址：")}{pigcms{$order['address']}
		</td>
	</tr>
</table>
</body>
</html>