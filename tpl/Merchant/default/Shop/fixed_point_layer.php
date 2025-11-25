<include file="Public:header"/>
<div class="main-content">
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="clearfix">
						<a class="btn btn-success" href="{pigcms{:U('Shop/fixed_point_layer_edit',array('store_id' => $_GET['store_id'],'floor_id'=>$_GET['floor_id'], 'store_id' => $_GET['store_id'], 'area_id' => $_GET['area_id']))}" style="float:left;">{pigcms{:L_("新增层号")}</a>&nbsp;&nbsp;
						<a class="btn btn-success" href="{pigcms{:U('Shop/fixed_point_floor',array('store_id' => $_GET['store_id'],'floor_id'=>$_GET['floor_id'],'layer_id'=>$_GET['layer_id'],'area_id' => $_GET['area_id']))}">{pigcms{:L_("返回上一级")}</a>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th style="width: 25%;">{pigcms{:L_("编号")}</th>
									<th style="width: 25%;">{pigcms{:L_("楼层名称")}</th>
									<th style="width: 25%;">{pigcms{:L_("楼层名称")}</th>
									<th style="width: 25%;">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.layer_id}</td>
											<td>{pigcms{$vo.layer_num}</td>
											<td>
												<a style="width: 60px;" class="label label-sm label-purple" href="{pigcms{:U('Shop/fixed_point_room',array('layer_id' => $vo['layer_id'], 'floor_id' => $vo['floor_id'], 'store_id' => $vo['store_id'], 'area_id' => $_GET['area_id']))}">{pigcms{:L_("管理房号")}</a>
											</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Shop/fixed_point_layer_edit',array('layer_id'=>$vo['layer_id'], 'store_id' => $vo['store_id'], 'floor_id' => $vo['floor_id'], 'area_id' => $_GET['area_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Shop/fixed_point_layer_del',array('layer_id'=>$vo['layer_id'], 'store_id' => $vo['store_id']))}">
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
	/*{pigcms{:L_("店铺状态")}*/
	jQuery(document).on('click','#shopList a.red',function(){
		if(!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
	});
});
</script>
<include file="Public:footer"/>