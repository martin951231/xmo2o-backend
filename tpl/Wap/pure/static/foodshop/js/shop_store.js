
var all_goods='' 
var goodsCart = [], goodsNumber = 0, goodsCartMoney = 0,goodsExtraPrice=0; 
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
/** laytpl-v1.1 */

;!function(){"use strict";var f,b={open:"{{",close:"}}"},c={exp:function(a){return new RegExp(a,"g")},query:function(a,c,e){var f=["#([\\s\\S])+?","([^{#}])*?"][a||0];return d((c||"")+b.open+f+b.close+(e||""))},escape:function(a){return String(a||"").replace(/&(?!#?[a-zA-Z0-9]+;)/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;").replace(/'/g,"&#39;").replace(/"/g,"&quot;")},error:function(a,b){var c="Laytpl Error：";return"object"==typeof console&&console.error(c+a+"\n"+(b||"")),c+a}},d=c.exp,e=function(a){this.tpl=a};e.pt=e.prototype,e.pt.parse=function(a,e){var f=this,g=a,h=d("^"+b.open+"#",""),i=d(b.close+"$","");a=a.replace(/[\r\t\n]/g," ").replace(d(b.open+"#"),b.open+"# ").replace(d(b.close+"}"),"} "+b.close).replace(/\\/g,"\\\\").replace(/(?="|')/g,"\\").replace(c.query(),function(a){return a=a.replace(h,"").replace(i,""),'";'+a.replace(/\\/g,"")+'; view+="'}).replace(c.query(1),function(a){var c='"+(';return a.replace(/\s/g,"")===b.open+b.close?"":(a=a.replace(d(b.open+"|"+b.close),""),/^=/.test(a)&&(a=a.replace(/^=/,""),c='"+_escape_('),c+a.replace(/\\/g,"")+')+"')}),a='"use strict";var view = "'+a+'";return view;';try{return f.cache=a=new Function("d, _escape_",a),a(e,c.escape)}catch(j){return delete f.cache,c.error(j,g)}},e.pt.render=function(a,b){var e,d=this;return a?(e=d.cache?d.cache(a,c.escape):d.parse(d.tpl,a),b?(b(e),void 0):e):c.error("no data")},f=function(a){return"string"!=typeof a?c.error("Template not found"):new e(a)},f.config=function(a){a=a||{};for(var c in a)b[c]=a[c]},f.v="1.1","function"==typeof define?define(function(){return f}):"undefined"!=typeof exports?module.exports=f:window.laytpl=f}();

/* 简单的消息弹出层 */
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
	}
};

$(function(){  
	 
    $("#nav a").off("click").on("click",function(){ 
       var index = $(this).index(); 
       $(this).addClass("on").siblings().removeClass("on");
       $("#contentBox .box").eq(index).addClass("active").siblings().removeClass("active");
     }); 
      
	
	 $(document).on('click',".pro_leftNav li",function(e){   
		$('.pro_con').animate({scrollTop:$('.foodright-'+$(this).data('sort_id')).offset().top-$('.pro_con').offset().top+$('.pro_con').scrollTop()},500) ;
	 }) 
	// 
	$(".pro_con").scroll(function(){ 
		var top = $(".pro_con").scrollTop();
		var menu = $(".pro_leftNav");
		var item = $(".pro_con .dl");
		var onid = "";
		item.each(function() {
			var n = $(this);
			var itemtop = $('.foodright-'+$(this).data('sort_id')).offset().top-$('.pro_con').offset().top+$('.pro_con').scrollTop();
			if (top > itemtop - 100) {
				onid = n.data('sort_id');
			}
		});
		var link = menu.find(".on");
		link.removeClass("on");
		menu.find("[data-sort_id="+onid+"]").addClass("on");
	});
 
	$(document).on('click', '.span_cart span', function(){ 
		var this_num = $(this).siblings("input").val(), name = $(this).data('name'), price = parseFloat($(this).data('price')), goods_id = parseInt($(this).data('id')), goodsCartKey = $(this).data('index'),goods_extra_price = parseFloat($(this).data('extra_pay_price')),goods_extra_price_name = $(this).data('extra_price_name');
		var stockNum = parseInt($(this).data('stock_num'));
		if ($(this).attr('class') == 'fr add') { 
			if (this_num*1+1 > stockNum && stockNum != -1) {
				motify.log('最多可以选择' + stockNum + '份');
				return false;
			}

			this_num ++;
			goodsNumber ++;
			goodsCartMoney += price;
			if(goods_extra_price>0){
				goodsExtraPrice +=goods_extra_price;
			}
		} else {
			this_num --;
			goodsNumber --;
			goodsCartMoney -= price;
			if(goods_extra_price>0){
				goodsExtraPrice-=goods_extra_price;
			}
		}
		if (this_num > stockNum && stockNum != -1) {
			motify.log('最多可以选择' + stockNum + '份'); 
			return false;
		}
		goodsCartMoney = parseFloat(goodsCartMoney.toFixed(2));
		var this_index = null;
		for (var i in goodsCart) {
			var old_goodsCartKey = goodsCart[i].goods_id;
			if (goodsCart[i].type == 'group') {
				if (goodsCart[i]['params'].length) {
					for (var pi in goodsCart[i]['params']) {
						old_goodsCartKey += '_' + goodsCart[i]['params'][pi].goods_id;
					}
				}
			} else {
				if (goodsCart[i]['params'].length) {
					for (var pi in goodsCart[i]['params']) {
						if (goodsCart[i]['params'][pi].type == 'spec') {
							old_goodsCartKey += '_s_' + goodsCart[i]['params'][pi].id;
						}
						if (goodsCart[i]['params'][pi]['data']!="undifined" ) {
							if (goodsCart[i]['params'][pi]['data'].length ) {
								for (var di in goodsCart[i]['params'][pi]['data']) {
									old_goodsCartKey += '_v_' + goodsCart[i]['params'][pi]['data'][di].id;
								}
							}
						}
					}
				}
			}
			if (goodsCartKey == old_goodsCartKey) {
				this_index = i;
				break;
			}
		}
		
		if (this_index != null) {
			goodsCart[this_index].num = this_num;
		} else {
			if(goods_extra_price>0){
				goodsCart.push({
					'goods_id':goods_id,
					'num':this_num,
					'type':'only',
					'name':name,
					'price':price,
					'stockNum':stockNum,
					'extra_price':goods_extra_price,
					'extra_price_name':goods_extra_price_name,
					'params':''});
			}else{
				goodsCart.push({
					'goods_id':goods_id,
					'num':this_num,
					'type':'only',
					'name':name,
					'price':price,
					'stockNum':stockNum,
					'params':''});
			}
		}
		if ( stringifyCart() == false) {
			return false;
		}
		
		$('.goods_' + goodsCartKey).find("input").val(this_num); 
		if (this_num > 0) {
			$(this).siblings().show();
			if (!$('.cartBox_ul').find('.goods_' + goodsCartKey).length) {
				if(goods_extra_price>0){
					price +='+'+goods_extra_price+goods_extra_price_name;
				}
				var cart_goods_html = ''; 
				cart_goods_html += '<li class="flex goods_' + goodsCartKey + '">';
					cart_goods_html += '<div class="cartBox_ul_li1">' + name + '</div>';
					cart_goods_html += '<div class="cartBox_ul_li2">'+Currency_symbol+price+'</div>';
					cart_goods_html += '<div class="cartBox_ul_li3">';
						cart_goods_html += '<div class="span_cart">';
							 cart_goods_html += ' <span class="fl minus" style="background:'+curColor+';border-color:'+curColor+';" style="background:'+curColor+';border-color:'+curColor+';"data-stock_num="' + stockNum + '" data-price="' + price + '" data-id="' + goods_id + '" data-index="' + goodsCartKey + '" data-name="' + name + '"'+ 'data-extra_pay_price="' + goods_extra_price +'" data-extra_price_name="' + goods_extra_price_name + '"></span> ';
							 cart_goods_html += ' <span class="fr add" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="' + stockNum + '" data-price="' + price + '" data-id="' + goods_id + '" data-index="' + goodsCartKey + '" data-name="' + name + '"'+ 'data-extra_pay_price="' + goods_extra_price +'" data-extra_price_name="' + goods_extra_price_name + '"></span>  ';
							 cart_goods_html += ' <input type="text" style="display:block"  class="fr span_cartNum num" readOnly="true" value="' + this_num + '"   />';
							 cart_goods_html += ' <div class="clear"></div>';
						cart_goods_html += '</div> '; 
					cart_goods_html += '</div>';
				cart_goods_html += '</li> ';  
				$('.cartBox_ul').append(cart_goods_html);
			}
		} else {
			$('.goods_' + goodsCartKey).find('.add').siblings().hide();
			$('.cartBox_ul').find('.goods_' + goodsCartKey).remove();
			if (!$('.cartBox_ul').find('li').length) {
				$(".Cart").slideUp();
				$(".mask").hide();
			}
		}

		if (goodsNumber > 0) {
			$(".floor").addClass("floorOn");
			$(".qty").show(500).text(goodsNumber);
			if(goodsExtraPrice>0){
				$('#total_price').text(goodsCartMoney+'+'+goodsExtraPrice+goods_extra_price_name);
			}else{
				$('#total_price').text(goodsCartMoney);
			}
			
		} else {
			goodsCart = [];
			$(".floor").removeClass("floorOn");
			$(".qty").hide(500);
			$('#total_price').text(0);
		}
	});
	
		
   	//清空购物车
   	$(document).on('click', '.Cart_top', function(){
   		if ( stringifyCart() == false) {
   		 	return false;
   		}
   		$(".cartBox_ul").find("li").remove();
   		$(".floor").removeClass("floorOn");
   		$(".qty").hide(500);
   		$('#total_price').text(0);
   		$(".Cart").slideUp();
   		$(".mask").hide(); 
   		$('.pro_con .ul_pro_con .span_cart').find('input').val(0);
   		$('.pro_con .ul_pro_con .span_cart').find('.add').siblings().hide();
   		goodsNumber = 0;
   		goodsCartMoney = 0;
   		goodsCart = [];
   	}); 
 
	if($('.cart_numboxSm').text() > 0){
		$('.cart_numbox').css('background','#29c7a2')
	}
	
	// 点击购物车弹窗
	$('.cart_numbox').click(function(){
		if($('.cart_numboxSm').text() > 0){
			$('.mask').show()
			$('.cartBox').show() 
		}
		
	}) 
	
	 // 多规格 
	$(document).on('click',".selectSpect",function(e){  
		$(this).siblings('.TcancelT').show().slideDown()
		$('.mask').show()
	})
	//关闭规格弹出
	$(document).on('click',".gb",function(){ 
		$(this).parents('.Tcancel').hide().slideUp();
		$(".mask").hide();
	});
	
	//购物效果 
	$(document).on('click',".trolley",function(){ 
		if(parseInt($('.qty').text())>0){
			$(".Cart").slideDown();
			$(".mask").show(); 
		}
		
	});
	
 
	
  });


// window.onscroll = function(){   
//     var t = document.documentElement.scrollTop || document.body.scrollTop;  
//     if( t >= 300 ) { 
//         $('#top_div').addClass('fixed')
//     } else { 
//         $('#top_div').removeClass('fixed') 
//     } 
// } 

//规格中选项的选择
	$(document).on('click', '.fications li', function(){ 
		var father_obj = $(this).parents('.fications');
		var type = father_obj.data('type'), id = father_obj.data('id'), name = father_obj.data('name'), num = father_obj.data('num');
		var this_id = $(this).data('id'), this_name = $(this).data('name'), goods_id = parseInt($(this).data('goods_id'));
		if (num == 1) {
			$(this).addClass('on').siblings('li').removeClass('on');
		} else {
			$(this).toggleClass("on");
			if (father_obj.find('.on').length > num) {
				$(this).removeClass("on");
				motify.log('最多可以选择' + num + '个');
				return false;
			}
		}
		var select_html = '已选：';
		var spec_ids = [];
		$(this).parents('.TcancelT').find('.fications').each(function(dom){
			$(this).find('li').each(function(){
				if ($(this).hasClass('on')) {
					select_html += '<span>' + $(this).data('name') + '</span>';
					if ($(this).data('type') == 'spec') {
						spec_ids.push($(this).data('id'))
					}
				}
			});
		});

		$(this).parents(".TcancelT_zh").siblings(".Selected").html(select_html);
		if (type == 'spec' && spec_ids.length > 0) {
			// var ALL_GOODS = $.parseJSON(all_goods);
			var ALL_GOODS = all_goods;
			console.log(ALL_GOODS)
			var price = 0, stockNum = 0;
			if (typeof(ALL_GOODS[goods_id][spec_ids.join('_')]) != 'undefined') {
			    var tData = ALL_GOODS[goods_id][spec_ids.join('_')];
				price = ALL_GOODS[goods_id][spec_ids.join('_')]['price'];
				stockNum = ALL_GOODS[goods_id][spec_ids.join('_')]['stock_num'];
				if (tData.properties.length > 0) {
				    for (var i in tData.properties) {
				        $('#properties_' + tData.properties[i].id).data('num', tData.properties[i].num);
				    }
				}  
			}
			$(this).parents('.TcancelT').find('.TcancelT_topL .price').html('<i>'+Currency_symbol+'</i>' + price);
			if (stockNum >= 0 && stockNum < 10) {
			    $(this).parents('.TcancelT').find('.TcancelT_topL .stock').html('剩余：' + stockNum);
			} else {
			    $(this).parents('.TcancelT').find('.TcancelT_topL .stock').html('');
			}
			
			$(this).parents('.TcancelT').find('.join').data('price', price);
			$(this).parents('.TcancelT').find('.join').data('stock_num', stockNum);
		}
	}); 
	
//提交规格选中的
	$(document).on('click', '.TcancelT .join', function(){   
		var goodsCartKey = $(this).data('goods_id'), goods_id = parseInt($(this).data('goods_id')), name = $(this).data('name'), price = parseFloat($(this).data('price'));
		var stockNum = parseInt($(this).data('stock_num'));
		var flag = false;
		var params = [];
		$(this).parents('.TcancelT').find('.fications').each(function(dom){
			var id = $(this).data('id'), name = $(this).data('name'), type = $(this).data('type'), num = $(this).data('num');
			var temp = {
					'type':type,
					'id':id,
					'name':name,
					'data':[]
			};
			var select_num = 0;
			$(this).find('li').each(function(){
				if ($(this).hasClass('on')) {
					temp['data'].push({'id':$(this).data('id'), 'name':$(this).data('name')});
					select_num ++;
				}
			});
			if (select_num == 0 && type == 'spec') {
				flag = true;
				motify.log('必须在' + name + '下选择一项'); 
				return false;
			} else if (select_num > num) {
				flag = true;
				motify.log('必须在' + name + '下最多可选' + num + '项'); 
				return false;
			}
			params.push(temp);
//			details.push(goodsDetail);
		});
		if (flag) return false;
		var names_str = '';
		if (params.length) {
			for (var pi in params) {
				if (params[pi].type == 'spec') {
					goodsCartKey += '_s_' + params[pi].id;
				}
                if (params[pi]['data'] != "undefined") {
                    if (params[pi]['data'].length) {
						for (var di in params[pi]['data']) {
							goodsCartKey += '_v_' + params[pi]['data'][di].id;
							if (names_str.length > 0) {
								names_str += ',' + params[pi]['data'][di].name
							} else {
								names_str += params[pi]['data'][di].name;
							}
						}
					}
				}
			}
		}
		
		if ($('.cartBox_ul').find('.goods_' + goodsCartKey).length) {
			var this_num = parseInt($('.goods_' + goodsCartKey).find("input").val());
		} else {
			var this_num = 0;
		}
		
		if (this_num*1+1 > stockNum && stockNum != -1) {
		    motify.log('最多可以选择' + stockNum + '份');
			// console.log('最多可以选择' + stockNum + '份')
		    return false;
		}
		this_num ++;
		goodsNumber ++;
		goodsCartMoney += price;
		goodsCartMoney = parseFloat(goodsCartMoney.toFixed(2));
		
		var this_index = null;
		for (var i in goodsCart) {
		    if (goodsCart[i].type == 'group') continue;
			var old_goodsCartKey = goodsCart[i].goods_id;
			if (goodsCart[i]['params'].length) {
				for (var pi in goodsCart[i]['params']) {
					if (goodsCart[i]['params'][pi].type == 'spec') {
						old_goodsCartKey += '_s_' + goodsCart[i]['params'][pi].id;
					}
					if (goodsCart[i]['params'][pi]['data']!='undefined') {
						if (goodsCart[i]['params'][pi]['data'].length) {
							for (var di in goodsCart[i]['params'][pi]['data']) {
								old_goodsCartKey += '_v_' + goodsCart[i]['params'][pi]['data'][di].id;
							}
						}
					}
				}
			}
			if (goodsCartKey == old_goodsCartKey) {
				this_index = i;
				break;
			}
		}
		
		if (this_index != null) {
			goodsCart[this_index].num = this_num;
		} else {
			goodsCart.push({
				'goods_id':goods_id,
				'type':'only',
				'num':this_num,
				'name':name,
				'price':price,
                'stockNum':stockNum,
				'params':params});
		}
		if ( stringifyCart() == false) {
		 	return false;
		} 
		
		$('.goods_' + goodsCartKey).find("input").val(this_num);
		if (this_num > 0) {
			if (!$('.cartBox_ul').find('.goods_' + goodsCartKey).length) {
				var cart_goods_html = ''; 
				cart_goods_html += '<li class="flex goods_' + goodsCartKey + '">';
					cart_goods_html += '<div class="cartBox_ul_li1">' + name + '</div>';
					cart_goods_html += '<div class="cartBox_ul_li2">'+Currency_symbol+price+'</div>';
					cart_goods_html += '<div class="cartBox_ul_li3">';
						cart_goods_html += '<div class="span_cart">';
							 cart_goods_html += ' <span class="fl minus" style="background:'+curColor+';border-color:'+curColor+'; display:block" data-stock_num="' + stockNum + '" data-price="' + price + '" data-id="' + goods_id + '" data-index="' + goodsCartKey + '" data-name="' + name + '" ></span> ';
							 cart_goods_html += ' <span class="fr add" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="' + stockNum + '" data-price="' + price + '" data-id="' + goods_id + '" data-index="' + goodsCartKey + '" data-name="' + name + '"></span>  ';
							 cart_goods_html += ' <input type="text"  style="display:block" class="fr span_cartNum num" readOnly="true" value="' + this_num + '"   />';
							 cart_goods_html += ' <div class="clear"></div>';
						cart_goods_html += '</div> '; 
					cart_goods_html += '</div>';
				cart_goods_html += '</li> ';   
				$('.cartBox_ul').append(cart_goods_html);
			} 			
		} 
		if (goodsNumber > 0) {
			$(".floor").addClass("floorOn");
			$(".qty").show(500).text(goodsNumber);
			$('#total_price').text(goodsCartMoney);
		}
		$(this).parents(".TcancelT").slideUp();
		$(".mask").hide();
	});
		
// 菜品数据
foodData() 
function foodData(){
	$.post('/appapi.php?c=Foodshop&a=getGoodsList',{store_id:store_id},function(result){
		console.log(result.result.goods_list) 
		console.log(curColor)
		all_goods = result.result.all_goods
		var goods_list = result.result.goods_list
		var pro_leftNavHtml = ''
		var ul_pro_conHtml = ''
		if(goods_list && goods_list.length >0){
			goods_list.forEach(function(element,index,array){  
				var  goods_listArr =  element.goods_list
				ul_pro_conHtml+='<div data-sort_id='+element.sort_id+' class="dl foodright-'+element.sort_id+'">'
					ul_pro_conHtml+='<div class="pro_conTil">'+element.sort_name+'</div>'
					ul_pro_conHtml+='<ul class="ul_pro_con"> '
					    if(goods_listArr && goods_listArr.length > 0){ 
						 goods_listArr.forEach(function(elementArr){
							if(element.sort_id != '-1'){
								ul_pro_conHtml+='<li class="goods_'+elementArr.goods_id+'">'   
							}else{
								ul_pro_conHtml+='<li class="goods_'+elementArr.id+'">' 
							} 
							if(elementArr.pic_arr && elementArr.pic_arr.length >0 && element.sort_id !=-1){
								ul_pro_conHtml+='<div class="pro_img" > <img src="'+elementArr.pic_arr[0].url.image+'" style="width:64px; height:50px" />' 
								ul_pro_conHtml+='</div> '
							 }  
							 if(element.sort_id == '-1'){
								ul_pro_conHtml+=' <div class="pro_details" style="padding-left:0">' 
							 }else{
								ul_pro_conHtml+=' <div class="pro_details">' 
							 } 
								ul_pro_conHtml+=' <h4 class="p1">'+elementArr.name+'</h4> ' 
								ul_pro_conHtml+=' <p class="p3">'+Currency_symbol+elementArr.price+'</p>'
								ul_pro_conHtml+='</div>' 
							 if(elementArr.spec_list){
								   ul_pro_conHtml+='<div class="spec_list_span selectSpect" style="background:'+curColor+';" >多规格</div>'
								   ul_pro_conHtml+='<section class="Tcancel TcancelT" style="display: none;">'
								    ul_pro_conHtml+=' <div class="TcancelT_top clr"> '
								     ul_pro_conHtml+=' <div class="TcancelT_topL"> '
								      ul_pro_conHtml+=' <h2>'+elementArr.name+'</h2>' 
								       ul_pro_conHtml+='<span class="price"><i>'+Currency_symbol+'</i>'+elementArr.price+'</span>'
								      ul_pro_conHtml+=' <span class="stock"> </span> '
								      ul_pro_conHtml+='</div> '
								      ul_pro_conHtml+='<a href="javascript:void(0)" class="gb"></a> '
								     ul_pro_conHtml+='</div> '
								    ul_pro_conHtml+=' <div class="TcancelT_zh"> '
								     ul_pro_conHtml+='<div class="TcancelT_n"> '
								      ul_pro_conHtml+=' <div class="TcancelT_list"> '
											for(var x in elementArr.spec_list){
											   ul_pro_conHtml+=' <h2>'+elementArr.spec_list[x].name+'</h2> '
												ul_pro_conHtml+='<div class="fications" data-id="'+x+'" data-num="1" data-name="'+elementArr.spec_list[x].name+'" data-type="spec"> '
												 ul_pro_conHtml+='<ul class="clr"> ' 
													for(var y in elementArr.spec_list[x].list){
													 ul_pro_conHtml+=' <li data-id="'+elementArr.spec_list[x].list[y].id+'" data-name="'+elementArr.spec_list[x].list[y].name+'" data-type="spec" data-goods_id="'+elementArr.goods_id+'">'+elementArr.spec_list[x].list[y].name+'</li>  ' 
													} 
												 ul_pro_conHtml+='</ul> '
											   ul_pro_conHtml+=' </div> '  
											};
											if(elementArr.properties_list){
												for(var p in elementArr.properties_list){
													ul_pro_conHtml+=' <h2>'+elementArr.properties_list[p].name+'</h2> '
													ul_pro_conHtml+='<div class="fications" data-id="40" data-num="1" data-name="'+elementArr.properties_list[p].name+'" data-type="properties"> '
													 ul_pro_conHtml+='<ul class="clr"> ' 
														for(var q in elementArr.properties_list[p].val){
														 ul_pro_conHtml+=' <li data-id="'+p+'" data-name="'+elementArr.properties_list[p].val[q]+'" data-type="properties" data-goods_id="'+elementArr.goods_id+'">'+elementArr.properties_list[p].val[q]+'</li>  ' 
														} 
													 ul_pro_conHtml+='</ul> '
													ul_pro_conHtml+=' </div> '  
												};   
											}
										  
								      ul_pro_conHtml+=' </div> '
								     ul_pro_conHtml+=' </div> '
								    ul_pro_conHtml+=' </div> '
								    ul_pro_conHtml+=' <div class="Selected">   已选：   <span></span> '
								     ul_pro_conHtml+='</div>' 
								    ul_pro_conHtml+=' <div class="join" data-stock_num="'+elementArr.stock_num+'" data-goods_id="'+elementArr.goods_id+'" data-name="'+elementArr.name+'" data-price="'+elementArr.price+'"> '
								     ul_pro_conHtml+=' <input type="button" value="加入菜单" /> '
								    ul_pro_conHtml+=' </div> '
								   ul_pro_conHtml+=' </section>'
							 }else if(element.sort_id == '-1'){
							  	   ul_pro_conHtml+='<div class="spec_list_span packageSpeci" data-id="'+elementArr.id+'" style="background:'+curColor+';">选菜品</div>'
								   
							 }else{
									ul_pro_conHtml+='<div class="span_cart"> '
										ul_pro_conHtml+='<span class="fl minus" style="background:'+curColor+';border-color:'+curColor+';" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="'+elementArr.stock_num+'" data-name="'+elementArr.name+'" data-price="'+elementArr.price+'" data-id="'+elementArr.goods_id+'" data-index="'+elementArr.goods_id+'"  ></span> '
										ul_pro_conHtml+='<span class="fr add" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="'+elementArr.stock_num+'" data-name="'+elementArr.name+'" data-price="'+elementArr.price+'" data-id="'+elementArr.goods_id+'" data-index="'+elementArr.goods_id+'"></span>  '
										ul_pro_conHtml+='<input type="text" value="0" readonly="true" class="fr span_cartNum" />'
										ul_pro_conHtml+='<div class="clear"></div>'
									ul_pro_conHtml+=' </div> '  
							  }  
							ul_pro_conHtml+='</li>'  
						 }) 
					    }               
					ul_pro_conHtml+='</ul> '
				ul_pro_conHtml+='</div> '   
				pro_leftNavHtml+="<li data-sort_id="+ element.sort_id +"> "+ element.sort_name +"</li>"
			}) 
			$('.pro_leftNav').html(pro_leftNavHtml)
			$('.pro_con').html(ul_pro_conHtml)
			 $('.pro_leftNav li:nth-child(1)').addClass('on')
		} 
		
	 });
}

// 渲染购物车
init_goods_menu() 
function init_goods_menu()
{  
	var nowShopCart ; 
	if(common.checkLogin()){ 
		common.http('Foodshop&a=ajaxGetGoods',{store_id:store_id},function(data){
		        nowShopCart = data;
		        goodsCart = [];
				var cart_goods_html = '';
				for (var i in nowShopCart) {
					if (nowShopCart[i] != null && nowShopCart[i].num > 0) {
						var detail_name = '', goodsCartKey = nowShopCart[i].goods_id;
						if (nowShopCart[i].type == 'only') {
			    			if (nowShopCart[i]['params'].length) {
			    				for (var pi in nowShopCart[i]['params']) {
			    					if (nowShopCart[i]['params'][pi].type == 'spec') {
			    						goodsCartKey += '_s_' + nowShopCart[i]['params'][pi].id;
			    					}
			    					if (nowShopCart[i]['params'][pi]['data']!='undefined') {
				    					if (nowShopCart[i]['params'][pi]['data'].length) {
				    						for (var di in nowShopCart[i]['params'][pi]['data']) {
				    							goodsCartKey += '_v_' + nowShopCart[i]['params'][pi]['data'][di].id;
				    							if (detail_name.length > 0) {
				    								detail_name += ',' + nowShopCart[i]['params'][pi]['data'][di].name
				    							} else {
				    								detail_name += nowShopCart[i]['params'][pi]['data'][di].name;
				    							}
				    						}
				    					}
			    					}
			    				}
			    			}
						} else {
						    if (nowShopCart[i]['params'].length) {
			                    for (var pi in nowShopCart[i]['params']) {
			                        goodsCartKey += '_' + nowShopCart[i]['params'][pi].goods_id;
			                    }
			                }
						}
						var tmp_extra_price = '';
						if(nowShopCart[i].extra_price>0&&open_extra_price==1){
							tmp_extra_price = '+'+nowShopCart[i].extra_price+nowShopCart[i].extra_price_name
						} 
						 				 
						cart_goods_html += '<li class="flex goods_' + goodsCartKey + '">';
							cart_goods_html += '<div class="cartBox_ul_li1">' + nowShopCart[i].name+'';
												if (detail_name.length) {
													cart_goods_html += '<br><span style="font-size:12px; color:#999">' + detail_name + '</span>';
												}              
							cart_goods_html += '</div>'; 
							cart_goods_html += '<div class="cartBox_ul_li2">'+Currency_symbol+nowShopCart[i].price+'</div>';
							cart_goods_html += '<div class="cartBox_ul_li3">';
								cart_goods_html += '<div class="span_cart">';
									 cart_goods_html += ' <span class="fl minus" style="background:'+curColor+';border-color:'+curColor+';display:block" data-stock_num="' + nowShopCart[i].stockNum + '" data-price="' + nowShopCart[i].price + '" data-id="' + nowShopCart[i].goods_id + '" data-index="' + goodsCartKey + '" data-name="' + nowShopCart[i].name + '"></span> ';
									 cart_goods_html += ' <span class="fr add" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="' + nowShopCart[i].stockNum + '" data-price="' + nowShopCart[i].price + '" data-id="' + nowShopCart[i].goods_id + '" data-index="' + goodsCartKey + '" data-name="' + nowShopCart[i].name + '"></span>  ';
									 cart_goods_html += ' <input type="text" style="display:block"  class="fr span_cartNum num" readOnly="true" value="' + parseInt(nowShopCart[i].num) + '" />';
									 cart_goods_html += ' <div class="clear"></div>';
								cart_goods_html += '</div> '; 
							cart_goods_html += '</div>';
						cart_goods_html += '</li> ';
						   
						// $('.cartBox_ul').append(cart_goods_html);  
						$('.goods_' + goodsCartKey).find("input").val(parseInt(nowShopCart[i].num)).show();
						$('.goods_' + goodsCartKey).find(".minus").show();
						goodsNumber += parseInt(nowShopCart[i].num);
						goodsCartMoney += parseFloat(nowShopCart[i].price) * parseInt(nowShopCart[i].num);
						goodsCartMoney = parseFloat(goodsCartMoney.toFixed(2));
						if(nowShopCart[i].extra_price>0&&open_extra_price==1){
							goodsExtraPrice+=parseFloat(nowShopCart[i].extra_price) * parseInt(nowShopCart[i].num);
							var extra_price_name = nowShopCart[i].extra_price_name;
						} 
						goodsCart[i] = nowShopCart[i];
					}
				}
				$('.cartBox_ul').append(cart_goods_html);
				if (goodsNumber > 0) { 
					$(".floor").addClass("floorOn");
					$(".floorOn .next").css("background",curColor)
					$(".qty").show(500).text(goodsNumber);
					if(goodsExtraPrice>0){
						$('#total_price').text(goodsCartMoney+'+'+goodsExtraPrice+extra_price_name);
					}else{			
						$('#total_price').text(goodsCartMoney);
					}
				} 
		 
		});
	}

	
}

function stringifyCart()
{ 
	var flag = false 
	if(common.checkLogin()){ 
		common.http('Foodshop&a=ajaxSaveGoods',{store_id:store_id,goodsCart,goodsCart},function(data){ 
			if (data.errorCode) { 
				motify.log(data.msg);
				return false;
			} else {
				flag = true;
				return true;
			}
		})
	} 

}

 //团购套餐点击查看产品详情
 $(document).on('click', '.packageSpeci', function() { 
	 var id = $(this).data('id');
	 $.post('wap.php?c=Foodshop&a=foodshop_getgroup_detail',{'group_id':id, 'store_id':store_id}, function(response){
		 if (response.errcode == 0) {
			 var data = response.data;
			 laytpl($('#groupDetailTpl').html()).render(data, function(html){
				 $('.setmenu').html(html).slideDown();
			 });
			 //团购套餐 
			 $(".setmenu_list dd:last-child").css("border-bottom", "#f1f1f1 1px solid");
			 $(".setmenu_list .condition").each(function(){
				 $(this).height($(this).siblings(".set_list").height());
			 })
			$('.mask').show()
		 } else {
			 motify.log(response.msg);
		 }
	 }, 'json');
 });
 //选中团购菜品
$(document).on('click', '.set_list li', function() {
	var maxNum = $(this).parents('ul').data('num'); 
	if (maxNum == 1) {
		$(this).addClass("on").siblings('li').removeClass("on");
	} else {
		if ($(this).is('.on')) {
			$(this).removeClass("on");
		} else {
			if ($(this).parents('ul').find('.on').size() >= maxNum) {
				motify.log('您只能选择' + maxNum + '项');
				return false;
			} else {
				$(this).addClass("on");
			}
		}
	}
});
  $(document).on('click', '.setmenu .join', function() {
        var tempData = [], is_no_select = false, tnum = 0, max = 0, id = $(this).data('id'), name = $(this).data('name'), price = parseFloat($(this).data('price'));
        var goodsCartKey = id;
        $(this).parents('.setmenu').find('.setmenu_list .set_list ul').each(function(){
            max = parseInt($(this).data('num'));
            tnum = 0;
            $(this).find('.on').each(function(){
                tempData.push({'goods_id':$(this).data('goods_id'), 'unit':$(this).data('unit'), 'name':$(this).data('name'), 'price':$(this).data('price')});
                goodsCartKey += '_' + $(this).data('goods_id');
                tnum ++;
            });
            if (tnum < max) is_no_select = true;
        });
        if (is_no_select) {
            motify.log('您有菜品未选择');
            return false;
        }
        
        var this_index = null;
        for (var i in goodsCart) {
            if (goodsCart[i].type == 'only') continue;
            
            var old_goodsCartKey = goodsCart[i].goods_id;
            if (goodsCart[i]['params'].length) {
                for (var pi in goodsCart[i]['params']) {
                    old_goodsCartKey += '_' + goodsCart[i]['params'][pi].goods_id;
                }
                goodsCart[i]['index'] = old_goodsCartKey;
            }
            if (goodsCartKey == old_goodsCartKey) {
                this_index = i;
                break;
            }
        }
        
        var num = 1;
        if (this_index != null) {
            goodsCart[this_index].num ++;
            num = goodsCart[this_index].num;
        } else {
            goodsCart.push({
                'goods_id':id,
                'type':'group',
                'index':goodsCartKey,
                'num':1,
                'name':name,
                'price':price,
                'stockNum':-1,//套餐默认是无限量(暂定)
                'params':tempData});
        }
		if ( stringifyCart() == false) {
		 	return false;
		}
        
        $('.goods_' + goodsCartKey).find("input").val(num);
        
        if (!$('.cartBox_ul').find('.goods_' + goodsCartKey).length) {
            var cart_goods_html = '';  
			cart_goods_html += '<li class="flex goods_' + goodsCartKey + '">';
				cart_goods_html += '<div class="cartBox_ul_li1">' + name + '</div>';
				cart_goods_html += '<div class="cartBox_ul_li2">'+Currency_symbol+price+'</div>';
				cart_goods_html += '<div class="cartBox_ul_li3">';
					cart_goods_html += '<div class="span_cart">';
						 cart_goods_html += ' <span class="fl minus" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="-1" data-price="' + price + '" data-id="' + id + '" data-index="' + goodsCartKey + '" data-name="' + name + '" style="display:block"></span> ';
						 cart_goods_html += ' <span class="fr add" style="background:'+curColor+';border-color:'+curColor+';" style="background:'+curColor+';border-color:'+curColor+';" data-stock_num="-1" data-price="' + price + '" data-id="' + id + '" data-index="' + goodsCartKey + '" data-name="' + name + '"></span>  ';
						 cart_goods_html += ' <input type="text"  style="display:block" class="fr span_cartNum num" readOnly="true" value="' + num + '"   />';
						 cart_goods_html += ' <div class="clear"></div>';
					cart_goods_html += '</div> '; 
				cart_goods_html += '</div>';
			cart_goods_html += '</li> ';   
			$('.cartBox_ul').append(cart_goods_html); 
        }

        $(".TcancelT,.setmenu,setmenu_n").slideUp();
        $(".spot").fadeOut();
        $(".mask").hide();
        goodsNumber ++;
        goodsCartMoney += price;
		goodsCartMoney = parseFloat(goodsCartMoney.toFixed(2));
        if (goodsNumber > 0) {
            $(".floor").addClass("floorOn");
            $(".qty").show(500).text(goodsNumber);
            $('#total_price').text(goodsCartMoney);
        }
    }); 
 
    $(document).on('click', '.mask,.setmenu .gb', function() {
        $(".TcancelT,.setmenu,setmenu_n").slideUp();
        $(".spot").fadeOut();
        $(".mask").hide();
    });
	
	$(document).on('click', '.cartMask', function() { 
	    $(".mask").hide();
		$('.Cart').hide();
	});
	
	
 // 保存订单 
 var flag = 1;
 $(document).on('click', '.submitOrder', function() { 
 	if (!flag) {
 		return false;
 	}
 	flag = 0;
    if(common.checkLogin()){
     	common.http('Foodshop&a=unbindTableSaveOrder',{store_id:store_id},function(data){
     		console.log(data) 
			if(data){
				if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
                    wx.miniProgram.navigateTo({
                            url:'/pages/shop_new/confirmOrder/pay?order_id='+ data.order_id +'&type='+data.type
                            ,complete:function(){
                                    $("#submit_order").removeClass('disabled');
                            }
                    });
	            }else{
	            	location.href = '/packapp/plat/pages/shop_new/confirmOrder/pay?order_id='+data.order_id+'&type='+data.type+'&order_from=';
	            }
			} 
 			flag = 1;
			
     	})
     }
 });
 
