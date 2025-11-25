<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<title>{pigcms{:L_("小猪")}{pigcms{$config.appoint_alias_name}{pigcms{:L_("预约系统")} - {pigcms{:L_("店铺管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	</head>
	<body>
		<table>
			<tr>
				<th width="15%">{pigcms{:L_("订单编号")}</th>
				<td width="35%">{pigcms{$now_order.order_id}</td>
				<th width="15%">{pigcms{:L_("预约信息")}</th>
				<td width="35%">{pigcms{$now_order.appoint_name}</td>
			</tr>
			<tr>
                <th width="15%">订单编号</th>
                <td width="35%">{pigcms{$now_order.real_orderid}</td>
            	<if condition="$now_order.orderid gt 0">
                    <th width="15%">订单流水号</th>
                    <td width="35%">{pigcms{$now_order.orderid}</td>
            	</if>
			</tr>

			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("订单信息：")}</b></td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("预约日期")}</th>
				<td width="35%">{pigcms{$now_order.appoint_date}</td>
				<th width="15%">{pigcms{:L_("预约时间点")}</th>
				<td width="35%"> {pigcms{$now_order.appoint_time}</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("订单状态")}</th>
				<td width="35%">
					<?php if($now_order['paid'] == 0){ ?>
					   	<font color="red">{pigcms{:L_("未支付")}</font>
					   
						<?php if($now_order['service_status'] == 0) { ?>
					   		<font color="red">{pigcms{:L_("未服务")}</font>
					   		<?php if(($now_order['is_del'] == 0) && ($now_order['paid'] == 1)) {?><a href="{pigcms{:U('Store/appoint_verify',array('order_id'=>$now_order['order_id']))}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a><?php }?>
					   	
						<?php }elseif($now_order['service_status'] == 1) {?>
					   		<font color="green">{pigcms{:L_("已服务")}</font>
						<?php } ?>
					
					<?php }elseif($now_order['paid'] == 1){ ?>
<!--                        <php>if ($now_order['payment_status']!=1) {</php>-->
<!--                            <font style="color:green">{pigcms{:L_("已支付")}</font>-->
<!--                        <php>} elseif ($now_order['service_status']<=0) {</php>-->
<!--                            <font  style="color:green">{pigcms{:L_("定金已支付")}|{pigcms{:L_("已预约")}|</font><font style="color:red">{pigcms{:L_("尾款待支付")}&nbsp;&nbsp;</font>-->
<!--                        <php>} else {</php>-->
<!--                            <font color="green">{pigcms{:L_("已支付")}</font>-->
<!--                        <php>}</php>-->
                        <php>if ($now_order['payment_status']!=1) {</php>
                        <font style="color:green">{pigcms{:L_("已支付")}</font>
                        <php>} elseif ($now_order['service_status']<=0 && $now_order['is_initiative']<=0) {</php>
                        <span style="color:green">定金已支付</span><span style="color:red">尾款待支付</span>
                        <php>} elseif ($now_order['service_status']>0 && $now_order['is_initiative']<=0) {</php>
                        <span style="color:red">尾款待支付</span>
                        <php>} else {</php>
                        <font color="green">{pigcms{:L_("已支付")}</font>
                        <php>}</php>

						
						<?php if($now_order['service_status'] == 0) { ?>
					   		<font color="red">{pigcms{:L_("未服务")}</font>
					   		<?php if(($now_order['is_del'] == 0) && ($now_order['paid'] == 1)) {?><a href="{pigcms{:U('Store/appoint_verify',array('order_id'=>$now_order['order_id']))}" class="group_verify_btn">{pigcms{:L_("验证服务")}</a><?php }?>
						<?php }elseif($now_order['service_status'] == 1){ ?>
					   		<font color="green">{pigcms{:L_("已服务")}</font>
						<?php }elseif($now_order['service_status'] == 2){ ?>
					   		<font color="green">{pigcms{:L_("已评价")}</font>
					   	<?php } ?>
					
					<?php }elseif($now_order['paid'] == 2){ ?>
						<font color="red">{pigcms{:L_("已退款")}</font>
				
					<?php } ?>
					
					<if condition='$now_order["is_del"] neq 0'>
					&nbsp;|&nbsp;
					<font color="red">
					<switch name='now_order["is_del"]'>
						<case value="1">{pigcms{:L_("已取消【用户】【PC端】")}</case>
						<case value="2">{pigcms{:L_("已取消【平台】")}</case>
						<case value="3">{pigcms{:L_("已取消【商家】")}</case>
						<case value="4">{pigcms{:L_("已取消【店员】")}</case>
						<case value="5">{pigcms{:L_("已取消【用户】【WAP端】")}</case>
					</switch>
					</font>
					</if>
				</td>
				
				<th width="15%">{pigcms{:L_("定金")}</th>
				<td width="35%"><if condition='$now_order["product_id"] gt 0'>{pigcms{$config['Currency_symbol']} {pigcms{$now_order.product_payment_price}<else />{pigcms{$config['Currency_symbol']} {pigcms{$now_order.payment_money}</if></td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("下单时间")}</th>
				<td width="35%">{pigcms{$now_order.order_time|date='Y-m-d H:i:s',###}</td>
				<th width="15%">{pigcms{:L_("总价")}</th>
				<td width="35%"><if condition='$now_order["product_id"] gt 0'>{pigcms{$config['Currency_symbol']} {pigcms{$now_order.product_price}<else />{pigcms{$config['Currency_symbol']} {pigcms{$now_order.appoint_price}</if></td>
			</tr>
			<if condition="$now_order['paid']">
				<tr>
					<th width="15%">{pigcms{:L_("付款时间")}</th>
					<td width="35%"><if condition="$now_order['pay_time']">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}<else/>{pigcms{:L_("无")}</if></td>
					<th width="15%">{pigcms{:L_("支付方式")}</th>
					<td width="35%" colspan="3">{pigcms{$now_order.pay_type}</td>
				</tr>
			</if>
            
            <tr>
				<th width="15%">{pigcms{:L_("服务类型")}</th>
				<td width="35%">
					<if condition="$now_order['appoint_type'] eq 0"><span style="color:red">{pigcms{:L_("到店")}</span>
					<elseif condition="$now_order['appoint_type'] eq 1" /><span style="color:red">{pigcms{:L_("上门")}</span>
					</if>
				</td>
				<th width="15%">{pigcms{:L_("买家留言")}</th>
					<td width="35%">{pigcms{$now_order.content}</td>
			</tr>
			
			<if condition='$now_order["product_detail"]'>
				<tr>
					<th width="15%">{pigcms{:L_("选择预约详情")}</th>
					<th width="85%" colspan="3"> {pigcms{:L_("名称：")}{pigcms{$now_order["product_detail"]['name']}&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("价格：")}{pigcms{$config['Currency_symbol']}{pigcms{$now_order["product_detail"]['price']}</th>
				</tr>
			</if>
				<if condition="!empty($now_order['last_time'])">		
					<tr>
						<th width="15%">{pigcms{:L_("验证时间")}</th>
						<td width="35%">{pigcms{$now_order.last_time|date='Y-m-d H:i:s',###}</td>
						<th width="15%">{pigcms{:L_("操作店员：")}</th>
						<td width="35%">{pigcms{$now_order.last_staff}</td>
					</tr>
				</if>


			<if condition="$finishRefunds['id'] neq ''">
				<tr>
					<th colspan="8"><strong>{pigcms{:L_("已退商品")}</strong></th>
				</tr>
				<tr>
					<th><strong>{pigcms{:L_("预约信息")}</strong></th>
					<th><strong>{pigcms{:L_("退款单价")}</strong></th>
				</tr>
				<tr>
					<th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}">{pigcms{$now_order.appoint_name}</th>
					<!-- <th style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}">{pigcms{$finishRefunds['price']|floatval}</th> -->
					<td style="color:#9F0050" rowspan="{pigcms{$finish['info_count']+1}"><if condition='$now_order["product_id"] gt 0'>￥ {pigcms{$now_order.product_price}<else />￥ {pigcms{$now_order.appoint_price}</if></td>
				</tr>
				<tr>
					<th colspan="4">{pigcms{:L_("退款备注")}: <strong style="color:red">{pigcms{$finishRefunds['reason']}</strong></th>
				</tr>
				<tr>
					<th colspan="4">{pigcms{:L_("退款凭证")}:
						<ul class="imgs" style="display:inline">
							<php>foreach ($imageLists as $image) {</php>
							<if condition="$image">
								<li style="display:inline">
									<img src="{pigcms{:file_domain()}{pigcms{$image}" height="50" class="refundImg"/>
								</li>
							</if>
							<php>}</php>
						</ul>
					</th>
				</tr>
				<tr>
					<th colspan="4">{pigcms{:L_("退款状态")}:

						<if condition="$finishRefunds['status'] eq 0"><span>{pigcms{:L_("正在审核中")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
							<elseif condition="$finishRefunds['status'] eq 1"/><span>{pigcms{:L_("商家审核通过")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
							<elseif condition="$finishRefunds['status'] eq 2"/><span>{pigcms{:L_("商家拒绝退款")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
							<elseif condition="$finishRefunds['status'] eq 3"/><span>{pigcms{:L_("您重新申请退款")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
							<elseif condition="$finishRefunds['status'] eq 4"/><span>{pigcms{:L_("您取消申请退款")} <php>if (!empty($finishRefunds['reply_content'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$finishRefunds['reply_content']}</strong><php>}</php></span>
						</if>
						{pigcms{$finishRefunds.reply_time|date="Y-m-d H:i",###}

					</th>
				</tr>


			</if>
			<if condition="$refund['id'] neq ''">
				<tr>
					<th colspan="4"><strong>{pigcms{:L_("退款信息")}</strong></th>
				</tr>
				<tr>
					<th><strong>{pigcms{:L_("预约信息")}</strong></th>
					<th><strong>{pigcms{:L_("单价")}</strong></th>

				</tr>

				<tr>
					<th style="color:#9F0050">{pigcms{$now_order.appoint_name}</th>
					<th style="color:#9F0050">{pigcms{$refund['price']|floatval}</th>

				</tr>
				<tr>
					<th colspan="4">{pigcms{:L_("退款备注")}: <strong style="color:red">{pigcms{$refund['reason']}</strong></th>
				</tr>
				<tr>
					<!-- <th colspan="4">{pigcms{:L_("退款金额")}: <strong style="color:red">{pigcms{$config['Currency_symbol']}{pigcms{$refund['price']}</strong></th> -->
					<td colspan="4">{pigcms{:L_("退款金额")}: ￥ {pigcms{$now_order.all_refund_money}</td>
				</tr>
				<tr>
					<th colspan="4">{pigcms{:L_("退款凭证")}:
						<ul class="imgs" style="display:inline">
							<php>foreach ($imageList as $image) {</php>
							<if condition="$image">
								<li style="display:inline">
									<img src="{pigcms{:file_domain()}{pigcms{$image}" height="50" class="refundImg"/>
								</li>
							</if>
							<php>}</php>
						</ul>
					</th>
				</tr>

				<tr>
					<th colspan="4">{pigcms{:L_("退款状态")}:

						<if condition="$refund['status'] eq 0"><span>{pigcms{:L_("正在审核中")} <php>if (!empty($refund['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$refund['note']}</strong><php>}</php></span>
							<elseif condition="$refund['status'] eq 1"/><span>{pigcms{:L_("商家审核通过")} <php>if (!empty($refund['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$refund['note']}</strong><php>}</php></span>
							<elseif condition="$refund['status'] eq 2"/><span>{pigcms{:L_("商家拒绝退款")} <php>if (!empty($refund['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$refund['note']}</strong><php>}</php></span>
							<elseif condition="$refund['status'] eq 3"/><span>{pigcms{:L_("您重新申请退款")} <php>if (!empty($refund['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$refund['note']}</strong><php>}</php></span>
							<elseif condition="$refund['status'] eq 4"/><span>{pigcms{:L_("您取消申请退款")} <php>if (!empty($refund['note'])) { </php>,{pigcms{:L_("理由：")}<strong style="color:red">{pigcms{$refund['note']}</strong><php>}</php></span>
						</if>
						{pigcms{$refund.applytime|date="Y-m-d H:i",###}

					</th>
				</tr>
				<tr>
					<th colspan="4">{pigcms{:L_("退款操作")}: <input type="text" name="reply_content" style="height: 24px;width:400px;" placeholder="{pigcms{:L_("请填写原因")}" /><button type="button" class="refund" data-type="agree" data-refund_id="{pigcms{$refund['id']}">{pigcms{:L_("同意")}</button><button type="button" class="refund" data-type="disagree" data-refund_id="{pigcms{$refund['id']}">{pigcms{:L_("不同意")}</button> </th>
				</tr>
			</if>


			<if condition="$now_order['paid'] eq '1'">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("用户信息：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("用户")}ID</th>
						<td width="35%">{pigcms{$now_order.uid}</td>
						<th width="15%">{pigcms{:L_("用户名")}</th>
						<td width="35%">{pigcms{$now_order.nickname}</td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("用户手机号")}</th>
						<td width="35%">{pigcms{$now_order.phone}</td>
						<th width="15%">{pigcms{:L_("使用商户余额")}</th>
						<td width="85%" colspan="3">{pigcms{$now_order.merchant_balance}</td>
					</tr>
				</if>
				<tr>
					<th width="15%">{pigcms{:L_("余额支付金额")}</th>
					<td width="35%">
					<if condition='$now_order["is_initiative"] AND $now_order["service_status"]'>
							{pigcms{$now_order['balance_pay'] + $now_order['product_balance_pay']}
						<elseif condition='$now_order["is_initiative"] AND !$now_order["service_status"]' />
							{pigcms{$now_order['product_balance_pay']}
						<else />
							{pigcms{$now_order.balance_pay}
						</if>
					</td>
					<th width="15%">{pigcms{:L_("实际支付金额")}</th>
					<td width="35%">
						<if condition='$now_order["is_initiative"] AND $now_order["service_status"]'>
							{pigcms{$now_order['product_real_payment_price'] + $now_order['product_real_balace_price']|number_format=###,2}
						<elseif condition='$now_order["is_initiative"] AND !$now_order["service_status"]' />
							{pigcms{$now_order['product_real_payment_price']|number_format=###,2}
						<else />
							{pigcms{$now_order.pay_money|number_format=###,2}
						</if>
					</td>
				</tr>
			<tr>
        <if condition="$now_order['payment_status']">
				<th width="15%">{pigcms{:L_("尾款支付金额")}</th>
          <else/>
          <th width="15%">{pigcms{:L_("支付金额")}</th>
        </if>
				<th width="85%" colspan="3">余额支付金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.product_balance_pay}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;实际支付金额
					<if condition="$now_order['product_balance_pay'] != $now_order['user_pay_money'] && $now_order['user_pay_money']">
						{pigcms{$config['Currency_symbol']}{pigcms{$now_order.user_pay_money}
						<else/>
						{pigcms{$config['Currency_symbol']}{pigcms{$now_order.product_real_balace_price}
					</if>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;使用商家会员卡余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['product_merchant_balance']+$now_order['product_card_give_money']}</th>

			</tr>

				<tr>
					<th width="15%">{pigcms{:L_("定金")}{pigcms{$config['score_name']}{pigcms{:L_("抵现金额")}</th>
					<td width="35%">{pigcms{$now_order.score_deducte|number_format=###,2}</td>
					<th width="15%">{pigcms{:L_("商家赠送余额")}</th>
					<td width="35%">{pigcms{$now_order.product_card_give_money}</td>
				</tr>
				
				<if condition='($now_order["product_id"]) AND ($now_order["paid"] == 1) AND ($now_order["pay_time"] gt 0)'>
					<tr>
						<th width="15%">{pigcms{:L_("实际支付定金")}</th>
						<td width="35%">{pigcms{$now_order['pay_money'] + $now_order['balance_pay'] + $now_order['score_deducte']|number_format=###,2}</td>
						<th width="15%">{pigcms{:L_("实际支付定金时间")}</th>
						<td width="35%">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</td>
					</tr>
				</if>
				<if condition='($now_order["product_id"]) AND ($now_order["paid"] == 1) AND ($now_order["service_status"] == 1) AND $now_order["is_initiative"]'>
					<tr>
						<th width="15%">{pigcms{:L_("实际支付尾款")}</th>
						<td width="35%">{pigcms{$now_order['user_pay_money'] + $now_order['product_balance_pay']}</td>
						<th width="15%">{pigcms{:L_("实际支付尾款时间")}</th>
						<td width="35%">{pigcms{$now_order.user_pay_time|date='Y-m-d H:i:s',###}</td>
					</tr>
					
					<tr>
						<th width="15%">{pigcms{:L_("实际支付")}{pigcms{$config['score_name']}{pigcms{:L_("金额")}</th>
						<td width="35%">{pigcms{$now_order['product_score_deducte']|number_format=###,2}</td>
						<th width="15%">{pigcms{:L_("实际支付平台优惠券金额")}</th>
						<td width="35%">{pigcms{$now_order['product_coupon_price']}</td>
					</tr>
					
					<tr>
						<th width="15%">{pigcms{:L_("实际支付商家优惠券金额")}</th>
						<td width="85%" colspan="3">{pigcms{$now_order['product_card_price']}</td>
					</tr>
				</if>
				
				<if condition='$now_order["del_time"]'>
					<tr>
						<th width="15%">{pigcms{:L_("取消时间")}</th>
						<td width="85%" colspan="3">{pigcms{$now_order.del_time|date='Y-m-d H:i:s',###}</td>
					</tr>
				</if>
				<if condition="$cue_list">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("自定义填写项：")}</b></td>
					</tr>
					<volist name="cue_list" id="val">
						<if condition="$val['type'] eq 2">
							<tr>
								<th width="15%">{pigcms{$val.name}</th>
								<td width="35%" colspan="3">
									{pigcms{:L_("地址：")}{pigcms{$val.address}
									{pigcms{$val.value}
								</td>
							</tr>
						<else/>
							<tr>
								<th width="15%">{pigcms{$val.name}</th>
								<td width="35%" colspan="3">{pigcms{$val.value}</td>
							</tr>
						</if>
					</volist>
				</if>
                
                 
           <if condition="$merchant_workers_info">
            <tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("服务工作人员信息：")}</b></td>
				</tr>
            	<tr>
                    <th width="15%">{pigcms{:L_("服务工作人员")}</th>
                    <td width="35%">
                    	{pigcms{$merchant_workers_info['name']}
                    </td>
                    <th width="15%">{pigcms{:L_("联系电话")}</th>
                    <td width="35%">
                    {pigcms{$merchant_workers_info['mobile']}
                    </td>
                </tr>
            </if>
		</table>
	<script type="text/javascript">
		$('.refund').click(function(){
			var refund_id = $(this).data('refund_id'), type = $(this).data('type'), order_id = '{pigcms{$refund["order_id"]}';
			var reply_content = $(this).parent('th').find('input[name="reply_content"]').val();
			console.log(refund_id)
			console.log(order_id)
			console.log(reply_content)
			console.log(type)
//			return
			$.post("{pigcms{:U('Store/appoint_reply_refund')}", {'order_id':order_id, 'refund_id':refund_id, 'reply_content':reply_content, 'type':type}, function(response){
				if (response.errcode == 1) {
                    alert(response.msg);
				} else {
					location.reload();
				}
			}, 'json');
		});

		$('.group_verify_btn').live('click',function(){
			var verify_btn = $(this);
			var data = {
				payment_status: "{pigcms{:$now_order['payment_status']}",
				service_status: "{pigcms{:$now_order['service_status']}",
			}
			//尾款未支付，二次确认框
			if(data.payment_status == 1 && data.service_status <= 0){
				parent.layer.alert('当前订单有尾款未支付，是否确认完成订单，完成订单后，用户将不能支付尾款！',{
					btn: ['确认','取消'],
					title: '提示',
				},function(index){
					parent.layer.close(index);
					verify();
				});
				return false
			}
			verify();
			function verify(){
				verify_btn.html('{pigcms{:L_("验证中")}..');
				$.get(verify_btn.attr('href'),function(result){
					var content = result.info;
					if(result.status == 1){
						parent.layer.alert(content,{
							btn: ['确认'],
							title: '提示',
						},function(index){
							parent.layer.close(index);
							window.location.reload()
							parent.location.reload();   
						});
					}
				});
			}
			return false;
		});
	</script>
	</body>
</html>