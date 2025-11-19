<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1"/>
		<title>{pigcms{$config.site_name} - {pigcms{:L_("店员管理中心")}</title>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/group_edit.css"/>
		<script type="text/javascript" src="{pigcms{$static_path}js/jquery.min.js"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/storestaffBase.js"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}css/market.css?{pigcms{$_SERVER['REQUEST_TIME']}"/>
		<script src="{pigcms{$static_public}js/layer/layer.js"></script>
		<script src="{pigcms{$static_path}js/swiper-3.3.1.jquery.min.js"></script>
		<script type="text/javascript" src="./appapi.php?c=Lang&a=js&lang={pigcms{$config.system_lang}&_g={pigcms{$Think.GROUP_NAME}&_m={pigcms{$Think.MODULE_NAME}&_a={pigcms{$Think.ACTION_NAME}"></script>
		<style>
		.check_phone:after{content:none !important;}
		.inputs{box-shadow: aliceblue !important; border:none !important;     background-color: transparent !important;}
	
		</style>
	</head>
	<body>
	<div class="fix settlement" style="display: block;    margin-top: -302.5px;">
		<div class="set_top">
			<div class="set">{pigcms{:L_("结算订单")}</div>
		</div>
		<div class="set_end clr">
			<div class="set_fl fl">
				<div class="number bf">
					<div class="tit clr">
						<div class="fl">{pigcms{:L_("订单编号")}</div>
						<div class="fr">{pigcms{:L_("订单总价")}</div>
					</div>
					<div class="pri clr">
						<div class="fl" id="order_id">{pigcms{$now_order['orderid']}</div>
						<div class="fr" id="order_price" data-price="{pigcms{$now_order['price']|floatval}">
							<span>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['price']|floatval}</span>
							<!-- <div class="disk">{pigcms{:L_("平台满减优惠,店铺满减优惠,店铺折扣优惠的价格")}</div> -->
						</div>
					</div>
				</div>
				<div class="choice bf d4 h40 clr">
					<div class="fl">{pigcms{:L_("商家优惠券")}</div>
					<div class="fr cho_yhj" data-price="0" id="coupon_price1" data-coupon="0">{pigcms{:L_("选择优惠券")}</div>
					<input type="hidden" name="coupon_discount_price1" name="coupon_discount_price1">
				</div>
				<div class="choice bf d4 h40 clr">
					<div class="fl">{pigcms{:L_("平台优惠券")}</div>
					<div class="fr cho_ptyhj" data-price="0" id="coupon_price2" data-coupon="0">{pigcms{:L_("选择优惠券")}</div>
					<input type="hidden" name="coupon_discount_price2" name="coupon_discount_price2">
				</div>
				
				<div class="score_discount bf d4 h40 clr" <?php if (!$score_can_use_count): ?>style="display: none;"<?php endif ?>>
					<div class="fl">{pigcms{:L_("本单可使用积分")}<span id="score_can_use_count" data-num="{pigcms{$score_can_use_count}" style="color: red;">{pigcms{$score_can_use_count}</span>{pigcms{:L_("个，可抵扣金额")}{pigcms{$config['Currency_symbol']}<span><span id="score_deducte" data-price="{pigcms{$score_deducte}" style="color: red;">{pigcms{$score_deducte}</span></div>
					<label style="width: 50px;float: right;text-align: center;" for="score_deducte_selecet"><input type="checkbox" name="score_deducte_selecet" id="score_deducte_selecet"></label>
				</div>
				
				<div class="discount bf d4 h40 clr">
					<div class="fl">{pigcms{:L_("应付金额")}</div>
					<div class="fr">
						<span id="pay_price" data-old_price="{pigcms{$now_order['price']|floatval}" data-price="{pigcms{$now_order['price']|floatval}">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['price']|floatval}</span>
						<input type="hidden" id="change_price_reason" />
						<input type="hidden" id="isChange" value="0"/>
						
						<if condition="$is_change eq 1">
						<span class="mod_hui" style="display:none;">
							<em>{pigcms{:L_("修改前：")}{pigcms{$config['Currency_symbol']}100</em>
							<em>{pigcms{:L_("备注：哈哈")}</em>
						</span>
						<span class="mod_hand">{pigcms{:L_("修改金额")}</span>
						</if>
					</div>
				</div>
				<div class="credit bf d4">
					<ul class="clr">
						<li class="wb3">
							<div class="li_n">
								<h2 class="name">{pigcms{:L_("会员卡号")}</h2>
								<div class="text" id="user_card_number">{pigcms{$now_card['id']}</div>
							</div>
						</li>
						<li class="wb2">
							<div class="li_n">
								<h2 class="name">{pigcms{:L_("当前余额")}</h2>
								<div class="text ef2" id="user_card_total" data-price="{pigcms{$now_card['card_money']+$now_card['card_money_give']|floatval}">{pigcms{$config['Currency_symbol']}{pigcms{$now_card['card_money']+$now_card['card_money_give']|floatval}</div>
							</div>
						</li> 
						<li class="w3">
							<div class="li_n">
								<h2 class="name">{pigcms{:L_("本次可用")}</h2>
								<div class="text ef2"><span id="user_card_money" data-price="0">{pigcms{$config['Currency_symbol']}0</span> <span class="modify">{pigcms{:L_("修改金额")}</span> </div>
							</div>
						</li> 
						<!--li class="w2">
							<div class="li_n">
								<div class="text"><span class="use">{pigcms{:L_("使用会员卡")}</span></div>
							</div>
						</li-->
					</ul>
				</div>
				<div class="still bf d4 clr">
					<div class="fr" id="go_pay_money" data-price="{pigcms{$now_order['price']|floatval}"><span class="still_zf">{pigcms{:L_("还需支付：")}</span>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['price']|floatval}</div>
				</div>
				<div class="pay">
					<ul class="clr">

                    	<if condition="$offline_pay_list">
                    	<li class="line">
							<div class="line_n">
							<span>{pigcms{:L_("线下支付")}</span>
							</div>
						</li>
                    	</if>
						
						<li class="wx">
							<div class="line_n">
							<span>{pigcms{:L_("扫码支付")}</span>
							</div>
						</li>
						<li class="balance_pay">
							<div class="line_n">
							<span>{pigcms{:L_("平台余额支付")}</span>
							</div>
						</li>
						<if condition="C('customization.internal_membership_card') eq 1">
							<li id="internalCard">
								<div class="line_n">
								<span>{pigcms{:L_("一卡通支付")}</span>
								</div>
							</li>
						</if>
						<li class="confirm" style="display: none;">
							<span>{pigcms{:L_("确认支付")}</span>
						</li>
					</ul>
				</div>
			</div>
			<div class="set_fr fr">
				<ul>
					<!-- <li class="cancel">
						<div class="line_n">{pigcms{:L_("整单取消")}</div>
					</li> -->
					<li class="on-line">
						<div class="line_n">{pigcms{:L_("用户扫码支付")}</div>
					</li>
				</ul>
			</div>
		</div>
	</div>
	<div class="fix linepay line_600 change_price">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("修改金额")}</div>
			<div class="fr return">{pigcms{:L_("返回")}</div>
		</div>
		<div class="linepay_end">
			<div class="over">
				<div class="pay_top clr">
					<div class="pay_fl">
						<ul>
							<li>
								<div class="pay_n  pay_input clr">
									<div class="fl li_fl">{pigcms{:L_("修改值")}</div>
									<input type="number" placeholder="{pigcms{:L_("这里输入您想要的修改的金额")}" name="change_price" class="text" >
								</div>
							</li>
							<li>
								<div class="pay_n pay_input clr" style="line-height: 50px;">
									<div class="fl li_fl">{pigcms{:L_("备注")}</div>
									<input type="text" placeholder="{pigcms{:L_("输入备注，限定8字以内")}" name="change_price_reason" class="text" >
								</div>
							</li>
							<li>
								<div class="pay_35 clr">
								<div class="fl li_fl">{pigcms{:L_("当前值")}</div>
								<div class="fr li_fr" data-price="0" id="show_last_price">0</div>
								</div>
							</li>
						</ul>
					</div>
					
				</div>
				<div class="pay_end pay_ends">
					<ul class="clr">
						<li class="hf">
							<span>{pigcms{:L_("恢复初始价格")}</span>
						</li>
						<li class="qr">
							<span>{pigcms{:L_("确认修改")}</span>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="fix coupon" style="margin-top: -193px;">
		<div class="coupon_top">
			<span>{pigcms{:L_("选择优惠券")}</span>
		</div>
		<div class="coupon_end">
			<div class="swiper-button-prev"></div> 
			<div class="swiper-container">
				<div class="swiper-wrapper">
				</div> 
			</div>
			<div class="swiper-button-next"></div> 
		</div>
	</div>
	<div class="fix container slcontainer counter only_counter" data-type="1">
		<div class="con_pos">
			<h2>{pigcms{:L_("请输入数量")}</h2>
			<input type="text" id="text" name="text" class="text" />
			<div class="buttons">
				<input type="button" value="7" class="button show">
				<input type="button" value="8" class="button show">
				<input type="button" value="9" class="button show">
				<input type="button" value="{pigcms{:L_("退格")}" class="button funback">
				<input type="button" value="4" class="button show">
				<input type="button" value="5" class="button show">
				<input type="button" value="6" class="button show">
				<input type="button" value="{pigcms{:L_("取消")}" class="button cancel funclear">
				<input type="button" value="1" class="button show">
				<input type="button" value="2" class="button show">
				<input type="button" value="3" class="button show">
				<input type="button" value=" " class="button"  style="border: none; background: none;" >
				<input type="button" value="00" class="button show">
				<input type="button" value="0" class="button show">
				<input type="button" value="." class="button point" id="intdd1"> 
				<input type="button" value="{pigcms{:L_("确认")}" class="button confirm getResult" >
			</div>
			<input type="button"  class="jt funclear">
		</div> 
	</div>
	<div class="fix linepay line_600 offline_pay">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("线下支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")}</div>
		</div>
		<div class="linepay_end">
			<div class="over">
				<div class="pay_top clr">
					<div class="pay_fl">
						<ul>
							<li>
								<div class="pay_n clr">
								<div class="fl li_fl">{pigcms{:L_("应收")}</div>
								<div class="fr li_fr" id="offline_total_money" data-price="0">0</div>
								</div>
							</li>
							<!-- <li>
								<div class="pay_n pay_input clr" >
								<div class="fl li_fl">{pigcms{:L_("已收")}</div>
								<input type="text" id="offline_finish_money" placeholder="{pigcms{:L_("输入收取的金额")}" name="text" class="text" >
								</div>
							</li>
							<li>
								<div class="pay_n1 clr">
									<div class="fl li_fl">{pigcms{:L_("待收")}</div>
									<div class="fr li_fr" id="offline_wait_money" data-price="0">0</div>
								</div>
								<div class="pay_n2 clr">
								<div class="fl li_fl">{pigcms{:L_("找零")}</div>
								<div class="fr li_fr" id="offline_return_money" data-price="0">0</div>
								</div>
							</li> -->
						</ul>
					</div>
				</div>
				<div class="pay_end offline_pay">
					<ul class="clr">
                    <!-- <if condition="empty($offline_pay_list)">
						<li class="zfb orderofflinepay" data-id="0">{pigcms{:L_("现金支付")}</li>
                    </if> -->
						<volist name="offline_pay_list" id="row">
						<li class="wx orderofflinepay" data-id="{pigcms{$row['id']}">{pigcms{$row['name']}</li>
						</volist>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="fix payment">
		<div class="payment_top clr">
			<div class="fl pay_xs">{pigcms{:L_("线上支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")}</div>
		</div>
		<div class="payment_end">
			<div class="pay_wc">
				<div class="pay_nc">
					<h2 class="tit">{pigcms{:L_("用户")}<if condition="$config['cash_pay_qrcode']">{pigcms{:L_("微信")}</if>{pigcms{:L_("扫码支付")}</h2>
					<if condition="$config['cash_pay_qrcode']">
						<img class="wxcode userCode" src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id={pigcms{$now_order['order_id']+3600000000}" style="width:250px;height:250px;"/>
					<else/>
						<img class="wxcode userCode" src="{pigcms{$config.site_url}/index.php?c=Recognition&a=get_own_qrcode&qrCon={pigcms{:urlencode($config['site_url'].'/wap.php?c=My&a=store_order_before&order_id='.$now_order['order_id'])}" style="width:250px;height:250px;"/>
					</if>
					<!-- <img src="" style="width:198px;height:198px;" id="pay_qrcode_url"/> -->
				</div>
			</div>
		</div>
	</div>

	<div class="fix chat">
		<div class="chat_top clr">
			<div class="fl wx_chat">{pigcms{:L_("扫码支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")}</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("扫描用户微信")}/{pigcms{:L_("支付宝付款码支付")}</h2>
				<div class="inputs clr input" style='clear: both;'>
					<input type="text" class="port fl" value="" id="userQrcode">
					<div class="firm fr" data-paymethod="weixin" id="userQrcodeBtn">{pigcms{:L_("确认支付")}</div>
				</div>
				<p style='clear: both;'>{pigcms{:L_("建议使用扫码枪直接扫描得到值，或者先刷新用户微信")}/{pigcms{:L_("支付宝中的码，再写入。如果扫码提示微信")}/{pigcms{:L_("支付宝错误，可以关闭本页面重新创建订单。")}</p>
			</div>
		</div>
	</div>
	
	
	<div class="fix balance">
		<div class="chat_top clr">
			<div class="fl wx_chat" style="padding: 1px 40px;">{pigcms{:L_("平台余额支付")}</div>
			<div class="fr return">{pigcms{:L_("返回")}</div>
			<div class="fr back_check" style="display:none;">{pigcms{:L_("返回上一步")}</div>
		</div>
		<div class="chat_end">
			<div class="chat_n">
				<h2>{pigcms{:L_("只有在平台账户绑定手机号的用户才能使用平台余额支付")}</h2>.
				<div class="input clr check_phone inputs" style='clear: both;' >
					<input type="text" class="port fl" value="" placeholder="{pigcms{:L_("请输入手机号")}" id="balance_phone" >
					<div class="check fr" id="verify_phone">{pigcms{:L_("发送验证码")}</div>
				</div>
				<div class="input clr confirm_pay inputs" style="display:none;clear: both;">
					<input type="text" class="port fl " value="" placeholder="{pigcms{:L_("请输入验证码")}" id="smscode" >
					<div class="sms fr" data-second="" >{pigcms{:L_("发送验证码")}</div>
					<br><br><br><br><br>
					<div class="firm" data-paymethod="balance" id="go_balance_pay">{pigcms{:L_("确认支付")}</div>
				</div>
				<p></p>
			</div>
		</div>
	</div>

	<div class="shadow_two"></div>
	<script type="text/javascript">
		var phone = '{pigcms{$phone}';
		console.log(phone)
		var canUseAlipay = <if condition="$config['arrival_alipay_open'] OR $is_sub_alipay">true<else/>false</if>;
	</script>
	<script type="text/javascript">
		var cancelUrl = "{pigcms{:U('cancel',array('order_id'=>$now_order['order_id']))}";
		var store_arrival_check = "{pigcms{:U('store_arrival_check',array('order_id'=>$now_order['order_id']))}";
		var order_price = 0;
		var internal_card_info = ''
		var internalCardNumber = '';
		order_price = {pigcms{$now_order['price']|floatval};
		//"修改金额")}
		$(".modify").click(function(){
			$(".counter").removeClass('slcontainer').addClass('xgcontainer');
			$(".counter").show().find("input")[0].focus();
			$(".shadow_two").show();
		});
		//"计算器输入值的处理")}
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
		//"计算器点击确定的处理结果")}
		$(".getResult").click(function(){
			var type = $(this).parents('.counter').data('type');
			var num = parseFloat($.trim($(this).parents('.counter').find("input.text").val()));
			if (isNaN(num)) {
				$(".counter, .shadow_two").hide();
				return false;
			}
			
			
			var card_total_money = parseFloat($('#user_card_total').data('price')), pay_price = $('#pay_price').data('price');
			if (num > card_total_money) {
				layer.msg('{pigcms{:L_("超过了可用余额")}');
				return false;
			}
			if (num > pay_price) {
				layer.msg('{pigcms{:L_("超过了待支付的金额")}');
				return false;
			}
			$('#user_card_money').html('{pigcms{$config['Currency_symbol']}' + num).data('price', num);

			// "修改会员卡使用金额")}
			var go_pay_money = parseFloat(pay_price-num);
			$('#go_pay_money').html('<span class="still_zf">{pigcms{:L_("还需支付：")}</span>{pigcms{$config['Currency_symbol']}' + go_pay_money).data('price', go_pay_money);
			if (go_pay_money <= 0) {
				$('.pay .confirm').show();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay').hide();
			} else {
				$('.pay .confirm').hide();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay').show();
			}

			$(".counter, .shadow_two").hide();
			
			$(this).parents('.counter').find("input.text").val('');
		});
		
		// "点击计算器中的数字")}
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
		//"点击计算器中的点")}
		$(".point").click(function(){
			if ($(this).parents('.counter').data('type') == 0) return false;
			var text = $(this).parents('.counter').find('.text')[0];
			var p = $(this)[0];
			if (text.value.search(/\./) != -1) return false;
			if (text == '') text = 0;
			text.value += p.value;
			$(this).parents('.counter').find('.text').focus();
		});
		
		//"点击计算器的清空")}
		$(".funclear").click(function(){
			$(this).parents('.counter').find('.text').val('').focus();
		});
		
		//"点击计算器的退格键")}
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
		// "计算器取消")}
		$(".counter .cancel").click(function(){
			if (!$(".shadow_two").is(":hidden")) {
				$(".counter, .shadow_two").hide();
			} else {
				number_or_name_focus();
				$(".counter, .shadow").hide();
			}
		});
		var type = 1;
		//"点击弹出优惠券")}
		$(".cho_yhj").click(function(){
			type = 1;
			left_cart_data = '{pigcms{$mer_coupon}';
			left_cart_data = $.parseJSON(left_cart_data);
			var html = '';
			
			var coupon_list = left_cart_data;
			
			for (var i in coupon_list) {
				// if (parseFloat(coupon_list[i].full_money) > temp_price) {
				// 	continue;
				// }
				html += '<div class="swiper-slide" data-id="' + coupon_list[i].coupon_id + '" data-full="' + coupon_list[i].full_money + '" data-reduce="' + coupon_list[i].reduce_money + '">';
				html += '<div class="text">';
				html += '<div class="money_top clr">';
				html += '<i class="fl">{pigcms{$config['Currency_symbol']}</i><span class="fl">' + coupon_list[i].reduce_money + '</span>';
				html += '</div>';
				html += '<div class="fullcut">满' + coupon_list[i].full_money + '{pigcms{$config['Currency_txt']}{pigcms{:L_("可用")}</div>';
				html += '<div class="coupon_use">{pigcms{:L_("点击使用")} ></div>';
				html += '<div class="effective">{pigcms{:L_("有效期至：")}' + coupon_list[i].end_time + '</div>';
				html += '</div>';
				html += '</div>';
			}
			
			if (html == '') {
				layer.msg('{pigcms{:L_("暂无可用优惠券")}');
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


		//"点击弹出平台优惠券")}
		$(".cho_ptyhj").click(function(){
			type = 2;
			left_cart_data = '{pigcms{$system_coupon}';
			// console.log(left_cart_data)
			left_cart_data = $.parseJSON(left_cart_data);
			var html = '';
			
			var coupon_list = left_cart_data;
			
			for (var i in coupon_list) {
				// if (parseFloat(coupon_list[i].full_money) > temp_price) {
				// 	continue;
				// }
				html += '<div class="swiper-slide" data-id="' + coupon_list[i].id + '" data-full="' + coupon_list[i].order_money + '" data-reduce="' + coupon_list[i].discount + '">';
				html += '<div class="text">';
				html += '<div class="money_top clr">';
				html += '<i class="fl">{pigcms{$config['Currency_symbol']}</i><span class="fl">' + coupon_list[i].discount + '</span>';
				html += '</div>';
				html += '<div class="fullcut">满' + coupon_list[i].order_money + '{pigcms{$config['Currency_txt']}{pigcms{:L_("可用")}</div>';
				html += '<div class="coupon_use">{pigcms{:L_("点击使用")} ></div>';
				html += '<div class="effective">{pigcms{:L_("有效期至：")}' + coupon_list[i].end_time + '</div>';
				html += '</div>';
				html += '</div>';
			}
			
			if (html == '') {
				layer.msg('{pigcms{:L_("暂无可用优惠券")}');
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

		//"选中使用优惠券")}
		$(document).on('click', '.coupon_end .swiper-slide', function(){
			var full_money = $(this).data('full'), reduce_money = $(this).data('reduce'), coupon = $(this).data('id'),discount = $('#card_discount').data('discount');
			$('#coupon_price'+type).data('price', reduce_money);
			$('#coupon_price'+type).data('coupon', coupon);
			//"计算积分")}
			var merc_id=$('#coupon_price1').data('coupon');
			var sysc_id=$('#coupon_price2').data('coupon');
			var get_score_discount_url = "{pigcms{:U('Store/ajax_get_score_discount',array('order_id'=>$now_order['order_id']))}";
			$.post(get_score_discount_url,{merc_id:merc_id,sysc_id:sysc_id},function(res){
				if (!res.error) {
					$('#score_deducte').data('price', parseFloat(res.score_deducte).toFixed(2));
					$('#score_deducte').html(parseFloat(res.score_deducte).toFixed(2));
					$('#score_can_use_count').data('num', res.score_can_use_count);
					$('#score_can_use_count').html(res.score_can_use_count);
					if(res.score_can_use_count){
						$('.score_discount').show();
					}else{
						$('.score_discount').hide();
					}
				}
			},'json')
			
			count_price_html();
			$('.coupon, .shadow_two').hide();
			if (type == 1) {
				$(".cho_yhj").text("满" + full_money + "减" + reduce_money).css("color", "#ef2e05");
			}else{
				$(".cho_ptyhj").text("满" + full_money + "减" + reduce_money).css("color", "#ef2e05");
			}
		});

		//"点击使用积分")}
		$(document).on('click', '#score_deducte_selecet', function(){
			console.log($(this).attr("checked"))
			if ($('#score_deducte_selecet').attr("checked")) {
				$('#score_deducte_selecet').attr("checked",true);
			}else{
				$('#score_deducte_selecet').attr("checked",false);
			}
			count_price_html();
		});

		function count_price_html(){
			var reduce_money1 = $('#coupon_price1').data('price'), reduce_money2 = $('#coupon_price2').data('price'), card_money = $('#user_card_money').data('price'),score_deducte=parseFloat($('#score_deducte').data('price'));
			var order_price = parseFloat($('#order_price').data('price'));
			if (reduce_money1) {
				order_price = parseFloat((order_price - reduce_money1).toFixed(2));
			}
			console.log(order_price)
			if (reduce_money2) {
				order_price = parseFloat((order_price - reduce_money2).toFixed(2));
			}
			if (order_price < 0) {
				order_price = 0;
			}

			if ($('#score_deducte_selecet').attr("checked") && score_deducte>0) {
				order_price = parseFloat((order_price - score_deducte).toFixed(2));
			}

			$('#pay_price').data('price', order_price).data('old_price', order_price);
			$('#pay_price').html('{pigcms{$config['Currency_symbol']}' + order_price);

			var payPrice = parseFloat($('#pay_price').data('price'));
			var card_total_money = parseFloat($('#user_card_total').data('price'))
			
			if (card_money<=payPrice) {
				if (card_total_money>=payPrice) {
					$('#user_card_money').html('{pigcms{$config['Currency_symbol']}' + payPrice).data('price', payPrice);
					order_price = 0;
				}else{
					order_price = parseFloat(payPrice - card_total_money).toFixed(2);
					$('#user_card_money').html('{pigcms{$config['Currency_symbol']}' + card_total_money).data('price', card_total_money);
				}
				
			}else{
				order_price = parseFloat(payPrice - card_total_money).toFixed(2);
				$('#user_card_money').html('{pigcms{$config['Currency_symbol']}' + card_total_money).data('price', card_total_money);
			}
			
			

			if (order_price < 0) {
				$('#user_card_money').html('{pigcms{$config['Currency_symbol']}' + payPrice).data('price', payPrice);
				order_price = 0;
			}

			$('#go_pay_money').html('<span class="still_zf">{pigcms{:L_("还需支付：")}</span>{pigcms{$config['Currency_symbol']}' + order_price).data('price', order_price);
			if (order_price <= 0) {
				$('.pay .confirm').show();
				$('.pay .line,.pay .wx,.pay .balance,.pay .alipay,.pay .balance_pay').hide();
			} else {
				$('.pay .confirm').hide();
				$('.pay .line,.pay .balance,.pay .alipay,.pay .balance_pay').show();
			}

		}	

		$(".shadow_two").click(function(){
			$(".coupon,.alip,.chat,.payment,.linepay,.xgcontainer,.balance").hide();
			$(this).hide();
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
		//"线下支付")}
		$(".pay .line").click(function(){
			$('#offline_total_money').html($('#go_pay_money').data('price'));
			$('#offline_wait_money').html($('#go_pay_money').data('price'));
			$(".offline_pay").show();
			$(".shadow_two").show();
		});

		$('.orderofflinepay,.pay .confirm').click(function(){
			// var tip_index = parent.layer.load(0, {shade: [0.5,'#fff']});
			var data = {order_id:{pigcms{$now_order.order_id},'offline_pay':$(this).data('id')};
			data.card_id = $('#coupon_price1').data('coupon');
			data.card_price = $('#coupon_price1').data('price');
			data.coupon_id = $('#coupon_price2').data('coupon');
			data.coupon_price = $('#coupon_price2').data('price');
			data.card_money = $('#user_card_money').data('price');
			data.score_used_count = $('#score_deducte').data('num');
			data.score_deducte = $('#score_deducte').data('price');
			data.price = $('#go_pay_money').data('price');
			data.is_use_score = $('#score_deducte_selecet').attr("checked") ? 1 : 0;
			data.user_card_id = "{pigcms{$now_card['card_id']}";
			$.post("{pigcms{:U('store_arrival_pay')}",data,function(result){
				// parent.layer.close(tip_index);
				if(result.status == 1){
					// clearInterval(checkPayStatus);
					layer.alert('{pigcms{:L_("支付成功")}', function(index){
						window.top.location.reload();
					});
				}else{
					layer.alert(result.info);
				}
			});
		});

		//"微信支付")}
		$(".pay .wx").click(function(){
			$(".chat, .shadow_two").show();
			$('#weixin_txt').focus();
			return false;
		});
		
		//"余额支付")}
		$(".pay .balance_pay").click(function(){
			if (phone) {
				var open_balance_pay_unverify = {pigcms{$config.open_balance_pay_unverify|intval=###};
				if (open_balance_pay_unverify==1) {					
					//"开启线下零售、到店付余额支付不使用短信验证码")}
					go_pay('balance','',-1,phone);
				}else{					
					$(".balance, .shadow_two").show();
					$('#balance_phone').focus();
					$('#balance_phone').val(phone);
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
		//"线上支付")}
		$(".on-line").click(function(){
			check_pay_time = setInterval(function(){
				$.post(store_arrival_check, {order_id:order_id}, function(result){
					if(result.status == 1){
						layer.msg('{pigcms{:L_("支付成功！")}');
						clearInterval(check_pay_time);
						// shop_mall.pay_success_back();
						$(".payment, .shadow_two, .shadow, .settlement").hide(); 
					}
				});
			},3000);
			$(".payment, .shadow_two").show(); 
		});
		// "取消订单")}
		$('.set_fr .cancel').click(function(){
			layer.confirm('{pigcms{:L_("确定取消预订之前，请及时联系顾客哦！确定取消？")}',{
				title:'{pigcms{:L_("取消订单")}',
				btn: ['{pigcms{:L_("确定', '取消")}'] }
				,function(index, layero){
				  	$.post(cancelUrl,function(result){
				  		if (result.err_code) {
				  			layer.alert(result.msg);
				  		}else{
							layer.msg(result.msg);
							// layer.closeAll();
							setTimeout(function(){							
								top.window.location.reload();
							},2000)
				  		}
					},'json');
				}
			);
		});

		//"返回")}
		$(".return").click(function(){
			if(!$(this).parents(".settlement").is(":hidden") && $(".shadow_two").is(":hidden")){
				// number_or_name_focus();
				$(".shadow").hide();
			}
			if(!$(".payment").is(":hidden") && !$(".shadow_two").is(":hidden")){
				// clearInterval(check_pay_time);
			}
			$(this).parents(".fix").hide();
			$(".shadow_two").hide();
		});

		var checkPayStatus = null;
		var userQrcodeFocus;
		$(function(){
			if(checkApp()){
				$('#userQrcode').remove();
				$('#qrcodeBox').prepend('<input type="tel" style="height:30px;line-height:30px;padding-left:5px;" id="userQrcode"/>');
				if(parent.customDisplayCan){
					window.pigcmspackapp.custom_display_work('',$('.userCode').attr('src'));
				}
			}
			$('.appcode').hide();
			
			$('#changeToAppScan').click(function(){
				$('.appcode').show();
				$('.wxcode').hide();
			});
			
			$('#changeToWxScan').click(function(){
				$('.wxcode').show();
				$('.appcode').hide();
			});
			
			$('#userQrcode').focus();
			userQrcodeFocus = setInterval(function(){
				if(document.activeElement.id != 'userQrcode'){
					$('#userQrcode').focus();
				}
			},1000);
			var postPrintNow = false; 
			$('#orderprinter').click(function(){
				if(postPrintNow == true){
					parent.layer.alert('{pigcms{:L_("正在请求中，请稍等")}');
					return false;
				}
				postPrintNow = true;
				$.post("{pigcms{:U('store_arrival_print')}",{order_id:{pigcms{$now_order['order_id']}},function(){
					parent.layer.alert('{pigcms{:L_("已发送打印")}');
					postPrintNow = false;
				});
			});

			
			$('#userQrcodeBtn').click(function(){
				postUserQrcode();
			});
			
			$('#balance_pay').click(function(){
				clearInterval(userQrcodeFocus)
				parent.layer.open({
				  type: 2,
				  title: '{pigcms{:L_("平台余额支付")}',
				  shadeClose: false,
				  shade: 0.6,
				  area: ['520px', '310px'],
				  content: "{pigcms{:U('arrival_balance_pay_before')}&order_id={pigcms{$now_order['order_id']}",
				  cancel:function(){
					  $('#userQrcode').focus();
						userQrcodeFocus = setInterval(function(){
							if(document.activeElement.id != 'userQrcode'){
								$('#userQrcode').focus();
							}
						},1000);
				  }
				});
			});
			$('#userQrcode').keyup(function(e){
				if(e.keyCode == 13){
					postUserQrcode();
				}
			});
			checkPayStatus = setInterval(function(){
				$.post("{pigcms{:U('store_arrival_check')}",{order_id:{pigcms{$now_order['order_id']}},function(result){
					if(result.status == 1){
						clearInterval(checkPayStatus);
						parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
							top.layer.load(0,{shade:0.3});
							top.location.reload();
						});
					}
				});
			},3000);
		});
		var postNow = false;
		var repay = 0;//用户非免密支付，需要再请求一次，带上这个参数 
		function postUserQrcode(){
			$('#userQrcode').val($.trim($('#userQrcode').val()));
			if(postNow == true){
				parent.layer.alert('{pigcms{:L_("正在请求中，请稍等")}');
				return false;
			}
			var auth_code = $('#userQrcode').val();
			var reg = new RegExp("^[0-9]*$");
			if(!reg.test(auth_code)){
				parent.layer.alert('{pigcms{:L_("付款码仅允许输入数字")}');
				return false;
			}
			if(auth_code.length < 2){
				$('#userQrcode').focus();
				return false;
			}
			var nowPayMethod = '';			
			var codePrefix =  auth_code.substr(0,2);		
			if(codePrefix == '10' || codePrefix == '11' || codePrefix == '12' || codePrefix == '13' || codePrefix == '14' || codePrefix == '15'){
				nowPayMethod = 'weixin';
				if(auth_code.length != 18){
					parent.layer.alert('{pigcms{:L_("微信付款码（以10/11/12/13/14/15为前缀的18位数字）")}', function(index){
						$('#userQrcode').focus();
						parent.layer.close(index);
					});
					return false;
				}
			}else if(canUseAlipay == true && (codePrefix == '25' || codePrefix == '26' || codePrefix == '27' || codePrefix == '28' || codePrefix == '29' || codePrefix == '30')){
				nowPayMethod = 'alipay';
				if(auth_code.length < 16 || auth_code.length > 24){
					parent.layer.alert('{pigcms{:L_("支付宝付款码（以25/26/27/28/29/30为前缀的16-24位数字）")}', function(index){
						$('#userQrcode').focus();
						parent.layer.close(index);
					});
					return false;
				}
			}else if(codePrefix == '51'){
				nowPayMethod = 'yzfpay';
				if(auth_code.length != 18){
					parent.layer.alert('{pigcms{:L_("翼支付款码（以51为前缀的18位数字）")}', function(index){
						$('#userQrcode').focus();
						parent.layer.close(index);
					});
					return false;
				}
			}else{
				parent.layer.alert('{pigcms{:L_("请扫描微信")}'+(canUseAlipay ? '{pigcms{:L_("或支付宝")}' : '')+'{pigcms{:L_("的付款码")}');
				return false;
			}
			$('#userQrcodeBtn').html('{pigcms{:L_("请求中")}...');
			postNow = true;
			var data = {order_id:{pigcms{$now_order.order_id},'auth_code':auth_code,'auth_type':nowPayMethod};
			data.card_id = $('#coupon_price1').data('coupon');
			data.card_price = $('#coupon_price1').data('price');
			data.coupon_id = $('#coupon_price2').data('coupon');
			data.coupon_price = $('#coupon_price2').data('price');
			data.card_money = $('#user_card_money').data('price');
			data.score_used_count = $('#score_deducte').data('num');
			data.score_deducte = $('#score_deducte').data('price');
			data.is_use_score = $('#score_deducte_selecet').attr("checked") ? 1 : 0;
			data.price = $('#go_pay_money').data('price');
			data.user_card_id = "{pigcms{$now_card['card_id']}";
			data.repay = repay;
			$.post("{pigcms{:U('store_arrival_pay')}",data,function(result){
				if(result.status == 1){
					clearInterval(checkPayStatus);
					parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
						top.layer.load(0,{shade:0.3});
						top.location.reload();
					});
				}
				else if(result.status == 2){
					repay = 1;
					parent.layer.alert(result.info);
				}
				else{
					parent.layer.alert(result.info);
				}
				$('#userQrcodeBtn,#userAlipayQrcodeBtn').html('{pigcms{:L_("确认支付")}');
				postNow = false;
			});
		}


var smsTimer;
var verify_phone_url="{pigcms{:U('balance_verify_phone')}",sms_code_url="{pigcms{:U('sendCode')}";
var price=parseFloat($('#go_pay_money').data('price'));
$(function(){
	var user_now_money = 0;
	$("#verify_phone").click(function(){
		var price=parseFloat($('#go_pay_money').data('price'));
		$.post(verify_phone_url, {phone:$('#balance_phone').val()}, function(result){
				if(result.status != 1){
					layer.msg(result.info);
				}else{
					$('.check_phone').hide();
					$('.confirm_pay').show();
					user_now_money=parseFloat(result.info.money);
					if(user_now_money<price){
						$('#title').html('{pigcms{:L_("当前平台余额：")}{pigcms{$config['Currency_symbol']}'+parseFloat(result.info.money)+',{pigcms{:L_("余额不足,待支付：")}<font color="red">{pigcms{$config['Currency_symbol']}'+price+'</font>')
					
					
						$('.confirm_pay .sms').css('background','#8b9092')
						$('.confirm_pay .firm').hide()
						$('.confirm_pay .sms,.confirm_pay .firm').css('color','#fff')
					}else{
						$('#title').html('{pigcms{:L_("当前平台余额：")}{pigcms{$config['Currency_symbol']}'+parseFloat(result.info.money)+',{pigcms{:L_("待支付：")}{pigcms{$config['Currency_symbol']}'+price)	
						$('.confirm_pay .firm').html('{pigcms{:L_("确认支付")} ({pigcms{$config['Currency_symbol']}'+price.toFixed(2)+')');
						$('.confirm_pay .firm').show()
						smscode();
					}
					$('.return').hide();
					$('.back_check').show();
				}
			});
	})
	
	$('.sms').click(function(){
		if(user_now_money>=price){
			smscode();
		}
	})
	
	$('#go_balance_pay').click(function(){
		go_pay('balance', $('#smscode').val(),-1,$('#balance_phone').val());
	})
	
	
	$('.back_check').click(function(){
		clearInterval(smsTimer);
		$('.sms').html('{pigcms{:L_("发送验证码")}')
		$('#title').html('{pigcms{:L_("只有在平台账户绑定手机号的用户才能使用平台余额支付")}')	
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
});
function smscode(val){
	$.post(sms_code_url, {phone:$('#balance_phone').val()}, function(result){
		layer.msg('{pigcms{:L_("短信发送成功")}');
		$('.sms').data('second',59).html('59 秒').prop('disabled',true);
		smsTimer = setInterval(function(){
			var second = $('.sms').data('second');
			if(second == 1){
				$('.sms').html('{pigcms{:L_("发送验证码")}').prop('disabled',false);
				clearInterval(smsTimer);
			}else{
				second--;
				$('.sms').data('second',second).html(second + ' 秒');
			}
		},1000);
	})
}

function go_pay(paymethod, auth_code,offline_pay,phone){
	var price=parseFloat($('#go_pay_money').data('price'));
	var tip_index = parent.layer.load(0, {shade: [0.5,'#fff']});

	var data = {order_id:{pigcms{$now_order.order_id},'phone':phone};
	data.card_id = $('#coupon_price1').data('coupon');
	data.card_price = $('#coupon_price1').data('price');
	data.coupon_id = $('#coupon_price2').data('coupon');
	data.coupon_price = $('#coupon_price2').data('price');
	data.card_money = $('#user_card_money').data('price');
	data.score_used_count = $('#score_deducte').data('num');
	data.score_deducte = $('#score_deducte').data('price');
	data.price = $('#go_pay_money').data('price');
	data.is_use_score = $('#score_deducte_selecet').attr("checked") ? 1 : 0;
	data.user_card_id = "{pigcms{$now_card['card_id']}";
	if (paymethod == 'balance') {
		data.balance_pay = 1;
		data.smscode = auth_code;
	}else if(paymethod == 'balance'){

	}

	$.post("{pigcms{:U('store_arrival_pay')}",data,function(result){
		parent.layer.close(tip_index);
		if(result.status == 1){
			// clearInterval(checkPayStatus);
			parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
				window.top.location.reload();
			});
		}else{
			parent.layer.alert(result.info);
		}
	});
}
	
		//一卡通支付逻辑--start
		$('#internalCard').click(function(){
			clearInterval(userQrcodeFocus)
			parent.layer.open({
				title: '{pigcms{:L_("一卡通支付")}',
				shadeClose: false,
				shade: 0.6,
				area: ['520px', '250px'],
				content: `
				<div class="internalCardNumberNumber" style="display: flex;flex-direction: column;align-items: center;padding-top: 30px;">
					<input style="width: 70%;display: block" type="text" placeholder="请输入一卡通号或者手机号" id="internalCardNumber">
					<div class="internalCardTip" style="color: red;margin-top: 6px"></div>
				</div>
				`,
				btn: ['查询'],
				yes:function(tip_index, layero){
				internalCardNumber  = parent.$('#internalCardNumber').val()
				ajax_card_new(tip_index,internalCardNumber)
				},
				success: function(layero, tip_index) {
					parent.$('#internalCardNumber').focus();
				}
			});
		});

		//一卡通查询逻辑
		function ajax_card_new(tip_index,key,password=''){
			if (key.length < 1) return false;
			let params = {
				order_id:{pigcms{$now_order.order_id},
				key,
				type:'ecard',
				password,
				v20_ticket: getCookieValue('storestaff_access_token'),
			}
			$.post(`/appapi.php?c=Storestaff&a=ajax_card_new`, params, function(res){
				let data = res.result
				if (res.errorCode == '0') {
					// 第一次输入是否需要密码
					if(data.need_password == true){
						internalCardPassword()
						return
					}

					//不需要密码或者输入密码操作
					if(data.internal_card_ids && data.internal_card_ids.length>0){
						internal_card_info = data
						internalCardPay()
					}else if(data.internal_card_ids){
						parent.$('.internalCardTip').html('未搜索到信息');
					}
				} else {
					parent.$('.internalCardTip').html(res.errorMsg)
					return
				}
			}, 'json');
		}

		//一卡通输入密码
		function internalCardPassword(){
			parent.layer.open({
				title: '{pigcms{:L_("一卡通密码")}',
				shadeClose: false,
				shade: 0.6,
				area: ['520px', '250px'],
				content: `
				<div class="internalCardNumberNumber" style="display: flex;flex-direction: column;align-items: center;padding-top: 30px;">
					<input style="width: 70%;display: block" type="password" placeholder="请输入一卡通密码" id="internalCardNumber">
					<div class="internalCardTip" style="color: red;margin-top: 6px"></div>
				</div>
				`,
				btn: ['确定'],
				yes:function(tip_index, layero){
				let password  = parent.$('#internalCardNumber').val()
				if(!password)return
				ajax_card_new(tip_index,internalCardNumber,password)
				},
				success: function(layero, tip_index) {
					parent.$('#internalCardNumber').focus();
				}
			});
		}
		
		//一卡通支付
		function internalCardPay(){
			parent.layer.open({
				title: '{pigcms{:L_("一卡通支付")}',
				shadeClose: false,
				shade: 0.6,
				area: ['520px', '200px'],
				content: `
				<div class="internalCardNumberNumber" style="display: flex;flex-direction: column;align-items: center;padding-top: 30px;">
					<p id="internalCardTitle" style="font-size: 16px;color: #f88718;">
						当前一卡通余额：￥<span id="ecard_total_money">${internal_card_info.ecard_total_money || 0}</span>
						，待支付 ￥<span class="total_price" style="color: red;">{pigcms{$now_order['price']|floatval}</span>
					</p>
					<div class="internalCardTip" style="color: red;margin-top: 6px"></div>
				</div>
				`,
				btn: ['支付'],
				yes:function(tip_index, layero){
				let phone = internal_card_info.internal_card_ids[0].phone
				let params = {
					order_id: "{pigcms{$now_order['order_id']}",
					auth_type: 'ecard',
					price: "{pigcms{$now_order['price']|floatval}",
					offline_pay: -1,
					phone: internalCardNumber,
					v20_ticket: getCookieValue('storestaff_access_token'),
				}
				$.post(`/appapi.php?c=Storestaff&a=store_arrival_pay`, params, function(res){
					let data = res.result
					if (res.errorCode == '0') {
						parent.layer.alert('{pigcms{:L_("支付成功")}', function(index){
							window.top.location.reload();
						});
					} else {
						parent.$('.internalCardTip').html(res.errorMsg)
						return
					}

				}, 'json');
				}
			});
		}

		// 获取指定名称的 cookie 值
		function getCookieValue(cookieName) {
			var cookies = document.cookie;
			if (cookies) {
				var cookieArr = cookies.split(';');
				for (var i = 0; i < cookieArr.length; i++) {
				var cookie = cookieArr[i].trim();
				if (cookie.indexOf(cookieName + '=') === 0) {
					return cookie.substring(cookieName.length + 1);
				}
				}
			}
			return null;
		}
		//一卡通支付逻辑--end
	</script>
	</body>
</html>