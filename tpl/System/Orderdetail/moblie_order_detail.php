<include file="Public:header"/>
<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
	<tr>
		<th colspan="1">手机号码</th>
		<th colspan="3">{pigcms{$now_order['phone']}</th>
	</tr>
	<tr>
		<th colspan="1">充值时间</th>
		<th colspan="3">{pigcms{$now_order['add_time']|date="Y-m-d H:i:s",###}</th>
	</tr>
	<tr>
		<th colspan="1">在线支付</th>
		<th colspan="3">{pigcms{$order['pay_money']|floatval}</th>
	</tr>
	<tr>
		<th colspan="1">平台余额抵扣</th>
		<th colspan="3">{pigcms{$order['system_balance']|floatval}</th>
	</tr>
	<tr>
		<th colspan="1">平台积分抵扣</th>
		<th colspan="3">{pigcms{$order['system_score_money']|floatval}</th>
	</tr>
	
	

</table>
<include file="Public:footer"/>