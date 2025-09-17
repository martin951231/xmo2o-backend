<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-truck"></i>
				<a href="{pigcms{:U('Market_express/index')}">{pigcms{:L_("运费模板管理")}</a>
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
									<a href="{pigcms{:U('Market_express/add')}" class="btn btn-success">{pigcms{:L_("新建运费模板")}</a>
								</div>
								<div class="freight-content">
									<div class="freight-template-list-wrap">
										<ul>
											<volist name="template_list" id="item">
											<li class="freight-template-item">
												<h4 class="freight-template-title">
													<b>{pigcms{$item['name']}</b>
													<div class="right">
														<span class="c-gray">{pigcms{:L_("最后编辑时间")} {pigcms{$item['dateline']|date='Y-m-d H:i:s',###}</span>&nbsp;&nbsp;
<!-- 														<a href="javascript:;" class="js-freight-copy" tpl-id="{pigcms{$tlist['id']}">{pigcms{:L_("复制模板")}</a> - -->
														<a href="javascript:;" data-href="{pigcms{:U('Market_express/edit', array('tid' => $item['id']))}" class="js-freight-edit" tpl-id="{pigcms{$item['id']}">{pigcms{:L_("修改")}</a> -
														<a href="javascript:;" class="js-freight-delete" tpl-id="{pigcms{$item['id']}">{pigcms{:L_("删除")}</a>
														<a href="javascript:;" class="js-freight-extend-toggle freight-extend-toggle freight-extend-toggle-extend"></a>
													</div>
												</h4>
                                                    <table class="freight-template-table hide">
                                                        <volist name="item['msg']" id="tlist">
                                                        <thead class="js-freight-cost-list-header">
                                                            <tr>
                                                                <th>{pigcms{:L_("配送方式")}</th>
                                                                <th>{pigcms{:L_("计费方式")}</th>
                                                                <th>{pigcms{:L_("可配送至")}</th>
                                                                <eq name="tlist.freight_type" value='1'>
                                                                    <th>{pigcms{:L_("首件")}</th>
                                                                    <th>{pigcms{:L_("运费(元")})</th>
                                                                    <th>{pigcms{:L_("续件")}</th>
                                                                    <th>{pigcms{:L_("续费(元")})</th>
                                                                </eq>
                                                                <eq name="tlist.freight_type" value='2'>
                                                                    <th>{pigcms{:L_("首重")}(KG)</th>
                                                                    <th>{pigcms{:L_("运费(元")})</th>
                                                                    <th>{pigcms{:L_("续重")}(KG)</th>
                                                                    <th>{pigcms{:L_("续费(元")})</th>
                                                                </eq>
                                                                <eq name="tlist.freight_type" value='3'>
                                                                    <th>{pigcms{:L_("运费（元/立方米）")}</th>
                                                                </eq>
                                                                <th>{pigcms{:L_("满免（满多少")}{pigcms{$config['Currency_txt']}{pigcms{:L_("免邮费, 0表示不免邮）")}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <volist name="tlist['value_list']" id="vlist">
                                                                <tr style="border-bottom: 1px solid #e5e5e5;">
                                                                    <td class="">
                                                                        <eq name="tlist.type" value='1'>
                                                                            快递配送
                                                                            <else/>
                                                                            物流配送
                                                                        </eq>
                                                                    </td>
                                                                    <td class="">
                                                                        <eq name="tlist.freight_type" value='3'>
                                                                            按体积
                                                                            <elseif condition="$tlist.freight_type eq 2"/>
                                                                            按重量
                                                                            <else/>
                                                                            按件数
                                                                        </eq>
                                                                    </td>

                                                                    <td class="">
                                                                        <span class="text-depth">{pigcms{$vlist['area_info']}</span>
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
                                                        </volist>
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
<script>var delete_url = "{pigcms{:U('Market_express/delete')}", load_url = '';</script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/market_delivery.js" charset="utf-8"></script>
<include file="Public:footer"/>