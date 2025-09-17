
var from = common.getCache('from',true); 
 

// 点击问号
$('.notice').click(function(){
	var ccv_alert = weui.alert('<img src="images/ccv_tip.png" style="width:93px"/><p class="p1 L_">'+L_('CCV码是信用卡背面的3位数字，美国运通卡（American Express）是正面4位数字')+'</p>', {
		title: L_('什么是CCV')+' ？',
		className:'ccv_tip_div',
		buttons: [{
			label: L_('知道了'),
			type: 'primary',
			onClick: function(){
				
			}
		}]
	});
	$('.ccv_tip_div .weui-dialog__ft').remove();
	$('.ccv_tip_div .weui-dialog__hd').append('<div class="close"><img src="images/close.png" style="width:26px; height:26px" /></div>');
	
	$('.ccv_tip_div .weui-dialog__hd .close').click(function(){
		ccv_alert.hide();
	});
})


// 过期时间
$('#datePicker').click(function(){   
	weui.datePicker({ 
		start: new Date().getFullYear(),
		end: '2050-12-12',  
		defaultValue: [new Date().getFullYear() + 2,1,1],
		onConfirm: function(result){ 
			var yy=result[0].value.toString().substring(2);
				var mm=''
				if(result[1].value<10){
					mm=0+result[1].value.toString();  
				}else{
					mm=result[1].value.toString();
				} 
			$('#datePicker').val(mm+"/"+yy);
			console.log(yy+"/"+mm); 

			check_form();
		},
		onChange:function(){
			var len=$('.expire_timer .weui-picker__group').length;
			for(var i=0; i<len; i++){
				$('.expire_timer .weui-picker__group:eq('+i+') .weui-picker__content .weui-picker__item').each(function(){
					var pickHtml=$(this).text().toString();  
					pickHtml=pickHtml.replace(/[^0-9]/ig,"");
					$(this).html(pickHtml);
				})
			} 
		},
		id: 'datePicker',
		className: 'expire_timer'
	}); 
	$('.expire_timer .weui-picker__group:eq(2)').remove(); 
	$('.expire_timer .weui-picker__group:eq(0)').css('float','right');
	$('.expire_timer .weui-picker__group:eq(1)').css('float','left');
	$('.expire_timer .weui-picker__group').css('width','50%');
	$('.weui-picker__bd').css('display','block')

	var len=$('.expire_timer .weui-picker__group').length;
	for(var i=0; i<len; i++){
		$('.expire_timer .weui-picker__group:eq('+i+') .weui-picker__content .weui-picker__item').each(function(){
			var pickHtml=$(this).text().toString();  
			pickHtml=pickHtml.replace(/[^0-9]/ig,"");
			$(this).html(pickHtml);
		})
	} 
})  
 
// 银行卡的输入格式判断
function formatBankNo (BankNo){  
	if (BankNo.value == "") return;
	var account = new String (BankNo.value);
	account = account.substring(0,23); /*帐号的总数, 包括空格在内 */
	if (account.match (".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}") == null){
		/* 对照格式 */
		if (account.match (".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}|" + ".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}|" +
		".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}|" + ".[0-9]{4}-[0-9]{4}-[0-9]{4}-[0-9]{7}") == null){
			var accountNumeric = accountChar = "", i;
			for (i=0;i<account.length;i++){
				accountChar = account.substr (i,1);
				if (!isNaN (accountChar) && (accountChar != " ")) accountNumeric = accountNumeric + accountChar;
			}
			account = "";
			for (i=0;i<accountNumeric.length;i++){    /* 可将以下空格改为-,效果也不错 */
				if (i == 4) account = account + " "; /* 帐号第四位数后加空格 */
				if (i == 8) account = account + " "; /* 帐号第八位数后加空格 */
				if (i == 12) account = account + " ";/* 帐号第十二位后数后加空格 */
				if (i == 16) account = account + " ";/* 帐号第十六位后数后加空格 */ 
				account = account + accountNumeric.substr (i,1)
			}
		}
	}
	else
	{
		account = " " + account.substring (1,5) + " " + account.substring (6,10) + " " + account.substring (14,18) + "-" + account.substring(18,25);
	}
	if (account != BankNo.value) BankNo.value = account;
}
 
// 验证表单
function check_form(){    
	var cardName = $.trim($('input[name="card_name"]').val());
	var cardNumber = $.trim($('input[name="card_number"]').val());
	var cardCvv = $.trim($('input[name="card_cvv"]').val());
	var cardExpiry = $.trim($('input[name="card_expiry"]').val());
	
	if(cardName != '' && cardNumber != '' && cardCvv != '' && cardExpiry != ''){
		$('.btn_next').addClass('red');
	}else{
		$('.btn_next').removeClass('red');
	}   
	
}

$('input[name="card_name"],input[name="card_cvv"],input[name="card_number"]').on('keyup',function(){
	check_form(); 
}); 

  check_form(); 
$('input[name="card_name"],input[name="card_cvv"],input[name="card_number"]').blur(function () {
	// 焦点离开时候自动滚动到顶部
	 window.scroll(0,0);
 });

// 请求
$('.btn_next').click(function(){ 
	if(!$(this).hasClass('red')){
		return false;
	}
	// 验证 
	if(check_form()){return false;}  
	var cardName = $.trim($('input[name="card_name"]').val());
	var cardNumber = $.trim($('input[name="card_number"]').val());
	var cardCvv = $.trim($('input[name="card_cvv"]').val()); 
	var card_expiry=$.trim($("input[name='card_expiry']").val());  
	var card_expiry_month=card_expiry.substring(-1,2); 
	var card_expiry_year=card_expiry.substring(3,5);   
      
	if(cardName.match(/\s+/g) != null ){ 
		if(cardName.match(/\s+/g).length != 1){
			$('input[name="cardName"]').focus();
			weui.topTips(L_('姓名输入格式不对，应该为 firstname空格lastname,并且输入字母！'));   
			return false;
		}  
		var regName=/^[A-Za-z]+$/;
		cardName=cardName.replace(/\s+/g, "");
		if(!regName.test(cardName)){
			weui.topTips(L_('姓名输入格式不对,应该输入字母！')); 
			$('input[name="card_number"]').focus(); 
			return false;
		}   

	}else{
		$('input[name="cardName"]').focus(); 
		weui.topTips(L_('姓名输入格式不对，应该为 firstname空格lastname,并且输入字母！'));  
		return false;
	}

	var regCard= /^[0-9]{16,19}$/; 
	cardNumber=cardNumber.replace(/\s*/g,"");  
	console.log(regCard.test(cardNumber)); 
	if(!regCard.test(cardNumber)){
		weui.topTips(L_('请正确填写卡号！')); 
		$('input[name="card_number"]').focus(); 
		return false;
	}  
	
	var regCcv= /^(\d{3}|\d{4})$/; 
	if(!regCcv.test(cardCvv)){
		weui.topTips(L_('请正确填写CCV！'));
		$('input[name="card_cvv"]').focus(); 
		return false;
	} 
	 
	if(from != 'pay' || $('#weuiAgree').is(":checked") == true){ 
		common.http('Hake_credit&a=addCard',{'card_name':cardName,'card_number':cardNumber,'card_expiry_year':card_expiry_year,'card_expiry_month':card_expiry_month,'card_cvv':cardCvv,'pay':from == 'pay' ? true : false},function(data){
			var id = data;
            redirect('inputAmount.html?id=' + id);
		});
	}else{    
		var orderid = common.getCache('orderid',true);
		common.http('Hake_credit&a=payCardRedirect',{'card_name':cardName,'card_number':cardNumber,'card_expiry_year':card_expiry_year,'card_expiry_month':card_expiry_month,'card_cvv':cardCvv,'pay':from == 'pay' ? true : false,'orderid':orderid},function(data){   
			$('body').append(data);
			document.forms['merchantwarrior_form'].submit(); 
		});
	}
})   
 
if(from == 'pay'){
	$('.checked_box').show();
	$('.btn_next').html(L_('立即支付'));
	document.title = L_('银行卡支付');
}else{ 
   $('.checked_box').hide(); 
} 
// 
$('.help_click').click(function(){ 
	weui.alert('<p class="textP">'+L_(' 哈客快捷支付带给您微信支付一样方便快捷安全的用户体验。开通哈客快捷支付您只需验证一次持卡者身份，以及设置支付密码。成功后，在每次订单支付时，输入支付密码，即可快速完成交易。')+'</p>', { 
		buttons: [{
			label:L_('知道了'),
			type: 'primary',
			onClick: function(){ console.log('ok') }
		}]
	}); 
}) 
// 小程序支付时绑定银行卡时候必须勾选
if(common.checkWeixinApp()){ 
    if(from == 'pay'){ 
        $('#weuiAgree').prop("checked",true);
        $('#weuiAgree').click(function(){
            $('#weuiAgree').prop("checked",true); 
            weui.alert('<p class="textP">'+L_("由于小程序限制，只能使用Hark快捷支付。")+'</p>', { 
            	buttons: [{
            		label:L_('知道了'),
            		type: 'primary',
            		onClick: function(){ 
                    }
            	}]
            }); 
        })
    }
}