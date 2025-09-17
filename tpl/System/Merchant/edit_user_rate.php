<include file="Public:header"/>
	<form id="myform" frame="true" refresh="true">
		<ul class="tab_ul">
		   <!--<li class="active"><a data-toggle="tab" href="#tab_mer_pr">商家</a></li>-->
		   <!--<li><a data-toggle="tab" href="#tab_group_pr">团购</a></li>-->
			<if condition="$config['shop_alias_name']">
				<li class="active"><a data-toggle="tab" href="#tab_shop_pr" onclick="changeTab('shop')">{pigcms{$config.shop_alias_name}</a></li>
			</if>
			<if condition="$config['mall_alias_name']">
				<li class=""><a data-toggle="tab" href="#tab_mall_pr" onclick="changeTab('mall')">{pigcms{$config.mall_alias_name}</a></li>
			</if>
            <if condition="$config['group_alias_name']">
                <li class=""><a data-toggle="tab" href="#tab_group_pr" onclick="changeTab('group')">{pigcms{$config.group_alias_name}</a></li>
            </if>
			<if condition="$config['meal_alias_name']">
				<li><a data-toggle="tab" href="#tab_meal_pr" onclick="changeTab('meal')">{pigcms{$config.meal_alias_name}</a></li>
			</if>
		      <!--<li><a data-toggle="tab" href="#tab_appoint_pr">预约</a></li>-->
			<if condition="$config['is_cashier'] OR $config['pay_in_store']">
				<li><a data-toggle="tab" href="#tab_store_pr" onclick="changeTab('store')">{pigcms{$config.cash_alias_name}</a></li>
				<li><a data-toggle="tab" href="#tab_cash_pr" onclick="changeTab('cash')">到店支付</a></li>
			</if>
			<if condition="$config['open_sub_card'] ">
				<li><a data-toggle="tab" href="#tab_sub_card_pr" onclick="changeTab('sub_card')">免单套餐</a></li>
				<li><a data-toggle="tab" href="#tab_yuedan_pr" onclick="changeTab('sub_card')">约单</a></li>
			</if>
		      <!--<li><a data-toggle="tab" href="#tab_wxapp_pr">营销</a></li>-->
		      <!--<li><a data-toggle="tab" href="#tab_weidian_pr">微店</a></li>-->
		      <!--<li><a data-toggle="tab" href="#tab_activity_pr">平台活动活动</a></li>-->
		</ul>
		  <!--shop-->
		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_shop_pr" style="display: none;">
		   <tbody>
		
			<tr>
			 <th width="160">直接推广用户分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['shop_first_rate']>=0&&$mer_pr['shop_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['shop_first_rate']>=0&&$mer_pr['shop_first_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['shop_first_rate']<0||$mer_pr['shop_first_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['shop_first_rate']<0||$mer_pr['shop_first_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
					
					<input type="text" class="input-text" name="shop_first_rate" id="shop_first_rate" value="{pigcms{$mer_pr['shop_first_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['shop_first_rate']<0||$mer_pr['shop_first_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上级分佣比例：</th>
			 <td>	
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['shop_second_rate']>=0&&$mer_pr['shop_second_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['shop_second_rate']>=0&&$mer_pr['shop_second_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['shop_second_rate']<0||$mer_pr['shop_second_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['shop_second_rate']<0||$mer_pr['shop_second_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				<input type="text" class="input-text" name="shop_second_rate" id="config_shop_second_rate" value="{pigcms{$mer_pr['shop_second_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['shop_second_rate']<0||$mer_pr['shop_second_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['shop_third_rate']>=0&&$mer_pr['shop_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['shop_third_rate']>=0&&$mer_pr['shop_third_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['shop_third_rate']<0||$mer_pr['shop_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['shop_third_rate']<0||$mer_pr['shop_third_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			 <input type="text" class="input-text" name="shop_third_rate" id="config_shop_third_rate" value="{pigcms{$mer_pr['shop_third_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['shop_third_rate']<0||$mer_pr['shop_third_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
		
		   </tbody>
		  </table> 
		  <!--mall-->
		<table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_mall_pr" style="display: none;">
			<tbody>
				<tr>
					<th width="160">直接推广用户分佣比例：</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['mall_first_rate']>=0&&$mer_pr['mall_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['mall_first_rate']>=0&&$mer_pr['mall_first_rate']!=""){</php>checked="checked"<php>}</php>/>
							</label>
						</span>	     
						<span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['mall_first_rate']<0||$mer_pr['mall_first_rate']==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if($mer_pr['mall_first_rate']<0||$mer_pr['mall_first_rate']==""){</php>checked="checked"<php>}</php>/>
							</label>
						</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="text" class="input-text" name="mall_first_rate" id="config_mall_first_rate" value="{pigcms{$mer_pr['mall_first_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['mall_first_rate']<0||$mer_pr['mall_first_rate']==""){</php>style="display:none"<php>}</php>/>
					</td>
				</tr>
				<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
					<th width="160">直接推广用户上级分佣比例：</th>
					<td>	
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['mall_second_rate']>=0&&$mer_pr['mall_second_rate']!=""){</php>selected<php>}</php>">
								<span>设置</span>
								<input type="radio" name="open_rate" value="1" <php>if($mer_pr['mall_second_rate']>=0&&$mer_pr['mall_second_rate']!=""){</php>checked="checked"<php>}</php>/>
							</label>
						</span>			     
						<span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['mall_second_rate']<0||$mer_pr['mall_second_rate']==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if($mer_pr['mall_second_rate']<0||$mer_pr['mall_second_rate']==""){</php>checked="checked"<php>}</php>/>
							</label>
						</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="text" class="input-text" name="mall_second_rate" id="config_mall_second_rate" value="{pigcms{$mer_pr['mall_second_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['mall_second_rate']<0||$mer_pr['mall_second_rate']==""){</php>style="display:none"<php>}</php>/>
					</td>
				</tr>
				<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
					<th width="160">直接推广用户上上级分佣比例：</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['mall_third_rate']>=0&&$mer_pr['mall_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['mall_third_rate']>=0&&$mer_pr['mall_third_rate']!=""){</php>checked="checked"<php>}</php>/>
							</label>
						</span>			     
						<span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['mall_third_rate']<0||$mer_pr['mall_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['mall_third_rate']<0||$mer_pr['mall_third_rate']==""){</php>checked="checked"<php>}</php>/>
							</label>
						</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

						<input type="text" class="input-text" name="mall_third_rate" id="config_mall_third_rate" value="{pigcms{$mer_pr['mall_third_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['mall_third_rate']<0||$mer_pr['mall_third_rate']==""){</php>style="display:none"<php>}</php>/>
					</td>
				</tr>
			</tbody>
		</table>
        <!--group-->
        <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_group_pr" style="display: none;">
            <tbody>
            <tr>
                <th width="160">直接推广用户分佣比例：</th>
                <td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['group_first_rate']>=0&&$mer_pr['group_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['group_first_rate']>=0&&$mer_pr['group_first_rate']!=""){</php>checked="checked"<php>}</php>/>
                            </label>
						</span>
                    <span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['group_first_rate']<0||$mer_pr['group_first_rate']==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if($mer_pr['group_first_rate']<0||$mer_pr['group_first_rate']==""){</php>checked="checked"<php>}</php>/>
                        </label>
						</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                    <input type="text" class="input-text" name="group_first_rate" id="config_group_first_rate" value="{pigcms{$mer_pr['group_first_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['group_first_rate']<0||$mer_pr['group_first_rate']==""){</php>style="display:none"<php>}</php>/>
                </td>
            </tr>
            <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
            <th width="160">直接推广用户上级分佣比例：</th>
            <td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['group_second_rate']>=0&&$mer_pr['group_second_rate']!=""){</php>selected<php>}</php>">
								<span>设置</span>
								<input type="radio" name="open_rate" value="1" <php>if($mer_pr['group_second_rate']>=0&&$mer_pr['group_second_rate']!=""){</php>checked="checked"<php>}</php>/>
                            </label>
						</span>
                <span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['group_second_rate']<0||$mer_pr['group_second_rate']==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if($mer_pr['group_second_rate']<0||$mer_pr['group_second_rate']==""){</php>checked="checked"<php>}</php>/>
                    </label>
						</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <input type="text" class="input-text" name="group_second_rate" id="config_group_second_rate" value="{pigcms{$mer_pr['group_second_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['group_second_rate']<0||$mer_pr['group_second_rate']==""){</php>style="display:none"<php>}</php>/>
            </td>
            </tr>
            <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
            <th width="160">直接推广用户上上级分佣比例：</th>
            <td>
						<span class="cb-enable">
							<label class="cb-enable  <php>if($mer_pr['group_third_rate']>=0&&$mer_pr['group_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['group_third_rate']>=0&&$mer_pr['group_third_rate']!=""){</php>checked="checked"<php>}</php>/>
                            </label>
						</span>
                <span class="cb-disable">
							<label class="cb-disable  <php>if($mer_pr['group_third_rate']<0||$mer_pr['group_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['group_third_rate']<0||$mer_pr['group_third_rate']==""){</php>checked="checked"<php>}</php>/>
                    </label>
						</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                <input type="text" class="input-text" name="group_third_rate" id="config_group_third_rate" value="{pigcms{$mer_pr['group_third_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['group_third_rate']<0||$mer_pr['group_third_rate']==""){</php>style="display:none"<php>}</php>/>
            </td>
            </tr>
            </tbody>
        </table>
		  <!--meal-->
		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_meal_pr" style="display: none;">
		   <tbody>
		
			<tr>
			 <th width="160">直接推广用户分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['meal_first_rate']>=0&&$mer_pr['meal_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['meal_first_rate']>=0&&$mer_pr['meal_first_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['meal_first_rate']<0||$mer_pr['meal_first_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['meal_first_rate']<0||$mer_pr['meal_first_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
			 
			 <input type="text" class="input-text" name="meal_first_rate" id="meal_first_rate" value="{pigcms{$mer_pr['meal_first_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['meal_first_rate']<0||$mer_pr['meal_first_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['meal_second_rate']>=0&&$mer_pr['meal_second_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['meal_second_rate']>=0&&$mer_pr['meal_second_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['meal_second_rate']<0||$mer_pr['meal_second_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['meal_second_rate']<0||$mer_pr['meal_second_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			 <input type="text" class="input-text" name="meal_second_rate" id="config_meal_second_rate" value="{pigcms{$mer_pr['meal_second_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['meal_second_rate']<0||$mer_pr['meal_second_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['meal_third_rate']>=0&&$mer_pr['meal_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['meal_third_rate']>=0&&$mer_pr['meal_third_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['meal_third_rate']<0||$mer_pr['meal_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['meal_third_rate']<0||$mer_pr['meal_third_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
					
			 <input type="text" class="input-text" name="meal_third_rate" id="config_meal_third_rate" value="{pigcms{$mer_pr['meal_third_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['meal_third_rate']<0||$mer_pr['meal_third_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
		
		   </tbody>
		  </table>
		  <!--store-->
		  <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_store_pr" style="display: none;">
		   <tbody>
			
			
			<tr>
			 <th width="160">直接推广用户分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['store_first_rate']>=0&&$mer_pr['store_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['store_first_rate']>=0&&$mer_pr['store_first_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['store_first_rate']<0||$mer_pr['store_first_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['store_first_rate']<0||$mer_pr['store_first_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			 <input type="text" class="input-text" name="store_first_rate" id="store_first_rate" value="{pigcms{$mer_pr['store_first_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['store_first_rate']<0||$mer_pr['store_first_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['store_second_rate']>=0&&$mer_pr['store_second_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['store_second_rate']>=0&&$mer_pr['store_second_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['store_second_rate']<0||$mer_pr['store_second_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['store_second_rate']<0||$mer_pr['store_second_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			 <input type="text" class="input-text" name="store_second_rate" id="config_store_second_rate" value="{pigcms{$mer_pr['store_second_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['store_second_rate']<0||$mer_pr['store_second_rate']==""){</php>style="display:none"<php>}</php> /></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['store_third_rate']>=0&&$mer_pr['store_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['store_third_rate']>=0&&$mer_pr['store_third_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['store_third_rate']<0||$mer_pr['store_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['store_third_rate']<0||$mer_pr['store_third_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
			 <input type="text" class="input-text" name="store_third_rate" id="config_store_third_rate" value="{pigcms{$mer_pr['store_third_rate']}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['store_third_rate']<0||$mer_pr['store_third_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			
		   </tbody>
		  </table>
		   <!--cash-->
		   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_cash_pr" style="display: none;">
		   <tbody>
			<tr>
			 <th width="160">直接推广用户分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['cash_first_rate']>=0&&$mer_pr['cash_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['cash_first_rate']>=0&&$mer_pr['cash_first_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['cash_first_rate']<0||$mer_pr['cash_first_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['cash_first_rate']<0||$mer_pr['cash_first_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
			 <input type="text" class="input-text" name="cash_first_rate" id="cash_first_rate" value="{pigcms{$mer_pr['cash_first_rate']}" size="10" validate="number:true,max:100" tips="到店消费用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['cash_first_rate']<0||$mer_pr['cash_first_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['cash_second_rate']>=0&&$mer_pr['cash_second_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['cash_second_rate']>=0&&$mer_pr['cash_second_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['cash_second_rate']<0||$mer_pr['cash_second_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['cash_second_rate']<0||$mer_pr['cash_second_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			 <input type="text" class="input-text" name="cash_second_rate" id="config_cash_second_rate" value="{pigcms{$mer_pr['cash_second_rate']}" size="10" validate="number:true,max:100" tips="到店消费用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）"  <php>if($mer_pr['cash_second_rate']<0||$mer_pr['cash_second_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['cash_third_rate']>=0&&$mer_pr['cash_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['cash_third_rate']>=0&&$mer_pr['cash_third_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['cash_third_rate']<0||$mer_pr['cash_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['cash_third_rate']<0||$mer_pr['cash_third_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
		
					
			 <input type="text" class="input-text" name="cash_third_rate" id="config_cash_third_rate" value="{pigcms{$mer_pr['cash_third_rate']}" size="10" validate="number:true,max:100" tips="到店消费用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['cash_third_rate']<0||$mer_pr['cash_third_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			
		   </tbody>
		  </table>
		  
		  <!--sub_card-->
		   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" id="tab_sub_card_pr" style="display: none;">
		   <tbody>
			<tr>
			 <th width="160">直接推广用户分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['sub_card_first_rate']>=0&&$mer_pr['sub_card_first_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['sub_card_first_rate']>=0&&$mer_pr['sub_card_first_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['sub_card_first_rate']<0||$mer_pr['sub_card_first_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['sub_card_first_rate']<0||$mer_pr['sub_card_first_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
			 <input type="text" class="input-text" name="sub_card_first_rate" id="sub_card_first_rate" value="{pigcms{$mer_pr['sub_card_first_rate']}" size="10" validate="number:true,max:100" tips="到店消费用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['sub_card_first_rate']<0||$mer_pr['sub_card_first_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['sub_card_second_rate']>=0&&$mer_pr['sub_card_second_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['sub_card_second_rate']>=0&&$mer_pr['sub_card_second_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['sub_card_second_rate']<0||$mer_pr['sub_card_second_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['sub_card_second_rate']<0||$mer_pr['sub_card_second_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			 <input type="text" class="input-text" name="sub_card_second_rate" id="config_sub_card_second_rate" value="{pigcms{$mer_pr['sub_card_second_rate']}" size="10" validate="number:true,max:100" tips="到店消费用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）"  <php>if($mer_pr['sub_card_second_rate']<0||$mer_pr['sub_card_second_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
			 <th width="160">直接推广用户上上级分佣比例：</th>
			 <td>
				<span class="cb-enable">
						<label class="cb-enable  <php>if($mer_pr['sub_card_third_rate']>=0&&$mer_pr['sub_card_third_rate']!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if($mer_pr['sub_card_third_rate']>=0&&$mer_pr['sub_card_third_rate']!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if($mer_pr['sub_card_third_rate']<0||$mer_pr['sub_card_third_rate']==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if($mer_pr['sub_card_third_rate']<0||$mer_pr['sub_card_third_rate']==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
		
					
			 <input type="text" class="input-text" name="sub_card_third_rate" id="config_sub_card_third_rate" value="{pigcms{$mer_pr['sub_card_third_rate']}" size="10" validate="number:true,max:100" tips="到店消费用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找系统的相关业务的用户分佣比例）" <php>if($mer_pr['sub_card_third_rate']<0||$mer_pr['sub_card_third_rate']==""){</php>style="display:none"<php>}</php>/></td>
			</tr>
			
		   </tbody>
		  </table>
              
            <!-- 一级分佣区间设置 -->
			<div class="table-list percent_div" <php>if(($mer_pr['shop_first_rate']<=0&&$mer_pr['shop_first_rate']=="")||$mer_pr['shop_first_rate']==-1){</php>style="display:none"<php>}</php>>
				<input type="hidden" class="input-text" name="use_spread_type" id="use_spread_type" value="shop" />
				<table width="100%" cellspacing="0">
					<colgroup><col> <col> <col> <col width="240" align="center"> </colgroup>
					<thead>
						<tr>
							<th>ID</th>
							<th>推广人数范围(<font color="red">请填写大于0的数字</font>)</th>
							<th>佣金比例(<font color="red">请填写0-100的数字</font>)</th>
							<th class="textcenter"><if condition="empty($_GET['type'])" >操作</if></th>
						</tr>
					</thead>
					<tbody id="percent_content">
						<if condition="$percent_detail">
							<volist name="percent_detail" id="vo">
								<tr class="plus">
									<td class="sort">{pigcms{$i}</td>
									<td>{pigcms{$vo.money_start} --- {pigcms{$vo.money_end}</td>
									<td><input class="input-text valid" type="text" name="money_percent[]" value="{pigcms{$detail[$i-1] ?: $vo['percent']}" ></td>
								</tr>
							</volist>
						</if>
					</tbody>
				</table>
			</div>
		<input type="hidden" name="mer_id" value="{pigcms{$mer_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			
		</table>
		<div class="btn ">
			<input  type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			
		</div>
	</form>
	<script>
		$(function(){
			$('#dosubmit').click(function(){
				$.post('{pigcms{:U('Merchant/edit_user_rate')}', $('#myform').serialize(), function(data, textStatus, xhr) {
					if(data.status==1){
						window.top.msg(2,data.info,true,2);
					}else{
						window.top.msg(0,data.info,true,2);
					}
					window.top.art.dialog({id:'edit_user_rate'}).close();
				});
			});
			
			
			var data_rate_arr = [];
			var test = $('.input-text').each(function(index,val){
				var ids = $(val).attr('id');
				data_rate_arr[ids] = $(val).val()
				
			});
			
			
			$('.input-text').blur(function(){
				$('.input-text').each(function(index,val){
					var ids = $(val).attr('id');
					data_rate_arr[ids] = $(val).val()
					console.log(data_rate_arr)
				});
			})

			
			$('input:radio').click(function(){
				var percent = $(this).parents('td').find('input[type="text"]');
				var text_id = percent.attr('id');
				var	open_percent = data_rate_arr[text_id];
				if($(this).val()==1){
					if(open_percent<0){
						percent.val('');
					}else{
						percent.val(open_percent);
					}
					percent.show();
					if(text_id.indexOf('first') != -1){
						$('.percent_div').show();
					}
				}else{
					if(text_id.indexOf('first') != -1){
						$('.percent_div').hide();
					}
					percent.hide();
					percent.val(-1);
					percent.hide();
				}
			});
			
			$('.table_form:eq(0)').show();
			
			$('.tab_ul li a').click(function(){
				$(this).closest('li').addClass('active').siblings('li').removeClass('active');
				$($(this).attr('href')).show().siblings('.table_form').hide();
				return false;
			});
			$('#im_key').click(function(){
				window.top.msg(2,'正在请求中,请稍等...',true,100);
				$.get("{pigcms{:U('Config/im')}",function(data){
					if(data.error_code){
						window.top.msg(0,data.msg,true,3);
					}else{
						window.top.msg(1,data.msg,true,3);
					}
				},'json');
			});
			$('#live_service_key').click(function(){
				window.top.msg(2,'正在请求中,请稍等...',true,100);
				$.get("{pigcms{:U('Config/live_service')}",function(data){
					if(data.error_code){
						window.top.msg(0,data.msg,true,3);
					}else{
						window.top.msg(1,data.msg,true,3);
					}
				},'json');
			});
			
	
			
		});
	</script>
	<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
	<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
	<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
	<script type="text/javascript">
		KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.config_upload_image_btn').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
				editor.loadPlugin('image', function(){
					editor.plugin.imageDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-image').val(site_url+url);
							editor.hideDialog();
						}
					});
				});
			});
			$('.config_upload_file_btn').click(function(){
				var upload_file_btn = $(this);
				editor.uploadJson = "{pigcms{:U('Config/ajax_upload_file')}&name="+upload_file_btn.siblings('.input-file').attr('name');
				editor.loadPlugin('insertfile', function(){
					editor.plugin.fileDialog({
						showRemote : false,
						clickFn : function(url, title, width, height, border, align) {
							upload_file_btn.siblings('.input-file').val(url);
							editor.hideDialog();
						}
					});
				});
			});
		});

		function changeTab(type='shop'){

			if($('input[name='+type+'_first_rate]').val() == '' || $('input[name='+type+'_first_rate]').val() <= '0'){
				$('.percent_div').hide();
			}else{
				$('.percent_div').show();
			}

			$.post('{pigcms{:U("ajax_get_user_rate")}',{type:type,mer_id:'{pigcms{$_GET["mer_id"]}'},function(data){
				var html = ''
				$('#use_spread_type').val(type)
				if(data.percent_detail.length > 0){
					for( var i=0; i <=data.percent_detail.length-1; i++){
						var item = data.percent_detail[i]
						if(type){
							var percent = item.percent
							if(data.detail){
								percent = data.detail[i] ? data.detail[i] : item.percent
							}
							html += '<tr class="plus">\
									<td class="sort">'+i+'</td>\
									<td>'+item.money_start+' --- '+item.money_end+'</td>\
									<td><input class="input-text valid" type="text" name="money_percent[]" value="'+percent+'" ></td>\
									<td class="textcenter" ><a href="javascript:void(0);" class="delete" parameter="id='+item.id+'" url="" onclick="del(this)">删除</a></td>\
								</tr>'

						}else{
							html += '<tr class="plus">\
									<td class="sort">'+i+'</td>\
									<td><input class="input-text valid" type="text" name="money_start[]" value="'+item.money_start+'" >--- <input  class="input-text valid" type="text" name="money_end[]" value="'+item.money_end+'"></td>\
									<td><input class="input-text valid" type="text" name="money_percent[]" value="'+item.percent+'" ></td>\
									<td class="textcenter" ><a href="javascript:void(0);" class="delete" parameter="id='+item.id+'" url="" onclick="del(this)">删除</a></td>\
								</tr>'

						}

					}
				}
				$('#percent_content').html(html)
			},'json')
		}
	</script>
	
	<style>
		.table_form{border:1px solid #ddd;}
		.tab_ul{margin-top:10px;border-color:#C5D0DC;margin-bottom:0!important;margin-left:0;position:relative;top:1px;border-bottom:1px solid #ddd;padding-left:0;list-style:none;}
		.tab_ul>li{position:relative;display:block;float:left;margin-bottom:-1px;}
		.tab_ul>li>a {
			position: relative;
			display: block;
			padding: 10px 15px;
			margin-right: 2px;
			line-height: 1.42857143;
			border: 1px solid transparent;
			border-radius: 4px 4px 0 0;
			padding: 7px 12px 8px;
			min-width: 50px;
			text-align: center;
		}
		.tab_ul>li>a, .tab_ul>li>a:focus {
			border-radius: 0!important;
			border-color: #c5d0dc;
			background-color: #F9F9F9;
			color: #999;
			margin-right: -1px;
			line-height: 18px;
			position: relative;
		}
		.tab_ul>li>a:focus, .tab_ul>li>a:hover {
			text-decoration: none;
			background-color: #eee;
		}
		.tab_ul>li>a:hover {
			border-color: #eee #eee #ddd;
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #555;
			background-color: #fff;
			border: 1px solid #ddd;
			border-bottom-color: transparent;
			cursor: default;
		}
		.tab_ul>li>a:hover {
			background-color: #FFF;
			color: #4c8fbd;
			border-color: #c5d0dc;
		}
		.tab_ul>li:first-child>a {
			margin-left: 0;
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #576373;
			border-color: #c5d0dc #c5d0dc transparent;
			border-top: 2px solid #4c8fbd;
			background-color: #FFF;
			z-index: 1;
			line-height: 18px;
			margin-top: -1px;
			box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #555;
			background-color: #fff;
			border: 1px solid #ddd;
			border-bottom-color: transparent;
			cursor: default;
		}
		.tab_ul>li.active>a, .tab_ul>li.active>a:focus, .tab_ul>li.active>a:hover {
			color: #576373;
			border-color: #c5d0dc #c5d0dc transparent;
			border-top: 2px solid #4c8fbd;
			background-color: #FFF;
			z-index: 1;
			line-height: 18px;
			margin-top: -1px;
			box-shadow: 0 -2px 3px 0 rgba(0,0,0,.15);
		}
		.tab_ul:before,.tab_ul:after{
			content: " ";
			display: table;
		}
		.tab_ul:after{
			clear: both;
		}
	</style>
<include file="Public:footer"/>