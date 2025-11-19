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
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/search.css?t={pigcms{:time()}"/>
		<link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/order_detail.css?t={pigcms{:time()}"/>

        <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/swiper.css" />
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/weui.css" />
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}jqueryweui/jquery-weui.min.css" />
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_path}groupnew/css/base.css?t={pigcms{:time()}" />
        <script type="text/javascript">
            var main_color = "{pigcms{$config.mobile_head_color}"
        </script>
	</head>
	<body>
		<div class="pageBg"></div>
		<scrollView class="page y two_category">
			<div class="container">
				<div class="order-detail">
                     <!-- 头部商品信息模块 -->
                    <div class="main-box" style="padding: 14px 0;">
                        <if condition="$now_merchant">
                            <div class="store-name link newWin" data-url="{pigcms{:U('merchant',array('mer_id'=>$now_merchant['mer_id'],'page_num'=>$page_num))}">
                                <img src="{pigcms{$now_merchant.img}"/><span>{pigcms{$now_merchant.name}</span>
                            </div>
                        <else/>
                            <div class="store-name link newWin"">
                                <img src="{pigcms{$now_order.banner_img}"/><span>{pigcms{$now_order.name}</span>
                            </div>
                        </if>
                        
                        <div class="flex goods-detail link newWin" data-url="{pigcms{$detail_url}">
                            <img class="flex-shink0" src="{pigcms{:thumb_img($now_order['list_pic'] ?? $now_order['image'],240,240,'fill')}" style="object-fit: cover;"/>
                            <div class="flex flex-zTopBottom flex-justify desc-box flex-1">
                                <if condition="$now_group['name']">
                                    <div class="title">{pigcms{$now_group.name} <if condition="$now_group['s_name'] AND $now_group['name'] " >|</if> {pigcms{$now_group.s_name}</div>
                                <else/>
                                    <div class="title">{pigcms{$now_order.name}</div>
                                </if>
                                <div class="flex flex-justify flex-align" style="margin: 4px 0;">
                                    <div class="flex flex-align">
                                        <div class="now-price flex" style="align-items: baseline;">
                                            <span style="font-weight: 400;font-size: 12px">{pigcms{$config['Currency_symbol']}</span>
                                            <span class="now-price">{pigcms{$now_order.price}</span>
                                        </div>  
                                        <div>
                                            <if condition="$now_order['specifications_info']">
                                                <span class="small-gray old-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_order['specifications_info']['old_price']}</span>
                                            <elseif condition="$now_group['old_price'] gt $now_group['price'] "/>
                                                <span class="small-gray old-price">{pigcms{$config['Currency_symbol']}{pigcms{$now_group.old_price}</span>
                                            </if>
                                        </div>
                                    </div>
                                    <span class="small-gray">×{pigcms{$now_order.num}</span>
                                </div>
                                <if condition="$now_order['specifications_info'] && $now_order['specifications_info']['specifications_name']">
                                    <div class="small-gray">款式：{pigcms{$now_order['specifications_info']['specifications_name']}</div>
                                    <else/>
                                    <p class="small-gray">{pigcms{$now_group.intro}</p>
                                </if>
                            </div>
                        </div>
                    </div>

                    <if condition="$pass_array">
                        <div class="eticket-box main-box">
                            <div class="qcode-box">
                                <!-- Swiper -->
                                <div class="swiper-container qcode-swiper">
                                    <div class="swiper-wrapper">
                                        <volist name="pass_array" id="item">
                                            <div class="swiper-slide" data-group_pass="{pigcms{$item['group_pass']}">
                                                <div class="qcode-detail">
                                                    <div class="qcode-img <if condition="$item['status'] gt 0 || $show_group_pass neq 1">disable</if>">
                                                        <if condition="$item['give_status'] eq 0">
                                                            <!-- 团购券转赠成功遮罩 -->
                                                            <div class="qcode-img-mask">已转赠成功</div>
                                                        </if>
                                                        <if condition="$item['status'] gt 0">
                                                            <div class="qcode-success">
                                                                <img src="{pigcms{$static_path}groupnew/images/success.png" alt="">
                                                            </div>
                                                        </if>
                                                    </div>
                                                    <div class="other-box">
                                                        <div class="desc" style="font-weight: bold;font-size: 16px;color: #333333;margin-bottom:5px">
                                                            <if condition="$item['status'] eq 2">
                                                                已退款
                                                                <elseif condition="$item['status'] eq 1" />
                                                                已核销
                                                                <else />
                                                                未核销
                                                            </if>
                                                        </div>
                                                        <div class="number">{pigcms{$item['group_pass']}</div>
                                                        <div class="coupon-gift">
                                                            <if condition="$now_group['is_give'] eq 1 && $give_submit_show eq 1">
                                                                <button class="btn">转赠</button>
                                                            </if>
                                                        </div>
                                                    </div>
                                                    
                                                        <if condition="$now_order['is_group_combine']">
                                                            <div class="qcode-info">
                                                                <div class="desc">已核销次数 <span>{pigcms{$item['used_count']}</span></div>
                                                                <div class="desc">未核销次数<span>{pigcms{$item['no_use_count']}</span></div>
                                                            </div>
                                                            <elseif condition="$item['verify_time']" />
                                                            <div class="qcode-info">
                                                                <div class="desc">核销时间<span>{pigcms{:date('Y-m-d H:i:s',$item['verify_time'])}</span></div>
                                                                <div class="desc">核销人员<span>{pigcms{$item['staff_name']}</span></div>   
                                                            </div>
                                                        </if>
                                                    
                                                    <if condition="$item['status'] eq 3">
                                                        <div class="qcode-mask">
                                                            <img src="{pigcms{$static_path}groupnew/images/out-time.png" />
                                                        </div>
                                                        <elseif condition="$item['status'] eq 1" />
                                                        <div class="qcode-mask">
                                                            <img src="{pigcms{$static_path}groupnew/images/out-use.png" />
                                                        </div>
                                                    </if>
                                                </div>
                                            </div>
                                        </volist>
                                    </div>
                                    <if condition="count($pass_array) gt 1">
                                        <!-- Add Pagination -->
                                        <div class="swiper-pagination"></div>
                                        <!-- Add Arrows -->
                                        <div class="swiper-button-next"></div>
                                        <div class="swiper-button-prev"></div>
                                    </if>
                                </div>
                            </div>
                            <if condition="$now_group && $now_group['cue_arr']">
                                <div class="use-desc">
                                    <div class="use-title">使用须知</div>
                                    <ul>
                                        <volist name="now_group['cue_arr']" id="item">
                                            <li>
                                                <b>{pigcms{$item['key']}：</b>{pigcms{$item['value']}
                                            </li>
                                        </volist>
                                    </ul>
                                </div>
                            </if>
                        </div>
                    </if>

                    <if condition="$now_group['store_list']">
                        <div class="main-box">
                            <div class="storeList">
                                <div class="title">适用店铺</div>
                                <volist name="now_group['store_list']" id="vo" key="k">
                                    <div class="item <if condition="$k gt 2">hide</if> <if condition="$k gt count($now_group['store_list'])-1">last</if>">
                                        <div class="flex">
                                            <div class="flex-1">
                                                <div class="storeName"><span class="name">{pigcms{$vo.name}</span><if condition="strip_tags($vo['work_time']) neq '-'"><span class="clock">{pigcms{$vo.work_time}</span></if></div>
                                                <div class="storeAddress">{pigcms{$vo.adress}</div>
                                            </div>
                                            <div class="phoneNavi">
                                                <div style="background-image: url({pigcms{$static_path}groupnew/images/phone.png);" class="phone" data-phone="{pigcms{$vo.phone}"></div>
                                                <div style="border-right: 1px solid #F1F1F1;width:1px;height:22px;margin: 0 12px"></div>
                                                <div style="background-image: url({pigcms{$static_path}groupnew/images/navi.png);" class="navi" data-lng="{pigcms{$vo.long}" data-lat="{pigcms{$vo.lat}" data-name="{pigcms{$vo.name}" data-address="{pigcms{$vo.adress}"></div>
                                            </div>
                                        </div>
                                    </div>
                                </volist>
                                <if condition="COUNT($now_group['store_list']) gt 2">
                                    <div class="more iconfont down">&#xe608; &nbsp;展开</div>
                                    <div class="more iconfont up hide">&#xe607; &nbsp;收起</div>
                                </if>
                            </div>
                        </div>
                    </if>

                <div class="main-box" style="padding: 14px 0 !important;">
                    <if condition="$now_order['status'] neq 4 || $now_group.no_refund eq 0 AND $now_group.before_effective_time gt 0 AND $now_group.tuan_type eq 0">
                        <div class="other-info-box" style="padding-top: 0;">
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
                    </if>
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
                                <span class="right"><font style="font-weight: bold;color: red;">
                                    {pigcms{$now_order['refund_total']}</font>{pigcms{$config['Currency_txt']}
                                    <if condition="$now_order['refund_fee'] gt 0">(手续费：{pigcms{$now_order['refund_fee']})</if>
                                </span>
                            </div>

                            <if condition="$pass_info && $pass_info['total_pass_num'] gt 0">
                                <div class="flex flex-justify line">
                                    <span class="left">总核销码数</span>
                                    <span class="right"><font style="font-weight: bold;color: #FDB338;">{pigcms{$pass_info['total_pass_num']}</font></span>
                                </div>
                                <div class="flex flex-justify line">
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
                        <elseif condition="$now_order['status'] eq 4" />
                            <div class="flex flex-justify line">
                                <span class="left">订单未支付已取消</span>
                            </div>
                        </if>
					</div>
                    <!-- <if condition="$is_gift neq 1">
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
                    </if> -->
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
                    
                    <if condition="$write_list">
                        <div class="other-info-box">
                            <volist name="write_list" id="vos">
                                <div class="flex flex-justify line">
                                    <span class="left">{pigcms{$vos.name}</span>
                                    <span class="right">{pigcms{$vos.value}</span>
                                </div>
                            </volist>
                        </div>
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

        <!-- 微信分享提示 -->
        <div class="weixin-share-tips-wrap">
            <div class="top">
                <img src="{pigcms{$static_path}groupnew/images/tipsBg.png" alt="" class="img">
                <span class="desc">点击右上角<br/>分享给团购券给您的好友~</span>
            </div>
            <div class="bottom">
                <img src="{pigcms{$static_path}groupnew/images/btnBg.png" alt="" class="img">
                <span class="desc">我知道了</span>
            </div>
        </div>
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
        <script src="{pigcms{$static_public}js/jquery.qrcode.min.js"></script>
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

        <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/minirefresh.min.css" />
        <script type="text/javascript" src="{pigcms{$static_public}minirefresh/minirefresh.min.js" charset="utf-8"></script>
        <link rel="stylesheet" type="text/css" href="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.css" />
        <script type="text/javascript" src="{pigcms{$static_public}minirefresh/themes/applet/minirefresh.theme.applet.min.js" charset="utf-8"></script>

        <!-- layer -->
        <script type="text/javascript" src="{pigcms{$static_public}jqueryweui/fastclick.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_public}layer/mobile/layer.js" charset="utf-8"></script>

        <script type="text/javascript" src="{pigcms{$static_public}jqueryweui/swiper.min.js" charset="utf-8"></script>

        <script type="text/javascript" src="{pigcms{$static_path}js/iosfunction.js?t={pigcms{:time()}" charset="utf-8"></script>

        <script type="text/javascript" src="{pigcms{$static_public}jqueryweui/jquery-weui.min.js" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}js/common.js?t={pigcms{:time()}" charset="utf-8"></script>
        <script type="text/javascript" src="{pigcms{$static_path}groupnew/js/base.js?t={pigcms{:time()}" charset="utf-8"></script>
        
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
        <script type="text/javascript" >
            $(function () {
                var group_pass = ''
                var sendFriendLinkInit = window.location.protocol + "//" + window.location.hostname + '/packapp/plat/pages/plat_menu/index?pass_id={pigcms{$now_order.share_id}&path=coupon'
                var sendFriendLink = sendFriendLinkInit
                window.shareData = {
                    "moduleName": "Home",
                    "moduleID": "0",
                    "imgUrl": "<if condition="
                    $config['wechat_share_img']
                    ">{pigcms{$now_order.list_pic}<else/>{pigcms{$config.site_logo}</if>",
                    "sendFriendLink": sendFriendLink,
                    "tTitle": "{pigcms{$now_order.user_name}送你一张团购商品券~",
                    "tContent": "{pigcms{$now_order.s_name}"
                };
                // 获取参数
                function GetRequest() {
                    var url = location.search;
                    var theRequest = {};
                    if (url.indexOf("?") != -1) {
                        var str = url.substr(1);
                        strs = str.split("&");
                        for (var i = 0; i < strs.length; i++) {
                            theRequest[strs[i].split("=")[0]] = decodeURIComponent(strs[i].split("=")[1]);
                        }
                    }
                    return theRequest;
                }

                // 设定swiper初始化时的索引 默认0
                var initialSlide = 0
                initialSlide = GetRequest()['key_id']?Number(GetRequest()['key_id']):0

                // 设置分享链接
                function setSendFriendLink() {
                    group_pass = $('.swiper-slide-active').data().group_pass
                    sendFriendLink = sendFriendLinkInit + '&group_pass=' + group_pass
                    if(typeof wx_share_init == 'function'){
                        wx_share_init();
                    }
                }

                var swiper = new Swiper(".qcode-swiper", {
                    // pagination: ".qcode-swiper .swiper-pagination",
                    // paginationClickable: ".qcode-swiper .swiper-pagination",
                    nextButton: ".qcode-swiper .swiper-button-next",
                    prevButton: ".qcode-swiper .swiper-button-prev",
                    initialSlide: initialSlide,
                    onInit: function () {
                        $(
                            ".qcode-swiper .swiper-pagination-bullet-active, .qcode-swiper .swiper-pagination-bullet"
                        ).css("background", main_color);
                        setSendFriendLink()
                    },
                    onTransitionStart: function () {
                        $(
                            ".qcode-swiper .swiper-pagination-bullet-active,.qcode-swiper .swiper-pagination-bullet"
                        ).css("background", main_color);
                        setSendFriendLink()
                    }
                });
            });

        </script>

        <script type="text/javascript">
            setTimeout(function() {
                var qrcode_width = $(window).width() * 0.64 > 154 ? 154 : $(window).width() * 0.64;

                $('.qcode-img').qrcode({
                    width: qrcode_width,
                    height: qrcode_width,
                    text: "{pigcms{$config.site_url}/wap.php?c=Storestaff&a=group_qrcode&order_id={pigcms{$now_order.real_orderid}&id={pigcms{$now_order.group_pass}&is_group_combine={pigcms{$now_order.is_group_combine}&owner={pigcms{$owner}"
                });
            }, 10);

            var get_route = "{pigcms{$config['site_url']}/wap.php?g=Wap&c=Groupnew&a=get_route";
        </script>
        <if condition="$give_submit_show eq 1">
            {pigcms{$shareScript}
        </if>

        <script type="text/javascript">
            $(".coupon-gift .btn").on("click", function() {
                $.confirm(
                    "团购商品一次只可转赠一份可同时转赠给多位好友，但仅第一位领取者能成功领取",
                    function() {
                        var group_pass = $('.swiper-slide-active').data().group_pass
                        //点击确认后的回调函数
                        if (motify.checkWeixinApp()) {
                            // 小程序中
                            // 添加分享参数

                            var wxappUrl = "/pages/plat_menu/index?pass_id={pigcms{$now_order.share_id}&path=coupon&group_pass=" + group_pass
                            var url = "/pages/index/share?title=" +
                                encodeURIComponent(window.shareData.tTitle) +
                                "&shareTitle=" + encodeURIComponent('{pigcms{$now_order.user_name}送你一张团购商品券~') +
                                "&shareImg=" + encodeURIComponent(window.shareData.imgUrl) +
                                "&sharePath=" + encodeURIComponent(wxappUrl) +
                                "&poster=" + encodeURIComponent(window.shareData.imgUrl) +
                                "&bg=" + encodeURIComponent(window.shareData.imgUrl)

                            console.log('分享参数', wxappUrl, url)

                            wx.miniProgram.navigateTo({
                                url: url
                            })
                        } else if (motify.checkApp()) {

                            var wxappUrl = window.location.protocol + '//' + window.location.host + "/packapp/plat/pages/plat_menu/index?pass_id={pigcms{$now_order.share_id}&path=coupon&group_pass=" + group_pass
                            let shareTtitle = window.shareData.tTitle
                            let shareImg = window.shareData.imgUrl
                            let shareDesc = window.shareData.tContent
                            if (motify.checkAndroid()) {
                                window.lifepasslogin.shareWeChat(shareTtitle, shareDesc, shareImg, wxappUrl)
                            } else {
                                var ios_shareWeChat = {
                                    name: 'shareWeChat',
                                    imgUrl: shareImg,
                                    sendFriendLink: wxappUrl,
                                    tTitle: shareTtitle,
                                    tContent: shareDesc
                                }
                                iosCommon.iosFunction('shareWeChat', ios_shareWeChat)
                            }
                        } else if (motify.checkWeixin()) {
                            // 微信公众号环境
                            $('.weixin-share-tips-wrap').show();
                        }
                    },
                    function() {
                        //点击取消后的回调函数
                    }
                );
            });

            $(".weixin-share-tips-wrap").on("click", function() {
                $('.weixin-share-tips-wrap').hide();
            })

            //店铺更多点击
            $(".storeList .more.down").click(function() {
                $(".storeList .item").show();
                // $(this).removeClass("hide");
                $(".storeList .more.down").addClass("hide");
                $(".storeList .more.up").removeClass("hide");
            });
            $(".storeList .more.up").click(function() {
                $(".storeList .item:gt(1)").hide();
                // $(this).removeClass("hide");
                $(".storeList .more.down").removeClass("hide");
                $(".storeList .more.up").addClass("hide");
            });
        </script>

    </body>
</html>