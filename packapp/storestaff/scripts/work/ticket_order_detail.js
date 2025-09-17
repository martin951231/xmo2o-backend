$(document).ready(function(){
	$(".mask").height($(window).height());
	
	common.onlyScroll($('.scroll'));
	
	$(document).on('click', '.consum', function(){
		var type = $(this).data('type');
		if (type == 1) {
			common.http('Storestaff&a=group_pass_array', {'order_id':urlParam.order_id, 'noTip':true}, function(data){
				laytpl($('#passList').html()).render(data.pass_array, function(html){
					$('.consum_tc').find('ul').html(html);
				});
			});
			$(".consum_tc,.mask").fadeIn();
			myScroll.refresh();
		} else {
			
			layer.open({
			    content: '是否验证?'
			    ,btn: ['是', '否']
			    ,yes: function(index){
			      	common.http('Storestaff&a=group_verify', {'order_id':urlParam.order_id, 'noTip':true}, function(data){
						motify.log('验证消费成功！');
						setTimeout(location.reload(), 5000);
					});
					layer.close(index);
			    }
			    ,no:function(){
			    	motify.log('已取消验证');
			    }
		    });
			// common.http('Storestaff&a=group_verify', {'order_id':urlParam.order_id, 'noTip':true}, function(data){
				// motify.log('验证消费成功！');
				// setTimeout(location.reload(), 5000);
			// });
		}
	});

	$(document).on('click', '.consum_tc_n span.a39', function(){
		var group_pass = $(this).data('pass'), obj = $(this);
		
		layer.open({
		    content: '是否验证?'
		    ,btn: ['是', '否']
		    ,yes: function(index){
		      	common.http('Storestaff&a=group_array_verify', {'order_id':urlParam.order_id, 'group_pass':group_pass, 'noTip':true}, function(data){
					motify.log('验证消费成功！');
					obj.removeClass('a39').addClass('ecc').html('已消费').unbind('click');
				});
				layer.close(index);
		    }
		    ,no:function(){
		    	motify.log('已取消验证');
		    }
		});
		
	});
	$(document).on('click', '#allVerify', function(){
		
		var order_id = urlParam.order_id;
		layer.open({
		    content: '是否验证?'
		    ,btn: ['是', '否']
		    ,yes: function(index){
		      	common.http('Storestaff&a=group_verify', {'order_id':order_id, 'noTip':true}, function(data){
					motify.log('验证消费成功！');
					setTimeout(location.reload(), 5000);
				});
				layer.close(index);
		    }
		    ,no:function(){
		    	motify.log('已取消全部验证');
		    }
		});
		// common.http('Storestaff&a=group_verify', {'order_id':urlParam.order_id, 'noTip':true}, function(data){
			// motify.log('验证消费成功！');
			// setTimeout(location.reload(), 5000);
		// });
	});
	
	$(document).on('click', '.del, .mask', function(){
		$(".consum_tc,.mask").fadeOut();
	});
	
	$(document).on('click', '.input .sweep', function(){
		common.scan('scanResult');
	});
	
	common.http('Storestaff&a=ticket_order_details', {'order_id':urlParam.order_id}, function(data){
		laytpl($('#goodsDetail').html()).render(data.now_order, function(html){
			$('.commod').html(html);
		});
		//导游信息
		if(data.guide_arr.length==0){
			$('.guide').hide();
		}else{
			laytpl($('#guideDetail').html()).render(data.guide_arr, function(html){
				$('.guide').html(html);
			});	
		}
		//车辆信息
		if(data.park_arr.length==0 || !data.park_arr.list){
			$('.park').hide();
		}else{
			console.log(data.park_arr);
			laytpl($('#parkDetail').html()).render(data.park_arr, function(html){
				$('.park').html(html);
			});	
		}
		//用户信息
		if(!data.travel_arr.family_list && !data.travel_arr.img_list && !data.travel_arr.ticket_list){
			$('.user').hide();
		}else{
			console.log(data.travel_arr);
			laytpl($('#userDetail').html()).render(data.travel_arr, function(html){
				$('.user').html(html);
			});	
		}
		//门票信息
		if(data.ticket_arr.length==0){
			$('.ticket').hide();
		}else{
			laytpl($('#ticketDetail').html()).render(data.ticket_arr, function(html){
				$('.ticket').html(html);
			});	
		}

        /*laytpl($('#userDetail').html()).render(data.user, function(html){
            $('.user').html(html);
        });
        laytpl($('#writeDetail').html()).render(data.write_list, function(html){
            $('.write').html(html);
        });
		if (data.now_order.is_pick_in_store != 1) {
		    laytpl($('#addressDetail').html()).render(data, function(html){
	            $('.distri').html(html);
	        });
		}
		
		laytpl($('#orderDetail').html()).render(data.now_order, function(html){
			$('.order').html(html);
		});
		if (data.trade_hotel_info != '') {
			laytpl($('#hotelDetail').html()).render(data.trade_hotel_info, function(html){
				$('.hotel').html(html);
			});
		} else {
			$('.hotel').remove();
		}
		laytpl($('#paymentDetail').html()).render(data.now_order, function(html){
			$('.payment').html(html);
		});
		
		if(data.now_order.tuan_type < 2){
			$('#distriBox').hide();
		}
		if(data.now_order.status == 6){
			$('#paymentBox').hide();
		}
		
		if(data.pin_info.status==0 ||data.pin_info.status==2 ){
			$('#consume').hide();
		}*/
		if(data.ticket_arr.ticket_union_txt){
			$('.leaving').html('<div class="top clr"><h2 class="fl">其他说明</h2></div><div class="textarea"><textarea placeholder="暂无" readonly="readonly">' + data.ticket_arr.ticket_union_txt + '</textarea></div>');	
		}
		
//		console.log(data)
	});
	
	$(document).on('click', '.preser', function(){
		var express_id = $('.distri').find('.input input').val(), express_type = $(".distri").find("select option:selected").val();
		common.http('Storestaff&a=group_express', {'order_id':urlParam.order_id, 'express_id':express_id, 'express_type':express_type, 'noTip':true}, function(data){
			motify.log('保存成功！');
		});
	});
	
	$(document).on('click', '.distri .modify', function(){
		var merchant_remark = $('.distri').find('.textarea textarea').val();
		common.http('Storestaff&a=group_remark', {'order_id':urlParam.order_id, 'merchant_remark':merchant_remark, 'noTip':true}, function(data){
			motify.log('修改成功！');
		});
	});
	
	$(document).on('click', '#hotel_refund', function(){
		var post_data = {'order_id':urlParam.order_id,'noTip':true}
		var refund_num = {}
		$('.refund_num').each(function(index,val){
			refund_num[$(val).data('id')] = $(val).val();
		})
		post_data['refund_num'] = refund_num
		
		layer.open({
		    content: '是否退款?'
		    ,btn: ['是', '否']
		    ,yes: function(index){
		      	common.http('Storestaff&a=hotel_refund',post_data, function(data){
			
					motify.log('退款成功！');
					setTimeout(location.reload(), 3000);
					
				});
				layer.close(index);
		    }
		    ,no:function(){
		    	motify.log('已取消退款');
		    }
		});
		
	});
	
	$(document).on('click', '.jia', function(){
		var id  = $(this).data('id')
		var refund_num = $('#refund_num_'+id).val();
		refund_num = Number(refund_num)
		if(refund_num+1<=$(this).data('max_num')){
			 $('#refund_num_'+id).val(refund_num+1)
		}
		refund_show()
	});
	
	
	$(document).on('click', '.jian', function(){
		var id  = $(this).data('id')
		var refund_num = $('#refund_num_'+id).val();
		refund_num = Number(refund_num)
		if(refund_num-1>=0){
			 $('#refund_num_'+id).val(refund_num-1)
		}
		 refund_show()
	});
	
	
});

function scanResult(value){
	$('.input input').val(value);
	
}

function refund_show(){
	var refund_days = 0;
	var refund_rooms = 0;
	$.each($('.refund_num'),function(i,val){
		if($(val).val()>0){
			refund_days++;
		}
		refund_rooms+=Number($(val).val())
	})
	
	if(refund_days>0 && refund_rooms>0){
		$('.refund_button').show()
	}else{
		$('.refund_button').hide()
		
	}

	
}
