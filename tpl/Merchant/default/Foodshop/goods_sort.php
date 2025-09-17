<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Foodshop/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{$now_store.name}</li>
			<li class="active">{pigcms{:L_("分类列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("新建分类")}</button>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("排序")}</th>
									<th>{pigcms{:L_("分类名称")}</th>
									<th>{pigcms{:L_("星期几显示")}</th>
									<th>{pigcms{:L_("显示时段")}</th>
									<th>{pigcms{:L_("商品数量")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$sort_list">
									<volist name="sort_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.sort_id}</td>
											<td>{pigcms{$vo.sort}</td>
											<td>{pigcms{$vo.sort_name}</td>
											<td>{pigcms{$vo.week_str}</td>
											<td>{pigcms{:substr($vo['show_start_time'], 0, -3)}-{pigcms{:substr($vo['show_end_time'], 0, -3)}</td>
											<td>{pigcms{$vo.goods_count}</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Foodshop/sort_edit',array('sort_id' => $vo['sort_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Foodshop/sort_del',array('sort_id' => $vo['sort_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
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
<script type="text/javascript">
$(function(){
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
function CreateShop(){
	window.location.href = "{pigcms{:U('Foodshop/sort_add',array('store_id'=>$now_store['store_id']))}";
}
</script>
<include file="Public:footer"/>
