<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/storestaffBase.css"/>
	</head>
	<body>
		<div class="headerBox">
			<div class="txt">{pigcms{$config.meal_alias_name}</div>
			<div class="back urlLink" data-url="{pigcms{:U('index')}" title="{pigcms{:L_("返回首页")}"></div>
			<div class="reload urlLink" data-url="reload" title="{pigcms{:L_("刷新页面")}"></div>
		</div>
		<div class="mainBox">
			<div class="leftMenu">
				<ul>
					<li class="urlLink cur" data-url="{pigcms{:U('meal_list')}">
						<div class="icon order"></div>
						<div class="text">{pigcms{:L_("订单列表")}</div>
					</li>
					<li class="urlLink" data-url="{pigcms{:U('table')}">
						<div class="icon table"></div>
						<div class="text">{pigcms{:L_("桌台列表")}</div>
					</li>
				</ul>
			</div>
			<div class="rightMain">
				<button class="btn btn-success handle_btn" data-title="no" data-box_width="95%" data-box_height="95%" href="{pigcms{:U('foodshop_order')}">{pigcms{:L_("创建订单")}</button>
				<button class="btn btn-success" onclick="window.location.href='{pigcms{:U('tmp_table')}'">{pigcms{:L_("桌台页面")}</button>
				<div class="alert alert-block alert-success">
					<p>
						{pigcms{:L_("注意:在每行的输入框里可以通过输入您想要搜索的订单的关键词")}<br/>
						{pigcms{:L_("在对应的标题下输入对应的关键词后按【Enter】即可搜索")}
					</p>
				</div>
				<div class="form-group">
					<label class="col-sm-1">{pigcms{:L_("订单状态筛选：")}</label>
					<select id="status" name="status">
						<volist name="status_list" id="vo">
							<option value="{pigcms{$key}" <if condition="$key eq $status">selected="selected"</if>>{pigcms{$vo}</option>
						</volist>
					</select>
				</div>
				<div class="grid-view">
					<table class="table table-striped table-bordered table-hover">
						<thead>
							<tr>
								<th id="shopList_c1" width="50">{pigcms{:L_("订单号")}</th>
								<th id="shopList_c1" width="50">{pigcms{:L_("下单人姓名")}</th>
								<th id="shopList_c0" width="80">{pigcms{:L_("下单人电话")}</th>
								<th id="shopList_c5" width="50" >{pigcms{:L_("餐台信息")}</th>
								<th id="shopList_c5" width="50" >{pigcms{:L_("消费类型")}</th>
								<th id="shopList_c5" width="100" >{pigcms{:L_("验证消费")}</th>
								<th id="shopList_c3" width="80">{pigcms{:L_("下单时间")}</th>
								<th id="shopList_c3" width="80">{pigcms{:L_("预计消费（送达）时间")}</th>
								<th id="shopList_c3" width="50">{pigcms{:L_("实际总价")}</th>
								<th id="shopList_c4" width="50">{pigcms{:L_("店员需收现")}</th>
								
								<th id="shopList_c4" width="80">{pigcms{:L_("支付状态")}</th>
								<th id="shopList_c4" width="80">{pigcms{:L_("订单状态")}</th>
								<th id="shopList_c5" width="120" >{pigcms{:L_("处理状态")}</th>
								<th class="button-column">{pigcms{:L_("操作")}</th>
							</tr>
						</thead>
						<tbody>
							<tr class="filters">
								<form method="post" action="" id="queryForList">
									<td><input id="order_id" name="order_id" type="text" maxlength="20" value="{pigcms{$order_id}"/></td>
									<td><input id="name" name="name" type="text" maxlength="20"  value="{pigcms{$name}"/></td>
									<td><input id="phone" name="phone" type="text" maxlength="20"  value="{pigcms{$phone}"/></td>
									<td><input id="table_name" name="table_name" type="text" maxlength="20"  value="{pigcms{$table_name}"/></td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
									<td>&nbsp;</td>
								</form>
							</tr>
							<volist name="order_list" id="vo">
								<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
									<td><div class="tagDiv">{pigcms{$vo.order_id}</div></td>
									<td><div class="tagDiv">{pigcms{$vo.name}</div></td>
									<td><div class="shopNameDiv">{pigcms{$vo.phone}</div></td>
									<td>{pigcms{$vo.tablename}</td>
									<td>
									<if condition="$vo['meal_type'] eq 0">{pigcms{:L_("预定")}
									<elseif condition="$vo['meal_type'] eq 1" />{pigcms{:L_("外卖")}
									<elseif condition="$vo['meal_type'] eq 2" />iPad{pigcms{:L_("点餐")}
									<elseif condition="$vo['meal_type'] eq 3" />{pigcms{:L_("堂内点餐")}
									</if>
									</td>
									<td>
									<if  condition="!empty($vo['last_staff'])">
									{pigcms{:L_("操作人员：")}<span class="red">{pigcms{$vo['last_staff']}</span><br/>{pigcms{:L_("消费时间：")}<br/>{pigcms{$vo.use_time|date="Y-m-d H:i",###}
									<else/>
									<span class="red">{pigcms{:L_("未验证消费")}</span>
									</if>
									</td>
									<td>{pigcms{$vo.dateline|date="Y-m-d H:i:s",###}</td>
									<td><if condition="empty($vo['arrive_time'])">{pigcms{:L_("尽快送达")}<else />{pigcms{$vo.arrive_time|date="Y-m-d H:i:s",###}</if></td>
									<td><if condition="$vo['total_price'] gt 0">{pigcms{$vo['total_price']|floatval}<else />{pigcms{$vo.price|floatval}</if></td>
									<td>
										<if condition="$vo['total_price'] gt 0">
										<strong style="color: red">{pigcms{$vo['total_price']-$vo['minus_price']-$vo['balance_pay']-$vo['merchant_balance']-$vo['payment_money']-$vo['coupon_price']-$vo['card_price']-$vo['score_deducte']|floatval}</strong>
										<else />
										<strong style="color: red">{pigcms{$vo['price']-$vo['balance_pay']-$vo['merchant_balance']-$vo['payment_money']-$vo['coupon_price']-$vo['card_price']-$vo['score_deducte']|floatval}</strong>
										</if>
									</td>
										
									<td>
										<if condition="$vo['paid'] eq 0">{pigcms{:L_("未支付")}
										<elseif condition="$vo['pay_type'] eq 'offline' AND empty($vo['third_id'])" />
										<span class="red">{pigcms{:L_("未付款")}</span>
										<elseif condition="$vo['paid'] eq 2"/>{pigcms{:L_("已付")}<span class="red">{pigcms{$vo.pay_money}</span>
										<elseif condition="$vo['paid'] eq 1"/><span class="green">{pigcms{:L_("已支付")}</span>
										</if>
									</td>
									<td>
										<if condition="$vo['status'] eq 0"><span style="color: red">{pigcms{:L_("未使用")}</span>
										<elseif condition="$vo['status'] eq 1" /><span style="color: green">{pigcms{:L_("已使用")}</span>
										<elseif condition="$vo['status'] eq 2" /><span style="color: green">{pigcms{:L_("已评价")}</span>
										<elseif condition="$vo['status'] eq 3" /><span style="color: red">{pigcms{:L_("已退款")}</span>
										<elseif condition="$vo['status'] eq 4" /><span style="color: red">{pigcms{:L_("已取消")}</span>
										</if>
									</td>
									<td>
										<if condition="$vo['paid'] gt 0">
											<if condition="$vo['status'] gt 2">
											<strong style="color: red">{pigcms{:L_("订单已取消")}</strong>
											<elseif condition="$vo['is_confirm'] eq 1" />
											<a title="{pigcms{:L_("已接单")}" class="green edit_btn" style="padding-right:8px;" href="javascript:;" >{pigcms{:L_("已接单")}</a>
											<elseif condition="$vo['is_confirm'] eq 0" />
											<a title="{pigcms{:L_("操作订单")}" class="green edit_btn js-add-order js-add-order-{pigcms{$vo.order_id}" style="padding-right:8px;" href="javascript:;" js-order="{pigcms{$vo.order_id}">{pigcms{:L_("接单")}</a>
											</if>
										<else />
										<strong style="color: red">{pigcms{:L_("未支付，不能接单")}</strong>
										</if>
										<!--label class="statusSwitch" style="display:inline-block;">
											<input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="{pigcms{$vo.order_id}" <if condition="$vo['is_confirm'] eq 1">checked="checked" data-status="OPEN"<else/>data-status="CLOSED"</if>/>
											<span class="lbl"></span>
										</label-->
									</td>
								
									
									<td class="button-column" width="40">
										<a title="{pigcms{:L_("查看订单详情")}"  data-title="{pigcms{:L_("订单详情")}" class="green handle_btn" style="padding-right:8px;" href="{pigcms{:U('Store/meal_edit',array('order_id'=>$vo['order_id']))}">
											<i class="shortBtn">{pigcms{:L_("查看详情")}</i>
										</a>
									</td>
								</tr>
							</volist>
						</tbody>
					</table>
					{pigcms{$pagebar}
				</div>
			</div>
		</div>
	</body>
	<script>
	$(function(){
		$('#status').change(function(){
			location.href = "{pigcms{:U('Store/meal_list', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=" + $(this).val();
		});	
		$('.js-add-order').click(function(){
			var order_id = $(this).attr('js-order');
			$('.js-add-order-'+order_id).html('{pigcms{:L_("处理中")}');
			$.post("{pigcms{:U('Store/check_confirm')}",{order_id:order_id,status:1},function(result){
				if(result.status == 1){
					$('.js-add-order-'+order_id).html(result.info);
					$('.js-add-order').removeClass('js-add-order');
				}else{
					alert(result.info);
					$('.js-add-order-'+order_id).html('{pigcms{:L_("接单")}');
				}
			});
		});
	});
	document.onkeydown = function(event_e) {
		if(window.event) event_e = window.event;  
		var int_keycode = event_e.charCode||event_e.keyCode;  
		if(int_keycode ==13 && ($('#order_id').val() != '' || $('#phone').val() != '' || $('#name').val() != '' || $('#meal_pass').val() != '')) $('#queryForList').submit();
	} 
	</script>
</html>