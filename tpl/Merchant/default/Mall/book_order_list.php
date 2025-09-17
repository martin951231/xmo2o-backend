<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>{pigcms{$now_store['name']}</li>
			<gt name='team_id' value='0'>
				<li><a href="{pigcms{:U('Mall/order',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("订单列表")}</a></li>
				<li class="active">{pigcms{:L_("拼团")}{pigcms{$team_id}{pigcms{:L_("团队订单列表")}</li>
			<else/>
				<li class="active">{pigcms{:L_("订单列表")}</li>
			</gt>			
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
						<form action="{pigcms{:U('Mall/order')}" method="get">
							<input type="hidden" name="c" value="Mall"/>
							<input type="hidden" name="a" value="book_order_list"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
							<input type="hidden" name="team_id" value="{pigcms{$_GET.team_id}"/>
							
							{pigcms{:L_("搜索")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="real_orderid" <if condition="$_GET['searchtype'] eq 'real_orderid'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
								<option value="orderid" <if condition="$_GET['searchtype'] eq 'orderid'">selected="selected"</if>>{pigcms{:L_("订单流水号")}</option>
								<option value="third_id" <if condition="$_GET['searchtype'] eq 'third_id'">selected="selected"</if>>{pigcms{:L_("第三方支付流水号")}</option>
								<option value="name" <if condition="$_GET['searchtype'] eq 'name'">selected="selected"</if>>{pigcms{:L_("客户名称")}</option>
								<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>{pigcms{:L_("客户电话")}</option>
							</select>
							<font color="#000">{pigcms{:L_("日期筛选：")}</font>
							<select name="searchTimeType">
								<option value="create_time" <if condition="$_GET['searchTimeType'] eq 'create_time'">selected="selected"</if>>{pigcms{:L_("下单时间")}</option>
								<option value="pay_time" <if condition="$_GET['searchTimeType'] eq 'pay_time'">selected="selected"</if>>{pigcms{:L_("支付时间")}</option>
							</select>
							<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
							<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>

							{pigcms{:L_("支付状态筛选")}: 
							<select id="book_status" name="book_status">
								<option value="0" <if condition="EMPTY($_GET['book_status'])">selected="selected"</if>>全部</option>
								<option value="1" <if condition="$_GET['book_status'] eq '1'">selected="selected"</if>>{pigcms{:L_("定金已支付")}</option>
								<option value="2" <if condition="$_GET['book_status'] eq '2'">selected="selected"</if>>{pigcms{:L_("尾款已支付")}</option>
							</select>
							<input type="hidden" name="shop_type" value="2" class="button"/><!--此参数为导出订单方法使用-->
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>　
						</form>
					</div>
					<empty name="team_id">
					
					</empty>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th id="shopList_c1" width="50">{pigcms{:L_("订单编号")}</th>
									<th id="shopList_c1" width="50">{pigcms{:L_("客户信息")}</th>
									
									<th id="shopList_c3" width="100">
									<if condition="$type eq 'price'">
										{pigcms{:L_("订单总价")}
										<!--<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'price', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>
										</if>-->
									<else />
										{pigcms{:L_("订单总价")}
										<!--<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'price', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("订单总价")}<i class="menu-icon fa fa-sort"></i></a>-->
									</if>
									</th>
									
									<th id="shopList_c3" width="80">{pigcms{:L_("下单时间")}</th>
									<th id="shopList_c3" width="90">
										{pigcms{:L_("定金支付时间")}
									<!--<if condition="$type eq 'pay_time'">
										
										<if condition="$sort eq 'ASC'">
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间1")}<i class="menu-icon fa fa-sort-desc"></i></a>
										<elseif condition="$sort eq 'DESC'" />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'pay_time', 'sort' => 'ASC', 'status' => $status))}">{pigcms{:L_("支付时间2")}<i class="menu-icon fa fa-sort-asc"></i></a>
										<else />
											<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间3")}<i class="menu-icon fa fa-sort"></i></a>
										</if>
									<else />
										<a href="{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'],'team_id'=>$team_id, 'type' => 'pay_time', 'sort' => 'DESC', 'status' => $status))}">{pigcms{:L_("支付时间4")}<i class="menu-icon fa fa-sort"></i></a>
									</if>-->
									</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("定金金额")}</th>
									<th id="shopList_c3" width="80">{pigcms{:L_("尾款金额")}</th>
									
									<th id="shopList_c4" width="70">{pigcms{:L_("支付状态")}</th>
<!-- 									<th id="shopList_c4" width="70">{pigcms{:L_("支付类型")}</th> -->
									<th id="shopList_c4" width="70">{pigcms{:L_("订单状态")}</th>
									
		
									<th id="shopList_c4" width="70">{pigcms{:L_("订单备注")}</th>
			
									<th id="shopList_c5" width="20" >{pigcms{:L_("查看订单详情")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.real_orderid}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.username}<br/>{pigcms{$vo.userphone}</div></td>
                                       

											
											<td>
                                                <if condition="$vo['book_id'] gt 0">
                                                    <if condition="$vo['book_status'] eq 1">
                                                        {pigcms{$vo.bargin_price|floatval}
                                                        <elseif condition="$vo['book_status'] eq 2"/>
                                                        {pigcms{$vo.book_all_pay|floatval}
                                                        <else/>
                                                        0
                                                    </if>
                                                    <else/>
                                                    {pigcms{$vo.price|floatval}
                                                </if>
                                            </td>

											
											<td>{pigcms{$vo.create_time|date="m-d H:i",###}</td>
											<if condition="$vo['book_time']">
											<td>{pigcms{$vo.book_time|date="m-d H:i",###}</td>
											<else/>
											<td></td>
											</if>
											
											<td>{pigcms{$vo.bargin_price|floatval}</td>
											
											
											<td>{pigcms{$vo.rest_price|floatval}</td>
											<if condition="$vo['book_status'] eq 1">
											<td>定金已支付</td>
                                            <elseif condition="$vo['book_status'] eq 2"/>
                                            <td>尾款已支付</td>
                                            <else/>
                                            <td><div class="tagDiv">定金未支付</div></td>
                                        	</if>
                                            <td>{pigcms{$vo.status_str}</td>
                                            
                                            
                                            
								<td width="160">
									{pigcms{:L_("备注：")}
									<input type="text" name="note" value="{pigcms{$vo.note}" style="width: 80px;">
									<button class="btn btn-success" style="float:right;display:inline;padding: 1px 4px;border-radius: 3px;" onclick="change_note(this,{pigcms{$vo.order_id});">{pigcms{:L_("提交")}</button>


								</td>

											
											<td>
												<if condition="empty($team_id) and $vo['group_id'] gt 0">
													<a title="{pigcms{:L_("操作订单")}" class="green" style="float:right" href="{pigcms{:U('Mall/order',array('store_id'=>$now_store['store_id'],'team_id'=>$vo['team_id']))}">
														<i class="ace-icon fa fa-search bigger-130"></i>
													</a>
												<else/>
													<a title="{pigcms{:L_("操作订单")}" class="green handle_btn" style="float:right" href="{pigcms{:U('Mall/order_detail',array('order_id'=>$vo['order_id']))}">
														<i class="ace-icon fa fa-search bigger-130"></i>
													</a>
												</if>
											</td>
											
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="30" >{pigcms{:L_("您的店铺暂时还没有预售订单。")}</td></tr>
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
	function change_note(obj,order_id){
		var note = $(obj).siblings('input[name="note"]').val();
		console.log(order_id)
		console.log(note)

		$.post('{pigcms{:U("change_mall_order_note")}',{order_id:order_id,note:note},function(date){
			alert(date.msg)
			window.location.reload();
		},'json');
	}
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

	$('#status').change(function(){
		location.href = "{pigcms{:U('Mall/order', array('store_id' => $now_store['store_id'], 'type' => $type, 'sort' => $sort))}&status=" + $(this).val();
	});	
});
 var url = "{pigcms{$config.site_url}"
    var export_url = "{pigcms{:U('Mall/export')}"
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>
