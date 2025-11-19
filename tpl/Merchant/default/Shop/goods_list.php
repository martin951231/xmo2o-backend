<include file="Public:header"/>
<!--图标样式-->
<link rel="stylesheet" type="text/css" href="https://cdn.bootcss.com/font-awesome/4.6.0/css/font-awesome.min.css">
<link rel="stylesheet" href="./tpl/Merchant/default/static/css/sort_menu.css">
<!-- <div class="main-content" style="margin-left: 281px"> -->
<!--修复样式问题-->
<if condition="empty($mer_id)">
  <style type="text/css">
      .main-content-right{
          float: right;
          width: calc(100% - 190px);
      }
  </style>
</if>
<div class="main-content <if condition="$merchant_session['store_id']">main-content-right </if>" <if condition="!$config['open_meal_v2']">style="margin-left: 281px" </if>>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{$config.shop_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li>{pigcms{$now_store['name']}</li>
			<li class="active">{pigcms{:L_("商品列表")}</li>
		</ul>
	</div>
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
				    top: 28px;
				    background: #fff;
					border: 1px solid #e5e5e5;
					z-index: 100;
					left: 480px;
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
				.header-item-2,.header-item {
				    border: 1px solid #e0e1e6;
                    padding: 4px 16px;
                    margin: 16px 5px 8px 0px;
                    cursor: pointer;
                    color: #3f4156;
                    position: relative;
                    border-radius: 3px;
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
				}
				.empty_content .item-2{
					color: #858692;
					margin-top: 14px;
				}
				.goods-left{
					width: 160px;
					margin-right: 10px;
				}
				.pagebar  a{
				    border-radius: 0!important;
				    color: #2283c5;
				    background-color: #fafafa;
				    margin: 0 -1px 0 0;
				    position: relative;
				    z-index: auto;
				    border: 1px solid #ddd;
				    padding: 5px 14px;
				}
				.pagebar a.active {
				    background-color: #6faed9;
				    border-color: #6faed9;
				    color: #fff;
				    text-shadow: 0 -1px 0 rgba(0,0,0,.25);
				    z-index: 2;
				}
				.pagebar a.fetch_page:hover {
				    background-color: #EFEFEF;
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

			<script type="text/javascript" src="./tpl/Merchant/default/static/js/sort_menu.js" ></script>
			<div class="row">
				<div class="col-xs-12">
					<!-- <div class="clearfix">
						<button class="btn btn-success" onclick="CreateShop()" style="float:left;">{pigcms{:L_("添加商品")}</button>
						<form action="{pigcms{:U('Shop/goods_list')}" method="get" style="float:left;margin-left:10px">
							<input type="hidden" name="c" value="Shop"/>
							<input type="hidden" name="a" value="goods_list"/>
							<input type="hidden" name="sort_id" value="{pigcms{$now_sort.sort_id}"/>
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
					</div> -->
					<div class="alert alert-info">
						<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>1. {pigcms{:L_("商城商品与X1商品是共用一个商品管理信息。",['X1'=>$config['shop_alias_name']])}<br/>
						2. {pigcms{:L_("商城商品不可使用分类折扣。")}<br/>
						3. {pigcms{:L_("每个商品均可以设置为仅为商城使用、仅为X1使用、商城与X1均可使用。",['X1'=>$config['shop_alias_name']])}<br/>
						4. {pigcms{:L_("设置【仅为商城使用】的商品不可在线下零售使用，其他商品可在线下零售使用。")}<br/>
					</div>

					<div id="shopList" class="grid-view">
						<div class="header-content flex" style="position: relative;">
							<div style="margin-right: 60px;" class=" flex">
                                <if condition="empty($merchant['miaomaiyun_switch'])">
								<div class="header-item" data-href="{pigcms{:U('Shop/goods_add',array('store_id' => $_GET['store_id'],'sort_id'=>$now_sort['sort_id']))}" style="color: #fff;background: #629B58;">新建商品</div>
                                </if>
<!--                                <eq name='config.miaomaiyun_erp_open' value='1'>-->
<!--                                    <div class="header-item-2"  onclick="chooseFromMiaomaiyunErp('{pigcms{$Think.get.store_id}')">淼迈云ERP商品库批量添加</div>-->
<!--                                </eq>-->
                                <div class="header-item" data-href="{pigcms{:U('Shop/goods_sort_list',array('store_id' => $_GET['store_id']))}">分类管理</div>
                                <div class="header-item" data-type="6" data-href="{pigcms{:U('Shop/batchOperation',array('store_id' => $_GET['store_id']))}">批量新增/传图/更新/导出</div>
								<div class="header-item" data-type="4" data-href="{pigcms{:U('Shop/goods_subsidiary_group',array('store_id' => $_GET['store_id']))}">附属菜分组列表</div>
								<div class="header-item" data-type="5" data-href="{pigcms{:U('Shop/shop_goods_group',array('store_id' => $_GET['store_id']))}">商品分组列表</div>

								<if condition="$now_store['youzanyun_client_id'] && $now_store['youzanyun_client_secret'] && $now_store['youzanyun_grant_id']">
								<div class="header-item sync_youzanyun" data-type="7" data-href="{pigcms{:U('Shop/synchronousGoodsByYouzanyun',array('store_id' => $_GET['store_id']))}">同步有赞云商品</div>
								</if>
							</div>
							<div style="right: 5px;position: absolute;">
								<form action="{pigcms{:U('Shop/goods_search')}" method="get" style="float:left;margin:8px 12px;">
									<input type="hidden" name="c" value="Shop"/>
									<input type="hidden" name="a" value="goods_search"/>
									<input type="hidden" name="sort_id" value="{pigcms{$now_sort.sort_id}"/>
									<input type="hidden" name="store_id" value="{pigcms{$_GET.store_id}"/>
									<!-- {pigcms{:L_("搜索类型")} -->
                                    状态：
                                    <select name="audit_status" style="    height: 36px;line-height: 36px;vertical-align: middle;font-size: 15px;">
                                        <option value="3" <if condition="$audit_status eq 3">selected="selected"</if>>全部状态</option>
                                        <option value="0" <if condition="$audit_status eq 0">selected="selected"</if>>未审核</option>
                                        <option value="1" <if condition="$audit_status eq 1">selected="selected"</if>>已审核</option>
                                        <option value="2" <if condition="$audit_status eq 2">selected="selected"</if>>审核失败</option>
                                    </select>
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
							<div class="goods-head flex search_goods">
								<a class="goods-head-item <php>if(!isset($_GET['single_no_delivery']) && !isset($_GET['status'])&&!isset($_GET['stock_num'])){</php>cur<php>}</php>" data-type="0" href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id']])}">{pigcms{:L_("全部")}{pigcms{$all_goods_total}</a>
								<a class="goods-head-item <php>if(isset($_GET['status'])&&$_GET['status']==1){</php>cur<php>}</php>" data-type="1" href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'status'=>1])}">{pigcms{:L_("售卖中")}{pigcms{$sell_goods_total}</a>
								<a class="goods-head-item <php>if(isset($_GET['status'])&&$_GET['status']==0){</php>cur<php>}</php>" data-type="2" href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'status'=>0])}">{pigcms{:L_("已下架")}{pigcms{$undercarriage_goods_total}</a>
								<a class="goods-head-item <php>if(isset($_GET['stock_num'])&&$_GET['stock_num']==0){</php>cur<php>}</php>" data-type="3" href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'stock_num'=>0])}">{pigcms{:L_("已售完")}{pigcms{$sellout_goods_total}</a>
								<a class="goods-head-item <php>if(isset($_GET['single_no_delivery'])&&$_GET['single_no_delivery']==1){</php>cur<php>}</php>" data-type="4" href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'single_no_delivery'=>1])}">{pigcms{:L_("单点不送")}{pigcms{$single_goods_total}</a>
							</div>
							<div class="goods-content flex">

								<if condition="empty($sort_list)">
									<div class="empty_content" style="width: 100%;">
										<p class="item-1">
											{pigcms{:L_("暂无商品")}
										</p>
                                        <if condition="empty($merchant['miaomaiyun_switch'])">
										<p class="item-2">{pigcms{:L_("请点击左上角的[新建商品]添加商品")}</p>
                                        </if>
									</div>
								<else/>
									<div class="goods-left" style="width: 10%;">
										<div class="s-side">
											<ul>
												<volist name="sort_list" id="vo" key="k_0">
													<if condition="$vo['operation_type'] eq 1">
														<li class="first <php>if($vo['selected']){</php>on<php>}</php>">
															<div class="d-firstNav s-firstNav clearfix <php>if($vo['child_selected']){</php>on <php>}elseif($vo['selected']){</php>self-on<php>}</php>">
																<span>{pigcms{$vo.sort_name}</span>
																<i class="fa fa-caret-right fr <php>if($vo['selected']){</php>iconRotate<php>}</php>"></i>
															</div>
															<if condition="$vo['son_list']">

																<ul class="d-firstDrop s-firstDrop <php>if($vo['child_selected']){</php>on <php>}elseif($vo['selected']){</php>self-on<php>}</php>" style="">
																	<volist name="vo['son_list']" id="son1" key="k_1">
																		<if condition="$son1['operation_type'] eq 1">
																			<li>
																				<div class="d-secondNav s-secondNav">
																			
																					<span>{pigcms{$son1.sort_name}</span>
																					<i class="fa fa-caret-right fr <php>if($son1['selected']){</php>iconRotate<php>}</php>"></i>
																				</div>
																				<if condition="$son1['son_list']">
																					<ul class="d-secondDrop s-secondDrop <php>if($son1['child_selected']){</php>on <php>}elseif($son1['selected']){</php>self-on<php>}</php>" style="">

																						<volist name="son1['son_list']" id="son2" key="k_2">
																							<li class="s-thirdItem <php>if($son2['selected']){</php>on <php>}</php>">
																								<a href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'sort_id'=>$son2['sort_id'],'status'=>$_GET['status'],'stock_num'=>$_GET['stock_num'],'single_no_delivery'=>$_GET['single_no_delivery']])}">{pigcms{$son2.sort_name}</a>
																							</li>
																						</volist>
																				   	</ul>
																			   	</if>
																			</li>
																		<else/>
																			<li class="s-secondItem <php>if($son1['child_selected']){</php>on <php>}elseif($son1['selected']){</php>self-on<php>}</php>">
																				<a href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'sort_id'=>$son1['sort_id'],'status'=>$_GET['status'],'stock_num'=>$_GET['stock_num'],'single_no_delivery'=>$_GET['single_no_delivery']])}">
																					<span>{pigcms{$son1.sort_name}</span>
																				</a>
																			</li>
																		</if>
																	</volist>
																</ul>
															</if>
														</li>
													<else/>
														<li class="s-firstItem first <php>if($vo['child_selected']){</php>on <php>}elseif($vo['selected']){</php>self-on<php>}</php>">
															<a href="{pigcms{:U('Shop/goods_list',['store_id'=>$_GET['store_id'],'sort_id'=>$vo['sort_id'],'status'=>$_GET['status'],'stock_num'=>$_GET['stock_num'],'single_no_delivery'=>$_GET['single_no_delivery']])}">
																<span>{pigcms{$vo.sort_name}</span>
															</a>
														</li>
													</if>
													
												</volist>
											</ul>
										</div>
									</div>
									<div class="goods-right" style="width: 90%;position: relative;">
										<if condition="$goods_list">
                                            <if condition="empty($merchant['miaomaiyun_switch'])">
											<div class="edit_batch_content">
												<div style="margin-right: 60px;" class="show_item_list">
													<div class="show_item " style="border: none;padding-left: 8px;">
														<label>
															<input type="checkbox" class="check_all" style="width: 14px;height: 14px;">
															全选本页
														</label>
													</div>
													<div class="show_item edit_batch" data-type="1">沽清</div>
													<div class="show_item edit_batch" data-type="2">置满</div>
													<div class="show_item edit_batch" data-type="3">修改库存</div>
													<div class="show_item edit_batch" data-type="4">上架</div>
													<div class="show_item edit_batch" data-type="5">下架</div>
													<div class="show_item edit_batch" data-type="6">删除</div>
													<div class="show_item edit_more" data-type="" style="border-right: 1px solid #e5e5e5;">
														更多<img src="{pigcms{$static_path}images/icons/jiantou.png">
														<div class="hide_item_content">
															<div class="hide_item edit_batch" data-type="7">修改分类</div>
															<div class="hide_item edit_batch" data-type="8">修改每天显示时段</div>
															<div class="hide_item edit_batch" data-type="9">修改商品副标题</div>
															<div class="hide_item edit_batch" data-type="10">修改最小购买量和单位</div>
															<div class="hide_item edit_batch" data-type="11">设置打包费</div>
															<div class="hide_item edit_batch" data-type="12">设置单点不送</div>
														</div>
													</div>
												</div>
											</div>
                                            </if>
											<table class="table table-striped table-bordered table-hover">
												<thead>
													<tr>
                                                        <if condition="empty($merchant['miaomaiyun_switch'])">
														<th><input type="checkbox" class="check_all"></th>
                                                        </if>
														<th width=""></th>
														<th width="10%">{pigcms{:L_("商品名称")}</th>
														<th>{pigcms{:L_("浏览量")}</th>
														<th>{pigcms{:L_("价格")}</th>
														<if condition="isset($config['is_open_jd_goods'])">
															<!-- <th>{pigcms{:L_("商品来源")}</th> -->
														</if>
														<th class="text-middle">{pigcms{:L_("二维码")}</th>
														<!-- <th>{pigcms{:L_("单位")}</th> -->
														<th>{pigcms{:L_("当前库存")}</th>
                                                        <th>{pigcms{:L_("虚拟销量")}</th>
														<th>{pigcms{:L_("今日销量")}</th>
														<th>{pigcms{:L_("总销量")}</th>
														<th>{pigcms{:L_("总重量")}</th>
														<!-- <th>{pigcms{:L_("商城商品")}</th> -->
														<th width="10%">{pigcms{:L_("操作时间")}</th>
														<!-- <th>{pigcms{:L_("归属打印机")}</th> -->
														<th>{pigcms{:L_("状态")}</th>
														<th>{pigcms{:L_("排序")}</th>
                                                        <th>{pigcms{:L_("审核状态")}</th>
                                                        <th>{pigcms{:L_("审核备注")}</th>
														<th class="am-text-middle">{pigcms{:L_("操作")}</th>
													</tr>
												</thead>
												<tbody>
												</tbody>
											</table>
											<div class="pagebar" style="float: right;">
											</div>
										<else/>
											<div class="empty_content">
												<p class="item-1">
													{pigcms{:L_("该分类下还没有商品")}
												</p>
                                                <if condition="empty($merchant['miaomaiyun_switch'])">
												<p class="item-2">{pigcms{:L_("请点击左上角的[新建商品]添加商品")}</p>
                                                </if>
											</div>
										</if>
									</div>
								</if>
							</div>
						</div>
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
<div id="store_sort" style="display: none;margin-top: 10px;margin-left: 10px;">
	<div class="row">
		<div class="col-xs-12">
			<div class="form-group">
				<fieldset id="choose_sort"></fieldset>
			</div>
		</div>
	</div>
</div>
<div id="store_sell_time" style="display: none;margin-top: 20px;">
	<div class="row">
		<div class="col-md-12">
			<div class="form-group">
				<label><label for="price">{pigcms{:L_("每天显示时段")}：</label></label>
                <input id="show_start_time" type="text" value="" name="show_start_time" readonly style="width:70px"/>   至
                <input id="show_end_time" type="text" value="" name="show_end_time" readonly style="width:70px"/>
	           	<br/>
	            <span class="form_tips" style="color: red;">{pigcms{:L_("不填或都填写00:00表示24小时都显示")}</span>
			</div>
		</div>
	</div>
</div>
<div id="store_describe" style="display: none;margin-top: 10px;margin-left: 15px;">
	<div class="row">
		<div>
			<textarea id="describe" placeholder='{pigcms{:L_("请输入商品副标题，建议30字以内！")}' style="width: 320px;height: 230px;"></textarea>
		</div>
	</div>
</div>
<div id="store_min_num" style="display: none;margin-top: 10px;">
	<div class="row">
		<div>
			<label class="col-md-5">{pigcms{:L_("最小购买数量")}</label>
			<input type="text" class="form-control" placeholder="{pigcms{:L_("最小购买数量")}" name="min_num" style="width: 200px;display: inline;margin-bottom: 5px;">
		</div>
		<div>
			<label class="col-md-5">{pigcms{:L_("商品单位")}</label>
			<input type="text" class="form-control" placeholder="{pigcms{:L_("请输入商品单位，（如：份）")}" name="unit" style="width: 200px;display: inline;">			
		</div>
	</div>
</div>
<div id="store_packing_charge" style="display: none;margin-top: 10px;">
	<div class="row">
		<div>
			<label class="col-md-5">{pigcms{:L_("打包费")}</label>
			<input type="text" class="form-control" placeholder="{pigcms{:L_("打包费")}" name="packing_charge" style="width: 200px;display: inline;margin-bottom: 5px;">
		</div>
	</div>
</div>

<div id="single_no_delivery_modal" style="display: none;margin-top: 10px;">
	<div class="row">
		<div style="padding-bottom: 30px;padding-top: 10px;">
			<label style="padding: 0 20px 0 20px;">{pigcms{:L_("设置单点不送")}</label>
			<select name="single_no_delivery" id="single_no_delivery">
				<option value="0">{pigcms{:L_("关闭")}</option>
				<option value="1">{pigcms{:L_("开启")}</option>
			</select>
		</div>
	</div>
</div>

<div id="img_box" style="display: none;margin-top: 10px;">
	<div class="row">
		<div>
			<img src="">
		</div>
	</div>
</div>
<script type="text/javascript">	 
var json = '{pigcms{$now_goods['json']}', sortList = '{pigcms{$sort_list_json}', selectIds = {};
var uploadJson = "{pigcms{$config.site_url}/index.php?g=Index&c=Upload&a=editor_ajax_upload&upload_dir=shop/content";
var cssPath = "{pigcms{$static_path}css/group_editor.css";
var upload_image = "{pigcms{:U('Shop/ajax_upload_pic', array('store_id' => $now_store['store_id']))}";

var page = 1;
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/goods.js?t=123"></script>
<script type="text/javascript">
$(function(){
	window.top.closeArt && window.top.closeArt();
	/*{pigcms{:L_("店铺状态")}*/
	updateStatus("body .statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");
	
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
	
	$(document).on('click', '.see_qrcode', function(){
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

	$(document).on('click', '.set_to_zero', function(){
		var goods_id = $(this).data('id');
		var stock_text = $(this).siblings('.stock_text');
		var current_stock = 0;//沽清就是清零
		layer.confirm('{pigcms{:L_("你确定要沽清该库存吗？")}',{icon:3,offset: getScrollTop()},function(index){
			$.ajax({
				url: "{pigcms{:U('Shop/reset_goods_info')}",
				type: 'post',
				dataType: 'json',
				data: {'stock_num': current_stock, 'goods_id':goods_id},
				success : function(d1){
					if(d1.status == 1){
						stock_text.text(0);
						layer.msg(d1.msg,{icon:6,time:3000,offset: getScrollTop()})
						layer.close(index);									
					}
					else{
						layer.msg(d1.msg,{icon:5,time:3000,offset: getScrollTop()})
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
		layer.confirm('{pigcms{:L_("你确定要置满该库存吗？")}',{icon:3,offset: getScrollTop()},function(index){
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
						layer.msg(d1.msg,{icon:6,time:3000,offset: getScrollTop()})
						layer.close(index);									
					}
					else{
						layer.msg(d1.msg,{icon:5,time:3000,offset: getScrollTop()})
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
						title:"{pigcms{:L_('修改')}" + '“'+d.data.name+'”'+"{pigcms{:L_('库存')}",
						content:$("#store_manage"),
						area:'300px',
						offset: getScrollTop(),
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
										layer.msg(d1.msg,{icon:6,time:2000,offset: getScrollTop()})
										layer.close(index);									
									}
									else{
										layer.msg(d1.msg,{icon:5,time:2000,offset: getScrollTop()})
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

	// 修改排序
	$(document).on('blur', '.edit_sort', function(){
		var goods_id = $(this).data('id');
		var sort = $(this).val();
		$.ajax({
			url:"{pigcms{:U('Shop/goods_sort_edit')}",
			type:"post",
			data:{"id":goods_id,sort:sort},
			dataType:"json",
			success:function(result){
				layer.msg(result.msg, {offset: getScrollTop()})
				ajax_goods_list(page)
			}
		});

	})
});

$('.header-item').click(function(){
	if($(this).data('type') == 7){
		var href = $(this).data('href');
		layer.confirm('是否同步有赞云商品？', {
		  btn: ['是', '否'] //可以无限个按钮
		  ,offset: getScrollTop()
		}, function(index, layero){

			layer.close(index);
			var indexload = layer.load(1,{offset: getScrollTop()});
			$.ajax({
				url:href,
				type:"get",
				dataType:"json",
				success:function(result){
					layer.close(indexload);
					layer.alert(result.msg, {icon: 1,offset: getScrollTop()},
						function(){
							window.location.reload()

							layer.closeAll();
						});			
				}
			});
		})
	}else{
		window.location.href = $(this).data('href');
	}
})

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
		if(_this.attr("checked")){	//"开启")}
			type = 'open';
		}else{		//"关闭")}
			type = 'close';
		}

		var is_sync = 0;
		var has_foodshop_goods= $(this).data('has_foodshop_goods');
		if (has_foodshop_goods==1) {
			layer.confirm('是否同步修改{pigcms{$config.meal_alias_name}商品？', {
			  btn: ['是', '否'] //可以无限个按钮
			  ,offset: getScrollTop()
			}, function(index, layero){
			  	is_sync=1;
			  	$.ajax({
					url:"{pigcms{:U('Shop/goods_status')}",
					type:"post",
					data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute,'is_sync':is_sync},
					dataType:"text",
					success:function(d){
						if(d != '1'){		//"失败")}
							if (type=='open') {
								_this.attr("checked",false);
							} else {
								_this.attr("checked",true);
							}
							bootbox.alert("{pigcms{:L_("操作失败")}");
						}else{
							if (type=='open') {
								layer.msg(L_('上架成功'), {offset: getScrollTop()})
							} else {
								layer.msg(L_('下架成功'), {offset: getScrollTop()})
							}
						}
						_this.attr("disabled",false);
					}
				});
				layer.closeAll();
			}, function(index, layero){
			  	$.ajax({
					url:"{pigcms{:U('Shop/goods_status')}",
					type:"post",
					data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute,'is_sync':is_sync},
					dataType:"text",
					success:function(d){
						if(d != '1' && d != 1){		//"失败")}
							if (type=='open') {
								_this.attr("checked",false);
							} else {
								_this.attr("checked",true);
							}
							bootbox.alert("{pigcms{:L_("操作失败")}");
						}else{
							if (type=='open') {
								layer.msg(L_('上架成功'), {offset: getScrollTop()})
							} else {
								layer.msg(L_('下架成功'), {offset: getScrollTop()})
							}
						}
						_this.attr("disabled",false);
					}
				});
				layer.closeAll();
			})
		}else{
			$.ajax({
				url:"{pigcms{:U('Shop/goods_status')}",
				type:"post",
				data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute,'is_sync':is_sync},
				dataType:"text",
				success:function(d){
					if(d != '1' && d != 1){		//"失败")}
						if (type=='open') {
							_this.attr("checked",false);
						} else {
							_this.attr("checked",true);
						}
						bootbox.alert("{pigcms{:L_("操作失败")}");
					}else{
						if (type=='open') {
							layer.msg(L_('上架成功'), {offset: getScrollTop()})
						} else {
							layer.msg(L_('下架成功'), {offset: getScrollTop()})
						}
					}
					_this.attr("disabled",false);
					// ajax_goods_list(page)
				}
			});
		}
	});
}
function change_status(type,id,status1,status2,attribute,is_sync){
	$.ajax({
		url:"{pigcms{:U('Shop/goods_status')}",
		type:"post",
		data:{"type":type,"id":id,"status1":status1,"status2":status2,"attribute":attribute,'is_sync':is_sync},
		dataType:"text",
		success:function(d){
		    console.log(d)
			if(d != '1' && d != 1){		//"失败")}
				if (type=='open') {
					_this.attr("checked",false);
				} else {
					_this.attr("checked",true);
				}
				bootbox.alert("{pigcms{:L_("操作失败")}");
			}else{
				if (type=='open') {
					layer.msg(L_('上架成功'), {offset: getScrollTop()})
				} else {
					layer.msg(L_('下架成功'), {offset: getScrollTop()})
				}
			}
			_this.attr("disabled",false);
		}
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
	$(document).on('click', '.check_goods', function(){
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
					layer.alert(res.info,{title:'{pigcms{:L_("提示")}',offset: getScrollTop()});
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
	$(document).on('click', '.weigh_goods_export', function(){
        var search_type =  $('select[name=search_type]').val();
        var keyword =  $('input[name=keyword]').val();
        var shop_type =  $('select[name=shop_type]').val();
        location.href = "{pigcms{:U('Shop/weigh_goods_export',array('search_type'=>'"+search_type+"','keyword'=>'"+ keyword+"','shop_type'=>'"+shop_type+"','sort_id'=>'"+sort_id+"'))}";
    })

    $('body').click(function () {
		$(this).removeClass('show1');
		$('.hide_item_content').hide();
    })

    $('.edit_more').hover(function(){
    	// $('.hide_item_content').show();
    });    

    $('.edit_more').mouseover(function(e){ 
	    var now    = $(this);
	    //调整显示位置
        var window_width = document.body.clientWidth;
        var dom_left = $(this).offset().left;
        var hide_width = $('.hide_item_content').width();
        var dom_width = $(this).width();
        var right = (window_width - dom_left - hide_width - dom_width - 20) + 'px';

        $('.hide_item_content').css({right: right, left: 'auto'}).show();
	    now.one('mouseout',function(){
			$('.hide_item_content').hide();
	    });       
	});

	$(document).on('click', '.edit_batch', function(){
    	var type = $(this).data("type");
    	var goods_id_arr = [];
    	$("input[name='goods_id[]']").each(function(){
    		if ($(this).is(':checked')) {
    			goods_id_arr.push($(this).val());
    		}
    	})
    	if (goods_id_arr.length <=0) {
			layer.alert("{pigcms{:L_("请选择商品")}", {offset: getScrollTop()});
			return false;
    	}

	    var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    var tips = "";
    	var data = {type:type};
    	data.goods_id = goods_id_arr;
    	switch(type){
    		case 1:
	    		tips = L_("你确定把已选的"+goods_id_arr.length+"个商品沽清库存吗？");
	    		var stock_num = 0;
	    		data.stock_num = stock_num;
	    		layer.confirm(tips,{icon:3,offset: getScrollTop()},function(index){
	    			ajax_fun(ajax_url,data,1);					
				});
    			break;
    		case 2:
	    		tips = L_("你确定把已选的"+goods_id_arr.length+"个商品置满库存吗？");
	    		var stock_num = 'full';
    			data.stock_num = stock_num;
	    		layer.confirm(tips,{icon:3,offset: getScrollTop()},function(index){
	    			ajax_fun(ajax_url,data,1);
					
				});	    		
    			break;
    		case 3:
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		var stock_num = 'full';
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_manage'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("修改库存")}",
					area:'300px',
					offset: getScrollTop(),
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
					  ,offset: getScrollTop()
					}, function(index, layero){
					  	data.is_sync=1;
	    				ajax_fun(ajax_url,data,1);
					}, function(index, layero){
	    				ajax_fun(ajax_url,data,1);
					})
				}else{
	    			tips = L_("你确定把已选的"+goods_id_arr.length+"个商品上架吗？");
		    		layer.confirm(tips,{icon:3,offset: getScrollTop()},function(index){
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
					  ,offset: getScrollTop()
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
	    		layer.confirm(tips,{icon:3,offset: getScrollTop()},function(index){
    				ajax_fun(ajax_url,data,1);
					
				});	
    			break;
    		case 7: //修改分类
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		var stock_num = 'full';
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_sort'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("修改分类")}",
					area:'400px',
					offset: getScrollTop(),
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var sort_id_1 = $('[name="sort_id_1"]').val();
						var sort_id_2 = $('[name="sort_id_2"]').val();
						var sort_id_3 = $('[name="sort_id_3"]').val();
		    			data.sort_id_1 = sort_id_1;
		    			data.sort_id_2 = sort_id_2;
		    			data.sort_id_3 = sort_id_3;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
    			break;
    		case 8: //每天显示时段
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_sell_time'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("修改每天显示时段")}",
					area:'350px',
					offset: getScrollTop(),
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
			    		var show_start_time = $('#show_start_time').val();
			    		var show_end_time = $('#show_end_time').val();
				    	data.show_start_time = show_start_time;
				    	data.show_end_time = show_end_time;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}
				});
    			break;
    		case 9: //商品副标题
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_describe'),
				  	title:"{pigcms{:L_("修改商品副标题")}",
					area:'350px',
					offset: getScrollTop(),
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var describe = $('#describe').val();
		    			data.describe = describe;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
    			break;
    		case 10: //最小购买量和单位
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_min_num'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("最小购买量和单位")}",
					area:'400px',
					offset: getScrollTop(),
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var min_num = $('[name="min_num"]').val();
						var unit = $('[name="unit"]').val();
		    			data.min_num = min_num;
		    			data.unit = unit;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
    			break;
    		case 11: //设置打包费
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		layer.open({
				  	type: 1, 
				  	content: $('#store_packing_charge'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("设置打包费")}",
					area:'400px',
					offset: getScrollTop(),
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var packing_charge = $('[name="packing_charge"]').val();
		    			data.packing_charge = packing_charge;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
    			break;
			case 12: //设置单品不送
	    		var ajax_url = "{pigcms{:U('Shop/reset_goods_info_batch')}";
	    		layer.open({
				  	type: 1, 
				  	content: $('#single_no_delivery_modal'), //这里content是一个普通的String
				  	title:"{pigcms{:L_("设置单品不送")}",
					area:'400px',
					offset: getScrollTop(),
					btn : ['{pigcms{:L_("确定','取消")}'],
					yes : function(index,layero){
						var single_no_delivery = $('[name="single_no_delivery"]').val();
		    			data.single_no_delivery = single_no_delivery;
	    				ajax_fun(ajax_url,data,1);
					},
					btn2 : function(index,layero){
						layer.close(index);
					}

				});
    			break;
    	}
    })

	$(document).on('click', '.check_all', function(){
		if($(this).is(':checked')){
			$(".check_all").prop("checked",true); 
			$("input[name='goods_id[]']").prop("checked",true);  
		}else{
			$(".check_all").prop("checked",false); 
			$("input[name='goods_id[]']").prop("checked",false);  	
		}	
			
	});

});

function Dcheckbox(e){
	var n=0;
	var len = $("input[name='goods_id[]']").length;
	for(var i=0;i<len;i++){
		if($("input[name='goods_id[]']")[i].checked) n++;		
	}
	if(n==len){
		$(".check_all").prop("checked",true); 
	}else{
		$(".check_all").prop("checked",false); 
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
				layer.closeAll();
				layer.msg(d1.msg,{icon:6,time:3000,offset: getScrollTop()})
				setTimeout(function(){
					if (reload) {
						$(".check_all").prop("checked",false);  
						ajax_goods_list(page)
						// window.location.reload();
					}
				},2000)								
			}
			else{
				layer.msg(d1.msg,{icon:5,time:3000,offset: getScrollTop()})
			}
		}
	});
}
function ajax_goods_list(page_num){
    var pifa_hidden = {pigcms{$pifa_hidden};
        
	$.ajax({
		url: "{pigcms{:U('ajax_goods_list',$_GET)}",
		type: 'get',
		dataType: 'json',
		data: {page:page_num},
		success : function(data){
			var html = '';
			if (data.goods_list) {
				var is_open_jd_goods = "{pigcms{$config['is_open_jd_goods']}"
				for (var i = 0; i < data.goods_list.length; i++) {
					var now_goods = data.goods_list[i];
					var now_goods_label='';
					if(now_goods.sell_type == 2){
						//称重
						now_goods_label +='<span style="color: white;background-color: #0a8ddf;font-size: 10px; padding: 1px 8px;margin-right:3px">{pigcms{:L_("称重")}</span>';
					}
					if(now_goods.single_no_delivery == 1){
						//开启单点不送
						now_goods_label +='<span style="color: white;background-color: #0a8ddf;font-size: 10px; padding: 1px 8px;margin-right:3px">{pigcms{:L_("单点不送")}</span>';
					}

					html += '<tr class="'+((i%2)==0 ?'odd' :'even')+'">';
                    <if condition="empty($merchant['miaomaiyun_switch'])">
					html += '	<th><input type="checkbox" name="goods_id[]" value="'+now_goods.goods_id+'" onclick="Dcheckbox()"></th>';
                        </if>
					html += '	<td class="img_item"><img class="goods_img" src="'+now_goods.image_url+'" style="width: 48px; margin-right: 5px; height: 48px;border-radius: 4px;"/></td>';
					html += '	<td>'+now_goods.name+(now_goods_label?'<br/>'+now_goods_label:'')+'</td>';
					html += '	<td>'+now_goods.browse_num+'</td>';
					html += '	<td>'+now_goods.price+'</td>';
					if (is_open_jd_goods) {
						// html += '		<td>'+(now_goods.jd_sku_id==0 ? '{pigcms{:L_("商家自营")}' : '<span style="color: #0a8ddf">{pigcms{:L_("京东自营")}</span>')+'</td>'
					}
					html += '	<td class="text-middle"><a href="{pigcms{:U(\'see_goods_qrcode\')}&code='+now_goods.goods_id+'" class="see_qrcode"><span class="card-icon iconfont icon-erweima"></span></a></td>';
					// html += '	<td>'+now_goods.unit+'</td>';
					if (now_goods.seckill_status) {
						html += '		<td>'+now_goods.stock_num_t+'</td>';
					}else if(now_goods.spec_value){
						html += '		<td>{pigcms{:L_("多规格")}</td>';
					}else{
						html += '	<td><span class="stock_text">'+now_goods.stock_num_t+'</span>&nbsp;&nbsp;' +
                            <if condition="empty($merchant['miaomaiyun_switch'])">
                            '<a href="#" data-id="'+now_goods.goods_id+'" class="store_button"><i class="menu-icon fa fa-edit" style="font-size: 15px;"></i></a>&nbsp;&nbsp;<a href="#" data-id="'+now_goods.goods_id+'" class="set_to_zero">{pigcms{:L_("沽清")}</a>&nbsp;&nbsp;<a href="#" data-id="'+now_goods.goods_id+'" class="set_to_full">{pigcms{:L_("置满")}</a>' +
                            </if>
                                '</td>';

					}
                    html += '	<td>'+now_goods.shop_virtual_sales+'</td>';
					html += '	<td>';
					html += '		'+now_goods.today_sell_count+'';
					if (now_goods.today_seckill_count > 0) {
						html += '({pigcms{:L_("限时优惠")}：'+now_goods.today_seckill_count+')';
					}
					html += '	</td>';
					html += '	<td>'+now_goods.sell_count+'</td>';
					if(now_goods.sell_weight){
            html += '	<td>'+now_goods.sell_weight;
            if(now_goods.weight_unit == 1){
              html += 'g</td>';
            }else{
              html += 'kg</td>';
            }
          }else{
            html += '<td>0g</td>';
          }
					// html += '	<td>'+(now_goods.shop_type==1 ? '{pigcms{:L_("是")}' : '{pigcms{:L_("不是")}')+'</td>';
					html += '	<td>'+now_goods.last_time+'</td>';
					html += '	<td>';
					html += '		<label class="statusSwitch" style="display:inline-block;">';
					html += '			<input name="switch-field-1" class="ace ace-switch ace-switch-6" type="checkbox" data-id="'+now_goods.goods_id+'"  data-has_foodshop_goods="'+now_goods.has_foodshop_goods+'"'+(now_goods.status==1 ? 'checked="checked" data-status="OPEN"' : 'data-status="CLOSED"')+'/>';
					html += '			<span class="lbl"></span>';
					html += '		</label>';
					html += '	</td>';
					html += '	<td><input type="number" class="edit_sort" value="'+now_goods.sort+'" data-id="'+now_goods.goods_id+'" style="width: 60px;"> </td>';
                    html += '	<td>'+now_goods.audit_name+'</td>';
                    html += '	<td>'+now_goods.audit_content+'</td>';
					html += '	<td class="am-text-middle">';
					html += '		<div class="tpl-table-black-operation">';
					html += '			<a href="{pigcms{:U(\'Shop/goods_edit\')}&goods_id='+now_goods.goods_id+'&store_id='+now_goods.store_id+'">{pigcms{:L_("编辑")}</a>';
                    if (now_goods.original_goods_id == '0' && {pigcms{$config['is_open_market']|intval} && data.is_fabu && data.is_publish_market_goods !=1 && pifa_hidden!=1) {
						html += '				<a class="tpl-table-black-operation-green" href="{pigcms{:U(\'Shop/goods_push\')}&goods_id='+now_goods.goods_id+'">{pigcms{:L_("发布")}</a>';

					}
                    <if condition="empty($merchant['miaomaiyun_switch'])">
					html += '			<a title="{pigcms{:L_("删除")}" class="item-delete tpl-table-black-operation-del check_goods"  data-href="{pigcms{:U(\'Shop/goods_del\')}&goods_id='+now_goods.goods_id+'">{pigcms{:L_("删除")}</a>';
                    </if>
					html += '		</div>';
					html += '	</td>';
					html += '</tr>';
				}

				$('.table tbody').html(html);
				$('.pagebar').html(data.page);
			}else{
				html += '<div class="empty_content">';
				html += '	<p class="item-1">{pigcms{:L_("该分类下还没有商品")}</p>';
				html += '	<p class="item-2">{pigcms{:L_("请点击左上角的[新建商品]添加商品")}</p>';
				html += '</div>';
				$('.goods-right').html(html)
			}
		}
	});
}

ajax_goods_list(page)

$(document).on('click', '.pagebar a.fetch_page', function(){
	page = $(this).attr('data-page-num');
	ajax_goods_list(page)
});

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
        // now.attr('title',now.data('tips'));
        tips_div.remove();
    });
});

$(document).on('click', '.pagebar a.js-page_btn', function(){
	page = $('.js-page_input').val();
	ajax_goods_list(page)
});

$(document).on('click', '.item-delete', function(){
	var href = $(this).data('href');
	layer.confirm(L_('确定要删除这条数据吗？不可恢复。'), {
	  btn: ['确定', '取消'] ,offset: getScrollTop()
	}, function(index, layero){
		$.get(href,{},function(data){
			if (data.status==1) {
				layer.msg(data.info, {offset: getScrollTop()});
				ajax_goods_list(page)
			}else{
				layer.msg(L_('删除失败'), {offset: getScrollTop()});
			}
		})

	}, function(index){
	  //按钮【按钮二】的回调
	});
});

function chooseFromMiaomaiyunErp(store_id){
    art.dialog.data('erpFun', searchErpGoods);
    art.dialog.open('?g=Merchant&c=Shop&a=miaomaiyunErpGoodsLists&is_multi=1&store_id=' + store_id,{lock:true,title:'{pigcms{:L_("商品库商品',width:800,height:550,top:40,yesText:'关闭")}',background: '#000',opacity: 0.45});
}
function searchErpGoods(val){
    if(val.length > 0){
        $.post("{pigcms{:U('Shop/insertErpGoods')}",{g_m_ids:val,store_id:'{pigcms{$Think.get.store_id}'},function (rs) {
            if(rs.error_no == 1){
                alert(rs.error_msg);
            }else{
                history.go(0);
            }
        })
    }
}
</script>
<include file="Public:footer"/>
