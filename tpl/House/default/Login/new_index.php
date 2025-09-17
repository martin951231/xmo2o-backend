<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>{pigcms{:L_("商家中心")} - {pigcms{$config.site_name}</title>
		<if condition="$config['site_favicon']">
			<link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
		</if>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}login/new_login.css"/>
		<script type="text/javascript">if(self!=top){window.top.location.href = "{pigcms{:U('Login/index')}";}</script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<style type="text/css">
			.subline{margin:.28rem .2rem;}
	   		.subline li{display:inline-block;}
		</style>
	</head>
	<body>
		<div class="W_header_line"></div>
		<div id="hdw">
			<div id="hd" style="background-image:url({pigcms{$config.site_logo});">{pigcms{:L_("商家中心")} - {pigcms{$config.site_name}</div>
		</div>
		<div id="login">
			<div id="switch_btn">
				<a class="login_p on" types="login">{pigcms{:L_("商户登录")}</a>
				<span class="vline">|</span>
				<a class="reg_p" types="reg">{pigcms{:L_("商户注册")}</a>
				<div class="clear"></div>
			</div>
			<div id="box" style="height: 600px;">
				<div style="float:left;">
					<form method="post" id="login_form">
						<p>
							<label>{pigcms{:L_("帐 号：")}</label>
							<input class="text-input" type="text" name="account" id="login_account"/>
							<span class="check">* {pigcms{:L_("长度为6~16位字符")}</span>
						</p>
						<p>
							<label>{pigcms{:L_("密码：")}</label>
							<input class="text-input" type="password" name="pwd" id="login_pwd"/>
							<span class="check">* {pigcms{:L_("长度为大于6位字符")}</span>
						</p>
						
						<p>
							<label>{pigcms{:L_("验证码：")}</label>
							<input class="text-input" type="text" id="login_verify" style="width:60px;" maxlength="4" name="verify"/>&nbsp;&nbsp;
							<span class="verify_box">
								<img src="{pigcms{:U('Login/verify',array('type'=>'login'))}" id="login_verifyImg" onclick="login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'login'))}')" title="{pigcms{:L_("刷新验证码")}" alt="刷新验证码"/>&nbsp;
								<a href="javascript:login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'login'))}')">{pigcms{:L_("刷新验证码")}</a>
							</span>
						</p>
						<p class="btn_login"><input type="submit" value="{pigcms{:L_("登 录")}" class="login_btn"/></p>
					</form>
					<form method="post" id="reg_form">
						<p>
							<label>{pigcms{:L_("帐 号：")}</label>
							<input class="text-input" type="text" name="account" id="reg_account"/>
							<span class="check">* {pigcms{:L_("长度为6~16位字符")}</span>
						</p>
						<p>
							<label>{pigcms{:L_("密 码：")}</label>
							<input class="text-input" type="password" name="pwd" id="reg_pwd"/>
							<span class="check">* {pigcms{:L_("长度为大于6位字符")}</span>
						</p>
						<if condition="$config['open_score_fenrun'] eq 1 OR $config['open_distributor'] eq 1 OR $config['open_c2b_spread'] eq 1 OR $config['merchant_recommend_wholesale_switch'] eq 1">
						<p>
							<label>{pigcms{:L_("推广码：")}</label>
							<input class="text-input" type="text" name="spread_code" id="spread_code"/>
							<span class="check">* {pigcms{:L_("从推荐人处获取")}</span>
						</p>
						</if>
						<if condition="$config.open_admin_code eq 1">
						
						<p>
							<label>{pigcms{:L_("邀请码：")}</label>
							<input class="text-input" type="text" name="invit_code" id="invit_code"/>
						</p>	
						</if>
                        <p>
                            <label>{pigcms{:L_("商家名称：")}</label>
                            <input class="text-input" type="text" name="name" id="reg_name"/>
                        </p>
                        <if condition="$config['merchant_type_switch'] eq 1">
                            <p>
                                <label>{pigcms{:L_("公司名称：")}</label>
                                <input class="text-input" type="text" name="company_name" id="company_name"/>
                                <span class="check">* {pigcms{:L_("公司名称（营业执照上的名称）")}</span>
                            </p>
                        </if>
						<p>
							<label>{pigcms{:L_("所在区域：")}</label>
							<span id="choose_cityarea"></span>
						</p>
						<p>
							<label>{pigcms{:L_("邮 箱：")}</label>
							<input class="text-input" type="text" name="email" id="reg_email"/>
						</p>

						<if condition="$config.international_phone eq 1">
							<p>
								<label>{pigcms{:L_("区 号：")}</label>
								
								<pigcms:national_phone id="phone_country_type" class="col-sm-1">
								 	<option value="{pigcms{$vo.code}"  <if condition="$config.qcloud_sms_default_country eq $vo['code']">selected</if>>+ {pigcms{$vo.code} {pigcms{$vo.national}</option>
								</pigcms:national_phone>
							</p>
						</if>
						<p>
							<label>{pigcms{:L_("手机号：")}</label>
								
							<input class="text-input" type="text" name="phone" id="reg_phone"/>
							<span class="check">* {pigcms{:L_("必填")}</span>
						</p>
						<if condition="$config.open_merchant_reg_sms eq 1">
						<p>
							<label>{pigcms{:L_("短信验证码：")}</label>
							<input class="text-input" type="text" name="smscode" id="reg_sms"/>
							<a href="javascript:void(0)" onclick="sendsms(this)">{pigcms{:L_("发送短信")}</a>
						</p>
						</if>
						<p>
							<label>{pigcms{:L_("验证码：")}</label>
							<input class="text-input" type="text" id="reg_verify" style="width:60px;" maxlength="4" name="verify"/>&nbsp;&nbsp;
							<span class="verify_box">
								<img src="{pigcms{:U('Login/verify',array('type'=>'reg'))}" id="reg_verifyImg" onclick="reg_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'reg'))}')" title="{pigcms{:L_("刷新验证码")}" alt="刷新验证码"/>&nbsp;
								<a href="javascript:reg_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'reg'))}')">{pigcms{:L_("刷新验证码")}</a>
							</span>
						</p>
						<if condition="$config['store_register_agreement']">
							<ul class="subline">
							    <li class="register_agreement register_agreement_box"><input type="checkbox" id="register_agreement" checked="checked"/>{pigcms{:L_("我已阅读并且同意")}<font color="#EE3968"><a href="javascript:void(0)" id="register_agreement_btn">{pigcms{:L_("《商家注册协议》")}</a></font></li>
							</ul>
						</if>
						<p class="btn_login"><input type="submit" value="{pigcms{:L_("注 册")}" class="login_btn"></p>
					</form>
				</div>

				<div style="float:right;font-size:12px;">
					<if condition="$config['site_phone']"><p>{pigcms{:L_("客服电话 ：")}{pigcms{$config.site_phone}</p></if>
					<if condition="$config['site_qq']"><p>{pigcms{:L_("客服 Q Q ：")}{pigcms{$config.site_qq}</p></if>
					<if condition="$config['site_email']"><p>{pigcms{:L_("联系邮箱 ：")}{pigcms{$config.site_email}</p></if>
				</div>
			</div>
		</div>
		<div class="copyright">
			<p style="float:left;"><a href="{pigcms{$config.site_url}" target="_blank">{pigcms{$config.site_name}</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<if condition="!empty($config['site_icp'])"><a href="http://www.miibeian.gov.cn/" target="_blank">{pigcms{$config.site_icp}</a></if></p>
			<p style="float:right;">Copyright &copy; <span>{pigcms{:date('Y')}</span>&nbsp;{pigcms{$config.top_domain}</p>
		</div>

		<div id='lay' style='display:none;'>
			<div style="padding:30px;">{pigcms{$config.store_register_agreement}</div>
		</div>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script type="text/javascript">
			var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",login_check="{pigcms{:U('Login/check')}",reg_check="{pigcms{:U('Login/reg_check')}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}", show_circle = 1;
			
			var countdown = 60;
			var sendsmsTimer = null;
			function sendsms(val){
				/*{pigcms{:L_("添加这个限制会导致倒计时不生效")}*/
				/*if(countdown != 60){
					return false;
				}*/
				if($("input[name='phone']").val()==''){
					alert('{pigcms{:L_("手机号码不能为空！")}');
				}else{
					if(countdown==60){
						$.ajax({
							url: '{pigcms{:U('sendsms')}',
							type: 'POST',
							dataType: 'json',
							data: {phone: $("input[name='phone']").val()<if condition="$config.international_phone eq 1">,phone_country_type:$('#phone_country_type').val()</if>},
							success:function(date){
								if(date.status === 0){
									alert(date.info);
									val.removeAttribute("disabled");
									$(val).html("{pigcms{:L_("验证短信")}");
									countdown = 60;
									clearTimeout(sendsmsTimer);
								}
								if(date.error_code){
									
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
						sendsmsTimer = setTimeout(function() {
							sendsms(val);
						},1000)
					}
				}
			}
		</script>
		<script>
            var merchant_type_switch = "{pigcms{$config.merchant_type_switch}";
			var international_phone = {pigcms{$config.international_phone|intval=###};
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}login/login.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/area.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
		<style>
		.col-sm-1 {
		  border: 1px solid #ccc;
		  color: #333;
		  -moz-border-radius: 2px;
		  -webkit-border-radius: 2px;
		  border-radius: 6px;
		  padding: 6px;
		  outline: 0;
		  box-shadow: 0px 1px 1px 0px #eaeaea inset;
		}
		</style>
		
		
	</body>
</html>