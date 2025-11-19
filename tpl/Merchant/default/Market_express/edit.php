<include file="Public:header"/>

<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-truck"></i>
				<a href="{pigcms{:U('Express/index')}">{pigcms{:L_("运费模板管理")}</a>
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
                                    <div class="control-group" style="color: red;padding: 10px 0;">
                                        <label class="control-label" style="color: red;font-weight: bolder;">
                                            {pigcms{:L_("配送方式：")}
                                        </label>
                                        <div class="controls">
                                            <if condition="empty($template)">
                                                <label>
                                                    <input type="radio" name='is_type' value="0" checked='checked' />&nbsp;&nbsp;{pigcms{:L_("快递和物流配送")}
                                                </label>
                                                <label>
                                                    <input type="radio" name='is_type' value="1" />&nbsp;&nbsp;{pigcms{:L_("快递配送")}
                                                </label>
                                                <label>
                                                    <input type="radio" name='is_type' value="2" />&nbsp;&nbsp;{pigcms{:L_("物流配送")}
                                                </label>
                                                <else/>
                                                <label>
                                                    <input type="radio" name='is_type' value="0" <eq name="template.is_type" value='0'>checked</eq> />&nbsp;&nbsp;{pigcms{:L_("快递和物流配送")}
                                                </label>
                                                <label>
                                                    <input type="radio" name='is_type' value="1" <eq name="template.is_type" value='1'>checked</eq> />&nbsp;&nbsp;{pigcms{:L_("快递配送")}
                                                </label>
                                                <label>
                                                    <input type="radio" name='is_type' value="2" <eq name="template.is_type" value='2'>checked</eq> />&nbsp;&nbsp;{pigcms{:L_("物流配送")}
                                                </label>

                                            </if>
                                        </div>
                                    </div>
                                    <div class="is_type_box1">
                                        <div style="padding: 10px 5px 10px 50px;text-align: left;width: 100%;font-size: 14px;line-height: 18px;background-color: #F6F6F6;color: #0a0a0a;">快递配送</div>

                                        <div class="control-group" style="padding: 10px 0;">
                                            <label class="control-label">
                                                {pigcms{:L_("计费方式：")}
                                            </label>
                                            <div class="controls">
                                                <if condition="empty($template)">
                                                    <label>
                                                        <input class="freight_type" data-type="1" type="radio" name='freight_type[1]' value="1" checked='checked' />&nbsp;&nbsp;{pigcms{:L_("按件数")}
                                                    </label>
                                                    <label>
                                                        <input class="freight_type" data-type="1" type="radio" name='freight_type[1]' value="2" />&nbsp;&nbsp;{pigcms{:L_("按重量")}
                                                    </label>
                                                    <label>
                                                        <input class="freight_type" data-type="1" type="radio" name='freight_type[1]' value="3" />&nbsp;&nbsp;{pigcms{:L_("按体积")}
                                                    </label>
                                                    <else/>
                                                    <php> if ($template['freight_type_1'] == 1) { </php>
                                                        <label>
                                                            <input type="radio" name='freight_type[1]' value="1" checked />&nbsp;&nbsp;{pigcms{:L_("按件数")}
                                                        </label>
                                                    <php> } </php>
                                                    <php> if ($template['freight_type_1'] == 2) { </php>
                                                        <label>
                                                            <input type="radio" name='freight_type[1]' value="2" checked />&nbsp;&nbsp;{pigcms{:L_("按重量")}
                                                        </label>
                                                    <php> } </php>
                                                    <php> if ($template['freight_type_1'] == 3) { </php>
                                                        <label>
                                                            <input type="radio" name='freight_type[1]' value="3" checked />&nbsp;&nbsp;{pigcms{:L_("按体积")}
                                                        </label>
                                                    <php> } </php>
                                                </if>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">
                                                {pigcms{:L_("配送区域：")}
                                            </label>
                                            <div class="controls">
                                                <table class="freight-template-table freight-template-item">
                                                    <thead class="js-freight-cost-header freight-template-title">
                                                    <tr>
                                                        <th>{pigcms{:L_("可配送区域")}</th>
                                                        <th class='type_box1'>{pigcms{:L_("首件")}</th>
                                                        <th class='type_box1'>{pigcms{:L_("运费(元")})</th>
                                                        <th class='type_box1'>{pigcms{:L_("续件")}</th>
                                                        <th class='type_box1'>{pigcms{:L_("运费(元")})</th>
                                                        <th class='type_box2'>{pigcms{:L_("首重")}(KG)</th>
                                                        <th class='type_box2'>{pigcms{:L_("运费(元")})</th>
                                                        <th class='type_box2'>{pigcms{:L_("续重")}(KG)</th>
                                                        <th class='type_box2'>{pigcms{:L_("续费(元")})</th>
                                                        <th class='type_box3'>{pigcms{:L_("单价（元/立方米）")}</th>
                                                        <th>{pigcms{:L_("满免（满多少")}{pigcms{$config['Currency_txt']}{pigcms{:L_("免邮费, 0表示不免邮）")}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <volist name="template['value_list'][1]" id="vlist">
                                                        <tr data-vid="{pigcms{$vlist['id']}" data-type="1">
                                                            <td>
                                                                <volist name="vlist['area_list']" id="vo">
                                                                    <span area-id="{pigcms{$vo['area_id']}" class="text-depth">{pigcms{$vo['area_name']}</span>、
                                                                </volist>
                                                                <div class="right"><a href="javascript:;" class="js-edit-cost-item" data-type="1">{pigcms{:L_("编辑")}</a> <a href="javascript:;" class="js-delete-cost-item" data-type="1">{pigcms{:L_("删除")}</a></div>
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
                                                        <td><a href="javascript:;" class="js-assign-cost" data-type="1">{pigcms{:L_("指定可配送区域和运费")}</a></td>
                                                        <td colspan="10"></td>
                                                    </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="is_type_box2">
                                        <div style="padding: 10px 5px 10px 50px;text-align: left;width: 100%;font-size: 14px;line-height: 18px;background-color: #F6F6F6;color: #0a0a0a;">物流配送</div>

                                        <div class="control-group" style="padding: 10px 0;">
                                            <label class="control-label">
                                                {pigcms{:L_("计费方式：")}
                                            </label>
                                            <div class="controls">
                                                <if condition="empty($template)">
                                                    <label>
                                                        <input class="freight_type" data-type="2" type="radio" name='freight_type[2]' value="1" checked='checked' />&nbsp;&nbsp;{pigcms{:L_("按件数")}
                                                    </label>
                                                    <label>
                                                        <input class="freight_type" data-type="2" type="radio" name='freight_type[2]' value="2" />&nbsp;&nbsp;{pigcms{:L_("按重量")}
                                                    </label>
                                                    <label>
                                                        <input class="freight_type" data-type="2" type="radio" name='freight_type[2]' value="3" />&nbsp;&nbsp;{pigcms{:L_("按体积")}
                                                    </label>
                                                    <else/>
                                                    <php> if ($template['freight_type_2'] == 1) { </php>
                                                        <label>
                                                            <input type="radio" name='freight_type[2]' value="1" checked />&nbsp;&nbsp;{pigcms{:L_("按件数")}
                                                        </label>
                                                    <php> } </php>
                                                    <php> if ($template['freight_type_2'] == 2) { </php>
                                                        <label>
                                                            <input type="radio" name='freight_type[2]' value="2" checked />&nbsp;&nbsp;{pigcms{:L_("按重量")}
                                                        </label>
                                                    <php> } </php>
                                                    <php> if ($template['freight_type_2'] == 3) { </php>
                                                        <label>
                                                            <input type="radio" name='freight_type[2]' value="3" checked />&nbsp;&nbsp;{pigcms{:L_("按体积")}
                                                        </label>
                                                    <php> } </php>
                                                </if>
                                            </div>
                                        </div>
                                        <div class="control-group">
                                            <label class="control-label">
                                                {pigcms{:L_("配送区域：")}
                                            </label>
                                            <div class="controls">
                                                <table class="freight-template-table freight-template-item">
                                                    <thead class="js-freight-cost-header freight-template-title">
                                                    <tr>
                                                        <th>{pigcms{:L_("可配送区域")}</th>
                                                        <th class='type_box1'>{pigcms{:L_("首件")}</th>
                                                        <th class='type_box1'>{pigcms{:L_("运费(元")})</th>
                                                        <th class='type_box1'>{pigcms{:L_("续件")}</th>
                                                        <th class='type_box1'>{pigcms{:L_("运费(元")})</th>
                                                        <th class='type_box2'>{pigcms{:L_("首重")}(KG)</th>
                                                        <th class='type_box2'>{pigcms{:L_("运费(元")})</th>
                                                        <th class='type_box2'>{pigcms{:L_("续重")}(KG)</th>
                                                        <th class='type_box2'>{pigcms{:L_("续费(元")})</th>
                                                        <th class='type_box3'>{pigcms{:L_("单价（元/立方米）")}</th>
                                                        <th>{pigcms{:L_("满免（满多少")}{pigcms{$config['Currency_txt']}{pigcms{:L_("免邮费, 0表示不免邮）")}</th>
                                                    </tr>
                                                    </thead>
                                                    <tbody>
                                                    <volist name="template['value_list'][2]" id="vlist">
                                                        <tr data-vid="{pigcms{$vlist['id']}" data-type="2">
                                                            <td>
                                                                <volist name="vlist['area_list']" id="vo">
                                                                    <span area-id="{pigcms{$vo['area_id']}" class="text-depth">{pigcms{$vo['area_name']}</span>、
                                                                </volist>
                                                                <div class="right"><a href="javascript:;" class="js-edit-cost-item" data-type="2">{pigcms{:L_("编辑")}</a> <a href="javascript:;" class="js-delete-cost-item" data-type="2">{pigcms{:L_("删除")}</a></div>
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
                                                        <td><a href="javascript:;" class="js-assign-cost" data-type="2">{pigcms{:L_("指定可配送区域和运费")}</a></td>
                                                        <td colspan="10"></td>
                                                    </tr>
                                                    </tfoot>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
									<div class="control-group">
										<div class="controls">
											<button type="button" class="tbtn btn-primary btn-wide js-save-btn">{pigcms{:L_("保存")}</button>&nbsp;&nbsp;<a href="{pigcms{:U('Express/index')}" class="tbtn btn-wide">{pigcms{:L_("返回")}</a>
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
<script>var load_url = "{pigcms{:U('Market_express/index')}", add_url = "{pigcms{:U('Market_express/save')}";</script>
<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/area.min.js" charset="utf-8"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/market_delivery.js?9" charset="utf-8"></script>
<script type="text/javascript">
    var freight_type_1="{pigcms{$template.freight_type_1|default=1}";
    var freight_type_2="{pigcms{$template.freight_type_2|default=1}";

    change_freight_type(freight_type_1, 'is_type_box1');
    change_freight_type(freight_type_2, 'is_type_box2');
    /*{pigcms{:L_("选择运费计算方式")}*/
    function change_freight_type(freight_type, class_info){
        if(freight_type==1){
            $(document).find('.'+class_info+' .type_box1').show();
            $(document).find('.'+class_info+' .type_box2').hide();
            $(document).find('.'+class_info+' .type_box3').hide();
        }else if(freight_type==2){
            $(document).find('.'+class_info+' .type_box2').show();
            $(document).find('.'+class_info+' .type_box1').hide();
            $(document).find('.'+class_info+' .type_box3').hide();
        }else{
            $(document).find('.'+class_info+' .type_box3').show();
            $(document).find('.'+class_info+' .type_box1').hide();
            $(document).find('.'+class_info+' .type_box2').hide();
        }
    }
    var is_type="{pigcms{$template.is_type|default=0}";
    /*{pigcms{:L_("选择运费计算方式")}*/
    function change_is_type(is_type){
        if(is_type==0){
            $(document).find('.is_type_box1').show();
            $(document).find('.is_type_box2').show();
        }else if(is_type==1){
            $(document).find('.is_type_box1').show();
            $(document).find('.is_type_box2').hide();
        }else{
            $(document).find('.is_type_box1').hide();
            $(document).find('.is_type_box2').show();
        }
    }

    change_is_type(is_type);

    $(function(){
        $('.freight_type').click(function(){
            var val=$(this).val();
            freight_type=val;
            var type = $(this).data('type');
            var class_info = 'is_type_box1';
            if (type==2) {
                class_info = 'is_type_box2';
            }
            change_freight_type(val, class_info);
        });
        $('input[name=is_type]').click(function(){
            var val=$(this).val();
            is_type=val;
            change_is_type(val);
        });
    });
</script>
<include file="Public:footer"/>