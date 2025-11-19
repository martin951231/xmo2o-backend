var nowPage='', storeTheme = 0;
var window_width = $(window).width();
var window_height = $(window).height();
var categoryList=null,sortList=null,typeList=null,choosePage='list', isShowSpell = false,cart_cookie = '';


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
	$.cookie('cart_cookie',randomString(32),{expires:1,path:'/'});
	cart_cookie = $.cookie('cart_cookie');
}


$(function(){
	if(typeof(FastClick) == 'function'){
		FastClick.attach(document.body);
	}
	
	/*页面点击事件*/
	$(document).on('click','.page-link',function(){
		redirectPage(ajax_url_root+'classic_'+$(this).data('url'));
		return false;
	});
	
	$(document).on('click','.storelive',function(){
		location.href = 'http://'+document.domain+'/static/html/video/store_live.html?sorce_url='+encodeURIComponent($('#video_url').attr('src'));
		return false;
	});

	$(window).resize(function(){
		if($(window).width() != window_width){
			location.reload();
		}
	});
    var is_group_goods = $('#goods_group3').length;
    console.log('is_group_goods',is_group_goods);
	if(window.__wxjs_is_wkwebview && is_group_goods){
		window.addEventListener("pageshow", function(){
            showShop();
		},false);
	}else if(is_group_goods){
        showShop();
	}
	
	$(document).on('click','.storelive',function(){
		$('.video').show();
		
		if(Hls.isSupported()) {
			var video_show = document.getElementById('video_url');
			hls = new Hls();
			 hls.loadSource($(video_show).attr('src'));
			hls.attachMedia(video_show);
			hls.on(Hls.Events.MANIFEST_PARSED,function() {
			  video_show.play();
		  });
		}
		return false;
	});

});
function redirectPage(url){
	pageLoadTips();
	window.addEventListener("pagehide", function(){
		pageLoadTipHide();
	},false);
	
	window.location.href = url;
}


function cartEventReg(){
	$(document).on('click','#shopProductCartDel',function(){
		layer.open({
			content: '您确定要清空购物车吗？',
			btn: ['确认', '取消'],
			shadeClose: false,
			yes: function(){
				$('#shopProductRightBar2 .product_btn.min,#shopProductRightBar2 .product_btn.number,#shopProductBottomBar .product_btn.min,#shopProductBottomBar .product_btn.number').remove();
				$('#shopDetailPageBuy').show();
				$('#shopDetailPageNumber').hide();
				productCart = [];
				productCartNumber = 0;
				productCartMoney = 0;
				
				cartFunction('clear');
				layer.closeAll();
				$('#shopProductCartShade').trigger('click');
			}, no: function(){
				
			}
		});
	});
	//top_header点击
    $('.top_header').click(function(e){
        var header_data = $('.header_data').text();
        if(deliverExtraPrice == 1){
            $('#shopProductCart #cartInfo .price').removeClass('active');
            deliverExtraPrice = 0;
            $('#checkCartEmpty').show();
            $('#checkCart').hide();
        } else {
            $('#shopProductCart #cartInfo .price').addClass('active');
            $('#additional').text(header_data);

            $('.top_header, #checkCartEmpty').hide();
            $('#checkCart').show();

            $('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height',window_height-152-50);
            deliverExtraPrice = 1;
        }
        click = true;
    });

	
	$('#cartInfo').click(function(){
		if(!$(this).hasClass('isShow')){
			$(this).addClass('isShow');
			$('#shopProductCartShade').show();
			$('#top_fei').css('max-height',(window_height-50)/3*2+'px').show();
			if (parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price && nowShop.store.extra_price > 0 && nowShop.store.deliver_type != 2 && nowShop.store.deliver_type != 5) {
			    $('.header_data').html(nowShop.store.extra_price);
			    if (deliverExtraPrice == 0) $('.top_header').show();
			} else {
			    deliverExtraPrice = 0;
			    $('.top_header').hide();
			}
			laytpl($('#productCartBoxTpl').html()).render(productCart, function(html){
				$('#shopProductCartBox').html(html);

                if(isShowSpell && nowIndex == '') {
                    $('#spell_tip').show();
                } else {
                    $('#spell_tip').hide();
                }
				$('body').css('overflow-y','hidden');
			});
		}else{
			$('#shopProductCartShade').trigger('click');
			$('#top_fei').show();
		}
		// $('#shopPageShade').trigger('click');
	});
	
	$('#shopProductCartShade').click(function(){
		$(this).hide();
		$('#shopProductCartBox').empty();
		$('#cartInfo').removeClass('isShow');
		$('body').css('overflow-y','auto');
//		$('#top_fei').hide();
	});
	$(document).on('click','#shopProductCartBox .product_btn.plus',function(event){
		if(nowShop.store.is_close == 1){
			motify.log('店铺休息中');
			return false;
		}
		tmpDomObj = $(this);
		cartFunction('plus',tmpDomObj,tmpDomObj.closest('dd'));
	});
	$(document).on('click','#shopProductCartBox .product_btn.min',function(event){
		tmpDomObj = $(this);
		cartFunction('min',tmpDomObj,tmpDomObj.hasClass('cart') ? tmpDomObj.closest('dd') : tmpDomObj.closest('li'));
		return false;
	});
	
	
	$('#checkCart').click(function(){
        var locationHashParam = locationClassicHash.split('-');
        $.get(ajax_basic_price,{store_id:locationHashParam[1]},function(basic_res){
            if(basic_res.basic_price > nowShop.store.delivery_price){
                nowShop.store.delivery_price = basic_res.basic_price;
                motify.log('起送价已更新！')
				window.location.reload();
            }
        },'json');
	    if (cartid != '' && nowIndex != '') {
            var cartData = window.localStorage.getItem(cartid + '_' + nowIndex);
            var jsonData = window.localStorage.getItem(cartid);
            var name = '', avatar = '';
            if (jsonData != '' && jsonData != null) {
                jsonData = $.parseJSON(jsonData);
                name = jsonData.name;
                avatar = jsonData.avatar;
            }
            var from = spellfrm == 'spell' ? 1 : 0;
            $.post(spell_add_cart, {'cartData':cartData, 'cartid':cartid, 'store_id':nowShop.store.id, 'index':nowIndex, 'name':name, 'avatar':avatar, 'from':from}, function(response){
                if (response.error == false) {
                    if (spellfrm == 'spell') {
                        var this_url = spell_cart_url + 'spell';
                    } else {
                        var this_url = spell_cart_url + 'sync';
                    }
                    window.location.href = this_url+'&store_id='+nowShop.store.id + '&cartid=' + response.cartid;
                } else {
                    motify.log(response.msg);
                }
            }, 'json');
        } else {
            window.location.href = check_cart_url+'&store_id='+nowShop.store.id + '&deliverExtraPrice=' + deliverExtraPrice;
        }
	});
    
    //点击分单
    $(document).on('click', '#spell_tip', function(){
		var cookieProductCart = [];
		for(var i in productCart){
			cookieProductCart.push(productCart[i]);
		}
		if(cookieProductCart.length > 0){
			nowShopCart = JSON.stringify(cookieProductCart);
		}else{
			nowShopCart = '{}';
		}
        $.post(spell_add_cart, {'cartData':nowShopCart, 'store_id':nowShop.store.id, 'index':1}, function(response){
            if (response.error == false) {
                if (spellfrm == 'spell') {
                    var this_url = spell_cart_url + 'spell';
                } else {
                    var this_url = spell_cart_url + 'sync';
                }
                //将点单数据写入下标为1
                window.localStorage.setItem(response.cartid + '_1', nowShopCart);
                window.location.href = this_url+'&store_id='+nowShop.store.id + '&cartid=' + response.cartid;
            } else {
                motify.log(response.msg);
            }
        }, 'json');
    });
}

var nowShop = {},isShowShop = false,tmpDomObj={},shopDetailPageIscroll = null,nowProduct = {},firstMenuClick = false,productSwiper = null,productPicList = [];
function showShop(){
	pageLoadTips({showBg:false});

	$('#pageShop').addClass('nowPage').show();
	$('#pageShop').css('min-height','');
	$('#pageShop').css('height',$(window).height());

	if(isShowShop == false){
        $('#goods_group3').css({width:window_width,'height':window_height-60});
        $('#shopContentBar').height(window_height-130);
        $('#shopContentBar>div').css({width:window_width});
        $('#shopContentBar #shopProductBox').css('left','0px');
        $('#shopContentBar #shopMerchantBox').css('left',window_width+'px');
        $('#shopProductLeftBar2,#shopProductRightBar2').css('height',window_height-130);
        $('#shopProductRightBar2').width(window_width-100);
        $('#shopMerchantBox').hide();
        $('#shopMerchantBox').css({height:window_height-152,'overflow-y':'auto'});

		$('#shopMenuBar li').click(function(){
            var tmpIndex = $(this).index();
            var tmpNav = $(this).data('nav');
            $(this).addClass('active').siblings().removeClass('active');
            // pageLoadTips({showBg:false});
            $('#shopContentBar').animate({'margin-left':'-'+tmpIndex*window_width+'px'},function(){
                showShopContent(tmpNav);
            });
		});

		cartEventReg();

		
		$(document).on('click','#shopProductRightBar2 li,#shopProductBottomBar li',function(event){
			redirectPage(ajax_url_root+'classic_good&shop_id='+store_id+'&good_id='+$(this).data('product_id'));
		});

		$(document).on('click','#shopProductRightBar2 .product_btn.plus,#shopProductBottomBar .product_btn.plus',function(event){
		   
			if(is_close == 1){
				motify.log('店铺休息中');
				return false;
			}
			tmpDomObj = $(this);
            if (parseInt(tmpDomObj.closest('li').data('max_num')) > 0 && parseInt(tmpDomObj.closest('li').data('max_num')) <= parseInt(tmpDomObj.siblings('.number').html())) {
                if (tmpDomObj.closest('li').data('is_seckill') && parseInt(tmpDomObj.closest('li').data('limit_type')) == 0) {
                    motify.log('每单可享受' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit') + '限时优惠价，超出恢复原价');
                } else {
                    if (parseInt(tmpDomObj.closest('li').data('limit_type')) == 0) {
                        motify.log('每单限购' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit'));
                    } else {
                        motify.log('每个用户限购' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit'));
                    }
                    return false;
                }
            }
			var max_num_=parseInt(tmpDomObj.closest('li').data('max_num')) ;
			var user_buyer_num=parseInt(tmpDomObj.closest('li').data('user_buy_num')) ;

			if(user_buyer_num>0 && max_num_>0 && user_buyer_num>=max_num_ && parseInt(tmpDomObj.closest('li').data('limit_type'))==1){
				motify.log('每个用户限购' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit'));
				return false;
			}
            
			var intStock = parseInt(tmpDomObj.closest('li').data('stock'));
			if(intStock != -1 && (intStock == 0 || intStock - parseInt(tmpDomObj.siblings('.number').html()) <= 0)){
				motify.log('没有库存了');
				return false;
			}

			cartFunction('plus',tmpDomObj,tmpDomObj.closest('li'));

			return false;
		});
		$(document).on('click','#shopProductRightBar2 .bgPlusBack',function(event){
            if(is_close == 1){
                motify.log('店铺休息中');
                return false;
            }
			tmpDomObj = $(this);
            if (parseInt(tmpDomObj.closest('li').data('max_num')) > 0 && parseInt(tmpDomObj.closest('li').data('max_num')) <= parseInt(tmpDomObj.siblings('.number').html())) {
                if (tmpDomObj.closest('li').data('is_seckill') && parseInt(tmpDomObj.closest('li').data('limit_type')) == 0) {
                    motify.log('每单可享受' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit') + '限时优惠价，超出恢复原价');
                } else {
                    if (parseInt(tmpDomObj.closest('li').data('limit_type')) == 0) {
                        motify.log('每单限购' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit'));
                    } else {
                        motify.log('每个用户限购' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit'));
                    }
                    return false;
                }
            }
			var max_num_=parseInt(tmpDomObj.closest('li').data('max_num')) ;
			var user_buyer_num=parseInt(tmpDomObj.closest('li').data('user_buy_num')) ;

			if(user_buyer_num>0 && max_num_>0 && user_buyer_num>=max_num_ && parseInt(tmpDomObj.closest('li').data('limit_type'))==1){
				motify.log('每个用户限购' + parseInt(tmpDomObj.closest('li').data('max_num')) + tmpDomObj.closest('li').data('unit'));
				return false;
			}
            
			var intStock = parseInt(tmpDomObj.closest('li').data('stock'));
			if(intStock != -1 && (intStock == 0 || intStock - parseInt(tmpDomObj.siblings('.number').html()) <= 0)){
				motify.log('没有库存了');
				return false;
			}

			cartFunction('plus',tmpDomObj,tmpDomObj.closest('li'));

			return false;
		});
		$(document).on('click','#shopProductRightBar2 .bgMinBack',function(event){
			tmpDomObj = $(this).siblings('.product_btn.min');
			cartFunction('min',tmpDomObj,tmpDomObj.hasClass('cart') ? tmpDomObj.closest('dd') : tmpDomObj.closest('li'));
			return false;
		});
		$(document).on('click','#shopProductRightBar2 .product_btn.min',function(event){
			tmpDomObj = $(this);
			cartFunction('min',tmpDomObj,tmpDomObj.hasClass('cart') ? tmpDomObj.closest('dd') : tmpDomObj.closest('li'));
			return false;
		});

		/*Right滚动条*/
		if(motify.checkIos()){
			$('#shopProductRightBar2,#shopProductBottomBar').on('touchmove',function(){
				scrollProductEvent('ios');
			});
			$('#shopProductRightBar2,#shopProductBottomBar').scroll(function(){
				$('#shopProductRightBar2').trigger('touchmove');
			});
		}else{
			$('#shopProductRightBar2,#shopProductBottomBar').scroll(function(){
				scrollProductEvent('android');
			});
		}
		function scrollProductEvent(phoneType){
			var scrollRightTop = $('#shopMenuBar').css('display') == 'none' ? $('#shopProductBottomBar').scrollTop() :$('#shopProductRightBar2').scrollTop();
			if(scrollRightTop > 10){
				$(window).scrollTop($('#shopMenuBar').css('display') == 'none' ? $('#shopCatBar').offset().top-50 : $('#shopMenuBar').offset().top-50);
			}
		}

        //初始化数据
        var nowShopCart = '';
        if (cartid != '' && nowIndex != '') {
            nowShopCart = window.localStorage.getItem(cartid + '_' + nowIndex);
        }
        var tmpShopCart = [];
        if ((nowShopCart == '' || nowShopCart == null) && nowIndex == '') {
            pageLoadTips();
            $.post(ajax_cart_url,{shop_id:store_id,cart_cookie:cart_cookie},function(result){
                if(!result.status || result.status == 0){
                    result.info = [];
                }
                initializeShopCart(result.info);
                if(location.href.indexOf('classic_good') != -1){
                    changeProductSpec();
                }
            });
        }else{
            initializeShopCart($.parseJSON(nowShopCart));
        }

		isShowShop = true;
	}
	
	productCart=[];
	productCartNumber = 0;
	productCartMoney  = 0;
	$('#shopProductCart #cartNumber').html(productCartNumber);
	$('#shopProductCart #cartMoney').html(productCartMoney.toFixed(2));
	$('#shopProductCart #cartInfo').hide();
	$('#shopProductCart #emptyCart').show();
	$.ajaxSettings.async = false;
	$.getJSON(ajax_url_root+'ajaxShop',{store_id:store_id},function(result){
		if(result.error_code){
			motify.log(result.msg);
			return false;
		}
		$('#shopTitle').html(result.store.name);
		$('#shopIcon').css('background-image','url('+result.store.image+')');
		if(result.store.delivery){
			if(result.store.delivery_money == 0){
                var str = ' | 免配送费';
			}else{
                var str = ' | 配送  '+currency_symbol+result.store.delivery_money;
			}
			$('#deliveryText').html('起送  '+currency_symbol+result.store.delivery_price + str + ' | 送达 '+result.store.delivery_time+result.store.delivery_time_type);
		}else{
			$('#deliveryText').html('本店铺仅支持门店自提');
		}
		$('#shopNoticeText').html(result.store.store_notice);

        // if (result.store.kf_url.length > 0) {
        //     $('#enter_im_div').show();
			// $('#enter_im').attr('data-url',result.store.kf_url);
        // }
        // if (result.store.vr_link && result.store.vr_link.length > 0 && result.store.vr_link!='') {
        //     $('#shopContentBar').append($('<a>',{'href':result.store.vr_link,'class':'vr_box'}));
        // }
		if(result.store.is_close == 1){
            if (result.store.close_reason.length > 0) {
                $('.top_header').text(result.store.close_reason).css({'background':'#eac3a7', 'font-size':'12px'}).unbind('click');
                $('#top_fei').show();
            }
			$('#checkCartEmpty').html('店铺休息中');
		}else if(result.store.delivery){
			$('#checkCartEmpty').html(parseFloat(result.store.delivery_price).toFixed(2)+currency_txt+'起送');
		}

		nowShop = result;

		$('#shopProductBox,#shopMerchantBox,#shopReplyBox').data('isShow','0');
		$('#shopReplyBox').hide();
		// showShopContent('product');
		firstMenuClick = true;
		$('#shopMenuBar .product').trigger('click');

		//自定义了分享就不要替换了
		if(window.shareData.tTitle == undefined){
			changeWechatShare('shop',{title:nowShop.store.name,desc:nowShop.store.txt_info,imgUrl:nowShop.store.image,link:shopShareUrl+nowShop.store.id});
		}
	});

	$('#shopContentBar,#shopBanner').show();
	
	setTimeout(function(){
		pageLoadHides();
	},500);
}

function changeProductSpec(){
	$('#shopDetailPageNumber .number').html('0');
	if(nowProduct.spec_list){
		var productSpecId = [];
		$.each($('#shopDetailPageFormat .row'),function(i,item){
			productSpecId.push($(item).find('li.active').data('spec_list_id'));
		});
		var productSpecStr = productSpecId.join('_');
		var nowProductSpect = nowProduct.list[productSpecStr];
		//$('#shopDetailPagePrice').html(currency_symbol+''+((nowProduct.is_seckill_price && nowProductSpect.seckill_price) ? nowProductSpect.seckill_price : nowProductSpect.price)+(nowProduct.extra_pay_price>0?'+'+nowProduct.extra_pay_price+nowProduct.extra_pay_price_name:'')+'<span class="unit"><em>/ </em>'+nowProduct.unit+'</span>'+(nowProductSpect.stock_num != -1 ? '<span class="stock" data-stock="'+nowProductSpect.stock_num+'">' + (nowProductSpect.stock_num < 10 ? '剩下'+nowProductSpect.stock_num+nowProduct.unit : '') + '</span>' : '<span class="stock" data-stock="-1"></span>'));
		//$('#shopDetailPagePrice').html(currency_symbol+''+nowProductSpect.price+'<span class="unit"><em>/ </em>'+nowProduct.unit+'</span>'+(nowProductSpect.stock_num != -1 ? '<span data-stock="'+nowProductSpect.stock_num+'">剩下'+nowProductSpect.stock_num+nowProduct.unit+'</span>' : '<span data-stock="-1"></span>'));
		$('#shopDetailPagePrice').html(
			'<div>'+
			currency_symbol +((nowProduct.is_seckill_price && nowProductSpect.seckill_price) ? nowProductSpect.seckill_price : nowProductSpect.price)+
			(nowProduct.extra_pay_price>0?'+'+nowProduct.extra_pay_price+nowProduct.extra_pay_price_name:'')+'<span class="unit"><em>/ </em>'+nowProduct.unit+'</span>'+(nowProductSpect.stock_num != -1 ? '<span class="stock" data-stock="'+nowProductSpect.stock_num+'">'+ (nowProductSpect.stock_num < 10 ? '剩下' + nowProductSpect.stock_num+nowProduct.unit : '') + '</span><span >(500g)</span></div>' : '<span >(500g)</span></div>')+
			'<p class="explain"><span class="weight">每份5元/200g</span><span class="instruction"><a href="javascript:;">结算说明&gt;</a></span></p>'
		);



		if (nowProductSpect.max_num > 0) {
	            $('#showMax').text(nowProductSpect.max_num);
	            $('#showLimit').show();
	        } else {
	            $('#showLimit').hide();
	        }
		if(nowProduct.properties_list){
			for(var i in nowProductSpect.properties){
				$('.productProperties_'+nowProductSpect.properties[i].id).data('num',nowProductSpect.properties[i].num);
			}
		}
		var nowProductCartLabel = nowProduct.goods_id + '_' + productSpecStr;
	}else{
		var nowProductCartLabel = nowProduct.goods_id;
	}
	if(nowProduct.properties_list){
		$.each($('#shopDetailPageLabelBox .row'),function(i,item){
			var tmpProductProperties = [];
			$.each($(item).find('li.active'),function(j,jtem){
				nowProductCartLabel = nowProductCartLabel+'_'+$(jtem).data('label_list_id')+'_'+$(jtem).data('label_id');
			});
		});
	}
	$('#shopDetailPageNumber .number').attr('class','product_btn number');
	$('#shopDetailPageNumber .number').addClass('productNum-'+nowProductCartLabel);
	if(productCart[nowProductCartLabel]){
		$('#shopDetailPageNumber').show();
		$('#shopDetailPageNumber .number').html(productCart[nowProductCartLabel].count);
		$('#shopDetailPageBuy').hide();
	}else{
		$('#shopDetailPageNumber').hide();
		$('#shopDetailPageBuy').show();
	}
}

var productCart = [],productCartNumber = 0,productCartMoney=0;
function cartFunction(type,obj,dataObj){
	if(dataObj == 'productPage'){
		var productId = nowProduct.goods_id;
		var productName = nowProduct.name;
		if(nowProduct.spec_list){
			var productSpecListId = [],productSpecId = [],productSpecText = [];
			$.each($('#shopDetailPageFormat .row'),function(i,item){
				productSpecListId.push($(item).find('li.active').data('spec_list_id'));
				productSpecId.push($(item).find('li.active').data('spec_id'));
				productSpecText.push($(item).find('li.active').html());
			});
			var productSpecStr = productSpecListId.join('_');

			var productKey = productId + '_' + productSpecStr;
			var nowProductSpect = nowProduct.list[productSpecStr];
			var productPrice = (nowProduct.is_seckill_price && nowProductSpect.seckill_price) ? parseFloat(nowProductSpect.seckill_price) : parseFloat(nowProductSpect.price);
			var productStock = nowProductSpect.stock_num;
            var oldPrice = parseFloat(nowProductSpect.old_price);
			var maxNum = parseInt(nowProductSpect.max_num);
			var isSeckill = nowProduct.is_seckill_price;
			var limit_type = nowProduct.limit_type;
			var unit = nowProduct.unit;
			var minNum = parseInt(nowProduct.min_num);
			
			var productParam = [];
			for(var i in productSpecListId){
				productParam.push({'type':'spec','spec_id':productSpecId[i],'id':productSpecListId[i],'name':productSpecText[i]});
			}
		}else{
			var productKey = productId;
			var productPrice = nowProduct.price;
			var productParam = [];
			var productStock = parseInt(nowProduct.stock_num);
			
            var oldPrice = parseFloat(nowProduct.old_price);
            var maxNum = parseInt(nowProduct.max_num);
            var minNum = parseInt(nowProduct.min_num);
            var isSeckill = nowProduct.is_seckill_price;
            var limit_type = nowProduct.limit_type;
            var unit = nowProduct.unit;
		}
        
		if(nowProduct.properties_list){
			$.each($('#shopDetailPageLabelBox .row'),function(i,item){
				var tmpProductProperties = [];
				$.each($(item).find('li.active'),function(j,jtem){
					productKey = productKey+'_'+$(jtem).data('label_list_id')+'_'+$(jtem).data('label_id');
					tmpProductProperties.push({'id':$(jtem).data('label_id'),'list_id':$(jtem).data('label_list_id'),'name':$(jtem).html()});
				});
				productParam.push({'type':'properties','data':tmpProductProperties});
			});
		}
        var productPackCharge = nowProduct.packing_charge;
	}else if(type != 'count' && type != 'clear'){
		if(dataObj.hasClass('cartDD') && dataObj.find('.cartLeft').hasClass('hasSpec')){
			var productKey = dataObj.find('.spec').data('product_id');
			var productStock = dataObj.find('.spec').data('stock');
		}else{
			var productKey = dataObj.data('product_id');
			var productStock = dataObj.data('stock');
		}
        var productPackCharge = dataObj.data('packing_charge');
		var productId = dataObj.data('product_id');
		var productName = dataObj.data('product_name');
		var productPrice = parseFloat(dataObj.data('product_price'));
		var oldPrice = parseFloat(dataObj.data('o_price'));
		var maxNum = parseInt(dataObj.data('max_num'));
		var isSeckill = dataObj.data('is_seckill');
		var limit_type = dataObj.data('limit_type');
		var unit = dataObj.data('unit');
		var minNum = parseInt(dataObj.data('min_num'));
		var productParam = [];
	}

	if(type == 'plus'){
		if(dataObj != 'productPage' && dataObj.hasClass('cartDD')){
			var tmpStock = parseInt(dataObj.data('stock'));
            
			if(tmpStock != -1 && productCart[productKey] && productCart[productKey]['count'] >= tmpStock){
				motify.log('没有库存了');
				return false;
			}
		}
		if ((dataObj == 'productPage' || dataObj.hasClass('cartDD')) && maxNum > 0) {
    		if (productCart[productKey] && maxNum <= productCart[productKey]['count']) {
                if (isSeckill == true && limit_type == 0) {
                    motify.log('每单可享受' + maxNum + unit + '限时优惠价，超出恢复原价');
                } else {
                    if (limit_type == 0) {
                        motify.log('每单限购' + maxNum + unit);
                    } else {
                        motify.log('每个用户限购' + maxNum + unit);
                    }
                    return false;
                }
            }
		}
		
		$('#shopProductCart .cart').addClass('bound');
		setTimeout(function(){
			$('#shopProductCart .cart').removeClass('bound');
		},500);
		var thisAddNum = 1;
		if (productCart[productKey]) {
			productCart[productKey]['count']++;
			$('.productNum-'+productKey).html(productCart[productKey]['count']);
		} else {
            if (minNum > 0) {
                thisAddNum = minNum;
            }
			if (dataObj == 'productPage') {
				$('#shopDetailPageBuy').hide();
				$('#shopDetailPageNumber').show();
				$('#shopDetailPageNumber .number').html(thisAddNum);
				$('.product_'+productId+' .plus').after('<div class="product_btn number productNum-'+productId+'">' + thisAddNum + '</div>').after('<div class="product_btn min"></div>');
			} else {
				obj.after('<div class="product_btn number productNum-'+productId+'">' + thisAddNum + '</div>');
				obj.after('<div class="product_btn min"></div>');
			}
			productPackCharge = productPackCharge ? productPackCharge : 0;
			productCart[productKey] = {
				'productId':productId,
				'productName':productName,
				'productPrice':productPrice,
				'productStock':productStock,
                'productParam':productParam,
                'productPackCharge':productPackCharge,
                'maxNum':maxNum,
                'minNum':minNum,
                'isSeckill':isSeckill,
                'limit_type':limit_type,
                'unit':unit,
                'oldPrice':oldPrice,
				'count':thisAddNum,
			};
		}
		if (parseInt(productCart[productKey]['maxNum']) > 0 && parseInt(productCart[productKey]['count']) > parseInt(productCart[productKey]['maxNum'])) {
		    productPrice = parseFloat(productCart[productKey]['oldPrice']);
		}
		productCartNumber += thisAddNum;
        productCartMoney = productCartMoney + (productPrice + productPackCharge) * thisAddNum;
        if (productPackCharge > 0 && dataObj != 'productPage' && dataObj.hasClass('cartDD')) {
            $('#packChargeCount').html((parseFloat($('#packChargeCount').html())+parseFloat(productPackCharge)).toFixed(2));
        }
        var locationHashParam = locationClassicHash.split('-');
        $.get(ajax_basic_price,{store_id:locationHashParam[1]},function(basic_res){
            if(basic_res.basic_price != nowShop.store.delivery_price){
                nowShop.store.delivery_price = basic_res.basic_price;
                motify.log('起送价已更新！');
                if(nowShop.store.delivery == true && parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price) {
                    $('#checkCart').hide();
                    $('#checkCartEmpty').addClass('noEmpty').show().html('还差'+currency_symbol + (nowShop.store.delivery_price - parseFloat(productCartMoney.toFixed(2))).toFixed(2) + '起送');
                    if (nowShop.store.deliver_type != 2 && nowShop.store.deliver_type != 5) {
                        if (nowShop.store.extra_price > 0) {
                            $('.header_data').html(nowShop.store.extra_price);
                            $('#top_fei, .top_header').show();
                            if ($('.top_header').is(':hidden')) {
                                $('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height', window_height - 166 - 50 - 30);
                            }
                        }
                    }
                }
            }
        },'json');
	}else if(type == 'min'){
		$('#shopProductCart .cart').addClass('bound');
		setTimeout(function(){
			$('#shopProductCart .cart').removeClass('bound');
		},500);
		var thisAddNum = 1;
        if (minNum > 0) {
            thisAddNum = minNum;
        }
        var reduceNum = 1;
		if(productCart[productKey].count == thisAddNum){
		    reduceNum = thisAddNum;
			if(dataObj == 'productPage'){
				$('#shopDetailPageBuy').show();
				$('#shopDetailPageNumber').hide();
				$('#shopDetailPageNumber .number').html('0');
			}else{
				obj.siblings('.number').remove();
				obj.remove();
				if(dataObj.hasClass('cartDD')){
					dataObj.remove();
					$('#shopProductRightBar2 .productNum-'+productKey).siblings('.min').remove();
					$('#shopProductRightBar2 .productNum-'+productKey).remove();
					$('#shopProductBottomBar .productNum-'+productKey).siblings('.min').remove();
					$('#shopProductBottomBar .productNum-'+productKey).remove();
					$('#shopDetailPageBuy').show();
					$('#shopDetailPageNumber').hide();
					$('#shopDetailPageNumber .number').html('0');
				}
			}
			delete productCart[productKey];
		}else{
			productCart[productKey]['count']--;
			$('.productNum-'+productKey).html(productCart[productKey]['count']);
            if(productPackCharge > 0 && dataObj != 'productPage' && dataObj.hasClass('cartDD')){
                $('#packChargeCount').html((parseFloat($('#packChargeCount').html())-parseFloat(productPackCharge)).toFixed(2));
            }
		}
		
        if (productCart[productKey] && parseInt(productCart[productKey]['maxNum']) > 0 && parseInt(productCart[productKey]['count']) >= parseInt(productCart[productKey]['maxNum'])) {
            productPrice = parseFloat(productCart[productKey]['oldPrice']);
        }
		productCartNumber -= reduceNum;
        productCartMoney = productCartMoney - (productPrice + productPackCharge) * reduceNum;
//		productCartMoney = productCartMoney - productPrice * reduceNum;
        var locationHashParam = locationClassicHash.split('-');
        $.get(ajax_basic_price,{store_id:locationHashParam[1]},function(basic_res){
            if(basic_res.basic_price != nowShop.store.delivery_price){
                nowShop.store.delivery_price = basic_res.basic_price;
                motify.log('起送价已更新！');
                if(nowShop.store.delivery == true && parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price) {
                    $('#checkCart').hide();
                    $('#checkCartEmpty').addClass('noEmpty').show().html('还差'+currency_symbol + (nowShop.store.delivery_price - parseFloat(productCartMoney.toFixed(2))).toFixed(2) + '起送');
                    if (nowShop.store.deliver_type != 2 && nowShop.store.deliver_type != 5) {
                        if (nowShop.store.extra_price > 0) {
                            $('.header_data').html(nowShop.store.extra_price);
                            $('#top_fei, .top_header').show();
                            if ($('.top_header').is(':hidden')) {
                                $('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height', window_height - 166 - 50 - 30);
                            }
                        }
                    }
                }
            }
        },'json');
	}
	
	
	$('#shopProductCart #cartNumber').html(productCartNumber);
	$('#shopProductCart #cartMoney').html(productCartMoney.toFixed(2));

	if(nowIndex != ''){
        $('#checkCartEmpty').hide();
        $('#checkCart').show();
        $('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height',window_height-166-50);
    } else if(productCartNumber == 0){
        if(nowShop.store.pick == true && nowShop.store.delivery == false){
            $('#checkCartEmpty').removeClass('noEmpty').show().html(currency_symbol+'0起送价');
        } else {
            $('#checkCartEmpty').removeClass('noEmpty').show().html(parseFloat(nowShop.store.delivery_price).toFixed(2)+currency_txt+'起送');
        }
		$('#checkCart').removeClass('noEmpty').hide();
	}else if(nowShop.store.pick == true){
		$('#checkCartEmpty').hide();
		$('#checkCart').show();
		$('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height',window_height-166-50);
        $('#cartInfo .price').removeClass('active');
        if (nowShop.store.deliver_type != 2 && nowShop.store.deliver_type != 5 && nowShop.store.extra_price > 0 && parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price) {
            $('.header_data').html(nowShop.store.extra_price);
            $('#top_fei, .top_header').show();
            if($('.top_header').is(':hidden')){
				$('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height',window_height-166-50-30);
			}
        } else {
            $('.top_header').hide();
            deliverExtraPrice = 0;
           
        }
		isShowSpell = true;
	}else if(nowShop.store.delivery == true && parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price){
		$('#checkCart').hide();
		$('#checkCartEmpty').addClass('noEmpty').show().html('还差'+currency_symbol+(nowShop.store.delivery_price - parseFloat(productCartMoney.toFixed(2))).toFixed(2)+'起送');
		if (nowShop.store.deliver_type != 2 && nowShop.store.deliver_type != 5) {
		    if (nowShop.store.extra_price > 0) {
    		    $('.header_data').html(nowShop.store.extra_price);
    		    $('#top_fei, .top_header').show();
    		    if($('.top_header').is(':hidden')){
    				$('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height',window_height-166-50-30);
    			}
		    }
		}

        isShowSpell = true;
	}else if(nowShop.store.pick == false && nowShop.store.delivery == false && parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price) {
        $('#checkCart').hide();
        $('#checkCartEmpty').addClass('noEmpty').show().html('还差'+currency_symbol + (nowShop.store.delivery_price - parseFloat(productCartMoney.toFixed(2))).toFixed(2) + '起送');
    }else{
		$('#checkCartEmpty').hide();
		$('#checkCart').show();
		$('#shopProductLeftBar,#shopProductRightBar,#shopProductBottomBar').css('height',window_height-166-50);
        $('#cartInfo .price').removeClass('active');
        $('.top_header').hide();
        deliverExtraPrice = 0;
        isShowSpell = true;
       
	}
	if(productCartNumber > 0){
		$('#shopProductCart #emptyCart').hide();
		$('#shopProductCart #cartInfo').show();
	}else{
		if($('#cartInfo').hasClass('isShow')){
			$('#shopProductCartShade').trigger('click');
		}
		$('#top_fei').hide();
		$('#shopProductCart #cartInfo').hide();
		$('#shopProductCart #emptyCart').show();
	}
	if (parseFloat(productCartMoney.toFixed(2)) < nowShop.store.delivery_price) {
	    deliverExtraPrice = 0;
	}
	stringifyCart(type);
}

function stringifyCart(type){
    var cookieProductCart = [];
    for(var i in productCart){
        cookieProductCart.push(productCart[i]);
    }
    
    //记录拼单的每个袋的数据
    if (cartid != '' && nowIndex != '') {
        window.localStorage.setItem(cartid + '_' + nowIndex, JSON.stringify(cookieProductCart));
    }
    
    if(nowIndex == '' && type != 'count'){
		pageLoadTips({showBg:false});
		$.post(ajax_cart_save_url,{shop_id:nowShop.store.id,cart_cookie:cart_cookie,cookieProductCart:cookieProductCart},function(){
			pageLoadHides();
		});
	}
}
function parseCart(){
	
}

function parseCoupon(obj,type){
	var returnObj = {};
	for(var i in obj){
		if(typeof(obj[i]) == 'object'){
			returnObj[i] = [];
			for(var j in obj[i]){
				if(i == 'delivery' && obj[i].length == 1 && obj[i][j].delivery_free){
					returnObj[i].push('满'+obj[i][j].money+currency_txt+'免免免');
				}else{
					returnObj[i].push('满'+obj[i][j].money+currency_txt+'减'+obj[i][j].minus+currency_txt+'');
				}
			}
		}else if(i=='invoice'){
			returnObj[i] = '满'+obj[i]+currency_txt+'支持开发票，请在下单时填写发票抬头';
		}else if(i=='discount'){
			returnObj[i] = '店内全场'+obj[i]+'折';
		}else if(i=='isDiscountGoods'){
            returnObj[i] = '店内有部分商品限时优惠';
        }else if(i=='isdiscountsort'){
            returnObj[i] = '部分商品分类参与折扣优惠';
        }
	}
	var textObj = [];
	for(var i in returnObj){
		if(typeof(returnObj[i]) == 'object'){
			switch(i){
				case 'system_newuser':
					textObj[i] = '平台首单'+returnObj[i].join(',');
					break;
				case 'system_minus':
					textObj[i] = '平台优惠'+returnObj[i].join(',');
					break;
				case 'newuser':
					textObj[i] = '店铺首单'+returnObj[i].join(',');
					break;
				case 'minus':
					textObj[i] = '店铺优惠'+returnObj[i].join(',');
					break;
				case 'system_minus':
					textObj[i] = '平台优惠'+returnObj[i].join(',');
					break;
				case 'delivery':
					if(returnObj[i].length == 1 && returnObj[i][0].indexOf('免免免') > 0){
						textObj[i] = returnObj[i][0].replace('免免免','免配送费');
					}else{
						textObj[i] = '配送费'+returnObj[i].join(',');
					}
					break;
			}
		}else if(i=='invoice' || i=='discount' || i=='isDiscountGoods' || i=='isdiscountsort'){
			textObj[i] = returnObj[i];
		}
	}
	if(type == 'text'){
		var tmpObj = [];
		for(var i in textObj){
			tmpObj.push(textObj[i]);
		}
		return tmpObj.join(';');
	}else{
		return textObj;
	}
}

function showShopContent(nav){
	if(nav == 'product'){
		pageLoadHides();
	}else if(nav == 'merchant'){
        $('#shopCatBar').hide();
        $('#shopMenuBar').show();
        if($('#shopMerchantBox').data('isShow') != '1'){
            $('#shopMerchantBox').data('isShow','1');
        }
        $('#shopMerchantBox').show();
        pageLoadHides();
	}
}

function initializeShopCart(nowShopCartArr){
	if(nowShopCartArr && nowShopCartArr.length > 0){
		productCart=[];
		productCartNumber = productCartMoney = 0;
		for(var i in nowShopCartArr){
			var tmpSpec = [];
			var tmpObj = nowShopCartArr[i].productParam;
			if(tmpObj.length > 0){
				for(var j in tmpObj){
					if(tmpObj[j].type == 'spec'){
						tmpSpec.push(tmpObj[j].id);
					}else{
						for(var k in tmpObj[j].data){
							tmpSpec.push(tmpObj[j].data[k].list_id);
							tmpSpec.push(tmpObj[j].data[k].id);
						}
					}
				}
				if(tmpSpec.length > 0){
					var tmpSpecStr = nowShopCartArr[i].productId + '_' + tmpSpec.join('_');
					productCart[tmpSpecStr] = nowShopCartArr[i];
				}else{
					productCart[nowShopCartArr[i].productId] = nowShopCartArr[i];
				}
			}else{
				productCart[nowShopCartArr[i].productId] = nowShopCartArr[i];
				$('.product_'+nowShopCartArr[i].productId+' .plus').after('<div class="product_btn number productNum-'+nowShopCartArr[i].productId+'">'+nowShopCartArr[i].count+'</div>').after('<div class="product_btn min"></div>');
			}

			productCartNumber += nowShopCartArr[i].count;
			if (parseInt(nowShopCartArr[i].maxNum) > 0 && parseInt(nowShopCartArr[i].count) > parseInt(nowShopCartArr[i].maxNum)) {
				productCartMoney += parseInt(nowShopCartArr[i].maxNum) * parseFloat(nowShopCartArr[i].productPrice);
				productCartMoney += parseInt(parseInt(nowShopCartArr[i].count) - parseInt(nowShopCartArr[i].maxNum)) * parseFloat(nowShopCartArr[i].oldPrice);
			} else {
				productCartMoney += parseFloat(parseInt(nowShopCartArr[i].count) * parseFloat(nowShopCartArr[i].productPrice));
			}
			if (nowShopCartArr[i].productPackCharge != '' && typeof(nowShopCartArr[i].productPackCharge) != 'undefined') {
			    productCartMoney += parseFloat(parseInt(nowShopCartArr[i].count) * parseFloat(nowShopCartArr[i].productPackCharge));
			}
		}
		//统计购物车功能
		cartFunction('count');
	}else{
		cartFunction('count');
	}
	$('#shopProductBox').data('isShow','1');
}

function showGoodsBySortId(sortId, shopId)
{
	$.getJSON(ajax_url_root+'showGoodsBySortId',{'store_id':shopId, 'sort_id':sortId},function(result){
		laytpl($('#shopProductRightBarTpl').html()).render(result.product_list, function(html){
			$('#shopProductRightBar2 dl').html(html);
			$(".lazy").lazyload({effect:"fadeIn",threshold:200,failurelimit:8,container:$('#shopProductRightBar2')});
			$('#shopProductRightBar2').scrollTop(0);
		});
		
		if(productCart.length > 0){
			nowShopCartArr = productCart; 
			productCartNumber = productCartMoney = 0;
			for(var i in nowShopCartArr){
				var tmpSpec = [];
				var tmpObj = nowShopCartArr[i].productParam;
				if(tmpObj.length > 0){
					for(var j in tmpObj){
						if(tmpObj[j].type == 'spec'){
							tmpSpec.push(tmpObj[j].id);
						}else{
							for(var k in tmpObj[j].data){
								tmpSpec.push(tmpObj[j].data[k].list_id);
								tmpSpec.push(tmpObj[j].data[k].id);
							}
						}
					}
					if(tmpSpec.length > 0){
						var tmpSpecStr = nowShopCartArr[i].productId + '_' + tmpSpec.join('_');
						productCart[tmpSpecStr] = nowShopCartArr[i];
					}else{
						productCart[nowShopCartArr[i].productId] = nowShopCartArr[i];
					}
				}else{
					productCart[nowShopCartArr[i].productId] = nowShopCartArr[i];
					$('.product_'+nowShopCartArr[i].productId+' .plus').after('<div class="product_btn number productNum-'+nowShopCartArr[i].productId+'">'+nowShopCartArr[i].count+'</div>').after('<div class="product_btn min"></div>');
				}
				productCartNumber += nowShopCartArr[i].count;
				productCartMoney += nowShopCartArr[i].count * nowShopCartArr[i].productPrice;
			}
			
			//统计购物车功能
			cartFunction('count');
		}else{
			cartFunction('count');
		}
	});
}
function changeTitle(title){
	$(document).attr("title",title);
}

function pageLoadTips(options){
	this.options = {
		showBg:true,
		top:'center',
		left:'center'
	}
	for (var i in options){
		this.options[i] = options[i];
	}
	options = this.options;
	//显示背景
	if(options.showBg){
		$('#pageLoadTipShade').css('background','rgba(216,216,216,0.5)').removeClass('nobg');
	}else{
		$('#pageLoadTipShade').addClass('nobg');
	}
	//显示顶边
	if(options.top == 'center'){
		options.top = (window_height-120)/2;
	}
	//显示顶边
	if(options.left == 'center'){
		options.left = (window_width-120)/2;
	}
	$('#pageLoadTipBox').css({'top':options.top+'px','left':options.left+'px'});
	$('#pageLoadTipShade').css({'height':$(window).height(),'width':$(window).width()}).show();
}
function pageLoadHides(){
	$('#pageLoadTipShade').hide();
}






function close_swiper(){
    $('.albumContainer').remove();
}


var myScroll2=null,myScroll3=null;
$(function(){
    $('.photo ul').click(function(){
        var album_more = $(this).data('pics');
        var album_array = album_more.split(',');
        if(motify.checkWeixin()){
            wx.previewImage({
                current:album_array[0],
                urls:album_array
            });
        }else{
            var album_html = '<div class="albumContainer" style="display:block;">';
            album_html += '<div class="swiper-container">';
            album_html += '<div class="swiper-wrapper">';
            $.each(album_array,function(i,item){
                album_html += '<div class="swiper-slide">';
                album_html += '<img src="'+item+'"/>';
                album_html += '</div>';
            });
            album_html += '</div>';
            album_html += '<div class="swiper-pagination"></div><div class="swiper-close" onclick="close_swiper()">X</div>';
            album_html += '</div>';
            album_html += '</div>';
            $('body').append(album_html);
            mySwiper_big = $('.albumContainer .swiper-container').swiper({
                pagination:'.albumContainer .swiper-pagination',
                loop:true,
                grabCursor: true,
                paginationClickable: true
            });
        }
    });
	$('.dropdown-toggle').click(function(){
		if(choosePage == 'list'){
			isListShow=true;
		}else{
			isCatListShow=true;
		}
		if($(this).hasClass('active')){
			close_dropdown();
			return false;
		}
		close_dropdown();
		
		$(this).addClass('active');
		var nav = $(this).attr('data-nav');
		
		
		$('.dropdown-wrapper').addClass(nav+' active');
		$('.'+nav+'-wrapper').addClass('active');
		
		$('#dropdown_scroller,.dropdown-module').height($('.'+nav+'-wrapper>ul>li').size()*41-1);
		
		if($('#dropdown_scroller').height() < ($(window).height() - 97)*0.5){
			// $('#dropdown_scroller,.dropdown-module').height(($(window).height() - 97)*0.5);
			$('#dropdown_scroller,.dropdown-module').height($('#dropdown_scroller div').height());
		}else if($('#dropdown_scroller').height() < ($(window).height() - 97)*0.8){
			$('#dropdown_scroller,.dropdown-module').height($('#dropdown_scroller').height());
		}else{
			$('#dropdown_scroller,.dropdown-module').height(($(window).height() - 97)*0.8);
			myScroll3 = new IScroll('#dropdown_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
		}
		
		if(!$('#listNavBox').hasClass('fixed')){
			if(choosePage == 'list'){
				if($('#pageList').height() < window_height + $('#listNavBox').offset().top){
					$('#pageList .shade').css('min-height',window_height + $('#listNavBox').offset().top).show();
				}
				$(window).scrollTop(listNavBarTop+5);
				setTimeout(function(){
					$('#listNavBox').addClass('fixed');
					$('#pageList .shade').height($('#pageList').height()+'px').show();
					isShowShade = true;
				},50);
			}else{
				$('#pageCat .shade').height($('#pageCat').height()+'px').show();
			}
		}else{
			$('#pageList .shade').height($('#pageList').height()+'px').show();
			// if($('#pageList').height() < window_height + $('#listNavBox').offset().top){
				// $('#pageList .shade').css('min-height',window_height + $('#listNavBox').offset().top);
			// }
			isShowShade = true;
		}
		
		if($('.'+nav+'-wrapper').find('.active').attr('data-has-sub')){
			$('#dropdown_sub_scroller').html('<div>'+$('.'+nav+'-wrapper').find('.active').find('.sub_cat').html()+'<div>').css('left','160px');
			$('#dropdown_scroller').width('160px');
		}
		myScroll2 = new IScroll('#dropdown_sub_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
	});
	$('#pageList .shade').click(function(){
		$('#listNavBox').removeClass('fixed');
		$('#listNavPlaceHolderBox').hide();
		close_dropdown();
	});
	$('#pageCat .shade').click(function(){
		close_dropdown();
	});
	
	$(document).on('click','.biz-wrapper ul>li, .category-wrapper ul>li',function(){
		$('#dropdown_sub_scroller').css({'overflow':'hide','overflow-y':''});
		$('.biz-wrapper ul>li, .category-wrapper ul>li').removeClass('active');	
		if($(this).attr('data-has-sub')){
			$(this).addClass('active');
			$('#dropdown_sub_scroller').html('<div>'+$(this).find('.sub_cat').html()+'<div>').css('left','160px');
			$('#dropdown_scroller').width('160px');
			if($('#dropdown_sub_scroller>div').height() > $('#dropdown_sub_scroller').height()){
				myScroll2 = new IScroll('#dropdown_sub_scroller', { probeType: 1,disableMouse:true,disablePointer:true,mouseWheel: false,scrollX: false, scrollY:true,click:iScrollClick()});
			}
		}
	});
	// $(document).on('click','.dropdown-list li',function(){
		// if(!$(this).attr('data-has-sub')){
			// alert(222);
			// list_location($(this));
		// }
	// });
});

function close_dropdown(){
	if(choosePage == 'list'){
		isListShow=false;
	}else{
		isCatListShow=false;
	}
	isShowShade = false;
	$('#dropdown_scroller,#dropdown_sub_scroller').css('width','');
	$('.dropdown-toggle').removeClass('active');
	$('.dropdown-wrapper').prop('class','dropdown-wrapper');
	$('#dropdown_scroller,.dropdown-module').css('height','');
	$('#pageList .shade,#pageCat .shade').hide();
	$('#dropdown_sub_scroller').css('left','100%');
	$('#dropdown_scroller>div>ul>li').removeClass('active');
	if(myScroll3){
		myScroll3.destroy();
		myScroll3 = null;
		$('#dropdown_scroller>div').removeAttr('style');
	}
	if(myScroll2){
		myScroll2.destroy();
		myScroll2 = null;
		$('#dropdown_sub_scroller>div').removeAttr('style');
	}
}



function getListGeocoderbefore(type){
	if(type == true){
		if(locationClassicHash == 'address'){
			getGeoconv(arguments[2],arguments[3]);
		}else{
			if(user_long != '0'){
				getListGeocoder();
			}else if($.cookie('userLocationName')){
				user_long = arguments[2];
				user_lat = arguments[3];
				getListGeocoder();
			}else{
				getGeoconv(arguments[2],arguments[3]);
			}
		}
	}else{
		// alert('2222222226666666');
		pageLoadHides();
	}
}
function getGeoconv(lng,lat){
	$.getJSON('https://api.map.baidu.com/geoconv/v1/?coords='+lng+','+lat+'&from=1&to=5&ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeoconvBack&jsoncallback=?');
}
function getListGeoconvBack(obj){
	// alert(JSON.stringify(obj.result));
	user_long = obj.result[0].x;
	user_lat = obj.result[0].y;
	getListGeocoder();
}
function getListGeocoder(){
	$.getJSON('https://api.map.baidu.com/geocoder/v2/?ak=4c1bb2055e24296bbaef36574877b4e2&callback=getListGeocoderBack&location='+user_lat+','+user_long+'&output=json&pois=1&jsoncallback=?');
}
function getListGeocoderBack(obj){
	if(addressGeocoder == false){
		if(obj.result.pois.length > 0){
			$('#locationText').html(obj.result.pois[0].name);
			$.cookie('userLocationName',obj.result.pois[0].name,{expires:700,path:'/'});
		}else{
			$('#locationText').html(obj.result.addressComponent.street);
			$.cookie('userLocationName',obj.result.addressComponent.street,{expires:700,path:'/'});
		}
		pageLoadHides();
		if(nowPage == 'list'){
			showShopList(true);
		}else if(nowPage == 'shopSearch'){
			showShopSearchList(true);
		}
	}else{
		var tmpName = obj.result.pois.length > 0 ? obj.result.pois[0].name : obj.result.addressComponent.street;
		$('#pageAddressLocationList').show().find('.content dd').data({'long':user_long,'lat':user_lat,'name':tmpName}).find('.name').html(tmpName);
		addressGeocoder = false;
	}
}


function list_location(obj){
	close_dropdown();
	if(obj.data('cat_url')){
		obj.addClass('red');
		$('.dropdown-toggle.category .nav-head-name').html(obj.find('span').data('name'));
		if(choosePage == 'cat'){
			$('#catTitle').html(obj.find('span').data('name'));
		}
		cat_url = obj.data('cat_url');
	}else if(obj.data('type_url')){
		obj.addClass('active').siblings('li').removeClass('active');
		$('.dropdown-toggle.type .nav-head-name').html(obj.find('span').data('name'));
		type_url = obj.data('type_url');
	}else if(obj.data('sort_url')){
		obj.addClass('active').siblings('li').removeClass('active');
		$('.dropdown-toggle.sort .nav-head-name').html(obj.find('span').data('name'));
		sort_url = obj.data('sort_url');
	}
	pageLoadTips({showBg:false});
	if(choosePage == 'list'){
		showShopList(true);
	}else{
		showCatShopList(true);
	}
}

var listShopSearchNowPage=0,shopSearchListHasMorePage = true;
function showShopSearchList(newPage){
	isSearchListShow = true;
	if(newPage || listShopSearchNowPage == 0){
		$('#pageShopSearch #storeListLoadTip').show();
		$('#pageShopSearch #storeList .dealcard').empty();

		listShopSearchNowPage = 1;
		shopSearchListHasMorePage = true;
		pageLoadTips({showBg:false});
	}else{
		listShopSearchNowPage++;
	}
	$.getJSON(ajax_url_root+'ajax_list',{user_lat:user_lat,user_long:user_long,page:listShopSearchNowPage,key:$('#pageShopSearchTxt').val()},function(result){
		if(result.store_list && result.store_list.length > 0){
			laytpl($('#listShopTpl').html()).render(result.store_list, function(html){
				if(newPage){
					$('#pageShopSearch #storeList .dealcard').html(html);
					$('#pageShopSearch #storeList').show();
				}else{
					$('#pageShopSearch #storeList .dealcard').append(html);
				}
			});
			if(result.has_more == false){
				shopSearchListHasMorePage = false;
				$('#pageShopSearch #storeListLoadTip').hide();
			}
		}else{
			shopSearchListHasMorePage = false;
			$('#pageShopSearch #storeListLoadTip').hide();
		}
		isSearchListShow = false;
		pageLoadHides();
	});
}

var listShopNowPage=0,listHasMorePage = true;
function showShopList(newPage){
    pageLoadHides();
}

var catShopNowPage=0,catHasMorePage = true;
function showCatShopList(newPage){
	isCatListShow = true;
	if(newPage || catShopNowPage == 0){
		$('#pageCat #storeListLoadTip').show();
		$('#pageCat #storeList .dealcard').empty();		
		catShopNowPage = 1;
		catHasMorePage = true;
		pageLoadTips();
	}else{
		catShopNowPage++;
	}
	$.getJSON(ajax_url_root+'ajax_list',{cat_url:cat_url,sort_url:sort_url,type_url:type_url,user_lat:user_lat,user_long:user_long,page:catShopNowPage},function(result){
		if(result.store_list && result.store_list.length > 0){
			laytpl($('#listShopTpl').html()).render(result.store_list, function(html){
				if(newPage){
					$('#pageCat #storeList .dealcard').html(html);
				}else{
					$('#pageCat #storeList .dealcard').append(html);
				}
			});
			if(result.has_more == false){
				catHasMorePage = false;
				$('#pageCat #storeListLoadTip').hide();
			}
		}else{
			catHasMorePage = false;
			$('#pageCat #storeListLoadTip').hide();
		}
		isCatListShow = false;
		pageLoadHides();
	});
}

function goBackPage(){
	if(motify.checkLifeApp() && motify.getLifeAppVersion() >= 50){
		if(motify.checkIos()){
			iosCommon.iosFunction('webViewGoBack', {name:'webViewGoBack'});
			window.history.go(-1);
		}else{
			window.lifepasslogin.webViewGoBack();
		}
	}else{
		if(document.referrer == ""){
			window.location.href = storeUrl;
		}else{
			window.history.go(-1);
		}
	}
}

function changeWechatShare(type,param){
	if(typeof(wxSdkLoad) == "undefined"){
		return false;
	}
	
	if(type == 'plat'){
		param = {
			title: window.shareData.tTitle,
			desc:  window.shareData.tContent,
			link:  window.shareData.sendFriendLink + '&openid=' + userOpenid,
			imgUrl: window.shareData.imgUrl,
		};
	}
	wx.ready(function () {
		wx.onMenuShareAppMessage({
			title: param.title,
			desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
			type: '', // 分享类型,music、video或link，不填默认为link
			dataUrl: '', // 如果type是music或video，则要提供数据链接，默认为空
			success: function () { 
				shareHandle('frined');
			},
			cancel: function () { 
			}
		});
		wx.onMenuShareTimeline({
			title: param.title,
			link: param.link,
			imgUrl: param.imgUrl,
			success: function () { 
				shareHandle('frineds');
			},
			cancel: function () { 
			}
		});
		wx.onMenuShareQQ({
			title: param.title,
			desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
			success: function () { 
			   // 用户确认分享后执行的回调函数
			},
			cancel: function () { 
			   // 用户取消分享后执行的回调函数
			}
		});
		
		
		wx.updateAppMessageShareData({ 
      		title: param.title,
      		desc: param.desc,
			link: param.link,
			imgUrl: param.imgUrl,
		  	success: function () {
			  shareHandle('frined');
			}
   		 });
		wx.updateTimelineShareData({ 
      		title: param.title,
      		link: param.link,
			imgUrl: param.imgUrl,
			success: function () {
			  shareHandle('frineds');
			}
    	});
	});
}

/*判断IOS方法调用*/
var iosCommon = {
	getVersion: function () {
		var reg = /versioncode=(.*?),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return '85';
		}else{
			return arr[1];
		}
	},
	iosFunction:function(paramStr, paramObj){
		var pack_version = iosCommon.getVersion();
		var str = parseFloat(pack_version) >= 1000
		if ( str == true) {
			window.webkit.messageHandlers.appJSBridgeMethod.postMessage(paramObj);
		} else {
			$('body').append('<iframe id="iosIframe" src="pigcmso2o://'+paramStr+'" style="display:none;"></iframe>');
		}
		
	}
}