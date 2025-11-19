var periodic_deliver_id=null
var all_goods = [];
$(document).ready(function(){ 
    common.setCache('order_id',urlParam.order_id, true);
  //修改金额
    $(document).on('click', '.amount', function(){
    	$('#order_id').val($(this).data('id'));
    	$('#change_price, #change_price_reason').val('');
    	$('#now_price').html($(this).data('price'));
        $(".mask,.amend").show();
    });
    $(document).on('click', '.button .section_recovery, .button .section_ensure', function(e){
    	e.stopPropagation();
        var type = $(this).data('type'), order_id = $('#order_id').val(), change_price_reason = $('#change_price_reason').val(), change_price = $('#change_price').val();
        if (change_price == '') {
        	motify.log(L_('金额不能为空'));
        	return false;
        }
        common.http('Storestaff&a=shopChangePrice',{'type':type, 'order_id':order_id, 'change_price':change_price, 'change_price_reason':change_price_reason, noTip:true}, function(data){
        	$('.change_' + order_id).html('' + L_('￥') + '' + data + ' <em class="xgh">'+ change_price_reason + '</em>');
            $('.modify_' + order_id).data('price', data);
            $(".mask,.amend").hide();
            location.reload();
        });
    });
      
    
    //确认消费
    $(document).on('click', '.mask, .seek .close, .cancel_confirm .close, .seek .del, .cancel_confirm .del', function(){
        $(".mask,.seek,.cancel_confirm").hide();
    });

    //顶部按钮
    $(window).scroll(function(){
        if($(this).scrollTop() > 200){
           $(".coping").show(); 
        }else{
           $(".coping").hide();  
        }
    });
    $(".coping .top").click(function(){
        $("body,html").animate({
            scrollTop: 0
        },500);
    });

	
	// 配送弹窗
	$(".flat").css({"top":($(window).height()-$(".flat").height())/2});
    $(".weidht_goods").css({"top": "15%"});
	
	
	//接单
	var confirmFlag = false;
    $(document).on('click', '.kd_rob', function(){
        if (confirmFlag) {
            return false;
        }
		
		//强制停止声音
		if (common.checkApp()) {
			if(common.checkAndroidApp()){
				window.pigcmspackapp.voicetip_stop();
			}else{
				common.iosFunction('voicetip_stop', {name:'voicetip_stop'});
			}
		}
        
        confirmFlag = true;
        var order_id = $(this).data('id');
        common.http('Storestaff&a=shopOrderEdit',{'status':1, 'order_id':order_id,'periodic_deliver_id':periodic_deliver_id, noTip:true}, function(data){
            confirmFlag = false;
        	location.reload();
        },function(err_data){
            confirmFlag = false;
            if(err_data.errorCode == 500005){
                layer.open({
                    title: L_('提示')
                    ,content: L_('该订单中有计重类商品，需要录入商品实际重量后才会将订单转至配送员')
                    ,btn: [L_('知道了')]
                    ,yes: function (index){
                        layer.close(index);
                    }
                    ,success: function (index) {
                        $('.layermbtn > span').css('color', '#40AFFE')
                    }
                });
            }else{
                motify.log(err_data.errorMsg);
            }
        });
    });
	 
	//发货到自提点
    $(document).on('click', '.send', function(){
        var order_id = $(this).data('id');
        common.http('Storestaff&a=shopOrderEdit', {'status':8, 'order_id':order_id,'periodic_deliver_id':periodic_deliver_id, noTip:true}, function(data){
        	location.reload();
        });
    });
	
	//确认消费弹窗提示
    $(document).on('click', '.sure', function(){
    	$('.div_ensure').data('id', $(this).data('id'))
    	$(".seek,.mask").show();
    });

    
	//快递配送时候
    $(document).on('click', '.express', function(){
        common.http('Storestaff&a=getExpress',{'order_id':$(this).data('id'), noTip:true}, function(data){
        	laytpl($('#expressTpl').html()).render(data, function(html){
    			$('.flat').html(html);
    		});
        	$(".flat,.mask").show();
        	$('.flat').css({"top":($(window).height()-$(".flat").height())/2});
        },function(err_data){
            if(err_data.errorCode == 500005){
                layer.open({
                    title: L_('提示')
                    ,content: L_('该订单中有计重类商品，需要录入商品实际重量后才会将订单转至配送员')
                    ,btn: [L_('知道了')]
                    ,yes: function (index){
                        layer.close(index);
                    }
                    ,success: function (index) {
                        $('.layermbtn > span').css('color', '#40AFFE')
                    }
                });
            }else{
                motify.log(err_data.errorMsg);
            }
        });
    });
    
    //确认消费
    var flag = false;
    $(document).on('click', '.div_ensure', function(){
        if(flag){
            return false;
        }
        flag = true;
        var obj = $(this);
        var order_id = $(this).data('id');
        obj.html(L_('消费中'))
        common.http('Storestaff&a=shopOrderEdit',{'status':2, 'order_id':order_id,'periodic_deliver_id':periodic_deliver_id, noTip:true}, function(data){
        	location.reload();
        	flag=false;
            obj.html(L_('确定消费'))
        },function(err_data){
            motify.log(err_data.errorMsg);
            flag=false;
            obj.html(L_('确定消费'))
        });
    });
    $(document).on('click', '.express_save', function(){
        var order_id = $(this).data('id'), express_id = $('#express_id').val(), express_number = $('#express_number').val();
        if (express_id.length < 1) {
        	motify.log(L_('请选择快递公司'));
        	return false;
        }
        if (express_number.length < 1) {
        	motify.log(L_('请填写快递单号'));
        	return false;
        }
        common.http('Storestaff&a=shopOrderEdit',{'status':1, 'order_id':order_id, 'express_number':express_number, 'express_id':express_id,'periodic_deliver_id':periodic_deliver_id, noTip:true}, function(data){
        	location.reload();
        });
    });
	
	//取消订单
    $(document).on('click', '.cancel', function(){
        $('.div_cancel').data('id', $(this).data('id'));

        var order_id = $(this).data('id');
        layer.open({
            title: L_('确认取消')
            ,content: L_('取消订单后，将退款给用户，操作后不可恢复')
            ,btn: [L_('确认'), L_('点错了')]
            ,yes: function (index){
                common.http('Storestaff&a=shopOrderEdit',{'status':5, 'order_id':order_id, noTip:true}, function(data){
                    location.reload();
                });
                layer.close(index);
            }
        });
    });
	
	//取消订单
    $(document).on('click', '.confirm_pay', function(){
        $('.div_cancel').data('id', $(this).data('id'));

        var order_id = $(this).data('id');
        layer.open({
            title: L_('确认支付')
            ,content: L_('确认支付后，订单将变成已支付')
            ,btn: [L_('确认'), L_('点错了')]
            ,yes: function (index){
                common.http('Storestaff&a=confirm_pay',{'order_id':order_id, noTip:true}, function(data){
                    location.reload();
                });
                layer.close(index);
            }
        });
    });
	
	//重打小票
    $(document).on('click', '.re_print', function(){
		var order_id = $(this).data('id');
		common.http('Storestaff&a=shop_re_print_order',{'order_id':order_id, noTip:true}, function(data){
        	motify.log(data);
        });
    });

    var cancelConfirmFlag = false;
    $(document).on('click', '.div_cancel', function(){
        if (cancelConfirmFlag) {
            return false;
        }
        cancelConfirmFlag = true;
        var order_id = $(this).data('id');
        common.http('Storestaff&a=shopOrderEdit',{'status':5, 'order_id':order_id, 'periodic_deliver_id':periodic_deliver_id,noTip:true}, function(data){
            cancelConfirmFlag = false;
            location.reload();
        });
    });
    
	//更换配送方式
    $(document).on('click', '.change_deliver', function(){
        common.http('Storestaff&a=mallOrderDetail',{'order_id':$(this).data('id'), noTip:true}, function(data){
        	laytpl($('#deliverTpl').html()).render(data, function(html){
    			$('.flat').html(html);
    		});
        	$(".flat,.mask").show();
        	$('.flat').css("margin-top",-$('.flat').height()/2);
        	$('#demo').scroller('destroy').scroller($.extend(opt['dateYMD'],opt['default']));
        });
    });
    $(document).on('click', '.con .sub.deliver_save', function(){
        common.http('Storestaff&a=checkDeliver',{'order_id':$(this).data('id'), 'expect_use_time':$('#demo').val(), noTip:true}, function(data){
        	location.reload();
        });
    });
    
    //------------pick address-------------------
    $(document).on('click', '.kd_since', function(e){
    	e.stopPropagation();
    	common.http('Storestaff&a=getPickAddress', {'order_id':$(this).data('id'), noTip:true}, function(data){
    		laytpl($('#pcikTpl').html()).render(data, function(html){
    			$('.since').html(html);
    			$(".mask, .since").show();
    			$('.since').css("margin-top", -$('.since').height()/2);
    			new IScroll('.since .ul',{ click: true});
            });
    	});
    });
    $(document).on('click', '.since li', function(e){
    	e.stopPropagation();
        $(this).addClass("on").siblings().removeClass("on");
    });
    $(document).on('click', '.determine', function(e){
    	e.stopPropagation();
    	var pick_id = $('.since').find('.on').data('id'), order_id = $(this).data('id');
    	if (pick_id == undefined) {
    		motify.log(L_('请选择自提点'));
    		return false;
    	}
    	common.http('Storestaff&a=pick', {'order_id':$(this).data('id'), 'pick_id':pick_id, noTip:true}, function(data){
    		if (data == 'SUCCESS') {
    			location.reload();
    		}
    		$('.mask, .since').hide();
    	});
    });
    //退款
    $(document).on('click', '.refund', function(e){
        var refund_id = $(this).data('refund_id'), type = $(this).data('type'), order_id = $(this).data('id');
        var reply_content = $(this).parent('dd').find('input[name="reply_content"]').val();
        common.http('Storestaff&a=replyRefund', {'order_id':order_id, 'refund_id':refund_id, 'reply_content':reply_content, 'type':type}, function(response){
            if (response.code == 1) {
                layer.msg(response.msg);
            } else {
                location.reload();
            }
        });
    });
    
	$(document).on('click', '.mask, .del', function(){
        $('.weidht_goods, .mask, .flat, .since, .amend,.cancel_confirm').hide();
    });

//插件日历
    var currYear = new Date().getFullYear();
    var opt = {  
        'dateYMD': {
            preset: 'datetime',
            dateFormat: 'yyyy-mm-dd',
//            theme: 'android-ics light', //皮肤样式
            display: 'modal',           //显示方式
            mode: 'scroller',           //日期选择模式
//            showNow: true,
//            nowText: "现在",
            startYear: currYear,    //开始年份
//            endYear: currYear + 1, //结束年份
            minDate: new Date()    //只能选择
        },'select': {
            preset: 'select'
        }
    };
    $(document).on('click', '.refund_img', function(){
        $(this).toggleClass('imgmax');
        $(this).toggleClass('imgmin');
    });
});
init();
var noTip=false; 
 function init(){  
            common.http('Storestaff&a=shopDetail', {'order_id':urlParam.order_id,noTip:noTip}, function(data){ 
            console.log(data)
            //var headerHtml = '<a class="link-url" data-nobtn="true" data-url="shop_order_log.html?order_id=' + urlParam.order_id + '" data-webview="true">';
            var headerHtml = '<a class="link-url" data-nobtn="true" data-url="shop_order_log.html?order_id=' + data.order_details.order_id + '" data-webview="true">';

                headerHtml += data.order_details.last_status.html;
                /**
                if (data.order_details.orderStatus.status == 0) {
                headerHtml += '<h2>'+ L_('订单生成成功') + '</h2> <p>'+ L_('订单编号') + '：' + data.order_details.real_orderid + '</p>';
            } else if (data.order_details.orderStatus.status == 1) {
                headerHtml += '<h2>'+ L_('订单支付成功') + '</h2> <p>'+ L_('订单编号') + '：' + data.order_details.real_orderid + '</p>';
            } else if (data.order_details.orderStatus.status == 2) {
                if (data.order_details.is_pick_in_store == 3) { 
                    headerHtml += '<h2>'+ L_('店员已发货') + '</h2> <p>已发货给快递公司<strong style="color:red">【' + data.order_details.express_name + '】</strong>，快递单号:<strong style="color:green">' + data.order_details.express_number + '</strong></p>';
                } else {
                   headerHtml += '<h2>'+ L_('店员接单') + '</h2> <p>'+ L_('正在为您准备商品') + '</p>';
                }
            } else if (data.order_details.orderStatus.status == 3) {
                headerHtml += '<h2>'+ L_('配送员接单') + '</h2> <p>'+ L_('配送员正在赶往店铺取货') + '</p>';
            } else if (data.order_details.orderStatus.status == 4) {
                headerHtml += '<h2>'+ L_('配送员取货') + '</h2> <p>'+ L_('已取货，准备配送，请耐心等待') + '</p>';
            } else if (data.order_details.orderStatus.status == 5) {
                headerHtml += '<h2>'+ L_('配送员配送中') + '</h2> <p>'+ L_('配送员正快速向您靠拢，请耐心等待！') + '</p>';
            } else if (data.order_details.orderStatus.status == 6) {
                headerHtml += '<h2>'+ L_('订单已完成') + '</h2> <p>';
                if (data.order_details.is_pick_in_store < 2) {
                    headerHtml += ''+ L_('配送员已完成配送，欢迎下次光临！') + '';
                } else {
                    headerHtml += ''+ L_('订单编号') + '：' + data.order_details.real_orderid;
                }
                headerHtml += '</p>';
            } else if (data.order_details.orderStatus.status == 7) {
                if (data.order_details.is_pick_in_store == 3) { 
                    headerHtml += '<h2>'+ L_('店员已发货') + '</h2> <p>已发货给快递公司<strong style="color:red">【' + data.order_details.express_name + '】</strong>，快递单号:<strong style="color:green">' + data.order_details.express_number + '</strong></p>';
                } else {
                    headerHtml += '<h2>'+ L_('店员验证消费') + '</h2> <p>'+ L_('订单改成已消费') + '</p>';
                }
            } else if (data.order_details.orderStatus.status == 8) {
                headerHtml += '<h2>'+ L_('完成评论') + '</h2> <p>'+ L_('您已完成评论，谢谢您提出宝贵意见！') + '</p>';
            } else if (data.order_details.orderStatus.status == 9) {
                headerHtml += '<h2>'+ L_('已完成退款') + '</h2> <p>';
                if (data.order_details.orderStatus.note.length < 1) {
                    headerHtml += ''+ L_('您已完成退款') + ''; 
                } else {
                    headerHtml += data.order_details.orderStatus.note;
                }
                headerHtml += '</p>';
            } else if (data.order_details.orderStatus.status == 10) {
                headerHtml += '<h2>'+ L_('已取消订单') + '</h2> <p>';
                if (data.order_details.orderStatus.note.length < 1) {
                    headerHtml += ''+ L_('您已经取消订单') + ''; 
                } else {
                    headerHtml += data.order_details.orderStatus.note;
                }
                headerHtml += '</p>';
            } else if (data.order_details.orderStatus.status == 11) {
                headerHtml += '<h2>'+ L_('商家分配自提点') + '</h2> <p>'+ L_('店员给您分配了自提点') + '</p>';
            } else if (data.order_details.orderStatus.status == 12) {
                headerHtml += '<h2>'+ L_('商家发货到自提点') + '</h2> <p>'+ L_('店员已经给您发货到配送点') + '</p>';
            } else if (data.order_details.orderStatus.status == 13) {
                headerHtml += '<h2>'+ L_('自提点已接货') + '</h2> <p>'+ L_('自提点已经接到您的货物了') + '</p>';
            } else if (data.order_details.orderStatus.status == 14) {
                headerHtml += '<h2>'+ L_('自提点已发货') + '</h2> <p>'+ L_('自提点已经给您发货了') + '</p>';
            } else if (data.order_details.orderStatus.status == 15) {
                headerHtml += '<h2>'+ L_('您在自提点取货') + '</h2> <p>'+ L_('您在自提点已经把您的货提走了！') + '</p>';
            } else if (data.order_details.orderStatus.status == 30) {
                headerHtml += '<h2>'+ L_('店员为您修改了价格') + '</h2> <p>'+ L_('店员已将订单的总价修改成') + '' + data.order_details.orderStatus.note + '</p>';
            } else if (data.order_details.orderStatus.status == 31) {
                headerHtml += '<h2>'+ L_('配送员放弃配送') + '</h2> <p>' + data.order_details.orderStatus.note + '</p>';
            } else if (data.order_details.orderStatus.status == 34) {
                headerHtml += '<h2>'+ L_('订单已被取消') + '</h2> <p>' + L_('已为用户退款！') + '</p>';
            } else if (data.order_details.orderStatus.status == 35) {
                headerHtml += '<h2>'+ L_('配送员接单') + '</h2> <p>' + L_('配送员正在赶往店铺取货') + '</p>';
            } else if (data.order_details.orderStatus.status == 36) {
                headerHtml += '<h2>'+ L_('确认收货') + '</h2> <p>' + data.order_details.orderStatus.note + '</p>';
            }**/
            headerHtml += '<div class="time">' + data.order_details.orderStatus.dateline + '</div>';
            headerHtml += '<em>'+ L_('更多状态') + '</em>';
            headerHtml += '</a>';
            $('.defrayal .defrayal_n').html(headerHtml);
        	laytpl($('#allTpl').html()).render(data, function(html){
        		$('.g_details').html(html);
        		$(".kd_dl .hx").height($(".kd_dl").height()-65);
        	});
            periodic_deliver_id=data.order_details.periodic_deliver_id;  
            //计重订单才能设置实际重量
            if (data.order_details.is_weight == 1) {
                $('.set_real_weight').show();
            } else {
                $('.set_real_weight').hide();
            }
            for(i in data.info){
                all_goods = all_goods.concat(data.info[i].list);
            }
            if(data.order_details.periodic_list.length>3){ 
                console.log('1111')
                $('.progress-detail').css('height',$('.progress-detail .item').height()*4);
                $('.btn_xiala').show(); 
            }
           
        }); 
        noTip=true;
        // setTimeout("init()",5000);   
    }
    $(document).on('click', '.agree_cancle_apply', function(){
        var id=$(this).data('id');
        layer.open({
            title:['提示','background-color:#2ecc71;color:#fff;'],
            content:'是否同意申请退款，同意后不可恢复',
            btn: ['确定','取消'],
            yes: function(index){
                common.http('Storestaff&a=agree_cancle_apply', {'id':id,noTip:true}, function(data){
                    console.log('data',data);
                    if(data.status==1){
                        layer.open({
                            content: data
                            ,skin: 'msg'
                            ,anim: 'up'
                            ,time: 2
                        });
                        window.location.reload();
                    }else{
                        layer.open({
                            content: '退款成功！'
                            ,skin: 'msg'
                            ,anim: 'up'
                            ,time: 5
                        });
                        window.location.reload();
                    }
                })
            }
        });

        
    })
    $(document).on('click', '.mall_confirm', function(e){
        //强制停止声音


        var order_id = $(this).data('id'), _this = $(this);
        common.http('Storestaff&a=take_order',{'order_id':order_id}, function(data){
            //confirmFlag = false;
            //_this.removeClass('kd_rob confirm').addClass('kd_order').html(L_('已接单'));
            console.log(data);
            location.reload();
        });
    });
    // type:申请（reply）或者取消（cancle）延期 
    $(document).on('click', '.delay_submit', function(){ 
        var id=$(this).data('id'); 
        var type = $(this).data('type');
        if(type=='reply'){
            var tip_str = '<p style="width:180px;color:#ff372d;">确认延期该周期购吗？延期后当前周期不会发货！</p>';
        }else{
            var tip_str = '<p style="width:180px;color:#ff372d;">确认取消该延期吗？取消后可恢复配送</p>';
        }
        layer.open({
            title:['提示','background-color:#2ecc71;color:#fff;'],
            content:tip_str,
            btn: ['确定','取消'],
            yes: function(index){
                layer.close(index);
                common.http('Storestaff&a=periodic_delay', {'id':id,'type':type,noTip:true}, function(data){ 
                    console.log('data',data);
                    if(data.status==1){
                        layer.open({
                            content: '操作成功！'
                            ,skin: 'msg'
                            ,anim: 'up'
                            ,time: 2
                        }); 
                        window.location.reload();
                    }else{
                        layer.open({
                            content: data
                            ,skin: 'msg'
                            ,anim: 'up'
                            ,time: 2
                        });
                        window.location.reload();
                    } 
                })
               
            }
        });
        
    })
    $(document).on('click', '.orderStatus', function(e){ 
        e.stopPropagation();
        e.preventDefault();
        $(this).next().show();
        $(".coupon_mask").show()
    })
    $(document).on('click', '.close', function(e){  
        e.stopPropagation();
        e.preventDefault();
        $(".coupon_mask,.status_model").hide()
    }) 
  
    $(document).on('click', '.btn_xiala', function(){ 
        $('.progress-detail').css('height','auto');
        $('.btn_xiala img').css('transform', 'rotate(900deg)');
        $('.btn_shangla').show();
        $('.btn_xiala').hide(); 
     }) 
       
    
    $(document).on('click', '.btn_shangla', function(){ 
        $('.progress-detail').css('height',$('.progress-detail .item').height()*4);
        $('.btn_xiala img').css('transform', 'rotate(360deg)');
        $('.btn_shangla').hide();
        $('.btn_xiala').show();
     })

    $(document).on('click', '.set_real_weight', function(){
        laytpl($('#weightGoodsTpl').html()).render({order_id:urlParam.order_id,goods:all_goods}, function(html){
            $('.weidht_goods').html(html);
            $(".weidht_goods,.mask").show();
        });
    })

    $(document).on('click', '.real_weight_save', function () {
        var detail_id = [];
        var real_weight = [];
        var order_id = $(this).data('id')
        $('.detail_id_input').each(function () {
            detail_id.push($(this).val())
        })
        $('.real_weight_input').each(function () {
            real_weight.push($(this).val())
        })
        var data = {
            "detail_id": detail_id,
            "real_weight": real_weight,
            "order_id": order_id
        }
        common.http('Storestaff&a=write_weight',data, function(data){
            location.reload();
        });
    })

   