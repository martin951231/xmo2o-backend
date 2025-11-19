
var goodsCart = [], goodsNumber = 0,
	cookie_index = 'mall_goods_cart',
	cookie_buy_index = 'buy_mall_goods_cart',
	goodsCartMoney = 0, goods_price_list = [],
	goods_index_list = [],
	goodsCartPackCharge = 0;
var property_json={};
var json={};
var cart_cookie = '';
var price_type=''
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

$(document).ready(function(){
	$('.Number').text('');

    if(window.__wxjs_is_wkwebview){
        window.addEventListener("pageshow", function(){
            goodsNumber = 0;
			init_goods_menu($.parseJSON(goods_cart));
        },false);
    }else{
        init_goods_menu($.parseJSON(goods_cart));
    }
	$('#productDetail img').removeAttr('width');
	$('#productDetail img').removeAttr('height');
	if($(".swiper-container1 .swiper-slide").length<=1){

	}else{
		var myswiper = new Swiper('.swiper-container1', {
			pagination: '.swiper-container1 .swiper-pagination',
			direction : 'horizontal',
			paginationClickable :true,
			autoplay :'2000',
			autoplayDisableOnInteraction : false,
			loop: true
		});
	}

	//----优惠部分的操作▼------
	$(".p75").each(function(){
		var height = $(this).height();
		if (height > 64) {
			$(this).css({"height":"64px","overflow":"hidden"});
		} else{
			$(this).siblings("a.more").hide();
		}
	});
	$("a.more").toggle(function(){
		$(this).addClass("morelv").find("span").text("收起更多");
		$(this).siblings(".p75").css("height","auto");
	},function(){
		$(this).removeClass("morelv").find("span").text("展开更多");
		$(this).siblings(".p75").css("height","64px");
	});
	//----优惠部分的操作▲------
	//加减号的操作
	$(document).on('click', '.plus a', function() {
		var is_seckill = $('.title_data').data('is_seckill');
		var num = parseInt($('.plus input').val());
		if ($(this).attr('class') == 'jia') {
			num ++;
			//限购校验
			var maxNum=$('#showMax').text();
			if(!is_seckill && typeof maxNum!='undefined' && !isNaN(maxNum) && maxNum>0){
				maxNum=parseInt(maxNum);
				if(num>maxNum){
					var limit_type = parseInt($('.title_data').data('limit_type'));
					var unit = $('.title_data').data('unit');
					if (limit_type == 0) {
	                    motify.log('每单限购' + maxNum + unit);
	                } else {
	                    motify.log('每个用户限购' + maxNum + unit);
	                }
					return false;
				}
			}
		} else {
			num --;
		}
		if (num < 1) {
			num = 1;
		}
		var type=$(this).parents('.Speci').find('.one_btn').data('type');
		if(type=='group_team' || type=='group_join'){
			var min_num=1;
		}else{
			var min_num=$('.title_data').data('min_num');
			min_num=min_num || 1;
		}
		if (num >min_num) {
			$(".plus a.jian").css("color","#333333");
		}else if(num==min_num){
			$(".plus a.jian").css("color","#bfbfbf");
		}else{
			$(".plus a.jian").css("color","#bfbfbf");
			return false;
		}
		/*记录不同类型活动当前商品数量*/		
		if(type=='group_team' || type=='group_join'){
			json['group_num']=num;
		}else{
			json['buy_num']=num;
		}
		$('.plus input').val(num);
	});

	// 图片等比例
	$(".details .swiper-wrapper img").each(function(){
		$(this).height($(this).width());
	});
	//评论星星
	$(".title p").each(function(index, element) {
		$(this).css("width", $(this).attr("tip") * 16);
	});

	//联系商家

	$("#discount_p").show();
	//选择规格
	$(".mask").height($(window).height());
	$(document).on('click', '.spec li', function(){
		$("#discount_p").show();
		$(this).addClass("on").siblings().removeClass("on");
		//获取当前的操作
		var type=$(this).parents('.Speci').find('.one_btn').data('type');
		show_select_txt(type);
	});
	
	$(document).on('click', '.property li', function(){
		if ($(this).hasClass("on")) {
			$(this).removeClass("on");
		} else {
			var max_num = parseInt($(this).parents('ul').data('num'));
			var now_num = parseInt($(this).parents('ul').find('.on').size());
			var pobj=$(this).parent();
			var pid=pobj.data('id');
			if (now_num >= max_num) {
				if(property_json[pid]){
					var k=property_json[pid].pop();
					pobj.find('li').eq(k).removeClass('on');
				}else{
					motify.log('最多能选' + max_num + '个');
					return false;	
				}
			}
			if(!property_json[pid]){
				property_json[pid]=[];
			}
			property_json[pid].push($(this).index());
			$(this).addClass("on");		
		}
		var type=$(this).parents('.Speci').find('.one_btn').data('type');
		//获取当前的操作
		show_select_txt(type);
	});
    
    // 周期购的选中 
    $(document).on('click', '.cyclePurchase li', function(){
      $(this).addClass('on').siblings().removeClass('on');  
    })
    
	//关闭选择的商品的窗口
	var hide_$del=function($href){
		if($("body").hasClass("modal-open")){
			var $top=parseFloat($("body").css("top"))*(-1);

			$("body").removeClass('modal-open')

			$("html,body").animate({"scrollTop":$top},10,'linear',function(){

			});
		}
		var b=$(".Speci").attr("data-bottom");
		$(".Speci").animate({bottom:b},'100','linear',function(){
			$(".mask").hide();
			 if($href && $href!=undefined){
				 location.href=$href
			 }
		});
	}
	var $del=function(type,group_id,team_id){
		if(type == 'buy'){
			if(add_buy_cart(true)){
				/*添加一个提示框*/
				if(book_id){
					layer.open({
						content: '支付定金,定金将不退回？'
						, btn: ['确定', '取消']
						, yes: function (index) {
							layer.close(index);
							var $href= save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
							hide_$del($href);
						}
					});
				}else{
					var $href= save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
					hide_$del($href);
				}
				//var $href= save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
				//hide_$del($href)
				//location.href = save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
			}
		}else if(type == 'book'){
			layer.open({
				content: '支付定金,定金将不退回？'
				, btn: ['确定', '取消']
				, yes: function (index) {
					layer.close(index);
					var $href= save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
					hide_$del($href);
				}
			});
			//layer.confirm("确定之后定金不退还！", {btn: ['确定', '取消'], title: "提示"}, function () {

			//})
			//var $href= save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
			//hide_$del($href)
			//location.href = save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
		}
		else if(type=='group_team' || type=='group_join'){
			if(add_buy_cart(true,type)){
				var $href= save_url+ '&params=onlybuy&group_id='+group_id+'&team_id='+team_id;
				hide_$del($href)
				//location.href = save_url+ '&params=onlybuy&group_id='+group_id+'&team_id='+team_id;
			}
		}else{
			hide_$del()
		}





	}

	$(".del").click(function(){
		hide_$del()
	});
	$(".mask").click(function(){
		hide_$del()
	});

	//点击规格显示
	$(".show_detail").click(function(){
		$(".Speci").animate({bottom:"0px"});
		if(book_id>1){
			$('.two_btn,.one_btn').hide(); 
			$(".mask, .three_btn").show();
		}else{
		  $('.one_btn,.three_btn').hide(); 
		  $(".mask, .two_btn").show();
		}
		
	});
	$(document).on("click",".Choice_n",function(){ 
	   $(".Speci").animate({bottom:"0px"});
	   
	  //  if(book_id>1){
	  //  	$('.two_btn,one_btn').hide();  
		 // $(".mask, .three_btn").show();
	  //  }else{
		 
	  //  } 
	   $('.two_btn').hide();
	   $(".mask, .one_btn").show();
	   var group_id=$(this).data('group_id') || '';
	   var team_id=$(this).data('team_id') || '';
	   var type=$(this).data('type');
	   var yuan_price=$(this).data('yuan_price');
	   if(yuan_price == 'yuan_price'){
		  // console.log($('.title_data').data('prcie'))
		   $('#show_format_price').html(goods_price)
		   price_type = 0
		   // $('.mask,.shops.two_btn').show();
		   // $('.shops.two_btn').css('width','100%')
		   // $(".one_btn").hide();
	   }else{
		   $('#show_format_price').html($('#tgoods_price').html())
	   }
	   
	   $('.one_btn').data('type',type).data('group_id',group_id).data('team_id',team_id);
   })

	$(document).on('click', '.one_btn, .two_btn,.three_btn', function(){
		var type=$(this).data('type');
		 if((type == 'buy') || (type=='group_team' || type=='group_join')){
			 var group_id=$(this).data('group_id');
			 var team_id=$(this).data('team_id');
			 $del(type,group_id,team_id)
		 }else{
			 if($.parseJSON(goods_cart).length >=1 && goodsCart.length<1){
				 motify.log('请等待页面加载完成');
				 return false;
			 }
			 if (add_cart(true)) {
				 $del()
			 }
		 }


	});


	$(".cars").click(function(){
		var text = $(".red span").text();
		$(".namic").text(text).css("color","#ff4d45");
		$(".opt").text("已选");
	});
	$('.evaluate dt.clr').click(function(){
		location.href = reply_url;
	});

	//init_goods_menu();
});
var account_money = $('.title_data').data('account_money'),account_score = $('.title_data').data('account_score');
function show_select_txt(type)
{   
	var txt = [],
		spec_ids = [],
		price = $('.title_data').data('prcie'),
		is_seckill = $('.title_data').data('is_seckill'),
		extra_pay_price = $('.title_data').data('extra_pay_price');
	$('.cations .on').each(function(){
		txt.push($(this).data('name'));
	});
	$(".namic").text(txt.join(' ')).css("color","#ff4d45");
	$(".opt").text("已选");

	$('.cations ul').each(function(){
		if ($(this).data('type') == 'spec') {
			$(this).find('li.on').each(function(){
				spec_ids.push($(this).data('id'));
			});
		}
	});

	var now_goods_list =goods_list?$.parseJSON(goods_list):null;
	var t_price = 0;
	var t_account_score=0 

	if (spec_ids.length > 0) {
		if (spec_ids.length > 1) {
			var str = spec_ids.join('_');
		} else {
			var str = spec_ids[0];
		}
		max_num = 0;
		 
		if (now_goods_list[str] != undefined) {
			/*console.log("---------------------");
			console.log("is_seckill:"+is_seckill);*/
			console.log(now_goods_list[str]);
			if (is_seckill) {
				if(price_type == 0){
					t_price = now_goods_list[str].seckill_price;
					
				}else{
					t_price = now_goods_list[str].account_money;
					t_account_score = now_goods_list[str].account_score;
				}  
				max_num = now_goods_list[str].max_num; 
			} else {
				if(price_type == 0){
					t_price = now_goods_list[str].price;
				}else{
					t_price = now_goods_list[str].account_money;
					t_account_score = now_goods_list[str].account_score;
				}  
				max_num = now_goods_list[str].max_num;  
			}
			if(type=='group_team'){		//发起拼团
				group_team_price = now_goods_list[str].join_price || '';
			}else if(type=='group_join'){	//参加拼团
				group_join_price = now_goods_list[str].join_price || '';
			}

			if(now_goods_list[str].stock_num==0){
				$('#show_stock_num').html('库存为0')
			}else if (now_goods_list[str].stock_num==-1){
				$('#show_stock_num').html('库存充足')
			}else{
				$('#show_stock_num').html('剩余'+now_goods_list[str].stock_num)
			}
			//新增商品规格图
			var reg_img=now_goods_list[str]['reg_img'];
			$('#reg_img').attr('src',reg_img).data('pics',reg_img);
		}
		if (max_num > 0) {
			$('#showMax').text(max_num);
			var num = parseInt($('.plus input').val());
			if(!is_seckill && num>max_num){
				$('.plus input').val(max_num);
			}
			$('#showDiscount').show();
		} else {
			$('#showMax').text(0);
			$('#showDiscount').hide();
		}
	} else if (now_goods_list == null) {
		if(price_type == 0){
			t_price = price;
		}else{
			t_price = account_money;
		} 
		 
	}
	if(extra_pay_price>0&&open_extra_price==1){
		if(type=='group_team' && typeof group_team_price!='undefined'){
			$('#show_format_price2').html(group_team_price+'+'+extra_pay_price+extra_price_name);
		}else if(type=='group_join' && typeof group_join_price!='undefined'){
			$('#show_format_price2').html(group_join_price+'+'+extra_pay_price+extra_price_name);
		}	

		
		if(price_type == 0){
			$('#show_format_price').html(t_price+'+'+extra_pay_price+extra_price_name);
		}else{
			$('#show_format_price').html(t_price + '+' + t_account_score+score_name+extra_pay_price+extra_price_name);
		} 

		 
		$('#tgoods_price').html(t_price+'+'+extra_pay_price+extra_price_name);
	}else{
		if(type=='group_team' && typeof group_team_price!='undefined'){
			$('#show_format_price2').text(group_team_price);
		}else if(type=='group_join' && typeof group_join_price!='undefined'){
			$('#show_format_price2').text(group_join_price);
		}
		if(t_price==0){
			t_price = goods_price;
		} 
		  
		if(price_type == 0){
			if(extra_pay_price > 0){
				$('#show_format_price').html(t_price + '+' +extra_pay_price+extra_price_name);
			}else{
				$('#show_format_price').html(t_price);
			}
			
		}else{
			  
			$('#show_format_price').html(t_price+'+'+t_account_score+score_name+extra_pay_price+extra_price_name);
		} 
		$('#tgoods_price').text(t_price); 
	}
	//(now_goods_list[str].bargin_price);
    if(now_goods_list[str] && now_goods_list[str].bargin_price!='undefined'){
		$('#bargin_price').html(now_goods_list[str].bargin_price) 
		$('#discount_price').html( Number(now_goods_list[str].bargin_price) + Number(now_goods_list[str].discount_price));
	} 
	$('#advance_price').html(t_price)  
	if(typeof str=='undefined'){	//没有选择规格
		return false;
	}
	//更新当前库存数量(存在规格库存)
	if(now_goods_list[str] && typeof now_goods_list[str].stock_num!='undefined'){
		$('.title_data').data('stock',now_goods_list[str].stock_num);
	}
	
	if(now_goods_list[str]){
		if(now_goods_list[str].stock_num==0){
			$('#show_stock_num').html('库存为0')
		}else if (now_goods_list[str].stock_num==-1){
			$('#show_stock_num').html('库存充足')
		}else{
			$('#show_stock_num').html('剩余'+now_goods_list[str].stock_num)
		}
    }
}
function add_cart(is_check)
{
	var productParam = [], store_id = $('.title_data').data('store_id'), is_seckill = $('.title_data').data('is_seckill'), maxNum = parseInt($('.title_data').data('max_num')), minNum = parseInt($('.title_data').data('min_num')), unit = $('.title_data').data('unit'), limit_type = parseInt($('.title_data').data('limit_type')), oldPrice = $('.title_data').data('o_price'), goods_id = $('.title_data').data('goods_id'), pack = $('.title_data').data('pack'), price = $('.title_data').data('price'), name = $('.title_data').data('name'), stock = $('.title_data').data('stock'), extra_pay_price = $('.title_data').data('extra_pay_price'),spec_ids = [], is_false = false;

	var index_key = 's_' + store_id + '_g_' + goods_id;
	//增加记录规格的键值
	var reg_key=index_key;
	$('.cations ul').each(function(){
		var type = $(this).data('type'), fid = $(this).data('id'), fname = $(this).data('name'), datas = null, select_num = $(this).data('num');
		if (type == 'spec') {
			var num = 0;
			$(this).find('li.on').each(function(){
				num = 1;
				var id = $(this).data('id'), name = $(this).data('name');
				datas = {
						type:'spec',
						spec_id:fid,
						id:id,
						name:name
				};
				index_key += '_s_' + id;
				reg_key += '_s_' + id;
				spec_ids.push(id);
				productParam.push(datas);
			});
			if (num < 1 && is_check) {
				motify.log(fname + '规格必须选择一个');
				is_false = true;
			}
		} else {
			var temp_data = [], num = 0;
			$(this).find('li.on').each(function(){
				num ++;
				temp_data.push({'id':$(this).data('id'), 'list_id':fid, 'name':$(this).data('name')});
				index_key += '_v_' + $(this).data('id');
			});
			if (num < 1 && is_check) {
				motify.log(fname + '属性至少选择1个');
				is_false = true;
			}
			if (num > select_num && is_check) {
				motify.log(fname + '属性最多选择' + select_num + '个');
				is_false = true;
			}
			if (temp_data.length > 0) {
				datas = {type:'properties', data:temp_data};
				productParam.push(datas);
			}
		}
	});
	if (is_false) return false;
	var now_goods_list =goods_list?$.parseJSON(goods_list):null;
	var t_price = 0;
	if (spec_ids.length > 0) {
		if (spec_ids.length > 1) {
			var str = spec_ids.join('_');
		} else {
			var str = spec_ids[0];
		}

		if (now_goods_list[str] != undefined) {
			if (is_seckill) {
				t_price = now_goods_list[str].seckill_price;
			} else {
				t_price = now_goods_list[str].price;
			}

			if(now_goods_list[str].stock_num==0){

				motify.log('库存不足，不能购买！');
				return false;
			}
			maxNum = now_goods_list[str].max_num;
			oldPrice = now_goods_list[str].price;
		}
	} else if (now_goods_list == null) {
		t_price = price;
	}
//	$('#show_format_price').text(t_price);
	
	var cart_data = format_cart_data(index_key,reg_key,1);
	console.log(cart_data);
	var num = parseInt($('.plus input').val());
	if (num < 1) {
		motify.log('请选择您要购买的数量');
		return false;
	}
	if (cart_data.this_index != null) {
		var this_index=cart_data.this_index;
		this_num = goodsCart[this_index].count + num;
		var cart_count=cart_data.count+num;

        if (maxNum > 0 && maxNum < cart_count) {
            if (is_seckill == true && limit_type == 0) {
                motify.log('每单可享受' + maxNum + unit + '限时优惠价，超出恢复原价');
            } else {
                if (limit_type == 0) {
                    motify.log('每单限购' + maxNum + unit);
                } else {
                    motify.log('每个用户限购' + maxNum + unit);
                }
                return false;
            }
//            if (is_seckill) {
//                motify.log('每单可享受' + maxNum + '份限时优惠价，超出恢复原价');
//            } else {
//                motify.log('每单限购' + maxNum + '份');
//                return false;
//            }
        }


		if (stock != -1 && cart_count > stock) {
			motify.log('库存不足，不能购买！');
			return false;
		}
		goodsCart[this_index].count = this_num;
		goodsCart[this_index].productStock = stock;
		goodsCart[this_index].productPrice = t_price;
		goodsCart[this_index].extra_pay_price = extra_pay_price;

		$.cookie(cookie_buy_index + '_0', JSON.stringify([goodsCart[this_index]]), {expires:700,path:'/'});
	} else {
		this_num = num;
		var cart_count=cart_data.count+num;

		if (maxNum > 0 && maxNum < cart_count) {
		    if (is_seckill == true && limit_type == 0) {
                motify.log('每单可享受' + maxNum + unit + '限时优惠价，超出恢复原价');
            } else {
                if (limit_type == 0) {
                    motify.log('每单限购' + maxNum + unit);
                } else {
                    motify.log('每个用户限购' + maxNum + unit);
                }
                return false;
            }
//            if (is_seckill) {
//                motify.log('每单可享受' + maxNum + '份限时优惠价，超出恢复原价');
//            } else {
//                motify.log('每单限购' + maxNum + '份');
//                return false;
//            }
        }

		if (stock != -1 && cart_count > stock) {
			motify.log('库存不足，不能购买！');
			return false;
		}
		goodsCart.push({
			'store_id':store_id,
			'productId':goods_id,
			'price_type':price_type,
			'count':this_num,
			'productName':name,
			'extra_pay_price':extra_pay_price,
			'productStock':stock,
			'productPrice':t_price,
			'productPackCharge':pack,
            'maxNum':maxNum,
            'minNum':minNum,
            'unit':unit,
            'limit_type':limit_type,
            'isSeckill':is_seckill,
            'oldPrice':oldPrice,
			'productParam':productParam});
	}


	// $.cookie(cookie_buy_index + '_0', JSON.stringify([{
	// 	'store_id':store_id,
	// 	'productId':goods_id,
	// 	'count':this_num,
	// 	'productName':name,
	// 	'extra_pay_price':extra_pay_price,
	// 	'productStock':stock,
	// 	'productPrice':t_price,
	// 	'productPackCharge':pack,
     //    'maxNum':maxNum,
     //    'minNum':minNum,
     //    'unit':unit,
     //    'limit_type':limit_type,
     //    'isSeckill':is_seckill,
     //    'oldPrice':oldPrice,
	// 	'productParam':productParam}]), {expires:700,path:'/'});
	goodsNumber += num;
    $.cookie('mall_buy_type','add');
	stringifyCart();
	$('.Number, .cart_num').text(goodsNumber);
	cart_init_number = goodsNumber;
	return true;
}
function add_buy_cart(is_check,buy_type)
{
	var buy_type=buy_type || 'buy';
    var productParam = [], store_id = $('.title_data').data('store_id'), is_seckill = $('.title_data').data('is_seckill'), maxNum = parseInt($('.title_data').data('max_num')), minNum = parseInt($('.title_data').data('min_num')), unit = $('.title_data').data('unit'), limit_type = parseInt($('.title_data').data('limit_type')), oldPrice = $('.title_data').data('o_price'), goods_id = $('.title_data').data('goods_id'), pack = $('.title_data').data('pack'), price = $('.title_data').data('price'), name = $('.title_data').data('name'), stock = $('.title_data').data('stock'), extra_pay_price = $('.title_data').data('extra_pay_price'),account_money = $('.title_data').data('account_money'),account_score = $('.title_data').data('account_score'),spec_ids = [], is_false = false;

    var index_key = 's_' + store_id + '_g_' + goods_id;
    $('.cations ul').each(function(){
        var type = $(this).data('type'), fid = $(this).data('id'), fname = $(this).data('name'), datas = null, select_num = $(this).data('num');
        if (type == 'spec') {
            var num = 0;
            $(this).find('li.on').each(function(){
                num = 1;
                var id = $(this).data('id'), name = $(this).data('name');
                datas = {
                    type:'spec',
                    spec_id:fid,
                    id:id,
                    name:name
                };
                index_key += '_s_' + id;
                spec_ids.push(id);
                productParam.push(datas);
            });
            if (num < 1 && is_check) {
                motify.log(fname + '规格必须选择一个');
                is_false = true;
            }
        } else {
            var temp_data = [], num = 0;
            $(this).find('li.on').each(function(){
                num ++;
                temp_data.push({'id':$(this).data('id'), 'list_id':fid, 'name':$(this).data('name')});
                index_key += '_v_' + $(this).data('id');
            });
            if (num < 1 && is_check) {
            	if(fname=='周期购'){
            		motify.log('请选择周期购时间');
            	}else{
            		motify.log(fname + '属性至少选择一个');
            	}
                
                is_false = true;
            }
            if (num > select_num && is_check) {
                motify.log(fname + '属性最多选择' + select_num + '个');
                is_false = true;
            }
            if (temp_data.length > 0) {
                datas = {type:'properties', data:temp_data};
                productParam.push(datas);
            }
        }
    });
	var str;
    if (is_false) return false;
    var now_goods_list =goods_list?$.parseJSON(goods_list):null;
    var t_price = 0;
    if (spec_ids.length > 0) {
        if (spec_ids.length > 1) {
             str = spec_ids.join('_');
        } else {
             str = spec_ids[0];
        }

        if (now_goods_list[str] != undefined) {
            if (is_seckill) {
                t_price = now_goods_list[str].seckill_price;
            } else {
                t_price = now_goods_list[str].price;
            }

			if(now_goods_list[str].stock_num==0){

				motify.log('库存不足，不能购买！');
				return false;
			}

            maxNum = now_goods_list[str].max_num;
            oldPrice = now_goods_list[str].price;
        }
    } else if (now_goods_list == null) {
        t_price = price;
    }
//	$('#show_format_price').text(t_price);

	/*拼团商品限购数量使用拼团单独的*/
    if(buy_type=='group_team' || buy_type=='group_join'){
    	maxNum=$('#group_limit_num').text();
    	limit_type=1;
    	maxNum=parseInt(maxNum);
    }

    var num = parseInt($('.plus input').val());
    if (num < 1) {
        motify.log('请选择您要购买的数量');
        return false;
    }
        this_num = num;
        if (maxNum > 0 && maxNum < this_num) {
            if (is_seckill == true && limit_type == 0) {
                motify.log('每单可享受' + maxNum + unit + '限时优惠价，超出恢复原价');
            } else {
                if (limit_type == 0) {
                    motify.log('每单限购' + maxNum + unit);
                } else {
                    motify.log('每个用户限购' + maxNum + unit);
                }
                return false;
            }
//            if (is_seckill) {
//                motify.log('每单可享受' + maxNum + '份限时优惠价，超出恢复原价');
//            } else {
//                motify.log('每单限购' + maxNum + '份');
//                return false;
//            }
        }

        if (stock != -1 && this_num > stock) {
            motify.log('库存不足，不能购买！');
            return false;
        }
        // var buy_goodsCart = [];
    	// buy_goodsCart.push({
         //    'store_id':store_id,
         //    'productId':goods_id,
         //    'count':this_num,
         //    'productName':name,
         //    'extra_pay_price':extra_pay_price,
         //    'productStock':stock,
         //    'productPrice':t_price,
         //    'productPackCharge':pack,
         //    'maxNum':maxNum,
         //    'minNum':minNum,
         //    'unit':unit,
         //    'limit_type':limit_type,
         //    'isSeckill':is_seckill,
         //    'oldPrice':oldPrice,
         //    'productParam':productParam});
         //周期购指定天数属性
    var periodic_appoint_day = $("ul.periodic_buy li.on").attr("data");
    //console.log(periodic_appoint_day);
    //console.log('success');
    $.cookie('only_buy_cart' + '_0', JSON.stringify([{ 
        'store_id':store_id,
        'productId':goods_id,
        'count':this_num,
        'productName':name,
        'extra_pay_price':extra_pay_price,
        'productStock':stock,
        'productPrice':t_price,
		'price_type':price_type,
		'account_money':account_money,
		'account_score':account_score,
        'productPackCharge':pack,
        'maxNum':maxNum,
        'minNum':minNum,
        'unit':unit,
        'limit_type':limit_type,
        'isSeckill':is_seckill,
        'oldPrice':oldPrice,
		'specid':str,//规格id
        'periodic_appoint_day':periodic_appoint_day,
        'productParam':productParam}]), {expires:700,path:'/'});
    $.cookie('mall_buy_type','buy');
    return true;
}
function format_cart_data(index,reg_key,type)
{
	type=type || 1;
	var this_index = null;
	var count=0;
	var json={};
	for (var i in goodsCart) {
		if (goodsCart[i].count > 0) {
			var old_goodsCartKey = 's_' + goodsCart[i].store_id + '_g_' + goodsCart[i].productId;
			var old_reg_key=old_goodsCartKey;
			if (goodsCart[i]['productParam'].length) {
				for (var pi in goodsCart[i]['productParam']) {
					if (goodsCart[i]['productParam'][pi].type == 'spec') {
						old_goodsCartKey += '_s_' + goodsCart[i]['productParam'][pi].id;
						old_reg_key += '_s_' + goodsCart[i]['productParam'][pi].id;
					} else {
						if (goodsCart[i]['productParam'][pi]['data'].length) {
							for (var di in goodsCart[i]['productParam'][pi]['data']) {
								old_goodsCartKey += '_v_' + goodsCart[i]['productParam'][pi]['data'][di].id;
							}
						}
					}
				}
			}
			if (index == old_goodsCartKey) {
				this_index = i;
			}
			if(reg_key==old_reg_key){
				count+=goodsCart[i].count;
			}
		}
	}
	json.this_index=this_index;
	//同种规格的商品总数
	json.count=count;
	return json;
}
function stringifyCart()
{
    for(var i = 0; i<40; i++){
        $.cookie(cookie_index + '_' + i, null);
    }

	var cookieProductCart = [];
	for(var i in goodsCart){
		if (goodsCart[i].count > 0) {
			cookieProductCart.push(goodsCart[i]);
		}
	}
	console.log(cookieProductCart);
    $.post(ajax_cart_save_url,{cart_cookie:cart_cookie,cookieProductCart:cookieProductCart},function(){
    });
}
function init_goods_menu(nowShopCart)
{
	goodsCart = [];
	for (var i in nowShopCart) {
		if (nowShopCart[i] != null && nowShopCart[i].count > 0) {
			var detail_name = '', goodsCartKey = 's_' + nowShopCart[i].store_id + '_g_' + nowShopCart[i].productId;
			if (nowShopCart[i]['productParam'].length) {
				for (var pi in nowShopCart[i]['productParam']) {
					if (nowShopCart[i]['productParam'][pi].type == 'spec') {
						goodsCartKey += '_s_' + nowShopCart[i]['productParam'][pi].id;
					} else {
						if (nowShopCart[i]['productParam'][pi]['data'].length) {
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
			goodsNumber += parseInt(nowShopCart[i].count);
			goodsCart[i] = nowShopCart[i];
		}
	}
	if(!isNaN($.cookie('mallCartNum')) && $.cookie('mallCartNum')!=null){
		goodsNumber=parseInt($.cookie('mallCartNum'));
	}
	if (goodsNumber > 0) $('.Number').text(goodsNumber);
	//获取店铺评论
	if(typeof shopReplyUrl!='undefined'){
		$.post(shopReplyUrl,{showCount:1},function(result){
			result = $.parseJSON(result);
			if(result){
				$('.evaluate dt span.fl').text('店铺评价('+result.all_count+')');
				var data = [];
				for (var i = 0; i < result.list.length; i++) {
					if (i < 3) {
						data.push(result.list[i]);
					} else {
						 break;
					}
				}
				laytpl($('#shopReplyTpl').html()).render(data, function(html){
					$('.evaluate dl').append(html);
				});
				$(".title p").each(function(index, element) {
					$(this).css("width", $(this).attr("tip") * 16);
				});
			}
			isLoading = false;
		});
	}	
}


 