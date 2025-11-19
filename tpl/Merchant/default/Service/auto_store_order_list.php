<include file="Public:header"/>
<div class="main-content">
	<style type="text/css">
		.alert span{
			border-right: 1px solid #6da7fc82;
    		padding: 0px 10px;
		}

		.alert span:last-of-type{
			border-right: none;
		}
	</style>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<table class="search_table" width="100%">
						<tr>
							<td>
								<form action="{pigcms{:U('Service/offer_list')}" method="get">
									<input type="hidden" name="c" value="Service"/>
									<input type="hidden" name="a" value="auto_store_order_list"/>
									筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
									<select name="searchtype">
				                        <option value="order_sn" <if condition="$_GET['searchtype'] eq 'order_sn'">selected="selected"</if>>订单编号</option>
										<option value="nickname" <if condition="$_GET['searchtype'] eq 'nickname'">selected="selected"</if>>用户昵称</option>
										<option value="phone" <if condition="$_GET['searchtype'] eq 'phone'">selected="selected"</if>>用户手机</option>
									</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

									<font color="#000">日期筛选：</font>
									<input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="{pigcms{$_GET.start_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>			   
									<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})"/>
									

									店铺:
									<select name="store_id">
										<option value="" <if condition="!$_GET['store_id']">selected="selected"</if>>全部</option>
										<volist name="autoStoreArray" id="vo">
											<option value="{pigcms{$vo['store_id']}" <if condition="$_GET['store_id'] eq $vo['store_id']">selected="selected"</if>>{pigcms{$vo.name}</option>
										</volist>
									</select>

									<input type="submit" style="margin-right:20px;" value="查询" class="button"/>
								</form>
								
							</td>
				            <td>
				                <a href="javascript:void(0)" onclick="exports()" class="button" style="float:right;margin-right: 10px;">导出订单</a>
				            </td>
						</tr>
					</table>

					<div class="alert alert-info" style="margin:10px 0;">
						<span>{pigcms{:L_("总价汇总：")}{pigcms{$count_list.total_price|floatval}</span>
						<span>{pigcms{:L_("小费汇总：")}{pigcms{$count_list.tip_price|floatval}</span>
						<span>{pigcms{:L_("重量费用汇总：")}{pigcms{$count_list.weight_price|floatval}</span>
						<span>{pigcms{:L_("商家基础运费汇总：")}{pigcms{$count_list.basic_distance_price|floatval}</span>
						<span>{pigcms{:L_("超出基础费用汇总：")}{pigcms{$count_list.distance_price|floatval}</span>
						<span>{pigcms{:L_("预估商品费用汇总：")}{pigcms{$count_list.estimate_goods_price|floatval}</span>
						<span>{pigcms{:L_("总运费汇总：")}{pigcms{$count_list.delivery_fee_total|floatval}</span>
					</div>
					<div class="table-list">
						<table class="table table-striped table-bordered table-hover">
							<colgroup>
								<col/>
								<col/>
								<col/>
								<col/>
								<col/>
							</colgroup>
							<thead>
								<tr>
			                        <th>订单编号</th>
									<th>用户</th>
									<th>商家/店铺</th>
									<th>总价</th>
									<th>小费</th>
									<th>重量费用</th>
									<th>商家基础运费</th>
									<th>超出基础运费</th>
									<th>预估商品费用</th>
									<th>总运费</th>
									<th>下单时间</th>
									<th class="textcenter">订单详情</th>
								</tr>
							</thead>
							<tbody>
								<if condition="is_array($order_list)">
									<volist name="order_list" id="vo">
										<tr>
			                                <td>{pigcms{$vo.p_order_sn}</td>
											<td>{pigcms{$vo.nickname}</td>
											<td>{pigcms{$vo.merchant_name}/{pigcms{$vo.store_name}</td>
											<td>{pigcms{$vo.total_price}</td>
											<td>{pigcms{$vo.tip_price}</td>
											<td>{pigcms{$vo.weight_price}</td>
											<td>{pigcms{$vo.basic_distance_price}</td>
											<td>{pigcms{$vo.distance_price}</td>
											<td>{pigcms{$vo.estimate_goods_price}</td>
											<td>{pigcms{$vo.delivery_fee_total}</td>
											<td>{pigcms{$vo.add_time|date='Y-m-d H:i:s',###}</td>
											<td class="textcenter">
												<a class="handle_btn" href="{pigcms{:U('Service/offer_info',array('offer_id'=>$vo['offer_id']))}">查看</a>
											</td>
										</tr>
									</volist>
									<tr><td class="textcenter pagebar" colspan="12">{pigcms{$pagebar}</td></tr>
								<else/>
									<tr><td class="textcenter red" colspan="12">列表为空！</td></tr>
								</if>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
 	var url = "{pigcms{$config.site_url}";
    var export_url = "{pigcms{:U('auto_store_order_export')}";
    $('.handle_btn').live('click',function(){
		layer.open({
			title:'{pigcms{:L_("查看订单详情")}',
			area:['800px','80%'],
			type: 2,
			shade: 0.4,
			content:$(this).attr('href')
		});
		return false;
	});
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>