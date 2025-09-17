<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('index')}">{pigcms{:L_("酒店管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("房型类别列表")}</li>
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
									<th>{pigcms{:L_("子分类管理")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$cat_list">
									<volist name="cat_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.cat_id}</td>
											<td>{pigcms{$vo.cat_sort}</td>
											<td>{pigcms{$vo.cat_name}</td>
											<td>
												<a class="green" style="padding-right:8px;" title="{pigcms{:L_("子分类管理")}" href="{pigcms{:U('son_cat_list',array('cat_id'=>$vo['cat_id']))}">{pigcms{:L_("子分类管理")}<i class="ace-icon fa fa-pencil bigger-130"></i></a>
											</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('category_edit',array('cat_id'=>$vo['cat_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('category_del',array('cat_id'=>$vo['cat_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="5" >{pigcms{:L_("无内容")}</td></tr>
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
	window.location.href = "{pigcms{:U('category_add')}";
}
</script>
<include file="Public:footer"/>
