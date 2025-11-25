<include file="Public:header"/>
<!--<link rel="stylesheet" href="./tpl/Merchant/default/static/css/jquery.treetable.css">-->
<!--<link rel="stylesheet" href="./tpl/Merchant/default/static/css/jquery.treetable.theme.default.css">-->
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Mall/index')}">{pigcms{$config.mall_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active"><a href="{pigcms{:U('Mall/goods_list',['store_id'=>$now_store['store_id']])}">{pigcms{$now_store.name}</a></li>
			<if condition="$sortList">
                <volist name="sortList" id="sl">
                    <li class="active"><a href="{pigcms{:U('Shop/goods_sort', array('fid' => $sl['fid'], 'store_id' => $now_store['store_id']))}">{pigcms{$sl['sort_name']}</a></li>
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
						<a class="btn btn-success" href="{pigcms{:U('Mall/sort_add',array('store_id' => $_GET['store_id'], 'fid' => $fid))}" style="float:left;">{pigcms{:L_("新建分类")}</a>
					</div>
					<div class="alert alert-info" style="margin:10px;">
						<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>1. <?php echo L_("商城商品与".$config['shop_alias_name']."商品是共用一个商品管理信息。"); ?><br/>
						2. {pigcms{:L_("商城商品不可使用分类折扣。")}<br/>
						3. <?php echo L_("每个商品均可以设置为仅为商城使用、仅为".$config['shop_alias_name']."使用、商城与".$config['shop_alias_name']."均可使用。"); ?><br/>
						4. {pigcms{:L_("设置【仅为商城使用】的商品不可在线下零售使用，其他商品可在线下零售使用。")}<br/>
					</div>
					<!-- <div><span class="operateAll" id="operateAll" data-type="0">{pigcms{:L_("全部展开")}</span></div> -->
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover" id="example-advanced">
							<thead>
								<tr>
									<th><span class="operateAll" id="operateAll" data-type="1">{pigcms{:L_("全部收起")}</span></th>
									<th>{pigcms{:L_("分类名称")}</th>
									<th>{pigcms{:L_("商品数量")}</th>
									<th>{pigcms{:L_("是否是附属商品分类")}</th>
									<th>{pigcms{:L_("星期几显示")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$sort_list">
									<volist name="sort_list" id="vo" key="k_0">
										<tr data-tt-id="{pigcms{$k_0}" class="<if condition="$i%2 eq 0">odd<else/>even</if>" >
											<td></td>
                                            <td <if condition="$vo['son_list']">style="cursor:pointer;"</if>>
                                                {pigcms{:htmlspecialchars_decode($vo['sort_name'])}
                                                <eq name="vo.is_must" value="1">
                                                    <span style="background: #1686ef;color: #fff;margin-left:5px;padding:1px 3px 3px 2px;;border-radius: 4px">必点</span>
                                                </eq>
                                            </td>
                                            <td>{pigcms{$vo.goods_count}</td>
											<td>
												<label class="statusSwitch" style="display:inline-block;">
													<if condition="$vo['status'] eq 0">是<else/>否</if>
												</label>
											</td>
											<td>{pigcms{$vo.week_str}</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_edit',array('store_id'=>$_GET['store_id'],'sort_id'=>$vo['sort_id'], 'fid' => $vo['fid']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_del',array('sort_id'=>$vo['sort_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>　　

												<if condition="$vo['operation_type'] neq 0">
													<a class="add_sort_son" href="{pigcms{:U('Mall/sort_add',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("新增下级分类")}</a>
												</if>
												<if condition="empty($fid)">
												<a style="width: 60px;" class="label label-sm label-info handle_btn" href="{pigcms{:U('Mall/sort_order',array('sort_id' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("销量详情")}</a>
											    </if>
											</td>
										</tr>
										<if condition="$vo['son_list']">
											<volist name="vo['son_list']" id="son1" key="k_1">
												<tr data-tt-id="{pigcms{$k_0}-{pigcms{$k_1}" data-tt-parent-id="{pigcms{$k_0}"  class="<if condition="$i%2 eq 0">odd<else/>even</if>" >
													<td></td>
													<td <if condition="$son1['son_list']">style="cursor:pointer;"</if>>
		                                                {pigcms{:htmlspecialchars_decode($son1['sort_name'])}
		                                                <eq name="son1.is_must" value="1">
		                                                    <span style="background: #1686ef;color: #fff;margin-left:5px;padding:2px 5px 4px 5px;border-radius: 4px">必点</span>
		                                                </eq>
		                                            </td>
                                            		<td>{pigcms{$son1.goods_count}</td>
													<td>
														<label class="statusSwitch" style="display:inline-block;">
															<if condition="$son1['status'] eq 0">是<else/>否</if>
														</label>
													</td>
													<td>{pigcms{$son1.week_str}</td>
													<td>
														<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_edit',array('store_id'=>$_GET['store_id'],'sort_id'=>$son1['sort_id'], 'fid' => $son1['fid']))}">
															<i class="ace-icon fa fa-pencil bigger-130"></i>
														</a>　　
														<a  style="margin-right: 38px;" title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_del',array('sort_id'=>$son1['sort_id']))}">
															<i class="ace-icon fa fa-trash-o bigger-130"></i>
														</a>

														<if condition="$son1['operation_type'] neq 0">
															<a class="add_sort_son" href="{pigcms{:U('Mall/sort_add',array('fid' => $son1['sort_id'], 'store_id' => $son1['store_id']))}">{pigcms{:L_("新增下级分类")}</a>
														</if>
													</td>
												</tr>

												<if condition="$son1['son_list']">
													<volist name="son1['son_list']" id="son2" key="k_2">
														<tr data-tt-id="{pigcms{$k_0}-{pigcms{$k_1}-{pigcms{$k_2}" data-tt-parent-id="{pigcms{$k_0}-{pigcms{$k_1}" class="<if condition="$i%2 eq 0">odd<else/>even</if>" >
															<td></td>
															<td>
				                                                {pigcms{:htmlspecialchars_decode($son2['sort_name'])}
				                                                <eq name="son2.is_must" value="1">
				                                                    <span style="background: #1686ef;color: #fff;margin-left:5px;padding:2px 5px 4px 5px;border-radius: 4px">必点</span>
				                                                </eq>
				                                            </td>
                                            				<td>{pigcms{$son2.goods_count}</td>
															<td>
																<label class="statusSwitch" style="display:inline-block;">
																	<if condition="$son2['status'] eq 0">是<else/>否</if>
																</label>
															</td>
															<td>{pigcms{$son2.week_str}</td>
															<td>
																<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_edit',array('store_id'=>$_GET['store_id'],'sort_id'=>$son2['sort_id'], 'fid' => $son2['fid']))}">
																	<i class="ace-icon fa fa-pencil bigger-130"></i>
																</a>　　
																<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('Mall/sort_del',array('sort_id'=>$son2['sort_id']))}">
																	<i class="ace-icon fa fa-trash-o bigger-130"></i>
																</a>　
															</td>
														</tr>
													</volist>
												</if>
											</volist>
										</if>
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
<script src="./tpl/Merchant/default/static/js/jquery.treetable.js"></script>
<style type="text/css">
    .add_sort_son{
        width: 100px;
        border: 1px solid #ddd;
        padding: 3px 5px;
        border-radius: 3px;
        color: #666;
        margin-right: 10px;
    }
    .operateAll{

        background: #FF9F24;
        color: #fff;
        padding: 3px 5px;
        border-radius: 3px;
        cursor: pointer;
    }
</style>
<script type="text/javascript">
 $("#example-advanced").treetable({ 
    expandable : true,
    clickableNodeNames : true,
    column :1,
    initialState:'expanded'
});
 $('#operateAll').on('click',function(){
 	var type = $(this).data('type');
 	console.log(type, 11111)
 	if (type=='0') {
 		$('tr').each(function(e){
 			var id = $(this).attr('data-tt-id')
 			if ($('tr[data-tt-parent-id='+id+']').length>0) {
 				$(this).addClass('expanded')
 				$(this).find('.indenter').html('<a href="#" title="Collapse">&nbsp;</a>')
 			}
 			if (typeof(id)!='undefined') {
				if(id.indexOf('-')){
					if (id.split('-').length==2) {
						$(this).find('.indenter').css({'padding-left': '19px'})
					}else if (id.split('-').length==3){

						$(this).find('.indenter').css({'padding-left': '38px'})
					}

				}
			}
 		})
 		$('.collapsed').removeClass('collapsed').addClass('expanded')
 		$('tr[data-tt-parent-id]').css({'display': 'table-row'})
 		$(this).data('type','1')
 		$(this).html('{pigcms{:L_("全部收起")}')
 	}else{
 		$('tr[data-tt-parent-id]').css({'display': 'none'})
 		$('.expanded').removeClass('expanded').addClass('collapsed')
 		$(this).html('{pigcms{:L_("全部展开")}')
 		$(this).data('type','0')
 	}
 })
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