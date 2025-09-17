var nowIndex = 0;
var View={
	init:function(){

	},
	height:function(item,list,index){
		//偶数
		if(index*1%2==0){
			if(index*1+1!=list.length){
				  if(item.act_info && item.act_info.price){
						   return 'none'
				  }else{
					  if(list[index*1+1].act_info && list[index*1+1].act_info.price ){
						  return 'block'
					  }else{
						  return 'none'
					  }
				  }
			}else{
				return 'none'
			}
		}else{
				if(item.act_info && item.act_info.price){
					return 'none'
				}else{
					if(list[index*1-1].act_info && list[index*1-1].act_info.price ){
						return 'block'
					}else{
						return 'none'
					}
				}

		}
	}

}
window.View=View;
View.init()
$(function(){
	if($('.coupon_list').data('coupon') != ""){
		$.post(getCouponUrl,{ids:$('.coupon_list').data('coupon')},function(result){
			if(result.status == 1){
				laytpl($('#couponTpl').html()).render(result.info, function(html){
					$('.coupon_list').append(html);
					$('.couponRow').css({'margin-top':$('.coupon_list').width()*0.02+'px','height':(129*$('.couponRow').width()/222)+'px'});
					$('.addCategoryBtn').css('margin-top',$('.coupon_list').width()*0.02+'px');
					// $('.coupon_name').css({'margin-left':($('.couponRow').width()*56/222-14)/2,'margin-right':$('.couponRow').width()/10});
					$('.coupon_name').css({'margin-left':($('.couponRow').width()*56/222-14)/2});
					$('.coupon_use,.coupon_monery').css({'width':$('.couponRow').width()*134/222,'margin-left':$('.couponRow').width()*18/222});
					$('.coupon_monery').css({'margin-top':$('.couponRow').height()*30/129});
					$('.coupon_use').css({'margin-top':$('.couponRow').height()*26/129*2-18});
					
					// $('.coupon_list').css({'margin-top':'-'+($('.couponRow:eq(0)').height() + ($('.couponRow:eq(0)').height())*0.2)+'px'});
				});
				
				$('.couponRow').click(function(){
					if($(this).hasClass('coupon_on1')){
						return false;
					}
					var couponDom = $(this);
					if(hasLogin == false){
						layer.open({
							title:['登录提示','background-color:#FF658E;color:#fff;'],
							content:'您需要先登录才能领取优惠券，是否前往登录？',
							btn: ['确定','取消'],
							yes:function(){
								window.location.href = LoginUrl;
							}
						});
						return false;
					}
					if(userPhone == ''){
						layer.open({
							title:['绑定手机','background-color:#FF658E;color:#fff;'],
							content:'您需要先绑定手机号码才能继续领取优惠券，是否前往绑定？',
							btn: ['确定','取消'],
							yes:function(){
								window.location.href = BindPhoneUrl;
							}
						});
						return false;
					}
					layer.open({type: 2});
					$.post(receiveCouponUrl,{coupon_id:couponDom.data('id'),phone:userPhone},function(data, textStatus, xhr) {
						layer.closeAll();
						switch(data.error_code){	
							case 0:
								motify.log("领取优惠券成功");
								break;
							case 1:
								motify.log("领取优惠券发生错误");
								break;
							case 2:
								couponDom.addClass('coupon_on1');
								motify.log("该优惠券已过期");
								break;
							case 3:
								couponDom.addClass('coupon_on1');
								motify.log("该优惠券已被领完");
								break;
							case 4:
								couponDom.addClass('coupon_on1');
								motify.log("该优惠券只允许新用户领取");
								break;
							case 5:
								couponDom.addClass('coupon_on1');
								motify.log("您已经领取过了");
								break;
						}
					},"json");
				});
			}
		});
	}

    var  like_page = 0;
    var sids = '';
	var nomore = false;
	var isLoading = false;
	$('.addCategoryBtn li').click(function(){
		if($(this).hasClass('curr')){
			return false;
		}
		if(!$(this).data('product')){
			if(type==3){
				motify.log('该分类下暂无商品');
			}else{
				motify.log('该分类下暂无店铺');
			}
			return false;
		}
		$(this).addClass('curr').siblings().removeClass('curr');
        sids = $(this).data('product');
        like_page = 0;
        nomore = false;
        $("#enddate").hide();
        $("#moress").show();
        $('.productBox').html('');

        nowIndex = $(this).index();
		if($('.productRow.cat-'+nowIndex).size() > 0){
			$('.productRow').hide();
			$('.productRow.cat-'+nowIndex).show();
			return false;
		}
		if(type==3){
			motify.log("加载商品中...",0,{show:true});
		}else{
			motify.log("加载店铺中...",0,{show:true});
		}
		$.post(getShopUrl+'&page=' + like_page,{user_lat:user_lat,user_long:user_long,ids:$(this).data('product')},function(result){
			$('.productRow').hide();
			motify.clearLog();
			if(result.status == 1){
				laytpl($('#productTpl').html()).render(result.info, function(html){
					console.log(html);
					$('.productBox').append(html);
					$('.productRow.cat-'+nowIndex+':last').css('margin-bottom',0);
					if(!motify.checkMobile()){
						$('.productRow').removeClass('link-url');
					}
					if(type == 5){
						$('.productRow img').height($('.productRow img').width() * 500 / 900);
					}
				});
			}
            if(result.info.length < page_count){
                $("#moress").hide();
                $("#enddate").show();
                nomore = true;
            }
		});
	});
	$('.addCategoryBtn li:eq(0)').trigger('click');
	
	$(document).on('click','.hasMore',function(){
		$(this).toggleClass('showMore');
		return false;
	});
	
	setTimeout(function(){
		$('body').removeAttr('style').css('padding-bottom','0px');
		$('#containers').removeAttr('id');
	},300);
	
	if(!motify.checkMobile()){
		$(document).on('click','.productRow',function(){
			motify.log('请使用手机访问！');
			return false;
		});
	}
	
	var hideWebView_timer = setInterval(function(){
		if(typeof window.webkit == 'object' || typeof window.webkit == 'function'){
			if(motify.checkLifeApp() && motify.getLifeAppVersion() >= 50 && motify.checkIos()){
				iosCommon.iosFunction('hideWebViewHeader/true', {name:'hideWebViewHeader', show_head:'1'});
			}
			clearInterval(hideWebView_timer);
		}
	},200);
    
	$('#containers').css({'top':'0px'});
	$('#containers,#scroller').css({'position':'static'});
	
	$(window).scroll(function(){
		if(isLoading == false && nomore == false && $(document).scrollTop() >= $(document).height() - $(window).height() - 50){
			$("#moress").show();
			$("#enddate").hide();
			getRecommendList();
		}
	});

    var page_count	=	10;
    function getRecommendList() {
		isLoading = true;
        like_page = like_page + page_count;
        $.post(getShopUrl+'&page=' + like_page,{user_lat:user_lat,user_long:user_long,ids:sids},function(result){
            if (result.status == 1) {
                laytpl($('#productTpl').html()).render(result.info, function(html){
                    $('.productBox').append(html);
                    $('.productRow.cat-'+nowIndex+':last').css('margin-bottom',0);
                    if(!motify.checkMobile()){
                        $('.productRow').removeClass('link-url');
                    }
                });
                if(result.info.length < page_count){
                    $("#moress").hide();
                    $("#enddate").show();
                    nomore = true;
				}
            }
			isLoading = false;
        });
    }

});



function parseCoupon(obj,type){
	var returnObj = {};
	for(var i in obj){
		if(typeof(obj[i]) == 'object'){
			returnObj[i] = [];
			for(var j in obj[i]){
				returnObj[i].push('满'+currency_symbol+obj[i][j].money+'元减'+currency_symbol+obj[i][j].minus);
			}
		}else if(i=='invoice'){
			returnObj[i] = '满'+currency_symbol+obj[i]+'支持开发票，请在下单时填写发票抬头';
		}else if(i=='discount'){
			returnObj[i] = '店内全场'+obj[i]+'折';
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
					textObj[i] = '配送费'+returnObj[i].join(',');
					break;
			}
		}else if(i=='invoice' || i=='discount'){
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