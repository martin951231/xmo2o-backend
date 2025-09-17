function change_window(){
	var login_form_top = parseInt($('.pubHeader').height() + ($(window).height() - $('.pubHeader').height() - 50 - $('.login-form').height())/2);
	if(login_form_top < 200 || parseInt($('.login-form').css('top').replace('px','')) < 200){
		$('.login-form').css('top', login_form_top);
	}
}
change_window();
$(window).resize(function(){
	change_window();
});


var province_list = [];
var city_list = [];
var area_list = [];
var tmp_location = {};

//短信地区代码
if($('.js-select-list').size() > 0){
	var now_area = $('.js-select-list li').eq(0).data('area');
	$('.js-area-num').html(now_area);
	$('#phone_country_type').val(now_area);
}
$('.js-arrow-select').click(function(){
	$('.js-select-list').toggle();
});
$('.js-select-list li').click(function(){
	var now_area = $(this).data('area');
	$('.js-area-num').html(now_area);
	$('#phone_country_type').val(now_area);
	$('.js-select-list').toggle();
});

//密码眼睛
$('.ui-toggle').click(function(){
	if($('#reg_pwd').prop('type') == 'password'){
		$('#reg_pwd').prop('type','text');
		$('.ui-toggle').addClass('active');
	}else{
		$('#reg_pwd').prop('type','password');
		$('.ui-toggle').removeClass('active');
	}
});

$('.address-bg').click(function(){
	$('.address.dropdown-menu,.address-bg').toggle();
});

$('address').click(function(e){
	if(province_list.length == 0){
		return false;
	}
	console.log(province_list);
	$('.address.dropdown-menu').css({left:($(window).width() - $('.address.dropdown-menu').width()) / 2});
	$('.address.dropdown-menu').css({top:($(window).height() - $('.address.dropdown-menu').height()) / 2});
	$('.address.dropdown-menu,.address-bg').toggle();
});

$.post(choose_location,function(result){
	$('#reg_area').attr('placeholder',result.info.province_name+'/'+result.info.city_name);
	$('#choose_province').val(result.info.province_id);
	$('#choose_city').val(result.info.city_id);
	
	if(result.info.many_city){
		$.post(choose_province,function(result){
			result = $.parseJSON(result);
			if(result.name && !result.list){
				result.list = [{
					id: result.id,
					name: result.name
				}];
			}
			province_list = result.list;
			console.log('province_list', province_list);
			$('.address-tab a[data-type="province"]').trigger('click');
		});
	}
});
//切换语言
$("#change_lang").bind("change",function(){
	var _val = $(this).find(':selected').val();
	var _url = $(this).data('url');
	var _this = $(this);
	layer.confirm(L_('您确定要切换当前语言环境吗？'),{icon: 3, title:L_('提示')}, function(index){
		$.post(_url,{lang:_val},function(data){
			if(data.status == 1){
				location.href = location.href;
			}
		},'json');
		layer.close(index);
	});
})
$('.w_pointer span').live('click',function(){
	var type = $(this).data('type');
	$('.w_pointer span').removeClass('active');
	$(this).addClass('active');
	if(type != 'area'){
		
		if(type == 'province'){
			tmp_location.province_id = $(this).data('id');
			tmp_location.province_name = $(this).data('name');
			$.post(choose_city,{id:$(this).data('id')},function(result){
				result = $.parseJSON(result);
				if(result.error == 2){
					result.list = [{id:result.id,name:result.name}];
				}
				city_list = result.list;
				$('.address-tab a[data-type="city"]').trigger('click');
			});
		}else if(type == 'city'){
			tmp_location.city_id = $(this).data('id');
			tmp_location.city_name = $(this).data('name');
			$.post(choose_area,{id:$(this).data('id')},function(result){
				result = $.parseJSON(result);
				if(result.error == 2){
					result.list = [{id:result.id,name:result.name}];
				}
				area_list = result.list;
				$('.address-tab a[data-type="area"]').trigger('click');
			});
		}
	}else{
		tmp_location.area_id = $(this).data('id');
		tmp_location.area_name = $(this).data('name');
		$('#reg_area').attr('placeholder',tmp_location.province_name+'/'+tmp_location.city_name+'/'+tmp_location.area_name);
		$('#choose_province').val(tmp_location.province_id);
		$('#choose_city').val(tmp_location.city_id);
		$('#choose_area').val(tmp_location.area_id);
		$('.address.dropdown-menu,.address-bg').toggle();
	}
});

$('.address-tab a').click(function(){
	$(this).addClass('active').siblings().removeClass('active');
	if($(this).data('type') == 'province'){
		var html = '';
		for(var i in province_list){
			html+=  '<div class="col-sm-3 address-list">';
			html+=  '	<div class="w_pointer">';
			html+= '		<span class="'+(province_list[i].id == tmp_location.province_id ? 'active' : '')+'" data-type="province" data-id="'+province_list[i].id+'" data-name="'+province_list[i].name+'">'+province_list[i].name+'</span>';
			html+= '	</div>';
			html+= '</div>';
		}
		$('.address-body').html(html);
	}else if($(this).data('type') == 'city'){
		var html = '';
		for(var i in city_list){
			html+=  '<div class="col-sm-3 address-list">';
			html+=  '	<div class="w_pointer">';
			html+= '		<span class="'+(city_list[i].id == tmp_location.city_id ? 'active' : '')+'" data-type="city" data-id="'+city_list[i].id+'" data-name="'+city_list[i].name+'">'+city_list[i].name+'</span>';
			html+= '	</div>';
			html+= '</div>';
		}
		$('.address-body').html(html);
	}else if($(this).data('type') == 'area'){
		var html = '';
		for(var i in area_list){
			html+=  '<div class="col-sm-3 address-list">';
			html+=  '	<div class="w_pointer">';
			html+= '		<span class="'+(area_list[i].id == tmp_location.area_id ? 'active' : '')+'" data-type="area" data-id="'+area_list[i].id+'" data-name="'+area_list[i].name+'">'+area_list[i].name+'</span>';
			html+= '	</div>';
			html+= '</div>';
		}
		$('.address-body').html(html);
	}
});

function smsVerify(code){
	$('#sms_flesh_verify').val(code);
	layer.closeAll();
	$('.ui-getcode').trigger('click');
}

$('.ui-getcode').click(function(){
	var that = $(this);
	$('.login-form .msg-error').addClass('hide');
	if($('#reg_phone').val().length < 1 || (!international_phone && !/^[0-9]{11}$/.test($('#reg_phone').val()))){
		notice('请输入正确的手机号码~','error');
		$('#reg_phone').focus();
		return false;
	}
	if($('#sms_flesh_verify').val() == ''){
		layer.open({
			title:L_('发送短信前请验证短信码'),
			type:2,
			area:['430px','200px'],
			content:'./index.php?g=Index&c=Verify&a=verify_fleshcode&page_type=var&'+Math.random()
		});
		return false;
	}
	if(that.hasClass('disabled')){
		return false;
	}
	that.addClass('disabled').html(L_('发送中'));
	var postData = {phone: $("#reg_phone").val()};
	if($('.js-area-num').size() > 0){
		postData.phone_country_type = $('.js-area-num').html();
	}
	$.post('./merchant.php?c=Login&a=sendsms',postData,function(data){
		if(data.status == 1){
			var time = 60;
			that.html(time+'s');
			var sms_timer = setInterval(function(){
				if(time == 0){
					clearTimeout(sms_timer);
					that.removeClass('disabled').html(L_('获取验证码'));
				}else{
					time--;
					that.html(time+'s');
				}
				
			},1000);
		}else{
			layer.alert(data.info);
			that.removeClass('disabled').html(L_('获取验证码'));
		}
	});
});

var qrcode_id = 0;
var qrcode_ajax = null;
$('.login-form .login-tab').click(function(){
	$('.login-form .login-tab a').removeClass('checked');
	$(this).find('a').addClass('checked');
	if($(this).hasClass('login-tab-l')){
		$('.login-form .login-box').hide();
		$('.login-form .qrcode-login').show();
	}else{
		$('.login-form .qrcode-login').hide();
		$('.login-form .login-box').show();
		$('#login_account').focus();
	}
});

$('#login_account,#login_pwd').focus(function(){
	$(this).closest('.item').addClass('item-focus');
}).blur(function(){
	$(this).closest('.item').removeClass('item-focus');
}).keyup(function(e){
	if($(this).val() != ''){
		$(this).siblings('.clear-btn').show();
	}else{
		$(this).siblings('.clear-btn').hide();
	}
	if(e.keyCode == 13){
		$('.login-btn').trigger('click');
	}
});
$('#login_verify').keyup(function(e){
	if(e.keyCode == 13){
		$('.login-btn').trigger('click');
	}
});

$('.form .clear-btn').click(function(){
	$(this).hide().siblings('input').val('').focus();
});

function notice(msg,pic){
	if(pic == 'error'){
		$('.login-btn').html(L_('登录')).removeClass('disabled');
	}
	$('.login-form .msg-error').html((pic=='error' ? '<b></b>' : '') + msg).removeClass('hide');
}

$('#reg_form').submit(function(){
	if($('.login-btn').hasClass('disabled')){
		return false;
	}
	$('.login-btn').html(L_('登录中')).addClass('disabled');
	$('.login-form .msg-error').addClass('hide');
	
	if($('#reg_phone').val().length < 1 || (!international_phone && !/^[0-9]{11}$/.test($('#reg_phone').val()))){
		notice('请输入正确的手机号码~','error');
		$('#reg_phone').focus();
	}else if($('#reg_sms').length>0 && $('#reg_sms').val().length != 4){
		notice('请输入短信验证码~','error');
		$('#reg_sms').focus();
	}else if($('#reg_pwd').val().length < 6){
		notice('请输入至少 6 个字符的密码~','error');
		$('#reg_pwd').focus();
	}else if($('#reg_name').val() == ''){
		notice('商户名称必填~','error');
		$('#reg_name').focus();
	}else if(merchant_type_switch == 1 && $('#company_name').val() == ''){
        notice('公司名称必填~','error');
        $('#company_name').focus();
	}else{
		$.post(reg_check,$("#reg_form").serialize(),function(result){
			result = $.parseJSON(result);
			if(result){
				if(result.error == 0){
					notice(result.msg,'ok');
					layer.alert(result.msg,{
						end:function(){
							location.href = './merchant.php?c=Login&a=index';
						}
					});
				}else{
					$('#login_'+result.dom_id).focus();
					notice(result.msg,'error');
				}
			}else{
				notice(L_('登录出现异常，请重试！'),'error');
			}
		});
	}
	return false;
});

setTimeout(function(){
	$('#login_account,#login_pwd').val('');
	$('.form .clear-btn').hide();
},1000);

function login_fleshVerify(url){
	var time = new Date().getTime();
	$('#login_verifyImg').attr('src',url+"&time="+time);
}
function reg_fleshVerify(url){
	var time = new Date().getTime();
	$('#reg_verifyImg').attr('src',url+"&time="+time);
}

var wx_qrcode_url = $('#wx_qrcode').attr('src');
$('.qrcode-error-2016 .refresh-btn').click(function(){
	$('#wx_qrcode').attr('src',wx_qrcode_url);
	$('.qrcode-error-2016').hide();
	$('.system_page_error').empty();
	$.post('./index.php?g=Index&c=Recognition&a=see_login_qrcode&content_type=json',function(result){
		if(result.status == 1){
			$('#wx_qrcode').attr('src',result.info.src);
			qrcode_id = result.info.qrcode_id;
			setTimeout(function(){
				ajax_weixin_login();
			},3000);
		}else{
			$('.qrcode-error-2016').show().find('.err-cont').html(L_('服务器异常'));
			$('.system_page_error').html('<span>'+L_('服务器返回：')+'</span>'+result.info);
		}
	});
});
$('.qrcode-error-2016 .refresh-btn').trigger('click');


window.onbeforeunload = function(){
	if(qrcode_ajax){
		qrcode_ajax.abort();
	}
}
function ajax_weixin_login(){
	qrcode_ajax = $.get("./index.php?c=Login&a=ajax_weixin_login&longhttp=false",{qrcode_id:qrcode_id},function(result){
		result = $.trim(result);
		if(result == 'reg_user'){
			$('#login_status').html(L_('已扫描！请在微信公众号里点击授权登录。')).css('color','green').show();
			ajax_weixin_login();
		}else if(result == 'no_user'){
			$('#login_status').html(L_('没有查找到此用户，请重新扫描二维码！')).css('color','red').show();
		}else if(result!=='true'){
			setTimeout(function(){
				ajax_weixin_login();
			},3000);
		}else{
			$('#login_status').html(L_('登录成功！正在跳转。')).css('color','green').show();
			window.top.location.href = redirect_url;
		}
	});
}

