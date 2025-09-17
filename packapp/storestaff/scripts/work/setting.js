var now_device_id = '', now_ticket = '', clearcache_index = null;
var flag = false;
$(document).ready(function () {

	if (common.checkWeixin()) {
		// $('#fixed_top').hide();
		document.title = $('#fixed_top .content').html();
	}

	var staffUser = common.getCache('store_staff', true);
	if (common.checkWeixin()) {
		if (urlParam.openid) {
			if (urlParam.openid != staffUser.openid) {
				staffUser.openid = urlParam.openid;
				common.setCache('store_staff', staffUser, true);
				common.http('Storestaff&a=save_openid', { openid: staffUser.openid }, function (data) {
					motify.log(L_('已成功绑定微信'));
				});
			}
		}
		if (staffUser.openid) {
			$('.openid .enquire').html(L_('已绑定'));
		} else {
			$('.openid .enquire').html(L_('未绑定'));
		}
		$('.openid').show();
	}

	if (!staffUser.is_notice || staffUser.is_notice == 0) {
		$('.notice .enquire').html(L_('接收'));
	} else {
		$('.notice .enquire').html(L_('不接收'));
	}

	//判断多语言
	var config = common.getCache('config', true);
	if (config.lang_config && config.lang_config.lang_list && config.lang_config.lang_list.length > 0) {
		for (var i in config.lang_config.lang_list) {
			if (config.lang_config.lang_list[i].val == now_lang) {
				$('#now_lang').html(config.lang_config.lang_list[i].display).show();
			}
		}
	}

	//联系客服
	if (config.site_phone && config.site_phone != '') {
		$('.contactPhone .enquire').html(config.site_phone).click(function () {
			location.href = 'tel:' + config.site_phone;
		});
	} else {
		$('.contactPhone').hide();
	}

	$('#now_lang').click(function (event) {
		var itemArr = {};
		for (var i in config.lang_config.lang_list) {
			itemArr[config.lang_config.lang_list[i].val] = config.lang_config.lang_list[i].display;
		}
		common.actionsheet({
			itemArr: itemArr,
			showCancel: false,
			success: function (index) {
				$('#now_lang').html(itemArr[index]);
				common.removeCache('all_lang', true);
				all_lang = null;
				now_lang = index;
				common.setCache('now_lang', now_lang, true);

				get_lang_list(index, 'changeLanguageOk');
			}
		});
	});

	$('.notice').click(function (event) {
		var itemArr = [L_('接收'), L_('不接收')];
		common.actionsheet({
			itemArr: itemArr,
			showCancel: false,
			success: function (index) {
				common.http('Storestaff&a=save_notice', { is_notice: index }, function (data) {
					staffUser.is_notice = index;
					common.setCache('store_staff', staffUser, true);
					$('.notice .enquire').html(itemArr[index]);
				});
			}
		});
	});
	$('.cache').click(function (event) {
		if (flag == true) {
			return false;
		}
		flag = true;
		now_device_id = common.getCache('device_id', true);
		now_ticket = common.getCache('ticket', true);
		clearcache_index = layer.open({ type: 2, shade: false, shadeClose: false });
		if (common.checkApp()) {
			if (common.checkAndroidApp()) {
				window.pigcmspackapp.clearcache('clearFunc');
			} else {
				common.iosFunction('clearcache/clearFunc', { name: 'clearcache', callback: 'clearFunc' });
			}
			setTimeout(function () {
				if (clearcache_index != null) {
					clearFunc();
				}
			}, 2000);
		} else {
			clearFunc();
		}
	});
	$('.openid').click(function (event) {
		layer.open({
			content: staffUser.openid ? L_('您确认前往重新授权绑定微信？') : L_('您确认前往绑定微信？')
			, btn: [L_('前往'), L_('取消')]
			, yes: function (index) {
				location.href = window.location.protocol + '//' + requestDomain + '/wap.php?c=Packapp&a=bind&referer=' + encodeURI(location.href);
				layer.close(index);
			}
		});
	});
	$('.cancellation').click(function (event) {
		layer.open({
			content: L_('注销后无法恢复，请慎重！<br/>您确定要注销当前账号吗？')
			, btn: [L_('确定'), L_('取消')]
			, yes: function (index) {
				common.http('Storestaff&a=cancelAccount', {}, function (data) {
					common.removeCache('ticket', true);
					localStorage.removeItem('storestaff_v20_ticket')
					common.removeCache('ticket');
					if (common.checkApp()) {
						common.setCache('isLogout', 'true', true);
						if (common.checkAndroidApp()) {
							window.pigcmspackapp.closewebview(2);
						} else {
							common.iosFunction('closewebview/2', { name: 'closewebview', type: '2' });
						}
					} else {
						location.href = 'login.html';
					}
					layer.close(index);
				});
			}
		});
	});
	$('#logout').click(function () {
		layer.open({
			content: L_('您确定要退出吗？')
			, btn: [L_('确定'), L_('取消')]
			, yes: function (index) {
				common.http('Storestaff&a=logout', {}, function (data) {
					common.removeCache('ticket', true);
					localStorage.removeItem('storestaff_v20_ticket')
					common.removeCache('ticket');
					if (common.checkApp()) {
						common.setCache('isLogout', 'true', true);
						if (common.checkAndroidApp()) {
							window.pigcmspackapp.closewebview(2);
						} else {
							common.iosFunction('closewebview/2', { name: 'closewebview', type: '2' });
						}
					} else {
						location.href = 'login.html';
					}
					layer.close(index);
				});
			}
		});
	});

	//检测新版本
	var new_app_url = '';
	var new_app_bao = '';
	if (common.checkAndroidApp()) {
		common.http('Storestaff&a=config', { 'from': 'setting' }, function (data) {
			var new_staff_android_vcode = parseInt(data.staff_android_vcode);
			var now_staff_android_vcode = parseInt(common.getVersion());
			if (now_staff_android_vcode && now_staff_android_vcode < new_staff_android_vcode) {
				$('.checkVersion .enquire').html(L_('有新版本') + ' (' + data.staff_android_v + ')');
				new_app_url = data.staff_android_url;
				new_app_bao = data.storestaff_android_package_name;
			} else {
				$('.checkVersion .enquire').html(L_('已最新'));
			}
		});
		$('.checkVersion').show();
	}
	$('.checkVersion').click(function () {
		if (new_app_url) {
			if (common.getVersion() >= 1444) {
				motify.log(L_('后台下载中，稍候会弹出安装页面。') + '<br/>' + L_('您也可以在通知栏中查看下载进度。'));
				window.pigcmspackapp.downLoadApp(new_app_url, new_app_bao);
			} else {
				motify.log(L_('老版本只能通过浏览器下载'));
				window.pigcmspackapp.openBrowser(new_app_url);
			}
		}
	});

	//修改密码
	$('.changePwd').click(function () {
		openWebviewUrl('changePwd.html');
	})
});

function changeLanguageOk() {
	common.http('Storestaff&a=config', {}, function (data) {
		androidUrl = data.mer_android_download_url;
		iosUrl = data.mer_ios_download_url;
		androidBao = data.mer_android_package_name;//安卓包名
		iosBao = data.mer_ios_package_name;//ios包名
		common.setCache('config', data, true);
		common.http('Storestaff&a=index', {}, function (data) {
			common.setCache('indexData', data, true);
			common.setCache('changeLanguageOk', '1', true);
			open_miniapp_count = 0; // 切换语言后重置小程序多语言
			location.reload();
		});
	});
}

function clearFunc() {
	common.removeAllCache(false);
	common.removeAllCache(true);
	if (now_device_id) {
		common.setCache('device_id', now_device_id, true);
	}
	common.setCache('ticket', now_ticket, true);
	layer.close(clearcache_index);
	clearcache_index = null;
	common.http('Storestaff&a=login', {}, function (data) {
		if (data.device_id) {
			common.setCache('device_id', data.device_id, true);
		}
		common.setCache('ticket', data.ticket);
		common.setCache('ticket', data.ticket, true);
		common.setCache('store_staff', data.user, true);
		localStorage.setItem('storestaff_v20_ticket', data.v20_ticket)
		common.http('Storestaff&a=index', {}, function (data) {
			flag = false;
			common.setCache('indexData', data, true);
			if (!common.checkApp()) {
				applicationCache.update();
			}
			motify.log(L_('清空缓存成功'));
		}, function (data) {
			motify.log(data.errorMsg);
			flag = false;
		});
	}, function (data) {
		motify.log(data.errorMsg);
		flag = false;
	});
}

if (common.checkApp()) {
	$('.p_ban').html('v ' + common.getVersionName())
}
//调整音量
var appVolume = 0;
function getCurrentVolume(volume) {
	appVolume = parseInt(volume * 100);
	$('.changeVolume .num').html(appVolume + '%');
	$('.changeVolume').show();
}

setTimeout(function () {
	if (common.checkApp() && common.getVersion() >= 1410) {
		if (common.checkAndroidApp()) {
			window.pigcmspackapp.getCurrentVolume('getCurrentVolume');
		} else {
			common.iosFunction('getCurrentVolume/' + getCurrentVolume, { name: 'getCurrentVolume', callback: 'getCurrentVolume' });
		}

		$('.changeVolume .plus').click(function () {
			appVolume = appVolume + 10;
			if (appVolume > 100) {
				appVolume = 100;
			}
			if (common.checkAndroidApp()) {
				window.pigcmspackapp.setVolume(appVolume / 100);
			} else {
				common.iosFunction('setVolume/' + (appVolume / 100), { name: 'setVolume', volume_value: appVolume / 100 });
			}
			$('.changeVolume .num').html(appVolume + '%');
		});

		$('.changeVolume .min').click(function () {
			appVolume = appVolume - 10;
			if (appVolume < 0) {
				appVolume = 0;
			}
			if (common.checkAndroidApp()) {
				window.pigcmspackapp.setVolume(appVolume / 100);
			} else {
				common.iosFunction('setVolume/' + (appVolume / 100), { name: 'setVolume', volume_value: appVolume / 100 });
			}
			$('.changeVolume .num').html(appVolume + '%');
		});
	}
}, 500);

$('#goAgreement').on("click", function () {
	openWebviewUrl('agreement.html');
});

$('#goUserAgreement').on("click", function () {
	openWebviewUrl('userAgreement.html');
});

var privacy_policy_md5 = '';
if (common.checkApp()) {
	common.http('Storestaff&a=get_privacy_policy', { noTip: true }, function (data) {
		privacy_policy_md5 = data.privacy_policy_md5;
		if (data.privacy_policy) {
			$('.fixedAgreement').show();
		}
	})
}

