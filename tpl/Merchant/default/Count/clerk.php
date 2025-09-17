<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<i class="ace-icon fa fa-bar-chart-o bar-chart-o-icon"></i>
			<li class="active">{pigcms{:L_("商家账单")}</li>
			<li class="active">{pigcms{:L_("店员对账")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<div class="tab-content">
							<div class="tab-pane active">
								<div id="shopList" class="grid-view">
									<table class="table table-striped table-bordered table-hover">
										<thead>
											<tr>
												<th width="180">{pigcms{:L_("店铺名称")}</th>
												<th width="180">{pigcms{:L_("帐号")}</th>
												<th>{pigcms{:L_("姓名")}</th>
												<th width="180">{pigcms{:L_("电话")}</th>
												<th width="180">{pigcms{:L_("添加时间")}</th>
												<th width="180" class="am-text-middle">{pigcms{:L_("操作")}</th>
											</tr>
										</thead>
										<tbody>
											<if condition="$staff_list">
												<volist name="staff_list" id="staff">
													<tr class="<if condition="$i%2 eq 0">odd<else/>even</if>">
														<td>{pigcms{$staff.store_name}</td>
														<td>{pigcms{$staff.username}</td>
														<td>{pigcms{$staff.name}</td>
														<td>{pigcms{$staff.tel}</td>
														<td>{pigcms{$staff.time|date='Y-m-d H:i:s',###}</td>
														<td class="am-text-middle">
															<div class="tpl-table-black-operation">
                                                                <a class="tpl-table-black-operation-green" href="{pigcms{:U('Count/staff_bill', array('itemid' => $staff['id'], 'store_id' => $staff['store_id']))}">{pigcms{:L_("店员对账")}</a>
                                                            </div>
														</td>
													</tr>
												</volist>
											<else/>
												<tr class="odd"><td class="button-column" colspan="6" >{pigcms{:L_("无内容")}</td></tr>
											</if>
										</tbody>
									</table>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<include file="Public:footer"/>
