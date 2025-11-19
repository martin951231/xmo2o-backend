if(common.checkWeixin()){
	$('.mui-bar-nav').remove();
}

var config = {}
var phone_country_type_arr=[];
var phone_country_type_txt='';
common.http('Merchantapp&a=config', {}, function(data){
	common.setCache('config',data,true);
	config=common.getCache('config',true); 
	config_loaded(data);
});
var merchant_type_switch = 0;

function config_loaded(config){  
	if(config.international_phone == '1'){
		$('.phone_country').show();
		
		$.each(config.national_phone,function(i,val){
			phone_country_type_arr.push({'value':val.code,'text':'+'+val.code+' '+val.national})
		})
	}  
}

mui.init();

mui('.mui-content').on('tap','.sendCode',function(e){   
	if(phone_country_type_arr.length > 0 && phone_country_type_txt == ''){
        mui.alert(L_('请选择国家/区号'));
        return false;
    }
	common.http('Merchantapp&a=sendCode',{phone:$('#phone').val(), 'type':4, phone_country_type:phone_country_type_txt}, function(data){
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

mui.init();
(function($, doc) {
	$.init();
	$.ready(function(){ 
		mui('.mui-content').on('tap','#phone_country_type',function(e){
			var userPicker = new $.PopPicker();
			userPicker.setData(phone_country_type_arr);	
			userPicker.pickers[0].setSelectedValue(phone_country_type_txt);
			userPicker.show(function(items) {
				document.getElementById('phone_country_type_click').innerHTML =items[0].text;
				document.getElementById('phone_country_type_click').style.color='#535353';
				phone_country_type_txt=items[0].value;
				//返回 false 可以阻止选择框的关闭
				//return false;
			});
		});
		
	});
})(mui, document);



mui('.mui-content').on('tap','.yes_mofidy',function(e){  
    if(phone_country_type_arr.length > 0 && phone_country_type_txt == ''){
        mui.alert(L_('请选择国家/区号'));
        return false;
    } 
	if((phone_country_type_arr.length == 0 || phone_country_type_txt == '86') && $('#phone').val().length != 11){
		mui.alert('请输入11位的手机号码');
	}else if($('#smsCode').val().length != 4){
		mui.alert('请输入4位的短信验证码');
	}else if($('#newPwd').val().length < 6){
		mui.alert('请输入6位以上的新密码');
	}else{
		var postData = {
			type:4,
			phone:$('#phone').val(),
			smsCode:$('#smsCode').val(),
			newPwd:$('#newPwd').val(),
            phone_country_type:phone_country_type_txt
            
		};
		common.http('Merchantapp&a=changePwd',postData,function(data){
			motify.log('密码修改成功，请使用新密码登录');
		setTimeout(function(){
                    openWindow({
		        url:'login.html',
		             id:'login'
	           });
                }, 3000);



		});
	}
});