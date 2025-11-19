<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/pick')}">{pigcms{:L_("自提点管理")}</a>
			</li>
			
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
					<button class="btn btn-success" onclick="window.location.href = '{pigcms{:U('Config/pick_address_add')}';">{pigcms{:L_("添加自提点")}</button>
					<a href="{pigcms{$config.site_url}/pick.php?g=Merchant&c=Pick&a=login" class="btn btn-success" target="_blank">{pigcms{:L_("自提点登录")}</a>
					<div id="shopList" class="grid-view">
						<table class="table table-striped table-bordered table-hover">
							<thead>
								<tr>
									<th width="120">{pigcms{:L_("编号")}</th>
									<th>{pigcms{:L_("省份")}</th>
									<th>{pigcms{:L_("城市")}</th>
									<th>{pigcms{:L_("区域")}</th>
									<th>{pigcms{:L_("自提点名称")}</th>
									<th>{pigcms{:L_("自提点电话")}</th>
									<th>{pigcms{:L_("登录密钥")}</th>
									<th>{pigcms{:L_("状态")}</th>
									<th class="text-middle">{pigcms{:L_("操作")}</th>
								</tr>
							</thead>
							<tbody>
								<if condition="$pick_addr">
									<volist name="pick_addr" id="vo">
										<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
											<td>{pigcms{$key}</td>
											<td>{pigcms{$vo['area_info']['province']}</td>
											<td>{pigcms{$vo['area_info']['city']}</td>
											<td>{pigcms{$vo['area_info']['area']}</td>
											<td>{pigcms{$vo.name}</td>
											<td><if condition ="$vo.phone eq 0">无<else />{pigcms{$vo.phone}</if></td>
											<td>
												<a title="{pigcms{:L_("操作订单")}" class="green layer-iframe" data-title="{pigcms{:L_("登录密钥")}" data-iframe_width="520" data-iframe_height="150" href="{pigcms{:U('Config/see_pick_pwd',array('pick_id'=>$vo['pick_addr_id']))}">{pigcms{:L_("查看")}</a>
											</td>
											<td><if condition ="$vo['is_open'] eq 0">关闭<else />开启</if></td>
											<td class="text-middle">
												<if condition="$vo['pick_addr_id']">
													<div class="tpl-table-black-operation">
														<a href="{pigcms{:U('Config/pick_address_edit',array('id'=>$vo['pick_addr_id']))}">{pigcms{:L_("编辑")}</a>
														<a title="{pigcms{:L_("删除")}" class="item-delete tpl-table-black-operation-del" href="{pigcms{:U('Config/pick_address_del',array('id'=>$vo['pick_addr_id']))}">{pigcms{:L_("删除")}</a>
													</div>
												</if>
											</td>
										</tr>
									</volist>
								<else/>
									<tr class="odd"><td class="button-column" colspan="8">{pigcms{:L_("您没有添加过自提点！")}</td></tr>
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
<include file="Public:footer"/>