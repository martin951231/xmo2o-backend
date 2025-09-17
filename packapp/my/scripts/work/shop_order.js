var userInfo = null;
var order = null; 
var orderObject='';
var map_config = null;
var order_store='';
var show_status='';
var order_deliver_user=''; 
var map=null;
var mapStyle=null; 
var point2,point1,point2=null;
var label2,label3,label1=null;
var opts1,opts2,opts3=null;
var marker1,marker2,marker3=null; 
var myIcon,myIcon1,myIcon2=null;  
var isShowMap=null;
var mapIsLoad = false;
var isSetMapCenter = false;
var isStoreMapSetview = false;
var isUserMapSetview = false;
var is_google_map_js_load = false; 
var pay_order_by_redirect=null;

var is_pay=null;
var is_status=null;
var openNum='' 
var curColor=''
var counponNum=null
var shareData=''
var phone=''

var is_init_share_coupon_data = false;
 
 common.removeCache('clickNum',true);
 common.removeCache('frist_input_pwd',true);
 common.removeCache('orderid',true); 
 common.removeCache('counponNum');  
 
 
$('#map').css('height',$(window).width());
$('.redbg').css('height',$(window).width()/3*2);  
$('.pay_box').css('margin-top',$(window).width()/3*2);  
$('.pay_box,.pay_sucess,.pay_block').css('width',$(window).width()-20); 

 
// 滚动到顶部出现  
 function scrollTop(){  
    $(window).scroll(function(){
        if(isShowMap == 1){
            if($(window).scrollTop() >= 50){
                $('.redbg').show(); 
                $('#map').hide();  
                $('.pay_sucess').removeClass('pay_sucessfixed');  
                $('.flex_position').addClass('fixed');   
                console.log($('.flex_position').height())
                $('.pay_indexTil').show(); 
                if(orderObject.order_score != 0){   
                    $('.pay_box').css('margin-top',$(window).width()/3*2-10);
                }    
    
            } else{ 
                $('.redbg').hide(); 
                $('#map').show();  
                $('.pay_sucess').addClass('pay_sucessfixed');  
                $('.flex_position').removeClass('fixed');   
                $('.pay_indexTil').hide();  
                $('.pay_box').css('margin-top',$(window).width()/3*2); 
            }   
            
            if($('.redbg').is(':visible')){ 
                $('#map').hide();
            } 
        }
     });  
 }  
var mw_redirect_info = '';
var is_load = true;
function init(){
    common.http('My&a=shop_order_detail',{'order_id':urlParam.order_id,noTip:order ? true : false},function(data){
        order = data;  
        orderObject=order.order;
        order_store=order.store;
        order_deliver_user=order.deliver_user; 
        orderObject.paid=orderObject.paid;  
       
        is_pay=orderObject.paid;
        is_status=orderObject.status;  
 
		var is_hark = orderObject.is_hark
        
		if(is_hark != undefined && is_hark == 1 ){
			$('#is_hark').show()
		}else{
			$('#is_hark').hide()
		}


        if (orderObject.mw_redirect_info && orderObject.mw_redirect_info != undefined && orderObject.mw_redirect_info.card_number != undefined) {
			mw_redirect_info = orderObject.mw_redirect_info; 		
			// 建议添加银行卡   
			if(mw_redirect_info && is_load == true){ 
				is_load = false;
				weui.confirm('<div class="addBank"><p class="bankTop">'+L_('不想再输入银行卡?一秒添加')+'<label style="color:red">'+ mw_redirect_info.card_number+'</label>'+L_('的银行卡，开始体验快捷与安全的支付')+'</p><div class="flex"><div style="width:100px;"><img src="images/warning.png" style="width:100%"  /></div><div class="fr_tan">'+L_('根据澳洲法规，您的银行卡信息由银行保存，HarkHark不会收集或保存您任何的信用卡信息。')+'</div> </div></div>', { 
					buttons: [{
						label: L_('下次吧！'),
						type: 'default',
						onClick: function(){
							console.log('no')   
						}
					}, {
						label: L_('我要添加'),
						type: 'primary',
						onClick: function(){ 
							common.setCache('from',1,true); 
							redirect_self('/packapp/hake_credit_pay/inputAmount.html?id=' + mw_redirect_info.id + '&redirect_card=1&order_id=' + urlParam.order_id);
						}
					}]
				});
			}
        }
		
        
        if(common.checkWeixin()){ 
            if(order.show_lottery == 1){
                $('.choujiang').show(); 
            }   
           if(order.share_coupon_show == 1){  
               $('.releaseLottery').show();
              if(order.share_coupon_info.share_coupon_get_num !=0 ){  
                  var counponOpenNum=common.getCache('counponNum');   
                  if(counponOpenNum == undefined){   
                     counponNum++;
                     common.setCache('counponNum',counponNum);
                     $('.alap1').show();   
                  }  
                  $('.look_btn').attr('href',order.share_coupon_info.go_to_view);
              }
              $('.share_coupon_get_num').text(order.share_coupon_info.share_coupon_get_num); 
              $('#share_coupon_num').text(order.share_coupon_info.share_coupon_num);
              shareData={
                  share_desc:order.share_coupon_info.share_desc,
                  share_img:order.share_coupon_info.share_img,
                  share_title:order.share_coupon_info.share_title,
                  share_link:order.share_coupon_info.go_to_view,
              }
          }
            if(!is_init_share_coupon_data){
                shareConfig(order.share_coupon_show)
            }
        } 
        
        
        common.setData(order); 
        // 是否显示地图  
        isShowMap=orderObject.isShowMap
        if(order && order.status_list && order.status_list[0] && order.status_list[0].status == data.status_list[0].status && isShowMap==1){
            setTimeout("initialize_map()",5000);
            // return false;
        }
        
        if(orderObject.paid == 0){
            $('.payInfoBox').hide();
        }else{
            $('.payInfoBox').show();
        }
       
		$('.openMap').data({name:orderObject.name,address:orderObject.adress,lng:orderObject.lng,lat:orderObject.lat});
		map_config = config.config.map_config;
		if(map_config == 'google'){
			$('.openMap').data({type:'wgs84'});
		}else{
			$('.openMap').data({type:'baidu'});
		}
  
        if( isShowMap== 1){
            if(!mapIsLoad){
                if(map_config == 'google'){
                    $.getScript('https://maps.googleapis.com/maps/api/js?v=3.exp&sensor=true&libraries=places&key='+config.config.google_map_ak+'&callback=initialize_map');  
                }else{
                    $.getScript('https://api.map.baidu.com/api?v=2.0&ak='+config.config.baidu_map_ak+'&callback=initialize_map'); 
                }
                mapIsLoad = true;
            }else{ 
                initialize_map();
            } 
           $('#map').show();
        //    scrollTop(); 
        }else{
           $('.redbg').show(); 
           $('#map').hide();  
           $('.pay_sucess').removeClass('pay_sucessfixed');  
           $('.flex_position').addClass('fixed');    
           $('.pay_indexTil').show(); 
           if(orderObject.order_score != 0){   
               $('.pay_box').css('margin-top',$(window).width()/3*2-10);
           }   
           if(orderObject.order_status < 5){  
               $('.flex_position.fixed .DeliveryAddress').addClass('DeliveryAddress1'); 
           }  
           $('.pay_box').css('margin-top','0'); 
           $('.pay_indexTil').css('position','fixed'); 
        }  
       
        $('.orderAddress').text(order.order.address);   
        $('.go_pay_price').text(order.order.go_pay_price); 
         $('.amountPaid').text(order.order.payment_money);
        
        // 备注
        if(order.order.desc){ 
            $('.remark .desc').html(order.order.desc);
        }
		
		// 发票
		if (orderObject['invoice_head']) {
			$('.invoice .desc').html(orderObject['invoice_head'])
		} else {
			$('.invoice').hide()
		}
		
		//自定义字段
        $('.order_custom_field').remove();
        if(order.order.cue_field && order.order.cue_field.length > 0){
            for(var i in order.order.cue_field){
				if (order.order.cue_field[i].txt) {
					$('#order_info').append('<li class="flex order_custom_field"><span>' + L_(order.order.cue_field[i].title) + '</span><span class="desc">' + L_(order.order.cue_field[i].txt) + '</span></li>');
				}
			}
        }
		
        //按钮展示
        if(orderObject.paid == 1){  
			$('.com_btn .pay_order').hide();
			
            $('.callTel').show();
            if(order.deliver_user && order.deliver_user.uid){     
                if(orderObject.order_status<5){ 
                    $('.link_horseman').show(); 
                }  
                $('.dl_arrviceInfo dt.is_pick1').show(); 
                $('.cancel_order').hide();
            }  
            if(orderObject.order_status == 5){ 
                $('.link_horseman').show();  
            }
            //店员接单后，隐藏取消按钮
            if(orderObject.order_status < 1){
                $('.cancel_order').show();
            }
            else{
                $('.cancel_order').hide(); 
            } 
           
        }else if(orderObject.paid == 0 && orderObject.status != 5){
           $('.com_btn .pay_order').show();
        }
        if(orderObject.order_status >=5){   
            $('.pay_box').css('margin-top',0);
            $('.pay_indexTil').css('position','fixed'); 
            $('.btn_evaluate,.after_sale,.Another_order').show(); 
            if( order.is_open_shop_refund == 1){ 
                $('.after_sale').show();
            }else{
                $('.after_sale').hide();
            }
			if(orderObject.order_status == 6){
				$('.btn_evaluate').hide();
			}
        }  
		
		// 确认收货
		if (orderObject.paid == 1 && orderObject.status == 1 && orderObject.is_pick_in_store == 3) {
			$('.com_btn .confirm').show();
			$(document).on('click', '.com_btn .confirm', function () {
				weui.confirm(L_('您确定已经收到货物了？'), {
				    title: L_('提示'),
				    buttons: [{
				        label: L_('取消'),
				        type: 'default',
				        onClick: function(){ console.log('no') }
				    }, {
				        label: L_('确定收货'),
				        type: 'primary',
				        onClick: function(){
							 common.http('My&a=shop_finishOrder', {order_id: urlParam.order_id}, function(data){
								 console.log('点击了确认收货', data)
							 })
						}
				    }]
				});
			})
		} else {
			$('.com_btn .confirm').hide();
		}
       if(orderObject.expect_use_time){
		 $('.expect_use_time').text(getDayName(orderObject.expect_use_time.substring(0,10))+orderObject.short_expect_use_time);  
	  }else{
		 $('.expect_use_time').text(orderObject.short_expect_use_time); 
	  }      
     
        // 评价 
        if(orderObject.order_score != 0){ 
            $('.evaluate').show();  
            $('.dl_arrviceInfo,.btn_evaluate').hide();
            var scoreW=orderObject.order_score/5*100+'%'
            $('.starNumFull').css('width',scoreW)
            $('.topStatic').css('margin-top','10px');  
        }  

        if(orderObject.status == 4){  
            $('.flex_position.fixed .DeliveryAddress').removeClass('DeliveryAddress1'); 
        }

        // if( orderObject.status == 1){  
        //     $('.callTel').remove();  
        // }
        //商品列表
        laytpl($('#order_listBox').html()).render(order.goods_list,function(html){ 
			$('.order_listBox').html(html);
			$('currency').replaceWith(config.config.Currency_symbol);
			var discount_old_price = false;
			$('.order_listColorGray.old_price').each(function(i,item){
				if($.trim($(item).html()) != ''){
					discount_old_price = true;
				}
			});
			if(discount_old_price == false){
				$('.order_listColorGray.old_price').remove();
			}
        });
         
        // 状态列表   
        var status_list= order.status_list; 
        $('.pay_indexTil').text(status_list[0].status_txt);
        $('.pay_static .staticTime label').text(status_list[0].dateline); 
        $('.pay_static .staticTime span').text(status_list[0].status_txt);
        laytpl($('#hideSelectList').html()).render(status_list,function(html){
            $('.hideSelect').html(html); 
            if(status_list.length <= 1){ 
                $('.toggleSlide').hide();
            }
        });
        // 进度条  
        $('.cur_progress').css('width',orderObject.progress_bar+'%');    
        //打包费显示隐藏  
        if(orderObject.packing_charge == 0){
            $('.packing_charge').hide();
        } 
        if(orderObject.freight_charge == 0){
            $('.freight_charge').hide();
        }
        if(orderObject.other_money == 0){
            $('.other_money').hide();
        }
        if(!orderObject.service_charge || orderObject.service_charge == '' || orderObject.service_charge == 0){
            $('.service_charge').hide();
        }

        if(orderObject.freight_charge == 0 && orderObject.packing_charge == 0 && orderObject.other_money == 0 && orderObject.service_charge == ''){
            $('.paySmBolck .paySmBolck1').remove();  
        } 
        // 平台
        var system_minusType=orderObject.discount_detail; 
        if(system_minusType != ''){
            var discount_detailHtml=''
            for (x in system_minusType){  
                discount_detailHtml+='<li class="flex system_minus"><span class="sp1">'
                if( system_minusType[x].discount_type == 1){ 
                    discount_detailHtml+='<label class="em_icon green">'+L_('首')+'</label>'+L_('平台满减')+''+config.config.Currency_symbol+system_minusType[x].money+''
                }else if(system_minusType[x].discount_type == 2){ 
                    discount_detailHtml+='<label class="em_icon">'+L_('减')+'</label><L_>平台优惠满</L_>'+config.config.Currency_symbol+system_minusType[x].money+'';
                }else if(system_minusType[x].discount_type == 3){
                    discount_detailHtml+='<label class="em_icon green">'+L_('首')+'</label><L_>店铺满减满</L_>'+config.config.Currency_symbol+system_minusType[x].money+'';
                }else if(system_minusType[x].discount_type == 4){ 
                    discount_detailHtml+='<label class="em_icon">'+L_('减')+'</label><L_>店铺优惠满</L_>'+config.config.Currency_symbol+system_minusType[x].money+''; 
                }else if(system_minusType[x].discount_type == 5){
                    discount_detailHtml+='<label class="em_icon">'+L_('惠')+'</label><L_>商品满</L_>'+config.config.Currency_symbol+system_minusType[x].money+'<L_>配送费</L_>';
                }  
                if (system_minusType[x].percentage_status==1) {
                    discount_detailHtml+=L_('享')+system_minusType[x].minus*10+L_('折')+'</span><span class="sp2">-'+config.config.Currency_symbol+system_minusType[x].minus2+'</span></li>' 
                }else{
                    discount_detailHtml+=L_('减')+config.config.Currency_symbol+system_minusType[x].minus+'</span><span class="sp2">-'+config.config.Currency_symbol+system_minusType[x].minus+'</span></li>' 
                }
            } 
            $('.paySmBolck2').html(discount_detailHtml);
        }else{
            $('.paySmBolck2').remove();
        } 

        // 优惠券 
        if(orderObject.coupon_price == 0){ 
            // $('#order-coupon_price').html('<label class="color9">'+L_('未使用')+'</label>');  
            $('#order-coupon_price').closest('li').remove();
        } 
        if(orderObject.card_price == 0){ 
            // $('#order-card_price').html('<label class="color9">'+L_('未使用')+'</label>'); 
            $('#order-card_price').closest('li').remove();
        }  
        if(orderObject.discount_minus == 0){
             $('#order_discount_minus').remove();
        } 
        // 会员折扣  
        if(orderObject.card_discount ==0 || orderObject.card_discount == 10){  
            $('#order-card_discount').closest('li').hide();
        }
        
        var discount_price=parseFloat(orderObject.discount_price)+parseFloat(orderObject.vip_level_reduce_money);
        var minus_price=parseFloat(orderObject.minus_price)+parseFloat(orderObject.vip_level_reduce_money);  
        $('.discount_price').text(discount_price.toFixed(2));
		if(minus_price.toFixed(2) == '0.00'){
			$('#order_discount_minus').hide(); 
		}else{
			$('.minus_price').text(minus_price.toFixed(2)); 
		}  
        // 配送地址
        $('.order-address').text(orderObject.address);    
        // order_status=orderObject.order_status;
        show_status=orderObject.order_status;   
        if(show_status == 5){ 
           $('.dl_arrviceInfo dd').html(L_('感谢您对'+orderObject.shop_plat_tip+'的信任，期待再次光临。')); 
           $('.pay_block.topStatic').css('margin-top','10px'); 
        } 

         // 支付成功   
        if(orderObject.paid == 1 && orderObject.status == 0){   
            // $('.pay_sucess').show();   
            if($('#map').is(':visible')){
                $('.pay_sucess').addClass('pay_sucessfixed'); 
                // setTimeout(function(){
                //     $('.pay_sucess').remove(); 
                // },5000); 
            }
        }   
        
		$('.deliver_details').hide();
        if(order.order.is_pick_in_store == 2){ // 自提  
            if(orderObject.paid == 0){
                $('.cancel_order').hide();
                //线下零售不显示期望送达时间
                if(order.order.order_from == 6){
                    $('.is_pick1').hide();
                }
            }else{
                if(order.order.order_status < 1){
                    $('.cancel_order').show();
                }else{
                    $('.cancel_order').hide();
                }
                $('.flex_position.fixed .DeliveryAddress .span12').show();
                $('.flex_position.fixed .DeliveryAddress .span13').hide(); 
                $('.flex_position.fixed .DeliveryAddress .span11').remove(); 
                $('.dl_arrviceInfo dd,.dl_arrviceInfo dt').hide();
                $('.dl_arrviceInfo dt.is_pick,.dl_arrviceInfo dd.is_pick').show();
                //线下零售不显示自提信息
                if(order.order.order_from != 6){
                    $('.is_pick2').show();
                }
                $('.is_pick1').hide();
                $('.userphone').text(order.order.userphone);  
                $('.pay_box').css('margin-top',0);
                $('.pay_indexTil').css('position','fixed');   
                $('.openMap').data({name:order.store.name,address:order.store.adress,lng:order.store.lng,lat:order.store.lat});
                if(order.order.status == 4 || order.order.status == 2 || order.order.status == 3 || order.order.status == 1 || order.order.status == 8 || order.order.status == 9 || order.order.status == 5){
                    $('.cancel_order').remove(); 
                }
            }  
        }else if(order.order.is_pick_in_store == 3){ // 快递 
            $('.pay_box').css('margin-top',0);
            $('.pay_indexTil').css('position','fixed');  
            $('.dl_arrviceInfo').hide();
            $('.flex_position.fixed .DeliveryAddress .span12').hide();
            $('.flex_position.fixed .DeliveryAddress .span13').show();
            $('.flex_position.fixed .DeliveryAddress .span11').remove();
            
            $('.pay_sucess').css('margin-bottom','20px')
            if(orderObject.status >0){
                $('.deliver_details').show();
            } 
            if(orderObject.status == 1){
                $('.cancel_order').show();
            }
            if(orderObject.status == 2){  
                $('.cancel_order').hide();  
                $('.btn_evaluate,.after_sale,.Another_order').show();
               
            } 
            // if(orderObject.status > 2 ){
            //     $('.btn_evaluate,.after_sale,.Another_order').show();
            // }

            if(orderObject.status == 3){ 
               $('.btn_evaluate').hide();
               $('.pay_block.DeliveryAddress1').remove();
            }  
           
        }else{
			$('.is_pick').hide();
			$('.is_pick2').hide(); 
        }
        
        if(orderObject.score_deducte == 0){
            $(this).closest('li').remove();
        }

        if(orderObject.card_give_money == 0){ 
             $('#order-card_give_money').closest('li').hide(); 
        }
        // 商家余额抵扣
        if(orderObject.merchant_balance == 0){ 
            $('#order-merchant_balance').closest('li').hide(); 
        } 
        // 
        if(orderObject.balance_pay == 0){ 
            $('#order-balance_pay').closest('li').hide(); 
        }

        if(orderObject.qiye_pay == 0){
            $('#order-qiye_pay').closest('li').hide();
        }

        if(orderObject.payment_money == 0){ 
            $('#order-payment_money').closest('li').hide(); 
        } 

        if(orderObject.score_deducte == 0){
            $('#order-score_deducte').closest('li').hide();  
        }   
        openNum=common.getCache('openNum'); 
         if(is_pay == 1 && is_status == 0){ 
            if(openNum >= 1){   
                openNum = 0;
                setTimeout(function(){ 
                     $('.pay_sucess').hide(); 
                },5000);
            }else{
               $('.pay_sucess').show();     
            } 
         }else{
            $('.pay_sucess').hide();   
         }
       setTimeout("init()",5000);  
        
        if(order.store.phone == ''){   
            $('.callTel').hide();   
        } else if(labelList.length == 0) {
			var storePhoneList = order.store.phone.split(' ')
			for (var i = 0 ; i < storePhoneList.length; i++) {
				labelList[i] = {}
				labelList[i].label = storePhoneList[i]
				labelList[i].onClick = (function (i) {
					return function () {
						console.log('11111', storePhoneList[i])
						window.location.href = 'tel:'+storePhoneList[i]
						if(common.checkIosApp()){
							window.webkit.messageHandlers.interactionCall.postMessage(storePhoneList[i]);
						}
					}
				})(i);
			}
		}
		
		phone = (order.deliver_user && order.deliver_user.phone) ? order.deliver_user.phone : order.order.deliver_info.phone;
		if(phone){
    		var phoneListArr = phone.split(' ')
    		for (var i = 0 ; i < phoneListArr.length; i++) {
    			phoneList[i] = {}
    			phoneList[i].label = phoneListArr[i]
    			phoneList[i].onClick = (function (i) {
    				return function () {
    					console.log('11111', phoneListArr[i])
    					window.location.href = 'tel:'+phoneListArr[i]
    					if(common.checkIosApp()){
    						window.webkit.messageHandlers.interactionCall.postMessage(phoneListArr[i]);
    					}
    				}
    			})(i);
    		} 
		} 

          
    }) 
}  
 

load_config(function(){   
    userInfo = common.getCache('userInfo',true);    
    $('.score_name').text(config.score_name);
    curColor=config.config.mobile_head_color; 
    $('.redbg,.pay_indexTil,.cur_progress ').css('background',curColor); 
    init(); 
   
    $('currency').replaceWith(config.config.Currency_symbol);
    
    replace_html_lang();
},true);   

 
function google_map_js_load(){   
    console.log('google_map_js_load',333)   
    var overlay = CustomMarker('store',map,{latlng:new google.maps.LatLng(order_store.lat, order_store.lng)},1,order_store.logo);   
   //用户
    if(show_status == 4){  
        $('.marker1').remove();  
        overlay=''
        var overlayUser = CustomMarker('user',map,{latlng:new google.maps.LatLng(orderObject.lat, orderObject.lng)},2,userInfo.avatar);  
    }
    var noticeTxt=''
    var distance=''
    var txtInfo=''
    
     
	if(order_deliver_user && order_deliver_user.uid){  
        var order_deliver_userUid=common.getCache('order_deliver_userUid',true); 
        if(order_deliver_userUid != order_deliver_user.uid ){
            $('.deliverClass').hide(); 
        } 
		
        common.setCache('order_deliver_userUid',order_deliver_user.uid,true);  
        var gmarker='' 
        var overlays=''
        if(order_deliver_user.goto == 'store'){ 
            txtInfo=L_("店铺")
            overlays = [
                new google.maps.LatLng(order_deliver_user.now_lat,order_deliver_user.now_lng),
                new google.maps.LatLng(order_store.lat,order_store.lng)
            ]; 
    
        }else if(order_deliver_user.goto == 'user'){
            txtInfo=L_("您")
            overlays = [
                new google.maps.LatLng(order_deliver_user.now_lat,order_deliver_user.now_lng),
                new google.maps.LatLng(orderObject.lat,orderObject.lng)
            ];   
        }   
        markers = []; 
		for (var j = 0; j < gmarker.length; j++ ) {
		    gmarker[j].setMap(null);
		}
        for(var i = 0;i<overlays.length;i++){
             gmarker = new google.maps.Marker({
                position : overlays[i],
                map : map,
                icon:'images/alap.png'
            });
            markers.push(gmarker);  
        }   
        
        var bounds = new google.maps.LatLngBounds();
        //读取标注点的位置坐标，加入LatLngBounds
        for(var i = 0;i < markers.length;i++){
            bounds.extend(markers[i].getPosition());
        }
        //调整map，使其适应LatLngBounds,实现展示最佳视野的功能
        map.fitBounds(bounds,50);
        var overlay_deliver_user=''
		if( show_status ==2 || show_status ==3 || show_status == 4 ){ 
            if(show_status == 2){
                if(orderObject.is_fetch_order){
					noticeTxt=L_("配送员已经接单")
				}else{
					noticeTxt=L_("分配配送员")
				}
            }else  if(show_status == 3){
                noticeTxt=L_("配送员已到店")
            }else if(show_status == 4){
                noticeTxt=L_("骑手正在派送")
            }
			var orderObjectInfo={
				noticeTxt:noticeTxt,
				txtInfo:txtInfo,
				distance:order.deliver_user.distance
            }    
			
            overlay_deliver_user = CustomMarker('deliver',map,{latlng:new google.maps.LatLng(order_deliver_user.now_lat, order_deliver_user.now_lng)},3,orderObjectInfo); //配送员
			
		}    
	}
}

function initialize_map(){   
    if(map_config == 'google'){ 
		if(typeof google != 'undefined'){ 
            // 店铺
			var point2=new google.maps.LatLng(order_store.lat,order_store.lng)
			var mapProp = {
				center:point2,
				zoom:15,
				disableDefaultUI:true,  
				mapTypeId:google.maps.MapTypeId.ROADMAP,
				gestureHandling: 'greedy'
            };  

			if(map == null){
				map = new google.maps.Map(document.getElementById("map"), mapProp); 
            } 
             
            var script = document.createElement('script');
            script.type = 'text/javascript';
            script.onload = function(){
                google_map_js_load();
            };
            script.src = 'scripts/work/CustomGoogleMapMarker.js'; 
			document.getElementsByTagName('head')[0].appendChild(script);      
		} 
    }else{    
        if(map == null){
            map = new BMap.Map("map");
            mapStyle={  style : "light" }
            map.setMapStyle(mapStyle);  
        }     
        // 店铺   
        console.log(show_status) 
        point2 = new BMap.Point(order_store.lng, order_store.lat);  
        if(show_status < 4){
            myIcon2 = new BMap.Icon("images/yuan.png", new BMap.Size(30,30));   
            marker2 = new BMap.Marker(point2,{icon:myIcon2});
            if(!isSetMapCenter){
                map.centerAndZoom(point2, 14);
                isSetMapCenter = true;
            }
            map.addOverlay(marker2);  
            opts2 = {
                position : point2,     
                offset   : new BMap.Size(-25, -60),    
                }  
                label2 = new BMap.Label('<div class="storeInfo"><img src="'+order_store.logo+'"><div class="sanjiao"></div></div>', opts2); 
                label2.setStyle({  
                height : "50px", 
                lineHeight : "50px",
                border:'1px solid #fff',
                background:'#fff'
                
            });  
            marker2.setLabel(label2);  
        }else{   
            $('.storeInfo').remove();
            myIcon2 = new BMap.Icon("images/alap_white.png", new BMap.Size(30,30));  
            marker2 = new BMap.Marker(point2,{icon:myIcon2});
            map.addOverlay(marker2); 
        } 

        // 配送员  
		if(marker1){
			map.removeOverlay(marker1);
		}
        if(order_deliver_user && order_deliver_user.uid){
            point = new BMap.Point(order_deliver_user.now_lng, order_deliver_user.now_lat);  //配送员的点    
            var noticeTxt='' 
            var txtInfo=""
            if(order_deliver_user.goto == 'store'){
                noticeTxt=L_("赶往店铺取货")
                txtInfo="店铺"
                if(!isStoreMapSetview){
                    var overlays = [
                        new BMap.Point(order_deliver_user.now_lng,order_deliver_user.now_lat),
                        new BMap.Point(order_store.lng, order_store.lat)
                    ];
                    var viewPort = map.getViewport(overlays)
                    map.centerAndZoom(viewPort.center, viewPort.zoom-2);
                    isStoreMapSetview = true;
                }
            }else if(order_deliver_user.goto == 'user'){
                txtInfo="您"
                if(!isUserMapSetview){
                    var overlays = [
                        new BMap.Point(order_deliver_user.now_lng,order_deliver_user.now_lat),
                        new BMap.Point(orderObject.lng, orderObject.lat)
                    ];
                    var viewPort = map.getViewport(overlays)
                    map.centerAndZoom(viewPort.center, viewPort.zoom-2);
                    isUserMapSetview = true;
                }
            }    
            if( show_status ==2 || show_status ==3 || show_status == 4 ){
                if(show_status == 2){
                    if(orderObject.is_fetch_order){
						noticeTxt=L_("配送员已经接单")
					}else{
						noticeTxt=L_("分配配送员")
					}
                }else  if(show_status == 3){
                    noticeTxt=L_("配送员已到店")
                }else if(show_status == 4){
                    noticeTxt=L_("骑手正在派送")
                }
                myIcon = new BMap.Icon("images/driver.png", new BMap.Size(38,36));  
                marker1 = new BMap.Marker(point,{icon:myIcon}); 
                map.addOverlay(marker1);  
                opts1 = {
                    position : point,    // 指定文本标注所在的地理位置
                    offset   : new BMap.Size(-50, -60),    //设置文本偏移量 
                    } 
                label1 = new BMap.Label('<div class="Distribution flex"><div class="DistributionTxt"><h3>'+noticeTxt+'</h3><p>' + L_('距'+txtInfo) + '：<label style="color:#FFD36F">'+order_deliver_user.distance+'</label></p></div></div>', opts1);  // 创建文本标注对象
                label1.setStyle({ 
                    fontSize : "12px",
                    height : "20px", 
                    lineHeight : "20px",
                    border:'1px solid transparent',
                    background:'transparent' 
                });  
                marker1.setLabel(label1);    
            } 
        }   
        // 用户  
		myIcon3 = new BMap.Icon("images/yuan.png", new BMap.Size(30,30)); 
        point3 = new BMap.Point(orderObject.lng, orderObject.lat);    
        marker3 = new BMap.Marker(point3,{icon:myIcon3});
        if(show_status == 4){ 
            map.addOverlay(marker3); 
            opts3 = {
                position : point3,     
                offset   : new BMap.Size(-10, -60),    
                }    
            label3 = new BMap.Label('<div class="avatar"><div class="sanjiao"></div><img src="'+userInfo.avatar+'"></div>', opts3); 
            label3.setStyle({  
                height : "50px", 
                lineHeight : "50px", 
                border:'1px solid transparent',
                background:'transparent' 
            });  
            marker3.setLabel(label3);    
        }else{
            map.removeOverlay(marker3);
        } 
    } 
}  
 
// 点击展开，收起   
if($('.ul_order_list li').length>3){
    $('.clickToggle').show();
    $('.ul_order_list').css('height',$('.ul_order_list li').height()*4+20); 
    $('.clickToggle').click(function(){  
        $('.ul_order_list').css('height','auto');
        $(this).hide();
    })
}   

// 点击查看状态
$('.pay_static_con').click(function(){    
    $('.hideSelect').toggleClass('show');  
    if($('.hideSelect').hasClass('show')){
        $('.toggleSlide').addClass('rotateImg');
        if($('.flex_position').hasClass('fixed')){ 
            // $('.pay_box').css('margin-top',$('.flex_position').height());
        }  
    }else{
        $('.toggleSlide').removeClass('rotateImg');  
        if($('#map').is(':visible') == false){
            // $('.pay_box').css('margin-top',$('.flex_position').height()); 
        } 
    }
}) 
//联系商户  
var labelList = []
var phoneList = []
$('.callTel').click(function(){
    weui.actionSheet(labelList, [
        {
            label: L_('取消'),
            onClick: function () {
                console.log(L_('取消'));
            }
        }
    ]);
}) 
// 联系骑手
 

$('.link_horseman').click(function(){ 
	weui.actionSheet(phoneList, [
	    {
	        label: L_('取消'),
	        onClick: function () {
	            console.log(L_('取消'));
	        }
	    }
	]); 
})  
 
  
//复制 
function copy1(){
     var Url2=document.getElementById("order-real_orderid").innerText;
     copyBtn(Url2);  
}

function copy2(){
     var Url2=document.getElementById("order-real_orderid").innerText;
     copyBtn(Url2);  
}


function copyBtn(message){   
    var input = document.createElement("input");
    input.value = message;
    document.body.appendChild(input);
    input.select();
    input.setSelectionRange(0, input.value.length), document.execCommand('Copy');
    document.body.removeChild(input);
    weui.toast(L_('复制成功'), 3000); 
} 
 
// 取消订单
$('.cancel_order').click(function(){
    window.location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + '/wap.php?g=Wap&c=My&a=shop_order_refund&order_id='+urlParam.order_id;   
}) 
// 评价
$('.btn_evaluate').click(function(){ 
    window.location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + "/wap.php?g=Wap&c=My&a=shop_feedback&order_id="+urlParam.order_id;  
})
// 再来一单
$('.Another_order').click(function(){
    window.location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + "/wap.php?g=Wap&c=Shop&a=confirm_order&order_id="+urlParam.order_id+'&store_id='+order.store.store_id; 
})
// 申请售后
$('.after_sale').click(function(){
    window.location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + "/wap.php?g=Wap&c=Shop&a=refund&order_id="+urlParam.order_id; 
})

//支付订单 
$('.pay_order').click(function(){
    var use_sys_coupon = order.order.coupon_id != 0 ? true : false
    var use_mer_coupon = order.order.card_id != 0 ? true : false
	if(common.checkWeixinApp()){
		wx.miniProgram.navigateTo({
			url:'/pages/shop_new/confirmOrder/pay?order_id='+urlParam.order_id+'&type=shop&use_sys_coupon='+use_sys_coupon+'&use_mer_coupon='+use_mer_coupon+'&system_coupon_id='+order.order.coupon_id+'&merchant_coupon_id='+order.order.card_id
		});
	}else if (common.checkApp()){
        if (common.checkAndroidApp()) {
            window.lifepasslogin.payCheckNew("shop", urlParam.order_id, use_sys_coupon, order.order.coupon_id, use_mer_coupon, order.order.card_id );
        } else {
            window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'payCheckNew', orderID:urlParam.order_id, type:'shop', 
                use_sys_coupon:use_sys_coupon, sys_id: order.order.coupon_id, use_mer_coupon:use_mer_coupon, mer_id:order.order.card_id });
        }
    } else {
		window.location.href = (window.location.protocol == 'http:' ? 'http:' : 'https:' ) + '//' + requestDomain + "/wap.php?g=Wap&c=Pay&a=check&type=shop&order_id="+urlParam.order_id;  
	}
})   

// 查看物流 
// web-view下的页面内 
// if (!window.WeixinJSBridge || !WeixinJSBridge.invoke) {
// //    document.addEventListener('WeixinJSBridgeReady', ready, false)
// } else {
   
// } 
$('.look_more').click(function(){   
	if(order.order.kdniao_url && order.order.kdniao_url != ''){
		window.location.href = order.order.kdniao_url;
	}else{
		window.location.href = 'http://m.kuaidi100.com/index_all.html?type='+order.order.express_code+'&postid='+order.order.express_number+'&callbackurl=/wap.php?g=Wap&c=Pay&a=check&type=shop&order_id='+urlParam.order_id;
	}
});

// 确定收货
$('.sure_order').click(function(){    
    weui.confirm(L_('您确定已经收到货物了？'), { 
        buttons: [{
            label: L_('取消'),
            type: 'default',
            onClick: function(){  
            }
        }, {
            label: L_('确定'),
            type: 'primary',
            onClick: function(){ 
                setTimeout(function(){
                    errcodeNotice();
                },350) 
            }
        }]
    });   
}) 

function errcodeNotice(){   
    common.http('My&a=finishOrder',{'order_id':urlParam.order_id},function(data){   
    },function(data){
        $('.sure_order').remove();
        $('.Another_order,.btn_evaluate,.after_sale').show();  
        if (data.errcode == 1) {   
            weui.confirm(data.msg, { 
                buttons: [{
                    label: '取消',
                    type: 'default',
                    onClick: function(){  
                    }
                }, {
                    label: '确定',
                    type: 'primary',
                    onClick: function(){  
                    }
                }]
            });   
        } 
    })  
}  

// 添加银行卡添加成功
var bindSucess=common.getCache('bindSucess');
if( bindSucess == 1){
	common.removeCache('bindSucess'); 
    $('.pay_sucess').remove();
    weui.confirm(L_('恭喜，您的银行卡已成功添加,如需更改设置，请前往个人中心 - 我的银行卡'), { 
        buttons: [{
            label: L_('知道了'),
            type: 'primary',
            onClick: function(){
  
            }
        }]
    });
} 
//验证成功且支付成功且未设支付密码  
var show_credit_pwd_tip=common.getCache('show_credit_pwd_tip'); 
if(show_credit_pwd_tip == 1 ){
    $('.pay_sucess').remove();
    weui.confirm(L_('恭喜，您的银行卡已添加成功，订单支付已完成。为保障您的支付安全，请您设置支付密码'), { 
        buttons: [{
            label: L_('设置支付密码'),
            type: 'primary',
            onClick: function(){
                common.setCache('show_credit_pwd_tip',2);
                common.setCache('show_credit_pwd_url',location.href); 
				window.location.href = "/packapp/hake_credit_pay/setNewsPwd.html";   
                
            }
        }]
    }); 
    common.removeCache('show_credit_pwd_tip');

} 

// 
var bind_hasPwd=common.getCache('bind_hasPwd');
if(bind_hasPwd == 1){
    weui.confirm(L_('恭喜，您的银行卡已添加成功。'), { 
        buttons: [{
            label: L_('知道了'),
            type: 'primary',
            onClick: function(){  
                 // common.setCache('bind_hasPwd',0);
                common.removeCache('bind_hasPwd');

            }
        }]
    });
}
// close_dt  
$('.close_dt').click(function(){   
    var fristNum=common.getCache('fristNum');   
    if(fristNum == undefined){   
       openNum++;
       common.setCache('openNum',openNum); 
    } 
    $('.pay_sucess').hide();
})  

// 发券
var hd=$(window).height()-$('.alapcon').height();   
if(common.checkWeixin()){  
     $('.alapcon').css('margin-top',hd/3);
     $('.close').click(function(){
         $('.alapcon').hide();
         $('.alap').hide(); 
     })
     $('.releaseLottery').click(function(){
         $('.alapcon').show();
         $('.alap2').show();
     })
     
     //  抽奖
     $('.choujiang').click(function(){
         common.http('My&a=lottery_shop',{'order_id':urlParam.order_id},function(data){ 
             window.location.href=data.url; 
        }) 
     })
     
     
     // 发券  分享  
     $('.share_btn').click(function(){ 
         $('.alap2').hide();
         $('.alap3').show();
         $('.alap1').hide();  
     })
     $('.alap').click(function(){
         $(this).hide();
     })
} 


//判断明天
function getDayName(d){  
	var td=new Date();
	td=new Date(td.getFullYear(),td.getMonth(),td.getDate());
	var od=new Date(d);
	od=new Date(od.getFullYear(),od.getMonth(),od.getDate());
	var xc=(od-td)/1000/60/60/24;
	console.log(xc)
	if(xc == 0){
		//return L_('今天'); 
		return ''; 
	}else if(xc == 1){
		return L_('明天') + ' ';
	}else{
        if(map_config == 'google'){
            return od.getDate() +"/"+(od.getMonth()+1)+'/'+od.getFullYear() + ' ';
        }else{
            return od.getFullYear()+"/"+(od.getMonth()+1)+'/'+od.getDate() + ' ';
        }
	}
}

function shareConfig(share_coupon_show){
    if(common.checkWeixin()){
        is_init_share_coupon_data = true;
        common.http('Config&a=wx_config',{'work':'share_coupon',order_id:urlParam.order_id,'share_coupon_show':share_coupon_show,noTip: false,location_url:location.href.split('#')[0]},function(data){
            console.log('data',data)
            wx.config({
                debug:false,
                appId:data.appId,
                timestamp:data.timestamp,
                nonceStr:data.nonceStr,
                signature:data.signature,
                jsApiList:[
                    'checkJsApi',
                    'onMenuShareTimeline',
                    'onMenuShareAppMessage',
                    'onMenuShareQQ',
                    'onMenuShareWeibo',
                    'scanQRCode',
                    'previewImage',
                    'openLocation',
                    'getLocation',
                    'getNetworkType'
                ]
            });
            if(data.share){
                wx.ready(function(){ 
                    wx.showOptionMenu();
                    wx.onMenuShareAppMessage({
                        title: data.share.title,
                        desc:data.share.content,
                        link:data.share.url ,
                        imgUrl: data.share.image,
                        type: '',
                        dataUrl: '',
                        success: function(){
                            // motify.log('分享成功');
                        },
                        cancel: function(){
                            // motify.log('取消了分享');
                        }
                    });
                    wx.onMenuShareTimeline({
                        title: data.share.title,
                        desc:data.share.content,
                        link:data.share.url ,
                        imgUrl: data.share.image,
                        success: function(){
                            // motify.log('分享成功');
                        },
                        cancel: function(){
                            // motify.log('取消了分享');
                        }
                    });
                    wx.onMenuShareWeibo({
                        title: data.share.title,
                        desc:data.share.content,
                        link:data.share.url ,
                        imgUrl: data.share.image,
                        success: function(){
                            // motify.log('分享成功');
                        },
                        cancel: function(){
                            // motify.log('取消了分享');
                        }
                    });
                });

            }

        })
    }
}


 