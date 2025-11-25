<include file="Public:header"/>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<empty name="team_id">
					<div class="form-group">
						<label class="col-sm-1" style="display:none">{pigcms{:L_("订单状态筛选：")}</label>
						<select id="status" name="status" style="display:none">
							<volist name="status_list" id="vo">
								<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
							</volist>
						</select>
						<form action="{pigcms{:U('Mall/order')}" method="get">
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
							</select>
							<font color="#000">{pigcms{:L_("日期筛选：")}</font>
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
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　
							<a href="javascript:void(0)" onclick="exports()" class="btn btn-success" style="float:right;margin-right: 10px;">{pigcms{:L_("导出订单")}</a>
						</form>
					</div>
					
					<div class="alert alert-info" style="margin:10px 0;">
						<b>{pigcms{:L_("应收总金额：")}{pigcms{$total_price|floatval}</b>　
						<b>{pigcms{:L_("在线支付总额：")}{pigcms{$online_price|floatval}</b>　
						<b>{pigcms{:L_("其他支付总额：")}{pigcms{$offline_price|floatval}</b>
					</div>
					</empty>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th id="shopList_c1" width="50">{pigcms{:L_("订单编号")}</th>
									<!--th id="shopList_c1" width="50">{pigcms{:L_("订单号")}</th-->
									<th id="shopList_c1" width="50">{pigcms{:L_("客户信息")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("订单来源")}</th>
									<!--th id="shopList_c0" width="80">{pigcms{:L_("配送方式")}</th>
									<th id="shopList_c0" width="80">{pigcms{:L_("地址")}</th-->
									
									
									<!--th id="shopList_c5" width="50">{pigcms{:L_("包装费")}</th-->
<!-- 									<th id="shopList_c3" width="30">{pigcms{:L_("配送费")}</th> -->
									<th id="shopList_c3" width="100">
									<if condition="$type eq 'price'">
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<else />
										<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
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
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<else />
										<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间")}<i class="menu-icon fa fa-sort"></i></a>
									</if>
									</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("期望送达时间")}</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("送达时间")}</th>
									
									<th id="shopList_c4" width="70">{pigcms{:L_("支付状态")}</th>
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
											<td><div class="tagDiv">{pigcms{$vo.username}<br/>{pigcms{$vo.userphone}</div></td>
                                            <td><if condition="$vo['platform'] eq 1">{pigcms{:L_("饿了么")}<elseif condition="$vo['platform'] eq 2"/>{pigcms{:L_("美团")}<elseif condition="$vo['order_from'] eq 6"/>{pigcms{:L_("线下购买")}<elseif condition="$vo['order_from'] eq 1" />{pigcms{:L_("商城")}<else />{pigcms{$config.mall_alias_name}</if></td>
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
											<td>{pigcms{$vo.expect_use_time|date="m-d H:i",###}</td>
											<else />
											<td>{pigcms{:L_("尽快")}</td>
											</if>
											<if condition="$vo['use_time']">
											<td>{pigcms{$vo.use_time|date="m-d H:i",###}</td>
											<else />
											<td></td>
											</if>
											<td>{pigcms{$vo.pay_status}</td>
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
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" style="float:right" href="{pigcms{:U('Mall/order_detail',array('order_id'=>$vo['order_id']))}">
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
<script type="text/javascript" src="./static/js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="./static/js/artdialog/iframeTools.js"></script>
<script>
$(function(){
	$('.handle_btn').live('click',function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("操作订单")}',
			padding: 0,
			width: 720,
			height: 520,
			lock: true,
			resize: false,
			background:'black',
			button: null,
			fixed: false,
			close: null,
			left: '50%',
			top: '38.2%',
			opacity:'0.4'
		});
		return false;
	});
	$('#status').change(function(){
		location.href = "{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=" + $(this).val();
	});	
});
 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Mall/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
