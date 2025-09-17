var source_goods_data = [], left_cart_data = [], myScroll1 = null, myScroll2 = null, myScroll3 = null, card_data = [], pause_cart_data = [], order_num = 1, pause_num = 0;
var clearmsg = false;
var ispoint = true;
var flag_pay = true;
var smsTimer;
var id="";
var repay = 0;
var check_pay_time = null, order_id = 0, order_price = 0, tip_out_time = 0, no_discount_money=0;
var local_total_index = 'local_total_index_' + store_id, local_left_cart_data_index = 'left_cart_data_' + store_id, local_pause_cart_data = 'pause_cart_data_' + store_id;
var shop_mall = {
	'postNow':false,
	'init_data':function(){//加载全部商品数据
		var t_data = $.parseJSON(window.localStorage.getItem(local_total_index));
		if (t_data == null) {
			var index_loading = layer.load(0, {shade: [0.1,'#000000']});
			$.post(ajax_goods_list, function(response){
				if (response.err_code) {
					layer.msg(response.data);
				} else {
					window.localStorage.setItem(local_total_index, JSON.stringify(response.data));
					shop_mall.init_data_html(response.data);
				}
				layer.close(index_loading);
			}, 'json');
		} else {
			this.init_data_html(t_data);
		}
	},
	'format_data':function(){
		var t_data = $.parseJSON(window.localStorage.getItem(local_total_index));
		var new_data = [];
		for (var i in t_data) {
			for (var ii in t_data[i].goods_list) {
				t_data[i].goods_list[ii].stock_num = source_goods_data[t_data[i].goods_list[ii].goods_id].stock_num;
				t_data[i].goods_list[ii].stock_weight = source_goods_data[t_data[i].goods_list[ii].goods_id].stock_weight;
			}
			new_data.push(t_data[i]);
		}
		window.localStorage.setItem(local_total_index, JSON.stringify(new_data));
	},
	'count_cart_num':function(data, goods_id, num, return_type){
		var new_data = [], is_new = true;
		for (var i in data) {
			if (data[i].goods_id == goods_id) {
				is_new = false;
				num += data[i].num;
				data[i].num = num;
			}
			new_data.push(data[i]);
		}
		if (is_new) {
			new_data.push({'goods_id':goods_id, 'num':num});
		}
		if (return_type !== undefined) {
			return num;
		} else {
			return new_data;
		}
	},
    'count_cart_weight':function(data, goods_id,num,goods_weight,code_weight,return_type){
        var new_data = [], is_new = true;
        for (var i in data) {
            if (data[i].goods_id == goods_id) {
                is_new = false;
                num += data[i].num;
                data[i].num = num;
                goods_weight = num * goods_weight;
                data[i].goods_weight = goods_weight;
                code_weight += data[i].code_weight;
                data[i].code_weight = code_weight;
            }
            new_data.push(data[i]);
        }
        if (is_new) {
            new_data.push({'goods_id':goods_id, 'num':num,'goods_weight':goods_weight * num,'code_weight':code_weight});
        }
        if (return_type !== undefined) {
        	if(num==0)goods_weight = goods_weight * num
            return goods_weight + code_weight;
        } else {
            return new_data;
        }
    },
	'init_data_html':function(datas){//加载全部商品页面
		var swiper_slide = '', goods_list = '', first = 0;
		$.each(datas, function(i, data){
			if (first == 0) {
				swiper_slide += '<div class="swiper-slide on"><span>' + data.sort_name + '</span></div>';
			} else {
				swiper_slide += '<div class="swiper-slide"><span>' + data.sort_name + '</span></div>';
			}
			first ++;
			goods_list += '<ul class="clr">';
			
			//减去挂单商品的数量和购物车里面的数量
			var goods_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
			if (goods_data != null) {
				left_cart_data = goods_data.goods_data;
			} else {
				left_cart_data = null;
			}
			var temp_data = [];
			for (var i in left_cart_data) {
				if(left_cart_data[i].sell_type==2){
                    temp_data = shop_mall.count_cart_weight(temp_data, left_cart_data[i].goods_id, left_cart_data[i].num,left_cart_data[i].goods_weight,left_cart_data[i].code_weight);
				}else{
                    temp_data = shop_mall.count_cart_num(temp_data, left_cart_data[i].goods_id, left_cart_data[i].num);
				}
			}
			
			var t_pause_cart_data = $.parseJSON(window.localStorage.getItem(local_pause_cart_data)), pause_num = 0;
			for (var t in t_pause_cart_data) {
				pause_num ++;
				var this_goods_data = t_pause_cart_data[t].goods_data;
				for (var s in this_goods_data) {
					if(this_goods_data[s].sell_type==2){
                        temp_data = shop_mall.count_cart_weight(temp_data, this_goods_data[s].goods_id, this_goods_data[s].num,this_goods_data[s].goods_weight,this_goods_data[s].code_weight);
                    }else{
                        temp_data = shop_mall.count_cart_num(temp_data, this_goods_data[s].goods_id, this_goods_data[s].num);
                    }
				}
			}
			if (pause_num > 0) {
				$(".right_1uick .gd").find("em").html(pause_num).show();
			} else {
				$(".right_1uick .gd").find("em").html(pause_num).hide();
			}
			
			$.each(data.goods_list, function(ii, goods){
				var stock_num = goods.stock_num;
				if (goods.stock_num != -1) {
					var num = shop_mall.count_cart_num(temp_data, goods.goods_id, 0, 1);
					stock_num -= num;
				}
                if(goods.sell_type==2){
                    var stock_weight = goods.stock_weight;
                    if (goods.stock_weight != -1) {
                        var weight = shop_mall.count_cart_weight(temp_data, goods.goods_id, 0,goods.goods_weight,0,1);
                        stock_weight -= weight;
                    }
				}
				source_goods_data[goods.goods_id] = {'goods_id':goods.goods_id, 'name':goods.name, 'number':goods.number, 'price':goods.price, 'stock_num':stock_num, 'unit':goods.unit,'sell_type':goods.sell_type,'unit_price':goods.unit_price,'goods_weight':goods.goods_weight,'stock_weight':stock_weight};
				goods_list += '<li class="source_data_' + goods.goods_id + '" data-id="' + goods.goods_id + '" data-price="' + goods.price + '" data-number="' + goods.number + '" data-unit="' + goods.unit + '" data-name="' + goods.name + '" data-stock="' + stock_num + '" data-goods_weight="'+ goods.goods_weight+ '" data-stock_weight="'+ goods.stock_weight+ '" data-sell_type="'+ goods.sell_type+'">';
				goods_list += '<a href="javascript:void(0);">';
				goods_list += '<h2>' + goods.name + '</h2>';
				if (goods.number != '') {
					goods_list += '<p>' + goods.number + '</p>';
				} else {
					goods_list += '<p>'+'无条形码'+'</p>';
				}
				goods_list += '<div class="clr bot">';
				if(goods.sell_type==2){
                    if (-1 == goods.stock_weight) {
                        goods_list += '<div class="fl stock">'+'无限'+'</div>';
                    } else {
                        goods_list += '<div class="fl stock">剩余' + parseFloat(stock_weight/1000).toFixed(3) +'kg</div>';
                    }
				}else{
                    if (-1 == goods.stock_num) {
                        goods_list += '<div class="fl stock">'+'无限'+'</div>';
                    } else {
                        goods_list += '<div class="fl stock">剩余' + stock_num + goods.unit + '</div>';
                    }
				}
				if(goods.sell_type==2){
                    goods_list += '<div class="fr" data-num="68">￥' + parseFloat(goods.unit_price) + '/500g</div>';
				}else{
                    goods_list += '<div class="fr" data-num="68">￥' + goods.price + '</div>';
				}
				goods_list += '</div>';
				goods_list += '</a>';
				goods_list += '</li>';
			});
			goods_list += '</ul>';
		});
		$('.right_list').find('.swiper-wrapper').html(swiper_slide);
		$('.right_end .tab_ul').html(goods_list);
		$(".tab_ul ul").eq(0).show().siblings("ul").hide();
		$(".right_end").height($(window).height() - 245);
		
		//切换导航
		var swiper = new Swiper('.right_list .swiper-container', {
			slidesPerView: 3,
			spaceBetween: 0,
			pagination: '.right_list .swiper-pagination',
			nextButton: '.right_list .swiper-button-next',
			prevButton: '.right_list .swiper-button-prev',
			slidesPerView: 'auto'
		});
		this.loading_cart_html();
	},
	'format_left_data':function(goods_id, type, data, num){//左边购物车的操作
		number_or_name_focus();
		if (goods_id == undefined) {
			return false;
		}
		if (data == undefined) {
			data = null;
		}
		var goods_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
		if (goods_data != null) {
			left_cart_data = goods_data.goods_data;
		} else {
			goods_data = {'card_data':'', 'goods_data':''};
			left_cart_data = null;
		}
		if (goods_id == '---') {//清空
			new_data = [];
			data = null;
			for (var i in left_cart_data) {
				var stock = $('.source_data_' + left_cart_data[i].goods_id).data('stock');
				if(left_cart_data[i].sell_type==2){
                    if (source_goods_data[left_cart_data[i].goods_id].stock_weight != -1) {
                        source_goods_data[left_cart_data[i].goods_id].stock_weight = parseFloat(source_goods_data[left_cart_data[i].goods_id].stock_weight) + parseFloat(left_cart_data[i].num * left_cart_data[i].goods_weight)+left_cart_data[i].code_weight;
                        $('.source_data_' + left_cart_data[i].goods_id).data('stock_weight', source_goods_data[left_cart_data[i].goods_id].stock_weight).removeClass('on');
                        $('.source_data_' + left_cart_data[i].goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[left_cart_data[i].goods_id].stock_weight/1000).toFixed(3) + 'kg';
                    } else {
                        $('.source_data_' + left_cart_data[i].goods_id).removeClass('on');
                    }
				}else{
                    if (source_goods_data[left_cart_data[i].goods_id].stock_num != -1) {
                        source_goods_data[left_cart_data[i].goods_id].stock_num += left_cart_data[i].num;
                        $('.source_data_' + left_cart_data[i].goods_id).data('stock', source_goods_data[left_cart_data[i].goods_id].stock_num).removeClass('on');
                        $('.source_data_' + left_cart_data[i].goods_id).find('.stock').html('剩余') + source_goods_data[left_cart_data[i].goods_id].stock_num + source_goods_data[left_cart_data[i].goods_id].unit;
                    } else {
                        $('.source_data_' + left_cart_data[i].goods_id).removeClass('on');
                    }
				}
			}
			$('.right_end .tab_ul').find('li').removeClass('on');
			goods_data.card_data = '';

			$('#coupon_price').data('coupon','0').data('price','0').css('color','#ccc').html('选择优惠券');

			shop_mall.tips('商品清空了');
		} else {
			var is_value = false, new_data = [],code_data = [];
			for (var i in left_cart_data) {
				if (left_cart_data[i].goods_id == goods_id) {
					is_value = true;
					if(data && data.code_weight > 0){
						code_data = data;
					}
					data = left_cart_data[i];
					if(code_data.code_weight > 0){
                        data.code_weight = parseFloat(code_data.code_weight) + parseFloat(data.code_weight);
                        if(data.code_price){
                            data.code_price = parseFloat(parseFloat(code_data.code_price) + parseFloat(data.code_price)).toFixed(2);
						}else{
                            data.code_price = parseFloat(code_data.code_price).toFixed(2);
						}
					}
				} else {
					new_data.push(left_cart_data[i]);
				}
			}
			
			// + - 删除一条，删除全部
			if (is_value) {
				if (type == '+') {
					if(!code_data.code_weight){
                        data.num ++;
					}
                    if(source_goods_data[goods_id].sell_type==2){ //称重商品
						var real_weight = code_data.code_weight ? code_data.code_weight : source_goods_data[goods_id].goods_weight;
                        if (source_goods_data[goods_id].stock_weight != -1) {
                            //var real_weight = code_data.code_weight ? code_data.code_weight : source_goods_data[goods_id].goods_weight;
                            if (source_goods_data[goods_id].stock_weight >= real_weight) {
                                source_goods_data[goods_id].stock_weight = source_goods_data[goods_id].stock_weight - real_weight;
                                $('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);//右边库存的显示
                                $('.source_data_' + goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[goods_id].stock_weight/1000).toFixed(3) + 'kg';
                            } else {
                                layer.msg('库存不足');
                                return false;
                            }
                        }
                        shop_mall.tips(data.name + '加'+real_weight+'g');
                    }else {
                        if (source_goods_data[goods_id].stock_num != -1) {
                            if (source_goods_data[goods_id].stock_num > 0) {
                                source_goods_data[goods_id].stock_num--;
                                $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);//右边库存的显示
                                $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit;
                            } else {
                                layer.msg('库存不足');
                                return false;
                            }
                        }
                        shop_mall.tips(source_goods_data[goods_id].name + '加1');
                    }
				} else if (type == '-') {
					var code_weight = 0;
					var num = data.num;
					if (data.num > 1) {
                        data.num--;
                    }else if(data.code_weight>0){
						code_weight = data.code_weight;
						data = null;
					} else {
						data = null;
					}
                    if(source_goods_data[goods_id].sell_type==2){ //称重商品
                        if (source_goods_data[goods_id].stock_weight != -1) {
                        	if(code_weight > 0 && num==0){
                                source_goods_data[goods_id].stock_weight = source_goods_data[goods_id].stock_weight + code_weight;
							}else{
                                source_goods_data[goods_id].stock_weight = source_goods_data[goods_id].stock_weight + parseFloat(source_goods_data[goods_id].goods_weight) + parseFloat(code_weight);
							}
							$('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);//右边库存的显示
							$('.source_data_' + goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[goods_id].stock_weight/1000).toFixed(3) + 'kg';
                        }
                        shop_mall.tips(source_goods_data[goods_id].name + '减'+source_goods_data[goods_id].goods_weight+'g');
                    }else {
                        if (source_goods_data[goods_id].stock_num != -1) {
                            source_goods_data[goods_id].stock_num++;
                            $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);
                            $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit;
                        }
                        shop_mall.tips(source_goods_data[goods_id].name + '减1');
                    }
					if (data == null) $('.source_data_' + goods_id).removeClass('on');
				} else if (type == '--') {
                    if(source_goods_data[goods_id].sell_type==2){ //称重商品
                        if (source_goods_data[goods_id].stock_weight != -1) {
                            source_goods_data[goods_id].stock_weight = parseFloat(source_goods_data[goods_id].stock_weight) + parseFloat(data.num * source_goods_data[goods_id].goods_weight)+data.code_weight;
                            $('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);//右边库存的显示
                            $('.source_data_' + goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[goods_id].stock_weight/1000).toFixed(3) + 'kg';
                        }
                    }else {
                        if (source_goods_data[goods_id].stock_num != -1) {
                            source_goods_data[goods_id].stock_num += data.num;
                            $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);
                            $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit;
                        }
                    }
					data = null;
					shop_mall.tips(source_goods_data[goods_id].name + '删除操作');
					$('.source_data_' + goods_id).removeClass('on');
				} else if (type == null && num != undefined) {
					if (data.num > num) {
						var diff_num = data.num - num;
						data.num = num;
                        if(source_goods_data[goods_id].sell_type==2) { //称重商品
                            if (source_goods_data[goods_id].stock_weight != -1) {
                                source_goods_data[goods_id].stock_weight += diff_num * source_goods_data[goods_id].goods_weight;
                                $('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);
                                $('.source_data_' + goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[goods_id].stock_weight/1000).toFixed(3) + 'kg';
                            }
                        }else{
                            if (source_goods_data[goods_id].stock_num != -1) {
                                source_goods_data[goods_id].stock_num += diff_num;
                                $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);
                                $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit;
                            }
						}
					} else if (data.num < num) {
						var diff_num = num - data.num;
                        if(source_goods_data[goods_id].sell_type==2) { //称重商品
                            if (source_goods_data[goods_id].stock_weight != -1) {
                                if (source_goods_data[goods_id].stock_weight - (diff_num * source_goods_data[goods_id].goods_weight) > 0) {
                                    source_goods_data[goods_id].stock_weight -= (diff_num * source_goods_data[goods_id].goods_weight);
                                    $('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);
                                    $('.source_data_' + goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[goods_id].stock_weight/1000).toFixed(3) + 'kg';
                                } else if (source_goods_data[goods_id].stock_weight - (diff_num * source_goods_data[goods_id].goods_weight) == 0) {
                                    source_goods_data[goods_id].stock_weight = 0;
                                    $('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);
                                    $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_weight + 'kg'.removeClass('on');
                                } else {
                                    layer.msg('库存不足');
                                    return false;
                                }
                            }
                        }else{
                            if (source_goods_data[goods_id].stock_num != -1) {
                                if (source_goods_data[goods_id].stock_num - diff_num > 0) {
                                    source_goods_data[goods_id].stock_num -= diff_num;
                                    $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);
                                    $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit;
                                } else if (source_goods_data[goods_id].stock_num - diff_num == 0) {
                                    source_goods_data[goods_id].stock_num = 0;
                                    $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);
                                    $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit.removeClass('on');
                                } else {
                                    layer.msg('库存不足');
                                    return false;
                                }
                            }
						}
						data.num = num;
					}
					shop_mall.tips(source_goods_data[goods_id].name + '修改成') + data.num + source_goods_data[goods_id].unit;
				}
			} else if (data != null) {
				if(source_goods_data[goods_id].sell_type==2){ //称重商品
					var real_weight = data.code_weight ? data.code_weight : source_goods_data[goods_id].goods_weight;
                    if (source_goods_data[goods_id].stock_weight != -1) {
                        //var real_weight = data.code_weight ? data.code_weight : source_goods_data[goods_id].goods_weight;
                        if (source_goods_data[goods_id].stock_weight >= real_weight) {
                            source_goods_data[goods_id].stock_weight = source_goods_data[goods_id].stock_weight - real_weight;
                            $('.source_data_' + goods_id).data('stock_weight', source_goods_data[goods_id].stock_weight);//右边库存的显示
                            $('.source_data_' + goods_id).find('.stock').html('剩余') + parseFloat(source_goods_data[goods_id].stock_weight/1000).toFixed(3) + 'kg';
                            if(data.code_weight)data.num = 0;//称重商品不计算数量
                        } else {
                            layer.msg('库存不足');
                            return false;
                        }
                    }
                    shop_mall.tips(data.name + '加'+real_weight+'g');
				}else{
                    if (source_goods_data[goods_id].stock_num != -1) {
                        if (source_goods_data[goods_id].stock_num > 0) {
                            source_goods_data[goods_id].stock_num --;
                            $('.source_data_' + goods_id).data('stock', source_goods_data[goods_id].stock_num);//右边库存的显示
                            $('.source_data_' + goods_id).find('.stock').html('剩余') + source_goods_data[goods_id].stock_num + source_goods_data[goods_id].unit;
                        } else {
                            layer.msg('库存不足');
                            return false;
                        }
                    }
                    shop_mall.tips(data.name + '加1');
				}
			}
		}

		if (data != null) {
			if (new_data.length > 0) {
				new_data.reverse().push(data);
				new_data.reverse();
			} else {
				new_data.push(data);
			}
		}
		goods_data.goods_data = new_data;
		window.localStorage.setItem(local_left_cart_data_index, JSON.stringify(goods_data));
		this.loading_cart_html();
	},
	'loading_cart_html':function() {//左边购物车页面的加载
		var cathtml = '', isFirst = 0, total_price = 0;
		var goods_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
		$('.left_card_id').html('会员号: 无');
		$('.left_card_money').html('￥0');
		$('.left_name').html('用户名: 无');
		$('.left_phone').html('手机号: 无');
		var left_cart_data = '';
		if (goods_data != null) {
			left_cart_data = goods_data.goods_data;
			card_data = goods_data.card_data;
			if (card_data.phone) {
				$('.left_name').html('用户名: '+ card_data.name);
				$('.left_phone').html('手机号: '+ card_data.phone);
			}
			if (card_data.card_id) {
				$('.left_card_id').html('会员号: ' + card_data.card_id);
				$('.left_card_money').html('￥' + card_data.card_money);
			}
		}
		for (var i in left_cart_data) {
			if (isFirst == 0) {
				cathtml += '<tr class="on goods_' + left_cart_data[i].goods_id + '" data-id="' + left_cart_data[i].goods_id + '" data-num="' + left_cart_data[i].num + '" data-price="' + left_cart_data[i].price + '" data-goods_weight="' + left_cart_data[i].goods_weight + '">';
			} else {
				cathtml += '<tr class="goods_' + left_cart_data[i].goods_id + '" data-id="' + left_cart_data[i].goods_id + '" data-num="' + left_cart_data[i].num + '" data-price="' + left_cart_data[i].price + '" data-goods_weight="' + left_cart_data[i].goods_weight  + '">';
			}
			isFirst ++;
			
			cathtml += '<td width="42">' + isFirst + '</td>';
			cathtml += '<td class="tl" width="235">';
			cathtml += '<h2>' + left_cart_data[i].name + '</h2>';
			cathtml += '<p>' + left_cart_data[i].number + '</p>';
			cathtml += '</td>';
			if(left_cart_data[i].sell_type==2){
				if(left_cart_data[i].code_weight > 0){
                    cathtml += '<td width="55">' + (left_cart_data[i].code_weight) +'g</td>';
				}else{
                    cathtml += '<td width="55">' + left_cart_data[i].goods_weight * left_cart_data[i].num  +'g</td>';
				}
			}else{
                cathtml += '<td width="55">' + left_cart_data[i].num + '</td>';
			}
			cathtml += '<td class="tl" width="115">';
			if(left_cart_data[i].code_price > 0){
                cathtml += '￥<span class="price">' + (parseFloat(left_cart_data[i].code_price)) + '</span>';
            }else{
                cathtml += '￥<span class="price">' + parseFloat((parseFloat(left_cart_data[i].price) * left_cart_data[i].num).toFixed(2)) + '</span>';
			}
			cathtml += '</td>';
			cathtml += '</tr>';
            if(left_cart_data[i].code_price > 0) {
                total_price += parseFloat(left_cart_data[i].code_price);
            }else{
                total_price += parseFloat(left_cart_data[i].price) * left_cart_data[i].num;
			}
			if(!/(android)/.test(navigator.userAgent.toLowerCase())){
				$('.source_data_' + left_cart_data[i].goods_id).addClass('on');
			}
		}
		total_price = parseFloat(total_price.toFixed(2));
        if (cathtml == '') {
            $(".tabx_list table.slide").html('').hide();
        } else {
            $(".tabx_list table.slide").html(cathtml).show();
        }
		$(".number .ef2").text(total_price);
		
		number_or_name_focus();
		// div高度
		$(".roll_table").height($(window).height() - 445);
		myScroll1 = new IScroll('.roll_table',{ click: true,mouseWheel:true,bounce:false});
	},
	'array_remove':function(data, index) {
		var new_data = [];
		for (var i in data) {
			if (index != i) {
				new_data.push(data[i]);
			}
		}
		return new_data;
	},
	//挂单操作
	'pause_cart':function(opt, index){
		pause_cart_data = $.parseJSON(window.localStorage.getItem(local_pause_cart_data));
		var new_datas = []
		for (var i in pause_cart_data) {
			new_datas.push(pause_cart_data[i]);
		}
		if (opt == '+') {
			left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
			
			var goods_data = '';
			if (left_cart_data != null) {
				goods_data = left_cart_data.goods_data;
			}
			if (goods_data.length > 0) {
				new_datas.push(left_cart_data);
			}
			window.localStorage.removeItem(local_left_cart_data_index)
			this.format_left_data('---');
			$('.left_card_id').html('会员号: 无');
			$('.left_card_money').html('￥0');
			$('.left_name').html('用户名: 无');
			$('.left_phone').html('手机号: 无');
			shop_mall.tips('挂单成功');
			
		} else if (opt == '-') {
			window.localStorage.setItem(local_left_cart_data_index, JSON.stringify(new_datas[index]));
			this.loading_cart_html();
			new_datas = shop_mall.array_remove(new_datas, index);
			shop_mall.tips('调出挂单成功');
		} else if (opt == '--') {
			window.localStorage.setItem(local_left_cart_data_index, JSON.stringify(new_datas[index]));
			this.format_left_data('---');
			new_datas = shop_mall.array_remove(new_datas, index);
		}
		window.localStorage.setItem(local_pause_cart_data, JSON.stringify(new_datas));
		myScroll1.refresh();
		
		this.pause_cart_count();
	},
	'pause_cart_html':function(){
		var left_menu = '', right_table = '';
		pause_num = 0;
		
		pause_cart_data = $.parseJSON(window.localStorage.getItem(local_pause_cart_data));
		var this_goods_data = '';
		for (var t in pause_cart_data) {
			pause_num ++;
			left_menu += '<li data-order_num="' + t + '">' + pause_num + '</li>';
			right_table += '<table class="end">';
			this_goods_data = pause_cart_data[t].goods_data;
			for (var s in this_goods_data) {
				right_table += '<tr>';
				right_table += '<td width="48">' + parseInt(parseInt(s) + 1) + '</td>';
				right_table += '<td width="138">' + this_goods_data[s].number + '</td>';
				right_table += '<td width="187">' + this_goods_data[s].name + '</td>';
				right_table += '<td width="50">' + this_goods_data[s].num + '</td>';
				right_table += '<td width="50">' + this_goods_data[s].price + '</td>';
				right_table += '<td width="75">' + parseFloat(this_goods_data[s].num * this_goods_data[s].price).toFixed(2) + '</td>';
				right_table += '</tr>';
			}
			right_table += '</table>';
		}
		$('.guadan .leaguer ul').html(left_menu);
		$('.member_list .tab_end .tab_table').html(right_table);
		$(".guadan, .shadow").show();
		myScroll2.refresh();
		myScroll3.refresh();
		
		$('.leaguer li').eq(0).addClass("on").siblings("li").removeClass("on");
		$(".tab_table table").eq(0).show().siblings("table").hide();
		this.pause_cart_count();
	},
	'pause_cart_count':function(){
		pause_num = 0;
		pause_cart_data = $.parseJSON(window.localStorage.getItem(local_pause_cart_data));
		for (var t in pause_cart_data) {
			pause_num ++;
		}
		if (pause_num > 0) {
			$(".right_1uick .gd").find("em").html(pause_num).show();
		} else {
			$(".right_1uick .gd").find("em").html(pause_num).hide();
		}
		return pause_num;
	},
	'search':function(){
		var key = $('#number_or_name').val();
		//if(key.length==13){ //对接电子称
		    var number = key.substr(2, 5);
            for (var i in source_goods_data) {
                if (source_goods_data[i].number == number || source_goods_data[i].name == key || source_goods_data[i].number == key) {
                    if(source_goods_data[i].sell_type == 2){
                    	var money = parseFloat(parseFloat(key.substr(7, 5))/100).toFixed(2);
                    	var unit_price = parseFloat(source_goods_data[i].unit_price);
                    	var code_weight = parseFloat(money/(unit_price * 2)).toFixed(3) * 1000;
                    	this.format_left_data(source_goods_data[i].goods_id, '+', {'goods_id':source_goods_data[i].goods_id, 'name':source_goods_data[i].name, 'unit':source_goods_data[i].unit, 'num':1, 'price':source_goods_data[i].price, 'number':source_goods_data[i].number,'goods_weight':source_goods_data[i].goods_weight,'sell_type':source_goods_data[i].sell_type,'code_weight':code_weight,'code_price':money});
                    }else{
                        this.format_left_data(source_goods_data[i].goods_id, '+', {'goods_id':source_goods_data[i].goods_id, 'name':source_goods_data[i].name, 'unit':source_goods_data[i].unit, 'num':1, 'price':source_goods_data[i].price, 'number':source_goods_data[i].number});
                    }
                    $('#number_or_name').val('');
                    break;
                }
            }
        /*}else{
            for (var i in source_goods_data) {
                if (source_goods_data[i].name == key || source_goods_data[i].number == key) {
                    // $('.source_data_' + source_goods_data[i].goods_id).addClass('on');
                    this.format_left_data(source_goods_data[i].goods_id, '+', {'goods_id':source_goods_data[i].goods_id, 'name':source_goods_data[i].name, 'unit':source_goods_data[i].unit, 'num':1, 'price':source_goods_data[i].price, 'number':source_goods_data[i].number});
                    $('#number_or_name').val('');
                    break;
                }
            }
		}*/
	},
	'pay_success_back':function(){
		window.localStorage.removeItem(local_left_cart_data_index);
		order_id = 0;

		$('#coupon_price').data('coupon','0').data('price','0').css('color','#ccc').html('选择优惠券');

		this.format_data();
		this.init_data();
	},
	'go_pay':function(paymethod, auth_code, offline_pay,phone){
		if(this.postNow == true && flag_pay==false){
			layer.msg('正在请求中，请稍等');
			return false;
		}
		$('.fix .chat_end .firm').html('请求中...');
		if (offline_pay == undefined) offline_pay = -1;
		this.postNow = true;
		var coupon = $('#coupon_price1').data('coupon'), card_money = $('#user_card_money').data('price'), price = parseFloat($('#pay_price').data('price')), uid = 0, card_id = 0, discount = 10;
		var change_reason = $('#change_price_reason').val();
		var sys_coupon = $('#coupon_price2').data('coupon');
		var is_use_score = $('#score_deducte_selecet').attr("checked") ? 1 : 0;
		var	score_used_count = $('#score_deducte').data('num');
		var	score_deducte = $('#score_deducte').data('price');
		// var	vip_discount_money = $('#vip_discount_money').val();
		var	discount_money = $('#discount_money').val();
		
		if (left_cart_data.card_data != '') {
			discount = parseFloat(left_cart_data.card_data.discount);
			if (discount > 0) {
			} else {
				discount = 10;
			}
			uid = left_cart_data.card_data.uid;
			card_id = left_cart_data.card_data.card_id;
		}

		flag_pay=false;
		var ajaxTimeoutTest = $.ajax({ 
			　　url:arrival_pay,  //请求的URL
			　　timeout : 10000, //超时时间设置，单位毫秒
			　　type : 'post',  //请求方式，get或post
			　　data :{order_id:order_id, 'auth_code':auth_code, 'repay':repay, 'auth_type':paymethod, 'change_reason':change_reason, 'price':price, 'card_id':card_id, 'uid':uid, 'discount':discount, 'coupon':coupon, 'card_money':card_money, 'offline_pay':offline_pay,'phone':phone,'sys_coupon':sys_coupon,'is_use_score':is_use_score,'score_used_count':score_used_count,'score_deducte':score_deducte,discount_money:discount_money,no_discount_money:no_discount_money},  //请求所传参数，json格式
			　　dataType:'json',//返回的数据格式
			　　success:function(result){ //请求成功的回调函数
					shop_mall.postNow = false;
			　　　　if(result.status == 1){
						layer.msg('支付成功');
						shop_mall.pay_success_back();
						//清空会员卡缓存 
						window.localStorage.setItem(local_left_cart_data_index,null);
						window.top.location.reload();
					}
					else if(result.status == 2){
						layer.msg(result.info);
						repay = 1;
					}
					else{
						if (typeof result.errcode != undefined && result.errcode == 'USERPAYING') {
							flag_pay = false;
							layer.confirm(result.info, {
								btn: ['是(Y)','否(N)'] //按钮
							}, function(index){
								layer.close(index);
								$('.fix .chat_end .firm').trigger("click");
							}, function(){});
							
						} else {
							flag_pay = true;
							layer.msg(result.info);
						}
					}
					if(paymethod!='balance'){						
						$('.fix .chat_end .firm').html('确认支付');
					}else{
						$('.fix .chat_end .firm').html('确认支付 (￥'+price.toFixed(2)+')');
					}
			　　},
			　　complete : function(XMLHttpRequest,status){ //请求完成后最终执行参数
			　　　　if(status=='timeout'){
			 　　　　　 ajaxTimeoutTest.abort();
			　　　　　  layer.msg('请求超时');
			　　　　}
			　　}
			});
			
		// $.post(arrival_pay,{order_id:order_id, 'auth_code':auth_code, 'auth_type':paymethod, 'change_reason':change_reason, 'price':price, 'card_id':card_id, 'uid':uid, 'discount':discount, 'coupon':coupon, 'card_money':card_money, 'offline_pay':offline_pay},function(result){
			// shop_mall.postNow = false;
			// if(result.status == 1){
				// layer.msg('支付成功');
				// shop_mall.pay_success_back();
				// window.top.location.reload();
			// }else{
			    // if (typeof result.errcode != undefined && result.errcode == 'USERPAYING') {
			        // layer.confirm(result.info, {
                        // btn: ['是(Y)','否(N)'] //按钮
                    // }, function(index){
                        // layer.close(index);
                        // $('.fix .chat_end .firm').trigger("click");
                    // }, function(){});
			        
			    // } else {
			        // layer.msg(result.info);
			    // }
			// }
			// $('.fix .chat_end .firm').html('确认支付');
		// });
	},
	'get_score':function(paymethod, auth_code, offline_pay,phone){
		
		$.ajax({ 
			　　url:ajax_get_score_discount_url,  //请求的URL
			　　timeout : 10000, //超时时间设置，单位毫秒
			　　type : 'post',  //请求方式，get或post
			　　data :{order_id:order_id, 'auth_code':auth_code, 'auth_type':paymethod, 'change_reason':change_reason, 'price':price, 'card_id':card_id, 'uid':uid, 'discount':discount, 'coupon':coupon, 'card_money':card_money, 'offline_pay':offline_pay,'phone':phone},  //请求所传参数，json格式
			　　dataType:'json',//返回的数据格式
			　　success:function(result){ //请求成功的回调函数
				  console.log("点击二")　
				  if(result.status == 1){
						
					}else{
						
					}
					
			　　}
			});
	},
	'count_price_html':function(){
		var discount = $('#card_discount').data('discount'), reduce_money1 = $('#coupon_price1').data('price'),reduce_money2 = $('#coupon_price2').data('price');
		// var payPrice = parseFloat($('#pay_price').data('price'));//parseFloat(discount * 0.1 * order_price - reduce_money).toFixed(2));

		var go_pay_money = $('#order_price').data('price');

		// var discount_compare = false;
		// var discount_total_money = 0;
		// var vip_discount = $('#vip_discount').val(),level_discount_type = $('#type').val(),vip_discount_type = $('#vip_discount_type').val();
		// var price,minus_price=0;
  //   	var vip_minus_price=0; // 会员等级优惠金额

		var no_discount = $('#no_discount').val() != '' ? parseFloat($('#no_discount').val()) : 0;
		// no_discount_money = $("#buy_discount").prop('checked') == true  ? no_discount : go_pay_money;
		no_discount_money = no_discount;
		if (no_discount_money>go_pay_money) {
			no_discount_money = go_pay_money;
		}
		// 买单优惠
		if ($("#buy_discount").prop('checked') == true) {
			if (discount_type == 1) {
				price = parseFloat(go_pay_money - no_discount_money) * parseFloat(discount_percent) / 10 + no_discount_money;// 买单优惠折扣后应付金额
				minus_price = (parseFloat(go_pay_money - no_discount_money) * (100 - discount_percent * 10) / 100).toFixed(2); // 买单优惠折扣金额
			} else if (discount_type == 2) {
				minus_price = (Math.floor(parseFloat(go_pay_money - no_discount_money) / parseFloat(condition_price)) * minus_money).toFixed(2);
				price = go_pay_money - minus_price;

			} else {
				minus_price = 0;
				price = go_pay_money;
			}
		}else{
			minus_price = 0;
			price = go_pay_money;
		}

		$('#discount_money_tip').html('-￥' + minus_price);
		$('#discount_money').val(minus_price);

		// 会员卡折扣
		left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
		var discount = 10, card_money = 0, use_card_money = 0, card_id = '无';
		if (left_cart_data) {
			if (left_cart_data.card_data != '') {
				discount = parseFloat(left_cart_data.card_data.discount);
				card_id = left_cart_data.card_data.card_id;
				card_money = left_cart_data.card_data.card_money;
			}
		}
		if (discount > 0 && discount != 10) {
			$('#card_discount').html(discount + '折').data('discount', discount);
		} else {
			discount = 10;
			$('#card_discount').html('无折扣').data('discount', 10);
		}
		if (card_id) {
			$('#user_card_number').html(card_id);
			$('#user_card_total').html('￥' + card_money).data('price', card_money);
		}
        // $(".cho_yhj").text('选择优惠券').css("color", "#cccccc");

		var payPrice = parseFloat(parseFloat(discount * 0.1 * (price-no_discount_money)+no_discount_money).toFixed(2));
		$('#pay_price').html('￥' + payPrice).data('price', payPrice);
		$('#pay_price').data('old_price', payPrice);

		// if (card_money > payPrice) {
		// 	$('#user_card_money').html('￥' + payPrice).data('price', payPrice);
		// } else {
		// 	$('#user_card_money').html('￥' + card_money).data('price', card_money);
		// }

		// 折扣后金额
		go_pay_money = payPrice;

		if (reduce_money1) {
			go_pay_money = parseFloat((go_pay_money - reduce_money1).toFixed(2));
		}

		if (reduce_money2) {
			go_pay_money = parseFloat((go_pay_money - reduce_money2).toFixed(2));
		}
		merc_id = $('#coupon_price1').data('coupon');//	商家优惠券
		sysc_id = $('#coupon_price2').data('coupon');//	平台优惠券
		card_id = $('#card_number').html();
		var uid = 0;
		if (left_cart_data) {
			if (left_cart_data.card_data) {
				uid = left_cart_data.card_data.uid;
			}
		}

		var isChange = $('#isChange').val();
		if (isChange == 1) {
			changePrice(payPrice, '');
			return false;
		}else{
			// 计算积分
			$.ajax({ 
			　　url:ajax_get_score_discount_url,  //请求的URL
			　　timeout : 10000, //超时时间设置，单位毫秒
			　　type : 'post',  //请求方式，get或post
			　　data :{type:'shop',order_id:order_id,merc_id:merc_id,sysc_id:sysc_id,card_id:card_id,total_money:go_pay_money,uid:uid},  //请求所传参数，json格式
			　　dataType:'json',//返回的数据格式
			　　success:function(res){ //请求成功的回调函数
				  console.log("点击三")　
				  if (!res.error) {
						$('#score_deducte').data('price', parseFloat(res.score_deducte).toFixed(2));
						$('#score_deducte').html(parseFloat(res.score_deducte).toFixed(2));
						$('#score_can_use_count').data('num', res.score_can_use_count);
						$('#score_can_use_count').html(res.score_can_use_count);
						if(res.score_can_use_count){
							// $('#score_deducte_selecet').prop('checked',true);
							$('.score_discount').show();
						}else{
							$('.score_discount').hide();
							$('#score_deducte_selecet').prop('checked',false);
						}
					}else{
						$('.score_discount').hide();
						$('#score_deducte_selecet').prop('checked',false);
					}
			　　}
			});
		}
		var score_deducte = $('#score_deducte').data('price');
		if ($('#score_deducte_selecet').attr("checked") && score_deducte>0) {
			go_pay_money = parseFloat((go_pay_money - score_deducte).toFixed(2));
		}

		if (go_pay_money <= 0) {
			go_pay_money = 0;
		}

		// $('#pay_price').html('￥' + go_pay_money);
		// $('#pay_price').data('price',go_pay_money);
		// var payPrice = parseFloat($('#pay_price').data('price'));
		var card_money = $('#user_card_money').data('price');
		var card_total_money = parseFloat($('#user_card_total').data('price'))
		
		if (card_total_money>go_pay_money) {
			$('#user_card_money').html('￥' + go_pay_money).data('price', go_pay_money);
			go_pay_money = 0;
		}else{
			go_pay_money = parseFloat(go_pay_money - card_total_money).toFixed(2);
			$('#user_card_money').html('￥' + card_total_money).data('price', card_total_money);
		}

		$('#go_pay_money').html('<span class="still_zf">还需支付：</span>￥' + go_pay_money).data('price', go_pay_money);

		if (go_pay_money <= 0) {
			$('.pay .confirm').show();
			$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').hide();
		} else {
			$('.pay .confirm').hide();
			$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').show();
		}
	},
	'tips':function(msg){
//		clearTimeout(tip_out_time);
		$('.prompt_span').html(msg);
//		tip_out_time = setTimeout(function(){$('.prompt_span').html('');}, 1000);
	}
};

function number_or_name_focus(){
	if(!/(android)/.test(navigator.userAgent.toLowerCase())){
		$('#number_or_name').focus();
	}
}

var keyCodeArr = {'48':'0','49':'1','50':'2','51':'3','52':'4','53':'5','54':'6','55':'7','56':'8','57':'9','96':'0','97':'1','98':'2','99':'3','100':'4','101':'5','102':'6','103':'7','104':'8','105':'9'};
$(document).ready(function(){	
	$('body').keyup(function(e){
		if(e.keyCode == 13){
			if($(".left_input input").val().length>0){
				shop_mall.search();
			}
		}else{
			if(document.activeElement.id == '' && keyCodeArr[e.keyCode]){
				$('#number_or_name').val($('#number_or_name').val()+keyCodeArr[e.keyCode]);
			}
		}
	});
	$('.payMoney').click(function(){
		id=$(this).next().attr("id");
		window.pigcmspackapp.scan('','','','backMoney');
	});
	function backMoney(res){

		$("#"+id).val(res)
	}
	$('.back').click(function(){
		location.href = $(this).data('url');
	});
	//初始化数据
//	window.localStorage.clear();
	shop_mall.init_data();
	$('#number_or_name').keyup(function(){
		var v = $(this).val();
		if (v == '/' || v == '*' || v == '+' || v == '-' || v == 'u' || v == 'h' || v == 'y' || v == 'd' || v == 'n' || v == 'z') {
			$('#number_or_name').val('');
		}
		return false;
	});
	// 右侧内容切换
	$(document).on('click', '.right_top .swiper-slide', function() {
		number_or_name_focus();
		$(this).addClass("on").siblings(".swiper-slide").removeClass("on");
		$(".tab_ul ul").eq($(this).index()).show().siblings("ul").hide();
	});
	//左侧购物车中的选择
	$(document).on("click", "table.slide tr", function() {
		$(this).addClass("on").siblings().removeClass("on");
	});
	
	myScroll3 = new IScroll('.leaguer', {click:true, mouseWheel:true, bounce:false});
	myScroll2 = new IScroll('.tab_end', {click:true, mouseWheel:true, bounce:false});
	
	//挂单页面中的挂单项切换
	$(document).on('click', '.leaguer li', function() {
		$(this).addClass("on").siblings("li").removeClass("on");
		$(".tab_table table").eq($(this).index()).show().siblings("table").hide();
		myScroll2.scrollTo(0, 0);
		myScroll2.refresh();
	});
	//挂单页的商品行选择效果
	$(document).on('click', '.tab_table tr', function(){
		$(this).addClass("on").siblings().removeClass("on");
	});
	$(document).on('click', '.reload', function(){
		var index_loading = layer.load(0, {shade: [0.1,'#000000']});
		$.post(ajax_goods_list, {refresh:0}, function(response){
			if (response.err_code) {
				layer.msg(response.data);
			} else {
				window.localStorage.setItem(local_total_index, JSON.stringify(response.data));
				shop_mall.init_data_html(response.data);
			}
			layer.close(index_loading);
		}, 'json');
	});
	
	//挂单的取消
	$(".op_cancel").click(function(){
		number_or_name_focus();
		$(".guadan, .shadow").hide();
	});
	
	//挂单删除
	$(".op_del").click(function(){
		layer.confirm('是否删除挂单号[' + (parseInt($('.leaguer').find('.on').data('order_num')) + 1) + ']？', {
						btn: ['是(Y)','否(N)'] //按钮
					}, function(index){
						layer.close(index);
						shop_mall.pause_cart('--', $('.leaguer').find('.on').data('order_num'));
						shop_mall.pause_cart_html();
						var num = shop_mall.pause_cart_count();
						if (num < 1) {
							$(".guadan, .shadow").hide();
						}
					}, function(){});
		
	});
	
	//选取挂单中的某个订单返回到购物车
	$('.op_confirm').click(function(){
		shop_mall.pause_cart('-', $('.leaguer').find('.on').data('order_num'));
		$(".guadan, .shadow").hide();
	});
	
	//右边的菜单点击
	var touchmove = false;
	var scroll_start, scroll_end;
	$(document).on('touchstart', '.tab_ul ul li', function(){
		console.log('111', $(this).offset().top)
		scroll_start = $(this).offset().top
	});
	$(document).on('touchmove', '.tab_ul ul li', function(){
		touchmove = true
		console.log('222', $(this).offset().top)
	});
	$(document).on('touchend', '.tab_ul ul li', function(){
		console.log('333', $(this).offset().top)
		scroll_end = $(this).offset().top
	});
	$(document).on('click', '.tab_ul ul li', function(){
		console.log('444', $(this).offset().top)
		if (touchmove && scroll_start != scroll_end) {
			return
		}
		if($(this).data('sell_type')==2){
            var name = $(this).data('name'), number = $(this).data('number'), price = parseFloat($(this).data('price')), goods_id = $(this).data('id'), unit = $(this).data('unit'),goods_weight = $(this).data('goods_weight'),sell_type = $(this).data('sell_type');
            shop_mall.format_left_data(goods_id, '+', {'goods_id':goods_id, 'name':name, 'unit':unit, 'num':1, 'price':price, 'number':number,'goods_weight':goods_weight,'sell_type':sell_type,'code_weight':0});
        }else{
            var name = $(this).data('name'), number = $(this).data('number'), price = parseFloat($(this).data('price')), goods_id = $(this).data('id'), unit = $(this).data('unit');
            shop_mall.format_left_data(goods_id, '+', {'goods_id':goods_id, 'name':name, 'unit':unit, 'num':1, 'price':price, 'number':number});
        }
	});
	
	$(document).on('click', '.right_1uick li', function(){
		var opt = $(this).data('opt');
		switch (opt){
		case '/'://结算 /点击
			left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
			var goods_data = '';
			if (left_cart_data != null) {
				goods_data = left_cart_data.goods_data;
			}
			if (goods_data.length > 0) {
				var index_loading = layer.load(0, {shade: [0.1,'#000000']});
				$.post(shop_order_save, {data:left_cart_data}, function(response){
					shop_mall.tips('进入结算');
					if (response.error_code) {
						layer.msg(response.msg);
					} else {
						order_id = response.order_id;
						order_price = response.price;
						$('#order_id').html(response.real_orderid);
						$('#order_price span').html('￥' + response.price);
						$('#order_price').data('price', response.price);
						$(".settlement, .shadow").show();
						$('#pay_qrcode_url').attr('src', response.pay_qrcode_url);
						if (response.discount_msg != '') {
							$('.disk').html(response.discount_msg);
							$('#order_price').addClass('on');
						} else {
							$('.disk').html('');
							$('#order_price').removeClass('on');
						}
						// left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
						// var discount = 10, card_money = 0, use_card_money = 0, card_id = '无';
						// if (left_cart_data.card_data != '') {
						// 	discount = parseFloat(left_cart_data.card_data.discount);
						// 	card_id = left_cart_data.card_data.card_id;
						// 	card_money = left_cart_data.card_data.card_money;
						// }
						// if (discount > 0 && discount != 10) {
						// 	$('#card_discount').html(discount + '折').data('discount', discount);
						// } else {
						// 	discount = 10;
						// 	$('#card_discount').html('无折扣').data('discount', 10);
						// }
						// $('#user_card_number').html(card_id);
						// $('#user_card_total').html('￥' + card_money).data('price', card_money);
      //                   $(".cho_yhj").text('选择优惠券').css("color", "#cccccc");

						// var payPrice = parseFloat(parseFloat(discount * 0.1 * response.price).toFixed(2));
						// $('#pay_price').html('￥' + payPrice).data('price', payPrice);
						// $('#pay_price').data('old_price', payPrice);
						// if (card_money > payPrice) {
						// 	$('#user_card_money').html('￥' + payPrice).data('price', payPrice);
						// } else {
						// 	$('#user_card_money').html('￥' + card_money).data('price', card_money);
						// }
						// $('#go_pay_money').html('<span class="still_zf">'+'还需支付：')+'</span>￥' + payPrice).data('price', payPrice);
						shop_mall.count_price_html();
					}
					layer.close(index_loading);
				}, 'json');
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
			}
			
			break;
		case '+':// + 点击
			if ($("table.slide").find('.on').data('id')) {
				shop_mall.format_left_data($("table.slide").find('.on').data('id'), '+');
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
			}
			
			break;
		case '-':// - 点击
			if ($("table.slide").find('.on').data('id')) {
				shop_mall.format_left_data($("table.slide").find('.on').data('id'), '-');
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
			}
//			shop_mall.format_left_data($("table.slide").find('.on').data('id'), '-');
			break;
		case '*'://数量
			if ($("table.slide").find('.on').size() == 1) {
				$(".counter").data('type', 0);//0 表示增加商品数量，1：支付的金额
				$(".counter").removeClass('xgcontainer').addClass('slcontainer');
				$(".counter").show().find("input")[0].focus();
				$('.shadow').show();
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
				number_or_name_focus();
			}
			break;
		case 'u'://u 删除 点击
			if ($("table.slide").find('.on').data('id')) {
				shop_mall.format_left_data($("table.slide").find('.on').data('id'), '--');
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
			}
			break;
		case 'h'://挂单
			left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
			var goods_data = '';
			if (left_cart_data != null) {
				goods_data = left_cart_data.goods_data;
			}
			pause_num = shop_mall.pause_cart_count();
			if (goods_data.length > 0) {
				shop_mall.pause_cart('+');
				shop_mall.pause_cart_count();
			} else if (pause_num > 0) {
				shop_mall.pause_cart_html();
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
			}
			break;
		case 'y'://会员卡
			$('#card_name, #card_number, #card_number2, #card_sex, #card_phone, #card_money, #card_discount_p').html('');
			$('#card_value').val('');
			$('#card_score').html('可用积分：0');
			$(".card").show().find("input")[0].focus();
			$(".query_qr").css("background","#ccc");
			$('.shadow').show();
			break;
		case 'd'://清空全部
			if ($("table.slide").find('.on').data('id')) {
				shop_mall.format_left_data('---');
			} else {
				shop_mall.tips('未选择任何商品，无法操作！');
			}
			break;

		default:
			break;
		}
	});
	//修改支付的价格
	$('.mod_hand').click(function(){
//		$('.change_price').find('input[name=change_price]').val($('#pay_price').data('price'));
		$('#show_last_price').html($('#pay_price').data('price'));
		$('.change_price, .shadow_two').show();
		$('.change_price').find('input[name=change_price]').val($('#pay_price').data('price')).focus();
	});
	
	$('.change_price .hf').click(function(){
		var price = parseFloat($('#pay_price').data('old_price'));
//		if (card_money > price) {
//			$('#user_card_money').html('￥' + price).data('price', price);
//		}
		$('#isChange').val(0);
		changePrice(price, '');
		shop_mall.count_price_html();
		return false;
//		$('#pay_price').html('￥' + price).data('price', price);
//		$('#change_price_reason').val('');
//		$('.mod_hui').html('').hide();
//		$('.change_price, .shadow_two').hide();
//		shop_mall.count_price_html();
	});
	
	$('.change_price .qr').click(function(){
		console.log(".change_price .qr")
		var change_price = parseFloat($('.change_price').find('input[name=change_price]').val()), old_price = parseFloat($('#pay_price').data('old_price')), card_money = parseFloat($('#user_card_money').data('price'));
		var change_price_reason = $('.change_price').find('input[name=change_price_reason]').val();
		if (change_price <= 0 || change_price == old_price) {
			$('#change_price_reason').val('');
			$('.mod_hui').html('').hide();
			$('.change_price, .shadow_two').hide();
			return false;
		}
		if (change_price_reason.length > 8) {
			layer.msg('理由最多8个字');
			return false;
		}
		$('#isChange').val(1);
		changePrice(change_price, change_price_reason);
		
		
//		if (card_money > change_price) {
//			$('#user_card_money').html('￥' + change_price).data('price', change_price);
//		}
//		$('#pay_price').html('￥' + change_price).data('price', change_price);
//		var html = '<em>修改前：￥' + old_price + '</em>';
//		if (change_price_reason.length > 0) {
//			html += '<em>备注：' + change_price_reason + '</em>';
//		}
//		$('#change_price_reason').val(change_price_reason);
//		$('.mod_hui').html(html).show();
//		$('.change_price, .shadow_two').hide();
//		shop_mall.count_price_html();
		return false;
	});
	
	//修改金额
	$(".modify").click(function(){
		$(".counter").data('type', 1);//0 表示增加商品数量，1：支付的金额
		$(".counter").removeClass('slcontainer').addClass('xgcontainer');
		$(".counter").show().find("input")[0].focus();
		$(".shadow_two").show();
	});
	
	//整单取消 
	$(".set_fr .cancel").click(function(){
		layer.confirm('真的取消订单吗？'), {
			btn: ['是(Y)','否(N)'] //按钮
		}, function(index){
			layer.close(index);
			$(".right_1uick .qk").trigger("click");
			$(".settlement, .shadow").hide();
			shop_mall.tips('取消结算成功');
			window.location.reload();
		};
	});
	//计算器输入值的处理
	$('.counter input.text').keyup(function(e){
		if (e.keyCode == 13) {
			$(this).parents('.counter').find('.getResult').trigger('click');
			return false;
		}
		var type = $(this).parents('.counter').data('type')
		if (type == 0) {
			var text = $(this).val(), last_v = $(this).val().substr(-1, 1);
			if (last_v.search(/\d/) == -1) {
				$(this).val(text.substr(0, text.length - 1));
				return false;
			}
		}
			
	});
	//计算器点击确定的处理结果
	$(".getResult").click(function(){
		var type = $(this).parents('.counter').data('type');
		var num = parseFloat($.trim($(this).parents('.counter').find("input.text").val()));
		if (isNaN(num)) {
			$(".counter, .shadow_two").hide();
			return false;
		}
		
		if (type == 0) {
			if (num < 1) {
				layer.msg('数量必须是大于0的整数');
				return false;
			}
			if (num % 1 !== 0) {
				layer.msg('数量必须是大于0的整数');
				return false;
			}
			shop_mall.format_left_data($("table.slide").find('.on').data('id'), null, null, num);
			$(".counter, .shadow").hide();
		} else if (type == 1) {
			var card_total_money = parseFloat($('#user_card_total').data('price')), pay_price = $('#pay_price').data('price');
			if (num > card_total_money) {
				layer.msg('超过了可用余额');
				return false;
			}
			var go_pay_money = pay_price;
			var reduce_money1 = $('#coupon_price1').data('price'),reduce_money2 = $('#coupon_price2').data('price');
			if (reduce_money1) {
				go_pay_money = parseFloat((go_pay_money - reduce_money1).toFixed(2));
			}

			if (reduce_money2) {
				go_pay_money = parseFloat((go_pay_money - reduce_money2).toFixed(2));
			}

			var isChange = $('#isChange').val();
			if (isChange == 1) {
				changePrice(payPrice, '');
				return false;
			}
			var score_deducte = $('#score_deducte').data('price');
			if ($('#score_deducte_selecet').attr("checked") && score_deducte>0) {
				go_pay_money = parseFloat((go_pay_money - score_deducte).toFixed(2));
			}

			if (num > go_pay_money) {
				layer.msg('超过了待支付的金额');
				return false;
			}
			$('#user_card_money').html('￥' + num).data('price', num);
			
			// 修改会员卡使用金额
			var go_pay_money = parseFloat(go_pay_money-num);
			$('#go_pay_money').html('<span class="still_zf">'+'还需支付：')+'</span>￥' + go_pay_money.data('price', go_pay_money);
			if (go_pay_money <= 0) {
				$('.pay .confirm').show();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').hide();
			} else {
				$('.pay .confirm').hide();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').show();
			}

			// shop_mall.count_price_html(true);
			$(".counter, .shadow_two").hide();
		} else if (type == 2) {
			var price = parseFloat($('#go_pay_money').data('price'));
			if (num < price) {
				layer.msg('金额不能小于应付的金额');
				return false;
			}
			$('#offline_finish_money').html(num);
			$('#offline_return_money').html(parseFloat((parseFloat(num) - price).toFixed(2)));
		}
		$(this).parents('.counter').find("input.text").val('');
	});
	
	// 点击计算器中的数字
	$(".show").click(function(){
		var data = $(this).val(), text = $(this).parents('.counter').find('.text').val();
		if (text != '0') {
			$(this).parents('.counter').find('.text').val(text + '' + data);
		} else {
			if (!(data == '0' || data == '00')) {
				$(this).parents('.counter').find('.text').val(data);
			}
		}
		$(this).parents('.counter').find('.text').focus();
	});
	//点击计算器中的点
	$(".point").click(function(){
		if ($(this).parents('.counter').data('type') == 0) return false;
		var text = $(this).parents('.counter').find('.text')[0];
		var p = $(this)[0];
		if (text.value.search(/\./) != -1) return false;
		if (text == '') text = 0;
		text.value += p.value;
		$(this).parents('.counter').find('.text').focus();
	});
	
	//点击计算器的清空
	$(".funclear").click(function(){
		$(this).parents('.counter').find('.text').val('').focus();
	});
	
	//点击计算器的退格键
	$(".funback").click(function(){
		var text = $(this).parents('.counter').find('.text').val(); 
		if (text == "0" || text == "") {
			$(this).parents('.counter').find('.text').val('');
		} else {
			var last_value = text.substr(-2, 1);
			if (last_value == '.') {
				$(this).parents('.counter').find('.text').val(text.substr(0, text.length - 2));
			} else {
				$(this).parents('.counter').find('.text').val(text.substr(0, text.length - 1));
			}
		}
		$(this).parents('.counter').find('.text').focus();
	});
	// 计算器取消
	$(".counter .cancel").click(function(){
		if (!$(".shadow_two").is(":hidden")) {
			$(".counter, .shadow_two").hide();
		} else {
			number_or_name_focus();
			$(".counter, .shadow").hide();
		}
	});

	// 商户优惠
	$("#buy_discount").change(function(){
		if($(this).prop('checked')){
			$('#buy_discount_set').show();
			$('.no_discount_div').show();
		}else{
			$('#buy_discount_set').hide();
			// if(!$('#vip_discount_open').prop('checked')){
				// $('.no_discount_div').hide();
			// }
		}
		shop_mall.count_price_html();
	});

	$('#no_discount').keyup(function(e){
		shop_mall.count_price_html();
	});
	if ($("#buy_discount") != 'undefined') {
		$('#buy_discount').prop('checked','checked');
		$('#buy_discount').trigger('change');
	}

	// 会员卡等级优惠
	/*$("#vip_discount_open").change(function(){
		if($(this).prop('checked')){
			// $('#vip_discount_offstr').show();
			$('#vip_discount_set').show();
			$('#no_discount_div').show();
		}else{
			// $('#vip_discount_offstr').hide();
			$('#vip_discount_set').hide();
			if(!$('#buy_discount').prop('checked')){
				$('#no_discount_div').hide();
			}
		}
		shop_mall.count_price_html();
	});*/

	//会员卡搜索输入框的情况按钮
	$('.inbox_top em').click(function(){
		$('.inbox_top input').val('').trigger('input');
	});
	$('#card_value').keyup(function(e){
		if (e.keyCode == 13) {
			$('.query_cx').trigger('click');
		}
	});
	//查询用户
	var temp_data = null;
	$('.query_cx').click(function(){
		var key = $('#card_value').val();
		if (key.length < 1) return false;
		var index_loading = layer.load(0, {shade: [0.1,'#000000']});
		$.post(ajax_card_url, {'key':key}, function(response){
			if (response.err_code) {
				$('.card .information').find('.clr').hide();
				$('.card .information').find('.img').show();
				$(".query_qr").css("background","#ccc");
			} else {
				$('.card .information').find('.clr').show();
				$('.card .information').find('.img').hide();
				$(".query_qr").css("background","#0099dc");
				temp_data = response.data;
				
				$('#card_name').html(temp_data.name);
				$('#card_phone').html(temp_data.phone);
				$('#card_sex').html(temp_data.sex);
				if (temp_data.card_id) {
					$('#card_number').html(temp_data.card_id);
					$('#card_number2').html(temp_data.physical_id);
					$('#card_discount_p').html(parseFloat(temp_data.discount) + '折');
					$('#card_money').html(temp_data.card_money);
					$('#card_score').html('可用积分：' + temp_data.card_score);
				}else{
					$('#card_number').html('暂未领卡');
					$('#card_number2').html('暂未领卡');
					$('#card_discount_p').html('暂未领卡');
					$('#card_money').html('暂未领卡');
					$('#card_score').html('暂未领卡');
				}
			}
			layer.close(index_loading);
		}, 'json');
	});
	//使用用户
	$('.query_qr').click(function(){
		if (temp_data == null) return false; 
		left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
		if (left_cart_data == null) {
			left_cart_data = {'card_data':temp_data, 'goods_data':''};
		} else {
			left_cart_data.card_data = temp_data;
		}
		window.localStorage.setItem(local_left_cart_data_index, JSON.stringify(left_cart_data));
		$('.left_card_id').html('会员号: 无');
		$('.left_card_money').html('￥0');
		$('.left_name').html('用户名: 无');
		$('.left_phone').html('手机号: 无');
		if (temp_data.phone) {
			$('.left_name').html('用户名: '+ temp_data.name);
			$('.left_phone').html('手机号: '+ temp_data.phone);
		}
		if (temp_data.card_id) {
			// 使用会员卡
			$('.left_card_id').html('会员号: ' + temp_data.card_id);
			$('.left_card_money').html('￥' + temp_data.card_money);
		}
		
		$('#coupon_price').data('coupon','0').data('price','0').css('color','#ccc').html('选择优惠券');
		$('.mod_hui').html('');
		
		temp_data = null;
		$('.shadow, .card').hide();
	});
	//快捷键
	$(document).keydown(function(e) {
		var code = e.keyCode || e.which || e.charCode;
		switch (code)
		{
			case 38:// ↑
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					if ($(".tabx_list table.slide").find('.on').prev('tr').size() > 0) {
						$(".tabx_list table.slide").find('.on').removeClass('on').prev('tr').addClass('on');
					} else {
						$(".tabx_list table.slide").find('.on').removeClass('on')
						$(".tabx_list table.slide").find('tr').eq($(".tabx_list table.slide").find('tr').size() - 1).addClass('on');
					}
				}
				if(!$(".guadan").is(":hidden") && !$(".shadow").is(":hidden")){
					if ($('.guadan .leaguer ul').find('.on').prev('li').size() > 0) {
						$('.guadan .leaguer ul').find('.on').prev('li').trigger("click");
					} else {
						$('.guadan .leaguer ul').find('li').eq($('.guadan .leaguer ul').find('li').size() - 1).trigger("click");
					}
				}
				break;
			case 40:// ↓
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					if ($(".tabx_list table.slide").find('.on').next('tr').size() > 0) {
						$(".tabx_list table.slide").find('.on').removeClass('on').next('tr').trigger("click");
					} else {
						$(".tabx_list table.slide").find('.on').removeClass('on');
						$(".tabx_list table.slide").find('tr').eq(0).trigger("click");
					}
				}
				if(!$(".guadan").is(":hidden") && !$(".shadow").is(":hidden")){
					if ($('.guadan .leaguer ul').find('.on').next('li').size() > 0) {
						$('.guadan .leaguer ul').find('.on').next('li').trigger("click");
					} else {
						$('.guadan .leaguer ul').find('li').eq(0).trigger("click");
					}
				}
				break;
			case 68:// d删除全部
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					$(".right_1uick .qk").trigger("click");
				}
				break;
			case 107:// +加
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					$(".right_1uick .jia").trigger("click");
				}
				break;
			case 109:// -减
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					$(".right_1uick .jian").trigger("click");
				}
				break;
			case 85:// U删除
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					$(".right_1uick .del").trigger("click");
				}
				break;
			case 13: // 条码搜索
				if ($(".shadow_two").is(":hidden") && $(".shadow").is(":hidden")) {
					if($(".left_input input").val().length>0){
						shop_mall.search();
						return false;
					}
				}
				if (!$('.guadan').is(":hidden") && !$(".shadow").is(":hidden")) {
					$('.op_confirm').trigger("click");
					return false;
				}
				if(!$(".settlement").is(":hidden") && !$(".shadow").is(":hidden") && $(".shadow_two").is(":hidden")){
					// $(".pay .confirm").trigger("click");
					$(".pay .line").trigger("click");
					return false;
				}
				return false;
				break;
			case 106:// *数量
				if($(".slcontainer").is(":hidden") && $(".shadow").is(":hidden")){
					$(".right_1uick .sl").trigger("click");
					return false;
				}
				break;
			case 72:// H挂单
				if($(".guadan").is(":hidden") && $(".shadow").is(":hidden")){
					$(".right_1uick .gd").trigger("click");
				}
				break;
			case 115:// f4挂单删除
				if (!($('.guadan').is(":hidden") && $(".shadow").is(":hidden"))) {
					$('.op_del').trigger("click");
				}
				break;
			case 89:// y会员卡
				if($(".card").is(":hidden") && $(".shadow").is(":hidden")){
					$(".right_1uick .hyk").trigger("click");
					return false;
				}
				
				$('.layui-layer-btn0').trigger("click");
				break;
			case 78:// n
				$('.layui-layer-btn1').trigger("click");
				break;
			case 111:// / 结算
				if ($(".settlement").is(":hidden") && $(".shadow").is(":hidden")) {
					$(".right_1uick .js").trigger("click");
				}
				break;
			case 90:// z整单取消
				if (!$(".settlement").is(":hidden") && !$(".shadow").is(":hidden")) {
					$(".set_fr .cancel").trigger("click");
				}
				break;
			case 27:// ESC 返回
				//挂单返回
				if(!$(".guadan").is(":hidden") && !$(".shadow").is(":hidden") && !$(document).find('.layui-layer-shade').size()){
					number_or_name_focus();
					$(".guadan, .shadow").hide();
				}
				//结算订单返回
				if(!$(".settlement").is(":hidden") && !$(".shadow").is(":hidden") && $(".shadow_two").is(":hidden")){
					number_or_name_focus();
					$(".settlement, .shadow").hide();
				}
				//线下支付返回
				if(!$(".linepay").is(":hidden") && !$(".shadow_two").is(":hidden")){
					$(".linepay, .shadow_two").hide();
				}
				//支付宝返回
				if(!$(".alip").is(":hidden") && !$(".shadow_two").is(":hidden")){
					$(".alip, .shadow_two").hide();
				}
				//微信返回
				if(!$(".chat").is(":hidden") && !$(".shadow_two").is(":hidden")){
					$(".chat, .shadow_two").hide();
				}
				//修改价格返回
				if(!$(".change_price").is(":hidden") && !$(".shadow_two").is(":hidden")){
					$(".change_price, .shadow_two").hide();
				}
				//线上支付返回
				if(!$(".payment").is(":hidden") && !$(".shadow_two").is(":hidden")){
					clearInterval(check_pay_time);
					$(".payment, .shadow_two").hide();
				}
				//会员卡返回
				if(!$(".card").is(":hidden") && !$(".shadow").is(":hidden")){
					number_or_name_focus();
					$(".card, .shadow").hide();
				}
				
				//余额支付返回
				if(!$(".balance").is(":hidden") && !$(".shadow").is(":hidden")){
				
					$(".balance, .shadow_two").hide();
				}
				
				//计算器返回
				if (!$(".only_counter").is(":hidden")) {
					if(!$(".shadow_two").is(":hidden")){
						$(".only_counter, .shadow_two").hide();
					} else if(!$(".shadow").is(":hidden")){
						number_or_name_focus();
						$(".only_counter, .shadow").hide();
					}
				}
				
				break;
		}
	});
	var coupon_type = 1;
	//点击弹出优惠券
	$(".cho_yhj").click(function(){
		coupon_type = 1;
		left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
		var html = '';
		if (left_cart_data.card_data != '') {
			var coupon_list = left_cart_data.card_data.card_new;
			if(left_cart_data.card_data.discount==0){
				left_cart_data.card_data.discount =10;
			}

			var temp_price = parseFloat($('#pay_price').data('price')).toFixed(2);
			for (var i in coupon_list) {
				if (parseFloat(coupon_list[i].full_money) > temp_price) {
					continue;
				}
				html += '<div class="swiper-slide" data-id="' + coupon_list[i].coupon_id + '" data-full="' + coupon_list[i].full_money + '" data-reduce="' + coupon_list[i].reduce_money + '">';
				html += '<div class="text">';
				html += '<div class="money_top clr">';
				html += '<i class="fl">'+'￥'+'</i><span class="fl">' + coupon_list[i].reduce_money + '</span>';
				html += '</div>';
				html += '<div class="fullcut">满' + coupon_list[i].full_money + '元可用</div>';
				html += '<div class="coupon_use">点击使用 ></div>';
				html += '<div class="effective">有效期至：' + coupon_list[i].end_time + '</div>';
				html += '</div>';
				html += '</div>';
			}
		}
		if (html == '') {
			layer.msg('暂无可用优惠券');
			return false;
		} else {
			$('.coupon').find('.swiper-wrapper').html(html);
			$('.coupon, .shadow_two').show();
			var swiper = new Swiper('.coupon_end .swiper-container', {
				pagination: '.coupon_end .swiper-pagination',
				nextButton: '.coupon_end .swiper-button-next',
				prevButton: '.coupon_end .swiper-button-prev',
				slidesPerView: 'auto'
			});
		}
	});
	//点击使用积分
	$(document).on('click', '#score_deducte_selecet', function(){
		console.log($(this).attr("checked"))
		if ($('#score_deducte_selecet').attr("checked")) {
			$('#score_deducte_selecet').attr("checked",true);
		}else{
			$('#score_deducte_selecet').attr("checked",false);
		}
		var isChange = $('#isChange').val();
		if (isChange==1) {
			var  score_deducte = parseFloat($('#score_deducte').data('price'));
			var user_card_money = 0;
			var go_pay_money = $('#pay_price').data('price');
			var card_money = parseFloat($('#user_card_money').data('price')),user_card_total=parseFloat($('#user_card_total').data('price'));
			if ($('#score_deducte_selecet').prop('checked') ){
				go_pay_money = go_pay_money - score_deducte;
			}
			if (user_card_total > go_pay_money) {
			console.log(go_pay_money)
				$('#user_card_money').html('￥' + go_pay_money).data('price', go_pay_money);
				go_pay_money = 0;
			} else {
				$('#user_card_money').html('￥' + user_card_total).data('price', user_card_total);
				go_pay_money = go_pay_money - card_money;
			}
			

			$('#go_pay_money').html('<span class="still_zf">'+'还需支付：')+'</span>￥' + go_pay_money.data('price', go_pay_money);
			if (go_pay_money <= 0) {
				$('.pay .confirm').show();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').hide();
			} else {
				$('.pay .confirm').hide();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').show();
			}
		}else{
			shop_mall.count_price_html();
		}
		
	});

	//点击弹出优惠券
	$(".cho_ptyhj").click(function(){
		coupon_type = 2;
		left_cart_data = $.parseJSON(window.localStorage.getItem(local_left_cart_data_index));
		var html = '';
		if (left_cart_data.system_card != '') {
			var coupon_list = left_cart_data.card_data.system_card;
			if(left_cart_data.card_data.discount==0){
				left_cart_data.card_data.discount =10;
			}
			var temp_price = parseFloat($('#pay_price').data('price')).toFixed(2);
			for (var i in coupon_list) {
				if (coupon_list[i].is_discount==1) {
					coupon_list[i].reduce_money = parseFloat(temp_price*((100-parseFloat(coupon_list[i].discount_value)*10)/100)).toFixed(2);
				}
				if (parseFloat(coupon_list[i].full_money) > temp_price) {
					continue;
				}
				html += '<div class="swiper-slide" data-id="' + coupon_list[i].coupon_id + '" data-full="' + coupon_list[i].full_money + '" data-reduce="' + coupon_list[i].reduce_money + '">';
				html += '<div class="text">';
				html += '<div class="money_top clr">';
				html += '<i class="fl">'+'￥'+'</i><span class="fl">' + coupon_list[i].reduce_money + '</span>';
				html += '</div>';
				html += '<div class="fullcut">满' + coupon_list[i].full_money + '元可用</div>';
				html += '<div class="coupon_use">点击使用 ></div>';
				html += '<div class="effective">有效期至：' + coupon_list[i].end_time + '</div>';
				html += '</div>';
				html += '</div>';
			}
		}
		if (html == '') {
			layer.msg('暂无可用优惠券');
			return false;
		} else {
			$('.coupon').find('.swiper-wrapper').html(html);
			$('.coupon, .shadow_two').show();
			var swiper = new Swiper('.coupon_end .swiper-container', {
				pagination: '.coupon_end .swiper-pagination',
				nextButton: '.coupon_end .swiper-button-next',
				prevButton: '.coupon_end .swiper-button-prev',
				slidesPerView: 'auto'
			});
		}
	});
	//选中使用优惠券
	$(document).on('click', '.coupon_end .swiper-slide', function(){
		var full_money = $(this).data('full'), reduce_money = $(this).data('reduce'), coupon = $(this).data('id'),discount = $('#card_discount').data('discount');
		$('#coupon_price'+coupon_type).data('price', reduce_money);
		$('#coupon_price'+coupon_type).data('coupon', coupon);
		var isChange = $('#isChange').val();
		// var payPrice = parseFloat((discount * 0.1 * order_price - reduce_money).toFixed(2));
		// $('#pay_price').data('price', payPrice).data('old_price', payPrice);//
		// console.log(isChange)
		// console.log(order_price)
		// if (isChange == 1) {
		// 	changePrice(parseFloat(discount * 0.1 * order_price), '');
		// }
		
		shop_mall.count_price_html();
		$('.coupon, .shadow_two').hide();
		if (coupon_type==1) {
			$(".cho_yhj").text("满" + full_money + "减" + reduce_money).css("color", "#ef2e05");
		}else if(coupon_type==2){
			$(".cho_ptyhj").text("满" + full_money + "减" + reduce_money).css("color", "#ef2e05");
		}
		
	});

	//线上支付
	$(".on-line").click(function(){
		check_pay_time = setInterval(function(){
			$.post(shop_arrival_check, {order_id:order_id}, function(result){
				if(result.status == 1){
					layer.msg('支付成功！');
					clearInterval(check_pay_time);
					shop_mall.pay_success_back();
					$(".payment, .shadow_two, .shadow, .settlement").hide(); 
				}
			});
		},3000);
		$(".payment, .shadow_two").show(); 
	});

	$(".shadow").click(function(){
		$(".fix").hide();
		$(this).hide();
		number_or_name_focus();
	});

	$(".shadow_two").click(function(){
		$(".coupon,.alip,.chat,.payment,.linepay,.xgcontainer,.phone_code").hide();
		$(this).hide();
	});


	//删除输入框
	$(".left_input .del").click(function(){
		$(this).siblings(".input")[0].value = $(this).siblings(".input")[0].value.slice(0,-1);
	});

	//返回
	$(".return").click(function(){
		if(!$(this).parents(".settlement").is(":hidden") && $(".shadow_two").is(":hidden")){
			number_or_name_focus();
			$(".shadow").hide();
		}
		if(!$(".payment").is(":hidden") && !$(".shadow_two").is(":hidden")){
			clearInterval(check_pay_time);
		}
		$(this).parents(".fix").hide();
		$(".shadow_two").hide();
	});


	$("body").css("background","-webkit-linear-gradient(top, #010102 0%,#573651 50%,#181b39 100%)");
	
	//判断高度 小屏一屏显示
	if($(window).height() < 725){
		$(".right .right_1uick li").css({"height":"20px","line-height":"20px","padding":"10px 0px"});
	}
	// // 弹窗居中
	// $(".fix").each(function(){
	// 	$(this).css({"margin-left":-($(this).width()/2),"margin-top":-($(this).height()/2)}); 
	// });
	
	//线下支付
	$(".pay .line").click(function(){
		$('#offline_total_money').html($('#go_pay_money').data('price'));
		$('#offline_wait_money').html($('#go_pay_money').data('price'));
		$(".offline_pay").show().find("input")[0].focus();
		$(".shadow_two").show();
	});
	
	$('#offline_finish_money').keyup(function(){
		var text = $(this).val(), last_v = $(this).val().substr(-1, 1);
		if (!(parseFloat(text) > 0)) {
			$('#offline_return_money').html(0);
		}
		if (last_v.search(/\d|\./) == -1) {
			$(this).val(text.substr(0, text.length - 1));
			return false;
		}
		if (text.length == 1 && text == '.') {
			$(this).val('');
			return false;
		}
		if (text.substr(0, text.length - 1).search(/\./) != -1 && last_v == '.') {
			$(this).val(text.substr(0, text.length - 1));
			return false;
		}
		var price = parseFloat($('#go_pay_money').data('price'));
		if (parseFloat(text) > 0) {
			$('#offline_return_money').html(parseFloat((parseFloat(text) - price).toFixed(2)));
		} else {
			$('#offline_return_money').html(0);
		}
	});
	
	
	
	//支付宝支付
	$(".pay .alipay").click(function(){
		$(".alip, .shadow_two").show();
		$('#alipay_txt').focus();
		return false;
	});

	//微信支付
	$(".pay .wx").click(function(){
		$(".wx, .shadow_two").show();
		$('#weixin_txt').focus();
		return false;
	});
	
	$(".pay .yzf_pay").click(function(){
		$(".chat, .shadow_two").show();
		$('#yzf_txt').focus();
		return false;
	});
	
	//微信支付
	$(".pay .balance_pay").click(function(){
		if (card_data) {
			if (open_balance_pay_unverify==1&&card_data.phone) {					
				//开启线下零售、到店付余额支付不使用短信验证码
				shop_mall.go_pay('balance','',-1,card_data.phone);
				
			}else{					
				$(".balance, .shadow_two").show();
				$('#balance_phone').focus();
				$('#balance_phone').val(card_data.phone);
				$('#balance_phone').prop('disabled',true);
			}
		}else{
			$(".balance, .shadow_two").show();
			$('#balance_phone').focus();
			$('#balance_phone').val('');
			$('#balance_phone').prop('disabled',false);
		}
		return false;
	});
	var user_now_money = 0;
	$("#verify_phone").click(function(){
		 price = parseFloat($('#go_pay_money').data('price'));
		$.post(verify_phone_url, {phone:$('#balance_phone').val()}, function(result){
				if(result.status != 1){
					layer.msg(result.info);
				}else{
					$('.check_phone').hide();
					$('.confirm_pay').show();
					user_now_money=parseFloat(result.info.money);
					if(user_now_money<price){
						$('.balance h2').html('当前平台余额：￥')+parseFloat(result.info.money)+',待支付：<font color="red">￥'+price+'</font>'
						$('.confirm_pay .firm').addClass('money_lease')
						$('.confirm_pay .money_lease').removeClass('firm')
						$('.confirm_pay .sms').css('background','#8b9092')
						$('.confirm_pay .sms').css('color','#fff')
					}else{
						$('.balance h2').html('当前平台余额：￥')+parseFloat(result.info.money)+',待支付：￥'+price
						$('.confirm_pay .firm').html('确认支付 (￥'+price.toFixed(2)+')');
						smscode();
					}
					$('.return').hide();
					$('.back_check').show();
				}
			});
	})
	
	$('.sms').click(function(){
		
		price = parseFloat($('#go_pay_money').data('price'));
		if(user_now_money>=price){
			smscode();
		}
	})
	$('.back_check').click(function(){
		clearInterval(smsTimer);
		$('.sms').html('发送验证码')
		$('.balance h2').html('只有在平台账户绑定手机号的用户才能使用平台余额支付')
		$('.confirm_pay .money_lease').addClass('firm')
		$('.confirm_pay .firm').removeClass('money_lease')
		$('.confirm_pay .sms').css('background','#fff')
		$('.confirm_pay .sms').css('color','#000')
		$('.check_phone').show();
		$('.confirm_pay').hide();
		$('.back_check').hide();
		$('#balance_phone').val('');

		$('.return').show();
	})

	//确认支付成
	$(".pay .confirm").click(function(){
		shop_mall.go_pay('', '', '');
		return false;
	});
	//点击微信与支付宝支付
	$('.chat .chat_end .firm,.meachat .chat_end .firm').click(function(){
		shop_mall.go_pay($(this).data('paymethod'), $(this).closest(".chat_n").find("input").val(), -1);
	});
	//回车微信与支付宝支付
	$('.chat .chat_end .port,.meachat .chat_end .port').keyup(function(e){
		if(e.keyCode == 13){
			shop_mall.go_pay($(this).closest(".chat_n").find(".firm").data('paymethod'), $(this).val(), -1);
		}
	});
	
	//线下支付
	$('.offline_pay .offline_pay li').click(function(){
		shop_mall.go_pay('', '', $(this).data('id'));
	});
	
	//余额支付
	$('.balance .chat_end .firm').click(function(){
		// alert($('#balance_phone').val())
		price = parseFloat($('#go_pay_money').data('price'));
		if(user_now_money>=price){
			shop_mall.go_pay($(this).data('paymethod'), $('#smscode').val(),-1,$('#balance_phone').val());
		}
	});
	
	$("#order_price.on").hover(function(){
		if($(".disk").html().length>0){
			$(".disk").slideDown();
			$(this).addClass("ou");
		}
		
	},function(){
		if($(".disk").html().length>0){
			$(".disk").slideUp();
			$(this).removeClass("ou");
		}
	})

	//扫码识别用户
	$(".phone_btn").click(function(){
		$(".phone_code, .shadow_two").show();
		$('#phoneQrcode').focus();
		return false;
	});

	$('#phoneQrcodeBtn').click(function(){
		postPhoneQrcode();
	});
});

var postNow = false;
function postPhoneQrcode(){
$('#phoneQrcode').val($.trim($('#phoneQrcode').val()));
	if(postNow == true){
		layer.alert('正在请求中，请稍等');
		return false;
	}
	var auth_code = $('#phoneQrcode').val();
	var reg = new RegExp("^[0-9]*$");
	if(!reg.test(auth_code)){
		layer.alert('只允许输入数字');
		return false;
	}
	if(auth_code.length < 2){
		$('#phoneQrcode').focus();
		return false;
	}
	var nowPayMethod = '';			
	var codePrefix =  auth_code.substr(0,2);		

	$('#userQrcodeBtn').html('请求中...');
	var index_loading = layer.load(0, {shade: [0.1,'#000000']});
	$.post(ajax_card_url, {'key':auth_code}, function(response){
		$('#card_name').html('');
		$('#card_phone').html('');
		$('#card_sex').html('');
		$('#card_number').html('暂未领卡');
		$('#card_number2').html('暂未领卡');
		$('#card_discount_p').html('暂未领卡');
		$('#card_money').html('暂未领卡');
		$('#card_score').html('暂未领卡');
		if (response.err_code) {
			layer.alert(response.data)
			// $('.card .information').find('.clr').hide();
			// $('.card .information').find('.img').show();
			// $(".query_qr").css("background","#ccc");
		} else {
			$('.card .information').find('.clr').show();
			$('.card .information').find('.img').hide();
			$(".query_qr").css("background","#0099dc");
			temp_data = response.data;
			
			$('#card_name').html(temp_data.name);
			$('#card_phone').html(temp_data.phone);
			$('#card_sex').html(temp_data.sex);
			if (temp_data.card_id) {
				$('#card_number').html(temp_data.card_id);
				$('#card_number2').html(temp_data.physical_id);
				$('#card_discount_p').html(parseFloat(temp_data.discount) + '折');
				$('#card_money').html(temp_data.card_money);
				$('#card_score').html('可用积分：' + temp_data.card_score);
			}else{
				$('#card_number').html('暂未领卡');
				$('#card_number2').html('暂未领卡');
				$('#card_discount_p').html('暂未领卡');
				$('#card_money').html('暂未领卡');
				$('#card_score').html('暂未领卡');
			}
			$(".phone_code, .shadow_two").hide();
		}

		layer.close(index_loading);
	}, 'json');

	// $.post("{pigcms{:U('Store/ajax_user_by_phone')}", {'auth_code':auth_code}, function(response){
	// 	$('#userQrcodeBtn').html('确认使用');
	// 	if(response.err_code){
	// 		layer.alert(response.data);
	// 	}else{
	// 		$('#phone').val(response.data.phone)
	// 		user_card(response.data)
	// 		$(".chat, .shadow_two").hide();
	// 	}
	// 	postNow = false;
	// },'json');
}

function smscode(val){
	$.post(sms_code_url, {phone:$('#balance_phone').val()}, function(result){
		layer.msg('短信发送成功');
		$('.sms').data('second',59).html('59 秒').prop('disabled',true);
		smsTimer = setInterval(function(){
			var second = $('.sms').data('second');
			if(second == 1){
				$('.sms').html('发送验证码').prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('.sms').data('second',second).html(second + ' 秒');
			}
		},1000);
	})
}


function changePrice(price, reason)
{
	console.log("郭杰")
	// 计算积分			
	var uid = 0;
	if (left_cart_data.card_data) {
		uid = left_cart_data.card_data.uid;
	}
	$.ajax({ 
	　　url:ajax_get_score_discount_url,  //请求的URL
	　　timeout : 10000, //超时时间设置，单位毫秒
	　　type : 'post',  //请求方式，get或post
	　　data :{type:'shop',order_id:order_id,total_money:price,uid:uid},  //请求所传参数，json格式
	　　dataType:'json',//返回的数据格式
	　　success:function(res){ //请求成功的回调函数
	　　　 console.log("点击一")　
		  if (!res.error) {
				$('#score_deducte').data('price', parseFloat(res.score_deducte).toFixed(2));
				$('#score_deducte').html(parseFloat(res.score_deducte).toFixed(2));
				$('#score_can_use_count').data('num', res.score_can_use_count);
				$('#score_can_use_count').html(res.score_can_use_count);
				if(res.score_can_use_count){
					// $('#score_deducte_selecet').prop('checked',true);
					$('.score_discount').show();
				}else{
					$('.score_discount').hide();
					$('#score_deducte_selecet').prop('checked',false);
				}
			}else{
				$('.score_discount').hide();
				$('#score_deducte_selecet').prop('checked',false);
			}
	　　}
	});

	var card_money = parseFloat($('#user_card_money').data('price')),user_card_total=parseFloat($('#user_card_total').data('price')), old_price = parseFloat($('#pay_price').data('old_price'));
	$.post(changePriceUrl,{order_id:order_id, 'change_reason':reason, 'change_price':price},function(result){
		if (result.status == 1) {
			var  score_deducte = parseFloat($('#score_deducte').data('price'));
			var user_card_money = 0;
			var go_pay_money = price;
			if ($('#score_deducte_selecet').prop('checked') ){
				go_pay_money = go_pay_money - score_deducte;
			}

			if (user_card_total > go_pay_money) {
				$('#user_card_money').html('￥' + go_pay_money).data('price', go_pay_money);
				go_pay_money = 0;
			} else {
				$('#user_card_money').html('￥' + user_card_total).data('price', user_card_total);
				go_pay_money = go_pay_money - user_card_total;
			}

			$('#pay_price').html('￥' + price).data('price', price);
			var html = '';
			if (old_price != price) {
				html += '<em>修改前：￥' + old_price + '</em>';
				if (reason.length > 0) {
					html += '<em>备注：' + reason + '</em>';
				}
			}
			$('.mod_hui').html(html).show();
			$('#change_price_reason').val(reason);
			// shop_mall.count_price_html();
			$('.change_price, .shadow_two').hide();

			$('#go_pay_money').html('<span class="still_zf">'+'还需支付：')+'</span>￥' + go_pay_money.data('price', go_pay_money);
			if (go_pay_money <= 0) {
				$('.pay .confirm').show();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').hide();
			} else {
				$('.pay .confirm').hide();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay,.pay .yzf_pay').show();
			}

		} else {
			layer.msg(result.info);
			$('.change_price, .shadow_two').hide();
		}
	});
}
