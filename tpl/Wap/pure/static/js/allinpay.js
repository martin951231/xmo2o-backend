var bind_id = 0;
var timer; //定时器
$(function(){
    $('.unbind').click(function(){
		bind_id = $(this).data('bind_id');
		$('#iosDialog1 .weui-dialog__title').html('解绑确认');
		$('#iosDialog1 .weui-dialog__bd').html('您确认要解绑卡吗？<br/>卡号：'+ $(this).data('card_id'));
		$('#iosDialog1').show();
		return false;
    });

    $('body').on('click',".band_car",function () {
		var post_data = {};
        var discount_id = $('#choose_discount').val();
        var bind_id = $('#choose_card').val();
		post_data.bind_id = bind_id;
		post_data.discount_id = discount_id;
		post_data.order_id = order_id;
		$.post(check_url,post_data,function(result){
			if(result.status==0){
				layer.open({
					content: result.info
					,skin: 'msg'
					,time: 2
				});
			}else{
                if(order_id != ''){
                    location.href = order_sms_url+'&order_id='+order_id+'&bind_id='+bind_id;
                }
			}
		});
    });

	$('#creditFlag').change(function(){
		if($(this).val() == '02'){
			$('.creditCardBox').removeClass('hide');
		}else{
			$('.creditCardBox').addClass('hide');
		}
	});
	
	
	$('#car_form_add').click(function(){
		$('#userName').val($.trim($('#userName').val()));
		$('#cardNo').val($.trim($('#cardNo').val()));
		$('#phoneNo').val($.trim($('#phoneNo').val()));
		$('#certificateNo').val($.trim($('#certificateNo').val()));
		$('#checkNo').val($.trim($('#checkNo').val()));
		
		var postData = {};
		postData.userName = $('#userName').val();
		postData.cardNo = $('#cardNo').val();
		postData.phoneNo = $('#phoneNo').val();
		postData.certificateType = '01';
		postData.certificateNo = $('#certificateNo').val();
		postData.creditFlag = $('#creditFlag').val();
		
		if(postData.creditFlag == '02'){
			postData.checkNo = $('#checkNo').val();
			postData.checkExpiry = $('#checkExpiryYear').val()+$('#checkExpiryMonth').val();
		}

		
		if(postData.userName == ''){
			alertMsg('请输入持卡人姓名');
			return false;
		}
		if(postData.cardNo == ''){
			alertMsg('请输入银行卡号');
			return false;
		}
		if(postData.phoneNo == ''){
			alertMsg('请输入银行预留手机号');
			return false;
		}
		if(postData.certificateNo == ''){
			alertMsg('请输入身份证号码');
			return false;
		}
		if(postData.creditFlag == '02' && postData.checkNo == ''){
			alertMsg('请输入信用卡背面最后3位数字');
			return false;
		}
		
		$('#loadingToast').fadeIn(100);
		var that = $(this);
		$.post(that.data('url'),postData,function(result){
			if(result.status == '1'){
				location.href = that.data('ok_url')+'&bind_id='+result.info;
			}else{
				$('#loadingToast').fadeOut(100);
				$('#iosDialog2 .weui-dialog__bd').html(result.info);
				$('#iosDialog2').show();
			}
			return false;
		});
	});
	
	
	$('#car_form_save').click(function(){
		$('#smsCode').val($.trim($('#smsCode').val()));
		
		var postData = {};
		postData.bind_id = $('#car_form_save').data('bind_id');
		postData.smsCode = $('#smsCode').val();
		
		if(postData.smsCode == ''){
			alertMsg('请输入短信验证码');
			return false;
		}
		$('#loadingToast').fadeIn(100);
		var that = $(this);
		$.post(that.data('url'),postData,function(result){
			if(result.status == '1'){
				$('#loadingToast').fadeOut(100);
				$('.msg_success').show();
			}else{
				$('#loadingToast').fadeOut(100);
				$('#iosDialog2 .weui-dialog__bd').html(result.info);
				$('#iosDialog2').show();
			}
			return false;
		});
	});
	
	
	$('#iosDialog2').on('click', '.weui-dialog__btn', function(){
		$(this).parents('.js_dialog').fadeOut(200);
	});
	
	$('#iosDialog1').on('click', '.weui-dialog__btn_default', function(){
		$(this).parents('.js_dialog').fadeOut(200);
	});
	$('#iosDialog1').on('click', '.weui-dialog__btn_primary', function(){
		$(this).parents('.js_dialog').hide(200);
		$('#loadingToast').fadeIn(100);
		var postData = {bind_id:bind_id,order_id:order_id};
		$.post(delete_url,postData,function(result){
			if(result.status == '1'){
				if($('.card_row').size() == 1){
					window.location.reload();
				}else{
					$('#loadingToast').fadeOut(100);
					$('.c_'+bind_id).remove();
				}
			}else{
				$('#loadingToast').fadeOut(100);
				$('#iosDialog2 .weui-dialog__bd').html(result.info);
				$('#iosDialog2').show();
			}
			return false;
		});
	});
	
	if($('#get_order_sms').size() > 0){
		$('#get_order_sms').click(function(){
			if($(this).hasClass('gray')){
				console.log('error');
				return false;
			}
			$('#loadingToast').fadeIn(100);
			var postData = {bind_id:$(this).data('bind_id'),order_id:$(this).data('order_id')};
			$.post($(this).data('url'),postData,function(result){
				$('#loadingToast').fadeOut(100);
				if(result.status == '1'){
					if(typeof(result.url) !="undefined" && result.url != ''){
                        var seconds = 5;
                        var str = '';
                        if(parseFloat(result.discount_money) > 0 ){
                            str = '<p style="text-align:center"><img src="'+static_url+'images/order_sms_success.png" style="width: 128px;height: 128px;"/><br><span style="font-size: 16px;">支付金额<span style="color:red">'+currency_symbol+result.paid_money+'</span><br>优惠金额<span style="color: red;">'+currency_symbol+ result.discount_money +'</span></span><br><div style="margin-top: 20px;color: gray;"><span id="time">5</span>秒后自动跳转到订单详情...</div></p>';
                        }
						setInterval(function(time){
							seconds --;
							$('#time').text(seconds);
						},1000);
						layer.open({
							type: 0,
							title: '支付结果',
							btn: ['立即跳转'],
							style: 'bottom:0px;width:80%;',
							skin:'footer',
							content: str,
							time:5,
							yes: function(){location.href = result.url;},
							end:function(){location.href = result.url;}
						});
					}
					var time = 59;
					$('#get_order_sms').addClass('gray').html(time + ' 秒');
						timer = setInterval(function(){
						if(time == 1){
							clearInterval(timer);
							$('#get_order_sms').removeClass('gray').html('获取验证码');
						}else{
							time--;
							$('#get_order_sms').html(time + ' 秒');
						}
					},1000);
				}else{
					$('#iosDialog2 .weui-dialog__bd').html(result.info);
					$('#iosDialog2').show();
				}
				return false;
			},'json');
		});
		$('#get_order_sms').trigger('click');
	}
	
	$('#card_pay_order').click(function(){
		var postData = {bind_id:$(this).data('bind_id'),order_id:$(this).data('order_id')};
		$('#smsCode').val($.trim($('#smsCode').val()));
		postData.smsCode = $('#smsCode').val();
		
		if(postData.smsCode == ''){
			alertMsg('请输入短信验证码');
			return false;
		}
		
		$('#loadingToast').fadeIn(100);
		$.post($(this).data('url'),postData,function(result){
			$('#loadingToast').fadeOut(100);
			var seconds = 5;
			var str = '';
			if(parseFloat(result.discount_money) > 0 ) {
				str = '<p style="text-align:center"><img src="'+static_url+'images/order_sms_success.png" style="width: 128px;height: 128px;"/><br><span style="font-size: 16px;">支付金额<span style="color:red">'+currency_symbol+result.paid_money+'</span><br>优惠金额<span style="color: red;">'+currency_symbol+ result.discount_money +'</span></span><br><div style="margin-top: 20px;color: gray;"><span id="time">5</span>秒后自动跳转到订单详情...</div></p>';
			}else{
				str='<p style="text-align:center"><img src="'+static_url+'images/order_sms_success.png" style="width: 128px;height: 128px;"/><br><span style="font-size: 16px;">支付金额<span style="color:red">'+currency_symbol+result.paid_money+'</span></span><br><div style="margin-top: 20px;color: gray;"><span id="time">5</span>秒后自动跳转到订单详情...</div></p>'
			}
			if(result.status == '1'){
               setInterval(function(time){
                    seconds --;
                    $('#time').text(seconds);
                },1000);
                layer.open({
                    type: 0,
                    title: '支付结果',
                    btn: ['查看订单详情'],
                    style: 'bottom:0px;width:80%;',
                    skin:'footer',
                    content: str,
                    time:5,
                    yes: function(){
                    	location.href = result.url;
                    	},
                    end:function(){
                    	location.href = result.url;
                    }
                });
			}else if(result.status == '1999'){
                clearInterval(timer);
                var time = 59;
                $('#get_order_sms').addClass('gray').html(time + ' 秒');
                var timer1 = setInterval(function(){
                    if(time == 1){
                        clearInterval(timer1);
                        $('#get_order_sms').removeClass('gray').html('获取验证码');
                    }else{
                        time--;
                        $('#get_order_sms').html(time + ' 秒');
                    }
                },1000);
                alertMsg('请输入新发送的验证码!');
			}else if(result.status == '2000'){
                var time = 120;
                $('#loadingToast').fadeIn(1200);
                var timer1 = setInterval(function(){
                    $.post(ajax_query,{order_id:order_id},function(result){
						if(result.is_paid==1){
                            $('#loadingToast').fadeOut(100);
                            var seconds = 5;
                            var str = '';
                            if(parseFloat(result.discount_money) > 0 ){
                                str = '<p style="text-align:center"><img src="'+static_url+'images/order_sms_success.png" style="width: 128px;height: 128px;"/><br><span style="font-size: 16px;">支付金额<span style="color:red">'+currency_symbol+result.paid_money+'</span><br>优惠金额<span style="color: red;">'+currency_symbol+ result.discount_money +'</span></span><br><div style="margin-top: 20px;color: gray;"><span id="time">5</span>秒后自动跳转到订单详情...</div></p>';
                            }else{
                                str='<p style="text-align:center"><img src="'+static_url+'images/order_sms_success.png" style="width: 128px;height: 128px;"/><br><span style="font-size: 16px;">支付金额<span style="color:red">'+currency_symbol+result.paid_money+'</span></span><br><div style="margin-top: 20px;color: gray;"><span id="time">5</span>秒后自动跳转到订单详情...</div></p>'
                            }
                            setInterval(function(time){
                                seconds --;
                                $('#time').text(seconds);
                            },1000);
                            layer.open({
                                type: 0,
                                title: '支付结果',
                                btn: ['立即跳转'],
                                style: 'bottom:0px;width:80%;',
                                skin:'footer',
                                content: str,
                                time:5,
                                yes: function(){location.href = result.url;},
                                end:function(){location.href = result.url;}
                            });
						}else{
                            if(time == 1){
                                $('#loadingToast').fadeOut(100);
                                alertMsg('支付失败！');
                                clearInterval(timer1);
							}
						}
						time-- ;
					},'json')
                },1000);
			}else{
				$('#iosDialog2 .weui-dialog__bd').html(result.info);
				$('#iosDialog2').show();
			}
			return false;
		},'json');
	});
});

function alertMsg(msg){
	$('#iosDialog2 .weui-dialog__bd').html(msg);
	$('#iosDialog2').show();
}