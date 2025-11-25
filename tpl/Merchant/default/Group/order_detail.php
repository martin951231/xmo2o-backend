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
		<table>
            <tr>
                <th width="15%">{pigcms{:L_("订单编号")}</th>
                <td width="35%">{pigcms{$now_order.real_orderid}</td>
                <th width="15%">{pigcms{$config.group_alias_name}{pigcms{:L_("商品")}</th>
                <if condition="$config['single_system_type'] eq 'village_group'">
                    <td width="35%">{pigcms{$now_order.s_name}</td>
                    <else/>
                    <td width="35%"><a href="{pigcms{$now_order.url}" target="_blank" title="{pigcms{:L_("查看商品详情")}">{pigcms{$now_order.s_name}</a></td>
                </if>
            </tr>
            <if condition="$now_order['specifications_info']">
                <tr>
                    <th width="15%">{pigcms{:L_("规格")}</th>
                    <td width="35%">{pigcms{$now_order.specifications_info.specifications_name}</td>
                </tr>
            </if>
			<if condition="$now_order.orderid neq 0">
			<tr>
				<th colspan="1" >{pigcms{:L_("流水号")}</th>
				<td colspan="3"><if condition="$now_order['pay_type'] neq 'baidu'">group_</if>{pigcms{$now_order.orderid}</td>
			</tr>
			</if>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("订单信息")}</b></td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("订单类型")}</th>
				<td width="35%"><if condition="$now_order['is_group_combine'] eq '1'">{pigcms{:L_("优惠组合")}<elseif condition="$now_order['tuan_type'] eq '0'"/>{pigcms{$config.group_alias_name}券<elseif condition="$now_order['tuan_type'] eq '1'"/>{pigcms{:L_("代金券")}<else/>{pigcms{:L_("实物")}</if></td>
				<th width="15%">{pigcms{:L_("订单状态")}</th>
				<td width="35%">
                    <if condition="$now_order['pay_type'] eq 'offline' AND empty($now_order['third_id'])" >
                        <font color="red">{pigcms{:L_("线下支付")}&nbsp;{pigcms{:L_("未付款")}</font>
					<elseif condition="$now_order['status'] eq 7">
				      <font color="red">{pigcms{:L_("退款失败，原因：")}{pigcms{$now_order['refund_detail']['msg']}</font>
					<elseif condition="$now_order['status'] eq 3"/>
                        <font color="red">已退款</font><php>if ($now_order['refund_detail']['refund_time']){</php><font color="red"> 退款时间：{pigcms{$now_order['refund_detail']['refund_time']|date='Y-m-d H:i:s',###}</font><php>}</php>
					<elseif condition="$now_order['paid'] eq '1'" />
						<if condition="$now_order['pay_type'] eq 'offline' AND empty($now_order['third_id'])" >
							<font color="red">{pigcms{:L_("线下支付　未付款")}</font>
						<elseif condition="$now_order['status'] eq '0'" />
							<font color="green">{pigcms{:L_("已付款")}</font>&nbsp;
                            <php>if($now_order['tuan_type'] != 2 && $now_order['is_pay_bill'] == 0){</php>
                                <font color="red">{pigcms{:L_("未消费")}</font>
                            <php>}else if ($now_order['tuan_type'] != 2 && $now_order['is_pay_bill'] == 1){</php>
                                <font color="red">{pigcms{:L_("部分消费")}</font>
							<php>}else{</php>
								<php>if($now_order['express_id'] != ''){</php>
									<font color="red">{pigcms{:L_("已发货")}</font>
								<php>}else{</php>
									<font color="red">{pigcms{:L_("未发货")}</font>
								<php>}</php>
							<php>}</php>
						<elseif condition="$now_order['status'] eq '1'"/>
							<php>if($now_order['tuan_type'] != 2){</php>
								<font color="green">{pigcms{:L_("已消费")}</font>
							<php>}else{</php>
								<php>if($vo['is_pick_in_store']){</php>
									<font color="green">{pigcms{:L_("已取货")}</font>
								<php>}else{</php>
									<font color="green">{pigcms{:L_("已发货")}</font>
								<php>}</php>
							<php>}</php>&nbsp;
							<font color="red">{pigcms{:L_("待评价")}</font>
						<elseif condition="$now_order['status'] eq 3"/>
							<font color="red">{pigcms{:L_("已退款")}</font>
						<elseif condition="$now_order['status'] eq 4"/>
							<font color="red">{pigcms{:L_("用户已取消")}</font>
                        <elseif condition="$now_order['status'] eq 5"/>
                            <font color="red">{pigcms{:L_("未完全消费")}</font>
                        <elseif condition="$now_order['status'] eq 6"/>
                            <font color="red">{pigcms{:L_("已取消")}-{pigcms{:L_("部分退款")}</font>
						<else/>
							<font color="green">{pigcms{:L_("已完成")}</font>
						</if>
					<else/>
						<if condition="$now_order['status'] eq 4">
							<font color="red">{pigcms{:L_("已取消")}</font>
						<else />
							<font color="red">{pigcms{:L_("未付款")}</font>
						</if>
					</if>
				</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("数量")}</th>
				<td width="35%">{pigcms{$now_order.num}</td>
				<th width="15%">{pigcms{:L_("总价")}</th>
				<td width="35%">{pigcms{$config['Currency_symbol']} {pigcms{$now_order['total_money']} <if condition="$config.open_extra_price eq 1 AND $now_order.extra_price gt 0">+{pigcms{$now_order.extra_price}{pigcms{$config.extra_price_alias_name}</if></td>
			</tr>
			<if condition="$now_order.express_fee gt 0">
			<tr>
				<th width="15%">{pigcms{:L_("运费")}</th>
				<td width="35%">{pigcms{$now_order.express_fee}</td>
				<th width="15%"></th>
				<td width="35%"></td>
			</tr>
			</if>
			<tr>
				<th width="15%">{pigcms{:L_("下单时间")}</th>
				<td width="35%">{pigcms{$now_order.add_time|date='Y-m-d H:i:s',###}</td>
				<if condition="$now_order['paid']">
					<th width="15%">{pigcms{:L_("付款时间")}</th>
					<td width="35%"><if condition="$now_order.pay_type neq 'offline'">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</if></td>
				<else/>
					<th width="15%"></th>
					<td width="35%"></td>
				</if>
			</tr>
            <if condition="$now_order.pay_type eq 'offline' AND empty($now_order['third_id'])">

                <tr>
                    <th width="15%">{pigcms{:L_("线下还需支付")}</th>
                    <td width="85%" colspan="3"><font color="red">{pigcms{$config['Currency_symbol']} {pigcms{$now_order['total_money']-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price']}</font></td>
                </tr>

            </if>
            <tr>
                <th width="15%">{pigcms{:L_("买家留言")}</th>
                <td width="85%" colspan="3">{pigcms{$now_order.delivery_comment}</td>
            </tr>
			<tr>
				<th width="15%">{pigcms{:L_("支付方式")}</th>
				<td width="85%" colspan="3">{pigcms{$now_order.paytypestr}</td>
			</tr>
			<if condition="!empty($now_order['use_time'])">		
				<tr>
					<th width="15%"><if condition="$now_order['tuan_type'] neq 2">{pigcms{:L_("消费")}<else/>{pigcms{:L_("发货")}</if>{pigcms{:L_("时间")}</th>
					<td width="35%">{pigcms{$now_order.use_time|date='Y-m-d H:i:s',###}</td>
					<th width="15%">{pigcms{:L_("操作店员：")}</th>
					<td width="35%">{pigcms{$now_order.last_staff}</td>
				</tr>
			</if>

            <php>if ($pass_info['total_pass_num'] > 0 || $pass_info['unconsume_pass_num'] > 0) {</php>
            <tr>
                <if condition="$pass_info['total_pass_num'] gt 0">
                    <th width="15%">{pigcms{:L_("总核销码数")}</th>
                    <td width="35%" style="font-weight: bold;color: #3283FA;">{pigcms{$pass_info['total_pass_num']}</td>
                </if>
                <if condition="$pass_info['unconsume_pass_num'] gt 0">
                    <th width="15%">{pigcms{:L_("未使用核销码数")}</th>
                    <td width="35%" style="font-weight: bold;color: red;">{pigcms{$pass_info['unconsume_pass_num']}</td>
                </if>
            </tr>
            <php>}</php>


            <if condition="$refund_detail">
                <tr>
                    <td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("退款详情信息：")}</b></td>
                </tr>

                <php>if ($refund_detail['err_msg']) {</php>
                <tr>
                    <th width="15%">{pigcms{:L_("退款未成功原因")}</th>
                    <td width="35%" style="font-weight: bold;color: red;">{pigcms{$refund_detail['err_msg']}</td>
                </tr>
                <php>}</php>
                <php>if ($refund_detail['refund_score'] > 0 || $refund_detail['refund_score_fee'] > 0) {</php>
                <tr>
                    <if condition="$refund_detail['refund_score'] gt 0">
                        <th width="15%">{pigcms{:L_("已退")}{pigcms{$config.score_name}数</th>
                        <td width="35%" style="font-weight: bold;color: #FDB338;">{pigcms{$refund_detail['refund_score']}</td>
                    </if>
                    <if condition="$refund_detail['refund_score_fee'] gt 0">
                        <th width="15%">{pigcms{$config.score_name}{pigcms{:L_("手续费")}</th>
                        <td width="35%" style="font-weight: bold;color: red;">{pigcms{$refund_detail['refund_score_fee']}</td>
                    </if>
                </tr>
                <php>}</php>

                <php>if ($refund_detail['refund_card_give_money'] > 0 || $refund_detail['refund_card_give_fee'] > 0) {</php>
                <tr>
                    <if condition="$refund_detail['refund_card_give_money'] gt 0">
                        <th width="15%">{pigcms{:L_("已退商户赠送余额")}</th>
                        <td width="35%" style="font-weight: bold;color: #FDB338;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_card_give_money']}</td>
                    </if>
                    <if condition="$refund_detail['refund_card_give_fee'] gt 0">
                        <th width="15%">{pigcms{:L_("商户赠送余额手续费")}</th>
                        <td width="35%" style="font-weight: bold;color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_card_give_fee']}</td>
                    </if>
                </tr>
                <php>}</php>

                <php>if ($refund_detail['refund_merchant_balance'] > 0 || $refund_detail['refund_merchant_balance_fee'] > 0) {</php>
                <tr>
                    <if condition="$refund_detail['refund_merchant_balance'] gt 0">
                        <th width="15%">{pigcms{:L_("已退商户余额")}</th>
                        <td width="35%" style="font-weight: bold;color: #FDB338;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_merchant_balance']}</td>
                    </if>
                    <if condition="$refund_detail['refund_merchant_balance_fee'] gt 0">
                        <th width="15%">{pigcms{:L_("商户余额手续费")}</th>
                        <td width="35%" style="font-weight: bold;color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_merchant_balance_fee']}</td>
                    </if>
                </tr>
                <php>}</php>

                <php>if ($refund_detail['refund_balance_pay'] > 0 || $refund_detail['refund_balance_pay_fee'] > 0) {</php>
                <tr>
                    <if condition="$refund_detail['refund_balance_pay'] gt 0">
                        <th width="15%">{pigcms{:L_("已退平台余额")}</th>
                        <td width="35%" style="font-weight: bold;color: #FDB338;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_balance_pay']}</td>
                    </if>
                    <if condition="$refund_detail['refund_balance_pay_fee'] gt 0">
                        <th width="15%">{pigcms{:L_("平台余额手续费")}</th>
                        <td width="35%" style="font-weight: bold;color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_balance_pay_fee']}</td>
                    </if>
                </tr>
                <php>}</php>

                <php>if ($refund_detail['refund_payment_money'] > 0 || $refund_detail['refund_payment_money_fee'] > 0) {</php>
                <tr>
                    <if condition="$refund_detail['refund_payment_money'] gt 0">
                        <th width="15%">{pigcms{:L_("已退线上支付金额")}</th>
                        <td width="35%" style="font-weight: bold;color: #FDB338;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_payment_money']}</td>
                    </if>
                    <if condition="$refund_detail['refund_payment_money_fee'] gt 0">
                        <th width="15%">{pigcms{:L_("线上支付金额手续费")}</th>
                        <td width="35%" style="font-weight: bold;color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_payment_money_fee']}</td>
                    </if>
                </tr>
                <php>}</php>

                <php>if ($refund_detail['refund_offline_money'] > 0 || $refund_detail['refund_offline_money_fee'] > 0) {</php>
                <tr>
                    <if condition="$refund_detail['refund_offline_money'] gt 0">
                        <th width="15%">{pigcms{:L_("应退线下支付金额")}</th>
                        <td width="35%" style="font-weight: bold;color: #FDB338;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_offline_money']}</td>
                    </if>
                    <if condition="$refund_detail['refund_offline_money_fee'] gt 0">
                        <th width="15%">{pigcms{:L_("线下支付金额手续费")}</th>
                        <td width="35%" style="font-weight: bold;color: red;">{pigcms{$config['Currency_symbol']}{pigcms{$refund_detail['refund_offline_money_fee']}</td>
                    </if>
                </tr>
                <php>}</php>
            </if>



            <php>if ($now_order['paid'] == 1){</php>
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
					<th width="15%">{pigcms{:L_("订单手机号")}</th>
                    <td width="35%">
                        <if condition="empty($now_order['is_pick_in_store'])">
                            {pigcms{$now_order.phone}
                            <else/>
                            <font color="red">{pigcms{:L_("到店自提，无需填写")}</font>
                        </if>
                    </td>
					<th width="15%">{pigcms{:L_("用户手机号")}</th>
					<td width="35%">{pigcms{$now_order.user_phone}</td>
				</tr>
				<if condition="$now_order['tuan_type'] eq 2">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("配送信息：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("收货人")}</th>
						<td width="35%">{pigcms{$now_order.contact_name}</td>
						<th width="15%">{pigcms{:L_("联系电话")}</th>
						<td width="35%">{pigcms{$now_order.phone}</td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("配送要求")}</th>
						<td width="35%">
							<switch name="now_order['delivery_type']">
								<case value="1">{pigcms{:L_("工作日、双休日与假日均可送货")}</case>
								<case value="2">{pigcms{:L_("只工作日送货")}</case>
								<case value="3">{pigcms{:L_("只双休日、假日送货")}</case>
								<case value="4">{pigcms{:L_("白天没人，其它时间送货")}</case>
							</switch>
						</td>
						<th width="15%">{pigcms{:L_("邮编")}</th>
						<td width="35%">{pigcms{$now_order.zipcode}</td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("收货地址")}</th>
						<td width="85%" colspan="3">{pigcms{$now_order.adress}</td>
					</tr>
					<php>if(empty($now_order['store_id'])&&empty($_GET['from'])){</php>
					<php>if(empty($pin_info) || $pin_info['status']==1 || $pin_info['status']==3){</php>
						<tr>
							<th width="15%">{pigcms{:L_("将订单归属于店铺：")}</th>
							<td width="85%" colspan="3">
								<select id="order_store_id">
									<volist name="group_store_list" id="vo">
										<option value="{pigcms{$vo.store_id}">{pigcms{$vo.name}</option>
									</volist>
								</select>
								<button id="store_id_btn">{pigcms{:L_("修改")}</button>
							</td>
						</tr>
						
						<php>}else{</php>
						<tr>
							<th width="15%">{pigcms{:L_("收货地址")}</th>
							<td width="85%" colspan="3" style="color:red">{pigcms{:L_("还未成团不能分配店铺")}</td>
						</tr>
						<php>}</php>
					<php>}</php>
				</if>
				<tr>
					<th width="15%">{pigcms{$config.ecard_alias_name}支付</th>
					<td width="85%" colspan="3">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['use_ecard_price']}</td>
				</tr>
				<tr>
					<th width="15%">{pigcms{:L_("余额支付金额")}</th>
					<td width="35%">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.balance_pay}</td>
					<th width="15%">{pigcms{:L_("在线支付金额")}</th>
					<td width="35%">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.payment_money}</td>
				</tr>
				<tr>
					<th width="15%">{pigcms{:L_("使用商家会员卡余额")}</th>
					<td width="85%" colspan="3">{pigcms{:L_("折扣：")}{pigcms{$now_order['card_discount']}{pigcms{:L_("折， 余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$now_order['merchant_balance']} {pigcms{:L_("，赠送余额：")}{pigcms{$config['Currency_symbol']}{pigcms{$now_order['card_give_money']}</td>
				</tr>
				
				<tr>
					<th width="15%">{pigcms{:L_("微信优惠")}</th>
					<td width="85%" colspan="3">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['wx_cheap']}</td>
				</tr>
				<tr>
				
						<th width="15%">{pigcms{:L_("平台优惠券")}</th>
						<td width="35%">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.coupon_price|floatval} </td>
					
				
						<th width="15%">{pigcms{:L_("商家优惠券")}</th>
						<td width="35%">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.card_price|floatval}</td>
				
				</tr>
				<tr>
					<th width="15%">{pigcms{$config.score_name}{pigcms{:L_("抵扣金额")}</th>
					<td width="35%">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.score_deducte} </td>
					<th width="15%">{pigcms{$config.score_name}{pigcms{:L_("使用数量")}</th>
					<td width="35%">{pigcms{$now_order.score_used_count}</td>
				</tr>

                <if condition="$now_order['write_list']">
                    <tr>
                        <td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("用户填写信息：")}</b></td>
                    </tr>
                    <volist name="now_order['write_list']" id="vo">
                        <tr>
                            <th width="15%">{pigcms{$vo.name}</th>
                            <td width="35%">{pigcms{$vo.value}</td>
                        </tr>
                    </volist>
                </if>

				<if condition="$now_order['paid'] eq '1' AND $_GET['from'] neq 'bill'">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("额外信息：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("订单标记")}</th>
						<td width="85%" colspan="3"><input type="text" class="input" id="merchant_remark" value="{pigcms{$now_order.merchant_remark}" style="width:400px;"/> <button id="merchant_remark_btn">{pigcms{:L_("修改")}</button></td>
					</tr>
				</if>
				<if condition="$pass_array">
					<tr>
						<th width="15%">{pigcms{:L_("消费密码")}</th>
						<th width="85%" colspan="3">
							<volist name="pass_array" id="vo">
                                {pigcms{$i}. {pigcms{$vo.group_pass}<php>if ($vo['verify_time']>0) {</php>（<php>if ($vo['staff_name']!='') {</php>店员名称：{pigcms{$vo.staff_name}，<php>}</php>核销时间：{pigcms{$vo.verify_time|date='Y-m-d H:i:s',###}）<php>}</php>
                                <br>
							</volist>
						</th>
					</tr>	
				<elseif condition="$now_order['group_pass']"   />
					<tr>
						<th width="15%">{pigcms{:L_("消费密码")}</th>
						<th width="85%" colspan="3">
                            {pigcms{$now_order.group_pass}<php>if ($now_order['use_time']>0) {</php>（<php>if ($now_order['last_staff']!='') {</php>店员名称：{pigcms{$now_order.last_staff}，<php>}</php>核销时间：{pigcms{$now_order.use_time|date='Y-m-d H:i:s',###}）<php>}</php>
                        </th>
					</tr>	
				</if>
				<if condition="$trade_hotel_info">
					<tr>
						<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("酒店订单详情：")}</b></td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("房间类型")}</th>
						<td width="85%" colspan="3">{pigcms{$trade_hotel_info.cat_pname} ({pigcms{$trade_hotel_info.cat_name})</td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("入住时间")}</th>
						<td width="35%">{pigcms{$trade_hotel_info.dep_time_txt}</td>
						<th width="15%">{pigcms{:L_("离店时间")}</th>
						<td width="35%">{pigcms{$trade_hotel_info.end_time_txt}</td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("房间数")}</th>
						<td width="35%">{pigcms{$trade_hotel_info.num}</td>
						<th width="15%">{pigcms{:L_("入住天数")}</th>
						<td width="35%">{pigcms{$trade_hotel_info['book_day']}天</td>
					</tr>
					<tr>
						<th width="15%">{pigcms{:L_("价格清单")}</th>
						<th width="65%" colspan="2">
							<volist name="trade_hotel_info['price_list_txt']" id="vo">
								{pigcms{$vo.day}：{pigcms{$config['Currency_symbol']}{pigcms{$vo.price} * {pigcms{$trade_hotel_info.num}<br/>
							</volist>
						</th>
						<th width="20%">
							<if condition="$now_order['refund_detail']['refund_time']">
								{pigcms{:L_("已退款积分")}：{pigcms{$config['Currency_symbol']}{pigcms{$now_order['refund_detail']['score']|floatval} 个<br/>
								{pigcms{:L_("已退款商家余额")}：{pigcms{$config['Currency_symbol']}{pigcms{$now_order['refund_detail']['card_give_money']+$now_order['refund_detail']['detail']['merchant_balance']|floatval}<br/>							
								{pigcms{:L_("已退款平台余额余额")}：{pigcms{$config['Currency_symbol']}{pigcms{$now_order['refund_detail']['balance_pay']|floatval}<br/>
								{pigcms{:L_("已退款在线支付")}：{pigcms{$config['Currency_symbol']}{pigcms{$now_order['refund_detail']['payment_money']|floatval}<br/>
							</if>
						</th>
					</tr>
				</if>
            <php>}else{</php>
                <if condition="$now_order['write_list']">
                    <tr>
                        <td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("用户填写信息：")}</b></td>
                    </tr>
                    <volist name="now_order['write_list']" id="vo">
                        <tr>
                            <th width="15%">{pigcms{$vo.name}</th>
                            <td width="35%">{pigcms{$vo.value}</td>
                        </tr>
                    </volist>
                </if>
            <php>}</php>
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