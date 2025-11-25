<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Foodshop/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
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
						
						<form action="{pigcms{:U('Foodshop/order')}" method="get">
							<input type="hidden" name="c" value="Foodshop"/>
							<input type="hidden" name="a" value="order"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
							{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
								<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
							</select>
							<font color="#000">{pigcms{:L_("支付时间筛选：")}</font>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
							
							{pigcms{:L_("订单状态筛选")}: 
							<select id="operate_status" name="operate_status" <if condition="!$config['open_meal_electronic_menu'] OR !$now_store['open_from_shop']">style="display:none;"</if>>
								<volist name="operate_status_list" id="vo">
									<option value="{pigcms{$key}" <if condition="$key eq $operate_status">selected="selected"</if>>{pigcms{$vo}</option>
								</volist>
							</select>

							<select id="status" name="status" <if condition="$config['open_meal_electronic_menu'] && $now_store['open_from_shop']">style="display:none;"</if>>
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
							</select>
							
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　　
							<a  href="javascript:void(0)" onclick="exports()" class="btn btn-success" style="float:right;margin-right: 10px;">{pigcms{:L_("导出订单")}</a>
						</form>
					</div>
                    
                    <div class="alert alert-info" style="margin:10px 0;">
                        <b>{pigcms{:L_("应收总金额：")}{pigcms{$total_price|floatval}</b>　
                        <b>{pigcms{:L_("在线支付总额：")}{pigcms{$online_price|floatval}</b>　
                        <b>{pigcms{:L_("其他支付总额：")}{pigcms{$offline_price|floatval}</b>
                    </div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("订单编号")}</th>
									<th width="80">{pigcms{:L_("客户姓名")}</th>
									<th width="80">{pigcms{:L_("客户电话")}</th>
									<th width="80" class="button-column">{pigcms{:L_("预订金")}</th>
									<th width="80">{pigcms{:L_("预订时间")}
									<if condition="$type eq 'book_time' AND false">
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'book_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("预订时间")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'book_time', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("预订时间")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'book_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("预订时间")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<elseif condition="false" />
										<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'book_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("预订时间")}<i class="menu-icon fa fa-sort"></i></a>
									</if>
									</th>
									<th width="80" class="button-column">{pigcms{:L_("桌台类型")}</th>
									<th width="80" class="button-column">{pigcms{:L_("桌台名称")}</th>
									<th width="80" class="button-column">{pigcms{:L_("订单状态")}</th>
									<th width="80" class="button-column">{pigcms{:L_("支付时间")}</th>
									<th width="80" class="button-column">{pigcms{:L_("订单总价")}
									<if condition="$type eq 'price' AND false">
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<elseif condition="false"  />
										<a href="{pigcms{:U('Foodshop/order', array('store_id' => $now_store['store_id'], 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
									</if>
									</th>
									<th width="80" class="button-column">{pigcms{:L_("查看订单详情")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.real_orderid}</td>
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.phone}</td>
											<td class="button-column">{pigcms{$vo.book_price|floatval}</td>
											<if condition="$vo['is_foodshop_new'] neq 1">
												<if condition="$vo['book_time']">
												<td>{pigcms{$vo.book_time|date='Y-m-d H:i:s',###}</td>
												<else />
												<td>--</td>
												</if>
											<else/>
												<td>{pigcms{$vo.create_time|date='Y-m-d H:i:s',###}</td>										
											</if>
											<td class="button-column">{pigcms{$vo.table_type_name}</td>
											<td class="button-column">{pigcms{$vo.table_name}</td>
											<td class="button-column">{pigcms{$vo.show_status}</td>
											<td class="button-column" ><if condition="$vo['pay_time'] gt 0">{pigcms{$vo.pay_time|date='Y-m-d H:i:s',###}<else />--</if></td>
											<if condition="$vo['status'] gt 2">
											<td class="button-column" style="color:green;">{pigcms{$vo.price|floatval}</td>
											<else />
											<td class="button-column" style="color:red;">{pigcms{:L_("还未买单")}</td>
											</if>
											<td class="button-column">
												<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" href="{pigcms{:U('Foodshop/order_detail', array('order_id' => $vo['order_id'], 'store_id' => $vo['store_id']))}">
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
		layer.open({
			title:'{pigcms{:L_("操作订单")}',
			area:['800px','80%'],
			type: 2,
			shade: 0.4,
			content:$(this).attr('href')
		});
		return false;
	});
});
   var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Foodshop/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
