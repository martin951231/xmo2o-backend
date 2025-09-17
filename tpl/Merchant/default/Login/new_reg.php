<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>{pigcms{:L_("商家中心")} - {pigcms{$config.site_name}</title>
		<if condition="$config['site_favicon']">
			<link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
		</if>
		<link rel="stylesheet" type="text/css" href="//at.alicdn.com/t/font_1016180_jlx05xsvtif.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}login/new/login.css?t={pigcms{:time()}"/>
		<script type="text/javascript">if(self!=top){window.top.location.href = "{pigcms{:U('reg')}";}</script>
		
		<style type="text/css">
			#change_lang{
				border-radius:3px !important;
				padding : 3px 4px;
				height: 30px;
				box-shadow:none !important;
				color:#858585;
				background-color:#fff;
				border:1px solid #d5d5d5;
			}
		</style>
	</head>
	<body class="reg-page">
		<div id="app">
			<div class="agreement" data-v-12bb8c1f>
				<div class="pubHeader">
					<div class="pubWrap clearfix">
						<div class="fl">
							<a href="{pigcms{$config.site_url}" class="logoicon mr10 fl" style="background-image:url({pigcms{$config.site_logo})" target="_blank"></a>
							<span class="pl30 register">{pigcms{:L_("商家注册")}</span>
						</div>
						<if condition="$config['is_demo_domain']">
							<div class="fr" style="color:#999;font-size:12px;margin-left:20px;">
								{pigcms{:L_("小猪O2O致力于为快速构建本地生活服务平台提供专业的解决方案")}
							</div>
						</if>
						<!--语言环境切换-->
						<if condition="$config['open_multilingual'] eq '1'">
						<div class="fr" style="color:#999;font-size:12px;margin-left:20px;">
							<select id="change_lang" data-url="{pigcms{:U('Public/change_lang')}">
								<volist name="lang_list" id="vo">
								<option value="{pigcms{$vo.val}" <if condition="$config['system_lang'] eq $vo['val']">selected="selected"</if>>{pigcms{$vo.display}</option>
								</volist>
							</select>
						</div>
						</if>
					</div>
					<div class="rightAside">
						<a href="http://wpa.qq.com/msgrd?v=3&uin={pigcms{$config.site_qq}&site=qq&menu=yes" target="_blank" class="qqCounsel pointer">
							<div class="block block1 on">
								<i class="iconfont icon-qq"></i>
								<p>{pigcms{:L_("在线咨询")}</p>
							</div>
						</a> 
						<div class="block block2">
							<i class="iconfont icon-erweima"></i> 
							<p>{pigcms{:L_("关注我们")}</p> 
							<div class="qrcode">
								<div class="qrcodeWrap">
									<img src="{pigcms{$config.wechat_qrcode}" alt="{pigcms{:L_("公众号二维码")}"/> 
									<p>{pigcms{:L_("扫码关注")}</p>
								</div>
							</div>
						</div>
						<div class="block block3">
							<i class="iconfont icon-dianhua1"></i> 
							<p>{pigcms{:L_("联系电话")}</p>
							<div class="qrcode phone">
								<div class="qrcodeWrap">
									<p>{pigcms{$config.site_phone}</p>
								</div>
							</div>
						</div>
						<div class="block block4">
							<i class="iconfont icon-youxiang"></i> 
							<p>{pigcms{:L_("联系邮箱")}</p>
							<div class="qrcode email">
								<div class="qrcodeWrap">
									<p>{pigcms{$config.site_email}</p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="login-form">
					<div class="login-tab login-tab-r reg-tab">
						<a href="javascript:void(0)" class="checked">{pigcms{:L_("账户注册")}</a>
					</div>
					<div class="login-box" style="display:block;visibility:visible;">
						<div class="msg-wrap">
							<div class="msg-error hide"><b></b></div>
						</div>
						<div class="mc">
							<div class="form">
								<form id="reg_form" method="post">
									<input type="hidden" name="phone_country_type" id="phone_country_type"/>
									<input type="hidden" name="province_id" id="choose_province"/>
									<input type="hidden" name="city_id" id="choose_city"/>
									<input type="hidden" name="area_id" id="choose_area"/>
									<input type="hidden" name="verify" id="sms_flesh_verify"/>
									<div class="ui-form-item g-mb30">
										<span class="ui-form-txt">{pigcms{:L_("手机号码")}</span>
										<if condition="$config.international_phone eq 1">
											<span class="s-fc-gy4 s-fs14 ui-area-num">+<span class="js-area-num"></span></span>
											<img class="ui-arrow-select js-arrow-select" alt="" src="{pigcms{$static_path}login/new/images/arrow_select.svg"/>
										</if>
										<input class="ui-form-input ui-form-input-phone" id="reg_phone" name="phone" placeholder="{pigcms{:L_("以后可以使用手机号登录")}" type="text"/>
										<if condition="$config.international_phone eq 1">
											<ul class="ui-select-list js-select-list" style="display:none;">
												<pigcms:national_phone_arr>
													<li data-area="{pigcms{$vo.code}">{pigcms{$vo.national}  +{pigcms{$vo.code}</li>
												</pigcms:national_phone_arr>
											</ul>
										</if>
									</div>
									<if condition="$config.open_merchant_reg_sms eq 1">
									<div class="ui-form-item g-mb30">
										<span class="ui-form-txt">{pigcms{:L_("短信验证码")}</span>
										<input class="ui-form-input" id="reg_sms" name="smscode" maxlength="4" placeholder="{pigcms{:L_("请填写短信验证码")}" type="text"/>
										<a class="s-txt-c getcode ui-getcode" href="javascript:;">{pigcms{:L_("获取验证码")}</a>
									</div>
									</if>
									<div class="ui-form-item g-mb30">
										<span class="ui-form-txt">{pigcms{:L_("设置密码")}</span>
										<input id="phone-copy" style="width: 1px;height: 1px;position: absolute;left: -9999px;" type="text"/>
										<input class="ui-form-input1" id="reg_pwd" name="pwd" placeholder="{pigcms{:L_("长度大于6位字符")}" autocomplete="new-password" type="password"/>
										<a class="ui-toggle f-fr toggle" href="javascript:;"></a>
									</div>
                                    <div class="ui-form-item g-mb30">
                                        <span class="ui-form-txt">{pigcms{:L_("商家名称")}</span>
                                        <input class="ui-form-input1" id="reg_name" name="name" placeholder="{pigcms{:L_("您店铺的品牌名称")}" autocomplete="new-password" type="text"/>
                                    </div>
                                    <if condition="$config['merchant_type_switch'] eq 1">
                                        <div class="ui-form-item g-mb30">
                                            <span class="ui-form-txt">{pigcms{:L_("公司名称")}</span>
                                            <input class="ui-form-input1" id="company_name" name="company_name" placeholder="{pigcms{:L_("您营业执照上公司名称")}" autocomplete="new-password" type="text"/>
                                        </div>
                                    </if>
									<div class="ui-form-item f-pr g-mb30">
										<span class="ui-form-txt">{pigcms{:L_("地区")}</span>
										<div class="address-wrap ng-scope">
											<label for="address"></label>
											<address class="m-b-none">
												<div class="dropdown">
													<a class="dropdown-toggle datetimepickerdropdown" href="javascript:;">
														<div class="input-group" title=""> 
															<input type="text" class="form-control address-input p-l-none ng-pristine ng-valid" readonly="readonly" placeholder="" id="reg_area"/>
															<span class="input-group-addon address-addon">
																<i class="fa  fa-caret-down down-icon"></i>
															</span>
														</div>
													</a>
													<div>
														<img class="ui-select ng-scope" alt="" src="{pigcms{$static_path}login/new/images/arrow_down.svg"/>
														<input class="ui-form-input input-hide ng-scope" id="address" name="address" type="text"/>
													</div>
												</div>
											</address>
										</div>
									</div>
									<if condition="$config['open_score_fenrun'] eq 1 OR $config['open_distributor'] eq 1 OR $config['open_c2b_spread'] eq 1">
										<div class="ui-form-item g-mb30">
											<span class="ui-form-txt">{pigcms{:L_("推广码")}</span>
											<input class="ui-form-input1" id="spread_code" name="spread_code" placeholder="{pigcms{:L_("从推荐人处获取（选填）")}" autocomplete="new-password" type="text"/>
										</div>
									</if>
									<if condition="$config.open_admin_code eq 1 || $config.open_bd_spread eq 1">
										<div class="ui-form-item g-mb30">
											<span class="ui-form-txt">{pigcms{:L_("邀请码")}</span>
											<input class="ui-form-input1" id="invit_code" name="invit_code" placeholder="{pigcms{:L_("从推荐人处获取（选填）")}" autocomplete="new-password" type="text"/>
										</div>
									</if>
									<div class="ui-form-btn"><input class="ui-button" id="submit" type="submit" value="{pigcms{:L_("注册")}"></div>
									<if condition="$config['store_register_agreement']">
										<div class="ui-protocol">{pigcms{:L_("注册即表示同意")}<a href="{pigcms{:U('agreement')}" target="_blank">{pigcms{:L_("《商家注册协议》")}</a></div>
									</if>
								</form>
							</div>
						</div>
					</div>
					 <div class="coagent" id="kbCoagent">
						<ul>
							<li class="extra-r">
								<div>
									<div class="regist-link">
										<a href="{pigcms{:U('index')}"><b></b>{pigcms{:L_("有账号，去登录")}</a>
									</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="address-bg"></div>
		<ul class="dropdown-menu address address-con r-3x" style="display:none;">
			<div class="address-tab m-b-sm wrapper-sm">
				<a href="javascript:;" class="active" data-type="province">省</a>
				<a href="javascript:;" data-type="city">市</a>
				<a href="javascript:;" data-type="area">区</a>
			</div>
			<div class="row address-body ng-scope"></div>
		</ul>
		<div class="copyright">
			Copyright &copy; {pigcms{:date('Y')} {pigcms{$config.site_name}{pigcms{$config.top_domain}{pigcms{:L_("版权所有")} <if condition="!empty($config['site_icp'])"><a href="https://beian.miit.gov.cn/" target="_blank">{pigcms{$config.site_icp}</a></if>
		</div>
		<if condition="$_SESSION['system']">
			<div class="system_page_error"></div>
		</if>
		<script type="text/javascript">
            var merchant_type_switch = "{pigcms{$config.merchant_type_switch}";
			var international_phone = {pigcms{$config.international_phone|intval=###};
			var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",login_check="{pigcms{:U('Login/check')}",reg_check="{pigcms{:U('Login/reg_check')}",merchant_index="{pigcms{:U('Index/index')}",choose_location="{pigcms{:U('Area/getLocation')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}", show_circle = 1;
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}"></script>
		<script type="text/javascript">
			var oldmd5langkey = localStorage.getItem('oldmd5langkey');
			if(typeof md5langkey != 'undefined'){
				if(oldmd5langkey != md5langkey && oldmd5langkey != null){
					localStorage.removeItem(oldmd5langkey);//去掉旧的，防止溢出
				}
				localStorage.setItem('oldmd5langkey',md5langkey);
				var _lang = localStorage.getItem(md5langkey);
				if(_lang == null){
					$.post("./appapi.php?c=Lang&a=js",{lang:'{pigcms{$config.system_lang}'},function(data){
						localStorage.setItem(data.key,data.lang);
						_lang = JSON.parse(data.lang);
					},'json');
				}
				_lang = JSON.parse(_lang);				
			}
			function L_(str){
				str = $.trim(str);
				if(typeof _lang == 'undefined'){
					return str;
				}
				if(str == ''){
					return str;
				}else if(typeof _lang[str] != 'undefined'){
					return _lang[str] != '' && _lang[str] ? _lang[str] : str;	
				}else{
					$.post('./appapi.php?c=Lang&a=add',{key:str},function(data){});
					return str;
				}
			}
		</script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}login/new/reg.js?t={pigcms{:time()}"></script>
	</body>
</html>