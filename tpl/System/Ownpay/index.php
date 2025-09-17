<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/styles.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<title>{pigcms{$config.site_name} - 自有支付</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
<link rel="stylesheet" href="{pigcms{$static_path}css/bootstrap.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/font-awesome.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-fonts.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace.min.css" id="main-ace-style">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-skins.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/ace-rtl.min.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/global.css">
<link rel="stylesheet" href="{pigcms{$static_path}css/jquery-ui-timepicker-addon.css">
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ba-bbq.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace-extra.min.js"></script>


<script type="text/javascript" src="{pigcms{$static_path}js/bootstrap.min.js"></script>

<!-- page specific plugin scripts -->
<script type="text/javascript" src="{pigcms{$static_path}js/bootbox.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.custom.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.ui.touch-punch.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.easypiechart.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery.sparkline.min.js"></script>

<!-- ace scripts -->
<script type="text/javascript" src="{pigcms{$static_path}js/ace-elements.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/ace.min.js"></script>

<script type="text/javascript" src="{pigcms{$static_path}js/jquery.yiigridview.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-i18n.min.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}js/jquery-ui-timepicker-addon.min.js"></script>
<style type="text/css">
html{
	background:#fff;
}
.jqstooltip {
	position: absolute;
	left: 0px;
	top: 0px;
	visibility: hidden;
	background: rgb(0, 0, 0) transparent;
	background-color: rgba(0, 0, 0, 0.6);
	filter: progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000,endColorstr=#99000000);
	-ms-filter:"progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";
	color: white;
	font: 10px arial, san serif;
	text-align: left;
	white-space: nowrap;
	padding: 5px;
	border: 1px solid white;
	z-index: 10000;
}

.jqsfield {
	color: white;
	font: 10px arial, san serif;
	text-align: left;
}

.statusSwitch, .orderValidSwitch, .unitShowSwitch, .authTypeSwitch {
	display: none;
}

#shopList .shopNameInput, #shopList .tagInput, #shopList .orderPrefixInput
	{
	font-size: 12px;
	color: black;
	display: none;
	width: 100%;
}
.add_content {
    line-height: 32px;
    margin-left: 33px;
    cursor: pointer;
}
.reduce_recharge_content {
    background: url(./tpl/Merchant/default/static/css/img/reduce_nc.png) no-repeat;
    width: 32px;
    height: 32px;
    border: none;
    position: absolute;
    margin-left: -16px;
    margin-top: -146px;
    left:  calc(8.333% + 750px);
}
</style>
<script type="text/javascript">
	try{ace.settings.check('navbar' , 'fixed')}catch(e){}
	try{ace.settings.check('main-container' , 'fixed')}catch(e){}
	try{ace.settings.check('sidebar' , 'fixed')}catch(e){}
	try{ace.settings.check('breadcrumbs' , 'fixed')}catch(e){}
</script>

</head>

<body class="no-skin">
<div class="main-content">
	<!-- 内容头部 -->
	<div class="breadcrumbs" id="breadcrumbs">
		<ul class="breadcrumb">
			<li>
				<i class="ace-icon fa fa-gear gear-icon"></i>
				<a href="{pigcms{:U('Ownpay/index',array('city_id'=>$_GET['city_id']))}">自有支付配置</a>
			</li>
		</ul>
	</div>
	<!-- 内容头部 -->
	<div class="page-content">
		<div class="alert alert-info" style="margin:10px 0;">
			<button type="button" class="close" data-dismiss="alert"><i class="ace-icon fa fa-times"></i></button>
			开启自有支付，则用户会优先使用自有支付！例如配置了微信支付，则支付时显示的“微信支付”会调用自有支付。<br/>
		
		</div>
		<div class="page-content-area">
			<div class="row">
				<div class="col-xs-12">
					<div class="tabbable">
						<ul class="nav nav-tabs" id="myTab">	
							<if condition="$config['open_sub_mchid']">
								<li class="active">
									<a data-toggle="tab" href="#weixin">微信支付</a>
								</li>
							</if>
							<li <if condition=" !$config['open_sub_mchid']"> class="active"</if>>
								<a data-toggle="tab" href="#tenpay">财付通支付</a>
							</li>
							<li>
								<a data-toggle="tab" href="#yeepay">银行卡支付(易宝支付)</a>
							</li>
							<li>
								<a data-toggle="tab" href="#allinpay">银行卡支付(通联支付)</a>
							</li>
							<li>
								<a data-toggle="tab" href="#chinabank">银行卡支付(网银在线)</a>
							</li>
							<li>
								<a data-toggle="tab" href="#alipayh5">支付宝</a>
							</li>
							<!--li>
								<a data-toggle="tab" href="#alipay_app">支付宝APP</a>
							</li-->
							<li>
								<a data-toggle="tab" href="#yzfpay">翼支付</a>
							</li>
							<if condition="$config['pay_wft_open'] eq 1">
							<li>
								<a data-toggle="tab" href="#wftpay">网付通</a>
							</li>
							</if>
							<if condition="$config['pay_weifutong_open']">
								<li>
									<a data-toggle="tab" href="#weifutong">{pigcms{$config.pay_weifutong_alias_name}</a>
								</li>
							</if>
                            <if condition="$config['pay_allinpay_mer_open']">
								<li>
									<a data-toggle="tab" href="#allinpay_mer">通联支付</a>
								</li>
							</if>
						</ul>
					</div>
					<form enctype="multipart/form-data" class="form-horizontal" method="post" id="add_form">
					<input type="hidden" name="city_id" value="{pigcms{$_GET['city_id']}">
						<div class="tab-content">
							<php>if($config['open_sub_mchid']){</php>
								<div id="weixin" class="tab-pane active"  >
									<div class="form-group" <if condition="!empty($now_merchant['sub_mch_id'])">style="display:none;"</if>>
										<label class="col-sm-1" for="wxpay_open">是否开启</label>
										<select name="weixin[open]" id="wxpay_open">
											<option value="0" <if condition="$ownpay['weixin']['open'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['weixin']['open'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="sub_mch_id">子商家号</label></label>
										<input class="col-sm-2" size="20" name="weixin[sub_mch_id]" id="sub_mch_id" type="text" value="{pigcms{$ownpay.weixin.sub_mch_id}"/>
									</div>
								</div>
							<php>}</php>
							<div id="tenpay" class="tab-pane <if condition="!$config['open_sub_mchid']">active</if>"  >
								<div class="form-group">
									<label class="col-sm-1" for="tenpay_open">是否开启</label>
									<select name="tenpay[open]" id="tenpay_open">
										<option value="0" <if condition="$ownpay['tenpay']['open'] eq 0">selected="selected"</if>>关闭</option>
										<option value="1" <if condition="$ownpay['tenpay']['open'] eq 1">selected="selected"</if>>开启</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_tenpay_partnerid">商家号</label></label>
									<input class="col-sm-2" size="20" name="tenpay[pay_tenpay_partnerid]" id="pay_tenpay_partnerid" type="text" value="{pigcms{$ownpay.tenpay.pay_tenpay_partnerid}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_tenpay_partnerkey">密钥</label></label>
									<input class="col-sm-2" size="20" name="tenpay[pay_tenpay_partnerkey]" id="pay_tenpay_partnerkey" type="text" value="{pigcms{$ownpay.tenpay.pay_tenpay_partnerkey}"/>
								</div>
							</div>
							<div id="yeepay" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" for="yeepay_open">是否开启</label>
									<select name="yeepay[open]" id="yeepay_open">
										<option value="0" <if condition="$ownpay['yeepay']['open'] eq 0">selected="selected"</if>>关闭</option>
										<option value="1" <if condition="$ownpay['yeepay']['open'] eq 1">selected="selected"</if>>开启</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_merchantaccount">商家编号</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_merchantaccount]" id="pay_yeepay_merchantaccount" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_merchantaccount}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_merchantprivatekey">商家私钥</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_merchantprivatekey]" id="pay_yeepay_merchantprivatekey" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_merchantprivatekey}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_merchantpublickey">商家公钥</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_merchantpublickey]" id="pay_yeepay_merchantpublickey" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_merchantpublickey}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_yeepaypublickey">易宝公钥</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_yeepaypublickey]" id="pay_yeepay_yeepaypublickey" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_yeepaypublickey}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_yeepay_productcatalog">商品类别码</label></label>
									<input class="col-sm-2" size="20" name="yeepay[pay_yeepay_productcatalog]" id="pay_yeepay_productcatalog" type="text" value="{pigcms{$ownpay.yeepay.pay_yeepay_productcatalog}"/>
								</div>
							</div>
							<div id="allinpay" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" for="allinpay_open">是否开启</label>
									<select name="allinpay[open]" id="allinpay_open">
										<option value="0" <if condition="$ownpay['allinpay']['open'] eq 0">selected="selected"</if>>关闭</option>
										<option value="1" <if condition="$ownpay['allinpay']['open'] eq 1">selected="selected"</if>>开启</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_allinpay_merchantid">商家号</label></label>
									<input class="col-sm-2" size="20" name="allinpay[pay_allinpay_merchantid]" id="pay_allinpay_merchantid" type="text" value="{pigcms{$ownpay.allinpay.pay_allinpay_merchantid}"/>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_allinpay_merchantkey">MD5 KEY</label></label>
									<input class="col-sm-2" size="20" name="allinpay[pay_allinpay_merchantkey]" id="pay_allinpay_merchantkey" type="text" value="{pigcms{$ownpay.allinpay.pay_allinpay_merchantkey}"/>
								</div>
							</div>
							<div id="chinabank" class="tab-pane">
								<div class="form-group">
									<label class="col-sm-1" for="chinabank_open">是否开启</label>
									<select name="chinabank[open]" id="chinabank_open">
										<option value="0" <if condition="$ownpay['chinabank']['open'] eq 0">selected="selected"</if>>关闭</option>
										<option value="1" <if condition="$ownpay['chinabank']['open'] eq 1">selected="selected"</if>>开启</option>
									</select>
								</div>
								<div class="form-group">
									<label class="col-sm-1"><label for="pay_chinabank_account">商家号</label></label>
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
										<label class="col-sm-1" for="weifutong_open">是否开启</label>
										<select name="weifutong[open]" id="weifutong_open">
											<option value="0" <if condition="$ownpay['weifutong']['open'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['weifutong']['open'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_weifutong_mchid">商家号</label></label>
										<input class="col-sm-2" size="20" name="weifutong[pay_weifutong_mchid]" id="pay_weifutong_mchid" type="text" value="{pigcms{$ownpay.weifutong.pay_weifutong_mchid}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_weifutong_key">支付密钥</label></label>
										<input class="col-sm-2" size="20" name="weifutong[pay_weifutong_key]" id="pay_weifutong_key" type="text" value="{pigcms{$ownpay.weifutong.pay_weifutong_key}"/>
									</div>
								</div>
							</if>
							<div id="alipayh5" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="alipay_open">是否开启</label>
										<select name="alipayh5[open]" id="alipay_open">
											<option value="0" <if condition="$ownpay['alipayh5']['open'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['alipayh5']['open'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipayh5_appid">PID</label></label>
										<input class="col-sm-2" size="20" name="alipayh5[pay_alipayh5_appid]" id="pay_alipayh5_appid" type="text" value="{pigcms{$ownpay.alipayh5.pay_alipayh5_appid}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipayh5_merchant_private_key">商家私钥</label></label>
										<input class="col-sm-2" size="20" name="alipayh5[pay_alipayh5_merchant_private_key]" id="pay_alipayh5_merchant_private_key" type="text" value="{pigcms{$ownpay.alipayh5.pay_alipayh5_merchant_private_key}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipayh5_public_key">支付宝公钥</label></label>
										<input class="col-sm-2" size="20" name="alipayh5[pay_alipayh5_public_key]" id="pay_alipayh5_public_key" type="text" value="{pigcms{$ownpay.alipayh5.pay_alipayh5_public_key}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1" for="pay_alipayh5_sign_type">加密方式</label>
										<select name="alipayh5[pay_alipayh5_sign_type]" id="pay_alipayh5_sign_type">
											<option value="0" <if condition="$ownpay['alipayh5']['pay_alipayh5_sign_type'] eq 0">selected="selected"</if>>RSA</option>
											<option value="1" <if condition="$ownpay['alipayh5']['pay_alipayh5_sign_type'] eq 1">selected="selected"</if>>RSA2</option>
										</select>
									</div>
								</div>
								
								<div id="alipay_app" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="alipay_app_open">是否开启</label>
										<select name="alipay_app[open]" id="alipay_app_open">
											<option value="0" <if condition="$ownpay['alipay_app']['open'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['alipay_app']['open'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_count">商家帐号</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_count]" id="pay_alipay_app_count" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_count}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_pid">PID</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_pid]" id="pay_alipay_app_pid" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_pid}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_private_key_android">商家私钥android</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_private_key_android]" id="pay_alipay_app_private_key_android" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_private_key_android}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_alipay_app_private_key_ios">商家私钥ios</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[pay_alipay_app_private_key_ios]" id="pay_alipay_app_private_key_ios" type="text" value="{pigcms{$ownpay.alipay_app.pay_alipay_app_private_key_ios}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="alipay_app_public_key">支付宝公钥</label></label>
										<input class="col-sm-2" size="20" name="alipay_app[alipay_app_public_key]" id="alipay_app_public_key" type="text" value="{pigcms{$ownpay.alipay_app.alipay_app_public_key}"/>
									</div>
									
								</div>
								
								<div id="yzfpay" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="yzfpay_open">是否开启翼支付自有支付</label>
										<select name="yzfpay[open]" id="yzfpay_open">
											<option value="0" <if condition="$ownpay['yzfpay']['open'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['yzfpay']['open'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<h5 style="color:red">翼支付3.0线上配置</h5>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_store_id">店铺ID</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_store_id]" id="pay_yzf2_store_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_store_id}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_mer_id">线上商家帐号</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_mer_id]" id="pay_yzf_mer_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_mer_id}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_cert_key">线上证书秘钥</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_cert_key]" id="pay_yzf_cert_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_cert_key}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_public_key">线上支付公钥</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_public_key" id="pay_yzf_public_key" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_public_key}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传文件</a><span class="form_tips">翼支付公钥，文件格式为cer</span>
									</div> 
								
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_p12">线上支付证书</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_p12" id="pay_yzf_p12" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_p12}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传文件</a><span class="form_tips">翼支付支付证书，文件格式为p12</span>
									</div>
										<h5 style="color:red">翼支付3.0线下配置</h5>
									<div class="form-group">
										<label class="col-sm-1" for="yzfpay_open">是否开启翼支付线下支付</label>
										<select name="yzfpay[open_offline]" id="yzfpay_open">
											<option value="0" <if condition="$ownpay['yzfpay']['open_offline'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['yzfpay']['open_offline'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_mer_id">线下商家帐号</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_offline_mer_id]" id="pay_yzf_offline_mer_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_mer_id}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_cert_key">线下证书秘钥</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf_offline_cert_key]" id="pay_yzf_offline_cert_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_cert_key}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_public_key">线下支付公钥</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_offline_public_key" id="pay_yzf_offline_public_key" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_public_key}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传文件</a><span class="form_tips">翼支付公钥，文件格式为cer</span>
									</div> 
								
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf_offline_p12">线下支付证书</label></label>
										<input type="text"  style="width:200px;" name="pay_yzf_offline_p12" id="pay_yzf_offline_p12" class="input input-file" value="{pigcms{$ownpay.yzfpay.pay_yzf_offline_p12}" readonly>&nbsp;&nbsp;<a href="javascript:void(0)" class="btn btn-sm btn-success J_selectImage"  style="background: #87b87f!important;border-color: #87b87f;color:#fff;">上传文件</a><span class="form_tips">翼支付支付证书，文件格式为p12</span>
									</div>
									<h5 style="color:red">翼支付2.0线下配置</h5>
									<div class="form-group">
										<label class="col-sm-1" for="yzfpay_open">是否开启翼支付2.0扫码支付</label>
										<select name="yzfpay[open_offline2]" id="yzfpay_open">
											<option value="0" <if condition="$ownpay['yzfpay']['open_offline2'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['yzfpay']['open_offline2'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_mer_id">2.0商家帐号</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_mer_id]" id="pay_yzf2_mer_id" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_mer_id}"/>
									</div>
								
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_date_key">2.0数据key</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_date_key]" id="pay_yzf2_date_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_date_key}"/>
									</div>
									
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_yzf2_trade_key">2.0交易key</label></label>
										<input class="col-sm-2" size="20" name="yzfpay[pay_yzf2_trade_key]" id="pay_yzf2_trade_key" type="text" value="{pigcms{$ownpay.yzfpay.pay_yzf2_trade_key}"/>
									</div>	
									
								</div>
								
								<div id="wftpay" class="tab-pane">
									<div class="form-group">
										<label class="col-sm-1" for="wftpay_open">是否开启</label>
										<select name="wftpay[open]" id="wftpay_open">
											<option value="0" <if condition="$ownpay['wftpay']['open'] eq 0">selected="selected"</if>>关闭</option>
											<option value="1" <if condition="$ownpay['wftpay']['open'] eq 1">selected="selected"</if>>开启</option>
										</select>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_wft_mer_id">商家 ID</label></label>
										<input class="col-sm-2" size="20" name="wftpay[pay_wft_mer_id]" id="pay_wft_mer_id" type="text" value="{pigcms{$ownpay.wftpay.pay_wft_mer_id}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_wft_mer_pwd">商家交易秘钥</label></label>
										<input class="col-sm-2" size="20" name="wftpay[pay_wft_mer_pwd]" id="pay_wft_mer_pwd" type="text" value="{pigcms{$ownpay.wftpay.pay_wft_mer_pwd}"/>
									</div>
									<div class="form-group">
										<label class="col-sm-1"><label for="pay_wft_key">商家秘钥</label></label>
										<input class="col-sm-2" size="20" name="wftpay[pay_wft_key]" id="pay_wft_key" type="text" value="{pigcms{$ownpay.wftpay.pay_wft_key}"/>
									</div>
									
								</div>
                            <div id="allinpay_mer" class="tab-pane">
                                <div class="form-group">
                                    <label class="col-sm-1" for="allinpay_open">是否开启</label>
                                    <select name="allinpay_mer[open]" id="allinpay_mer_open">
                                        <option value="0" <if condition="$ownpay['allinpay_mer']['open'] eq 0">selected="selected"</if>>关闭</option>
                                        <option value="1" <if condition="$ownpay['allinpay_mer']['open'] eq 1">selected="selected"</if>>开启</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-1"><label for="pay_allinpay_mer_id">商家号</label></label>
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
										保存
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
<script src="{pigcms{$static_public}kindeditor/lang/zh_CN.js"></script>
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