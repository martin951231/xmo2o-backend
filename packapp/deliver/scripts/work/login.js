$(document).ready(function(){
	if (common.getVersion() >= 1300) {
		common.appFunction({name:"closeStartupPage"});
	}

	$("body").css({"height":$(window).height()});
	
	$(".Land_end input").blur(function(){
		if ($(this).val() == '') {
			return 
		}
		$(this).siblings("a").show();
	});
	$(".Land_end a").click(function(){
		$(this).hide();
		$(this).siblings("input").val("");
	});     
	
	var ticket = common.getCache('ticket');
	if (ticket) {
		redirect_self('index.html');
		return false;
	}
	
	$('.fillBg').css('min-height',$(window).height());
	if(common.checkWeixin()){
		common.fillPageBg(1,'#ebf3f8');
	}

	common.http('Config&a=get_reg',{}, function(data){
    	if(data.reg == '1'){
    		$("#reg_account").show();
    	}
    });

    $(".reg_btn").click(function(){
    	var phone = $('#login_phone').val(), pwd = $('#login_pwd').val();
		if (phone.length < 1) {
			motify.log("登录手机号不能为空");
			return false;
		}
		if (pwd.length < 1) {
			motify.log("登录密码不能为空");
			return false;
		}
    	common.http('Config&a=get_reg',{'account':phone, 'passwd':pwd}, function(data){
			motify.log(L_("注册成功！请等待平台审核"));
			setTimeout(function(){
				window.location.replace('login.html')
			},1500)
	    });
    });
	
	
	common.http('Deliver&a=lang',{noTip:true}, function(data){
		if(data.lang_config && data.lang_config.now_lang && (data.lang_config.now_lang != 'chinese' || (data.lang_config.lang_list && data.lang_config.lang_list.length > 0))){
			common.setCache('lang_list',data.lang_config.lang_list,true);
			get_lang_list(data.lang_config.now_lang);	
		}
	});

	$('#login_pwd').keyup(function(e){
		if(e.keyCode == 13){
			$("#login_form").trigger('click');
			return false;
		}
	});
	$("#login_form").click(function(){
		var phone = $('#login_phone').val(), pwd = $('#login_pwd').val();
		if (phone.length < 1) {
			motify.log("登录手机号不能为空");
			return false;
		}
		if (pwd.length < 1) {
			motify.log("登录密码不能为空");
			return false;
		}
		var client = common.checkAndroidApp()  ?  2 : (common.checkIosApp() ? 1 : 0);
		common.http('Deliver&a=login', {'phone':phone, 'pwd':pwd, 'client':client}, function(data){
			common.setCache('ticket',data.ticket,true);
			common.setCache('deliver_user',data.user,true);
			common.setCache('ticket',data.ticket);
			
			if(common.getVersion()>=800){
				if(common.checkAndroidApp() && (typeof window.pigcmspackapp.getUPushToken == 'object' || typeof window.pigcmspackapp.getUPushToken == 'function')){
					var device_token = window.pigcmspackapp.getUPushToken();	
					if(device_token){
						common.http('Deliver&a=get_device_token', {'device_token': device_token}, function (data) {
							redirect_self((urlParam.back ? urlParam.back : 'index')+'.html');
						});
					}else{
						redirect_self((urlParam.back ? urlParam.back : 'index')+'.html');
					}
				}else{
					redirect_self((urlParam.back ? urlParam.back : 'index')+'.html');
				}
			}else{
				redirect_self((urlParam.back ? urlParam.back : 'index')+'.html');
			}
		});
	})
});

var exitLayer = -1;
function appbackmonitor(){
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
		window.lifepasslogin.closeWebView();
	}else{
		if (common.getVersion() >= 1300) {
			window.pigcmspackapp.returnToHomePage();
			return false 
		}
		if(exitLayer != -1){
			window.pigcmspackapp.closewebview(2);
		}else{
			layer.closeAll();
			exitLayer = layer.open({
				content: '您确定要退出程序吗？再次按返回键将退出。'
				,btn: ['确定', '取消']
				,yes: function(index){
					window.pigcmspackapp.closewebview(2);
					layer.close(index);
				}
				,end: function(index){
					exitLayer = -1;
				}
			});
		}
	}
}  
 
 // 隐私协议    
 var lastAgreeTime='';
 var privacy_policy_md5='';   

if(common.checkApp()){  
     common.http('Deliver&a=get_privacy_policy',{noTip:true}, function(data){ 
          privacy_policy_md5=data.privacy_policy_md5; 
          if(data.privacy_policy){
            $('.fixedAgreement').show();  
            if (common.checkIosApp()) {  
                common.iosFunction('getAgreement/getAgreementOk', {name: 'getAgreement',callback:'getAgreementOk'});     
             }else{  
                window.pigcmspackapp.getAgreement('getAgreementOk');
            } 
          } 
     })   
 } 
 function getAgreementOk(option){    
    if(!option || privacy_policy_md5.trim() != option.trim()){ 
      agreementShow();
    } 
}
function agreementShow(){
     weui.confirm(L_('<div class="agreementCon"><p>感谢您信任并使用我们的软件！</p> <p>我们依据最新的法律要求更新了用户协议和隐私政策，特向您推送本提示。请您仔细阅读并充分理解相关条款，其中的重点条款已为您标注，方便您了解自己的权利。</p> <p>我们将通过《隐私政策》帮助您了解我们收集、使用、存储和共享个人信息的情况，以及您所享有的相关权利。并向您介绍了我们公司主体的基本信息以及告知了我们的联系通道与方式。</p><p>您点击"同意"，即表示您已阅读并同意上述协议条款，我们将尽全力保障您的合法权益并继续为您提供优质的产品和服务。如您点击"不同意"，将可能导致无法继续使用我们的产品和服务。</p><p style="color:#999">您可通过阅读完整版<a href="userAgreement.html" class="agreementLink">《用户协议》</a>和<a href="agreement.html" class="agreementLink">《隐私政策》</a>了解详尽的条款内容。</p></div>'), {
          title: L_('温馨提示'),
          buttons: [{
              label: L_('不同意'), 
              type: 'gray', 
              onClick: function(){   
                $('.mark').show();
              }
          }, {
              label: L_('同意'), 
              type: 'primary', 
              onClick: function(){   
                 if (common.checkIosApp()) {  
                     common.iosFunction('saveAgreement/'+privacy_policy_md5, {name: 'saveAgreement',privacyPolicyMd5:privacy_policy_md5});    
                 }else{  
                     window.pigcmspackapp.saveAgreement(privacy_policy_md5);
                 } 
              }
          }]
      }); 
} 
 // function notice(){
 //     
 //     setTimeout(function(){
 //         weui.alert('<p class="textP"><img src="images/notice.png" style="width:50px"  /><br>'+L_('若不同意则不能正常使用本软件')+'！</p>', { 
 //            buttons: [{
 //                label:L_('知道了'),
 //                type: 'primary',
 //                onClick: function(){ 
 //                    setTimeout(function(){
 //                        agreementShow();
 //                    },350) 
 //                }
 //            }]
 //        });  
 //     },350);
 // } 
 $('.seen').click(function(){
     $('.mark').hide();
     agreementShow();
 })
$('#goAgreement').on("click",function(){
    openWebviewUrl('agreement.html');
}); 
$('#goUserAgreement').on("click",function(){
    openWebviewUrl('userAgreement.html');
}); 