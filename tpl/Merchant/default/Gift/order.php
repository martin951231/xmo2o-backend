<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Gift/order')}">{pigcms{:L_("商家")}{pigcms{$config.gift_alias_name}{pigcms{:L_("订单列表")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">					
					<div class="form-group">
						<form action="{pigcms{:U('Gift/order')}" method="get">
							<input type="hidden" name="c" value="Gift"/>
							<input type="hidden" name="a" value="order"/>
							{pigcms{:L_("筛选")}: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}"/>
							<select name="searchtype">
								<option value="order_name" <if condition="$_GET['searchtype'] eq 'order_name'">selected="selected"</if>>{pigcms{$config.gift_alias_name}{pigcms{:L_("名称")}</option>
								<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>{pigcms{:L_("订单编号")}</option>
							</select>
							<input type="submit" value="{pigcms{:L_("查询")}" class="button"/>
							&nbsp;&nbsp;&nbsp;&nbsp;
							{pigcms{:L_("订单状态筛选")}: 
							<select id="status" name="status">
									<option value="0" <if condition="empty($_GET['status'])">selected="selected"</if>>{pigcms{:L_("全部")}</option>
									<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>{pigcms{:L_("未发货")}</option>
									<option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>{pigcms{:L_("已发货")}</option>
							</select>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("订单编号")}</th>
									<th>{pigcms{$config.gift_alias_name}{pigcms{:L_("信息")}</th>
									<th>{pigcms{:L_("订单信息")}</th>
									<th>{pigcms{:L_("订单用户")}</th>
									<th>{pigcms{:L_("订单状态")}</th>
									<th>									
										<if condition="$type eq 'order_time'">
											<if condition="$sort eq 'ASC'">
												<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'DESC'))}" style="color:blue;">{pigcms{:L_("下单时间")}↑</a>
											<elseif condition="$sort eq 'DESC'" />
												<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'ASC'))}" style="color:blue;">{pigcms{:L_("下单时间")}↓</a>
											<else />
												<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'DESC'))}" style="color:blue;">{pigcms{:L_("下单时间")}</a>
											</if>
										<else />
											<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'DESC'))}" style="color:blue;">{pigcms{:L_("下单时间")}</a>
										</if>									
									</th>
									<th class="textcenter">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$order_list">
									<volist name="order_list" id="vo">
										<tr class='<if condition="$i%2 eq 0">odd<else/>even</if>'>
											<td>{pigcms{$vo.order_id}</td>
											<td>{pigcms{$config.gift_alias_name}ID：{pigcms{$vo.gift_id}　
											<br/>{pigcms{$config.gift_alias_name}{pigcms{:L_("名称：")}{pigcms{$vo.gift_name}</td>
											<td>{pigcms{:L_("数量：")}{pigcms{$vo.num}<br/>
											总{pigcms{$config['score_name']}：{pigcms{$vo.total_integral|floatval=###}
											<if condition="$vo['exchange_type'] eq 1">
												&nbsp;&nbsp;&nbsp;&nbsp;{pigcms{:L_("总价：")}{pigcms{$config['Currency_symbol']}{pigcms{$vo.total_price|floatval=###}
											</if>
											</td>
											<td>{pigcms{:L_("用户名：")}{pigcms{$vo.nickname}<br/>{pigcms{:L_("订单手机号：")}{pigcms{$vo.gift_phone}</td>
											<td>												
												<if condition="$vo['paid'] eq 0">
													<font color="red">{pigcms{:L_("未支付")}</font>
												<else />
													<font color="green">{pigcms{:L_("已支付")}</font>
												</if>
												&nbsp;&nbsp;
												<if condition='$vo["status"] eq 0'>
													<font class="red">{pigcms{:L_("未发货")}</font>
												<else />
													<font color="green">{pigcms{:L_("已发货")}</font>
												</if>
											</td>
											<td>
												{pigcms{$vo['order_time']|date='Y-m-d H:i:s',###}
											</td>											
											<td class="textcenter"><a href="javascript:;" data-src="{pigcms{:U('Gift/order_detail',array('order_id'=>$vo['order_id']))}" class="handle_btn">{pigcms{:L_("查看详情")}</a></td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="30" >{pigcms{:L_("列表为空！")}</td></tr>
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
		art.dialog.open($(this).data('src'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
			},
			id: 'handle',
			title:'{pigcms{:L_("查看")}{pigcms{$config.gift_alias_name}{pigcms{:L_("订单详情")}',
			padding: 0,
			width: 600,
			height: 460,
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
});
</script>
<include file="Public:footer"/>
