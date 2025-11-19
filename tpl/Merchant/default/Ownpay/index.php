<include file="Public:header"/>
<div class="main-content">
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Ownpay/index')}">{pigcms{:L_("自有支付配置")}</a>
			</li>
		</ul>
	</div>
	<!-- {pigcms{:L_("内容头部")} -->
	<div class="page-content">
		<div class="alert alert-info" style="margin:10px 0;">
			<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
			{pigcms{:L_("开启自有支付，则用户会优先使用自有支付！例如配置了微信支付，则支付时显示的“微信支付”会调用自有支付。")}<br/>
			<!-- {pigcms{:L_("【注】开启了自有支付后店铺就不能使用")}{pigcms{$config['deliver_name']}{pigcms{:L_("了！！！")} -->
		</div>
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">	
							<if condition="$config['pay_weixin_open']">
								<li class="active">
									<a data-toggle="tab" href="#weixin">{pigcms{:L_("微信支付")}</a>
								</li>
							</if>
							<li <if condition="!$hasBind && !$config['pay_weixin_open']"> class="active"</if>>
								<a data-toggle="tab" href="#tenpay">{pigcms{:L_("财付通支付")}</a>
							</li>
							<li>
								<a data-toggle="tab" href="#yeepay">{pigcms{:L_("银行卡支付(易宝支付")})</a>
							</li>
							<li>
								<a data-toggle="tab" href="#allinpay">{pigcms{:L_("银行卡支付(通联支付")})</a>
							</li>
							<li>
								<a data-toggle="tab" href="#chinabank">{pigcms{:L_("银行卡支付(网银在线")})</a>
							</li>
							<li>
								<a data-toggle="tab" href="#alipayh5">{pigcms{:L_("支付宝")}</a>
							</li>
							<!--li>
								<a data-toggle="tab" href="#alipay_app">{pigcms{:L_("支付宝")}APP</a>
							</li-->
							<li>
								<a data-toggle="tab" href="#yzfpay">{pigcms{:L_("翼支付")}</a>
							</li>
							<if condition="$config['pay_wft_open'] eq 1">
							<li>
								<a data-toggle="tab" href="#wftpay">{pigcms{:L_("网付通")}</a>
							</li>
							</if>
							<if condition="$config['pay_weifutong_open']">
								<li>
									<a data-toggle="tab" href="#weifutong">{pigcms{$config.pay_weifutong_alias_name}</a>
								</li>
							</if>
                            <if condition="$config['pay_allinpay_mer_open']">
								<li>
									<a data-toggle="tab" href="#allinpay_mer">{pigcms{:L_("通联支付")}</a>
								</li>
							</if>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_form">
						<div class="tab-content">
							<php>if($config['pay_weixin_open']){</php>
								<div id="weixin" class="tab-pane active"  >
										<php>if($config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && !empty($now_merchant['sub_mch_id']) ){</php>
										<div class="form-group">
											<span class="form_tips">{pigcms{:L_("您已经已经是平台微信服务商的子商户，微信支付将直接支付到您的子商户账号，不需要配置自有支付，如果需要配置自有支付，请向平台申请关闭子商户支付功能")}</span>
										</div>
										<php>}else{</php>
										<if condition="!$hasBind">
											<div id="weixin" class="tab-pane active" >
												<div class="form-group"    style=" margin-left: 40px;<if condition="!empty($now_merchant['sub_mch_id'])">display:none;</if>">
													<a class="btn btn-success cert-setting-btn js-wxauth-btn" href="{pigcms{$url}"><i class="ace-icon fa fa-wechat"></i>{pigcms{:L_("请先绑定微信公众号，立即设置")}</a>
												</div>
											</div>
										<else />
											<div class="form-group" <if condition="!empty($now_merchant['sub_mch_id'])">style="display:none;"</if>>
												<label class="col-sm-1" for="wxpay_open">{pigcms{:L_("是否开启")}</label>
												<select name="weixin[open]" id="wxpay_open">
													<option value="0" <if condition="$ownpay['weixin']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
													<option value="1" <if condition="$ownpay['weixin']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
												</select>
											</div>
											<div class="form-group">
												<label class="col-sm-1"><label for="pay_weixin_appid">Appid</label></label>
												<input class="col-sm-2" size="20" name="weixin[pay_weixin_appid]" id="pay_weixin_appid" type="text" value="{pigcms{$ownpay.weixin.pay_weixin_appid}"/>
											</div>
											<div class="form-group">
												<label class="col-sm-1"><label for="pay_weixin_mchid">Mchid</label></label>
												<input class="col-sm-2" size="20" name="weixin[pay_weixin_mchid]" id="pay_weixin_mchid" type="text" value="{pigcms{$ownpay.weixin.pay_weixin_mchid}"/>
											</div>
											<div class="form-group">
												<label class="col-sm-1"><label for="pay_weixin_key">Key</label></label>
												<input class="col-sm-2" size="20" name="weixin[pay_weixin_key]" id="pay_weixin_key" type="text" value="{pigcms{$ownpay.weixin.pay_weixin_key}"/>
											</div>
											<div class="form-group" style="display:none;">
												<label class="col-sm-1"><label for="pay_weixin_appsecret">Key</label></label>
												<input class="col-sm-2" size="20" name="weixin[pay_weixin_appsecret]" id="pay_weixin_appsecret" type="text" value="123456"/>
											</div>
											<div class="form-group">
												<label class="col-sm-1"><label for="pay_weixin_client_cert">{pigcms{:L_("微信支付证书")}</label></label>
												<input type="text"  style="width:200px;" name="pay_weixin_client_cert" id="pay_weixin_client_cert" class="input input-file" value="{pigcms{$ownpay.weixin.pay_weixin_client_cert}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">{pigcms{:L_("上传文件")}</a><span class="form_tips">{pigcms{:L_("微信支付证书，在微信商家平台中可以下载！文件名一般为")}apiclient_cert.pem</span>
											</div>
											<div class="form-group">
												<label class="col-sm-1"><label for="pay_weixin_client_key">{pigcms{:L_("微信支付证书密钥")}</label></label>
												<input type="text"  style="width:200px;" name="pay_weixin_client_key" id="pay_weixin_client_key" class="input input-file" value="{pigcms{$ownpay.weixin.pay_weixin_client_key}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">{pigcms{:L_("上传文件")}</a><span class="form_tips">{pigcms{:L_("微信支付证书密钥，在微信商家平台中可以下载！文件名一般为")}apiclient_key.pem</span>
											</div>
										</if>
										<php>}</php>
								</div>
							<php>}</php>
							<div id="tenpay" class="tab-pane <if condition="!$config['pay_weixin_open']">active</if>"  >
								<div class="form-group">
									<label class="col-sm-1" for="tenpay_open">{pigcms{:L_("是否开启")}</label>
									<select name="tenpay[open]" id="tenpay_open">
										<option value="0" <if condition="$ownpay['tenpay']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$ownpay['tenpay']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_tenpay_partnerid">{pigcms{:L_("商户号")}</label></label>
									<input class="col-sm-2" size="20" name="tenpay[pay_tenpay_partnerid]" id="pay_tenpay_partnerid" type="text" value="{pigcms{$ownpay.tenpay.pay_tenpay_partnerid}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_tenpay_partnerkey">{pigcms{:L_("密钥")}</label></label>
									<input class="col-sm-2" size="20" name="tenpay[pay_tenpay_partnerkey]" id="pay_tenpay_partnerkey" type="text" value="{pigcms{$ownpay.tenpay.pay_tenpay_partnerkey}"/>
								</div>
							</div>
							<div id="yeepay" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" for="yeepay_open">{pigcms{:L_("是否开启")}</label>
									<select name="yeepay[open]" id="yeepay_open">
										<option value="0" <if condition="$ownpay['yeepay']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$ownpay['yeepay']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_merchantaccount">{pigcms{:L_("商户编号")}</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_merchantaccount]" id="pay_yeepay_merchantaccount" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_merchantaccount}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_merchantprivatekey">{pigcms{:L_("商户私钥")}</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_merchantprivatekey]" id="pay_yeepay_merchantprivatekey" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_merchantprivatekey}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_merchantpublickey">{pigcms{:L_("商户公钥")}</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_merchantpublickey]" id="pay_yeepay_merchantpublickey" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_merchantpublickey}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_yeepaypublickey">{pigcms{:L_("易宝公钥")}</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_yeepaypublickey]" id="pay_yeepay_yeepaypublickey" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_yeepaypublickey}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_productcatalog">{pigcms{:L_("商品类别码")}</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_productcatalog]" id="pay_yeepay_productcatalog" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_productcatalog}"/>
								</div>
							</div>
							<div id="allinpay" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" for="allinpay_open">{pigcms{:L_("是否开启")}</label>
									<select name="allinpay[open]" id="allinpay_open">
										<option value="0" <if condition="$ownpay['allinpay']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$ownpay['allinpay']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_allinpay_merchantid">{pigcms{:L_("商户号")}</label></label>
									<input class="col-sm-2" size="20" name="allinpay[pay_allinpay_merchantid]" id="pay_allinpay_merchantid" type="text" value="{pigcms{$ownpay.allinpay.pay_allinpay_merchantid}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_allinpay_merchantkey">MD5 KEY</label></label>
									<input class="col-sm-2" size="20" name="allinpay[pay_allinpay_merchantkey]" id="pay_allinpay_merchantkey" type="text" value="{pigcms{$ownpay.allinpay.pay_allinpay_merchantkey}"/>
								</div>
							</div>
							<div id="chinabank" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" for="chinabank_open">{pigcms{:L_("是否开启")}</label>
									<select name="chinabank[open]" id="chinabank_open">
										<option value="0" <if condition="$ownpay['chinabank']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
										<option value="1" <if condition="$ownpay['chinabank']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_chinabank_account">{pigcms{:L_("商户号")}</label></label>
									<input class="col-sm-2" size="20" name="chinabank[pay_chinabank_account]" id="pay_chinabank_account" type="text" value="{pigcms{$ownpay.chinabank.pay_chinabank_account}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_chinabank_key">MD5 KEY</label></label>
									<input class="col-sm-2" size="20" name="chinabank[pay_chinabank_key]" id="pay_chinabank_key" type="text" value="{pigcms{$ownpay.chinabank.pay_chinabank_key}"/>
								</div>
							</div>
							<if condition="$config['pay_weifutong_open']">
								<div id="weifutong" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="weifutong_open">{pigcms{:L_("是否开启")}</label>
										<select name="weifutong[open]" id="weifutong_open">
											<option value="0" <if condition="$ownpay['weifutong']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['weifutong']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_weifutong_mchid">{pigcms{:L_("商户号")}</label></label>
										<input class="col-sm-2" size="20" name="weifutong[pay_weifutong_mchid]" id="pay_weifutong_mchid" type="text" value="{pigcms{$ownpay.weifutong.pay_weifutong_mchid}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_weifutong_key">{pigcms{:L_("支付密钥")}</label></label>
										<input class="col-sm-2" size="20" name="weifutong[pay_weifutong_key]" id="pay_weifutong_key" type="text" value="{pigcms{$ownpay.weifutong.pay_weifutong_key}"/>
									</div>
								</div>
							</if>
							<div id="alipayh5" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="alipay_open">{pigcms{:L_("是否开启")}</label>
										<select name="alipayh5[open]" id="alipay_open">
											<option value="0" <if condition="$ownpay['alipayh5']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['alipayh5']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipayh5_appid">PID</label></label>
										<input class="col-sm-2" size="20" name="alipayh5[pay_alipayh5_appid]" id="pay_alipayh5_appid" type="text" value="{pigcms{$ownpay.alipayh5.pay_alipayh5_appid}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipayh5_merchant_private_key">{pigcms{:L_("商户私钥")}</label></label>
										<input class="col-sm-2" size="20" name="alipayh5[pay_alipayh5_merchant_private_key]" id="pay_alipayh5_merchant_private_key" type="text" value="{pigcms{$ownpay.alipayh5.pay_alipayh5_merchant_private_key}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipayh5_public_key">{pigcms{:L_("支付宝公钥")}</label></label>
										<input class="col-sm-2" size="20" name="alipayh5[pay_alipayh5_public_key]" id="pay_alipayh5_public_key" type="text" value="{pigcms{$ownpay.alipayh5.pay_alipayh5_public_key}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1" for="pay_alipayh5_sign_type">{pigcms{:L_("加密方式")}</label>
										<select name="alipayh5[pay_alipayh5_sign_type]" id="pay_alipayh5_sign_type">
											<option value="0" <if condition="$ownpay['alipayh5']['pay_alipayh5_sign_type'] eq 0">selected="selected"</if>>RSA</option>
											<option value="1" <if condition="$ownpay['alipayh5']['pay_alipayh5_sign_type'] eq 1">selected="selected"</if>>RSA2</option>
										</select>
									</div>
								</div>
								
								<div id="alipay_app" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="alipay_app_open">{pigcms{:L_("是否开启")}</label>
										<select name="alipay_app[open]" id="alipay_app_open">
											<option value="0" <if condition="$ownpay['alipay_app']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['alipay_app']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_count">{pigcms{:L_("商户帐号")}</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_count]" id="pay_alipay_app_count" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_count}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_pid">PID</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_pid]" id="pay_alipay_app_pid" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_pid}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_private_key_android">{pigcms{:L_("商户私钥")}android</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_private_key_android]" id="pay_alipay_app_private_key_android" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_private_key_android}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_private_key_ios">{pigcms{:L_("商户私钥")}ios</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_private_key_ios]" id="pay_alipay_app_private_key_ios" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_private_key_ios}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="alipay_app_public_key">{pigcms{:L_("支付宝公钥")}</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[alipay_app_public_key]" id="alipay_app_public_key" type="text" value="{pigcms{$ownpay.alipay_app.alipay_app_public_key}"/>
									</div>
									
								</div>
								
								<div id="yzfpay" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="yzfpay_open">{pigcms{:L_("是否开启翼支付自有支付")}</label>
										<select name="yzfpay[open]" id="yzfpay_open">
											<option value="0" <if condition="$ownpay['yzfpay']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['yzfpay']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
									<h5 style="color:red">{pigcms{:L_("翼支付3.0线上配置")}</h5>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_store_id">{pigcms{:L_("店铺")}ID</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_store_id]" id="pay_yzf2_store_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_store_id}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_mer_id">{pigcms{:L_("线上商户帐号")}</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_mer_id]" id="pay_yzf_mer_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_mer_id}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_cert_key">{pigcms{:L_("线上证书秘钥")}</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_cert_key]" id="pay_yzf_cert_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_cert_key}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_public_key">{pigcms{:L_("线上支付公钥")}</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_public_key" id="pay_yzf_public_key" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_public_key}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">{pigcms{:L_("上传文件")}</a><span class="form_tips">{pigcms{:L_("翼支付公钥，文件格式为")}cer</span>
									</div> 
								
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_p12">{pigcms{:L_("线上支付证书")}</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_p12" id="pay_yzf_p12" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_p12}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">{pigcms{:L_("上传文件")}</a><span class="form_tips">{pigcms{:L_("翼支付支付证书，文件格式为")}p12</span>
									</div>
										<h5 style="color:red">{pigcms{:L_("翼支付3.0线下配置")}</h5>
									<div class="form-group">
										<label class="col-sm-1" for="yzfpay_open">{pigcms{:L_("是否开启翼支付线下支付")}</label>
										<select name="yzfpay[open_offline]" id="yzfpay_open">
											<option value="0" <if condition="$ownpay['yzfpay']['open_offline'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['yzfpay']['open_offline'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_mer_id">{pigcms{:L_("线下商户帐号")}</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_offline_mer_id]" id="pay_yzf_offline_mer_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_mer_id}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_cert_key">{pigcms{:L_("线下证书秘钥")}</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_offline_cert_key]" id="pay_yzf_offline_cert_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_cert_key}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_public_key">{pigcms{:L_("线下支付公钥")}</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_offline_public_key" id="pay_yzf_offline_public_key" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_public_key}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">{pigcms{:L_("上传文件")}</a><span class="form_tips">{pigcms{:L_("翼支付公钥，文件格式为")}cer</span>
									</div> 
								
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_p12">{pigcms{:L_("线下支付证书")}</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_offline_p12" id="pay_yzf_offline_p12" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_p12}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">{pigcms{:L_("上传文件")}</a><span class="form_tips">{pigcms{:L_("翼支付支付证书，文件格式为")}p12</span>
									</div>
									<h5 style="color:red">{pigcms{:L_("翼支付2.0线下配置")}</h5>
									<div class="form-group">
										<label class="col-sm-1" for="yzfpay_open">{pigcms{:L_("是否开启翼支付2.0扫码支付")}</label>
										<select name="yzfpay[open_offline2]" id="yzfpay_open">
											<option value="0" <if condition="$ownpay['yzfpay']['open_offline2'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['yzfpay']['open_offline2'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_mer_id">2.0{pigcms{:L_("商户帐号")}</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_mer_id]" id="pay_yzf2_mer_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_mer_id}"/>
									</div>
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_date_key">2.0{pigcms{:L_("数据")}key</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_date_key]" id="pay_yzf2_date_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_date_key}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_trade_key">2.0{pigcms{:L_("交易")}key</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_trade_key]" id="pay_yzf2_trade_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_trade_key}"/>
									</div>	
									
								</div>
								
								<div id="wftpay" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="wftpay_open">{pigcms{:L_("是否开启")}</label>
										<select name="wftpay[open]" id="wftpay_open">
											<option value="0" <if condition="$ownpay['wftpay']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
											<option value="1" <if condition="$ownpay['wftpay']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_wft_mer_id">{pigcms{:L_("商户")} ID</label></label>
										<input class="col-sm-2" size="20" name="wftpay[pay_wft_mer_id]" id="pay_wft_mer_id" type="text" value="{pigcms{$ownpay.wftpay.pay_wft_mer_id}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_wft_mer_pwd">{pigcms{:L_("商家交易秘钥")}</label></label>
										<input class="col-sm-2" size="20" name="wftpay[pay_wft_mer_pwd]" id="pay_wft_mer_pwd" type="text" value="{pigcms{$ownpay.wftpay.pay_wft_mer_pwd}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_wft_key">{pigcms{:L_("商家秘钥")}</label></label>
										<input class="col-sm-2" size="20" name="wftpay[pay_wft_key]" id="pay_wft_key" type="text" value="{pigcms{$ownpay.wftpay.pay_wft_key}"/>
									</div>
									
								</div>
                            <div id="allinpay_mer" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1" for="allinpay_open">{pigcms{:L_("是否开启")}</label>
                                    <select name="allinpay_mer[open]" id="allinpay_mer_open">
                                        <option value="0" <if condition="$ownpay['allinpay_mer']['open'] eq 0">selected="selected"</if>>{pigcms{:L_("关闭")}</option>
                                        <option value="1" <if condition="$ownpay['allinpay_mer']['open'] eq 1">selected="selected"</if>>{pigcms{:L_("开启")}</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="pay_allinpay_mer_id">{pigcms{:L_("商户号")}</label></label>
                                    <input class="col-sm-2" size="20" name="allinpay_mer[pay_allinpay_mer_id]" id="pay_allinpay_mer_id" type="text" value="{pigcms{$ownpay.allinpay_mer.pay_allinpay_mer_id}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="pay_allinpay_mer_appid">APPID</label></label>
                                    <input class="col-sm-2" size="20" name="allinpay_mer[pay_allinpay_mer_appid]" id="pay_allinpay_mer_appid" type="text" value="{pigcms{$ownpay.allinpay_mer.pay_allinpay_mer_appid}"/>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="pay_allinpay_mer_key">KEY</label></label>
                                    <input class="col-sm-2" size="20" name="allinpay_mer[pay_allinpay_mer_key]" id="pay_allinpay_mer_key" type="text" value="{pigcms{$ownpay.allinpay_mer.pay_allinpay_mer_key}"/>
                                </div>
                            </div>
							<div class="clearfix form-actions">
								<div class="col-md-offset-3 col-md-9">
									<button class="btn btn-info" type="submit" id="save_btn">
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
</style>
<script type="text/javascript" src="{pigcms{$static_public}js/date/WdatePicker.js"></script>
<link rel="stylesheet" href="{pigcms{$static_public}kindeditor/themes/default/default.css">
<script src="{pigcms{$static_public}kindeditor/kindeditor.js"></script>
<script src="{pigcms{$static_public}kindeditor/lang/{pigcms{$config.system_lang_alias}.js"></script>

<script type="text/javascript">
	KindEditor.ready(function(K){
			var site_url = "{pigcms{$config.site_url}";
			var editor = K.editor({
				allowFileManager : true
			});
			$('.J_selectImage').click(function(){
				
					var upload_file_btn = $(this);
					editor.uploadJson = "/index.php?g=Index&c=Upload&a=ajax_upload_file&name="+upload_file_btn.siblings('.input-file').attr('name');
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
</script>
<script type="text/javascript">
$(function(){
	$('#add_form').submit(function(){
		$('#save_btn').prop('disabled',true);
		$.post("{pigcms{:U('Ownpay/save')}",$('#add_form').serialize(),function(result){
			if(result.status == 1){
				alert(result.info);
				window.location.reload();
			}else{
				alert(result.info);
			}
			$('#save_btn').prop('disabled',false);
		})
		return false;
	});
});
function deleteImage(path,obj){
	$.post("{pigcms{:U('Group/ajax_del_pic')}",{path:path});
	$(obj).closest('.upload_pic_li').remove();
}
</script>
<include file="Public:footer"/>