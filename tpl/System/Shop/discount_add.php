<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Shop/discount_modify')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
            <input type="hidden" name="shop_type" value='{pigcms{$shop_type}'/>
            <tr>
                <th width="90">优惠名称</th>
                <td><input type="text" class="input fl" name="name" id="name" size="25" placeholder="优惠标识" validate="maxlength:20" tips="后台管理使用"/></td>
            </tr>

            <if condition="$config['open_meal_electronic_menu'] OR $config['open_meal_v2']">
            <tr>
                <th width="90">适用的业务</th>
                <td><select name="use_limit">
					<if condition="$config['shop_alias_name']">
						<option value="0">仅{pigcms{$config.shop_alias_name}</option>
					</if>
					<if condition="$config['meal_alias_name']">
						<option value="1">仅{pigcms{$config.meal_alias_name}</option>
					</if>
					<if condition="$config['mall_alias_name']">
						<option value="3">仅{pigcms{$config.mall_alias_name}</option>
					</if>
					<if condition="$config['shop_alias_name'] && $config['meal_alias_name']">
						<option value="2">{pigcms{$config.shop_alias_name}和{pigcms{$config.meal_alias_name}</option>
					</if>
                </select>
                </td>
            </tr>
            </if>
            <tr>
                <th width="90">优惠条件</th>
                <td><input type="text" class="input fl" name="full_money" id="full_money" size="25" placeholder="0表示任意金额都可" validate="required:true" tips="满足该条件才能有相应的优惠"/></td>
            </tr>
            <tr>
                <th width="90">使用百分比折扣优惠</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable"><span>启用</span><input type="radio" name="percentage_status" value="1" /></label></span>
                    <span class="cb-disable"><label class="cb-disable selected"><span>关闭</span><input type="radio" name="percentage_status" value="0" checked="checked"/></label></span>
                </td>
            </tr>
			<tr>
				<th width="90">优惠的金额</th>
				<td><input type="text" class="input fl" name="reduce_money" id="reduce_money" size="25" placeholder="优惠金额大于0" validate="maxlength:20,required:true" tips="减免的金额 开启百分比时需填写百分比 例0.5"/></td>
			</tr>
            <tr>
                <th width="90">SYSTEM_SHOP_DISCOUNT_ADD_1</th>
                <td><input type="text" class="input fl" name="plat_money" value="{pigcms{$now_discount.plat_money}" id="plat_money" size="10" placeholder="大于等于0" validate="maxlength:10" tips="平台承担的金额与商家承担的金额的和等于优惠的金额，如果两个都不填写的话，由平台全部承担 开启百分比时需填写百分比 例0.5"/></td>
            </tr>
            <tr>
                <th width="90">商家补贴金额</th>
                <td><input type="text" class="input fl" name="merchant_money" value="{pigcms{$now_discount.merchant_money}" id="merchant_money" size="10" placeholder="大于等于0" validate="maxlength:10" tips="平台承担的金额与商家承担的金额的和等于优惠的金额，如果两个都不填写的话，由平台全部承担 开启百分比时需填写百分比 例0.5"/></td>
            </tr>
			<tr>
				<th width="90">使用状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>
            <tr>
                <th width="90">优惠类型</th>
                <td>
                    <select name="type" class="valid" tips="满减：就是用户一次下单的金额满足了条件得到相应的优惠；配送：指用户一次下单满足多少钱后减免指定金额的配送费，最多减至配送费是0">
                    <option value="1">满减</option>
                    <neq name='shop_type' value='2'>
                        <option value="2">配送</option>
                    </neq>
                    </select>
                </td>
            </tr>

            <tr>
                <th width="90">优惠程度</th>
                <td>
                    <select name="order_count" class="valid" tips="限制用户前几单可以享受此优惠">
                        <option value="0">不限制</option>
                        <option value="1">首单优惠</option>
                        <option value="2">首2单优惠</option>
                        <option value="3">首3单优惠</option>
                    </select>
                </td>
            </tr>

            <tr class="is_shop_only">
                <th width="90">使用场景</th>
                <td id="usage_scenario">
                     <select name="usage_scenario" class="valid" tips="限制用户是配送还是自提时才可以享受此优惠">
                        <option value="0">不限制</option>
                        <option value="1">配送</option>
                        <option value="2">自提</option>
                    </select>
                </td>
            </tr>

            <tr>
                <th width="90">使用开始时间：</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="start_time" style="width:120px;" id="d4311"  value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" validate="required:true"/>
                </td>
            </tr>
            <tr>
                <th width="90">使用结束时间：</th>
                <td colspan="2">
                    <input type="text" class="input-text" name="end_time" style="width:120px;" id="d4311" value="" onfocus="WdatePicker({isShowClear:false,readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" validate="required:true"/>
                </td>
            </tr>

            <tr id="isShow">
                <th width="90">同享规则</th>
                <td>
                    <select name="is_share" class="valid" tips="平台满减/新单/配送活动与商家限时优惠、店铺/分类折扣、会员优惠活动是否同享">
                    <option value="0" >不同享</option>
                    <option value="1" selected>同享</option>
                    </select>
                </td>
            </tr>
            <if condition="$admin_level eq 2">
            <tr>
                <th width="90">使用区域</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>全区域可用</span><input type="radio" name="use_area" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>指定区域可用</span><input type="radio" name="use_area" value="1" /></label></span>
                </td>
            </tr>
            <else/>
            <input type="hidden" name="use_area" value="1" />
            </if>
            <tr>
                <th width="90">使用店铺</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>全部店铺可用</span><input type="radio" name="use_type" value="0" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>指定店铺可用</span><input type="radio" name="use_type" value="1" /></label></span>
                </td>
            </tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    <script type="text/javascript">
    $(function(){
        $('select[name=type]').change(function(){
            if ($(this).val() == 2) {
                $('#isShow').hide();
            } else {
                $('#isShow').show();
            }
			update_usage_scenario();
        });
		function update_usage_scenario(){
			var a = $('select[name=type]').val();
			$('#usage_scenario').html('');
			if(a==1){
				$('#usage_scenario').html('<select name="usage_scenario"  class="valid" tips="限制用户是配送还是自提时才可以享受此优惠"><option value="0">不限制</option><option value="1">配送</option><option value="2">自提</option></select>');
			}else{
				$('#usage_scenario').html('<select name="usage_scenario"  class="valid" tips="限制用户是配送还是自提时才可以享受此优惠"><option value="1">配送</option></select>');
			}
		}
		update_usage_scenario();
    });

    var open_meal_electronic_menu = {pigcms{$config.open_meal_electronic_menu|default=0};
    if (open_meal_electronic_menu) {
        $('select[name=use_limit]').change(function(){
            if ($(this).val() == '1') {
                $('.is_shop_only').hide();
            } else {
                $('.is_shop_only').show();
            }
        });
    }
    </script>
<include file="Public:footer"/>