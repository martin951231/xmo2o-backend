<include file="Public:header"/>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/store_amend')}" frame="true" refresh="true">
		<input type="hidden" name="store_id" value="{pigcms{$store.store_id}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="80">店铺名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$store.name}" size="25" placeholder="店铺名称" validate="required:true"/></td>
			</tr>
			<if condition="$config.international_phone eq 1">
			<tr>
				<th width="80">区号</th>
				<td>
				
					 <pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;">
					 	<option value="{pigcms{$vo.code}"  <if condition="$store.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
					</pigcms:national_phone>

				</td>
			</tr>

			</if>
			<tr>
				<th width="80">联系电话</th>
				<td>
			
				<input type="text" class="input fl" name="phone" size="25" value="{pigcms{$store.phone}" placeholder="店铺的电话" validate="required:true" tips="多个电话号码以空格分开"/></td>
			</tr>
			<tr>
				<th width="80">店铺经纬度</th>
				<td id="choose_map" data-input="1" default_long_lat="{pigcms{$store.long},{pigcms{$store.lat}"></td>
			</tr>
			<tr>
				<th width="80">店铺所在地</th>
				<td id="choose_cityarea" show_please_select="1" street_id="{pigcms{$store.street_id|default=0}" province_id="{pigcms{$store.province_id}" city_id="{pigcms{$store.city_id}" area_id="{pigcms{$store.area_id}" circle_id="{pigcms{$store.circle_id}" market_id="{pigcms{$store.market_id}"></td>
			</tr>
			<tr>
				<th width="80">店铺地址</th>
				<td><input type="text" class="input fl" name="adress" id="adress" value="{pigcms{$store.adress}" size="25" placeholder="店铺的地址" validate="required:true"/></td>
			</tr>
			<tr>
				<th width="80">店铺排序</th>
				<td><input type="text" class="input fl" name="sort" size="5" value="{pigcms{$store.sort}" validate="required:true,number:true,maxlength:6" tips="默认添加顺序排序！手动调值，数值越大，排序越前"/></td>
			</tr>
			<if condition="$config.open_sub_mchid eq 1">
				<tr>
					<th width="160">是否开启特约子商功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$store['open_sub_mchid'] eq 1">selected</if>"><span>是</span>
						<input type="radio" name="open_sub_mchid" value="1" <if condition="$store['open_sub_mchid'] eq 1">checked="checked"</if>/></label></span>
						<span class="cb-disable " ><label class="cb-disable  <if condition="$store['open_sub_mchid'] eq 0">selected</if>"><span>否</span>
						<input type="radio" name="open_sub_mchid" value="0" <if condition="$store['open_sub_mchid'] eq 0">checked="checked"</if>/></label></span>
						<em class="notice_tips" tips="开启后，该商户不能设置使用自有的微信支付，该商家的支付将按服务商的子商户号支付<br>(开启后请正确配置子商户号、子商户退款、是否允许使用平台余额、是否允许使用平台优惠)"></em>
					</td>
				</tr>
				
				<if condition="$config['open_weixin_profit_sharing'] eq 1">
					<tr class="sub_mch">
						<th width="160">是否开启分账功能</th>
						<td>
							<span class="cb-enable">
								<label class="cb-enable  <if condition="$store['open_weixin_profit_sharing'] eq 1">selected</if>">
									<span>开启</span><input type="radio" name="open_weixin_profit_sharing" value="1" <if condition="$store['open_weixin_profit_sharing'] eq 1">checked="checked"</if> />
								</label>
							</span>
							<span class="cb-disable">
								<label class="cb-disable <if condition="$store['open_weixin_profit_sharing'] eq 0">selected</if>">
									<span>关闭</span><input type="radio" name="open_weixin_profit_sharing" value="0" <if condition="$store['open_weixin_profit_sharing'] eq 0">checked="checked"</if> />
								</label>
							</span>
							
							<em class="notice_tips" tips="开启后用户推广分佣佣金直接分账到用户微信账户中，平台和商户之间实现分账"></em>
						</td>
					</tr>
				</if>
				<tr class="sub_mch">
					<th width="160">微信子商户号</th>
					<td><input type="text" class="input fl" name="sub_mch_id" size="25" value="{pigcms{$store['sub_mch_id']}" placeholder="子商户号" validate="" />
						<em class="notice_tips" tips="开启子商户支付后必须要填写正确的子商户号，子商户号可以在微信子商户平台查看"></em>
					</td>
				</tr>
				<tr class="sub_mch">
					<th width="160">微信特约商户APPID</th>
					<td><input type="text" class="input fl" name="sub_appid" size="25" value="{pigcms{$store['sub_appid']}" placeholder="" validate="" />
						<em class="notice_tips" tips="特约商户在微信开放平台上申请的APPID，仅在APP支付中使用"></em>
					</td>
				</tr>
				<tr class="sub_mch">
					<th width="160">支付宝子商户授权码</th>
					<td><input type="text" class="input fl" name="alipay_sub_authcode" size="25" value="{pigcms{$store['alipay_sub_authcode']}" placeholder="支付宝子商户授权码" validate="" />
						<em class="notice_tips" tips="开启子商户支付后必须要填写正确的子商户号授权码，可以在支付宝服务商平台查看相关信息，支付宝子商户只适用到店付，不能退款"></em>
					</td>
				</tr>
				<tr class="sub_mch">
					<th width="160">是否开启子商户支付退款</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$store['sub_mch_refund'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_refund" value="1" <if condition="$store['sub_mch_refund'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$store['sub_mch_refund'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_refund" value="0" <if condition="$store['sub_mch_refund'] eq 0">checked="checked"</if>/></label></span>
						<font color="red">请确认子商户是否授权退款权限,如果没有请不要开启</font>
						<em class="notice_tips" tips="开启子商户支付退款，需要子商户申请退款权限给微信服务商，如果未得到授权，退款将失败;<br>关闭后用户将不能退款，当开启子商户支付功能时才有效"></em>
					</td>
				</tr>
				
				
				<tr class="sub_mch"  style="display:none">
					<th width="160">是否允许适用平台余额</th>
					<td>
						<span class="cb-enable"><label class="cb-enable  <if condition="$store['sub_mch_sys_pay'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_sys_pay" value="1" <if condition="$store['sub_mch_sys_pay'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable  <if condition="$store['sub_mch_sys_pay'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_sys_pay" value="0" <if condition="$store['sub_mch_sys_pay'] eq 0">checked="checked"</if>/></label></span>
						<em class="notice_tips" tips="开启后用户通过子商户支付的同时也可以使用平台余额，且平台余额部分参与平台跟商家对账,必须开启特约子商户功能;<br>关闭后，用户不能使用平台余额，当开启子商户支付功能时才有效"></em>
					</td>
				</tr>
				<tr class="sub_mch"  style="display:none">
					<th width="160">是否允许适用平台优惠(积分优惠券)</th>
					<td>
						<span class="cb-enable"><label class="cb-enable  <if condition="$store['sub_mch_discount'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_discount" value="1" <if condition="$store['sub_mch_discount'] eq 1">checked="checked"</if>  /></label></span>
						<span class="cb-disable"><label class="cb-disable  <if condition="$store['sub_mch_discount'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_discount" value="0" <if condition="$store['sub_mch_discount'] eq 0">checked="checked"</if> /></label></span>
						<em class="notice_tips" tips="开启后,用户通过子商户支付的同时也可以使用平台优惠，且优惠部分参与平台跟商家对账,必须开启特约子商户功能;<br>关闭后用户不能使用平台优惠券，平台积分抵扣，当开启子商户支付功能时才有效"></em>
					</td>
				</tr>
				
				<if condition="$config.open_sub_owe_money">
					<tr class="sub_mch">
						<th width="160">是否允许商家余额欠费</th>
						<td>
						<input type="text" class="input fl" name="mch_owe_money" size="25" placeholder="子商户欠钱额度" value="{pigcms{$store.mch_owe_money}" validate="" />
						<em class="notice_tips" tips="0 表示不允许欠款，一旦商家余额不足够{pigcms{$config['deliver_name']}，用户则不能下单，1000 表示平台允许商家欠平台1000{pigcms{$config['Currency_txt']}，超过1000后用户不能使用{pigcms{$config['deliver_name']}下单"></em>
						</td>
					</tr>
				</if>
			</if>
            <if condition="$open_mall_check_id_card eq 0">
                <tr>
                    <th width="160">是否开启商城实名认证</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$store['is_authentication'] eq 1">selected</if>"><span>开启</span><input type="radio" name="is_authentication" value="1" <if condition="$store['is_authentication'] eq 1">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$store['is_authentication'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="is_authentication" value="0" <if condition="$store['is_authentication'] eq 0">checked="checked"</if>/></label></span>
                        <em class="notice_tips" tips="开启后，用户在下单该店铺的所有商城商品时需要进过实名认证"></em>
                    </td>
                </tr>
            </if>
			<if condition="$config['group_alias_name']">
				<tr style="display:<if condition="$config['single_system_type'] neq 'village_group'">table-row<else/>none</if>;">
					<th width="80">{pigcms{$config.group_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$store['have_group'] eq 1">selected</if>"><span>开启</span><input type="radio" name="have_group" value="1" <if condition="$store['have_group'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$store['have_group'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="have_group" value="0" <if condition="$store['have_group'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
			<if condition="$config['meal_alias_name']">
				<tr>
					<th width="80">{pigcms{$config.meal_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$store['have_meal'] eq 1">selected</if>"><span>开启</span><input type="radio" name="have_meal" value="1" <if condition="$store['have_meal'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$store['have_meal'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="have_meal" value="0" <if condition="$store['have_meal'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
			<if condition="$config['shop_alias_name']">
				<tr <if condition="$config['single_system_type'] eq 'shop'">style="display:none;"</if>>
					<th width="80">{pigcms{$config.shop_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$store['have_shop'] eq 1 OR $config['single_system_type'] eq 'shop'">selected</if>"><span>开启</span><input type="radio" name="have_shop" value="1" <if condition="$store['have_shop'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$store['have_shop'] eq 0 AND $config['single_system_type'] neq 'shop'">selected</if>"><span>关闭</span><input type="radio" name="have_shop" value="0" <if condition="$store['have_shop'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
			<if condition="$config['store_open_waimai']">
				<tr>
					<th width="80">{pigcms{$config.waimai_alias_name}功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$store['have_waimai'] eq 1">selected</if>"><span>开启</span><input type="radio" name="have_waimai" value="1" <if condition="$store['have_waimai'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$store['have_waimai'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="have_waimai" value="0" <if condition="$store['have_waimai'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
            <if condition="$config['mall_alias_name']">
                <tr>
                    <th width="80">{pigcms{$config.mall_alias_name}功能</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$store['have_mall'] eq 1">selected</if>"><span>开启</span><input type="radio" name="have_mall" value="1" <if condition="$store['have_mall'] eq 1">checked="checked"</if> /></label></span>
            <span class="cb-disable"><label class="cb-disable <if condition="$store['have_mall'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="have_mall" value="0" <if condition="$store['have_mall'] eq 0">checked="checked"</if>/></label></span>
                    </td>
                </tr>
            </if>
			<tr>
				<th width="80">店铺状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$store['status'] neq 0">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$store['status'] neq 0">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$store['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$store['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
            <if condition="C('customization.internal_membership_card')">
            <tr>
                <th width="80">是否可以使用一卡通支付</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$store['is_internal_card_pay'] neq 0">selected</if>"><span>是</span><input type="radio" name="is_internal_card_pay" value="1" <if condition="$store['is_internal_card_pay'] neq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$store['is_internal_card_pay'] eq 0">selected</if>"><span>否</span><input type="radio" name="is_internal_card_pay" value="0" <if condition="$store['is_internal_card_pay'] eq 0">checked="checked"</if>/></label></span>
                </td>
            </tr>
            </if>
			<tr>
				<th width="160">店铺是否上架到平台</th>
				<td>
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/store_edit_on_sale',array('store_id'=>$store['store_id'],'type'=>'merchant_store'))}','店铺是否上架到平台',800,560,true,false,false,null,'edit_percent',true);" style="color:blue">店铺是否上架到平台</a>&nbsp;&nbsp;&nbsp;&nbsp;
			</tr>
			<tr>
				<th width="160">店铺抽成设置</th>
				<td>
				<a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/store_edit_percent',array('store_id'=>$store['store_id'],'type'=>'merchant_store'))}','设置店铺抽成比例',800,560,true,false,false,null,'edit_percent',true);" style="color:blue">设置店铺抽成比例</a>&nbsp;&nbsp;&nbsp;&nbsp;
			</tr>
			<tr>
				<th width="160">通联支付配置</th>
				<td><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/store_allinpay',array('store_id'=>$store['store_id']))}','通联支付配置',700,480,true,false,false,null,'edit_store_allinpay',true);" style="color:blue">设置通联支付配置</a></td>
			</tr>
            <if condition="$config['is_open_merchant_foodshop_discount'] eq 1">
                <tr>
                    <th width="160">店铺餐饮折扣设置</th>
                    <td><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/m_discount',array('store_id'=>$store['store_id']))}','设置店铺餐饮折扣',700,500,true,false,false,null,'m_discount',true);" style="color:blue">设置店铺餐饮折扣</a><td>
                </tr>
            </if>
			<if condition="$config['open_meal_electronic_menu'] eq 1">
			<tr>
				<th width="80">堂扫模式</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$store['open_foodshop_pay'] eq 1">selected</if>"><span>扫码点餐</span><input type="radio" name="open_foodshop_pay" value="1" <if condition="$store['open_foodshop_pay'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$store['open_foodshop_pay'] eq 0">selected</if>"><span>扫码看菜单</span><input type="radio" name="open_foodshop_pay" value="0" <if condition="$store['open_foodshop_pay'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<tr>
				<th width="160">堂扫配置</th>
				<td><a href="javascript:void(0);" onclick="window.o2oTop.artiframe('{pigcms{:U('Merchant/edit_pay_rates',array('mer_id'=>$store['mer_id'],'type'=>'alipay','store_id'=>$store['store_id']))}','堂扫配置',800,560,true,false,false,null,'edit_pay_rates',true);" style="color:blue">堂扫配置</a></td>
			</tr>
			</if>
            <if condition="!empty($merchant['miaomaiyun_erp_appid']) && !empty($merchant['miaomaiyun_erp_appsecret']) && !empty($merchant['miaomaiyun_url'])">
                <tr><th colspan="2" style="color: red;text-align:center"> 淼迈云设置 </th></tr>
                <tr>
                    <th width="160">淼迈云ERP 门店ID</th>
                    <td><input type="text" class="input fl" name="miaomaiyun_erp_storeid" size="25" value="{pigcms{$store['miaomaiyun_erp_storeid']}" validate="" />
                    </td>
                </tr>
            </if>
		</table>
		<div class="btn hidden">
			<input type="submit" name="dosubmit" id="dosubmit" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
	<script>
		$(function(){
			if($('input[name="open_sub_mchid"]:checked').val()==1){
				$('.sub_mch').show();
			}else{
				$('.sub_mch').hide();
			}
			$('input[name="open_sub_mchid"]').click(function(){
				var sub = $(this);
				if(sub.val()==1){
					$('.sub_mch').show();
				}else{
					$('.sub_mch').hide();
				}
			});
			
			$('input[name="set_score_percent"]').click(function(){
				var sub = $(this);
				if(sub.val()==1){
					if($('input[name="score_get_percent"]').val()<0){
						$('input[name="score_get_percent"]').val(0);
					}
					$('input[name="score_get_percent"]').show();
				}else{
					$('input[name="score_get_percent"]').hide();
					$('input[name="score_get_percent"]').val(-1);
				}
			});
			
			
		});
	</script>
<include file="Public:footer"/>
