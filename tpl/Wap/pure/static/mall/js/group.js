var property_json={};
var json={};
$(function(){
	//选择规格
	$(".mask").height($(window).height());
	$(document).on('click', '.spec li', function(){
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
	//关闭选择的商品的窗口
	$(".del").click(function(){
		$(this).parents(".Speci").animate({bottom:"-400px"});
		$(".mask").hide();
	});
	$(".mask").click(function(){
		$(this).hide();
		$(".Speci").animate({bottom:"-400px"});
	})
	//点击规格显示
	$(".show_detail").click(function(){
		$('.one_btn').hide();
		$(".Speci").animate({bottom:"0px"});
		$(".mask, .two_btn").show();
	});

	$(".Choice_n").click(function(){
		$(".Speci").animate({bottom:"0px"});
        $('.two_btn').hide();
        $(".mask, .one_btn").show();
        var group_id=$(this).data('group_id') || '';
        var team_id=$(this).data('team_id') || '';
        var type=$(this).data('type');
        $('.one_btn').data('type',type).data('group_id',group_id).data('team_id',team_id);
	});
	$(document).on('click', '.one_btn, .two_btn', function(){
		var type=$(this).data('type');
		if(type == 'buy'){
			if(add_buy_cart(true)){
                $(".Speci").animate({bottom:"-400px"});
                $(".mask").hide();
                location.href = save_url+ '&params=onlybuy';// + '&params=' + window.localStorage.getItem(cookie_buy_index);
			}
		}else if(type=='group_team' || type=='group_join'){
			var group_id=$(this).data('group_id');
			var team_id=$(this).data('team_id');
			if(add_buy_cart(true,type)){
				$(".Speci").animate({bottom:"-400px"});
                $(".mask").hide();
                location.href = save_url+ '&params=onlybuy&group_id='+group_id+'&team_id='+team_id;
			}
		}else{
            if (add_cart(true)) {
                $(".Speci").animate({bottom:"-400px"});
                $(".mask").hide();
            }
		}
	});
	//加减号的操作
	$(document).on('click', '.plus a', function() {
		var num = parseInt($('.plus input').val());
		if ($(this).attr('class') == 'jia') {
			num ++;
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
	
});
function add_buy_cart(is_check,buy_type){
    var buy_type=buy_type || 'buy';

	var productParam = [], store_id = $('.title_data').data('store_id'), is_seckill = $('.title_data').data('is_seckill'), maxNum = parseInt($('.title_data').data('max_num')), minNum = parseInt($('.title_data').data('min_num')), unit = $('.title_data').data('unit'), limit_type = parseInt($('.title_data').data('limit_type')), oldPrice = $('.title_data').data('o_price'), goods_id = $('.title_data').data('goods_id'), pack = $('.title_data').data('pack'), price = $('.title_data').data('price'), name = $('.title_data').data('name'), stock = $('.title_data').data('stock'), extra_pay_price = $('.title_data').data('extra_pay_price'),spec_ids = [], is_false = false;

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
                motify.log(fname + '属性至少选择一个');
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
    var now_goods_list = $.parseJSON(goods_list);
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

    console.log('add_buy_cart');
    $.cookie('only_buy_cart' + '_0', JSON.stringify([{
        'store_id':store_id,
        'productId':goods_id,
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
        'productParam':productParam}]), {expires:700,path:'/'});
    $.cookie('mall_buy_type','buy');
    return true;
}
function show_select_txt(type)
{
	var txt = [], spec_ids = [], price = $('.title_data').data('prcie'), is_seckill = $('.title_data').data('is_seckill'),extra_pay_price = $('.title_data').data('extra_pay_price');
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
	var now_goods_list = $.parseJSON(goods_list);
	var t_price = 0;
	if (spec_ids.length > 0) {
		if (spec_ids.length > 1) {
			var str = spec_ids.join('_');
		} else {
			var str = spec_ids[0];
		}
		max_num = 0;
		if (now_goods_list[str] != undefined) {
			if (is_seckill) {
				t_price = now_goods_list[str].seckill_price;
				max_num = now_goods_list[str].max_num;
			} else {
				t_price = now_goods_list[str].price;
				max_num = now_goods_list[str].max_num;
			}
			if(type=='group_team'){      //发起拼团
                group_team_price = now_goods_list[str].team_price || '';
            }else if(type=='group_join'){   //参加拼团
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
            $('#showDiscount').show();
		} else {
		    $('#showDiscount').hide();
		}
	} else if (now_goods_list == null) {
		t_price = price;
	}
	if(extra_pay_price>0&&open_extra_price==1){
        if(type=='group_team' && typeof group_team_price!='undefined'){
            $('#show_format_price2').html(group_team_price+'+'+extra_pay_price+extra_price_name);
        }else if(type=='group_join' && typeof group_join_price!='undefined'){
            $('#show_format_price2').html(group_join_price+'+'+extra_pay_price+extra_price_name);
        }
		$('#show_format_price').html(t_price+'+'+extra_pay_price+extra_price_name);
	}else{
        if(type=='group_team' && typeof group_team_price!='undefined'){
            $('#show_format_price2').text(group_team_price);
        }else if(type=='group_join' && typeof group_join_price!='undefined'){
            $('#show_format_price2').text(group_join_price);
        }
		$('#show_format_price').text(t_price);
	}
    if(now_goods_list[str].stock_num==0){
        $('#show_stock_num').html('库存为0')
    }else if (now_goods_list[str].stock_num==-1){
        $('#show_stock_num').html('库存充足')
    }else{
        $('#show_stock_num').html('剩余'+now_goods_list[str].stock_num)
    }
}
function close_swiper(){
	$('.albumContainer').remove();
}
/*预览图片*/
$("body").on("click",".pics",function(){
    var album_array = [];
    $(this).find('img').each(function(n,v){
        var pics=$(this).data('pics');
        if(pics){
            album_array[n]=pics;
        }                      
    });
    if(album_array.length==0){
        return false;
    }
    if(/(micromessenger)/.test(navigator.userAgent.toLowerCase())){
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
