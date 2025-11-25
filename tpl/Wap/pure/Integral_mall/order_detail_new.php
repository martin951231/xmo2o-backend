<!DOCTYPE html>
<html lang="zh-CN">
    <head>
        <meta charset="utf-8"/>
        <title>商家积分商城订单详情</title>
        <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,minimum-scale=1.0,user-scalable=no,width=device-width"/>
        <meta http-equiv="pragma" content="no-cache"/>
        <meta name="apple-mobile-web-app-capable" content="yes"/>
        <meta name='apple-touch-fullscreen' content='yes'/>
        <meta name="apple-mobile-web-app-status-bar-style" content="black"/>
        <meta name="format-detection" content="telephone=no"/>
        <meta name="format-detection" content="address=no"/>
        <link href="{pigcms{$static_path}mall/css/order_detail.css?v=01" rel="stylesheet"/>
        <script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
       	<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?2112222" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
        <style>
            #pageLoadTip{display:none !important;}
            .finish_order {
                width: 100% !important;
            }
            .w_100 {
                width: 100% !important;
            }
        </style>
    </head>
    <body>
        <section class="public">
            <a class="return link-url" id="goBackUrl" href="{pigcms{$goBackUrl|default=U('Integral_mall/order_list', array('mer_id' => $order_details['mer_id']))}" data-url="{pigcms{$goBackUrl|default=U('Integral_mall/order_list', array('mer_id' => $order_details['mer_id']))}"></a>
            <div class="content">订单详情</div>
        </section>
        <if condition="$order_details['log_list']">
        <section class="defrayal">
            <div class="defrayal_n" <if condition="$order_details['paid'] egt 1 && $order_details['is_pick_in_store'] eq 0 && 0">style="height: 102px;"</if>>
                <a href="{pigcms{:U('Integral_mall/status_new', array('order_id' => $order_details['order_id'],'app_no_header'=>1))}">
                    <if condition="$order_details['log_list']['status'] eq 0"> <h2>订单生成成功</h2> <p>订单编号：{pigcms{$order_details.orderid}</p>
                    <elseif condition="$order_details['log_list']['status'] eq 1"/> <h2>订单支付成功</h2> <p>订单编号：{pigcms{$order_details.orderid}</p>
                    <elseif condition="$order_details['log_list']['status'] eq 2"/> <h2>商家发货成功</h2> <p>订单编号：{pigcms{$order_details.orderid}</p>
                    <elseif condition="$order_details['log_list']['status'] eq 3"/> <h2>用户收货</h2> <p>订单编号：{pigcms{$order_details.orderid}</p>
                    <elseif condition="$order_details['log_list']['status'] eq 4"/> <h2>已退款</h2> <p>订单编号：{pigcms{$order_details.orderid}</p>
                    <elseif condition="$order_details['log_list']['status'] eq 5"/> <h2>用户已自提</h2> <p>订单编号：{pigcms{$order_details.orderid}</p>
                    </if>
                    <if condition="$order_details['paid'] egt 1 && $order_details['is_pick_in_store'] eq 0 && 0">
                        <p>自提码：{pigcms{$order_details.fetch_number}</p>
                    </if>
                    <div class="time">{pigcms{$order_details['log_list']['date_line']|date="Y-m-d H:i",###}</div>
                    <em>更多状态</em>
                </a>
            </div>
        </section>
        </if>
        <div class="h105"></div>
        <section class="g_details p40" <if condition="$order_details['paid'] egt 1 && $order_details['is_pick_in_store'] eq 0 && 0">style="margin-top: 56px;"</if>>
            <div class="infor">
                <!-- 新版的商品详情 -->
                <ul class="menu_list order_list" id="orderList">
                    <li class="goods-item">
                        <div>
                            <img src="{pigcms{$order_details.wap_image}" alt="{pigcms{$order_details.goods_name}">
                        </div>
                        <div>
                            <h3 class="ellipsis">{pigcms{$order_details.goods_name}</h3>
                            <if condition="!empty($spec_info)">
                                <div class="spec ellipsis font_size12 color_9" style="line-height: 1.4;padding-top: 5px;">
                                    <volist name="spec_info['spec']" id="spec">
                                        <span>{pigcms{$spec['spec_val_name']}&nbsp;&nbsp;</span>
                                    </volist>
                                </div>
                            </if>
                            <span class="count">3</span>
                            <strong class="price font_size16">
                                积分
                                <span class='unit_price'>{pigcms{$order_details.payment_pure_integral}</span>
                                <span class="fr font_size14 color_3" style="margin-top: 2px;">x{pigcms{$order_details.num}</span>
                            </strong>
                        </div>
                    </li>
                </ul>
                <if condition="$order_details['freight_charge'] gt 0">
                    <div class="mealfee">
                        <dl>
                            <dd class="clr">
                                <div class="fl">配送费</div>
                                <div class="fr">
                                    <if condition="$order_details['freight_charges'] eq 1">
                                        <span style="color: red;">(商户承担)</span>
                                    </if>
                                    {pigcms{$config['Currency_symbol']}{pigcms{$order_details['freight_charge']}
                                </div>
                            </dd>
                        </dl>
                    </div>
                </if>
                <div class="answer clr">
                    <div class="fl">
                        订单
                        <if condition="$order_details['total_money'] gt 0">
                            {pigcms{$config['Currency_symbol']}{pigcms{$order_details['total_money']|floatval} +
                        </if>
                        {pigcms{$order_details.payment_pure_integral} 积分
                    </div>
                    <if condition="$order_details['total_money'] gt 0">
                        <div class="fr">
                            <div>应付总金额: {pigcms{$config['Currency_symbol']}{pigcms{$order_details['real_money']|floatval}</div>
                        </div>
                    </if>
                </div>            
            </div>

            <div class="infor">
                <ul>
                    <if condition="$order_details['is_pick_in_store'] eq 0">
                        <li class="clr first">
                            <div class="fl match">自提信息</div>
                        </li>
                        <li class="clr">
                            <div class="fl">配送方式</div>
                            <div class="fr">{pigcms{$order_details['deliver_str']}</div>
                        </li>
                        <li class="clr">
                            <div class="fl">自提地址</div>
                            <div class="p90">
                                <p>{pigcms{$order_details['address']}</p>
                            </div>
                        </li>
<!--                        <li class="clr">-->
<!--                            <div class="fl">自提二维码</div>-->
<!--                            <div class="p90">-->
<!--                                <p id="see_storestaff_qrcode">查看二维码</p>-->
<!--                            </div>-->
<!--                        </li>-->
                    <else />
                        <li class="clr first">
                            <div class="fl match">配送信息</div>
                        </li>
                        <li class="clr">
                            <div class="fl">配送方式</div>
                            <div class="fr">{pigcms{$order_details['deliver_str']}</div>
                        </li>
                        <li class="clr">
                            <div class="fl">收货信息</div>
                            <div class="p90">
                                <p>{pigcms{$order_details['address']}</p>
                                <p>{pigcms{$order_details['username']} {pigcms{$order_details['userphone']}</p>
                            </div>
                        </li>
                        <if condition="$order_details['is_pick_in_store'] eq 1 AND $order_details['express_name'] AND $order_details['express_number']">
                            <li class="clr">
                                <div class="fl">快递详情</div>
                                <dl class="kd_dl kd_dls">
                                    <dd>
                                        <h2 class="endt">已发货：{pigcms{$order_details['express_name']} <a>{pigcms{$order_details['express_number']}</a></h2>
                                        <p><a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$order_details['express_code']}&postid={pigcms{$order_details['express_number']}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>" target="_blank" >查看物流 ></a></p>
                                    </dd>
                                </dl>
                            </li>
                        </if>
                        <if condition="$order_details['send_note'] neq ''">
                            <li class="clr">
                                <div class="fl">发货备注信息</div>
                                <div class="p90" style="text-align: left;padding-left: 100px;">{pigcms{$order_details['send_note']}</div>
                            </li>
                        </if>
                    </if>
                </ul>
            </div>
            
            <div class="infor">
                <ul>
                    <li class="clr first">
                        <div class="fl book">订单信息</div>
                    </li>
                    <li class="clr">
                        <div class="fl">订单编号</div>
                        <div class="fr">{pigcms{$order_details['orderid']}</div>
                    </li>
                    <li class="clr">
                        <div class="fl">下单时间</div>
                        <div class="fr">{pigcms{$order_details['create_time']}</div>
                    </li>
                    <if condition="$order_details['note'] neq ''">
                        <li class="clr">
                            <div class="fl">备注信息</div>
                            <div class="p90" style="text-align: left;">{pigcms{$order_details['note']}</div>
                        </li>
                    </if>
                </ul>
            </div>
             <if condition="$order_details['paid'] eq 1">
             <div class="infor">
                 <ul>
        	         <li class="clr first">
        	             <div class="fl branch">支付信息</div>
        	         </li>
                     <li class="clr">
                         <div class="fl">支付时间</div>
                         <div class="fr">{pigcms{$order_details['pay_time']}</div>
                     </li>
                     <li class="clr">
                         <div class="fl">支付方式</div>
                         <div class="fr">{pigcms{$order_details['pay_type_str']}</div>
                     </li>
                     <if condition="$order_details['total_money'] gt 0">
                         <li class="clr">
                             <div class="fl">应收总额</div>
                             <div class="p90">
                                 <div>应收总金额: {pigcms{$config['Currency_symbol']}{pigcms{$order_details['total_money']|floatval}</div>
                                 <php>if ($order_details['freight_charges']==1 && $order_details['freight_charge']>0) {</php>
                                     <span style="color: red;">(商户承担运费)</span>
                                 <php>}</php>
                             </div>
                         </li>
                     </if>
                     <if condition="$order_details['card_give_money'] gt 0">
                     <li class="clr">
                         <div class="fl">会员卡赠送余额支付</div>
                         <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['card_give_money']|floatval}</div>
                     </li>
                     </if>
                     <if condition="$order_details['merchant_balance'] gt 0">
                     <li class="clr">
                         <div class="fl">商家余额支付</div>
                         <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['merchant_balance']|floatval}</div>
                     </li>
                     </if>
                     <if condition="$order_details['balance_pay'] gt 0">
                     <li class="clr">
                         <div class="fl">平台余额支付</div>
                         <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['balance_pay']|floatval}</div>
                     </li>
                     </if>
                     <if condition="$order_details['payment_money'] gt 0">
                     <li class="clr">
                         <div class="fl">{pigcms{$order_details['pay_type_str']}</div>
                         <div class="fr e2c">-{pigcms{$config['Currency_symbol']}{pigcms{$order_details['payment_money']|floatval}</div>
                     </li>
                     </if>
                 </ul>
             </div>
            </if>
            <div class="consume consumes">
                <ul class="clr">
                    <if condition="$order_details['status'] elt 3">
                        <if condition="$order_details['paid'] eq 0">
							<li class="fl firmly w_100 order-pay" data-order_id="{pigcms{$order_details['order_id']}">支付订单</li>
                        </if>
                        <if condition="$order_details['paid'] eq 1 AND $order_details['status'] eq 2 AND $order_details['is_pick_in_store'] eq 1">
                            <li class="fl firmly finish_order" data-url="{pigcms{:U('Integral_mall/finish_order', array('order_id' => $order_details['order_id']))}">确认收货</li>
                        </if>
                        <if condition="$order_details['paid'] eq 1 AND $order_details['status'] eq 1 AND $order_details['is_pick_in_store'] eq 0">
                            <li class="fl firmly w_100 finish_take" data-url="{pigcms{:U('Integral_mall/finish_take', array('order_id' => $order_details['order_id']))}">确认自提</li>
                        </if>
                        <if condition="$order_details['status'] eq 3">
                            <li class="fr zlyd w_100" data-url="{pigcms{:U('Integral_mall/goodsDetail', array('goods_id' => $order_details['goods_id']))}">再来一单</li>
                        </if>
                    <else/>
                    <li class="fr zlyd w_100" data-url="{pigcms{:U('Integral_mall/goodsDetail', array('goods_id' => $order_details['goods_id']))}">再来一单</li>
                    </if>
                </ul>
            </div>
        </section>
		<if condition="$config.open_share_lottery eq 1 AND $is_weixin_browser AND $order_details.paid  eq 1 AND $lottery_info.status neq 1">
			<div class="float-open" id="float-open"  <if condition=" ( $lottery.status eq 0) OR  ($order_details.share_status eq 0 AND $config.lottery_must_share eq 1)">style="display:none;"</if> ><a class="open-btn" href="javascript:void(0);"><img src="{pigcms{$static_path}shop/images/lottery.png"></a></div>
		<elseif condition="$config.open_share_lottery eq 0  AND $config.share_coupon eq 1 AND $is_weixin_browser AND $config.get_coupon_must_share eq 0 AND $order_details.paid  eq 1 AND !empty($share_coupon)" />
			<div class="float-open" id="send_friend" ><a class="open-btn" href="javascript:void(0);"><img src="{pigcms{$static_path}shop/images/share_coupon.png"></a></div>
		</if>
		

        <include file="floatTabble"/>
    </body>

    {pigcms{$hideScript}
    <script src="{pigcms{$static_public}js/jquery.qrcode.min.js"></script>
    <script type="text/javascript">
    $(function(){
        //查看自提二维码
        $('#see_storestaff_qrcode').click(function(){
            var qrcode_width = $(window).width()*0.6 > 256 ? 256 : $(window).width()*0.6;
            layer.open({
                title:['自提二维码','background-color:#8DCE16;color:#fff;'],
                content:'生成的二维码仅限提供给商家店铺员工扫描验证消费使用！<br/><br/><div id="qrcode"></div>',
                success:function(){
                    $('#qrcode').qrcode({
                        width:qrcode_width,
                        height:qrcode_width,
                        text:"{pigcms{$order_details.orderid|default=0}"
                    });
                }
            });
            $('.layermbox0 .layermchild').css({width:qrcode_width+30+'px','max-width':qrcode_width+30+'px'});
        });
    });
    </script>
	<div class="pageSliderHide"></div>
</html>
<style>
	.lottery_before{
		color:#999;
		border-color:#999;
	}
	.lottery{
		color:red;
		border-color:red;
	}
	.float-open{
		
		right: 0px;
		height: 60;

		padding: 4px 4px 4px 6px;
		width: 60px;
		z-index: 99;
		top: 50%;
		position: fixed;
		
	}
</style>
<script>
$(document).ready(function(){
	$('.consumes ul li').click(function(){
        if ($(this).hasClass('finish_order')) {
            var postUrl = $(this).data('url');
            layer.open({
                content: '您确定已经收到货物了？'
                ,btn: ['确定收货', '取消']
                ,yes: function(index){
                    $.post(postUrl, function(res) {
                        layer.close(index);
                        if (res.errcode == 1) {
                            layer.open({
                                content: res.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                              });
                        } else {
                            location.reload();
                            layer.close(index);
                        }
                    }, 'json');
                }
            });
        } else if ($(this).hasClass('finish_take'))  {
            var postUrl = $(this).data('url');
            layer.open({
                content: '您确定已经取到货物了？'
                ,btn: ['确定取货', '取消']
                ,yes: function(index){
                    $.post(postUrl, function(res) {
                        layer.close(index);
                        if (res.errcode == 1) {
                            layer.open({
                                content: res.msg
                                ,skin: 'msg'
                                ,time: 2 //2秒后自动关闭
                            });
                        } else {
                            location.reload();
                            layer.close(index);
                        }
                    }, 'json');
                }
            });
        } else {
            var url = $(this).data('url');
			if(url){
				location.href = url;
			}
        }
    });
	$('#send_friend').click(function(){
		$('.coupon_my').show();
	})
	
	if(motify.checkWeixinApp()){
	
		$('.t_remind').remove()  
		$('#goBackUrl').hide()  
		$(".public").each(function(){
			$(this).css("top",0)
		});
		$(".defrayal").each(function(){
			$(this).css("top",44);
		});
		$(".h105").each(function(){
			$(this).css("height",105 );
		})
	}
	
	$('#float-open').click(function(){
		$.post('{pigcms{$config.site_url}{pigcms{:U('My/ajax_share_friend')}', {order_type:'shop',order_id: {pigcms{$order_details['order_id']}}, function(data, textStatus, xhr) {
			if(data.status){
					window.location.href=data.url;
			}else{
					layer.open({content:data.info,btn: ['确定'],
					 yes: function(index){
						  location.reload();
						  layer.close(index); 
					}});
			}
		},'json');
	});
	
    $(document).on('click','.phone',function(event){
        if($(this).attr('data-phone')){
            var tmpPhone = $(this).attr('data-phone').split(' ');
            var msg_dom = '<div class="msg-bg"></div>';
            msg_dom+= '<div id="msg" class="msg-doc msg-option">';
            msg_dom+= '<div class="msg-bd">'+($(this).data('phonetitle') ? $(this).data('phonetitle') : '拨打电话')+'</div>';
            for(var i in tmpPhone){
                msg_dom+= '<div class="msg-option-btns"><a class="btn msg-btn" href="tel:'+tmpPhone[i]+'">'+(tmpPhone.length == 1 && $(this).data('phonetip') ? $(this).data('phonetip') : tmpPhone[i])+'</a></div>';
            }
            msg_dom+= '     <button class="btn msg-btn-cancel" type="button">取消</button>';
            msg_dom+= '</div>'; 
            $('body').append(msg_dom);
        }
        event.stopPropagation();
    });
    $(document).on('click','.msg-btn-cancel,.msg-bg',function(){
        $('.msg-doc,.msg-bg').remove();
    });
	
	if(typeof(wxSdkLoad) != "undefined"){
		wx.ready(function(){
			if(window.__wxjs_environment === 'miniprogram'){
				go_shop_bind(1);
			}else{
				go_shop_bind(1);
			}
		});
	}else{
		go_shop_bind(1);
	}
});

function go_shop_bind(type){
	if(type == 1){
		$('#go_shop').click(function(){
			if($(this).data('is_mult_class') == '1'){
				location.href = "{pigcms{:U('Mall/store')}&store_id={pigcms{$store['store_id']}&show_own=1";
			}else{
				location.href = "{pigcms{:U('Mall/index')}#shop-{pigcms{$store['store_id']}";
			}
		});
	}else{
		$('#go_shop').click(function(){
			wx.miniProgram.navigateTo({url: "/pages/shop_new/shopDetail/shopDetail?store_id={pigcms{$store['store_id']}"});
		});
	}
}

$(".public").each(function(){
    $(this).css("top",$(".t_remind").height())
});
$(".defrayal").each(function(){
    $(this).css("top",$(".t_remind").height()+44);
});
$(".h105").each(function(){
    $(this).css("height",105 + $(".t_remind").height());
})
var version = 0;
if(/(pigcmso2oreallifeapp)/.test(navigator.userAgent.toLowerCase()) || (/(pigcmso2olifeapp)/.test(navigator.userAgent.toLowerCase()) && /(life_app)/.test(navigator.userAgent.toLowerCase()))){
	var reg = /versioncode=(\d+),/;
	var arr = reg.exec(navigator.userAgent.toLowerCase());
	if(arr == null){
		
	}else{
        version = parseInt(arr[1]);
		if(version >= 50){
			if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
				$('#goBackUrl').click(function(){
					if(version >= 100){
                        iosCommon.iosFunction('closewebview/2', {name:'closewebview', type: '2'});
					}else{
						$('body').append('<iframe src="pigcmso2o://webViewGoBack" style="display:none;"></iframe>');
					}
					return false;
				});
			}else{
				if(version >= 100){
					window.lifepasslogin.hideWebViewHeader(false);
				}
				$('#goBackUrl').click(function(){
					if(version >= 100){
                        window.pigcmspackapp.closewebview(2);
					}else{
						window.lifepasslogin.webViewGoBack();
					}
					return false;
				});
			}
		}
	}
}

function appbackmonitor(){
	if(/(iphone|ipad|ipod)/.test(navigator.userAgent.toLowerCase())){
        if(version >= 100){
            iosCommon.iosFunction('closewebview/2', {name:'closewebview', type: '2'});
        }else{
            $('body').append('<iframe src="pigcmso2o://webViewGoBack" style="display:none;"></iframe>');
        }
	}else{
        if(version >= 100){
            window.lifepasslogin.webViewGoBack();
            // window.pigcmspackapp.closewebview(2);
        }else{
            window.lifepasslogin.webViewGoBack();
        }
        return false;
	}
}
$('.order-pay').click(function(){
	var order_id = $(this).data('order_id');
    var freight_charge = "{pigcms{$order_details['freight_charge']}"
    if (freight_charge>0) {
        if(typeof wx !=='undefined' && window.__wxjs_environment === 'miniprogram'){
            wx.miniProgram.navigateTo({url:'/pages/pay/index?order_id='+ order_id +'&type=shop'});
        }else{
            location.href = "{pigcms{:U('Pay/check',array('type'=>'integral_mall'))}&order_id="+order_id;
        }
    }else{
        go_pay(order_id)
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
                content: '请先登录'
                , btn: ['去登录']
                , yes: function (index) {
                    location.href = data.url;
                }
            });
        } else if (data.errcode > 0) {
            layer.open({
                content: data.msg
                , btn: ['确定']
                , yes: function (index) {
                    layer.close(index);
                }
            });
        } else{

            // 可以兑换
            layer.open({
                content: 
                '<div>{pigcms{:L_("剩余商家积分")}：<span class="money_info">' + data.info.card_score + '</span></div>' +
                '<div>{pigcms{:L_("当前兑换所需积分")}：<span class="money_info" style="color: #ff4d00">' + data.info.total_integral + '</span></div>'
                , btn: ['立即兑换', '取消']
                , yes: function (index) {
                    $.post(go_pay_url, {'order_id': order_id}, function (data) {
                        console.log('支付信息gopay-》  ', data)
                        if (data.errcode == 2) {
                            layer.open({
                                content: '请先登录'
                                , btn: ['去登录']
                                , yes: function (index) {
                                    location.href = data.url;
                                }
                            });
                        } else if (data.errcode > 0) {
                            layer.open({
                                content: data.msg
                                , btn: ['确定']
                                , yes: function (index) {
                                    layer.close(index);
                                }
                            });
                        } else{
                            // 兑换成功
                            layer.open({
                                content: data.msg
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
</script>