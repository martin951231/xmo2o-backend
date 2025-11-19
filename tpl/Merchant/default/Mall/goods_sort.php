<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/index')}">{pigcms{$now_store.name}</a></li>
			<if condition="$sortList">
                <volist name="sortList" id="sl">
                    <li class="active"><a href="{pigcms{:U('Mall/goods_sort', array('fid' => $sl['fid'], 'store_id' => $now_store['store_id']))}">{pigcms{$sl['sort_name']}</a></li>
                </volist>
			</if>
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
					<div class="clearfix">
						<a class="btn btn-success" href="{pigcms{:U('Mall/sort_add',array('store_id' => $now_store['store_id'], 'fid' => $fid))}" style="float:left;">{pigcms{:L_("新建分类")}</a>&nbsp;&nbsp;
                        <a class="btn btn-success" href="{pigcms{:U('Shop/shop_goods_group',array('store_id' => $now_store['store_id']))}" style="margin-left: 35px;margin-top: 1px;">{pigcms{:L_("分组列表")}</a>
						<form action="{pigcms{:U('Mall/sort_goods_search')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Mall"/>
							<input type="hidden" name="a" value="sort_goods_search"/>
							<input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
							<select name="search_type">
								<option value="name" <eq name="_GET.search_type" value='name'>selected='selected'</eq> >{pigcms{:L_("商品名称")}</option>
								<option value="number" <eq name="_GET.search_type" value='number'>selected='selected'</eq>>{pigcms{:L_("商品条形码")}</option>
							</select>&nbsp;&nbsp;
							<input type="text" name="keyword" class="input-text" placeholder='{pigcms{:L_("请输入商品名称")}'/>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>

						<a class="btn btn-success" href="{pigcms{:U('batchOperation',array('store_id' => $now_store['store_id']))}" style="margin-left: 35px;margin-top: 1px;">{pigcms{:L_("批量上传/更新商品")}</a>
					</div>
					<div class="alert alert-info" style="margin:10px;">
						<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>1. {pigcms{:L_("商城商品与X1商品是共用一个商品管理信息，可通过筛选商品类型来区分。",['X1'=>$config['shop_alias_name']])}<br/>
						2. {pigcms{:L_("商城商品不可使用分类折扣。")}<br/>
						3. {pigcms{:L_("每个商品均可以设置为仅为商城使用、仅为X1使用、商城与X1均可使用。",['X1'=>$config['shop_alias_name']])}<br/>
						4. {pigcms{:L_("设置【仅为商城使用】的商品不可在线下零售使用，其他商品可在线下零售使用。")}<br/>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("排序")}</th>
									<th>{pigcms{:L_("分类名称")}</th>
<!--									<th>{pigcms{:L_("是否开启只星期几显示")}</th>-->
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
<!--											<td>-->
<!--												<label class="statusSwitch" style="display:inline-block;">-->
<!--													<input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="{pigcms{$vo.sort_id}" <if condition="$vo['is_weekshow'] eq 1">checked="checked" data-status="OPEN"<else/>data-status="CLOSED"</if>/>-->
<!--													<span class="lbl"></span>-->
<!--												</label>-->
<!--											</td>-->
											<td>{pigcms{$vo.week_str}</td>
											<td>
											     <if condition="$vo['operation_type'] eq 2">
											         <a style="width: 60px;" class="label label-sm label-purple" href="{pigcms{:U('Mall/goods_list',array('sort_id'=>$vo['sort_id']))}">{pigcms{:L_("子分类")}</a>
											         <a style="width: 60px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('Mall/goods_sort',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("商品管理")}</a>
											     <elseif condition="$vo['operation_type'] eq 1" />
											         <a style="width: 60px;" class="label label-sm label-purple" href="{pigcms{:U('Mall/goods_sort',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("子分类")}</a>
											     <else />
											         <a style="width: 60px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('Mall/goods_list',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("商品管理")}</a>
											     </if>
											</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_edit',array('sort_id'=>$vo['sort_id'], 'fid' => $vo['fid']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_del',array('sort_id'=>$vo['sort_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>　　
												<if condition="empty($fid)">
												<a style="width: 60px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('Mall/sort_order',array('sort_id' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("销量详情")}</a>
											    </if>
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
	updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");
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
function updateStatus(dom1, dom2, status1, status2, attribute){
	$(dom1).each(function(){
		if($(this).attr("data-status")==status1){
			$(this).attr("checked",true);
		}else{
			$(this).attr("checked",false);
		}
		$(dom2).show();
	}).click(function(){
		var _this = $(this),
		 	type = 'open',
		 	id = $(this).attr("data-id");
		_this.attr("disabled",true);
		if(_this.attr("checked")){	//"开启")}
			type = 'open';
		}else{		//"关闭")}
			type = 'close';
		}
		$.ajax({
			url:"{pigcms{:U('Mall/sort_status')}",
			type:"post",
			data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
			dataType:"text",
			success:function(d){
				if(d != '1'){		//"失败")}
					if(type=='open'){
						_this.attr("checked",false);
					}else{
						_this.attr("checked",true);
					}
					bootbox.alert("{pigcms{:L_("操作失败")}");
				}
				_this.attr("disabled",false);
			}
		});
	});
}
</script>
<include file="Public:footer"/>