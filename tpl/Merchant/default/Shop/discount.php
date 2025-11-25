<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Shop/index')}">{pigcms{:L_("店铺管理")}</a>
			</li>
			<li class="active">{pigcms{:L_("店铺优惠列表")}</li>
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
					<a class="btn btn-success" title="{pigcms{:L_("新建优惠")}" href="{pigcms{:U('Shop/discount_add', array('store_id' => $store['store_id']))}">{pigcms{:L_("新建优惠")}</a>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
								<th>{pigcms{:L_("编号")}</th>
								<th>{pigcms{:L_("类别")}</th>
								<th>{pigcms{:L_("满足金额")}</th>
								<th>{pigcms{:L_("优惠金额")}</th>
								<th>{pigcms{:L_("是否与限时优惠、店铺")}/{pigcms{:L_("分类折扣、会员优惠同享")}</th>
								<th>{pigcms{:L_("使用状态")}</th>
								<th class="textcenter">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
							<if condition="is_array($discount_list)">
								<volist name="discount_list" id="vo">
									<tr>
										<td>{pigcms{$vo.id}</td>
										<td><if condition="$vo['type'] eq 0">{pigcms{:L_("新单")}<elseif condition="$vo['type'] eq 1" />{pigcms{:L_("满减")}<else />{pigcms{:L_("配送")}</if></td>
										<td>{pigcms{$vo.full_money}</td>
										<td>{pigcms{$vo.reduce_money}</td>
										<td><if condition="$vo['is_share'] eq 1"><font color="green">{pigcms{:L_("同享")}</font><else/><font color="red">{pigcms{:L_("不同享")}</font></if></td>
										<td><if condition="$vo['status'] eq 1"><font color="green">{pigcms{:L_("启用")}</font><else/><font color="red">{pigcms{:L_("停用")}</font></if></td>
										<td class="button-column">
											<a style="width:80px;" class="label label-sm label-info" href="{pigcms{:U('Shop/discount_edit',array('store_id'=>$vo['store_id'], 'id' => $vo['id']))}">{pigcms{:L_("编辑")}</a>
										</td>
									</tr>
								</volist>
							<else/>
								<tr><td class="textcenter red" colspan="6">{pigcms{:L_("列表为空！")}</td></tr>
							</if>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer"/>
