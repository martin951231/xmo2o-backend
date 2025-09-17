<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
	<title>{pigcms{:L_('取消订单')}</title>
    <meta name="viewport" content="initial-scale=1, width=device-width, maximum-scale=1, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name='apple-touch-fullscreen' content='yes'>
	<meta name="apple-mobile-web-app-status-bar-style" content="black">
	<meta name="format-detection" content="telephone=no">
	<meta name="format-detection" content="address=no">
    <link href="{pigcms{$static_path}css/eve.7c92a906.css" rel="stylesheet"/>
    <link href="{pigcms{$static_path}css/wap_pay_check.css" rel="stylesheet"/>
	<style>
    .btn-wrapper {
        margin: .28rem .2rem;
    }
    .hotel-price {
        color: #ff8c00;
        font-size: 12px;
        display: block;
    }
    .dealcard .line-right {
        display: none;
    }
    .agreement li {
        display: inline-block;
        width: 50%;
        box-sizing: border-box;
        color: #666;
    }

    .agreement li:nth-child(2n) {
        padding-left: .14rem;
    }

    .agreement li:nth-child(1n) {
        padding-right: .14rem;
    }

    .agreement ul.agree li {
        height: .32rem;
        line-height: .32rem;
    }

    .agreement ul.btn-line li {
        vertical-align: middle;
        margin-top: .06rem;
        margin-bottom: 0;
    }

    .agreement .text-icon {
        margin-right: .14rem;
        vertical-align: top;
        height: 100%;
    }

    .agreement .agree .text-icon {
        font-size: .4rem;
        margin-right: .2rem;
    }


    #deal-details .detail-title {
        background-color: #F8F9FA;
        padding: .2rem;
        font-size: .3rem;
        color: #000;
        border-bottom: 1px solid #ccc;
    }

    #deal-details .detail-title p {
        text-align: center;
    }

    #deal-details .detail-group {
        font-size: .3rem;
        display: -webkit-box;
        display: -ms-flexbox;
    }

    .detail-group .left {
        -webkit-box-flex: 1;
        -ms-flex: 1;
        display: block;
        padding: .28rem 0;
        padding-right: .2rem;
    }

    .detail-group .right {
        display: -webkit-box;
        display: -ms-flexbox;
        -webkit-box-align: center;
        -ms-box-align: center;
        width: 1.2rem;
        padding: .28rem .2rem;
        border-left: 1px solid #ccc;
    }

    .detail-group .middle {
        display: -webkit-box;
        display: -ms-flexbox;
        -webkit-box-align: center;
        -ms-box-align: center;
        width: 1.7rem;
        padding: .28rem .2rem;
        border-left: 1px solid #ccc;
    }

    ul.ul {
        list-style-type: initial;
        padding-left: .4rem;
        margin: .2rem 0;
    }

    ul.ul li {
        font-size: .3rem;
        margin: .1rem 0;
        line-height: 1.5;
    }
    .coupons small{
        float: right;
        font-size: .28rem;
    }
    strong {
        color: #FDB338;
    }
    .coupons-code {
        color: #666;
        text-indent: .2rem;
    }
    .voice-info {
        font-size: .3rem;
        color: #eb8706;
    }
</style>
</head>
<body>
        <div id="tips" class="tips"></div>
        <div class="wrapper-list">
			<h4>{pigcms{$now_order.s_name}</h4>
			<dl class="list">
			    <dd>
			        <dl>
			            <dd class="kv-line-r dd-padding">
			                <h6>{pigcms{:L_('订单号')}：</h6><p><strong class="highlight-price">{pigcms{$now_order.real_orderid}</strong></p>
			            </dd>
						<dd class="kv-line-r dd-padding">
							<h6>{pigcms{:L_('购买数量')}：</h6><p>{pigcms{$now_order.num}</p>
						</dd>
						<dd class="kv-line-r dd-padding">
							<h6>{pigcms{:L_('商品总价')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.goods_price|floatval}</p>
						</dd>
						<dd class="kv-line-r dd-padding">
							<h6>{pigcms{:L_('配送费')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.freight_charge|floatval}</p>
						</dd>
						<dd class="kv-line-r dd-padding">
							<h6>{pigcms{:L_('订单总价')}：</h6><p>
                                <if condition="$now_order['book_id'] gt 0">
                                    {pigcms{$config['Currency_symbol']}{pigcms{$now_order['bargin_price']+$now_order['rest_price']|floatval}
                                    <else/>
                                    {pigcms{$config['Currency_symbol']}{pigcms{$now_order.total_price|floatval}
                                </if>
                            </p>
						</dd>
						<if condition="$now_order['balance_reduce'] gt 0">
							<dd class="kv-line-r dd-padding">
								<h6>{pigcms{:L_('平台优惠')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.balance_reduce|floatval}</p>
							</dd>
						</if>
						<if condition="$now_order['merchant_reduce'] gt 0">
							<dd class="kv-line-r dd-padding">
								<h6>{pigcms{:L_('商家优惠')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.merchant_reduce|floatval}</p>
							</dd>
						</if>
						<dd class="kv-line-r dd-padding">
							<h6>{pigcms{:L_('实收总额')}：</h6><p>
                                <if condition="$now_order['book_id'] gt 0">
                                    {pigcms{$config['Currency_symbol']}{pigcms{$now_order['bargin_price']+$now_order['rest_price']|floatval}
                                    <else/>
                                    {pigcms{$config['Currency_symbol']}{pigcms{$now_order.price|floatval}
                                </if>
                            </p>
						</dd>
						
			        </dl>
			    </dd>
			</dl>
			<dl class="list">
			    <dd>
			        <dl>
						<if condition="$now_order['coupon_id']">
							<dd>
								<a class="react" href="javascript:;">
									<div class="more more-weak">
										<h6>{pigcms{:L_('使用平台优惠券')}：</h6>
										<span class="more-after">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.coupon_price|floatval}</span>
									</div>
								</a>
							</dd>
						</if>
						<if condition="$now_order['card_id']">
							<dd>
								<a class="react" href="javascript:;">
									<div class="more more-weak">
										<h6>{pigcms{:L_('使用商家优惠券')}：</h6>
										<span class="more-after">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.card_price|floatval}</span>
									</div>
								</a>
							</dd>
						</if>
                        <if condition="$now_order['use_ecard_price'] neq '0.00'">
                            <dd class="kv-line-r dd-padding">
                                <h6>使用{pigcms{$config.ecard_alias_name}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.use_ecard_price|floatval}</p>
                            </dd>
                        </if>
						<if condition="$now_order['balance_pay'] neq '0.00'">
							<dd class="kv-line-r dd-padding">
                                    <h6>{pigcms{:L_('使用平台余额')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.balance_pay|floatval}</p>
							</dd>
						</if>
						<if condition="$now_order['merchant_balance'] neq '0.00' OR $now_order.card_give_money neq '0.00'">
							<dd class="kv-line-r dd-padding">
								<h6>{pigcms{:L_('使用商家会员卡余额')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order['merchant_balance']+$now_order['card_give_money']|floatval}</p>
							</dd>
						</if>
                        <if condition="$now_order['score_used_count'] neq '0'">
                            <dd class="kv-line-r dd-padding">
                                <h6>使用{pigcms{$config.score_name}：</h6><p>{pigcms{$now_order.score_used_count}</p>
                            </dd>
                        </if>
                        <if condition="$now_order['score_deducte'] neq '0.00'">
                            <dd class="kv-line-r dd-padding">
                                <h6>{pigcms{:L_('使用X1抵扣金额', ['X1'=>$config['score_name']])}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.score_deducte|floatval}</p>
                            </dd>
                        </if>
                        <if condition="$now_order['service_charge'] neq '0.00'">
                            <dd class="kv-line-r dd-padding">
                                <h6>{pigcms{:L_('支付手续费')}：</h6><p>{pigcms{$config['Currency_symbol']}{pigcms{$now_order.service_charge|floatval}</p>
                            </dd>
                        </if>
						<if condition="$now_order['payment_money'] neq '0.00'">
							<dd class="kv-line-r dd-padding">
								<h6>{pigcms{:L_('在线支付金额')}：</h6>
								<p>
									<strong class="highlight-price">
										{pigcms{$config['Currency_symbol']}<span class="need-pay">{pigcms{$now_order.payment_money}</span>
									</strong>
								</p>
							</dd>
							<dd class="kv-line-r dd-padding">
								<h6>{pigcms{:L_('在线支付方式')}：</h6>
								<p>{pigcms{$now_order.pay_type_txt}</p>
							</dd>
						</if>
			        </dl>
			    </dd>
			</dl>
			<div class="btn-wrapper" style="line-height:1.5;color:#666;">
                <if condition="$config.is_refund_balance == 1">
                    {pigcms{:L_('全部实际支付金额将退款到您的平台余额中，敬请查看核对!')}
                    <else />
                    {pigcms{:L_('银行卡退款的到账时间一般为几个工作日，以银行的实际情况为准。')}
                </if>
            </div>
			<div class="btn-wrapper">
                <eq name="_GET['apply']" value="1">
                    <span id="cancel" class="btn btn-larger btn-block btn-strong" style="margin-bottom:15px;">{pigcms{:L_('申请取消')}</span>
                <else/>
                    <span id="cancel" class="btn btn-larger btn-block btn-strong" style="margin-bottom:15px;">{pigcms{:L_('确定取消')}</span>
                </eq>
				
			</div>
		</div>
    	<script src="{pigcms{:C('JQUERY_FILE')}"></script>
		<script src="{pigcms{$static_path}js/common_wap.js"></script>	
		<script>
			$(function(){
				
				$('#cancel').click(function(){
					var cancel_ = true;
					if(cancel_){
						$(this).css('background-color','#ccc')
						window.location.href='{pigcms{:U('My/shop_order_check_refund',array('mer_id' => $mer_id, 'order_id' => $now_order['order_id'], 'store_id' => $now_order['store_id'],'apply'=>$_GET['apply']))}';
						cancel_ = false;
					}
				});
				
			})
		</script>
		<include file="Public:footer"/>
{pigcms{$hideScript}
</body>
</html>