<include file="Public:header"/>
<div class="main-content">

	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="clearfix">
						
						<form action="{pigcms{:U('Foodshop/sort_goods_search')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Foodshop"/>
							<input type="hidden" name="a" value="sort_goods_search"/>
							<input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
							<select name="search_type">
								<option value="name" <eq name="_GET.search_type" value='name'>selected='selected'</eq> >{pigcms{:L_("商品名称")}</option>
							</select>&nbsp;&nbsp;
							<input type="text" name="keyword" class="input-text" placeholder='{pigcms{:L_("请输入商品名称")}'/>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("排序")}</th>
									<th>{pigcms{:L_("分类名称")}</th>
									<th>{pigcms{:L_("是否是附属商品分类")}</th>
									<th>{pigcms{:L_("星期几显示")}</th>
									<th>{pigcms{:L_("商品管理")}</th>
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
											<td>
												<label class="statusSwitch" style="display:inline-block;">
													<if condition="$vo['status'] eq 0">是<else/>否</if>
												</label>
											</td>
											<td>{pigcms{$vo.week_str}</td>
											<td>
											     <if condition="$vo['operation_type'] eq 2">
											         <a style="width: 60px;" class="label label-sm label-purple" href="{pigcms{:U('goods_sort_new',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("子分类")}</a>
											         <a style="width: 60px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('goods_list_new',array('sort_id'=>$vo['sort_id']))}">{pigcms{:L_("商品管理")}</a>
											     <elseif condition="$vo['operation_type'] eq 1" />
											         <a style="width: 60px;" class="label label-sm label-purple" href="{pigcms{:U('goods_sort_new',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("子分类")}</a>
											     <else />
											         <a style="width: 60px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('goods_list_new',array('sort_id'=>$vo['sort_id']))}">{pigcms{:L_("商品管理")}</a>
											     </if>
											</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Shop/sort_edit',array('sort_id'=>$vo['sort_id'], 'fid' => $vo['fid']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8" >{pigcms{:L_("无内容")}</td></tr>
								</if>
							</tbody>
						</table>
						<!-- {pigcms{$pagebar} -->
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