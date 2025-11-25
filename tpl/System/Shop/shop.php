<include file="Public:header" />
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Shop/shop')}" class="on">{pigcms{$config['deliver_name']}店铺列表</a>|
			<a href="{pigcms{:U('Shop/open_shop')}">营业中店铺</a>
			<a href="{pigcms{:U('Shop/close_shop')}">未营业店铺</a>
		</ul>
	</div>
	<table class="search_table" width="100%">
		<tr>
			<td>
				<form action="{pigcms{:U('Shop/shop')}" method="get">
					<input type="hidden" name="c" value="Shop" />
					<input type="hidden" name="a" value="shop" />
					<select name="searchtype">
						<option value="store_name" <if condition="$_GET['searchtype'] eq 'store_name'">selected="selected"</if>>店铺名称</option>
						<option value="mer_name" <if condition="$_GET['searchtype'] eq 'mer_name'">selected="selected"</if>>商家名称</option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" />

					<font color="#000">服务范围：</font>
					<select name="delivery_range">
						<option value="0" <if condition="$_GET['delivery_range'] eq '0'">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['delivery_range'] eq '1'">selected="selected"</if>>已设置</option>
						<option value="2" <if condition="$_GET['delivery_range'] eq '2'">selected="selected"</if>>未设置</option>
					</select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

					<font color="#000">创建时间：</font>
					<input type="text" class="input-text" name="begin_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.begin_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" />
					<input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="{pigcms{$_GET.end_time}" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd'})" />
					<input type="submit" value="查询" class="button" />
					<input type="button" value="导出" class="button" onclick="store_export()" />
				</form>
			</td>
		</tr>
	</table>
	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<table width="100%" cellspacing="0">
				<thead>
					<tr>
						<th>店铺ID</th>
						<th>商家名称</th>
						<th>店铺名称</th>
						<th>独立配送设置</th>
						<th>服务范围</th>
						<th>商家电话</th>
						<th>店铺电话</th>
						<th>创建时间</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="is_array($order_list)">
						<volist name="order_list" id="vo">
							<tr>
								<td>{pigcms{$vo.store_id}</td>
								<td>{pigcms{$vo.merchant_name}</td>
								<td>{pigcms{$vo.store_name}</td>
								<td>
									<if condition="$vo['s_is_open_own'] eq 1"><b style="color:red">开启</b>
										<else />关闭
									</if>
								</td>
								<td>
									<if condition="$vo['delivery_range_type'] eq 1">自定义
										<elseif condition="$vo['delivery_radius'] gt 0" />{pigcms{$vo.delivery_radius|floatval}公里
										<else /><b style="color:red">未设置</b>
									</if>
								</td>
								<td>{pigcms{$vo.merchant_phone}</td>
								<td>{pigcms{$vo.store_phone}</td>
								<td>{pigcms{$vo.create_time|date="Y-m-d H:i:s",###}</td>
								<td class="textcenter"><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Shop/shop_edit',array('store_id' => $vo['store_id']))}','设置店铺配送',680,750,true,false,false,editbtn,'edit',true);">设置店铺配送</a></td>
							</tr>
						</volist>
						<tr>
							<td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td>
						</tr>
						<else />
						<tr>
							<td class="textcenter red" colspan="13">列表为空！</td>
						</tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>

<script type="text/javascript">
	function store_export() {
		var searchtype = $('select[name=searchtype]').val();
		var keyword = $('input[name=keyword]').val();
		var delivery_range = $('select[name=delivery_range]').val();
		var begin_time = $('input[name=begin_time]').val();
		var end_time = $('input[name=end_time]').val();
		var url = "{pigcms{:U('Shop/shop')}" +
			'&searchtype=' + searchtype +
			'&keyword=' + keyword +
			'&delivery_range=' + delivery_range +
			'&begin_time=' + begin_time +
			'&end_time=' + end_time +
			'&is_export=1';
		window.open(url);
	}
</script>
<include file="Public:footer" />