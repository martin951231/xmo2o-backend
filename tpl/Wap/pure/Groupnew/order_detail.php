<?php if(!defined('PigCms_VERSION')){ exit('deny access!');} ?>
<!DOCTYPE html>
<html lang="zh-CN">
	<head>
		<meta charset="utf-8" />
		<title>订单详情</title>
		<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
		<meta name="apple-mobile-web-app-capable" content="yes"/>
		<meta name='apple-touch-fullscreen' content='yes'/>
		<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
		<meta name="format-detection" content="telephone=no"/>
		<meta name="format-detection" content="address=no"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/search.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/order_detail.css?t={pigcms{:time()}"/>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView class="page y two_category" id="two_category_page">
			<div class="container">
				<div class="order-detail">
					<div class="store-name link newWin" data-url="{pigcms{:U('merchant',array('mer_id'=>$now_merchant['mer_id'],'page_num'=>$page_num))}">
						<img src="{pigcms{$now_merchant.img}"/><span>{pigcms{$now_merchant.name}</span>
					</div>
					<div class="flex flex-justify goods-detail link newWin" data-url="{pigcms{$detail_url}">
						<img class="flex-shink0" src="{pigcms{:thumb_img($now_order['list_pic'],240,240,'fill')}"/>
						<div class="flex flex-zTopBottom flex-justify desc-box ">
							<div class="title">{pigcms{$now_group.name} <if condition="$now_group['s_name'] AND $now_group['name'] " >|</if> {pigcms{$now_group.s_name}</div>
                            <if condition="$now_order['specifications_info'] && $now_order['specifications_info']['specifications_name']">
                                <div class="small-gray">款式：{pigcms{$now_order['specifications_info']['specifications_name']}</div>
                                <else/>
                                <p class="small-gray">{pigcms{$now_group.intro}</p>
                            </if>
						</div>
						<div class="flex-shink0 flex flex-zTopBottom flex-justify num-box">
							<div>
								<div class="now-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_order.price}</div>
                                <if condition="$now_order['specifications_info']">
                                    <div class="small-gray old-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['specifications_info']['old_price']}</div>
                                <elseif condition="$now_group['old_price'] gt $now_group['price'] "/>
                                    <div class="small-gray old-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.old_price}</div>
                                </if>
							</div>
							<div class="small-gray">×{pigcms{$now_order.num}</div>
						</div>
					</div>
                    <div class="other-info-box">
                    
                        <if condition="$now_order['status'] neq 4">
                            <div class="flex flex-justify line">
                                <span class="left">{pigcms{$config.group_alias_name}有效期</span>
                                <span class="right">{pigcms{$now_group.deadline_time|date='Y-m-d H:i:s',###}</span>
                            </div>
                        </if>
                        <if condition="$now_group.no_refund eq 0 AND $now_group.before_effective_time gt 0 AND $now_group.tuan_type eq 0">
                            <div class="flex flex-justify line">
                                <span class="left">{pigcms{$config.group_alias_name}退款有效期</span>
                                <span class="right">{pigcms{$config.group_alias_name}有效期{pigcms{$now_group.before_effective_time}<if condition="$now_group['effective_time_type'] eq 1">天<else/>小时</if>前可退</span>
                            </div>
                        </if>
                    </div>

                    <if condition="$now_order['tuan_type'] == 2 && $now_order['paid'] && $now_order['status'] lt 3">
                        <div class="other-info-box">
                        <if condition="$now_order['is_pick_in_store'] == 0">
                                <php>if($now_order['contact_name']){</php>
                                <div class="flex flex-justify line">
                                    <span class="left">收货人</span>
                                    <span class="right">{pigcms{$now_order.contact_name}</span>
                                </div>
                                <php>}</php>
                                <php>if($now_order['adress']){</php>
                                <div class="flex flex-justify line">
                                    <span class="left">地址</span>
                                    <span class="right">{pigcms{$now_order.adress}</span>
                                </div>
                                <php>}</php>
                                <div class="flex flex-justify line">
                                    <span class="left">联系电话</span>
                                    <span class="right">{pigcms{$now_order.phone}</span>
                                </div>
                                <php>if($now_order['zipcode']){</php>
                                <div class="flex flex-justify line">
                                    <span class="left">邮编</span>
                                    <span class="right">{pigcms{$now_order.zipcode}</span>
                                </div>
                                <php>}</php>
                                <php>if($now_order['express_type']){</php>
                                    <div class="flex flex-justify line">
                                        <span class="left">快递公司</span>
                                        <span class="right">{pigcms{$now_order.express_info.name}</span>
                                    </div>
                                    <div class="flex flex-justify line">
                                        <span class="left">快递单号</span>
                                        <span class="right">
											{pigcms{$now_order.express_id}
											<small>
											<if condition="$config['kdniao_appkey']">
												<a href="{pigcms{:U('Mall/express_new')}&type={pigcms{$now_order.express_info.code}&postid={pigcms{$now_order.express_id}" target="_blank" style="color:#1B9C46;">查看物流信息</a>
											<else/>
												<a href="http://m.kuaidi100.com/index_all.html?type={pigcms{$now_order.express_info.code}&postid={pigcms{$now_order.express_id}&callbackurl=<?php echo 'http://'.urlencode($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);?>" target="_blank" style="color:#1B9C46;">查看物流信息</a>
											</if>
											</small>
										</span>
                                    </div>
                                <php>}</php>
                            <else/>

                            <div class="flex flex-justify line">
                                <span class="left">地址</span>
                                <span class="right">{pigcms{$now_order.adress} <a id="pickUrl" style="color:blue" href="{pigcms{:U('My/map')}&origin_lat={pigcms{$lat}&origin_long={pigcms{$lng}&end_lat={pigcms{$now_order.pick_lat}&end_long={pigcms{$now_order.pick_lng}&drive=1">自提导航>></a></span>
                            </div>
                            <div class="flex flex-justify line">
                                <span class="left">电话</span>
                                <span class="right">{pigcms{$now_order.phone}</span>
                            </div>
                        </if>
                        </div>
                    </if>
					<div class="other-info-box">
						<div class="flex flex-justify line">
							<span class="left">交易单号</span>
							<span class="right">{pigcms{$now_order.real_orderid}</span>
						</div>
						<div class="flex flex-justify line">
							<span class="left">下单时间</span>
							<span class="right"><span>{pigcms{$now_order.add_time|date='Y-m-d',###}</span>&nbsp;<span>{pigcms{$now_order.add_time|date='H:i',###}</span></span>
						</div>
                        <div class="flex flex-justify line">
                            <span class="left">联系电话</span>
                            <span class="right">{pigcms{$now_order.phone}</span>
                        </div>
                        <!--新增酒店展示-->
                        <if condition="$trade_hotel_info">
                            <div class="flex flex-justify line">
                                <span class="left">入住时间</span>
                                <span class="right">{pigcms{$trade_hotel_info.dep_time_txt}</span>
                            </div>
                            <div class="flex flex-justify line">
                                <span class="left">离开时间</span>
                                <span class="right">{pigcms{$trade_hotel_info.end_time_txt}</span>
                            </div>
                            <div class="flex flex-justify line">
                                <span class="left">房间类型</span>
                                <span class="right">{pigcms{$trade_hotel_info.cat_name}</span>
                            </div>
                        </if>
                        
                        <if condition="$now_order.delivery_comment neq ''">
                            <div class="flex flex-justify line">
                                <span class="left">备注</span>
                                <span class="right">{pigcms{$now_order.delivery_comment}</span>
                            </div>
                        </if>
                        <if condition="$now_order.express_fee gt 0">
                            <div class="flex flex-justify line">
                                <span class="left">配送费</span>
                                <span class="right">{pigcms{$now_order.express_fee|floatval}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order['card_discount'] gt 0 && $now_order['card_discount'] lt 10">
                            <div class="flex flex-justify line">
                                <span class="left">享受会员卡折扣</span>
                                <span class="right">{pigcms{$now_order['card_discount']}折</span>
                            </div>
                        </if>
                        <if condition="$now_order['merchant_balance'] neq 0 OR $now_order['card_give_money'] neq 0">
                            <div class="flex flex-justify line">
                                <span class="left">商家会员卡余额抵扣</span>
                                <span class="right">{pigcms{$now_order['merchant_balance']+$now_order['card_give_money']}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order.balance_pay neq 0">
                            <div class="flex flex-justify line">
                                <span class="left">平台余额抵扣</span>
                                <span class="right">{pigcms{$now_order.balance_pay}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order.card_price neq 0">
                            <div class="flex flex-justify line">
                                <span class="left">商家优惠券</span>
                                <span class="right">{pigcms{$now_order.card_price}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order.coupon_price neq 0">
                            <div class="flex flex-justify line">
                                <span class="left">平台优惠券</span>
                                <span class="right">{pigcms{$now_order.coupon_price} {pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order.wx_cheap gt 0">
                            <div class="flex flex-justify line">
                                <span class="left">微信优惠</span>
                                <span class="right">{pigcms{$now_order.wx_cheap}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order['score_deducte'] gt 0">
                            <div class="flex flex-justify line">
                                <span class="left">{pigcms{$config.score_name}抵扣</span>
                                <span class="right">{pigcms{$now_order.score_deducte}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order['payment_money'] neq 0">
                            <div class="flex flex-justify line">
                                <span class="left">在线支付</span>
                                <span class="right">{pigcms{$now_order.payment_money}{pigcms{$config['Currency_txt']}</span>
                            </div>
                        </if>
                        <if condition="$now_order['third_id'] eq '0' AND $now_order['pay_type'] eq 'offline'">
                            <div class="flex flex-justify line">
                                <span class="left">线下需向商家付金额</span>
                                <span class="right"><font color="red">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['total_money']*$now_order['card_discount']/10-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price']}{pigcms{$config['Currency_txt']}</font></span>
                            </div>
                            <elseif condition="($now_order['pay_type']=='offline' AND $now_order['paid'] AND !empty($now_order['third_id'])) OR ($now_order['pay_type']!='offline' AND $now_order['paid'])"/>
                            <div class="flex flex-justify line">
                                <span class="left">付款方式</span>
                                <span class="right">{pigcms{$now_order.pay_type_txt}</span>
                            </div>
                            <div class="flex flex-justify line">
                                <span class="left">支付时间</span>
                                <span class="right">{pigcms{$now_order.pay_time|date='Y-m-d H:i',###}</span>
                            </div>
                        </if>
                        <if condition="!empty($now_order['use_time'])">
                            <div class="flex flex-justify line">
                                <span class="left">消费时间</span>
                                <span class="right">{pigcms{$now_order.use_time|date='Y-m-d H:i',###}</span>
                            </div>
                        </if>
                        <if condition="$now_order['status'] eq 3 OR $now_order['status'] eq 6">
                            <div class="flex flex-justify line">
                                <span class="left">已退款总金额</span>
                                <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$now_order['refund_total']}</font>{pigcms{$config['Currency_txt']}<if condition="$now_order['refund_fee'] gt 0">(手续费：{pigcms{$now_order['refund_fee']})</if></span>
                            </div>

                            <if condition="$pass_info && $pass_info['total_pass_num'] gt 0">

                                <div class="flex flex-justify line">
                                    <span class="left">总核销码数</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$pass_info['total_pass_num']}</font></span>
                                    <span class="left">未使用核销码数</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$pass_info['unconsume_pass_num']}</font></span>
                                </div>
                            </if>

                            <if condition="$refund_score && $refund_score gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">已退{pigcms{$config.score_name}数</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$refund_score}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_score_fee && $refund_score_fee gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">{pigcms{$config.score_name}手续费</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$refund_score_fee}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_card_give_money && $refund_card_give_money gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">已退商户赠送余额</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$refund_card_give_money}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_card_give_fee && $refund_card_give_fee gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">商户赠送余额手续费</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$refund_card_give_fee}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_merchant_balance && $refund_merchant_balance gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">已退商户余额</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$refund_merchant_balance}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_merchant_balance_fee && $refund_merchant_balance_fee gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">商户余额手续费</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$refund_merchant_balance_fee}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_balance_pay && $refund_balance_pay gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">已退平台余额</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$refund_balance_pay}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_balance_pay_fee && $refund_balance_pay_fee gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">平台余额手续费</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$refund_balance_pay_fee}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_payment_money && $refund_payment_money gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">已退线上支付金额</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$refund_payment_money}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_payment_money_fee && $refund_payment_money_fee gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">线上支付金额手续费</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$refund_payment_money_fee}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_offline_money && $refund_offline_money gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">应退线下支付金额</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$refund_offline_money}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                            <if condition="$refund_offline_money_fee && $refund_offline_money_fee gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">线下支付金额手续费</span>
                                    <span class="right"><font style="font-weight: bold;color: red;">{pigcms{$refund_offline_money_fee}{pigcms{$config['Currency_txt']}</font></span>
                                </div>
                            </if>
                        <elseif condition="$now_order['status'] eq 4">
                            <div class="flex flex-justify line">
                                <span class="left">订单未支付已取消</span>
                            </div>
                        </if>
					</div>
                    <if condition="$is_gift neq 1">
                    <if condition="$now_order['status'] neq 3 && $now_order['status'] neq 6">
                        <php>if($now_order['is_share_group']==2||(($now_group['open_num']<=$now_group['sale_count']&&$now_order['open_num']!=0)||$now_group['open_num']==0)||($now_group['open_now_num']<=$now_group['sale_count']&&$now_group['open_now_num']!=0)){</php>

                            <php>if($now_order['pass_array']){</php>
                                <div class="other-info-box">
                                    <volist name="pass_array" id="vv">
                                        <div class="flex flex-justify line">
                                            <span class="left">消费密码{pigcms{$key+1}</span>
                                            <span class="right" onclick="location.href='{pigcms{:U('eticket', ['order_id'=>$now_order['order_id'],'group_pass'=>$vv['group_pass']])}&key_id={pigcms{$key}&path=coupon'">
                                                <php>if($vv['status'] == 2){</php><font color="red">无法查看</font><php>}else{</php>{pigcms{$vv.group_pass} <font color="red">二维码></font> <if condition="$give_submit_show eq 1 && $now_group['is_give'] eq 1"><button class="coupon-gift-btn" style='color:{pigcms{$config.mobile_head_color};border-color:{pigcms{$config.mobile_head_color};'>转赠</button></if> <php>}</php>
                                            </span>
                                        </div>
                                    </volist>
                                </div>
                            <php>}else if($now_order['group_pass_txt']){</php>
                                <div class="other-info-box">
                                    <div class="flex flex-justify line">
                                        <span class="left">消费密码</span>
                                        <span class="right" onclick="location.href='{pigcms{:U('eticket', ['order_id'=>$now_order['order_id'],'group_pass'=>$now_order['group_pass']])}&key_id={pigcms{$key}&path=coupon'">{pigcms{$now_order.group_pass_txt} <font color="red">二维码></font><if condition="$give_submit_show eq 1 && $now_group['is_give'] eq 1"><button class="coupon-gift-btn" style='color:{pigcms{$config.mobile_head_color};border-color:{pigcms{$config.mobile_head_color};'>转赠</button></if></span>
                                    </div>
                                </div>
                            <php>}</php>
                        <php>}</php>
                    </if>
                    </if>
                    <if condition="$now_order['tuan_type'] eq 3">
                        <div class="other-info-box">
                            <div class="flex flex-justify line">
                                <span class="left">温馨提示：可以复制兑换码到第三方平台兑换</span>
                                <span class="right" style="color: red" onclick="exchange_rules('{pigcms{$now_group.exchange_rules}')">详情</span>
                            </div>
                        </div>
                        <if condition="$redemption_code_list">
                            <div class="other-info-box">
                                <volist name="redemption_code_list" id="vv">
                                    <div class="flex flex-justify line">
                                        <span class="left">兑换码{pigcms{$key+1}</span>
                                        <span class="right">{pigcms{$vv}</span>
                                        <span class="right" onclick="copyElementContent('{pigcms{$vv}')">复制</span>
                                    </div>
                                </volist>
                            </div>
                        </if>
                    </if>
                    <div class="other-info-box">
                        <if condition="$write_list">
                            <volist name="write_list" id="vos">
                                <div class="flex flex-justify line">
                                    <span class="left">{pigcms{$vos.name}</span>
                                    <span class="right">{pigcms{$vos.value}</span>
                                </div>
                            </volist>
                        </if>
                    </div>
				</div>
			</div>
		</scrollView>

        <if condition="$now_order['status'] eq 4 OR  $now_order['status'] eq 6">
            <div class="handleBtn canceled">已取消</div>

            <elseif condition="$now_order['status'] eq 3 OR $now_order['status'] eq 4" />
<!--                <div class="other-info-box">-->
<!--                    <div class="flex flex-justify line">-->
<!--                        <span class="left">订单状态</span>-->
<!--                        <span class="right">订单已取消</span>-->
<!--                    </div>-->
<!--                </div>-->
            <elseif condition="$now_order['status'] eq 2" />
<!--                <div class="other-info-box">-->
<!--                    <div class="flex flex-justify line">-->
<!--                        <span class="left">订单状态</span>-->
<!--                        <span class="right">订单已完成</span>-->
<!--                    </div>-->
<!--                </div>-->
            <elseif condition="empty($now_order['paid'])" />
                <div class="handleBtn order-pay" data-order_id="{pigcms{$now_order['order_id']}">付款</div>
            <elseif condition="$now_order['status'] eq 0 AND $now_order.is_pick_in_store eq 0 AND $now_group.tuan_type eq 2 AND $now_order.express_id neq ''"/>
                <div class="handleBtn recive_confirm">确认取货</div>
            <elseif condition="($now_order['status'] eq 0) AND (empty($trade_hotel_info) OR ($trade_hotel_info['refund']) )"/>
            <div class="btn-wrapper">
                <php> if($config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_refund'] == 0 && $now_order['is_own'] == 2 && $now_order['pay_type'] == 'weixin'){</php>
                    <div class="other-info-box">
                        <div class="flex flex-justify line">
                            <span class="left">退款状况</span>
                            <span class="right">该订单不能退款，请联系商家 【{pigcms{$now_merchant.name}】</span>
                        </div>
                    </div>
                <php>}else if($now_group['no_refund']==1){</php>
                    <div class="other-info-box">
                        <div class="flex flex-justify line">
                            <span class="left">退款状况</span>
                            <span class="right">该订单不能退款，请联系商家 【{pigcms{$now_merchant.name}】</span>
                        </div>
                    </div>
                <php>}else{</php>
                    <if condition="$canCancel">
                        <div class="handleBtn link" data-url="{pigcms{:U('My/group_order_refund',array('order_id'=>$now_order['order_id']))}">取消订单</div>
                    </if>
                <php>}</php>
            </div>
            <elseif condition="$now_order['status'] eq 1"/>
            <div class="handleBtn link" data-url="{pigcms{:U('My/group_feedback',array('order_id'=>$now_order['order_id']))}">订单评价</div>
        </if>
		
		<div id="hideLoad" class="hide"></div>
		
		<script type="text/javascript">
			window.shareData = {
				"moduleName":"Home",
				"moduleID":"0",
				"imgUrl": "<if condition="$config['wechat_share_img']">{pigcms{$config.wechat_share_img}<else/>{pigcms{$config.site_logo}</if>",
				"sendFriendLink": "{pigcms{$config.site_url}{pigcms{:U('Groupnew/index')}",
				"tTitle": "{pigcms{$config.group_index_name|default='同城优选'}",
				"tContent": "{pigcms{$config.site_name}"
			};
            var group_recive_confirm_url = '{pigcms{:U('My/group_recive_confirm',array('order_id'=>$now_order['order_id']))}';
            //var pay_check = '{pigcms{:U('Pay/check',array('type'=>'group','order_id'=>$now_order['order_id']))}';
            var pay_check =  "{pigcms{:get_base_url('pages/pay/check?order_type=group')}"+"&order_id="+"{pigcms{$now_order['order_id']}";
		</script>
		{pigcms{$shareScript}
		
		<script type="text/javascript" src="{pigcms{:C('JQUERY_FILE_190')}" charset="utf-8"></script>
		
		<!-- minirefresh -->
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css"/>
		<script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

		<!-- layer -->
		<script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?210" charset="utf-8"></script>
		
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}groupnew/js/order_detail.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript" src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" charset="utf-8"></script>
		<script type="text/javascript">
			var origin_lat = '{pigcms{$lat}';
			var origin_long= '{pigcms{$lng}';
			if (!origin_lat || !origin_long) {
				getUserLocation({useHistory:false, reportLocation:false, okFunction:'setUrl'});
			}
			function setUrl (userLonglat, userLong,userLat ) {
				$('#pickUrl').attr("href","{pigcms{:U('My/map')}&origin_lat="+ userLat +"&origin_long="+userLong+"&end_lat={pigcms{$now_order.pick_lat}&end_long={pigcms{$now_order.pick_lng}&drive=1")
			}

            window.addEventListener('pageshow', function(event) {
                if (event.persisted || (performance && performance.navigation.type == performance.navigation.TYPE_BACK_FORWARD)) {
                    window.location.reload();
                }
            });
            // 复制功能实现
            function copyElementContent(text) {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    // 使用现代的 Clipboard API
                    navigator.clipboard.writeText(text).then(() => {
                        layer.open({content:'复制成功', skin:'msg', time:1});
                    }).catch(err => {
                        fallbackCopyText(text); // 如果 Clipboard API 失败，尝试备用方法
                    });
                } else {
                    // 如果浏览器不支持 Clipboard API，使用备用方法
                    fallbackCopyText(text);
                }
            }
            // 备用方法：使用 document.execCommand
            function fallbackCopyText(text) {
                const input = $('<input>').val(text).appendTo('body')[0];
                input.select();
                try {
                    const successful = document.execCommand('copy');
                    if (successful) {
                        layer.open({content:'复制成功', skin:'msg', time:1});
                    } else {
                        layer.open({content:'复制失败', skin:'msg', time:1});
                    }
                } catch (err) {
                    layer.open({content:'复制失败：'+ err, skin:'msg', time:1});
                }
                $(input).remove(); // 移除临时输入框
            }
            function exchange_rules(rules){
                if(rules){
                    layer.open({
                        content: rules,
                        shadeClose: false,
                        closeBtn: 0,
                        btn: ['关闭']
                    })
                }else {
                    layer.open({content:'暂无规则', skin:'msg', time:1});
                }
            }
		</script>
	</body>
</html>