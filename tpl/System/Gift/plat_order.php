<include file="Public:header" />
<script type="text/javascript" src="{pigcms{$static_path}js/area_pca.js"></script>
<div class="mainbox">
	<div id="nav" class="mainnav_title">
		<ul>
			<a href="{pigcms{:U('Gift/order')}">订单列表</a>
			<a href="{pigcms{:U('Gift/plat_order')}" class="on">平台配送订单</a>
		</ul>
	</div>
	<table class="search_table" width="100%">
		<tr>
			<td>
				<form action="{pigcms{:U('Gift/plat_order')}" method="get">
					<input type="hidden" name="c" value="Gift" />
					<input type="hidden" name="a" value="plat_order" />
					筛选: <input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" />
					<select name="searchtype">
						<option value="order_name" <if condition="$_GET['searchtype'] eq 'order_name'">selected="selected"</if>>{pigcms{$config.gift_alias_name}名称</option>
						<option value="order_id" <if condition="$_GET['searchtype'] eq 'order_id'">selected="selected"</if>>订单编号</option>
					</select>
					<input type="submit" value="查询" class="button" />
					&nbsp;&nbsp;&nbsp;&nbsp;
					订单状态筛选:
					<select id="status" name="status">
						<option value="0" <if condition="empty($_GET['status'])">selected="selected"</if>>全部</option>
						<option value="1" <if condition="$_GET['status'] eq 1">selected="selected"</if>>未发货</option>
						<option value="2" <if condition="$_GET['status'] eq 2">selected="selected"</if>>已发货</option>
						<option value="3" <if condition="$_GET['status'] eq 3">selected="selected"</if>>已收货</option>
					</select>
				</form>
			</td>
		</tr>
	</table>

	<form name="myform" id="myform" action="" method="post">
		<div class="table-list">
			<style>
				.table-list td {
					line-height: 22px;
					padding-top: 5px;
					padding-bottom: 5px;
				}
			</style>
			<table width="100%" cellspacing="0">
				<colgift>
					<col />
					<col />
					<col />
					<col />
					<col />
					<col />
					<col />
				</colgift>
				<thead>
					<tr>
						<th>订单编号</th>
						<th>{pigcms{$config.gift_alias_name}信息</th>
						<th>订单信息</th>
						<th>订单用户</th>
						<th>查看用户信息</th>
						<th>订单状态</th>
						<th>

							<if condition="$type eq 'order_time'">
								<if condition="$sort eq 'ASC'">
									<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'DESC'))}" style="color:blue;">下单时间↑</a>
									<elseif condition="$sort eq 'DESC'" />
									<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'ASC'))}" style="color:blue;">下单时间↓</a>
									<else />
									<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'DESC'))}" style="color:blue;">下单时间</a>
								</if>
								<else />
								<a href="{pigcms{:U('order', array('type' => 'order_time', 'sort' => 'DESC'))}" style="color:blue;">下单时间</a>
							</if>

						</th>
						<th>订单类型</th>
						<th class="textcenter">操作</th>
					</tr>
				</thead>
				<tbody>
					<if condition="is_array($order_list)">
						<volist name="order_list" id="vo">
							<tr>
								<td>{pigcms{$vo.order_id}</td>
								<td>{pigcms{$config.gift_alias_name}ID：{pigcms{$vo.gift_id}　
									<br />{pigcms{$config.gift_alias_name}名称：{pigcms{$vo.gift_name}
								</td>
								<td>数量：{pigcms{$vo.num}<br />
									总{pigcms{$config['score_name']}：{pigcms{$vo.total_integral|floatval=###}
									<if condition="$vo['exchange_type'] eq 1">
										&nbsp;&nbsp;&nbsp;&nbsp;总价：{pigcms{$config['Currency_symbol']}{pigcms{$vo.total_price|floatval=###}
									</if>
								</td>
								<td>
									<if condition="$vo['uid']">用户名：{pigcms{$vo.nickname}
										<else />商家名：{pigcms{$vo.name}
									</if><br />订单手机号：{pigcms{$vo.gift_phone}
								</td>
								<td>
									<if condition="$vo['uid']">
										<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('User/edit',array('uid'=>$vo['uid']))}','编辑用户信息',680,560,true,false,false,editbtn,'edit',true);">查看用户信息</a>
										<else />
										&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;--
									</if>
								</td>
								<td>

									<if condition="$vo['paid'] eq 0">
										<font color="red">未支付</font>
										<else />
										<font color="green">已支付</font>
									</if>
									&nbsp;&nbsp;
									<if condition='$vo["status"] eq 0'>
										<font class="red">未发货</font>
										<elseif condition='$vo["status"] eq 2' />
										<font class="green">已收货</font>
										<else />
										<font color="orange">已发货</font>
									</if>
								</td>
								<td>
									{pigcms{$vo['order_time']|date='Y-m-d H:i:s',###}
								</td>
								<td>
									<gt name="vo.g_mer_id" value='0'>
										商家订单/{pigcms{$vo.mer_name}
										<else />
										平台订单
									</gt>
								</td>
								<td class="textcenter">
									<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Gift/order_detail',array('order_id'=>$vo['order_id']))}','查看{pigcms{$config.gift_alias_name}订单详情',600,460,true,false,false,false,'order_edit',true);">查看详情</a>
									<if condition="($vo['paid'] eq 1) and ($vo['status'] eq 0) and ($vo['is_pick_in_store'] eq 2)">
										&nbsp| &nbsp<a href="javascript:void(0);" onclick="send_deliver({pigcms{$vo['order_id']})" style="color:green;">发货</a>
									</if>
								</td>
							</tr>
						</volist>
						<tr>
							<td class="textcenter pagebar" colspan="8">{pigcms{$pagebar}</td>
						</tr>
						<else />
						<tr>
							<td class="textcenter red" colspan="8">列表为空！</td>
						</tr>
					</if>
				</tbody>
			</table>
		</div>
	</form>
</div>

<script>
	$(function() {
		$('#status').change(function() {
			location.href = "{pigcms{:U('plat_order', array('type' => $type, 'sort' => $sort))}&status=" + $(this).val();
		});
	});

	function send_deliver(order_id) {
		window.o2oTop.art.dialog({
			icon: 'question',
			title: '发货确认',
			id: 'msg' + Math.random(),
			lock: true,
			fixed: true,
			opacity: '0.4',
			resize: false,
			content: '当前订单编号【' + order_id + '】，请确认是否为该订单安排发货，发货后无法修改或撤销！',
			ok: function() {

				$.post("{pigcms{:U('Gift/send_deliver')}", {
					order_id: order_id
				}, function(data) {
					if (data.status == 1) {
						window.o2oTop.msg(1, data.info);
						window.location.reload();
					} else {
						window.o2oTop.msg(0, data.info);
					}
				});
			},
			cancel: true
		});
	}
</script>
<include file="Public:footer" />