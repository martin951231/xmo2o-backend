<include file="Public:header"/>
		<div class="mainbox">
			<div id="nav" class="mainnav_title">
				<ul>
					<a href="{pigcms{:U('Percent/user_rate')}" class="on">用户推广分佣设置</a>
					<a href="{pigcms{:U('Percent/rate')}">商家推广分佣设置</a>	
				</ul>
			</div>
			<form id="myform" method="post" action="/admin.php?g=System&c=Config&a=amend" refresh="true"> 
				<ul class="tab_ul">
					<li class="active"><a data-toggle="tab" href="#tab_system_pr" onclick="changeTab()">平台</a></li>
					<if condition="$config['open_pufa_pay'] eq '1'">
					<li><a data-toggle="tab" href="#tab_recharge_pr" onclick="changeTab('recharge')">平台充值</a></li>
					</if>
					<if condition="$config['group_alias_name']">
						<li><a data-toggle="tab" href="#tab_group_pr" onclick="changeTab('group')">{pigcms{$config.group_alias_name}</a></li>
					</if>
					<if condition="$config['shop_alias_name']">
						<li><a data-toggle="tab" href="#tab_shop_pr" onclick="changeTab('shop')">{pigcms{$config.shop_alias_name}</a></li>
					</if>
					<if condition="$config['mall_alias_name']">
						<li><a data-toggle="tab" href="#tab_mall_pr" onclick="changeTab('mall')">{pigcms{$config.mall_alias_name}</a></li>
					</if>
					<if condition="$config['meal_alias_name']">
						<li><a data-toggle="tab" href="#tab_meal_pr" onclick="changeTab('meal')">{pigcms{$config.meal_alias_name}</a></li>
					</if>
					<if condition="$config['is_cashier'] OR $config['pay_in_store']">
						<li><a data-toggle="tab" href="#tab_store_pr" onclick="changeTab('store')">{pigcms{$config.cash_alias_name}</a></li>
						<li><a data-toggle="tab" href="#tab_cash_pr" onclick="changeTab('cash')">到店消费</a></li>
					</if>
					<if condition="$config['open_sub_card'] ">
						<li><a data-toggle="tab" href="#tab_sub_card_pr" onclick="changeTab('sub_card')">免单套餐</a></li>
						<li><a data-toggle="tab" href="#tab_yuedan_pr" onclick="changeTab('yuedan')">约单</a></li>
					</if>
                    <if condition="$config['classify_name']">
                        <li><a data-toggle="tab" href="#tab_classifynew_pr" onclick="changeTab('classifynew')">{pigcms{$config.classify_name}</a></li>
                    </if>
                    <li><a data-toggle="tab" href="#tab_user_level" onclick="changeTab('user_level')">平台会员</a></li>
				</ul> 
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%"  id="tab_system_pr">
				<tbody>
					<tr><td colspan="2" style="color:red">
						推广用户分享获得平台佣金比例设置
					</td></tr>
					<tr>
						<th width="160">直接推广用户分享<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>比例：</th>
						<td>
						<input type="text" class="input-text" name="user_spread_rate" id="$config_user_spread_rate" value="{pigcms{:C('config.user_spread_rate')}" size="10" validate="required:true,number:true,range:[0,100]" tips="用户分享链接购买商品获得<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>比例（百分比 0-100）微信和小程序分享有效！"  ></td>
					</tr>
					<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
						<th width="160">直接推广用户的上级分享<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>比例：</th>
						<td>
						<input type="text" class="input-text" name="user_first_spread_rate" id="$config_user_first_spread_rate" value="{pigcms{:C('config.user_first_spread_rate')}" size="10" validate="required:true,number:true,range:[0,100]" tips="直接推广用户的上级分享链接购买商品获得<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>比例（百分比 0-100）微信和小程序分享有效！如果此值+用户分享的<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>值和大于100，则取100减去用户分享的<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>值" ></td>
					</tr>
					 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
					  	<th width="160">直接推广用户的上上级分享<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>比例：</th>
					  	<td>
					  		<input type="text" class="input-text" name="user_second_spread_rate" id="$config_user_second_spread_rate" value="{pigcms{:C('config.user_second_spread_rate')}" size="10" validate="required:true,number:true,range:[0,100]" tips="直接推广用户的上上级分享链接购买商品获得<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>比例（百分比 0-100）微信和小程序分享有效！如果此值+用户分享的<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>值和大于100，则取100减去用户分享的<if condition="$config.open_extra_price eq 1">{pigcms{$config.score_name}<else />佣金</if>值" />
					  	</td>
					 </tr>
					 <tr>
					  	<th width="160">抽成/分佣计算方式：</th>
						 <td>
							<span class="cb-enable">
								<label class="cb-enable  <php>if(C('config.add_spread_by_system_commission')>0&&C('config.add_spread_by_system_commission')!=""){</php>selected<php>}</php>">
									<span>平台抽成结算</span>
									<input type="radio" name="add_spread_by_system_commission" value="1" <php>if(C('config.add_spread_by_system_commission')>0&&C('config.add_spread_by_system_commission')!=""){</php>checked="checked"<php>}</php>/>
								</label>
							</span>			     
							<span class="cb-disable">
								<label class="cb-disable  <php>if(C('config.add_spread_by_system_commission')<=0||C('config.add_spread_by_system_commission')==""){</php>selected<php>}</php>">
									<span>订单金额结算</span>
									<input type="radio" name="add_spread_by_system_commission" value="0" <php>if(C('config.add_spread_by_system_commission')<=0||C('config.add_spread_by_system_commission')==""){</php>checked="checked"<php>}</php>/>
								</label>
							</span>
					  	</td>
					 </tr>
				 	<eq name='config.open_jifen_back' value='1'>
					 	<tr><td colspan="2" style="color:red">
							推广用户分享获得平台积分比例设置（只支持这一总设置，不支持其他单独业务设置）
						</td></tr>
						<tr>
							<th width="160">
								直接推广用户分享{pigcms{$config.score_name}比例：
							</th>
							<td>
								<input type="text" class="input-text" name="first_jifen_percent" id="$config_first_jifen_percent" value="{pigcms{:C('config.first_jifen_percent')}" size="10" validate="required:true,number:true,range:[0,100]" tips="用户分享链接购买商品获得{pigcms{$config.score_name}比例（百分比 0-100）微信和小程序分享有效！"  />
							</td>
						</tr>
						<tr>
							<th width="160">
								直接推广用户的上级分享{pigcms{$config.score_name}比例：
							</th>
							<td>
								<input type="text" class="input-text" name="second_jifen_percent" id="$config_second_jifen_percent" value="{pigcms{:C('config.second_jifen_percent')}" size="10" validate="required:true,number:true,range:[0,100]" tips="用户分享链接购买商品获得{pigcms{$config.score_name}比例（百分比 0-100）微信和小程序分享有效！"  />
							</td>
						</tr>
						<tr>
							<th width="160">
								直接推广用户的上上级分享{pigcms{$config.score_name}比例：
							</th>
							<td>
								<input type="text" class="input-text" name="third_jifen_percent" id="$config_third_jifen_percent" value="{pigcms{:C('config.third_jifen_percent')}" size="10" validate="required:true,number:true,range:[0,100]" tips="用户分享链接购买商品获得{pigcms{$config.score_name}比例（百分比 0-100）微信和小程序分享有效！"  />
							</td>
						</tr>
					</eq>
				</tbody>
			   </table>
			   <!--充值-->
			   
		
			   <if condition="$config['open_pufa_pay'] eq '1'">
			    <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_recharge_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.recharge_spread_rate')>=0&&C('config.recharge_spread_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.recharge_spread_rate')>=0&&C('config.recharge_spread_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.recharge_spread_rate')<0||C('config.recharge_spread_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.recharge_spread_rate')<0||C('config.recharge_spread_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			
				  <input type="text" class="input-text" name="recharge_spread_rate" id="config_recharge_spread_rate" value="{pigcms{:C('config.recharge_spread_rate')}" size="10" validate="number:true,max:100" tips="平台团购用户推广分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.recharge_spread_rate')<0||C('config.recharge_spread_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr >
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.recharge_first_spread_rate')>=0&&C('config.recharge_first_spread_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.recharge_first_spread_rate')>=0&&C('config.recharge_first_spread_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.recharge_first_spread_rate')<0||C('config.recharge_first_spread_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.recharge_first_spread_rate')<0||C('config.recharge_first_spread_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
					
				  <input type="text" class="input-text" name="recharge_first_spread_rate" id="config_recharge_first_spread_rate" value="{pigcms{:C('config.recharge_first_spread_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.recharge_first_spread_rate')<0||C('config.recharge_first_spread_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr >
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.recharge_second_spread_rate')>=0&&C('config.recharge_second_spread_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.recharge_second_spread_rate')>=0&&C('config.recharge_second_spread_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.recharge_second_spread_rate')<0||C('config.recharge_second_spread_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.recharge_second_spread_rate')<0||C('config.recharge_second_spread_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
				  <input type="text" class="input-text" name="recharge_second_spread_rate" id="config_recharge_second_spread_rate" value="{pigcms{:C('config.recharge_second_spread_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.recharge_second_spread_rate')<0||C('config.recharge_second_spread_rate')==""){</php>style="display:none"<php>}</php> /></td>
				 </tr>
				</tbody>
				
			   </table>
				</if>
			   
			   
			   <!--团购-->
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_group_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.group_first_rate')>=0&&C('config.group_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.group_first_rate')>=0&&C('config.group_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.group_first_rate')<0||C('config.group_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.group_first_rate')<0||C('config.group_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
			
				  <input type="text" class="input-text" name="group_first_rate" id="$config_group_first_rate" value="{pigcms{:C('config.group_first_rate')}" size="10" validate="number:true,max:100" tips="平台团购用户推广分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.group_first_rate')<0||C('config.group_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.group_second_rate')>=0&&C('config.group_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.group_second_rate')>=0&&C('config.group_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.group_second_rate')<0||C('config.group_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.group_second_rate')<0||C('config.group_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
					
				  <input type="text" class="input-text" name="group_second_rate" id="$config_group_second_rate" value="{pigcms{:C('config.group_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.group_second_rate')<0||C('config.group_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.group_third_rate')>=0&&C('config.group_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.group_third_rate')>=0&&C('config.group_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.group_third_rate')<0||C('config.group_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.group_third_rate')<0||C('config.group_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
				  <input type="text" class="input-text" name="group_third_rate" id="$config_group_third_rate" value="{pigcms{:C('config.group_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.group_third_rate')<0||C('config.group_third_rate')==""){</php>style="display:none"<php>}</php> /></td>
				 </tr>
				</tbody>
				
				</table>
				<!--快店-->
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_shop_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.shop_first_rate')>=0&&C('config.shop_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.shop_first_rate')>=0&&C('config.shop_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.shop_first_rate')<0||C('config.shop_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.shop_first_rate')<0||C('config.shop_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
					
				  <input type="text" class="input-text" name="shop_first_rate" id="$config_shop_first_rate" value="{pigcms{:C('config.shop_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.shop_first_rate')<0||C('config.shop_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.shop_second_rate')>=0&&C('config.shop_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.shop_second_rate')>=0&&C('config.shop_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.shop_second_rate')<0||C('config.shop_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.shop_second_rate')<0||C('config.shop_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
					
				  <input type="text" class="input-text" name="shop_second_rate" id="$config_shop_second_rate" value="{pigcms{:C('config.shop_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.shop_second_rate')<0||C('config.shop_second_rate')==""){</php>style="display:none"<php>}</php> /></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.shop_third_rate')>=0&&C('config.shop_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.shop_third_rate')>=0&&C('config.shop_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.shop_third_rate')<0||C('config.shop_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.shop_third_rate')<0||C('config.shop_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			
					
				  <input type="text" class="input-text" name="shop_third_rate" id="$config_shop_third_rate" value="{pigcms{:C('config.shop_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.shop_third_rate')<0||C('config.shop_third_rate')==""){</php>style="display:none"<php>}</php> /></td>
				 </tr>
				</tbody>
			   </table>
			   	<!--商城-->
			   	<table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_mall_pr">
					<tbody>
						<tr>
							<th width="160">直接推广用户分佣比例：</th>
							<td>
								<span class="cb-enable">
								<label class="cb-enable  <php>if(C('config.mall_first_rate')>=0&&C('config.mall_first_rate')!=""){</php>selected<php>}</php>">
								<span>设置</span>
								<input type="radio" name="open_rate" value="1" <php>if(C('config.mall_first_rate')>=0&&C('config.mall_first_rate')!=""){</php>checked="checked"<php>}</php>/>
								</label>
								</span>			     
								<span class="cb-disable">
								<label class="cb-disable  <php>if(C('config.mall_first_rate')<0||C('config.mall_first_rate')==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if(C('config.mall_first_rate')<0||C('config.mall_first_rate')==""){</php>checked="checked"<php>}</php>/>
								</label>
								</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<input type="text" class="input-text" name="mall_first_rate" id="$config_mall_first_rate" value="{pigcms{:C('config.mall_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.mall_first_rate')<0||C('config.mall_first_rate')==""){</php>style="display:none"<php>}</php>/>
							</td>
						</tr>
						<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
							<th width="160">直接推广用户上级分佣比例：</th>
							<td>
								<span class="cb-enable">
								<label class="cb-enable  <php>if(C('config.mall_second_rate')>=0&&C('config.mall_second_rate')!=""){</php>selected<php>}</php>">
								<span>设置</span>
								<input type="radio" name="open_rate" value="1" <php>if(C('config.mall_second_rate')>=0&&C('config.mall_second_rate')!=""){</php>checked="checked"<php>}</php>/>
								</label>
								</span>			     
								<span class="cb-disable">
								<label class="cb-disable  <php>if(C('config.mall_second_rate')<0||C('config.mall_second_rate')==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if(C('config.mall_second_rate')<0||C('config.mall_second_rate')==""){</php>checked="checked"<php>}</php>/>
								</label>
								</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

								<input type="text" class="input-text" name="mall_second_rate" id="$config_mall_second_rate" value="{pigcms{:C('config.mall_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.mall_second_rate')<0||C('config.mall_second_rate')==""){</php>style="display:none"<php>}</php> />
							</td>
						</tr>
						<tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
							<th width="160">直接推广用户上上级分佣比例：</th>
							<td>
								<span class="cb-enable">
								<label class="cb-enable  <php>if(C('config.mall_third_rate')>=0&&C('config.mall_third_rate')!=""){</php>selected<php>}</php>">
								<span>设置</span>
								<input type="radio" name="open_rate" value="1" <php>if(C('config.mall_third_rate')>=0&&C('config.mall_third_rate')!=""){</php>checked="checked"<php>}</php>/>
								</label>
								</span>			     
								<span class="cb-disable">
								<label class="cb-disable  <php>if(C('config.mall_third_rate')<0||C('config.mall_third_rate')==""){</php>selected<php>}</php>">
								<span>跳过</span>
								<input type="radio" name="open_rate" value="0" <php>if(C('config.mall_third_rate')<0||C('config.mall_third_rate')==""){</php>checked="checked"<php>}</php>/>
								</label>
								</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
								<input type="text" class="input-text" name="mall_third_rate" id="$config_mall_third_rate" value="{pigcms{:C('config.mall_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.mall_third_rate')<0||C('config.mall_third_rate')==""){</php>style="display:none"<php>}</php> />
							</td>
						</tr>
					</tbody>
			   	</table>
			    <!--餐饮买单-->
				
				<table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_meal_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
				  <span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.meal_first_rate')>=0&&C('config.meal_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.meal_first_rate')>=0&&C('config.meal_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.meal_first_rate')<0||C('config.meal_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.meal_first_rate')<0||C('config.meal_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
				  <input type="text" class="input-text" name="meal_first_rate" id="$config_meal_first_rate" value="{pigcms{:C('config.meal_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.meal_first_rate')<0||C('config.meal_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>	
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.meal_second_rate')>=0&&C('config.meal_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.meal_second_rate')>=0&&C('config.meal_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.meal_second_rate')<0||C('config.meal_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.meal_second_rate')<0||C('config.meal_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
					
					
				  <input type="text" class="input-text" name="meal_second_rate" id="$config_meal_second_rate" value="{pigcms{:C('config.meal_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.meal_second_rate')<0||C('config.meal_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.meal_third_rate')>=0&&C('config.meal_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.meal_third_rate')>=0&&C('config.meal_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.meal_third_rate')<0||C('config.meal_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.meal_third_rate')<0||C('config.meal_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				  <input type="text" class="input-text" name="meal_third_rate" id="$config_meal_third_rate" value="{pigcms{:C('config.meal_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.meal_third_rate')<0||C('config.meal_third_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				</tbody>
			   </table>
			    <!--{pigcms{$config.cash_alias_name}-->
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_store_pr">
				<tbody>
				 <tr >
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.store_first_rate')>=0&&C('config.store_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.store_first_rate')>=0&&C('config.store_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.store_first_rate')<0||C('config.store_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.store_first_rate')<0||C('config.store_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
					
				  <input type="text" class="input-text" name="store_first_rate" id="$config_store_first_rate" value="{pigcms{:C('config.store_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.store_first_rate')<0||C('config.store_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.store_second_rate')>=0&&C('config.store_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.store_second_rate')>=0&&C('config.store_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.store_second_rate')<0||C('config.store_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.store_second_rate')<0||C('config.store_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
					
				  <input type="text" class="input-text" name="store_second_rate" id="$config_store_second_rate" value="{pigcms{:C('config.store_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.store_second_rate')<0||C('config.store_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
				  <span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.store_third_rate')>=0&&C('config.store_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.store_third_rate')>=0&&C('config.store_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.store_third_rate')<0||C('config.store_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.store_third_rate')<0||C('config.store_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
					
				  
				  <input type="text" class="input-text" name="store_third_rate" id="$config_store_third_rate" value="{pigcms{:C('config.store_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.store_third_rate')<0||C('config.store_third_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				</tbody>
				
			   </table>
				 <!--到店消费-->
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_cash_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.cash_first_rate')>=0&&C('config.cash_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.cash_first_rate')>=0&&C('config.cash_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.cash_first_rate')<0||C('config.cash_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.cash_first_rate')<0||C('config.cash_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
					
				  <input type="text" class="input-text" name="cash_first_rate" id="$config_cash_first_rate" value="{pigcms{:C('config.cash_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.cash_first_rate')<0||C('config.cash_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.cash_second_rate')>=0&&C('config.cash_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.cash_second_rate')>=0&&C('config.cash_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.cash_second_rate')<0||C('config.cash_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.cash_second_rate')<0||C('config.cash_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			
					
				  <input type="text" class="input-text" name="cash_second_rate" id="$config_cash_second_rate" value="{pigcms{:C('config.cash_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.cash_second_rate')<0||C('config.cash_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.cash_third_rate')>=0&&C('config.cash_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.cash_third_rate')>=0&&C('config.cash_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.cash_third_rate')<0||C('config.cash_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.cash_third_rate')<0||C('config.cash_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
				  <input type="text" class="input-text" name="cash_third_rate" id="$config_cash_third_rate" value="{pigcms{:C('config.cash_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.cash_third_rate')<0||C('config.cash_third_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				</tbody>
			   </table> 
			   
			   	 <!--免单套餐消费-->
			   <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_sub_card_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.sub_card_first_rate')>=0&&C('config.sub_card_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.sub_card_first_rate')>=0&&C('config.sub_card_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.sub_card_first_rate')<0||C('config.sub_card_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.sub_card_first_rate')<0||C('config.sub_card_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
					
				  <input type="text" class="input-text" name="sub_card_first_rate" id="$config_sub_card_first_rate" value="{pigcms{:C('config.sub_card_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.sub_card_first_rate')<0||C('config.sub_card_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.sub_card_second_rate')>=0&&C('config.sub_card_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.sub_card_second_rate')>=0&&C('config.sub_card_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.sub_card_second_rate')<0||C('config.sub_card_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.sub_card_second_rate')<0||C('config.sub_card_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			
					
				  <input type="text" class="input-text" name="sub_card_second_rate" id="$config_sub_card_second_rate" value="{pigcms{:C('config.sub_card_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.sub_card_second_rate')<0||C('config.sub_card_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.sub_card_third_rate')>=0&&C('config.sub_card_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.sub_card_third_rate')>=0&&C('config.sub_card_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.sub_card_third_rate')<0||C('config.sub_card_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.sub_card_third_rate')<0||C('config.sub_card_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
				  <input type="text" class="input-text" name="sub_card_third_rate" id="$config_sub_card_third_rate" value="{pigcms{:C('config.sub_card_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.sub_card_third_rate')<0||C('config.sub_card_third_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				</tbody>
			   </table> 
			   
			   <!--约单-->
			   
			    <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_yuedan_pr">
				<tbody>
				 <tr>
				  <th width="160">直接推广用户分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.yuedan_first_rate')>=0&&C('config.yuedan_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.yuedan_first_rate')>=0&&C('config.yuedan_first_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.yuedan_first_rate')<0||C('config.yuedan_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.yuedan_first_rate')<0||C('config.yuedan_first_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
				
					
				  <input type="text" class="input-text" name="yuedan_first_rate" id="$config_sub_card_first_rate" value="{pigcms{:C('config.yuedan_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.yuedan_first_rate')<0||C('config.yuedan_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.yuedan_second_rate')>=0&&C('config.yuedan_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.yuedan_second_rate')>=0&&C('config.yuedan_second_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.yuedan_second_rate')<0||C('config.yuedan_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.yuedan_second_rate')<0||C('config.yuedan_second_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			
					
				  <input type="text" class="input-text" name="yuedan_second_rate" id="$config_sub_card_second_rate" value="{pigcms{:C('config.yuedan_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.yuedan_second_rate')<0||C('config.yuedan_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				 <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
				  <th width="160">直接推广用户上上级分佣比例：</th>
				  <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.yuedan_third_rate')>=0&&C('config.yuedan_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.yuedan_third_rate')>=0&&C('config.yuedan_third_rate')!=""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>			     
					<span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.yuedan_third_rate')<0||C('config.yuedan_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.yuedan_third_rate')<0||C('config.yuedan_third_rate')==""){</php>checked="checked"<php>}</php>/>
						</label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					
				
				  <input type="text" class="input-text" name="yuedan_third_rate" id="$config_sub_card_third_rate" value="{pigcms{:C('config.yuedan_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.yuedan_third_rate')<0||C('config.yuedan_third_rate')==""){</php>style="display:none"<php>}</php>/></td>
				 </tr>
				</tbody>
			   </table>

                <!--分类信息-->

                <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_classifynew_pr">
                    <tbody>
                    <tr>
                        <th width="160">直接推广用户分佣比例：</th>
                        <td>
				  <span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.classifynew_first_rate')>=0&&C('config.classifynew_first_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.classifynew_first_rate')>=0&&C('config.classifynew_first_rate')!=""){</php>checked="checked"<php>}</php>/>
                      </label>
					</span>
                            <span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.classifynew_first_rate')<0||C('config.classifynew_first_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.classifynew_first_rate')<0||C('config.classifynew_first_rate')==""){</php>checked="checked"<php>}</php>/>
                                </label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;


                            <input type="text" class="input-text" name="classifynew_first_rate" id="$config_classifynew_first_rate" value="{pigcms{:C('config.classifynew_first_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.classifynew_first_rate')<0||C('config.classifynew_first_rate')==""){</php>style="display:none"<php>}</php>/></td>
                    </tr>
                    <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
                    <th width="160">直接推广用户上级分佣比例：</th>
                    <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.classifynew_second_rate')>=0&&C('config.classifynew_second_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.classifynew_second_rate')>=0&&C('config.classifynew_second_rate')!=""){</php>checked="checked"<php>}</php>/>
                        </label>
					</span>
                        <span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.classifynew_second_rate')<0||C('config.classifynew_second_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.classifynew_second_rate')<0||C('config.classifynew_second_rate')==""){</php>checked="checked"<php>}</php>/>
                            </label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;



                        <input type="text" class="input-text" name="classifynew_second_rate" id="$config_classifynew_second_rate" value="{pigcms{:C('config.classifynew_second_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.classifynew_second_rate')<0||C('config.classifynew_second_rate')==""){</php>style="display:none"<php>}</php>/></td>
                    </tr>
                    <tr <if condition="$config.open_extra_price eq 1">style="display:none"</if>>
                    <th width="160">直接推广用户上上级分佣比例：</th>
                    <td>
					<span class="cb-enable">
						<label class="cb-enable  <php>if(C('config.classifynew_third_rate')>=0&&C('config.classifynew_third_rate')!=""){</php>selected<php>}</php>">
							<span>设置</span>
							<input type="radio" name="open_rate" value="1" <php>if(C('config.classifynew_third_rate')>=0&&C('config.classifynew_third_rate')!=""){</php>checked="checked"<php>}</php>/>
                        </label>
					</span>
                        <span class="cb-disable">
						<label class="cb-disable  <php>if(C('config.classifynew_third_rate')<0||C('config.classifynew_third_rate')==""){</php>selected<php>}</php>">
							<span>跳过</span>
							<input type="radio" name="open_rate" value="0" <php>if(C('config.classifynew_third_rate')<0||C('config.classifynew_third_rate')==""){</php>checked="checked"<php>}</php>/>
                            </label>
					</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

                        <input type="text" class="input-text" name="classifynew_third_rate" id="$config_classifynew_third_rate" value="{pigcms{:C('config.classifynew_third_rate')}" size="10" validate="number:true,max:100" tips="直接推广用户上上级分佣比例（点击设置后，请填写百分比，不要填写%，选择跳过则跳过当前设置，向上查找平台的用户分佣比例）微信和小程序分享有效" <php>if(C('config.classifynew_third_rate')<0||C('config.classifynew_third_rate')==""){</php>style="display:none"<php>}</php>/></td>
                    </tr>
                    </tbody>
                </table>

                <!--平台会员-->

                <table cellpadding="0" cellspacing="0" class="table_form" width="100%" style="display:none;" id="tab_user_level">
                    <tbody>
                    <tr>
                        <td colspan="2" style="color:red">
                            平台会员推广分佣比例设置
                        </td>
                    </tr>
                    <tr>
                        <th width="160">直接推广用户佣金比例：</th>
                        <td>
                        <input type="text" class="input-text" name="level_spread_rate" id="$config_level_spread_rate" value="{pigcms{$config['level_spread_rate']}" size="10" validate="number:true,range:[0,100]" tips="直接推广用户用户开通会员等级获得佣金比例（百分比 0-100）"  >
                        </td>
                    </tr>

                    <tr>
                    <th width="160">直接推广用户的上级佣金比例：</th>
                        <td>
                        <input type="text" class="input-text" name="level_first_spread_rate" id="$config_level_first_spread_rate" value="{pigcms{$config['level_first_spread_rate']}" size="10" validate="number:true,range:[0,100]" tips="直接推广用户的上级开通会员等级获得佣金比例（百分比 0-100）" >
                        </td>
                    </tr>
                    <tr>
                    <th width="160">直接推广用户的上上级佣金比例：</th>
                        <td>
                            <input type="text" class="input-text" name="level_second_spread_rate" id="$config_level_second_spread_rate" value="{pigcms{$config['level_second_spread_rate']}" size="10" validate="number:true,range:[0,100]" tips="直接推广用户的上上级开通会员等级获得佣金比例（百分比 0-100）" />
                        </td>
                    </tr>
                    </tbody>
                </table>
                
                <!-- 一级分佣区间设置 -->
				<div class="table-list percent_div">
					<input type="hidden" class="input-text" name="use_spread_type" id="use_spread_type" value="" />
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
										<td><input class="input-text valid" type="text" name="money_start[]" value="{pigcms{$vo.money_start}" >--- <input  class="input-text valid" type="text" name="money_end[]" value="{pigcms{$vo.money_end}"></td>
										<td><input class="input-text valid" type="text" name="money_percent[]" value="{pigcms{$vo.percent}" ></td>
										<td class="textcenter" ><a href="javascript:void(0);" class="delete" parameter="id={pigcms{$vo.id}" url="" onclick="del(this)">删除</a></td>
									</tr>
								</volist>
							<else/>
								<tr class="plus">
									<td class="sort">1</td>
									<td><input class="input-text valid" type="text" name="money_start[]" value="" >--- <input  class="input-text valid" type="text" name="money_end[]" value="" ></td>
									<td><input class="input-text valid" type="text" name="money_percent[]" value="" ></td>
									<td class="textcenter" ><a href="javascript:void(0);" class="delete" parameter="id={pigcms{$vo.id}" url="" onclick="del(this)">删除</a></td>
								</tr>
							</if>
						</tbody>
						<tr>
							<if condition="empty($_GET['type'])" ><td colspan="3" style="text-align:center;color:blue" onclick="add();">[增加]</td></if>
						</tr>
					</table>
				</div>

			   <div class="btn" style="margin-top:20px;"> 
				<input type="submit" name="dosubmit" value="提交" class="button" /> 
				
			   </div> 
			  </form> 
		</div>
		<script>
		
			$(function(){
				var data_rate_arr = [];
				var test = $('.input-text').each(function(index,val){
					var ids = $(val).attr('id');
					data_rate_arr[ids] = $(val).val()
					
				});
			
			
				$('.input-text').blur(function(){
					$('.input-text').each(function(index,val){
						var ids = $(val).attr('id');
						data_rate_arr[ids] = $(val).val()
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
						if(text_id.indexOf('first') != -1){
							$('.percent_div').show();
						}
						percent.show();
					}else{
						if(text_id.indexOf('first') != -1){
							$('.percent_div').hide();
						}
						percent.hide();
						percent.val(-1);
						percent.hide();
					}
				});
				
				
			})
		
			$('.tab_ul li a').click(function(){
					$(this).closest('li').addClass('active').siblings('li').removeClass('active');
					$($(this).attr('href')).show().siblings('.table_form').hide();
					return false;
				});

			function add(){
				var item = $('.plus:last');
				if($('.plus').length<=1&&$('.plus').css('display')=='none'){
					$('.plus').show();
				}else{
					var newitem = $(item).clone(true);
					var No = parseInt(item.find(".sort").html())+1;
					$(item).after(newitem);
					newitem.find('input').attr('value','');
					newitem.find(".sort").html(No);
				}
				// newitem.find('input[name="url[]"]').attr('id','url'+No);
			}
			
			function del(obj){
				if($('.plus').length<=1){
					$('.plus').hide();
				}else{
					$(obj).parents('.plus').remove();
					$.each($('.plus'), function(index, val) {
						var No =index+1;
						$(val).find('.sort').html(No);
						$(val).find('input[name="url[]"]').attr('id','url'+No);
						
					});
				}
			}
			function changeTab(type){
				if(type){
					if($('input[name='+type+'_first_rate]').val() == '' || $('input[name='+type+'_first_rate]').val() <= '0'){
						$('.percent_div').hide();
					}else{
						$('.percent_div').show();
					}
				}
				console.log($('input[name='+type+'_first_rate]').val())
				$.post('{pigcms{:U("ajax_get_user_rate")}',{type:type},function(data){
					var html = ''
					$('#use_spread_type').val(type)
					console.log(data.percent_detail)
					if(data.percent_detail.length > 0){
						for( var i=0; i <=data.percent_detail.length-1; i++){
							var item = data.percent_detail[i]
							console.log(item,'item')
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
				min-width: 100px;
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