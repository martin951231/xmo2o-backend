<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Shop/index')}">{pigcms{$now_store.name}</a></li>
			<if condition="$sortList">
                <volist name="sortList" id="sl">
                    <li class="active"><a href="{pigcms{:U('Shop/goods_sort', array('fid' => $sl['fid'], 'store_id' => $now_store['store_id']))}">{pigcms{$sl['sort_name']}</a></li>
                </volist>
			</if>
			<li class="active">{pigcms{:L_("区域列表")}</li>
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
					<div class="clearfix">
						<a class="btn btn-success" href="{pigcms{:U('Shop/fixed_point_edit',array('store_id' => $_GET['store_id']))}" style="float:left;">{pigcms{:L_("新增位置")}</a>                       
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th style="width: 20%;">{pigcms{:L_("编号")}</th>
									<th style="width: 20%;">{pigcms{:L_("位置名称")}</th>
									<th style="width: 20%;">{pigcms{:L_("区域")}</th>
									<th style="width: 20%;">{pigcms{:L_("管理楼号")}</th>
									<th style="width: 20%;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.id}</td>
											<td>{pigcms{$vo.address}</td>
											<td>{pigcms{$vo.area_name}</td>
											<td>
											<a style="width: 60px;" class="label label-sm label-purple" href="{pigcms{:U('Shop/fixed_point_floor',array('area_id' => $vo['id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("管理楼号")}</a>
											</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Shop/fixed_point_edit',array('id'=>$vo['id'], 'store_id' => $vo['store_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Shop/fixed_point_del',array('id'=>$vo['id'], 'store_id' => $vo['store_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>　
											    <a style="width: 100px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('Shop/code_export',array('id'=>$vo['id']))}">
													导出所有二维码
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
	/*{pigcms{:L_("店铺状态")}*/
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});

	/*{pigcms{:L_("选择搜索类型")}*/
	$('select[name=search_type]').change(function(){
		var val=$(this).val();
		var tip='';
		if(val=='name'){
			tip='{pigcms{:L_("请输入商品名称！")}';
		}else if(val=='number'){
			tip='{pigcms{:L_("请输入商品条形码！")}';
		}
		$(this).next().attr('placeholder',tip);
	});
});
</script>
<include file="Public:footer"/>