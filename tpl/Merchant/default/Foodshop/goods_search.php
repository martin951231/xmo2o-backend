<include file="Public:header"/>
<!--图标样式-->
<link rel="stylesheet" type="text/css" href="http://cdn.bootcss.com/font-awesome/4.6.0/css/font-awesome.min.css">
<link rel="stylesheet" href="./tpl/Merchant/default/static/css/sort_menu.css">
<script type="text/javascript" src="./tpl/Merchant/default/static/js/sort_menu.js" ></script>
<div class="main-content">
	
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
				.show_item,.show_item_fr{
					display: inline;
				}
				.edit_batch_content{
					margin-bottom: 15px;
					position: relative;
				}
				.hide_item_content{
					display: none;
				    position: absolute;
				    /*right: 0;*/
				    top: 97px;
				    background: #fff;
					border: 1px solid #e5e5e5;
					z-index: 100;
					left: 420px;
				}
				.col-sm-1 {
				    width: 150px;
				}
				.show_item,.show_item_fr {
					border: 1px solid #e5e5e5;
   					padding: 6px 16px;
   					margin: -2px;    
   					border-right: none;
   					cursor:pointer
				}
				.show_item_fr{
					margin-left: 400px;
					border-right: 1px solid #e5e5e5;
				}
				.hide_item{
					padding: 12px;    
					cursor:pointer
				}
				.show_item_list .show_item:last-child{
					border-right: 1px solid #e5e5e5;
				}
				.edit_batch_content img{
					width: 10px;
					margin-left: 5px;
				} 
				.cur_place{
					padding: 10px 0 20px 0;
					color:#999
				}
				.cur_place span,.cur_place a{
					padding: 0 5px;
					 
				}
				.header-content{
					border-bottom: 1px solid #eee;
				}
				.header-item {
				    border: 1px solid #c5c6cc;
				    padding: 7px 16px;
    				margin: 10px;
				    cursor: pointer;
				    color: #3f4156;
					position: relative;
				}
				.goods-head{
					border-bottom: 1px solid #eee;
    				margin-bottom: 20px;
				}
				.goods-head-item{
					margin-right: 20px;
					color: #858692;
					padding: 14px;
					cursor: pointer;
				}
				.goods-head-item.cur{
					color: #3f4156;    
					border-bottom: 1px solid #3f4156;
				}
				.goods-head a:hover {
					color: #3f4156;    
				}
				.empty_content{
					text-align: center;    
					margin-top: 200px;
					margin-bottom: 400px;
    				/*margin-top: 20%;*/
				}
				.empty_content .item-2{
					color: #858692;
					margin-top: 14px;
				}
				.search_msg{
					padding: 20px 5px 25px 0px;
				}
				.search_msg a{
					margin-left: 10px;
					color: #428bca;
				}
				.goods-left{
					width: 160px;
					margin-right: 10px;
				}
				.img_item{position: relative;}
				.tooltipdi{ position:absolute; right:0; top:0; float:left; z-index:99;max-width:300px;width: 200%;}
				.tooltipdi span{ font:12px #fff; color:#fff; float:left; background:#fff; border:1px solid #c8c8c8; padding:8px; border-radius:5px;}
				.tooltipdi span b{ display:block; position:absolute; left:-6px; top:80px; font-size:0; line-height:0; width:0; height:0; border-color:transparent; border-style:dashed; border-width:7px; border-right-color:#c8c8c8; border-right-style:solid;}
				.tooltipdi span em{ display:block; position:absolute; left:-5px; top:80px; font-size:0; line-height:0; width:0; height:0; border-color:transparent; border-style:dashed; border-width:7px; border-right-color:#fff; border-right-style:solid;}
				.item-delete{
					cursor: pointer;
				}
				.main-content-goods{
					margin-bottom: 200px;
				}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div id="shopList" class="grid-view">
						<div class="header-content flex" style="position: relative;">
							<div style="margin-right: 60px;" class=" flex">
								<a class="header-item" style="color: #fff;background: #629B58;margin:0 10px 10px 0;" href="{pigcms{:U('Foodshop/goods_list',array('store_id'=>$_GET['store_id']))}">&lt;返回商品列表</a>
							</div>
							<div style="right: 5px;position: absolute;bottom: -7px;">
								<form action="{pigcms{:U('Foodshop/goods_search')}" method="get" style="float:left;margin:16px 12px;">
									<input type="hidden" name="c" value="Foodshop"/>
									<input type="hidden" name="a" value="goods_search"/>
									<input type="hidden" name="sort_id" value="{pigcms{$now_sort.sort_id}"/>
									<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
									<!-- {pigcms{:L_("搜索类型")} -->
									<select name="search_type" style="    height: 36px;line-height: 36px;vertical-align: middle;font-size: 15px;">
										<option value="name" <eq name="_GET.search_type" value='name'>selected='selected'</eq> >{pigcms{:L_("商品名称")}</option>
										<option value="number" <eq name="_GET.search_type" value='number'>selected='selected'</eq>>{pigcms{:L_("商品条形码")}</option>
									</select>&nbsp;&nbsp;
									<input type="text" name="keyword" class="input-text" value="{pigcms{$_GET['keyword']}" placeholder='{pigcms{:L_("请输入商品名称")}' style="width: 210px;padding: 5px 5px 8px 6px;" />
									<button class="btn btn-success" >{pigcms{:L_("查询")}</button>
								</form>
							</div>
						</div>
						<div class="main-content-goods">
							<div class="goods-content flex">
								<div class="goods-left">
									<div class="s-side">
										<ul>
											<volist name="sort_list" id="vo" key="k_0">
												<li class="s-firstItem first">
													<a href="{pigcms{:U('Foodshop/goods_list',['store_id'=>$_GET['store_id'],'sort_id'=>$vo['sort_id']])}">
														<span>{pigcms{$vo.sort_name}</span>
													</a>
												</li>
											</volist>
										</ul>
									</div>
								</div>
								<div class="goods-right" style="width: 100%;position: relative;">
									<div class="edit_batch_content">
										<div class="search_msg">
											{pigcms{:L('找到与')} [ {pigcms{$_GET['keyword']} ] {pigcms{:L('相关的')} {pigcms{$count_goods} {pigcms{:L('条结果')}<a href="{pigcms{:U('Foodshop/goods_list',array('store_id'=>$_GET['store_id']))}">清空搜索结果</a>
										</div>
										<div style="margin-right: 60px;" class="show_item_list">
											<div class="show_item edit_batch" data-type="1">估清</div>
											<div class="show_item edit_batch" data-type="2">置满</div>
											<div class="show_item edit_batch" data-type="3">修改库存</div>
											<div class="show_item edit_batch" data-type="4">上架</div>
											<div class="show_item edit_batch" data-type="5">下架</div>
											<div class="show_item edit_batch" data-type="6">删除</div>
											<div class="show_item edit_more" data-type="" style="border-right: 1px solid #e5e5e5;">
												更多<img src="{pigcms{$static_path}images/icons/jiantou.png">
												<div class="hide_item_content">
													<div class="hide_item edit_batch" data-type="7">修改分类</div>
													<div class="hide_item edit_batch" data-type="8">修改单位</div>
												</div>
											</div>
										</div>
									</div>

									<if condition="$goods_list">
										<volist name="goods_list" id="tmp_goods">
											<if condition="$tmp_goods['product_list']">
												<div style="font-size: 20px;margin-bottom: 20px;margin-top: 28px;">{pigcms{$tmp_goods['sort_name']}</div>
												<table class="table table-striped table-bordered table-hover">
													<thead>
														<tr>
															<th><input type="checkbox" class="check_sort"></th>
															<th></th>
															<th width="20%">{pigcms{:L_("商品名称")}</th>
															<th>{pigcms{:L_("价格")}</th>
															<th>{pigcms{:L_("单位")}</th>
															<th>{pigcms{:L_("当前库存")}</th>
															<th>{pigcms{:L_("销量")}</th>
															<th>{pigcms{:L_("最后操作时间")}</th>
															<th>{pigcms{:L_("状态")}</th>
															<th>{pigcms{:L_("排序")}</th>
															<th class="am-text-middle">{pigcms{:L_("操作")}</th>
														</tr>
													</thead>
													<tbody>
														<volist name="tmp_goods['product_list']" id="vo">
					                                        <tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
																<th><input type="checkbox" name="goods_id[]" value="{pigcms{$vo.goods_id}" onclick="Dcheckbox(this)"></th>
					                                            <td class="img_item"><img class="goods_img" src="{pigcms{$vo.image_url}" style="width: 48px; margin-right: 5px;height: 48px;border-radius: 4px;"/></td>
					                                            <td>
																	<div>{pigcms{$vo.name}</div>
																	<if condition="$vo['number']">
																	<div>
																		<span style="font-size: 12px;color: #858585;">{pigcms{:L_("条形码")}:</span>
																		<span style="font-size: 12px;color: #428bca;">{pigcms{$vo.number}</span>
																	</div>
																	</if>
					                                            </td>
																<td>{pigcms{$vo.price|floatval}</td>
																<td>{pigcms{$vo.unit}</td>
																<if condition="!empty($vo['spec_value'])">
																	<td>{pigcms{:L_("多规格")}</td>
																<else />
																<td><span class="stock_text">{pigcms{$vo.stock_num}</span>&nbsp;&nbsp;<a href="#" data-id="{pigcms{$vo.goods_id}" class="store_button"><i class="menu-icon fa fa-edit" style="font-size: 15px;"></i></a>&nbsp;&nbsp;<a href="#" data-id="{pigcms{$vo.goods_id}" class="set_to_zero">{pigcms{:L_("估清")}</a>&nbsp;&nbsp;<a href="#" data-id="{pigcms{$vo.goods_id}" class="set_to_full">{pigcms{:L_("置满")}</a></td>
																</if>
																<td>{pigcms{$vo.sell_count}</td>
																<td>{pigcms{$vo.last_time|date='Y-m-d H:i:s',###}</td>
																<td>
																	<label class="statusSwitch" style="display:inline-block;">
																		<input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="{pigcms{$vo.goods_id}" <if condition="$vo['status'] eq 1">checked="checked" data-status="OPEN"<else/>data-status="CLOSED"</if>/>
																		<span class="lbl"></span>
																	</label>
																</td>
																<td><input type="number" class="edit_sort" value="{pigcms{$vo.sort}" data-id="{pigcms{$vo.goods_id}" style="width: 60px;"> </td>
																<td class="am-text-middle">
																	<div class="tpl-table-black-operation">
																		<a href="{pigcms{:U('Foodshop/goods_edit',array('goods_id'=>$vo['goods_id'], 'store_id'=>$vo['store_id']))}">{pigcms{:L_("编辑")}</a>
																		<a title="{pigcms{:L_("删除")}" class="item-delete tpl-table-black-operation-del check_goods" href="{pigcms{:U('Foodshop/goods_del',array('goods_id'=>$vo['goods_id']))}">{pigcms{:L_("删除")}</a>
																	</div>
																</td>
															</tr>
														</volist>
													</tbody>
												</table>
											</if>
										</volist>
									<else/>
										<div class="empty_content">
											<p class="item-1">{pigcms{:L_("未查询到相关商品，换个词试试")}</p>
										</div>
									</if>
								</div>
							</div>
						</div>
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
<div id="store_describe" style="display: none;margin-top: 10px;margin-left: 15px;">
	<div class="row">
		<div>
			<textarea id="describe" placeholder='{pigcms{:L_("请输入商品副标题，建议30字以内！")}' style="width: 320px;height: 230px;"></textarea>
		</div>
	</div>
</div>
<div id="store_min_num" style="display: none;margin-top: 30px;">
	<div class="row">
		<div>
			<label class="col-md-3" style="line-height: 30px;margin-left: 40px;">{pigcms{:L_("商品单位")}</label>
			<input type="text" class="form-control" placeholder="{pigcms{:L_("请输入商品单位，（如：份）")}" name="unit" style="width: 200px;display: inline;">			
		</div>
	</div>
</div>

<div id="store_sort" style="display: none;margin-top: 30px;margin-left: 10px;text-align: center;">
	<div class="row" style="text-align: center;">
		<div class="col-xs-12">
			<div class="form-group">
				<label class="col-md-3" style="line-height: 20px;margin-left: 40px;">{pigcms{:L_("商品分类")}</label>
				<select name="sort_id_select" class="col-sm-1" id="sort_id">
					<volist name="sort_list" id="sort">
						<option value="{pigcms{$sort.sort_id}">{pigcms{$sort.sort_name}</option>
					</volist>
				</select>
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">	
var ajax_url = "{pigcms{:U('Foodshop/reset_goods_info_batch')}";
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
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
	
	//"估清")}
	$(document).on('click', '.set_to_zero', function(){
		var goods_id = $(this).data('id');
		var stock_text = $(this).siblings('.stock_text');
		var current_stock = 0;//"估清就是清零")}
		layer.confirm('{pigcms{:L_("你确定要估清该库存吗？")}',{icon:3},function(index){
			$.ajax({
				url: ajax_url,
				type: 'post',
				dataType: 'json',
				data: {'type': 1, 'goods_id':[goods_id]},
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
	$(document).on('click', '.set_to_full', function(){
		var goods_id = $(this).data('id');
		var stock_text = $(this).siblings('.stock_text');
		var current_stock = 'full';//"置满")}
		layer.confirm('{pigcms{:L_("你确定要置满该库存吗？")}',{icon:3},function(index){
			$.ajax({
				url: ajax_url,
				type: 'post',
				dataType: 'json',
				data: {'type': 2, 'goods_id':[goods_id]},
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
	$(document).on('click', '.store_button', function(){
		var goods_id = $(this).data('id');
		var _this = this;
		var stock_text = $(this).siblings('.stock_text');
		$.ajax({
			url:"{pigcms{:U('Foodshop/get_goods_info')}",
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
								url: ajax_url,
								type: 'post',
								dataType: 'json',
								data: {type:3,'stock_num': current_stock,'original_stock' : all_stock, 'goods_id':[current_goods_id]},
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


function updateStatus(dom1, dom2, status1, status2, attribute){
	$(document).on('each',dom1,function(){
		if($(this).attr("data-status")==status1){
			$(this).attr("checked",true);
		}else{
			$(this).attr("checked",false);
		}
		$(dom2).show();
	});

	$(document).on('click',dom1,function(){
		var _this = $(this),
		 	type = 'open',
		 	id = $(this).attr("data-id");
		_this.attr("disabled",true);
		if(_this.attr("checked")){	
			type = 'open';
		}else{		
			type = 'close';
		}
		$.ajax({
			url:"{pigcms{:U('Foodshop/goods_status')}",
			type:"post",
			data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute},
			dataType:"text",
			success:function(d){
				if(d != '1'){		
					if (type=='open') {
						_this.attr("checked",false);
					} else {
						_this.attr("checked",true);
					}
				}else{
					if (type=='open') {
						layer.msg(L_('上架成功'))
					} else {
						layer.msg(L_('下架成功'))
					}
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
    $('body').click(function () {
		$(this).removeClass('show1');
		$('.hide_item_content').hide();
    })

    $('.edit_more').mouseover(function(e){ 
	    var now    = $(this);
    	$('.hide_item_content').show();
	    now.one('mouseout',function(){
			$('.hide_item_content').hide();
	    });       
	});

    $('.edit_batch').click(function () {
    	var type = $(this).data("type");
    	var goods_id_arr = [];
    	$("input[name='goods_id[]']").each(function(){
    		if ($(this).is(':checked')) {
    			goods_id_arr.push($(this).val());
    		}
    	})
    	if (goods_id_arr.length <=0) {
			layer.alert("{pigcms{:L_("请选择商品")}");
			return false;
    	}

	    var tips = "";
    	var data = {type:type};
    	data.goods_id = goods_id_arr;
		switch(type){
			case 1:
	    		tips = L_("你确定把已选的"+goods_id_arr.length+"个商品估清库存吗？");
	    		var stock_num = 0;
	    		data.stock_num = stock_num;
	    		layer.confirm(tips,{icon:3},function(index){
	    			ajax_fun(ajax_url,data,1);					
				});
				break;
			case 2:
	    		tips = L_("你确定把已选的"+goods_id_arr.length+"个商品置满库存吗？");
	    		var stock_num = 'full';
				data.stock_num = stock_num;
	    		layer.confirm(tips,{icon:3},function(index){
	    			ajax_fun(ajax_url,data,1);
					
				});	    		
				break;
			case 3:
	    		var stock_num = 'full';
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_manage'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("修改库存")}",
					area:'300px',
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var stock_num = $('[name="current_stock"]').val();
						var original_stock = $('[name="all_stock"]').val();
		    			data.stock_num = stock_num;
		    			data.original_stock = original_stock;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
				break;
			case 4: //上架
	    		var is_sync = 0;
				var open_from_shop= {pigcms{$open_from_shop|intval};
				var open_meal_electronic_menu= {pigcms{$config.open_meal_electronic_menu|intval};
				if (open_from_shop==1&&open_meal_electronic_menu==1) {
					layer.confirm('是否同步上架{pigcms{$config.meal_alias_name}商品？', {
					  btn: ['是', '否'] //可以无限个按钮
					}, function(index, layero){
					  	data.is_sync=1;
	    				ajax_fun(ajax_url,data,1);
					}, function(index, layero){
	    				ajax_fun(ajax_url,data,1);
					})
				}else{
	    			tips = L_("你确定把已选的"+goods_id_arr.length+"个商品上架吗？");
		    		layer.confirm(tips,{icon:3},function(index){
	    				ajax_fun(ajax_url,data,1);
						
					});	
				}
				break;
			case 5: //下架
	    		var is_sync = 0;
				var open_from_shop= {pigcms{$open_from_shop|intval};
				var open_meal_electronic_menu= {pigcms{$config.open_meal_electronic_menu|intval};
				if (open_from_shop==1&&open_meal_electronic_menu==1) {
					layer.confirm('是否同步修改下架{pigcms{$config.meal_alias_name}商品？', {
					  btn: ['是', '否'] //可以无限个按钮
					}, function(index, layero){
					  	data.is_sync=1;
	    				ajax_fun(ajax_url,data,1);
					}, function(index, layero){
	    				ajax_fun(ajax_url,data,1);
					})
				}else{
	    			tips = L_("你确定把已选的"+goods_id_arr.length+"个商品下架吗？");
		    		layer.confirm(tips,{icon:3},function(index){
	    				ajax_fun(ajax_url,data,1);
						
					});	
				}
				break;
			case 6: //删除
				tips = L_("你确定把已选的"+goods_id_arr.length+"个商品删除吗？");
	    		layer.confirm(tips,{icon:3},function(index){
					ajax_fun(ajax_url,data,1);
					
				});	
				break;
			case 7: //修改分类
	    		var stock_num = 'full';
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_sort'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("修改分类")}",
					area:['400px','200px'],
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var sort_id = $('select[name="sort_id_select"]').val();
		    			data.sort_id = sort_id;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
				break;
			case 8: //单位
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_min_num'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("单位")}",
					area:['400px','200px'],
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var unit = $('[name="unit"]').val();
		    			data.unit = unit;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
				break;
		}
    })

    $(".check_all").on('click',function(){	
		if($(this).is(':checked')){
			$("input[name='goods_id[]']").prop("checked",true);  
		}else{
			$("input[name='goods_id[]']").prop("checked",false);  	
		}	
			
	});

    $(".check_sort").on('click',function(){
		if($(this).is(':checked')){

			$(this).parents('table').find("input[name='goods_id[]']").prop("checked",true);  
		}else{
			$(this).parents('table').find("input[name='goods_id[]']").prop("checked",false);  	
		}	
			
	});

});

function Dcheckbox(e){
	var n=0;
	var len = $(e).parents('table').find("input[name='goods_id[]']").length;
	for(var i=0;i<len;i++){
		if($(e).parents('table').find("input[name='goods_id[]']")[i].checked) n++;		
	}
	if(n==len){
		$(e).parents('table').find(".check_sort").prop("checked",true); 
	}else{
		$(e).parents('table').find(".check_sort").prop("checked",false); 
	}
		
}

function ajax_fun(url,data,reload){
	$.ajax({
		url: url,
		type: 'post',
		dataType: 'json',
		data: data,
		success : function(d1){
			if(d1.status == 1){
				layer.msg(d1.msg,{icon:6,time:3000})
				layer.closeAll();
				setTimeout(function(){
					if (reload) {
						window.location.reload();
					}
				},2000)								
			}
			else{
				layer.msg(d1.msg,{icon:5,time:3000})
			}
		}
	});
}
$(document).on('mouseover', '.img_item img.goods_img', function(){
	var now    = $(this);
    var offset = $(this).position();
    var parent = $(this).closest('td');
    // now.data('src',now.attr('src')).attr('src','');
   
    var info =  now.data('info');
    console.log(info)
    var info_str =  '';
   
    info_str += '<div><img style="width:130px;" src="'+now.attr('src')+'"/></div>';
        
    parent.append('<div class="tooltipdi" style="position:absolute;padding: 8px; left:'+(offset.left+49)+'px;top:'+(offset.top-63)+'px"><span><b></b><em></em>'+info_str+'</span></div>');
    var tips_div = parent.find('.tooltipdi');
    parent.one('mouseout',function(){
        tips_div.remove();
    });
});
</script>
<include file="Public:footer"/>
