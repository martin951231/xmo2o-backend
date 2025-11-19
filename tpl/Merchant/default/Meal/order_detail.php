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
		<th colspan="3"><if condition="$order['pay_type'] neq 'baidu'"><if condition="$order['meal_type']" >takeout_<else />food_</if> </if>{pigcms{$order['orderid']}</th>
	</tr>
	</if>
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
		<th width="180">{pigcms{:L_("总价：")}{pigcms{$config['Currency_symbol']}<if condition="$order['total_price'] gt 0">{pigcms{$order['total_price']}<else />{pigcms{$order['price']}</if></th>
		<th colspan="3">{pigcms{:L_("优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['minus_price']}</th>
		
	</tr>
	<tr>
		<th>{pigcms{:L_("支付状态")}:　
		<if condition="empty($order['paid'])">{pigcms{:L_("未支付")}
		<elseif condition="$order['pay_type'] eq 'offline' AND empty($order['third_id'])" />{pigcms{:L_("线下未支付")}
		<elseif condition="$order['paid'] eq 2"  /><span style="color:green">{pigcms{:L_("已付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['pay_money']}</span>，<span style="color:red">{pigcms{:L_("未付")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price'] - $order['pay_money']}</span>
		<else /><span style="color:green">{pigcms{:L_("全额支付")}</span>
		</if>
		</th>
		<th colspan="3">{pigcms{:L_("支付方式：")}
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
		<span style="color:green">{pigcms{:L_("平台商户支付")}</span>
		<else />
		<span style="color:green">{pigcms{:L_("暂未选择")}</span>
		</if>
		</th>
	
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("余额支付金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order['balance_pay']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{$config['score_name']}{pigcms{:L_("抵扣金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order.score_deducte} {pigcms{$config['score_name']}{pigcms{:L_("使用数量")}: {pigcms{$order.score_used_count}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("在线支付金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order['payment_money']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("使用商家会员卡余额")}:{pigcms{$config['Currency_symbol']} {pigcms{$order['merchant_balance']}</th>
	</tr>
	<if condition="$system_coupon">
	<tr>
		<th colspan="4">{pigcms{:L_("平台优惠券抵扣金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$system_coupon.price}</th>
	</tr>
	<elseif condition="$card" />
	<tr>
		<th colspan="4">{pigcms{:L_("商家优惠券抵扣金额")}:{pigcms{$config['Currency_symbol']} {pigcms{$card.price}</th>
	</tr>
	</if>
	<if condition="$order['pay_type'] eq 'offline' AND empty($order['third_id'])" >
	<tr>
		<th colspan="3">{pigcms{:L_("线下需向商家付金额：")}<font color="red">{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']-$order['wx_cheap']-$order['merchant_balance']-$order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price']}{pigcms{$config['Currency_txt']}</font></th>
	</tr>
	</if>
	<if condition="$order['deliver_user_info']">
	<tr>
		<th colspan="3">{pigcms{:L_("配送员姓名：")}{pigcms{$order['deliver_user_info']['name']}</th>
	</tr>
	<tr>
		<th colspan="3">{pigcms{:L_("配送员电话：")}{pigcms{$order['deliver_user_info']['phone']}</th>
	</tr>
	</if>
	<tr>
		<td colspan="4" style="line-height:22px;padding-top:15px;">
		{pigcms{:L_("姓名：")}{pigcms{$order['name']}<br/>
		{pigcms{:L_("电话：")}{pigcms{$order['phone']}<br/>
		{pigcms{:L_("使用人数：")}{pigcms{$order['num']}<br/>
		{pigcms{:L_("地址：")}{pigcms{$order['address']}
		</td>
	</tr>
</table>
<script type="text/javascript">
			$(function(){
				$('#merchant_remark_btn').click(function(){
					$(this).html('{pigcms{:L_("提交中")}...').prop('disabled',true);
					$.post("{pigcms{:U('Group/group_remark',array('order_id'=>$now_order['order_id']))}",{merchant_remark:$('#merchant_remark').val()},function(result){
						$('#merchant_remark_btn').html('{pigcms{:L_("修改")}').prop('disabled',false);
						alert(result.info);
					});
				});
				$('#store_id_btn').click(function(){
					$(this).html('{pigcms{:L_("提交中")}...').prop('disabled',true);
					$.post("{pigcms{:U('Group/order_store_id',array('order_id'=>$now_order['order_id']))}",{store_id:$('#order_store_id').val()},function(result){
						$('#store_id_btn').html('{pigcms{:L_("修改")}').prop('disabled',false);
						alert(result.info);
					});
				});
			});
		</script>
	</body>
</html>