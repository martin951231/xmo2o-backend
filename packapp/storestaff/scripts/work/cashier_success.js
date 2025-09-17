var order_info = common.getCache('cashier_order_info',true);
var checkPayTimer = null;
var re_call = false;
var smsTimer;
var is_pufa = false;
var canScanAgain_ = true;
var open_company_advance_deposit = config.open_company_advance_deposit;
var internal_card_info = {}//一卡通
var config = common.getCache('config',true) || null;
if(config.internal_membership_card == 1){
	$('#internalCard_pay_method').show();
}else{
	$('#internalCard_pay_method').hide();
}
if (open_company_advance_deposit != undefined && parseInt(open_company_advance_deposit) == 1) {
	$('#qiye_pay_method').show();
}else{
	$('#qiye_pay_method').hide();
}
$(document).ready(function(){

	var indexDatas = common.getCache('indexData',true);
	console.log(indexDatas)
	if (typeof(indexDatas)!='undefined') {
		if (typeof(indexDatas.offlinepay)!='undefined') {
			if(indexDatas.offlinepay.length>0){
				laytpl($('#payoffline_tpl').html()).render(indexDatas.offlinepay, function(html){
					$('#offlinepay').append(html);
				});
			}
		}
	}
	
	$('.user_code,.scan_code').height($('.user_code').width());

	if(common.checkAndroidApp()  && typeof window.pigcmspackapp.isShowPfBtn == 'object'){
		window.pigcmspackapp.isShowPfBtn();
	}

	if(!order_info){
		if(common.checkApp()){
			if(common.checkAndroidApp()){
				window.pigcmspackapp.closewebview(2);
			}else{
				common.iosFunction('closewebview/2' ,{name:'closewebview', type: '2'});
			}
		}else{
			window.history.go(-2);
		}
	}
	$('#scan_code_img').attr('src',order_info.img);
	common.setData(order_info);
	if(order_info.txt_info == ''){
		$('#txt_info_dom').hide();
	}
	if(order_info.discount_money == ''){
		$('#discount_money_dom').hide();
	}
	
	$('#scanQrcode').click(function(){
		if(canScanAgain_){
			canScanAgain_ =false;
			 if(is_pufa){
				 window.pigcmspackapp.pufa_pos_pay(2,order_info.pay_money.toFixed(2),order_info.order_id); 
			  }else{
				common.scan('scanResult');
			  }
		}else{
			layer.open({
				content:'不要重复扫码'
				,  shadeClose: false
				,btn: ['确定']
				,yes: function(index){
					layer.close(index);
				}
			});
		}
	});
	
	$(".pay_type>div.fl").each(function(){
		$(this).height($(this).width());
	});
	
	$('.balance_click').click(function(){
		var menthod = $(this).attr('data-pay-method');
		if(menthod == 'qiye'){
			$(".qiye_pay_alert,.mask").show();
			return false;
		}
		if(menthod =='internalCard'){
			$("#internalCardPopup,.mask").show();
			$('#internalCardPopup .sm_input').show()
			$('#internalCardPayInfo').hide()
			return
		}

		if (order_info.open_balance_pay_unverify==1 && order_info.phone) {
			//开启线下零售、到店付余额支付不使用短信验证码
			go_pay('balance','', -1,order_info.phone);
		}else{
			$(".balance,.mask").show();
			$("#balance_phone").val(order_info.phone);
		}
	})
	
	$(".scan_code").click(function(){
		$(".fix_ewm").show();
		if(is_pufa){
			window.pigcmspackapp.pufa_pos_pay(2,order_info.pay_money.toFixed(2),order_info.order_id); 
		}
	});
	
	$(".del").click(function(){
		$(".fix_ewm").hide();
		$(".balance").hide();
		$("#internalCardPopup").hide();
		$("#internalCardPassPopup").hide();
		$(".qiye_pay_alert").hide();

		$(".mask").hide();
	});

	$(".offlinepay_click").click(function(){
		common.http('Storestaff&a=store_arrival_pay',{order_id:order_info.orderid,offline_pay:$(this).data('id')},function(data){
			layer.open({
				content:'订单已经支付成功！'
				,btn: ['确定']
				,yes: function(index){
					layer.closeAll();
					if(common.checkApp()){
						if(common.checkAndroidApp()){
							window.pigcmspackapp.closewebview(2);
						}else{
							common.iosFunction('closewebview/2' ,{name:'closewebview', type: '2'});
						}
					}else{
						window.history.go(-2);
					}
				}
			});
			clearInterval(checkPayTimer);
		});
	});
	
	$("#printOrder").click(function(){
		common.http('Storestaff&a=store_arrival_print',{order_id:order_info.order_id},function(data){
			motify.log('请求打印成功');
		});
	});
	
	$('.pufa_pos_pay').click(function(){
		window.pigcmspackapp.pufa_pos_pay(1,order_info.pay_money,order_info.order_id); 
	});
	
	$("#verify_phone").click(function(){
		price =order_info.pay_money;
		common.http('Storestaff&a=balance_verify_phone',{phone:$('#balance_phone').val(),noTip:true}, function(result){
			$('#balance_input').hide();
			$('#verify_phone').hide();
			$('#balance_phone').hide();
			$('#smscode').show();
			$('#balance_smscode').show();
			$('#balance_btn').show();
			user_now_money=parseFloat(result.money);
			if(user_now_money<price){
				$('#balance_title').html('当前平台余额：' + L_('￥') +parseFloat(result.money)+',待支付：<font color="red">' + L_('￥') + price.toFixed(2)+'</font>')
				
				$('#balance_smscode,#balance_btn').css({'background':'#8b9092','color':'#fff'});
			
			}else{
				$('#balance_smscode,#balance_btn').css({'background':'#00aaee','color':'#fff'});
				$('#balance_title').html('当前平台余额：'+L_('￥')+parseFloat(result.money)+',待支付：'+L_('￥')+price.toFixed(2))	
				$('#balance_btn').html('确认支付 ('+L_('￥')+price.toFixed(2)+')');
				smscode();
			}
			$('.balance .del').hide();
			$('.balance .back_check').show();
			
		});
	})

	$('#balance_smscode').click(function(){
		price = order_info.pay_money;
		if(user_now_money>=price){
			smscode();
		}
	})
	$('.balance .back_check').click(function(){
		clearInterval(smsTimer);
		$('#balance_smscode').html('发送验证码')
		$('#balance_title').html('只有在平台账户绑定手机号的用户才能使用平台余额支付')	
		$('#balance_input').show();
		$('#verify_phone').show();
		$('#balance_phone').show();
		$('#smscode').hide();
		$('#balance_smscode').hide();
		$('#balance_btn').hide();
		$('#balance_phone').val('');
		$('#smscode').val('');
		$('.balance .del').show();
		$('.balance .back_check').hide();
	})
	
	$('#balance_btn').click(function(){
		if(order_info.pay_money<=user_now_money){			
			if($('#smscode').val() == '' || !/^[0-9]*$/.test($('#smscode').val())){
				motify.log("短信验证码是数字",3000,{},20);
				return false;
			}
			go_pay('balance',$('#smscode').val(), -1,$('#balance_phone').val());
		}
	});

	$('#qiye_btn').click(function(){
		var pay_code = $('#qiye_pay_code').val();
		if (pay_code == '' || pay_code.length != 18) {
			motify.log("支付失败，用户付款码为:以90为前缀的18位数字）", 3000, {}, 20);
			return false;
		}
		var code_prefix = pay_code.substr(0, 2);
		if (code_prefix != '90') {
			motify.log("支付失败，用户付款码为:以90为前缀的18位数字", 3000, {}, 20);
			return false;
		}
		go_pay('qiye',pay_code, -1,'');
	});

	
	
	checkPayTimer = setInterval(function(){
		common.http('Storestaff&a=check_store_arrival_order',{order_id:order_info.orderid,noTip:true},function(data){
			common.removeCache('cashier_order_info',true);
			layer.open({
				content:'订单已经支付成功！'
				,btn: ['确定']
				,yes: function(index){
					layer.closeAll();
					if(common.checkApp()){
						if(common.checkAndroidApp()){
							window.pigcmspackapp.closewebview(2);
						}else{
							common.iosFunction('closewebview/2' ,{name:'closewebview', type: '2'});
						}
					}else{
						window.history.go(-2);
					}
				}
			});
			clearInterval(checkPayTimer);
		},function(data){
			// console.log(data);
		});	
	},2000);
});

function pufa_show(show){
	$('.pufa_pos_pay').show();
	$('.pufa_pos_pay').height($('.pufa_pos_pay').width());
	is_pufa = true;
}

function smscode(val){
	common.http('Storestaff&a=sendCode',{phone:$('#balance_phone').val(),noTip:true}, function(result){
		motify.log("短信发送成功",3000,{},20);
		$('#balance_smscode').data('second',59).html('59 秒').prop('disabled',true);
		smsTimer = setInterval(function(){
			var second = $('#balance_smscode').data('second');
			if(second == 1){
				$('#balance_smscode').html('发送验证码').prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('#balance_smscode').data('second',second).html(second + ' 秒');
			}
		},1000);
	})
}

//  term_seq  凭证号  
//tran_dt_tm 交易时间
// sys_order_id  系统订单号
//order_id 订单号
// tran_amt 交易金额
// resp_cd 交易状态码  00 成功
//resp_msg  交易状态信息   


function payAfterPufa(term_seq,tran_dt_tm,sys_order_id,order_id,tran_amt,resp_cd,resp_msg,ticket,device_id){

	common.http('Storestaff&a=store_arrival_pufa_pay',{order_code:term_seq,
	pay_time:tran_dt_tm,third_id:sys_order_id,order_id:order_id,pay_money:tran_amt,order_status:resp_cd,order_msg:resp_msg},function(data){
		// layer.open({
			// content:'订单已经支付成功！'
			// ,btn: ['确定']
			// ,yes: function(index){
				// window.history.go(-1);
			// }
		// });

	});
}

function go_pay(paymethod, auth_code, offline_pay,phone,system_coupon_hadpull_id = 0){
	console.log(order_info)
	var postData = {};
	postData.order_id = order_info.orderid;
	postData.auth_code = auth_code;
	postData.auth_type = paymethod;
	postData.price = order_info.pay_money;
	postData.offline_pay = offline_pay;
	if(typeof phone !=undefined){
		postData.phone = phone;
	}
	postData.system_coupon_hadpull_id = system_coupon_hadpull_id;
	common.http('Storestaff&a=store_arrival_pay',postData, function(data){
		// layer.open({
			// content:'订单已经支付成功！'
			// ,btn: ['确定']
			// ,yes: function(index){
				// window.history.go(-1);
			// }
		// });
	});
}

function scanResult(value){
	var strArr = value.split(',');
	if(strArr.length == 2){
		value = strArr[1];
	}
	common.http('Storestaff&a=store_arrival_pay',{order_id:order_info.orderid,auth_code:value,re_call:re_call ? 1 : 0},function(data){
		layer.open({
			content:'订单已经支付成功！'
			,btn: ['确定']
			,yes: function(index){
				layer.closeAll();
				if(common.checkApp()){
					if(common.checkAndroidApp()){
						window.pigcmspackapp.closewebview(2);
					}else{
						common.iosFunction('closewebview/2' ,{name:'closewebview', type: '2'});
					}
				}else{
					window.history.go(-2);
				}
			}
		});
		clearInterval(checkPayTimer);
	},function(data){
		if(data.errorCode == '20130035'){
			re_call = true;
			data.errorMsg = data.errorMsg + '，请在用户输入密码后再点击';
			layer.open({
				content:'需要用户输入支付密码，用户输入完成后点击按钮重新请求。'
				,btn: ['确定']
				,yes: function(index){
					scanResult(value);
				}
			});
		}else{
			re_call = false;
			layer.open({
				content:data.errorMsg
				,btn: ['确定']
				,yes: function(index){
					layer.closeAll();
				}
			});
		}
		canScanAgain_ = true;
	});
}

function scanQiyeResult(str) {
	$('#qiye_pay_code').val(str)
}

$('.qiye_scan').on('click', function () {
	common.scan('scanQiyeResult');
})

//一卡通查询
$("#internalCardQuery").click(function(){
	let card_no = $('#card_no').val()
	if(!card_no){
		motify.log("卡号不能为空", 3000, {}, 20);
		return
	}
	ajax_card_new();
})

//确定密码
$("#confirmPassword").click(function(){
	let password = $('#internalCardPassPopup #password').val()
	if(!password){
		motify.log("密码不能为空", 3000, {}, 20);
		return
	}
	ajax_card_new(password);
	
})

//确认支付
$("#confirmPayment").click(function(){
	var system_coupon_hadpull_id = $('#choose_system_coupon').val();
	if(system_coupon_hadpull_id <= 0){
		if(order_info.pay_money > internal_card_info.ecard_total_money){
			motify.log("余额不足", 3000, {}, 20);
			return
		}
	}
	go_pay('ecard','', -1, internal_card_info.keyword,system_coupon_hadpull_id);
})

function ajax_card_new(password=''){
	let card_no = $('#card_no').val()
	let store_staff = common.getCache('store_staff',true);
	let params = {key: card_no,type: 'ecard',user_name: store_staff.username,user_id: store_staff.id,password}

	common.http('Storestaff&a=ajax_card_new',params,function(data){
		if(data.internal_card_ids && data.internal_card_ids.length == 0){
			motify.log("未查询到信息", 3000, {}, 20);
			return
		}
		if(data.need_password == true){
			$('#internalCardPassPopup').show()
			$('#internalCardPassPopup #password').focus();
			$('#internalCardPopup').hide()
		}else{
			$('#internalCardPopup .sm_input').hide()
			$('#internalCardPayInfo').show()
		}
		if(data.internal_card_ids){
			internal_card_info = data
			internal_card_info.keyword = card_no;
			$('#confirmPassword #password').val('')
			$('#internalCardPassPopup').hide()
			$('.total_price').html(order_info.pay_money)
			$('#internalCardPopup').show()
			$('#ecard_total_money').html(data.ecard_total_money)
			if(order_info.pay_money > internal_card_info.ecard_total_money){
				$('#internalCardPopup #confirmPayment').css({'background-color': '#999'})
			}
		}

		if(data.uid > 0){
			ajax_system_coupons(data.uid);
		}
	},function(err){
		motify.log(err.errorMsg, 3000, {}, 20);
	});
}

//获取平台优惠券
function ajax_system_coupons(uid){
	let params = {order_id: order_info.orderid,uid:uid}
	common.http('Storestaff&a=get_cash_coupons',params,function(data){
		laytpl($('#systemcoupon_tpl').html()).render(data, function(html){
			$('#useSystemCoupon').html(html);
		});
	},function(err){
		motify.log(err.errorMsg, 3000, {}, 20);
	});
}