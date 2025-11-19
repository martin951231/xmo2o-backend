<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
	<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
	<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
	<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
</head>
<body>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">{pigcms{:L_("订单编号")}</th>
		<th colspan="3">{pigcms{$order['real_orderid']}</th>
	</tr>
	<if condition="$order['fetch_number'] gt 0">
	<tr>
		<th colspan="1">{pigcms{:L_("流水号")}</th>
		<th colspan="3">{pigcms{$order['fetch_number']}</th>
	</tr>
	</if>
	<if condition="$order.orderid neq 0">
	<tr>
		<th colspan="1">{pigcms{:L_("流水号")}</th>
		<th colspan="3"><if condition="$order['pay_type'] neq 'baidu'">foodshop_</if>{pigcms{$order['orderid']}</th>
	</tr>
	</if>
	<if condition="$order['info']">
	<tr>
		<th width="180">{pigcms{:L_("商品名称")}</th>
		<th>{pigcms{:L_("单价")}</th>
		<th>{pigcms{:L_("数量")}</th>
		<th>{pigcms{:L_("规格属性详情")}</th>
	</tr>
	<volist name="order['info']" id="vo">
	<tr>
		<th width="180" style="color:#9F0050">{pigcms{$vo['name']}<if condition="$vo['package_id']"> <span style="color:green;">　({pigcms{:L_("套餐")})</span></if></th>
		<th style="color:#9F0050">{pigcms{$vo['price']|floatval}</th>
		<th style="color:#9F0050"><strong>{pigcms{$vo['num']|floatval}</strong> / {pigcms{$vo['unit']}</th>
		<th style="color:#9F0050">{pigcms{$vo['spec']}</th>
	</tr>
	</volist>
	</if>
	<tr>
		<th colspan="4">{pigcms{:L_("客户姓名：")}{pigcms{$order['name']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("客户手机：")}{pigcms{$order['phone']}</th>
	</tr>
	<if condition="$order['register_phone']">
	<tr>
		<th colspan="4" style="color:red">{pigcms{:L_("客户注册手机：")}{pigcms{$order['register_phone']}</th>
	</tr>
	</if>
	<tr>
		<th colspan="4">{pigcms{:L_("桌台类型：")}{pigcms{$order['table_type_name']} </th>
	</tr>

	<tr>
		<th colspan="4">{pigcms{:L_("桌台名称：")}{pigcms{$order['table_name']} </th>
	</tr>

	<tr>
		<th colspan="4">{pigcms{:L_("下单时间：")}{pigcms{$order['create_time']|date="Y-m-d H:i:s",###} </th>
	</tr>

	<tr>
		<th colspan="4">{pigcms{:L_("预订金额：")}{pigcms{$order['book_price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("应付服务费：")}{pigcms{$order['table_service_payable']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("实付服务费：")}{pigcms{$order['table_service_payment']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	<if condition="$order['status'] gt 2 AND $order['status'] lt 5">
	<tr>
		<th colspan="4">{pigcms{:L_("订单总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['total_price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
    <if condition="$order['table_or_vipcard_discount'] lt 1 AND $order['table_discount_money'] gt 0">
        <tr>
            <th colspan="4">{pigcms{:L_("桌位折扣：")}{pigcms{$order['mer_table_discount']/10|floatval} 折</th>
        </tr>
        <tr>
            <th colspan="4">{pigcms{:L_("桌位优惠金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['table_discount_money']|floatval} {pigcms{$config['Currency_txt']}</th>
        </tr>
    </if>
	<if condition="$order['merchant_reduce'] gt 0">
	<tr>
		<th colspan="4">{pigcms{:L_("店铺优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['balance_reduce'] gt 0">
	<tr>
		<th colspan="4">{pigcms{:L_("平台优惠：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_reduce']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<gt name="order.use_ecard_price" value='0'>
		<tr>
			<th colspan="4">
				{pigcms{$config.ecard_alias_name}支付：{pigcms{$config.Currency_symbol}{pigcms{$order.use_ecard_price|floatval}{pigcms{$config.Currency_txt}
			</th>
		</tr>
	</gt>
	<if condition="$order['score_used_count']">
	<tr>
		<th colspan="4">{pigcms{:L_("使用")}{pigcms{$config.score_name}：{pigcms{$order['score_used_count']} </th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{$config.score_name}{pigcms{:L_("抵现：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['score_deducte']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	
	<if condition="$order['merchant_balance']">
	<tr>
		<th colspan="4">{pigcms{:L_("商家余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	
	<if condition="$order['merchant_balance_give']">
	<tr>
		<th colspan="4">{pigcms{:L_("商家赠送余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['merchant_balance_give']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['balance_pay']">
	<tr>
		<th colspan="4">{pigcms{:L_("平台余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['balance_pay']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['payment_money']">
	<tr>
		<th colspan="4">{pigcms{:L_("在线支付：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['payment_money']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['card_id']">
	<tr>
		<th colspan="4">{pigcms{:L_("店铺优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['card_price']} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
	<if condition="$order['coupon_id']">
	<tr>
		<th colspan="4">{pigcms{:L_("平台优惠券金额：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['coupon_price']} {pigcms{$config['Currency_txt']}</th>
	</tr>
	</if>
			
	<tr>
		<th colspan="4">{pigcms{:L_("实付总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$order['price']|floatval} {pigcms{$config['Currency_txt']}</th>
	</tr>
		<if condition="$order['book_pay_type']">
		<tr>
			<th colspan="4">{pigcms{:L_("预订支付方式：")}{pigcms{$order['book_pay_type']}</th>
		</tr>
		</if>
		<if condition="$order['pay_type']">
		<tr>
			<th colspan="4">{pigcms{:L_("订单支付方式：")}{pigcms{$order['pay_type']}</th>
		</tr>
		</if>
	</if>
	<tr>
		<th colspan="4">{pigcms{:L_("订单状态：")}{pigcms{$order['show_status']} </th>
	</tr>
	<if condition="$order['status'] eq 5">
	<tr>
		<th colspan="4">{pigcms{:L_("取消时间：")}{pigcms{$order['cancel_time']|date="Y-m-d H:i:s",###}</th>
	</tr>
	<tr>
		<th colspan="4">{pigcms{:L_("取消原因：")}{pigcms{$order['cancel_reason']}</th>
	</tr>
	</if>

	<tr>
		<th colspan="4">{pigcms{:L_("备注")}:{pigcms{$order['note']|default="无"}</th>
	</tr>
	<tr>
		<th colspan="4">
			<if condition="$order['is_fodoshop_new'] neq 1 OR $order['operate_status'] gt 0">
			<button  class="print_order">{pigcms{:L_("打印订单")}</button>
			</if>
			<if condition="$order['account_type'] eq 2 AND $order['is_fodoshop_new'] neq 1 AND $order['status'] neq 5 AND $staff_session['type'] eq 2">
			<button  class="cancel_order">{pigcms{:L_("取消订单")}</button>
			</if>
			<if condition="$order['account_type'] eq 2 AND $order['is_fodoshop_new'] neq 1">
			<button  class="print_menu">{pigcms{:L_("补打后厨单")}</button>
			</if>
		</th>
	</tr>
</table>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
<script type="text/javascript">	
	$('.print_order').click(function(){
		layer.confirm('{pigcms{:L_("打印结账单，一般适用于结账单补打。")}<br/><font color="red">{pigcms{:L_("确认打印？")}</span>', {
			title:'{pigcms{:L_("确认提醒：")}',
			btn: ['{pigcms{:L_("确认','取消")}']
		}, function(index){
			var tip_index = layer.load(0, {shade: [0.5,'#fff']});
	
			$.post("{pigcms{:U('foodshop_print_order',array('order_id'=>$_GET['order_id']))}",function(result){
				layer.close(index);
				layer.close(tip_index);
				alert(result.info);
			});
		});
	})
	$('.print_menu').click(function(){
		layer.confirm('{pigcms{:L_("打印订单，只会打印订单中的已点菜品。而且只会使用主打印机进行打印，一般适用于用户买单确定，")}<br/><font color="red">{pigcms{:L_("确认打印？")}</span>', {
			title:'{pigcms{:L_("确认提醒：")}',
			btn: ['{pigcms{:L_("确认','取消")}']
		}, function(index){
			var tip_index = layer.load(0, {shade: [0.5,'#fff']});
	
			$.post("{pigcms{:U('foodshop_print_order',array('order_id'=>$_GET['order_id'],'is_print_menu'=>1))}",function(result){
				layer.close(index);
				layer.close(tip_index);
				alert(result.info);
			});
		});
	})
	$('.cancel_order').click(function(){
		layer.confirm('{pigcms{:L_("确定取消嘛？取消过后无法恢复")}<br/><font color="red">{pigcms{:L_("确认取消？")}</span>', {
			title:'{pigcms{:L_("确认提醒：")}',
			btn: ['{pigcms{:L_("确认','取消")}']
		}, function(index){
			var tip_index = layer.load(0, {shade: [0.5,'#fff']});

			$.post("{pigcms{:U('cancel_foodshop_order',array('order_id'=>$_GET['order_id']))}",function(result){
				layer.close(index);
				layer.close(tip_index);
				result = JSON.parse(result);
				alert(result.msg);
			});
		});
	})
</script>
</body>
</html>