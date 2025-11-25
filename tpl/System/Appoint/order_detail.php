<include file="Public:header"/>

		<input type="hidden" name="appoint_id" value="{pigcms{$now_order.appoint_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>订单信息：</b></td>
			</tr>
			<tr>
                <th width="80">ID</th>
                <td>{pigcms{$now_order.order_id}</td>
				<th width="80">用户昵称</th>
				<td>{pigcms{$now_order.nickname}</td>
			</tr>
			<tr>
                <th width="80">订单编号</th>
                <td colspan="3">{pigcms{$now_order.real_orderid}</td>
            </tr>
			<if condition="$now_order.orderid gt 0">
			<tr>
				<th width="80">流水号</th>
				<td colspan="3">{pigcms{$now_order.tmp_orderid}</td>
			</tr>
			</if>
            <tr>
                <th width="80">编号</th>
                <td>{pigcms{$now_order.real_orderid}</td>
            </tr>
			<tr>
				<th width="80">用户手机</th>
				<td>{pigcms{$now_order.phone}</td>
				<th width="80">服务名称</th>
				<td>
					<a href="/appoint/{pigcms{$now_order['appoint_id']}.html" target="_blank" style="color: blue;">
						<div><if condition='$now_order["type"] eq 1'>{pigcms{$now_order.cat_name}<else />{pigcms{$now_order.appoint_name}</if></div>
					</a>
				</td>
			</tr>
			<tr>
				<th width="80">店铺名称</th>
				<td>{pigcms{$now_order.store_name}</td>
				<th width="80">下单时间</th>
				<td>{pigcms{$now_order.order_time|date='Y-m-d H:i:s',###}</td>
			</tr>
			<tr>
				<th width="80">预约日期</th>
				<td>{pigcms{$now_order.appoint_date}</td>
				<th width="80">预约时间点</th>
				<td>{pigcms{$now_order.appoint_time}</td>
			</tr>
			<tr>
				<th width="80">定金金额</th>
				<td>{pigcms{$now_order.payment_money}</td>
				<th width="80">用户留言</th>
				<td>{pigcms{$now_order.content}</td>
			</tr>
			<tr>
				<th width="80">服务类型</th>
				<td>
					<if condition='$now_order["type"] eq 1'>
					<div class="show">平台自营</div>
					<else/>
						<if condition="$now_order['appoint_type'] eq 0">到店</if>
					 	<if condition="$now_order['appoint_type'] eq 1">上门</if>
					</if>
				</td>
				
				<th width="80">支付状态</th>
				<td>
					<if condition="$now_order['paid'] eq 0">未支付</if>
					<if condition="($now_appoint['payment_money'] || $now_appoint['product_payment_price']) && $now_order['paid'] eq 1">
                        <php>if ($now_order['payment_status']!=1) {</php>
                            <span style="color:green">已支付</span>
                        <php>} elseif ($now_order['service_status']<=0 && $now_order['is_initiative']<=0) {</php>
                            <span style="color:green">定金已支付</span><span style="color:red">尾款待支付</span>
                        <php>} elseif ($now_order['service_status']>0 && $now_order['is_initiative']<=0) {</php>
                            <span style="color:green">已服务</span><span style="color:red">尾款待支付</span>
                        <php>} else {</php>
                            <span style="color:green">已支付</span>
                        <php>}</php>
                    </if>
					<if condition="$now_order['paid'] eq 2">已退款</if>
				</td>
			</tr>
			<tr>
				<th width="80">服务状态</th>
				<td>
					<if condition="$now_order['service_status'] eq 0">未服务</if>
					<if condition="($now_order['service_status'] eq 1) || ($now_order['service_status'] eq 2)">已服务</if>
				</td>
				<th width="80">验证店员</th>
				<td>{pigcms{$now_order.last_staff}</td>
			</tr>

            <if condition='$now_order["is_del"] neq ""'>
				<tr>
					<th width="80">订单状态</th>
					<td>
                        <if condition="$now_order['is_del'] eq 0 && $now_order['paid'] neq 3">
                            <span>未取消</span>
                            <elseif condition='$now_order["paid"] eq 3'/>
                            <span>已取消【用户】</span>
                        </if>
                        <if condition="$now_order['is_del'] eq 1">
                            <span>已取消【用户】【来源：PC端】</span>
                        </if>
                        <if condition="$now_order['is_del'] eq 2">
                            <span>已取消【平台】</span>
                        </if>
                        <if condition="$now_order['is_del'] eq 3">
                            <span>已取消【商家】</span>
                        </if>
                        <if condition="$now_order['is_del'] eq 4">
                            <span >已取消【店员：{pigcms{$now_order['staff_name']}】</span>
                        </if>
                        <if condition="$now_order['is_del'] eq 5">
                            <span>已取消【用户】【来源：WAP端】</span>
                        </if>
                    </td>

                    <if condition="$now_order['del_time']">
                        <th width="80">取消时间</th>
                        <td>
                            {pigcms{$now_order['del_time']|date='Y-m-d H:i:s',###}
                        </td>
                    </if>
                </tr>
			</if>
			<if condition="($now_order['paid'] egt '1')">
				<tr>
					<th width="15%">定金支付方式</th>
					<th width="85%" colspan="3">余额支付金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.balance_pay}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;实际支付金额 
					{pigcms{$config['Currency_symbol']}{pigcms{$now_order.pay_money}
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;使用商家会员卡余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['merchant_balance']+$now_order['card_give_money']}</th>
				</tr>
				<tr>
					<th width="15%">{pigcms{$config['score_name']}抵扣金额</th>
					<th width="35%" >{pigcms{$now_order.score_deducte}</th>
					<th width="15%">{pigcms{$config['score_name']}使用数量</th>
					<th width="35%" >{pigcms{$now_order.score_used_count}</th>
				</tr>
				<if condition="$system_coupon">
				<tr>
					<th width="15%">平台优惠券抵扣金额</th>
					<th width="85%" colspan="3"> {pigcms{$system_coupon.price}</th>
				</tr>
				<elseif condition="$card" />
				<tr>
					<th width="15%">商家优惠券抵扣金额</th>
					<th width="85%" colspan="3">{pigcms{$card.price}</th>
				</tr>
				</if>
                <if condition="$now_order['pay_time'] gt 0">
                <tr>
                    <th width="15%">定金支付时间</th>
                    <td width="85%"  colspan="3">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</td>
                </tr>
                </if>
			</if>

            <if condition="($now_order['paid'] egt '1') AND $now_order['is_initiative'] gt 0">
				<tr>
					<th width="15%">尾款支付方式</th>
					<th width="85%" colspan="3">余额支付金额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order.product_balance_pay}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;实际支付金额
                        <if condition="$now_order['product_balance_pay'] != $now_order['user_pay_money'] && $now_order['user_pay_money']">
                            {pigcms{$config['Currency_symbol']}{pigcms{$now_order.user_pay_money}
                            <else/>
                            {pigcms{$config['Currency_symbol']}{pigcms{$now_order.product_real_balace_price}
                        </if>
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;使用商家会员卡余额 {pigcms{$config['Currency_symbol']}{pigcms{$now_order['product_merchant_balance']+$now_order['product_card_give_money']}</th>
				</tr>
				<tr>
					<th width="15%">{pigcms{$config['score_name']}抵扣金额</th>
					<th width="35%" >{pigcms{$now_order.product_score_deducte}</th>
					<th width="15%">{pigcms{$config['score_name']}使用数量</th>
					<th width="35%" >{pigcms{$now_order.score_used_count}</th>
				</tr>
				<if condition="$system_coupon">
				<tr>
					<th width="15%">平台优惠券抵扣金额</th>
					<th width="85%" colspan="3"> {pigcms{$system_coupon.price}</th>
				</tr>
                <elseif condition="$now_order.product_coupon_price gt 0" />
                <tr>
                    <th width="15%">平台优惠券抵扣金额</th>
                    <th width="85%" colspan="3">{pigcms{$now_order.product_coupon_price}</th>
                </tr>
				<elseif condition="$card" />
				<tr>
					<th width="15%">商家优惠券抵扣金额</th>
					<th width="85%" colspan="3">{pigcms{$card.price}</th>
				</tr>
				<elseif condition="$now_order.product_card_price gt 0" />
                <tr>
                    <th width="15%">商家优惠券抵扣金额</th>
                    <th width="85%" colspan="3">{pigcms{$now_order.product_card_price}</th>
                </tr>
				</if>
                <if condition="$now_order['user_pay_time'] gt 0">
                    <tr>
                        <th width="15%">尾款支付时间</th>
                        <td width="85%"  colspan="3">{pigcms{$now_order.user_pay_time|date='Y-m-d H:i:s',###}</td>
                    </tr>
                    <elseif condition="$now_order['product_real_balace_pay_time'] gt 0"/>
                    <tr>
                        <th width="15%">尾款支付时间</th>
                        <td width="85%"  colspan="3">{pigcms{$now_order.product_real_balace_pay_time|date='Y-m-d H:i:s',###}</td>
                    </tr>
                </if>
			</if>
			
			
			<if condition='$now_order["product_detail"]'>
				<tr>
					<th width="15%">选择预约详情</th>
					<th width="85%" colspan="3"> 名称：{pigcms{$now_order["product_detail"]['name']}&nbsp;&nbsp;&nbsp;&nbsp;价格：{pigcms{$config['Currency_symbol']}{pigcms{$now_order["product_detail"]['price']}</th>
				</tr>
			</if>
			
			<if condition="$cue_list">
				<tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>自定义填写项：</b></td>
				</tr>
				<volist name="cue_list" id="val">
					<if condition="$val['type'] eq 2">
						<tr>
							<th width="80">{pigcms{$val.name}</th>
                            <php>if ($val['address'] && $val['address']!='') {</php>
							<td colspan="3">
								地址：{pigcms{$val.address}
								<php>if ($val['showName'] && $val['showName']!='') {</php>
								联系人：{pigcms{$val.showName}
								<php>}</php>
								<php>if ($val['phone'] && $val['phone']!='') {</php>
								电话：{pigcms{$val.phone}
								<php>}</php>
								<!--{pigcms{$val.value}-->
							</td>
                            <php>}else{</php>
                            <td colspan="3">{pigcms{$val.value}</td>
                            <php>}</php>
						</tr>
					<else/>
						<tr>
							<th width="80">{pigcms{$val.name}</th>
							<td colspan="3">{pigcms{$val.value}</td>
						</tr>
					</if>
				</volist>
			</if>

<!--		</table>-->
		<!--<form id="myform" method="post" action="{pigcms{:U('Appoint/editOrder')}" enctype="multipart/form-data">
			<input type="hidden" name="order_id" value="{pigcms{$now_order.order_id}"/>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>自定义项目名称：</b></td>
			</tr>

			<tr>
				<th width="80">城市名称</th>
				<td colspan="3">
					<select id="first" onchange="jump()" name="project_city_id">
						<option <if condition="$now_order['project_city_id'] eq ''">selected="selected"</if> value="0">请选择城市名称</option>

						<volist name="project_city_list" id="vo">
							<option <if condition="$now_order['project_city_id'] eq $vo['project_city_name']">selected="selected"</if> value="{pigcms{$vo['project_city_name']}">{pigcms{$vo['project_city_name']}</option>
						</volist>
					</select>
				</td>
			</tr>
			<tr>
				<th width="80">项目名称:</th>
				<td colspan="3">
					<select id="second" name="project_id">
						<option  value=" ">请选择项目名称</option>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan="4">
					<div class="btn">
						<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
					</div>
				</td>
			</tr>
		</form>-->

			<form id="myform" method="post" action="{pigcms{:U('Appoint/orderNote')}" enctype="multipart/form-data">
				<input type="hidden" name="order_id" value="{pigcms{$now_order.order_id}"/>
				<tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>备注：</b></td>
				</tr>
				<tr>
					<th width="80">请填写:</th>
					<td colspan="3">
						<div class="">

							<input type="text" class="input fl" name="note" value="{pigcms{$now_order.note}" size="60" placeholder="" validate="">
						</div>

					</td>
				</tr>
				<tr>
					<td colspan="4">
						<div class="btn">
							<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
						</div>
					</td>
				</tr>
			</form>
		</table>


<script type="text/javascript">

	$(document).ready(function(){
		console.log($("#first").val());

		$.ajax({
			url: "{pigcms{:U('Appoint/ajax_order')}",
			type:'post',
			data: {order_id: "{pigcms{$now_order.order_id}"},
			dataType : "json",
			success: function(res){
				console.log("========");
				console.log(res);
				if(res.project_id != null){
					var html = '';
					html += '<option value="'+res.project_id+'">'+res.project_id+'</option>';
					$("#second").html(html);
				}else{
					var html = '';
					html += '<option value="">请项目名称</option>';
					$("#second").html(html);
				}

			}
		})
	});
    function jump(){
	   $.ajax({
		   url: "{pigcms{:U('Appoint/ajax_city')}",
		   type:'post',
		   data: {city: $("#first").val()},
		   dataType : "json",
		   success: function(res){
			   console.log(res);
			   var html = '';
			   html += '<option value="">请项目名称</option>';
			   for(var i in res){
				   html += '<option  value="'+ res[i]['project_name'] +'">'+res[i]['project_name']+'</option>';
			   }
			   $("#second").html(html);
		   }
	   });
	}


</script>
<include file="Public:footer"/>