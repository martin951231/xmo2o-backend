<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>{pigcms{$now_store['name']}</li>
			<li class="active">{pigcms{:L_("订单列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="form-group">
						<label class="col-sm-1" style="display:none">{pigcms{:L_("订单状态筛选：")}</label>
						<select id="status" name="status" style="display:none">
							<volist name="status_list" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
						<form action="{pigcms{:U('Shop/order')}" method="get">
							<input type="hidden" name="c" value="Shop"/>
							<input type="hidden" name="a" value="order"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
							
							{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
								<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
								<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
								<option value="register_phone" <if condition="$_GET['searchtype'] eq 'register_phone'">selected="selected"</if>>{pigcms{:L_("注册手机号")}</option>
								<option value="member_card" <if condition="$_GET['searchtype'] eq 'member_card'">selected="selected"</if>>{pigcms{:L_("员工会员卡号")}</option>
							</select>
							<font color="#000">{pigcms{:L_("日期筛选：")}</font>
							<select name="searchTimeType">
								<option value="create_time" <if condition="$_GET['searchTimeType'] eq 'create_time'">selected="selected"</if>>下单时间</option>
								<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>支付时间</option>
								<option value="refund_time" <if condition="$_GET['searchTimeType'] eq 'refund_time'">selected="selected"</if>>退款时间</option>
								<option value="use_time" <if condition="$_GET['searchTimeType'] eq 'use_time'">selected="selected"</if>>消费时间</option>
								 <option value="expect_use_time" <if condition="$_GET['searchTimeType'] eq 'expect_use_time'">selected="selected"</if>>预计送达时间</option>
							</select>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							{pigcms{:L_("订单状态筛选")}:
							<select id="status" name="status">
								<volist name="status_list" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
								</volist>
							</select>
							{pigcms{:L_("支付方式筛选")}: 
							<select id="pay_type" name="pay_type">
									<option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部支付方式")}</option>
								<volist name="pay_method" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $pay_type">selected="selected"</if>>{pigcms{$vo.name}</option>
								</volist>
									<option value="balance" <if condition="'balance' eq $pay_type">selected="selected"</if>>{pigcms{:L_("余额支付")}</option>
							</select>
              {pigcms{:L_("订单来源")}:
              <select id="order_from" name="order_from">
                <option value="">{pigcms{:L_("全部")}</option>
                <option value="1" <if condition="1 eq $_GET['order_from']">selected="selected"</if>>{pigcms{:L_("线下购买")}</option>
                <option value="2" <if condition="2 eq $_GET['order_from']">selected="selected"</if>>{pigcms{$config.shop_alias_name}</option>
              </select>
							<br/><br/>
							{pigcms{:L_("自提点筛选")}:
                            <select id="pick_id" name="pick_id">
                                <option value="" <if condition="'' eq $pick_id">selected="selected"</if>>{pigcms{:L_("全部")}</option>
                                <volist name="self_pick" id="vo">
                                    <option value="{pigcms{$vo.id}" <if condition="$vo.id eq $pick_id">selected="selected"</if>>{pigcms{$vo.pick_addr}</option>
                                </volist>
                            </select>
                            {pigcms{:L_("操作店员筛选")}:
                            <select id="staff_id" name="staff_id">
                                <option value="" <if condition="'' eq $pay_type">selected="selected"</if>>{pigcms{:L_("全部")}</option>
                                <volist name="staff_arr" id="vo">
                                    <option value="{pigcms{$vo.id}" <if condition="$vo.id eq $staff_id">selected="selected"</if>>{pigcms{$vo.name}</option>
                                </volist>
                            </select>
							{pigcms{:L_("部门")}: 
							<input type="text" name="department" value="{pigcms{$department}"/>
							{pigcms{:L_("身份")}: 
							<input type="text"  name="identity" value="{pigcms{$identity}"/>
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　
							<a href="javascript:void(0)" onclick="exports()" class="btn btn-success" style="float:right;margin-right: 10px;">{pigcms{:L_("导出订单")}</a>
						</form>
					</div>
					
					<div class="alert alert-info" style="margin:10px 0;">
						<div>
							<b>{pigcms{:L_("应收总金额：")}{pigcms{$total_price|floatval}</b>　
							<b>{pigcms{:L_("在线支付总额：")}{pigcms{$online_price|floatval}</b>　
							<b>{pigcms{:L_("其他支付总额：")}{pigcms{$offline_price|floatval}</b>
							<a style="margin-left:20px;cursor: pointer;user-select: none;" id="desc_btn">{pigcms{:L_("展开")}</a>
							<div style="margin-top: 20px;display:none;" id="alert_desc" data-type="0">
                                (以上数据受（日期筛选|自提点筛选|操作店员筛选）的筛选条件影响。)
								<div>
									<b>{pigcms{:L_("应收总金额：订单实际要支付的金额总和,不包含已取消与已退款订单。")}</b>
								</div>
								<div>
									<b>{pigcms{:L_("在线支付总金额：订单实际要支付的金额包含商家优惠券的金额、使用商户余额、平台余额支付的金额、在线支付的钱、积分抵扣金额、平台优惠券金额，并且不包含退款与取消订单金额。")}</b>
								</div>
								<div>
									<b>{pigcms{:L_("其他支付总金额: 线下支付总金额，不包含退款与已取消订单。")}</b>
								</div>
							</div>
						</div>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th id="shopList_c1" width="50">{pigcms{:L_("订单编号")}</th>
									<!--th id="shopList_c1" width="50">{pigcms{:L_("订单号")}</th-->
									<th id="shopList_c1" width="50">{pigcms{:L_("客户信息")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("注册姓名")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("注册电话")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("身份")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("部门")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("订单来源")}</th>
									<!--th id="shopList_c0" width="80">{pigcms{:L_("配送方式")}</th>
									<th id="shopList_c0" width="80">{pigcms{:L_("地址")}</th-->
									
									
									<!--th id="shopList_c5" width="50">{pigcms{:L_("包装费")}</th-->
<!-- 									<th id="shopList_c3" width="30">{pigcms{:L_("配送费")}</th> -->
									<th id="shopList_c3" width="100">
									<if condition="$type eq 'price'">
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<else />
										<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
									</if>
									</th>
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("商家优惠的金额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("平台优惠的金额")}</th> -->
									
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("实付金额")}</th> -->
									
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("余额支付金额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("在线支付金额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("使用商家会员卡余额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("商家优惠券支付的金额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("平台优惠券支付的金额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("使用")}{pigcms{$config['score_name']}数</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("使用")}{pigcms{$config['score_name']}{pigcms{:L_("金额")}</th> -->
<!-- 									<th id="shopList_c4" width="50">{pigcms{:L_("店员应收现价")}</th> -->
									
									<th id="shopList_c3" width="80">{pigcms{:L_("下单时间")}</th>
									<th id="shopList_c3" width="90">
									<if condition="$type eq 'pay_time'">
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<else />
										<a href="{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort"></i></a>
									</if>
									</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("期望送达时间")}</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("送达时间")}</th>
                                    <th id="shopList_c3" width="80">{pigcms{:L_("收货地址")}</th>
									<th id="shopList_c4" width="70">{pigcms{:L_("支付状态")}</th>
									<th id="shopList_c6" width="70">{pigcms{:L_("金额<br/>修改备注")}</th>
<!-- 									<th id="shopList_c4" width="70">{pigcms{:L_("支付类型")}</th> -->
									<th id="shopList_c4" width="70">{pigcms{:L_("订单状态")}</th>
									<th id="shopList_c4" width="70">{pigcms{:L_("配送状态")}</th>
									<th id="shopList_c5" width="120" >{pigcms{:L_("店员操作信息")}</th>
									<!--th id="shopList_c5" width="120" >{pigcms{:L_("顾客留言")}</th>
									<th id="shopList_c4" width="50">{pigcms{:L_("发票抬头")}</th-->
									<th id="shopList_c5" width="20" >{pigcms{:L_("查看订单详情")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.real_orderid}</div></td>
											<!--td><div class="tagDiv">{pigcms{$vo.orderid}</div></td-->
											<td><div class="tagDiv">
												{pigcms{$vo.username}<br/>
												<if condition="$vo['virtual_phone_arr']">
													{pigcms{:phone_show($vo['userphone'])}
													<?php if($vo['virtual_phone_overtime'] >= time()){ ?>
														<br/> 隐私号码：
														{pigcms{$vo['virtual_phone_arr'][0][0]}
														<?php if($vo['virtual_phone_arr'][0][1]){ ?>
															{pigcms{:L_('转')}{pigcms{$vo['virtual_phone_arr'][0][1]}
														<?php } ?>
														
													<?php } ?>
												<else/>
													{pigcms{$vo.userphone}
												</if>
											</div></td>
											<td><div class="tagDiv"><php>echo $users[$vo['uid']]['nickname']??'';</php></div></td>
											<td><div class="tagDiv"><php>echo $users[$vo['uid']]['phone']??'';</php></div></td>
											<td>{pigcms{$identityList[$vo['uid']]?? ''}</td>
											<td>{pigcms{$departmentList[$vo['uid']]?? ''}</td>

                                            <td><if condition="$vo['platform'] eq 1">{pigcms{:L_("饿了么")}<elseif condition="$vo['platform'] eq 2"/>{pigcms{:L_("美团")}<elseif condition="$vo['order_from'] eq 6"/>{pigcms{:L_("线下购买")}<elseif condition="$vo['order_from'] eq 1" />{pigcms{:L_("商城")}<else />{pigcms{$config.shop_alias_name}</if></td>
											<!--td>{pigcms{$vo.deliver_str}</td>
											<td>{pigcms{$vo.address}</td-->
											
											<td>{pigcms{$vo.price|floatval}</td>
											<!--td>{pigcms{$vo.packing_charge}</td-->
<!-- 											<td>{pigcms{$vo.freight_charge|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.merchant_reduce|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.balance_reduce|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.price|floatval}</td> -->
											
<!-- 											<td>{pigcms{$vo.balance_pay|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.payment_money|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.merchant_balance|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.card_price|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.coupon_price|floatval}</td> -->
<!-- 											<td>{pigcms{$vo.score_used_count}</td> -->
<!-- 											<td>{pigcms{$vo.score_deducte|floatval}</td> -->
											<!-- td style="color: green"><strong>{pigcms{$vo['offline_price']|floatval}</strong></td> -->
											
											<td>{pigcms{$vo.create_time|date="m-d H:i",###}</td>
											<if condition="$vo['pay_time']">
											<td>{pigcms{$vo.pay_time|date="m-d H:i",###}</td>
											<else />
											<td></td>
											</if>
											<if condition="$vo['expect_use_time']">
											<td>
											<if condition="$vo['is_right_now']">
												<span style="color: red;">{pigcms{:L_('立即送达')} </span>{pigcms{$vo.expect_use_time|date="H:i",###}
											<else/>
												{pigcms{$vo.expect_use_time|date="Y-m-d H:i",###}
											</if>
											</td>
											<else />
											<td>{pigcms{:L_("尽快")}</td>
											</if>
											<if condition="$vo['use_time']">
											<td>{pigcms{$vo.use_time|date="m-d H:i",###}</td>
											<else />
											<td></td>
											</if>
                                            <td>{pigcms{$vo.address}</td>
											<td>{pigcms{$vo.pay_status}</td>
                      <td>{pigcms{$vo.change_price_reason}</td>
                                            <if condition="$vo['platform'] eq 0">
<!-- 											<td>{pigcms{$vo.pay_type_str}</td> -->
                                            <td>{pigcms{$vo.status_str}</td>
                                            <td>{pigcms{$vo.deliver_status_str}</td>
                                            <else />
<!--                                             <td></td> -->
                                            <td>{pigcms{$vo.status_str}</td>
                                            <td></td>
                                            </if>
											<td>
											<if condition="!empty($vo['last_staff'])">
											{pigcms{:L_("操作人员：")}<span class="red">{pigcms{$vo['last_staff']}</span><if condition="$vo['use_time']"><br/>{pigcms{:L_("消费时间：")}<br/>{pigcms{$vo.use_time|date="Y-m-d H:i",###}</if>
											<else/>
											<span class="red"></span>
											</if>
											</td>
											<!--td>{pigcms{$vo.note}</td>
											<td>{pigcms{$vo.invoice_head}</td-->
											
											<td>
											<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" style="float:right" href="{pigcms{:U('Shop/order_detail',array('order_id'=>$vo['order_id']))}">
												<i class="ace-icon fa fa-search bigger-130"></i>
											</a>
											</td>
											
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="30" >{pigcms{:L_("您的店铺暂时还没有订单。")}</td></tr>
								</if>
							</tbody>
						</table>
						{pigcms{$pagebar}
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
$(function(){
	$('.handle_btn').live('click',function(){
        if(window.top && window.top.artiframe) {
            window.top.artiframe($(this).attr('href'),'{pigcms{:L_("操作订单")}','80%','80%',true,false,false,false,'handle_btn',true,'50%','20%');
        } else {
            layer.open({
                title:'{pigcms{:L_("操作订单")}',
                area:['800px','80%'],
                type: 2,
                shade: 0.4,
                content:$(this).attr('href')
            });
        }
		return false;
	});
	$('#status').change(function(){
		location.href = "{pigcms{:U('Shop/order', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=" + $(this).val();
	});

	$('#desc_btn').click(function(){
		let type = $('#alert_desc').attr('data-type');
		if(type == 0){
			$(this).html('{pigcms{:L_("收起")}')
			$('#alert_desc').show();
			$('#alert_desc').attr('data-type',1);
		}else{
			$('#alert_desc').hide();
			$(this).html('{pigcms{:L_("展开")}')
			$('#alert_desc').attr('data-type',0);
		}
	});
});
 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Shop/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
