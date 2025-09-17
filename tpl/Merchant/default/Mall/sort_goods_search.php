<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{$now_store.name}</a></li>
			<li class="active">{pigcms{:L_("商品搜索")}</li>
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
						<form action="{pigcms{:U('Mall/sort_goods_search')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Mall"/>
							<input type="hidden" name="a" value="sort_goods_search"/>
							<input type="hidden" name="store_id" value="{pigcms{$now_store.store_id}"/>
							<select name="search_type">
								<option value="name" <eq name="_GET.search_type" value='name'>selected='selected'</eq> >{pigcms{:L_("商品名称")}</option>
								<option value="number" <eq name="_GET.search_type" value='number'>selected='selected'</eq>>{pigcms{:L_("商品条形码")}</option>
							</select>&nbsp;&nbsp;
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入商品名称")}'/>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("编号")}</th>
									<th width="50">{pigcms{:L_("排序")}</th>
									<th class="button-column">{pigcms{:L_("商品名称")}</th>
									<th width="100">{pigcms{:L_("价格")}</th>
                                    <th width="100">{pigcms{:L_("二维码")}</th>
									<th class="button-column" style="width:100px;">{pigcms{:L_("单位")}</th>
									<th width="80">{pigcms{:L_("原始库存")}</th>
									<th width="80">{pigcms{:L_("实际库存")}</th>
									<th width="80">{pigcms{:L_("今日销量")}</th>
									<th width="80">{pigcms{:L_("总销量")}</th>
									<th class="button-column" style="width:180px;">{pigcms{:L_("最后操作时间")}</th>
									<th class="button-column" style="width:100px;">{pigcms{:L_("归属打印机")}</th>
									<th width="100" class="button-column">{pigcms{:L_("状态")}</th>
									<th width="100" class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$goods_list">
									<volist name="goods_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.goods_id}</td>
											<td>{pigcms{$vo.sort}</td>
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.price|floatval}</td>
                                            <td><a href="{pigcms{:U('see_goods_qrcode',array('code'=>$vo['goods_id']))}" class="see_qrcode">{pigcms{:L_("二维码")}</a></td>
											<td class="button-column">{pigcms{$vo.unit}</td>
											<if condition="$vo['stock_num'] eq -1">
											<td>{pigcms{:L_("无限")}</td>
											<else />
											<td>{pigcms{$vo.stock_num}</td>
											</if>
											<td>{pigcms{$vo.stock_num_t}</td>
											<td>{pigcms{$vo.today_sell_count}</td>
											<td>{pigcms{$vo.sell_count}</td>
											<td class="button-column">{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
											<td class="button-column">{pigcms{$vo.print_name}</td>
											<td class="button-column">
												<label class="statusSwitch" style="display:inline-block;">
													<input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="{pigcms{$vo.goods_id}" <if condition="$vo['status'] eq 1">checked="checked" data-status="OPEN"<else/>data-status="CLOSED"</if>/>
													<span class="lbl"></span>
												</label>
											</td>
											<td class="button-column">
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Mall/goods_edit',array('goods_id'=>$vo['goods_id']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Mall/goods_del',array('goods_id'=>$vo['goods_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
                                                <if condition="empty($vo['original_goods_id']) && $config['is_open_market'] && $merchant_menu[110]['menu_list'][10008]">
                                                    <a title="{pigcms{:L_("发布商品到市场")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Mall/goods_push',array('goods_id'=>$vo['goods_id']))}">{pigcms{:L_("发布")}</a>
                                                </if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="13" >{pigcms{:L_("无内容")}</td></tr>
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
function CreateShop(){
	window.location.href = "{pigcms{:U('Mall/goods_add',array('sort_id' => $now_sort['sort_id']))}";
}
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
			url:"{pigcms{:U('Mall/goods_status')}",
			type:"post",
			data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
			dataType:"text",
			success:function(d){
				if(d != '1'){		//"失败")}
					if (type=='open') {
						_this.attr("checked",false);
					} else {
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
