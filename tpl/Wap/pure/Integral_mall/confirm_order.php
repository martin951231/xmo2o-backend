<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/datePicker.css">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/mobiscroll_min.css" media="all">
<script type="text/javascript" src="{pigcms{$static_path}integral_mall/js/jquery1.8.3.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}integral_mall/js/dialog.js"></script>
<script type="text/javascript" src="{pigcms{$static_path}integral_mall/js/mobiscroll_min.js"></script>

<title>{pigcms{$store['name']|default="积分商城"}</title>
<meta content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
<meta name="Keywords" content="">
<meta name="Description" content="">
<meta content="telephone=no, address=no" name="format-detection">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="format-detection" content="telephone=no"/>
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/main.css" media="all">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/index.css" media="all">
<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}integral_mall/css/confirm_order.css" media="all">
<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
<style>
    .pay_box li a > span {
        width: 100%;
         text-align: left;
    }

	.layermcont{padding: 0 !important;overflow: hidden;}
	.layermcont .title_info, .layermcont .title_msg{background-color: #ff4d00;padding: 10px 15px;color: #fff;text-align: center;}
	.layermcont .title_msg{background: #fff;color: #555;}
	.layermcont .content-wrapper{padding:15px;width: 240px;}
	.layermcont .tip_info_list{line-height: 2;}
	.layermbtn span[type="1"]{color: #ff4d00;}
</style>
</head>
<script type="text/javascript" src="{pigcms{$static_path}integral_mall/js/scroller.js"></script>
<body onselectstart="return true;" ondragstart="return false;">
<div class="container">
    <form name="cart_confirm_form" id="cart_confirm_form" action="{pigcms{:U('Integral_mall/save_order')}" method="post">
	    <input type="hidden" name="num" value="{pigcms{$_GET['num']}"/>
        <input type="hidden" name="spec_index" value="{pigcms{$_GET['spec_index']}"/>
        <input type="hidden" name="mer_id" value="{pigcms{$detail['mer_id']}"/>
        <input type="hidden" name="goods_id" value="{pigcms{$detail['goods_id']}"/>
    <section class="menu_wrap pay_wrap">
		<ul class="box">
			<li>
				<a class="">配送方式：</a>&nbsp;&nbsp;
				<if condition="$delivery_type eq 0">
				<a class="btn_express pick_in_store" id="post_package" data-key='1'>快递配送</a>&nbsp;&nbsp;
				</if>
				<if condition="$delivery_type eq 1">
				<a class="btn_express pick_in_store" id="pick_in_store" data-key='2'>自提</a>
				</if>
			</li>
			<if condition="$delivery_type eq 0">
			<li id="li_delivery">
				<a href="{pigcms{:U('My/adress',array('buy_type' => 'integral_mall', 'mer_id' => $detail['mer_id'], 'current_id'=>$user_adress['adress_id'], 'order_id'=>$order_id,'goods_id'=>$detail['goods_id'], 'num' => $_GET['num'], 'spec_index' => $_GET['spec_index']))}">
					<strong>
						<span id="showAddres"><if condition="$user_adress['adress_id']">{pigcms{$user_adress['province_txt']} {pigcms{$user_adress['city_txt']} {pigcms{$user_adress['area_txt']} {pigcms{$user_adress['adress']} {pigcms{$user_adress['detail']}<else/>请点击添加送货地址</if></span><br>
						<span id="showName">{pigcms{$user_adress['name']}</span>
						<span id="showTel">{pigcms{$user_adress['phone']}</span>
					</strong>
					<div><i class="ico_arrow"></i></div>
				</a>
			</li>
			</if>
			<if condition="$delivery_type eq 1 && $pick_address">
                <li id="li_pick">
                    <a href="{pigcms{:U('My/pick_address',array('buy_type' => 'integral_mall', 'mer_id' => $detail['mer_id'], 'current_id'=>$user_adress['adress_id'], 'order_id'=>$order_id,'goods_id'=>$detail['goods_id'], 'num' => $_GET['num'], 'spec_index' => $_GET['spec_index'],'pick_addr_id' => $pick_address['pick_addr_id']))}">
                        <strong>
                            <span id="showAddres">地址：{pigcms{$pick_address['name']}</span><br>
                            <span id="showName">电话：{pigcms{$pick_address['phone']}</span><br>
                            <span id="showTel">省市区：{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']}</span>
                        </strong>
                        <div><i class="ico_arrow"></i></div>
                    </a>
                </li>
			</if>
            <if condition="$error_tips">
                <span id="error_tips" style="color: red;font-size: 12px;display: none;">缺货提示：{pigcms{$error_tips}</span>
            </if>
            <div><img class="w100" style="display: block;" src="{pigcms{$static_path}integral_mall/images/wxapp_addaddress.png"></div>
		</ul>

		<div class="store-info">
			<!-- <img src="{pigcms{$static_path}mall/images/seckill.png"> -->
			<span class="name">{pigcms{$merchant['name']|default="积分商城"}</span>
		</div>
		<if condition="!empty($detail)">
			<ul class="menu_list order_list" id="orderList">
                <li class="goods-item">
                    <div>
                    <if condition="!empty($order['wap_image'])">
                        <img src="{pigcms{$order['wap_image']}" alt="">
                        <else/>
                        <img src="{pigcms{$detail['wap_image']}" alt="">
                    </if>
                    </div>
                    <div>
                        <if condition="!empty($order['wap_image'])">
                            <h3 class="ellipsis">{pigcms{$order['goods_name']}</h3>
                            <else/>
                            <h3 class="ellipsis">{pigcms{$detail['goods_name']}</h3>
                        </if>
                        <strong class="price font_size16">
                            积分
                            <if condition="$spec_info">
                                    <span class='unit_price'>{pigcms{$spec_info.payment_pure_integral}</span>
                                <elseif condition="!empty($order)"/>
                                    <span class='unit_price'>{pigcms{$order.payment_pure_integral}</span>
                                <else/>
                                    <span class='unit_price'>{pigcms{$detail.payment_pure_integral}</span>
                            </if>
                            <span class="fr font_size14 color_3" style="margin-top: 2px;">x
                                <if condition="!empty($order)">
                                    {pigcms{$order['num']}
                                    <else/>
                                    {pigcms{$_GET['num']}
                                </if>
                            </span>
                        </strong>
                    </div>
                </li>
			</ul>
			<ul class="menu_list discount-list box">
				<li>
					<div>
						<h3>总需要积分：
							<span class='fr'>
                                {pigcms{$basic_price|floatval}
							</span>
						</h3>
					</div>
				</li>
                <php>if ($express_fee > 0) {</php>
                    <li id="show_delivery_fee">
                        <div>
                            <h3 >
                                配送费：
                                <span class='fr'>
                                    <if condition="$detail['freight_charges'] eq 1">
                                    (商家承担)
                                    </if>
                                    {pigcms{$config['Currency_symbol']}{pigcms{$express_fee}
                                </span>
                            </h3>
                        </div>
                    </li>
                <php>}</php>
                <php>if ($pay_price > 0) {</php>
                    <li>
                        <div>
                            <h3>
                                <span class="fr">小计：
                                    {pigcms{$config['Currency_symbol']}<span class="font_size24 total_price">{pigcms{$pay_price}</span>
                                </span>
                            </h3>
                        </div>
                    </li>
                <php>}</php>
			</ul>
		</if>

		<ul class="box pay_box">
			<li>
				<a href="javascript:void(0);" id="remarkBtn" class="line_box">
					<strong>订单备注</strong>
					<span id="remarkTxt">点击添加订单备注</span>
					<div><i class="ico_arrow"></i></div>
				</a>
			</li>
		</ul>
	</section>
	<div style="display:none;">
		<input class="hidden" id="order_id" name="order_id" value="{pigcms{$order_id}">
		<input class="hidden" id="ouserName" name="ouserName" value="{pigcms{$user_adress['name']}">
		<input class="hidden" id="ouserTel" name="ouserTel" value="{pigcms{$user_adress['phone']}">
		<input class="hidden" id="ouserAddres" name="ouserAddres" value="{pigcms{$user_adress['province_txt']} {pigcms{$user_adress['city_txt']} {pigcms{$user_adress['area_txt']} {pigcms{$user_adress['adress']}  {pigcms{$user_adress['detail']}">
		<input class="hidden" id="address_id" name="adress_id" value="{pigcms{$user_adress['adress_id']}">
        <if condition="$pick_address">
            <input type="hidden" name="pick_address" value="{pigcms{$pick_address['area_info']['province']} {pigcms{$pick_address['area_info']['city']} {pigcms{$pick_address['area_info']['area']} {pigcms{$pick_address['name']} 电话：{pigcms{$pick_address['phone']}"/>
            <input type="hidden" name="pick_id" value="{pigcms{$pick_address['pick_addr_id']}"/>
        </if>

        <input class="hidden" id="area_code" name="area_code" value="{pigcms{$area_code}">
        <input class="hidden" id="area_address" name="area_address" value="{pigcms{$area_address}">
        <input class="hidden" id="omark" name="omark" value="{pigcms{$order['desc']}">
	</div>
	</form>
	<div class="addres_box" id="remarkBox">
		<ul>
			<li><textarea class="txt max" placeholder="请填写备注" id="userMark"></textarea></li>
			<li class="btns_wrap">
			<span><a href="javascript:void(0);" class="comm_btn higher disabled" id="cancleRemark">取消</a></span>
			<span><a href="javascript:void(0);" class="comm_btn higher" id="saveRemark">确认</a></span>
			</li>
		</ul>
	</div>
</div>
<div class="fixed" style="height:50px;padding:14px;">
	<div style="position: absolute; bottom: 8px; left: 10px;right: 10px;">
		<span style="line-height: 31px;color: #ff510c;">
			待支付：<strong>
                <if condition="$pay_price gt 0">{pigcms{$config['Currency_symbol']}<span id="totalPrice_"><i class='total_price'>{pigcms{$pay_price} +</i></span></if>
                <i class='total_price'>{pigcms{$basic_price|floatval}积分</i>
            </strong>

		</span>
		<span class="fr">
			<a href="javascript:;" class="comm_btn" id="submit_order" >确认订单</a>
		</span>
		
	</div>
</div>
<script type="text/javascript" src="{pigcms{$static_path}layer/layer.m.js"></script>
<script type="text/javascript">
var motify = {
	timer:null,
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
	}
};
$(document).ready(function () {
	$('.mask').click(function(){
		$('.mask').addClass('hidden');
        return false;
	});
    var desc = "{pigcms{$order['desc']}";
    if (desc != '') {
        $('#remarkTxt').text(desc);
    }
    
	$(window).scrollTop(1);
	$('div.fixed').css('bottom','0');

	// 添加备注
	$('#remarkBtn').bind('click', function(){
		var remark = $('#remarkTxt').text();
        console.log('remark', remark);
		if(remark == '点击添加订单备注') remark = '';
		$('#userMark').val(remark);
		$('#remarkBox').dialog({title: '添加备注'});
	});

	$('#cancleRemark').bind('click', function(){
		$('#remarkBox').dialog('close');
	});

	$('#saveRemark').bind('click', function(){
		$('#remarkTxt').text($('#userMark').val());
		$('#userMark').val('');
		$('#remarkBox').dialog('close');
	});


	$("#submit_order").click(function(){
        if($('#error_tips').text() != ''){
            motify.log($('#error_tips').text());
            return false;
        }
		if($('#deliver_type').val() == 0 && $('#address_id').val() == ''){
			motify.log('请您先添加配送地址');
			return false;
		}
		if(!$(this).hasClass('disabled')){
			$(this).addClass('disabled');
			
			var wo_memo = $.trim($("#remarkTxt").html());

			if(wo_memo == '点击添加订单备注') {
				wo_memo = '';
			}
			$('#omark').val(wo_memo);

			$.post($('#cart_confirm_form').attr('action'), $('#cart_confirm_form').serialize(), function(response){
			    console.log('response', response);
				if (response.status) {
					if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
						wx.miniProgram.navigateTo({
							url:'/pages/pay/index?order_id='+ response.info.order_id +'&type=mall'
							,complete:function(){
								$("#submit_order").removeClass('disabled');
							}
						});
					}else{


						$("#submit_order").removeClass('disabled');
						if (response.info.freight_charge>0) {
							location.href = "{pigcms{:U('Pay/check',array('type'=>'integral_mall'))}&order_id="+response.info.order_id;
						}else{
							go_pay(response.info.order_id)
						}
					}
				} else {
					alert(response.info);
					$("#submit_order").removeClass('disabled');
				}
			});
			return false;
		}
		return false;
	});
	
	if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
		var reg = /versioncode=(\d+),/;
		var arr = reg.exec(navigator.userAgent.toLowerCase());
		if(arr == null){
			
		}else{
			var version = parseInt(arr[1]);
			if(version >= 50){
				if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
					iosCommon.iosFunction('hideWebViewHeader/true', {name:'hideWebViewHeader', show_head: '1'});
					$('#li_delivery a').click(function(){
						var address_id = $('#address_id').val() == '' ? '0' : $('#address_id').val();
						iosCommon.iosFunction('getUserAddress/'+address_id, {name:'getUserAddress', address_id:address_id });
						return false;
					});
				}else{
					$('#li_delivery a').click(function(){
						var address_id = $('#address_id').val() == '' ? '0' : $('#address_id').val();
						window.lifepasslogin.getUserAddress(address_id);
						return false;
					});
				}
			}
		}
	}
});

var click = false;
function go_pay(order_id) {
    if (click) return false;
    click = true;
    setTimeout(function () {
        if (click) {
            console.log('change')
            click = false;
        }
    }, 2000);
    var pay_check_url = "{pigcms{:U('Integral_mall/pay_check')}";
    var go_pay_url = "{pigcms{:U('Integral_mall/go_pay')}";

    $.post(pay_check_url, {'order_id': order_id}, function (data) {
        console.log('支付信息-》  ', data)
        click = false;
        if (data.errcode == 2) {
        	layer.open({
                content: '<div  class="title_msg">请先登录</div>'
                , btn: ['去登录']
                , yes: function (index) {
                    location.href = data.url;
                }
            });
        } else if (data.errcode > 0) {
            layer.open({
                content: '<div  class="title_msg">' + data.msg + '</div>'
                , btn: ['确定']
                , yes: function (index) {
                    layer.close(index);
                }
            });
        } else{
            // 可以兑换
            layer.open({
                content: '<div  class="title_info">提示</div>' +
                '<div class="content-wrapper"><div class="tip_info_list">{pigcms{:L_("剩余商家积分")}：<span class="money_info">' + data.info.card_score + '</span></div>' +
                '<div class="tip_info_list">{pigcms{:L_("当前兑换所需积分")}：<span class="money_info" style="color: #ff4d00">' + data.info.total_integral + '</span></div>'
                , btn: ['立即兑换', '取消']
                , yes: function (index) {
                	$.post(go_pay_url, {'order_id': order_id}, function (data) {
				        console.log('支付信息gopay-》  ', data)
				        if (data.errcode == 2) {
				        	layer.open({
				                content: '<div  class="title_msg">请先登录</div>'
				                , btn: ['去登录']
				                , yes: function (index) {
                    				location.href = data.url;
				                }
				            });
				        } else if (data.errcode > 0) {
				            layer.open({
				                content: '<div  class="title_msg">' + data.msg + '</div>'
				                , btn: ['确定']
				                , yes: function (index) {
                    				layer.close(index);
				                }
				            });
				        } else{
				        	// 兑换成功
				            layer.open({
				                content: '<div  class="title_msg">' + data.msg + '</div>'
				                , btn: ['确定']
				                , yes: function (index) {
				                    window.location.href = data.url;
				                }
				            });
				        }
				    }, 'json');
                }
            });
        }
    }, 'json');
}
function callbackUserAddress(address){
	var addressArr = address.split('<>');
	// $('#remarkTxt').html(addressArr[0]);
	$('#address_id').val(addressArr[0]);
	$('#showName').html(addressArr[1]);
	$('#showTel').html(addressArr[2]);
	$('#showAddres').html(addressArr[3]);
	<php>
		$tmpGet = $_GET;
		unset($tmpGet['adress_id']);
	</php>
	window.location.href = "{pigcms{:U('confirm_order',$tmpGet)}&adress_id="+addressArr[0];
}
</script>
<script type="text/javascript">
    window.onload = function(){
            if($('#error_tips').text() != ''){
                motify.log($('#error_tips').text(),5000);

            }
    }
</script>

</body>
{pigcms{$hideScript}
</html>