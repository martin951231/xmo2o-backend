<include file="Public:header"/>
	<style>
		.sub_mch{
			display:none
		}
	</style>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/modify')}" frame="true" refresh="true">
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<tr>
				<th width="160">商家账号</th>
				<td><input type="text" class="input fl" name="account" size="25" placeholder="商家平台的帐号" validate="required:true" tips="设定之后，以后不能再修改！"/></td>
			</tr>
			<tr>
				<th width="160">商家密码</th>
				<td><input type="password" id="check_pwd" check_width="180" class="input fl" name="pwd" size="25" placeholder="商家平台的密码" validate="required:true,minlength:6" tips="商家的密码很重要，填写难度较强的密码有效保护商家的信息，也可以保护网站的数据安全。"/></td>
			</tr>
			<tr>
				<th width="160">商家名称</th>
				<td><input type="text" class="input fl" name="name" size="25" placeholder="商家的公司名或..." validate="required:true"/></td>
			</tr>

            <if condition="$config.merchant_type_switch eq 1">
                <tr>
                    <th width="160">公司名称</th>
                    <td><input type="text" class="input fl" name="company_name"  size="25" placeholder="公司名称（营业执照上的名称）" validate="maxlength:40,required:true"/></td>
                </tr>
                <tr>
                    <th width="160">公司法人</th>
                    <td><input type="text" class="input fl" name="legal_person" value="" size="25" placeholder="公司法人"/></td>
                </tr>
                <if condition="$type_list">
                    <tr>
                        <th width="160">商家类型</th>
                        <td>
                            <select name="type_id" class="valid">
                                <option value="0">请选择商家类型</option>
                                <volist name="type_list" id="vo">
                                    <option value="{pigcms{$vo['type_id']}">{pigcms{$vo['type_name']}</option>
                                </volist>
                            </select>
                            <em class="notice_tips" tips="选择后在系统后台的商家列表里可以按照商家类型来筛选"></em>
                        </td>
                    </tr>
                </if>
            </if>

			<if condition="$config.open_score_discount eq 1">
			<tr>
				<th width="160">用户支付优惠方式</th>
				<td>
				<select name="user_discount_type" class="valid">
					<option value="0" >优惠可控通道</option>
					<option value="1" >纯优惠通道</option>
					<option value="2" >积分通道</option>
					<option value="3" >积分优惠2选1通道</option>
				</select>
				<em class="notice_tips" tips="优惠可控通道，即用户下单时，只可享受业务该有的优惠，不可获得平台积分（取决于用户消费1{pigcms{$config['Currency_txt']}获得积分设置项）；纯优惠通道，即用户下单时，只可享受业务该有的优惠，不可获得平台积分（用户消费1{pigcms{$config['Currency_txt']}获得积分的值不管是否设置，不受影响）；积分通道，即用户下单时，用户只可获得平台积分（取决于用户消费1{pigcms{$config['Currency_txt']}获得积分设置项），不可使用业务下优惠；2选1模式，即用户下单时，可在2个模式中随意选择其一（取决于用户消费1{pigcms{$config['Currency_txt']}获得积分设置项）"></em>
				</td>
			</tr>
			</if>

			<tr id="super_admins_box">
				<th width="160">绑定管理员</th>
				<td>
					<select name="admin_id" class="valid">
						<option value="0">请选择</option>
						<volist name="super_admins" id="a">
							<option value="{pigcms{$a.id}">{pigcms{$a.show_name}</option>
						</volist>
					</select>
				</td>
			</tr>


			<if condition="C('config.open_bd_spread') eq 1">
				<tr>
					<th width="160">业务代理身份</th>
					<td>
						<select name="bd_type" class="valid">
							<option value="0">请选择</option>
							<option value="2" >业务经理</option>
							<option value="1" >业务员</option>
						</select>
					</td>
				</tr>
				<tr id="bd_list_box">
					<th width="160">业务代理人</th>
					<td>
						<select name="bd_id" class="valid">
							<option value="0">请选择</option>
							<volist name="bd_list" id="bd">
							<option value="{pigcms{$bd.bd_id}">{pigcms{$bd.bd_name}</option>
							</volist>
						</select>
					</td>
				</tr>
				<tr id="bd_son_list_box" style="display: none">
					<th width="160">业务代理人</th>
					<td>
						<select name="bd_son_id" class="valid">
							<option value="0">请选择</option>
							<volist name="bd_son_list" id="bd">
							<option value="{pigcms{$bd.bd_id}" >{pigcms{$bd.bd_name}</option>
							</volist>
						</select>
					</td>
				</tr>
			</if>

			<if condition="C('config.pay_in_store') eq 1">
			<tr>
				<th width="160">是否开启扫码核销积分优惠券</th>
				<td>
					<span class="cb-enable"><label class="cb-enable" ><span>是</span>
					<input type="radio" name="san_pay_score_coupon" value="1" /></label></span>
					<span class="cb-disable " ><label class="cb-disable selected" ><span>否</span>
					<input type="radio" name="san_pay_score_coupon" value="0" checked="checked"/></label></span>
					<em class="notice_tips" tips="开启则核销，不开启则不核销。线下零售中商家用扫码枪扫描用户的商家会员微信卡包里的二维码与微信付款码时，系统将自动核销可以抵扣的用户平台积分与平台最优优惠券；同理，店内收银中商家用扫码枪扫描用户微信付款码时，系统将自动核销可以抵扣的用户平台积分与最优优惠券"></em>
				</td>
			</tr>
			</if>
			<if condition="C('config.open_sub_mchid') eq 1">
				<tr>
					<th width="160">是否开启特约子商支付功能</th>
					<td>
						<span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="open_sub_mchid" value="1"/></label></span>
						<span class="cb-disable"><label class="cb-disable  selected"><span>否</span><input type="radio" name="open_sub_mchid" value="0" checked="checked" /></label></span>
						<em class="notice_tips" tips="开启后，该商家不能设置使用自有的微信支付，该商家的支付将按服务商的子商家号支付<br>(开启后请正确配置子商家号、子商家退款、是否允许使用平台余额、是否允许使用平台优惠)"></em>
					</td>
				</tr>

				
				<tr class="sub_mch">
					<th width="160">子商家号</th>
					<td>
					<input type="text" class="input fl" name="sub_mch_id" size="25" placeholder="子商家号" validate="" />
					<em class="notice_tips" tips="开启子商家支付后必须要填写正确的子商家号，子商家号可以在微信子商家平台查看"></em>
					</td>
					
				</tr>
				
				<tr class="sub_mch">
					<th width="160">是否开启子商家支付退款</th>
					<td>
						<span class="cb-enable"><label class="cb-enable "><span>开启</span><input type="radio" name="sub_mch_refund" value="1"  /></label></span>
						<span class="cb-disable"><label class="cb-disable selected"><span>关闭</span><input type="radio" name="sub_mch_refund" value="0" checked="checked"/></label></span>
						<font color="red">请确认子商家是否授权退款权限,如果没有请不要开启</font>
						<em class="notice_tips" tips="开启子商家支付退款，需要子商家申请退款权限给微信服务商，如果未得到授权，退款将失败;<br>关闭后用户将不能退款，当开启子商家支付功能时才有效"></em>
					</td>
				</tr>
				<tr class="sub_mch">
					<th width="160">是否允许使用平台余额</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="sub_mch_system_pay" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="sub_mch_system_pay" value="0"/></label></span>
						<em class="notice_tips" tips="开启后用户通过子商家支付的同时也可以使用平台余额，且平台余额部分参与平台跟商家对账,必须开启特约子商家功能;<br>关闭后，用户不能使用平台余额，当开启子商家支付功能时才有效"></em>
					</td>
				</tr>
				<tr class="sub_mch">
					<th width="160">是否允许使用平台优惠(积分优惠券)</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>开启</span><input type="radio" name="sub_mch_discount" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="sub_mch_discount" value="0"/></label></span>
						<em class="notice_tips" tips="开启后,用户通过子商家支付的同时也可以使用平台优惠，且优惠部分参与平台跟商家对账,必须开启特约子商家功能;<br>关闭后用户不能使用平台优惠券，平台积分抵扣，当开启子商家支付功能时才有效"></em>
					</td>
				</tr>			
			</if>
					
					
			<if condition="$config['nmgzhcs_appid']">
				<tr >
					<th width="160">商家openid</th>
					<td><input type="text" class="input fl" name="shop_openid" value="" size="25" placeholder="" /></td>
				</tr>
				<tr >
					<th width="160">内蒙古智慧城市商家账号</th>
					<td><input type="text" class="input fl" name="nmgzhcs_account" autocomplete="off" value="" size="25" placeholder="" /></td>
				</tr>
				<tr >
					<th width="160">内蒙古智慧城市商家密码</th>
					<td><input type="password" class="input fl" name="nmgzhcs_passwd" autocomplete="off" value="" size="25" placeholder="" /></td>
				</tr>
			</if>
			
			
			<if condition="$config.open_mer_owe_money">
			<tr >
				<th width="160">是否允许商家余额欠费</th>
				<td>
				<input type="text" class="input fl" name="mch_owe_money" size="25" placeholder="子商家欠钱额度" validate="" />
				<em class="notice_tips" tips="0 表示不允许欠款，一旦商家余额不足够{pigcms{$config['deliver_name']}，用户则不能下单，1000 表示平台允许商家欠平台1000{pigcms{$config['Currency_txt']}，超过1000后用户不能使用{pigcms{$config['deliver_name']}下单"></em>
				</td>
				
			</tr>
			
			</if>
			
			<if condition="$config.open_merchant_express eq 1">
			<tr>
				<th width="160">商家固定配送费</th>
				<td>
					<input type="text" class="input fl" name="express_fee" size="25"  value="{pigcms{$merchant.express_fee}" validate="" />
				</td>
			</tr>
			</if>
			
			
			<if condition="C('config.open_extra_price') eq 1">
			<tr>
				<th width="160">{pigcms{:C('config.extra_price_alias_name')}结算比例</th>
				<td><input type="text" class="input fl" name="extra_price_percent" value="" size="25"  validate="required:true,min:0,max:100" tips=""/></td>
			</tr>
			<tr>
				<th width="160">消费1{pigcms{$config['Currency_txt']}赠送{pigcms{:C('config.score_name')}数</th>
				<td><input type="text" class="input fl" name="score_get" value="" size="25"  validate="required:true,min:0" tips=""/>0 相当于不得{pigcms{$config.score_name}</td>
			</tr>
			</if>
			<if condition="$config.international_phone eq 1">
			<tr>
				<th width="160">区号</th>
				<td>
					
				    <pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;">
					 	<option value="{pigcms{$vo.code}"  <if condition="$config.qcloud_sms_default_country eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
					</pigcms:national_phone>
				</td>
			</tr>

			</if>
			<if condition="$config.open_fans_percent eq 1">
			<tr>
				<th width="160">粉丝单独抽成比例</th>
				<td>
				
				<input type="text" class="input fl" name="fans_percent" size="25" placeholder="粉丝单独抽成比例" validate="required:true,min:0,max:100" tips="商家自己推广到平台的粉丝，在购买自家商品时，平台抽取的抽成比例可单独设置，直接填写阿拉伯数字即可,"/></td>
			</tr>
			</if>
			
			<if condition="$config.open_store_percent_single eq 1">
			<tr>
				<th width="160">快速买单/到店付的结算方式</th>
				<td>
				<span class="cb-enable"><label class="cb-enable selected"><span>按固定金额</span><input type="radio" name="store_percent_single_type" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>按比例结算</span><input type="radio" name="store_percent_single_type" value="2"/></label></span>
				<input type="text" class="input fl" name="store_percent_single" size="25" placeholder="快速买单/到店付的结算方式" validate="min:0,max:100" tips="商家设置0或者空则向上寻找系统的设置"/></td>
			</tr>
			</if>
			
			
			<tr>
				<th width="160">联系电话</th>
				<td>
				<input type="text" class="input fl" name="phone" size="25" placeholder="联系人的电话" validate="required:true,mobile:true" tips=""/></td>
			</tr>
		
			<tr>
				<th width="160">联系邮箱</th>
				<td><input type="text" class="input fl" name="email" size="25" placeholder="可不填写" validate="email:true" tips="只供管理员后台记录，前台不显示"/></td>
			</tr>
			<tr style="display:none;">
				<th width="160">对账周期</th>
				<td><input type="text" class="input fl" name="bill_period" size="25" placeholder="可不填写" validate="number:true,min:1" tips="对账周期，不填则按系统对账周期计算,最小为一天"/></td>
			</tr>
			
			<if condition="$system_session['area_type'] neq 3">
			<tr>
				<th width="160">所在区域</th>
				<td id="choose_cityarea" street_id="{pigcms{$merchant.street_id}" province_id="{pigcms{$merchant.province_id}" city_id="{pigcms{$merchant.city_id}" area_id="{pigcms{$merchant.area_id}" circle_id="-1"></td>
			</tr>
			<else />
				<tr>
					<th width="160">所在区域</th>
					<td>
					{pigcms{$merchant.province_name} {pigcms{$merchant.city_name} {pigcms{$merchant.area_name} {pigcms{$merchant.street_name}
					<input type="hidden" name="area_id"  value = "{pigcms{$system_session['area_id']}">
					
					<input type="hidden" name="city_id"  value = "{pigcms{$merchant['city_id']}">
					<input type="hidden" name="province_id"  value = "{pigcms{$merchant['province_id']}">
					<input type="hidden" name="street_id"  value = "{pigcms{$system_session['street_id']}">
					</td>
				</tr>
			</if>
            <tr>
                <th width="160">详细地址</th>
                <td><input type="text" class="input fl" name="address" value="{pigcms{$merchant.address}" size="50" tips=""/></td>
            </tr>
			<tr>
				<th width="160">到期时间</th>
				<td><input type="text" class="input fl" name="merchant_end_time" size="25" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" tips="商家到期时间，到期之后不允许进入商家平台并关闭该商家！清空为永不过期"/></td>
			</tr>
			<tr>
				<th width="160">商家状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>启用</span><input type="radio" name="status" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>关闭</span><input type="radio" name="status" value="0" /></label></span>
				</td>
			</tr>
			<if condition="$config['single_system_type'] neq 'village_group'">
			<tr>
				<th width="160">签约商家</th>
				<td>
					<span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="issign" value="1"/></label></span>
					<span class="cb-disable"><label class="cb-disable  selected"><span>否</span><input type="radio" name="issign" value="0" checked="checked" /></label></span>
					<em class="notice_tips" tips="开启后商家中心会显示此商家已签约标签即商家是优质客户，所有新增的产品都无需审核"></em>
				</td>
			</tr>
			<tr>
				<th width="160">认证商家</th>
				<td>
					<span class="cb-enable"><label class="cb-enable"><span>是</span><input type="radio" name="isverify" value="1" /></label></span>
					<span class="cb-disable"><label class="cb-disable  selected"><span>否</span><input type="radio" name="isverify" value="0"  checked="checked"/></label></span>
					<em class="notice_tips" tips="开启后商家中心会显示此商家已认证标签"></em>
				</td>
			</tr>
			</if>
			<if condition="$config['wx_token']">
			<tr>
				<th width="160">使用公众号</th>
				<td>
					<span class="cb-enable"><label class="cb-enable selected"><span>允许</span><input type="radio" name="is_open_oauth" value="1" checked="checked" /></label></span>
					<span class="cb-disable"><label class="cb-disable"><span>禁止</span><input type="radio" name="is_open_oauth" value="0"/></label></span>
					<em class="notice_tips" tips="如果系统设置中允许所有商家都使用公众号，请禁止无效。"></em>
				</td>
			</tr>
			</if>
			<if condition="$config['is_open_weidian']">
				<tr>
					<th width="160">开微店</th>
					<td>
						<span class="cb-enable"><label class="cb-enable selected"><span>允许</span><input type="radio" name="is_open_oauth" value="1" checked="checked" /></label></span>
						<span class="cb-disable"><label class="cb-disable"><span>禁止</span><input type="radio" name="is_open_oauth" value="0"/></label></span>
						<em class="notice_tips" tips="如果系统设置中允许所有商家都能开微店，请禁止无效。"></em>
					</td>
				</tr>
			</if>

            <!--批发-->
            <if condition="$config['merchant_wholesale_control'] eq 1">
            <tr>
                <th width="160">是否允许发布商品到批发市场</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_publish_market_goods'] eq 0">selected</if>"><span>允许</span><input type="radio" name="is_publish_market_goods" value="0" <if condition="$merchant['is_publish_market_goods'] eq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_publish_market_goods'] eq 1">selected</if>"><span>禁止</span><input type="radio" name="is_publish_market_goods" value="1" <if condition="$merchant['is_publish_market_goods'] eq 1">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr>
                <th width="160">是否允许批发来的商品上架到店铺</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_market_goods_upper_store'] eq 0">selected</if>"><span>允许</span><input type="radio" name="is_market_goods_upper_store" value="0" <if condition="$merchant['is_market_goods_upper_store'] eq 0">checked="checked"</if> /></label></span>
                    <span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_market_goods_upper_store'] eq 1">selected</if>"><span>禁止</span><input type="radio" name="is_market_goods_upper_store" value="1" <if condition="$merchant['is_market_goods_upper_store'] eq 1">checked="checked"</if>/></label></span>
                </td>
            </tr>
            <tr>
                <th width="160">批发商品时候的优惠折扣(%)</th>
                <td><input type="text" class="input fl" name="wholesale_discount" value="{pigcms{$merchant.wholesale_discount}" size="25" tips="即该商家批发其他商家商品时享受的优惠折扣。折扣优惠的钱由供货商家出，且与供货商家本身的【满多少享折扣】优惠叠加使用，请输入0-100的数值，填写50为5折，不填或者0为无折扣，不要填写%"/></td>
            </tr>
            </if>
            <php> if (C('config.mall_third_deliver_open')) {</php>
            <tr>
                <th width="160">是否开启第三方配送</th>
                <td>
                    <span class="cb-enable"><label class="cb-enable"><span>开启</span><input type="radio" name="open_third_deliver" value="1" /></label></span>
                    <span class="cb-disable"><label class="cb-disable selected"><span>关闭</span><input type="radio" name="open_third_deliver" value="0" checked="checked"/></label></span>
                </td>
            </tr>
            <php> }</php>
			<if condition="C('config.pay_ebankpay_open') eq 1">
				<tr><th colspan="2" style="color: red;text-align:center"> 日照银行设置 </th></tr>
				<tr>
					<th width="160">子商户号</th>
					<td><input type="text" class="input fl" name="ebankpay_smchid" size="25" value="{pigcms{$merchant['ebankpay_smchid']}" validate="" />
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
			$('input[name="open_sub_mchid"]').click(function(){
				var sub = $(this);
				if(sub.val()==1){
					$('.sub_mch').show();
				}else{
					$('.sub_mch').hide();
				}
			});

			$('[name="bd_type"]').bind("change", function(){
				if($(this).val() == 1){
					$("#bd_list_box").hide();
					$("#bd_son_list_box").show();
				}
				else{
					$("#bd_list_box").show();
					$("#bd_son_list_box").hide();	
				}
			})
		});
	</script>
	
<include file="Public:footer"/>