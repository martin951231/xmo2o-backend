$(document).ready(function(){
	var indexData = common.getCache('indexData',true);
	
	if(!indexData){
		location.href = 'index.html';
	}
	
	if(common.checkWeixin()){
		common.fillPageBg(1,'#f4f4f4');
	}
	
	if(common.checkIosApp()){
		$('#container').height($(window).height() - 64);
		common.onlyScroll($('#container'));
	}
	
	common.http('Deliver&a=new_detail',{supply_id:urlParam.supply_id}, function(data){
		$('.openUserMap').data({baidu_lng:data.supply.aim_lnt,baidu_lat:data.supply.aim_lat,name:'[' + L_('用户') + '] ' + data.supply.name,address:data.supply.aim_site});
		$('.openStoreMap').data({baidu_lng:data.supply.from_lnt,baidu_lat:data.supply.from_lat,name:'[' + L_('店铺') + '] ' + data.supply.store_name,address:data.supply.from_site});
		if (data.supply.status < 2) {
		    $('#supply-from_text').parent('li').remove();
		} else if(data.supply.get_type == 0){
			data.supply.from_text = L_('抢单');
		}else if(data.supply.get_type == 1){
			data.supply.from_text = L_('系统派单');
		}else if(data.supply.get_type == 1){
			data.supply.from_text = data.supply.change_name + '配送员';
		}

		if (data.supply.item == 3 || data.supply.item == 4 ) {
			$('#goodsList').parent('div').remove();
			$('#supply-appoint_time').parent('li').remove();
			var html = '';
            data.order.real_orderid = data.supply.real_orderid;
            data.order.create_time = data.supply.create_time;
			if (data.supply.server_type == 1) {
				html += '<div class="leaflets">' + L_('帮送') + '</div>';
				html += '<div class="f16 c9">' + L_('商品类型') + '：' + data.supply.goods_name+ '</div>';
				html += '<div class="f16 c9">' + L_('商品重量') + '：' + data.supply.goods_weight+ '</div>';
				html += '<div class="f16 c9">' + L_('商品价格') + '：' + currency_symbol + data.supply.goods_price+'</div>';
				if (data.supply.image.length > 0) {
				    html += '<div class="f16 c9 acc"><span>商品图片:</span>';
				    for (var im in data.supply.image) {
				        html += '<img class="img_click" src="' + data.supply.image[im] + '" alt="" >';
				    }
				    html += '</div>';
				}
				$('#appoint_show').html(L_('取货时间'));
				data.supply.appoint_time = data.supply.server_time;
				$('.server_type2,.server_type3').remove();
			} else {
	            html += '<div class="leaflets">' + L_('帮买') + '</div>';
	            html += '<div class="f14 c9">' + L_('商品名称') + '：' + data.supply.goods_name+ '</div>';
	            if (data.supply.goods_weight) {
                    html += '<div class="f16 c9">' + L_('商品重量') + '：' + data.supply.goods_weight + L_('千克') + '</div>';
				}
	            html += '<div class="f14 c9">' + L_('商品估价') + '：' + currency_symbol + data.supply.goods_price+ '</div>';
                
	            if (data.supply.store_name) {
                    html += '<div class="f14 c9">' + L_('店铺名称') + '：' + data.supply.store_name+ '</div>';
                } 

                if (data.supply.store_phone ) {
                    html += '<div class="f14 c9 Namelist" style="padding: 0">' + L_('店铺电话') + '：<span class="c6"><a href="tel:'+data.supply.store_phone+'">' + data.supply.store_phone+ '</span></div>';
                }

                if (data.supply.image.length > 0) {
                    html += '<div class="f16 c9 acc"><span>' + L_('商品图片') + '：</span>';
                    for (var im in data.supply.image) {
                        html += '<img class="img_click" src="' + data.supply.image[im] + '" alt="" >';
                    }
                    html += '</div>';
                }
	            $('#appoint_show').html(L_('期望送达'));
	            $('.server_type1').remove();
	            if (data.supply.server_type == 2) {
	            	$('.server_type3').remove();
	            } else {
	            	$('.server_type2').remove();
	            }
			}
            html += '<p class="f12 red">' + L_('额外小费') + '：' + currency_symbol + data.supply.tip_price + '</p>';
            html += '<p class="f12 red">' + L_('配送距离') + '：' + data.supply.distance + L_('公里') + '，' + L_('配送费') + currency_symbol + data.supply.freight_charge+ '（'+ L_('配送费收入') + currency_symbol + data.supply.deliver_user_fee+'）</p>';
			$('.details_list').html(html);
			$('#cue_field_box, #Merchant').remove();
		} else {
			$('#fromSource').remove();
			laytpl($('#goodListTogherTpl').html()).render(data.goods_list, function(html){
				$('#goodsList').html(html);
			});

			if(data.store && data.store.store_image && data.store.store_image.length>0){
				$('#store_image_sec').show();
				laytpl($('#storeImageTpl').html()).render(data.store.store_image, function(html){
					$('#store_image').html(html);
				});
			}else{
				$('#store_image_sec').hide();
			}
			
			
			if(data.order.cue_field && data.order.cue_field.length > 0){
				laytpl($('#cueFieldListTpl').html()).render(data.order.cue_field, function(html){
					$('#cue_field_ul').html(html);
				});
			}else{
				$('#cue_field_box').remove();
			}
		}
		if (parseInt(data.supply.fetch_number) == 0) {
		    $('#supply-fetch_number').parent('li').remove();
		}
		common.setData(data);
		if(data.supply.note == ''){
			$('#supply-note').html(L_('无'));
		}

		// 隐私号码处理
		if (data.supply.virtual_phone.length>0 && data.supply.virtual_phone_overtime==0) {
			$('#supply-phone').attr('data-phone',data.supply.virtual_phone[0]);
		}else if (data.supply.virtual_phone.length>0 && data.supply.virtual_phone_overtime==0) {
			$('#supply-phone').attr('data-phone','');
		}else{
			$('#supply-phone').attr('data-phone',data.supply.phone);
		}

		var storePhoneArr = data.store.phone.split(' ');
		storePhoneArr = arrUnique(storePhoneArr);
		var storePhoneTxt = '';
		for(var i in storePhoneArr){
			storePhoneTxt += '<div style="line-height:1.5;">'+storePhoneArr[i]+'</div>';
		}
		$('#store-phone').html(storePhoneTxt);
		
		var order_status = data.supply.status;
		var statusTxt = '';
		var postUrl = '';
		switch(order_status){
			case '1':
				statusTxt = L_('抢单');
				postUrl = 'Deliver&a=grab';
				$('#fetch_number_box').remove();
				break;
			case '2':
				if(data.supply.is_fetch_order == '0'){
					$('#eventBtn').addClass('Fetch');
					statusTxt = L_('接单');
					postUrl = 'Deliver&a=fetch';
				}else{
					$('#eventBtn').addClass('Pick');
					statusTxt = L_('取货');
					postUrl = 'Deliver&a=pick';
				}
				if(data.order.fetch_number == '0'){
					$('#fetch_number_box').remove();
				}
				if (data.is_cancel_order == 1) {
    				$('#cancel').show().click(function(){
    		            var supply_id = urlParam.supply_id;
    	                var postParam = {'supply_id':supply_id};
    		            layer.open({
    		                content: L_('确认要放弃配送此单吗') + '？'
    		                ,btn: [L_('确定'), L_('取消')]
    		                ,yes: function(index){
    		                    common.http('Deliver&a=cancelOrder', postParam, function(data){
    		                        layer.open({title:[L_('操作提示') + '：','background-color:#289FFD;color:#fff;'],content:L_('放弃配送成功'),btn: [L_('确定')],end:function(){
    		                            if(common.checkApp()){
											if(common.checkAndroidApp()){
												window.pigcmspackapp.closewebview(2);
											}else{
												common.iosFunction('closewebview/2', {name: 'closewebview', type: '2'});
											}
										}else{
											location.href = 'pick.html';
										}
    		                        },success:function(){$('.layermbtn span').width('100%');}});
    		                    });
    		                    layer.close(index);
    		                }
    		            });
    		        });
				}
				break;
			case '3':
				$('#eventBtn').addClass('Dis');
				statusTxt = L_('配送');
				postUrl = 'Deliver&a=send';
				if(data.order.fetch_number == '0'){
					$('#fetch_number_box').remove();
				}
				break;
			case '4':
				$('#eventBtn').addClass('service');
				statusTxt = L_('送达');
				postUrl = 'Deliver&a=complete';
				if(data.order.fetch_number == '0'){
					$('#fetch_number_box').remove();
				}
				break;
			case '5':
			case '6':
			case '7':
				statusTxt = L_('删除');
				postUrl = 'Deliver&a=new_del';
				$('#fetch_number_box').remove();
				break;
		}
		if(order_status < 5){
			$('#supply-end_time').html(L_('未送达'));
		}
		$('#eventBtn').html(statusTxt).show().click(function(){
			var supply_id = urlParam.supply_id;
			if(order_status != 5){
				var postParam = {'supply_id':supply_id};
			}else{
				var postParam = {'supply_ids':supply_id};
			}
			layer.open({
				content: '确认要设置为已'+statusTxt+'吗？'
				,btn: [L_('确定'), L_('取消')]
				,yes: function(index){
					common.http(postUrl,postParam, function(data){
						layer.open({
							title:[L_('操作提示'),'background-color:#289FFD;color:#fff;']
							,content:'已成功设置为'+statusTxt,btn: [L_('确定')]
							,end:function(){
								$('#layerTmpStyle').remove();
								location.reload();
							}
							,success:function(){
								$('.layermbtn span').width('100%');
								$('body').append('<style id="layerTmpStyle">.layermbtn:before{content:"";position:static;}</style>');
							}
						});
					});
					layer.close(index);
				}
			});
		});
		if(data.supply.is_hide == '1'){
			$('#eventBtn').html(L_('已删除')).removeAttr('id').unbind('click');
		}
	});
});