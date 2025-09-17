<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Config/merchant')}">{pigcms{:L_("商家设置")}</a>
			</li>
			<li class="active">{pigcms{:L_("商家设置")}</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="page-content-area">
			<style>
				.ace-file-input a {display:none;}
			</style>
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">
							<li class="active">
								<a data-toggle="tab" href="#basicinfo">{pigcms{:L_("基本设置")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtstore">{pigcms{:L_("商家描述")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#txtpwd">{pigcms{:L_("修改密码")}</a>
							</li>
							<if condition="$config.open_merchant_change_phone eq 1">
							<li>
								<a data-toggle="tab" href="#txtPhone">{pigcms{:L_("修改手机")}</a>
							</li>
							</if>
							<li>
								<a data-toggle="tab" href="#bindUser">{pigcms{:L_("管理账号")}</a>
							</li>
                            <li>
                                <a data-toggle="tab" href="#invoice">{pigcms{:L_("电子发票设置")}</a>
                            </li>
							<li style="display:none;">
								<a data-toggle="tab" href="#AllinyunUser">{pigcms{:L_("云商通")}</a>
							</li>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="edit_form">
						<div class="tab-content">
							<div id="basicinfo" class="tab-pane active">
								<if condition="$now_merchant['account']">
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("商户帐号")}</label></label>
										<span style="line-height:32px;color:#939192;"><notempty name="now_merchant.account">{pigcms{$now_merchant.account}<else />{pigcms{$now_merchant.phone}</notempty></span>
									</div>
								</if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("商户名称")}</label></label>
                                    <span style="line-height:32px;color:#939192;">{pigcms{$now_merchant.name}</span>
                                </div>
                                <if condition="$config['merchant_type_switch'] eq 1">
                                    <if condition="$now_merchant['company_name']">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label>{pigcms{:L_("公司名称")}</label></label>
                                            <span style="line-height:32px;color:#939192;">{pigcms{$now_merchant['company_name']}</span>
                                        </div>
                                    </if>
                                    <if condition="$type_info">
                                        <div class="form-group">
                                            <label class="col-sm-1"><label>{pigcms{:L_("商户类型")}</label></label>
                                            <span style="line-height:32px;color:#939192;">{pigcms{$type_info['type_name']}</span>
                                        </div>
                                    </if>
                                </if>
                                <if condition="$config['open_multilingual'] eq '1'">
									<div class="form-group">
										<label class="col-sm-1" for="merchant_default_lang">{pigcms{:L_("日常使用语言")}</label>
										<select name="merchant_default_lang" id="merchant_default_lang">
											<volist name="service_lang" id="lang">
												<option value="{pigcms{$lang['val']}" <if condition="$now_merchant.merchant_default_lang eq $lang['val']">selected="selected"</if>>{pigcms{$lang['display']}</option>
											</volist>
										</select>
									</div>
                                </if>
                                <div class="form-group">
                                    <label class="col-sm-1"><label>{pigcms{:L_("商户简介")}</label></label>
                                    <input class="col-sm-2" size="30" name="s_txt" value="{pigcms{$now_merchant.s_txt}" id="s_txt" type="text"/>
                                    <span class="form_tips">{pigcms{:L_("请填写简洁有力的介绍，限30字")}</span>   
								</div>
								<if condition="$config.open_admin_code eq 1">
									<div class="form-group">
										<label class="col-sm-1"><label>{pigcms{:L_("邀请码")}</label></label>
										<input class="col-sm-2" size="20" name="invit_code" value="{pigcms{$now_merchant.invit_code}" id="invit_code" type="text" <if condition="$now_merchant.invit_code neq ''">readOnly</if>/>
									</div>
								</if>
								<if condition="!isset($config['not_o2o_demo'])">
									<div class="form-group">
										<label class="col-sm-1"><label for="phone">{pigcms{:L_("联系电话")}</label></label>
										<input class="col-sm-2" size="20" name="phone" value="{pigcms{$now_merchant.phone}" id="phone" type="text" <if condition="$config['open_merchant_change_phone'] eq 1 && !empty($now_merchant['phone'])">readOnly</if> />
										<span class="form_tips">{pigcms{:L_("多个手机号码时，在修改手机号时将使用第一个手机号发送验证码且只可修改第一个手机号，建议只填写一个手机号")}</span>
									</div>
								</if>
								<div class="form-group">
									<label class="col-sm-1"><label for="email">{pigcms{:L_("商家邮箱")}</label></label>
									<input class="col-sm-2" size="20" name="email" value="{pigcms{$now_merchant.email}" id="email" type="text"/>
									<span class="form_tips">{pigcms{:L_("可选，建议填写")}</span>
								</div>
								<if condition="$config['group_alias_name']">
									<div class="form-group" style="display:<if condition="!$config['single_system']">block<else/>none</if>;">
										<label class="col-sm-1"><label>{pigcms{$config.group_alias_name}{pigcms{:L_("快递收货超时时间")}</label></label>
										<input class="col-sm-2" size="20" name="group_express_outtime" value="{pigcms{$now_merchant.group_express_outtime}" type="text"/><span class="form_tips">0{pigcms{:L_("为永不超时，1为一天后超时并确认消费。（店员发货后开始计时）")}</span>
									</div>
									<div class="form-group" style="display:<if condition="!$config['single_system']">block<else/>none</if>;">
										<label class="col-sm-1" for="group_qrcode_recommend">{pigcms{:L_("扫描团购商品二维码是否发送其他推荐")}</label>
										<select name="scan_group_show_other" id="group_qrcode_recommend">
											<option value="1" <if condition="$now_merchant.scan_group_show_other eq 1">selected="selected"</if>>{pigcms{:L_("推荐")}</option>
											<option value="0" <if condition="$now_merchant.scan_group_show_other eq 0">selected="selected"</if>>{pigcms{:L_("不推荐")}</option>
										</select>
										<span class="form_tips">{pigcms{:L_("扫描每个团购商品的二维码时，是否将其他推荐信息发送给用户，不推荐则每次只发送该团购商品信息")}</span>
									</div>
								</if>
                                <div class="form-group" style="display:<if condition="!$config['single_system']">block<else/>none</if>;">
                                <label class="col-sm-1" for="transfer_multi_kefu">{pigcms{:L_("用户回复无效关键词转商家自有多客服处理")}</label>
                                <select name="transfer_multi_kefu" id="transfer_multi_kefu">
                                    <option value="1" <if condition="$now_merchant.transfer_multi_kefu eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
                                    <option value="0" <if condition="$now_merchant.transfer_multi_kefu eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
                                </select>
                                </div>
								<if condition="$pay_offline_open eq 0 AND $now_merchant.is_close_offline eq 1">
									<div class="form-group" style="display:<if condition="!$config['single_system']">block<else/>none</if>;">
										<label class="col-sm-1">{pigcms{:L_("线下支付的权限")}</label>
										<label class="col-sm-2" style="color: red;">{pigcms{:L_("系统禁止您使用线下支付的权限")}</label>
									</div>
								<elseif condition="$pay_offline_open eq 1" />
									<div class="form-group" style="display:<if condition="!$config['single_system']">block<else/>none</if>;">
										<label class="col-sm-1" for="is_offline">{pigcms{:L_("线下支付的权限")}</label>
										<select name="is_offline" id="is_offline">
											<option value="0" <if condition="$now_merchant.is_offline eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$now_merchant.is_offline eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
								</if>
								<div class="form-group" style="display:<if condition="!$config['single_system']">block<else/>none</if>;">
									<label class="col-sm-1"><label>{pigcms{:L_("微官网点击量")}</label></label>
									<input class="col-sm-2" size="20" value="{pigcms{$now_merchant.hits}" type="text" style="border:none;background:white!important;" readonly="readonly"/>
								</div>
								
								
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商家")}LOGO</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectLogo">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("商家LOGO将显示在商家会员卡，以及用户在微信分享网页时会分享此图。建议上传200*200 或 宽高相同的图片")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">LOGO{pigcms{:L_("预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_logo_ul">
											<if condition="$now_merchant['logo']">
												<li class="upload_pic_li"><img src="{pigcms{:file_domain()}{pigcms{$now_merchant.logo}"/><input type="hidden" name="logo" value="{pigcms{$now_merchant.logo}"/></li>
											</if>
										</ul>
									</div>
								</div>
							</div>
							<div id="txtstore" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商家描述")}</label>
									<textarea class="col-sm-5" rows="5" name="txt_info">{pigcms{$now_merchant.txt_info}</textarea>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("商家图片")}</label>
									<a href="javascript:void(0)" class="btn btn-sm btn-success" id="J_selectImage">{pigcms{:L_("上传图片")}</a>
									<span class="form_tips">{pigcms{:L_("第一张将作为主图片！最多上传10个图片！图片宽度建议为700px，高度建议为420px。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1">{pigcms{:L_("图片预览")}</label>
									<div id="upload_pic_box">
										<ul id="upload_pic_ul">
											<volist name="now_merchant['pic']" id="vo">
												<li class="upload_pic_li"><img src="{pigcms{$vo.url}"/><input type="hidden" name="pic[]" value="{pigcms{$vo.title}"/><br/><a href="#" onclick="deleteImage('{pigcms{$vo.title}',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>
											</volist>
										</ul>
									</div>
								</div>
							</div>
							<div id="txtpwd" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1"><label for="email">{pigcms{:L_("原密码")}</label></label>
									<input class="col-sm-2" size="20" name="old_pass" type="password"/>
									<span class="form_tips">{pigcms{:L_("不修改密码可不填写")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="email">{pigcms{:L_("新密码")}</label></label>
									<input class="col-sm-2" size="20" name="new_pass" type="password"/>
									<span class="form_tips">{pigcms{:L_("不修改密码请留空，最少6个字符")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="email">{pigcms{:L_("确认密码")}</label></label>
									<input class="col-sm-2" size="20" name="re_pass" type="password"/>
									<span class="form_tips">{pigcms{:L_("请再输入一次上面的新密码，以便确保输对了")}</span>
								</div>
							</div>
							<php>if($config['open_merchant_change_phone']== 1){</php>
								<div id="txtPhone" class="tab-pane">
									<if condition="!isset($config['not_o2o_demo'])">
										<div class="form-group">
											<label class="col-sm-1"><label for="email">{pigcms{:L_("原手机")}</label></label>
											<php>$phone  = explode(' ',$now_merchant['phone']);$phone = $phone[0];</php>
											<input class="col-sm-2" size="20" name="old_phone" type="tel" value="{pigcms{$phone}" readOnly />
										</div>
									<else/>
										<php>$phone  = explode(' ',$now_merchant['phone']);$phone = $phone[0];</php>
										<input class="col-sm-2" size="20" name="old_phone" type="hidden" value="{pigcms{$phone}" readOnly />
									</if>
									<if condition="$config.open_merchant_reg_sms eq 1">
										<div class="form-group">
											<label class="col-sm-1"><label for="email">{pigcms{:L_("原手机验证码")}</label></label>
											<input class="col-sm-2" size="20" name="SmsCode" type="text" value=""/>
											&nbsp;&nbsp;&nbsp;&nbsp;
											<a href="javascript:void(0)" onclick="sendsms(this)" class="btn btn-sm btn-success">{pigcms{:L_("发送验证码")}</a>
										</div>
									</if>
									<div class="form-group">
										<label class="col-sm-1"><label for="email">{pigcms{:L_("新手机")}</label></label>
										<!--if condition="$config.international_phone eq 1">
											<select name="phone_country_type" id="phone_country_type" style="height:34px;float:left;margin-right:5px;">
											<option value="86" <if condition="$now_merchant.phone_country_type eq 86">selected</if>>+86 {pigcms{:L_("中国")} China</option>
											<option value="1" <if condition="$now_merchant.phone_country_type eq 1">selected</if>>+1 {pigcms{:L_("加拿大")} Canada</option>
											</select>
										</if-->
										<pigcms:national_phone id="phone_country_type" style="height:34px;float:left;margin-right:5px;">
										 	<option value="{pigcms{$vo.code}"  <if condition="$now_merchant.phone_country_type eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
										</pigcms:national_phone>
										<input class="col-sm-2" size="20" name="new_phone" type="tel"/>
										<span class="form_tips">{pigcms{:L_("不修改手机留空")}</span>
									</div>
									
									<if condition="$config.open_merchant_reg_sms eq 1">
										<div class="form-group">
											<label class="col-sm-1"><label for="email">{pigcms{:L_("新手机验证码")}</label></label>
											<input class="col-sm-2" size="20" name="SmsCode2" type="text" value=""/>
											&nbsp;&nbsp;&nbsp;&nbsp;
											<a href="javascript:void(0)" onclick="sendsms2(this)" class="btn btn-sm btn-success">{pigcms{:L_("发送验证码")}</a>
										</div>
									</if>
									
								</div>
										<php>}</php>
							<div id="bindUser" class="tab-pane">
								<div class="form-group" id="has_user" <if condition="!$user">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("绑定微信用户")}</label>
									<label class="col-sm-1"><label for="email" id="bind_usernickname">{pigcms{$user['nickname']}</label></label>
									<a class="form_tips" id="delete_bind">{pigcms{:L_("解除绑定")}</a>
								</div>
								<div class="form-group" id="no_user" <if condition="$user">style="display:none"</if>>
									<label class="col-sm-1">{pigcms{:L_("绑定微信用户")}</label>
									<label class="col-sm-1"><a href="{pigcms{:U('Config/see_tmp_qrcode')}" class="see_qrcode">{pigcms{:L_("查看二维码")}</a></label>
									<span class="form_tips">{pigcms{:L_("绑定微信用户之后您将可：1. 商家余额收款后会通过微信公众号通知到您。 2. 商家提现时可以提现至微信零钱账户。")}</span>
								</div>
								<div class="form-group">
									<label class="col-sm-1" for="open_money_tempnews">{pigcms{:L_("商家收款微信通知")}</label>
									<select name="open_money_tempnews" id="open_money_tempnews">
										<option value="0" <if condition="$now_merchant.open_money_tempnews eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$now_merchant.open_money_tempnews eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
								</div>
								<if condition="$config['open_bind_merchant_boss']">
	                                <div class="form-group">
	                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("绑定老板账号")}</label></label>
	                                    <input class="col-sm-2" size="15" id="boss_phone" value="{pigcms{$boss_user.phone}" type="text" />
	                                    <span class="form_tips bind_boss" style="cursor: pointer;color: #428bca;">
	                                		<if condition="empty($boss_user)">添加<else/>修改</if>
	                                	</span>
	                                </div>
	                                <div class="form-group boss_user" <if condition="empty($boss_user)">style="display:none"</if>>
	                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("用户ID")}</label></label>
	                                    <input class="col-sm-2" size="15" id="boss_uid" value="{pigcms{$boss_user.uid}" type="text" disabled/>
	                                </div>
	                                <div class="form-group boss_user" <if condition="empty($boss_user)">style="display:none"</if>>
	                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("昵称")}</label></label>
	                                    <input class="col-sm-2" size="15" id="boss_nickname" value="{pigcms{$boss_user.nickname}" type="text" disabled />
	                                </div>
                            	</if>
							</div>
                            <div id="invoice" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1" for="is_open">{pigcms{:L_("是否开启该功能")}</label>
                                    <select name="is_open" id="is_open">
                                        <option value="0" <if condition="$data_invoice.is_open eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
                                        <option value="1" <if condition="$data_invoice.is_open eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
                                    </select>
                                    <span class="form_tips">{pigcms{:L_("开启后，外卖与商城业务支持给用户开电子发票功能")}</span>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("销售方单位名称")}</label></label>
                                    <input class="col-sm-3" size="20" name="seller_department" value="{pigcms{$data_invoice.seller_department}" type="text" placeholder="请输入销售方单位名称"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("税号")}</label></label>
                                    <input class="col-sm-3" size="20" name="duty" value="{pigcms{$data_invoice.duty}" type="text" placeholder="请输入税号"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("销售方地址")}</label></label>
                                    <input class="col-sm-3" size="20" name="seller_address" value="{pigcms{$data_invoice.seller_address}" type="text" placeholder="请输入销售方地址"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("销售方电话")}</label></label>
                                    <input class="col-sm-3" size="20" name="seller_phone" value="{pigcms{$data_invoice.seller_phone}" type="text" placeholder="请输入销售方电话"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("开户银行")}</label></label>
                                    <input class="col-sm-3" size="20" name="deposit_bank" value="{pigcms{$data_invoice.deposit_bank}" type="text" placeholder="请输入开户银行"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="email">{pigcms{:L_("开户账号")}</label></label>
                                    <input class="col-sm-3" size="20" name="account_number" value="{pigcms{$data_invoice.account_number}" type="text" placeholder="请输入开户账号"/>
                                </div>
                            </div>
							<div id="AllinyunUser" class="tab-pane">
								
							
								<div class="form-group">
									<label class="col-sm-1" for="open_money_tempnews">{pigcms{:L_("云商通企业账户创建")}</label>
									<if condition="$deposit.bizUserId neq ''">
										{pigcms{:L_("云商通账号：")}{pigcms{$deposit.bizUserId}
									<else />
										<a href="{pigcms{:U('SetAccountDeposit/createAllinyunAccount')}" class="btn btn-sm btn-success" id="Create_Allinyun">{pigcms{:L_("创建账号")}</a>
									</if>
									
									<if condition="!empty($deposit['phone'])">
										{pigcms{:L_("云商通绑定手机：")}{pigcms{$deposit.phone}
										<a href="{pigcms{:U('SetAccountDeposit/editphone')}" class="btn btn-sm btn-success" >{pigcms{:L_("重置手机")}</a>
									<else />
										<a href="{pigcms{:U('SetAccountDeposit/bindphone')}" class="btn btn-sm btn-success">{pigcms{:L_("绑定手机")}</a>
									</if>
									
									<a href="{pigcms{:U('SetAccountDeposit/submitVerify')}" class="btn btn-sm btn-success" id="Submit_verify"><if condition="$deposit.status eq 0">{pigcms{:L_("提交审核")}<elseif condition="$deposit.status eq 1" /> {pigcms{:L_("查看企业信息")}<else />{pigcms{:L_("编辑企业信息")}</if></a>
									
									<a href="{pigcms{:U('SetAccountDeposit/signConnect')}&sign=1" class="btn btn-sm btn-success" id="signConnect"><if condition="$deposit.sign_status eq 0">{pigcms{:L_("签约电子协议")}<elseif condition="$deposit.sign_status eq 1" /> {pigcms{:L_("已签约")}</if></a>
									
									<a href="{pigcms{:U('SetAccountDeposit/addBank')}" class="btn btn-sm btn-success" id="signConnect"><if condition="$deposit.bank_list neq ''">{pigcms{:L_("添加银行卡")}<else />{pigcms{:L_("银行卡列表")}</if></a>
								</div>
							</div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit">
										<i class="ace-icon fa fa-check bigger-110"></i>
										{pigcms{:L_("保存")}
									</button>
								</div>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>
<style>
input.ke-input-text {
background-color: #FFFFFF;
background-color: #FFFFFF!important;
font-family: "sans serif",tahoma,verdana,helvetica;
font-size: 12px;
line-height: 24px;
height: 24px;
padding: 2px 4px;
border-color: #848484 #E0E0E0 #E0E0E0 #848484;
border-style: solid;
border-width: 1px;
display: -moz-inline-stack;
display: inline-block;
vertical-align: middle;
zoom: 1;
}
.form-group>label{font-size:12px;line-height:24px;}
#upload_pic_box{margin-top:20px;height:150px;}
#upload_pic_box .upload_pic_li{width:130px;float:left;list-style:none;}
#upload_pic_box img{width:100px;height:70px;}

#upload_pic_box #upload_logo_ul img{width:70px;height:70px;}

.small_btn{
margin-left: 10px;
padding: 6px 8px;
cursor: pointer;
display: inline-block;
text-align: center;
line-height: 1;
letter-spacing: 2px;
font-family: Tahoma, Arial/9!important;
width: auto;
overflow: visible;
color: #333;
border: solid 1px #999;
-moz-border-radius: 5px;
-webkit-border-radius: 5px;
border-radius: 5px;
background: #DDD;
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#FFFFFF', endColorstr='#DDDDDD');
background: linear-gradient(top, #FFF, #DDD);
background: -moz-linear-gradient(top, #FFF, #DDD);
background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#FFF), to(#DDD));
text-shadow: 0px 1px 1px rgba(255, 255, 255, 1);
box-shadow: 0 1px 0 rgba(255, 255, 255, .7), 0 -1px 0 rgba(0, 0, 0, .09);
-moz-transition: -moz-box-shadow linear .2s;
-webkit-transition: -webkit-box-shadow linear .2s;
transition: box-shadow linear .2s;
outline: 0;
}
.small_btn:active{
border-color: #1c6a9e;
filter: progid:DXImageTransform.Microsoft.gradient(startColorstr='#33bbee', endColorstr='#2288cc');
background: linear-gradient(top, #33bbee, #2288cc);
background: -moz-linear-gradient(top, #33bbee, #2288cc);
background: -webkit-gradient(linear, 0% 0%, 0% 100%, from(#33bbee), to(#2288cc));
}
</style>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config['system_lang_alias']|default='zh_CN'}.js"></script>

<script type="text/javascript">
KindEditor.ready(function(K){
	var editor = K.editor({
		allowFileManager : true,
		langType : '{pigcms{$config['system_lang_alias']|default='zh_CN'}'
	});
	K('#J_selectImage').click(function(){
		if($('#upload_pic_ul .upload_pic_li').size() >= 10){
			alert('{pigcms{:L_("最多上传10个图片！")}');
			return false;
		}
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_pic_ul').append('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="pic[]" value="'+title+'"/><br/><a href="#" onclick="deleteImage(\''+title+'\',this);return false;">[ {pigcms{:L_("删除")} ]</a></li>');
					editor.hideDialog();
				}
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
			},200);
		});
	});
	
	K('#J_selectLogo').click(function(){
		editor.uploadJson = "{pigcms{:U('Config/ajax_upload_pic')}";
		editor.loadPlugin('image', function(){
			editor.plugin.imageDialog({
				showRemote : false,
				clickFn : function(url, title, width, height, border, align) {
					$('#upload_logo_ul').html('<li class="upload_pic_li"><img src="'+url+'"/><input type="hidden" name="logo" value="'+url+'"/></li>');
					editor.hideDialog();
				}
			});
			setTimeout(function(){
				$('.ke-dialog').css('top',(($(window).height()-$('.ke-dialog').height())/2)-5+'px');
			},200);
		});
	});
	
	$('#edit_form').submit(function(){
		$.post("{pigcms{:U('Config/merchant')}",$('#edit_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.href = "{pigcms{:U('Config/merchant')}";
			}else{
				alert(result.info);
			}
		})
		return false;
	});
	
	$('#indexsort_edit_btn').click(function(){
		$(this).prop('disabled',true).html('{pigcms{:L_("提交中")}...');
		$.post("{pigcms{:U('Config/merchant_indexsort')}",{group_indexsort:$('#group_indexsort').val(),indexsort_groupid:$('#indexsort_groupid').val()},function(result){
			alert('{pigcms{:L_("处理完成！正在刷新页面。")}');
			window.location.href = window.location.href;
		});
	});
});
function deleteImage(path,obj){
	$.post("{pigcms{:U('Config/ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
</script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_public}js/artdialog/iframeTools.js"></script>
<script type="text/javascript">
	var loop_time = null, art_index = null;
	$(function(){
		$('.see_qrcode').click(function(){
			art.dialog.open($(this).attr('href'),{
				init: function(){
					loop_time = setInterval(is_bind, 1500);
					var iframe = this.iframe.contentWindow;
					window.top.art.dialog.data('iframe_handle',iframe);
				},
				id: 'handle',
				title:'{pigcms{:L_("绑定管理账号的二维码")}',
				padding: 0,
				width: 430,
				height: 433,
				lock: true,
				resize: false,
				background:'black',
				button: null,
				fixed: false,
				close: function(){clearInterval(loop_time);},
				left: '50%',
				top: '38.2%',
				opacity:'0.4'
			});
			return false;
		});

		$('#delete_bind').click(function(){
			art.dialog.confirm('{pigcms{:L_("你确定要解除绑定的用户吗？")}', function () {
				$.get("{pigcms{:U('Config/ajax_del_binduser')}", function(response){
					if (response.error_code) {
						art.dialog.tips(response.msg);
					} else {
						$('#has_user').hide();
						$('#no_user').show();
					}
				}, 'json');
			}, function () {});
		});

		$('.bind_boss').click(function(){
			var old_phone = "{pigcms{$boss_user['phone']}"
			console.log(old_phone)
			if($('#boss_phone').val() == old_phone){
				return false;
			}
			art.dialog.confirm('{pigcms{:L_("你确定要绑定该用户为老板账号吗？")}', function () {
				$.post("{pigcms{:U('Config/ajax_bind_boss')}",{phone:$('#boss_phone').val()}, function(response){
					if (response.error_code) {
						art.dialog.tips(response.msg);
					} else {
						$('.bind_boss').html('修改')
						$('.boss_user').show();
						$('#boss_uid').val(response.user.uid);
						$('#boss_nickname').val(response.user.nickname);
					}
				}, 'json');
			}, function () {});
		});
	});
	var countdown = 60;
	function sendsms(val){
		
			var phone =  $("input[name='old_phone']").val();
	
			if(phone==''){
				alert('{pigcms{:L_("手机号码不能为空！")}');
			}else{
				
				
				if(countdown==60){
					$.ajax({
						url: '{pigcms{:U('sendsms')}',
						type: 'POST',
						dataType: 'json',
						data: {phone:phone, phone_country_type:'{pigcms{$now_merchant['phone_country_type']}'},
						success:function(date){
							if(date.status==0){
								// alert(date.info)
							}
						}

					});
				}
				if (countdown == 0) {
					val.removeAttribute("disabled");
					$(val).html("{pigcms{:L_("验证短信")}");
					countdown = 60;
					//clearTimeout(t);
				} else {
					val.setAttribute("disabled", true);
					$(val).html("{pigcms{:L_("重新发送")}(" + countdown + ")");
					countdown--;
					setTimeout(function() {
						sendsms(val);
					},1000)
				}
			}
		}
		var countdown2 = 60;
		function sendsms2(val){
			
		
			var phone = $("input[name='new_phone']").val()
			
		
			if(phone==''){
				alert('{pigcms{:L_("手机号码不能为空！")}');
			}else{
				
				
				if(countdown2==60){
					$.ajax({
						url: '{pigcms{:U('sendsms2')}',
						type: 'POST',
						dataType: 'json',
						data: {newphone:phone, phone_country_type: $('#phone_country_type').val()},
						success:function(date){
							if(date.status==0){
								// alert(date.info)
							}
						}

					});
				}
				if (countdown2 == 0) {
					val.removeAttribute("disabled");
					$(val).html("{pigcms{:L_("验证短信")}");
					countdown2 = 60;
					//clearTimeout(t);
				} else {
					val.setAttribute("disabled", true);
					$(val).html("{pigcms{:L_("重新发送")}(" + countdown2 + ")");
					countdown2--;
					setTimeout(function() {
						sendsms2(val);
					},1000)
				}
			}
		}
	function is_bind()
	{
		$.get("{pigcms{:U('Config/has_bind')}", function(response){
			if (response.error_code == false) {
				$('#bind_usernickname').html(response.nickname);
				$('#has_user').show();
				$('#no_user').hide();
				clearInterval(loop_time);
				art.dialog.list['handle'].close();
			}
		}, 'json');
	}
</script>
<include file="Public:footer"/>
