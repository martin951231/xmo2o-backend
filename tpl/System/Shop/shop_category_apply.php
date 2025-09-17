<include file="Public:header"/>
	<div class="mainbox">
		<table class="search_table" width="100%">
			<tr>
				<td>
					<form action="{pigcms{:U('Shop/shop')}" method="get">
						<input type="hidden" name="c" value="Shop"/>
						<input type="hidden" name="a" value="shop_category_apply"/>
						店铺名: <input type="text" name="name" class="input-text" value="{pigcms{$_GET['name']}"/>
						<input type="submit" value="查询" class="button"/>
					</form>
				</td>
			</tr>
		</table>
		<form name="myform" id="myform" action="" method="post">
			<div class="table-list">
				<table width="100%" cellspacing="0">
					<thead>
						<tr>
							<th>店铺名称</th>
							<th>联系电话</th>
							<th>申请时间</th>
							<th>当前使用中的分类</th>
							<th>申请添加的分类</th>
							<th class="textcenter">操作</th>
						</tr>
					</thead>
					<tbody>
						<if condition="is_array($list)">
							<volist name="list" id="vo">
								<tr>
									<td>{pigcms{$vo.name}</td>
									<td>{pigcms{$vo.phone}</td>
									<td>{pigcms{$vo.cate_apply_time|date="Y-m-d H:i:s",###}</td>
									<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Shop/show_category',array('store_id'=>$vo['store_id']))}','查看详情',550,450,true,false,false,'','detail',true);">查看详情</a></td>
									<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Shop/show_category',array('store_id'=>$vo['store_id'],'apply'=>1))}','查看详情',550,450,true,false,false,'','detail',true);">查看详情</a></td>
									<td class="textcenter">
										<a href="javascript:void(0);" onclick="do_apply({pigcms{$vo['store_id']},'1')">审核通过</a>|
										<a href="javascript:void(0);" onclick="do_apply({pigcms{$vo['store_id']},'0')">拒绝</a>
									</td>
								</tr>
							</volist>
							<tr><td class="textcenter pagebar" colspan="13">{pigcms{$pagebar}</td></tr>
						<else/>
							<tr><td class="textcenter red" colspan="13">列表为空！</td></tr>
						</if>
					</tbody>
				</table>
			</div>
		</form>
	</div>
	<script type="text/javascript">
		function do_apply(store_id,agree) {
			var text = '确认审核通过？';
			if (agree=='0') {
				text = '确认审核拒绝？';
			}
			window.location.href="{pigcms{:U('Shop/do_apply')}"+'&store_id='+store_id+'&agree='+agree;
			return;
			if (confirm(text)) {
				$.post("{pigcms{:U('Shop/do_apply')}",{store_id:store_id,agree:agree},function(data){
					if (data.status==1) {
						alert(data.status);
					}
				})
			}
		}
	</script>
<include file="Public:footer"/>