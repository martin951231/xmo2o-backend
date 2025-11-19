<include file="Public:header" />
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/store')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("店铺列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {
					display: none;
				}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<if condition="empty($merchant_session['store_id'])">
						<button class="btn btn-success" onclick="CreateShop()">{pigcms{:L_("新建店铺")}</button>
					</if>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th>{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("排序")}</th>
									<th>{pigcms{:L_("店铺名称")}</th>
									<if condition="($config['open_share_goods']) and (empty($merchant_session['store_id']))">
										<th>{pigcms{:L_("共享店铺")}</th>
									</if>
									<th class="text-middle">{pigcms{:L_("店铺基本信息")}</th>
									<eq name='config.open_yongyou_erp' value='1'>
										<th class="text-middle">{pigcms{:L_("用友Erp对接地址")}</th>
									</eq>
									<th class="text-middle">{pigcms{:L_("店铺状态")}</th>
									<th class="text-middle" style="display:<if condition=" $config['single_system_type'] neq 'village_group'">table-cell<else/>none</if>;">{pigcms{$config.shop_alias_name}{pigcms{:L_("资质审核状态")} <if condition="$config['store_shop_auth'] eq 0"><b style="color:red">({pigcms{:L_("平台未开启")})</b></if>
									</th>
									<th class="text-middle" style="display:<if condition=" $config['single_system_type'] neq 'village_group'">table-cell<else/>none</if>;">{pigcms{:L_("微信二维码")}</th>
									<if condition="$config['store_open_wxapp_qrcode']">
										<th class="text-middle">{pigcms{:L_("小程序二维码")}</th>
									</if>
									<if condition="$config['mall_alias_name']">
										<th>{pigcms{:L_("自定义页面")}</th>
									</if>
									<eq name='config.miaomaiyun_erp_open' value='1'>
										<th class="text-middle">{pigcms{:L_("淼迈云ERP")}</th>
									</eq>
									<if condition="empty($merchant_session['store_id'])">
										<th>{pigcms{:L_("商品库")}</th>
									</if>
									<th style="display:<if condition=" !$config['single_system'] || $config['single_system_type']=='shop'">table-cell<else/>none</if>;">{pigcms{:L_("店员管理")}</th>
									<th style="display:table-cell">{pigcms{:L_("导航管理")}</th>
									<if condition="$config['pay_in_store']">
										<th class="text-middle">{pigcms{:L_("线下支付方式")}</th>
									</if>
									<th class="button-column" width="140">{pigcms{:L_("店铺优惠")}</th>
									<th class="text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$store_list">
									<volist name="store_list" id="vo">
										<tr class="<if condition=" $i%2 eq 0">odd
											<else />even
								</if>">
								<td>{pigcms{$vo.store_id}</td>
								<td>{pigcms{$vo.sort}</td>
								<td>{pigcms{$vo.name}</td>
								<if condition="($config['open_share_goods']) and (empty($merchant_session['store_id']))">
									<td>


										<label  class="is-share-switch" data-id="{pigcms{$vo.store_id}" style="display:inline-block;">
											<input name="switch-field-1" class="ace ace-switch ace-switch-6"  type="checkbox" data-id=""<if condition="$vo.is_share == 1 "> checked="checked" </if> />
											<span class="lbl"></span>
										</label>

									</td>
								</if>
								<td class="text-middle"><a href="javascript:;" class="store_info_more" data-store_name="{pigcms{$vo.name}" data-phone="{pigcms{$vo.phone|phone_demo_show}" data-address="{pigcms{$vo.area_name} - {pigcms{$vo.adress}" data-hasmeal="<if condition=" $vo['have_meal']">有
										<else />无</if>" data-hasgroup="<if condition="$vo['have_group']">有
											<else />无
										</if>" data-hasshop="<if condition="$vo['have_shop']">有
											<else />无
										</if>" data-hasmall="<if condition="$vo['have_mall']">有
											<else />无
										</if>" data-store_qrcode_url="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=merchantstore&id={pigcms{$vo['store_id']}&img=1" data-store_status="{pigcms{$vo['status']}">{pigcms{:L_("查看")}
									</a></td>
								<eq name='config.open_yongyou_erp' value='1'>
									<td class="text-middle">
										<a href='javascript:;' class='erp_btn' data-mer_id='{pigcms{$vo.mer_id}' data-store_id='{pigcms{$vo.store_id}'>{pigcms{:L_("详情")}</a>
									</td>
								</eq>
								<td class="text-middle">
									<switch name="vo['status']">
										<case value="0">{pigcms{:L_("关闭")}</case>
										<case value="1">{pigcms{:L_("正常")}</case>
										<case value="2">{pigcms{:L_("审核中")}</case>
									</switch>
								</td>
								<td class="text-middle" style="display:<if condition=" $config['single_system_type'] neq 'village_group'">table-cell<else/>none</if>;">
									<switch name="vo['auth']">
										<case value="0"><b style="color:#723f23">{pigcms{:L_("未提交")} </b>　<a href="{pigcms{:U('Config/auth_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("提交")}</a></case>
										<case value="1"><b style="color:blue">{pigcms{:L_("审核中")}</b>　<a href="{pigcms{:U('Config/auth_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("修改")}</a></case>
										<case value="2"><b style="color:red">{pigcms{:L_("已拒绝")}</b>　<a href="{pigcms{:U('Config/auth_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("修改")}</a></case>
										<case value="3"><b style="color:green">{pigcms{:L_("已通过")}</b>　<a href="{pigcms{:U('Config/auth_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("修改")}</a></case>
										<case value="4"><b style="color:blue">{pigcms{:L_("审核中")}</b>　<a href="{pigcms{:U('Config/auth_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("修改")}</a></case>
										<case value="5"><b style="color:red">{pigcms{:L_("已驳回")}</b>　<a href="{pigcms{:U('Config/auth_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("修改")}</a></case>
									</switch>
								</td>
								<if condition="$vo['status'] neq 2">
									<td class="text-middle" style="display:<if condition=" $config['single_system_type'] neq 'village_group'">table-cell<else/>none</if>;">
										<a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition&a=see_qrcode&type=merchantstore&id={pigcms{$vo['store_id']}&img=1" class="see_qrcode"><span class="card-icon iconfont icon-erweima"></span></a>
									</td>
									<if condition="$config['store_open_wxapp_qrcode']">
										<td class="text-middle">
											<a href="{pigcms{$config.site_url}/index.php?g=Index&c=Recognition_wxapp&a=see_qrcode&type=merchantstore&id={pigcms{$vo['store_id']}&img=1" class="see_qrcode_wxapp"><span class="card-icon iconfont icon-xiaochengxu color-green"></span></a>
										</td>
									</if>
									<if condition="$config['mall_alias_name']">
										<td>
											<a href="{pigcms{:U('Diypage/index',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("自定义页面")}</a>
										</td>
									</if>

									<eq name='config.miaomaiyun_erp_open' value='1'>
										<td>
											<a class="label label-sm label-info" href="{pigcms{:U('Config/miaomaiyun_erp',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("淼迈云ERP")}</a>
										</td>
									</eq>

									<if condition="empty($merchant_session['store_id'])">
										<td>
											<a class="label label-sm label-info" href="{pigcms{:U('GoodsLibrary/goods_list',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("商品库")}</a>
										</td>
									</if>
									<td style="display:<if condition=" !$config['single_system'] || $config['single_system_type']=='shop'">table-cell<else/>none</if>;">
										<a class="label label-sm label-info" title="{pigcms{:L_(" 店员管理")}" href="{pigcms{:U('Config/staff',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("店员管理")}</a>
									</td>
									<td style="display:table-cell;">
										<a class="label label-sm label-info" title="{pigcms{:L_(" 导航管理")}" href="{pigcms{:U('Config/store_slider',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("导航管理")}</a>
									</td>
									<if condition="$config['pay_in_store']">
										<td class="text-middle">
											<a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_(" 支付类型管理")}" href="{pigcms{:U('Config/pay', array('store_id'=>$vo['store_id']))}">{pigcms{:L_("查看")}</a>
										</td>
									</if>
									<else />
									<td>--</td>
									<if condition="$config['store_open_wxapp_qrcode']">
										<td>--</td>
									</if>
									<if condition="$config['mall_alias_name']">
										<td>--</td>
									</if>
									<if condition="$config['pay_in_store']">
										<td class="text-middle">--</td>
									</if>
								</if>

								<td class="am-text-middle">
									<a style="width: 60px;" class="label label-sm label-info" title="{pigcms{:L_(" 店铺优惠")}" href="{pigcms{:U('Config/discount',array('store_id'=>$vo['store_id']))}">{pigcms{:L_("店铺优惠")}</a>
								</td>
								<td class="am-text-middle">
									<div class="tpl-table-black-operation">
										<a href="{pigcms{:U('Config/store_edit',array('id'=>$vo['store_id']))}">{pigcms{:L_("编辑")}</a>
										<a title="{pigcms{:L_(" 删除")}" class="item-delete tpl-table-black-operation-del" href="{pigcms{:U('Config/store_del',array('id'=>$vo['store_id']))}">{pigcms{:L_("删除")}</a>
									</div>
								</td>
								</tr>
								</volist>
								<else />
								<tr class="odd">
									<td class="button-column" colspan="11">{pigcms{:L_("无内容")}</td>
								</tr>
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
	$(function() {
		/*{pigcms{:L_("店铺状态")}*/
		updateStatus(".statusSwitch .ace-switch", ".statusSwitch", "OPEN", "CLOSED", "shopstatus");

		jQuery(document).on('click', '#shopList a.red', function() {
			if (!confirm('{pigcms{:L_("确定要删除这条数据吗?不可恢复。")}')) return false;
		});

	});

	function CreateShop() {
		window.location.href = "{pigcms{:U('Config/store_add')}";
	}

	function updateStatus(dom1, dom2, status1, status2, attribute) {
		$(dom1).each(function() {
			if ($(this).attr("data-status") == status1) {
				$(this).attr("checked", true);
			} else {
				$(this).attr("checked", false);
			}
			$(dom2).show();
		}).click(function() {
			var _this = $(this),
				type = 'open',
				id = $(this).attr("data-id");
			_this.attr("disabled", true);
			if (_this.attr("checked")) { //"开启")}
				type = 'open';
			} else { //"关闭")}
				type = 'close';
			}
			$.ajax({
				url: "{pigcms{:U('Config/store_status')}",
				type: "post",
				data: {
					"type": type,
					"id": id,
					"status1": status1,
					"status2": status2,
					"attribute": attribute
				},
				dataType: "text",
				success: function(d) {
					if (!d) { //"失败")}
						if (type == 'open') {
							_this.attr("checked", false);
						} else {
							_this.attr("checked", true);
						}
						bootbox.alert("{pigcms{:L_('操作失败')}");
					}
					_this.attr("disabled", false);
				}
			});
		});
	}
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	var shopName = '{pigcms{$config["shop_alias_name"]}',
		groupName = '{pigcms{$config["group_alias_name"]}',
		mealName = '{pigcms{$config["meal_alias_name"]}',
		mallName = '{pigcms{$config["mall_alias_name"]}';
	$(function() {
		$('.see_qrcode').live('click', function() {
			art.dialog.open($(this).attr('href'), {
				init: function() {
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle', iframe);
				},
				id: 'handle',
				title: '{pigcms{:L_("查看渠道二维码")}',
				padding: 0,
				width: 430,
				height: 433,
				lock: true,
				resize: false,
				background: 'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity: '0.4'
			});
			return false;
		});
		$('.see_qrcode_wxapp').live('click', function() {
			art.dialog.open($(this).attr('href'), {
				init: function() {
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle', iframe);
				},
				id: 'handle',
				title: '{pigcms{:L_("查看小程序二维码")}',
				padding: 0,
				width: 430,
				height: 430,
				lock: true,
				resize: false,
				background: 'black',
				button: null,
				fixed: false,
				close: null,
				left: '50%',
				top: '38.2%',
				opacity: '0.4'
			});
			return false;
		});

		$('.store_info_more').click(function() {
			var content = '<p>{pigcms{:L_("店铺名称：")}' + $(this).data('store_name') + '</p>';
			content += '<p>{pigcms{:L_("联系电话：")}' + $(this).data('phone') + '</p>';
			content += '<p>{pigcms{:L_("店铺地址：")}' + $(this).data('address') + '</p>';
			if (mealName) {
				content += '<p>' + mealName + '：' + $(this).data('hasmeal') + '</p>';
			}
			if (groupName) {
				content += '<p>' + groupName + '：' + $(this).data('hasgroup') + '</p>';
			}
			if (shopName) {
				content += '<p>' + shopName + '：' + $(this).data('hasshop') + '</p>';
			}
			if (mallName) {
				content += '<p>' + mallName + '：' + $(this).data('hasmall') + '</p>';
			}
			if ($(this).data('store_status') != '2') {
				content += '<p>{pigcms{:L_("店铺二维码：")}<a href="' + $(this).data('store_qrcode_url') + '" class="see_qrcode">{pigcms{:L_("查看二维码")}</a></p>';
			}
			art.dialog({
				title: '{pigcms{:L_("店铺基本信息")}',
				content: content,
				lock: true
			});
		});
		var host = "{pigcms{$config.site_url}";
		$('.erp_btn').click(function() {
			var mer_id = $(this).data('mer_id');
			var store_id = $(this).data('store_id');
			var goods_src = host + "/appapi.php?c=Erpapi&a=save_store_goods&mer_id=" + mer_id + "&store_id=" + store_id;
			var sku_src = host + "/appapi.php?c=Erpapi&a=save_goods_num&mer_id=" + mer_id + "&store_id=" + store_id;
			var content = '<div style="max-width:400px;">';
			content += '<p>{pigcms{:L_("商品档案同步接口：")}' + goods_src + '<button type="button" class="erp_copy" style="margin-left:5px;" data-clipboard-text=' + goods_src + '>{pigcms{:L_("复制")}</button></p>';
			content += '<p>{pigcms{:L_("可售量同步接口：")}' + sku_src + '<button type="button" class="erp_copy" style="margin-left:5px;" data-clipboard-text=' + sku_src + '>{pigcms{:L_("复制")}</button></p>';
			content += '</div>';
			art.dialog({
				title: '{pigcms{:L_("用友Erp对接接口信息")}',
				content: content,
				lock: true
			});
		});


	});
</script>
<script type="text/javascript">
	$(function() {
		var clipboard = new Clipboard('.erp_copy');

		clipboard.on('success', function(e) {
			layer.alert('{pigcms{:L_("拷贝成功！")}', {
				icon: 1
			});
		});

		clipboard.on('error', function(e) {
			layer.alert('{pigcms{:L_("抱歉，该浏览器不支持该插件，请更换浏览器操作！")}', {
				icon: 2
			});
		});
	});
</script>

<script>
	$(document).ready(function(){
		$('.is-share-switch').click(function(){
			console.log($(this).children('input').prop('checked'));
		})
		// console.log($('.is-share-switch').val());
	})

	
</script>


<script type="text/javascript">
	<if condition = "$_GET['page_from'] eq 'shop_fitment' && $_GET['page'] elt 1" >
		<if condition = "count($store_list) eq 1" >
		location.href = "{pigcms{:U('Diypage/index',array('store_id'=>$store_list[0]['store_id']))}"; 
		</if> 
	</if>
</script>



<include file="Public:footer" />