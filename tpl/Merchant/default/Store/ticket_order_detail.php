<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{:L_("景区管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
		<table>
			<tr>
				<th width="15%">{pigcms{:L_("订单编号")}</th>
				<td width="35%">{pigcms{$now_order.order_id}</td>
				<th width="15%">{pigcms{:L_("门票名称")}</th>
				<td width="35%">{pigcms{$scenic_ticket.ticket_title}</td>
			</tr>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("订单信息")}</b></td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("订单状态")}</th>
				<td width="35%">
					<if condition="$now_order['paid'] eq 1"><font color="red">{pigcms{:L_("未支付")}</font><else/><font color="green">{pigcms{:L_("已支付")}</font></if>
					<if condition="$now_order['order_status'] eq 1"><font color="green">{pigcms{:L_("未消费")}</font>
						<elseif condition="$now_order['order_status'] eq 2"/><font color="green">{pigcms{:L_("未评价")}</font>
						<elseif condition="$now_order['order_status'] eq 3"/><font color="green">{pigcms{:L_("已完成")}</font>
						<elseif condition="$now_order['order_status'] eq 4"/><font color="red">{pigcms{:L_("用户退款")}</font>
						<elseif condition="$now_order['order_status'] eq 5"/><font color="gray">{pigcms{:L_("已过期")}</font>
						<elseif condition="$now_order['order_status'] eq 6"/><font color="gray">{pigcms{:L_("已过期")}</font>
					</if>
				</td>
				<th width="15%">{pigcms{:L_("游玩时间")}</th>
				<td width="35%" colspan="3">{pigcms{$now_order.ticket_time}</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("总价")}</th>
				<td width="35%">{pigcms{$config['Currency_symbol']} {pigcms{$now_order['order_total']} {pigcms{$config['Currency_txt']}</td>
				<th width="15%">{pigcms{:L_("支付方式")}</th>
				<td width="35%" colspan="3">{pigcms{$now_order.pay_type}</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("下单时间")}</th>
				<td width="35%">{pigcms{$now_order.add_time|date='Y-m-d H:i:s',###}</td>
				<if condition="$now_order['paid'] eq 2">
					<th width="15%">{pigcms{:L_("付款时间")}</th>
					<td width="35%"><if condition="$now_order['pay_type'] neq 'offline'">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</if></td>
				<else/>
					<th width="15%"></th>
					<td width="35%"></td>
				</if>
			</tr>
			</tr>
			<!--<tr>
				<th width="15%">{pigcms{:L_("买家留言")}</th>
				<td width="85%" colspan="3">{pigcms{$now_order.delivery_comment}</td>
			</tr>-->
			<if condition="$now_order['paid'] eq '2'">
				<tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("用户信息：")}</b></td>
				</tr>
				<tr>
					<th width="15%">{pigcms{:L_("用户")}ID</th>
					<td width="35%">{pigcms{$now_order.user_id}</td>
					<th width="15%">{pigcms{:L_("用户名")}</th>
					<td width="35%">{pigcms{$now_order.truename}</td>
				</tr>
				<tr>
					<th width="15%">{pigcms{:L_("手机号")}</th>
					<td width="35%">{pigcms{$now_order.phone}</td>
				</tr>
				<tr>
					<th width="15%">{pigcms{:L_("余额支付金额")}</th>
					<td width="35%">{pigcms{$now_order.balance_pay}</td>
					<th width="15%">{pigcms{:L_("在线支付金额")}</th>
					<td width="35%">{pigcms{$now_order.payment_money}</td>
				</tr>
			</if>
			<if condition="$now_order['paid'] eq 2">
				<if condition="$order_com['ticket']">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("门票订单：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("门票消费码")}</th>
						<th width="85%" colspan="3">
							<volist name="order_com.ticket" id="vo">
								{pigcms{$vo.code}-----<if condition="$vo['status'] eq 1">{pigcms{:L_("未消费")}<elseif condition="$vo.status eq 2"/>{pigcms{:L_("已消费")}<else/>{pigcms{:L_("已退款")}</if><br>
							</volist>
						</th>
					</tr>
				</if>
				<if condition="$scenic_family">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("出行人：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("出行人信息")}</th>
						<th width="85%" colspan="3">
							<volist name="scenic_family" id="vo">
								{pigcms{:L_("姓名：")}{pigcms{$vo.family_name} {pigcms{:L_("身份证：")}{pigcms{$vo.certificates}<br>
							</volist>
						</th>
					</tr>
				</if>
				<if condition="$order_com['park']">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("车位订单：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("车牌信息")}</th>
						<th width="85%" colspan="3">
							<volist name="order_com.park" id="vo">
								{pigcms{$vo.code}-----<if condition="$vo['status'] eq 1">{pigcms{:L_("未消费")}<elseif condition="$vo['status'] eq 2"/>{pigcms{:L_("已消费")}<else/>{pigcms{:L_("已退款")}</if><br>
							</volist>
						</th>
					</tr>
				</if>
				<if condition="$order_com['guide']">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("向导订单：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("向导信息")}</th>
						<th width="85%" colspan="3">
							<volist name="order_com.guide" id="vo">
								{pigcms{$vo.guide_name}-----<if condition="$vo['status'] eq 1">{pigcms{:L_("未消费")}<elseif condition="$vo['status'] eq 2"/>{pigcms{:L_("已消费")}<else/>{pigcms{:L_("已退款")}</if><br>
							</volist>
						</th>
					</tr>
				</if>
			</if>
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