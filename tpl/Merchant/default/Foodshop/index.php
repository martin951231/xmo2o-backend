<include file="Public:header"/>
<div class="main-content">
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-cubes"></i>
				<a href="{pigcms{:U('Foodshop/index')}">{pigcms{$config.meal_alias_name}{pigcms{:L_("管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("店铺列表")}</li>
		</ul>
	</div>
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div id="shopList" class="grid-view" style="padding:0;">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="50">{pigcms{:L_("编号")}</th>
									<th width="50">{pigcms{:L_("排序")}</th>
									<th width="100">{pigcms{:L_("店铺名称")}</th>
									<th width="150">{pigcms{:L_("店铺电话")}</th>
									<th class="button-column" width="140">{pigcms{:L_("查看二维码")}</th>
									<th class="button-column" width="140">{pigcms{:L_("完善店铺信息")}</th>
									<th class="button-column" width="140">{pigcms{:L_("订单查看")}</th>
									<th class="button-column" width="140">{pigcms{:L_("商品管理")}</th>
<!-- 									<th class="button-column" width="140">{pigcms{:L_("店铺优惠")}</th> -->
									<th class="button-column" width="140">{pigcms{:L_("桌台管理")}</th>
									<th class="button-column" width="140">{pigcms{:L_("套餐管理")}</th>
									<th class="button-column" width="140">{pigcms{:L_("导航管理")}</th>
									<th class="button-column" width="140">{pigcms{:L_("同步商品到")}{pigcms{$config['shop_alias_name']}{pigcms{:L_("店铺")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$store_list">
									<volist name="store_list" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td><div class="tagDiv">{pigcms{$vo.store_id}</div></td>
											<td><div class="tagDiv">{pigcms{$vo.sort}</div></td>
											<td><div class="shopNameDiv">{pigcms{$vo.name}</div></td>
											<td>{pigcms{$vo.phone}</td>
											
											<if condition="empty($vo['sid'])">
											<td></td>
											<td class="button-column">
												<a style="width:80px;" class="label label-sm label-pink" title="{pigcms{:L_("完善店铺信息")}" href="{pigcms{:U('Foodshop/shop_edit',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("完善店铺信息")}</a>
											</td>
											<td></td>
											<td></td>
											<td></td>
<!-- 											<td></td> -->
											<td></td>
											<td></td>
											<td></td>
											<else />
											<td class="button-column">
												<a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=meal&id={pigcms{$vo['store_id']}" class="see_qrcode">{pigcms{:L_("查看二维码")}</a>
											</td>
											<td class="button-column">
												<a style="width:80px;" class="label label-sm label-success" title="{pigcms{:L_("修改")}" href="{pigcms{:U('Foodshop/shop_edit',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("修改店铺信息")}</a>
											</td>
											<td class="button-column">
												<a style="width:80px;" class="label label-sm label-warning" title="{pigcms{:L_("查看店铺订单")}" href="{pigcms{:U('Foodshop/order',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("查看店铺订单")}</a>
											</td>
											<td class="button-column">
												<a style="width: 60px;" class="label label-sm label-purple" title="{pigcms{:L_("商品分类")}" <if condition="$config['open_meal_electronic_menu'] && $vo['open_from_shop']">href="{pigcms{:U('Foodshop/goods_sort_new',array('store_id'=>$vo['store_id']))}"<else/>
												 href="{pigcms{:U('Foodshop/goods_list',array('store_id'=>$vo['store_id']))}"</if>>{pigcms{:L_("商品管理")}</a>
												
											</td>
											<!--td class="button-column">
												<a style="width: 60px;" class="label label-sm label-grey" title="{pigcms{:L_("店铺优惠")}" href="{pigcms{:U('Foodshop/discount',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("店铺优惠")}</a>
											</td-->
											<td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("桌台管理")}" href="{pigcms{:U('Foodshop/table', array('store_id'=>$vo['store_id']))}">{pigcms{:L_("桌台管理")}</a>
											</td>
											<td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("套餐管理")}" href="{pigcms{:U('Foodshop/package', array('store_id'=>$vo['store_id']))}">{pigcms{:L_("套餐管理")}</a>
											</td>
											<td class="button-column">
												<a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_("导航管理")}" href="{pigcms{:U('Foodshop/store_slider', array('store_id'=>$vo['store_id']))}">{pigcms{:L_("导航管理")}</a>
											</td>
											
                                            <td class="button-column">
                                                <a style="width: 60px;" class="label label-sm label-info handle_btn" title="{pigcms{:L_("同步商品")}" href="{pigcms{:U('Foodshop/shop',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("同步商品")}</a>
                                            </td>
											</if >
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="12" >{pigcms{:L_("您没有添加店铺，或店铺没开启")}{pigcms{$config.meal_alias_name}{pigcms{:L_("功能，或店铺正在审核中。")}</td></tr>
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
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("查看渠道二维码")}',
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
		$('.handle_btn').live('click',function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("克隆店铺商品至其他店铺")}',
				padding: 0,
				width: 720,
				height: 520,
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
</script>
<include file="Public:footer"/>
