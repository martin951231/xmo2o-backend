jQuery.cookie = function (key, value, options) {
    if (arguments.length > 1 && (value === null || typeof value !== "object")){
        options = jQuery.extend({}, options);
        if (value === null) {
            options.expires = -1;
        }
        if (typeof options.expires === 'number'){
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }
        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? String(value) : encodeURIComponent(String(value)),
            options.expires ? '; expires=' + options.expires.toUTCString() : '',
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};

var motify = {
	timer:null,
	/*shade 为 object调用 show为true显示 opcity 透明度*/
	log:function(msg,time,shade){
		$('.motifyShade,.motify').hide();
		if(motify.timer) clearTimeout(motify.timer);
		if($('.motify').size() > 0){
			$('.motify').show().find('.motify-inner').html(msg);
		}else{
			$('body').append('<div class="motify" style="display:block;"><div class="motify-inner">'+msg+'</div></div>');
		}
		if(shade && shade.show){
			if($('.motifyShade').size() > 0){
				$('.motifyShade').css({'background-color':'rgba(0,0,0,'+(shade.opcity ? shade.opcity : '0.3')+')'}).show();
			}else{
				$('body').append('<div class="motifyShade" style="display:block;background-color:rgba(0,0,0,'+(shade.opcity ? shade.opcity : '0.3')+');"></div>');
			}
		}
		if(typeof(time) == 'undefined'){
			time = 3000;
		}
		if(time != 0){
			motify.timer = setTimeout(function(){
				$('.motify').hide();
			},time);
		}
	},
	clearLog:function(){
		$('.motifyShade,.motify').hide();
	},
	checkMobile:function(){
		if(/(iphone|ipad|ipod|android|windows phone)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
    checkIos:function(){
        if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    },
    checkAndroid:function(){
        if(/(android)/.test(navigator.userAgent.toLowerCase())){
            return true;
        }else{
            return false;
        }
    },
	checkWeixin:function(){
		if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
	},
	checkWeixinApp:function(){
        if(window.__wxjs_environment === 'miniprogram' || (motify.checkWeixin() && /(miniprogram)/.test(navigator.userAgent.toLowerCase()))){
			return true;
		}else{
			return false;
		}
	},
    checkApp:function(){
        if(/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) || /(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || motify.checkWeixinApp()){
            return true;
        }else{
            return false;
        }
    },
	checkLifeApp:function(){
        if((/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase())) || /(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase())){
			return true;
		}else{
			return false;
		}
    },
	getLifeAppVersion:function(){
		var reg = /versioncode=(\d+),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			return 0;
		}else{
			return parseInt(arr[1]);
		}
	},
	getAndroidVersion:function(){
		var index = navigator.userAgent.indexOf("Android");
		if(index >= 0){
			var androidVersion = parseFloat(navigator.userAgent.slice(index+8));
			if(androidVersion > 1){
				return androidVersion;
			}else{
				return 100;
			}
		}else{
			return 100;
		}
	}
};

	
function goodList_before(){
	if($('.goodListBox.row .goodList').size() > 0){
		$('.goodListBox.row .goodList').each(function(i,item){
			var itemWidth = ($(item).width() - 15)/2.3;
			$(item).find('.goodItem').width(itemWidth);
			$(item).find('.goodImage img').height(itemWidth*500/900);
		});
	}
	if($('.goodListBox.list .goodList').size() > 0){
		$('.goodListBox.list .goodList').each(function(i,item){
			console.log($(item).find('.goodItem').size());
			if($(item).find('.goodItem').size() % 2 == 1){
				$(item).find('.goodItem').eq(-2).css({'margin-bottom':'15px','padding-bottom':'15px'})
			}
			var itemWidth = ($(item).width() - 15)/2;
			$(item).find('.goodItem').width(itemWidth);
			$(item).find('.goodImage img').height(itemWidth*500/900);
		});
	}
}

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

function forceRefresh () {
	var u = navigator.userAgent;
    var isAndroid = u.indexOf('Android') > -1 || u.indexOf('Linux') > -1; //android终端或者uc浏览器
    var isiOS = !!u.match(/\(i[^;]+;( U;)? CPU.+Mac OS X/); //ios终端
    if(isAndroid){ //android
        window.addEventListener('pageshow', function(event) {
            if(event.persisted) location.reload();  
        });
    }else if(isiOS){ // ios
        var browserRule = /^.*((iPhone)|(iPad)|(Safari))+.*$/;
        if (browserRule.test(navigator.userAgent)) {
            window.onpageshow = function(event) {
                if (event.persisted) {
                    window.location.reload()
                }
            }
        }
    }
}

$(function(){
	$(document).on('click', '.link', function(){
		var url = $(this).data('url');
		var title = $(this).data('title');
		if(!title){
			title = '打开新页面';
		}
		if($(this).hasClass('newWin') && url.indexOf('javascript')===-1 && motify.checkWeixinApp()){
			if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
				url = 'https://' + document.domain + url;
			}
			wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent(title)});
		}else{
			location.href=url;
		}
		return false
	})

	$(document).on('click', '.plus, #pageIndex .addIcon .cart', function(){
		var end_time = 0
		var good_id = $(this).data('goods_id'); 
		var good_price = parseFloat($(this).parents('.goodsItem').data('price'));
		var good_num = $(this).closest('.price-box').find('.number').html();
		good_num = parseInt(good_num);

		if ($(this).parents('.info').find('.future').length > 0) {
			end_time = $(this).parents('.info').find('.tag.future').data('end_time')
			var current_time = Date.parse(new Date());
			
			if ((end_time+86400) * 1000 <= current_time) {
				weui.alert('当前商品已结束预售，再看看别的商品吧~', {
				    title: '提示',
				    buttons: [{
				        label: '好的',
				        type: 'primary',
				        onClick: function(){ console.log('ok') }
				    }]
				});
				return false
			}
		}

		console.log('33333', end_time)
		
		var max_num = $(this).parents('.goodsItem').data('max_num')
		max_num = parseFloat(max_num);
		 

		if ( $(this).data('sell_count') >= 0) {
			var sell_out = $(this).data('sell_count') * 1

			if (max_num == good_num) {
				weui.alert('已达库存上限', {
				    title: '提示',
				    buttons: [{
				        label: '好的',
				        type: 'primary',
				        onClick: function(){ console.log('ok') }
				    }]
				});
				return false
			}
		}

		if(good_num+1<=max_num){
			var now_good_num = good_num+1;
			$(this).parents('.price-box').find('.number').html(now_good_num)
			
			goodsCart[good_id] = {'num':now_good_num,'good_price':good_price.toFixed(2)};
			all_money += good_price
			num +=1;
			goodsCart['all'] = {'all_money':parseFloat(all_money).toFixed(2),'all_num':num};
			
			if (now_good_num == 1) {
				$(this).parents('.addIcon').addClass('hidden').siblings('.addBox').removeClass('hidden')
			}
		}
		
		
		$('.price_total').html(parseFloat(all_money).toFixed(2))
		$('.goods_num').html(num)
		$('#cate_count').html(num)
		$.cookie('VillagegoodsCart', JSON.stringify(goodsCart));
		save_goods_cart();
		return false
	});


	$(document).on('click', '.minus', function(){
		var obj = $(this)
		if ( nowPage == 'pageCart' && $(this).siblings('.number').html() == 1 ) {
			weui.confirm('确认删除该商品？', {
			    title: '提示',
			    buttons: [{
			        label: '取消',
			        type: 'default',
			        onClick: function(){ console.log('取消删除商品') }
			    }, {
			        label: '确定',
			        type: 'primary',
			        onClick: function(){
			        	minusFun(obj)
			        }
			    }]
			});
		} else {
			minusFun(obj)
		}

		return false
	});



    $(document).on('click', '.cycle_plus', function(){ 
		$('.cycle_minus').removeClass('gray')
        var end_time = 0;
        var good_id = $(this).data('goods_id');
        var good_num = $(this).closest('.calc-box').find('.number').html();
        good_num = parseInt(good_num);
        console.log('good_num', good_num)

		end_time = $(this).data('end_time');
        console.log('end_time', end_time);
		var current_time = Date.parse(new Date());
        console.log('current_time', current_time);

		if ((end_time+86400) * 1000 <= current_time) {
			weui.alert('当前商品已结束预售，再看看别的商品吧~', {
				title: '提示',
				buttons: [{
					label: '好的',
					type: 'primary',
					onClick: function(){ console.log('ok') }
				}]
			});
			return false
		}


        var max_num = $(this).parents('.calc-box').data('max_num');
        max_num = parseFloat(max_num);
        console.log('max_num', max_num);
        console.log('sell_count', $(this).data('sell_count'));


        if ( $(this).data('sell_count') >= 0) {
            var sell_out = $(this).data('sell_count') * 1;

            if (max_num == good_num) {
                weui.alert('已达库存上限', {
                    title: '提示',
                    buttons: [{
                        label: '好的',
                        type: 'primary',
                        onClick: function(){ console.log('ok') }
                    }]
                });
                return false
            }
        }

        if(good_num+1<=max_num){
            var now_good_num = good_num+1;
            $(this).parents('.calc-box').find('.number').html(now_good_num);
        }
        return false
	}); 
	
	if($('.cycle_minus').siblings('.number').html()==1){ 
		$('.cycle_minus').addClass('gray')
	}else{
		$('.cycle_minus').removeClass('gray')
	}

});


$(document).on('click', '.cycle_minus', function(){
	var obj = $(this) 
    if ($(this).siblings('.number').html() > 1 ) {
		cycleMinusFun(obj);
		$(this).removeClass('gray');
    }else{
		$(this).addClass('gray');
	} 
	if($('.cycle_minus').siblings('.number').html()==1){ 
		$('.cycle_minus').addClass('gray')
	} 

    return false
});

function  cycleMinusFun (obj) {
	var good_id = obj.data('goods_id');
	var good_num = obj.siblings('.number').html();
	good_num = parseInt(good_num);
    var max_num = obj.parents('.calc-box').data('max_num');
	max_num = parseFloat(max_num)

	if(good_num-1>=0){
		var now_good_num = good_num-1;
		obj.siblings('.number').html(now_good_num);
		obj.addClass('gray');
	} 
}

function  minusFun (obj) {
    var good_id = obj.data('goods_id');
    var good_price = parseFloat(obj.parents('.goodsItem').data('price'));
    var good_num = obj.siblings('.number').html();
    good_num = parseInt(good_num);
    var max_num = obj.parents('.goodsItem').data('max_num')
    max_num = parseFloat(max_num)

    if(good_num-1>=0){
        var now_good_num = good_num-1;
        obj.siblings('.number').html(now_good_num)
        all_money =parseFloat(all_money).toFixed(2)- parseFloat(good_price).toFixed(2);
        num -=1;
        goodsCart[good_id] = {'num':now_good_num,'good_price':good_price.toFixed(2)};
        goodsCart['all'] = {'all_money':parseFloat(all_money).toFixed(2),'all_num':num};
	} 
    $('.price_total').html(parseFloat(all_money).toFixed(2))
    $('.goods_num').html(num)
    $('#cate_count').html(num)
    if ( good_num-1 == 0) {
        $('#pageIndex #good_'+good_id).closest('.addBox').addClass('hidden').siblings('.addIcon').removeClass('hidden')

        if (nowPage == 'pageCart') {
            obj.parents('.goodsItem').remove();
        }
    }

    if (num == 0) {
        $('#pageCart .emptyBox').removeClass('hidden')
    }

    $.cookie('VillagegoodsCart', JSON.stringify(goodsCart));
    save_goods_cart();
}


function actionsheet(arr){
	if(typeof arr != 'object' || typeof arr.itemList != 'object' || arr.itemList.length < 1){
		return false;
	}
	if(!arr.itemColor || arr.itemColor == ''){
		arr.itemColor = '#000000';
	}
	if(!arr.title || arr.title == ''){
		arr.title = '选择操作';
	}
	var html = '<div id="actionsheet" class="layui-m-layer layui-m-layer0">';
		html+= '	<div class="layui-m-layershade"></div>';
		html+= '		<div class="layui-m-layermain">';
		html+= '			<div class="layui-m-layersection">';
		html+= '				<div class="layui-m-layerchild layui-m-layer-footer layui-m-anim-up">';
		html+= '					<div class="layui-m-layercont">'+arr.title+'</div>';
		html+= '						<div class="layui-m-layerbtn">';
		for(var i in arr.itemList){
			html+= '						<span class="item" data-index="' + i + '" style="color:'+arr.itemColor+';">' + arr.itemList[i] +'</span>';
		}
		html+= '							<span class="cancel">取消</span>';
		html+= '						</div>';
		html+= '					</div>';
		html+= '			</div>';
		html+= '	</div>';
		html+= '</div>';
	$('body').append(html);
	$('#actionsheet .layui-m-layershade,#actionsheet .cancel').click(function(){
		$('#actionsheet').remove();
	});
	$('#actionsheet .layui-m-layerbtn .item').click(function(){
		if(typeof arr.success == 'function'){
			var status = arr.success({index:$(this).data('index'),text:$.trim($(this).text())});
			if(status !== false){
				$('#actionsheet').remove();
			}
		}else{
			$('#actionsheet').remove();
		}
	});
}

function save_goods_cart(){
	var cate_num = 0;
	for(i in goodsCart){
		cate_num++;
	} 
	goodsCart['all']['cate_num'] = cate_num-1;
	$.post(save_cart_url,{start_id:start_id,cart_cookie:cart_cookie,goodsCart:goodsCart,leader_id:leader_id},function(data){
		
		for(var item in goodsCart) {
			if (goodsCart[item].num == 0) {
				delete goodsCart[item]
			}
		}
		$.cookie('VillagegoodsCart', JSON.stringify(goodsCart));
		
		if (goodsCart['all']['all_num'] == 0) {
			$('#pageCart .emptyBox').removeClass('hidden')
		}

		if(!data.status){
			weui.alert(data.info, {
			    title: '提示',
			    buttons: [{
			        label: '好的',
			        type: 'primary',
			        onClick: function(){
                        if(data && data.return && data.return.error_code==2){
							var sell_out = data.return.sell_out;
							for(var i=0;i<sell_out.length;i++){

								var goods_id_ = sell_out[i].goods_id;
								$('#goods_'+goods_id_).parents('.goodsItem').remove();
								$('#good_'+goods_id_).html(0);
								$('#good_'+goods_id_).parents('.goodsItem').append('<div class="sale-out"><div>已售罄</div></div>');
							
								
								goodsCart['all']['all_money'] -= goodsCart[goods_id_]['num']*goodsCart[goods_id_]['good_price'];
								goodsCart['all']['all_num'] -=goodsCart[goods_id_]['num'];
								goodsCart['all']['cate_num'] -=1;
								goodsCart[goods_id_].num = 0
							
							}

							$('.price_total').html(goodsCart['all']['all_money'].toFixed(2))
							$('.goods_num').html(goodsCart['all']['all_num'])
							$('#cate_count').html(goodsCart['all'].cate_num)
							$.cookie('VillagegoodsCart', JSON.stringify(goodsCart));
							save_goods_cart()
							
						} else {
                            realod_info();
						}
			        }
			    }]
			});
		}
	},'json');
}

function openNewWin(url,title){
	if(url.indexOf('javascript')===-1 && motify.checkWeixinApp()){
		if(url.indexOf('http://')===-1 && url.indexOf('https://')===-1){
			url = 'https://' + document.domain + url;
		}
		if(!title){
			title = '打开新页面';
		}
		wx.miniProgram.navigateTo({url:'/pages/webview/webview?webview_url='+encodeURIComponent(url)+'&webview_title='+encodeURIComponent(title)});
	}else{
		location.href=url;
	}
}
function realod_info(){
	location.reload();
    /* var {search,href} = window.location;
    href = href.replace(/&?time=(\d+)/g,'');
    window.location.href = href+(search?'&':'?')+"time="+new Date().getTime(); */
};