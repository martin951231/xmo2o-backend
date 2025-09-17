<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/store_modify')}" frame="true" refresh="true">
		<input type="hidden" name="mer_id" value="{pigcms{$merchant.mer_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">店铺名称</th>
				<td><input type="text" class="input fl" name="name" size="25" placeholder="店铺名称" validate="required:true"/></td>
			</tr>
			<if condition="$config.international_phone eq 1">
				<tr>
					<th width="80">区号</th>
					<td>
				
						 <pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;">
							<option value="{pigcms{$vo.code}"  <if condition="$config.qcloud_sms_default_country eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
						</pigcms:national_phone>

					</td>
				</tr>
			</if>
			<tr>
				<th width="80">联系电话</th>
				<td>
			
				<input type="text" class="input fl" name="phone" size="25" placeholder="店铺的电话" validate="required:true" tips="多个电话号码以空格分开"/></td>
			</tr>
			<tr>
				<th width="80">店铺经纬度</th>
				<td id="choose_map" data-input="1"></td> 
			</tr>
			<if condition="$system_session['area_type'] neq 3">
				<tr>
					<th width="80">店铺所在地</th>
					<td id="choose_cityarea" show_please_select="1"></td>
				</tr>
			<else />
				<tr>
					<th width="160">所在区域</th>
					<td>
					{pigcms{$merchant.province_name} {pigcms{$merchant.city_name} {pigcms{$merchant.area_name}
					<input type="hidden" name="area_id"  value = "{pigcms{$system_session['area_id']}">
					<input type="hidden" name="city_id"  value = "{pigcms{$merchant['city_id']}">
					<input type="hidden" name="province_id"  value = "{pigcms{$merchant['province_id']}">
					</td>
				</tr>
			</if>
			<tr>
				<th width="80">店铺地址</th>
				<td><input type="text" class="input fl" name="adress" id="adress" size="25" placeholder="店铺的地址" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="80">店铺排序</th>
				<td><input type="text" class="input fl" name="sort" size="5" value="0" validate="required:true,number:true,maxlength:6" tips="默认添加顺序排序！手动调值，数值越大，排序越前"/></td>
			</tr>
			<if condition="$config['group_alias_name']">
				<tr>
					<th width="80">{pigcms{$config.group_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="have_group" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="have_group" value="0" /></label></span>
					</td>
				</tr>
			</if>
			            <tr>
                    <th width="160">是否开启商城实名认证</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$store['is_authentication'] eq 1">selected</if>"><span>开启</span><input type="radio" name="is_authentication" value="1" <if condition="$store['is_authentication'] eq 1">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$store['is_authentication'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="is_authentication" value="0" <if condition="$store['is_authentication'] eq 0">checked="checked"</if>/></label></span>
                        <em class="notice_tips" tips="开启后，用户在下单该店铺的所有商城商品时需要进过实名认证"></em>
                    </td>
                </tr>
			<if condition="$config['meal_alias_name']">
				<tr>
					<th width="80">{pigcms{$config.meal_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="have_meal" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="have_meal" value="0" /></label></span>
					</td>
				</tr>
			</if>
			<if condition="$config['shop_alias_name']">
				<tr <if condition="$config['single_system_type'] eq 'shop'">style="display:none;"</if>>
					<th width="80">{pigcms{$config.shop_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="have_shop" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="have_shop" value="0" /></label></span>
					</td>
				</tr>
			</if>
			<if condition="$config['store_open_waimai']">
				<tr>
					<th width="80">{pigcms{$config.waimai_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="have_waimai" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="have_waimai" value="0" /></label></span>
					</td>
				</tr>
			</if>
            <if condition="$config['mall_alias_name']">
                <th width="80">{pigcms{$config.mall_alias_name}功能</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="have_mall" value="1" checked="checked" /></label></span>
                    <span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="have_mall" value="0" /></label></span>
                </td>
                </tr>
            </if>
			<tr>
				<th width="80">店铺状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>
            <tr>
                <th width="80">是否可以使用一卡通支付</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="is_internal_card_pay" value="1"/></label></span>
                    <span class="cb-disable"><label class="cb-disable selected"><span>否</span><input type="radio" name="is_internal_card_pay" value="0" checked="checked"/></label></span>
                </td>
            </tr>
            <tr>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
<include file="Public:footer"/>