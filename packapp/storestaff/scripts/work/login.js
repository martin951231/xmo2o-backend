var client = common.checkAndroidApp() ? 2 : (common.checkIosApp() ? 1 : 0);
var isApp = false
$(document).ready(function () {

	common.removeCache('otherLoginAccount', true);
	common.removeCache('isLogout', true);
	common.setCache('isShowLogin', 'true', true);

	if (common.getVersion() >= 1300) {
		common.appFunction({ name: "closeStartupPage" });
	}

	var ticket = common.getCache('ticket');
	if (ticket) {
		redirect_self('index.html');
		return false;
	}
	$(".signin").height($(window).height());

	$('#passwd').keyup(function (e) {

		if (e.keyCode == 13) {
			$(".sign_bun").trigger('click');
			return false;
		}
	});

	$('#account,#passwd').focus(function () {
		$('.signin .land').css('margin-top', '-300px');
	})
	$('#account,#passwd').blur(function () {
		$('.signin .land').css('margin-top', '-200px');
	})

	common.http('Config&a=get_reg', {}, function (data) {
		if (data.reg == '1') {
			$("#reg_account").show();
		}
	});

	$(".reg_btn").click(function () {
		var account = $('#account').val(), passwd = $('#passwd').val();
		if (account.length < 1) {
			motify.log(L_("登录账号不能为空"));
			return false;
		}
		if (passwd.length < 1) {
			motify.log(L_("登录密码不能为空"));
			return false;
		}
		common.http('Config&a=get_reg', { 'account': account, 'passwd': passwd, 'client': client }, function (data) {
			// weui.alert("注册成功！请等待平台审核", function(){
			// redirect_self('login.html');
			// window.history.go(-1);
			motify.log(L_("注册成功！请等待平台审核"));
			setTimeout(function () {
				window.location.replace('login.html')
			}, 1500)
			// });
		});
	});

	$(".sign_bun").click(function () {
		$('.signin .land').css('margin-top', '-200px');
		var account = $('#account').val(), passwd = $('#passwd').val();
		if (account.length < 1) {
			motify.log(L_("登录账号不能为空"));
			return false;
		}
		if (passwd.length < 1) {
			motify.log(L_("登录密码不能为空"));
			return false;
		}
		if (!$('#login_agreement').prop('checked') && isApp) {
			motify.log(L_("请先阅读并勾选页面底部协议"));
			return false;
		}
		var client = common.checkAndroidApp() ? 2 : (common.checkIosApp() ? 1 : 0);
		common.http('Storestaff&a=login', { 'account': account, 'passwd': passwd, 'client': client }, function (data) {
			//重新登录清除所有缓存
			common.removeAllCache(true);
			common.removeAllCache(false);

			common.setCache('ticket', data.ticket, true);
			localStorage.setItem('storestaff_v20_ticket', data.v20_ticket);
			common.setCache('store_staff', data.user, true);
			if ($('#checkbox_c1').attr('checked')) {
				common.setCache('ticket', data.ticket);
			}
			common.removeCache('buy_list', true);
			common.removeCache('indexData', true);
			common.removeCache('total_goods', true);

			// 判断密码是不是123456
			if (passwd == '123456') {
				common.setCache('is_Simple_Password', '1', true);
			}

			if (common.getVersion() >= 800) {
				if (common.checkAndroidApp() && common.getVersion() >= 1420) {
					var tokenArr = {};
					if (typeof window.pigcmspackapp.getUPushToken == 'object' || typeof window.pigcmspackapp.getUPushToken == 'function') {
						tokenArr.upushToken = window.pigcmspackapp.getUPushToken();
					}
					if (typeof window.pigcmspackapp.getJpushToken == 'object' || typeof window.pigcmspackapp.getJpushToken == 'function') {
						tokenArr.jpushToken = window.pigcmspackapp.getJpushToken();
					}
					common.http('Storestaff&a=many_device_token', tokenArr, function (data) {
						redirect_self((urlParam.back ? urlParam.back : 'index') + '.html');
					});
				} else if (common.checkAndroidApp() && (typeof window.pigcmspackapp.getUPushToken == 'object' || typeof window.pigcmspackapp.getUPushToken == 'function')) {
					var device_token = window.pigcmspackapp.getUPushToken();
					if (device_token) {
						common.http('Storestaff&a=get_device_token', { 'device_token': device_token }, function (data) {
							redirect_self((urlParam.back ? urlParam.back : 'index') + '.html');
						});
					} else {
						redirect_self((urlParam.back ? urlParam.back : 'index') + '.html');
					}
				} else {
					redirect_self((urlParam.back ? urlParam.back : 'index') + '.html');
				}
			} else {
				redirect_self((urlParam.back ? urlParam.back : 'index') + '.html');
			}
		});
	});

	config = common.getCache('config', true);
	if (!config || common.checkApp()) {
		common.http('Storestaff&a=config', { noTip: true, 'client': client }, function (data) {
			config = data;
			common.setCache('config', data, true);
			config_loaded();
		});
	} else {
		config_loaded();
	}

});

function config_loaded() {
	if (config.open_multilingual == 1 && config.lang_config && config.lang_config.lang_list && config.lang_config.lang_list.length > 0) {
		if (!all_lang || !now_lang || common.checkApp()) {
			common.setCache('lang_list', config.lang_config.lang_list, true);
			common.setCache('open_multilingual', config.open_multilingual, true);
			get_lang_list(config.lang_config.now_lang);
		} else {
			replace_html_lang();
		}
	}
	get_currency_symbol();
}

var exitLayer = -1;
function appbackmonitor() {
	if (/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())) {
		window.lifepasslogin.closeWebView();
	} else {
		if (common.getVersion() >= 1300) {
			window.pigcmspackapp.returnToHomePage();
			return false
		}
		if (exitLayer != -1) {
			window.pigcmspackapp.closewebview(2);
		} else {
			layer.closeAll();
			exitLayer = layer.open({
				content: '您确定要退出程序吗？再次按返回键将退出。'
				, btn: ['确定', '取消']
				, yes: function (index) {
					window.pigcmspackapp.closewebview(2);
					layer.close(index);
				}
				, end: function (index) {
					exitLayer = -1;
				}
			});
		}
	}
}

// 隐私协议    
// $('.fixedAgreement').show();
var lastAgreeTime = '';
var privacy_policy_md5 = '';
if (common.checkApp()) {
	common.http('Storestaff&a=get_privacy_policy', { noTip: true }, function (data) {
		privacy_policy_md5 = data.privacy_policy_md5;
		if (data.privacy_policy) {
			isApp = true
			$('.fixedAgreement').show();
			if (common.checkIosApp()) {
				common.iosFunction('getAgreement/getAgreementOk', { name: 'getAgreement', callback: 'getAgreementOk' });
			} else {
				window.pigcmspackapp.getAgreement('getAgreementOk');
			}
		}
	})
}
function getAgreementOk(option) {
	if (!option || privacy_policy_md5.trim() != option.trim()) {
		agreementShow();
	}
}
function agreementShow() {
	weui.confirm(L_('<div class="agreementCon"><p>感谢您信任并使用我们的软件！</p> <p>我们依据最新的法律要求更新了用户协议和隐私政策，特向您推送本提示。请您仔细阅读并充分理解相关条款，其中的重点条款已为您标注，方便您了解自己的权利。</p> <p>我们将通过《隐私政策》帮助您了解我们收集、使用、存储和共享个人信息的情况，以及您所享有的相关权利。并向您介绍了我们公司主体的基本信息以及告知了我们的联系通道与方式。</p><p>您点击"同意"，即表示您已阅读并同意上述协议条款，我们将尽全力保障您的合法权益并继续为您提供优质的产品和服务。如您点击"不同意"，将可能导致无法继续使用我们的产品和服务。</p><p style="color:#999">您可通过阅读完整版<a href="userAgreement.html" class="agreementLink">《用户协议》</a>和<a href="agreement.html" class="agreementLink">《隐私政策》</a>了解详尽的条款内容</p></div>'), {
		title: L_('温馨提示'),
		buttons: [{
			label: L_('不同意'),
			type: 'gray',
			onClick: function () {
				$('.mark').show();
			}
		}, {
			label: L_('同意'),
			type: 'primary',
			onClick: function () {
				if (common.checkIosApp()) {
					common.iosFunction('saveAgreement/' + privacy_policy_md5, { name: 'saveAgreement', privacyPolicyMd5: privacy_policy_md5 });
				} else {
					window.pigcmspackapp.saveAgreement(privacy_policy_md5);
				}
			}
		}]
	});
}

$('.seen').click(function () {
	$('.mark').hide();
	agreementShow();
	window.pigcmspackapp.closewebview(2);

})

$('#goAgreement').on("click", function () {
	openWebviewUrl('agreement.html');
});

$('#goUserAgreement').on("click", function () {
	openWebviewUrl('userAgreement.html');
});




