<include file="Public:header"/>
<link rel="stylesheet" href="./tpl/Merchant/default/static/css/jquery.treetable.css">
<link rel="stylesheet" href="./tpl/Merchant/default/static/css/jquery.treetable.theme.default.css">
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
<div class="main-content" >
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
                <a href="{pigcms{:U('GoodsLibrary/goods_list',['store_id'=>$_GET['store_id']])}">{pigcms{:L_("商品库管理")}</a>			</li>
			</li>
			<li>{pigcms{$now_store['name']}</li>
			<if condition="$sortList">
                <volist name="sortList" id="sl">
                    <li class="active"><a href="{pigcms{:U('GoodsLibrary/goods_sort', array('fid' => $sl['fid'], 'store_id' => $now_store['store_id']))}">{pigcms{$sl['sort_name']}</a></li>
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
						<a class="btn btn-success" href="{pigcms{:U('GoodsLibrary/sort_add',array('store_id' => $_GET['store_id'], 'fid' => $fid))}" style="float:left;">{pigcms{:L_("新建分类")}</a>
					</div>
                    <div class="alert alert-info" style="margin-top: 10px">
                        <button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>1. {pigcms{:L_("商品库可为X1、X2等业务提供商品基础信息源头。",['X1'=>$config['meal_alias_name'],'X2'=>$config['mall_alias_name']])}<br/>
                        2. {pigcms{:L_("商品库中商品数据来源于X1业务下的商品数据。",['X1'=>$config['shop_alias_name']])}<br/>
                    </div>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover" id="example-advanced">
							<thead>
								<tr>
									<th><span class="operateAll" id="operateAll" data-type="1">{pigcms{:L_("全部收起")}</span></th>
									<th>{pigcms{:L_("分类名称")}</th>
									<th>{pigcms{:L_("商品数量")}</th>
									<th>{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$sort_list">
									<volist name="sort_list" id="vo" key="k_0">
										<tr <if condition="$vo['son_list']">data-tt-id="{pigcms{$k_0}"</if> class="<if condition="$i%2 eq 0">odd<else/>even</if>" >
											<td></td>
                                            <td <if condition="$vo['son_list']">style="cursor:pointer;"</if>>
                                                {pigcms{:htmlspecialchars_decode($vo['sort_name'])}
                                            </td>
                                            <td>{pigcms{$vo.goods_count}</td>
											<td>
												<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('GoodsLibrary/sort_edit',array('sort_id'=>$vo['sort_id'], 'fid' => $vo['fid']))}">
													<i class="ace-icon fa fa-pencil bigger-130"></i>
												</a>　　
												<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('GoodsLibrary/sort_del',array('sort_id'=>$vo['sort_id']))}">
													<i class="ace-icon fa fa-trash-o bigger-130"></i>
												</a>　　

												<if condition="$vo['operation_type'] neq 0">
													<a class="add_sort_son" href="{pigcms{:U('GoodsLibrary/sort_add',array('fid' => $vo['sort_id'], 'store_id' => $vo['store_id']))}">{pigcms{:L_("新增下级分类")}</a>
												</if>
											</td>
										</tr>
										<if condition="$vo['son_list']">
											<volist name="vo['son_list']" id="son1" key="k_1">
												<tr data-tt-id="{pigcms{$k_0}-{pigcms{$k_1}" data-tt-parent-id="{pigcms{$k_0}"  class="<if condition="$i%2 eq 0">odd<else/>even</if>" >
													<td></td>
													<td <if condition="$son1['son_list']">style="cursor:pointer;"</if>>
		                                                {pigcms{:htmlspecialchars_decode($son1['sort_name'])}
		                                            </td>
                                            		<td>{pigcms{$son1.goods_count}</td>
													<td>
														<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('GoodsLibrary/sort_edit',array('sort_id'=>$son1['sort_id'], 'fid' => $son1['fid']))}">
															<i class="ace-icon fa fa-pencil bigger-130"></i>
														</a>　　
														<a  style="margin-right: 38px;" title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('GoodsLibrary/sort_del',array('sort_id'=>$son1['sort_id']))}">
															<i class="ace-icon fa fa-trash-o bigger-130"></i>
														</a>

														<if condition="$son1['operation_type'] neq 0">
															<a class="add_sort_son" href="{pigcms{:U('GoodsLibrary/sort_add',array('fid' => $son1['sort_id'], 'store_id' => $son1['store_id']))}">{pigcms{:L_("新增下级分类")}</a>
														</if>
													</td>
												</tr>

												<if condition="$son1['son_list']">
													<volist name="son1['son_list']" id="son2" key="k_2">
														<tr data-tt-id="{pigcms{$k_0}-{pigcms{$k_1}-{pigcms{$k_2}" data-tt-parent-id="{pigcms{$k_0}-{pigcms{$k_1}" class="<if condition="$i%2 eq 0">odd<else/>even</if>" >
															<td></td>
															<td>
				                                                {pigcms{:htmlspecialchars_decode($son2['sort_name'])}
				                                            </td>
                                            				<td>{pigcms{$son2.goods_count}</td>
															<td>
																<a title="{pigcms{:L_("修改")}" class="green" style="padding-right:8px;" href="{pigcms{:U('GoodsLibrary/sort_edit',array('sort_id'=>$son2['sort_id'], 'fid' => $son2['fid']))}">
																	<i class="ace-icon fa fa-pencil bigger-130"></i>
																</a>　　
																<a title="{pigcms{:L_("删除")}" class="red" style="padding-right:8px;" href="{pigcms{:U('GoodsLibrary/sort_del',array('sort_id'=>$son2['sort_id']))}">
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
<script type="text/javascript">
 $("#example-advanced").treetable({ 
    expandable : true,
    clickableNodeNames : true,
    column :1,
    initialState:'expanded'
});
 $('#operateAll').on('click',function(){
 	var type = $(this).data('type');
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
});
</script>
<include file="Public:footer"/>