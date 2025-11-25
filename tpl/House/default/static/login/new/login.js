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

var qrcode_id = 0;
var qrcode_ajax = null;
$('.login-form .login-tab').click(function(){
	$('.system_page_error.login_tip').remove();
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

$('#login_status').change(function(){
	$(this).css('color', 'black');
});

$('#login_account,#login_pwd,#login_status').focus(function(){
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

$('.login-btn').click(function(){
	login();
	return false;
});

function login(msg) {
	if ((!msg || 'choose'!=msg) && $('.login-btn').hasClass('disabled')) {
        return false;
	}
    $('.login-btn').html(L_('登录中')).addClass('disabled');
    $('.login-form .msg-error').addClass('hide');
    if($('#login_account').val()==''){
        notice('请输入帐号~','error');
        $('#login_account').focus();
        return false;
    }else if($('#login_pwd').val()==''){
        notice(L_('请输入密码~'),'error');
        $('#login_pwd').focus();
    }else if($('#login_status').val()==''){
		notice(L_('请选择身份~'),'error');
		$('#login_pwd').focus();
	}else if($('#login_verify').val().length!=4){
        notice('请输入4位验证码~','error');
        $('#login_verify').focus();
    }else{
        $.post(login_check,$("#login_form").serialize(),function(result){
            result = $.parseJSON(result);
            if(result){
                if(result.error == 0){
                    notice(result.msg,'ok');
                    setTimeout(function(){
                        window.parent.location = house_index;
                    },1000);
                }else if(result.error == 7){
                    var html = '<ul class="village_list_select" style="max-height:'+($(window).height()*0.8)+'px;">';
                    $.each(result.house_list,function(i,item){
                        html+= '<li data-village_id="'+item.village_id+'" '+(i == 0 ? 'class="first"' : '')+'><div>'+item.village_name+'</div></li>';
                    });
                    html+= '</ul>';
                    art.dialog({
                        title: '请选择小区',
                        // background :'#600',
                        opacity:'0.4',
                        lock: true,
                        fixed: true,
                        resize: false,
                        padding:'25px 0',
                        content: html
                    });
                    click_reload();
                }else if(result.error == 11){//物业管理员
					console.log(property_login);
					window.location.href = property_login;
				}else{
                    $('#login_'+result.dom_id).focus();
                    if (result.error == 3 || result.error == 5 || result.error == 8) {
                        $('#village_id').val('');
					}
                    notice(result.msg,'error');
                }
            }else{
                notice('登录出现异常，请重试！','error');
            }
        });


    }
}

$('.village_list_select li').live('click',function(){
    var list = art.dialog.list;
    for (var i in list) {
        list[i].close();
    };
    $('#village_id').val($(this).data('village_id'));
    login('choose');
});
var is_reload=false;
function click_reload() {
	if (is_reload) {
		return false;
	}
    is_reload = true;
    $('.aui_close').click(function(){
        console.log('hahah ');
        $('.login-btn').html(L_('登录')).removeClass('disabled');
    });
}

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
	$('.system_page_error').empty().attr('style','');
	$.post('./index.php?g=Index&c=Recognition&a=see_login_qrcode&qrcode_type=merchant&content_type=json',function(result){
		if(result.status == 1){
			$('#wx_qrcode').attr('src',result.info.ticket);
			qrcode_id = result.info.id;
			setTimeout(function(){
				ajax_weixin_login();
			},2500);
		}else{
			$('.qrcode-error-2016').show().find('.err-cont').html(L_('服务器异常'));
			$('.system_page_error').html('<span>'+L_('服务器返回：')+'</span>'+result.info);
		}
	});
});
// $('.qrcode-error-2016 .refresh-btn').trigger('click');


window.onbeforeunload = function(){
	if(qrcode_ajax){
		qrcode_ajax.abort();
	}
}
function ajax_weixin_login(){
	$('.system_page_error.login_tip').remove();
	qrcode_ajax = $.get("./index.php?c=Login&a=ajax_weixin_login&longhttp=false",{qrcode_id:qrcode_id},function(result){
		result = $.trim(result);
		if(result == 'reg_user'){
			$('body').append('<div class="system_page_error login_tip" style="color:green;font-size:14px;">'+L_('已扫描！请在微信公众号里点击授权登录。')+'</div>');
			ajax_weixin_login();
		}else if(result == 'no_user'){
			$('body').append('<div class="system_page_error login_tip" style="color:red;font-size:14px;">'+L_('没有查找到此用户，请重新扫描二维码！')+'</div>');
			$('.qrcode-error-2016').show().find('.err-cont').html(L_('用户不存在'));
		}else if(result!=='true'){
			setTimeout(function(){
				ajax_weixin_login();
			},2500);
		}else{
			$('body').append('<div class="system_page_error login_tip" style="color:green;font-size:14px;">'+L_('登录成功！正在跳转。')+'</div>');
			$.post(login_check+'&login_type=user_openid',function(data){
				$('.system_page_error.login_tip').remove();
				data = $.parseJSON(data);
				if(data.error == '0'){
					$('body').append('<div class="system_page_error login_tip" style="color:green;font-size:14px;">'+data.msg+'</div>');
					setTimeout(function(){
						location.href = './merchant.php';
					},1000);
				}else{
					$('body').append('<div class="system_page_error login_tip" style="color:red;font-size:14px;">'+data.msg+'</div>');
				}
			});
		}
	});
}

