<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>景区管理中心</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
	</head>
	<body>
		<table>
			<tr>
				<th width="15%">订单编号</th>
				<td width="35%">{pigcms{$now_order.order_id}</td>
				<th width="15%">门票名称</th>
				<td width="35%">{pigcms{$scenic_ticket.ticket_title}</td>
			</tr>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>订单信息</b></td>
			</tr>
			<tr>
				<th width="15%">订单状态</th>
				<td width="35%">
					<if condition="$now_order['paid'] eq 1"><font color="red">未支付</font><else/><font color="green">已支付</font></if>
					<if condition="$now_order['order_status'] eq 1"><font color="green">未消费</font>
						<elseif condition="$now_order['order_status'] eq 2"/><font color="green">未评价</font>
						<elseif condition="$now_order['order_status'] eq 3"/><font color="green">已完成</font>
						<elseif condition="$now_order['order_status'] eq 4"/><font color="red">用户退款</font>
						<elseif condition="$now_order['order_status'] eq 5"/><font color="gray">已过期</font>
						<elseif condition="$now_order['order_status'] eq 6"/><font color="gray">已过期</font>
					</if>
				</td>
			</tr>
			<tr>
				<th width="15%">总价</th>
				<td width="35%">￥ {pigcms{$now_order['order_total']} 元</td>
				<th width="15%">支付方式</th>
				<td width="35%" colspan="3">{pigcms{$now_order.pay_type}</td>
			</tr>
			<tr>
				<th width="15%">下单时间</th>
				<td width="35%">{pigcms{$now_order.add_time|date='Y-m-d H:i:s',###}</td>
				<if condition="$now_order['paid'] eq 2">
					<th width="15%">付款时间</th>
					<td width="35%"><if condition="$now_order['pay_type'] neq 'offline'">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</if></td>
				<else/>
					<th width="15%"></th>
					<td width="35%"></td>
				</if>
			</tr>
			</tr>
			<!--<tr>
				<th width="15%">买家留言</th>
				<td width="85%" colspan="3">{pigcms{$now_order.delivery_comment}</td>
			</tr>-->
			<if condition="$now_order['paid'] eq '2'">
				<tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>用户信息：</b></td>
				</tr>
				<tr>
					<th width="15%">用户ID</th>
					<td width="35%">{pigcms{$now_order.user_id}</td>
					<th width="15%">用户名</th>
					<td width="35%">{pigcms{$now_order.truename}</td>
				</tr>
				<tr>
					<th width="15%">手机号</th>
					<td width="35%">{pigcms{$now_order.phone}</td>
				</tr>
				<tr>
					<th width="15%">余额支付金额</th>
					<td width="35%">{pigcms{$now_order.balance_pay}</td>
					<th width="15%">在线支付金额</th>
					<td width="35%">{pigcms{$now_order.payment_money}</td>
				</tr>
			</if>
			<if condition="$now_order['paid'] eq 2">
				<if condition="$order_com['ticket']">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>门票订单：</b></td>
					</tr>
					<tr>
						<th width="15%">门票消费码</th>
						<th width="85%" colspan="3">
							<volist name="order_com.ticket" id="vo">
								{pigcms{$vo.code}-----<if condition="$vo['status'] eq 1">未消费<elseif condition="$vo.status eq 2"/>已消费<else/>已退款</if><br>
							</volist>
						</th>
					</tr>
				</if>
				<if condition="$scenic_family">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>出行人：</b></td>
					</tr>
					<tr>
						<th width="15%">出行人信息</th>
						<th width="85%" colspan="3">
							<volist name="scenic_family" id="vo">
								姓名：{pigcms{$vo.family_name} 身份证：{pigcms{$vo.certificates}<br>
							</volist>
						</th>
					</tr>
				</if>
				<if condition="$order_com['park']">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>车位订单：</b></td>
					</tr>
					<tr>
						<th width="15%">车牌信息</th>
						<th width="85%" colspan="3">
							<volist name="order_com.park" id="vo">
								{pigcms{$vo.code}-----<if condition="$vo['status'] eq 1">未消费<elseif condition="$vo['status'] eq 2"/>已消费<else/>已退款</if><br>
							</volist>
						</th>
					</tr>
				</if>
				<if condition="$order_com['guide']">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>向导订单：</b></td>
					</tr>
					<tr>
						<th width="15%">向导信息</th>
						<th width="85%" colspan="3">
							<volist name="order_com.guide" id="vo">
								{pigcms{$vo.guide_name}-----<if condition="$vo['status'] eq 1">未消费<elseif condition="$vo['status'] eq 2"/>已消费<else/>已退款</if><br>
							</volist>
						</th>
					</tr>
				</if>
			</if>
		</table>
		<script type="text/javascript">
			$(function(){
				$('#merchant_remark_btn').click(function(){
					$(this).html('提交中...').prop('disabled',true);
					$.post("{pigcms{:U('Group/group_remark',array('order_id'=>$now_order['order_id']))}",{merchant_remark:$('#merchant_remark').val()},function(result){
						$('#merchant_remark_btn').html('修改').prop('disabled',false);
						alert(result.info);
					});
				});
				$('#store_id_btn').click(function(){
					$(this).html('提交中...').prop('disabled',true);
					$.post("{pigcms{:U('Group/order_store_id',array('order_id'=>$now_order['order_id']))}",{store_id:$('#order_store_id').val()},function(result){
						$('#store_id_btn').html('修改').prop('disabled',false);
						alert(result.info);
					});
				});
			});
		</script>
	</body>
</html>