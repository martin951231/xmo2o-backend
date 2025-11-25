<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN""http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{:L_("店员登录")} - {pigcms{$config.site_name}</title>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}login/login.css"/>
		<style>
			
			.qweb_handle{
				position: fixed;
				top:5px;
				right: 5px;
				background: rgba(255,255,255,0.7);
				border-radius: 20px;
				padding: 5px 10px;
			}
			.qweb_handle div{
				padding: 0;
				display:inline-block;
				cursor:pointer;
			}
			.qweb_handle div:hover{
				background: rgba(0,0,0,0.2);
				border-radius:30px;
			}
			.qweb_handle .min_btn{
				margin-right: 5px;
			}
			.qweb_handle .close_btn{
				margin-left: 5px;
			}
			.qweb_handle img{
				width: 30px;
			}
		</style>
	</head>
	<body>
		<if condition="$pack_qweb_app">
			<div class="qweb_handle">
				<div class="min_btn">
					<img src="{pigcms{$static_public}js/qweb/min_web.png"/>
				</div>
				<div class="close_btn">
					<img src="{pigcms{$static_public}js/qweb/close_web.png"/>
				</div>
			</div>
		</if>
		<div id="hdw">
			<div id="hd">{pigcms{:L_("店员登录")} - {pigcms{$config.site_name}</div>
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
							<img src="{pigcms{:U('Login/verify',array('type'=>'store_login'))}" id="login_verifyImg" onclick="login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'store_login'))}')" title="{pigcms{:L_('刷新验证码')}" alt="{pigcms{:L_('刷新验证码')}"/>
							<a href="javascript:login_fleshVerify('{pigcms{:U('Login/verify',array('type'=>'store_login'))}')">{pigcms{:L_("刷新验证码")}</a>
						</span>
					</p>
					<p class="btn_login"><input type="submit" value="{pigcms{:L_("登 录")}" class="login_btn"/></p>
				</form>
			</div>
		</div>
		<script type="text/javascript" src="{pigcms{$static_public}js/qweb/qwebchannel.js"></script>
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script type="text/javascript" src="{pigcms{$static_public}js/layer/layer.js"></script>
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
		<script type="text/javascript">
			//"判断在pc打包软件中打开")}
			if(pack_qweb_app){
				check_soft("print_soft.exe","no_tip",1,"");

				$('.qweb_handle .min_btn').click(function(){
					min_window();
				});
				$('.qweb_handle .close_btn').click(function(){
					layer.open({
						title:'{pigcms{:L_("确认提醒：")}'
						,content:'{pigcms{:L_("您确定要关闭软件吗？")}'
						,btn: ['{pigcms{:L_("确定','取消")}']
						,yes: function(index){
							close_window();
						}
					});
				});
			}
		
			var static_public="{pigcms{$static_public}",static_path="{pigcms{$static_path}",login_check="{pigcms{:U('Store/login')}",store_index="{pigcms{:U('Store/index')}";

			// "解决输入法遮挡")}
			$(function(){
				
		        
				// $('.text-input').each(function(){
				// 	var id = $(this).attr('id');
					if(checkAndroidApp() && $('body').width() <= 1280) {

						inputshow();
					}
				// });


				
				function inputshow(id) {
					// var inp = document.querySelector(id); 

					var bodyHeight =  250 + pageYOffset -5 ; 
					$('.text-input[type!=submit]').on('click', function(ev){
						document.querySelector('body').style.height = '9999px';
						setTimeout(function(){
							document.body.scrollTop = document.documentElement.scrollTop = bodyHeight;
						},200);
						return false;
					 })

					$('.text-input').on('blur', function(ev){
						var _target = ev.target || ev.srcElement;  
						if(_target.nodeName == 'INPUT') { return }
					 	document.querySelector('body').style.height="auto"
						document.querySelector('body').removeAttribute('style')
						
					})
					//"触摸取消")}blur
					function fn(ev){
						var _target = ev.target || ev.srcElement;  
						if(_target.nodeName != 'INPUT') { $('.text-input').blur();}
						ev.preventDefault()
					};

					$(window).on('click', function(){
						$('.text-input').blur();
						document.querySelector('body').style.height="auto"
						document.querySelector('body').removeAttribute('style')
					})

					$(window).on('touchmove', function(){
						$('.text-input').blur();
						document.querySelector('body').style.height="auto"
						document.querySelector('body').removeAttribute('style')
					})

				}

				function checkApp(){
					if(/(pigcms_pack_app)/.test(navigator.userAgent.toLowerCase())){
						return true;
					}else{
						return false;
					}
				}
				function checkAndroid(){
					if(/(android)/.test(navigator.userAgent.toLowerCase())){
						return true;
					}else{
						return false;
					}
				}

				function checkAndroidApp(){
					if(checkApp() && checkAndroid()){
						return true;
					}else{
						return false;
					}
				}
			    

			})
        
		</script>
		<script type="text/javascript" src="{pigcms{$static_path}login/store_login.js"></script>
	</body>
</html>