if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}


var merchant_info = common.getCache('merchant_info',true);

if(merchant_info){
	$('#phoneTxt').html(merchant_info.phone.substr(0,3)+'****'+merchant_info.phone.substr(-4,4));
}
if(!merchant_info){
	common.http(
		"Merchantapp&a=login",
		{ noTip: true },
		function (data) {
		  common.setCache("merchant_info", data.user, true);
		  merchant_info = data.user;
		  $('#phoneTxt').html(merchant_info.phone.substr(0,3)+'****'+merchant_info.phone.substr(-4,4));
		},
		function (data) {}
	  );
}

var phone_country_type_arr=[];
var phone_country_type_txt='';

var config = common.getCache('config',true);
if(!config){
	common.http('Merchantapp&a=config', {}, function(data){
		common.setCache('config',data,true);
		config= data;
		check_config();
	});
}else{
	check_config();
}

function check_config(){
	if(config.open_merchant_reg_sms && config.open_merchant_reg_sms == '1'){
		$('.smscode').show();
	}
	if(config.international_phone && config.international_phone == '1'){
		$('.phone_country').show();
		$.each(config.national_phone,function(i,val){
			phone_country_type_arr.push({'value':val.code,'text':'+'+val.code+' '+val.national})
		});
	}
}

mui.init();

mui('.mui-content').on('tap','.sendCode',function(e){
	common.http('Merchantapp&a=sendCode',{}, function(data){
		motify.log('短信发送成功');
		$('.sendCode').data('second',59).html('59 秒').prop('disabled',true);
		var smsTimer = setInterval(function(){
			var second = $('.sendCode').data('second');
			if(second == 1){
				$('.sendCode').html('获取验证码').prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('.sendCode').data('second',second).html(second + ' 秒');
			}
		},1000);
	});
});

mui('.mui-content').on('tap','#phone_country_type',function(e){
	var userPicker = new mui.PopPicker();
	userPicker.setData(phone_country_type_arr);	
	userPicker.pickers[0].setSelectedValue(phone_country_type_txt);
	userPicker.show(function(items) {
		document.getElementById('phone_country_type_click').innerHTML =items[0].text;
		document.getElementById('phone_country_type_click').style.color='#535353';
		phone_country_type_txt=items[0].value;
	});
	replace_html_lang();
});

mui('.mui-content').on('tap','.sendCode2',function(e){
	if($('#new_phone').val()==''){
		motify.log(L_('新手机不能为空'));
		return false;
	}
	if(phone_country_type_arr.length > 0 && phone_country_type_txt == ''){
        mui.alert(L_('请选择国家/区号'));
        return false;
    }
	common.http('Merchantapp&a=sendCode',{newphone:$('#new_phone').val()}, function(data){
		motify.log(L_('短信发送成功'));
		$('.sendCode2').data('second',59).html('59 '+L_('秒')).prop('disabled',true);
		var smsTimer = setInterval(function(){
			var second = $('.sendCode2').data('second');
			if(second == 1){
				$('.sendCode2').html(L_('获取验证码')).prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('.sendCode2').data('second',second).html(second + ' '+L_('秒'));
			}
		},1000);
	});
});

mui('.mui-content').on('tap','.modify_password',function(e){
	if($('#smsCode').val().length != 4){
		mui.alert(L_('请输入4位的短信验证码'));
	}else if($('#newPwd').val().length < 6){
		mui.alert(L_('请输入6位以上的新密码'));
	}else if($('#newPwd').val() != $('#confirmPwd').val()){
		mui.alert(L_('两次输入密码不一致'));
	}else{
		var postData = {
			smsCode:$('#smsCode').val(),
			newPwd:$('#newPwd').val()
		};
		common.http('Merchantapp&a=changePwd',postData,function(data){
            common.setCache('is_Simple_Password','0',true);
			motify.log(L_('密码修改成功，下次请使用新密码登录')); 
             window.history.go(-1); 
		});
	}
});

mui('.mui-content').on('tap','.modify_phone',function(e){
	if($('#smsCode').val().length != 4 && config.open_merchant_reg_sms==1){
		mui.alert(L_('请输入4位的短信验证码'));
	}else if(!/^[0-9]{11}$/.test($('#new_phone').val())){
		mui.alert(L_('请输入正确的手机号码'));
	}else{
		var postData = {
			smsCode:$('#smsCode').val(),

			new_phone:$('#new_phone').val(),
			phone_country_type:phone_country_type_txt,
			smsCode2:$('#smsCode2').val()
		};
		common.http('Merchantapp&a=changePhone',postData,function(data){
			merchant_info.phone = $('#new_phone').val();
			common.setCache('merchant_info',merchant_info,true);
			motify.log(L_('手机修改成功，下次请使用新手机登录'));
			window.location.reload();
		});
	}
});

if(urlParam.back == 2){
    $('.go_back').attr('data-url','');
    $('.go_back').click(function(){
        window.location.href="index.html"
    })
    
}

