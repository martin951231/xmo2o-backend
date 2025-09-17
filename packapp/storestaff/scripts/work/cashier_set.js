var checkPayTimer = null;
var auth_code = '';
var is_open_pay_bind_user = 0;
var card_data = new Array();
var merchant_coupon_id = 0;
var merchant_coupon_discount = 0;
var system_coupon_id = 0;
var system_coupon_discount = 0;
var vip_discount_type = 0;
var discount_type = 0;
$(document).ready(function(){
    $('.offDiscount').hide();
	common.http('Storestaff&a=store_arrival_add_info',{},function(data){
		vip_discount_type = data.vip_discount_type;
		discount_type = data.discount_type;
		console.log(data.is_open_pay_bind_user)
		$("#Currency_symbol").val(data.Currency_symbol);
		is_open_pay_bind_user = data.is_open_pay_bind_user;
        
		if (data.is_open_pay_bind_user == '1') {
			// $('.hasBindPhone').show();
            $('.openPayBindUser').show();
		}else{
			$('.hasBindPhone').hide();
            $('.openPayBindUser').hide();
		}
		if (data.open_user_import_create_user==1) {
			$('#phone').attr('placeholder',L_('请输入手机号/实体卡号'));
		}else{
			$('#phone').attr('placeholder',L_('请输入手机号'));
		}

		if (data.open_staff_power&&data.is_change==0&&urlParam.business_type) {
			$('#total_price').attr('disabled','disabled'); 
			$('#change_price').show(); 
		}

		if (data.cash_alias_name) {
			$('.hasDiscount .tit').html(L_(data.cash_alias_name+'优惠'));
			vip_discount_txt1 = L_('与【'+data.cash_alias_name+'优惠】优惠方式为：折扣最优');
		}else{
			vip_discount_txt1 = L_('与【商家优惠】优惠方式为：折扣最优');
		}

		if(data.has_discount == true){
			if(data.discount_type == 1){	//1.打折
				var discountTxt = data.discount_percent +' '+ L_('折优惠');
			}else if(data.discount_type == 2){    //2.满减
				var discountTxt = L_('满')+data.condition_price+L_('元减')+data.minus_price+L_('元');
			}
			$('#discountTxt').html(discountTxt);
            $('.merDiscount').show();
			// 商家优惠		
			$("#switch1 div").click(function(){
				$(this).addClass("on").siblings().removeClass("on");
				if($(this).hasClass('off')){
					$('#price').html($('#total_price').val());
					$('.offDiscount').hide();
					if(!$("#switch3 div.no").hasClass('on')&&!$("#switch4 div.no").hasClass('on')){
						$('.no_discount_money').hide();
					}
                    $('#total_price').trigger('keyup');
				}else{
					$('#total_price').trigger('keyup');
					$('.offDiscount').show();
					$('.no_discount_money').show();
				}
			});
            $("#switch1 .no").addClass("on").siblings().removeClass("on");
            $('#total_price').trigger('keyup');
           
			$('.offDiscount').show();
			$('.no_discount_money').show();
		}else{
			$('.hasDiscount').hide();
		}
			$('#total_price,#no_discount_money,.no_discount_money').keyup(function(){
				var no_discount_money = parseFloat($('#no_discount_money').val()), total_money = parseFloat($('#total_price').val()),price = 0,minus_price = 0,
					can_discount_table_money = parseFloat($('#can_discount_table_money').val()),mer_table_discount = parseFloat($('#mer_table_discount').text());
				if(isNaN(no_discount_money)){
					no_discount_money = 0;
				}
				if (no_discount_money>total_money) {
					no_discount_money = total_money;
				}
				if(isNaN(total_money)){
					total_money = 0;
				}
	            var old_total_money = parseFloat($('#old_total_price').val());
	            var coupon_discount_money = parseFloat($('#coupon_discount_money').val());

	            if(isNaN(coupon_discount_money)){
	                coupon_discount_money = 0;
	            }
	            
	            if(isNaN(old_total_money)){
	                old_total_money = 0;
	            }

	            if (old_total_money != total_money) {
	                coupon_discount_money = 0;
	                $('#is_use_discount').val(0);
	                $('.plat_discount').hide();
	            } else {
	            	if ($('#coupon_discount_id').val() != '0') {
		                $('.plat_discount').show();
		                $('#is_use_discount').val(1);
	                }
	            }
				price = total_money;
				
	            //定制桌台优惠
	            if($('#switch2 .no').hasClass('on')){
	                mer_table_discount = (mer_table_discount / 10).toFixed(2);
	                var minus_table_price = (can_discount_table_money - can_discount_table_money * mer_table_discount).toFixed(2);
	                total_money = price = total_money>minus_table_price ? (total_money - minus_table_price) : 0;
				}else{
					minus_table_price = 0;
					total_money = parseFloat($('#total_price').val()).toFixed(2)
				}
      			if(minus_table_price > 0){
                    $('#show_table_minus').html('-'+data.Currency_symbol+'<span id="discount_table_money">' + common.floatVal(minus_table_price) + '</span>');
                }else{
                    $('#show_table_minus').html('-'+data.Currency_symbol+'0');
                }

				var vip_discount = parseFloat($('#vip_discount').val()),level_discount_type = $('#vip_discount_type').val(),vip_minus_price=0,discount_compare=false;
				// 商家优惠
				if($('#switch1 .no').hasClass('on')&&data.has_discount==true) {
                    if (discount_type == 1) {
                    	if(data.discount_percent > 0){
	                        price = eval(total_money - no_discount_money) * eval(data.discount_percent) / 10 + no_discount_money;
	                        minus_price = eval(total_money - no_discount_money) * (100 - data.discount_percent * 10) / 100;
                    	}
                    } else if (discount_type == 2) {
                        minus_price = Math.floor(eval(total_money - no_discount_money) / eval(data.condition_price)) * data.minus_price;
                        price = total_money - minus_price;
                    } else {
                        minus_price = 0;
                        price = total_money;
                    }
               	}else{
					minus_price = 0;
					price = total_money;
				}
				if (isNaN(minus_price)) {
					minus_price = 0;
				}
				price = parseFloat(price).toFixed(2);
				minus_price = parseFloat(minus_price).toFixed(2);

				// 会员等级优惠
				var vip_discount_txt = '';
			    if($('#switch3 .no').hasClass('on')){
					if(vip_discount_type==1){ // 折扣最优，优惠买和会员等级优惠不同享,选择最优
						if(level_discount_type==1){
							vip_minus_price = parseFloat((total_money-no_discount_money)*(1-(vip_discount/100)).toFixed(2)).toFixed(2);
							vip_discount_txt = '（<span style="font-size:9px;">'+vip_discount+'折</span>）';
						}else if(level_discount_type==2){
				    		if (vip_discount>total_money) {
								vip_minus_price = total_money;
							}else{
								vip_minus_price = vip_discount;
							}
							vip_discount_txt = '（<span style="font-size:9px;">'+L_('立减')+vip_discount+'元</span>）';
						}
						discount_compare = true;
					}else if(vip_discount_type==2 ){ // 折上折
						if(level_discount_type==1){
							vip_minus_price = parseFloat((price-no_discount_money)*(1-(vip_discount/100)).toFixed(2)).toFixed(2);
							vip_discount_txt = '（<span style="font-size:9px;">'+vip_discount+'折</span>）';
						}else if(level_discount_type==2){
							vip_minus_price = vip_discount>price ? price : vip_discount;
							vip_discount_txt = '（<span style="font-size:9px;">'+L_('立减')+vip_discount+'元</span>）';
						}
						price = price-vip_minus_price<0?0:parseFloat(price-vip_minus_price).toFixed(2);
					}else{
						vip_minus_price = 0;
					}
			    }else{
			    	vip_minus_price = 0;
			    }

				if(discount_compare && (Number(vip_minus_price)>Number(minus_price))){ // 折扣最优，会员等级优惠 较优
					if(total_money-minus_price<0){
						vip_minus_price = minus_price = total_money;
					}
					price = parseFloat(total_money - vip_minus_price).toFixed(2);
					minus_price = 0;

					discountTxt = L_('与【平台会员等级优惠】优惠方式为：折扣最优');
					$('#discountTxt').html(discountTxt);
					$('#vip_discount_money').html('-'+data.Currency_symbol + vip_minus_price + vip_discount_txt);
			       
				}else{
					if(vip_discount_type==2 ){ // 折上折
						$('#discountTxt').html('-'+data.Currency_symbol + minus_price+'（<span style="font-size:9px;">'+discountTxt+'</span>）');
						$('#vip_discount_money').html('-'+data.Currency_symbol + vip_minus_price + vip_discount_txt);
					}else{
			        	// 使用 买单优惠
						vip_minus_price = 0;
						$('#discountTxt').html('-'+data.Currency_symbol + minus_price+'（<span style="font-size:9px;">'+discountTxt+'</span>）');
						$('#vip_discount_money').html(vip_discount_txt1);
					}
				}

		        $('#vip_discount_money').data('price',parseFloat(vip_minus_price));

				if(minus_price > 0){
					$('#show_minus').html('-'+data.Currency_symbol+'<span id="discount_money">' + common.floatVal(minus_price) + '</span>');
				}else{
					$('#show_minus').html('-'+data.Currency_symbol+'0');
				}
				if(vip_minus_price > 0){
					$('#show_vip_minus').html('-'+data.Currency_symbol+'<span id="discount_vip_money">' + common.floatVal(vip_minus_price) + '</span>');
				}else{
					$('#show_vip_minus').html('-'+data.Currency_symbol+'0');
				}
				// 会员卡折扣
			    var card_discount = parseFloat($('#card_discount').val());
			    if (isNaN(card_discount)) {
			    	card_discount = 0;
			    }
			    card_discount = card_discount/10;
			    if($('#switch4 .no').hasClass('on') && card_discount>0){
			        var unmer_price  = parseFloat(price).toFixed(2); 
			        if (isNaN(unmer_price)) {
				    	unmer_price = 0;
				    }
			        var card_minus_price = ((unmer_price-no_discount_money) - (unmer_price-no_discount_money) * card_discount).toFixed(2);
			        $('#card_discount_d').html('-'+data.Currency_symbol + card_minus_price + '（<span style="font-size:9px;">'+card_discount*10+'折</span>）');
			        $('#card_discount_money').val('-'+data.Currency_symbol + card_minus_price);
			        $('#card_discount_money').data('price',parseFloat(card_minus_price));
                    $('#show_card_minus').html('-'+data.Currency_symbol+'<span id="discount_card_money">' + common.floatVal(card_minus_price) + '</span>');
			        price = (parseFloat(unmer_price) - card_minus_price).toFixed(2);
			    }else{
                    $('#show_card_minus').html('-'+data.Currency_symbol+'0');
			        $('#card_discount_money').val('-'+data.Currency_symbol+'0');
			        $('#card_discount_money').data('price',0);
			    }

				if (price<=total_money) {
					total_money = price; // 折扣后金额
				}
				if (isNaN(price)) {
					price = 0;
				}
		        $('#payPrice').html(price);
		        $('#payPrice').data('price',price);
		        var go_pay_price = price;

				console.log(price)
			    var uid =  $('#uid').val();
			    // 获取优惠券
				common.http('Storestaff&a=get_coupon',{noTip:true,total_money:go_pay_price,uid:uid,business_type:urlParam.business_type},function(coupon_data){
				    merchant_coupon_id = 0;
				    merchant_coupon_discount = 0;
				    system_coupon_id = 0;
				    system_coupon_discount = 0;
					card_data.card_new = coupon_data.card_new
					card_data.system_coupon = coupon_data.system_coupon
					$(".choice1").removeClass('ce9').html(L_('选择优惠券'));
					$(".choice2").removeClass('ce9').html(L_('选择优惠券'));
				})
			    // 优惠券
			    if (merchant_coupon_discount) {
			    	go_pay_price = parseFloat((go_pay_price - merchant_coupon_discount).toFixed(2));
			    }
			    if (system_coupon_discount) {
			    	go_pay_price = parseFloat((go_pay_price - system_coupon_discount).toFixed(2));
			    }

			    //积分抵扣
				common.http('Storestaff&a=get_score_discount',{noTip:true,total_money:go_pay_price,uid:uid},function(score_data){
					
					$('#score_deducte').data('price', parseFloat(score_data.score_deducte).toFixed(2));
					$('#score_deducte').html(L_('￥')+parseFloat(score_data.score_deducte).toFixed(2));
					$('#score_can_use_count').data('num', score_data.score_can_use_count);
					$('#score_can_use_count').html(score_data.score_can_use_count);
					if(score_data.score_can_use_count){
						// $('#use_score').prop('checked',true);
						$('.score_discount_li').show();
					}else{
						$('.score_discount_li').hide();
						$('#use_score').prop('checked',false);
					}
					
				})
				if ($('#use_score').prop('checked')) {
			    	go_pay_price = parseFloat((go_pay_price - $('#score_deducte').data('price')).toFixed(2));
				}
				var card_total_money = parseFloat($('#card_data-card_money').data('price'));

				if (card_total_money>=go_pay_price) {
					$('#user_card_money').html(L_('￥') + go_pay_price)
					$('#user_card_money').data('price', go_pay_price);
					go_pay_price = 0;
				}else if(card_total_money>0){
					go_pay_price = parseFloat(go_pay_price - card_total_money).toFixed(2);
					$('#user_card_money').html(L_('￥') + card_total_money).data('price', card_total_money);
				}

				var card_money = parseFloat($('#user_card_money').data('price'));
				// 还应支付金额
				$('#price').html(common.floatVal(go_pay_price - coupon_discount_money));

			});
			
		// }else{
			// $('.hasDiscount').hide();
			// $('#total_price').keyup(function(){
   //              var can_discount_table_money = parseFloat($('#can_discount_table_money').val()),mer_table_discount = parseFloat($('#mer_table_discount').text());
			// 	var total_money = parseFloat($('#total_price').val());
   //              //定制桌台优惠
   //              if($('#switch2 .no').hasClass('on')){
   //                  mer_table_discount = (mer_table_discount / 10).toFixed(2);
   //                  var minus_table_price = (can_discount_table_money - can_discount_table_money * mer_table_discount).toFixed(2);
   //                  total_money = total_money - minus_table_price;
   //              }else{
   //                  minus_table_price = 0;
   //                  total_money = parseFloat($('#total_price').val())
   //              }
   //              if(minus_table_price > 0){
   //                  $('#show_table_minus').html('-￥<span id="discount_table_money">' + common.floatVal(minus_table_price) + '</span>');
   //              }else{
   //                  $('#show_table_minus').html('');
   //              }

			// 	var old_total_money = parseFloat($('#old_total_price').val());
			//     var coupon_discount_money = parseFloat($('#coupon_discount_money').val());
			    
			//     if(isNaN(old_total_money)){
			//         old_total_money = 0;
			//     }
			// 	if(isNaN(total_money)){
			// 		total_money = 0;
			// 	}
			//     if(isNaN(coupon_discount_money)){
			//         coupon_discount_money = 0;
			//     }
			//     if (old_total_money != total_money) {
			//         coupon_discount_money = 0;
			//         $('#is_use_discount').val(0);
			//         $('.plat_discount').hide();
			//     } else {
			//         $('.plat_discount').show();
			//         $('#is_use_discount').val(1);
			//     }
			// 	$('#price').html(parseFloat((total_money - coupon_discount_money).toFixed(2)));
			// });
		// }
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
		// 会员等级优惠
		$("#switch3 div").click(function(){
			$(this).addClass("on").siblings().removeClass("on");
			if($(this).hasClass('off')){
				$('.offVipDiscount').hide();
				if(!$("#switch1 div.no").hasClass('on')&&!$("#switch4 div.no").hasClass('on')){
					$('.no_discount_money').hide();
				}
                $('#total_price').trigger('keyup');
			}else{
				$('#total_price').trigger('keyup');
				$('.offVipDiscount').show();
				$('.no_discount_money').show();
			}
		});

		// 会员卡折扣
		$("#switch4 div").click(function(){
			$(this).addClass("on").siblings().removeClass("on");
			if($(this).hasClass('off')){
				$('.offCardDiscount').hide();
                if(!$("#switch1 div.no").hasClass('on')&&!$("#switch3 div.no").hasClass('on')){
					$('.no_discount_money').hide();
				}
                $('#total_price').trigger('keyup');
			}else{
				$('#total_price').trigger('keyup');
				$('.offCardDiscount').show();
				$('.no_discount_money').show();
			}
		});	

		//点击使用积分
		$(document).on('click', '#use_score', function(){
			console.log($(this).attr("checked"))
			if ($('#use_score').attr("checked")) {
				$('#use_score').attr("checked",true);
			}else{
				$('#use_score').attr("checked",false);
			}
			var go_pay_price = parseFloat($('#payPrice').data('price'));
		    // 优惠券
		    if (merchant_coupon_discount) {
		    	go_pay_price = parseFloat((go_pay_price - merchant_coupon_discount).toFixed(2));
		    }
		    if (system_coupon_discount) {
		    	go_pay_price = parseFloat((go_pay_price - system_coupon_discount).toFixed(2));
		    }

			if ($('#use_score').prop('checked')) {
		    	go_pay_price = parseFloat((go_pay_price - $('#score_deducte').data('price')).toFixed(2));
			}
			var card_total_money = parseFloat($('#card_data-card_money').data('price'));

			console.log(card_total_money)
			if (card_total_money>=go_pay_price) {
				$('#user_card_money').html(L_('￥') + go_pay_price)
				$('#user_card_money').data('price', go_pay_price);
				go_pay_price = 0;
			}else{
				go_pay_price = parseFloat(go_pay_price - card_total_money).toFixed(2);
				$('#user_card_money').html(L_('￥') + card_total_money).data('price', card_total_money);
			}

			var card_money = parseFloat($('#user_card_money').data('price'));

		    var coupon_discount_money = parseFloat($('#coupon_discount_money').val());
		    if (isNaN(coupon_discount_money)) {
		    	coupon_discount_money = 0;
		    }
			// 还应支付金额
			$('#price').html(common.floatVal(go_pay_price - coupon_discount_money));

            // $('#total_price').trigger('keyup');
		});
	
		if(urlParam.business_type){
			common.http('Storestaff&a=store_arrival_get_info',{business_type:urlParam.business_type,business_id:urlParam.business_id},function(data){
				if (data.phone&&is_open_pay_bind_user==1) {
					$('#phone').val(data.phone);
					$('#phone').trigger('keyup');
				}
			
				if (data.coupon != null && parseInt(data.coupon.is_discount) == 1) {
				    $('#coupon_discount_id').val(parseInt(data.coupon.id));
				    $('#coupon_discount').html(parseFloat(data.coupon.discount_value) + '折');
				    $('#coupon_discount_money').val(parseFloat(data.coupon.discount_money));
				    $('#show_coupon_discount_money').html(L_('￥') + parseFloat(data.coupon.discount_money));
				    $('#is_use_discount').val(1);
				    $('.plat_discount').show();
				} else {
				    $('#is_use_discount').val(0);
				    $('.plat_discount').hide();
				}
                $('#old_total_price').val(data.go_pay_price);
                var go_pay_price = data.go_pay_price;;
				$('#total_price').val(go_pay_price);
                setTimeout(function(){
					$('#total_price').trigger('keyup');
                },'100')


                $('#can_discount_table_money').val(parseFloat(data.can_discount_table_money));
                $('#mer_tables').text(data.tables);
                $('#mer_table_discount').text(data.mer_discount);
				//定制桌位折扣 -- 如有桌位折扣则不可关闭
                if(data.mer_discount > 0){
                    $('.hasTableDiscount').show();
                    $("#switch2 .no").addClass("on").siblings().removeClass("on");
                    $('#total_price').trigger('keyup');
                    $('.offTableDiscount').show();
                }
                // $("#switch2 div").click(function(){
                //     $(this).addClass("on").siblings().removeClass("on");
                //     if($(this).hasClass('off')){
                //         $('#price').html($('#total_price').val());
                //         $('#total_price').trigger('keyup');
                //         $('.offTableDiscount').hide();
                //     }else{
                //         $('#total_price').trigger('keyup');
                //         $('.offTableDiscount').show();
                //     }
                // });
                //end
			});
		}else{
			$('#total_price').focus();
		}

		$('#orderForm').submit(function(){
			var total_price = parseFloat($('#total_price').val());
			if(isNaN(total_price)){
				motify.log('请输入订单金额');
				return false;
			}
			var postData = {};
			postData.total_price = total_price;
			postData.Currency_symbol1 = $("#Currency_symbol").val();
			postData.Currency_symbol2 = $("#Currency_symbol").val();
			postData.Currency_symbol3 = $("#Currency_symbol").val();
			if(!$('#offDiscount').hasClass('on')){
				postData.discount_money = parseFloat($('#discount_money').html());
				if(isNaN(postData.discount_money)){
					postData.discount_money = 0;
				}
			}else{
				postData.discount_money = 0;
		 	}

		 	//桌台折扣
            if(!$('#offTableDiscount').hasClass('on')){
                    postData.discount_money += parseFloat($('#discount_table_money').html());
                if(isNaN(postData.discount_money)){
                    postData.discount_money = 0;
                }
            }
			postData.coupon_discount_id = parseInt($('#coupon_discount_id').val());
			postData.is_use_discount = parseInt($('#is_use_discount').val());
			postData.coupon_discount_money = parseFloat($('#coupon_discount_money').val());
			postData.pay_money = parseFloat($('#price').html());
			postData.txt_info = $('#txt_info').val();
			
			if(urlParam.business_type){
				postData.business_type = urlParam.business_type;
				postData.business_id = urlParam.business_id;
			}
			if(urlParam.from_scan && urlParam.uid && urlParam.payid){
				postData.from_scan = urlParam.from_scan;
				postData.uid = urlParam.uid;
				postData.payid = urlParam.payid;
			}
			var no_discount_money = $('#no_discount_money').val();
			if (no_discount_money) {
                postData.no_discount_money = no_discount_money;
			}

			if (card_data) {
				postData.uid = card_data.uid;
			 	// 会员等级优惠
				if(!$('#offVipDiscount').hasClass('on')){
					postData.vip_discount_money = parseFloat($('#vip_discount_money').data('price'));
					if(isNaN(postData.vip_discount_money)){
						postData.vip_discount_money = 0;
					}
				}else{
					postData.vip_discount_money = 0;
			 	}	

			 	// 会员卡折扣
				if(!$('#offCardDiscount').hasClass('on')){
					postData.card_discount = parseFloat($('#card_discount_money').data('price'));
					if(isNaN(postData.card_discount)){
						postData.card_discount = 0;
					}
				}else{
					postData.card_discount = 0;
			 	}

			 	// 积分抵扣
			 	if ($('#use_score').prop('checked')) {
			 		postData.is_use_score = 1;
			 		postData.score_deducte = parseFloat($('#score_deducte').data('price'));
			 		postData.score_used_count = parseFloat($('#score_can_use_count').data('price'));
					if(isNaN(postData.score_deducte)){
						postData.score_deducte = 0;
						postData.score_used_count = 0;
					}
				}else{
					postData.score_deducte = 0;
					postData.score_used_count = 0;
			 	}

			 	// 商家优惠券
			 	if (merchant_coupon_id&&merchant_coupon_discount) {
			 		postData.card_id = merchant_coupon_id;
			 		postData.card_price = parseFloat(merchant_coupon_discount);
				}else{
			 		postData.card_price = 0;
				}

			 	// 平台优惠券
			 	if (system_coupon_id&&system_coupon_discount) {
			 		postData.coupon_id = system_coupon_id;
			 		postData.coupon_price = parseFloat(system_coupon_discount);
				}else{
			 		postData.coupon_price = 0;
				}

			 	// 会员卡余额使用
				var card_money = parseFloat($('#user_card_money').data('price'));
			 	if (card_money) {
			 		postData.card_money = parseFloat(card_money);
				}
			}
		    postData.pay_price =   parseFloat( $('#payPrice').data('price'));

			// console.log(postData);return false;
			common.http('Storestaff&a=store_arrival_add',postData,function(saveData){
				$('#total_price,#txt_info,#no_discount_money,#phone').val('');
				$('#show_minus,#price').html('');
				if(saveData.code=='SCAN_PAY_SUCCESS'){
					layer.open({
						title:'订单信息'
						,shadeClose:false
						,content: '订单创建成功，等待用户支付'
					});
					
					checkPayTimer = setInterval(function(){
						common.http('Storestaff&a=check_store_arrival_order',{order_id:saveData.order_id,noTip:true},function(data){
							common.removeCache('cashier_order_info',true);
							layer.open({
								content:'订单已经支付成功！'
								,shadeClose:false
								,btn: ['确定']
								,yes: function(index){
									window.history.go(-1);
								}
							});
							clearInterval(checkPayTimer);
						},function(data){
							// console.log(data);
						});	
					},2000);				
				}else{

							console.log(postData);			
							console.log(postData.discount_money+postData.vip_discount_money+postData.card_discount+postData.score_deducte+postData.card_price+postData.coupon_price);			
					saveData.total_price = postData.total_price;
					saveData.discount_money = (postData.discount_money+postData.vip_discount_money+postData.card_discount+postData.score_deducte+postData.card_price+postData.coupon_price).toFixed(2);
					saveData.pay_money = postData.pay_money;
					saveData.txt_info = postData.txt_info;
					saveData.Currency_symbol1 = postData.Currency_symbol1;
					saveData.Currency_symbol2 = postData.Currency_symbol2;
					saveData.Currency_symbol3 = postData.Currency_symbol3;
					saveData.phone = $('#card_data-phone').html();
					saveData.open_balance_pay_unverify = data.open_balance_pay_unverify;
					common.setCache('cashier_order_info',saveData,true);
					location.href = 'cashier_success.html';
				}
			});
			return false;
		});

		
	});

	$('.query .input .search,.query .input .search1').click(function(){
		common.scan('scanCardResult'); 
	});
	// 获取用户信息
	$('#phone').keyup(function(){
		var phone =$('#phone').val();
		if(isNaN(phone)){
			motify.log('请输入手机号');
			return false;
		}

		var postData = {};
		postData.phone = phone;
		postData.noTip = true;
		postData.auth_code = $('#auth_code').val();
		$('#auth_code').val('');
		common.http('Storestaff&a=get_user_by_phone',postData,function(data){
			card_data = data;
			if (postData.auth_code) {
				$('#phone').val(card_data.phone)
			}
			
			if (data.uid) {
				$('.offUserDiscount').show();
			}else{
				$('.offUserDiscount').hide()
			}
			$('#uid').val(data.uid);
			$('#card_id').val('');
			$('#card_no').html('');
			$('#card_money').html('');
			$('#card_discount').val(0);
			$('.card_discount_li').hide();

			$('#card_data-name').html('');
			$('#card_data-phone').html('');
			$('#card_data-card_id').html('');
			$('#card_data-card_money').html(0);
			$('#card_data-card_money').data('price',0);
			$('#card_data').hide();
			$('#user_data').hide();

			$('#vip_discount_type').val(0);
			$('#vip_discount').val(0);
			$('#offstr').html(0);
			$('.vipDiscount').hide();
			$("#switch3 div.off").addClass('on').siblings().removeClass("on");
			$("#switch4 div.off").addClass('on').siblings().removeClass("on");
			$('.offCardDiscount').hide();
			$('.offCouponDiscount').hide();
			$('.offVipDiscount').hide();
			if (data.phone) {
				$('#card_data-name').html(data.name);
				$('#card_data-phone').html(data.phone);
				$('#user_data').show();
			}
			// 会员卡信息
			if (data.card_id) {
				$('#card_id').val(data.card_id);
				$('#card_no').html(data.card_id);
				$('#card_money').html(data.card_money);
				$('#card_discount').val(data.discount);
				$('.card_discount_li').show();

				$('#card_data-card_id').html(data.card_id);
				$('#card_data-card_money').html(data.card_money);
				$('#card_data-card_money').data('price',data.card_money);
				$('#card_data').show();
				$("#switch4 div.no").addClass('on').siblings().removeClass("on");
				$('.cardDiscountInfo').show();
				$('.offCardDiscount').show();
				$('.offCouponDiscount').show();
			}
			// 会员等级优惠
			if (typeof(data.leveloff)!='undefined') {
				if (data.leveloff!=false) {
					$('#vip_discount_type').val(data.leveloff.type);
					$('#vip_discount').val(data.leveloff.vv);
					$('#offstr').html(data.leveloff.vv);
					$('.vipDiscount').show();
					$("#switch3 div.no").addClass('on').siblings().removeClass("on");
					$('.no_discount_money').show();
					$('.offVipDiscount').show();
				}
			}
			$('#total_price').trigger('keyup');
		});
		return false;
	});

	// 获取可用积分
	$('#check_phone').click(function(){
		var phone =$('#phone').val();
		if(isNaN(phone)){
			motify.log('请输入手机号');
			return false;
		}
		var temp_price = parseFloat($('#price').html());
		var postData = {};
		postData.total_money = temp_price;
		
		common.http('Storestaff&a=get_score_discount',postData,function(data){
			$('#score_deducte').val(data.score_deducte);
			$('#score_can_use_count').val(data.score_can_use_count);
			if (data.score_deducte) {
				$('.score_discount_li').show();
				$('.score_label').html('本单可使用积分<font color="red">'+data.score_can_use_count+'个</font>,可抵扣金额<font color="red">'+L_('￥')+data.score_deducte+'</font>');
			}else{
				$('.score_discount_li').hide();
			}
		});
		return false;
	});

	var choice_type = 1; //1-商家优惠券2-平台优惠券
	//优惠劵
	$(".choice1").click(function(){
		choice_type = 1;
		if(card_data){
			var coupon_list = card_data.card_new;
		console.log(coupon_list)
			var temp_price = parseFloat($('#payPrice').html());
			if( typeof(coupon_list) !='undefined'){
				if (coupon_list.length > 0) {
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
				}else{
					motify.log('无可用优惠券');
				}
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
			var coupon_list = card_data.system_coupon;
			var temp_price = parseFloat($('#payPrice').data('price'));;
			if( typeof(coupon_list) !='undefined'){
				if (coupon_list.length > 0) {
					var can_use_coupon_list = [];
					for(var i in coupon_list){
						if (parseFloat(coupon_list[i].order_money) <= temp_price) {
							can_use_coupon_list.push(coupon_list[i]);
						}
					}
					if(can_use_coupon_list.length > 0){
						laytpl($('#listCouponTpl2').html()).render(can_use_coupon_list, function(html){
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

		var go_pay_price = parseFloat($('#payPrice').data('price'));
	    // 优惠券
	    if (merchant_coupon_discount) {
	    	go_pay_price = parseFloat((go_pay_price - merchant_coupon_discount).toFixed(2));
	    }
	    if (system_coupon_discount) {
	    	go_pay_price = parseFloat((go_pay_price - system_coupon_discount).toFixed(2));
	    }

	    var uid =  $('#uid').val();
	    //积分抵扣
		common.http('Storestaff&a=get_score_discount',{total_money:go_pay_price,uid:uid},function(score_data){
			
			$('#score_deducte').data('price', parseFloat(score_data.score_deducte).toFixed(2));
			$('#score_deducte').html(L_('￥')+parseFloat(score_data.score_deducte).toFixed(2));
			$('#score_can_use_count').data('num', score_data.score_can_use_count);
			$('#score_can_use_count').html(score_data.score_can_use_count);
			if(score_data.score_can_use_count){
				// $('#use_score').prop('checked',true);
				$('.score_discount_li').show();
			}else{
				$('.score_discount_li').hide();
				$('#use_score').prop('checked',false);
			}
			
		})
		if ($('#use_score').prop('checked')) {
	    	go_pay_price = parseFloat((go_pay_price - $('#score_deducte').data('price')).toFixed(2));
		}
		var card_total_money = parseFloat($('#card_data-card_money').data('price'));

		if (card_total_money>=go_pay_price) {
			$('#user_card_money').html(L_('￥') + go_pay_price)
			$('#user_card_money').data('price', go_pay_price);
			go_pay_price = 0;
		}else{
			go_pay_price = parseFloat(go_pay_price - card_total_money).toFixed(2);
			$('#user_card_money').html(L_('￥') + card_total_money).data('price', card_total_money);
		}

		var card_money = parseFloat($('#user_card_money').data('price'));

	    var coupon_discount_money = parseFloat($('#coupon_discount_money').val());
	    if (isNaN(coupon_discount_money)) {
	    	coupon_discount_money = 0;
	    }
		// 还应支付金额
		$('#price').html(common.floatVal(go_pay_price - coupon_discount_money));


		if($('#otherPayMoney').html() == L_('￥') + '0'){
			$('.xxzf,.wxzf,.zfb,balance_pay,.wxsm').hide();
			$(".set_end li").removeClass("on");
		}else{
			$('.xxzf,.wxzf,.zfb,balance_pay,.wxsm').show();
			$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
		}
	});

	//修改会员卡使用价格
	$("#change_card_money").click(function(){
		$('#merchant_card_money').val('');

		var card_money = parseFloat($('#user_card_money').data('price'));
		$('#money_now').html(card_money);
		$("#merchant_card_change,.mask").show();
	});
	$(".mask,.amend .del").click(function(){
		$(".amend,.mask").hide();
	})

	$('#merchant_card_change .ensure').click(function(){
		var tmp_money = parseFloat($('#merchant_card_money').val());
		var payPrice = parseFloat($('#payPrice').data('price'));
	    // 优惠券
	    if (merchant_coupon_discount) {
	    	payPrice = parseFloat((payPrice - merchant_coupon_discount).toFixed(2));
	    }
	    if (system_coupon_discount) {
	    	payPrice = parseFloat((payPrice - system_coupon_discount).toFixed(2));
	    }

		if ($('#use_score').prop('checked')) {
	    	payPrice = parseFloat((payPrice - parseFloat($('#score_deducte').data('price'))).toFixed(2));
		}
		// var card_total_money = parseFloat($('#card_data-card_money').data('price'));

		// console.log(card_total_money)
		// if (card_total_money>=go_pay_price) {
		// 	$('#user_card_money').html('￥' + go_pay_price)
		// 	$('#user_card_money').data('price', go_pay_price);
		// 	go_pay_price = 0;
		// }else{
		// 	go_pay_price = parseFloat(go_pay_price - card_total_money).toFixed(2);
		// 	$('#user_card_money').html('￥' + card_total_money).data('price', card_total_money);
		// }

		// var card_money = parseFloat($('#user_card_money').data('price'));

	    var coupon_discount_money = parseFloat($('#coupon_discount_money').val());
	    if (isNaN(coupon_discount_money)) {
	    	coupon_discount_money = 0;
	    }

		if(isNaN(tmp_money)){
			motify.log('请输入正确的价格');
			return false;
		}
		if(tmp_money > card_data.card_money){
			motify.log('使用会员卡余额不能超过当前余额');
			return false;
		}
		if(tmp_money > payPrice){
			motify.log('使用会员卡余额不能超过实付金额');
			return false;
		}
		$('#user_card_money').data('price',tmp_money);
		$('#user_card_money').html(L_('￥') + tmp_money);
		// 还应支付金额
		$('#price').html(common.floatVal(payPrice-tmp_money-coupon_discount_money));
		$('#price').data('price',common.floatVal(payPrice-tmp_money-coupon_discount_money));
		// if($('#otherPayMoney').html() == '￥0'){
		// 	$('#otherPayMethod').hide();
		// 	$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').hide();
		// 	$(".set_end li").removeClass("on");
		// }else{
		// 	$('#otherPayMethod').show();
		// 	$('.xxzf,.wxzf,.zfb,.balance_pay,.wxsm').show();
		// 	$(".set_end li.xxzf").addClass("on").siblings().removeClass('on');
		// }
		$(".amend,.mask").hide();
	});

	$(".mask,.coupon .del").click(function(){
		$(".coupon,.mask").hide();
	});
	$('#confirmOrder').css({display:'block',width:'80%',margin:'25px auto'});
});

function scanCardResult(str){
	$('#auth_code').val(str)
	// $('.introduce').val(str);
	if (is_open_pay_bind_user==1) {
		$('#phone').trigger('keyup');
	}
}