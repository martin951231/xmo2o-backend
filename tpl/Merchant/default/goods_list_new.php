<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Shop/goods_sort',array('store_id'=>$now_store['store_id']))}">{pigcms{:L_("分类列表")}</a></li>
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
						<button class="btn btn-success" onclick="CreateShop()" style="float:left;">{pigcms{:L_("添加商品")}</button>
						<form action="{pigcms{:U('Shop/goods_list')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Shop"/>
							<input type="hidden" name="a" value="goods_list"/>
							<input type="hidden" name="sort_id" value="{pigcms{$now_sort.sort_id}"/>
							<!-- {pigcms{:L_("搜索类型")} -->
							<select name="search_type">
								<option value="name" <eq name="_GET.search_type" value='name'>selected='selected'</eq> >{pigcms{:L_("商品名称")}</option>
								<option value="number" <eq name="_GET.search_type" value='number'>selected='selected'</eq>>{pigcms{:L_("商品条形码")}</option>
							</select>&nbsp;&nbsp;
							<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入商品名称")}'/>
							{pigcms{:L_("商品类型：")}
							<select name='shop_type'>
								<option value="0">{pigcms{:L_("全部")}</option>	
								<option value="1" <eq name="_GET['shop_type']" value='1'>selected='selected'</eq> >{pigcms{$config.shop_alias_name}</option>
								<option value="2" <eq name="_GET['shop_type']" value='2'>selected='selected'</eq>>{pigcms{$config.mall_alias_name}</option>	
							</select>
                            <if condition="$config.is_open_shop_weigh eq 1">
                                {pigcms{:L_("计价方式：")}
                                <select name='sell_type' id="sell_type">
                                    <option value="0">{pigcms{:L_("全部")}</option>
                                    <option value="1" <eq name="_GET['sell_type']" value='1'>selected='selected'</eq> >{pigcms{:L_("计件")}</option>
                                    <option value="2" <eq name="_GET['sell_type']" value='2'>selected='selected'</eq>>{pigcms{:L_("计重")}</option>
                                </select>
                            </if>
							<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
							<input class="btn export" type="button" id="weigh_goods_export" style="margin-left: 20px;display: none;" value="{pigcms{:L_('导出')}">
						</form>
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
									<th>{pigcms{:L_("排序")}</th>
									<th>{pigcms{:L_("商品名称")}</th>
									<th>{pigcms{:L_("价格")}</th>
									<if condition="isset($config['is_open_jd_goods'])">
										<th>{pigcms{:L_("商品来源")}</th>
									</if>
									<th class="text-middle">{pigcms{:L_("二维码")}</th>
									<th>{pigcms{:L_("单位")}</th>
									<th>{pigcms{:L_("当前库存")}</th>
									<th>{pigcms{:L_("今日销量")}</th>
									<th>{pigcms{:L_("总销量")}</th>
									<th>{pigcms{:L_("是否同时是商城商品")}</th>
									<th>{pigcms{:L_("最后操作时间")}</th>
									<th>{pigcms{:L_("归属打印机")}</th>
									<th>{pigcms{:L_("状态")}</th>
									<th class="am-text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$goods_list">
									<volist name="goods_list" id="vo">
                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$vo.sort}</td>
                                            <td>{pigcms{$vo.name}<if condition="$vo.sell_type eq 2"><br><span style="color: white;background-color: #0a8ddf;font-size: 10px; padding: 1px 8px;">{pigcms{:L_("称重")}</span></if></td>
											<td>{pigcms{$vo.price|floatval}</td>
											<if condition="isset($config['is_open_jd_goods'])">
												<td><if condition="$vo.jd_sku_id eq 0">{pigcms{:L_("商家自营")}<else/><span style="color: #0a8ddf">{pigcms{:L_("京东自营")}</span></if></td>
											</if>
											<td class="text-middle"><a href="{pigcms{:U('see_goods_qrcode',array('code'=>$vo['goods_id']))}" class="see_qrcode"><span class="card-icon iconfont icon-erweima"></span></a></td>
											<td>{pigcms{$vo.unit}</td>
											<if condition="$vo['seckill_status']">
												<td>{pigcms{$vo.stock_num_t}</td>
											<elseif condition="!empty($vo['spec_value'])"/>
												<td>{pigcms{:L_("多规格")}</td>
											<else />
											<td><span class="stock_text">{pigcms{$vo.stock_num_t}</span>&nbsp;&nbsp;<a href="#" data-id="{pigcms{$vo.goods_id}" class="store_button"><i class="menu-icon fa fa-edit" style="font-size: 15px;"></i></a>&nbsp;&nbsp;<a href="#" data-id="{pigcms{$vo.goods_id}" class="set_to_zero">{pigcms{:L_("估清")}</a>&nbsp;&nbsp;<a href="#" data-id="{pigcms{$vo.goods_id}" class="set_to_full">{pigcms{:L_("置满")}</a></td>
											</if>
											<td>{pigcms{$vo.today_sell_count}</td>
											<td>{pigcms{$vo.sell_count}</td>
											<td><eq name="vo.shop_type" value='1'>{pigcms{:L_("是")}<else/>{pigcms{:L_("不是")}</eq></td>
											<td>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
											<td>{pigcms{$vo.print_name}</td>
											<td>
												<label class="statusSwitch" style="display:inline-block;">
													<input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="{pigcms{$vo.goods_id}" <if condition="$vo['status'] eq 1">checked="checked" data-status="OPEN"<else/>data-status="CLOSED"</if>/>
													<span class="lbl"></span>
												</label>
											</td>
											<td class="am-text-middle">
												<div class="tpl-table-black-operation">
													<a href="{pigcms{:U('Shop/goods_edit',array('goods_id'=>$vo['goods_id'], 'store_id'=>$vo['store_id']))}">{pigcms{:L_("编辑")}</a>
													<a title="{pigcms{:L_("删除")}" class="item-delete tpl-table-black-operation-del check_goods" href="{pigcms{:U('Shop/goods_del',array('goods_id'=>$vo['goods_id']))}">{pigcms{:L_("删除")}</a>
                                                    <if condition="empty($vo['original_goods_id']) && $config['is_open_market'] && $merchant_menu[110]['menu_list'][10008] && $is_publish_market_goods neq 1">
                                                        <a class="tpl-table-black-operation-green" href="{pigcms{:U('Shop/goods_push',array('goods_id'=>$vo['goods_id']))}">{pigcms{:L_("发布")}</a>
                                                    </if>
												</div>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="15" >{pigcms{:L_("无内容")}</td></tr>
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
<div id="store_manage" style="display: none;">
	<div class="row">
		<div class="col-md-6">
			<div class="form-group">
				<label>{pigcms{:L_("当前库存")}</label>
				<input type="text" class="form-control" placeholder="{pigcms{:L_("当前库存")}" name="current_stock">
			</div>
		</div>
		<div class="col-md-6">
			<div class="form-group">
				<label>{pigcms{:L_("原始库存")}</label>
				<input type="text" class="form-control" placeholder="{pigcms{:L_("原始库存")}" name="all_stock">
			</div>
		</div>
		<input type="hidden" name="current_goods_id" value="" />
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

	//"估清")}
	$(".set_to_zero").bind("click",function(){
		var goods_id = $(this).data('id');
		var stock_text = $(this).siblings('.stock_text');
		var current_stock = 0;//"估清就是清零")}
		layer.confirm('{pigcms{:L_("你确定要估清该库存吗？")}',{icon:3},function(index){
			$.ajax({
				url: "{pigcms{:U('Shop/reset_goods_info')}",
				type: 'post',
				dataType: 'json',
				data: {'stock_num': current_stock, 'goods_id':goods_id},
				success : function(d1){
					if(d1.status == 1){
						stock_text.text(0);
						layer.msg(d1.msg,{icon:6,time:3000})
						layer.close(index);									
					}
					else{
						layer.msg(d1.msg,{icon:5,time:3000})
					}
				}
			});
		});
		return false;
	})

	//"置满")}
	$(".set_to_full").bind("click",function(){
		var goods_id = $(this).data('id');
		var stock_text = $(this).siblings('.stock_text');
		var current_stock = 'full';//"置满")}
		layer.confirm('{pigcms{:L_("你确定要置满该库存吗？")}',{icon:3},function(index){
			$.ajax({
				url: "{pigcms{:U('Shop/reset_goods_info')}",
				type: 'post',
				dataType: 'json',
				data: {'stock_num': current_stock, 'goods_id':goods_id},
				success : function(d1){
					if(d1.status == 1){
						if(d1.data.update == -1){
							stock_text.text('{pigcms{:L_("无限")}');
						}
						else{
							stock_text.text(d1.data.update);
						}	
						layer.msg(d1.msg,{icon:6,time:3000})
						layer.close(index);									
					}
					else{
						layer.msg(d1.msg,{icon:5,time:3000})
					}
				}
			});
		});
		return false;
	})

	//"修改库存")}
	$(".store_button").bind("click",function(){
		var goods_id = $(this).data('id');
		var _this = this;
		var stock_text = $(this).siblings('.stock_text');
		$.ajax({
			url:"{pigcms{:U('Shop/get_goods_info')}",
			type:"post",
			data:{"goods_id":goods_id},
			dataType:"json",
			success:function(d){
				if(d.status == 1){
					$('[name="current_stock"]').val(d.data.stock_num);
					$('[name="all_stock"]').val(d.data.original_stock);
					$('[name="current_goods_id"]').val(goods_id);
					layer.open({
						type:1,
						title:"{pigcms{:L_("修改“")}"+d.data.name+"{pigcms{:L_("”库存")}",
						content:$("#store_manage"),
						area:'300px',
						btn : ['{pigcms{:L_("确定','取消")}'],
						yes : function(index,layero){
							var current_stock = $('[name="current_stock"]').val();
							var all_stock = $('[name="all_stock"]').val();
							var current_goods_id = $('[name="current_goods_id"]').val();
							$.ajax({
								url: "{pigcms{:U('Shop/reset_goods_info')}",
								type: 'post',
								dataType: 'json',
								data: {'stock_num': current_stock,'original_stock' : all_stock, 'goods_id':current_goods_id},
								success : function(d1){
									if(d1.status == 1){
										if(current_stock == -1){
											stock_text.text('{pigcms{:L_("无限")}');
										}
										else{
											stock_text.text(current_stock);
										}	
										layer.msg(d1.msg,{icon:6,time:2000})
										layer.close(index);									
									}
									else{
										layer.msg(d1.msg,{icon:5,time:2000})
									}
								}
							});							
						},
						btn2 : function(index,layero){
							layer.close(index);
						}
					});
				}
				else{
					layer.tips('{pigcms{:L_("存在未知错误")}!', _this,{time:1000});
				}
			}
		});
		return false;
	})
});

function CreateShop(){
	window.location.href = "{pigcms{:U('Shop/goods_add',array('sort_id' => $now_sort['sort_id']))}";
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
			url:"{pigcms{:U('Shop/goods_status')}",
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
var store_id = "{pigcms{$now_store['store_id']}";
var sort_id = "{pigcms{$now_sort['sort_id']}";
var exports_url = "{pigcms{:U('Shop/weigh_goods_export')}";
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
	if( $('#sell_type').val()==2){
        $('.export').show();
    }
    $('#sell_type').change(function(){
        if ($(this).val()==2) {
            $('.export').show();
        } else {
            $('.export').hide();
        }
    });
    $('#weigh_goods_export').click(function () {
        var search_type =  $('select[name=search_type]').val();
        var keyword =  $('input[name=keyword]').val();
        var shop_type =  $('select[name=shop_type]').val();
        location.href = "{pigcms{:U('Shop/weigh_goods_export',array('search_type'=>'"+search_type+"','keyword'=>'"+ keyword+"','shop_type'=>'"+shop_type+"','sort_id'=>'"+sort_id+"'))}";
    })
});
</script>
<include file="Public:footer"/>
