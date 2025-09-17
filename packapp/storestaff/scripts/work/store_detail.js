$(document).ready(function(){
	if(urlParam.order_id){
		var order_id = urlParam.order_id;
	}else{
		redirect('index.html','openLeftWindow');
		return false;
	}
	
	common.http('Storestaff&a=store_order_detail',{'order_id':order_id},function(data){ 
		common.setData(data);
		if(!data.user){
			$('#userInfo').hide();
		}
		$('#remark').html(data.order.remark);
		if(data.order.noDiscountBox == '0.00'){
			$('#noDiscountBox').hide();
		}
		
		if(data.order.ticketNum != 0){
			$('.ticketBox').show();
		}
		if(data.order.ticketInsure == 0){
			$('#ticketInsureLi').hide();
		}
		
		var merchantDiscount = data.order.card_discount_money;
		if(parseFloat(merchantDiscount)>0){
			merchantDiscount = merchantDiscount.toFixed(2) + '（' + data.order.card_discount + '折）';
			$('#order-price').html(data.order.price-data.order.card_discount_money)
			$('#CardDiscountBox').show();
			$('#order-CardDiscount').html(merchantDiscount);		
		}else{
			$('#order-price').html(data.order.price)
		}
		
		if(data.order.pay_total){
			$('#order-price').html(data.order.pay_total);
		}
		
		if(data.order.score_deducte){
			$('#order-score').html(data.order.score_deducte);
		}
		
		if(data.order.card_price){
			$('#order-cardPrice').html(data.order.card_price);
		}
		
		if(data.order.coupon_price){
			$('#order-couponPrice').html(data.order.coupon_price);
		}
		
		if(data.order.payment_money){
			$('#order-onlinePay').html(data.order.payment_money);
		}

		if(parseFloat(data.order.balance_pay)>0){
			$('#order-balancePay').html(data.order.balance_pay);
			$('#balancePayBox').show();
		}

		if(parseFloat(data.order.merchant_balance)>0){
			$('#order-merchantBalance').html(data.order.merchant_balance);
			$('#merchantBalanceBox').show();
		}

		if(parseFloat(data.order.card_give_money)>0){
			$('#order-cardGiveMoney').html(data.order.card_give_money);
			$('#cardGiveMoneyBox').show();
		}
		if(parseFloat(data.order.vip_discount_money)>0){
			$('#order-vipDiscountMoney').html(data.order.vip_discount_money);
			$('#vipDiscountMoneyBox').show();
		}
		if(parseFloat(data.order.offline_money)>0){
			$('#order-offlineMoney').html(data.order.offline_money);
			data.order.pay_type && $('#offline_pay_type').html('（'+data.order.pay_type+'）');
			$('#offlineMoneyBox').show();
		}
		
		$('span').each(function(i,item){
			if($(item).html() === '0.00' || $(item).html() === '0'){
				$(item).closest('li').hide();
			}
		});
		$('#order-pay_type').html(data.order.pay_type);

		//支付方式余额的循环展示
		if(data.order.pay_info && data.order.pay_info.length >0){
			laytpl($('#pay_info_list').html()).render(data.order, function(html){
				$('#pay_info').html(html);
			});
		}

		if (data.order.refund == 0) {
			$('.verification').show();
		} else {
			$('.rfd').show();
		}
	});


	//退款
	$(document).on('click', '.verification', function(e){
        e.stopPropagation();
        $('#order_id').val($(this).data('id'));
        $(".seek, .mask").show();
    });

	$(document).on('click', '.ensure', function(e){
		common.http('Storestaff&a=refund_store_order',{'order_id':$('#order-order_id').html(), noTip:true}, function(data){
            location.reload();
        });
    });

	$(".mask,.seek .del, .close").click(function(){
        $(".seek,.mask").hide();
    });
});