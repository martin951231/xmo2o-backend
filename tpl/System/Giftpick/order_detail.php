<include file="Public:header"/>
	<style>
		.frame_form td{line-height:24px;}
	</style>
	<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
		<tr>
			<th width="15%">订单编号</th>
			<td colspan="3" width="85%">{pigcms{$now_order.order_id}</td>
		</tr>
		<if condition="$now_order.orderid neq 0">
		<tr>
			<th width="15%">订单流水号</th>
			<td colspan="3" width="85%">{pigcms{$now_order.orderid}</td>
		</tr>
		</if>
		<tr>
			<th width="15%">{pigcms{$config.gift_alias_name}商品</th>
			<td colspan="3" width="85%"><a href="javascript:void(0)">{pigcms{$now_order.order_name}&nbsp;&nbsp;(兑换类型：<if condition='$now_order["exchange_type"] eq 0'>纯{pigcms{$config['score_name']}<elseif condition='$now_order["exchange_type"] eq 1' />{pigcms{$config['score_name']}+余额</if>)</a></td>
		</tr>
		
		
		<tr>
			<td colspan="4" style="padding-left:5px;color:black;"><b>订单信息</b></td>
		</tr>
		<tr>
			<th width="15%">订单类型</th>
			<td width="35%">实物</td>
			<th width="15%">订单状态</th>
			<td width="35%">
				<if condition='$now_order["status"] eq 2'>
					<font color="green">已完成</font>
				<elseif condition='empty($now_order["paid"])' />
					<font color="red">未支付</font>
				<else />
					<font color="green">已支付</font>
				</if>
			</td>
		</tr>
		<tr>
			<th width="15%">数量</th>
			<td width="35%">{pigcms{$now_order.num}</td>
			<th width="15%">总价</th>
			<td width="35%">
				{pigcms{$config['score_name']}：{pigcms{$now_order.total_integral}
			<if condition='$now_order["exchange_type"] eq 1'>
				<br />
				{pigcms{$config['Currency_symbol']} {pigcms{$now_order.total_price}
			</if>
			</td>
		</tr>
		<tr>
			<th width="15%">下单时间</th>
			<td width="35%">{pigcms{$now_order.order_time|date='Y-m-d H:i:s',###}</td>
			<if condition="!empty($now_order['paid']) && !empty($vo['pay_time'])">
				<th width="15%">付款时间</th>
				<td width="35%">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</td>
			<else/>
				<th width="15%"></th>
				<td width="35%"></td>
			</if>
		</tr>
		<tr>
			<th width="15%">买家留言</th>
			<td width="85%" colspan="3">{pigcms{$now_order.memo}</td>
		</tr>
		<tr>
			<td colspan="4" style="padding-left:5px;color:black;"><b>用户信息：</b></td>
		</tr>
		<tr>
			<if condition="$now_order['mer_id']">
				<th width="15%">商家ID</th>
				<td width="35%">{pigcms{$now_order.mer_id}</td>
				<th width="15%">商家名</th>
				<td width="35%">{pigcms{$now_order.name}</td>
			<else/>
				<th width="15%">用户ID</th>
				<td width="35%">{pigcms{$now_order.uid}</td>
				<th width="15%">用户昵称</th>
				<td width="35%">{pigcms{$now_order.nickname}</td>
			</if>
		</tr>
		<tr>
			<if condition="$now_order['mer_id']">
			<th width="15%">商家手机号</th>
			<td width="35%" colspan="3">{pigcms{$now_order.m_phone}</td>
			<else/>
			<th width="15%">用户手机号</th>
			<td width="35%" colspan="3">{pigcms{$now_order.user_phone}</td>
			</if>
		</tr>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
<include file="Public:footer"/>