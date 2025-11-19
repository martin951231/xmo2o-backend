<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-truck"></i>
				<a href="{pigcms{:U('Express/index',array('no_nav'=>$_GET['no_nav']))}">{pigcms{:L_("运费模板管理")}</a>
			</li>
			<li>{pigcms{:L_("模板编辑")}</li>
		</ul>
	</div>
	<style type="text/css">
	label{margin-right: 20px;}
	label input[type=radio]{
		vertical-align: sub;
	}
	</style>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<div class="app">
				<div class="app-inner clearfix">
					<div class="app-init-container">
						<div class="freight-wrap">
							<style type="text/css">
							.city{
								margin-left:20px;
							}
							.city ul li{padding:5px;color:grey;}
							.select_city{
								background-color: #d7d7d7;
							}
							</style>
							<?php if($_GET['tid']){?>
								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("编辑运费模板")}</p>
							<?php }else{?>
								<p style="font-size:23px;font-weight:bold;">{pigcms{:L_("新增运费模板")}</p>
							<?php }?>
							<div>
								<form class="form-horizontal freight-add-form" onkeypress="return event.keyCode != 13;" tpl-id="{pigcms{$template['id']}">
									<div class="control-group">
										<label class="control-label">
											{pigcms{:L_("模板名称：")}
										</label>
										<div class="controls">
											<input type="text" value="{pigcms{$template['name']}" name="name">
										</div>
									</div>
									<div class="control-group">
										<label class="control-label">
											{pigcms{:L_("计费方式：")}
										</label>
										<div class="controls">
											<if condition="empty($template)">
												<label>
													<input type="radio" name='freight_type' value="1" checked='checked' />{pigcms{:L_("按件数")}
												</label>
												<label>
													<input type="radio" name='freight_type' value="2" />{pigcms{:L_("按重量")}
												</label>
												<label>
													<input type="radio" name='freight_type' value="3" />{pigcms{:L_("按体积")}
												</label>
											<else/>
												<eq name="template.freight_type" value='1'>
												<label>
													<input type="radio" name='freight_type' value="1" checked />{pigcms{:L_("按件数")}
												</label>
												</eq>
												<eq name="template.freight_type" value='2'>
												<label>
													<input type="radio" name='freight_type' value="2" checked />{pigcms{:L_("按重量")}
												</label>
												</eq>
												<eq name="template.freight_type" value='3'>
												<label>
													<input type="radio" name='freight_type' value="3" checked />{pigcms{:L_("按体积")}
												</label>
												</eq>
											</if>
										</div>
									</div>
									<div class="control-group">
										<label class="control-label">
											{pigcms{:L_("配送区域")}：
										</label>
										<div class="controls">
											<table class="freight-template-table freight-template-item">
												<thead class="js-freight-cost-header freight-template-title">
													<tr>
														<th>{pigcms{:L_("可配送区域")}</th>
														<th class='type_box1'>{pigcms{:L_("首件")}</th>
														<th class='type_box1'>{pigcms{:L_("运费")}({pigcms{$config.Currency_txt})</th>
														<th class='type_box1'>{pigcms{:L_("续件")}</th>
														<th class='type_box1'>{pigcms{:L_("运费")}({pigcms{$config.Currency_txt})</th>
														<th class='type_box2'>{pigcms{:L_("首重")}(KG)</th>
														<th class='type_box2'>{pigcms{:L_("运费")}({pigcms{$config.Currency_txt})</th>
														<th class='type_box2'>{pigcms{:L_("续重")}(KG)</th>
														<th class='type_box2'>{pigcms{:L_("续费")}({pigcms{$config.Currency_txt})</th>
														<th class='type_box3'>{pigcms{:L_("单价（X1/立方米）", ['X1'=>$config['Currency_txt']])}</th>
														<th>{pigcms{:L_("满免（满多少X1免邮费, 0表示不免邮）", ['X1'=>$config['Currency_txt']])}</th>
													</tr>
												</thead>
												<tbody>
													<volist name="template['value_list']" id="vlist">
													<tr data-vid="{pigcms{$vlist['id']}">
														<td>
															<volist name="vlist['area_list']" id="vo">
															<span area-id="{pigcms{$vo['area_id']}" class="text-depth">{pigcms{$vo['area_name']}</span>、
															</volist>
															<div class="right"><a href="javascript:;" class="js-edit-cost-item">{pigcms{:L_("编辑")}</a> <a href="javascript:;" class="js-delete-cost-item">{pigcms{:L_("删除")}</a></div>
														</td>
														<td class='type_box1'>
															<input type="text" value="{pigcms{$vlist['first_weight']}" class="cost-input js-input-currency" name="first_weight" maxlength="8">
														</td>
														<td class='type_box1'>
															<input type="text" value="{pigcms{$vlist['first_freight']}" class="cost-input js-input-currency" name="first_freight" maxlength="8">
														</td>
														<td class='type_box1'>
															<input type="text" value="{pigcms{$vlist['add_weight']}" class="cost-input js-input-currency" name="add_weight" maxlength="8">
														</td>
														<td class='type_box1'>
															<input type="text" value="{pigcms{$vlist['add_freight']}" class="cost-input js-input-currency" name="add_freight" maxlength="8">
														</td>
														<td class='type_box2'>
															<input type="text" value="{pigcms{$vlist['first_weight']}" class="cost-input js-input-currency" name="first_weight" maxlength="8" data-min_num='0.1'/>
														</td>
														<td class='type_box2'>
															<input type="text" value="{pigcms{$vlist['first_freight']}" class="cost-input js-input-currency" name="first_freight" maxlength="8"  />
														</td>
														<td class='type_box2'>
															<input type="text" value="{pigcms{$vlist['add_weight']}" class="cost-input js-input-currency" name="add_weight" maxlength="8" />
														</td>
														<td class='type_box2'>
															<input type="text" value="{pigcms{$vlist['add_freight']}" class="cost-input js-input-currency" name="add_freight" maxlength="8" />
														</td>
														<td class='type_box3'>
															<input type="text" value="{pigcms{$vlist['freight']}" class="cost-input js-input-currency" name="freight" maxlength="8">
														</td>
														<td>
															<input type="text" value="{pigcms{$vlist['full_money']}" class="cost-input js-input-currency" name="full_money" data-default="0" maxlength="5">
														</td>
													</tr>
													</volist>
												</tbody>
												<tfoot class="js-freight-tablefoot" style="display:table-footer-group;">
													<tr>
														<td><a href="javascript:;" class="js-assign-cost">{pigcms{:L_("指定可配送区域和运费")}</a></td>
														<td colspan="10"></td>
													</tr>
												</tfoot>
											</table>
										</div>
									</div>
									<div class="control-group">
										<div class="controls">
											<input type="hidden" value="{pigcms{$_GET['no_nav']}" class="cost-input js-input-currency" name="no_nav">
											<button type="button" class="tbtn btn-primary btn-wide js-save-btn">{pigcms{:L_("保存")}</button>&nbsp;&nbsp;<a href="{pigcms{:U('Express/index',array('no_nav'=>$_GET['no_nav']))}" class="tbtn btn-wide">{pigcms{:L_("返回")}</a>
										</div>
									</div>
								</form>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
var load_url = "{pigcms{:U('Express/index',array('no_nav'=>$_GET['no_nav']))}", add_url = "{pigcms{:U('Express/save')}",open_limit='1';
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.min.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/delivery.js?2" charset="utf-8"></script>
<script type="text/javascript">
var freight_type={pigcms{$template.freight_type|default=1};
/*{pigcms{:L_("选择运费计算方式")}*/
function change_freight_type(freight_type){
	if(freight_type==1){
		$(document).find('.type_box1').show();
		$(document).find('.type_box2').hide();
		$(document).find('.type_box3').hide();
	}else if(freight_type==2){
		$(document).find('.type_box2').show();
		$(document).find('.type_box1').hide();
		$(document).find('.type_box3').hide();
	}else{
		$(document).find('.type_box3').show();
		$(document).find('.type_box1').hide();
		$(document).find('.type_box2').hide();
	}
}

change_freight_type(freight_type);
$(function(){
	$('input[name=freight_type]').click(function(){
		var val=$(this).val();
		freight_type=val;
		change_freight_type(val);
	});
});
</script>
<include file="Public:footer"/>