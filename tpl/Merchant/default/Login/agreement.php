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
		<script type="text/javascript">if(self!=top){window.top.location.href = "{pigcms{:U('Login/index')}";}</script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
	</head>
	<body>
		<div id="app">
			<div class="agreement" data-v-12bb8c1f>
				<div class="pubHeader">
					<div class="pubWrap clearfix">
						<div class="fl">
							<a href="{pigcms{$config.site_url}" class="logoicon mr10 fl" style="background-image:url({pigcms{$config.site_logo})" target="_blank"></a>
							<span class="pl30 register">{pigcms{:L_("商家注册协议")}</span>
						</div>
						<if condition="$config['is_demo_domain']">
							<div class="fr" style="color:#999;font-size:12px;margin-left:20px;">
								{pigcms{:L_("小猪O2O致力于为快速构建本地生活服务平台提供专业的解决方案")}
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
				<div data-v-12bb8c1f class="main">
					{pigcms{$config.store_register_agreement}
				</div>
			</div>
		</div>
	</body>
</html>