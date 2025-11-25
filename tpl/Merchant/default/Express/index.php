<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-truck"></i>
				<a href="{pigcms{:U('Express/index',array('no_nav'=>$_GET['no_nav']))}">{pigcms{:L_("运费模板管理")}</a>
			</li>
			<li>{pigcms{:L_("运费模板列表")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
		
			<div class="app">
				<div class="app-inner clearfix">
					<div class="app-init-container">
						<div class="freight-wrap">
							<div class="freight-list">
								<div class="freight-head">
									<a href="{pigcms{:U('Express/add',array('no_nav'=>$_GET['no_nav']))}" class="btn btn-success">{pigcms{:L_("新建运费模板")}</a>
								</div>
								<div class="freight-content">
									<div class="freight-template-list-wrap">
										<ul>
											<volist name="template_list" id="tlist">
											<li class="freight-template-item">
												<h4 class="freight-template-title">
													<b>{pigcms{$tlist['name']}</b>  
													<div class="right">
														<span class="c-gray">{pigcms{:L_("最后编辑时间")} {pigcms{$tlist['dateline']|date='Y-m-d H:i:s',###}</span>&nbsp;&nbsp;
<!-- 														<a href="javascript:;" class="js-freight-copy" tpl-id="{pigcms{$tlist['id']}">{pigcms{:L_("复制模板")}</a> - -->
														<a href="javascript:;" data-href="{pigcms{:U('Express/edit', array('tid' => $tlist['id'],'no_nav'=>$_GET['no_nav']))}" class="js-freight-edit" tpl-id="{pigcms{$tlist['id']}">{pigcms{:L_("修改")}</a> -
														<a href="javascript:;" class="js-freight-delete" tpl-id="{pigcms{$tlist['id']}">{pigcms{:L_("删除")}</a>
														<a href="javascript:;" class="js-freight-extend-toggle freight-extend-toggle freight-extend-toggle-extend"></a>
													</div>
												</h4>
												<table class="freight-template-table hide">
													<thead class="js-freight-cost-list-header">
														<tr>
															<th>{pigcms{:L_("可配送至")}</th>
															<eq name="tlist.freight_type" value='1'>
																<th>{pigcms{:L_("首件")}</th>
																<th>{pigcms{:L_("运费")}({pigcms{$config.Currency_txt})</th>
																<th>{pigcms{:L_("续件")}</th>
																<th>{pigcms{:L_("续费")}({pigcms{$config.Currency_txt})</th>
															</eq>
															<eq name="tlist.freight_type" value='2'>
																<th>{pigcms{:L_("首重")}(KG)</th>
																<th>{pigcms{:L_("运费")}({pigcms{$config.Currency_txt})</th>
																<th>{pigcms{:L_("续重")}(KG)</th>
																<th>{pigcms{:L_("续费")}({pigcms{$config.Currency_txt})</th>
															</eq>
															<eq name="tlist.freight_type" value='3'>
																<th>{pigcms{:L_("运费（X1/立方米）", ['X1' => $config['Currency_txt']])}</th>
															</eq>
															<th>{pigcms{:L_("满免（满多少X1免邮费, 0表示不免邮）", ['X1' => $config['Currency_txt']])}</th>
														</tr>
													</thead>
													<tbody>
														<volist name="tlist['value_list']" id="vlist">
														<tr>
															<td class="">
																<volist name="vlist['area_list']" id="vo">
																<span class="text-depth">{pigcms{$vo['area_name']}</span>,
																</volist>
															</td>
															<eq name="tlist.freight_type" value='3'>
																<td>{pigcms{$vlist['freight']|floatval}</td>
															<else/>
																<td>{pigcms{$vlist['first_weight']|floatval}</td>
																<td>{pigcms{$vlist['first_freight']|floatval}</td>
																<td>{pigcms{$vlist['add_weight']|floatval}</td>
																<td>{pigcms{$vlist['add_freight']|floatval}</td>
															</eq>
															<td>{pigcms{$vlist['full_money']|floatval}</td>
														</tr>
														</volist>
													</tbody>
												</table>
											</li>
											</volist>
										</ul>
									</div>
								</div>
								{pigcms{$pagebar}
								<!--div class="js-page-list ui-box pagenavi"><span class="total">{pigcms{:L_("共 3 条，每页 15 条")}</span> </div-->
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>var delete_url = "{pigcms{:U('Express/delete')}", load_url = '';</script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/delivery.js" charset="utf-8"></script>
<include file="Public:footer"/>