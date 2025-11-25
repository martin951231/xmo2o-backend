<include file="Public:header"/>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Service/auto_goodsname')}">分类列表</a>|
			<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/auto_cat_add')}','添加主分类',580,450,true,false,false,addbtn,'add',true);">添加分类</a>|
			<a href="{pigcms{:U('Service/auto_store_order_list')}" class="on"> 指定商家订单查询</a>
		</ul>
	</div>
	<style type="text/css">
		.alert {
		    font-size: 14px;
		    border-radius: 0;
    		padding: 15px;
    		border: 1px solid transparent;
		}
		.alert span{
			border-right: 1px solid #6da7fc82;
    		padding: 0px 10px;
		}

		.alert span:last-of-type{
			border-right: none;
		}
		.alert-info {
		    background-color: #d9edf7;
		    border-color: #bce8f1;
		    color: #31708f;
		}
	</style>
    <script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
	<table class="search_table" width="100%">
		<tr>
			<td>
				<form action="{pigcms{:U('Service/offer_list')}" method="get">
					<input type="hidden" name="c" value="Service"/>
					<input type="hidden" name="a" value="auto_store_order_list"/>
					筛选:
                    <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}" area_id="{pigcms{$_GET.area_id}" style="display:inline"></div>
                 <!--   <div id="choose_pca" province_idss="{pigcms{$_GET.province_idss}" city_idss="{pigcms{$_GET.city_idss}"  style="display:inline"></div>-->
                    <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
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
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
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
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Service/offer_info',array('offer_id'=>$vo['offer_id'],'frame_show'=>true))}','查看订单信息',600,600,true,false,false,false,'detail',true);">查看</a>
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
	</form>
</div>
<script type="text/javascript">
 	var url = "{pigcms{$config.site_url}";
    var export_url = "{pigcms{:U('auto_store_order_export')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/export.js"> </script>
<include file="Public:footer"/>