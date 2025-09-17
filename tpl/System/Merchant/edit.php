<include file="Public:header"/>
<style type="text/css">
	.image_show{
		cursor: pointer;
		width: 100px;
	}
</style>
	<form id="myform" method="post" action="{pigcms{:U('Merchant/amend')}"  frame="true" refresh="true">
		<input type="hidden" name="mer_id" value="{pigcms{$merchant.mer_id}"/>
		<input type="hidden" name="is_verify" value="{pigcms{$_GET.is_verify|default=0}"/>
		<table cellpadding="0" cellspacing="0" class="frame_form" width="100%">
			<if condition="$merchant['account'] or $merchant['phone']">
				<tr>
					<th width="160">商家账号</th>
					<if condition="$can_edit_account">
						<td><input type="text" class="input fl" name="account" size="25" value="<notempty name="merchant.account">{pigcms{$merchant.account}<else/>{pigcms{$merchant.phone}</notempty>"/></td>
					<else/>
						<td><div class="show"><notempty name="merchant.account">{pigcms{$merchant.account}<else/>{pigcms{$merchant.phone}</notempty></div></td>
					</if>
				</tr>
			</if>
			<tr>
				<th width="160">注册时间</th>
				<td><div class="show">{pigcms{$merchant.reg_time|date='Y-m-d H:i:s',###}</div></td>
			</tr>
			<tr>
				<th width="160">商家名称</th>
				<td><input type="text" class="input fl" name="name" value="{pigcms{$merchant.name}" size="25" placeholder="商家的公司名或..." validate="required:true"/></td>
			</tr>
			<tr>
				<th width="160">商家密码</th>
				<td><input type="password" id="check_pwd" check_width="180" check_event="keyup" class="input fl" name="pwd" value="" size="25" placeholder="不修改则不填写！" validate="minlength:6" tips="不修改则不填写！"/></td>
			</tr>

            <if condition="$config.merchant_type_switch eq 1">
                <tr>
                    <th width="160">公司名称</th>
                    <td><input type="text" class="input fl" name="company_name" value="{pigcms{$merchant.company_name}" size="25" placeholder="公司名称（营业执照上的名称）" validate="maxlength:40,required:true"/></td>
                </tr>
                <tr>
                    <th width="160">公司法人</th>
                    <td><input type="text" class="input fl" name="legal_person" value="{pigcms{$merchant.legal_person}" size="25" placeholder="公司法人"/></td>
                </tr>
                <if condition="$type_list">
                    <tr>
                        <th width="160">商家类型</th>
                        <td>
                            <select name="type_id" class="valid">
                                <option value="0">请选择商家类型</option>
                                <volist name="type_list" id="vo">
                                    <option value="{pigcms{$vo['type_id']}" <if condition="$merchant['type_id'] eq $vo['type_id']">selected="selected"</if>>{pigcms{$vo['type_name']}</option>
                                </volist>
                            </select>
                            <em class="notice_tips" tips="选择后在系统后台的商家列表里可以按照商家类型来筛选"></em>
                        </td>
                    </tr>
                </if>
            </if>
            
            <tr>
                <th width="160">营业执照证书照片</th>
                <td>&nbsp;&nbsp;
                    <input type="hidden" class="input-image" name="trading_certificate_image" value="{pigcms{$merchant['trading_certificate_image']}">
                    <ul class="image_product_list" id="avatar_detail">

                    </ul>
                    <ul class="avatar_list" id="avatar_show" >
                        <li  class="upload_pic_li1" style="float:left;list-style:none;"><img class="image_show" src="{pigcms{:replace_file_domain($merchant['trading_certificate_image_info'])}" style="margin-right:5px;"></li>
                    </ul>
                    <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >更换图片</a>
                </td>
            </tr>
       
       
            <tr>
                <th width="160">身份证正面照</th>
                <td>
                    <input type="hidden" class="input-image" name="id_card_front" value="{pigcms{$merchant['id_card_front']}">
                    <ul class="image_product_list" id="avatar_detail">

                    </ul>
                    <ul class="avatar_list" id="avatar_show" >
                        <li  class="upload_pic_li1" style="float:left;list-style:none;"><img class="image_show" src="{pigcms{:replace_file_domain($merchant['id_card_front_info'])}" style="margin-right:5px;"></li>
                    </ul>
                    <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >更换图片</a>&nbsp;&nbsp;
                </td>
            </tr>
        
       
            <tr>
                <th width="160">身份证反面照</th>
                <td>&nbsp;&nbsp;
                    <input type="hidden" class="input-image" name="id_card_reverse" value="{pigcms{$merchant['id_card_reverse']}">
                    <ul class="image_product_list" id="avatar_detail">

                    </ul>
                    <ul class="avatar_list" id="avatar_show" >
                        <li  class="upload_pic_li1" style="float:left;list-style:none;"><img class="image_show" src="{pigcms{:replace_file_domain($merchant['id_card_reverse_info'])}" style="margin-right:5px;"></li>
                    </ul>
                    <a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage" style="background: #87b87f!important;border-color: #87b87f;color:#fff;" >更换图片</a>
                </td>
            </tr>
            

            <if condition="$config.merchant_recommend_wholesale_switch eq 1">
                <tr>
                    <th width="160">我的荐码</th>
                    <td><div class="show">{pigcms{$merchant.mer_code}</div></td>
                 </tr>
                <tr>
                    <th width="160">商家推荐码</th>
                    <td><input type="text" class="input fl" name="mer_spread_code" value="{pigcms{$merchant.mer_spread_code}" size="25" placeholder="填写商家推荐码" validate="maxlength:40"/></td>
                </tr>
            </if>


			
			<if condition="$config.open_score_discount eq 1">
			<tr>
				<th width="160">用户支付优惠方式</th>
				<td>
				<select name="user_discount_type" class="valid">
					<option value="0" <if condition="$merchant['user_discount_type'] eq 0">selected="selected"</if>>优惠可控通道</option>
					<option value="1" <if condition="$merchant['user_discount_type'] eq 1">selected="selected"</if>>纯优惠通道</option>
					<option value="2" <if condition="$merchant['user_discount_type'] eq 2">selected="selected"</if>>积分通道</option>
					<option value="3" <if condition="$merchant['user_discount_type'] eq 3">selected="selected"</if>>积分优惠2选1通道</option>
				</select>
				<em class="notice_tips" tips="优惠可控通道，即用户下单时，只可享受业务该有的优惠，可获得平台积分（取决于用户消费1{pigcms{$config['Currency_txt']}获得积分设置项）；纯优惠通道，即用户下单时，只可享受业务该有的优惠，不可获得平台积分（用户消费1{pigcms{$config['Currency_txt']}获得积分的值不管是否设置，不受影响）；积分通道，即用户下单时，用户只可获得平台积分（取决于用户消费1{pigcms{$config['Currency_txt']}获得积分设置项），不可使用业务下优惠；2选1模式，即用户下单时，可在2个模式中随意选择其一（取决于用户消费1{pigcms{$config['Currency_txt']}获得积分设置项）"></em>
				</td>
			</tr>
			</if>

			<tr id="super_admins_box">
					<th width="160">绑定管理员</th>
					<td>
						<select name="admin_id" class="valid">
							<option value="0">请选择</option>
							<volist name="super_admins" id="a">
								<option value="{pigcms{$a.id}" <if condition="$merchant['admin_id'] eq $a['id']"> selected="selected"</if>>{pigcms{$a.show_name}</option>
							</volist>
						</select>
					</td>
				</tr>

			<if condition="C('config.open_bd_spread_new') eq 1">
				<tr>
					<th width="160">业务代理身份(新)</th>
					<td>
					<select name="bd_type" class="valid">
						<option value="0">请选择</option>
						<option value="2" <if condition="$merchant['bd_type'] eq 2">selected="selected"</if>>业务经理</option>
						<option value="1" <if condition="$merchant['bd_type'] eq 1">selected="selected"</if>>业务员</option>
					</select>
					</td>
				</tr>
				<tr id="bd_list_box" <if condition="$merchant['bd_type'] eq '1'">style="display: none"</if>>
					<th width="160">业务代理人(新)</th>
					<td>
					<select name="bd_id" class="valid">
						<option value="0">请选择</option>
						<volist name="bd_list" id="bd">
						<option value="{pigcms{$bd.bd_id}" <if condition="$merchant['bd_id'] eq $bd['bd_id']">selected="selected"</if>>{pigcms{$bd.bd_name}</option>
						</volist>
					</select>
					</td>
				</tr>
				<tr id="bd_son_list_box" <if condition="$merchant['bd_type'] eq '2' || empty($merchant['bd_type'])">style="display: none"</if>>
					<th width="160">业务代理人</th>
					<td>
					<select name="bd_son_id" class="valid">
						<option value="0">请选择</option>
						<volist name="bd_son_list" id="bd">
						<option value="{pigcms{$bd.bd_id}" <if condition="$merchant['bd_id'] eq $bd['bd_id']">selected="selected"</if>>{pigcms{$bd.bd_name}</option>
						</volist>
					</select>
					</td>
				</tr>
            <elseif condition="C('config.open_bd_spread') eq 1" />
                <tr>
                    <th width="160">业务代理身份</th>
                    <td>
                        <select name="bd_type" class="valid">
                            <option value="0">请选择</option>
                            <option value="2" <if condition="$merchant['bd_type'] eq 2">selected="selected"</if>>业务经理</option>
                            <option value="1" <if condition="$merchant['bd_type'] eq 1">selected="selected"</if>>业务员</option>
                        </select>
                    </td>
                </tr>
                <tr id="bd_list_box" <if condition="$merchant['bd_type'] eq '1'">style="display: none"</if>>
                <th width="160">业务代理人</th>
                <td>
                    <select name="bd_id" class="valid">
                        <option value="0">请选择</option>
                        <volist name="bd_list" id="bd">
                            <option value="{pigcms{$bd.bd_id}" <if condition="$merchant['bd_id'] eq $bd['bd_id']">selected="selected"</if>>{pigcms{$bd.bd_name}</option>
                        </volist>
                    </select>
                </td>
                </tr>
                <tr id="bd_son_list_box" <if condition="$merchant['bd_type'] eq '2' || empty($merchant['bd_type'])">style="display: none"</if>>
                <th width="160">业务代理人</th>
                <td>
                    <select name="bd_son_id" class="valid">
                        <option value="0">请选择</option>
                        <volist name="bd_son_list" id="bd">
                            <option value="{pigcms{$bd.bd_id}" <if condition="$merchant['bd_id'] eq $bd['bd_id']">selected="selected"</if>>{pigcms{$bd.bd_name}</option>
                        </volist>
                    </select>
                </td>
                </tr>
			</if>
			
                <tr>
                    <th width="160">{pigcms{:L_('商家推广用户消费的金额是否参与抽成')}</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$merchant['spread_user_buy_no_commission'] eq 1">selected</if>"><span>是</span>
            <input type="radio" name="spread_user_buy_no_commission" value="1" <if condition="$merchant['spread_user_buy_no_commission'] eq 1">checked="checked"</if>/></label></span>
            <span class="cb-disable " ><label class="cb-disable  <if condition="$merchant['spread_user_buy_no_commission'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="spread_user_buy_no_commission" value="0" <if condition="$merchant['spread_user_buy_no_commission'] eq 0">checked="checked"</if>/></label></span>
            <em class="notice_tips" tips="商家推广的用户在推广商家里消费外卖业务时，平台不抽取该商家抽成"></em>
            </td>
            </tr>

			<if condition="C('config.pay_in_store') eq 1">
			<tr>
				<th width="160">是否开启扫码核销积分优惠券</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$merchant['san_pay_score_coupon'] eq 1">selected</if>"><span>是</span>
					<input type="radio" name="san_pay_score_coupon" value="1" <if condition="$merchant['san_pay_score_coupon'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="$merchant['san_pay_score_coupon'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="san_pay_score_coupon" value="0" <if condition="$merchant['san_pay_score_coupon'] eq 0">checked="checked"</if>/></label></span>
					<em class="notice_tips" tips="开启则核销，不开启则不核销。线下零售中商家用扫码枪扫描用户的商家会员微信卡包里的二维码与微信付款码时，系统将自动核销可以抵扣的用户平台积分与平台最优优惠券；同理，店内收银中商家用扫码枪扫描用户微信付款码时，系统将自动核销可以抵扣的用户平台积分与最优优惠券"></em>
				</td>
			</tr>
			</if>

			<if condition="$config['open_shop_auto_order_notice']">
			<tr>
				<th width="160">是否开启外卖自动接单电话通知商家</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$merchant['open_shop_auto_order_notice'] eq 1">selected</if>"><span>是</span>
					<input type="radio" name="open_shop_auto_order_notice" value="1" <if condition="$merchant['open_shop_auto_order_notice'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="$merchant['open_shop_auto_order_notice'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="open_shop_auto_order_notice" value="0" <if condition="$merchant['open_shop_auto_order_notice'] eq 0">checked="checked"</if>/></label></span>
					<em class="notice_tips" tips="开启后，可以在商家后台外卖基本信息下设置自动接单后多长时间内发送短信提醒"></em>
				</td>
			</tr>
			</if>
			
			
			<if condition="$config.open_sub_mchid eq 1">
			<tr>
				<th width="160">是否开启特约子商功能</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$merchant['open_sub_mchid'] eq 1">selected</if>"><span>是</span>
					<input type="radio" name="open_sub_mchid" value="1" <if condition="$merchant['open_sub_mchid'] eq 1">checked="checked"</if>/></label></span>
					<span class="cb-disable " ><label class="cb-disable  <if condition="$merchant['open_sub_mchid'] eq 0">selected</if>"><span>否</span>
					<input type="radio" name="open_sub_mchid" value="0" <if condition="$merchant['open_sub_mchid'] eq 0">checked="checked"</if>/></label></span>
					<em class="notice_tips" tips="开启后，该商家不能设置使用自有的微信支付，该商家的支付将按服务商的子商家号支付<br>(开启后请正确配置子商家号、子商家退款、是否允许使用平台余额、是否允许使用平台优惠)"></em>
				</td>
			</tr>
			</if>
			<if condition="$config['open_yanglao_and_sande'] eq 1">
				<tr>
					<th width="160">杉德分账方编号</th>
					<td><input type="text" class="input fl" name="sande_merchant_fz" size="25" value="{pigcms{$merchant['sande_merchant_fz']}" placeholder="分账方编号" validate="" />
						<em class="notice_tips" tips="在杉德机构为本商户添加的分账方，得到分账方编号录入到这里"></em>
					</td>
				</tr>
			</if>

			<if condition="$config['sub_mchid_only_offline'] eq 1">
				<tr class="sub_mch">
					<th width="160">只允许子商家线下支付</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <if condition="$merchant['sub_mchid_only_offline'] eq 1">selected</if>">
								<span>开启</span><input type="radio" name="sub_mchid_only_offline" value="1" <if condition="$merchant['sub_mchid_only_offline'] eq 1">checked="checked"</if> />
							</label>
						</span>
						<span class="cb-disable">
							<label class="cb-disable <if condition="$merchant['sub_mchid_only_offline'] eq 0">selected</if>">
								<span>关闭</span><input type="radio" name="sub_mchid_only_offline" value="0" <if condition="$merchant['sub_mchid_only_offline'] eq 0">checked="checked"</if> />
							</label>
						</span>
						
						<em class="notice_tips" tips="开启后线上走平台，线下走子商家，关闭后都走子商家"></em>
					</td>
				</tr>
			</if>
			
			<if condition="$config['open_weixin_profit_sharing'] eq 1">
				<tr class="sub_mch">
					<th width="160">是否开启分账功能</th>
					<td>
						<span class="cb-enable">
							<label class="cb-enable  <if condition="$merchant['open_weixin_profit_sharing'] eq 1">selected</if>">
								<span>开启</span><input type="radio" name="open_weixin_profit_sharing" value="1" <if condition="$merchant['open_weixin_profit_sharing'] eq 1">checked="checked"</if> />
							</label>
						</span>
						<span class="cb-disable">
							<label class="cb-disable <if condition="$merchant['open_weixin_profit_sharing'] eq 0">selected</if>">
								<span>关闭</span><input type="radio" name="open_weixin_profit_sharing" value="0" <if condition="$merchant['open_weixin_profit_sharing'] eq 0">checked="checked"</if> />
							</label>
						</span>
						
						<em class="notice_tips" tips="开启后用户推广分佣佣金直接分账到用户微信账户中，平台和商户之间实现分账"></em>
					</td>
				</tr>
			</if>
			<tr class="sub_mch">
				<th width="160">微信子商家号</th>
				<td><input type="text" class="input fl" name="sub_mch_id" size="25" value="{pigcms{$merchant['sub_mch_id']}" placeholder="子商家号" validate="" />
					<em class="notice_tips" tips="开启子商家支付后必须要填写正确的子商家号，子商家号可以在微信子商家平台查看"></em>
				</td>
			</tr>
			<tr class="sub_mch">
				<th width="160">微信特约商户APPID</th>
				<td><input type="text" class="input fl" name="sub_appid" size="25" value="{pigcms{$merchant['sub_appid']}" placeholder="" validate="" />
					<em class="notice_tips" tips="特约商户在微信开放平台上申请的APPID，仅在APP支付中使用"></em>
				</td>
			</tr>
			<tr class="sub_mch">
				<th width="160">支付宝子商家授权码</th>
				<td><input type="text" class="input fl" name="alipay_auth_code" size="25" value="{pigcms{$merchant['alipay_auth_code']}" placeholder="支付宝子商家授权码" validate="" />
					<em class="notice_tips" tips="开启子商家支付后必须要填写正确的子商家号授权码，可以在支付宝服务商平台查看，支付宝子商家只适用到店付，不能退款，不能使用平台余额及优惠"></em>
				</td>
			</tr>
			<if condition="$config.pay_scrcu_cash_open eq 1">
			<tr>
				<th width="160">四川农信商户编号</th>
				<td><input type="text" class="input fl" name="scrcu_mer_no" size="25" value="{pigcms{$merchant['scrcu_mer_no']}" placeholder="四川农信银行分配的子商户编号" validate="" />
				</td>
			</tr>
			</if>
			<if condition="$config.open_tianquetech eq 1">
			<tr>
				<th width="160">随行付商户编号</th>
				<td><input type="text" class="input fl" name="tianque_mno" size="25" value="{pigcms{$merchant['tianque_mno']}" placeholder="随行付分配商户编号" validate="" />
					<p style="height: 15px;line-height: 15px;" class="fl">
					<if condition="$merchant.tianque_mno_status eq 1">
						<span style="color:green">当前商户号已签约</span>
					<elseif condition="$merchant.tianque_mno_status eq 2" />
					<span style="color:red">当前商户号签约存在异常（可能是商户号填写不正确）</span>
					<elseif condition="$merchant.tianque_mno_url neq ''" />
					<span style="color:red">将该地址（{pigcms{$merchant['tianque_mno_url']}）复制给商家进行签约分账权限，签约成功后，次日生效。</span>
					</if>
					</p>
				</td>
			</tr>
			</if>
			<tr class="sub_mch">
				<th width="160">是否开启子商家支付退款</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$merchant['sub_mch_refund'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_refund" value="1" <if condition="$merchant['sub_mch_refund'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$merchant['sub_mch_refund'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_refund" value="0" <if condition="$merchant['sub_mch_refund'] eq 0">checked="checked"</if>/></label></span>
					<font color="red">请确认子商家是否授权退款权限,如果没有请不要开启</font>
					<em class="notice_tips" tips="开启子商家支付退款，需要子商家申请退款权限给微信服务商，如果未得到授权，退款将失败;<br>关闭后用户将不能退款，当开启子商家支付功能时才有效"></em>
				</td>
			</tr>

			<if condition="$config.open_chinaums eq 1">
			<tr>
				<th width="160">银联商户编号</th>
				<td><input type="text" class="input fl" name="chinaums_merno" size="25" value="{pigcms{$merchant['chinaums_merno']}" placeholder="银联分配商户编号，用以调用支付" validate="" />
				</td>
			</tr>
            <if condition="$open_chinaums_without_asset eq 1">
            <tr>
                <th width="160">子商户代付编号</th>
                <td><input type="text" class="input fl" name="chinaums_withdraw_merno" size="25" value="{pigcms{$merchant['chinaums_withdraw_merno']}" placeholder="子商户入网成功之后会生成这个编号，用于银联商户提现" validate="" />
                    <em class="notice_tips" tips="子商户入网成功之后会生成这个编号，用于银联商户提现"></em>
                </td>
            </tr>
            </if>
			<tr>
				<th width="160">银联企业用户号</th>
				<td><input type="text" class="input fl" name="chinaums_zg_merno" size="25" value="{pigcms{$merchant['chinaums_zg_merno']}" placeholder="银联分配资管企业用户号，用以分账" validate="" />
				</td>
			</tr>
			<tr>
				<th width="160">账期</th>
				<td><input type="text" class="input fl" name="chinaums_accounting_period" size="10" value="{pigcms{$merchant['chinaums_accounting_period']}" placeholder="银联分账账期，单位：天" validate="" />
				</td>
			</tr>
			</if>
			
			
			<tr class="sub_mch">
				<th width="160">是否允许适用平台余额</th>
				<td>
					<span class="cb-enable"><label class="cb-enable  <if condition="$merchant['sub_mch_sys_pay'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_sys_pay" value="1" <if condition="$merchant['sub_mch_sys_pay'] eq 1">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$merchant['sub_mch_sys_pay'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_sys_pay" value="0" <if condition="$merchant['sub_mch_sys_pay'] eq 0">checked="checked"</if>/></label></span>
					<em class="notice_tips" tips="开启后用户通过子商家支付的同时也可以使用平台余额，且平台余额部分参与平台跟商家对账,必须开启特约子商家功能;<br>关闭后，用户不能使用平台余额，当开启子商家支付功能时才有效"></em>
				</td>
			</tr>
			
			
			<tr class="sub_mch">
				<th width="160">是否允许适用平台优惠(积分优惠券)</th>
				<td>
					<span class="cb-enable"><label class="cb-enable  <if condition="$merchant['sub_mch_discount'] eq 1">selected</if>"><span>开启</span><input type="radio" name="sub_mch_discount" value="1" <if condition="$merchant['sub_mch_discount'] eq 1">checked="checked"</if>  /></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$merchant['sub_mch_discount'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="sub_mch_discount" value="0" <if condition="$merchant['sub_mch_discount'] eq 0">checked="checked"</if> /></label></span>
					<em class="notice_tips" tips="开启后,用户通过子商家支付的同时也可以使用平台优惠，且优惠部分参与平台跟商家对账,必须开启特约子商家功能;<br>关闭后用户不能使用平台优惠券，平台积分抵扣，当开启子商家支付功能时才有效"></em>
				</td>
			</tr>
			
			</if>
			
			
			<if condition="$config['nmgzhcs_appid']">
				<tr >
					<th width="160">商家openid</th>
					<td><input type="text" class="input fl" name="shop_openid" value="{pigcms{$merchant.shop_openid}" size="25" placeholder="" /></td>
				</tr>
				<tr >
					<th width="160">内蒙古智慧城市商家账号</th>
					<td><input type="text" class="input fl" name="nmgzhcs_account" autocomplete="off" value="{pigcms{$merchant.nmgzhcs_account}" size="25" placeholder="" /></td>
				</tr>
				<tr >
					<th width="160">内蒙古智慧城市商家密码</th>
					<td><input type="password" class="input fl" name="nmgzhcs_passwd" autocomplete="off" value="{pigcms{$merchant.nmgzhcs_passwd}" size="25" placeholder="" /></td>
				</tr>
			</if>
		
			<if condition="$config.open_mer_owe_money eq 1">
			<tr >
				<th width="160">是否允许商家余额欠费</th>
				<td>
				<input type="text" class="input fl" name="mch_owe_money" size="25" placeholder="商家欠钱额度" value="{pigcms{$merchant.mch_owe_money}" validate="" />
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
			<!-- 商家入驻缴费 -->
			<if condition="C('customization.merchant_settle_in') eq 1 ">
				<tr>
					<th width="160">入驻缴费金额</th>
					<td>
						<span>￥{pigcms{$merchant.settle_in_money}</span>
					</td>
				</tr>
				<tr>
					<th width="160">自定义标签</th>
					<td>
						<input type="text" class="input fl" name="settle_in_sign" size="25" value="{pigcms{$merchant.settle_in_sign}" validate="" />
					</td>
				</tr>
			</if>

			<if condition="$config['shop_alias_name']">
			<tr>
				<th width="160">打包费是否抽成</th>
				<td>
					<span class="cb-enable"><label class="cb-enable  <if condition="$merchant['package_fee_percent'] eq 1">selected</if>"><span>开启</span><input type="radio" name="package_fee_percent" value="1" <if condition="$merchant['package_fee_percent'] eq 1">checked="checked"</if>  /></label></span>
					<span class="cb-disable"><label class="cb-disable  <if condition="$merchant['package_fee_percent'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="package_fee_percent" value="0" <if condition="$merchant['package_fee_percent'] eq 0">checked="checked"</if> /></label></span>
					<em class="notice_tips" tips="开启后{pigcms{$shop_alias_name}业务的打包费也会抽成"></em>
				</td>
			</tr>
			</if>
			
			<if condition="$config.open_extra_price eq 1">
			<tr>
				<th width="160">商家欠平台{pigcms{:C('config.extra_price_alias_name')}数</th>
				<td><input type="text" class="input fl" name="extra_price_pay_for_system" value="{pigcms{$merchant.extra_price_pay_for_system|floatval}" size="25"  validate="required:true,min:0" tips=""/>,即{pigcms{:sprintf("%.2f", $merchant['extra_price_pay_for_system']*$merchant['extra_price_percent']/100)}{pigcms{$config['Currency_txt']}</td>
			</tr>
			<tr>
				<th width="160">{pigcms{:C('config.extra_price_alias_name')}结算比例</th>
				<td><input type="text" class="input fl" name="extra_price_percent" value="{pigcms{$merchant.extra_price_percent|floatval}" size="25"  validate="required:true,min:0,max:100" tips=""/></td>
			</tr>
			<tr>
				<th width="160">消费1{pigcms{$config['Currency_txt']}赠送{pigcms{:C('config.score_name')}数</th>
				<td><input type="text" class="input fl" name="score_get" value="{pigcms{$merchant.score_get|floatval}" size="25"  validate="required:true,min:0" tips=""/>0 相当于不得{pigcms{$config.score_name}</td>
			</tr>
			</if>

            <if condition="$config.merchant_buy_points_switch eq 1">
                <tr>
                    <th width="160">商家平台积分</th>
                    <td>
                        <div style="height:30px;line-height:24px;">现在积分：{pigcms{$config['Currency_symbol']}{pigcms{$merchant.platform_score|floatval=###} &nbsp;&nbsp;&nbsp;&nbsp;
                            <select name="set_type"><option value="0">增加</option><option value="1">减少</option></select>&nbsp;&nbsp;
                            <input type="text" class="input" name="sore_count" size="10" validate="number:true" tips="此处填写增加或减少的额度，不是将积分变为此处填写的值"/>
                            备注：<input type="text" class="input" name="remark" size="20"  tips="更改积分时请填写备注"/>
                        </div>
                    </td>
                </tr>
            </if>
            <if condition="$config.open_merchant_score eq 1">
                <tr>
                    <th width="160">商家积分</th>
                    <td>
                        <div style="height:30px;line-height:24px;">现在积分：{pigcms{$config['Currency_symbol']}{pigcms{$merchant.merchant_score|floatval=###} &nbsp;&nbsp;&nbsp;&nbsp;
                        	<if condition="$system_session['level'] == 2 || in_array(646, $system_session['menus'])">
                            	<select name="set_merchant_score_type"><option value="0">增加</option><option value="1">减少</option></select>&nbsp;&nbsp;
                            	<input type="text" class="input" name="merchant_score" size="10" validate="number:true" tips="此处填写增加或减少的额度，不是将积分变为此处填写的值"/>
                            	备注：<input type="text" class="input" name="merchant_score_remark" size="20" />
                        	</if>
                        </div>
                    </td>
                </tr>
            </if>
	
			<if condition="$config['single_system_type'] neq 'village_group'">
				<tr>
				
					<tr>
					<th width="160">消费1{pigcms{$config['Currency_txt']}赠送{pigcms{:C('config.score_name')}百分比</th>
					<td>
						<span class="cb-enable"><label class="cb-enable  <if condition="$merchant['score_get_percent'] egt 0">selected</if>"><span>设置</span>
						<input type="radio" name="set_score_percent" value="1" <if condition="$merchant['score_get_percent'] egt 0">checked="checked"</if>/></label></span>
						<span class="cb-disable " ><label class="cb-disable  <if condition="$merchant['score_get_percent'] lt 0">selected</if>"><span>跳过</span>
						<input type="radio" name="set_score_percent" value="0" <if condition="$merchant['score_get_percent'] lt 0">checked="checked"</if>/></label>
						</span>
						
			
						<input type="text" name="score_get_percent" class="input fl" value="{pigcms{$merchant['score_get_percent']}" style="margin-left:5px;" <if condition="$merchant['score_get_percent'] lt 0">style="display:none"</if>/>
						<em class="notice_tips" tips="（按百分比，不要填写%，不设请选择跳过）"></em>
					</td>
				</tr>
			</if>
			<if condition="$config.international_phone eq 1">
			<tr>
				<th width="160">区号</th>
				<td>
					

				     <pigcms:national_phone id="phone_country_type" class="fl" style="margin-right:5px;">
					 	<option value="{pigcms{$vo.code}"  <if condition="$merchant.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
					</pigcms:national_phone>
				</td>
			</tr>
			</if>
			
			<if condition="$config.open_fans_percent eq 1">
			<tr>
				<th width="160">粉丝单独抽成比例</th>
				<td>
				<input type="text" class="input fl" name="fans_percent" size="25" value="{pigcms{$merchant.fans_percent|floatval}" placeholder="粉丝单独抽成比例" validate="required:true,min:0,max:100" tips="商家自己推广到平台的粉丝，在购买自家商品时，平台抽取的抽成比例可单独设置，直接填写阿拉伯数字即可,"/></td>
			</tr>
			</if>

			<if condition="$config.open_store_percent_single eq 1">
			<tr>
				<th width="160">快速买单/到店付的结算方式</th>
				<td>
				<span class="cb-enable">
					<label class="cb-enable <if condition="$merchant['store_percent_single_type'] eq 1">selected</if>">
						<span>按固定金额</span>
						<input type="radio" name="store_percent_single_type" value="1" <if condition="$merchant['store_percent_single_type'] eq 1">checked</if> />
					</label>
				</span>
				<span class="cb-disable">
					<label class="cb-disable <if condition="$merchant['store_percent_single_type'] eq 2">selected</if>">
						<span>按比例结算</span>
						<input type="radio" name="store_percent_single_type" value="2" <if condition="$merchant['store_percent_single_type'] eq 2">checked</if> />
					</label>
				</span>
				<input type="text" class="input fl" name="store_percent_single" size="25" placeholder="快速买单/到店付的结算方式" value="{pigcms{$merchant.store_percent_single|floatval}" tips="商家设置0或者空则向上寻找系统的设置"/></td>
			</tr>
			</if>
			
			
			
			<if condition="!isset($config['not_o2o_demo'])">
			<tr>
				<th width="160">联系电话</th>
				<td>
			
				<input type="text" class="input fl" name="phone" value="{pigcms{$merchant.phone}" size="25" placeholder="联系人的电话" validate="required:true,mobile:true" tips=""/></td>
			</tr>
			</if>
			<tr>
				<th width="160">联系邮箱</th>
				<td><input type="text" class="input fl" name="email" value="{pigcms{$merchant.email}" size="25" placeholder="可不填写" validate="email:true" tips="只供管理员后台记录，前台不显示"/></td>
			</tr>
			<tr style="display:none;">
				<th width="160">对账周期</th>
				<td><input type="text" class="input fl" name="bill_period" value="{pigcms{$merchant.bill_period}" size="25" placeholder="可不填写" validate="number:true,min:0" tips="对账周期，填0则按系统对账周期计算,最小为一天"/></td>
			</tr>
			<if condition="$system_session['area_type'] neq 3">
			<tr>
				<th width="160">所在区域</th>
				<td id="choose_cityarea" street_id="{pigcms{$merchant.street_id|default=0}" province_id="{pigcms{$merchant.province_id}" city_id="{pigcms{$merchant.city_id}" area_id="{pigcms{$merchant.area_id}" circle_id="-1"></td>
			</tr>
			<else />
				<tr>
					<th width="160">所在区域</th>
					<td>
					{pigcms{$merchant.province_name} {pigcms{$merchant.city_name} {pigcms{$merchant.area_name} {pigcms{$merchant.street_name}
					</td>
				</tr>
			</if>
            <tr>
                <th width="160">详细地址</th>
                <td><input type="text" class="input fl" name="address" value="{pigcms{$merchant.address}" size="50" tips=""/></td>
            </tr>
			<tr>
				<th width="160">到期时间</th>
				<td><input type="text" class="input fl" name="merchant_end_time" value="{pigcms{$merchant.merchant_end_time}" size="25" onfocus="WdatePicker({readOnly:true,dateFmt:'yyyy-MM-dd HH:mm'})" tips="商家到期时间，到期之后不允许进入商家平台并关闭该商家！清空为永不过期"/></td>
			</tr>
			<tr>
				<th width="160">商家状态</th>
				<td>
					<span class="cb-enable"><label class="cb-enable <if condition="$merchant['status'] neq 0">selected</if>"><span>启用</span><input type="radio" name="status" value="1" <if condition="$merchant['status'] neq 0">checked="checked"</if> /></label></span>
					<span class="cb-disable"><label class="cb-disable <if condition="$merchant['status'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="status" value="0" <if condition="$merchant['status'] eq 0">checked="checked"</if>/></label></span>
				</td>
			</tr>
			<if condition="$config['single_system_type'] neq 'village_group'">
				<tr style="display:none">
					<th width="160">线下支付</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_close_offline'] eq 0">selected</if>"><span>启用</span><input type="radio" name="is_close_offline" value="0" <if condition="$merchant['is_close_offline'] eq 0">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_close_offline'] eq 1">selected</if>"><span>关闭</span><input type="radio" name="is_close_offline" value="1" <if condition="$merchant['is_close_offline'] eq 1">checked="checked"</if>/></label></span>
					</td>
				</tr>
				<tr>
					<th width="160">签约商家</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$merchant['issign'] eq 1">selected</if>"><span>是</span><input type="radio" name="issign" value="1" <if condition="$merchant['issign'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable  <if condition="$merchant['issign'] neq 1">selected</if>"><span>否</span><input type="radio" name="issign" value="0"  <if condition="$merchant['issign'] neq 1">checked="checked"</if> /></label></span>
						<em class="notice_tips" tips="开启后商家中心会显示此商家已签约标签即商家是优质客户，所有新增的产品都无需审核"></em>
					</td>
				</tr>
				<tr>
					<th width="160">认证商家</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$merchant['isverify'] eq 1">selected</if>"><span>是</span><input type="radio" name="isverify" value="1" <if condition="$merchant['isverify'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$merchant['isverify'] neq 1">selected</if>"><span>否</span><input type="radio" name="isverify" value="0"  <if condition="$merchant['isverify'] neq 1">checked="checked"</if> /></label></span>
						<em class="notice_tips" tips="开启后商家中心会显示此商家已认证标签"></em>
					</td>
				</tr>
				<tr>
					<th width="160">使用公众号</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_open_oauth'] eq 1">selected</if>"><span>允许</span><input type="radio" name="is_open_oauth" value="1" <if condition="$merchant['is_open_oauth'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_open_oauth'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="is_open_oauth" value="0" <if condition="$merchant['is_open_oauth'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
			<if condition="$config['is_open_weidian']">
				<tr>
					<th width="160">开微店</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_open_weidian'] eq 1">selected</if>"><span>允许</span><input type="radio" name="is_open_weidian" value="1" <if condition="$merchant['is_open_weidian'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_open_weidian'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="is_open_weidian" value="0" <if condition="$merchant['is_open_weidian'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
			<if condition="isset($config['group_page_row']) && isset($config['now_scenic']) && $config['now_scenic'] neq 2">
				<tr>
					<th width="160">开通景区</th>
					<td>
						<span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_open_scenic'] eq 1">selected</if>"><span>允许</span><input type="radio" name="is_open_scenic" value="1" <if condition="$merchant['is_open_scenic'] eq 1">checked="checked"</if> /></label></span>
						<span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_open_scenic'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="is_open_scenic" value="0" <if condition="$merchant['is_open_scenic'] eq 0">checked="checked"</if>/></label></span>
					</td>
				</tr>
			</if>
            <if condition=" $config['is_open_jd_goods'] eq 1">
                <tr  data-id="{pigcms{$config['merchant_wholesale_control']}">
                    <th width="160">是否开启同步京东商品</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_open_jd'] eq 1">selected</if>"><span>允许</span><input type="radio" name="is_open_jd" value="1" <if condition="$merchant['is_open_jd'] eq 1">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_open_jd'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="is_open_jd" value="0" <if condition="$merchant['is_open_jd'] eq 0">checked="checked"</if>/></label></span>
                     </td>
                </tr>
            </if>
            <!--批发-->
			<if condition="$config['single_system_type'] neq 'village_group'">
                <php>if ($config['merchant_wholesale_control'] == 1) {</php>
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
                <php>}</php>

                <php>if ($config['merchant_integral_mall_control'] == 1) {</php>
                    <tr>
                        <th width="160">是否允许批发来的商品发布到商家积分商城</th>
                        <td>
                            <span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_publish_integral_mall'] eq 1">selected</if>"><span>允许</span><input type="radio" name="is_publish_integral_mall" value="1" <if condition="$merchant['is_publish_integral_mall'] eq 1">checked="checked"</if> /></label></span>
                            <span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_publish_integral_mall'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="is_publish_integral_mall" value="0" <if condition="$merchant['is_publish_integral_mall'] eq 0">checked="checked"</if>/></label></span>
                        </td>
                    </tr>
                <php>}</php>
                <tr>
                    <th width="160">是否允许自主添加商家积分商城商品</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$merchant['is_add_integral_mall'] eq 1">selected</if>"><span>允许</span><input type="radio" name="is_add_integral_mall" value="1" <if condition="$merchant['is_add_integral_mall'] eq 1">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$merchant['is_add_integral_mall'] eq 0">selected</if>"><span>禁止</span><input type="radio" name="is_add_integral_mall" value="0" <if condition="$merchant['is_add_integral_mall'] eq 0">checked="checked"</if>/></label></span>
                    </td>
                </tr>
                <php> if (C('config.mall_third_deliver_open')) {</php>
                <tr>
                    <th width="160">是否开启第三方配送</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$merchant['open_third_deliver'] eq 1">selected</if>"><span>开启</span><input type="radio" name="open_third_deliver" value="1" <if condition="$merchant['open_third_deliver'] eq 1">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$merchant['open_third_deliver'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="open_third_deliver" value="0" <if condition="$merchant['open_third_deliver'] eq 0">checked="checked"</if>/></label></span>
                    </td>
                </tr>
                <php> }</php>
				<if condition="$can_percent">
				<tr>
					<th width="160">批发抽成(%)</th>
					<td><input type="text" class="input fl" name="market_percent" value="{pigcms{$merchant.market_percent}" size="25" tips="批发抽成，请填写百分比，不要填写%"/></td>
				</tr>
                    <php>if ($draw_in_settings) {</php>
                        <tr>
                            <th width="160">抽成设置</th>
                            <td>
                            <a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/edit_percent',array('mer_id'=>$merchant['mer_id'],'type'=>'merchant'))}','编辑商家抽成比例',800,560,true,false,false,null,'edit_percent',true);" style="color:blue">设置商家抽成比例</a>&nbsp;&nbsp;&nbsp;&nbsp;
                        </tr>
                    <php>}</php>
				</if>
				<!--<if condition="$config['is_open_merchant_discount'] eq 1">
				<tr>
					<th width="160">最多折扣订单数</th>
					<td><input type="text" class="input fl" name="discount_order_num" value="{pigcms{$merchant.discount_order_num}" size="25" tips="该商家最多有多少单享有该优惠折扣，0表示不限制折扣订单数"/></td>
				</tr>
				<tr>
					<th width="160">商家折扣比例</th>
					<td><input type="text" class="input fl" name="discount_percent" value="{pigcms{$merchant.discount_percent}" size="25" tips="开启后，用户在该商家下所有业务所有店铺付款时会优先享用该折扣（填写0-100的数字，数字越大优惠的越少，如99，就按照 99%进行优惠, 0和100都是无折扣）"/></td>
				</tr>
				</if>-->
				<if condition="$config.open_juhepay eq 1">
				<tr>
					<th width="160">聚合支付</th>
					<td>

					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/juhepay',array('mer_id'=>$merchant['mer_id']))}','编辑商家聚合支付',400,200,true,false,false,null,'edit_juhe',true);" style="color:blue">编辑商家聚合支付</a>&nbsp;&nbsp;&nbsp;&nbsp;
				
					</td>

				</tr>
				</if>
				<if condition="$config.open_weikebaba eq 1">
				<tr>
					<th width="160">weikebaba信息</th>
					<td>

					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/weikebaba',array('mer_id'=>$merchant['mer_id']))}','编辑weikebaba商家信息',400,300,true,false,false,null,'edit_weikebaba',true);" style="color:blue">编辑商家weikebaba信息</a>&nbsp;&nbsp;&nbsp;&nbsp;
				
					</td>

				</tr>
				</if>
				<tr>
					<th width="160">分佣设置</th>
					<td>

					<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/edit_rate',array('mer_id'=>$merchant['mer_id']))}','编辑商家推广分佣比例',800,560,true,false,false,null,'edit_rate',true);" style="color:blue">设置商家推广分佣比例</a>&nbsp;&nbsp;&nbsp;&nbsp;
					<if condition="$config.close_system_spread neq 1">
						<a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/edit_user_rate',array('mer_id'=>$merchant['mer_id']))}','用户分佣设置',800,560,true,false,false,null,'edit_user_rate',true);" style="color:blue">设置用户分佣比例</a>
					</if>
					</td>

				</tr>
				<tr>
					<th width="160">线上&线下支付设置</th>
					<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/edit_offline',array('mer_id'=>$merchant['mer_id']))}','设置线下支付',800,560,true,false,false,null,'edit_offline',true);" style="color:blue">设置线上&线下支付</a></td>
				</tr>
				<tr>
					<th width="160">{pigcms{$config.score_name}使用设置</th>
					<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/edit_score',array('mer_id'=>$merchant['mer_id']))}','设置不同业务{pigcms{$config.score_name}最大使用数量',800,560,true,false,false,null,'edit_score',true);" style="color:blue">设置不同业务{pigcms{$config.score_name}使用数量</a></td>
				</tr>
				<tr>
					<th width="160">商家权限设置</th>
					<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/menu',array('mer_id'=>$merchant['mer_id']))}','设置商家使用权限',800,600,true,false,false,null,'menu',true);" style="color:blue">设置商家使用权限</a><td>
				</tr>
				<if condition="$config['is_open_merchant_foodshop_discount'] eq 1">
				<tr>
					<th width="160">商家餐饮折扣设置</th>
					<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/m_discount',array('mer_id'=>$merchant['mer_id']))}','设置商家餐饮折扣',700,500,true,false,false,null,'m_discount',true);" style="color:blue">设置商家餐饮折扣</a><td>
				</tr>
				</if>
				<if condition="$config['mer_card_union'] eq 1">
				<tr>
					<th width="160">商家会员卡联盟设置</th>
					<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/mer_card_union',array('mer_id'=>$merchant['mer_id']))}','商家会员卡联盟设置',700,500,true,false,false,null,'mer_card_union',true);" style="color:blue">商家会员卡联盟设置</a><td>
				</tr>
				</if>

				<if condition="C('customization.area_commisson_mer_set') eq 1">
				<tr>
					<th width="160">区域代理商佣金设置</th>
					<td><a href="javascript:void(0);" onclick="window.top.artiframe('{pigcms{:U('Merchant/areaCommission',array('mer_id'=>$merchant['mer_id'],'type'=>'merchant'))}','区域代理商佣金设置',700,500,true,false,false,null,'areaCommission',true);" style="color:blue">区域代理商佣金设置</a><td>
				</tr>
				</if>

                <if condition="$config['mall_confirm_self_delivery'] eq 1">
                <tr>
                    <th width="160">{pigcms{$config.mall_alias_name_new}是否开启用户确认自提</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition='$merchant["mall_confirm_self_delivery"] eq 1'>selected</if>"><span>开启</span><input type="radio" name="mall_confirm_self_delivery" value="1" <if condition="$merchant['mall_confirm_self_delivery'] eq 1">checked="checked"</if> /></label></span>
                        <span class="cb-disable"><label class="cb-disable <if condition="$merchant['mall_confirm_self_delivery'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="mall_confirm_self_delivery" value="0" <if condition="$merchant['mall_confirm_self_delivery'] eq 0">checked="checked"</if>/></label></span>
                    </td>
                </tr>
                </if>


				<php>if($config['open_company_advance_deposit'] == 1){</php>
				<tr>
					<th width="160">开启校园充值</th>
					<td> 
						<span class="cb-enable"><label class="cb-enable <if condition='$merchant["open_school"] eq 1'>selected</if>"><span>开启</span><input type="radio" name="open_school" value="1" <if condition="$merchant['open_school'] eq 1">checked="checked"</if> /></label></span>
                            <span class="cb-disable"><label class="cb-disable <if condition="$merchant['open_school'] eq 0">selected</if>"><span>关闭</span><input type="radio" name="open_school" value="0" <if condition="$merchant['open_school'] eq 0">checked="checked"</if>/></label></span>
                    <td>
				</tr>
				<php>}</php>
				<tr>
					<th width="160">平台{pigcms{$config.score_name}</th>
					<td><input type="text" class="input fl" name="plat_score" value="{pigcms{$merchant.plat_score}" size="10" placeholder="0" tips="平台{pigcms{$config.score_name}"/></td>
				</tr>
				<php>if($config['buy_merchant_auth']){</php>
				<tr>
					<th width="160">权限套餐</th>
					<td>
						
						<select name="authority_group_id" tips="权限套餐">
							<option value="0">不选</option>	
							<volist name="authority_group" id="vo">
								<option value="{pigcms{$vo.id}" <php>if($merchant['authority_group_id'] == $vo['id']){</php>selected="selected"<php>}</php>>{pigcms{$vo.name}</option>								
							</volist>
						</select>
					</td>
					
				</tr>
				<php>}</php>
			</if>

			<tr>
				<th width="120">绑定小区ID</th>
				<td><textarea name="village_ids" tips="商家可以直接绑定小区ID，绑定后商家后台可以直接访问小区后台；多个小区ID用“,”号分割。"/>{pigcms{$merchant.village_ids}</textarea> </td>
			</tr>

			<if condition="C('config.pay_ebankpay_open') eq 1">
				<tr><th colspan="2" style="color: red;text-align:center"> 日照银行设置 </th></tr>
				<tr>
					<th width="160">子商户号</th>
					<td><input type="text" class="input fl" name="ebankpay_smchid" size="25" value="{pigcms{$merchant['ebankpay_smchid']}" validate="" />
					</td>
				</tr>
			</if>
            <if condition="C('customization.internal_membership_card') eq 1">
                <tr><th colspan="2" style="color: red;text-align:center"> 淼迈云设置 </th></tr>
                <tr>
                    <th width="160">是否开启淼迈云</th>
                    <td>
                        <span class="cb-enable"><label class="cb-enable <if condition="$merchant['miaomaiyun_switch'] eq 1">selected</if>"><span>允许</span><input type="radio" name="miaomaiyun_switch" value="1" <if condition="$merchant['miaomaiyun_switch'] eq 1">checked="checked"</if> /></label></span>
                        <if condition="$merchant['miaomaiyun_switch'] eq 0">
                        <span class="cb-disable"><label class="cb-disable"><span>禁止</span><input type="radio" name="miaomaiyun_switch" value="0"/></label></span>
                        </if>    
                    </td>
                </tr>
                <tr>
                    <th width="160">淼迈云ERP 请求地址</th>
                    <td><input type="text" class="input fl" name="miaomaiyun_url" size="25" value="{pigcms{$merchant['miaomaiyun_url']}" validate="" />
                    </td>
                </tr>
                <tr>
                    <th width="160">淼迈云ERP 应用ID</th>
                    <td><input type="text" class="input fl" name="miaomaiyun_erp_appid" size="25" value="{pigcms{$merchant['miaomaiyun_erp_appid']}" validate="" />
                    </td>
                </tr>
                <tr>
                    <th width="160">淼迈云ERP 应用密钥</th>
                    <td><input type="text" class="input fl" name="miaomaiyun_erp_appsecret" size="25" value="{pigcms{$merchant['miaomaiyun_erp_appsecret']}" validate="" />
                    </td>
                </tr>
            </if>
            
			<if condition="!$config['single_system'] && false">
				<tr><th colspan="2" style="color: red;text-align:center"> 超级广告设置 </th></tr>
				<tr>
					<th width="160">首页宣传状态</th>
					<td>
						<select name="share_open" class="valid">
						<option value="0" <if condition="$merchant['share_open'] eq 0">selected="selected"</if>>关闭</option>
						<option value="1" <if condition="$merchant['share_open'] eq 1">selected="selected"</if>>开启显示商家信息</option>
						<option value="2" <if condition="$merchant['share_open'] eq 2">selected="selected"</if>>开启跳转到商家微网站</option>
						</select>
					</td>
				</tr>

				<tr>
					<th width="160">绑定业务员</th>
					<td>
						<if condition="$merchant.invit_code neq ''">
							已绑定
						<else />
							未绑定
						</if>
						&nbsp;&nbsp;&nbsp;&nbsp;
						<input type="text" class="input fl" name="invit_code" value="{pigcms{$merchant.invit_code}" size="25" placeholder="可不填写" <if condition="$now_merchant.invit_code neq ''">readOnly</if>/>
						
					</td>
				</tr>
				<tr>
					<th width="160">广告语</th>
					<td><input type="text" class="input fl" name="a_title" value="{pigcms{$home_share.title}" size="25" placeholder="可不填写" tips="粉丝看到自己的第一次进入本站来自哪个商家的店铺"/></td>
				</tr>
				<tr>
					<th width="160">进入提示语</th>
					<td><input type="text" class="input fl" name="a_name" value="{pigcms{$home_share.a_name}" size="5" placeholder="可不填写" tips="提示粉丝进入的提示语言"/></td>
				</tr>
				<tr>
					<th width="160">进入网址</th>
					<td><input type="text" class="input fl" name="a_href" value="{pigcms{$home_share.a_href}" size="60" placeholder="可不填写" tips="跳转到指定地方的网址"  validate="url:true"/></td>
				</tr>
			</if>

            <if condition="$merchant['diy_form_data']">
                <tr><th colspan="2" style="color: red;text-align:center"> 入驻填写项</th></tr>
                <volist name="merchant.diy_form_data" id="f">
                    <tr>
                        <th width="160">{pigcms{$f.name}</th>
                        <if condition="in_array($f['type'],[2,4])">
                            <td>
                                <volist name="f.value" id="img">
                                    <img class="view_msg_express" max-width="800" src="{pigcms{$img|replace_file_domain=###}"/>
                                </volist>
                            </td>
                        <else/>
                            <td>{pigcms{$f.value}</td>
                        </if>
                    </tr>
                </volist>
            </if>

            <if condition="$lichu_config">
                <tr><th colspan="2" style="color: red;text-align:center"> 利楚扫呗商户配置</th></tr>
                <tr>
                    <th width="160">商户号</th>
                    <td><input type="text" class="input fl" name="lichu_merchant_no" value="{pigcms{$merchant.lichu_merchant_no}" size="15" placeholder="" tips="利楚商户号"/></td>
                </tr>
                <tr>
                    <th width="160">终端号</th>
                    <td><input type="text" class="input fl" name="lichu_terminal_id" value="{pigcms{$merchant.lichu_terminal_id}" size="8" placeholder="" tips="利楚终端号"/></td>
                </tr>
                <tr>
                    <th width="160">密钥</th>
                    <td><input type="text" class="input fl" name="lichu_token" value="{pigcms{$merchant.lichu_token}" size="32" placeholder="" tips="利楚密钥"/></td>
                </tr>
            </if>

			<if condition="$qianzhangtong">
				<tr><th colspan="2" style="color: red;text-align:center"> 拉卡拉支付设置 </th></tr>
				<tr>
					<th width="160">外部会员标识</th>
					<td>{pigcms{$qianzhangtong.out_member_no}</td>
				</tr>
				<tr>
					<th width="160">会员编号</th>
					<td>{pigcms{$qianzhangtong.member_no}</td>
				</tr>
				<tr>
					<th width="160">手机号</th>
					<td>{pigcms{$qianzhangtong.mobile}</td>
				</tr>
				<tr>
					<th width="160">会员状态</th>
					<td>
						<php>
							$desc = [
								'1' => '有效',
								'10' => '锁定',
								'20' => '关闭',
								'99' => '待审核',
							];
							echo $desc[$qianzhangtong['member_state']]??'已提交申请';
						</php>
					</td>
				</tr>
				<tr>
					<th width="160">渠道商户号</th>
					<td>{pigcms{$qianzhangtong.channel_merchant_no}</td>
				</tr>
				<tr>
					<th width="160">渠道终端号</th>
					<td>{pigcms{$qianzhangtong.channel_terminal_no}</td>
				</tr>
				<tr>
					<th width="160">钱账通商户号</th>
					<td>{pigcms{$qianzhangtong.merchant_no}</td>
				</tr>
				<tr>
					<th width="160">商户状态</th>
					<td>
						<php>
							$desc = [
								'1' => '审核中',
								'2' => '进件中',
								'3' => '有效',
								'4' => '无效',
							];
							echo $desc[$qianzhangtong['merchant_status']]??'未知';
						</php>
					</td>
				</tr>
			</if>
		</table>
		<div class="btn hidden">
			<input type="button" name="dosubmit" id="dosubmit" onsubmit="return false" onclick="handleSubmit()" value="提交" class="button" />
			<input type="reset" value="取消" class="button" />
		</div>
	</form>
    <style type="text/css">
    .view_msg_express,.img_prev{
        max-height: 65px;
        padding: 2px;
        margin: 2px;
        border: 1px solid #ddd;
    }
    </style>
	
	<script>

		$.fn.serializeObject = function() {
			var o = {};
			var a = this.serializeArray();
			$.each(a, function() {
				if (o[this.name]) {
					if (!o[this.name].push) {
						o[this.name] = [ o[this.name] ];
					}
					o[this.name].push(this.value || '');
				} else {
					o[this.name] = this.value || '';
				}
			});
			return o;
		};

		function handleSubmit(){
			let data = $('#myform').serializeObject(); 
			if(data.status == 0 && !confirm('关闭商家后，商家下的店铺都将关闭，确认关闭吗？')){
				return false;
			} 
			$('#myform').submit();
		}
		$(function(){
			if($('input[name="open_sub_mchid"]:checked').val()==1){
				$('.sub_mch').show();
			}else{
				$('.sub_mch').hide();
			}
			var sub_mch_id = $('input[name="sub_mch_id"]').val();
			var alipay_auth_code = $('input[name="alipay_auth_code"]').val();
			$('input[name="sub_mch_id"]').change(function(){
				sub_mch_id=  $(this).val();
			})
			
			$('input[name="alipay_auth_code"]').change(function(){
				alipay_auth_code=  $(this).val();
			})
			
			$('input[name="open_sub_mchid"]').click(function(){
				var sub = $(this);
				if(sub.val()==1){
					$('input[name="sub_mch_id"]').val(sub_mch_id)
					$('input[name="alipay_auth_code"]').val(alipay_auth_code)
					$('.sub_mch').show();
				}else{
					$('input[name="sub_mch_id"]').val('')
					$('input[name="alipay_auth_code"]').val('')
					$('.sub_mch').hide();
				}
			});
			
			if($('input[name="score_get_percent"]').val()<0){
				$('input[name="score_get_percent"]').hide();
			}else{
				$('input[name="score_get_percent"]').show();
			}
			
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

			// 查看大图
			$('.image_show').click(function(){
				var src = $(this).attr('src');
				layer.open({
                    content: '<img  class="image_show_big" style="width:100%" src="' + src + '"></img>'
                    , btn: ['关闭']
                     ,area: ['600px', '500px']
                    , yes: function (index) {
                    	layer.closeAll()
                    }
                });
			});
		});
	</script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
<script type="text/javascript">
    KindEditor.ready(function(K){
        var editor = K.editor({
            allowFileManager : true
        });
        $('.J_selectImage').click(function(){
            var upload_file_btn = $(this);
            editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}&type=merchant";
            editor.loadPlugin('image', function(){
                editor.plugin.imageDialog({
                    showRemote : false,
                    clickFn : function(url) {
                        upload_file_btn.siblings('#avatar_show').show();
                        upload_file_btn.siblings('.input-image').val(url);

                        var html='<li class="upload_pic_li" style="float:left;list-style:none;"><img class="image_show" src="'+url+'" style="margin-right:5px;"><br></li>';
                        upload_file_btn.siblings('.avatar_list').html(html)
                        editor.hideDialog();
                    }
                });
            });
        });
    })

</script>
<include file="Public:footer"/>