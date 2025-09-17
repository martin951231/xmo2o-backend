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
                <th width="15%">订单ID</th>
                <td width="85%" colspan="3">{pigcms{$now_order.order_id}</td>
            </tr>
            <tr>
                <th width="15%">订单编号</th>
                <td width="85%" colspan="3">{pigcms{$now_order.real_orderid}</td>
            </tr>
            <if condition="$now_order.orderid gt 0">
                <tr>
                    <th width="15%">订单流水号</th>
                    <td width="85%" colspan="3">{pigcms{$now_order.orderid}</td>
                </tr>
            </if>
			<tr>
				<th width="15%">{pigcms{:L_("服务名称")}</th>
				<td width="85%" colspan="3">{pigcms{$now_order.appoint_name}</td>
			</tr>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("订单信息：")}</b></td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("用户名")}</th>
				<td width="35%">{pigcms{$now_order.nickname}</td>
				<th width="15%">{pigcms{:L_("手机号")}</th>
				<td width="35%">{pigcms{$now_order.phone}</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("预约日期")}</th>
				<td width="35%">{pigcms{$now_order.appoint_date}</td>
				<th width="15%">{pigcms{:L_("预约时间点")}</th>
				<td width="35%">{pigcms{$now_order.appoint_time}</td>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("下单时间")}</th>
				<td width="35%">{pigcms{$now_order.order_time|date='Y-m-d H:i:s',###}</td>
				<if condition="$now_order['payment_status']">
				<th width="15%">{pigcms{:L_("定金")}</th>
				<td width="35%"><if condition='$now_order["product_id"] gt 0'>{pigcms{$config['Currency_symbol']} {pigcms{$now_order.product_payment_price}<else />{pigcms{$config['Currency_symbol']} {pigcms{$now_order.payment_money}</if></td>
				</if>
			</tr>
			<tr>
				<th width="15%">{pigcms{:L_("服务类型")}</th>
				<td width="35%">
					<if condition="$now_order['appoint_type'] eq 0"><span style="color:red">{pigcms{:L_("到店")}</span>
					<elseif condition="$now_order['appoint_type'] eq 1" /><span style="color:red">{pigcms{:L_("上门")}</span>
					</if>
				</td>
				<th width="15%">{pigcms{:L_("总价")}</th>
				<td width="35%">
				
				<if condition="$now_order['paid'] egt 1 AND $now_order['service_status'] eq 0 AND $now_order.is_appoint_price eq 0">
					{pigcms{$config['Currency_symbol']}&nbsp;<input type="text" name="price" value="{pigcms{$now_order.total_price|floatval}" class="input" style="    width: 50px;">
				<else />
					<if condition="$now_order['product_price'] gt 0">
					{pigcms{$config['Currency_symbol']}&nbsp;{pigcms{$now_order.product_price|floatval}
					<else/>
					{pigcms{$config['Currency_symbol']}&nbsp;{pigcms{$now_order.appoint_price|floatval}
					</if>
				</if>  
				<if condition="$now_order['paid'] eq 1 AND $now_order['service_status'] eq 0 AND $now_order['is_appoint_price'] eq 0">
                    <button style="display:inline" onclick="change_price(this);">{pigcms{:L_("修改价格")}</button>
                </if>
				&nbsp;<a onclick="window.top.artiframe('{pigcms{:U('Appoint/order_price_change_log',array('order_id'=>$now_order['order_id'],'frame_show'=>true))}','查看订单价格修改记录',720,520,true,false,false,false,'order_detail_log',true);">{pigcms{:L_("修改记录")}</a>
                </td>
			</tr>
            <tr>
				<th width="15%">{pigcms{:L_("订单状态")}</th>
				<td width="35%">
					<if condition="$now_order['paid'] == 0" >
					   	<font color="red">{pigcms{:L_("未支付")}</font>
					<elseif condition="$now_order['paid'] == 1" />

                        <php>if ($now_order['payment_status']!=1) {</php>
                            <font style="color:green">{pigcms{:L_("已支付")}</font>
                        <php>} elseif ($now_order['service_status']<=0 && $now_order['is_initiative']<=0) {</php>
                            <span style="color:green">定金已支付</span><span style="color:red">尾款待支付</span>
                        <php>} elseif ($now_order['service_status']>0 && $now_order['is_initiative']<=0 && $now_order['secondary_payment_money']) {</php>
                            <span style="color:red">尾款待支付</span>
                        <php>} else {</php>
                            <font color="green">{pigcms{:L_("已支付")}</font>
                        <php>}</php>
					<elseif condition="$now_order['paid'] == 2" />
						<font color="red">{pigcms{:L_("已退款")}</font>
					</if>
					
					<if condition="$now_order['service_status'] == 0" >
						<font color="red">{pigcms{:L_("未服务")}</font>
						<if condition='($now_order["is_del"] eq 0) && ($now_order["paid"] eq 1) || (($now_order["is_del"] eq 0) && ($now_order["payment_status"] eq 0))'><a href="{pigcms{:U('appoint_verify',array('order_id'=>$now_order['order_id']))}" class="group_verify_btn"><span title="请确认订单是否已完成后再点击">{pigcms{:L_("完成订单")}</span></a></if>
					<elseif condition="$now_order['service_status'] == 1" />
						<font color="green">{pigcms{:L_("已服务")}</font>
					<elseif condition="$now_order['service_status'] == 2" />
						<font color="green">{pigcms{:L_("已评价")}</font>
					</if>
					
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
                        <elseif condition='$now_order["paid"] eq 3'/>
                        <font color="red">
                        {pigcms{:L_("已取消【用户】")}
                        </font>
					</if>
				</td>
				
				
				<!--th width="15%">{pigcms{:L_("定金")}</th>
				<td width="35%">{pigcms{$config['Currency_symbol']} {pigcms{$now_order.payment_money}</td-->
			</tr>
			<if condition='$now_order["del_time"]'>
				<tr>
					<th width="15%">{pigcms{:L_("取消时间")}</th>
					<td width="85%" colspan="3">{pigcms{$now_order.del_time|date='Y-m-d H:i:s',###}</td>
				</tr>
			</if>
             <if condition='$now_order["product_detail"]'>
				<tr>
					<th width="15%">{pigcms{:L_("选择预约详情")}</th>
					<th width="85%" colspan="3"> {pigcms{:L_("名称：")}{pigcms{$now_order["product_detail"]['name']}&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("价格：")}{pigcms{$config['Currency_symbol']}{pigcms{$now_order["product_detail"]['price']}</th>
				</tr>
			</if>
			
			<tr>
				<th width="15%">{pigcms{:L_("买家留言")}</th>
				<td width="85%" colspan="3">{pigcms{$now_order.content}</td>
			</tr>
            
            
           <if condition="$merchant_workers_info">
				<tr>
					<td colspan="4" style="padding-left:5px;color:black;"><b>{pigcms{:L_("服务工作人员信息：")}</b></td>
				</tr>
                
                <if condition='$now_order["store_id"]'>
					<tr>
						<th width="15%">{pigcms{:L_("店铺名称")}</th>
						<td width="85%" colspan="3">
							{pigcms{$now_order['store_name']}
						</td>
					</tr>
                </if>
                
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
            
            
			<if condition="!empty($now_order['use_time'])">		
				<tr>
					<th width="15%"><if condition="$now_order['tuan_type'] neq 2">{pigcms{:L_("消费")}<else/>{pigcms{:L_("发货")}</if>{pigcms{:L_("时间")}</th>
					<td width="35%">{pigcms{$now_order.use_time|date='Y-m-d H:i:s',###}</td>
					<th width="15%">{pigcms{:L_("操作店员：")}</th>
					<td width="35%">{pigcms{$now_order.last_staff}</td>
				</tr>
			</if>
			<if condition="$now_order['paid'] egt '1'">
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
						<td width="35%">{pigcms{:L_("会员卡余额")}:{pigcms{$now_order['merchant_balance']} + {pigcms{:L_("会员卡赠送余额")}: {pigcms{$now_order['card_give_money']}</td>
				</tr>


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
                    <if condition="$now_order['pay_time'] gt 0">
                    <tr>
                        <th width="15%">定金支付时间</th>
                        <td width="85%"  colspan="3">{pigcms{$now_order.pay_time|date='Y-m-d H:i:s',###}</td>
                    </tr>
                    </if>
                </if>


                <php>if(($now_order['paid'] >= '1') AND ($now_order['service_status'] == '1' OR $now_order['is_initiative'] > 0)){</php>
                    <tr>
                      <if condition="$now_order['payment_status']">
                        <th width="15%">尾款支付方式</th>
                        <else/>
                        <th width="15%">支付方式</th>
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
                        <th width="15%">{pigcms{$config['score_name']}抵扣金额</th>
                        <th width="35%" >{pigcms{$now_order.product_score_deducte}</th>
                        <th width="15%">{pigcms{$config['score_name']}使用数量</th>
                        <th width="35%" >{pigcms{$now_order.score_used_count}</th>
                    </tr>
                    <if condition="$now_order['user_pay_time'] gt 0">
                        <tr>
                          <if condition="$now_order['payment_status']">
                            <th width="15%">尾款支付时间</th>
                            <else/>
                            <th width="15%">支付时间</th>
                          </if>
                            <td width="85%"  colspan="3">{pigcms{$now_order.user_pay_time|date='Y-m-d H:i:s',###}</td>
                        </tr>
                        <elseif condition="$now_order['product_real_balace_pay_time'] gt 0"/>
                        <tr>
                          <if condition="$now_order['payment_status']">
                            <th width="15%">尾款支付时间</th>
                            <else/>
                            <th width="15%">支付时间</th>
                          </if>
                            <td width="85%"  colspan="3">{pigcms{$now_order.product_real_balace_pay_time|date='Y-m-d H:i:s',###}</td>
                        </tr>
                    </if>
                <php>}</php>

				<if condition='($now_order["service_status"] gt 0) AND $now_order["is_initiative"]'>
<!--					<tr>-->
<!--						<th width="15%">--><?php //if($now_order["product_id"]){ ?><!--{pigcms{:L_("商品规格")}--><?php //} ?><!--{pigcms{:L_("实际支付余额")}</th>-->
<!--						<td width="35%">{pigcms{$now_order['user_pay_money'] + $now_order['product_balance_pay']|number_format=###,2}</td>-->
<!--						<th width="15%">--><?php //if($now_order["product_id"]){ ?><!--{pigcms{:L_("商品规格")}--><?php //} ?><!--{pigcms{:L_("实际支付余额时间")}</th>-->
<!--						<td width="35%">{pigcms{$now_order.user_pay_time|date='Y-m-d H:i:s',###}</td>-->
<!--					</tr>-->
					
					<tr>
						<th width="15%"><?php if($now_order["product_id"]){ ?>{pigcms{:L_("商品规格")}<?php } ?>{pigcms{:L_("实际支付")}{pigcms{$config['score_name']}{pigcms{:L_("金额")}</th>
						<td width="35%">{pigcms{$now_order['product_score_deducte']|number_format=###,2}</td>
						<th width="15%"><?php if($now_order["product_id"]){ ?>{pigcms{:L_("商品规格")}<?php } ?>{pigcms{:L_("尾款支付平台优惠券金额")}</th>
						<td width="35%">{pigcms{$now_order['product_coupon_price']}</td>
					</tr>
					
					<tr>
						<th width="15%"><?php if($now_order["product_id"]){ ?>{pigcms{:L_("商品规格")}<?php } ?>{pigcms{:L_("尾款支付商家优惠券金额")}</th>
						<td width="85%" colspan="3">{pigcms{$now_order['product_card_price']}</td>
					</tr>
				</if>
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
								{pigcms{:L_("地址：")}{pigcms{$val.address}	<!--{pigcms{$val.value}-->
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
		<form id="myform" method="post" action="{pigcms{:U('Appoint/editOrder')}" enctype="multipart/form-data">
			<input type="hidden" name="order_id" value="{pigcms{$now_order.order_id}"/>
			<tr>
				<td colspan="4" style="padding-left:5px;color:black;"><b>自定义项目名称：</b></td>
			</tr>

			<tr>
				<th width="80">城市名称</th>
				<td>
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
				<td>
					<select id="second" name="project_id">
						<option  value=" ">请选择项目名称</option>
					</select>

				</td>
			</tr>
			<tr>
				<td>
					<div class="btn">
						<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />

					</div>
				</td>
			</tr>
		</form>
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
			
			function change_price(obj){
				var price = $(obj).siblings('input[name="price"]').val();
				$.post('{pigcms{:U('change_order_price')}',{order_id:'{pigcms{$_GET['order_id']}',price:price},function(date){
					
					alert(date.msg)
					window.location.reload();
				},'json')
				
			}




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
	</body>
</html>