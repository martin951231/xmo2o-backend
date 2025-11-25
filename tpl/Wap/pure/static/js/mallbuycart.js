var goodsCart = [], goodsNumber = 0, cookie_index = 'mall_goods_cart', cookie_buy_index = 'buy_mall_goods_cart', goodsCartMoney = 0, goods_price_list = [], goods_index_list = [], goodsCartPackCharge = 0;

var cart_cookie = '';
//购物车COOKIE标识
function randomString(len) {
    len = len || 32;
    var $chars = 'ABCDEFGHJKMNPQRSTWXYZabcdefhijkmnprstwxyz2345678oOLl9gqVvUuI1';
    var maxPos = $chars.length;
    var pwd = '';
    for(i = 0; i < len; i++) {
        pwd += $chars.charAt(Math.floor(Math.random() * maxPos));
    }
    return pwd;
}
cart_cookie = $.cookie('cart_cookie');
if(!cart_cookie){
    $.cookie('cart_cookie',randomString(32),{expires:365,path:'/'});
    cart_cookie = $.cookie('cart_cookie');
}

var goodsCart = [], goodsNumber = 0, goodsCartMoney = 0, goods_price_list = [], goods_index_list = [], goodsCartPackCharge = 0;
$(document).ready(function(){
	if (!$('.footerMenu').height()) {
		$('.endcar').css('padding-bottom', '62px');
		$('.carbot').css('bottom', '0');
	}
	
	//购物车选中 全选
	$("dt .piton span").click(function(){
		if ($(this).hasClass("on")) {
			$(this).removeClass("on");
			$(this).parents("dt").siblings("dd").find(".piton span").removeClass("on");
		} else {
			if ($(this).parents('li').siblings('li').find('span.on').size() > 0) {
				motify.log('暂时无法支持跨店支付！');
				return false;
			} else {
				$(this).addClass("on");
				$(this).parents("dt").siblings("dd").find(".piton span").addClass("on");
				$('.carbot .carbot_right .close').data('store_id', $(this).data('store_id'));
			}
		}
		count_price();
	});
	//单个选择
	$("dd .piton span").click(function(){
		if ($(this).hasClass("on")) {
			$(this).removeClass("on");
			$(this).parents('li').find('dt span').removeClass("on");
		} else {
			if ($(this).parents('li').siblings('li').find('span.on').size() > 0) {
				motify.log('暂时无法支持跨店支付！');
				return false;
			}
			$(this).addClass("on");
			var is_all = true;
			$(this).parents('li').find('dd .piton span').each(function(){
				if (!$(this).hasClass("on")) {
					is_all = false;
				}
			});
			if (is_all) $(this).parents('li').find('dt span').addClass("on");
			$('.carbot .carbot_right .close').data('store_id', $(this).data('store_id'));
		}
		count_price();
	});

	$(".whole span").click(function(){
		if ($(this).hasClass("on")) {
			$(this).removeClass("on");
			$(".piton span").removeClass("on"); 
		} else {
			$(this).addClass("on");
			$(".piton span").addClass("on");
			$(".carbot_right .whole").text("删除").css("background","none") 
		}
	});

	$('.carbot .carbot_right .close').click(function(){
		if ($('dd .piton span.on').size() < 1) {
			motify.log('请选择您要买单的商品！');
			return false;
		} else {
			var cookieProductCart = [];
			var mall_cart_ids = [];
			$("dd .piton span.on").each(function(){
				var this_index = format_cart_data($(this).data('index_key'));
				if (this_index != null) {
					cookieProductCart.push(goodsCart[this_index]);
				}
				 mall_cart_ids.push($(this).data('mall_cart_id'));
			});
            $.cookie('mall_cart_ids',JSON.stringify(mall_cart_ids));
			
		   // var cookieProductCartObj = {};
		   //  for(var i in cookieProductCart){
		   //      var k = Math.floor(i/5);
		   //      if(!cookieProductCartObj[k]){
		   //          cookieProductCartObj[k] = [];
		   //      }
		   //      cookieProductCartObj[k].push(cookieProductCart[i]);
		   //  }
		    // for(var i in cookieProductCartObj){
		    //     $.cookie(cookie_buy_index + '_' + i, JSON.stringify(cookieProductCartObj[i]),{expires:700,path:'/'});
		    // }
            $.cookie('mall_buy_type','add');
//			$.cookie(cookie_buy_index, JSON.stringify(cookieProductCart), {expires:700,path:'/'});
			
//			window.localStorage.setItem(cookie_buy_index, JSON.stringify(cookieProductCart));//, {expires:700,path:'/'});
			var url = save_url + '&store_id=' + $(this).data('store_id');
//          location.href = save_url + '&store_id=' + $(this).data('store_id') + '&params=' + JSON.stringify(cookieProductCart);
			if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
				url = 'http://' + document.domain + url;
			}
			if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
				
				var reg = /versioncode=(\d+),/;
				var arr = reg.exec(navigator.userAgent.toLowerCase());
				if(arr == null){
					
				}else{
					var version = parseInt(arr[1]);
					if(version >= 1400){
						if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
							var iosUrl = window.btoa(url);
							iosUrl = iosUrl.replace('/','&');
							window.webkit.messageHandlers.appJSBridgeMethod.postMessage({name:'createwebview', url:iosUrl, webUrl: url});
							return false;
						}else{
							window.lifepasslogin.createwebview(url);
							return false;
						}
					}
				}
			}
			location.href = url;
		}
	});
	var h=$(window).height();
	$(".mask").height(h);
	$(".carxq_ul").css("max-height",h-200);

	//购物单
	$(".total_end").click(function(){
		$(".carxq").show();
		$(".mask").show();
	});
	$(".shut").click(function(){
		$(this).parents(".carxq").hide();
		$(".mask").hide();
	});


	//加减号的操作
	$(document).on('click', '.plus em', function() {
		var index_key = $(this).data('index_key'), store_id = $(this).data('store_id'), price = $(this).data('price'), stock = $(this).data('stock'),extra_pay_price = $(this).data('extra_pay_price');
		var this_index = format_cart_data(index_key);
		if (this_index != null) {
			if ($(this).attr('class') == 'jia') {
				this_num = goodsCart[this_index].count + 1;
				if (stock != -1 && this_num > stock) {
					motify.log('库存不足，不能购买！');
					return false;
				}
			} else {
				this_num = goodsCart[this_index].count - 1;
				if (this_num < 1) {
					motify.log('不能再减了');
					return false;
					if ($('#' + index_key).parents('li').find('.goods').size() > 1) {
						$('#' + index_key).remove();
					} else {
						$('#' + index_key).parents('li').remove();
					}
				}
			}
			$(this).parents('.plus').find('input').val(this_num);
			goodsCart[this_index].count = this_num;
			goodsCart[this_index].productStock = stock;
			goodsCart[this_index].productPrice = price;
			goodsCart[this_index].extra_pay_price = extra_pay_price;
			
				
			$(this).parents('li').find('.total_top span').text(stringifyCart(store_id));
			
			count_price();
		}
	});

	
	$('.carbot .carbot_right .whole').click(function(){
		if ($('dd .piton span.on').size() < 1) {
			motify.log('请选择您要删除的商品！');
			return false;
		} else {
			var flag=true;
			layer.open({
				content:'确认删除选中的商品！',
				btn:['确定','取消'],
				yes:function(index){
					$('dd .piton span.on').each(function(){
						var index_key = $(this).data('index_key'), store_id = $(this).data('store_id');
						var this_index = format_cart_data(index_key);
						var pobj=$(this).parents('li');
						if (this_index != null) {
								if ($('#' + index_key).parents('li').find('.goods').size() > 1) {
									$('#' + index_key).remove();
								} else {
									$('#' + index_key).parents('li').remove();
		                            $.post(ajax_cart_save_url,{cart_cookie:cart_cookie,store_id:store_id},function(){

		                            });
								}
							setTimeout(function () {
								window.location.reload();
							},500);
							$(this).parents('.plus').find('input').val(0);
							goodsCart[this_index].count = 0;
							pobj.find('.total_top span').text(stringifyCart(store_id));
						}
					});
					if ($('.endcar').find('.goods').size() > 0) {
						$('.stroll').hide();
					} else {
						$('.stroll').show();
					}
					count_price();
					layer.close(index);
					if($('dd .piton span').size()<1){
						$('.noDataCart').show();
					}
					
				},
				no:function(){
					console.log('取消！');
				}
			});
			return false;			
		}
	});

    $.post(ajax_cart_url,{cart_cookie:cart_cookie},function(result){
        if(!result.status || result.status == 0){
            result.info = [];
        }
        init_goods_menu(result.info);
        count_price();
    });
	// init_goods_menu();
	// count_price();
});

function format_cart_data(index)
{
	var this_index = null;
	for (var i in goodsCart) {
		if (goodsCart[i].count > 0) {
			var old_goodsCartKey = 's_' + goodsCart[i].store_id + '_g_' + goodsCart[i].productId;
			if (goodsCart[i]['productParam'].length) {
				for (var pi in goodsCart[i]['productParam']) {
					if (goodsCart[i]['productParam'][pi].type == 'spec') {
						old_goodsCartKey += '_s_' + goodsCart[i]['productParam'][pi].id;
					} else {
						if (goodsCart[i]['productParam'][pi]['data'] && goodsCart[i]['productParam'][pi]['data'].length) {
							for (var di in goodsCart[i]['productParam'][pi]['data']) {
								old_goodsCartKey += '_v_' + goodsCart[i]['productParam'][pi]['data'][di].id;
							}
						}
					}
				}
			}
			if (index == old_goodsCartKey) {
				this_index = i;
				break;
			}
		}
	}
	return this_index;
}

function count_price()
{
	var total_price = 0;
	var extra_total_price = 0;
	var total_account_score=0
	$("dd .piton span.on").each(function(){
		var this_index = format_cart_data($(this).data('index_key'));
		if (this_index != null) {
		    if (goodsCart[this_index].maxNum > 0 && goodsCart[this_index].count > goodsCart[this_index].maxNum) {
		         
			   if(goodsCart[this_index].price_type == 0 || goodsCart[this_index].price_type == 1){
			     total_price += parseFloat(goodsCart[this_index].maxNum * goodsCart[this_index].productPrice);
			     total_price += parseFloat((parseInt(goodsCart[this_index].count) - parseInt(goodsCart[this_index].maxNum)) * goodsCart[this_index].oldPrice);
			   		       
			   }else{
			   	total_price += parseFloat(goodsCart[this_index].maxNum * goodsCart[this_index].account_money);
			   	total_price += parseFloat((parseInt(goodsCart[this_index].count) - parseInt(goodsCart[this_index].maxNum)) * goodsCart[this_index].account_money);
			   	total_account_score += parseFloat(goodsCart[this_index].count * goodsCart[this_index].account_score)
			   	total_account_score += parseFloat((parseInt(goodsCart[this_index].count) - parseInt(goodsCart[this_index].maxNum)) * goodsCart[this_index].account_score);
			    	 
			   }
			   
			} else { 
				if(goodsCart[this_index].price_type == 0 || goodsCart[this_index].price_type == 1){
					total_price += parseFloat(goodsCart[this_index].count * goodsCart[this_index].productPrice);
				}else{
					total_price += parseFloat(goodsCart[this_index].count * goodsCart[this_index].account_money); 
					total_account_score += parseFloat(goodsCart[this_index].count * goodsCart[this_index].account_score)
				}
		    }
			
	
			extra_total_price+=parseFloat(goodsCart[this_index].count * goodsCart[this_index].extra_pay_price);
		}
	});
	total_price = parseFloat(total_price.toFixed(2));
	if(open_extra_price==1&&extra_total_price>0){
		if(total_account_score>0){
			$('.carbot .carbot_left .close').text('合计:'+currency_symbol + total_price+'+'+extra_total_price+extra_price_name);
		}else{ 
			$('.carbot .carbot_left .close').text('合计:'+currency_symbol + total_price+'+'+extra_total_price+extra_price_name+'+'+total_account_score+score_name);
		}
		
	}else{ 
		if(total_account_score>0){
			$('.carbot .carbot_left .close').text('合计:'+currency_symbol + total_price+'+'+total_account_score+score_name);
		}else{
			$('.carbot .carbot_left .close').text('合计:'+currency_symbol + total_price);
			
		}
		
		
	}
	return total_price;
}


function stringifyCart(store_id)
{
	var cookieProductCart = [], total_price = 0,extra_price=0,total_account_score=0;
    for(var i = 0; i<40; i++){
        $.cookie(cookie_index + '_' + i, null);
    }
    
	for(var i in goodsCart){
		if (goodsCart[i].count > 0) {
			cookieProductCart.push(goodsCart[i]);
			if (goodsCart[i].store_id == store_id ) {
			    if (goodsCart[i].maxNum > 0 && goodsCart[i].count > goodsCart[i].maxNum) {
					if(goodsCart[i].price_type == 0 || goodsCart[i].price_type == 1){
						total_price += parseFloat(goodsCart[i].maxNum * goodsCart[i].productPrice);
						total_price += parseFloat((parseInt(goodsCart[i].count) - parseInt(goodsCart[i].maxNum)) * goodsCart[i].oldPrice);
					}else{
						total_price += parseFloat(goodsCart[i].maxNum * goodsCart[i].account_money);
						total_price += parseFloat((parseInt(goodsCart[i].count) - parseInt(goodsCart[i].maxNum)) * goodsCart[i].account_money);
						total_account_score += parseFloat(goodsCart[i].count * goodsCart[i].account_score)
						total_account_score += parseFloat((parseInt(goodsCart[i].count) - parseInt(goodsCart[i].maxNum)) * goodsCart[i].account_score);
					 	 
					}
	                
	            } else {
					if(goodsCart[i].price_type == 0 || goodsCart[i].price_type == 1){
						total_price += parseFloat(goodsCart[i].count * goodsCart[i].productPrice);
					}else{ 
						total_price += parseFloat(goodsCart[i].count * goodsCart[i].account_money);
						total_account_score += parseFloat(goodsCart[i].count * goodsCart[i].account_score);
					}
	                
	            }
				extra_price += parseFloat(goodsCart[i].count * goodsCart[i].extra_pay_price);
			}
		}
	}
	console.log(total_account_score)
	if(extra_price>0&&open_extra_price){
		if(total_account_score >0){ 
			 total_price=total_price+'+'+extra_price+extra_price_name+'+'+total_account_score+score_name;
		}else{
			total_price=total_price+'+'+extra_price+extra_price_name;
		}
		 
		
	}
    $.post(ajax_cart_save_url,{cart_cookie:cart_cookie,cookieProductCart:cookieProductCart},function(){

    });
   // var cookieProductCartObj = {};
   //  for(var i in cookieProductCart){
   //      var k = Math.floor(i/5);
   //      if(!cookieProductCartObj[k]){
   //          cookieProductCartObj[k] = [];
   //      }
   //      cookieProductCartObj[k].push(cookieProductCart[i]);
   //  }
   //  for(var i in cookieProductCartObj){
   //      $.cookie(cookie_index + '_' + i, JSON.stringify(cookieProductCartObj[i]),{expires:700,path:'/'});
   //  }
	    
//	$.cookie(cookie_index, JSON.stringify(cookieProductCart), {expires:700,path:'/'});
	
//	window.localStorage.setItem(cookie_index, JSON.stringify(cookieProductCart));
   if(total_account_score>0){
	  total_price = currency_symbol+parseFloat(total_price.toFixed(2))+'+'+total_account_score+score_name; 
   }else{
	   total_price = currency_symbol+parseFloat(total_price.toFixed(2))
   }
	
	return total_price;
}



function init_goods_menu(nowShopCart)
{
    // var nowShopCart = '';//$.parseJSON(window.localStorage.getItem(cookie_index));//$.parseJSON($.cookie(cookie_index));
    // var tmpShopCart = [];
    // for (var i = 0; i < 40; i++) {
    //     var tmp = $.cookie(cookie_index + '_' + i);
    //     if (tmp) {
    //         tmpShopCart = tmpShopCart.concat($.parseJSON(tmp));
    //     } else {
    //         break;
    //     }
    // }
    // nowShopCart = tmpShopCart;
    console.log(nowShopCart);
	goodsCart = [];
	var cart_goods_html = '', total_prices = [],extra_prices = [],total_account_score = [];
	for (var i in nowShopCart) {
		if (nowShopCart[i] != null && nowShopCart[i].count > 0) {
			var detail_name = '', goodsCartKey = 's_' + nowShopCart[i].store_id + '_g_' + nowShopCart[i].productId;
			if (total_prices[nowShopCart[i].store_id] != undefined) {
			    
			    if (nowShopCart[i].maxNum > 0 && nowShopCart[i].count > nowShopCart[i].maxNum) {
					if(nowShopCart[i].price_type == 0 || nowShopCart[i].price_type == 1){
						total_prices[nowShopCart[i].store_id] += parseFloat(nowShopCart[i].maxNum * nowShopCart[i].productPrice);
						total_prices[nowShopCart[i].store_id] += parseFloat((parseInt(nowShopCart[i].count) - parseInt(nowShopCart[i].maxNum)) * nowShopCart[i].oldPrice);
					     
					}else{
						total_prices[nowShopCart[i].store_id] += parseFloat(nowShopCart[i].maxNum * nowShopCart[i].account_money);
						total_prices[nowShopCart[i].store_id] += parseFloat((parseInt(nowShopCart[i].count) - parseInt(nowShopCart[i].maxNum)) * nowShopCart[i].account_money);
					    if(total_account_score.length != 0){
					    	total_account_score[nowShopCart[i].store_id] += parseFloat(nowShopCart[i].count * nowShopCart[i].account_score); 
					    }else{
					    	total_account_score[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].account_score);
					    }
						
					} 
				   
				} else { 
					if(nowShopCart[i].price_type == 0 || nowShopCart[i].price_type == 1){
						total_prices[nowShopCart[i].store_id] += parseFloat(nowShopCart[i].count * nowShopCart[i].productPrice);
						 
					}else{ 
						total_prices[nowShopCart[i].store_id] += parseFloat(nowShopCart[i].count * nowShopCart[i].account_money);  
						 
						if(total_account_score.length != 0){
							total_account_score[nowShopCart[i].store_id] += parseFloat(nowShopCart[i].count * nowShopCart[i].account_score); 
						}else{
							total_account_score[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].account_score);
						}
						
					}
					
                }
				extra_prices[nowShopCart[i].store_id] += nowShopCart[i].count * nowShopCart[i].extra_pay_price;
			} else {
			    if (nowShopCart[i].maxNum > 0 && nowShopCart[i].count > nowShopCart[i].maxNum) {
                    if(nowShopCart[i].price_type == 0){
						total_prices[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].maxNum * nowShopCart[i].productPrice);
						total_prices[nowShopCart[i].store_id] += parseFloat((parseInt(nowShopCart[i].count) - parseInt(nowShopCart[i].maxNum)) * nowShopCart[i].oldPrice);
					    
					 }else{
						total_prices[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].maxNum * nowShopCart[i].account_money);
						total_prices[nowShopCart[i].store_id] += parseFloat((parseInt(nowShopCart[i].count) - parseInt(nowShopCart[i].maxNum)) * nowShopCart[i].account_money);
						total_account_score[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].account_score);
					}
				} else {
					if(nowShopCart[i].price_type == 0){
						total_prices[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].productPrice);
						 
					}else{ 
						total_prices[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].account_money); 
						total_account_score[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].account_score);
						console.log(total_account_score)
					}
                    
                }
			    
//				total_prices[nowShopCart[i].store_id] = parseFloat(nowShopCart[i].count * nowShopCart[i].productPrice);
				extra_prices[nowShopCart[i].store_id] = nowShopCart[i].count * nowShopCart[i].extra_pay_price;
			}
			if (nowShopCart[i]['productParam'].length) {
				for (var pi in nowShopCart[i]['productParam']) {
					if (nowShopCart[i]['productParam'][pi].type == 'spec') {
						goodsCartKey += '_s_' + nowShopCart[i]['productParam'][pi].id;
					} else {
						if (nowShopCart[i]['productParam'][pi]['data'] && nowShopCart[i]['productParam'][pi]['data'].length) {
							for (var di in nowShopCart[i]['productParam'][pi]['data']) {
								goodsCartKey += '_v_' + nowShopCart[i]['productParam'][pi]['data'][di].id;
								if (detail_name.length > 0) {
									detail_name += ',' + nowShopCart[i]['productParam'][pi]['data'][di].name
								} else {
									detail_name += nowShopCart[i]['productParam'][pi]['data'][di].name;
								}
							}
						}
					}
				}
			}
//			goodsNumber += parseInt(nowShopCart[i].count);
			goodsCart[i] = nowShopCart[i];
		}
	}

	for (var t in total_prices) {
		if(extra_prices[t]>0&&open_extra_price){  
			 if(total_account_score.length>0){
				 for(var j in total_account_score){
				 	$('#this_total_' + t).text(currency_symbol+ total_prices[t]+'+'+extra_prices[t]+extra_price_name+'+'+total_account_score[j]+score_name);
				 }
			 }else{
				 $('#this_total_' + t).text(currency_symbol+ total_prices[t]+'+'+extra_prices[t]+extra_price_name)
			 }  
		}else{
			 // $('#this_total_' + t).text(currency_symbol + parseFloat(total_prices[t].toFixed(2)))
			if(total_account_score.length>0){
			 for(var j in total_account_score){
				$('#this_total_' + t).text(currency_symbol+  parseFloat(total_prices[t].toFixed(2))+'+'+total_account_score[j]+score_name);
			 }
			}else{
				$('#this_total_' + t).text(currency_symbol + parseFloat(total_prices[t].toFixed(2)))
			}  
			
		}
	}
//	if (goodsNumber > 0) $('.Number').text(goodsNumber);
}

// 猜你喜欢
mayLove()
function mayLove(){
	var html='' 
	$.post(guess_you_like,{},function(data){
	//console.log(data)
	var loveList = JSON.parse(data) 
	//console.log(loveList);
 
   if(loveList.length>0){
	   $('.may_til').show();
	   for(let i=0;i<loveList.length;i++){
	   	html+= '<li class="w47 ui-border-t tjt_border_t love_listLi" >'
	   	  	html+= '<a href="'+loveList[i].link_url+'" target="_blank" app_target_blank="_blank">'
	   	  		html+= '<div class="pt0 pb0  bg_f  ui-border-l tjt_border_l  ">'
	   	  			html+= '<div class="ui-border-r tjt_border_r">'
	   	  				html+= '<div class="ui-img-vertical">'
	   	  					html+= '<span class="pd0">'
	   	  						html+= '<img src="'+loveList[i].image_url+'" style="width:100%;height:176.25px">'
									   if(loveList[i].book_record == 1){
										   html+= '<div class="tip_yu">预售</div>'
									   }
	   	  					html+= '</span>'
	   	  				html+= '</div>'
	   	  				html+= '<div class="ui-grid-info  m_0  ui-border-b" >'
	   	  					html+= '<div class="ui-nowrap-multi pl5"> <div class="hd">'+loveList[i].name+'</div> </div> '
	   						// if(loveList[i].price_type == 0){
	   						// 	html+= '<p class="pl5 pb0">	<span class="color_red font-weight">'+currency_symbol+loveList[i].price+'</span> </p>'
	   						// }else if(loveList[i].price_type == 1){ 
	   						// 	html+= '<p class="pl5 pb0">	<span class="color_red font-weight">'+currency_symbol+loveList[i].account_money+'</span> </p>'
	   						// }else 
	   							if(loveList[i].price_type == 2){
	   							html+= '<p class="pl5 pb0">	<span class="color_red font-weight">'+currency_symbol+loveList[i].account_money+'+'+loveList[i].account_score+score_name+'</span> </p>'
	   						}else if(loveList[i].price_type == 3){
	   							html+= '<p class="pl5 pb0">	<span class="color_red font-weight">'+currency_symbol+loveList[i].account_score+score_name+'</span> </p>'
	   						}else{
								html+= '<p class="pl5 pb0">	<span class="color_red font-weight">'+currency_symbol+loveList[i].price+'</span> </p>'
							} 
	   	  					html+= '<p class="pl5 pb0"> <span class="font_size12  pt5" style="color:#777">已售'+loveList[i].sell_count+'</span></p> '
	   	  				html+= '</div>'
	   	  			html+= '</div>'
	   	  		html+= '</div>'
	   	  html+= '	</a> '
	   	  html+= '</li>'
	   	  
	   } 
   }

	$('.love_list').append(html)  
	});
}