<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
			<li class="active">{pigcms{$now_sort.sort_name}</li>
			<li class="active">{pigcms{:L_("商品列表")}</li>
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
						<button class="btn btn-success" onclick="CreateShop()" style="float:left;">{pigcms{:L_("添加周期购商品")}</button>
						<form action="{pigcms{:U('Mall_periodic_purchase/index')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Mall_periodic_purchase"/>
							<input type="hidden" name="a" value="index"/>
							<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
							<!-- {pigcms{:L_("搜索类型")} 
							<select name="search_type">
								<option value="name" <eq name="_GET.search_type" value='name'>selected='selected'</eq> >{pigcms{:L_("商品名称")}</option>
								<option value="number" <eq name="_GET.search_type" value='number'>selected='selected'</eq>>{pigcms{:L_("商品条形码")}</option>
							</select>&nbsp;&nbsp;-->
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入商品名称")}'/>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
						</form>
					</div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th class="button-column">{pigcms{:L_("商品名称")}</th>
									<th width="50">{pigcms{:L_("价格")}</th>
									<!--<th width="50">{pigcms{:L_("原始库存")}</th>
									<th width="50">{pigcms{:L_("实际库存")}</th>
									<th width="50">{pigcms{:L_("今日销量")}</th>
									<th width="50">{pigcms{:L_("总销量")}</th>-->
									<th width="50">{pigcms{:L_("周期购详情")}</th>
									<th width="50">{pigcms{:L_("周期购日期")}</th>
									<th width="100">{pigcms{:L_("配送提前期")}</th>
									<th width="50">{pigcms{:L_("可延期数")}</th>
									<th width="50">{pigcms{:L_("最后操作时间")}</th>
									<th width="150" class="button-column">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$list">
									<volist name="list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.name}</td>
											<td>{pigcms{$vo.price|floatval}</td>
											<!--<if condition="$vo['original_stock'] eq -1">
											<td>{pigcms{:L_("无限")}</td>
											<else />
											<td>{pigcms{$vo.original_stock}</td>
											</if>
											<td>{pigcms{$vo.stock_num_t}</td>
											<td>{pigcms{$vo.today_sell_count}</td>
											<td>{pigcms{$vo.sell_count}</td>-->
											<td>{pigcms{$vo.periodic_text}</td>
											<td>{pigcms{$vo.periodic_data_text}</td>
											<td>{pigcms{$vo.deliver_text}</td>
											<td>{pigcms{$vo.delay_limit}</td>
											<td>{pigcms{$vo.update_time}</td>
											<td class="button-column">
												<a title="{pigcms{:L_("修改")}" class="green" data-goods_id="{pigcms{$vo.id}" style="padding-right:8px;" href="{pigcms{:U('Mall_periodic_purchase/edit',array('id'=>$vo['id'],'store_id'=>$_GET['store_id'],'page'=>$_GET['page']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>
												<a title="{pigcms{:L_("删除")}" class="red check_goods" data-goods_id="{pigcms{$vo.id}" style="padding-right:8px;" href="{pigcms{:U('Mall_periodic_purchase/del',array('id'=>$vo['id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>
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
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
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
	
	$('.see_qrcode').click(function(){
		art.dialog.open($(this).attr('href'),{
			init: function(){
				var iframe = this.iframe.contentWindow;
				window.top.art.dialog.data('iframe_handle',iframe);
				
			},
			id: 'handle',
			title:'{pigcms{:L_("查看商品二维码")}',
			padding: 0,
			width: 430,
			height: 433,
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
function CreateShop(){
	window.location.href = "{pigcms{:U('add',array('store_id' => $_GET['store_id']))}";
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
<script type="text/javascript">
$(function(){
	/*{pigcms{:L_("校验商品类型增加提示")}*/
	var src="{pigcms{:U('Mall/check_goods_type')}";
	$('.check_goods').click(function(){
		var goods_id=$(this).data('goods_id');
		if(!goods_id)return true;
		var flag=true;
		$.ajax({
			type:'post',
			url:src,
			data:{goods_id:goods_id},
			async:false,
			timeout:1500,
			success:function(res){
				if(!res.status && res.is_check==1){
					layer.alert(res.info,{title:'{pigcms{:L_("提示")}'});
					flag=false;
				}
			},
			complete:function(request,status){

			}
		});
		if(!flag){
			return false;	
		}
	});
})
</script>
<include file="Public:footer"/>
