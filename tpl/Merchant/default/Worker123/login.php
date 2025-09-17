<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("登录")} - {pigcms{$config.site_name}</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}login/login.css"/>
	</head>
	<body>
		<div id="hdw">
			<div id="hd"><?php echo $config['appoint_worker_name'] ? $config['appoint_worker_name'] : L_('技师'); ?>{pigcms{:L_("登录")} - {pigcms{$config.site_name}</div>
		</div>
		<div id="login">
			<div id="switch_btn">
				<a class="login_p on" types="login">{pigcms{:L_("登 录")}</a>
				<div class="clear"></div> 
			</div>
			<div id="box">
				<form method="post" id="login_form">
					<p>
						<label>{pigcms{:L_("帐 号：")}</label>
						<input class="text-input" type="text" name="account" id="login_account"/>
					</p>
					<p>
						<label>{pigcms{:L_("密码：")}</label>
						<input class="text-input" type="password" name="pwd" id="login_pwd"/>
					</p>
					<p>
						<label>{pigcms{:L_("验证码：")}</label>
						<input class="text-input" type="text" id="login_verify" style="width:60px;" maxlength="4" name="verify"/>
						<span id="verify_box">
							<img src="{pigcms{:U('Login/verify',array('type'=>'store_login'))}" id="login_verifyImg" onclick="login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'store_login'))}')" title="{pigcms{:L_("刷新验证码" alt="刷新验证码")}"/>
							<a href="javascript:login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'store_login'))}')">{pigcms{:L_("刷新验证码")}</a>
						</span>
					</p>
					<p class="btn_login"><input type="submit" value="{pigcms{:L_("登 录")}" class="login_btn"/></p>
				</form>
			</div>
		</div>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script type="text/javascript">
			var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",login_check="{pigcms{:U('Worker/login')}",store_index="{pigcms{:U('Worker/index')}";
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}login/store_login.js"></script>
	</body>
</html>