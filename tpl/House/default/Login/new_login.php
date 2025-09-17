<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>{pigcms{:L_("小区中心")} - {pigcms{$config.site_name}</title>
		<if condition="$config['site_favicon']">
			<link rel="shortcut icon" href="{pigcms{$config.site_favicon}"/>
		</if>
		<link rel="stylesheet" type="text/css" href="//at.alicdn.com/t/font_1016180_jlx05xsvtif.css"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}login/new/login.css?t={pigcms{:time()}"/>
		<script type="text/javascript">if(self!=top){window.top.location.href = "{pigcms{:U('Login/index')}";}</script>
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
            .login-form .login-tab-r {
                text-align: center;
                width: 100%;
            }
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
            .village_list_select{
                width: 300px;
                list-style: none;
                padding:0 30px;
                overflow-y:scroll;
            }
            .village_list_select li{
                height:50px;
                line-height:50px;
                cursor:pointer;
                border-top: 1px solid #ccc;
                padding-left:15px;
            }
            .village_list_select li.first{
                border-top:0px;
            }
		</style>
	</head>
	<body>
		<div id="app">
			<div class="agreement" data-v-12bb8c1f>
				<div class="pubHeader">
					<div class="pubWrap clearfix">
						<div class="fl">
							<a href="{pigcms{$config['site_url']}" class="logoicon mr10 fl" style="background-image:url({pigcms{:replace_file_domain($config['site_logo'])})" target="_blank"></a>
							<span class="pl30 register">{pigcms{$config['house_name']}{pigcms{:L_("登录")}</span>
						</div>
						<!--语言环境切换-->
<!--						<if condition="$config['open_multilingual'] eq '1'">-->
<!--						<div class="fr" style="color:#999;font-size:12px;margin-left:20px;">-->
<!--							<select id="change_lang" data-url="{pigcms{:U('Public/change_lang')}">-->
<!--								<volist name="lang_list" id="vo">-->
<!--								<option value="{pigcms{$vo.val}" <if condition="$config['system_lang'] eq $vo['val']">selected="selected"</if>>{pigcms{$vo.display}</option>-->
<!--								</volist>-->
<!--							</select>-->
<!--						</div>-->
<!--						</if>-->
					</div>
					<div class="rightAside">
						<if condition="$config['site_qq']">
							<a href="http://wpa.qq.com/msgrd?v=3&uin={pigcms{$config.site_qq}&site=qq&menu=yes" target="_blank" class="qqCounsel pointer">
								<div class="block block1 on">
									<i class="iconfont icon-qq"></i>
									<p>{pigcms{:L_("在线咨询")}</p>
								</div>
							</a>
						</if>
						<if condition="$config['wechat_qrcode']">
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
						</if>
						<if condition="$config['site_phone']">
							<div class="block block3">
								<i class="iconfont icon-dianhua1"></i> 
								<p>{pigcms{:L_("联系电话")}</p>
								<div class="qrcode phone">
									<div class="qrcodeWrap">
										<p>{pigcms{$config.site_phone}</p>
									</div>
								</div>
							</div>
						</if>
						<if condition="$config['site_email']">
							<div class="block block4">
								<i class="iconfont icon-youxiang"></i> 
								<p>{pigcms{:L_("联系邮箱")}</p>
								<div class="qrcode email">
									<div class="qrcodeWrap">
										<p>{pigcms{$config.site_email}</p>
									</div>
								</div>
							</div>
						</if>
					</div>
				</div>
				<div class="login-form">
					<div class="login-tab login-tab-l" style="display: none;">
						<a href="javascript:void(0)" class="checked"> {pigcms{:L_("扫码登录")}</a>
					</div>
					<div class="login-tab login-tab-r">
						<a href="javascript:void(0)" class="checked">{pigcms{:L_("账户登录")}</a>
					</div>
					<div class="login-box" style="display:block;visibility:visible;">
						<div class="msg-wrap">
							<div class="msg-error hide"><b></b></div>
						</div>
						<div class="mc" >
							<div class="form">
								<form id="login_form" method="post" onsubmit="return false;">
                                    <input type="hidden" name="village_id" id="village_id" value=""/>
									<div class="item item-fore1">
										<label for="login_account" class="login-label name-label"></label>
										<input id="login_account" type="text" class="itxt" name="account" tabindex="1" autocomplete="off" value="" placeholder="{pigcms{:L_("账号")}"/>
										<span class="clear-btn"></span>
									</div>
                                    <div id="entry" class="item item-fore2" style="visibility:visible;">
                                        <label class="login-label pwd-label" for="login_pwd"></label>
                                        <input type="password" id="login_pwd" name="pwd" class="itxt itxt-error" tabindex="2" autocomplete="off" placeholder="{pigcms{:L_("密码")}"/>
                                        <span class="clear-btn"></span>
                                        <span class="capslock" style="display:none;"><b></b>{pigcms{:L_("大小写锁定已打开")}</span>
                                    </div>
                                    <div id="login_choose" class="item item-fore4" style="visibility:visible;">
                                        <label class="login-label choose-label" for="login_pwd"></label>
                                        <select name="login_status" id="login_status" style="color: #BBBBBB;" class="itxt4 itxt-error" tabindex="4" autocomplete="off">
                                            <option value="" selected disabled style="display: none;">{pigcms{:L_("请选择登录身份")}</option>
                                            <option value="1" style="color:black;">{pigcms{:L_("物业公司总管理员")}</option>
                                            <option value="2" style="color:black;">{pigcms{:L_("物业公司普通管理员")}</option>
                                            <option value="3" style="color:black;">{pigcms{:L_("小区物业管理人员")}</option>
                                        </select>
                                        <span class="clear-btn"></span>
                                        <span class="capslock" style="display:none;"><b></b>{pigcms{:L_("大小写锁定已打开")}</span>
                                    </div>
									<div id="o-authcode" class="item item-vcode item-fore3">
										<input id="login_verify" type="text" class="itxt itxt02" name="verify" tabindex="3"/>
										<img class="verify-code" src="{pigcms{:U('Login/verify',array('type'=>'login'))}" id="login_verifyImg" onclick="login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'login'))}')" title="{pigcms{:L_("刷新验证码")}" alt="刷新验证码"/>
										<a href="javascript:void(0)" onclick="login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'login'))}')">{pigcms{:L_("看不清换一张")}</a>
									</div>
									<div class="item item-fore5">
										<div class="login-btn">{pigcms{:L_("登录")}</div>
									</div>
								</form>
							</div>
						</div>
					</div>
					<div class="qrcode-login" style="display:none;visibility:visible;">
						<div class="mc">
							<div class="qrcode-error-2016">
								<div class="qrcode-error-mask"></div>
								<p class="err-cont">{pigcms{:L_("二维码已失效")}</p>
								<a href="javascript:void(0)" class="refresh-btn" style="outline: rgb(109, 109, 109) none 0px;">{pigcms{:L_("刷新")}</a>
							</div>
							<div class="qrcode-main">
								<div class="qrcode-img">
									<img src="{pigcms{$static_public}images/impc_qrcode.jpg" alt="" id="wx_qrcode"/>
									<div class="qrcode-error02 hide" id="J-qrcoderror" style="display: none;">
										<a href="#none">
											<span class="error-icon"></span>
											<div class="txt">{pigcms{:L_("网络开小差咯")}<span class="ml10">{pigcms{:L_("刷新二维码")}</span></div>							
										</a>
									</div>
								</div>
								<div class="qrcode-help" style="display:none;"></div>
							</div>
							<div class="qrcode-panel">
								<ul>
									<li class="fore1">
										<span>{pigcms{:L_("打开")}</span>
										<a href="http://weixin.qq.com" target="_blank" clstag="pageclick|keycount|201607144|9">
											<span class="red">{pigcms{:L_("手机微信")}</span>
										</a>
									</li>
									<li>{pigcms{:L_("扫描二维码")}</li>
								</ul>
							</div>
							<div class="coagent qr-coagent" id="qrCoagent">
								<ul>
									<li><b></b><em>{pigcms{:L_("免输入")}</em></li>
									<li><b class="faster"></b><em>{pigcms{:L_("更快")}&nbsp;</em></li>
									<li><b class="more-safe"></b><em>{pigcms{:L_("更安全")}</em></li>
								</ul>
							</div>
						</div>
					</div>
					 <div class="coagent" id="kbCoagent">
						<ul>
							<!--li>
								<b></b>
								<a href="javascript:void(0)" onclick="" class="pdl">
									<b class="QQ-icon"></b>
									<span>QQ</span>
								</a>
								<span class="line">|</span>
							</li>
							<li>
								<a href="javascript:void(0)" onclick="" class="pdl">
									<b class="weixin-icon"></b>
									<span>{pigcms{:L_("微信")}</span>
								</a>
							</li-->
							<li class="extra-r">
								<div>
									<div class="regist-link">
										<a href="{pigcms{:U('reg')}"><b></b>{pigcms{:L_("立即注册")}</a>
									</div>
								</div>
							</li>
						</ul>
					</div>
				</div>
			</div>
		</div>
		<div class="copyright">
			Copyright &copy; {pigcms{:date('Y')}&nbsp;{pigcms{$config.site_name}&nbsp;{pigcms{$config.top_domain}&nbsp;{pigcms{:L_("版权所有")}&nbsp;<if condition="!empty($config['site_icp'])"><a href="http://beian.miit.gov.cn/" target="_blank">{pigcms{$config.site_icp}</a></if>
		</div>
<!--		<if condition="$_SESSION['system']">-->
<!--			<div class="system_page_error"></div>-->
<!--		</if>  -->
		<script type="text/javascript">
			var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",login_check="{pigcms{:U('Login/check')}",reg_check="{pigcms{:U('Login/reg_check')}",merchant_index="{pigcms{:U('Index/index')}",choose_province="{pigcms{:U('Area/ajax_province')}",choose_city="{pigcms{:U('Area/ajax_city')}",choose_area="{pigcms{:U('Area/ajax_area')}",choose_circle="{pigcms{:U('Area/ajax_circle')}", show_circle = 1;
			var verify_url = '{pigcms{:U('Login/verify',array('type'=>'login'))}',house_index="{pigcms{:U('Index/index')}";
			var property_login = "{pigcms{:U('NewProperty/index_new')}"
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/layer.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}login/new/login.js?t={pigcms{:time()}"></script>
        <script type="text/javascript" src="{pigcms{$static_public}js/artdialog/jquery.artDialog.js"></script>
	</body> 
</html>