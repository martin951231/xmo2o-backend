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
			<td colspan="3" width="85%"><a href="javascript:void(0)">{pigcms{$now_order.order_name}<if condition="$now_order['memo']">（{pigcms{$now_order.memo}）</if>&nbsp;&nbsp;(兑换类型：<if condition='$now_order["exchange_type"] eq 0'>纯{pigcms{$config['score_name']}<elseif condition='$now_order["exchange_type"] eq 1' />{pigcms{$config['score_name']}+余额</if>)</a></td>
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
				<elseif condition='$now_order["status"] eq 5' />
					<font color="orange">已取消</font>
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
                    总金额：{pigcms{$config['Currency_symbol']} {pigcms{$now_order.total_price}
                </if>
                <if condition='$now_order["delivery_fee"] gt 0'>
                    <br />
                    配送费： {pigcms{$config['Currency_symbol']} {pigcms{$now_order.delivery_fee}
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
			<if condition="$vo['uid']">
				<th width="15%">用户ID</th>
				<td width="35%">{pigcms{$now_order.uid}</td>
				<th width="15%">用户昵称</th>
				<td width="35%">{pigcms{$now_order.nickname}</td>
			<else/>
				<th width="15%">商家ID</th>
				<td width="35%">{pigcms{$now_order.mer_id}</td>
				<th width="15%">商家名</th>
				<td width="35%">{pigcms{$now_order.name}</td>
			</if>
		</tr>
		<tr>
			<th width="15%">订单手机号</th>
			<td width="35%">{pigcms{$now_order.phone}</td>
			<th width="15%">用户手机号</th>
			<td width="35%">{pigcms{$now_order.user_phone}</td>
		</tr>
		<if condition="$now_order['paid'] eq 1 and !empty($now_order['receive_code'])">
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>自提信息：</b></td>
			</tr>
			<tr>
				<th width="15%">自提状态</th>
				<td width="35%" colspan="3">
					<eq name='now_order.status' value='0'>
						<font color="red">待自提</font>
					</eq>
					<eq name='now_order.status' value='2'>
						<font color="green">已自提</font>
					</eq>
				</td>
			</tr>
		<elseif condition="$now_order['pick_address']"/>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>配送信息：</b></td>
			</tr>
			<tr>
				<th width="15%">配送方式</th>
				<td width="35%" colspan="3">门店自取</td>
			</tr>
			<tr>
				<th width="15%">门店信息</th>
				<td width="35%" colspan="3">{pigcms{$now_order['pick_address']['area_info']['province']}{pigcms{$now_order['pick_address']['area_info']['city']}{pigcms{$now_order['pick_address']['area_info']['area']}-{pigcms{$now_order['pick_address']['name']}</td>
			</tr>
			<tr>
				<th width="15%">门店电话</th>
				<td width="35%" colspan="3">{pigcms{$now_order['pick_address']['phone']}</td>
			</tr>
			<tr>
				<td width="35%" colspan="4"><if condition="$now_order['status'] eq 0"><button id="send_pick">发货到自提点</button><else/>已发货到自提点</if></td>
			</tr>
		<elseif condition="$now_order['is_pick_in_store'] lt 2"/>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>配送信息：</b></td>
			</tr>
			<tr>
				<th width="15%">收货人</th>
				<td width="35%">{pigcms{$now_order.contact_name}</td>
				<th width="15%">联系电话</th>
				<td width="35%">{pigcms{$now_order.phone}</td>
			</tr>
			<tr>
				<th width="15%">配送要求</th>
				<td width="35%">
					<switch name="now_order['delivery_type']">
						<case value="1">工作日、双休日与假日均可送货</case>
						<case value="2">只工作日送货</case>
						<case value="3">只双休日、假日送货</case>
						<case value="4">白天没人，其它时间送货</case>
					</switch>
				</td>
				<th width="15%">邮编</th>
				<td width="35%">{pigcms{$now_order.zipcode}</td>
			</tr>
			<tr>
				<th width="15%">收货地址</th>
				<td width="85%" colspan="3">{pigcms{$now_order.adress}</td>
			</tr>
			<tr>
				<th width="15%">快递信息</th>
				<td width="85%" colspan="3"><select id="express_type"><volist name="express_list" id="vo"><option value="{pigcms{$vo.id}" <if condition='$vo["id"] eq $now_order["express_type"]'>selected="selected"</if>>{pigcms{$vo.name}</option></volist></select> <input type="text" class="input" id="express_id" value="{pigcms{$now_order.express_id}" style="width:140px;"/> <button id="express_id_btn">填写</button></td>
			</tr>
		</if>
	</table>
	<div class="btn hidden">
		<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
		<input type="reset" value="取消" class="button" />
	</div>
	<script type="text/javascript" language="javascript">
	$('#express_id_btn').click(function(){
		if(confirm("您确定要提交快递信息吗？提交后订单状态会修改为已发货。")){
			express_post();
		}
	});
	$('#send_pick').click(function(){
		if(confirm("您确定要发货吗？提交后订单状态会修改为已发货。")){
			$.post("{pigcms{:U('gift_pick',array('order_id'=>$now_order['order_id']))}",function(result){
				if(result.status == 1){
					window.location.href = window.location.href;
				}
				alert(result.info);
			});
		}
	});
	
function express_post(){
	$('#express_id_btn').html('提交中...').prop('disabled',true);
	$.post("{pigcms{:U('gift_express',array('order_id'=>$now_order['order_id']))}",{express_type:$('#express_type').val(),express_id:$('#express_id').val()},function(result){
		if(result.status == 1){
			window.location.href = window.location.href;
		}
		$('#express_id_btn').html('填写').prop('disabled',false);
		alert(result.info);
	});
}

	</script>
<include file="Public:footer"/>