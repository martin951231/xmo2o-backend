var merchant_card_use = 0,merchant_coupon_id = 0,merchant_coupon_discount = 0,order_info=null,payPrice=0,go_pay_price=0,card_data=null,user_pay_timer = null;
var smsTimer;
var system_coupon_id = 0;
var system_coupon_discount = 0, score_deducte =0;
var mer_discount=null,is_change=0,discount_money=0,no_discount_money=0,is_open_pay_bind_user=0;
var re_call = 0;
var pay_yzfpay_open = 0;
var cardActionType = '';//membership 会员卡 internalCard 一卡通
var internal_card_info = common.getCache('internal_card_info',true) || null;//一卡通
var ecard_usable_money=0;//一卡通卡用余额
var config = common.getCache('config',true) || null;
var ecard_phone = '';
$(function(){
	card_data = common.getCache('card_data',true);
	if (card_data) {
		if (card_data.card_id) {
			$('.hasCardDiscount').show();
		}else{
			$('.hasCardDiscount').hide(); 
		}
	}else{
		$('.hasCardDiscount').hide(); 
	}
	if(internal_card_info && config.internal_membership_card == 1){
		//定制才开启
		$('#internalCard_info').show()
		if(internal_card_info.internal_card_ids.length){
			ecard_phone = internal_card_info.internal_card_ids[0].phone
		}
	}else{
		$('#internalCard_info').hide()
	}
	common.http('Storestaff&a=store_arrival_add_info',{},function(data){
		mer_discount = data;
		is_open_pay_bind_user = data.is_open_pay_bind_user
		if (!data.is_open_pay_bind_user){
			$('.hasBindPhone').hide();
		}

		// 翼支付
		pay_yzfpay_open = data.pay_yzfpay_open ? Number(data.pay_yzfpay_open) : 0
		if(pay_yzfpay_open <= 0){
			$('.yzf').hide();
		}
		if(data.has_discount == true){
			if(data.discount_type == 1){	//1.打折
				var discountTxt = data.discount_percent+'折优惠';
			}else if(data.discount_type == 2){    //2.满减
				var discountTxt = '满'+data.condition_price+'元减'+data.minus_price+'元';
			}
			$('#discountTxt').html(discountTxt);
			// 商家优惠		
			$("#switch1 div").click(function(){
				$(this).addClass("on").siblings().removeClass("on");
				if($(this).hasClass('off')){
					// $('#price').html($('#total_price').val());
					$('.offDiscount').hide();
					// $('.no_discount_money').hide();
					
                    // $('#total_price').trigger('keyup');
				}else{
					// $('#total_price').trigger('keyup');
					$('.offDiscount').show();
					$('.no_discount_money').show();
				}
				count_price_html()
			});
            $("#switch1 .no").addClass("on").siblings().removeClass("on");
            $('#total_price').trigger('keyup');
			$('.offDiscount').show();
			$('.no_discount_money').show();
			$('.no_discount_money').keyup(function(){
				count_price_html();
			})
		}else{
			$('.hasDiscount').hide();
		}

		$(".settlement").height($(window).height()-94);
		
		var staffArr = common.getCache('store_staff',true);
		// if(staffArr.mer_id!='118'){ 
		// 	$('.balance_click').hide()
		// }
		console.log(staffArr);
		if(staffArr.is_change == '0'){
			$('#changeOrderMoney').hide();
		}
		
		common.onlyScroll($(".settlement"));
		
		order_info = common.getCache('order_info',true);
		if(!order_info){
			history.go(-1);
		}
		common.setData({order_info:order_info});
		console.log(order_info);
		
		$('#scan_code_img').attr('src',order_info.pay_qrcode_url);
		
		getOfflinePayMethod();
		
		if(common.checkAndroidApp() && typeof window.pigcmspackapp.isShowPfBtn == 'object'){
			window.pigcmspackapp.isShowPfBtn();
		}
		
		// 计算应付金额
		count_price_html();

		setTimeout(function(){
			if (data.merchant_buy_points_switch==1) {
				if ( data.platform_score<100&&data.platform_score>=50) {
					layer.open({
			            title: '提示',
			            content: "商家的平台"+data.score_name+"已不足100，平台用户支付后可能无法正常获得相应"+data.score_name+"，请及时充值!",
			            btn: ['关闭']
			        });
				}else if(data.platform_score<50&&data.platform_score>0){
					layer.open({
			            title: '提示',
			            content: "商家的平台"+data.score_name+"已不足50，平台用户支付后可能无法正常获得相应"+data.score_name+"，请及时充值!",
			            btn: ['关闭']
			        });
				}else if(data.platform_score<=0){
					layer.open({
			            title: '提示',
			            content: "商家的平台"+data.score_name+"已不足0，平台用户支付后可能无法正常获得相应"+data.score_name+"，请及时充值!",
			            btn: ['关闭']
			        });
				}
			}
		},1000)
		

	})


	$("#changeOrderMoney").click(function(){
		$('#change_price').val('');
		$('#change_price_reason').val('');
		$('#order_money_now').html(payPrice);
		$("#change_order_price,.mask").show();
	});
	$('#change_order_price .recovery').click(function(){
		is_change = 0;
		count_price_html();

		$(".amend,.mask").hide();
	});
	$('#change_order_price .ensure').click(function(){
		if($('#change_price').val() == ''){
			motify.log('金额不能为空');
			return false;
		}
		var tmp_money = parseFloat($('#change_price').val());
		if(isNaN(tmp_money)){
			motify.log('请输入正确的价格');
			return false;
		}
		is_change = 1;
		// 计算可用积分
		var card_money = 0;
		if(card_data){
			card_money = parseFloat(card_data.card_money);
			if (isNaN(card_money)) {
				card_money = 0;
			}

		    var uid =  card_data.uid;
		    if (card_data.uid&&is_open_pay_bind_user) {
			    //积分抵扣
				common.http('Storestaff&a=get_score_discount',{total_money:tmp_money,uid:uid,type:'shop',order_id:order_info.order_id,noTip:true},function(score_data){
					
					$('#score_deducte').data('price', parseFloat(score_data.score_deducte).toFixed(2));
					$('#score_deducte').html('' + L_('￥') + ''+parseFloat(score_data.score_deducte).toFixed(2));
					$('#score_can_use_count').data('num', score_data.score_can_use_count);
					$('#score_can_use_count').html(score_data.score_can_use_count);
					if(score_data.score_can_use_count){
						$('.score_discount_li').show();
					}else{
						$('.score_discount_li').hide();
					}
					$('#use_score').prop('checked',false);
					score_deducte = 0;
				})
			}else{
				$('.score_discount_li').hide();
				$('#use_score').prop('checked',false);
			}
		}
		common.http('Storestaff&a=shop_change_price',{'order_id':order_info.order_id,'change_price':tmp_money,'change_price_reason':$('#change_price_reason').val()}, function(data){
			payPrice = tmp_money;
			$('#pay_price').html('' + L_('￥') + '' + tmp_money);

			if(card_money > payPrice){
				merchant_card_use = payPrice;
				$('#user_card_money').html('' + L_('￥') + '' + payPrice);
			} else {
				merchant_card_use = card_money;
				$('#user_card_money').html('' + L_('￥') + '' + card_money);
			}
			go_pay_price = parseFloat(payPrice-merchant_card_use).toFixed(2);
			
			$('#otherPayMoney').html('' + L_('￥') + '' + common.floatVal(go_pay_price));
			if($('#otherPayMoney').html() == L_('￥') + '0'){
				$('.xxzf,.wxzf,.zfb,balance_pay,.wxsm,yzf').hide();
				$(".set_end li").removeClass("on");
			}else{
				$('.xxzf,.wxzf,.zfb,balance_pay,.wxsm').show();
				if(pay_yzfpay_open){
					$('.yzf').show();
				}
				$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
			}
			$(".amend,.mask").hide();
			updateCardMoney('internalCard')
		});
	});
	
	
	//修改价格
	$("#change_card_money").click(function(){
		cardActionType = 'membership';
		$('#merchant_card_money').val('');
		$('#money_now').html(merchant_card_use);
		$("#merchant_card_change,.mask").show();
	});
	// 一卡通修改使用余额
	$("#change_internalCard_money").click(function(){
		cardActionType = 'internalCard';
		$('#merchant_card_money').val('');
		$('#money_now').html(ecard_usable_money);
		$("#merchant_card_change,.mask").show();
	});

	$(".mask,.amend .del").click(function(){
		$(".amend,.mask").hide();
	})
	// 恢复会员卡使用金额
	$('#merchant_card_change .recovery').click(function(){
		updateCardMoney()
	});
	// 修改会员卡使用金额
	$('#merchant_card_change .ensure').click(function(){
		var tmp_money = parseFloat($('#merchant_card_money').val());
		let tmp_go_pay_price = 0 //每次减去的使用金额的值
		if(isNaN(tmp_money)){
			motify.log('请输入正确的价格');
			return false;
		}
		if (is_change==1) {
			go_pay_price = parseFloat(parseFloat(payPrice - score_deducte).toFixed(2));
		}else{
			go_pay_price = parseFloat(parseFloat(payPrice - merchant_coupon_discount - system_coupon_discount- score_deducte).toFixed(2));
		}
		switch (cardActionType) {
			case 'membership':
				tmp_go_pay_price = parseFloat(go_pay_price - ecard_usable_money).toFixed(2)//每次减去的使用金额的值
				if(tmp_money > card_data.card_money){
					motify.log('使用会员卡余额不能超过当前余额');
					return false;
				}
				if(tmp_money > tmp_go_pay_price){
					motify.log('使用会员卡余额不能超过实付金额');
					return false;
				}
				merchant_card_use = tmp_money;
				$('#user_card_money').html('' + L_('￥') + '' + tmp_money);
				break;
			case 'internalCard':
				//一卡通
				tmp_go_pay_price = parseFloat(go_pay_price - merchant_card_use).toFixed(2)//每次减去的使用金额的值
				if(tmp_money > internal_card_info.ecard_total_money){
					motify.log('使用一卡通余额不能超过当前余额');
					return false;
				}
				if(tmp_money > tmp_go_pay_price){
					motify.log('使用一卡通余额不能超过实付金额');
					return false;
				}
				ecard_usable_money = tmp_money;
				$('#user_internalCard_money').html(L_('￥') + ecard_usable_money)
				break;
			default:
				break;
		}
		go_pay_price = parseFloat(go_pay_price - merchant_card_use - ecard_usable_money).toFixed(2)
		$('#otherPayMoney').html('' + L_('￥') + '' + common.floatVal(go_pay_price));
		if($('#otherPayMoney').html() == '' + L_('￥') + '0'){
			$('#otherPayMethod').hide();
			$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm,yzf').hide();
			$(".set_end li").removeClass("on");
		}else{
			$('#otherPayMethod').show();
			$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').show();
			if(pay_yzfpay_open){
				$('.yzf').show();
			}
			$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
		}
		
		$(".amend,.mask").hide();
	});
	
	// 支付
	$(".set_end li").click(function(){
		if($(this).hasClass("on")){
			$(this).removeClass("on");
		}else{
			$(this).addClass("on").siblings().removeClass("on");
		}
	})

	var choice_type = 1; //1-商家优惠券2-平台优惠券
	//优惠劵
	$(".choice1").click(function(){
		choice_type = 1;
		if(card_data){
			var coupon_list = card_data.card_new;
			if(card_data.discount === undefined){
				var temp_price = parseFloat((order_info.price).toFixed(2));
			}else{
				var temp_price = parseFloat((order_info.price * card_data.discount * 0.1).toFixed(2));
			}
			if(coupon_list.length > 0){
				var can_use_coupon_list = [];
				for(var i in coupon_list){
					if (parseFloat(coupon_list[i].full_money) <= temp_price) {
						can_use_coupon_list.push(coupon_list[i]);
					}
				}
				if(can_use_coupon_list.length > 0){
					laytpl($('#listCouponTpl').html()).render(can_use_coupon_list, function(html){
						$(".coupon ul").html(html);
					});
					$('.coupon .h2 span').html('选择优惠券');
					$(".coupon,.mask").show();
				}else{
					motify.log('无可用优惠券');
				}
			}else if(card_data.card_can_had > 0){
				$('.coupon .h2 span').html('用户扫码领优惠券');
				$('#coupon_can_had').html(card_data.card_can_had);
				$(".coupon ul").hide();
				$(".coupon .no_coupon").show();
				var qrCon = requestUrl.replace('appapi.php','wap.php')+'My_card&a=merchant_card&mer_id='+staffArr.mer_id;
				var qrSrc = requestUrl.replace('appapi.php','index.php')+'Recognition&a=get_own_qrcode&qrCon='+encodeURIComponent(qrCon);
				if($('#merchant_card_qrcode').prop('src') != qrSrc){
					$('#merchant_card_qrcode').prop('src',qrSrc);
				}
				$(".coupon,.mask").show();
			}else{
				motify.log('无可用优惠券');
			}
		}else{
			motify.log('未输入会员卡信息');
		}
	});

		//平台优惠劵
	$(".choice2").click(function(){
		choice_type = 2;
		if(card_data){
			var coupon_list = card_data.system_card;
			var temp_price = parseFloat((order_info.price * card_data.discount * 0.1).toFixed(2));
			if( typeof(coupon_list) !='undefined'){
				if (coupon_list.length > 0) {
					var can_use_coupon_list = [];
					for(var i in coupon_list){
						if (coupon_list[i].is_discount==1) {
							coupon_list[i].reduce_money = parseFloat(temp_price*((100-parseFloat(coupon_list[i].discount_value)*10)/100)).toFixed(2);
						}
						if (parseFloat(coupon_list[i].full_money) <= temp_price) {
							can_use_coupon_list.push(coupon_list[i]);
						}
					}
					if(can_use_coupon_list.length > 0){
						laytpl($('#listCouponTpl').html()).render(can_use_coupon_list, function(html){
							$(".coupon ul").html(html);
						});
						$('.coupon .h2 span').html('选择优惠券');
						$(".coupon,.mask").show();
					}else{
						motify.log('无可用优惠券');
					}
				}else{
					motify.log('无可用优惠券');
				}
			}else{
				motify.log('无可用优惠券');
			}
		}else{
			motify.log('未输入用户信息');
		}

	});
	
	$(".mask,.coupon .del").click(function(){
		$(".coupon,.mask").hide();
	});

	$(document).on('click','.coupon li',function(){
		var price=$(this).data("reduce");
		var total=$(this).data("full");
		$(".coupon,.mask").hide();
		$(".choice"+choice_type).addClass('ce9').html('满'+total+'减'+price+'元');
		if (choice_type == 1) {
			merchant_coupon_id = $(this).data('id');
			merchant_coupon_discount = price;
		}else{
			system_coupon_id = $(this).data('id');
			system_coupon_discount = price;
		}
		is_change = 0;
		count_price_html();
		return false;
	
	});

	//支付
	$(".affirm").click(function(){
		if($(".set_end li.xxzf").hasClass("on")){
			$('#offline_money').html(common.floatVal(go_pay_price));
			$(".js_line,.mask").show(); 
			$(".js_line").css("margin-top",-$(".js_line").height()/2);
		}else if($(".set_end li.pufapos").hasClass("on")){ 
			 var pufa_pay_money = common.floatVal(payPrice-merchant_card_use);
			 window.pigcmspackapp.pufa_pos_pay(1,parseFloat(pufa_pay_money).toFixed(2),order_info.order_id); 
		}else if($(".set_end li.pufapos_scan").hasClass("on")){ 
			var pufa_pay_money = common.floatVal(payPrice-merchant_card_use);
			 window.pigcmspackapp.pufa_pos_pay(2,parseFloat(pufa_pay_money).toFixed(2),order_info.order_id); 
		}else if($(".set_end li.yzf").hasClass("on")){
			$(".yzfpay,.mask").show();
		}else if($(".set_end li.wxzf").hasClass("on")){
			$(".wechat,.mask").show();
		}else if($(".set_end li.balance_pay").hasClass("on")){
			if (card_data) {
				if (mer_discount.open_balance_pay_unverify==1 && card_data.phone) {
					console.log(card_data.phone);
					//开启线下零售、到店付余额支付不使用短信验证码
					go_pay('balance','', -1,card_data.phone);
				}else if(card_data.phone){
					// 自动填充手机号
					$('#balance_phone').val(card_data.phone);
					$('#balance_phone').prop('disabled',true);
					$(".balance,.mask").show();
				}else{
					$('#balance_phone').val('');
					$('#balance_phone').prop('disabled',false);
					$(".balance,.mask").show();
				}
			}else{
				$('#balance_phone').val('');
				$('#balance_phone').prop('disabled',false);
				$(".balance,.mask").show();
			}
		}else if($(".set_end li.zfb").hasClass("on")){
			$(".alipay,.mask").show();
		}else if($(".set_end li.wxsm").hasClass("on")){
			$(".scan_code,.mask").show();
			user_pay_timer = setInterval(function(){
				common.http('Storestaff&a=shop_arrival_check',{order_id:order_info.order_id,noTip:true}, function(data){
					clearInterval(user_pay_timer);
					common.removeCache('card_data',true);
					common.removeCache('buy_list',true);
					$(".back_index,.pay_ok_mask").show();
				},function(){
					
				});
			},2000);
		}else{
			if($('#otherPayMoney').html() == '' + L_('￥') + '0'){
				go_pay('', '', '',card_data && card_data.phone?card_data.phone:'');
			}else{
				motify.log('请选择支付方式');
				return false;
			}
		}
	});
	
	$("#verify_phone").click(function(){
		var price =go_pay_price;
		common.http('Storestaff&a=balance_verify_phone',{phone:$('#balance_phone').val(),noTip:true}, function(result){
			$('#balance_input').hide();
			$('#verify_phone').hide();
			$('#balance_phone').hide();
			$('#smscode').show();
			$('#balance_smscode').show();
			$('#balance_btn').show();
			user_now_money=parseFloat(result.money);
			if(user_now_money<price){
				$('#balance_title').html('当前平台余额：' + L_('￥') + ''+parseFloat(result.money)+',待支付：<font color="red">' + L_('￥') + ''+price+'</font>')
				// $('.confirm_pay .firm').addClass('money_lease')
				// $('.confirm_pay .money_lease').removeClass('firm')
				// $('.confirm_pay .sms').css('background','#8b9092')
				// $('.confirm_pay .sms').css('color','#fff')
				
				$('#balance_smscode,#balance_btn').css({'background':'#8b9092','color':'#fff'});
			
			}else{
				$('#balance_smscode,#balance_btn').css({'background':'#00aaee','color':'#fff'});
				$('#balance_title').html('当前平台余额：' + L_('￥') + ''+parseFloat(result.money)+',待支付：' + L_('￥') + ''+price)	
				$('#balance_btn').html('确认支付 (' + L_('￥') + ''+price+')');
				smscode();
			}
			$('.balance .del').hide();
			$('.balance .back_check').show();
			
		});
	})
	
	$('#balance_smscode').click(function(){
		var price = go_pay_price;
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

	$(document).on('click','#offline_method dd',function(){
		go_pay('', '', $(this).data('id'));
	});
	
	$('#offline_cash').keyup(function(){
		var tmpCash = parseFloat($('#offline_cash').val());
		if(isNaN(tmpCash)){
			$('#offline_zhaoling').html('0.00');
		}else{
			if(common.floatVal(tmpCash-go_pay_price) > 0){
				$('#offline_zhaoling').html(common.floatVal(tmpCash-go_pay_price));
			}
		}
	});
	
	$(".mask,.js_line .del,.popup .del,.back_index .del,.seek .del,.empty .close").click(function(){
		$(".js_line,.popup,.back_index,.seek,.mask").hide();
	});

	$(".cancel").click(function(){
		$(".seek,.mask").show(); 
	});
	
	$('.empty .ensure').click(function(){
		common.removeCache('card_data',true);
		common.removeCache('buy_list',true);
		common.removeCache('total_goods',true);
		common.removeCache('internal_card_info',true);
		history.go(-1);
	});
	
	$('#weixin_input_dom em').click(function(){
		common.scan('scanWeixinPayResult');
	});
	$('#weixin_btn').click(function(){
		if($('#weixin_input').val() == '' || !/^[0-9]*$/.test($('#weixin_input').val())){
			motify.log("微信付款码是数字",3000,{},20);
			return false;
		}
		go_pay('weixin',$('#weixin_input').val(), -1);
	});
	$('#yzfpay_input_dom em').click(function(){
		common.scan('scanYzfPayResult');
	});
	$('#yzfpay_btn').click(function(){
		if($('#yzfpay_input').val() == '' || !/^[0-9]*$/.test($('#yzfpay_input').val())){
			motify.log("翼支付付款码是数字",3000,{},20);
			return false;
		}
		go_pay('yzfpay',$('#yzfpay_input').val(), -1);
	});
	
	$('#alipay_input_dom em').click(function(){
		common.scan('scanAliPayResult');
	});
	$('#alipay_btn').click(function(){
		if($('#alipay_input').val() == '' || !/^[0-9]*$/.test($('#alipay_input').val())){
			motify.log("支付宝付款码是数字",3000,{},20);
			return false;
		}
		go_pay('alipay',$('#alipay_input').val(), -1);
	});
	
	$('#balance_btn').click(function(){
		if(go_pay_price<=user_now_money){			
			if($('#smscode').val() == '' || !/^[0-9]*$/.test($('#smscode').val())){
				motify.log("短信验证码是数字",3000,{},20);
				return false;
			}
			go_pay('balance',$('#smscode').val(), -1,$('#balance_phone').val());
		}
	});
	
	$('#card_data .refresh').click(function(){
		common.http('Storestaff&a=ajax_card_new', {'key':card_data && card_data.phone?card_data.phone:''}, function(data){
			if (data.discount) {
				data.discount = common.floatVal(data.discount);
			}
			common.setCache('card_data',data,true);
			location.reload();
		});
	});
	//点击使用积分
	$(document).on('click', '#use_score', function(){
		console.log($(this).attr("checked"))
		if ($('#use_score').attr("checked")) {
			$('#use_score').attr("checked",true);
		}else{
			$('#use_score').attr("checked",false);
		}
		go_pay_price = payPrice;
	    // 优惠券
	    if (merchant_coupon_discount) {
	    	go_pay_price = parseFloat((go_pay_price - merchant_coupon_discount).toFixed(2));
	    }
	    if (system_coupon_discount) {
	    	go_pay_price = parseFloat((go_pay_price - system_coupon_discount).toFixed(2));
	    }

		if ($('#use_score').prop('checked')) {
			score_deducte = parseFloat($('#score_deducte').data('price')).toFixed(2);
	    	go_pay_price = parseFloat((go_pay_price - $('#score_deducte').data('price')).toFixed(2));
		}else{
			score_deducte = 0;
		}

		var card_money = 0;
		if(card_data && card_data.card_id){
			card_money = parseFloat(card_data.card_money);
		}

		if(card_money > go_pay_price){
			merchant_card_use = go_pay_price;
			$('#user_card_money').html('' + L_('￥') + '' + go_pay_price);
		} else {
			merchant_card_use = card_money;
			$('#user_card_money').html('' + L_('￥') + '' + card_money);
		}
		go_pay_price = parseFloat(go_pay_price-merchant_card_use).toFixed(2);
		$('#otherPayMoney').html('' + L_('￥') + '' + common.floatVal(go_pay_price));
		if($('#otherPayMoney').html() == '' + L_('￥') + '0'){
			$('#otherPayMethod').hide();
			$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').hide();
			$(".set_end li").removeClass("on");
		}else{
			$('#otherPayMethod').show();
			$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').show();
			$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
		}

		//重置一卡通
		$('#user_internalCard_money').html(L_('￥') +'0');
		ecard_usable_money = 0;
	});
});

function count_price_html(){
	if (is_change==1) {
		// 修改价格后不使用优惠
		return false;
	}
	
	var discount = $('#card_discount').data('discount'), reduce_money1 = $('#coupon_price1').data('price'),reduce_money2 = $('#coupon_price2').data('price');
	no_discount_money = parseFloat($('#no_discount_money').val()), price = 0,minus_price = 0;
		if(isNaN(no_discount_money)){
			no_discount_money = 0;
		}
	var total_money = order_info.price;

	// 商家优惠
	if(total_money > no_discount_money){
		if($('#switch1 .no').hasClass('on')&&mer_discount.has_discount==true) {
            if (mer_discount.discount_type == 1) {
                price = eval(total_money - no_discount_money) * eval(mer_discount.discount_percent) / 10 + no_discount_money;
                minus_price = eval(total_money - no_discount_money) * (100 - mer_discount.discount_percent * 10) / 100;
            } else if (mer_discount.discount_type == 2) {
                minus_price = Math.floor(eval(total_money - no_discount_money) / eval(mer_discount.condition_price)) * mer_discount.minus_price;
                price = total_money - minus_price;
            } else {
                minus_price = 0;
                price = total_money;
            }
       	}else{
			minus_price = 0;
			price = total_money;
		}
		price = parseFloat(price).toFixed(2)
		if(minus_price > 0){
			$('#show_minus').html('-' + L_('￥') + '<span id="discount_money">' + common.floatVal(minus_price) + '</span>');
		}else{
			$('#show_minus').html('-' + L_('￥') + '0');
		}
	}else{
		minus_price = 0;
		price = total_money;
		$('#show_minus').html('-' + L_('￥') + '0');
	}
	discount_money = minus_price;

	var discount = 10,card_money = 0;
	if(card_data){
		if(card_data.card_id){
			discount = parseFloat(card_data.discount);
			card_money = parseFloat(card_data.card_money);
			common.setData({card_data:card_data});
		}else{
			$('#card_data,#merchant_discount_li').hide();
		}
	}else{
		$('#card_data,#merchant_discount_li').hide();
	}
	
	if (discount > 0 && discount != 10) {
		$('#merchant_discount').html(discount + '折');	
	}else{
		$('#merchant_discount').html('无折扣');
	}
	
	payPrice = parseFloat(parseFloat(discount * 0.1 * (price-no_discount_money)+no_discount_money).toFixed(2));
	$('#pay_price').html('' + L_('￥') + '' + payPrice);
	go_pay_price = payPrice;
	// 优惠券
    if (merchant_coupon_discount) {
    	go_pay_price = parseFloat((go_pay_price - merchant_coupon_discount).toFixed(2));
    }
    if (system_coupon_discount) {
    	go_pay_price = parseFloat((go_pay_price - system_coupon_discount).toFixed(2));
    }
    var card_money = 0;
	if(card_data){
	    var uid =  card_data.uid;
	    if (card_data.uid&&is_open_pay_bind_user) {
		    //积分抵扣
			common.http('Storestaff&a=get_score_discount',{total_money:go_pay_price,uid:uid,type:'shop',order_id:order_info.order_id,noTip:true},function(score_data){
				
				$('#score_deducte').data('price', parseFloat(score_data.score_deducte).toFixed(2));
				$('#score_deducte').html('' + L_('￥') + ''+parseFloat(score_data.score_deducte).toFixed(2));
				$('#score_can_use_count').data('num', score_data.score_can_use_count);
				$('#score_can_use_count').html(score_data.score_can_use_count);
				if(score_data.score_can_use_count){
					$('.score_discount_li').show();
				}else{
					$('.score_discount_li').hide();
				}
				$('#use_score').prop('checked',false);
				score_deducte = 0;
				
			})
		}else{
			$('.score_discount_li').hide();
			$('#use_score').prop('checked',false);
		}

		if ($('#use_score').prop('checked')) {
			score_deducte = parseFloat($('#score_deducte').data('price')).toFixed(2);
	    	go_pay_price = parseFloat((go_pay_price - $('#score_deducte').data('price')).toFixed(2));
		}else{
			score_deducte = 0;
		}
	}

	if(card_money > go_pay_price){
		merchant_card_use = go_pay_price;
		$('#user_card_money').html('' + L_('￥') + '' + go_pay_price);
	} else {
		merchant_card_use = card_money;
		$('#user_card_money').html('' + L_('￥') + '' + card_money);
	}
	go_pay_price = parseFloat(go_pay_price-merchant_card_use).toFixed(2);

	$('#otherPayMoney').html('' + L_('￥') + '' + common.floatVal(go_pay_price));
	if($('#otherPayMoney').html() == '' + L_('￥') + '0'){
		$('#otherPayMethod').hide();
		$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').hide();
		$(".set_end li").removeClass("on");
	}else{
		$('#otherPayMethod').show();
		$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').show();
		$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
	}
	
	//回显一卡通
	if(internal_card_info){
		getInternalCardInfo(internal_card_info)
	}
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

function pufa_show(show){
	$('.wxzf,.wxsm').hide();
	$('.pufapos,.pufapos_scan').show();
}

function payAfterPufa(term_seq,tran_dt_tm,sys_order_id,order_id,tran_amt,resp_cd,resp_msg,ticket,device_id){
	if(resp_cd=='00'){
		
		var postData = {};
		postData.order_id = order_info.order_id;
		postData.auth_type = 'pufapos';
		postData.price = payPrice;
		postData.card_id = card_data ? card_data.card_id : 0;
		postData.uid = card_data ? card_data.uid : 0;
		postData.discount = card_data ? (card_data.discount ? card_data.discount : 10) : 10;
		postData.coupon = merchant_coupon_id;
		postData.card_money = merchant_card_use;
		postData.offline_pay = 1;
		postData.pay_time = tran_dt_tm;
		postData.third_id = sys_order_id;

		postData.sys_coupon = system_coupon_id;
		postData.is_use_score = $('#use_score').prop('checked') ? 1 : 0;
		postData.discount_money = discount_money; // 商家优惠
		common.http('Storestaff&a=arrival_pay',postData, function(data){
			common.removeCache('card_data',true);
			common.removeCache('buy_list',true);
			common.removeCache('total_goods',true);
			common.removeCache('internal_card_info',true);
			$(".back_index,.pay_ok_mask").show();
		});

	}else{
		motify.log("支付失败",3000,{},20);
	}

}

function scanAliPayResult(str){
	var strArr = str.split(',');
	if(strArr.length == 2){
		str = strArr[1];
	}
	if(!/^[0-9]*$/.test(str)){
        motify.log("支付宝付款码是数字",3000,{},20);
		return false;
    }
	$('#alipay_input').val(str);
	$('#alipay_btn').trigger('click');
}
function scanWeixinPayResult(str){
	var strArr = str.split(',');
	if(strArr.length == 2){
		str = strArr[1];
	}
	if(!/^[0-9]*$/.test(str)){
        motify.log("微信付款码是数字",3000,{},20);
		return false;
    }
	$('#weixin_input').val(str);
	$('#weixin_btn').trigger('click');
}

function scanYzfPayResult(str){
	var strArr = str.split(',');
	if(strArr.length == 2){
		str = strArr[1];
	}
	if(!/^[0-9]*$/.test(str)){
        motify.log("翼支付付款码是数字",3000,{},20);
		return false;
    }
	$('#yzfpay_input').val(str);
	$('#yzfpay_btn').trigger('click');
}

function go_pay(paymethod, auth_code, offline_pay,phone){
	var postData = {};
	postData.order_id = order_info.order_id;
	postData.auth_code = auth_code;
	postData.auth_type = paymethod;
	postData.price = payPrice;
	postData.card_id = card_data ? card_data.card_id : 0;
	postData.uid = card_data ? card_data.uid : 0;
	postData.discount = card_data ? (card_data.discount ? card_data.discount : 10) : 10;
	postData.coupon = merchant_coupon_id;
	postData.card_money = merchant_card_use;
	postData.offline_pay = offline_pay;

	postData.sys_coupon = system_coupon_id;
	postData.is_use_score = $('#use_score').prop('checked') ? 1 : 0;
	postData.discount_money = discount_money; // 商家优惠
	postData.no_discount_money = no_discount_money; // 不可优惠金额

	if(ecard_usable_money > 0){
		postData.internal_card_pay = ecard_usable_money; //一卡通支付金额
	}

	if(typeof phone !=undefined){
		postData.phone = phone;
	}
	console.log(internal_card_info,'internal_card_info');
	if(config.internal_membership_card == 1 && internal_card_info){
		postData.phone = internal_card_info.keyword;
	}
	postData.re_call = re_call;
	common.http('Storestaff&a=arrival_pay',postData, function(data){
		common.removeCache('card_data',true);
		common.removeCache('buy_list',true);
		common.removeCache('total_goods',true);
		common.removeCache('internal_card_info',true);
		$(".back_index,.pay_ok_mask").show();
	},function(data){
		if(data.errorCode == '20130035'){
			re_call = 1;
			data.errorMsg = data.errorMsg + '，请在用户输入密码后再点击';
			layer.open({
				content:'需要用户输入支付密码，用户输入完成后点击按钮重新请求。'
				,btn: [L_('确定')]
				,yes: function(index){
					layer.closeAll();
					go_pay(paymethod, auth_code, offline_pay);
				}
			});
		}else{
			re_call = 0;
			layer.open({
				content:data.errorMsg
				,btn: [L_('确定')]
				,yes: function(index){
					layer.closeAll();
                    // 已支付成功
                    if (data.errorCode == '1009') {
                        common.removeCache('card_data',true);
                        common.removeCache('buy_list',true);
                        common.removeCache('total_goods',true);
						common.removeCache('internal_card_info',true);
                        $(".back_index,.pay_ok_mask").show();
                    }
				}
			});
		}
	});
}

function getOfflinePayMethod(){
	var offline_pay_method = common.getCache('offline_pay_method',true);
	if(offline_pay_method){
		setOfflineMethod(offline_pay_method);
	}else{
		common.http('Storestaff&a=get_pay_method',{}, function(data){
			common.setCache('offline_pay_method',data,true);
			setOfflineMethod(data);
		});
	}
}
function setOfflineMethod(data){
	if(data.open_alipay == 0){
		$('#open_alipay').remove();
	}
	if(data.cash_pay_qrcode == '0'){
		$('.wxsm .fl').html('用户扫码');
		$('.scan_code .h2 span').html('用户扫码');
		$('.scan_code .cot .inb').html('用户扫码支付');
	}
	if(data.offline_pay_list && data.offline_pay_list.length > 0){
		var html = '';
		for(var i in data.offline_pay_list){
			html+= '<dd data-id="'+data.offline_pay_list[i].id+'">'+data.offline_pay_list[i].name+'</dd>';
		}
		$('#offline_method').html(html);
	}
}

//回显一卡通
function getInternalCardInfo(data){
	ecard_usable_money = 0
	$('#internalCard_info #internalCard_money').html(data.ecard_total_money)
	$('#money_now').html(ecard_usable_money);
	$('#user_internalCard_money').html('￥'+ecard_usable_money);
}
//更新会员卡使用余额
function updateCardMoney(type=''){
	if(type){
		cardActionType = type;
	}
	var card_money = 0;
	let tmp_go_pay_price = 0 //每次减去的使用金额的值
	if (is_change==1) {
		go_pay_price = parseFloat(parseFloat(payPrice - score_deducte).toFixed(2));
	}else{
		go_pay_price = parseFloat(parseFloat(payPrice - merchant_coupon_discount - system_coupon_discount- score_deducte).toFixed(2));
	}
	switch (cardActionType) {
		case 'membership':
			tmp_go_pay_price = parseFloat(go_pay_price - ecard_usable_money).toFixed(2)//每次减去的使用金额的值
			card_money = card_data.card_money;
			if(card_money > tmp_go_pay_price){
				merchant_card_use = tmp_go_pay_price;
				$('#user_card_money').html('' + L_('￥') + '' + tmp_go_pay_price);
			} else {
				merchant_card_use = card_money;
				$('#user_card_money').html('' + L_('￥') + '' + card_money);
			}
			break;
		case 'internalCard':
			//一卡通
			tmp_go_pay_price = Number(parseFloat(go_pay_price - merchant_card_use).toFixed(2))//每次减去的使用金额的值
			card_money = parseFloat(internal_card_info.ecard_total_money);
			if(card_money > tmp_go_pay_price){
				ecard_usable_money = tmp_go_pay_price;
				$('#user_internalCard_money').html('' + L_('￥') + '' + tmp_go_pay_price);
			} else {
				ecard_usable_money = card_money;
				$('#user_internalCard_money').html('' + L_('￥') + '' + card_money);
			}
			break;
		default:
			break;
	}

	go_pay_price = parseFloat(go_pay_price - merchant_card_use - ecard_usable_money).toFixed(2)
	$('#otherPayMoney').html('' + L_('￥') + '' + common.floatVal(go_pay_price));
	if($('#otherPayMoney').html() == '' + L_('￥') + '0'){
		$('#otherPayMethod').hide();
		$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm,yzf').hide();
		$(".set_end li").removeClass("on");
	}else{
		$('#otherPayMethod').show();
		$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').show();

		if(pay_yzfpay_open){
			$('.yzf').show();
		}
		$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
	}
	$(".amend,.mask").hide();
}